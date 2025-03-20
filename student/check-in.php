<?php
/**
 * student/check-in.php - หน้าเช็คชื่อสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบบทบาท
if ($_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ดึงข้อมูลนักเรียน
$student_sql = "SELECT s.*, u.first_name, u.last_name, u.profile_picture
               FROM students s
               JOIN users u ON s.user_id = u.user_id
               WHERE s.user_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // ไม่พบข้อมูลนักเรียน
    header('Location: register.php');
    exit;
}

$student = $result->fetch_assoc();
$student_id = $student['student_id'];

// ตรวจสอบการตั้งค่าการเช็คชื่อ
$settings_sql = "SELECT as.* 
                FROM attendance_settings as
                JOIN academic_years ay ON as.academic_year_id = ay.academic_year_id
                WHERE ay.is_active = 1";
$settings_result = $conn->query($settings_sql);

if ($settings_result->num_rows === 0) {
    $error_message = "ไม่พบการตั้งค่าการเช็คชื่อ กรุณาติดต่อผู้ดูแลระบบ";
} else {
    $settings = $settings_result->fetch_assoc();
    
    // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
    $current_time = date('H:i:s');
    $start_time = $settings['attendance_start_time'];
    $end_time = $settings['attendance_end_time'];
    
    $can_check_in = ($current_time >= $start_time && $current_time <= $end_time);
    
    // ตรวจสอบว่าเช็คชื่อไปแล้วหรือยัง
    $today = date('Y-m-d');
    $check_attendance_sql = "SELECT * FROM attendance WHERE student_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_attendance_sql);
    $check_stmt->bind_param("is", $student_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    $already_checked_in = ($check_result->num_rows > 0);
    $check_stmt->close();
}

// ประมวลผลการเช็คชื่อ
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่าสามารถเช็คชื่อได้หรือไม่
    if (!$can_check_in && !isset($_POST['admin_override'])) {
        $message = "ไม่สามารถเช็คชื่อได้ เนื่องจากไม่อยู่ในช่วงเวลาเช็คชื่อ";
    } elseif ($already_checked_in && !isset($_POST['admin_override'])) {
        $message = "คุณได้เช็คชื่อไปแล้วในวันนี้";
    } else {
        $check_method = $_POST['check_method'] ?? '';
        
        // ตรวจสอบวิธีการเช็คชื่อ
        switch ($check_method) {
            case 'gps':
                // ตรวจสอบพิกัด GPS
                $lat = $_POST['latitude'] ?? 0;
                $lng = $_POST['longitude'] ?? 0;
                $accuracy = $_POST['accuracy'] ?? 0;
                
                // ตรวจสอบระยะห่างจากจุดศูนย์กลาง
                $center_lat = $settings['gps_center_lat'];
                $center_lng = $settings['gps_center_lng'];
                $allowed_radius = $settings['gps_radius'];
                
                $distance = calculateDistance($lat, $lng, $center_lat, $center_lng);
                
                if ($distance <= $allowed_radius) {
                    // บันทึกการเช็คชื่อ
                    saveAttendance($conn, $student_id, 'GPS', $lat, $lng, null, null);
                    $message = "เช็คชื่อด้วย GPS สำเร็จ";
                    $already_checked_in = true;
                } else {
                    $message = "ไม่สามารถเช็คชื่อได้ เนื่องจากอยู่นอกพื้นที่ที่กำหนด (ห่างเกิน $allowed_radius เมตร)";
                }
                break;
                
            case 'pin':
                // ตรวจสอบรหัส PIN
                $pin_code = $_POST['pin_code'] ?? '';
                
                // ตรวจสอบความถูกต้องของรหัส PIN
                $pin_sql = "SELECT * FROM pins 
                           WHERE pin_code = ? 
                           AND NOW() BETWEEN valid_from AND valid_until 
                           AND is_active = 1";
                $pin_stmt = $conn->prepare($pin_sql);
                $pin_stmt->bind_param("s", $pin_code);
                $pin_stmt->execute();
                $pin_result = $pin_stmt->get_result();
                
                if ($pin_result->num_rows > 0) {
                    $pin_data = $pin_result->fetch_assoc();
                    
                    // ตรวจสอบว่ารหัส PIN นี้ใช้ได้กับห้องเรียนของนักเรียนหรือไม่
                    if ($pin_data['class_id'] === null || $pin_data['class_id'] == $student['current_class_id']) {
                        // บันทึกการเช็คชื่อ
                        saveAttendance($conn, $student_id, 'PIN', null, null, $pin_code, null);
                        $message = "เช็คชื่อด้วยรหัส PIN สำเร็จ";
                        $already_checked_in = true;
                    } else {
                        $message = "รหัส PIN นี้ไม่สามารถใช้ได้กับห้องเรียนของคุณ";
                    }
                } else {
                    $message = "รหัส PIN ไม่ถูกต้องหรือหมดอายุแล้ว";
                }
                $pin_stmt->close();
                break;
                
            case 'qr':
                // ตรวจสอบการสร้าง QR Code
                $qr_action = $_POST['qr_action'] ?? '';
                
                if ($qr_action === 'generate') {
                    // สร้าง QR Code ใหม่
                    $qr_data = "STD-" . $student_id . "-" . date('dmY-His');
                    $valid_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // บันทึกข้อมูล QR Code
                    $qr_sql = "INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, created_at) 
                              VALUES (?, ?, NOW(), ?, NOW())";
                    $qr_stmt = $conn->prepare($qr_sql);
                    $qr_stmt->bind_param("iss", $student_id, $qr_data, $valid_until);
                    
                    if ($qr_stmt->execute()) {
                        $qr_code_id = $conn->insert_id;
                        $message = "สร้าง QR Code สำเร็จ แสดงให้ครูสแกนเพื่อเช็คชื่อ";
                        $qr_code_data = $qr_data;
                        $qr_expiry = $valid_until;
                    } else {
                        $message = "เกิดข้อผิดพลาดในการสร้าง QR Code";
                    }
                    $qr_stmt->close();
                }
                break;
                
            case 'photo':
                // ตรวจสอบการอัปโหลดรูปภาพ
                if (isset($_FILES['attendance_photo']) && $_FILES['attendance_photo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/attendance/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['attendance_photo']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'attendance_' . $student_id . '_' . date('Ymd_His') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['attendance_photo']['tmp_name'], $upload_path)) {
                        $photo_url = 'uploads/attendance/' . $new_filename;
                        
                        // บันทึกการเช็คชื่อ
                        saveAttendance($conn, $student_id, 'Manual', null, null, null, $photo_url);
                        $message = "เช็คชื่อด้วยรูปภาพสำเร็จ";
                        $already_checked_in = true;
                    } else {
                        $message = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                    }
                } else {
                    $message = "กรุณาอัปโหลดรูปภาพ";
                }
                break;
                
            default:
                $message = "กรุณาเลือกวิธีการเช็คชื่อ";
                break;
        }
    }
}

// ฟังก์ชันคำนวณระยะห่างระหว่างพิกัด GPS (หน่วยเป็นเมตร)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // รัศมีของโลก (เมตร)
    
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return $distance;
}

// ฟังก์ชันบันทึกการเช็คชื่อ
function saveAttendance($conn, $student_id, $check_method, $lat, $lng, $pin_code, $photo_url) {
    // ค้นหาปีการศึกษาปัจจุบัน
    $year_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $year_result = $conn->query($year_sql);
    $year_row = $year_result->fetch_assoc();
    $academic_year_id = $year_row['academic_year_id'];
    
    // เตรียมข้อมูลการเช็คชื่อ
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $user_id = $_SESSION['user_id'];
    
    // เพิ่มหรืออัปเดตข้อมูลการเช็คชื่อ
    $check_sql = "SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $student_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // มีข้อมูลอยู่แล้ว ให้อัปเดต
        $attendance_row = $check_result->fetch_assoc();
        $attendance_id = $attendance_row['attendance_id'];
        
        $update_sql = "UPDATE attendance SET 
                      check_method = ?, 
                      checker_user_id = ?, 
                      location_lat = ?, 
                      location_lng = ?, 
                      photo_url = ?, 
                      pin_code = ?, 
                      check_time = ?
                      WHERE attendance_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("siddsssi", 
                              $check_method, 
                              $user_id, 
                              $lat, 
                              $lng, 
                              $photo_url, 
                              $pin_code, 
                              $current_time, 
                              $attendance_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // ยังไม่มีข้อมูล ให้เพิ่มใหม่
        $insert_sql = "INSERT INTO attendance (
                      student_id, 
                      academic_year_id, 
                      date, 
                      is_present, 
                      check_method, 
                      checker_user_id, 
                      location_lat, 
                      location_lng, 
                      photo_url, 
                      pin_code, 
                      check_time, 
                      created_at
                    ) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiississs", 
                             $student_id, 
                             $academic_year_id, 
                             $today, 
                             $check_method, 
                             $user_id, 
                             $lat, 
                             $lng, 
                             $photo_url, 
                             $pin_code, 
                             $current_time);
        $insert_stmt->execute();
        $insert_stmt->close();
        
        // อัปเดตจำนวนวันเข้าแถวในตาราง student_academic_records
        $record_sql = "UPDATE student_academic_records 
                      SET total_attendance_days = total_attendance_days + 1 
                      WHERE student_id = ? AND academic_year_id = ?";
        $record_stmt = $conn->prepare($record_sql);
        $record_stmt->bind_param("ii", $student_id, $academic_year_id);
        $record_stmt->execute();
        $record_stmt->close();
    }
    
    $check_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - เช็คชื่อเข้าแถว</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/student-checkin.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>เช็คชื่อเข้าแถว</h1>
        <div class="header-icon">
            <span class="material-icons">help_outline</span>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'สำเร็จ') !== false ? 'alert-success' : 'alert-error'; ?>">
            <span class="material-icons"><?php echo strpos($message, 'สำเร็จ') !== false ? 'check_circle' : 'error'; ?></span>
            <span><?php echo $message; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- การ์ดเวลาและสถานะ -->
        <div class="status-card">
            <div class="date-display">
                <div class="current-date"><?php echo date('l, d F Y'); ?></div>
            </div>
            <div class="time-display" id="current-time">--:--:--</div>
            <div class="time-description">เวลาเช็คชื่อ: <?php echo date('H:i', strtotime($start_time)); ?> - <?php echo date('H:i', strtotime($end_time)); ?> น.</div>
            <div class="status-indicator <?php echo $can_check_in ? 'status-open' : 'status-closed'; ?>">
                <?php echo $can_check_in ? 'เปิดให้เช็คชื่อ' : 'ปิดการเช็คชื่อแล้ว'; ?>
            </div>
            
            <?php if ($already_checked_in): ?>
            <div class="checked-in-status">
                <span class="material-icons">check_circle</span>
                <span>คุณได้เช็คชื่อแล้วในวันนี้</span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$already_checked_in): ?>
        <!-- วิธีการเช็คชื่อ -->
        <div class="check-methods-card">
            <div class="card-title">
                <span class="material-icons">how_to_reg</span> เลือกวิธีการเช็คชื่อ
            </div>
            
            <div class="method-grid">
                <div class="method-card" onclick="showMethod('gps')">
                    <div class="method-icon gps">
                        <span class="material-icons">gps_fixed</span>
                    </div>
                    <div class="method-name">GPS</div>
                    <div class="method-description">เช็คชื่อด้วยตำแหน่งที่ตั้ง</div>
                </div>
                
                <div class="method-card" onclick="showMethod('qr')">
                    <div class="method-icon qr">
                        <span class="material-icons">qr_code_2</span>
                    </div>
                    <div class="method-name">QR Code</div>
                    <div class="method-description">สร้าง QR เพื่อให้ครูสแกน</div>
                </div>
                
                <div class="method-card" onclick="showMethod('pin')">
                    <div class="method-icon pin">
                        <span class="material-icons">pin</span>
                    </div>
                    <div class="method-name">รหัส PIN</div>
                    <div class="method-description">ใส่รหัส PIN จากครู</div>
                </div>
                
                <div class="method-card" onclick="showMethod('photo')">
                    <div class="method-icon photo">
                        <span class="material-icons">add_a_photo</span>
                    </div>
                    <div class="method-name">ถ่ายรูป</div>
                    <div class="method-description">อัพโหลดรูปเข้าแถว</div>
                </div>
            </div>
        </div>

        <!-- GPS Method -->
        <div class="gps-card" id="gps-method">
            <div class="card-title">
                <span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS
            </div>
            
            <div class="gps-status">
                <div class="gps-icon">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="gps-text" id="gps-status-text">กำลังตรวจสอบตำแหน่ง</div>
                <div class="gps-subtext" id="gps-status-subtext">โปรดรอสักครู่...</div>
            </div>
            
            <div class="gps-details">
                <div class="gps-detail-item">
                    <div class="gps-detail-label">สถานะ GPS:</div>
                    <div class="gps-detail-value" id="gps-status">กำลังตรวจสอบ...</div>
                </div>
                <div class="gps-detail-item">
                    <div class="gps-detail-label">ระยะห่างจากโรงเรียน:</div>
                    <div class="gps-detail-value" id="gps-distance">กำลังคำนวณ...</div>
                </div>
                <div class="gps-detail-item">
                    <div class="gps-detail-label">ความแม่นยำ:</div>
                    <div class="gps-detail-value" id="gps-accuracy">กำลังคำนวณ...</div>
                </div>
            </div>
            
            <form method="POST" id="gps-form">
                <input type="hidden" name="check_method" value="gps">
                <input type="hidden" name="latitude" id="latitude" value="">
                <input type="hidden" name="longitude" id="longitude" value="">
                <input type="hidden" name="accuracy" id="accuracy" value="">
                <button type="submit" class="gps-action" id="gps-submit" disabled>
                    <span class="material-icons">check</span> ยืนยันการเช็คชื่อด้วย GPS
                </button>
            </form>
        </div>

        <!-- QR Code Method -->
        <div class="qr-card" id="qr-method">
            <div class="card-title">
                <span class="material-icons">qr_code_2</span> เช็คชื่อด้วย QR Code
            </div>
            
            <div class="qr-container">
                <div class="qr-code">
                    <?php if (isset($qr_code_data)): ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qr_code_data); ?>&size=200x200" alt="QR Code" id="qr-image">
                    <?php else: ?>
                    <div class="qr-placeholder">
                        <span class="material-icons">qr_code_2</span>
                        <p>กดปุ่มสร้าง QR Code</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="qr-info">
                    <?php if (isset($qr_code_data)): ?>
                    QR Code นี้จะหมดอายุใน <span id="qr-timer">5:00</span> นาที<br>
                    แสดงให้ครูสแกนเพื่อเช็คชื่อ
                    <?php else: ?>
                    กดปุ่มด้านล่างเพื่อสร้าง QR Code สำหรับให้ครูสแกนเช็คชื่อ
                    <?php endif; ?>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="check_method" value="qr">
                <input type="hidden" name="qr_action" value="generate">
                <button type="submit" class="qr-refresh">
                    <span class="material-icons">refresh</span> สร้าง QR Code
                </button>
            </form>
        </div>

        <!-- PIN Method -->
        <div class="pin-card" id="pin-method">
            <div class="card-title">
                <span class="material-icons">pin</span> เช็คชื่อด้วยรหัส PIN
            </div>
            
            <div class="pin-input-container">
                <form method="POST" id="pin-form">
                    <input type="hidden" name="check_method" value="pin">
                    <div class="pin-input">
                        <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                        <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                        <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                        <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                    </div>
                    <input type="hidden" name="pin_code" id="pin-code-hidden">
                    <div class="pin-info">
                        กรอกรหัส PIN 4 หลักที่ได้รับจากครู
                    </div>
                </form>
            </div>
            
            <button type="button" class="pin-submit" id="pin-submit-btn">
                <span class="material-icons">check</span> ยืนยันรหัส PIN
            </button>
        </div>

        <!-- อัพโหลดรูปภาพ -->
        <div class="upload-card" id="photo-method">
            <div class="card-title">
                <span class="material-icons">add_a_photo</span> อัพโหลดรูปภาพการเข้าแถว
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="photo-form">
                <input type="hidden" name="check_method" value="photo">
                
                <div class="upload-area" onclick="document.getElementById('attendance_photo').click()">
                    <input type="file" id="attendance_photo" name="attendance_photo" style="display: none;" accept="image/*" onchange="previewImage(this)">
                    <div class="upload-icon">
                        <span class="material-icons">cloud_upload</span>
                    </div>
                    <div class="upload-text">คลิกเพื่ออัพโหลดภาพถ่ายการเข้าแถว</div>
                    <div class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</div>
                </div>
                
                <div class="upload-preview" id="image-preview" style="display: none;">
                    <div class="preview-title">
                        <span class="material-icons">photo</span> ภาพตัวอย่าง
                    </div>
                    <img src="#" class="preview-image" id="preview-img" alt="ภาพตัวอย่าง">
                    
                    <div class="upload-actions">
                        <button type="button" class="upload-button secondary" onclick="resetImage()">
                            <span class="material-icons">refresh</span> เลือกใหม่
                        </button>
                        <button type="submit" class="upload-button primary">
                            <span class="material-icons">file_upload</span> อัพโหลด
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php else: ?>
        <!-- การ์ดเช็คชื่อสำเร็จ -->
        <div class="success-card">
            <div class="success-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="success-message">
                <h2>เช็คชื่อสำเร็จแล้ว</h2>
                <p>คุณได้เช็คชื่อเข้าแถวสำหรับวันนี้เรียบร้อยแล้ว</p>
                <p>วิธีที่ใช้: <span id="check-method">
                    <?php 
                        $check_result = $check_result->fetch_assoc();
                        echo $check_result['check_method'];
                    ?>
                </span></p>
                <p>เวลาที่เช็คชื่อ: <span id="check-time">
                    <?php echo date('H:i น.', strtotime($check_result['check_time'])); ?>
                </span></p>
            </div>
            <button class="btn primary" onclick="window.location.href='dashboard.php'">
                กลับหน้าหลัก <span class="material-icons">home</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script>
        // อัพเดทเวลาปัจจุบัน
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        // อัพเดทเวลาทุกวินาที
        setInterval(updateTime, 1000);
        updateTime(); // เรียกใช้งานครั้งแรก
        
        // แสดงวิธีการเช็คชื่อที่เลือก
        function showMethod(method) {
            // ซ่อนวิธีการเช็คชื่อทั้งหมด
            document.getElementById('gps-method').style.display = 'none';
            document.getElementById('qr-method').style.display = 'none';
            document.getElementById('pin-method').style.display = 'none';
            document.getElementById('photo-method').style.display = 'none';
            
            // แสดงวิธีการที่เลือก
            if (method === 'gps') {
                document.getElementById('gps-method').style.display = 'block';
                initGPS(); // เริ่มต้นการตรวจสอบ GPS
            } else if (method === 'qr') {
                document.getElementById('qr-method').style.display = 'block';
                initQRTimer(); // เริ่มต้นตัวจับเวลา QR Code
            } else if (method === 'pin') {
                document.getElementById('pin-method').style.display = 'block';
                setupPinInputs(); // ตั้งค่า input สำหรับรหัส PIN
            } else if (method === 'photo') {
                document.getElementById('photo-method').style.display = 'block';
            }
        }
        
        // ตั้งค่า input สำหรับรหัส PIN
        function setupPinInputs() {
            const pinDigits = document.querySelectorAll('.pin-digit');
            
            // เพิ่ม event listener สำหรับ input แต่ละตัว
            pinDigits.forEach((input, index) => {
                // เมื่อพิมพ์ตัวเลข ให้เลื่อนไปยัง input ถัดไป
                input.addEventListener('input', function() {
                    if (this.value.length === 1) {
                        if (index < pinDigits.length - 1) {
                            pinDigits[index + 1].focus();
                        }
                    }
                });
                
                // เมื่อกด Backspace ให้เลื่อนไปยัง input ก่อนหน้า
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0) {
                        if (index > 0) {
                            pinDigits[index - 1].focus();
                        }
                    }
                });
            });
            
            // ตั้งค่า focus ที่ input แรก
            pinDigits[0].focus();
            
            // ตั้งค่าการส่งฟอร์มเมื่อคลิกปุ่มยืนยัน
            document.getElementById('pin-submit-btn').addEventListener('click', function() {
                const pinCode = Array.from(pinDigits).map(input => input.value).join('');
                if (pinCode.length === 4) {
                    document.getElementById('pin-code-hidden').value = pinCode;
                    document.getElementById('pin-form').submit();
                } else {
                    alert('กรุณากรอกรหัส PIN ให้ครบ 4 หลัก');
                }
            });
        }
        
        // แสดงตัวอย่างรูปภาพ
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                    document.querySelector('.upload-area').style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // รีเซ็ตรูปภาพ
        function resetImage() {
            document.getElementById('attendance_photo').value = '';
            document.getElementById('image-preview').style.display = 'none';
            document.querySelector('.upload-area').style.display = 'block';
        }
        
        // ตั้งค่าการตรวจสอบ GPS
        function initGPS() {
            if (navigator.geolocation) {
                document.getElementById('gps-status-text').textContent = "กำลังตรวจสอบตำแหน่ง";
                document.getElementById('gps-status-subtext').textContent = "โปรดรอสักครู่...";
                document.getElementById('gps-status').textContent = "กำลังตรวจสอบ...";
                document.getElementById('gps-distance').textContent = "กำลังคำนวณ...";
                document.getElementById('gps-accuracy').textContent = "กำลังคำนวณ...";
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // รับพิกัดปัจจุบัน
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        // พิกัดของโรงเรียน (จากการตั้งค่าในฐานข้อมูล)
                        const schoolLat = <?php echo $settings['gps_center_lat'] ?? '0'; ?>;
                        const schoolLng = <?php echo $settings['gps_center_lng'] ?? '0'; ?>;
                        const allowedRadius = <?php echo $settings['gps_radius'] ?? '0'; ?>;
                        
                        // คำนวณระยะห่าง
                        const distance = calculateDistance(lat, lng, schoolLat, schoolLng);
                        
                        // อัพเดทค่าใน form
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        document.getElementById('accuracy').value = accuracy;
                        
                        // อัพเดทข้อมูลในหน้าจอ
                        document.getElementById('gps-status').textContent = "พร้อมใช้งาน";
                        document.getElementById('gps-distance').textContent = `${Math.round(distance)} เมตร`;
                        document.getElementById('gps-accuracy').textContent = `± ${Math.round(accuracy)} เมตร`;
                        
                        if (distance <= allowedRadius) {
                            document.getElementById('gps-status-text').textContent = "คุณอยู่ในพื้นที่ที่กำหนด";
                            document.getElementById('gps-status-subtext').textContent = "สามารถเช็คชื่อได้";
                            document.getElementById('gps-submit').disabled = false;
                        } else {
                            document.getElementById('gps-status-text').textContent = "คุณอยู่นอกพื้นที่ที่กำหนด";
                            document.getElementById('gps-status-subtext').textContent = `ระยะห่างเกิน ${allowedRadius} เมตร`;
                            document.getElementById('gps-submit').disabled = true;
                        }
                    },
                    function(error) {
                        // เกิดข้อผิดพลาดในการรับพิกัด
                        document.getElementById('gps-status-text').textContent = "ไม่สามารถรับพิกัดได้";
                        document.getElementById('gps-status-subtext').textContent = getLocationErrorMessage(error);
                        document.getElementById('gps-status').textContent = "ไม่พร้อมใช้งาน";
                        document.getElementById('gps-submit').disabled = true;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                document.getElementById('gps-status-text').textContent = "ไม่รองรับ GPS";
                document.getElementById('gps-status-subtext').textContent = "อุปกรณ์นี้ไม่รองรับการใช้งาน GPS";
                document.getElementById('gps-status').textContent = "ไม่พร้อมใช้งาน";
                document.getElementById('gps-submit').disabled = true;
            }
        }
        
        // คำนวณระยะห่างระหว่างพิกัด
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // รัศมีของโลกในเมตร
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            
            return R * c; // ระยะทางในเมตร
        }
        
        // แปลข้อผิดพลาดของ Geolocation
        function getLocationErrorMessage(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    return "คุณไม่ได้อนุญาตให้เข้าถึงตำแหน่ง";
                case error.POSITION_UNAVAILABLE:
                    return "ไม่สามารถรับข้อมูลตำแหน่งได้";
                case error.TIMEOUT:
                    return "หมดเวลาในการรับข้อมูลตำแหน่ง";
                case error.UNKNOWN_ERROR:
                    return "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
                default:
                    return "เกิดข้อผิดพลาดในการรับพิกัด";
            }
        }
        
        // ตั้งค่าตัวจับเวลา QR Code
        function initQRTimer() {
            <?php if (isset($qr_expiry)): ?>
            const expiryTime = new Date("<?php echo $qr_expiry; ?>").getTime();
            
            const qrTimer = setInterval(function() {
                const now = new Date().getTime();
                const distance = expiryTime - now;
                
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById("qr-timer").textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                
                if (distance < 0) {
                    clearInterval(qrTimer);
                    document.getElementById("qr-timer").textContent = "หมดเวลา";
                    // อาจจะเพิ่มการรีเฟรชหน้าหรือแสดงข้อความให้สร้าง QR ใหม่ด้วย
                }
            }, 1000);
            <?php endif; ?>
        }
    </script>
</body>
</html>
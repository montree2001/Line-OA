<?php
/**
 * student/check-in.php - หน้าเช็คชื่อสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';



// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// รับข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$page_title = "เช็คชื่อเข้าแถว";

try {
    // เชื่อมต่อฐานข้อมูล
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $stmt = $db->prepare("
        SELECT s.*, d.name AS department_name
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$studentData) {
        throw new Exception("ไม่พบข้อมูลนักเรียน");
    }
    
    // ตรวจสอบการตั้งค่าการเช็คชื่อ
    $stmt = $db->prepare("
        SELECT `as`.*
        FROM attendance_settings `as`
        JOIN academic_years ay ON `as`.academic_year_id = ay.academic_year_id
        WHERE ay.is_active = 1
    ");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        $error_message = "ไม่พบการตั้งค่าการเช็คชื่อ กรุณาติดต่อผู้ดูแลระบบ";
        $settings = [
            'attendance_start_time' => '08:00:00',
            'attendance_end_time' => '08:30:00',
            'gps_center_lat' => 0,
            'gps_center_lng' => 0,
            'gps_radius' => 100
        ];
        $can_check_in = false;
    } else {
        // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
        $current_time = date('H:i:s');
        $start_time = $settings['attendance_start_time'];
        $end_time = $settings['attendance_end_time'];
        
        $can_check_in = ($current_time >= $start_time && $current_time <= $end_time);
    }
    
    // ตรวจสอบว่าเช็คชื่อไปแล้วหรือยัง
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$studentData['id'], $today]);
    $check_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $already_checked_in = ($check_result !== false);
    
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
                        saveAttendance($db, $studentData['id'], 'GPS', $lat, $lng, null, null);
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
                    $pin_stmt = $db->prepare($pin_sql);
                    $pin_stmt->execute([$pin_code]);
                    $pin_result = $pin_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($pin_result) {
                        $pin_data = $pin_result;
                        
                        // ตรวจสอบว่ารหัส PIN นี้ใช้ได้กับห้องเรียนของนักเรียนหรือไม่
                        if ($pin_data['class_id'] === null || $pin_data['class_id'] == $studentData['current_class_id']) {
                            // บันทึกการเช็คชื่อ
                            saveAttendance($db, $studentData['id'], 'PIN', null, null, $pin_code, null);
                            $message = "เช็คชื่อด้วยรหัส PIN สำเร็จ";
                            $already_checked_in = true;
                        } else {
                            $message = "รหัส PIN นี้ไม่สามารถใช้ได้กับห้องเรียนของคุณ";
                        }
                    } else {
                        $message = "รหัส PIN ไม่ถูกต้องหรือหมดอายุแล้ว";
                    }
                    $pin_stmt->closeCursor();
                    break;
                    
                case 'qr':
                    // ตรวจสอบการสร้าง QR Code
                    $qr_action = $_POST['qr_action'] ?? '';
                    
                    if ($qr_action === 'generate') {
                        // สร้าง QR Code ใหม่
                        $qr_data = "STD-" . $studentData['id'] . "-" . date('dmY-His');
                        $valid_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                        
                        // บันทึกข้อมูล QR Code
                        $qr_sql = "INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, created_at) 
                                  VALUES (?, ?, NOW(), ?, NOW())";
                        $qr_stmt = $db->prepare($qr_sql);
                        $qr_stmt->execute([$studentData['id'], $qr_data, $valid_until]);
                        
                        if ($qr_stmt->rowCount() > 0) {
                            $qr_code_id = $db->lastInsertId();
                            $message = "สร้าง QR Code สำเร็จ แสดงให้ครูสแกนเพื่อเช็คชื่อ";
                            $qr_code_data = $qr_data;
                            $qr_expiry = $valid_until;
                        } else {
                            $message = "เกิดข้อผิดพลาดในการสร้าง QR Code";
                        }
                        $qr_stmt->closeCursor();
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
                        $new_filename = 'attendance_' . $studentData['id'] . '_' . date('Ymd_His') . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['attendance_photo']['tmp_name'], $upload_path)) {
                            $photo_url = 'uploads/attendance/' . $new_filename;
                            
                            // บันทึกการเช็คชื่อ
                            saveAttendance($db, $studentData['id'], 'Manual', null, null, null, $photo_url);
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
    
    // โหลดหน้าเนื้อหา
    $content_path = 'pages/check_in_content.php';
    include_once 'templates/main_template.php';
    
} catch (Exception $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    error_log("Error in check-in page: " . $e->getMessage());
    header('Location: error.php');
    exit;
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
function saveAttendance($db, $student_id, $check_method, $lat, $lng, $pin_code, $photo_url) {
    // ค้นหาปีการศึกษาปัจจุบัน
    $year_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $year_stmt = $db->prepare($year_sql);
    $year_stmt->execute();
    $year_row = $year_stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $year_row['academic_year_id'];
    
    // เตรียมข้อมูลการเช็คชื่อ
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $user_id = $_SESSION['user_id'];
    
    // เพิ่มหรืออัปเดตข้อมูลการเช็คชื่อ
    $check_sql = "SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([$student_id, $today]);
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_result) {
        // มีข้อมูลอยู่แล้ว ให้อัปเดต
        $attendance_id = $check_result['attendance_id'];
        
        $update_sql = "UPDATE attendance SET 
                      check_method = ?, 
                      checker_user_id = ?, 
                      location_lat = ?, 
                      location_lng = ?, 
                      photo_url = ?, 
                      pin_code = ?, 
                      check_time = ?
                      WHERE attendance_id = ?";
        
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute([
            $check_method, 
            $user_id, 
            $lat, 
            $lng, 
            $photo_url, 
            $pin_code, 
            $current_time, 
            $attendance_id
        ]);
        $update_stmt->closeCursor();
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
        
        $insert_stmt = $db->prepare($insert_sql);
        $insert_stmt->execute([
            $student_id, 
            $academic_year_id, 
            $today, 
            $check_method, 
            $user_id, 
            $lat, 
            $lng, 
            $photo_url, 
            $pin_code, 
            $current_time
        ]);
        $insert_stmt->closeCursor();
        
        // อัปเดตจำนวนวันเข้าแถวในตาราง student_academic_records
        $record_sql = "UPDATE student_academic_records 
                      SET total_attendance_days = total_attendance_days + 1 
                      WHERE student_id = ? AND academic_year_id = ?";
        $record_stmt = $db->prepare($record_sql);
        $record_stmt->execute([$student_id, $academic_year_id]);
        $record_stmt->closeCursor();
    }
    
    $check_stmt->closeCursor();
}
?>
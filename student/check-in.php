<?php
/**
 * check-in.php - หน้าเช็คชื่อเข้าแถวสำหรับนักเรียน
 */
session_start();
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// รับข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$student_id = 0; // จะถูกแทนที่ด้วยค่าจริงหลังจากค้นหาข้อมูลนักเรียน
$page_title = "เช็คชื่อเข้าแถว";
$message = '';
$error = '';

try {
    // เชื่อมต่อฐานข้อมูล
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $stmt = $db->prepare("
        SELECT s.*, u.first_name, u.last_name, u.phone_number, u.email, u.profile_picture
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("ไม่พบข้อมูลนักเรียน");
    }
    
    $student_id = $student['student_id'];
    $student_name = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
    
    // ค้นหาปีการศึกษาปัจจุบัน
    $stmt = $db->prepare("SELECT * FROM academic_years WHERE is_active = 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // ดึงการตั้งค่าการเช็คชื่อจากตาราง system_settings
    $stmt = $db->prepare("
        SELECT 
            (SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time') AS start_time,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time') AS end_time,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius') AS gps_radius,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude') AS school_latitude,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude') AS school_longitude,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'require_photo') AS require_photo
    ");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // กำหนดค่าตั้งต้นในกรณีที่ไม่พบข้อมูล
    if (!$settings) {
        $settings = [
            'start_time' => '07:30',
            'end_time' => '08:30',
            'gps_radius' => 100,
            'school_latitude' => 0,
            'school_longitude' => 0,
            'require_photo' => 0
        ];
        $error = "ไม่พบการตั้งค่าการเช็คชื่อ กรุณาติดต่อผู้ดูแลระบบ";
    }
    
    // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
    $current_time = date('H:i');
    $start_time = $settings['start_time'];
    $end_time = $settings['end_time'];
    
    $can_check_in = true;
    
    // ตรวจสอบเวลาให้เป็นตัวเลขเพื่อการเปรียบเทียบ
    $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
    $start_minutes = (intval(substr($start_time, 0, 2)) * 60) + intval(substr($start_time, 3, 2));
    $end_minutes = (intval(substr($end_time, 0, 2)) * 60) + intval(substr($end_time, 3, 2));
    
    if ($current_minutes < $start_minutes || $current_minutes > $end_minutes) {
        $can_check_in = false;
    }
    
    // ตรวจสอบว่าเช็คชื่อไปแล้วหรือยัง
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student_id, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $already_checked_in = ($attendance !== false);
    
    // สำหรับแสดงในหน้าเว็บ
    $check_in_time_range = $start_time . " - " . $end_time . " น.";
    $check_in_open = $can_check_in;
    
    // แปลงวันที่เป็นภาษาไทย
    $thai_month_names = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $thai_day_names = [
        'อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'
    ];
    
    $day_of_week = date('w');
    $day = date('j');
    $month = date('n');
    $year = date('Y') + 543; // แปลงเป็น พ.ศ.
    
    $thai_date = "วัน" . $thai_day_names[$day_of_week] . "ที่ " . $day . " " . $thai_month_names[$month] . " " . $year;
    
    // ประมวลผลการเช็คชื่อ
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['check_method'])) {
            $check_method = $_POST['check_method'];
            
            // ตรวจสอบว่าสามารถเช็คชื่อได้หรือไม่
            if (!$can_check_in && !isset($_POST['admin_override'])) {
                $error = "ไม่สามารถเช็คชื่อได้ในขณะนี้ เนื่องจากไม่อยู่ในช่วงเวลาเช็คชื่อ";
            } elseif ($already_checked_in && !isset($_POST['admin_override'])) {
                $message = "คุณได้เช็คชื่อไปแล้วในวันนี้";
            } else {
                // ดำเนินการเช็คชื่อตามวิธีที่เลือก
                switch ($check_method) {
                    case 'gps':
                        // ตรวจสอบพิกัด GPS
                        $lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
                        $lng = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
                        $accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : 0;
                        
                        if ($lat == 0 || $lng == 0) {
                            $error = "ไม่สามารถรับพิกัดของคุณได้ กรุณาอนุญาตให้เข้าถึงตำแหน่งที่ตั้ง";
                        } else {
                            // คำนวณระยะห่างจากโรงเรียน
                            $school_lat = floatval($settings['school_latitude']);
                            $school_lng = floatval($settings['school_longitude']);
                            $radius = intval($settings['gps_radius']);
                            
                            $distance = calculateDistance($lat, $lng, $school_lat, $school_lng);
                            
                            if ($distance <= $radius) {
                                saveAttendance($db, $student_id, $academic_year_id, 'GPS', $lat, $lng);
                                $message = "เช็คชื่อด้วย GPS สำเร็จ!";
                                $already_checked_in = true;
                            } else {
                                $error = "ไม่สามารถเช็คชื่อได้ เนื่องจากอยู่ห่างจากโรงเรียนเกิน " . $radius . " เมตร (ระยะห่างปัจจุบัน: " . round($distance) . " เมตร)";
                            }
                        }
                        break;
                        
                    case 'qr':
                        // สร้าง QR Code สำหรับให้ครูสแกน
                        $qr_action = isset($_POST['qr_action']) ? $_POST['qr_action'] : '';
                        
                        if ($qr_action === 'generate') {
                            $token = md5($student_id . time() . rand(1000, 9999));
                            $qr_data = json_encode([
                                'type' => 'student_link',
                                'student_id' => $student_id,
                                'student_code' => $student['student_code'],
                                'token' => $token,
                                'expire_time' => date('Y-m-d H:i:s', strtotime('+7 days'))
                            ]);
                            
                            // เพิ่มหรืออัปเดต QR Code ในฐานข้อมูล
                            $stmt = $db->prepare("
                                INSERT INTO qr_codes 
                                (student_id, qr_code_data, valid_from, valid_until, is_active, created_at) 
                                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 1, NOW())
                            ");
                            
                            $result = $stmt->execute([$student_id, $qr_data]);
                            
                            if ($result) {
                                $qr_code_id = $db->lastInsertId();
                                $message = "สร้าง QR Code สำเร็จ กรุณาแสดงให้ครูสแกนเพื่อเช็คชื่อ";
                            } else {
                                $error = "เกิดข้อผิดพลาดในการสร้าง QR Code";
                            }
                        } else {
                            $error = "กรุณาเลือกการสร้าง QR Code";
                        }
                        break;
                        
                    case 'pin':
                        // ตรวจสอบรหัส PIN
                        $pin_digits = isset($_POST['pin']) ? $_POST['pin'] : [];
                        $pin_code = implode('', $pin_digits);
                        
                        if (strlen($pin_code) !== 4 || !is_numeric($pin_code)) {
                            $error = "รหัส PIN ต้องเป็นตัวเลข 4 หลัก";
                        } else {
                            // ตรวจสอบรหัส PIN ในฐานข้อมูล
                            $stmt = $db->prepare("
                                SELECT * FROM pins 
                                WHERE pin_code = ? 
                                AND is_active = 1 
                                AND NOW() BETWEEN valid_from AND valid_until
                            ");
                            $stmt->execute([$pin_code]);
                            $pin_data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($pin_data) {
                                // ตรวจสอบว่า PIN นี้ใช้ได้กับห้องเรียนนี้หรือไม่
                                $pin_class_id = $pin_data['class_id'];
                                $student_class_id = $student['current_class_id'];
                                
                                if ($pin_class_id === null || $pin_class_id == $student_class_id) {
                                    saveAttendance($db, $student_id, $academic_year_id, 'PIN', null, null, $pin_code);
                                    $message = "เช็คชื่อด้วยรหัส PIN สำเร็จ!";
                                    $already_checked_in = true;
                                } else {
                                    $error = "รหัส PIN นี้ไม่สามารถใช้ได้กับห้องเรียนของคุณ";
                                }
                            } else {
                                $error = "รหัส PIN ไม่ถูกต้องหรือหมดอายุแล้ว";
                            }
                        }
                        break;
                        
                    case 'photo':
                        // ตรวจสอบการอัปโหลดรูปภาพ
                        if (isset($_FILES['attendance_photo']) && $_FILES['attendance_photo']['error'] === UPLOAD_ERR_OK) {
                            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                            $max_size = 5 * 1024 * 1024; // 5MB
                            
                            if (!in_array($_FILES['attendance_photo']['type'], $allowed_types)) {
                                $error = "รูปภาพต้องเป็นไฟล์ JPG หรือ PNG เท่านั้น";
                            } elseif ($_FILES['attendance_photo']['size'] > $max_size) {
                                $error = "รูปภาพต้องมีขนาดไม่เกิน 5MB";
                            } else {
                                // สร้างโฟลเดอร์ถ้ายังไม่มี
                                $upload_dir = '../uploads/attendance/' . date('Y/m/d/');
                                if (!file_exists($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                
                                // สร้างชื่อไฟล์
                                $file_ext = pathinfo($_FILES['attendance_photo']['name'], PATHINFO_EXTENSION);
                                $new_filename = $student_id . '_' . date('YmdHis') . '_' . rand(1000, 9999) . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($_FILES['attendance_photo']['tmp_name'], $upload_path)) {
                                    $photo_url = 'uploads/attendance/' . date('Y/m/d/') . $new_filename;
                                    
                                    saveAttendance($db, $student_id, $academic_year_id, 'Photo', null, null, null, $photo_url);
                                    $message = "เช็คชื่อด้วยรูปภาพสำเร็จ!";
                                    $already_checked_in = true;
                                } else {
                                    $error = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                                }
                            }
                        } else {
                            $error = "กรุณาอัปโหลดรูปภาพ";
                        }
                        break;
                        
                    default:
                        $error = "กรุณาเลือกวิธีการเช็คชื่อ";
                        break;
                }
            }
        } else {
            $error = "กรุณาเลือกวิธีการเช็คชื่อ";
        }
    }
    
    // ตรวจสอบสถานะการตั้งค่า
    $enable_gps = true;
    $enable_qr = true;
    $enable_pin = true; 
    $enable_photo = true;
    
    // เพิ่ม CSS และ JS สำหรับหน้านี้
    $extra_css = ['assets/css/student-checkin.css'];
    $extra_js = ['assets/js/student-checkin.js'];
    
        
    // โหลดหน้าเนื้อหา
    $content_path = 'pages/student_checkin_content.php';
    include_once 'templates/main_template.php';
    
} catch (Exception $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    error_log("Error in check-in.php: " . $e->getMessage());
    header('Location: error.php');
    exit;
}

/**
 * คำนวณระยะห่างระหว่างพิกัด GPS (หน่วยเป็นเมตร)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    }
    
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

/**
 * บันทึกข้อมูลการเช็คชื่อ
 */
function saveAttendance($db, $student_id, $academic_year_id, $check_method, $lat = null, $lng = null, $pin_code = null, $photo_url = null) {
    $today = date('Y-m-d');
    $now = date('H:i:s');
    
    // ตรวจสอบว่ามีข้อมูลการเช็คชื่อวันนี้แล้วหรือไม่
    $stmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = ?");
    $stmt->execute([$student_id, $today]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // ถ้ามีข้อมูลแล้ว ให้อัปเดต
        $stmt = $db->prepare("
            UPDATE attendance 
            SET check_method = ?, 
                location_lat = ?, 
                location_lng = ?, 
                pin_code = ?, 
                photo_url = ?, 
                check_time = ?, 
                is_present = 1
            WHERE attendance_id = ?
        ");
        
        $stmt->execute([
            $check_method,
            $lat,
            $lng,
            $pin_code,
            $photo_url,
            $now,
            $existing['attendance_id']
        ]);
    } else {
        // ถ้ายังไม่มีข้อมูล ให้เพิ่มใหม่
        $stmt = $db->prepare("
            INSERT INTO attendance 
            (student_id, academic_year_id, date, is_present, check_method, 
             location_lat, location_lng, photo_url, pin_code, check_time, created_at) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $student_id,
            $academic_year_id,
            $today,
            $check_method,
            $lat,
            $lng,
            $photo_url,
            $pin_code,
            $now
        ]);
        
        // อัปเดตสถิติในตาราง student_academic_records
        $stmt = $db->prepare("
            UPDATE student_academic_records 
            SET total_attendance_days = total_attendance_days + 1,
                updated_at = NOW()
            WHERE student_id = ? AND academic_year_id = ?
        ");
        
        $stmt->execute([$student_id, $academic_year_id]);
    }
}
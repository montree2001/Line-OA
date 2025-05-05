<?php
/**
 * check_attendance.php - หน้าเช็คชื่อนักเรียน (Admin/Teacher)
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';
/* 
// ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
} */

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'เช็คชื่อนักเรียน';
$page_header = 'ระบบเช็คชื่อนักเรียน';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = 123;
$user_role = 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $admin_info = [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => 'ผู้ดูแลระบบ',
            'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
        ];
    } else {
        // ผู้ใช้เป็นครู - ดึงข้อมูลครูเพิ่มเติม
        $stmt = $conn->prepare("
            SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, 
                   t.position, d.department_name
            FROM users u
            JOIN teachers t ON u.user_id = t.user_id
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $admin_info = [
            'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
            'role' => $teacher['position'] . ' ' . $teacher['department_name'],
            'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
            'teacher_id' => $teacher['teacher_id']
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    error_log("Database error: " . $e->getMessage());
    $admin_info = [
        'name' => 'ไม่พบข้อมูล',
        'role' => 'ไม่พบข้อมูล',
        'initials' => 'x'
    ];
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM risk_students 
        WHERE academic_year_id = ? AND risk_level IN ('high', 'critical')
    ");
    $stmt->execute([$current_academic_year_id]);
    $risk_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $at_risk_count = $risk_data['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $at_risk_count = 0;
}

// ดึงรายชื่อแผนกวิชา และระดับชั้น สำหรับฟิลเตอร์
try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงระดับชั้นที่มีในระบบ
    $stmt = $conn->prepare("
        SELECT DISTINCT level 
        FROM classes 
        WHERE academic_year_id = ? 
        ORDER BY CASE 
            WHEN level = 'ปวช.1' THEN 1
            WHEN level = 'ปวช.2' THEN 2
            WHEN level = 'ปวช.3' THEN 3
            WHEN level = 'ปวส.1' THEN 4
            WHEN level = 'ปวส.2' THEN 5
            ELSE 6
        END
    ");
    $stmt->execute([$current_academic_year_id]);
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $departments = [];
    $levels = [];
}

// -------- จัดการสร้าง PIN --------
if (isset($_POST['generate_pin']) || isset($_POST['ajax_generate_pin'])) {
    try {
        // ดึงการตั้งค่ารหัส PIN
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'pin_length'");
        $stmt->execute();
        $pin_length = (int)($stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 4);
        
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'");
        $stmt->execute();
        $pin_expiration = (int)($stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 10); // หน่วยเป็นนาที
        
        // สร้างรหัส PIN ตามความยาวที่กำหนด
        $min = pow(10, $pin_length - 1);
        $max = pow(10, $pin_length) - 1;
        $pin = mt_rand($min, $max);
        
        // กำหนดเวลาหมดอายุ
        $valid_from = date('Y-m-d H:i:s');
        $valid_until = date('Y-m-d H:i:s', strtotime("+{$pin_expiration} minutes"));
        
        // เตรียมค่า class_id (ถ้ามี)
        $class_id = null;
        if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
            $class_id = $_POST['class_id'];
        }
        
        // บันทึกรหัส PIN ลงฐานข้อมูล
        $stmt = $conn->prepare("
            INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([$pin, $user_id, $current_academic_year_id, $valid_from, $valid_until, $class_id]);
        
        $pin_id = $conn->lastInsertId();
        
        // ส่งค่ากลับในรูปแบบ JSON สำหรับการเรียกผ่าน AJAX
        if (isset($_POST['ajax_generate_pin'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'pin' => $pin,
                'pin_id' => $pin_id,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'expires_at' => strtotime($valid_until),
                'expiration_minutes' => $pin_expiration
            ]);
            exit;
        }
        
        // บันทึกข้อมูล PIN ลง session สำหรับการแสดงผล
        $_SESSION['current_pin'] = $pin;
        $_SESSION['pin_created_at'] = time();
        $_SESSION['pin_expires_at'] = time() + ($pin_expiration * 60);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        
        if (isset($_POST['ajax_generate_pin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการสร้างรหัส PIN']);
            exit;
        }
    }
}

// ดึงข้อมูล PIN ล่าสุดที่ยังใช้งานได้
try {
    $stmt = $conn->prepare("
        SELECT pin_id, pin_code, valid_from, valid_until, class_id
        FROM pins
        WHERE creator_user_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY valid_from DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $active_pin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($active_pin) {
        $current_pin = $active_pin['pin_code'];
        $pin_expires_at = strtotime($active_pin['valid_until']);
        $pin_remaining_time = max(0, $pin_expires_at - time());
        $pin_remaining_min = floor($pin_remaining_time / 60);
        $pin_remaining_sec = $pin_remaining_time % 60;
        $pin_active = true;
        $pin_class_id = $active_pin['class_id'];
        
        // ดึงชื่อห้องเรียน (ถ้ามี)
        if ($pin_class_id) {
            $stmt = $conn->prepare("
                SELECT CONCAT(level, '/', group_number, ' ', d.department_name) as class_name
                FROM classes c
                JOIN departments d ON c.department_id = d.department_id
                WHERE class_id = ?
            ");
            $stmt->execute([$pin_class_id]);
            $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $pin_class_name = $class_data['class_name'] ?? 'ไม่ระบุห้องเรียน';
        } else {
            $pin_class_name = 'ทุกห้องเรียน';
        }
        
        // ดึงนักเรียนที่ใช้รหัส PIN นี้เช็คชื่อ
        $stmt = $conn->prepare("
            SELECT a.attendance_id, a.student_id, a.check_time, a.attendance_status, a.remarks,
                   s.student_code, s.title, u.first_name, u.last_name,
                   c.level, c.group_number, d.department_name
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE a.pin_code = ? AND a.date = CURRENT_DATE()
            ORDER BY a.check_time DESC
        ");
        $stmt->execute([$current_pin]);
        $pin_checked_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $current_pin = '----';
        $pin_remaining_min = 0;
        $pin_remaining_sec = 0;
        $pin_active = false;
        $pin_class_name = 'ไม่ระบุห้องเรียน';
        $pin_checked_students = [];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $current_pin = '----';
    $pin_remaining_min = 0;
    $pin_remaining_sec = 0;
    $pin_active = false;
    $pin_class_name = 'ไม่ระบุห้องเรียน';
    $pin_checked_students = [];
}

// -------- จัดการกับการบันทึกการเช็คชื่อ (POST) --------
$save_success = false;
$save_error = false;
$error_message = '';

if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
        
        foreach ($_POST['attendance'] as $student_id => $data) {
            $status = $data['status'] ?? 'absent';
            $remarks = $data['remarks'] ?? '';
            
            // ตรวจสอบว่ามีข้อมูลการเช็คชื่อของนักเรียนในวันนี้แล้วหรือไม่
            $stmt = $conn->prepare("
                SELECT attendance_id FROM attendance 
                WHERE student_id = ? AND date = ?
            ");
            $stmt->execute([$student_id, $attendance_date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // อัปเดตข้อมูลเดิม
                $stmt = $conn->prepare("
                    UPDATE attendance 
                    SET attendance_status = ?, checker_user_id = ?, remarks = ?, check_method = 'Manual'
                    WHERE attendance_id = ?
                ");
                $stmt->execute([$status, $user_id, $remarks, $existing['attendance_id']]);
            } else {
                // เพิ่มข้อมูลใหม่
                $stmt = $conn->prepare("
                    INSERT INTO attendance 
                    (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks)
                    VALUES (?, ?, ?, ?, 'Manual', ?, NOW(), ?)
                ");
                $stmt->execute([$student_id, $current_academic_year_id, $attendance_date, $status, $user_id, $remarks]);
            }
            
            // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
            $stmt = $conn->prepare("
                SELECT record_id FROM student_academic_records 
                WHERE student_id = ? AND academic_year_id = ?
            ");
            $stmt->execute([$student_id, $current_academic_year_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                // อัปเดตจำนวนวันที่เข้าแถวและขาดแถว
                $stmt = $conn->prepare("
                    UPDATE student_academic_records 
                    SET 
                        total_attendance_days = (
                            SELECT COUNT(*) FROM attendance 
                            WHERE student_id = ? AND academic_year_id = ? AND attendance_status IN ('present', 'late')
                        ),
                        total_absence_days = (
                            SELECT COUNT(*) FROM attendance 
                            WHERE student_id = ? AND academic_year_id = ? AND attendance_status = 'absent'
                        ),
                        updated_at = NOW()
                    WHERE record_id = ?
                ");
                $stmt->execute([$student_id, $current_academic_year_id, $student_id, $current_academic_year_id, $record['record_id']]);
            }
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'update_student_status';
        $action_details = json_encode([
            'type' => 'attendance',
            'date' => $attendance_date,
            'student_count' => count($_POST['attendance']),
            'method' => 'manual'
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $action_type, $action_details]);
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAttendanceReport()'
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/check_attendance.css'
];

$extra_js = [
    'assets/js/check_attendance.js',
    'assets/js/qrcode.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'current_pin' => $current_pin,
    'pin_remaining_min' => $pin_remaining_min,
    'pin_remaining_sec' => $pin_remaining_sec,
    'pin_active' => $pin_active,
    'pin_class_name' => $pin_class_name,
    'pin_checked_students' => $pin_checked_students,
    'departments' => $departments,
    'levels' => $levels,
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'save_success' => $save_success,
    'save_error' => $save_error,
    'error_message' => $error_message,
    'user_role' => $user_role
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/check_attendance_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
<?php
/**
 * check-in.php - หน้าเช็คชื่อเข้าแถวสำหรับนักเรียน
 * สนับสนุนการเช็คชื่อด้วย GPS, QR Code และ PIN
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ตรวจสอบว่าได้เช็คชื่อแล้วหรือยัง
$today = date('Y-m-d');
$already_checked_in = false;
$check_in_method = '';

try {
// ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, d.department_name
               FROM students s
               JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
    header('Location: register.php');
    exit;
}

    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    // ตรวจสอบการเช็คชื่อวันนี้
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student['student_id'], $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($today_attendance) {
                    $already_checked_in = true;
        $check_in_method = $today_attendance['check_method'];
        $check_in_time = $today_attendance['check_time'];
    }
    
    // ดึงค่าพิกัด GPS จากการตั้งค่า
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude'
    ");
    $stmt->execute();
    $school_lat = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude'
    ");
    $stmt->execute();
    $school_lng = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius'
    ");
    $stmt->execute();
    $gps_radius = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '100';
    
    // ดึงเวลาเช็คชื่อ
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time'
    ");
    $stmt->execute();
    $start_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '07:30';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '08:30';
    
    // ตรวจสอบสถานะ QR Code
    $stmt = $conn->prepare("
        SELECT * FROM qr_codes 
        WHERE student_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student['student_id']]);
    $current_qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_active_qr = false;
    $qr_expire_time = '';
    
    if ($current_qr) {
        $has_active_qr = true;
        $qr_expire_time = date('H:i', strtotime($current_qr['valid_until']));
    }
    
    // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเช็คชื่อหรือไม่
    $current_time = date('H:i');
    $check_in_available = true;
    
    if ($current_time < $start_time || $current_time > $end_time) {
        $check_in_available = false;
    }
    
    // สร้างข้อมูลสำหรับแสดงผล
    $student_info = [
        'id' => $student['student_id'],
        'code' => $student['student_code'],
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number'],
    ];
    
    $gps_info = [
        'lat' => $school_lat,
        'lng' => $school_lng,
        'radius' => $gps_radius,
    ];
    
    $attendance_info = [
        'already_checked_in' => $already_checked_in,
        'check_in_method' => mapCheckMethod($check_in_method),
        'check_in_time' => isset($check_in_time) ? date('H:i', strtotime($check_in_time)) : '',
        'check_in_available' => $check_in_available,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ];
    
    $qr_info = [
        'has_active_qr' => $has_active_qr,
        'qr_data' => $current_qr['qr_code_data'] ?? '',
        'expire_time' => $qr_expire_time,
    ];
    
    // สร้างตัวอักษรแรกของชื่อสำหรับใช้แสดงในกรณีไม่มีรูปโปรไฟล์
    $first_char = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
    // กำหนดชื่อหน้า
    $page_title = "STD-Prasat - เช็คชื่อเข้าแถว";
    
    // กำหนด CSS เพิ่มเติม
    $extra_css = [
        'assets/css/check-in.css'
    ];
    
    // กำหนด JS เพิ่มเติม
     $extra_js = [
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js',
        'assets/js/check-in.js'
    ];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/check_in_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}

// ฟังก์ชันแปลงรูปแบบการเช็คชื่อ
function mapCheckMethod($method) {
    switch ($method) {
        case 'GPS':
            return 'เช็คชื่อผ่าน GPS';
        case 'QR_Code':
            return 'เช็คชื่อด้วย QR Code';
        case 'PIN':
            return 'เช็คชื่อด้วยรหัส PIN';
        case 'Manual':
            return 'ครูเช็คชื่อให้';
                default:
            return '';
    }
}
?>
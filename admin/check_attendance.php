<?php
/**
 * check_attendance.php - หน้าเช็คชื่อนักเรียน
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'teacher')) {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'เช็คชื่อนักเรียน';
$page_header = 'ระบบเช็คชื่อนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAttendanceReport()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/check_attendance.css'
];

$extra_js = [
    'assets/js/check_attendance.js',
    'assets/js/qrcode.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/check_attendance_content.php';

// ตรวจสอบการส่ง PIN (ถ้ามี)
if (isset($_POST['generate_pin'])) {
    // สร้าง PIN 4 หลักแบบสุ่ม
    $pin = mt_rand(1000, 9999);
    
    // บันทึกลง session (ในทางปฏิบัติจริง ควรบันทึกลงฐานข้อมูล)
    $_SESSION['current_pin'] = $pin;
    $_SESSION['pin_created_at'] = time();
    
    // ส่งคืนค่า PIN ในรูปแบบ JSON (สำหรับการเรียกใช้ผ่าน AJAX)
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['pin' => $pin, 'expires_at' => time() + 600]); // หมดอายุใน 10 นาที
        exit;
    }
}

// ดึง PIN ปัจจุบัน (ถ้ามี) และตรวจสอบอายุ
$current_pin = isset($_SESSION['current_pin']) ? $_SESSION['current_pin'] : '';
$pin_created_at = isset($_SESSION['pin_created_at']) ? $_SESSION['pin_created_at'] : 0;
$pin_expires_at = $pin_created_at + 600; // 10 นาที
$pin_remaining_time = max(0, $pin_expires_at - time());
$pin_remaining_min = floor($pin_remaining_time / 60);
$pin_remaining_sec = $pin_remaining_time % 60;

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'current_pin' => $current_pin,
    'pin_remaining_min' => $pin_remaining_min,
    'pin_remaining_sec' => $pin_remaining_sec,
    'pin_active' => (time() < $pin_expires_at)
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
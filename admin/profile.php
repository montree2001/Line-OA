<?php
/**
 * profile.php - หน้าโปรไฟล์ของเจ้าหน้าที่
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'profile';
$page_title = 'โปรไฟล์เจ้าหน้าที่';
$page_header = 'ข้อมูลส่วนตัว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'id' => 1,
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ',
    'email' => 'jaruwan.b@prasat.ac.th',
    'phone' => '081-234-5678',
    'department' => 'ฝ่ายกิจการนักเรียน',
    'last_login' => '16 มีนาคม 2568 08:15',
    'avatar' => '/uploads/admin/jaruwan.jpg'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'แก้ไขข้อมูล',
        'icon' => 'edit',
        'onclick' => 'editProfile()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/profile.css'
];

$extra_js = [
    'assets/js/profile.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/admin_profile_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
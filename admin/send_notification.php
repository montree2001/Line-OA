<?php
/**
 * send_notification.php - หน้าส่งข้อความแจ้งเตือนผู้ปกครอง
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
$current_page = 'send_notification';
$page_title = 'ส่งข้อความแจ้งเตือน';
$page_header = 'ส่งข้อความรายงานการเข้าแถว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดูประวัติการส่งข้อความ',
        'icon' => 'history',
        'onclick' => 'showHistory()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/notification.css'
];

$extra_js = [
    'assets/js/notification.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/notification_content.php';

// ดึงข้อมูลนักเรียน (จริงๆ ควรดึงจากฐานข้อมูล)
// นี่เป็นตัวอย่างข้อมูล
$students = [
    [
        'id' => 1,
        'name' => 'นายธนกฤต สุขใจ',
        'class' => 'ม.6/2',
        'class_number' => 12,
        'attendance_rate' => 65,
        'attendance_days' => '26/40',
        'status' => 'เสี่ยงตกกิจกรรม',
        'parent' => 'นางวันดี สุขใจ (แม่)'
    ],
    [
        'id' => 2,
        'name' => 'นางสาวสมหญิง มีสุข',
        'class' => 'ม.5/3',
        'class_number' => 8,
        'attendance_rate' => 75,
        'attendance_days' => '30/40',
        'status' => 'ต้องระวัง',
        'parent' => 'นายสมชาย มีสุข (พ่อ)'
    ],
    [
        'id' => 3,
        'name' => 'นายพิชัย รักเรียน',
        'class' => 'ม.4/1',
        'class_number' => 15,
        'attendance_rate' => 95,
        'attendance_days' => '38/40',
        'status' => 'ปกติ',
        'parent' => 'นางรักดี รักเรียน (แม่)'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต (ในทางปฏิบัติจริง ควรมีการจัดการข้อมูลที่ซับซ้อนกว่านี้)
$data = [
    'students' => $students
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
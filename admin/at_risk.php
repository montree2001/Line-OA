<?php
/**
 * at_risk.php - หน้าแสดงนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว
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
$current_page = 'at_risk';
$page_title = 'นักเรียนเสี่ยงตกกิจกรรม';
$page_header = 'นักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ส่งรายงานกลุ่ม',
        'icon' => 'send',
        'onclick' => 'showBulkNotificationModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAtRiskReport()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/at_risk.css'
];

$extra_js = [
    'assets/js/at_risk.js',
    'assets/js/charts.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/at_risk_content.php';

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม (จริงๆ ควรดึงจากฐานข้อมูล)
// นี่เป็นตัวอย่างข้อมูล
$at_risk_students = [
    [
        'id' => 1,
        'name' => 'นายธนกฤต สุขใจ',
        'class' => 'ม.6/2',
        'class_number' => 12,
        'attendance_rate' => 68.5,
        'days_missed' => 15,
        'advisor' => 'อ.ประสิทธิ์ ดีเลิศ',
        'notification_status' => 'ยังไม่แจ้ง'
    ],
    [
        'id' => 2,
        'name' => 'นางสาวสมหญิง มีสุข',
        'class' => 'ม.5/3',
        'class_number' => 8,
        'attendance_rate' => 70.2,
        'days_missed' => 14,
        'advisor' => 'อ.วันดี สดใส',
        'notification_status' => 'แจ้งแล้ว 1 ครั้ง'
    ],
    [
        'id' => 3,
        'name' => 'นายพิชัย รักเรียน',
        'class' => 'ม.4/1',
        'class_number' => 15,
        'attendance_rate' => 75.3,
        'days_missed' => 12,
        'advisor' => 'อ.ใจดี มากเมตตา',
        'notification_status' => 'แจ้งแล้ว 2 ครั้ง'
    ],
    [
        'id' => 4,
        'name' => 'นางสาววรรณา ชาติไทย',
        'class' => 'ม.5/2',
        'class_number' => 10,
        'attendance_rate' => 73.5,
        'days_missed' => 13,
        'advisor' => 'อ.วิชัย สุขสวัสดิ์',
        'notification_status' => 'แจ้งแล้ว 1 ครั้ง'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต (ในทางปฏิบัติจริง ควรมีการจัดการข้อมูลที่ซับซ้อนกว่านี้)
$data = [
    'at_risk_students' => $at_risk_students
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
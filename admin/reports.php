<?php
/**
 * reports.php - หน้ารายงานและสถิติการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'reports';
$page_title = 'รายงานและสถิติการเข้าแถว';
$page_header = 'ระบบรายงานและสถิติการเข้าแถว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (ในระบบจริงควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'จารุวรรณ บุญมี',
    'role' => $_SESSION['user_role'] == 'admin' ? 'เจ้าหน้าที่กิจการนักเรียน' : 'ครูที่ปรึกษา',
    'initials' => mb_substr($_SESSION['user_name'] ?? 'จ', 0, 1, 'UTF-8')
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadReportData()'
    ],
    [
        'text' => 'พิมพ์รายงาน',
        'icon' => 'print',
        'onclick' => 'window.print()'
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/reports.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
    'assets/js/reports.js'
];

// ซ่อนช่องค้นหาในเฮดเดอร์ (เพราะหน้ารายงานมีแผงค้นหาแบบละเอียดแล้ว)
$hide_search = true;

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/reports_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
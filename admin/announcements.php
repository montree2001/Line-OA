<?php
/**
 * announcements.php - หน้าจัดการประกาศถึงนักเรียน
 */

// เริ่ม session
session_start();

/* // เพิ่มการตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
}
 */
// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'announcements';
$page_title = 'จัดการประกาศ';
$page_header = 'จัดการประกาศถึงนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'สร้างประกาศใหม่',
        'icon' => 'plus',
        'id' => 'create-announcement-btn',
        'class' => 'btn btn-primary'
    ],
    [
        'text' => 'รีเฟรช',
        'icon' => 'sync',
        'id' => 'refresh-announcements-btn',
        'class' => 'btn btn-secondary'
    ]
];

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

// ดึงข้อมูลประกาศทั้งหมด
try {
    $stmt = $conn->prepare("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูลประกาศ: " . $e->getMessage();
    error_log($error_message);
    $announcements = [];
}

// ฟังก์ชันแปลงประเภทประกาศเป็นชื่อไทย
function getAnnouncementTypeName($type) {
    $types = [
        'general' => 'ทั่วไป',
        'urgent' => 'สำคัญ',
        'event' => 'กิจกรรม',
        'info' => 'ข้อมูล',
        'success' => 'ความสำเร็จ',
        'warning' => 'คำเตือน'
    ];
    
    return isset($types[$type]) ? $types[$type] : 'ทั่วไป';
}

// ดึงข้อมูลแผนกวิชาสำหรับฟิลเตอร์
try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูลแผนกวิชา: " . $e->getMessage();
    error_log($error_message);
    $departments = [];
}

// ดึงข้อมูลระดับชั้นสำหรับฟิลเตอร์
$levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม (ตัวอย่าง - ควรดึงจากฐานข้อมูล)
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/announcements.css',
    'assets/css/modal.css',
    'assets/css/modal-details.css',
    'assets/css/button-effects.css',
    'assets/css/theme-switch.css',
    'assets/plugins/summernote/summernote-bs4.min.css',
    'assets/plugins/sweetalert2/sweetalert2.min.css'
];

$extra_js = [
    'assets/plugins/jquery/jquery.min.js',
    'assets/plugins/bootstrap/js/bootstrap.bundle.min.js',
    'assets/plugins/summernote/summernote-bs4.min.js',
    'assets/plugins/summernote/lang/summernote-th-TH.min.js',
    'assets/plugins/sweetalert2/sweetalert2.min.js',
    'assets/js/theme-switch.js',
    'assets/js/announcements.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/announcements_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?> 
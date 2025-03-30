<?php
/**
 * settings.php - หน้าตั้งค่าระบบน้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
} */

// เชื่อมต่อกับฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'settings';
$page_title = 'ตั้งค่าระบบ';
$page_header = 'การตั้งค่าระบบน้องชูใจ AI ดูแลผู้เรียน';

// ข้อมูลเกี่ยวกับแอดมิน (ดึงจากฐานข้อมูล)
try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? AND role = 'admin'");
    $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $admin_info = [
        'name' => ($admin) ? $admin['first_name'] . ' ' . $admin['last_name'] : 'ผู้ดูแลระบบ',
        'role' => 'เจ้าหน้าที่กิจการนักเรียน',
        'initials' => ($admin) ? mb_substr($admin['first_name'], 0, 1, 'UTF-8') : 'A'
    ];
} catch (PDOException $e) {
    $admin_info = [
        'name' => 'ผู้ดูแลระบบ',
        'role' => 'เจ้าหน้าที่กิจการนักเรียน',
        'initials' => 'A'
    ];
}

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'บันทึกการตั้งค่า',
        'icon' => 'save',
        'class' => 'btn-primary save-button',
        'onclick' => 'saveSettings()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM risk_students WHERE risk_level IN ('high', 'critical')");
    $stmt->execute();
    $at_risk_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $at_risk_count = 0;
}

// ดึงค่าตั้งค่าจากฐานข้อมูล
function getSystemSettings($conn) {
    $settings = [];
    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // กรณีเกิดข้อผิดพลาด ให้ใช้ค่าเริ่มต้น
    }
    return $settings;
}

$settings = getSystemSettings($conn);

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/settings.css'
];

$extra_js = [
    'assets/js/settings.js',
    'https://maps.googleapis.com/maps/api/js?key=AIzaSyBSv3_qs4kBD_6GBL-U5QARYux0JXD7ar4&callback=initMapAPI&libraries=geometry&v=weekly'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/settings_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
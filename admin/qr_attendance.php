<?php
/**
 * qr_attendance.php - หน้าเช็คชื่อผ่าน QR Code Scanner
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'qr_attendance';
$page_title = 'เช็คชื่อผ่าน QR Code';
$page_header = 'ระบบเช็คชื่อผ่าน QR Code Scanner';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $admin_info = [
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => 'ผู้ดูแลระบบ',
                'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
            ];
        } else {
            $admin_info = [
                'name' => 'ผู้ดูแลระบบ',
                'role' => 'ผู้ดูแลระบบ',
                'initials' => 'A'
            ];
        }
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
        
        if ($teacher) {
            $admin_info = [
                'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
                'role' => $teacher['position'] . ' ' . $teacher['department_name'],
                'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
                'teacher_id' => $teacher['teacher_id']
            ];
        } else {
            $admin_info = [
                'name' => 'ครูผู้สอน',
                'role' => 'ครูผู้สอน',
                'initials' => 'T'
            ];
        }
    }
} catch (PDOException $e) {
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
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/qr_scanner.css'
];

$extra_js = [
    'assets/js/qr_scanner.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'user_role' => $user_role,
    'user_id' => $user_id
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/qr_attendance_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
<?php
/**
 * bulk_attendance.php - หน้าเช็คชื่อนักเรียนแบบกลุ่มสำหรับผู้ดูแลระบบและครู
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * ปรับปรุงใหม่: แยกแท็บระหว่างเช็คชื่อแล้วและยังไม่เช็ค, แสดงไอคอนวิธีการเช็ค, 
 * ค้นหาโดยชื่อ, และกรองตามแผนก/ชั้น/กลุ่ม
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


// ตรวจสอบการบันทึกการเช็คชื่อ
if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    // เก็บค่า class_id ไว้ใน session
    if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
        $_SESSION['last_selected_class_id'] = $_POST['class_id'];
    }
}

// ดึงค่า class_id ที่เลือกล่าสุดจาก session (ถ้ามี)
$selected_class_id = $_SESSION['last_selected_class_id'] ?? '';

// ถ้าไม่มีค่าใน session ให้ใช้ค่าจาก POST หรือ GET
if (empty($selected_class_id)) {
    if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
        $selected_class_id = $_POST['class_id'];
    } elseif (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
        $selected_class_id = $_GET['class_id'];
    }
}

// ถ้ามีค่า class_id และไม่มีการบันทึกไว้ใน session ให้บันทึก
if (!empty($selected_class_id) && empty($_SESSION['last_selected_class_id'])) {
    $_SESSION['last_selected_class_id'] = $selected_class_id;
}

// ดึงข้อมูลชั้นเรียนที่เลือก (ถ้ามี)
$selected_class_info = [];
if (!empty($selected_class_id)) {
    try {
        // เชื่อมต่อฐานข้อมูล (ต้องอยู่หลังจากมีการเชื่อมต่อฐานข้อมูลแล้ว)
        $stmt = $conn->prepare("
            SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$selected_class_id]);
        $selected_class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching class info: " . $e->getMessage());
    }
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'bulk_attendance';
$page_title = 'เช็คชื่อนักเรียนแบบกลุ่ม';
$page_header = 'ระบบเช็คชื่อนักเรียนแบบกลุ่ม';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';


// ตรวจสอบค่า SESSION สำหรับตัวกรอง (ถ้ายังไม่มีให้สร้างแบบว่าง)
if (!isset($_SESSION['bulk_attendance_filters'])) {
    $_SESSION['bulk_attendance_filters'] = [
        'class_id' => '',
        'date' => date('Y-m-d')
    ];
}

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

// -------- จัดการกับการบันทึกการเช็คชื่อ (POST) --------
$save_success = false;
$save_error = false;
$error_message = '';
$response_message = '';

if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
        $class_id = $_POST['class_id'] ?? '';
        
        // เก็บ class_id ไว้ใน session สำหรับการเลือกครั้งต่อไป
        $_SESSION['last_selected_class_id'] = $class_id;

        
        // บันทึกค่าตัวกรองลงใน SESSION
        $_SESSION['bulk_attendance_filters']['class_id'] = $class_id;
        $_SESSION['bulk_attendance_filters']['date'] = $attendance_date;
        
        // โค้ดที่มีอยู่เดิมสำหรับการบันทึกข้อมูล...
        
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

$selected_class_id = $_SESSION['last_selected_class_id'] ?? '';

// ดึงตัวกรองจาก SESSION
$selected_class_id = $_SESSION['bulk_attendance_filters']['class_id'];
$selected_date = $_SESSION['bulk_attendance_filters']['date'];
$data['selected_class_id'] = $selected_class_id;
$data['selected_class_info'] = $selected_class_info;

// ดึงข้อมูลห้องเรียนที่เลือก (ถ้ามี)
$selected_class_info = null;
if (!empty($selected_class_id)) {
    try {
        $stmt = $conn->prepare("
            SELECT c.class_id, c.level, c.group_number, d.department_name, d.department_id 
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$selected_class_id]);
        $selected_class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error when fetching class info: " . $e->getMessage());
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
    'assets/js/bulk_attendance.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'departments' => $departments,
    'levels' => $levels,
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'save_success' => $save_success,
    'save_error' => $save_error,
    'error_message' => $error_message,
    'response_message' => $response_message,
    'user_role' => $user_role
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/bulk_attendance_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
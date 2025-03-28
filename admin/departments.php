<?php
/**
 * departments.php - หน้าจัดการข้อมูลแผนกวิชา
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
/* if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าไฟล์ฟังก์ชัน
require_once 'includes/department_functions.php';
require_once 'includes/api_handlers.php';

// ตรวจสอบการส่ง Form
if (isset($_POST['form_action'])) {
    $action = $_POST['form_action'];
    
    switch ($action) {
        case 'add_department':
            $department_name = $_POST['department_name'] ?? '';
            $department_code = $_POST['department_code'] ?? '';
            
            if (empty($department_name)) {
                $_SESSION['error_message'] = 'กรุณาระบุชื่อแผนกวิชา';
                header('Location: departments.php');
                exit;
            }
            
            $result = addDepartment([
                'department_name' => $department_name,
                'department_code' => $department_code
            ]);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            header('Location: departments.php');
            exit;
            break;
            
        case 'edit_department':
            $department_id = $_POST['department_id'] ?? '';
            $department_name = $_POST['department_name'] ?? '';
            
            if (empty($department_id) || empty($department_name)) {
                $_SESSION['error_message'] = 'กรุณาระบุข้อมูลให้ครบถ้วน';
                header('Location: departments.php');
                exit;
            }
            
            $result = updateDepartment([
                'department_id' => $department_id,
                'department_name' => $department_name
            ]);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            header('Location: departments.php');
            exit;
            break;
            
        case 'delete_department':
            $department_id = $_POST['department_id'] ?? '';
            
            if (empty($department_id)) {
                $_SESSION['error_message'] = 'กรุณาระบุรหัสแผนกวิชา';
                header('Location: departments.php');
                exit;
            }
            
            $result = deleteDepartment($department_id);
            
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            
            header('Location: departments.php');
            exit;
            break;
    }
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'departments';
$page_title = 'จัดการแผนกวิชา';
$page_header = 'ข้อมูลและการจัดการแผนกวิชา';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มแผนกวิชาใหม่',
        'icon' => 'add',
        'onclick' => 'showDepartmentModal()'
    ],
    [
        'text' => 'จัดการชั้นเรียน',
        'icon' => 'class',
        'onclick' => "window.location.href='classes.php'"
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js',
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

// แสดงข้อความแจ้งเตือน
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

// เคลียร์ข้อความแจ้งเตือนใน session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// ดึงข้อมูลแผนกวิชาจากฐานข้อมูล
$departments = getDepartmentsFromDB();

// ถ้าดึงข้อมูลไม่สำเร็จ ให้ใช้ข้อมูลตัวอย่าง
if ($departments === false) {
    $departments = getSampleDepartments();
}

$data = [
    'departments' => $departments,
    'success_message' => $success_message,
    'error_message' => $error_message
]; 

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/department_content.php';  // เปลี่ยนเป็นใช้ department_content.php แทน

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
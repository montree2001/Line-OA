<?php
/**
 * students.php - หน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     header('Location: login.php');
//     exit;
// }

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// เพิ่ม include ไฟล์ model
require_once '../models/students_model.php';
//require_once '../modals/import_modal.php';
// สร้างตัวแปรสำหรับเก็บเงื่อนไขการกรอง
$filters = [
    'name' => $_GET['name'] ?? '',
    'student_code' => $_GET['student_code'] ?? '',
    'level' => $_GET['level'] ?? '',
    'group_number' => $_GET['group_number'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'attendance_status' => $_GET['attendance_status'] ?? '',
    'line_status' => $_GET['line_status'] ?? ''
];

// ดึงข้อมูลนักเรียนผ่าน model function
$students = getAllStudents($filters);

// ดึงข้อมูลแผนกวิชาทั้งหมด
$departments = [];
try {
    $conn = getDB();
    $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

// ดึงข้อมูลชั้นเรียนทั้งหมด
$classes = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT c.class_id, c.level, c.group_number, d.department_name, c.department_id
        FROM classes c 
        JOIN departments d ON c.department_id = d.department_id
        JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE ay.is_active = 1
        ORDER BY c.level, c.group_number
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดกลุ่มชั้นเรียนตามระดับชั้น
    $classGroups = [];
    foreach ($classes as $class) {
        $level = $class['level'];
        if (!isset($classGroups[$level])) {
            $classGroups[$level] = [];
        }
        $classGroups[$level][] = $class;
    }
} catch (PDOException $e) {
    error_log("Error fetching classes: " . $e->getMessage());
    $classGroups = [];
}

// ดึงข้อมูลครูทั้งหมด
$teachers = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT t.teacher_id, t.title, t.first_name, t.last_name, d.department_name
        FROM teachers t
        LEFT JOIN departments d ON t.department_id = d.department_id
        ORDER BY t.first_name, t.last_name
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
}

// ดึงสถิติจำนวนนักเรียน
$student_stats = getStudentStatistics();

// ส่วนการเพิ่มนักเรียนใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['title'])) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // เรียกใช้ฟังก์ชัน addStudent จาก model
        $result = addStudent($_POST);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: students.php?success=add");
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// ส่วนการแก้ไขข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (empty($_POST['student_id']) || empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // เรียกใช้ฟังก์ชัน updateStudent จาก model
        $result = updateStudent($_POST);

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: students.php?success=edit");
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// ส่วนการลบข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (empty($_POST['student_id'])) {
        $error_message = "ไม่ระบุรหัสนักเรียนที่ต้องการลบ";
    } else {
        // เรียกใช้ฟังก์ชัน deleteStudent จาก model
        $result = deleteStudent($_POST['student_id']);

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: students.php?success=delete");
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// ส่วนการนำเข้าข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "ไม่มีไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์";
    } else {
        // เรียกใช้ฟังก์ชัน importStudentsFromExcel จาก model
        $result = importStudentsFromExcel($_FILES['import_file'], [
            'skip_header' => isset($_POST['skip_header']),
            'update_existing' => isset($_POST['update_existing']),
            'import_class_id' => $_POST['import_class_id'] ?? null
        ]);

        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: students.php?success=import");
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// ตรวจสอบการแสดงข้อความแจ้งเตือนจาก URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'add':
            $success_message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'edit':
            $success_message = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'delete':
            $success_message = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'import':
            $success_message = "นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
    }
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/students.css'
];

$extra_js = [
    'assets/js/students.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/students_content.php';

// กำหนดหัวข้อและข้อความแสดงบนหน้าเว็บ
$page_title = "จัดการข้อมูลนักเรียน";
$page_header = "จัดการข้อมูลนักเรียน";

// กำหนดปุ่มสำหรับ header
$header_buttons = [
    [
        'text' => 'เพิ่มนักเรียนใหม่',
        'icon' => 'person_add',
        'id' => 'addStudentBtn',
        'onclick' => 'showAddStudentModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'upload_file',
        'id' => 'importBtn', 
        'onclick' => 'showImportModal()'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'statistics' => $student_stats,
    'departments' => $departments,
    'classes' => $classes,
    'classGroups' => $classGroups ?? [],
    'teachers' => $teachers
];

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
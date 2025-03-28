<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

// ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
/* if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าไฟล์ฟังก์ชัน
require_once 'includes/classes_functions.php';
require_once 'includes/department_functions.php';
require_once 'includes/api_handlers.php';

// ตรวจสอบการส่ง Form หรือการเรียก API
if (isset($_POST['form_action']) || isset($_GET['action'])) {
    // จัดการ Form Action
    if (isset($_POST['form_action'])) {
        $action = $_POST['form_action'];
        
        switch ($action) {
            case 'add_class':
                $academic_year_id = $_POST['academic_year_id'] ?? '';
                $level = $_POST['level'] ?? '';
                $department_id = $_POST['department_id'] ?? '';
                $group_number = $_POST['group_number'] ?? '';
                $classroom = $_POST['classroom'] ?? '';
                
                if (empty($academic_year_id) || empty($level) || empty($department_id) || empty($group_number)) {
                    $_SESSION['error_message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                    header('Location: classes.php');
                    exit;
                }
                
                $result = addClass([
                    'academic_year_id' => $academic_year_id,
                    'level' => $level,
                    'department_id' => $department_id,
                    'group_number' => $group_number,
                    'classroom' => $classroom
                ]);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                header('Location: classes.php');
                exit;
                break;
                
            case 'edit_class':
                $class_id = $_POST['class_id'] ?? '';
                $academic_year_id = $_POST['academic_year_id'] ?? '';
                $level = $_POST['level'] ?? '';
                $department_id = $_POST['department_id'] ?? '';
                $group_number = $_POST['group_number'] ?? '';
                $classroom = $_POST['classroom'] ?? '';
                
                if (empty($class_id) || empty($academic_year_id) || empty($level) || 
                    empty($department_id) || empty($group_number)) {
                    $_SESSION['error_message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                    header('Location: classes.php');
                    exit;
                }
                
                $result = updateClass([
                    'class_id' => $class_id,
                    'academic_year_id' => $academic_year_id,
                    'level' => $level,
                    'department_id' => $department_id,
                    'group_number' => $group_number,
                    'classroom' => $classroom
                ]);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                header('Location: classes.php');
                exit;
                break;
                
            case 'delete_class':
                $class_id = $_POST['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $_SESSION['error_message'] = 'กรุณาระบุรหัสชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                
                $result = deleteClass($class_id);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                header('Location: classes.php');
                exit;
                break;
                
            case 'manage_advisors':
                $class_id = $_POST['class_id'] ?? '';
                $changes = json_decode($_POST['changes'] ?? '[]', true);
                
                if (empty($class_id)) {
                    $_SESSION['error_message'] = 'กรุณาระบุรหัสชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                
                $result = manageAdvisors([
                    'class_id' => $class_id,
                    'changes' => $changes
                ]);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                header('Location: classes.php');
                exit;
                break;
                
            case 'promote_students':
                $from_academic_year_id = $_POST['from_academic_year_id'] ?? '';
                $to_academic_year_id = $_POST['to_academic_year_id'] ?? '';
                $notes = $_POST['notes'] ?? '';
                
                if (empty($from_academic_year_id) || empty($to_academic_year_id)) {
                    $_SESSION['error_message'] = 'กรุณาเลือกปีการศึกษาต้นทางและปลายทาง';
                    header('Location: classes.php');
                    exit;
                }
                
                $result = promoteStudents([
                    'from_academic_year_id' => $from_academic_year_id,
                    'to_academic_year_id' => $to_academic_year_id,
                    'notes' => $notes
                ]);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                header('Location: classes.php');
                exit;
                break;
        }
    }
    
    // จัดการ GET Action
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        switch ($action) {
            case 'view_class':
                $class_id = $_GET['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $_SESSION['error_message'] = 'ไม่ระบุรหัสชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                
                // ดึงข้อมูลชั้นเรียนและส่งไปยังหน้าแสดงรายละเอียด
                $class_details = getDetailedClassInfo($class_id);
                
                if ($class_details['status'] == 'success') {
                    // บันทึกข้อมูลชั้นเรียนลงใน session สำหรับแสดงผล
                    $_SESSION['class_details'] = $class_details;
                    header('Location: class_details.php?id=' . $class_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = $class_details['message'] ?? 'ไม่พบข้อมูลชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                break;
                
            case 'download_report':
                $class_id = $_GET['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $_SESSION['error_message'] = 'ไม่ระบุรหัสชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                
                // ดาวน์โหลดรายงานชั้นเรียน
                downloadClassReport($class_id);
                exit;
                break;
        }
    }
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียน';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'จัดการแผนกวิชา',
        'icon' => 'account_balance',
        'onclick' => "window.location.href='departments.php'"
    ],
    [
        'text' => 'สถิติชั้นเรียน',
        'icon' => 'leaderboard',
        'onclick' => 'showClassStatistics()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
//$at_risk_count = getAtRiskStudentCount();

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

// ดึงข้อมูลจากฐานข้อมูล
$classes = getClassesFromDB();
$departments = getDepartmentsFromDB();
$academicYearData = getAcademicYearsFromDB();
$teachers = getTeachersFromDB();

// ถ้าดึงข้อมูลไม่สำเร็จ ให้ใช้ข้อมูลตัวอย่าง
if ($classes === false) {
    $classes = getSampleClasses();
}

if ($departments === false) {
    $departments = getSampleDepartments();
}

if ($academicYearData === false) {
    $academicYearData = getSampleAcademicYears();
}

$academic_years = $academicYearData['academic_years'];
$has_new_academic_year = $academicYearData['has_new_academic_year'];
$current_academic_year = $academicYearData['current_academic_year'];
$next_academic_year = $academicYearData['next_academic_year'];
$active_year_id = $academicYearData['active_year_id'];

if ($teachers === false) {
    $teachers = getSampleTeachers();
}

// ข้อมูลการเลื่อนชั้นจะดึงเมื่อมีปีการศึกษาถัดไป
if ($has_new_academic_year && $active_year_id !== null) {
    $promotion_counts = getPromotionCounts($active_year_id);
    
    if ($promotion_counts === false) {
        $promotion_counts = getSamplePromotionCounts();
    }
} else {
    $promotion_counts = [];
}

$data = [
    'classes' => $classes,
    'departments' => $departments,
    'academic_years' => $academic_years,
    'has_new_academic_year' => $has_new_academic_year,
    'current_academic_year' => $current_academic_year,
    'next_academic_year' => $next_academic_year,
    'promotion_counts' => $promotion_counts,
    'teachers' => $teachers,
    'success_message' => $success_message,
    'error_message' => $error_message
]; 

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/classes_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
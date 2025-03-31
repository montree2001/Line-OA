<?php
// Tambahkan error reporting di bagian atas
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียนและแผนกวิชา
 * 
 * ระบบ STP-Prasat (Student Tracking Platform)
 * 1. สร้างและจัดการชั้นเรียน
 * 2. กำหนดครูที่ปรึกษาให้แต่ละชั้นเรียน
 * 3. เลื่อนระดับชั้นเมื่อเปลี่ยนปีการศึกษา
 */

// เพิ่มการตรวจสอบ session และการล็อกอิน
session_start();
$adminId = $_SESSION['user_id'] ?? 1; // ใช้ ID 1 เป็นค่าเริ่มต้นถ้าไม่มี session
// ตรวจสอบการล็อกอิน
/* if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // ถ้าไม่ได้ล็อกอิน ให้เปลี่ยนเส้นทางไปยังหน้าล็อกอิน
    header('Location: login.php');
    exit;
} */
/* 
// เพิ่มการตรวจสอบสิทธิ์การเข้าถึง (ถ้ามี)
if ($_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าฟังก์ชันที่จำเป็น
$required_files = [
    'includes/classes_functions.php',
    'includes/department_functions.php',
    'includes/teacher_functions.php',
    'includes/academic_years_functions.php',
    'includes/api_functions.php'
];

// ตรวจสอบและนำเข้าไฟล์
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        error_log("ไม่พบไฟล์: $file");
        die("ไม่พบไฟล์ที่จำเป็น: $file");
    }
    require_once $file;
}

// ดึงข้อมูลที่จำเป็น
try {
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    $conn = getDB();
    if (!$conn) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }

    // ดึงข้อมูลทั้งหมด
    $classes = getClassesFromDB();
    $departments = getDepartmentsFromDB();
    $academicYearData = getAcademicYearsFromDB();
    $teachers = getTeachersFromDB();

    // ตรวจสอบว่าดึงข้อมูลสำเร็จหรือไม่
    if ($classes === false || $departments === false || $academicYearData === false || $teachers === false) {
        throw new Exception("ไม่สามารถดึงข้อมูลจากฐานข้อมูลได้");
    }

    // ใช้ข้อมูลตัวอย่างหากดึงข้อมูลไม่สำเร็จ
    $classes = $classes ?: getSampleClasses();
    $departments = $departments ?: getSampleDepartments();
    $academicYearData = $academicYearData ?: getSampleAcademicYears();
    $teachers = $teachers ?: getSampleTeachers();

    $academic_years = $academicYearData['academic_years'];
    $has_new_academic_year = $academicYearData['has_new_academic_year'];
    $active_year_id = $academicYearData['active_year_id'];

    // ข้อมูลการเลื่อนชั้น
    $promotion_counts = [];
    if ($has_new_academic_year && $active_year_id !== null) {
        $promotion_counts = getPromotionCounts($active_year_id) ?: getSamplePromotionCounts();
    }

    // ข้อมูลแสดงผล
    $page_title = 'จัดการชั้นเรียนและแผนกวิชา';
    $data = [
        'classes' => $classes,
        'departments' => $departments,
        'academic_years' => $academic_years,
        'has_new_academic_year' => $has_new_academic_year,
        'promotion_counts' => $promotion_counts,
        'teachers' => $teachers,
        'at_risk_count' => getAtRiskStudentCount() ?: 0
    ];

} catch (Exception $e) {
    // จัดการข้อผิดพลาดที่เกิดขึ้น
    error_log('เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage());
    
    // ใช้ข้อมูลตัวอย่างเมื่อเกิดข้อผิดพลาด
    $data = [
        'classes' => getSampleClasses(),
        'departments' => getSampleDepartments(),
        'academic_years' => getSampleAcademicYears()['academic_years'],
        'has_new_academic_year' => false,
        'promotion_counts' => getSamplePromotionCounts(),
        'teachers' => getSampleTeachers(),
        'at_risk_count' => 0
    ];
    
    // แสดงข้อความแจ้งเตือน
    echo '<div class="alert alert-warning" role="alert">
            <i class="material-icons">warning</i>
            เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาติดต่อผู้ดูแลระบบ
          </div>';
}

// ตรวจสอบการ submit form หรือเรียก API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_class':
                $result = addClass($_POST);
                break;
            case 'edit_class':
                $result = updateClass($_POST);
                break;
            case 'delete_class':
                $result = deleteClass($_POST['class_id']);
                break;
            case 'manage_advisors':
                $changes = json_decode($_POST['changes'] ?? '[]', true);
                $result = updateClassAdvisors($_POST['class_id'], $changes);
                break;
            case 'promote_students':
                $result = promoteStudents([
                    'from_academic_year_id' => $_POST['from_academic_year_id'],
                    'to_academic_year_id' => $_POST['to_academic_year_id'],
                    'notes' => $_POST['notes'] ?? '',
                    'admin_id' => $_SESSION['user_id'] ?? 1
                ]);
                break;
            case 'get_class_details':
                $result = getDetailedClassInfo($_POST['class_id']);
                break;
            case 'get_class_advisors':
                $result = getClassAdvisors($_POST['class_id']);
                break;
            default:
                $result = ['success' => false, 'message' => 'ไม่พบ action ที่ระบุ'];
                break;
        }
        
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        error_log('เกิดข้อผิดพลาดในการประมวลผล: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการประมวลผล: ' . $e->getMessage()
        ]);
        exit;
    }
}

// ตรวจสอบการเรียก API ผ่าน GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'download_report' && isset($_GET['class_id'])) {
        downloadClassReport($_GET['class_id']);
        exit;
    }
}

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/classes_content.php';
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js',
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

// แสดงผลหน้าเว็บ
include_once 'templates/header.php';
include_once 'templates/sidebar.php';
include_once 'templates/main_content.php';
include_once 'templates/footer.php';
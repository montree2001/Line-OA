<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียนและแผนกวิชา
 * 
 * ระบบ STP-Prasat (Student Tracking Platform)
 * 1. สร้างและจัดการชั้นเรียน
 * 2. กำหนดครูที่ปรึกษาให้แต่ละชั้นเรียน
 * 3. เลื่อนระดับชั้นเมื่อเปลี่ยนปีการศึกษา
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าฟังก์ชันที่จำเป็น
require_once 'includes/classes_functions.php';
require_once 'includes/department_functions.php';
require_once 'includes/teacher_functions.php';
require_once 'includes/academic_years_functions.php';
require_once 'includes/api_functions.php';

// ดึงข้อมูลที่จำเป็น
$classes = getClassesFromDB();
$departments = getDepartmentsFromDB();
$academicYearData = getAcademicYearsFromDB();
$teachers = getTeachersFromDB();

// ถ้าดึงข้อมูลไม่สำเร็จ ใช้ข้อมูลตัวอย่าง
if ($classes === false) $classes = getSampleClasses();
if ($departments === false) $departments = getSampleDepartments();
if ($academicYearData === false) $academicYearData = getSampleAcademicYears();
if ($teachers === false) $teachers = getSampleTeachers();

$academic_years = $academicYearData['academic_years'];
$has_new_academic_year = $academicYearData['has_new_academic_year'];
$active_year_id = $academicYearData['active_year_id'];

// ข้อมูลการเลื่อนชั้น
if ($has_new_academic_year && $active_year_id !== null) {
    $promotion_counts = getPromotionCounts($active_year_id);
    if ($promotion_counts === false) $promotion_counts = getSamplePromotionCounts();
} else {
    $promotion_counts = [];
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
    'at_risk_count' => getAtRiskStudentCount()
];

// ตรวจสอบการ submit form หรือเรียก API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_class':
            $result = addClass($_POST);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            exit;
            
        case 'edit_class':
            $result = updateClass($_POST);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            exit;
            
        case 'delete_class':
            $result = deleteClass($_POST['class_id']);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            exit;
            
        case 'manage_advisors':
            $changes = json_decode($_POST['changes'] ?? '[]', true);
            $result = updateClassAdvisors($_POST['class_id'], $changes);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            exit;
        
        case 'promote_students':
            $result = promoteStudents([
                'from_academic_year_id' => $_POST['from_academic_year_id'],
                'to_academic_year_id' => $_POST['to_academic_year_id'],
                'notes' => $_POST['notes'] ?? '',
                'admin_id' => $_SESSION['user_id'] ?? 1
            ]);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'promoted_count' => $result['promoted_count'] ?? 0,
                'graduated_count' => $result['graduated_count'] ?? 0
            ]);
            exit;
            
        case 'get_class_details':
            $result = getDetailedClassInfo($_POST['class_id']);
            echo json_encode($result);
            exit;
            
        case 'get_class_advisors':
            $result = getClassAdvisors($_POST['class_id']);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'class_name' => $result['class_name'] ?? '',
                'advisors' => $result['advisors'] ?? []
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

// แสดงผลหน้าเว็บ
include_once 'templates/header.php';
include_once 'templates/sidebar.php';
include_once 'templates/main_content.php';
include_once 'templates/footer.php';
?>
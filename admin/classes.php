<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียนและแผนกวิชา
 * 
 * ระบบ STP-Prasat (Student Tracking Platform)
 * 1. สร้างและจัดการชั้นเรียน
 * 2. กำหนดครูที่ปรึกษาให้แต่ละชั้นเรียน
 * 3. เลื่อนระดับชั้นเมื่อเปลี่ยนปีการศึกษา
 */

// สำหรับ debug เท่านั้น ควรปิดในโหมดการทำงานจริง
error_reporting(E_ALL);
ini_set('display_errors', 1);

// เริ่ม session
session_start();
// ตรวจสอบการล็อกอิน (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];
// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ตรวจสอบไฟล์และนำเข้าฟังก์ชันที่จำเป็น
try {
    $required_files = [
        __DIR__ . '/includes/classes_functions.php',
        __DIR__ . '/includes/department_functions.php',
        __DIR__ . '/includes/teacher_functions.php',
        __DIR__ . '/includes/academic_years_functions.php',
        __DIR__ . '/includes/api_functions.php'
    ];
    
    // ตรวจสอบและนำเข้าไฟล์
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("ไม่พบไฟล์ที่จำเป็น: $file");
        }
        require_once $file;
    }
    
    // ดึงข้อมูลที่จำเป็น
    global $conn;
    $conn = getDB();
    if (!$conn) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }
    
    $departments = getDepartmentsFromDB();
    $classes = getClassesFromDB();
    $academicYearData = getAcademicYearsFromDB();
    $teachers = getTeachersFromDB();
    
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
    error_log('classes.php error: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile());
    $isDebug = true; // ตั้งค่าเป็น false เมื่อใช้งานจริง
    
    if ($isDebug) {
        echo '<div class="alert alert-danger" role="alert">
                <strong>เกิดข้อผิดพลาด:</strong> ' . $e->getMessage() . '
                <br>File: ' . basename($e->getFile()) . ' (line ' . $e->getLine() . ')
                <br>ดูรายละเอียดเพิ่มเติมได้ที่ error log
              </div>';
    } else {
        echo '<div class="alert alert-warning" role="alert">
                <i class="material-icons">warning</i>
                เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาติดต่อผู้ดูแลระบบ
              </div>';
    }
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
}

// ตรวจสอบการ submit form หรือเรียก API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตั้งค่า header เป็น JSON และเปิดใช้งาน UTF-8
    header('Content-Type: application/json; charset=utf-8');
    
    // ตรวจสอบ action
    $action = $_POST['action'] ?? '';
    
    try {
        // กำหนดตัวแปรสำหรับตอบกลับ
        $result = null;
        
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
            case 'add_department':
                $result = addDepartment($_POST);
                break;
            case 'edit_department':
                $result = updateDepartment($_POST);
                break;
            case 'delete_department':
                $result = deleteDepartment($_POST['department_id']);
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
        
        // ตรวจสอบก่อนแปลง JSON
        if (!is_array($result)) {
            throw new Exception('ข้อมูลตอบกลับไม่ถูกต้อง ไม่ใช่ array');
        }
        
        // สร้าง JSON และตอบกลับ
        $json = json_encode($result, JSON_UNESCAPED_UNICODE);
        
        // ตรวจสอบว่า json_encode ทำงานได้ถูกต้อง
        if ($json === false) {
            throw new Exception('เกิดข้อผิดพลาดในการแปลงข้อมูลเป็น JSON: ' . json_last_error_msg());
        }
        
        echo $json;
    } catch (Exception $e) {
        // บันทึกข้อผิดพลาด
        error_log('เกิดข้อผิดพลาดในการประมวลผล action: ' . $action . ', error: ' . $e->getMessage());
        
        // ส่งข้อความผิดพลาดกลับไปหาผู้ใช้งาน
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการประมวลผล: ' . $e->getMessage(),
            'error_details' => $isDebug ? $e->getTraceAsString() : null
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // จบการทำงานหลังจากตอบกลับ API
    exit;
}

// ตรวจสอบการเรียก API ผ่าน GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    try {
        if ($action == 'download_report' && isset($_GET['class_id'])) {
            downloadClassReport($_GET['class_id'], $_GET['type'] ?? 'full');
            exit;
        }
    } catch (Exception $e) {
        // บันทึกข้อผิดพลาด
        error_log('เกิดข้อผิดพลาดในการดาวน์โหลดรายงาน: ' . $e->getMessage());
        
        // ส่งข้อความผิดพลาดกลับไปหาผู้ใช้งาน
        header('Content-Type: text/plain; charset=utf-8');
        echo 'เกิดข้อผิดพลาดในการดาวน์โหลดรายงาน: ' . $e->getMessage();
        exit;
    }
}

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/classes_content.php';
$extra_css = [
    'assets/css/classes.css',
    'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
    'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css'
];

$extra_js = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
    'assets/js/classes.js',
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

// แสดงผลหน้าเว็บ
include_once 'templates/header.php';
include_once 'templates/sidebar.php';
include_once 'templates/main_content.php';
include_once 'templates/footer.php';
?>

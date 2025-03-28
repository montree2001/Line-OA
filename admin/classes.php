<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียนและแผนกวิชา
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat (Student Tracking Platform)
 * - จัดการข้อมูลชั้นเรียน
 * - กำหนดและจัดการครูที่ปรึกษา
 * - เลื่อนชั้นเรียนอัตโนมัติเมื่อเปลี่ยนปีการศึกษา
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    // เก็บ URL ปัจจุบันเพื่อกลับมาหลังจากล็อกอิน
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../login.php');
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าไฟล์ฟังก์ชัน
require_once 'includes/classes_functions.php';
require_once 'includes/department_functions.php';
require_once 'includes/teacher_functions.php';
require_once 'includes/api_functions.php';

// ตรวจสอบการส่ง Form หรือการเรียก API
if (isset($_POST['action']) || isset($_GET['action'])) {
    // จัดการ Form Action
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            // === แผนกวิชา ===
            case 'add_department':
                $department_name = $_POST['department_name'] ?? '';
                $department_code = $_POST['department_code'] ?? '';
                
                if (empty($department_name) || empty($department_code)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุชื่อและรหัสแผนกวิชา'
                    ];
                } else {
                    $result = addDepartment([
                        'department_name' => $department_name,
                        'department_code' => $department_code
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'edit_department':
                $department_id = $_POST['department_id'] ?? '';
                $department_name = $_POST['department_name'] ?? '';
                
                if (empty($department_id) || empty($department_name)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'
                    ];
                } else {
                    $result = updateDepartment([
                        'department_id' => $department_id,
                        'department_name' => $department_name
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'delete_department':
                $department_id = $_POST['department_id'] ?? '';
                
                if (empty($department_id)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุรหัสแผนกวิชา'
                    ];
                } else {
                    $result = deleteDepartment($department_id);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            // === ชั้นเรียน ===
            case 'add_class':
                $academic_year_id = $_POST['academic_year_id'] ?? '';
                $level = $_POST['level'] ?? '';
                $department_id = $_POST['department_id'] ?? '';
                $group_number = $_POST['group_number'] ?? '';
                $classroom = $_POST['classroom'] ?? '';
                
                if (empty($academic_year_id) || empty($level) || empty($department_id) || empty($group_number)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                    ];
                } else {
                    $result = addClass([
                        'academic_year_id' => $academic_year_id,
                        'level' => $level,
                        'department_id' => $department_id,
                        'group_number' => $group_number,
                        'classroom' => $classroom
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message'],
                        'class_id' => $result['class_id'] ?? null
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'edit_class':
                $class_id = $_POST['class_id'] ?? '';
                $academic_year_id = $_POST['academic_year_id'] ?? '';
                $level = $_POST['level'] ?? '';
                $department_id = $_POST['department_id'] ?? '';
                $group_number = $_POST['group_number'] ?? '';
                $classroom = $_POST['classroom'] ?? '';
                
                if (empty($class_id) || empty($academic_year_id) || empty($level) || 
                    empty($department_id) || empty($group_number)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
                    ];
                } else {
                    $result = updateClass([
                        'class_id' => $class_id,
                        'academic_year_id' => $academic_year_id,
                        'level' => $level,
                        'department_id' => $department_id,
                        'group_number' => $group_number,
                        'classroom' => $classroom
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'delete_class':
                $class_id = $_POST['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุรหัสชั้นเรียน'
                    ];
                } else {
                    $result = deleteClass($class_id);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'get_class_details':
                $class_id = $_POST['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'ไม่ระบุรหัสชั้นเรียน'
                    ];
                } else {
                    $result = getDetailedClassInfo($class_id);
                    $response = $result;
                }
                
                echo json_encode($response);
                exit;
                
            // === ครูที่ปรึกษา ===
            case 'get_class_advisors':
                $class_id = $_POST['class_id'] ?? '';
                
                if (empty($class_id)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'ไม่ระบุรหัสชั้นเรียน'
                    ];
                } else {
                    $result = getClassAdvisors($class_id);
                    
                    // ถ้าสำเร็จ ส่งข้อมูลครูที่ปรึกษากลับ
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message'] ?? '',
                        'class_name' => $result['class_name'] ?? '',
                        'advisors' => $result['advisors'] ?? []
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            case 'manage_advisors':
                $class_id = $_POST['class_id'] ?? '';
                $changes = json_decode($_POST['changes'] ?? '[]', true);
                
                if (empty($class_id) || empty($changes)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'ไม่มีข้อมูลการเปลี่ยนแปลง'
                    ];
                } else {
                    $result = updateClassAdvisors($class_id, $changes);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            // === การเลื่อนชั้น ===
            case 'promote_students':
                $from_academic_year_id = $_POST['from_academic_year_id'] ?? '';
                $to_academic_year_id = $_POST['to_academic_year_id'] ?? '';
                $notes = $_POST['notes'] ?? '';
                
                if (empty($from_academic_year_id) || empty($to_academic_year_id)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'
                    ];
                } else {
                    $result = promoteStudents([
                        'from_academic_year_id' => $from_academic_year_id,
                        'to_academic_year_id' => $to_academic_year_id,
                        'notes' => $notes,
                        'admin_id' => $_SESSION['user_id']
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message'],
                        'promoted_count' => $result['promoted_count'] ?? 0,
                        'graduated_count' => $result['graduated_count'] ?? 0
                    ];
                }
                
                echo json_encode($response);
                exit;
                
            // === ปีการศึกษา ===
            case 'add_academic_year':
                $year = $_POST['year'] ?? '';
                $semester = $_POST['semester'] ?? '';
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? '';
                $required_days = $_POST['required_days'] ?? 80;
                
                if (empty($year) || empty($semester) || empty($start_date) || empty($end_date)) {
                    $response = [
                        'status' => 'error',
                        'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'
                    ];
                } else {
                    $result = addAcademicYear([
                        'year' => $year,
                        'semester' => $semester,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'required_days' => $required_days
                    ]);
                    
                    $response = [
                        'status' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message'],
                        'academic_year_id' => $result['academic_year_id'] ?? null
                    ];
                }
                
                echo json_encode($response);
                exit;
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
                $type = $_GET['type'] ?? 'full';
                
                if (empty($class_id)) {
                    $_SESSION['error_message'] = 'ไม่ระบุรหัสชั้นเรียน';
                    header('Location: classes.php');
                    exit;
                }
                
                // ดาวน์โหลดรายงานชั้นเรียน
                downloadClassReport($class_id, $type);
                exit;
                break;
        }
    }
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียนและแผนกวิชา';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับผู้ใช้
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้ดูแลระบบ',
    'role' => $_SESSION['user_role_name'] ?? 'เจ้าหน้าที่',
    'initials' => mb_substr($_SESSION['user_name'] ?? 'ผรบ', 0, 1, 'UTF-8')
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'เพิ่มแผนกวิชา',
        'icon' => 'business',
        'onclick' => 'showDepartmentModal()'
    ],
    [
        'text' => 'เลื่อนชั้นนักเรียน',
        'icon' => 'upgrade',
        'onclick' => 'showPromoteStudentsModal()',
        'class' => 'btn-warning'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = getAtRiskStudentCount();

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

// รวมข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'classes' => $classes,
    'departments' => $departments,
    'academic_years' => $academic_years,
    'has_new_academic_year' => $has_new_academic_year,
    'current_academic_year' => $current_academic_year,
    'next_academic_year' => $next_academic_year,
    'promotion_counts' => $promotion_counts,
    'teachers' => $teachers,
    'at_risk_count' => $at_risk_count,
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
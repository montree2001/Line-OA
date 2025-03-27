<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// นำเข้าไฟล์ฟังก์ชัน
require_once 'includes/classes_functions.php';
require_once 'includes/department_functions.php';
require_once 'includes/api_handlers.php';

// ตรวจสอบ API requests
if (isset($_POST['action']) || isset($_GET['action'])) {
    handleApiRequest();
    exit;
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
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadClassReport()'
    ],
    [
        'text' => 'สถิติชั้นเรียน',
        'icon' => 'leaderboard',
        'onclick' => 'showClassStatistics()'
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
    'assets/js/charts.js'
];

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

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'classes' => $classes,
    'departments' => $departments,
    'academic_years' => $academic_years,
    'has_new_academic_year' => $has_new_academic_year,
    'current_academic_year' => $current_academic_year,
    'next_academic_year' => $next_academic_year,
    'promotion_counts' => $promotion_counts,
    'teachers' => $teachers
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
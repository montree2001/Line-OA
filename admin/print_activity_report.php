<?php
/**
 * print_activity_report.php - หน้าพิมพ์รายงานผลกิจกรรมเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => isset($_SESSION['user_name']) ? mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8') : 'A',
];

// ดึงข้อมูลปีการศึกษาปัจจุบัน
function getActiveAcademicYear() {
    $db = getDB();
    $query = "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลแผนกวิชาทั้งหมด
function getDepartments() {
    $db = getDB();
    $query = "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลชั้นเรียนตามแผนกวิชาและปีการศึกษา
function getClassesByDepartment($department_id, $academic_year_id) {
    $db = getDB();
    $query = "SELECT * FROM classes 
              WHERE department_id = ? AND academic_year_id = ? AND is_active = 1 
              ORDER BY level, group_number";
    $stmt = $db->prepare($query);
    $stmt->execute([$department_id, $academic_year_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// คำนวณสัปดาห์จากวันที่
function calculateWeeks($start_date, $current_date) {
    $start = new DateTime($start_date);
    $current = new DateTime($current_date);
    $interval = $start->diff($current);
    
    // คำนวณจำนวนวันและหารด้วย 7 เพื่อให้ได้จำนวนสัปดาห์
    $days = $interval->days;
    return floor($days / 7) + 1;
}

// ดึงข้อมูลสัปดาห์ทั้งหมดในภาคเรียน
function getAllWeeks($academic_year) {
    $start_date = new DateTime($academic_year['start_date']);
    $end_date = new DateTime($academic_year['end_date']);
    
    $interval = $start_date->diff($end_date);
    $total_days = $interval->days;
    $total_weeks = ceil($total_days / 7);
    
    $weeks = [];
    for ($i = 1; $i <= $total_weeks; $i++) {
        $week_start = clone $start_date;
        $week_start->modify('+' . (($i - 1) * 7) . ' days');
        
        $week_end = clone $week_start;
        $week_end->modify('+6 days');
        
        // ปรับให้อยู่ในช่วงของภาคเรียน
        if ($week_end > $end_date) {
            $week_end = clone $end_date;
        }
        
        $weeks[] = [
            'week_number' => $i,
            'start_date' => $week_start->format('Y-m-d'),
            'end_date' => $week_end->format('Y-m-d'),
            'start_date_display' => $week_start->format('d/m/Y'),
            'end_date_display' => $week_end->format('d/m/Y')
        ];
    }
    
    return $weeks;
}

// ดึงข้อมูลวันหยุดนักขัตฤกษ์
function getHolidays($academic_year_id) {
    // ในระบบจริงควรดึงจากฐานข้อมูล
    // สำหรับตัวอย่างนี้ใช้วันหยุดประจำปี 2568 (2025)
    return [
        '2025-01-01' => 'วันขึ้นปีใหม่',
        '2025-02-10' => 'วันมาฆบูชา',
        '2025-04-06' => 'วันจักรี',
        '2025-04-13' => 'วันสงกรานต์',
        '2025-04-14' => 'วันสงกรานต์',
        '2025-04-15' => 'วันสงกรานต์',
        '2025-05-01' => 'วันแรงงานแห่งชาติ',
        '2025-05-05' => 'วันฉัตรมงคล',
        '2025-06-03' => 'วันเฉลิมพระชนมพรรษาสมเด็จพระราชินี',
        '2025-07-28' => 'วันเฉลิมพระชนมพรรษา ร.10',
        '2025-08-12' => 'วันแม่แห่งชาติ',
        '2025-10-13' => 'วันคล้ายวันสวรรคต ร.9',
        '2025-12-05' => 'วันพ่อแห่งชาติ',
        '2025-12-10' => 'วันรัฐธรรมนูญ',
        '2025-12-31' => 'วันสิ้นปี'
    ];
}

// ดึงข้อมูลการเข้าแถวของนักเรียนในชั้นเรียนและช่วงวันที่กำหนด
function getAttendanceData($class_id, $start_date, $end_date) {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียนในชั้นเรียน
    $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name 
              FROM students s 
              JOIN users u ON s.user_id = u.user_id 
              WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
              ORDER BY s.student_code";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลการเข้าแถวในช่วงวันที่กำหนด
    foreach ($students as &$student) {
        $query = "SELECT date, attendance_status, remarks 
                  FROM attendance 
                  WHERE student_id = ? AND date BETWEEN ? AND ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$student['student_id'], $start_date, $end_date]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบข้อมูลการเข้าแถวตามวัน
        $student['attendances'] = [];
        foreach ($attendances as $att) {
            $student['attendances'][$att['date']] = [
                'status' => $att['attendance_status'],
                'remarks' => $att['remarks']
            ];
        }
    }
    
    return $students;
}

// ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
function getClassAdvisors($class_id) {
    $db = getDB();
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary 
              FROM class_advisors ca 
              JOIN teachers t ON ca.teacher_id = t.teacher_id 
              WHERE ca.class_id = ? 
              ORDER BY ca.is_primary DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลการตั้งค่าระบบสำหรับรายงาน
function getReportSettings() {
    $db = getDB();
    $settings = [
        'activity_head_name' => 'นายมนตรี ศรีสุข',
        'activity_head_title' => 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา',
        'director_deputy_name' => 'นายพงษ์ศักดิ์ สนโศรก',
        'director_deputy_title' => 'รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา',
        'director_name' => 'นายชัยพงษ์ พงษ์พิทักษ์',
        'director_title' => 'ผู้อำนวยการวิทยาลัยการอาชีพปราสาท',
        'logo_path' => 'assets/images/school_logo.png'
    ];
    
    // ในระบบจริงควรดึงจากฐานข้อมูล
    $query = "SELECT * FROM system_settings WHERE setting_key IN 
              ('activity_head_name', 'activity_head_title', 'director_deputy_name', 
               'director_deputy_title', 'director_name', 'director_title', 'school_logo')";
    
    try {
        $stmt = $db->query($query);
        $dbSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dbSettings as $setting) {
            if ($setting['setting_key'] === 'school_logo') {
                $settings['logo_path'] = $setting['setting_value'];
            } else {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }
    } catch (PDOException $e) {
        // ถ้าไม่มีการตั้งค่าในฐานข้อมูล ใช้ค่าเริ่มต้น
    }
    
    return $settings;
}

// ดึงข้อมูลสำหรับหน้ารายงาน
$academic_year = getActiveAcademicYear();
$departments = getDepartments();
$current_week = calculateWeeks($academic_year['start_date'], date('Y-m-d'));
$all_weeks = getAllWeeks($academic_year);
$holidays = getHolidays($academic_year['academic_year_id']);
$report_settings = getReportSettings();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'print_activity_report';
$page_title = 'พิมพ์ผลกิจกรรมเข้าแถว';
$page_header = 'พิมพ์รายงานผลกิจกรรมเข้าแถว';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/print_activity.css',
];

$extra_js = [
    'assets/js/print_activity.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/print_activity_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
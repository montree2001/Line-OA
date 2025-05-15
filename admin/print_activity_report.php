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

// ดึงข้อมูลสัปดาห์ทั้งหมดในภาคเรียน
function getAllWeeks($academic_year) {
    $start_date = new DateTime($academic_year['start_date']);
    $end_date = new DateTime($academic_year['end_date']);
    
    // ตรวจสอบว่าเป็นระดับ ปวช. หรือ ปวส.
    $is_high_level = strpos($academic_year['description'] ?? '', 'ปวส.') !== false;
    $max_weeks = $is_high_level ? 15 : 18; // ปวส. 15 สัปดาห์, ปวช. 18 สัปดาห์
    
    $interval = $start_date->diff($end_date);
    $total_days = $interval->days;
    $total_weeks = min(ceil($total_days / 7), $max_weeks); // จำกัดจำนวนสัปดาห์
    
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
        
        // แปลงวันที่เป็นรูปแบบ d/m/Y ภาษาไทย
        $thai_start = thaiDate($week_start->format('Y-m-d'));
        $thai_end = thaiDate($week_end->format('Y-m-d'));
        
        $weeks[] = [
            'week_number' => $i,
            'start_date' => $week_start->format('Y-m-d'),
            'end_date' => $week_end->format('Y-m-d'),
            'start_date_display' => $thai_start,
            'end_date_display' => $thai_end
        ];
    }
    
    return $weeks;
}

// ฟังก์ชันแปลงวันที่เป็นรูปแบบไทย
function thaiDate($date) {
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    list($year, $month, $day) = explode('-', $date);
    $thai_year = (int)$year + 543;
    $thai_month = $thai_months[$month];
    
    return $day . ' ' . $thai_month . ' ' . $thai_year;
}

// ดึงข้อมูลวันหยุดนักขัตฤกษ์และวันหยุดเพิ่มเติม
function getHolidays($academic_year_id) {
    $db = getDB();
    $holidays = [];
    
    // ดึงวันหยุดจากฐานข้อมูล (ถ้ามี)
    try {
        $query = "SELECT holiday_date, holiday_name FROM holidays WHERE academic_year_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$academic_year_id]);
        $db_holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($db_holidays as $holiday) {
            $holidays[$holiday['holiday_date']] = $holiday['holiday_name'];
        }
    } catch (PDOException $e) {
        // ถ้าไม่มีตาราง holidays ให้ใช้ข้อมูลจำลอง
    }
    
    // ถ้าไม่มีข้อมูลวันหยุดในฐานข้อมูล ใช้วันหยุดนักขัตฤกษ์พื้นฐาน
    if (empty($holidays)) {
        $holidays = [
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
    
    // ดึงวันหยุดเพิ่มเติมจากการตั้งค่า
    $exemption_dates = getSetting('exemption_dates');
    if ($exemption_dates) {
        $dates = explode(',', $exemption_dates);
        foreach ($dates as $date) {
            $date = trim($date);
            if (!empty($date)) {
                // แปลงรูปแบบ dd/mm/yyyy เป็น yyyy-mm-dd ถ้าจำเป็น
                if (strpos($date, '/') !== false) {
                    list($d, $m, $y) = explode('/', $date);
                    $date = "$y-$m-$d";
                }
                
                if (!isset($holidays[$date])) {
                    $holidays[$date] = 'วันหยุดพิเศษ';
                }
            }
        }
    }
    
    return $holidays;
}

// ดึงค่าการตั้งค่าจากฐานข้อมูล
function getSetting($key, $default = '') {
    $db = getDB();
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : $default;
}

// ดึงข้อมูลการตั้งค่าระบบสำหรับรายงาน
function getReportSettings() {
    $settings = [
        'activity_head_name' => getSetting('activity_head_name', 'นายมนตรี ศรีสุข'),
        'activity_head_title' => getSetting('activity_head_title', 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา'),
        'director_deputy_name' => getSetting('director_deputy_name', 'นายพงษ์ศักดิ์ สนโศรก'),
        'director_deputy_title' => getSetting('director_deputy_title', 'รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา'),
        'director_name' => getSetting('director_name', 'นายชัยพงษ์ พงษ์พิทักษ์'),
        'director_title' => getSetting('director_title', 'ผู้อำนวยการวิทยาลัยการอาชีพปราสาท'),
        'logo_path' => getSetting('school_logo', 'assets/images/school_logo.png')
    ];
    
    return $settings;
}

// ดึงข้อมูลสำหรับหน้ารายงาน
$academic_year = getActiveAcademicYear();
$departments = getDepartments();
$current_week = calculateWeeks($academic_year['start_date'], date('Y-m-d'));
$all_weeks = getAllWeeks($academic_year);
$holidays = getHolidays($academic_year['academic_year_id']);
$report_settings = getReportSettings();

// คำนวณสัปดาห์ปัจจุบันจากวันที่เริ่มต้นภาคเรียน
function calculateWeeks($start_date, $current_date) {
    $start = new DateTime($start_date);
    $current = new DateTime($current_date);
    $interval = $start->diff($current);
    
    // คำนวณจำนวนวันและหารด้วย 7 เพื่อให้ได้จำนวนสัปดาห์
    $days = $interval->days;
    return floor($days / 7) + 1;
}

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
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/print_activity_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
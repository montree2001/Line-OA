<?php
/**
 * attendance_report.php - หน้ารายงานการเข้าแถวของนักเรียน
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
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

// ข้อมูลเกี่ยวกับผู้ใช้งาน
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้ใช้งาน',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => substr(($_SESSION['user_name'] ?? 'A'), 0, 1),
];

// ดึงข้อมูลปีการศึกษาที่เปิดใช้งาน
$conn = getDB();
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// ถ้าไม่มีปีการศึกษาที่เปิดใช้งาน ให้แสดงข้อความแจ้งเตือน
if (!$academic_year) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน กรุณาตั้งค่าปีการศึกษาก่อน";
    header('Location: settings.php');
    exit;
}

// ดึงข้อมูลแผนกวิชา
$query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
$stmt = $conn->query($query);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องเรียนทั้งหมด
$query = "SELECT c.class_id, c.level, c.group_number, d.department_name, 
          CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name 
          FROM classes c 
          JOIN departments d ON c.department_id = d.department_id 
          WHERE c.academic_year_id = ? AND c.is_active = 1 
          ORDER BY c.level, c.group_number, d.department_name";
$stmt = $conn->prepare($query);
$stmt->execute([$academic_year['academic_year_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// การตั้งค่าเริ่มต้น
$department_id = $_GET['department_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$start_week = isset($_GET['start_week']) ? $_GET['start_week'] : 1;
$end_week = isset($_GET['end_week']) ? $_GET['end_week'] : 1;
$search_term = $_GET['search'] ?? '';

// ถ้าเป็นครูที่ปรึกษา ให้แสดงเฉพาะห้องที่เป็นที่ปรึกษา
if ($_SESSION['user_role'] == 'teacher') {
    $teacher_id = $_SESSION['teacher_id'] ?? 0;
    
    // ดึงข้อมูลห้องที่เป็นที่ปรึกษา
    $query = "SELECT c.class_id FROM classes c 
              JOIN class_advisors ca ON c.class_id = ca.class_id 
              WHERE ca.teacher_id = ? AND c.academic_year_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$teacher_id, $academic_year['academic_year_id']]);
    $advisor_classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ถ้ายังไม่ได้เลือกห้อง ให้ใช้ห้องแรกที่เป็นที่ปรึกษา
    if (!$class_id && !empty($advisor_classes)) {
        $class_id = $advisor_classes[0];
    }
    
    // กรองเฉพาะห้องที่เป็นที่ปรึกษา
    $classes = array_filter($classes, function($class) use ($advisor_classes) {
        return in_array($class['class_id'], $advisor_classes);
    });
}

// คำนวณสัปดาห์ทั้งหมดในภาคเรียน
$start_date = new DateTime($academic_year['start_date']);
$end_date = new DateTime($academic_year['end_date']);
$interval = $start_date->diff($end_date);
$total_weeks = ceil($interval->days / 7);

// สร้างตัวเลือกสัปดาห์
$week_options = [];
for ($i = 1; $i <= $total_weeks; $i++) {
    // คำนวณวันเริ่มต้นและสิ้นสุดของสัปดาห์
    $week_start = clone $start_date;
    $week_start->modify('+' . (($i - 1) * 7) . ' days');
    
    $week_end = clone $week_start;
    $week_end->modify('+6 days');
    
    if ($week_end > $end_date) {
        $week_end = clone $end_date;
    }
    
    $week_options[] = [
        'number' => $i,
        'start_date' => $week_start->format('Y-m-d'),
        'end_date' => $week_end->format('Y-m-d'),
        'text' => "สัปดาห์ที่ {$i}: " . $week_start->format('d/m/Y') . " - " . $week_end->format('d/m/Y')
    ];
}

// ดึงข้อมูลห้องเรียนที่เลือก
$selected_class = null;
if ($class_id) {
    $query = "SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name, 
              CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $selected_class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_class) {
        $department_id = $selected_class['department_id'];
    }
}

// ดึงข้อมูลนักเรียนตามห้องหรือรายบุคคล
$students = [];
if ($class_id || $student_id) {
    $query_params = [];
    
    if ($student_id) {
        // กรณีค้นหารายบุคคล
        $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                  c.class_id, c.level, c.group_number, d.department_name,
                  CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name,
                  CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id 
                  JOIN classes c ON s.current_class_id = c.class_id 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE s.student_id = ? AND s.status = 'กำลังศึกษา'";
        $query_params[] = $student_id;
    } else {
        // กรณีค้นหาตามห้อง
        $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                  c.class_id, c.level, c.group_number, d.department_name,
                  CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name,
                  CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id 
                  JOIN classes c ON s.current_class_id = c.class_id 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";
        $query_params[] = $class_id;
    }
    
    // เพิ่มการค้นหา
    if (!empty($search_term)) {
        $query .= " AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_term = "%{$search_term}%";
        $query_params[] = $search_term;
        $query_params[] = $search_term;
        $query_params[] = $search_term;
    }
    
    $query .= " ORDER BY s.student_code";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// เตรียมข้อมูลวันที่ของรายงาน
$report_data = [];
if ($start_week && $end_week && !empty($students)) {
    // กำหนดวันเริ่มต้นและสิ้นสุดของรายงาน
    $report_start_date = $week_options[$start_week - 1]['start_date'] ?? $academic_year['start_date'];
    $report_end_date = $week_options[$end_week - 1]['end_date'] ?? $academic_year['end_date'];
    
    // สร้างข้อมูลวันที่สำหรับรายงาน (เฉพาะวันจันทร์-ศุกร์)
    $current_date = new DateTime($report_start_date);
    $end_report_date = new DateTime($report_end_date);
    
    // ดึงข้อมูลวันหยุด
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'exemption_dates'";
    $stmt = $conn->query($query);
    $exemption_dates_str = $stmt->fetchColumn() ?: '';
    $exemption_dates = explode(',', $exemption_dates_str);
    $exemption_dates = array_map('trim', $exemption_dates);
    
    // สร้างอาเรย์วันที่
    $report_dates = [];
    while ($current_date <= $end_report_date) {
        $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
        
        // เฉพาะวันจันทร์ถึงศุกร์
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $date_str = $current_date->format('Y-m-d');
            $is_holiday = in_array($date_str, $exemption_dates);
            
            $day_names = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
            
            $report_dates[] = [
                'date' => $date_str,
                'day_name' => $day_names[$day_of_week],
                'day_num' => $current_date->format('j'),
                'is_holiday' => $is_holiday,
                'holiday_name' => $is_holiday ? 'วันหยุด' : null
            ];
        }
        
        $current_date->modify('+1 day');
    }
    
    // ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
    $student_ids = array_column($students, 'student_id');
    $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
    
    $query = "SELECT student_id, date, attendance_status FROM attendance 
              WHERE student_id IN ({$placeholders}) 
              AND academic_year_id = ? 
              AND date BETWEEN ? AND ?";
    
    $query_params = array_merge($student_ids, [$academic_year['academic_year_id'], $report_start_date, $report_end_date]);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูลเป็น [student_id][date] => status
    $attendance_data = [];
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
    
    // ดึงข้อมูลครูที่ปรึกษา
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name 
              FROM teachers t 
              JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
              WHERE ca.class_id = ? AND ca.is_primary = 1
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $primary_advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // นับจำนวนนักเรียนชาย/หญิง
    $male_count = 0;
    $female_count = 0;
    foreach ($students as $student) {
        if ($student['title'] == 'นาย') {
            $male_count++;
        } else {
            $female_count++;
        }
    }
    
    // สร้างข้อมูลรายงาน
    $report_data = [
        'academic_year' => $academic_year,
        'class' => $selected_class,
        'department' => ['department_name' => $selected_class['department_name'] ?? ''],
        'students' => $students,
        'week_days' => $report_dates,
        'attendance_data' => $attendance_data,
        'total_count' => count($students),
        'male_count' => $male_count,
        'female_count' => $female_count,
        'primary_advisor' => $primary_advisor,
        'start_date' => $report_start_date,
        'end_date' => $report_end_date,
        'week_number' => "({$start_week}" . ($start_week != $end_week ? "-{$end_week}" : '') . ")"
    ];
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'attendance_report';
$page_title = 'รายงานการเข้าแถว';
$page_header = 'รายงานการเข้าแถว';

// กำหนดปุ่มในส่วนหัว
$header_buttons = [];

if (!empty($report_data)) {
    // ปุ่มพิมพ์รายงานตัวอักษร
    $header_buttons[] = [
        'id' => 'print-report-btn',
        'text' => 'พิมพ์รายงาน',
        'icon' => 'print',
        'onclick' => "printAttendanceReport()"
    ];
    
    // ปุ่มพิมพ์รายงานกราฟ
    $header_buttons[] = [
        'id' => 'print-chart-btn',
        'text' => 'พิมพ์กราฟ',
        'icon' => 'analytics',
        'onclick' => "printAttendanceChart()"
    ];
    
    // ปุ่มดาวน์โหลด Excel
    $header_buttons[] = [
        'id' => 'download-excel-btn',
        'text' => 'ดาวน์โหลด Excel',
        'icon' => 'download',
        'onclick' => "downloadExcel()"
    ];
}

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/reports_print.css',
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    'https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'assets/js/attendance_report.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/attendance_report_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
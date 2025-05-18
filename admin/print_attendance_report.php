<?php
/**
 * print_attendance_report.php - สร้างไฟล์ PDF รายงานการเข้าแถวรายสัปดาห์
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
/* แสดงผล Erorr */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['class_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

// นำเข้าไฟล์ MPDF
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

// ดึงข้อมูลที่ส่งมา
$class_id = $_POST['class_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$week_number = $_POST['week_number'] ?? 1;
$end_week = $_POST['end_week'] ?? $week_number;
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องเรียน
$query = "SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name 
          FROM classes c 
          JOIN departments d ON c.department_id = d.department_id 
          WHERE c.class_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    die('ไม่พบข้อมูลห้องเรียน');
}

// ดึงข้อมูลแผนกวิชา
$department = [
    'department_id' => $class['department_id'],
    'department_name' => $class['department_name']
];

// กำหนดเงื่อนไขการค้นหา
$searchCondition = '';
$searchParams = [$class_id];

if (!empty($search)) {
    $searchCondition = " AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchParams[] = "%$search%";
    $searchParams[] = "%$search%";
    $searchParams[] = "%$search%";
}

// ดึงข้อมูลนักเรียนในห้อง
$query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
          CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
          FROM students s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE s.current_class_id = ? $searchCondition AND s.status = 'กำลังศึกษา' 
          ORDER BY s.student_code";
$stmt = $conn->prepare($query);
$stmt->execute($searchParams);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($students);

if ($total_count === 0) {
    die('ไม่พบข้อมูลนักเรียนในห้องเรียนที่เลือก');
}

// นับจำนวนนักเรียนชาย/หญิง
$male_count = 0;
$female_count = 0;
foreach ($students as $student) {
    if ($student['display_title'] == 'นาย') {
        $male_count++;
    } else {
        $female_count++;
    }
}

// ดึงข้อมูลวันหยุด
$query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->execute([$start_date, $end_date]);
$holidays = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $holidays[$row['holiday_date']] = $row['holiday_name'];
}

// ดึงข้อมูลวันยกเว้นเพิ่มเติม
$query = "SELECT setting_value FROM system_settings WHERE setting_key = 'exemption_dates'";
$stmt = $conn->query($query);
$exemption_dates_str = $stmt->fetchColumn() ?: '';
$exemption_dates = explode(',', $exemption_dates_str);
$exemption_dates = array_map('trim', $exemption_dates);

// กำหนดค่าวันไทย
$thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
$thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
$thaiMonths = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];

// สร้างอาเรย์สัปดาห์ที่ต้องการพิมพ์
$weeks = [];

// คำนวณวันเริ่มต้นของภาคเรียน
$semesterStart = new DateTime($academic_year['start_date']);

// สร้างข้อมูลสำหรับแต่ละสัปดาห์ที่เลือก
for ($currentWeek = (int)$week_number; $currentWeek <= (int)$end_week; $currentWeek++) {
    // คำนวณวันเริ่มต้นของสัปดาห์ (จันทร์)
    $weekStart = clone $semesterStart;
    $weekStart->modify('+' . (($currentWeek - 1) * 7) . ' days');
    
    // ปรับให้เป็นวันจันทร์
    $dayOfWeek = (int)$weekStart->format('N'); // 1 = จันทร์, 7 = อาทิตย์
    if ($dayOfWeek > 1) {
        $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
    }
    
    // คำนวณวันสิ้นสุดของสัปดาห์ (ศุกร์)
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+4 days');
    
    // สร้างข้อมูลวันในสัปดาห์ (จันทร์-ศุกร์ เท่านั้น)
    $days = [];
    $currentDay = clone $weekStart;
    
    for ($i = 0; $i < 5; $i++) {
        $dateStr = $currentDay->format('Y-m-d');
        $monthKey = $currentDay->format('m');
        
        // ตรวจสอบว่าเป็นวันหยุดหรือไม่
        $isHoliday = in_array($dateStr, $exemption_dates) || isset($holidays[$dateStr]);
        $holidayName = isset($holidays[$dateStr]) ? $holidays[$dateStr] : null;
        
        $days[] = [
            'date' => $dateStr,
            'day_name' => $thaiDayAbbrs[$currentDay->format('w')],
            'day_full' => $thaiDays[$currentDay->format('w')],
            'day_num' => $currentDay->format('j'),
            'month' => $thaiMonths[$monthKey],
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName
        ];
        
        $currentDay->modify('+1 day');
    }
    
    $weeks[] = [
        'week_number' => $currentWeek,
        'start_date' => $weekStart->format('Y-m-d'),
        'end_date' => $weekEnd->format('Y-m-d'),
        'days' => $days
    ];
}

// ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
$student_ids = array_column($students, 'student_id');
$attendance_data = [];

if (!empty($student_ids)) {
    $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
    
    $query = "SELECT student_id, date, attendance_status FROM attendance 
              WHERE student_id IN ({$placeholders}) 
              AND academic_year_id = ? 
              AND date BETWEEN ? AND ?";
    
    $query_params = array_merge($student_ids, [$academic_year['academic_year_id'], $start_date, $end_date]);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูลเป็น [student_id][date] => status
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
}

// ดึงข้อมูลครูที่ปรึกษา
$query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, t.phone 
          FROM teachers t 
          JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
          WHERE ca.class_id = ? AND ca.is_primary = 1
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$primary_advisor = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลผู้ลงนามในรายงาน
$query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY signer_id LIMIT 3";
$stmt = $conn->query($query);
$signers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนดค่า config สำหรับ mPDF
$mpdf_config = [
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font_size' => 16,
    'default_font' => 'thsarabun',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => __DIR__ . '/../tmp'
];

// สร้าง mPDF
$mpdf = new \Mpdf\Mpdf($mpdf_config);

// ตรวจสอบและกำหนดฟอนต์
$mpdf->fontdata['thsarabun'] = [
    'R' => 'THSarabunNew.ttf',
    'B' => 'THSarabunNew-Bold.ttf',
    'I' => 'THSarabunNew-Italic.ttf',
    'BI' => 'THSarabunNew-BoldItalic.ttf'
];

$mpdf->SetFont('thsarabun');
$mpdf->useAdobeCJK = true;
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

$mpdf->SetTitle("รายงานการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}");

// กำหนดตัวแปรสำหรับหน้า
$studentsPerPage = 30; // จำนวนนักเรียนต่อหน้า

// สร้าง PDF สำหรับแต่ละสัปดาห์ (แยกหน้าแต่ละสัปดาห์)
foreach ($weeks as $weekIndex => $week) {
    // สำหรับแต่ละสัปดาห์จะสร้างหน้า PDF ใหม่
    if ($weekIndex > 0) {
        $mpdf->AddPage();
    }
    
    // กำหนดตัวแปรที่จะส่งไปยังเทมเพลต
    $week_number = $week['week_number'];
    $start_date = $week['start_date'];
    $end_date = $week['end_date'];
    $week_days = $week['days'];
    
    // คำนวณจำนวนหน้าทั้งหมดสำหรับสัปดาห์นี้
    $totalPages = ceil(count($students) / $studentsPerPage);
    
    // ถ้านักเรียนมีจำนวนมากเกินกว่าที่จะแสดงในหน้าเดียว
    if ($totalPages > 1) {
        for ($page = 1; $page <= $totalPages; $page++) {
            if ($page > 1) {
                $mpdf->AddPage();
            }
            
            $startIndex = ($page - 1) * $studentsPerPage;
            $pageStudents = array_slice($students, $startIndex, $studentsPerPage);
            $currentPage = $page;
            
            // สร้างเนื้อหา PDF ตามแบบฟอร์ม
            ob_start();
            include 'templates/attendance_report_pdf.php';
            $content = ob_get_clean();
            
            // เพิ่มเนื้อหาลงใน mPDF
            $mpdf->WriteHTML($content);
        }
    } else {
        // ถ้านักเรียนมีจำนวนน้อยพอที่จะแสดงในหน้าเดียว
        $currentPage = 1;
        
        // สร้างเนื้อหา PDF ตามแบบฟอร์ม
        ob_start();
        include 'templates/attendance_report_pdf.php';
        $content = ob_get_clean();
        
        // เพิ่มเนื้อหาลงใน mPDF
        $mpdf->WriteHTML($content);
    }
}

// กำหนดชื่อไฟล์
$filename = "รายงานการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}-{$end_week}.pdf";

// Output PDF
$mpdf->Output($filename, 'I');
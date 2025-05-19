<?php

/**
 * print_attendance_report.php - สร้างไฟล์ PDF รายงานการเข้าแถวรายสัปดาห์
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
$report_type = $_POST['report_type'] ?? 'attendance';
$search = $_POST['search'] ?? '';

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

// ดึงข้อมูลแผนกวิชา
$department = [
    'department_id' => $class['department_id'],
    'department_name' => $class['department_name']
];

// เตรียม query สำหรับดึงนักเรียน
$query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                   CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
                   FROM students s 
                   JOIN users u ON s.user_id = u.user_id 
                   WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";

// เพิ่มเงื่อนไขค้นหาถ้ามี
if (!empty($search)) {
    $query_students .= " AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_param = "%$search%";
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$class_id, $search_param, $search_param, $search_param]);
} else {
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$class_id]);
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($students);

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
$query = "SELECT holiday_date, holiday_name FROM holidays";
$stmt = $conn->query($query);
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

// คำนวณสัปดาห์ที่กำลังดำเนินการ
$currentWeek = $week_number;
$endWeek = $end_week;

// สร้างอาเรย์วันที่สำหรับแต่ละสัปดาห์
$weeks = [];
$semesterStart = new DateTime($academic_year['start_date']);

// สร้างและจัดกลุ่มตามสัปดาห์
for ($w = $currentWeek; $w <= $endWeek; $w++) {
    // คำนวณวันเริ่มต้นของสัปดาห์
    $weekStart = clone $semesterStart;
    $weekStart->modify('+' . (($w - 1) * 7) . ' days');

    // ปรับให้เป็นวันจันทร์
    $dayOfWeek = $weekStart->format('N'); // 1 = จันทร์, 7 = อาทิตย์
    if ($dayOfWeek > 1) {
        $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
    }

    // คำนวณวันสิ้นสุดของสัปดาห์ (วันศุกร์)
    $weekEnd = clone $weekStart;
    $weekEnd->modify('+4 days');

    // สร้างข้อมูลวันจันทร์ถึงศุกร์ของสัปดาห์นี้
    $days = [];
    $currentDay = clone $weekStart;

    $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $thaiMonths = [
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    ];

    for ($i = 0; $i < 5; $i++) { // จันทร์ถึงศุกร์
        $dateStr = $currentDay->format('Y-m-d');
        $dayNum = (int)$currentDay->format('N'); // 1 = จันทร์, 7 = อาทิตย์

        // ตรวจสอบว่าเป็นวันหยุดหรือไม่
        $isHoliday = in_array($dateStr, $exemption_dates) || isset($holidays[$dateStr]);
        $holidayName = isset($holidays[$dateStr]) ? $holidays[$dateStr] : ($isHoliday ? 'วันหยุด' : null);

        $days[] = [
            'date' => $dateStr,
            'day_name' => $thaiDayAbbrs[$dayNum % 7],
            'day_full' => $thaiDays[$dayNum % 7],
            'day_num' => $currentDay->format('j'),
            'month' => $thaiMonths[$currentDay->format('m')],
            'year' => (int)$currentDay->format('Y') + 543, // พ.ศ.
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName
        ];

        $currentDay->modify('+1 day');
    }

    $weeks[] = [
        'week_number' => $w,
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

    // คำนวณวันที่เริ่มต้นของสัปดาห์แรกที่ต้องการดู
    $start_week_date = new DateTime($academic_year['start_date']);
    $start_week_date->modify('+' . (($currentWeek - 1) * 7) . ' days');

    // คำนวณวันที่สิ้นสุดของสัปดาห์สุดท้ายที่ต้องการดู
    $end_week_date = new DateTime($academic_year['start_date']);
    $end_week_date->modify('+' . ($endWeek * 7 - 1) . ' days');

    $query_params = array_merge(
        $student_ids,
        [$academic_year['academic_year_id'], $start_week_date->format('Y-m-d'), $end_week_date->format('Y-m-d')]
    );

    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดรูปแบบข้อมูลเป็น [student_id][date] => status
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
}

// ดึงข้อมูลครูที่ปรึกษา
$query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, u.phone_number as phone 
          FROM teachers t 
          JOIN users u ON t.user_id = u.user_id
          JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
          WHERE ca.class_id = ? AND ca.is_primary = 1
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$primary_advisor = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลผู้ลงนามจากตาราง report_signers
$query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY signer_id LIMIT 3";
$stmt = $conn->query($query);
$signers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// กำหนดความเหมาะสมของจำนวนนักเรียนต่อหน้า (สามารถปรับได้ตามความเหมาะสม)
$studentsPerPage = 20; // ประมาณ 25 คนต่อหน้า A4 ขึ้นอยู่กับขนาดฟอนต์และรูปแบบรายงาน

if ($total_count >=16 AND $total_count <= 19) {
    $studentsPerPage = 15; // 20 คนต่อหน้า
}


// ค้นหาตำแหน่งที่ตั้งของฟอนต์ mPDF
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

// ค้นหาข้อมูลฟอนต์ที่มีอยู่แล้ว
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

// กำหนดค่า config สำหรับ mPDF
$mpdf_config = [
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font_size' => 16,
    'default_font' => 'thsarabunnew',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => __DIR__ . '/../tmp',
    // เพิ่มตำแหน่งที่ตั้งของฟอนต์
    'fontDir' => array_merge($fontDirs, [
        __DIR__ . '/../ttfonts',
    ]),
    // กำหนดข้อมูลฟอนต์
    'fontdata' => array_merge($fontData, [
        'thsarabunnew' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew-Bold.ttf',
            'I' => 'THSarabunNew-Italic.ttf',
            'BI' => 'THSarabunNew-BoldItalic.ttf',
        ],
    ]),
];

// สร้าง mPDF
$mpdf = new \Mpdf\Mpdf($mpdf_config);

// ยกเลิกการใช้ฟอนต์ autoLangToFont
$mpdf->autoLangToFont = false;
$mpdf->autoScriptToLang = false;
$mpdf->useAdobeCJK = false;
$mpdf->SetFont('thsarabunnew');
$mpdf->SetDefaultBodyCSS('font-family', "thsarabunnew");
$mpdf->SetTitle("รายงานการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}");

// สร้าง PDF สำหรับแต่ละสัปดาห์
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

    // วนลูปสร้างหน้าสำหรับสัปดาห์นี้
    for ($page = 1; $page <= $totalPages; $page++) {
        // สร้างหน้าใหม่ถ้าไม่ใช่หน้าแรกของสัปดาห์
        if ($page > 1) {
            $mpdf->AddPage();
        }

        // คำนวณนักเรียนสำหรับหน้านี้
        $startIndex = ($page - 1) * $studentsPerPage;
        $studentsOnCurrentPage = array_slice($students, $startIndex, $studentsPerPage);
        
        // กำหนดตัวแปรสำหรับหน้าปัจจุบัน
        $currentPage = $page;

        // สร้างเนื้อหา PDF ตามแบบฟอร์ม
        ob_start();
        include 'templates/attendance_report_pdf.php';
        $content = ob_get_clean();

        // เพิ่มเนื้อหาลงใน mPDF
        $mpdf->WriteHTML($content);
    }
}

// Output PDF
$filename = "รายงานการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}-{$endWeek}.pdf";
$mpdf->Output($filename, 'I');

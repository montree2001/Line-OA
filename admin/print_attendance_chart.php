<?php
/**
 * print_attendance_chart.php - สร้างไฟล์ PDF กราฟการเข้าแถว
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
$week_number = $_POST['week_number'] ?? '1';
$report_type = $_POST['report_type'] ?? 'chart';

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

// ดึงข้อมูลนักเรียนในห้อง
$query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
          CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
          FROM students s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
          ORDER BY s.student_code";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($students);

// สร้างข้อมูลวันที่สำหรับรายงาน (เฉพาะวันจันทร์-ศุกร์)
$current_date = new DateTime($start_date);
$end_report_date = new DateTime($end_date);

// ดึงข้อมูลวันหยุด
$query = "SELECT setting_value FROM system_settings WHERE setting_key = 'exemption_dates'";
$stmt = $conn->query($query);
$exemption_dates_str = $stmt->fetchColumn() ?: '';
$exemption_dates = explode(',', $exemption_dates_str);
$exemption_dates = array_map('trim', $exemption_dates);

// สร้างอาเรย์วันที่
$week_days = [];
$thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
$thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

while ($current_date <= $end_report_date) {
    $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
    
    // เฉพาะวันจันทร์ถึงศุกร์
    if ($day_of_week >= 1 && $day_of_week <= 5) {
        $date_str = $current_date->format('Y-m-d');
        $is_holiday = in_array($date_str, $exemption_dates);
        
        $week_days[] = [
            'date' => $date_str,
            'day_name' => $thaiDayAbbrs[$day_of_week],
            'day_full' => $thaiDays[$day_of_week],
            'day_num' => $current_date->format('j'),
            'is_holiday' => $is_holiday,
            'holiday_name' => $is_holiday ? 'วันหยุด' : null
        ];
    }
    
    $current_date->modify('+1 day');
}

// ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
$student_ids = array_column($students, 'student_id');
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
    $attendance_data = [];
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
} else {
    $attendance_data = [];
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
$mpdf->useAdobeCJK = true;
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

// อัปโหลดแบบอักษร Sarabun หรือแบบอักษรไทยอื่น ๆ
$mpdf->SetFont('thsarabun');

// สร้างเนื้อหา PDF ตามแบบฟอร์ม
ob_start();
include 'templates/attendance_chart_pdf.php';
$content = ob_get_clean();

// เพิ่มเนื้อหาลงใน mPDF
$mpdf->WriteHTML($content);

// กำหนดชื่อไฟล์
$filename = "กราฟการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}.pdf";

// Output PDF
$mpdf->Output($filename, 'I');
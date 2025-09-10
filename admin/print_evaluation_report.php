<?php
/**
 * print_evaluation_report.php - รายงานการประเมินผลกิจกรรม
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตั้งค่าเวลา
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบ POST data
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    die('ข้อมูลไม่ครบถ้วน');
}

// นำเข้า mPDF และเชื่อมต่อฐานข้อมูล
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

$conn = getDB();

// รับข้อมูลจาก POST
$class_id = $_POST['class_id'] ?? '';
$search_input = $_POST['search'] ?? '';
$search_type = $_POST['search_type'] ?? 'class';
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$start_week = $_POST['week_number'] ?? 1;
$end_week = $_POST['end_week'] ?? 1;

// ดึงข้อมูลปีการศึกษา
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// คำนวณจำนวนสัปดาห์ทั้งหมด
$academic_start = new DateTime($academic_year['start_date']);
$academic_end = new DateTime($academic_year['end_date']);
$total_days = $academic_start->diff($academic_end)->days;
$total_weeks = ceil($total_days / 7);

// ดึงข้อมูลนักเรียน
if ($search_type === 'class' && !empty($class_id)) {
    // ค้นหาตามห้องเรียน
    $query = "
        SELECT s.student_id, s.student_code, s.title, s.first_name, s.last_name, s.level,
               c.level as class_level, c.group_number, d.department_name
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE s.class_id = ? AND s.is_active = 1
        ORDER BY s.student_code
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ข้อมูลห้องเรียน
    $class_info = $students[0] ?? null;
    $report_title = $class_info ? "{$class_info['class_level']}/{$class_info['group_number']} {$class_info['department_name']}" : "ไม่พบข้อมูลห้องเรียน";
} else {
    // ค้นหาตามนักเรียน
    $query = "
        SELECT s.student_id, s.student_code, s.title, s.first_name, s.last_name, s.level,
               c.level as class_level, c.group_number, d.department_name
        FROM students s
        JOIN classes c ON s.class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ?) 
              AND s.is_active = 1
        ORDER BY s.student_code
    ";
    $search_term = "%{$search_input}%";
    $stmt = $conn->prepare($query);
    $stmt->execute([$search_term, $search_term, $search_term]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $report_title = "ค้นหา: {$search_input}";
}

if (empty($students)) {
    die('ไม่พบข้อมูลนักเรียน');
}

// ดึงข้อมูลวันหยุด
$query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->execute([$start_date, $end_date]);
$holidays_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$holidays = [];
foreach ($holidays_data as $holiday) {
    $holidays[$holiday['holiday_date']] = $holiday['holiday_name'];
}

// ดึงข้อมูลการเข้าแถว
$student_ids = array_column($students, 'student_id');
$placeholders = str_repeat('?,', count($student_ids) - 1) . '?';

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($academic_year_query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
$academic_year_id = $academic_year ? $academic_year['academic_year_id'] : 1;

$query = "
    SELECT student_id, date as attendance_date, 
           CASE 
               WHEN attendance_status = 'present' THEN 'present'
               WHEN attendance_status = 'late' THEN 'late'
               ELSE 'absent'
           END as status
    FROM attendance
    WHERE student_id IN ($placeholders) 
          AND academic_year_id = ?
          AND date BETWEEN ? AND ?
    ORDER BY date
";

$params = array_merge($student_ids, [$academic_year_id, $start_date, $end_date]);
$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดรูปแบบข้อมูลการเข้าแถว
$attendance_data = [];
foreach ($attendance_records as $record) {
    $attendance_data[$record['student_id']][$record['attendance_date']] = $record['status'];
}

// เตรียมข้อมูลสำหรับรายงาน HTML
$date_range_text = formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date) . ' (สัปดาห์ที่ ' . $start_week . '-' . $end_week . ')';

// สร้าง mPDF instance
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L', // A4 Landscape
        'default_font_size' => 10,
        'default_font' => 'sarabun',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 5,
        'margin_footer' => 5
    ]);
} catch (\Mpdf\MpdfException $e) {
    die('เกิดข้อผิดพลาดในการสร้าง PDF: ' . $e->getMessage());
}

// คำนวณข้อมูลสำหรับแต่ละสัปดาห์
$weekly_data = [];
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

for ($week = $start_week; $week <= $end_week; $week++) {
    // คำนวณวันที่ของสัปดาห์
    $week_start = new DateTime($academic_year['start_date']);
    $week_start->add(new DateInterval('P' . (($week - 1) * 7) . 'D'));
    
    // ปรับให้เป็นวันจันทร์
    $day_of_week = $week_start->format('w');
    if ($day_of_week == 0) {
        $week_start->add(new DateInterval('P1D'));
    } elseif ($day_of_week > 1) {
        $week_start->sub(new DateInterval('P' . ($day_of_week - 1) . 'D'));
    }
    
    // คำนวณวันจันทร์-ศุกร์
    $week_days = [];
    $temp_date = clone $week_start;
    
    for ($i = 0; $i < 5; $i++) {
        $date_str = $temp_date->format('Y-m-d');
        if ($temp_date >= $current_date && $temp_date <= $end_date_obj) {
            $week_days[] = $date_str;
        }
        $temp_date->add(new DateInterval('P1D'));
    }
    
    // นับวันเรียนจริง (ไม่รวมวันหยุด)
    $study_days = 0;
    foreach ($week_days as $day) {
        if (!isset($holidays[$day])) {
            $study_days++;
        }
    }
    
    $weekly_data[$week] = [
        'days' => $week_days,
        'study_days' => $study_days,
        'week_start' => clone $week_start
    ];
}

// กำหนดเกณฑ์การผ่านกิจกรรม
$pass_criteria = 0.6; // 60%

// สร้าง HTML content
$html = '
<style>
    body { font-family: "sarabun", sans-serif; font-size: 10px; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { font-size: 18px; margin: 5px 0; font-weight: bold; }
    .header h2 { font-size: 16px; margin: 3px 0; font-weight: bold; }
    .header p { font-size: 12px; margin: 2px 0; }
    
    .evaluation-table { width: 100%; border-collapse: collapse; font-size: 8px; }
    .evaluation-table th, .evaluation-table td { 
        border: 1px solid #000; 
        padding: 3px; 
        text-align: center; 
        vertical-align: middle;
    }
    .evaluation-table th { 
        background-color: #f0f0f0; 
        font-weight: bold; 
    }
    .student-name { text-align: left !important; }
    .pass { background-color: #d4edda; color: #155724; font-weight: bold; }
    .fail { background-color: #f8d7da; color: #721c24; font-weight: bold; }
    
    .notes { margin-top: 15px; font-size: 9px; }
    .notes ul { margin: 5px 0; padding-left: 20px; }
</style>

<div class="header">
    <h1>วิทยาลัยการอาชีพปราสาท</h1>
    <h2>รายงานการประเมินผลกิจกรรม</h2>
    <p>' . $report_title . '</p>
    <p>' . $date_range_text . '</p>
</div>

<table class="evaluation-table">
    <thead>
        <tr>
            <th width="30">ลำดับ</th>
            <th width="70">รหัสนักศึกษา</th>
            <th width="100">ชื่อ-สกุล</th>';

// หัวตารางสัปดาห์
foreach ($weekly_data as $week => $data) {
    $week_start_formatted = $data['week_start']->format('d/m');
    $week_end = clone $data['week_start'];
    $week_end->add(new DateInterval('P4D'));
    $week_end_formatted = $week_end->format('d/m');
    
    $html .= '<th width="40">สัปดาห์ ' . $week . '<br>(' . $week_start_formatted . '-' . $week_end_formatted . ')<br>' . $data['study_days'] . ' วัน</th>';
}

$html .= '<th width="40">รวม<br>เข้าแถว</th>
    <th width="40">รวม<br>วันเรียน</th>
    <th width="30">%</th>
    <th width="40">ผลการ<br>ประเมิน</th>
        </tr>
    </thead>
    <tbody>';

// แถวข้อมูลนักเรียน
foreach ($students as $index => $student) {
    $total_present = 0;
    $total_study_days = 0;
    
    $html .= '<tr>
        <td>' . ($index + 1) . '</td>
        <td>' . $student['student_code'] . '</td>
        <td class="student-name">' . $student['title'] . $student['first_name'] . ' ' . $student['last_name'] . '</td>';
    
    // แต่ละสัปดาห์
    foreach ($weekly_data as $week => $data) {
        $week_present = 0;
        $week_study_days = $data['study_days'];
        
        foreach ($data['days'] as $day) {
            if (!isset($holidays[$day])) { // ไม่นับวันหยุด
                if (isset($attendance_data[$student['student_id']][$day])) {
                    $status = $attendance_data[$student['student_id']][$day];
                    if ($status === 'present' || $status === 'late') {
                        $week_present++;
                    }
                }
            }
        }
        
        $total_present += $week_present;
        $total_study_days += $week_study_days;
        
        $week_percent = $week_study_days > 0 ? round(($week_present / $week_study_days) * 100, 1) : 0;
        $week_class = $week_percent >= ($pass_criteria * 100) ? 'pass' : 'fail';
        
        $html .= '<td class="' . $week_class . '">' . $week_present . '/' . $week_study_days . '<br>(' . $week_percent . '%)</td>';
    }
    
    // สรุปรวม
    $overall_percent = $total_study_days > 0 ? round(($total_present / $total_study_days) * 100, 1) : 0;
    $overall_status = $overall_percent >= ($pass_criteria * 100) ? 'ผ่าน' : 'ไม่ผ่าน';
    $overall_class = $overall_percent >= ($pass_criteria * 100) ? 'pass' : 'fail';
    
    $html .= '
        <td><strong>' . $total_present . '</strong></td>
        <td><strong>' . $total_study_days . '</strong></td>
        <td><strong>' . $overall_percent . '%</strong></td>
        <td class="' . $overall_class . '">' . $overall_status . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// เพิ่มคำอธิบาย
$html .= '
<div class="notes">
    <p><strong>หมายเหตุ:</strong></p>
    <ul>
        <li>เกณฑ์การผ่านกิจกรรม: ' . ($pass_criteria * 100) . '% ของจำนวนวันเรียนจริง (ไม่นับวันหยุดราชการ)</li>
        <li>วันเรียนจริงต่อสัปดาห์: วันจันทร์ - ศุกร์ หักลบวันหยุดราชการ</li>';

// ระบุจำนวนสัปดาห์ตามประเภท
if (!empty($students)) {
    $level = $students[0]['level'] ?? '';
    if (strpos($level, 'ปวช') !== false) {
        $html .= '<li>หลักสูตรประกาศนียบัตรวิชาชีพ (ปวช.): 18 สัปดาห์</li>';
    } elseif (strpos($level, 'ปวส') !== false) {
        $html .= '<li>หลักสูตรประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.): 15 สัปดาห์</li>';
    }
}

$html .= '
    </ul>
    <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</p>
</div>';

// ส่งออก PDF
try {
    $mpdf->WriteHTML($html);
    
    $filename = 'รายงานประเมินผลกิจกรรม_' . date('Y-m-d_H-i-s') . '.pdf';
    $mpdf->Output($filename, 'I'); // I = Inline (แสดงในเบราว์เซอร์)
    
} catch (\Mpdf\MpdfException $e) {
    die('เกิดข้อผิดพลาดในการสร้าง PDF: ' . $e->getMessage());
}


// ฟังก์ชันช่วยเหลือ
function formatThaiDate($date_str) {
    $date = new DateTime($date_str);
    return $date->format('d/m/') . ($date->format('Y') + 543);
}
?>
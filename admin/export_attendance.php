<?php
/**
 * export_attendance.php - ส่งออกข้อมูลการเข้าแถวเป็นไฟล์ Excel
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

// นำเข้าไฟล์ PHPExcel
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ดึงข้อมูลที่ส่งมา
$class_id = $_POST['class_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$week_number = $_POST['week_number'] ?? '1';
$export_type = $_POST['export_type'] ?? 'excel';

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

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// กำหนดชื่อแผ่นงาน
$sheet->setTitle('รายงานการเข้าแถว');

// กำหนดข้อมูลหัว
$sheet->setCellValue('A1', 'วิทยาลัยการอาชีพปราสาท');
$sheet->setCellValue('A2', 'รายงานการเข้าแถวของนักเรียน นักศึกษา');
$sheet->setCellValue('A3', "ภาคเรียนที่ {$academic_year['semester']} ปีการศึกษา {$academic_year['year']} สัปดาห์ที่ {$week_number}");
$sheet->setCellValue('A4', "ระหว่างวันที่ " . date('j/n/Y', strtotime($start_date)) . " ถึง " . date('j/n/Y', strtotime($end_date)));
$sheet->setCellValue('A5', "ระดับชั้น {$class['level']} กลุ่ม {$class['group_number']} แผนกวิชา{$class['department_name']}");

// กำหนดสไตล์หัว
$sheet->getStyle('A1:A5')->getFont()->setBold(true);
$sheet->getStyle('A1:A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// กำหนดความสูงแถว
$sheet->getRowDimension(1)->setRowHeight(25);
$sheet->getRowDimension(2)->setRowHeight(25);
$sheet->getRowDimension(3)->setRowHeight(25);
$sheet->getRowDimension(4)->setRowHeight(25);
$sheet->getRowDimension(5)->setRowHeight(25);

// กำหนดหัวตาราง
$sheet->setCellValue('A7', 'ลำดับที่');
$sheet->setCellValue('B7', 'รหัสนักศึกษา');
$sheet->setCellValue('C7', 'ชื่อ-สกุล');

// กำหนดหัวคอลัมน์วันที่
$col = 'D';
foreach ($week_days as $day) {
    $sheet->setCellValue($col . '7', $day['day_name'] . ' ' . $day['day_num']);
    if ($day['is_holiday']) {
        $sheet->setCellValue($col . '8', '(หยุด)');
        $sheet->getStyle($col . '8')->getFont()->getColor()->setARGB('FF0000');
    }
    $col++;
}

$sheet->setCellValue($col . '7', 'รวม');

// กำหนดสไตล์หัวตาราง
$sheet->getStyle('A7:' . $col . '7')->getFont()->setBold(true);
$sheet->getStyle('A7:' . $col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A7:' . $col . '7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');
$sheet->getStyle('A7:' . $col . '7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// กำหนดข้อมูลนักเรียน
$row = 9;
$no = 1;
foreach ($students as $student) {
    $sheet->setCellValue('A' . $row, $no);
    $sheet->setCellValue('B' . $row, $student['student_code']);
    $sheet->setCellValue('C' . $row, $student['display_title'] . $student['first_name'] . ' ' . $student['last_name']);
    
    // กำหนดข้อมูลการเข้าแถว
    $col = 'D';
    $totalPresent = 0;
    
    foreach ($week_days as $day) {
        if ($day['is_holiday']) {
            $sheet->setCellValue($col . $row, 'หยุด');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF0000');
        } elseif (isset($attendance_data[$student['student_id']][$day['date']])) {
            $attendanceStatus = $attendance_data[$student['student_id']][$day['date']];
            
            if ($attendanceStatus == 'present') {
                $sheet->setCellValue($col . $row, '✓');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF00B050');
                $totalPresent++;
            } elseif ($attendanceStatus == 'absent') {
                $sheet->setCellValue($col . $row, 'ขาด');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFF0000');
            } elseif ($attendanceStatus == 'late') {
                $sheet->setCellValue($col . $row, 'สาย');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFF9900');
                $totalPresent++; // นับสายเป็นมาเรียน
            } elseif ($attendanceStatus == 'leave') {
                $sheet->setCellValue($col . $row, 'ลา');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF0070C0');
            }
        } else {
            $sheet->setCellValue($col . $row, '-');
        }
        
        $col++;
    }
    
    // กำหนดจำนวนรวม
    $sheet->setCellValue($col . $row, $totalPresent);
    
    // กำหนดสไตล์แถว
    $sheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D' . $row . ':' . $col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row++;
    $no++;
}

// กำหนดแถวสรุป
$sheet->setCellValue('A' . ($row + 2), "สรุป จำนวนคน {$no} คน ชาย {$male_count} คน หญิง {$female_count} คน");

// คำนวณอัตราการเข้าแถว
$totalAttendanceRate = 0;
if (count($students) > 0) {
    $totalAttendanceData = 0;
    $totalPossibleAttendance = 0;
    
    foreach ($students as $student) {
        foreach ($week_days as $day) {
            if (!$day['is_holiday']) {
                $totalPossibleAttendance++;
                if (isset($attendance_data[$student['student_id']][$day['date']])) {
                    $status = $attendance_data[$student['student_id']][$day['date']];
                    if ($status == 'present' || $status == 'late') {
                        $totalAttendanceData++;
                    }
                }
            }
        }
    }
    
    if ($totalPossibleAttendance > 0) {
        $totalAttendanceRate = ($totalAttendanceData / $totalPossibleAttendance) * 100;
    }
}

$sheet->setCellValue('A' . ($row + 3), "อัตราการเข้าแถวรวม: " . number_format($totalAttendanceRate, 2) . "%");

// กำหนดลายเซ็น
$sheet->setCellValue('B' . ($row + 5), "ลงชื่อ...........................................................");
$sheet->setCellValue('D' . ($row + 5), "ลงชื่อ...........................................................");
$sheet->setCellValue('F' . ($row + 5), "ลงชื่อ...........................................................");

if ($primary_advisor) {
    $sheet->setCellValue('B' . ($row + 6), "({$primary_advisor['title']}{$primary_advisor['first_name']} {$primary_advisor['last_name']})");
} else {
    $sheet->setCellValue('B' . ($row + 6), "(.....................................................)");
}
$sheet->setCellValue('D' . ($row + 6), "(นายนนทศรี ศรีสุข)");
$sheet->setCellValue('F' . ($row + 6), "(นายพงษ์ศักดิ์ สมใจรัก)");

$sheet->setCellValue('B' . ($row + 7), "ครูที่ปรึกษา");
$sheet->setCellValue('D' . ($row + 7), "หัวหน้างานกิจกรรมนักเรียน นักศึกษา");
$sheet->setCellValue('F' . ($row + 7), "รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา");

// กำหนดความกว้างคอลัมน์อัตโนมัติ
$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(30);

$col = 'D';
foreach ($week_days as $day) {
    $sheet->getColumnDimension($col)->setWidth(10);
    $col++;
}
$sheet->getColumnDimension($col)->setWidth(10);

// สร้างไฟล์ Excel
$writer = new Xlsx($spreadsheet);

// กำหนดชื่อไฟล์
$filename = "รายงานการเข้าแถว_{$class['level']}_{$class['group_number']}_{$class['department_name']}_สัปดาห์ที่{$week_number}.xlsx";

// ส่งไฟล์ Excel ให้ผู้ใช้ดาวน์โหลด
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
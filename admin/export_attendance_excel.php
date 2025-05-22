<?php
/**
 * export_attendance_excel.php - ส่งออกรายงานการเข้าแถวเป็น Excel
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
date_default_timezone_set('Asia/Bangkok');

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
} */

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

// นำเข้าไฟล์ PhpSpreadsheet
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ดึงข้อมูลที่ส่งมา
$class_id = $_POST['class_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$week_number = $_POST['week_number'] ?? 1;
$end_week = $_POST['end_week'] ?? $week_number;
$search = $_POST['search'] ?? '';
$search_type = $_POST['search_type'] ?? 'class';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// เตรียม query สำหรับดึงนักเรียน
if ($search_type === 'student' && !empty($search)) {
    // ค้นหาตามชื่อนักเรียน
    $query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                      CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title,
                      c.class_id, c.level, c.group_number, d.department_name
                      FROM students s 
                      JOIN users u ON s.user_id = u.user_id 
                      LEFT JOIN classes c ON s.current_class_id = c.class_id
                      LEFT JOIN departments d ON c.department_id = d.department_id
                      WHERE s.status = 'กำลังศึกษา'
                      AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
                      ORDER BY s.student_code";
    
    $search_param = "%$search%";
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$search_param, $search_param, $search_param]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ใช้ข้อมูลของนักเรียนคนแรกสำหรับข้อมูลห้องเรียน
    if (!empty($students)) {
        $first_student = $students[0];
        $class_info = [
            'level' => $first_student['level'],
            'group_number' => $first_student['group_number'],
            'department_name' => $first_student['department_name']
        ];
    } else {
        die('ไม่พบข้อมูลนักเรียนที่ค้นหา');
    }
} else {
    // ค้นหาตามห้องเรียน
    if (empty($class_id)) {
        die('กรุณาระบุห้องเรียน');
    }
    
    // ดึงข้อมูลห้องเรียน
    $query = "SELECT c.class_id, c.level, c.group_number, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        die('ไม่พบข้อมูลห้องเรียน');
    }
    
    // ดึงข้อมูลนักเรียนในห้อง
    $query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                      CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
                      FROM students s 
                      JOIN users u ON s.user_id = u.user_id 
                      WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
                      ORDER BY s.student_code";
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลวันหยุด
$query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->execute([$start_date, $end_date]);
$holidays = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $holidays[$row['holiday_date']] = $row['holiday_name'];
}

// สร้างข้อมูลวันทำการ (จันทร์-ศุกร์)
$weekdays = [];
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

while ($current_date <= $end_date_obj) {
    $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
    
    // เฉพาะวันจันทร์ถึงศุกร์ (1-5)
    if ($day_of_week >= 1 && $day_of_week <= 5) {
        $date_str = $current_date->format('Y-m-d');
        $is_holiday = isset($holidays[$date_str]);
        
        $weekdays[] = [
            'date' => $date_str,
            'day_name' => $thaiDayAbbrs[$day_of_week],
            'day_num' => $current_date->format('j'),
            'is_holiday' => $is_holiday,
            'holiday_name' => $is_holiday ? $holidays[$date_str] : null
        ];
    }
    
    $current_date->modify('+1 day');
}

// ดึงข้อมูลการเข้าแถว
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

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ตั้งค่าข้อมูลหัวเรื่อง
$sheet->setTitle('รายงานการเข้าแถว');

// ส่วนหัวรายงาน
$headerLines = [
    'งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท',
    'แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง',
    "ภาคเรียนที่ {$academic_year['semester']} ปีการศึกษา {$academic_year['year']} สัปดาห์ที่ {$week_number}" . 
    ($end_week != $week_number ? "-{$end_week}" : ""),
    "ระดับชั้น {$class_info['level']} กลุ่ม {$class_info['group_number']} แผนกวิชา{$class_info['department_name']}"
];

$currentRow = 1;
foreach ($headerLines as $line) {
    $sheet->setCellValue("A{$currentRow}", $line);
    $sheet->mergeCells("A{$currentRow}:" . chr(67 + count($weekdays)) . "{$currentRow}");
    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
    $currentRow++;
}

$currentRow++; // เว้นบรรทัด

// สร้างหัวตาราง
$sheet->setCellValue("A{$currentRow}", 'ลำดับ');
$sheet->setCellValue("B{$currentRow}", 'รหัสนักศึกษา');
$sheet->setCellValue("C{$currentRow}", 'ชื่อ-สกุล');

$col = 'D';
foreach ($weekdays as $day) {
    $dayText = $day['day_name'] . "\n" . $day['day_num'];
    if ($day['is_holiday']) {
        $dayText .= "\n(หยุด)";
    }
    $sheet->setCellValue($col . $currentRow, $dayText);
    $sheet->getStyle($col . $currentRow)->getAlignment()->setWrapText(true);
    $col++;
}

$sheet->setCellValue($col . $currentRow, 'รวม');

// จัดรูปแบบหัวตาราง
$headerRow = $currentRow;
$headerRange = "A{$headerRow}:" . $col . $headerRow;
$sheet->getStyle($headerRange)->getFont()->setBold(true);
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle($headerRange)->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('E0E0E0');

$currentRow++;

// เพิ่มข้อมูลนักเรียน
foreach ($students as $index => $student) {
    $sheet->setCellValue("A{$currentRow}", $index + 1);
    $sheet->setCellValue("B{$currentRow}", $student['student_code']);
    $sheet->setCellValue("C{$currentRow}", $student['display_title'] . $student['first_name'] . ' ' . $student['last_name']);
    
    $col = 'D';
    $totalPresent = 0;
    
    foreach ($weekdays as $day) {
        $cellValue = '-';
        $cellColor = null;
        
        if ($day['is_holiday']) {
            $cellValue = 'หยุด';
            $cellColor = 'FFFF00'; // สีเหลือง
        } elseif (isset($attendance_data[$student['student_id']][$day['date']])) {
            $status = $attendance_data[$student['student_id']][$day['date']];
            
            switch ($status) {
                case 'present':
                    $cellValue = 'มา';
                    $cellColor = '90EE90'; // สีเขียวอ่อน
                    $totalPresent++;
                    break;
                case 'absent':
                    $cellValue = 'ขาด';
                    $cellColor = 'FFB6C1'; // สีแดงอ่อน
                    break;
                case 'late':
                    $cellValue = 'สาย';
                    $cellColor = 'FFE4B5'; // สีส้มอ่อน
                    $totalPresent++;
                    break;
                case 'leave':
                    $cellValue = 'ลา';
                    $cellColor = 'ADD8E6'; // สีฟ้าอ่อน
                    break;
            }
        }
        
        $sheet->setCellValue($col . $currentRow, $cellValue);
        
        if ($cellColor) {
            $sheet->getStyle($col . $currentRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($cellColor);
        }
        
        $col++;
    }
    
    $sheet->setCellValue($col . $currentRow, $totalPresent);
    $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
    
    $currentRow++;
}

// จัดรูปแบบตาราง
$dataRange = "A{$headerRow}:" . $col . ($currentRow - 1);
$sheet->getStyle($dataRange)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

// ปรับความกว้างคอลัมน์
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);

$col = 'D';
foreach ($weekdays as $day) {
    $sheet->getColumnDimension($col)->setWidth(8);
    $col++;
}
$sheet->getColumnDimension($col)->setWidth(8);

// ปรับความสูงแถวหัวตาราง
$sheet->getRowDimension($headerRow)->setRowHeight(40);

// จัดการจัดตำแหน่งข้อความ
$sheet->getStyle("A{$headerRow}:" . $col . ($currentRow - 1))
    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C" . ($headerRow + 1) . ":C" . ($currentRow - 1))
    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// สร้างชื่อไฟล์
$search_info = $search_type === 'student' && !empty($search) ? "_ค้นหา_{$search}" : "";
$filename = "รายงานการเข้าแถว_{$class_info['level']}_{$class_info['group_number']}_{$class_info['department_name']}_สัปดาห์ที่{$week_number}";
if ($end_week != $week_number) {
    $filename .= "-{$end_week}";
}
$filename .= "{$search_info}.xlsx";

// ส่งออกไฟล์
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
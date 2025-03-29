<?php
/**
 * export_students.php - API สำหรับส่งออกข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง
/* if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
    header('Content-Type: text/plain');
    echo 'ไม่มีสิทธิ์เข้าถึง API นี้';
    exit;
} */

// เชื่อมต่อไฟล์ที่จำเป็น
require_once '../../db_connect.php';
require_once '../models/students_model.php';
// ใช้ไลบรารี PhpSpreadsheet สำหรับสร้างไฟล์ Excel
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ดึงข้อมูลนักเรียนตามตัวกรอง
$filters = [];
if (isset($_GET['name'])) {
    $filters['name'] = $_GET['name'];
}
if (isset($_GET['student_code'])) {
    $filters['student_code'] = $_GET['student_code'];
}
if (isset($_GET['level'])) {
    $filters['level'] = $_GET['level'];
}
if (isset($_GET['room'])) {
    $filters['room'] = $_GET['room'];
}
if (isset($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

$students = getAllStudents($filters);

// สร้าง spreadsheet ใหม่
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('รายชื่อนักเรียน');

// ตั้งค่าหัวตาราง
$sheet->setCellValue('A1', 'รหัสนักศึกษา');
$sheet->setCellValue('B1', 'คำนำหน้า');
$sheet->setCellValue('C1', 'ชื่อ');
$sheet->setCellValue('D1', 'นามสกุล');
$sheet->setCellValue('E1', 'ชั้น/ห้อง');
$sheet->setCellValue('F1', 'แผนกวิชา');
$sheet->setCellValue('G1', 'วันที่เข้าแถว');
$sheet->setCellValue('H1', 'วันที่ขาด');
$sheet->setCellValue('I1', 'ร้อยละการเข้าแถว');
$sheet->setCellValue('J1', 'สถานะการเข้าแถว');
$sheet->setCellValue('K1', 'เชื่อมต่อไลน์');
$sheet->setCellValue('L1', 'สถานะการศึกษา');

// ตั้งค่าสไตล์ของหัวตาราง
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];

$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// เพิ่มข้อมูลนักเรียน
$row = 2;
foreach ($students as $student) {
    $sheet->setCellValue('A' . $row, $student['student_code']);
    $sheet->setCellValue('B' . $row, $student['title']);
    $sheet->setCellValue('C' . $row, $student['first_name']);
    $sheet->setCellValue('D' . $row, $student['last_name']);
    $sheet->setCellValue('E' . $row, $student['class']);
    $sheet->setCellValue('F' . $row, $student['department_name'] ?? '');
    $sheet->setCellValue('G' . $row, $student['total_attendance_days'] ?? 0);
    $sheet->setCellValue('H' . $row, $student['total_absence_days'] ?? 0);
    $sheet->setCellValue('I' . $row, $student['attendance_rate'] ?? 0);
    $sheet->setCellValue('J' . $row, $student['attendance_status']);
    $sheet->setCellValue('K' . $row, $student['line_connected'] ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้เชื่อมต่อ');
    $sheet->setCellValue('L' . $row, $student['status']);
    
    // ตั้งค่าสีพื้นหลังตามสถานะการเข้าแถว
    $statusColor = '';
    if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
        $statusColor = 'FFC7CE'; // สีแดงอ่อน
    } elseif ($student['attendance_status'] === 'ต้องระวัง') {
        $statusColor = 'FFEB9C'; // สีเหลืองอ่อน
    } else {
        $statusColor = 'C6EFCE'; // สีเขียวอ่อน
    }
    
    $sheet->getStyle('J' . $row)->getFill()
          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setRGB($statusColor);
    
    $row++;
}

// ปรับขนาดคอลัมน์ให้พอดีกับข้อมูล
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ตั้งค่าสไตล์ของตาราง
$tableStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A1:L' . ($row - 1))->applyFromArray($tableStyle);

// ตั้งค่าการจัดตำแหน่งข้อมูล
$sheet->getStyle('A2:A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('G2:I' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('J2:L' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// สร้างชื่อไฟล์ Excel
$filename = 'รายชื่อนักเรียน_' . date('Y-m-d_H-i-s') . '.xlsx';

// ตั้งค่า headers สำหรับการดาวน์โหลด
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// ส่งออกไฟล์ Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
<?php
/**
 * export_class_performance_excel.php - ส่งออกรายงานประสิทธิภาพห้องเรียนเป็น Excel
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(403);
    exit('ไม่ได้รับอนุญาต');
}

// โหลด PhpSpreadsheet
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ดึงพารามิเตอร์
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;

// ใช้ฟังก์ชันจาก executive_reports.php
require_once 'executive_reports.php';

try {
    // ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อน
    $conn = getDB();
    if (!$conn) {
        throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    }
    
    // ดึงข้อมูลรายงาน
    $report_data = getExecutiveReportData($period, $departmentId);
    $class_performance = $report_data['class_performance'];
    $academic_year = $report_data['academic_year'];
    
    // สร้าง Spreadsheet ใหม่
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // กำหนดข้อมูลหัวรายงาน
    $current_academic_year = $academic_year['year'] + 543;
    $current_semester = $academic_year['semester'];
    
    function getThaiPeriod($period) {
        switch($period) {
            case 'day': return 'วันนี้';
            case 'week': return 'สัปดาห์นี้';
            case 'month': return 'เดือนนี้';
            case 'semester': return 'ภาคเรียนนี้';
            default: return 'ช่วงที่เลือก';
        }
    }
    
    $thaiPeriod = getThaiPeriod($period);
    $reportDate = date('d/m/') . (date('Y') + 543) . ' เวลา ' . date('H:i');
    
    // ชื่อแผนกในรายงาน
    $departmentName = 'ทุกแผนก';
    if ($departmentId) {
        $deptQuery = "SELECT department_name FROM departments WHERE department_id = ?";
        $conn = getDB();
        $stmt = $conn->prepare($deptQuery);
        $stmt->execute([$departmentId]);
        $deptResult = $stmt->fetchColumn();
        if ($deptResult) {
            $departmentName = $deptResult;
        }
    }
    
    // ตั้งค่าหัวข้อรายงาน
    $sheet->setCellValue('A1', 'รายงานประสิทธิภาพห้องเรียน');
    $sheet->setCellValue('A2', 'วิทยาลัยการอาชีพปราสาท');
    $sheet->setCellValue('A3', 'ปีการศึกษา ' . $current_academic_year . ' ภาคเรียนที่ ' . $current_semester);
    $sheet->setCellValue('A4', 'แผนกวิชา: ' . $departmentName);
    $sheet->setCellValue('A5', 'ช่วงเวลา: ' . $thaiPeriod);
    $sheet->setCellValue('A6', 'วันที่ออกรายงาน: ' . $reportDate);
    
    // จัดรูปแบบหัวข้อ
    $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A2:G2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A3:G6')->getFont()->setBold(true)->setSize(12);
    
    // ผสานเซลล์หัวข้อ
    $sheet->mergeCells('A1:G1');
    $sheet->mergeCells('A2:G2');
    $sheet->mergeCells('A3:G3');
    $sheet->mergeCells('A4:G4');
    $sheet->mergeCells('A5:G5');
    $sheet->mergeCells('A6:G6');
    
    // จัดให้อยู่กลาง
    $sheet->getStyle('A1:G6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // สร้างหัวข้อตาราง (เริ่มที่แถว 8)
    $headerRow = 8;
    $headers = [
        'A' => 'ลำดับ',
        'B' => 'ชั้นเรียน',
        'C' => 'แผนกวิชา',
        'D' => 'จำนวนนักเรียน',
        'E' => 'อัตราการเข้าแถว (%)',
        'F' => 'นักเรียนเสี่ยง (คน)',
        'G' => 'ระดับประสิทธิภาพ'
    ];
    
    foreach ($headers as $col => $header) {
        $sheet->setCellValue($col . $headerRow, $header);
    }
    
    // จัดรูปแบบหัวข้อตาราง
    $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // เติมข้อมูล
    $row = $headerRow + 1;
    $number = 1;
    
    foreach ($class_performance as $class) {
        $sheet->setCellValue('A' . $row, $number);
        $sheet->setCellValue('B' . $row, $class['class_name']);
        $sheet->setCellValue('C' . $row, $class['department_name']);
        $sheet->setCellValue('D' . $row, $class['total_students']);
        $sheet->setCellValue('E' . $row, $class['avg_attendance_rate']);
        $sheet->setCellValue('F' . $row, $class['risk_count']);
        $sheet->setCellValue('G' . $row, $class['level_text']);
        
        // กำหนดสีตามระดับประสิทธิภาพ
        $fillColor = 'FFFFFF'; // สีขาวเป็นค่าเริ่มต้น
        switch ($class['performance_level']) {
            case 'excellent':
                $fillColor = 'C6EFCE'; // เขียวอ่อน
                break;
            case 'good':
                $fillColor = 'FFEB9C'; // เหลืองอ่อน
                break;
            case 'average':
                $fillColor = 'FFD1DC'; // ชมพูอ่อน
                break;
            case 'poor':
                $fillColor = 'FFC7CE'; // แดงอ่อน
                break;
        }
        
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        
        $row++;
        $number++;
    }
    
    // เพิ่มสรุปข้อมูลท้ายตาราง
    $summaryRow = $row + 2;
    $sheet->setCellValue('A' . $summaryRow, 'สรุปข้อมูล');
    $sheet->mergeCells('A' . $summaryRow . ':G' . $summaryRow);
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $summaryRow++;
    
    // คำนวณสถิติ
    $totalClasses = count($class_performance);
    $totalStudents = array_sum(array_column($class_performance, 'total_students'));
    $avgAttendanceRate = $totalStudents > 0 ? 
        array_sum(array_map(function($class) { 
            return $class['avg_attendance_rate'] * $class['total_students']; 
        }, $class_performance)) / $totalStudents : 0;
    $totalRiskStudents = array_sum(array_column($class_performance, 'risk_count'));
    
    $excellentClasses = count(array_filter($class_performance, function($class) { 
        return $class['performance_level'] == 'excellent'; 
    }));
    $goodClasses = count(array_filter($class_performance, function($class) { 
        return $class['performance_level'] == 'good'; 
    }));
    $averageClasses = count(array_filter($class_performance, function($class) { 
        return $class['performance_level'] == 'average'; 
    }));
    $poorClasses = count(array_filter($class_performance, function($class) { 
        return $class['performance_level'] == 'poor'; 
    }));
    
    $summaryData = [
        ['ข้อมูล', 'จำนวน'],
        ['จำนวนห้องเรียนทั้งหมด', $totalClasses . ' ห้อง'],
        ['จำนวนนักเรียนทั้งหมด', number_format($totalStudents) . ' คน'],
        ['อัตราการเข้าแถวเฉลี่ย', number_format($avgAttendanceRate, 1) . '%'],
        ['จำนวนนักเรียนเสี่ยงทั้งหมด', number_format($totalRiskStudents) . ' คน'],
        ['', ''],
        ['ระดับประสิทธิภาพ', 'จำนวนห้อง'],
        ['ดีเยี่ยม', $excellentClasses . ' ห้อง'],
        ['ดี', $goodClasses . ' ห้อง'],
        ['ปานกลาง', $averageClasses . ' ห้อง'],
        ['ต้องปรับปรุง', $poorClasses . ' ห้อง']
    ];
    
    foreach ($summaryData as $summaryItem) {
        $sheet->setCellValue('B' . $summaryRow, $summaryItem[0]);
        $sheet->setCellValue('C' . $summaryRow, $summaryItem[1]);
        
        if ($summaryItem[0] == 'ข้อมูล' || $summaryItem[0] == 'ระดับประสิทธิภาพ') {
            $sheet->getStyle('B' . $summaryRow . ':C' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $summaryRow . ':C' . $summaryRow)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        } else {
            $sheet->getStyle('B' . $summaryRow . ':C' . $summaryRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
        }
        
        $summaryRow++;
    }
    
    // ปรับความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(25);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(18);
    $sheet->getColumnDimension('G')->setWidth(20);
    
    // กำหนดชื่อไฟล์
    $fileName = 'รายงานประสิทธิภาพห้องเรียน_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // ส่งไฟล์ให้ดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}
?>
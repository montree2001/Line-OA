<?php
/**
 * download_report.php - API สำหรับดาวน์โหลดรายงานในรูปแบบต่างๆ
 * 
 * ส่วนหนึ่งของระบบน้องสัตบรรณ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access');
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ตรวจสอบพารามิเตอร์
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$reportType = isset($_GET['type']) ? $_GET['type'] : 'attendance';

// ดึงข้อมูลรายงาน
try {
    // ดึงปีการศึกษาปัจจุบัน
    $conn = getDB();
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academicYear) {
        throw new Exception('No active academic year found');
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // สร้างชื่อไฟล์
    $filename = 'รายงาน_' . $reportType . '_' . date('Y-m-d');
    
    // กำหนดช่วงเวลาตามประเภทรายงาน
    $dateFilter = '';
    $dateParams = [];
    
    switch ($period) {
        case 'day':
            $dateFilter = " AND a.date = ?";
            $dateParams[] = date('Y-m-d');
            $filename .= '_วันนี้';
            break;
            
        case 'week':
            $dateFilter = " AND a.date BETWEEN ? AND ?";
            $dateParams[] = date('Y-m-d', strtotime('monday this week'));
            $dateParams[] = date('Y-m-d', strtotime('sunday this week'));
            $filename .= '_สัปดาห์นี้';
            break;
            
        case 'month':
            $dateFilter = " AND a.date BETWEEN ? AND ?";
            $dateParams[] = date('Y-m-01');
            $dateParams[] = date('Y-m-t');
            $filename .= '_เดือนนี้';
            break;
            
        case 'semester':
            $dateFilter = " AND a.date BETWEEN ? AND ?";
            $dateParams[] = $academicYear['start_date'] ?? date('Y-m-d', strtotime('-3 months'));
            $dateParams[] = $academicYear['end_date'] ?? date('Y-m-d');
            $filename .= '_ภาคเรียน' . $academicYear['semester'] . '_' . ($academicYear['year'] + 543);
            break;
            
        case 'custom':
            if ($startDate && $endDate) {
                $dateFilter = " AND a.date BETWEEN ? AND ?";
                $dateParams[] = $startDate;
                $dateParams[] = $endDate;
                $filename .= '_' . date('d-m-Y', strtotime($startDate)) . '_ถึง_' . date('d-m-Y', strtotime($endDate));
            }
            break;
    }
    
    // กำหนดเงื่อนไขแผนก
    $departmentFilter = '';
    $departmentParams = [];
    
    if ($departmentId) {
        // ดึงชื่อแผนก
        $query = "SELECT department_name FROM departments WHERE department_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$departmentId]);
        $departmentName = $stmt->fetchColumn();
        
        if ($departmentName) {
            $departmentFilter = " AND c.department_id = ?";
            $departmentParams[] = $departmentId;
            $filename .= '_' . $departmentName;
        }
    }
    
    // ประมวลผลตามรูปแบบไฟล์
    switch ($format) {
        case 'excel':
            generateExcelReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType);
            break;
            
        case 'pdf':
            generatePdfReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType);
            break;
            
        case 'csv':
            generateCsvReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType);
            break;
            
        default:
            throw new Exception('Unsupported format: ' . $format);
    }
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error generating report: ' . $e->getMessage());
}

/**
 * สร้างรายงานในรูปแบบ Excel
 */
function generateExcelReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType) {
    // ต้องติดตั้ง PhpSpreadsheet ก่อน
    // composer require phpoffice/phpspreadsheet
    
    // ตรวจสอบว่ามีการติดตั้ง PhpSpreadsheet หรือไม่
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // ถ้าไม่มี ให้สร้างไฟล์ CSV แทน
        generateCsvReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType);
        return;
    }
    
    // ใช้ PhpSpreadsheet
    require 'vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    
    // สร้าง Spreadsheet ใหม่
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // ตั้งค่า header ของไฟล์
    $sheet->setCellValue('A1', 'วิทยาลัยการอาชีพปราสาท');
    $sheet->setCellValue('A2', 'รายงาน' . getReportTypeName($reportType));
    
    // ตั้งค่าสไตล์
    $headerStyle = [
        'font' => [
            'bold' => true,
            'size' => 14
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ];
    
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    $sheet->getStyle('A2:F2')->applyFromArray($headerStyle);
    
    // ขึ้นอยู่กับประเภทรายงาน
    switch ($reportType) {
        case 'attendance':
            generateAttendanceExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        case 'risk':
            generateRiskExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        case 'class':
            generateClassExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        default:
            generateAttendanceExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
    }
    
    // สร้างไฟล์ Excel
    $writer = new Xlsx($spreadsheet);
    
    // ตั้งค่า header ของ HTTP response
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // ส่งไฟล์ไปยังเบราว์เซอร์
    $writer->save('php://output');
    exit;
}

/**
 * สร้างรายงานในรูปแบบ PDF
 */
function generatePdfReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType) {
    // ต้องติดตั้ง TCPDF หรือ mPDF ก่อน
    // composer require mpdf/mpdf
    
    // ตรวจสอบว่ามีการติดตั้ง mPDF หรือไม่
    if (!class_exists('Mpdf\Mpdf')) {
        // ถ้าไม่มี ให้แจ้งเตือน
        header('HTTP/1.1 500 Internal Server Error');
        exit('mPDF library is not installed. Please install it using: composer require mpdf/mpdf');
    }
    
    // ใช้ mPDF
    require_once 'vendor/autoload.php';
    
    // สร้าง mPDF object
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);
    
    // ตั้งค่า metadata
    $mpdf->SetTitle($filename);
    $mpdf->SetAuthor('วิทยาลัยการอาชีพปราสาท');
    $mpdf->SetCreator('ระบบน้องสัตบรรณ - ดูแลผู้เรียน');
    
    // สร้าง HTML content
    $html = generateReportHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType);
    
    // เพิ่ม CSS
    $stylesheet = file_get_contents('../admin/assets/css/reports.css');
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    
    // เขียน HTML
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    // ส่ง PDF ไปยังเบราว์เซอร์
    $mpdf->Output($filename . '.pdf', 'D');
    exit;
}

/**
 * สร้างรายงานในรูปแบบ CSV
 */
function generateCsvReport($conn, $academicYearId, $filename, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType) {
    // สร้างไฟล์ CSV
    $output = fopen('php://output', 'w');
    
    // ตั้งค่า header ของ HTTP response
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
    
    // เพิ่ม BOM เพื่อให้ Excel อ่านภาษาไทยได้
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // ขึ้นอยู่กับประเภทรายงาน
    switch ($reportType) {
        case 'attendance':
            generateAttendanceCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        case 'risk':
            generateRiskCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        case 'class':
            generateClassCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            break;
            
        default:
            generateAttendanceCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
    }
    
    // ปิดไฟล์
    fclose($output);
    exit;
}

/**
 * สร้าง HTML สำหรับรายงาน PDF
 */
function generateReportHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams, $reportType) {
    // ขึ้นอยู่กับประเภทรายงาน
    switch ($reportType) {
        case 'attendance':
            return generateAttendanceHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            
        case 'risk':
            return generateRiskHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            
        case 'class':
            return generateClassHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
            
        default:
            return generateAttendanceHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams);
    }
}

/**
 * สร้างรายงานการเข้าแถวในรูปแบบ Excel
 */
function generateAttendanceExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // ตั้งค่าหัวตาราง
    $sheet->setCellValue('A4', 'ลำดับ');
    $sheet->setCellValue('B4', 'รหัสนักเรียน');
    $sheet->setCellValue('C4', 'ชื่อ-นามสกุล');
    $sheet->setCellValue('D4', 'ชั้นเรียน');
    $sheet->setCellValue('E4', 'จำนวนวันมา');
    $sheet->setCellValue('F4', 'จำนวนวันขาด');
    $sheet->setCellValue('G4', 'จำนวนวันสาย');
    $sheet->setCellValue('H4', 'จำนวนวันลา');
    $sheet->setCellValue('I4', 'อัตราการเข้าแถว');
    $sheet->setCellValue('J4', 'สถานะ');
    
    // ตั้งค่าสไตล์หัวตาราง
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'E1F5FE',
            ],
        ],
    ];
    
    $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);
    
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            ORDER BY c.level, c.group_number, s.student_code";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน Excel
    $row = 5;
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_late'] + $student['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_late'] + $student['days_leave']) / $totalDays * 100 : 0;
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, $student['student_code']);
        $sheet->setCellValue('C' . $row, $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']);
        $sheet->setCellValue('D' . $row, $student['class_name']);
        $sheet->setCellValue('E' . $row, $student['days_present']);
        $sheet->setCellValue('F' . $row, $student['days_absent']);
        $sheet->setCellValue('G' . $row, $student['days_late']);
        $sheet->setCellValue('H' . $row, $student['days_leave']);
        $sheet->setCellValue('I' . $row, round($attendanceRate, 1) . '%');
        
        // กำหนดสถานะ
        if ($attendanceRate >= 80) {
            $sheet->setCellValue('J' . $row, 'ปกติ');
        } elseif ($attendanceRate >= 70) {
            $sheet->setCellValue('J' . $row, 'เสี่ยงตกกิจกรรม');
        } else {
            $sheet->setCellValue('J' . $row, 'ตกกิจกรรม');
        }
        
        $row++;
        $counter++;
    }
    
    // ปรับความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(15);
    $sheet->getColumnDimension('J')->setWidth(20);
    
    // ตั้งค่าสไตล์ของข้อมูล
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    
    $sheet->getStyle('A5:J' . ($row - 1))->applyFromArray($dataStyle);
    
    // จัดตำแหน่งข้อมูล
    $sheet->getStyle('A5:A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B5:B' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D5:J' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
}

/**
 * สร้างรายงานนักเรียนเสี่ยงในรูปแบบ Excel
 */
function generateRiskExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // ตั้งค่าหัวตาราง
    $sheet->setCellValue('A4', 'ลำดับ');
    $sheet->setCellValue('B4', 'รหัสนักเรียน');
    $sheet->setCellValue('C4', 'ชื่อ-นามสกุล');
    $sheet->setCellValue('D4', 'ชั้นเรียน');
    $sheet->setCellValue('E4', 'ครูที่ปรึกษา');
    $sheet->setCellValue('F4', 'อัตราการเข้าแถว');
    $sheet->setCellValue('G4', 'วันที่มา');
    $sheet->setCellValue('H4', 'วันที่ขาด');
    $sheet->setCellValue('I4', 'สถานะ');
    
    // ตั้งค่าสไตล์หัวตาราง
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'FFF3E0',
            ],
        ],
    ];
    
    $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);
    
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status IN ('late', 'leave') $dateFilter) as days_other
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            HAVING 
                (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100 <= 80
            ORDER BY (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน Excel
    $row = 5;
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_other'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_other']) / $totalDays * 100 : 0;
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, $student['student_code']);
        $sheet->setCellValue('C' . $row, $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']);
        $sheet->setCellValue('D' . $row, $student['class_name']);
        $sheet->setCellValue('E' . $row, $student['advisor_name'] ?? '-');
        $sheet->setCellValue('F' . $row, round($attendanceRate, 1) . '%');
        $sheet->setCellValue('G' . $row, $student['days_present']);
        $sheet->setCellValue('H' . $row, $student['days_absent']);
        
        // กำหนดสถานะ
        if ($attendanceRate >= 70) {
            $sheet->setCellValue('I' . $row, 'เสี่ยงตกกิจกรรม');
        } else {
            $sheet->setCellValue('I' . $row, 'ตกกิจกรรม');
        }
        
        $row++;
        $counter++;
    }
    
    // ปรับความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(30);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(20);
    
    // ตั้งค่าสไตล์ของข้อมูล
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    
    $sheet->getStyle('A5:I' . ($row - 1))->applyFromArray($dataStyle);
    
    // จัดตำแหน่งข้อมูล
    $sheet->getStyle('A5:A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B5:B' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D5:I' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
}

/**
 * สร้างรายงานชั้นเรียนในรูปแบบ Excel
 */
function generateClassExcelReport($conn, $sheet, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // ตั้งค่าหัวตาราง
    $sheet->setCellValue('A4', 'ลำดับ');
    $sheet->setCellValue('B4', 'ชั้นเรียน');
    $sheet->setCellValue('C4', 'แผนกวิชา');
    $sheet->setCellValue('D4', 'ครูที่ปรึกษา');
    $sheet->setCellValue('E4', 'จำนวนนักเรียน');
    $sheet->setCellValue('F4', 'จำนวนมา');
    $sheet->setCellValue('G4', 'จำนวนขาด');
    $sheet->setCellValue('H4', 'จำนวนสาย');
    $sheet->setCellValue('I4', 'จำนวนลา');
    $sheet->setCellValue('J4', 'อัตราการเข้าแถว');
    
    // ตั้งค่าสไตล์หัวตาราง
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'E8F5E9',
            ],
        ],
    ];
    
    $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);
    
    // สร้าง query
    $sql = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                d.department_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM students s 
                 WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') as student_count,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.academic_year_id = ? AND c.is_active = 1 $departmentFilter
            ORDER BY d.department_name, c.level, c.group_number";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId],
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน Excel
    $row = 5;
    $counter = 1;
    
    foreach ($classes as $class) {
        $totalDays = $class['days_present'] + $class['days_absent'] + $class['days_late'] + $class['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($class['days_present'] + $class['days_late'] + $class['days_leave']) / $totalDays * 100 : 0;
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, $class['class_name']);
        $sheet->setCellValue('C' . $row, $class['department_name']);
        $sheet->setCellValue('D' . $row, $class['advisor_name'] ?? '-');
        $sheet->setCellValue('E' . $row, $class['student_count']);
        $sheet->setCellValue('F' . $row, $class['days_present']);
        $sheet->setCellValue('G' . $row, $class['days_absent']);
        $sheet->setCellValue('H' . $row, $class['days_late']);
        $sheet->setCellValue('I' . $row, $class['days_leave']);
        $sheet->setCellValue('J' . $row, round($attendanceRate, 1) . '%');
        
        $row++;
        $counter++;
    }
    
    // ปรับความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(25);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(15);
    $sheet->getColumnDimension('J')->setWidth(15);
    
    // ตั้งค่าสไตล์ของข้อมูล
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    
    $sheet->getStyle('A5:J' . ($row - 1))->applyFromArray($dataStyle);
    
    // จัดตำแหน่งข้อมูล
    $sheet->getStyle('A5:J' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C5:D' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
}

/**
 * สร้างรายงานการเข้าแถวในรูปแบบ CSV
 */
function generateAttendanceCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // เขียนหัวตาราง
    fputcsv($output, [
        'ลำดับ',
        'รหัสนักเรียน',
        'ชื่อ-นามสกุล',
        'ชั้นเรียน',
        'จำนวนวันมา',
        'จำนวนวันขาด',
        'จำนวนวันสาย',
        'จำนวนวันลา',
        'อัตราการเข้าแถว',
        'สถานะ'
    ]);
    
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            ORDER BY c.level, c.group_number, s.student_code";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน CSV
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_late'] + $student['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_late'] + $student['days_leave']) / $totalDays * 100 : 0;
        
        // กำหนดสถานะ
        if ($attendanceRate >= 80) {
            $status = 'ปกติ';
        } elseif ($attendanceRate >= 70) {
            $status = 'เสี่ยงตกกิจกรรม';
        } else {
            $status = 'ตกกิจกรรม';
        }
        
        fputcsv($output, [
            $counter,
            $student['student_code'],
            $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'],
            $student['class_name'],
            $student['days_present'],
            $student['days_absent'],
            $student['days_late'],
            $student['days_leave'],
            round($attendanceRate, 1) . '%',
            $status
        ]);
        
        $counter++;
    }
}

/**
 * สร้างรายงานนักเรียนเสี่ยงในรูปแบบ CSV
 */
function generateRiskCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // เขียนหัวตาราง
    fputcsv($output, [
        'ลำดับ',
        'รหัสนักเรียน',
        'ชื่อ-นามสกุล',
        'ชั้นเรียน',
        'ครูที่ปรึกษา',
        'อัตราการเข้าแถว',
        'วันที่มา',
        'วันที่ขาด',
        'สถานะ'
    ]);
    
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status IN ('late', 'leave') $dateFilter) as days_other
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            HAVING 
                (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100 <= 80
            ORDER BY (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน CSV
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_other'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_other']) / $totalDays * 100 : 0;
        
        // กำหนดสถานะ
        if ($attendanceRate >= 70) {
            $status = 'เสี่ยงตกกิจกรรม';
        } else {
            $status = 'ตกกิจกรรม';
        }
        
        fputcsv($output, [
            $counter,
            $student['student_code'],
            $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'],
            $student['class_name'],
            $student['advisor_name'] ?? '-',
            round($attendanceRate, 1) . '%',
            $student['days_present'],
            $student['days_absent'],
            $status
        ]);
        
        $counter++;
    }
}

/**
 * สร้างรายงานชั้นเรียนในรูปแบบ CSV
 */
function generateClassCsvReport($conn, $output, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // เขียนหัวตาราง
    fputcsv($output, [
        'ลำดับ',
        'ชั้นเรียน',
        'แผนกวิชา',
        'ครูที่ปรึกษา',
        'จำนวนนักเรียน',
        'จำนวนมา',
        'จำนวนขาด',
        'จำนวนสาย',
        'จำนวนลา',
        'อัตราการเข้าแถว'
    ]);
    
    // สร้าง query
    $sql = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                d.department_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM students s 
                 WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') as student_count,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.academic_year_id = ? AND c.is_active = 1 $departmentFilter
            ORDER BY d.department_name, c.level, c.group_number";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId],
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน CSV
    $counter = 1;
    
    foreach ($classes as $class) {
        $totalDays = $class['days_present'] + $class['days_absent'] + $class['days_late'] + $class['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($class['days_present'] + $class['days_late'] + $class['days_leave']) / $totalDays * 100 : 0;
        
        fputcsv($output, [
            $counter,
            $class['class_name'],
            $class['department_name'],
            $class['advisor_name'] ?? '-',
            $class['student_count'],
            $class['days_present'],
            $class['days_absent'],
            $class['days_late'],
            $class['days_leave'],
            round($attendanceRate, 1) . '%'
        ]);
        
        $counter++;
    }
}

/**
 * สร้าง HTML สำหรับรายงานการเข้าแถว
 */
function generateAttendanceHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // สร้าง HTML header
    $html = '
    <div class="print-header">
        <img src="../admin/assets/img/logo.png" alt="Logo">
        <div class="print-header-text">
            <h1>วิทยาลัยการอาชีพปราสาท</h1>
            <p>รายงานการเข้าแถว</p>
        </div>
    </div>
    
    <table class="data-table" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รหัสนักเรียน</th>
                <th>ชื่อ-นามสกุล</th>
                <th>ชั้นเรียน</th>
                <th>มา</th>
                <th>ขาด</th>
                <th>สาย</th>
                <th>ลา</th>
                <th>อัตรา</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>';
    
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            ORDER BY c.level, c.group_number, s.student_code";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน HTML
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_late'] + $student['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_late'] + $student['days_leave']) / $totalDays * 100 : 0;
        
        // กำหนดสถานะและสี
        if ($attendanceRate >= 80) {
            $statusClass = 'success';
            $status = 'ปกติ';
        } elseif ($attendanceRate >= 70) {
            $statusClass = 'warning';
            $status = 'เสี่ยง';
        } else {
            $statusClass = 'danger';
            $status = 'ตกกิจกรรม';
        }
        
        $html .= "
            <tr>
                <td style='text-align: center;'>{$counter}</td>
                <td style='text-align: center;'>{$student['student_code']}</td>
                <td>{$student['title']} {$student['first_name']} {$student['last_name']}</td>
                <td style='text-align: center;'>{$student['class_name']}</td>
                <td style='text-align: center;'>{$student['days_present']}</td>
                <td style='text-align: center;'>{$student['days_absent']}</td>
                <td style='text-align: center;'>{$student['days_late']}</td>
                <td style='text-align: center;'>{$student['days_leave']}</td>
                <td style='text-align: center;'>" . round($attendanceRate, 1) . "%</td>
                <td style='text-align: center;' class='{$statusClass}'>{$status}</td>
            </tr>";
        
        $counter++;
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div class="print-footer">
        <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</p>
        <p>ระบบน้องสัตบรรณ - ดูแลผู้เรียน</p>
    </div>';
    
    return $html;
}

/**
 * สร้าง HTML สำหรับรายงานนักเรียนเสี่ยง
 */
function generateRiskHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // สร้าง HTML header
    $html = '
    <div class="print-header">
        <img src="../admin/assets/img/logo.png" alt="Logo">
        <div class="print-header-text">
            <h1>วิทยาลัยการอาชีพปราสาท</h1>
            <p>รายงานนักเรียนที่มีความเสี่ยงตกกิจกรรม</p>
        </div>
    </div>
    
    <table class="data-table" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รหัสนักเรียน</th>
                <th>ชื่อ-นามสกุล</th>
                <th>ชั้นเรียน</th>
                <th>ครูที่ปรึกษา</th>
                <th>อัตราการเข้าแถว</th>
                <th>วันที่มา</th>
                <th>วันที่ขาด</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>';
    
    // คล้ายกับฟังก์ชัน generateRiskExcelReport
    // สร้าง query
    $sql = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 WHERE a.student_id = s.student_id AND a.academic_year_id = ? 
                 AND a.attendance_status IN ('late', 'leave') $dateFilter) as days_other
                
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.status = 'กำลังศึกษา' $departmentFilter
            HAVING 
                (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100 <= 80
            ORDER BY (days_present + days_other) / NULLIF(days_present + days_absent + days_other, 0) * 100";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน HTML
    $counter = 1;
    
    foreach ($students as $student) {
        $totalDays = $student['days_present'] + $student['days_absent'] + $student['days_other'];
        $attendanceRate = ($totalDays > 0) ? ($student['days_present'] + $student['days_other']) / $totalDays * 100 : 0;
        
        // กำหนดสถานะและสี
        if ($attendanceRate >= 70) {
            $statusClass = 'warning';
            $status = 'เสี่ยงตกกิจกรรม';
        } else {
            $statusClass = 'danger';
            $status = 'ตกกิจกรรม';
        }
        
        $html .= "
            <tr>
                <td style='text-align: center;'>{$counter}</td>
                <td style='text-align: center;'>{$student['student_code']}</td>
                <td>{$student['title']} {$student['first_name']} {$student['last_name']}</td>
                <td style='text-align: center;'>{$student['class_name']}</td>
                <td>" . ($student['advisor_name'] ?? '-') . "</td>
                <td style='text-align: center;'>" . round($attendanceRate, 1) . "%</td>
                <td style='text-align: center;'>{$student['days_present']}</td>
                <td style='text-align: center;'>{$student['days_absent']}</td>
                <td style='text-align: center;' class='{$statusClass}'>{$status}</td>
            </tr>";
        
        $counter++;
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div class="print-footer">
        <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</p>
        <p>ระบบน้องสัตบรรณ - ดูแลผู้เรียน</p>
    </div>';
    
    return $html;
}

/**
 * สร้าง HTML สำหรับรายงานชั้นเรียน
 */
function generateClassHtml($conn, $academicYearId, $dateFilter, $dateParams, $departmentFilter, $departmentParams) {
    // สร้าง HTML header
    $html = '
    <div class="print-header">
        <img src="../admin/assets/img/logo.png" alt="Logo">
        <div class="print-header-text">
            <h1>วิทยาลัยการอาชีพปราสาท</h1>
            <p>รายงานการเข้าแถวตามชั้นเรียน</p>
        </div>
    </div>
    
    <table class="data-table" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชั้นเรียน</th>
                <th>แผนกวิชา</th>
                <th>ครูที่ปรึกษา</th>
                <th>จำนวนนักเรียน</th>
                <th>จำนวนมา</th>
                <th>จำนวนขาด</th>
                <th>จำนวนสาย</th>
                <th>จำนวนลา</th>
                <th>อัตราการเข้าแถว</th>
            </tr>
        </thead>
        <tbody>';
    
    // คล้ายกับฟังก์ชัน generateClassExcelReport
    // สร้าง query
    $sql = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                d.department_name,
                
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                
                (SELECT COUNT(*) FROM students s 
                 WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') as student_count,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'present' $dateFilter) as days_present,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'absent' $dateFilter) as days_absent,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'late' $dateFilter) as days_late,
                
                (SELECT COUNT(*) FROM attendance a 
                 JOIN students s ON a.student_id = s.student_id
                 WHERE s.current_class_id = c.class_id 
                 AND a.academic_year_id = ? 
                 AND a.attendance_status = 'leave' $dateFilter) as days_leave
                
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.academic_year_id = ? AND c.is_active = 1 $departmentFilter
            ORDER BY d.department_name, c.level, c.group_number";
    
    // เตรียมพารามิเตอร์
    $params = array_merge(
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId], $dateParams,
        [$academicYearId],
        $departmentParams
    );
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เขียนข้อมูลลงใน HTML
    $counter = 1;
    
    foreach ($classes as $class) {
        $totalDays = $class['days_present'] + $class['days_absent'] + $class['days_late'] + $class['days_leave'];
        $attendanceRate = ($totalDays > 0) ? ($class['days_present'] + $class['days_late'] + $class['days_leave']) / $totalDays * 100 : 0;
        
        $html .= "
            <tr>
                <td style='text-align: center;'>{$counter}</td>
                <td style='text-align: center;'>{$class['class_name']}</td>
                <td>{$class['department_name']}</td>
                <td>" . ($class['advisor_name'] ?? '-') . "</td>
                <td style='text-align: center;'>{$class['student_count']}</td>
                <td style='text-align: center;'>{$class['days_present']}</td>
                <td style='text-align: center;'>{$class['days_absent']}</td>
                <td style='text-align: center;'>{$class['days_late']}</td>
                <td style='text-align: center;'>{$class['days_leave']}</td>
                <td style='text-align: center;'>" . round($attendanceRate, 1) . "%</td>
            </tr>";
        
        $counter++;
    }
    
    $html .= '
        </tbody>
    </table>
    
    <div class="print-footer">
        <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i:s') . '</p>
        <p>ระบบน้องสัตบรรณ - ดูแลผู้เรียน</p>
    </div>';
    
    return $html;
}

/**
 * แปลงประเภทรายงานเป็นชื่อภาษาไทย
 */
function getReportTypeName($reportType) {
    switch ($reportType) {
        case 'attendance':
            return 'การเข้าแถว';
        case 'risk':
            return 'นักเรียนที่มีความเสี่ยงตกกิจกรรม';
        case 'class':
            return 'การเข้าแถวตามชั้นเรียน';
        default:
            return 'การเข้าแถว';
    }
}
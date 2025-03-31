<?php
/**
 * api/export_students.php - API สำหรับส่งออกข้อมูลนักเรียนเป็นไฟล์ Excel
 */

// ตั้งค่า header เพื่อดาวน์โหลดไฟล์
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="student_data_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// เรียกใช้ PhpSpreadsheet
// ปรับเส้นทางให้ถูกต้องตามโครงสร้างโฟลเดอร์ของคุณ
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     http_response_code(403);
//     exit("ไม่มีสิทธิ์เข้าถึง API นี้");
// }

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // สร้าง query พื้นฐาน
    $query = "
        SELECT 
            s.student_id,
            s.student_code,
            u.title,
            u.first_name,
            u.last_name,
            u.phone_number,
            u.email,
            c.level,
            c.group_number,
            d.department_name,
            s.status,
            COALESCE(sar.total_attendance_days, 0) as attendance_days,
            COALESCE(sar.total_absence_days, 0) as absence_days
        FROM 
            students s
        JOIN 
            users u ON s.user_id = u.user_id
        LEFT JOIN 
            classes c ON s.current_class_id = c.class_id
        LEFT JOIN 
            departments d ON c.department_id = d.department_id
        LEFT JOIN 
            student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = (
                SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
            )
        WHERE 1=1
    ";
    
    // เพิ่มเงื่อนไขการค้นหา
    $params = [];
    
    // ตรวจสอบพารามิเตอร์ต่างๆ
    if (isset($_GET['name']) && !empty($_GET['name'])) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchName = "%" . $_GET['name'] . "%";
        $params[] = $searchName;
        $params[] = $searchName;
    }
    
    if (isset($_GET['student_code']) && !empty($_GET['student_code'])) {
        $query .= " AND s.student_code LIKE ?";
        $params[] = "%" . $_GET['student_code'] . "%";
    }
    
    if (isset($_GET['level']) && !empty($_GET['level'])) {
        $query .= " AND c.level = ?";
        $params[] = $_GET['level'];
    }
    
    if (isset($_GET['group_number']) && !empty($_GET['group_number'])) {
        $query .= " AND c.group_number = ?";
        $params[] = $_GET['group_number'];
    }
    
    if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
        $query .= " AND c.department_id = ?";
        $params[] = $_GET['department_id'];
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $query .= " AND s.status = ?";
        $params[] = $_GET['status'];
    }
    
    // เพิ่มการจัดเรียง
    $query .= " ORDER BY s.student_code ASC";
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้าง Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('รายชื่อนักเรียน');
    
    // เพิ่มชื่อโรงเรียนและวันที่
    $sheet->setCellValue('A1', 'รายชื่อนักเรียน');
    $sheet->setCellValue('A2', 'วิทยาลัยการอาชีพปราสาท');
    $sheet->setCellValue('A3', 'วันที่พิมพ์: ' . date('d/m/Y'));
    
    // จัดรูปแบบหัวข้อ
    $sheet->getStyle('A1:A3')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setSize(16);
    
    // ตั้งค่าหัวตาราง
    $headers = [
        'ลำดับ', 'รหัสนักศึกษา', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 
        'ระดับชั้น', 'กลุ่ม', 'แผนกวิชา', 'เบอร์โทรศัพท์', 'อีเมล',
        'สถานะ', 'จำนวนวันที่มา', 'จำนวนวันที่ขาด', 'อัตราการเข้าแถว (%)'
    ];
    
    // ใส่หัวตาราง
    $col = 'A';
    $row = 5;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    
    // จัดรูปแบบหัวตาราง
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4285F4'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    
    $sheet->getStyle('A5:N5')->applyFromArray($headerStyle);
    
    // ใส่ข้อมูล
    $row = 6;
    $index = 1;
    foreach ($students as $student) {
        $col = 'A';
        
        // คำนวณอัตราการเข้าแถว
        $total_days = $student['attendance_days'] + $student['absence_days'];
        $attendance_rate = $total_days > 0 ? ($student['attendance_days'] / $total_days) * 100 : 0;
        
        // ใส่ข้อมูลแต่ละคอลัมน์
        $sheet->setCellValue($col++ . $row, $index);
        $sheet->setCellValue($col++ . $row, $student['student_code']);
        $sheet->setCellValue($col++ . $row, $student['title']);
        $sheet->setCellValue($col++ . $row, $student['first_name']);
        $sheet->setCellValue($col++ . $row, $student['last_name']);
        $sheet->setCellValue($col++ . $row, $student['level'] ?? '-');
        $sheet->setCellValue($col++ . $row, $student['group_number'] ?? '-');
        $sheet->setCellValue($col++ . $row, $student['department_name'] ?? '-');
        $sheet->setCellValue($col++ . $row, $student['phone_number'] ?? '-');
        $sheet->setCellValue($col++ . $row, $student['email'] ?? '-');
        $sheet->setCellValue($col++ . $row, $student['status']);
        $sheet->setCellValue($col++ . $row, $student['attendance_days']);
        $sheet->setCellValue($col++ . $row, $student['absence_days']);
        $sheet->setCellValue($col++ . $row, number_format($attendance_rate, 1));
        
        $row++;
        $index++;
    }
    
    // จัดรูปแบบข้อมูล
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    
    $sheet->getStyle('A6:N' . ($row - 1))->applyFromArray($dataStyle);
    
    // จัดความกว้างคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(8);
    $sheet->getColumnDimension('H')->setWidth(20);
    $sheet->getColumnDimension('I')->setWidth(15);
    $sheet->getColumnDimension('J')->setWidth(25);
    $sheet->getColumnDimension('K')->setWidth(15);
    $sheet->getColumnDimension('L')->setWidth(15);
    $sheet->getColumnDimension('M')->setWidth(15);
    $sheet->getColumnDimension('N')->setWidth(18);
    
    // สร้างไฟล์
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // บันทึกข้อผิดพลาด
    error_log('Error exporting students: ' . $e->getMessage());
    
    // ส่งข้อความแสดงข้อผิดพลาด
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'เกิดข้อผิดพลาดในการส่งออกข้อมูล: ' . $e->getMessage();
}
 ?>
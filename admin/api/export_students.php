<?php
/**
 * export_students.php - API สำหรับส่งออกข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     header('Content-Type: text/plain');
//     echo 'ไม่มีสิทธิ์เข้าถึง API นี้';
//     exit;
// }

// ใช้ไลบรารี PhpSpreadsheet สำหรับสร้างไฟล์ Excel
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// สร้างเงื่อนไขการค้นหา
$where_conditions = [];
$params = [];

if (isset($_GET['name']) && !empty($_GET['name'])) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_name = '%' . $_GET['name'] . '%';
    $params[] = $search_name;
    $params[] = $search_name;
}

if (isset($_GET['student_code']) && !empty($_GET['student_code'])) {
    $where_conditions[] = "s.student_code LIKE ?";
    $params[] = '%' . $_GET['student_code'] . '%';
}

if (isset($_GET['level']) && !empty($_GET['level'])) {
    $where_conditions[] = "c.level = ?";
    $params[] = $_GET['level'];
}

if (isset($_GET['group_number']) && !empty($_GET['group_number'])) {
    $where_conditions[] = "c.group_number = ?";
    $params[] = $_GET['group_number'];
}

if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $where_conditions[] = "c.department_id = ?";
    $params[] = $_GET['department_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "s.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['attendance_status']) && !empty($_GET['attendance_status'])) {
    // สร้างเงื่อนไขสำหรับสถานะการเข้าแถว (จะต้องคำนวณในภายหลัง)
    $attendance_status = $_GET['attendance_status'];
    $attendance_condition = true;
} else {
    $attendance_condition = false;
}

if (isset($_GET['line_status']) && !empty($_GET['line_status'])) {
    if ($_GET['line_status'] === 'connected') {
        $where_conditions[] = "u.line_id IS NOT NULL AND u.line_id != ''";
    } else if ($_GET['line_status'] === 'not_connected') {
        $where_conditions[] = "(u.line_id IS NULL OR u.line_id = '')";
    }
}

// สร้าง SQL สำหรับดึงข้อมูลนักเรียน
$sql_condition = "";
if (!empty($where_conditions)) {
    $sql_condition = " WHERE " . implode(" AND ", $where_conditions);
}

try {
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
    $query = "SELECT s.student_id, s.student_code, s.status, 
              u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email,
              c.level, c.group_number, c.class_id,
              d.department_name, d.department_id,
              (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
               FROM class_advisors ca 
               JOIN teachers t ON ca.teacher_id = t.teacher_id 
               WHERE ca.class_id = c.class_id AND ca.is_primary = 1
               LIMIT 1) as advisor_name,
              IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
              IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              $sql_condition
              ORDER BY s.student_code";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query($query);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เติมข้อมูลเพิ่มเติม
    foreach ($students as &$student) {
        // สร้างชื่อชั้นเรียน
        $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        
        // คำนวณอัตราการเข้าแถว
        $total_days = $student['attendance_days'] + $student['absence_days'];
        if ($total_days > 0) {
            $student['attendance_rate'] = ($student['attendance_days'] / $total_days) * 100;
        } else {
            $student['attendance_rate'] = 100; // ถ้ายังไม่มีข้อมูลให้เป็น 100%
        }
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] < 60) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] < 75) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
        
        // ตรวจสอบการเชื่อมต่อกับ LINE
        $student['line_connected'] = !empty($student['line_id']);
    }
    
    // กรองตามสถานะการเข้าแถว (ถ้ามี)
    if ($attendance_condition) {
        $students = array_filter($students, function($student) use ($attendance_status) {
            return $student['attendance_status'] === $attendance_status;
        });
    }
    
    // สร้าง spreadsheet ใหม่
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('รายชื่อนักเรียน');
    
    // ตั้งค่าหัวตาราง
    $headers = [
        'A1' => 'รหัสนักศึกษา',
        'B1' => 'คำนำหน้า',
        'C1' => 'ชื่อ',
        'D1' => 'นามสกุล',
        'E1' => 'ชั้น/ห้อง',
        'F1' => 'แผนกวิชา',
        'G1' => 'เบอร์โทรศัพท์',
        'H1' => 'อีเมล',
        'I1' => 'ครูที่ปรึกษา',
        'J1' => 'วันที่เข้าแถว',
        'K1' => 'วันที่ขาด',
        'L1' => 'อัตราการเข้าแถว',
        'M1' => 'สถานะการเข้าแถว',
        'N1' => 'เชื่อมต่อ LINE',
        'O1' => 'สถานะการศึกษา'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
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
    
    $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
    
    // เพิ่มข้อมูลนักเรียน
    $row = 2;
    foreach ($students as $student) {
        $sheet->setCellValue('A' . $row, $student['student_code']);
        $sheet->setCellValue('B' . $row, $student['title']);
        $sheet->setCellValue('C' . $row, $student['first_name']);
        $sheet->setCellValue('D' . $row, $student['last_name']);
        $sheet->setCellValue('E' . $row, $student['class']);
        $sheet->setCellValue('F' . $row, $student['department_name'] ?? '');
        $sheet->setCellValue('G' . $row, $student['phone_number']);
        $sheet->setCellValue('H' . $row, $student['email']);
        $sheet->setCellValue('I' . $row, $student['advisor_name'] ?? '');
        $sheet->setCellValue('J' . $row, $student['attendance_days']);
        $sheet->setCellValue('K' . $row, $student['absence_days']);
        $sheet->setCellValue('L' . $row, number_format($student['attendance_rate'], 2) . '%');
        $sheet->setCellValue('M' . $row, $student['attendance_status']);
        $sheet->setCellValue('N' . $row, $student['line_connected'] ? 'เชื่อมต่อแล้ว' : 'ยังไม่เชื่อมต่อ');
        $sheet->setCellValue('O' . $row, $student['status']);
        
        // ตั้งค่าสีพื้นหลังตามสถานะการเข้าแถว
        $statusCell = 'M' . $row;
        if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
            $sheet->getStyle($statusCell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFC7CE');
        } elseif ($student['attendance_status'] === 'ต้องระวัง') {
            $sheet->getStyle($statusCell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFEB9C');
        } else {
            $sheet->getStyle($statusCell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('C6EFCE');
        }
        
        $row++;
    }
    
    // ปรับขนาดคอลัมน์ให้พอดีกับข้อมูล
    foreach (range('A', 'O') as $col) {
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
    $sheet->getStyle('A1:O' . ($row - 1))->applyFromArray($tableStyle);
    
    // ตั้งค่าการจัดตำแหน่งข้อมูล
    $sheet->getStyle('A2:A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E2:E' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J2:L' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('M2:O' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    
    // กำหนดรูปแบบเปอร์เซ็นต์สำหรับอัตราการเข้าแถว
    for ($i = 2; $i < $row; $i++) {
        $sheet->getStyle('L' . $i)->getNumberFormat()->setFormatCode('0.00%');
    }
    
    // สร้างชื่อไฟล์ Excel
    $filename = 'รายชื่อนักเรียน_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // กำหนด headers สำหรับการดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // ส่งออกไฟล์ Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (PDOException $e) {
    // แสดงข้อผิดพลาด
    header('Content-Type: text/plain');
    echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    exit;
}
?>
<?php
/**
 * download_template.php - API สำหรับดาวน์โหลดเทมเพลตการนำเข้าข้อมูล
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
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบประเภทของเทมเพลต
$template_type = $_GET['type'] ?? '';

switch ($template_type) {
    case 'students':
        generateStudentTemplate();
        break;
        
    default:
        header('Content-Type: text/plain');
        echo 'ไม่รู้จักประเภทเทมเพลต';
        exit;
}

/**
 * สร้างเทมเพลตสำหรับนำเข้าข้อมูลนักเรียน
 */
function generateStudentTemplate() {
    // สร้าง spreadsheet ใหม่
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('ข้อมูลนักเรียน');
    
    // ตั้งค่าหัวตาราง
    $headers = [
        'A1' => 'รหัสนักศึกษา*',
        'B1' => 'คำนำหน้า*',
        'C1' => 'ชื่อ*',
        'D1' => 'นามสกุล*',
        'E1' => 'เบอร์โทรศัพท์',
        'F1' => 'อีเมล',
        'G1' => 'ระดับชั้น',
        'H1' => 'กลุ่ม',
        'I1' => 'แผนกวิชา',
        'J1' => 'สถานะการศึกษา'
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
    
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    
    // ตั้งค่า validation สำหรับคำนำหน้า
    $validation = $sheet->getCell('B2')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(false);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"นาย,นางสาว,เด็กชาย,เด็กหญิง"');
    $sheet->setDataValidation('B2:B1000', $validation);
    
    // ตั้งค่า validation สำหรับระดับชั้น
    $validation = $sheet->getCell('G2')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"ปวช.1,ปวช.2,ปวช.3,ปวส.1,ปวส.2"');
    $sheet->setDataValidation('G2:G1000', $validation);
    
    // ตั้งค่า validation สำหรับกลุ่ม
    $validation = $sheet->getCell('H2')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"1,2,3,4,5"');
    $sheet->setDataValidation('H2:H1000', $validation);
    
    // ดึงข้อมูลแผนกวิชาจากฐานข้อมูล
    try {
        $conn = getDB();
        $stmt = $conn->query("SELECT department_name FROM departments ORDER BY department_name");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($departments) {
            $departmentList = '"' . implode(',', $departments) . '"';
            
            // ตั้งค่า validation สำหรับแผนกวิชา
            $validation = $sheet->getCell('I2')->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($departmentList);
            $sheet->setDataValidation('I2:I1000', $validation);
        }
    } catch (PDOException $e) {
        // ถ้าเกิดข้อผิดพลาดในการดึงข้อมูลแผนกวิชา ให้ใช้ค่าตั้งต้น
        $defaultDepartments = '"เทคโนโลยีสารสนเทศ,ช่างยนต์,ช่างไฟฟ้ากำลัง,ช่างกลโรงงาน,การบัญชี"';
        $validation = $sheet->getCell('I2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($defaultDepartments);
        $sheet->setDataValidation('I2:I1000', $validation);
    }
    
    // ตั้งค่า validation สำหรับสถานะการศึกษา
    $validation = $sheet->getCell('J2')->getDataValidation();
    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"กำลังศึกษา,พักการเรียน,พ้นสภาพ,สำเร็จการศึกษา"');
    $sheet->setDataValidation('J2:J1000', $validation);
    
    // ปรับขนาดคอลัมน์ให้พอดีกับข้อมูล
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // ใส่ข้อมูลตัวอย่าง
    $exampleData = [
        ['6439010001', 'นาย', 'สมชาย', 'ใจดี', '0812345678', 'somchai@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา'],
        ['6439010002', 'นางสาว', 'สมหญิง', 'รักเรียน', '0823456789', 'somying@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา']
    ];
    
    $row = 2;
    foreach ($exampleData as $data) {
        $col = 'A';
        foreach ($data as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // เพิ่มแผ่นงานคำอธิบาย
    $spreadsheet->createSheet();
    $spreadsheet->setActiveSheetIndex(1);
    $instructionSheet = $spreadsheet->getActiveSheet();
    $instructionSheet->setTitle('คำอธิบาย');
    
    $instructions = [
        ['A1', 'คำอธิบายการนำเข้าข้อมูลนักเรียน'],
        ['A3', '1. ช่องที่มีเครื่องหมาย * ต้องกรอกข้อมูล'],
        ['A4', '2. รหัสนักเรียน: ต้องไม่ซ้ำกับที่มีอยู่ในระบบ'],
        ['A5', '3. คำนำหน้า: เลือกจากรายการที่กำหนด (นาย, นางสาว, เด็กชาย, เด็กหญิง)'],
        ['A6', '4. ระดับชั้น: เลือกจากรายการที่กำหนด (ปวช.1, ปวช.2, ปวช.3, ปวส.1, ปวส.2)'],
        ['A7', '5. กลุ่ม: เลือกจากรายการที่กำหนด (1, 2, 3, 4, 5)'],
        ['A8', '6. แผนกวิชา: เลือกจากรายการที่กำหนด'],
        ['A9', '7. สถานะการศึกษา: เลือกจากรายการที่กำหนด (กำลังศึกษา, พักการเรียน, พ้นสภาพ, สำเร็จการศึกษา)'],
        ['A11', 'หมายเหตุ:'],
        ['A12', '- การนำเข้าข้อมูลจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน'],
        ['A13', '- ระบบจะอัพเดทข้อมูลอัตโนมัติหากพบรหัสนักเรียนซ้ำ'],
        ['A14', '- กรุณาอย่าเปลี่ยนแปลงโครงสร้างของเทมเพลต']
    ];
    
    foreach ($instructions as $instruction) {
        $instructionSheet->setCellValue($instruction[0], $instruction[1]);
    }
    
    $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $instructionSheet->getColumnDimension('A')->setWidth(100);
    
    // กลับไปที่แผ่นงานแรก
    $spreadsheet->setActiveSheetIndex(0);
    
    // กำหนด headers สำหรับการดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="template_students.xlsx"');
    header('Cache-Control: max-age=0');
    
    // ส่งออกไฟล์ Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
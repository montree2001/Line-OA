<?php
/**
 * download_template.php - API สำหรับดาวน์โหลดไฟล์เทมเพลตนำเข้าข้อมูล
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// ตรวจสอบว่ามีการระบุประเภทเทมเพลตหรือไม่
if (!isset($_GET['type']) || empty($_GET['type'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'ไม่ได้ระบุประเภทเทมเพลต';
    exit;
}

// ประเภทเทมเพลต
$type = $_GET['type'];

// โหลดไลบรารี PhpSpreadsheet
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// สร้าง Spreadsheet ใหม่
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// กำหนดสไตล์สำหรับหัวคอลัมน์
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '06C755'], // สีหลักของระบบ
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

// กำหนดสไตล์สำหรับแถวตัวอย่าง
$exampleStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

// สร้างเทมเพลตตามประเภท
if ($type === 'teachers') {
    // ชื่อไฟล์
    $filename = 'ไฟล์เทมเพลตนำเข้าข้อมูลครู_' . date('Ymd') . '.xlsx';
    
    // กำหนดชื่อและความกว้างของคอลัมน์
    $sheet->setCellValue('A1', 'เลขบัตรประชาชน');
    $sheet->setCellValue('B1', 'คำนำหน้า');
    $sheet->setCellValue('C1', 'ชื่อ');
    $sheet->setCellValue('D1', 'นามสกุล');
    $sheet->setCellValue('E1', 'แผนก');
    $sheet->setCellValue('F1', 'ตำแหน่ง');
    $sheet->setCellValue('G1', 'เบอร์โทรศัพท์');
    $sheet->setCellValue('H1', 'อีเมล');
    
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(20);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(25);
    
    // ใส่ข้อมูลตัวอย่าง
    $sheet->setCellValue('A2', '1234567890123');
    $sheet->setCellValue('B2', 'นาย');
    $sheet->setCellValue('C2', 'ตัวอย่าง');
    $sheet->setCellValue('D2', 'ทดสอบ');
    $sheet->setCellValue('E2', 'เทคโนโลยีสารสนเทศ');
    $sheet->setCellValue('F2', 'ครูชำนาญการ');
    $sheet->setCellValue('G2', '0812345678');
    $sheet->setCellValue('H2', 'example@prasat.ac.th');
    
    $sheet->setCellValue('A3', '1234567890124');
    $sheet->setCellValue('B3', 'นาง');
    $sheet->setCellValue('C3', 'สมศรี');
    $sheet->setCellValue('D3', 'มีสุข');
    $sheet->setCellValue('E3', 'ช่างยนต์');
    $sheet->setCellValue('F3', 'ครูชำนาญการพิเศษ');
    $sheet->setCellValue('G3', '0898765432');
    $sheet->setCellValue('H3', 'somsri@prasat.ac.th');
    
    // เพิ่มหมายเหตุ
    $sheet->mergeCells('A5:H5');
    $sheet->setCellValue('A5', 'หมายเหตุ:');
    $sheet->getStyle('A5')->getFont()->setBold(true);
    
    $sheet->mergeCells('A6:H6');
    $sheet->setCellValue('A6', '1. เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก');
    
    $sheet->mergeCells('A7:H7');
    $sheet->setCellValue('A7', '2. คำนำหน้าที่รองรับ: นาย, นาง, นางสาว, ดร., ผศ., รศ., ศ., อื่นๆ');
    
    $sheet->mergeCells('A8:H8');
    $sheet->setCellValue('A8', '3. ชื่อและนามสกุลต้องไม่เป็นค่าว่าง');
    
    $sheet->mergeCells('A9:H9');
    $sheet->setCellValue('A9', '4. แผนกควรตรงกับแผนกในระบบ เช่น เทคโนโลยีสารสนเทศ, ช่างยนต์, การบัญชี, ฯลฯ');
    
} elseif ($type === 'students') {
    // ชื่อไฟล์
    $filename = 'ไฟล์เทมเพลตนำเข้าข้อมูลนักเรียน_' . date('Ymd') . '.xlsx';
    
    // กำหนดชื่อและความกว้างของคอลัมน์
    $sheet->setCellValue('A1', 'รหัสนักเรียน');
    $sheet->setCellValue('B1', 'คำนำหน้า');
    $sheet->setCellValue('C1', 'ชื่อ');
    $sheet->setCellValue('D1', 'นามสกุล');
    $sheet->setCellValue('E1', 'เบอร์โทรศัพท์');
    $sheet->setCellValue('F1', 'อีเมล');
    $sheet->setCellValue('G1', 'ระดับชั้น');
    $sheet->setCellValue('H1', 'กลุ่ม');
    $sheet->setCellValue('I1', 'แผนก');
    $sheet->setCellValue('J1', 'สถานะ');
    
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(15);
    $sheet->getColumnDimension('F')->setWidth(25);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(10);
    $sheet->getColumnDimension('I')->setWidth(20);
    $sheet->getColumnDimension('J')->setWidth(15);
    
    // ใส่ข้อมูลตัวอย่าง
    $sheet->setCellValue('A2', '67319010001');
    $sheet->setCellValue('B2', 'นาย');
    $sheet->setCellValue('C2', 'ตัวอย่าง');
    $sheet->setCellValue('D2', 'ทดสอบ');
    $sheet->setCellValue('E2', '0812345678');
    $sheet->setCellValue('F2', 'student@example.com');
    $sheet->setCellValue('G2', 'ปวช.1');
    $sheet->setCellValue('H2', '1');
    $sheet->setCellValue('I2', 'เทคโนโลยีสารสนเทศ');
    $sheet->setCellValue('J2', 'กำลังศึกษา');
    
    $sheet->setCellValue('A3', '67319010002');
    $sheet->setCellValue('B3', 'นางสาว');
    $sheet->setCellValue('C3', 'สมหญิง');
    $sheet->setCellValue('D3', 'รักเรียน');
    $sheet->setCellValue('E3', '0898765432');
    $sheet->setCellValue('F3', 'somying@example.com');
    $sheet->setCellValue('G3', 'ปวช.1');
    $sheet->setCellValue('H3', '1');
    $sheet->setCellValue('I3', 'การบัญชี');
    $sheet->setCellValue('J3', 'กำลังศึกษา');
    
    // เพิ่มหมายเหตุ
    $sheet->mergeCells('A5:J5');
    $sheet->setCellValue('A5', 'หมายเหตุ:');
    $sheet->getStyle('A5')->getFont()->setBold(true);
    
    $sheet->mergeCells('A6:J6');
    $sheet->setCellValue('A6', '1. รหัสนักเรียนต้องไม่ซ้ำกับที่มีอยู่ในระบบ');
    
    $sheet->mergeCells('A7:J7');
    $sheet->setCellValue('A7', '2. คำนำหน้าที่รองรับ: นาย, นางสาว, เด็กชาย, เด็กหญิง, นาง');
    
    $sheet->mergeCells('A8:J8');
    $sheet->setCellValue('A8', '3. ระดับชั้นที่รองรับ: ปวช.1, ปวช.2, ปวช.3, ปวส.1, ปวส.2');
    
    $sheet->mergeCells('A9:J9');
    $sheet->setCellValue('A9', '4. สถานะที่รองรับ: กำลังศึกษา, พักการเรียน, พ้นสภาพ, สำเร็จการศึกษา');
    
} else {
    // ประเภทไม่รองรับ
    header('HTTP/1.1 400 Bad Request');
    echo 'ประเภทเทมเพลตไม่รองรับ';
    exit;
}

// จัดรูปแบบหัวตาราง
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
$sheet->getStyle('A2:' . $sheet->getHighestColumn() . '3')->applyFromArray($exampleStyle);

// ตั้งค่าการแสดงผลทั้งหมดให้อยู่ตรงกลางแนวตั้ง
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// บันทึกไฟล์
$writer = new Xlsx($spreadsheet);

// ส่งไฟล์ไปยังผู้ใช้
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
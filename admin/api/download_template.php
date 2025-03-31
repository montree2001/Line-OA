<?php
/**
 * api/download_template.php - ดาวน์โหลดเทมเพลตสำหรับการนำเข้าข้อมูล
 */

// ลบทุก output buffer ก่อนหน้า
@ob_clean();

// ใช้ namespace ก่อนเข้าบล็อกโค้ด
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// เริ่ม session ก่อนส่ง headers
session_start();

// เพิ่มการตรวจสอบข้อผิดพลาดแต่ไม่แสดงในเบราว์เซอร์
error_reporting(E_ALL);
ini_set('display_errors', 0);

// กำหนดประเภทของเทมเพลต (students หรือ teachers)
$template_type = isset($_GET['type']) ? $_GET['type'] : 'students';

// พยายามโหลด Composer autoloader จากหลายตำแหน่งที่เป็นไปได้
$autoloader_paths = [
    __DIR__ . '/../../vendor/autoload.php',   // ถ้า vendor อยู่ที่ root ของโปรเจค
    __DIR__ . '/../vendor/autoload.php',      // ถ้า vendor อยู่ในโฟลเดอร์ admin
    __DIR__ . '/vendor/autoload.php',         // ถ้า vendor อยู่ในโฟลเดอร์ api
    '../vendor/autoload.php',                 // relative path ดั้งเดิม
    '../../vendor/autoload.php',              // relative path ไปที่ root
    $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php' // จาก document root
];

$autoloader_loaded = false;
foreach ($autoloader_paths as $path) {
    if (file_exists($path)) {
        try {
            require_once $path;
            $autoloader_loaded = true;
            error_log('Loaded autoloader from: ' . $path);
            break;
        } catch (Exception $e) {
            error_log('Error loading autoloader from ' . $path . ': ' . $e->getMessage());
            continue;
        }
    }
}

// ถ้าไม่พบ autoloader ให้ใช้วิธีสร้าง CSV แทน
if (!$autoloader_loaded) {
    error_log('Autoloader not found, falling back to CSV');
    
    // กำหนดประเภทของไฟล์และชื่อไฟล์ที่จะดาวน์โหลด (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="import_template_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    // เปิด output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 support in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // กำหนดหัวข้อตามประเภทของเทมเพลต
    if ($template_type == 'students') {
        // หัวข้อสำหรับนักเรียน
        fputcsv($output, [
            'รหัสนักศึกษา', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 
            'อีเมล', 'ระดับชั้น', 'กลุ่ม', 'แผนก', 'สถานะ'
        ]);
        
        // ตัวอย่างข้อมูล
        fputcsv($output, ['65101020001', 'นาย', 'สมชาย', 'ใจดี', '0891234567', 'somchai@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา']);
        fputcsv($output, ['65101020002', 'นางสาว', 'สมหญิง', 'รักเรียน', '0891234568', 'somying@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา']);
    } else {
        // หัวข้อสำหรับครู
        fputcsv($output, [
            'รหัสประจำตัวประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'แผนก', 
            'ตำแหน่ง', 'เบอร์โทรศัพท์', 'อีเมล'
        ]);
        
        // ตัวอย่างข้อมูล
        fputcsv($output, ['1234567890123', 'นาย', 'สมศักดิ์', 'สอนดี', 'เทคโนโลยีสารสนเทศ', 'ครูผู้สอน', '0891234569', 'somsak@example.com']);
        fputcsv($output, ['9876543210987', 'นาง', 'สมศรี', 'มีความรู้', 'เทคโนโลยีสารสนเทศ', 'หัวหน้าแผนก', '0891234570', 'somsri@example.com']);
    }
    
    // ปิด output stream
    fclose($output);
    exit;
}

try {
    // ตรวจสอบว่า class PhpSpreadsheet มีอยู่จริงหรือไม่
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        throw new Exception('PhpSpreadsheet class not found. Please run "composer require phpoffice/phpspreadsheet"');
    }
    
    // กำหนดประเภทของไฟล์และชื่อไฟล์ที่จะดาวน์โหลด (Excel)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="import_template_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // สร้าง Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // ตั้งชื่อ Sheet
    if ($template_type == 'students') {
        $sheet->setTitle('นำเข้าข้อมูลนักเรียน');
    } else {
        $sheet->setTitle('นำเข้าข้อมูลครู');
    }
    
    // เพิ่มหัวข้อและคำแนะนำ
    if ($template_type == 'students') {
        $sheet->setCellValue('A1', 'เทมเพลตนำเข้าข้อมูลนักเรียน');
        $sheet->setCellValue('A2', 'คำแนะนำ: กรุณาใส่ข้อมูลให้ตรงคอลัมน์ และห้ามเปลี่ยนชื่อคอลัมน์');
    } else {
        $sheet->setCellValue('A1', 'เทมเพลตนำเข้าข้อมูลครูที่ปรึกษา');
        $sheet->setCellValue('A2', 'คำแนะนำ: กรุณาใส่ข้อมูลให้ตรงคอลัมน์ และห้ามเปลี่ยนชื่อคอลัมน์');
    }
    
    // จัดรูปแบบหัวข้อ
    $sheet->getStyle('A1:A2')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setSize(14);
    
    // กำหนดหัวตาราง
    if ($template_type == 'students') {
        // หัวตารางสำหรับนักเรียน
        $headers = [
            'รหัสนักศึกษา', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 
            'อีเมล', 'ระดับชั้น', 'กลุ่ม', 'แผนก', 'สถานะ'
        ];
    } else {
        // หัวตารางสำหรับครู
        $headers = [
            'รหัสประจำตัวประชาชน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'แผนก', 
            'ตำแหน่ง', 'เบอร์โทรศัพท์', 'อีเมล'
        ];
    }
    
    // ใส่หัวตาราง
    $col = 'A';
    $row = 4;
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    
    // จัดรูปแบบหัวตาราง
    $lastCol = chr(ord('A') + count($headers) - 1);
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
    
    $sheet->getStyle('A4:' . $lastCol . '4')->applyFromArray($headerStyle);
    
    // เพิ่มตัวอย่างข้อมูล
    if ($template_type == 'students') {
        // ตัวอย่างสำหรับนักเรียน
        $examples = [
            ['65101020001', 'นาย', 'สมชาย', 'ใจดี', '0891234567', 'somchai@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา'],
            ['65101020002', 'นางสาว', 'สมหญิง', 'รักเรียน', '0891234568', 'somying@example.com', 'ปวช.1', '1', 'เทคโนโลยีสารสนเทศ', 'กำลังศึกษา']
        ];
    } else {
        // ตัวอย่างสำหรับครู
        $examples = [
            ['1234567890123', 'นาย', 'สมศักดิ์', 'สอนดี', 'เทคโนโลยีสารสนเทศ', 'ครูผู้สอน', '0891234569', 'somsak@example.com'],
            ['9876543210987', 'นาง', 'สมศรี', 'มีความรู้', 'เทคโนโลยีสารสนเทศ', 'หัวหน้าแผนก', '0891234570', 'somsri@example.com']
        ];
    }
    
    // ใส่ตัวอย่างข้อมูล
    $row = 5;
    foreach ($examples as $example) {
        $col = 'A';
        foreach ($example as $value) {
            $sheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // จัดรูปแบบตาราง
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    
    $sheet->getStyle('A4:' . $lastCol . '6')->applyFromArray($dataStyle);
    
    // ปรับขนาดคอลัมน์ให้พอดีกับข้อมูล
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // สร้างไฟล์
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    // บันทึกข้อผิดพลาดอย่างละเอียด
    error_log('Error generating template: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    // ส่งข้อความแสดงข้อผิดพลาด
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'เกิดข้อผิดพลาดในการสร้างเทมเพลต: ' . $e->getMessage();
    exit;
}
?>

<?php
/**
 * install_fonts.php - สคริปต์สำหรับติดตั้งฟอนต์ THSarabunNew ให้กับ MPDF
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 * 
 * วิธีใช้งาน:
 * 1. อัพโหลดไฟล์ฟอนต์ THSarabunNew.ttf, THSarabunNew-Bold.ttf, THSarabunNew-Italic.ttf, THSarabunNew-BoldItalic.ttf 
 *    ไว้ในโฟลเดอร์ fonts/thsarabunnew/
 * 2. รันสคริปต์นี้ผ่านเว็บเบราวเซอร์หรือคอมมานด์ไลน์ (php install_fonts.php)
 * 3. หลังจากรันสคริปต์นี้แล้ว MPDF จะสามารถใช้ฟอนต์ THSarabunNew ได้
 */

// เรียกใช้ mPDF
require_once __DIR__ . '/vendor/autoload.php';

// ตรวจสอบว่ามีโฟลเดอร์เก็บฟอนต์หรือไม่
$fontsDir = __DIR__ . '/fonts';
if (!is_dir($fontsDir)) {
    mkdir($fontsDir, 0755, true);
}

// สร้างโฟลเดอร์ย่อยสำหรับฟอนต์ THSarabunNew
$thSarabunDir = $fontsDir . '/thsarabunnew';
if (!is_dir($thSarabunDir)) {
    mkdir($thSarabunDir, 0755, true);
}

// ตรวจสอบว่ามีไฟล์ฟอนต์หรือไม่
$fontFiles = [
    'THSarabunNew.ttf',
    'THSarabunNew-Bold.ttf',
    'THSarabunNew-Italic.ttf',
    'THSarabunNew-BoldItalic.ttf'
];

$fontsMissing = false;
$missingFonts = [];

foreach ($fontFiles as $file) {
    $fontPath = $thSarabunDir . '/' . $file;
    if (!file_exists($fontPath)) {
        $fontsMissing = true;
        $missingFonts[] = $file;
    }
}

if ($fontsMissing) {
    echo "ไม่พบไฟล์ฟอนต์บางรายการ: " . implode(', ', $missingFonts) . "\n";
    echo "กรุณาอัพโหลดไฟล์ฟอนต์ไปที่ " . $thSarabunDir . "\n";
    exit;
}

// สร้างไฟล์ MPDF เพื่อติดตั้งฟอนต์
try {
    // กำหนดค่าฟอนต์
    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    // กำหนดค่า MPDF
    $mpdf = new \Mpdf\Mpdf([
        'fontDir' => array_merge($fontDirs, [
            $fontsDir,
        ]),
        'fontdata' => $fontData + [
            'thsarabun' => [
                'R' => 'thsarabunnew/THSarabunNew.ttf',
                'B' => 'thsarabunnew/THSarabunNew-Bold.ttf',
                'I' => 'thsarabunnew/THSarabunNew-Italic.ttf',
                'BI' => 'thsarabunnew/THSarabunNew-BoldItalic.ttf',
            ]
        ],
        'default_font' => 'thsarabun',
        'mode' => 'utf-8',
    ]);

    // ทดสอบการสร้างไฟล์ PDF
    $html = '<html><head><title>ทดสอบฟอนต์ THSarabunNew</title></head><body>';
    $html .= '<h1 style="font-family: thsarabun;">ทดสอบฟอนต์ THSarabunNew</h1>';
    $html .= '<p style="font-family: thsarabun;">นี่คือข้อความภาษาไทยที่ใช้ฟอนต์ THSarabunNew</p>';
    $html .= '<p style="font-family: thsarabun; font-weight: bold;">ตัวหนา (Bold)</p>';
    $html .= '<p style="font-family: thsarabun; font-style: italic;">ตัวเอียง (Italic)</p>';
    $html .= '<p style="font-family: thsarabun; font-weight: bold; font-style: italic;">ตัวหนาและเอียง (Bold Italic)</p>';
    $html .= '</body></html>';

    // สร้าง PDF
    $mpdf->WriteHTML($html);
    
    // ตรวจสอบโฟลเดอร์สำหรับไฟล์ tmp
    $tmpDir = __DIR__ . '/tmp';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0755, true);
    }
    
    // บันทึกไฟล์ PDF
    $testPdfPath = $tmpDir . '/font-test.pdf';
    $mpdf->Output($testPdfPath, \Mpdf\Output\Destination::FILE);
    
    echo "ติดตั้งฟอนต์ THSarabunNew สำเร็จ!\n";
    echo "สร้างไฟล์ PDF ทดสอบที่: " . $testPdfPath . "\n";
    
    // สร้างไฟล์ config สำหรับ MPDF
    $configContent = "<?php\n\n";
    $configContent .= "// ไฟล์นี้สร้างโดยอัตโนมัติจาก install_fonts.php\n";
    $configContent .= "// กำหนดค่าฟอนต์ THSarabunNew สำหรับ MPDF\n\n";
    $configContent .= "return [\n";
    $configContent .= "    'fontDir' => array_merge(\$this->fontDir, [\n";
    $configContent .= "        __DIR__ . '/../../fonts',\n";
    $configContent .= "    ]),\n";
    $configContent .= "    'fontdata' => \$this->fontdata + [\n";
    $configContent .= "        'thsarabun' => [\n";
    $configContent .= "            'R' => 'thsarabunnew/THSarabunNew.ttf',\n";
    $configContent .= "            'B' => 'thsarabunnew/THSarabunNew-Bold.ttf',\n";
    $configContent .= "            'I' => 'thsarabunnew/THSarabunNew-Italic.ttf',\n";
    $configContent .= "            'BI' => 'thsarabunnew/THSarabunNew-BoldItalic.ttf',\n";
    $configContent .= "        ]\n";
    $configContent .= "    ],\n";
    $configContent .= "    'default_font' => 'thsarabun',\n";
    $configContent .= "];\n";
    
    // บันทึกไฟล์ config
    $configDir = __DIR__ . '/vendor/mpdf/mpdf/config';
    if (is_dir($configDir)) {
        file_put_contents($configDir . '/font-thsarabun.php', $configContent);
        echo "บันทึกไฟล์ config สำเร็จที่: " . $configDir . "/font-thsarabun.php\n";
    }
    
    echo "\nการติดตั้งเสร็จสมบูรณ์ คุณสามารถใช้ฟอนต์ THSarabunNew ใน MPDF ได้แล้ว!\n";
    
} catch (Exception $e) {
    echo "เกิดข้อผิดพลาดในการติดตั้งฟอนต์: " . $e->getMessage() . "\n";
}
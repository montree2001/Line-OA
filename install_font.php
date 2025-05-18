<?php
// สร้างไฟล์ install_font.php ในโฟลเดอร์โปรเจกต์
require_once __DIR__ . '/vendor/autoload.php';

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

// ตรวจสอบว่ามีโฟลเดอร์เก็บฟอนต์หรือไม่
$customFontDir = __DIR__ . '/fonts';
if (!is_dir($customFontDir)) {
    mkdir($customFontDir, 0755, true);
}

// ดาวน์โหลดฟอนต์ THSarabunNew (ต้องมีไฟล์ฟอนต์ก่อน)
$fonts = [
    'THSarabunNew.ttf',
    'THSarabunNew-Bold.ttf',
    'THSarabunNew-Italic.ttf',
    'THSarabunNew-BoldItalic.ttf'
];

// ถ้ามีไฟล์ฟอนต์แล้วคุณสามารถคัดลอกไปยังโฟลเดอร์ fonts ได้เลย
// copy('path/to/your/font/THSarabunNew.ttf', $customFontDir . '/THSarabunNew.ttf');

echo "Font installation complete!";
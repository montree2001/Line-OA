<?php
require_once __DIR__ . '/vendor/autoload.php'; // เรียกใช้งาน autoload ของ Composer
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mpdf = new \Mpdf\Mpdf();

$html = '
<h1 style="color:navy;">Hello, mPDF!</h1>
<p>นี่คือตัวอย่างการสร้าง PDF ด้วย <strong>mPDF</strong></p>
<ul>
    <li>รองรับภาษาไทย</li>
    <li>รองรับ CSS เบื้องต้น</li>
    <li>ใช้งานง่าย</li>
</ul>
';

$mpdf->WriteHTML($html);
$mpdf->Output('example.pdf', 'I'); // 'I' = แสดงใน browser, 'D' = ดาวน์โหลด

<?php
/**
 * download_sample.php - ไฟล์สำหรับดาวน์โหลดตัวอย่างไฟล์นำเข้าข้อมูล
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// กำหนดประเภทของไฟล์ที่จะโหลด
$type = isset($_GET['type']) ? $_GET['type'] : '';

// ตรวจสอบว่าเป็นประเภทที่รองรับหรือไม่
if (!in_array($type, ['teacher', 'student', 'parent', 'class'])) {
    die("ไม่รองรับประเภทไฟล์ที่ต้องการดาวน์โหลด");
}

// โหลดคลาสสำหรับสร้างไฟล์ตัวอย่าง
require_once '../import/ImportTeachers.php';

// สร้างไฟล์ตัวอย่างตามประเภท
switch ($type) {
    case 'teacher':
        $importer = new ImportTeachers();
        $file_path = $importer->createSampleFile();
        $file_name = 'teacher_template.xlsx';
        break;
    
    case 'student':
        // ในอนาคตอาจจะมีคลาสสำหรับนำเข้าข้อมูลนักเรียน
        die("อยู่ระหว่างการพัฒนา");
        break;
    
    case 'parent':
        // ในอนาคตอาจจะมีคลาสสำหรับนำเข้าข้อมูลผู้ปกครอง
        die("อยู่ระหว่างการพัฒนา");
        break;
    
    case 'class':
        // ในอนาคตอาจจะมีคลาสสำหรับนำเข้าข้อมูลชั้นเรียน
        die("อยู่ระหว่างการพัฒนา");
        break;
}

// ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
if (!file_exists($file_path)) {
    die("ไม่พบไฟล์ตัวอย่าง");
}

// กำหนดส่วนหัวสำหรับการดาวน์โหลด
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// ส่งไฟล์ไปยังผู้ใช้
readfile($file_path);
exit;
?>
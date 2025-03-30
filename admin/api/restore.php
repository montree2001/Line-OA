<?php
// api/restore.php - API endpoint สำหรับการกู้คืนข้อมูล

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'POST':
        // กู้คืนฐานข้อมูล
        restoreDatabase();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับกู้คืนฐานข้อมูล
function restoreDatabase() {
    try {
        // ตรวจสอบว่ามีไฟล์ที่อัปโหลดหรือไม่
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('ไม่มีไฟล์สำรองที่อัปโหลดหรือเกิดข้อผิดพลาดในการอัปโหลด');
        }
        
        // รับไฟล์ที่อัปโหลด
        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $originalFilename = $_FILES['backup_file']['name'];
        
        // ตรวจสอบว่าเป็นไฟล์ SQL หรือไม่
        $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        if ($fileExtension !== 'sql') {
            throw new Exception('รูปแบบไฟล์ไม่ถูกต้อง อนุญาตเฉพาะไฟล์ SQL เท่านั้น');
        }
        
        // สร้างคำสั่ง mysql เพื่อกู้คืนฐานข้อมูล
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($uploadedFile)
        );
        
        // เรียกใช้คำสั่ง mysql
        exec($command, $output, $returnValue);
        
        if ($returnValue !== 0) {
            throw new Exception('เกิดข้อผิดพลาดในการใช้คำสั่ง mysql: ' . implode("\n", $output));
        }
        
        // ส่งการตอบกลับ
        echo json_encode([
            'success' => true,
            'message' => 'กู้คืนข้อมูลเรียบร้อยแล้ว'
        ]);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการกู้คืนข้อมูล: ' . $e->getMessage()]);
    }
}
?>
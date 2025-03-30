<?php

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'POST':
        // สำรองฐานข้อมูล
        backupDatabase();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับสำรองฐานข้อมูล
function backupDatabase() {
    try {
        // ดึงค่าพาธที่เก็บไฟล์สำรองจาก settings
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'backup_path'");
        $stmt->execute();
        $backupPath = $stmt->fetchColumn() ?: 'backups';
        
        // ตรวจสอบและสร้างไดเร็กทอรีหากยังไม่มี
        if (!file_exists($backupPath)) {
            if (!mkdir($backupPath, 0755, true)) {
                throw new Exception('ไม่สามารถสร้างไดเร็กทอรีสำรองข้อมูลได้');
            }
        }
        
        // กำหนดชื่อไฟล์สำรอง
        $backupFile = $backupPath . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // สร้างคำสั่ง mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($backupFile)
        );
        
        // เรียกใช้คำสั่ง mysqldump
        exec($command, $output, $returnValue);
        
        if ($returnValue !== 0) {
            throw new Exception('เกิดข้อผิดพลาดในการใช้คำสั่ง mysqldump: ' . implode("\n", $output));
        }
        
        // สร้าง URL สำหรับดาวน์โหลดไฟล์สำรอง
        $downloadUrl = '/download.php?file=' . urlencode(basename($backupFile));
        
        // ส่งการตอบกลับ
        echo json_encode([
            'success' => true,
            'message' => 'สำรองข้อมูลเรียบร้อยแล้ว',
            'backup_file' => basename($backupFile),
            'download_url' => $downloadUrl
        ]);
        
        // ลบไฟล์สำรองเก่าถ้าต้องการ
        cleanupOldBackups($backupPath);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการสำรองข้อมูล: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับลบไฟล์สำรองเก่า
function cleanupOldBackups($backupPath) {
    try {
        // ดึงค่าจำนวนไฟล์สำรองที่เก็บจาก settings
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'backup_keep_count'");
        $stmt->execute();
        $keepCount = (int)($stmt->fetchColumn() ?: 5);
        
        // ถ้า keepCount เป็น 0 ให้เก็บทั้งหมด
        if ($keepCount === 0) {
            return;
        }
        
        // ดึงไฟล์สำรองทั้งหมด
        $files = glob($backupPath . '/backup_*.sql');
        
        // เรียงตามเวลาที่แก้ไข (ล่าสุดก่อน)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // เก็บเฉพาะจำนวนที่กำหนด
        for ($i = $keepCount; $i < count($files); $i++) {
            unlink($files[$i]);
        }
    } catch (Exception $e) {
        // บันทึกข้อผิดพลาดแต่ไม่ขัดจังหวะฟังก์ชันหลัก
        error_log('Error cleaning up old backups: ' . $e->getMessage());
    }
}
?>
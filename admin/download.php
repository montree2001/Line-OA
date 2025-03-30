<?php
// download.php - สคริปต์สำหรับดาวน์โหลดไฟล์สำรองข้อมูล

// รวม database connection เพื่อตรวจสอบสิทธิ์
require_once 'db_connect.php';

// เริ่ม session
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง (ต้องล็อกอินและเป็นแอดมิน)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo 'คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้';
    exit;
}

// ตรวจสอบว่ามีพารามิเตอร์ file หรือไม่
if (!isset($_GET['file'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'ไม่ระบุชื่อไฟล์';
    exit;
}

// ดึงชื่อไฟล์จาก URL และทำความสะอาด
$filename = basename($_GET['file']);

// ดึงค่าพาธที่เก็บไฟล์สำรองจาก settings
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'backup_path'");
    $stmt->execute();
    $backupPath = $stmt->fetchColumn() ?: 'backups';
} catch (PDOException $e) {
    error_log('Error getting backup path: ' . $e->getMessage());
    $backupPath = 'backups';
}

// ตรวจสอบว่าไฟล์มีอยู่หรือไม่
$filePath = $backupPath . '/' . $filename;
if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    echo 'ไม่พบไฟล์';
    exit;
}

// ตรวจสอบว่าไฟล์อยู่ในโฟลเดอร์ backup หรือไม่
$realPath = realpath($filePath);
$realBackupPath = realpath($backupPath);
if (strpos($realPath, $realBackupPath) !== 0) {
    header('HTTP/1.0 403 Forbidden');
    echo 'ไม่อนุญาตให้เข้าถึงไฟล์นี้';
    exit;
}

// ตั้งค่า header สำหรับดาวน์โหลด
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: no-cache');
header('Expires: 0');

// อ่านและส่งไฟล์
readfile($filePath);
exit;
<?php
/**
 * api/backup.php - API endpoint สำหรับการสำรองข้อมูล
 */

// เริ่ม session
session_start();

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึง']);
    exit;
} */

// เชื่อมต่อกับฐานข้อมูล
require_once '../../db_connect.php';

// เรียกใช้ค่า config
define('DB_NAME', 'stp_prasat'); // ชื่อฐานข้อมูล ใช้ตาม db_connect.php

// ตรวจสอบโฟลเดอร์สำรองข้อมูล
$backup_dir = isset($_SESSION['backup_path']) ? $_SESSION['backup_path'] : '../backups/';

if (!file_exists($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์สำรองข้อมูลได้']);
        exit;
    }
}

// สร้างชื่อไฟล์สำรองข้อมูล
$date = date("Y-m-d_H-i-s");
$backup_file = $backup_dir . 'backup_' . DB_NAME . '_' . $date . '.sql';

// คำสั่ง mysqldump สำหรับสำรองข้อมูล
// สำหรับ Linux/Mac
$command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file;

// สำหรับ Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $command = "mysqldump -u " . DB_USER . " -p" . DB_PASS . " -h " . DB_HOST . " " . DB_NAME . " > " . $backup_file;
}

// ทำการสำรองข้อมูล
exec($command, $output, $result);

// ตรวจสอบผลลัพธ์
if ($result !== 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'การสำรองข้อมูลล้มเหลว']);
    exit;
}

// บีบอัดไฟล์
$zip_file = $backup_file . '.zip';
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
    $zip->addFile($backup_file, basename($backup_file));
    $zip->close();
    
    // ลบไฟล์ SQL หลังจากบีบอัดแล้ว
    unlink($backup_file);
    
    // กำหนด URL สำหรับดาวน์โหลด
    $download_url = str_replace('../', '', $zip_file);
    $download_url = '../' . $download_url;
    
    // บันทึกการดำเนินการในตาราง admin_actions
    try {
        $conn = getDB();
        $action_details = json_encode(['backup_file' => basename($zip_file)]);
        
        $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, action_details) VALUES (?, 'backup_database', ?)");
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->bindParam(2, $action_details);
        $stmt->execute();
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาด แต่ไม่ส่งกลับให้ผู้ใช้
        error_log("Error recording backup action: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'สำรองข้อมูลเรียบร้อยแล้ว',
        'backup_file' => basename($zip_file),
        'download_url' => $download_url
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'การบีบอัดไฟล์ล้มเหลว']);
}
?>
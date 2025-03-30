<?php
/**
 * api/restore.php - API endpoint สำหรับการกู้คืนข้อมูล
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

// ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์สำรองข้อมูล']);
    exit;
}

// ตรวจสอบนามสกุลไฟล์
$file_extension = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
if (!in_array($file_extension, ['sql', 'gz', 'zip'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'รูปแบบไฟล์ไม่ถูกต้อง (รองรับ .sql, .gz, .zip เท่านั้น)']);
    exit;
}

// โฟลเดอร์ชั่วคราวสำหรับการกู้คืน
$temp_dir = sys_get_temp_dir() . '/restore_' . time() . '/';
if (!mkdir($temp_dir, 0755, true)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างโฟลเดอร์ชั่วคราวได้']);
    exit;
}

// ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์ชั่วคราว
$uploaded_file = $temp_dir . $_FILES['backup_file']['name'];
if (!move_uploaded_file($_FILES['backup_file']['tmp_name'], $uploaded_file)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถย้ายไฟล์ที่อัปโหลดได้']);
    exit;
}

// ตรวจสอบและแตกไฟล์ ZIP หรือ GZ หากจำเป็น
$sql_file = '';
if ($file_extension == 'zip') {
    $zip = new ZipArchive();
    if ($zip->open($uploaded_file) === TRUE) {
        // ค้นหาไฟล์ SQL ในไฟล์ ZIP
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file_name = $zip->getNameIndex($i);
            if (pathinfo($file_name, PATHINFO_EXTENSION) == 'sql') {
                $zip->extractTo($temp_dir, $file_name);
                $sql_file = $temp_dir . $file_name;
                break;
            }
        }
        $zip->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเปิดไฟล์ ZIP ได้']);
        exit;
    }
} elseif ($file_extension == 'gz') {
    $gz_file = $uploaded_file;
    $sql_file = $temp_dir . pathinfo($uploaded_file, PATHINFO_FILENAME) . '.sql';
    $command = "gunzip -c " . $gz_file . " > " . $sql_file;
    exec($command, $output, $result);
    if ($result !== 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถแตกไฟล์ GZ ได้']);
        exit;
    }
} else {
    // กรณีเป็นไฟล์ SQL
    $sql_file = $uploaded_file;
}

// ตรวจสอบว่าพบไฟล์ SQL หรือไม่
if (empty($sql_file) || !file_exists($sql_file)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ SQL ในไฟล์สำรองข้อมูล']);
    exit;
}

// กู้คืนข้อมูลจากไฟล์ SQL
// สำหรับ Linux/Mac
$command = "mysql --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " < " . $sql_file;

// สำหรับ Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $command = "mysql -u " . DB_USER . " -p" . DB_PASS . " -h " . DB_HOST . " " . DB_NAME . " < " . $sql_file;
}

exec($command, $output, $result);

// ลบไฟล์ชั่วคราว
deleteDir($temp_dir);

// ตรวจสอบผลลัพธ์
if ($result !== 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'การกู้คืนข้อมูลล้มเหลว']);
    exit;
}

// บันทึกการดำเนินการในตาราง admin_actions
try {
    $conn = getDB();
    $action_details = json_encode(['restore_file' => $_FILES['backup_file']['name']]);
    
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, action_details) VALUES (?, 'restore_database', ?)");
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->bindParam(2, $action_details);
    $stmt->execute();
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาด แต่ไม่ส่งกลับให้ผู้ใช้
    error_log("Error recording restore action: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'กู้คืนข้อมูลเรียบร้อยแล้ว']);

/**
 * ลบโฟลเดอร์และไฟล์ทั้งหมดในโฟลเดอร์
 */
function deleteDir($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}
?>
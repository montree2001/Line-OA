<?php
session_start();
require_once 'config/db_config.php';
require_once 'lib/line_api.php';
require_once 'config_manager.php'; // เพิ่มไฟล์ ConfigManager

// ดึงการตั้งค่า LINE จากฐานข้อมูล
$configManager = ConfigManager::getInstance();
$lineSettings = $configManager->getLineSettings();

// ตรวจสอบพารามิเตอร์ state เพื่อระบุบทบาท
$role = isset($_GET['state']) ? $_GET['state'] : '';

if (!in_array($role, ['student', 'teacher', 'parent', 'admin'])) {
    echo "ไม่สามารถระบุบทบาทได้";
    exit;
}

// บันทึกบทบาทไว้ในเซสชัน
$_SESSION['role'] = $role;

// ใช้การตั้งค่าให้ถูกต้องตามบทบาท
if ($configManager->getBoolSetting('single_line_oa', true)) {
    // กรณีใช้ LINE OA เดียว
    $client_id = $lineSettings['client_id'];
    $client_secret = $lineSettings['client_secret'];
    $redirect_uri = $lineSettings['redirect_uri'];
} else {
    // กรณีใช้หลาย LINE OA
    $client_id = $lineSettings[$role]['client_id'];
    $client_secret = $lineSettings[$role]['client_secret'];
    $redirect_uri = $lineSettings[$role]['redirect_uri'];
}

// สร้างอ็อบเจ็กต์ LINE API
$line_api = new LineAPI($client_id, $client_secret, $redirect_uri);

// สร้าง URL สำหรับ LINE Login
$login_url = $line_api->getLoginUrl($role);

// เปลี่ยนเส้นทางไปยัง LINE Login
header('Location: ' . $login_url);
exit;
?>
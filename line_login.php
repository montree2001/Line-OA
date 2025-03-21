<?php
session_start();
require_once 'config/db_config.php';
require_once 'lib/line_api.php';

// กำหนดค่า LINE Login
$client_id = '2007088707'; // แก้เป็น Client ID จริงของคุณ
$client_secret = 'ebd6dffa14e54908a835c59c3bd3a7cf'; // แก้เป็น Client Secret จริงของคุณ
$redirect_uri = 'https://c0a7-202-29-240-27.ngrok-free.app/line-OA/callback.php';

// สร้างอ็อบเจ็กต์ LINE API
$line_api = new LineAPI($client_id, $client_secret, $redirect_uri);

// ตรวจสอบพารามิเตอร์ state เพื่อระบุบทบาท
$role = isset($_GET['state']) ? $_GET['state'] : '';

if (!in_array($role, ['student', 'teacher', 'parent', 'admin'])) {
    echo "ไม่สามารถระบุบทบาทได้";
    exit;
}

// บันทึกบทบาทไว้ในเซสชัน
$_SESSION['role'] = $role;

// สร้าง URL สำหรับ LINE Login
$login_url = $line_api->getLoginUrl($role);

// เปลี่ยนเส้นทางไปยัง LINE Login
header('Location: ' . $login_url);
exit;
?>
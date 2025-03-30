<?php
// api/index.php - หน้าแรกของ API โดยเป็นหน้าสำหรับปกป้องไม่ให้เข้าถึง API โดยตรง

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// ตรวจสอบ Referer เพื่อป้องกันการเข้าถึงโดยตรง
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// ตรวจสอบว่า Referer มาจากเซิร์ฟเวอร์เดียวกันหรือไม่ หรือมาจาก localhost
if (empty($referer) || (strpos($referer, $host) === false && strpos($referer, 'localhost') === false)) {
    // ถ้าไม่ใช่การเรียกจากเซิร์ฟเวอร์เดียวกัน ให้ปฏิเสธการเข้าถึง
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// ตรวจสอบการล็อกอิน (อาจจะตรวจสอบจาก session หรือ token)
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ถ้าผ่านการตรวจสอบทั้งหมด ให้แสดงข้อมูล API
echo json_encode([
    'success' => true,
    'message' => 'ยินดีต้อนรับสู่ API ของระบบน้องชูใจ AI ดูแลผู้เรียน',
    'version' => '1.0.0',
    'endpoints' => [
        '/api/settings.php',
        '/api/academic-years.php',
        '/api/backup.php',
        '/api/restore.php',
        '/api/test-sms.php',
        '/api/update-liff.php',
        '/api/update-rich-menu.php'
    ]
]);
?>
<?php
/**
 * API สำหรับสร้างรหัส PIN
 */
session_start();
header('Content-Type: application/json');

// เปิด error reporting เพื่อช่วยในการดีบัก
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการสร้าง PIN'
    ]);
    exit;
}

// ตรวจสอบว่ารับข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'ต้องใช้วิธี POST เท่านั้น'
    ]);
    exit;
}

// รับและแปลงข้อมูล JSON
$input_json = file_get_contents('php://input');
if (empty($input_json)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูล JSON ที่ส่งมา'
    ]);
    exit;
}

$input_data = json_decode($input_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูล JSON ไม่ถูกต้อง: ' . json_last_error_msg()
    ]);
    exit;
}

// ตรวจสอบว่ามีการระบุ class_id หรือไม่
if (!isset($input_data['class_id']) || empty($input_data['class_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสห้องเรียน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

// เก็บค่าที่ส่งมา
$user_id = $_SESSION['user_id'];
$class_id = intval($input_data['class_id']);

// ตรวจสอบสิทธิ์ในการสร้าง PIN สำหรับห้องเรียนนี้
if ($_SESSION['role'] === 'teacher') {
    $check_permission_query = "SELECT ca.class_id 
                              FROM class_advisors ca 
                              JOIN teachers t ON ca.teacher_id = t.teacher_id 
                              WHERE t.user_id = ? AND ca.class_id = ?";
    
    $stmt = $conn->prepare($check_permission_query);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'เตรียมคำสั่ง SQL ล้มเหลว: ' . $conn->error
        ]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์ในการสร้าง PIN สำหรับห้องเรียนนี้'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
}

// ตรวจสอบรหัสปีการศึกษาปัจจุบัน
$academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
$academic_year_result = $conn->query($academic_year_query);
if (!$academic_year_result) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถดึงข้อมูลปีการศึกษา: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

if ($academic_year_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน'
    ]);
    $conn->close();
    exit;
}
$academic_year_data = $academic_year_result->fetch_assoc();
$academic_year_id = $academic_year_data['academic_year_id'];

// ดึงการตั้งค่าเกี่ยวกับ PIN
$settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'";
$settings_result = $conn->query($settings_query);
if (!$settings_result) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถดึงการตั้งค่า PIN: ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$settings_data = $settings_result->fetch_assoc();
$pin_expiration_minutes = $settings_data ? intval($settings_data['setting_value']) : 10;

// สร้างรหัส PIN 4 หลักแบบสุ่ม
$pin_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

// กำหนดเวลาหมดอายุ
$valid_from = date('Y-m-d H:i:s');
$valid_until = date('Y-m-d H:i:s', time() + ($pin_expiration_minutes * 60));

// ยกเลิก PIN เก่าที่ยังใช้งานได้
$deactivate_query = "UPDATE pins SET is_active = 0 WHERE creator_user_id = ? AND class_id = ? AND is_active = 1";
$deactivate_stmt = $conn->prepare($deactivate_query);
if (!$deactivate_stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'เตรียมคำสั่ง SQL ล้มเหลว (deactivate): ' . $conn->error
    ]);
    $conn->close();
    exit;
}

$deactivate_stmt->bind_param("ii", $user_id, $class_id);
$deactivate_result = $deactivate_stmt->execute();
if (!$deactivate_result) {
    echo json_encode([
        'success' => false,
        'message' => 'ยกเลิก PIN เก่าล้มเหลว: ' . $deactivate_stmt->error
    ]);
    $deactivate_stmt->close();
    $conn->close();
    exit;
}
$deactivate_stmt->close();

// บันทึก PIN ใหม่ - ใช้ SQL ตรงๆ เพื่อหลีกเลี่ยงปัญหากับ prepared statement
$sql = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id) 
       VALUES ('$pin_code', $user_id, $academic_year_id, '$valid_from', '$valid_until', 1, $class_id)";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'บันทึกข้อมูลล้มเหลว: ' . $conn->error,
        'sql' => $sql
    ]);
    $conn->close();
    exit;
}

// สำเร็จ
echo json_encode([
    'success' => true,
    'pin_code' => $pin_code,
    'expire_minutes' => $pin_expiration_minutes,
    'valid_until' => $valid_until
]);

// ปิดการเชื่อมต่อ
$conn->close();
?>
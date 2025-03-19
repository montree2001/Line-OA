<?php
session_start();
require_once '../config/db_config.php';

// ตรวจสอบว่าเป็นคำขอ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// รับข้อมูล JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($data['line_id']) || !isset($data['role'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// รับข้อมูลจาก JSON
$line_id = $data['line_id'];
$display_name = $data['display_name'] ?? '';
$picture_url = $data['picture_url'] ?? '';
$role = $data['role'];

// ตรวจสอบบทบาท
if (!in_array($role, ['student', 'teacher', 'parent', 'admin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'บทบาทไม่ถูกต้อง']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit;
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
$stmt = $conn->prepare("SELECT user_id, role, first_name, last_name FROM users WHERE line_id = ?");
$stmt->bind_param("s", $line_id);
$stmt->execute();
$result = $stmt->get_result();

// ตัวแปรสำหรับเก็บข้อมูลผู้ใช้และ URL ที่จะเปลี่ยนเส้นทาง
$user_id = null;
$first_name = null;
$last_name = null;
$redirect_url = '';

if ($result->num_rows > 0) {
    // ผู้ใช้มีอยู่แล้ว
    $user_data = $result->fetch_assoc();
    $user_id = $user_data['user_id'];
    $first_name = $user_data['first_name'];
    $last_name = $user_data['last_name'];
    
    // อัปเดตข้อมูลรูปโปรไฟล์
    $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $picture_url, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // ใช้บทบาทที่มีอยู่แล้ว
    $role = $user_data['role'];
} else {
    // ผู้ใช้ใหม่ - เพิ่มข้อมูล
    $insert_stmt = $conn->prepare("INSERT INTO users (line_id, role, profile_picture, created_at) VALUES (?, ?, ?, NOW())");
    $insert_stmt->bind_param("sss", $line_id, $role, $picture_url);
    
    if ($insert_stmt->execute()) {
        $user_id = $conn->insert_id;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถสร้างผู้ใช้ใหม่ได้']);
        exit;
    }
    
    $insert_stmt->close();
}

// ปิดการเชื่อมต่อฐานข้อมูล
$stmt->close();
$conn->close();

// บันทึกข้อมูลใน session
$_SESSION['user_id'] = $user_id;
$_SESSION['line_id'] = $line_id;
$_SESSION['role'] = $role;
$_SESSION['logged_in'] = true;
$_SESSION['first_name'] = $first_name;
$_SESSION['last_name'] = $last_name;

// กำหนด URL ที่จะเปลี่ยนเส้นทางตามบทบาท
switch ($role) {
    case 'student':
        // ตรวจสอบว่ามีข้อมูลเพิ่มเติมหรือไม่
        if ($first_name === null) {
            $redirect_url = '../student/register.php';
        } else {
            $redirect_url = '../student/dashboard.php';
        }
        break;
    case 'teacher':
        // ตรวจสอบว่ามีข้อมูลเพิ่มเติมหรือไม่
        if ($first_name === null) {
            $redirect_url = '../teacher/register.php';
        } else {
            $redirect_url = '../teacher/dashboard.php';
        }
        break;
    case 'parent':
        // ตรวจสอบว่ามีข้อมูลเพิ่มเติมหรือไม่
        if ($first_name === null) {
            $redirect_url = '../parent/register.php';
        } else {
            $redirect_url = '../parent/dashboard.php';
        }
        break;
    case 'admin':
        $redirect_url = '../admin/dashboard.php';
        break;
    default:
        $redirect_url = '../index.php';
}

// ส่งคำตอบกลับ
echo json_encode([
    'success' => true,
    'user_id' => $user_id,
    'role' => $role,
    'redirect_url' => $redirect_url
]);
?>
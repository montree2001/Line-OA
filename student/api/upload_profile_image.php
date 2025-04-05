<?php
/**
 * api/upload_profile_image.php - API อัพโหลดรูปโปรไฟล์
 */
session_start();
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ตั้งค่า header สำหรับการตอบกลับเป็น JSON
header('Content-Type: application/json');

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

// ตรวจสอบว่ามีการส่งไฟล์มาหรือไม่
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] != 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์รูปภาพ']);
    exit;
}

// ตรวจสอบประเภทไฟล์
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = $_FILES['profile_image']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'รูปแบบไฟล์ไม่ถูกต้อง กรุณาใช้ไฟล์ jpg, png หรือ gif เท่านั้น']);
    exit;
}

// ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
$max_size = 5 * 1024 * 1024; // 5MB

if ($_FILES['profile_image']['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'ขนาดไฟล์ใหญ่เกินไป กรุณาใช้ไฟล์ขนาดไม่เกิน 5MB']);
    exit;
}

// ดึง user_id จาก session หรือ POST data
$user_id = $_SESSION['user_id'] ?? null;

if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    // ตรวจสอบสิทธิ์ในการอัพเดทข้อมูล (เฉพาะกรณีที่ไม่ใช่ user ของตัวเอง)
    if ($_POST['user_id'] != $user_id && $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์อัพเดทข้อมูลของผู้ใช้นี้']);
        exit;
    }
    
    $user_id = $_POST['user_id'];
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
    exit;
}

// สร้างชื่อไฟล์ใหม่
$file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
$new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;

// กำหนด path ที่จะเก็บไฟล์
$upload_directory = '../../uploads/profiles/';

// สร้างโฟลเดอร์หากยังไม่มี
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0755, true);
}

$file_path = $upload_directory . $new_filename;
$db_file_path = 'uploads/profiles/' . $new_filename; // path ที่จะเก็บในฐานข้อมูล

// ย้ายไฟล์ไปยังโฟลเดอร์ปลายทาง
if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $file_path)) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพโหลด กรุณาลองใหม่อีกครั้ง']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลโปรไฟล์เดิม (หากมี)
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $old_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // อัพเดทข้อมูลในฐานข้อมูล
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$db_file_path, $user_id]);
    
    // ลบไฟล์เก่า (ถ้ามี)
    if ($old_profile && !empty($old_profile['profile_picture'])) {
        $old_file_path = '../../' . $old_profile['profile_picture'];
        if (file_exists($old_file_path) && $old_file_path != $file_path) {
            @unlink($old_file_path);
        }
    }
    
    // คืนค่าสำเร็จพร้อม URL ของรูปภาพ
    $image_url = '../' . $db_file_path; // URL สำหรับเรียกใช้ในหน้าเว็บ
    
    echo json_encode([
        'success' => true, 
        'message' => 'อัพโหลดรูปโปรไฟล์สำเร็จ',
        'image_url' => $image_url
    ]);
    
} catch (PDOException $e) {
    // ลบไฟล์ที่อัพโหลดในกรณีที่เกิดข้อผิดพลาด
    @unlink($file_path);
    
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()]);
    exit;
}
?>
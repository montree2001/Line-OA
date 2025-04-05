<?php
/**
 * api/upload_profile_photo.php - API สำหรับอัพโหลดรูปโปรไฟล์
 */
session_start();
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ใช้งานส่วนนี้']);
    exit;
}

// ตรวจสอบว่ามีการส่งไฟล์มาหรือไม่
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์รูปภาพ หรือมีข้อผิดพลาดในการอัพโหลด']);
    exit;
}

// ตรวจสอบว่ามีการส่ง student_id มาหรือไม่
if (!isset($_POST['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = $_POST['student_id'];
$user_id = $_SESSION['user_id'];

// ตรวจสอบไฟล์รูปภาพ
$file = $_FILES['photo'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// ตรวจสอบนามสกุลไฟล์
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_extension, $allowed_extensions)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น (jpg, jpeg, png, gif)']);
    exit;
}

// ตรวจสอบขนาดไฟล์
if ($file['size'] > $max_file_size) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ขนาดไฟล์ต้องไม่เกิน 5MB']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // สร้างชื่อไฟล์ใหม่
    $new_filename = 'profile_' . $student_id . '_' . time() . '.' . $file_extension;
    
    // โฟลเดอร์สำหรับเก็บรูปโปรไฟล์
    $upload_dir = '../uploads/profiles/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // อัพโหลดไฟล์
    $upload_path = $upload_dir . $new_filename;
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // อัพเดทตาราง users
        $stmt = $conn->prepare("
            UPDATE users 
            SET profile_picture = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $profile_pic_path = 'uploads/profiles/' . $new_filename;
        $stmt->execute([$profile_pic_path, $user_id]);
        
        // คืนค่าสำเร็จ
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'อัพโหลดรูปโปรไฟล์สำเร็จ',
            'photo_url' => $profile_pic_path
        ]);
    } else {
        // กรณีอัพโหลดไม่สำเร็จ
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัพโหลดไฟล์ได้']);
        exit;
    }
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการอัพเดทฐานข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
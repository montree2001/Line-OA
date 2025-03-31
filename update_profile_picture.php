<?php
/**
 * update_profile_picture.php - อัปเดตรูปโปรไฟล์ LINE ลงในฐานข้อมูล
 */
session_start();
require_once 'config/db_config.php';
require_once 'db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ล็อกอิน']);
    exit;
}

// รับข้อมูลจาก POST
$user_id = $_SESSION['user_id'] ?? null;
$profile_picture = $_POST['profile_picture'] ?? null;

// ตรวจสอบว่ามีข้อมูลที่จำเป็นหรือไม่
if (!$user_id || !$profile_picture || !filter_var($profile_picture, FILTER_VALIDATE_URL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // อัพเดตรูปโปรไฟล์ในฐานข้อมูล
    $stmt = $conn->prepare("
        UPDATE users
        SET profile_picture = ?, updated_at = NOW()
        WHERE user_id = ?
    ");
    
    $result = $stmt->execute([$profile_picture, $user_id]);
    
    if ($result) {
        // เก็บ URL รูปภาพในเซสชัน เพื่อไม่ต้องดึงจากฐานข้อมูลทุกครั้ง
        $_SESSION['profile_picture'] = $profile_picture;
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'อัปเดตรูปโปรไฟล์เรียบร้อย']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตรูปโปรไฟล์ได้']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?> 
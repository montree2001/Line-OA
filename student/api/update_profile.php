<?php
session_start();
require_once '../../config/db_config.php';
require_once '../../db_connect.php';
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาล็อกอินก่อนใช้งาน']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$response = ['status' => 'error', 'message' => 'ไม่สามารถประมวลผลข้อมูลได้'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getDB();
        
        // ข้อมูลที่อนุญาตให้อัปเดต
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $line_id = $_POST['line_id'] ?? '';
        
        // ตรวจสอบความถูกต้องของข้อมูล
        $errors = [];
        
        if (empty($phone)) {
            $errors[] = 'เบอร์โทรศัพท์ไม่ควรเป็นค่าว่าง';
        } elseif (!preg_match('/^[0-9\-]{9,15}$/', $phone)) {
            $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        }
        
        if (empty($email)) {
            $errors[] = 'อีเมลไม่ควรเป็นค่าว่าง';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }
        
        if (!empty($errors)) {
            $response = ['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง', 'errors' => $errors];
        } else {
            // อัปเดตข้อมูลในตาราง users
            $stmt = $conn->prepare("
                UPDATE users 
                SET phone_number = ?, email = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$phone, $email, $user_id]);
            
            // อัปเดตข้อมูลในตาราง students
            $stmt = $conn->prepare("
                UPDATE students 
                SET line_id = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$line_id, $user_id]);
            
            $response = [
                'status' => 'success', 
                'message' => 'อัปเดตข้อมูลสำเร็จ',
                'data' => [
                    'phone' => $phone,
                    'email' => $email,
                    'line_id' => $line_id
                ]
            ];
        }
    } catch (PDOException $e) {
        error_log("API Error (update_profile): " . $e->getMessage());
        $response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล'];
    }
}

echo json_encode($response);
?> 
<?php
/**
 * api/check_existing_attendance.php - API ตรวจสอบว่ามีข้อมูลการเช็คชื่อแล้วหรือไม่
 */
session_start();
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

// ตรวจสอบว่าเป็นบทบาท admin เท่านั้น
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ใช้งานส่วนนี้']);
    exit;
} */

// รับพารามิเตอร์
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// ตรวจสอบความถูกต้องของพารามิเตอร์
if ($student_id <= 0 || empty($date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// ตรวจสอบรูปแบบวันที่
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'รูปแบบวันที่ไม่ถูกต้อง (YYYY-MM-DD)']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ตรวจสอบว่ามีข้อมูลการเช็คชื่อแล้วหรือไม่
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student_id, $date]);
    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    
    if ($existing_attendance) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'attendance_id' => $existing_attendance['attendance_id']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
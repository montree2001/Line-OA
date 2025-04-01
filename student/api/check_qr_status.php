<?php
/**
 * api/check_qr_status.php - API สำหรับตรวจสอบสถานะ QR Code
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

// รับข้อมูล POST เป็น JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = $data['student_id'];

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ตรวจสอบว่าได้เช็คชื่อแล้วหรือยัง
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student_id, $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($today_attendance) {
        // ถ้าเช็คชื่อแล้ว ตรวจสอบว่าเช็คชื่อด้วย QR Code หรือไม่
        $is_qr_checkin = $today_attendance['check_method'] === 'QR_Code';
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'is_checked_in' => true,
            'check_method' => $today_attendance['check_method'],
            'check_time' => $today_attendance['check_time'],
            'is_qr_used' => $is_qr_checkin
        ]);
        exit;
    }
    
    // ตรวจสอบว่ามี QR Code ที่ยังไม่หมดอายุหรือไม่
    $stmt = $conn->prepare("
        SELECT * FROM qr_codes 
        WHERE student_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $active_qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$active_qr) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่พบ QR Code ที่ยังไม่หมดอายุ',
            'expired' => true
        ]);
        exit;
    }
    
    // ตรวจสอบว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time'
    ");
    $stmt->execute();
    $start_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '07:30';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '08:30';
    
    $current_time = date('H:i');
    
    if ($current_time < $start_time || $current_time > $end_time) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่อยู่ในช่วงเวลาเช็คชื่อ (' . $start_time . ' - ' . $end_time . ' น.)'
        ]);
        exit;
    }
    
    // คืนค่าสถานะ QR Code
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'is_checked_in' => false,
        'qr_id' => $active_qr['qr_code_id'],
        'expires_at' => $active_qr['valid_until']
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
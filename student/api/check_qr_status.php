<?php
/**
 * api/check_qr_status.php - API สำหรับตรวจสอบสถานะ QR Code (แก้ไขแล้ว)
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
            'attendance_status' => $today_attendance['attendance_status'],
            'is_qr_used' => $is_qr_checkin,
            'message' => 'คุณได้เช็คชื่อไปแล้วในวันนี้'
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
            'message' => 'ไม่อยู่ในช่วงเวลาเช็คชื่อ (' . $start_time . ' - ' . $end_time . ' น.)',
            'attendance_period' => [
                'start' => $start_time,
                'end' => $end_time,
                'current' => $current_time
            ]
        ]);
        exit;
    }
    
    // ตรวจสอบว่ามี QR Code ที่ยังไม่หมดอายุหรือไม่
    $stmt = $conn->prepare("
        SELECT qc.*, s.student_code 
        FROM qr_codes qc
        JOIN students s ON qc.student_id = s.student_id
        WHERE qc.student_id = ? AND qc.is_active = 1 AND qc.valid_until > NOW()
        ORDER BY qc.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $active_qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$active_qr) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่พบ QR Code ที่ยังไม่หมดอายุ กรุณาสร้าง QR Code ใหม่',
            'expired' => true,
            'can_generate' => true
        ]);
        exit;
    }
    
    // ตรวจสอบข้อมูล QR Code
    $qr_data = json_decode($active_qr['qr_code_data'], true);
    
    // ตรวจสอบว่า QR Code มีรูปแบบที่ถูกต้องหรือไม่
    if (!$qr_data || !isset($qr_data['type']) || !isset($qr_data['student_id'])) {
        // สร้าง QR Code ใหม่ที่มีรูปแบบถูกต้อง
        $token = md5(time() . $student_id . $active_qr['student_code'] . rand(1000, 9999));
        $new_qr_data = [
            'type' => 'student_attendance', // ใช้ type ที่ถูกต้อง
            'student_id' => (int)$student_id,
            'student_code' => $active_qr['student_code'],
            'token' => $token,
            'expire_time' => $active_qr['valid_until'],
            'generated_at' => $active_qr['created_at']
        ];
        
        // อัปเดต QR Code ในฐานข้อมูล
        $stmt = $conn->prepare("
            UPDATE qr_codes 
            SET qr_code_data = ? 
            WHERE qr_code_id = ?
        ");
        $stmt->execute([
            json_encode($new_qr_data),
            $active_qr['qr_code_id']
        ]);
        
        $qr_data = $new_qr_data;
    }
    
    // คำนวณเวลาที่เหลือ (ในวินาที)
    $expire_timestamp = strtotime($active_qr['valid_until']);
    $current_timestamp = time();
    $time_remaining = $expire_timestamp - $current_timestamp;
    
    // คืนค่าสถานะ QR Code
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'is_checked_in' => false,
        'qr_id' => $active_qr['qr_code_id'],
        'qr_data' => $qr_data,
        'expires_at' => $active_qr['valid_until'],
        'time_remaining' => $time_remaining,
        'time_remaining_formatted' => gmdate('i:s', max(0, $time_remaining)),
        'is_expired' => $time_remaining <= 0,
        'qr_string' => json_encode($qr_data), // เพิ่มสำหรับการสร้าง QR Code
        'attendance_period' => [
            'start' => $start_time,
            'end' => $end_time,
            'current' => $current_time
        ]
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    error_log("Database error in check_qr_status.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง',
        'error_code' => 'DB_ERROR'
    ]);
    exit;
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาดอื่นๆ
    error_log("General error in check_qr_status.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ]);
    exit;
}
?>
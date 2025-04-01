<?php
/**
 * api/generate_qr.php - API สำหรับสร้าง QR Code
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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'คุณได้เช็คชื่อไปแล้วในวันนี้']);
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
        echo json_encode(['success' => false, 'message' => 'ไม่อยู่ในช่วงเวลาเช็คชื่อ (' . $start_time . ' - ' . $end_time . ' น.)']);
        exit;
    }

    // ดึงข้อมูลนักเรียนเพื่อใช้ในการสร้าง QR Code
    $stmt = $conn->prepare("
        SELECT student_code FROM students WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
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
    $existing_qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_qr) {
        // ใช้ QR Code ที่มีอยู่แล้ว
        $qr_data = json_decode($existing_qr['qr_code_data'], true);
        $expire_time = $existing_qr['valid_until'];
    } else {
        // สร้าง QR Code ใหม่
        // กำหนดเวลาหมดอายุ (5 นาที)
        $valid_from = new DateTime();
        $valid_until = clone $valid_from;
        $valid_until->add(new DateInterval('PT5M')); // เพิ่ม 5 นาที
        
        // สร้างข้อมูลสำหรับ QR Code
        $token = md5(time() . $student_id . $student['student_code'] . rand(1000, 9999));
        $qr_data = [
            'type' => 'student_link',
            'student_id' => $student_id,
            'student_code' => $student['student_code'],
            'token' => $token,
            'expire_time' => $valid_until->format('Y-m-d H:i:s')
        ];
        
        // บันทึกข้อมูล QR Code ลงฐานข้อมูล
        $stmt = $conn->prepare("
            INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $student_id,
            json_encode($qr_data),
            $valid_from->format('Y-m-d H:i:s'),
            $valid_until->format('Y-m-d H:i:s')
        ]);
        
        $expire_time = $valid_until->format('Y-m-d H:i:s');
    }
    
    // คืนค่าสำเร็จ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'qr_data' => $qr_data,
        'expire_time' => $expire_time
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
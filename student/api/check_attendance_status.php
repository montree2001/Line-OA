<?php
/**
 * api/check_attendance_status.php - API สำหรับตรวจสอบสถานะการเช็คชื่อของนักเรียน
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

// รับข้อมูล
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;
$student_id = $data['student_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลนักเรียน
    if (!$student_id) {
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $student_id = $student['student_id'] ?? null;
    }
    
    if (!$student_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ตรวจสอบการเช็คชื่อวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT a.*, u.first_name, u.last_name 
        FROM attendance a
        LEFT JOIN users u ON a.checker_user_id = u.user_id
        WHERE a.student_id = ? AND a.date = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        // มีการเช็คชื่อแล้ว
        $checker_name = '';
        if ($attendance['checker_user_id']) {
            $checker_name = $attendance['first_name'] . ' ' . $attendance['last_name'];
        }
        
        // ตรวจสอบว่าใช้ QR Code หรือไม่
        $is_qr_used = $attendance['check_method'] === 'QR_Code';
        
        // หากใช้ QR Code ให้ปิดการใช้งาน QR Code
        if ($is_qr_used) {
            $stmt = $conn->prepare("
                UPDATE qr_codes 
                SET is_active = 0 
                WHERE student_id = ? AND is_active = 1
            ");
            $stmt->execute([$student_id]);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_checked_in' => true,
            'attendance' => [
                'status' => $attendance['attendance_status'],
                'method' => $attendance['check_method'],
                'time' => $attendance['check_time'],
                'date' => $attendance['date'],
                'checker' => $checker_name,
                'remarks' => $attendance['remarks']
            ],
            'qr_used' => $is_qr_used,
            'message' => 'คุณได้เช็คชื่อแล้ววันนี้'
        ]);
        exit;
    }
    
    // ยังไม่มีการเช็คชื่อ - ตรวจสอบ QR Code ที่ยังใช้งานได้
    $stmt = $conn->prepare("
        SELECT * FROM qr_codes 
        WHERE student_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id]);
    $qr_code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($qr_code) {
        // มี QR Code ที่ยังใช้งานได้
        $qr_data = json_decode($qr_code['qr_code_data'], true);
        $expire_timestamp = strtotime($qr_code['valid_until']);
        $current_timestamp = time();
        $time_remaining = $expire_timestamp - $current_timestamp;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_checked_in' => false,
            'has_active_qr' => true,
            'qr_info' => [
                'qr_code_id' => $qr_code['qr_code_id'],
                'qr_data' => $qr_data,
                'expires_at' => $qr_code['valid_until'],
                'time_remaining' => max(0, $time_remaining),
                'time_remaining_formatted' => gmdate('H:i:s', max(0, $time_remaining)),
                'is_expired' => $time_remaining <= 0
            ],
            'message' => 'QR Code ของคุณยังใช้งานได้ ให้ครูสแกนเพื่อเช็คชื่อ'
        ]);
    } else {
        // ไม่มี QR Code ที่ใช้งานได้
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_checked_in' => false,
            'has_active_qr' => false,
            'message' => 'ยังไม่มีการเช็คชื่อวันนี้ และไม่มี QR Code ที่ใช้งานได้'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in check_attendance_status.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ',
        'error_code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("General error in check_attendance_status.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>
<?php
/**
 * api/scan_qr_check_in.php - API สำหรับเช็คชื่อด้วยการสแกน QR Code
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

if (!isset($data['student_id']) || !isset($data['qr_data'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = $data['student_id'];
$qr_data = $data['qr_data'];

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
    
    // ถอดรหัส QR Code
    $qr_data_decoded = null;
    
    // ตรวจสอบว่า QR ที่สแกนได้เป็น JSON หรือไม่
    if (substr($qr_data, 0, 1) === '{') {
        $qr_data_decoded = json_decode($qr_data, true);
    } else {
        // ไม่ใช่ JSON ลองตรวจสอบว่าเป็น PIN หรือไม่
        if (strlen($qr_data) === 4 && is_numeric($qr_data)) {
            // น่าจะเป็น PIN ให้เรียกใช้ API check_in_pin.php แทน
            $_POST['student_id'] = $student_id;
            $_POST['pin'] = $qr_data;
            
            include 'check_in_pin.php';
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'รูปแบบ QR Code ไม่ถูกต้อง']);
            exit;
        }
    }
    
    // ตรวจสอบประเภทของ QR Code
    if ($qr_data_decoded['type'] === 'check_in') {
        // QR Code สำหรับการเช็คชื่อโดยตรง
        $check_in_token = $qr_data_decoded['token'] ?? '';
        $valid_until = $qr_data_decoded['valid_until'] ?? date('Y-m-d H:i:s');
        
        // ตรวจสอบว่า QR Code หมดอายุหรือยัง
        if (strtotime($valid_until) < time()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'QR Code หมดอายุแล้ว']);
            exit;
        }
        
        // ตรวจสอบความถูกต้องของ token
        $stmt = $conn->prepare("
            SELECT * FROM qr_tokens 
            WHERE token = ? AND valid_until >= NOW() AND is_active = 1
        ");
        $stmt->execute([$check_in_token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$token_data) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token ไม่ถูกต้องหรือหมดอายุ']);
            exit;
        }
        
    } else if ($qr_data_decoded['type'] === 'pin') {
        // QR Code ที่มี PIN ให้เรียกใช้ API check_in_pin.php แทน
        $_POST['student_id'] = $student_id;
        $_POST['pin'] = $qr_data_decoded['pin'];
        
        include 'check_in_pin.php';
        exit;
        
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ประเภท QR Code ไม่ถูกต้อง']);
        exit;
    }
    
    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    if (!$current_academic_year_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    // บันทึกการเช็คชื่อ
    $stmt = $conn->prepare("
        INSERT INTO attendance (student_id, academic_year_id, date, is_present, check_method, 
                               check_time, created_at)
        VALUES (?, ?, ?, 1, 'QR_Code', NOW(), NOW())
    ");
    $stmt->execute([
        $student_id,
        $current_academic_year_id,
        $today
    ]);
    
    // อัพเดทสถิติการเข้าแถวในตาราง student_academic_records
    $stmt = $conn->prepare("
        UPDATE student_academic_records 
        SET total_attendance_days = total_attendance_days + 1, 
            updated_at = NOW()
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $current_academic_year_id]);
    
    // คืนค่าสำเร็จ
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'เช็คชื่อสำเร็จ']);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
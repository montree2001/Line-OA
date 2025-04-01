<?php
/**
 * api/check_in_pin.php - API สำหรับเช็คชื่อด้วย PIN
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

if (!isset($data['student_id']) || !isset($data['pin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = $data['student_id'];
$pin = $data['pin'];

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
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.*, c.class_id 
        FROM students s
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ตรวจสอบ PIN
    $stmt = $conn->prepare("
        SELECT * FROM pins 
        WHERE pin_code = ? AND is_active = 1 AND valid_from <= NOW() AND valid_until >= NOW()
    ");
    $stmt->execute([$pin]);
    $pin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pin_data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัส PIN ไม่ถูกต้องหรือหมดอายุ']);
        exit;
    }
    
    // ตรวจสอบว่า PIN นี้ใช้ได้กับห้องเรียนที่นักเรียนอยู่หรือไม่
    if ($pin_data['class_id'] !== null && $pin_data['class_id'] != $student['class_id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัส PIN นี้ไม่สามารถใช้กับห้องเรียนของคุณได้']);
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
                               pin_code, check_time, created_at)
        VALUES (?, ?, ?, 1, 'PIN', ?, NOW(), NOW())
    ");
    $stmt->execute([
        $student_id,
        $current_academic_year_id,
        $today,
        $pin
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
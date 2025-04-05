<?php
/**
 * api/create_test_attendance.php - API สร้างข้อมูลการเช็คชื่อสำหรับการทดสอบ
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

// รับข้อมูล POST เป็น JSON
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบความถูกต้องของข้อมูล
if (!isset($data['student_id']) || !isset($data['academic_year_id']) || !isset($data['date']) || !isset($data['check_method'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = intval($data['student_id']);
$academic_year_id = intval($data['academic_year_id']);
$date = $data['date'];
$check_method = $data['check_method'];
$location_lat = $data['location_lat'] ?? null;
$location_lng = $data['location_lng'] ?? null;
$check_time = $data['check_time'] ?? date('H:i:s');

// ตรวจสอบความถูกต้องของพารามิเตอร์
if ($student_id <= 0 || $academic_year_id <= 0 || empty($date) || empty($check_method)) {
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

// ตรวจสอบวิธีการเช็คชื่อ
$valid_methods = ['GPS', 'QR_Code', 'PIN', 'Manual'];
if (!in_array($check_method, $valid_methods)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'วิธีการเช็คชื่อไม่ถูกต้อง']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ตรวจสอบว่าได้เช็คชื่อแล้วหรือยัง
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student_id, $date]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($today_attendance) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'มีข้อมูลการเช็คชื่อแล้ว']);
        exit;
    }
    
    // สร้างรหัส PIN หากจำเป็น
    $pin_code = null;
    if ($check_method === 'PIN') {
        $pin_code = mt_rand(1000, 9999);
    }
    
    // บันทึกข้อมูลการเช็คชื่อ
    $stmt = $conn->prepare("
        INSERT INTO attendance (
            student_id, academic_year_id, date, is_present, check_method, 
            location_lat, location_lng, pin_code, check_time, created_at, remarks
        )
        VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, NOW(), 'สร้างโดยระบบทดสอบ')
    ");
    $stmt->execute([
        $student_id,
        $academic_year_id,
        $date,
        $check_method,
        $location_lat,
        $location_lng,
        $pin_code,
        $check_time
    ]);
    
    $attendance_id = $conn->lastInsertId();
    
    // อัพเดทสถิติการเข้าแถวในตาราง student_academic_records
    $stmt = $conn->prepare("
        UPDATE student_academic_records 
        SET total_attendance_days = total_attendance_days + 1, 
            updated_at = NOW()
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $academic_year_id]);
    
    // ตอบกลับ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'สร้างข้อมูลการเช็คชื่อสำเร็จ',
        'attendance_id' => $attendance_id
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
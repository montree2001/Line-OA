<?php
/**
 * api/generate_qr.php - API สำหรับสร้าง QR Code (แก้ไขแล้ว)
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

// ตรวจสอบว่ามีการส่งข้อมูลแบบ application/json หรือไม่
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    // รับข้อมูล JSON
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    // รับข้อมูลแบบ form data
    $data = $_POST;
}

// รับค่า student_id จาก request หรือจาก session
$student_id = isset($data['student_id']) ? $data['student_id'] : $_SESSION['user_id'];

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
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่อยู่ในช่วงเวลาเช็คชื่อ (' . $start_time . ' - ' . $end_time . ' น.)',
            'current_time' => $current_time,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
        exit;
    }

    // ดึงข้อมูลนักเรียนเพื่อใช้ในการสร้าง QR Code
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ปิดการใช้งาน QR Code เก่าที่ยังใช้งานได้
    $stmt = $conn->prepare("
        UPDATE qr_codes 
        SET is_active = 0 
        WHERE student_id = ? AND is_active = 1
    ");
    $stmt->execute([$student_id]);
    
    // กำหนดเวลาหมดอายุ (7 วัน)
    $valid_from = new DateTime();
    $valid_until = clone $valid_from;
    $valid_until->add(new DateInterval('P7D')); // เพิ่ม 7 วัน
    
    // สร้างข้อมูลสำหรับ QR Code
    $token = hash('sha256', time() . $student_id . $student['student_code'] . rand(1000, 9999));
    $qr_data = [
        'type' => 'student_attendance', // ใช้ type ที่ถูกต้อง
        'student_id' => (int)$student_id,
        'student_code' => $student['student_code'], // แก้ไขจาก $active_qr['student_code'] เป็น $student['student_code']
        'token' => $token,
        'generated_at' => date('Y-m-d H:i:s'),
        'expires_at' => $valid_until->format('Y-m-d H:i:s'),
        'for_date' => date('Y-m-d')
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
    
    $qr_code_id = $conn->lastInsertId();
    
    // คืนค่าสำเร็จ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'qr_data' => $qr_data,
        'qr_code_id' => $qr_code_id,
        'expire_time' => $valid_until->format('Y-m-d H:i:s'),
        'qr_string' => json_encode($qr_data), // สำหรับการสร้าง QR Code
        'message' => 'สร้าง QR Code สำเร็จ แสดงให้ครูสแกนเพื่อเช็คชื่อ'
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    error_log("Database error in generate_qr.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาดอื่น ๆ
    error_log("General error in generate_qr.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()]);
    exit;
}
?>
<?php
/**
 * generate_student_qr.php - API สำหรับสร้าง QR Code นักเรียน
 * ใช้สำหรับสร้าง QR Code สำหรับนักเรียนผ่าน AJAX
 */
session_start();
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับข้อมูล JSON จาก request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id']) || empty($data['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ระบุรหัสนักเรียน']);
    exit;
}

$student_id = intval($data['student_id']);
$qr_validity = isset($data['qr_validity']) ? intval($data['qr_validity']) : 7;
$start_date = isset($data['start_date']) && !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
$expiry_date = isset($data['expiry_date']) && !empty($data['expiry_date']) ? $data['expiry_date'] : '';

// ตรวจสอบค่า validity
if ($qr_validity < 1) $qr_validity = 1;
if ($qr_validity > 365) $qr_validity = 365;

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ตรวจสอบสิทธิ์การเข้าถึงข้อมูลนักเรียน (สำหรับครู)
    if ($_SESSION['user_role'] === 'teacher') {
        $teacher_id = null;
        
        // ดึงข้อมูล teacher_id
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลครูผู้สอน']);
            exit;
        }
        
        $teacher_id = $teacher['teacher_id'];
        
        // ตรวจสอบว่านักเรียนอยู่ในห้องที่ครูเป็นที่ปรึกษาหรือไม่
        $stmt = $conn->prepare("
            SELECT s.student_id
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN class_advisors ca ON c.class_id = ca.class_id
            WHERE s.student_id = ? AND ca.teacher_id = ?
        ");
        $stmt->execute([$student_id, $teacher_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้']);
            exit;
        }
    }
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_code, s.title, u.first_name, u.last_name 
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
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
    
    // กำหนดเวลาเริ่มต้น
    $valid_from = new DateTime($start_date);
    
    // กำหนดเวลาหมดอายุ
    if (!empty($expiry_date)) {
        // ใช้วันหมดอายุที่กำหนด
        $valid_until = new DateTime($expiry_date);
        // ตั้งเวลาเป็น 23:59:59
        $valid_until->setTime(23, 59, 59);
    } else {
        // คำนวณจากจำนวนวันที่กำหนด
        $valid_until = clone $valid_from;
        $valid_until->add(new DateInterval('P' . $qr_validity . 'D'));
    }
    
    // สร้างข้อมูลสำหรับ QR Code
    $token = hash('sha256', time() . $student_id . $student['student_code'] . rand(1000, 9999));
    $qr_data = [
        'type' => 'student_attendance',
        'student_id' => (int)$student_id,
        'student_code' => $student['student_code'],
        'token' => $token,
        'generated_at' => $valid_from->format('Y-m-d H:i:s'),
        'expires_at' => $valid_until->format('Y-m-d H:i:s')
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
    
    // บันทึกประวัติการพิมพ์ QR Code
    $stmt = $conn->prepare("
        INSERT INTO qr_print_history (
            qr_code_id, 
            student_id, 
            printed_by, 
            print_date, 
            validity_days, 
            expire_date, 
            printer_role,
            notes
        )
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([
        $qr_code_id,
        $student_id,
        $_SESSION['user_id'],
        $qr_validity,
        $valid_until->format('Y-m-d H:i:s'),
        $_SESSION['user_role'],
        'พิมพ์ QR Code รายบุคคลผ่าน Modal'
    ]);
    
    // ส่งผลลัพธ์กลับ
    echo json_encode([
        'success' => true,
        'qr_code_id' => $qr_code_id,
        'qr_data' => $qr_data,
        'expire_time' => $valid_until->format('Y-m-d H:i:s'),
        'message' => 'สร้าง QR Code สำเร็จ'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in generate_student_qr.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบฐานข้อมูล กรุณาลองใหม่อีกครั้ง',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in generate_student_qr.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
        'error' => $e->getMessage()
    ]);
}
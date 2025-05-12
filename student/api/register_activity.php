<?php
/**
 * api/register_activity.php - API สำหรับลงทะเบียนเข้าร่วมกิจกรรม
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

if (!isset($data['activity_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$activity_id = $data['activity_id'];
$user_id = $_SESSION['user_id'];

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_id FROM students s
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    $student_id = $student['student_id'];
    
    // ตรวจสอบว่ากิจกรรมมีอยู่จริงหรือไม่
    $stmt = $conn->prepare("SELECT * FROM activities WHERE activity_id = ?");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }
    
    // ตรวจสอบว่าลงทะเบียนแล้วหรือไม่
    $stmt = $conn->prepare("
        SELECT * FROM activity_attendance 
        WHERE activity_id = ? AND student_id = ?
    ");
    $stmt->execute([$activity_id, $student_id]);
    $existing_registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_registration) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'คุณได้ลงทะเบียนกิจกรรมนี้ไปแล้ว']);
        exit;
    }
    
    // ตรวจสอบว่ากิจกรรมผ่านไปแล้วหรือไม่
    $activity_date = new DateTime($activity['activity_date']);
    $current_date = new DateTime();
    
    if ($activity_date < $current_date) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลงทะเบียนกิจกรรมที่ผ่านไปแล้วได้']);
        exit;
    }
    
    // บันทึกข้อมูลการลงทะเบียน
    $stmt = $conn->prepare("
        INSERT INTO activity_attendance (
            activity_id, 
            student_id, 
            attendance_status, 
            recorder_id, 
            record_time,
            remarks
        ) VALUES (?, ?, 'present', ?, NOW(), 'ลงทะเบียนผ่านระบบออนไลน์')
    ");
    
    $stmt->execute([
        $activity_id,
        $student_id,
        $user_id // ใช้ ID ของนักเรียนเป็นผู้บันทึก
    ]);
    
    // คืนค่าสำเร็จ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'ลงทะเบียนเข้าร่วมกิจกรรมสำเร็จ'
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}
?>
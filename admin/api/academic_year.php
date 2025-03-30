<?php
/**
 * api/academic_year.php - API endpoint สำหรับการจัดการปีการศึกษา
 */

// เริ่ม session
session_start();

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึง']);
    exit;
} */

// เชื่อมต่อกับฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

// รับข้อมูล JSON จาก request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($data['year']) || !isset($data['semester']) || !isset($data['start_date']) || !isset($data['end_date'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// เตรียมตรวจสอบว่ามีปีการศึกษาและภาคเรียนนี้อยู่แล้วหรือไม่
try {
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? AND semester = ?");
    $stmt->bindParam(1, $data['year']);
    $stmt->bindParam(2, $data['semester']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ปีการศึกษาและภาคเรียนนี้มีอยู่แล้ว']);
        exit;
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล: ' . $e->getMessage()]);
    exit;
}

// เตรียมข้อมูลสำหรับการบันทึก
$year = $data['year'];
$semester = $data['semester'];
$start_date = $data['start_date'];
$end_date = $data['end_date'];
$is_active = isset($data['is_active']) && $data['is_active'] ? 1 : 0;
$required_attendance_days = isset($data['required_attendance_days']) ? $data['required_attendance_days'] : 80;

// เริ่ม transaction
$conn->beginTransaction();

try {
    // หากปีการศึกษาใหม่เป็นปีการศึกษาปัจจุบัน ให้ยกเลิกสถานะปัจจุบันของปีการศึกษาเดิม
    if ($is_active) {
        $stmt = $conn->prepare("UPDATE academic_years SET is_active = 0");
        $stmt->execute();
    }
    
    // เพิ่มปีการศึกษาใหม่
    $stmt = $conn->prepare("INSERT INTO academic_years (year, semester, start_date, end_date, required_attendance_days, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $year);
    $stmt->bindParam(2, $semester);
    $stmt->bindParam(3, $start_date);
    $stmt->bindParam(4, $end_date);
    $stmt->bindParam(5, $required_attendance_days);
    $stmt->bindParam(6, $is_active);
    $stmt->execute();
    
    $academic_year_id = $conn->lastInsertId();
    
    // บันทึกการดำเนินการในตาราง admin_actions
    $action_details = json_encode([
        'action' => 'create_academic_year',
        'academic_year_id' => $academic_year_id,
        'year' => $year,
        'semester' => $semester,
        'is_active' => $is_active
    ]);
    
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, action_details) VALUES (?, 'create_academic_year', ?)");
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->bindParam(2, $action_details);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'เพิ่มปีการศึกษาใหม่เรียบร้อยแล้ว',
        'academic_year_id' => $academic_year_id
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction
    $conn->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา: ' . $e->getMessage()]);
}
?>
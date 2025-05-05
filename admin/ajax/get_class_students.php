<?php
/**
 * get_class_students.php - ไฟล์สำหรับดึงข้อมูลนักเรียนในห้องเรียนผ่าน AJAX
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json'); // กำหนด content type เป็น JSON ทุกกรณี

/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$level = isset($_GET['level']) ? $_GET['level'] : '';
$room = isset($_GET['room']) ? $_GET['room'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Debug - บันทึกข้อมูลที่ได้รับ
error_log("get_class_students.php - Parameters: level=$level, room=$room, date=$date");

if (empty($level) || empty($room)) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุห้องเรียน']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // ดึงข้อมูลห้องเรียน
    $stmt = $conn->prepare("
        SELECT class_id FROM classes 
        WHERE level = ? AND group_number = ? AND academic_year_id = ?
        LIMIT 1
    ");
    $stmt->execute([$level, $room, $academic_year_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug - บันทึกข้อมูลห้องเรียนที่ค้นพบ
    error_log("get_class_students.php - Class found: " . ($class ? "Yes, ID: " . $class['class_id'] : "No"));
    
    if (!$class) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลห้องเรียน']);
        exit;
    }
    
    $class_id = $class['class_id'];
    
    // ดึงรายชื่อนักเรียนในห้องเรียน
    $sql = "
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
               s.status
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
        ORDER BY s.student_code
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug - บันทึกจำนวนนักเรียนที่พบ
    error_log("get_class_students.php - Students found: " . count($students));
    
    // ดึงข้อมูลการเช็คชื่อของวันที่ระบุ
    $attendance_data = [];
    if (!empty($students)) {
        $student_ids = array_column($students, 'student_id');
        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
        
        $sql = "
            SELECT student_id, attendance_status, check_time, remarks
            FROM attendance
            WHERE student_id IN ($placeholders) AND date = ?
        ";
        
        $params = array_merge($student_ids, [$date]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attendance_data[$row['student_id']] = $row;
        }
        
        // Debug - บันทึกข้อมูลการเช็คชื่อที่พบ
        error_log("get_class_students.php - Attendance records found: " . count($attendance_data));
    }
    
    // รวมข้อมูลนักเรียนและการเช็คชื่อ
    $result = [];
    foreach ($students as $student) {
        $student_data = $student;
        
        // เพิ่มข้อมูลการเช็คชื่อ (ถ้ามี)
        if (isset($attendance_data[$student['student_id']])) {
            $student_data['attendance_status'] = $attendance_data[$student['student_id']]['attendance_status'];
            $student_data['check_time'] = date('H:i', strtotime($attendance_data[$student['student_id']]['check_time']));
            $student_data['remarks'] = $attendance_data[$student['student_id']]['remarks'];
        } else {
            $student_data['attendance_status'] = null;
            $student_data['check_time'] = null;
            $student_data['remarks'] = null;
        }
        
        $result[] = $student_data;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode(['success' => true, 'students' => $result]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_class_students.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
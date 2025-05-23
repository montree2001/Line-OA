<?php
/**
 * get_student.php - ไฟล์สำหรับดึงข้อมูลนักเรียนผ่าน AJAX
 * ปรับปรุงให้รองรับการค้นหาด้วย student_id และ student_code
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$student_code = isset($_GET['student_code']) ? $_GET['student_code'] : '';

if (!$student_id && !$student_code) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสนักเรียน']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // สร้างคำสั่ง SQL ตามเงื่อนไข
    $sql = "
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name, u.profile_picture
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.status = 'กำลังศึกษา' AND ";
    
    if ($student_id) {
        $sql .= "s.student_id = :id";
        $param_name = ':id';
        $param_value = $student_id;
    } else {
        $sql .= "s.student_code = :code";
        $param_name = ':code';
        $param_value = $student_code;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam($param_name, $param_value);
    $stmt->execute();
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // จัดเตรียมข้อมูลนักเรียนสำหรับส่งกลับ
    $student_data = [
        'student_id' => $student['student_id'],
        'student_code' => $student['student_code'],
        'title' => $student['title'],
        'first_name' => $student['first_name'],
        'last_name' => $student['last_name'],
        'class' => $student['level'] . '/' . $student['group_number'] . ' ' . $student['department_name'],
        'level' => $student['level'],
        'group_number' => $student['group_number'],
        'department_name' => $student['department_name'],
        'profile_picture' => $student['profile_picture']
    ];
    
    // ดึงข้อมูลการเช็คชื่อวันนี้ (ถ้ามี)
    $today = date('Y-m-d');
    $sql = "
        SELECT a.attendance_id, a.attendance_status, a.check_time, a.check_method, a.remarks
        FROM attendance a
        WHERE a.student_id = :student_id AND a.date = :date
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student['student_id']);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        $student_data['attendance'] = [
            'status' => $attendance['attendance_status'],
            'check_time' => date('H:i', strtotime($attendance['check_time'])),
            'method' => $attendance['check_method'],
            'remarks' => $attendance['remarks']
        ];
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode(['success' => true, 'student' => $student_data]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_student.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()]);
}
?>
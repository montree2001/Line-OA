<?php
/**
 * API ดึงข้อมูลการเช็คชื่อล่าสุด
 */
session_start();
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการเข้าถึงข้อมูล'
    ]);
    exit;
}

// ตรวจสอบว่ามีการระบุ class_id หรือไม่
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสห้องเรียน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว'
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

// เก็บค่า class_id
$user_id = $_SESSION['user_id'];
$class_id = intval($_GET['class_id']);

// ตรวจสอบสิทธิ์ในการดูข้อมูลห้องเรียนนี้
if ($_SESSION['role'] === 'teacher') {
    $check_permission_query = "SELECT ca.class_id 
                              FROM class_advisors ca 
                              JOIN teachers t ON ca.teacher_id = t.teacher_id 
                              WHERE t.user_id = ? AND ca.class_id = ?";
    
    $stmt = $conn->prepare($check_permission_query);
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์ในการดูข้อมูลห้องเรียนนี้'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
}

// หาสถิติการเข้าแถววันนี้
$today = date('Y-m-d');
$attendance_stats_query = "SELECT 
                           COUNT(DISTINCT s.student_id) as total_students,
                           SUM(CASE WHEN a.attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as present_count,
                           SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                           COUNT(a.attendance_id) as checked_count
                          FROM students s
                          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                          WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";

$stats_stmt = $conn->prepare($attendance_stats_query);
$stats_stmt->bind_param("si", $today, $class_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats_data = $stats_result->fetch_assoc();

$total_students = $stats_data['total_students'];
$present_count = $stats_data['present_count'] ?: 0;
$absent_count = $stats_data['absent_count'] ?: 0;
$checked_count = $stats_data['checked_count'] ?: 0;
$not_checked = $total_students - $checked_count;

// ดึงข้อมูลนักเรียน 5 คนล่าสุดที่เช็คชื่อ
$students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                  a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time,
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number
                 FROM attendance a
                 JOIN students s ON a.student_id = s.student_id
                 JOIN users u ON s.user_id = u.user_id
                 WHERE s.current_class_id = ? AND a.date = ?
                 ORDER BY a.check_time DESC
                 LIMIT 5";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("is", $class_id, $today);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

$students = [];
while ($student = $students_result->fetch_assoc()) {
    $students[] = [
        'id' => $student['student_id'],
        'number' => $student['number'],
        'student_code' => $student['student_code'],
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'status' => ($student['attendance_status'] === 'absent') ? 'absent' : 'present',
        'time' => $student['check_time']
    ];
}

// สร้างข้อมูลส่งกลับ
$response = [
    'success' => true,
    'stats' => [
        'total_students' => $total_students,
        'present_count' => $present_count,
        'absent_count' => $absent_count,
        'not_checked' => $not_checked
    ],
    'students' => $students
];

echo json_encode($response);

// ปิดการเชื่อมต่อ
$stats_stmt->close();
$students_stmt->close();
$conn->close();
?>
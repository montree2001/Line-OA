<?php
/**
 * auth_check.php - ไฟล์ตรวจสอบการเข้าสู่ระบบสำหรับครู
 */
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทครูหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'];
$line_id = $_SESSION['line_id'];
$profile_picture = $_SESSION['profile_picture'] ?? null;

// เชื่อมต่อฐานข้อมูล
require_once dirname(__FILE__) . '/../../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีข้อมูลครูแล้วหรือไม่
$stmt = $conn->prepare("
    SELECT t.*, u.first_name, u.last_name, u.title, u.phone_number, u.email, u.profile_picture, d.department_name 
    FROM teachers t
    JOIN users u ON t.user_id = u.user_id
    LEFT JOIN departments d ON t.department_id = d.department_id
    WHERE t.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ถ้าไม่พบข้อมูลครูในระบบ ให้ไปที่หน้าลงทะเบียน
if ($result->num_rows === 0) {
    header('Location: register.php');
    exit;
}

// ถ้ามีข้อมูลครู ดึงข้อมูลมาใช้งาน
$teacher_data = $result->fetch_assoc();
$teacher_id = $teacher_data['teacher_id'];
$teacher_name = $teacher_data['title'] . $teacher_data['first_name'] . ' ' . $teacher_data['last_name'];
$teacher_department = $teacher_data['department_name'];
$teacher_position = $teacher_data['position'];

// ดึงข้อมูลชั้นเรียนที่เป็นที่ปรึกษา
$class_stmt = $conn->prepare("
    SELECT c.class_id, c.level, c.group_number, d.department_name, ca.is_primary,
           (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
    FROM class_advisors ca
    JOIN classes c ON ca.class_id = c.class_id
    JOIN departments d ON c.department_id = d.department_id
    WHERE ca.teacher_id = ? AND c.is_active = 1
    ORDER BY ca.is_primary DESC, c.level, d.department_name, c.group_number
");

$class_stmt->bind_param("i", $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$teacher_classes = [];

while ($class = $class_result->fetch_assoc()) {
    $teacher_classes[] = $class;
}

// ปิดการเชื่อมต่อ
$stmt->close();
$class_stmt->close();
$conn->close();
?>
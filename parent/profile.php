<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าโปรไฟล์และแก้ไขข้อมูลส่วนตัวผู้ปกครอง
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - โปรไฟล์ผู้ปกครอง';
$current_page = 'profile';
$extra_css = [
    'assets/css/parent-profile.css'
];
$extra_js = [
    'assets/js/parent-profile.js'
];

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ปกครอง
$user_id = $_SESSION['user_id'];
$parent_id = null;
$parent_data = null;
$user_data = null;
$success_message = "";
$error_message = "";

// ดึงข้อมูลผู้ใช้
$user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
} else {
    $error_message = "ไม่พบข้อมูลผู้ใช้";
}
$user_stmt->close();

// ดึงข้อมูลผู้ปกครอง
$parent_stmt = $conn->prepare("SELECT * FROM parents WHERE user_id = ?");
$parent_stmt->bind_param("i", $user_id);
$parent_stmt->execute();
$parent_result = $parent_stmt->get_result();
if ($parent_result->num_rows > 0) {
    $parent_data = $parent_result->fetch_assoc();
    $parent_id = $parent_data['parent_id'];
} else {
    // ถ้ายังไม่ได้ลงทะเบียนเป็นผู้ปกครอง ให้ไปที่หน้าลงทะเบียน
    header('Location: register.php');
    exit;
}
$parent_stmt->close();

// จัดการการอัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $relationship = $conn->real_escape_string($_POST['relationship']);
    
    // อัปเดตข้อมูลในตาราง users
    $update_user_stmt = $conn->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
    $update_user_stmt->bind_param("sssssi", $title, $first_name, $last_name, $phone_number, $email, $user_id);
    
    if ($update_user_stmt->execute()) {
        // อัปเดตข้อมูลในตาราง parents
        $update_parent_stmt = $conn->prepare("UPDATE parents SET title = ?, relationship = ?, updated_at = NOW() WHERE parent_id = ?");
        $update_parent_stmt->bind_param("ssi", $title, $relationship, $parent_id);
        
        if ($update_parent_stmt->execute()) {
            $success_message = "อัปเดตข้อมูลเรียบร้อยแล้ว";
            
            // รีโหลดข้อมูลใหม่
            $user_stmt->execute();
            $user_data = $user_stmt->get_result()->fetch_assoc();
            
            $parent_stmt->execute();
            $parent_data = $parent_stmt->get_result()->fetch_assoc();
        } else {
            $error_message = "ไม่สามารถอัปเดตข้อมูลผู้ปกครองได้: " . $conn->error;
        }
        $update_parent_stmt->close();
    } else {
        $error_message = "ไม่สามารถอัปเดตข้อมูลผู้ใช้ได้: " . $conn->error;
    }
    $update_user_stmt->close();
}

// จัดการการเปลี่ยน Line ID (เฉพาะแอดมิน)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_line_id'])) {
    // แสดงข้อความแจ้งเตือนว่าไม่สามารถเปลี่ยน Line ID ได้
    $error_message = "ไม่สามารถเปลี่ยน Line ID ได้ กรุณาติดต่อผู้ดูแลระบบ";
}

// จัดการการเปลี่ยนรหัสผ่าน (ถ้ามี)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // แสดงข้อความแจ้งเตือนว่าไม่มีรหัสผ่านในระบบนี้
    $error_message = "ระบบนี้ใช้การยืนยันตัวตนผ่าน LINE ไม่มีรหัสผ่าน";
}

// จัดการการอัปโหลดรูปโปรไฟล์ (สมมติว่าปรับแค่ URL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_picture'])) {
    // แสดงข้อความแจ้งเตือนว่าใช้รูปโปรไฟล์จาก LINE
    $error_message = "รูปโปรไฟล์ถูกดึงมาจาก LINE โดยอัตโนมัติ ไม่สามารถเปลี่ยนได้";
}

// ดึงรายชื่อนักเรียนในความดูแล
$students = [];
$student_stmt = $conn->prepare("
    SELECT s.student_id, s.student_code, s.title AS student_title, 
           u.first_name, u.last_name, 
           c.level, c.group_number, d.department_name
    FROM parent_student_relation psr
    JOIN students s ON psr.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    LEFT JOIN departments d ON c.department_id = d.department_id
    WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
");
$student_stmt->bind_param("i", $parent_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

while ($row = $student_result->fetch_assoc()) {
    $full_name = $row['student_title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
    $class_name = isset($row['level']) ? $row['level'] . '/' . $row['group_number'] . ' ' . $row['department_name'] : 'ไม่ระบุชั้นเรียน';
    
    $students[] = [
        'id' => $row['student_id'],
        'student_code' => $row['student_code'],
        'name' => $full_name,
        'class' => $class_name
    ];
}
$student_stmt->close();

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/profile_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
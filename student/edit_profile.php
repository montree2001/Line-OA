<?php
/**
 * edit_profile.php - หน้าแก้ไขข้อมูลส่วนตัวของนักเรียน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ลดการแสดงข้อผิดพลาดในหน้าเว็บ แต่เก็บไว้ในล็อก
error_reporting(0);
ini_set('display_errors', 0);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'edit_profile';
$page_title = 'STD-Prasat - แก้ไขข้อมูลส่วนตัว';
$page_header = 'แก้ไขข้อมูลส่วนตัว';

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$message = '';
$error = '';

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ตรวจสอบหากมีการส่งข้อมูลการแก้ไข
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // รับข้อมูลจากฟอร์ม
        $title = $_POST['title'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $line_id = $_POST['line_id'] ?? '';
        
        // ตรวจสอบความถูกต้องของข้อมูล
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'กรุณาเลือกคำนำหน้า';
        }
        
        if (empty($first_name)) {
            $errors[] = 'กรุณากรอกชื่อ';
        }
        
        if (empty($last_name)) {
            $errors[] = 'กรุณากรอกนามสกุล';
        }
        
        if (empty($phone)) {
            $errors[] = 'เบอร์โทรศัพท์ไม่ควรเป็นค่าว่าง';
        } elseif (!preg_match('/^[0-9\-]{9,15}$/', $phone)) {
            $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }
        
        // ถ้าไม่มีข้อผิดพลาด ทำการอัปเดตข้อมูล
        if (empty($errors)) {
            try {
                // เริ่ม transaction
                $conn->beginTransaction();
                
                // อัปเดตข้อมูลในตาราง users
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$title, $first_name, $last_name, $phone, $email, $user_id]);
                
                // อัปเดตคำนำหน้าในตาราง students
                $stmt = $conn->prepare("
                    UPDATE students
                    SET title = ?, line_id = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$title, $line_id, $user_id]);
                
                // ยืนยัน transaction
                $conn->commit();
                $message = 'อัปเดตข้อมูลส่วนตัวสำเร็จ';
                
            } catch (Exception $e) {
                // ยกเลิก transaction
                $conn->rollBack();
                $error = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage();
            }
        } else {
            // มีข้อผิดพลาดในการตรวจสอบข้อมูล
            $error = implode('<br>', $errors);
        }
    }
    
    // ดึงข้อมูลนักเรียนเพื่อแสดงในฟอร์ม
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title as student_title, s.current_class_id, s.line_id,
               u.title as user_title, u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email, 
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // แปลงข้อมูลจากฐานข้อมูลเป็นรูปแบบที่ใช้ในหน้าเว็บ
    $student_id = $student_data['student_id'];
    $class_info = $student_data['level'] . ' ' . $student_data['department_name'] . ' กลุ่ม ' . $student_data['group_number'];
    $first_char = mb_substr($student_data['first_name'], 0, 1, 'UTF-8');
    
    // ใช้คำนำหน้าจากตาราง students หรือ users (ให้ความสำคัญกับ students มากกว่า)
    $title = !empty($student_data['student_title']) ? $student_data['student_title'] : $student_data['user_title'];
    
    $student_info = [
        'id' => $student_id,
        'student_code' => $student_data['student_code'],
        'title' => $title,
        'first_name' => $student_data['first_name'],
        'last_name' => $student_data['last_name'],
        'full_name' => $title . $student_data['first_name'] . ' ' . $student_data['last_name'],
        'class' => $class_info,
        'department' => $student_data['department_name'],
        'phone' => $student_data['phone_number'],
        'email' => $student_data['email'],
        'line_id' => $student_data['line_id'],
        'avatar' => $first_char,
        'profile_image' => !empty($student_data['profile_picture']) ? $student_data['profile_picture'] : null
    ];
    
    // กำหนด CSS และ JS เพิ่มเติม
    $extra_css = [
        'assets/css/student-profile.css',
        'assets/css/student-form.css'
    ];
    $extra_js = [
        'assets/js/profile-image-upload.js',
        'assets/js/form-validation.js'
    ];
    
    // เส้นทางเนื้อหา
    $content_path = 'pages/edit_profile_content.php';
    
    // โหลดเทมเพลต
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาดแต่ไม่แสดงให้ผู้ใช้เห็น
    error_log("Database error in edit_profile.php: " . $e->getMessage());
    
    // ส่งไปยังหน้า error
    header('Location: error.php?msg=' . urlencode('เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล'));
    exit;
}
?>
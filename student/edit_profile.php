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
    
    // ตรวจสอบว่าการเชื่อมต่อสำเร็จหรือไม่
    if ($conn === null) {
        throw new Exception('ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้');
    }
    
    // ดึงข้อมูลแผนกวิชาทั้งหมดเพื่อใช้ในฟอร์ม
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบหากมีการส่งข้อมูลการแก้ไข
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // รับข้อมูลจากฟอร์ม
        $title = $_POST['title'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $student_code = $_POST['student_code'] ?? '';
        $level = $_POST['level'] ?? '';
        $department_id = $_POST['department_id'] ?? '';
        $group_number = $_POST['group_number'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        
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
        
        if (empty($student_code)) {
            $errors[] = 'กรุณากรอกรหัสนักศึกษา';
        } elseif (!preg_match('/^\d{11}$/', $student_code)) {
            $errors[] = 'รหัสนักศึกษาต้องเป็นตัวเลข 11 หลักเท่านั้น';
        }
        
        if (empty($level)) {
            $errors[] = 'กรุณาเลือกระดับชั้น';
        }
        
        if (empty($department_id)) {
            $errors[] = 'กรุณาเลือกแผนกวิชา';
        }
        
        if (empty($group_number)) {
            $errors[] = 'กรุณาเลือกกลุ่มเรียน';
        }
        
        if (empty($phone)) {
            $errors[] = 'เบอร์โทรศัพท์ไม่ควรเป็นค่าว่าง';
        } elseif (!preg_match('/^[0-9\-]{9,15}$/', $phone)) {
            $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }
        
        // ตรวจสอบว่ารหัสนักศึกษาซ้ำหรือไม่ (ยกเว้นรหัสของตัวเอง)
        $stmt = $conn->prepare("
            SELECT student_id FROM students 
            WHERE student_code = ? AND user_id != ?
        ");
        $stmt->execute([$student_code, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'รหัสนักศึกษานี้มีอยู่ในระบบแล้ว กรุณาตรวจสอบอีกครั้ง';
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
                
                // ตรวจสอบว่าชั้นเรียนมีอยู่หรือไม่ ถ้าไม่มีให้สร้างใหม่
                $stmt = $conn->prepare("
                    SELECT class_id FROM classes 
                    WHERE level = ? AND department_id = ? AND group_number = ?
                ");
                $stmt->execute([$level, $department_id, $group_number]);
                $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($class_data) {
                    $class_id = $class_data['class_id'];
                } else {
                    // สร้างชั้นเรียนใหม่
                    $stmt = $conn->prepare("
                        INSERT INTO classes (level, department_id, group_number, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$level, $department_id, $group_number]);
                    $class_id = $conn->lastInsertId();
                }
                
                // อัปเดตข้อมูลในตาราง students
                $stmt = $conn->prepare("
                    UPDATE students
                    SET title = ?, student_code = ?, current_class_id = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$title, $student_code, $class_id, $user_id]);
                
                // ยืนยัน transaction
                $conn->commit();
                $message = 'อัปเดตข้อมูลส่วนตัวสำเร็จ';
                
            } catch (Exception $e) {
                // ยกเลิก transaction
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $error = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage();
                error_log("Error updating profile: " . $e->getMessage());
            }
        } else {
            // มีข้อผิดพลาดในการตรวจสอบข้อมูล
            $error = implode('<br>', $errors);
        }
    }
    
    // ดึงข้อมูลนักเรียนเพื่อแสดงในฟอร์ม
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title as student_title, s.current_class_id,
               u.title as user_title, u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email, 
               c.level, c.group_number, c.department_id, d.department_name
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
        'department_id' => $student_data['department_id'],
        'level' => $student_data['level'],
        'group_number' => $student_data['group_number'],
        'phone' => $student_data['phone_number'],
        'email' => $student_data['email'],
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
    
    // ส่งไปยังหน้า error หรือกำหนดข้อความข้อผิดพลาด
    $error_message = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล';
    
    // ตรวจสอบว่ามีไฟล์ error.php หรือไม่
    if (file_exists('error.php')) {
        header('Location: error.php?msg=' . urlencode($error_message));
        exit;
    } else {
        // แสดงข้อความข้อผิดพลาดอย่างง่าย
        echo '<!DOCTYPE html>
              <html>
              <head>
                <meta charset="UTF-8">
                <title>ข้อผิดพลาด</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background-color: #f5f5f5;
                    }
                    .error-container {
                        max-width: 500px;
                        margin: 100px auto;
                        background: white;
                        padding: 20px;
                        border-radius: 5px;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        text-align: center;
                    }
                    .error-icon {
                        font-size: 48px;
                        color: #f44336;
                        margin-bottom: 20px;
                    }
                    .error-message {
                        margin-bottom: 20px;
                    }
                    .back-button {
                        background-color: #4CAF50;
                        color: white;
                        padding: 10px 15px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        text-decoration: none;
                        display: inline-block;
                    }
                </style>
              </head>
              <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <div class="error-message">' . $error_message . '</div>
                    <a href="student_profile.php" class="back-button">กลับไปยังหน้าโปรไฟล์</a>
                </div>
              </body>
              </html>';
        exit;
    }
}
?>
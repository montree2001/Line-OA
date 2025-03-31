<?php
/**
 * student/check-in.php - หน้าเช็คชื่อสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// รับข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // ดึงข้อมูลนักเรียน
    $student_sql = "SELECT s.*, u.first_name, u.last_name, u.profile_picture
                   FROM students s
                   JOIN users u ON s.user_id = u.user_id
                   WHERE s.user_id = ?";
    $stmt = $conn->prepare($student_sql);
    
    if (!$stmt) {
        throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: " . $stmt->error);
    }

    if ($result->num_rows === 0) {
        // ไม่พบข้อมูลนักเรียน
        header('Location: register.php');
        exit;
    }

    $student = $result->fetch_assoc();
    $student_id = $student['student_id'];

    // ตรวจสอบการตั้งค่าการเช็คชื่อ
    $settings_sql = "SELECT as.* 
                    FROM attendance_settings as
                    JOIN academic_years ay ON as.academic_year_id = ay.academic_year_id
                    WHERE ay.is_active = 1";
    $settings_result = $conn->query($settings_sql);

    if (!$settings_result) {
        throw new Exception("เกิดข้อผิดพลาดในการดึงข้อมูลการตั้งค่า: " . $conn->error);
    }

    if ($settings_result->num_rows === 0) {
        $error_message = "ไม่พบการตั้งค่าการเช็คชื่อ กรุณาติดต่อผู้ดูแลระบบ";
        $settings = [
            'attendance_start_time' => '08:00:00',
            'attendance_end_time' => '08:30:00',
            'gps_center_lat' => 0,
            'gps_center_lng' => 0,
            'gps_radius' => 100
        ];
        $can_check_in = false;
    } else {
        $settings = $settings_result->fetch_assoc();
        
        // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
        $current_time = date('H:i:s');
        $start_time = $settings['attendance_start_time'];
        $end_time = $settings['attendance_end_time'];
        
        $can_check_in = ($current_time >= $start_time && $current_time <= $end_time);
    }
    
    // ตรวจสอบว่าเช็คชื่อไปแล้วหรือยัง
    $today = date('Y-m-d');
    $check_attendance_sql = "SELECT * FROM attendance WHERE student_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_attendance_sql);
    
    if (!$check_stmt) {
        throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    
    $check_stmt->bind_param("is", $student_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if (!$check_result) {
        throw new Exception("เกิดข้อผิดพลาดในการตรวจสอบการเช็คชื่อ: " . $check_stmt->error);
    }
    
    $already_checked_in = ($check_result->num_rows > 0);
    $check_data = $already_checked_in ? $check_result->fetch_assoc() : null;
    $check_stmt->close();

    // ... ส่วนที่เหลือของโค้ดยังคงเหมือนเดิม ...
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $settings = [
        'attendance_start_time' => '08:00:00',
        'attendance_end_time' => '08:30:00',
        'gps_center_lat' => 0,
        'gps_center_lng' => 0,
        'gps_radius' => 100
    ];
    $can_check_in = false;
}

// ... ส่วนที่เหลือของโค้ดยังคงเหมือนเดิม ... 
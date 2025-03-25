<?php
/**
 * parent/register.php
 * หน้าแรกของกระบวนการลงทะเบียนผู้ปกครอง
 * ตรวจสอบสถานะการลงทะเบียนและเริ่มต้นกระบวนการ
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่าเคยลงทะเบียนแล้วหรือยัง
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ถ้าเคยลงทะเบียนแล้ว ให้ไปที่หน้า dashboard
    header('Location: dashboard.php');
    exit;
}

// ดึงข้อมูลผู้ใช้จาก users table
$stmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// ปิดการเชื่อมต่อฐานข้อมูล
$stmt->close();
$conn->close();

// เริ่มกระบวนการลงทะเบียน
// เก็บสถานะการลงทะเบียนลงใน session
$_SESSION['registration_step'] = 1;
$_SESSION['selected_students'] = [];

// ส่งต่อไปยังหน้าเลือกนักเรียน
header('Location: register_select_students.php');
exit;
?>
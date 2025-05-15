<?php
/**
 * get_classes.php - API สำหรับดึงข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบพารามิเตอร์
if (!isset($_GET['department_id']) || !isset($_GET['academic_year_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$department_id = $_GET['department_id'];
$academic_year_id = $_GET['academic_year_id'];

// ดึงข้อมูลชั้นเรียน
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT class_id, level, group_number 
        FROM classes 
        WHERE department_id = ? AND academic_year_id = ? AND is_active = 1 
        ORDER BY level, group_number
    ");
    $stmt->execute([$department_id, $academic_year_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับ
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'classes' => $classes]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน']);
    exit;
}
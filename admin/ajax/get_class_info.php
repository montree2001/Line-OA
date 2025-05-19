<?php
/**
 * get_class_info.php - ไฟล์สำหรับดึงข้อมูลชั้นเรียนตาม class_id
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$class_id) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสห้องเรียน']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลห้องเรียน
    $stmt = $conn->prepare("
        SELECT c.class_id, c.level, c.group_number, c.department_id, d.department_name
        FROM classes c
        JOIN departments d ON c.department_id = d.department_id
        WHERE c.class_id = ?
    ");
    $stmt->execute([$class_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลห้องเรียน']);
        exit;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'class_info' => $class_info
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_class_info.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>
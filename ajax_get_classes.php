<?php
/**
 * ajax_get_classes.php - API สำหรับดึงข้อมูลห้องเรียนตามแผนกวิชา (สาธารณะ)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

header('Content-Type: application/json');

try {
    // เชื่อมต่อฐานข้อมูล
    require_once 'db_connect.php';
    $conn = getDB();

    // ตรวจสอบว่ามีการส่ง department_id มาหรือไม่
    if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
        throw new Exception("กรุณาระบุรหัสแผนกวิชา");
    }
    
    $department_id = (int)$_GET['department_id'];
    
    // ดึงข้อมูลห้องเรียนในแผนกที่ระบุ
    $query = "
        SELECT c.class_id, c.level, c.group_number, d.department_name
        FROM classes c
        JOIN departments d ON c.department_id = d.department_id
        WHERE c.department_id = ? AND c.is_active = 1
        ORDER BY c.level, c.group_number
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งผลลัพธ์
    echo json_encode([
        'status' => 'success',
        'classes' => $classes,
        'count' => count($classes)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>
<?php
/**
 * ajax/get_classes_by_department.php - API สำหรับดึงข้อมูลห้องเรียนตามแผนกวิชา
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

try {
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    // ตรวจสอบว่ามีการส่ง department_id มาหรือไม่
    if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
        throw new Exception("กรุณาระบุรหัสแผนกวิชา");
    }
    
    $department_id = $_GET['department_id'];
    
    // ถ้าเป็นครูให้ดึงเฉพาะห้องที่เป็นที่ปรึกษา
    if ($_SESSION['user_role'] == 'teacher' && isset($_SESSION['teacher_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        
        $query = "SELECT DISTINCT c.class_id, c.level, c.group_number, d.department_name
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  JOIN class_advisors ca ON c.class_id = ca.class_id 
                  WHERE ca.teacher_id = ? AND c.department_id = ? AND c.academic_year_id = ? AND c.is_active = 1
                  ORDER BY c.level, c.group_number";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$teacher_id, $department_id, $academic_year['academic_year_id']]);
    } else {
        // ถ้าเป็นแอดมินให้ดึงทุกห้องในแผนกนั้น
        $query = "SELECT DISTINCT c.class_id, c.level, c.group_number, d.department_name
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE c.department_id = ? AND c.academic_year_id = ? AND c.is_active = 1
                  ORDER BY c.level, c.group_number";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$department_id, $academic_year['academic_year_id']]);
    }
    
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบและลบข้อมูลซ้ำ (เพิ่มเติม)
    $unique_classes = [];
    $keys = [];
    
    foreach ($classes as $class) {
        $key = $class['level'] . '/' . $class['group_number'];
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            $unique_classes[] = $class;
        }
    }
    
    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['classes' => $unique_classes, 'status' => 'success']);
} catch (Exception $e) {
    // ส่งข้อความแจ้งข้อผิดพลาดกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'status' => 'error']);
}
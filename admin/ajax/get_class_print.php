// แก้ไขไฟล์ ajax/get_classes_print.php ที่มีปัญหา
<?php
/**
 * ajax/get_classes.php - API สำหรับดึงข้อมูลห้องเรียนทั้งหมด
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'status' => 'error']);
    exit;
}

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
    
    // ตรวจสอบว่าเป็นครูหรือแอดมิน
    if ($_SESSION['user_role'] == 'teacher' && isset($_SESSION['teacher_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        
        // ดึงข้อมูลห้องเรียนที่ครูเป็นที่ปรึกษา
        $query = "SELECT c.class_id, c.level, c.group_number, d.department_name, 
                  CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  JOIN class_advisors ca ON c.class_id = ca.class_id 
                  WHERE ca.teacher_id = ? AND c.academic_year_id = ? AND c.is_active = 1 
                  ORDER BY c.level, c.group_number, d.department_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$teacher_id, $academic_year['academic_year_id']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // ดึงข้อมูลห้องเรียนทั้งหมดสำหรับแอดมิน
        $query = "SELECT c.class_id, c.level, c.group_number, d.department_name, 
                  CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE c.academic_year_id = ? AND c.is_active = 1 
                  ORDER BY c.level, c.group_number, d.department_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$academic_year['academic_year_id']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['classes' => $classes, 'status' => 'success']);
} catch (Exception $e) {
    // ส่งข้อความแจ้งข้อผิดพลาดกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'status' => 'error']);
}
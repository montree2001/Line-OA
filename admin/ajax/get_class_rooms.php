<?php
/**
 * get_class_rooms.php - ไฟล์สำหรับดึงข้อมูลห้องเรียนตามระดับชั้นผ่าน AJAX
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}
 */
// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$level = isset($_GET['level']) ? $_GET['level'] : '';

if (empty($level)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุระดับชั้น']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // กรณีเป็นครู ดึงเฉพาะห้องที่เป็นครูที่ปรึกษา
    if ($_SESSION['role'] == 'teacher') {
        // ดึงรหัสครู
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลครู']);
            exit;
        }
        
        $teacher_id = $teacher['teacher_id'];
        
        // ดึงห้องเรียนตามครูที่ปรึกษา
        $sql = "
            SELECT c.class_id, c.level, c.group_number, d.department_name, ca.is_primary
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN class_advisors ca ON c.class_id = ca.class_id
            WHERE c.level = :level AND c.academic_year_id = :academic_year_id AND ca.teacher_id = :teacher_id
            ORDER BY c.group_number
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':academic_year_id', $academic_year_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
    } else {
        // ดึงห้องเรียนทั้งหมดสำหรับแอดมิน
        $sql = "
            SELECT c.class_id, c.level, c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.level = :level AND c.academic_year_id = :academic_year_id
            ORDER BY c.group_number
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':academic_year_id', $academic_year_id);
    }
    
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'rooms' => $rooms]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()]);
}
?>
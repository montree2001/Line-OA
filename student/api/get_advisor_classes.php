<?php
/**
 * get_advisor_classes.php - API สำหรับดึงรายการชั้นเรียนที่ครูที่ปรึกษาดูแล
 */
header('Content-Type: application/json');
require_once '../../db_connect.php';

// ตรวจสอบการล็อกอิน (ไม่อนุญาตให้เข้าถึง API โดยตรง)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'ไม่ได้รับอนุญาตให้เข้าถึง']);
    exit;
}

// รับพารามิเตอร์รหัสครู
$teacherId = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

if ($teacherId <= 0) {
    echo json_encode(['error' => 'รหัสครูไม่ถูกต้อง']);
    exit;
}

try {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $year_stmt = $conn->prepare($year_query);
    $year_stmt->execute();
    $academic_year = $year_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    
    // ดึงชั้นเรียนที่ครูเป็นที่ปรึกษา
    $query = "SELECT c.class_id, c.level, c.department, c.group_number 
              FROM classes c 
              JOIN class_advisors ca ON c.class_id = ca.class_id
              WHERE ca.teacher_id = :teacher_id
              AND c.academic_year_id = :academic_year_id
              ORDER BY c.level, c.department, c.group_number";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน']);
    exit;
}
<?php
/**
 * search_advisor.php - API สำหรับค้นหาครูที่ปรึกษา
 */
header('Content-Type: application/json');
require_once '../../db_connect.php';

// ตรวจสอบการล็อกอิน (ไม่อนุญาตให้เข้าถึง API โดยตรง)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'ไม่ได้รับอนุญาตให้เข้าถึง']);
    exit;
}

// รับพารามิเตอร์คำค้นหา
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

try {
    $conn = getDB();
    
    // ค้นหาครูที่ปรึกษาจากชื่อหรือนามสกุล
    $query = "SELECT t.teacher_id, t.title, u.first_name, u.last_name, t.department 
              FROM teachers t 
              JOIN users u ON t.user_id = u.user_id 
              WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term
              OR u.first_name LIKE :search_term 
              OR u.last_name LIKE :search_term
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $search_param = "%" . $searchTerm . "%";
    $stmt->bindParam(':search_term', $search_param, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการค้นหาข้อมูล']);
    exit;
}
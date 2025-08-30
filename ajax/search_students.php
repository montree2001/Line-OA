<?php
/**
 * search_students.php - AJAX endpoint สำหรับค้นหานักเรียน
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../db_connect.php";

$search = $_POST["search"] ?? "";

if (empty($search) || strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $conn = getDB();
    
    $query = "SELECT 
                s.student_id,
                s.student_code,
                CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              WHERE (s.student_code LIKE ? 
                     OR u.first_name LIKE ? 
                     OR u.last_name LIKE ?
                     OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)
                    AND s.status = 'กำลังศึกษา'
              ORDER BY u.first_name, u.last_name
              LIMIT 20";
    
    $search_term = '%' . $search . '%';
    $stmt = $conn->prepare($query);
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
    
} catch (Exception $e) {
    error_log("Search Students Error: " . $e->getMessage());
    echo json_encode([]);
}
?>
<?php
/**
 * get_classes.php - AJAX endpoint สำหรับดึงข้อมูลห้องเรียนตามแผนก
 */

header("Content-Type: application/json");

require_once "../../db_connect.php";

$department_id = $_POST["department_id"] ?? "";

if (empty($department_id)) {
    echo json_encode([]);
    exit;
}

try {
    $conn = getDB();
    
    $query = "SELECT class_id, level, group_number 
              FROM classes 
              WHERE department_id = ? AND is_active = 1 
              ORDER BY level, group_number";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$department_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($classes);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>

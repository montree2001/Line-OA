<?php
try {
    require_once 'db_connect.php';
    $conn = getDB();
    echo "Database connection successful!<br>";
    
    // ทดสอบ query
    $query = "SELECT COUNT(*) as count FROM students";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Students count: " . $result['count'];
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
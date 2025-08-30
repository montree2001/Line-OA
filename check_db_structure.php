<?php
try {
    require_once 'db_connect.php';
    $conn = getDB();
    
    echo "<h2>Students Table Structure:</h2>";
    $query = "DESCRIBE students";
    $stmt = $conn->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Sample Students Data:</h2>";
    $query = "SELECT * FROM students LIMIT 3";
    $stmt = $conn->query($query);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($samples);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
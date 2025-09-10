<?php
require_once 'db_connect.php';

echo "<h2>🔍 ตรวจสอบโครงสร้างตารางด่วน</h2>";

try {
    $conn = getDB();
    
    echo "<h3>academic_years</h3>";
    $result = $conn->query("SHOW COLUMNS FROM academic_years");
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . "<br>";
    }
    
    echo "<h3>students</h3>";  
    $result = $conn->query("SHOW COLUMNS FROM students");
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . "<br>";
    }
    
    echo "<h3>attendance</h3>";
    $result = $conn->query("SHOW COLUMNS FROM attendance"); 
    while ($row = $result->fetch()) {
        echo "- " . $row['Field'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
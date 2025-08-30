<?php
try {
    require_once 'db_connect.php';
    $conn = getDB();
    
    echo "<h2>Tables in Database:</h2>";
    $query = "SHOW TABLES";
    $stmt = $conn->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table[0] . "</li>";
        
        // ถ้าชื่อตารางมีคำว่า attendance หรือ record
        if (stripos($table[0], 'attendance') !== false || stripos($table[0], 'record') !== false) {
            echo "<strong>*** Found attendance related table: " . $table[0] . " ***</strong><br>";
            
            // แสดงโครงสร้างตาราง
            $desc_query = "DESCRIBE " . $table[0];
            $desc_stmt = $conn->query($desc_query);
            $columns = $desc_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
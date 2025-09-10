<?php
// ตรวจสอบโครงสร้างฐานข้อมูล
error_reporting(E_ALL);
ini_set("display_errors", 1);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=prasat_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 โครงสร้างตาราง students</h2>";
    $stmt = $pdo->query("DESCRIBE students");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border=\"1\" style=\"border-collapse: collapse;\">";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column[\"Field\"]}</td>";
        echo "<td>{$column[\"Type\"]}</td>";
        echo "<td>{$column[\"Null\"]}</td>";
        echo "<td>{$column[\"Key\"]}</td>";
        echo "<td>{$column[\"Default\"]}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

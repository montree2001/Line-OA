<?php
/**
 * check_table_structure.php - ตรวจสอบโครงสร้างตารางจริง
 */

require_once 'db_connect.php';

echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง</h2>";

try {
    $conn = getDB();
    
    // ตรวจสอบตาราง academic_years
    echo "<h3>📚 ตาราง academic_years</h3>";
    $desc_academic = $conn->query("DESCRIBE academic_years");
    $academic_columns = $desc_academic->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($academic_columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ตรวจสอบตาราง students
    echo "<h3>👥 ตาราง students</h3>";
    $desc_students = $conn->query("DESCRIBE students");
    $student_columns = $desc_students->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($student_columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ตรวจสอบตาราง attendance
    echo "<h3>📋 ตาราง attendance</h3>";
    $desc_attendance = $conn->query("DESCRIBE attendance");
    $attendance_columns = $desc_attendance->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($attendance_columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>📊 ตัวอย่างข้อมูล academic_years</h3>";
    $sample_academic = $conn->query("SELECT * FROM academic_years LIMIT 3");
    $academic_data = $sample_academic->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($academic_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        $headers = array_keys($academic_data[0]);
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($academic_data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>📊 ตัวอย่างข้อมูล students</h3>";
    $sample_students = $conn->query("SELECT * FROM students LIMIT 3");
    $students_data = $sample_students->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        $headers = array_keys($students_data[0]);
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($students_data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . ($value ?: 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
</style>
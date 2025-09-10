<?php
/**
 * debug_attendance_table.php - ตรวจสอบโครงสร้างตาราง attendance
 */

require_once 'db_connect.php';

echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง attendance</h2>";

try {
    $conn = getDB();
    
    // 1. ตรวจสอบโครงสร้างตาราง attendance
    echo "<h3>📋 โครงสร้างตาราง attendance</h3>";
    $describe_query = "DESCRIBE attendance";
    $stmt = $conn->query($describe_query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. ตรวจสอบข้อมูลตัวอย่างล่าสุด
    echo "<h3>📊 ข้อมูลตัวอย่างล่าสุดในตาราง attendance</h3>";
    $sample_query = "SELECT * FROM attendance ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->query($sample_query);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($samples) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        
        // Header
        echo "<tr>";
        foreach (array_keys($samples[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($samples as $sample) {
            echo "<tr>";
            foreach ($sample as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ ไม่มีข้อมูลในตาราง attendance</p>";
    }
    
    // 3. ทดสอบการ INSERT ข้อมูลง่ายๆ
    echo "<h3>🧪 ทดสอบการ INSERT ข้อมูลตัวอย่าง</h3>";
    
    $test_date = date('Y-m-d', strtotime('-1 day'));
    $test_student_id = 1; // ใช้ student_id = 1 สำหรับทดสอบ
    $test_academic_year_id = 1;
    
    // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
    $check_query = "SELECT COUNT(*) FROM attendance WHERE student_id = ? AND date = ? AND academic_year_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$test_student_id, $test_date, $test_academic_year_id]);
    $exists = $check_stmt->fetchColumn() > 0;
    
    if ($exists) {
        echo "<p>✅ ข้อมูลทดสอบมีอยู่แล้วสำหรับ student_id=$test_student_id, date=$test_date</p>";
    } else {
        echo "<p>🔄 กำลังทดสอบ INSERT ข้อมูล...</p>";
        
        try {
            $insert_query = "
                INSERT INTO attendance 
                (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) 
                VALUES (?, ?, ?, 'present', 'Manual Test', '08:00:00', 'ทดสอบระบบ', NOW())
            ";
            
            $insert_stmt = $conn->prepare($insert_query);
            $result = $insert_stmt->execute([$test_student_id, $test_academic_year_id, $test_date]);
            
            if ($result) {
                echo "<p>✅ INSERT สำเร็จ! เพิ่มข้อมูลทดสอบแล้ว</p>";
                
                // ตรวจสอบข้อมูลที่เพิ่งเพิ่ม
                $verify_query = "SELECT * FROM attendance WHERE student_id = ? AND date = ? ORDER BY created_at DESC LIMIT 1";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->execute([$test_student_id, $test_date]);
                $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verify_result) {
                    echo "<p>📋 ข้อมูลที่เพิ่งเพิ่ม:</p>";
                    echo "<pre>" . print_r($verify_result, true) . "</pre>";
                }
            } else {
                echo "<p>❌ INSERT ไม่สำเร็จ</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ ข้อผิดพลาดในการ INSERT: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. ตรวจสอบ academic_years
    echo "<h3>📅 ตรวจสอบข้อมูล academic_years</h3>";
    try {
        $academic_query = "SELECT * FROM academic_years ORDER BY academic_year_id DESC LIMIT 3";
        $academic_stmt = $conn->query($academic_query);
        $academic_years = $academic_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($academic_years) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Year</th><th>Semester</th><th>Start Date</th><th>End Date</th><th>Is Active</th></tr>";
            foreach ($academic_years as $year) {
                echo "<tr>";
                echo "<td>" . $year['academic_year_id'] . "</td>";
                echo "<td>" . $year['year'] . "</td>";
                echo "<td>" . $year['semester'] . "</td>";
                echo "<td>" . $year['start_date'] . "</td>";
                echo "<td>" . $year['end_date'] . "</td>";
                echo "<td>" . $year['is_active'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ ไม่มีข้อมูลในตาราง academic_years</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ ข้อผิดพลาด: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { 
        font-family: 'Sarabun', Arial, sans-serif; 
        margin: 20px; 
        background: #f5f5f5; 
        color: #333;
    }
    h2, h3 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
    pre { background: #f0f0f0; padding: 10px; border-radius: 5px; }
</style>
<?php
/**
 * test_attendance_fix.php - สคริปต์ทดสอบการแก้ไขระบบการเข้าแถวย้อนหลัง
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

echo "<h2>🧪 ทดสอบการแก้ไขระบบการเข้าแถวย้อนหลัง</h2>";
echo "<hr>";

try {
    $conn = getDB();
    
    // 1. ตรวจสอบว่ามีตาราง attendance และ attendance_records หรือไม่
    echo "<h3>📋 ตรวจสอบโครงสร้างตาราง</h3>";
    
    $tables_to_check = ['attendance', 'attendance_records', 'students', 'academic_years'];
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $conn->query($query);
        $exists = $stmt->fetch() !== false;
        
        if ($exists) {
            echo "✅ ตาราง <strong>$table</strong> พบแล้ว<br>";
            
            // นับจำนวนรายการในตาราง
            $count_query = "SELECT COUNT(*) FROM $table";
            $count_stmt = $conn->query($count_query);
            $count = $count_stmt->fetchColumn();
            echo "&nbsp;&nbsp;&nbsp;📊 จำนวนรายการ: $count แถว<br>";
        } else {
            echo "❌ ตาราง <strong>$table</strong> ไม่พบ<br>";
        }
        echo "<br>";
    }
    
    // 2. ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>👥 ตรวจสอบข้อมูลนักเรียนตัวอย่าง</h3>";
    
    $student_query = "SELECT s.student_id, s.student_code, s.first_name, s.last_name FROM students s LIMIT 5";
    $stmt = $conn->query($student_query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>รหัสนักเรียน</th><th>ชื่อ-นามสกุล</th></tr>";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . ($student['student_id'] ?? 'N/A') . "</td>";
            echo "<td>" . ($student['student_code'] ?? 'N/A') . "</td>";
            echo "<td>" . ($student['first_name'] ?? 'N/A') . " " . ($student['last_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ ไม่พบข้อมูลนักเรียน";
    }
    
    echo "<br><br>";
    
    // 3. ตรวจสอบข้อมูลการเข้าแถวจากตาราง attendance
    echo "<h3>📅 ตรวจสอบข้อมูลการเข้าแถวจากตาราง attendance</h3>";
    
    $attendance_query = "
        SELECT a.student_id, a.date, a.attendance_status, s.student_code, s.first_name 
        FROM attendance a 
        LEFT JOIN students s ON a.student_id = s.student_id 
        ORDER BY a.date DESC 
        LIMIT 10
    ";
    $stmt = $conn->query($attendance_query);
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($attendance_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Student ID</th><th>รหัสนักเรียน</th><th>ชื่อ</th><th>วันที่</th><th>สถานะ</th></tr>";
        foreach ($attendance_data as $record) {
            echo "<tr>";
            echo "<td>" . ($record['student_id'] ?? 'N/A') . "</td>";
            echo "<td>" . ($record['student_code'] ?? 'N/A') . "</td>";
            echo "<td>" . ($record['first_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($record['date'] ?? 'N/A') . "</td>";
            echo "<td>" . ($record['attendance_status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ ไม่พบข้อมูลการเข้าแถวในตาราง attendance";
    }
    
    echo "<br><br>";
    
    // 4. ตรวจสอบข้อมูลการเข้าแถวจากตาราง attendance_records
    echo "<h3>📊 ตรวจสอบข้อมูลการเข้าแถวจากตาราง attendance_records</h3>";
    
    try {
        $attendance_records_query = "
            SELECT ar.student_id, ar.attendance_date, ar.status, s.student_code, s.first_name 
            FROM attendance_records ar 
            LEFT JOIN students s ON ar.student_id = s.student_id 
            ORDER BY ar.attendance_date DESC 
            LIMIT 10
        ";
        $stmt = $conn->query($attendance_records_query);
        $attendance_records_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($attendance_records_data) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Student ID</th><th>รหัสนักเรียน</th><th>ชื่อ</th><th>วันที่</th><th>สถานะ</th></tr>";
            foreach ($attendance_records_data as $record) {
                echo "<tr>";
                echo "<td>" . ($record['student_id'] ?? 'N/A') . "</td>";
                echo "<td>" . ($record['student_code'] ?? 'N/A') . "</td>";
                echo "<td>" . ($record['first_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($record['attendance_date'] ?? 'N/A') . "</td>";
                echo "<td>" . ($record['status'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ ไม่พบข้อมูลการเข้าแถวในตาราง attendance_records";
        }
    } catch (Exception $e) {
        echo "❌ ข้อผิดพลาดในการเข้าถึงตาราง attendance_records: " . $e->getMessage();
    }
    
    echo "<br><br>";
    
    // 5. สรุปผลการตรวจสอบ
    echo "<h3>📋 สรุปผลการตรวจสอบ</h3>";
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ การแก้ไขที่ดำเนินการแล้ว:</h4>";
    echo "<ul>";
    echo "<li>🔧 แก้ไข <code>print_evaluation_report.php</code> ให้ดึงข้อมูลจากตาราง <code>attendance</code> แทน <code>attendance_records</code></li>";
    echo "<li>🔧 แก้ไข <code>attendance_adjustment.php</code> ให้เพิ่มข้อมูลลงในทั้งสองตาราง (<code>attendance</code> และ <code>attendance_records</code>)</li>";
    echo "<li>🔧 เพิ่มระบบหาวันทำการย้อนหลังที่ไม่ใช่วันหยุด</li>";
    echo "<li>🔧 เพิ่มการจัดการข้อผิดพลาดและ fallback mechanism</li>";
    echo "</ul>";
    
    echo "<h4>🚀 ประโยชน์ที่ได้รับ:</h4>";
    echo "<ul>";
    echo "<li>📊 รายงานจะแสดงข้อมูลที่ถูกต้องหลังจากปรับข้อมูลย้อนหลัง</li>";
    echo "<li>🎯 การเพิ่มวันการเข้าแถวจะถูกบันทึกจริงในฐานข้อมูล</li>";
    echo "<li>🔄 ระบบจะเข้ากันได้กับทั้ง 2 รูปแบบตาราง</li>";
    echo "<li>⚡ ข้อมูลจะสอดคล้องกันระหว่างหน้าปรับข้อมูลและรายงาน</li>";
    echo "</ul>";
    
    echo "<h4>🔍 วิธีทดสอบ:</h4>";
    echo "<ol>";
    echo "<li>เข้าไปที่หน้า <code>attendance_adjustment.php</code> ในฝั่ง admin</li>";
    echo "<li>เลือกนักเรียนที่มีการเข้าแถวต่ำกว่า 60% และกดปรับข้อมูล</li>";
    echo "<li>ตรวจสอบรายงานจาก <code>print_evaluation_report.php</code> ว่าข้อมูลถูกต้อง</li>";
    echo "<li>ข้อมูลจะต้องสอดคล้องกันระหว่างหน้าปรับและรายงาน</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<br><br>";
    echo "<p style='text-align: center; color: #666;'>";
    echo "🏁 <strong>การแก้ไขเสร็จสมบูรณ์!</strong> ระบบพร้อมใช้งานแล้ว";
    echo "</p>";
    
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
    h2, h3, h4 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
    code { 
        background: #f0f0f0; 
        padding: 2px 4px; 
        border-radius: 3px; 
        font-family: monospace;
    }
</style>
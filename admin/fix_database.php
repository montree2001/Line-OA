<?php
/**
 * fix_database.php - แก้ไขโครงสร้างฐานข้อมูลเพื่อแก้ปัญหาการเพิ่มนักเรียน
 */

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

echo "<h1>กำลังแก้ไขโครงสร้างฐานข้อมูล</h1>";

try {
    $conn = getDB();
    
    // เริ่ม transaction
    $conn->beginTransaction();
    
    echo "<h2>1. แก้ไขโครงสร้างตาราง users</h2>";
    
    // แก้ไขคอลัมน์ line_id ให้สามารถเป็น NULL ได้
    $alterQuery = "ALTER TABLE `users` MODIFY `line_id` varchar(255) NULL";
    $conn->exec($alterQuery);
    echo "<p>✅ แก้ไขคอลัมน์ line_id ให้เป็น NULL ได้สำเร็จ</p>";
    
    // ลบข้อมูล line_id ที่เป็น TEMP_ โดยแก้ไขเป็น NULL
    $updateQuery = "UPDATE users SET line_id = NULL WHERE line_id LIKE 'TEMP_%' OR line_id = ''";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p>✅ แก้ไขข้อมูล line_id ที่เป็น TEMP_ หรือค่าว่างเป็น NULL จำนวน $updated รายการ</p>";
    
    // ตรวจสอบข้อมูลที่ซ้ำกัน
    $checkQuery = "SELECT line_id, COUNT(*) as count FROM users WHERE line_id IS NOT NULL GROUP BY line_id HAVING count > 1";
    $stmt = $conn->query($checkQuery);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<p>⚠️ พบข้อมูล line_id ที่ซ้ำกัน:</p><ul>";
        foreach ($duplicates as $dup) {
            echo "<li>line_id: {$dup['line_id']} จำนวน: {$dup['count']} รายการ</li>";
        }
        echo "</ul>";
        
        // แก้ไขข้อมูลที่ซ้ำกัน
        echo "<p>กำลังแก้ไขข้อมูลที่ซ้ำกัน...</p>";
        foreach ($duplicates as $dup) {
            $line_id = $dup['line_id'];
            
            // ดึงรายชื่อ user_id ที่มี line_id ซ้ำกัน
            $userQuery = "SELECT user_id FROM users WHERE line_id = ?";
            $userStmt = $conn->prepare($userQuery);
            $userStmt->execute([$line_id]);
            $users = $userStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // ข้าม user_id แรก และแก้ไข user_id ที่เหลือให้เป็น NULL
            for ($i = 1; $i < count($users); $i++) {
                $updateDupQuery = "UPDATE users SET line_id = NULL WHERE user_id = ?";
                $updateDupStmt = $conn->prepare($updateDupQuery);
                $updateDupStmt->execute([$users[$i]]);
                echo "<p>✅ แก้ไข user_id: {$users[$i]} ให้มี line_id เป็น NULL</p>";
            }
        }
    } else {
        echo "<p>✅ ไม่พบข้อมูล line_id ที่ซ้ำกัน</p>";
    }
    
    // สรุปข้อมูลนักเรียน
    $studentQuery = "SELECT COUNT(*) as total FROM students";
    $stmt = $conn->query($studentQuery);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h2>2. ตรวจสอบข้อมูลนักเรียน</h2>";
    echo "<p>จำนวนนักเรียนทั้งหมดในระบบ: $total คน</p>";
    
    // ดึงข้อมูลนักเรียน 10 คนล่าสุด
    $recentQuery = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, u.line_id
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  ORDER BY s.student_id DESC LIMIT 10";
    $stmt = $conn->query($recentQuery);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>นักเรียน 10 คนล่าสุด:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>รหัสนักศึกษา</th><th>ชื่อ-นามสกุล</th><th>LINE ID</th></tr>";
    
    foreach ($students as $student) {
        $line_id = $student['line_id'] === null ? 'NULL' : $student['line_id'];
        echo "<tr>";
        echo "<td>{$student['student_id']}</td>";
        echo "<td>{$student['student_code']}</td>";
        echo "<td>{$student['first_name']} {$student['last_name']}</td>";
        echo "<td>{$line_id}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Commit transaction
    $conn->commit();
    
    echo "<h2>✅ การแก้ไขเสร็จสมบูรณ์</h2>";
    echo "<p>คุณสามารถกลับไปที่ <a href='students.php'>หน้าจัดการนักเรียน</a> เพื่อเพิ่มข้อมูลนักเรียนใหม่ได้</p>";
    
} catch (PDOException $e) {
    // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    echo "<h2>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p>ข้อความแสดงข้อผิดพลาด: " . $e->getMessage() . "</p>";
    echo "<p>โปรดติดต่อผู้ดูแลระบบเพื่อแก้ไขปัญหา</p>";
}
?>
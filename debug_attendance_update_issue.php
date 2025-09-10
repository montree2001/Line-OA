<?php
/**
 * debug_attendance_update_issue.php - ตรวจสอบปัญหา UPDATE ได้ 0 แถว
 */

require_once 'db_connect.php';

echo "<h2>🔍 ตรวจสอบปัญหา UPDATE ได้ 0 แถว</h2>";
echo "<p><em>ตรวจสอบว่าทำไม UPDATE attendance จาก absent เป็น present ไม่ได้</em></p>";

try {
    $conn = getDB();
    
    // รับพารามิเตอร์จาก URL หรือใช้ค่าเริ่มต้น
    $test_student_id = $_GET['student_id'] ?? 1; // เปลี่ยนตามต้องการ
    $test_academic_year_id = $_GET['academic_year_id'] ?? 1;
    
    echo "<h3>📋 พารามิเตอร์การทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $test_student_id</p>";
    echo "<p><strong>Academic Year ID:</strong> $test_academic_year_id</p>";
    echo "<p><em>เปลี่ยนได้ผ่าน URL: ?student_id=X&academic_year_id=Y</em></p>";
    
    // === 1. ตรวจสอบข้อมูลนักเรียน ===
    echo "<h3>👤 1. ตรวจสอบข้อมูลนักเรียน</h3>";
    $student_query = "SELECT student_id, student_code, first_name, last_name FROM students WHERE student_id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->execute([$test_student_id]);
    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>✅ พบนักเรียน: {$student['student_code']} - {$student['first_name']} {$student['last_name']}</p>";
    } else {
        echo "<p>❌ ไม่พบนักเรียน ID: $test_student_id</p>";
        exit;
    }
    
    // === 2. ตรวจสอบข้อมูล academic year ===
    echo "<h3>📅 2. ตรวจสอบข้อมูล Academic Year</h3>";
    $academic_query = "SELECT * FROM academic_years WHERE academic_year_id = ?";
    $academic_stmt = $conn->prepare($academic_query);
    $academic_stmt->execute([$test_academic_year_id]);
    $academic = $academic_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($academic as $key => $value) {
            echo "<tr><td>$key</td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ ไม่พบ Academic Year ID: $test_academic_year_id</p>";
        // แสดงรายการที่มี
        $all_academic = $conn->query("SELECT academic_year_id, year, semester, is_active FROM academic_years");
        echo "<p><strong>Academic Years ที่มี:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Year</th><th>Semester</th><th>Active</th></tr>";
        while ($row = $all_academic->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['academic_year_id']}</td><td>{$row['year']}</td><td>{$row['semester']}</td><td>{$row['is_active']}</td></tr>";
        }
        echo "</table>";
    }
    
    // === 3. ตรวจสอบข้อมูลการเข้าแถวทั้งหมด ===
    echo "<h3>📊 3. ตรวจสอบข้อมูลการเข้าแถวของนักเรียน</h3>";
    $attendance_query = "
        SELECT date, attendance_status, academic_year_id, check_method, remarks, created_at 
        FROM attendance 
        WHERE student_id = ? 
        ORDER BY date DESC 
        LIMIT 20
    ";
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->execute([$test_student_id]);
    $attendance_data = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($attendance_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr><th>วันที่</th><th>สถานะ</th><th>Academic Year</th><th>วิธีเช็ค</th><th>หมายเหตุ</th><th>สร้างเมื่อ</th></tr>";
        
        $absent_count = 0;
        $present_count = 0;
        
        foreach ($attendance_data as $record) {
            $status = $record['attendance_status'];
            $color = $status === 'absent' ? 'red' : ($status === 'present' ? 'green' : 'orange');
            
            echo "<tr>";
            echo "<td>{$record['date']}</td>";
            echo "<td style='color: $color;'><strong>$status</strong></td>";
            echo "<td>{$record['academic_year_id']}</td>";
            echo "<td>{$record['check_method']}</td>";
            echo "<td>{$record['remarks']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
            
            if ($status === 'absent') $absent_count++;
            if ($status === 'present') $present_count++;
        }
        echo "</table>";
        
        echo "<p><strong>สรุป (ล่าสุด 20 วัน):</strong> Absent = $absent_count, Present = $present_count</p>";
    } else {
        echo "<p>❌ ไม่พบข้อมูลการเข้าแถวของนักเรียน ID: $test_student_id</p>";
    }
    
    // === 4. นับข้อมูลตาม academic year ===
    echo "<h3>📈 4. สถิติการเข้าแถวตาม Academic Year</h3>";
    $stats_query = "
        SELECT 
            academic_year_id,
            attendance_status, 
            COUNT(*) as count
        FROM attendance 
        WHERE student_id = ? 
        GROUP BY academic_year_id, attendance_status 
        ORDER BY academic_year_id, attendance_status
    ";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute([$test_student_id]);
    $stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($stats) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Academic Year ID</th><th>สถานะ</th><th>จำนวน</th></tr>";
        foreach ($stats as $stat) {
            $color = $stat['attendance_status'] === 'absent' ? 'red' : ($stat['attendance_status'] === 'present' ? 'green' : 'orange');
            echo "<tr>";
            echo "<td><strong>{$stat['academic_year_id']}</strong></td>";
            echo "<td style='color: $color;'><strong>{$stat['attendance_status']}</strong></td>";
            echo "<td><strong>{$stat['count']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ ไม่พบสถิติการเข้าแถว</p>";
    }
    
    // === 5. ทดสอบการหา absent days ===
    echo "<h3>🔍 5. ทดสอบการหาวันที่ขาดเรียน</h3>";
    
    // นับวันที่ขาด
    $count_absent_query = "
        SELECT COUNT(*) as total_absent_days
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ? 
          AND attendance_status = 'absent'
    ";
    $count_stmt = $conn->prepare($count_absent_query);
    $count_stmt->execute([$test_student_id, $test_academic_year_id]);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_absent = $count_result['total_absent_days'];
    
    echo "<p><strong>วันที่ขาดเรียนทั้งหมด (Academic Year $test_academic_year_id):</strong> $total_absent วัน</p>";
    
    if ($total_absent > 0) {
        // แสดงรายการวันที่ขาด
        $absent_list_query = "
            SELECT date, remarks, created_at
            FROM attendance 
            WHERE student_id = ? 
              AND academic_year_id = ? 
              AND attendance_status = 'absent'
            ORDER BY date DESC
            LIMIT 10
        ";
        $absent_stmt = $conn->prepare($absent_list_query);
        $absent_stmt->execute([$test_student_id, $test_academic_year_id]);
        $absent_list = $absent_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>วันที่ขาด (10 วันล่าสุด):</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>วันที่</th><th>หมายเหตุ</th><th>สร้างเมื่อ</th></tr>";
        foreach ($absent_list as $absent) {
            echo "<tr>";
            echo "<td><strong>{$absent['date']}</strong></td>";
            echo "<td>{$absent['remarks']}</td>";
            echo "<td>{$absent['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // === 6. ทดสอบ UPDATE จริง ===
        echo "<h3>🛠️ 6. ทดสอบ UPDATE แถวแรก</h3>";
        $test_date = $absent_list[0]['date'];
        
        echo "<p><strong>จะทดสอบ UPDATE วันที่:</strong> $test_date</p>";
        
        // ตรวจสอบก่อน UPDATE
        $before_query = "SELECT attendance_status, academic_year_id FROM attendance WHERE student_id = ? AND date = ?";
        $before_stmt = $conn->prepare($before_query);
        $before_stmt->execute([$test_student_id, $test_date]);
        $before = $before_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($before) {
            echo "<p><strong>ก่อน UPDATE:</strong> Status = {$before['attendance_status']}, Academic Year = {$before['academic_year_id']}</p>";
            
            // ทำ UPDATE (ใน transaction เพื่อ rollback)
            $conn->beginTransaction();
            
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual Test', 
                    remarks = 'ทดสอบ UPDATE',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([$test_student_id, $test_academic_year_id, $test_date]);
            $rows_affected = $update_stmt->rowCount();
            
            echo "<p><strong>UPDATE Result:</strong> Success = " . ($result ? 'true' : 'false') . ", Rows Affected = $rows_affected</p>";
            
            if ($result && $rows_affected > 0) {
                echo "<p style='color: green;'><strong>✅ UPDATE สำเร็จ!</strong></p>";
                
                // ตรวจสอบหลัง UPDATE
                $after_stmt = $conn->prepare($before_query);
                $after_stmt->execute([$test_student_id, $test_date]);
                $after = $after_stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<p><strong>หลัง UPDATE:</strong> Status = {$after['attendance_status']}, Academic Year = {$after['academic_year_id']}</p>";
            } else {
                echo "<p style='color: red;'><strong>❌ UPDATE ล้มเหลว!</strong></p>";
                echo "<p><strong>สาเหตุที่เป็นไปได้:</strong></p>";
                echo "<ul>";
                echo "<li>Academic Year ID ไม่ตรงกัน (ใช้ $test_academic_year_id แต่ข้อมูลเป็น {$before['academic_year_id']})</li>";
                echo "<li>สถานะไม่ใช่ 'absent' (ปัจจุบัน: {$before['attendance_status']})</li>";
                echo "<li>WHERE condition ไม่ตรงกัน</li>";
                echo "</ul>";
                
                // ลอง UPDATE โดยไม่เช็ค academic_year_id
                echo "<h4>🔄 ลอง UPDATE โดยไม่เช็ค academic_year_id</h4>";
                $update_simple = "
                    UPDATE attendance 
                    SET attendance_status = 'present', 
                        check_method = 'Manual Test Simple', 
                        remarks = 'ทดสอบ UPDATE แบบง่าย',
                        updated_at = NOW()
                    WHERE student_id = ? AND date = ? AND attendance_status = 'absent'
                ";
                
                $simple_stmt = $conn->prepare($update_simple);
                $simple_result = $simple_stmt->execute([$test_student_id, $test_date]);
                $simple_rows = $simple_stmt->rowCount();
                
                echo "<p><strong>UPDATE Simple Result:</strong> Success = " . ($simple_result ? 'true' : 'false') . ", Rows Affected = $simple_rows</p>";
                
                if ($simple_result && $simple_rows > 0) {
                    echo "<p style='color: green;'><strong>✅ UPDATE แบบง่ายสำเร็จ!</strong> ปัญหาคือ academic_year_id ไม่ตรงกัน</p>";
                } else {
                    echo "<p style='color: red;'><strong>❌ UPDATE แบบง่ายก็ล้มเหลว!</strong> ปัญหาไม่ใช่ academic_year_id</p>";
                }
            }
            
            // Rollback เพื่อไม่ให้เปลี่ยนแปลงข้อมูลจริง
            $conn->rollBack();
            echo "<p><em>ℹ️ หมายเหตุ: การ UPDATE ถูก rollback แล้ว ข้อมูลไม่เปลี่ยนแปลง</em></p>";
            
        } else {
            echo "<p>❌ ไม่พบข้อมูลสำหรับวันที่: $test_date</p>";
        }
        
    } else {
        echo "<p>⚠️ ไม่มีวันที่ขาดเรียนในปีการศึกษา $test_academic_year_id</p>";
    }
    
    // === สรุปและแนะนำ ===
    echo "<hr>";
    echo "<h3>💡 สรุปและแนะนำการแก้ไข</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
    echo "<h4>🔍 จุดที่ต้องตรวจสอบ:</h4>";
    echo "<ol>";
    echo "<li><strong>Academic Year ID:</strong> ตรวจสอบว่าข้อมูลในตาราง attendance ใช้ academic_year_id เท่าไร</li>";
    echo "<li><strong>สถานะข้อมูล:</strong> ตรวจสอบว่าข้อมูลมีสถานะ 'absent' จริงหรือไม่</li>";
    echo "<li><strong>WHERE Condition:</strong> ตรวจสอบว่า WHERE condition ครบถ้วนและถูกต้อง</li>";
    echo "<li><strong>การเข้าใจผิด:</strong> อาจจะมีการเข้าใจผิดเกี่ยวกับโครงสร้างข้อมูล</li>";
    echo "</ol>";
    
    echo "<h4>🛠️ วิธีแก้ไขที่แนะนำ:</h4>";
    echo "<ul>";
    echo "<li>ใช้ academic_year_id ที่ถูกต้องตามข้อมูลจริง</li>";
    echo "<li>เพิ่ม debug log เพื่อดูค่าที่ใช้ใน WHERE condition</li>";
    echo "<li>ลองไม่เช็ค academic_year_id ถ้าข้อมูลไม่มีฟิลด์นี้</li>";
    echo "<li>ตรวจสอบโครงสร้างตารางให้ถูกต้อง</li>";
    echo "</ul>";
    echo "</div>";
    
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
    hr { margin: 20px 0; border: 1px solid #e0e0e0; }
</style>
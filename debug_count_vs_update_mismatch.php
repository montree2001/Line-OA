<?php
/**
 * debug_count_vs_update_mismatch.php - ตรวจสอบทำไม COUNT หาได้แต่ UPDATE ไม่ได้
 */

session_start();
$_SESSION['user_id'] = 1;

require_once 'db_connect.php';

echo "<h2>🔍 Debug: COUNT vs UPDATE Mismatch</h2>";
echo "<p><em>ตรวจสอบทำไมนับ absent ได้ 21 วัน แต่ UPDATE ได้ 0 วัน</em></p>";

// รับ student_id จาก URL
$student_id = $_GET['student_id'] ?? 1; // เปลี่ยนเป็น ID ที่มีปัญหาจริง
$days_to_adjust = 2;

try {
    $conn = getDB();
    
    echo "<h3>📋 ข้อมูลที่ทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $student_id</p>";
    
    // === ขั้นที่ 1: ดึงข้อมูล Academic Year ===
    echo "<h3>📚 ขั้นที่ 1: ข้อมูล Academic Year</h3>";
    
    $academic_year_query = "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($academic_year_query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_year) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Value</th></tr>";
        foreach ($academic_year as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        
        // หา academic_year_id
        $academic_year_id = null;
        foreach (['academic_year_id', 'id', 'year_id'] as $possible_column) {
            if (isset($academic_year[$possible_column])) {
                $academic_year_id = $academic_year[$possible_column];
                echo "<p>✅ <strong>ใช้ Academic Year ID:</strong> $academic_year_id (จากคอลัมน์: $possible_column)</p>";
                break;
            }
        }
        
        if (!$academic_year_id) {
            echo "<p>❌ ไม่พบ academic_year_id ที่เหมาะสม</p>";
            exit;
        }
    } else {
        echo "<p>❌ ไม่พบปีการศึกษาที่ active</p>";
        exit;
    }
    
    // === ขั้นที่ 2: ดึงข้อมูล Student ===
    echo "<h3>👤 ขั้นที่ 2: ข้อมูล Student</h3>";
    
    $student_query = "SELECT * FROM students WHERE student_id = ? LIMIT 1";
    $stmt = $conn->prepare($student_query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Value</th></tr>";
        foreach ($student as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // หา academic_year_id ของ student
        $student_academic_year_id = null;
        foreach (['academic_year_id', 'year_id', 'class_year'] as $possible_column) {
            if (isset($student[$possible_column])) {
                $student_academic_year_id = $student[$possible_column];
                echo "<p>✅ <strong>Student's Academic Year ID:</strong> $student_academic_year_id (จากคอลัมน์: $possible_column)</p>";
                break;
            }
        }
        
        // เลือกใช้ academic_year_id ของ student หากมี
        $final_academic_year_id = $student_academic_year_id ?: $academic_year_id;
        echo "<p>🎯 <strong>Final Academic Year ID ที่ใช้:</strong> $final_academic_year_id</p>";
        
        if ($student_academic_year_id != $academic_year_id) {
            echo "<p style='color: orange;'>⚠️ <strong>Warning:</strong> Student's academic year ($student_academic_year_id) ≠ System active year ($academic_year_id)</p>";
        }
    } else {
        echo "<p>❌ ไม่พบข้อมูล student</p>";
        exit;
    }
    
    // === ขั้นที่ 3: ทดสอบ COUNT Query ===
    echo "<h3>📊 ขั้นที่ 3: ทดสอบ COUNT Query</h3>";
    
    $count_query = "
        SELECT COUNT(*) as total_absent_days
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ? 
          AND attendance_status = 'absent'
    ";
    
    echo "<p><strong>COUNT Query:</strong></p>";
    echo "<pre>$count_query</pre>";
    echo "<p><strong>Parameters:</strong> student_id=$student_id, academic_year_id=$final_academic_year_id</p>";
    
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute([$student_id, $final_academic_year_id]);
    $total_absent = $count_stmt->fetchColumn();
    
    echo "<p><strong>ผลลัพธ์ COUNT:</strong> $total_absent วัน</p>";
    
    // === ขั้นที่ 4: ดูข้อมูลจริงในตาราง ===
    echo "<h3>📋 ขั้นที่ 4: ข้อมูลจริงในตาราง attendance</h3>";
    
    $data_query = "
        SELECT date, attendance_status, academic_year_id, student_id
        FROM attendance 
        WHERE student_id = ?
        ORDER BY date DESC
        LIMIT 10
    ";
    
    $data_stmt = $conn->prepare($data_query);
    $data_stmt->execute([$student_id]);
    $attendance_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($attendance_data) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Date</th><th>Status</th><th>Academic Year ID</th><th>Student ID</th><th>Match?</th></tr>";
        
        $matching_absent = 0;
        foreach ($attendance_data as $row) {
            $status_color = $row['attendance_status'] === 'absent' ? 'red' : 'green';
            $year_match = $row['academic_year_id'] == $final_academic_year_id ? '✅' : '❌';
            $is_absent_and_match = ($row['attendance_status'] === 'absent' && $row['academic_year_id'] == $final_academic_year_id);
            
            if ($is_absent_and_match) {
                $matching_absent++;
            }
            
            echo "<tr>";
            echo "<td>{$row['date']}</td>";
            echo "<td style='color: $status_color;'><strong>{$row['attendance_status']}</strong></td>";
            echo "<td>{$row['academic_year_id']}</td>";
            echo "<td>{$row['student_id']}</td>";
            echo "<td>" . ($is_absent_and_match ? '✅ UPDATE ได้' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>จำนวนที่ตรงเงื่อนไข UPDATE ใน 10 แถว:</strong> $matching_absent แถว</p>";
    }
    
    // === ขั้นที่ 5: ทดสอบ UPDATE Query ===
    echo "<h3>🔄 ขั้นที่ 5: ทดสอบ UPDATE Query</h3>";
    
    // หาวันที่จะ update
    $select_days_query = "
        SELECT date 
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ? 
          AND attendance_status = 'absent'
        ORDER BY date DESC
        LIMIT ?
    ";
    
    $select_stmt = $conn->prepare($select_days_query);
    $select_stmt->execute([$student_id, $final_academic_year_id, $days_to_adjust]);
    $days_to_update = $select_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>วันที่จะ UPDATE:</strong> " . implode(', ', $days_to_update) . "</p>";
    
    $conn->beginTransaction(); // เริ่ม transaction เพื่อทดสอบ
    
    $updated_count = 0;
    foreach ($days_to_update as $date) {
        echo "<hr>";
        echo "<h4>📅 ทดสอบ UPDATE วันที่: $date</h4>";
        
        // ตรวจสอบก่อน UPDATE
        $check_before = "SELECT attendance_status, academic_year_id FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_before);
        $check_stmt->execute([$student_id, $final_academic_year_id, $date]);
        $before_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($before_data) {
            echo "<p><strong>ข้อมูลก่อน UPDATE:</strong> Status = {$before_data['attendance_status']}, Academic Year = {$before_data['academic_year_id']}</p>";
            
            if ($before_data['attendance_status'] === 'absent') {
                // ทำ UPDATE
                $update_query = "
                    UPDATE attendance 
                    SET attendance_status = 'present', 
                        check_method = 'Manual Adjustment Test', 
                        updated_at = NOW()
                    WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
                ";
                
                $update_stmt = $conn->prepare($update_query);
                $result = $update_stmt->execute([$student_id, $final_academic_year_id, $date]);
                $rows_affected = $update_stmt->rowCount();
                
                echo "<p><strong>UPDATE Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
                echo "<p><strong>Rows Affected:</strong> $rows_affected</p>";
                
                if ($rows_affected > 0) {
                    $updated_count++;
                    echo "<p style='color: green;'>✅ UPDATE สำเร็จ!</p>";
                } else {
                    echo "<p style='color: red;'>❌ UPDATE ล้มเหลว - ไม่มีแถวที่ถูกเปลี่ยน</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ สถานะไม่ใช่ absent - ข้าม</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ ไม่พบข้อมูลที่ตรงเงื่อนไข</p>";
        }
    }
    
    $conn->rollBack(); // ยกเลิกการเปลี่ยนแปลงเพื่อทดสอบ
    
    // === สรุปผล ===
    echo "<hr>";
    echo "<h3>📊 สรุปผล</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
    echo "<p><strong>COUNT Query ได้:</strong> $total_absent วัน</p>";
    echo "<p><strong>UPDATE สำเร็จ:</strong> $updated_count วัน</p>";
    echo "<p><strong>Academic Year ID ที่ใช้:</strong> $final_academic_year_id</p>";
    
    if ($updated_count == $days_to_adjust) {
        echo "<p style='color: green;'><strong>🎯 ระบบควรทำงานได้ถูกต้อง!</strong></p>";
    } elseif ($updated_count == 0 && $total_absent > 0) {
        echo "<p style='color: red;'><strong>❌ ปัญหา: มีข้อมูล absent แต่ UPDATE ไม่ได้</strong></p>";
        echo "<p><strong>สาเหตุเป็นไปได้:</strong></p>";
        echo "<ul>";
        echo "<li>Academic Year ID ไม่ตรงกัน</li>";
        echo "<li>Student ID ในตาราง attendance ต่างจากที่ส่งมา</li>";
        echo "<li>Attendance Status ไม่ใช่ 'absent' แต่เป็นค่าอื่น</li>";
        echo "</ul>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3, h4 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
    hr { margin: 20px 0; border: 1px solid #e0e0e0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>
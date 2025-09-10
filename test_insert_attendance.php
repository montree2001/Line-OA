<?php
/**
 * test_insert_attendance.php - ทดสอบการ INSERT ข้อมูลการเข้าแถวย้อนหลัง
 */

session_start();
$_SESSION['user_id'] = 1; // จำลอง user_id สำหรับทดสอบ

require_once 'db_connect.php';

echo "<h2>🧪 ทดสอบการ INSERT ข้อมูลการเข้าแถวย้อนหลัง</h2>";

try {
    $conn = getDB();
    
    // ข้อมูลทดสอบ
    $test_student_id = 1;
    $test_academic_year_id = 1;
    $test_dates = [
        date('Y-m-d', strtotime('-5 days')),
        date('Y-m-d', strtotime('-4 days')),
        date('Y-m-d', strtotime('-3 days')),
    ];
    
    echo "<h3>📋 ข้อมูลทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $test_student_id</p>";
    echo "<p><strong>Academic Year ID:</strong> $test_academic_year_id</p>";
    echo "<p><strong>วันที่จะทดสอบ:</strong> " . implode(', ', $test_dates) . "</p>";
    
    echo "<h3>🔄 กระบวนการทดสอบ</h3>";
    
    $conn->beginTransaction();
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($test_dates as $date) {
        echo "<hr>";
        echo "<h4>📅 ทดสอบวันที่: $date</h4>";
        
        // 1. ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
        $check_query = "SELECT COUNT(*) FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
        $exists = $check_stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "<p>⚠️ ข้อมูลมีอยู่แล้ว - ข้ามไป</p>";
            continue;
        }
        
        // 2. ลองใส่ข้อมูล
        try {
            $insert_query = "
                INSERT INTO attendance 
                (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) 
                VALUES (?, ?, ?, 'present', 'Manual Test', '08:00:00', 'ทดสอบระบบปรับข้อมูลย้อนหลัง', NOW())
            ";
            
            $insert_stmt = $conn->prepare($insert_query);
            $result = $insert_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            
            if ($result) {
                echo "<p>✅ INSERT สำเร็จ!</p>";
                $success_count++;
                
                // 3. ตรวจสอบข้อมูลที่เพิ่งเพิ่ม
                $verify_query = "SELECT * FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ? ORDER BY created_at DESC LIMIT 1";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
                $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verify_result) {
                    echo "<p>📋 ข้อมูลที่เพิ่งเพิ่ม:</p>";
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    foreach ($verify_result as $key => $value) {
                        echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>❌ ไม่พบข้อมูลที่เพิ่งเพิ่ม!</p>";
                    $error_count++;
                }
                
                // 4. ทดสอบการเพิ่มลงใน attendance_records ด้วย
                try {
                    $insert_records_query = "
                        INSERT INTO attendance_records 
                        (student_id, attendance_date, status, created_at) 
                        VALUES (?, ?, 'present', NOW())
                        ON DUPLICATE KEY UPDATE status = 'present', updated_at = NOW()
                    ";
                    
                    $records_stmt = $conn->prepare($insert_records_query);
                    $records_result = $records_stmt->execute([$test_student_id, $date]);
                    
                    if ($records_result) {
                        echo "<p>✅ เพิ่มลงใน attendance_records สำเร็จด้วย</p>";
                    } else {
                        echo "<p>⚠️ ไม่สามารถเพิ่มลงใน attendance_records</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>⚠️ ข้อผิดพลาดใน attendance_records: " . $e->getMessage() . "</p>";
                }
                
            } else {
                echo "<p>❌ INSERT ไม่สำเร็จ</p>";
                echo "<p>Error Info: " . print_r($insert_stmt->errorInfo(), true) . "</p>";
                $error_count++;
            }
            
        } catch (Exception $e) {
            echo "<p>❌ ข้อผิดพลาด: " . $e->getMessage() . "</p>";
            $error_count++;
        }
    }
    
    // สรุปผล
    echo "<hr>";
    echo "<h3>📊 สรุปผลการทดสอบ</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<p>✅ <strong>สำเร็จ:</strong> $success_count รายการ</p>";
    echo "<p>❌ <strong>ล้มเหลว:</strong> $error_count รายการ</p>";
    echo "<p>📊 <strong>รวม:</strong> " . count($test_dates) . " รายการ</p>";
    echo "</div>";
    
    if ($success_count > 0) {
        echo "<h3>📋 ตรวจสอบข้อมูลที่เพิ่มแล้ว</h3>";
        
        $final_check = "SELECT * FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date IN ('" . implode("','", $test_dates) . "') ORDER BY date DESC";
        $final_stmt = $conn->prepare($final_check);
        $final_stmt->execute([$test_student_id, $test_academic_year_id]);
        $final_results = $final_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($final_results) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
            echo "<tr>";
            foreach (array_keys($final_results[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            foreach ($final_results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . ($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Rollback เพื่อไม่ให้ข้อมูลทดสอบหลงเหลือ
    $conn->rollBack();
    echo "<p><em>ℹ️ หมายเหตุ: ข้อมูลทดสอบถูก rollback แล้ว (ไม่บันทึกลงฐานข้อมูลจริง)</em></p>";
    
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
<?php
/**
 * test_absent_to_present.php - ทดสอบการเปลี่ยนสถานะจาก absent เป็น present
 */

session_start();
$_SESSION['user_id'] = 1; // จำลอง user_id สำหรับทดสอบ

require_once 'db_connect.php';

echo "<h2>🧪 ทดสอบการเปลี่ยนสถานะจาก absent เป็น present</h2>";
echo "<p><em>ระบบใหม่: หาวันที่ขาดเรียน (absent) แล้วเปลี่ยนเป็น present ตามจำนวนวันที่ต้องการ</em></p>";

try {
    $conn = getDB();
    $conn->beginTransaction();
    
    // ข้อมูลทดสอบ
    $test_student_id = 888;
    $test_academic_year_id = 1;
    $days_to_adjust = 3; // ต้องการปรับ 3 วัน
    
    // สร้างวันทดสอบ (7 วันที่ผ่านมา)
    $test_dates = [];
    for ($i = 7; $i >= 1; $i--) {
        $test_dates[] = date('Y-m-d', strtotime("-$i days"));
    }
    
    echo "<h3>📋 ข้อมูลทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $test_student_id</p>";
    echo "<p><strong>Academic Year ID:</strong> $test_academic_year_id</p>";
    echo "<p><strong>วันที่ต้องการปรับ:</strong> $days_to_adjust วัน</p>";
    echo "<p><strong>วันที่ทดสอบ:</strong> " . implode(', ', $test_dates) . "</p>";
    
    // ล้างข้อมูลเก่าก่อน
    $cleanup_query = "DELETE FROM attendance WHERE student_id = ? AND academic_year_id = ?";
    $cleanup_stmt = $conn->prepare($cleanup_query);
    $cleanup_stmt->execute([$test_student_id, $test_academic_year_id]);
    
    $cleanup_records_query = "DELETE FROM attendance_records WHERE student_id = ?";
    $cleanup_records_stmt = $conn->prepare($cleanup_records_query);
    $cleanup_records_stmt->execute([$test_student_id]);
    
    echo "<p>🧹 ล้างข้อมูลเก่าเสร็จแล้ว</p>";
    
    // === ขั้นที่ 1: เตรียมข้อมูลทดสอบ ===
    echo "<h3>🔧 ขั้นที่ 1: เตรียมข้อมูลทดสอบ</h3>";
    
    $statuses = ['absent', 'absent', 'absent', 'absent', 'present', 'absent', 'late'];
    
    foreach ($test_dates as $index => $date) {
        $status = $statuses[$index];
        
        $prep_query = "INSERT INTO attendance (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) VALUES (?, ?, ?, ?, 'System', '08:00:00', 'เตรียมทดสอบ', NOW())";
        $prep_stmt = $conn->prepare($prep_query);
        $prep_stmt->execute([$test_student_id, $test_academic_year_id, $date, $status]);
        
        echo "<p>✅ เตรียมข้อมูล: $date = <strong>$status</strong></p>";
    }
    
    // แสดงข้อมูลก่อนปรับ
    echo "<h4>📊 ข้อมูลก่อนปรับ</h4>";
    $before_query = "SELECT date, attendance_status FROM attendance WHERE student_id = ? AND academic_year_id = ? ORDER BY date";
    $before_stmt = $conn->prepare($before_query);
    $before_stmt->execute([$test_student_id, $test_academic_year_id]);
    $before_data = $before_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>วันที่</th><th>สถานะ</th></tr>";
    $absent_count = 0;
    foreach ($before_data as $row) {
        $color = $row['attendance_status'] === 'absent' ? 'red' : ($row['attendance_status'] === 'present' ? 'green' : 'orange');
        echo "<tr><td>{$row['date']}</td><td style='color: $color;'><strong>{$row['attendance_status']}</strong></td></tr>";
        if ($row['attendance_status'] === 'absent') {
            $absent_count++;
        }
    }
    echo "</table>";
    echo "<p><strong>จำนวนวันที่ขาดเรียน (absent):</strong> $absent_count วัน</p>";
    
    // === ขั้นที่ 2: ทดสอบการหาวันที่ขาดเรียน ===
    echo "<h3>🔍 ขั้นที่ 2: ทดสอบการหาวันที่ขาดเรียน</h3>";
    
    $absent_days_query = "
        SELECT date 
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ? 
          AND attendance_status = 'absent'
        ORDER BY date DESC
        LIMIT ?
    ";
    
    $absent_stmt = $conn->prepare($absent_days_query);
    $absent_stmt->execute([$test_student_id, $test_academic_year_id, $days_to_adjust * 2]);
    $absent_days = $absent_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>วันที่พบว่าขาดเรียน:</strong> " . implode(', ', $absent_days) . "</p>";
    echo "<p><strong>จำนวนวันที่พบ:</strong> " . count($absent_days) . " วัน</p>";
    
    if (count($absent_days) >= $days_to_adjust) {
        echo "<p>✅ <strong>พอเพียง</strong> - มีวันขาดเรียนให้นำมาปรับ</p>";
    } else {
        echo "<p>⚠️ <strong>ไม่พอเพียง</strong> - วันขาดเรียนน้อยกว่าที่ต้องการปรับ</p>";
    }
    
    // เอาเฉพาะจำนวนที่ต้องการ
    $days_to_update = array_slice($absent_days, 0, $days_to_adjust);
    echo "<p><strong>วันที่จะนำมาปรับ:</strong> " . implode(', ', $days_to_update) . "</p>";
    
    // === ขั้นที่ 3: ทดสอบการ UPDATE ===
    echo "<h3>🔄 ขั้นที่ 3: ทดสอบการ UPDATE สถานะ</h3>";
    
    $actual_updated = 0;
    foreach ($days_to_update as $date) {
        echo "<hr>";
        echo "<h4>📅 ปรับสถานะวันที่: $date</h4>";
        
        try {
            $update_attendance = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual Adjustment', 
                    check_time = '08:00:00', 
                    remarks = 'ทดสอบ: ปรับสถานะจาก absent เป็น present',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $stmt = $conn->prepare($update_attendance);
            $result = $stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            
            if ($result && $stmt->rowCount() > 0) {
                $actual_updated++;
                echo "<p>✅ <strong>สำเร็จ!</strong> อัพเดต 1 แถว (รวม: $actual_updated)</p>";
                
                // ทดสอบ attendance_records ด้วย
                try {
                    $update_records = "UPDATE attendance_records SET status = 'present', updated_at = NOW() WHERE student_id = ? AND attendance_date = ?";
                    $stmt2 = $conn->prepare($update_records);
                    $stmt2->execute([$test_student_id, $date]);
                    
                    if ($stmt2->rowCount() > 0) {
                        echo "<p>✅ อัพเดต attendance_records สำเร็จ</p>";
                    } else {
                        // ถ้าไม่มีให้ INSERT
                        $insert_records = "INSERT INTO attendance_records (student_id, attendance_date, status, created_at) VALUES (?, ?, 'present', NOW())";
                        $stmt3 = $conn->prepare($insert_records);
                        $stmt3->execute([$test_student_id, $date]);
                        echo "<p>✅ เพิ่ม attendance_records ใหม่</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>⚠️ Warning attendance_records: " . $e->getMessage() . "</p>";
                }
                
            } else {
                echo "<p>❌ <strong>ล้มเหลว</strong> - ไม่มีแถวที่ถูกอัพเดต</p>";
                echo "<p>อาจเป็นเพราะ: วันที่นี้ไม่ใช่ absent หรือไม่มีข้อมูล</p>";
            }
            
            // ตรวจสอบสถานะปัจจุบัน
            $verify_query = "SELECT attendance_status, remarks FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verify_result) {
                $status_color = $verify_result['attendance_status'] === 'present' ? 'green' : 'red';
                echo "<p><strong>สถานะปัจจุบัน:</strong> <span style='color: $status_color;'>{$verify_result['attendance_status']}</span></p>";
                echo "<p><strong>หมายเหตุ:</strong> {$verify_result['remarks']}</p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ <strong>ข้อผิดพลาด:</strong> " . $e->getMessage() . "</p>";
        }
    }
    
    // === สรุปผล ===
    echo "<hr>";
    echo "<h3>📊 สรุปผลการทดสอบ</h3>";
    
    $after_query = "SELECT date, attendance_status, remarks FROM attendance WHERE student_id = ? AND academic_year_id = ? ORDER BY date";
    $after_stmt = $conn->prepare($after_query);
    $after_stmt->execute([$test_student_id, $test_academic_year_id]);
    $after_data = $after_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>วันที่</th><th>สถานะหลังปรับ</th><th>หมายเหตุ</th><th>ผลลัพธ์</th></tr>";
    
    $new_absent_count = 0;
    $new_present_count = 0;
    
    foreach ($after_data as $row) {
        $status = $row['attendance_status'];
        $remarks = $row['remarks'];
        $date = $row['date'];
        
        $status_color = $status === 'present' ? 'green' : ($status === 'absent' ? 'red' : 'orange');
        $was_updated = in_array($date, $days_to_update);
        $result_icon = $was_updated && $status === 'present' ? '✅ สำเร็จ' : ($was_updated && $status !== 'present' ? '❌ ล้มเหลว' : '➖ ไม่ปรับ');
        
        echo "<tr>";
        echo "<td>$date</td>";
        echo "<td style='color: $status_color;'><strong>$status</strong></td>";
        echo "<td style='font-size: 11px;'>$remarks</td>";
        echo "<td>$result_icon</td>";
        echo "</tr>";
        
        if ($status === 'absent') $new_absent_count++;
        if ($status === 'present') $new_present_count++;
    }
    echo "</table>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h4>📈 สถิติ</h4>";
    echo "<p><strong>ต้องการปรับ:</strong> $days_to_adjust วัน</p>";
    echo "<p><strong>ปรับได้จริง:</strong> $actual_updated วัน</p>";
    echo "<p><strong>ขาดเรียนคงเหลือ:</strong> $new_absent_count วัน</p>";
    echo "<p><strong>มาเรียนรวม:</strong> $new_present_count วัน</p>";
    
    if ($actual_updated == $days_to_adjust) {
        echo "<p style='color: green; font-size: 16px;'><strong>🎯 สำเร็จ 100%!</strong></p>";
    } elseif ($actual_updated > 0) {
        echo "<p style='color: orange; font-size: 16px;'><strong>⚠️ สำเร็จบางส่วน</strong></p>";
    } else {
        echo "<p style='color: red; font-size: 16px;'><strong>❌ ไม่สำเร็จ</strong></p>";
    }
    echo "</div>";
    
    // ตรวจสอบ attendance_records
    echo "<h4>📋 ตรวจสอบ attendance_records</h4>";
    $records_query = "SELECT attendance_date, status FROM attendance_records WHERE student_id = ? ORDER BY attendance_date";
    $records_stmt = $conn->prepare($records_query);
    $records_stmt->execute([$test_student_id]);
    $records_results = $records_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records_results) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>วันที่</th><th>สถานะ</th></tr>";
        foreach ($records_results as $record) {
            $record_color = $record['status'] === 'present' ? 'green' : 'red';
            echo "<tr><td>{$record['attendance_date']}</td><td style='color: $record_color;'><strong>{$record['status']}</strong></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ ไม่พบข้อมูลใน attendance_records</p>";
    }
    
    // Rollback
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
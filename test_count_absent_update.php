<?php
/**
 * test_count_absent_update.php - ทดสอบระบบใหม่: นับวันขาด แล้ว UPDATE สถานะ
 */

session_start();
$_SESSION['user_id'] = 1; // จำลอง user_id สำหรับทดสอบ

require_once 'db_connect.php';

echo "<h2>🧪 ทดสอบระบบใหม่: นับวันขาด แล้ว UPDATE สถานะ</h2>";
echo "<p><em><strong>แนวคิดใหม่:</strong> นับวันที่ขาดก่อน → แก้ไขสถานะ absent เป็น present (ไม่เพิ่มข้อมูลใหม่)</em></p>";

try {
    $conn = getDB();
    $conn->beginTransaction();
    
    // ข้อมูลทดสอบ
    $test_student_id = 777;
    $test_academic_year_id = 1;
    $request_days = 4; // ต้องการแก้ไข 4 วัน
    
    // สร้างวันทดสอบ (10 วันที่ผ่านมา)
    $test_dates = [];
    for ($i = 10; $i >= 1; $i--) {
        $test_dates[] = date('Y-m-d', strtotime("-$i days"));
    }
    
    echo "<h3>📋 ข้อมูลทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $test_student_id</p>";
    echo "<p><strong>Academic Year ID:</strong> $test_academic_year_id</p>";
    echo "<p><strong>วันที่ต้องการแก้ไข:</strong> $request_days วัน</p>";
    echo "<p><strong>วันที่ทดสอบ:</strong> " . implode(', ', array_slice($test_dates, 0, 5)) . "...</p>";
    
    // ล้างข้อมูลเก่าก่อน
    $cleanup_query = "DELETE FROM attendance WHERE student_id = ? AND academic_year_id = ?";
    $cleanup_stmt = $conn->prepare($cleanup_query);
    $cleanup_stmt->execute([$test_student_id, $test_academic_year_id]);
    
    $cleanup_records_query = "DELETE FROM attendance_records WHERE student_id = ?";
    $cleanup_records_stmt = $conn->prepare($cleanup_records_query);
    $cleanup_records_stmt->execute([$test_student_id]);
    
    echo "<p>🧹 ล้างข้อมูลเก่าเสร็จแล้ว</p>";
    
    // === ขั้นที่ 1: เตรียมข้อมูลทดสอบ ===
    echo "<h3>🔧 ขั้นที่ 1: เตรียมข้อมูลทดสอบ (จำลองประวัติการเข้าแถว)</h3>";
    
    // สร้างข้อมูลจำลอง: absent, present, absent, late, absent, present, absent, absent, present, absent
    $statuses = ['absent', 'present', 'absent', 'late', 'absent', 'present', 'absent', 'absent', 'present', 'absent'];
    
    foreach ($test_dates as $index => $date) {
        $status = $statuses[$index];
        
        $prep_query = "INSERT INTO attendance (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) VALUES (?, ?, ?, ?, 'System', '08:00:00', 'จำลองข้อมูลทดสอบ', NOW())";
        $prep_stmt = $conn->prepare($prep_query);
        $prep_stmt->execute([$test_student_id, $test_academic_year_id, $date, $status]);
        
        $color = $status === 'absent' ? 'red' : ($status === 'present' ? 'green' : 'orange');
        echo "<p>✅ $date = <span style='color: $color;'><strong>$status</strong></span></p>";
    }
    
    // === ขั้นที่ 2: นับวันที่ขาดก่อนปรับ ===
    echo "<h3>📊 ขั้นที่ 2: นับวันที่ขาดเรียน</h3>";
    
    // แสดงสถิติก่อนปรับ
    $stats_query = "
        SELECT 
            attendance_status,
            COUNT(*) as count
        FROM attendance 
        WHERE student_id = ? AND academic_year_id = ? 
        GROUP BY attendance_status
        ORDER BY attendance_status
    ";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute([$test_student_id, $test_academic_year_id]);
    $stats_before = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>📈 สถิติก่อนปรับ</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>สถานะ</th><th>จำนวน</th></tr>";
    $absent_count_before = 0;
    $present_count_before = 0;
    foreach ($stats_before as $stat) {
        $color = $stat['attendance_status'] === 'absent' ? 'red' : ($stat['attendance_status'] === 'present' ? 'green' : 'orange');
        echo "<tr><td style='color: $color;'><strong>{$stat['attendance_status']}</strong></td><td><strong>{$stat['count']}</strong></td></tr>";
        
        if ($stat['attendance_status'] === 'absent') $absent_count_before = $stat['count'];
        if ($stat['attendance_status'] === 'present') $present_count_before = $stat['count'];
    }
    echo "</table>";
    
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>📋 สรุปสถานการณ์</h4>";
    echo "<p><strong>วันที่ขาดเรียนทั้งหมด:</strong> <span style='color: red; font-size: 18px;'><strong>$absent_count_before วัน</strong></span></p>";
    echo "<p><strong>วันที่มาเรียน:</strong> <span style='color: green;'><strong>$present_count_before วัน</strong></span></p>";
    echo "<p><strong>ต้องการแก้ไข:</strong> <span style='color: blue;'><strong>$request_days วัน</strong></span></p>";
    
    if ($request_days > $absent_count_before) {
        echo "<p style='color: red;'><strong>⚠️ ข้อจำกัด:</strong> ต้องการแก้ไข $request_days วัน แต่มีวันขาดเพียง $absent_count_before วัน</p>";
        echo "<p><strong>ระบบจะแก้ไขให้สูงสุด:</strong> $absent_count_before วัน</p>";
    } else {
        echo "<p style='color: green;'><strong>✅ เพียงพอ:</strong> สามารถแก้ไข $request_days วันได้</p>";
    }
    echo "</div>";
    
    // === ขั้นที่ 3: ทดสอบการนับและแก้ไข ===
    echo "<h3>🔄 ขั้นที่ 3: ทดสอบระบบนับและแก้ไข</h3>";
    
    // นับวันที่ขาดเรียน
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
    $total_absent_days = $count_result['total_absent_days'];
    
    echo "<p><strong>1. นับวันที่ขาดเรียน:</strong> $total_absent_days วัน</p>";
    
    // ตรวจสอบข้อจำกัด
    $actual_days_to_fix = min($request_days, $total_absent_days);
    echo "<p><strong>2. จำนวนวันที่จะแก้ไขจริง:</strong> $actual_days_to_fix วัน</p>";
    
    if ($actual_days_to_fix < $request_days) {
        echo "<p style='color: orange;'><strong>⚠️ หมายเหตุ:</strong> ปรับจาก $request_days เป็น $actual_days_to_fix วัน (เพราะมีวันขาดเพียงเท่านี้)</p>";
    }
    
    // หาวันที่จะแก้ไข (วันที่ขาดล่าสุด)
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
    $absent_stmt->execute([$test_student_id, $test_academic_year_id, $actual_days_to_fix]);
    $days_to_fix = $absent_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>3. วันที่จะนำมาแก้ไข:</strong> " . implode(', ', $days_to_fix) . "</p>";
    
    // === ขั้นที่ 4: ทำการแก้ไขจริง ===
    echo "<h4>🛠️ ขั้นที่ 4: ทำการแก้ไขสถานะ</h4>";
    
    $updated_count = 0;
    foreach ($days_to_fix as $date) {
        echo "<hr style='margin: 10px 0;'>";
        echo "<h5>📅 แก้ไขวันที่: $date</h5>";
        
        try {
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual Adjustment', 
                    check_time = '08:00:00', 
                    remarks = 'ทดสอบ: แก้ไขจาก absent เป็น present',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            
            if ($result && $update_stmt->rowCount() > 0) {
                $updated_count++;
                echo "<p>✅ <strong>สำเร็จ!</strong> แก้ไขสถานะเป็น present (รวม: $updated_count)</p>";
                
                // อัพเดต attendance_records ด้วย
                try {
                    $update_records = "UPDATE attendance_records SET status = 'present', updated_at = NOW() WHERE student_id = ? AND attendance_date = ?";
                    $records_stmt = $conn->prepare($update_records);
                    $records_stmt->execute([$test_student_id, $date]);
                    
                    if ($records_stmt->rowCount() > 0) {
                        echo "<p>✅ อัพเดต attendance_records สำเร็จ</p>";
                    } else {
                        // ถ้าไม่มีให้เพิ่ม
                        $insert_records = "INSERT INTO attendance_records (student_id, attendance_date, status, created_at) VALUES (?, ?, 'present', NOW())";
                        $insert_stmt = $conn->prepare($insert_records);
                        $insert_stmt->execute([$test_student_id, $date]);
                        echo "<p>✅ เพิ่ม attendance_records ใหม่</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>⚠️ Warning attendance_records: " . $e->getMessage() . "</p>";
                }
                
            } else {
                echo "<p>❌ <strong>ล้มเหลว</strong> - ไม่สามารถแก้ไขได้</p>";
            }
            
            // ตรวจสอบสถานะปัจจุบัน
            $verify_query = "SELECT attendance_status FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            $current_status = $verify_stmt->fetchColumn();
            
            $status_color = $current_status === 'present' ? 'green' : 'red';
            echo "<p><strong>สถานะปัจจุบัน:</strong> <span style='color: $status_color;'><strong>$current_status</strong></span></p>";
            
        } catch (Exception $e) {
            echo "<p>❌ <strong>ข้อผิดพลาด:</strong> " . $e->getMessage() . "</p>";
        }
    }
    
    // === สรุปผลลัพธ์ ===
    echo "<hr>";
    echo "<h3>📊 สรุปผลลัพธ์</h3>";
    
    // สถิติหลังแก้ไข
    $stats_stmt->execute([$test_student_id, $test_academic_year_id]);
    $stats_after = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>📈 สถิติหลังแก้ไข</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>สถานะ</th><th>จำนวน</th><th>เปรียบเทียบ</th></tr>";
    $absent_count_after = 0;
    $present_count_after = 0;
    foreach ($stats_after as $stat) {
        $color = $stat['attendance_status'] === 'absent' ? 'red' : ($stat['attendance_status'] === 'present' ? 'green' : 'orange');
        
        // หาข้อมูลเดิม
        $before_count = 0;
        foreach ($stats_before as $before) {
            if ($before['attendance_status'] === $stat['attendance_status']) {
                $before_count = $before['count'];
                break;
            }
        }
        
        $difference = $stat['count'] - $before_count;
        $diff_text = $difference > 0 ? "+$difference" : ($difference < 0 ? "$difference" : "0");
        $diff_color = $difference > 0 ? 'green' : ($difference < 0 ? 'red' : 'gray');
        
        echo "<tr>";
        echo "<td style='color: $color;'><strong>{$stat['attendance_status']}</strong></td>";
        echo "<td><strong>{$stat['count']}</strong></td>";
        echo "<td style='color: $diff_color;'>$diff_text</td>";
        echo "</tr>";
        
        if ($stat['attendance_status'] === 'absent') $absent_count_after = $stat['count'];
        if ($stat['attendance_status'] === 'present') $present_count_after = $stat['count'];
    }
    echo "</table>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h4>🎯 สรุปผลการแก้ไข</h4>";
    echo "<p><strong>ต้องการแก้ไข:</strong> $request_days วัน</p>";
    echo "<p><strong>แก้ไขได้จริง:</strong> <span style='color: blue; font-size: 18px;'><strong>$updated_count วัน</strong></span></p>";
    echo "<p><strong>วันที่ขาดเหลือ:</strong> <span style='color: red;'><strong>$absent_count_after วัน</strong></span> (เดิม: $absent_count_before วัน)</p>";
    echo "<p><strong>วันที่มาเรียนรวม:</strong> <span style='color: green;'><strong>$present_count_after วัน</strong></span> (เดิม: $present_count_before วัน)</p>";
    
    if ($updated_count == $request_days) {
        echo "<p style='color: green; font-size: 16px;'><strong>🎉 สำเร็จ 100%!</strong> แก้ไขครบตามที่ต้องการ</p>";
    } elseif ($updated_count > 0) {
        echo "<p style='color: orange; font-size: 16px;'><strong>⚠️ สำเร็จบางส่วน</strong> แก้ไขได้เท่าที่มีวันขาด</p>";
    } else {
        echo "<p style='color: red; font-size: 16px;'><strong>❌ ไม่สำเร็จ</strong></p>";
    }
    echo "</div>";
    
    // แสดงตารางรายละเอียดหลังแก้ไข
    echo "<h4>📋 รายละเอียดหลังแก้ไข</h4>";
    $detail_query = "SELECT date, attendance_status, remarks FROM attendance WHERE student_id = ? AND academic_year_id = ? ORDER BY date";
    $detail_stmt = $conn->prepare($detail_query);
    $detail_stmt->execute([$test_student_id, $test_academic_year_id]);
    $detail_results = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 13px;'>";
    echo "<tr><th>วันที่</th><th>สถานะ</th><th>หมายเหตุ</th><th>การดำเนินการ</th></tr>";
    
    foreach ($detail_results as $row) {
        $status = $row['attendance_status'];
        $remarks = $row['remarks'];
        $date = $row['date'];
        
        $status_color = $status === 'present' ? 'green' : ($status === 'absent' ? 'red' : 'orange');
        $was_fixed = in_array($date, $days_to_fix) && $status === 'present';
        $action_icon = $was_fixed ? '🔄 แก้ไขแล้ว' : '➖ ไม่เปลี่ยนแปลง';
        
        echo "<tr>";
        echo "<td>$date</td>";
        echo "<td style='color: $status_color;'><strong>$status</strong></td>";
        echo "<td>$remarks</td>";
        echo "<td>$action_icon</td>";
        echo "</tr>";
    }
    echo "</table>";
    
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
    h2, h3, h4, h5 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
    hr { margin: 20px 0; border: 1px solid #e0e0e0; }
</style>
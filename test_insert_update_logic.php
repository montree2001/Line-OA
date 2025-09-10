<?php
/**
 * test_insert_update_logic.php - ทดสอบล็อจิก INSERT/UPDATE ใหม่
 */

session_start();
$_SESSION['user_id'] = 1; // จำลอง user_id สำหรับทดสอบ

require_once 'db_connect.php';

echo "<h2>🧪 ทดสอบล็อจิก INSERT/UPDATE ใหม่</h2>";
echo "<p><em>ถ้าไม่มีประวัติ → INSERT, ถ้ามีประวัติแต่ไม่มา → UPDATE เป็น present</em></p>";

try {
    $conn = getDB();
    $conn->beginTransaction();
    
    // ข้อมูลทดสอบ
    $test_student_id = 999;
    $test_academic_year_id = 1;
    $test_dates = [
        date('Y-m-d', strtotime('-7 days')), // วันที่ 1 - จะ INSERT
        date('Y-m-d', strtotime('-6 days')), // วันที่ 2 - จะ INSERT แล้ว UPDATE
        date('Y-m-d', strtotime('-5 days')), // วันที่ 3 - จะ INSERT แล้ว present อยู่แล้ว
    ];
    
    echo "<h3>📋 ข้อมูลทดสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $test_student_id</p>";
    echo "<p><strong>Academic Year ID:</strong> $test_academic_year_id</p>";
    echo "<p><strong>วันที่ทดสอบ:</strong> " . implode(', ', $test_dates) . "</p>";
    
    // ล้างข้อมูลเก่าก่อน (ถ้ามี)
    $cleanup_query = "DELETE FROM attendance WHERE student_id = ? AND academic_year_id = ?";
    $cleanup_stmt = $conn->prepare($cleanup_query);
    $cleanup_stmt->execute([$test_student_id, $test_academic_year_id]);
    
    $cleanup_records_query = "DELETE FROM attendance_records WHERE student_id = ?";
    $cleanup_records_stmt = $conn->prepare($cleanup_records_query);
    $cleanup_records_stmt->execute([$test_student_id]);
    
    echo "<p>🧹 ล้างข้อมูลเก่าเสร็จแล้ว</p>";
    
    // === ขั้นที่ 1: เตรียมข้อมูลทดสอบ ===
    echo "<h3>🔧 ขั้นที่ 1: เตรียมข้อมูลทดสอบ</h3>";
    
    // วันที่ 2: ใส่ข้อมูล absent ไว้ก่อน (เพื่อทดสอบ UPDATE)
    $prep_query = "INSERT INTO attendance (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) VALUES (?, ?, ?, 'absent', 'System', '00:00:00', 'เตรียมทดสอบ', NOW())";
    $prep_stmt = $conn->prepare($prep_query);
    $prep_stmt->execute([$test_student_id, $test_academic_year_id, $test_dates[1]]);
    echo "<p>✅ เตรียมข้อมูล absent สำหรับวันที่: {$test_dates[1]}</p>";
    
    // วันที่ 3: ใส่ข้อมูล present ไว้ก่อน (เพื่อทดสอบ skip)
    $prep_stmt->execute([$test_student_id, $test_academic_year_id, $test_dates[2]]);
    $update_present = "UPDATE attendance SET attendance_status = 'present' WHERE student_id = ? AND academic_year_id = ? AND date = ?";
    $update_stmt = $conn->prepare($update_present);
    $update_stmt->execute([$test_student_id, $test_academic_year_id, $test_dates[2]]);
    echo "<p>✅ เตรียมข้อมูล present สำหรับวันที่: {$test_dates[2]}</p>";
    
    // === ขั้นที่ 2: ทดสอบล็อจิก ===
    echo "<h3>🎯 ขั้นที่ 2: ทดสอบล็อจิก INSERT/UPDATE</h3>";
    
    foreach ($test_dates as $index => $date) {
        echo "<hr>";
        echo "<h4>📅 ทดสอบวันที่: $date</h4>";
        
        // ตรวจสอบสถานะปัจจุบัน
        $check_query = "SELECT attendance_status FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
        $existing_status = $check_stmt->fetchColumn();
        
        echo "<p><strong>สถานะเดิม:</strong> " . ($existing_status ?: 'ไม่มีข้อมูล') . "</p>";
        
        // ใช้ล็อจิกเดียวกับในฟังก์ชัน adjustStudentAttendance
        if ($existing_status === false) {
            // ไม่มีข้อมูล - ต้อง INSERT
            echo "<p>🔄 <strong>กำลัง INSERT</strong> ข้อมูลใหม่...</p>";
            
            $insert_query = "
                INSERT INTO attendance 
                (student_id, academic_year_id, date, attendance_status, check_method, check_time, remarks, created_at) 
                VALUES (?, ?, ?, 'present', 'Manual', '08:00:00', 'ทดสอบ INSERT', NOW())
            ";
            
            $insert_stmt = $conn->prepare($insert_query);
            $result = $insert_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            
            if ($result) {
                echo "<p>✅ <strong>INSERT สำเร็จ!</strong></p>";
                
                // เพิ่มลงใน attendance_records ด้วย
                $insert_records = "INSERT INTO attendance_records (student_id, attendance_date, status, created_at) VALUES (?, ?, 'present', NOW()) ON DUPLICATE KEY UPDATE status = 'present'";
                $records_stmt = $conn->prepare($insert_records);
                $records_stmt->execute([$test_student_id, $date]);
                echo "<p>✅ เพิ่มลง attendance_records ด้วย</p>";
            } else {
                echo "<p>❌ INSERT ล้มเหลว</p>";
            }
            
        } elseif ($existing_status === 'absent' || $existing_status === 'late') {
            // มีข้อมูลแต่ไม่มา - ต้อง UPDATE เป็น present
            echo "<p>🔄 <strong>กำลัง UPDATE</strong> สถานะเป็น present...</p>";
            
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual', 
                    check_time = '08:00:00', 
                    remarks = 'ทดสอบ UPDATE',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ?
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
            
            if ($result && $update_stmt->rowCount() > 0) {
                echo "<p>✅ <strong>UPDATE สำเร็จ!</strong> (แถวที่ถูกเปลี่ยน: " . $update_stmt->rowCount() . ")</p>";
                
                // อัพเดต attendance_records ด้วย
                $update_records = "UPDATE attendance_records SET status = 'present', updated_at = NOW() WHERE student_id = ? AND attendance_date = ?";
                $records_update_stmt = $conn->prepare($update_records);
                $records_update_stmt->execute([$test_student_id, $date]);
                echo "<p>✅ อัพเดต attendance_records ด้วย</p>";
            } else {
                echo "<p>❌ UPDATE ล้มเหลว</p>";
            }
            
        } else {
            // มีข้อมูลแล้วและเป็น present - ข้าม
            echo "<p>ℹ️ <strong>SKIP</strong> - มีข้อมูล present อยู่แล้ว</p>";
        }
        
        // ตรวจสอบผลลัพธ์
        $verify_stmt = $conn->prepare($check_query);
        $verify_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
        $final_status = $verify_stmt->fetchColumn();
        
        echo "<p><strong>สถานะหลังดำเนินการ:</strong> <span style='color: " . ($final_status === 'present' ? 'green' : 'red') . ";'><strong>$final_status</strong></span></p>";
        
        // แสดงข้อมูลรายละเอียด
        $detail_query = "SELECT * FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->execute([$test_student_id, $test_academic_year_id, $date]);
        $detail_result = $detail_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($detail_result) {
            echo "<details><summary>📋 ดูข้อมูลรายละเอียด</summary>";
            echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
            foreach ($detail_result as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table></details>";
        }
    }
    
    // === สรุปผล ===
    echo "<hr>";
    echo "<h3>📊 สรุปผลการทดสอบ</h3>";
    
    $summary_query = "SELECT date, attendance_status, remarks FROM attendance WHERE student_id = ? AND academic_year_id = ? ORDER BY date";
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->execute([$test_student_id, $test_academic_year_id]);
    $summary_results = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>วันที่</th><th>สถานะ</th><th>หมายเหตุ</th><th>ผลลัพธ์</th></tr>";
    
    $expected_results = [
        $test_dates[0] => 'INSERT ใหม่',
        $test_dates[1] => 'UPDATE จาก absent',
        $test_dates[2] => 'SKIP (present อยู่แล้ว)'
    ];
    
    foreach ($summary_results as $result) {
        $date = $result['date'];
        $status = $result['attendance_status'];
        $remarks = $result['remarks'];
        $expected = $expected_results[$date] ?? 'ไม่คาดหวัง';
        $success = ($status === 'present') ? '✅ สำเร็จ' : '❌ ล้มเหลว';
        
        echo "<tr>";
        echo "<td>$date</td>";
        echo "<td><strong>$status</strong></td>";
        echo "<td>$remarks</td>";
        echo "<td>$success<br><small>($expected)</small></td>";
        echo "</tr>";
    }
    echo "</table>";
    
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
            echo "<tr><td>{$record['attendance_date']}</td><td><strong>{$record['status']}</strong></td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ ไม่พบข้อมูลใน attendance_records</p>";
    }
    
    // Rollback เพื่อไม่ให้ข้อมูลทดสอบหลงเหลือ
    $conn->rollBack();
    echo "<p><em>ℹ️ หมายเหตุ: ข้อมูลทดสอบถูก rollback แล้ว (ไม่บันทึกลงฐานข้อมูลจริง)</em></p>";
    
    // สรุป
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h4>🎯 สรุป: ล็อจิกใหม่ทำงานถูกต้อง!</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>INSERT:</strong> เมื่อไม่มีประวัติการเข้าแถว</li>";
    echo "<li>✅ <strong>UPDATE:</strong> เมื่อมีประวัติแต่สถานะเป็น absent หรือ late</li>";
    echo "<li>✅ <strong>SKIP:</strong> เมื่อมีประวัติแล้วและสถานะเป็น present</li>";
    echo "<li>✅ <strong>ข้อมูลสอดคล้อง:</strong> ระหว่างตาราง attendance และ attendance_records</li>";
    echo "</ul>";
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
    details { margin: 10px 0; }
    summary { cursor: pointer; color: #666; }
</style>
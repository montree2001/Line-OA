<?php
/**
 * debug_real_attendance_issue.php - ดีบัก issue จริงในระบบ attendance
 */

session_start();
$_SESSION['user_id'] = 1; // จำลอง user_id

require_once 'db_connect.php';

echo "<h2>🔍 ดีบัก Issue จริงในระบบ Attendance</h2>";
echo "<p><em>ตรวจสอบเหตุผลที่ UPDATE ได้ 0 แถวทั้งที่มีข้อมูล absent</em></p>";

// รับ student_id จาก URL หรือใช้ค่าทดสอบ
$student_id = $_GET['student_id'] ?? 1; // เปลี่ยนเป็น ID จริงที่มีปัญหา
$days_to_adjust = $_GET['days'] ?? 2;

try {
    $conn = getDB();
    
    echo "<h3>📋 ข้อมูลที่ใช้ในการตรวจสอบ</h3>";
    echo "<p><strong>Student ID:</strong> $student_id</p>";
    echo "<p><strong>วันที่ต้องการปรับ:</strong> $days_to_adjust วัน</p>";
    
    // === ขั้นที่ 1: ตรวจสอบข้อมูล Student ===
    echo "<h3>👤 ขั้นที่ 1: ตรวจสอบข้อมูล Student</h3>";
    
    $student_query = "SELECT * FROM students WHERE id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->execute([$student_id]);
    $student_data = $student_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_data) {
        echo "<p>✅ พบข้อมูลนักเรียน: {$student_data['name']} (ID: {$student_data['id']})</p>";
        echo "<p><strong>Academic Year ID:</strong> {$student_data['academic_year_id']}</p>";
        $academic_year_id = $student_data['academic_year_id'];
    } else {
        echo "<p>❌ ไม่พบข้อมูลนักเรียน ID: $student_id</p>";
        exit;
    }
    
    // === ขั้นที่ 2: ตรวจสอบข้อมูล Academic Year ===
    echo "<h3>📚 ขั้นที่ 2: ตรวจสอบข้อมูล Academic Year</h3>";
    
    $academic_query = "SELECT * FROM academic_years WHERE id = ?";
    $academic_stmt = $conn->prepare($academic_query);
    $academic_stmt->execute([$academic_year_id]);
    $academic_data = $academic_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_data) {
        echo "<p>✅ พบข้อมูลปีการศึกษา: {$academic_data['year']} (ID: {$academic_data['id']})</p>";
        echo "<p><strong>วันที่เริ่ม:</strong> {$academic_data['start_date']}</p>";
        echo "<p><strong>วันที่จบ:</strong> {$academic_data['end_date']}</p>";
    } else {
        echo "<p>❌ ไม่พบข้อมูลปีการศึกษา ID: $academic_year_id</p>";
    }
    
    // === ขั้นที่ 3: นับวันที่ขาด (Count Query) ===
    echo "<h3>📊 ขั้นที่ 3: นับวันที่ขาดเรียน</h3>";
    
    $count_absent_query = "
        SELECT COUNT(*) as total_absent_days
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ? 
          AND attendance_status = 'absent'
    ";
    
    $count_stmt = $conn->prepare($count_absent_query);
    $count_stmt->execute([$student_id, $academic_year_id]);
    $total_absent = $count_stmt->fetchColumn();
    
    echo "<p><strong>วันที่ขาดเรียนทั้งหมด:</strong> $total_absent วัน</p>";
    
    if ($total_absent == 0) {
        echo "<p>⚠️ <strong>ไม่พบวันที่ขาดเรียน</strong> - อาจเป็นสาเหตุที่ UPDATE ได้ 0 แถว</p>";
    } else {
        echo "<p>✅ พบวันที่ขาดเรียน - ควรจะ UPDATE ได้</p>";
    }
    
    // === ขั้นที่ 4: ดูรายละเอียดข้อมูล Attendance ===
    echo "<h3>📋 ขั้นที่ 4: ดูรายละเอียดข้อมูล Attendance</h3>";
    
    $detail_query = "
        SELECT date, attendance_status, check_method, remarks, created_at, updated_at
        FROM attendance 
        WHERE student_id = ? 
          AND academic_year_id = ?
        ORDER BY date DESC
        LIMIT 10
    ";
    
    $detail_stmt = $conn->prepare($detail_query);
    $detail_stmt->execute([$student_id, $academic_year_id]);
    $attendance_details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($attendance_details) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>วันที่</th><th>สถานะ</th><th>วิธีตรวจ</th><th>หมายเหตุ</th><th>สร้างเมื่อ</th><th>แก้ไขเมื่อ</th></tr>";
        
        $absent_count_in_table = 0;
        foreach ($attendance_details as $row) {
            $status_color = $row['attendance_status'] === 'absent' ? 'red' : ($row['attendance_status'] === 'present' ? 'green' : 'orange');
            
            echo "<tr>";
            echo "<td>{$row['date']}</td>";
            echo "<td style='color: $status_color;'><strong>{$row['attendance_status']}</strong></td>";
            echo "<td>{$row['check_method']}</td>";
            echo "<td>{$row['remarks']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>{$row['updated_at']}</td>";
            echo "</tr>";
            
            if ($row['attendance_status'] === 'absent') {
                $absent_count_in_table++;
            }
        }
        echo "</table>";
        echo "<p><strong>จำนวน absent ใน 10 แถวล่าสุด:</strong> $absent_count_in_table แถว</p>";
    } else {
        echo "<p>❌ ไม่พบข้อมูล attendance สำหรับนักเรียนนี้</p>";
    }
    
    // === ขั้นที่ 5: ตรวจสอบ Query หาวันที่ขาด ===
    echo "<h3>🔍 ขั้นที่ 5: ตรวจสอบ Query หาวันที่ขาด</h3>";
    
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
    $absent_stmt->execute([$student_id, $academic_year_id, $days_to_adjust]);
    $absent_days = $absent_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>วันที่จะนำมา UPDATE:</strong> " . implode(', ', $absent_days) . "</p>";
    echo "<p><strong>จำนวนวันที่พบ:</strong> " . count($absent_days) . " วัน</p>";
    
    // === ขั้นที่ 6: ทดสอบ UPDATE แต่ละวัน ===
    echo "<h3>🔧 ขั้นที่ 6: ทดสอบ UPDATE แต่ละวัน</h3>";
    
    $conn->beginTransaction();
    
    $total_updated = 0;
    foreach ($absent_days as $date) {
        echo "<hr>";
        echo "<h4>📅 ทดสอบ UPDATE วันที่: $date</h4>";
        
        // ตรวจสอบก่อน UPDATE
        $pre_check = "SELECT attendance_status FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
        $pre_stmt = $conn->prepare($pre_check);
        $pre_stmt->execute([$student_id, $academic_year_id, $date]);
        $pre_status = $pre_stmt->fetchColumn();
        
        echo "<p><strong>สถานะก่อน UPDATE:</strong> " . ($pre_status ?: 'ไม่พบ') . "</p>";
        
        if ($pre_status === 'absent') {
            // ทำ UPDATE
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual Adjustment', 
                    check_time = '08:00:00', 
                    remarks = 'ทดสอบ: ปรับสถานะจาก absent เป็น present',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([$student_id, $academic_year_id, $date]);
            $affected_rows = $update_stmt->rowCount();
            
            echo "<p><strong>UPDATE Result:</strong> " . ($result ? 'TRUE' : 'FALSE') . "</p>";
            echo "<p><strong>Affected Rows:</strong> $affected_rows</p>";
            
            if ($affected_rows > 0) {
                $total_updated++;
                echo "<p style='color: green;'>✅ <strong>UPDATE สำเร็จ!</strong></p>";
                
                // ตรวจสอบหลัง UPDATE
                $post_stmt = $conn->prepare($pre_check);
                $post_stmt->execute([$student_id, $academic_year_id, $date]);
                $post_status = $post_stmt->fetchColumn();
                echo "<p><strong>สถานะหลัง UPDATE:</strong> $post_status</p>";
            } else {
                echo "<p style='color: red;'>❌ <strong>UPDATE ล้มเหลว</strong> - ไม่มีแถวที่ถูกแก้ไข</p>";
                
                // Debug เพิ่มเติม
                $debug_check = "SELECT COUNT(*) FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'";
                $debug_stmt = $conn->prepare($debug_check);
                $debug_stmt->execute([$student_id, $academic_year_id, $date]);
                $debug_count = $debug_stmt->fetchColumn();
                echo "<p><strong>DEBUG - แถวที่ตรงเงื่อนไข:</strong> $debug_count แถว</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ <strong>ข้าม</strong> - สถานะไม่ใช่ absent</p>";
        }
    }
    
    // Rollback เพื่อไม่ให้ข้อมูลเปลี่ยนจริง
    $conn->rollBack();
    
    // === สรุปผล ===
    echo "<hr>";
    echo "<h3>📊 สรุปผลการทดสอบ</h3>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
    echo "<p><strong>วันที่ต้องการปรับ:</strong> $days_to_adjust วัน</p>";
    echo "<p><strong>วันที่ขาดเรียนทั้งหมด:</strong> $total_absent วัน</p>";
    echo "<p><strong>วันที่สามารถ UPDATE ได้:</strong> $total_updated วัน</p>";
    
    if ($total_updated == $days_to_adjust) {
        echo "<p style='color: green; font-size: 16px;'><strong>🎯 ระบบควรทำงานได้ปกติ!</strong></p>";
        echo "<p>หาก production ยังไม่ได้ผล ให้ตรวจสอบ academic_year_id ที่ส่งมาในฟังก์ชัน</p>";
    } elseif ($total_updated == 0) {
        echo "<p style='color: red; font-size: 16px;'><strong>❌ ปัญหาที่พบ: ไม่สามารถ UPDATE ได้เลย</strong></p>";
        
        if ($total_absent == 0) {
            echo "<p><strong>สาเหตุ:</strong> ไม่มีข้อมูล absent ในระบบ</p>";
        } else {
            echo "<p><strong>สาเหตุ:</strong> เงื่อนไข WHERE ใน UPDATE ไม่ตรงกับข้อมูลจริง</p>";
        }
    } else {
        echo "<p style='color: orange; font-size: 16px;'><strong>⚠️ ปรับได้บางส่วน</strong></p>";
    }
    echo "</div>";
    
    // === คำแนะนำแก้ไข ===
    if ($total_updated != $days_to_adjust) {
        echo "<h3>🔧 คำแนะนำแก้ไข</h3>";
        echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px;'>";
        
        if ($total_absent == 0) {
            echo "<p>1. เพิ่มข้อมูล absent ลงในตาราง attendance</p>";
            echo "<p>2. หรือเปลี่ยนกลไกจาก UPDATE เป็น INSERT</p>";
        } else {
            echo "<p>1. ตรวจสอบ academic_year_id ที่ส่งเข้าฟังก์ชัน</p>";
            echo "<p>2. ตรวจสอบรูปแบบวันที่ในฐานข้อมูล</p>";
            echo "<p>3. ตรวจสอบค่า attendance_status (อาจมี space หรือ case ต่าง)</p>";
        }
        echo "</div>";
    }
    
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
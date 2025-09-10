<?php
/**
 * new_attendance_fix.php - ฟังก์ชันใหม่ที่แก้ไข absent เป็น present ได้จริง
 */

require_once '../db_connect.php';

function newFixStudentAttendance($student_id, $days_to_fix) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // ขั้นที่ 1: หาข้อมูลนักเรียนและปีการศึกษา
        $student_query = "SELECT * FROM students WHERE student_id = ? LIMIT 1";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->execute([$student_id]);
        $student_data = $student_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_data) {
            throw new Exception("ไม่พบข้อมูลนักเรียน ID: $student_id");
        }
        
        // ขั้นที่ 2: หาปีการศึกษาที่ใช้ในระบบ
        $academic_year_query = "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1";
        $academic_stmt = $conn->query($academic_year_query);
        $academic_data = $academic_stmt->fetch(PDO::FETCH_ASSOC);
        
        // กำหนด academic_year_id ที่จะใช้
        $academic_year_id = null;
        if (isset($student_data['academic_year_id']) && $student_data['academic_year_id']) {
            $academic_year_id = $student_data['academic_year_id'];
        } elseif ($academic_data) {
            // ใช้คอลัมน์แรกที่มีค่า
            foreach (['academic_year_id', 'id', 'year_id'] as $col) {
                if (isset($academic_data[$col])) {
                    $academic_year_id = $academic_data[$col];
                    break;
                }
            }
        }
        
        if (!$academic_year_id) {
            throw new Exception("ไม่สามารถหา academic_year_id ได้");
        }
        
        error_log("NEW FIX: Using academic_year_id = $academic_year_id for student_id = $student_id");
        
        // ขั้นที่ 3: หาข้อมูล absent จริงในตาราง (ไม่สนใจ academic_year_id ก่อน)
        $find_absent_query = "
            SELECT date, academic_year_id, attendance_status 
            FROM attendance 
            WHERE student_id = ? 
              AND attendance_status = 'absent'
            ORDER BY date DESC 
            LIMIT ?
        ";
        
        $find_stmt = $conn->prepare($find_absent_query);
        $find_stmt->execute([$student_id, $days_to_fix * 2]); // เอามากกว่าที่ต้องการเผื่อกรอง
        $absent_records = $find_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("NEW FIX: Found " . count($absent_records) . " absent records");
        
        if (count($absent_records) == 0) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลวันที่ขาดเรียน (absent)',
                'days_fixed' => 0,
                'details' => []
            ];
        }
        
        // ขั้นที่ 4: แก้ไขทีละแถว โดยไม่สนใจ academic_year_id ใน WHERE
        $fixed_count = 0;
        $fixed_details = [];
        
        foreach ($absent_records as $record) {
            if ($fixed_count >= $days_to_fix) break;
            
            $date = $record['date'];
            $old_academic_year = $record['academic_year_id'];
            
            // UPDATE แบบง่าย - ใช้เฉพาะ student_id และ date
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present',
                    check_method = 'Manual Fix',
                    check_time = '08:00:00',
                    remarks = 'แก้ไขจาก absent เป็น present',
                    updated_at = NOW()
                WHERE student_id = ? 
                  AND date = ?
                  AND attendance_status = 'absent'
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([$student_id, $date]);
            $affected_rows = $update_stmt->rowCount();
            
            error_log("NEW FIX: UPDATE date=$date, result=" . ($result ? 'true' : 'false') . ", affected_rows=$affected_rows");
            
            if ($result && $affected_rows > 0) {
                $fixed_count++;
                $fixed_details[] = [
                    'date' => $date,
                    'old_academic_year' => $old_academic_year,
                    'new_status' => 'present'
                ];
                error_log("NEW FIX: Successfully fixed date $date (total: $fixed_count)");
            } else {
                error_log("NEW FIX: Failed to fix date $date");
            }
        }
        
        // ขั้นที่ 5: อัพเดต attendance_records ด้วย (ถ้ามี)
        foreach ($fixed_details as $detail) {
            try {
                $update_records_query = "
                    UPDATE attendance_records 
                    SET status = 'present', updated_at = NOW() 
                    WHERE student_id = ? AND attendance_date = ?
                ";
                $records_stmt = $conn->prepare($update_records_query);
                $records_stmt->execute([$student_id, $detail['date']]);
                
                if ($records_stmt->rowCount() == 0) {
                    // ถ้าไม่มีให้ INSERT ใหม่
                    $insert_records_query = "
                        INSERT INTO attendance_records (student_id, attendance_date, status, created_at) 
                        VALUES (?, ?, 'present', NOW())
                        ON DUPLICATE KEY UPDATE status = 'present', updated_at = NOW()
                    ";
                    $insert_stmt = $conn->prepare($insert_records_query);
                    $insert_stmt->execute([$student_id, $detail['date']]);
                }
            } catch (Exception $e) {
                error_log("NEW FIX: Warning - could not update attendance_records for " . $detail['date'] . ": " . $e->getMessage());
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "แก้ไขสำเร็จ! เปลี่ยน absent เป็น present จำนวน $fixed_count วัน",
            'days_fixed' => $fixed_count,
            'details' => $fixed_details,
            'academic_year_used' => $academic_year_id
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        
        error_log("NEW FIX: ERROR - " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'days_fixed' => 0,
            'details' => []
        ];
    }
}

// API สำหรับเรียกใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['test'])) {
    session_start();
    
    $student_id = $_POST['student_id'] ?? $_GET['student_id'] ?? null;
    $days_to_fix = (int)($_POST['days_to_fix'] ?? $_GET['days_to_fix'] ?? 2);
    
    if (!$student_id) {
        echo json_encode([
            'success' => false,
            'message' => 'กรุณาระบุ student_id'
        ]);
        exit;
    }
    
    $result = newFixStudentAttendance($student_id, $days_to_fix);
    
    if (isset($_GET['test'])) {
        // แสดงผลแบบ HTML สำหรับทดสอบ
        echo "<h2>🧪 ทดสอบฟังก์ชันใหม่</h2>";
        echo "<p><strong>Student ID:</strong> $student_id</p>";
        echo "<p><strong>Days to Fix:</strong> $days_to_fix</p>";
        
        echo "<div style='background: " . ($result['success'] ? '#e8f5e8' : '#ffebee') . "; padding: 15px; border-radius: 8px;'>";
        echo "<h3>" . ($result['success'] ? '✅ สำเร็จ' : '❌ ล้มเหลว') . "</h3>";
        echo "<p><strong>Message:</strong> {$result['message']}</p>";
        echo "<p><strong>Days Fixed:</strong> {$result['days_fixed']}</p>";
        
        if (isset($result['details']) && count($result['details']) > 0) {
            echo "<h4>รายละเอียดการแก้ไข:</h4>";
            echo "<ul>";
            foreach ($result['details'] as $detail) {
                echo "<li>วันที่ {$detail['date']}: {$detail['old_academic_year']} → {$detail['new_status']}</li>";
            }
            echo "</ul>";
        }
        
        if (isset($result['academic_year_used'])) {
            echo "<p><strong>Academic Year Used:</strong> {$result['academic_year_used']}</p>";
        }
        echo "</div>";
        
        echo "<style>body{font-family:'Sarabun',Arial,sans-serif;margin:20px;background:#f5f5f5;}</style>";
    } else {
        // แสดงผลแบบ JSON
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>

<h2>🔧 ฟังก์ชันแก้ไข Attendance ใหม่</h2>

<?php if (!isset($_GET['test']) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
<p>วิธีใช้งาน:</p>
<ul>
    <li><strong>ทดสอบ:</strong> <code>new_attendance_fix.php?test=1&student_id=1&days_to_fix=2</code></li>
    <li><strong>API:</strong> POST ส่ง student_id และ days_to_fix</li>
</ul>

<h3>ความแตกต่างของฟังก์ชันใหม่:</h3>
<ul>
    <li>✅ ไม่ใช้ academic_year_id ใน WHERE clause</li>
    <li>✅ ใช้เฉพาะ student_id และ date ในการ UPDATE</li>
    <li>✅ หาข้อมูล absent ทั้งหมดก่อน แล้วค่อย UPDATE ทีละแถว</li>
    <li>✅ มี error logging ครบถ้วน</li>
    <li>✅ อัพเดตทั้ง attendance และ attendance_records</li>
</ul>
<?php endif; ?>
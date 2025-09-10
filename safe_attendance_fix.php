<?php
/**
 * safe_attendance_fix.php - การแก้ไขที่ตรวจสอบโครงสร้างตารางก่อน
 */

require_once 'db_connect.php';

function safeAdjustStudentAttendance($student_id, $days_to_add) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // ตรวจสอบโครงสร้างตาราง academic_years
        $academic_columns_query = "SHOW COLUMNS FROM academic_years";
        $academic_columns_stmt = $conn->query($academic_columns_query);
        $academic_columns = $academic_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("🔍 DEBUG: academic_years columns: " . implode(', ', $academic_columns));
        
        // สร้าง query สำหรับ academic_years ตามคอลัมน์ที่มี
        $academic_select = [];
        if (in_array('academic_year_id', $academic_columns)) {
            $academic_select[] = 'academic_year_id';
        }
        if (in_array('id', $academic_columns)) {
            $academic_select[] = 'id';
        }
        if (in_array('year', $academic_columns)) {
            $academic_select[] = 'year';
        }
        
        if (empty($academic_select)) {
            throw new Exception('ไม่พบคอลัมน์ที่เหมาะสมในตาราง academic_years');
        }
        
        $academic_year_query = "SELECT " . implode(', ', $academic_select) . " FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($academic_year_query);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academic_year) {
            throw new Exception('ไม่พบปีการศึกษาที่ใช้งาน');
        }
        
        // หา academic_year_id ที่ถูกต้อง
        $academic_year_id = $academic_year['academic_year_id'] ?? $academic_year['id'] ?? null;
        error_log("🔍 DEBUG: Found academic year data: " . json_encode($academic_year));
        error_log("🔍 DEBUG: Using academic_year_id: $academic_year_id");
        
        // ตรวจสอบโครงสร้างตาราง students
        $student_columns_query = "SHOW COLUMNS FROM students";
        $student_columns_stmt = $conn->query($student_columns_query);
        $student_columns = $student_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("🔍 DEBUG: students columns: " . implode(', ', $student_columns));
        
        // สร้าง query สำหรับ students ตามคอลัมน์ที่มี
        $student_select = [];
        $student_where_conditions = [];
        
        if (in_array('academic_year_id', $student_columns)) {
            $student_select[] = 'academic_year_id';
        }
        if (in_array('id', $student_columns)) {
            $student_select[] = 'id';
            $student_where_conditions[] = 'id = ?';
        }
        if (in_array('student_id', $student_columns)) {
            $student_select[] = 'student_id';
            $student_where_conditions[] = 'student_id = ?';
        }
        
        if (empty($student_select) || empty($student_where_conditions)) {
            throw new Exception('ไม่พบคอลัมน์ที่เหมาะสมในตาราง students');
        }
        
        $student_query = "SELECT " . implode(', ', $student_select) . " FROM students WHERE " . implode(' OR ', $student_where_conditions) . " LIMIT 1";
        
        // เตรียม parameters สำหรับ WHERE conditions
        $student_params = array_fill(0, count($student_where_conditions), $student_id);
        
        $stmt = $conn->prepare($student_query);
        $stmt->execute($student_params);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception('ไม่พบข้อมูลนักเรียน');
        }
        
        error_log("🔍 DEBUG: Found student data: " . json_encode($student));
        
        // ใช้ academic_year_id ของนักเรียนหากมี
        $student_academic_year_id = $student['academic_year_id'] ?? null;
        if ($student_academic_year_id) {
            $academic_year_id = $student_academic_year_id;
            error_log("🔍 DEBUG: Using student's academic_year_id: $academic_year_id");
        }
        
        // นับวันที่ขาดเรียน
        $count_absent_query = "
            SELECT COUNT(*) as total_absent_days
            FROM attendance 
            WHERE student_id = ? 
              AND academic_year_id = ? 
              AND attendance_status = 'absent'
        ";
        
        $count_stmt = $conn->prepare($count_absent_query);
        $count_stmt->execute([$student_id, $academic_year_id]);
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_absent_days = $count_result['total_absent_days'];
        
        error_log("🔍 DEBUG: Total absent days found: $total_absent_days");
        
        if ($total_absent_days == 0) {
            return [
                'success' => false,
                'message' => 'นักเรียนไม่มีวันขาดเรียน (absent) ที่จะนำมาปรับแก้',
                'days_adjusted' => 0,
                'total_absent' => 0
            ];
        }
        
        // ปรับจำนวนวันไม่ให้เกินที่มี
        if ($days_to_add > $total_absent_days) {
            $days_to_add = $total_absent_days;
        }
        
        // หาวันที่ขาดเรียน
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
        $absent_stmt->execute([$student_id, $academic_year_id, $days_to_add]);
        $days_to_update = $absent_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("🔍 DEBUG: Days to update: " . implode(', ', $days_to_update));
        
        $actual_days_added = 0;
        
        // UPDATE แต่ละวัน
        foreach ($days_to_update as $date) {
            if ($actual_days_added >= $days_to_add) break;
            
            $update_attendance = "
                UPDATE attendance 
                SET attendance_status = 'present', 
                    check_method = 'Manual Adjustment', 
                    check_time = '08:00:00', 
                    remarks = 'ปรับสถานะจาก absent เป็น present',
                    updated_at = NOW()
                WHERE student_id = ? AND academic_year_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $stmt = $conn->prepare($update_attendance);
            $result = $stmt->execute([$student_id, $academic_year_id, $date]);
            $rows_affected = $stmt->rowCount();
            
            error_log("🔍 DEBUG: UPDATE $date - success: " . ($result ? 'true' : 'false') . ", rows: $rows_affected");
            
            if ($result && $rows_affected > 0) {
                $actual_days_added++;
            }
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "ปรับข้อมูลเสร็จสิ้น! แก้ไขได้ $actual_days_added วัน จากทั้งหมด $total_absent_days วันที่ขาด",
            'days_adjusted' => $actual_days_added,
            'total_absent' => $total_absent_days,
            'academic_year_id_used' => $academic_year_id
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'days_adjusted' => 0
        ];
    }
}

// ทดสอบฟังก์ชัน
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    echo "<h2>🧪 ทดสอบ Safe Attendance Fix</h2>";
    
    $student_id = $_GET['student_id'] ?? 1;
    $days = $_GET['days'] ?? 2;
    
    echo "<p>กำลังทดสอบ student_id: $student_id, days: $days</p>";
    
    $result = safeAdjustStudentAttendance($student_id, $days);
    
    echo "<div style='background: " . ($result['success'] ? '#e8f5e8' : '#ffebee') . "; padding: 15px; border-radius: 8px;'>";
    echo "<h3>" . ($result['success'] ? '✅ สำเร็จ' : '❌ ล้มเหลว') . "</h3>";
    echo "<p><strong>ข้อความ:</strong> {$result['message']}</p>";
    if (isset($result['days_adjusted'])) {
        echo "<p><strong>จำนวนวันที่แก้ไข:</strong> {$result['days_adjusted']}</p>";
    }
    if (isset($result['total_absent'])) {
        echo "<p><strong>วันขาดทั้งหมด:</strong> {$result['total_absent']}</p>";
    }
    if (isset($result['academic_year_id_used'])) {
        echo "<p><strong>Academic Year ID ที่ใช้:</strong> {$result['academic_year_id_used']}</p>";
    }
    echo "</div>";
}
?>

<style>
    body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3 { color: #2196f3; }
</style>

<?php if (!isset($_GET['test'])): ?>
<p>เพื่อทดสอบ ให้เปิด: <code>safe_attendance_fix.php?test=1&student_id=1&days=2</code></p>
<?php endif; ?>
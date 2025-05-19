<?php
/**
 * update_missing_attendance.php - ไฟล์สำหรับเพิ่มข้อมูลการขาดเรียนให้นักเรียนที่ไม่มีข้อมูลการเช็คชื่อในวันนั้นๆ
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// ตรวจสอบว่ามีข้อมูลครบหรือไม่
if ((!$class_id && !isset($_POST['all_classes'])) || !$start_date || !$end_date) {
    echo json_encode([
        'success' => false, 
        'error' => 'กรุณาระบุข้อมูลให้ครบถ้วน'
    ]);
    exit;
}

// เตรียมเพื่อบันทึกกิจกรรมของผู้ดูแลระบบ
$user_id = $_SESSION['user_id'];
$action_details = [
    'class_id' => $class_id,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'all_classes' => isset($_POST['all_classes']) ? true : false
];

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception('ไม่พบข้อมูลปีการศึกษาปัจจุบัน');
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // เริ่ม transaction
    $conn->beginTransaction();
    
    // ดึงรายการวันที่มีการเช็คชื่อในช่วงที่กำหนด
    $sql = "
        SELECT DISTINCT date FROM attendance 
        WHERE date BETWEEN ? AND ?
        ORDER BY date
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $attendance_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($attendance_dates)) {
        throw new Exception('ไม่พบข้อมูลการเช็คชื่อในช่วงวันที่ระบุ');
    }
    
    // เตรียมเงื่อนไขสำหรับคลาสเรียน
    $class_condition = "";
    $params = [];
    
    if ($class_id) {
        $class_condition = "AND s.current_class_id = ?";
        $params[] = $class_id;
    }
    
    // หากเป็นครูที่ปรึกษา ให้กำหนดเงื่อนไขเพิ่มเติม
    if ($_SESSION['user_role'] == 'teacher') {
        // ดึงรหัสครู
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            if (empty($class_condition)) {
                $class_condition = "AND s.current_class_id IN (
                    SELECT class_id FROM class_advisors 
                    WHERE teacher_id = ?
                )";
            } else {
                $class_condition .= " AND s.current_class_id IN (
                    SELECT class_id FROM class_advisors 
                    WHERE teacher_id = ?
                )";
            }
            $params[] = $teacher['teacher_id'];
        }
    }
    
    // นับจำนวนรายการที่จะปรับปรุง
    $total_updated = 0;
    $date_summaries = [];
    
    foreach ($attendance_dates as $date) {
        // ดึงนักเรียนที่ยังไม่มีข้อมูลการเช็คชื่อในวันนี้
        $sql = "
            SELECT s.student_id, s.student_code, u.first_name, u.last_name 
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.status = 'กำลังศึกษา'
            {$class_condition}
            AND NOT EXISTS (
                SELECT 1 FROM attendance a 
                WHERE a.student_id = s.student_id 
                AND a.date = ?
            )
        ";
        
        $query_params = array_merge($params, [$date]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($query_params);
        $missing_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $date_updated = 0;
        
        // เพิ่มข้อมูลการขาดเรียนให้นักเรียนเหล่านี้
        if (!empty($missing_students)) {
            $insert_sql = "
                INSERT INTO attendance 
                (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks)
                VALUES (?, ?, ?, 'absent', 'Manual', ?, NOW(), 'บันทึกอัตโนมัติสำหรับนักเรียนที่ไม่มีข้อมูลการเช็คชื่อ')
            ";
            
            $insert_stmt = $conn->prepare($insert_sql);
            
            foreach ($missing_students as $student) {
                $insert_stmt->execute([
                    $student['student_id'],
                    $academic_year_id,
                    $date,
                    $user_id
                ]);
                
                // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
                updateStudentAttendanceSummary($conn, $student['student_id'], $academic_year_id);
                
                $date_updated++;
                $total_updated++;
            }
        }
        
        // เก็บสรุปการปรับปรุงในแต่ละวัน
        $date_summaries[] = [
            'date' => $date,
            'updated_count' => $date_updated,
            'format_date' => date('d/m/Y', strtotime($date))
        ];
    }
    
    // บันทึกกิจกรรมการปรับปรุงข้อมูล
    $action_details['total_updated'] = $total_updated;
    $action_details['date_summaries'] = $date_summaries;
    
 
    
    // ยืนยัน transaction
    $conn->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true, 
        'message' => "ปรับปรุงข้อมูลการเช็คชื่อสำเร็จ จำนวน {$total_updated} รายการ",
        'total_updated' => $total_updated,
        'date_summaries' => $date_summaries
    ]);
    
} catch (Exception $e) {
    // ยกเลิก transaction ในกรณีที่เกิดข้อผิดพลาด
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // ส่งข้อความผิดพลาดกลับ
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

/**
 * อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
 */
function updateStudentAttendanceSummary($conn, $student_id, $academic_year_id) {
    // ตรวจสอบว่ามีข้อมูลบันทึกหรือยัง
    $stmt = $conn->prepare("
        SELECT record_id FROM student_academic_records 
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $academic_year_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        // อัปเดตจำนวนวันที่เข้าแถวและขาดแถว
        $stmt = $conn->prepare("
            UPDATE student_academic_records 
            SET 
                total_attendance_days = (
                    SELECT COUNT(*) FROM attendance 
                    WHERE student_id = ? AND academic_year_id = ? AND attendance_status IN ('present', 'late')
                ),
                total_absence_days = (
                    SELECT COUNT(*) FROM attendance 
                    WHERE student_id = ? AND academic_year_id = ? AND attendance_status = 'absent'
                ),
                updated_at = NOW()
            WHERE record_id = ?
        ");
        $stmt->execute([$student_id, $academic_year_id, $student_id, $academic_year_id, $record['record_id']]);
    } else {
        // สร้างข้อมูลสรุปใหม่
        // หาชั้นเรียนปัจจุบันของนักเรียน
        $stmt = $conn->prepare("SELECT current_class_id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student && $student['current_class_id']) {
            $class_id = $student['current_class_id'];
            
            // นับจำนวนวันที่เข้าแถวและขาดแถว
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(CASE WHEN attendance_status IN ('present', 'late') THEN 1 END) AS attendance_days,
                    COUNT(CASE WHEN attendance_status = 'absent' THEN 1 END) AS absence_days
                FROM attendance 
                WHERE student_id = ? AND academic_year_id = ?
            ");
            $stmt->execute([$student_id, $academic_year_id]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $attendance_days = $counts['attendance_days'] ?? 0;
            $absence_days = $counts['absence_days'] ?? 0;
            
            // เพิ่มข้อมูลสรุป
            $stmt = $conn->prepare("
                INSERT INTO student_academic_records 
                (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$student_id, $academic_year_id, $class_id, $attendance_days, $absence_days]);
        }
    }
    
    // ตรวจสอบความเสี่ยงตกกิจกรรม
    checkRiskStatus($conn, $student_id, $academic_year_id);
}

/**
 * ตรวจสอบความเสี่ยงตกกิจกรรม
 */
function checkRiskStatus($conn, $student_id, $academic_year_id) {
    // ดึงจำนวนวันที่เข้าแถวและขาดแถว
    $stmt = $conn->prepare("
        SELECT total_attendance_days, total_absence_days
        FROM student_academic_records 
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $academic_year_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) return;
    
    $attendance_days = $record['total_attendance_days'] ?? 0;
    $absence_days = $record['total_absence_days'] ?? 0;
    
    // ดึงเกณฑ์ความเสี่ยง
    $stmt = $conn->prepare("
        SELECT 
            (SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_low') AS low,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_medium') AS medium,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high') AS high,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_critical') AS critical,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'required_attendance_days') AS required_days
        FROM dual
    ");
    $stmt->execute();
    $thresholds = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $required_days = (int)($thresholds['required_days'] ?? 90);
    $low_threshold = (int)($thresholds['low'] ?? 80);
    $medium_threshold = (int)($thresholds['medium'] ?? 70);
    $high_threshold = (int)($thresholds['high'] ?? 60);
    $critical_threshold = (int)($thresholds['critical'] ?? 50);
    
    // คำนวณเปอร์เซ็นต์การเข้าแถว
    $attendance_percent = 0;
    if ($required_days > 0) {
        $attendance_percent = ($attendance_days / $required_days) * 100;
    }
    
    // กำหนดระดับความเสี่ยง
    $risk_level = 'low';
    if ($attendance_percent <= $critical_threshold) {
        $risk_level = 'critical';
    } else if ($attendance_percent <= $high_threshold) {
        $risk_level = 'high';
    } else if ($attendance_percent <= $medium_threshold) {
        $risk_level = 'medium';
    }
    
    // ตรวจสอบว่ามีข้อมูลความเสี่ยงหรือยัง
    $stmt = $conn->prepare("
        SELECT risk_id, risk_level, notification_sent
        FROM risk_students 
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $academic_year_id]);
    $risk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($risk) {
        // อัปเดตข้อมูลความเสี่ยง
        $stmt = $conn->prepare("
            UPDATE risk_students 
            SET risk_level = ?, absence_count = ?, updated_at = NOW()
            WHERE risk_id = ?
        ");
        $stmt->execute([$risk_level, $absence_days, $risk['risk_id']]);
        
        // ตรวจสอบการแจ้งเตือน
        if ($risk_level == 'high' || $risk_level == 'critical') {
            if (!$risk['notification_sent']) {
                // ส่งการแจ้งเตือน (ให้ระบบอื่นดำเนินการต่อ)
                $stmt = $conn->prepare("
                    UPDATE risk_students 
                    SET notification_sent = 1, notification_date = NOW()
                    WHERE risk_id = ?
                ");
                $stmt->execute([$risk['risk_id']]);
            }
        }
    } else {
        // เพิ่มข้อมูลความเสี่ยงใหม่
        $stmt = $conn->prepare("
            INSERT INTO risk_students 
            (student_id, academic_year_id, absence_count, risk_level, notification_sent, created_at, updated_at)
            VALUES (?, ?, ?, ?, 0, NOW(), NOW())
        ");
        $stmt->execute([$student_id, $academic_year_id, $absence_days, $risk_level]);
    }
}
?>
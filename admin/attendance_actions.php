<?php
/**
 * attendance_actions.php - ไฟล์สำหรับจัดการการเช็คชื่อผ่าน AJAX
 * ปรับปรุงให้รองรับการอัปเดตการเช็คชื่อ
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับข้อมูลจาก POST
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// เริ่มการตรวจสอบและดำเนินการตาม action
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
    
    // ดำเนินการตาม action
    switch ($action) {
        // บันทึกการเช็คชื่อจากการสแกน QR หรือวิธีอื่น
        case 'record_attendance':
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $method = $_POST['method'] ?? 'QR_Code';
            $status = $_POST['status'] ?? 'present';
            $remarks = $_POST['remarks'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');
            
            if (!$student_id) {
                throw new Exception('ไม่ระบุรหัสนักเรียน');
            }
            
            // ตรวจสอบว่านักเรียนมีอยู่ในระบบหรือไม่
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                throw new Exception('ไม่พบข้อมูลนักเรียน');
            }
            
            // ตรวจสอบว่ามีการเช็คชื่อแล้วหรือไม่
            $stmt = $conn->prepare("
                SELECT attendance_id FROM attendance 
                WHERE student_id = ? AND date = ?
            ");
            $stmt->execute([$student_id, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $conn->beginTransaction();
            
            if ($existing) {
                // อัปเดตข้อมูลเดิม
                $stmt = $conn->prepare("
                    UPDATE attendance 
                    SET attendance_status = ?, check_method = ?, checker_user_id = ?, remarks = ?, check_time = NOW()
                    WHERE attendance_id = ?
                ");
                $stmt->execute([$status, $method, $user_id, $remarks, $existing['attendance_id']]);
                $attendance_id = $existing['attendance_id'];
            } else {
                // เพิ่มข้อมูลใหม่
                $stmt = $conn->prepare("
                    INSERT INTO attendance 
                    (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$student_id, $academic_year_id, $date, $status, $method, $user_id, $remarks]);
                $attendance_id = $conn->lastInsertId();
            }
            
            // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
            updateStudentAttendanceSummary($conn, $student_id, $academic_year_id);
            
            $conn->commit();
            
            // ดึงข้อมูลนักเรียนเพื่อส่งกลับ
            $stmt = $conn->prepare("
                SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                       c.level, c.group_number, d.department_name,
                       a.check_time, a.attendance_status
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                LEFT JOIN attendance a ON a.student_id = s.student_id AND a.attendance_id = ?
                WHERE s.student_id = ?
            ");
            $stmt->execute([$attendance_id, $student_id]);
            $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // จัดรูปแบบเวลา
            if ($student_data['check_time']) {
                $student_data['check_time'] = date('H:i', strtotime($student_data['check_time']));
            }
            
            // ส่งข้อมูลกลับ
            echo json_encode([
                'success' => true, 
                'message' => 'บันทึกการเช็คชื่อเรียบร้อย',
                'student' => $student_data
            ]);
            break;
            
        // อัปเดตการเช็คชื่อที่มีอยู่แล้ว
        case 'update_attendance':
            $attendance_id = isset($_POST['attendance_id']) ? intval($_POST['attendance_id']) : 0;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $status = $_POST['status'] ?? 'present';
            $remarks = $_POST['remarks'] ?? '';
            
            if (!$attendance_id || !$student_id) {
                throw new Exception('ข้อมูลไม่ครบถ้วน');
            }
            
            // ตรวจสอบสิทธิ์ในการแก้ไข (ถ้าเป็นครูให้ตรวจสอบว่าเป็นครูที่ปรึกษาหรือไม่)
            if ($_SESSION['user_role'] == 'teacher') {
                $stmt = $conn->prepare("
                    SELECT t.teacher_id 
                    FROM teachers t 
                    WHERE t.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($teacher) {
                    // ตรวจสอบว่านักเรียนอยู่ในห้องที่ครูเป็นที่ปรึกษาหรือไม่
                    $stmt = $conn->prepare("
                        SELECT a.attendance_id 
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id
                        JOIN class_advisors ca ON s.current_class_id = ca.class_id
                        WHERE a.attendance_id = ? AND ca.teacher_id = ?
                    ");
                    $stmt->execute([$attendance_id, $teacher['teacher_id']]);
                    
                    if (!$stmt->fetch()) {
                        throw new Exception('ไม่มีสิทธิ์แก้ไขข้อมูลการเช็คชื่อนี้');
                    }
                }
            }
            
            $conn->beginTransaction();
            
            // อัปเดตข้อมูลการเช็คชื่อ
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET attendance_status = ?, remarks = ?, checker_user_id = ?
                WHERE attendance_id = ?
            ");
            $stmt->execute([$status, $remarks, $user_id, $attendance_id]);
            
            // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
            updateStudentAttendanceSummary($conn, $student_id, $academic_year_id);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'แก้ไขการเช็คชื่อเรียบร้อย'
            ]);
            break;
            
        // บันทึกการเช็คชื่อแบบกลุ่ม
        case 'bulk_attendance':
            $attendance_date = $_POST['date'] ?? date('Y-m-d');
            $students = isset($_POST['students']) && is_array($_POST['students']) ? $_POST['students'] : [];
            
            if (empty($students)) {
                throw new Exception('ไม่มีข้อมูลนักเรียนที่ต้องการบันทึก');
            }
            
            $conn->beginTransaction();
            
            foreach ($students as $student) {
                $student_id = $student['student_id'] ?? 0;
                $status = $student['status'] ?? 'absent';
                $remarks = $student['remarks'] ?? '';
                
                if (!$student_id) continue;
                
                // ตรวจสอบว่ามีการเช็คชื่อแล้วหรือไม่
                $stmt = $conn->prepare("
                    SELECT attendance_id FROM attendance 
                    WHERE student_id = ? AND date = ?
                ");
                $stmt->execute([$student_id, $attendance_date]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // อัปเดตข้อมูลเดิม
                    $stmt = $conn->prepare("
                        UPDATE attendance 
                        SET attendance_status = ?, checker_user_id = ?, remarks = ?, check_method = 'Manual'
                        WHERE attendance_id = ?
                    ");
                    $stmt->execute([$status, $user_id, $remarks, $existing['attendance_id']]);
                } else {
                    // เพิ่มข้อมูลใหม่
                    $stmt = $conn->prepare("
                        INSERT INTO attendance 
                        (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks)
                        VALUES (?, ?, ?, ?, 'Manual', ?, NOW(), ?)
                    ");
                    $stmt->execute([$student_id, $academic_year_id, $attendance_date, $status, $user_id, $remarks]);
                }
                
                // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
                updateStudentAttendanceSummary($conn, $student_id, $academic_year_id);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'บันทึกการเช็คชื่อนักเรียนจำนวน ' . count($students) . ' คนเรียบร้อย'
            ]);
            break;
            
        default:
            throw new Exception('ไม่รู้จักคำสั่ง');
    }
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
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
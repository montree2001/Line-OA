<?php
/**
 * api/ajax_attendance.php - API สำหรับการเช็คชื่อแบบ AJAX
 * 
 * รับข้อมูล POST:
 * - action: 'mark_attendance'
 * - student_id: รหัสนักเรียน
 * - status: สถานะการเช็คชื่อ (present, absent, late, leave)
 * - class_id: รหัสห้องเรียน
 * - date: วันที่เช็คชื่อ
 * - teacher_id: รหัสครู
 * - is_retroactive: เป็นการเช็คชื่อย้อนหลังหรือไม่
 * - remarks: หมายเหตุ (ถ้ามี)
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการเช็คชื่อ'
    ]);
    exit;
}

// ตรวจสอบว่ารับข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'ต้องใช้วิธี POST เท่านั้น'
    ]);
    exit;
}

// ตรวจสอบ action
if (!isset($_POST['action']) || $_POST['action'] !== 'mark_attendance') {
    echo json_encode([
        'success' => false,
        'message' => 'Action ไม่ถูกต้อง'
    ]);
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($_POST['student_id'], $_POST['status'], $_POST['class_id'], $_POST['date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage()
    ]);
    exit;
}

// เก็บค่าที่ส่งมา
$student_id = intval($_POST['student_id']);
$status = $_POST['status'];
$class_id = intval($_POST['class_id']);
$check_date = $_POST['date'];
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
$is_retroactive = isset($_POST['is_retroactive']) ? (bool)$_POST['is_retroactive'] : false;
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;

// ตรวจสอบสถานะที่ถูกต้อง
if (!in_array($status, ['present', 'absent', 'late', 'leave'])) {
    echo json_encode([
        'success' => false,
        'message' => 'สถานะไม่ถูกต้อง'
    ]);
    exit;
}

// สร้างหมายเหตุสำหรับการเช็คย้อนหลัง
if ($is_retroactive && !empty($_POST['retroactive_note'])) {
    $remarks = !empty($remarks) ? $remarks . " (" . $_POST['retroactive_note'] . ")" : $_POST['retroactive_note'];
} elseif ($is_retroactive) {
    $remarks = !empty($remarks) ? $remarks . " (เช็คชื่อย้อนหลังโดยครู)" : "เช็คชื่อย้อนหลังโดยครู";
}

try {
    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year_data) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
    }
    
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    // ดึง user_id ของครูที่เช็คชื่อ
    if ($teacher_id > 0) {
        $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $db->prepare($user_id_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_id_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_id_data) {
            throw new Exception("ไม่พบข้อมูลครูที่ระบุ");
        }
        
        $checker_user_id = $user_id_data['user_id'];
    } else {
        $checker_user_id = $_SESSION['user_id'];
    }
    
    // เริ่ม Transaction
    $db->beginTransaction();
    
    // ตรวจสอบว่ามีข้อมูลการเช็คชื่อในวันนี้แล้วหรือไม่
    $check_existing_query = "SELECT attendance_id FROM attendance 
                           WHERE student_id = :student_id AND date = :check_date";
    $stmt = $db->prepare($check_existing_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->execute();
    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_attendance) {
        // อัพเดทข้อมูลเดิม
        $update_query = "UPDATE attendance 
                       SET attendance_status = :status, 
                           check_method = 'Manual',
                           checker_user_id = :checker_user_id, 
                           check_time = NOW(),
                           updated_at = NOW(),
                           remarks = :remarks
                       WHERE attendance_id = :attendance_id";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $stmt->bindParam(':attendance_id', $existing_attendance['attendance_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $attendance_id = $existing_attendance['attendance_id'];
    } else {
        // เพิ่มข้อมูลใหม่
        $insert_query = "INSERT INTO attendance 
                       (student_id, academic_year_id, date, attendance_status, check_method, 
                       checker_user_id, check_time, created_at, updated_at, remarks) 
                       VALUES (:student_id, :academic_year_id, :check_date, :status, 'Manual', 
                       :checker_user_id, NOW(), NOW(), NOW(), :remarks)";
        
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $stmt->execute();
        
        $attendance_id = $db->lastInsertId();
    }
    
    // บันทึกประวัติการเช็คชื่อย้อนหลัง
    if ($is_retroactive) {
        $log_query = "INSERT INTO attendance_logs 
                     (user_id, academic_year_id, class_id, action_type, action_date, action_details, created_at)
                     VALUES 
                     (:user_id, :academic_year_id, :class_id, 'retroactive_check', :check_date, :action_details, NOW())";
        
        $action_details = json_encode([
            'teacher_id' => $teacher_id,
            'student_id' => $student_id,
            'status' => $status,
            'remarks' => $remarks
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare($log_query);
        $stmt->bindParam(':user_id', $checker_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->bindParam(':action_details', $action_details, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    // อัพเดทสถิติการเข้าแถวในตาราง student_academic_records
    $update_stats_query = "
        UPDATE student_academic_records sar
        JOIN (
            SELECT 
                student_id,
                SUM(CASE WHEN attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as total_absent
            FROM attendance
            WHERE student_id = :student_id AND academic_year_id = :academic_year_id
            GROUP BY student_id
        ) att ON sar.student_id = att.student_id
        SET 
            sar.total_attendance_days = att.total_present,
            sar.total_absence_days = att.total_absent,
            sar.updated_at = NOW()
        WHERE sar.student_id = :student_id AND sar.academic_year_id = :academic_year_id
    ";
    
    $stmt = $db->prepare($update_stats_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // ตรวจสอบและอัพเดทสถานะความเสี่ยง
    $risk_query = "
        INSERT INTO risk_students 
            (student_id, academic_year_id, absence_count, risk_level, notification_sent, created_at, updated_at)
        SELECT 
            s.student_id, 
            :academic_year_id, 
            sar.total_absence_days,
            CASE 
                WHEN sar.total_absence_days >= 20 THEN 'critical'
                WHEN sar.total_absence_days >= 15 THEN 'high'
                WHEN sar.total_absence_days >= 10 THEN 'medium'
                ELSE 'low'
            END as risk_level,
            0, 
            NOW(), 
            NOW()
        FROM 
            students s
            JOIN student_academic_records sar ON s.student_id = sar.student_id
        WHERE 
            s.student_id = :student_id
            AND sar.academic_year_id = :academic_year_id 
            AND sar.total_absence_days >= 10
        ON DUPLICATE KEY UPDATE 
            absence_count = sar.total_absence_days,
            risk_level = CASE 
                WHEN sar.total_absence_days >= 20 THEN 'critical'
                WHEN sar.total_absence_days >= 15 THEN 'high'
                WHEN sar.total_absence_days >= 10 THEN 'medium'
                ELSE 'low'
            END,
            updated_at = NOW()
    ";
    
    $stmt = $db->prepare($risk_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // ดึงข้อมูลนักเรียนสำหรับส่งกลับ
    $student_query = "SELECT s.student_code, s.title, u.first_name, u.last_name, u.profile_picture
                     FROM students s
                     JOIN users u ON s.user_id = u.user_id
                     WHERE s.student_id = :student_id";
    
    $stmt = $db->prepare($student_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit Transaction
    $db->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการเช็คชื่อเรียบร้อย',
        'attendance_id' => $attendance_id,
        'student' => [
            'id' => $student_id,
            'code' => $student_data['student_code'],
            'name' => $student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name'],
            'profile_picture' => $student_data['profile_picture'],
            'status' => $status,
            'remarks' => $remarks,
            'check_time' => date('H:i')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
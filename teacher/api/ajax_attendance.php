<?php
/**
 * ajax_attendance.php - API สำหรับการเช็คชื่อแบบ AJAX
 * 
 * รับข้อมูล POST:
 * - student_id: รหัสนักเรียน
 * - status: สถานะการเช็คชื่อ (present, absent, late, leave)
 * - class_id: รหัสห้องเรียน
 * - date: วันที่เช็คชื่อ
 * - is_retroactive: เป็นการเช็คชื่อย้อนหลังหรือไม่
 * - remarks: หมายเหตุ (ถ้ามี)
 */

session_start();
header('Content-Type: application/json');

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

// รับและแปลงข้อมูล JSON
$input_json = file_get_contents('php://input');
$input_data = json_decode($input_json, true);

// ตรวจสอบการแปลง JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูล JSON ไม่ถูกต้อง: ' . json_last_error_msg()
    ]);
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($input_data['student_id']) || !isset($input_data['status']) || 
    !isset($input_data['class_id']) || !isset($input_data['date'])) {
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

try {
    // เก็บค่าจาก input
    $student_id = intval($input_data['student_id']);
    $status = $input_data['status'];
    $class_id = intval($input_data['class_id']);
    $check_date = $input_data['date'];
    $is_retroactive = isset($input_data['is_retroactive']) ? (bool)$input_data['is_retroactive'] : false;
    $remarks = isset($input_data['remarks']) ? $input_data['remarks'] : '';
    
    // เพิ่มหมายเหตุกรณีเช็คย้อนหลัง
    if ($is_retroactive && empty($remarks)) {
        $remarks = 'เช็คชื่อย้อนหลังโดยครู';
    }
    
    // ตรวจสอบความถูกต้องของสถานะ
    if (!in_array($status, ['present', 'absent', 'late', 'leave'])) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }
    
    // เริ่ม Transaction
    $db->beginTransaction();
    
    // ดึงปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year_data) {
        throw new Exception('ไม่พบข้อมูลปีการศึกษาปัจจุบัน');
    }
    
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    // ตรวจสอบว่ามีการเช็คชื่อนักเรียนคนนี้ในวันนี้แล้วหรือไม่
    $check_query = "SELECT attendance_id FROM attendance 
                   WHERE student_id = :student_id AND date = :check_date";
    
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->execute();
    
    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $_SESSION['user_id'];
    
    if ($existing_attendance) {
        // อัพเดตการเช็คชื่อที่มีอยู่แล้ว
        $update_query = "UPDATE attendance 
                        SET attendance_status = :status, 
                            check_method = 'Manual',
                            checker_user_id = :user_id, 
                            check_time = NOW(),
                            remarks = :remarks
                        WHERE attendance_id = :attendance_id";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $stmt->bindParam(':attendance_id', $existing_attendance['attendance_id'], PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('อัพเดตข้อมูลล้มเหลว: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $attendance_id = $existing_attendance['attendance_id'];
    } else {
        // เพิ่มการเช็คชื่อใหม่
        $insert_query = "INSERT INTO attendance 
                        (student_id, academic_year_id, date, attendance_status, check_method, 
                        checker_user_id, check_time, created_at, remarks) 
                        VALUES (:student_id, :academic_year_id, :check_date, :status, 'Manual', 
                        :user_id, NOW(), NOW(), :remarks)";
        
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('เพิ่มข้อมูลล้มเหลว: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $attendance_id = $db->lastInsertId();
    }
    
    // อัพเดทสถิติการเข้าแถวของนักเรียน
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
    
    // ดึงข้อมูลนักเรียนเพื่อส่งกลับ
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
    $response = [
        'success' => true,
        'message' => 'บันทึกการเช็คชื่อเรียบร้อย',
        'attendance_id' => $attendance_id,
        'student' => [
            'id' => $student_id,
            'code' => $student_data['student_code'],
            'name' => $student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name'],
            'profile_picture' => $student_data['profile_picture'],
            'status' => $status,
            'time_checked' => date('H:i')
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback Transaction เมื่อเกิดข้อผิดพลาด
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
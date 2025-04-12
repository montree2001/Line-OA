<?php
/**
 * api/save_attendance.php - API บันทึกการเช็คชื่อนักเรียน (ปรับปรุงใหม่)
 * 
 * รับข้อมูล:
 * - class_id: รหัสห้องเรียน
 * - date: วันที่เช็คชื่อ
 * - teacher_id: รหัสครูผู้เช็คชื่อ
 * - students: รายการนักเรียนที่เช็คชื่อ (array)
 *   - student_id: รหัสนักเรียน
 *   - status: สถานะการเช็คชื่อ (present/late/leave/absent)
 *   - remarks: หมายเหตุ (ถ้ามี)
 * - is_retroactive: เป็นการเช็คชื่อย้อนหลังหรือไม่ (boolean)
 * - check_method: วิธีการเช็คชื่อ (Manual/PIN/QR_Code/GPS)
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการบันทึกการเช็คชื่อ'
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
$input_data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($input_data['class_id']) || empty($input_data['class_id']) ||
    !isset($input_data['date']) || empty($input_data['date']) ||
    !isset($input_data['teacher_id']) || empty($input_data['teacher_id']) ||
    !isset($input_data['students']) || empty($input_data['students'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// เริ่ม Transaction
$conn->begin_transaction();

try {
    // เก็บค่าที่ส่งมา
    $class_id = intval($input_data['class_id']);
    $check_date = $input_data['date'];
    $teacher_id = intval($input_data['teacher_id']);
    $students = $input_data['students'];
    $is_retroactive = isset($input_data['is_retroactive']) ? (bool)$input_data['is_retroactive'] : false;
    $check_method = isset($input_data['check_method']) ? $input_data['check_method'] : 'Manual';
    
    // ตรวจสอบความถูกต้องของรูปแบบวันที่
    $date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_pattern, $check_date)) {
        throw new Exception("รูปแบบวันที่ไม่ถูกต้อง");
    }
    
    // ตรวจสอบสิทธิ์ในการเช็คชื่อห้องเรียนนี้ (กรณีเป็นครู)
    if ($_SESSION['role'] === 'teacher') {
        $check_permission_query = "SELECT ca.class_id 
                                  FROM class_advisors ca 
                                  JOIN teachers t ON ca.teacher_id = t.teacher_id 
                                  WHERE t.teacher_id = ? AND ca.class_id = ?";
        
        $stmt = $conn->prepare($check_permission_query);
        
        if (!$stmt) {
            throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $teacher_id, $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("คุณไม่มีสิทธิ์ในการเช็คชื่อนักเรียนห้องนี้");
        }
        $stmt->close();
    }
    
    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $academic_year_result = $conn->query($academic_year_query);
    
    if (!$academic_year_result || $academic_year_result->num_rows === 0) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
    }
    
    $academic_year_data = $academic_year_result->fetch_assoc();
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    // ตรวจสอบว่าวันที่เช็คอยู่ในช่วงปีการศึกษาปัจจุบันหรือไม่
    $check_date_query = "SELECT * FROM academic_years 
                        WHERE academic_year_id = ? 
                        AND ? BETWEEN start_date AND end_date";
    $check_date_stmt = $conn->prepare($check_date_query);
    
    if (!$check_date_stmt) {
        throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    $check_date_stmt->bind_param("is", $academic_year_id, $check_date);
    $check_date_stmt->execute();
    $check_date_result = $check_date_stmt->get_result();
    
    if ($check_date_result->num_rows === 0) {
        throw new Exception("วันที่เช็คชื่อไม่อยู่ในช่วงปีการศึกษาปัจจุบัน");
    }
    $check_date_stmt->close();
    
    // เตรียม Prepared Statements
    $delete_stmt = $conn->prepare("DELETE FROM attendance WHERE student_id = ? AND date = ?");
    $insert_stmt = $conn->prepare("INSERT INTO attendance 
                                  (student_id, academic_year_id, date, attendance_status, check_method, 
                                  checker_user_id, check_time, created_at, remarks) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
    
    if (!$delete_stmt || !$insert_stmt) {
        throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    // ดึง user_id ของครูที่เช็คชื่อ
    $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = ?";
    $user_id_stmt = $conn->prepare($user_id_query);
    
    if (!$user_id_stmt) {
        throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    $user_id_stmt->bind_param("i", $teacher_id);
    $user_id_stmt->execute();
    $user_id_result = $user_id_stmt->get_result();
    
    if ($user_id_result->num_rows === 0) {
        throw new Exception("ไม่พบข้อมูลครูที่เช็คชื่อ");
    }
    
    $user_id_data = $user_id_result->fetch_assoc();
    $checker_user_id = $user_id_data['user_id'];
    $user_id_stmt->close();
    
    // นับจำนวนการบันทึกสำเร็จ
    $success_count = 0;
    
    // วนลูปบันทึกการเช็คชื่อทีละคน
    foreach ($students as $student) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (!isset($student['student_id']) || !isset($student['status'])) {
            continue; // ข้ามรายการที่ข้อมูลไม่ครบ
        }
        
        $student_id = intval($student['student_id']);
        $status = $student['status']; // present, late, leave, absent
        $remarks = isset($student['remarks']) ? $student['remarks'] : ($is_retroactive ? 'เช็คชื่อย้อนหลังโดยครู' : '');
        
        // ตรวจสอบว่าสถานะถูกต้อง
        if (!in_array($status, ['present', 'late', 'leave', 'absent'])) {
            // ถ้าสถานะไม่ถูกต้อง ให้ใช้ค่าเริ่มต้นเป็น absent
            $status = 'absent';
        }
        
        // ลบข้อมูลเดิม (ถ้ามี)
        $delete_stmt->bind_param("is", $student_id, $check_date);
        $delete_stmt->execute();
        
        // เพิ่มข้อมูลใหม่
        $insert_stmt->bind_param("iisssss", 
            $student_id, 
            $academic_year_id, 
            $check_date, 
            $status, // เปลี่ยนจาก $is_present เป็น $status
            $check_method, 
            $checker_user_id, 
            $remarks
        );
        
        if ($insert_stmt->execute()) {
            $success_count++;
        }
    }
    
    // ปิด Statements
    $delete_stmt->close();
    $insert_stmt->close();
    
    // อัพเดตสถิติการเข้าแถวในตาราง student_academic_records
    $update_records_query = "
        UPDATE student_academic_records sar
        JOIN (
            SELECT 
                student_id,
                SUM(CASE WHEN attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as total_absent
            FROM attendance
            WHERE academic_year_id = ?
            GROUP BY student_id
        ) att ON sar.student_id = att.student_id
        SET 
            sar.total_attendance_days = att.total_present,
            sar.total_absence_days = att.total_absent,
            sar.updated_at = NOW()
        WHERE sar.academic_year_id = ?
    ";
    
    $update_records_stmt = $conn->prepare($update_records_query);
    
    if (!$update_records_stmt) {
        throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    $update_records_stmt->bind_param("ii", $academic_year_id, $academic_year_id);
    $update_records_stmt->execute();
    $update_records_stmt->close();
    
    // Commit Transaction
    $conn->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการเช็คชื่อเรียบร้อย',
        'count' => $success_count
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction เมื่อเกิดข้อผิดพลาด
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // ปิดการเชื่อมต่อฐานข้อมูล
    $conn->close();
}
?>
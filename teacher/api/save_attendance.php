<?php
/**
 * api/save_attendance.php - API สำหรับบันทึกการเช็คชื่อนักเรียน
 * 
 * รับข้อมูล JSON:
 * {
 *   "class_id": int,             // รหัสห้องเรียน
 *   "date": "YYYY-MM-DD",        // วันที่เช็คชื่อ
 *   "teacher_id": int,           // รหัสครูผู้เช็คชื่อ
 *   "students": [                // รายการนักเรียน
 *     {
 *       "student_id": int,       // รหัสนักเรียน
 *       "status": string,        // สถานะ (present/late/leave/absent)
 *       "remarks": string,       // หมายเหตุ (ถ้ามี)
 *       "attendance_id": int     // รหัสการเช็คชื่อ (กรณีแก้ไข)
 *     },
 *     ...
 *   ],
 *   "is_retroactive": bool,      // เป็นการเช็คชื่อย้อนหลังหรือไม่
 *   "check_method": string       // วิธีการเช็คชื่อ (Manual/PIN/QR_Code/GPS)
 * }
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
header('Content-Type: application/json; charset=UTF-8');

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
    !isset($input_data['students']) || !is_array($input_data['students'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง'
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

// เริ่ม Transaction
$db->beginTransaction();

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
                                  WHERE t.teacher_id = :teacher_id AND ca.class_id = :class_id";
        
        $stmt = $db->prepare($check_permission_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("คุณไม่มีสิทธิ์ในการเช็คชื่อนักเรียนห้องนี้");
        }
    }
    
    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
    }
    
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    // ตรวจสอบว่าวันที่เช็คอยู่ในช่วงปีการศึกษาปัจจุบันหรือไม่
    $check_date_query = "SELECT * FROM academic_years 
                        WHERE academic_year_id = :academic_year_id 
                        AND :check_date BETWEEN start_date AND end_date";
    
    $stmt = $db->prepare($check_date_query);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("วันที่เช็คชื่อไม่อยู่ในช่วงปีการศึกษาปัจจุบัน");
    }
    
    // เตรียม Prepared Statements
    $delete_stmt = $db->prepare("DELETE FROM attendance WHERE student_id = :student_id AND date = :check_date");
    $insert_stmt = $db->prepare("INSERT INTO attendance 
                               (student_id, academic_year_id, date, attendance_status, check_method, 
                               checker_user_id, check_time, created_at, remarks) 
                               VALUES (:student_id, :academic_year_id, :check_date, :status, :check_method, 
                               :checker_user_id, NOW(), NOW(), :remarks)");
    
    $update_stmt = $db->prepare("UPDATE attendance 
                               SET attendance_status = :status, 
                                   check_method = :check_method,
                                   checker_user_id = :checker_user_id, 
                                   remarks = :remarks
                               WHERE attendance_id = :attendance_id");
    
    // ดึง user_id ของครูที่เช็คชื่อ
    $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
    $stmt = $db->prepare($user_id_query);
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("ไม่พบข้อมูลครูที่เช็คชื่อ");
    }
    
    $user_id_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $checker_user_id = $user_id_data['user_id'];
    
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
        $attendance_id = isset($student['attendance_id']) ? intval($student['attendance_id']) : null;
        
        // ตรวจสอบว่าสถานะถูกต้อง
        if (!in_array($status, ['present', 'late', 'leave', 'absent'])) {
            // ถ้าสถานะไม่ถูกต้อง ให้ใช้ค่าเริ่มต้นเป็น absent
            $status = 'absent';
        }
        
        // ตรวจสอบว่าเป็นการแก้ไขหรือเพิ่มใหม่
        if ($attendance_id) {
            // แก้ไขข้อมูลที่มีอยู่
            $update_stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $update_stmt->bindParam(':check_method', $check_method, PDO::PARAM_STR);
            $update_stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            
            if ($update_stmt->execute()) {
                $success_count++;
            }
        } else {
            // ลบข้อมูลเดิม (ถ้ามี)
            $delete_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $delete_stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $delete_stmt->execute();
            
            // เพิ่มข้อมูลใหม่
            $insert_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $insert_stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $insert_stmt->bindParam(':check_method', $check_method, PDO::PARAM_STR);
            $insert_stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            
            if ($insert_stmt->execute()) {
                $success_count++;
            }
        }
    }
    
    // อัพเดตสถิติการเข้าแถวในตาราง student_academic_records
    // คำนวณใหม่โดยดูจากจำนวนการเข้าแถวทั้งหมดของนักเรียนในปีการศึกษานี้
    $update_records_query = "
        UPDATE student_academic_records sar
        JOIN (
            SELECT 
                student_id,
                SUM(CASE WHEN attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as total_absent
            FROM attendance
            WHERE academic_year_id = :academic_year_id
            GROUP BY student_id
        ) att ON sar.student_id = att.student_id
        SET 
            sar.total_attendance_days = att.total_present,
            sar.total_absence_days = att.total_absent,
            sar.updated_at = NOW()
        WHERE sar.academic_year_id = :academic_year_id
    ";
    
    $stmt = $db->prepare($update_records_query);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // ตรวจสอบนักเรียนที่เสี่ยงตกกิจกรรม และอัพเดทข้อมูลในตาราง risk_students
    $update_risk_query = "
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
            sar.academic_year_id = :academic_year_id 
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
    
    $stmt = $db->prepare($update_risk_query);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Commit Transaction
    $db->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการเช็คชื่อเรียบร้อย',
        'count' => $success_count
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction เมื่อเกิดข้อผิดพลาด
    $db->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
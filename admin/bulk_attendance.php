<?php
/**
 * bulk_attendance.php - หน้าเช็คชื่อนักเรียนแบบกลุ่มสำหรับผู้ดูแลระบบและครู
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * ปรับปรุงใหม่: แยกแท็บระหว่างเช็คชื่อแล้วและยังไม่เช็ค, แสดงไอคอนวิธีการเช็ค, 
 * ค้นหาโดยชื่อ, และกรองตามแผนก/ชั้น/กลุ่ม
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'bulk_attendance';
$page_title = 'เช็คชื่อนักเรียนแบบกลุ่ม';
$page_header = 'ระบบเช็คชื่อนักเรียนแบบกลุ่ม';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $admin_info = [
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => 'ผู้ดูแลระบบ',
                'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
            ];
        } else {
            $admin_info = [
                'name' => 'ผู้ดูแลระบบ',
                'role' => 'ผู้ดูแลระบบ',
                'initials' => 'A'
            ];
        }
    } else {
        // ผู้ใช้เป็นครู - ดึงข้อมูลครูเพิ่มเติม
        $stmt = $conn->prepare("
            SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, 
                   t.position, d.department_name
            FROM users u
            JOIN teachers t ON u.user_id = t.user_id
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $admin_info = [
                'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
                'role' => $teacher['position'] . ' ' . $teacher['department_name'],
                'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
                'teacher_id' => $teacher['teacher_id']
            ];
        } else {
            $admin_info = [
                'name' => 'ครูผู้สอน',
                'role' => 'ครูผู้สอน',
                'initials' => 'T'
            ];
        }
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    error_log("Database error: " . $e->getMessage());
    $admin_info = [
        'name' => 'ไม่พบข้อมูล',
        'role' => 'ไม่พบข้อมูล',
        'initials' => 'x'
    ];
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// ดึงรายชื่อแผนกวิชา และระดับชั้น สำหรับฟิลเตอร์
try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงระดับชั้นที่มีในระบบ
    $stmt = $conn->prepare("
        SELECT DISTINCT level 
        FROM classes 
        WHERE academic_year_id = ? 
        ORDER BY CASE 
            WHEN level = 'ปวช.1' THEN 1
            WHEN level = 'ปวช.2' THEN 2
            WHEN level = 'ปวช.3' THEN 3
            WHEN level = 'ปวส.1' THEN 4
            WHEN level = 'ปวส.2' THEN 5
            ELSE 6
        END
    ");
    $stmt->execute([$current_academic_year_id]);
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $departments = [];
    $levels = [];
}

// -------- จัดการกับการบันทึกการเช็คชื่อ (POST) --------
$save_success = false;
$save_error = false;
$error_message = '';
$response_message = '';

if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
        
        foreach ($_POST['attendance'] as $student_id => $data) {
            $status = $data['status'] ?? 'absent';
            $remarks = $data['remarks'] ?? '';
            
            // ตรวจสอบว่ามีข้อมูลการเช็คชื่อของนักเรียนในวันนี้แล้วหรือไม่
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
                $stmt->execute([$student_id, $current_academic_year_id, $attendance_date, $status, $user_id, $remarks]);
            }
            
            // อัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
            $stmt = $conn->prepare("
                SELECT record_id FROM student_academic_records 
                WHERE student_id = ? AND academic_year_id = ?
            ");
            $stmt->execute([$student_id, $current_academic_year_id]);
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
                $stmt->execute([$student_id, $current_academic_year_id, $student_id, $current_academic_year_id, $record['record_id']]);
            }
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ (ถ้าต้องการบันทึก)
        $skip_admin_action = isset($_POST['skip_admin_action']) && $_POST['skip_admin_action'] == 1;
        
        if (!$skip_admin_action) {
            $action_type = 'update_student_status';
            $action_details = json_encode([
                'type' => 'attendance',
                'date' => $attendance_date,
                'student_count' => count($_POST['attendance']),
                'method' => 'manual'
            ]);
            
            $stmt = $conn->prepare("
                INSERT INTO admin_actions (admin_id, action_type, action_details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $action_type, $action_details]);
        }
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "บันทึกการเช็คชื่อเรียบร้อยแล้ว จำนวน " . count($_POST['attendance']) . " คน";
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAttendanceReport()'
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/check_attendance.css'
];

$extra_js = [
    'assets/js/bulk_attendance.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'departments' => $departments,
    'levels' => $levels,
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'save_success' => $save_success,
    'save_error' => $save_error,
    'error_message' => $error_message,
    'response_message' => $response_message,
    'user_role' => $user_role
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/bulk_attendance_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
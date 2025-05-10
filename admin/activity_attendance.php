<?php
/**
 * activity_attendance.php - หน้าบันทึกการเข้าร่วมกิจกรรมกลาง
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
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

// รับพารามิเตอร์
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$activity_id) {
    // กรณีไม่ระบุรหัสกิจกรรม ให้กลับไปที่หน้ารายการกิจกรรม
    header('Location: activities.php');
    exit;
}

// ดึงข้อมูลกิจกรรม
try {
    $stmt = $conn->prepare("
        SELECT 
            a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
            a.description, a.required_attendance, a.created_at,
            a.academic_year_id, a.created_by,
            u.first_name, u.last_name
        FROM activities a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        // กรณีไม่พบข้อมูลกิจกรรม
        $_SESSION['error_message'] = 'ไม่พบข้อมูลกิจกรรมที่ต้องการ';
        header('Location: activities.php');
        exit;
    }
    
    // ดึงแผนกวิชาเป้าหมาย
    $stmt = $conn->prepare("
        SELECT d.department_name
        FROM activity_target_departments atd
        JOIN departments d ON atd.department_id = d.department_id
        WHERE atd.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $activity['target_departments'] = $target_departments;
    
    // ดึงระดับชั้นเป้าหมาย
    $stmt = $conn->prepare("
        SELECT level
        FROM activity_target_levels
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $activity['target_levels'] = $target_levels;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรม';
    header('Location: activities.php');
    exit;
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'activity_attendance';
$page_title = 'บันทึกการเข้าร่วมกิจกรรม';
$page_header = 'บันทึกการเข้าร่วมกิจกรรม: ' . $activity['activity_name'];

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $admin_info = [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => 'ผู้ดูแลระบบ',
            'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
        ];
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
        
        $admin_info = [
            'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
            'role' => $teacher['position'] . ' ' . $teacher['department_name'],
            'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
            'teacher_id' => $teacher['teacher_id']
        ];
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

// -------- จัดการกับการบันทึกการเข้าร่วมกิจกรรม (POST) --------
$save_success = false;
$save_error = false;
$error_message = '';
$response_message = '';

if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        foreach ($_POST['attendance'] as $student_id => $data) {
            $status = $data['status'] ?? 'absent';
            $remarks = $data['remarks'] ?? '';
            
            // ตรวจสอบว่ามีข้อมูลการเข้าร่วมกิจกรรมของนักเรียนแล้วหรือไม่
            $stmt = $conn->prepare("
                SELECT attendance_id FROM activity_attendance 
                WHERE student_id = ? AND activity_id = ?
            ");
            $stmt->execute([$student_id, $activity_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // อัปเดตข้อมูลเดิม
                $stmt = $conn->prepare("
                    UPDATE activity_attendance 
                    SET attendance_status = ?, recorder_id = ?, remarks = ?
                    WHERE attendance_id = ?
                ");
                $stmt->execute([$status, $user_id, $remarks, $existing['attendance_id']]);
            } else {
                // เพิ่มข้อมูลใหม่
                $stmt = $conn->prepare("
                    INSERT INTO activity_attendance 
                    (student_id, activity_id, attendance_status, recorder_id, record_time, remarks)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$student_id, $activity_id, $status, $user_id, $remarks]);
            }
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'record_activity_attendance';
        $action_details = json_encode([
            'activity_id' => $activity_id,
            'activity_name' => $activity['activity_name'],
            'student_count' => count($_POST['attendance'])
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $action_type, $action_details]);
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "บันทึกการเข้าร่วมกิจกรรมเรียบร้อยแล้ว จำนวน " . count($_POST['attendance']) . " คน";
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    }
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

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/activities.css',
    'assets/css/check_attendance.css'
];

$extra_js = [
    'assets/js/activity_attendance.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'departments' => $departments,
    'levels' => $levels,
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'activity' => $activity,
    'save_success' => $save_success,
    'save_error' => $save_error,
    'error_message' => $error_message,
    'response_message' => $response_message,
    'user_role' => $user_role
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/activity_attendance_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
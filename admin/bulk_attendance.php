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


// ตรวจสอบการบันทึกการเช็คชื่อ
if (isset($_POST['save_attendance']) && isset($_POST['attendance']) && is_array($_POST['attendance'])) {
    // เก็บค่า class_id ไว้ใน session
    if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
        $_SESSION['last_selected_class_id'] = $_POST['class_id'];
    }
}

// ดึงค่า class_id ที่เลือกล่าสุดจาก session (ถ้ามี)
$selected_class_id = $_SESSION['last_selected_class_id'] ?? '';

// ถ้าไม่มีค่าใน session ให้ใช้ค่าจาก POST หรือ GET
if (empty($selected_class_id)) {
    if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
        $selected_class_id = $_POST['class_id'];
    } elseif (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
        $selected_class_id = $_GET['class_id'];
    }
}

// ถ้ามีค่า class_id และไม่มีการบันทึกไว้ใน session ให้บันทึก
if (!empty($selected_class_id) && empty($_SESSION['last_selected_class_id'])) {
    $_SESSION['last_selected_class_id'] = $selected_class_id;
}

// ดึงข้อมูลชั้นเรียนที่เลือก (ถ้ามี)
$selected_class_info = [];
if (!empty($selected_class_id)) {
    try {
        // เชื่อมต่อฐานข้อมูล (ต้องอยู่หลังจากมีการเชื่อมต่อฐานข้อมูลแล้ว)
        $stmt = $conn->prepare("
            SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$selected_class_id]);
        $selected_class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching class info: " . $e->getMessage());
    }
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'bulk_attendance';
$page_title = 'เช็คชื่อนักเรียนแบบกลุ่ม';
$page_header = 'ระบบเช็คชื่อนักเรียนแบบกลุ่ม';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';


// ตรวจสอบค่า SESSION สำหรับตัวกรอง (ถ้ายังไม่มีให้สร้างแบบว่าง)
if (!isset($_SESSION['bulk_attendance_filters'])) {
    $_SESSION['bulk_attendance_filters'] = [
        'class_id' => '',
        'date' => date('Y-m-d')
    ];
}

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
        $class_id = $_POST['class_id'] ?? '';
        
        // เก็บ class_id ไว้ใน session สำหรับการเลือกครั้งต่อไป
        $_SESSION['last_selected_class_id'] = $class_id;
        
        // บันทึกค่าตัวกรองลงใน SESSION
        $_SESSION['bulk_attendance_filters']['class_id'] = $class_id;
        $_SESSION['bulk_attendance_filters']['date'] = $attendance_date;
        
        // ดึงข้อมูลปีการศึกษาปัจจุบัน
        $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_academic_year) {
            throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
        }
        
        $academic_year_id = $current_academic_year['academic_year_id'];
        $processed_count = 0;
        
        // ประมวลผลข้อมูลการเช็คชื่อ
        foreach ($_POST['attendance'] as $student_id => $attendance_data) {
            // ตรวจสอบว่ามีการเช็คชื่อหรือไม่
            if (isset($attendance_data['check']) && $attendance_data['check'] == '1') {
                $status = $attendance_data['status'] ?? 'absent';
                $remarks = $attendance_data['remarks'] ?? '';
                
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
                
                $processed_count++;
            }
        }

        
        // บันทึกการเปลี่ยนแปลง
        $conn->commit();
        
        // กำหนดข้อความแจ้งความสำเร็จ
        $save_success = true;
        $response_message = "บันทึกการเช็คชื่อนักเรียนจำนวน " . $processed_count . " คนเรียบร้อย";
        
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาดทั่วไป
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// ฟังก์ชันอัปเดตข้อมูลสรุปการเข้าแถวของนักเรียน
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

// ฟังก์ชันตรวจสอบความเสี่ยงตกกิจกรรม
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


$selected_class_id = $_SESSION['last_selected_class_id'] ?? '';

// ดึงตัวกรองจาก SESSION
$selected_class_id = $_SESSION['bulk_attendance_filters']['class_id'];
$selected_date = $_SESSION['bulk_attendance_filters']['date'];
$data['selected_class_id'] = $selected_class_id;
$data['selected_class_info'] = $selected_class_info;

// ดึงข้อมูลห้องเรียนที่เลือก (ถ้ามี)
$selected_class_info = null;
if (!empty($selected_class_id)) {
    try {
        $stmt = $conn->prepare("
            SELECT c.class_id, c.level, c.group_number, d.department_name, d.department_id 
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$selected_class_id]);
        $selected_class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error when fetching class info: " . $e->getMessage());
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
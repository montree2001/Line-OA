<?php
/**
 * new_check_attendance.php - หน้าเช็คชื่อนักเรียนรูปแบบใหม่สำหรับครูที่ปรึกษา
 * 
 * คุณสมบัติ:
 * - UI แบบใหม่ที่ใช้งานง่าย ไม่ซับซ้อน
 * - เช็คชื่อได้ 4 สถานะ: มา, ขาด, สาย, ลา
 * - เช็คชื่อผ่าน PIN, QR Code, และกรอกโดยครู
 * - สามารถเช็คชื่อย้อนหลังได้ (เก็บประวัติการเช็คย้อนหลัง)
 * - แก้ไขการเช็คชื่อที่บันทึกแล้วได้
 * - ดูข้อมูลสรุปการเช็คชื่อ
 * - แสดงปีเป็น พ.ศ.
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// ดึงข้อมูลครูที่ปรึกษาจากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, d.department_name 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id 
                 LEFT JOIN departments d ON t.department_id = d.department_id 
                 WHERE t.user_id = :user_id";

try {
    $stmt = $db->prepare($teacher_query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $teacher_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_data) {
        throw new Exception("ไม่พบข้อมูลครู");
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลครู: " . $e->getMessage());
}

// สร้างข้อมูลครูที่ปรึกษา
$teacher_id = $teacher_data['teacher_id'];
$teacher_name = $teacher_data['title'] . ' ' . $teacher_data['first_name'] . ' ' . $teacher_data['last_name'];
$teacher_info = [
    'name' => $teacher_name,
    'avatar' => mb_substr($teacher_data['first_name'], 0, 1, 'UTF-8'),
    'role' => 'ครูที่ปรึกษา' . ($teacher_data['department_name'] ? ' ' . $teacher_data['department_name'] : ''),
    'profile_picture' => $teacher_data['profile_picture']
];

// ดึงห้องเรียนที่ครูเป็นที่ปรึกษา
$classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                 c.level, d.department_name, c.group_number, ay.year, ay.semester, ca.is_primary,
                 (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                 FROM class_advisors ca 
                 JOIN classes c ON ca.class_id = c.class_id 
                 JOIN departments d ON c.department_id = d.department_id 
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE ca.teacher_id = :teacher_id AND c.is_active = 1 AND ay.is_active = 1
                 ORDER BY ca.is_primary DESC, c.level, c.group_number";

try {
    $stmt = $db->prepare($classes_query);
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();
    $classes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
}

// อ่านข้อมูลห้องเรียนและเตรียมข้อมูลสำหรับแสดงผล
$teacher_classes = [];
foreach ($classes_result as $class) {
    $teacher_classes[] = [
        'id' => $class['class_id'],
        'name' => $class['class_name'],
        'level' => $class['level'],
        'department' => $class['department_name'],
        'group' => $class['group_number'],
        'year' => $class['year'],
        'semester' => $class['semester'],
        'is_primary' => $class['is_primary'],
        'total_students' => $class['total_students']
    ];
}

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// ถ้าไม่มีห้องเรียนที่กำลังดู ให้ใช้ห้องแรกในรายการ
if ($current_class_id == 0 && !empty($teacher_classes)) {
    $current_class_id = $teacher_classes[0]['id'];
}

// ตรวจสอบสิทธิ์ในการเข้าถึงห้องเรียนนี้
if ($_SESSION['role'] === 'teacher') {
    $has_permission = false;
    foreach ($teacher_classes as $class) {
        if ($class['id'] == $current_class_id) {
            $has_permission = true;
            $current_class = $class;
            break;
        }
    }
    
    if (!$has_permission) {
        // ถ้าไม่มีสิทธิ์ ให้เปลี่ยนไปใช้ห้องแรกที่มีสิทธิ์
        if (!empty($teacher_classes)) {
            $current_class_id = $teacher_classes[0]['id'];
            $current_class = $teacher_classes[0];
        } else {
            // ถ้าไม่มีห้องที่รับผิดชอบเลย ให้แสดงข้อความแจ้งเตือน
            echo "<script>alert('คุณไม่มีสิทธิ์ในการเข้าถึงข้อมูลห้องเรียนนี้');</script>";
            echo "<script>window.location.href = 'home.php';</script>";
            exit;
        }
    }
} else {
    // กรณีเป็น admin สามารถเข้าถึงได้ทุกห้อง
    try {
        // ดึงข้อมูลห้องเรียนปัจจุบัน
        $class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                       c.level, d.department_name, c.group_number, ay.year, ay.semester,
                       (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                       FROM classes c 
                       JOIN departments d ON c.department_id = d.department_id 
                       JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                       WHERE c.class_id = :class_id AND c.is_active = 1 AND ay.is_active = 1";
        
        $stmt = $db->prepare($class_query);
        $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
        $stmt->execute();
        $class_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class_result) {
            $current_class = [
                'id' => $class_result['class_id'],
                'name' => $class_result['class_name'],
                'level' => $class_result['level'],
                'department' => $class_result['department_name'],
                'group' => $class_result['group_number'],
                'year' => $class_result['year'],
                'semester' => $class_result['semester'],
                'total_students' => $class_result['total_students']
            ];
        } else {
            // ถ้าไม่พบห้องเรียน ให้ใช้ห้องแรกที่มีในระบบ
            $all_class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                              c.level, d.department_name, c.group_number, ay.year, ay.semester,
                              (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                              FROM classes c 
                              JOIN departments d ON c.department_id = d.department_id 
                              JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                              WHERE c.is_active = 1 AND ay.is_active = 1
                              LIMIT 1";
            
            $stmt = $db->query($all_class_query);
            $class_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($class_result) {
                $current_class_id = $class_result['class_id'];
                $current_class = [
                    'id' => $class_result['class_id'],
                    'name' => $class_result['class_name'],
                    'level' => $class_result['level'],
                    'department' => $class_result['department_name'],
                    'group' => $class_result['group_number'],
                    'year' => $class_result['year'],
                    'semester' => $class_result['semester'],
                    'total_students' => $class_result['total_students']
                ];
            } else {
                echo "<script>alert('ไม่พบข้อมูลห้องเรียนในระบบ');</script>";
                echo "<script>window.location.href = 'home.php';</script>";
                exit;
            }
        }
        
        // กรณีเป็น admin ให้ดึงทุกห้องเรียนมาแสดง
        $all_classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                            c.level, d.department_name, c.group_number, ay.year, ay.semester,
                            (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                            FROM classes c 
                            JOIN departments d ON c.department_id = d.department_id 
                            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                            WHERE c.is_active = 1 AND ay.is_active = 1
                            ORDER BY c.level, d.department_name, c.group_number";
        
        $stmt = $db->query($all_classes_query);
        $all_classes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($teacher_classes)) {
            // เคลียร์ข้อมูลเดิม และเพิ่มข้อมูลใหม่
            $teacher_classes = [];
        }
        
        foreach ($all_classes_result as $class) {
            $teacher_classes[] = [
                'id' => $class['class_id'],
                'name' => $class['class_name'],
                'level' => $class['level'],
                'department' => $class['department_name'],
                'group' => $class['group_number'],
                'year' => $class['year'],
                'semester' => $class['semester'],
                'total_students' => $class['total_students']
            ];
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
    }
}

// ดึงวันที่ที่ต้องการเช็คชื่อ (จาก URL หรือวันปัจจุบัน)
$check_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// ตรวจสอบความถูกต้องของรูปแบบวันที่
$date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($date_pattern, $check_date)) {
    $check_date = date('Y-m-d');
}

// ถ้าเป็นวันในอนาคต ให้เปลี่ยนเป็นวันปัจจุบัน (ยกเว้นกรณีเป็น admin)
if ($_SESSION['role'] !== 'admin' && $check_date > date('Y-m-d')) {
    $check_date = date('Y-m-d');
}

// ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
$is_retroactive = ($check_date != date('Y-m-d'));

// หาสถิติการเข้าแถววันนี้
$attendance_stats_query = "SELECT 
                          COUNT(DISTINCT s.student_id) as total_students,
                          SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.attendance_status = 'late' THEN 1 ELSE 0 END) as late_count,
                          SUM(CASE WHEN a.attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_count,
                          SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          COUNT(a.attendance_id) as checked_count
                          FROM students s
                          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :check_date
                          WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'";

try {
    $stmt = $db->prepare($attendance_stats_query);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_students = $stats_data['total_students'];
    $present_count = $stats_data['present_count'] ?: 0;
    $late_count = $stats_data['late_count'] ?: 0;
    $leave_count = $stats_data['leave_count'] ?: 0;
    $absent_count = $stats_data['absent_count'] ?: 0;
    $checked_count = $stats_data['checked_count'] ?: 0;
    $not_checked = $total_students - $checked_count;
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลสถิติการเช็คชื่อ: " . $e->getMessage());
}

// ปรับปรุงข้อมูลของ $current_class เพื่อแสดงผล
$current_class['present_count'] = $present_count;
$current_class['late_count'] = $late_count;
$current_class['leave_count'] = $leave_count;
$current_class['absent_count'] = $absent_count;
$current_class['not_checked'] = $not_checked;

// ดึงรายชื่อนักเรียนทั้งหมดพร้อมสถานะการเช็คชื่อ
$students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                  a.attendance_id, a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time, a.check_method, a.remarks
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :check_date
                 WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                 ORDER BY s.student_code";

try {
    $stmt = $db->prepare($students_query);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->execute();
    $students_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แยกนักเรียนตามสถานะการเช็คชื่อ
    $unchecked_students = [];
    $checked_students = [];
    
    foreach ($students_result as $student) {
        // สร้างข้อมูลนักเรียน
        $student_data = [
            'id' => $student['student_id'],
            'number' => $student['number'],
            'code' => $student['student_code'],
            'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
            'profile_picture' => $student['profile_picture'],
            'status' => $student['attendance_status'] ?? 'not_checked',
            'time_checked' => $student['check_time'] ?? '',
            'check_method' => $student['check_method'] ?? '',
            'remarks' => $student['remarks'] ?? '',
            'attendance_id' => $student['attendance_id'] ?? null
        ];
        
        // แยกตามสถานะ
        if ($student_data['status'] === 'not_checked') {
            $unchecked_students[] = $student_data;
        } else {
            $checked_students[] = $student_data;
        }
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: " . $e->getMessage());
}

// รวม CSS และ JS
$extra_css = [
    'assets/css/new_check_attendance.css',
    'https://fonts.googleapis.com/icon?family=Material+Icons',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];

$extra_js = [
    'assets/js/new_check_attendance.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/new_check_attendance_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'ระบบเช็คชื่อ - เช็คชื่อนักเรียน';
$page_header = 'เช็คชื่อนักเรียน';

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';
require_once '../lib/functions.php';

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ประมวลผล POST request สำหรับการเช็คชื่อ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_attendance':
                // เช็คชื่อรายบุคคล
                handleSingleAttendance($db);
                break;
            case 'mark_all':
                // เช็คชื่อทั้งหมด
                handleBulkAttendance($db);
                break;
            case 'create_pin':
                // สร้าง PIN สำหรับการเช็คชื่อ
                createAttendancePin($db);
                break;
            case 'scan_qr':
                // ประมวลผลการแสกน QR Code
                handleQrScan($db);
                break;
        }
    }
}

/**
 * บันทึกการเช็คชื่อรายบุคคล
 */
function handleSingleAttendance($db) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['student_id'], $_POST['status'], $_POST['class_id'], $_POST['date'])) {
        $_SESSION['error'] = "ข้อมูลไม่ครบถ้วน";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $_POST['class_id'] . "&date=" . $_POST['date']);
        exit;
    }

    $student_id = intval($_POST['student_id']);
    $status = $_POST['status'];
    $class_id = intval($_POST['class_id']);
    $check_date = $_POST['date'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $is_retroactive = isset($_POST['is_retroactive']) ? (bool)$_POST['is_retroactive'] : false;
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $attendance_id = isset($_POST['attendance_id']) ? intval($_POST['attendance_id']) : null;

    // ตรวจสอบสถานะที่ถูกต้อง
    if (!in_array($status, ['present', 'absent', 'late', 'leave'])) {
        $_SESSION['error'] = "สถานะไม่ถูกต้อง";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
        exit;
    }

    // สร้างหมายเหตุสำหรับการเช็คย้อนหลัง
    if ($is_retroactive && isset($_POST['retroactive_note']) && !empty($_POST['retroactive_note'])) {
        $remarks = !empty($remarks) ? $remarks . " (" . $_POST['retroactive_note'] . ")" : $_POST['retroactive_note'];
    }

    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year_data['academic_year_id'];

    // ดึง user_id ของครูที่เช็คชื่อ
    if ($teacher_id > 0) {
        $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $db->prepare($user_id_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_id_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $checker_user_id = $user_id_data['user_id'];
    } else {
        $checker_user_id = $_SESSION['user_id'];
    }

    try {
        // เริ่ม Transaction
        $db->beginTransaction();

        // ตรวจสอบว่ามีการเช็คชื่อของนักเรียนในวันนี้แล้วหรือไม่
        $check_existing_query = "SELECT attendance_id FROM attendance 
                               WHERE student_id = :student_id AND date = :check_date";
        $stmt = $db->prepare($check_existing_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->execute();
        $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_attendance) {
            // ถ้ามีข้อมูลอยู่แล้ว ให้อัพเดท
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
        } else {
            // ถ้ายังไม่มีข้อมูล ให้เพิ่มใหม่
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
        }

        // ถ้าเป็นการเช็คชื่อย้อนหลัง ให้บันทึกประวัติ
        if ($is_retroactive) {
            $log_retroactive_query = "
                INSERT INTO attendance_logs 
                    (user_id, academic_year_id, class_id, action_type, action_date, action_details, created_at)
                VALUES 
                    (:user_id, :academic_year_id, :class_id, 'retroactive_check', :check_date, :action_details, NOW())
            ";
            
            $action_details = json_encode([
                'teacher_id' => $teacher_id,
                'student_id' => $student_id,
                'status' => $status,
                'remarks' => $remarks
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $db->prepare($log_retroactive_query);
            $stmt->bindParam(':user_id', $checker_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $stmt->bindParam(':action_details', $action_details, PDO::PARAM_STR);
            $stmt->execute();
        }

        // อัพเดตสถิติการเข้าแถวในตาราง student_academic_records
        updateStudentAttendanceStats($db, $student_id, $academic_year_id);

        // ตรวจสอบนักเรียนที่เสี่ยงตกกิจกรรม
        updateStudentRiskStatus($db, $student_id, $academic_year_id);

        // Commit Transaction
        $db->commit();

        // ตั้งค่าข้อความสำเร็จ
        $_SESSION['success'] = "บันทึกการเช็คชื่อเรียบร้อย";
    } catch (Exception $e) {
        // Rollback Transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // Redirect กลับไปยังหน้าเดิม
    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
    exit;
}

/**
 * บันทึกการเช็คชื่อพร้อมกันหลายคน
 */
function handleBulkAttendance($db) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['status'], $_POST['class_id'], $_POST['date'], $_POST['student_ids'])) {
        $_SESSION['error'] = "ข้อมูลไม่ครบถ้วน";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $_POST['class_id'] . "&date=" . $_POST['date']);
        exit;
    }

    $status = $_POST['status'];
    $class_id = intval($_POST['class_id']);
    $check_date = $_POST['date'];
    $student_ids = isset($_POST['student_ids']) ? explode(',', $_POST['student_ids']) : [];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $is_retroactive = isset($_POST['is_retroactive']) ? (bool)$_POST['is_retroactive'] : false;
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;

    // ตรวจสอบสถานะที่ถูกต้อง
    if (!in_array($status, ['present', 'absent', 'late', 'leave'])) {
        $_SESSION['error'] = "สถานะไม่ถูกต้อง";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
        exit;
    }

    // ถ้าไม่มีนักเรียนที่ต้องการเช็ค
    if (empty($student_ids)) {
        $_SESSION['error'] = "ไม่มีนักเรียนที่ต้องการเช็คชื่อ";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
        exit;
    }

    // สร้างหมายเหตุสำหรับการเช็คย้อนหลัง
    if ($is_retroactive && isset($_POST['retroactive_note']) && !empty($_POST['retroactive_note'])) {
        $remarks = !empty($remarks) ? $remarks . " (" . $_POST['retroactive_note'] . ")" : $_POST['retroactive_note'];
    }

    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year_data['academic_year_id'];

    // ดึง user_id ของครูที่เช็คชื่อ
    if ($teacher_id > 0) {
        $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $db->prepare($user_id_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_id_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $checker_user_id = $user_id_data['user_id'];
    } else {
        $checker_user_id = $_SESSION['user_id'];
    }

    try {
        // เริ่ม Transaction
        $db->beginTransaction();

        // เตรียม Statements
        $check_existing_stmt = $db->prepare("SELECT attendance_id FROM attendance 
                                           WHERE student_id = :student_id AND date = :check_date");
        
        $update_stmt = $db->prepare("UPDATE attendance 
                                   SET attendance_status = :status, 
                                       check_method = 'Manual',
                                       checker_user_id = :checker_user_id, 
                                       check_time = NOW(),
                                       updated_at = NOW(),
                                       remarks = :remarks
                                   WHERE attendance_id = :attendance_id");
        
        $insert_stmt = $db->prepare("INSERT INTO attendance 
                                   (student_id, academic_year_id, date, attendance_status, check_method, 
                                   checker_user_id, check_time, created_at, updated_at, remarks) 
                                   VALUES (:student_id, :academic_year_id, :check_date, :status, 'Manual', 
                                   :checker_user_id, NOW(), NOW(), NOW(), :remarks)");

        // ประมวลผลทีละคน
        $success_count = 0;
        
        foreach ($student_ids as $student_id) {
            $student_id = intval($student_id);
            
            // ตรวจสอบว่ามีการเช็คชื่อแล้วหรือไม่
            $check_existing_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $check_existing_stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $check_existing_stmt->execute();
            $existing_attendance = $check_existing_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_attendance) {
                // อัพเดทข้อมูลเดิม
                $update_stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $update_stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
                $update_stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
                $update_stmt->bindParam(':attendance_id', $existing_attendance['attendance_id'], PDO::PARAM_INT);
                $update_stmt->execute();
            } else {
                // เพิ่มข้อมูลใหม่
                $insert_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
                $insert_stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $insert_stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
                $insert_stmt->execute();
            }
            
            // อัพเดทสถิติของนักเรียน
            updateStudentAttendanceStats($db, $student_id, $academic_year_id);
            
            // ตรวจสอบความเสี่ยง
            updateStudentRiskStatus($db, $student_id, $academic_year_id);
            
            $success_count++;
        }

        // ถ้าเป็นการเช็คชื่อย้อนหลัง ให้บันทึกประวัติ
        if ($is_retroactive) {
            $log_retroactive_query = "
                INSERT INTO attendance_logs 
                    (user_id, academic_year_id, class_id, action_type, action_date, action_details, created_at)
                VALUES 
                    (:user_id, :academic_year_id, :class_id, 'retroactive_check', :check_date, :action_details, NOW())
            ";
            
            $action_details = json_encode([
                'teacher_id' => $teacher_id,
                'status' => $status,
                'remarks' => $remarks,
                'students_count' => count($student_ids)
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt = $db->prepare($log_retroactive_query);
            $stmt->bindParam(':user_id', $checker_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $stmt->bindParam(':action_details', $action_details, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Commit Transaction
        $db->commit();

        // ตั้งค่าข้อความสำเร็จ
        $_SESSION['success'] = "บันทึกการเช็คชื่อเรียบร้อย {$success_count} คน";
    } catch (Exception $e) {
        // Rollback Transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // Redirect กลับไปยังหน้าเดิม
    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
    exit;
}

/**
 * สร้าง PIN สำหรับการเช็คชื่อ
 */
function createAttendancePin($db) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['class_id'])) {
        $_SESSION['error'] = "ไม่ได้ระบุรหัสห้องเรียน";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $class_id = intval($_POST['class_id']);
    $user_id = $_SESSION['user_id'];

    try {
        // ดึงรหัสปีการศึกษาปัจจุบัน
        $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
        $stmt = $db->query($academic_year_query);
        $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year_data['academic_year_id'];

        // ดึงการตั้งค่าเกี่ยวกับ PIN
        $settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'";
        $stmt = $db->query($settings_query);
        $settings_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $pin_expiration_minutes = $settings_data ? intval($settings_data['setting_value']) : 10;

        // สร้างรหัส PIN 4 หลักแบบสุ่ม
        $pin_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // กำหนดเวลาหมดอายุ
        $valid_from = date('Y-m-d H:i:s');
        $valid_until = date('Y-m-d H:i:s', time() + ($pin_expiration_minutes * 60));

        // เริ่ม Transaction
        $db->beginTransaction();

        // ยกเลิก PIN เก่าที่ยังใช้งานได้
        $deactivate_query = "UPDATE pins SET is_active = 0 
                            WHERE creator_user_id = :user_id AND class_id = :class_id AND is_active = 1";

        $stmt = $db->prepare($deactivate_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();

        // บันทึก PIN ใหม่
        $insert_query = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id, created_at, updated_at) 
                        VALUES (:pin_code, :user_id, :academic_year_id, :valid_from, :valid_until, 1, :class_id, NOW(), NOW())";

        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':pin_code', $pin_code, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':valid_from', $valid_from, PDO::PARAM_STR);
        $stmt->bindParam(':valid_until', $valid_until, PDO::PARAM_STR);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();

        // บันทึกการสร้าง PIN ในประวัติ
        $log_query = "INSERT INTO pin_logs (pin_code, creator_user_id, academic_year_id, class_id, created_at, action_type) 
                     VALUES (:pin_code, :user_id, :academic_year_id, :class_id, NOW(), 'create')";

        $stmt = $db->prepare($log_query);
        $stmt->bindParam(':pin_code', $pin_code, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();

        // Commit Transaction
        $db->commit();

        // บันทึกข้อมูล PIN ลงใน session สำหรับแสดงใน modal
        $_SESSION['pin_data'] = [
            'pin_code' => $pin_code,
            'expire_minutes' => $pin_expiration_minutes,
            'valid_until' => $valid_until
        ];

        $_SESSION['success'] = "สร้างรหัส PIN สำเร็จ";
    } catch (Exception $e) {
        // Rollback Transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // Redirect กลับไปยังหน้าเดิม
    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&show_pin=1");
    exit;
}

/**
 * จัดการการแสกน QR Code
 */
function handleQrScan($db) {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['qr_data'], $_POST['class_id'], $_POST['date'])) {
        $_SESSION['error'] = "ข้อมูลไม่ครบถ้วน";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $_POST['class_id'] . "&date=" . $_POST['date']);
        exit;
    }

    $qr_data = $_POST['qr_data'];
    $class_id = intval($_POST['class_id']);
    $check_date = $_POST['date'];
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;

    // แปลง QR data เป็น JSON
    $student_data = json_decode($qr_data, true);

    if (!$student_data || !isset($student_data['student_id'])) {
        $_SESSION['error'] = "QR Code ไม่ถูกต้องหรือหมดอายุ";
        header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date);
        exit;
    }

    $student_id = intval($student_data['student_id']);

    // ดึงรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year_data['academic_year_id'];

    // ดึง user_id ของครูที่เช็คชื่อ
    if ($teacher_id > 0) {
        $user_id_query = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $db->prepare($user_id_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_id_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $checker_user_id = $user_id_data['user_id'];
    } else {
        $checker_user_id = $_SESSION['user_id'];
    }

    try {
        // เริ่ม Transaction
        $db->beginTransaction();

        // ตรวจสอบว่านักเรียนอยู่ในห้องเรียนที่กำลังเช็คชื่อหรือไม่
        $check_class_query = "SELECT current_class_id FROM students WHERE student_id = :student_id";
        $stmt = $db->prepare($check_class_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $student_class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student_class || $student_class['current_class_id'] != $class_id) {
            throw new Exception("นักเรียนไม่ได้อยู่ในห้องเรียนนี้");
        }

        // ตรวจสอบว่ามีการเช็คชื่อแล้วหรือไม่
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
                           SET attendance_status = 'present', 
                               check_method = 'QR_Code',
                               checker_user_id = :checker_user_id, 
                               check_time = NOW(),
                               updated_at = NOW()
                           WHERE attendance_id = :attendance_id";
            
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':attendance_id', $existing_attendance['attendance_id'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // เพิ่มข้อมูลใหม่
            $insert_query = "INSERT INTO attendance 
                           (student_id, academic_year_id, date, attendance_status, check_method, 
                           checker_user_id, check_time, created_at, updated_at) 
                           VALUES (:student_id, :academic_year_id, :check_date, 'present', 'QR_Code', 
                           :checker_user_id, NOW(), NOW(), NOW())";
            
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $stmt->bindParam(':checker_user_id', $checker_user_id, PDO::PARAM_INT);
            $stmt->execute();
        }

        // อัพเดทสถิติของนักเรียน
        updateStudentAttendanceStats($db, $student_id, $academic_year_id);

        // อัพเดท QR Code เป็นใช้งานแล้ว
        if (isset($student_data['qr_code_id'])) {
            $update_qr_query = "UPDATE qr_codes SET is_active = 0 WHERE qr_code_id = :qr_code_id";
            $stmt = $db->prepare($update_qr_query);
            $stmt->bindParam(':qr_code_id', $student_data['qr_code_id'], PDO::PARAM_INT);
            $stmt->execute();
        }

        // Commit Transaction
        $db->commit();

        // ดึงข้อมูลนักเรียนสำหรับแสดงผล
        $student_info_query = "SELECT s.student_code, s.title, u.first_name, u.last_name
                              FROM students s
                              JOIN users u ON s.user_id = u.user_id
                              WHERE s.student_id = :student_id";
        $stmt = $db->prepare($student_info_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $student_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // บันทึกข้อมูลการสแกนลงใน session สำหรับแสดงใน modal
        $_SESSION['qr_scan_result'] = [
            'student_id' => $student_id,
            'student_code' => $student_info['student_code'],
            'student_name' => $student_info['title'] . $student_info['first_name'] . ' ' . $student_info['last_name'],
            'status' => 'success',
            'message' => 'เช็คชื่อสำเร็จ'
        ];

        $_SESSION['success'] = "เช็คชื่อด้วย QR Code สำเร็จ";
    } catch (Exception $e) {
        // Rollback Transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        
        // บันทึกข้อมูลการสแกนล้มเหลวลงใน session
        $_SESSION['qr_scan_result'] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }

    // Redirect กลับไปยังหน้าเดิม
    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=" . $class_id . "&date=" . $check_date . "&show_qr_result=1");
    exit;
}

/**
 * อัพเดทสถิติการเข้าแถวของนักเรียน
 */
function updateStudentAttendanceStats($db, $student_id, $academic_year_id) {
    // คำนวณสถิติจากตาราง attendance
    $stats_query = "SELECT 
                   SUM(CASE WHEN attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as total_present,
                   SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as total_absent
                  FROM attendance
                  WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_present = $stats['total_present'] ?? 0;
    $total_absent = $stats['total_absent'] ?? 0;
    
    // ดึงข้อมูลชั้นเรียนปัจจุบันของนักเรียน
    $class_query = "SELECT current_class_id FROM students WHERE student_id = :student_id";
    $stmt = $db->prepare($class_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_id = $class_data['current_class_id'] ?? 0;
    
    // ตรวจสอบว่ามีข้อมูลในตาราง student_academic_records หรือไม่
    $check_record_query = "SELECT record_id FROM student_academic_records 
                          WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
    $stmt = $db->prepare($check_record_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        // อัพเดทข้อมูลเดิม
        $update_query = "UPDATE student_academic_records
                        SET total_attendance_days = :total_present,
                            total_absence_days = :total_absent,
                            updated_at = NOW()
                        WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':total_present', $total_present, PDO::PARAM_INT);
        $stmt->bindParam(':total_absent', $total_absent, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // เพิ่มข้อมูลใหม่
        $insert_query = "INSERT INTO student_academic_records
                        (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, created_at, updated_at)
                        VALUES
                        (:student_id, :academic_year_id, :class_id, :total_present, :total_absent, NOW(), NOW())";
        
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_present', $total_present, PDO::PARAM_INT);
        $stmt->bindParam(':total_absent', $total_absent, PDO::PARAM_INT);
        $stmt->execute();
    }
}

/**
 * อัพเดทสถานะความเสี่ยงของนักเรียน
 */
function updateStudentRiskStatus($db, $student_id, $academic_year_id) {
    // ดึงข้อมูลการขาดเรียน
    $absence_query = "SELECT total_absence_days FROM student_academic_records 
                     WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
    $stmt = $db->prepare($absence_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $absence_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $absence_count = $absence_data['total_absence_days'] ?? 0;
    
    // ถ้าการขาดเรียนน้อยกว่า 10 ครั้ง ไม่ถือว่าเสี่ยง
    if ($absence_count < 10) {
        return;
    }
    
    // กำหนดระดับความเสี่ยง
    $risk_level = 'low';
    if ($absence_count >= 20) {
        $risk_level = 'critical';
    } elseif ($absence_count >= 15) {
        $risk_level = 'high';
    } elseif ($absence_count >= 10) {
        $risk_level = 'medium';
    }
    
    // ตรวจสอบว่ามีข้อมูลในตาราง risk_students หรือไม่
    $check_risk_query = "SELECT risk_id FROM risk_students 
                        WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
    $stmt = $db->prepare($check_risk_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $risk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $now = date('Y-m-d H:i:s');
    
    if ($risk) {
        // อัพเดทข้อมูลเดิม
        $update_query = "UPDATE risk_students
                        SET absence_count = :absence_count,
                            risk_level = :risk_level,
                            updated_at = :now
                        WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':absence_count', $absence_count, PDO::PARAM_INT);
        $stmt->bindParam(':risk_level', $risk_level, PDO::PARAM_STR);
        $stmt->bindParam(':now', $now, PDO::PARAM_STR);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // เพิ่มข้อมูลใหม่
        $insert_query = "INSERT INTO risk_students
                        (student_id, academic_year_id, absence_count, risk_level, notification_sent, created_at, updated_at)
                        VALUES
                        (:student_id, :academic_year_id, :absence_count, :risk_level, 0, :now, :now)";
        
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':absence_count', $absence_count, PDO::PARAM_INT);
        $stmt->bindParam(':risk_level', $risk_level, PDO::PARAM_STR);
        $stmt->bindParam(':now', $now, PDO::PARAM_STR);
        $stmt->execute();
    }
}
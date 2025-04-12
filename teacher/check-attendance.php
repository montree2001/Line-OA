<?php
/**
 * check-attendance.php - หน้าเช็คชื่อนักเรียนสำหรับครูที่ปรึกษา
 * 
 * ฟังก์ชันหลัก:
 * - แสดงรายชื่อนักเรียนสำหรับเช็คชื่อ
 * - เลือกวันที่เพื่อเช็คชื่อย้อนหลังได้
 * - บันทึกข้อมูลการเช็คชื่อลงฐานข้อมูล
 * - แสดงสถิติการเช็คชื่อ
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'น้องชูใจ AI - เช็คชื่อนักเรียน';
$page_header = 'เช็คชื่อนักเรียน';

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../lib/functions.php';

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ดึงข้อมูลครูที่ปรึกษาจากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, d.department_name 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id 
                 LEFT JOIN departments d ON t.department_id = d.department_id 
                 WHERE t.user_id = ?";
$teacher_stmt = $conn->prepare($teacher_query);
if ($teacher_stmt === false) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL (teacher_query): " . $conn->error);
}
$teacher_stmt->bind_param("i", $user_id);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher_data = $teacher_result->fetch_assoc();

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
                 (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as student_count
                 FROM class_advisors ca 
                 JOIN classes c ON ca.class_id = c.class_id 
                 JOIN departments d ON c.department_id = d.department_id 
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE ca.teacher_id = ? AND c.is_active = 1 AND ay.is_active = 1
                 ORDER BY ca.is_primary DESC, c.level, c.group_number";
$classes_stmt = $conn->prepare($classes_query);
if ($classes_stmt === false) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL (classes_query): " . $conn->error);
}
$classes_stmt->bind_param("i", $teacher_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

// อ่านข้อมูลห้องเรียนและเตรียมข้อมูลสำหรับแสดงผล
$teacher_classes = [];
while ($class = $classes_result->fetch_assoc()) {
    $teacher_classes[] = [
        'id' => $class['class_id'],
        'name' => $class['class_name'],
        'total_students' => $class['student_count']
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
    // ดึงข้อมูลห้องเรียนปัจจุบัน
    $class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                    (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                    FROM classes c 
                    JOIN departments d ON c.department_id = d.department_id 
                    JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                    WHERE c.class_id = ? AND c.is_active = 1 AND ay.is_active = 1";
    $class_stmt = $conn->prepare($class_query);
    if ($class_stmt === false) {
        die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL (class_query): " . $conn->error);
    }
    $class_stmt->bind_param("i", $current_class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    
    if ($class_result->num_rows > 0) {
        $current_class = $class_result->fetch_assoc();
    } else {
        // ถ้าไม่พบห้องเรียน ให้ใช้ห้องแรกที่มีในระบบ
        $all_class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                          (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                          FROM classes c 
                          JOIN departments d ON c.department_id = d.department_id 
                          JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                          WHERE c.is_active = 1 AND ay.is_active = 1
                          LIMIT 1";
        $all_class_result = $conn->query($all_class_query);
        
        if ($all_class_result->num_rows > 0) {
            $current_class = $all_class_result->fetch_assoc();
            $current_class_id = $current_class['class_id'];
        } else {
            echo "<script>alert('ไม่พบข้อมูลห้องเรียนในระบบ');</script>";
            echo "<script>window.location.href = 'home.php';</script>";
            exit;
        }
    }
    
    // กรณีเป็น admin ให้ดึงทุกห้องเรียนมาแสดง
    $all_classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                         (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                         FROM classes c 
                         JOIN departments d ON c.department_id = d.department_id 
                         JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                         WHERE c.is_active = 1 AND ay.is_active = 1
                         ORDER BY c.level, d.department_name, c.group_number";
    $all_classes_result = $conn->query($all_classes_query);
    
    $teacher_classes = [];
    while ($class = $all_classes_result->fetch_assoc()) {
        $teacher_classes[] = [
            'id' => $class['class_id'],
            'name' => $class['class_name'],
            'total_students' => $class['total_students']
        ];
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

// หาสถิติการเข้าแถววันนี้ - แก้ไขคอลัมน์จาก is_present เป็น attendance_status
$attendance_stats_query = "SELECT 
                           COUNT(DISTINCT s.student_id) as total_students,
                           SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                           SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                           COUNT(a.attendance_id) as checked_count
                          FROM students s
                          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                          WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";

$stats_stmt = $conn->prepare($attendance_stats_query);
if ($stats_stmt === false) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL (attendance_stats_query): " . $conn->error);
}
$stats_stmt->bind_param("si", $check_date, $current_class_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats_data = $stats_result->fetch_assoc();

$total_students = $stats_data['total_students'];
$present_count = $stats_data['present_count'] ?: 0;
$absent_count = $stats_data['absent_count'] ?: 0;
$checked_count = $stats_data['checked_count'] ?: 0;
$not_checked = $total_students - $checked_count;

// ปรับปรุงข้อมูลของ $current_class เพื่อแสดงผล
$current_class['present_count'] = $present_count;
$current_class['absent_count'] = $absent_count;
$current_class['not_checked'] = $not_checked;

// ดึงรายชื่อนักเรียนทั้งหมดพร้อมสถานะการเช็คชื่อ - แก้ไขจาก is_present เป็น attendance_status
$students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                  a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time, a.check_method, a.remarks
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                 WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
                 ORDER BY s.student_code";

$students_stmt = $conn->prepare($students_query);
if ($students_stmt === false) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL (students_query): " . $conn->error);
}
$students_stmt->bind_param("si", $check_date, $current_class_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// แยกนักเรียนตามสถานะการเช็คชื่อ
$unchecked_students = [];
$checked_students = [];

while ($student = $students_result->fetch_assoc()) {
    // สร้างข้อมูลนักเรียน และเปลี่ยนการตรวจสอบจาก is_present เป็น attendance_status
    $student_data = [
        'id' => $student['student_id'],
        'number' => $student['number'],
        'code' => $student['student_code'],
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'status' => isset($student['attendance_status']) ? ($student['attendance_status'] == 'present' ? 'present' : 'absent') : 'not_checked',
        'time_checked' => $student['check_time'] ?? '',
        'check_method' => $student['check_method'] ?? '',
        'remarks' => $student['remarks'] ?? ''
    ];
    
    // แยกตามสถานะ
    if ($student_data['status'] === 'not_checked') {
        $unchecked_students[] = $student_data;
    } else {
        $checked_students[] = $student_data;
    }
}

// ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
$is_retroactive = ($check_date != date('Y-m-d'));

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-check-attendance.css',
    'assets/css/modal.css',
    'assets/css/alerts.css'
];

$extra_js = [
    'assets/js/teacher-check-attendance.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_check_attendance_content.php';

// ปิดการเชื่อมต่อกับฐานข้อมูล
$teacher_stmt->close();
$classes_stmt->close();
$stats_stmt->close();
$students_stmt->close();
$conn->close();

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
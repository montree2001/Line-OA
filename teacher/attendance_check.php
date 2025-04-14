<?php
/**
 * attendance_check.php - หน้าเช็คชื่อนักเรียนสำหรับครูที่ปรึกษา
 * 
 * คุณสมบัติ:
 * - เช็คชื่อได้ 4 สถานะ: มา/ขาด/สาย/ลา
 * - สร้างรหัส PIN 4 หลักเพื่อให้นักเรียนเช็คชื่อ
 * - สแกน QR Code ของนักเรียน
 * - เช็คชื่อย้อนหลังได้พร้อมบันทึกประวัติ
 * - แสดงสรุปข้อมูลนักเรียนรายบุคคล
 * - ดาวน์โหลดรายงานได้
 * - ดูข้อมูลผู้ปกครองได้
 * - แสดงปีการศึกษาเป็น พ.ศ.
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

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
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css',
    'assets/css/custom.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
    'assets/js/attendance.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/attendance_check_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once $content_path;
require_once 'templates/footer.php';
?>
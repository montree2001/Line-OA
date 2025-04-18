<?php
// เริ่มต้น session และเชื่อมต่อฐานข้อมูล
session_start();
require_once '../config/db_config.php';
require_once '../lib/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'dashboard';
$page_title = 'น้องชูใจ AI - หน้าหลักครูที่ปรึกษา';
$page_header = 'หน้าหลัก';

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

// ทดลองใช้การ Query ตรงๆ เพื่อหลีกเลี่ยงปัญหา prepared statement
$teacher_query = "SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, d.department_name 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id 
                 LEFT JOIN departments d ON t.department_id = d.department_id 
                 WHERE t.user_id = $user_id";
$teacher_result = $conn->query($teacher_query);
if (!$teacher_result) {
    die("การดึงข้อมูลครูล้มเหลว: " . $conn->error);
}
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
                 c.level, d.department_name, c.group_number, ay.year, ay.semester, ca.is_primary
                 FROM class_advisors ca 
                 JOIN classes c ON ca.class_id = c.class_id 
                 JOIN departments d ON c.department_id = d.department_id 
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE ca.teacher_id = $teacher_id AND c.is_active = 1 AND ay.is_active = 1
                 ORDER BY ca.is_primary DESC, c.level, c.group_number";
$classes_result = $conn->query($classes_query);
if (!$classes_result) {
    die("การดึงข้อมูลชั้นเรียนล้มเหลว: " . $conn->error);
}

// อ่านข้อมูลห้องเรียนและเตรียมข้อมูลสำหรับแสดงผล
$teacher_classes = [];
while ($class = $classes_result->fetch_assoc()) {
    // นับจำนวนนักเรียนในห้อง
    $class_id = $class['class_id'];
    $student_count_query = "SELECT COUNT(*) as total FROM students WHERE current_class_id = $class_id AND status = 'กำลังศึกษา'";
    $student_count_result = $conn->query($student_count_query);
    if (!$student_count_result) {
        continue; // ข้ามห้องเรียนนี้ถ้ามีปัญหา
    }
    $student_count_data = $student_count_result->fetch_assoc();
    $total_students = $student_count_data['total'];
    
    // หาสถิติการเข้าแถววันนี้
    $today = date('Y-m-d');
    $attendance_query = "SELECT 
                          SUM(CASE WHEN a.attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          COUNT(*) as checked_count
                         FROM attendance a 
                         JOIN students s ON a.student_id = s.student_id 
                         WHERE s.current_class_id = $class_id AND a.date = '$today'";
    $attendance_result = $conn->query($attendance_query);
    if (!$attendance_result) {
        // ตั้งค่าเริ่มต้นถ้าคำสั่ง SQL ผิดพลาด
        $present_today = 0;
        $absent_today = 0;
        $checked_count = 0;
    } else {
        $attendance_data = $attendance_result->fetch_assoc();
        $present_today = $attendance_data['present_count'] ?? 0;
        $absent_today = $attendance_data['absent_count'] ?? 0;
        $checked_count = $attendance_data['checked_count'] ?? 0;
    }
    $not_checked = $total_students - $checked_count;
    
    // คำนวณอัตราการเข้าแถวเฉลี่ย
    $attendance_rate_query = "SELECT 
                              (SUM(CASE WHEN a.attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as rate
                             FROM attendance a 
                             JOIN students s ON a.student_id = s.student_id 
                             WHERE s.current_class_id = $class_id AND a.date BETWEEN DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AND CURRENT_DATE()";
    $attendance_rate_result = $conn->query($attendance_rate_query);
    if (!$attendance_rate_result) {
        $attendance_rate = 0;
    } else {
        $attendance_rate_data = $attendance_rate_result->fetch_assoc();
        $attendance_rate = $attendance_rate_data['rate'] ?? 0;
    }
    
    // หาจำนวนนักเรียนเสี่ยงตกกิจกรรม
    $risk_query = "SELECT COUNT(DISTINCT s.student_id) as risk_count
                  FROM students s
                  JOIN student_academic_records sar ON s.student_id = sar.student_id
                  WHERE s.current_class_id = $class_id 
                  AND sar.total_absence_days > (SELECT required_attendance_days * 0.3 FROM academic_years WHERE is_active = 1)";
    $risk_result = $conn->query($risk_query);
    if (!$risk_result) {
        $at_risk_count = 0;
    } else {
        $risk_data = $risk_result->fetch_assoc();
        $at_risk_count = $risk_data['risk_count'] ?? 0;
    }
    
    // เพิ่มในอาร์เรย์ของห้องเรียน
    $teacher_classes[] = [
        'id' => $class['class_id'],
        'name' => $class['class_name'],
        'level' => $class['level'],
        'department' => $class['department_name'],
        'group' => $class['group_number'],
        'year' => $class['year'],
        'semester' => $class['semester'],
        'is_primary' => $class['is_primary'],
        'total_students' => $total_students,
        'present_today' => $present_today,
        'absent_today' => $absent_today,
        'not_checked' => $not_checked,
        'attendance_rate' => number_format($attendance_rate, 1),
        'at_risk_count' => $at_risk_count
    ];
}

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// ถ้าไม่มีห้องเรียนที่กำลังดู ให้ใช้ห้องแรกในรายการ
if ($current_class_id == 0 && !empty($teacher_classes)) {
    $current_class_id = $teacher_classes[0]['id'];
}

// ดึงข้อมูลห้องเรียนปัจจุบัน
$current_class = null;
foreach ($teacher_classes as $class) {
    if ($class['id'] == $current_class_id) {
        $current_class = $class;
        break;
    }
}

// หากไม่พบห้องเรียนที่ระบุ ให้ใช้ห้องแรก
if ($current_class === null && !empty($teacher_classes)) {
    $current_class = $teacher_classes[0];
    $current_class_id = $current_class['id'];
}

// ตรวจสอบรหัส PIN ที่ใช้งานอยู่
$active_pin = null;
if ($current_class !== null) {
    $pin_query = "SELECT pin_code, TIMESTAMPDIFF(MINUTE, NOW(), valid_until) as expire_in_minutes
                 FROM pins
                 WHERE creator_user_id = $user_id AND class_id = $current_class_id AND is_active = 1 AND valid_until > NOW()
                 ORDER BY valid_until DESC
                 LIMIT 1";
    $pin_result = $conn->query($pin_query);
    if ($pin_result && $pin_result->num_rows > 0) {
        $pin_data = $pin_result->fetch_assoc();
        $active_pin = [
            'code' => $pin_data['pin_code'],
            'expire_in_minutes' => $pin_data['expire_in_minutes']
        ];
    }
}

// ดึงข้อมูลนักเรียน 5 คนล่าสุดที่เช็คชื่อ
$students_summary = [];
if ($current_class !== null) {
    $today = date('Y-m-d');
    $student_summary_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                             a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time, 
                             (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number
                            FROM attendance a
                            JOIN students s ON a.student_id = s.student_id
                            JOIN users u ON s.user_id = u.user_id
                            WHERE s.current_class_id = $current_class_id AND a.date = '$today'
                            ORDER BY a.check_time DESC
                            LIMIT 5";
    $student_summary_result = $conn->query($student_summary_query);
    if ($student_summary_result) {
        while ($student = $student_summary_result->fetch_assoc()) {
            $students_summary[] = [
                'id' => $student['student_id'],
                'number' => $student['number'],
                'student_code' => $student['student_code'],
                'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
                'status' => ($student['attendance_status'] === 'absent') ? 'absent' : 'present',
                'time' => $student['check_time']
            ];
        }
    }
}

// ดึงข้อมูลนักเรียนที่มีความเสี่ยงตกกิจกรรม
$at_risk_students = [];
if ($current_class !== null) {
    $risk_students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                           sar.total_attendance_days, sar.total_absence_days,
                           CASE 
                               WHEN (sar.total_attendance_days + sar.total_absence_days) = 0 THEN 0 
                               ELSE (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) 
                           END as attendance_rate,
                           (SELECT DATE_FORMAT(MAX(a.date), '%d/%m/%Y') FROM attendance a WHERE a.student_id = s.student_id AND a.attendance_status = 'absent') as last_absent,
                           (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number
                          FROM students s
                          JOIN users u ON s.user_id = u.user_id
                          JOIN student_academic_records sar ON s.student_id = sar.student_id
                          WHERE s.current_class_id = $current_class_id AND s.status = 'กำลังศึกษา'
                          AND sar.total_absence_days > (SELECT required_attendance_days * 0.3 FROM academic_years WHERE is_active = 1)
                          ORDER BY attendance_rate ASC
                          LIMIT 5";
    $risk_students_result = $conn->query($risk_students_query);
    if ($risk_students_result) {
        while ($student = $risk_students_result->fetch_assoc()) {
            $at_risk_students[] = [
                'id' => $student['student_id'],
                'number' => $student['number'],
                'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
                'attendance_rate' => number_format($student['attendance_rate'], 1),
                'absent_days' => $student['total_absence_days'],
                'last_absent' => $student['last_absent'] ?? 'ไม่มีข้อมูล'
            ];
        }
    }
}

// ดึงประกาศล่าสุด
$announcements = [];
$announcements_query = "SELECT announcement_id, title, content, DATE_FORMAT(created_at, '%d %b %Y') as formatted_date
                       FROM announcements
                       WHERE status = 'active' AND (is_all_targets = 1 OR target_department IS NULL OR 
                             target_department = (SELECT department_id FROM teachers WHERE teacher_id = $teacher_id))
                       ORDER BY created_at DESC
                       LIMIT 3";
$announcements_result = $conn->query($announcements_query);
if ($announcements_result) {
    while ($announcement = $announcements_result->fetch_assoc()) {
        $announcements[] = [
            'id' => $announcement['announcement_id'],
            'title' => $announcement['title'],
            'content' => $announcement['content'],
            'date' => $announcement['formatted_date']
        ];
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-home.css',
    'assets/css/modal.css',
    'assets/css/alerts.css'
];

$extra_js = [
    'assets/js/teacher-home.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_home_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>

<script src="assets/js/teacher-home.js?v=<?php echo time(); ?>"></script>
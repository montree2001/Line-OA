<?php
/**
 * home.php - หน้าหลักระบบนักเรียน
 * แสดงข้อมูลสรุปและฟังก์ชันหลักของระบบ
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นนักเรียน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// กำหนดเมนูปัจจุบัน
$current_page = 'dashboard';

// ดึงข้อมูลนักเรียน
$user_id = $_SESSION['user_id'] ?? 0;
$conn = getDB();

try {
    $sql = "SELECT s.student_id, s.student_code, s.title, s.current_class_id, s.status,
                u.first_name, u.last_name, u.profile_picture
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน
        session_destroy();
        header('Location: ../index.php?error=no_student_data');
        exit;
    }
    
    // ดึงข้อมูลชั้นเรียน
    if ($student['current_class_id']) {
        $class_sql = "SELECT c.level, d.department_name, c.group_number
                      FROM classes c
                      JOIN departments d ON c.department_id = d.department_id
                      WHERE c.class_id = :class_id";
        $class_stmt = $conn->prepare($class_sql);
        $class_stmt->bindParam(':class_id', $student['current_class_id'], PDO::PARAM_INT);
        $class_stmt->execute();
        $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
        
        $student['class'] = $class_info ? 
            $class_info['level'] . ' แผนก' . $class_info['department_name'] . ' กลุ่ม ' . $class_info['group_number'] : 
            'ไม่ระบุ';
    } else {
        $student['class'] = 'ไม่ระบุ';
    }
    
    // อัปเดตข้อมูลในเซสชัน
    $_SESSION['student_id'] = $student['student_id'];
    $_SESSION['student_code'] = $student['student_code'];
    $_SESSION['first_name'] = $student['first_name'];
    $_SESSION['last_name'] = $student['last_name'];
    
    // Avatar จากชื่อ (ใช้อักษรตัวแรกของชื่อ)
    $student['avatar'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
    // ดึงข้อมูลสถิติการเข้าแถว
    // ดึงปีการศึกษาปัจจุบัน
    $academic_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $academic_stmt = $conn->prepare($academic_sql);
    $academic_stmt->execute();
    $academic_year = $academic_stmt->fetch(PDO::FETCH_ASSOC);
    
    $attendance_stats = [
        'total_days' => 0,
        'attendance_days' => 0,
        'attendance_percentage' => 0
    ];
    
    if ($academic_year) {
        $stats_sql = "SELECT total_attendance_days, total_absence_days 
                      FROM student_academic_records 
                      WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
        $stats_stmt->bindParam(':academic_year_id', $academic_year['academic_year_id'], PDO::PARAM_INT);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $attendance_stats['total_days'] = $stats['total_attendance_days'] + $stats['total_absence_days'];
            $attendance_stats['attendance_days'] = $stats['total_attendance_days'];
            $attendance_stats['attendance_percentage'] = $attendance_stats['total_days'] > 0 ? 
                round(($stats['total_attendance_days'] / $attendance_stats['total_days']) * 100) : 0;
        }
    }
    
    // ดึงประวัติการเช็คชื่อล่าสุด 5 รายการ
    $history_sql = "SELECT a.date, a.is_present, a.check_method, a.check_time
                   FROM attendance a
                   WHERE a.student_id = :student_id
                   ORDER BY a.date DESC, a.check_time DESC
                   LIMIT 5";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
    $history_stmt->execute();
    $history_rows = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $check_in_history = [];
    foreach ($history_rows as $row) {
        $date = new DateTime($row['date']);
        $check_in_history[] = [
            'day' => $date->format('d'),
            'month' => $date->format('M'),
            'status' => $row['is_present'] ? 'present' : 'absent',
            'method' => $row['check_method'],
            'time' => substr($row['check_time'], 0, 5)
        ];
    }
    
    // ดึงประกาศล่าสุด
    $announcements = [
        [
            'title' => 'แจ้งข่าวหยุดเรียน',
            'content' => 'วันที่ 13-15 เมษายน 2568 หยุดเรียนเนื่องในเทศกาลสงกรานต์',
            'date' => '10 เม.ย. 2568',
            'badge' => 'info',
            'badge_text' => 'ประกาศ'
        ],
        [
            'title' => 'กำหนดสอบปลายภาค',
            'content' => 'สอบปลายภาคเรียนที่ 2/2567 ในวันที่ 1-5 พฤษภาคม 2568',
            'date' => '5 เม.ย. 2568',
            'badge' => 'urgent',
            'badge_text' => 'ด่วน'
        ],
        [
            'title' => 'กิจกรรมกีฬาสี',
            'content' => 'กิจกรรมกีฬาสีประจำปี 2568 จัดขึ้นในวันที่ 20-21 พฤษภาคม 2568',
            'date' => '1 เม.ย. 2568',
            'badge' => 'event',
            'badge_text' => 'กิจกรรม'
        ]
    ];
    
    // ข้อความแจ้งเตือน
    $alert = [
        'title' => 'ยินดีต้อนรับสู่ระบบเช็คชื่อเข้าแถวออนไลน์',
        'message' => 'อย่าลืมเช็คชื่อทุกวันระหว่างเวลา 07:30 - 08:20 น.'
    ];
    
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

// กำหนด CSS เพิ่มเติม
$extra_css = ["assets/css/student-home.css"];

// กำหนด JS เพิ่มเติม
$extra_js = ["assets/js/student-home.js"];

// กำหนดหัวข้อเพจ
$page_title = "STD-Prasat - หน้าหลัก";

// เริ่มแสดงหน้าเว็บ
include 'templates/header.php';
?>

<!-- เนื้อหาหลัก -->
<?php include 'pages/student_home_content.php'; ?>

<!-- ส่วนท้าย -->
<?php include 'templates/footer.php'; ?>
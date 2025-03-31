<?php
/**
 * home.php - หน้าหลักสำหรับนักเรียนหลังจากล็อกอิน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลนักเรียน
try {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // ดึงข้อมูลการเข้าแถวของวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student['student_id'], $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    // ดึงสถิติการเข้าแถว
    $stmt = $conn->prepare("
        SELECT total_attendance_days, total_absence_days, passed_activity
        FROM student_academic_records
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student['student_id'], $current_academic_year_id]);
    $attendance_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณเปอร์เซ็นต์การเข้าแถว
    $total_days = $attendance_record['total_attendance_days'] + $attendance_record['total_absence_days'];
    $attendance_percentage = $total_days > 0 ? round(($attendance_record['total_attendance_days'] / $total_days) * 100, 1) : 0;
    
    // ดึงประวัติการเช็คชื่อล่าสุด 5 รายการ
    $stmt = $conn->prepare("
        SELECT a.date, a.check_time, a.is_present, a.check_method
        FROM attendance a
        WHERE a.student_id = ?
        ORDER BY a.date DESC, a.check_time DESC
        LIMIT 5
    ");
    $stmt->execute([$student['student_id']]);
    $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประวัติการเช็คชื่อ
    $check_in_history = [];
    foreach ($recent_attendance as $record) {
        $date_obj = new DateTime($record['date']);
        $check_in_history[] = [
            'day' => $date_obj->format('d'),
            'month' => $date_obj->format('M'),
            'time' => (new DateTime($record['check_time']))->format('H:i'),
            'status' => $record['is_present'] ? 'present' : 'absent',
            'method' => mapCheckMethod($record['check_method'])
        ];
    }
    
    // ดึงประกาศล่าสุด
    $announcements = [
        [
            'title' => 'ประกาศงดกิจกรรมหน้าเสาธง',
            'content' => 'เนื่องจากสภาพอากาศไม่เอื้ออำนวย จึงงดกิจกรรมหน้าเสาธงในวันที่ 1-3 เมษายน 2568 นักเรียนสามารถเช็คชื่อผ่านระบบได้ตามปกติ',
            'date' => '30 มีนาคม 2568',
            'badge' => 'info',
            'badge_text' => 'ประกาศ'
        ],
        [
            'title' => 'การแข่งขันกีฬาสีประจำปี 2568',
            'content' => 'ขอเชิญนักเรียนทุกคนเข้าร่วมการแข่งขันกีฬาสีประจำปี 2568 ในวันที่ 10-12 เมษายน 2568 เวลา 08.00-16.00 น.',
            'date' => '28 มีนาคม 2568',
            'badge' => 'event',
            'badge_text' => 'กิจกรรม'
        ],
        [
            'title' => 'เตือนนักเรียนที่ยังไม่ผ่านกิจกรรม',
            'content' => 'ขอให้นักเรียนที่มีอัตราการเข้าแถวต่ำกว่า 75% ติดต่อครูที่ปรึกษาโดยด่วน เพื่อรับทราบแนวทางการแก้ไข',
            'date' => '25 มีนาคม 2568',
            'badge' => 'urgent',
            'badge_text' => 'สำคัญ'
        ]
    ];
    
    // แสดงหน้าเว็บ
    $page_title = "STD-Prasat - หน้าหลักนักเรียน";
    
    // สร้างข้อมูลสำหรับแสดงผล
    $student_info = [
        'avatar' => substr($student['first_name'], 0, 1),
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'],
        'number' => $student['group_number']
    ];
    
    $attendance_stats = [
        'total_days' => $total_days,
        'attendance_days' => $attendance_record['total_attendance_days'],
        'attendance_percentage' => $attendance_percentage
    ];
    
    $alert = [
        'title' => 'แจ้งเตือนสำคัญ',
        'message' => 'มีประกาศใหม่ 3 รายการ กรุณาตรวจสอบในหน้าประกาศ'
    ];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/student_home_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}

// ฟังก์ชันแปลงรูปแบบการเช็คชื่อ
function mapCheckMethod($method) {
    switch ($method) {
        case 'GPS':
            return 'GPS';
        case 'QR_Code':
            return 'QR Code';
        case 'PIN':
            return 'PIN';
        case 'Manual':
            return 'ครูเช็คให้';
        default:
            return 'ไม่ระบุ';
    }
}
?>
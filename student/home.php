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
    
    // ดึงค่าตั้งค่าระบบสำหรับเวลาเช็คชื่อ
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time_setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $attendance_end_time = $end_time_setting['setting_value'] ?? '08:30';
    
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
    $total_days = ($attendance_record['total_attendance_days'] ?? 0) + ($attendance_record['total_absence_days'] ?? 0);
    $attendance_percentage = $total_days > 0 ? round((($attendance_record['total_attendance_days'] ?? 0) / $total_days) * 100, 1) : 0;
    
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
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    foreach ($recent_attendance as $record) {
        $date_parts = explode('-', $record['date']);
        $day = ltrim($date_parts[2], '0');
        $month = $thai_months[$date_parts[1]];
        
        $check_in_history[] = [
            'day' => $day,
            'month' => $month,
            'time' => date('H:i', strtotime($record['check_time'])),
            'status' => $record['is_present'] ? 'present' : 'absent',
            'status_text' => $record['is_present'] ? 'เข้าแถว' : 'ขาดแถว',
            'method' => mapCheckMethod($record['check_method']),
            'method_icon' => getMethodIcon($record['check_method'])
        ];
    }
    
    // ดึงประกาศล่าสุด 3 รายการ
    $stmt = $conn->prepare("
        SELECT a.title, a.content, a.type, a.created_at
        FROM announcements a
        WHERE a.status = 'active' 
        AND (a.is_all_targets = 1 
             OR (a.target_department = ? OR a.target_level = ?))
        ORDER BY a.created_at DESC
        LIMIT 3
    ");
    $dept_id = 0;
    $level = '';
    
    if (isset($student['department_id'])) {
        $dept_id = $student['department_id'];
    }
    
    if (isset($student['level'])) {
        $level = $student['level'];
    }
    
    $stmt->execute([$dept_id, $level]);
    $db_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประกาศ
    $announcements = [];
    foreach ($db_announcements as $announcement) {
        $created_date = new DateTime($announcement['created_at']);
        $badge_type = '';
        $badge_text = '';
        
        switch ($announcement['type']) {
            case 'urgent':
                $badge_type = 'urgent';
                $badge_text = 'ด่วน';
                break;
            case 'event':
                $badge_type = 'event';
                $badge_text = 'กิจกรรม';
                break;
            case 'academic':
                $badge_type = 'info';
                $badge_text = 'วิชาการ';
                break;
            default:
                $badge_type = 'info';
                $badge_text = 'ข่าวสาร';
        }
        
        $announcements[] = [
            'title' => $announcement['title'],
            'content' => $announcement['content'],
            'date' => $created_date->format('d') . ' ' . $thai_months[$created_date->format('m')] . ' ' . 
                     ($created_date->format('Y') + 543),  // แปลงเป็นปี พ.ศ.
            'badge' => $badge_type,
            'badge_text' => $badge_text
        ];
    }
    
    // ถ้าไม่มีประกาศในฐานข้อมูล ใช้ข้อมูลตัวอย่าง
    if (empty($announcements)) {
        $announcements = [
            [
                'title' => 'ประกาศงดกิจกรรมหน้าเสาธง',
                'content' => 'เนื่องจากสภาพอากาศไม่เอื้ออำนวย จึงงดกิจกรรมหน้าเสาธงในวันที่ 1-3 เมษายน 2568 นักเรียนสามารถเช็คชื่อผ่านระบบได้ตามปกติ',
                'date' => '30 มี.ค. 2568',
                'badge' => 'info',
                'badge_text' => 'ประกาศ'
            ],
            [
                'title' => 'การแข่งขันกีฬาสีประจำปี 2568',
                'content' => 'ขอเชิญนักเรียนทุกคนเข้าร่วมการแข่งขันกีฬาสีประจำปี 2568 ในวันที่ 10-12 เมษายน 2568 เวลา 08.00-16.00 น.',
                'date' => '28 มี.ค. 2568',
                'badge' => 'event',
                'badge_text' => 'กิจกรรม'
            ],
            [
                'title' => 'เตือนนักเรียนที่ยังไม่ผ่านกิจกรรม',
                'content' => 'ขอให้นักเรียนที่มีอัตราการเข้าแถวต่ำกว่า 75% ติดต่อครูที่ปรึกษาโดยด่วน เพื่อรับทราบแนวทางการแก้ไข',
                'date' => '25 มี.ค. 2568',
                'badge' => 'urgent',
                'badge_text' => 'สำคัญ'
            ]
        ];
    }
    
    // แสดงหน้าเว็บ
    $page_title = "STD-Prasat - หน้าหลักนักเรียน";
    
    // สร้างข้อมูลสำหรับแสดงผล
    $student_info = [
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number'],
        'profile_picture' => $student['profile_picture'],
        'student_code' => $student['student_code']
    ];
    
    // สร้างตัวอักษรแรกของชื่อสำหรับใช้แสดงในกรณีไม่มีรูปโปรไฟล์
    $first_char = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
    $attendance_stats = [
        'total_days' => $total_days,
        'attendance_days' => $attendance_record['total_attendance_days'] ?? 0,
        'attendance_percentage' => $attendance_percentage
    ];
    
    $attendance_status = [
        'is_checked_in' => !empty($today_attendance),
        'status_text' => !empty($today_attendance) ? 'เข้าแถวแล้ววันนี้' : 'ยังไม่ได้เข้าแถววันนี้',
        'status_class' => !empty($today_attendance) ? 'status-present' : 'status-absent',
        'status_icon' => !empty($today_attendance) ? 'check_circle' : 'cancel'
    ];
    
    $alert = [
        'title' => 'แจ้งเตือนเวลาเช็คชื่อ',
        'message' => "เช็คชื่อเข้าแถวได้ถึงเวลา $attendance_end_time น. เท่านั้น"
    ];
    
    // กำหนด CSS เพิ่มเติม
    $additional_css = ['assets/css/student-home.css'];
    
    // กำหนด JS เพิ่มเติม
    $additional_js = ['assets/js/student-home.js'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'student_home_content.php';
    
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
            return 'เช็คชื่อผ่าน GPS';
        case 'QR_Code':
            return 'เช็คชื่อด้วย QR Code';
        case 'PIN':
            return 'เช็คชื่อด้วยรหัส PIN';
        case 'Manual':
            return 'ครูเช็คชื่อให้';
        default:
            return 'ไม่ระบุ';
    }
}

// ฟังก์ชันกำหนดไอคอนตามวิธีการเช็คชื่อ
function getMethodIcon($method) {
    switch ($method) {
        case 'GPS':
            return 'gps_fixed';
        case 'QR_Code':
            return 'qr_code_scanner';
        case 'PIN':
            return 'pin';
        case 'Manual':
            return 'person';
        default:
            return 'help_outline';
    }
}
?>
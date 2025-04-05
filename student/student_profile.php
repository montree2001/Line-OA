<?php
/**
 * student_profile.php - หน้าโปรไฟล์นักเรียนใหม่
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ลดการแสดงข้อผิดพลาดในหน้าเว็บ แต่เก็บไว้ในล็อก
error_reporting(0);
ini_set('display_errors', 0);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'profile';
$page_title = 'STD-Prasat - โปรไฟล์ของฉัน';
$page_header = 'โปรไฟล์ของฉัน';

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
    SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
           u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email, u.line_id,
           c.level, c.group_number, d.department_name
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    LEFT JOIN departments d ON c.department_id = d.department_id
    WHERE s.user_id = ?
");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // แปลงข้อมูลจากฐานข้อมูลเป็นรูปแบบที่ใช้ในหน้าเว็บ
    $student_id = $student_data['student_id'];
    $class_info = $student_data['level'] . '/' . $student_data['group_number'];
    $first_char = mb_substr($student_data['first_name'], 0, 1, 'UTF-8');
    
    $student_info = [
        'id' => $student_id,
        'student_code' => $student_data['student_code'],
        'title' => $student_data['title'],
        'first_name' => $student_data['first_name'],
        'last_name' => $student_data['last_name'],
        'full_name' => $student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name'],
        'class' => $class_info,
        'department' => $student_data['department_name'],
        'department_short' => $student_data['department_short_name'],
        'phone' => $student_data['phone_number'],
        'email' => $student_data['email'],
        'line_id' => $student_data['line_id'],
        'birth_date' => $student_data['birth_date_formatted'],
        'blood_type' => $student_data['blood_type'],
        'nationality' => $student_data['nationality_name'],
        'religion' => $student_data['religion_name'],
        'avatar' => $first_char,
        'profile_image' => !empty($student_data['profile_picture']) ? $student_data['profile_picture'] : null
    ];
    
    // ดึงข้อมูลครูที่ปรึกษา
    $stmt = $conn->prepare("
        SELECT t.teacher_id, t.title, u.first_name, u.last_name, 
               u.profile_picture, u.phone_number, u.email, u.line_id,
               d.department_name
        FROM teachers t
        JOIN users u ON t.user_id = u.user_id
        JOIN departments d ON t.department_id = d.department_id
        JOIN class_advisors ca ON t.teacher_id = ca.teacher_id
        JOIN classes c ON ca.class_id = c.class_id
        WHERE c.class_id = ?
        LIMIT 1
    ");
    $stmt->execute([$student_data['current_class_id']]);
    $advisor_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($advisor_data) {
        $advisor_first_char = mb_substr($advisor_data['first_name'], 0, 1, 'UTF-8');
        
        $advisor_info = [
            'id' => $advisor_data['teacher_id'],
            'title' => $advisor_data['title'],
            'first_name' => $advisor_data['first_name'],
            'last_name' => $advisor_data['last_name'],
            'full_name' => $advisor_data['title'] . $advisor_data['first_name'] . ' ' . $advisor_data['last_name'],
            'department' => $advisor_data['department_name'],
            'phone' => $advisor_data['phone_number'],
            'email' => $advisor_data['email'],
            'line_id' => $advisor_data['line_id'],
            'avatar' => $advisor_first_char,
            'profile_image' => !empty($advisor_data['profile_picture']) ? '../' . $advisor_data['profile_picture'] : null
        ];
    } else {
        $advisor_info = [
            'full_name' => 'ไม่มีข้อมูลครูที่ปรึกษา',
            'department' => '-',
            'avatar' => 'ค'
        ];
    }
    
    // ดึงข้อมูลผู้ปกครอง
    $stmt = $conn->prepare("
    SELECT p.parent_id, p.title, p.relationship,
           u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email, u.line_id
    FROM parents p
    JOIN users u ON p.user_id = u.user_id
    JOIN parent_student_relation psr ON p.parent_id = psr.parent_id
    WHERE psr.student_id = ?
    LIMIT 1
");
    $stmt->execute([$student_id]);
    $parent_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($parent_data) {
        $parent_first_char = mb_substr($parent_data['first_name'], 0, 1, 'UTF-8');
        
        $parent_info = [
            'id' => $parent_data['parent_id'],
            'title' => $parent_data['title'],
            'first_name' => $parent_data['first_name'],
            'last_name' => $parent_data['last_name'],
            'full_name' => $parent_data['title'] . $parent_data['first_name'] . ' ' . $parent_data['last_name'],
            'relationship' => $parent_data['relationship'],
            'phone' => $parent_data['phone_number'],
            'email' => $parent_data['email'],
            'line_id' => $parent_data['line_id'],
            'address' => $parent_data['address'],
            'avatar' => $parent_first_char,
            'profile_image' => !empty($parent_data['profile_picture']) ? '../' . $parent_data['profile_picture'] : null
        ];
    } else {
        $parent_info = [
            'full_name' => 'ไม่มีข้อมูลผู้ปกครอง',
            'relationship' => '-',
            'avatar' => 'ผ'
        ];
    }
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("
        SELECT academic_year_id 
        FROM academic_years 
        WHERE is_active = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    if ($academic_year_id) {
        // ดึงข้อมูลการเข้าแถวของนักเรียน
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_school_days,
                   SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as attended_days
            FROM attendance
            WHERE student_id = ? AND academic_year_id = ?
        ");
        $stmt->execute([$student_id, $academic_year_id]);
        $attendance_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงค่า required_attendance_days จากตาราง system_settings
        $stmt = $conn->prepare("
            SELECT setting_value 
            FROM system_settings 
            WHERE setting_key = 'required_attendance_days'
        ");
        $stmt->execute();
        $required_days_setting = $stmt->fetch(PDO::FETCH_ASSOC);
        $required_days = isset($required_days_setting['setting_value']) ? 
                      intval($required_days_setting['setting_value']) : 80; // ค่าเริ่มต้นถ้าไม่พบในฐานข้อมูล
        
        // คำนวณข้อมูลสถิติการเข้าแถว
        $total_days = intval($attendance_data['total_school_days'] ?? 0);
        $attended_days = intval($attendance_data['attended_days'] ?? 0);
        
        if ($required_days > 0) {
            $attendance_percentage = round(($attended_days / $required_days) * 100);
            // จำกัดค่าไม่ให้เกิน 100%
            $attendance_percentage = min($attendance_percentage, 100);
        } else {
            $attendance_percentage = 0;
        }
        
        $attendance_stats = [
            'total_school_days' => $total_days,
            'attended_days' => $attended_days,
            'required_days' => $required_days,
            'attendance_percentage' => $attendance_percentage
        ];
    } else {
        // ใช้ข้อมูลตัวอย่าง
        $attendance_stats = [
            'total_school_days' => 97,
            'attended_days' => 97,
            'required_days' => 97,
            'attendance_percentage' => 100
        ];
    }
    
    // กำหนด CSS และ JS เพิ่มเติม
    $extra_css = [
        'assets/css/student-profile.css'
    ];
    $extra_js = [
        'assets/js/profile-image-upload.js'
    ];
    
    // เส้นทางเนื้อหา
    $content_path = 'pages/student_profile_content.php';
    
    // โหลดเทมเพลต
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาดแต่ไม่แสดงให้ผู้ใช้เห็น
    error_log("Database error in student_profile.php: " . $e->getMessage());
    
    // สร้างข้อมูลตัวอย่างสำหรับใช้ในกรณีที่เกิดข้อผิดพลาด
    $student_info = [
        'id' => 16536,
        'student_code' => '16536',
        'title' => 'นาย',
        'first_name' => 'เอกชัย',
        'last_name' => 'รักเรียน',
        'full_name' => 'นายเอกชัย รักเรียน',
        'class' => 'ม.6/1',
        'department' => 'แผนกอิเล็กทรอนิกส์',
        'department_short' => 'อิเล็กฯ',
        'phone' => '089-123-4567',
        'email' => 'ekachai.r@student.prasat.ac.th',
        'line_id' => 'ekachai_line',
        'birth_date' => '15/09/2549',
        'blood_type' => 'O',
        'nationality' => 'ไทย',
        'religion' => 'พุทธ',
        'avatar' => 'อ',
        'profile_image' => null
    ];
    
    $advisor_info = [
        'full_name' => 'อาจารย์ใจดี มากเมตตา',
        'department' => 'แผนกสามัญ',
        'phone' => '089-987-6543',
        'email' => 'jaidee.m@prasat.ac.th', 
        'line_id' => 'jaidee_teacher',
        'avatar' => 'จ',
        'profile_image' => null
    ];
    
    $parent_info = [
        'full_name' => 'นายสมชาย รักเรียน',
        'relationship' => 'บิดา',
        'phone' => '081-234-5678',
        'email' => 'somchai.r@gmail.com',
        'line_id' => 'somchai_line',
        'address' => '123 หมู่ 4 ต.กระโพ อ.ท่าตูม จ.สุรินทร์ 32120',
        'avatar' => 'ส',
        'profile_image' => null
    ];
    
    $attendance_stats = [
        'total_school_days' => 97,
        'attended_days' => 97,
        'required_days' => 97,
        'attendance_percentage' => 100
    ];
    
    // กำหนด CSS และ JS เพิ่มเติม
    $extra_css = [
        'assets/css/student-profile.css'
    ];
    $extra_js = [
        'assets/js/profile-image-upload.js'
    ];
    
    // เส้นทางเนื้อหา
    $content_path = 'pages/student_profile_content.php';
    
    // โหลดเทมเพลต
    include 'templates/main_template.php';
}
?>
<?php
/**
 * students.php - หน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (แบบ soft check สำหรับการพัฒนา)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     header('Location: login.php');
//     exit;
// }

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'students';
$page_title = 'จัดการข้อมูลนักเรียน';
$page_header = 'จัดการข้อมูลนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้ดูแลระบบ',
    'role' => $_SESSION['user_role'] ?? 'เจ้าหน้าที่',
    'initials' => mb_substr($_SESSION['user_name'] ?? 'ป', 0, 1, 'UTF-8')
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มนักเรียนใหม่',
        'icon' => 'person_add',
        'onclick' => 'showAddStudentModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'file_upload',
        'onclick' => 'showImportModal()'
    ]
];

// ดึงข้อมูลนักเรียนจากฐานข้อมูล (ใช้ข้อมูลจำลองชั่วคราว)
$students = [];

try {
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
    $query = "SELECT s.student_id, s.student_code, s.status, 
              u.title, u.first_name, u.last_name, u.line_id, 
              c.level, c.group_number, 
              d.department_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              ORDER BY c.level, c.group_number, u.first_name, u.last_name
              LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เติมข้อมูลเพิ่มเติม
    foreach ($students as &$student) {
        // สร้างชื่อชั้นเรียน
        $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        
        // จำลองข้อมูลการเข้าแถว
        $student['attendance_rate'] = rand(60, 100);
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] < 60) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] < 75) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
        
        // ตรวจสอบการเชื่อมต่อกับ LINE
        $student['line_connected'] = !empty($student['line_id']);
    }
    
} catch (PDOException $e) {
    // Log error
    error_log("Error fetching students: " . $e->getMessage());
}

// ดึงสถิติจำนวนนักเรียน
$student_stats = [
    'total' => count($students),
    'male' => 0,
    'female' => 0,
    'risk' => 0
];

// นับจำนวนนักเรียนชาย/หญิง และนักเรียนที่เสี่ยงตกกิจกรรม
foreach ($students as $student) {
    if (in_array($student['title'], ['นาย', 'เด็กชาย'])) {
        $student_stats['male']++;
    } else {
        $student_stats['female']++;
    }
    
    if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
        $student_stats['risk']++;
    }
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/students.css'
];

$extra_js = [
    'assets/js/students.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/students_content.php';

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'statistics' => $student_stats
];

// ประมวลผลการเพิ่ม/แก้ไข/ลบข้อมูลนักเรียน (ถ้ามี)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $success_message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'edit':
                $success_message = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'delete':
                $success_message = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'import':
                $success_message = "นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
        }
    }
}

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
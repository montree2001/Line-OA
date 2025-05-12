<?php
/**
 * activity_detail.php - หน้าแสดงรายละเอียดกิจกรรม
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

// รับค่า ID กิจกรรมจาก URL
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activity_id <= 0) {
    // ถ้าไม่มี ID กิจกรรมที่ถูกต้อง ให้กลับไปยังหน้ากิจกรรมทั้งหมด
    header('Location: activities.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? 0;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_id FROM students s
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $student['student_id'] ?? 0;
    
    // ดึงข้อมูลกิจกรรมตาม ID
    $stmt = $conn->prepare("
        SELECT a.*, 
               u.first_name, u.last_name, u.title as user_title,
               (SELECT attendance_status FROM activity_attendance 
                WHERE activity_id = a.activity_id AND student_id = ?) AS attendance_status
        FROM activities a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.activity_id = ?
    ");
    $stmt->execute([$student_id, $activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        // ถ้าไม่พบกิจกรรมหรือกิจกรรมไม่ได้เปิดใช้งาน
        header('Location: activities.php');
        exit;
    }
    
    // ดึงข้อมูลแผนกวิชาที่เกี่ยวข้อง
    $stmt = $conn->prepare("
        SELECT d.department_name
        FROM activity_target_departments atd
        JOIN departments d ON atd.department_id = d.department_id
        WHERE atd.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึงข้อมูลระดับชั้นที่เกี่ยวข้อง
    $stmt = $conn->prepare("
        SELECT atl.level
        FROM activity_target_levels atl
        WHERE atl.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // จัดรูปแบบวันที่ในรูปแบบไทย
    $thai_months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    
    $activity_date = new DateTime($activity['activity_date']);
    $thai_date = $activity_date->format('d') . ' ' . $thai_months[$activity_date->format('m')] . ' ' . 
                ($activity_date->format('Y') + 543);
    
    // กำหนดสถานะการเข้าร่วม
    $attendance_status = '';
    $status_class = '';
    
    if ($activity['attendance_status'] === 'present') {
        $attendance_status = 'เข้าร่วมแล้ว';
        $status_class = 'status-present';
    } elseif ($activity['attendance_status'] === 'absent') {
        $attendance_status = 'ไม่ได้เข้าร่วม';
        $status_class = 'status-absent';
    } else {
        // กรณียังไม่มีการบันทึกการเข้าร่วม
        $attendance_status = 'รอเข้าร่วม';
        $status_class = 'status-pending';
    }
    
    // สร้างข้อความกิจกรรมบังคับ
    $required_badge = $activity['required_attendance'] ? 
        '<span class="activity-badge required-badge">กิจกรรมบังคับ</span>' : '';
    
    // ตรวจสอบว่ากิจกรรมผ่านมาแล้วหรือไม่
    $is_past = $activity_date < new DateTime();
    
    // ข้อมูลผู้สร้างกิจกรรม
    $creator_name = '';
    if (!empty($activity['user_title']) && !empty($activity['first_name']) && !empty($activity['last_name'])) {
        $creator_name = $activity['user_title'] . $activity['first_name'] . ' ' . $activity['last_name'];
    } else {
        $creator_name = 'เจ้าหน้าที่วิทยาลัย';
    }
    
    // สร้างข้อมูลสำหรับแสดงผล
    $activity_data = [
        'id' => $activity['activity_id'],
        'name' => $activity['activity_name'],
        'date' => $thai_date,
        'location' => $activity['activity_location'] ?? 'ไม่ระบุสถานที่',
        'description' => $activity['description'] ?? '',
        'required' => $activity['required_attendance'],
        'required_badge' => $required_badge,
        'attendance_status' => $attendance_status,
        'status_class' => $status_class,
        'is_past' => $is_past,
        'target_departments' => $target_departments,
        'target_levels' => $target_levels,
        'creator' => $creator_name,
        'created_at' => date('d/m/Y', strtotime($activity['created_at']))
    ];
    
    // กำหนดชื่อหน้า
    $page_title = "กิจกรรม: " . $activity['activity_name'];
    
    // กำหนด CSS เพิ่มเติม
    $extra_css = ['assets/css/activity-detail.css'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/activity_detail_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}
?>
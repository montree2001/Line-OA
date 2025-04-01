<?php
/**
 * announcements.php - หน้าแสดงรายการประกาศทั้งหมด
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
        SELECT s.student_id, s.current_class_id, c.department_id, c.level
        FROM students s
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงรายการประกาศทั้งหมดที่เกี่ยวข้องกับนักเรียน
    $dept_id = isset($student['department_id']) ? $student['department_id'] : 0;
    $level = isset($student['level']) ? $student['level'] : '';
    
    $stmt = $conn->prepare("
        SELECT a.announcement_id, a.title, a.content, a.type, a.created_at, 
               u.first_name, u.last_name, u.title as user_title
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.status = 'active' 
        AND (a.is_all_targets = 1 OR (a.target_department = ? OR a.target_level = ?))
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$dept_id, $level]);
    $db_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประกาศ
    $announcements = [];
    
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
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
        
        // ตัดข้อความให้สั้นลงสำหรับแสดงในหน้ารายการ
        $short_content = mb_substr(strip_tags($announcement['content']), 0, 150, 'UTF-8');
        if (mb_strlen($announcement['content'], 'UTF-8') > 150) {
            $short_content .= '...';
        }
        
        // ผู้สร้างประกาศ
        $creator_name = '';
        if (isset($announcement['user_title']) && isset($announcement['first_name']) && isset($announcement['last_name'])) {
            $creator_name = $announcement['user_title'] . $announcement['first_name'] . ' ' . $announcement['last_name'];
        } else {
            $creator_name = 'เจ้าหน้าที่วิทยาลัย';
        }
        
        $announcements[] = [
            'id' => $announcement['announcement_id'],
            'title' => $announcement['title'],
            'content' => $short_content,
            'date' => $created_date->format('d') . ' ' . $thai_months[$created_date->format('m')] . ' ' . 
                     ($created_date->format('Y') + 543),
            'badge' => $badge_type,
            'badge_text' => $badge_text,
            'creator' => $creator_name
        ];
    }
    
    // แสดงหน้าเว็บ
    $page_title = "STD-Prasat - ประกาศทั้งหมด";
    
    // กำหนด CSS เพิ่มเติม
    $additional_css = ['assets/css/announcements.css'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/announcements_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}
?>
<?php
/**
 * announcement_detail.php - หน้าแสดงรายละเอียดประกาศฉบับเต็ม
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

// รับค่า ID ประกาศจาก URL
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($announcement_id <= 0) {
    // ถ้าไม่มี ID ประกาศที่ถูกต้อง ให้กลับไปยังหน้าประกาศทั้งหมด
    header('Location: announcements.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลประกาศตาม ID
try {
    $stmt = $conn->prepare("
        SELECT a.announcement_id, a.title, a.content, a.type, a.created_at, 
               a.created_by, u.first_name, u.last_name, u.title as user_title
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.announcement_id = ? AND a.status = 'active'
    ");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        // ถ้าไม่พบประกาศหรือประกาศไม่ได้เปิดใช้งาน
        header('Location: announcements.php');
        exit;
    }
    
    // จัดรูปแบบวันที่
    $created_date = new DateTime($announcement['created_at']);
    $thai_months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    
    $thai_date = $created_date->format('d') . ' ' . $thai_months[$created_date->format('m')] . ' ' . 
                 ($created_date->format('Y') + 543);
    
    // กำหนดแบดจ์ตามประเภทประกาศ
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
    
    // ข้อมูลผู้สร้างประกาศ
    $creator_name = '';
    if (isset($announcement['user_title']) && isset($announcement['first_name']) && isset($announcement['last_name'])) {
        $creator_name = $announcement['user_title'] . $announcement['first_name'] . ' ' . $announcement['last_name'];
    } else {
        $creator_name = 'เจ้าหน้าที่วิทยาลัย';
    }
    
    // สร้างข้อมูลสำหรับแสดงผล
    $announcement_data = [
        'id' => $announcement['announcement_id'],
        'title' => $announcement['title'],
        'content' => $announcement['content'],
        'date' => $thai_date,
        'badge' => $badge_type,
        'badge_text' => $badge_text,
        'creator' => $creator_name
    ];
    
    // กำหนดชื่อหน้า
    $page_title = "ประกาศ: " . $announcement['title'];
    
    // กำหนด CSS เพิ่มเติม
    $additional_css = ['assets/css/announcement-detail.css'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/announcement_detail_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}
?>
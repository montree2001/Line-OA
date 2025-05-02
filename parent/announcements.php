<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าแสดงประกาศและข่าวสารสำหรับผู้ปกครอง
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - ประกาศและข่าวสาร';
$current_page = 'announcements';
$extra_css = [
    'assets/css/parent-announcements.css'
];
$extra_js = [
    'assets/js/parent-announcements.js'
];

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ปกครอง
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // ถ้ายังไม่ได้ลงทะเบียนเป็นผู้ปกครอง ให้ไปที่หน้าลงทะเบียน
    header('Location: register.php');
    exit;
} else {
    $parent_data = $result->fetch_assoc();
    $parent_id = $parent_data['parent_id'];
}
$stmt->close();

// ดึงข้อมูลนักเรียนที่อยู่ในความดูแลเพื่อใช้กรองประกาศ
$student_departments = [];
$student_levels = [];

$stmt = $conn->prepare("
    SELECT DISTINCT d.department_id, c.level
    FROM parent_student_relation psr
    JOIN students s ON psr.student_id = s.student_id
    JOIN classes c ON s.current_class_id = c.class_id
    JOIN departments d ON c.department_id = d.department_id
    WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $student_departments[] = $row['department_id'];
    $student_levels[] = $row['level'];
}
$stmt->close();

// ตรวจสอบการดูรายละเอียดประกาศ
$selected_announcement = null;
if (isset($_GET['id'])) {
    $announcement_id = intval($_GET['id']);
    
    // ดึงข้อมูลประกาศที่เลือก
    $stmt = $conn->prepare("
        SELECT a.*, 
               CASE
                   WHEN a.type = 'exam' THEN 'สอบ'
                   WHEN a.type = 'event' THEN 'กิจกรรม'
                   WHEN a.type = 'important' THEN 'สำคัญ'
                   ELSE 'ประกาศ'
               END as category_name,
               CASE
                   WHEN a.type = 'exam' THEN 'exam'
                   WHEN a.type = 'event' THEN 'event'
                   WHEN a.type = 'important' THEN 'important'
                   ELSE 'announcement'
               END as category_class,
               DATE_FORMAT(a.created_at, '%d %b %Y %H:%i') as formatted_date,
               u.first_name, u.last_name, u.title
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.announcement_id = ? AND a.status = 'active'
    ");
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_announcement = $result->fetch_assoc();
        
        // ตรวจสอบว่าผู้ปกครองสามารถเข้าถึงประกาศนี้ได้หรือไม่
        $can_access = true;
        
        // ถ้าประกาศไม่ได้เป็นแบบทั้งหมด ตรวจสอบเงื่อนไข
        if ($selected_announcement['is_all_targets'] == 0) {
            $can_access = false;
            
            // ตรวจสอบแผนกและระดับชั้น
            if ($selected_announcement['target_department'] !== null) {
                foreach ($student_departments as $dept) {
                    if ($dept == $selected_announcement['target_department']) {
                        $can_access = true;
                        break;
                    }
                }
            }
            
            if (!$can_access && $selected_announcement['target_level'] !== null) {
                foreach ($student_levels as $level) {
                    if ($level == $selected_announcement['target_level']) {
                        $can_access = true;
                        break;
                    }
                }
            }
        }
        
        // ถ้าไม่สามารถเข้าถึงได้ให้ไปหน้าหลัก
        if (!$can_access) {
            $selected_announcement = null;
        }
    }
    $stmt->close();
}

// ดึงข้อมูลประกาศทั้งหมดสำหรับผู้ปกครอง
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// สร้าง query สำหรับดึงประกาศ
$query = "
    SELECT a.announcement_id, a.title, LEFT(a.content, 200) as short_content, a.type, 
           DATE_FORMAT(a.created_at, '%d %b %Y') as formatted_date,
           CASE
               WHEN a.type = 'exam' THEN 'สอบ'
               WHEN a.type = 'event' THEN 'กิจกรรม'
               WHEN a.type = 'important' THEN 'สำคัญ'
               ELSE 'ประกาศ'
           END as category_name,
           CASE
               WHEN a.type = 'exam' THEN 'exam'
               WHEN a.type = 'event' THEN 'event'
               WHEN a.type = 'important' THEN 'important'
               ELSE 'announcement'
           END as category_class
    FROM announcements a
    WHERE a.status = 'active' AND
          (a.expiration_date IS NULL OR a.expiration_date > NOW()) AND
          (a.is_all_targets = 1";

// เพิ่มเงื่อนไขกรองตามแผนกและระดับชั้นของนักเรียนในความดูแล
if (count($student_departments) > 0) {
    $dept_ids = implode(',', $student_departments);
    $query .= " OR a.target_department IN ($dept_ids)";
}

if (count($student_levels) > 0) {
    $levels = "'" . implode("','", $student_levels) . "'";
    $query .= " OR a.target_level IN ($levels)";
}

$query .= ")";

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
}

// เพิ่มเงื่อนไขตามประเภทประกาศ
if ($filter_type != 'all') {
    $query .= " AND a.type = ?";
}

// เรียงตามวันที่สร้างล่าสุด
$query .= " ORDER BY a.created_at DESC";

// กำหนดการแบ่งหน้า
$items_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// ดึงจำนวนรายการทั้งหมดเพื่อคำนวณหน้า
$count_query = str_replace("a.announcement_id, a.title, LEFT(a.content, 200) as short_content, a.type, 
           DATE_FORMAT(a.created_at, '%d %b %Y') as formatted_date,
           CASE
               WHEN a.type = 'exam' THEN 'สอบ'
               WHEN a.type = 'event' THEN 'กิจกรรม'
               WHEN a.type = 'important' THEN 'สำคัญ'
               ELSE 'ประกาศ'
           END as category_name,
           CASE
               WHEN a.type = 'exam' THEN 'exam'
               WHEN a.type = 'event' THEN 'event'
               WHEN a.type = 'important' THEN 'important'
               ELSE 'announcement'
           END as category_class", "COUNT(*) as total", $query);

// เตรียม statement สำหรับนับจำนวน
$count_stmt = $conn->prepare($count_query);

// ผูกพารามิเตอร์สำหรับการค้นหาและตัวกรอง
$param_types = "";
$param_values = [];

if (!empty($search_query)) {
    $param_types .= "ss";
    $param_values[] = $search_term;
    $param_values[] = $search_term;
}

if ($filter_type != 'all') {
    $param_types .= "s";
    $param_values[] = $filter_type;
}

// ผูกพารามิเตอร์ถ้ามี
if (!empty($param_types)) {
    $count_stmt->bind_param($param_types, ...$param_values);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_items = $count_row['total'];
$total_pages = ceil($total_items / $items_per_page);

$count_stmt->close();

// เพิ่ม LIMIT เข้าไปในคำสั่ง SQL หลัก
$query .= " LIMIT ?, ?";

// เตรียม statement สำหรับข้อมูลหลัก
$stmt = $conn->prepare($query);

// ผูกพารามิเตอร์สำหรับการค้นหาและตัวกรอง
$param_types = "";
$param_values = [];

if (!empty($search_query)) {
    $param_types .= "ss";
    $param_values[] = $search_term;
    $param_values[] = $search_term;
}

if ($filter_type != 'all') {
    $param_types .= "s";
    $param_values[] = $filter_type;
}

// เพิ่มพารามิเตอร์สำหรับ LIMIT
$param_types .= "ii";
$param_values[] = $offset;
$param_values[] = $items_per_page;

// ผูกพารามิเตอร์
if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$param_values);
}

$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/announcements_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
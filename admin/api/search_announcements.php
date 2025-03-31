<?php
/**
 * API ค้นหาประกาศ
 */

// ต้องเป็น admin เท่านั้น
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
$db = getDB();

// รับพารามิเตอร์การค้นหา
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';

// สร้างคำสั่ง SQL พื้นฐาน
$sql = "
    SELECT a.*, 
           u.first_name, u.last_name,
           CONCAT(u.first_name, ' ', u.last_name) as author_name,
           d.department_name as target_department_name
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.user_id
    LEFT JOIN departments d ON a.target_department = d.department_id
    WHERE 1=1
";

// เตรียมพารามิเตอร์สำหรับ PDO
$params = [];

// เพิ่มเงื่อนไขการค้นหา
if (!empty($keyword)) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if (!empty($type)) {
    $sql .= " AND a.type = ?";
    $params[] = $type;
}

if (!empty($department)) {
    $sql .= " AND (a.is_all_targets = '1' OR a.target_department = ?)";
    $params[] = $department;
}

if (!empty($level)) {
    $sql .= " AND (a.is_all_targets = '1' OR a.target_level = ?)";
    $params[] = $level;
}

// เรียงลำดับตามวันที่สร้าง (ล่าสุดก่อน)
$sql .= " ORDER BY a.created_at DESC";

try {
    // เตรียมและดำเนินการคำสั่ง SQL
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งผลลัพธ์กลับ
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $announcements]);
    
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาด
    error_log('Error searching announcements: ' . $e->getMessage());
    
    // ส่งข้อความผิดพลาดกลับ
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการค้นหาประกาศ: ' . $e->getMessage()]);
}
?> 
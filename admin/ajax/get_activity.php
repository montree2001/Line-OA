<?php

/**
 * get_activity.php - ไฟล์สำหรับดึงข้อมูลกิจกรรมผ่าน AJAX
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// ป้องกันการเข้าถึงโดยตรง (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

if (!$activity_id) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสกิจกรรม']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();

    // ดึงข้อมูลกิจกรรม
    $stmt = $conn->prepare("
    SELECT 
        a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
        a.description, a.required_attendance, a.created_at,
        a.academic_year_id, a.created_by,
        au.title, au.first_name, au.last_name
    FROM activities a
    LEFT JOIN admin_users au ON a.created_by = au.admin_id
    WHERE a.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }

    // ดึงแผนกวิชาเป้าหมาย
    $stmt = $conn->prepare("
        SELECT department_id
        FROM activity_target_departments
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $activity['target_departments'] = $target_departments;

    // ดึงระดับชั้นเป้าหมาย
    $stmt = $conn->prepare("
        SELECT level
        FROM activity_target_levels
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $activity['target_levels'] = $target_levels;

    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode(['success' => true, 'activity' => $activity]);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_activity.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}

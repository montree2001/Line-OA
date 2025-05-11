<?php
/**
 * API สำหรับประมวลผลการเพิ่ม/แก้ไข/ลบประกาศ โดยใช้ AJAX
 */

// ช่วยให้การ fetch แบบ AJAX ทำงานได้ถูกต้อง
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// เริ่มเซสชัน (ถ้าระบบมีการใช้เซสชัน)
if (!isset($_SESSION)) {
    session_start();
}

// กำหนดให้ส่งค่ากลับเป็น JSON
header('Content-Type: application/json');
require_once __DIR__ . '/../../db_connect.php';
$db = getDB();

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$response = [
    'success' => false,
    'message' => 'Unknown error'
];

try {
    if (isset($_POST['action'])) {
        // การเพิ่มหรือแก้ไขประกาศ
        if ($_POST['action'] === 'save_announcement') {
            $announcement_id = isset($_POST['announcement_id']) && !empty($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0;
            $title = trim($_POST['title']);
            $content = $_POST['content'];
            $type = $_POST['type'];
            $status = $_POST['status'];
            $is_all_targets = isset($_POST['is_all_targets']) ? 1 : 0;
            $target_department = $is_all_targets ? null : (isset($_POST['target_department']) && !empty($_POST['target_department']) ? (int)$_POST['target_department'] : null);
            $target_level = $is_all_targets ? null : (isset($_POST['target_level']) && !empty($_POST['target_level']) ? $_POST['target_level'] : null);
            $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
            $scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
            
            if ($announcement_id > 0) {
                // อัปเดตประกาศที่มีอยู่
                $stmt = $db->prepare("UPDATE announcements SET 
                    title = :title, 
                    content = :content, 
                    type = :type, 
                    status = :status, 
                    is_all_targets = :is_all_targets, 
                    target_department = :target_department, 
                    target_level = :target_level, 
                    expiration_date = :expiration_date, 
                    scheduled_date = :scheduled_date, 
                    updated_at = NOW() 
                    WHERE announcement_id = :announcement_id");
                $stmt->execute([
                    ':title' => $title,
                    ':content' => $content,
                    ':type' => $type,
                    ':status' => $status,
                    ':is_all_targets' => $is_all_targets,
                    ':target_department' => $target_department,
                    ':target_level' => $target_level,
                    ':expiration_date' => $expiration_date,
                    ':scheduled_date' => $scheduled_date,
                    ':announcement_id' => $announcement_id
                ]);
                $response = [
                    'success' => true,
                    'message' => 'อัปเดตประกาศเรียบร้อยแล้ว',
                    'announcement_id' => $announcement_id
                ];
            } else {
                // เพิ่มประกาศใหม่
                $stmt = $db->prepare("INSERT INTO announcements 
                    (title, content, type, status, is_all_targets, target_department, target_level, 
                    expiration_date, scheduled_date, created_by, created_at) 
                    VALUES 
                    (:title, :content, :type, :status, :is_all_targets, :target_department, :target_level, 
                    :expiration_date, :scheduled_date, :created_by, NOW())");
                $created_by = 1; // ควรใช้ ID ของผู้ใช้ที่กำลังล็อกอินอยู่
                $stmt->execute([
                    ':title' => $title,
                    ':content' => $content,
                    ':type' => $type,
                    ':status' => $status,
                    ':is_all_targets' => $is_all_targets,
                    ':target_department' => $target_department,
                    ':target_level' => $target_level,
                    ':expiration_date' => $expiration_date,
                    ':scheduled_date' => $scheduled_date,
                    ':created_by' => $created_by
                ]);
                $announcement_id = $db->lastInsertId();
                $response = [
                    'success' => true,
                    'message' => 'เพิ่มประกาศเรียบร้อยแล้ว',
                    'announcement_id' => $announcement_id
                ];
            }
        }
        
        // การลบประกาศ
        elseif ($_POST['action'] === 'delete_announcement') {
            $announcement_id = (int)$_POST['announcement_id'];
            
            $stmt = $db->prepare("DELETE FROM announcements WHERE announcement_id = :announcement_id");
            $stmt->execute([':announcement_id' => $announcement_id]);
            $response = [
                'success' => true,
                'message' => 'ลบประกาศเรียบร้อยแล้ว',
                'announcement_id' => $announcement_id
            ];
        }
        else {
            $response = [
                'success' => false,
                'message' => 'Invalid action'
            ];
        }
    }
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

// ส่งผลลัพธ์กลับเป็น JSON
echo json_encode($response);
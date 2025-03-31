<?php
/**
 * API ลบประกาศ
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

// รับ ID ประกาศ
$id = isset($_POST['id']) ? $_POST['id'] : null;

if (!$id) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบ ID ประกาศ']);
    exit;
}

try {
    // เริ่ม transaction
    $db->beginTransaction();
    
    // ลบการแจ้งเตือนที่เกี่ยวข้องกับประกาศนี้ (ถ้ามี)
    $stmt = $db->prepare("DELETE FROM notifications WHERE type = 'announcement' AND link LIKE ?");
    $stmt->execute(['%announcements.php?id=' . $id]);
    
    // ลบประกาศ
    $stmt = $db->prepare("DELETE FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    
    // ตรวจสอบจำนวนแถวที่ถูกลบ
    if ($stmt->rowCount() === 0) {
        // ไม่พบประกาศที่ต้องการลบ
        $db->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบประกาศที่ต้องการลบ']);
        exit;
    }
    
    // Commit transaction
    $db->commit();
    
    // ส่งผลลัพธ์กลับ
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'ลบประกาศเรียบร้อยแล้ว']);
    
} catch (PDOException $e) {
    // Rollback transaction
    $db->rollBack();
    
    // บันทึกข้อผิดพลาด
    error_log('Error deleting announcement: ' . $e->getMessage());
    
    // ส่งข้อความผิดพลาดกลับ
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบประกาศ: ' . $e->getMessage()]);
}
?> 
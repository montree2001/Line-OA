<?php
/**
 * API สำหรับบันทึกประกาศ
 */

// เริ่ม session
session_start();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'
    ]);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

// ตรวจสอบข้อมูลที่ส่งมา
if (!isset($_POST['title']) || !isset($_POST['content']) || !isset($_POST['type'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อมูลไม่ครบถ้วน'
    ]);
    exit;
}

// ดึงข้อมูลจาก POST
$title = trim($_POST['title']);
$content = $_POST['content'];
$type = $_POST['type'];
$status = isset($_POST['status']) ? $_POST['status'] : 'active';
$announcement_id = isset($_POST['announcement_id']) && !empty($_POST['announcement_id']) ? intval($_POST['announcement_id']) : null;

// ข้อมูลเพิ่มเติม
$target_department = isset($_POST['target_department']) ? $_POST['target_department'] : null;
$target_level = isset($_POST['target_level']) ? $_POST['target_level'] : null;
$expiration_date = isset($_POST['expiration_date']) && !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
$scheduled_date = isset($_POST['scheduled_date']) && !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
$send_notification = isset($_POST['send_notification']) ? 1 : 0;

// ตรวจสอบว่าถ้าเลือก "ทั้งหมด" ให้ไม่ระบุ department และ level
if (isset($_POST['target_all']) && $_POST['target_all'] == 'on') {
    $target_department = null;
    $target_level = null;
}

// เพิ่มข้อมูลสำหรับการ debug
error_log('Announcement data - Title: ' . $title);
error_log('Announcement data - Type: ' . $type);
error_log('Announcement data - Status: ' . $status);
error_log('Announcement data - ID: ' . $announcement_id);

try {
    // เตรียม user_id ของผู้สร้างประกาศ
    $user_id = $_SESSION['user_id'];
    
    $conn->beginTransaction();
    
    if ($announcement_id) {
        // ถ้ามี ID = แก้ไขประกาศเดิม
        $stmt = $conn->prepare("
            UPDATE announcements 
            SET title = :title, 
                content = :content, 
                type = :type, 
                status = :status,
                target_department = :target_department,
                target_level = :target_level,
                expiration_date = :expiration_date,
                scheduled_date = :scheduled_date,
                updated_at = NOW()
            WHERE announcement_id = :announcement_id
        ");
        
        $stmt->bindParam(':announcement_id', $announcement_id);
        
        // Debug
        error_log('Updating announcement with ID: ' . $announcement_id);
    } else {
        // ถ้าไม่มี ID = สร้างประกาศใหม่
        $stmt = $conn->prepare("
            INSERT INTO announcements 
            (title, content, type, status, created_by, target_department, target_level, expiration_date, scheduled_date) 
            VALUES 
            (:title, :content, :type, :status, :created_by, :target_department, :target_level, :expiration_date, :scheduled_date)
        ");
        
        $stmt->bindParam(':created_by', $user_id);
        
        // Debug
        error_log('Creating new announcement');
    }
    
    // Bind parameters
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':target_department', $target_department);
    $stmt->bindParam(':target_level', $target_level);
    $stmt->bindParam(':expiration_date', $expiration_date);
    $stmt->bindParam(':scheduled_date', $scheduled_date);
    
    // Execute
    $stmt->execute();
    
    // ถ้าเป็นการสร้างใหม่ ให้ดึง ID ที่เพิ่งสร้าง
    if (!$announcement_id) {
        $announcement_id = $conn->lastInsertId();
    }
    
    // ถ้าต้องการส่งการแจ้งเตือน
    if ($send_notification) {
        // สร้างการแจ้งเตือนในฐานข้อมูล
        $notification_title = 'ประกาศใหม่: ' . $title;
        $notification_message = 'มีประกาศใหม่ที่เกี่ยวข้องกับคุณ กรุณาตรวจสอบ';
        
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (title, message, type, user_type, related_id, target_department, target_level) 
            VALUES 
            (:title, :message, 'announcement', 'student', :related_id, :target_department, :target_level)
        ");
        
        $stmt->bindParam(':title', $notification_title);
        $stmt->bindParam(':message', $notification_message);
        $stmt->bindParam(':related_id', $announcement_id);
        $stmt->bindParam(':target_department', $target_department);
        $stmt->bindParam(':target_level', $target_level);
        
        $stmt->execute();
        
        // TODO: ส่งการแจ้งเตือนผ่าน Line (ถ้าต้องการ)
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกประกาศเรียบร้อยแล้ว',
        'announcement_id' => $announcement_id
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log('Error saving announcement: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
    ]);
}
?> 
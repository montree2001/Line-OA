<?php
/**
 * line_disconnect_student_api.php - API สำหรับการยกเลิกการเชื่อมต่อ LINE ของนักเรียน
 */

// เริ่ม session
session_start();

// ตั้งค่า header สำหรับ response
header('Content-Type: application/json');

// บันทึกข้อมูลเพื่อการดีบัก
error_log("API called with SESSION: " . json_encode($_SESSION));
error_log("POST data: " . json_encode($_POST));

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง: ไม่พบ user_id ใน session'
    ]);
    exit;
}

if (!isset($_SESSION['role'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง: ไม่พบ role ใน session'
    ]);
    exit;
}

if ($_SESSION['role'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง: คุณไม่ใช่นักเรียน'
    ]);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับ user_id จาก session
$user_id = $_SESSION['user_id'];

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ตรวจสอบว่านักเรียนมีการเชื่อมต่อ LINE หรือไม่
    $stmt = $conn->prepare("
        SELECT u.line_id, u.first_name, u.last_name, s.student_code 
        FROM users u 
        JOIN students s ON u.user_id = s.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User data: " . json_encode($user_data));
    
    if (!$user_data) {
        throw new Exception("ไม่พบข้อมูลผู้ใช้");
    }
    
    if (empty($user_data['line_id']) || strpos($user_data['line_id'], 'TEMP_') === 0) {
        throw new Exception("ไม่มีการเชื่อมต่อกับ LINE");
    }
    
    // เริ่มต้น transaction
    $conn->beginTransaction();
    
    // สร้าง line_id ชั่วคราวที่ไม่ซ้ำกัน
    $tempLineId = 'TEMP_' . $user_data['student_code'] . '_' . time() . '_' . bin2hex(random_bytes(3));
    
    // อัพเดตข้อมูลผู้ใช้ - เปลี่ยน line_id เป็นชั่วคราว และล้างรูปโปรไฟล์
    $stmt = $conn->prepare("
        UPDATE users
        SET line_id = ?, profile_picture = NULL
        WHERE user_id = ?
    ");
    $stmt->execute([$tempLineId, $user_id]);
    
    // บันทึกว่าข้อมูลได้รับการอัพเดตหรือไม่
    $rowsUpdated = $stmt->rowCount();
    error_log("Rows updated: " . $rowsUpdated);
    
    if ($rowsUpdated === 0) {
        throw new Exception("ไม่สามารถอัพเดตข้อมูลผู้ใช้ได้");
    }
    
    // บันทึกประวัติการยกเลิกการเชื่อมต่อ (ถ้ามีตาราง)
    if (tableExists($conn, 'system_logs')) {
        $actionDetails = json_encode([
            'action' => 'disconnect_line',
            'user_id' => $user_id,
            'original_line_id' => $user_data['line_id'],
            'temp_line_id' => $tempLineId,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO system_logs (log_type, user_id, action, details, created_at)
            VALUES ('line_disconnect', ?, 'student_self_disconnect', ?, NOW())
        ");
        $stmt->execute([$user_id, $actionDetails]);
    }
    
    // Commit transaction
    $conn->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'message' => 'ยกเลิกการเชื่อมต่อ LINE เรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction ถ้ามีการเริ่มต้น
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // บันทึกข้อผิดพลาด
    error_log("Error in line_disconnect_student_api.php: " . $e->getMessage());
    
    // ส่งข้อความผิดพลาดกลับ
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

/**
 * ตรวจสอบว่าตารางมีอยู่ในฐานข้อมูลหรือไม่
 * 
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 * @param string $tableName ชื่อตาราง
 * @return boolean
 */
function tableExists($conn, $tableName) {
    try {
        $stmt = $conn->prepare("
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = ? AND table_name = ?
        ");
        $stmt->execute([DB_NAME, $tableName]);
        return $stmt->fetchColumn() !== false;
    } catch (Exception $e) {
        error_log("Error checking if table exists: " . $e->getMessage());
        return false;
    }
}
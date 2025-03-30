<?php
/**
 * api/settings.php - API endpoint สำหรับบันทึกการตั้งค่าระบบ
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึง']);
    exit;
}

// เชื่อมต่อกับฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

// รับข้อมูล JSON จาก request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if (empty($data)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลที่จะบันทึก']);
    exit;
}

// เริ่ม transaction
$conn->beginTransaction();

try {
    // บันทึกการตั้งค่าทั่วไป
    if (isset($data['system'])) {
        foreach ($data['system'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'general');
        }
    }

    // บันทึกการตั้งค่าการแจ้งเตือน
    if (isset($data['notification'])) {
        foreach ($data['notification'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'notification');
        }
    }

    // บันทึกการตั้งค่าการเช็คชื่อ
    if (isset($data['attendance'])) {
        foreach ($data['attendance'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'attendance');
        }
    }

    // บันทึกการตั้งค่า GPS
    if (isset($data['gps'])) {
        foreach ($data['gps'] as $key => $value) {
            // กรณีที่เป็นตำแหน่งเพิ่มเติม
            if ($key === 'additional_locations' && is_array($value)) {
                // ลบตำแหน่งเพิ่มเติมเดิมทั้งหมด
                $stmt = $conn->prepare("DELETE FROM additional_locations");
                $stmt->execute();

                // เพิ่มตำแหน่งใหม่
                foreach ($value as $location) {
                    $stmt = $conn->prepare("INSERT INTO additional_locations (name, radius, latitude, longitude) VALUES (?, ?, ?, ?)");
                    $stmt->bindParam(1, $location['name']);
                    $stmt->bindParam(2, $location['radius']);
                    $stmt->bindParam(3, $location['latitude']);
                    $stmt->bindParam(4, $location['longitude']);
                    $stmt->execute();
                }
            } else {
                saveSystemSetting($conn, $key, $value, 'gps');
            }
        }
    }

    // บันทึกการตั้งค่า LINE
    if (isset($data['line'])) {
        foreach ($data['line'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'line');
        }
    }

    // บันทึกการตั้งค่า SMS
    if (isset($data['sms'])) {
        foreach ($data['sms'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'sms');
        }
    }

    // บันทึกการตั้งค่า Webhook
    if (isset($data['webhook'])) {
        // กรณีที่มีคำสั่งและการตอบกลับ
        if (isset($data['webhook']['commands']) && is_array($data['webhook']['commands'])) {
            // ลบคำสั่งเดิมทั้งหมด
            $stmt = $conn->prepare("DELETE FROM bot_commands");
            $stmt->execute();

            // เพิ่มคำสั่งใหม่
            foreach ($data['webhook']['commands'] as $command) {
                $stmt = $conn->prepare("INSERT INTO bot_commands (command_key, command_reply) VALUES (?, ?)");
                $stmt->bindParam(1, $command['key']);
                $stmt->bindParam(2, $command['reply']);
                $stmt->execute();
            }

            // ลบคำสั่งออกจากข้อมูลที่จะบันทึกในตาราง system_settings
            unset($data['webhook']['commands']);
        }

        // บันทึกการตั้งค่า webhook ที่เหลือ
        foreach ($data['webhook'] as $key => $value) {
            saveSystemSetting($conn, $key, $value, 'webhook');
        }
    }

    // บันทึกการดำเนินการในตาราง admin_actions
    $action_details = json_encode(['action' => 'update_settings', 'timestamp' => time()]);
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, action_details) VALUES (?, 'update_settings', ?)");
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->bindParam(2, $action_details);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว']);

} catch (Exception $e) {
    // Rollback ในกรณีที่เกิดข้อผิดพลาด
    $conn->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage()]);
}

/**
 * บันทึกการตั้งค่าในฐานข้อมูล
 */
function saveSystemSetting($conn, $key, $value, $group) {
    // ตรวจสอบว่ามีการตั้งค่านี้อยู่แล้วหรือไม่
    $stmt = $conn->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
    $stmt->bindParam(1, $key);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // อัปเดตค่าที่มีอยู่
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, setting_group = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?");
        $stmt->bindParam(1, $value);
        $stmt->bindParam(2, $group);
        $stmt->bindParam(3, $_SESSION['user_id']);
        $stmt->bindParam(4, $key);
    } else {
        // เพิ่มค่าใหม่
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_by) VALUES (?, ?, ?, ?)");
        $stmt->bindParam(1, $key);
        $stmt->bindParam(2, $value);
        $stmt->bindParam(3, $group);
        $stmt->bindParam(4, $_SESSION['user_id']);
    }
    
    $stmt->execute();
}
?>
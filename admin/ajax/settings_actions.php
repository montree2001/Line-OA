<?php
/**
 * settings_actions.php - ไฟล์สำหรับจัดการการตั้งค่าระบบผ่าน AJAX
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับข้อมูลจาก POST
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// เริ่มการตรวจสอบและดำเนินการตาม action
try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดำเนินการตาม action
    switch ($action) {
        // อัปเดตการตั้งค่า GPS
        case 'update_gps_settings':
            $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : 0;
            $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : 0;
            $radius = isset($_POST['radius']) ? (int)$_POST['radius'] : 100;
            
            if (!$latitude || !$longitude) {
                throw new Exception('ไม่ระบุพิกัด GPS');
            }
            
            if ($radius < 10 || $radius > 1000) {
                throw new Exception('รัศมีต้องอยู่ระหว่าง 10-1000 เมตร');
            }
            
            $conn->beginTransaction();
            
            // อัปเดตการตั้งค่า
            $settings = [
                'school_latitude' => $latitude,
                'school_longitude' => $longitude,
                'gps_radius' => $radius
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    UPDATE system_settings 
                    SET setting_value = ?, updated_by = ?, updated_at = NOW()
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $user_id, $key]);
            }
            
            // บันทึกการดำเนินการของผู้ดูแลระบบ
            $action_type = 'edit_settings';
            $action_details = json_encode([
                'category' => 'gps',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius
            ]);
            
            $stmt = $conn->prepare("
                INSERT INTO admin_actions (admin_id, action_type, action_details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $action_type, $action_details]);
            
            $conn->commit();
            
            // ส่งข้อมูลกลับ
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'บันทึกการตั้งค่า GPS เรียบร้อย'
            ]);
            break;
            
        // อัปเดตการตั้งค่าเวลาเช็คชื่อ
        case 'update_attendance_time':
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            
            if (!$start_time || !$end_time) {
                throw new Exception('ไม่ระบุเวลาเช็คชื่อ');
            }
            
            $conn->beginTransaction();
            
            // อัปเดตการตั้งค่า
            $settings = [
                'attendance_start_time' => $start_time,
                'attendance_end_time' => $end_time
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    UPDATE system_settings 
                    SET setting_value = ?, updated_by = ?, updated_at = NOW()
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $user_id, $key]);
            }
            
            // บันทึกการดำเนินการของผู้ดูแลระบบ
            $action_type = 'edit_settings';
            $action_details = json_encode([
                'category' => 'attendance_time',
                'start_time' => $start_time,
                'end_time' => $end_time
            ]);
            
            $stmt = $conn->prepare("
                INSERT INTO admin_actions (admin_id, action_type, action_details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $action_type, $action_details]);
            
            $conn->commit();
            
            // ส่งข้อมูลกลับ
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'บันทึกการตั้งค่าเวลาเช็คชื่อเรียบร้อย'
            ]);
            break;
            
        // อัปเดตการตั้งค่าระบบแจ้งเตือน
        case 'update_notification_settings':
            $enable_notifications = isset($_POST['enable_notifications']) ? (int)$_POST['enable_notifications'] : 0;
            $line_notification = isset($_POST['line_notification']) ? (int)$_POST['line_notification'] : 0;
            $sms_notification = isset($_POST['sms_notification']) ? (int)$_POST['sms_notification'] : 0;
            $email_notification = isset($_POST['email_notification']) ? (int)$_POST['email_notification'] : 0;
            $app_notification = isset($_POST['app_notification']) ? (int)$_POST['app_notification'] : 0;
            
            $conn->beginTransaction();
            
            // อัปเดตการตั้งค่า
            $settings = [
                'enable_notifications' => $enable_notifications,
                'line_notification' => $line_notification,
                'sms_notification' => $sms_notification,
                'email_notification' => $email_notification,
                'app_notification' => $app_notification
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    UPDATE system_settings 
                    SET setting_value = ?, updated_by = ?, updated_at = NOW()
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $user_id, $key]);
            }
            
            // บันทึกการดำเนินการของผู้ดูแลระบบ
            $action_type = 'edit_settings';
            $action_details = json_encode([
                'category' => 'notification',
                'settings' => $settings
            ]);
            
            $stmt = $conn->prepare("
                INSERT INTO admin_actions (admin_id, action_type, action_details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $action_type, $action_details]);
            
            $conn->commit();
            
            // ส่งข้อมูลกลับ
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'บันทึกการตั้งค่าระบบแจ้งเตือนเรียบร้อย'
            ]);
            break;
            
        default:
            throw new Exception('ไม่รู้จักคำสั่ง');
    }
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
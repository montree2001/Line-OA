<?php
/**
 * api/create_pin.php - API สำหรับสร้างรหัส PIN สำหรับการเช็คชื่อ
 * 
 * รับข้อมูล JSON:
 * {
 *   "class_id": int             // รหัสห้องเรียน
 * }
 * 
 * ส่งข้อมูลกลับ:
 * {
 *   "success": bool,            // สถานะการทำงาน
 *   "pin_code": string,         // รหัส PIN 4 หลัก
 *   "expire_minutes": int,      // จำนวนนาทีที่ PIN มีอายุการใช้งาน
 *   "valid_until": string       // เวลาที่ PIN หมดอายุ
 * }
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการสร้าง PIN'
    ]);
    exit;
}

// ตรวจสอบว่ารับข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'ต้องใช้วิธี POST เท่านั้น'
    ]);
    exit;
}

// รับและแปลงข้อมูล JSON
$input_data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($input_data['class_id']) || empty($input_data['class_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสห้องเรียน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // เก็บค่าที่ส่งมา
    $user_id = $_SESSION['user_id'];
    $class_id = intval($input_data['class_id']);
    
    // ตรวจสอบสิทธิ์ในการสร้าง PIN สำหรับห้องเรียนนี้
    if ($_SESSION['role'] === 'teacher') {
        $check_permission_query = "SELECT ca.class_id 
                                  FROM class_advisors ca 
                                  JOIN teachers t ON ca.teacher_id = t.teacher_id 
                                  WHERE t.user_id = :user_id AND ca.class_id = :class_id";
        
        $stmt = $db->prepare($check_permission_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('คุณไม่มีสิทธิ์ในการสร้าง PIN สำหรับห้องเรียนนี้');
        }
    }
    
    // ตรวจสอบรหัสปีการศึกษาปัจจุบัน
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $stmt = $db->query($academic_year_query);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('ไม่พบข้อมูลปีการศึกษาปัจจุบัน');
    }
    
    $academic_year_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    // ดึงการตั้งค่าเกี่ยวกับ PIN
    $settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'";
    $stmt = $db->query($settings_query);
    
    $settings_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $pin_expiration_minutes = $settings_data ? intval($settings_data['setting_value']) : 10;
    
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    $pin_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    
    // กำหนดเวลาหมดอายุ
    $valid_from = date('Y-m-d H:i:s');
    $valid_until = date('Y-m-d H:i:s', time() + ($pin_expiration_minutes * 60));
    
    // เริ่ม Transaction
    $db->beginTransaction();
    
    // ยกเลิก PIN เก่าที่ยังใช้งานได้
    $deactivate_query = "UPDATE pins SET is_active = 0 
                        WHERE creator_user_id = :user_id AND class_id = :class_id AND is_active = 1";
    
    $stmt = $db->prepare($deactivate_query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // บันทึก PIN ใหม่
    $insert_query = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id, created_at) 
                    VALUES (:pin_code, :user_id, :academic_year_id, :valid_from, :valid_until, 1, :class_id, NOW())";
    
    $stmt = $db->prepare($insert_query);
    $stmt->bindParam(':pin_code', $pin_code, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->bindParam(':valid_from', $valid_from, PDO::PARAM_STR);
    $stmt->bindParam(':valid_until', $valid_until, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Commit Transaction
    $db->commit();
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'pin_code' => $pin_code,
        'expire_minutes' => $pin_expiration_minutes,
        'valid_until' => $valid_until
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction เมื่อเกิดข้อผิดพลาด
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
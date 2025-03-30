<?php
// api/settings.php - API endpoint สำหรับการจัดการตั้งค่าระบบ

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'GET':
        // ดึงการตั้งค่าทั้งหมด
        getSettings();
        break;
    case 'POST':
        // บันทึกการตั้งค่า
        saveSettings();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับดึงการตั้งค่าทั้งหมด
function getSettings() {
    $db = getDB();
    
    try {
        // ดึงการตั้งค่าทั้งหมดจากตาราง system_settings
        $stmt = $db->prepare("SELECT setting_key, setting_value, setting_group FROM system_settings");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // ดึงข้อมูลปีการศึกษาที่ใช้งานอยู่
        $stmt = $db->prepare("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $activeYear = $stmt->fetch();
        
        if ($activeYear) {
            $settings['current_academic_year'] = $activeYear['year'];
            $settings['current_semester'] = $activeYear['semester'];
            $settings['semester_start_date'] = $activeYear['start_date'];
            $settings['semester_end_date'] = $activeYear['end_date'];
            $settings['required_attendance_days'] = $activeYear['required_attendance_days'];
        }
        
        echo json_encode(['success' => true, 'settings' => $settings]);
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงการตั้งค่า: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่า
function saveSettings() {
    $db = getDB();
    
    // รับข้อมูล JSON ที่ส่งมา
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
        return;
    }
    
    try {
        // เริ่ม transaction
        $db->beginTransaction();
        
        // ประมวลผลและบันทึกการตั้งค่าแต่ละหมวดหมู่
        if (isset($data['system'])) {
            saveSystemSettings($db, $data['system']);
        }
        
        if (isset($data['notification'])) {
            saveSettingsByGroup($db, $data['notification'], 'notification');
        }
        
        if (isset($data['attendance'])) {
            saveSettingsByGroup($db, $data['attendance'], 'attendance');
        }
        
        if (isset($data['gps'])) {
            saveSettingsByGroup($db, $data['gps'], 'gps');
        }
        
        if (isset($data['line'])) {
            saveSettingsByGroup($db, $data['line'], 'line');
        }
        
        if (isset($data['sms'])) {
            saveSettingsByGroup($db, $data['sms'], 'sms');
        }
        
        if (isset($data['webhook'])) {
            saveWebhookSettings($db, $data['webhook']);
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        // Rollback transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่าหมวด system
function saveSystemSettings($db, $settings) {
    // จัดการกับการตั้งค่าพิเศษของปีการศึกษา
    if (isset($settings['current_academic_year']) && isset($settings['current_semester'])) {
        try {
            // ตรวจสอบว่ามีปีการศึกษาอยู่แล้วหรือไม่
            $stmt = $db->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? AND semester = ?");
            $stmt->execute([$settings['current_academic_year'], $settings['current_semester']]);
            $academicYear = $stmt->fetch();
            
            if ($academicYear) {
                // อัปเดตปีการศึกษาที่มีอยู่
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 1, 
                               start_date = ?, end_date = ?, 
                               required_attendance_days = ? 
                               WHERE academic_year_id = ?");
                $stmt->execute([
                    $settings['semester_start_date'] ?? date('Y-m-d'),
                    $settings['semester_end_date'] ?? date('Y-m-d', strtotime('+4 months')),
                    $settings['required_attendance_days'] ?? 80,
                    $academicYear['academic_year_id']
                ]);
                
                // ยกเลิกการใช้งานปีการศึกษาอื่น
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 0 WHERE academic_year_id != ?");
                $stmt->execute([$academicYear['academic_year_id']]);
            } else {
                // สร้างปีการศึกษาใหม่และตั้งเป็นปีที่ใช้งาน
                $stmt = $db->prepare("INSERT INTO academic_years 
                               (year, semester, start_date, end_date, required_attendance_days, is_active) 
                               VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $settings['current_academic_year'],
                    $settings['current_semester'],
                    $settings['semester_start_date'] ?? date('Y-m-d'),
                    $settings['semester_end_date'] ?? date('Y-m-d', strtotime('+4 months')),
                    $settings['required_attendance_days'] ?? 80
                ]);
                
                // ยกเลิกการใช้งานปีการศึกษาอื่น
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 0 WHERE year != ? OR semester != ?");
                $stmt->execute([$settings['current_academic_year'], $settings['current_semester']]);
            }
        } catch (PDOException $e) {
            error_log('Error updating academic year: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // บันทึกการตั้งค่าระบบอื่นๆ
    saveSettingsByGroup($db, $settings, 'general');
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่าตามหมวดหมู่
function saveSettingsByGroup($db, $settings, $group) {
    foreach ($settings as $key => $value) {
        // แปลงค่า boolean เป็น 0/1
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        
        // แปลงค่า checkbox
        if ($value === 'on') {
            $value = '1';
        } else if ($value === 'off') {
            $value = '0';
        }
        
        // แปลงค่า array เป็น JSON
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        try {
            // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
            $stmt = $db->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                // อัปเดตการตั้งค่าที่มีอยู่
                $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                // เพิ่มการตั้งค่าใหม่
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$key, $value, $group]);
            }
        } catch (PDOException $e) {
            error_log('Error saving setting ' . $key . ': ' . $e->getMessage());
            // ทำต่อไปกับการตั้งค่าอื่น แม้จะมีข้อผิดพลาดกับการตั้งค่านี้
            continue;
        }
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่า webhook และคำสั่งตอบกลับ
function saveWebhookSettings($db, $settings) {
    // บันทึกการตั้งค่าพื้นฐาน
    saveSettingsByGroup($db, $settings, 'webhook');
    
    // จัดการกับคำสั่งและการตอบกลับ
    if (isset($settings['commands']) && is_array($settings['commands'])) {
        try {
            // ลบคำสั่งที่มีอยู่เดิม
            $stmt = $db->prepare("DELETE FROM system_settings WHERE setting_key LIKE 'command_%'");
            $stmt->execute();
            
            // เพิ่มคำสั่งใหม่
            foreach ($settings['commands'] as $index => $command) {
                $keyKey = 'command_key_' . $index;
                $replyKey = 'command_reply_' . $index;
                
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, 'webhook', NOW())");
                $stmt->execute([$keyKey, $command['key']]);
                
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, 'webhook', NOW())");
                $stmt->execute([$replyKey, $command['reply']]);
            }
        } catch (PDOException $e) {
            error_log('Error saving webhook commands: ' . $e->getMessage());
            throw $e;
        }
    }
}
?>
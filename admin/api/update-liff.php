<?php
// api/update-liff.php - API endpoint สำหรับการอัปเดตการตั้งค่า LIFF

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'POST':
        // อัปเดตการตั้งค่า LIFF
        updateLiffSettings();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับอัปเดตการตั้งค่า LIFF
function updateLiffSettings() {
    // รับข้อมูล JSON ที่ส่งมา
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['liff_id']) || !isset($data['liff_url'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน กรุณาระบุ LIFF ID และ LIFF URL']);
        return;
    }
    
    $liffId = $data['liff_id'];
    $liffType = $data['liff_type'] ?? 'tall';
    $liffUrl = $data['liff_url'];
    
    try {
        // เชื่อมต่อฐานข้อมูล
        $db = getDB();
        
        // เริ่ม transaction
        $db->beginTransaction();
        
        // บันทึกการตั้งค่า LIFF
        saveLiffSettings($db, 'liff_id', $liffId);
        saveLiffSettings($db, 'liff_type', $liffType);
        saveLiffSettings($db, 'liff_url', $liffUrl);
        
        // อัปเดตไฟล์การตั้งค่า LIFF
        updateLiffConfigFile($liffId, $liffType, $liffUrl);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'อัปเดตการตั้งค่า LIFF เรียบร้อยแล้ว'
        ]);
    } catch (Exception $e) {
        // Rollback transaction เมื่อเกิดข้อผิดพลาด
        if (isset($db)) {
            $db->rollBack();
        }
        
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตการตั้งค่า LIFF: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่า LIFF ลงในฐานข้อมูล
function saveLiffSettings($db, $key, $value) {
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
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, 'line', NOW())");
            $stmt->execute([$key, $value]);
        }
    } catch (PDOException $e) {
        throw new Exception('ไม่สามารถบันทึกการตั้งค่า LIFF ลงในฐานข้อมูลได้: ' . $e->getMessage());
    }
}

// ฟังก์ชันสำหรับอัปเดตไฟล์การตั้งค่า LIFF
function updateLiffConfigFile($liffId, $liffType, $liffUrl) {
    try {
        // ตำแหน่งไฟล์ JavaScript ของ LIFF
        $liffJsFile = '../lib/line_liff.js';
        
        // ตรวจสอบว่าไฟล์มีอยู่หรือไม่
        if (!file_exists($liffJsFile)) {
            throw new Exception('ไม่พบไฟล์ line_liff.js');
        }
        
        // อ่านไฟล์
        $content = file_get_contents($liffJsFile);
        
        // อัปเดต LIFF ID
        $content = preg_replace('/const\s+liffId\s+=\s+\"[^\"]*\"/', 'const liffId = "' . $liffId . '"', $content);
        
        // บันทึกไฟล์
        if (file_put_contents($liffJsFile, $content) === false) {
            throw new Exception('ไม่สามารถบันทึกไฟล์ line_liff.js ได้');
        }
        
        // อัปเดตไฟล์ callback.php และ line_login.php (ถ้าจำเป็น)
        updateCallbackFiles($liffUrl);
        
        return true;
    } catch (Exception $e) {
        throw new Exception('ไม่สามารถอัปเดตไฟล์การตั้งค่า LIFF ได้: ' . $e->getMessage());
    }
}

// ฟังก์ชันสำหรับอัปเดตไฟล์ที่เกี่ยวข้องกับการเรียกกลับ
function updateCallbackFiles($liffUrl) {
    try {
        // อัปเดตไฟล์ callback.php
        $callbackFile = '../callback.php';
        if (file_exists($callbackFile)) {
            $content = file_get_contents($callbackFile);
            
            // ปรับ URL ให้ตรงกับ redirect_uri
            $pattern = '/\$redirect_uri\s+=\s+\'[^\']*\'/';
            $replacement = '$redirect_uri = \'' . $liffUrl . '\'';
            $content = preg_replace($pattern, $replacement, $content);
            
            // บันทึกไฟล์
            if (file_put_contents($callbackFile, $content) === false) {
                throw new Exception('ไม่สามารถบันทึกไฟล์ callback.php ได้');
            }
        }
        
        // อัปเดตไฟล์ line_login.php
        $loginFile = '../line_login.php';
        if (file_exists($loginFile)) {
            $content = file_get_contents($loginFile);
            
            // ปรับ URL ให้ตรงกับ redirect_uri
            $pattern = '/\$redirect_uri\s+=\s+\'[^\']*\'/';
            $replacement = '$redirect_uri = \'' . $liffUrl . '\'';
            $content = preg_replace($pattern, $replacement, $content);
            
            // บันทึกไฟล์
            if (file_put_contents($loginFile, $content) === false) {
                throw new Exception('ไม่สามารถบันทึกไฟล์ line_login.php ได้');
            }
        }
        
        return true;
    } catch (Exception $e) {
        throw new Exception('ไม่สามารถอัปเดตไฟล์ callback ได้: ' . $e->getMessage());
    }
}
?>
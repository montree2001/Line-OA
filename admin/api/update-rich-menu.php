<?php
// api/update-rich-menu.php - API endpoint สำหรับการอัปเดต Rich Menu

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'POST':
        // อัปเดต Rich Menu
        updateRichMenu();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับอัปเดต Rich Menu
function updateRichMenu() {
    // รับข้อมูล JSON ที่ส่งมา
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
        return;
    }
    
    try {
        // เชื่อมต่อฐานข้อมูล
        $db = getDB();
        
        // เริ่ม transaction
        $db->beginTransaction();
        
        // ตั้งค่าเปิด/ปิดการใช้งาน Rich Menu
        saveRichMenuSettings($db, 'enable_rich_menu', isset($data['enable']) ? ($data['enable'] ? '1' : '0') : '0');
        
        // บันทึกการตั้งค่า Rich Menu สำหรับผู้ปกครอง
        if (isset($data['parent'])) {
            saveRichMenuSettings($db, 'parent_rich_menu_name', $data['parent']['name'] ?? '');
            saveRichMenuSettings($db, 'parent_rich_menu_id', $data['parent']['id'] ?? '');
        }
        
        // บันทึกการตั้งค่า Rich Menu สำหรับนักเรียน
        if (isset($data['student'])) {
            saveRichMenuSettings($db, 'student_rich_menu_name', $data['student']['name'] ?? '');
            saveRichMenuSettings($db, 'student_rich_menu_id', $data['student']['id'] ?? '');
        }
        
        // บันทึกการตั้งค่า Rich Menu สำหรับครู
        if (isset($data['teacher'])) {
            saveRichMenuSettings($db, 'teacher_rich_menu_name', $data['teacher']['name'] ?? '');
            saveRichMenuSettings($db, 'teacher_rich_menu_id', $data['teacher']['id'] ?? '');
        }
        
        // อัปเดต Rich Menu ใน LINE API (ถ้ามีการเปิดใช้งาน)
        $updated = false;
        if (isset($data['enable']) && $data['enable']) {
            $updated = updateRichMenuInLineAPI($data);
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'อัปเดต Rich Menu เรียบร้อยแล้ว',
            'line_api_updated' => $updated
        ]);
    } catch (Exception $e) {
        // Rollback transaction เมื่อเกิดข้อผิดพลาด
        if (isset($db)) {
            $db->rollBack();
        }
        
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดต Rich Menu: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่า Rich Menu ลงในฐานข้อมูล
function saveRichMenuSettings($db, $key, $value) {
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
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, 'webhook', NOW())");
            $stmt->execute([$key, $value]);
        }
    } catch (PDOException $e) {
        throw new Exception('ไม่สามารถบันทึกการตั้งค่า Rich Menu ลงในฐานข้อมูลได้: ' . $e->getMessage());
    }
}

// ฟังก์ชันสำหรับอัปเดต Rich Menu ใน LINE API
function updateRichMenuInLineAPI($data) {
    try {
        // ตรวจสอบว่ามีการตั้งค่า API ของ LINE หรือไม่
        $db = getDB();
        
        // ดึงค่า Channel Access Token สำหรับแต่ละบทบาท
        $tokens = [
            'parent' => getAccessToken($db, 'parent_line_access_token'),
            'student' => getAccessToken($db, 'student_line_access_token'),
            'teacher' => getAccessToken($db, 'teacher_line_access_token')
        ];
        
        // ตรวจสอบว่ามี Access Token หรือไม่
        $hasToken = false;
        foreach ($tokens as $token) {
            if (!empty($token)) {
                $hasToken = true;
                break;
            }
        }
        
        if (!$hasToken) {
            // ไม่มี Access Token จะไม่อัปเดตใน LINE API
            return false;
        }
        
        // อัปเดต Rich Menu สำหรับแต่ละบทบาท
        $updated = false;
        
        // อัปเดต Rich Menu สำหรับผู้ปกครอง
        if (!empty($tokens['parent']) && isset($data['parent']) && !empty($data['parent']['id'])) {
            $updated = setDefaultRichMenu($tokens['parent'], $data['parent']['id']) || $updated;
        }
        
        // อัปเดต Rich Menu สำหรับนักเรียน
        if (!empty($tokens['student']) && isset($data['student']) && !empty($data['student']['id'])) {
            $updated = setDefaultRichMenu($tokens['student'], $data['student']['id']) || $updated;
        }
        
        // อัปเดต Rich Menu สำหรับครู
        if (!empty($tokens['teacher']) && isset($data['teacher']) && !empty($data['teacher']['id'])) {
            $updated = setDefaultRichMenu($tokens['teacher'], $data['teacher']['id']) || $updated;
        }
        
        return $updated;
    } catch (Exception $e) {
        // บันทึกข้อผิดพลาดแต่ไม่ขัดจังหวะฟังก์ชันหลัก
        error_log('Error updating Rich Menu in LINE API: ' . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับดึงค่า Channel Access Token
function getAccessToken($db, $key) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn() ?: '';
    } catch (PDOException $e) {
        error_log('Error getting access token: ' . $e->getMessage());
        return '';
    }
}

// ฟังก์ชันสำหรับตั้งค่า Rich Menu เริ่มต้น
function setDefaultRichMenu($accessToken, $richMenuId) {
    try {
        // URL ของ LINE API สำหรับตั้งค่า Rich Menu เริ่มต้น
        $url = "https://api.line.me/v2/bot/user/all/richmenu/{$richMenuId}";
        
        // ส่งคำขอ HTTP
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        $errMsg = curl_error($ch);
        curl_close($ch);
        
        // ตรวจสอบข้อผิดพลาด
        if ($errNo) {
            error_log('Error setting default Rich Menu: ' . $errMsg);
            return false;
        }
        
        // ตรวจสอบ HTTP Code
        if ($httpCode !== 200) {
            error_log('Error setting default Rich Menu. HTTP Code: ' . $httpCode . ', Response: ' . $response);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Error setting default Rich Menu: ' . $e->getMessage());
        return false;
    }
}
?>
<?php
/**
 * line_connect_api.php - ปรับปรุง API สำหรับสร้าง QR Code และเชื่อมต่อกับ LINE
 */

// เริ่ม session
session_start();

// เชื่อมต่อไฟล์ที่จำเป็น
require_once '../../db_connect.php';

// ตั้งค่า header สำหรับ JSON
header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการร้องขอ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_qr_code':
            handleGenerateQrCode();
            break;
            
        case 'check_line_status':
            handleCheckLineStatus();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบการดำเนินการที่ระบุ'
            ]);
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'วิธีการร้องขอไม่ถูกต้อง'
    ]);
}

/**
 * จัดการการสร้าง QR Code สำหรับเชื่อมต่อกับ LINE
 */
function handleGenerateQrCode() {
    // ตรวจสอบว่ามีการส่ง student_id มาหรือไม่
    $student_id = $_POST['student_id'] ?? 0;
    
    if (empty($student_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่ระบุรหัสนักเรียน'
        ]);
        return;
    }
    
    try {
        $db = getDB();
        
        // ดึงข้อมูลของนักเรียน
        $stmt = $db->prepare("
            SELECT s.student_id, s.student_code, u.first_name, u.last_name, u.line_id
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            return;
        }
        
        // ตรวจสอบว่าเชื่อมต่อ LINE แล้วหรือไม่
        if (!empty($student['line_id']) && $student['line_id'] !== 'TEMP_' . $student['student_code'] . '%') {
            echo json_encode([
                'success' => false,
                'message' => 'นักเรียนคนนี้เชื่อมต่อกับ LINE แล้ว'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามี QR Code ที่ยังไม่หมดอายุหรือไม่
        $checkQrQuery = "SELECT qr_code_id, qr_code_data, valid_until 
                        FROM qr_codes 
                        WHERE student_id = ? AND valid_until > NOW() AND is_active = 1
                        ORDER BY valid_until DESC LIMIT 1";
        $checkQrStmt = $db->prepare($checkQrQuery);
        $checkQrStmt->execute([$student_id]);
        $existingQr = $checkQrStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingQr) {
            // ใช้ QR Code ที่มีอยู่แล้ว
            $qrData = json_decode($existingQr['qr_code_data'], true);
            $expireTime = $existingQr['valid_until'];
            $token = $qrData['token'];
        } else {
            // สร้างโทเค็นเฉพาะสำหรับการเชื่อมต่อ
            $token = md5(uniqid($student['student_code'], true));
            $expireTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // สร้างข้อมูลสำหรับ QR Code
            $qrData = [
                'type' => 'student_link',
                'student_id' => $student['student_id'],
                'student_code' => $student['student_code'],
                'token' => $token,
                'expire_time' => $expireTime
            ];
            
            // แปลงเป็น JSON
            $qrDataJson = json_encode($qrData, JSON_UNESCAPED_UNICODE);
            
            // บันทึกข้อมูล QR Code ลงในฐานข้อมูล
            $stmt = $db->prepare("
                INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, is_active)
                VALUES (?, ?, NOW(), ?, 1)
            ");
            $stmt->execute([$student_id, $qrDataJson, $expireTime]);
        }
        
        // สร้าง URL สำหรับการเชื่อมต่อ LINE
        // ในระบบจริง ควรใช้ LINE Bot API และ Messaging API เพื่อสร้าง URL ที่ถูกต้อง
        // สำหรับตัวอย่างนี้ จะส่งคืน URL จำลอง
        $lineConnectUrl = "https://line.me/R/ti/p/xxxxx?linkToken=" . urlencode($token);
        
        // สร้าง QR Code โดยใช้ Google Chart API
        $qrCodeUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($lineConnectUrl) . '&choe=UTF-8';
        
        echo json_encode([
            'success' => true,
            'message' => 'สร้าง QR Code สำเร็จ',
            'qr_code_url' => $qrCodeUrl,
            'line_connect_url' => $lineConnectUrl,
            'expire_time' => $expireTime
        ]);
    } catch (PDOException $e) {
        error_log("Error generating QR code: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการสร้าง QR Code: ' . $e->getMessage()
        ]);
    }
}

/**
 * จัดการการตรวจสอบสถานะการเชื่อมต่อกับ LINE
 */
function handleCheckLineStatus() {
    // ตรวจสอบว่ามีการส่ง student_id มาหรือไม่
    $student_id = $_POST['student_id'] ?? 0;
    
    if (empty($student_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่ระบุรหัสนักเรียน'
        ]);
        return;
    }
    
    try {
        $db = getDB();
        
        // ดึงข้อมูลของนักเรียน
        $stmt = $db->prepare("
            SELECT s.student_id, u.line_id
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            return;
        }
        
        // ตรวจสอบการเชื่อมต่อ LINE
        $is_connected = !empty($student['line_id']) && strpos($student['line_id'], 'TEMP_') !== 0;
        
        echo json_encode([
            'success' => true,
            'is_connected' => $is_connected,
            'message' => $is_connected ? 'เชื่อมต่อกับ LINE แล้ว' : 'ยังไม่ได้เชื่อมต่อกับ LINE'
        ]);
    } catch (PDOException $e) {
        error_log("Error checking LINE status: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ: ' . $e->getMessage()
        ]);
    }
}
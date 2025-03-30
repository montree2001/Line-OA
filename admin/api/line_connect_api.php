<?php
/**
 * line_connect_api.php - API สำหรับสร้าง QR Code และเชื่อมต่อกับ LINE
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

/* // ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง API นี้'
    ]);
    exit;
} */

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
            SELECT s.student_id, s.student_code, u.first_name, u.last_name
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
        
        // สร้างโทเค็นเฉพาะสำหรับการเชื่อมต่อ
        $token = md5(uniqid($student['student_code'], true));
        $expireTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // สร้างข้อมูลสำหรับ QR Code
        $qrData = json_encode([
            'type' => 'student_link',
            'student_id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'token' => $token,
            'expire_time' => $expireTime
        ]);
        
        // บันทึกข้อมูล QR Code ลงในฐานข้อมูล
        $stmt = $db->prepare("
            INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, is_active)
            VALUES (?, ?, NOW(), ?, 1)
        ");
        $stmt->execute([$student_id, $qrData, $expireTime]);
        
        // สร้าง URL สำหรับการเชื่อมต่อ LINE
        $lineConnectUrl = "https://line.me/R/ti/p/@xxx?linkToken=" . urlencode($token);
        
        // ในทางปฏิบัติจริง ต้องเรียกใช้ LINE Messaging API เพื่อสร้าง QR Code จริง
        // สำหรับตัวอย่างนี้ จะส่งคืน URL สำหรับการเชื่อมต่อแทน
        
        echo json_encode([
            'success' => true,
            'message' => 'สร้าง QR Code สำเร็จ',
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($lineConnectUrl),
            'line_connect_url' => $lineConnectUrl,
            'expire_time' => $expireTime
        ]);
    } catch (PDOException $e) {
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
        
        echo json_encode([
            'success' => true,
            'is_connected' => !empty($student['line_id']),
            'message' => !empty($student['line_id']) ? 'เชื่อมต่อกับ LINE แล้ว' : 'ยังไม่ได้เชื่อมต่อกับ LINE'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ: ' . $e->getMessage()
        ]);
    }
}
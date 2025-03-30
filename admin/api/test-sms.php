<?php
// api/test-sms.php - API endpoint สำหรับการทดสอบส่ง SMS

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'POST':
        // ทดสอบส่ง SMS
        sendTestSms();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับทดสอบส่ง SMS
function sendTestSms() {
    // รับข้อมูล JSON ที่ส่งมา
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['phone_number']) || !isset($data['message'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน กรุณาระบุเบอร์โทรศัพท์และข้อความ']);
        return;
    }
    
    $phone = $data['phone_number'];
    $message = $data['message'];
    $provider = $data['provider'] ?? 'thsms';
    $apiKey = $data['api_key'] ?? '';
    $apiSecret = $data['api_secret'] ?? '';
    $apiUrl = $data['api_url'] ?? '';
    $senderId = $data['sender_id'] ?? 'PRASAT';
    $useUnicode = $data['use_unicode'] ?? true;
    
    try {
        // ตรวจสอบรูปแบบเบอร์โทรศัพท์
        if (!preg_match('/^[0-9]{9,10}$/', $phone)) {
            throw new Exception('รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
        }
        
        // ส่ง SMS ตามผู้ให้บริการที่เลือก
        switch ($provider) {
            case 'thsms':
                $result = sendThSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode);
                break;
            case 'thaibulksms':
                $result = sendThaiBulkSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode);
                break;
            case 'twilio':
                $result = sendTwilioSms($phone, $message, $apiKey, $apiSecret, $senderId);
                break;
            case 'custom':
                $result = sendCustomSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode);
                break;
            default:
                throw new Exception('ไม่รองรับผู้ให้บริการ SMS ที่เลือก');
        }
        
        // บันทึกข้อมูลการส่ง SMS ลงในฐานข้อมูล
        logSmsSent($phone, $message, $provider, $result);
        
        // ตอบกลับผลการทำงาน
        echo json_encode([
            'success' => true,
            'message' => 'ส่ง SMS ทดสอบเรียบร้อยแล้ว',
            'details' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการส่ง SMS: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันส่ง SMS ผ่าน THSMS
function sendThSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode) {
    // ตรวจสอบว่ามีการกำหนด API URL หรือไม่
    if (empty($apiUrl)) {
        $apiUrl = 'https://api.thsms.com/api/send';
    }
    
    // ตรวจสอบว่าเบอร์โทรศัพท์มี 0 นำหน้าหรือไม่
    if (substr($phone, 0, 1) === '0') {
        // แปลงเป็นรูปแบบ +66
        $phone = '+66' . substr($phone, 1);
    }
    
    // กำหนดพารามิเตอร์
    $params = [
        'sender' => $senderId,
        'msisdn' => $phone,
        'message' => $message,
        'force' => $useUnicode ? 'unicode' : 'standard'
    ];
    
    // เพิ่ม API Key ถ้ามี
    if (!empty($apiKey)) {
        $params['key'] = $apiKey;
    }
    
    // เพิ่ม API Secret ถ้ามี
    if (!empty($apiSecret)) {
        $params['secret'] = $apiSecret;
    }
    
    // ส่งคำขอ HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errMsg = curl_error($ch);
    curl_close($ch);
    
    // ตรวจสอบข้อผิดพลาด
    if ($errNo) {
        throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $errMsg);
    }
    
    // แปลงผลลัพธ์เป็น JSON
    $result = json_decode($response, true);
    
    // ตรวจสอบผลลัพธ์
    if (!$result || !isset($result['status'])) {
        throw new Exception('ไม่สามารถแปลงผลลัพธ์เป็น JSON ได้: ' . $response);
    }
    
    if ($result['status'] !== 'success') {
        throw new Exception('การส่ง SMS ล้มเหลว: ' . ($result['message'] ?? 'ไม่ทราบสาเหตุ'));
    }
    
    return $result;
}

// ฟังก์ชันส่ง SMS ผ่าน ThaiBulkSMS
function sendThaiBulkSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode) {
    // ตรวจสอบว่ามีการกำหนด API URL หรือไม่
    if (empty($apiUrl)) {
        $apiUrl = 'https://api.thaibulksms.com/sms';
    }
    
    // ตรวจสอบว่าเบอร์โทรศัพท์มี 0 นำหน้าหรือไม่
    if (substr($phone, 0, 1) !== '0') {
        // ถ้าไม่มี 0 นำหน้า ให้เพิ่ม 0
        $phone = '0' . $phone;
    }
    
    // กำหนดพารามิเตอร์
    $params = [
        'key' => $apiKey,
        'secret' => $apiSecret,
        'msisdn' => $phone,
        'message' => $message,
        'sender' => $senderId
    ];
    
    // ถ้าใช้ Unicode
    if ($useUnicode) {
        $params['unicodevalue'] = '1';
    }
    
    // ส่งคำขอ HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errMsg = curl_error($ch);
    curl_close($ch);
    
    // ตรวจสอบข้อผิดพลาด
    if ($errNo) {
        throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $errMsg);
    }
    
    // แปลงผลลัพธ์เป็น JSON
    $result = json_decode($response, true);
    
    // ตรวจสอบผลลัพธ์
    if (!$result) {
        throw new Exception('ไม่สามารถแปลงผลลัพธ์เป็น JSON ได้: ' . $response);
    }
    
    return $result;
}

// ฟังก์ชันส่ง SMS ผ่าน Twilio
function sendTwilioSms($phone, $message, $accountSid, $authToken, $from) {
    // ตรวจสอบว่ามีการกำหนด Account SID และ Auth Token หรือไม่
    if (empty($accountSid) || empty($authToken)) {
        throw new Exception('กรุณาระบุ Account SID และ Auth Token');
    }
    
    // ตรวจสอบว่าเบอร์โทรศัพท์มีรูปแบบถูกต้องหรือไม่
    if (substr($phone, 0, 1) === '0') {
        // แปลงเป็นรูปแบบ +66
        $phone = '+66' . substr($phone, 1);
    }
    
    // URL ของ Twilio API
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    // กำหนดพารามิเตอร์
    $params = [
        'To' => $phone,
        'From' => $from,
        'Body' => $message
    ];
    
    // ส่งคำขอ HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    
    $response = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errMsg = curl_error($ch);
    curl_close($ch);
    
    // ตรวจสอบข้อผิดพลาด
    if ($errNo) {
        throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $errMsg);
    }
    
    // แปลงผลลัพธ์เป็น JSON
    $result = json_decode($response, true);
    
    // ตรวจสอบผลลัพธ์
    if (!$result) {
        throw new Exception('ไม่สามารถแปลงผลลัพธ์เป็น JSON ได้: ' . $response);
    }
    
    if (isset($result['error_code'])) {
        throw new Exception('การส่ง SMS ล้มเหลว: ' . ($result['error_message'] ?? 'ไม่ทราบสาเหตุ'));
    }
    
    return $result;
}

// ฟังก์ชันส่ง SMS ผ่านผู้ให้บริการที่กำหนดเอง
function sendCustomSms($phone, $message, $apiKey, $apiSecret, $apiUrl, $senderId, $useUnicode) {
    // ตรวจสอบว่ามีการกำหนด API URL หรือไม่
    if (empty($apiUrl)) {
        throw new Exception('กรุณาระบุ API URL');
    }
    
    // กำหนดพารามิเตอร์ (ส่วนนี้ต้องปรับตามผู้ให้บริการที่กำหนดเอง)
    $params = [
        'key' => $apiKey,
        'secret' => $apiSecret,
        'phone' => $phone,
        'message' => $message,
        'sender' => $senderId,
        'unicode' => $useUnicode ? '1' : '0'
    ];
    
    // ส่งคำขอ HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errMsg = curl_error($ch);
    curl_close($ch);
    
    // ตรวจสอบข้อผิดพลาด
    if ($errNo) {
        throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $errMsg);
    }
    
    // ลองแปลงผลลัพธ์เป็น JSON
    $result = json_decode($response, true);
    
    // หากแปลงไม่ได้ ให้ส่งผลลัพธ์เป็นข้อความ
    if (!$result) {
        return ['response' => $response];
    }
    
    return $result;
}

// ฟังก์ชันบันทึกข้อมูลการส่ง SMS
function logSmsSent($phone, $message, $provider, $result) {
    // เชื่อมต่อฐานข้อมูล
    $db = getDB();
    
    // รับ user_id ของแอดมิน (ถ้ามี)
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // ตรวจสอบว่ามีตาราง sms_logs หรือไม่
    try {
        // ลองสร้างตาราง sms_logs ถ้ายังไม่มี
        $db->exec("
            CREATE TABLE IF NOT EXISTS `sms_logs` (
              `log_id` int(11) NOT NULL AUTO_INCREMENT,
              `phone_number` varchar(20) NOT NULL,
              `message` text NOT NULL,
              `provider` varchar(50) NOT NULL,
              `result` text,
              `status` varchar(20) NOT NULL,
              `sent_by` int(11) DEFAULT NULL,
              `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`log_id`),
              KEY `sent_by` (`sent_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // แปลงผลลัพธ์เป็น JSON (หรือใช้ข้อความถ้าเป็น JSON อยู่แล้ว)
        $resultJson = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result;
        
        // บันทึกข้อมูลลงในตาราง
        $stmt = $db->prepare("
            INSERT INTO `sms_logs` 
            (`phone_number`, `message`, `provider`, `result`, `status`, `sent_by`) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $status = isset($result['status']) && $result['status'] === 'success' ? 'success' : 'pending';
        
        $stmt->execute([
            $phone,
            $message,
            $provider,
            $resultJson,
            $status,
            $userId
        ]);
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดแต่ไม่ขัดจังหวะฟังก์ชันหลัก
        error_log('Error logging SMS: ' . $e->getMessage());
    }
}
?>
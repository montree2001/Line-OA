<?php
/**
 * api/check_in.php - API สำหรับเช็คชื่อด้วย GPS
 */
session_start();
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ใช้งานส่วนนี้']);
    exit;
}

// รับข้อมูล POST เป็น JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id']) || !isset($data['method']) || !isset($data['lat']) || !isset($data['lng'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$student_id = $data['student_id'];
$method = $data['method'];
$lat = $data['lat'];
$lng = $data['lng'];

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ตรวจสอบว่าได้เช็คชื่อแล้วหรือยัง
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student_id, $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($today_attendance) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'คุณได้เช็คชื่อไปแล้วในวันนี้']);
        exit;
    }
    
    // ตรวจสอบว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time'
    ");
    $stmt->execute();
    $start_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '07:30';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '08:30';
    
    $current_time = date('H:i');
    
    if ($current_time < $start_time || $current_time > $end_time) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่อยู่ในช่วงเวลาเช็คชื่อ (' . $start_time . ' - ' . $end_time . ' น.)']);
        exit;
    }
    
    // ตรวจสอบตำแหน่ง GPS
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude'
    ");
    $stmt->execute();
    $school_lat = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude'
    ");
    $stmt->execute();
    $school_lng = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius'
    ");
    $stmt->execute();
    $gps_radius = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '100';
    
    // คำนวณระยะห่างระหว่างผู้ใช้กับโรงเรียน
    $distance = getDistance($lat, $lng, $school_lat, $school_lng);
    
    if ($distance > $gps_radius) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'คุณอยู่นอกรัศมีที่กำหนด (' . $gps_radius . ' เมตร) ระยะห่างปัจจุบัน: ' . round($distance) . ' เมตร'
        ]);
        exit;
    }
    
    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    if (!$current_academic_year_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    // บันทึกการเช็คชื่อ
    $stmt = $conn->prepare("
        INSERT INTO attendance (
            student_id, 
            academic_year_id, 
            date, 
            attendance_status,
            check_method, 
            location_lat, 
            location_lng, 
            check_time, 
            created_at
        )
        VALUES (?, ?, ?, 'present', 'GPS', ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $student_id,
        $current_academic_year_id,
        $today,
        $lat,
        $lng
    ]);
    
    // อัพเดทสถิติการเข้าแถวในตาราง student_academic_records
    $stmt = $conn->prepare("
        UPDATE student_academic_records 
        SET total_attendance_days = total_attendance_days + 1, 
            updated_at = NOW()
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student_id, $current_academic_year_id]);
    
    // คืนค่าสำเร็จ
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'เช็คชื่อสำเร็จ']);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    exit;
}

// ฟังก์ชันคำนวณระยะทางระหว่างจุดสองจุดบนพื้นโลก (Haversine formula)
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3; // รัศมีของโลกในหน่วยเมตร
    $φ1 = $lat1 * M_PI / 180;
    $φ2 = $lat2 * M_PI / 180;
    $Δφ = ($lat2 - $lat1) * M_PI / 180;
    $Δλ = ($lon2 - $lon1) * M_PI / 180;
    
    $a = sin($Δφ / 2) * sin($Δφ / 2) +
            cos($φ1) * cos($φ2) *
            sin($Δλ / 2) * sin($Δλ / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $R * $c; // ระยะทางในหน่วยเมตร
}
?>
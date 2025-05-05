<?php
/**
 * get_location.php - ไฟล์สำหรับดึงข้อมูลตำแหน่ง GPS ของนักเรียนและโรงเรียน
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}
 */
// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$map_id = isset($_GET['map_id']) ? str_replace('map-', '', $_GET['map_id']) : 0;
$attendance_id = intval($map_id);

if (!$attendance_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสการเช็คชื่อ']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลตำแหน่ง GPS ของการเช็คชื่อ
    $stmt = $conn->prepare("
        SELECT a.location_lat, a.location_lng, a.student_id, s.title, u.first_name, u.last_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE a.attendance_id = ?
    ");
    $stmt->execute([$attendance_id]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attendance) {
        throw new Exception('ไม่พบข้อมูลการเช็คชื่อ');
    }
    
    if (!$attendance['location_lat'] || !$attendance['location_lng']) {
        throw new Exception('ไม่พบข้อมูลตำแหน่ง GPS');
    }
    
    // ดึงพิกัด GPS ของโรงเรียน
    $stmt = $conn->prepare("
        SELECT 
            (SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude') AS school_lat,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude') AS school_lng,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius') AS radius
        FROM dual
    ");
    $stmt->execute();
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณระยะห่าง
    $distance = calculateDistance(
        $attendance['location_lat'], 
        $attendance['location_lng'], 
        $school['school_lat'], 
        $school['school_lng']
    );
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'latitude' => $attendance['location_lat'],
        'longitude' => $attendance['location_lng'],
        'school_lat' => $school['school_lat'],
        'school_lng' => $school['school_lng'],
        'radius' => $school['radius'],
        'distance' => round($distance),
        'student_name' => $attendance['title'] . $attendance['first_name'] . ' ' . $attendance['last_name'],
        'student_id' => $attendance['student_id']
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * คำนวณระยะห่างระหว่างพิกัด (Haversine formula)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // รัศมีโลกเป็นเมตร
    
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);
    
    $latDiff = $lat2Rad - $lat1Rad;
    $lonDiff = $lon2Rad - $lon1Rad;
    
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($lonDiff / 2) * sin($lonDiff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}
?>
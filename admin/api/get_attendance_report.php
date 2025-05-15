<?php
/**
 * get_attendance_report.php - API สำหรับดึงข้อมูลรายงานการเช็คชื่อเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบว่าเป็นการเรียกใช้ด้วย POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ต้องใช้ method POST']);
    exit;
}

// รับข้อมูล JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// ตรวจสอบข้อมูลที่ส่งมา
if (!isset($data['class_id']) || !isset($data['week_number']) || !isset($data['start_date']) || !isset($data['end_date'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$class_id = $data['class_id'];
$week_number = $data['week_number'];
$start_date = $data['start_date'];
$end_date = $data['end_date'];
$academic_year_id = $data['academic_year_id'] ?? null;

// ถ้าไม่มี academic_year_id ให้ดึงจากปีการศึกษาปัจจุบัน
if (!$academic_year_id) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'] ?? null;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลปีการศึกษา']);
        exit;
    }
}

// ดึงข้อมูลนักเรียนในชั้นเรียน
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name 
        FROM students s 
        JOIN users u ON s.user_id = u.user_id 
        WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
        ORDER BY s.student_code
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน']);
    exit;
}

// ดึงข้อมูลการเข้าแถวในช่วงวันที่กำหนด
foreach ($students as &$student) {
    try {
        $stmt = $db->prepare("
            SELECT date, attendance_status, remarks 
            FROM attendance 
            WHERE student_id = ? AND date BETWEEN ? AND ? AND academic_year_id = ?
        ");
        $stmt->execute([$student['student_id'], $start_date, $end_date, $academic_year_id]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบข้อมูลการเข้าแถวตามวัน
        $student['attendances'] = [];
        foreach ($attendances as $att) {
            $student['attendances'][$att['date']] = [
                'status' => $att['attendance_status'],
                'remarks' => $att['remarks']
            ];
        }
    } catch (PDOException $e) {
        // ข้ามไปยังนักเรียนคนถัดไปหากเกิดข้อผิดพลาด
        continue;
    }
}

// ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
try {
    $stmt = $db->prepare("
        SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary 
        FROM class_advisors ca 
        JOIN teachers t ON ca.teacher_id = t.teacher_id 
        WHERE ca.class_id = ? 
        ORDER BY ca.is_primary DESC
    ");
    $stmt->execute([$class_id]);
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $advisors = [];
}

// สร้างรายการวันในสัปดาห์
$week_days = [];
$current_date = new DateTime($start_date);
$end_date_dt = new DateTime($end_date);

while ($current_date <= $end_date_dt) {
    $week_days[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}

// ส่งข้อมูลกลับ
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'report_data' => [
        'students' => $students,
        'advisors' => $advisors,
        'week_days' => $week_days
    ]
]);
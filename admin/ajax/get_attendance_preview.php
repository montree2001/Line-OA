<?php
/**
 * ajax/get_attendance_preview.php - API สำหรับดึงข้อมูลตัวอย่างการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตั้งค่า headers สำหรับ cross-platform compatibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// เริ่ม session
session_start();

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'status' => 'error']);
    exit;
}
 */
// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

try {
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    // รับข้อมูลจาก GET parameters
    $search_type = $_GET['search_type'] ?? 'class';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $start_week = $_GET['start_week'] ?? '';
    $end_week = $_GET['end_week'] ?? '';
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($start_date) || empty($end_date) || empty($start_week) || empty($end_week)) {
        throw new Exception("ข้อมูลไม่ครบถ้วน");
    }
    
    $students = [];
    $class_info = null;
    
    if ($search_type === 'class') {
        // ค้นหาตามห้องเรียน
        $class_id = $_GET['class_id'] ?? '';
        if (empty($class_id)) {
            throw new Exception("กรุณาระบุรหัสห้องเรียน");
        }
        
        // ดึงข้อมูลห้องเรียน
        $query = "SELECT c.class_id, c.level, c.group_number, d.department_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE c.class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class_info) {
            throw new Exception("ไม่พบข้อมูลห้องเรียน");
        }
        
        // ดึงข้อมูลนักเรียนในห้อง
        $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                  CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id 
                  WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
                  ORDER BY s.student_code";
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // ค้นหาตามนักเรียน
        $search_input = $_GET['search_input'] ?? '';
        if (empty($search_input)) {
            throw new Exception("กรุณาระบุข้อมูลที่ต้องการค้นหา");
        }
        
        // ค้นหานักเรียนจากชื่อ นามสกุล หรือรหัสนักเรียน
        $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                  CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title,
                  c.class_id, c.level, c.group_number, d.department_name,
                  CONCAT(c.level, '/', c.group_number, ' ', d.department_name) as class_name
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id 
                  LEFT JOIN classes c ON s.current_class_id = c.class_id
                  LEFT JOIN departments d ON c.department_id = d.department_id
                  WHERE s.status = 'กำลังศึกษา' 
                  AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
                       OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)
                  ORDER BY s.student_code";
        
        $search_param = "%$search_input%";
        $stmt = $conn->prepare($query);
        $stmt->execute([$search_param, $search_param, $search_param, $search_param]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (empty($students)) {
        throw new Exception("ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่ระบุ");
    }
    
    // ดึงข้อมูลวันหยุด
    $query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $holidays_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $holidays = [];
    foreach ($holidays_result as $holiday) {
        $holidays[$holiday['holiday_date']] = $holiday['holiday_name'];
    }
    
    // ดึงข้อมูลการเข้าแถว
    $student_ids = array_column($students, 'student_id');
    $attendance_data = [];
    
    if (!empty($student_ids)) {
        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
        
        $query = "SELECT student_id, date, attendance_status FROM attendance 
                  WHERE student_id IN ({$placeholders}) 
                  AND academic_year_id = ? 
                  AND date BETWEEN ? AND ?";
        
        $query_params = array_merge($student_ids, [$academic_year['academic_year_id'], $start_date, $end_date]);
        
        $stmt = $conn->prepare($query);
        $stmt->execute($query_params);
        $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบข้อมูลเป็น [student_id][date] => status
        foreach ($attendance_results as $result) {
            $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
        }
    }
    
    // สร้างข้อมูลวันทำการ (จันทร์-ศุกร์)
    $week_days = [];
    $current_date = new DateTime($start_date);
    $end_report_date = new DateTime($end_date);
    
    $thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    while ($current_date <= $end_report_date) {
        $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
        
        // เฉพาะวันจันทร์ถึงศุกร์
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $date_str = $current_date->format('Y-m-d');
            $is_holiday = isset($holidays[$date_str]);
            
            $week_days[] = [
                'date' => $date_str,
                'day_name' => $thaiDayAbbrs[$day_of_week],
                'day_num' => $current_date->format('j'),
                'is_holiday' => $is_holiday,
                'holiday_name' => $is_holiday ? $holidays[$date_str] : null
            ];
        }
        
        $current_date->modify('+1 day');
    }
    
    // ส่งข้อมูลกลับ
    $response = [
        'status' => 'success',
        'students' => $students,
        'attendance_data' => $attendance_data,
        'week_days' => $week_days,
        'holidays' => $holidays,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'start_week' => $start_week,
        'end_week' => $end_week,
        'search_type' => $search_type
    ];
    
    if ($class_info) {
        $response['class_info'] = $class_info;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage(), 'status' => 'error'], JSON_UNESCAPED_UNICODE);
}
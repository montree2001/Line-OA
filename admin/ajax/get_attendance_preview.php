<?php
/**
 * ajax/get_attendance_preview.php - API สำหรับดึงข้อมูลการเข้าแถวเพื่อแสดงตัวอย่าง
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'status' => 'error']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

try {
    // ตรวจสอบว่ามีการส่งข้อมูลที่จำเป็นมาหรือไม่
    if (!isset($_GET['class_id']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
        throw new Exception("กรุณาระบุข้อมูลให้ครบถ้วน");
    }
    
    $class_id = $_GET['class_id'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    // ดึงข้อมูลห้องเรียน
    $query = "SELECT c.class_id, c.level, c.group_number, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        throw new Exception("ไม่พบข้อมูลห้องเรียนที่ระบุ");
    }
    
    // ดึงข้อมูลครูที่ปรึกษา
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name 
              FROM teachers t 
              JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
              WHERE ca.class_id = ? AND ca.is_primary = 1
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลนักเรียนในห้อง
    $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name 
              FROM students s 
              JOIN users u ON s.user_id = u.user_id 
              WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";
    
    // ถ้ามีการค้นหา ให้เพิ่มเงื่อนไขการค้นหา
    if (!empty($search_term)) {
        $query .= " AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    }
    
    $query .= " ORDER BY s.student_code";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($search_term)) {
        $search_param = "%$search_term%";
        $stmt->execute([$class_id, $search_param, $search_param, $search_param]);
    } else {
        $stmt->execute([$class_id]);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างอาเรย์วันที่สำหรับรายงาน (เฉพาะวันจันทร์-ศุกร์)
    $current_date = new DateTime($start_date);
    $end_report_date = new DateTime($end_date);
    
    $week_days = [];
    $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    // ดึงข้อมูลวันหยุด
    $query = "SELECT holiday_date, holiday_name FROM holidays 
              WHERE holiday_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $holidays = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    while ($current_date <= $end_report_date) {
        $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
        
        // เฉพาะวันจันทร์ถึงศุกร์
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $date_str = $current_date->format('Y-m-d');
            $is_holiday = isset($holidays[$date_str]);
            
            $week_days[] = [
                'date' => $date_str,
                'day_name' => $thaiDayAbbrs[$day_of_week],
                'day_full' => $thaiDays[$day_of_week],
                'day_num' => $current_date->format('j'),
                'is_holiday' => $is_holiday,
                'holiday_name' => $is_holiday ? $holidays[$date_str] : null
            ];
        }
        
        $current_date->modify('+1 day');
    }
    
    // ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
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
    
    // นับจำนวนนักเรียนชาย/หญิง
    $male_count = 0;
    $female_count = 0;
    
    foreach ($students as $student) {
        if ($student['title'] == 'นาย') {
            $male_count++;
        } else {
            $female_count++;
        }
    }
    
    // จัดเตรียมข้อมูลสำหรับส่งกลับ
    $result = [
        'class' => $class,
        'advisor' => $advisor,
        'students' => $students,
        'week_days' => $week_days,
        'attendance_data' => $attendance_data,
        'counts' => [
            'total' => count($students),
            'male' => $male_count,
            'female' => $female_count
        ],
        'academic_year' => $academic_year,
        'status' => 'success'
    ];
    
    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    // ส่งข้อความแจ้งข้อผิดพลาดกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'status' => 'error']);
}
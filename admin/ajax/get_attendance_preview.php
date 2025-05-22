<?php
/**
 * ajax/get_attendance_preview.php - API สำหรับแสดงตัวอย่างข้อมูลการเข้าแถวก่อนพิมพ์
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
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_GET['class_id']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
        throw new Exception("กรุณาระบุข้อมูลให้ครบถ้วน");
    }
    
    $class_id = $_GET['class_id'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    // ดึงข้อมูลห้องเรียน
    $query = "SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        throw new Exception("ไม่พบข้อมูลห้องเรียน");
    }
    
    // เตรียม query สำหรับดึงนักเรียน
    $query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                    CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
                    FROM students s 
                    JOIN users u ON s.user_id = u.user_id 
                    WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'";

    // เพิ่มเงื่อนไขค้นหาถ้ามี
    if (!empty($search)) {
        $query_students .= " AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_param = "%$search%";
        $stmt = $conn->prepare($query_students);
        $stmt->execute([$class_id, $search_param, $search_param, $search_param]);
    } else {
        $stmt = $conn->prepare($query_students);
        $stmt->execute([$class_id]);
    }

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_count = count($students);
    
    if ($total_count == 0) {
        throw new Exception("ไม่พบข้อมูลนักเรียนในห้องเรียนนี้");
    }
    
    // ดึงข้อมูลวันหยุด
    $query = "SELECT holiday_date, holiday_name FROM holidays 
             WHERE holiday_date BETWEEN ? AND ? 
             OR (is_repeating = 1 AND DATE_FORMAT(holiday_date, '%m-%d') 
                 BETWEEN DATE_FORMAT(?, '%m-%d') AND DATE_FORMAT(?, '%m-%d'))";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
    $holidays = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $holidays[$row['holiday_date']] = $row['holiday_name'];
    }
    
    // สร้างอาเรย์วันที่ (เฉพาะวันจันทร์-ศุกร์)
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $days = [];
    $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $thaiMonths = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    
    while ($current_date <= $end_date_obj) {
        $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
        
        // เฉพาะวันจันทร์ถึงศุกร์
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            $date_str = $current_date->format('Y-m-d');
            $is_holiday = isset($holidays[$date_str]);
            
            $days[] = [
                'date' => $date_str,
                'day_name' => $thaiDayAbbrs[$day_of_week],
                'day_full' => $thaiDays[$day_of_week],
                'day_num' => $current_date->format('j'),
                'month' => $thaiMonths[$current_date->format('m')],
                'year' => (int)$current_date->format('Y') + 543, // พ.ศ.
                'is_holiday' => $is_holiday,
                'holiday_name' => $is_holiday ? $holidays[$date_str] : null
            ];
        }
        
        $current_date->modify('+1 day');
    }
    
    // ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
    $student_ids = array_column($students, 'student_id');
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
        $attendance_data = [];
        foreach ($attendance_results as $result) {
            $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
        }
    } else {
        $attendance_data = [];
    }
    
    // สร้างข้อมูลครูที่ปรึกษา
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name 
              FROM teachers t 
              JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
              WHERE ca.class_id = ? AND ca.is_primary = 1
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณสัปดาห์
    $semester_start = new DateTime($academic_year['start_date']);
    $report_start = new DateTime($start_date);
    $report_end = new DateTime($end_date);
    
    $diff = $semester_start->diff($report_start);
    $days_diff = $diff->days;
    $start_week = floor($days_diff / 7) + 1;
    
    $diff = $semester_start->diff($report_end);
    $days_diff = $diff->days;
    $end_week = floor($days_diff / 7) + 1;
    
    // ส่งข้อมูลกลับเป็น JSON
    $response = [
        'status' => 'success',
        'class' => $class,
        'academic_year' => $academic_year,
        'students' => $students,
        'total_count' => $total_count,
        'week_days' => $days,
        'attendance_data' => $attendance_data,
        'advisor' => $advisor,
        'start_week' => $start_week,
        'end_week' => $end_week,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // ส่งข้อความแจ้งข้อผิดพลาดกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'status' => 'error']);
}
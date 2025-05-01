<?php
/**
 * get_calendar_data.php - API สำหรับดึงข้อมูลปฏิทินการเข้าแถว
 */

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// รับพารามิเตอร์
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if ($class_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสชั้นเรียน'
    ]);
    exit;
}

try {
    $db = getDB();
    
    // ดึงข้อมูลชั้นเรียน
    $class_query = "SELECT c.class_id, c.level, d.department_name, c.group_number, 
                   (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                   FROM classes c
                   JOIN departments d ON c.department_id = d.department_id
                   WHERE c.class_id = :class_id";
    
    $stmt = $db->prepare($class_query);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_data) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลชั้นเรียน'
        ]);
        exit;
    }
    
    // สร้างข้อมูลปฏิทิน
    $calendar_data = [];
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day = date('N', strtotime("$year-$month-01"));
    
    // วันเริ่มต้นในปฏิทิน (ถ้าวันที่ 1 ไม่ใช่วันจันทร์)
    $prev_month = $month - 1;
    $prev_year = $year;
    if ($prev_month < 1) {
        $prev_month = 12;
        $prev_year--;
    }
    $days_in_prev_month = cal_days_in_month(CAL_GREGORIAN, $prev_month, $prev_year);
    $start_day = $days_in_prev_month - $first_day + 2;
    
    // ดึงข้อมูลการเข้าแถวในเดือนที่เลือก
    $calendar_query = "SELECT 
                      a.date,
                      COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present_count,
                      COUNT(CASE WHEN a.attendance_status = 'absent' THEN 1 END) as absent_count,
                      COUNT(DISTINCT a.student_id) as total_count
                      FROM attendance a
                      JOIN students s ON a.student_id = s.student_id
                      WHERE s.current_class_id = :class_id
                      AND MONTH(a.date) = :month
                      AND YEAR(a.date) = :year
                      GROUP BY a.date";
    
    $stmt = $db->prepare($calendar_query);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->bindParam(':month', $month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    $calendar_data_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างตัวแปรสำหรับเก็บข้อมูลแยกตามวันที่
    $attendance_by_date = [];
    foreach ($calendar_data_db as $data) {
        $date = $data['date'];
        $day = (int)date('j', strtotime($date));
        $attendance_by_date[$day] = [
            'present' => $data['present_count'],
            'absent' => $data['absent_count'],
            'total' => $data['total_count'],
            'percentage' => $data['total_count'] > 0 ? round(($data['present_count'] / $data['total_count']) * 100, 1) : 0
        ];
    }
    
    // สร้างข้อมูลวันที่ก่อนหน้าเดือนปัจจุบัน
    for ($i = 0; $i < $first_day - 1; $i++) {
        $calendar_data[] = [
            'day' => $start_day + $i,
            'month' => $prev_month,
            'year' => $prev_year,
            'current_month' => false,
            'present' => 0,
            'absent' => 0,
            'total' => $class_data['total_students'] ?? 0,
            'percentage' => 0,
            'is_school_day' => false
        ];
    }
    
    // สร้างข้อมูลวันที่ในเดือนปัจจุบัน
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = "$year-$month-$day";
        $day_of_week = date('N', strtotime($date));
    
        // สมมติว่าวันเสาร์-อาทิตย์ไม่ใช่วันเรียน
        $is_school_day = ($day_of_week < 6);
    
        // ดึงข้อมูลการเช็คชื่อสำหรับวันนี้ (ถ้ามี)
        $attendance = $attendance_by_date[$day] ?? null;
    
        if ($attendance) {
            $present = $attendance['present'];
            $absent = $attendance['absent'];
            $total = $attendance['total'];
            $percentage = $attendance['percentage'];
        } else {
            $present = 0;
            $absent = 0;
            $total = $class_data['total_students'] ?? 0;
            $percentage = 0;
        }
    
        $calendar_data[] = [
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'current_month' => true,
            'present' => $present,
            'absent' => $absent,
            'total' => $total,
            'percentage' => $percentage,
            'is_school_day' => $is_school_day
        ];
    }
    
    // สร้างข้อมูลวันที่หลังเดือนปัจจุบัน (เพื่อให้ครบ 42 ช่อง หรือ 6 สัปดาห์)
    $next_month = $month + 1;
    $next_year = $year;
    if ($next_month > 12) {
        $next_month = 1;
        $next_year++;
    }
    
    $remaining_days = 42 - count($calendar_data);
    for ($day = 1; $day <= $remaining_days; $day++) {
        $calendar_data[] = [
            'day' => $day,
            'month' => $next_month,
            'year' => $next_year,
            'current_month' => false,
            'present' => 0,
            'absent' => 0,
            'total' => $class_data['total_students'] ?? 0,
            'percentage' => 0,
            'is_school_day' => false
        ];
    }
    
    // ชื่อเดือนภาษาไทย
    $thai_month_names = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    // ข้อมูลสรุป
    $monthly_summary = [
        'month' => $month,
        'year' => $year,
        'month_name' => $thai_month_names[$month],
        'month_name_full' => $thai_month_names[$month] . ' ' . ($year + 543),
        'days_in_month' => $days_in_month,
        'school_days' => array_reduce($calendar_data, function($carry, $day) {
            if ($day['current_month'] && $day['is_school_day']) {
                return $carry + 1;
            }
            return $carry;
        }, 0),
        'attendance_days' => count($calendar_data_db),
        'class_students' => $class_data['total_students']
    ];
    
    // รวมข้อมูลและส่งกลับ
    $result = [
        'success' => true,
        'calendar_data' => $calendar_data,
        'summary' => $monthly_summary
    ];
    
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
    
    // บันทึกข้อผิดพลาด
    error_log('Error in get_calendar_data.php: ' . $e->getMessage());
}
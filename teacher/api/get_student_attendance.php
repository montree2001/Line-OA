<?php
/**
 * get_student_attendance.php - API ดึงข้อมูลประวัติการเข้าแถวของนักเรียนรายบุคคลแบบละเอียด
 * 
 * API นี้ใช้สำหรับดึงข้อมูลประวัติการเข้าแถวของนักเรียนรายบุคคลแบบละเอียด
 * รองรับการดึงข้อมูลตามช่วงเวลา และการแบ่งหน้า (pagination)
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
require_once '../../lib/functions.php';

// ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นหรือไม่
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสนักเรียน'
    ]);
    exit;
}

// รับพารามิเตอร์
$student_id = intval($_GET['student_id']);
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 30; // จำนวนรายการต่อหน้า
$offset = ($page - 1) * $items_per_page;

try {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $student_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                      (SELECT COUNT(*) + 1
                       FROM students
                       WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as student_number,
                      c.level, d.department_name, c.group_number, d.department_code,
                      CONCAT(c.level, '/', d.department_code, '/', c.group_number) as class_name
                      FROM students s
                      JOIN users u ON s.user_id = u.user_id
                      JOIN classes c ON s.current_class_id = c.class_id
                      JOIN departments d ON c.department_id = d.department_id
                      WHERE s.student_id = :student_id";
    
    $stmt = $db->prepare($student_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลนักเรียน'
        ]);
        exit;
    }
    
    // สร้างข้อมูลนักเรียนที่จะส่งกลับ
    $student_info = [
        'id' => $student['student_id'],
        'code' => $student['student_code'],
        'name' => ($student['title'] ? $student['title'] : '') . $student['first_name'] . ' ' . $student['last_name'],
        'number' => $student['student_number'],
        'class' => $student['class_name'],
        'level' => $student['level'],
        'department' => $student['department_name'],
        'group' => $student['group_number'],
        'profile_picture' => $student['profile_picture']
    ];
    
    // สร้าง WHERE clause สำหรับการกรองตามเดือนและปี
    $where_conditions = ["a.student_id = :student_id"];
    $params = [':student_id' => $student_id];
    
    if ($month !== null) {
        $where_conditions[] = "MONTH(a.date) = :month";
        $params[':month'] = $month;
    }
    
    if ($year !== null) {
        $where_conditions[] = "YEAR(a.date) = :year";
        $params[':year'] = $year;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // นับจำนวนรายการทั้งหมด (สำหรับ pagination)
    $count_query = "SELECT COUNT(*) as total
                   FROM attendance a
                   WHERE $where_clause";
    
    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page);
    
    // ดึงประวัติการเข้าแถว
    $history_query = "SELECT a.attendance_id, a.date, a.attendance_status, a.check_time, a.check_method,
                     a.location_lat, a.location_lng, a.photo_url, a.pin_code, a.remarks,
                     u.first_name, u.last_name, t.title as checker_title
                     FROM attendance a
                     LEFT JOIN users u ON a.checker_user_id = u.user_id
                     LEFT JOIN teachers t ON u.user_id = t.user_id
                     WHERE $where_clause
                     ORDER BY a.date DESC, a.check_time DESC
                     LIMIT :offset, :limit";
    
    $stmt = $db->prepare($history_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างสรุปการเข้าแถว
    $summary_query = "SELECT 
                     COUNT(DISTINCT a.date) as total_days,
                     SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                     SUM(CASE WHEN a.attendance_status = 'late' THEN 1 ELSE 0 END) as late_days,
                     SUM(CASE WHEN a.attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                     SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                     MAX(CASE WHEN a.attendance_status = 'absent' THEN a.date ELSE NULL END) as last_absent_date
                     FROM attendance a
                     WHERE $where_clause";
    
    $stmt = $db->prepare($summary_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณเปอร์เซ็นต์การเข้าแถว
    $total_days = $summary['total_days'] ?: 0;
    $present_days = $summary['present_days'] ?: 0;
    $late_days = $summary['late_days'] ?: 0;
    $leave_days = $summary['leave_days'] ?: 0;
    $absent_days = $summary['absent_days'] ?: 0;
    
    $attendance_percentage = 0;
    if ($total_days > 0) {
        $attendance_percentage = round((($present_days + $late_days) / $total_days) * 100, 1);
    }
    
    $attendance_status = 'danger';
    if ($attendance_percentage >= 80) {
        $attendance_status = 'good';
    } else if ($attendance_percentage >= 70) {
        $attendance_status = 'warning';
    }
    
    // แปลงรายการประวัติให้อยู่ในรูปแบบที่ต้องการ
    $attendance_history = [];
    foreach ($attendance_records as $record) {
        // แปลงสถานะเป็นภาษาไทย
        $status_text = '';
        $status_class = '';
        
        switch ($record['attendance_status']) {
            case 'present':
                $status_text = 'มาเรียน';
                $status_class = 'present';
                break;
            case 'late':
                $status_text = 'มาสาย';
                $status_class = 'late';
                break;
            case 'leave':
                $status_text = 'ลา';
                $status_class = 'leave';
                break;
            case 'absent':
                $status_text = 'ขาดเรียน';
                $status_class = 'absent';
                break;
            default:
                $status_text = 'ไม่ระบุ';
                $status_class = '';
        }
        
        // แปลงวิธีการเช็คชื่อเป็นภาษาไทย
        $method_text = '';
        switch ($record['check_method']) {
            case 'GPS':
                $method_text = 'GPS';
                break;
            case 'QR_Code':
                $method_text = 'QR Code';
                break;
            case 'PIN':
                $method_text = 'รหัส PIN';
                break;
            case 'Manual':
                $method_text = 'ครู';
                break;
            default:
                $method_text = $record['check_method'];
        }
        
        // แปลงวันที่เป็นรูปแบบไทย
        $date = new DateTime($record['date']);
        $day_of_week = [
            0 => 'อาทิตย์',
            1 => 'จันทร์',
            2 => 'อังคาร',
            3 => 'พุธ',
            4 => 'พฤหัสบดี',
            5 => 'ศุกร์',
            6 => 'เสาร์'
        ];
        $month_names = [
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
        
        $weekday = $day_of_week[$date->format('w')];
        $day = $date->format('j');
        $month = $month_names[(int)$date->format('n')];
        $year = $date->format('Y') + 543;
        
        $thai_date = "วัน$weekday ที่ $day $month $year";
        $short_date = "$day $month " . substr($year, 2, 2);
        
        // สร้างข้อมูลผู้เช็คชื่อ
        $checker_name = '-';
        if ($record['first_name']) {
            $checker_name = ($record['checker_title'] ? $record['checker_title'] . ' ' : '') . 
                            $record['first_name'] . ' ' . $record['last_name'];
        }
        
        // เพิ่มข้อมูลลงในรายการประวัติ
        $attendance_history[] = [
            'id' => $record['attendance_id'],
            'date' => $record['date'],
            'thai_date' => $thai_date,
            'short_date' => $short_date,
            'weekday' => $weekday,
            'status' => $record['attendance_status'],
            'status_text' => $status_text,
            'status_class' => $status_class,
            'time' => substr($record['check_time'], 0, 5),
            'method' => $method_text,
            'original_method' => $record['check_method'],
            'location_lat' => $record['location_lat'],
            'location_lng' => $record['location_lng'],
            'photo_url' => $record['photo_url'],
            'pin_code' => $record['pin_code'],
            'remarks' => $record['remarks'],
            'checker_name' => $checker_name
        ];
    }
    
    // สร้างข้อมูลสำหรับส่งกลับ
    $result = [
        'success' => true,
        'student' => $student_info,
        'summary' => [
            'total_days' => $total_days,
            'present_days' => $present_days,
            'late_days' => $late_days,
            'leave_days' => $leave_days,
            'absent_days' => $absent_days,
            'attendance_days' => $present_days + $late_days,
            'attendance_percentage' => $attendance_percentage,
            'attendance_status' => $attendance_status,
            'last_absent_date' => $summary['last_absent_date'] 
                                 ? date('d/m/Y', strtotime($summary['last_absent_date'])) 
                                 : null
        ],
        'history' => $attendance_history,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'items_per_page' => $items_per_page
        ]
    ];
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
    
    // บันทึกข้อผิดพลาด
    error_log('Error in get_student_attendance.php: ' . $e->getMessage());
}
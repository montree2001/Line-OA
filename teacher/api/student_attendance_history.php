<?php
/**
 * student_attendance_history.php - API สำหรับดึงประวัติการเข้าแถวของนักเรียน
 */

// เริ่มต้น session และตรวจสอบการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นหรือไม่
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสนักเรียน']);
    exit;
}

// เชื่อมต่อกับฐานข้อมูล
require_once '../../db_connect.php';
try {
    $db = getDB();

    // ดึงข้อมูลนักเรียน
    $student_id = intval($_GET['student_id']);
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

    // ดึงข้อมูลพื้นฐานของนักเรียน
    $student_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                     (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as student_number,
                     c.level, d.department_name, c.group_number
                     FROM students s
                     JOIN users u ON s.user_id = u.user_id
                     JOIN classes c ON s.current_class_id = c.class_id
                     JOIN departments d ON c.department_id = d.department_id
                     WHERE s.student_id = :student_id";

    $stmt = $db->prepare($student_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }

    // สรุปข้อมูลการเข้าแถว
    $summary_query = "SELECT 
                     COUNT(DISTINCT a.date) as total_days,
                     SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                     SUM(CASE WHEN a.attendance_status = 'late' THEN 1 ELSE 0 END) as late_days,
                     SUM(CASE WHEN a.attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                     SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                     ROUND((SUM(CASE WHEN a.attendance_status IN ('present', 'late') THEN 1 ELSE 0 END) * 100.0) / 
                            NULLIF(COUNT(DISTINCT a.date), 0), 1) as attendance_percentage,
                     MAX(CASE WHEN a.attendance_status = 'absent' THEN a.date ELSE NULL END) as last_absent_date
                     FROM attendance a
                     WHERE a.student_id = :student_id";

    // เพิ่มเงื่อนไขการกรองตามเดือนและปีถ้ามีการระบุ
    $params = [':student_id' => $student_id];
    
    if (isset($_GET['month']) && isset($_GET['year'])) {
        $summary_query .= " AND MONTH(a.date) = :month AND YEAR(a.date) = :year";
        $params[':month'] = $month;
        $params[':year'] = $year;
    } else if (isset($_GET['year'])) {
        $summary_query .= " AND YEAR(a.date) = :year";
        $params[':year'] = $year;
    }

    $stmt = $db->prepare($summary_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // ดึงประวัติการเข้าแถวรายวัน
    $history_query = "SELECT 
                     a.attendance_id,
                     a.date,
                     a.attendance_status,
                     a.check_time,
                     a.check_method,
                     a.remarks,
                     a.checker_user_id,
                     CONCAT(COALESCE(u.title, ''), u.first_name, ' ', u.last_name) as checker_name
                     FROM attendance a
                     LEFT JOIN users u ON a.checker_user_id = u.user_id
                     WHERE a.student_id = :student_id";
                     
    // เพิ่มเงื่อนไขการกรองตามเดือนและปีถ้ามีการระบุ
    if (isset($_GET['month']) && isset($_GET['year'])) {
        $history_query .= " AND MONTH(a.date) = :month AND YEAR(a.date) = :year";
    } else if (isset($_GET['year'])) {
        $history_query .= " AND YEAR(a.date) = :year";
    }
    
    $history_query .= " ORDER BY a.date DESC, a.check_time DESC LIMIT 30";
    
    $stmt = $db->prepare($history_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลที่ได้ให้อยู่ในรูปแบบที่เหมาะสม
    $attendance_history = [];
    foreach ($history_data as $record) {
        // แปลงสถานะภาษาอังกฤษให้เป็นภาษาไทย
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
        
        // แปลงวิธีการเช็คชื่อภาษาอังกฤษให้เป็นภาษาไทย
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
        
        // แปลงวันที่ให้เป็นรูปแบบไทย
        $date_obj = new DateTime($record['date']);
        $thai_day = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $thai_month = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        $day_of_week = $thai_day[$date_obj->format('w')]; // วันในสัปดาห์
        $day = $date_obj->format('j'); // วันที่
        $month = $thai_month[(int)$date_obj->format('n')]; // เดือน
        $year = $date_obj->format('Y') + 543; // ปี พ.ศ.
        
        $thai_date = "$day_of_week ที่ $day $month $year";
        
        $attendance_history[] = [
            'id' => $record['attendance_id'],
            'date' => $record['date'],
            'thai_date' => $thai_date,
            'status' => $record['attendance_status'],
            'status_text' => $status_text,
            'status_class' => $status_class,
            'time' => substr($record['check_time'], 0, 5), // แสดงแค่ชั่วโมงและนาที
            'method' => $method_text,
            'remarks' => $record['remarks'],
            'checker_name' => $record['checker_name'] ?: 'ไม่ระบุ'
        ];
    }
    
    // เตรียมข้อมูลเพื่อส่งกลับ
    $response = [
        'success' => true,
        'student' => [
            'id' => $student_data['student_id'],
            'code' => $student_data['student_code'],
            'name' => ($student_data['title'] ?: '') . $student_data['first_name'] . ' ' . $student_data['last_name'],
            'number' => $student_data['student_number'],
            'class' => $student_data['level'] . '/' . $student_data['department_name'] . '/' . $student_data['group_number'],
            'profile_picture' => $student_data['profile_picture']
        ],
        'summary' => [
            'total_days' => $summary_data['total_days'] ?: 0,
            'present_days' => $summary_data['present_days'] ?: 0,
            'late_days' => $summary_data['late_days'] ?: 0,
            'leave_days' => $summary_data['leave_days'] ?: 0,
            'absent_days' => $summary_data['absent_days'] ?: 0,
            'attendance_percentage' => $summary_data['attendance_percentage'] ?: 0,
            'last_absent_date' => $summary_data['last_absent_date'] ? date('d/m/Y', strtotime($summary_data['last_absent_date'])) : '-'
        ],
        'history' => $attendance_history
    ];
    
    // กำหนดสถานะการเข้าแถว (good, warning, danger)
    $attendance_percentage = $summary_data['attendance_percentage'] ?: 0;
    if ($attendance_percentage >= 80) {
        $response['summary']['status'] = 'good';
    } elseif ($attendance_percentage >= 70) {
        $response['summary']['status'] = 'warning';
    } else {
        $response['summary']['status'] = 'danger';
    }

    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
    
    // บันทึกข้อผิดพลาดลงในไฟล์ log
    error_log('Database error in student_attendance_history.php: ' . $e->getMessage());
}
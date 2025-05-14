<?php
// api/student_attendance_history.php - API ดึงข้อมูลประวัติการเข้าแถวของนักเรียน

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึงข้อมูล']);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบพารามิเตอร์
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุรหัสนักเรียน']);
    exit;
}

// รับพารามิเตอร์อื่นๆ (ถ้ามี)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 30; // จำนวนรายการต่อหน้า
$offset = ($page - 1) * $items_per_page;








try {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $student_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                      c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) as class_name,
                      (SELECT COUNT(*) + 1 FROM students 
                       WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number
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
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ดึงข้อมูลประวัติการเข้าแถว
    $history_query = "SELECT a.attendance_id, a.date, a.attendance_status, a.check_method, a.check_time, 
                     a.remarks, a.photo_url, u.first_name, u.last_name
                     FROM attendance a
                     LEFT JOIN users u ON a.checker_user_id = u.user_id
                     WHERE a.student_id = :student_id ";
    
    // เพิ่มเงื่อนไขการกรองตามเดือนและปี (ถ้ามี)
    if ($month > 0) {
        $history_query .= "AND MONTH(a.date) = :month ";
    }
    
    if ($year > 0) {
        $history_query .= "AND YEAR(a.date) = :year ";
    }
    
    $history_query .= "ORDER BY a.date DESC, a.check_time DESC 
                      LIMIT :offset, :items_per_page";
    
    $stmt = $db->prepare($history_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    
    if ($month > 0) {
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
    }
    
    if ($year > 0) {
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณสรุปการเข้าแถว
    $summary_query = "SELECT 
                     COUNT(DISTINCT date) as total_days,
                     SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                     SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_days,
                     SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                     SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_days
                     FROM attendance 
                     WHERE student_id = :student_id ";
    
    // เพิ่มเงื่อนไขการกรองตามเดือนและปี (ถ้ามี)
    if ($month > 0) {
        $summary_query .= "AND MONTH(date) = :month ";
    }
    
    if ($year > 0) {
        $summary_query .= "AND YEAR(date) = :year ";
    }
    
    $stmt = $db->prepare($summary_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    
    if ($month > 0) {
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
    }
    
    if ($year > 0) {
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // แปลงข้อมูลประวัติการเข้าแถว
    $history = [];
    foreach ($history_data as $record) {
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
        
        // แปลงวิธีการเช็คเป็นภาษาไทย
        $method = '';
        switch ($record['check_method']) {
            case 'GPS':
                $method = 'GPS';
                break;
            case 'QR_Code':
                $method = 'QR Code';
                break;
            case 'PIN':
                $method = 'รหัส PIN';
                break;
            case 'Manual':
                $method = 'ครูเช็ค';
                break;
            default:
                $method = $record['check_method'];
        }
        
        // แปลงวันที่เป็นรูปแบบไทย
        $date = date_create($record['date']);
        $thai_date = date_format($date, 'd/m/') . (date_format($date, 'Y') + 543);
        
        $history[] = [
            'id' => $record['attendance_id'],
            'date' => $record['date'],
            'thai_date' => $thai_date,
            'status' => $record['attendance_status'],
            'status_text' => $status_text,
            'status_class' => $status_class,
            'time' => substr($record['check_time'], 0, 5),
            'method' => $method,
            'remarks' => $record['remarks'] ?? '-',
            'checker' => $record['first_name'] ? $record['first_name'] . ' ' . $record['last_name'] : '-'
        ];
    }
    
    // คำนวณเปอร์เซ็นต์การเข้าแถว
    $total_days = $summary['total_days'] ?: 0;
    $present_days = $summary['present_days'] ?: 0;
    $late_days = $summary['late_days'] ?: 0;
    $leave_days = $summary['leave_days'] ?: 0;
    $absent_days = $summary['absent_days'] ?: 0;
    
    $attendance_days = $present_days + $late_days;
    $attendance_percentage = $total_days > 0 ? round(($attendance_days / $total_days) * 100, 1) : 0;
    
    // กำหนดสถานะตามเปอร์เซ็นต์
    $status = 'danger';
    if ($attendance_percentage >= 80) {
        $status = 'good';
    } elseif ($attendance_percentage >= 70) {
        $status = 'warning';
    }
    
    // สร้างข้อมูลสำหรับส่งกลับ
    $result = [
        'success' => true,
        'student' => [
            'id' => $student['student_id'],
            'code' => $student['student_code'],
            'name' => ($student['title'] ? $student['title'] . ' ' : '') . $student['first_name'] . ' ' . $student['last_name'],
            'number' => $student['number'],
            'class' => $student['class_name'],
            'profile_picture' => $student['profile_picture']
        ],
        'summary' => [
            'total_days' => $total_days,
            'present_days' => $present_days,
            'late_days' => $late_days,
            'leave_days' => $leave_days,
            'absent_days' => $absent_days,
            'attendance_percentage' => $attendance_percentage,
            'status' => $status
        ],
        'history' => $history
    ];










    
    // ส่งข้อมูลกลับ
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

try {
    // เดิม: หลังจากดึงข้อมูลนักเรียนและประวัติการเข้าแถว

    // ดึงข้อมูลกิจกรรมที่เข้าร่วม
    $activities_query = "SELECT 
                        a.activity_id, a.activity_name, a.activity_date, aa.attendance_status,
                        a.description, a.activity_location
                        FROM activities a
                        LEFT JOIN activity_attendance aa ON a.activity_id = aa.activity_id AND aa.student_id = :student_id
                        WHERE a.academic_year_id = :academic_year_id
                        ORDER BY a.activity_date DESC";

    $stmt = $db->prepare($activities_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $activities_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลกิจกรรม
    $activities = [];
    foreach ($activities_data as $activity) {
        // แปลงวันที่เป็นรูปแบบไทย
        $activity_date = date_create($activity['activity_date']);
        $thai_date = date_format($activity_date, 'd/m/') . (date_format($activity_date, 'Y') + 543);
        
        // แปลงสถานะเป็นภาษาไทย
        $status_text = $activity['attendance_status'] === 'present' ? 'เข้าร่วม' : 'ไม่เข้าร่วม';
        $status_class = $activity['attendance_status'] === 'present' ? 'present' : 'absent';
        
        $activities[] = [
            'id' => $activity['activity_id'],
            'name' => $activity['activity_name'],
            'date' => $activity['activity_date'],
            'thai_date' => $thai_date,
            'status' => $activity['attendance_status'] ?? 'absent',
            'status_text' => $status_text,
            'status_class' => $status_class,
            'location' => $activity['activity_location'],
            'description' => $activity['description']
        ];
    }

    // เพิ่มข้อมูลกิจกรรมลงในผลลัพธ์
    $result['activities'] = $activities;
    
    // ตามด้วยการส่งข้อมูลกลับ
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    // จัดการข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
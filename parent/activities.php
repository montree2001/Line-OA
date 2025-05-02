<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าแสดงกิจกรรมทั้งหมดของนักเรียนในความดูแลของผู้ปกครอง
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - กิจกรรมของนักเรียน';
$current_page = 'activities';
$extra_css = [
    'assets/css/parent-activities.css'
];
$extra_js = [
    'assets/js/parent-activities.js'
];

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ปกครอง
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // ถ้ายังไม่ได้ลงทะเบียนเป็นผู้ปกครอง ให้ไปที่หน้าลงทะเบียน
    header('Location: register.php');
    exit;
} else {
    $parent_data = $result->fetch_assoc();
    $parent_id = $parent_data['parent_id'];
}
$stmt->close();

// กำหนดค่าตัวกรอง
$filter_student = isset($_GET['student']) ? intval($_GET['student']) : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_period = isset($_GET['period']) ? $_GET['period'] : 'month';
$filter_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 20; // จำนวนรายการต่อหน้า

// ฟังก์ชันดึงข้อมูลนักเรียนในความดูแล
function getStudents($conn, $parent_id) {
    $students = [];
    
    $sql = "SELECT s.student_id, s.student_code, s.title AS student_title, 
               u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name
        FROM parent_student_relation psr
        JOIN students s ON psr.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
        ORDER BY u.first_name, u.last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['student_title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        $class_name = isset($row['level']) ? $row['level'] . '/' . $row['group_number'] : 'ไม่ระบุชั้นเรียน';
        
        $students[] = [
            'id' => $row['student_id'],
            'student_code' => $row['student_code'],
            'name' => $full_name,
            'class' => $class_name,
            'department' => $row['department_name'] ?? 'ไม่ระบุแผนก'
        ];
    }
    $stmt->close();
    
    return $students;
}

// ฟังก์ชันดึงข้อมูลกิจกรรมทั้งหมด
function getActivities($conn, $parent_id, $filter_student, $filter_type, $filter_period, $page, $items_per_page) {
    $activities = [];
    $total_items = 0;
    $offset = ($page - 1) * $items_per_page;
    
    // สร้างเงื่อนไข WHERE ตามตัวกรอง
    $where_conditions = ["psr.parent_id = ?"]; // เงื่อนไขพื้นฐาน
    $params = [$parent_id]; // พารามิเตอร์พื้นฐาน
    $param_types = "i"; // ประเภทพารามิเตอร์พื้นฐาน
    
    // กรองตามนักเรียน
    if ($filter_student > 0) {
        $where_conditions[] = "a.student_id = ?";
        $params[] = $filter_student;
        $param_types .= "i";
    }
    
    // กรองตามประเภทกิจกรรม
    if ($filter_type !== 'all') {
        switch ($filter_type) {
            case 'present':
                $where_conditions[] = "a.attendance_status = 'present'";
                break;
            case 'absent':
                $where_conditions[] = "a.attendance_status = 'absent'";
                break;
            case 'late':
                $where_conditions[] = "a.attendance_status = 'late'";
                break;
            case 'leave':
                $where_conditions[] = "a.attendance_status = 'leave'";
                break;
        }
    }
    
    // กรองตามช่วงเวลา
    $current_date = date('Y-m-d');
    switch ($filter_period) {
        case 'today':
            $where_conditions[] = "DATE(a.date) = ?";
            $params[] = $current_date;
            $param_types .= "s";
            break;
        case 'week':
            $start_of_week = date('Y-m-d', strtotime('this week monday', strtotime($current_date)));
            $where_conditions[] = "a.date >= ?";
            $params[] = $start_of_week;
            $param_types .= "s";
            break;
        case 'month':
            $start_of_month = date('Y-m-01');
            $where_conditions[] = "a.date >= ?";
            $params[] = $start_of_month;
            $param_types .= "s";
            break;
        case 'semester':
            // ดึงข้อมูลภาคเรียนปัจจุบัน
            $semester_query = "SELECT start_date FROM academic_years WHERE is_active = 1 LIMIT 1";
            $semester_result = $conn->query($semester_query);
            if ($semester_result && $semester_result->num_rows > 0) {
                $semester_data = $semester_result->fetch_assoc();
                $start_of_semester = $semester_data['start_date'];
                $where_conditions[] = "a.date >= ?";
                $params[] = $start_of_semester;
                $param_types .= "s";
            }
            break;
    }
    
    // รวมเงื่อนไข WHERE
    $where_clause = implode(' AND ', $where_conditions);
    
    // คำนวณจำนวนรายการทั้งหมด
    $count_sql = "SELECT COUNT(*) as total 
                 FROM attendance a
                 JOIN students s ON a.student_id = s.student_id
                 JOIN users u ON s.user_id = u.user_id
                 JOIN parent_student_relation psr ON s.student_id = psr.student_id
                 WHERE $where_clause";
                 
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        $count_stmt->bind_param($param_types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_data = $count_result->fetch_assoc();
        $total_items = $total_data['total'];
        $count_stmt->close();
    }
    
    // SQL หลักเพื่อดึงข้อมูลกิจกรรม
    $sql = "SELECT a.attendance_id, a.student_id, a.date, a.check_time, a.attendance_status,
                 a.check_method, a.remarks, 
                 s.title, u.first_name, u.last_name,
                 c.level, c.group_number, d.department_name,
                 DATE_FORMAT(a.date, '%d-%m-%Y') as formatted_date,
                 DATE_FORMAT(a.check_time, '%H:%i') as formatted_time,
                 CONCAT(DATE_FORMAT(a.date, '%d-%m-%Y'), ' ', DATE_FORMAT(a.check_time, '%H:%i')) as full_datetime,
                 UNIX_TIMESTAMP(CONCAT(a.date, ' ', a.check_time)) as timestamp,
                 CASE 
                     WHEN a.attendance_status = 'present' THEN 'เข้าแถว'
                     WHEN a.attendance_status = 'absent' THEN 'ขาดแถว'
                     WHEN a.attendance_status = 'late' THEN 'มาสาย'
                     WHEN a.attendance_status = 'leave' THEN 'ลา'
                     ELSE 'ไม่ทราบสถานะ'
                 END as status_text,
                 CASE 
                     WHEN a.check_method = 'GPS' THEN 'ระบบ GPS'
                     WHEN a.check_method = 'QR_Code' THEN 'สแกน QR Code'
                     WHEN a.check_method = 'PIN' THEN 'รหัส PIN'
                     WHEN a.check_method = 'Manual' THEN 'ครูเช็คชื่อ'
                     ELSE 'ไม่ทราบวิธี'
                 END as method_text
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            JOIN parent_student_relation psr ON s.student_id = psr.student_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE $where_clause
            ORDER BY a.date DESC, a.check_time DESC
            LIMIT ?, ?";
    
    $sql_params = $params;
    $sql_params[] = $offset;
    $sql_params[] = $items_per_page;
    $sql_param_types = $param_types . "ii";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($sql_param_types, ...$sql_params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
            
            // กำหนดประเภทกิจกรรมและไอคอนตามสถานะการเข้าแถว
            $type = '';
            $icon = '';
            $status_class = '';
            
            switch ($row['attendance_status']) {
                case 'present':
                    $type = 'check-in';
                    $icon = 'check_circle';
                    $status_class = 'present';
                    break;
                case 'absent':
                    $type = 'absent';
                    $icon = 'cancel';
                    $status_class = 'absent';
                    break;
                case 'late':
                    $type = 'late';
                    $icon = 'watch_later';
                    $status_class = 'late';
                    break;
                case 'leave':
                    $type = 'leave';
                    $icon = 'event_busy';
                    $status_class = 'leave';
                    break;
                default:
                    $type = 'other';
                    $icon = 'info';
                    $status_class = 'unknown';
            }
            
            // กำหนดข้อความตามช่วงเวลา
            $now = time();
            $activity_time = strtotime($row['date'] . ' ' . $row['check_time']);
            $diff = $now - $activity_time;
            
            $time_text = '';
            
            if ($diff < 60) {
                $time_text = 'เมื่อสักครู่';
            } elseif ($diff < 3600) {
                $minutes = floor($diff / 60);
                $time_text = $minutes . ' นาทีที่แล้ว';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                $time_text = $hours . ' ชั่วโมงที่แล้ว';
            } elseif ($diff < 172800) {
                $time_text = 'เมื่อวาน, ' . $row['formatted_time'] . ' น.';
            } else {
                $time_text = $row['formatted_date'] . ', ' . $row['formatted_time'] . ' น.';
            }
            
            // รายละเอียดนักเรียน
            $student_details = $row['level'] . '/' . $row['group_number'] . ' ' . $row['department_name'];
            
            $activities[] = [
                'id' => $row['attendance_id'],
                'student_id' => $row['student_id'],
                'student_name' => $full_name,
                'student_details' => $student_details,
                'date' => $row['formatted_date'],
                'time' => $row['formatted_time'],
                'full_datetime' => $row['full_datetime'],
                'timestamp' => $row['timestamp'],
                'time_text' => $time_text,
                'status' => $row['attendance_status'],
                'status_text' => $row['status_text'],
                'status_class' => $status_class,
                'method' => $row['check_method'],
                'method_text' => $row['method_text'],
                'remarks' => $row['remarks'],
                'type' => $type,
                'icon' => $icon
            ];
        }
        $stmt->close();
    }
    
    return [
        'activities' => $activities,
        'total_items' => $total_items,
        'total_pages' => ceil($total_items / $items_per_page),
        'current_page' => $page
    ];
}

// ดึงข้อมูลนักเรียนในความดูแล
$students = getStudents($conn, $parent_id);

// ดึงข้อมูลกิจกรรมตามตัวกรอง
$activities_data = getActivities($conn, $parent_id, $filter_student, $filter_type, $filter_period, $filter_page, $items_per_page);
$activities = $activities_data['activities'];
$total_items = $activities_data['total_items'];
$total_pages = $activities_data['total_pages'];
$current_page = $activities_data['current_page'];

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/activities_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
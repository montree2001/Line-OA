<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าแสดงรายชื่อนักเรียนในความดูแลของผู้ปกครอง
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
$page_title = 'SADD-Prasat - นักเรียนในความดูแล';
$current_page = 'students';
$extra_css = [
    'assets/css/parent-students.css'
];
$extra_js = [
    'assets/js/parent-students.js'
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

// จัดการการเพิ่มนักเรียน
if (isset($_POST['add_student']) && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    
    // ตรวจสอบว่านักเรียนมีอยู่จริงและยังไม่ได้อยู่ในความดูแลของผู้ปกครองคนนี้
    $check_stmt = $conn->prepare("SELECT 1 FROM parent_student_relation WHERE parent_id = ? AND student_id = ?");
    $check_stmt->bind_param("ii", $parent_id, $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // เพิ่มความสัมพันธ์
        $add_stmt = $conn->prepare("INSERT INTO parent_student_relation (parent_id, student_id, created_at) VALUES (?, ?, NOW())");
        $add_stmt->bind_param("ii", $parent_id, $student_id);
        if ($add_stmt->execute()) {
            $success_message = "เพิ่มนักเรียนเข้าสู่ความดูแลเรียบร้อยแล้ว";
        } else {
            $error_message = "ไม่สามารถเพิ่มนักเรียนได้: " . $conn->error;
        }
        $add_stmt->close();
    } else {
        $error_message = "นักเรียนนี้อยู่ในความดูแลของคุณแล้ว";
    }
    $check_stmt->close();
}

// จัดการการลบนักเรียน
if (isset($_POST['remove_student']) && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    
    // ลบความสัมพันธ์
    $remove_stmt = $conn->prepare("DELETE FROM parent_student_relation WHERE parent_id = ? AND student_id = ?");
    $remove_stmt->bind_param("ii", $parent_id, $student_id);
    if ($remove_stmt->execute()) {
        $success_message = "ลบนักเรียนออกจากความดูแลเรียบร้อยแล้ว";
    } else {
        $error_message = "ไม่สามารถลบนักเรียนได้: " . $conn->error;
    }
    $remove_stmt->close();
}

// ตรวจสอบการดูรายละเอียดนักเรียน
$selected_student = null;
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    
    // ตรวจสอบว่านักเรียนอยู่ในความดูแลของผู้ปกครองคนนี้
    $stmt = $conn->prepare("
        SELECT s.*, u.first_name, u.last_name, u.phone_number, u.email, 
               c.level, c.group_number, d.department_name,
               s.title AS student_title
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN parent_student_relation psr ON s.student_id = psr.student_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE psr.parent_id = ? AND s.student_id = ?
    ");
    $stmt->bind_param("ii", $parent_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_student = $result->fetch_assoc();
        
        // ดึงข้อมูลเพิ่มเติม
        $selected_student['attendance_history'] = getAttendanceHistory($conn, $student_id);
        $selected_student['teacher'] = getTeacher($conn, $student_id);
    }
    $stmt->close();
}

// ดึงข้อมูลนักเรียนในความดูแล
function getStudents($conn, $parent_id) {
    $students = [];
    
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title AS student_title, 
               u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name,
               sar.total_attendance_days, sar.total_absence_days
        FROM parent_student_relation psr
        JOIN students s ON psr.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = (
            SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
        )
        WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
    ");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // คำนวณเปอร์เซ็นต์การเข้าแถว
        $total_days = (int)$row['total_attendance_days'] + (int)$row['total_absence_days'];
        $attendance_percentage = ($total_days > 0) 
            ? round(($row['total_attendance_days'] / $total_days) * 100, 1) 
            : 0;
        
        // ดึงข้อมูลการเช็คชื่อล่าสุด
        $check_in_sql = "SELECT attendance_status, DATE_FORMAT(check_time, '%H:%i') as check_time, date
                        FROM attendance 
                        WHERE student_id = ? 
                        ORDER BY date DESC, check_time DESC 
                        LIMIT 1";
        $check_stmt = $conn->prepare($check_in_sql);
        $check_stmt->bind_param("i", $row['student_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // สร้างชื่อเต็ม
        $full_name = $row['student_title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        
        // สร้างชื่อชั้นเรียน
        $class_name = isset($row['level']) ? $row['level'] . '/' . $row['group_number'] : 'ไม่ระบุชั้นเรียน';
        
        // สร้างอักษรนำของชื่อ
        $avatar = mb_substr($row['first_name'], 0, 1, 'UTF-8');
        
        // เช็คสถานะการเข้าแถวล่าสุด
        $status = 'ไม่มีข้อมูล';
        $status_class = 'unknown';
        $status_icon = 'help_outline';
        $check_in_time = '';
        $check_date = '';
        
        if ($check_data) {
            $check_in_time = $check_data['check_time'];
            $check_date = date('d/m/Y', strtotime($check_data['date']));
            
            switch ($check_data['attendance_status']) {
                case 'present':
                    $status = 'เข้าแถว';
                    $status_class = 'present';
                    $status_icon = 'check_circle';
                    break;
                case 'absent':
                    $status = 'ขาดแถว';
                    $status_class = 'absent';
                    $status_icon = 'cancel';
                    break;
                case 'late':
                    $status = 'มาสาย';
                    $status_class = 'late';
                    $status_icon = 'watch_later';
                    break;
                case 'leave':
                    $status = 'ลา';
                    $status_class = 'leave';
                    $status_icon = 'event_busy';
                    break;
                default:
                    $status = 'ไม่มีข้อมูล';
                    $status_class = 'unknown';
                    $status_icon = 'help_outline';
            }
        }
        
        $students[] = [
            'id' => $row['student_id'],
            'student_code' => $row['student_code'],
            'name' => $full_name,
            'avatar' => $avatar,
            'class' => $class_name,
            'department' => $row['department_name'] ?? 'ไม่ระบุแผนก',
            'number' => mt_rand(1, 40), // สมมติค่า เพราะไม่มีในฐานข้อมูล
            'status' => $status,
            'status_class' => $status_class,
            'status_icon' => $status_icon,
            'check_in_time' => $check_in_time,
            'check_date' => $check_date,
            'attendance_days' => (int)$row['total_attendance_days'],
            'absent_days' => (int)$row['total_absence_days'],
            'attendance_percentage' => $attendance_percentage
        ];
    }
    $stmt->close();
    
    return $students;
}

// ฟังก์ชันค้นหานักเรียนเพื่อเพิ่มเข้าความดูแล
function searchStudents($conn, $keyword, $parent_id) {
    $results = [];
    
    // ปรับคีย์เวิร์ดสำหรับการค้นหา
    $search_term = "%" . $keyword . "%";
    
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
          AND s.status = 'กำลังศึกษา'
          AND s.student_id NOT IN (
              SELECT student_id FROM parent_student_relation WHERE parent_id = ?
          )
        LIMIT 10
    ");
    $stmt->bind_param("sssi", $search_term, $search_term, $search_term, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        $class_name = isset($row['level']) ? $row['level'] . '/' . $row['group_number'] : 'ไม่ระบุชั้นเรียน';
        
        $results[] = [
            'id' => $row['student_id'],
            'student_code' => $row['student_code'],
            'name' => $full_name,
            'class' => $class_name,
            'department' => $row['department_name'] ?? 'ไม่ระบุแผนก'
        ];
    }
    $stmt->close();
    
    return $results;
}

// ฟังก์ชันดึงประวัติการเข้าแถว
function getAttendanceHistory($conn, $student_id, $limit = 10) {
    $history = [];
    
    $stmt = $conn->prepare("
        SELECT a.date, a.attendance_status, a.check_time, a.check_method,
               DATE_FORMAT(a.date, '%d') as day,
               DATE_FORMAT(a.date, '%m/%Y') as month,
               CASE
                   WHEN MONTH(a.date) = 1 THEN 'ม.ค.'
                   WHEN MONTH(a.date) = 2 THEN 'ก.พ.'
                   WHEN MONTH(a.date) = 3 THEN 'มี.ค.'
                   WHEN MONTH(a.date) = 4 THEN 'เม.ย.'
                   WHEN MONTH(a.date) = 5 THEN 'พ.ค.'
                   WHEN MONTH(a.date) = 6 THEN 'มิ.ย.'
                   WHEN MONTH(a.date) = 7 THEN 'ก.ค.'
                   WHEN MONTH(a.date) = 8 THEN 'ส.ค.'
                   WHEN MONTH(a.date) = 9 THEN 'ก.ย.'
                   WHEN MONTH(a.date) = 10 THEN 'ต.ค.'
                   WHEN MONTH(a.date) = 11 THEN 'พ.ย.'
                   WHEN MONTH(a.date) = 12 THEN 'ธ.ค.'
               END as month_short
        FROM attendance a
        WHERE a.student_id = ?
        ORDER BY a.date DESC, a.check_time DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $student_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $present = ($row['attendance_status'] === 'present' || $row['attendance_status'] === 'late');
        
        $method_icon = '';
        switch ($row['check_method']) {
            case 'GPS':
                $method_icon = 'gps_fixed';
                break;
            case 'QR_Code':
                $method_icon = 'qr_code_scanner';
                break;
            case 'PIN':
                $method_icon = 'pin';
                break;
            case 'Manual':
                $method_icon = 'edit';
                break;
            default:
                $method_icon = 'check_circle';
        }
        
        $entry = [
            'date' => $row['date'],
            'day' => $row['day'],
            'month' => $row['month'],
            'month_short' => $row['month_short'],
            'present' => $present,
            'status' => $row['attendance_status'],
            'time' => substr($row['check_time'], 0, 5),
            'method' => $row['check_method'],
            'method_icon' => $method_icon
        ];
        
        $history[] = $entry;
    }
    $stmt->close();
    
    return $history;
}

// ฟังก์ชันดึงข้อมูลครูที่ปรึกษา
function getTeacher($conn, $student_id) {
    $teacher = null;
    
    $stmt = $conn->prepare("
        SELECT t.teacher_id, t.title, t.first_name, t.last_name, 
               u.phone_number, d.department_name, c.level, c.group_number
        FROM students s
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
        JOIN teachers t ON ca.teacher_id = t.teacher_id
        JOIN users u ON t.user_id = u.user_id
        JOIN departments d ON t.department_id = d.department_id
        WHERE s.student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        $position = 'ครูประจำชั้น ' . $row['level'] . '/' . $row['group_number'] . ' แผนก' . $row['department_name'];
        
        $teacher = [
            'id' => $row['teacher_id'],
            'name' => $full_name,
            'position' => $position,
            'phone' => $row['phone_number'],
            'line_id' => '@teacher_prasat' // สมมติค่า เพราะไม่มีในฐานข้อมูล
        ];
    } else {
        // กรณีไม่พบข้อมูลครูที่ปรึกษา ให้ใช้ค่าเริ่มต้น
        $teacher = [
            'id' => 0,
            'name' => 'ยังไม่ได้กำหนด',
            'position' => 'ยังไม่ได้กำหนดครูที่ปรึกษา',
            'phone' => '-',
            'line_id' => '-'
        ];
    }
    $stmt->close();
    
    return $teacher;
}

// ดึงข้อมูลที่จำเป็น
$students = getStudents($conn, $parent_id);

// ค้นหานักเรียนเพื่อเพิ่ม
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_results = searchStudents($conn, $_GET['search'], $parent_id);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/students_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
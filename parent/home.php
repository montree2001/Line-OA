<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าหลักสำหรับผู้ปกครองในระบบเช็คชื่อเข้าแถวนักเรียน
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน (ในการใช้งานจริงควรมีการตรวจสอบการล็อกอินผ่าน LINE)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - หน้าหลักผู้ปกครอง';
$current_page = 'dashboard';
$extra_css = [
    'assets/css/parent-dashboard.css'
];
$extra_js = [
    'assets/js/parent-dashboard.js'
];

// เชื่อมต่อกับฐานข้อมูล
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
    // ถ้ายังไม่ได้ลงทะเบียนเป็นผู้ปกครอง ให้ใช้ค่าเริ่มต้น (สำหรับการพัฒนา)
    $parent_id = 3; // ค่าเริ่มต้น
} else {
    $parent_data = $result->fetch_assoc();
    $parent_id = $parent_data['parent_id'];
}
$stmt->close();

// สร้างฟังก์ชันดึงข้อมูลนักเรียนในความดูแลของผู้ปกครอง
function getStudents($conn, $parent_id) {
    $students = [];
    
    // SQL เพื่อดึงข้อมูลนักเรียนที่เกี่ยวข้องกับผู้ปกครอง
    $sql = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
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
           WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // ถ้าไม่พบข้อมูลจริง ใช้ข้อมูลจำลอง (สำหรับการพัฒนา)
        return [];
    }
    
    while ($row = $result->fetch_assoc()) {
        // คำนวณเปอร์เซ็นต์การเข้าแถว
        $total_days = (int)$row['total_attendance_days'] + (int)$row['total_absence_days'];
        $attendance_percentage = ($total_days > 0) 
            ? round(($row['total_attendance_days'] / $total_days) * 100, 1) 
            : 0;
        
        // ดึงข้อมูลการเช็คชื่อล่าสุด
        $check_in_sql = "SELECT DATE_FORMAT(check_time, '%H:%i') as check_time, 
                               attendance_status, DATE(date) as attendance_date
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
        $full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        
        // สร้างชื่อชั้นเรียน
        $class_name = $row['level'] . '/' . $row['group_number'];
        
        // สร้างอักษรนำของชื่อ
        $avatar = mb_substr($row['first_name'], 0, 1, 'UTF-8');
        
        // เช็คสถานะการเข้าแถววันนี้
        $present = false;
        $check_in_time = '';
        if ($check_data) {
            $present = ($check_data['attendance_status'] === 'present' || $check_data['attendance_status'] === 'late');
            $check_in_time = $check_data['check_time'];
            
            // ตรวจสอบว่าเป็นวันนี้หรือไม่
            $today = date('Y-m-d');
            if ($check_data['attendance_date'] !== $today) {
                $present = null; // ไม่มีข้อมูลการเช็คชื่อวันนี้
            }
        }
        
        $students[] = [
            'id' => $row['student_id'],
            'name' => $full_name,
            'avatar' => $avatar,
            'class' => $class_name,
            'number' => 0, // ไม่มีในฐานข้อมูล
            'present' => $present,
            'check_in_time' => $check_in_time,
            'attendance_days' => (int)$row['total_attendance_days'],
            'absent_days' => (int)$row['total_absence_days'],
            'attendance_percentage' => $attendance_percentage
        ];
    }
    $stmt->close();
    
    return $students;
}

// สร้างฟังก์ชันดึงข้อมูลกิจกรรมล่าสุด
function getActivities($conn, $parent_id) {
    $activities = [];
    
    // SQL เพื่อดึงข้อมูลกิจกรรมล่าสุดของนักเรียนที่เกี่ยวข้องกับผู้ปกครอง
    $sql = "SELECT a.attendance_id, a.student_id, a.date, a.check_time, a.attendance_status,
                  s.title, u.first_name, u.last_name
           FROM attendance a
           JOIN students s ON a.student_id = s.student_id
           JOIN users u ON s.user_id = u.user_id
           JOIN parent_student_relation psr ON s.student_id = psr.student_id
           WHERE psr.parent_id = ?
           ORDER BY a.date DESC, a.check_time DESC
           LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // ถ้าไม่พบข้อมูลจริง ใช้ข้อมูลจำลอง (สำหรับการพัฒนา)
        return [];
    }
    
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
        
        // กำหนดประเภทกิจกรรมและไอคอนตามสถานะการเข้าแถว
        $type = '';
        $icon = '';
        switch ($row['attendance_status']) {
            case 'present':
                $type = 'check-in';
                $icon = 'check_circle';
                $action_text = 'เช็คชื่อเข้าแถว';
                break;
            case 'absent':
                $type = 'absent';
                $icon = 'cancel';
                $action_text = 'ขาดเข้าแถว';
                break;
            case 'late':
                $type = 'check-in';
                $icon = 'watch_later';
                $action_text = 'เช็คชื่อเข้าแถว (สาย)';
                break;
            case 'leave':
                $type = 'leave';
                $icon = 'event_busy';
                $action_text = 'ลาการเข้าแถว';
                break;
            default:
                $type = 'other';
                $icon = 'info';
                $action_text = 'มีการเปลี่ยนแปลงการเข้าแถว';
        }
        
        // กำหนดเวลา
        $date = new DateTime($row['date']);
        $today = new DateTime();
        $yesterday = new DateTime('-1 day');
        
        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
            $time_text = 'วันนี้, ' . substr($row['check_time'], 0, 5) . ' น.';
        } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $time_text = 'เมื่อวาน, ' . substr($row['check_time'], 0, 5) . ' น.';
        } else {
            $time_text = $date->format('d/m/Y') . ', ' . substr($row['check_time'], 0, 5) . ' น.';
        }
        
        $activities[] = [
            'id' => $row['attendance_id'],
            'type' => $type,
            'icon' => $icon,
            'title' => $full_name . ' ' . $action_text,
            'time' => $time_text
        ];
    }
    $stmt->close();
    
    // หากไม่พบกิจกรรมใด ๆ ให้เพิ่มการแจ้งเตือนประกาศล่าสุด
    if (empty($activities)) {
        // ดึงประกาศล่าสุด
        $ann_sql = "SELECT announcement_id, title, created_at 
                    FROM announcements 
                    WHERE status = 'active' 
                    ORDER BY created_at DESC LIMIT 1";
        $ann_result = $conn->query($ann_sql);
        if ($ann_result && $ann_result->num_rows > 0) {
            $ann = $ann_result->fetch_assoc();
            $created = new DateTime($ann['created_at']);
            $activities[] = [
                'id' => $ann['announcement_id'],
                'type' => 'announcement',
                'icon' => 'campaign',
                'title' => 'ประกาศ: ' . $ann['title'],
                'time' => $created->format('d/m/Y') . ', ' . $created->format('H:i') . ' น.'
            ];
        }
    }
    
    return $activities;
}

// สร้างฟังก์ชันดึงข้อมูลครูที่ปรึกษาสำหรับทุกนักเรียนของผู้ปกครอง
function getAllTeachers($conn, $parent_id) {
    $teachers = [];
    
    // SQL เพื่อดึงข้อมูลนักเรียนที่เกี่ยวข้องกับผู้ปกครอง
    $sql = "SELECT s.student_id, s.title as student_title, su.first_name as student_first_name, su.last_name as student_last_name,
                  c.class_id, c.level, c.group_number, d.department_name,
                  t.teacher_id, t.title as teacher_title, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
                  tu.phone_number, ca.is_primary
           FROM parent_student_relation psr
           JOIN students s ON psr.student_id = s.student_id
           JOIN users su ON s.user_id = su.user_id
           LEFT JOIN classes c ON s.current_class_id = c.class_id
           LEFT JOIN departments d ON c.department_id = d.department_id
           LEFT JOIN class_advisors ca ON c.class_id = ca.class_id
           LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
           LEFT JOIN users tu ON t.user_id = tu.user_id
           WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
           ORDER BY s.student_id, ca.is_primary DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // ถ้าไม่พบข้อมูล ส่งค่าว่างกลับไป
        return [];
    }
    
    // สร้างโครงสร้างข้อมูลสำหรับจัดเก็บครูที่ปรึกษาตามนักเรียน
    $student_teachers = [];
    
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
        $student_name = $row['student_title'] . ' ' . $row['student_first_name'] . ' ' . $row['student_last_name'];
        $class_name = $row['level'] . '/' . $row['group_number'] . ' แผนก' . $row['department_name'];
        
        // ถ้ายังไม่มีข้อมูลของนักเรียนนี้ ให้สร้างใหม่
        if (!isset($student_teachers[$student_id])) {
            $student_teachers[$student_id] = [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'class_name' => $class_name,
                'advisors' => []
            ];
        }
        
        // ถ้ามีข้อมูลครูที่ปรึกษา ให้เพิ่มเข้าไป
        if ($row['teacher_id']) {
            $teacher_id = $row['teacher_id'];
            $teacher_name = $row['teacher_title'] . ' ' . $row['teacher_first_name'] . ' ' . $row['teacher_last_name'];
            $position = ($row['is_primary'] == 1) ? 'ครูที่ปรึกษาหลัก' : 'ครูที่ปรึกษาร่วม';
            $position .= ' ' . $class_name;
            
            // ตรวจสอบว่าครูนี้ยังไม่อยู่ในรายการครูที่ปรึกษาของนักเรียนนี้
            $advisor_exists = false;
            foreach ($student_teachers[$student_id]['advisors'] as $advisor) {
                if ($advisor['id'] == $teacher_id) {
                    $advisor_exists = true;
                    break;
                }
            }
            
            if (!$advisor_exists) {
                $student_teachers[$student_id]['advisors'][] = [
                    'id' => $teacher_id,
                    'name' => $teacher_name,
                    'position' => $position,
                    'phone' => $row['phone_number'],
                    'is_primary' => $row['is_primary']
                ];
            }
        }
    }
    $stmt->close();
    
    return array_values($student_teachers);
}

// สร้างฟังก์ชันดึงข้อมูลประกาศ
function getAnnouncements($conn) {
    // SQL เพื่อดึงข้อมูลประกาศล่าสุด
    $sql = "SELECT announcement_id, title, content, type, DATE_FORMAT(created_at, '%d %b %Y') as formatted_date
            FROM announcements
            WHERE status = 'active'
            ORDER BY created_at DESC
            LIMIT 3";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        // ถ้าไม่พบข้อมูล ส่งค่าว่างกลับไป
        return [];
    }
    
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
        // กำหนดหมวดหมู่และคลาส CSS ตามประเภทประกาศ
        $category = 'ประกาศ';
        $category_class = 'announcement';
        
        switch ($row['type']) {
            case 'exam':
                $category = 'สอบ';
                $category_class = 'exam';
                break;
            case 'event':
                $category = 'กิจกรรม';
                $category_class = 'event';
                break;
            case 'important':
                $category = 'สำคัญ';
                $category_class = 'important';
                break;
        }
        
        $announcements[] = [
            'id' => $row['announcement_id'],
            'category' => $category,
            'category_class' => $category_class,
            'title' => $row['title'],
            'content' => $row['content'],
            'date' => $row['formatted_date']
        ];
    }
    
    return $announcements;
}

// ดึงข้อมูลที่จำเป็น
$students = getStudents($conn, $parent_id);
$latest_check_in = null;

if (!empty($students) && isset($students[0]) && isset($students[0]['check_in_time']) && !empty($students[0]['check_in_time'])) {
    $latest_check_in = $students[0]['name'] . ' เช็คชื่อเข้าแถวเวลา ' . $students[0]['check_in_time'] . ' น.';
}

$activities = getActivities($conn, $parent_id);

// ดึงข้อมูลครูที่ปรึกษาของทุกนักเรียนในความดูแลของผู้ปกครอง
$student_teachers = getAllTeachers($conn, $parent_id);

$announcements = getAnnouncements($conn);

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/dashboard_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
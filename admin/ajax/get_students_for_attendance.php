<?php
/**
 * get_students_for_attendance.php - ไฟล์สำหรับดึงข้อมูลนักเรียนสำหรับการเช็คชื่อแบบกลุ่ม
 * ปรับปรุงใหม่: รองรับการค้นหาด้วยชื่อหรือรหัส, แสดงวิธีการเช็คชื่อ
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json'); // กำหนด content type เป็น JSON ทุกกรณี

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$level = isset($_GET['level']) ? $_GET['level'] : '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ตรวจสอบว่ามีเงื่อนไขการค้นหาอย่างน้อย 1 อย่าง
if (!$department_id && !$level && !$class_id && !$search) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุเงื่อนไขการค้นหา']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // สร้าง SQL query ตามเงื่อนไข
    $sql = "
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name,
               a.attendance_status, a.check_time, a.remarks, a.check_method
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = :date
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    // กำหนดเงื่อนไขเพิ่มเติม
    $params = [':date' => $date];
    
    if ($class_id) {
        $sql .= " AND s.current_class_id = :class_id";
        $params[':class_id'] = $class_id;
        
        // ดึงข้อมูลห้องเรียนเพิ่มเติม
        $stmt = $conn->prepare("
            SELECT c.level, c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class_info) {
            $class_display = $class_info['level'] . '/' . $class_info['group_number'] . ' ' . $class_info['department_name'];
        }
    } else {
        if ($department_id) {
            $sql .= " AND c.department_id = :department_id";
            $params[':department_id'] = $department_id;
            
            // ดึงชื่อแผนก
            $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $stmt->execute([$department_id]);
            $dept_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dept_info) {
                $dept_display = $dept_info['department_name'];
            }
        }
        
        if ($level) {
            $sql .= " AND c.level = :level";
            $params[':level'] = $level;
        }
        
        $sql .= " AND c.academic_year_id = :academic_year_id";
        $params[':academic_year_id'] = $academic_year_id;
    }
    
    // เพิ่มเงื่อนไขการค้นหาตามชื่อหรือรหัสนักเรียน
    if ($search) {
        $sql .= " AND (s.student_code LIKE :search OR CONCAT(s.title, u.first_name, ' ', u.last_name) LIKE :search_name)";
        $params[':search'] = "%$search%";
        $params[':search_name'] = "%$search%";
    }
    
    // หากเป็นครูที่ปรึกษา ให้ดึงเฉพาะห้องที่เป็นที่ปรึกษา
    if ($_SESSION['user_role'] == 'teacher') {
        // ดึงรหัสครู
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $sql .= " AND s.current_class_id IN (
                SELECT class_id FROM class_advisors 
                WHERE teacher_id = :teacher_id
            )";
            $params[':teacher_id'] = $teacher['teacher_id'];
        }
    }
    
    // เรียงลำดับตามรหัสนักเรียน
    $sql .= " ORDER BY s.student_code";
    
    // เตรียม statement และ execute
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่าพบนักเรียนหรือไม่
    if (count($students) === 0) {
        echo json_encode([
            'success' => true,
            'students' => [],
            'message' => 'ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก'
        ]);
        exit;
    }
    
    // กำหนดข้อมูลชื่อกลุ่มที่เลือก
    $class_info = '';
    if (isset($class_display)) {
        $class_info = 'ห้อง ' . $class_display;
    } elseif (isset($dept_display)) {
        $class_info = 'แผนก ' . $dept_display;
        if ($level) {
            $class_info .= ' ระดับชั้น ' . $level;
        }
    } elseif ($level) {
        $class_info = 'ระดับชั้น ' . $level;
    }
    
    if ($search) {
        $class_info .= ($class_info ? ' - ' : '') . 'ค้นหา: ' . $search;
    }
    
    // เพิ่มข้อมูลวันที่
    $thai_month = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    
    $date_parts = explode('-', $date);
    $thai_date = intval($date_parts[2]) . ' ' . $thai_month[$date_parts[1]] . ' ' . (intval($date_parts[0]) + 543);
    $class_info .= ($class_info ? ' - ' : '') . 'วันที่ ' . $thai_date;
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'students' => $students,
        'class_info' => $class_info,
        'class_id' => $class_id,
        'date' => $date
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_students_for_attendance.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
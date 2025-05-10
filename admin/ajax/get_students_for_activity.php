<?php
/**
 * get_students_for_activity.php - ไฟล์สำหรับดึงข้อมูลนักเรียนสำหรับการบันทึกการเข้าร่วมกิจกรรม
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json'); // กำหนด content type เป็น JSON ทุกกรณี

/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$level = isset($_GET['level']) ? $_GET['level'] : '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ตรวจสอบว่ามีรหัสกิจกรรมหรือไม่
if (!$activity_id) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสกิจกรรม']);
    exit;
}

// ตรวจสอบว่ามีเงื่อนไขการค้นหาอย่างน้อย 1 อย่าง
if (!$department_id && !$level && !$class_id && !$search) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุเงื่อนไขการค้นหา']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลกิจกรรมเพื่อตรวจสอบกลุ่มเป้าหมาย
    $stmt = $conn->prepare("
        SELECT a.activity_id, a.activity_name, a.academic_year_id
        FROM activities a
        WHERE a.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }
    
    // ดึงแผนกวิชาเป้าหมาย
    $stmt = $conn->prepare("
        SELECT department_id
        FROM activity_target_departments
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึงระดับชั้นเป้าหมาย
    $stmt = $conn->prepare("
        SELECT level
        FROM activity_target_levels
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // สร้าง SQL query ตามเงื่อนไข
    $sql = "
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name,
               aa.attendance_status, aa.remarks
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN activity_attendance aa ON aa.student_id = s.student_id AND aa.activity_id = ?
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    // กำหนดเงื่อนไขเพิ่มเติม
    $params = [$activity_id]; // เริ่มต้นด้วย activity_id
    $whereClauses = [];
    
    // สร้างเงื่อนไขตามเป้าหมายกิจกรรม
    if (!empty($target_departments)) {
        $placeholders = rtrim(str_repeat('?,', count($target_departments)), ',');
        $whereClauses[] = "d.department_id IN ($placeholders)";
        $params = array_merge($params, $target_departments);
    }
    
    if (!empty($target_levels)) {
        $placeholders = rtrim(str_repeat('?,', count($target_levels)), ',');
        $whereClauses[] = "c.level IN ($placeholders)";
        $params = array_merge($params, $target_levels);
    }
    
    // สร้างเงื่อนไขตามค่าค้นหา
    if ($class_id) {
        $whereClauses[] = "s.current_class_id = ?";
        $params[] = $class_id;
        
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
            $class_display = "ห้อง " . $class_info['level'] . '/' . $class_info['group_number'] . ' ' . $class_info['department_name'];
        }
    } else {
        if ($department_id) {
            $whereClauses[] = "c.department_id = ?";
            $params[] = $department_id;
            
            // ดึงชื่อแผนก
            $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            $stmt->execute([$department_id]);
            $dept_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dept_info) {
                $dept_display = $dept_info['department_name'];
            }
        }
        
        if ($level) {
            $whereClauses[] = "c.level = ?";
            $params[] = $level;
        }
    }
    
    // เงื่อนไขการค้นหา
    if ($search) {
        $whereClauses[] = "(s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // รวมเงื่อนไขทั้งหมด
    if (!empty($whereClauses)) {
        $sql .= " AND " . implode(' AND ', $whereClauses);
    }
    
    // หากเป็นครูที่ปรึกษา ให้ดึงเฉพาะห้องที่เป็นที่ปรึกษา
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'teacher' && isset($_SESSION['user_id'])) {
        // ดึงรหัสครู
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $sql .= " AND s.current_class_id IN (
                SELECT class_id FROM class_advisors 
                WHERE teacher_id = ?
            )";
            $params[] = $teacher['teacher_id'];
        }
    }
    
    // เรียงลำดับตามรหัสนักเรียน
    $sql .= " ORDER BY s.student_code";
    
    // เตรียม statement และ execute
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
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
        $class_info = $class_display;
    } elseif (isset($dept_display)) {
        $class_info = 'แผนก ' . $dept_display;
        if ($level) {
            $class_info .= ' ระดับชั้น ' . $level;
        }
    } elseif ($level) {
        $class_info = 'ระดับชั้น ' . $level;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'students' => $students,
        'class_info' => $class_info,
        'activity_id' => $activity_id
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_students_for_activity.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
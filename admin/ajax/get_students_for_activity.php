<?php
/**
 * get_students_for_activity.php - ไฟล์สำหรับดึงข้อมูลนักเรียนตามเป้าหมายของกิจกรรม
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// ป้องกันการเข้าถึงโดยตรง (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
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
$search = isset($_GET['search']) ? $_GET['search'] : '';

if (!$activity_id) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสกิจกรรม']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลกิจกรรม
    $stmt = $conn->prepare("
        SELECT 
            a.activity_id, a.activity_name, a.activity_date, a.academic_year_id,
            a.required_attendance
        FROM activities a
        WHERE a.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }
    
    // ดึงแผนกวิชาและระดับชั้นเป้าหมาย
    $stmt = $conn->prepare("
        SELECT department_id FROM activity_target_departments WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $conn->prepare("
        SELECT level FROM activity_target_levels WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // สร้างเงื่อนไข SQL สำหรับกรองตามเป้าหมายของกิจกรรม และตามตัวกรองที่ผู้ใช้เลือก
    $conditions = [];
    $params = [];
    
    // เงื่อนไขรหัสกิจกรรม (สำหรับตาราง LEFT JOIN)
    $params[] = $activity_id;
    
    // เงื่อนไขตามเป้าหมายของกิจกรรม
    if (!empty($target_departments)) {
        $dept_placeholders = rtrim(str_repeat('?,', count($target_departments)), ',');
        $conditions[] = "d.department_id IN ($dept_placeholders)";
        $params = array_merge($params, $target_departments);
    }
    
    if (!empty($target_levels)) {
        $level_placeholders = rtrim(str_repeat('?,', count($target_levels)), ',');
        $conditions[] = "c.level IN ($level_placeholders)";
        $params = array_merge($params, $target_levels);
    }
    
    // เงื่อนไขตามตัวกรองที่ผู้ใช้เลือก
    if ($department_id) {
        $conditions[] = "d.department_id = ?";
        $params[] = $department_id;
    }
    
    if ($level) {
        $conditions[] = "c.level = ?";
        $params[] = $level;
    }
    
    if ($class_id) {
        $conditions[] = "c.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($search) {
        $conditions[] = "(s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // สร้าง WHERE clause จากเงื่อนไข
    $where_clause = "s.status = 'กำลังศึกษา'";
    if (!empty($conditions)) {
        $where_clause .= " AND " . implode(" AND ", $conditions);
    }
    
    // สร้าง Class Info สำหรับแสดงในหน้าเว็บ
    $class_info = '';
    if ($class_id) {
        $stmt = $conn->prepare("
            SELECT c.level, c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$class_id]);
        $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class_data) {
            $class_info = "ห้อง {$class_data['level']}/{$class_data['group_number']} แผนก {$class_data['department_name']}";
        }
    } elseif ($department_id) {
        $stmt = $conn->prepare("
            SELECT department_name FROM departments WHERE department_id = ?
        ");
        $stmt->execute([$department_id]);
        $dept_name = $stmt->fetchColumn();
        
        if ($dept_name) {
            $class_info = "แผนก $dept_name";
            if ($level) {
                $class_info .= " ระดับชั้น $level";
            }
        }
    } elseif ($level) {
        $class_info = "ระดับชั้น $level";
    }
    
    // ดึงข้อมูลนักเรียนตามเงื่อนไข
    $sql = "
        SELECT
            s.student_id,
            s.student_code,
            s.title,
            u.first_name,
            u.last_name,
            c.level,
            c.group_number,
            d.department_name,
            aa.attendance_status,
            aa.remarks
        FROM
            students s
        JOIN
            users u ON s.user_id = u.user_id
        JOIN
            classes c ON s.current_class_id = c.class_id
        JOIN
            departments d ON c.department_id = d.department_id
        LEFT JOIN
            activity_attendance aa ON aa.student_id = s.student_id AND aa.activity_id = ?
        WHERE
            $where_clause
        ORDER BY
            d.department_name,
            c.level,
            c.group_number,
            s.student_code
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'activity' => $activity,
        'students' => $students,
        'class_info' => $class_info,
        'count' => count($students)
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
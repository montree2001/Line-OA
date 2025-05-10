<?php
/**
 * get_activity_summary.php - ไฟล์สำหรับดึงข้อมูลสรุปการเข้าร่วมกิจกรรมผ่าน AJAX
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
            a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
            a.description, a.required_attendance, a.academic_year_id
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
        SELECT atd.department_id, d.department_name
        FROM activity_target_departments atd
        JOIN departments d ON atd.department_id = d.department_id
        WHERE atd.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงระดับชั้นเป้าหมาย
    $stmt = $conn->prepare("
        SELECT level
        FROM activity_target_levels
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ตรวจสอบว่ามีข้อมูลการเข้าร่วมกิจกรรมหรือไม่
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM activity_attendance
        WHERE activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $attendance_count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_attendance = ($attendance_count['count'] > 0);
    
    if (!$has_attendance) {
        // ยังไม่มีข้อมูลการเข้าร่วมกิจกรรม
        echo json_encode([
            'success' => true,
            'has_attendance' => false,
            'activity_id' => $activity_id,
            'activity_name' => $activity['activity_name']
        ]);
        exit;
    }
    
    // สร้างเงื่อนไข SQL สำหรับกรองตามเป้าหมาย
    $target_conditions = [];
    $target_params = [];
    
    if (!empty($target_departments)) {
        $dept_ids = array_column($target_departments, 'department_id');
        $placeholders = rtrim(str_repeat('?,', count($dept_ids)), ',');
        $target_conditions[] = "d.department_id IN ($placeholders)";
        $target_params = array_merge($target_params, $dept_ids);
    }
    
    if (!empty($target_levels)) {
        $placeholders = rtrim(str_repeat('?,', count($target_levels)), ',');
        $target_conditions[] = "c.level IN ($placeholders)";
        $target_params = array_merge($target_params, $target_levels);
    }
    
    // ถ้าไม่ได้กำหนดเป้าหมาย ให้นับนักเรียนทั้งหมด
    $target_sql = "";
    if (!empty($target_conditions)) {
        $target_sql = " AND " . implode(' AND ', $target_conditions);
    }
    
    // นับจำนวนนักเรียนทั้งหมดที่เป็นเป้าหมาย
    $sql = "
        SELECT COUNT(*) as total
        FROM students s
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE s.status = 'กำลังศึกษา'
        $target_sql
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($target_params);
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_students = $total_result['total'];
    
    // นับจำนวนนักเรียนที่เข้าร่วมกิจกรรม
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN aa.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN aa.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM activity_attendance aa
        JOIN students s ON aa.student_id = s.student_id
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE aa.activity_id = ? AND s.status = 'กำลังศึกษา'
        $target_sql
    ";
    
    $stmt = $conn->prepare($sql);
    $params = array_merge([$activity_id], $target_params);
    $stmt->execute($params);
    $attendance_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $present_count = $attendance_result['present_count'] ?? 0;
    $absent_count = $attendance_result['absent_count'] ?? 0;
    
    // คำนวณเปอร์เซ็นต์การเข้าร่วม
    $attendance_percent = 0;
    if ($total_students > 0) {
        $attendance_percent = round(($present_count / $total_students) * 100, 1);
    }
    
    // ดึงข้อมูลสรุปตามแผนกวิชา
    $department_summary = [];
    
    $sql = "
        SELECT 
            d.department_id, 
            d.department_name,
            COUNT(DISTINCT s.student_id) as total_students,
            SUM(CASE WHEN aa.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN aa.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM departments d
        JOIN classes c ON c.department_id = d.department_id
        JOIN students s ON s.current_class_id = c.class_id
        LEFT JOIN activity_attendance aa ON aa.student_id = s.student_id AND aa.activity_id = ?
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    // เพิ่มเงื่อนไขกรองตามแผนกวิชาเป้าหมาย
    $dept_filter_params = [$activity_id];
    
    if (!empty($target_departments)) {
        $dept_ids = array_column($target_departments, 'department_id');
        $placeholders = rtrim(str_repeat('?,', count($dept_ids)), ',');
        $sql .= " AND d.department_id IN ($placeholders)";
        $dept_filter_params = array_merge($dept_filter_params, $dept_ids);
    }
    
    // เพิ่มเงื่อนไขกรองตามระดับชั้นเป้าหมาย
    if (!empty($target_levels)) {
        $placeholders = rtrim(str_repeat('?,', count($target_levels)), ',');
        $sql .= " AND c.level IN ($placeholders)";
        $dept_filter_params = array_merge($dept_filter_params, $target_levels);
    }
    
    $sql .= " GROUP BY d.department_id, d.department_name ORDER BY d.department_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($dept_filter_params);
    $department_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลสรุปตามระดับชั้น
    $level_summary = [];
    
    $sql = "
        SELECT 
            c.level,
            COUNT(DISTINCT s.student_id) as total_students,
            SUM(CASE WHEN aa.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN aa.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM classes c
        JOIN students s ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN activity_attendance aa ON aa.student_id = s.student_id AND aa.activity_id = ?
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    // เพิ่มเงื่อนไขกรองตามแผนกวิชาเป้าหมาย
    $level_filter_params = [$activity_id];
    
    if (!empty($target_departments)) {
        $dept_ids = array_column($target_departments, 'department_id');
        $placeholders = rtrim(str_repeat('?,', count($dept_ids)), ',');
        $sql .= " AND d.department_id IN ($placeholders)";
        $level_filter_params = array_merge($level_filter_params, $dept_ids);
    }
    
    // เพิ่มเงื่อนไขกรองตามระดับชั้นเป้าหมาย
    if (!empty($target_levels)) {
        $placeholders = rtrim(str_repeat('?,', count($target_levels)), ',');
        $sql .= " AND c.level IN ($placeholders)";
        $level_filter_params = array_merge($level_filter_params, $target_levels);
    }
    
    $sql .= " GROUP BY c.level ORDER BY 
        CASE 
            WHEN c.level = 'ปวช.1' THEN 1
            WHEN c.level = 'ปวช.2' THEN 2
            WHEN c.level = 'ปวช.3' THEN 3
            WHEN c.level = 'ปวส.1' THEN 4
            WHEN c.level = 'ปวส.2' THEN 5
            ELSE 6
        END";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($level_filter_params);
    $level_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'has_attendance' => true,
        'activity_id' => $activity_id,
        'activity_name' => $activity['activity_name'],
        'total_students' => $total_students,
        'present_count' => $present_count,
        'absent_count' => $absent_count,
        'attendance_percent' => $attendance_percent,
        'department_summary' => $department_summary,
        'level_summary' => $level_summary
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_activity_summary.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
// File: ajax/get_activity_summary_by_department.php
<?php
/**
 * get_activity_summary_by_department.php - ดึงข้อมูลสรุปกิจกรรมตามแผนกวิชา
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

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
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    
    // ดึงรายการแผนกวิชา
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เตรียมข้อมูลสำหรับกราฟ
    $department_names = [];
    $total_students = [];
    $participants = [];
    
    foreach ($departments as $department) {
        $department_id = $department['department_id'];
        $department_names[] = $department['department_name'];
        
        // นับจำนวนนักเรียนทั้งหมดในแผนก
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT s.student_id) as total
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.department_id = ? AND s.status = 'กำลังศึกษา' AND c.academic_year_id = ?
        ");
        $stmt->execute([$department_id, $current_academic_year_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_students[] = $result['total'] ?? 0;
        
        // นับจำนวนนักเรียนที่เข้าร่วมกิจกรรมอย่างน้อย 1 ครั้ง
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT s.student_id) as total
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN activity_attendance aa ON s.student_id = aa.student_id
            JOIN activities a ON aa.activity_id = a.activity_id
            WHERE c.department_id = ? AND s.status = 'กำลังศึกษา' 
            AND c.academic_year_id = ? AND a.academic_year_id = ?
            AND aa.attendance_status = 'present'
        ");
        $stmt->execute([$department_id, $current_academic_year_id, $current_academic_year_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $participants[] = $result['total'] ?? 0;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'department_names' => $department_names,
        'total_students' => $total_students,
        'participants' => $participants
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>

// File: ajax/get_activity_summary_by_level.php
<?php
/**
 * get_activity_summary_by_level.php - ดึงข้อมูลสรุปกิจกรรมตามระดับชั้น
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

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
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    
    // ดึงรายการระดับชั้น
    $stmt = $conn->prepare("
        SELECT DISTINCT level 
        FROM classes 
        WHERE academic_year_id = ? 
        ORDER BY CASE 
            WHEN level = 'ปวช.1' THEN 1
            WHEN level = 'ปวช.2' THEN 2
            WHEN level = 'ปวช.3' THEN 3
            WHEN level = 'ปวส.1' THEN 4
            WHEN level = 'ปวส.2' THEN 5
            ELSE 6
        END
    ");
    $stmt->execute([$current_academic_year_id]);
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // เตรียมข้อมูลสำหรับกราฟ
    $total_students = [];
    $participants = [];
    
    foreach ($levels as $level) {
        // นับจำนวนนักเรียนทั้งหมดในระดับชั้น
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT s.student_id) as total
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.level = ? AND s.status = 'กำลังศึกษา' AND c.academic_year_id = ?
        ");
        $stmt->execute([$level, $current_academic_year_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_students[] = $result['total'] ?? 0;
        
        // นับจำนวนนักเรียนที่เข้าร่วมกิจกรรมอย่างน้อย 1 ครั้ง
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT s.student_id) as total
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN activity_attendance aa ON s.student_id = aa.student_id
            JOIN activities a ON aa.activity_id = a.activity_id
            WHERE c.level = ? AND s.status = 'กำลังศึกษา' 
            AND c.academic_year_id = ? AND a.academic_year_id = ?
            AND aa.attendance_status = 'present'
        ");
        $stmt->execute([$level, $current_academic_year_id, $current_academic_year_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $participants[] = $result['total'] ?? 0;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'levels' => $levels,
        'total_students' => $total_students,
        'participants' => $participants
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>
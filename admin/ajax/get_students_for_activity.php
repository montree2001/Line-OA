<?php
/**
 * get_students_for_activity.php - ไฟล์สำหรับดึงข้อมูลนักเรียนเพื่อบันทึกการเข้าร่วมกิจกรรม
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

// ป้องกันการเข้าถึงโดยตรง (อาจเปิดให้ทดสอบได้โดยปิด comment)
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์จาก GET request
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

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // สร้าง SQL query พื้นฐาน
    $sql = "
        SELECT 
            s.student_id, s.student_code, s.title, 
            u.first_name, u.last_name, 
            c.level, c.group_number,
            d.department_name,
            aa.attendance_status, aa.record_time, aa.remarks
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN activity_attendance aa ON s.student_id = aa.student_id AND aa.activity_id = ?
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    // สร้างเงื่อนไขการค้นหาเพิ่มเติม
    $params = [$activity_id];
    $conditions = [];
    
    // เพิ่มเงื่อนไขตามแผนกวิชา
    if ($department_id) {
        $conditions[] = "d.department_id = ?";
        $params[] = $department_id;
    }
    
    // เพิ่มเงื่อนไขตามระดับชั้น
    if ($level) {
        $conditions[] = "c.level = ?";
        $params[] = $level;
    }
    
    // เพิ่มเงื่อนไขตามกลุ่มเรียน
    if ($class_id) {
        $conditions[] = "c.class_id = ?";
        $params[] = $class_id;
    }
    
    // เพิ่มเงื่อนไขการค้นหา (รหัสนักเรียนหรือชื่อ)
    if ($search) {
        $conditions[] = "(s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // เพิ่มเงื่อนไขการค้นหาลงใน SQL
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    // เพิ่ม ORDER BY
    $sql .= " ORDER BY d.department_name, c.level, c.group_number, u.first_name, u.last_name";
    
    // เตรียมและ execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างข้อมูลสำหรับระบุกลุ่มที่กำลังแสดง
    $class_info = '';
    
    if ($class_id) {
        // ดึงข้อมูลกลุ่มเรียนที่เลือก
        $stmt = $conn->prepare("
            SELECT c.level, c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class) {
            $class_info = "กลุ่ม {$class['level']}/{$class['group_number']} แผนก{$class['department_name']}";
        }
    } else if ($department_id && $level) {
        // กรณีเลือกแผนกและระดับชั้น
        $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $stmt->execute([$department_id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($department) {
            $class_info = "ระดับชั้น {$level} แผนก{$department['department_name']}";
        }
    } else if ($department_id) {
        // กรณีเลือกเฉพาะแผนก
        $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $stmt->execute([$department_id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($department) {
            $class_info = "แผนก{$department['department_name']}";
        }
    } else if ($level) {
        // กรณีเลือกเฉพาะระดับชั้น
        $class_info = "ระดับชั้น {$level}";
    } else if ($search) {
        // กรณีค้นหา
        $class_info = "ผลการค้นหา: {$search}";
    }
    
    // ส่งผลลัพธ์
    echo json_encode([
        'success' => true,
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
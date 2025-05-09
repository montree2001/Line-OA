<?php
/**
 * get_classes.php - ไฟล์สำหรับดึงข้อมูลชั้นเรียนตามแผนกและระดับชั้น
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$level = isset($_GET['level']) ? $_GET['level'] : '';

// บันทึกข้อมูลที่ได้รับสำหรับการตรวจสอบ
error_log("get_classes.php - Parameters: department_id=$department_id, level=" . urlencode($level));

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        error_log("get_classes.php - No active academic year found");
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
    
    $academic_year_id = $academic_year['academic_year_id'];
    
    // สร้าง SQL query ตามเงื่อนไข
    $sql = "
        SELECT c.class_id, c.level, c.group_number, d.department_name
        FROM classes c
        JOIN departments d ON c.department_id = d.department_id
        WHERE c.is_active = 1 AND c.academic_year_id = :academic_year_id
    ";
    
    // กำหนดเงื่อนไขเพิ่มเติม
    $params = [':academic_year_id' => $academic_year_id];
    
    if ($department_id) {
        $sql .= " AND c.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    if ($level) {
        $sql .= " AND c.level = :level";
        $params[':level'] = $level;
    }
    
    // หากเป็นครูที่ปรึกษา ให้ดึงเฉพาะห้องที่เป็นที่ปรึกษา
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher') {
        // ดึงรหัสครู
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $sql .= " AND c.class_id IN (
                SELECT class_id FROM class_advisors 
                WHERE teacher_id = :teacher_id
            )";
            $params[':teacher_id'] = $teacher['teacher_id'];
        }
    }
    
    // เรียงลำดับตามระดับชั้นและกลุ่มเรียน
    $sql .= " ORDER BY c.level, c.group_number";
    
    // บันทึก SQL query สำหรับการตรวจสอบ
    error_log("get_classes.php - SQL: " . $sql);
    
    // เตรียม statement และ execute
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
        error_log("get_classes.php - Bind parameter: $key = $value");
    }
    $stmt->execute();
    
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // บันทึกจำนวนข้อมูลที่พบ
    error_log("get_classes.php - Found " . count($classes) . " classes");
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'classes' => $classes,
        'count' => count($classes),
        'query_info' => [
            'department_id' => $department_id,
            'level' => $level,
            'academic_year_id' => $academic_year_id
        ]
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_classes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
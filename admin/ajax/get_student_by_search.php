<?php
/**
 * get_student_by_search.php - ไฟล์สำหรับค้นหานักเรียนจากทั้งวิทยาลัย
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
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// ตรวจสอบว่ามีคำค้นหาหรือไม่
if (empty($search_term)) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุคำค้นหา']);
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
    
    // สร้าง SQL query สำหรับการค้นหานักเรียน
    $sql = "
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name,
               a.attendance_status, a.check_method, a.check_time, a.remarks
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = :date
        WHERE s.status = 'กำลังศึกษา'
        AND (
            s.student_code LIKE :search_term 
            OR u.first_name LIKE :search_term 
            OR u.last_name LIKE :search_term
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term
        )
    ";
    
    // หากเป็นครูที่ปรึกษา ให้ดึงเฉพาะห้องที่เป็นที่ปรึกษา
    if ($_SESSION['role'] == 'teacher') {
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
    
    // กำหนดพารามิเตอร์
    $params = [
        ':date' => $date,
        ':search_term' => '%' . $search_term . '%'
    ];
    
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
            'message' => 'ไม่พบนักเรียนที่ตรงกับคำค้นหา "' . htmlspecialchars($search_term) . '"'
        ]);
        exit;
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true,
        'students' => $students,
        'class_info' => 'ผลการค้นหา: ' . htmlspecialchars($search_term),
        'date' => $date
    ]);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    error_log("Database error in get_student_by_search.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
}
?>
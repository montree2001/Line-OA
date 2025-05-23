<?php
/**
 * get_today_attendance.php - ดึงข้อมูลการเช็คชื่อของวันที่ระบุ
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับพารามิเตอร์
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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
    
    // สร้าง SQL query ตามบทบาทผู้ใช้
    $sql = "
        SELECT 
            a.attendance_id,
            a.student_id,
            a.attendance_status,
            a.check_method,
            a.check_time,
            a.remarks,
            s.student_code,
            s.title,
            u.first_name,
            u.last_name,
            c.level,
            c.group_number,
            d.department_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE a.date = ? AND a.academic_year_id = ?
    ";
    
    $params = [$date, $academic_year_id];
    
    // ถ้าเป็นครูที่ปรึกษา ให้ดึงเฉพาะนักเรียนในห้องที่เป็นที่ปรึกษา
    if ($_SESSION['user_role'] == 'teacher') {
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
    
    $sql .= " ORDER BY a.check_time DESC, s.student_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบเวลา
    foreach ($attendance as &$record) {
        $record['check_time'] = date('H:i', strtotime($record['check_time']));
    }
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'date' => $date,
        'count' => count($attendance)
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_today_attendance.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>
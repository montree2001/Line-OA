<?php
// api/get_activity_detail.php - API ดึงข้อมูลรายละเอียดกิจกรรม

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึงข้อมูล']);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบพารามิเตอร์
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;

if ($activity_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุรหัสกิจกรรม']);
    exit;
}

try {
    $db = getDB();
    
    // ดึงข้อมูลกิจกรรม
    $activity_query = "SELECT a.activity_id, a.activity_name, a.activity_date, a.activity_location, a.description,
                      a.required_attendance, 
                      (SELECT COUNT(DISTINCT aa.student_id) FROM activity_attendance aa WHERE aa.activity_id = a.activity_id) as participating_count,
                      (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา' AND current_class_id IN 
                       (SELECT c.class_id FROM classes c JOIN activity_target_levels atl ON c.level = atl.level WHERE atl.activity_id = a.activity_id)) as total_students
                      FROM activities a
                      WHERE a.activity_id = :activity_id";
    
    $stmt = $db->prepare($activity_query);
    $stmt->bindParam(':activity_id', $activity_id, PDO::PARAM_INT);
    $stmt->execute();
    $activity_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity_data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }
    
    // คำนวณอัตราการเข้าร่วม
    $total_students = $activity_data['total_students'] ?: 1; // ป้องกันการหารด้วย 0
    $participation_rate = round(($activity_data['participating_count'] / $total_students) * 100, 1);
    
    // เพิ่มข้อมูลอัตราการเข้าร่วม
    $activity_data['participation_rate'] = $participation_rate;
    
    // ดึงข้อมูลนักเรียนที่เข้าร่วมกิจกรรม
    $students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                      (SELECT COUNT(*) + 1 FROM students 
                       WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                      CASE WHEN aa.attendance_id IS NOT NULL THEN 'present' ELSE 'absent' END as status
                      FROM students s
                      JOIN users u ON s.user_id = u.user_id
                      LEFT JOIN activity_attendance aa ON s.student_id = aa.student_id AND aa.activity_id = :activity_id
                      WHERE s.status = 'กำลังศึกษา'
                      AND s.current_class_id IN (
                          SELECT c.class_id FROM classes c 
                          JOIN activity_target_levels atl ON c.level = atl.level 
                          WHERE atl.activity_id = :activity_id
                      )
                      ORDER BY s.current_class_id, s.student_code";
    
    $stmt = $db->prepare($students_query);
    $stmt->bindParam(':activity_id', $activity_id, PDO::PARAM_INT);
    $stmt->execute();
    $students_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงข้อมูลนักเรียน
    $students = [];
    foreach ($students_data as $student) {
        $students[] = [
            'id' => $student['student_id'],
            'number' => $student['number'],
            'name' => ($student['title'] ? $student['title'] . ' ' : '') . $student['first_name'] . ' ' . $student['last_name'],
            'status' => $student['status']
        ];
    }
    
    // สร้างข้อมูลสำหรับส่งกลับ
    $response = [
        'success' => true,
        'activity' => $activity_data,
        'students' => $students
    ];
    
    // ส่งข้อมูล JSON กลับ
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
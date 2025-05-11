<?php
/**
 * export_attendance.php - ไฟล์สำหรับส่งออกข้อมูลการเข้าร่วมกิจกรรมเป็นไฟล์ Excel
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// รับพารามิเตอร์
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$level = isset($_GET['level']) ? $_GET['level'] : '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// ตรวจสอบว่ามีรหัสกิจกรรม
if (!$activity_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสกิจกรรม']);
    exit;
}

// ดึงข้อมูลกิจกรรม
try {
    $stmt = $conn->prepare("
        SELECT 
            a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
            a.description, a.required_attendance, a.academic_year_id,
            u.first_name, u.last_name
        FROM activities a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.activity_id = ?
    ");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลกิจกรรม']);
        exit;
    }
    
    // สร้างเงื่อนไข SQL สำหรับค้นหานักเรียน
    $conditions = [];
    $params = [];
    
    // เงื่อนไขรหัสกิจกรรม (สำหรับตาราง LEFT JOIN)
    $params[] = $activity_id;
    
    // เงื่อนไขแผนกวิชา
    if ($department_id) {
        $conditions[] = "d.department_id = ?";
        $params[] = $department_id;
    }
    
    // เงื่อนไขระดับชั้น
    if ($level) {
        $conditions[] = "c.level = ?";
        $params[] = $level;
    }
    
    // เงื่อนไขกลุ่มเรียน
    if ($class_id) {
        $conditions[] = "c.class_id = ?";
        $params[] = $class_id;
    }
    
    // เงื่อนไขค้นหา
    if ($search) {
        $conditions[] = "(s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // สร้าง WHERE clause จากเงื่อนไข
    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = " AND " . implode(" AND ", $conditions);
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
            s.status = 'กำลังศึกษา'
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
    
    // กำหนดชื่อไฟล์
    $filename = 'รายชื่อผู้เข้าร่วมกิจกรรม_' . $activity['activity_id'] . '_' . date('Ymd') . '.csv';
    
    // กำหนด header สำหรับไฟล์ CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // เปิด output stream
    $output = fopen('php://output', 'w');
    
    // กำหนด BOM สำหรับ UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // เขียน header ของไฟล์ CSV
    fputcsv($output, [
        'กิจกรรม: ' . $activity['activity_name'],
        'วันที่: ' . date('d/m/Y', strtotime($activity['activity_date'])),
        'สถานที่: ' . $activity['activity_location'],
    ]);
    
    fputcsv($output, ['']); // เว้นบรรทัด
    
    // เขียน header ของตาราง
    fputcsv($output, [
        'ลำดับ',
        'รหัสนักศึกษา',
        'ชื่อ-นามสกุล',
        'ระดับชั้น/กลุ่ม',
        'แผนกวิชา',
        'สถานะการเข้าร่วม',
        'หมายเหตุ'
    ]);
    
    // เขียนข้อมูลนักเรียน
    $i = 1;
    foreach ($students as $student) {
        $name = $student['title'] . $student['first_name'] . ' ' . $student['last_name'];
        $class = $student['level'] . '/' . $student['group_number'];
        
        // แปลงสถานะการเข้าร่วม
        $status = '';
        if ($student['attendance_status'] === 'present') {
            $status = 'เข้าร่วม';
        } elseif ($student['attendance_status'] === 'absent') {
            $status = 'ไม่เข้าร่วม';
        } else {
            $status = 'ไม่ระบุ';
        }
        
        fputcsv($output, [
            $i,
            $student['student_code'],
            $name,
            $class,
            $student['department_name'],
            $status,
            $student['remarks'] ?? ''
        ]);
        
        $i++;
    }
    
    // ปิด output stream
    fclose($output);
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี่
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
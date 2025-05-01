<?php
// api/student_parent_data.php
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเข้าถึงข้อมูล']);
    exit;
}

// รับพารามิเตอร์
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุรหัสนักเรียน']);
    exit;
}

try {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $student_query = "SELECT s.student_id, s.student_code, s.title, s.status,
                     u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
                     (SELECT COUNT(*) + 1 FROM students 
                      WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                     CONCAT(c.level, '/', d.department_code, '/', c.group_number) as class_name
                     FROM students s
                     JOIN users u ON s.user_id = u.user_id
                     JOIN classes c ON s.current_class_id = c.class_id
                     JOIN departments d ON c.department_id = d.department_id
                     WHERE s.student_id = :student_id";
    
    $stmt = $db->prepare($student_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ดึงข้อมูลผู้ปกครอง
    $parent_query = "SELECT p.parent_id, p.relationship, p.title,
                     u.first_name, u.last_name, u.phone_number, u.email, u.profile_picture
                     FROM parent_student_relation psr
                     JOIN parents p ON psr.parent_id = p.parent_id
                     JOIN users u ON p.user_id = u.user_id
                     WHERE psr.student_id = :student_id";
    
    $stmt = $db->prepare($parent_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $parents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลการเข้าแถว
    $attendance_query = "SELECT a.date, a.attendance_status, a.check_time, a.remarks, a.check_method
                         FROM attendance a
                         WHERE a.student_id = :student_id
                         ORDER BY a.date DESC
                         LIMIT 20";
    
    $stmt = $db->prepare($attendance_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างและส่งข้อมูล
    $result = [
        'success' => true,
        'student' => [
            'id' => $student_data['student_id'],
            'code' => $student_data['student_code'],
            'name' => ($student_data['title'] ?? '') . $student_data['first_name'] . ' ' . $student_data['last_name'],
            'number' => $student_data['number'],
            'class' => $student_data['class_name'],
            'profile_picture' => $student_data['profile_picture'],
            'phone' => $student_data['phone_number'],
            'email' => $student_data['email'],
            'status' => $student_data['status']
        ],
        'parents' => [],
        'attendance' => []
    ];
    
    // แปลงข้อมูลผู้ปกครอง
    foreach ($parents_data as $parent) {
        $result['parents'][] = [
            'id' => $parent['parent_id'],
            'name' => ($parent['title'] ?? '') . $parent['first_name'] . ' ' . $parent['last_name'],
            'relationship' => $parent['relationship'],
            'phone' => $parent['phone_number'],
            'email' => $parent['email'],
            'profile_picture' => $parent['profile_picture']
        ];
    }
    
    // แปลงข้อมูลการเข้าแถว
    foreach ($attendance_data as $attendance) {
        // แปลงวันที่เป็นรูปแบบไทย
        $thai_date = date('d/m/Y', strtotime($attendance['date']));
        $thai_date = str_replace(date('/Y'), '/' . (date('Y', strtotime($attendance['date'])) + 543), $thai_date);
        
        // แปลงสถานะเป็นภาษาไทย
        $status_text = '';
        $status_class = '';
        switch ($attendance['attendance_status']) {
            case 'present':
                $status_text = 'มาเรียน';
                $status_class = 'present';
                break;
            case 'late':
                $status_text = 'มาสาย';
                $status_class = 'late';
                break;
            case 'leave':
                $status_text = 'ลา';
                $status_class = 'leave';
                break;
            case 'absent':
                $status_text = 'ขาดเรียน';
                $status_class = 'absent';
                break;
        }
        
        $result['attendance'][] = [
            'date' => $attendance['date'],
            'thai_date' => $thai_date,
            'status' => $attendance['attendance_status'],
            'status_text' => $status_text,
            'status_class' => $status_class,
            'time' => $attendance['check_time'],
            'method' => $attendance['check_method'],
            'remarks' => $attendance['remarks']
        ];
    }
    
    // สรุปข้อมูลการเข้าแถว
    $summary_query = "SELECT 
                      SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                      SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_days,
                      SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_days,
                      SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                      COUNT(*) as total_days,
                      MAX(CASE WHEN attendance_status = 'absent' THEN date ELSE NULL END) as last_absent_date
                      FROM attendance
                      WHERE student_id = :student_id";
    
    $stmt = $db->prepare($summary_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($summary_data) {
        $total_days = $summary_data['total_days'] ?: 1; // ป้องกันการหารด้วย 0
        $attendance_percentage = round(
            (($summary_data['present_days'] + $summary_data['late_days']) / $total_days) * 100,
            1
        );
        
        // กำหนดสถานะ
        $status = 'danger';
        if ($attendance_percentage >= 80) {
            $status = 'good';
        } elseif ($attendance_percentage >= 70) {
            $status = 'warning';
        }
        
        // แปลงวันที่ขาดล่าสุดเป็นรูปแบบไทย
        $last_absent_date = '';
        if ($summary_data['last_absent_date']) {
            $last_absent_date = date('d/m/Y', strtotime($summary_data['last_absent_date']));
            $last_absent_date = str_replace(
                date('/Y', strtotime($summary_data['last_absent_date'])),
                '/' . (date('Y', strtotime($summary_data['last_absent_date'])) + 543),
                $last_absent_date
            );
        }
        
        $result['summary'] = [
            'present_days' => (int)$summary_data['present_days'],
            'late_days' => (int)$summary_data['late_days'],
            'leave_days' => (int)$summary_data['leave_days'],
            'absent_days' => (int)$summary_data['absent_days'],
            'total_days' => (int)$summary_data['total_days'],
            'attendance_percentage' => $attendance_percentage,
            'status' => $status,
            'last_absent_date' => $last_absent_date
        ];
    } else {
        $result['summary'] = [
            'present_days' => 0,
            'late_days' => 0,
            'leave_days' => 0,
            'absent_days' => 0,
            'total_days' => 0,
            'attendance_percentage' => 0,
            'status' => 'danger',
            'last_absent_date' => ''
        ];
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
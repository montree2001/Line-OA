<?php
/**
 * get_student_parents.php - API ดึงข้อมูลผู้ปกครองของนักเรียน
 * 
 * API นี้ใช้สำหรับดึงข้อมูลผู้ปกครองของนักเรียนรายบุคคล
 * รองรับการดึงข้อมูลตามรหัสนักเรียน
 */

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
require_once '../../lib/functions.php';

// ตรวจสอบว่ามีพารามิเตอร์ที่จำเป็นหรือไม่
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสนักเรียน'
    ]);
    exit;
}

// รับพารามิเตอร์
$student_id = intval($_GET['student_id']);

try {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $student_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                      u.phone_number, u.email,
                      (SELECT COUNT(*) + 1
                       FROM students
                       WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as student_number,
                      c.class_id, c.level, d.department_name, c.group_number, d.department_code,
                      CONCAT(c.level, '/', d.department_code, '/', c.group_number) as class_name
                      FROM students s
                      JOIN users u ON s.user_id = u.user_id
                      JOIN classes c ON s.current_class_id = c.class_id
                      JOIN departments d ON c.department_id = d.department_id
                      WHERE s.student_id = :student_id";
    
    $stmt = $db->prepare($student_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบข้อมูลนักเรียน'
        ]);
        exit;
    }
    
    // สร้างข้อมูลนักเรียนที่จะส่งกลับ
    $student_info = [
        'id' => $student['student_id'],
        'code' => $student['student_code'],
        'name' => ($student['title'] ? $student['title'] : '') . $student['first_name'] . ' ' . $student['last_name'],
        'number' => $student['student_number'],
        'class' => $student['class_name'],
        'level' => $student['level'],
        'department' => $student['department_name'],
        'group' => $student['group_number'],
        'profile_picture' => $student['profile_picture'],
        'phone_number' => $student['phone_number'],
        'email' => $student['email']
    ];
    
    // ดึงข้อมูลครูที่ปรึกษา
    $advisor_query = "SELECT t.teacher_id, t.title, u.first_name, u.last_name, u.phone_number, u.email, 
                     u.profile_picture, d.department_name, ca.is_primary
                     FROM class_advisors ca
                     JOIN teachers t ON ca.teacher_id = t.teacher_id
                     JOIN users u ON t.user_id = u.user_id
                     LEFT JOIN departments d ON t.department_id = d.department_id
                     WHERE ca.class_id = :class_id
                     ORDER BY ca.is_primary DESC";
    
    $stmt = $db->prepare($advisor_query);
    $stmt->bindParam(':class_id', $student['class_id'], PDO::PARAM_INT);
    $stmt->execute();
    $advisors_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $advisors = [];
    foreach ($advisors_data as $advisor) {
        $advisors[] = [
            'id' => $advisor['teacher_id'],
            'name' => ($advisor['title'] ? $advisor['title'] . ' ' : '') . $advisor['first_name'] . ' ' . $advisor['last_name'],
            'department' => $advisor['department_name'],
            'is_primary' => (bool)$advisor['is_primary'],
            'phone_number' => $advisor['phone_number'],
            'email' => $advisor['email'],
            'profile_picture' => $advisor['profile_picture']
        ];
    }
    
    // ดึงข้อมูลผู้ปกครอง
    $parents_query = "SELECT p.parent_id, p.relationship, p.title, u.first_name, u.last_name,
                     u.phone_number, u.email, u.profile_picture, u.line_id
                     FROM parent_student_relation psr
                     JOIN parents p ON psr.parent_id = p.parent_id
                     JOIN users u ON p.user_id = u.user_id
                     WHERE psr.student_id = :student_id";
    
    $stmt = $db->prepare($parents_query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $parents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $parents = [];
    foreach ($parents_data as $parent) {
        $parents[] = [
            'id' => $parent['parent_id'],
            'name' => ($parent['title'] ? $parent['title'] . ' ' : '') . $parent['first_name'] . ' ' . $parent['last_name'],
            'relationship' => $parent['relationship'],
            'phone_number' => $parent['phone_number'],
            'email' => $parent['email'],
            'profile_picture' => $parent['profile_picture'],
            'has_line' => !empty($parent['line_id'])
        ];
    }
    
    // สร้างข้อมูลสำหรับส่งกลับ
    $result = [
        'success' => true,
        'student' => $student_info,
        'advisors' => $advisors,
        'parents' => $parents
    ];
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
    
    // บันทึกข้อผิดพลาด
    error_log('Error in get_student_parents.php: ' . $e->getMessage());
}
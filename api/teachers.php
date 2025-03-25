<?php
/**
 * API สำหรับข้อมูลครูที่ปรึกษา
 * ให้ข้อมูลในรูปแบบ JSON
 */

// กำหนดให้แสดงผลเป็น JSON
header('Content-Type: application/json');

// โหลดไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// โหลดคลาสสำหรับจัดการข้อมูลครู
require_once '../../models/Teacher.php';
$teacherModel = new Teacher();

// ดึง ID ครูจาก parameter (ถ้ามี)
$teacherId = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($teacherId > 0) {
        // ดึงข้อมูลครูตาม ID
        $teacher = $teacherModel->getTeacherById($teacherId);
        
        if ($teacher) {
            // คืนค่าข้อมูลครู
            echo json_encode([
                'success' => true,
                'teacher' => $teacher
            ]);
        } else {
            // ไม่พบข้อมูลครู
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลครูที่ต้องการ'
            ]);
        }
    } else {
        // ถ้าไม่ระบุ ID ให้ดึงข้อมูลครูทั้งหมด
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        // สร้างตัวกรอง
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        if (isset($_GET['department']) && $_GET['department'] !== '') {
            $filters['department'] = $_GET['department'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $filters['status'] = $_GET['status'];
        }
        
        $teachers = $teacherModel->getAllTeachers($limit, $offset, $filters);
        $total = $teacherModel->getTeacherStats()['total'];
        
        echo json_encode([
            'success' => true,
            'total' => $total,
            'teachers' => $teachers
        ]);
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
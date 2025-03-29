<?php
/**
 * students_api.php - API สำหรับจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

/* // ตรวจสอบสิทธิ์การเข้าถึง (ถ้าไม่ใช่ admin หรือ teacher ไม่อนุญาตให้เข้าถึง API นี้)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง API นี้'
    ]);
    exit;
} */

// เชื่อมต่อไฟล์ที่จำเป็น
require_once '../../db_connect.php';
require_once '../models/students_model.php';

// ตั้งค่า header สำหรับ JSON
header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการร้องขอ
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    handleGetRequest();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostRequest();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'วิธีการร้องขอไม่ถูกต้อง'
    ]);
    exit;
}

// จัดการการร้องขอแบบ GET
function handleGetRequest() {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_student':
            $student_id = $_GET['student_id'] ?? 0;
            $student = getStudentById($student_id);
            
            if ($student) {
                echo json_encode([
                    'success' => true,
                    'student' => $student
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ]);
            }
            break;
            
        case 'get_students':
            $filters = $_GET;
            $students = getAllStudents($filters);
            
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;
            
        case 'get_classes':
            $classes = getAllClasses();
            
            echo json_encode([
                'success' => true,
                'classes' => $classes
            ]);
            break;
            
        case 'get_advisors':
            $advisors = getAllAdvisors();
            
            echo json_encode([
                'success' => true,
                'advisors' => $advisors
            ]);
            break;
            
        case 'get_statistics':
            $stats = getStudentStatistics();
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบการดำเนินการที่ระบุ'
            ]);
            break;
    }
}

// จัดการการร้องขอแบบ POST
function handlePostRequest() {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_student':
            $result = addStudent($_POST);
            echo json_encode($result);
            break;
            
        case 'update_student':
            $result = updateStudent($_POST);
            echo json_encode($result);
            break;
            
        case 'delete_student':
            $student_id = $_POST['student_id'] ?? 0;
            $result = deleteStudent($student_id);
            echo json_encode($result);
            break;
            
        case 'import_students':
            $options = [
                'skip_header' => isset($_POST['skip_header']) && $_POST['skip_header'] === 'on',
                'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === 'on'
            ];
            
            $result = importStudentsFromExcel($_FILES['import_file'], $options);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบการดำเนินการที่ระบุ'
            ]);
            break;
    }
}
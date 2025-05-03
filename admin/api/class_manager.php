<?php
/**
 * class_manager.php - API สำหรับจัดการชั้นเรียนและแผนกวิชา
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// ตั้งค่า header สำหรับ API
header('Content-Type: application/json; charset=UTF-8');

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// นำเข้าไฟล์ฟังก์ชันที่จำเป็น
$required_files = [
    '../includes/classes_functions.php',
    '../includes/department_functions.php',
    '../includes/api_functions.php'
];

// ตรวจสอบและนำเข้าไฟล์
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่พบไฟล์: $file"
        ]);
        exit;
    }
    require_once $file;
}

try {
    global $conn;
    $conn = getDB();
    
    // ตรวจสอบว่าเป็นการร้องขอแบบ GET หรือ POST
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่รองรับวิธีการร้องขอนี้'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

/**
 * จัดการคำขอแบบ GET
 */
function handleGetRequest() {
    // ดึงพารามิเตอร์
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_department_details':
            // ดึงข้อมูลรายละเอียดแผนกวิชา
            $department_id = $_GET['department_id'] ?? '';
            $result = getDepartmentDetails($department_id);
            echo json_encode($result);
            break;
            
        case 'get_class_details':
            // ดึงข้อมูลรายละเอียดชั้นเรียน
            $class_id = $_GET['class_id'] ?? '';
            $result = getDetailedClassInfo($class_id);
            echo json_encode($result);
            break;
            
        case 'get_class_advisors':
            // ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
            $class_id = $_GET['class_id'] ?? '';
            $result = getClassAdvisors($class_id);
            echo json_encode($result);
            break;
            
        case 'download_report':
            // ดาวน์โหลดรายงานชั้นเรียน
            $class_id = $_GET['class_id'] ?? '';
            if (!$class_id) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ไม่ระบุรหัสชั้นเรียน'
                ]);
                exit;
            }
            downloadClassReport($class_id);
            exit; // ออกจากสคริปต์หลังจากดาวน์โหลด
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่รู้จัก action: ' . $action
            ]);
            break;
    }
}

/**
 * จัดการคำขอแบบ POST
 */
function handlePostRequest() {
    // ดึงพารามิเตอร์
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_department':
            // เพิ่มแผนกวิชาใหม่
            $department_name = $_POST['department_name'] ?? '';
            $department_code = $_POST['department_code'] ?? '';
            
            if (empty($department_name)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุชื่อแผนกวิชา'
                ]);
                exit;
            }
            
            $result = addDepartment([
                'department_name' => $department_name,
                'department_code' => $department_code
            ]);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'department_id' => $result['department_id'] ?? null
            ]);
            break;
            
        case 'edit_department':
            // แก้ไขแผนกวิชา
            $department_id = $_POST['department_id'] ?? '';
            $department_name = $_POST['department_name'] ?? '';
            
            if (empty($department_id) || empty($department_name)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'
                ]);
                exit;
            }
            
            $result = updateDepartment([
                'department_id' => $department_id,
                'department_name' => $department_name
            ]);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'delete_department':
            // ลบแผนกวิชา
            $department_id = $_POST['department_id'] ?? '';
            
            if (empty($department_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุรหัสแผนกวิชา'
                ]);
                exit;
            }
            
            $result = deleteDepartment($department_id);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'add_class':
            // เพิ่มชั้นเรียนใหม่
            $result = addClass($_POST);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'class_id' => $result['class_id'] ?? null
            ]);
            break;
            
        case 'edit_class':
            // แก้ไขชั้นเรียน
            $result = updateClass($_POST);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'delete_class':
            // ลบชั้นเรียน
            $class_id = $_POST['class_id'] ?? '';
            
            if (empty($class_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุรหัสชั้นเรียน'
                ]);
                exit;
            }
            
            $result = deleteClass($class_id);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'get_department_details':
            // ดึงข้อมูลรายละเอียดแผนกวิชา
            $department_id = $_POST['department_id'] ?? '';
            $result = getDepartmentDetails($department_id);
            echo json_encode($result);
            break;
            
        case 'get_class_details':
            // ดึงข้อมูลรายละเอียดชั้นเรียน
            $class_id = $_POST['class_id'] ?? '';
            $result = getDetailedClassInfo($class_id);
            echo json_encode($result);
            break;
            
        case 'get_class_advisors':
            // ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
            $class_id = $_POST['class_id'] ?? '';
            $result = getClassAdvisors($class_id);
            echo json_encode($result);
            break;
            
        case 'manage_advisors':
            // จัดการครูที่ปรึกษา
            $class_id = $_POST['class_id'] ?? '';
            $changes = json_decode($_POST['changes'] ?? '[]', true);
            
            if (empty($class_id) || !is_array($changes)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ข้อมูลไม่ถูกต้อง'
                ]);
                exit;
            }
            
            $result = updateClassAdvisors($class_id, $changes);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'promote_students':
            // เลื่อนชั้นนักเรียน
            $from_academic_year_id = $_POST['from_academic_year_id'] ?? '';
            $to_academic_year_id = $_POST['to_academic_year_id'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if (empty($from_academic_year_id) || empty($to_academic_year_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'
                ]);
                exit;
            }
            
            $result = promoteStudents([
                'from_academic_year_id' => $from_academic_year_id,
                'to_academic_year_id' => $to_academic_year_id,
                'notes' => $notes,
                'admin_id' => $_SESSION['user_id'] ?? 1
            ]);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'batch_id' => $result['batch_id'] ?? null,
                'promoted_count' => $result['promoted_count'] ?? 0,
                'graduated_count' => $result['graduated_count'] ?? 0
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่รู้จัก action: ' . $action
            ]);
            break;
    }
}
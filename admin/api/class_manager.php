<?php
// class_manager.php - API สำหรับจัดการชั้นเรียนและแผนกวิชา

header('Content-Type: application/json');
session_start();

// ตรวจสอบการล็อกอินและสิทธิ์การเข้าถึง (สามารถปิดการตรวจสอบนี้ระหว่างการพัฒนา)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

require_once '../../db_connect.php';
require_once '../includes/department_functions.php';
require_once '../includes/classes_functions.php';

// รับ action จาก request
$action = $_POST['action'] ?? '';

try {
    $db = getDB();
    $response = ['status' => 'error', 'message' => 'ไม่รู้จัก action นี้'];
    
    switch ($action) {
        case 'add_department':
            $departmentName = $_POST['department_name'] ?? '';
            $departmentCode = $_POST['department_code'] ?? '';
            
            if (empty($departmentName)) {
                $response = ['status' => 'error', 'message' => 'กรุณาระบุชื่อแผนกวิชา'];
                break;
            }
            
            $data = [
                'department_name' => $departmentName,
                'department_code' => $departmentCode
            ];
            
            $result = addDepartment($data);
            
            if ($result['success']) {
                $response = [
                    'status' => 'success', 
                    'message' => $result['message'],
                    'department_id' => $result['department_id'] ?? null,
                    'department_code' => $result['department_code'] ?? null
                ];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'edit_department':
            $departmentId = $_POST['department_id'] ?? '';
            $departmentName = $_POST['department_name'] ?? '';
            
            if (empty($departmentId) || empty($departmentName)) {
                $response = ['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'];
                break;
            }
            
            $data = [
                'department_id' => $departmentId,
                'department_name' => $departmentName
            ];
            
            $result = updateDepartment($data);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message']];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'delete_department':
            $departmentId = $_POST['department_id'] ?? '';
            
            if (empty($departmentId)) {
                $response = ['status' => 'error', 'message' => 'ไม่ระบุรหัสแผนกวิชา'];
                break;
            }
            
            $result = deleteDepartment($departmentId);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message']];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'add_class':
            $result = addClass($_POST);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message'], 'class_id' => $result['class_id'] ?? null];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'edit_class':
            $result = updateClass($_POST);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message']];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'delete_class':
            $classId = $_POST['class_id'] ?? '';
            
            if (empty($classId)) {
                $response = ['status' => 'error', 'message' => 'ไม่ระบุรหัสชั้นเรียน'];
                break;
            }
            
            $result = deleteClass($classId);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message']];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'manage_advisors':
            $classId = $_POST['class_id'] ?? '';
            $changes = json_decode($_POST['changes'] ?? '[]', true);
            
            if (empty($classId) || !is_array($changes)) {
                $response = ['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง'];
                break;
            }
            
            $data = [
                'class_id' => $classId,
                'changes' => $changes
            ];
            
            $result = manageAdvisors($data);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message']];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'promote_students':
            $fromAcademicYearId = $_POST['from_academic_year_id'] ?? '';
            $toAcademicYearId = $_POST['to_academic_year_id'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if (empty($fromAcademicYearId) || empty($toAcademicYearId)) {
                $response = ['status' => 'error', 'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'];
                break;
            }
            
            $data = [
                'from_academic_year_id' => $fromAcademicYearId,
                'to_academic_year_id' => $toAcademicYearId,
                'notes' => $notes
            ];
            
            $result = promoteStudents($data);
            
            if ($result['success']) {
                $response = ['status' => 'success', 'message' => $result['message'], 'batch_id' => $result['batch_id'] ?? null];
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'get_class_details':
            $classId = $_POST['class_id'] ?? '';
            
            if (empty($classId)) {
                $response = ['status' => 'error', 'message' => 'ไม่ระบุรหัสชั้นเรียน'];
                break;
            }
            
            $result = getDetailedClassInfo($classId);
            
            if ($result['success']) {
                $response = $result;
                $response['status'] = 'success';
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'get_class_advisors':
            $classId = $_POST['class_id'] ?? '';
            
            if (empty($classId)) {
                $response = ['status' => 'error', 'message' => 'ไม่ระบุรหัสชั้นเรียน'];
                break;
            }
            
            $result = getClassAdvisors($classId);
            
            if ($result['success']) {
                $response = $result;
                $response['status'] = 'success';
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'get_department_details':
            $departmentId = $_POST['department_id'] ?? '';
            
            if (empty($departmentId)) {
                $response = ['status' => 'error', 'message' => 'ไม่ระบุรหัสแผนกวิชา'];
                break;
            }
            
            $result = getDepartmentDetails($departmentId);
            
            if ($result['success']) {
                $response = $result;
                $response['status'] = 'success';
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        case 'get_all_departments':
            $activeOnly = isset($_POST['active_only']) ? (bool)$_POST['active_only'] : true;
            
            $result = getAllDepartments($activeOnly);
            
            if ($result['success']) {
                $response = $result;
                $response['status'] = 'success';
            } else {
                $response = ['status' => 'error', 'message' => $result['message']];
            }
            break;
            
        default:
            $response = ['status' => 'error', 'message' => 'ไม่รู้จัก action นี้'];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
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

/* // ตรวจสอบสิทธิ์การเข้าถึง (ถ้าไม่ใช่ admin ไม่อนุญาตให้เข้าถึง API นี้)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่มีสิทธิ์เข้าถึง API นี้'
    ]);
    exit;
} */

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

try {
    $db = getDB();
    
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
            
            $result = manageAdvisors([
                'class_id' => $class_id,
                'changes' => $changes
            ]);
            
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
                'notes' => $notes
            ]);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'batch_id' => $result['batch_id'] ?? null
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

/**
 * เพิ่มแผนกวิชาใหม่
 * 
 * @param array $data ข้อมูลแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function addDepartment($data) {
    global $db;
    
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณาระบุชื่อแผนกวิชา'];
        }
        
        // สร้างรหัสแผนก (ถ้าไม่มี)
        $department_code = isset($data['department_code']) && !empty($data['department_code']) 
            ? strtoupper($data['department_code']) 
            : generateDepartmentCode($data['department_name']);
        
        // ตรวจสอบว่ามีแผนกซ้ำหรือไม่
        $checkQuery = "SELECT department_id FROM departments 
                      WHERE department_code = :code OR department_name = :name";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':code', $department_code, PDO::PARAM_STR);
        $checkStmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'แผนกวิชานี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มแผนกวิชาใหม่
        $query = "INSERT INTO departments (department_code, department_name, is_active) 
                 VALUES (:code, :name, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':code', $department_code, PDO::PARAM_STR);
        $stmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $stmt->execute();
        
        $department_id = $db->lastInsertId();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'department_id' => $department_id,
            'department_code' => $department_code,
            'department_name' => $data['department_name']
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'add_department', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return [
            'success' => true, 
            'message' => 'เพิ่มแผนกวิชาสำเร็จ', 
            'department_id' => $department_id,
            'department_code' => $department_code
        ];
    } catch (PDOException $e) {
        error_log("Error adding department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * แก้ไขข้อมูลแผนกวิชา
 * 
 * @param array $data ข้อมูลแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function updateDepartment($data) {
    global $db;
    
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_id']) || empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีแผนกวิชานี้ในระบบหรือไม่
        $checkQuery = "SELECT department_id FROM departments WHERE department_id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $data['department_id'], PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
        
        // ตรวจสอบว่ามีชื่อแผนกวิชาซ้ำหรือไม่ (ยกเว้นแผนกปัจจุบัน)
        $dupeQuery = "SELECT department_id FROM departments 
                     WHERE department_name = :name AND department_id != :id";
        $dupeStmt = $db->prepare($dupeQuery);
        $dupeStmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $dupeStmt->bindParam(':id', $data['department_id'], PDO::PARAM_STR);
        $dupeStmt->execute();
        
        if ($dupeStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'มีแผนกวิชาชื่อนี้อยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลแผนกวิชา
        $query = "UPDATE departments SET department_name = :name WHERE department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $data['department_name'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $data['department_id'], PDO::PARAM_STR);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'department_id' => $data['department_id'],
            'department_name' => $data['department_name']
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'edit_department', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'แก้ไขข้อมูลแผนกวิชาสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error updating department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * ลบแผนกวิชา
 * 
 * @param string $department_id รหัสแผนกวิชา
 * @return array ผลลัพธ์การดำเนินการ
 */
function deleteDepartment($department_id) {
    global $db;
    
    try {
        // ตรวจสอบว่ามีชั้นเรียนที่ใช้แผนกนี้อยู่หรือไม่
        $checkClassesQuery = "SELECT COUNT(*) AS count FROM classes WHERE department_id = :id";
        $checkClassesStmt = $db->prepare($checkClassesQuery);
        $checkClassesStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $checkClassesStmt->execute();
        $classesCount = $checkClassesStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($classesCount > 0) {
            return ['success' => false, 'message' => "ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีชั้นเรียนที่ใช้แผนกนี้อยู่ {$classesCount} ชั้นเรียน"];
        }
        
        // ตรวจสอบว่ามีครูที่อยู่ในแผนกนี้หรือไม่
        $checkTeachersQuery = "SELECT COUNT(*) AS count FROM teachers WHERE department_id = :id";
        $checkTeachersStmt = $db->prepare($checkTeachersQuery);
        $checkTeachersStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $checkTeachersStmt->execute();
        $teachersCount = $checkTeachersStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($teachersCount > 0) {
            return ['success' => false, 'message' => "ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีครูที่อยู่ในแผนกนี้ {$teachersCount} คน"];
        }
        
        // ดึงข้อมูลแผนกวิชาก่อนลบ
        $getDeptQuery = "SELECT department_code, department_name FROM departments WHERE department_id = :id";
        $getDeptStmt = $db->prepare($getDeptQuery);
        $getDeptStmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $getDeptStmt->execute();
        $departmentInfo = $getDeptStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$departmentInfo) {
            return ['success' => false, 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
        
        // ลบแผนกวิชา
        $query = "DELETE FROM departments WHERE department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $department_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'department_id' => $department_id,
            'department_code' => $departmentInfo['department_code'],
            'department_name' => $departmentInfo['department_name']
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'remove_department', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'ลบแผนกวิชาสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error deleting department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลแผนกวิชา
 * 
 * @param string $department_id รหัสแผนกวิชา
 * @return array ข้อมูลแผนกวิชา
 */
function getDepartmentDetails($department_id) {
    global $db;
    
    try {
        $query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM classes c WHERE c.department_id = d.department_id) AS class_count,
                 (SELECT COUNT(*) FROM students s 
                  JOIN classes c ON s.current_class_id = c.class_id 
                  WHERE c.department_id = d.department_id AND s.status = 'กำลังศึกษา') AS student_count,
                 (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.department_id) AS teacher_count
                 FROM departments d
                 WHERE d.department_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $department_id, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'department' => $department];
        } else {
            return ['status' => 'error', 'message' => 'ไม่พบแผนกวิชานี้ในระบบ'];
        }
    } catch (PDOException $e) {
        error_log("Error getting department details: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลแผนกวิชา: ' . $e->getMessage()];
    }
}

/**
 * สร้างรหัสแผนกวิชาจากชื่อแผนก
 * 
 * @param string $department_name ชื่อแผนกวิชา
 * @return string รหัสแผนกวิชา
 */
function generateDepartmentCode($department_name) {
    global $db;
    
    try {
        // ดึงเฉพาะตัวอักษรภาษาอังกฤษ
        preg_match_all('/[a-zA-Z]+/', $department_name, $matches);
        $words = $matches[0];
        
        if (count($words) > 0) {
            // ถ้ามีคำภาษาอังกฤษ ใช้อักษรตัวแรกของแต่ละคำ
            $code = '';
            foreach ($words as $word) {
                $code .= strtoupper(substr($word, 0, 1));
            }
            
            // ถ้าสั้นเกินไป ให้เอาตัวที่ 2 ของคำแรกมาเพิ่ม
            if (strlen($code) < 3 && isset($words[0]) && strlen($words[0]) > 1) {
                $code .= strtoupper(substr($words[0], 1, 1));
            }
        } else {
            // ถ้าไม่มีคำภาษาอังกฤษ ใช้ตัวอักษร 4 ตัวแรกของชื่อแผนก
            $code = strtoupper(substr($department_name, 0, 4));
        }
        
        // ถ้ายังสั้นเกินไป ให้เติม X
        while (strlen($code) < 3) {
            $code .= 'X';
        }
        
        // ตัดให้ไม่เกิน 10 ตัวอักษร
        $code = substr($code, 0, 10);
        
        // ตรวจสอบว่ามีรหัสซ้ำในระบบหรือไม่
        $found_unique = false;
        $suffix = '';
        $attempt = 0;
        
        while (!$found_unique && $attempt < 100) {
            $test_code = $code . $suffix;
            
            $query = "SELECT COUNT(*) AS count FROM departments WHERE department_code = :code";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':code', $test_code, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                $found_unique = true;
                $code = $test_code;
            } else {
                $attempt++;
                $suffix = $attempt;
            }
        }
        
        return $code;
    } catch (Exception $e) {
        error_log("Error generating department code: " . $e->getMessage());
        // หากเกิดข้อผิดพลาด ให้สร้างรหัสแบบสุ่ม
        return 'DEPT' . rand(100, 999);
    }
}

/**
 * เพิ่มชั้นเรียนใหม่
 * 
 * @param array $data ข้อมูลชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function addClass($data) {
    global $db;
    
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['academic_year_id']) || empty($data['level']) || 
            empty($data['department_id']) || empty($data['group_number'])) {
            return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีชั้นเรียนซ้ำหรือไม่
        $checkQuery = "SELECT class_id FROM classes 
                       WHERE academic_year_id = :academic_year_id 
                       AND level = :level 
                       AND department_id = :department_id 
                       AND group_number = :group_number";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_STR);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มชั้นเรียนใหม่
        $classroom = isset($data['classroom']) ? $data['classroom'] : null;
        $query = "INSERT INTO classes (academic_year_id, level, department_id, group_number, classroom, is_active) 
                  VALUES (:academic_year_id, :level, :department_id, :group_number, :classroom, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $stmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_STR);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $classroom, PDO::PARAM_STR);
        $stmt->execute();
        
        $class_id = $db->lastInsertId();
        
        // เพิ่มครูที่ปรึกษา (ถ้ามี)
        if (!empty($data['advisor_id'])) {
            $advisorQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                            VALUES (:class_id, :teacher_id, 1)";
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $advisorStmt->bindParam(':teacher_id', $data['advisor_id'], PDO::PARAM_INT);
            $advisorStmt->execute();
        }
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'class_id' => $class_id,
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'add_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'เพิ่มชั้นเรียนสำเร็จ', 'class_id' => $class_id];
    } catch (PDOException $e) {
        error_log("Error adding class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * แก้ไขข้อมูลชั้นเรียน
 * 
 * @param array $data ข้อมูลชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function updateClass($data) {
    global $db;
    
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['class_id']) || empty($data['academic_year_id']) || 
            empty($data['level']) || empty($data['department_id']) || empty($data['group_number'])) {
            return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีชั้นเรียนซ้ำหรือไม่ (ยกเว้นชั้นเรียนปัจจุบัน)
        $checkQuery = "SELECT class_id FROM classes 
                       WHERE academic_year_id = :academic_year_id 
                       AND level = :level 
                       AND department_id = :department_id 
                       AND group_number = :group_number
                       AND class_id != :class_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_STR);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลชั้นเรียน
        $classroom = isset($data['classroom']) ? $data['classroom'] : null;
        $query = "UPDATE classes 
                  SET academic_year_id = :academic_year_id,
                      level = :level,
                      department_id = :department_id,
                      group_number = :group_number,
                      classroom = :classroom
                  WHERE class_id = :class_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $stmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_STR);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $classroom, PDO::PARAM_STR);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'class_id' => $data['class_id'],
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'edit_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'แก้ไขข้อมูลชั้นเรียนสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error updating class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูลชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * ลบชั้นเรียน
 * 
 * @param int $class_id รหัสชั้นเรียน
 * @return array ผลลัพธ์การดำเนินการ
 */
function deleteClass($class_id) {
    global $db;
    
    try {
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
        $checkQuery = "SELECT COUNT(*) AS count FROM students WHERE current_class_id = :class_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => "ไม่สามารถลบชั้นเรียนได้ เนื่องจากมีนักเรียนในชั้นเรียนนี้ {$result['count']} คน"];
        }
        
        // ดึงข้อมูลชั้นเรียนก่อนลบ
        $getClassQuery = "SELECT c.level, c.group_number, d.department_name, ay.year, ay.semester 
                         FROM classes c 
                         JOIN departments d ON c.department_id = d.department_id 
                         JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id 
                         WHERE c.class_id = :class_id";
        $getClassStmt = $db->prepare($getClassQuery);
        $getClassStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $getClassStmt->execute();
        $classInfo = $getClassStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$classInfo) {
            return ['success' => false, 'message' => 'ไม่พบชั้นเรียนนี้ในระบบ'];
        }
        
        // ลบข้อมูลครูที่ปรึกษาของชั้นเรียนก่อน
        $deleteAdvisorsQuery = "DELETE FROM class_advisors WHERE class_id = :class_id";
        $deleteAdvisorsStmt = $db->prepare($deleteAdvisorsQuery);
        $deleteAdvisorsStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $deleteAdvisorsStmt->execute();
        
        // ลบชั้นเรียน
        $query = "DELETE FROM classes WHERE class_id = :class_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1;
        $details = json_encode([
            'class_id' => $class_id,
            'level' => $classInfo['level'],
            'group_number' => $classInfo['group_number'],
            'department_name' => $classInfo['department_name'],
            'academic_year' => "{$classInfo['year']} ภาคเรียนที่ {$classInfo['semester']}"
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                        VALUES (:admin_id, 'remove_class', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'ลบชั้นเรียนสำเร็จ'];
    } catch (PDOException $e) {
        error_log("Error deleting class: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลรายละเอียดชั้นเรียนพร้อมนักเรียนและครูที่ปรึกษา
 * 
 * @param int $class_id รหัสชั้นเรียน
 * @return array ข้อมูลรายละเอียดชั้นเรียน
 */
function getDetailedClassInfo($class_id) {
    global $db;
    
    try {
        // ดึงข้อมูลพื้นฐานของชั้นเรียน
        $classQuery = "SELECT c.*, d.department_name, ay.year, ay.semester, 
                      (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
                      FROM classes c
                      JOIN departments d ON c.department_id = d.department_id
                      JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                      WHERE c.class_id = :class_id";
        $classStmt = $db->prepare($classQuery);
        $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $classStmt->execute();
        
        if ($classStmt->rowCount() == 0) {
            return ['status' => 'error', 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
        
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลครูที่ปรึกษา
        $advisorQuery = "SELECT t.teacher_id AS id, 
                        CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                        t.position, ca.is_primary
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        WHERE ca.class_id = :class_id
                        ORDER BY ca.is_primary DESC, t.first_name, t.last_name";
        $advisorStmt = $db->prepare($advisorQuery);
        $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $advisorStmt->execute();
        $advisors = $advisorStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลนักเรียน
        $studentQuery = "SELECT s.student_id AS id, s.student_code AS code, 
                        CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) AS name,
                        COALESCE(sar.total_attendance_days, 0) AS attendance,
                        (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) AS total,
                        CASE
                            WHEN (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) > 0 
                            THEN (COALESCE(sar.total_attendance_days, 0) / (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) * 100)
                            ELSE 100
                        END AS percent,
                        CASE
                            WHEN (COALESCE(sar.total_attendance_days, 0) / NULLIF((COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)), 0) * 100) > 90 THEN 'ปกติ'
                            WHEN (COALESCE(sar.total_attendance_days, 0) / NULLIF((COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)), 0) * 100) > 75 THEN 'ต้องระวัง'
                            ELSE 'เสี่ยง'
                        END AS status
                        FROM students s
                        JOIN users u ON s.user_id = u.user_id
                        LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = :academic_year_id
                        WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                        ORDER BY u.first_name, u.last_name";
        $studentStmt = $db->prepare($studentQuery);
        $studentStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $studentStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $studentStmt->execute();
        $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงสถิติการเข้าแถว
        $attendanceQuery = "SELECT 
                          SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS present_days,
                          COUNT(*) - SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS absent_days,
                          COUNT(*) AS total_days,
                          (SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100) AS overall_rate
                          FROM attendance a
                          JOIN students s ON a.student_id = s.student_id
                          WHERE s.current_class_id = :class_id
                          AND a.academic_year_id = :academic_year_id";
        $attendanceStmt = $db->prepare($attendanceQuery);
        $attendanceStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $attendanceStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $attendanceStmt->execute();
        $attendanceStats = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงสถิติรายเดือน
        $monthlyQuery = "SELECT 
                        MONTH(a.date) AS month_num,
                        CASE 
                            WHEN MONTH(a.date) = 1 THEN 'ม.ค.'
                            WHEN MONTH(a.date) = 2 THEN 'ก.พ.'
                            WHEN MONTH(a.date) = 3 THEN 'มี.ค.'
                            WHEN MONTH(a.date) = 4 THEN 'เม.ย.'
                            WHEN MONTH(a.date) = 5 THEN 'พ.ค.'
                            WHEN MONTH(a.date) = 6 THEN 'มิ.ย.'
                            WHEN MONTH(a.date) = 7 THEN 'ก.ค.'
                            WHEN MONTH(a.date) = 8 THEN 'ส.ค.'
                            WHEN MONTH(a.date) = 9 THEN 'ก.ย.'
                            WHEN MONTH(a.date) = 10 THEN 'ต.ค.'
                            WHEN MONTH(a.date) = 11 THEN 'พ.ย.'
                            WHEN MONTH(a.date) = 12 THEN 'ธ.ค.'
                        END AS month,
                        SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) AS present,
                        SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) AS absent
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id
                        WHERE s.current_class_id = :class_id
                        AND a.academic_year_id = :academic_year_id
                        GROUP BY MONTH(a.date)
                        ORDER BY month_num";
        $monthlyStmt = $db->prepare($monthlyQuery);
        $monthlyStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $monthlyStmt->bindParam(':academic_year_id', $class['academic_year_id'], PDO::PARAM_INT);
        $monthlyStmt->execute();
        $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ถ้าไม่มีข้อมูลจริง ให้สร้างข้อมูลตัวอย่างสำหรับการแสดงผล
        if (!$attendanceStats || !isset($attendanceStats['total_days']) || $attendanceStats['total_days'] == 0) {
            $attendanceStats = [
                'present_days' => 0,
                'absent_days' => 0,
                'total_days' => 0,
                'overall_rate' => 0
            ];
        }
        
        if (empty($monthlyStats)) {
            $monthlyStats = [
                ['month' => 'ม.ค.', 'present' => 90, 'absent' => 10],
                ['month' => 'ก.พ.', 'present' => 85, 'absent' => 15],
                ['month' => 'มี.ค.', 'present' => 88, 'absent' => 12],
                ['month' => 'เม.ย.', 'present' => 92, 'absent' => 8],
                ['month' => 'พ.ค.', 'present' => 94, 'absent' => 6]
            ];
        }
        
        // รวมข้อมูลสถิติการเข้าแถว
        $attendance_stats = [
            'present_days' => floatval($attendanceStats['present_days'] ?? 0),
            'absent_days' => floatval($attendanceStats['absent_days'] ?? 0),
            'total_days' => floatval($attendanceStats['total_days'] ?? 0),
            'overall_rate' => floatval($attendanceStats['overall_rate'] ?? 0),
            'monthly' => $monthlyStats
        ];
        
        return [
            'status' => 'success',
            'class' => $class,
            'advisors' => $advisors,
            'students' => $students,
            'attendance_stats' => $attendance_stats
        ];
    } catch (PDOException $e) {
        error_log("Error getting detailed class info: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลรายละเอียดชั้นเรียน: ' . $e->getMessage()];
    }
}

/**
 * ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
 * 
 * @param int $class_id รหัสชั้นเรียน
 * @return array ข้อมูลครูที่ปรึกษา
 */
function getClassAdvisors($class_id) {
    global $db;
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $classQuery = "SELECT c.class_id, c.level, c.group_number, d.department_name 
                      FROM classes c 
                      JOIN departments d ON c.department_id = d.department_id 
                      WHERE c.class_id = :class_id";
        $classStmt = $db->prepare($classQuery);
        $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $classStmt->execute();
        
        if ($classStmt->rowCount() == 0) {
            return ['status' => 'error', 'message' => 'ไม่พบข้อมูลชั้นเรียน'];
        }
        
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);
        $class_name = "{$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']}";
        
        // ดึงข้อมูลครูที่ปรึกษา
        $advisorQuery = "SELECT t.teacher_id AS id, 
                        CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                        t.position, ca.is_primary
                        FROM class_advisors ca
                        JOIN teachers t ON ca.teacher_id = t.teacher_id
                        WHERE ca.class_id = :class_id
                        ORDER BY ca.is_primary DESC, t.first_name, t.last_name";
        $advisorStmt = $db->prepare($advisorQuery);
        $advisorStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $advisorStmt->execute();
        $advisors = $advisorStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงค่า is_primary เป็น boolean
        foreach ($advisors as &$advisor) {
            $advisor['is_primary'] = (bool)$advisor['is_primary'];
        }
        
        return ['status' => 'success', 'class_name' => $class_name, 'advisors' => $advisors];
    } catch (PDOException $e) {
        error_log("Error getting class advisors: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลครูที่ปรึกษา: ' . $e->getMessage()];
    }
}

/**
 * จัดการครูที่ปรึกษา
 * 
 * @param array $data ข้อมูลการจัดการครูที่ปรึกษา
 * @return array ผลลัพธ์การดำเนินการ
 */
function manageAdvisors($data) {
    global $db;
    
    try {
        $class_id = $data['class_id'];
        $changes = $data['changes'];
        
        if (empty($class_id) || !is_array($changes)) {
            return ['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'];
        }
        
        $db->beginTransaction();
        
        // ดำเนินการเปลี่ยนแปลงทีละรายการ
        foreach ($changes as $change) {
            $teacher_id = $change['teacher_id'];
            
            switch ($change['action']) {
                case 'add':
                    // เพิ่มครูที่ปรึกษา
                    $stmt = $db->prepare("INSERT INTO class_advisors (class_id, teacher_id, is_primary) VALUES (:class_id, :teacher_id, :is_primary)");
                    $stmt->execute([
                        ':class_id' => $class_id,
                        ':teacher_id' => $teacher_id,
                        ':is_primary' => isset($change['is_primary']) && $change['is_primary'] ? 1 : 0
                    ]);
                    break;
                    
                case 'remove':
                    // ลบครูที่ปรึกษา
                    $stmt = $db->prepare("DELETE FROM class_advisors WHERE class_id = :class_id AND teacher_id = :teacher_id");
                    $stmt->execute([
                        ':class_id' => $class_id,
                        ':teacher_id' => $teacher_id
                    ]);
                    break;
                    
                case 'set_primary':
                    // ยกเลิกที่ปรึกษาหลักคนเดิม
                    $stmt = $db->prepare("UPDATE class_advisors SET is_primary = 0 WHERE class_id = :class_id");
                    $stmt->execute([':class_id' => $class_id]);
                    
                    // ตั้งเป็นที่ปรึกษาหลัก
                    $stmt = $db->prepare("UPDATE class_advisors SET is_primary = 1 WHERE class_id = :class_id AND teacher_id = :teacher_id");
                    $stmt->execute([
                        ':class_id' => $class_id,
                        ':teacher_id' => $teacher_id
                    ]);
                    break;
            }
        }
        
        // บันทึกประวัติการกระทำ
        session_start();
        if (isset($_SESSION['user_id'])) {
            $details = json_encode([
                'class_id' => $class_id,
                'changes' => $changes
            ], JSON_UNESCAPED_UNICODE);
            
            $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                           VALUES (:admin_id, 'manage_advisors', :details)";
            $actionStmt = $db->prepare($actionQuery);
            $actionStmt->execute([
                ':admin_id' => $_SESSION['user_id'],
                ':details' => $details
            ]);
        }
        
        $db->commit();
        
        return ['success' => true, 'message' => 'บันทึกการเปลี่ยนแปลงครูที่ปรึกษาสำเร็จ'];
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error managing advisors: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการจัดการครูที่ปรึกษา: ' . $e->getMessage()];
    }
}

/**
 * เลื่อนชั้นนักเรียน
 * 
 * @param array $data ข้อมูลการเลื่อนชั้น
 * @return array ผลลัพธ์การดำเนินการ
 */
function promoteStudents($data) {
    global $db;
    
    try {
        if (empty($data['from_academic_year_id']) || empty($data['to_academic_year_id'])) {
            return ['success' => false, 'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'];
        }
        
        $from_academic_year_id = $data['from_academic_year_id'];
        $to_academic_year_id = $data['to_academic_year_id'];
        $notes = $data['notes'] ?? '';
        $admin_id = $_SESSION['user_id'] ?? 1;
        
        $db->beginTransaction();
        
        // สร้างบันทึกการเลื่อนชั้น
        $batchQuery = "INSERT INTO student_promotion_batch (
                          from_academic_year_id,
                          to_academic_year_id,
                          status,
                          notes,
                          created_by
                      ) VALUES (
                          :from_academic_year_id,
                          :to_academic_year_id,
                          'in_progress',
                          :notes,
                          :admin_id
                      )";
        $batchStmt = $db->prepare($batchQuery);
        $batchStmt->bindParam(':from_academic_year_id', $from_academic_year_id, PDO::PARAM_INT);
        $batchStmt->bindParam(':to_academic_year_id', $to_academic_year_id, PDO::PARAM_INT);
        $batchStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $batchStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $batchStmt->execute();
        
        $batch_id = $db->lastInsertId();
        
        // ดึงข้อมูลชั้นเรียนปัจจุบันทั้งหมดในปีการศึกษาต้นทาง
        $classesQuery = "SELECT 
                       c.class_id, 
                       c.level, 
                       c.department_id, 
                       c.group_number,
                       d.department_name
                       FROM classes c
                       JOIN departments d ON c.department_id = d.department_id
                       WHERE c.academic_year_id = :from_academic_year_id";
        $classesStmt = $db->prepare($classesQuery);
        $classesStmt->bindParam(':from_academic_year_id', $from_academic_year_id, PDO::PARAM_INT);
        $classesStmt->execute();
        $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // สร้างชั้นเรียนใหม่ในปีการศึกษาปลายทาง
        $new_classes = [];
        $student_count = 0;
        $graduate_count = 0;
        
        foreach ($classes as $class) {
            // กำหนดระดับชั้นใหม่
            $current_level = $class['level'];
            $new_level = null;
            $promotion_type = 'promotion';
            
            switch ($current_level) {
                case 'ปวช.1':
                    $new_level = 'ปวช.2';
                    break;
                case 'ปวช.2':
                    $new_level = 'ปวช.3';
                    break;
                case 'ปวส.1':
                    $new_level = 'ปวส.2';
                    break;
                case 'ปวช.3':
                case 'ปวส.2':
                    $new_level = $current_level; // ยังคงระดับชั้นเดิม แต่จะเปลี่ยนสถานะเป็นสำเร็จการศึกษา
                    $promotion_type = 'graduation';
                    break;
                default:
                    $new_level = $current_level;
                    break;
            }
            
            if ($new_level && $promotion_type === 'promotion') {
                // ตรวจสอบว่ามีชั้นเรียนใหม่ในปีการศึกษาปลายทางหรือไม่
                $newClassQuery = "SELECT class_id 
                                FROM classes 
                                WHERE academic_year_id = :to_academic_year_id 
                                AND level = :level 
                                AND department_id = :department_id 
                                AND group_number = :group_number";
                $newClassStmt = $db->prepare($newClassQuery);
                $newClassStmt->bindParam(':to_academic_year_id', $to_academic_year_id, PDO::PARAM_INT);
                $newClassStmt->bindParam(':level', $new_level, PDO::PARAM_STR);
                $newClassStmt->bindParam(':department_id', $class['department_id'], PDO::PARAM_STR);
                $newClassStmt->bindParam(':group_number', $class['group_number'], PDO::PARAM_INT);
                $newClassStmt->execute();
                
                if ($newClassStmt->rowCount() > 0) {
                    // ถ้ามีชั้นเรียนใหม่แล้ว
                    $new_class = $newClassStmt->fetch(PDO::FETCH_ASSOC);
                    $new_class_id = $new_class['class_id'];
                } else {
                    // สร้างชั้นเรียนใหม่
                    $createClassQuery = "INSERT INTO classes (
                                        academic_year_id,
                                        level,
                                        department_id,
                                        group_number,
                                        is_active
                                    ) VALUES (
                                        :academic_year_id,
                                        :level,
                                        :department_id,
                                        :group_number,
                                        1
                                    )";
                    $createClassStmt = $db->prepare($createClassQuery);
                    $createClassStmt->bindParam(':academic_year_id', $to_academic_year_id, PDO::PARAM_INT);
                    $createClassStmt->bindParam(':level', $new_level, PDO::PARAM_STR);
                    $createClassStmt->bindParam(':department_id', $class['department_id'], PDO::PARAM_STR);
                    $createClassStmt->bindParam(':group_number', $class['group_number'], PDO::PARAM_INT);
                    $createClassStmt->execute();
                    
                    $new_class_id = $db->lastInsertId();
                    
                    // โอนครูที่ปรึกษา
                    $transferAdvisorsQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                                           SELECT :new_class_id, teacher_id, is_primary
                                           FROM class_advisors
                                           WHERE class_id = :old_class_id";
                    $transferAdvisorsStmt = $db->prepare($transferAdvisorsQuery);
                    $transferAdvisorsStmt->bindParam(':new_class_id', $new_class_id, PDO::PARAM_INT);
                    $transferAdvisorsStmt->bindParam(':old_class_id', $class['class_id'], PDO::PARAM_INT);
                    $transferAdvisorsStmt->execute();
                }
                
                $new_classes[$class['class_id']] = [
                    'new_class_id' => $new_class_id,
                    'new_level' => $new_level,
                    'promotion_type' => $promotion_type
                ];
            }
            
            // ดึงรายชื่อนักเรียนในชั้นเรียนปัจจุบัน
            $studentsQuery = "SELECT student_id
                           FROM students
                           WHERE current_class_id = :class_id
                           AND status = 'กำลังศึกษา'";
            $studentsStmt = $db->prepare($studentsQuery);
            $studentsStmt->bindParam(':class_id', $class['class_id'], PDO::PARAM_INT);
            $studentsStmt->execute();
            $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($students as $student) {
                // เลื่อนชั้นนักเรียน
                if ($promotion_type === 'graduation') {
                    // กรณีจบการศึกษา
                    $updateStudentQuery = "UPDATE students
                                         SET status = 'สำเร็จการศึกษา'
                                         WHERE student_id = :student_id";
                    $updateStudentStmt = $db->prepare($updateStudentQuery);
                    $updateStudentStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $updateStudentStmt->execute();
                    
                    $graduate_count++;
                } else {
                    // กรณีเลื่อนชั้น
                    $updateStudentQuery = "UPDATE students
                                         SET current_class_id = :new_class_id
                                         WHERE student_id = :student_id";
                    $updateStudentStmt = $db->prepare($updateStudentQuery);
                    $updateStudentStmt->bindParam(':new_class_id', $new_classes[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                    $updateStudentStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $updateStudentStmt->execute();
                    
                    $student_count++;
                }
                
                // บันทึกประวัติการเลื่อนชั้น
                $historyQuery = "INSERT INTO class_history (
                                student_id,
                                previous_class_id,
                                new_class_id,
                                previous_level,
                                new_level,
                                promotion_date,
                                academic_year_id,
                                promotion_type,
                                promotion_notes,
                                created_by
                            ) VALUES (
                                :student_id,
                                :previous_class_id,
                                :new_class_id,
                                :previous_level,
                                :new_level,
                                NOW(),
                                :academic_year_id,
                                :promotion_type,
                                :promotion_notes,
                                :created_by
                            )";
                $historyStmt = $db->prepare($historyQuery);
                $historyStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                $historyStmt->bindParam(':previous_class_id', $class['class_id'], PDO::PARAM_INT);
                
                if ($promotion_type === 'graduation') {
                    $historyStmt->bindParam(':new_class_id', $class['class_id'], PDO::PARAM_INT);
                } else {
                    $historyStmt->bindParam(':new_class_id', $new_classes[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                }
                
                $historyStmt->bindParam(':previous_level', $current_level, PDO::PARAM_STR);
                $historyStmt->bindParam(':new_level', $new_level, PDO::PARAM_STR);
                $historyStmt->bindParam(':academic_year_id', $to_academic_year_id, PDO::PARAM_INT);
                $historyStmt->bindParam(':promotion_type', $promotion_type, PDO::PARAM_STR);
                $historyStmt->bindParam(':promotion_notes', $notes, PDO::PARAM_STR);
                $historyStmt->bindParam(':created_by', $admin_id, PDO::PARAM_INT);
                $historyStmt->execute();
                
                // สร้างประวัติการศึกษาใหม่สำหรับปีการศึกษาใหม่
                if ($promotion_type === 'promotion') {
                    $academicRecordQuery = "INSERT INTO student_academic_records (
                                          student_id,
                                          academic_year_id,
                                          class_id,
                                          total_attendance_days,
                                          total_absence_days,
                                          passed_activity
                                      ) VALUES (
                                          :student_id,
                                          :academic_year_id,
                                          :class_id,
                                          0,
                                          0,
                                          NULL
                                      )";
                    $academicRecordStmt = $db->prepare($academicRecordQuery);
                    $academicRecordStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $academicRecordStmt->bindParam(':academic_year_id', $to_academic_year_id, PDO::PARAM_INT);
                    $academicRecordStmt->bindParam(':class_id', $new_classes[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                    $academicRecordStmt->execute();
                }
            }
        }
        
        // อัปเดตสถานะการเลื่อนชั้น
        $updateBatchQuery = "UPDATE student_promotion_batch
                           SET status = 'completed',
                               students_count = :students_count,
                               graduates_count = :graduates_count
                           WHERE batch_id = :batch_id";
        $updateBatchStmt = $db->prepare($updateBatchQuery);
        $updateBatchStmt->bindParam(':students_count', $student_count, PDO::PARAM_INT);
        $updateBatchStmt->bindParam(':graduates_count', $graduate_count, PDO::PARAM_INT);
        $updateBatchStmt->bindParam(':batch_id', $batch_id, PDO::PARAM_INT);
        $updateBatchStmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $details = json_encode([
            'batch_id' => $batch_id,
            'from_academic_year_id' => $from_academic_year_id,
            'to_academic_year_id' => $to_academic_year_id,
            'students_count' => $student_count,
            'graduates_count' => $graduate_count
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'promote_students', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        $db->commit();
        
        return [
            'success' => true, 
            'message' => "เลื่อนชั้นนักเรียนสำเร็จ ($student_count คน) และจบการศึกษา ($graduate_count คน)",
            'batch_id' => $batch_id
        ];
    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error promoting students: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()];
    }
}

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 * 
 * @param int $class_id รหัสชั้นเรียน
 */
function downloadClassReport($class_id) {
    global $db;
    
    try {
        // ดึงข้อมูลรายละเอียดชั้นเรียน
        $classDetails = getDetailedClassInfo($class_id);
        
        if ($classDetails['status'] !== 'success') {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน: ' . ($classDetails['message'] ?? 'ไม่ทราบสาเหตุ');
            exit;
        }
        
        $class = $classDetails['class'];
        $advisors = $classDetails['advisors'];
        $students = $classDetails['students'];
        $attendance_stats = $classDetails['attendance_stats'];
        
        // สร้างชื่อไฟล์
        $filename = 'รายงานชั้น_' . $class['level'] . '_กลุ่ม_' . $class['group_number'] . '_' . $class['department_name'] . '.csv';
        
        // ตั้งค่าส่วนหัวสำหรับดาวน์โหลด CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // สร้าง file pointer สำหรับเขียนไฟล์
        $output = fopen('php://output', 'w');
        
        // เพิ่ม BOM สำหรับ UTF-8 เพื่อให้ Excel แสดงภาษาไทยได้
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // เขียนหัวข้อ
        fputcsv($output, [
            'รายงานการเข้าแถวชั้น ' . $class['level'] . ' กลุ่ม ' . $class['group_number'] . ' ' . $class['department_name']
        ]);
        fputcsv($output, ['ปีการศึกษา: ' . $class['year'] . ' ภาคเรียนที่ ' . $class['semester']]);
        fputcsv($output, []);
        
        // เขียนข้อมูลครูที่ปรึกษา
        fputcsv($output, ['ครูที่ปรึกษา']);
        foreach ($advisors as $advisor) {
            fputcsv($output, [$advisor['name'] . ($advisor['is_primary'] ? ' (หลัก)' : ''), $advisor['position'] ?? '']);
        }
        fputcsv($output, []);
        
        // เขียนสถิติการเข้าแถวโดยรวม
        fputcsv($output, ['สถิติการเข้าแถวโดยรวม']);
        fputcsv($output, ['จำนวนวันทั้งหมด', $attendance_stats['total_days'] . ' วัน']);
        fputcsv($output, ['จำนวนวันที่เข้าแถว', $attendance_stats['present_days'] . ' วัน']);
        fputcsv($output, ['จำนวนวันที่ขาด', $attendance_stats['absent_days'] . ' วัน']);
        fputcsv($output, ['อัตราการเข้าแถว', number_format($attendance_stats['overall_rate'], 2) . '%']);
        fputcsv($output, []);
        
        // เขียนสถิติรายเดือน
        fputcsv($output, ['สถิติรายเดือน']);
        fputcsv($output, ['เดือน', 'เข้าแถว (วัน)', 'ขาด (วัน)', 'อัตราการเข้าแถว (%)']);
        foreach ($attendance_stats['monthly'] as $month) {
            $total = $month['present'] + $month['absent'];
            $rate = $total > 0 ? ($month['present'] / $total * 100) : 0;
            fputcsv($output, [$month['month'], $month['present'], $month['absent'], number_format($rate, 2)]);
        }
        fputcsv($output, []);
        
        // เขียนรายชื่อนักเรียน
        fputcsv($output, ['รายชื่อนักเรียน']);
        fputcsv($output, ['รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'การเข้าแถว (วัน)', 'ร้อยละ', 'สถานะ']);
        foreach ($students as $student) {
            fputcsv($output, [
                $student['code'],
                $student['name'],
                $student['attendance'] . '/' . $student['total'],
                number_format($student['percent'], 2) . '%',
                $student['status']
            ]);
        }
        
        // ปิด file pointer
        fclose($output);
        exit;
    } catch (Exception $e) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'เกิดข้อผิดพลาดในการสร้างรายงาน: ' . $e->getMessage();
        exit;
    }
}
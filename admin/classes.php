<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ตรวจสอบการส่งข้อมูลแบบ AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
    
    try {
        switch ($_POST['action']) {
            case 'add_class':
                $response = addClass($_POST);
                break;
            case 'update_class':
                $response = updateClass($_POST);
                break;
            case 'delete_class':
                $response = deleteClass($_POST['class_id']);
                break;
            case 'add_department':
                $response = addDepartment($_POST);
                break;
            case 'update_department':
                $response = updateDepartment($_POST);
                break;
            case 'delete_department':
                $response = deleteDepartment($_POST['department_id']);
                break;
            case 'manage_advisors':
                $response = manageAdvisors($_POST);
                break;
            case 'promote_students':
                $response = promoteStudents($_POST);
                break;
            default:
                $response = ['success' => false, 'message' => 'คำสั่งไม่ถูกต้อง'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        error_log("Error in classes.php AJAX: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียน';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadClassReport()'
    ],
    [
        'text' => 'สถิติชั้นเรียน',
        'icon' => 'leaderboard',
        'onclick' => 'showClassStatistics()'
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js',
    'assets/js/charts.js'
];

/**
 * ฟังก์ชันจัดการข้อมูลชั้นเรียน
 */

// เพิ่มชั้นเรียนใหม่
function addClass($data) {
    try {
        $db = getDB();
        
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
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มชั้นเรียนใหม่
        $query = "INSERT INTO classes (academic_year_id, level, department_id, group_number, classroom, is_active) 
                  VALUES (:academic_year_id, :level, :department_id, :group_number, :classroom, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
        $stmt->bindParam(':level', $data['level'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $data['classroom'] ?? null, PDO::PARAM_STR);
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
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $class_id,
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ]);
        
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

// แก้ไขข้อมูลชั้นเรียน
function updateClass($data) {
    try {
        $db = getDB();
        
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
        $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $checkStmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $checkStmt->bindParam(':class_id', $data['class_id'], PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'ชั้นเรียนนี้มีอยู่แล้วในระบบ'];
        }
        
        // แก้ไขข้อมูลชั้นเรียน
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
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindParam(':group_number', $data['group_number'], PDO::PARAM_INT);
        $stmt->bindParam(':classroom', $data['classroom'] ?? null, PDO::PARAM_STR);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $data['class_id'],
            'academic_year_id' => $data['academic_year_id'],
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'group_number' => $data['group_number']
        ]);
        
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

// ลบชั้นเรียน
function deleteClass($class_id) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
        $checkQuery = "SELECT COUNT(*) AS count FROM students WHERE current_class_id = :class_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบชั้นเรียนได้ เนื่องจากมีนักเรียนในชั้นเรียนนี้ ' . $result['count'] . ' คน'];
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
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode(['class_id' => $class_id]);
        
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

// เพิ่มแผนกวิชาใหม่
function addDepartment($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_code']) || empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณากรอกรหัสและชื่อแผนกวิชา'];
        }
        
        // ตรวจสอบว่ามีแผนกวิชาซ้ำหรือไม่
        $checkQuery = "SELECT department_id FROM departments 
                      WHERE department_code = :department_code OR department_name = :department_name";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':department_code', $data['department_code'], PDO::PARAM_STR);
        $checkStmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'รหัสหรือชื่อแผนกวิชานี้มีอยู่แล้วในระบบ'];
        }
        
        // เพิ่มแผนกวิชาใหม่
        $query = "INSERT INTO departments (department_code, department_name, is_active) 
                 VALUES (:department_code, :department_name, 1)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':department_code', $data['department_code'], PDO::PARAM_STR);
        $stmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
        $stmt->execute();
        
        $department_id = $db->lastInsertId();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'department_id' => $department_id,
            'department_code' => $data['department_code'],
            'department_name' => $data['department_name']
        ]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'add_department', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        return ['success' => true, 'message' => 'เพิ่มแผนกวิชาสำเร็จ', 'department_id' => $department_id];
    } catch (PDOException $e) {
        error_log("Error adding department: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มแผนกวิชา: ' . $e->getMessage()];
    }
}

// แก้ไขข้อมูลแผนกวิชา
function updateDepartment($data) {
    try {
        $db = getDB();
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['department_id']) || empty($data['department_name'])) {
            return ['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'];
        }
        
        // ตรวจสอบว่ามีแผนกวิชาซ้ำหรือไม่ (ยกเว้นแผนกวิชาปัจจุบัน)
        if (!empty($data['department_code'])) {
            $checkQuery = "SELECT department_id FROM departments 
                          WHERE (department_code = :department_code OR department_name = :department_name)
                          AND department_id != :department_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':department_code', $data['department_code'], PDO::PARAM_STR);
            $checkStmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
            $checkStmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'รหัสหรือชื่อแผนกวิชานี้มีอยู่แล้วในระบบ'];
            }
            
            // แก้ไขข้อมูลแผนกวิชา (รวมรหัส)
            $query = "UPDATE departments 
                     SET department_code = :department_code, 
                         department_name = :department_name
                     WHERE department_id = :department_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':department_code', $data['department_code'], PDO::PARAM_STR);
        } else {
            // แก้ไขเฉพาะชื่อแผนกวิชา
            $query = "UPDATE departments 
                     SET department_name = :department_name
                     WHERE department_id = :department_id";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':department_name', $data['department_name'], PDO::PARAM_STR);
        $stmt->bindParam(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'department_id' => $data['department_id'],
            'department_name' => $data['department_name']
        ]);
        
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

// ลบแผนกวิชา
function deleteDepartment($department_id) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่ามีการใช้งานแผนกวิชานี้หรือไม่ (ในตาราง classes)
        $checkClassesQuery = "SELECT COUNT(*) AS count FROM classes WHERE department_id = :department_id";
        $checkClassesStmt = $db->prepare($checkClassesQuery);
        $checkClassesStmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
        $checkClassesStmt->execute();
        $classResult = $checkClassesStmt->fetch();
        
        if ($classResult['count'] > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีชั้นเรียนที่ใช้แผนกวิชานี้อยู่ ' . $classResult['count'] . ' ชั้นเรียน'];
        }
        
        // ตรวจสอบว่ามีการใช้งานแผนกวิชานี้หรือไม่ (ในตาราง teachers)
        $checkTeachersQuery = "SELECT COUNT(*) AS count FROM teachers WHERE department_id = :department_id";
        $checkTeachersStmt = $db->prepare($checkTeachersQuery);
        $checkTeachersStmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
        $checkTeachersStmt->execute();
        $teacherResult = $checkTeachersStmt->fetch();
        
        if ($teacherResult['count'] > 0) {
            return ['success' => false, 'message' => 'ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีครูที่อยู่ในแผนกวิชานี้ ' . $teacherResult['count'] . ' คน'];
        }
        
        // ลบแผนกวิชา
        $query = "DELETE FROM departments WHERE department_id = :department_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode(['department_id' => $department_id]);
        
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

// จัดการครูที่ปรึกษา
function manageAdvisors($data) {
    try {
        $db = getDB();
        
        if (empty($data['class_id']) || !isset($data['changes']) || !is_array($data['changes'])) {
            return ['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'];
        }
        
        $class_id = $data['class_id'];
        $changes = $data['changes'];
        
        $db->beginTransaction();
        
        foreach ($changes as $change) {
            if (!isset($change['action']) || !isset($change['teacher_id'])) {
                continue;
            }
            
            switch ($change['action']) {
                case 'add':
                    // ตรวจสอบว่าครูนี้เป็นที่ปรึกษาของห้องนี้แล้วหรือไม่
                    $checkQuery = "SELECT COUNT(*) AS count FROM class_advisors 
                                  WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $checkStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $checkStmt->execute();
                    $result = $checkStmt->fetch();
                    
                    if ($result['count'] == 0) {
                        // ถ้าเพิ่มครูที่ปรึกษาหลัก ต้องยกเลิกครูที่ปรึกษาหลักคนเดิมก่อน
                        if (isset($change['is_primary']) && $change['is_primary']) {
                            $resetQuery = "UPDATE class_advisors SET is_primary = 0 
                                          WHERE class_id = :class_id AND is_primary = 1";
                            $resetStmt = $db->prepare($resetQuery);
                            $resetStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                            $resetStmt->execute();
                        }
                        
                        // เพิ่มครูที่ปรึกษา
                        $addQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary) 
                                    VALUES (:class_id, :teacher_id, :is_primary)";
                        $addStmt = $db->prepare($addQuery);
                        $addStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                        $addStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                        $is_primary = isset($change['is_primary']) && $change['is_primary'] ? 1 : 0;
                        $addStmt->bindParam(':is_primary', $is_primary, PDO::PARAM_INT);
                        $addStmt->execute();
                    }
                    break;
                    
                case 'remove':
                    // ลบครูที่ปรึกษา
                    $removeQuery = "DELETE FROM class_advisors 
                                   WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $removeStmt = $db->prepare($removeQuery);
                    $removeStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $removeStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $removeStmt->execute();
                    break;
                    
                case 'set_primary':
                    // ยกเลิกครูที่ปรึกษาหลักคนเดิม
                    $resetQuery = "UPDATE class_advisors SET is_primary = 0 
                                  WHERE class_id = :class_id AND is_primary = 1";
                    $resetStmt = $db->prepare($resetQuery);
                    $resetStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $resetStmt->execute();
                    
                    // ตั้งครูคนนี้เป็นที่ปรึกษาหลัก
                    $setPrimaryQuery = "UPDATE class_advisors SET is_primary = 1 
                                       WHERE class_id = :class_id AND teacher_id = :teacher_id";
                    $setPrimaryStmt = $db->prepare($setPrimaryQuery);
                    $setPrimaryStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                    $setPrimaryStmt->bindParam(':teacher_id', $change['teacher_id'], PDO::PARAM_INT);
                    $setPrimaryStmt->execute();
                    break;
            }
        }
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        $details = json_encode([
            'class_id' => $class_id,
            'changes' => $changes
        ]);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                       VALUES (:admin_id, 'manage_advisors', :details)";
        $actionStmt = $db->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        $db->commit();
        
        return ['success' => true, 'message' => 'บันทึกการเปลี่ยนแปลงครูที่ปรึกษาสำเร็จ'];
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error managing advisors: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการจัดการครูที่ปรึกษา: ' . $e->getMessage()];
    }
}

// เลื่อนชั้นนักเรียน
function promoteStudents($data) {
    try {
        $db = getDB();
        
        if (empty($data['from_academic_year_id']) || empty($data['to_academic_year_id'])) {
            return ['success' => false, 'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'];
        }
        
        $from_academic_year_id = $data['from_academic_year_id'];
        $to_academic_year_id = $data['to_academic_year_id'];
        $notes = $data['notes'] ?? '';
        $admin_id = $_SESSION['user_id'] ?? 1; // ควรดึงจาก session จริง
        
        // เรียกใช้ stored procedure สำหรับการเลื่อนชั้น
        $stmt = $db->prepare("CALL promote_students(?, ?, ?, ?)");
        $stmt->bindParam(1, $from_academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $to_academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(4, $notes, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $batch_id = $result['batch_id'] ?? null;
        
        if ($batch_id) {
            return ['success' => true, 'message' => 'เลื่อนชั้นนักเรียนสำเร็จ', 'batch_id' => $batch_id];
        } else {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน'];
        }
    } catch (PDOException $e) {
        error_log("Error promoting students: " . $e->getMessage());
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()];
    }
}

/**
 * ฟังก์ชันดึงข้อมูล
 */

// ดึงข้อมูลชั้นเรียนจากฐานข้อมูล
function getClassesFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                c.class_id,
                c.academic_year_id,
                c.level,
                c.group_number,
                c.department_id,
                d.department_name,
                (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.is_active = 1
            ORDER BY c.level, c.group_number";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $classesResult = $stmt->fetchAll();
        
        $classes = [];
        foreach ($classesResult as $row) {
            // ดึงข้อมูลครูที่ปรึกษา
            $advisorQuery = "SELECT 
                    t.teacher_id,
                    CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                    ca.is_primary
                FROM class_advisors ca
                JOIN teachers t ON ca.teacher_id = t.teacher_id
                WHERE ca.class_id = :class_id
                ORDER BY ca.is_primary DESC";
                
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $row['class_id'], PDO::PARAM_INT);
            $advisorStmt->execute();
            $advisorResult = $advisorStmt->fetchAll();
            
            $advisors = [];
            foreach ($advisorResult as $advisor) {
                $advisors[] = [
                    'id' => $advisor['teacher_id'],
                    'name' => $advisor['name'],
                    'is_primary' => (bool)$advisor['is_primary']
                ];
            }
            
            // สร้างข้อมูลการเข้าแถว (กรณีนี้จะใช้ข้อมูลตัวอย่าง เพราะการคำนวณจริงต้องใช้ข้อมูลจากตาราง attendance)
            $attendanceRate = rand(75, 100);
            
            $classes[] = [
                'class_id' => $row['class_id'],
                'academic_year_id' => $row['academic_year_id'],
                'level' => $row['level'],
                'department' => $row['department_name'],
                'group_number' => $row['group_number'],
                'student_count' => $row['student_count'],
                'attendance_rate' => $attendanceRate,
                'status' => $attendanceRate > 90 ? 'good' : ($attendanceRate > 75 ? 'warning' : 'danger'),
                'advisors' => $advisors
            ];
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log("Error fetching classes: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลแผนกวิชาจากฐานข้อมูล
function getDepartmentsFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                department_id,
                department_code,
                department_name 
            FROM departments
            WHERE is_active = 1
            ORDER BY department_name";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $departmentsResult = $stmt->fetchAll();
        
        $departments = [];
        foreach ($departmentsResult as $row) {
            // ดึงจำนวนนักเรียน
            $studentCountQuery = "SELECT COUNT(*) AS count 
                FROM students s 
                JOIN classes c ON s.current_class_id = c.class_id 
                WHERE c.department_id = :department_id 
                AND s.status = 'กำลังศึกษา'";
                
            $studentStmt = $db->prepare($studentCountQuery);
            $studentStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $studentStmt->execute();
            $studentData = $studentStmt->fetch();
            
            // ดึงจำนวนชั้นเรียน
            $classCountQuery = "SELECT COUNT(DISTINCT class_id) AS count 
                FROM classes 
                WHERE department_id = :department_id 
                AND is_active = 1";
                
            $classStmt = $db->prepare($classCountQuery);
            $classStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $classStmt->execute();
            $classData = $classStmt->fetch();
            
            // ดึงจำนวนครู
            $teacherCountQuery = "SELECT COUNT(*) AS count 
                FROM teachers 
                WHERE department_id = :department_id";
                
            $teacherStmt = $db->prepare($teacherCountQuery);
            $teacherStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $teacherStmt->execute();
            $teacherData = $teacherStmt->fetch();
            
            $departments[$row['department_code']] = [
                'name' => $row['department_name'],
                'student_count' => $studentData['count'],
                'class_count' => $classData['count'],
                'teacher_count' => $teacherData['count']
            ];
        }
        
        return $departments;
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลปีการศึกษาจากฐานข้อมูล
function getAcademicYearsFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                academic_year_id,
                year,
                semester,
                is_active,
                start_date,
                end_date,
                required_attendance_days
            FROM academic_years
            ORDER BY year DESC, semester DESC";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $academic_years = $stmt->fetchAll();
        
        // ดึงข้อมูลปีการศึกษาปัจจุบัน
        $activeYearQuery = "SELECT academic_year_id, year, semester
            FROM academic_years
            WHERE is_active = 1
            LIMIT 1";
            
        $activeYearStmt = $db->prepare($activeYearQuery);
        $activeYearStmt->execute();
        
        if ($activeYearStmt->rowCount() > 0) {
            $activeYear = $activeYearStmt->fetch();
            
            // ตรวจสอบว่ามีปีการศึกษาถัดไปหรือไม่
            $nextYearQuery = "SELECT academic_year_id, year, semester
                FROM academic_years
                WHERE (year > :year) OR (year = :year AND semester > :semester)
                ORDER BY year ASC, semester ASC
                LIMIT 1";
                
            $nextYearStmt = $db->prepare($nextYearQuery);
            $nextYearStmt->bindParam(':year', $activeYear['year'], PDO::PARAM_INT);
            $nextYearStmt->bindParam(':semester', $activeYear['semester'], PDO::PARAM_INT);
            $nextYearStmt->execute();
            
            $has_new_academic_year = ($nextYearStmt->rowCount() > 0);
            $current_academic_year = $activeYear['year'] . ' ภาคเรียนที่ ' . $activeYear['semester'];
            
            if ($has_new_academic_year) {
                $nextYear = $nextYearStmt->fetch();
                $next_academic_year = $nextYear['year'] . ' ภาคเรียนที่ ' . $nextYear['semester'];
            } else {
                $next_academic_year = '';
            }
        } else {
            $has_new_academic_year = false;
            $current_academic_year = '';
            $next_academic_year = '';
        }
        
        return [
            'academic_years' => $academic_years,
            'has_new_academic_year' => $has_new_academic_year,
            'current_academic_year' => $current_academic_year, 
            'next_academic_year' => $next_academic_year,
            'active_year_id' => $activeYear['academic_year_id'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
function getAtRiskStudentCount() {
    try {
        $db = getDB();
        $query = "SELECT COUNT(*) AS count
            FROM risk_students rs
            JOIN students s ON rs.student_id = s.student_id
            JOIN academic_years ay ON rs.academic_year_id = ay.academic_year_id
            WHERE rs.risk_level IN ('high', 'critical')
            AND s.status = 'กำลังศึกษา'
            AND ay.is_active = 1";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['count'];
    } catch (PDOException $e) {
        error_log("Error fetching at-risk count: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลการเลื่อนชั้น
function getPromotionCounts($academic_year_id) {
    try {
        $db = getDB();
        $query = "SELECT 
                c.level AS current_level,
                COUNT(s.student_id) AS student_count,
                CASE 
                    WHEN c.level = 'ปวช.1' THEN 'ปวช.2'
                    WHEN c.level = 'ปวช.2' THEN 'ปวช.3'
                    WHEN c.level = 'ปวช.3' THEN 'สำเร็จการศึกษา'
                    WHEN c.level = 'ปวส.1' THEN 'ปวส.2'
                    WHEN c.level = 'ปวส.2' THEN 'สำเร็จการศึกษา'
                    ELSE c.level
                END AS new_level
            FROM 
                students s
                JOIN classes c ON s.current_class_id = c.class_id
            WHERE 
                s.status = 'กำลังศึกษา'
                AND c.academic_year_id = :academic_year_id
            GROUP BY 
                current_level, new_level
            ORDER BY 
                c.level";
                
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->execute();
        $promotion_counts = $stmt->fetchAll();
        return $promotion_counts;
    } catch (PDOException $e) {
        error_log("Error fetching promotion counts: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลครูทั้งหมด
function getTeachersFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                t.teacher_id,
                t.title,
                t.first_name,
                t.last_name,
                d.department_name
            FROM 
                teachers t
                LEFT JOIN departments d ON t.department_id = d.department_id
            ORDER BY 
                t.first_name, t.last_name";
                
        $stmt = $db->prepare($query);
        $stmt->execute();
        $teachers = $stmt->fetchAll();
        return $teachers;
    } catch (PDOException $e) {
        error_log("Error fetching teachers: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลจากฐานข้อมูล
$classes = getClassesFromDB();
$departments = getDepartmentsFromDB();
$academicYearData = getAcademicYearsFromDB();
$at_risk_count_db = getAtRiskStudentCount();
$teachers = getTeachersFromDB();

// ถ้าดึงข้อมูลไม่สำเร็จ ให้ใช้ข้อมูลตัวอย่าง
if ($classes === false) {
    $classes = [
        [
            'class_id' => 1,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '1',
            'attendance_rate' => 94.3,
            'status' => 'good',
            'student_count' => 35,
            'advisors' => [
                ['name' => 'นาย มนตรี ศรีสุข', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 2,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '2',
            'attendance_rate' => 87.5,
            'status' => 'warning',
            'student_count' => 32,
            'advisors' => [
                ['name' => 'นาง ราตรี นอนดึก', 'is_primary' => true]
            ]
        ]
    ];
}

if ($departments === false) {
    $departments = [
        'IT' => ['name' => 'เทคโนโลยีสารสนเทศ', 'student_count' => 110, 'class_count' => 4, 'teacher_count' => 6],
        'AUTO' => ['name' => 'ช่างยนต์', 'student_count' => 120, 'class_count' => 4, 'teacher_count' => 8],
        'GEN' => ['name' => 'สามัญ', 'student_count' => 0, 'class_count' => 0, 'teacher_count' => 12]
    ];
}

if ($academicYearData === false) {
    $academic_years = [
        ['academic_year_id' => 1, 'year' => '2568', 'semester' => '1', 'is_active' => 1]
    ];
    $has_new_academic_year = true;
    $current_academic_year = '2567 ภาคเรียนที่ 2';
    $next_academic_year = '2568 ภาคเรียนที่ 1';
    $active_year_id = 1;
} else {
    $academic_years = $academicYearData['academic_years'];
    $has_new_academic_year = $academicYearData['has_new_academic_year'];
    $current_academic_year = $academicYearData['current_academic_year'];
    $next_academic_year = $academicYearData['next_academic_year'];
    $active_year_id = $academicYearData['active_year_id'];
}

if ($at_risk_count_db !== false) {
    $at_risk_count = $at_risk_count_db;
}

if ($teachers === false) {
    $teachers = [
        ['teacher_id' => 1, 'title' => 'นาย', 'first_name' => 'ใจดี', 'last_name' => 'มากเมตตา'],
        ['teacher_id' => 2, 'title' => 'นาง', 'first_name' => 'ราตรี', 'last_name' => 'นอนดึก'],
        ['teacher_id' => 3, 'title' => 'นาย', 'first_name' => 'มานะ', 'last_name' => 'พยายาม'],
        ['teacher_id' => 4, 'title' => 'นางสาว', 'first_name' => 'วันดี', 'last_name' => 'สดใส'],
        ['teacher_id' => 5, 'title' => 'นาง', 'first_name' => 'สมศรี', 'last_name' => 'ใจดี']
    ];
}

// ข้อมูลการเลื่อนชั้นจะดึงเมื่อมีปีการศึกษาถัดไป
if ($has_new_academic_year && $active_year_id !== null) {
    $promotion_counts = getPromotionCounts($active_year_id);
    
    if ($promotion_counts === false) {
        $promotion_counts = [
            ['current_level' => 'ปวช.1', 'student_count' => 120, 'new_level' => 'ปวช.2'],
            ['current_level' => 'ปวช.2', 'student_count' => 105, 'new_level' => 'ปวช.3'],
            ['current_level' => 'ปวช.3', 'student_count' => 95, 'new_level' => 'สำเร็จการศึกษา'],
            ['current_level' => 'ปวส.1', 'student_count' => 80, 'new_level' => 'ปวส.2'],
            ['current_level' => 'ปวส.2', 'student_count' => 75, 'new_level' => 'สำเร็จการศึกษา']
        ];
    }
} else {
    $promotion_counts = [];
}

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'classes' => $classes,
    'departments' => $departments,
    'academic_years' => $academic_years,
    'has_new_academic_year' => $has_new_academic_year,
    'current_academic_year' => $current_academic_year,
    'next_academic_year' => $next_academic_year,
    'promotion_counts' => $promotion_counts,
    'teachers' => $teachers
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/classes_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
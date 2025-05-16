<?php
/**
 * api/classes.php - API สำหรับจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการร้องขอ
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // การร้องขอข้อมูล GET
    $action = $_GET['action'] ?? '';
    
    if ($action === 'list') {
        // รายการชั้นเรียนทั้งหมด
        getClassesList();
    } elseif ($action === 'get') {
        // ข้อมูลชั้นเรียน
        $class_id = $_GET['class_id'] ?? null;
        
        if ($class_id) {
            getClassById($class_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id']);
        }
    } elseif ($action === 'get_by_department') {
        // ข้อมูลชั้นเรียนตามแผนกวิชา
        $department_id = $_GET['department_id'] ?? null;
        $academic_year_id = $_GET['academic_year_id'] ?? null;
        
        if ($department_id) {
            getClassesByDepartment($department_id, $academic_year_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ department_id']);
        }
    } elseif ($action === 'students') {
        // รายชื่อนักเรียนในชั้นเรียน
        $class_id = $_GET['class_id'] ?? null;
        
        if ($class_id) {
            getStudentsByClass($class_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id']);
        }
    } elseif ($action === 'advisors') {
        // รายชื่อครูที่ปรึกษาในชั้นเรียน
        $class_id = $_GET['class_id'] ?? null;
        
        if ($class_id) {
            getAdvisorsByClass($class_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่รู้จักการกระทำ']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล POST
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if ($data === null && empty($_POST)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง']);
        exit;
    }
    
    // ใช้ข้อมูลจาก $_POST หากไม่มีข้อมูล JSON
    if (empty($data)) {
        $data = $_POST;
    }
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'create') {
        // สร้างชั้นเรียนใหม่
        $level = $data['level'] ?? null;
        $department_id = $data['department_id'] ?? null;
        $group_number = $data['group_number'] ?? null;
        $academic_year_id = $data['academic_year_id'] ?? null;
        
        if ($level && $department_id && $group_number && $academic_year_id) {
            addClass($level, $department_id, $group_number, $academic_year_id, $data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
        }
    } elseif ($action === 'update') {
        // อัปเดตข้อมูลชั้นเรียน
        $class_id = $data['class_id'] ?? null;
        
        if ($class_id) {
            updateClass($class_id, $data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id']);
        }
    } elseif ($action === 'delete') {
        // ลบชั้นเรียน
        $class_id = $data['class_id'] ?? null;
        
        if ($class_id) {
            deleteClass($class_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id']);
        }
    } elseif ($action === 'assign_advisor') {
        // กำหนดครูที่ปรึกษา
        $class_id = $data['class_id'] ?? null;
        $teacher_id = $data['teacher_id'] ?? null;
        $is_primary = $data['is_primary'] ?? 0;
        
        if ($class_id && $teacher_id) {
            assignAdvisor($class_id, $teacher_id, $is_primary);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id และ teacher_id']);
        }
    } elseif ($action === 'remove_advisor') {
        // ลบครูที่ปรึกษา
        $class_id = $data['class_id'] ?? null;
        $teacher_id = $data['teacher_id'] ?? null;
        
        if ($class_id && $teacher_id) {
            removeAdvisor($class_id, $teacher_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id และ teacher_id']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่รู้จักการกระทำ']);
    }
}

// ฟังก์ชันดึงรายการชั้นเรียนทั้งหมด
function getClassesList() {
    $db = getDB();
    
    $sql = "SELECT c.*, d.department_name,
                   ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.is_active = 1
            ORDER BY ay.year DESC, ay.semester DESC, c.level, d.department_name, c.group_number";
    
    $stmt = $db->query($sql);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เพิ่มจำนวนนักเรียนในแต่ละชั้นเรียน
    foreach ($classes as &$class) {
        $sql = "SELECT COUNT(*) FROM students 
                WHERE current_class_id = ? AND status = 'กำลังศึกษา'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class['class_id']]);
        $class['student_count'] = $stmt->fetchColumn();
        
        // เพิ่มข้อมูลครูที่ปรึกษา
        $sql = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary
                FROM class_advisors ca
                JOIN teachers t ON ca.teacher_id = t.teacher_id
                WHERE ca.class_id = ?
                ORDER BY ca.is_primary DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class['class_id']]);
        $class['advisors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'classes' => $classes]);
}

// ฟังก์ชันดึงข้อมูลชั้นเรียนตาม ID
function getClassById($class_id) {
    $db = getDB();
    
    $sql = "SELECT c.*, d.department_name,
                   ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน']);
        return;
    }
    
    // เพิ่มจำนวนนักเรียนในชั้นเรียน
    $sql = "SELECT COUNT(*) FROM students 
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $class['student_count'] = $stmt->fetchColumn();
    
    // เพิ่มข้อมูลครูที่ปรึกษา
    $sql = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $class['advisors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'class' => $class]);
}

// ฟังก์ชันดึงรายการชั้นเรียนตามแผนกวิชา
function getClassesByDepartment($department_id, $academic_year_id = null) {
    $db = getDB();
    
    // ถ้าไม่ระบุปีการศึกษา ให้ใช้ปีการศึกษาปัจจุบัน
    if (!$academic_year_id) {
        $sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $db->query($sql);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'];
    }
    
    $sql = "SELECT c.*, d.department_name,
                   ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.department_id = ? AND c.academic_year_id = ? AND c.is_active = 1
            ORDER BY c.level, c.group_number";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$department_id, $academic_year_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เพิ่มจำนวนนักเรียนในแต่ละชั้นเรียน
    foreach ($classes as &$class) {
        $sql = "SELECT COUNT(*) FROM students 
                WHERE current_class_id = ? AND status = 'กำลังศึกษา'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class['class_id']]);
        $class['student_count'] = $stmt->fetchColumn();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'classes' => $classes]);
}

// ฟังก์ชันดึงรายชื่อนักเรียนในชั้นเรียน
function getStudentsByClass($class_id) {
    $db = getDB();
    
    $sql = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                   u.phone_number, u.email, s.status
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'students' => $students]);
}

// ฟังก์ชันดึงรายชื่อครูที่ปรึกษาในชั้นเรียน
function getAdvisorsByClass($class_id) {
    $db = getDB();
    
    $sql = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, 
                   t.position, ca.is_primary, u.phone_number, u.email
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            JOIN users u ON t.user_id = u.user_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'advisors' => $advisors]);
}

// ฟังก์ชันเพิ่มชั้นเรียนใหม่
function addClass($level, $department_id, $group_number, $academic_year_id, $data) {
    $db = getDB();
    
    // ตรวจสอบว่ามีห้องเรียนซ้ำหรือไม่
    $sql = "SELECT class_id FROM classes 
            WHERE level = ? AND department_id = ? AND group_number = ? AND academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$level, $department_id, $group_number, $academic_year_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'มีชั้นเรียนนี้อยู่แล้ว']);
        return;
    }
    
    // เพิ่มข้อมูลชั้นเรียน
    $classroom = $data['classroom'] ?? null;
    
    $sql = "INSERT INTO classes (level, department_id, group_number, academic_year_id, classroom, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$level, $department_id, $group_number, $academic_year_id, $classroom]);
    
    $class_id = $db->lastInsertId();
    
    // บันทึกประวัติการดำเนินการ (ถ้ามีตาราง)
    if ($_SESSION['user_role'] === 'admin') {
        $sql = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                VALUES (?, 'add_class', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            "เพิ่มชั้นเรียน $level/$group_number แผนก $department_id"
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'class_id' => $class_id]);
}

// ฟังก์ชันอัปเดตข้อมูลชั้นเรียน
function updateClass($class_id, $data) {
    $db = getDB();
    
    // ตรวจสอบว่ามีชั้นเรียนนี้หรือไม่
    $sql = "SELECT * FROM classes WHERE class_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลชั้นเรียน']);
        return;
    }
    
    // รวบรวมข้อมูลที่จะอัปเดต
    $level = $data['level'] ?? $class['level'];
    $department_id = $data['department_id'] ?? $class['department_id'];
    $group_number = $data['group_number'] ?? $class['group_number'];
    $classroom = $data['classroom'] ?? $class['classroom'];
    $is_active = isset($data['is_active']) ? $data['is_active'] : $class['is_active'];
    
    // ตรวจสอบว่ามีห้องเรียนซ้ำหรือไม่ (ถ้ามีการเปลี่ยนแปลง)
    if ($level !== $class['level'] || $department_id !== $class['department_id'] || 
        $group_number !== $class['group_number'] || $class['academic_year_id'] !== $class['academic_year_id']) {
        
        $sql = "SELECT class_id FROM classes 
                WHERE level = ? AND department_id = ? AND group_number = ? AND academic_year_id = ? AND class_id != ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$level, $department_id, $group_number, $class['academic_year_id'], $class_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'มีชั้นเรียนนี้อยู่แล้ว']);
            return;
        }
    }
    
    // อัปเดตข้อมูลชั้นเรียน
    $sql = "UPDATE classes 
            SET level = ?, department_id = ?, group_number = ?, classroom = ?, is_active = ?, updated_at = NOW()
            WHERE class_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$level, $department_id, $group_number, $classroom, $is_active, $class_id]);
    
    // บันทึกประวัติการดำเนินการ (ถ้ามีตาราง)
    if ($_SESSION['user_role'] === 'admin') {
        $sql = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                VALUES (?, 'edit_class', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            "แก้ไขชั้นเรียน ID: $class_id"
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}

// ฟังก์ชันลบชั้นเรียน
function deleteClass($class_id) {
    $db = getDB();
    
    // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
    $sql = "SELECT COUNT(*) FROM students WHERE current_class_id = ? AND status = 'กำลังศึกษา'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $student_count = $stmt->fetchColumn();
    
    if ($student_count > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่สามารถลบชั้นเรียนได้เนื่องจากมีนักเรียนในชั้นเรียนนี้ (' . $student_count . ' คน)'
        ]);
        return;
    }
    
    // ลบครูที่ปรึกษาในชั้นเรียนนี้
    $sql = "DELETE FROM class_advisors WHERE class_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    
    // ลบชั้นเรียน (หรือกำหนดให้ไม่ใช้งาน)
    $sql = "UPDATE classes SET is_active = 0, updated_at = NOW() WHERE class_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    
    // บันทึกประวัติการดำเนินการ (ถ้ามีตาราง)
    if ($_SESSION['user_role'] === 'admin') {
        $sql = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                VALUES (?, 'remove_class', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            "ลบชั้นเรียน ID: $class_id"
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}

// ฟังก์ชันกำหนดครูที่ปรึกษา
function assignAdvisor($class_id, $teacher_id, $is_primary) {
    $db = getDB();
    
    // ตรวจสอบว่าครูคนนี้เป็นที่ปรึกษาชั้นเรียนนี้อยู่แล้วหรือไม่
    $sql = "SELECT * FROM class_advisors WHERE class_id = ? AND teacher_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id, $teacher_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($is_primary) {
        // ถ้ากำหนดให้เป็นที่ปรึกษาหลัก ต้องยกเลิกที่ปรึกษาหลักคนเดิมก่อน
        $sql = "UPDATE class_advisors SET is_primary = 0 WHERE class_id = ? AND is_primary = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class_id]);
    }
    
    if ($existing) {
        // อัปเดตข้อมูลที่มีอยู่
        $sql = "UPDATE class_advisors SET is_primary = ? WHERE class_id = ? AND teacher_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$is_primary, $class_id, $teacher_id]);
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO class_advisors (class_id, teacher_id, is_primary) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class_id, $teacher_id, $is_primary]);
    }
    
    // บันทึกประวัติการดำเนินการ (ถ้ามีตาราง)
    if ($_SESSION['user_role'] === 'admin') {
        $sql = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                VALUES (?, 'manage_advisors', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            "กำหนดครูที่ปรึกษา $teacher_id ให้กับชั้นเรียน $class_id (หลัก: " . ($is_primary ? 'ใช่' : 'ไม่ใช่') . ")"
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}

// ฟังก์ชันลบครูที่ปรึกษา
function removeAdvisor($class_id, $teacher_id) {
    $db = getDB();
    
    // ตรวจสอบว่าครูคนนี้เป็นที่ปรึกษาชั้นเรียนนี้หรือไม่
    $sql = "SELECT * FROM class_advisors WHERE class_id = ? AND teacher_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id, $teacher_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ครูคนนี้ไม่ได้เป็นที่ปรึกษาชั้นเรียนนี้']);
        return;
    }
    
    // ลบข้อมูลครูที่ปรึกษา
    $sql = "DELETE FROM class_advisors WHERE class_id = ? AND teacher_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id, $teacher_id]);
    
    // บันทึกประวัติการดำเนินการ (ถ้ามีตาราง)
    if ($_SESSION['user_role'] === 'admin') {
        $sql = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                VALUES (?, 'manage_advisors', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'],
            "ลบครูที่ปรึกษา $teacher_id ออกจากชั้นเรียน $class_id"
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
}
<?php

/**
 * students.php - หน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     header('Location: login.php');
//     exit;
// }

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';




// ใน students.php - ปรับปรุงส่วนการดึงข้อมูลนักเรียน
// โดยใช้ฟังก์ชันจาก Model มาแทนการเขียนคำสั่ง SQL โดยตรง

// เพิ่ม include ไฟล์ model
require_once '../models/students_model.php';

// สร้างตัวแปรสำหรับเก็บเงื่อนไขการกรอง
$filters = [
    'name' => $_GET['name'] ?? '',
    'student_code' => $_GET['student_code'] ?? '',
    'level' => $_GET['level'] ?? '',
    'group_number' => $_GET['group_number'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'attendance_status' => $_GET['attendance_status'] ?? '',
    'line_status' => $_GET['line_status'] ?? ''
];

// ดึงข้อมูลนักเรียนผ่าน model function
$students = [];
try {
    $conn = getDB();
    
    // แก้ไขคำสั่ง SQL ให้กระชับและป้องกันการซ้ำ
    $query = "SELECT DISTINCT
        s.student_id,
        s.student_code,
        s.status,
        s.current_class_id,
        u.title,
        u.first_name,
        u.last_name,
        c.level,
        c.group_number,
        d.department_name
    FROM students s
    INNER JOIN users u ON s.user_id = u.user_id 
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    LEFT JOIN departments d ON c.department_id = d.department_id
    WHERE s.student_code IS NOT NULL";

    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($filters['name'])) {
        $query .= " AND (u.first_name LIKE :name OR u.last_name LIKE :name)";
    }
    if (!empty($filters['student_code'])) {
        $query .= " AND s.student_code LIKE :student_code";
    }
    if (!empty($filters['level'])) {
        $query .= " AND c.level = :level";
    }
    if (!empty($filters['group_number'])) {
        $query .= " AND c.group_number = :group_number";
    }
    if (!empty($filters['department_id'])) {
        $query .= " AND d.department_id = :department_id";
    }
    if (!empty($filters['status'])) {
        $query .= " AND s.status = :status";
    }

    $query .= " ORDER BY s.student_code";

    $stmt = $conn->prepare($query);

    // Bind parameters
    if (!empty($filters['name'])) {
        $stmt->bindValue(':name', '%' . $filters['name'] . '%');
    }
    if (!empty($filters['student_code'])) {
        $stmt->bindValue(':student_code', '%' . $filters['student_code'] . '%');
    }
    if (!empty($filters['level'])) {
        $stmt->bindValue(':level', $filters['level']);
    }
    if (!empty($filters['group_number'])) {
        $stmt->bindValue(':group_number', $filters['group_number']);
    }
    if (!empty($filters['department_id'])) {
        $stmt->bindValue(':department_id', $filters['department_id']);
    }
    if (!empty($filters['status'])) {
        $stmt->bindValue(':status', $filters['status']);
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มข้อมูลพื้นฐานที่จำเป็น
    foreach ($students as &$student) {
        $student['class'] = ($student['level'] ? $student['level'] : '-') . '/' . 
                           ($student['group_number'] ? $student['group_number'] : '-');
        $student['attendance_rate'] = 100;
        $student['attendance_status'] = 'ปกติ';
        $student['line_connected'] = false;
    }

    // Debug ข้อมูล
    // echo "<pre>";
    // print_r($students);
    // echo "</pre>";

} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $students = [];
}

// ดึงข้อมูลแผนกวิชาทั้งหมด
$departments = [];
try {
    $conn = getDB();
    $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

// ดึงข้อมูลชั้นเรียนทั้งหมด
$classes = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT c.class_id, c.level, c.group_number, d.department_name, c.department_id
        FROM classes c 
        JOIN departments d ON c.department_id = d.department_id
        JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE ay.is_active = 1
        ORDER BY c.level, c.group_number
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching classes: " . $e->getMessage());
}

// ดึงข้อมูลครูทั้งหมด
$teachers = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT t.teacher_id, t.title, t.first_name, t.last_name, d.department_name
        FROM teachers t
        LEFT JOIN departments d ON t.department_id = d.department_id
        ORDER BY t.first_name, t.last_name
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
}

// ดึงสถิติจำนวนนักเรียน
$student_stats = [
    'total' => 0,
    'male' => 0,
    'female' => 0,
    'risk' => 0
];

try {
    $conn = getDB();

    // ดึงจำนวนนักเรียนทั้งหมดที่กำลังศึกษา
    $queryTotal = "SELECT COUNT(*) as total FROM students WHERE status = 'กำลังศึกษา'";
    $stmtTotal = $conn->prepare($queryTotal);
    $stmtTotal->execute();
    $student_stats['total'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // ดึงจำนวนนักเรียนชาย
    $queryMale = "SELECT COUNT(*) as male 
                FROM students s 
                JOIN users u ON s.user_id = u.user_id
                WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นาย', 'เด็กชาย')";
    $stmtMale = $conn->prepare($queryMale);
    $stmtMale->execute();
    $student_stats['male'] = $stmtMale->fetch(PDO::FETCH_ASSOC)['male'] ?? 0;

    // ดึงจำนวนนักเรียนหญิง
    $queryFemale = "SELECT COUNT(*) as female 
                  FROM students s 
                  JOIN users u ON s.user_id = u.user_id
                  WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นางสาว', 'เด็กหญิง', 'นาง')";
    $stmtFemale = $conn->prepare($queryFemale);
    $stmtFemale->execute();
    $student_stats['female'] = $stmtFemale->fetch(PDO::FETCH_ASSOC)['female'] ?? 0;

    // ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
    $queryRisk = "SELECT COUNT(*) as risk FROM risk_students WHERE risk_level IN ('high', 'critical')";
    $stmtRisk = $conn->prepare($queryRisk);
    $stmtRisk->execute();
    $student_stats['risk'] = $stmtRisk->fetch(PDO::FETCH_ASSOC)['risk'] ?? 0;
} catch (PDOException $e) {
    error_log("Error fetching student statistics: " . $e->getMessage());
}


// ใน students.php - ปรับปรุงส่วนการเพิ่มนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['title'])) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        try {
            $conn = getDB();
            
            // ตรวจสอบรหัสนักเรียนซ้ำ
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_code = ?");
            $checkStmt->execute([trim($_POST['student_code'])]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("รหัสนักเรียนนี้มีในระบบแล้ว");
            }

            $conn->beginTransaction();

            // 1. สร้าง user ก่อน
            $userSql = "INSERT INTO users (title, first_name, last_name, role, created_at) 
                       VALUES (?, ?, ?, 'student', NOW())";
            $userStmt = $conn->prepare($userSql);
            $userStmt->execute([
                $_POST['title'],
                $_POST['firstname'],
                $_POST['lastname']
            ]);
            $userId = $conn->lastInsertId();

            // 2. สร้าง student
            $studentSql = "INSERT INTO students (user_id, student_code, current_class_id, status, created_at) 
                          VALUES (?, ?, ?, 'กำลังศึกษา', NOW())";
            $studentStmt = $conn->prepare($studentSql);
            $studentStmt->execute([
                $userId,
                trim($_POST['student_code']),
                $_POST['class_id'] ?? null
            ]);

            $conn->commit();
            $_SESSION['success_message'] = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
            header("Location: students.php?success=add");
            exit;

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            error_log("Error adding student: " . $e->getMessage());
        }
    }
}

// ส่วนการแก้ไขข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['student_id']) || empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        try {
            // เรียกใช้ฟังก์ชัน updateStudent จาก model
            $result = updateStudent([
                'student_id' => $_POST['student_id'],
                'student_code' => trim($_POST['student_code']),
                'title' => $_POST['title'],
                'firstname' => $_POST['firstname'],
                'lastname' => $_POST['lastname'],
                'phone_number' => $_POST['phone_number'] ?? '',
                'email' => $_POST['email'] ?? '',
                'class_id' => $_POST['class_id'] ?? null,
                'status' => $_POST['status'] ?? 'กำลังศึกษา'
            ]);

            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                header("Location: students.php?success=edit");
                exit;
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            error_log("Error updating student: " . $e->getMessage());
        }
    }
}

// ส่วนการลบข้อมูลนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (empty($_POST['student_id'])) {
        $error_message = "ไม่ระบุรหัสนักเรียนที่ต้องการลบ";
    } else {
        try {
            // เรียกใช้ฟังก์ชัน deleteStudent จาก model
            $result = deleteStudent($_POST['student_id']);

            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
                header("Location: students.php?success=delete");
                exit;
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            error_log("Error deleting student: " . $e->getMessage());
        }
    }
}


// ตรวจสอบการแสดงข้อความแจ้งเตือนจาก URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'add':
            $success_message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'edit':
            $success_message = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'delete':
            $success_message = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
        case 'import':
            $success_message = "นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว";
            break;
    }
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/students.css'
];

$extra_js = [
    'assets/js/students.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/students_content.php';

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'statistics' => $student_stats,
    'departments' => $departments,
    'classes' => $classes,
    'teachers' => $teachers
];

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';

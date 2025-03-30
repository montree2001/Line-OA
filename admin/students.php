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
$students = getAllStudents($filters);

// ดึงข้อมูลสถิติจำนวนนักเรียน
$student_stats = getStudentStatistics();

// ดึงข้อมูลชั้นเรียนทั้งหมด
$classes = getAllClasses();

// ดึงข้อมูลแผนกวิชา
$departments = []; // ดึงข้อมูลแผนกวิชาจากฐานข้อมูล
try {
    $conn = getDB();
    $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'statistics' => $student_stats,
    'departments' => $departments,
    'classes' => $classes
];




// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'students';
$page_title = 'จัดการข้อมูลนักเรียน';
$page_header = 'จัดการข้อมูลนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้ดูแลระบบ',
    'role' => $_SESSION['user_role'] ?? 'เจ้าหน้าที่',
    'initials' => mb_substr($_SESSION['user_name'] ?? 'ป', 0, 1, 'UTF-8')
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มนักเรียนใหม่',
        'icon' => 'person_add',
        'onclick' => 'showAddStudentModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'file_upload',
        'onclick' => 'showImportModal()'
    ]
];

// สร้างเงื่อนไขการค้นหา
$where_conditions = [];
$params = [];

if (isset($_GET['name']) && !empty($_GET['name'])) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_name = '%' . $_GET['name'] . '%';
    $params[] = $search_name;
    $params[] = $search_name;
}

if (isset($_GET['student_code']) && !empty($_GET['student_code'])) {
    $where_conditions[] = "s.student_code LIKE ?";
    $params[] = '%' . $_GET['student_code'] . '%';
}

if (isset($_GET['level']) && !empty($_GET['level'])) {
    $where_conditions[] = "c.level = ?";
    $params[] = $_GET['level'];
}

if (isset($_GET['group_number']) && !empty($_GET['group_number'])) {
    $where_conditions[] = "c.group_number = ?";
    $params[] = $_GET['group_number'];
}

if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $where_conditions[] = "c.department_id = ?";
    $params[] = $_GET['department_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "s.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['attendance_status']) && !empty($_GET['attendance_status'])) {
    // สร้างเงื่อนไขสำหรับสถานะการเข้าแถว (จะต้องคำนวณในภายหลัง)
    $attendance_condition = true;
} else {
    $attendance_condition = false;
}

if (isset($_GET['line_status']) && !empty($_GET['line_status'])) {
    if ($_GET['line_status'] === 'connected') {
        $where_conditions[] = "u.line_id IS NOT NULL AND u.line_id != ''";
    } else if ($_GET['line_status'] === 'not_connected') {
        $where_conditions[] = "(u.line_id IS NULL OR u.line_id = '')";
    }
}

// สร้าง SQL condition
$sql_condition = "";
if (!empty($where_conditions)) {
    $sql_condition = " WHERE " . implode(" AND ", $where_conditions);
}

// ดึงข้อมูลนักเรียนจากฐานข้อมูล
$students = [];

/**
 * ส่วนของการดึงข้อมูลนักเรียนและเตรียมข้อมูลสำหรับแสดงผล
 * ให้แก้ไขในส่วนนี้ของไฟล์ students.php
 */

// ให้ไปแทนที่โค้ดเดิมในส่วนการดึงข้อมูล
$students = [];
try {
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน - ใช้ GROUP BY เพื่อป้องกันข้อมูลซ้ำซ้อน
    $query = "SELECT s.student_id, s.student_code, s.status, 
          u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email,
          c.level, c.group_number, c.class_id,
          d.department_name, d.department_id,
          (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
           FROM class_advisors ca 
           JOIN teachers t ON ca.teacher_id = t.teacher_id 
           WHERE ca.class_id = c.class_id AND ca.is_primary = 1
           LIMIT 1) as advisor_name,
          IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
          IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
          FROM students s
          JOIN users u ON s.user_id = u.user_id
          LEFT JOIN classes c ON s.current_class_id = c.class_id
          LEFT JOIN departments d ON c.department_id = d.department_id
          $sql_condition
          GROUP BY s.student_id
          ORDER BY s.student_code";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query($query);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เติมข้อมูลเพิ่มเติม
    foreach ($students as &$student) {
        // สร้างชื่อชั้นเรียน
        $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        
        // คำนวณอัตราการเข้าแถว
        $total_days = $student['attendance_days'] + $student['absence_days'];
        if ($total_days > 0) {
            $student['attendance_rate'] = ($student['attendance_days'] / $total_days) * 100;
        } else {
            $student['attendance_rate'] = 100; // ถ้ายังไม่มีข้อมูลให้เป็น 100%
        }
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] < 60) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] < 75) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
        
        // ตรวจสอบการเชื่อมต่อกับ LINE
        $student['line_connected'] = !empty($student['line_id']);
    }
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาด
    error_log("Error fetching students: " . $e->getMessage());
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
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['title'])) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        try {
            // เรียกใช้ฟังก์ชัน addStudent จาก model
            $result = addStudent([
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
                // สร้าง session message สำหรับแสดงข้อความแจ้งเตือน
                $_SESSION['success_message'] = $result['message'];
                header("Location: students.php?success=add");
                exit;
            } else {
                $error_message = $result['message'];
            }
        } catch (Exception $e) {
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
?>
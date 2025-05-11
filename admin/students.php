<?php
/**
 * students-fix.php - แก้ไขปัญหา SQL Error ในหน้าจัดการนักเรียน
 * ใช้งานโดยการนำโค้ดนี้ไปใช้แทนหรือแก้ไขไฟล์ students.php
 */

// เริ่ม session
session_start();
// ตรวจสอบการล็อกอิน (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];
// ฟังก์ชันสำหรับดึงข้อมูลนักเรียน
function getStudents($filters = []) {
    $conn = getDB();
    
    // สร้าง query พื้นฐาน
    $query = "
        SELECT 
            s.student_id,
            s.student_code,
            s.title,
            s.status,
            s.current_class_id,
            u.first_name,
            u.last_name,
            u.phone_number,
            u.email,
            u.line_id,
            c.level,
            c.group_number,
            d.department_name,
            CONCAT(c.level, '/', c.group_number) AS class,
            COALESCE(sar.total_attendance_days, 0) AS attendance_days,
            COALESCE(sar.total_absence_days, 0) AS absence_days,
            (CASE 
                WHEN u.line_id IS NOT NULL AND u.line_id NOT LIKE 'TEMP_%' THEN 1 
                ELSE 0 
            END) AS line_connected
        FROM 
            students s
        JOIN 
            users u ON s.user_id = u.user_id
        LEFT JOIN 
            classes c ON s.current_class_id = c.class_id
        LEFT JOIN 
            departments d ON c.department_id = d.department_id
        LEFT JOIN 
            student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = (
                SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
            )
        WHERE 1=1
    ";
    
    $params = [];
    
    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($filters['name'])) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchName = "%" . $filters['name'] . "%";
        $params[] = $searchName;
        $params[] = $searchName;
    }
    
    if (!empty($filters['student_code'])) {
        $query .= " AND s.student_code LIKE ?";
        $params[] = "%" . $filters['student_code'] . "%";
    }
    
    if (!empty($filters['level'])) {
        $query .= " AND c.level = ?";
        $params[] = $filters['level'];
    }
    
    if (!empty($filters['group_number'])) {
        $query .= " AND c.group_number = ?";
        $params[] = $filters['group_number'];
    }
    
    if (!empty($filters['department_id'])) {
        $query .= " AND c.department_id = ?";
        $params[] = $filters['department_id'];
    }
    
    if (!empty($filters['status'])) {
        $query .= " AND s.status = ?";
        $params[] = $filters['status'];
    }
    
    // แก้ไขส่วนของการกรองตามสถานะเข้าแถว - ใช้ attendance_status แทน is_present
    if (!empty($filters['attendance_status'])) {
        if ($filters['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
            // หาเกณฑ์ความเสี่ยงจากการตั้งค่าระบบ
            $settingQuery = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_critical'";
            $stmt = $conn->prepare($settingQuery);
            $stmt->execute();
            $riskThreshold = $stmt->fetchColumn();
            
            if (!$riskThreshold) $riskThreshold = 50; // ค่าเริ่มต้นถ้าไม่พบการตั้งค่า
            
            // เงื่อนไขสำหรับนักเรียนที่เสี่ยงตกกิจกรรม
            $query .= " AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
            ) <= ?";
            $params[] = $riskThreshold;
        } elseif ($filters['attendance_status'] === 'ต้องระวัง') {
            // หาเกณฑ์ความเสี่ยงจากการตั้งค่าระบบ
            $settingQuery = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
            $stmt = $conn->prepare($settingQuery);
            $stmt->execute();
            $riskThreshold = $stmt->fetchColumn();
            
            if (!$riskThreshold) $riskThreshold = 60; // ค่าเริ่มต้นถ้าไม่พบการตั้งค่า
            
            // เงื่อนไขสำหรับนักเรียนที่ต้องระวัง
            $query .= " AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
            ) <= ? AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
            ) > ?";
            $params[] = $riskThreshold;
            
            $settingQuery = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_critical'";
            $stmt = $conn->prepare($settingQuery);
            $stmt->execute();
            $criticalThreshold = $stmt->fetchColumn();
            
            if (!$criticalThreshold) $criticalThreshold = 50; // ค่าเริ่มต้นถ้าไม่พบการตั้งค่า
            $params[] = $criticalThreshold;
        } elseif ($filters['attendance_status'] === 'ปกติ') {
            // หาเกณฑ์ความเสี่ยงจากการตั้งค่าระบบ
            $settingQuery = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
            $stmt = $conn->prepare($settingQuery);
            $stmt->execute();
            $riskThreshold = $stmt->fetchColumn();
            
            if (!$riskThreshold) $riskThreshold = 60; // ค่าเริ่มต้นถ้าไม่พบการตั้งค่า
            
            // เงื่อนไขสำหรับนักเรียนที่มีสถานะปกติ
            $query .= " AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
            ) > ?";
            $params[] = $riskThreshold;
        }
    }
    
    // กรองตามสถานะการเชื่อมต่อ LINE
    if (!empty($filters['line_status'])) {
        if ($filters['line_status'] === 'connected') {
            $query .= " AND u.line_id IS NOT NULL AND u.line_id NOT LIKE 'TEMP_%'";
        } elseif ($filters['line_status'] === 'not_connected') {
            $query .= " AND (u.line_id IS NULL OR u.line_id LIKE 'TEMP_%')";
        }
    }
    
    // จัดเรียงข้อมูล
    $query .= " ORDER BY s.student_code ASC";
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถว
    foreach ($students as &$student) {
        $totalDays = $student['attendance_days'] + $student['absence_days'];
        $student['attendance_rate'] = $totalDays > 0 ? ($student['attendance_days'] / $totalDays) * 100 : 100;
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] <= 50) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] <= 60) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
    }
    
    return $students;
}

// ฟังก์ชันสำหรับดึงสถิติข้อมูลนักเรียน
function getStudentStatistics() {
    $conn = getDB();
    
    $statistics = [
        'total' => 0,
        'male' => 0,
        'female' => 0,
        'risk' => 0
    ];
    
    // จำนวนนักเรียนทั้งหมดที่กำลังศึกษา
    $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
    $stmt = $conn->query($query);
    $statistics['total'] = $stmt->fetchColumn();
    
    // จำนวนนักเรียนชาย (นาย, เด็กชาย)
    $query = "SELECT COUNT(*) FROM students s 
              JOIN users u ON s.user_id = u.user_id
              WHERE s.status = 'กำลังศึกษา' AND (s.title = 'นาย' OR s.title = 'เด็กชาย')";
    $stmt = $conn->query($query);
    $statistics['male'] = $stmt->fetchColumn();
    
    // จำนวนนักเรียนหญิง (นางสาว, เด็กหญิง, นาง)
    $query = "SELECT COUNT(*) FROM students s 
              JOIN users u ON s.user_id = u.user_id
              WHERE s.status = 'กำลังศึกษา' AND (s.title = 'นางสาว' OR s.title = 'เด็กหญิง' OR s.title = 'นาง')";
    $stmt = $conn->query($query);
    $statistics['female'] = $stmt->fetchColumn();
    
    // จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
    $query = "
        SELECT COUNT(*) FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id
        WHERE s.status = 'กำลังศึกษา' AND sar.academic_year_id = (
            SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
        ) AND (
            CASE 
                WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                ELSE 100 
            END
        ) <= 50
    ";
    $stmt = $conn->query($query);
    $statistics['risk'] = $stmt->fetchColumn();
    
    return $statistics;
}

// ฟังก์ชันสำหรับดึงข้อมูลแผนกวิชา
function getDepartments() {
    $conn = getDB();
    
    $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันสำหรับดึงข้อมูลชั้นเรียน
function getClassGroups() {
    $conn = getDB();
    
    $query = "
        SELECT 
            c.class_id,
            c.level,
            c.group_number,
            d.department_id,
            d.department_name
        FROM 
            classes c
        JOIN 
            departments d ON c.department_id = d.department_id
        JOIN 
            academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE 
            c.is_active = 1 AND ay.is_active = 1
        ORDER BY 
            c.level, d.department_name, c.group_number
    ";
    $stmt = $conn->query($query);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดกลุ่มตามระดับชั้น
    $classGroups = [];
    foreach ($classes as $class) {
        $level = $class['level'];
        if (!isset($classGroups[$level])) {
            $classGroups[$level] = [];
        }
        $classGroups[$level][] = $class;
    }
    
    return $classGroups;
}

// ฟังก์ชันสำหรับบันทึกนักเรียนใหม่
function addStudent($data) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // สร้าง line_id ชั่วคราวที่ไม่ซ้ำกัน
        $tempLineId = 'TEMP_' . $data['student_code'] . '_' . time() . '_' . bin2hex(random_bytes(3));
        
        // 1. เพิ่มข้อมูลในตาราง users
        $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                    VALUES (?, 'student', ?, ?, ?, ?, ?, 1)";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $tempLineId,
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? '',
            $data['email'] ?? ''
        ]);
        
        $userId = $conn->lastInsertId();
        
        // 2. เพิ่มข้อมูลในตาราง students
        $studentQuery = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                       VALUES (?, ?, ?, ?, ?)";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([
            $userId,
            $data['student_code'],
            $data['title'],
            !empty($data['class_id']) ? $data['class_id'] : null,
            $data['status']
        ]);
        
        $studentId = $conn->lastInsertId();
        
        // 3. เพิ่มประวัติการศึกษาถ้ามีชั้นเรียน
        if (!empty($data['class_id'])) {
            $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
            $yearStmt = $conn->prepare($yearQuery);
            $yearStmt->execute([$data['class_id']]);
            $academicYearId = $yearStmt->fetchColumn();
            
            if ($academicYearId) {
                $recordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                              VALUES (?, ?, ?)";
                $recordStmt = $conn->prepare($recordQuery);
                $recordStmt->execute([$studentId, $academicYearId, $data['class_id']]);
            }
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error adding student: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับแก้ไขข้อมูลนักเรียน
function editStudent($data) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // 1. ดึงข้อมูล user_id ของนักเรียน
        $query = "SELECT user_id, current_class_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['student_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception("ไม่พบข้อมูลนักเรียน");
        }
        
        $userId = $student['user_id'];
        $currentClassId = $student['current_class_id'];
        
        // 2. อัพเดตข้อมูลในตาราง users
        $userQuery = "UPDATE users SET 
                    title = ?, 
                    first_name = ?, 
                    last_name = ?, 
                    phone_number = ?, 
                    email = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $data['title'],
            $data['firstname'],
            $data['lastname'],
            $data['phone_number'] ?? '',
            $data['email'] ?? '',
            $userId
        ]);
        
        // 3. อัพเดตข้อมูลในตาราง students
        $studentQuery = "UPDATE students SET 
                       student_code = ?,
                       title = ?,
                       status = ?,
                       updated_at = CURRENT_TIMESTAMP";
        $studentParams = [
            $data['student_code'],
            $data['title'],
            $data['status']
        ];
        
        // เพิ่มการอัพเดตชั้นเรียนถ้ามี
        if (!empty($data['class_id'])) {
            $studentQuery .= ", current_class_id = ?";
            $studentParams[] = $data['class_id'];
        }
        
        $studentQuery .= " WHERE student_id = ?";
        $studentParams[] = $data['student_id'];
        
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute($studentParams);
        
        // 4. เพิ่มหรืออัพเดตประวัติการศึกษาถ้ามีการเปลี่ยนชั้นเรียน
        if (!empty($data['class_id']) && $data['class_id'] != $currentClassId) {
            $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
            $yearStmt = $conn->prepare($yearQuery);
            $yearStmt->execute([$data['class_id']]);
            $academicYearId = $yearStmt->fetchColumn();
            
            if ($academicYearId) {
                // ตรวจสอบว่ามีประวัติการศึกษาในปีการศึกษานี้หรือไม่
                $recordQuery = "SELECT record_id FROM student_academic_records 
                              WHERE student_id = ? AND academic_year_id = ?";
                $recordStmt = $conn->prepare($recordQuery);
                $recordStmt->execute([$data['student_id'], $academicYearId]);
                $recordId = $recordStmt->fetchColumn();
                
                if ($recordId) {
                    // อัพเดตประวัติการศึกษาที่มีอยู่แล้ว
                    $updateRecordQuery = "UPDATE student_academic_records 
                                        SET class_id = ?, 
                                        updated_at = CURRENT_TIMESTAMP
                                        WHERE record_id = ?";
                    $updateRecordStmt = $conn->prepare($updateRecordQuery);
                    $updateRecordStmt->execute([$data['class_id'], $recordId]);
                } else {
                    // สร้างประวัติการศึกษาใหม่
                    $newRecordQuery = "INSERT INTO student_academic_records 
                                     (student_id, academic_year_id, class_id)
                                     VALUES (?, ?, ?)";
                    $newRecordStmt = $conn->prepare($newRecordQuery);
                    $newRecordStmt->execute([$data['student_id'], $academicYearId, $data['class_id']]);
                }
            }
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error editing student: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับลบข้อมูลนักเรียน
function deleteStudent($studentId) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // 1. ดึงข้อมูล user_id ของนักเรียน
        $query = "SELECT user_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            throw new Exception("ไม่พบข้อมูลนักเรียน");
        }
        
        // 2. ลบข้อมูลจากตาราง student_academic_records
        $query = "DELETE FROM student_academic_records WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        
        // 3. ลบข้อมูลจากตาราง parent_student_relation
        $query = "DELETE FROM parent_student_relation WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        
        // 4. ลบข้อมูลจากตาราง attendance
        $query = "DELETE FROM attendance WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        
        // 5. ลบข้อมูลจากตาราง students
        $query = "DELETE FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        
        // 6. ลบข้อมูลจากตาราง users
        $query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error deleting student: " . $e->getMessage());
        return false;
    }
}

// ตรวจสอบการ submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ตรวจสอบประเภทการทำงาน
    if ($action === 'add') {
        // เพิ่มนักเรียนใหม่
        $result = addStudent($_POST);
        if ($result) {
            $_SESSION['success_message'] = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูลนักเรียน";
        }
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: students.php");
        exit;
    } elseif ($action === 'edit') {
        // แก้ไขข้อมูลนักเรียน
        $result = editStudent($_POST);
        if ($result) {
            $_SESSION['success_message'] = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูลนักเรียน";
        }
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: students.php");
        exit;
    } elseif ($action === 'delete') {
        // ลบข้อมูลนักเรียน
        $result = deleteStudent($_POST['student_id']);
        if ($result) {
            $_SESSION['success_message'] = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน";
        }
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: students.php");
        exit;
    }
}

// ดึงข้อมูลสำหรับแสดงผล
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

$data = [
    'students' => getStudents($filters),
    'departments' => getDepartments(),
    'statistics' => getStudentStatistics(),
    'classGroups' => getClassGroups()
];

// กำหนดตัวแปรสำหรับเทมเพลต
$page_title = "จัดการข้อมูลนักเรียน";
$page_header = "จัดการข้อมูลนักเรียน";
$current_page = "students";
$content_path = "pages/students_content.php";

// แสดงข้อความแจ้งเตือน (ถ้ามี)
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

// ล้างข้อความแจ้งเตือนใน session
if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    unset($_SESSION['error_message']);
}

// เพิ่ม CSS และ JS สำหรับหน้านี้
$extra_css = [
    "assets/css/students.css",
    "https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css",
    "https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css"
];

$extra_js = [
    "https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js",
    "https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js",
    "assets/js/students.js",
    "assets/js/import-students.js"
];

// โหลดเทมเพลต
include "templates/header.php";
include "templates/sidebar.php";
include "templates/main_content.php";
include "templates/footer.php";
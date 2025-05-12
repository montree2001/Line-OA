<?php
/**
 * users.php - หน้าจัดการข้อมูลผู้ใช้งานระบบ
 * ระบบ STUDENT-Prasat (น้องชูใจ AI)
 */

// เริ่ม session
session_start();
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้
function getUsers($filters = []) {
    $conn = getDB();
    
    // สร้าง query พื้นฐาน
    $query = "
        SELECT 
            u.user_id,
            u.line_id,
            u.role,
            u.title,
            u.first_name,
            u.last_name,
            u.profile_picture,
            u.phone_number,
            u.email,
            u.gdpr_consent,
            u.gdpr_consent_date,
            u.created_at,
            u.updated_at,
            u.last_login,
            CASE 
                WHEN u.role = 'student' THEN (SELECT s.student_code FROM students s WHERE s.user_id = u.user_id)
                ELSE NULL
            END AS student_code
        FROM 
            users u
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
    
    if (!empty($filters['role'])) {
        $query .= " AND u.role = ?";
        $params[] = $filters['role'];
    }
    
    if (!empty($filters['line_status'])) {
        if ($filters['line_status'] === 'connected') {
            $query .= " AND u.line_id IS NOT NULL AND u.line_id NOT LIKE 'TEMP_%'";
        } elseif ($filters['line_status'] === 'not_connected') {
            $query .= " AND (u.line_id IS NULL OR u.line_id LIKE 'TEMP_%')";
        }
    }
    
    // จัดเรียงข้อมูล
    $query .= " ORDER BY u.role ASC, u.first_name ASC";
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $users;
}

// ฟังก์ชันสำหรับดึงสถิติข้อมูลผู้ใช้
function getUserStatistics() {
    $conn = getDB();
    
    $statistics = [
        'total' => 0,
        'students' => 0,
        'teachers' => 0,
        'parents' => 0,
        'admins' => 0,
        'line_connected' => 0
    ];
    
    // จำนวนผู้ใช้ทั้งหมด
    $query = "SELECT COUNT(*) FROM users";
    $stmt = $conn->query($query);
    $statistics['total'] = $stmt->fetchColumn();
    
    // จำนวนนักเรียน
    $query = "SELECT COUNT(*) FROM users WHERE role = 'student'";
    $stmt = $conn->query($query);
    $statistics['students'] = $stmt->fetchColumn();
    
    // จำนวนครู
    $query = "SELECT COUNT(*) FROM users WHERE role = 'teacher'";
    $stmt = $conn->query($query);
    $statistics['teachers'] = $stmt->fetchColumn();
    
    // จำนวนผู้ปกครอง
    $query = "SELECT COUNT(*) FROM users WHERE role = 'parent'";
    $stmt = $conn->query($query);
    $statistics['parents'] = $stmt->fetchColumn();
    
    // จำนวนผู้ดูแลระบบ
    $query = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
    $stmt = $conn->query($query);
    $statistics['admins'] = $stmt->fetchColumn();
    
    // จำนวนผู้ใช้ที่เชื่อมต่อ LINE
    $query = "SELECT COUNT(*) FROM users WHERE line_id IS NOT NULL AND line_id NOT LIKE 'TEMP_%'";
    $stmt = $conn->query($query);
    $statistics['line_connected'] = $stmt->fetchColumn();
    
    return $statistics;
}

// ฟังก์ชันสำหรับแก้ไขข้อมูลผู้ใช้
function editUser($data) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // อัพเดตข้อมูลในตาราง users
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
            $data['user_id']
        ]);
        
        // ถ้าเป็นนักเรียน ต้องอัพเดตข้อมูลในตาราง students ด้วย
        if (isset($data['role']) && $data['role'] === 'student' && isset($data['student_code'])) {
            $studentQuery = "UPDATE students SET 
                           title = ?,
                           student_code = ?
                           WHERE user_id = ?";
            $studentStmt = $conn->prepare($studentQuery);
            $studentStmt->execute([
                $data['title'],
                $data['student_code'],
                $data['user_id']
            ]);
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error editing user: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับลบข้อมูลผู้ใช้
function deleteUser($userId) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // ตรวจสอบบทบาทของผู้ใช้ก่อนลบ
        $query = "SELECT role FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        if ($role === 'student') {
            // ลบข้อมูลจากตาราง student_academic_records
            $query = "DELETE FROM student_academic_records WHERE student_id IN (SELECT student_id FROM students WHERE user_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // ลบข้อมูลจากตาราง parent_student_relation
            $query = "DELETE FROM parent_student_relation WHERE student_id IN (SELECT student_id FROM students WHERE user_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // ลบข้อมูลจากตาราง attendance
            $query = "DELETE FROM attendance WHERE student_id IN (SELECT student_id FROM students WHERE user_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // ลบข้อมูลจากตาราง students
            $query = "DELETE FROM students WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
        } elseif ($role === 'teacher') {
            // ลบข้อมูลจากตาราง class_advisors
            $query = "DELETE FROM class_advisors WHERE teacher_id IN (SELECT teacher_id FROM teachers WHERE user_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // ลบข้อมูลจากตาราง teachers
            $query = "DELETE FROM teachers WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
        } elseif ($role === 'parent') {
            // ลบข้อมูลจากตาราง parent_student_relation
            $query = "DELETE FROM parent_student_relation WHERE parent_id IN (SELECT parent_id FROM parents WHERE user_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // ลบข้อมูลจากตาราง parents
            $query = "DELETE FROM parents WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
        }
        
        // ลบข้อมูลจากตาราง users
        $query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

// ตรวจสอบการ submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ตรวจสอบประเภทการทำงาน
    if ($action === 'edit') {
        // แก้ไขข้อมูลผู้ใช้
        $result = editUser($_POST);
        if ($result) {
            $_SESSION['success_message'] = "แก้ไขข้อมูลผู้ใช้เรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูลผู้ใช้";
        }
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: users.php");
        exit;
    } elseif ($action === 'delete') {
        // ลบข้อมูลผู้ใช้
        $result = deleteUser($_POST['user_id']);
        if ($result) {
            $_SESSION['success_message'] = "ลบข้อมูลผู้ใช้เรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูลผู้ใช้";
        }
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: users.php");
        exit;
    } elseif ($action === 'reset_line') {
        // รีเซ็ตการเชื่อมต่อ LINE
        try {
            $conn = getDB();
            $userId = $_POST['user_id'];
            
            // สร้าง temporary LINE ID ใหม่
            $tempLineId = 'TEMP_' . time() . '_' . bin2hex(random_bytes(3));
            
            $query = "UPDATE users SET line_id = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$tempLineId, $userId]);
            
            $_SESSION['success_message'] = "รีเซ็ตการเชื่อมต่อ LINE เรียบร้อยแล้ว";
        } catch (Exception $e) {
            error_log("Error resetting LINE connection: " . $e->getMessage());
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการรีเซ็ตการเชื่อมต่อ LINE";
        }
        
        // Redirect เพื่อหลีกเลี่ยงการ resubmit form
        header("Location: users.php");
        exit;
    }
}

// ดึงข้อมูลสำหรับแสดงผล
$filters = [
    'name' => $_GET['name'] ?? '',
    'role' => $_GET['role'] ?? '',
    'line_status' => $_GET['line_status'] ?? ''
];

$data = [
    'users' => getUsers($filters),
    'statistics' => getUserStatistics()
];

// กำหนดตัวแปรสำหรับเทมเพลต
$page_title = "จัดการข้อมูลผู้ใช้งาน";
$page_header = "จัดการข้อมูลผู้ใช้งาน";
$current_page = "users";
$content_path = "pages/users_content.php";

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
    "assets/css/users.css",
    "https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css",
    "https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css"
];

$extra_js = [
    "https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js",
    "https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js",
    "assets/js/users.js"
];

// โหลดเทมเพลต
include "templates/header.php";
include "templates/sidebar.php";
include "templates/main_content.php";
include "templates/footer.php";
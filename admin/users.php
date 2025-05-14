<?php
/**
 * users.php - หน้าจัดการข้อมูลผู้ใช้งานระบบน้องชูใจ AI ดูแลผู้เรียน
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
                WHEN u.role = 'student' THEN (
                    SELECT s.student_code
                    FROM students s 
                    WHERE s.user_id = u.user_id
                    LIMIT 1
                )
                ELSE NULL
            END as student_code,
            CASE 
                WHEN u.role = 'student' THEN (
                    SELECT CONCAT(c.level, '/', c.group_number, ' ', d.department_name)
                    FROM students s 
                    LEFT JOIN classes c ON s.current_class_id = c.class_id
                    LEFT JOIN departments d ON c.department_id = d.department_id
                    WHERE s.user_id = u.user_id
                    LIMIT 1
                )
                ELSE NULL
            END as class_info,
            CASE 
                WHEN u.role = 'student' THEN (
                    SELECT s.status
                    FROM students s 
                    WHERE s.user_id = u.user_id
                    LIMIT 1
                )
                ELSE NULL
            END as student_status,
            CASE 
                WHEN u.role = 'teacher' THEN (
                    SELECT d.department_name
                    FROM teachers t 
                    LEFT JOIN departments d ON t.department_id = d.department_id
                    WHERE t.user_id = u.user_id
                    LIMIT 1
                )
                ELSE NULL
            END as teacher_department,
            CASE 
                WHEN u.role = 'parent' THEN (
                    SELECT COUNT(psr.student_id)
                    FROM parents p 
                    LEFT JOIN parent_student_relation psr ON p.parent_id = psr.parent_id
                    WHERE p.user_id = u.user_id
                )
                ELSE NULL
            END as parent_children_count
        FROM 
            users u
        WHERE 1=1
    ";
    
    $params = [];
    
    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($filters['search'])) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.line_id LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
        $searchTerm = "%" . $filters['search'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['role'])) {
        $query .= " AND u.role = ?";
        $params[] = $filters['role'];
    }
    
    if (isset($filters['line_connected']) && $filters['line_connected'] !== '') {
        if ($filters['line_connected'] == '1') {
            $query .= " AND u.line_id IS NOT NULL AND u.line_id NOT LIKE 'TEMP_%'";
        } else {
            $query .= " AND (u.line_id IS NULL OR u.line_id LIKE 'TEMP_%')";
        }
    }
    
    // จัดเรียงข้อมูล
    $query .= " ORDER BY u.created_at DESC";
    
    // จำกัดผลลัพธ์
    if (!empty($filters['limit'])) {
        $query .= " LIMIT ?";
        $params[] = (int)$filters['limit'];
    }
    
    // ดึงข้อมูล
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $users;
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้รายบุคคล
function getUserById($userId) {
    $conn = getDB();
    
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$userId]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ฟังก์ชันสำหรับแก้ไขข้อมูลผู้ใช้
function updateUser($data) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // อัพเดตข้อมูลพื้นฐาน
        $query = "UPDATE users SET 
                  title = ?,
                  first_name = ?,
                  last_name = ?,
                  phone_number = ?,
                  email = ?,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['first_name'],
            $data['last_name'],
            $data['phone_number'],
            $data['email'],
            $data['user_id']
        ]);
        
        // ถ้ามีการอัพเดตสถานะของนักเรียน
        if ($data['role'] === 'student' && !empty($data['student_status'])) {
            $query = "UPDATE students SET 
                      status = ?,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $data['student_status'],
                $data['user_id']
            ]);
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับลบผู้ใช้
function deleteUser($userId) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // ตรวจสอบบทบาทผู้ใช้
        $query = "SELECT role FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        // ลบข้อมูลตามบทบาท
        if ($role === 'student') {
            // ลบข้อมูลจากตารางที่เกี่ยวข้องกับนักเรียน
            $query = "SELECT student_id FROM students WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            $studentId = $stmt->fetchColumn();
            
            if ($studentId) {
                // ลบประวัติการศึกษา
                $query = "DELETE FROM student_academic_records WHERE student_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$studentId]);
                
                // ลบประวัติการเข้าแถว
                $query = "DELETE FROM attendance WHERE student_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$studentId]);
                
                // ลบความสัมพันธ์กับผู้ปกครอง
                $query = "DELETE FROM parent_student_relation WHERE student_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$studentId]);
                
                // ลบข้อมูลนักเรียน
                $query = "DELETE FROM students WHERE student_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$studentId]);
            }
        } elseif ($role === 'teacher') {
            // ลบข้อมูลจากตารางที่เกี่ยวข้องกับครู
            $query = "SELECT teacher_id FROM teachers WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            $teacherId = $stmt->fetchColumn();
            
            if ($teacherId) {
                // ลบความสัมพันธ์กับห้องเรียน
                $query = "DELETE FROM class_advisors WHERE teacher_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$teacherId]);
                
                // ลบข้อมูลครู
                $query = "DELETE FROM teachers WHERE teacher_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$teacherId]);
            }
        } elseif ($role === 'parent') {
            // ลบข้อมูลจากตารางที่เกี่ยวข้องกับผู้ปกครอง
            $query = "SELECT parent_id FROM parents WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            $parentId = $stmt->fetchColumn();
            
            if ($parentId) {
                // ลบความสัมพันธ์กับนักเรียน
                $query = "DELETE FROM parent_student_relation WHERE parent_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$parentId]);
                
                // ลบข้อมูลผู้ปกครอง
                $query = "DELETE FROM parents WHERE parent_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$parentId]);
            }
        }
        
        // ลบการแจ้งเตือน
        $query = "DELETE FROM notifications WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        
        // ลบข้อมูลผู้ใช้
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

// ฟังก์ชันสำหรับดึงสถิติผู้ใช้
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
    
    // จำนวนแยกตามประเภท
    $query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $stmt = $conn->query($query);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        switch ($role['role']) {
            case 'student':
                $statistics['students'] = $role['count'];
                break;
            case 'teacher':
                $statistics['teachers'] = $role['count'];
                break;
            case 'parent':
                $statistics['parents'] = $role['count'];
                break;
            case 'admin':
                $statistics['admins'] = $role['count'];
                break;
        }
    }
    
    // จำนวนผู้ใช้ที่เชื่อมต่อ LINE
    $query = "SELECT COUNT(*) FROM users WHERE line_id IS NOT NULL AND line_id NOT LIKE 'TEMP_%'";
    $stmt = $conn->query($query);
    $statistics['line_connected'] = $stmt->fetchColumn();
    
    return $statistics;
}

// ตรวจสอบการ submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ตรวจสอบประเภทการทำงาน
    if ($action === 'edit') {
        // แก้ไขข้อมูลผู้ใช้
        $result = updateUser($_POST);
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
    }
}

// ดึงข้อมูลสำหรับแสดงผล
$filters = [
    'search' => $_GET['search'] ?? '',
    'role' => $_GET['role'] ?? '',
    'line_connected' => $_GET['line_connected'] ?? ''
];

$data = [
    'users' => getUsers($filters),
    'statistics' => getUserStatistics()
];

// กำหนดตัวแปรสำหรับเทมเพลต
$page_title = "จัดการข้อมูลผู้ใช้งานระบบ";
$page_header = "จัดการข้อมูลผู้ใช้งานระบบ";
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
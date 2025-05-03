<?php
/**
 * parents.php - หน้าจัดการข้อมูลผู้ปกครอง
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();
/* 
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
 */
// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ฟังก์ชันช่วยเหลือ
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// กำหนดตัวแปรสำหรับหน้าเพจ
$page_title = 'จัดการข้อมูลผู้ปกครอง';
$page_header = 'จัดการข้อมูลผู้ปกครอง';
$current_page = 'parents';
$hide_search = true;

/* // โหลดข้อมูลผู้ดูแลระบบ (admin) ที่ล็อกอินอยู่
$admin_id = $_SESSION['user_id'];
$stmt = getDB()->prepare("SELECT u.first_name, u.last_name, u.role FROM users u WHERE u.user_id = ?");
$stmt->execute([$admin_id]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_info = [
    'name' => $admin_user['first_name'] . ' ' . $admin_user['last_name'],
    'role' => $admin_user['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'เจ้าหน้าที่',
    'initials' => mb_substr($admin_user['first_name'], 0, 1, 'UTF-8')
]; */

// กำหนดปุ่มในส่วนหัว
$header_buttons = [
    [
        'id' => 'exportDataButton',
        'text' => 'ส่งออกข้อมูล',
        'icon' => 'file_download',
        'onclick' => 'exportParentsData()'
    ]
];

// กำหนด CSS และ JavaScript เพิ่มเติม
$extra_css = ['assets/css/parents.css'];
$extra_js = ['assets/js/parents.js'];

// ดึงข้อมูลผู้ปกครองทั้งหมด พร้อมกับข้อมูลที่เกี่ยวข้อง
function getParentsData($search = '', $filter = []) {
    $db = getDB();
    
    $query = "SELECT 
                p.parent_id, 
                u.user_id, 
                u.title, 
                u.first_name, 
                u.last_name, 
                u.phone_number, 
                u.email, 
                u.line_id, 
                p.relationship,
                (SELECT COUNT(*) FROM parent_student_relation psr WHERE psr.parent_id = p.parent_id) as student_count
              FROM parents p
              JOIN users u ON p.user_id = u.user_id
              WHERE 1=1";
    
    $params = [];
    
    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($search)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone_number LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // เพิ่มเงื่อนไขฟิลเตอร์
    if (!empty($filter['relationship']) && $filter['relationship'] !== '') {
        $query .= " AND p.relationship = ?";
        $params[] = $filter['relationship'];
    }
    
    if (isset($filter['line_status']) && $filter['line_status'] !== '') {
        if ($filter['line_status'] === 'connected') {
            $query .= " AND u.line_id IS NOT NULL";
        } else {
            $query .= " AND (u.line_id IS NULL OR u.line_id = '')";
        }
    }
    
    // คำสั่ง SQL สำหรับนับจำนวนรายการทั้งหมด
    $countQuery = str_replace("SELECT 
                p.parent_id, 
                u.user_id, 
                u.title, 
                u.first_name, 
                u.last_name, 
                u.phone_number, 
                u.email, 
                u.line_id, 
                p.relationship,
                (SELECT COUNT(*) FROM parent_student_relation psr WHERE psr.parent_id = p.parent_id) as student_count", "SELECT COUNT(*)", $query);
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total_rows = $stmt->fetchColumn();
    
    // จัดการการแบ่งหน้า
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // จำนวนรายการต่อหน้า
    $offset = ($page - 1) * $limit;
    
    // เพิ่มการจัดเรียง
    $query .= " ORDER BY u.first_name ASC";
    
    // เพิ่มการแบ่งหน้า
    $query .= " LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'data' => $parents,
        'total' => $total_rows,
        'page' => $page,
        'limit' => $limit
    ];
}

// ดึงข้อมูลนักเรียนที่อยู่ในความปกครองของผู้ปกครอง
function getParentStudents($parentId) {
    $db = getDB();
    
    $query = "SELECT 
                s.student_id, 
                s.student_code, 
                s.title as student_title, 
                u.first_name, 
                u.last_name,
                c.level,
                c.group_number,
                d.department_name,
                psr.relation_id
              FROM parent_student_relation psr
              JOIN students s ON psr.student_id = s.student_id
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              WHERE psr.parent_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลประวัติการแจ้งเตือนที่ส่งถึงผู้ปกครอง
function getParentNotifications($userId, $limit = 5) {
    $db = getDB();
    
    $query = "SELECT 
                ln.notification_id,
                ln.message,
                ln.sent_at,
                ln.status,
                ln.notification_type
              FROM line_notifications ln
              WHERE ln.user_id = ?
              ORDER BY ln.sent_at DESC
              LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลผู้ปกครองคนเดียว
function getParent($parentId) {
    $db = getDB();
    
    $query = "SELECT 
                p.parent_id, 
                u.user_id, 
                u.title, 
                u.first_name, 
                u.last_name, 
                u.phone_number, 
                u.email, 
                u.line_id, 
                p.relationship
              FROM parents p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.parent_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$parentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลนักเรียนทั้งหมดที่ยังไม่มีผู้ปกครอง หรือค้นหาตามเงื่อนไข
function getAvailableStudents($search = '', $filter = []) {
    $db = getDB();
    
    $query = "SELECT 
                s.student_id, 
                s.student_code, 
                s.title as student_title, 
                u.first_name, 
                u.last_name,
                c.level,
                c.group_number,
                d.department_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              WHERE s.status = 'กำลังศึกษา'";
    
    $params = [];
    
    // เพิ่มเงื่อนไขการค้นหา
    if (!empty($search)) {
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_code LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // เพิ่มเงื่อนไขฟิลเตอร์
    if (!empty($filter['level']) && $filter['level'] !== '') {
        $query .= " AND c.level = ?";
        $params[] = $filter['level'];
    }
    
    if (!empty($filter['group']) && $filter['group'] !== '') {
        $query .= " AND c.group_number = ?";
        $params[] = $filter['group'];
    }
    
    $query .= " ORDER BY c.level, c.group_number, u.first_name ASC
              LIMIT 50"; // จำกัดผลลัพธ์เพื่อประสิทธิภาพ
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลระดับชั้นทั้งหมด
function getAllLevels() {
    $db = getDB();
    $query = "SELECT DISTINCT level FROM classes WHERE is_active = 1 ORDER BY level";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ดึงข้อมูลกลุ่มเรียนทั้งหมด
function getAllGroups() {
    $db = getDB();
    $query = "SELECT DISTINCT group_number FROM classes WHERE is_active = 1 ORDER BY group_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// จัดการการส่งข้อมูลฟอร์ม - เพิ่ม/แก้ไขข้อมูลผู้ปกครอง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // เพิ่มผู้ปกครองใหม่
    if ($action === 'add_parent') {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // ข้อมูลผู้ใช้ (users)
            $title = sanitize($_POST['title']);
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $phoneNumber = sanitize($_POST['phone_number']);
            $email = !empty($_POST['email']) ? sanitize($_POST['email']) : null;
            
            // ตรวจสอบว่าเบอร์โทรศัพท์ซ้ำหรือไม่
            $stmt = $db->prepare("SELECT user_id FROM users WHERE phone_number = ?");
            $stmt->execute([$phoneNumber]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error_message'] = 'เบอร์โทรศัพท์นี้มีในระบบแล้ว';
                header('Location: parents.php');
                exit;
            }
            
            // เพิ่มข้อมูลผู้ใช้
            $stmt = $db->prepare("INSERT INTO users (role, title, first_name, last_name, phone_number, email, gdpr_consent, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute(['parent', $title, $firstName, $lastName, $phoneNumber, $email]);
            $userId = $db->lastInsertId();
            
            // ข้อมูลผู้ปกครอง (parents)
            $relationship = sanitize($_POST['relationship']);
            
            // เพิ่มข้อมูลผู้ปกครอง
            $stmt = $db->prepare("INSERT INTO parents (user_id, title, relationship, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $title, $relationship]);
            $parentId = $db->lastInsertId();
            
            // ตรวจสอบว่ามีการเลือกนักเรียนหรือไม่
            if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
                $studentIds = $_POST['student_ids'];
                foreach ($studentIds as $studentId) {
                    $stmt = $db->prepare("INSERT INTO parent_student_relation (parent_id, student_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$parentId, $studentId]);
                }
            }
            
            $db->commit();
            $_SESSION['success_message'] = 'เพิ่มข้อมูลผู้ปกครองเรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: parents.php');
        exit;
    }
    
    // แก้ไขข้อมูลผู้ปกครอง
    else if ($action === 'edit_parent' && isset($_POST['parent_id'])) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $parentId = (int)$_POST['parent_id'];
            
            // ดึงข้อมูลผู้ปกครองเดิม
            $stmt = $db->prepare("SELECT user_id FROM parents WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent) {
                $_SESSION['error_message'] = 'ไม่พบข้อมูลผู้ปกครอง';
                header('Location: parents.php');
                exit;
            }
            
            $userId = $parent['user_id'];
            
            // ข้อมูลผู้ใช้ (users)
            $title = sanitize($_POST['title']);
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $phoneNumber = sanitize($_POST['phone_number']);
            $email = !empty($_POST['email']) ? sanitize($_POST['email']) : null;
            
            // ตรวจสอบว่าเบอร์โทรศัพท์ซ้ำหรือไม่ (ยกเว้นผู้ปกครองคนนี้)
            $stmt = $db->prepare("SELECT user_id FROM users WHERE phone_number = ? AND user_id != ?");
            $stmt->execute([$phoneNumber, $userId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error_message'] = 'เบอร์โทรศัพท์นี้มีในระบบแล้ว';
                header('Location: parents.php');
                exit;
            }
            
            // อัปเดตข้อมูลผู้ใช้
            $stmt = $db->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$title, $firstName, $lastName, $phoneNumber, $email, $userId]);
            
            // ข้อมูลผู้ปกครอง (parents)
            $relationship = sanitize($_POST['relationship']);
            
            // อัปเดตข้อมูลผู้ปกครอง
            $stmt = $db->prepare("UPDATE parents SET title = ?, relationship = ?, updated_at = NOW() WHERE parent_id = ?");
            $stmt->execute([$title, $relationship, $parentId]);
            
            // ลบความสัมพันธ์กับนักเรียนเดิมทั้งหมด
            $stmt = $db->prepare("DELETE FROM parent_student_relation WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            
            // เพิ่มความสัมพันธ์กับนักเรียนใหม่
            if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
                $studentIds = $_POST['student_ids'];
                foreach ($studentIds as $studentId) {
                    $stmt = $db->prepare("INSERT INTO parent_student_relation (parent_id, student_id, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$parentId, $studentId]);
                }
            }
            
            // ข้อมูลการแจ้งเตือน
            $enableNotifications = isset($_POST['enable_notifications']) ? 1 : 0;
            
            // อัปเดตการตั้งค่าแจ้งเตือน (สมมติว่ามีฟิลด์ในตาราง users)
            $stmt = $db->prepare("UPDATE users SET notification_enabled = ? WHERE user_id = ?");
            $stmt->execute([$enableNotifications, $userId]);
            
            $db->commit();
            $_SESSION['success_message'] = 'แก้ไขข้อมูลผู้ปกครองเรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: parents.php');
        exit;
    }
    
    // ลบข้อมูลผู้ปกครอง
    else if ($action === 'delete_parent' && isset($_POST['parent_id'])) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $parentId = (int)$_POST['parent_id'];
            
            // ดึงข้อมูลผู้ปกครอง
            $stmt = $db->prepare("SELECT user_id FROM parents WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent) {
                $_SESSION['error_message'] = 'ไม่พบข้อมูลผู้ปกครอง';
                header('Location: parents.php');
                exit;
            }
            
            $userId = $parent['user_id'];
            
            // ลบความสัมพันธ์กับนักเรียน
            $stmt = $db->prepare("DELETE FROM parent_student_relation WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            
            // ลบข้อมูลผู้ปกครอง
            $stmt = $db->prepare("DELETE FROM parents WHERE parent_id = ?");
            $stmt->execute([$parentId]);
            
            // ลบข้อมูลผู้ใช้
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $db->commit();
            $_SESSION['success_message'] = 'ลบข้อมูลผู้ปกครองเรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: parents.php');
        exit;
    }
    
    // ส่งข้อความถึงผู้ปกครอง
    else if ($action === 'send_message' && isset($_POST['user_id'])) {
        try {
            $db = getDB();
            
            $userId = (int)$_POST['user_id'];
            $message = sanitize($_POST['message']);
            
            // บันทึกข้อความที่จะส่ง
            $stmt = $db->prepare("INSERT INTO line_notifications (user_id, message, sent_at, status, notification_type) VALUES (?, ?, NOW(), 'pending', 'system')");
            $stmt->execute([$userId, $message]);
            
            // ในทางปฏิบัติจริง ควรมีการส่งข้อความไปยัง LINE API ที่นี่
            // แต่เพื่อการสาธิต เราจะสมมติว่าส่งสำเร็จ
            
            // อัปเดตสถานะการส่ง
            $notificationId = $db->lastInsertId();
            $stmt = $db->prepare("UPDATE line_notifications SET status = 'sent' WHERE notification_id = ?");
            $stmt->execute([$notificationId]);
            
            $_SESSION['success_message'] = 'ส่งข้อความถึงผู้ปกครองเรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
        
        header('Location: parents.php');
        exit;
    }
}

// ค้นหาและกรองข้อมูล
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filters = [
    'relationship' => isset($_GET['relationship']) ? sanitize($_GET['relationship']) : '',
    'line_status' => isset($_GET['line_status']) ? sanitize($_GET['line_status']) : ''
];

// ดึงข้อมูลผู้ปกครอง
$parents_data = getParentsData($search, $filters);
$parents = $parents_data['data'];
$total_rows = $parents_data['total'];
$current_page = $parents_data['page'];
$limit = $parents_data['limit'];
$total_pages = ceil($total_rows / $limit);

// Path ไปยังเนื้อหาหลัก
$content_path = 'pages/parents_content.php';

// นำเข้าเทมเพลต header, sidebar, main_content และ footer
include 'templates/header.php';
include 'templates/sidebar.php';
include 'templates/main_content.php';
include 'templates/footer.php';
?>
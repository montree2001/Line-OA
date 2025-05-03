<?php
/**
 * parents_handler.php - ตัวจัดการคำขอ AJAX สำหรับหน้าผู้ปกครอง
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
} */

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ฟังก์ชันช่วยเหลือ
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ตรวจสอบว่ามีคำขอ AJAX หรือไม่
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // แสดงข้อมูลผู้ปกครอง
    if ($action === 'get_parent') {
        if (!isset($_POST['parent_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่ระบุรหัสผู้ปกครอง']);
            exit;
        }
        
        $parentId = (int)$_POST['parent_id'];
        
        try {
            $db = getDB();
            
            // ดึงข้อมูลผู้ปกครอง
            $stmt = $db->prepare("SELECT 
                                    p.parent_id, 
                                    u.user_id, 
                                    u.title, 
                                    u.first_name, 
                                    u.last_name, 
                                    u.phone_number, 
                                    u.email, 
                                    u.line_id, 
                                    p.relationship,
                                    (CASE WHEN u.line_id IS NOT NULL THEN 'connected' ELSE 'disconnected' END) as line_status
                                  FROM parents p
                                  JOIN users u ON p.user_id = u.user_id
                                  WHERE p.parent_id = ?");
            $stmt->execute([$parentId]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ปกครอง']);
                exit;
            }
            
            // ดึงข้อมูลนักเรียนที่อยู่ในความปกครอง
            $stmt = $db->prepare("SELECT 
                                    s.student_id, 
                                    s.student_code, 
                                    s.title as student_title, 
                                    u.first_name, 
                                    u.last_name,
                                    c.level,
                                    c.group_number,
                                    d.department_name
                                  FROM parent_student_relation psr
                                  JOIN students s ON psr.student_id = s.student_id
                                  JOIN users u ON s.user_id = u.user_id
                                  LEFT JOIN classes c ON s.current_class_id = c.class_id
                                  LEFT JOIN departments d ON c.department_id = d.department_id
                                  WHERE psr.parent_id = ?");
            $stmt->execute([$parentId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงข้อมูลการแจ้งเตือนที่ส่งถึงผู้ปกครอง
            $stmt = $db->prepare("SELECT 
                                    notification_id,
                                    message,
                                    sent_at,
                                    status,
                                    notification_type
                                  FROM line_notifications
                                  WHERE user_id = ?
                                  ORDER BY sent_at DESC
                                  LIMIT 5");
            $stmt->execute([$parent['user_id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'parent' => $parent,
                    'students' => $students,
                    'notifications' => $notifications
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
    
    // ค้นหานักเรียน
    else if ($action === 'search_students') {
        $search = isset($_POST['search']) ? sanitize($_POST['search']) : '';
        $level = isset($_POST['level']) ? sanitize($_POST['level']) : '';
        $group = isset($_POST['group']) ? sanitize($_POST['group']) : '';
        
        try {
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
                        (SELECT CONCAT(u2.title, ' ', u2.first_name, ' ', u2.last_name) 
                         FROM parent_student_relation psr 
                         JOIN parents p ON psr.parent_id = p.parent_id 
                         JOIN users u2 ON p.user_id = u2.user_id 
                         WHERE psr.student_id = s.student_id 
                         LIMIT 1) as current_parent
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
            if (!empty($level)) {
                $query .= " AND c.level = ?";
                $params[] = $level;
            }
            
            if (!empty($group)) {
                $query .= " AND c.group_number = ?";
                $params[] = $group;
            }
            
            $query .= " ORDER BY c.level, c.group_number, u.first_name ASC
                      LIMIT 30"; // จำกัดผลลัพธ์เพื่อประสิทธิภาพ
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'success', 'data' => $students]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
    
    // ส่งข้อความถึงผู้ปกครอง
    else if ($action === 'send_message') {
        if (!isset($_POST['user_id']) || !isset($_POST['message'])) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }
        
        $userId = (int)$_POST['user_id'];
        $message = sanitize($_POST['message']);
        
        try {
            $db = getDB();
            
            // บันทึกข้อความที่จะส่ง
            $stmt = $db->prepare("INSERT INTO line_notifications (user_id, message, sent_at, status, notification_type) VALUES (?, ?, NOW(), 'pending', 'system')");
            $stmt->execute([$userId, $message]);
            
            // ในทางปฏิบัติจริง ควรมีการส่งข้อความไปยัง LINE API ที่นี่
            // แต่เพื่อการสาธิต เราจะสมมติว่าส่งสำเร็จ
            
            // อัปเดตสถานะการส่ง
            $notificationId = $db->lastInsertId();
            $stmt = $db->prepare("UPDATE line_notifications SET status = 'sent' WHERE notification_id = ?");
            $stmt->execute([$notificationId]);
            
            echo json_encode(['status' => 'success', 'message' => 'ส่งข้อความเรียบร้อยแล้ว']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
    
    // ส่งออกข้อมูลผู้ปกครอง
    else if ($action === 'export_parents') {
        try {
            $db = getDB();
            
            $query = "SELECT 
                        p.parent_id, 
                        u.title, 
                        u.first_name, 
                        u.last_name, 
                        u.phone_number, 
                        u.email, 
                        p.relationship,
                        (CASE WHEN u.line_id IS NOT NULL THEN 'เชื่อมต่อแล้ว' ELSE 'ยังไม่เชื่อมต่อ' END) as line_status,
                        (SELECT COUNT(*) FROM parent_student_relation psr WHERE psr.parent_id = p.parent_id) as student_count
                      FROM parents p
                      JOIN users u ON p.user_id = u.user_id
                      ORDER BY u.first_name, u.last_name";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ในทางปฏิบัติจริง ควรสร้างไฟล์ CSV หรือ Excel ที่นี่
            // แต่เพื่อการสาธิต เราจะส่งข้อมูลกลับไปในรูปแบบ JSON
            
            echo json_encode(['status' => 'success', 'data' => $parents]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }
    
    // ถ้าไม่มีการระบุคำสั่ง
    else {
        echo json_encode(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีคำสั่ง']);
}
?>
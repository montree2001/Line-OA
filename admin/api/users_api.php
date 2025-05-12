<?php
/**
 * users_api.php - API สำหรับจัดการข้อมูลผู้ใช้งาน
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึง'
    ]);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบประเภทการทำงาน
$action = $_GET['action'] ?? '';

// ดึงข้อมูลผู้ใช้
if ($action === 'get_user') {
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($userId <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'รหัสผู้ใช้ไม่ถูกต้อง'
        ]);
        exit;
    }
    
    try {
        $conn = getDB();
        
        // ดึงข้อมูลพื้นฐานของผู้ใช้
        $query = "
            SELECT 
                u.*,
                CASE 
                    WHEN u.role = 'student' THEN (SELECT s.student_code FROM students s WHERE s.user_id = u.user_id)
                    ELSE NULL
                END AS student_code
            FROM 
                users u
            WHERE 
                u.user_id = ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้'
            ]);
            exit;
        }
        
        // ดึงข้อมูลเพิ่มเติมตามบทบาท
        switch ($user['role']) {
            case 'student':
                $query = "
                    SELECT 
                        s.*,
                        c.level,
                        c.group_number,
                        d.department_name
                    FROM 
                        students s
                    LEFT JOIN 
                        classes c ON s.current_class_id = c.class_id
                    LEFT JOIN 
                        departments d ON c.department_id = d.department_id
                    WHERE 
                        s.user_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
                $additionalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($additionalInfo) {
                    $user = array_merge($user, $additionalInfo);
                }
                break;
                
            case 'teacher':
                $query = "
                    SELECT 
                        t.*,
                        d.department_name
                    FROM 
                        teachers t
                    LEFT JOIN 
                        departments d ON t.department_id = d.department_id
                    WHERE 
                        t.user_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
                $additionalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($additionalInfo) {
                    $user = array_merge($user, $additionalInfo);
                }
                break;
                
            case 'parent':
                $query = "
                    SELECT 
                        p.*
                    FROM 
                        parents p
                    WHERE 
                        p.user_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
                $additionalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($additionalInfo) {
                    $user = array_merge($user, $additionalInfo);
                }
                
                // ดึงข้อมูลความสัมพันธ์กับนักเรียน
                $query = "
                    SELECT 
                        s.student_id,
                        s.student_code,
                        u.first_name,
                        u.last_name
                    FROM 
                        parent_student_relation psr
                    JOIN 
                        students s ON psr.student_id = s.student_id
                    JOIN
                        users u ON s.user_id = u.user_id
                    WHERE 
                        psr.parent_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$additionalInfo['parent_id']]);
                $relatedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $user['related_students'] = $relatedStudents;
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    // ไม่พบ action ที่ต้องการ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบ action ที่ต้องการ'
    ]);
    exit;
}
<?php
/**
 * line_disconnect_api.php - API สำหรับการยกเลิกการเชื่อมต่อ LINE
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

// ตรวจสอบการร้องขอ API
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'search_students_for_disconnect':
        searchStudentsForDisconnect();
        break;

    case 'disconnect_line_accounts':
        disconnectLineAccounts();
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'การร้องขอไม่ถูกต้อง'
        ]);
        exit;
}

/**
 * ค้นหานักเรียนตามเงื่อนไขสำหรับการยกเลิกการเชื่อมต่อ LINE
 */
function searchStudentsForDisconnect() {
    $conn = getDB();
    
    try {
        // สร้าง query พื้นฐาน
        $query = "
            SELECT 
                s.student_id,
                s.student_code,
                s.title,
                s.status,
                s.current_class_id,
                u.user_id,
                u.first_name,
                u.last_name,
                u.line_id,
                u.profile_picture,
                c.level,
                c.group_number,
                d.department_name,
                CONCAT(c.level, '/', c.group_number, ' ', d.department_name) AS class,
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
            WHERE 1=1
        ";
        
        $params = [];
        
        // เพิ่มเงื่อนไขการค้นหา
        // รหัสนักศึกษา
        if (!empty($_POST['student_code'])) {
            $query .= " AND s.student_code LIKE ?";
            $params[] = '%' . $_POST['student_code'] . '%';
        }
        
        // ชื่อนักเรียน
        if (!empty($_POST['student_name'])) {
            $studentName = $_POST['student_name'];
            $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params[] = '%' . $studentName . '%';
            $params[] = '%' . $studentName . '%';
        }
        
        // ระดับชั้น
        if (!empty($_POST['level'])) {
            $query .= " AND c.level = ?";
            $params[] = $_POST['level'];
        }
        
        // กลุ่ม
        if (!empty($_POST['group_number'])) {
            $query .= " AND c.group_number = ?";
            $params[] = $_POST['group_number'];
        }
        
        // แผนกวิชา
        if (!empty($_POST['department_id'])) {
            $query .= " AND c.department_id = ?";
            $params[] = $_POST['department_id'];
        }
        
        // ครูที่ปรึกษา
        if (!empty($_POST['advisor_id'])) {
            $query .= " AND EXISTS (
                SELECT 1 FROM class_advisors ca 
                WHERE ca.class_id = s.current_class_id 
                AND ca.teacher_id = ?
            )";
            $params[] = $_POST['advisor_id'];
        }
        
        // สถานะ LINE
        if (!empty($_POST['line_status']) && $_POST['line_status'] !== 'all') {
            if ($_POST['line_status'] === 'connected') {
                $query .= " AND u.line_id IS NOT NULL AND u.line_id NOT LIKE 'TEMP_%'";
            } elseif ($_POST['line_status'] === 'not_connected') {
                $query .= " AND (u.line_id IS NULL OR u.line_id LIKE 'TEMP_%')";
            }
        }
        
        // สถานะการศึกษา
        if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
            $query .= " AND s.status = ?";
            $params[] = $_POST['status'];
        }
        
        // จัดเรียงข้อมูล
        $query .= " ORDER BY s.student_code ASC LIMIT 500";
        
        // บันทึกคิวรี่เพื่อการดีบัก
        error_log("SQL Query: " . $query);
        error_log("Params: " . json_encode($params));
        
        // ดึงข้อมูล
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // บันทึกจำนวนนักเรียนที่พบ
        error_log("Found students: " . count($students));
        
        // ส่งข้อมูลกลับ
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'students' => $students,
            'count' => count($students)
        ]);
        
    } catch (Exception $e) {
        error_log("Error in searchStudentsForDisconnect: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการค้นหาข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * ยกเลิกการเชื่อมต่อ LINE ของนักเรียนที่เลือก
 */
function disconnectLineAccounts() {
    $conn = getDB();
    
    try {
        // ตรวจสอบรหัสผ่านผู้ดูแลระบบ
        $adminPassword = $_POST['admin_password'] ?? '';
        $userId = $_SESSION['user_id'];
        
        $query = "SELECT password FROM admin_users WHERE admin_id = (SELECT admin_id FROM users WHERE user_id = ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$adminData || md5($adminPassword) !== $adminData['password']) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'รหัสผ่านผู้ดูแลระบบไม่ถูกต้อง'
            ]);
            exit;
        }
        
        // รับข้อมูลนักเรียนที่เลือก
        $selectedStudents = json_decode($_POST['students'], true);
        
        if (empty($selectedStudents)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'ไม่มีนักเรียนที่เลือก'
            ]);
            exit;
        }
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        $successCount = 0;
        $failedCount = 0;
        $failedStudents = [];
        
        foreach ($selectedStudents as $student) {
            $studentId = $student['student_id'];
            $userId = $student['user_id'];
            
            try {
                // ดึงข้อมูลนักเรียนและผู้ใช้
                $query = "
                    SELECT 
                        s.student_id, s.student_code, s.title, 
                        u.first_name, u.last_name, u.line_id
                    FROM 
                        students s
                    JOIN 
                        users u ON s.user_id = u.user_id
                    WHERE 
                        s.student_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$studentId]);
                $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$studentData) {
                    throw new Exception("ไม่พบข้อมูลนักเรียน");
                }
                
                // ข้ามถ้าไม่ได้เชื่อมต่อ LINE
                if (empty($studentData['line_id']) || strpos($studentData['line_id'], 'TEMP_') === 0) {
                    $failedStudents[] = [
                        'student_id' => $studentId,
                        'title' => $studentData['title'],
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'student_code' => $studentData['student_code'],
                        'error' => 'ไม่ได้เชื่อมต่อ LINE'
                    ];
                    $failedCount++;
                    continue;
                }
                
                // สร้าง line_id ชั่วคราวที่ไม่ซ้ำกัน
                $tempLineId = 'TEMP_' . $studentData['student_code'] . '_' . time() . '_' . bin2hex(random_bytes(3));
                
                // อัพเดตข้อมูลผู้ใช้ - เปลี่ยน line_id เป็นชั่วคราว และล้างรูปโปรไฟล์
                $query = "
                    UPDATE users
                    SET line_id = ?, profile_picture = NULL
                    WHERE user_id = ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$tempLineId, $userId]);
                
                // บันทึกประวัติการยกเลิกการเชื่อมต่อ (ถ้ามีตาราง)
                
                $actionDetails = json_encode([
                    'student_id' => $studentId,
                    'student_code' => $studentData['student_code'],
                    'student_name' => $studentData['title'] . $studentData['first_name'] . ' ' . $studentData['last_name'],
                    'original_line_id' => $studentData['line_id'],
                    'temp_line_id' => $tempLineId
                ]);

                $successCount++;
                
            } catch (Exception $e) {
                // บันทึกข้อมูลนักเรียนที่มีปัญหา
                $failedStudents[] = [
                    'student_id' => $studentId,
                    'title' => $studentData['title'] ?? '',
                    'first_name' => $studentData['first_name'] ?? '',
                    'last_name' => $studentData['last_name'] ?? '',
                    'student_code' => $studentData['student_code'] ?? '',
                    'error' => $e->getMessage()
                ];
                $failedCount++;
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // ส่งข้อมูลกลับ
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_students' => $failedStudents
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการยกเลิกการเชื่อมต่อ LINE: ' . $e->getMessage()
        ]);
    }
}
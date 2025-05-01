<?php
/**
 * send_parent_notification.php - API ส่งข้อความแจ้งเตือนไปยังผู้ปกครอง
 * 
 * API นี้ใช้สำหรับส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียน
 * รองรับการส่งแบบเดี่ยวและแบบกลุ่ม
 */

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';
require_once '../../lib/functions.php';
require_once '../../lib/line_api.php'; // กำหนดให้มีไฟล์ line_api.php สำหรับส่งข้อความผ่าน LINE

// ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ต้องใช้ POST method เท่านั้น'
    ]);
    exit;
}

// รับข้อมูลจาก POST
$post_data = json_decode(file_get_contents('php://input'), true);

if (!$post_data) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูลที่ส่งมา'
    ]);
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (empty($post_data['message'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุข้อความ'
    ]);
    exit;
}

// รับข้อมูล
$message = $post_data['message'];
$notification_type = $post_data['notification_type'] ?? 'single'; // 'single' หรือ 'group'
$student_id = $post_data['student_id'] ?? null;
$parent_id = $post_data['parent_id'] ?? null;
$class_id = $post_data['class_id'] ?? null;
$only_risk = isset($post_data['only_risk']) ? (bool)$post_data['only_risk'] : false;

try {
    $db = getDB();
    $recipients = []; // ข้อมูลผู้รับ
    
    // กรณีส่งแบบเดี่ยว
    if ($notification_type === 'single') {
        if (empty($student_id)) {
            throw new Exception('กรุณาระบุรหัสนักเรียน');
        }
        
        // ดึงข้อมูลผู้ปกครองของนักเรียน
        $query = "SELECT p.parent_id, u.line_id, p.relationship, u.first_name, u.last_name, 
                 s.student_id, s.student_code, su.first_name as student_first_name, su.last_name as student_last_name
                 FROM parent_student_relation psr
                 JOIN parents p ON psr.parent_id = p.parent_id
                 JOIN users u ON p.user_id = u.user_id
                 JOIN students s ON psr.student_id = s.student_id
                 JOIN users su ON s.user_id = su.user_id
                 WHERE psr.student_id = :student_id";
        
        if ($parent_id) {
            $query .= " AND p.parent_id = :parent_id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        
        if ($parent_id) {
            $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $parent_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($parent_data)) {
            throw new Exception('ไม่พบข้อมูลผู้ปกครองของนักเรียน');
        }
        
        foreach ($parent_data as $parent) {
            if (empty($parent['line_id'])) {
                continue; // ข้ามถ้าไม่มี line_id
            }
            
            $recipients[] = [
                'parent_id' => $parent['parent_id'],
                'line_id' => $parent['line_id'],
                'name' => $parent['first_name'] . ' ' . $parent['last_name'],
                'relationship' => $parent['relationship'],
                'student_id' => $parent['student_id'],
                'student_code' => $parent['student_code'],
                'student_name' => $parent['student_first_name'] . ' ' . $parent['student_last_name']
            ];
        }
    }
    // กรณีส่งแบบกลุ่ม
    else if ($notification_type === 'group') {
        if (empty($class_id)) {
            throw new Exception('กรุณาระบุรหัสห้องเรียน');
        }
        
        // ดึงข้อมูลนักเรียนและผู้ปกครองในห้องเรียน
        $query = "SELECT p.parent_id, u.line_id, p.relationship, u.first_name, u.last_name, 
                 s.student_id, s.student_code, su.first_name as student_first_name, su.last_name as student_last_name
                 FROM students s
                 JOIN users su ON s.user_id = su.user_id
                 JOIN parent_student_relation psr ON s.student_id = psr.student_id
                 JOIN parents p ON psr.parent_id = p.parent_id
                 JOIN users u ON p.user_id = u.user_id
                 WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'";
        
        // กรณีเลือกเฉพาะนักเรียนที่มีความเสี่ยง
        if ($only_risk) {
            $query .= " AND s.student_id IN (
                        SELECT s.student_id
                        FROM students s
                        WHERE s.current_class_id = :class_id
                        AND (SELECT COUNT(*) FROM attendance a 
                             WHERE a.student_id = s.student_id 
                             AND a.attendance_status = 'absent') >= 5
                      )";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        $parents_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($parents_data)) {
            throw new Exception($only_risk ? 'ไม่พบข้อมูลผู้ปกครองของนักเรียนที่มีความเสี่ยง' : 'ไม่พบข้อมูลผู้ปกครองในห้องเรียนนี้');
        }
        
        // จัดกลุ่มผู้ปกครองตาม line_id เพื่อป้องกันการส่งซ้ำ
        $unique_recipients = [];
        foreach ($parents_data as $parent) {
            if (empty($parent['line_id'])) {
                continue; // ข้ามถ้าไม่มี line_id
            }
            
            $key = $parent['line_id'] . '-' . $parent['student_id'];
            if (!isset($unique_recipients[$key])) {
                $unique_recipients[$key] = [
                    'parent_id' => $parent['parent_id'],
                    'line_id' => $parent['line_id'],
                    'name' => $parent['first_name'] . ' ' . $parent['last_name'],
                    'relationship' => $parent['relationship'],
                    'student_id' => $parent['student_id'],
                    'student_code' => $parent['student_code'],
                    'student_name' => $parent['student_first_name'] . ' ' . $parent['student_last_name']
                ];
            }
        }
        
        $recipients = array_values($unique_recipients);
    } else {
        throw new Exception('ประเภทการแจ้งเตือนไม่ถูกต้อง');
    }
    
    if (empty($recipients)) {
        throw new Exception('ไม่พบผู้รับการแจ้งเตือนที่มี LINE ID');
    }
    
    // บันทึกการแจ้งเตือนลงฐานข้อมูล
    $successful_count = 0;
    $failed_count = 0;
    $failed_recipients = [];
    
    foreach ($recipients as $recipient) {
        // แทนที่ตัวแปรในข้อความ
        $personalized_message = str_replace(
            ['[ชื่อผู้ปกครอง]', '[ชื่อนักเรียน]', '[รหัสนักเรียน]'],
            [$recipient['name'], $recipient['student_name'], $recipient['student_code']],
            $message
        );
        
        // บันทึกการส่งแจ้งเตือน
        $insert_query = "INSERT INTO line_notifications (user_id, message, status, notification_type)
                        SELECT u.user_id, :message, 'pending', 'attendance'
                        FROM users u
                        JOIN parents p ON u.user_id = p.user_id
                        WHERE p.parent_id = :parent_id";
        
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':message', $personalized_message);
        $stmt->bindParam(':parent_id', $recipient['parent_id'], PDO::PARAM_INT);
        $stmt->execute();
        $notification_id = $db->lastInsertId();
        
        // ส่งข้อความผ่าน LINE API
        $success = sendLineMessage($recipient['line_id'], $personalized_message);
        
        // อัพเดตสถานะการส่ง
        $update_query = "UPDATE line_notifications 
                        SET status = :status, 
                            error_message = :error_message,
                            sent_at = NOW()
                        WHERE notification_id = :notification_id";
        
        $stmt = $db->prepare($update_query);
        
        if ($success) {
            $status = 'sent';
            $error_message = null;
            $successful_count++;
        } else {
            $status = 'failed';
            $error_message = 'ไม่สามารถส่งข้อความไปยัง LINE ได้';
            $failed_count++;
            $failed_recipients[] = $recipient['name'];
        }
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':error_message', $error_message);
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // เพิ่มบันทึกประวัติการส่งข้อความ
    $log_query = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                 VALUES (:user_id, 'send_notification', :details)";
    
    $details = json_encode([
        'notification_type' => $notification_type,
        'message' => $message,
        'recipients_count' => count($recipients),
        'successful_count' => $successful_count,
        'failed_count' => $failed_count,
        'class_id' => $class_id,
        'student_id' => $student_id,
        'only_risk' => $only_risk
    ]);
    
    $stmt = $db->prepare($log_query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':details', $details);
    $stmt->execute();
    
    // ส่งผลลัพธ์กลับ
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'ส่งการแจ้งเตือนเรียบร้อยแล้ว',
        'total_recipients' => count($recipients),
        'successful_count' => $successful_count,
        'failed_count' => $failed_count,
        'failed_recipients' => $failed_recipients
    ]);
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการส่งการแจ้งเตือน: ' . $e->getMessage()
    ]);
    
    // บันทึกข้อผิดพลาด
    error_log('Error in send_parent_notification.php: ' . $e->getMessage());
}

/**
 * ส่งข้อความผ่าน LINE API
 * 
 * @param string $line_id LINE ID ของผู้รับ
 * @param string $message ข้อความที่ต้องการส่ง
 * @return bool สถานะการส่ง
 */
function sendLineMessage($line_id, $message) {
    // ในที่นี้เป็นตัวอย่างการเรียกใช้งาน LINE API
    // ในระบบจริงจะมีการเชื่อมต่อกับ LINE API จริงๆ
    
    // สมมติว่าการส่งสำเร็จ
    return true;
}
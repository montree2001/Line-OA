<?php
/**
 * send_line_notification.php - API สำหรับส่งข้อความแจ้งเตือนผ่าน LINE OA
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

// นำเข้าไฟล์ที่จำเป็น
require_once '../../../db_connect.php';

// รับพารามิเตอร์
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// รับค่าประเภทการส่งข้อความ
$notification_type = isset($data['type']) ? $data['type'] : 'individual';
$student_id = isset($data['student_id']) ? $data['student_id'] : null;
$message = isset($data['message']) ? $data['message'] : '';
$filters = isset($data['filters']) ? $data['filters'] : [];

try {
    $db = getDB();
    
    // ดึงการตั้งค่า LINE OA จากฐานข้อมูล
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings 
                         WHERE setting_key IN ('line_channel_id', 'line_channel_secret', 'line_access_token')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $line_channel_id = $settings['line_channel_id'] ?? '';
    $line_channel_secret = $settings['line_channel_secret'] ?? '';
    $line_access_token = $settings['line_access_token'] ?? '';
    
    // ตรวจสอบว่ามีการตั้งค่า LINE OA หรือไม่
    if (empty($line_access_token)) {
        throw new Exception('ไม่พบการตั้งค่า LINE OA');
    }
    
    // การส่งข้อความแบบรายบุคคล
    if ($notification_type === 'individual' && $student_id) {
        // ดึงข้อมูลผู้ปกครอง
        $stmt = $db->prepare("SELECT p.parent_id, u.line_id, CONCAT(u.first_name, ' ', u.last_name) as parent_name
                            FROM students s
                            JOIN parent_student_relation psr ON s.student_id = psr.student_id
                            JOIN parents p ON psr.parent_id = p.parent_id
                            JOIN users u ON p.user_id = u.user_id
                            WHERE s.student_id = :student_id AND u.line_id IS NOT NULL");
        $stmt->execute([':student_id' => $student_id]);
        $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ถ้าไม่พบผู้ปกครอง
        if (empty($parents)) {
            throw new Exception('ไม่พบข้อมูลผู้ปกครองหรือผู้ปกครองไม่ได้ลงทะเบียน LINE');
        }
        
        // ส่งข้อความไปยังผู้ปกครองทุกคน
        $sent_count = 0;
        foreach ($parents as $parent) {
            if (!empty($parent['line_id'])) {
                // เรียกใช้ฟังก์ชันส่งข้อความ LINE
                $result = sendLineMessage($parent['line_id'], $message, $line_access_token);
                
                if ($result) {
                    $sent_count++;
                    
                    // บันทึกประวัติการส่งข้อความ
                    $stmt = $db->prepare("INSERT INTO line_notifications 
                                        (user_id, message, notification_type, status) 
                                        VALUES 
                                        ((SELECT user_id FROM parents WHERE parent_id = :parent_id), :message, 'risk_alert', 'sent')");
                    $stmt->execute([
                        ':parent_id' => $parent['parent_id'],
                        ':message' => $message
                    ]);
                    
                    // บันทึกการแจ้งเตือน
                    $stmt = $db->prepare("INSERT INTO notifications 
                                        (user_id, type, title, notification_message, related_student_id)
                                        VALUES 
                                        ((SELECT user_id FROM parents WHERE parent_id = :parent_id), 
                                        'risk_alert', 'แจ้งเตือนนักเรียนเสี่ยงตกกิจกรรม', :message, :student_id)");
                    $stmt->execute([
                        ':parent_id' => $parent['parent_id'],
                        ':message' => $message,
                        ':student_id' => $student_id
                    ]);
                }
            }
        }
        
        // ส่งผลลัพธ์กลับ
        echo json_encode([
            'status' => 'success',
            'message' => "ส่งข้อความแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว ($sent_count คน)",
            'sent_count' => $sent_count
        ]);
    }
    // การส่งข้อความแบบกลุ่ม
    else if ($notification_type === 'bulk') {
        // สร้างเงื่อนไข SQL
        $where_conditions = ["sar.academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1)"];
        $params = [];
        
        // เพิ่มเงื่อนไขการกรองข้อมูล
        if (!empty($filters['department_id'])) {
            $where_conditions[] = "d.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['class_level'])) {
            $where_conditions[] = "c.level = :class_level";
            $params[':class_level'] = $filters['class_level'];
        }
        
        if (!empty($filters['class_room'])) {
            $where_conditions[] = "c.group_number = :class_room";
            $params[':class_room'] = $filters['class_room'];
        }
        
        if (!empty($filters['advisor'])) {
            $where_conditions[] = "t.teacher_id = :advisor";
            $params[':advisor'] = $filters['advisor'];
        }
        
        if (!empty($filters['min_attendance']) && !empty($filters['max_attendance'])) {
            $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 BETWEEN :min_attendance AND :max_attendance";
            $params[':min_attendance'] = $filters['min_attendance'];
            $params[':max_attendance'] = $filters['max_attendance'];
        } elseif (!empty($filters['min_attendance'])) {
            $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 >= :min_attendance";
            $params[':min_attendance'] = $filters['min_attendance'];
        } elseif (!empty($filters['max_attendance'])) {
            $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 <= :max_attendance";
            $params[':max_attendance'] = $filters['max_attendance'];
        }
        
        // เพิ่มเงื่อนไขนักเรียนที่เสี่ยงตกกิจกรรม
        $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80";
        $where_conditions[] = "s.status = 'กำลังศึกษา'";
        
        // รวมเงื่อนไข SQL
        $where_clause = implode(" AND ", $where_conditions);
        
        // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
        $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name,
               c.level, c.group_number, d.department_name,
               sar.total_attendance_days, sar.total_absence_days
               FROM students s
               JOIN users u ON s.user_id = u.user_id
               JOIN student_academic_records sar ON s.student_id = sar.student_id
               JOIN classes c ON s.current_class_id = c.class_id
               JOIN departments d ON c.department_id = d.department_id
               LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
               LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
               WHERE $where_clause
               ORDER BY (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรม
        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($students as $student) {
            // ดึงข้อมูลผู้ปกครอง
            $stmt = $db->prepare("SELECT p.parent_id, u.line_id, u.user_id
                                FROM parent_student_relation psr
                                JOIN parents p ON psr.parent_id = p.parent_id
                                JOIN users u ON p.user_id = u.user_id
                                WHERE psr.student_id = :student_id AND u.line_id IS NOT NULL");
            $stmt->execute([':student_id' => $student['student_id']]);
            $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($parents)) {
                foreach ($parents as $parent) {
                    if (!empty($parent['line_id'])) {
                        // แทนที่ตัวแปรในข้อความ
                        $student_name = $student['first_name'] . ' ' . $student['last_name'];
                        $class = $student['level'] . '/' . $student['group_number'];
                        $attendance_days = $student['total_attendance_days'];
                        $absence_days = $student['total_absence_days'];
                        $total_days = $attendance_days + $absence_days;
                        $attendance_rate = $total_days > 0 ? round(($attendance_days / $total_days) * 100, 1) : 0;
                        
                        $personalized_message = str_replace(
                            ['{{ชื่อนักเรียน}}', '{{ชั้นเรียน}}', '{{จำนวนวันเข้าแถว}}', '{{จำนวนวันขาด}}', '{{จำนวนวันทั้งหมด}}', '{{ร้อยละการเข้าแถว}}'],
                            [$student_name, $class, $attendance_days, $absence_days, $total_days, $attendance_rate],
                            $message
                        );
                        
                        // ส่งข้อความ LINE
                        $result = sendLineMessage($parent['line_id'], $personalized_message, $line_access_token);
                        
                        if ($result) {
                            $sent_count++;
                            
                            // บันทึกประวัติการส่งข้อความ
                            $stmt = $db->prepare("INSERT INTO line_notifications 
                                                (user_id, message, notification_type, status) 
                                                VALUES 
                                                (:user_id, :message, 'risk_alert', 'sent')");
                            $stmt->execute([
                                ':user_id' => $parent['user_id'],
                                ':message' => $personalized_message
                            ]);
                            
                            // บันทึกการแจ้งเตือน
                            $stmt = $db->prepare("INSERT INTO notifications 
                                                (user_id, type, title, notification_message, related_student_id)
                                                VALUES 
                                                (:user_id, 'risk_alert', 'แจ้งเตือนนักเรียนเสี่ยงตกกิจกรรม', :message, :student_id)");
                            $stmt->execute([
                                ':user_id' => $parent['user_id'],
                                ':message' => $personalized_message,
                                ':student_id' => $student['student_id']
                            ]);
                        } else {
                            $failed_count++;
                        }
                    }
                }
            } else {
                $failed_count++;
            }
        }
        
        // ส่งผลลัพธ์กลับ
        echo json_encode([
            'status' => 'success',
            'message' => "ส่งข้อความแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว ($sent_count คน, ล้มเหลว $failed_count คน)",
            'sent_count' => $sent_count,
            'failed_count' => $failed_count
        ]);
    } else {
        throw new Exception('ไม่รองรับประเภทการส่งข้อความนี้');
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้ส่ง error กลับไป
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}

/**
 * ฟังก์ชันส่งข้อความผ่าน LINE Messaging API
 * 
 * @param string $line_id LINE User ID ของผู้รับ
 * @param string $message ข้อความที่ต้องการส่ง
 * @param string $access_token Channel Access Token ของ LINE OA
 * @return bool สถานะการส่งข้อความ
 */
function sendLineMessage($line_id, $message, $access_token) {
    $url = 'https://api.line.me/v2/bot/message/push';
    
    $data = [
        'to' => $line_id,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
    ];
    
    $post_data = json_encode($data);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 200;
}
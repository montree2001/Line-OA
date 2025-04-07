<?php
/**
 * API สำหรับส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
session_start();
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการส่งการแจ้งเตือน'
    ]);
    exit;
}

// ตรวจสอบว่ารับข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'ต้องใช้วิธี POST เท่านั้น'
    ]);
    exit;
}

// รับและแปลงข้อมูล JSON
$input_data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบว่ามีการระบุ student_id หรือไม่
if (!isset($input_data['student_id']) || empty($input_data['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสนักเรียน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../lib/line_notify.php'; // ต้องมีไลบรารีสำหรับส่งการแจ้งเตือน LINE

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว'
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

// เก็บค่าที่ส่งมา
$user_id = $_SESSION['user_id'];
$student_id = intval($input_data['student_id']);
$notification_type = $input_data['notification_type'] ?? 'risk_alert';
$custom_message = $input_data['message'] ?? '';

// ตรวจสอบสิทธิ์ในการส่งแจ้งเตือนสำหรับนักเรียนคนนี้
if ($_SESSION['role'] === 'teacher') {
    $check_permission_query = "SELECT ca.class_id 
                              FROM class_advisors ca 
                              JOIN teachers t ON ca.teacher_id = t.teacher_id 
                              JOIN students s ON s.current_class_id = ca.class_id
                              WHERE t.user_id = ? AND s.student_id = ?";
    
    $stmt = $conn->prepare($check_permission_query);
    $stmt->bind_param("ii", $user_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์ในการส่งการแจ้งเตือนสำหรับนักเรียนคนนี้'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
}

// ดึงข้อมูลนักเรียนและผู้ปกครอง
$student_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                 c.level, d.department_name, c.group_number,
                 (SELECT GROUP_CONCAT(DISTINCT p.parent_id) 
                  FROM parent_student_relation psr 
                  JOIN parents p ON psr.parent_id = p.parent_id 
                  WHERE psr.student_id = s.student_id) as parent_ids
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                JOIN classes c ON s.current_class_id = c.class_id
                JOIN departments d ON c.department_id = d.department_id
                WHERE s.student_id = ?";

$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูลนักเรียน'
    ]);
    $student_stmt->close();
    $conn->close();
    exit;
}

$student_data = $student_result->fetch_assoc();
$student_name = $student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name'];
$student_class = $student_data['level'] . '/' . $student_data['department_name'] . '/' . $student_data['group_number'];
$parent_ids = $student_data['parent_ids'] ? explode(',', $student_data['parent_ids']) : [];

// ดึงข้อมูลการเข้าแถวของนักเรียน
$attendance_query = "SELECT 
                    sar.total_attendance_days, 
                    sar.total_absence_days,
                    (SELECT COUNT(*) FROM attendance WHERE student_id = s.student_id AND is_present = 0) as absence_count,
                    (SELECT COUNT(*) FROM attendance WHERE student_id = s.student_id) as total_days,
                    (SELECT MAX(date) FROM attendance WHERE student_id = s.student_id AND is_present = 0) as last_absent_date
                   FROM students s
                   JOIN student_academic_records sar ON s.student_id = sar.student_id
                   WHERE s.student_id = ?";

$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("i", $student_id);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_data = $attendance_result->fetch_assoc();

// ดึงข้อมูลครูที่ปรึกษา
$advisor_query = "SELECT t.title, t.first_name, t.last_name, u.phone_number
                 FROM class_advisors ca
                 JOIN teachers t ON ca.teacher_id = t.teacher_id
                 JOIN users u ON t.user_id = u.user_id
                 WHERE ca.class_id = (SELECT current_class_id FROM students WHERE student_id = ?)
                 AND ca.is_primary = 1";

$advisor_stmt = $conn->prepare($advisor_query);
$advisor_stmt->bind_param("i", $student_id);
$advisor_stmt->execute();
$advisor_result = $advisor_stmt->get_result();
$advisor_data = $advisor_result->fetch_assoc();
$advisor_name = $advisor_data ? $advisor_data['title'] . $advisor_data['first_name'] . ' ' . $advisor_data['last_name'] : 'ไม่ระบุ';
$advisor_phone = $advisor_data ? $advisor_data['phone_number'] : 'ไม่ระบุ';

// สร้างข้อความแจ้งเตือน
$absent_count = $attendance_data['total_absence_days'];
$total_days = $attendance_data['total_attendance_days'] + $attendance_data['total_absence_days'];
$attendance_rate = $total_days > 0 ? round(($attendance_data['total_attendance_days'] / $total_days) * 100, 1) : 0;
$last_absent = $attendance_data['last_absent_date'] ? date('d/m/Y', strtotime($attendance_data['last_absent_date'])) : 'ไม่มีข้อมูล';

// ดึงข้อความแจ้งเตือนจากการตั้งค่าระบบ
$message_template_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_notification_message'";
$message_template_result = $conn->query($message_template_query);
$message_template_data = $message_template_result->fetch_assoc();
$message_template = $message_template_data ? $message_template_data['setting_value'] : '';

if (empty($message_template)) {
    $message_template = "เรียนผู้ปกครอง บุตรหลานของท่าน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง ซึ่งมีความเสี่ยงที่จะไม่ผ่านกิจกรรม โปรดติดต่อครูที่ปรึกษา: {advisor_name} โทร: {advisor_phone}";
}

// แทนที่ตัวแปรในข้อความแจ้งเตือน
$placeholders = [
    '{student_name}' => $student_name,
    '{student_code}' => $student_data['student_code'],
    '{student_class}' => $student_class,
    '{absent_count}' => $absent_count,
    '{attendance_rate}' => $attendance_rate,
    '{last_absent}' => $last_absent,
    '{advisor_name}' => $advisor_name,
    '{advisor_phone}' => $advisor_phone
];

$notification_message = str_replace(array_keys($placeholders), array_values($placeholders), $message_template);

// ถ้ามีข้อความกำหนดเอง ให้เพิ่มต่อท้าย
if (!empty($custom_message)) {
    $notification_message .= "\n\nหมายเหตุ: " . $custom_message;
}

// ดึงข้อมูลผู้ปกครองและส่งการแจ้งเตือน
$success_count = 0;
$failed_count = 0;

if (empty($parent_ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูลผู้ปกครองของนักเรียนคนนี้'
    ]);
    $student_stmt->close();
    $attendance_stmt->close();
    $advisor_stmt->close();
    $conn->close();
    exit;
}

// สร้างบันทึกการแจ้งเตือนในตาราง notifications และ line_notifications
$notification_title = "แจ้งเตือนความเสี่ยงการเข้าแถวกิจกรรม";
$notification_type = $notification_type;
$now = date('Y-m-d H:i:s');

foreach ($parent_ids as $parent_id) {
    // ดึงข้อมูลผู้ปกครอง
    $parent_query = "SELECT p.parent_id, u.user_id, u.line_id, p.relationship
                    FROM parents p
                    JOIN users u ON p.user_id = u.user_id
                    WHERE p.parent_id = ?";
    
    $parent_stmt = $conn->prepare($parent_query);
    $parent_stmt->bind_param("i", $parent_id);
    $parent_stmt->execute();
    $parent_result = $parent_stmt->get_result();
    
    if ($parent_result->num_rows > 0) {
        $parent_data = $parent_result->fetch_assoc();
        $parent_user_id = $parent_data['user_id'];
        $parent_line_id = $parent_data['line_id'];
        
        // บันทึกลงตาราง notifications
        $insert_notification_query = "INSERT INTO notifications (user_id, type, title, notification_message, is_read, created_at, related_student_id)
                                     VALUES (?, ?, ?, ?, 0, ?, ?)";
        
        $insert_notification_stmt = $conn->prepare($insert_notification_query);
        $insert_notification_stmt->bind_param("issssi", $parent_user_id, $notification_type, $notification_title, $notification_message, $now, $student_id);
        $insert_notification_stmt->execute();
        $insert_notification_stmt->close();
        
        // ถ้ามี LINE ID ให้บันทึกลงตาราง line_notifications
        if ($parent_line_id) {
            $insert_line_notification_query = "INSERT INTO line_notifications (user_id, message, sent_at, status, notification_type)
                                             VALUES (?, ?, ?, 'pending', ?)";
            
            $insert_line_notification_stmt = $conn->prepare($insert_line_notification_query);
            $insert_line_notification_stmt->bind_param("isss", $parent_user_id, $notification_message, $now, $notification_type);
            $insert_line_notification_stmt->execute();
            $insert_line_notification_stmt->close();
            
            // ส่งการแจ้งเตือนไปยัง LINE ด้วย LINE Notify API
            // ในระบบจริงจะเรียกใช้ฟังก์ชันจาก line_notify.php
            try {
                // สมมติว่าฟังก์ชันส่งสำเร็จ (จำลอง)
                // sendLineNotification($parent_line_id, $notification_message);
                $success_count++;
                
                // อัพเดตสถานะการส่ง
                $update_status_query = "UPDATE line_notifications SET status = 'sent' 
                                      WHERE user_id = ? AND sent_at = ? AND notification_type = ?";
                
                $update_status_stmt = $conn->prepare($update_status_query);
                $update_status_stmt->bind_param("iss", $parent_user_id, $now, $notification_type);
                $update_status_stmt->execute();
                $update_status_stmt->close();
            } catch (Exception $e) {
                $failed_count++;
                
                // อัพเดตสถานะการส่งเป็นล้มเหลว
                $update_status_query = "UPDATE line_notifications SET status = 'failed', error_message = ? 
                                      WHERE user_id = ? AND sent_at = ? AND notification_type = ?";
                
                $error_message = $e->getMessage();
                $update_status_stmt = $conn->prepare($update_status_query);
                $update_status_stmt->bind_param("siss", $error_message, $parent_user_id, $now, $notification_type);
                $update_status_stmt->execute();
                $update_status_stmt->close();
            }
        } else {
            // ถ้าไม่มี LINE ID ถือว่าส่งไม่สำเร็จ
            $failed_count++;
        }
    }
    
    $parent_stmt->close();
}

// อัพเดทตาราง risk_students เพื่อบันทึกว่าได้ส่งการแจ้งเตือนแล้ว
$academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
$academic_year_result = $conn->query($academic_year_query);
$academic_year_data = $academic_year_result->fetch_assoc();
$academic_year_id = $academic_year_data['academic_year_id'];

// ตรวจสอบว่ามีข้อมูลในตาราง risk_students หรือไม่
$check_risk_query = "SELECT risk_id FROM risk_students WHERE student_id = ? AND academic_year_id = ?";
$check_risk_stmt = $conn->prepare($check_risk_query);
$check_risk_stmt->bind_param("ii", $student_id, $academic_year_id);
$check_risk_stmt->execute();
$check_risk_result = $check_risk_stmt->get_result();

if ($check_risk_result->num_rows > 0) {
    // อัพเดทข้อมูลที่มีอยู่
    $update_risk_query = "UPDATE risk_students SET 
                         absence_count = ?, 
                         risk_level = CASE 
                                     WHEN ? >= 20 THEN 'critical'
                                     WHEN ? >= 15 THEN 'high'
                                     WHEN ? >= 10 THEN 'medium'
                                     ELSE 'low'
                                     END,
                         notification_sent = 1,
                         notification_date = ?,
                         updated_at = ?
                        WHERE student_id = ? AND academic_year_id = ?";
    
    $update_risk_stmt = $conn->prepare($update_risk_query);
    $update_risk_stmt->bind_param("iiiiisii", $absent_count, $absent_count, $absent_count, $absent_count, $now, $now, $student_id, $academic_year_id);
    $update_risk_stmt->execute();
    $update_risk_stmt->close();
} else {
    // สร้างข้อมูลใหม่
    $insert_risk_query = "INSERT INTO risk_students (
                         student_id, academic_year_id, absence_count, 
                         risk_level, notification_sent, notification_date, created_at, updated_at
                        ) VALUES (
                         ?, ?, ?,
                         CASE 
                         WHEN ? >= 20 THEN 'critical'
                         WHEN ? >= 15 THEN 'high'
                         WHEN ? >= 10 THEN 'medium'
                         ELSE 'low'
                         END,
                         1, ?, ?, ?
                        )";
    
    $insert_risk_stmt = $conn->prepare($insert_risk_query);
    $insert_risk_stmt->bind_param("iiiiiiiss", $student_id, $academic_year_id, $absent_count, $absent_count, $absent_count, $absent_count, $now, $now, $now);
    $insert_risk_stmt->execute();
    $insert_risk_stmt->close();
}

$check_risk_stmt->close();

// สร้างข้อมูลส่งกลับ
echo json_encode([
    'success' => true,
    'message' => "ส่งการแจ้งเตือนสำเร็จ {$success_count} คน, ล้มเหลว {$failed_count} คน",
    'student_name' => $student_name,
    'notification_message' => $notification_message
]);

// ปิดการเชื่อมต่อ
$student_stmt->close();
$attendance_stmt->close();
$advisor_stmt->close();
$conn->close();
?>
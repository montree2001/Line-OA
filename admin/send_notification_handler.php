<?php
/**
 * send_notification_handler.php - ตัวจัดการการส่งข้อความแจ้งเตือนผ่าน AJAX
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
/* if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้']);
    exit;
} */

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

// เรียกใช้ API สำหรับส่งข้อความ LINE
require_once 'line_notification_api.php';

// เรียกใช้ Generator สำหรับสร้างกราฟและรายงาน
require_once 'attendance_report_generator.php';

// เรียกใช้ Template Manager สำหรับจัดการเทมเพลต
require_once 'notification_templates.php';

/**
 * ฟังก์ชันแทนที่ตัวแปรในเทมเพลตข้อความ
 * 
 * @param string $message ข้อความเทมเพลต
 * @param array $data ข้อมูลสำหรับแทนที่
 * @return string ข้อความที่แทนที่แล้ว
 */
function replaceTemplateVariables($message, $data) {
    $variables = [
        '{{ชื่อนักเรียน}}' => $data['student_name'] ?? '',
        '{{ชั้นเรียน}}' => $data['class'] ?? '',
        '{{จำนวนวันเข้าแถว}}' => $data['attendance_days'] ?? '',
        '{{จำนวนวันทั้งหมด}}' => $data['total_days'] ?? '',
        '{{ร้อยละการเข้าแถว}}' => $data['attendance_rate'] ?? '',
        '{{จำนวนวันขาด}}' => $data['absence_days'] ?? '',
        '{{สถานะการเข้าแถว}}' => $data['attendance_status'] ?? '',
        '{{ชื่อครูที่ปรึกษา}}' => $data['advisor_name'] ?? '',
        '{{เบอร์โทรครู}}' => $data['advisor_phone'] ?? '',
        '{{เดือน}}' => $data['month'] ?? date('m'),
        '{{ปี}}' => $data['year'] ?? date('Y'),
        '{{ชื่อกิจกรรม}}' => $data['activity_name'] ?? '',
        '{{วันที่}}' => $data['event_date'] ?? '',
        '{{เวลา}}' => $data['event_time'] ?? '',
        '{{สถานที่}}' => $data['location'] ?? '',
        '{{รายละเอียด}}' => $data['details'] ?? '',
        '{{ผู้รับผิดชอบ}}' => $data['responsible_person'] ?? '',
        '{{เบอร์โทร}}' => $data['phone'] ?? ''
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $message);
}

/**
 * ฟังก์ชันดึงข้อมูลนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @return array ข้อมูลนักเรียน
 */
function getStudentData($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT 
            s.student_id, s.title, u.first_name, u.last_name,
            c.level, c.group_number, u.phone_number, u.email,
            p.parent_id, pu.user_id as parent_user_id, pu.line_id as parent_line_id,
            pu.first_name as parent_first_name, pu.last_name as parent_last_name,
            p.relationship
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN parent_student_relation psr ON s.student_id = psr.student_id
            LEFT JOIN parents p ON psr.parent_id = p.parent_id
            LEFT JOIN users pu ON p.user_id = pu.user_id
        WHERE 
            s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return null;
    }
    
    // สร้างข้อมูลเพิ่มเติม
    $student['student_name'] = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
    $student['class'] = $student['level'] . '/' . $student['group_number'];
    $student['parent_name'] = $student['parent_first_name'] . ' ' . $student['parent_last_name'] . ' (' . $student['relationship'] . ')';
    
    // ดึงข้อมูลครูที่ปรึกษา
    $stmt = $conn->prepare("
        SELECT 
            t.title, t.first_name, t.last_name, u.phone_number
        FROM 
            classes c
            JOIN class_advisors ca ON c.class_id = ca.class_id
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            JOIN users u ON t.user_id = u.user_id
        WHERE 
            c.class_id = ? AND ca.is_primary = 1
    ");
    $stmt->execute([$student['current_class_id']]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($advisor) {
        $student['advisor_name'] = $advisor['title'] . ' ' . $advisor['first_name'] . ' ' . $advisor['last_name'];
        $student['advisor_phone'] = $advisor['phone_number'];
    } else {
        $student['advisor_name'] = '-';
        $student['advisor_phone'] = '-';
    }
    
    return $student;
}

/**
 * ฟังก์ชันดึงข้อมูลการเข้าแถวของนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return array ข้อมูลการเข้าแถว
 */
function getAttendanceData($conn, $student_id, $start_date = null, $end_date = null) {
    // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
    if (empty($start_date) || empty($end_date)) {
        $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $start_date = $academic_year['start_date'];
        $end_date = $academic_year['end_date'];
    }
    
    // ดึงข้อมูลการเข้าแถว
    $stmt = $conn->prepare("
        SELECT 
            a.date,
            a.attendance_status,
            a.check_method,
            a.check_time
        FROM 
            attendance a
            JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
        WHERE 
            a.student_id = ? 
            AND a.date BETWEEN ? AND ?
            AND ay.is_active = 1
        ORDER BY 
            a.date ASC
    ");
    
    $stmt->execute([$student_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // นับจำนวนวันตามสถานะ
    $present_count = 0;
    $absent_count = 0;
    $late_count = 0;
    $leave_count = 0;
    
    foreach ($attendance_records as $record) {
        switch ($record['attendance_status']) {
            case 'present':
                $present_count++;
                break;
            case 'absent':
                $absent_count++;
                break;
            case 'late':
                $late_count++;
                break;
            case 'leave':
                $leave_count++;
                break;
        }
    }
    
    $total_days = $present_count + $absent_count + $late_count + $leave_count;
    $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
    
    // กำหนดสถานะการเข้าแถว
    $attendance_status = 'ปกติ';
    if ($attendance_rate < 60) {
        $attendance_status = 'เสี่ยงตกกิจกรรม';
    } elseif ($attendance_rate < 80) {
        $attendance_status = 'ต้องระวัง';
    }
    
    return [
        'records' => $attendance_records,
        'present_count' => $present_count,
        'absent_count' => $absent_count,
        'late_count' => $late_count,
        'leave_count' => $leave_count,
        'total_days' => $total_days,
        'attendance_rate' => $attendance_rate,
        'attendance_status' => $attendance_status,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

/**
 * ฟังก์ชันส่งข้อความรายบุคคล
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $message ข้อความที่ต้องการส่ง
 * @param string $start_date วันที่เริ่มต้น (optional)
 * @param string $end_date วันที่สิ้นสุด (optional)
 * @param bool $include_chart แนบกราฟหรือไม่
 * @param bool $include_link แนบลิงก์หรือไม่
 * @return array ผลลัพธ์การส่ง
 */
function sendIndividualMessage($student_id, $message, $start_date = null, $end_date = null, $include_chart = false, $include_link = false) {
    global $conn;
    
    // ดึงข้อมูลนักเรียนและผู้ปกครอง
    $student = getStudentData($conn, $student_id);
    if (!$student) {
        return ['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน'];
    }
    
    // ตรวจสอบว่ามี LINE ID ของผู้ปกครองหรือไม่
    if (empty($student['parent_line_id'])) {
        return ['success' => false, 'message' => 'ไม่พบ LINE ID ของผู้ปกครอง'];
    }
    
    // ดึงข้อมูลการเข้าแถว
    $attendance = getAttendanceData($conn, $student_id, $start_date, $end_date);
    
    // สร้างข้อมูลสำหรับแทนที่ตัวแปรในเทมเพลต
    $template_data = [
        'student_name' => $student['student_name'],
        'class' => $student['class'],
        'attendance_days' => $attendance['present_count'],
        'total_days' => $attendance['total_days'],
        'attendance_rate' => $attendance['attendance_rate'],
        'absence_days' => $attendance['absent_count'],
        'attendance_status' => $attendance['attendance_status'],
        'advisor_name' => $student['advisor_name'],
        'advisor_phone' => $student['advisor_phone'],
        'month' => date('m'),
        'year' => date('Y') + 543 // พ.ศ.
    ];
    
    // แทนที่ตัวแปรในข้อความ
    $personalized_message = replaceTemplateVariables($message, $template_data);
    
    // สร้างกราฟ (ถ้าต้องการ)
    $chart_image_url = '';
    if ($include_chart) {
        $chart_image_url = generateAttendanceChart($student_id, $start_date, $end_date);
    }
    
    // สร้างลิงก์ (ถ้าต้องการ)
    $detail_url = '';
    if ($include_link) {
        $detail_url = generateDetailUrl($student_id, $start_date, $end_date);
    }
    
    // ส่งข้อความ
    $result = sendAttendanceNotification(
        $student['parent_line_id'],
        $personalized_message,
        $chart_image_url,
        $detail_url
    );
    
    // เพิ่มข้อมูลผลลัพธ์
    $result['student_id'] = $student_id;
    $result['student_name'] = $student['student_name'];
    $result['parent_line_id'] = $student['parent_line_id'];
    $result['message_count'] = 1 + ($include_chart ? 1 : 0) + ($include_link ? 1 : 0);
    
    // คำนวณค่าใช้จ่าย
    $message_cost = 0.075; // บาทต่อข้อความ
    $image_cost = 0.15; // บาทต่อรูปภาพ
    $cost = $message_cost + ($include_chart ? $image_cost : 0) + ($include_link ? $message_cost : 0);
    $result['cost'] = $cost;
    
    return $result;
}

/**
 * ฟังก์ชันส่งข้อความกลุ่ม
 * 
 * @param array $student_ids รายการรหัสนักเรียน
 * @param string $message ข้อความที่ต้องการส่ง
 * @param string $start_date วันที่เริ่มต้น (optional)
 * @param string $end_date วันที่สิ้นสุด (optional)
 * @param bool $include_chart แนบกราฟหรือไม่
 * @param bool $include_link แนบลิงก์หรือไม่
 * @return array ผลลัพธ์การส่ง
 */
function sendGroupMessage($student_ids, $message, $start_date = null, $end_date = null, $include_chart = false, $include_link = false) {
    $results = [];
    $success_count = 0;
    $error_count = 0;
    $total_cost = 0;
    
    foreach ($student_ids as $student_id) {
        $result = sendIndividualMessage($student_id, $message, $start_date, $end_date, $include_chart, $include_link);
        $results[] = $result;
        
        if ($result['success']) {
            $success_count++;
            $total_cost += $result['cost'];
        } else {
            $error_count++;
        }
    }
    
    return [
        'success' => ($success_count > 0),
        'message' => "ส่งข้อความสำเร็จ $success_count รายการ, ล้มเหลว $error_count รายการ",
        'results' => $results,
        'total_cost' => $total_cost,
        'success_count' => $success_count,
        'error_count' => $error_count
    ];
}

/**
 * ฟังก์ชันบันทึกเทมเพลตข้อความ
 * 
 * @param string $name ชื่อเทมเพลต
 * @param string $type ประเภทเทมเพลต (individual, group)
 * @param string $category หมวดหมู่เทมเพลต
 * @param string $content เนื้อหาเทมเพลต
 * @return array ผลลัพธ์การบันทึก
 */
function saveMessageTemplate($name, $type, $category, $content) {
    global $conn;
    
    try {
        // ตรวจสอบว่ามีชื่อซ้ำหรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM message_templates 
            WHERE name = ? AND id != ?
        ");
        $stmt->execute([$name, $_POST['template_id'] ?? 0]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            return ['success' => false, 'message' => 'มีเทมเพลตชื่อนี้อยู่แล้ว'];
        }
        
        // ถ้ามี ID ให้อัปเดต ถ้าไม่มีให้เพิ่มใหม่
        if (!empty($_POST['template_id'])) {
            $stmt = $conn->prepare("
                UPDATE message_templates 
                SET name = ?, type = ?, category = ?, content = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $category, $content, $_POST['template_id']]);
            
            return ['success' => true, 'message' => 'อัปเดตเทมเพลตเรียบร้อยแล้ว', 'template_id' => $_POST['template_id']];
        } else {
            $stmt = $conn->prepare("
                INSERT INTO message_templates (name, type, category, content, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $type, $category, $content, $_SESSION['user_id']]);
            
            return ['success' => true, 'message' => 'สร้างเทมเพลตใหม่เรียบร้อยแล้ว', 'template_id' => $conn->lastInsertId()];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

// ตรวจสอบการร้องขอ AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // ส่งข้อความรายบุคคล
    if (isset($_POST['send_individual_message'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_id']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียนและข้อความ']);
            exit;
        }
        
        $student_id = $_POST['student_id'];
        $message = $_POST['message'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $include_chart = isset($_POST['include_chart']) && $_POST['include_chart'] === 'true';
        $include_link = isset($_POST['include_link']) && $_POST['include_link'] === 'true';
        
        $result = sendIndividualMessage($student_id, $message, $start_date, $end_date, $include_chart, $include_link);
        
        echo json_encode($result);
        exit;
    }
    
    // ส่งข้อความกลุ่ม
    if (isset($_POST['send_group_message'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_ids']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียนและข้อความ']);
            exit;
        }
        
        $student_ids = json_decode($_POST['student_ids'], true);
        if (!is_array($student_ids) || empty($student_ids)) {
            echo json_encode(['success' => false, 'message' => 'รายการรหัสนักเรียนไม่ถูกต้อง']);
            exit;
        }
        
        $message = $_POST['message'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $include_chart = isset($_POST['include_chart']) && $_POST['include_chart'] === 'true';
        $include_link = isset($_POST['include_link']) && $_POST['include_link'] === 'true';
        
        $result = sendGroupMessage($student_ids, $message, $start_date, $end_date, $include_chart, $include_link);
        
        echo json_encode($result);
        exit;
    }
    
    // บันทึกเทมเพลต
    if (isset($_POST['save_template'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['template_name']) || empty($_POST['template_type']) || empty($_POST['template_content'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit;
        }
        
        $name = $_POST['template_name'];
        $type = $_POST['template_type'];
        $category = $_POST['template_category'] ?? 'attendance';
        $content = $_POST['template_content'];
        
        $result = saveMessageTemplate($name, $type, $category, $content);
        
        echo json_encode($result);
        exit;
    }
    
    // ดึงข้อมูลเทมเพลต
    if (isset($_POST['get_template'])) {
        if (empty($_POST['template_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสเทมเพลต']);
            exit;
        }
        
        $template_id = $_POST['template_id'];
        $template = getMessageTemplate($template_id);
        
        if ($template) {
            echo json_encode(['success' => true, 'template' => $template]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบเทมเพลต']);
        }
        exit;
    }
    
    // ลบเทมเพลต
    if (isset($_POST['delete_template'])) {
        if (empty($_POST['template_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสเทมเพลต']);
            exit;
        }
        
        $template_id = $_POST['template_id'];
        $result = deleteMessageTemplate($template_id);
        
        echo json_encode($result);
        exit;
    }
    
    // ดึงประวัติการส่งข้อความ
    if (isset($_POST['get_notification_history'])) {
        if (empty($_POST['student_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียน']);
            exit;
        }
        
        $student_id = $_POST['student_id'];
        $limit = $_POST['limit'] ?? 10;
        
        $history = getNotificationHistory($student_id, $limit);
        
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
    if (isset($_POST['get_at_risk_students'])) {
        $filters = [
            'class_level' => $_POST['class_level'] ?? '',
            'class_group' => $_POST['class_group'] ?? '',
            'risk_status' => $_POST['risk_status'] ?? '',
            'attendance_rate' => $_POST['attendance_rate'] ?? '',
            'student_name' => $_POST['student_name'] ?? ''
        ];
        
        $limit = $_POST['limit'] ?? 10;
        $offset = $_POST['offset'] ?? 0;
        
        // สมมติว่ามีฟังก์ชัน getAtRiskStudents ใน attendance_report_generator.php
        $students = getAtRiskStudents($conn, $limit, $offset, $filters);
        $total = countAtRiskStudents($conn, $filters);
        
        echo json_encode([
            'success' => true,
            'students' => $students,
            'total' => $total
        ]);
        exit;
    }
}

// กรณีเรียกโดยตรงโดยไม่ใช่ AJAX
echo json_encode(['success' => false, 'message' => 'การเข้าถึงโดยตรงไม่ได้รับอนุญาต']);
<?php
/**
 * send_notification.php - หน้าส่งข้อความแจ้งเตือนผู้ปกครอง
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'send_notification';
$page_title = 'ส่งข้อความแจ้งเตือน';
$page_header = 'ส่งข้อความรายงานการเข้าแถว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$admin_id]);
$admin_data = $stmt->fetch();

$admin_info = [
    'name' => $admin_data['first_name'] . ' ' . $admin_data['last_name'],
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => mb_substr($admin_data['first_name'], 0, 1, 'UTF-8')
];

// ค่าใช้จ่ายในการส่งข้อความ LINE
$line_message_cost = 0.075; // บาทต่อข้อความ
$notification_cost_image = 0.15; // บาทต่อภาพ

// ดึงข้อมูลตั้งค่าระบบ
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->execute(['line_access_token']);
$line_access_token = $stmt->fetchColumn();

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดูประวัติการส่งข้อความ',
        'icon' => 'history',
        'onclick' => 'showHistory()'
    ]
];

// ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$academicYearQuery = $conn->query("SELECT academic_year_id FROM academic_years WHERE is_active = 1");
$current_academic_year = $academicYearQuery->fetchColumn();

$at_risk_query = $conn->prepare("
    SELECT COUNT(*) FROM student_academic_records sar
    JOIN students s ON sar.student_id = s.student_id
    JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
    WHERE ay.is_active = 1
    AND s.status = 'กำลังศึกษา'
    AND (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 80
");
$at_risk_query->execute();
$at_risk_count = $at_risk_query->fetchColumn();

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/notification.css'
];

$extra_js = [
    'assets/js/notification.js',
    'assets/js/chart.min.js'  // เพิ่ม Chart.js สำหรับสร้างกราฟ
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/notification_content.php';

/**
 * ฟังก์ชันดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $limit จำนวนรายการที่ต้องการ
 * @param int $offset ตำแหน่งเริ่มต้น
 * @param array $filters เงื่อนไขการกรอง (ชั้นเรียน, ห้อง, สถานะ)
 * @return array ข้อมูลนักเรียน
 */
function getAtRiskStudents($conn, $limit = 10, $offset = 0, $filters = []) {
    $params = [];
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    
    // เพิ่มเงื่อนไขการกรอง
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        if ($filters['risk_status'] == 'เสี่ยงตกกิจกรรม') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['risk_status'] == 'ต้องระวัง') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['risk_status'] == 'ปกติ') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 80";
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        if ($filters['attendance_rate'] == 'น้อยกว่า 70%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['attendance_rate'] == '70% - 80%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['attendance_rate'] == '80% - 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 80 AND 90";
        } elseif ($filters['attendance_rate'] == 'มากกว่า 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 90";
        }
    }
    
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%" . $filters['student_name'] . "%";
        $params[] = "%" . $filters['student_name'] . "%";
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
    $query = "
        SELECT 
            s.student_id,
            s.student_code,
            s.title,
            u.first_name,
            u.last_name,
            c.level,
            c.group_number,
            (SELECT GROUP_CONCAT(CONCAT(pu.first_name, ' ', pu.last_name, ' (', p.relationship, ')'))
             FROM parent_student_relation psr
             JOIN parents p ON psr.parent_id = p.parent_id
             JOIN users pu ON p.user_id = pu.user_id
             WHERE psr.student_id = s.student_id) as parents_info,
            sar.total_attendance_days,
            sar.total_absence_days,
            (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 as attendance_rate,
            (SELECT LINE_id FROM users WHERE user_id IN 
                (SELECT user_id FROM parents WHERE parent_id IN 
                    (SELECT parent_id FROM parent_student_relation WHERE student_id = s.student_id)
                )
            ) as parent_line_id
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
        ORDER BY 
            attendance_rate ASC
        LIMIT ?, ?
    ";
    
    $params[] = (int)$offset;
    $params[] = (int)$limit;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เพิ่มข้อมูลสถานะความเสี่ยง
    foreach ($students as &$student) {
        $rate = $student['attendance_rate'];
        if ($rate < 60) {
            $student['status'] = 'เสี่ยงตกกิจกรรม';
            $student['status_class'] = 'danger';
        } elseif ($rate < 80) {
            $student['status'] = 'ต้องระวัง';
            $student['status_class'] = 'warning';
        } else {
            $student['status'] = 'ปกติ';
            $student['status_class'] = 'success';
        }
        
        // คำนวณจำนวนวันทั้งหมด
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $student['attendance_days'] = $student['total_attendance_days'] . '/' . $total_days . ' วัน (' . round($rate) . '%)';
        
        // รหัสห้องเรียน
        $student['class'] = $student['level'] . '/' . $student['group_number'];
        
        // ตัวอักษรแรกของชื่อ
        $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    }
    
    return $students;
}

/**
 * ฟังก์ชันนับจำนวนนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมด
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @return int จำนวนนักเรียนทั้งหมด
 */
function countAtRiskStudents($conn, $filters = []) {
    $params = [];
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    
    // เพิ่มเงื่อนไขการกรอง (เหมือนกับฟังก์ชัน getAtRiskStudents)
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        if ($filters['risk_status'] == 'เสี่ยงตกกิจกรรม') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['risk_status'] == 'ต้องระวัง') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['risk_status'] == 'ปกติ') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 80";
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        if ($filters['attendance_rate'] == 'น้อยกว่า 70%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['attendance_rate'] == '70% - 80%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['attendance_rate'] == '80% - 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 80 AND 90";
        } elseif ($filters['attendance_rate'] == 'มากกว่า 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 90";
        }
    }
    
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%" . $filters['student_name'] . "%";
        $params[] = "%" . $filters['student_name'] . "%";
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
    $query = "
        SELECT 
            COUNT(*) as total
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'];
}

/**
 * ฟังก์ชันดึงข้อมูลการเข้าแถวของนักเรียนในช่วงเวลาที่กำหนด
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
    
    $query = "
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
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้างข้อมูลสรุป
    $summary = [
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'leave' => 0,
        'total_days' => 0,
        'attendance_rate' => 0,
        'dates' => [],
        'statuses' => []
    ];
    
    foreach ($attendance_records as $record) {
        $summary[$record['attendance_status']]++;
        $summary['total_days']++;
        $summary['dates'][] = $record['date'];
        $summary['statuses'][] = $record['attendance_status'];
    }
    
    if ($summary['total_days'] > 0) {
        $summary['attendance_rate'] = ($summary['present'] / $summary['total_days']) * 100;
    }
    
    return [
        'records' => $attendance_records,
        'summary' => $summary
    ];
}

/**
 * ฟังก์ชันสร้างกราฟการเข้าแถวเป็น URL ของรูปภาพ
 * 
 * @param array $attendance_data ข้อมูลการเข้าแถว
 * @param string $student_name ชื่อนักเรียน
 * @param string $class_info ข้อมูลชั้นเรียน
 * @return string URL ของรูปภาพ
 */
function generateAttendanceChartImage($attendance_data, $student_name, $class_info) {
    // ในสถานการณ์จริง นี่จะเป็นการสร้างกราฟด้วย library เช่น Chart.js หรือ GD
    // แล้วส่งออกเป็นไฟล์รูปภาพที่จะใช้กับ LINE API
    
    // สำหรับตัวอย่าง เราจะจำลองการส่งกลับ URL ของรูปภาพ
    $image_url = "https://example.com/charts/attendance_" . time() . ".png";
    
    return $image_url;
}

/**
 * ฟังก์ชันส่งข้อความผ่าน LINE Messaging API
 * 
 * @param string $line_id LINE ID ของผู้รับ
 * @param string $message ข้อความที่ต้องการส่ง
 * @param string $chart_image_url URL ของรูปภาพกราฟ
 * @param string $detail_link URL ของลิงก์ดูรายละเอียด
 * @return array ผลลัพธ์การส่ง
 */
function sendLineNotification($line_id, $message, $chart_image_url = '', $detail_link = '') {
    global $line_access_token;
    
    // สร้างข้อความที่จะส่ง
    $messages = [];
    
    // เพิ่มข้อความหลัก
    $messages[] = [
        'type' => 'text',
        'text' => $message
    ];
    
    // เพิ่มรูปภาพกราฟ (ถ้ามี)
    if (!empty($chart_image_url)) {
        $messages[] = [
            'type' => 'image',
            'originalContentUrl' => $chart_image_url,
            'previewImageUrl' => $chart_image_url
        ];
    }
    
    // เพิ่มปุ่มลิงก์ดูรายละเอียด (ถ้ามี)
    if (!empty($detail_link)) {
        $messages[] = [
            'type' => 'template',
            'altText' => 'ดูรายละเอียดเพิ่มเติม',
            'template' => [
                'type' => 'buttons',
                'text' => 'คลิกเพื่อดูรายละเอียดเพิ่มเติม',
                'actions' => [
                    [
                        'type' => 'uri',
                        'label' => 'ดูรายละเอียด',
                        'uri' => $detail_link
                    ]
                ]
            ]
        ];
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    $data = [
        'to' => $line_id,
        'messages' => $messages
    ];
    
    // ในสถานการณ์จริง นี่จะเป็นการส่ง HTTP request ไปยัง LINE API
    // โดยใช้ curl หรือ library อื่นๆ
    
    // จำลองการส่ง (ในทางปฏิบัติจริง)
    /*
    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $line_access_token
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $success = ($httpCode == 200);
    */
    
    // สำหรับตัวอย่าง เราจะจำลองผลลัพธ์
    $success = true;
    $error_message = '';
    
    // ในกรณีบางครั้ง สร้างเหตุการณ์การส่งไม่สำเร็จสุ่ม
    if (rand(1, 10) == 1) {
        $success = false;
        $error_message = 'ไม่สามารถส่งข้อความได้ (จำลอง)';
    }
    
    return [
        'success' => $success,
        'line_id' => $line_id,
        'message_count' => count($messages),
        'error_message' => $error_message
    ];
}

/**
 * ฟังก์ชันคำนวณค่าใช้จ่ายในการส่งข้อความ
 * 
 * @param int $text_message_count จำนวนข้อความข้อความ
 * @param int $image_count จำนวนรูปภาพ
 * @return float ค่าใช้จ่ายทั้งหมด (บาท)
 */
function calculateMessageCost($text_message_count, $image_count = 0) {
    global $line_message_cost, $notification_cost_image;
    
    return ($text_message_count * $line_message_cost) + ($image_count * $notification_cost_image);
}

/**
 * ฟังก์ชันบันทึกประวัติการส่งข้อความ
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $user_id รหัสผู้ใช้ผู้รับ
 * @param string $message ข้อความที่ส่ง
 * @param string $status สถานะการส่ง (pending, sent, failed)
 * @param string $notification_type ประเภทการแจ้งเตือน
 * @param string $error_message ข้อความแสดงข้อผิดพลาด (ถ้ามี)
 * @return int รหัสการแจ้งเตือนที่บันทึก
 */
function logNotification($conn, $user_id, $message, $status = 'pending', $notification_type = 'attendance', $error_message = '') {
    $query = "
        INSERT INTO line_notifications (
            user_id, message, status, notification_type, error_message
        ) VALUES (
            ?, ?, ?, ?, ?
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $message, $status, $notification_type, $error_message]);
    
    return $conn->lastInsertId();
}

/**
 * ฟังก์ชันดึงประวัติการส่งข้อความของนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param int $limit จำนวนรายการที่ต้องการ
 * @return array ประวัติการส่งข้อความ
 */
function getStudentNotificationHistory($conn, $student_id, $limit = 10) {
    $query = "
        SELECT 
            ln.notification_id,
            ln.message,
            ln.sent_at,
            ln.status,
            ln.notification_type,
            u.first_name,
            u.last_name
        FROM 
            line_notifications ln
            JOIN users u ON ln.user_id = u.user_id
            JOIN parents p ON u.user_id = p.user_id
            JOIN parent_student_relation psr ON p.parent_id = psr.parent_id
        WHERE 
            psr.student_id = ?
        ORDER BY 
            ln.sent_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ฟังก์ชันดึงเทมเพลตข้อความ
 * 
 * @return array เทมเพลตข้อความ
 */
function getMessageTemplates() {
    // ในทางปฏิบัติจริง ควรดึงจากฐานข้อมูล
    return [
        [
            'id' => 1,
            'name' => 'แจ้งเตือนความเสี่ยงรายบุคคล',
            'type' => 'รายบุคคล',
            'created_at' => '10/03/2568',
            'last_used' => '16/03/2568',
            'status' => 'ใช้งาน',
            'content' => 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางโรงเรียนขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน ({{ร้อยละการเข้าแถว}}%)

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
        ],
        [
            'id' => 2,
            'name' => 'นัดประชุมผู้ปกครองกลุ่มเสี่ยง',
            'type' => 'กลุ่ม',
            'created_at' => '05/03/2568',
            'last_used' => '16/03/2568',
            'status' => 'ใช้งาน',
            'content' => 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
        ],
        [
            'id' => 3,
            'name' => 'แจ้งเตือนฉุกเฉิน',
            'type' => 'รายบุคคล',
            'created_at' => '01/02/2568',
            'last_used' => '10/03/2568',
            'status' => 'ใช้งาน',
            'content' => 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

[ข้อความด่วน] ทางโรงเรียนขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน ({{ร้อยละการเข้าแถว}}%)

ขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
        ],
        [
            'id' => 4,
            'name' => 'รายงานสรุปประจำเดือน',
            'type' => 'กลุ่ม',
            'created_at' => '15/01/2568',
            'last_used' => '01/03/2568',
            'status' => 'ใช้งาน',
            'content' => 'เรียน ท่านผู้ปกครองนักเรียน

สรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน{{เดือน}} {{ปี}}

จำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)
จำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน
สถานะ: {{สถานะการเข้าแถว}}

หมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
        ],
        [
            'id' => 5,
            'name' => 'แจ้งข่าวสารทั่วไป',
            'type' => 'กลุ่ม',
            'created_at' => '05/01/2568',
            'last_used' => '-',
            'status' => 'ใช้งาน',
            'content' => 'เรียน ท่านผู้ปกครองนักเรียน

ทางวิทยาลัยขอแจ้งข้อมูลข่าวสารเกี่ยวกับกิจกรรม{{ชื่อกิจกรรม}} ซึ่งจะจัดขึ้นในวันที่ {{วันที่}} เวลา {{เวลา}} ณ {{สถานที่}}

นักเรียนจะต้อง{{รายละเอียด}}

หากมีข้อสงสัยกรุณาติดต่อ {{ผู้รับผิดชอบ}} ที่เบอร์โทร {{เบอร์โทร}}

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท'
        ]
    ];
}

// ตรวจสอบการส่งข้อมูลผ่าน AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตก
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
        
        $students = getAtRiskStudents($conn, $limit, $offset, $filters);
        $total = countAtRiskStudents($conn, $filters);
        
        echo json_encode([
            'success' => true,
            'students' => $students,
            'total' => $total
        ]);
        exit;
    }
    
    // ดึงข้อมูลการเข้าแถวของนักเรียน
    if (isset($_POST['get_attendance_data'])) {
        $student_id = $_POST['student_id'] ?? 0;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        $attendance_data = getAttendanceData($conn, $student_id, $start_date, $end_date);
        
        echo json_encode([
            'success' => true,
            'attendance_data' => $attendance_data
        ]);
        exit;
    }
    
    // ดึงประวัติการส่งข้อความ
    if (isset($_POST['get_notification_history'])) {
        $student_id = $_POST['student_id'] ?? 0;
        
        $history = getStudentNotificationHistory($conn, $student_id);
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        exit;
    }
    
    // ส่งข้อความรายบุคคล
    if (isset($_POST['send_individual_message'])) {
        $student_id = $_POST['student_id'] ?? 0;
        $message = $_POST['message'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $include_chart = $_POST['include_chart'] ?? false;
        $include_link = $_POST['include_link'] ?? false;
        
        // ดึงข้อมูลนักเรียน
        $stmt = $conn->prepare("
            SELECT 
                s.student_id, s.title, u.first_name, u.last_name, 
                c.level, c.group_number,
                p.parent_id, pu.user_id as parent_user_id, pu.line_id as parent_line_id
            FROM 
                students s
                JOIN users u ON s.user_id = u.user_id
                JOIN classes c ON s.current_class_id = c.class_id
                JOIN parent_student_relation psr ON s.student_id = psr.student_id
                JOIN parents p ON psr.parent_id = p.parent_id
                JOIN users pu ON p.user_id = pu.user_id
            WHERE 
                s.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_data) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            exit;
        }
        
        // ดึงข้อมูลการเข้าแถว
        $attendance_data = getAttendanceData($conn, $student_id, $start_date, $end_date);
        
        // สร้างชื่อนักเรียนแบบเต็ม
        $student_full_name = $student_data['title'] . ' ' . $student_data['first_name'] . ' ' . $student_data['last_name'];
        $class_info = $student_data['level'] . '/' . $student_data['group_number'];
        
        // สร้างกราฟ (ถ้าต้องการ)
        $chart_image_url = '';
        if ($include_chart) {
            $chart_image_url = generateAttendanceChartImage($attendance_data, $student_full_name, $class_info);
        }
        
        // สร้างลิงก์ดูรายละเอียด (ถ้าต้องการ)
        $detail_link = '';
        if ($include_link) {
            $detail_link = "https://example.com/parents/attendance_detail.php?student_id=$student_id";
            if (!empty($start_date) && !empty($end_date)) {
                $detail_link .= "&start_date=$start_date&end_date=$end_date";
            }
        }
        
        // แทนค่าข้อมูลในข้อความ
        $message = str_replace('{{ชื่อนักเรียน}}', $student_full_name, $message);
        $message = str_replace('{{ชั้นเรียน}}', $class_info, $message);
        $message = str_replace('{{จำนวนวันเข้าแถว}}', $attendance_data['summary']['present'], $message);
        $message = str_replace('{{จำนวนวันทั้งหมด}}', $attendance_data['summary']['total_days'], $message);
        $message = str_replace('{{ร้อยละการเข้าแถว}}', round($attendance_data['summary']['attendance_rate']), $message);
        $message = str_replace('{{จำนวนวันขาด}}', $attendance_data['summary']['absent'], $message);
        
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
        $stmt->execute([$student_data['current_class_id']]);
        $advisor_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($advisor_data) {
            $advisor_name = $advisor_data['title'] . ' ' . $advisor_data['first_name'] . ' ' . $advisor_data['last_name'];
            $advisor_phone = $advisor_data['phone_number'];
            
            $message = str_replace('{{ชื่อครูที่ปรึกษา}}', $advisor_name, $message);
            $message = str_replace('{{เบอร์โทรครู}}', $advisor_phone, $message);
        }
        
        // ส่งข้อความ
        $send_result = sendLineNotification(
            $student_data['parent_line_id'],
            $message,
            $chart_image_url,
            $detail_link
        );
        
        // คำนวณค่าใช้จ่าย
        $message_count = 1; // ข้อความพื้นฐาน
        $image_count = !empty($chart_image_url) ? 1 : 0;
        $link_count = !empty($detail_link) ? 1 : 0;
        
        $total_message_count = $message_count + $image_count + $link_count;
        $cost = calculateMessageCost($message_count, $image_count);
        
        // บันทึกประวัติการส่งข้อความ
        $notification_status = $send_result['success'] ? 'sent' : 'failed';
        $notification_id = logNotification(
            $conn,
            $student_data['parent_user_id'],
            $message,
            $notification_status,
            'attendance',
            $send_result['error_message']
        );
        
        echo json_encode([
            'success' => $send_result['success'],
            'message' => $send_result['success'] ? 'ส่งข้อความเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาดในการส่งข้อความ',
            'student_id' => $student_id,
            'student_name' => $student_full_name,
            'parent_line_id' => $student_data['parent_line_id'],
            'message_count' => $total_message_count,
            'cost' => $cost,
            'notification_id' => $notification_id,
            'error_message' => $send_result['error_message']
        ]);
        exit;
    }
    
    // ส่งข้อความกลุ่ม
    if (isset($_POST['send_group_message'])) {
        $student_ids = $_POST['student_ids'] ?? [];
        $message = $_POST['message'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $include_chart = $_POST['include_chart'] ?? false;
        $include_link = $_POST['include_link'] ?? false;
        
        if (empty($student_ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'กรุณาเลือกนักเรียนอย่างน้อย 1 คน'
            ]);
            exit;
        }
        
        $results = [];
        $total_cost = 0;
        $success_count = 0;
        $error_count = 0;
        
        foreach ($student_ids as $student_id) {
            // ดึงข้อมูลนักเรียน
            $stmt = $conn->prepare("
                SELECT 
                    s.student_id, s.title, u.first_name, u.last_name, 
                    c.level, c.group_number,
                    p.parent_id, pu.user_id as parent_user_id, pu.line_id as parent_line_id
                FROM 
                    students s
                    JOIN users u ON s.user_id = u.user_id
                    JOIN classes c ON s.current_class_id = c.class_id
                    JOIN parent_student_relation psr ON s.student_id = psr.student_id
                    JOIN parents p ON psr.parent_id = p.parent_id
                    JOIN users pu ON p.user_id = pu.user_id
                WHERE 
                    s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student_data) {
                $results[] = [
                    'student_id' => $student_id,
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ];
                $error_count++;
                continue;
            }
            
            // ดึงข้อมูลการเข้าแถว
            $attendance_data = getAttendanceData($conn, $student_id, $start_date, $end_date);
            
            // สร้างชื่อนักเรียนแบบเต็ม
            $student_full_name = $student_data['title'] . ' ' . $student_data['first_name'] . ' ' . $student_data['last_name'];
            $class_info = $student_data['level'] . '/' . $student_data['group_number'];
            
            // สร้างกราฟ (ถ้าต้องการ)
            $chart_image_url = '';
            if ($include_chart) {
                $chart_image_url = generateAttendanceChartImage($attendance_data, $student_full_name, $class_info);
            }
            
            // สร้างลิงก์ดูรายละเอียด (ถ้าต้องการ)
            $detail_link = '';
            if ($include_link) {
                $detail_link = "https://example.com/parents/attendance_detail.php?student_id=$student_id";
                if (!empty($start_date) && !empty($end_date)) {
                    $detail_link .= "&start_date=$start_date&end_date=$end_date";
                }
            }
            
            // แทนค่าข้อมูลในข้อความ
            $personalized_message = str_replace('{{ชื่อนักเรียน}}', $student_full_name, $message);
            $personalized_message = str_replace('{{ชั้นเรียน}}', $class_info, $personalized_message);
            $personalized_message = str_replace('{{จำนวนวันเข้าแถว}}', $attendance_data['summary']['present'], $personalized_message);
            $personalized_message = str_replace('{{จำนวนวันทั้งหมด}}', $attendance_data['summary']['total_days'], $personalized_message);
            $personalized_message = str_replace('{{ร้อยละการเข้าแถว}}', round($attendance_data['summary']['attendance_rate']), $personalized_message);
            $personalized_message = str_replace('{{จำนวนวันขาด}}', $attendance_data['summary']['absent'], $personalized_message);
            
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
            $stmt->execute([$student_data['current_class_id']]);
            $advisor_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($advisor_data) {
                $advisor_name = $advisor_data['title'] . ' ' . $advisor_data['first_name'] . ' ' . $advisor_data['last_name'];
                $advisor_phone = $advisor_data['phone_number'];
                
                $personalized_message = str_replace('{{ชื่อครูที่ปรึกษา}}', $advisor_name, $personalized_message);
                $personalized_message = str_replace('{{เบอร์โทรครู}}', $advisor_phone, $personalized_message);
            }
            
            // ส่งข้อความ
            $send_result = sendLineNotification(
                $student_data['parent_line_id'],
                $personalized_message,
                $chart_image_url,
                $detail_link
            );
            
            // คำนวณค่าใช้จ่าย
            $message_count = 1; // ข้อความพื้นฐาน
            $image_count = !empty($chart_image_url) ? 1 : 0;
            $link_count = !empty($detail_link) ? 1 : 0;
            
            $total_message_count = $message_count + $image_count + $link_count;
            $cost = calculateMessageCost($message_count, $image_count);
            $total_cost += $cost;
            
            // บันทึกประวัติการส่งข้อความ
            $notification_status = $send_result['success'] ? 'sent' : 'failed';
            $notification_id = logNotification(
                $conn,
                $student_data['parent_user_id'],
                $personalized_message,
                $notification_status,
                'attendance',
                $send_result['error_message']
            );
            
            $results[] = [
                'student_id' => $student_id,
                'student_name' => $student_full_name,
                'parent_line_id' => $student_data['parent_line_id'],
                'success' => $send_result['success'],
                'message_count' => $total_message_count,
                'cost' => $cost,
                'notification_id' => $notification_id,
                'error_message' => $send_result['error_message']
            ];
            
            if ($send_result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        echo json_encode([
            'success' => ($success_count > 0),
            'message' => "ส่งข้อความสำเร็จ $success_count รายการ, ล้มเหลว $error_count รายการ",
            'results' => $results,
            'total_cost' => $total_cost,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
        exit;
    }
    
    // บันทึกเทมเพลต
    if (isset($_POST['save_template'])) {
        $template_name = $_POST['template_name'] ?? '';
        $template_type = $_POST['template_type'] ?? '';
        $template_content = $_POST['template_content'] ?? '';
        
        // ในทางปฏิบัติจริง ควรมีการบันทึกลงฐานข้อมูล
        // นี่เป็นตัวอย่างการจำลองการบันทึก
        
        $response = [
            'success' => true,
            'message' => 'บันทึกเทมเพลตเรียบร้อยแล้ว',
            'template_id' => time() // ใช้เวลาปัจจุบันเป็น ID
        ];
        
        echo json_encode($response);
        exit;
    }
}

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
$filters = [];
$students = getAtRiskStudents($conn, 10, 0, $filters);
$at_risk_students = getAtRiskStudents($conn, 8, 0, ['class_level' => 'ม.5', 'class_group' => '1', 'attendance_rate' => 'น้อยกว่า 70%']);

// ดึงเทมเพลตข้อความ
$templates = getMessageTemplates();

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'at_risk_students' => $at_risk_students,
    'templates' => $templates
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
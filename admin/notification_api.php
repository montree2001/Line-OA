<?php
/**
 * notification_api.php - API สำหรับส่งข้อความแจ้งเตือนผ่าน LINE Messaging API
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

/**
 * ฟังก์ชันส่งข้อความผ่าน LINE Messaging API
 * 
 * @param string $to LINE User ID ของผู้รับ
 * @param array $messages ข้อความที่ต้องการส่ง
 * @param string $channel_access_token Access Token ของ LINE Channel
 * @return array ผลลัพธ์การส่ง
 */
function sendLineMessage($to, $messages, $channel_access_token) {
    $url = 'https://api.line.me/v2/bot/message/push';
    
    $data = [
        'to' => $to,
        'messages' => $messages
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $channel_access_token
            ],
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    // ตรวจสอบสถานะการส่ง
    $status_line = $http_response_header[0];
    preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
    $status = $match[1];
    
    return [
        'success' => ($status == 200),
        'response' => $response,
        'status' => $status
    ];
}

/**
 * แทนที่ตัวแปรในเทมเพลตข้อความ
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
        '{{ปี}}' => $data['year'] ?? date('Y')
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $message);
}

/**
 * ฟังก์ชันสร้างภาพกราฟการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL ของรูปภาพกราฟ
 */
function generateAttendanceChart($conn, $student_id, $start_date = null, $end_date = null) {
    // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
    if (empty($start_date) || empty($end_date)) {
        $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $start_date = $academic_year['start_date'];
        $end_date = $academic_year['end_date'];
    }
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT 
            s.title, u.first_name, u.last_name,
            c.level, c.group_number
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
        WHERE 
            s.student_id = ?
    ");
    
    $stmt->execute([$student_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        return '';
    }
    
    // ดึงข้อมูลการเข้าแถว
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(a.date, '%d/%m') as date_label,
            a.attendance_status
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
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดเตรียมข้อมูลสำหรับสร้างกราฟ
    $dates = [];
    $attendance_rates = [];
    $current_rate = 0;
    $total_days = 0;
    $present_days = 0;
    
    foreach ($attendance_data as $record) {
        $dates[] = $record['date_label'];
        
        if ($record['attendance_status'] == 'present') {
            $present_days++;
        }
        $total_days++;
        
        $current_rate = ($present_days / $total_days) * 100;
        $attendance_rates[] = round($current_rate);
    }
    
    // ถ้าไม่มีข้อมูล ใช้ข้อมูลตัวอย่าง
    if (empty($dates)) {
        $current_date = new DateTime();
        $dates = [];
        $attendance_rates = [];
        
        for ($i = 0; $i < 5; $i++) {
            $date = clone $current_date;
            $date->modify("-$i week");
            $dates[] = $date->format('d/m');
            $attendance_rates[] = rand(60, 90);
        }
        
        // เรียงลำดับวันที่
        $dates = array_reverse($dates);
    }
    
    // สร้างหัวข้อกราฟ
    $student_full_name = $student_data['title'] . ' ' . $student_data['first_name'] . ' ' . $student_data['last_name'];
    $class_info = $student_data['level'] . '/' . $student_data['group_number'];
    $chart_title = 'การเข้าแถวของ ' . $student_full_name;
    $chart_subtitle = 'ชั้น ' . $class_info . ' วันที่ ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
    
    // สร้างข้อมูลสำหรับกราฟ
    $chart_data = [
        'type' => 'line',
        'data' => [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'อัตราการเข้าแถว (%)',
                    'data' => $attendance_rates,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ],
        'options' => [
            'title' => [
                'display' => true,
                'text' => [$chart_title, $chart_subtitle]
            ],
            'scales' => [
                'yAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true,
                            'max' => 100
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    // ในสถานการณ์จริง ใช้ QuickChart API
    $chart_url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chart_data));
    
    return $chart_url;
}

/**
 * ฟังก์ชันสร้าง URL สำหรับดูรายละเอียดการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL สำหรับดูรายละเอียด
 */
function generateDetailUrl($student_id, $start_date = null, $end_date = null) {
    $base_url = 'https://student-prasat.ac.th/parents/attendance_detail.php';
    $url = $base_url . '?student_id=' . $student_id;
    
    if (!empty($start_date) && !empty($end_date)) {
        $url .= '&start_date=' . $start_date . '&end_date=' . $end_date;
    }
    
    return $url;
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
 * ฟังก์ชันบันทึกประวัติการส่งข้อความ
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $user_id รหัสผู้ใช้ผู้รับ
 * @param string $message ข้อความที่ส่ง
 * @param string $status สถานะการส่ง
 * @param string $type ประเภทการแจ้งเตือน
 * @param string $error ข้อความแสดงข้อผิดพลาด
 * @return int รหัสการแจ้งเตือน
 */
function logNotification($conn, $user_id, $message, $status = 'sent', $type = 'attendance', $error = '') {
    $stmt = $conn->prepare("
        INSERT INTO line_notifications (
            user_id, message, status, notification_type, error_message
        ) VALUES (
            ?, ?, ?, ?, ?
        )
    ");
    $stmt->execute([$user_id, $message, $status, $type, $error]);
    
    return $conn->lastInsertId();
}

/**
 * ฟังก์ชันคำนวณค่าใช้จ่ายในการส่งข้อความ
 * 
 * @param int $messages จำนวนข้อความ
 * @param int $images จำนวนรูปภาพ
 * @param int $links จำนวนลิงก์
 * @return float ค่าใช้จ่ายรวม
 */
function calculateCost($messages = 1, $images = 0, $links = 0) {
    $message_cost = 0.075; // บาทต่อข้อความ
    $image_cost = 0.150; // บาทต่อรูปภาพ
    $link_cost = 0.075; // บาทต่อลิงก์
    
    return ($messages * $message_cost) + ($images * $image_cost) + ($links * $link_cost);
}

/**
 * ฟังก์ชันดึงข้อมูลนักเรียนที่มีความเสี่ยง
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @param int $limit จำนวนรายการที่ต้องการ
 * @param int $offset ตำแหน่งเริ่มต้น
 * @return array ข้อมูลนักเรียน
 */
function getAtRiskStudents($conn, $filters = [], $limit = 10, $offset = 0) {
    $limit = max(1, intval($limit));
    $offset = max(0, intval($offset));
    
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    $params = [];
    
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        switch ($filters['risk_status']) {
            case 'เสี่ยงตกกิจกรรม':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 60";
                break;
            case 'ต้องระวัง':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 60 AND 80";
                break;
            case 'ปกติ':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 80";
                break;
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        switch ($filters['attendance_rate']) {
            case 'น้อยกว่า 70%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 70";
                break;
            case '70% - 80%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 70 AND 80";
                break;
            case '80% - 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 80 AND 90";
                break;
            case 'มากกว่า 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 90";
                break;
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
            (
                SELECT GROUP_CONCAT(CONCAT(pu.first_name, ' ', pu.last_name, ' (', p.relationship, ')'))
                FROM parent_student_relation psr
                JOIN parents p ON psr.parent_id = p.parent_id
                JOIN users pu ON p.user_id = pu.user_id
                WHERE psr.student_id = s.student_id
            ) as parents_info,
            sar.total_attendance_days,
            sar.total_absence_days,
            CASE 
                WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                THEN ROUND((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100, 2)
                ELSE 0 
            END as attendance_rate
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id 
                AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
        ORDER BY 
            attendance_rate ASC
        LIMIT ?, ?
    ";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ปรับแต่งข้อมูล
    foreach ($students as &$student) {
        $rate = $student['attendance_rate'];
        
        // กำหนดสถานะความเสี่ยง
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
        
        // จัดรูปแบบข้อมูล
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $student['attendance_days'] = "{$student['total_attendance_days']}/$total_days วัน ({$rate}%)";
        $student['class'] = $student['level'] . '/' . $student['group_number'];
        $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    }
    
    return $students;
}

/**
 * ฟังก์ชันนับจำนวนนักเรียนที่มีความเสี่ยงทั้งหมด
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @return int จำนวนนักเรียนทั้งหมด
 */
function countAtRiskStudents($conn, $filters = []) {
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    $params = [];
    
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        switch ($filters['risk_status']) {
            case 'เสี่ยงตกกิจกรรม':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 60";
                break;
            case 'ต้องระวัง':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 60 AND 80";
                break;
            case 'ปกติ':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 80";
                break;
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        switch ($filters['attendance_rate']) {
            case 'น้อยกว่า 70%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 70";
                break;
            case '70% - 80%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 70 AND 80";
                break;
            case '80% - 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 80 AND 90";
                break;
            case 'มากกว่า 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 90";
                break;
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
            JOIN student_academic_records sar ON s.student_id = sar.student_id 
                AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'];
}

// ตรวจสอบการร้องขอ AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ Content-Type
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        // รับข้อมูล JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // ส่งข้อความรายบุคคล
        if (isset($data['action']) && $data['action'] === 'send_individual') {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($data['student_id']) || empty($data['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียนและข้อความ']);
                exit;
            }
            
            $student_id = $data['student_id'];
            $message = $data['message'];
            $start_date = $data['start_date'] ?? null;
            $end_date = $data['end_date'] ?? null;
            $include_chart = $data['include_chart'] ?? false;
            $include_link = $data['include_link'] ?? false;
            
            try {
                // ดึงข้อมูลนักเรียน
                $student = getStudentData($conn, $student_id);
                if (!$student) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
                    exit;
                }
                
                // ตรวจสอบว่ามี LINE ID ของผู้ปกครองหรือไม่
                if (empty($student['parent_line_id'])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'ไม่พบ LINE ID ของผู้ปกครอง ' . $student['parent_name']
                    ]);
                    exit;
                }
                
                // ดึงข้อมูลการเข้าแถว
                $attendance = getAttendanceData($conn, $student_id, $start_date, $end_date);
                
                // แทนที่ตัวแปรในข้อความ
                $data = [
                    'student_name' => $student['student_name'],
                    'class' => $student['class'],
                    'attendance_days' => $attendance['present_count'],
                    'total_days' => $attendance['total_days'],
                    'attendance_rate' => $attendance['attendance_rate'],
                    'absence_days' => $attendance['absent_count'],
                    'attendance_status' => $attendance['attendance_status'],
                    'advisor_name' => $student['advisor_name'],
                    'advisor_phone' => $student['advisor_phone']
                ];
                
                $personalized_message = replaceTemplateVariables($message, $data);
                
                // ดึง Channel Access Token
                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_access_token'");
                $stmt->execute();
                $channel_access_token = $stmt->fetchColumn();
                
                // สร้างข้อความที่จะส่ง
                $messages = [];
                
                // เพิ่มข้อความพื้นฐาน
                $messages[] = [
                    'type' => 'text',
                    'text' => $personalized_message
                ];
                
                // สร้างกราฟและเพิ่มรูปภาพ (ถ้าต้องการ)
                $chart_url = '';
                if ($include_chart) {
                    $chart_url = generateAttendanceChart($conn, $student_id, $start_date, $end_date);
                    if ($chart_url) {
                        $messages[] = [
                            'type' => 'image',
                            'originalContentUrl' => $chart_url,
                            'previewImageUrl' => $chart_url
                        ];
                    }
                }
                
                // สร้างลิงก์และเพิ่มปุ่ม (ถ้าต้องการ)
                $detail_url = '';
                if ($include_link) {
                    $detail_url = generateDetailUrl($student_id, $start_date, $end_date);
                    $messages[] = [
                        'type' => 'template',
                        'altText' => 'ดูรายละเอียดเพิ่มเติม',
                        'template' => [
                            'type' => 'buttons',
                            'text' => 'ต้องการดูข้อมูลโดยละเอียดหรือไม่?',
                            'actions' => [
                                [
                                    'type' => 'uri',
                                    'label' => 'ดูรายละเอียด',
                                    'uri' => $detail_url
                                ]
                            ]
                        ]
                    ];
                }
                
                // ส่งข้อความผ่าน LINE API
                $result = sendLineMessage($student['parent_line_id'], $messages, $channel_access_token);
                
                // คำนวณค่าใช้จ่าย
                $message_count = 1;
                $image_count = $include_chart && $chart_url ? 1 : 0;
                $link_count = $include_link ? 1 : 0;
                $total_cost = calculateCost($message_count, $image_count, $link_count);
                
                // บันทึกประวัติการส่งข้อความ
                $status = $result['success'] ? 'sent' : 'failed';
                $notification_id = logNotification(
                    $conn,
                    $student['parent_user_id'],
                    $personalized_message,
                    $status,
                    'attendance',
                    $result['success'] ? '' : $result['response']
                );
                
                // ส่งผลลัพธ์กลับ
                echo json_encode([
                    'success' => $result['success'],
                    'message' => $result['success'] ? 'ส่งข้อความเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาดในการส่งข้อความ',
                    'student_id' => $student_id,
                    'student_name' => $student['student_name'],
                    'parent_line_id' => $student['parent_line_id'],
                    'message_count' => $message_count + $image_count + $link_count,
                    'cost' => $total_cost,
                    'notification_id' => $notification_id,
                    'error' => $result['success'] ? '' : $result['response']
                ]);
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
                ]);
            }
            exit;
        }
        
        // ส่งข้อความกลุ่ม
        elseif (isset($data['action']) && $data['action'] === 'send_group') {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($data['student_ids']) || empty($data['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียนและข้อความ']);
                exit;
            }
            
            $student_ids = $data['student_ids'];
            $message = $data['message'];
            $start_date = $data['start_date'] ?? null;
            $end_date = $data['end_date'] ?? null;
            $include_chart = $data['include_chart'] ?? false;
            $include_link = $data['include_link'] ?? false;
            
            $results = [];
            $success_count = 0;
            $error_count = 0;
            $total_cost = 0;
            
            // ดึง Channel Access Token
            $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_access_token'");
            $stmt->execute();
            $channel_access_token = $stmt->fetchColumn();
            
            foreach ($student_ids as $student_id) {
                try {
                    // ดึงข้อมูลนักเรียน
                    $student = getStudentData($conn, $student_id);
                    if (!$student) {
                        $results[] = [
                            'student_id' => $student_id,
                            'success' => false,
                            'message' => 'ไม่พบข้อมูลนักเรียน',
                            'student_name' => "รหัส $student_id"
                        ];
                        $error_count++;
                        continue;
                    }
                    
                    // ตรวจสอบว่ามี LINE ID ของผู้ปกครองหรือไม่
                    if (empty($student['parent_line_id'])) {
                        $results[] = [
                            'student_id' => $student_id,
                            'success' => false,
                            'message' => 'ไม่พบ LINE ID ของผู้ปกครอง',
                            'student_name' => $student['student_name']
                        ];
                        $error_count++;
                        continue;
                    }
                    
                    // ดึงข้อมูลการเข้าแถว
                    $attendance = getAttendanceData($conn, $student_id, $start_date, $end_date);
                    
                    // แทนที่ตัวแปรในข้อความ
                    $data = [
                        'student_name' => $student['student_name'],
                        'class' => $student['class'],
                        'attendance_days' => $attendance['present_count'],
                        'total_days' => $attendance['total_days'],
                        'attendance_rate' => $attendance['attendance_rate'],
                        'absence_days' => $attendance['absent_count'],
                        'attendance_status' => $attendance['attendance_status'],
                        'advisor_name' => $student['advisor_name'],
                        'advisor_phone' => $student['advisor_phone']
                    ];
                    
                    $personalized_message = replaceTemplateVariables($message, $data);
                    
                    // สร้างข้อความที่จะส่ง
                    $messages = [];
                    
                    // เพิ่มข้อความพื้นฐาน
                    $messages[] = [
                        'type' => 'text',
                        'text' => $personalized_message
                    ];
                    
                    // สร้างกราฟและเพิ่มรูปภาพ (ถ้าต้องการ)
                    $chart_url = '';
                    if ($include_chart) {
                        $chart_url = generateAttendanceChart($conn, $student_id, $start_date, $end_date);
                        if ($chart_url) {
                            $messages[] = [
                                'type' => 'image',
                                'originalContentUrl' => $chart_url,
                                'previewImageUrl' => $chart_url
                            ];
                        }
                    }
                    
                    // สร้างลิงก์และเพิ่มปุ่ม (ถ้าต้องการ)
                    $detail_url = '';
                    if ($include_link) {
                        $detail_url = generateDetailUrl($student_id, $start_date, $end_date);
                        $messages[] = [
                            'type' => 'template',
                            'altText' => 'ดูรายละเอียดเพิ่มเติม',
                            'template' => [
                                'type' => 'buttons',
                                'text' => 'ต้องการดูข้อมูลโดยละเอียดหรือไม่?',
                                'actions' => [
                                    [
                                        'type' => 'uri',
                                        'label' => 'ดูรายละเอียด',
                                        'uri' => $detail_url
                                    ]
                                ]
                            ]
                        ];
                    }
                    
                    // ส่งข้อความผ่าน LINE API
                    $result = sendLineMessage($student['parent_line_id'], $messages, $channel_access_token);
                    
                    // คำนวณค่าใช้จ่าย
                    $message_count = 1;
                    $image_count = $include_chart && $chart_url ? 1 : 0;
                    $link_count = $include_link ? 1 : 0;
                    $cost = calculateCost($message_count, $image_count, $link_count);
                    $total_cost += $cost;
                    
                    // บันทึกประวัติการส่งข้อความ
                    $status = $result['success'] ? 'sent' : 'failed';
                    $notification_id = logNotification(
                        $conn,
                        $student['parent_user_id'],
                        $personalized_message,
                        $status,
                        'attendance',
                        $result['success'] ? '' : $result['response']
                    );
                    
                    // บันทึกผลลัพธ์
                    $results[] = [
                        'student_id' => $student_id,
                        'student_name' => $student['student_name'],
                        'success' => $result['success'],
                        'message_count' => $message_count + $image_count + $link_count,
                        'cost' => $cost,
                        'notification_id' => $notification_id,
                        'error' => $result['success'] ? '' : $result['response']
                    ];
                    
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                    
                } catch (Exception $e) {
                    $results[] = [
                        'student_id' => $student_id,
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                        'student_name' => isset($student) ? $student['student_name'] : "รหัส $student_id"
                    ];
                    $error_count++;
                }
            }
            
            // ส่งผลลัพธ์กลับ
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
        
        // ดึงข้อมูลนักเรียนที่มีความเสี่ยง
        elseif (isset($data['action']) && $data['action'] === 'get_at_risk_students') {
            $filters = [
                'class_level' => $data['class_level'] ?? '',
                'class_group' => $data['class_group'] ?? '',
                'risk_status' => $data['risk_status'] ?? '',
                'attendance_rate' => $data['attendance_rate'] ?? '',
                'student_name' => $data['student_name'] ?? ''
            ];
            
            $limit = $data['limit'] ?? 10;
            $offset = $data['offset'] ?? 0;
            
            $students = getAtRiskStudents($conn, $filters, $limit, $offset);
            $total = countAtRiskStudents($conn, $filters);
            
            echo json_encode([
                'success' => true,
                'students' => $students,
                'total' => $total
            ]);
            exit;
        }
        
        // ดึงประวัติการส่งข้อความ
        elseif (isset($data['action']) && $data['action'] === 'get_notification_history') {
            $student_id = $data['student_id'] ?? 0;
            $limit = $data['limit'] ?? 10;
            $offset = $data['offset'] ?? 0;
            
            if (!$student_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียน']);
                exit;
            }
            
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
                LIMIT ?, ?
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$student_id, $offset, $limit]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            exit;
        }
        
        // ดึงข้อมูลประวัติการส่งข้อความตาม ID
        elseif (isset($data['action']) && $data['action'] === 'get_notification_detail') {
            $notification_id = $data['notification_id'] ?? 0;
            
            if (!$notification_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสการแจ้งเตือน']);
                exit;
            }
            
            $query = "
                SELECT 
                    ln.*,
                    u.first_name,
                    u.last_name,
                    p.relationship,
                    s.student_id,
                    su.first_name as student_first_name,
                    su.last_name as student_last_name
                FROM 
                    line_notifications ln
                    JOIN users u ON ln.user_id = u.user_id
                    JOIN parents p ON u.user_id = p.user_id
                    JOIN parent_student_relation psr ON p.parent_id = psr.parent_id
                    JOIN students s ON psr.student_id = s.student_id
                    JOIN users su ON s.user_id = su.user_id
                WHERE 
                    ln.notification_id = ?
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$notification_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการแจ้งเตือน']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'notification' => $notification
            ]);
            exit;
        }
        
        // ส่งข้อความซ้ำ
        elseif (isset($data['action']) && $data['action'] === 'resend_notification') {
            $notification_id = $data['notification_id'] ?? 0;
            
            if (!$notification_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสการแจ้งเตือน']);
                exit;
            }
            
            $query = "
                SELECT 
                    ln.*,
                    u.line_id
                FROM 
                    line_notifications ln
                    JOIN users u ON ln.user_id = u.user_id
                WHERE 
                    ln.notification_id = ?
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$notification_id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการแจ้งเตือน']);
                exit;
            }
            
            if (empty($notification['line_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ไม่พบ LINE ID ของผู้รับ']);
                exit;
            }
            
            // ดึง Channel Access Token
            $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_access_token'");
            $stmt->execute();
            $channel_access_token = $stmt->fetchColumn();
            
            // สร้างข้อความที่จะส่ง
            $messages = [
                [
                    'type' => 'text',
                    'text' => $notification['message']
                ]
            ];
            
            // ส่งข้อความซ้ำ
            $result = sendLineMessage($notification['line_id'], $messages, $channel_access_token);
            
            // บันทึกประวัติการส่งข้อความใหม่
            $status = $result['success'] ? 'sent' : 'failed';
            $new_notification_id = logNotification(
                $conn,
                $notification['user_id'],
                $notification['message'],
                $status,
                $notification['notification_type'],
                $result['success'] ? '' : $result['response']
            );
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] ? 'ส่งข้อความซ้ำเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาดในการส่งข้อความซ้ำ',
                'notification_id' => $new_notification_id,
                'error' => $result['success'] ? '' : $result['response']
            ]);
            exit;
        }
    }
    elseif (isset($_POST['action'])) {
        // รองรับการส่งข้อมูลแบบ form-data
        
        // ดึงข้อมูลนักเรียนที่มีความเสี่ยง
        if ($_POST['action'] === 'get_at_risk_students') {
            $filters = [
                'class_level' => $_POST['class_level'] ?? '',
                'class_group' => $_POST['class_group'] ?? '',
                'risk_status' => $_POST['risk_status'] ?? '',
                'attendance_rate' => $_POST['attendance_rate'] ?? '',
                'student_name' => $_POST['student_name'] ?? ''
            ];
            
            $limit = $_POST['limit'] ?? 10;
            $offset = $_POST['offset'] ?? 0;
            
            $students = getAtRiskStudents($conn, $filters, $limit, $offset);
            $total = countAtRiskStudents($conn, $filters);
            
            echo json_encode([
                'success' => true,
                'students' => $students,
                'total' => $total
            ]);
            exit;
        }
    }
    else {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'Content-Type ไม่ถูกต้อง']);
        exit;
    }
}

// การเข้าถึงโดยตรงไม่ได้รับอนุญาต
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
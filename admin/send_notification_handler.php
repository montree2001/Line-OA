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
    
    // เมื่อใช้งานจริง ให้บันทึกข้อมูลการส่งไว้ในฐานข้อมูล
    error_log("LINE API Response: " . $response);
    
    return [
        'success' => ($status == 200),
        'response' => $response,
        'status' => $status
    ];
}

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
 * ฟังก์ชันสร้างภาพกราฟการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL ของรูปภาพกราฟ
 */
function generateAttendanceChart($student_id, $start_date = null, $end_date = null) {
    global $conn;
    
    // ดึงข้อมูลการเข้าแถว
    $attendance = getAttendanceData($conn, $student_id, $start_date, $end_date);
    
    // ดึงข้อมูลนักเรียน
    $student = getStudentData($conn, $student_id);
    
    // สร้างข้อมูลสำหรับกราฟ
    $chart_data = [
        'type' => 'line',
        'data' => [
            'labels' => ['1 เม.ย.', '8 เม.ย.', '15 เม.ย.', '22 เม.ย.', '29 เม.ย.'], // ตัวอย่างข้อมูล
            'datasets' => [
                [
                    'label' => 'อัตราการเข้าแถว (%)',
                    'data' => [65, 68, 70, 72, 75], // ตัวอย่างข้อมูล
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ],
        'options' => [
            'title' => [
                'display' => true,
                'text' => ['การเข้าแถวของ ' . $student['student_name'], 'ชั้น ' . $student['class']]
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
    
    // ในสถานการณ์จริง ควรใช้ QuickChart API หรือ GD Library
    // $chart_url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chart_data));
    
    // สำหรับตัวอย่าง
    $chart_url = 'https://example.com/charts/attendance_' . $student_id . '_' . time() . '.png';
    
    return $chart_url;
}

/**
 * ฟังก์ชันสร้าง URL ดูรายละเอียด
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL สำหรับดูรายละเอียด
 */
function generateDetailUrl($student_id, $start_date = null, $end_date = null) {
    $base_url = 'https://example.com/parents/attendance_detail.php';
    $url = $base_url . '?student_id=' . $student_id;
    
    if (!empty($start_date) && !empty($end_date)) {
        $url .= '&start_date=' . $start_date . '&end_date=' . $end_date;
    }
    
    return $url;
}

/**
 * ฟังก์ชันส่งข้อความแจ้งเตือนผ่าน LINE
 * 
 * @param string $line_id LINE ID ของผู้รับ
 * @param string $message ข้อความที่ต้องการส่ง
 * @param string $chart_url URL ของรูปภาพกราฟ (ถ้ามี)
 * @param string $detail_url URL สำหรับดูรายละเอียด (ถ้ามี)
 * @return array ผลลัพธ์การส่ง
 */
function sendNotification($line_id, $message, $chart_url = '', $detail_url = '') {
    // ดึง Access Token จากฐานข้อมูล
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_access_token'");
    $stmt->execute();
    $access_token = $stmt->fetchColumn();
    
    // สร้างข้อความที่จะส่ง
    $messages = [];
    
    // เพิ่มข้อความพื้นฐาน
    $messages[] = [
        'type' => 'text',
        'text' => $message
    ];
    
    // เพิ่มรูปภาพกราฟ (ถ้ามี)
    if (!empty($chart_url)) {
        $messages[] = [
            'type' => 'image',
            'originalContentUrl' => $chart_url,
            'previewImageUrl' => $chart_url
        ];
    }
    
    // เพิ่มปุ่มลิงก์ดูรายละเอียด (ถ้ามี)
    if (!empty($detail_url)) {
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
    return sendLineMessage($line_id, $messages, $access_token);
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
 * @return float ค่าใช้จ่ายรวม
 */
function calculateCost($messages = 1, $images = 0, $links = 0) {
    $message_cost = 0.075; // บาทต่อข้อความ
    $image_cost = 0.15; // บาทต่อรูปภาพ
    $link_cost = 0.075; // บาทต่อลิงก์
    
    return ($messages * $message_cost) + ($images * $image_cost) + ($links * $link_cost);
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
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $include_chart = (isset($_POST['include_chart']) && $_POST['include_chart'] === 'true');
        $include_link = (isset($_POST['include_link']) && $_POST['include_link'] === 'true');
        
        try {
            // ดึงข้อมูลนักเรียน
            $student = getStudentData($conn, $student_id);
            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
                exit;
            }
            
            // ตรวจสอบว่ามี LINE ID ของผู้ปกครองหรือไม่
            if (empty($student['parent_line_id'])) {
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
                'advisor_phone' => $student['advisor_phone'],
                'month' => date('m'),
                'year' => date('Y') + 543, // พ.ศ.
            ];
            
            $personalized_message = replaceTemplateVariables($message, $data);
            
            // สร้างกราฟและลิงก์ (ถ้าต้องการ)
            $chart_url = $include_chart ? generateAttendanceChart($student_id, $start_date, $end_date) : '';
            $detail_url = $include_link ? generateDetailUrl($student_id, $start_date, $end_date) : '';
            
            // ส่งข้อความ
            $result = sendNotification(
                $student['parent_line_id'],
                $personalized_message,
                $chart_url,
                $detail_url
            );
            
            // คำนวณค่าใช้จ่าย
            $message_count = 1;
            $image_count = $include_chart ? 1 : 0;
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
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
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
            echo json_encode(['success' => false, 'message' => 'รูปแบบรหัสนักเรียนไม่ถูกต้อง']);
            exit;
        }
        
        $message = $_POST['message'];
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $include_chart = (isset($_POST['include_chart']) && $_POST['include_chart'] === 'true');
        $include_link = (isset($_POST['include_link']) && $_POST['include_link'] === 'true');
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        $total_cost = 0;
        
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
                    'advisor_phone' => $student['advisor_phone'],
                    'month' => date('m'),
                    'year' => date('Y') + 543, // พ.ศ.
                ];
                
                $personalized_message = replaceTemplateVariables($message, $data);
                
                // สร้างกราฟและลิงก์ (ถ้าต้องการ)
                $chart_url = $include_chart ? generateAttendanceChart($student_id, $start_date, $end_date) : '';
                $detail_url = $include_link ? generateDetailUrl($student_id, $start_date, $end_date) : '';
                
                // ส่งข้อความ
                $result = sendNotification(
                    $student['parent_line_id'],
                    $personalized_message,
                    $chart_url,
                    $detail_url
                );
                
                // คำนวณค่าใช้จ่าย
                $message_count = 1;
                $image_count = $include_chart ? 1 : 0;
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
    
    // ดึงประวัติการส่งข้อความ
    if (isset($_POST['get_notification_history'])) {
        if (empty($_POST['student_id'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียน']);
            exit;
        }
        
        $student_id = $_POST['student_id'];
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        try {
            $stmt = $conn->prepare("
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
            ");
            $stmt->execute([$student_id, $limit]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ดึงข้อมูลนักเรียนที่มีความเสี่ยง
    if (isset($_POST['get_at_risk_students'])) {
        $class_level = $_POST['class_level'] ?? '';
        $class_group = $_POST['class_group'] ?? '';
        $risk_status = $_POST['risk_status'] ?? '';
        $attendance_rate = $_POST['attendance_rate'] ?? '';
        $student_name = $_POST['student_name'] ?? '';
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        try {
            // สร้างเงื่อนไขการค้นหา
            $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
            $params = [];
            
            if (!empty($class_level)) {
                $where_clauses[] = "c.level = ?";
                $params[] = $class_level;
            }
            
            if (!empty($class_group)) {
                $where_clauses[] = "c.group_number = ?";
                $params[] = $class_group;
            }
            
            if (!empty($risk_status)) {
                switch ($risk_status) {
                    case 'เสี่ยงตกกิจกรรม':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) < 60";
                        break;
                    case 'ต้องระวัง':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) BETWEEN 60 AND 80";
                        break;
                    case 'ปกติ':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) > 80";
                        break;
                }
            }
            
            if (!empty($attendance_rate)) {
                switch ($attendance_rate) {
                    case 'น้อยกว่า 70%':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) < 70";
                        break;
                    case '70% - 80%':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) BETWEEN 70 AND 80";
                        break;
                    case '80% - 90%':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) BETWEEN 80 AND 90";
                        break;
                    case 'มากกว่า 90%':
                        $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 ELSE 0 END) > 90";
                        break;
                }
            }
            
            if (!empty($student_name)) {
                $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
                $params[] = "%$student_name%";
                $params[] = "%$student_name%";
            }
            
            $where_clause = implode(" AND ", $where_clauses);
            
            // คำสั่ง SQL สำหรับดึงข้อมูลนักเรียน
            $sql = "
                SELECT 
                    s.student_id,
                    s.student_code,
                    s.title,
                    u.first_name,
                    u.last_name,
                    c.level,
                    c.group_number,
                    (
                        SELECT GROUP_CONCAT(CONCAT(pu.first_name, ' ', pu.last_name))
                        FROM parent_student_relation psr
                        JOIN parents p ON psr.parent_id = p.parent_id
                        JOIN users pu ON p.user_id = pu.user_id
                        WHERE psr.student_id = s.student_id
                    ) as parents_info,
                    sar.total_attendance_days,
                    sar.total_absence_days,
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN ROUND((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100)
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
            
            // เพิ่มพารามิเตอร์ limit และ offset
            $params[] = $offset;
            $params[] = $limit;
            
            $stmt = $conn->prepare($sql);
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
            
            // นับจำนวนนักเรียนทั้งหมด
            $count_sql = "
                SELECT COUNT(*) as total
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
            
            // ลบพารามิเตอร์ limit และ offset
            array_pop($params);
            array_pop($params);
            
            $stmt = $conn->prepare($count_sql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'students' => $students,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// การเข้าถึงโดยตรงไม่ได้รับอนุญาต
http_response_code(403);
echo json_encode(['success' => false, 'message' => 'การเข้าถึงโดยตรงไม่ได้รับอนุญาต']);
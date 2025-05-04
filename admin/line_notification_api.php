<?php
/**
 * line_notification_api.php - API สำหรับส่งข้อความแจ้งเตือนผ่าน LINE Messaging API
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

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
 * ฟังก์ชันส่งข้อความพร้อมรูปภาพและลิงก์
 * 
 * @param string $to LINE User ID ของผู้รับ
 * @param string $text ข้อความที่ต้องการส่ง
 * @param string $image_url URL ของรูปภาพ (ถ้ามี)
 * @param string $detail_url URL ของลิงก์ดูรายละเอียด (ถ้ามี)
 * @param string $channel_access_token Access Token ของ LINE Channel
 * @return array ผลลัพธ์การส่ง
 */
function sendAttendanceNotification($to, $text, $image_url = '', $detail_url = '', $channel_access_token = '') {
    $conn = getDB();
    
    // ถ้าไม่มี Access Token ให้ดึงจากฐานข้อมูล
    if (empty($channel_access_token)) {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_access_token'");
        $stmt->execute();
        $channel_access_token = $stmt->fetchColumn();
    }
    
    // สร้างข้อความที่จะส่ง
    $messages = [];
    
    // เพิ่มข้อความหลัก
    $messages[] = [
        'type' => 'text',
        'text' => $text
    ];
    
    // เพิ่มรูปภาพ (ถ้ามี)
    if (!empty($image_url)) {
        $messages[] = [
            'type' => 'image',
            'originalContentUrl' => $image_url,
            'previewImageUrl' => $image_url
        ];
    }
    
    // เพิ่มปุ่มลิงก์ (ถ้ามี)
    if (!empty($detail_url)) {
        $messages[] = [
            'type' => 'template',
            'altText' => 'ดูรายละเอียดเพิ่มเติม',
            'template' => [
                'type' => 'buttons',
                'text' => 'ต้องการดูข้อมูลการเข้าแถวโดยละเอียดหรือไม่?',
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
    
    // ส่งข้อความ
    $result = sendLineMessage($to, $messages, $channel_access_token);
    
    // บันทึกประวัติการส่ง
    $stmt = $conn->prepare("
        INSERT INTO line_notifications (
            user_id, message, status, notification_type, error_message
        ) VALUES (
            (SELECT user_id FROM users WHERE line_id = ?),
            ?,
            ?,
            'attendance',
            ?
        )
    ");
    
    $status = $result['success'] ? 'sent' : 'failed';
    $error_message = $result['success'] ? '' : $result['response'];
    
    $stmt->execute([$to, $text, $status, $error_message]);
    
    return $result;
}

/**
 * ฟังก์ชันสร้างรูปภาพกราฟการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL ของรูปภาพกราฟที่สร้าง
 */
function generateAttendanceChart($student_id, $start_date = null, $end_date = null) {
    $conn = getDB();
    
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
        $dates = ['01/05', '08/05', '15/05', '22/05', '29/05'];
        $attendance_rates = [65, 68, 70, 72, 75];
    }
    
    // สร้างหัวข้อกราฟ
    $chart_title = 'การเข้าแถวของ ' . $student_data['title'] . ' ' . $student_data['first_name'] . ' ' . $student_data['last_name'];
    $chart_subtitle = 'ชั้น ' . $student_data['level'] . '/' . $student_data['group_number'] . ' วันที่ ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
    
    // ในทางปฏิบัติจริง นี่จะเป็นการสร้างรูปภาพกราฟด้วย library เช่น GD หรือ QuickChart API
    // แต่ในตัวอย่างนี้ เราจะจำลองการสร้าง URL ของรูปภาพ
    
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
    
    // QuickChart API URL (ในทางปฏิบัติจริง)
    // $chart_url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chart_data));
    
    // สำหรับตัวอย่าง ใช้ URL ทั่วไป
    $chart_url = 'https://chart.example.com/attendance_' . $student_id . '_' . time() . '.png';
    
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
    $base_url = 'https://student-prasat.example.com/parents/attendance_detail.php';
    $url = $base_url . '?student_id=' . $student_id;
    
    if (!empty($start_date) && !empty($end_date)) {
        $url .= '&start_date=' . $start_date . '&end_date=' . $end_date;
    }
    
    return $url;
}

// ทดสอบการส่งข้อความแจ้งเตือนผ่าน LINE API หากมีพารามิเตอร์ test=1
if (isset($_GET['test']) && $_GET['test'] == 1) {
    // ตรวจสอบ LINE User ID
    if (empty($_GET['line_id'])) {
        echo json_encode(['success' => false, 'message' => 'กรุณาระบุ LINE User ID']);
        exit;
    }
    
    // สร้างข้อความทดสอบ
    $test_message = "ทดสอบการส่งข้อความแจ้งเตือนจากระบบ STUDENT-Prasat\n\nนี่เป็นข้อความทดสอบเพื่อตรวจสอบการทำงานของระบบแจ้งเตือนผ่าน LINE Messaging API\n\nขอบคุณสำหรับการทดสอบ";
    
    // ส่งข้อความทดสอบ
    $result = sendAttendanceNotification($_GET['line_id'], $test_message);
    
    // แสดงผลลัพธ์
    echo json_encode($result);
    exit;
}

// กรณีเรียกใช้งานโดยตรง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ Content-Type
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        http_response_code(415);
        echo json_encode(['success' => false, 'message' => 'Content-Type ต้องเป็น application/json']);
        exit;
    }
    
    // รับข้อมูล JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($data['line_id']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'กรุณาระบุ LINE User ID และข้อความ']);
        exit;
    }
    
    // ตรวจสอบข้อมูลเพิ่มเติม
    $image_url = $data['image_url'] ?? '';
    $detail_url = $data['detail_url'] ?? '';
    
    // ส่งข้อความ
    $result = sendAttendanceNotification($data['line_id'], $data['message'], $image_url, $detail_url);
    
    // แสดงผลลัพธ์
    echo json_encode($result);
    exit;
}

// กรณีเรียกผ่าน URL โดยตรงโดยไม่มีพารามิเตอร์ test
echo json_encode(['success' => false, 'message' => 'การเข้าถึงโดยตรงไม่ได้รับอนุญาต']);
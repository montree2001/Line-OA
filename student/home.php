<?php
/**
 * home.php - หน้าหลักสำหรับนักเรียนหลังจากล็อกอิน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตั้งค่าการแสดงข้อผิดพลาดสำหรับการพัฒนา
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลนักเรียน
try {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // ดึงค่าตั้งค่าระบบสำหรับเวลาเช็คชื่อ
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time_setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $attendance_end_time = $end_time_setting['setting_value'] ?? '08:30';
    
    // ดึงข้อมูลการเข้าแถวของวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student['student_id'], $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    // ดึงจำนวนวันที่ต้องเข้าแถว
    $required_attendance_days = $academic_year['required_attendance_days'] ?? 80;
    
    // ดึงสถิติการเข้าแถว
    $stmt = $conn->prepare("
        SELECT total_attendance_days, total_absence_days, passed_activity
        FROM student_academic_records
        WHERE student_id = ? AND academic_year_id = ?
    ");
    $stmt->execute([$student['student_id'], $current_academic_year_id]);
    $attendance_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณเปอร์เซ็นต์การเข้าแถวโดยใช้จำนวนวันที่ต้องเข้าแถวเป็นตัวตั้ง
    $total_attended_days = $attendance_record['total_attendance_days'] ?? 0;
    $attendance_percentage = $required_attendance_days > 0 
        ? round(($total_attended_days / $required_attendance_days) * 100, 1) 
        : 0;
    
    // จำกัดค่าสูงสุดที่ 100%
    $attendance_percentage = min($attendance_percentage, 100);
    
    // ดึงประวัติการเช็คชื่อล่าสุด 5 รายการ
    $stmt = $conn->prepare("
        SELECT a.*, DATE_FORMAT(a.date, '%d-%m-%Y') as formatted_date
        FROM attendance a
        WHERE a.student_id = ?
        ORDER BY a.date DESC, a.check_time DESC
        LIMIT 5
    ");
    $stmt->execute([$student['student_id']]);
    $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประวัติการเช็คชื่อ
    $check_in_history = [];
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    foreach ($recent_attendance as $record) {
        $date_parts = explode('-', $record['date']);
        if (count($date_parts) === 3) {
            $day = ltrim($date_parts[2], '0');
            $month = $thai_months[$date_parts[1]] ?? '';
            
            $status_class = '';
            switch($record['attendance_status']) {
                case 'present':
                    $status_class = 'present';
                    break;
                case 'late':
                    $status_class = 'present'; // ใช้คลาสเดียวกับ present แต่ข้อความต่างกัน
                    break;
                case 'absent':
                case 'leave':
                default:
                    $status_class = 'absent';
                    break;
            }
            
            $check_in_history[] = [
                'day' => $day,
                'month' => $month,
                'time' => date('H:i', strtotime($record['check_time'])),
                'status' => $status_class,
                'status_text' => getStatusText($record['attendance_status']),
                'method' => mapCheckMethod($record['check_method']),
                'method_icon' => getMethodIcon($record['check_method'])
            ];
        }
    }
    
    // กรณีไม่มีประวัติ
    if (empty($check_in_history)) {
        // เพิ่มข้อมูลตัวอย่าง (ในกรณีที่ยังไม่มีประวัติ)
        $current_date = new DateTime();
        for ($i = 0; $i < 3; $i++) {
            $date = clone $current_date;
            $date->modify("-$i day");
            
            $check_in_history[] = [
                'day' => $date->format('d'),
                'month' => $thai_months[$date->format('m')],
                'time' => '08:00',
                'status' => 'present',
                'status_text' => 'เข้าแถว',
                'method' => 'เช็คชื่อผ่าน GPS',
                'method_icon' => 'gps_fixed'
            ];
        }
    }
    
    // ดึงประกาศล่าสุด 3 รายการจากฐานข้อมูล
    $announcements = [];
    try {
        // ดึงข้อมูลแผนก/ระดับชั้นของนักเรียน
        $stmt = $conn->prepare("
            SELECT c.department_id, c.level
            FROM students s
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.student_id = ?
        ");
        $stmt->execute([$student['student_id']]);
        $student_class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // เตรียมค่า dept_id และ level
        $dept_id = isset($student_class['department_id']) ? $student_class['department_id'] : 0;
        $level = isset($student_class['level']) ? $student_class['level'] : '';
        
        // ดึงประกาศที่เกี่ยวข้องกับนักเรียน
        $stmt = $conn->prepare("
            SELECT a.announcement_id, a.title, a.content, a.type, a.created_at, 
                   u.first_name, u.last_name, u.title as user_title
            FROM announcements a
            LEFT JOIN users u ON a.created_by = u.user_id
            WHERE a.status = 'active' 
            AND (a.is_all_targets = 1 OR (a.target_department = ? OR a.target_level = ?))
            ORDER BY a.created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$dept_id, $level]);
        $db_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($db_announcements)) {
            foreach ($db_announcements as $item) {
                // จัดรูปแบบวันที่ในรูปแบบไทย
                $created_date = new DateTime($item['created_at']);
                $thai_date = $created_date->format('d') . ' ' . 
                             $thai_months[$created_date->format('m')] . ' ' .
                             ($created_date->format('Y') + 543);
                
                // กำหนดประเภทแบดจ์ตามประเภทประกาศ
                $badge_type = '';
                $badge_text = '';
                switch ($item['type']) {
                    case 'urgent':
                        $badge_type = 'urgent';
                        $badge_text = 'ด่วน';
                        break;
                    case 'event':
                        $badge_type = 'event';
                        $badge_text = 'กิจกรรม';
                        break;
                    case 'academic':
                        $badge_type = 'info';
                        $badge_text = 'วิชาการ';
                        break;
                    default:
                        $badge_type = 'info';
                        $badge_text = 'ข่าวสาร';
                }
                
                // ตัดข้อความให้สั้นลง
                $short_content = mb_substr(strip_tags($item['content']), 0, 100, 'UTF-8');
                if (mb_strlen($item['content'], 'UTF-8') > 100) {
                    $short_content .= '...';
                }
                
                // กำหนดชื่อผู้สร้างประกาศ
                $creator_name = '';
                if (!empty($item['user_title']) && !empty($item['first_name']) && !empty($item['last_name'])) {
                    $creator_name = $item['user_title'] . $item['first_name'] . ' ' . $item['last_name'];
                } else {
                    $creator_name = 'เจ้าหน้าที่วิทยาลัย';
                }
                
                $announcements[] = [
                    'id' => $item['announcement_id'],
                    'title' => $item['title'],
                    'content' => $short_content,
                    'date' => $thai_date,
                    'badge' => $badge_type,
                    'badge_text' => $badge_text
                ];
            }
        }
    } catch (PDOException $e) {
        // หากมีข้อผิดพลาดในการดึงประกาศ ใช้ข้อมูลตัวอย่างแทน
        $announcements = [
            [
                'id' => 'sample-1',
                'title' => 'ประกาศงดกิจกรรมหน้าเสาธง',
                'content' => 'เนื่องจากสภาพอากาศไม่เอื้ออำนวย จึงงดกิจกรรมหน้าเสาธงในวันที่ 1-3 เมษายน 2568 นักเรียนสามารถเช็คชื่อผ่านระบบได้ตามปกติ',
                'date' => date('d') . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543),
                'badge' => 'info',
                'badge_text' => 'ประกาศ'
            ],
            [
                'id' => 'sample-2',
                'title' => 'การแข่งขันกีฬาสีประจำปี 2568',
                'content' => 'ขอเชิญนักเรียนทุกคนเข้าร่วมการแข่งขันกีฬาสีประจำปี 2568 ในวันที่ 10-12 เมษายน 2568',
                'date' => (date('d')-2) . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543),
                'badge' => 'event',
                'badge_text' => 'กิจกรรม'
            ],
            [
                'id' => 'sample-3',
                'title' => 'เตือนนักเรียนที่ยังไม่ผ่านกิจกรรม',
                'content' => 'ขอให้นักเรียนที่มีอัตราการเข้าแถวต่ำกว่า 75% ติดต่อครูที่ปรึกษาโดยด่วน',
                'date' => (date('d')-5) . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543),
                'badge' => 'urgent',
                'badge_text' => 'สำคัญ'
            ]
        ];
    }

    // แสดงหน้าเว็บ
    $page_title = "STD-Prasat - หน้าหลักนักเรียน";
    
    // สร้างข้อมูลสำหรับแสดงผล
    $student_info = [
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number'],
        'profile_picture' => $student['profile_picture'],
        'student_code' => $student['student_code']
    ];
    
    // สร้างตัวอักษรแรกของชื่อสำหรับใช้แสดงในกรณีไม่มีรูปโปรไฟล์
    $first_char = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
    $attendance_stats = [
        'total_days' => $total_attended_days + ($attendance_record['total_absence_days'] ?? 0),
        'attendance_days' => $total_attended_days,
        'attendance_percentage' => $attendance_percentage,
        'required_days' => $required_attendance_days  // จำนวนวันที่ต้องเข้าแถว
    ];
    
    $attendance_status = [
        'is_checked_in' => !empty($today_attendance),
        'status_text' => !empty($today_attendance) ? 'เข้าแถวแล้ววันนี้' : 'ยังไม่ได้เข้าแถววันนี้',
        'status_class' => !empty($today_attendance) ? 'status-present' : 'status-absent',
        'status_icon' => !empty($today_attendance) ? 'check_circle' : 'cancel'
    ];
    
    $alert = [
        'title' => 'แจ้งเตือนเวลาเช็คชื่อ',
        'message' => "เช็คชื่อเข้าแถวได้ถึงเวลา $attendance_end_time น. เท่านั้น"
    ];
    
    $extra_css = ['assets/css/student-home.css'];
    $extra_js = ['assets/js/student-home.js'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/student_home_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}

// ฟังก์ชันแปลงรูปแบบการเช็คชื่อ
function mapCheckMethod($method) {
    switch ($method) {
        case 'GPS':
            return 'เช็คชื่อผ่าน GPS';
        case 'QR_Code':
            return 'เช็คชื่อด้วย QR Code';
        case 'PIN':
            return 'เช็คชื่อด้วยรหัส PIN';
        case 'Manual':
            return 'ครูเช็คชื่อให้';
        default:
            return 'ไม่ระบุ';
    }
}

// ฟังก์ชันกำหนดไอคอนตามวิธีการเช็คชื่อ
function getMethodIcon($method) {
    switch ($method) {
        case 'GPS':
            return 'gps_fixed';
        case 'QR_Code':
            return 'qr_code_scanner';
        case 'PIN':
            return 'pin';
        case 'Manual':
            return 'person';
        default:
            return 'help_outline';
    }
}

// ฟังก์ชันแปลงสถานะการเช็คชื่อเป็นข้อความ
function getStatusText($status) {
    switch ($status) {
        case 'present':
            return 'เข้าแถว';
        case 'absent':
            return 'ขาดแถว';
        case 'late':
            return 'มาสาย';
        case 'leave':
            return 'ลา';
        default:
            return 'ไม่ระบุ';
    }
}
?>
<?php
/**
 * history.php - หน้าประวัติการเข้าแถวสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ลดการแสดงข้อผิดพลาดในหน้าเว็บ แต่เก็บไว้ในล็อก
error_reporting(0);
ini_set('display_errors', 0);

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

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'history';
$page_title = 'STD-Prasat - ประวัติการเข้าแถว';
$page_header = 'ประวัติการเข้าแถว';

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
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
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // แปลงข้อมูลจากฐานข้อมูลเป็นรูปแบบที่ใช้ในหน้าเว็บ
    $student_id = $student_data['student_id'];
    $class_info = $student_data['level'] . '/' . $student_data['group_number'];
    $first_char = mb_substr($student_data['first_name'], 0, 1, 'UTF-8');
    
    $student_info = [
        'name' => $student_data['first_name'] . ' ' . $student_data['last_name'],
        'class' => $class_info,
        'number' => $student_data['student_code'] ?? 'ไม่ระบุ',
        'avatar' => $first_char,
        'profile_image' => $student_data['profile_picture']
    ];
    
    // ดึงปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id, year as academic_year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $academic_year ? $academic_year['academic_year_id'] : null;
    $academic_year_text = $academic_year ? $academic_year['academic_year'] : (date('Y') + 543);
    
    // ดึงข้อมูลการเข้าแถวประจำเดือนปัจจุบัน
    $current_month = date('m');
    $current_year = date('Y');
    
    // ดึงชื่อเดือนไทย
    $thai_months = [
        '01' => 'ม.ค.',
        '02' => 'ก.พ.',
        '03' => 'มี.ค.',
        '04' => 'เม.ย.',
        '05' => 'พ.ค.',
        '06' => 'มิ.ย.',
        '07' => 'ก.ค.',
        '08' => 'ส.ค.',
        '09' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    ];
    $current_month_name = $thai_months[date('m')];
    $current_year_thai = (int)date('Y') + 543;
    
    if ($academic_year_id) {
        // ดึงข้อมูลการเข้าแถวของเดือนปัจจุบัน
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_days, 
                   SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days
            FROM attendance
            WHERE student_id = ? 
              AND academic_year_id = ?
              AND MONTH(date) = ? 
              AND YEAR(date) = ?
        ");
        $stmt->execute([$student_id, $academic_year_id, $current_month, $current_year]);
        $monthly_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลเกณฑ์การผ่านกิจกรรม
        $stmt = $conn->prepare("
            SELECT setting_value FROM system_settings 
            WHERE setting_key = 'min_attendance_rate'
        ");
        $stmt->execute();
        $min_percentage = $stmt->fetch(PDO::FETCH_ASSOC);
        $min_percentage = $min_percentage ? floatval($min_percentage['setting_value']) : 80.0;
        
        // คำนวณข้อมูลสรุป
        $total_days = $monthly_data ? intval($monthly_data['total_days']) : 0;
        $present_days = $monthly_data ? intval($monthly_data['present_days']) : 0;
        $absent_days = $total_days - $present_days;
        
        // คำนวณเปอร์เซ็นต์การเข้าแถว
        $attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;
        
        // คำนวณคะแนนความสม่ำเสมอ
        $regularity_score = $attendance_percentage;
        
        // ตรวจสอบความเสี่ยงตกกิจกรรม
        $is_at_risk = $attendance_percentage < $min_percentage;
        
        $monthly_summary = [
            'total_days' => $total_days,
            'present_days' => $present_days,
            'absent_days' => $absent_days,
            'attendance_percentage' => $attendance_percentage,
            'regularity_score' => $regularity_score,
            'is_at_risk' => $is_at_risk,
            'min_percentage' => $min_percentage
        ];
    } else {
        // ข้อมูลสรุปตัวอย่าง (ใช้ในกรณีไม่มีฐานข้อมูล)
        $monthly_summary = [
            'total_days' => 23,
            'present_days' => 23,
            'absent_days' => 0,
            'attendance_percentage' => 100,
            'regularity_score' => 97,
            'is_at_risk' => false,
            'min_percentage' => 80
        ];
    }
    
    // ดึงข้อมูลสถิติย้อนหลัง 6 เดือน
    $attendance_chart = [];
    
    if ($academic_year_id) {
        // ดึงข้อมูลการเข้าแถวย้อนหลัง 6 เดือน
        for ($i = 0; $i < 6; $i++) {
            $month = date('m', strtotime("-$i month"));
            $year = date('Y', strtotime("-$i month"));
            
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_days, 
                       SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as present_days
                FROM attendance
                WHERE student_id = ? 
                  AND academic_year_id = ?
                  AND MONTH(date) = ? 
                  AND YEAR(date) = ?
            ");
            $stmt->execute([$student_id, $academic_year_id, $month, $year]);
            $chart_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $chart_total = $chart_data ? intval($chart_data['total_days']) : 0;
            $chart_present = $chart_data ? intval($chart_data['present_days']) : 0;
            $chart_percentage = $chart_total > 0 ? round(($chart_present / $chart_total) * 100) : 0;
            
            $attendance_chart[] = [
                'month' => $thai_months[$month],
                'percentage' => $chart_percentage,
                'total_days' => $chart_total,
                'present_days' => $chart_present
            ];
        }
        
        // กลับลำดับให้เป็นจากเดือนเก่าไปใหม่
        $attendance_chart = array_reverse($attendance_chart);
    } else {
        // ข้อมูลกราฟตัวอย่าง (ใช้ในกรณีไม่มีฐานข้อมูล)
        $attendance_chart = [
            ['month' => 'ต.ค.', 'percentage' => 85, 'total_days' => 20, 'present_days' => 17],
            ['month' => 'พ.ย.', 'percentage' => 90, 'total_days' => 22, 'present_days' => 20],
            ['month' => 'ธ.ค.', 'percentage' => 80, 'total_days' => 15, 'present_days' => 12],
            ['month' => 'ม.ค.', 'percentage' => 95, 'total_days' => 20, 'present_days' => 19],
            ['month' => 'ก.พ.', 'percentage' => 90, 'total_days' => 20, 'present_days' => 18],
            ['month' => 'มี.ค.', 'percentage' => 100, 'total_days' => 23, 'present_days' => 23]
        ];
    }
    
    // ดึงข้อมูลประวัติการเข้าแถว
    if ($academic_year_id) {
        $stmt = $conn->prepare("
            SELECT a.date, a.is_present, a.check_method, a.check_time 
            FROM attendance a
            WHERE a.student_id = ? 
              AND a.academic_year_id = ?
            ORDER BY a.date DESC
            LIMIT 15
        ");
        $stmt->execute([$student_id, $academic_year_id]);
        $history_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $check_in_history = [];
        foreach ($history_data as $entry) {
            $date_obj = new DateTime($entry['date']);
            $day = $date_obj->format('d');
            $month = $thai_months[$date_obj->format('m')];
            $year = (int)$date_obj->format('Y') + 543; // แปลงเป็นปี พ.ศ.
            
            $check_time = $entry['check_time'] ? date('H:i', strtotime($entry['check_time'])) : '00:00';
            
            $check_in_history[] = [
                'date' => $day . ' ' . $month . ' ' . $year,
                'time' => $check_time,
                'status' => $entry['is_present'] ? 'present' : 'absent',
                'method' => mapCheckMethod($entry['check_method'])
            ];
        }
    } else {
        // ข้อมูลประวัติตัวอย่าง (ใช้ในกรณีไม่มีฐานข้อมูล)
        $check_in_history = [
            [
                'date' => '17 มี.ค. 2568',
                'time' => '07:45',
                'status' => 'present',
                'method' => 'GPS'
            ],
            [
                'date' => '14 มี.ค. 2568',
                'time' => '07:40',
                'status' => 'present',
                'method' => 'PIN'
            ],
            [
                'date' => '13 มี.ค. 2568',
                'time' => '07:38',
                'status' => 'present',
                'method' => 'QR Code'
            ],
            [
                'date' => '12 มี.ค. 2568',
                'time' => '07:42',
                'status' => 'present',
                'method' => 'GPS'
            ],
            [
                'date' => '11 มี.ค. 2568',
                'time' => '07:36',
                'status' => 'present',
                'method' => 'PIN'
            ],
            [
                'date' => '10 มี.ค. 2568',
                'time' => '07:41',
                'status' => 'present',
                'method' => 'QR Code'
            ],
            [
                'date' => '7 มี.ค. 2568',
                'time' => '07:39',
                'status' => 'present',
                'method' => 'GPS'
            ]
        ];
    }
    
    // สร้างข้อมูลปฏิทิน
    $current_month_year = $current_month_name . ' ' . $current_year_thai;
    
    $first_day = new DateTime(date('Y-m-01'));
    $last_day = new DateTime(date('Y-m-t'));
    $first_day_of_week = (int)$first_day->format('N'); // 1 (จันทร์) ถึง 7 (อาทิตย์)
    $total_days_in_month = (int)$last_day->format('d');
    
    // ปรับให้วันอาทิตย์เป็น 0 (ตามข้อกำหนดของ JavaScript)
    $first_day_of_week = $first_day_of_week % 7;
    
    // ดึงข้อมูลการเข้าแถวของเดือนปัจจุบัน
    $calendar_dates = [];
    if ($academic_year_id) {
        $stmt = $conn->prepare("
            SELECT DAY(date) as day, is_present 
            FROM attendance
            WHERE student_id = ? 
              AND academic_year_id = ?
              AND MONTH(date) = ? 
              AND YEAR(date) = ?
        ");
        $stmt->execute([$student_id, $academic_year_id, $current_month, $current_year]);
        $calendar_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงเป็น associative array เพื่อง่ายต่อการค้นหา
        $attendance_by_day = [];
        foreach ($calendar_attendance as $entry) {
            $attendance_by_day[$entry['day']] = $entry['is_present'] ? 'present' : 'absent';
        }
    } else {
        // ข้อมูลตัวอย่าง
        $attendance_by_day = [
            3 => 'present',
            4 => 'present',
            5 => 'present',
            6 => 'present',
            7 => 'present',
            10 => 'present',
            11 => 'present',
            12 => 'present',
            13 => 'present',
            14 => 'present',
            17 => 'present'
        ];
    }
    
    // คำนวณข้อมูลสำหรับปฏิทิน
    // วันของเดือนก่อนหน้า
    $prev_month_days = $first_day_of_week;
    $prev_month_last_day = (int)(new DateTime(date('Y-m-01')))->modify('-1 day')->format('d');
    
    for ($i = $prev_month_days - 1; $i >= 0; $i--) {
        $day_num = $prev_month_last_day - $i;
        $calendar_dates[] = [
            'day' => $day_num,
            'status' => 'other-month',
            'date' => date('Y-m-d', strtotime(date('Y-m-01') . " -" . ($i + 1) . " days"))
        ];
    }
    
    // วันของเดือนปัจจุบัน
    $current_date = date('d');
    for ($i = 1; $i <= $total_days_in_month; $i++) {
        $status = isset($attendance_by_day[$i]) ? $attendance_by_day[$i] : '';
        $is_today = ($i == $current_date) ? 'today' : '';
        
        $calendar_dates[] = [
            'day' => $i,
            'status' => $status,
            'is_today' => $is_today,
            'date' => date('Y-m-') . sprintf('%02d', $i)
        ];
    }
    
    // วันของเดือนถัดไป
    $next_month_days = 42 - (count($calendar_dates)); // 42 คือจำนวนช่องทั้งหมดในปฏิทิน (6 แถว x 7 วัน)
    
    for ($i = 1; $i <= $next_month_days; $i++) {
        $calendar_dates[] = [
            'day' => $i,
            'status' => 'other-month',
            'date' => date('Y-m-d', strtotime(date('Y-m-01') . " +" . ($total_days_in_month + $i - 1) . " days"))
        ];
    }
    
} catch (PDOException $e) {
    // กรณีมีข้อผิดพลาดในการดึงข้อมูล ใช้ข้อมูลตัวอย่าง
    error_log("Database error in history.php: " . $e->getMessage());
    
    // ข้อมูลนักเรียนตัวอย่าง
    $student_info = [
        'name' => 'นายเอกชัย รักเรียน',
        'class' => 'ม.6/1',
        'number' => 15,
        'avatar' => 'อ',
        'profile_image' => null
    ];
    
    // สรุปการเข้าแถวประจำเดือน
    $monthly_summary = [
        'total_days' => 23,
        'present_days' => 23,
        'absent_days' => 0,
        'attendance_percentage' => 100,
        'regularity_score' => 97,
        'is_at_risk' => false,
        'min_percentage' => 80
    ];
    
    // กราฟข้อมูลการเข้าแถว
    $attendance_chart = [
        ['month' => 'ต.ค.', 'percentage' => 85, 'total_days' => 20, 'present_days' => 17],
        ['month' => 'พ.ย.', 'percentage' => 90, 'total_days' => 22, 'present_days' => 20],
        ['month' => 'ธ.ค.', 'percentage' => 80, 'total_days' => 15, 'present_days' => 12],
        ['month' => 'ม.ค.', 'percentage' => 95, 'total_days' => 20, 'present_days' => 19],
        ['month' => 'ก.พ.', 'percentage' => 90, 'total_days' => 20, 'present_days' => 18],
        ['month' => 'มี.ค.', 'percentage' => 100, 'total_days' => 23, 'present_days' => 23]
    ];
    
    // ประวัติการเข้าแถว
    $check_in_history = [
        [
            'date' => '17 มี.ค. 2568',
            'time' => '07:45',
            'status' => 'present',
            'method' => 'GPS'
        ],
        [
            'date' => '14 มี.ค. 2568',
            'time' => '07:40',
            'status' => 'present',
            'method' => 'PIN'
        ],
        [
            'date' => '13 มี.ค. 2568',
            'time' => '07:38',
            'status' => 'present',
            'method' => 'QR Code'
        ],
        [
            'date' => '12 มี.ค. 2568',
            'time' => '07:42',
            'status' => 'present',
            'method' => 'GPS'
        ],
        [
            'date' => '11 มี.ค. 2568',
            'time' => '07:36',
            'status' => 'present',
            'method' => 'PIN'
        ],
        [
            'date' => '10 มี.ค. 2568',
            'time' => '07:41',
            'status' => 'present',
            'method' => 'QR Code'
        ],
        [
            'date' => '7 มี.ค. 2568',
            'time' => '07:39',
            'status' => 'present',
            'method' => 'GPS'
        ]
    ];
    
    // ข้อมูลปฏิทิน
    $current_month_name = 'มีนาคม';
    $current_year_thai = 2568;
    $current_month_year = $current_month_name . ' ' . $current_year_thai;
    
    $calendar_dates = [];
    $attendance_by_day = [
        3 => 'present',
        4 => 'present',
        5 => 'present',
        6 => 'present',
        7 => 'present',
        10 => 'present',
        11 => 'present',
        12 => 'present',
        13 => 'present',
        14 => 'present',
        17 => 'present'
    ];
    
    // สร้างข้อมูลตัวอย่างสำหรับแสดงผลปฏิทิน
    for ($i = 0; $i < 3; $i++) {
        $calendar_dates[] = ['day' => 28 + $i, 'status' => 'other-month'];
    }
    
    for ($i = 1; $i <= 31; $i++) {
        $status = isset($attendance_by_day[$i]) ? $attendance_by_day[$i] : '';
        $is_today = ($i == 17) ? 'today' : '';
        $calendar_dates[] = ['day' => $i, 'status' => $status, 'is_today' => $is_today];
    }
    
    for ($i = 1; $i <= 8; $i++) {
        $calendar_dates[] = ['day' => $i, 'status' => 'other-month'];
    }
}

// รวม CSS และ JS
$extra_css = [
    'assets/css/student-report.css'
];
$extra_js = [
    'assets/js/student-report.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/student_report_content.php';

// ฟังก์ชันแปลงรูปแบบการเช็คชื่อ
function mapCheckMethod($method) {
    switch ($method) {
        case 'GPS':
            return 'GPS';
        case 'QR_Code':
            return 'QR Code';
        case 'PIN':
            return 'PIN';
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

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
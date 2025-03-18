<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าแสดงรายชื่อนักเรียนในความดูแลของผู้ปกครอง
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน (ในการใช้งานจริงควรมีการตรวจสอบการล็อกอินผ่าน LINE)
/* if (!isset($_SESSION['parent_id']) && !isset($_GET['debug'])) {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: login.php');
    exit;
}
 */
// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - นักเรียนในความดูแล';
$current_page = 'students';
$extra_css = [
    'assets/css/parent-students.css'
];
$extra_js = [
    'assets/js/parent-students.js'
];

// ในการใช้งานจริงควรมีการดึงข้อมูลจากฐานข้อมูล
// ตัวอย่างการดึงข้อมูลนักเรียนในความดูแลของผู้ปกครอง
$parent_id = isset($_SESSION['parent_id']) ? $_SESSION['parent_id'] : 1;

// สร้างฟังก์ชันจำลองการดึงข้อมูลนักเรียน (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getStudents($parent_id) {
    // ในการใช้งานจริงควรใช้คำสั่ง SQL เพื่อดึงข้อมูลจากฐานข้อมูล
    // ตัวอย่าง: SELECT * FROM students WHERE parent_id = :parent_id

    // ข้อมูลจำลอง
    return [
        [
            'id' => 1,
            'name' => 'นายเอกชัย รักเรียน',
            'avatar' => 'อ',
            'class' => 'ม.6/1',
            'number' => 15,
            'student_id' => '16536',
            'present' => true,
            'check_in_time' => '07:45',
            'attendance_days' => 97,
            'absent_days' => 0,
            'attendance_percentage' => 100,
            'monthly_data' => [
                ['month' => 'ต.ค.', 'percentage' => 80],
                ['month' => 'พ.ย.', 'percentage' => 90],
                ['month' => 'ธ.ค.', 'percentage' => 100],
                ['month' => 'ม.ค.', 'percentage' => 95],
                ['month' => 'ก.พ.', 'percentage' => 97],
                ['month' => 'มี.ค.', 'percentage' => 100]
            ]
        ],
        [
            'id' => 2,
            'name' => 'นางสาวสมหญิง รักเรียน',
            'avatar' => 'ส',
            'class' => 'ม.4/2',
            'number' => 8,
            'student_id' => '14528',
            'present' => true,
            'check_in_time' => '07:40',
            'attendance_days' => 95,
            'absent_days' => 2,
            'attendance_percentage' => 97.9,
            'monthly_data' => [
                ['month' => 'ต.ค.', 'percentage' => 85],
                ['month' => 'พ.ย.', 'percentage' => 95],
                ['month' => 'ธ.ค.', 'percentage' => 92],
                ['month' => 'ม.ค.', 'percentage' => 90],
                ['month' => 'ก.พ.', 'percentage' => 94],
                ['month' => 'มี.ค.', 'percentage' => 98]
            ]
        ],
        [
            'id' => 3,
            'name' => 'เด็กชายธนกฤต รักเรียน',
            'avatar' => 'ธ',
            'class' => 'ป.6/3',
            'number' => 10,
            'student_id' => '09610',
            'present' => true,
            'check_in_time' => '07:35',
            'attendance_days' => 94,
            'absent_days' => 3,
            'attendance_percentage' => 96.9,
            'monthly_data' => [
                ['month' => 'ต.ค.', 'percentage' => 88],
                ['month' => 'พ.ย.', 'percentage' => 92],
                ['month' => 'ธ.ค.', 'percentage' => 95],
                ['month' => 'ม.ค.', 'percentage' => 93],
                ['month' => 'ก.พ.', 'percentage' => 90],
                ['month' => 'มี.ค.', 'percentage' => 96]
            ]
        ]
    ];
}

// สร้างฟังก์ชันจำลองการดึงข้อมูลประวัติการเข้าแถว (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getAttendanceHistory($student_id, $limit = 10) {
    // ข้อมูลจำลองสำหรับนักเรียนแต่ละคน
    $history = [];
    
    // วันที่ปัจจุบัน
    $today = new DateTime();
    
    // สุ่มข้อมูลการเข้าแถวย้อนหลัง
    for ($i = 0; $i < $limit; $i++) {
        $date = clone $today;
        $date->modify("-$i day");
        
        // ข้ามวันเสาร์อาทิตย์
        $dayOfWeek = $date->format('N');
        if ($dayOfWeek > 5) { // 6 = เสาร์, 7 = อาทิตย์
            continue;
        }
        
        // สุ่มสถานะการมาเรียน (90% มาเรียน, 10% ขาดเรียน)
        $present = (mt_rand(1, 100) <= 90);
        
        $entry = [
            'date' => $date->format('d/m/Y'),
            'day' => $date->format('d'),
            'month' => $date->format('m/Y'),
            'month_short' => getThaiBuddhistMonthShort($date),
            'present' => $present
        ];
        
        if ($present) {
            // เวลามาโรงเรียน
            $hour = mt_rand(7, 8);
            $minute = mt_rand(0, 59);
            if ($hour == 7 && $minute < 30) {
                $minute = mt_rand(30, 59); // มาไวเกินไป
            }
            if ($hour == 8 && $minute > 30) {
                $minute = mt_rand(0, 30); // มาสายเกินไป
            }
            
            $entry['time'] = sprintf('%02d:%02d', $hour, $minute);
            
            // วิธีเช็คชื่อ
            $methods = [
                ['icon' => 'gps_fixed', 'name' => 'GPS'],
                ['icon' => 'qr_code_scanner', 'name' => 'QR Code'],
                ['icon' => 'pin', 'name' => 'รหัส PIN']
            ];
            $method = $methods[array_rand($methods)];
            $entry['method_icon'] = $method['icon'];
            $entry['method'] = $method['name'];
        }
        
        $history[] = $entry;
    }
    
    return $history;
}

// ฟังก์ชันแปลงเดือนเป็นภาษาไทยแบบสั้น
function getThaiBuddhistMonthShort($date) {
    $monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $month = intval($date->format('n')) - 1; // 0-11
    return $monthNames[$month];
}

// สร้างฟังก์ชันจำลองการดึงข้อมูลครูที่ปรึกษา (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getTeacher($student_id) {
    // ข้อมูลจำลองตามรหัสนักเรียน
    $teachers = [
        1 => [
            'id' => 1,
            'name' => 'อาจารย์ใจดี มากเมตตา',
            'position' => 'ครูประจำชั้น ม.6/1',
            'phone' => '0812345678',
            'line_id' => '@teacher_prasat'
        ],
        2 => [
            'id' => 2,
            'name' => 'อาจารย์สุขใจ มีเมตตา',
            'position' => 'ครูประจำชั้น ม.4/2',
            'phone' => '0823456789',
            'line_id' => '@teacher_prasat'
        ],
        3 => [
            'id' => 3,
            'name' => 'อาจารย์รักศิษย์ ยิ้มแย้ม',
            'position' => 'ครูประจำชั้น ป.6/3',
            'phone' => '0834567890',
            'line_id' => '@teacher_prasat'
        ]
    ];
    
    return isset($teachers[$student_id]) ? $teachers[$student_id] : $teachers[1];
}

// ดึงข้อมูล
$students = getStudents($parent_id);

// ตรวจสอบการดูรายละเอียดนักเรียน
$selected_student = null;
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    
    // ค้นหานักเรียนตาม ID
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            $selected_student = $student;
            break;
        }
    }
    
    // ถ้าพบข้อมูลนักเรียน ให้ดึงข้อมูลเพิ่มเติม
    if ($selected_student) {
        $selected_student['attendance_history'] = getAttendanceHistory($student_id);
        $selected_student['teacher'] = getTeacher($student_id);
    }
}

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/students_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
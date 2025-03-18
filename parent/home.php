<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าหลักสำหรับผู้ปกครองในระบบเช็คชื่อเข้าแถวนักเรียน
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
$page_title = 'SADD-Prasat - หน้าหลักผู้ปกครอง';
$current_page = 'dashboard';
$extra_css = [
    'assets/css/parent-dashboard.css'
];
$extra_js = [
    'assets/js/parent-dashboard.js'
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
            'present' => true,
            'check_in_time' => '07:45',
            'attendance_days' => 97,
            'absent_days' => 0,
            'attendance_percentage' => 100
        ],
        [
            'id' => 2,
            'name' => 'นางสาวสมหญิง รักเรียน',
            'avatar' => 'ส',
            'class' => 'ม.4/2',
            'number' => 8,
            'present' => true,
            'check_in_time' => '07:40',
            'attendance_days' => 95,
            'absent_days' => 2,
            'attendance_percentage' => 97.9
        ],
        [
            'id' => 3,
            'name' => 'เด็กชายธนกฤต รักเรียน',
            'avatar' => 'ธ',
            'class' => 'ป.6/3',
            'number' => 10,
            'present' => true,
            'check_in_time' => '07:35',
            'attendance_days' => 94,
            'absent_days' => 3,
            'attendance_percentage' => 96.9
        ]
    ];
}

// สร้างฟังก์ชันจำลองการดึงข้อมูลกิจกรรมล่าสุด (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getActivities($parent_id) {
    // ข้อมูลจำลอง
    return [
        [
            'id' => 1,
            'type' => 'check-in',
            'icon' => 'check_circle',
            'title' => 'นายเอกชัย รักเรียน เช็คชื่อเข้าแถว',
            'time' => 'วันนี้, 07:45 น.'
        ],
        [
            'id' => 2,
            'type' => 'check-in',
            'icon' => 'check_circle',
            'title' => 'นางสาวสมหญิง รักเรียน เช็คชื่อเข้าแถว',
            'time' => 'วันนี้, 07:40 น.'
        ],
        [
            'id' => 3,
            'type' => 'check-in',
            'icon' => 'check_circle',
            'title' => 'เด็กชายธนกฤต รักเรียน เช็คชื่อเข้าแถว',
            'time' => 'วันนี้, 07:35 น.'
        ],
        [
            'id' => 4,
            'type' => 'announcement',
            'icon' => 'campaign',
            'title' => 'ประกาศ: แจ้งกำหนดการสอบปลายภาค',
            'time' => 'เมื่อวาน, 10:30 น.'
        ]
    ];
}

// สร้างฟังก์ชันจำลองการดึงข้อมูลครูที่ปรึกษา (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getTeacher($student_id) {
    // ข้อมูลจำลอง
    return [
        'id' => 1,
        'name' => 'อาจารย์ใจดี มากเมตตา',
        'position' => 'ครูประจำชั้น ม.6/1',
        'phone' => '0812345678',
        'line_id' => '@teacher_prasat'
    ];
}

// สร้างฟังก์ชันจำลองการดึงข้อมูลประกาศ (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
function getAnnouncements() {
    // ข้อมูลจำลอง
    return [
        [
            'id' => 1,
            'category' => 'สอบ',
            'category_class' => 'exam',
            'title' => 'แจ้งกำหนดการสอบปลายภาค',
            'content' => 'แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2567 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น.',
            'date' => '14 มี.ค. 2568'
        ],
        [
            'id' => 2,
            'category' => 'กิจกรรม',
            'category_class' => 'event',
            'title' => 'ประชุมผู้ปกครองภาคเรียนที่ 2',
            'content' => 'ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน',
            'date' => '10 มี.ค. 2568'
        ]
    ];
}

// ดึงข้อมูล
$students = getStudents($parent_id);
$latest_check_in = null;
if (!empty($students) && isset($students[0])) {
    $latest_check_in = $students[0]['name'] . ' เช็คชื่อเข้าแถวเวลา ' . $students[0]['check_in_time'] . ' น.';
}

$activities = getActivities($parent_id);
$teacher = getTeacher(isset($students[0]['id']) ? $students[0]['id'] : 1);
$announcements = getAnnouncements();

// กำหนดเส้นทางไฟล์เนื้อหา
$content_path = 'pages/dashboard_content.php';

// Include ไฟล์เทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
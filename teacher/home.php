<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'dashboard';
$page_title = 'Teacher-Prasat - หน้าหลัก';
$page_header = 'หน้าหลัก';

// ข้อมูลครูที่ปรึกษา
$teacher_info = [
    'name' => 'อาจารย์ใจดี มากเมตตา',
    'avatar' => 'ค',
    'role' => 'ครูที่ปรึกษา'
];

// ข้อมูลห้องเรียนที่ครูรับผิดชอบ
$teacher_classes = [
    [
        'id' => 1,
        'name' => 'ม.6/1',
        'total_students' => 35,
        'present_today' => 33,
        'absent_today' => 2,
        'not_checked' => 0,
        'attendance_rate' => 94.3,
        'at_risk_count' => 2
    ],
    [
        'id' => 2,
        'name' => 'ม.6/2',
        'total_students' => 32,
        'present_today' => 28,
        'absent_today' => 1,
        'not_checked' => 3,
        'attendance_rate' => 87.5,
        'at_risk_count' => 3
    ],
    [
        'id' => 3,
        'name' => 'ม.6/3',
        'total_students' => 30,
        'present_today' => 0,
        'absent_today' => 0,
        'not_checked' => 30,
        'attendance_rate' => 90.2,
        'at_risk_count' => 1
    ]
];

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 1;

// ดึงข้อมูลห้องเรียนปัจจุบัน
$current_class = null;
foreach ($teacher_classes as $class) {
    if ($class['id'] == $current_class_id) {
        $current_class = $class;
        break;
    }
}

// ถ้าไม่พบห้องเรียน ให้ใช้ห้องแรก
if ($current_class === null) {
    $current_class = $teacher_classes[0];
}

// รหัส PIN สำหรับการเช็คชื่อ
$active_pin = [
    'code' => '5731',
    'expire_in_minutes' => 9
];

// ข้อมูลนักเรียนสรุป (5 คนล่าสุด)
$students_summary = [
    [
        'number' => 1,
        'name' => 'นายเอกชัย รักเรียน',
        'status' => 'present',
        'time' => '07:45'
    ],
    [
        'number' => 2,
        'name' => 'นางสาวสมศรี ใจดี',
        'status' => 'present',
        'time' => '07:46'
    ],
    [
        'number' => 3,
        'name' => 'นายสมชาย เรียนดี',
        'status' => 'present',
        'time' => '07:48'
    ],
    [
        'number' => 4,
        'name' => 'นางสาวพิมพ์ใจ ร่าเริง',
        'status' => 'absent',
        'time' => '08:00'
    ],
    [
        'number' => 5,
        'name' => 'นายสุชาติ รักษาสัตย์',
        'status' => 'present',
        'time' => '07:52'
    ],
];

// นักเรียนที่มีความเสี่ยงตกกิจกรรม
$at_risk_students = [
    [
        'id' => 4,
        'number' => 4,
        'name' => 'นางสาวพิมพ์ใจ ร่าเริง',
        'attendance_rate' => 68.5,
        'absent_days' => 15,
        'last_absent' => 'วันนี้'
    ],
    [
        'id' => 8,
        'number' => 8,
        'name' => 'นายขวัญใจ นารี',
        'attendance_rate' => 70.2,
        'absent_days' => 14,
        'last_absent' => 'เมื่อวาน'
    ]
];

// ประกาศสำคัญ
$announcements = [
    [
        'title' => 'แจ้งกำหนดการสอบปลายภาค',
        'content' => 'แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2568 ระหว่างวันที่ 1-5 เมษายน 2568',
        'date' => '14 มี.ค. 2025'
    ],
    [
        'title' => 'ประชุมผู้ปกครองภาคเรียนที่ 2',
        'content' => 'ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองในวันเสาร์ที่ 22 มีนาคม 2568',
        'date' => '10 มี.ค. 2025'
    ]
];

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-home.css'
];

$extra_js = [
    'assets/js/teacher-home.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_home_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
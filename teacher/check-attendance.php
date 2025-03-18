<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'Teacher-Prasat - เช็คชื่อนักเรียน';
$page_header = 'เช็คชื่อนักเรียน';

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
        'present_count' => 30,
        'absent_count' => 2,
        'not_checked' => 3
    ],
    [
        'id' => 2,
        'name' => 'ม.6/2',
        'total_students' => 32,
        'present_count' => 25,
        'absent_count' => 3,
        'not_checked' => 4
    ],
    [
        'id' => 3,
        'name' => 'ม.6/3',
        'total_students' => 30,
        'present_count' => 0,
        'absent_count' => 0,
        'not_checked' => 30
    ]
];

// ดึงห้องเรียนที่กำลังเช็คชื่อ (จาก URL หรือค่าเริ่มต้น)
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

// สร้างรหัส PIN สำหรับการเช็คชื่อ
$pin_code = rand(1000, 9999); // สร้างรหัส PIN 4 หลักแบบสุ่ม

// ข้อมูลนักเรียนในชั้นเรียนปัจจุบัน
// สถานะการเช็คชื่อ: 'present' = มาเรียน, 'absent' = ขาดเรียน, 'not_checked' = ยังไม่ได้เช็ค
$students = [
    // นักเรียนที่ยังไม่ได้เช็คชื่อ
    [
        'id' => 1,
        'number' => 1,
        'name' => 'นายเอกชัย รักเรียน',
        'status' => 'not_checked'
    ],
    [
        'id' => 2,
        'number' => 2,
        'name' => 'นางสาวสมศรี ใจดี',
        'status' => 'not_checked'
    ],
    [
        'id' => 3,
        'number' => 3,
        'name' => 'นายสมชาย เรียนดี',
        'status' => 'not_checked'
    ],
    // นักเรียนที่เช็คชื่อแล้ว - มาเรียน
    [
        'id' => 4,
        'number' => 4,
        'name' => 'นางสาวพิมพ์ใจ ร่าเริง',
        'status' => 'present',
        'time_checked' => '07:45'
    ],
    [
        'id' => 5,
        'number' => 5,
        'name' => 'นายสุชาติ รักษาสัตย์',
        'status' => 'present',
        'time_checked' => '07:48'
    ],
    [
        'id' => 6,
        'number' => 6,
        'name' => 'นางสาววันเพ็ญ แสนสุข',
        'status' => 'present',
        'time_checked' => '07:50'
    ],
    [
        'id' => 7,
        'number' => 7,
        'name' => 'นายธันวา มั่นคง',
        'status' => 'present',
        'time_checked' => '07:52'
    ],
    [
        'id' => 8,
        'number' => 8,
        'name' => 'นายขวัญใจ นารี',
        'status' => 'present',
        'time_checked' => '07:53'
    ],
    // นักเรียนที่เช็คชื่อแล้ว - ขาดเรียน
    [
        'id' => 9,
        'number' => 9,
        'name' => 'นางสาวน้ำใส ไหลเย็น',
        'status' => 'absent',
        'time_checked' => '08:00'
    ],
    [
        'id' => 10,
        'number' => 10,
        'name' => 'นายรุ่งโรจน์ สดใส',
        'status' => 'absent',
        'time_checked' => '08:00'
    ]
];

// แยกนักเรียนตามสถานะการเช็คชื่อ
$unchecked_students = [];
$checked_students = [];

foreach ($students as $student) {
    if ($student['status'] === 'not_checked') {
        $unchecked_students[] = $student;
    } else {
        $checked_students[] = $student;
    }
}

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-check-attendance.css'
];

$extra_js = [
    'assets/js/teacher-check-attendance.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_check_attendance_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
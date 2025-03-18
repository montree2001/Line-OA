<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'dashboard';
$page_title = 'Teacher-Prasat - หน้าหลัก';
$page_header = 'หน้าหลัก';

// ข้อมูลครูที่ปรึกษา
$teacher_info = [
    'name' => 'อาจารย์ใจดี มากเมตตา',
    'class' => 'ม.6/1',
    'avatar' => 'ค',
    'role' => 'ครูที่ปรึกษา'
];

// ข้อมูลนักเรียนในที่ปรึกษา
$class_info = [
    'name' => 'ม.6/1',
    'total_students' => 35,
    'present_today' => 33,
    'absent_today' => 2
];

// รหัส PIN สำหรับการเช็คชื่อ
$active_pin = [
    'code' => '5731',
    'expire_in_minutes' => 9
];

// ข้อมูลนักเรียนสรุป (ตัวอย่าง 5 คนแรก)
$students_summary = [
    [
        'number' => 1,
        'name' => 'นายเอกชัย รักเรียน',
        'status' => 'present'
    ],
    [
        'number' => 2,
        'name' => 'นางสาวสมศรี ใจดี',
        'status' => 'present'
    ],
    [
        'number' => 3,
        'name' => 'นายสมชาย เรียนดี',
        'status' => 'present'
    ],
    [
        'number' => 4,
        'name' => 'นางสาวพิมพ์ใจ ร่าเริง',
        'status' => 'absent'
    ],
    [
        'number' => 5,
        'name' => 'นายสุชาติ รักษาสัตย์',
        'status' => 'present'
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
<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'reports';
$page_title = 'Teacher-Prasat - รายงานการเข้าแถว';
$page_header = 'รายงานการเข้าแถว';

// ข้อมูลครูที่ปรึกษา
$teacher_info = [
    'name' => 'อาจารย์ใจดี มากเมตตา',
    'class' => 'ม.6/1',
    'avatar' => 'ค',
    'role' => 'ครูที่ปรึกษา'
];

// ข้อมูลชั้นเรียน
$class_info = [
    'name' => 'ม.6/1',
    'total_students' => 35
];

// สถิติการเข้าแถว
$attendance_stats = [
    'average_rate' => 93.5,
    'problem_count' => 3,
    'school_days' => 23
];

// ข้อมูลการเข้าแถวรายวัน (ตัวอย่าง 7 วันล่าสุด)
$daily_attendance = [
    ['day' => 'จันทร์', 'percentage' => 95.2],
    ['day' => 'อังคาร', 'percentage' => 96.1],
    ['day' => 'พุธ', 'percentage' => 93.8],
    ['day' => 'พฤหัสบดี', 'percentage' => 92.7],
    ['day' => 'ศุกร์', 'percentage' => 91.5],
    ['day' => 'จันทร์', 'percentage' => 98.1],
    ['day' => 'วันนี้', 'percentage' => 95.0]
];

// รายชื่อนักเรียน (ตัวอย่าง)
$students = [
    [
        'id' => 1,
        'number' => 1,
        'name' => 'นายเอกชัย รักเรียน',
        'attendance_days' => '23/23',
        'percentage' => 100,
        'status' => 'good'
    ],
    [
        'id' => 2,
        'number' => 2,
        'name' => 'นางสาวสมศรี ใจดี',
        'attendance_days' => '23/23',
        'percentage' => 100,
        'status' => 'good'
    ],
    [
        'id' => 3,
        'number' => 3,
        'name' => 'นายสมชาย เรียนดี',
        'attendance_days' => '21/23',
        'percentage' => 91.3,
        'status' => 'good'
    ],
    [
        'id' => 4,
        'number' => 4,
        'name' => 'นางสาวพิมพ์ใจ ร่าเริง',
        'attendance_days' => '18/23',
        'percentage' => 78.3,
        'status' => 'warning'
    ],
    [
        'id' => 5,
        'number' => 5,
        'name' => 'นายสุชาติ รักษาสัตย์',
        'attendance_days' => '22/23',
        'percentage' => 95.7,
        'status' => 'good'
    ]
];

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-reports.css'
];
$extra_js = [
    'assets/js/teacher-reports.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_reports_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
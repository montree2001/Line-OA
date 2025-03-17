<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'history';
$page_title = 'STD-Prasat - ประวัติการเข้าแถว';
$page_header = 'ประวัติการเข้าแถว';

// ข้อมูลนักเรียน
$student_info = [
    'name' => 'นายเอกชัย รักเรียน',
    'class' => 'ม.6/1',
    'number' => 15,
    'avatar' => 'อ'
];

// สรุปการเข้าแถวประจำเดือน
$monthly_summary = [
    'total_days' => 23,
    'absent_days' => 0,
    'attendance_percentage' => 100,
    'regularity_score' => 97
];

// กราฟข้อมูลการเข้าแถว
$attendance_chart = [
    ['month' => 'ม.ค.', 'percentage' => 80],
    ['month' => 'ก.พ.', 'percentage' => 90],
    ['month' => 'มี.ค.', 'percentage' => 100],
    ['month' => 'เม.ย.', 'percentage' => 0],
    ['month' => 'พ.ค.', 'percentage' => 0],
    ['month' => 'มิ.ย.', 'percentage' => 0]
];

// ประวัติการเข้าแถว
$check_in_history = [
    [
        'date' => '17 มี.ค. 2025',
        'time' => '07:45',
        'status' => 'present',
        'method' => 'GPS'
    ],
    [
        'date' => '14 มี.ค. 2025',
        'time' => '07:40',
        'status' => 'present',
        'method' => 'PIN'
    ],
    [
        'date' => '13 มี.ค. 2025',
        'time' => '07:38',
        'status' => 'present',
        'method' => 'QR Code'
    ],
    [
        'date' => '12 มี.ค. 2025',
        'time' => '07:42',
        'status' => 'present',
        'method' => 'GPS'
    ],
    [
        'date' => '11 มี.ค. 2025',
        'time' => '07:36',
        'status' => 'present',
        'method' => 'PIN'
    ],
    [
        'date' => '10 มี.ค. 2025',
        'time' => '07:41',
        'status' => 'present',
        'method' => 'QR Code'
    ],
    [
        'date' => '7 มี.ค. 2025',
        'time' => '07:39',
        'status' => 'present',
        'method' => 'GPS'
    ]
];

// รวม CSS และ JS
$extra_css = [
    'assets/css/student-report.css'
];
$extra_js = [
    'assets/js/student-report.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/student_report_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
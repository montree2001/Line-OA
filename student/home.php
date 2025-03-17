<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'dashboard';
$page_title = 'STD-Prasat - หน้าหลัก';
$page_header = 'หน้าหลัก';

// ข้อมูลนักเรียน
$student_info = [
    'name' => 'นายเอกชัย รักเรียน',
    'class' => 'ม.6/1',
    'number' => 15,
    'avatar' => 'อ'
];

// สถิติการเข้าแถว
$attendance_stats = [
    'total_days' => 97,
    'attendance_days' => 97,
    'attendance_percentage' => 100
];

// การแจ้งเตือน
$alert = [
    'title' => 'แจ้งเตือนเวลาเช็คชื่อ',
    'message' => 'เช็คชื่อเข้าแถวได้ถึงเวลา 08:30 น. เท่านั้น'
];

// ประวัติการเช็คชื่อ
$check_in_history = [
    [
        'day' => 16,
        'month' => 'มี.ค.',
        'time' => '07:45',
        'method' => 'GPS'
    ],
    [
        'day' => 15,
        'month' => 'มี.ค.',
        'time' => '07:40', 
        'method' => 'PIN'
    ],
    [
        'day' => 14,
        'month' => 'มี.ค.',
        'time' => '07:38',
        'method' => 'QR Code'
    ]
];

// ประกาศจากโรงเรียน
$announcements = [
    [
        'badge' => 'urgent',
        'badge_text' => 'ด่วน',
        'title' => 'แจ้งกำหนดการสอบปลายภาค',
        'content' => 'แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2568 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น.',
        'date' => '14 มี.ค. 2025'
    ],
    [
        'badge' => 'event',
        'badge_text' => 'กิจกรรม',
        'title' => 'ประชุมผู้ปกครองภาคเรียนที่ 2',
        'content' => 'ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน',
        'date' => '10 มี.ค. 2025'
    ],
    [
        'badge' => 'info',
        'badge_text' => 'ข่าวสาร',
        'title' => 'แนะแนวการศึกษาต่อ',
        'content' => 'จะมีการแนะแนวการศึกษาต่อระดับอุดมศึกษา ในวันพุธที่ 26 มีนาคม 2568 เวลา 13.00-16.00 น. ณ หอประชุมโรงเรียน',
        'date' => '8 มี.ค. 2025'
    ]
];

// รวม CSS และ JS
$extra_css = [
    'assets/css/student-home.css'
];
$extra_js = [
    'assets/js/student-home.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/student_home_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
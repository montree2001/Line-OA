<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'reports';
$page_title = 'Teacher-Prasat - รายงานการเข้าแถว';
$page_header = 'รายงานการเข้าแถว';

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
        'present_count' => 33,
        'absent_count' => 2,
        'attendance_rate' => 94.3,
        'at_risk_count' => 2
    ],
    [
        'id' => 2,
        'name' => 'ม.6/2',
        'total_students' => 32,
        'present_count' => 30,
        'absent_count' => 2,
        'attendance_rate' => 93.8,
        'at_risk_count' => 3
    ],
    [
        'id' => 3,
        'name' => 'ม.6/3',
        'total_students' => 30,
        'present_count' => 28,
        'absent_count' => 2,
        'attendance_rate' => 93.3,
        'at_risk_count' => 1
    ]
];

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 1;

// ดึงข้อมูลเดือนที่กำลังดู (จาก URL หรือค่าเริ่มต้น = เดือนปัจจุบัน)
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

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

// สถิติการเข้าแถว
$attendance_stats = [
    'average_rate' => $current_class['attendance_rate'],
    'problem_count' => $current_class['at_risk_count'],
    'school_days' => 23
];

// ข้อมูลการเข้าแถวรายวัน (ตัวอย่าง 7 วันล่าสุด)
$daily_attendance = [
    ['day' => 'จันทร์', 'date' => '16 มี.ค.', 'percentage' => 95.2],
    ['day' => 'อังคาร', 'date' => '17 มี.ค.', 'percentage' => 96.1],
    ['day' => 'พุธ', 'date' => '18 มี.ค.', 'percentage' => 93.8],
    ['day' => 'พฤหัสบดี', 'date' => '19 มี.ค.', 'percentage' => 92.7],
    ['day' => 'ศุกร์', 'date' => '20 มี.ค.', 'percentage' => 91.5],
    ['day' => 'จันทร์', 'date' => '23 มี.ค.', 'percentage' => 98.1],
    ['day' => 'อังคาร', 'date' => '24 มี.ค.', 'percentage' => 95.0]
];

// ข้อมูลการเข้าแถวรายเดือน
$monthly_attendance = [
    ['month' => 'มกราคม', 'value' => 1, 'percentage' => 93.5],
    ['month' => 'กุมภาพันธ์', 'value' => 2, 'percentage' => 94.2],
    ['month' => 'มีนาคม', 'value' => 3, 'percentage' => 95.0],
    ['month' => 'เมษายน', 'value' => 4, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'พฤษภาคม', 'value' => 5, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'มิถุนายน', 'value' => 6, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'กรกฎาคม', 'value' => 7, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'สิงหาคม', 'value' => 8, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'กันยายน', 'value' => 9, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'ตุลาคม', 'value' => 10, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'พฤศจิกายน', 'value' => 11, 'percentage' => 0], // ยังไม่มีข้อมูล
    ['month' => 'ธันวาคม', 'value' => 12, 'percentage' => 92.8]
];

// ข้อมูลนักเรียนในห้องเรียนที่เลือก
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
    ],
    [
        'id' => 6,
        'number' => 6,
        'name' => 'นางสาววันเพ็ญ แสนสุข',
        'attendance_days' => '23/23',
        'percentage' => 100,
        'status' => 'good'
    ],
    [
        'id' => 7,
        'number' => 7,
        'name' => 'นายธันวา มั่นคง',
        'attendance_days' => '20/23',
        'percentage' => 87.0,
        'status' => 'good'
    ],
    [
        'id' => 8,
        'number' => 8,
        'name' => 'นายขวัญใจ นารี',
        'attendance_days' => '16/23',
        'percentage' => 69.6,
        'status' => 'danger'
    ],
    [
        'id' => 9,
        'number' => 9,
        'name' => 'นางสาวน้ำใส ไหลเย็น',
        'attendance_days' => '22/23',
        'percentage' => 95.7,
        'status' => 'good'
    ],
    [
        'id' => 10,
        'number' => 10,
        'name' => 'นายรุ่งโรจน์ สดใส',
        'attendance_days' => '21/23',
        'percentage' => 91.3,
        'status' => 'good'
    ],
];

// ข้อมูลปฏิทินการเข้าแถว (สมมติ มีนาคม 2025)
$calendar_data = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
$first_day = date('N', strtotime("$current_year-$current_month-01"));

// วันเริ่มต้นในปฏิทิน (ถ้าวันที่ 1 ไม่ใช่วันจันทร์)
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$days_in_prev_month = cal_days_in_month(CAL_GREGORIAN, $prev_month, $prev_year);
$start_day = $days_in_prev_month - $first_day + 2;

// สร้างข้อมูลวันที่ก่อนหน้าเดือนปัจจุบัน
for ($i = 0; $i < $first_day - 1; $i++) {
    $calendar_data[] = [
        'day' => $start_day + $i,
        'month' => $prev_month,
        'year' => $prev_year,
        'current_month' => false,
        'present' => 0,
        'absent' => 0,
        'total' => $current_class['total_students'],
        'percentage' => 0,
        'is_school_day' => false
    ];
}

// สร้างข้อมูลวันที่ในเดือนปัจจุบัน
for ($day = 1; $day <= $days_in_month; $day++) {
    $date = "$current_year-$current_month-$day";
    $day_of_week = date('N', strtotime($date));
    
    // สมมติว่าวันเสาร์-อาทิตย์ไม่ใช่วันเรียน
    $is_school_day = ($day_of_week < 6);
    
    // สุ่มข้อมูลการเช็คชื่อสำหรับวันเรียน
    if ($is_school_day) {
        $present = rand(round($current_class['total_students'] * 0.85), $current_class['total_students']);
        $absent = $current_class['total_students'] - $present;
        $percentage = round(($present / $current_class['total_students']) * 100, 1);
    } else {
        $present = 0;
        $absent = 0;
        $percentage = 0;
    }
    
    $calendar_data[] = [
        'day' => $day,
        'month' => $current_month,
        'year' => $current_year,
        'current_month' => true,
        'present' => $present,
        'absent' => $absent,
        'total' => $current_class['total_students'],
        'percentage' => $percentage,
        'is_school_day' => $is_school_day
    ];
}

// สร้างข้อมูลวันที่หลังเดือนปัจจุบัน (เพื่อให้ครบ 42 ช่อง หรือ 6 สัปดาห์)
$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$remaining_days = 42 - count($calendar_data);
for ($day = 1; $day <= $remaining_days; $day++) {
    $calendar_data[] = [
        'day' => $day,
        'month' => $next_month,
        'year' => $next_year,
        'current_month' => false,
        'present' => 0,
        'absent' => 0,
        'total' => $current_class['total_students'],
        'percentage' => 0,
        'is_school_day' => false
    ];
}

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
<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'settings';
$page_title = 'Teacher-Prasat - ตั้งค่า';
$page_header = 'ตั้งค่า';

// ข้อมูลครูที่ปรึกษา
$teacher_info = [
    'name' => 'อาจารย์ใจดี มากเมตตา',
    'avatar' => 'ค',
    'role' => 'ครูที่ปรึกษา'
];

// รายการห้องเรียนที่ครูรับผิดชอบ
$teacher_classes = [
    [
        'id' => 1,
        'name' => 'ม.6/1',
        'total_students' => 35,
        'attendance_rate' => 94.3,
        'at_risk_count' => 2,
        'is_primary' => true
    ],
    [
        'id' => 2,
        'name' => 'ม.6/2',
        'total_students' => 32,
        'attendance_rate' => 87.5,
        'at_risk_count' => 3,
        'is_primary' => false
    ],
    [
        'id' => 3,
        'name' => 'ม.6/3',
        'total_students' => 30,
        'attendance_rate' => 90.2,
        'at_risk_count' => 1,
        'is_primary' => false
    ]
];

// ข้อมูลการตั้งค่าโปรไฟล์
$profile_settings = [
    'id' => 1,
    'title' => 'อาจารย์',
    'firstname' => 'ใจดี',
    'lastname' => 'มากเมตตา',
    'phone' => '0891234567',
    'email' => 'teacher@prasat.ac.th',
    'line_id' => 'teacher_prasat',
    'profile_image' => null,
    'department' => 'คณิตศาสตร์'
];

// ข้อมูลการตั้งค่าการแจ้งเตือน
$notification_settings = [
    'student_present' => true,
    'student_absent' => true,
    'school_announcement' => true,
    'parent_message' => true,
    'at_risk_warning' => true,
    'attendance_summary' => true
];

// ข้อมูลการตั้งค่าการเช็คชื่อ
$attendance_settings = [
    'pin_expiration' => 10, // หน่วยเป็นนาที
    'pin_length' => 4,
    'qr_expiration' => 15, // หน่วยเป็นนาที
    'gps_distance' => 100, // หน่วยเป็นเมตร
    'gps_latitude' => '14.967500',
    'gps_longitude' => '102.076683',
    'time_start' => '07:30',
    'time_end' => '08:30',
    'auto_absent' => true,
    'absent_notification' => true
];

// ข้อมูลการตั้งค่าทั่วไป
$general_settings = [
    'language' => 'th',
    'theme' => 'light',
    'font_size' => 'medium',
    'dashboard_view' => 'card'
];

// ข้อมูลรุ่นแอปพลิเคชัน
$app_version = [
    'version' => 'v1.0.0',
    'updated_at' => '2025-03-01',
    'copyright' => '© 2025 โรงเรียนปราสาทวิทยาคม'
];

// ดึงการตั้งค่าตามประเภท
$setting_type = isset($_GET['type']) ? $_GET['type'] : 'profile';

// ฟังก์ชันสำหรับการบันทึกการตั้งค่า
function saveSettings($type, $data) {
    // ในระบบจริงจะมีการบันทึกข้อมูลลงฐานข้อมูล
    // แต่ในตัวอย่างนี้จะเป็นเพียงการจำลองการบันทึก
    
    // สร้างการแจ้งเตือนว่าบันทึกสำเร็จ
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว'
    ];
    
    // ลิงก์กลับไปยังหน้าการตั้งค่าเดิม
    return "settings.php?type={$type}";
}

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-settings.css'
];

$extra_js = [
    'assets/js/teacher-settings.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_settings_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
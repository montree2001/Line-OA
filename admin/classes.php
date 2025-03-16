<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
//session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียน';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadClassReport()'
    ],
    [
        'text' => 'สถิติชั้นเรียน',
        'icon' => 'leaderboard',
        'onclick' => 'showClassStatistics()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js',
    'assets/js/charts.js'
];

// ข้อมูลตัวอย่างชั้นเรียน (ในทางปฏิบัติจริง ควรดึงจากฐานข้อมูล)
$classes = [
    [
        'level' => 'ม.6',
        'room' => '1',
        'advisor' => 'อาจารย์ใจดี มากเมตตา',
        'total_students' => 35,
        'attendance_rate' => 94.3,
        'status' => 'good'
    ],
    [
        'level' => 'ม.6',
        'room' => '2',
        'advisor' => 'อาจารย์ราตรี นอนดึก',
        'total_students' => 32,
        'attendance_rate' => 87.5,
        'status' => 'warning'
    ],
    [
        'level' => 'ม.5',
        'room' => '1',
        'advisor' => 'อาจารย์มานะ พยายาม',
        'total_students' => 35,
        'attendance_rate' => 97.1,
        'status' => 'good'
    ]
];

// ข้อมูลครูที่ปรึกษา
$advisors = [
    'อาจารย์ใจดี มากเมตตา',
    'อาจารย์ราตรี นอนดึก',
    'อาจารย์มานะ พยายาม',
    'อาจารย์วันดี สดใส',
    'อาจารย์สมศรี ใจดี'
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'classes' => $classes,
    'advisors' => $advisors
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/classes_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */



/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียน';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadClassReport()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js'
];

// ข้อมูลตัวอย่างชั้นเรียน (ในทางปฏิบัติจริง ควรดึงจากฐานข้อมูล)
$classes = [
    [
        'level' => 'ม.6',
        'room' => '1',
        'advisor' => 'อาจารย์ใจดี มากเมตตา',
        'total_students' => 35,
        'attendance_rate' => 94.3,
        'status' => 'good'
    ],
    [
        'level' => 'ม.6',
        'room' => '2',
        'advisor' => 'อาจารย์ราตรี นอนดึก',
        'total_students' => 32,
        'attendance_rate' => 87.5,
        'status' => 'warning'
    ],
    [
        'level' => 'ม.5',
        'room' => '1',
        'advisor' => 'อาจารย์มานะ พยายาม',
        'total_students' => 35,
        'attendance_rate' => 97.1,
        'status' => 'good'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'classes' => $classes
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/classes_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
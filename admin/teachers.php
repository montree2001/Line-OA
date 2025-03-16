<?php
/**
 * teachers.php - หน้าจัดการข้อมูลครูที่ปรึกษา
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'teachers';
$page_title = 'จัดการครูที่ปรึกษา';
$page_header = 'จัดการข้อมูลครูที่ปรึกษา';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มครูที่ปรึกษา',
        'icon' => 'person_add',
        'onclick' => 'showAddTeacherModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'upload_file',
        'onclick' => 'showImportModal()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// เพิ่ม Bootstrap CSS แต่ยังคงใช้ CSS หลักของระบบ
$extra_css = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'assets/css/teachers.css' // CSS เฉพาะสำหรับหน้านี้ (ถ้ามี)
];

// เพิ่ม Bootstrap JS และ JS สำหรับหน้านี้
$extra_js = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'assets/js/teachers.js' // JS เฉพาะสำหรับหน้านี้ (ถ้ามี)
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/teachers_content.php';

// ตรวจสอบการส่งฟอร์มเพิ่มหรือแก้ไขข้อมูลครู (ในการใช้งานจริงจะมีการบันทึกลงฐานข้อมูล)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher']) || isset($_POST['edit_teacher'])) {
        // ดำเนินการเพิ่มหรือแก้ไขข้อมูลครู
        // ในทางปฏิบัติจริง จะมีการตรวจสอบข้อมูล และบันทึกลงฐานข้อมูล
        
        // จำลองการเพิ่ม/แก้ไขสำเร็จ
        $success_message = isset($_POST['add_teacher']) ? "เพิ่มข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว" : "แก้ไขข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
    } elseif (isset($_POST['import_teachers'])) {
        // ดำเนินการนำเข้าข้อมูลครู
        // ในทางปฏิบัติจริง จะมีการอัปโหลดและประมวลผลไฟล์
        
        // จำลองการนำเข้าสำเร็จ
        $success_message = "นำเข้าข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
    } elseif (isset($_POST['delete_teacher'])) {
        // ดำเนินการลบข้อมูลครู
        
        // จำลองการลบสำเร็จ
        $success_message = "ลบข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
    }
    
    // Redirect เพื่อป้องกันการ resubmit form
    header("Location: teachers.php" . (isset($success_message) ? "?success=" . urlencode($success_message) : ""));
    exit;
}

// แสดงข้อความแจ้งเตือนความสำเร็จ (ถ้ามี)
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// ตัวอย่างข้อมูลครูที่ปรึกษา (ในการใช้งานจริงจะดึงจากฐานข้อมูล)
$teachers = [
    [
        'id' => 1,
        'code' => 'T001',
        'name' => 'อาจารย์ประสิทธิ์ ดีเลิศ',
        'gender' => 'ชาย',
        'position' => 'ครูชำนาญการพิเศษ',
        'class' => 'ม.6/2',
        'department' => 'วิทยาศาสตร์',
        'phone' => '081-234-5678',
        'email' => 'prasit.d@prasat.ac.th',
        'status' => 'active',
        'students_count' => 35
    ],
    [
        'id' => 2,
        'code' => 'T002',
        'name' => 'อาจารย์วันดี สดใส',
        'gender' => 'หญิง',
        'position' => 'ครูชำนาญการ',
        'class' => 'ม.5/3',
        'department' => 'ภาษาไทย',
        'phone' => '089-876-5432',
        'email' => 'wandee.s@prasat.ac.th',
        'status' => 'active',
        'students_count' => 32
    ],
    [
        'id' => 3,
        'code' => 'T003',
        'name' => 'อาจารย์อิศรา สุขใจ',
        'gender' => 'ชาย',
        'position' => 'ครู',
        'class' => 'ม.5/1',
        'department' => 'คณิตศาสตร์',
        'phone' => '062-345-6789',
        'email' => 'issara.s@prasat.ac.th',
        'status' => 'active',
        'students_count' => 36
    ],
    [
        'id' => 4,
        'code' => 'T004',
        'name' => 'อาจารย์ใจดี มากเมตตา',
        'gender' => 'หญิง',
        'position' => 'ครูชำนาญการพิเศษ',
        'class' => 'ม.4/1',
        'department' => 'ภาษาอังกฤษ',
        'phone' => '091-234-5678',
        'email' => 'jaidee.m@prasat.ac.th',
        'status' => 'active',
        'students_count' => 30
    ],
    [
        'id' => 5,
        'code' => 'T005',
        'name' => 'อาจารย์สมหมาย ใจร่าเริง',
        'gender' => 'ชาย',
        'position' => 'ครูชำนาญการ',
        'class' => 'ม.4/2',
        'department' => 'สังคมศึกษา',
        'phone' => '098-765-4321',
        'email' => 'sommai.j@prasat.ac.th',
        'status' => 'inactive',
        'students_count' => 0
    ]
];

// ตัวอย่างข้อมูลภาพรวม (ในการใช้งานจริงจะดึงจากฐานข้อมูล)
$teachers_stats = [
    'total' => 25,
    'active' => 23,
    'inactive' => 2,
    'classrooms' => 35,
    'students' => 1250
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'teachers' => $teachers,
    'teachers_stats' => $teachers_stats,
    'success_message' => isset($success_message) ? $success_message : null
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
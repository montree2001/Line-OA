<?php
/**
 * parents.php - หน้าจัดการข้อมูลผู้ปกครอง
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
$current_page = 'parents';
$page_title = 'จัดการข้อมูลผู้ปกครอง';
$page_header = 'จัดการข้อมูลผู้ปกครอง';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มผู้ปกครอง',
        'icon' => 'person_add',
        'onclick' => 'showAddParentModal()'
    ],
    [
        'text' => 'นำเข้า CSV',
        'icon' => 'file_upload',
        'onclick' => 'showImportModal()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/parents.css'
];

$extra_js = [
    'assets/js/parents.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/parents_content.php';

// ตัวอย่างข้อมูลผู้ปกครอง (ในทางปฏิบัติจริงควรดึงจากฐานข้อมูล)
$parents = [
    [
        'id' => 1,
        'name' => 'นางวันดี สุขใจ',
        'relation' => 'แม่',
        'phone' => '089-765-4321',
        'email' => 'wandee@example.com',
        'line_id' => 'wandee123',
        'address' => '123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000',
        'student' => 'นายธนกฤต สุขใจ',
        'student_class' => 'ม.6/2',
        'notification_channels' => 'SMS, LINE',
        'status' => 'ใช้งาน'
    ],
    [
        'id' => 2,
        'name' => 'นายสมชาย มีสุข',
        'relation' => 'พ่อ',
        'phone' => '081-234-5678',
        'email' => 'somchai@example.com',
        'line_id' => 'somchai456',
        'address' => '456 หมู่ 8 ตำบลในเมือง อำเภอเมือง จังหวัดสุรินทร์ 32000',
        'student' => 'นางสาวสมหญิง มีสุข',
        'student_class' => 'ม.5/3',
        'notification_channels' => 'LINE',
        'status' => 'ใช้งาน'
    ],
    [
        'id' => 3,
        'name' => 'นางรักดี รักเรียน',
        'relation' => 'แม่',
        'phone' => '087-654-3210',
        'email' => 'rakdee@example.com',
        'line_id' => 'rakdee789',
        'address' => '789 หมู่ 2 ตำบลนอกเมือง อำเภอเมือง จังหวัดสุรินทร์ 32000',
        'student' => 'นายพิชัย รักเรียน',
        'student_class' => 'ม.4/1',
        'notification_channels' => 'SMS, LINE, Email',
        'status' => 'ใช้งาน'
    ],
    [
        'id' => 4,
        'name' => 'นายวิชัย พัฒนา',
        'relation' => 'พ่อ',
        'phone' => '082-345-6789',
        'email' => 'wichai@example.com',
        'line_id' => 'wichai012',
        'address' => '12 หมู่ 6 ตำบลตากูก อำเภอเมือง จังหวัดสุรินทร์ 32000',
        'student' => 'นางสาวรุ่งนภา พัฒนา',
        'student_class' => 'ม.5/1',
        'notification_channels' => 'LINE',
        'status' => 'ใช้งาน'
    ],
    [
        'id' => 5,
        'name' => 'นางสาวอรุณ ภักดี',
        'relation' => 'แม่',
        'phone' => '084-567-8901',
        'email' => 'arun@example.com',
        'line_id' => 'arun345',
        'address' => '345 หมู่ 3 ตำบลสลักได อำเภอเมือง จังหวัดสุรินทร์ 32000',
        'student' => 'นายอานนท์ ภักดี',
        'student_class' => 'ม.5/1',
        'notification_channels' => 'SMS, LINE',
        'status' => 'ใช้งาน'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'parents' => $parents
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
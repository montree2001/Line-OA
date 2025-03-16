<?php
/**
 * students.php - หน้าจัดการข้อมูลนักเรียน
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
$current_page = 'students';
$page_title = 'จัดการข้อมูลนักเรียน';
$page_header = 'จัดการข้อมูลนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มนักเรียนใหม่',
        'icon' => 'person_add',
        'onclick' => 'showAddStudentModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'file_upload',
        'onclick' => 'showImportModal()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม (ดึงจากฐานข้อมูลจริง)
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/students.css'
];

$extra_js = [
    'assets/js/students.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/students_content.php';

// ดึงข้อมูลนักเรียน (จริงๆ ควรดึงจากฐานข้อมูล)
// นี่เป็นตัวอย่างข้อมูล
$students = [
    [
        'id' => 1,
        'student_id' => '16478',
        'name' => 'นายธนกฤต สุขใจ',
        'class' => 'ม.6/2',
        'class_number' => 12,
        'gender' => 'ชาย',
        'birthdate' => '25/05/2551',
        'address' => '123 หมู่ 5 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000',
        'parent_name' => 'นางวันดี สุขใจ',
        'parent_relation' => 'มารดา',
        'parent_phone' => '081-234-5678',
        'advisor' => 'อ.ประสิทธิ์ ดีเลิศ',
        'status' => 'กำลังศึกษา'
    ],
    [
        'id' => 2,
        'student_id' => '16479',
        'name' => 'นางสาวสมหญิง มีสุข',
        'class' => 'ม.5/3',
        'class_number' => 8,
        'gender' => 'หญิง',
        'birthdate' => '15/08/2552',
        'address' => '456 หมู่ 3 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000',
        'parent_name' => 'นายสมชาย มีสุข',
        'parent_relation' => 'บิดา',
        'parent_phone' => '089-876-5432',
        'advisor' => 'อ.วันดี สดใส',
        'status' => 'กำลังศึกษา'
    ],
    [
        'id' => 3,
        'student_id' => '16480',
        'name' => 'นายพิชัย รักเรียน',
        'class' => 'ม.4/1',
        'class_number' => 15,
        'gender' => 'ชาย',
        'birthdate' => '10/03/2553',
        'address' => '789 หมู่ 8 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000',
        'parent_name' => 'นางรักดี รักเรียน',
        'parent_relation' => 'มารดา',
        'parent_phone' => '062-345-6789',
        'advisor' => 'อ.ใจดี มากเมตตา',
        'status' => 'กำลังศึกษา'
    ],
    [
        'id' => 4,
        'student_id' => '16481',
        'name' => 'นางสาววรรณา ชาติไทย',
        'class' => 'ม.5/2',
        'class_number' => 10,
        'gender' => 'หญิง',
        'birthdate' => '05/12/2552',
        'address' => '101 หมู่ 2 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000',
        'parent_name' => 'นายวิชัย ชาติไทย',
        'parent_relation' => 'บิดา',
        'parent_phone' => '098-765-4321',
        'advisor' => 'อ.วิชัย สุขสวัสดิ์',
        'status' => 'กำลังศึกษา'
    ],
    [
        'id' => 5,
        'student_id' => '16482',
        'name' => 'นายมานะ พากเพียร',
        'class' => 'ม.4/3',
        'class_number' => 7,
        'gender' => 'ชาย',
        'birthdate' => '18/07/2553',
        'address' => '202 หมู่ 4 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000',
        'parent_name' => 'นางสายใจ พากเพียร',
        'parent_relation' => 'มารดา',
        'parent_phone' => '085-432-1098',
        'advisor' => 'อ.สุดา ใจงาม',
        'status' => 'กำลังศึกษา'
    ]
];

// ส่งข้อมูลไปยังเทมเพลต (ในทางปฏิบัติจริง ควรมีการจัดการข้อมูลที่ซับซ้อนกว่านี้)
$data = [
    'students' => $students
];

// ประมวลผลการเพิ่ม/แก้ไข/ลบข้อมูลนักเรียน (ถ้ามี)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // ในทางปฏิบัติจริง จะมีการบันทึกข้อมูลลงฐานข้อมูล
                // จำลองการเพิ่มนักเรียนสำเร็จ
                $success_message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'edit':
                // ในทางปฏิบัติจริง จะมีการอัปเดตข้อมูลในฐานข้อมูล
                // จำลองการแก้ไขนักเรียนสำเร็จ
                $success_message = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'delete':
                // ในทางปฏิบัติจริง จะมีการลบข้อมูลในฐานข้อมูล
                // จำลองการลบนักเรียนสำเร็จ
                $success_message = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
                
            case 'import':
                // ในทางปฏิบัติจริง จะมีการนำเข้าข้อมูลจากไฟล์ CSV/Excel
                // จำลองการนำเข้าข้อมูลสำเร็จ
                $success_message = "นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว";
                break;
        }
    }
}

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
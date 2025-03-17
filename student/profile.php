<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'edit_profile';
$page_title = 'STD-Prasat - แก้ไขข้อมูลส่วนตัว';
$page_header = 'แก้ไขข้อมูลส่วนตัว';

// ข้อมูลนักเรียนปัจจุบัน
$student_info = [
    'name' => 'นายเอกชัย รักเรียน',
    'name_eng' => 'Ekachai Ruckhrian',
    'class' => 'ม.6/1',
    'number' => 15,
    'student_id' => 16536,
    'phone' => '089-123-4567',
    'email' => 'ekachai.r@student.prasat.ac.th',
    'line_id' => 'ekachai_line',
    'birth_date' => '15/09/2549',
    'blood_type' => 'O',
    'nationality' => 'ไทย',
    'religion' => 'พุทธ'
];

// รายการชั้นเรียน
$class_list = [
    'ม.4/1', 'ม.4/2', 'ม.4/3', 'ม.4/4',
    'ม.5/1', 'ม.5/2', 'ม.5/3', 'ม.5/4',
    'ม.6/1', 'ม.6/2', 'ม.6/3', 'ม.6/4'
];

// CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/edit-profile.css'
];
$extra_js = [
    'assets/js/edit-profile.js'
];

// เส้นทางเนื้อหา
$content_path = 'pages/edit_profile_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
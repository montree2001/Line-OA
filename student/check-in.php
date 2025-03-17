<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'check-in';
$page_title = 'STD-Prasat - เช็คชื่อเข้าแถว';
$page_header = 'เช็คชื่อเข้าแถว';

// ข้อมูลวันและเวลา
$current_date = date('l, d F Y');
$thai_date = 'วันจันทร์ที่ 16 มีนาคม 2025'; // ให้ใช้ฟังก์ชันเปลี่ยนเป็นภาษาไทยในการใช้งานจริง
$check_in_time_range = '07:00 - 08:30 น.';

// สถานะการเช็คชื่อ (เปิด/ปิด)
$check_in_open = true;

// รวม CSS และ JS
$extra_css = [
    'assets/css/student-checkin.css'
];
$extra_js = [
    'assets/js/student-checkin.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/student_checkin_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
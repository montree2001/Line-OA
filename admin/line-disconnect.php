<?php
/**
 * line-disconnect.php - หน้าจัดการยกเลิกการเชื่อมต่อ LINE
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ข้อมูลเกี่ยวกับผู้ดูแลระบบ
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ฟังก์ชันสำหรับดึงข้อมูลแผนกวิชา
function getDepartments() {
    $conn = getDB();
    
    $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันสำหรับดึงข้อมูลครูที่ปรึกษา
function getAdvisors() {
    $conn = getDB();
    
    $query = "
        SELECT DISTINCT
            t.teacher_id,
            t.title,
            t.first_name,
            t.last_name
        FROM 
            teachers t
        JOIN 
            class_advisors ca ON t.teacher_id = ca.teacher_id
        JOIN 
            classes c ON ca.class_id = c.class_id
        JOIN 
            academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE 
            ay.is_active = 1
        ORDER BY 
            t.first_name, t.last_name
    ";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลสำหรับแสดงผล
$data = [
    'departments' => getDepartments(),
    'advisors' => getAdvisors()
];

// กำหนดตัวแปรสำหรับเทมเพลต
$page_title = "ยกเลิกการเชื่อมต่อ LINE";
$page_header = "ยกเลิกการเชื่อมต่อ LINE";
$current_page = "line_disconnect";
$content_path = "pages/line_disconnect_content.php";

// เพิ่ม CSS และ JS สำหรับหน้านี้
$extra_css = [
    "assets/css/students.css",
    "https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css"
];

$extra_js = [
    "https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js",
    "assets/js/line-disconnect.js"
];

// โหลดเทมเพลต
include "templates/header.php";
include "templates/sidebar.php";
include "templates/main_content.php";
include "templates/footer.php";
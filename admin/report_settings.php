<?php
/**
 * report_settings.php - หน้าตั้งค่ารายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ดึงข้อมูลวันหยุด
function getHolidays() {
    $conn = getDB();
    $query = "SELECT h.*, a.year, a.semester 
              FROM holidays h
              LEFT JOIN academic_years a ON h.academic_year_id = a.academic_year_id
              ORDER BY h.holiday_date DESC";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลผู้ลงนาม
function getSigners() {
    $conn = getDB();
    $query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY position";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลปีการศึกษา
function getAcademicYears() {
    $conn = getDB();
    $query = "SELECT * FROM academic_years ORDER BY year DESC, semester DESC";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDB();
    
    // เพิ่มวันหยุด
    if (isset($_POST['add_holiday'])) {
        $holidayDate = $_POST['holiday_date'];
        $holidayName = $_POST['holiday_name'];
        $holidayType = $_POST['holiday_type'];
        $isRepeating = isset($_POST['is_repeating']) ? 1 : 0;
        $academicYearId = !empty($_POST['academic_year_id']) ? $_POST['academic_year_id'] : null;
        
        $query = "INSERT INTO holidays (holiday_date, holiday_name, holiday_type, is_repeating, academic_year_id, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$holidayDate, $holidayName, $holidayType, $isRepeating, $academicYearId, $_SESSION['user_id']]);
        
        $_SESSION['success_message'] = "เพิ่มวันหยุดเรียบร้อยแล้ว";
        header('Location: report_settings.php');
        exit;
    }
    
    // ลบวันหยุด
    if (isset($_POST['delete_holiday'])) {
        $holidayId = $_POST['holiday_id'];
        
        $query = "DELETE FROM holidays WHERE holiday_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$holidayId]);
        
        $_SESSION['success_message'] = "ลบวันหยุดเรียบร้อยแล้ว";
        header('Location: report_settings.php');
        exit;
    }
    
    // เพิ่ม/แก้ไขผู้ลงนาม
    if (isset($_POST['save_signer'])) {
        $signerId = $_POST['signer_id'] ?? null;
        $position = $_POST['position'];
        $title = $_POST['title'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        
        if ($signerId) {
            // แก้ไขผู้ลงนาม
            $query = "UPDATE report_signers 
                      SET position = ?, title = ?, first_name = ?, last_name = ? 
                      WHERE signer_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$position, $title, $firstName, $lastName, $signerId]);
            
            $_SESSION['success_message'] = "แก้ไขผู้ลงนามเรียบร้อยแล้ว";
        } else {
            // เพิ่มผู้ลงนาม
            $query = "INSERT INTO report_signers (position, title, first_name, last_name) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$position, $title, $firstName, $lastName]);
            
            $_SESSION['success_message'] = "เพิ่มผู้ลงนามเรียบร้อยแล้ว";
        }
        
        header('Location: report_settings.php');
        exit;
    }
    
    // ลบผู้ลงนาม
    if (isset($_POST['delete_signer'])) {
        $signerId = $_POST['signer_id'];
        
        $query = "UPDATE report_signers SET is_active = 0 WHERE signer_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$signerId]);
        
        $_SESSION['success_message'] = "ลบผู้ลงนามเรียบร้อยแล้ว";
        header('Location: report_settings.php');
        exit;
    }
    
    // อัพโหลดโลโก้
    if (isset($_POST['upload_logo'])) {
        $targetDir = "../uploads/logo/";
        
        // ตรวจสอบว่ามีโฟลเดอร์หรือไม่ ถ้าไม่มีให้สร้าง
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetFile = $targetDir . "school_logo.png";
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($_FILES["logo_file"]["name"], PATHINFO_EXTENSION));
        
        // ตรวจสอบว่าเป็นรูปภาพหรือไม่
        $check = getimagesize($_FILES["logo_file"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error_message'] = "ไฟล์ที่อัพโหลดไม่ใช่รูปภาพ";
            $uploadOk = 0;
        }
        
        // ตรวจสอบประเภทไฟล์
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $_SESSION['error_message'] = "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG และ GIF เท่านั้น";
            $uploadOk = 0;
        }
        
        // ตรวจสอบขนาดไฟล์
        if ($_FILES["logo_file"]["size"] > 5000000) {
            $_SESSION['error_message'] = "ไฟล์มีขนาดใหญ่เกินไป (ไม่เกิน 5MB)";
            $uploadOk = 0;
        }
        
        // ดำเนินการอัพโหลด
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["logo_file"]["tmp_name"], $targetFile)) {
                // บันทึกการตั้งค่า
                $query = "INSERT INTO system_settings (setting_key, setting_value, setting_description, setting_group, updated_by) 
                          VALUES ('report_logo', 'school_logo.png', 'โลโก้สำหรับรายงาน', 'report', ?) 
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_SESSION['user_id']]);
                
                $_SESSION['success_message'] = "อัพโหลดโลโก้เรียบร้อยแล้ว";
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัพโหลดไฟล์";
            }
        }
        
        header('Location: report_settings.php');
        exit;
    }
}

// ดึงข้อมูล
$holidays = getHolidays();
$signers = getSigners();
$academicYears = getAcademicYears();

// ดึงข้อมูลโลโก้
$conn = getDB();
$query = "SELECT setting_value FROM system_settings WHERE setting_key = 'report_logo'";
$stmt = $conn->query($query);
$logoFile = $stmt->fetchColumn();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'report_settings';
$page_title = 'ตั้งค่ารายงาน';
$page_header = 'ตั้งค่ารายงานการเข้าแถว';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/reports.css',
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css'
];

$extra_js = [
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js',
    'assets/js/report_settings.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/report_settings_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
<?php
/**
 * api/import_teachers.php - API สำหรับนำเข้าข้อมูลครูจากไฟล์ Excel
 */
/* 
// ตรวจสอบการส่งคำขอแบบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
} */
/* 
// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
} */

// โหลดไฟล์ที่จำเป็น
require_once '../../db_connect.php';
require_once '../classes/ImportTeachers.php';

// ดำเนินการนำเข้าข้อมูล
try {
    $importer = new ImportTeachers();
    
    // ตรวจสอบไฟล์
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("ไม่พบไฟล์หรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์");
    }
    
    // ตัวเลือกการนำเข้า
    $overwrite = isset($_POST['update_existing']) && $_POST['update_existing'] === 'on';
    
    // นำเข้าข้อมูล
    $result = $importer->import($_FILES['import_file'], $overwrite);
    
    if ($result['success']) {
        // ตั้งค่าข้อความสำเร็จและ redirect กลับไปยังหน้าครูที่ปรึกษา
        $_SESSION['success_message'] = $result['message'];
    } else {
        // ตั้งค่าข้อความข้อผิดพลาดและ redirect กลับไปยังหน้าครูที่ปรึกษา
        $_SESSION['error_message'] = $result['message'];
        if (!empty($result['errors'])) {
            $_SESSION['import_errors'] = $result['errors'];
        }
    }
    
    // Redirect กลับไปยังหน้าครูที่ปรึกษา
    header('Location: ../teachers.php');
    exit;
    
} catch (Exception $e) {
    // ตั้งค่าข้อความข้อผิดพลาดและ redirect กลับไปยังหน้าครูที่ปรึกษา
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . $e->getMessage();
    
    // Redirect กลับไปยังหน้าครูที่ปรึกษา
    header('Location: ../teachers.php');
    exit;
}
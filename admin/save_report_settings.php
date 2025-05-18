<?php
/**
 * save_report_settings.php - บันทึกการตั้งค่ารายงาน
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'ไม่สามารถบันทึกการตั้งค่าได้: วิธีการส่งข้อมูลไม่ถูกต้อง';
    header('Location: attendance_report.php');
    exit;
}

try {
    // เริ่ม transaction
    $conn->beginTransaction();
    
    // บันทึกข้อมูลโลโก้ (ถ้ามีการอัปโหลด)
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] == 0) {
        // ตรวจสอบประเภทไฟล์
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['school_logo']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น (JPG, PNG, GIF)');
        }
        
        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
        if ($_FILES['school_logo']['size'] > 2097152) {
            throw new Exception('ขนาดไฟล์ต้องไม่เกิน 2MB');
        }
        
        // สร้างโฟลเดอร์สำหรับเก็บไฟล์ (ถ้ายังไม่มี)
        $upload_dir = '../uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // สร้างชื่อไฟล์ใหม่
        $file_extension = pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'school_logo_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // อัปโหลดไฟล์
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_path)) {
            // บันทึกที่อยู่ไฟล์ลงในฐานข้อมูล
            $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'school_logo'";
            $stmt = $conn->prepare($query);
            $stmt->execute([$new_filename]);
        } else {
            throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
        }
    }
    
    // บันทึกชื่อสถานศึกษา
    if (isset($_POST['school_name']) && !empty($_POST['school_name'])) {
        $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'school_name'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_POST['school_name']]);
    }
    
    // บันทึกข้อมูลผู้ลงนาม
    if (isset($_POST['signer_position']) && isset($_POST['signer_title']) && 
        isset($_POST['signer_first_name']) && isset($_POST['signer_last_name'])) {
        
        // ลบข้อมูลผู้ลงนามเดิม
        $query = "DELETE FROM report_signers";
        $conn->exec($query);
        
        // เพิ่มข้อมูลผู้ลงนามใหม่
        $query = "INSERT INTO report_signers (position, title, first_name, last_name, is_active) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($query);
        
        for ($i = 0; $i < count($_POST['signer_position']); $i++) {
            // ข้ามบันทึกถ้าไม่มีข้อมูลตำแหน่ง
            if (empty($_POST['signer_position'][$i])) continue;
            
            $stmt->execute([
                $_POST['signer_position'][$i],
                $_POST['signer_title'][$i],
                $_POST['signer_first_name'][$i],
                $_POST['signer_last_name'][$i]
            ]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = 'บันทึกการตั้งค่ารายงานเรียบร้อยแล้ว';
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollBack();
    $_SESSION['error_message'] = 'ไม่สามารถบันทึกการตั้งค่าได้: ' . $e->getMessage();
}

// กลับไปยังหน้ารายงาน
header('Location: attendance_report.php');
exit;
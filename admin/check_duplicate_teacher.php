<?php
/**
 * check_duplicate_teacher.php - ตรวจสอบข้อมูลซ้ำของครู
 * 
 * ไฟล์นี้ใช้สำหรับตรวจสอบข้อมูลซ้ำของครู เช่น เลขบัตรประชาชน
 * ผ่าน AJAX call จาก JavaScript
 */

// เริ่ม session
session_start();

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

// โหลดไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ค่าเริ่มต้นสำหรับการตอบกลับ
$response = ['duplicate' => false];

// ตรวจสอบเลขบัตรประชาชนซ้ำ
if (isset($_POST['national_id']) && !empty($_POST['national_id'])) {
    $nationalId = trim($_POST['national_id']);
    $excludeId = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;
    
    try {
        $db = getDB();
        
        // สร้าง query
        $sql = "SELECT COUNT(*) FROM teachers WHERE national_id = :national_id";
        if ($excludeId > 0) {
            $sql .= " AND teacher_id != :exclude_id";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':national_id', $nationalId);
        
        if ($excludeId > 0) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        // ถ้าพบข้อมูลซ้ำ
        if ($count > 0) {
            $response['duplicate'] = true;
        }
        
    } catch (PDOException $e) {
        // กรณีเกิดข้อผิดพลาด
        $response['error'] = $e->getMessage();
    }
}

// ส่งข้อมูลกลับในรูปแบบ JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
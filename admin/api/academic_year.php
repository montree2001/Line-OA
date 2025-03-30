<?php
// api/academic-years.php - API endpoint สำหรับการจัดการปีการศึกษา

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');

// รวม database connection
require_once '../db_connect.php';

// ตรวจสอบวิธีการร้องขอ (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

// จัดการกับการร้องขอตามวิธีที่ส่งมา
switch ($method) {
    case 'GET':
        // ดึงข้อมูลปีการศึกษา
        getAcademicYears();
        break;
    case 'POST':
        // เพิ่ม/อัปเดตปีการศึกษา
        saveAcademicYear();
        break;
    default:
        // จัดการวิธีที่ไม่รองรับ
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// ฟังก์ชันสำหรับดึงข้อมูลปีการศึกษา
function getAcademicYears() {
    $db = getDB();
    
    try {
        // ดึงข้อมูลปีการศึกษาทั้งหมดเรียงตามปีและภาคเรียน
        $stmt = $db->prepare("SELECT * FROM academic_years ORDER BY year DESC, semester ASC");
        $stmt->execute();
        
        $academicYears = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'academicYears' => $academicYears]);
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลปีการศึกษา: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันสำหรับบันทึกปีการศึกษา
function saveAcademicYear() {
    $db = getDB();
    
    // รับข้อมูล JSON ที่ส่งมา
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['year']) || !isset($data['semester']) || !isset($data['start_date']) || !isset($data['end_date'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน กรุณาระบุปีการศึกษา ภาคเรียน วันเริ่มต้น และวันสิ้นสุด']);
        return;
    }
    
    try {
        // เริ่ม transaction
        $db->beginTransaction();
        
        // หากตั้งเป็นปีการศึกษาที่ใช้งาน ให้ยกเลิกปีการศึกษาอื่น
        if (isset($data['is_active']) && $data['is_active']) {
            $stmt = $db->prepare("UPDATE academic_years SET is_active = 0");
            $stmt->execute();
        }
        
        // ตรวจสอบว่ามีปีการศึกษานี้อยู่แล้วหรือไม่
        $stmt = $db->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? AND semester = ?");
        $stmt->execute([$data['year'], $data['semester']]);
        
        if ($row = $stmt->fetch()) {
            // อัปเดตปีการศึกษาที่มีอยู่
            $stmt = $db->prepare("UPDATE academic_years SET 
                           start_date = ?, 
                           end_date = ?, 
                           is_active = ?, 
                           required_attendance_days = ? 
                           WHERE academic_year_id = ?");
            $stmt->execute([
                $data['start_date'],
                $data['end_date'],
                isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 0,
                isset($data['required_attendance_days']) ? $data['required_attendance_days'] : 80,
                $row['academic_year_id']
            ]);
            
            $academicYearId = $row['academic_year_id'];
            $message = 'อัปเดตปีการศึกษาเรียบร้อยแล้ว';
        } else {
            // เพิ่มปีการศึกษาใหม่
            $stmt = $db->prepare("INSERT INTO academic_years 
                           (year, semester, start_date, end_date, is_active, required_attendance_days) 
                           VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['year'],
                $data['semester'],
                $data['start_date'],
                $data['end_date'],
                isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 0,
                isset($data['required_attendance_days']) ? $data['required_attendance_days'] : 80
            ]);
            
            $academicYearId = $db->lastInsertId();
            $message = 'เพิ่มปีการศึกษาใหม่เรียบร้อยแล้ว';
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message, 
            'academic_year_id' => $academicYearId
        ]);
    } catch (PDOException $e) {
        // Rollback transaction เมื่อเกิดข้อผิดพลาด
        $db->rollBack();
        
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกปีการศึกษา: ' . $e->getMessage()]);
    }
}
<?php
/**
 * process_qr_attendance.php - API สำหรับประมวลผล QR Code ที่สแกนได้
 */

// เริ่ม session และตรวจสอบการเข้าถึง
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// รับข้อมูล JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

$action = $data['action'] ?? '';

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    switch ($action) {
        case 'scan_qr':
            echo json_encode(processQRScan($conn, $data));
            break;
            
        case 'record_attendance':
            echo json_encode(recordAttendance($conn, $data));
            break;
            
        case 'update_attendance':
            echo json_encode(updateAttendance($conn, $data));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'action ไม่ถูกต้อง']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in process_qr_attendance.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()]);
}

/**
 * ฟังก์ชันประมวลผลการสแกน QR Code
 */
function processQRScan($conn, $data) {
    try {
        $qr_data_string = $data['qr_data'] ?? '';
        
        // แปลงข้อมูล QR Code
        $qr_data = json_decode($qr_data_string, true);
        
        if (!$qr_data) {
            return ['success' => false, 'error' => 'QR Code ไม่ถูกต้อง'];
        }
        
        // ตรวจสอบประเภท QR Code
        if ($qr_data['type'] !== 'student_attendance') {
            return ['success' => false, 'error' => 'QR Code นี้ไม่ใช่สำหรับการเช็คชื่อ'];
        }
        
        $student_id = $qr_data['student_id'] ?? 0;
        $token = $qr_data['token'] ?? '';
        $for_date = $qr_data['for_date'] ?? date('Y-m-d');
        
        if (!$student_id || !$token) {
            return ['success' => false, 'error' => 'ข้อมูล QR Code ไม่ครบถ้วน'];
        }
        
        // ตรวจสอบ QR Code ในฐานข้อมูล
        $stmt = $conn->prepare("
            SELECT qc.*, s.student_code, s.title, u.first_name, u.last_name,
                   c.level, c.group_number, d.department_name
            FROM qr_codes qc
            JOIN students s ON qc.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE qc.student_id = ? AND qc.is_active = 1 AND qc.valid_until > NOW()
            ORDER BY qc.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$student_id]);
        $qr_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$qr_record) {
            return ['success' => false, 'error' => 'QR Code หมดอายุหรือไม่ถูกต้อง'];
        }
        
        // ตรวจสอบ token
        $stored_qr_data = json_decode($qr_record['qr_code_data'], true);
        if ($stored_qr_data['token'] !== $token) {
            return ['success' => false, 'error' => 'QR Code ไม่ถูกต้อง'];
        }
        
        // ตรวจสอบว่าเช็คชื่อแล้วหรือยัง
        $stmt = $conn->prepare("
            SELECT * FROM attendance 
            WHERE student_id = ? AND date = ?
        ");
        $stmt->execute([$student_id, $for_date]);
        $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_attendance) {
            return [
                'success' => false, 
                'error' => 'นักเรียนคนนี้เช็คชื่อแล้ววันนี้',
                'attendance_info' => [
                    'status' => $existing_attendance['attendance_status'],
                    'time' => $existing_attendance['check_time'],
                    'method' => $existing_attendance['check_method']
                ]
            ];
        }
        
        // คืนข้อมูลนักเรียนสำหรับยืนยัน
        return [
            'success' => true,
            'student' => [
                'student_id' => $qr_record['student_id'],
                'student_code' => $qr_record['student_code'],
                'title' => $qr_record['title'],
                'first_name' => $qr_record['first_name'],
                'last_name' => $qr_record['last_name'],
                'class' => $qr_record['level'] . '/' . $qr_record['group_number'] . ' ' . $qr_record['department_name'],
                'level' => $qr_record['level'],
                'group_number' => $qr_record['group_number'],
                'department_name' => $qr_record['department_name']
            ],
            'qr_info' => [
                'qr_code_id' => $qr_record['qr_code_id'],
                'generated_at' => $qr_record['created_at'],
                'expires_at' => $qr_record['valid_until']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error in processQRScan: " . $e->getMessage());
        return ['success' => false, 'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * ฟังก์ชันบันทึกการเช็คชื่อ
 */
function recordAttendance($conn, $data) {
    try {
        $student_id = $data['student_id'] ?? 0;
        $method = $data['method'] ?? 'QR_Code';
        $status = $data['status'] ?? 'present';
        $date = $data['date'] ?? date('Y-m-d');
        $checker_id = $_SESSION['user_id'];
        $remarks = $data['remarks'] ?? '';
        
        if (!$student_id) {
            return ['success' => false, 'error' => 'ไม่ระบุรหัสนักเรียน'];
        }
        
        // ตรวจสอบว่าเช็คชื่อแล้วหรือยัง
        $stmt = $conn->prepare("
            SELECT * FROM attendance 
            WHERE student_id = ? AND date = ?
        ");
        $stmt->execute([$student_id, $date]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            return ['success' => false, 'error' => 'นักเรียนคนนี้เช็คชื่อแล้ววันนี้'];
        }
        
        // ดึงข้อมูลปีการศึกษา
        $stmt = $conn->prepare("
            SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
        ");
        $stmt->execute();
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'] ?? 1;
        
        // บันทึกการเช็คชื่อ
        $stmt = $conn->prepare("
            INSERT INTO attendance 
            (student_id, academic_year_id, date, attendance_status, check_method, 
             checker_user_id, check_time, created_at, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        
        $check_time = date('H:i:s');
        $stmt->execute([
            $student_id,
            $academic_year_id,
            $date,
            $status,
            $method,
            $checker_id,
            $check_time,
            $remarks
        ]);
        
        // ปิดการใช้งาน QR Code ที่ใช้แล้ว (ถ้าเป็นการเช็คชื่อด้วย QR Code)
        if ($method === 'QR_Code') {
            $stmt = $conn->prepare("
                UPDATE qr_codes 
                SET is_active = 0 
                WHERE student_id = ? AND is_active = 1
            ");
            $stmt->execute([$student_id]);
        }
        
        // ดึงข้อมูลนักเรียนสำหรับส่งกลับ
        $stmt = $conn->prepare("
            SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                   c.level, c.group_number, d.department_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE s.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => 'บันทึกการเช็คชื่อสำเร็จ',
            'student' => array_merge($student, [
                'attendance_status' => $status,
                'check_time' => $check_time,
                'check_method' => $method
            ])
        ];
        
    } catch (Exception $e) {
        error_log("Error in recordAttendance: " . $e->getMessage());
        return ['success' => false, 'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

/**
 * ฟังก์ชันแก้ไขการเช็คชื่อ
 */
function updateAttendance($conn, $data) {
    try {
        $attendance_id = $data['attendance_id'] ?? 0;
        $status = $data['status'] ?? 'present';
        $remarks = $data['remarks'] ?? '';
        $updater_id = $_SESSION['user_id'];
        
        if (!$attendance_id) {
            return ['success' => false, 'error' => 'ไม่ระบุรหัสการเช็คชื่อ'];
        }
        
        // อัปเดตการเช็คชื่อ
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET attendance_status = ?, remarks = ?, 
                updated_at = NOW(), updated_by = ?
            WHERE attendance_id = ?
        ");
        $stmt->execute([$status, $remarks, $updater_id, $attendance_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'ไม่พบข้อมูลการเช็คชื่อที่ต้องการแก้ไข'];
        }
        
        return [
            'success' => true,
            'message' => 'แก้ไขการเช็คชื่อสำเร็จ'
        ];
        
    } catch (Exception $e) {
        error_log("Error in updateAttendance: " . $e->getMessage());
        return ['success' => false, 'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}
?>
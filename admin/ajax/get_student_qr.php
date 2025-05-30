<?php
/**
 * get_student_qr.php - ไฟล์สำหรับดึงข้อมูลนักเรียนผ่าน AJAX (แก้ไขใหม่)
 * รองรับการค้นหาด้วย student_id และ student_code
 * ปรับปรุงให้รองรับรูปแบบ QR Code ใหม่
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

// รับพารามิเตอร์
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$student_code = isset($_GET['student_code']) ? trim($_GET['student_code']) : '';

if (!$student_id && !$student_code) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุรหัสนักเรียน']);
    exit;
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // สร้างคำสั่ง SQL ตามเงื่อนไข
    $sql = "
        SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
               c.level, c.group_number, d.department_name, u.profile_picture
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.status = 'กำลังศึกษา'
    ";
    
    $params = [];
    
    if ($student_id) {
        $sql .= " AND s.student_id = ?";
        $params[] = $student_id;
    } else {
        $sql .= " AND s.student_code = ?";
        $params[] = $student_code;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // จัดเตรียมข้อมูลนักเรียนสำหรับส่งกลับ
    $class_display = '';
    if ($student['level'] && $student['group_number'] && $student['department_name']) {
        $class_display = $student['level'] . '/' . $student['group_number'] . ' ' . $student['department_name'];
    }
    
    $student_data = [
        'student_id' => intval($student['student_id']),
        'student_code' => $student['student_code'],
        'title' => $student['title'],
        'first_name' => $student['first_name'],
        'last_name' => $student['last_name'],
        'full_name' => trim(($student['title'] ?? '') . $student['first_name'] . ' ' . $student['last_name']),
        'class' => $class_display,
        'level' => $student['level'],
        'group_number' => $student['group_number'],
        'department_name' => $student['department_name'],
        'profile_picture' => $student['profile_picture']
    ];
    
    // ดึงข้อมูลการเช็คชื่อวันนี้ (ถ้ามี)
    $today = date('Y-m-d');
    $attendance_sql = "
        SELECT a.attendance_id, a.attendance_status, a.check_time, a.check_method, a.remarks
        FROM attendance a
        WHERE a.student_id = ? AND a.date = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($attendance_sql);
    $stmt->execute([$student['student_id'], $today]);
    
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        $student_data['attendance'] = [
            'id' => $attendance['attendance_id'],
            'status' => $attendance['attendance_status'],
            'check_time' => $attendance['check_time'] ? date('H:i', strtotime($attendance['check_time'])) : null,
            'method' => $attendance['check_method'],
            'remarks' => $attendance['remarks'],
            'already_checked' => true
        ];
    } else {
        $student_data['attendance'] = [
            'status' => null,
            'check_time' => null,
            'method' => null,
            'remarks' => null,
            'already_checked' => false
        ];
    }
    
    // ดึงข้อมูล QR Code ที่ยังใช้งานได้ (ถ้ามี)
    $qr_sql = "
        SELECT qr_code_id, qr_code_data, valid_until, is_active
        FROM qr_codes 
        WHERE student_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($qr_sql);
    $stmt->execute([$student['student_id']]);
    
    $qr_code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($qr_code) {
        $qr_data = json_decode($qr_code['qr_code_data'], true);
        
        $student_data['qr_code'] = [
            'id' => $qr_code['qr_code_id'],
            'data' => $qr_data,
            'valid_until' => $qr_code['valid_until'],
            'is_active' => $qr_code['is_active'],
            'has_valid_qr' => true
        ];
        
        // ตรวจสอบว่า QR Code เป็นรูปแบบใหม่หรือไม่
        if (!$qr_data || !isset($qr_data['type']) || $qr_data['type'] !== 'student_attendance') {
            $student_data['qr_code']['needs_update'] = true;
        }
    } else {
        $student_data['qr_code'] = [
            'has_valid_qr' => false
        ];
    }
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode([
        'success' => true, 
        'student' => $student_data,
        'debug_info' => [
            'student_id' => $student['student_id'],
            'search_by' => $student_id ? 'id' : 'code',
            'today' => $today,
            'has_attendance' => !empty($attendance),
            'has_qr' => !empty($qr_code)
        ]
    ]);
    
} catch (PDOException $e) {
    // Log error สำหรับ debug
    error_log("Database error in get_student_qr.php: " . $e->getMessage());
    error_log("SQL: " . ($sql ?? 'N/A'));
    error_log("Params: " . json_encode($params ?? []));
    
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    // Log error สำหรับ debug
    error_log("General error in get_student_qr.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>
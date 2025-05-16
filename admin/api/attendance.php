<?php
/**
 * api/attendance.php - API สำหรับจัดการข้อมูลการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการร้องขอ
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // การร้องขอข้อมูล GET
    $action = $_GET['action'] ?? '';
    
    if ($action === 'list') {
        // รายการการเข้าแถว
        $student_id = $_GET['student_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $class_id = $_GET['class_id'] ?? null;
        
        if ($student_id) {
            // ดึงรายการการเข้าแถวของนักเรียนคนเดียว
            getStudentAttendance($student_id, $date);
        } elseif ($class_id) {
            // ดึงรายการการเข้าแถวของชั้นเรียน
            getClassAttendance($class_id, $date);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ student_id หรือ class_id']);
        }
    } elseif ($action === 'summary') {
        // สรุปการเข้าแถวของนักเรียน
        $student_id = $_GET['student_id'] ?? null;
        $academic_year_id = $_GET['academic_year_id'] ?? null;
        
        if ($student_id && $academic_year_id) {
            getAttendanceSummary($student_id, $academic_year_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ student_id และ academic_year_id']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่รู้จักการกระทำ']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล POST
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if ($data === null && empty($_POST)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง']);
        exit;
    }
    
    // ใช้ข้อมูลจาก $_POST หากไม่มีข้อมูล JSON
    if (empty($data)) {
        $data = $_POST;
    }
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check_in') {
        // บันทึกการเข้าแถว
        $student_id = $data['student_id'] ?? null;
        $status = $data['status'] ?? 'present';
        $check_method = $data['check_method'] ?? 'Manual';
        $location_lat = $data['location_lat'] ?? null;
        $location_lng = $data['location_lng'] ?? null;
        $date = $data['date'] ?? date('Y-m-d');
        $checker_id = $_SESSION['user_id'];
        
        if ($student_id) {
            recordAttendance($student_id, $status, $check_method, $location_lat, $location_lng, $date, $checker_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ student_id']);
        }
    } elseif ($action === 'bulk_check') {
        // บันทึกการเข้าแถวแบบกลุ่ม
        $student_ids = $data['student_ids'] ?? [];
        $status = $data['status'] ?? 'present';
        $check_method = $data['check_method'] ?? 'Manual';
        $date = $data['date'] ?? date('Y-m-d');
        $checker_id = $_SESSION['user_id'];
        
        if (!empty($student_ids) && is_array($student_ids)) {
            bulkRecordAttendance($student_ids, $status, $check_method, $date, $checker_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ student_ids เป็นอาร์เรย์']);
        }
    } elseif ($action === 'get_report') {
        // ดึงข้อมูลสำหรับรายงาน
        $class_id = $data['class_id'] ?? null;
        $week_number = $data['week_number'] ?? null;
        $start_date = $data['start_date'] ?? null;
        $end_date = $data['end_date'] ?? null;
        $academic_year_id = $data['academic_year_id'] ?? null;
        
        if ($class_id && $start_date && $end_date) {
            getAttendanceReport($class_id, $week_number, $start_date, $end_date, $academic_year_id);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ต้องระบุ class_id, start_date และ end_date']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่รู้จักการกระทำ']);
    }
}

// ฟังก์ชันดึงข้อมูลการเข้าแถวของนักเรียน
function getStudentAttendance($student_id, $date = null) {
    $db = getDB();
    $params = [$student_id];
    
    $sql = "SELECT a.*, u.first_name, u.last_name, s.student_code, s.title
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE a.student_id = ?";
    
    if ($date) {
        $sql .= " AND a.date = ?";
        $params[] = $date;
    }
    
    $sql .= " ORDER BY a.date DESC, a.check_time DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $attendance]);
}

// ฟังก์ชันดึงข้อมูลการเข้าแถวของชั้นเรียน
function getClassAttendance($class_id, $date = null) {
    $db = getDB();
    $params = [$class_id];
    
    $sql = "SELECT a.*, u.first_name, u.last_name, s.student_code, s.title
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN attendance a ON s.student_id = a.student_id";
    
    if ($date) {
        $sql .= " AND a.date = ?";
        $params[] = $date;
    }
    
    $sql .= " WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
              ORDER BY s.student_code, a.date DESC, a.check_time DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $attendance]);
}

// ฟังก์ชันดึงข้อมูลสรุปการเข้าแถวของนักเรียน
function getAttendanceSummary($student_id, $academic_year_id) {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียน
    $sql = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                   c.class_id, c.level, c.group_number, d.department_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN departments d ON c.department_id = d.department_id
            WHERE s.student_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        return;
    }
    
    // ดึงข้อมูลสรุปการเข้าแถว
    $sql = "SELECT COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) AS present_count,
                   COUNT(CASE WHEN attendance_status = 'absent' THEN 1 END) AS absent_count,
                   COUNT(CASE WHEN attendance_status = 'late' THEN 1 END) AS late_count,
                   COUNT(CASE WHEN attendance_status = 'leave' THEN 1 END) AS leave_count,
                   COUNT(*) AS total_count
            FROM attendance
            WHERE student_id = ? AND academic_year_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id, $academic_year_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลปีการศึกษา
    $sql = "SELECT required_attendance_days FROM academic_years WHERE academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$academic_year_id]);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลล่าสุดจากตาราง student_academic_records
    $sql = "SELECT total_attendance_days, total_absence_days, passed_activity
            FROM student_academic_records
            WHERE student_id = ? AND academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id, $academic_year_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถว
    $attendance_rate = 0;
    if (($summary['present_count'] + $summary['absent_count'] + $summary['late_count'] + $summary['leave_count']) > 0) {
        $attendance_rate = (($summary['present_count'] + $summary['late_count']) / 
                           ($summary['present_count'] + $summary['absent_count'] + $summary['late_count'])) * 100;
    }
    
    $result = [
        'student' => $student,
        'summary' => $summary,
        'academic_year' => $academic_year,
        'record' => $record,
        'attendance_rate' => round($attendance_rate, 2)
    ];
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $result]);
}

// ฟังก์ชันบันทึกการเข้าแถว
function recordAttendance($student_id, $status, $check_method, $location_lat, $location_lng, $date, $checker_id) {
    $db = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $db->query($sql);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // ตรวจสอบว่ามีข้อมูลการเข้าแถวของวันนี้แล้วหรือไม่
    $sql = "SELECT attendance_id FROM attendance 
            WHERE student_id = ? AND date = ? AND academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id, $date, $academic_year['academic_year_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // อัปเดตข้อมูลที่มีอยู่
        $sql = "UPDATE attendance 
                SET attendance_status = ?, check_method = ?, checker_user_id = ?,
                    location_lat = ?, location_lng = ?, check_time = NOW()
                WHERE attendance_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$status, $check_method, $checker_id, $location_lat, $location_lng, $existing['attendance_id']]);
        
        // บันทึกประวัติการแก้ไข
        $sql = "INSERT INTO attendance_retroactive_history 
                (attendance_id, student_id, retroactive_date, retroactive_status, retroactive_reason, created_by)
                VALUES (?, ?, ?, ?, 'แก้ไขโดยเจ้าหน้าที่', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$existing['attendance_id'], $student_id, $date, $status, $checker_id]);
        
        $result = ['updated' => true, 'attendance_id' => $existing['attendance_id']];
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO attendance 
                (student_id, academic_year_id, date, attendance_status, check_method, 
                checker_user_id, location_lat, location_lng, check_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $student_id, $academic_year['academic_year_id'], $date, $status, 
            $check_method, $checker_id, $location_lat, $location_lng
        ]);
        
        $attendance_id = $db->lastInsertId();
        $result = ['updated' => false, 'attendance_id' => $attendance_id];
    }
    
    // อัปเดตสถิติในตาราง student_academic_records
    updateStudentAcademicRecord($student_id, $academic_year['academic_year_id']);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $result]);
}

// ฟังก์ชันบันทึกการเข้าแถวแบบกลุ่ม
function bulkRecordAttendance($student_ids, $status, $check_method, $date, $checker_id) {
    $db = getDB();
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $db->query($sql);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    $results = [];
    $db->beginTransaction();
    
    try {
        foreach ($student_ids as $student_id) {
            // ตรวจสอบว่ามีข้อมูลการเข้าแถวของวันนี้แล้วหรือไม่
            $sql = "SELECT attendance_id FROM attendance 
                    WHERE student_id = ? AND date = ? AND academic_year_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$student_id, $date, $academic_year['academic_year_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // อัปเดตข้อมูลที่มีอยู่
                $sql = "UPDATE attendance 
                        SET attendance_status = ?, check_method = ?, checker_user_id = ?,
                            check_time = NOW()
                        WHERE attendance_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$status, $check_method, $checker_id, $existing['attendance_id']]);
                
                // บันทึกประวัติการแก้ไข
                $sql = "INSERT INTO attendance_retroactive_history 
                        (attendance_id, student_id, retroactive_date, retroactive_status, retroactive_reason, created_by)
                        VALUES (?, ?, ?, ?, 'แก้ไขแบบกลุ่มโดยเจ้าหน้าที่', ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$existing['attendance_id'], $student_id, $date, $status, $checker_id]);
                
                $results[$student_id] = ['updated' => true, 'attendance_id' => $existing['attendance_id']];
            } else {
                // เพิ่มข้อมูลใหม่
                $sql = "INSERT INTO attendance 
                        (student_id, academic_year_id, date, attendance_status, check_method, 
                        checker_user_id, check_time)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $student_id, $academic_year['academic_year_id'], $date, $status, 
                    $check_method, $checker_id
                ]);
                
                $attendance_id = $db->lastInsertId();
                $results[$student_id] = ['updated' => false, 'attendance_id' => $attendance_id];
            }
            
            // อัปเดตสถิติในตาราง student_academic_records
            updateStudentAcademicRecord($student_id, $academic_year['academic_year_id']);
        }
        
        $db->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (Exception $e) {
        $db->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
}

// ฟังก์ชันอัปเดตสถิติการเข้าแถวในตาราง student_academic_records
function updateStudentAcademicRecord($student_id, $academic_year_id) {
    $db = getDB();
    
    // ดึงข้อมูลการเข้าแถวทั้งหมดของนักเรียน
    $sql = "SELECT COUNT(CASE WHEN attendance_status = 'present' OR attendance_status = 'late' THEN 1 END) AS present_count,
                   COUNT(CASE WHEN attendance_status = 'absent' THEN 1 END) AS absent_count
            FROM attendance
            WHERE student_id = ? AND academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id, $academic_year_id]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่ามีข้อมูลในตาราง student_academic_records หรือไม่
    $sql = "SELECT record_id FROM student_academic_records 
            WHERE student_id = ? AND academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id, $academic_year_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลชั้นเรียนของนักเรียน
    $sql = "SELECT current_class_id FROM students WHERE student_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงจำนวนวันที่ต้องมาเข้าแถว
    $sql = "SELECT required_attendance_days FROM academic_years WHERE academic_year_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$academic_year_id]);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณสถานะการผ่านกิจกรรม
    $passed = null;
    if ($attendance['present_count'] + $attendance['absent_count'] > 0) {
        $rate = ($attendance['present_count'] / ($attendance['present_count'] + $attendance['absent_count'])) * 100;
        $required_rate = 80; // อัตราการเข้าแถวที่ต้องการ (%)
        
        if ($rate >= $required_rate) {
            $passed = 1;
        } else {
            $passed = 0;
        }
    }
    
    if ($record) {
        // อัปเดตข้อมูลที่มีอยู่
        $sql = "UPDATE student_academic_records 
                SET total_attendance_days = ?, total_absence_days = ?, passed_activity = ?
                WHERE record_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $attendance['present_count'], $attendance['absent_count'], $passed, $record['record_id']
        ]);
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO student_academic_records 
                (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, passed_activity)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $student_id, $academic_year_id, $student['current_class_id'],
            $attendance['present_count'], $attendance['absent_count'], $passed
        ]);
    }
}

// ฟังก์ชันดึงข้อมูลสำหรับรายงานการเข้าแถว
function getAttendanceReport($class_id, $week_number, $start_date, $end_date, $academic_year_id) {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียนในห้องเรียน
    $sql = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลการเข้าแถวของนักเรียนในช่วงวันที่กำหนด
    $week_days = getDaysInRange($start_date, $end_date);
    
    foreach ($students as &$student) {
        $student['attendances'] = [];
        
        // ดึงข้อมูลการเข้าแถวของนักเรียนแต่ละคน
        $sql = "SELECT date, attendance_status, remarks
                FROM attendance
                WHERE student_id = ? AND date BETWEEN ? AND ? AND academic_year_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$student['student_id'], $start_date, $end_date, $academic_year_id]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบข้อมูลการเข้าแถวตามวัน
        foreach ($attendances as $attendance) {
            $student['attendances'][$attendance['date']] = [
                'status' => $attendance['attendance_status'],
                'remarks' => $attendance['remarks']
            ];
        }
    }
    
    // ดึงข้อมูลครูที่ปรึกษา
    $sql = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$class_id]);
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $report_data = [
        'students' => $students,
        'advisors' => $advisors,
        'week_days' => $week_days
    ];
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'report_data' => $report_data]);
}

// ฟังก์ชันช่วยหาวันในช่วงที่กำหนด
function getDaysInRange($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // รวมวันสุดท้าย
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $days = [];
    foreach ($period as $day) {
        $days[] = $day->format('Y-m-d');
    }
    
    return $days;
}
<?php
/**
 * activities.php - หน้าแสดงกิจกรรมทั้งหมดสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? 0;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, c.department_id, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }

    // ดึงปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? 0;
    
    // สร้างข้อมูลสรุปนักเรียน
    $student_info = [
        'id' => $student['student_id'],
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number'],
        'profile_picture' => $student['profile_picture'],
        'student_code' => $student['student_code']
    ];
    
    // สร้างตัวอักษรแรกของชื่อสำหรับใช้แสดงในกรณีไม่มีรูปโปรไฟล์
    $first_char = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
    // ดึงข้อมูลกิจกรรมทั้งหมดในปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("
        SELECT a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
               a.description, a.required_attendance, a.academic_year_id
        FROM activities a
        WHERE a.academic_year_id = ?
        ORDER BY a.activity_date DESC
    ");
    $stmt->execute([$current_academic_year_id]);
    $all_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลกิจกรรมที่นักเรียนเข้าร่วม
    $stmt = $conn->prepare("
        SELECT aa.activity_id, aa.attendance_status, aa.record_time, aa.remarks
        FROM activity_attendance aa
        WHERE aa.student_id = ?
    ");
    $stmt->execute([$student['student_id']]);
    $student_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // สร้าง array ของ activity_id ที่นักเรียนเข้าร่วม
    $participated_activities = [];
    foreach ($student_activities as $activity) {
        $participated_activities[$activity['activity_id']] = $activity;
    }
    
    // ดึงข้อมูลกิจกรรมที่กำหนดเป้าหมายตามแผนกและระดับชั้น
    $activity_targets = [];
    
    // ดึงข้อมูลกิจกรรมที่กำหนดเป้าหมายตามแผนก
    $stmt = $conn->prepare("
        SELECT atd.activity_id, atd.department_id
        FROM activity_target_departments atd
        WHERE atd.activity_id IN (" . implode(',', array_map(function($a) { return $a['activity_id']; }, $all_activities) ?: [0]) . ")
    ");
    $stmt->execute();
    $department_targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($department_targets as $target) {
        if (!isset($activity_targets[$target['activity_id']])) {
            $activity_targets[$target['activity_id']] = [
                'departments' => [],
                'levels' => []
            ];
        }
        $activity_targets[$target['activity_id']]['departments'][] = $target['department_id'];
    }
    
    // ดึงข้อมูลกิจกรรมที่กำหนดเป้าหมายตามระดับชั้น
    $stmt = $conn->prepare("
        SELECT atl.activity_id, atl.level
        FROM activity_target_levels atl
        WHERE atl.activity_id IN (" . implode(',', array_map(function($a) { return $a['activity_id']; }, $all_activities) ?: [0]) . ")
    ");
    $stmt->execute();
    $level_targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($level_targets as $target) {
        if (!isset($activity_targets[$target['activity_id']])) {
            $activity_targets[$target['activity_id']] = [
                'departments' => [],
                'levels' => []
            ];
        }
        $activity_targets[$target['activity_id']]['levels'][] = $target['level'];
    }
    
    // แยกกิจกรรมเป็นสองกลุ่ม: เข้าร่วมแล้วและยังไม่ได้เข้าร่วม
    $participated = [];
    $not_participated = [];
    
    // จัดรูปแบบวันที่ในรูปแบบไทย
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    foreach ($all_activities as $activity) {
        // ตรวจสอบว่ากิจกรรมนี้นักเรียนต้องเข้าร่วมหรือไม่
        $student_required = true;
        
        // ถ้ามีการกำหนดกลุ่มเป้าหมาย ให้ตรวจสอบว่านักเรียนอยู่ในกลุ่มเป้าหมายหรือไม่
        if (isset($activity_targets[$activity['activity_id']])) {
            $targets = $activity_targets[$activity['activity_id']];
            
            // ถ้ามีการกำหนดแผนกเป้าหมาย
            if (!empty($targets['departments'])) {
                // ถ้านักเรียนไม่ได้อยู่ในแผนกเป้าหมาย
                if (!in_array($student['department_id'], $targets['departments'])) {
                    $student_required = false;
                }
            }
            
            // ถ้ามีการกำหนดระดับชั้นเป้าหมาย
            if (!empty($targets['levels'])) {
                // ถ้านักเรียนไม่ได้อยู่ในระดับชั้นเป้าหมาย
                if (!in_array($student['level'], $targets['levels'])) {
                    $student_required = false;
                }
            }
        }
        
        // ถ้านักเรียนไม่อยู่ในกลุ่มเป้าหมาย ข้ามกิจกรรมนี้
        if (!$student_required) {
            continue;
        }
        
        // แปลงรูปแบบวันที่
        $thai_date = "";
        if (isset($activity['activity_date']) && !empty($activity['activity_date'])) {
            $date_parts = explode('-', $activity['activity_date']);
            if (count($date_parts) === 3) {
                $day = ltrim($date_parts[2], '0');
                $month = $thai_months[$date_parts[1]] ?? '';
                $year = intval($date_parts[0]) + 543; // แปลงเป็นปี พ.ศ.
                $thai_date = "$day $month $year";
            }
        }
        
        // เพิ่มข้อมูลวันที่ไทย
        $activity['thai_date'] = $thai_date;
        
        // เพิ่มข้อมูลสถานะการเข้าร่วม
        $activity['attended'] = isset($participated_activities[$activity['activity_id']]);
        
        if ($activity['attended']) {
            $activity['attendance_status'] = $participated_activities[$activity['activity_id']]['attendance_status'];
            $activity['record_time'] = $participated_activities[$activity['activity_id']]['record_time'];
            $activity['remarks'] = $participated_activities[$activity['activity_id']]['remarks'];
            $participated[] = $activity;
        } else {
            $not_participated[] = $activity;
        }
    }
    
    // จำนวนกิจกรรมทั้งหมด
    $total_activities = count($participated) + count($not_participated);
    $total_participated = count($participated);
    $total_not_participated = count($not_participated);
    
    // คำนวณเปอร์เซ็นต์การเข้าร่วมกิจกรรม
    $participation_percentage = $total_activities > 0 ? round(($total_participated / $total_activities) * 100) : 0;
    
    // สร้างข้อมูลสรุปกิจกรรม
    $activities_summary = [
        'total' => $total_activities,
        'participated' => $total_participated,
        'not_participated' => $total_not_participated,
        'percentage' => $participation_percentage
    ];

    // รวมกิจกรรมทั้งหมดสำหรับแท็บ "ทั้งหมด"
    $all_activities = array_merge($participated, $not_participated);

    // กำหนดชื่อหน้า
    $page_title = "STD-Prasat - กิจกรรม";
    
    // กำหนด CSS เพิ่มเติม
    $extra_css = ['assets/css/activities.css'];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/activities_content.php';
    
    // รวม template หลัก
    include 'templates/main_template.php';
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}
?>
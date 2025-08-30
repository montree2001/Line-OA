<?php
/**
 * at_risk_export.php - ไฟล์สำหรับส่งออกข้อมูลนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */

// เปิดการแสดงข้อผิดพลาดเพื่อการแก้ไข (ลบหรือปิดในเวอร์ชันใช้งานจริง)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่ม session
session_start();

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล (ตรวจสอบว่าพาธถูกต้อง)
require_once '../../../db_connect.php';

// ดึงค่าพารามิเตอร์จาก POST หรือ GET (รองรับทั้งสองแบบ)
$department_id = isset($_REQUEST['department_id']) ? $_REQUEST['department_id'] : '';
$class_level = isset($_REQUEST['class_level']) ? $_REQUEST['class_level'] : '';
$class_room = isset($_REQUEST['class_room']) ? $_REQUEST['class_room'] : '';
$advisor = isset($_REQUEST['advisor']) ? $_REQUEST['advisor'] : '';
$min_attendance = isset($_REQUEST['min_attendance']) ? $_REQUEST['min_attendance'] : '';
$max_attendance = isset($_REQUEST['max_attendance']) ? $_REQUEST['max_attendance'] : '';
$academic_year_id = isset($_REQUEST['academic_year_id']) ? (int)$_REQUEST['academic_year_id'] : 1;

try {
    // เชื่อมต่อฐานข้อมูล
    $db = getDB();
    
    // ดึงข้อมูลปีการศึกษา
    $stmt = $db->prepare("SELECT year, semester FROM academic_years WHERE academic_year_id = :academic_year_id");
    $stmt->execute([':academic_year_id' => $academic_year_id]);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ถ้าไม่พบปีการศึกษาที่ระบุ ให้ใช้ปีปัจจุบัน
        $stmt = $db->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($academic_year) {
            $academic_year_id = $academic_year['academic_year_id'];
        } else {
            throw new Exception("ไม่พบข้อมูลปีการศึกษา");
        }
    }
    
    // สร้างเงื่อนไข SQL สำหรับการกรองข้อมูล
    $where_conditions = ["sar.academic_year_id = :academic_year_id"];
    $params = [':academic_year_id' => $academic_year_id];
    
    // กรองตามแผนกวิชา
    if (!empty($department_id)) {
        $where_conditions[] = "d.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    // กรองตามระดับชั้น
    if (!empty($class_level)) {
        $where_conditions[] = "c.level = :class_level";
        $params[':class_level'] = $class_level;
    }
    
    // กรองตามห้องเรียน
    if (!empty($class_room)) {
        $where_conditions[] = "c.group_number = :class_room";
        $params[':class_room'] = $class_room;
    }
    
    // กรองตามครูที่ปรึกษา
    if (!empty($advisor)) {
        $where_conditions[] = "t.teacher_id = :advisor";
        $params[':advisor'] = $advisor;
    }
    
    // กรองตามอัตราการเข้าแถว
    if (!empty($min_attendance) && !empty($max_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 BETWEEN :min_attendance AND :max_attendance";
        $params[':min_attendance'] = $min_attendance;
        $params[':max_attendance'] = $max_attendance;
    } elseif (!empty($min_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 >= :min_attendance";
        $params[':min_attendance'] = $min_attendance;
    } elseif (!empty($max_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 <= :max_attendance";
        $params[':max_attendance'] = $max_attendance;
    }
    
    // เพิ่มเงื่อนไขนักเรียนที่เสี่ยงตกกิจกรรม (อัตราการเข้าแถวต่ำกว่า 80%)
    $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80";
    $where_conditions[] = "s.status = 'กำลังศึกษา'";
    
    // รวมเงื่อนไข SQL
    $where_clause = implode(" AND ", $where_conditions);
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, 
           c.level, c.group_number, d.department_name,
           sar.total_attendance_days, sar.total_absence_days,
           CONCAT(tu.first_name, ' ', tu.last_name) as advisor_name,
           tu.phone_number as advisor_phone
           FROM students s
           JOIN users u ON s.user_id = u.user_id
           JOIN student_academic_records sar ON s.student_id = sar.student_id
           JOIN classes c ON s.current_class_id = c.class_id
           JOIN departments d ON c.department_id = d.department_id
           LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
           LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
           LEFT JOIN users tu ON t.user_id = tu.user_id
           WHERE $where_clause
           ORDER BY (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบข้อมูล
    if (empty($students_data)) {
        echo "ไม่พบข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม";
        exit;
    }
    
    // แปลงข้อมูลให้อยู่ในรูปแบบที่เหมาะสม
    $export_data = [];
    
    // เพิ่มส่วนหัวตาราง
    $export_data[] = [
        'ลำดับ',
        'รหัสนักเรียน',
        'ชื่อ-สกุล',
        'ชั้น/ห้อง',
        'แผนกวิชา',
        'วันที่เข้าแถว',
        'วันที่ขาด',
        'รวมวัน',
        'อัตราการเข้าแถว',
        'ครูที่ปรึกษา',
        'เบอร์โทรครู'
    ];
    
    // เพิ่มข้อมูลนักเรียน
    $count = 1;
    foreach ($students_data as $student) {
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $attendance_rate = $total_days > 0 ? 
            ($student['total_attendance_days'] / $total_days) * 100 : 0;
        
        // สร้างชื่อเต็ม
        $title = '';
        if (strpos($student['first_name'], 'นาย') !== 0 && 
            strpos($student['first_name'], 'นางสาว') !== 0) {
            $title = 'นาย';
            if ($student['first_name'][0] == 'เ') {
                $title = 'นางสาว';
            }
        }
        $full_name = $title . $student['first_name'] . ' ' . $student['last_name'];
        
        // สร้างข้อมูลชั้นเรียน
        $class = $student['level'] . '/' . $student['group_number'];
        
        $export_data[] = [
            $count,
            $student['student_code'],
            $full_name,
            $class,
            $student['department_name'],
            $student['total_attendance_days'],
            $student['total_absence_days'],
            $total_days,
            number_format($attendance_rate, 1) . '%',
            $student['advisor_name'] ?: 'ไม่ระบุ',
            $student['advisor_phone'] ?: 'ไม่ระบุ'
        ];
        
        $count++;
    }
    
    // ส่งออกเป็นไฟล์ CSV
    $filename = 'รายงานนักเรียนเสี่ยงตกกิจกรรม_' . date('Y-m-d_His') . '.csv';
    
    // ตั้งค่า header สำหรับดาวน์โหลด
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    
    // สร้าง output stream
    $output = fopen('php://output', 'w');
    
    // ใส่ BOM เพื่อให้ Excel อ่านภาษาไทยได้
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // เขียนข้อมูล CSV
    foreach ($export_data as $row) {
        fputcsv($output, $row, ',', '"', "\\");
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล
    echo "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
    error_log("Database error in at_risk_export.php: " . $e->getMessage());
    exit;
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาดทั่วไป
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    error_log("General error in at_risk_export.php: " . $e->getMessage());
    exit;
}
<?php
/**
 * reports.php - แดชบอร์ดสรุปข้อมูลและสถิติการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องสัตบรรณ - ดูแลผู้เรียน
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

// ดึงข้อมูลสำหรับหน้ารายงาน
function getReportData() {
    $conn = getDB();
    $data = [];
    
    // ข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $data['academic_year'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // ข้อมูลแผนกวิชา
    $query = "SELECT department_id, department_code, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    $data['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ข้อมูลสถิติภาพรวม
    $data['overview'] = getOverviewStats($conn, $data['academic_year']['academic_year_id']);
    
    // ข้อมูลการเข้าแถวแยกตามแผนก
    $data['department_stats'] = getDepartmentStats($conn, $data['academic_year']['academic_year_id']);
    
    // ข้อมูลนักเรียนที่มีความเสี่ยง
    $data['risk_students'] = getRiskStudents($conn, $data['academic_year']['academic_year_id'], 5);
    
    // ข้อมูลอันดับห้องเรียน
    $data['class_ranking'] = getClassRanking($conn, $data['academic_year']['academic_year_id']);
    
    // ข้อมูลแนวโน้มการเข้าแถว 7 วันล่าสุด
    $data['weekly_trends'] = getWeeklyTrends($conn, $data['academic_year']['academic_year_id']);
    
    // ข้อมูลสาเหตุการขาดแถว
    $data['absence_reasons'] = getAbsenceReasons($conn, $data['academic_year']['academic_year_id']);
    
    return $data;
}

// ฟังก์ชันดึงข้อมูลสถิติภาพรวม
function getOverviewStats($conn, $academicYearId) {
    // จำนวนนักเรียนทั้งหมด
    $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
    $stmt = $conn->query($query);
    $totalStudents = $stmt->fetchColumn();
    
    // อัตราการเข้าแถวเฉลี่ย
    $query = "SELECT 
                AVG(CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END) as avg_rate
              FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId]);
    $avgAttendanceRate = $stmt->fetchColumn();
    
    // จำนวนนักเรียนตกกิจกรรม
    $query = "SELECT COUNT(*) FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) < 70";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId]);
    $failedStudents = $stmt->fetchColumn();
    
    // จำนวนนักเรียนเสี่ยงตกกิจกรรม
    $query = "SELECT COUNT(*) FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) BETWEEN 70 AND 80";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId]);
    $riskStudents = $stmt->fetchColumn();
    
    // เทียบกับเดือนที่แล้ว (ตัวอย่าง - ในระบบจริงต้องคำนวณจากข้อมูลจริง)
    $lastMonthRate = $avgAttendanceRate - (rand(-2, 2));
    $rateChange = $avgAttendanceRate - $lastMonthRate;
    
    return [
        'total_students' => $totalStudents,
        'avg_attendance_rate' => round($avgAttendanceRate, 1),
        'rate_change' => round($rateChange, 1),
        'failed_students' => $failedStudents,
        'risk_students' => $riskStudents
    ];
}

// ฟังก์ชันดึงข้อมูลการเข้าแถวแยกตามแผนก
function getDepartmentStats($conn, $academicYearId) {
    $query = "SELECT 
                d.department_id,
                d.department_name,
                COUNT(DISTINCT s.student_id) as student_count,
                SUM(sar.total_attendance_days) as total_attendance,
                SUM(sar.total_absence_days) as total_absence,
                COUNT(CASE 
                    WHEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) < 80 
                    THEN 1 ELSE NULL END) as risk_count
              FROM departments d
              LEFT JOIN classes c ON d.department_id = c.department_id
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              GROUP BY d.department_id
              ORDER BY d.department_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถวและจัดรูปแบบข้อมูล
    foreach ($departments as &$dept) {
        $totalDays = $dept['total_attendance'] + $dept['total_absence'];
        $dept['attendance_rate'] = ($totalDays > 0) ? round(($dept['total_attendance'] / $totalDays) * 100, 1) : 100;
        
        // กำหนดคลาสสำหรับอัตราการเข้าแถว
        if ($dept['attendance_rate'] >= 90) {
            $dept['rate_class'] = 'good';
        } elseif ($dept['attendance_rate'] >= 80) {
            $dept['rate_class'] = 'warning';
        } else {
            $dept['rate_class'] = 'danger';
        }
    }
    
    return $departments;
}

// ฟังก์ชันดึงข้อมูลนักเรียนที่มีความเสี่ยง
function getRiskStudents($conn, $academicYearId, $limit = 5) {
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                sar.total_attendance_days,
                sar.total_absence_days,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END as attendance_rate
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              JOIN classes c ON s.current_class_id = c.class_id
              JOIN student_academic_records sar ON s.student_id = sar.student_id
              WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) <= 80
              ORDER BY attendance_rate ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId, $limit]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูล
    foreach ($students as &$student) {
        $student['attendance_rate'] = round($student['attendance_rate'], 1);
        $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        
        // กำหนดสถานะความเสี่ยง
        if ($student['attendance_rate'] < 70) {
            $student['status'] = 'danger';
            $student['status_text'] = 'ตกกิจกรรม';
        } else {
            $student['status'] = 'warning';
            $student['status_text'] = 'เสี่ยงตกกิจกรรม';
        }
    }
    
    return $students;
}

// ฟังก์ชันดึงข้อมูลอันดับห้องเรียน
function getClassRanking($conn, $academicYearId) {
    $query = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1 
                 LIMIT 1) as advisor_name,
                COUNT(DISTINCT s.student_id) as student_count,
                SUM(sar.total_attendance_days) as present_count,
                SUM(sar.total_attendance_days) + SUM(sar.total_absence_days) as total_days,
                CASE 
                    WHEN SUM(sar.total_attendance_days) + SUM(sar.total_absence_days) > 0 
                    THEN (SUM(sar.total_attendance_days) / (SUM(sar.total_attendance_days) + SUM(sar.total_absence_days)) * 100) 
                    ELSE 100 
                END as attendance_rate
              FROM classes c
              JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE c.academic_year_id = ?
              GROUP BY c.class_id
              ORDER BY attendance_rate DESC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId, $academicYearId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูล
    foreach ($classes as &$class) {
        $class['attendance_rate'] = round($class['attendance_rate'], 1);
        
        // กำหนดระดับชั้น (ปวช. หรือ ปวส.)
        if (strpos($class['level'], 'ปวช.') !== false) {
            $class['level_group'] = 'middle';
        } else {
            $class['level_group'] = 'high';
        }
        
        // กำหนดคลาสสำหรับอัตราการเข้าแถว
        if ($class['attendance_rate'] >= 90) {
            $class['rate_class'] = 'good';
            $class['bar_class'] = 'green';
        } elseif ($class['attendance_rate'] >= 80) {
            $class['rate_class'] = 'warning';
            $class['bar_class'] = 'yellow';
        } else {
            $class['rate_class'] = 'danger';
            $class['bar_class'] = 'red';
        }
    }
    
    return $classes;
}

// ฟังก์ชันดึงข้อมูลแนวโน้มการเข้าแถว 7 วันล่าสุด
function getWeeklyTrends($conn, $academicYearId) {
    $trends = [];
    $thaiDays = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayOfWeek = date('w', strtotime($date));
        
        // ดึงข้อมูลการเข้าแถวในวันนี้
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN attendance_status = 'present' THEN student_id END) as present_count,
                    COUNT(DISTINCT student_id) as total_students
                  FROM attendance
                  WHERE academic_year_id = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $date]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // คำนวณอัตราการเข้าแถว
        $rate = 0;
        if ($data['total_students'] > 0) {
            $rate = ($data['present_count'] / $data['total_students']) * 100;
        }
        
        // จัดรูปแบบวันที่
        $displayDate = $thaiDays[$dayOfWeek] . ' ' . date('j/n', strtotime($date));
        
        $trends[] = [
            'date' => $displayDate,
            'attendance_rate' => round($rate, 1),
            'is_weekend' => ($dayOfWeek == 0 || $dayOfWeek == 6)
        ];
    }
    
    return $trends;
}

// ฟังก์ชันดึงข้อมูลสาเหตุการขาดแถว
function getAbsenceReasons($conn, $academicYearId) {
    // ในระบบจริงควรมีตารางเก็บสาเหตุการขาดแถว
    // ตัวอย่างข้อมูลจำลอง
    return [
        ['reason' => 'ป่วย', 'percent' => 42, 'color' => '#2196f3'],
        ['reason' => 'ธุระส่วนตัว', 'percent' => 28, 'color' => '#ff9800'],
        ['reason' => 'มาสาย', 'percent' => 15, 'color' => '#9c27b0'],
        ['reason' => 'ไม่ทราบสาเหตุ', 'percent' => 15, 'color' => '#f44336']
    ];
}

// ดึงข้อมูลทั้งหมดสำหรับหน้ารายงาน
$report_data = getReportData();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'reports';
$page_title = 'รายงานและสถิติการเข้าแถว';
$page_header = 'แดชบอร์ดสรุปข้อมูลและสถิติการเข้าแถว';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/reports.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'assets/js/reports.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/reports_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
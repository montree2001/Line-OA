<?php
/**
 * executive_reports.php - แดชบอร์ดสำหรับผู้บริหาร
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 * 
 * หน้านี้สำหรับผู้บริหารระดับสูง (super_admin) เท่านั้น
 * เพื่อดูภาพรวมการเข้าแถว ความเสี่ยง และสถิติต่างๆ ของวิทยาลัย
 */

// เริ่ม session
session_start();

/* // ตรวจสอบการล็อกอินและสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
} */

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
/* 
// ตรวจสอบว่าเป็น super_admin หรือไม่
$conn = getDB();
$checkAdminQuery = "SELECT role FROM admin_users WHERE admin_id = ? AND role = 'super_admin' AND is_active = 1";
$stmt = $conn->prepare($checkAdminQuery);
$stmt->execute([$_SESSION['user_id']]);
$adminRole = $stmt->fetchColumn();

if (!$adminRole) {
    // ไม่ใช่ super_admin ให้ redirect ไปหน้าหลัก
    header('Location: dashboard.php');
    exit;
} */

// ข้อมูลผู้บริหาร
$executive_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้บริหาร',
    'role' => 'ผู้บริหารระดับสูง',
    'initials' => substr($_SESSION['user_name'] ?? 'ผ', 0, 1),
];

/**
 * ฟังก์ชันดึงข้อมูลรายงานสำหรับผู้บริหาร
 */
function getExecutiveReportData($period = 'month', $departmentId = null) 
{
    $conn = getDB();
    $data = [];

    // ข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $data['academic_year'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // ข้อมูลแผนกวิชาทั้งหมด
    $query = "SELECT department_id, department_code, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    $data['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ข้อมูลสถิติภาพรวมระดับวิทยาลัย
    $data['overview'] = getExecutiveOverview($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // ข้อมูลประสิทธิภาพแต่ละแผนก
    $data['department_performance'] = getDepartmentPerformance($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // ข้อมูลนักเรียนเสี่ยงสูง
    $data['critical_students'] = getCriticalRiskStudents($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // ข้อมูลประสิทธิภาพห้องเรียน
    $data['class_performance'] = getClassPerformance($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // แนวโน้มการเข้าแถวรายสัปดาห์
    $data['weekly_trends'] = getExecutiveWeeklyTrends($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // สถานะการเข้าแถวทั่วทั้งวิทยาลัย
    $data['attendance_status'] = getCollegeAttendanceStatus($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // ข้อมูลเปรียบเทียบกับเป้าหมาย
    $data['target_comparison'] = getTargetComparison($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    // สถิติการแจ้งเตือนผู้ปกครอง
    $data['notification_stats'] = getNotificationStats($conn, $data['academic_year']['academic_year_id'], $period, $departmentId);

    return $data;
}

/**
 * ข้อมูลภาพรวมระดับผู้บริหาร
 */
function getExecutiveOverview($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    // จำนวนนักเรียนทั้งหมดที่กำลังศึกษา
    $studentQuery = "SELECT COUNT(DISTINCT s.student_id) FROM students s
                     JOIN classes c ON s.current_class_id = c.class_id
                     WHERE s.status = 'กำลังศึกษา' AND c.academic_year_id = ? $depCondition";
    
    $params = array_merge([$academicYearId], $depParams);
    $stmt = $conn->prepare($studentQuery);
    $stmt->execute($params);
    $totalStudents = $stmt->fetchColumn();
    
    // จำนวนห้องเรียนที่เปิดใช้งาน
    $classQuery = "SELECT COUNT(*) FROM classes c WHERE c.academic_year_id = ? AND c.is_active = 1 $depCondition";
    $stmt = $conn->prepare($classQuery);
    $stmt->execute($params);
    $totalClasses = $stmt->fetchColumn();
    
    // จำนวนแผนกวิชา
    $deptQuery = "SELECT COUNT(*) FROM departments WHERE is_active = 1";
    if ($departmentId) {
        $deptQuery .= " AND department_id = ?";
        $stmt = $conn->prepare($deptQuery);
        $stmt->execute([$departmentId]);
    } else {
        $stmt = $conn->query($deptQuery);
    }
    $totalDepartments = $stmt->fetchColumn();
    
    // อัตราการเข้าแถวเฉลี่ยทั้งวิทยาลัย
    $avgRateQuery = "SELECT 
                        AVG(CASE 
                            WHEN sar.total_attendance_days + sar.total_absence_days > 0 
                            THEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days))
                            ELSE 0 
                        END) as avg_rate
                     FROM student_academic_records sar
                     JOIN students s ON sar.student_id = s.student_id
                     JOIN classes c ON s.current_class_id = c.class_id
                     WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา' $depCondition";
    
    $stmt = $conn->prepare($avgRateQuery);
    $stmt->execute($params);
    $avgAttendanceRate = $stmt->fetchColumn() ?: 0;
    
    // นักเรียนเสี่ยงตกกิจกรรม (< 80%)
    $riskQuery = "SELECT 
                    COUNT(CASE 
                        WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) BETWEEN 70 AND 79.99 
                        THEN 1 END) as risk_students,
                    COUNT(CASE 
                        WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) < 70 
                        THEN 1 END) as critical_students,
                    COUNT(CASE 
                        WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) >= 90 
                        THEN 1 END) as excellent_students
                  FROM student_academic_records sar
                  JOIN students s ON sar.student_id = s.student_id
                  JOIN classes c ON s.current_class_id = c.class_id
                  WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา' 
                    AND (sar.total_attendance_days + sar.total_absence_days) > 0 $depCondition";
    
    $stmt = $conn->prepare($riskQuery);
    $stmt->execute($params);
    $riskData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณเปอร์เซนต์ความสำเร็จของวิทยาลัย
    $successRate = $totalStudents > 0 ? (($totalStudents - $riskData['risk_students'] - $riskData['critical_students']) / $totalStudents) * 100 : 0;
    
    return [
        'total_students' => $totalStudents,
        'total_classes' => $totalClasses,
        'total_departments' => $totalDepartments,
        'avg_attendance_rate' => round($avgAttendanceRate, 1),
        'risk_students' => $riskData['risk_students'] ?: 0,
        'critical_students' => $riskData['critical_students'] ?: 0,
        'excellent_students' => $riskData['excellent_students'] ?: 0,
        'success_rate' => round($successRate, 1),
        'period' => $period
    ];
}

/**
 * ข้อมูลประสิทธิภาพแต่ละแผนก
 */
function getDepartmentPerformance($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND d.department_id = ?";
        $depParams = [$departmentId];
    }

    $query = "SELECT 
                d.department_id,
                d.department_name,
                d.department_code,
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(DISTINCT c.class_id) as total_classes,
                AVG(CASE 
                    WHEN sar.total_attendance_days + sar.total_absence_days > 0 
                    THEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days))
                    ELSE 0 
                END) as avg_attendance_rate,
                COUNT(CASE 
                    WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) < 80 
                    THEN 1 END) as at_risk_count,
                COUNT(CASE 
                    WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) >= 90 
                    THEN 1 END) as excellent_count
              FROM departments d
              LEFT JOIN classes c ON d.department_id = c.department_id AND c.academic_year_id = ? AND c.is_active = 1
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE d.is_active = 1 $depCondition
              GROUP BY d.department_id, d.department_name, d.department_code
              ORDER BY avg_attendance_rate DESC";
    
    $params = array_merge([$academicYearId, $academicYearId], $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($departments as &$dept) {
        $dept['avg_attendance_rate'] = round($dept['avg_attendance_rate'] ?: 0, 1);
        $dept['risk_percentage'] = $dept['total_students'] > 0 ? round(($dept['at_risk_count'] / $dept['total_students']) * 100, 1) : 0;
        
        // กำหนดสถานะประสิทธิภาพ
        if ($dept['avg_attendance_rate'] >= 90) {
            $dept['performance_status'] = 'excellent';
            $dept['status_text'] = 'ดีเยี่ยม';
        } elseif ($dept['avg_attendance_rate'] >= 80) {
            $dept['performance_status'] = 'good';
            $dept['status_text'] = 'ดี';
        } elseif ($dept['avg_attendance_rate'] >= 70) {
            $dept['performance_status'] = 'warning';
            $dept['status_text'] = 'ต้องปรับปรุง';
        } else {
            $dept['performance_status'] = 'critical';
            $dept['status_text'] = 'ต้องแก้ไขด่วน';
        }
    }
    
    return $departments;
}

/**
 * นักเรียนที่มีความเสี่ยงสูง
 */
function getCriticalRiskStudents($conn, $academicYearId, $period = 'month', $departmentId = null, $limit = 10)
{
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name,
                CONCAT(c.level, '/', c.group_number, ' ', d.department_name) as class_name,
                sar.total_attendance_days,
                sar.total_absence_days,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) 
                    ELSE 0 
                END as attendance_rate,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              JOIN classes c ON s.current_class_id = c.class_id
              JOIN departments d ON c.department_id = d.department_id
              JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE s.status = 'กำลังศึกษา'
                AND (sar.total_attendance_days + sar.total_absence_days) > 0
                AND (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) < 80
                $depCondition
              ORDER BY attendance_rate ASC
              LIMIT ?";
    
    $params = array_merge([$academicYearId], $depParams, [$limit]);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as &$student) {
        $student['attendance_rate'] = round($student['attendance_rate'], 1);
        $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        
        if ($student['attendance_rate'] < 60) {
            $student['risk_level'] = 'critical';
            $student['risk_text'] = 'วิกฤต';
        } elseif ($student['attendance_rate'] < 70) {
            $student['risk_level'] = 'high';
            $student['risk_text'] = 'สูง';
        } else {
            $student['risk_level'] = 'medium';
            $student['risk_text'] = 'ปานกลาง';
        }
    }
    
    return $students;
}

/**
 * ประสิทธิภาพห้องเรียน
 */
function getClassPerformance($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    $query = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                d.department_name,
                CONCAT(c.level, '/', c.group_number) as class_name,
                COUNT(DISTINCT s.student_id) as total_students,
                AVG(CASE 
                    WHEN sar.total_attendance_days + sar.total_absence_days > 0 
                    THEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days))
                    ELSE 0 
                END) as avg_attendance_rate,
                COUNT(CASE 
                    WHEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days)) < 80 
                    THEN 1 END) as risk_count,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_name
              FROM classes c
              JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE c.academic_year_id = ? AND c.is_active = 1 $depCondition
              GROUP BY c.class_id
              ORDER BY avg_attendance_rate DESC";
    
    $params = array_merge([$academicYearId, $academicYearId], $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($classes as &$class) {
        $class['avg_attendance_rate'] = round($class['avg_attendance_rate'] ?: 0, 1);
        $class['risk_percentage'] = $class['total_students'] > 0 ? round(($class['risk_count'] / $class['total_students']) * 100, 1) : 0;
        
        // กำหนดระดับประสิทธิภาพ
        if ($class['avg_attendance_rate'] >= 95) {
            $class['performance_level'] = 'excellent';
            $class['level_text'] = 'ดีเยี่ยม';
        } elseif ($class['avg_attendance_rate'] >= 85) {
            $class['performance_level'] = 'good';
            $class['level_text'] = 'ดี';
        } elseif ($class['avg_attendance_rate'] >= 75) {
            $class['performance_level'] = 'average';
            $class['level_text'] = 'ปานกลาง';
        } else {
            $class['performance_level'] = 'poor';
            $class['level_text'] = 'ต้องปรับปรุง';
        }
    }
    
    return $classes;
}

/**
 * แนวโน้มการเข้าแถวรายสัปดาห์สำหรับผู้บริหาร
 */
function getExecutiveWeeklyTrends($conn, $academicYearId, $period = 'week', $departmentId = null)
{
    $trends = [];
    $thaiDays = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND EXISTS (
            SELECT 1 FROM students s2
            JOIN classes c2 ON s2.current_class_id = c2.class_id
            WHERE s2.student_id = a.student_id AND c2.department_id = ?
        )";
        $depParams = [$departmentId];
    }
    
    // ข้อมูล 14 วันล่าสุด (2 สัปดาห์) เพื่อให้เห็นแนวโน้ม
    for ($i = 13; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayOfWeek = date('w', strtotime($date));
        
        // ข้ามวันเสาร์-อาทิตย์
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            continue;
        }
        
        // ดึงข้อมูลการเข้าแถวในวันนี้
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                    COUNT(DISTINCT a.student_id) as total_checked,
                    (SELECT COUNT(DISTINCT s.student_id) 
                     FROM students s 
                     JOIN classes c ON s.current_class_id = c.class_id 
                     WHERE s.status = 'กำลังศึกษา' AND c.academic_year_id = ? $depCondition) as total_students
                  FROM attendance a
                  WHERE a.academic_year_id = ? AND a.date = ? $depCondition";
        
        $params = array_merge([$academicYearId], $depParams, [$academicYearId, $date], $depParams);
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // คำนวณอัตราการเข้าแถว
        $rate = 0;
        if ($data && $data['total_checked'] > 0) {
            $rate = ($data['present_count'] / $data['total_checked']) * 100;
        }
        
        // จัดรูปแบบวันที่
        $displayDate = $thaiDays[$dayOfWeek] . ' ' . date('j/n', strtotime($date));
        
        $trends[] = [
            'date' => $displayDate,
            'attendance_rate' => round($rate, 1),
            'present_count' => $data['present_count'] ?: 0,
            'total_checked' => $data['total_checked'] ?: 0,
            'total_students' => $data['total_students'] ?: 0,
            'is_weekend' => false
        ];
    }
    
    return $trends;
}

/**
 * สถานะการเข้าแถวทั่วทั้งวิทยาลัย
 */
function getCollegeAttendanceStatus($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND EXISTS (
            SELECT 1 FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.student_id = a.student_id AND c.department_id = ?
        )";
        $depParams = [$departmentId];
    }
    
    $query = "SELECT 
                attendance_status,
                COUNT(*) as count
              FROM attendance a
              WHERE a.academic_year_id = ? $periodCondition $depCondition
              GROUP BY attendance_status";
    
    $params = array_merge([$academicYearId], $periodParams, $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = array_sum(array_column($statusCounts, 'count'));
    
    $result = [];
    $colors = [
        'present' => '#4caf50',
        'absent' => '#f44336', 
        'late' => '#ff9800',
        'leave' => '#2196f3'
    ];
    
    $statusMap = [
        'present' => 'เข้าแถวปกติ',
        'absent' => 'ขาดแถว',
        'late' => 'เข้าแถวสาย',
        'leave' => 'ลาป่วย/ลากิจ'
    ];
    
    foreach ($statusCounts as $status) {
        $statusKey = $status['attendance_status'];
        $count = $status['count'];
        $percent = ($total > 0) ? round(($count / $total) * 100) : 0;
        
        $result[] = [
            'status' => $statusMap[$statusKey] ?? $statusKey,
            'count' => $count,
            'percent' => $percent,
            'color' => $colors[$statusKey] ?? '#9e9e9e'
        ];
    }
    
    return $result;
}

/**
 * เปรียบเทียบกับเป้าหมาย
 */
function getTargetComparison($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    // ดึงเป้าหมายจากการตั้งค่าระบบ
    $targetQuery = "SELECT setting_value FROM system_settings WHERE setting_key = 'custom_attendance_rate'";
    $stmt = $conn->query($targetQuery);
    $target = $stmt->fetchColumn() ?: 80;
    
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    // คำนวณอัตราปัจจุบัน
    $query = "SELECT 
                AVG(CASE 
                    WHEN sar.total_attendance_days + sar.total_absence_days > 0 
                    THEN (sar.total_attendance_days * 100.0 / (sar.total_attendance_days + sar.total_absence_days))
                    ELSE 0 
                END) as current_rate
              FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              JOIN classes c ON s.current_class_id = c.class_id
              WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา' $depCondition";
    
    $params = array_merge([$academicYearId], $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $currentRate = $stmt->fetchColumn() ?: 0;
    
    $difference = $currentRate - $target;
    $achievementRate = ($target > 0) ? ($currentRate / $target) * 100 : 0;
    
    return [
        'target' => $target,
        'current' => round($currentRate, 1),
        'difference' => round($difference, 1),
        'achievement_rate' => round($achievementRate, 1),
        'status' => $difference >= 0 ? 'achieved' : 'below_target'
    ];
}

/**
 * สถิติการแจ้งเตือนผู้ปกครอง
 */
function getNotificationStats($conn, $academicYearId, $period = 'month', $departmentId = null)
{
    list($periodCondition, $periodParams) = getPeriodCondition($period, 'ln.sent_at');
    
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND EXISTS (
            SELECT 1 FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.user_id = ln.user_id AND c.department_id = ?
        )";
        $depParams = [$departmentId];
    }
    
    $query = "SELECT 
                COUNT(*) as total_sent,
                COUNT(CASE WHEN ln.status = 'sent' THEN 1 END) as successful_sent,
                COUNT(CASE WHEN ln.status = 'failed' THEN 1 END) as failed_sent,
                COUNT(CASE WHEN ln.notification_type = 'risk_alert' THEN 1 END) as risk_alerts,
                COUNT(CASE WHEN ln.notification_type = 'attendance' THEN 1 END) as attendance_alerts
              FROM line_notifications ln
              WHERE 1=1 $periodCondition $depCondition";
    
    $params = array_merge($periodParams, $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total_sent' => $stats['total_sent'] ?: 0,
        'successful_sent' => $stats['successful_sent'] ?: 0,
        'failed_sent' => $stats['failed_sent'] ?: 0,
        'success_rate' => $stats['total_sent'] > 0 ? round(($stats['successful_sent'] / $stats['total_sent']) * 100, 1) : 0,
        'risk_alerts' => $stats['risk_alerts'] ?: 0,
        'attendance_alerts' => $stats['attendance_alerts'] ?: 0
    ];
}

// ฟังก์ชันสร้างเงื่อนไขสำหรับช่วงเวลา (รองรับตารางต่างๆ)
function getPeriodCondition($period, $dateColumn = 'a.date')
{
    $condition = '';
    $params = [];
    
    switch ($period) {
        case 'day':
            $condition = " AND $dateColumn = ?";
            $params[] = date('Y-m-d');
            break;
            
        case 'yesterday':
            $condition = " AND $dateColumn = ?";
            $params[] = date('Y-m-d', strtotime('-1 day'));
            break;
            
        case 'week':
            $condition = " AND $dateColumn BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime('monday this week'));
            $params[] = date('Y-m-d', strtotime('sunday this week'));
            break;
            
        case 'month':
            $condition = " AND $dateColumn BETWEEN ? AND ?";
            $params[] = date('Y-m-01');
            $params[] = date('Y-m-t');
            break;
            
        case 'semester':
            // ใช้ข้อมูลจากปีการศึกษาที่เปิดใช้งาน
            break;
            
        case 'custom':
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $condition = " AND $dateColumn BETWEEN ? AND ?";
                $params[] = $_GET['start_date'];
                $params[] = $_GET['end_date'];
            }
            break;
    }
    
    return [$condition, $params];
}

// ดึงข้อมูลรายงานสำหรับผู้บริหาร
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;

$report_data = getExecutiveReportData($period, $departmentId);

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'executive_reports';
$page_title = 'แดชบอร์ดผู้บริหาร - ภาพรวมการเข้าแถวและการจัดการ';
$page_header = 'แดชบอร์ดผู้บริหารระดับสูง';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/executive_reports.css',
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js',
    'assets/js/executive_reports.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/executive_reports_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/executive_sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
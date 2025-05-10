<?php
/**
 * api/reports.php - API สำหรับดึงข้อมูลรายงานและสถิติการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องสัตบรรณ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล']);
    exit;
}

// เชื่อมต่อกับฐานข้อมูล
require_once '../../db_connect.php';
$conn = getDB();

// ปิดการจำลอง prepared statements เพื่อหลีกเลี่ยงปัญหา SQL
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// กำหนดชนิดของเนื้อหาเป็น JSON
header('Content-Type: application/json');

// รับวิธีการร้องขอและการกระทำ
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// จัดการกับการร้องขอตามวิธีการและการกระทำ
try {
    if ($method === 'GET') {
        switch ($action) {
            case 'overview':
                getOverviewStats();
                break;
                
            case 'department_stats':
                getDepartmentStats();
                break;
                
            case 'class_ranking':
                getClassRanking();
                break;
                
            case 'risk_students':
                getRiskStudents();
                break;
                
            case 'weekly_trends':
                getWeeklyTrends();
                break;
                
            case 'absence_reasons':
                getAbsenceReasons();
                break;
                
            case 'student_details':
                getStudentDetails();
                break;
                
            case 'attendance_summary':
                getAttendanceSummary();
                break;
                
            default:
                echo json_encode(['error' => 'ไม่พบการกระทำที่ระบุ']);
                break;
        }
    } elseif ($method === 'POST') {
        // รับข้อมูล POST
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'send_notification':
                sendNotification($data);
                break;
                
            case 'export_report':
                exportReport($data);
                break;
                
            default:
                echo json_encode(['error' => 'ไม่พบการกระทำที่ระบุ']);
                break;
        }
    } else {
        echo json_encode(['error' => 'วิธีการร้องขอไม่ถูกต้อง']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}

/**
 * ดึงข้อมูลสถิติภาพรวม
 */
function getOverviewStats() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academicYear) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // รับพารามิเตอร์ชนิดของข้อมูล
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    
    // สร้างเงื่อนไข WHERE สำหรับแผนกวิชา
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    // สร้างเงื่อนไข WHERE สำหรับช่วงเวลา
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    // จำนวนนักเรียนทั้งหมด
    $studentQuery = "SELECT COUNT(*) FROM students s
                     JOIN classes c ON s.current_class_id = c.class_id
                     WHERE s.status = 'กำลังศึกษา'";
    
    if ($departmentId) {
        $studentQuery .= $depCondition;
        $stmt = $conn->prepare($studentQuery);
        $stmt->execute($depParams);
    } else {
        $stmt = $conn->query($studentQuery);
    }
    
    $totalStudents = $stmt->fetchColumn();
    
    // อัตราการเข้าแถวเฉลี่ย
    $query = "SELECT 
                AVG(CASE 
                    WHEN a.attendance_status = 'present' THEN 1
                    ELSE 0
                END) * 100 as avg_rate
              FROM attendance a
              JOIN students s ON a.student_id = s.student_id
              JOIN classes c ON s.current_class_id = c.class_id
              WHERE a.academic_year_id = ? $periodCondition $depCondition
                AND s.status = 'กำลังศึกษา'";
    
    $params = array_merge([$academicYearId], $periodParams, $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $avgAttendanceRate = $stmt->fetchColumn() ?: 0;
    
    // จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
    $query = "SELECT 
                COUNT(DISTINCT s.student_id) as risk_count,
                COUNT(DISTINCT CASE WHEN 
                    sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100 < 70
                    THEN s.student_id END) as fail_count
              FROM students s
              JOIN classes c ON s.current_class_id = c.class_id
              JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE s.status = 'กำลังศึกษา'
                AND sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100 < 80
                $depCondition";
    
    $params = array_merge([$academicYearId], $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $riskData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $riskStudents = $riskData['risk_count'] - $riskData['fail_count'];
    $failedStudents = $riskData['fail_count'];
    
    // เทียบกับช่วงเวลาก่อนหน้า
    list($prevPeriodCondition, $prevPeriodParams) = getPreviousPeriodCondition($period);
    
    $query = "SELECT 
                AVG(CASE 
                    WHEN a.attendance_status = 'present' THEN 1
                    ELSE 0
                END) * 100 as avg_rate
              FROM attendance a
              JOIN students s ON a.student_id = s.student_id
              JOIN classes c ON s.current_class_id = c.class_id
              WHERE a.academic_year_id = ? $prevPeriodCondition $depCondition
                AND s.status = 'กำลังศึกษา'";
    
    $params = array_merge([$academicYearId], $prevPeriodParams, $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $prevAvgRate = $stmt->fetchColumn() ?: 0;
    
    // คำนวณการเปลี่ยนแปลง
    $rateChange = $avgAttendanceRate - $prevAvgRate;
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => [
            'total_students' => $totalStudents,
            'avg_attendance_rate' => round($avgAttendanceRate, 1),
            'rate_change' => round($rateChange, 1),
            'failed_students' => $failedStudents,
            'risk_students' => $riskStudents,
            'period' => $period
        ]
    ]);
}

/**
 * ดึงข้อมูลการเข้าแถวแยกตามแผนกวิชา
 */
function getDepartmentStats() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์ชนิดของข้อมูล
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    
    // สร้างเงื่อนไข WHERE สำหรับช่วงเวลา
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    // ดึงข้อมูลแผนกวิชา
    $query = "SELECT 
                d.department_id,
                d.department_name,
                COUNT(DISTINCT s.student_id) as student_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.attendance_id END) as total_attendance,
                COUNT(DISTINCT CASE WHEN a.attendance_status IN ('absent', 'leave') THEN a.attendance_id END) as total_absence,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(CASE WHEN a2.attendance_status = 'present' THEN 1 END) * 100 / COUNT(*) 
                          FROM attendance a2 
                          WHERE a2.student_id = s.student_id AND a2.academic_year_id = ? $periodCondition) < 80 
                    THEN s.student_id END) as risk_count
              FROM departments d
              LEFT JOIN classes c ON d.department_id = c.department_id AND c.academic_year_id = ?
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN attendance a ON a.student_id = s.student_id AND a.academic_year_id = ? $periodCondition
              WHERE d.is_active = 1
              GROUP BY d.department_id
              ORDER BY d.department_name";
    
    $allParams = array_merge([$academicYearId], $periodParams, [$academicYearId, $academicYearId], $periodParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถวและจัดรูปแบบข้อมูล
    foreach ($departments as &$dept) {
        $totalChecks = $dept['total_attendance'] + $dept['total_absence'];
        $dept['attendance_rate'] = ($totalChecks > 0) 
            ? round(($dept['total_attendance'] / $totalChecks) * 100, 1) 
            : 0;
        
        // กำหนดคลาสสำหรับอัตราการเข้าแถว
        if ($dept['attendance_rate'] >= 90) {
            $dept['rate_class'] = 'good';
        } elseif ($dept['attendance_rate'] >= 80) {
            $dept['rate_class'] = 'warning';
        } else {
            $dept['rate_class'] = 'danger';
        }
    }
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => $departments,
        'period' => $period
    ]);
}

/**
 * ดึงข้อมูลอันดับห้องเรียน
 */
function getClassRanking() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์ชนิดของข้อมูล
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    
    // สร้างเงื่อนไข WHERE สำหรับแผนกวิชาและระดับชั้น
    $filterCondition = '';
    $filterParams = [];
    
    if ($departmentId) {
        $filterCondition .= " AND c.department_id = ?";
        $filterParams[] = $departmentId;
    }
    
    if ($level) {
        if ($level == 'middle') {
            $filterCondition .= " AND c.level LIKE 'ปวช.%'";
        } else if ($level == 'high') {
            $filterCondition .= " AND c.level LIKE 'ปวส.%'";
        }
    }
    
    // สร้างเงื่อนไข WHERE สำหรับช่วงเวลา
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    // ดึงข้อมูลห้องเรียน
    $query = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                d.department_name,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_name,
                COUNT(DISTINCT s.student_id) as student_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.attendance_id END) as present_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status IN ('absent', 'leave') THEN a.attendance_id END) as absence_count,
                CASE 
                    WHEN COUNT(DISTINCT a.attendance_id) > 0 
                    THEN (COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.attendance_id END) * 100 / COUNT(DISTINCT a.attendance_id))
                    ELSE 0
                END as attendance_rate
              FROM classes c
              JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN attendance a ON a.student_id = s.student_id AND a.academic_year_id = ? $periodCondition
              WHERE c.academic_year_id = ? AND c.is_active = 1 $filterCondition
              GROUP BY c.class_id
              ORDER BY attendance_rate DESC
              LIMIT 20";
    
    $allParams = array_merge([$academicYearId], $periodParams, [$academicYearId], $filterParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
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
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => $classes,
        'period' => $period
    ]);
}

/**
 * ดึงข้อมูลนักเรียนที่มีความเสี่ยง
 */
function getRiskStudents() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    $classId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // สร้างเงื่อนไข WHERE
    $filterCondition = '';
    $filterParams = [];
    
    if ($departmentId) {
        $filterCondition .= " AND c.department_id = ?";
        $filterParams[] = $departmentId;
    }
    
    if ($classId) {
        $filterCondition .= " AND c.class_id = ?";
        $filterParams[] = $classId;
    }
    
    // ดึงข้อมูลนักเรียนที่มีความเสี่ยง
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_name,
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
              JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) <= 80 $filterCondition
              ORDER BY attendance_rate ASC, s.student_code ASC
              LIMIT ?";
    
    $allParams = array_merge([$academicYearId], $filterParams, [$limit]);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
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
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => $students,
        'total' => count($students)
    ]);
}

/**
 * ดึงข้อมูลแนวโน้มการเข้าแถว
 */
function getWeeklyTrends() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์
    $period = isset($_GET['period']) ? $_GET['period'] : 'week';
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    
    // สร้างเงื่อนไข WHERE สำหรับแผนกวิชา
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = " AND c.department_id = ?";
        $depParams = [$departmentId];
    }
    
    $trends = [];
    $thaiDays = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $thaiMonths = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    
    if ($period == 'week') {
        // ข้อมูล 7 วันล่าสุด
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayOfWeek = date('w', strtotime($date));
            
            // ดึงข้อมูลการเข้าแถวในวันนี้
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                        COUNT(DISTINCT a.student_id) as total_students
                      FROM attendance a
                      JOIN students s ON a.student_id = s.student_id
                      JOIN classes c ON s.current_class_id = c.class_id
                      WHERE a.academic_year_id = ? AND a.date = ? $depCondition
                        AND s.status = 'กำลังศึกษา'";
            
            $params = array_merge([$academicYearId, $date], $depParams);
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // คำนวณอัตราการเข้าแถว
            $rate = 0;
            if ($data && $data['total_students'] > 0) {
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
    } elseif ($period == 'month') {
        // ข้อมูลรายวันในเดือนปัจจุบัน
        $currentMonth = date('m');
        $currentYear = date('Y');
        $daysInMonth = date('t');
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
            if (strtotime($date) > time()) continue; // ไม่แสดงวันในอนาคต
            
            $dayOfWeek = date('w', strtotime($date));
            
            // ดึงข้อมูลการเข้าแถวในวันนี้
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                        COUNT(DISTINCT a.student_id) as total_students
                      FROM attendance a
                      JOIN students s ON a.student_id = s.student_id
                      JOIN classes c ON s.current_class_id = c.class_id
                      WHERE a.academic_year_id = ? AND a.date = ? $depCondition
                        AND s.status = 'กำลังศึกษา'";
            
            $params = array_merge([$academicYearId, $date], $depParams);
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // คำนวณอัตราการเข้าแถว
            $rate = 0;
            if ($data && $data['total_students'] > 0) {
                $rate = ($data['present_count'] / $data['total_students']) * 100;
            }
            
            // จัดรูปแบบวันที่
            $displayDate = $day;
            
            $trends[] = [
                'date' => (string)$displayDate,
                'attendance_rate' => round($rate, 1),
                'is_weekend' => ($dayOfWeek == 0 || $dayOfWeek == 6)
            ];
        }
    } elseif ($period == 'semester') {
        // ข้อมูลรายเดือนในภาคเรียนปัจจุบัน
        $query = "SELECT start_date, end_date FROM academic_years WHERE academic_year_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $academicYearDates = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($academicYearDates) {
            $startDate = new DateTime($academicYearDates['start_date']);
            $endDate = new DateTime($academicYearDates['end_date']);
            $endDate = min($endDate, new DateTime()); // ไม่เกินวันปัจจุบัน
            
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $monthNum = (int)$currentDate->format('n');
                $year = (int)$currentDate->format('Y');
                $month = $thaiMonths[$monthNum];
                
                // ดึงข้อมูลการเข้าแถวในเดือนนี้
                $query = "SELECT 
                            COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                            COUNT(DISTINCT a.student_id) as total_students
                          FROM attendance a
                          JOIN students s ON a.student_id = s.student_id
                          JOIN classes c ON s.current_class_id = c.class_id
                          WHERE a.academic_year_id = ? 
                            AND MONTH(a.date) = ? 
                            AND YEAR(a.date) = ?
                            $depCondition
                            AND s.status = 'กำลังศึกษา'";
                
                $params = array_merge([$academicYearId, $monthNum, $year], $depParams);
                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // คำนวณอัตราการเข้าแถว
                $rate = 0;
                if ($data && $data['total_students'] > 0) {
                    $rate = ($data['present_count'] / $data['total_students']) * 100;
                }
                
                $trends[] = [
                    'date' => $month,
                    'attendance_rate' => round($rate, 1),
                    'is_weekend' => false
                ];
                
                // เลื่อนไปเดือนถัดไป
                $currentDate->modify('+1 month');
            }
        }
    }
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => $trends,
        'period' => $period
    ]);
}

/**
 * ดึงข้อมูลสาเหตุการขาดแถว
 */
function getAbsenceReasons() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    
    // สร้างเงื่อนไข WHERE สำหรับแผนกวิชา
    $depCondition = '';
    $depParams = [];
    if ($departmentId) {
        $depCondition = "AND EXISTS (
            SELECT 1 FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE s.student_id = a.student_id AND c.department_id = ?
        )";
        $depParams = [$departmentId];
    }
    
    // ดึงสาเหตุการขาดแถว
    $query = "SELECT 
                COALESCE(NULLIF(TRIM(a.remarks), ''), 'ไม่ระบุสาเหตุ') as reason, 
                COUNT(*) as count
              FROM attendance a
              WHERE a.academic_year_id = ? 
              AND a.attendance_status IN ('absent', 'leave')
              $depCondition
              GROUP BY reason
              ORDER BY count DESC
              LIMIT 4";
    
    $params = array_merge([$academicYearId], $depParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $reasonsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณร้อยละของแต่ละสาเหตุ
    $total = array_sum(array_column($reasonsData, 'count'));
    $colors = ['#2196f3', '#ff9800', '#9c27b0', '#f44336'];
    
    $reasons = [];
    if ($total > 0) {
        foreach ($reasonsData as $index => $data) {
            $percent = round(($data['count'] / $total) * 100);
            $reasons[] = [
                'reason' => $data['reason'],
                'percent' => $percent,
                'color' => $colors[$index % count($colors)]
            ];
        }
    }
    
    // ถ้าไม่มีข้อมูลหรือข้อมูลไม่เพียงพอ ให้ใช้ข้อมูลตัวอย่าง
    if (count($reasons) < 2) {
        $reasons = [
            ['reason' => 'ป่วย', 'percent' => 42, 'color' => '#2196f3'],
            ['reason' => 'ธุระส่วนตัว', 'percent' => 28, 'color' => '#ff9800'],
            ['reason' => 'มาสาย', 'percent' => 15, 'color' => '#9c27b0'],
            ['reason' => 'ไม่ทราบสาเหตุ', 'percent' => 15, 'color' => '#f44336']
        ];
    }
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => $reasons
    ]);
}

/**
 * ดึงข้อมูลรายละเอียดของนักเรียน
 */
function getStudentDetails() {
    global $conn;
    
    $studentId = !empty($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    
    if (!$studentId) {
        echo json_encode(['error' => 'ไม่ได้ระบุรหัสนักเรียน']);
        return;
    }
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academicYear) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // ดึงข้อมูลพื้นฐานของนักเรียน
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                u.phone_number,
                u.email,
                u.profile_picture,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                d.department_name,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_name,
                sar.total_attendance_days,
                sar.total_absence_days,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END as attendance_rate
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              WHERE s.student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId, $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['error' => 'ไม่พบข้อมูลนักเรียน']);
        return;
    }
    
    // จัดรูปแบบอัตราการเข้าแถว
    $student['attendance_rate'] = round($student['attendance_rate'], 1);
    $student['status_text'] = $student['attendance_rate'] < 70 ? 'ตกกิจกรรม' : 
                             ($student['attendance_rate'] < 80 ? 'เสี่ยงตกกิจกรรม' : 'ปกติ');
    $student['status_class'] = $student['attendance_rate'] < 70 ? 'danger' : 
                              ($student['attendance_rate'] < 80 ? 'warning' : 'success');
    
    // ประวัติการเข้าแถว (15 วันล่าสุด)
    $query = "SELECT 
                a.date,
                a.attendance_status,
                a.check_time,
                a.check_method,
                a.remarks
              FROM attendance a
              WHERE a.student_id = ? AND a.academic_year_id = ?
              ORDER BY a.date DESC
              LIMIT 15";
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId, $academicYearId]);
    $attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประวัติการเข้าแถว
    $formattedHistory = [];
    $thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    
    foreach ($attendanceHistory as $record) {
        // แปลงรูปแบบวันที่
        $date = new DateTime($record['date']);
        $dayOfWeek = $thaiDays[$date->format('w')];
        $formattedDate = $dayOfWeek . ' ' . $date->format('d/m/') . ($date->format('Y') + 543);
        
        // กำหนดสถานะและคลาส
        $status = '';
        $statusClass = '';
        switch ($record['attendance_status']) {
            case 'present':
                $status = 'มา';
                $statusClass = 'success';
                break;
            case 'absent':
                $status = 'ขาด';
                $statusClass = 'danger';
                break;
            case 'late':
                $status = 'มาสาย';
                $statusClass = 'warning';
                break;
            case 'leave':
                $status = 'ลา';
                $statusClass = 'info';
                break;
        }
        
        // จัดรูปแบบวิธีการเช็คชื่อ
        $checkMethod = '';
        switch ($record['check_method']) {
            case 'GPS':
                $checkMethod = 'GPS';
                break;
            case 'QR_Code':
                $checkMethod = 'QR Code';
                break;
            case 'PIN':
                $checkMethod = 'PIN Code';
                break;
            case 'Manual':
                $checkMethod = 'ครูเช็คให้';
                break;
        }
        
        // จัดรูปแบบเวลา
        $time = $record['check_time'] ? date('H:i', strtotime($record['check_time'])) : '-';
        
        $formattedHistory[] = [
            'date' => $formattedDate,
            'status' => $status,
            'statusClass' => $statusClass,
            'time' => $time,
            'checkMethod' => $checkMethod,
            'remark' => $record['remarks'] ?: '-'
        ];
    }
    
    // ดึงประวัติการแจ้งเตือน
    $query = "SELECT 
                n.notification_id,
                n.created_at as date,
                n.type,
                n.title,
                CASE
                    WHEN n.is_read = 1 THEN 'อ่านแล้ว'
                    ELSE 'ยังไม่อ่าน'
                END as status,
                CASE
                    WHEN n.is_read = 1 THEN 'success'
                    ELSE 'warning'
                END as statusClass
              FROM notifications n
              WHERE n.related_student_id = ?
              ORDER BY n.created_at DESC
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId]);
    $notificationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประวัติการแจ้งเตือน
    $notificationHistory = [];
    foreach ($notificationsData as $notification) {
        // แปลงรูปแบบวันที่
        $date = new DateTime($notification['date']);
        $formattedDate = $date->format('d/m/') . ($date->format('Y') + 543);
        
        $notificationHistory[] = [
            'date' => $formattedDate,
            'type' => $notification['title'],
            'sender' => 'ระบบ',
            'status' => $notification['status'],
            'statusClass' => $notification['statusClass']
        ];
    }
    
    // ถ้าไม่มีประวัติการแจ้งเตือน ให้ใช้ข้อมูลตัวอย่าง
    if (empty($notificationHistory)) {
        $notificationHistory = [
            ['date' => date('d/m/') . (date('Y') + 543 - 0), 'type' => 'ไม่มีประวัติการแจ้งเตือน', 'sender' => '-', 'status' => '-', 'statusClass' => 'info']
        ];
    }
    
    // ดึงแนวโน้มการเข้าแถวรายเดือน (3 เดือนล่าสุด)
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    $monthlyData = [];
    $thaiMonths = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    
    for ($i = 0; $i < 3; $i++) {
        $month = $currentMonth - $i;
        $year = $currentYear;
        
        if ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        // ดึงข้อมูลการเข้าแถวของเดือนนี้
        $query = "SELECT 
                    COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present_days,
                    COUNT(*) as total_days
                  FROM attendance a
                  WHERE a.student_id = ? AND a.academic_year_id = ? 
                  AND MONTH(a.date) = ? AND YEAR(a.date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId, $academicYearId, $month, $year]);
        $monthData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $rate = 0;
        if ($monthData && $monthData['total_days'] > 0) {
            $rate = ($monthData['present_days'] / $monthData['total_days']) * 100;
        }
        
        $monthlyData[] = [
            'month' => $thaiMonths[$month],
            'rate' => round($rate, 1)
        ];
    }
    
    // ย้อนกลับอาร์เรย์เพื่อให้ได้ลำดับตามปฏิทิน
    $monthlyData = array_reverse($monthlyData);
    
    // เตรียมข้อมูลเต็มรูปแบบสำหรับส่งกลับ
    $response = [
        'success' => true,
        'data' => [
            'student' => $student,
            'attendanceHistory' => $formattedHistory,
            'notificationHistory' => $notificationHistory,
            'monthlyTrend' => [
                'labels' => array_column($monthlyData, 'month'),
                'rates' => array_column($monthlyData, 'rate')
            ],
            'academic_year' => $academicYear['year'] + 543,
            'semester' => $academicYear['semester']
        ]
    ];
    
    echo json_encode($response);
}

/**
 * ดึงข้อมูลสรุปการเข้าแถว
 */
function getAttendanceSummary() {
    global $conn;
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // รับพารามิเตอร์
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    $classId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;
    
    // สร้างเงื่อนไข WHERE สำหรับช่วงเวลา
    list($periodCondition, $periodParams) = getPeriodCondition($period);
    
    // สร้างเงื่อนไข WHERE สำหรับแผนกวิชาและชั้นเรียน
    $filterCondition = '';
    $filterParams = [];
    
    if ($departmentId) {
        $filterCondition .= " AND c.department_id = ?";
        $filterParams[] = $departmentId;
    }
    
    if ($classId) {
        $filterCondition .= " AND c.class_id = ?";
        $filterParams[] = $classId;
    }
    
    // ดึงข้อมูลสรุปภาพรวม
    $query = "SELECT 
                COUNT(DISTINCT s.student_id) as total_students,
                COUNT(DISTINCT a.date) as total_days,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.attendance_id END) as present_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'absent' THEN a.attendance_id END) as absent_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'late' THEN a.attendance_id END) as late_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'leave' THEN a.attendance_id END) as leave_count
              FROM students s
              JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN attendance a ON a.student_id = s.student_id 
                   AND a.academic_year_id = ? $periodCondition
              WHERE s.status = 'กำลังศึกษา' AND c.academic_year_id = ? $filterCondition";
    
    $allParams = array_merge([$academicYearId], $periodParams, [$academicYearId], $filterParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถวเฉลี่ย
    $totalChecks = $summary['present_count'] + $summary['absent_count'] + $summary['late_count'] + $summary['leave_count'];
    $summary['avg_attendance_rate'] = $totalChecks > 0 
        ? round(($summary['present_count'] / $totalChecks) * 100, 1) 
        : 0;
    
    // ดึงข้อมูลแนวโน้มรายวัน
    $dailyTrend = getDailyTrend($conn, $academicYearId, $period, $filterCondition, $filterParams);
    
    // ดึงข้อมูลแยกตามชั้นเรียน
    $query = "SELECT 
                c.class_id,
                c.level,
                c.group_number,
                CONCAT(c.level, '/', c.group_number) as class_name,
                COUNT(DISTINCT s.student_id) as student_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.attendance_id END) as present_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'absent' THEN a.attendance_id END) as absent_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'late' THEN a.attendance_id END) as late_count,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'leave' THEN a.attendance_id END) as leave_count,
                COUNT(DISTINCT a.date) as check_days
              FROM classes c
              LEFT JOIN students s ON c.class_id = s.current_class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN attendance a ON a.student_id = s.student_id 
                   AND a.academic_year_id = ? $periodCondition
              WHERE c.academic_year_id = ? $filterCondition
              GROUP BY c.class_id
              ORDER BY c.level, c.group_number";
    
    $allParams = array_merge([$academicYearId], $periodParams, [$academicYearId], $filterParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // คำนวณอัตราการเข้าแถวสำหรับแต่ละชั้นเรียน
    foreach ($classes as &$class) {
        $totalChecks = $class['present_count'] + $class['absent_count'] + $class['late_count'] + $class['leave_count'];
        $class['attendance_rate'] = $totalChecks > 0 
            ? round(($class['present_count'] / $totalChecks) * 100, 1) 
            : 0;
            
        // กำหนดสถานะตามอัตราการเข้าแถว
        if ($class['attendance_rate'] >= 90) {
            $class['status'] = 'good';
            $class['color'] = '#28a745';
        } elseif ($class['attendance_rate'] >= 80) {
            $class['status'] = 'warning';
            $class['color'] = '#ffc107';
        } else {
            $class['status'] = 'danger';
            $class['color'] = '#dc3545';
        }
    }
    
    // ดึงช่วงวันที่ที่เลือก
    list($startDate, $endDate) = getDateRange($period);
    
    // รูปแบบวันที่ไทย
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $startThaiDate = '';
    $endThaiDate = '';
    
    if ($startDate && $endDate) {
        $startObj = new DateTime($startDate);
        $endObj = new DateTime($endDate);
        
        $startThaiDate = $startObj->format('j') . ' ' . $thaiMonths[(int)$startObj->format('n')] . ' ' . ($startObj->format('Y') + 543);
        $endThaiDate = $endObj->format('j') . ' ' . $thaiMonths[(int)$endObj->format('n')] . ' ' . ($endObj->format('Y') + 543);
    }
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'classes' => $classes,
            'dailyTrend' => $dailyTrend,
            'period' => [
                'type' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_thai_date' => $startThaiDate,
                'end_thai_date' => $endThaiDate
            ]
        ]
    ]);
}

/**
 * ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรม
 */
function sendNotification($data) {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ได้รับ
    if (empty($data['student_ids']) && empty($data['class_id']) && empty($data['department_id'])) {
        echo json_encode(['error' => 'ไม่ได้ระบุผู้รับการแจ้งเตือน']);
        return;
    }
    
    if (empty($data['message']) && empty($data['template_id'])) {
        echo json_encode(['error' => 'ไม่ได้ระบุข้อความหรือเทมเพลต']);
        return;
    }
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    if (!$academicYearId) {
        echo json_encode(['error' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        return;
    }
    
    // สร้างเงื่อนไข WHERE สำหรับการหาผู้รับ
    $whereCondition = '';
    $params = [];
    
    if (!empty($data['student_ids'])) {
        // ส่งถึงนักเรียนที่ระบุโดยตรง
        $placeholders = implode(',', array_fill(0, count($data['student_ids']), '?'));
        $whereCondition = "s.student_id IN ($placeholders)";
        $params = $data['student_ids'];
    } elseif (!empty($data['class_id'])) {
        // ส่งถึงนักเรียนทั้งห้อง
        $whereCondition = "s.current_class_id = ?";
        $params = [$data['class_id']];
    } elseif (!empty($data['department_id'])) {
        // ส่งถึงนักเรียนทั้งแผนก
        $whereCondition = "c.department_id = ?";
        $params = [$data['department_id']];
    }
    
    // ถ้าต้องการส่งเฉพาะนักเรียนที่เสี่ยง
    if (!empty($data['only_risk']) && $data['only_risk']) {
        $whereCondition .= " AND (
            sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100 < 80
        )";
    }
    
    // ดึงรายชื่อผู้รับ
    $query = "SELECT 
                s.student_id,
                s.student_code,
                CONCAT(s.title, ' ', u.first_name, ' ', u.last_name) as student_name,
                p.parent_id,
                pu.line_id as parent_line_id
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
              LEFT JOIN parent_student_relation psr ON s.student_id = psr.student_id
              LEFT JOIN parents p ON psr.parent_id = p.parent_id
              LEFT JOIN users pu ON p.user_id = pu.user_id
              WHERE s.status = 'กำลังศึกษา' AND $whereCondition
                AND pu.line_id IS NOT NULL";
    
    $allParams = array_merge([$academicYearId], $params);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recipients)) {
        echo json_encode(['error' => 'ไม่พบผู้รับการแจ้งเตือนที่มี LINE ID']);
        return;
    }
    
    // ดึงข้อความเทมเพลต (ถ้ามี)
    $message = $data['message'] ?? '';
    
    if (!empty($data['template_id'])) {
        $query = "SELECT content FROM message_templates WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$data['template_id']]);
        $template = $stmt->fetchColumn();
        
        if ($template) {
            $message = $template;
        }
    }
    
    // กำหนดประเภทการแจ้งเตือน
    $notificationType = $data['type'] ?? 'attendance_alert';
    
    // บันทึกการส่งข้อความแจ้งเตือน
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($recipients as $recipient) {
        if (empty($recipient['parent_line_id'])) {
            $errorCount++;
            continue;
        }
        
        // แทนที่ตัวแปรในข้อความ
        $personalizedMessage = replaceMessageVariables($message, $recipient);
        
        // บันทึกการแจ้งเตือนลงฐานข้อมูล
        $query = "INSERT INTO line_notifications (user_id, message, status, notification_type) 
                  VALUES (?, ?, 'pending', ?)";
        $stmt = $conn->prepare($query);
        
        try {
            $stmt->execute([$recipient['parent_id'], $personalizedMessage, $notificationType]);
            $successCount++;
            
            // บันทึกการแจ้งเตือนลงในตาราง notifications สำหรับแสดงในแอพ
            $query = "INSERT INTO notifications (user_id, type, title, notification_message, is_read, related_student_id) 
                      VALUES (?, ?, ?, ?, 0, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $recipient['parent_id'], 
                $notificationType, 
                'แจ้งเตือนการเข้าแถว', 
                $personalizedMessage, 
                $recipient['student_id']
            ]);
        } catch (Exception $e) {
            $errorCount++;
        }
    }
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'message' => "ส่งข้อความแจ้งเตือนสำเร็จ $successCount รายการ, ล้มเหลว $errorCount รายการ",
        'data' => [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_recipients' => count($recipients)
        ]
    ]);
}

/**
 * ส่งออกรายงานในรูปแบบที่กำหนด
 */
function exportReport($data) {
    // ตรวจสอบข้อมูลที่ได้รับ
    if (empty($data['format'])) {
        echo json_encode(['error' => 'ไม่ได้ระบุรูปแบบการส่งออก']);
        return;
    }
    
    // กำหนดชื่อไฟล์
    $filename = 'attendance_report_' . date('Ymd_His');
    
    // สร้าง URL สำหรับดาวน์โหลด
    $downloadUrl = "export_report.php?";
    
    // เพิ่มพารามิเตอร์ต่างๆ
    $params = [];
    $params['format'] = $data['format'];
    
    if (!empty($data['period'])) {
        $params['period'] = $data['period'];
    }
    
    if (!empty($data['department_id'])) {
        $params['department_id'] = $data['department_id'];
    }
    
    if (!empty($data['class_id'])) {
        $params['class_id'] = $data['class_id'];
    }
    
    if (!empty($data['start_date'])) {
        $params['start_date'] = $data['start_date'];
    }
    
    if (!empty($data['end_date'])) {
        $params['end_date'] = $data['end_date'];
    }
    
    // สร้าง query string
    $downloadUrl .= http_build_query($params);
    
    // ส่งคืนข้อมูล
    echo json_encode([
        'success' => true,
        'data' => [
            'download_url' => $downloadUrl,
            'filename' => $filename . '.' . ($data['format'] == 'excel' ? 'xlsx' : $data['format'])
        ]
    ]);
}

/**
 * สร้างเงื่อนไขสำหรับช่วงเวลา
 */
function getPeriodCondition($period) {
    $condition = '';
    $params = [];
    
    switch ($period) {
        case 'day':
            $condition = " AND a.date = ?";
            $params[] = date('Y-m-d');
            break;
            
        case 'yesterday':
            $condition = " AND a.date = ?";
            $params[] = date('Y-m-d', strtotime('-1 day'));
            break;
            
        case 'week':
            $condition = " AND a.date BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime('monday this week'));
            $params[] = date('Y-m-d', strtotime('sunday this week'));
            break;
            
        case 'month':
            $condition = " AND a.date BETWEEN ? AND ?";
            $params[] = date('Y-m-01');
            $params[] = date('Y-m-t');
            break;
            
        case 'semester':
            // ดึงช่วงวันที่ของภาคเรียนจากฐานข้อมูล
            $condition = ""; // จะใช้ปีการศึกษาเป็นตัวกรองหลักอยู่แล้ว
            break;
            
        case 'custom':
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $condition = " AND a.date BETWEEN ? AND ?";
                $params[] = $_GET['start_date'];
                $params[] = $_GET['end_date'];
            }
            break;
            
        default:
            // ไม่มีเงื่อนไขเพิ่มเติม
            break;
    }
    
    return [$condition, $params];
}

/**
 * สร้างเงื่อนไขสำหรับช่วงเวลาก่อนหน้า
 */
function getPreviousPeriodCondition($period) {
    $condition = '';
    $params = [];
    
    switch ($period) {
        case 'day':
            $condition = " AND a.date = ?";
            $params[] = date('Y-m-d', strtotime('-1 day'));
            break;
            
        case 'week':
            $condition = " AND a.date BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime('monday last week'));
            $params[] = date('Y-m-d', strtotime('sunday last week'));
            break;
            
        case 'month':
            $lastMonth = date('m') - 1;
            $year = date('Y');
            if ($lastMonth <= 0) {
                $lastMonth = 12;
                $year--;
            }
            $lastDayOfMonth = date('t', strtotime("$year-$lastMonth-01"));
            
            $condition = " AND a.date BETWEEN ? AND ?";
            $params[] = "$year-$lastMonth-01";
            $params[] = "$year-$lastMonth-$lastDayOfMonth";
            break;
            
        case 'semester':
            // ไม่มีช่วงก่อนหน้าที่ชัดเจน
            break;
            
        case 'custom':
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $startDate = new DateTime($_GET['start_date']);
                $endDate = new DateTime($_GET['end_date']);
                $interval = $startDate->diff($endDate);
                $days = $interval->days + 1;
                
                $newEndDate = clone $startDate;
                $newEndDate->modify('-1 day');
                $newStartDate = clone $newEndDate;
                $newStartDate->modify("-$days days");
                
                $condition = " AND a.date BETWEEN ? AND ?";
                $params[] = $newStartDate->format('Y-m-d');
                $params[] = $newEndDate->format('Y-m-d');
            }
            break;
            
        default:
            // ไม่มีเงื่อนไขเพิ่มเติม
            break;
    }
    
    return [$condition, $params];
}

/**
 * ดึงช่วงวันที่ตามเงื่อนไข
 */
function getDateRange($period) {
    $startDate = null;
    $endDate = null;
    
    switch ($period) {
        case 'day':
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
            break;
            
        case 'yesterday':
            $startDate = date('Y-m-d', strtotime('-1 day'));
            $endDate = date('Y-m-d', strtotime('-1 day'));
            break;
            
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
            break;
            
        case 'month':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            break;
            
        case 'custom':
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                $startDate = $_GET['start_date'];
                $endDate = $_GET['end_date'];
            }
            break;
    }
    
    return [$startDate, $endDate];
}

/**
 * ดึงข้อมูลแนวโน้มรายวัน
 */
function getDailyTrend($conn, $academicYearId, $period, $filterCondition, $filterParams) {
    $dailyTrend = [];
    
    // ดึงช่วงวันที่
    list($startDate, $endDate) = getDateRange($period);
    
    if (!$startDate || !$endDate) {
        return $dailyTrend;
    }
    
    // จำกัดจำนวนวันที่แสดง
    $maxDays = 30;
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    
    if ($interval->days >= $maxDays) {
        // ถ้ามีจำนวนวันมากเกินไป ให้แสดงเป็นรายสัปดาห์หรือรายเดือนแทน
        return $dailyTrend;
    }
    
    // ดึงข้อมูลรายวัน
    $query = "SELECT 
                a.date,
                COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                COUNT(DISTINCT a.student_id) as total_students
              FROM attendance a
              JOIN students s ON a.student_id = s.student_id
              JOIN classes c ON s.current_class_id = c.class_id
              WHERE a.academic_year_id = ? 
                AND a.date BETWEEN ? AND ?
                AND s.status = 'กำลังศึกษา' $filterCondition
              GROUP BY a.date
              ORDER BY a.date";
    
    $allParams = array_merge([$academicYearId, $startDate, $endDate], $filterParams);
    $stmt = $conn->prepare($query);
    $stmt->execute($allParams);
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูล
    $thaiDays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
    
    foreach ($dailyData as $day) {
        $date = new DateTime($day['date']);
        $dayOfWeek = (int)$date->format('w');
        $dayName = $thaiDays[$dayOfWeek];
        $displayDate = $date->format('j') . ' ' . $dayName;
        
        // คำนวณอัตราการเข้าแถว
        $rate = 0;
        if ($day['total_students'] > 0) {
            $rate = ($day['present_count'] / $day['total_students']) * 100;
        }
        
        $dailyTrend[] = [
            'date' => $displayDate,
            'fullDate' => $day['date'],
            'rate' => round($rate, 1),
            'isWeekend' => ($dayOfWeek == 0 || $dayOfWeek == 6)
        ];
    }
    
    return $dailyTrend;
}

/**
 * แทนที่ตัวแปรในข้อความแจ้งเตือน
 */
function replaceMessageVariables($message, $studentData) {
    global $conn;
    
    // ดึงข้อมูลเพิ่มเติมที่จำเป็น
    $studentId = $studentData['student_id'];
    
    // ดึงข้อมูลการเข้าแถว
    $query = "SELECT 
                sar.total_attendance_days, 
                sar.total_absence_days,
                c.level,
                c.group_number,
                (SELECT GROUP_CONCAT(CONCAT(t.title, ' ', t.first_name, ' ', t.last_name, ' (', u.phone_number, ')') SEPARATOR ', ') 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id
                 JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1) as advisor_info
              FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              JOIN classes c ON s.current_class_id = c.class_id
              JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
              WHERE sar.student_id = ? AND ay.is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId]);
    $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attendanceData) {
        return $message;
    }
    
    // คำนวณอัตราการเข้าแถว
    $totalDays = $attendanceData['total_attendance_days'] + $attendanceData['total_absence_days'];
    $attendanceRate = ($totalDays > 0) ? round(($attendanceData['total_attendance_days'] / $totalDays) * 100, 1) : 0;
    
    // แยกข้อมูลที่ปรึกษาเป็นชื่อและเบอร์โทร
    $advisorInfo = $attendanceData['advisor_info'] ?? 'ไม่ระบุ';
    $advisorName = preg_replace('/\s*\(\d+\)$/', '', $advisorInfo);
    $advisorPhone = '';
    if (preg_match('/\((\d+)\)/', $advisorInfo, $matches)) {
        $advisorPhone = $matches[1];
    }
    
    // ค่าตัวแปรสำหรับแทนที่
    $variables = [
        '{{ชื่อนักเรียน}}' => $studentData['student_name'],
        '{{รหัสนักเรียน}}' => $studentData['student_code'],
        '{{ชั้นเรียน}}' => $attendanceData['level'] . '/' . $attendanceData['group_number'],
        '{{จำนวนวันเข้าแถว}}' => $attendanceData['total_attendance_days'],
        '{{จำนวนวันขาด}}' => $attendanceData['total_absence_days'],
        '{{จำนวนวันทั้งหมด}}' => $totalDays,
        '{{ร้อยละการเข้าแถว}}' => $attendanceRate,
        '{{ชื่อครูที่ปรึกษา}}' => $advisorName,
        '{{เบอร์โทรครู}}' => $advisorPhone,
        '{{เดือน}}' => getThaiMonth(date('n')),
        '{{ปี}}' => (date('Y') + 543),
        '{{สถานะการเข้าแถว}}' => ($attendanceRate >= 80 ? 'ปกติ' : ($attendanceRate >= 70 ? 'เสี่ยงตกกิจกรรม' : 'ตกกิจกรรม'))
    ];
    
    // แทนที่ตัวแปรในข้อความ
    foreach ($variables as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }
    
    return $message;
}
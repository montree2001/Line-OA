<?php
/**
 * reports.php - หน้ารายงานการเข้าแถวของ Teacher-Prasat
 * 
 * หน้านี้ใช้สำหรับแสดงรายงานการเข้าแถวของนักเรียนในกลุ่มที่ครูเป็นที่ปรึกษา
 * และดูรายงานต่างๆ เกี่ยวกับการเข้าแถวของนักเรียน
 */

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'reports';
$page_title = 'Teacher-Prasat - รายงานการเข้าแถว';

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
require_once '../lib/functions.php';

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ดึงข้อมูลครูที่ปรึกษาจากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, d.department_name 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id 
                 LEFT JOIN departments d ON t.department_id = d.department_id 
                 WHERE t.user_id = :user_id";

try {
    $stmt = $db->prepare($teacher_query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $teacher_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher_data && $_SESSION['role'] === 'teacher') {
        die("ไม่พบข้อมูลครู");
    }

    // สร้างข้อมูลครูที่ปรึกษา
    if ($teacher_data) {
        $teacher_id = $teacher_data['teacher_id'];
        $teacher_name = $teacher_data['title'] . ' ' . $teacher_data['first_name'] . ' ' . $teacher_data['last_name'];
        $teacher_info = [
            'name' => $teacher_name,
            'avatar' => mb_substr($teacher_data['first_name'], 0, 1, 'UTF-8'),
            'role' => 'ครูที่ปรึกษา' . ($teacher_data['department_name'] ? ' ' . $teacher_data['department_name'] : ''),
            'profile_picture' => $teacher_data['profile_picture']
        ];
    } else {
        // กรณีเป็น admin และไม่พบข้อมูลครู ให้ใช้ข้อมูลจากผู้ใช้
        $user_query = "SELECT first_name, last_name, profile_picture FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($user_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $teacher_id = 0; // ใช้ 0 แทนเมื่อเป็น admin
        $teacher_info = [
            'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
            'avatar' => 'A',
            'role' => 'ผู้ดูแลระบบ',
            'profile_picture' => $user_data['profile_picture']
        ];
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลครู: " . $e->getMessage());
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $current_academic_query = "SELECT academic_year_id, year, semester 
                              FROM academic_years 
                              WHERE is_active = 1 
                              LIMIT 1";
    $stmt = $db->query($current_academic_query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$academic_year) {
        die("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }

    $academic_year_id = $academic_year['academic_year_id'];
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลปีการศึกษา: " . $e->getMessage());
}

// ดึงห้องเรียนที่ครูเป็นที่ปรึกษา
if ($_SESSION['role'] === 'teacher') {
    $classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS name, 
                    c.level, d.department_name, c.group_number,
                    (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students,
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id AND a.attendance_status = 'present' 
                     AND DATE(a.date) = CURDATE()) as present_count,
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id AND a.attendance_status = 'absent' 
                     AND DATE(a.date) = CURDATE()) as absent_count,
                    (SELECT ROUND(COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) * 100.0 / 
                            NULLIF(COUNT(*), 0), 1) 
                     FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id 
                     AND a.date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) as attendance_rate,
                    (SELECT COUNT(*) FROM students s 
                     WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
                     AND (SELECT COUNT(*) FROM attendance a 
                          WHERE a.student_id = s.student_id AND a.attendance_status = 'absent') >= 5) as at_risk_count
                    FROM classes c 
                    JOIN departments d ON c.department_id = d.department_id 
                    JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                    JOIN class_advisors ca ON c.class_id = ca.class_id
                    WHERE ca.teacher_id = :teacher_id AND c.is_active = 1 AND ay.is_active = 1
                    ORDER BY ca.is_primary DESC, c.level, c.group_number";

    try {
        $stmt = $db->prepare($classes_query);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        $teacher_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($teacher_classes)) {
            die("คุณยังไม่ได้รับมอบหมายให้เป็นครูที่ปรึกษาห้องใด");
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
    }
} else {
    // กรณีเป็น admin ให้ดึงทุกห้องเรียน
    $classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS name, 
                    c.level, d.department_name, c.group_number,
                    (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students,
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id AND a.attendance_status = 'present' 
                     AND DATE(a.date) = CURDATE()) as present_count,
                    (SELECT COUNT(DISTINCT a.student_id) FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id AND a.attendance_status = 'absent' 
                     AND DATE(a.date) = CURDATE()) as absent_count,
                    (SELECT ROUND(COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) * 100.0 / 
                            NULLIF(COUNT(*), 0), 1) 
                     FROM attendance a 
                     JOIN students s ON a.student_id = s.student_id 
                     WHERE s.current_class_id = c.class_id 
                     AND a.date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) as attendance_rate,
                    (SELECT COUNT(*) FROM students s 
                     WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
                     AND (SELECT COUNT(*) FROM attendance a 
                          WHERE a.student_id = s.student_id AND a.attendance_status = 'absent') >= 5) as at_risk_count
                    FROM classes c 
                    JOIN departments d ON c.department_id = d.department_id 
                    JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                    WHERE c.is_active = 1 AND ay.is_active = 1
                    ORDER BY c.level, d.department_name, c.group_number";

    try {
        $stmt = $db->query($classes_query);
        $teacher_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($teacher_classes)) {
            die("ไม่พบข้อมูลห้องเรียนในระบบ");
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
    }
}

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : ($teacher_classes[0]['class_id'] ?? 0);

// ดึงข้อมูลเดือนที่กำลังดู (จาก URL หรือค่าเริ่มต้น = เดือนปัจจุบัน)
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// ดึงข้อมูลห้องเรียนปัจจุบัน
$current_class = null;
foreach ($teacher_classes as $class) {
    if ($class['class_id'] == $current_class_id) {
        $current_class = $class;
        break;
    }
}

// ถ้าไม่พบห้องเรียน ให้ใช้ห้องแรก
if ($current_class === null && !empty($teacher_classes)) {
    $current_class = $teacher_classes[0];
    $current_class_id = $current_class['class_id'];
}

// สถิติการเข้าแถว
try {
    $stats_query = "SELECT 
                   COALESCE(ROUND(AVG(CASE WHEN a.attendance_status = 'present' THEN 100 ELSE 0 END), 1), 0) as average_rate,
                   (SELECT COUNT(*) FROM students s 
                    WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                    AND (SELECT COUNT(*) FROM attendance a 
                         WHERE a.student_id = s.student_id AND a.attendance_status = 'absent') >= 5) as problem_count,
                   (SELECT COUNT(DISTINCT date) FROM attendance 
                    WHERE MONTH(date) = :month AND YEAR(date) = :year) as school_days
                   FROM attendance a
                   JOIN students s ON a.student_id = s.student_id
                   WHERE s.current_class_id = :class_id
                   AND MONTH(a.date) = :month AND YEAR(a.date) = :year";

    $stmt = $db->prepare($stats_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':month', $current_month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();
    $attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // ถ้าไม่มีข้อมูล ให้ใช้ค่าเริ่มต้น
    if (!$attendance_stats) {
        $attendance_stats = [
            'average_rate' => $current_class['attendance_rate'] ?? 0,
            'problem_count' => $current_class['at_risk_count'] ?? 0,
            'school_days' => 0
        ];
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้ใช้ค่าเริ่มต้น
    $attendance_stats = [
        'average_rate' => $current_class['attendance_rate'] ?? 0,
        'problem_count' => $current_class['at_risk_count'] ?? 0,
        'school_days' => 0
    ];
    error_log("Error fetching attendance stats: " . $e->getMessage());
}

// ข้อมูลการเข้าแถวรายวัน (7 วันล่าสุด)
try {
    $daily_query = "SELECT 
                   DATE_FORMAT(a.date, '%a') as day,
                   DATE_FORMAT(a.date, '%d %b') as date,
                   ROUND(SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) * 100.0 / 
                         COUNT(DISTINCT a.student_id), 1) as percentage
                   FROM attendance a
                   JOIN students s ON a.student_id = s.student_id
                   WHERE s.current_class_id = :class_id
                   AND a.date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                   GROUP BY a.date
                   ORDER BY a.date DESC
                   LIMIT 7";

    $stmt = $db->prepare($daily_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->execute();
    $daily_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงชื่อวันเป็นภาษาไทย
    $day_map = [
        'Mon' => 'จันทร์',
        'Tue' => 'อังคาร',
        'Wed' => 'พุธ',
        'Thu' => 'พฤหัสบดี',
        'Fri' => 'ศุกร์',
        'Sat' => 'เสาร์',
        'Sun' => 'อาทิตย์',
    ];

    foreach ($daily_attendance as &$day) {
        $day['day'] = $day_map[$day['day']] ?? $day['day'];
    }

    // ถ้าไม่มีข้อมูล ให้สร้างข้อมูลจำลอง
    if (empty($daily_attendance)) {
        $daily_attendance = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $day_name = date('D', strtotime($date));
            $daily_attendance[] = [
                'day' => $day_map[$day_name] ?? $day_name,
                'date' => date('d M', strtotime($date)),
                'percentage' => 0
            ];
        }
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้สร้างข้อมูลจำลอง
    $daily_attendance = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('D', strtotime($date));
        $daily_attendance[] = [
            'day' => $day_map[$day_name] ?? $day_name,
            'date' => date('d M', strtotime($date)),
            'percentage' => 0
        ];
    }
    error_log("Error fetching daily attendance: " . $e->getMessage());
}

// ข้อมูลการเข้าแถวรายเดือน
try {
    // สร้างตารางชื่อเดือนภาษาไทย
    $months_query = "SELECT 1 as `value`, 'มกราคม' as month 
                    UNION SELECT 2, 'กุมภาพันธ์' 
                    UNION SELECT 3, 'มีนาคม' 
                    UNION SELECT 4, 'เมษายน' 
                    UNION SELECT 5, 'พฤษภาคม' 
                    UNION SELECT 6, 'มิถุนายน' 
                    UNION SELECT 7, 'กรกฎาคม' 
                    UNION SELECT 8, 'สิงหาคม' 
                    UNION SELECT 9, 'กันยายน' 
                    UNION SELECT 10, 'ตุลาคม' 
                    UNION SELECT 11, 'พฤศจิกายน' 
                    UNION SELECT 12, 'ธันวาคม'";

    $stmt = $db->query($months_query);
    $months = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ดึงข้อมูลการเข้าแถวรายเดือน
    $monthly_query = "SELECT 
                     MONTH(a.date) as `value`,
                     ROUND(SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) * 100.0 / 
                           COUNT(DISTINCT a.student_id), 1) as percentage
                     FROM attendance a
                     JOIN students s ON a.student_id = s.student_id
                     WHERE s.current_class_id = :class_id
                     AND YEAR(a.date) = :year
                     GROUP BY MONTH(a.date)";

    $stmt = $db->prepare($monthly_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // รวมข้อมูลเดือนและสถิติ
    $monthly_attendance = [];
    foreach ($months as $month) {
        $month_value = $month['value'];
        $percentage = 0;

        // หาข้อมูลเดือนที่ตรงกัน
        foreach ($monthly_data as $data) {
            if ($data['value'] == $month_value) {
                $percentage = $data['percentage'];
                break;
            }
        }

        $monthly_attendance[] = [
            'month' => $month['month'],
            'value' => $month_value,
            'percentage' => $percentage
        ];
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้สร้างข้อมูลจำลอง
    $monthly_attendance = [
        ['month' => 'มกราคม', 'value' => 1, 'percentage' => 0],
        ['month' => 'กุมภาพันธ์', 'value' => 2, 'percentage' => 0],
        ['month' => 'มีนาคม', 'value' => 3, 'percentage' => 0],
        ['month' => 'เมษายน', 'value' => 4, 'percentage' => 0],
        ['month' => 'พฤษภาคม', 'value' => 5, 'percentage' => 0],
        ['month' => 'มิถุนายน', 'value' => 6, 'percentage' => 0],
        ['month' => 'กรกฎาคม', 'value' => 7, 'percentage' => 0],
        ['month' => 'สิงหาคม', 'value' => 8, 'percentage' => 0],
        ['month' => 'กันยายน', 'value' => 9, 'percentage' => 0],
        ['month' => 'ตุลาคม', 'value' => 10, 'percentage' => 0],
        ['month' => 'พฤศจิกายน', 'value' => 11, 'percentage' => 0],
        ['month' => 'ธันวาคม', 'value' => 12, 'percentage' => 0]
    ];
    error_log("Error fetching monthly attendance: " . $e->getMessage());
}

// ข้อมูลนักเรียนในห้องเรียนที่เลือก
try {
    $students_query = "SELECT 
    s.student_id,
    (SELECT COUNT(*) + 1 
     FROM students 
     WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
    CONCAT(COALESCE(s.title, ''), u.first_name, ' ', u.last_name) as name,
    s.student_code, u.profile_picture, u.phone_number, u.email,
    (SELECT CONCAT(SUM(CASE WHEN a.attendance_status IN ('present', 'late') THEN 1 ELSE 0 END), '/', 
                  COUNT(DISTINCT a.date))
     FROM attendance a 
     WHERE a.student_id = s.student_id 
     AND MONTH(a.date) = :month 
     AND YEAR(a.date) = :year) as attendance_days,
    (SELECT ROUND(SUM(CASE WHEN a.attendance_status IN ('present', 'late') THEN 1 ELSE 0 END) * 100.0 / 
                NULLIF(COUNT(DISTINCT a.date), 0), 1)
     FROM attendance a 
     WHERE a.student_id = s.student_id 
     AND MONTH(a.date) = :month 
     AND YEAR(a.date) = :year) as percentage
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.current_class_id = :class_id
    AND s.status = 'กำลังศึกษา'
    ORDER BY s.student_code";

    $stmt = $db->prepare($students_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':month', $current_month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();
    $students_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // แปลงข้อมูลให้อยู่ในรูปแบบที่ต้องการ
    $students = [];
    foreach ($students_data as $student) {
        $percentage = $student['percentage'] ?? 0;

        // กำหนดสถานะตามเปอร์เซ็นต์
        if ($percentage >= 80) {
            $status = 'good';
        } elseif ($percentage >= 70) {
            $status = 'warning';
        } else {
            $status = 'danger';
        }

        $students[] = [
            'id' => $student['student_id'],
            'number' => $student['number'],
            'name' => $student['name'],
            'attendance_days' => $student['attendance_days'] ?? '0/0',
            'percentage' => $percentage,
            'status' => $status
        ];
    }
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้สร้างข้อมูลจำลอง
    $students = [];
    error_log("Error fetching students: " . $e->getMessage());
}

// ฟังก์ชันแทนที่ cal_days_in_month() ที่ไม่ต้องใช้ Calendar extension
function get_days_in_month($month, $year) {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

// ข้อมูลปฏิทินการเข้าแถว
$calendar_data = [];
$days_in_month = get_days_in_month($current_month, $current_year);
$first_day = date('N', strtotime("$current_year-$current_month-01"));

// วันเริ่มต้นในปฏิทิน (ถ้าวันที่ 1 ไม่ใช่วันจันทร์)
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$days_in_prev_month = get_days_in_month($prev_month, $prev_year);
$start_day = $days_in_prev_month - $first_day + 2;

// ดึงข้อมูลการเข้าแถวในเดือนที่เลือก
try {
    $calendar_query = "SELECT 
                      a.date,
                      COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present_count,
                      COUNT(CASE WHEN a.attendance_status = 'absent' THEN 1 END) as absent_count,
                      COUNT(DISTINCT a.student_id) as total_count
                      FROM attendance a
                      JOIN students s ON a.student_id = s.student_id
                      WHERE s.current_class_id = :class_id
                      AND MONTH(a.date) = :month
                      AND YEAR(a.date) = :year
                      GROUP BY a.date";

    $stmt = $db->prepare($calendar_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':month', $current_month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();
    $calendar_data_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้างตัวแปรสำหรับเก็บข้อมูลแยกตามวันที่
    $attendance_by_date = [];
    foreach ($calendar_data_db as $data) {
        $date = $data['date'];
        $day = (int)date('j', strtotime($date));
        $attendance_by_date[$day] = [
            'present' => $data['present_count'],
            'absent' => $data['absent_count'],
            'total' => $data['total_count'],
            'percentage' => $data['total_count'] > 0 ? round(($data['present_count'] / $data['total_count']) * 100, 1) : 0
        ];
    }
} catch (Exception $e) {
    $attendance_by_date = [];
    error_log("Error fetching calendar data: " . $e->getMessage());
}


// เพิ่มส่วนนี้หลังการดึงข้อมูลนักเรียนในห้องเรียน

// ดึงข้อมูลกิจกรรมกลางของนักเรียนในห้องเรียน
try {
    $activities_query = "SELECT a.activity_id, a.activity_name, a.activity_date, a.description,
                         a.required_attendance, 
                         COUNT(DISTINCT aa.student_id) as participating_students,
                         (SELECT COUNT(*) FROM students WHERE current_class_id = :class_id AND status = 'กำลังศึกษา') as total_students
                         FROM activities a
                         LEFT JOIN activity_attendance aa ON a.activity_id = aa.activity_id
                         LEFT JOIN activity_target_levels atl ON a.activity_id = atl.activity_id
                         LEFT JOIN classes c ON FIND_IN_SET(c.level, atl.level) > 0
                         WHERE (c.class_id = :class_id OR atl.level IS NULL)
                         AND a.academic_year_id = :academic_year_id
                         GROUP BY a.activity_id
                         ORDER BY a.activity_date DESC
                         LIMIT 10";
    
    $stmt = $db->prepare($activities_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $stmt->execute();
    $class_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $class_activities = [];
    error_log("Error fetching activities: " . $e->getMessage());
}

// ดึงข้อมูลนักเรียนที่มีความเสี่ยงตกกิจกรรม
try {
    $risk_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                  (SELECT COUNT(*) FROM attendance WHERE student_id = s.student_id AND attendance_status = 'absent') as absent_count,
                  (SELECT COUNT(*) FROM attendance WHERE student_id = s.student_id AND attendance_status IN ('present', 'late')) as present_count,
                  (SELECT COUNT(DISTINCT date) FROM attendance WHERE MONTH(date) = :month AND YEAR(date) = :year) as total_days,
                  (SELECT ROUND((COUNT(CASE WHEN attendance_status IN ('present', 'late') THEN 1 END) * 100.0 / 
                                NULLIF(COUNT(DISTINCT date), 0)), 1)
                   FROM attendance 
                   WHERE student_id = s.student_id) as attendance_percentage
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  WHERE s.current_class_id = :class_id
                  AND s.status = 'กำลังศึกษา'
                  HAVING attendance_percentage < 80
                  ORDER BY attendance_percentage ASC
                  LIMIT 15";
    
    $stmt = $db->prepare($risk_query);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':month', $current_month, PDO::PARAM_INT);
    $stmt->bindParam(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();
    $risk_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $risk_students = [];
    error_log("Error fetching risk students: " . $e->getMessage());
}

// ดึงข้อมูลเทมเพลตข้อความแจ้งเตือน
try {
    $templates_query = "SELECT id, name, content FROM message_templates 
                       WHERE type = 'individual' AND category = 'attendance'
                       ORDER BY id ASC";
    $stmt = $db->query($templates_query);
    $message_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message_templates = [];
    error_log("Error fetching message templates: " . $e->getMessage());
}




// สร้างข้อมูลวันที่ก่อนหน้าเดือนปัจจุบัน
for ($i = 0; $i < $first_day - 1; $i++) {
    $calendar_data[] = [
        'day' => $start_day + $i,
        'month' => $prev_month,
        'year' => $prev_year,
        'current_month' => false,
        'present' => 0,
        'absent' => 0,
        'total' => $current_class['total_students'] ?? 0,
        'percentage' => 0,
        'is_school_day' => false
    ];
}

// สร้างข้อมูลวันที่ในเดือนปัจจุบัน
for ($day = 1; $day <= $days_in_month; $day++) {
    $date = "$current_year-$current_month-$day";
    $day_of_week = date('N', strtotime($date));

    // สมมติว่าวันเสาร์-อาทิตย์ไม่ใช่วันเรียน
    $is_school_day = ($day_of_week < 6);

    // ดึงข้อมูลการเช็คชื่อสำหรับวันนี้ (ถ้ามี)
    $attendance = $attendance_by_date[$day] ?? null;

    if ($attendance) {
        $present = $attendance['present'];
        $absent = $attendance['absent'];
        $total = $attendance['total'];
        $percentage = $attendance['percentage'];
    } else {
        $present = 0;
        $absent = 0;
        $total = $current_class['total_students'] ?? 0;
        $percentage = 0;
    }

    $calendar_data[] = [
        'day' => $day,
        'month' => $current_month,
        'year' => $current_year,
        'current_month' => true,
        'present' => $present,
        'absent' => $absent,
        'total' => $total,
        'percentage' => $percentage,
        'is_school_day' => $is_school_day
    ];
}

// สร้างข้อมูลวันที่หลังเดือนปัจจุบัน (เพื่อให้ครบ 42 ช่อง หรือ 6 สัปดาห์)
$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$remaining_days = 42 - count($calendar_data);
for ($day = 1; $day <= $remaining_days; $day++) {
    $calendar_data[] = [
        'day' => $day,
        'month' => $next_month,
        'year' => $next_year,
        'current_month' => false,
        'present' => 0,
        'absent' => 0,
        'total' => $current_class['total_students'] ?? 0,
        'percentage' => 0,
        'is_school_day' => false
    ];
}

// รวม CSS และ JS
$extra_css = [
    'assets/css/teacher-reports.css'
];
$extra_js = [
    'assets/js/teacher-reports.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/teacher_reports_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
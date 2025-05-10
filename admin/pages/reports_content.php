<?php
// เชื่อมต่อกับฐานข้อมูล (ถ้ายังไม่เชื่อมต่อ)
if (!function_exists('getDB')) {
    require_once '../db_connect.php';
}

// ฟังก์ชันสำหรับดึงข้อมูลสถิติภาพรวม
function getOverallStats() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id, year, semester, start_date, end_date, required_attendance_days 
                FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academicYear) {
            return [
                'total_students' => 0,
                'attendance_days' => 0,
                'avg_attendance_rate' => 0,
                'risk_students' => 0,
                'academic_year' => 'ไม่พบข้อมูล',
                'semester' => '',
                'required_days' => 0,
                'elapsed_days' => 0
            ];
        }
        
        $academicYearId = $academicYear['academic_year_id'];
        
        // จำนวนนักเรียนทั้งหมด
        $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
        $stmt = $conn->query($query);
        $totalStudents = $stmt->fetchColumn();
        
        // จำนวนวันเข้าแถวทั้งหมดในภาคเรียนปัจจุบัน
        $query = "SELECT COUNT(DISTINCT date) 
                FROM attendance 
                WHERE academic_year_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $attendanceDays = $stmt->fetchColumn();
        
        // จำนวนวันที่ผ่านไปแล้วในภาคเรียน (ไม่นับวันหยุด)
        $startDate = new DateTime($academicYear['start_date']);
        $today = new DateTime();
        $interval = $startDate->diff($today);
        $daysPassed = $interval->days;
        
        // วันที่ต้องเข้าแถวตามเกณฑ์
        $requiredDays = $academicYear['required_attendance_days'];
        
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
        
        // ดึงเกณฑ์ความเสี่ยง
        $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
        $stmt = $conn->query($query);
        $riskThreshold = $stmt->fetchColumn() ?: 60;
        
        // จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
        $query = "SELECT COUNT(*) FROM student_academic_records sar
                JOIN students s ON sar.student_id = s.student_id
                WHERE sar.academic_year_id = ? AND s.status = 'กำลังศึกษา'
                AND (
                    CASE 
                        WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                        THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                        ELSE 100 
                    END
                ) <= ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $riskThreshold]);
        $riskStudents = $stmt->fetchColumn();
        
        // อัตราการเข้าแถวเฉลี่ยเดือนที่แล้ว
        $lastMonth = date('m', strtotime('-1 month'));
        $lastMonthYear = date('Y', strtotime('-1 month'));
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count_last,
                    (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students_last,
                    COUNT(DISTINCT a.date) as total_days_last
                FROM attendance a
                WHERE a.academic_year_id = ? 
                AND MONTH(a.date) = ? 
                AND YEAR(a.date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $lastMonth, $lastMonthYear]);
        $lastMonthData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count_current,
                    (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students_current,
                    COUNT(DISTINCT a.date) as total_days_current
                FROM attendance a
                WHERE a.academic_year_id = ? 
                AND MONTH(a.date) = ? 
                AND YEAR(a.date) = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $currentMonth, $currentYear]);
        $currentMonthData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $lastMonthRate = 0;
        if ($lastMonthData['total_days_last'] > 0 && $lastMonthData['total_students_last'] > 0) {
            $lastMonthRate = ($lastMonthData['present_count_last'] / $lastMonthData['total_students_last']) * 100;
        }
        
        $currentMonthRate = 0;
        if ($currentMonthData['total_days_current'] > 0 && $currentMonthData['total_students_current'] > 0) {
            $currentMonthRate = ($currentMonthData['present_count_current'] / $currentMonthData['total_students_current']) * 100;
        }
        
        $rateChange = $currentMonthRate - $lastMonthRate;
        
        return [
            'total_students' => $totalStudents,
            'attendance_days' => $attendanceDays,
            'avg_attendance_rate' => $avgAttendanceRate,
            'rate_change' => $rateChange,
            'risk_students' => $riskStudents,
            'academic_year' => $academicYear['year'],
            'semester' => $academicYear['semester'],
            'current_month' => getThaiMonth(date('m')),
            'required_days' => $requiredDays,
            'elapsed_days' => $daysPassed
        ];
    } catch (PDOException $e) {
        error_log("Error fetching overall stats: " . $e->getMessage());
        return [
            'total_students' => 0,
            'attendance_days' => 0,
            'avg_attendance_rate' => 0,
            'risk_students' => 0,
            'academic_year' => 'เกิดข้อผิดพลาด',
            'semester' => '',
            'required_days' => 0,
            'elapsed_days' => 0
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลนักเรียนที่เสี่ยงตก
function getRiskStudents($limit = 5) {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            return [];
        }
        
        // ดึงเกณฑ์ความเสี่ยง
        $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
        $stmt = $conn->query($query);
        $riskThreshold = $stmt->fetchColumn() ?: 60;
        
        // ดึงข้อมูลนักเรียนที่เสี่ยงตก
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
                ) <= ?
                ORDER BY attendance_rate ASC
                LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $riskThreshold, $limit]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $students;
    } catch (PDOException $e) {
        error_log("Error fetching risk students: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลอัตราการเข้าแถวตามห้องเรียน
function getClassAttendanceRates() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            return [];
        }
        
        // ดึงข้อมูลอัตราการเข้าแถวตามห้องเรียน
        $query = "SELECT 
                    c.class_id,
                    c.level,
                    c.group_number,
                    CONCAT(c.level, '/', c.group_number) as class_name,
                    d.department_name,
                    COUNT(DISTINCT s.student_id) as student_count,
                    SUM(sar.total_attendance_days) as total_attendance,
                    SUM(sar.total_absence_days) as total_absence,
                    CASE
                        WHEN SUM(sar.total_attendance_days) + SUM(sar.total_absence_days) > 0
                        THEN (SUM(sar.total_attendance_days) / (SUM(sar.total_attendance_days) + SUM(sar.total_absence_days)) * 100)
                        ELSE 100
                    END as attendance_rate
                FROM classes c
                JOIN departments d ON c.department_id = d.department_id
                LEFT JOIN students s ON s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
                LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
                WHERE c.academic_year_id = ?
                GROUP BY c.class_id
                ORDER BY attendance_rate DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $academicYearId]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $classes;
    } catch (PDOException $e) {
        error_log("Error fetching class attendance rates: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลอัตราการเข้าแถวตลอดปีการศึกษา
function getYearlyAttendanceTrends() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academicYear) {
            return [];
        }
        
        $academicYearId = $academicYear['academic_year_id'];
        $startDate = $academicYear['start_date'];
        $endDate = $academicYear['end_date'];
        
        // สร้างอาร์เรย์ของเดือน
        $months = [];
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        $today = new DateTime();
        
        if ($today < $endDateObj) {
            $endDateObj = $today;
        }
        
        while ($currentDate <= $endDateObj) {
            $month = $currentDate->format('m');
            $year = $currentDate->format('Y');
            
            $months[] = [
                'month' => $month,
                'year' => $year,
                'month_name' => getThaiMonth($month)
            ];
            
            $currentDate->modify('+1 month');
        }
        
        // ดึงข้อมูลอัตราการเข้าแถวรายเดือน
        $trends = [];
        
        foreach ($months as $monthData) {
            $month = $monthData['month'];
            $year = $monthData['year'];
            
            $query = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count,
                        (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students,
                        COUNT(DISTINCT a.date) as attendance_days
                    FROM attendance a
                    WHERE a.academic_year_id = ? 
                    AND MONTH(a.date) = ? 
                    AND YEAR(a.date) = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$academicYearId, $month, $year]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $rate = 0;
            if ($data['attendance_days'] > 0 && $data['total_students'] > 0) {
                $rate = ($data['present_count'] / ($data['total_students'] * $data['attendance_days'])) * 100;
            }
            
            $trends[] = [
                'month' => $monthData['month_name'],
                'rate' => $rate
            ];
        }
        
        return $trends;
    } catch (PDOException $e) {
        error_log("Error fetching yearly attendance trends: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลสาเหตุการขาดแถว
function getAbsenceReasons() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            return [];
        }
        
        // หาจำนวนการขาดแถวตามสถานะ
        $query = "SELECT 
                    attendance_status,
                    COUNT(*) as count
                FROM attendance
                WHERE academic_year_id = ? AND attendance_status != 'present'
                GROUP BY attendance_status";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $absenceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบข้อมูล
        $totalAbsences = 0;
        $reasonCounts = [
            'absent' => 0,
            'late' => 0,
            'leave' => 0
        ];
        
        foreach ($absenceData as $data) {
            $status = $data['attendance_status'];
            $count = $data['count'];
            
            if (isset($reasonCounts[$status])) {
                $reasonCounts[$status] = $count;
                $totalAbsences += $count;
            }
        }
        
        // คำนวณเปอร์เซ็นต์และสร้างข้อมูลส่งกลับ
        $reasons = [];
        
        if ($totalAbsences > 0) {
            $reasons = [
                ['reason' => 'ขาด', 'count' => $reasonCounts['absent'], 'percent' => ($reasonCounts['absent'] / $totalAbsences) * 100],
                ['reason' => 'มาสาย', 'count' => $reasonCounts['late'], 'percent' => ($reasonCounts['late'] / $totalAbsences) * 100],
                ['reason' => 'ลา', 'count' => $reasonCounts['leave'], 'percent' => ($reasonCounts['leave'] / $totalAbsences) * 100]
            ];
        } else {
            $reasons = [
                ['reason' => 'ขาด', 'count' => 0, 'percent' => 0],
                ['reason' => 'มาสาย', 'count' => 0, 'percent' => 0],
                ['reason' => 'ลา', 'count' => 0, 'percent' => 0]
            ];
        }
        
        return $reasons;
    } catch (PDOException $e) {
        error_log("Error fetching absence reasons: " . $e->getMessage());
        return [
            ['reason' => 'ขาด', 'count' => 0, 'percent' => 0],
            ['reason' => 'มาสาย', 'count' => 0, 'percent' => 0],
            ['reason' => 'ลา', 'count' => 0, 'percent' => 0]
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลแผนกวิชา
function getDepartments() {
    $conn = getDB();
    
    try {
        $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
        $stmt = $conn->query($query);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $departments;
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลระดับชั้น
function getClassLevels() {
    $conn = getDB();
    
    try {
        $query = "SELECT DISTINCT level FROM classes WHERE is_active = 1 ORDER BY level";
        $stmt = $conn->query($query);
        $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $levels;
    } catch (PDOException $e) {
        error_log("Error fetching class levels: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลการเข้าแถวตามแผนก
function getDepartmentAttendanceStats() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            return [];
        }
        
        // ดึงข้อมูลอัตราการเข้าแถวตามแผนก
        $query = "SELECT 
                    d.department_id,
                    d.department_name,
                    COUNT(DISTINCT s.student_id) as student_count,
                    SUM(sar.total_attendance_days) as total_attendance,
                    SUM(sar.total_absence_days) as total_absence,
                    CASE
                        WHEN SUM(sar.total_attendance_days) + SUM(sar.total_absence_days) > 0
                        THEN (SUM(sar.total_attendance_days) / (SUM(sar.total_attendance_days) + SUM(sar.total_absence_days)) * 100)
                        ELSE 100
                    END as attendance_rate
                FROM departments d
                JOIN classes c ON d.department_id = c.department_id
                LEFT JOIN students s ON s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
                LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
                WHERE c.academic_year_id = ? AND d.is_active = 1
                GROUP BY d.department_id
                ORDER BY attendance_rate DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId, $academicYearId]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $departments;
    } catch (PDOException $e) {
        error_log("Error fetching department attendance stats: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลการเข้าแถวรายวัน (7 วันล่าสุด)
function getDailyAttendanceStats() {
    $conn = getDB();
    
    try {
        // ดึงปีการศึกษาปัจจุบัน
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
        
        if (!$academicYearId) {
            return [];
        }
        
        // ดึงข้อมูล 7 วันย้อนหลัง
        $query = "SELECT 
                    a.date,
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count,
                    COUNT(DISTINCT a.student_id) as total_students,
                    CASE
                        WHEN COUNT(DISTINCT a.student_id) > 0
                        THEN (COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) / COUNT(DISTINCT a.student_id)) * 100
                        ELSE 0
                    END as attendance_rate
                FROM attendance a
                WHERE a.academic_year_id = ?
                GROUP BY a.date
                ORDER BY a.date DESC
                LIMIT 7";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดรูปแบบวันที่และเรียงวันที่ใหม่
        foreach ($dailyStats as &$day) {
            // แปลงรูปแบบวันที่
            $date = new DateTime($day['date']);
            $day['formatted_date'] = $date->format('d/m/Y');
            $day['day_name'] = getThaiDayName($date->format('N'));
            $day['short_date'] = $date->format('d') . ' ' . getThaiMonthShort($date->format('m'));
        }
        
        // เรียงตามวันที่จากเก่าไปใหม่
        usort($dailyStats, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        return $dailyStats;
    } catch (PDOException $e) {
        error_log("Error fetching daily attendance stats: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันแปลงชื่อวันเป็นภาษาไทย
function getThaiDayName($dayOfWeek) {
    $thaiDays = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์'
    ];
    
    return isset($thaiDays[$dayOfWeek]) ? 'วัน' . $thaiDays[$dayOfWeek] : '';
}

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// ฟังก์ชันแปลงเดือนเป็นภาษาไทยแบบย่อ
function getThaiMonthShort($month) {
    $thaiMonths = [
        1 => 'ม.ค.',
        2 => 'ก.พ.',
        3 => 'มี.ค.',
        4 => 'เม.ย.',
        5 => 'พ.ค.',
        6 => 'มิ.ย.',
        7 => 'ก.ค.',
        8 => 'ส.ค.',
        9 => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.'
    ];
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// ดึงข้อมูลสำหรับรายงาน
$stats = getOverallStats();
$riskStudents = getRiskStudents(10);
$classRates = getClassAttendanceRates();
$yearlyTrends = getYearlyAttendanceTrends();
$absenceReasons = getAbsenceReasons();
$departments = getDepartments();
$classLevels = getClassLevels();
$departmentStats = getDepartmentAttendanceStats();
$dailyStats = getDailyAttendanceStats();

// แปลงข้อมูลให้อยู่ในรูปแบบ JSON สำหรับใช้ใน JavaScript
$jsonData = [
    'stats' => $stats,
    'riskStudents' => $riskStudents,
    'classRates' => $classRates,
    'yearlyTrends' => $yearlyTrends,
    'absenceReasons' => $absenceReasons,
    'departmentStats' => $departmentStats,
    'dailyStats' => $dailyStats
];
?>

<!-- คำสั่ง JavaScript สำหรับตัวแปรข้อมูลรายงาน -->
<script>
    // ข้อมูลสถิติสำหรับแสดงในกราฟ
    const reportData = <?php echo json_encode($jsonData); ?>;
</script>

<!-- โครงสร้างหน้ารายงาน -->
<div class="reports-container">
    <!-- แผงค้นหาและกรองข้อมูล -->
    <div class="report-card filter-card">
        <div class="report-header">
            <h2><i class="material-icons">filter_list</i> ตัวกรองรายงาน</h2>
            <div class="card-actions">
                <button class="action-button toggle-filters"><i class="material-icons">expand_more</i></button>
            </div>
        </div>
        <div class="filter-body">
            <div class="filters-grid">
                <div class="filter-item">
                    <label for="reportType">ประเภทรายงาน</label>
                    <select id="reportType" class="form-control">
                        <option value="overview" selected>ภาพรวมทั้งหมด</option>
                        <option value="daily">รายวัน</option>
                        <option value="weekly">รายสัปดาห์</option>
                        <option value="monthly">รายเดือน</option>
                        <option value="department">ตามแผนกวิชา</option>
                        <option value="class">ตามชั้นเรียน</option>
                        <option value="student">รายบุคคล</option>
                    </select>
                </div>
                
                <div class="filter-item" id="departmentFilter">
                    <label for="department">แผนกวิชา</label>
                    <select id="department" class="form-control">
                        <option value="">ทุกแผนก</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" id="periodFilter">
                    <label for="period">ช่วงเวลา</label>
                    <select id="period" class="form-control">
                        <option value="current">เดือนปัจจุบัน (<?php echo $stats['current_month']; ?>)</option>
                        <option value="last7">7 วันล่าสุด</option>
                        <option value="last30">30 วันล่าสุด</option>
                        <option value="semester">ภาคเรียนที่ <?php echo $stats['semester']; ?>/<?php echo $stats['academic_year']; ?></option>
                        <option value="custom">กำหนดเอง</option>
                    </select>
                </div>
                
                <div class="filter-item date-range" style="display: none;">
                    <label for="startDate">วันที่เริ่มต้น</label>
                    <input type="date" id="startDate" class="form-control">
                </div>
                
                <div class="filter-item date-range" style="display: none;">
                    <label for="endDate">วันที่สิ้นสุด</label>
                    <input type="date" id="endDate" class="form-control">
                </div>
                
                <div class="filter-item" id="classLevelFilter">
                    <label for="classLevel">ระดับชั้น</label>
                    <select id="classLevel" class="form-control">
                        <option value="">ทุกระดับชั้น</option>
                        <?php foreach ($classLevels as $level): ?>
                        <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item" id="classRoomFilter">
                    <label for="classRoom">ห้องเรียน</label>
                    <select id="classRoom" class="form-control">
                        <option value="">ทุกห้อง</option>
                        <!-- กลุ่มห้องจะถูกเติมโดย JavaScript -->
                    </select>
                </div>
                
                <div class="filter-item student-filter" style="display: none;">
                    <label for="studentSearch">รหัส/ชื่อนักเรียน</label>
                    <input type="text" id="studentSearch" class="form-control" placeholder="พิมพ์รหัสหรือชื่อนักเรียน">
                </div>
            </div>
            
            <div class="filter-actions">
                <button id="generateReport" class="btn btn-primary">
                    <i class="material-icons">search</i> สร้างรายงาน
                </button>
                <button id="resetFilters" class="btn btn-outline">
                    <i class="material-icons">refresh</i> รีเซ็ตตัวกรอง
                </button>
            </div>
        </div>
    </div>

    <!-- สรุปข้อมูลรายงาน -->
    <div class="report-card overview-card">
        <div class="report-header">
            <h2><i class="material-icons">assessment</i> สรุปภาพรวมการเข้าแถว</h2>
            <div class="report-subtitle">
                ภาคเรียนที่ <?php echo $stats['semester']; ?>/<?php echo $stats['academic_year']; ?> (วันที่เก็บข้อมูล: <?php echo date('d/m/Y'); ?>)
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="material-icons">groups</i>
                </div>
                <div class="stat-info">
                    <div class="stat-title">นักเรียนทั้งหมด</div>
                    <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                    <div class="stat-description">คน</div>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="material-icons">calendar_today</i>
                </div>
                <div class="stat-info">
                    <div class="stat-title">วันที่เข้าแถว</div>
                    <div class="stat-value"><?php echo $stats['attendance_days']; ?></div>
                    <div class="stat-description">จากทั้งหมด <?php echo $stats['required_days']; ?> วัน</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="material-icons">insights</i>
                </div>
                <div class="stat-info">
                    <div class="stat-title">อัตราการเข้าแถวเฉลี่ย</div>
                    <div class="stat-value"><?php echo number_format($stats['avg_attendance_rate'], 1); ?>%</div>
                    <div class="stat-description">
                        <?php if ($stats['rate_change'] >= 0): ?>
                            <span class="text-success">
                                <i class="material-icons">trending_up</i> 
                                เพิ่มขึ้น <?php echo number_format(abs($stats['rate_change']), 1); ?>%
                            </span>
                        <?php else: ?>
                            <span class="text-danger">
                                <i class="material-icons">trending_down</i> 
                                ลดลง <?php echo number_format(abs($stats['rate_change']), 1); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="material-icons">warning</i>
                </div>
                <div class="stat-info">
                    <div class="stat-title">นักเรียนเสี่ยงตกกิจกรรม</div>
                    <div class="stat-value"><?php echo $stats['risk_students']; ?></div>
                    <div class="stat-description">
                        คิดเป็น <?php echo $stats['total_students'] > 0 ? number_format(($stats['risk_students'] / $stats['total_students']) * 100, 1) : 0; ?>% ของนักเรียนทั้งหมด
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- รายงานแนวโน้มการเข้าแถว -->
    <div class="reports-grid">
        <div class="report-card chart-card trend-chart">
            <div class="report-header">
                <h2><i class="material-icons">show_chart</i> แนวโน้มการเข้าแถวตลอดภาคเรียน</h2>
                <div class="card-actions">
                    <button class="action-button" id="refreshYearlyChart">
                        <i class="material-icons">refresh</i>
                    </button>
                    <button class="action-button" id="downloadYearlyChart">
                        <i class="material-icons">download</i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="yearlyAttendanceChart"></canvas>
            </div>
        </div>
        
        <div class="report-card chart-card daily-chart">
            <div class="report-header">
                <h2><i class="material-icons">bar_chart</i> อัตราการเข้าแถว 7 วันล่าสุด</h2>
                <div class="card-actions">
                    <button class="action-button" id="refreshDailyChart">
                        <i class="material-icons">refresh</i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="dailyAttendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- สาเหตุการขาดแถวและอัตราการเข้าแถวตามแผนก -->
    <div class="reports-grid">
        <div class="report-card chart-card reasons-chart">
            <div class="report-header">
                <h2><i class="material-icons">pie_chart</i> สาเหตุการขาดแถว</h2>
                <div class="card-actions">
                    <button class="action-button" id="refreshReasonsChart">
                        <i class="material-icons">refresh</i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="absenceReasonsChart"></canvas>
            </div>
        </div>
        
        <div class="report-card chart-card department-chart">
            <div class="report-header">
                <h2><i class="material-icons">business</i> อัตราการเข้าแถวตามแผนกวิชา</h2>
                <div class="card-actions">
                    <button class="action-button" id="refreshDepartmentChart">
                        <i class="material-icons">refresh</i>
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="departmentAttendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- รายชื่อนักเรียนที่เสี่ยงตกกิจกรรม -->
    <div class="report-card students-card">
        <div class="report-header">
            <h2><i class="material-icons">warning</i> รายชื่อนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว</h2>
            <div class="card-actions">
                <button class="action-button" id="showAllRiskStudents">
                    <i class="material-icons">visibility</i> ดูทั้งหมด
                </button>
                <button class="action-button" id="sendNotificationsToAll">
                    <i class="material-icons">notifications_active</i> แจ้งเตือนทั้งหมด
                </button>
            </div>
        </div>
        
        <div class="responsive-table">
            <table class="data-table" id="riskStudentsTable">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>นักเรียน</th>
                        <th>ชั้น/ห้อง</th>
                        <th class="text-center">อัตราการเข้าแถว</th>
                        <th class="text-center">วันที่เข้าแถว/ขาด</th>
                        <th>ครูที่ปรึกษา</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riskStudents)): ?>
                    <tr>
                        <td colspan="8" class="text-center">ไม่พบข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($riskStudents as $index => $student): ?>
                            <?php 
                                $attendanceRate = $student['attendance_rate'];
                                $statusClass = $attendanceRate < 70 ? 'danger' : 'warning';
                                $statusText = $attendanceRate < 70 ? 'ตกกิจกรรม' : 'เสี่ยงตกกิจกรรม';
                                $studentInitial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
                                $totalDays = $student['total_attendance_days'] + $student['total_absence_days'];
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"><?php echo $studentInitial; ?></div>
                                        <div class="student-details">
                                            <div class="student-name"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                            <div class="student-code">รหัส <?php echo $student['student_code']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['class_name']; ?></td>
                                <td class="text-center">
                                    <span class="attendance-rate <?php echo $statusClass; ?>">
                                        <?php echo number_format($attendanceRate, 1); ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php echo $student['total_attendance_days']; ?>/<?php echo $totalDays; ?> วัน
                                </td>
                                <td><?php echo $student['advisor_name']; ?></td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="table-action-btn view-btn" data-student-id="<?php echo $student['student_id']; ?>" title="ดูข้อมูลละเอียด">
                                            <i class="material-icons">visibility</i>
                                        </button>
                                        <button class="table-action-btn notify-btn" data-student-id="<?php echo $student['student_id']; ?>" title="ส่งการแจ้งเตือน">
                                            <i class="material-icons">notifications</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ตารางอัตราการเข้าแถวตามชั้นเรียน -->
    <div class="report-card classes-card">
        <div class="report-header">
            <h2><i class="material-icons">school</i> อัตราการเข้าแถวตามชั้นเรียน</h2>
            <div class="card-actions">
                <button class="action-button" id="viewAllClasses">
                    <i class="material-icons">list</i> ดูทุกชั้นเรียน
                </button>
                <div class="search-container">
                    <i class="material-icons">search</i>
                    <input type="text" id="classSearch" placeholder="ค้นหาชั้นเรียน...">
                </div>
            </div>
        </div>
        
        <div class="responsive-table">
            <table class="data-table" id="classAttendanceTable">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>ชั้นเรียน</th>
                        <th>แผนกวิชา</th>
                        <th class="text-center">จำนวนนักเรียน</th>
                        <th class="text-center">อัตราการเข้าแถว</th>
                        <th class="text-center">ความคืบหน้า</th>
                        <th class="text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classRates)): ?>
                    <tr>
                        <td colspan="7" class="text-center">ไม่พบข้อมูลชั้นเรียน</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($classRates as $index => $class): ?>
                            <?php 
                                $rate = $class['attendance_rate'];
                                $progressClass = '';
                                
                                if ($rate >= 90) {
                                    $progressClass = 'success';
                                } elseif ($rate >= 80) {
                                    $progressClass = 'info';
                                } elseif ($rate >= 70) {
                                    $progressClass = 'warning';
                                } else {
                                    $progressClass = 'danger';
                                }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td><?php echo $class['class_name']; ?></td>
                                <td><?php echo $class['department_name']; ?></td>
                                <td class="text-center"><?php echo $class['student_count']; ?> คน</td>
                                <td class="text-center">
                                    <span class="attendance-rate <?php echo $progressClass; ?>">
                                        <?php echo number_format($rate, 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo min(100, $rate); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="table-action-btn view-class-btn" data-class-id="<?php echo $class['class_id']; ?>" title="ดูรายละเอียด">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal รายละเอียดนักเรียน -->
<div class="modal" id="studentDetailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>รายละเอียดการเข้าแถว - <span id="studentName"></span></h3>
            <button class="modal-close" id="closeStudentModal">
                <i class="material-icons">close</i>
            </button>
        </div>
        <div class="modal-body">
            <div class="student-profile">
                <div class="student-profile-header">
                    <div class="student-profile-avatar" id="studentInitial"></div>
                    <div class="student-profile-info">
                        <h4 id="studentFullName"></h4>
                        <p>รหัสนักเรียน: <span id="studentCode"></span></p>
                        <p>ชั้น <span id="studentClass"></span></p>
                        <p>อัตราการเข้าแถว: <span id="studentRate" class="attendance-rate"></span></p>
                    </div>
                </div>
                
                <div class="attendance-summary">
                    <h4>สรุปการเข้าแถว</h4>
                    <div class="summary-flex">
                        <div class="summary-item">
                            <div class="summary-value" id="presentDays"></div>
                            <div class="summary-label">วันที่เข้าแถว</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="absentDays"></div>
                            <div class="summary-label">วันที่ขาดแถว</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="totalDays"></div>
                            <div class="summary-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
                
                <div class="attendance-history">
                    <h4>ประวัติการเข้าแถวล่าสุด</h4>
                    <div class="responsive-table">
                        <table class="data-table history-table">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">เวลา</th>
                                    <th>หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceHistory">
                                <!-- จะถูกเติมด้วย JavaScript -->
                                <tr>
                                    <td colspan="4" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="attendance-trend">
                    <h4>แนวโน้มการเข้าแถวรายเดือน</h4>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="studentMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="closeStudentDetailBtn">ปิด</button>
            <button class="btn btn-primary" id="sendNotificationBtn">
                <i class="material-icons">notifications</i> ส่งการแจ้งเตือน
            </button>
            <button class="btn btn-primary" id="printStudentReportBtn">
                <i class="material-icons">print</i> พิมพ์รายงาน
            </button>
        </div>
    </div>
</div>

<!-- Modal รายละเอียดชั้นเรียน -->
<div class="modal" id="classDetailModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>รายละเอียดการเข้าแถว - ชั้น <span id="className"></span></h3>
            <button class="modal-close" id="closeClassModal">
                <i class="material-icons">close</i>
            </button>
        </div>
        <div class="modal-body">
            <div class="class-profile">
                <div class="class-profile-header">
                    <div class="class-profile-icon">
                        <i class="material-icons">school</i>
                    </div>
                    <div class="class-profile-info">
                        <h4 id="classFullName"></h4>
                        <p>แผนกวิชา: <span id="classDepartment"></span></p>
                        <p>จำนวนนักเรียน: <span id="classStudentCount"></span> คน</p>
                        <p>อัตราการเข้าแถว: <span id="classRate" class="attendance-rate"></span></p>
                    </div>
                </div>
                
                <div class="class-attendance-summary">
                    <h4>สรุปการเข้าแถว</h4>
                    <div class="summary-flex">
                        <div class="summary-item">
                            <div class="summary-value" id="classPresentCount"></div>
                            <div class="summary-label">วันที่เข้าแถว</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="classAbsentCount"></div>
                            <div class="summary-label">นักเรียนที่ขาดแถว</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value" id="classRiskCount"></div>
                            <div class="summary-label">นักเรียนเสี่ยงตก</div>
                        </div>
                    </div>
                </div>
                
                <div class="class-students-list">
                    <h4>รายชื่อนักเรียนในชั้นเรียน</h4>
                    <div class="responsive-table">
                        <table class="data-table class-students-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>รหัส</th>
                                    <th>ชื่อ-สกุล</th>
                                    <th class="text-center">อัตราการเข้าแถว</th>
                                    <th class="text-center">วันที่เข้าแถว/ขาด</th>
                                    <th class="text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="classStudentsList">
                                <!-- จะถูกเติมด้วย JavaScript -->
                                <tr>
                                    <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="class-advisors">
                    <h4>ครูที่ปรึกษา</h4>
                    <div id="classAdvisorsList" class="advisors-list">
                        <!-- จะถูกเติมด้วย JavaScript -->
                        <div class="text-center">กำลังโหลดข้อมูล...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="closeClassDetailBtn">ปิด</button>
            <button class="btn btn-primary" id="sendClassNotificationBtn">
                <i class="material-icons">notifications</i> ส่งการแจ้งเตือน
            </button>
            <button class="btn btn-primary" id="printClassReportBtn">
                <i class="material-icons">print</i> พิมพ์รายงาน
            </button>
        </div>
    </div>
</div>

<!-- Modal ส่งการแจ้งเตือน -->
<div class="modal" id="sendNotificationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>ส่งการแจ้งเตือนไปยังผู้ปกครอง</h3>
            <button class="modal-close" id="closeNotificationModal">
                <i class="material-icons">close</i>
            </button>
        </div>
        <div class="modal-body">
            <div class="notification-form">
                <div class="form-group">
                    <label for="notificationTarget">ส่งถึง</label>
                    <input type="text" id="notificationTarget" class="form-control" readonly>
                    <input type="hidden" id="notificationStudentId">
                    <input type="hidden" id="notificationClassId">
                    <input type="hidden" id="notificationType" value="individual">
                </div>
                
                <div class="form-group">
                    <label for="notificationTemplate">เลือกเทมเพลต</label>
                    <select id="notificationTemplate" class="form-control">
                        <option value="">เลือกเทมเพลตข้อความ</option>
                        <option value="1">แจ้งเตือนความเสี่ยงรายบุคคล</option>
                        <option value="2">นัดประชุมผู้ปกครองกลุ่มเสี่ยง</option>
                        <option value="3">แจ้งเตือนฉุกเฉิน</option>
                        <option value="4">รายงานสรุปประจำเดือน</option>
                        <option value="5">แจ้งข่าวกิจกรรมวิทยาลัย</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notificationMessage">ข้อความ</label>
                    <textarea id="notificationMessage" class="form-control" rows="10" placeholder="พิมพ์ข้อความที่ต้องการส่ง..."></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-container">
                        <input type="checkbox" id="sendImmediate" checked>
                        <label for="sendImmediate">ส่งทันที</label>
                    </div>
                </div>
                
                <div class="scheduled-options" style="display: none;">
                    <div class="form-group">
                        <label for="scheduledDate">วันที่ส่ง</label>
                        <input type="date" id="scheduledDate" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="scheduledTime">เวลาส่ง</label>
                        <input type="time" id="scheduledTime" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelNotificationBtn">ยกเลิก</button>
            <button class="btn btn-primary" id="confirmSendNotificationBtn">
                <i class="material-icons">send</i> ส่งการแจ้งเตือน
            </button>
        </div>
    </div>
</div>

<script>
// เริ่มต้น JavaScript สำหรับการแสดงผลกราฟและการจัดการข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    // จะถูกแทนที่ด้วยไฟล์ reports.js
    
    // สร้างกราฟแนวโน้มรายเดือน
    const yearlyCtx = document.getElementById('yearlyAttendanceChart').getContext('2d');
    const yearlyData = reportData.yearlyTrends;
    
    new Chart(yearlyCtx, {
        type: 'line',
        data: {
            labels: yearlyData.map(item => item.month),
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: yearlyData.map(item => item.rate),
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                borderColor: 'rgba(25, 118, 210, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 60,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    
    // สร้างกราฟวงกลมสาเหตุการขาด
    const reasonsCtx = document.getElementById('absenceReasonsChart').getContext('2d');
    const reasonsData = reportData.absenceReasons;
    
    new Chart(reasonsCtx, {
        type: 'doughnut',
        data: {
            labels: reasonsData.map(item => item.reason),
            datasets: [{
                data: reasonsData.map(item => item.percent),
                backgroundColor: [
                    'rgba(244, 67, 54, 0.8)',  // สีแดง (ขาด)
                    'rgba(255, 152, 0, 0.8)',  // สีส้ม (มาสาย)
                    'rgba(76, 175, 80, 0.8)'   // สีเขียว (ลา)
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed.toFixed(1)}%`;
                        }
                    }
                }
            }
        }
    });
    
    // สร้างกราฟอัตราการเข้าแถวตามแผนก
    const deptCtx = document.getElementById('departmentAttendanceChart').getContext('2d');
    const deptData = reportData.departmentStats;
    
    new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: deptData.map(item => item.department_name),
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: deptData.map(item => item.attendance_rate),
                backgroundColor: deptData.map(item => {
                    const rate = item.attendance_rate;
                    if (rate >= 90) return 'rgba(76, 175, 80, 0.8)';  // สีเขียว
                    if (rate >= 80) return 'rgba(33, 150, 243, 0.8)';  // สีฟ้า
                    if (rate >= 70) return 'rgba(255, 152, 0, 0.8)';  // สีส้ม
                    return 'rgba(244, 67, 54, 0.8)';  // สีแดง
                }),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const dept = deptData[dataIndex];
                            return [
                                `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`,
                                `จำนวนนักเรียน: ${dept.student_count} คน`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 60,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    
    // สร้างกราฟอัตราการเข้าแถว 7 วันล่าสุด
    const dailyCtx = document.getElementById('dailyAttendanceChart').getContext('2d');
    const dailyData = reportData.dailyStats;
    
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: dailyData.map(item => item.short_date),
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: dailyData.map(item => item.attendance_rate),
                backgroundColor: dailyData.map(item => {
                    const rate = item.attendance_rate;
                    if (rate >= 90) return 'rgba(76, 175, 80, 0.8)';  // สีเขียว
                    if (rate >= 80) return 'rgba(33, 150, 243, 0.8)';  // สีฟ้า
                    if (rate >= 70) return 'rgba(255, 152, 0, 0.8)';  // สีส้ม
                    return 'rgba(244, 67, 54, 0.8)';  // สีแดง
                }),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const dataIndex = context[0].dataIndex;
                            return dailyData[dataIndex].day_name + ' ' + dailyData[dataIndex].formatted_date;
                        },
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const day = dailyData[dataIndex];
                            return [
                                `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`,
                                `จำนวนนักเรียนที่มา: ${day.present_count} คน`,
                                `จำนวนนักเรียนทั้งหมด: ${day.total_students} คน`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 60,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    
    // แสดง/ซ่อนตัวกรองเพิ่มเติม
    document.querySelector('.toggle-filters').addEventListener('click', function() {
        const filterBody = document.querySelector('.filter-body');
        filterBody.classList.toggle('show');
        
        const icon = this.querySelector('.material-icons');
        if (filterBody.classList.contains('show')) {
            icon.textContent = 'expand_less';
        } else {
            icon.textContent = 'expand_more';
        }
    });
    
    // แสดง/ซ่อนช่วงวันที่
    document.getElementById('period').addEventListener('change', function() {
        const dateRangeFields = document.querySelectorAll('.date-range');
        if (this.value === 'custom') {
            dateRangeFields.forEach(field => field.style.display = 'block');
        } else {
            dateRangeFields.forEach(field => field.style.display = 'none');
        }
    });
    
    // แสดง/ซ่อนฟิลด์ค้นหานักเรียน
    document.getElementById('reportType').addEventListener('change', function() {
        const studentFilter = document.querySelector('.student-filter');
        if (this.value === 'student') {
            studentFilter.style.display = 'block';
        } else {
            studentFilter.style.display = 'none';
        }
    });
    
    // กำหนดค่าเริ่มต้นสำหรับวันที่
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('startDate').valueAsDate = startOfMonth;
    document.getElementById('endDate').valueAsDate = today;
    
    // เปิด Modal รายละเอียดนักเรียน
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            openStudentDetail(studentId);
        });
    });
    
    // เปิด Modal รายละเอียดชั้นเรียน
    document.querySelectorAll('.view-class-btn').forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.getAttribute('data-class-id');
            openClassDetail(classId);
        });
    });
    
    // เปิด Modal ส่งการแจ้งเตือน
    document.querySelectorAll('.notify-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            openSendNotification('student', studentId);
        });
    });
    
    // เปิด/ปิด Modal
    function openStudentDetail(studentId) {
        // ในระบบจริง จะต้องดึงข้อมูลนักเรียนจาก API
        // สำหรับตัวอย่าง เราจะใช้ข้อมูลจาก reportData
        const student = reportData.riskStudents.find(s => s.student_id == studentId);
        
        if (student) {
            // เติมข้อมูลลงใน Modal
            document.getElementById('studentName').textContent = student.first_name;
            document.getElementById('studentInitial').textContent = student.first_name.charAt(0);
            document.getElementById('studentFullName').textContent = `${student.title} ${student.first_name} ${student.last_name}`;
            document.getElementById('studentCode').textContent = student.student_code;
            document.getElementById('studentClass').textContent = student.class_name;
            
            const rateElement = document.getElementById('studentRate');
            rateElement.textContent = `${student.attendance_rate.toFixed(1)}%`;
            
            if (student.attendance_rate < 70) {
                rateElement.className = 'attendance-rate danger';
            } else if (student.attendance_rate < 80) {
                rateElement.className = 'attendance-rate warning';
            } else {
                rateElement.className = 'attendance-rate success';
            }
            
            document.getElementById('presentDays').textContent = student.total_attendance_days;
            document.getElementById('absentDays').textContent = student.total_absence_days;
            document.getElementById('totalDays').textContent = parseInt(student.total_attendance_days) + parseInt(student.total_absence_days);
            
            // เปิด Modal
            document.getElementById('studentDetailModal').classList.add('show');
        }
    }
    
    function openClassDetail(classId) {
        // ในระบบจริง จะต้องดึงข้อมูลชั้นเรียนจาก API
        // สำหรับตัวอย่าง เราจะใช้ข้อมูลจาก reportData
        const classInfo = reportData.classRates.find(c => c.class_id == classId);
        
        if (classInfo) {
            // เติมข้อมูลลงใน Modal
            document.getElementById('className').textContent = classInfo.class_name;
            document.getElementById('classFullName').textContent = `ชั้น ${classInfo.class_name}`;
            document.getElementById('classDepartment').textContent = classInfo.department_name;
            document.getElementById('classStudentCount').textContent = classInfo.student_count;
            
            const rateElement = document.getElementById('classRate');
            rateElement.textContent = `${classInfo.attendance_rate.toFixed(1)}%`;
            
            if (classInfo.attendance_rate < 70) {
                rateElement.className = 'attendance-rate danger';
            } else if (classInfo.attendance_rate < 80) {
                rateElement.className = 'attendance-rate warning';
            } else if (classInfo.attendance_rate < 90) {
                rateElement.className = 'attendance-rate info';
            } else {
                rateElement.className = 'attendance-rate success';
            }
            
            // เปิด Modal
            document.getElementById('classDetailModal').classList.add('show');
        }
    }
    
    function openSendNotification(type, id) {
        // กำหนดค่าเริ่มต้นสำหรับฟอร์มการแจ้งเตือน
        document.getElementById('notificationType').value = type;
        
        if (type === 'student') {
            document.getElementById('notificationStudentId').value = id;
            document.getElementById('notificationClassId').value = '';
            
            // หาข้อมูลนักเรียน
            const student = reportData.riskStudents.find(s => s.student_id == id);
            if (student) {
                document.getElementById('notificationTarget').value = `${student.title} ${student.first_name} ${student.last_name} (${student.class_name})`;
            }
        } else if (type === 'class') {
            document.getElementById('notificationStudentId').value = '';
            document.getElementById('notificationClassId').value = id;
            
            // หาข้อมูลชั้นเรียน
            const classInfo = reportData.classRates.find(c => c.class_id == id);
            if (classInfo) {
                document.getElementById('notificationTarget').value = `ชั้น ${classInfo.class_name} (${classInfo.student_count} คน)`;
            }
        }
        
        // เปิด Modal
        document.getElementById('sendNotificationModal').classList.add('show');
    }
    
    // ปิด Modal
    document.querySelectorAll('.modal-close, #closeStudentDetailBtn, #closeClassDetailBtn, #cancelNotificationBtn').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('show');
        });
    });
    
    // ส่งการแจ้งเตือน
    document.getElementById('sendNotificationBtn').addEventListener('click', function() {
        const studentId = document.querySelector('#studentDetailModal').getAttribute('data-student-id');
        openSendNotification('student', studentId);
    });
    
    document.getElementById('sendClassNotificationBtn').addEventListener('click', function() {
        const classId = document.querySelector('#classDetailModal').getAttribute('data-class-id');
        openSendNotification('class', classId);
    });
    
    // ตัวเลือกการส่งทันที/กำหนดเวลา
    document.getElementById('sendImmediate').addEventListener('change', function() {
        const scheduledOptions = document.querySelector('.scheduled-options');
        scheduledOptions.style.display = this.checked ? 'none' : 'block';
    });
    
    // เลือกเทมเพลตข้อความ
    document.getElementById('notificationTemplate').addEventListener('change', function() {
        // ในระบบจริง จะต้องดึงข้อมูลเทมเพลตจาก API
        // สำหรับตัวอย่าง เราจะใช้ข้อความตัวอย่าง
        const templateId = this.value;
        let templateText = '';
        
        switch (templateId) {
            case '1':
                templateText = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
                break;
            case '2':
                templateText = 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
                break;
            case '3':
                templateText = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
                break;
            case '4':
                templateText = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน{{เดือน}} {{ปี}}\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
                break;
            case '5':
                templateText = 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งข้อมูลข่าวสารเกี่ยวกับกิจกรรม{{ชื่อกิจกรรม}} ซึ่งจะจัดขึ้นในวันที่ {{วันที่}} เวลา {{เวลา}} ณ {{สถานที่}}\n\nนักเรียนจะต้อง{{รายละเอียด}}\n\nหากมีข้อสงสัยกรุณาติดต่อ {{ผู้รับผิดชอบ}} ที่เบอร์โทร {{เบอร์โทร}}\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
                break;
            default:
                templateText = '';
                break;
        }
        
        document.getElementById('notificationMessage').value = templateText;
    });
    
    // ยืนยันการส่งการแจ้งเตือน
    document.getElementById('confirmSendNotificationBtn').addEventListener('click', function() {
        // ในระบบจริง จะต้องส่งข้อมูลไปยัง API
        alert('ส่งการแจ้งเตือนเรียบร้อยแล้ว');
        document.getElementById('sendNotificationModal').classList.remove('show');
    });
    
    // สร้างรายงาน
    document.getElementById('generateReport').addEventListener('click', function() {
        // ในระบบจริง จะต้องส่งข้อมูลไปยัง API เพื่อสร้างรายงาน
        alert('กำลังสร้างรายงาน...');
    });
    
    // รีเซ็ตตัวกรอง
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('reportType').value = 'overview';
        document.getElementById('department').value = '';
        document.getElementById('period').value = 'current';
        document.getElementById('classLevel').value = '';
        document.getElementById('classRoom').value = '';
        document.getElementById('studentSearch').value = '';
        
      // ซ่อนฟิลด์ค้นหานักเรียน
      document.querySelector('.student-filter').style.display = 'none';
    });
    
    // ค้นหาในตารางชั้นเรียน
    document.getElementById('classSearch').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('classAttendanceTable');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const classCell = row.querySelector('td:nth-child(2)');
            const deptCell = row.querySelector('td:nth-child(3)');
            
            if (classCell && deptCell) {
                const className = classCell.textContent.toLowerCase();
                const deptName = deptCell.textContent.toLowerCase();
                
                if (className.includes(searchTerm) || deptName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
    
    // ดูทั้งหมด
    document.getElementById('showAllRiskStudents').addEventListener('click', function() {
        window.location.href = 'risk_students.php';
    });
    
    document.getElementById('viewAllClasses').addEventListener('click', function() {
        window.location.href = 'classes.php';
    });
    
    // ส่งการแจ้งเตือนทั้งหมด
    document.getElementById('sendNotificationsToAll').addEventListener('click', function() {
        if (confirm('คุณต้องการส่งการแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดหรือไม่?')) {
            // ในระบบจริง จะต้องส่งข้อมูลไปยัง API
            alert('ส่งการแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดเรียบร้อยแล้ว');
        }
    });
    
    // พิมพ์รายงาน
    document.getElementById('printStudentReportBtn').addEventListener('click', function() {
        window.print();
    });
    
    document.getElementById('printClassReportBtn').addEventListener('click', function() {
        window.print();
    });
    
    // Initialization - แสดงตัวกรองเริ่มต้น
    document.querySelector('.filter-body').classList.add('show');
});
</script>
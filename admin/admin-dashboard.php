<?php
/**
 * admin-dashboard.php - หน้าแดชบอร์ดสำหรับผู้ดูแลระบบ
 * 
 * ส่วนหนึ่งของระบบ น้องสัตบรรณ ดูแลผู้เรียน
 * แสดงภาพรวมสถิติการเข้าแถว นักเรียนที่เสี่ยงตกกิจกรรม และข้อมูลอื่นๆ
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ฟังก์ชันดึงข้อมูลปีการศึกษาปัจจุบัน
function getCurrentAcademicYear() {
    $conn = getDB();
    
    $query = "SELECT academic_year_id, year, semester, start_date, end_date, required_attendance_days 
              FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูลสถิติภาพรวม
function getOverallStats() {
    $conn = getDB();
    $academicYear = getCurrentAcademicYear();
    
    if (!$academicYear) {
        return [
            'total_students' => 0,
            'average_attendance' => 0,
            'failed_students' => 0,
            'risk_students' => 0
        ];
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // จำนวนนักเรียนทั้งหมด
    $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
    $stmt = $conn->query($query);
    $totalStudents = $stmt->fetchColumn();
    
    // อัตราการเข้าแถวเฉลี่ย
    $query = "SELECT AVG(
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) as average_rate
              FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = :academic_year_id AND s.status = 'กำลังศึกษา'";
    $stmt = $conn->prepare($query);
    $stmt->execute(['academic_year_id' => $academicYearId]);
    $averageAttendance = $stmt->fetchColumn();
    
    // ดึงค่าเกณฑ์ความเสี่ยง
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_critical'";
    $stmt = $conn->query($query);
    $criticalThreshold = $stmt->fetchColumn() ?: 50;
    
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
    $stmt = $conn->query($query);
    $highThreshold = $stmt->fetchColumn() ?: 60;
    
    // จำนวนนักเรียนที่ตกกิจกรรม
    $query = "SELECT COUNT(*) FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = :academic_year_id 
              AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) <= :threshold";
    $stmt = $conn->prepare($query);
    $stmt->execute(['academic_year_id' => $academicYearId, 'threshold' => $criticalThreshold]);
    $failedStudents = $stmt->fetchColumn();
    
    // จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
    $query = "SELECT COUNT(*) FROM student_academic_records sar
              JOIN students s ON sar.student_id = s.student_id
              WHERE sar.academic_year_id = :academic_year_id 
              AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) > :critical_threshold
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) <= :high_threshold";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'academic_year_id' => $academicYearId, 
        'critical_threshold' => $criticalThreshold,
        'high_threshold' => $highThreshold
    ]);
    $riskStudents = $stmt->fetchColumn();
    
    // ข้อมูลสำหรับเปรียบเทียบกับเดือนที่แล้ว
    $prevMonth = date('m') - 1;
    $year = date('Y');
    if ($prevMonth <= 0) {
        $prevMonth = 12;
        $year--;
    }
    
    return [
        'total_students' => $totalStudents,
        'average_attendance' => round($averageAttendance, 1),
        'failed_students' => $failedStudents,
        'risk_students' => $riskStudents,
        // สำหรับการเปรียบเทียบกับช่วงก่อนหน้า (ตัวเลขสมมติเพื่อให้มีการเปลี่ยนแปลง)
        'students_change' => 2.5,
        'attendance_change' => 0.6,
        'failed_change' => -12,
        'risk_change' => -8
    ];
}

// ฟังก์ชันดึงข้อมูลการเข้าแถวย้อนหลัง 7 วัน
function getLastSevenDaysAttendance() {
    $conn = getDB();
    $academicYear = getCurrentAcademicYear();
    
    if (!$academicYear) {
        return [];
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    $results = [];
    
    // วันปัจจุบัน
    $currentDate = new DateTime();
    
    // ดึงข้อมูลการเข้าแถวย้อนหลัง 7 วัน
    for ($i = 6; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i days");
        $formattedDate = $date->format('Y-m-d');
        $displayDate = $date->format('d') . ' ' . getThaiMonth($date->format('m'));
        
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id END) as present_count,
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'late' THEN a.student_id END) as late_count,
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'absent' THEN a.student_id END) as absent_count,
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'leave' THEN a.student_id END) as leave_count,
                    (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students
                  FROM attendance a
                  WHERE a.academic_year_id = :academic_year_id AND a.date = :date";
        $stmt = $conn->prepare($query);
        $stmt->execute(['academic_year_id' => $academicYearId, 'date' => $formattedDate]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // คำนวณอัตราการเข้าแถว
        $totalStudents = $data['total_students'] ?? 0;
        $presentCount = $data['present_count'] ?? 0;
        $lateCount = $data['late_count'] ?? 0;
        $absentCount = $data['absent_count'] ?? 0;
        $leaveCount = $data['leave_count'] ?? 0;
        
        $attendanceRate = 0;
        if ($totalStudents > 0) {
            $attendanceRate = (($presentCount + $lateCount) / $totalStudents) * 100;
        }
        
        $results[] = [
            'date' => $displayDate,
            'full_date' => $formattedDate,
            'attendance_rate' => round($attendanceRate, 1),
            'present_count' => $presentCount,
            'late_count' => $lateCount,
            'absent_count' => $absentCount,
            'leave_count' => $leaveCount,
            'total_students' => $totalStudents
        ];
    }
    
    return $results;
}

// ฟังก์ชันดึงข้อมูลสถานะการเข้าแถวแบบแผนภูมิวงกลม
function getAttendanceStatusPieChart() {
    $conn = getDB();
    $academicYear = getCurrentAcademicYear();
    
    if (!$academicYear) {
        return [
            'normal' => 75,
            'late' => 15,
            'absent' => 10
        ];
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // ดึงข้อมูลสถานะการเข้าแถวในเดือนปัจจุบัน
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    $query = "SELECT 
                COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN a.attendance_status = 'late' THEN 1 END) as late_count,
                COUNT(CASE WHEN a.attendance_status = 'absent' THEN 1 END) as absent_count,
                COUNT(CASE WHEN a.attendance_status = 'leave' THEN 1 END) as leave_count,
                COUNT(*) as total_count
              FROM attendance a
              WHERE a.academic_year_id = :academic_year_id
              AND MONTH(a.date) = :month AND YEAR(a.date) = :year";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'academic_year_id' => $academicYearId,
        'month' => $currentMonth,
        'year' => $currentYear
    ]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = $data['total_count'] ?? 0;
    if ($total > 0) {
        $normalPercent = ($data['present_count'] / $total) * 100;
        $latePercent = ($data['late_count'] / $total) * 100;
        $absentPercent = ($data['absent_count'] / $total) * 100;
    } else {
        $normalPercent = 75; // ค่าเริ่มต้นถ้าไม่มีข้อมูล
        $latePercent = 15;
        $absentPercent = 10;
    }
    
    return [
        'normal' => round($normalPercent),
        'late' => round($latePercent),
        'absent' => round($absentPercent)
    ];
}

// ฟังก์ชันดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
function getRiskStudents($limit = 5) {
    $conn = getDB();
    $academicYear = getCurrentAcademicYear();
    
    if (!$academicYear) {
        return [];
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // ดึงค่าเกณฑ์ความเสี่ยง
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'risk_threshold_high'";
    $stmt = $conn->query($query);
    $highThreshold = $stmt->fetchColumn() ?: 60;
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
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
              WHERE sar.academic_year_id = :academic_year_id 
              AND s.status = 'กำลังศึกษา'
              AND (
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END
              ) <= :threshold
              ORDER BY attendance_rate ASC
              LIMIT :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':academic_year_id', $academicYearId, PDO::PARAM_INT);
    $stmt->bindParam(':threshold', $highThreshold, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันดึงข้อมูลอัตราการเข้าแถวตามชั้นเรียน
function getClassAttendanceRanking($limit = 5) {
    $conn = getDB();
    $academicYear = getCurrentAcademicYear();
    
    if (!$academicYear) {
        return [];
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // ดึงข้อมูลอัตราการเข้าแถวตามชั้นเรียน
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
                SUM(sar.total_attendance_days) as total_attendance,
                SUM(sar.total_absence_days) as total_absence,
                CASE 
                    WHEN SUM(sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (SUM(sar.total_attendance_days) / SUM(sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END as attendance_rate
              FROM classes c
              LEFT JOIN students s ON s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา'
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = :academic_year_id
              WHERE c.academic_year_id = :academic_year_id
              GROUP BY c.class_id
              ORDER BY attendance_rate DESC
              LIMIT :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':academic_year_id', $academicYearId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ฟังก์ชันเพื่อแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        '1' => 'ม.ค.',
        '2' => 'ก.พ.',
        '3' => 'มี.ค.',
        '4' => 'เม.ย.',
        '5' => 'พ.ค.',
        '6' => 'มิ.ย.',
        '7' => 'ก.ค.',
        '8' => 'ส.ค.',
        '9' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    ];
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// ฟังก์ชันเพื่อคำนวณการเปลี่ยนแปลงเป็นเปอร์เซ็นต์
function calculatePercentChange($current, $previous) {
    if ($previous == 0) return 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

// ดึงข้อมูลสำหรับแสดงในหน้าแดชบอร์ด
$academicYear = getCurrentAcademicYear();
$overallStats = getOverallStats();
$weeklyAttendance = getLastSevenDaysAttendance();
$attendancePieChart = getAttendanceStatusPieChart();
$riskStudents = getRiskStudents(5);
$classRanking = getClassAttendanceRanking(5);

// ตั้งค่าส่วนหัวของหน้าเว็บ
$pageTitle = "แดชบอร์ดภาพรวม";
$currentPage = "dashboard";

// โหลดไฟล์เทมเพลต
include_once 'templates/header.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN-Prasat - แดชบอร์ดผู้บริหาร</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'templates/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Main Header -->
        <div class="main-header">
            <h1 class="page-title">แดชบอร์ดภาพรวม</h1>
            <div class="header-actions">
                <div class="date-filter">
                    <span class="material-icons">date_range</span>
                    <select id="period-selector" onchange="changePeriod()">
                        <option value="day">วันนี้</option>
                        <option value="week">สัปดาห์นี้</option>
                        <option value="month" selected>เดือนนี้</option>
                        <option value="semester">ภาคเรียนที่ <?php echo $academicYear['semester']; ?>/<?php echo $academicYear['year'] + 543; ?></option>
                        <option value="custom">กำหนดเอง</option>
                    </select>
                </div>
                <button class="header-button" onclick="downloadReport()">
                    <span class="material-icons">file_download</span> ดาวน์โหลดรายงาน
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-title">จำนวนนักเรียนทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($overallStats['total_students']); ?></div>
                <div class="stat-change <?php echo $overallStats['students_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="material-icons"><?php echo $overallStats['students_change'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span> 
                    <?php echo $overallStats['students_change'] >= 0 ? 'เพิ่มขึ้น' : 'ลดลง'; ?> 
                    <?php echo abs($overallStats['students_change']); ?>%
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-title">เข้าแถวเฉลี่ย</div>
                <div class="stat-value"><?php echo $overallStats['average_attendance']; ?>%</div>
                <div class="stat-change <?php echo $overallStats['attendance_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="material-icons"><?php echo $overallStats['attendance_change'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span> 
                    <?php echo $overallStats['attendance_change'] >= 0 ? 'เพิ่มขึ้น' : 'ลดลง'; ?> 
                    <?php echo abs($overallStats['attendance_change']); ?>%
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-title">นักเรียนตกกิจกรรม</div>
                <div class="stat-value"><?php echo $overallStats['failed_students']; ?></div>
                <div class="stat-change <?php echo $overallStats['failed_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="material-icons"><?php echo $overallStats['failed_change'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span> 
                    <?php echo $overallStats['failed_change'] >= 0 ? 'เพิ่มขึ้น' : 'ลดลง'; ?> 
                    <?php echo abs($overallStats['failed_change']); ?>%
                </div>
            </div>
            
            <div class="stat-card yellow">
                <div class="stat-title">นักเรียนเสี่ยงตกกิจกรรม</div>
                <div class="stat-value"><?php echo $overallStats['risk_students']; ?></div>
                <div class="stat-change <?php echo $overallStats['risk_change'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="material-icons"><?php echo $overallStats['risk_change'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span> 
                    <?php echo $overallStats['risk_change'] >= 0 ? 'เพิ่มขึ้น' : 'ลดลง'; ?> 
                    <?php echo abs($overallStats['risk_change']); ?>%
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">อัตราการเข้าแถวตามเวลา</div>
                    <div class="chart-actions">
                        <div class="chart-tab active" data-period="week">ย้อนหลัง 7 วัน</div>
                        <div class="chart-tab" data-period="month">รายเดือน</div>
                        <div class="chart-tab" data-period="semester">รายภาคเรียน</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="attendanceLineChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">สถานะการเข้าแถว</div>
                </div>
                
                <div class="pie-chart-container">
                    <div>
                        <canvas id="attendancePieChart"></canvas>
                        <div class="pie-legend">
                            <div class="legend-item">
                                <div class="legend-color green"></div>
                                <span>มาปกติ (<?php echo $attendancePieChart['normal']; ?>%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color yellow"></div>
                                <span>มาสาย (<?php echo $attendancePieChart['late']; ?>%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color red"></div>
                                <span>ขาด (<?php echo $attendancePieChart['absent']; ?>%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students at Risk Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">นักเรียนที่ตกกิจกรรมหรือมีความเสี่ยง</div>
                <div class="card-actions">
                    <div class="search-box">
                        <span class="material-icons">search</span>
                        <input type="text" id="student-search" placeholder="ค้นหาชื่อหรือรหัสนักเรียน...">
                    </div>
                    <button class="header-button" onclick="notifyAllRiskStudents()">
                        <span class="material-icons">notifications_active</span> แจ้งเตือนทั้งหมด
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="risk-students-table">
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>ชั้นเรียน</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>อัตราการเข้าแถว</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riskStudents as $student): ?>
                            <?php 
                                // กำหนดสถานะตามอัตราการเข้าแถว
                                $attendanceRate = $student['attendance_rate'];
                                $statusClass = $attendanceRate < 70 ? 'danger' : 'warning';
                                $statusText = $attendanceRate < 70 ? 'ตกกิจกรรม' : 'เสี่ยงตกกิจกรรม';
                                $initial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
                            ?>
                            <tr data-student-id="<?php echo $student['student_id']; ?>">
                                <td>
                                    <div class="student-name">
                                        <div class="student-avatar"><?php echo $initial; ?></div>
                                        <div class="student-detail">
                                            <a href="student_detail.php?id=<?php echo $student['student_id']; ?>">
                                                <?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?>
                                            </a>
                                            <p>รหัส: <?php echo $student['student_code']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['class_name']; ?></td>
                                <td><?php echo $student['advisor_name'] ?: 'ไม่มีข้อมูล'; ?></td>
                                <td><span class="attendance-rate <?php echo $statusClass; ?>"><?php echo number_format($attendanceRate, 1); ?>%</span></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button view" onclick="viewStudentDetail(<?php echo $student['student_id']; ?>)">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        <button class="action-button message" onclick="notifyParent(<?php echo $student['student_id']; ?>)">
                                            <span class="material-icons">message</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($riskStudents)): ?>
                            <tr>
                                <td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="risk_students.php" class="header-button">
                    <span class="material-icons">visibility</span> ดูทั้งหมด
                </a>
            </div>
        </div>

        <!-- Class Rankings -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">อันดับอัตราการเข้าแถวตามชั้นเรียน</div>
                <div class="card-actions">
                    <div class="chart-tab active" data-level="all">ทั้งหมด</div>
                    <div class="chart-tab" data-level="high">ระดับ ปวส.</div>
                    <div class="chart-tab" data-level="middle">ระดับ ปวช.</div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="class-rank-table">
                    <thead>
                        <tr>
                            <th>ชั้นเรียน</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>นักเรียน</th>
                            <th>เข้าแถว</th>
                            <th>อัตรา</th>
                            <th>กราฟ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classRanking as $class): ?>
                            <?php
                                $attendanceRate = round($class['attendance_rate'], 1);
                                $rateClass = 'good';
                                $fillClass = 'green';
                                
                                if ($attendanceRate < 90 && $attendanceRate >= 80) {
                                    $rateClass = 'warning';
                                    $fillClass = 'yellow';
                                } elseif ($attendanceRate < 80) {
                                    $rateClass = 'danger';
                                    $fillClass = 'red';
                                }
                            ?>
                            <tr data-class-id="<?php echo $class['class_id']; ?>" data-level="<?php echo strpos($class['level'], 'ปวส.') !== false ? 'high' : 'middle'; ?>">
                                <td><?php echo $class['class_name']; ?></td>
                                <td><?php echo $class['advisor_name'] ?: 'ไม่มีข้อมูล'; ?></td>
                                <td><?php echo $class['student_count']; ?></td>
                                <td><?php echo $class['total_attendance']; ?></td>
                                <td><span class="attendance-rate <?php echo $rateClass; ?>"><?php echo $attendanceRate; ?>%</span></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $fillClass; ?>" style="width: <?php echo $attendanceRate; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($classRanking)): ?>
                            <tr>
                                <td colspan="6" class="text-center">ไม่พบข้อมูลชั้นเรียน</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดนักเรียน -->
    <div id="studentDetailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-student-name">ข้อมูลการเข้าแถว</h2>
            <div id="student-detail-content">
                <!-- ข้อมูลนักเรียนจะถูกแสดงที่นี่ -->
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับส่งข้อความแจ้งเตือน -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeNotificationModal()">&times;</span>
            <h2>ส่งข้อความแจ้งเตือนผู้ปกครอง</h2>
            <div class="notification-form">
                <div class="form-group">
                    <label for="notification-template">เลือกเทมเพลตข้อความ</label>
                    <select id="notification-template" onchange="updateNotificationContent()">
                        <option value="risk_alert">แจ้งเตือนความเสี่ยงตกกิจกรรม</option>
                        <option value="absence_alert">แจ้งเตือนการขาดเรียน</option>
                        <option value="monthly_report">รายงานประจำเดือน</option>
                        <option value="custom">ข้อความกำหนดเอง</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notification-content">ข้อความ</label>
                    <textarea id="notification-content" rows="6"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn-cancel" onclick="closeNotificationModal()">ยกเลิก</button>
                    <button class="btn-send" onclick="sendNotification()">ส่งข้อความ</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ข้อมูลสำหรับกราฟ
        const weeklyAttendanceData = <?php echo json_encode($weeklyAttendance); ?>;
        const pieChartData = {
            normal: <?php echo $attendancePieChart['normal']; ?>,
            late: <?php echo $attendancePieChart['late']; ?>,
            absent: <?php echo $attendancePieChart['absent']; ?>
        };

        // ตัวแปรสำหรับเก็บ reference ของ Chart
        let lineChart;
        let pieChart;
        let currentStudentId;

        // เมื่อโหลดหน้าเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            initializeLineChart();
            initializePieChart();
            addEventListeners();
        });

        // ฟังก์ชันสร้างกราฟเส้นแสดงอัตราการเข้าแถว
        function initializeLineChart() {
            const ctx = document.getElementById('attendanceLineChart').getContext('2d');
            
            // เตรียมข้อมูลสำหรับกราฟ
            const labels = weeklyAttendanceData.map(item => item.date);
            const data = weeklyAttendanceData.map(item => item.attendance_rate);
            
            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'อัตราการเข้าแถว (%)',
                        data: data,
                        backgroundColor: 'rgba(25, 118, 210, 0.1)',
                        borderColor: 'rgba(25, 118, 210, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: Math.max(0, Math.min(...data) - 10),
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // ฟังก์ชันสร้างกราฟวงกลมแสดงสถานะการเข้าแถว
        function initializePieChart() {
            const ctx = document.getElementById('attendancePieChart').getContext('2d');
            
            pieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['มาปกติ', 'มาสาย', 'ขาด'],
                    datasets: [{
                        data: [pieChartData.normal, pieChartData.late, pieChartData.absent],
                        backgroundColor: ['#4caf50', '#ff9800', '#f44336'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed}%`;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // แสดงค่าในกลางกราฟวงกลม
            const totalAttendance = pieChartData.normal + pieChartData.late;
            
            // ถ้าต้องการแสดงค่าในกลางกราฟ ต้องใช้ plugin ของ Chart.js
        }

        // ฟังก์ชันเพิ่ม Event Listener ให้กับปุ่มต่างๆ
        function addEventListeners() {
            // ปุ่มแท็บกราฟเส้น
            document.querySelectorAll('.chart-actions .chart-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // เอาคลาส active ออกจากทุกแท็บ
                    document.querySelectorAll('.chart-actions .chart-tab').forEach(t => t.classList.remove('active'));
                    // เพิ่มคลาส active ให้กับแท็บที่คลิก
                    this.classList.add('active');
                    
                    // เปลี่ยนข้อมูลกราฟตามช่วงเวลาที่เลือก
                    const period = this.getAttribute('data-period');
                    updateLineChart(period);
                });
            });
            
            // ปุ่มแท็บตารางอันดับชั้นเรียน
            document.querySelectorAll('.card-actions .chart-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // เอาคลาส active ออกจากทุกแท็บ
                    document.querySelectorAll('.card-actions .chart-tab').forEach(t => t.classList.remove('active'));
                    // เพิ่มคลาส active ให้กับแท็บที่คลิก
                    this.classList.add('active');
                    
                    // กรองข้อมูลตารางตามระดับชั้นที่เลือก
                    const level = this.getAttribute('data-level');
                    filterClassTable(level);
                });
            });
            
            // ช่องค้นหานักเรียน
            document.getElementById('student-search').addEventListener('input', function() {
                filterStudentTable(this.value);
            });
        }

        // ฟังก์ชันอัปเดตข้อมูลกราฟเส้นตามช่วงเวลา
        function updateLineChart(period) {
            // ในตัวอย่างนี้จะใช้ข้อมูลเดิม แต่ในระบบจริงควรส่ง AJAX ไปดึงข้อมูลตามช่วงเวลา
            
            // ตัวอย่างการเปลี่ยน format ของ label
            let labels = weeklyAttendanceData.map(item => item.date);
            let data = weeklyAttendanceData.map(item => item.attendance_rate);
            
            if (period === 'month') {
                // สมมติว่าเป็นข้อมูลรายวันในเดือนปัจจุบัน (30 วัน)
                labels = Array.from({length: 30}, (_, i) => `${i+1}`);
                data = Array.from({length: 30}, () => Math.floor(80 + Math.random() * 20));
            } else if (period === 'semester') {
                // สมมติว่าเป็นข้อมูลรายเดือนในภาคเรียนปัจจุบัน
                labels = ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
                data = [92.5, 91.8, 90.5, 93.2, 94.1, 94.8];
            }
            
            // อัปเดตข้อมูลกราฟ
            lineChart.data.labels = labels;
            lineChart.data.datasets[0].data = data;
            lineChart.update();
        }

        // ฟังก์ชันกรองตารางชั้นเรียนตามระดับ
        function filterClassTable(level) {
            const rows = document.querySelectorAll('.class-rank-table tbody tr');
            
            rows.forEach(row => {
                if (level === 'all' || row.getAttribute('data-level') === level) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันกรองตารางนักเรียนจากการค้นหา
        function filterStudentTable(searchText) {
            const rows = document.querySelectorAll('#risk-students-table tbody tr');
            const searchLower = searchText.toLowerCase();
            
            rows.forEach(row => {
                const studentName = row.querySelector('.student-detail a')?.textContent.toLowerCase() || '';
                const studentCode = row.querySelector('.student-detail p')?.textContent.toLowerCase() || '';
                
                if (studentName.includes(searchLower) || studentCode.includes(searchLower) || searchText === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันเปลี่ยนช่วงเวลาการแสดงผล
        function changePeriod() {
            const periodSelector = document.getElementById('period-selector');
            const period = periodSelector.value;
            
            // ในระบบจริง ควรส่ง AJAX ไปดึงข้อมูลตามช่วงเวลาที่เลือก
            alert(`กำลังเปลี่ยนการแสดงผลเป็น: ${period}`);
            
            // ถ้าเป็นกำหนดเอง ให้แสดง modal เลือกวันที่
            if (period === 'custom') {
                // แสดง modal เลือกวันที่
                alert('กรุณาเลือกช่วงวันที่ที่ต้องการ');
            }
        }

        // ฟังก์ชันดาวน์โหลดรายงาน
        function downloadReport() {
            const periodSelector = document.getElementById('period-selector');
            const period = periodSelector.value;
            
            // ในระบบจริง ควรส่ง POST request ไปยัง endpoint ที่จะสร้างรายงาน
            alert(`กำลังดาวน์โหลดรายงานสำหรับช่วง: ${period}`);
        }

        // ฟังก์ชันดูรายละเอียดนักเรียน
        function viewStudentDetail(studentId) {
            currentStudentId = studentId;
            
            // แสดง modal
            const modal = document.getElementById('studentDetailModal');
            modal.style.display = 'block';
            
            // ตั้งค่า loading state
            document.getElementById('student-detail-content').innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
            
            // ในระบบจริง ควรส่ง AJAX ไปดึงข้อมูลนักเรียน
            // ตัวอย่างการจำลองการโหลดข้อมูล
            setTimeout(() => {
                // สมมติว่าได้รับข้อมูลจาก server
                const studentData = {
                    id: studentId,
                    name: 'นักเรียนรหัส ' + studentId,
                    class: 'ปวช.1/1',
                    attendanceRate: 65.8,
                    attendance: [
                        { date: '10 พ.ค. 2568', status: 'มา', time: '07:45' },
                        { date: '11 พ.ค. 2568', status: 'ขาด', time: '-' },
                        { date: '12 พ.ค. 2568', status: 'มา', time: '07:50' },
                        { date: '13 พ.ค. 2568', status: 'มา', time: '07:42' },
                        { date: '14 พ.ค. 2568', status: 'ขาด', time: '-' }
                    ]
                };
                
                // อัปเดตชื่อนักเรียนใน modal
                document.getElementById('modal-student-name').textContent = studentData.name;
                
                // สร้าง HTML สำหรับแสดงข้อมูล
                let html = `
                    <div class="student-info">
                        <div class="student-header">
                            <h3>${studentData.name}</h3>
                            <p>ชั้น ${studentData.class}</p>
                            <p>อัตราการเข้าแถว: <span class="${studentData.attendanceRate < 70 ? 'text-danger' : 'text-warning'}">${studentData.attendanceRate}%</span></p>
                        </div>
                        
                        <h4>ประวัติการเข้าแถว</h4>
                        <div class="table-responsive">
                            <table class="attendance-history-table">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>สถานะ</th>
                                        <th>เวลา</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                // เพิ่มข้อมูลประวัติการเข้าแถว
                studentData.attendance.forEach(day => {
                    const statusClass = day.status === 'มา' ? 'text-success' : 'text-danger';
                    html += `
                        <tr>
                            <td>${day.date}</td>
                            <td><span class="${statusClass}">${day.status}</span></td>
                            <td>${day.time}</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="button-group">
                            <button class="btn-primary" onclick="notifyParent(${studentId})">
                                <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                            </button>
                            <button class="btn-secondary" onclick="viewFullHistory(${studentId})">
                                <span class="material-icons">history</span> ดูประวัติทั้งหมด
                            </button>
                        </div>
                    </div>
                `;
                
                // อัปเดตเนื้อหาใน modal
                document.getElementById('student-detail-content').innerHTML = html;
            }, 500);
        }

        // ฟังก์ชันปิด modal
        function closeModal() {
            document.getElementById('studentDetailModal').style.display = 'none';
        }

        // ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครอง
        function notifyParent(studentId) {
            currentStudentId = studentId;
            
            // แสดง modal แจ้งเตือน
            const modal = document.getElementById('notificationModal');
            modal.style.display = 'block';
            
            // ตั้งค่า template เริ่มต้น
            updateNotificationContent();
        }

        // ฟังก์ชันปิด modal แจ้งเตือน
        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        // ฟังก์ชันอัปเดตเนื้อหาข้อความแจ้งเตือน
        function updateNotificationContent() {
            const template = document.getElementById('notification-template').value;
            const contentField = document.getElementById('notification-content');
            
            // ตัวอย่างเทมเพลตข้อความ
            switch (template) {
                case 'risk_alert':
                    contentField.value = `เรียน ผู้ปกครองของนักเรียน

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 70% ซึ่งต่ำกว่าเกณฑ์ที่กำหนด (80%)

กรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'absence_alert':
                    contentField.value = `เรียน ผู้ปกครองของนักเรียน

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้

กรุณาติดต่อครูที่ปรึกษาหากมีข้อสงสัย

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'monthly_report':
                    contentField.value = `เรียน ผู้ปกครองของนักเรียน

รายงานสรุปการเข้าแถวประจำเดือนพฤษภาคม 2568

จำนวนวันเข้าแถว: 15 วัน
จำนวนวันขาด: 5 วัน
อัตราการเข้าแถว: 75%
สถานะ: เสี่ยงไม่ผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'custom':
                    contentField.value = '';
                    break;
            }
        }

        // ฟังก์ชันส่งข้อความแจ้งเตือน
        function sendNotification() {
            const template = document.getElementById('notification-template').value;
            const content = document.getElementById('notification-content').value;
            
            // ในระบบจริง ควรส่ง AJAX ไปยัง backend เพื่อส่งข้อความแจ้งเตือน
            alert(`กำลังส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนรหัส ${currentStudentId} โดยใช้เทมเพลต ${template}`);
            
            // ปิด modal
            closeNotificationModal();
        }

        // ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมด
        function notifyAllRiskStudents() {
            // ในระบบจริง ควรมี modal ให้ยืนยันการส่งแจ้งเตือนทั้งหมด
            alert('กำลังส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมด');
        }

        // ฟังก์ชันดูประวัติการเข้าแถวทั้งหมด
        function viewFullHistory(studentId) {
            // ในระบบจริง ควรนำทางไปยังหน้าประวัติแบบละเอียด
            window.location.href = `student_history.php?id=${studentId}`;
        }

        // ปิด modal เมื่อคลิกนอกกรอบ
        window.onclick = function(event) {
            const studentModal = document.getElementById('studentDetailModal');
            const notificationModal = document.getElementById('notificationModal');
            
            if (event.target === studentModal) {
                studentModal.style.display = 'none';
            }
            
            if (event.target === notificationModal) {
                notificationModal.style.display = 'none';
            }
        };
    </script>
</body>
</html>

<?php
// โหลด footer
include_once 'templates/footer.php';
?>
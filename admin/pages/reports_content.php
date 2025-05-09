<?php
// เชื่อมต่อกับฐานข้อมูล (ถ้ายังไม่เชื่อมต่อ)
if (!function_exists('getDB')) {
    require_once '../db_connect.php';
}

// ฟังก์ชันสำหรับดึงข้อมูลสถิติภาพรวม
function getOverallStats() {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academicYear) {
        return array(
            'total_students' => 0,
            'attendance_days' => 0,
            'avg_attendance_rate' => 0,
            'risk_students' => 0,
            'academic_year' => 'ไม่พบข้อมูล',
            'semester' => ''
        );
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    
    // จำนวนนักเรียนทั้งหมด
    $query = "SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา'";
    $stmt = $conn->query($query);
    $totalStudents = $stmt->fetchColumn();
    
    // จำนวนวันเข้าแถวทั้งหมดในเดือนปัจจุบัน
    $currentMonth = date('m');
    $currentYear = date('Y');
    $query = "SELECT COUNT(DISTINCT date) 
              FROM attendance 
              WHERE academic_year_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId, $currentMonth, $currentYear]);
    $attendanceDays = $stmt->fetchColumn();
    
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
    $lastMonth = $currentMonth - 1;
    $lastMonthYear = $currentYear;
    if ($lastMonth <= 0) {
        $lastMonth = 12;
        $lastMonthYear--;
    }
    
    $query = "SELECT 
                COUNT(DISTINCT a.student_id) as total_present,
                (SELECT COUNT(*) FROM students WHERE status = 'กำลังศึกษา') as total_students,
                COUNT(DISTINCT a.date) as total_days
              FROM attendance a
              WHERE a.academic_year_id = ? 
              AND MONTH(a.date) = ? 
              AND YEAR(a.date) = ?
              AND a.attendance_status = 'present'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$academicYearId, $lastMonth, $lastMonthYear]);
    $lastMonthData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastMonthRate = 0;
    if ($lastMonthData['total_days'] > 0 && $lastMonthData['total_students'] > 0) {
        $lastMonthRate = ($lastMonthData['total_present'] / ($lastMonthData['total_students'] * $lastMonthData['total_days'])) * 100;
    }
    
    $rateChange = $avgAttendanceRate - $lastMonthRate;
    
    return array(
        'total_students' => $totalStudents,
        'attendance_days' => $attendanceDays,
        'avg_attendance_rate' => $avgAttendanceRate,
        'rate_change' => $rateChange,
        'risk_students' => $riskStudents,
        'academic_year' => $academicYear['year'],
        'semester' => $academicYear['semester'],
        'current_month' => getThaiMonth($currentMonth)
    );
}

// ฟังก์ชันสำหรับดึงข้อมูลนักเรียนที่เสี่ยงตก
function getRiskStudents($limit = 5) {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
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
}

// ฟังก์ชันสำหรับดึงข้อมูลอัตราการเข้าแถวตามห้องเรียน
function getClassAttendanceRates() {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
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
                (SUM(sar.total_attendance_days) / (SUM(sar.total_attendance_days) + SUM(sar.total_absence_days)) * 100) as attendance_rate
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
}

// ฟังก์ชันสำหรับดึงข้อมูลอัตราการเข้าแถวตลอดปีการศึกษา
function getYearlyAttendanceTrends() {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academicYear) {
        return array();
    }
    
    $academicYearId = $academicYear['academic_year_id'];
    $startDate = $academicYear['start_date'];
    $endDate = $academicYear['end_date'];
    
    // สร้างอาร์เรย์ของเดือน
    $months = array();
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    while ($currentDate <= $endDateObj) {
        $month = $currentDate->format('m');
        $year = $currentDate->format('Y');
        
        $months[] = array(
            'month' => $month,
            'year' => $year,
            'month_name' => getThaiMonth($month)
        );
        
        $currentDate->modify('+1 month');
    }
    
    // ดึงข้อมูลอัตราการเข้าแถวรายเดือน
    $trends = array();
    
    foreach ($months as $monthData) {
        $month = $monthData['month'];
        $year = $monthData['year'];
        
        $query = "SELECT 
                    COUNT(DISTINCT CASE WHEN a.attendance_status = 'present' THEN a.student_id ELSE NULL END) as present_count,
                    COUNT(DISTINCT a.student_id) as total_students,
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
        
        $trends[] = array(
            'month' => $monthData['month_name'],
            'rate' => $rate
        );
    }
    
    return $trends;
}

// ฟังก์ชันสำหรับดึงข้อมูลสาเหตุการขาดแถว
function getAbsenceReasons() {
    $conn = getDB();
    
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academicYearId = $stmt->fetchColumn();
    
    // ในระบบจริงควรมีตารางเก็บสาเหตุการขาด แต่ในตัวอย่างนี้จะใช้ข้อมูลตัวอย่าง
    // เนื่องจากไม่พบตารางเก็บสาเหตุการขาดในโครงสร้างฐานข้อมูล
    $reasons = array(
        array('reason' => 'ป่วย', 'percent' => 42),
        array('reason' => 'ธุระส่วนตัว', 'percent' => 28),
        array('reason' => 'มาสาย', 'percent' => 15),
        array('reason' => 'ไม่ทราบสาเหตุ', 'percent' => 15)
    );
    
    return $reasons;
}

// ฟังก์ชันสำหรับดึงข้อมูลแผนกวิชา
function getDepartments() {
    $conn = getDB();
    
    $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $departments;
}

// ฟังก์ชันสำหรับดึงข้อมูลระดับชั้น
function getClassLevels() {
    $conn = getDB();
    
    $query = "SELECT DISTINCT level FROM classes WHERE is_active = 1 ORDER BY level";
    $stmt = $conn->query($query);
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return $levels;
}

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = array(
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
    );
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

// ดึงข้อมูลสำหรับรายงาน
$stats = getOverallStats();
$riskStudents = getRiskStudents(5);
$classRates = getClassAttendanceRates();
$yearlyTrends = getYearlyAttendanceTrends();
$absenceReasons = getAbsenceReasons();
$departments = getDepartments();
$classLevels = getClassLevels();
?>

<!-- แผงค้นหาและกรองข้อมูล -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ตัวกรองรายงาน
    </div>
    
    <div class="filter-container">
        <div class="filter-group">
            <div class="filter-label">ประเภทรายงาน</div>
            <select class="form-control" id="reportType" onchange="changeReportType()">
                <option value="daily">รายงานประจำวัน</option>
                <option value="weekly">รายงานประจำสัปดาห์</option>
                <option value="monthly" selected>รายงานประจำเดือน</option>
                <option value="semester">รายงานประจำภาคเรียน</option>
                <option value="class">รายงานตามชั้นเรียน</option>
                <option value="student">รายงานรายบุคคล</option>
            </select>
        </div>
        
        <div class="filter-group">
            <div class="filter-label">แผนกวิชา</div>
            <select class="form-control" id="departmentFilter">
                <option value="">ทุกแผนก</option>
                <?php foreach ($departments as $department): ?>
                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <div class="filter-label">ช่วงเวลา</div>
            <select class="form-control" id="reportPeriod" onchange="toggleDateRange()">
                <option value="current" selected>เดือนปัจจุบัน (<?php echo $stats['current_month']; ?> <?php echo $stats['academic_year'] + 543; ?>)</option>
                <option value="prev">เดือนที่แล้ว</option>
                <option value="last3">3 เดือนย้อนหลัง</option>
                <option value="semester">ภาคเรียนที่ <?php echo $stats['semester']; ?>/<?php echo $stats['academic_year'] + 543; ?></option>
                <option value="custom">กำหนดเอง</option>
            </select>
        </div>
        
        <div class="filter-group date-range" style="display: none;">
            <div class="filter-label">วันที่เริ่มต้น</div>
            <input type="date" class="form-control" id="startDate">
        </div>
        
        <div class="filter-group date-range" style="display: none;">
            <div class="filter-label">วันที่สิ้นสุด</div>
            <input type="date" class="form-control" id="endDate">
        </div>
        
        <div class="filter-group class-filter">
            <div class="filter-label">ระดับชั้น</div>
            <select class="form-control" id="classLevel">
                <option value="">ทุกระดับชั้น</option>
                <?php foreach ($classLevels as $level): ?>
                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group class-filter">
            <div class="filter-label">ห้องเรียน</div>
            <select class="form-control" id="classRoom">
                <option value="">ทุกห้อง</option>
                <!-- กลุ่มห้องจะถูกเติมด้วย JavaScript เมื่อเลือกระดับชั้น -->
            </select>
        </div>
        
        <div class="filter-group student-filter" style="display: none;">
            <div class="filter-label">รหัส/ชื่อนักเรียน</div>
            <input type="text" class="form-control" id="studentSearch" placeholder="ป้อนรหัสหรือชื่อนักเรียน">
        </div>
        
        <button class="filter-button" onclick="generateReport()">
            <span class="material-icons">search</span>
            สร้างรายงาน
        </button>
    </div>
</div>

<!-- สรุปข้อมูลรายงาน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">assessment</span>
        สรุปรายงานประจำเดือน<?php echo $stats['current_month']; ?> <?php echo $stats['academic_year'] + 543; ?>
    </div>
    
    <div class="monthly-summary">
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">จำนวนนักเรียนทั้งหมด</div>
            <div class="monthly-summary-value"><?php echo number_format($stats['total_students']); ?></div>
            <div class="monthly-summary-subtext">นักเรียนทั้งหมดในระบบ</div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">จำนวนวันเข้าแถว</div>
            <div class="monthly-summary-value"><?php echo $stats['attendance_days']; ?></div>
            <div class="monthly-summary-subtext">วันเข้าแถวเดือน<?php echo $stats['current_month']; ?></div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">อัตราการเข้าแถวเฉลี่ย</div>
            <div class="monthly-summary-value <?php echo ($stats['rate_change'] >= 0) ? 'success' : 'danger'; ?>">
                <?php echo number_format($stats['avg_attendance_rate'], 1); ?>%
            </div>
            <div class="monthly-summary-subtext">
                <?php if ($stats['rate_change'] >= 0): ?>
                    เพิ่มขึ้น <?php echo number_format(abs($stats['rate_change']), 1); ?>% จากเดือนที่แล้ว
                <?php else: ?>
                    ลดลง <?php echo number_format(abs($stats['rate_change']), 1); ?>% จากเดือนที่แล้ว
                <?php endif; ?>
            </div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">นักเรียนเสี่ยงตกกิจกรรม</div>
            <div class="monthly-summary-value danger"><?php echo $stats['risk_students']; ?></div>
            <div class="monthly-summary-subtext">อัตราเข้าแถวต่ำกว่าเกณฑ์</div>
        </div>
    </div>
    
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">แนวโน้มการเข้าแถวตลอดปีการศึกษา</div>
                <div class="chart-actions">
                    <button class="chart-action-btn" onclick="refreshYearlyChart()">
                        <span class="material-icons">refresh</span>
                        รีเฟรช
                    </button>
                    <button class="chart-action-btn" onclick="downloadYearlyChart()">
                        <span class="material-icons">download</span>
                        ดาวน์โหลด
                    </button>
                </div>
            </div>
            
            <div class="chart-container" id="yearlyTrendChart">
                <!-- Chart will be rendered here by JavaScript -->
                <canvas id="yearlyAttendanceChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">สาเหตุการขาดแถว</div>
            </div>
            
            <div class="chart-container" id="absenceReasonChart">
                <!-- Pie chart will be rendered here by JavaScript -->
                <canvas id="absenceReasonsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- เปรียบเทียบอัตราการเข้าแถวตามชั้นเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">bar_chart</span>
        อัตราการเข้าแถวแยกตามระดับชั้น
    </div>
    
    <div class="chart-container" id="classComparisonChart">
        <!-- Class comparison chart will be rendered here by JavaScript -->
        <canvas id="classComparisonBarChart"></canvas>
    </div>
</div>

<!-- รายชื่อนักเรียนที่เสี่ยงตกกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">warning</span>
        รายชื่อนักเรียนที่เสี่ยงตกหรือตกกิจกรรมเข้าแถว
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">นักเรียน</th>
                    <th width="10%">ชั้น/ห้อง</th>
                    <th width="10%">อัตราการเข้าแถว</th>
                    <th width="10%">วันที่ขาด</th>
                    <th width="15%">ครูที่ปรึกษา</th>
                    <th width="15%">สถานะ</th>
                    <th width="15%">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riskStudents as $index => $student): ?>
                    <?php 
                        $attendanceRate = $student['attendance_rate'];
                        $statusClass = $attendanceRate < 70 ? 'danger' : 'warning';
                        $statusText = $attendanceRate < 70 ? 'ตกกิจกรรม' : 'เสี่ยงตกกิจกรรม';
                        $studentInitial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar"><?php echo $studentInitial; ?></div>
                                <div class="student-details">
                                    <div class="student-name"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                    <div class="student-class">เลขที่ <?php echo $student['student_code']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $student['class_name']; ?></td>
                        <td><span class="attendance-percent <?php echo $statusClass; ?>"><?php echo number_format($attendanceRate, 1); ?>%</span></td>
                        <td><?php echo $student['total_absence_days']; ?> วัน</td>
                        <td><?php echo $student['advisor_name']; ?></td>
                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="viewStudentDetails(<?php echo $student['student_id']; ?>)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ" onclick="sendNotificationToParent(<?php echo $student['student_id']; ?>)">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($riskStudents)): ?>
                    <tr>
                        <td colspan="8" class="text-center">ไม่พบข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card-footer">
        <div class="pagination">
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">2</a>
            <a href="#" class="page-link">3</a>
            <span class="page-separator">...</span>
            <a href="#" class="page-link"><?php echo ceil($stats['risk_students'] / 5); ?></a>
        </div>
        
        <div class="page-info">
            แสดง 1-<?php echo min(5, $stats['risk_students']); ?> จาก <?php echo $stats['risk_students']; ?> รายการ
        </div>
    </div>
</div>

<!-- ตัวแทน Modal ที่จะถูกเรียกใช้จาก JavaScript -->
<div class="modal" id="studentDetailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('studentDetailModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ข้อมูลการเข้าแถวละเอียด - <span id="studentDetailName"></span></h2>
        
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar" id="studentDetailInitial"></div>
                <div class="student-profile-info">
                    <h3 id="studentDetailFullName"></h3>
                    <p>รหัสนักเรียน: <span id="studentDetailCode"></span></p>
                    <p>ชั้น <span id="studentDetailClass"></span></p>
                    <p>อัตราการเข้าแถว: <span id="studentDetailRate" class="status-badge"></span></p>
                </div>
            </div>
            
            <div class="student-attendance-summary">
                <h4>สรุปการเข้าแถวประจำเดือน<?php echo $stats['current_month']; ?> <?php echo $stats['academic_year'] + 543; ?></h4>
                <div class="row">
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="studentDetailPresent"></div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="studentDetailAbsent"></div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="studentDetailTotal"></div>
                            <div class="attendance-stat-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-history">
                <h4>ประวัติการเข้าแถวรายวัน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                                <th>หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody id="studentAttendanceHistory">
                            <!-- จะถูกเติมด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="attendance-chart">
                <h4>แนวโน้มการเข้าแถวรายเดือน</h4>
                <div class="chart-container" style="height: 250px;">
                    <!-- ในทางปฏิบัติจริง ควรใช้ Chart.js สร้างกราฟเส้น -->
                    <canvas id="studentMonthlyChart"></canvas>
                </div>
            </div>
            
            <div class="notification-history">
                <h4>ประวัติการแจ้งเตือนผู้ปกครอง</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>ผู้ส่ง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="studentNotificationHistory">
                            <!-- จะถูกเติมด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('studentDetailModal')">ปิด</button>
            <button class="btn btn-primary" onclick="openSendMessageModal()">
                <span class="material-icons">send</span>
                ส่งข้อความแจ้งเตือน
            </button>
            <button class="btn btn-primary" onclick="printStudentReport()">
                <span class="material-icons">print</span>
                พิมพ์รายงาน
            </button>
        </div>
    </div>
</div>

<script>
// ข้อมูลสำหรับแผนภูมิ
const yearlyTrendsData = <?php echo json_encode($yearlyTrends); ?>;
const absenceReasonsData = <?php echo json_encode($absenceReasons); ?>;
const classRatesData = <?php echo json_encode($classRates); ?>;

// ฟังก์ชันเปลี่ยนประเภทรายงาน
function changeReportType() {
    const reportType = document.getElementById('reportType').value;
    const studentFilter = document.querySelectorAll('.student-filter');
    const classFilter = document.querySelectorAll('.class-filter');
    
    // แสดง/ซ่อนตัวกรองตามประเภทรายงาน
    if (reportType === 'student') {
        studentFilter.forEach(filter => filter.style.display = 'block');
    } else {
        studentFilter.forEach(filter => filter.style.display = 'none');
    }
}

// ฟังก์ชันแสดง/ซ่อนตัวเลือกช่วงวันที่
function toggleDateRange() {
    const reportPeriod = document.getElementById('reportPeriod').value;
    const dateRange = document.querySelectorAll('.date-range');
    
    if (reportPeriod === 'custom') {
        dateRange.forEach(filter => filter.style.display = 'block');
    } else {
        dateRange.forEach(filter => filter.style.display = 'none');
    }
}

// ฟังก์ชันสร้างรายงาน
function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    const departmentId = document.getElementById('departmentFilter').value;
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const studentSearch = document.getElementById('studentSearch')?.value || '';
    
    // ในทางปฏิบัติจริง จะเป็นการส่ง AJAX request ไปยัง backend
    console.log(`สร้างรายงานประเภท ${reportType} ช่วงเวลา ${reportPeriod} แผนก ${departmentId} ชั้น ${classLevel} ห้อง ${classRoom}`);
    
    // แสดงข้อความกำลังโหลด
    alert('กำลังสร้างรายงาน...');
    
    // Simulating reload with new data
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// ฟังก์ชันแสดงรายละเอียดนักเรียน
function viewStudentDetails(studentId) {
    // ในทางปฏิบัติจริง จะเป็นการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    
    // ข้อมูลตัวอย่างสำหรับการแสดงผล
    const studentData = {
        id: studentId,
        name: 'นายธนกฤต สุขใจ',
        code: '16478',
        className: 'ม.6/2 เลขที่ 12',
        attendanceRate: 68.5,
        presentDays: 15,
        absentDays: 7,
        totalDays: 22,
        attendanceHistory: [
            { date: '16/03/2568', status: 'มา', statusClass: 'success', time: '08:02', remark: '-' },
            { date: '15/03/2568', status: 'มา', statusClass: 'success', time: '08:05', remark: '-' },
            { date: '14/03/2568', status: 'ขาด', statusClass: 'danger', time: '-', remark: 'ไม่มาโรงเรียน' },
            { date: '13/03/2568', status: 'มาสาย', statusClass: 'warning', time: '08:32', remark: 'รถติด' },
            { date: '12/03/2568', status: 'มา', statusClass: 'success', time: '08:10', remark: '-' }
        ],
        notificationHistory: [
            { date: '16/03/2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'จารุวรรณ บุญมี', status: 'ส่งสำเร็จ', statusClass: 'success' },
            { date: '01/03/2568', type: 'แจ้งเตือนปกติ', sender: 'อ.ประสิทธิ์ ดีเลิศ', status: 'ส่งสำเร็จ', statusClass: 'success' },
            { date: '15/02/2568', type: 'แจ้งเตือนปกติ', sender: 'อ.ประสิทธิ์ ดีเลิศ', status: 'ส่งสำเร็จ', statusClass: 'success' }
        ]
    };
    
    // เติมข้อมูลลงใน Modal
    document.getElementById('studentDetailName').textContent = studentData.name;
    document.getElementById('studentDetailInitial').textContent = studentData.name.charAt(0);
    document.getElementById('studentDetailFullName').textContent = studentData.name;
    document.getElementById('studentDetailCode').textContent = studentData.code;
    document.getElementById('studentDetailClass').textContent = studentData.className;
    
    const rateElement = document.getElementById('studentDetailRate');
    rateElement.textContent = `${studentData.attendanceRate}%`;
    rateElement.className = 'status-badge ' + (studentData.attendanceRate < 70 ? 'danger' : 'warning');
    
    document.getElementById('studentDetailPresent').textContent = studentData.presentDays;
    document.getElementById('studentDetailAbsent').textContent = studentData.absentDays;
    document.getElementById('studentDetailTotal').textContent = studentData.totalDays;
    
    // เติมประวัติการเข้าแถว
    const historyTable = document.getElementById('studentAttendanceHistory');
    historyTable.innerHTML = '';
    
    studentData.attendanceHistory.forEach(record => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${record.date}</td>
            <td><span class="status-badge ${record.statusClass}">${record.status}</span></td>
            <td>${record.time}</td>
            <td>${record.remark}</td>
        `;
        historyTable.appendChild(row);
    });
    
    // เติมประวัติการแจ้งเตือน
    const notificationTable = document.getElementById('studentNotificationHistory');
    notificationTable.innerHTML = '';
    
    studentData.notificationHistory.forEach(record => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${record.date}</td>
            <td>${record.type}</td>
            <td>${record.sender}</td>
            <td><span class="status-badge ${record.statusClass}">${record.status}</span></td>
        `;
        notificationTable.appendChild(row);
    });
    
    // สร้างกราฟแนวโน้มการเข้าแถวรายเดือน
    createStudentMonthlyChart();
    
    // แสดง Modal
    showModal('studentDetailModal');
}

// ฟังก์ชันสร้างกราฟแนวโน้มการเข้าแถวรายเดือนของนักเรียน
function createStudentMonthlyChart() {
    const ctx = document.getElementById('studentMonthlyChart');
    
    // ข้อมูลตัวอย่าง
    const data = {
        labels: ['ม.ค.', 'ก.พ.', 'มี.ค.'],
        datasets: [{
            label: 'อัตราการเข้าแถว (%)',
            data: [68, 70, 68.5],
            backgroundColor: '#ffebee',
            borderColor: '#f44336',
            borderWidth: 2,
            tension: 0.4
        }]
    };
    
    // ถ้ามีกราฟเก่าให้ทำลายก่อน
    if (window.studentChart) {
        window.studentChart.destroy();
    }
    
    // สร้างกราฟใหม่
    window.studentChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            scales: {
                y: {
                    beginAtZero: false,
                    min: 0,
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
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// ฟังก์ชันส่งข้อความแจ้งเตือนไปยังผู้ปกครอง
function sendNotificationToParent(studentId) {
    // ในทางปฏิบัติจริง จะเป็นการส่ง AJAX request ไปยัง backend
    alert(`กำลังส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนรหัส ${studentId}`);
}

// ฟังก์ชันเปิดหน้าส่งข้อความ
function openSendMessageModal() {
    // ในทางปฏิบัติจริง จะเป็นการนำทางไปยังหน้าส่งข้อความหรือแสดงโมดัลส่งข้อความ
    window.location.href = 'send_notification.php?student_id=16478';
}

// ฟังก์ชันพิมพ์รายงานนักเรียน
function printStudentReport() {
    // ในทางปฏิบัติจริง จะเป็นการเปิดหน้าต่างพิมพ์หรือดาวน์โหลด PDF
    window.print();
}

// ฟังก์ชันแสดงโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// ฟังก์ชันปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// ฟังก์ชันรีเฟรชกราฟรายปี
function refreshYearlyChart() {
    createYearlyTrendChart();
}

// ฟังก์ชันดาวน์โหลดกราฟรายปี
function downloadYearlyChart() {
    // ในทางปฏิบัติจริง จะเป็นการดาวน์โหลดรูปภาพกราฟ
    alert('กำลังดาวน์โหลดกราฟแนวโน้มการเข้าแถวตลอดปีการศึกษา');
}

// ฟังก์ชันสร้างกราฟแนวโน้มรายปี
function createYearlyTrendChart() {
    const ctx = document.getElementById('yearlyAttendanceChart');
    
    const labels = yearlyTrendsData.map(item => item.month);
    const data = yearlyTrendsData.map(item => item.rate);
    
    // ถ้ามีกราฟเก่าให้ทำลายก่อน
    if (window.yearlyChart) {
        window.yearlyChart.destroy();
    }
    
    // สร้างกราฟใหม่
    window.yearlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: data,
                backgroundColor: '#e3f2fd',
                borderColor: '#1976d2',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
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
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                        }
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// ฟังก์ชันสร้างกราฟวงกลมสาเหตุการขาดแถว
function createAbsenceReasonsPieChart() {
    const ctx = document.getElementById('absenceReasonsChart');
    
    const labels = absenceReasonsData.map(item => item.reason);
    const data = absenceReasonsData.map(item => item.percent);
    
    // ถ้ามีกราฟเก่าให้ทำลายก่อน
    if (window.absenceChart) {
        window.absenceChart.destroy();
    }
    
    // สร้างกราฟใหม่
    window.absenceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#2196f3',  // สีฟ้า
                    '#ff9800',  // สีส้ม
                    '#9c27b0',  // สีม่วง
                    '#f44336'   // สีแดง
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed}%`;
                        }
                    }
                }
            }
        }
    });
}

// ฟังก์ชันสร้างกราฟเปรียบเทียบอัตราการเข้าแถวตามชั้นเรียน
function createClassComparisonChart() {
    const ctx = document.getElementById('classComparisonBarChart');
    
    // เตรียมข้อมูลสำหรับกราฟ
    const classNames = classRatesData.map(item => item.class_name);
    const attendanceRates = classRatesData.map(item => item.attendance_rate);
    
    // กำหนดสีตามเกณฑ์
    const barColors = attendanceRates.map(rate => {
        if (rate >= 90) return '#4caf50';  // สีเขียว (ดี)
        if (rate >= 80) return '#ff9800';  // สีส้ม (พอใช้)
        return '#f44336';  // สีแดง (ต้องปรับปรุง)
    });
    
    // ถ้ามีกราฟเก่าให้ทำลายก่อน
    if (window.classChart) {
        window.classChart.destroy();
    }
    
    // สร้างกราฟใหม่
    window.classChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: classNames,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: attendanceRates,
                backgroundColor: barColors,
                borderWidth: 0,
                maxBarThickness: 50
            }]
        },
        options: {
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
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const classInfo = classRatesData[dataIndex];
                            return [
                                `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`,
                                `จำนวนนักเรียน: ${classInfo.student_count} คน`,
                                `แผนกวิชา: ${classInfo.department_name}`
                            ];
                        }
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadReportData() {
    // ในทางปฏิบัติจริง จะเป็นการส่ง request ไปยัง endpoint ที่สร้างไฟล์รายงาน
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    
    alert(`กำลังดาวน์โหลดรายงาน ${reportType} สำหรับช่วง ${reportPeriod}`);
}

// เมื่อโหลดหน้าเสร็จ ให้เรียกฟังก์ชันเพื่อตั้งค่าแท็บและอื่นๆ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าการแสดงผลตัวกรอง
    const reportPeriodSelect = document.getElementById('reportPeriod');
    if (reportPeriodSelect) {
        reportPeriodSelect.addEventListener('change', toggleDateRange);
    }
    
    // ตั้งค่าการแสดงผลตัวกรองนักเรียน
    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', changeReportType);
    }
    
    // เพิ่ม event listener ให้กับปุ่มดูรายละเอียดนักเรียน
    const detailButtons = document.querySelectorAll('.table-action-btn.primary');
    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ในทางปฏิบัติจริง จะต้องดึง id ของนักเรียนจากแต่ละแถว
            viewStudentDetails(1);
        });
    });
    
    // สร้างกราฟต่างๆ
    createYearlyTrendChart();
    createAbsenceReasonsPieChart();
    createClassComparisonChart();
});
</script>
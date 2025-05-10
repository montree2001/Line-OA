<?php
// ตรวจสอบว่ามีตัวแปร $report_data หรือไม่
if (!isset($report_data)) {
    echo "<div class='alert alert-danger'>ไม่พบข้อมูลสำหรับการแสดงผลรายงาน</div>";
    return;
}

// ดึงข้อมูลจาก $report_data
$academic_year = $report_data['academic_year'];
$overview = $report_data['overview'];
$departments = $report_data['departments'];
$department_stats = $report_data['department_stats'];
$risk_students = $report_data['risk_students'];
$class_ranking = $report_data['class_ranking'];
$weekly_trends = $report_data['weekly_trends'];
$absence_reasons = $report_data['absence_reasons'];

// ข้อมูลปีการศึกษาปัจจุบัน
$current_academic_year = $academic_year['year'] + 543; // แปลงเป็น พ.ศ.
$current_semester = $academic_year['semester'];
$current_month = date('n');
$current_year = date('Y') + 543; // แปลงเป็น พ.ศ.

// ฟังก์ชันแปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thaiMonths[$month] ?? '';
}

// ชื่อเดือนปัจจุบันภาษาไทย
$current_thai_month = getThaiMonth($current_month);
?>

<!-- Main Header -->
<div class="main-header">
    <h1 class="page-title">แดชบอร์ดสรุปข้อมูลและสถิติการเข้าแถว</h1>
    <div class="header-actions">
        <div class="filter-group">
            <div class="date-filter">
                <span class="material-icons">date_range</span>
                <select id="period-selector">
                    <option value="day">วันนี้</option>
                    <option value="week">สัปดาห์นี้</option>
                    <option value="month" selected>เดือนนี้ (<?php echo $current_thai_month; ?>)</option>
                    <option value="semester">ภาคเรียนที่ <?php echo $current_semester; ?>/<?php echo $current_academic_year; ?></option>
                    <option value="custom">กำหนดเอง</option>
                </select>
            </div>
            <div class="department-filter">
                <span class="material-icons">category</span>
                <select id="department-selector">
                    <option value="all">ทุกแผนก</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button class="header-button" id="downloadReportBtn">
            <span class="material-icons">file_download</span> ดาวน์โหลดรายงาน
        </button>
        <button class="header-button" id="printReportBtn">
            <span class="material-icons">print</span> พิมพ์รายงาน
        </button>
    </div>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-title">
            <span class="material-icons">people</span>
            จำนวนนักเรียนทั้งหมด
        </div>
        <div class="stat-value"><?php echo number_format($overview['total_students']); ?></div>
        <div class="stat-change">
            <span>นักเรียนในระบบทั้งหมด</span>
        </div>
    </div>
    
    <div class="stat-card green">
        <div class="stat-title">
            <span class="material-icons">check_circle</span>
            เข้าแถวเฉลี่ย
        </div>
        <div class="stat-value"><?php echo number_format($overview['avg_attendance_rate'], 1); ?>%</div>
        <div class="stat-change <?php echo ($overview['rate_change'] >= 0) ? 'positive' : 'negative'; ?>">
            <span class="material-icons"><?php echo ($overview['rate_change'] >= 0) ? 'arrow_upward' : 'arrow_downward'; ?></span>
            <?php echo ($overview['rate_change'] >= 0) ? 'เพิ่มขึ้น' : 'ลดลง'; ?> <?php echo abs($overview['rate_change']); ?>%
        </div>
    </div>
    
    <div class="stat-card red">
        <div class="stat-title">
            <span class="material-icons">cancel</span>
            นักเรียนตกกิจกรรม
        </div>
        <div class="stat-value"><?php echo $overview['failed_students']; ?></div>
        <div class="stat-change">
            <span>น้อยกว่า 70%</span>
        </div>
    </div>
    
    <div class="stat-card yellow">
        <div class="stat-title">
            <span class="material-icons">warning</span>
            นักเรียนเสี่ยงตกกิจกรรม
        </div>
        <div class="stat-value"><?php echo $overview['risk_students']; ?></div>
        <div class="stat-change">
            <span>70% - 80%</span>
        </div>
    </div>
</div>

<!-- Department stats -->
<div class="department-stats" id="departmentStats">
    <?php foreach ($department_stats as $dept): ?>
    <div class="department-card">
        <div class="department-name">
            <span><?php echo $dept['department_name']; ?></span>
            <span class="attendance-rate <?php echo $dept['rate_class']; ?>"><?php echo $dept['attendance_rate']; ?>%</span>
        </div>
        <div class="department-stats-row">
            <div class="department-stat">
                <div class="department-stat-label">นักเรียน</div>
                <div class="department-stat-value"><?php echo $dept['student_count']; ?></div>
            </div>
            <div class="department-stat">
                <div class="department-stat-label">เข้าแถว</div>
                <div class="department-stat-value"><?php echo $dept['total_attendance']; ?></div>
            </div>
            <div class="department-stat">
                <div class="department-stat-label">เสี่ยง</div>
                <div class="department-stat-value"><?php echo $dept['risk_count']; ?></div>
            </div>
        </div>
        <div class="department-progress">
            <div class="progress-bar">
                <div class="progress-fill <?php echo $dept['rate_class']; ?>" style="width: <?php echo $dept['attendance_rate']; ?>%;"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="charts-row">
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <span class="material-icons">trending_up</span>
                อัตราการเข้าแถวตามเวลา
            </div>
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
            <div class="chart-title">
                <span class="material-icons">pie_chart</span>
                สาเหตุการขาดแถว
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="attendancePieChart"></canvas>
            <div class="pie-legend">
                <?php foreach ($absence_reasons as $reason): ?>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: <?php echo $reason['color']; ?>"></div>
                    <span><?php echo $reason['reason']; ?> (<?php echo $reason['percent']; ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Daily Attendance Calendar (for daily view) -->
<div class="card" id="dailyAttendanceCard" style="display:none;">
    <div class="card-header">
        <div class="card-title">
            <span class="material-icons">calendar_today</span>
            การเข้าแถวรายวัน - <?php echo $current_thai_month; ?> <?php echo $current_year; ?>
        </div>
    </div>
    
    <div class="calendar-view" id="calendarView">
        <!-- Calendar will be populated by JavaScript -->
    </div>
</div>

<!-- Students at Risk Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span class="material-icons">warning</span>
            นักเรียนที่ตกกิจกรรมหรือมีความเสี่ยง
        </div>
        <div class="card-actions">
            <div class="search-box">
                <span class="material-icons">search</span>
                <input type="text" id="student-search" placeholder="ค้นหาชื่อหรือรหัสนักเรียน...">
            </div>
            <button class="header-button" id="notifyAllBtn">
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
                <?php if (empty($risk_students)): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียนที่มีความเสี่ยง</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($risk_students as $student): ?>
                    <tr data-student-id="<?php echo $student['student_id']; ?>">
                        <td>
                            <div class="student-name">
                                <div class="student-avatar"><?php echo $student['initial']; ?></div>
                                <div class="student-detail">
                                    <a href="#" class="student-link" data-student-id="<?php echo $student['student_id']; ?>">
                                        <?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?>
                                    </a>
                                    <p>รหัส: <?php echo $student['student_code']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $student['class_name']; ?></td>
                        <td><?php echo $student['advisor_name']; ?></td>
                        <td><span class="attendance-rate <?php echo $student['status']; ?>"><?php echo $student['attendance_rate']; ?>%</span></td>
                        <td><span class="status-badge <?php echo $student['status']; ?>"><?php echo $student['status_text']; ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-button view" data-student-id="<?php echo $student['student_id']; ?>">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="action-button message" data-student-id="<?php echo $student['student_id']; ?>">
                                    <span class="material-icons">message</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
        <div class="card-title">
            <span class="material-icons">leaderboard</span>
            อันดับอัตราการเข้าแถวตามชั้นเรียน
        </div>
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
                <?php if (empty($class_ranking)): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่พบข้อมูลชั้นเรียน</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($class_ranking as $class): ?>
                    <tr data-class-id="<?php echo $class['class_id']; ?>" data-level="<?php echo $class['level_group']; ?>">
                        <td><?php echo $class['class_name']; ?></td>
                        <td><?php echo $class['advisor_name']; ?></td>
                        <td><?php echo $class['student_count']; ?></td>
                        <td><?php echo $class['present_count']; ?></td>
                        <td><span class="attendance-rate <?php echo $class['rate_class']; ?>"><?php echo $class['attendance_rate']; ?>%</span></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $class['bar_class']; ?>" style="width: <?php echo $class['attendance_rate']; ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal สำหรับแสดงรายละเอียดนักเรียน -->
<div class="modal" id="studentDetailModal">
    <div class="modal-content">
        <span class="close" id="closeStudentModal">&times;</span>
        <h2 id="modal-student-name">ข้อมูลการเข้าแถว</h2>
        <div id="student-detail-content">
            <!-- ข้อมูลนักเรียนจะถูกแสดงที่นี่ -->
            <div class="loading">กำลังโหลดข้อมูล...</div>
        </div>
    </div>
</div>

<!-- Modal สำหรับส่งข้อความแจ้งเตือน -->
<div class="modal" id="notificationModal">
    <div class="modal-content">
        <span class="close" id="closeNotificationModal">&times;</span>
        <h2>ส่งข้อความแจ้งเตือนผู้ปกครอง</h2>
        <div class="notification-form">
            <div class="form-group">
                <label for="notification-template">เลือกเทมเพลตข้อความ</label>
                <select id="notification-template">
                    <option value="risk_alert">แจ้งเตือนความเสี่ยงตกกิจกรรม</option>
                    <option value="absence_alert">แจ้งเตือนการขาดเรียน</option>
                    <option value="monthly_report">รายงานประจำเดือน</option>
                    <option value="custom">ข้อความกำหนดเอง</option>
                </select>
            </div>
            <div class="form-group">
                <label for="notification-content">ข้อความ</label>
                <textarea id="notification-content" rows="8"></textarea>
            </div>
            <div class="form-actions">
                <button class="btn-cancel" id="cancelNotification">ยกเลิก</button>
                <button class="btn-send" id="sendNotification">ส่งข้อความ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับเลือกช่วงวันที่ -->
<div class="modal" id="dateRangeModal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" id="closeDateRangeModal">&times;</span>
        <h2>เลือกช่วงวันที่</h2>
        <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
            <div class="form-group">
                <label for="start-date">วันที่เริ่มต้น</label>
                <input type="date" id="start-date" class="form-control">
            </div>
            <div class="form-group">
                <label for="end-date">วันที่สิ้นสุด</label>
                <input type="date" id="end-date" class="form-control">
            </div>
            <div class="form-actions">
                <button class="btn-cancel" id="cancelDateRange">ยกเลิก</button>
                <button class="btn-send" id="applyDateRange">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- ส่งข้อมูลไปยัง JavaScript -->
<script>
// ข้อมูลเส้นกราฟสำหรับการเข้าแถว 7 วันล่าสุด
const weeklyTrendsData = <?php echo json_encode($weekly_trends); ?>;

// ข้อมูลสาเหตุการขาดแถว
const absenceReasonsData = <?php echo json_encode($absence_reasons); ?>;

// ข้อมูลปีการศึกษาปัจจุบัน
const academicYearData = {
    year: <?php echo $academic_year['year']; ?>,
    semester: <?php echo $academic_year['semester']; ?>,
    thai_year: <?php echo $current_academic_year; ?>,
    current_month: '<?php echo $current_thai_month; ?>',
    current_year: <?php echo $current_year; ?>
};
</script>
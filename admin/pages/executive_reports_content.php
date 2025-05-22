<?php
/**
 * executive_reports_content.php - เนื้อหาหน้าแดชบอร์ดผู้บริหาร
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตรวจสอบข้อมูลรายงาน
if (!isset($report_data)) {
    echo "<div class='alert alert-danger'>ไม่สามารถโหลดข้อมูลรายงานได้</div>";
    return;
}

// แยกข้อมูลออกจาก array
$academic_year = $report_data['academic_year'];
$overview = $report_data['overview'];
$departments = $report_data['departments'];
$department_performance = $report_data['department_performance'];
$critical_students = $report_data['critical_students'];
$class_performance = $report_data['class_performance'];
$weekly_trends = $report_data['weekly_trends'];
$attendance_status = $report_data['attendance_status'];
$target_comparison = $report_data['target_comparison'];
$notification_stats = $report_data['notification_stats'];

// ข้อมูลปีการศึกษา
$current_academic_year = $academic_year['year'] + 543;
$current_semester = $academic_year['semester'];
$current_month = date('n');
$current_year = date('Y') + 543;

function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thaiMonths[$month] ?? '';
}

$current_thai_month = getThaiMonth($current_month);
?>

<!-- Header สำหรับผู้บริหาร -->
<div class="executive-header">
    <div class="executive-title">
        <h1>แดชบอร์ดผู้บริหาร</h1>
        <p class="subtitle">ภาพรวมการเข้าแถวและการจัดการวิทยาลัยการอาชีพปราสาท</p>
    </div>
    
    <div class="executive-filters">
        <div class="filter-group">
            <label>ช่วงเวลา:</label>
            <select id="period-selector" class="form-select">
                <option value="day">วันนี้</option>
                <option value="week">สัปดาห์นี้</option>
                <option value="month" selected>เดือนนี้ (<?php echo $current_thai_month; ?>)</option>
                <option value="semester">ภาคเรียนที่ <?php echo $current_semester; ?>/<?php echo $current_academic_year; ?></option>
                <option value="custom">กำหนดเอง</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>แผนกวิชา:</label>
            <select id="department-selector" class="form-select">
                <option value="all">ทุกแผนก</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button class="refresh-btn" onclick="refreshDashboard()">
            <i class="material-icons">refresh</i> รีเฟรช
        </button>
        
        <button class="export-btn" onclick="exportReport()">
            <i class="material-icons">file_download</i> ส่งออกรายงาน
        </button>
    </div>
</div>

<!-- สถิติหลักระดับผู้บริหาร -->
<div class="executive-stats">
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="material-icons">school</i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($overview['total_students']); ?></h3>
            <p>นักเรียนทั้งหมด</p>
            <span class="sub-text"><?php echo $overview['total_classes']; ?> ห้องเรียน, <?php echo $overview['total_departments']; ?> แผนก</span>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="material-icons">trending_up</i>
        </div>
        <div class="stat-content">
            <h3><?php echo $overview['avg_attendance_rate']; ?>%</h3>
            <p>อัตราการเข้าแถวเฉลี่ย</p>
            <span class="sub-text <?php echo $target_comparison['status'] == 'achieved' ? 'positive' : 'negative'; ?>">
                เป้าหมาย <?php echo $target_comparison['target']; ?>% 
                (<?php echo $target_comparison['status'] == 'achieved' ? '+' : ''; ?><?php echo $target_comparison['difference']; ?>%)
            </span>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="material-icons">warning</i>
        </div>
        <div class="stat-content">
            <h3><?php echo $overview['risk_students'] + $overview['critical_students']; ?></h3>
            <p>นักเรียนที่ต้องติดตาม</p>
            <span class="sub-text">เสี่ยง <?php echo $overview['risk_students']; ?> คน, วิกฤต <?php echo $overview['critical_students']; ?> คน</span>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="material-icons">stars</i>
        </div>
        <div class="stat-content">
            <h3><?php echo $overview['success_rate']; ?>%</h3>
            <p>อัตราความสำเร็จ</p>
            <span class="sub-text">นักเรียนดีเยี่ยม <?php echo $overview['excellent_students']; ?> คน</span>
        </div>
    </div>
</div>

<!-- แผนภูมิภาพรวม -->
<div class="executive-charts">
    <div class="chart-row">
        <!-- แนวโน้มการเข้าแถว -->
        <div class="chart-card main-trend">
            <div class="chart-header">
                <h3><i class="material-icons">show_chart</i> แนวโน้มการเข้าแถว (14 วันล่าสุด)</h3>
                <div class="chart-actions">
                    <button class="chart-btn active" data-period="week">รายสัปดาห์</button>
                    <button class="chart-btn" data-period="month">รายเดือน</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="executiveTrendChart"></canvas>
            </div>
        </div>
        
        <!-- สถานะการเข้าแถว -->
        <div class="chart-card status-overview">
            <div class="chart-header">
                <h3><i class="material-icons">donut_large</i> สถานะการเข้าแถว</h3>
            </div>
            <div class="chart-container">
                <canvas id="executiveStatusChart"></canvas>
            </div>
            <div class="status-legend">
                <?php foreach ($attendance_status as $status): ?>
                <div class="legend-item">
                    <div class="color-box" style="background-color: <?php echo $status['color']; ?>"></div>
                    <span><?php echo $status['status']; ?></span>
                    <strong><?php echo number_format($status['count']); ?> (<?php echo $status['percent']; ?>%)</strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- แท็บสำหรับข้อมูลรายละเอียด -->
<div class="executive-tabs">
    <button class="tab-btn active" data-tab="departments">ประสิทธิภาพแผนก</button>
    <button class="tab-btn" data-tab="classes">ประสิทธิภาพห้องเรียน</button>
    <button class="tab-btn" data-tab="students">นักเรียนเสี่ยงสูง</button>
    <button class="tab-btn" data-tab="reports">รายงานและแจ้งเตือน</button>
</div>

<!-- เนื้อหาแท็บ -->
<div class="tab-content active" id="tab-departments">
    <!-- ประสิทธิภาพแผนกวิชา -->
    <div class="section-header">
        <h2><i class="material-icons">business</i> ประสิทธิภาพแผนกวิชา</h2>
        <p>เปรียบเทียบผลการดำเนินงานแต่ละแผนกวิชา</p>
    </div>
    
    <!-- กราฟแท่งเปรียบเทียบแผนก -->
    <div class="chart-card department-comparison">
        <div class="chart-header">
            <h3>อัตราการเข้าแถวเปรียบเทียบระหว่างแผนก</h3>
        </div>
        <div class="chart-container">
            <canvas id="departmentComparisonChart"></canvas>
        </div>
    </div>
    
    <!-- ตารางประสิทธิภาพแผนก -->
    <div class="performance-grid">
        <?php foreach ($department_performance as $dept): ?>
        <div class="department-performance-card <?php echo $dept['performance_status']; ?>">
            <div class="dept-header">
                <h4><?php echo $dept['department_name']; ?></h4>
                <span class="status-badge <?php echo $dept['performance_status']; ?>">
                    <?php echo $dept['status_text']; ?>
                </span>
            </div>
            
            <div class="dept-metrics">
                <div class="metric">
                    <div class="metric-value"><?php echo $dept['total_students']; ?></div>
                    <div class="metric-label">นักเรียน</div>
                </div>
                <div class="metric">
                    <div class="metric-value"><?php echo $dept['total_classes']; ?></div>
                    <div class="metric-label">ห้องเรียน</div>
                </div>
                <div class="metric">
                    <div class="metric-value"><?php echo $dept['avg_attendance_rate']; ?>%</div>
                    <div class="metric-label">อัตราเข้าแถว</div>
                </div>
                <div class="metric">
                    <div class="metric-value"><?php echo $dept['at_risk_count']; ?></div>
                    <div class="metric-label">เสี่ยง</div>
                </div>
            </div>
            
            <div class="progress-indicator">
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $dept['performance_status']; ?>" 
                         style="width: <?php echo $dept['avg_attendance_rate']; ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span>ความเสี่ยง: <?php echo $dept['risk_percentage']; ?>%</span>
                    <span>เป้าหมาย: <?php echo $target_comparison['target']; ?>%</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="tab-content" id="tab-classes">
    <!-- ประสิทธิภาพห้องเรียน -->
    <div class="section-header">
        <h2><i class="material-icons">class</i> ประสิทธิภาพห้องเรียน</h2>
        <p>รายละเอียดผลการดำเนินงานของแต่ละห้องเรียน</p>
    </div>
    
    <!-- กราฟอันดับห้องเรียน -->
    <div class="chart-card class-ranking">
        <div class="chart-header">
            <h3>อันดับห้องเรียนตามประสิทธิภาพ</h3>
            <div class="filter-buttons">
                <button class="filter-btn active" data-level="all">ทั้งหมด</button>
                <button class="filter-btn" data-level="high">ปวส.</button>
                <button class="filter-btn" data-level="middle">ปวช.</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="classRankingChart"></canvas>
        </div>
    </div>
    
    <!-- รายละเอียดห้องเรียน -->
    <div class="classes-grid">
        <?php foreach ($class_performance as $class): ?>
        <div class="class-performance-card <?php echo $class['performance_level']; ?>">
            <div class="class-header">
                <div class="class-info">
                    <h4><?php echo $class['class_name']; ?> <?php echo $class['department_name']; ?></h4>
                    <p>ครูที่ปรึกษา: <?php echo $class['advisor_name'] ?: 'ไม่ระบุ'; ?></p>
                </div>
                <div class="performance-badge <?php echo $class['performance_level']; ?>">
                    <?php echo $class['level_text']; ?>
                </div>
            </div>
            
            <div class="class-stats">
                <div class="stat-row">
                    <span>จำนวนนักเรียน:</span>
                    <strong><?php echo $class['total_students']; ?> คน</strong>
                </div>
                <div class="stat-row">
                    <span>อัตราการเข้าแถว:</span>
                    <strong class="<?php echo $class['performance_level']; ?>">
                        <?php echo $class['avg_attendance_rate']; ?>%
                    </strong>
                </div>
                <div class="stat-row">
                    <span>นักเรียนเสี่ยง:</span>
                    <strong><?php echo $class['risk_count']; ?> คน (<?php echo $class['risk_percentage']; ?>%)</strong>
                </div>
            </div>
            
            <!-- กราหวงเล็ก -->
            <div class="mini-chart">
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $class['performance_level']; ?>" 
                         style="width: <?php echo $class['avg_attendance_rate']; ?>%;"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="tab-content" id="tab-students">
    <!-- นักเรียนเสี่ยงสูง -->
    <div class="section-header">
        <h2><i class="material-icons">person_off</i> นักเรียนที่ต้องติดตามเร่งด่วน</h2>
        <p>รายชื่อนักเรียนที่มีความเสี่ยงสูงในการตกกิจกรรม</p>
    </div>
    
    <!-- สรุปความเสี่ยง -->
    <div class="risk-summary">
        <div class="risk-card critical">
            <div class="risk-icon">
                <i class="material-icons">dangerous</i>
            </div>
            <div class="risk-content">
                <h3><?php echo $overview['critical_students']; ?></h3>
                <p>นักเรียนเสี่ยงวิกฤต</p>
                <span>< 70% ต้องแก้ไขทันที</span>
            </div>
        </div>
        
        <div class="risk-card high">
            <div class="risk-icon">
                <i class="material-icons">warning</i>
            </div>
            <div class="risk-content">
                <h3><?php echo $overview['risk_students']; ?></h3>
                <p>นักเรียนเสี่ยงสูง</p>
                <span>70-80% ต้องติดตาม</span>
            </div>
        </div>
        
        <div class="risk-card success">
            <div class="risk-icon">
                <i class="material-icons">check_circle</i>
            </div>
            <div class="risk-content">
                <h3><?php echo $overview['excellent_students']; ?></h3>
                <p>นักเรียนดีเยี่ยม</p>
                <span>> 90% เป็นแบบอย่าง</span>
            </div>
        </div>
    </div>
    
    <!-- รายชื่อนักเรียนเสี่ยง -->
    <div class="critical-students-list">
        <div class="list-header">
            <h3>รายชื่อนักเรียนเสี่ยงสูง (10 คนแรก)</h3>
            <div class="search-box">
                <i class="material-icons">search</i>
                <input type="text" placeholder="ค้นหานักเรียน...">
            </div>
        </div>
        
        <div class="students-table">
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>นักเรียน</th>
                        <th>ชั้นเรียน</th>
                        <th>อัตราเข้าแถว</th>
                        <th>ระดับเสี่ยง</th>
                        <th>ครูที่ปรึกษา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($critical_students as $student): ?>
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar"><?php echo $student['initial']; ?></div>
                                <div>
                                    <strong><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></strong>
                                    <br><small>รหัส: <?php echo $student['student_code']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $student['class_name']; ?></td>
                        <td>
                            <div class="attendance-display">
                                <span class="rate <?php echo $student['risk_level']; ?>">
                                    <?php echo $student['attendance_rate']; ?>%
                                </span>
                                <div class="mini-bar">
                                    <div class="fill <?php echo $student['risk_level']; ?>" 
                                         style="width: <?php echo $student['attendance_rate']; ?>%;"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="risk-badge <?php echo $student['risk_level']; ?>">
                                <?php echo $student['risk_text']; ?>
                            </span>
                        </td>
                        <td><?php echo $student['advisor_name'] ?: 'ไม่ระบุ'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-content" id="tab-reports">
    <!-- รายงานและการแจ้งเตือน -->
    <div class="section-header">
        <h2><i class="material-icons">assessment</i> รายงานและการแจ้งเตือน</h2>
        <p>สถิติการส่งรายงานและการแจ้งเตือนผู้ปกครอง</p>
    </div>
    
    <!-- สถิติการแจ้งเตือน -->
    <div class="notification-stats-grid">
        <div class="notification-card">
            <div class="notification-icon">
                <i class="material-icons">send</i>
            </div>
            <div class="notification-content">
                <h4><?php echo number_format($notification_stats['total_sent']); ?></h4>
                <p>การแจ้งเตือนทั้งหมด</p>
                <span>ในช่วง<?php echo $period == 'month' ? 'เดือนนี้' : 'ช่วงที่เลือก'; ?></span>
            </div>
        </div>
        
        <div class="notification-card success">
            <div class="notification-icon">
                <i class="material-icons">check_circle</i>
            </div>
            <div class="notification-content">
                <h4><?php echo $notification_stats['success_rate']; ?>%</h4>
                <p>อัตราการส่งสำเร็จ</p>
                <span><?php echo number_format($notification_stats['successful_sent']); ?> จาก <?php echo number_format($notification_stats['total_sent']); ?> ครั้ง</span>
            </div>
        </div>
        
        <div class="notification-card warning">
            <div class="notification-icon">
                <i class="material-icons">warning</i>
            </div>
            <div class="notification-content">
                <h4><?php echo number_format($notification_stats['risk_alerts']); ?></h4>
                <p>แจ้งเตือนความเสี่ยง</p>
                <span>ผู้ปกครองได้รับแจ้ง</span>
            </div>
        </div>
        
        <div class="notification-card info">
            <div class="notification-icon">
                <i class="material-icons">schedule</i>
            </div>
            <div class="notification-content">
                <h4><?php echo number_format($notification_stats['attendance_alerts']); ?></h4>
                <p>แจ้งเตือนการเข้าแถว</p>
                <span>รายงานประจำวัน</span>
            </div>
        </div>
    </div>
    
    <!-- เปรียบเทียบกับเป้าหมาย -->
    <div class="target-comparison-card">
        <div class="comparison-header">
            <h3><i class="material-icons">track_changes</i> ผลการดำเนินงานเทียบกับเป้าหมาย</h3>
        </div>
        
        <div class="comparison-content">
            <div class="target-meter">
                <div class="meter-container">
                    <div class="meter-bar">
                        <div class="meter-fill <?php echo $target_comparison['status']; ?>" 
                             style="width: <?php echo min($target_comparison['achievement_rate'], 100); ?>%;"></div>
                    </div>
                    <div class="meter-labels">
                        <span>0%</span>
                        <span>เป้าหมาย <?php echo $target_comparison['target']; ?>%</span>
                        <span>100%</span>
                    </div>
                </div>
                
                <div class="achievement-summary">
                    <div class="achievement-rate">
                        <h2><?php echo $target_comparison['achievement_rate']; ?>%</h2>
                        <p>ผลสำเร็จของเป้าหมาย</p>
                    </div>
                    
                    <div class="current-vs-target">
                        <div class="comparison-item">
                            <span>ปัจจุบัน:</span>
                            <strong class="<?php echo $target_comparison['status']; ?>">
                                <?php echo $target_comparison['current']; ?>%
                            </strong>
                        </div>
                        <div class="comparison-item">
                            <span>เป้าหมาย:</span>
                            <strong><?php echo $target_comparison['target']; ?>%</strong>
                        </div>
                        <div class="comparison-item">
                            <span>ส่วนต่าง:</span>
                            <strong class="<?php echo $target_comparison['difference'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $target_comparison['difference'] >= 0 ? '+' : ''; ?><?php echo $target_comparison['difference']; ?>%
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ปุ่มการดำเนินการ (ปิดสำหรับผู้บริหาร) -->
    <div class="action-buttons-disabled">
        <h4><i class="material-icons">info</i> หมายเหตุสำหรับผู้บริหาร</h4>
        <p>หน้านี้เป็นหน้าสำหรับการติดตามและวิเคราะห์ข้อมูลเท่านั้น การจัดการและแจ้งเตือนจะดำเนินการโดยเจ้าหน้าที่ที่รับผิดชอบ</p>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner-container">
        <div class="spinner"></div>
        <p>กำลังโหลดข้อมูล...</p>
    </div>
</div>

<!-- ส่งข้อมูลไปยัง JavaScript -->
<script>
// ข้อมูลสำหรับ Charts
const executiveData = {
    // ข้อมูลแนวโน้ม
    weeklyTrends: <?php echo json_encode($weekly_trends); ?>,
    
    // ข้อมูลสถานะการเข้าแถว
    attendanceStatus: <?php echo json_encode($attendance_status); ?>,
    
    // ข้อมูลประสิทธิภาพแผนก
    departmentPerformance: <?php echo json_encode($department_performance); ?>,
    
    // ข้อมูลประสิทธิภาพห้องเรียน
    classPerformance: <?php echo json_encode($class_performance); ?>,
    
    // ข้อมูลปีการศึกษา
    academicYear: {
        year: <?php echo $academic_year['year']; ?>,
        semester: <?php echo $current_semester; ?>,
        thaiYear: <?php echo $current_academic_year; ?>,
        currentMonth: '<?php echo $current_thai_month; ?>',
        currentYear: <?php echo $current_year; ?>
    },
    
    // ข้อมูลเป้าหมาย
    targetComparison: <?php echo json_encode($target_comparison); ?>,
    
    // ข้อมูลสถิติการแจ้งเตือน
    notificationStats: <?php echo json_encode($notification_stats); ?>
};

// ตัวแปรสำหรับการตั้งค่า
const executiveConfig = {
    isExecutiveMode: true,
    canEdit: false,
    canSendNotifications: false,
    refreshInterval: 300000 // 5 นาที
};
</script>
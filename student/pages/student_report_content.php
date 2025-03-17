<div class="header">
        <a href="#" onclick="goBack()" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>ประวัติการเช็คชื่อ</h1>
        <div class="header-icon">
            <span class="material-icons">notifications</span>
        </div>
    </div>

<div class="container">
    <!-- โปรไฟล์ -->
    <div class="profile-card">
        <div class="profile-image">
            <span><?php echo $student_info['avatar']; ?></span>
        </div>
        <div class="profile-info">
            <h2><?php echo $student_info['name']; ?></h2>
            <p><?php echo $student_info['class'] . ' เลขที่ ' . $student_info['number']; ?></p>
        </div>
    </div>

    <!-- การ์ดสรุปข้อมูล -->
    <div class="summary-card">
        <div class="summary-title">
            <span>สรุปการเข้าแถวเดือนมีนาคม</span>
            <span class="material-icons">equalizer</span>
        </div>
        
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $monthly_summary['total_days']; ?></div>
                <div class="stat-label">วันเข้าแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $monthly_summary['absent_days']; ?></div>
                <div class="stat-label">วันขาดแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value good"><?php echo $monthly_summary['attendance_percentage']; ?>%</div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
        
        <div class="progress-container">
            <div class="progress-label">
                <span>ความสม่ำเสมอ</span>
                <span>ดีเยี่ยม</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $monthly_summary['regularity_score']; ?>%;"></div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-bars">
                <?php foreach ($attendance_chart as $month): ?>
                    <div class="bar" style="height: <?php echo $month['percentage']; ?>%;">
                        <div class="bar-label"><?php echo $month['month']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-axis"></div>
        </div>
    </div>

    <!-- แท็บเมนู -->
    <div class="tab-menu">
        <div class="tab-item active" onclick="switchTab('calendar')">ปฏิทิน</div>
        <div class="tab-item" onclick="switchTab('history')">ประวัติ</div>
    </div>

    <!-- ปฏิทินการเข้าแถว -->
    <div class="calendar-card" id="calendar-tab">
        <div class="calendar-header">
            <div class="calendar-title">ปฏิทินการเข้าแถว</div>
            <div class="calendar-nav">
                <button class="nav-button" onclick="prevMonth()">
                    <span class="material-icons">chevron_left</span>
                </button>
                <div class="calendar-month">มีนาคม 2025</div>
                <button class="nav-button" onclick="nextMonth()">
                    <span class="material-icons">chevron_right</span>
                </button>
            </div>
        </div>
        
        <div class="calendar-days">
            <div class="day-label">อา</div>
            <div class="day-label">จ</div>
            <div class="day-label">อ</div>
            <div class="day-label">พ</div>
            <div class="day-label">พฤ</div>
            <div class="day-label">ศ</div>
            <div class="day-label">ส</div>
        </div>
        
        <div class="calendar-dates">
            <!-- The calendar dates would be dynamically generated based on actual attendance data -->
            <div class="date-cell other-month">23</div>
            <div class="date-cell other-month">24</div>
            <div class="date-cell other-month">25</div>
            <div class="date-cell other-month">26</div>
            <div class="date-cell other-month">27</div>
            <div class="date-cell other-month">28</div>
            <div class="date-cell">1</div>
            
            <div class="date-cell">2</div>
            <div class="date-cell present">3</div>
            <div class="date-cell present">4</div>
            <div class="date-cell present">5</div>
            <div class="date-cell present">6</div>
            <div class="date-cell present">7</div>
            <div class="date-cell">8</div>
            
            <div class="date-cell">9</div>
            <div class="date-cell present">10</div>
            <div class="date-cell present">11</div>
            <div class="date-cell present">12</div>
            <div class="date-cell present">13</div>
            <div class="date-cell present">14</div>
            <div class="date-cell">15</div>
            
            <div class="date-cell">16</div>
            <div class="date-cell today present">17</div>
            <div class="date-cell">18</div>
            <div class="date-cell">19</div>
            <div class="date-cell">20</div>
            <div class="date-cell">21</div>
            <div class="date-cell">22</div>
            
            <div class="date-cell">23</div>
            <div class="date-cell">24</div>
            <div class="date-cell">25</div>
            <div class="date-cell">26</div>
            <div class="date-cell">27</div>
            <div class="date-cell">28</div>
            <div class="date-cell">29</div>
            
            <div class="date-cell">30</div>
            <div class="date-cell">31</div>
            <div class="date-cell other-month">1</div>
            <div class="date-cell other-month">2</div>
            <div class="date-cell other-month">3</div>
            <div class="date-cell other-month">4</div>
            <div class="date-cell other-month">5</div>
        </div>
    </div>

    <!-- ประวัติการเข้าแถว -->
    <div class="history-card" id="history-tab" style="display: none;">
        <div class="history-title">
            <span>ประวัติการเข้าแถว</span>
            <button class="filter-button">
                <span class="material-icons">filter_list</span>
                กรอง
            </button>
        </div>
        
        <div class="history-list">
            <?php foreach ($check_in_history as $entry): ?>
                <div class="history-item">
                    <div class="history-date">
                        <div class="history-day"><?php echo substr($entry['date'], 0, 2); ?></div>
                        <div class="history-month"><?php echo substr($entry['date'], 3); ?></div>
                    </div>
                    <div class="history-details">
                        <div class="history-status">
                            <div class="status-indicator <?php echo $entry['status']; ?>"></div>
                            <div class="status-text <?php echo $entry['status']; ?>">
                                <?php echo $entry['status'] === 'present' ? 'มาเรียน' : 'ขาดเรียน'; ?>
                            </div>
                        </div>
                        <div class="history-time">เช็คชื่อเวลา <?php echo $entry['time']; ?> น.</div>
                        <div class="history-method">
                            <span class="material-icons">
                                <?php 
                                switch ($entry['method']) {
                                    case 'GPS': echo 'gps_fixed'; break;
                                    case 'PIN': echo 'pin'; break;
                                    case 'QR Code': echo 'qr_code_scanner'; break;
                                    default: echo 'check_circle';
                                } 
                                ?>
                            </span>
                            เช็คชื่อด้วย <?php echo $entry['method']; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // สลับแท็บ
    function switchTab(tabName) {
        const calendarTab = document.getElementById('calendar-tab');
        const historyTab = document.getElementById('history-tab');
        const calendarTabItem = document.querySelector('.tab-item:first-child');
        const historyTabItem = document.querySelector('.tab-item:last-child');

        if (tabName === 'calendar') {
            calendarTab.style.display = 'block';
            historyTab.style.display = 'none';
            calendarTabItem.classList.add('active');
            historyTabItem.classList.remove('active');
        } else {
            calendarTab.style.display = 'none';
            historyTab.style.display = 'block';
            calendarTabItem.classList.remove('active');
            historyTabItem.classList.add('active');
        }
    }

    // ย้อนกลับ
    function goBack() {
        history.back();
    }

    // การนำทางเดือน (ตัวอย่าง)
    function prevMonth() {
        alert('ดึงข้อมูลเดือนก่อนหน้า');
    }

    function nextMonth() {
        alert('ดึงข้อมูลเดือนถัดไป');
    }
</script>
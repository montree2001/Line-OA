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
        <?php if (!empty($student_info['profile_image'])): ?>
        <div class="profile-image profile-photo">
            <img src="<?php echo $student_info['profile_image']; ?>" alt="<?php echo $student_info['name']; ?>">
        </div>
        <?php else: ?>
        <div class="profile-image">
            <span><?php echo $student_info['avatar']; ?></span>
        </div>
        <?php endif; ?>
        <div class="profile-info">
            <h2><?php echo $student_info['name']; ?></h2>
            <p><?php echo $student_info['class'] . ' เลขที่ ' . $student_info['number']; ?></p>
        </div>
    </div>

    <!-- การ์ดสรุปข้อมูล -->
    <div class="summary-card">
        <div class="summary-title">
            <span>สรุปการเข้าแถวเดือน<?php echo $current_month_name ?? 'มีนาคม'; ?></span>
            <span class="material-icons">equalizer</span>
        </div>
        
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $monthly_summary['total_days']; ?></div>
                <div class="stat-label">วันเข้าแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value <?php echo $monthly_summary['absent_days'] > 0 ? 'warning' : ''; ?>"><?php echo $monthly_summary['absent_days']; ?></div>
                <div class="stat-label">วันขาดแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $monthly_summary['required_days']; ?></div>
                <div class="stat-label">วันที่ต้องเข้าแถว</div>
            </div>
            <div class="stat-box">
                <?php 
                $attendance_percentage = $monthly_summary['attendance_percentage'];
                $status_class = '';
                if ($attendance_percentage >= 90) {
                    $status_class = 'good';
                } elseif ($attendance_percentage >= 80) {
                    $status_class = 'warning';
                } else {
                    $status_class = 'danger';
                }
                ?>
                <div class="stat-value <?php echo $status_class; ?>"><?php echo $attendance_percentage; ?>%</div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
        
        <div class="progress-container">
            <div class="progress-label">
                <span>ความสม่ำเสมอ</span>
                <?php 
                $regularity = $monthly_summary['regularity_score'];
                $regularity_text = 'พอใช้';
                if ($regularity >= 90) {
                    $regularity_text = 'ดีเยี่ยม';
                } elseif ($regularity >= 80) {
                    $regularity_text = 'ดี';
                } elseif ($regularity >= 70) {
                    $regularity_text = 'พอใช้';
                } else {
                    $regularity_text = 'ต้องปรับปรุง';
                }
                ?>
                <span><?php echo $regularity_text; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $regularity; ?>%;"></div>
            </div>
        </div>
        
        <?php if (isset($monthly_summary['is_at_risk']) && $monthly_summary['is_at_risk']): ?>
<div class="risk-alert">
    <div class="risk-icon">
        <span class="material-icons">warning</span>
    </div>
    <div class="risk-message">
        <div class="risk-title">เสี่ยงตกกิจกรรม</div>
        <div class="risk-description">
            อัตราการเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด (<?php echo $monthly_summary['min_percentage']; ?>%)
        </div>
    </div>
</div>
<?php endif; ?>
        
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
                <div class="calendar-month"><?php echo $current_month_year; ?></div>
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
            <?php foreach ($calendar_dates as $date): ?>
                <div class="date-cell <?php echo $date['status'] . ' ' . ($date['is_today'] ?? ''); ?>">
                    <?php echo $date['day']; ?>
                    <?php if (isset($date['status']) && $date['status'] === 'absent'): ?>
                        <div class="status-dot absent"></div>
                    <?php elseif (isset($date['status']) && $date['status'] === 'present'): ?>
                        <div class="status-dot present"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ประวัติการเข้าแถว -->
    <div class="history-card" id="history-tab" style="display: none;">
        <div class="history-title">
            <span>ประวัติการเข้าแถว</span>
            <button class="filter-button" onclick="showFilterOptions()">
                <span class="material-icons">filter_list</span>
                กรอง
            </button>
        </div>
        
        <div class="history-list">
            <?php if (empty($check_in_history)): ?>
                <div class="no-history">
                    <div class="no-data-icon">
                        <span class="material-icons">event_busy</span>
                    </div>
                    <div class="no-data-message">ไม่พบประวัติการเช็คชื่อในขณะนี้</div>
                </div>
            <?php else: ?>
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
                                    <?php echo $entry['status'] === 'present' ? 'เข้าแถว' : 'ขาดแถว'; ?>
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
            <?php endif; ?>
        </div>
    </div>

    <!-- ตัวกรองข้อมูลรายงาน -->
    <div class="filter-modal" id="filterModal">
        <div class="filter-modal-content">
            <div class="filter-header">
                <h3>ตัวกรองข้อมูล</h3>
                <span class="material-icons close-filter" onclick="hideFilterOptions()">close</span>
            </div>
            <div class="filter-body">
                <div class="filter-section">
                    <h4>สถานะ</h4>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="status" value="all" checked> ทั้งหมด
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="status" value="present"> เข้าแถว
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="status" value="absent"> ขาดแถว
                        </label>
                    </div>
                </div>
                <div class="filter-section">
                    <h4>วิธีการเช็คชื่อ</h4>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="method" value="all" checked> ทั้งหมด
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="method" value="GPS"> GPS
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="method" value="PIN"> PIN
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="method" value="QR_Code"> QR Code
                        </label>
                    </div>
                </div>
                <div class="filter-section">
                    <h4>ช่วงเวลา</h4>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="period" value="all" checked> ทั้งหมด
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="period" value="this_month"> เดือนนี้
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="period" value="last_month"> เดือนที่แล้ว
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="period" value="three_months"> 3 เดือนล่าสุด
                        </label>
                    </div>
                </div>
            </div>
            <div class="filter-footer">
                <button class="reset-btn" onclick="resetFilter()">รีเซ็ต</button>
                <button class="apply-btn" onclick="applyFilter()">ค้นหา</button>
            </div>
        </div>
    </div>
</div>

<!-- พื้นที่ว่างสำหรับรองรับ bottom_nav -->
<div style="height: 70px;"></div>

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

    // การนำทางเดือน
    function prevMonth() {
        // ในระบบจริงจะส่ง request ไปดึงข้อมูลเดือนก่อนหน้า
        window.location.href = 'history.php?month=' + (new Date().getMonth()) + '&year=' + new Date().getFullYear();
    }

    function nextMonth() {
        // ในระบบจริงจะส่ง request ไปดึงข้อมูลเดือนถัดไป
        window.location.href = 'history.php?month=' + (new Date().getMonth() + 2) + '&year=' + new Date().getFullYear();
    }
    
    // ฟังก์ชันสำหรับตัวกรอง
    function showFilterOptions() {
        document.getElementById('filterModal').style.display = 'block';
    }
    
    function hideFilterOptions() {
        document.getElementById('filterModal').style.display = 'none';
    }
    
    function resetFilter() {
        // รีเซ็ตค่าทุกอันเป็นค่าเริ่มต้น
        document.querySelectorAll('input[name="status"][value="all"]')[0].checked = true;
        document.querySelectorAll('input[name="method"][value="all"]')[0].checked = true;
        document.querySelectorAll('input[name="period"][value="all"]')[0].checked = true;
    }
    
    function applyFilter() {
        // ดึงค่าตัวกรองที่เลือก
        const status = document.querySelector('input[name="status"]:checked').value;
        const method = document.querySelector('input[name="method"]:checked').value;
        const period = document.querySelector('input[name="period"]:checked').value;
        
        // ส่ง request ไปยัง API เพื่อกรองข้อมูล
        window.location.href = 'history.php?status=' + status + '&method=' + method + '&period=' + period;
        
        // ปิดหน้าต่างตัวกรอง
        hideFilterOptions();
    }
</script>

<style>
/* เพิ่มสไตล์ให้กับหน้า history */
.risk-alert {
    display: flex;
    align-items: center;
    background-color: #fff3e0;
    border-radius: 8px;
    padding: 12px;
    margin-top: 15px;
    border-left: 4px solid #ff9800;
}

.risk-icon {
    margin-right: 12px;
}

.risk-icon .material-icons {
    color: #ff9800;
    font-size: 24px;
}

.risk-title {
    font-weight: bold;
    margin-bottom: 4px;
    color: #e65100;
}

.risk-description {
    font-size: 12px;
    color: #666;
}

.profile-photo {
    overflow: hidden;
}

.profile-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-history {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    color: #999;
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #ccc;
}

.no-data-message {
    font-size: 14px;
}

/* สไตล์สำหรับตัวกรอง */
.filter-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.filter-modal-content {
    background-color: white;
    margin: 20% auto;
    width: 90%;
    max-width: 400px;
    border-radius: 10px;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.filter-header h3 {
    margin: 0;
    font-size: 18px;
}

.close-filter {
    cursor: pointer;
}

.filter-body {
    padding: 15px;
    max-height: 60vh;
    overflow-y: auto;
}

.filter-section {
    margin-bottom: 20px;
}

.filter-section h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.filter-option input {
    margin-right: 8px;
}

.filter-footer {
    display: flex;
    justify-content: flex-end;
    padding: 15px;
    border-top: 1px solid #f0f0f0;
    gap: 10px;
}

.reset-btn {
    background-color: #f5f5f5;
    color: #333;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
}

.apply-btn {
    background-color: #06c755;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
}

/* ปรับ grid layout สำหรับ summary-stats */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

@media (max-width: 360px) {
    .summary-stats {
        grid-template-columns: repeat(1, 1fr);
    }
}
</style>
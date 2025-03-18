<!-- แจ้งเตือน -->
<div class="notification-banner">
    <span class="material-icons icon">calendar_today</span>
    <div class="content">
        <div class="title">รายงานการเข้าแถว</div>
        <div class="message">ภาคเรียนที่ 2 ปีการศึกษา <?php echo date('Y') + 543 - 1; ?></div>
    </div>
</div>

<!-- แท็บเมนู -->
<div class="tab-menu">
    <button class="tab-button" onclick="switchTab('overview')">ภาพรวม</button>
    <button class="tab-button active" onclick="switchTab('attendance')">การเข้าแถว</button>
    <button class="tab-button" onclick="switchTab('news')">ข่าวสาร</button>
</div>

<!-- ตัวกรองข้อมูล -->
<div class="filter-section">
    <div class="filter-card">
        <div class="filter-header">
            <h3>ตัวกรองข้อมูล</h3>
        </div>
        <div class="filter-form">
            <div class="form-group">
                <label for="student-select">นักเรียน</label>
                <select id="student-select" class="form-control">
                    <option value="all">ทั้งหมด</option>
                    <?php if(isset($students) && !empty($students)): ?>
                        <?php foreach($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">นายเอกชัย รักเรียน</option>
                        <option value="2">นางสาวสมหญิง รักเรียน</option>
                        <option value="3">เด็กชายธนกฤต รักเรียน</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="month-select">เดือน</label>
                <select id="month-select" class="form-control">
                    <option value="all">ทั้งหมด</option>
                    <option value="1">มกราคม</option>
                    <option value="2">กุมภาพันธ์</option>
                    <option value="3" selected>มีนาคม</option>
                    <option value="4">เมษายน</option>
                    <option value="5">พฤษภาคม</option>
                    <option value="6">มิถุนายน</option>
                    <option value="7">กรกฎาคม</option>
                    <option value="8">สิงหาคม</option>
                    <option value="9">กันยายน</option>
                    <option value="10">ตุลาคม</option>
                    <option value="11">พฤศจิกายน</option>
                    <option value="12">ธันวาคม</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status-select">สถานะ</label>
                <select id="status-select" class="form-control">
                    <option value="all">ทั้งหมด</option>
                    <option value="present">มาเรียน</option>
                    <option value="absent">ขาดเรียน</option>
                </select>
            </div>
            <button class="filter-button" onclick="applyFilters()">
                <span class="material-icons">filter_list</span> กรอง
            </button>
        </div>
    </div>
</div>

<!-- สรุปการเข้าแถว -->
<div class="attendance-summary">
    <div class="section-header">
        <h2>สรุปการเข้าแถว</h2>
        <div class="date-range">เดือนมีนาคม 2568</div>
    </div>
    
    <div class="summary-card">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value"><?php echo isset($summary['total_days']) ? $summary['total_days'] : '23'; ?></div>
                <div class="summary-label">วันเรียนทั้งหมด</div>
            </div>
            <div class="summary-item">
                <div class="summary-value present"><?php echo isset($summary['present_days']) ? $summary['present_days'] : '22'; ?></div>
                <div class="summary-label">วันที่มาเรียน</div>
            </div>
            <div class="summary-item">
                <div class="summary-value absent"><?php echo isset($summary['absent_days']) ? $summary['absent_days'] : '1'; ?></div>
                <div class="summary-label">วันที่ขาดเรียน</div>
            </div>
            <div class="summary-item">
                <div class="summary-value percentage"><?php echo isset($summary['attendance_percentage']) ? $summary['attendance_percentage'] : '95.7'; ?>%</div>
                <div class="summary-label">อัตราการเข้าแถว</div>
            </div>
        </div>
        
        <!-- กราฟแสดงสถิติการเข้าแถวรายเดือน -->
        <div class="attendance-chart">
            <h3 class="chart-title">สถิติการเข้าแถวรายเดือน</h3>
            <div class="chart-container">
                <div class="chart-bars">
                    <div class="chart-bar" style="height: 90%;" data-percentage="90%">
                        <div class="bar-label">ม.ค.</div>
                    </div>
                    <div class="chart-bar" style="height: 95%;" data-percentage="95%">
                        <div class="bar-label">ก.พ.</div>
                    </div>
                    <div class="chart-bar active" style="height: 96%;" data-percentage="96%">
                        <div class="bar-label">มี.ค.</div>
                    </div>
                    <div class="chart-bar" style="height: 0%;" data-percentage="0%">
                        <div class="bar-label">เม.ย.</div>
                    </div>
                    <div class="chart-bar" style="height: 0%;" data-percentage="0%">
                        <div class="bar-label">พ.ค.</div>
                    </div>
                    <div class="chart-bar" style="height: 0%;" data-percentage="0%">
                        <div class="bar-label">มิ.ย.</div>
                    </div>
                </div>
                <div class="chart-axis"></div>
            </div>
        </div>
    </div>
</div>

<!-- ปฏิทินการเข้าแถว -->
<div class="attendance-calendar">
    <div class="section-header">
        <h2>ปฏิทินการเข้าแถว</h2>
        <div class="calendar-navigation">
            <button class="nav-button" onclick="prevMonth()">
                <span class="material-icons">chevron_left</span>
            </button>
            <div class="current-month">มีนาคม 2568</div>
            <button class="nav-button" onclick="nextMonth()">
                <span class="material-icons">chevron_right</span>
            </button>
        </div>
    </div>
    
    <div class="calendar-card">
        <div class="calendar-weekdays">
            <div class="weekday">อา</div>
            <div class="weekday">จ</div>
            <div class="weekday">อ</div>
            <div class="weekday">พ</div>
            <div class="weekday">พฤ</div>
            <div class="weekday">ศ</div>
            <div class="weekday">ส</div>
        </div>
        
        <div class="calendar-dates">
            <?php 
            // ในงานจริงควรมีการคำนวณวันที่จริงๆ
            $days_in_month = 31; // มีนาคม มี 31 วัน
            $first_day_of_month = 6; // วันที่ 1 ของเดือนตรงกับวันอาทิตย์ (0=อาทิตย์, 6=เสาร์)
            $total_cells = $first_day_of_month + $days_in_month;
            
            // แสดงวันสุดท้ายของเดือนก่อนหน้า
            for ($i = 0; $i < $first_day_of_month; $i++) {
                echo '<div class="date-cell other-month"></div>';
            }
            
            // แสดงวันที่ในเดือนปัจจุบัน
            for ($day = 1; $day <= $days_in_month; $day++) {
                $class = '';
                
                // วันที่ 17 เป็นวันปัจจุบัน
                if ($day == 17) {
                    $class .= ' today';
                }
                
                // กำหนดวันที่มีการเข้าแถวและขาดเรียน (ตัวอย่าง)
                if ($day <= 15 && $day != 5 && $day != 6 && $day != 12 && $day != 13) {
                    $class .= ' present';
                } else if ($day == 16) {
                    $class .= ' absent';
                }
                
                // วันเสาร์-อาทิตย์
                if (($first_day_of_month + $day - 1) % 7 == 0 || ($first_day_of_month + $day - 1) % 7 == 6) {
                    $class .= ' weekend';
                }
                
                echo "<div class=\"date-cell$class\">$day</div>";
            }
            
            // เติมช่องว่างหลังวันสุดท้ายของเดือน
            $remaining_cells = 7 - ($total_cells % 7);
            if ($remaining_cells < 7) {
                for ($i = 0; $i < $remaining_cells; $i++) {
                    echo '<div class="date-cell other-month"></div>';
                }
            }
            ?>
        </div>
        
        <div class="calendar-legend">
            <div class="legend-item">
                <div class="legend-color present"></div>
                <div class="legend-text">มาเรียน</div>
            </div>
            <div class="legend-item">
                <div class="legend-color absent"></div>
                <div class="legend-text">ขาดเรียน</div>
            </div>
            <div class="legend-item">
                <div class="legend-color today-marker"></div>
                <div class="legend-text">วันนี้</div>
            </div>
        </div>
    </div>
</div>

<!-- ประวัติการเข้าแถว -->
<div class="attendance-history">
    <div class="section-header">
        <h2>ประวัติการเข้าแถว</h2>
        <button class="export-button" onclick="exportData()">
            <span class="material-icons">file_download</span> ส่งออก
        </button>
    </div>
    
    <div class="history-card">
        <div class="history-list">
            <?php if(isset($attendance_history) && !empty($attendance_history)): ?>
                <?php foreach($attendance_history as $entry): ?>
                    <div class="history-item">
                        <div class="history-date">
                            <div class="history-day"><?php echo substr($entry['date'], 0, 2); ?></div>
                            <div class="history-month"><?php echo substr($entry['date'], 3); ?></div>
                        </div>
                        <div class="history-details">
                            <div class="student-info">
                                <div class="student-avatar"><?php echo $entry['avatar']; ?></div>
                                <div class="student-name"><?php echo $entry['student_name']; ?></div>
                            </div>
                            <div class="history-status <?php echo $entry['status']; ?>">
                                <span class="material-icons"><?php echo $entry['status'] == 'present' ? 'check_circle' : 'cancel'; ?></span>
                                <?php echo $entry['status'] == 'present' ? 'มาเรียน' : 'ขาดเรียน'; ?>
                            </div>
                            <?php if($entry['status'] == 'present'): ?>
                                <div class="history-time">
                                    <span class="material-icons">access_time</span>
                                    เช็คชื่อเวลา <?php echo $entry['time']; ?> น.
                                </div>
                                <div class="history-method">
                                    <span class="material-icons"><?php echo $entry['method_icon']; ?></span>
                                    <?php echo $entry['method']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- ข้อมูลตัวอย่าง -->
                <div class="history-item">
                    <div class="history-date">
                        <div class="history-day">17</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-details">
                        <div class="student-info">
                            <div class="student-avatar">อ</div>
                            <div class="student-name">นายเอกชัย รักเรียน</div>
                        </div>
                        <div class="history-status present">
                            <span class="material-icons">check_circle</span>
                            มาเรียน
                        </div>
                        <div class="history-time">
                            <span class="material-icons">access_time</span>
                            เช็คชื่อเวลา 07:45 น.
                        </div>
                        <div class="history-method">
                            <span class="material-icons">gps_fixed</span>
                            GPS
                        </div>
                    </div>
                </div>
                
                <div class="history-item">
                    <div class="history-date">
                        <div class="history-day">17</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-details">
                        <div class="student-info">
                            <div class="student-avatar">ส</div>
                            <div class="student-name">นางสาวสมหญิง รักเรียน</div>
                        </div>
                        <div class="history-status present">
                            <span class="material-icons">check_circle</span>
                            มาเรียน
                        </div>
                        <div class="history-time">
                            <span class="material-icons">access_time</span>
                            เช็คชื่อเวลา 07:40 น.
                        </div>
                        <div class="history-method">
                            <span class="material-icons">qr_code_scanner</span>
                            QR Code
                        </div>
                    </div>
                </div>
                
                <div class="history-item">
                    <div class="history-date">
                        <div class="history-day">16</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-details">
                        <div class="student-info">
                            <div class="student-avatar">อ</div>
                            <div class="student-name">นายเอกชัย รักเรียน</div>
                        </div>
                        <div class="history-status absent">
                            <span class="material-icons">cancel</span>
                            ขาดเรียน
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript สำหรับหน้า Attendance -->
<script>
function applyFilters() {
    const student = document.getElementById('student-select').value;
    const month = document.getElementById('month-select').value;
    const status = document.getElementById('status-select').value;
    
    console.log(`กรองข้อมูล: นักเรียน=${student}, เดือน=${month}, สถานะ=${status}`);
    // ในการใช้งานจริงควรมีการดึงข้อมูลตามเงื่อนไขและอัพเดทหน้าจอ
    alert('กำลังกรองข้อมูลตามเงื่อนไขที่เลือก');
}

function prevMonth() {
    // ในการใช้งานจริงควรมีการอัพเดทปฏิทินเป็นเดือนก่อนหน้า
    alert('กำลังแสดงข้อมูลเดือนก่อนหน้า');
}

function nextMonth() {
    // ในการใช้งานจริงควรมีการอัพเดทปฏิทินเป็นเดือนถัดไป
    alert('กำลังแสดงข้อมูลเดือนถัดไป');
}

function exportData() {
    // ในการใช้งานจริงควรมีการส่งออกข้อมูลเป็นไฟล์ CSV หรือ PDF
    alert('กำลังส่งออกข้อมูลการเข้าแถว');
}
</script>

<!-- เพิ่ม CSS เฉพาะสำหรับหน้า Attendance -->
<style>
/* ตัวกรองข้อมูล */
.filter-section {
    margin-bottom: 20px;
}

.filter-card {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--card-shadow);
}

.filter-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-size: 14px;
    margin-bottom: 5px;
    color: var(--text-light);
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    background-color: #f9f9f9;
}


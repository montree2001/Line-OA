<!-- ส่วนหัว -->
<div class="header-navigation">
    <a href="students.php" class="back-button">
        <span class="material-icons">arrow_back</span>
    </a>
    <h2 class="page-title">ข้อมูลนักเรียน</h2>
</div>

<!-- แถบนักเรียน -->
<div class="students-bar">
    <?php if(isset($students) && !empty($students)): ?>
        <?php foreach($students as $student): ?>
            <div class="student-pill <?php echo ($student['id'] == $selected_student['id']) ? 'active' : ''; ?>" 
                 onclick="window.location.href='students.php?id=<?php echo $student['id']; ?>'">
                <div class="student-pill-avatar"><?php echo $student['avatar']; ?></div>
                <div class="student-pill-name"><?php echo explode(' ', $student['name'])[0]; ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ข้อมูลนักเรียน -->
<div class="student-profile">
    <div class="profile-header">
        <div class="student-avatar"><?php echo $selected_student['avatar']; ?></div>
        <div class="student-info">
            <div class="student-name"><?php echo $selected_student['name']; ?></div>
            <div class="student-details"><?php echo $selected_student['class']; ?> เลขที่ <?php echo $selected_student['number']; ?></div>
            <div class="student-details">รหัสนักเรียน: <?php echo $selected_student['student_id']; ?></div>
            <div class="student-status <?php echo $selected_student['present'] ? 'present' : 'absent'; ?>">
                <span class="material-icons" style="font-size: 14px; margin-right: 5px;">
                    <?php echo $selected_student['present'] ? 'check_circle' : 'cancel'; ?>
                </span>
                <?php 
                    if($selected_student['present']) {
                        echo 'เข้าแถววันนี้ เวลา ' . $selected_student['check_in_time'] . ' น.';
                    } else {
                        echo 'ขาดเรียนวันนี้';
                    }
                ?>
            </div>
        </div>
    </div>
    
    <div class="profile-actions">
        <button class="profile-action-button" onclick="window.location.href='attendance_history.php?id=<?php echo $selected_student['id']; ?>'">
            <span class="material-icons">history</span>
            ประวัติการเข้าแถว
        </button>
        <button class="profile-action-button">
            <span class="material-icons">note</span>
            ข้อมูลการเรียน
        </button>
        <button class="profile-action-button">
            <span class="material-icons">contact_page</span>
            ข้อมูลส่วนตัว
        </button>
    </div>
</div>

<!-- สถิติการเข้าแถว -->
<div class="stats-card">
    <div class="stats-header">
        <div class="stats-title">สถิติการเข้าแถว</div>
        <div class="stats-term">ภาคเรียนที่ 2/<?php echo date('Y') + 543 - 1; ?></div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-value"><?php echo $selected_student['attendance_days'] + $selected_student['absent_days']; ?></div>
            <div class="stat-label">วันเรียนทั้งหมด</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $selected_student['attendance_days']; ?></div>
            <div class="stat-label">วันเข้าแถว</div>
        </div>
        <div class="stat-item">
            <div class="stat-value <?php echo $selected_student['attendance_percentage'] >= 90 ? 'good' : ($selected_student['attendance_percentage'] >= 80 ? 'warning' : 'danger'); ?>">
                <?php echo number_format($selected_student['attendance_percentage'], 1); ?>%
            </div>
            <div class="stat-label">อัตราการเข้าแถว</div>
        </div>
    </div>
    
    <div class="stats-progress">
        <div class="progress-label">
            <span class="progress-text">ความสม่ำเสมอในการเข้าแถว</span>
            <span class="progress-value">
                <?php 
                if($selected_student['attendance_percentage'] >= 95) {
                    echo 'ยอดเยี่ยม';
                } elseif($selected_student['attendance_percentage'] >= 90) {
                    echo 'ดีมาก';
                } elseif($selected_student['attendance_percentage'] >= 85) {
                    echo 'ดี';
                } elseif($selected_student['attendance_percentage'] >= 80) {
                    echo 'พอใช้';
                } else {
                    echo 'ต้องปรับปรุง';
                }
                ?>
            </span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo min(100, $selected_student['attendance_percentage']); ?>%;"></div>
        </div>
    </div>
    
    <div class="chart-container">
        <div class="chart-bars">
            <?php if(isset($selected_student['monthly_data']) && !empty($selected_student['monthly_data'])): ?>
                <?php foreach($selected_student['monthly_data'] as $month_data): ?>
                    <div class="chart-bar" style="height: <?php echo $month_data['percentage']; ?>%;">
                        <div class="chart-bar-label"><?php echo $month_data['month']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- ข้อมูลตัวอย่าง -->
                <div class="chart-bar" style="height: 80%;">
                    <div class="chart-bar-label">ต.ค.</div>
                </div>
                <div class="chart-bar" style="height: 90%;">
                    <div class="chart-bar-label">พ.ย.</div>
                </div>
                <div class="chart-bar" style="height: 100%;">
                    <div class="chart-bar-label">ธ.ค.</div>
                </div>
                <div class="chart-bar" style="height: 95%;">
                    <div class="chart-bar-label">ม.ค.</div>
                </div>
                <div class="chart-bar" style="height: 97%;">
                    <div class="chart-bar-label">ก.พ.</div>
                </div>
                <div class="chart-bar" style="height: 100%;">
                    <div class="chart-bar-label">มี.ค.</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="chart-axis"></div>
    </div>
</div>

<!-- ประวัติการเข้าแถว -->
<div class="attendance-card">
    <div class="attendance-header">
        <div class="attendance-title">ประวัติการเข้าแถว</div>
        <a href="attendance_history.php?id=<?php echo $selected_student['id']; ?>" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <div class="attendance-filters">
        <button class="filter-button active" onclick="filterAttendance('all')">ทั้งหมด</button>
        <button class="filter-button" onclick="filterAttendance('month')">เดือนนี้</button>
        <button class="filter-button" onclick="filterAttendance('week')">สัปดาห์นี้</button>
        <button class="filter-button" onclick="filterAttendance('present')">มาเรียน</button>
        <button class="filter-button" onclick="filterAttendance('absent')">ขาดเรียน</button>
    </div>
    
    <div class="attendance-list">
        <?php if(isset($selected_student['attendance_history']) && !empty($selected_student['attendance_history'])): ?>
            <?php foreach(array_slice($selected_student['attendance_history'], 0, 5) as $entry): ?>
                <div class="attendance-item">
                    <div class="attendance-date">
                        <div class="attendance-day"><?php echo $entry['day']; ?></div>
                        <div class="attendance-month"><?php echo $entry['month_short']; ?></div>
                    </div>
                    <div class="attendance-details">
                        <div class="attendance-status">
                            <div class="status-dot <?php echo $entry['present'] ? 'present' : 'absent'; ?>"></div>
                            <div class="status-text <?php echo $entry['present'] ? 'present' : 'absent'; ?>">
                                <?php echo $entry['present'] ? 'มาเรียน' : 'ขาดเรียน'; ?>
                            </div>
                        </div>
                        <?php if($entry['present']): ?>
                            <div class="attendance-time">
                                เช็คชื่อเวลา <?php echo $entry['time']; ?> น.
                            </div>
                            <div class="attendance-method">
                                <span class="material-icons"><?php echo $entry['method_icon']; ?></span>
                                เช็คชื่อผ่าน <?php echo $entry['method']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- ข้อมูลตัวอย่าง -->
            <div class="attendance-item">
                <div class="attendance-date">
                    <div class="attendance-day">16</div>
                    <div class="attendance-month">มี.ค.</div>
                </div>
                <div class="attendance-details">
                    <div class="attendance-status">
                        <div class="status-dot present"></div>
                        <div class="status-text present">มาเรียน</div>
                    </div>
                    <div class="attendance-time">เช็คชื่อเวลา 07:45 น.</div>
                    <div class="attendance-method">
                        <span class="material-icons">gps_fixed</span>
                        เช็คชื่อผ่าน GPS
                    </div>
                </div>
            </div>
            
            <div class="attendance-item">
                <div class="attendance-date">
                    <div class="attendance-day">15</div>
                    <div class="attendance-month">มี.ค.</div>
                </div>
                <div class="attendance-details">
                    <div class="attendance-status">
                        <div class="status-dot present"></div>
                        <div class="status-text present">มาเรียน</div>
                    </div>
                    <div class="attendance-time">เช็คชื่อเวลา 07:40 น.</div>
                    <div class="attendance-method">
                        <span class="material-icons">pin</span>
                        เช็คชื่อด้วยรหัส PIN
                    </div>
                </div>
            </div>
            
            <div class="attendance-item">
                <div class="attendance-date">
                    <div class="attendance-day">14</div>
                    <div class="attendance-month">มี.ค.</div>
                </div>
                <div class="attendance-details">
                    <div class="attendance-status">
                        <div class="status-dot present"></div>
                        <div class="status-text present">มาเรียน</div>
                    </div>
                    <div class="attendance-time">เช็คชื่อเวลา 07:38 น.</div>
                    <div class="attendance-method">
                        <span class="material-icons">qr_code_scanner</span>
                        เช็คชื่อด้วย QR Code
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ข้อมูลครูที่ปรึกษา -->
<div class="teacher-card">
    <div class="teacher-title">ครูที่ปรึกษา</div>
    
    <?php if(isset($selected_student['teacher']) && !empty($selected_student['teacher'])): ?>
        <div class="teacher-info">
            <div class="teacher-avatar">
                <span class="material-icons">person</span>
            </div>
            <div class="teacher-details">
                <div class="teacher-name"><?php echo $selected_student['teacher']['name']; ?></div>
                <div class="teacher-position"><?php echo $selected_student['teacher']['position']; ?></div>
                <div class="teacher-contact">
                    <span class="material-icons">phone</span>
                    <?php echo $selected_student['teacher']['phone']; ?>
                </div>
            </div>
        </div>
        
        <div class="contact-buttons">
            <button class="contact-button call" onclick="callTeacher('<?php echo $selected_student['teacher']['phone']; ?>')">
                <span class="material-icons">call</span> โทร
            </button>
            <button class="contact-button message" onclick="messageTeacher('<?php echo $selected_student['teacher']['id']; ?>')">
                <span class="material-icons">chat</span> ข้อความ
            </button>
        </div>
    <?php else: ?>
        <!-- ข้อมูลตัวอย่าง -->
        <div class="teacher-info">
            <div class="teacher-avatar">
                <span class="material-icons">person</span>
            </div>
            <div class="teacher-details">
                <div class="teacher-name">อาจารย์ใจดี มากเมตตา</div>
                <div class="teacher-position">ครูประจำชั้น ม.6/1</div>
                <div class="teacher-contact">
                    <span class="material-icons">phone</span>
                    081-234-5678
                </div>
            </div>
        </div>
        
        <div class="contact-buttons">
            <button class="contact-button call" onclick="callTeacher('0812345678')">
                <span class="material-icons">call</span> โทร
            </button>
            <button class="contact-button message" onclick="messageTeacher(1)">
                <span class="material-icons">chat</span> ข้อความ
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
// กรองประวัติการเข้าแถว
function filterAttendance(filterType) {
    // ในการใช้งานจริงควรมีการกรองข้อมูลตามประเภท
    console.log(`กรองข้อมูลตามประเภท: ${filterType}`);
    
    // ตั้งค่าปุ่มกรอง
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => button.classList.remove('active'));
    
    const clickedButton = Array.from(filterButtons).find(button => 
        button.textContent.trim().toLowerCase().includes(filterType) || 
        (filterType === 'all' && button.textContent.trim() === 'ทั้งหมด') ||
        (filterType === 'month' && button.textContent.trim() === 'เดือนนี้') ||
        (filterType === 'week' && button.textContent.trim() === 'สัปดาห์นี้') ||
        (filterType === 'present' && button.textContent.trim() === 'มาเรียน') ||
        (filterType === 'absent' && button.textContent.trim() === 'ขาดเรียน')
    );
    
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
}

// โทรหาครูที่ปรึกษา
function callTeacher(phone) {
    // ในการใช้งานจริงควรมีการเชื่อมต่อกับระบบโทรศัพท์หรือ LINE
    console.log(`โทรหาครูที่ปรึกษา: ${phone}`);
    window.location.href = `tel:${phone}`;
}

// ส่งข้อความหาครูที่ปรึกษา
function messageTeacher(teacherId) {
    // ในการใช้งานจริงควรมีการเชื่อมต่อกับ LINE API หรือไปยังหน้าส่งข้อความ
    console.log(`ส่งข้อความหาครูที่ปรึกษา ID: ${teacherId}`);
    window.location.href = `messages.php?teacher=${teacherId}`;
}
</script>






<style>
/* สไตล์สำหรับหน้ารายละเอียดนักเรียน */
.header-navigation {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.back-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: white;
    color: var(--text-dark);
    margin-right: 15px;
    box-shadow: var(--card-shadow);
    text-decoration: none;
}

.page-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-dark);
}

.students-bar {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    overflow-x: auto;
    white-space: nowrap;
    display: flex;
    gap: 15px;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.students-bar::-webkit-scrollbar {
    height: 4px;
}

.students-bar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.students-bar::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 2px;
}

.student-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 30px;
    background-color: #f5f5f5;
    cursor: pointer;
    transition: all 0.2s;
}

.student-pill.active {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 2px 5px rgba(142, 36, 170, 0.3);
}

.student-pill-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #e0e0e0;
    margin-right: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.student-pill.active .student-pill-avatar {
    background-color: white;
    color: var(--primary-color);
}

.student-pill-name {
    font-size: 14px;
    font-weight: 500;
}

/* ข้อมูลนักเรียน */
.student-profile {
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    position: relative;
}

.profile-header {
    display: flex;
    margin-bottom: 20px;
}

.student-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background-color: var(--primary-color);
    margin-right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    font-weight: bold;
}

.student-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.student-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 5px;
}

.student-details {
    color: var(--text-light);
    font-size: 14px;
    margin-bottom: 3px;
}

.student-status {
    margin-top: 5px;
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.student-status.present {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.student-status.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.profile-actions {
    display: flex;
    gap: 10px;
    margin-top: 5px;
    flex-wrap: wrap;
}

.profile-action-button {
    background-color: #f5f5f5;
    border: none;
    border-radius: 20px;
    padding: 8px 15px;
    font-size: 13px;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.2s;
}

.profile-action-button:hover {
    background-color: #e8e8e8;
}

.profile-action-button .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

/* สถิติการเข้าแถว */
.stats-card {
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.stats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.stats-title {
    font-size: 16px;
    font-weight: 600;
}

.stats-term {
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 500;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    padding: 15px;
    border-radius: 12px;
    background-color: #f8f9fa;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-value.good {
    color: var(--success-color);
}

.stat-value.warning {
    color: var(--warning-color);
}

.stat-value.danger {
    color: var(--danger-color);
}

.stat-label {
    font-size: 12px;
    color: var(--text-light);
}

.stats-progress {
    margin-top: 20px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 14px;
}

.progress-text {
    font-weight: 500;
}

.progress-value {
    color: var(--primary-color);
    font-weight: 600;
}

.progress-bar {
    height: 8px;
    background-color: #f1f1f1;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color) 0%, #b039c3 100%);
    border-radius: 4px;
}

/* Chart Styles */
.chart-container {
    height: 200px;
    margin-top: 20px;
    position: relative;
}

.chart-bars {
    display: flex;
    height: 150px;
    align-items: flex-end;
    justify-content: space-between;
}

.chart-bar {
    width: 15%;
    background: linear-gradient(0deg, var(--primary-color) 0%, #b039c3 100%);
    border-radius: 5px 5px 0 0;
    position: relative;
}

.chart-bar-label {
    position: absolute;
    bottom: -25px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 12px;
    color: var(--text-light);
}

.chart-axis {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 1px;
    background-color: #e0e0e0;
}

/* ประวัติการเข้าแถว */
.attendance-card {
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.attendance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.attendance-title {
    font-size: 16px;
    font-weight: 600;
}

.view-all {
    color: var(--primary-color);
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
}

.attendance-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    overflow-x: auto;
    padding-bottom: 5px;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.attendance-filters::-webkit-scrollbar {
    height: 4px;
}

.attendance-filters::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.attendance-filters::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 2px;
}

.filter-button {
    padding: 8px 15px;
    border: none;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    white-space: nowrap;
    background-color: #f5f5f5;
    color: var(--text-dark);
    transition: all 0.2s;
}

.filter-button:hover {
    background-color: #e8e8e8;
}

.filter-button.active {
    background-color: var(--primary-color);
    color: white;
}

.attendance-list {
    max-height: 350px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.attendance-list::-webkit-scrollbar {
    width: 4px;
}

.attendance-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.attendance-list::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 2px;
}

.attendance-item {
    display: flex;
    padding: 15px 0;
    border-bottom: 1px solid #f1f1f1;
}

.attendance-item:last-child {
    border-bottom: none;
}

.attendance-date {
    width: 40px;
    text-align: center;
    margin-right: 15px;
}

.attendance-day {
    font-size: 18px;
    font-weight: bold;
}

.attendance-month {
    font-size: 12px;
    color: var(--text-light);
}

.attendance-details {
    flex: 1;
}

.attendance-status {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 10px;
}

.status-dot.present {
    background-color: var(--success-color);
}

.status-dot.absent {
    background-color: var(--danger-color);
}

.status-text {
    font-weight: 600;
}

.status-text.present {
    color: var(--success-color);
}

.status-text.absent {
    color: var(--danger-color);
}

.attendance-time {
    font-size: 12px;
    color: var(--text-light);
}

.attendance-method {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: var(--text-light);
    margin-top: 5px;
}

.attendance-method .material-icons {
    font-size: 14px;
    margin-right: 5px;
}

/* ข้อมูลครูที่ปรึกษา */
.teacher-card {
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.teacher-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
}

.teacher-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.teacher-avatar {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background-color: var(--secondary-color-light);
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary-color);
    font-size: 24px;
}

.teacher-details {
    flex: 1;
}

.teacher-name {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 3px;
}

.teacher-position {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 3px;
}

.teacher-contact {
    font-size: 12px;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
}

.teacher-contact .material-icons {
    font-size: 14px;
    margin-right: 5px;
}

.contact-buttons {
    display: flex;
    gap: 10px;
}

.contact-button {
    flex: 1;
    padding: 12px 0;
    border: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.contact-button.call {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.contact-button.call:hover {
    background-color: #d7f0d8;
}

.contact-button.message {
    background-color: var(--secondary-color-light);
    color: var(--secondary-color);
}

.contact-button.message:hover {
    background-color: #d2e8fd;
}

.contact-button .material-icons {
    margin-right: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .stat-item {
        padding: 10px;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .profile-actions {
        flex-wrap: wrap;
    }
    
    .profile-action-button {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .profile-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .student-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .student-info {
        margin-bottom: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .student-pill {
        padding: 6px 12px;
    }
    
    .student-pill-avatar {
        width: 25px;
        height: 25px;
        font-size: 12px;
    }
    
    .student-pill-name {
        font-size: 12px;
    }
    
    .attendance-filters {
        flex-wrap: nowrap;
    }
    
    .filter-button {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .attendance-day {
        font-size: 16px;
    }
    
    .attendance-month {
        font-size: 10px;
    }
    
    .teacher-avatar {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .teacher-name {
        font-size: 14px;
    }
    
    .teacher-position,
    .teacher-contact {
        font-size: 12px;
    }
    
    .contact-button {
        font-size: 12px;
        padding: 10px 0;
    }
}
</style>
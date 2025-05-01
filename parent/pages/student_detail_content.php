<?php
// ตรวจสอบว่ามีข้อมูลนักเรียนหรือไม่
if(!isset($selected_student)) {
    echo '<div class="error-message">ไม่พบข้อมูลนักเรียน</div>';
    exit;
}

// สร้างชื่อเต็ม
$full_name = $selected_student['student_title'] . ' ' . $selected_student['first_name'] . ' ' . $selected_student['last_name'];

// สร้างชื่อชั้นเรียน
$class_name = isset($selected_student['level']) ? $selected_student['level'] . '/' . $selected_student['group_number'] : 'ไม่ระบุชั้นเรียน';

// สร้างอักษรนำของชื่อ
$avatar = mb_substr($selected_student['first_name'], 0, 1, 'UTF-8');

// นับจำนวนข้อมูล
$attendance_count = isset($selected_student['attendance_history']) ? count($selected_student['attendance_history']) : 0;
$total_attendance_days = isset($selected_student['total_attendance_days']) ? (int)$selected_student['total_attendance_days'] : 0;
$total_absence_days = isset($selected_student['total_absence_days']) ? (int)$selected_student['total_absence_days'] : 0;
$total_days = $total_attendance_days + $total_absence_days;
$attendance_percentage = ($total_days > 0) ? round(($total_attendance_days / $total_days) * 100, 1) : 0;
?>

<!-- ปุ่มย้อนกลับ -->
<div class="back-button-container">
    <a href="students.php" class="back-button">
        <span class="material-icons">arrow_back</span>
        <span>กลับไปหน้ารายการนักเรียน</span>
    </a>
</div>

<!-- ข้อมูลนักเรียน -->
<div class="student-profile">
    <div class="student-profile-header">
        <div class="student-avatar large"><?php echo $avatar; ?></div>
        <div class="student-profile-info">
            <h1 class="student-name"><?php echo $full_name; ?></h1>
            <p class="student-class-info"><?php echo $class_name; ?> แผนก<?php echo $selected_student['department_name'] ?? 'ไม่ระบุ'; ?></p>
            <p class="student-id-info">รหัสนักเรียน: <?php echo $selected_student['student_code']; ?></p>
        </div>
    </div>
    
    <div class="student-contact-info">
        <div class="info-item">
            <span class="material-icons">phone</span>
            <span><?php echo $selected_student['phone_number'] ? $selected_student['phone_number'] : 'ไม่ระบุ'; ?></span>
        </div>
        <div class="info-item">
            <span class="material-icons">email</span>
            <span><?php echo $selected_student['email'] ? $selected_student['email'] : 'ไม่ระบุ'; ?></span>
        </div>
    </div>
</div>

<!-- แท็บควบคุม -->
<div class="tab-menu">
    <button class="tab-button active" onclick="switchTab('attendance')">การเข้าแถว</button>
    <button class="tab-button" onclick="switchTab('teacher')">ครูที่ปรึกษา</button>
</div>

<!-- เนื้อหาแท็บการเข้าแถว -->
<div id="tab-attendance" class="tab-content active">
    <div class="attendance-summary-card">
        <div class="summary-title">สรุปการเข้าแถว</div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_attendance_days; ?></div>
                <div class="stat-label">เข้าแถว</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_absence_days; ?></div>
                <div class="stat-label">ขาดแถว</div>
            </div>
            <div class="stat-item">
                <div class="stat-value percentage <?php echo $attendance_percentage >= 90 ? 'good' : ($attendance_percentage >= 80 ? 'warning' : 'danger'); ?>">
                    <?php echo number_format($attendance_percentage, 1); ?>%
                </div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
    </div>
    
    <div class="attendance-filter">
        <div class="filter-title">กรองข้อมูล</div>
        <div class="filter-buttons">
            <button class="filter-button active" onclick="filterAttendance('all')">ทั้งหมด</button>
            <button class="filter-button" onclick="filterAttendance('month')">เดือนนี้</button>
            <button class="filter-button" onclick="filterAttendance('week')">สัปดาห์นี้</button>
            <button class="filter-button" onclick="filterAttendance('present')">มาเรียน</button>
            <button class="filter-button" onclick="filterAttendance('absent')">ขาดเรียน</button>
        </div>
    </div>
    
    <div class="attendance-history">
        <div class="history-title">ประวัติการเข้าแถว</div>
        
        <?php if(!empty($selected_student['attendance_history'])): ?>
            <?php foreach($selected_student['attendance_history'] as $item): ?>
                <div class="attendance-item">
                    <div class="attendance-date">
                        <div class="date-day"><?php echo $item['day']; ?></div>
                        <div class="date-month"><?php echo $item['month_short']; ?></div>
                    </div>
                    <div class="attendance-details">
                        <div class="attendance-status-text <?php echo $item['status']; ?>">
                            <?php
                            switch($item['status']) {
                                case 'present':
                                    echo 'มาเรียน';
                                    break;
                                case 'absent':
                                    echo 'ขาดเรียน';
                                    break;
                                case 'late':
                                    echo 'มาสาย';
                                    break;
                                case 'leave':
                                    echo 'ลา';
                                    break;
                                default:
                                    echo 'ไม่ระบุ';
                            }
                            ?>
                        </div>
                        <?php if($item['present']): ?>
                            <div class="attendance-time">เวลา: <?php echo $item['time']; ?> น.</div>
                            <div class="attendance-method">
                                <span class="material-icons"><?php echo $item['method_icon']; ?></span>
                                <span><?php echo $item['method']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="attendance-status">
                        <span class="material-icons">
                            <?php
                            switch($item['status']) {
                                case 'present':
                                    echo 'check_circle';
                                    break;
                                case 'absent':
                                    echo 'cancel';
                                    break;
                                case 'late':
                                    echo 'watch_later';
                                    break;
                                case 'leave':
                                    echo 'event_busy';
                                    break;
                                default:
                                    echo 'help';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-attendance-data">
                <span class="material-icons">event_busy</span>
                <p>ไม่พบประวัติการเข้าแถว</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- เนื้อหาแท็บครูที่ปรึกษา -->
<div id="tab-teacher" class="tab-content">
    <div class="teacher-profile">
        <?php if(isset($selected_student['teacher']) && !empty($selected_student['teacher'])): ?>
            <div class="teacher-avatar">
                <?php if(isset($selected_student['teacher']['avatar'])): ?>
                    <img src="<?php echo $selected_student['teacher']['avatar']; ?>" alt="<?php echo $selected_student['teacher']['name']; ?>">
                <?php else: ?>
                    <span class="material-icons">person</span>
                <?php endif; ?>
            </div>
            <div class="teacher-info">
                <h2 class="teacher-name"><?php echo $selected_student['teacher']['name']; ?></h2>
                <p class="teacher-position"><?php echo $selected_student['teacher']['position']; ?></p>
                
                <div class="teacher-contact">
                    <div class="contact-item">
                        <span class="material-icons">phone</span>
                        <span><?php echo $selected_student['teacher']['phone']; ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="material-icons">chat</span>
                        <span>LINE ID: <?php echo $selected_student['teacher']['line_id']; ?></span>
                    </div>
                </div>
                
                <div class="teacher-actions">
                    <a href="tel:<?php echo $selected_student['teacher']['phone']; ?>" class="teacher-action-button call">
                        <span class="material-icons">call</span>
                        <span>โทรหาครูที่ปรึกษา</span>
                    </a>
                    <a href="messages.php?teacher=<?php echo $selected_student['teacher']['id']; ?>" class="teacher-action-button message">
                        <span class="material-icons">chat</span>
                        <span>ส่งข้อความ</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="no-teacher-data">
                <span class="material-icons">person_off</span>
                <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// สลับแท็บ
function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // ลบคลาส active จากทุกแท็บ
    tabs.forEach(tab => tab.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));
    
    // เพิ่มคลาส active ให้แท็บที่เลือก
    if(tabName === 'attendance') {
        tabs[0].classList.add('active');
        document.getElementById('tab-attendance').classList.add('active');
    } else if(tabName === 'teacher') {
        tabs[1].classList.add('active');
        document.getElementById('tab-teacher').classList.add('active');
    }
}

// กรองข้อมูลการเข้าแถว
function filterAttendance(filterType) {
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
    
    if(clickedButton) {
        clickedButton.classList.add('active');
    }
    
    // ตัวอย่างการกรองรายการเข้าแถว
    const attendanceItems = document.querySelectorAll('.attendance-item');
    
    if(filterType === 'all') {
        // แสดงทั้งหมด
        attendanceItems.forEach(item => {
            item.style.display = 'flex';
        });
    } else if(filterType === 'month') {
        // กรองตามเดือนปัจจุบัน
        const currentMonth = new Date().getMonth() + 1; // 1-12
        attendanceItems.forEach(item => {
            const monthElement = item.querySelector('.date-month');
            if(monthElement) {
                // สำหรับตัวอย่าง ตรวจสอบแค่ว่าเป็นเดือนปัจจุบัน (ในการใช้งานจริงควรมีการตรวจสอบที่ดีกว่านี้)
                if(getMonthNumberFromThaiAbbr(monthElement.textContent) === currentMonth) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            } else {
                item.style.display = 'none';
            }
        });
    } else if(filterType === 'week') {
        // กรองตามสัปดาห์ปัจจุบัน
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay()); // วันอาทิตย์ของสัปดาห์นี้
        
        // ในตัวอย่างนี้ แสดงเฉพาะ 3 รายการล่าสุด เพื่อจำลองการกรองสัปดาห์
        attendanceItems.forEach((item, index) => {
            if(index < 3) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    } else if(filterType === 'present') {
        // กรองเฉพาะมาเรียน
        attendanceItems.forEach(item => {
            const statusElement = item.querySelector('.attendance-status-text');
            if(statusElement && (statusElement.classList.contains('present') || statusElement.classList.contains('late'))) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    } else if(filterType === 'absent') {
        // กรองเฉพาะขาดเรียน
        attendanceItems.forEach(item => {
            const statusElement = item.querySelector('.attendance-status-text');
            if(statusElement && statusElement.classList.contains('absent')) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
}

// แปลงชื่อย่อเดือนภาษาไทยเป็นเลขเดือน
function getMonthNumberFromThaiAbbr(thaiMonth) {
    const monthMap = {
        'ม.ค.': 1,
        'ก.พ.': 2,
        'มี.ค.': 3,
        'เม.ย.': 4,
        'พ.ค.': 5,
        'มิ.ย.': 6,
        'ก.ค.': 7,
        'ส.ค.': 8,
        'ก.ย.': 9,
        'ต.ค.': 10,
        'พ.ย.': 11,
        'ธ.ค.': 12
    };
    
    // ดึงเฉพาะชื่อเดือน (เช่น จาก "ม.ค." หรือ "ม.ค. 2567")
    const monthAbbr = thaiMonth.trim();
    return monthMap[monthAbbr] || 0;
}

// โทรหาครูที่ปรึกษา
function callTeacher(phone) {
    window.location.href = `tel:${phone}`;
}
</script>

<style>
/* สไตล์สำหรับหน้ารายละเอียดนักเรียน */
.back-button-container {
    margin-bottom: 20px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.back-button:hover {
    color: var(--primary-color-dark);
}

.back-button .material-icons {
    margin-right: 5px;
}

.student-profile {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.student-profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.student-avatar.large {
    width: 80px;
    height: 80px;
    font-size: 32px;
    margin-right: 20px;
}

.student-profile-info {
    flex: 1;
}

.student-name {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.student-class-info, .student-id-info {
    color: var(--text-light);
    margin-bottom: 3px;
}

.student-contact-info {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    color: var(--text-light);
}

.info-item .material-icons {
    margin-right: 5px;
    font-size: 20px;
    color: var(--primary-color);
}

.tab-menu {
    background-color: white;
    border-radius: 10px;
    display: flex;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
}

.tab-button {
    flex: 1;
    padding: 15px 0;
    text-align: center;
    background: none;
    border: none;
    font-weight: 600;
    font-size: 14px;
    color: var(--text-light);
    position: relative;
    cursor: pointer;
    transition: color var(--transition-speed);
}

.tab-button.active {
    color: var(--primary-color);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.attendance-summary-card {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.summary-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.summary-stats {
    display: flex;
    justify-content: space-between;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 5px;
    color: var(--primary-color);
}

.stat-value.percentage {
    color: var(--primary-color);
}

.stat-value.percentage.good {
    color: var(--success-color);
}

.stat-value.percentage.warning {
    color: var(--warning-color);
}

.stat-value.percentage.danger {
    color: var(--danger-color);
}

.stat-label {
    font-size: 14px;
    color: var(--text-light);
}

.attendance-filter {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.filter-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--text-dark);
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.filter-button {
    padding: 8px 15px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    background: none;
    color: var(--text-light);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-button.active, .filter-button:hover {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.attendance-history {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.history-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.attendance-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    background-color: var(--bg-light);
    margin-bottom: 10px;
}

.attendance-date {
    width: 60px;
    text-align: center;
    margin-right: 15px;
}

.date-day {
    font-size: 24px;
    font-weight: 700;
    line-height: 1;
    color: var(--primary-color);
}

.date-month {
    font-size: 14px;
    color: var(--text-light);
}

.attendance-details {
    flex: 1;
}

.attendance-status-text {
    font-weight: 600;
    margin-bottom: 5px;
}

.attendance-status-text.present {
    color: var(--success-color);
}

.attendance-status-text.absent {
    color: var(--danger-color);
}

.attendance-status-text.late {
    color: var(--warning-color);
}

.attendance-status-text.leave {
    color: var(--text-muted);
}

.attendance-time, .attendance-method {
    font-size: 14px;
    color: var(--text-light);
    display: flex;
    align-items: center;
}

.attendance-method .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.attendance-status {
    margin-left: 15px;
}

.attendance-status .material-icons {
    font-size: 24px;
}

.attendance-status .material-icons[textContent="check_circle"] {
    color: var(--success-color);
}

.attendance-status .material-icons[textContent="cancel"] {
    color: var(--danger-color);
}

.attendance-status .material-icons[textContent="watch_later"] {
    color: var(--warning-color);
}

.attendance-status .material-icons[textContent="event_busy"] {
    color: var(--text-muted);
}

.no-attendance-data {
    text-align: center;
    padding: 30px;
    color: var(--text-light);
}

.no-attendance-data .material-icons {
    font-size: 48px;
    margin-bottom: 10px;
    color: #ccc;
}

.teacher-profile {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.teacher-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    overflow: hidden;
}

.teacher-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.teacher-avatar .material-icons {
    font-size: 48px;
    color: var(--secondary-color);
}

.teacher-info {
    width: 100%;
}

.teacher-name {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.teacher-position {
    color: var(--text-light);
    margin-bottom: 20px;
}

.teacher-contact {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.contact-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--text-light);
}

.contact-item .material-icons {
    color: var(--primary-color);
    font-size: 20px;
}

.teacher-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}

.teacher-action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s;
}

.teacher-action-button.call {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.teacher-action-button.call:hover {
    background-color: #d7f0d8;
}

.teacher-action-button.message {
    background-color: var(--secondary-color-light);
    color: var(--secondary-color);
}

.teacher-action-button.message:hover {
    background-color: #d2e8fd;
}

.teacher-action-button .material-icons {
    margin-right: 8px;
}

.no-teacher-data {
    text-align: center;
    padding: 30px;
    color: var(--text-light);
}

.no-teacher-data .material-icons {
    font-size: 48px;
    margin-bottom: 10px;
    color: #ccc;
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .student-profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .student-avatar.large {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .student-contact-info {
        justify-content: center;
    }
    
    .summary-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .teacher-contact {
        align-items: center;
    }
}

@media (max-width: 480px) {
    .student-name {
        font-size: 20px;
    }
    
    .filter-button {
        font-size: 12px;
        padding: 6px 12px;
    }
    
    .attendance-item {
        flex-wrap: wrap;
    }
    
    .attendance-date {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .date-day, .date-month {
        font-size: 16px;
    }
    
    .attendance-status {
        position: absolute;
        top: 15px;
        right: 15px;
    }
    
    .attendance-item {
        position: relative;
        padding-top: 40px;
    }
}
</style>
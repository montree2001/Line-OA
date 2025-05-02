<!-- หน้ารายละเอียดนักเรียน สำหรับผู้ปกครอง -->
<?php
// หากไม่มีข้อมูลนักเรียนที่เลือก ให้เปลี่ยนเส้นทาง
if (!isset($selected_student) || empty($selected_student)) {
    echo '<div class="error-message">ไม่พบข้อมูลนักเรียน หรือนักเรียนไม่ได้อยู่ในความดูแลของคุณ</div>';
    echo '<div class="back-section"><a href="students.php" class="back-button"><span class="material-icons">arrow_back</span> กลับไปยังรายการนักเรียน</a></div>';
    return;
}

// ดึงข้อมูลผลการเรียนและการเข้าแถวจากฐานข้อมูลอีกครั้ง (เพื่อให้แน่ใจว่ามีข้อมูลล่าสุด)
$student_id = $selected_student['student_id'];
$conn2 = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn2->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn2->connect_error);
}
$conn2->set_charset("utf8mb4");

// ดึงข้อมูลการเข้าแถวจากตาราง student_academic_records
$academic_stmt = $conn2->prepare("
    SELECT sar.total_attendance_days, sar.total_absence_days, sar.passed_activity
    FROM student_academic_records sar
    JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
    WHERE sar.student_id = ? AND ay.is_active = 1
");
$academic_stmt->bind_param("i", $student_id);
$academic_stmt->execute();
$academic_result = $academic_stmt->get_result();

$attendance_days = 0;
$absence_days = 0;
$passed_activity = null;

if ($academic_result->num_rows > 0) {
    $academic_data = $academic_result->fetch_assoc();
    $attendance_days = (int)$academic_data['total_attendance_days'];
    $absence_days = (int)$academic_data['total_absence_days'];
    $passed_activity = $academic_data['passed_activity'];
}
$academic_stmt->close();

// ดึงข้อมูลการเช็คชื่อล่าสุด
$latest_check_stmt = $conn2->prepare("
    SELECT attendance_status, DATE_FORMAT(check_time, '%H:%i') as check_time, date
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC, check_time DESC 
    LIMIT 1
");
$latest_check_stmt->bind_param("i", $student_id);
$latest_check_stmt->execute();
$latest_check_result = $latest_check_stmt->get_result();
$latest_check_data = null;

if ($latest_check_result->num_rows > 0) {
    $latest_check_data = $latest_check_result->fetch_assoc();
}
$latest_check_stmt->close();

// คำนวณเปอร์เซ็นต์การเข้าแถว
$total_days = $attendance_days + $absence_days;
$attendance_percentage = ($total_days > 0) ? round(($attendance_days / $total_days) * 100, 1) : 0;

// กำหนดสถานะการเช็คชื่อล่าสุด
$latest_status = 'ไม่มีข้อมูล';
$latest_status_class = 'unknown';
$latest_status_icon = 'help_outline';
$latest_check_in_time = '';
$latest_check_date = '';

if ($latest_check_data) {
    $latest_check_in_time = $latest_check_data['check_time'];
    $latest_check_date = date('d/m/Y', strtotime($latest_check_data['date']));
    
    switch ($latest_check_data['attendance_status']) {
        case 'present':
            $latest_status = 'เข้าแถว';
            $latest_status_class = 'present';
            $latest_status_icon = 'check_circle';
            break;
        case 'absent':
            $latest_status = 'ขาดแถว';
            $latest_status_class = 'absent';
            $latest_status_icon = 'cancel';
            break;
        case 'late':
            $latest_status = 'มาสาย';
            $latest_status_class = 'late';
            $latest_status_icon = 'watch_later';
            break;
        case 'leave':
            $latest_status = 'ลา';
            $latest_status_class = 'leave';
            $latest_status_icon = 'event_busy';
            break;
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn2->close();

// จัดเตรียมข้อมูลสำหรับแสดงผล
$full_name = $selected_student['student_title'] . ' ' . $selected_student['first_name'] . ' ' . $selected_student['last_name'];
$class_name = isset($selected_student['level']) ? $selected_student['level'] . '/' . $selected_student['group_number'] : 'ไม่ระบุชั้นเรียน';
$department = isset($selected_student['department_name']) ? $selected_student['department_name'] : 'ไม่ระบุแผนก';
$avatar_text = mb_substr($selected_student['first_name'], 0, 1, 'UTF-8');

// สถานะกิจกรรม
$activity_status_text = 'รอประเมิน';
$activity_status_class = 'waiting';
if ($passed_activity !== null) {
    if ($passed_activity == 1) {
        $activity_status_text = 'ผ่าน';
        $activity_status_class = 'passed';
    } else {
        $activity_status_text = 'ไม่ผ่าน';
        $activity_status_class = 'failed';
    }
}

// คำนวณวันที่ต้องมาเข้าแถวเพิ่ม (กรณีเสี่ยงไม่ผ่าน)
$required_days = 0;
$days_remaining = 0;

// ตรวจสอบว่ามีครูที่ปรึกษาหรือไม่
$has_teacher = isset($selected_student['teacher']) && $selected_student['teacher']['id'] > 0;
?>

<!-- ปุ่มกลับไปยังรายการนักเรียน -->
<div class="back-section">
    <a href="students.php" class="back-button"><span class="material-icons">arrow_back</span> กลับไปยังรายการนักเรียน</a>
</div>

<!-- ส่วนหัวข้อมูลนักเรียน -->
<div class="student-detail-header">
    <div class="student-avatar large"><?php echo $avatar_text; ?></div>
    <div class="student-basic-info">
        <h1 class="student-name"><?php echo $full_name; ?></h1>
        <div class="student-info-row">
            <div class="info-item">
                <span class="info-label">รหัสนักเรียน:</span>
                <span class="info-value"><?php echo $selected_student['student_code']; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ระดับชั้น:</span>
                <span class="info-value"><?php echo $class_name; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">แผนก:</span>
                <span class="info-value"><?php echo $department; ?></span>
            </div>
        </div>
        <div class="student-status large <?php echo $latest_status_class; ?>" title="<?php echo $latest_status; ?> <?php echo $latest_check_date; ?> <?php echo $latest_check_in_time; ?>">
            <span class="material-icons"><?php echo $latest_status_icon; ?></span>
            <span><?php echo $latest_status; ?></span>
            <?php if($latest_check_in_time): ?>
                <span class="status-details"><?php echo $latest_check_date; ?>, <?php echo $latest_check_in_time; ?> น.</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- แท็บเมนู -->
<div class="tab-menu">
    <button class="tab-button active" data-tab="attendance">การเข้าแถว</button>
    <button class="tab-button" data-tab="personal">ข้อมูลส่วนตัว</button>
    <button class="tab-button" data-tab="teacher">ครูที่ปรึกษา</button>
</div>

<!-- เนื้อหาแท็บการเข้าแถว -->
<div class="tab-content active" id="attendance-tab">
    <!-- สรุปการเข้าแถว -->
    <div class="content-section">
        <h2 class="section-title">สรุปการเข้าแถว</h2>
        <div class="attendance-summary-card">
            <div class="summary-item">
                <div class="summary-value"><?php echo $attendance_days; ?></div>
                <div class="summary-label">วันเข้าแถว</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo $absence_days; ?></div>
                <div class="summary-label">วันขาดแถว</div>
            </div>
            <div class="summary-item">
                <div class="summary-value percentage <?php echo $attendance_percentage >= 90 ? 'good' : ($attendance_percentage >= 80 ? 'warning' : 'danger'); ?>">
                    <?php echo $attendance_percentage; ?>%
                </div>
                <div class="summary-label">อัตราการเข้าแถว</div>
            </div>
            <div class="summary-item">
                <div class="summary-value status <?php echo $activity_status_class; ?>">
                    <?php echo $activity_status_text; ?>
                </div>
                <div class="summary-label">สถานะกิจกรรม</div>
            </div>
        </div>
    </div>

    <!-- ประวัติการเข้าแถว -->
    <div class="content-section">
        <div class="section-header-with-filter">
            <h2 class="section-title">ประวัติการเข้าแถว</h2>
            <div class="filter-buttons">
                <button class="filter-button active" data-filter="all">ทั้งหมด</button>
                <button class="filter-button" data-filter="present">มาแถว</button>
                <button class="filter-button" data-filter="absent">ขาดแถว</button>
                <button class="filter-button" data-filter="month">เดือนนี้</button>
                <button class="filter-button" data-filter="week">สัปดาห์นี้</button>
            </div>
        </div>
        
        <div class="attendance-history">
            <?php if(isset($selected_student['attendance_history']) && !empty($selected_student['attendance_history'])): ?>
                <?php foreach($selected_student['attendance_history'] as $history): ?>
                    <div class="attendance-item" data-status="<?php echo $history['status']; ?>">
                        <div class="attendance-date">
                            <div class="date-day"><?php echo $history['day']; ?></div>
                            <div class="date-month"><?php echo $history['month_short']; ?></div>
                        </div>
                        <div class="attendance-status">
                            <span class="material-icons <?php echo $history['status']; ?>">
                                <?php 
                                switch($history['status']) {
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
                                        echo 'help_outline';
                                }
                                ?>
                            </span>
                            <div class="attendance-status-text <?php echo $history['status']; ?>">
                                <?php 
                                switch($history['status']) {
                                    case 'present':
                                        echo 'เข้าแถว';
                                        break;
                                    case 'absent':
                                        echo 'ขาดแถว';
                                        break;
                                    case 'late':
                                        echo 'มาสาย';
                                        break;
                                    case 'leave':
                                        echo 'ลา';
                                        break;
                                    default:
                                        echo 'ไม่มีข้อมูล';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="attendance-time">
                            <span class="material-icons"><?php echo $history['method_icon']; ?></span>
                            <span class="time-text"><?php echo $history['time']; ?> น.</span>
                        </div>
                        <div class="attendance-method">
                            <?php echo $history['method']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data small" id="no-attendance-message">
                    <div class="no-data-icon">
                        <span class="material-icons">event_busy</span>
                    </div>
                    <div class="no-data-message">ไม่พบข้อมูลการเข้าแถว</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บข้อมูลส่วนตัว -->
<div class="tab-content" id="personal-tab">
    <div class="content-section">
        <h2 class="section-title">ข้อมูลส่วนตัว</h2>
        <div class="personal-info-card">
            <div class="info-group">
                <div class="info-row">
                    <div class="info-label">ชื่อ-นามสกุล:</div>
                    <div class="info-value"><?php echo $full_name; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">รหัสนักเรียน:</div>
                    <div class="info-value"><?php echo $selected_student['student_code']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ระดับชั้น:</div>
                    <div class="info-value"><?php echo $class_name; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">แผนก:</div>
                    <div class="info-value"><?php echo $department; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">สถานะ:</div>
                    <div class="info-value"><?php echo $selected_student['status']; ?></div>
                </div>
                <?php if(isset($selected_student['phone_number']) && !empty($selected_student['phone_number'])): ?>
                <div class="info-row">
                    <div class="info-label">เบอร์โทรศัพท์:</div>
                    <div class="info-value"><?php echo $selected_student['phone_number']; ?></div>
                </div>
                <?php endif; ?>
                <?php if(isset($selected_student['email']) && !empty($selected_student['email'])): ?>
                <div class="info-row">
                    <div class="info-label">อีเมล:</div>
                    <div class="info-value"><?php echo $selected_student['email']; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บครูที่ปรึกษา -->
<div class="tab-content" id="teacher-tab">
    <div class="content-section">
        <h2 class="section-title">ข้อมูลครูที่ปรึกษา</h2>
        
        <?php if($has_teacher): ?>
            <div class="teacher-card">
                <div class="teacher-info">
                    <div class="teacher-avatar">
                        <span class="material-icons">person</span>
                    </div>
                    <div class="teacher-details">
                        <div class="teacher-name"><?php echo $selected_student['teacher']['name']; ?></div>
                        <div class="teacher-position"><?php echo $selected_student['teacher']['position']; ?></div>
                    </div>
                </div>
                
                <div class="contact-buttons">
                    <a href="tel:<?php echo $selected_student['teacher']['phone']; ?>" class="contact-button call call-teacher-btn" data-phone="<?php echo $selected_student['teacher']['phone']; ?>">
                        <span class="material-icons">call</span> โทร
                    </a>
                    <a href="messages.php?teacher=<?php echo $selected_student['teacher']['id']; ?>" class="contact-button message message-teacher-btn" data-teacher-id="<?php echo $selected_student['teacher']['id']; ?>">
                        <span class="material-icons">chat</span> ข้อความ
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="no-data small">
                <div class="no-data-icon">
                    <span class="material-icons">person_off</span>
                </div>
                <div class="no-data-message">ไม่พบข้อมูลครูที่ปรึกษา</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* แก้ไขให้แสดงผลเต็มหน้าจอ */
@media (max-width: 768px) {
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 10px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    body {
        padding: 0 !important;
        background-color: #f5f5f5;
    }
    
    .content-section, 
    .student-detail-header, 
    .tab-menu, 
    .personal-info-card, 
    .teacher-card, 
    .attendance-summary-card {
        width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 8px !important;
    }
}

/* สไตล์สำหรับหน้ารายละเอียดนักเรียน */
.back-section {
    margin-bottom: 20px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.back-button .material-icons {
    margin-right: 5px;
}

.student-detail-header {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    width: 100%;
    box-sizing: border-box;
}

.student-avatar.large {
    width: 80px;
    height: 80px;
    font-size: 32px;
    margin-right: 20px;
}

.student-basic-info {
    flex: 1;
}

.student-basic-info h1 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.student-info-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 10px;
}

.info-item {
    display: flex;
    align-items: center;
    max-width: 100%;
}

.info-label {
    color: var(--text-light);
    margin-right: 5px;
    flex-shrink: 0;
}

.info-value {
    font-weight: 500;
    word-break: break-word;
}

.student-status.large {
    position: static;
    display: inline-flex;
    margin-top: 10px;
}

/* แท็บเมนู */
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

/* ส่วนเนื้อหา */
.content-section {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
}

/* การ์ดสรุปการเข้าแถว */
.attendance-summary-card {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    background-color: var(--bg-light);
    border-radius: 10px;
    padding: 15px;
}

.summary-item {
    text-align: center;
    padding: 15px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.summary-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.summary-value.percentage {
    color: var(--primary-color);
}

.summary-value.percentage.good {
    color: var(--success-color);
}

.summary-value.percentage.warning {
    color: var(--warning-color);
}

.summary-value.percentage.danger {
    color: var(--danger-color);
}

.summary-value.status {
    font-size: 18px;
}

.summary-value.status.waiting {
    color: var(--text-muted);
}

.summary-value.status.passed {
    color: var(--success-color);
}

.summary-value.status.failed {
    color: var(--danger-color);
}

.summary-label {
    font-size: 14px;
    color: var(--text-light);
}

/* ส่วนตัวกรอง */
.section-header-with-filter {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    width: 100%;
    box-sizing: border-box;
}

.filter-button {
    background-color: var(--bg-light);
    color: var(--text-light);
    border: none;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    flex: 0 0 auto;
}

.filter-button.active {
    background-color: var(--primary-color);
    color: white;
}

/* ประวัติการเข้าแถว */
.attendance-history {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
    width: 100%;
    box-sizing: border-box;
}

.attendance-item {
    display: grid;
    grid-template-columns: 60px 1fr 100px 80px;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background-color: var(--bg-light);
    border-radius: 8px;
    margin-bottom: 10px;
    width: 100%;
    box-sizing: border-box;
}

.attendance-date {
    text-align: center;
}

.date-day {
    font-size: 20px;
    font-weight: 600;
}

.date-month {
    font-size: 12px;
    color: var(--text-light);
}

.attendance-status {
    display: flex;
    align-items: center;
    gap: 5px;
}

.attendance-status .material-icons {
    font-size: 18px;
}

.attendance-status .material-icons.present {
    color: var(--success-color);
}

.attendance-status .material-icons.absent {
    color: var(--danger-color);
}

.attendance-status .material-icons.late {
    color: var(--warning-color);
}

.attendance-status .material-icons.leave {
    color: var(--text-muted);
}

.attendance-status-text {
    font-weight: 500;
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

.attendance-time {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--text-light);
}

.attendance-time .material-icons {
    font-size: 16px;
}

.attendance-method {
    text-align: right;
    color: var(--text-muted);
    font-size: 12px;
}

/* ข้อมูลส่วนตัว */
.personal-info-card {
    background-color: var(--bg-light);
    border-radius: 10px;
    padding: 15px;
}

.info-group {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-label {
    width: 120px;
    color: var(--text-light);
    flex-shrink: 0;
}

/* การ์ดครูที่ปรึกษา */
.teacher-card {
    background-color: var(--bg-light);
    border-radius: 10px;
    padding: 15px;
}

.teacher-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.teacher-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary-color);
    font-size: 24px;
    overflow: hidden;
}

.teacher-details {
    flex: 1;
}

.teacher-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.teacher-position {
    font-size: 14px;
    color: var(--text-light);
}

.contact-buttons {
    display: flex;
    gap: 10px;
}

.contact-button {
    flex: 1;
    padding: 12px 0;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color var(--transition-speed);
    text-decoration: none;
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
    font-size: 18px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .student-detail-header {
        flex-direction: column;
        text-align: center;
    }
    
    .student-avatar.large {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .student-info-row {
        justify-content: center;
    }
    
    /* ปรับข้อมูลสถานะการเข้าแถวให้ชัดเจนยิ่งขึ้น */
    .student-status.large {
        position: relative;
        display: inline-flex;
        margin-top: 10px;
        justify-content: center;
        padding: 8px 12px;
    }
    
    .attendance-summary-card {
        grid-template-columns: 1fr 1fr;
    }
    
    .attendance-item {
        grid-template-columns: 60px 1fr 80px;
    }
    
    .attendance-method {
        display: none;
    }
    
    .section-header-with-filter {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-buttons {
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }
}

@media (max-width: 480px) {
    .student-detail-header {
        padding: 15px;
        flex-direction: column;
    }
    
    .student-basic-info {
        width: 100%;
        text-align: center;
    }
    
    .student-basic-info h1 {
        font-size: 18px;
        margin-bottom: 15px;
        word-break: break-word;
    }
    
    .student-avatar.large {
        width: 70px;
        height: 70px;
        font-size: 28px;
        margin: 0 auto 15px auto;
    }
    
    /* แก้ไขการแสดงผลข้อมูลนักเรียนให้เป็นทีละบรรทัดแบบเต็ม */
    .student-info-row {
        flex-direction: column;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .info-item {
        width: 100%;
        display: block;
        text-align: center;
        white-space: normal;
        overflow: visible;
    }
    
    .info-label {
        display: inline-block;
        margin-right: 5px;
    }
    
    .info-value {
        display: inline-block;
    }
    
    /* ปรับสถานะให้แสดงเต็มบรรทัด */
    .student-status.large {
        position: static;
        display: flex;
        justify-content: center;
        margin: 5px auto;
        width: auto;
        max-width: 100%;
        padding: 8px 15px;
        border-radius: 20px;
    }
    
    .student-status.large .status-details {
        display: inline !important;
        margin-left: 5px;
    }
    
    .tab-button {
        padding: 12px 0;
        font-size: 12px;
    }
    
    .content-section {
        padding: 15px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .summary-value {
        font-size: 20px;
    }
    
    .attendance-item {
        grid-template-columns: 50px 1fr;
        gap: 10px;
    }
    
    .attendance-time {
        display: none;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .teacher-info {
        flex-direction: column;
        text-align: center;
    }
    
    .teacher-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .contact-buttons {
        flex-direction: column;
    }
}
</style>

<script>
// สคริปต์จัดการแท็บ
document.addEventListener('DOMContentLoaded', function() {
    // สลับแท็บ
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ลบคลาส active จากทุกแท็บ
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // เพิ่มคลาส active ให้แท็บที่คลิกและเนื้อหาที่เกี่ยวข้อง
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // จัดการตัวกรองประวัติการเข้าแถว
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ลบคลาส active จากทุกปุ่ม
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // เพิ่มคลาส active ให้ปุ่มที่คลิก
            this.classList.add('active');
            
            // กรองข้อมูลตามประเภทที่เลือก
            const filterType = this.getAttribute('data-filter');
            filterAttendance(filterType);
        });
    });
    
    // ฟังก์ชันกรองข้อมูลการเข้าแถว
    function filterAttendance(filterType) {
        const attendanceItems = document.querySelectorAll('.attendance-item');
        let visibleCount = 0;
        
        if (filterType === 'all') {
            // แสดงทั้งหมด
            attendanceItems.forEach(item => {
                item.style.display = 'grid';
                visibleCount++;
            });
        } else if (filterType === 'present') {
            // กรองเฉพาะเข้าแถวและมาสาย
            attendanceItems.forEach(item => {
                const status = item.getAttribute('data-status');
                if (status === 'present' || status === 'late') {
                    item.style.display = 'grid';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        } else if (filterType === 'absent') {
            // กรองเฉพาะขาดแถว
            attendanceItems.forEach(item => {
                const status = item.getAttribute('data-status');
                if (status === 'absent') {
                    item.style.display = 'grid';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
        } else if (filterType === 'month') {
            // กรองตามเดือนปัจจุบัน
            const currentMonth = new Date().getMonth() + 1; // 1-12
            
            attendanceItems.forEach(item => {
                const monthElement = item.querySelector('.date-month');
                if (monthElement) {
                    const monthText = monthElement.textContent;
                    // แปลงเดือนย่อภาษาไทยเป็นตัวเลข
                    const monthNumber = getMonthNumberFromThaiAbbr(monthText);
                    
                    if (monthNumber === currentMonth) {
                        item.style.display = 'grid';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        } else if (filterType === 'week') {
            // กรองตามสัปดาห์ปัจจุบัน
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay()); // วันอาทิตย์ของสัปดาห์นี้
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6); // วันเสาร์ของสัปดาห์นี้
            
            attendanceItems.forEach(item => {
                const dayElement = item.querySelector('.date-day');
                const monthElement = item.querySelector('.date-month');
                
                if (dayElement && monthElement) {
                    const day = parseInt(dayElement.textContent);
                    const monthText = monthElement.textContent;
                    const month = getMonthNumberFromThaiAbbr(monthText) - 1; // 0-11 สำหรับ JavaScript Date
                    const year = new Date().getFullYear();
                    
                    const itemDate = new Date(year, month, day);
                    
                    if (itemDate >= startOfWeek && itemDate <= endOfWeek) {
                        item.style.display = 'grid';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // แสดง/ซ่อนข้อความเมื่อไม่พบข้อมูล
        const noAttendanceMessage = document.getElementById('no-attendance-message');
        if (noAttendanceMessage) {
            if (visibleCount === 0) {
                noAttendanceMessage.style.display = 'block';
            } else {
                noAttendanceMessage.style.display = 'none';
            }
        }
    }
    
    // แปลงเดือนย่อภาษาไทยเป็นตัวเลข
    function getMonthNumberFromThaiAbbr(thaiMonthAbbr) {
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
        
        return monthMap[thaiMonthAbbr] || 0;
    }
});
</script>
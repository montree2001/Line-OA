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

// ใช้ข้อมูลที่มีอยู่แล้วจาก selected_student ที่ดึงมาจากฐานข้อมูลในไฟล์ students.php
// การมาถึงบรรทัดนี้แสดงว่า selected_student มีข้อมูลแล้ว เพราะผ่านเงื่อนไข isset ข้างบน
$total_attendance_days = isset($selected_student['total_attendance_days']) ? (int)$selected_student['total_attendance_days'] : 0;
$total_absence_days = isset($selected_student['total_absence_days']) ? (int)$selected_student['total_absence_days'] : 0;
$total_days = $total_attendance_days + $total_absence_days;
$attendance_percentage = ($total_days > 0) ? round(($total_attendance_days / $total_days) * 100, 1) : 0;

// จัดกลุ่มข้อมูลตามเดือน (สำหรับการแสดงผลประวัติการเข้าแถวตามเดือน)
$attendance_by_month = [];
if (!empty($selected_student['attendance_history'])) {
    foreach ($selected_student['attendance_history'] as $item) {
        $month_key = $item['month_short'] . ' ' . (date('Y') + 543); // เพิ่มปี พ.ศ.
        if (!isset($attendance_by_month[$month_key])) {
            $attendance_by_month[$month_key] = [];
        }
        $attendance_by_month[$month_key][] = $item;
    }
    // เรียงลำดับเดือนจากล่าสุดไปเก่าสุด
    krsort($attendance_by_month);
}

// คำนวณสถิติเพิ่มเติมจากประวัติการเข้าแถว
$present_count = 0;
$late_count = 0;
$absent_count = 0;
$leave_count = 0;

if (!empty($selected_student['attendance_history'])) {
    foreach ($selected_student['attendance_history'] as $item) {
        switch ($item['status']) {
            case 'present':
                $present_count++;
                break;
            case 'late':
                $late_count++;
                break;
            case 'absent':
                $absent_count++;
                break;
            case 'leave':
                $leave_count++;
                break;
        }
    }
}

// กำหนดสถานะตามอัตราการเข้าแถว
$attendance_status_class = '';
if ($attendance_percentage >= 90) {
    $attendance_status_class = 'good';
} elseif ($attendance_percentage >= 80) {
    $attendance_status_class = 'warning';
} else {
    $attendance_status_class = 'danger';
}

// คำนวณจำนวนวันที่ต้องมาเพิ่มเพื่อให้ผ่านเกณฑ์ (สมมติว่าเกณฑ์คือ 80%)
$attendance_threshold = 80; // เกณฑ์ผ่านกิจกรรม (%)
$days_needed = 0;

if ($attendance_percentage < $attendance_threshold && $total_days > 0) {
    // คำนวณจำนวนวันทั้งหมดที่ต้องมาเพื่อให้ได้เปอร์เซ็นต์ตามเกณฑ์
    $total_days_needed = ceil(($attendance_threshold * $total_days) / 100);
    $days_needed = $total_days_needed - $total_attendance_days;
}

$attendance_count = isset($selected_student['attendance_history']) ? count($selected_student['attendance_history']) : 0;
?>

<!-- CSS สำหรับหน้ารายละเอียดนักเรียน -->
<style>
/* CSS สำหรับหน้ารายละเอียดนักเรียนที่รองรับการแสดงผลบนมือถือ */

/* ปรับขนาดของคอนเทนเนอร์หลัก */
.container {
    width: 100%;
    max-width: 600px;
    margin: 70px auto 80px;
    padding: 10px;
    box-sizing: border-box;
}

/* ปุ่มย้อนกลับ */
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

/* ข้อมูลนักเรียน */
.student-profile {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

/* ปรับแต่งส่วนหัวของโปรไฟล์นักเรียน */
.student-profile-header {
    display: flex;
    align-items: center;
    padding: 0 0 15px 0;
    gap: 15px;
}

/* แก้ไขปัญหาของอวตารล้นจอ */
.student-avatar.large {
    width: 70px;
    height: 70px;
    min-width: 70px;
    font-size: 28px;
    margin-right: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    background-color: var(--primary-color);
    border-radius: 50%;
}

.student-profile-info {
    flex: 1;
    min-width: 0; /* ช่วยในการควบคุมการล้น */
}

/* ปรับปรุงการแสดงชื่อและข้อมูลนักเรียน */
.student-name {
    font-size: 20px;
    line-height: 1.2;
    margin-bottom: 8px;
    word-break: break-word;
    font-weight: 600;
    color: var(--text-dark);
}

.student-class-info, .student-id-info {
    font-size: 14px;
    line-height: 1.3;
    word-break: break-word;
    color: var(--text-light);
    margin-bottom: 3px;
}

/* ปรับปรุงการแสดงข้อมูลติดต่อ */
.student-contact-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 15px 0 0 0;
    border-top: 1px solid var(--border-color);
}

.info-item {
    display: flex;
    align-items: center;
    width: 100%;
    overflow: hidden;
    color: var(--text-light);
}

.info-item .material-icons {
    margin-right: 5px;
    font-size: 20px;
    color: var(--primary-color);
    min-width: 20px;
}

.info-item span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

/* ปรับปรุงปุ่มแท็บ */
.tab-button {
    flex: 1;
    padding: 12px 0;
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

/* ข้อมูลการเข้าแถว */
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
    display: flex;
    align-items: center;
}

.summary-title .material-icons {
    margin-right: 8px;
    font-size: 22px;
    color: var(--primary-color);
}

/* ปรับแต่งสรุปการเข้าแถว */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 12px 8px;
    background-color: var(--bg-light);
    border-radius: 10px;
    transition: transform 0.2s;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-value {
    font-size: 24px;
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
    font-size: 13px;
    color: var(--text-light);
}

/* ส่วนของกราฟสรุป */
.attendance-stats-card {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stats-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 10px;
    border-radius: 8px;
    background-color: var(--bg-light);
    transition: transform 0.2s;
}

.stats-item:hover {
    transform: translateY(-2px);
}

.stats-value {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stats-value.present {
    color: var(--success-color);
}

.stats-value.late {
    color: var(--warning-color);
}

.stats-value.absent {
    color: var(--danger-color);
}

.stats-value.leave {
    color: var(--text-muted);
}

.stats-label {
    font-size: 13px;
    color: var(--text-light);
    text-align: center;
}

.progress-container {
    width: 100%;
    background-color: #f0f0f0;
    border-radius: 10px;
    margin-top: 15px;
    overflow: hidden;
    height: 12px;
}

.progress-bar {
    height: 100%;
    border-radius: 10px;
}

.progress-bar.present {
    background-color: var(--success-color);
}

.progress-bar.late {
    background-color: var(--warning-color);
}

.progress-bar.absent {
    background-color: var(--danger-color);
}

.progress-bar.leave {
    background-color: var(--text-muted);
}

/* ส่วนตัวกรอง */
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
    margin-bottom: 15px;
    color: var(--text-dark);
    display: flex;
    align-items: center;
}

.filter-title .material-icons {
    margin-right: 8px;
    font-size: 20px;
    color: var(--primary-color);
}

/* ปรับปรุงส่วนของตัวกรองให้เป็นแนวนอน */
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
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-button.active, .filter-button:hover {
    background-color: var(--primary-color-light);
    color: var(--primary-color);
    border-color: var(--primary-color-light);
}

/* ประวัติการเข้าแถว */
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
    display: flex;
    align-items: center;
}

.history-title .material-icons {
    margin-right: 8px;
    font-size: 22px;
    color: var(--primary-color);
}

/* ส่วนของเดือน */
.month-section {
    margin-bottom: 25px;
}

.month-header {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-color-light);
}

/* ปรับปรุงรายการประวัติการเข้าแถว */
.attendance-item {
    position: relative;
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 8px;
    background-color: var(--bg-light);
    margin-bottom: 10px;
    transition: transform 0.2s;
}

.attendance-item:hover {
    transform: translateX(5px);
}

.attendance-date {
    min-width: 50px;
    text-align: center;
    margin-right: 15px;
}

.date-day {
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
    color: var(--primary-color);
}

.date-month {
    font-size: 12px;
    color: var(--text-light);
}

.date-full {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 2px;
}

.attendance-details {
    flex: 1;
    min-width: 0;
    padding-right: 30px;
}

.attendance-status-text {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
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

.attendance-status-text .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.attendance-time, .attendance-method {
    font-size: 12px;
    color: var(--text-light);
    display: flex;
    align-items: center;
    margin-bottom: 3px;
}

.attendance-method .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.attendance-remarks {
    font-size: 12px;
    color: var(--text-muted);
    font-style: italic;
    margin-top: 5px;
}

.attendance-status {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.attendance-status .material-icons {
    font-size: 24px;
}

.attendance-status.present .material-icons {
    color: var(--success-color);
}

.attendance-status.absent .material-icons {
    color: var(--danger-color);
}

.attendance-status.late .material-icons {
    color: var(--warning-color);
}

.attendance-status.leave .material-icons {
    color: var(--text-muted);
}

.no-attendance-data, .no-filter-results {
    text-align: center;
    padding: 30px;
    color: var(--text-light);
}

.no-attendance-data .material-icons, .no-filter-results .material-icons {
    font-size: 48px;
    margin-bottom: 10px;
    color: #ccc;
}

/* การ์ดคำแนะนำ */
.info-card {
    background-color: var(--primary-color-light);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
}

.info-card.warning {
    background-color: var(--warning-color-light);
    border-left-color: var(--warning-color);
}

.info-card.danger {
    background-color: var(--danger-color-light);
    border-left-color: var(--danger-color);
}

.info-card.success {
    background-color: var(--success-color-light);
    border-left-color: var(--success-color);
}

.info-card-title {
    display: flex;
    align-items: center;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--primary-color);
}

.info-card.warning .info-card-title {
    color: var(--warning-color);
}

.info-card.danger .info-card-title {
    color: var(--danger-color);
}

.info-card.success .info-card-title {
    color: var(--success-color);
}

.info-card-title .material-icons {
    margin-right: 8px;
}

.info-card-content {
    font-size: 14px;
    color: var(--text-dark);
    line-height: 1.5;
}

/* ครูที่ปรึกษา */
.teacher-profile {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

/* ปรับโครงสร้างข้อมูลครูที่ปรึกษาเป็นแนวตั้ง */
.teacher-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0 0 20px 0;
}

/* ปรับแต่งอวาตาร์ครู */
.teacher-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
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

/* ข้อมูลหลักของครูที่ปรึกษา */
.teacher-info {
    width: 100%;
    margin-bottom: 15px;
}

.teacher-name {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.teacher-position {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 15px;
    line-height: 1.4;
}

/* ช่องทางการติดต่อแบบทีละบรรทัด */
.teacher-contact {
    width: 100%;
    background-color: var(--bg-light);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.contact-title {
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 10px;
    color: var(--text-dark);
    text-align: left;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.contact-item:last-child {
    border-bottom: none;
}

.contact-item .material-icons {
    color: var(--primary-color);
    font-size: 20px;
    min-width: 30px;
}

.contact-label {
    width: 70px;
    font-weight: 500;
    font-size: 13px;
    color: var(--text-dark);
}

.contact-value {
    flex: 1;
    text-align: left;
    font-size: 14px;
    color: var(--text-light);
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ข้อมูลเพิ่มเติมครู */
.teacher-additional-info {
    background-color: var(--bg-light);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    text-align: left;
}

.teacher-additional-info-title {
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 15px;
    color: var(--text-dark);
}

.teacher-schedule {
    font-size: 13px;
    color: var(--text-light);
    line-height: 1.5;
}

.teacher-schedule p {
    margin-bottom: 8px;
    display: flex;
}

.teacher-schedule p:last-child {
    margin-bottom: 0;
}

.schedule-label {
    min-width: 100px;
    font-weight: 500;
    color: var(--text-dark);
}

/* ปุ่มดำเนินการ */
.teacher-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    width: 100%;
}

.teacher-action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    border-radius: 8px;
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

/* ปุ่มดูรายละเอียดเพิ่มเติม */
.view-more-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 12px;
    background-color: var(--primary-color-light);
    color: var(--primary-color);
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 10px;
    transition: background-color 0.2s;
}

.view-more-button:hover {
    background-color: #e4d0ed;
}

.view-more-button .material-icons {
    margin-left: 5px;
    font-size: 16px;
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 480px) {
    .student-name {
        font-size: 18px;
    }
    
    .student-class-info, .student-id-info {
        font-size: 13px;
    }
    
    .summary-title, .history-title, .filter-title {
        font-size: 16px;
    }
    
    .stat-value {
        font-size: 22px;
    }
    
    .stat-label {
        font-size: 12px;
    }
    
    .attendance-status-text {
        font-size: 13px;
    }
    
    .attendance-time, .attendance-method {
        font-size: 11px;
    }
    
    .date-full {
        font-size: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .stats-value {
        font-size: 18px;
    }
    
    .stats-label {
        font-size: 11px;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .filter-button {
        width: 100%;
        text-align: left;
    }
}

/* รองรับการแสดงผลบนหน้าจอขนาดเล็กมาก */
@media (max-width: 360px) {
    .student-profile-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .student-avatar.large {
        margin-bottom: 5px;
    }
    
    .student-contact-info {
        align-items: center;
    }
    
    .info-item {
        justify-content: center;
    }
    
    .summary-stats {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .attendance-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding-top: 35px;
    }
    
    .attendance-date {
        display: flex;
        width: 100%;
        justify-content: flex-start;
        gap: 5px;
        margin-bottom: 5px;
    }
    
    .date-day, .date-month {
        font-size: 14px;
    }
    
    .attendance-status {
        position: absolute;
        top: 10px;
        right: 10px;
        transform: none;
    }
    
    .attendance-details {
        padding-right: 0;
        width: 100%;
    }
    
    .teacher-avatar {
        width: 80px;
        height: 80px;
    }
    
    .teacher-name {
        font-size: 18px;
    }
    
    .contact-label {
        min-width: 60px;
    }
    
    .schedule-label {
        min-width: 90px;
    }
}

/* อนิเมชั่น */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.attendance-item, .month-section, .info-card {
    animation: fadeIn 0.3s ease-out;
}
</style>

<!-- ข้อความแสดงข้อมูล Debug สำหรับช่วยตรวจสอบ -->
<?php if(false): // เปลี่ยนเป็น true เพื่อแสดงข้อมูล Debug ?>
<div style="background-color: #f0f0f0; padding: 10px; margin-bottom: 10px; font-family: monospace; font-size: 12px;">
    <strong>Debug Info:</strong><br>
    total_attendance_days: <?php echo $total_attendance_days; ?><br>
    total_absence_days: <?php echo $total_absence_days; ?><br>
    total_days: <?php echo $total_days; ?><br>
    attendance_percentage: <?php echo $attendance_percentage; ?><br>
    <hr>
    <pre><?php print_r($selected_student); ?></pre>
</div>
<?php endif; ?>

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
    <!-- สถานะการเข้าแถว -->
    <?php if ($total_days > 0): ?>
        <?php if ($attendance_percentage < 80): ?>
            <div class="info-card danger">
                <div class="info-card-title">
                    <span class="material-icons">warning</span>
                    <span>แจ้งเตือน: เสี่ยงไม่ผ่านกิจกรรม</span>
                </div>
                <div class="info-card-content">
                    อัตราการเข้าแถวปัจจุบัน <?php echo number_format($attendance_percentage, 1); ?>% ต่ำกว่าเกณฑ์ที่กำหนด (80%)
                    <?php if ($days_needed > 0): ?>
                        จำเป็นต้องมาเข้าแถวอีก <?php echo $days_needed; ?> วัน เพื่อให้ผ่านเกณฑ์
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($attendance_percentage < 90): ?>
            <div class="info-card warning">
                <div class="info-card-title">
                    <span class="material-icons">info</span>
                    <span>ข้อควรระวัง</span>
                </div>
                <div class="info-card-content">
                    อัตราการเข้าแถวปัจจุบัน <?php echo number_format($attendance_percentage, 1); ?>% ควรระวังไม่ให้ขาดการเข้าแถวเพิ่มเติม
                </div>
            </div>
        <?php else: ?>
            <div class="info-card success">
                <div class="info-card-title">
                    <span class="material-icons">check_circle</span>
                    <span>สถานะปกติ</span>
                </div>
                <div class="info-card-content">
                    อัตราการเข้าแถวปัจจุบัน <?php echo number_format($attendance_percentage, 1); ?>% อยู่ในเกณฑ์ดี ผ่านเกณฑ์ที่กำหนด
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- สรุปการเข้าแถว -->
    <div class="attendance-summary-card">
        <div class="summary-title">
            <span class="material-icons">summarize</span>
            <span>สรุปการเข้าแถว</span>
        </div>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_attendance_days; ?></div>
                <div class="stat-label">วันที่เข้าแถว</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_absence_days; ?></div>
                <div class="stat-label">วันที่ขาดแถว</div>
            </div>
            <div class="stat-item">
                <div class="stat-value percentage <?php echo $attendance_status_class; ?>">
                    <?php echo number_format($attendance_percentage, 1); ?>%
                </div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
    </div>
    
    <!-- สถิติเพิ่มเติม (เฉพาะข้อมูลในประวัติที่แสดง) -->
    <?php if ($attendance_count > 0): ?>
    <div class="attendance-stats-card">
        <div class="summary-title">
            <span class="material-icons">bar_chart</span>
            <span>สถิติการเข้าแถว (จากประวัติล่าสุด)</span>
        </div>
        <div class="stats-grid">
            <div class="stats-item">
                <div class="stats-value present"><?php echo $present_count; ?></div>
                <div class="stats-label">มาปกติ</div>
                <?php if($attendance_count > 0): ?>
                <div class="progress-container">
                    <div class="progress-bar present" style="width: <?php echo ($present_count / $attendance_count) * 100; ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="stats-item">
                <div class="stats-value late"><?php echo $late_count; ?></div>
                <div class="stats-label">มาสาย</div>
                <?php if($attendance_count > 0): ?>
                <div class="progress-container">
                    <div class="progress-bar late" style="width: <?php echo ($late_count / $attendance_count) * 100; ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="stats-item">
                <div class="stats-value absent"><?php echo $absent_count; ?></div>
                <div class="stats-label">ขาดแถว</div>
                <?php if($attendance_count > 0): ?>
                <div class="progress-container">
                    <div class="progress-bar absent" style="width: <?php echo ($absent_count / $attendance_count) * 100; ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="stats-item">
                <div class="stats-value leave"><?php echo $leave_count; ?></div>
                <div class="stats-label">ลา</div>
                <?php if($attendance_count > 0): ?>
                <div class="progress-container">
                    <div class="progress-bar leave" style="width: <?php echo ($leave_count / $attendance_count) * 100; ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ตัวกรองข้อมูล -->
    <div class="attendance-filter">
        <div class="filter-title">
            <span class="material-icons">filter_list</span>
            <span>กรองข้อมูล</span>
        </div>
        <div class="filter-buttons">
            <button class="filter-button active" onclick="filterAttendance('all')">ทั้งหมด</button>
            <button class="filter-button" onclick="filterAttendance('month')">เดือนนี้</button>
            <button class="filter-button" onclick="filterAttendance('week')">สัปดาห์นี้</button>
            <button class="filter-button" onclick="filterAttendance('present')">เข้าแถว</button>
            <button class="filter-button" onclick="filterAttendance('absent')">ขาดแถว</button>
        </div>
    </div>
    
    <!-- ประวัติการเข้าแถว -->
    <div class="attendance-history">
        <div class="history-title">
            <span class="material-icons">history</span>
            <span>ประวัติการเข้าแถว</span>
        </div>
        
        <?php if(!empty($attendance_by_month)): ?>
            <?php foreach($attendance_by_month as $month => $month_items): ?>
                <div class="month-section" data-month="<?php echo $month; ?>">
                    <div class="month-header"><?php echo $month; ?></div>
                    
                    <?php foreach($month_items as $item): ?>
                        <div class="attendance-item" data-status="<?php echo $item['status']; ?>">
                            <div class="attendance-date">
                                <div class="date-day"><?php echo $item['day']; ?></div>
                                <div class="date-month"><?php echo $item['month_short']; ?></div>
                                <div class="date-full"><?php echo $item['day'] . ' ' . $item['month_short'] . ' ' . (date('Y') + 543); ?></div>
                            </div>
                            <div class="attendance-details">
                                <div class="attendance-status-text <?php echo $item['status']; ?>">
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
                                    <?php
                                    switch($item['status']) {
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
                                            echo 'ไม่ระบุ';
                                    }
                                    ?>
                                </div>
                                <?php if($item['present'] || $item['status'] == 'late'): ?>
                                    <div class="attendance-time">
                                        <span class="material-icons">access_time</span>
                                        เวลาเช็คชื่อ: <?php echo $item['time']; ?> น.
                                    </div>
                                    <div class="attendance-method">
                                        <span class="material-icons"><?php echo $item['method_icon']; ?></span>
                                        <span>วิธีเช็คชื่อ: <?php echo $item['method']; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($item['remarks']) && !empty($item['remarks'])): ?>
                                    <div class="attendance-remarks">
                                        <span class="material-icons">comment</span>
                                        <span>หมายเหตุ: <?php echo $item['remarks']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="attendance-status <?php echo $item['status']; ?>">
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
                </div>
            <?php endforeach; ?>
            
            <?php if(count($selected_student['attendance_history']) > 10): ?>
                <button class="view-more-button" id="view-more-attendance">
                    ดูข้อมูลการเข้าแถวเพิ่มเติม
                    <span class="material-icons">expand_more</span>
                </button>
            <?php endif; ?>
            
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
            <div class="teacher-card">
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
                </div>
            </div>
            
            <!-- ข้อมูลการติดต่อเป็นทีละบรรทัด -->
            <div class="teacher-contact">
                <div class="contact-title">ข้อมูลการติดต่อ</div>
                
                <div class="contact-item">
                    <span class="material-icons">phone</span>
                    <span class="contact-label">โทรศัพท์</span>
                    <span class="contact-value"><?php echo $selected_student['teacher']['phone']; ?></span>
                </div>
                
                <div class="contact-item">
                    <span class="material-icons">chat</span>
                    <span class="contact-label">LINE ID</span>
                    <span class="contact-value"><?php echo $selected_student['teacher']['line_id']; ?></span>
                </div>
                
                <div class="contact-item">
                    <span class="material-icons">email</span>
                    <span class="contact-label">อีเมล</span>
                    <span class="contact-value"><?php echo isset($selected_student['teacher']['email']) ? $selected_student['teacher']['email'] : 'ไม่ระบุ'; ?></span>
                </div>
                
                <div class="contact-item">
                    <span class="material-icons">location_on</span>
                    <span class="contact-label">ห้องพัก</span>
                    <span class="contact-value"><?php echo isset($selected_student['teacher']['room']) ? $selected_student['teacher']['room'] : 'ห้องพักครู'; ?></span>
                </div>
            </div>
            
            <!-- ข้อมูลเพิ่มเติมของครู -->
            <div class="teacher-additional-info">
                <div class="teacher-additional-info-title">ตารางการติดต่อครูที่ปรึกษา</div>
                <div class="teacher-schedule">
                    <p>
                        <span class="schedule-label">วันทำการ</span>
                        <span>จันทร์-ศุกร์ 08:00 - 16:30 น.</span>
                    </p>
                    <p>
                        <span class="schedule-label">ช่วงเวลาสะดวก</span>
                        <span>12:00 - 13:00 น. และ หลัง 16:30 น.</span>
                    </p>
                    <p>
                        <span class="schedule-label">หมายเหตุ</span>
                        <span>กรณีเร่งด่วนสามารถติดต่อผ่าน LINE ได้ตลอดเวลา</span>
                    </p>
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
            
        <?php else: ?>
            <div class="no-teacher-data">
                <span class="material-icons">person_off</span>
                <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript สำหรับการทำงานของหน้า -->
<script>
// เมื่อโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบ URL และตั้งค่าแท็บที่เหมาะสม
    checkActiveTab();
    
    // ปรับความสูงของเนื้อหาให้เหมาะสมกับหน้าจอ
    adjustContentHeight();
    
    // ตั้งค่าปุ่มดูเพิ่มเติม
    setupViewMoreButton();
});

// ตรวจสอบแท็บที่ควรเปิดจาก URL
function checkActiveTab() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab === 'teacher') {
        switchTab('teacher');
    }
}

// ปรับความสูงของเนื้อหาให้เหมาะสม
function adjustContentHeight() {
    const windowHeight = window.innerHeight;
    const headerHeight = document.querySelector('.header')?.offsetHeight || 0;
    const bottomNavHeight = document.querySelector('.bottom-nav')?.offsetHeight || 0;
    const backButtonHeight = document.querySelector('.back-button-container')?.offsetHeight || 0;
    const profileHeight = document.querySelector('.student-profile')?.offsetHeight || 0;
    const tabMenuHeight = document.querySelector('.tab-menu')?.offsetHeight || 0;
    
    // คำนวณความสูงที่เหลือสำหรับเนื้อหาแท็บ
    const availableHeight = windowHeight - headerHeight - bottomNavHeight - backButtonHeight - profileHeight - tabMenuHeight - 40; // 40px padding
    
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        // ตั้งค่าความสูงต่ำสุดสำหรับเนื้อหา
        content.style.minHeight = `${Math.max(availableHeight, 300)}px`;
    });
}

// ตั้งค่าปุ่มดูเพิ่มเติม
function setupViewMoreButton() {
    const viewMoreButton = document.getElementById('view-more-attendance');
    if (viewMoreButton) {
        // จำนวนเดือนที่แสดงเริ่มต้น
        let visibleMonths = 2;
        
        // ซ่อนเดือนที่เกินจำนวนที่กำหนด
        const monthSections = document.querySelectorAll('.month-section');
        if (monthSections.length > visibleMonths) {
            for (let i = visibleMonths; i < monthSections.length; i++) {
                monthSections[i].style.display = 'none';
            }
        }
        
        // ตั้งค่าการคลิกปุ่มดูเพิ่มเติม
        viewMoreButton.addEventListener('click', function() {
            const hiddenMonths = document.querySelectorAll('.month-section[style="display: none;"]');
            
            // แสดงเดือนเพิ่มทีละ 2 เดือน
            let count = 0;
            hiddenMonths.forEach(month => {
                if (count < 2) {
                    month.style.display = 'block';
                    count++;
                }
            });
            
            // ซ่อนปุ่มถ้าไม่มีเดือนที่ซ่อนอยู่แล้ว
            if (document.querySelectorAll('.month-section[style="display: none;"]').length === 0) {
                viewMoreButton.style.display = 'none';
            }
        });
    }
}

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
    
    // ปรับความสูงของเนื้อหาแท็บใหม่
    adjustContentHeight();
    
    // อัปเดต URL เพื่อให้สามารถรีเฟรชได้โดยอยู่ที่แท็บเดิม
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('id');
    if (studentId) {
        const newUrl = `students.php?id=${studentId}&tab=${tabName}`;
        history.replaceState(null, '', newUrl);
    }
}

// กรองข้อมูลการเข้าแถว
function filterAttendance(filterType) {
    // ตั้งค่าปุ่มกรอง
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => button.classList.remove('active'));
    
    // เลือกปุ่มที่ตรงกับประเภทการกรอง
    const clickedButton = Array.from(filterButtons).find(button => 
        button.textContent.trim().toLowerCase().includes(filterType) || 
        (filterType === 'all' && button.textContent.trim() === 'ทั้งหมด') ||
        (filterType === 'month' && button.textContent.trim() === 'เดือนนี้') ||
        (filterType === 'week' && button.textContent.trim() === 'สัปดาห์นี้') ||
        (filterType === 'present' && button.textContent.trim() === 'เข้าแถว') ||
        (filterType === 'absent' && button.textContent.trim() === 'ขาดแถว')
    );
    
    if(clickedButton) {
        clickedButton.classList.add('active');
    }
    
    // แสดงทุกเดือนก่อน
    const monthSections = document.querySelectorAll('.month-section');
    monthSections.forEach(section => {
        section.style.display = 'block';
    });
    
    // ซ่อนปุ่มดูเพิ่มเติมเมื่อกรอง
    const viewMoreButton = document.getElementById('view-more-attendance');
    if (viewMoreButton && filterType !== 'all') {
        viewMoreButton.style.display = 'none';
    } else if (viewMoreButton) {
        viewMoreButton.style.display = 'flex';
    }
    
    // กรองรายการเข้าแถว
    const attendanceItems = document.querySelectorAll('.attendance-item');
    
    if(filterType === 'all') {
        // แสดงทั้งหมด
        attendanceItems.forEach(item => {
            item.style.display = 'flex';
        });
        
        // เริ่มต้นการซ่อนเดือนที่เกินจำนวนที่กำหนดใหม่
        setupViewMoreButton();
    } else if(filterType === 'month') {
        // กรองตามเดือนปัจจุบัน
        const currentMonth = new Date().getMonth() + 1; // 1-12
        const currentYear = new Date().getFullYear() + 543; // ปี พ.ศ.
        const currentMonthThai = getThaiMonth(currentMonth) + ' ' + currentYear;
        
        monthSections.forEach(section => {
            const monthName = section.getAttribute('data-month');
            if(monthName === currentMonthThai) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
        
        // กรณีไม่พบเดือนปัจจุบัน ให้แสดงทุกเดือน
        const visibleMonths = document.querySelectorAll('.month-section[style="display: block;"]');
        if(visibleMonths.length === 0) {
            monthSections.forEach(section => {
                section.style.display = 'block';
            });
        }
    } else if(filterType === 'week') {
        // กรองตามสัปดาห์ปัจจุบัน (แสดงรายการล่าสุด 7 รายการ)
        let count = 0;
        attendanceItems.forEach(item => {
            if(count < 7) {
                item.style.display = 'flex';
                count++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // ซ่อนเดือนที่ไม่มีรายการแสดง
        monthSections.forEach(section => {
            const visibleItems = section.querySelectorAll('.attendance-item[style="display: flex;"]');
            if(visibleItems.length === 0) {
                section.style.display = 'none';
            }
        });
    } else if(filterType === 'present') {
        // กรองเฉพาะเข้าแถว (รวมมาปกติและมาสาย)
        attendanceItems.forEach(item => {
            const status = item.getAttribute('data-status');
            if(status === 'present' || status === 'late') {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
        
        // ซ่อนเดือนที่ไม่มีรายการแสดง
        monthSections.forEach(section => {
            const visibleItems = section.querySelectorAll('.attendance-item[style="display: flex;"]');
            if(visibleItems.length === 0) {
                section.style.display = 'none';
            }
        });
    } else if(filterType === 'absent') {
        // กรองเฉพาะขาดแถว
        attendanceItems.forEach(item => {
            const status = item.getAttribute('data-status');
            if(status === 'absent') {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
        
        // ซ่อนเดือนที่ไม่มีรายการแสดง
        monthSections.forEach(section => {
            const visibleItems = section.querySelectorAll('.attendance-item[style="display: flex;"]');
            if(visibleItems.length === 0) {
                section.style.display = 'none';
            }
        });
    }
    
    // เช็คว่ามีรายการที่แสดงหรือไม่
    checkEmptyFilterResults(filterType);
}

// ตรวจสอบว่ามีผลลัพธ์จากการกรองหรือไม่
function checkEmptyFilterResults(filterType) {
    const historySection = document.querySelector('.attendance-history');
    const visibleItems = document.querySelectorAll('.attendance-item[style="display: flex;"]');
    const visibleMonths = document.querySelectorAll('.month-section[style="display: block;"]');
    
    // หากไม่มีรายการที่แสดง ให้แสดงข้อความว่าไม่พบข้อมูล
    let noDataElement = document.querySelector('.no-filter-results');
    
    if(visibleItems.length === 0 || visibleMonths.length === 0) {
        if(!noDataElement) {
            noDataElement = document.createElement('div');
            noDataElement.className = 'no-filter-results';
            noDataElement.innerHTML = `
                <span class="material-icons">filter_alt_off</span>
                <p>ไม่พบข้อมูลตามเงื่อนไขที่เลือก</p>
            `;
            historySection.appendChild(noDataElement);
        }
    } else {
        if(noDataElement) {
            noDataElement.remove();
        }
    }
}

// แปลงเลขเดือนเป็นชื่อเดือนไทยย่อ
function getThaiMonth(month) {
    const thaiMonths = [
        'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];
    return thaiMonths[month - 1];
}

// รองรับการเปลี่ยนขนาดหน้าจอ
window.addEventListener('resize', function() {
    adjustContentHeight();
});
</script>
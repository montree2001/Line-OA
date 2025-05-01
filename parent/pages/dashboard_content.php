<!-- แจ้งเตือน -->
<?php if ($latest_check_in): ?>
    <div class="notification-banner">
        <span class="material-icons icon">check_circle</span>
        <div class="content">
            <div class="title">บุตรของท่านมาเรียนวันนี้</div>
            <div class="message"><?php echo $latest_check_in; ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- แท็บเมนู -->
<div class="tab-menu">
    <button class="tab-button active" onclick="switchTab('overview')">ภาพรวม</button>
    <button class="tab-button" onclick="switchTab('attendance')">การเข้าแถว</button>
    <button class="tab-button" onclick="switchTab('news')">ข่าวสาร</button>
</div>

<!-- เนื้อหาแท็บ Overview -->
<div id="overview-content" class="tab-content">
    <!-- ข้อมูลนักเรียน -->
    <div class="student-section">
        <div class="section-header">
            <h2>บุตรของฉัน</h2>
            <a href="students.php" class="view-all">ดูทั้งหมด</a>
        </div>

        <div class="student-cards">
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <div class="student-card">
                        <div class="header">
                            <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                            <div class="student-info">
                                <div class="student-name"><?php echo $student['name']; ?></div>
                                <div class="student-class"><?php echo $student['class']; ?> เลขที่ <?php echo $student['number']; ?></div>
                            </div>
                            <div class="student-status <?php echo $student['present'] === null ? '' : ($student['present'] ? '' : 'absent'); ?>">

                            </div>
                        </div>

                        <div class="attendance-details">
                            <div class="attendance-item">
                                <div class="attendance-label">จำนวนวันเข้าแถว:</div>
                                <div class="attendance-value"><?php echo $student['attendance_days']; ?> วัน</div>
                            </div>
                            <div class="attendance-item">
                                <div class="attendance-label">จำนวนวันขาดแถว:</div>
                                <div class="attendance-value"><?php echo $student['absent_days']; ?> วัน</div>
                            </div>
                            <div class="attendance-item">
                                <div class="attendance-label">อัตราการเข้าแถว:</div>
                                <div class="attendance-value <?php echo $student['attendance_percentage'] >= 90 ? 'good' : ($student['attendance_percentage'] >= 80 ? 'warning' : 'danger'); ?>">
                                    <?php echo $student['attendance_percentage']; ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <span class="material-icons">school</span>
                    </div>
                    <div class="no-data-message">ไม่พบข้อมูลนักเรียนในความดูแลของท่าน</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- กิจกรรมล่าสุด -->
    <div class="recent-activities">
        <div class="section-header">
            <h2>กิจกรรมล่าสุด</h2>
            <a href="activities.php" class="view-all">ดูทั้งหมด</a>
        </div>

        <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['type']; ?>">
                        <span class="material-icons"><?php echo $activity['icon']; ?></span>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo $activity['title']; ?></div>
                        <div class="activity-time"><?php echo $activity['time']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="activity-item">
                <div class="activity-icon other">
                    <span class="material-icons">info</span>
                </div>
                <div class="activity-content">
                    <div class="activity-title">ยังไม่มีกิจกรรมล่าสุด</div>
                    <div class="activity-time">-</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ติดต่อครูประจำชั้น -->
    <?php if (!empty($student_teachers)): ?>
        <?php foreach ($student_teachers as $student_data): ?>
            <div class="contact-teacher">
                <div class="section-header">
                    <h2>ครูที่ปรึกษาของ <?php echo $student_data['student_name']; ?></h2>
                    <span class="student-class-info"><?php echo $student_data['class_name']; ?></span>
                </div>

                <?php if (!empty($student_data['advisors'])): ?>
                    <?php foreach ($student_data['advisors'] as $advisor): ?>
                        <div class="teacher-info">
                            <div class="teacher-avatar">
                                <span class="material-icons">person</span>
                            </div>
                            <div class="teacher-details">
                                <div class="teacher-name"><?php echo $advisor['name']; ?></div>
                                <div class="teacher-position"><?php echo $advisor['position']; ?></div>
                            </div>
                        </div>

                        <div class="contact-buttons">
                            <button class="contact-button call" onclick="callTeacher('<?php echo $advisor['phone']; ?>')">
                                <span class="material-icons">call</span> โทร
                            </button>
                            <button class="contact-button message" onclick="messageTeacher(<?php echo $advisor['id']; ?>)">
                                <span class="material-icons">chat</span> ข้อความ
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-advisor-info">
                        <span class="material-icons">info</span>
                        <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="contact-teacher">
            <div class="section-header">
                <h2>ติดต่อครูประจำชั้น</h2>
            </div>

            <div class="no-advisor-info">
                <span class="material-icons">info</span>
                <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- เนื้อหาแท็บ Attendance -->
<div id="attendance-content" class="tab-content" style="display:none;">
    <div class="attendance-summary">
        <div class="section-header">
            <h2>สรุปการเข้าแถวของบุตรหลาน</h2>
        </div>

        <?php if (!empty($students)): ?>
            <div class="attendance-filter">
                <div class="filter-buttons">
                    <button class="filter-button active" onclick="filterAttendance('all')">ทั้งหมด</button>
                    <button class="filter-button" onclick="filterAttendance('month')">เดือนนี้</button>
                    <button class="filter-button" onclick="filterAttendance('week')">สัปดาห์นี้</button>
                </div>
            </div>

            <?php foreach ($students as $student): ?>
                <div class="attendance-detail-card">
                    <div class="student-header">
                        <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $student['name']; ?></div>
                            <div class="student-class"><?php echo $student['class']; ?></div>
                        </div>
                    </div>

                    <div class="attendance-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $student['attendance_days']; ?></div>
                            <div class="stat-label">วันที่เข้าแถว</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $student['absent_days']; ?></div>
                            <div class="stat-label">วันที่ขาดแถว</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value <?php echo $student['attendance_percentage'] >= 90 ? 'good' : ($student['attendance_percentage'] >= 80 ? 'warning' : 'danger'); ?>"><?php echo $student['attendance_percentage']; ?>%</div>
                            <div class="stat-label">อัตราการเข้าแถว</div>
                        </div>
                    </div>

                    <div class="attendance-history">
                        <h3>ประวัติการเข้าแถวล่าสุด</h3>
                        <div class="attendance-records">
                            <?php
                            // สร้างข้อมูลประวัติการเข้าแถว (ในการใช้งานจริงควรดึงจากฐานข้อมูล)
                            $sampleDates = [
                                date('Y-m-d', strtotime('today')),
                                date('Y-m-d', strtotime('yesterday')),
                                date('Y-m-d', strtotime('-2 days')),
                                date('Y-m-d', strtotime('-3 days')),
                                date('Y-m-d', strtotime('-4 days'))
                            ];

                            $statuses = ['present', 'present', 'present', 'absent', 'present'];

                            for ($i = 0; $i < 5; $i++):
                                $date = $sampleDates[$i];
                                $status = $statuses[$i];
                                $statusText = $status === 'present' ? 'เข้าแถว' : 'ขาดแถว';
                                $statusClass = $status === 'present' ? 'present' : 'absent';
                                $time = $status === 'present' ? '07:' . rand(30, 59) : '--:--';
                            ?>
                                <div class="record-item">
                                    <div class="record-date"><?php echo date('d/m/Y', strtotime($date)); ?></div>
                                    <div class="record-time"><?php echo $time; ?></div>
                                    <div class="record-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <span class="material-icons">event_busy</span>
                </div>
                <div class="no-data-message">ไม่พบข้อมูลการเข้าแถวของนักเรียน</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- เนื้อหาแท็บ News -->
<div id="news-content" class="tab-content" style="display:none;">
    <!-- ประกาศและข่าวสาร -->
    <div class="announcements-full">
        <div class="section-header">
            <h2>ประกาศและข่าวสารทั้งหมด</h2>
        </div>

        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item">
                    <div class="announcement-header">
                        <div class="announcement-category <?php echo $announcement['category_class']; ?>"><?php echo $announcement['category']; ?></div>
                        <div class="announcement-date"><?php echo $announcement['date']; ?></div>
                    </div>
                    <div class="announcement-title"><?php echo $announcement['title']; ?></div>
                    <div class="announcement-text"><?php echo $announcement['content']; ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="announcement-item">
                <div class="announcement-header">
                    <div class="announcement-category">ข้อมูล</div>
                    <div class="announcement-date"><?php echo date('d M Y'); ?></div>
                </div>
                <div class="announcement-title">ไม่มีประกาศในขณะนี้</div>
                <div class="announcement-text">ยังไม่มีประกาศล่าสุดในระบบ</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .no-data {
        text-align: center;
        padding: 40px 0;
        color: var(--text-muted);
    }

    .no-data-icon .material-icons {
        font-size: 48px;
        margin-bottom: 16px;
    }

    .no-data-message {
        font-size: 16px;
        font-weight: 500;
    }

    .no-advisor-info {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background-color: var(--bg-light);
        border-radius: 8px;
        color: var(--text-muted);
        margin: 15px 0;
    }

    .no-advisor-info .material-icons {
        margin-right: 10px;
        font-size: 24px;
    }

    .student-class-info {
        font-size: 14px;
        color: var(--text-light);
        font-weight: normal;
        margin-left: 10px;
    }

    /* ส่วนของแท็บ Attendance */
    .attendance-filter {
        background-color: white;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 15px;
        box-shadow: var(--card-shadow);
    }

    .filter-buttons {
        display: flex;
        justify-content: space-between;
    }

    .filter-button {
        flex: 1;
        padding: 8px;
        background: var(--bg-light);
        border: none;
        border-radius: 5px;
        margin: 0 5px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .filter-button.active {
        background-color: var(--primary-color);
        color: white;
    }

    .attendance-detail-card {
        background-color: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: var(--card-shadow);
    }

    .student-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .attendance-stats {
        display: flex;
        justify-content: space-between;
        background-color: var(--bg-light);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .stat-item {
        text-align: center;
        flex: 1;
    }

    .stat-value {
        font-size: 20px;
        font-weight: 600;
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

    .attendance-history h3 {
        font-size: 14px;
        margin-bottom: 10px;
        color: var(--text-dark);
    }

    .record-item {
        display: flex;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .record-item:last-child {
        border-bottom: none;
    }

    .record-date {
        width: 30%;
        font-size: 14px;
    }

    .record-time {
        width: 30%;
        font-size: 14px;
    }

    .record-status {
        width: 40%;
        font-size: 14px;
        font-weight: 500;
        text-align: right;
    }

    .record-status.present {
        color: var(--success-color);
    }

    .record-status.absent {
        color: var(--danger-color);
    }

    /* ส่วนของแท็บ News */
    .announcements-full .announcement-item {
        margin-bottom: 20px;
    }

    .announcements-full .announcement-text {
        -webkit-line-clamp: unset;
        max-height: none;
        overflow: visible;
    }
</style>
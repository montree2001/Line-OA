<div class="header">
    <div class="app-name">STD-Prasat</div>
    <div class="header-icons">
        <span class="material-icons">notifications</span>
        <span class="material-icons" id="userMenuToggle">account_circle</span>
    </div>
</div>

<div class="container">
    <!-- การ์ดแจ้งเตือน -->
    <div class="alert-card">
        <span class="material-icons alert-icon">warning</span>
        <div class="alert-content">
            <div class="alert-title"><?php echo $alert['title']; ?></div>
            <div class="alert-message"><?php echo $alert['message']; ?></div>
        </div>
    </div>

    <!-- โปรไฟล์และสถิติ -->
    <div class="profile-card">
        <div class="profile-header">
            <?php if (!empty($student_info['profile_picture'])): ?>
                <div class="profile-image profile-image-pic" style="background-image: url('<?php echo $student_info['profile_picture']; ?>');"></div>
            <?php else: ?>
                <div class="profile-image"><?php echo $first_char; ?></div>
            <?php endif; ?>
            <div class="profile-info">
                <div class="profile-name"><?php echo $student_info['name']; ?></div>
                <div class="profile-details"><?php echo $student_info['class']; ?></div>
                <div class="profile-status <?php echo $attendance_status['status_class']; ?>">
                    <span class="material-icons"><?php echo $attendance_status['status_icon']; ?></span> 
                    <?php echo $attendance_status['status_text']; ?>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $attendance_stats['required_days']; ?></div>
                <div class="stat-label">วันที่ต้องเข้าแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $attendance_stats['attendance_days']; ?></div>
                <div class="stat-label">วันเข้าแถวแล้ว</div>
            </div>
            <div class="stat-box">
                <?php
                $color_class = '';
                if ($attendance_stats['attendance_percentage'] >= 80) {
                    $color_class = 'good';
                } elseif ($attendance_stats['attendance_percentage'] >= 70) {
                    $color_class = 'warning';
                } else {
                    $color_class = 'danger';
                }
                ?>
                <div class="stat-value <?php echo $color_class; ?>"><?php echo $attendance_stats['attendance_percentage']; ?>%</div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
    </div>

    <!-- ปุ่มเช็คชื่อ -->
    <button class="check-in-button" onclick="location.href='check-in.php'" <?php echo $attendance_status['is_checked_in'] ? 'disabled' : ''; ?>>
        <span class="material-icons"><?php echo $attendance_status['is_checked_in'] ? 'check_circle' : 'how_to_reg'; ?></span>
        <?php echo $attendance_status['is_checked_in'] ? 'เช็คชื่อแล้ววันนี้' : 'เช็คชื่อเข้าแถววันนี้'; ?>
    </button>

    <!-- ปุ่มกิจกรรม -->
    <button class="activity-button" onclick="location.href='activities.php'">
        <span class="material-icons">event</span> ดูกิจกรรมทั้งหมด
    </button>

    <!-- ประวัติการเช็คชื่อล่าสุด -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span class="material-icons">history</span> ประวัติการเช็คชื่อล่าสุด
            </div>
            <a href="history.php" class="view-all">ดูทั้งหมด</a>
        </div>
        
        <ul class="history-list">
            <?php if (empty($check_in_history)): ?>
                <div class="empty-history">ยังไม่มีประวัติการเช็คชื่อ</div>
            <?php else: ?>
                <?php foreach ($check_in_history as $history): ?>
                    <li class="history-item">
                        <div class="history-date">
                            <div class="history-day"><?php echo $history['day']; ?></div>
                            <div class="history-month"><?php echo $history['month']; ?></div>
                        </div>
                        <div class="history-content">
                            <div class="history-status">
                                <div class="status-dot <?php echo $history['status']; ?>"></div>
                                <div class="history-status-text"><?php echo $history['status_text']; ?></div>
                            </div>
                            <div class="history-time">เช็คชื่อเวลา <?php echo $history['time']; ?> น.</div>
                            <div class="history-method">
                                <span class="material-icons"><?php echo $history['method_icon']; ?></span> 
                                <?php echo $history['method']; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

<!-- ประกาศจากวิทยาลัย -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span class="material-icons">campaign</span> ประกาศจากวิทยาลัย
        </div>
        <a href="announcements.php" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <ul class="announcement-list">
        <?php if (empty($announcements)): ?>
            <div class="empty-announcements">ยังไม่มีประกาศในขณะนี้</div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <li class="announcement-item">
                    <?php 
                    // ตรวจสอบและรับประกันว่ามีค่า ID (สำหรับประกาศจริงและตัวอย่าง)
                    $announcement_id = isset($announcement['id']) ? $announcement['id'] : 'no-id';
                    ?>
                    <a href="view_announcement.php?id=<?php echo $announcement_id; ?>" class="announcement-link">
                        <div class="announcement-title">
                            <span class="announcement-badge badge-<?php echo $announcement['badge']; ?>"><?php echo $announcement['badge_text']; ?></span>
                            <?php echo $announcement['title']; ?>
                        </div>
                        <div class="announcement-content">
                            <?php echo $announcement['content']; ?>
                        </div>
                        <div class="announcement-footer">
                            <div class="announcement-date">
                                <span class="material-icons">event</span> <?php echo $announcement['date']; ?>
                            </div>
                            <div class="read-more">
                                อ่านเพิ่มเติม <span class="material-icons">arrow_forward</span>
                            </div>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

</div>

<style>
/* เพิ่มสไตล์สำหรับปุ่มกิจกรรม */
.activity-button {
    background-color: #2196f3;
    color: white;
    border: none;
    border-radius: 15px;
    padding: 15px;
    font-size: 16px;
    font-weight: bold;
    width: 100%;
    margin-bottom: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s;
}

.activity-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.activity-button .material-icons {
    margin-right: 10px;
}

/* ปรับแต่งปุ่มเช็คชื่อให้เมเข้ากับปุ่มกิจกรรม */
.check-in-button {
    margin-bottom: 10px;
}
</style>
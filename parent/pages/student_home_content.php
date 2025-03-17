<div class="header">
        <div class="app-name">STD-Prasat</div>
        <div class="header-icons">
            <span class="material-icons">notifications</span>
            <span class="material-icons">account_circle</span>
          
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
            <div class="profile-image"><?php echo $student_info['avatar']; ?></div>
            <div class="profile-info">
                <div class="profile-name"><?php echo $student_info['name']; ?></div>
                <div class="profile-details"><?php echo $student_info['class']; ?> เลขที่ <?php echo $student_info['number']; ?></div>
                <div class="profile-status status-present">
                    <span class="material-icons">check_circle</span> เข้าแถวแล้ววันนี้
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $attendance_stats['total_days']; ?></div>
                <div class="stat-label">วันเรียนทั้งหมด</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $attendance_stats['attendance_days']; ?></div>
                <div class="stat-label">วันเข้าแถว</div>
            </div>
            <div class="stat-box">
                <div class="stat-value good"><?php echo $attendance_stats['attendance_percentage']; ?>%</div>
                <div class="stat-label">อัตราการเข้าแถว</div>
            </div>
        </div>
    </div>

    <!-- ปุ่มเช็คชื่อ -->
     <a href="check-in.php" class="check-in-button">
        
    <button class="check-in-button">
        <span class="material-icons">how_to_reg</span> เช็คชื่อเข้าแถววันนี้
    </button>
     </a>

    <!-- ประวัติการเช็คชื่อล่าสุด -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span class="material-icons">history</span> ประวัติการเช็คชื่อล่าสุด
            </div>
            <a href="history.php" class="view-all">ดูทั้งหมด</a>
        </div>
        
        <ul class="history-list">
            <?php foreach ($check_in_history as $history): ?>
            <li class="history-item">
                <div class="history-date">
                    <div class="history-day"><?php echo $history['day']; ?></div>
                    <div class="history-month"><?php echo $history['month']; ?></div>
                </div>
                <div class="history-content">
                    <div class="history-status">
                        <div class="status-dot present"></div>
                        <div class="history-status-text">เข้าแถว</div>
                    </div>
                    <div class="history-time">เช็คชื่อเวลา <?php echo $history['time']; ?> น.</div>
                    <div class="history-method">
                        <?php 
                        $icon = 'how_to_reg';
                        if ($history['method'] == 'GPS') {
                            $icon = 'gps_fixed';
                        } elseif ($history['method'] == 'PIN') {
                            $icon = 'pin';
                        } elseif ($history['method'] == 'QR Code') {
                            $icon = 'qr_code_scanner';
                        }
                        ?>
                        <span class="material-icons"><?php echo $icon; ?></span> เช็คชื่อด้วย<?php echo $history['method']; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- ประกาศจากโรงเรียน -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span class="material-icons">campaign</span> ประกาศจากโรงเรียน
            </div>
            <a href="#" class="view-all">ดูทั้งหมด</a>
        </div>
        
        <ul class="announcement-list">
            <?php foreach ($announcements as $announcement): ?>
            <li class="announcement-item">
                <div class="announcement-title">
                    <span class="announcement-badge badge-<?php echo $announcement['badge']; ?>">
                        <?php echo $announcement['badge_text']; ?>
                    </span>
                    <?php echo $announcement['title']; ?>
                </div>
                <div class="announcement-content">
                    <?php echo $announcement['content']; ?>
                </div>
                <div class="announcement-date">
                    <span class="material-icons">event</span> <?php echo $announcement['date']; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
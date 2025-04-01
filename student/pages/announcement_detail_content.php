<div class="header">
    <button class="back-button" onclick="history.back()">
        <span class="material-icons">arrow_back</span>
    </button>
    <div class="header-title">รายละเอียดประกาศ</div>
    <div class="header-spacer"></div>
</div>

<div class="container">
    <div class="announcement-detail-card">
        <div class="announcement-badge badge-<?php echo $announcement_data['badge']; ?>">
            <?php echo $announcement_data['badge_text']; ?>
        </div>
        
        <h1 class="announcement-title"><?php echo $announcement_data['title']; ?></h1>
        
        <div class="announcement-meta">
            <div class="announcement-date">
                <span class="material-icons">event</span>
                <?php echo $announcement_data['date']; ?>
            </div>
            <div class="announcement-creator">
                <span class="material-icons">person</span>
                <?php echo $announcement_data['creator']; ?>
            </div>
        </div>
        
        <div class="announcement-content">
            <?php echo nl2br($announcement_data['content']); ?>
        </div>
        
        <div class="action-buttons">
            <button class="btn secondary" onclick="history.back()">
                <span class="material-icons">arrow_back</span> กลับ
            </button>
            <?php if (isset($_GET['from']) && $_GET['from'] === 'home'): ?>
            <a href="announcements.php" class="btn primary">
                <span class="material-icons">list</span> ดูประกาศทั้งหมด
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
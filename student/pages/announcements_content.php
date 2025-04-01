<div class="header">
    <button class="back-button" onclick="history.back()">
        <span class="material-icons">arrow_back</span>
    </button>
    <div class="header-title">ประกาศทั้งหมด</div>
    <div class="header-spacer"></div>
</div>

<div class="container">
    <?php if (empty($announcements)): ?>
        <div class="empty-announcements">
            <span class="material-icons empty-icon">campaign</span>
            <div class="empty-message">ยังไม่มีประกาศในขณะนี้</div>
            <button class="btn secondary" onclick="history.back()">
                <span class="material-icons">arrow_back</span> กลับหน้าหลัก
            </button>
        </div>
    <?php else: ?>
        <div class="announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <a href="announcement_detail.php?id=<?php echo $announcement['id']; ?>" class="announcement-link">
                        <div class="announcement-badge badge-<?php echo $announcement['badge']; ?>">
                            <?php echo $announcement['badge_text']; ?>
                        </div>
                        <h2 class="announcement-title"><?php echo $announcement['title']; ?></h2>
                        <div class="announcement-meta">
                            <div class="announcement-date">
                                <span class="material-icons">event</span> <?php echo $announcement['date']; ?>
                            </div>
                            <div class="announcement-creator">
                                <span class="material-icons">person</span> <?php echo $announcement['creator']; ?>
                            </div>
                        </div>
                        <div class="announcement-content">
                            <?php echo $announcement['content']; ?>
                        </div>
                        <div class="read-more">
                            อ่านเพิ่มเติม <span class="material-icons">arrow_forward</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
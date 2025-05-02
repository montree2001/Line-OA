<?php
// ตรวจสอบว่าเป็นการดูรายละเอียดประกาศหรือไม่
if (isset($selected_announcement)) {
    // แสดงรายละเอียดของประกาศที่เลือก
?>
    <!-- แสดงรายละเอียดประกาศ -->
    <div class="back-section">
        <a href="announcements.php" class="back-button">
            <span class="material-icons">arrow_back</span>
            กลับไปหน้าประกาศ
        </a>
    </div>

    <div class="announcement-detail">
        <div class="announcement-header">
            <div class="announcement-meta">
                <div class="announcement-category <?php echo $selected_announcement['category_class']; ?>">
                    <?php echo $selected_announcement['category_name']; ?>
                </div>
                <div class="announcement-date">
                    <span class="material-icons">event</span>
                    <?php echo $selected_announcement['formatted_date']; ?>
                </div>
            </div>
            <h1 class="announcement-title"><?php echo $selected_announcement['title']; ?></h1>
            <div class="announcement-author">
                <span class="material-icons">person</span>
                ประกาศโดย: <?php echo $selected_announcement['title'] . ' ' . $selected_announcement['first_name'] . ' ' . $selected_announcement['last_name']; ?>
            </div>
        </div>

        <div class="announcement-content">
            <?php 
            // แปลง newlines เป็น <br> เพื่อแสดงผลบรรทัดใหม่ในเนื้อหาประกาศ
            echo nl2br(htmlspecialchars($selected_announcement['content'])); 
            ?>
        </div>
    </div>
<?php
} else {
    // แสดงรายการประกาศทั้งหมด
?>
    <!-- ส่วนการค้นหาและกรอง -->
    <div class="search-filter-section">
        <form method="get" class="search-form">
            <div class="search-input">
                <span class="material-icons">search</span>
                <input type="text" name="search" placeholder="ค้นหาประกาศ..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['type']) && $_GET['type'] != 'all'): ?>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($_GET['type']); ?>">
                <?php endif; ?>
                <button type="submit" class="search-button">
                    <span class="material-icons">search</span>
                </button>
            </div>
        </form>

        <div class="filter-buttons">
            <a href="announcements.php<?php echo isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''; ?>" class="filter-button <?php echo (!isset($_GET['type']) || $_GET['type'] == 'all') ? 'active' : ''; ?>">
                ทั้งหมด
            </a>
            <a href="announcements.php?type=important<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="filter-button important <?php echo (isset($_GET['type']) && $_GET['type'] == 'important') ? 'active' : ''; ?>">
                สำคัญ
            </a>
            <a href="announcements.php?type=event<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="filter-button event <?php echo (isset($_GET['type']) && $_GET['type'] == 'event') ? 'active' : ''; ?>">
                กิจกรรม
            </a>
            <a href="announcements.php?type=exam<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="filter-button exam <?php echo (isset($_GET['type']) && $_GET['type'] == 'exam') ? 'active' : ''; ?>">
                สอบ
            </a>
            <a href="announcements.php?type=general<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="filter-button <?php echo (isset($_GET['type']) && $_GET['type'] == 'general') ? 'active' : ''; ?>">
                ทั่วไป
            </a>
        </div>
    </div>

    <!-- แสดงข้อความแจ้งเตือนผลการค้นหา -->
    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
        <div class="search-result-message">
            <span class="material-icons">info</span>
            ผลการค้นหาสำหรับ "<strong><?php echo htmlspecialchars($_GET['search']); ?></strong>" พบทั้งหมด <?php echo $total_items; ?> รายการ
        </div>
    <?php endif; ?>

    <!-- แสดงรายการประกาศ -->
    <div class="announcements-list">
        <?php if (!empty($announcements)): ?>
            <?php foreach($announcements as $announcement): ?>
                <div class="announcement-item">
                    <div class="announcement-header">
                        <div class="announcement-category <?php echo $announcement['category_class']; ?>">
                            <?php echo $announcement['category_name']; ?>
                        </div>
                        <div class="announcement-date">
                            <span class="material-icons">event</span>
                            <?php echo $announcement['formatted_date']; ?>
                        </div>
                    </div>
                    <h2 class="announcement-title">
                        <a href="announcements.php?id=<?php echo $announcement['announcement_id']; ?>">
                            <?php echo $announcement['title']; ?>
                        </a>
                    </h2>
                    <div class="announcement-excerpt">
                        <?php 
                        // ตัดเนื้อหาให้ความยาวไม่เกิน 200 ตัวอักษร
                        $excerpt = strip_tags($announcement['short_content']);
                        if (strlen($excerpt) > 200) {
                            $excerpt = substr($excerpt, 0, 197) . '...';
                        }
                        echo nl2br(htmlspecialchars($excerpt)); 
                        ?>
                    </div>
                    <div class="announcement-actions">
                        <a href="announcements.php?id=<?php echo $announcement['announcement_id']; ?>" class="read-more-button">
                            <span class="material-icons">visibility</span>
                            อ่านเพิ่มเติม
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- การแบ่งหน้า -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="announcements.php?page=<?php echo ($page - 1); ?><?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="pagination-button prev">
                            <span class="material-icons">chevron_left</span>
                            ก่อนหน้า
                        </a>
                    <?php endif; ?>

                    <div class="pagination-pages">
                        <?php
                        // แสดงเลขหน้าแบบย่อ
                        $range = 2; // จำนวนหน้าที่แสดงข้างๆ หน้าปัจจุบัน
                        
                        // แสดงหน้าแรก
                        if ($page > $range + 1) {
                            echo '<a href="announcements.php?page=1';
                            echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '';
                            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                            echo '" class="pagination-page">1</a>';
                            
                            if ($page > $range + 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        // แสดงช่วงหน้า
                        for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
                            echo '<a href="announcements.php?page=' . $i;
                            echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '';
                            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                            echo '" class="pagination-page ' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                        }
                        
                        // แสดงหน้าสุดท้าย
                        if ($page < $total_pages - $range) {
                            if ($page < $total_pages - $range - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            
                            echo '<a href="announcements.php?page=' . $total_pages;
                            echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '';
                            echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                            echo '" class="pagination-page">' . $total_pages . '</a>';
                        }
                        ?>
                    </div>

                    <?php if ($page < $total_pages): ?>
                        <a href="announcements.php?page=<?php echo ($page + 1); ?><?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="pagination-button next">
                            ถัดไป
                            <span class="material-icons">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-announcements">
                <div class="no-data-icon">
                    <span class="material-icons">campaign_off</span>
                </div>
                <div class="no-data-message">
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        ไม่พบประกาศที่ตรงกับการค้นหา "<?php echo htmlspecialchars($_GET['search']); ?>"
                    <?php elseif (isset($_GET['type']) && $_GET['type'] != 'all'): ?>
                        ไม่พบประกาศประเภท "<?php 
                        switch ($_GET['type']) {
                            case 'important':
                                echo 'สำคัญ';
                                break;
                            case 'event':
                                echo 'กิจกรรม';
                                break;
                            case 'exam':
                                echo 'สอบ';
                                break;
                            default:
                                echo 'ทั่วไป';
                        }
                        ?>"
                    <?php else: ?>
                        ไม่พบประกาศหรือข่าวสารในขณะนี้
                    <?php endif; ?>
                </div>
                <div class="no-data-action">
                    <a href="announcements.php" class="reset-search-button">
                        <span class="material-icons">refresh</span>
                        แสดงประกาศทั้งหมด
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php 
} // ปิด else ของการตรวจสอบว่าเป็นการดูรายละเอียดประกาศหรือไม่
?>

<style>
/* สไตล์สำหรับหน้าประกาศ */
.search-filter-section {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.search-form {
    margin-bottom: 15px;
}

.search-input {
    display: flex;
    align-items: center;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 0 15px;
    overflow: hidden;
}

.search-input input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px;
    font-size: 15px;
    outline: none;
}

.search-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.filter-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-light);
    background-color: var(--bg-light);
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.filter-button:hover {
    background-color: #e8e8e8;
}

.filter-button.active {
    background-color: var(--primary-color);
    color: white;
}

.filter-button.important {
    color: var(--danger-color);
}

.filter-button.important.active {
    background-color: var(--danger-color);
    color: white;
}

.filter-button.event {
    color: var(--secondary-color);
}

.filter-button.event.active {
    background-color: var(--secondary-color);
    color: white;
}

.filter-button.exam {
    color: var(--warning-color);
}

.filter-button.exam.active {
    background-color: var(--warning-color);
    color: white;
}

.search-result-message {
    display: flex;
    align-items: center;
    background-color: var(--secondary-color-light);
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    color: var(--secondary-color);
}

.search-result-message .material-icons {
    margin-right: 8px;
}

.announcement-item {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s, box-shadow 0.2s;
}

.announcement-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.announcement-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.announcement-category {
    font-size: 12px;
    font-weight: 600;
    color: white;
    background-color: var(--warning-color);
    padding: 3px 8px;
    border-radius: 12px;
    margin-right: 10px;
}

.announcement-category.event {
    background-color: var(--secondary-color);
}

.announcement-category.exam {
    background-color: var(--danger-color);
}

.announcement-category.important {
    background-color: var(--primary-color-dark);
}

.announcement-date {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: var(--text-muted);
}

.announcement-date .material-icons {
    font-size: 14px;
    margin-right: 4px;
}

.announcement-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
}

.announcement-title a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.3s ease;
}

.announcement-title a:hover {
    color: var(--primary-color);
}

.announcement-excerpt {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 15px;
    line-height: 1.5;
}

.announcement-actions {
    display: flex;
    justify-content: flex-end;
}

.read-more-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--primary-color);
    background-color: var(--primary-color-light);
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.read-more-button:hover {
    background-color: #e8dbef;
}

.read-more-button .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 30px;
    margin-bottom: 20px;
}

.pagination-button, .pagination-page {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    background-color: white;
    text-decoration: none;
    margin: 0 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.pagination-button {
    color: var(--primary-color);
}

.pagination-page.active {
    background-color: var(--primary-color);
    color: white;
}

.pagination-button:hover, .pagination-page:hover {
    background-color: var(--bg-light);
}

.pagination-pages {
    display: flex;
    align-items: center;
}

.pagination-ellipsis {
    margin: 0 5px;
    color: var(--text-light);
}

.no-announcements {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #e0e0e0;
}

.no-data-message {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 15px;
}

.no-data-action {
    margin-top: 20px;
}

.reset-search-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
}

.reset-search-button .material-icons {
    margin-right: 5px;
}

/* ส่วนสำหรับหน้ารายละเอียดประกาศ */
.back-section {
    margin-bottom: 20px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.back-button:hover {
    color: var(--primary-color-dark);
}

.back-button .material-icons {
    margin-right: 5px;
}

.announcement-detail {
    background-color: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: var(--card-shadow);
}

.announcement-detail .announcement-header {
    margin-bottom: 25px;
    flex-direction: column;
    align-items: flex-start;
}

.announcement-detail .announcement-meta {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.announcement-detail .announcement-title {
    font-size: 24px;
    margin-bottom: 15px;
}

.announcement-author {
    display: flex;
    align-items: center;
    font-size: 14px;
    color: var(--text-light);
}

.announcement-author .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.announcement-content {
    font-size: 16px;
    line-height: 1.6;
    color: var(--text-dark);
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .filter-buttons {
        flex-wrap: nowrap;
        gap: 8px;
    }
    
    .filter-button {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .announcement-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .announcement-category {
        margin-right: 0;
    }
    
    .announcement-title {
        font-size: 16px;
    }
    
    .announcement-detail .announcement-title {
        font-size: 20px;
    }
    
    .pagination {
        flex-wrap: wrap;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .search-input {
        flex-direction: row;
        padding: 5px 10px;
    }
    
    .search-button {
        padding: 8px;
    }
    
    .filter-buttons {
        overflow-x: auto;
        padding-bottom: 10px;
        -webkit-overflow-scrolling: touch;
    }
    
    .announcement-detail {
        padding: 15px;
    }
    
    .announcement-content {
        font-size: 14px;
    }
    
    .pagination-button, .pagination-page {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .pagination-pages {
        max-width: 150px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    
    .pagination-pages::-webkit-scrollbar {
        display: none;
    }
}
</style>

<script>
// JavaScript สำหรับจัดการเมนูกรอง
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าการเลื่อนแบบนุ่มนวลสำหรับ filter buttons
    const filterButtonsContainer = document.querySelector('.filter-buttons');
    if (filterButtonsContainer) {
        // ตรวจสอบว่ามีปุ่มที่เลือกอยู่หรือไม่
        const activeButton = filterButtonsContainer.querySelector('.filter-button.active');
        if (activeButton) {
            // เลื่อนไปที่ปุ่มที่เลือก
            setTimeout(() => {
                activeButton.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }, 100);
        }
    }
    
    // ตั้งค่าการคลิกที่ announcement-item
    const announcementItems = document.querySelectorAll('.announcement-item');
    announcementItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // ถ้าคลิกที่ปุ่มอ่านเพิ่มเติม ให้ดำเนินการตามปกติ
            if (e.target.closest('.read-more-button')) {
                return;
            }
            
            // ถ้าคลิกที่ส่วนอื่น ให้ไปที่หน้ารายละเอียด
            const titleLink = this.querySelector('.announcement-title a');
            if (titleLink) {
                window.location.href = titleLink.getAttribute('href');
            }
        });
    });
});
</script>
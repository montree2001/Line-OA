<!-- เนื้อหาหลัก -->
<div class="main-content" id="mainContent">
    <div class="header">
        <h1 class="page-title"><?php echo isset($page_header) ? $page_header : 'หน้าหลัก'; ?></h1>
        <div class="header-actions">
            <?php if(!isset($hide_search) || !$hide_search): ?>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="<?php echo isset($search_placeholder) ? $search_placeholder : 'ค้นหา...'; ?>">
                <button class="search-button">
                    <span class="material-icons">search</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($header_buttons) && is_array($header_buttons)): ?>
                <?php foreach($header_buttons as $button): ?>
                    <button class="action-button" <?php echo isset($button['id']) ? 'id="'.$button['id'].'"' : ''; ?> <?php echo isset($button['onclick']) ? 'onclick="'.$button['onclick'].'"' : ''; ?>>
                        <?php if(isset($button['icon'])): ?>
                            <span class="material-icons"><?php echo $button['icon']; ?></span>
                        <?php endif; ?>
                        <?php echo $button['text']; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php 
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>
    <!-- เนื้อหาเฉพาะหน้า -->
    <div class="content">
        <?php include $content_path; ?>
    </div>
</div>
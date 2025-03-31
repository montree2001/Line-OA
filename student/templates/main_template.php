<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title : 'STD-Prasat - ระบบเช็คชื่อเข้าแถวออนไลน์'; ?></title>
    
    <!-- Material Icons & Google Fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/stlye.css" rel="stylesheet">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- ตรวจสอบไฟล์ CSS เฉพาะหน้า -->
    <?php
    $content_file = basename($content_path, '.php');
    $specific_css = 'assets/css/' . str_replace('_content', '', $content_file) . '.css';
    if (file_exists($specific_css)):
    ?>
    <link href="<?php echo $specific_css; ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <?php include 'templates/bottom_nav.php'; ?>
    
    <?php include $content_path; ?>
    
    <!-- Loading Indicator -->
    <div class="loading" id="loading-indicator">
        <div class="spinner"></div>
        <div class="loading-text">กำลังดำเนินการ...</div>
    </div>

    <!-- User Dropdown Menu -->
    <div class="user-dropdown" id="userDropdown">
        <a href="profile.php" class="user-dropdown-item">
            <span class="material-icons">account_circle</span>
            ข้อมูลส่วนตัว
        </a>
        <a href="settings.php" class="user-dropdown-item">
            <span class="material-icons">settings</span>
            ตั้งค่า
        </a>
        <a href="help.php" class="user-dropdown-item">
            <span class="material-icons">help</span>
            ช่วยเหลือ
        </a>
        <div class="user-dropdown-divider"></div>
        <a href="../logout.php" class="user-dropdown-item">
            <span class="material-icons">exit_to_app</span>
            ออกจากระบบ
        </a>
    </div>

    <!-- Alert Container -->
    <div class="alert-container" id="alertContainer"></div>

    <!-- Core JS -->
    <script src="assets/js/main.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- ตรวจสอบไฟล์ JS เฉพาะหน้า -->
    <?php
    $specific_js = 'assets/js/' . str_replace('_content', '', $content_file) . '.js';
    if (file_exists($specific_js)):
    ?>
    <script src="<?php echo $specific_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
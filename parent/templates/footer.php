
<!-- แถบนำทางด้านล่าง -->
<div class="bottom-nav">
    <a href="home.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">home</span>
        <span>หน้าหลัก</span>
    </a>
    <a href="students.php" class="nav-item <?php echo ($current_page == 'students') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">child_care</span>
        <span>นักเรียน</span>
    </a>
  
    <a href="profile.php" class="nav-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">person</span>
        <span>โปรไฟล์</span>
    </a>
</div>

    <!-- JavaScript -->
    <script src="assets/js/parent-main.js"></script>
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
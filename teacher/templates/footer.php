<!-- แถบนำทางด้านล่าง -->
<div class="bottom-nav">
    <a href="home.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">home</span>
        <span>หน้าหลัก</span>
    </a>
    <a href="new_check_attendance.php" class="nav-item <?php echo ($current_page == 'check_attendance') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">groups</span>
        <span>เช็คชื่อ</span>
    </a>
    <a href="reports.php" class="nav-item <?php echo ($current_page == 'reports') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">assessment</span>
        <span>รายงาน</span>
    </a>
    <a href="#" class="nav-item <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">settings</span>
        <span>ตั้งค่า</span>
    </a>
</div>

<!-- ข้อมูลผู้ใช้แบบดรอปดาวน์ -->
<div class="user-dropdown" id="userDropdown">
    <div class="user-header">
        <div class="user-avatar"><?php echo isset($teacher_info['avatar']) ? $teacher_info['avatar'] : 'ค'; ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo isset($teacher_info['name']) ? $teacher_info['name'] : 'อาจารย์ใจดี มากเมตตา'; ?></div>
            <div class="user-role"><?php echo isset($teacher_info['role']) ? $teacher_info['role'] : 'ครูที่ปรึกษา'; ?></div>
        </div>
    </div>
    <div class="dropdown-divider"></div>
    <a href="profile.php" class="dropdown-item">
        <span class="material-icons">account_circle</span>
        <span>ข้อมูลส่วนตัว</span>
    </a>
    <a href="settings.php" class="dropdown-item">
        <span class="material-icons">settings</span>
        <span>ตั้งค่า</span>
    </a>
    <a href="help.php" class="dropdown-item">
        <span class="material-icons">help</span>
        <span>ช่วยเหลือ</span>
    </a>
    <div class="dropdown-divider"></div>
    <a href="logout.php" class="dropdown-item">
        <span class="material-icons">exit_to_app</span>
        <span>ออกจากระบบ</span>
    </a>
</div>

<!-- JavaScript -->
<?php if(isset($extra_js)): ?>
    <?php foreach($extra_js as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
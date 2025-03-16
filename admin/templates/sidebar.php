<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo">
            <span class="material-icons">school</span>
            STUDENT-Prasat
        </a>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-category">หน้าหลัก</div>
        <a href="index.php" class="menu-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <span class="material-icons">dashboard</span>
            แดชบอร์ด
        </a>
        <a href="check_attendance.php" class="menu-item <?php echo ($current_page == 'check_attendance') ? 'active' : ''; ?>">
            <span class="material-icons">how_to_reg</span>
            เช็คชื่อนักเรียน
        </a>
        <a href="reports.php" class="menu-item <?php echo ($current_page == 'reports') ? 'active' : ''; ?>">
            <span class="material-icons">assessment</span>
            รายงานและสถิติ
        </a>
        <a href="send_notification.php" class="menu-item <?php echo ($current_page == 'send_notification') ? 'active' : ''; ?>">
            <span class="material-icons">send</span>
            ส่งรายงานผู้ปกครอง
        </a>
        <a href="at_risk.php" class="menu-item <?php echo ($current_page == 'at_risk') ? 'active' : ''; ?>">
            <span class="material-icons">warning</span>
            นักเรียนเสี่ยงตกกิจกรรม
            <?php if (isset($at_risk_count) && $at_risk_count > 0): ?>
                <span class="badge"><?php echo $at_risk_count; ?></span>
            <?php endif; ?>
        </a>
        
        <div class="menu-category">จัดการข้อมูล</div>
        <a href="students.php" class="menu-item <?php echo ($current_page == 'students') ? 'active' : ''; ?>">
            <span class="material-icons">people</span>
            นักเรียน
        </a>
        <a href="teachers.php" class="menu-item <?php echo ($current_page == 'teachers') ? 'active' : ''; ?>">
            <span class="material-icons">person</span>
            ครูที่ปรึกษา
        </a>
        <a href="parents.php" class="menu-item <?php echo ($current_page == 'parents') ? 'active' : ''; ?>">
            <span class="material-icons">family_restroom</span>
            ผู้ปกครอง
        </a>
        <a href="classes.php" class="menu-item <?php echo ($current_page == 'classes') ? 'active' : ''; ?>">
            <span class="material-icons">class</span>
            ชั้นเรียน
        </a>
        
        <div class="menu-category">ตั้งค่า</div>
        <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
            <span class="material-icons">settings</span>
            ตั้งค่าระบบ
        </a>
        <a href="help.php" class="menu-item <?php echo ($current_page == 'help') ? 'active' : ''; ?>">
            <span class="material-icons">help</span>
            ช่วยเหลือ
        </a>
    </div>
    
    <div class="admin-info">
        <div class="admin-avatar"><?php echo isset($admin_info['initials']) ? $admin_info['initials'] : 'A'; ?></div>
        <div class="admin-details">
            <div class="admin-name"><?php echo isset($admin_info['name']) ? $admin_info['name'] : 'เจ้าหน้าที่'; ?></div>
            <div class="admin-role"><?php echo isset($admin_info['role']) ? $admin_info['role'] : 'เจ้าหน้าที่ระบบ'; ?></div>
        </div>
        <div class="admin-menu" id="adminMenuToggle">
            <span class="material-icons">more_vert</span>
        </div>
    </div>
</div>

<!-- Admin dropdown menu -->
<div class="admin-dropdown" id="adminDropdown">
    <a href="profile.php" class="admin-dropdown-item">
        <span class="material-icons">account_circle</span>
        ข้อมูลส่วนตัว
    </a>
    <a href="change_password.php" class="admin-dropdown-item">
        <span class="material-icons">lock</span>
        เปลี่ยนรหัสผ่าน
    </a>
    <div class="admin-dropdown-divider"></div>
    <a href="logout.php" class="admin-dropdown-item">
        <span class="material-icons">exit_to_app</span>
        ออกจากระบบ
    </a>
</div>
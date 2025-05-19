<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo">
            <span class="material-icons">school</span>
            STP-Prasat
        </a>
    </div>

    <div class="sidebar-menu">
        <div class="menu-category">หน้าหลัก</div>
        <a href="index.php" class="menu-item <?php echo ($current_page == 'reports') ? 'active' : ''; ?>">
            <span class="material-icons">dashboard</span>
            <span class="menu-text">แดชบอร์ด</span>
        </a>
        <a href="bulk_attendance.php" class="menu-item <?php echo ($current_page == 'bulk_attendance') ? 'active' : ''; ?>">
            <span class="material-icons">how_to_reg</span>
            <span class="menu-text">เช็คชื่อนักเรียน</span>
        </a>

        <a href="enhanced_notification.php" class="menu-item <?php echo ($current_page == 'enhanced_notification') ? 'active' : ''; ?>">
            <span class="material-icons">send</span>
            <span class="menu-text">ส่งรายงานผู้ปกครอง</span>
        </a>
        <a href="at_risk.php" class="menu-item <?php echo ($current_page == 'at_risk') ? 'active' : ''; ?>">
            <span class="material-icons">warning</span>
            <span class="menu-text">นักเรียนเสี่ยงตกกิจกรรม</span>
            <?php if (isset($at_risk_count) && $at_risk_count > 0): ?>
                <span class="badge"><?php echo $at_risk_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="menu-category">บัญชีผู้ใช้งาน</div>
        <a href="users.php" class="menu-item <?php echo ($current_page == 'users') ? 'active' : ''; ?>">
            <span class="material-icons">people</span>
            <span class="menu-text">จัดการผู้ใช้งาน</span>
        </a>
        <a href="line-disconnect.php" class="menu-item <?php echo ($current_page == 'line-disconnect') ? 'active' : ''; ?>">
            <span class="material-icons">cancel</span>
            <span class="menu-text">ยกเลิกการเชื่อมต่อ</span>
        </a>

        <div class="menu-category">พิมพ์ผลรายงาน</div>
        <a href="attendance_report.php" class="menu-item <?php echo ($current_page == 'attendance_report') ? 'active' : ''; ?>">
            <span class="material-icons">print</span>
            <span class="menu-text">พิมพ์ผลกิจกรรม</span>
        </a>
        <a href="report_settings.php" class="menu-item <?php echo ($current_page == 'report_settings') ? 'active' : ''; ?>">
            <span class="material-icons">settings</span>
            <span class="menu-text">ตั้งค่าการพิมพ์</span>
        </a>
        <div class="menu-category">จัดการกิจกรรม</div>
        <a href="activities.php" class="menu-item <?php echo ($current_page == 'activities') ? 'active' : ''; ?>">
            <span class="material-icons">event</span>
            <span class="menu-text">กิจกรรม</span>
        </a>
        <a href="activity_attendance.php" class="menu-item <?php echo ($current_page == 'activity_attendance') ? 'active' : ''; ?>">
            <span class="material-icons">category</span>
            <span class="menu-text">บันทึกเข้าร่วมกิจกรรม</span>
        </a>




        <div class="menu-category">จัดการข้อมูล</div>
        <a href="students.php" class="menu-item <?php echo ($current_page == 'students') ? 'active' : ''; ?>">
            <span class="material-icons">people</span>
            <span class="menu-text">นักเรียน</span>
        </a>
        <a href="teachers.php" class="menu-item <?php echo ($current_page == 'teachers') ? 'active' : ''; ?>">
            <span class="material-icons">person</span>
            <span class="menu-text">ครูที่ปรึกษา</span>
        </a>
        <a href="parents.php" class="menu-item <?php echo ($current_page == 'parents') ? 'active' : ''; ?>">
            <span class="material-icons">family_restroom</span>
            <span class="menu-text">ผู้ปกครอง</span>
        </a>
        <a href="classes.php" class="menu-item <?php echo ($current_page == 'classes') ? 'active' : ''; ?>">
            <span class="material-icons">class</span>
            <span class="menu-text">ชั้นเรียน</span>
        </a>



        <div class="menu-category">ตั้งค่า</div>
        <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
            <span class="material-icons">settings</span>
            <span class="menu-text">ตั้งค่าระบบ</span>
        </a>
        <a href="help.php" class="menu-item <?php echo ($current_page == 'help') ? 'active' : ''; ?>">
            <span class="material-icons">help</span>
            <span class="menu-text">ช่วยเหลือ</span>
        </a>
        <a href="announcements.php" class="menu-item <?php echo ($current_page == 'announcements') ? 'active' : ''; ?>">
            <span class="material-icons">campaign</span>
            <span class="menu-text">จัดการประกาศ</span>
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
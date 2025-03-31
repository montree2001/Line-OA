<?php
// กำหนดหน้าปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- แถบนำทางด้านล่าง -->
<div class="bottom-nav">
    <a href="home.php" class="nav-item <?php echo $current_page == 'home' || $current_page == 'index' ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">home</span>
        <span>หน้าหลัก</span>
    </a>
    
    <a href="check-in.php" class="nav-item <?php echo $current_page == 'check-in' ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">how_to_reg</span>
        <span>เช็คชื่อ</span>
    </a>
    
    <a href="history.php" class="nav-item <?php echo $current_page == 'history' ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">history</span>
        <span>ประวัติ</span>
    </a>
    
    <a href="profile.php" class="nav-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
        <span class="material-icons nav-icon">person</span>
        <span>โปรไฟล์</span>
    </a>
</div>
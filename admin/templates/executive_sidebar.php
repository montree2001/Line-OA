<?php
/**
 * executive_sidebar.php - เมนูด้านข้างสำหรับผู้บริหารระดับสูง
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตรวจสอบว่าเป็น super_admin หรือไม่
$is_super_admin = false;
if (isset($_SESSION['user_id'])) {
    $conn = getDB();
    $checkQuery = "SELECT role FROM admin_users WHERE admin_id = ? AND role = 'super_admin' AND is_active = 1";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([$_SESSION['user_id']]);
    $is_super_admin = (bool)$stmt->fetchColumn();
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="material-icons">dashboard</i>
            <span class="logo-text">ผู้บริหารระดับสูง</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="material-icons">menu</i>
        </button>
    </div>

    <div class="sidebar-content">
        <!-- ข้อมูลผู้ใช้ -->
        <div class="user-info">
            <div class="user-avatar">
                <?php echo substr($_SESSION['user_name'] ?? 'ผ', 0, 1); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['user_name'] ?? 'ผู้บริหาร'; ?></div>
                <div class="user-role">ผู้บริหารระดับสูง</div>
            </div>
        </div>

        <!-- เมนูหลัก -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <!-- แดชบอร์ดผู้บริหาร -->
                <li class="nav-item <?php echo ($current_page == 'executive_reports') ? 'active' : ''; ?>">
                    <a href="executive_reports.php" class="nav-link">
                        <i class="material-icons">dashboard</i>
                        <span class="nav-text">แดชบอร์ดผู้บริหาร</span>
                    </a>
                </li>

                <!-- ภาพรวมวิทยาลัย -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">school</i>
                        <span class="nav-text">ภาพรวมวิทยาลัย</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="executive_reports.php?view=overview">สถิติภาพรวม</a></li>
                        <li><a href="executive_reports.php?view=trends">แนวโน้มการเข้าแถว</a></li>
                        <li><a href="executive_reports.php?view=comparison">เปรียบเทียบข้อมูล</a></li>
                    </ul>
                </li>

                <!-- ประสิทธิภาพแผนกวิชา -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">business</i>
                        <span class="nav-text">ประสิทธิภาพแผนก</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="executive_reports.php?tab=departments">เปรียบเทียบแผนก</a></li>
                        <li><a href="department_analysis.php">วิเคราะห์รายแผนก</a></li>
                        <li><a href="department_goals.php">เป้าหมายแผนก</a></li>
                    </ul>
                </li>

                <!-- ประสิทธิภาพห้องเรียน -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">class</i>
                        <span class="nav-text">ประสิทธิภาพห้องเรียน</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="executive_reports.php?tab=classes">อันดับห้องเรียน</a></li>
                        <li><a href="class_performance.php">วิเคราะห์รายชั้น</a></li>
                        <li><a href="advisor_performance.php">ประสิทธิภาพครูที่ปรึกษา</a></li>
                    </ul>
                </li>

                <!-- นักเรียนเสี่ยงสูง -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">warning</i>
                        <span class="nav-text">นักเรียนเสี่ยงสูง</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="executive_reports.php?tab=students">รายชื่อเสี่ยงสูง</a></li>
                        <li><a href="risk_analysis.php">วิเคราะห์ความเสี่ยง</a></li>
                        <li><a href="intervention_tracking.php">ติดตามการแก้ไข</a></li>
                    </ul>
                </li>

                <!-- รายงานและสถิติ -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">assessment</i>
                        <span class="nav-text">รายงานและสถิติ</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="executive_reports.php?tab=reports">สถิติการแจ้งเตือน</a></li>
                        <li><a href="monthly_reports.php">รายงานประจำเดือน</a></li>
                        <li><a href="semester_reports.php">รายงานประจำภาคเรียน</a></li>
                        <li><a href="annual_reports.php">รายงานประจำปี</a></li>
                    </ul>
                </li>

                <!-- เป้าหมายและตัวชี้วัด -->
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">track_changes</i>
                        <span class="nav-text">เป้าหมายและตัวชี้วัด</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="kpi_dashboard.php">แดชบอร์ด KPI</a></li>
                        <li><a href="goal_tracking.php">ติดตามเป้าหมาย</a></li>
                        <li><a href="benchmark_comparison.php">เปรียบเทียบมาตรฐาน</a></li>
                    </ul>
                </li>

                <li class="nav-divider"></li>

                <!-- การจัดการระบบ (สำหรับ super_admin เท่านั้น) -->
                <?php if ($is_super_admin): ?>
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="material-icons">admin_panel_settings</i>
                        <span class="nav-text">การจัดการระบบ</span>
                        <i class="material-icons nav-arrow">keyboard_arrow_right</i>
                    </a>
                    <ul class="submenu">
                        <li><a href="system_settings.php">ตั้งค่าระบบ</a></li>
                        <li><a href="user_management.php">จัดการผู้ใช้</a></li>
                        <li><a href="backup_restore.php">สำรองข้อมูล</a></li>
                        <li><a href="system_logs.php">บันทึกระบบ</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- ข้อมูลส่วนตัว -->
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="material-icons">account_circle</i>
                        <span class="nav-text">ข้อมูลส่วนตัว</span>
                    </a>
                </li>

                <!-- ออกจากระบบ -->
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link logout-link">
                        <i class="material-icons">logout</i>
                        <span class="nav-text">ออกจากระบบ</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- สถานะการเชื่อมต่อ -->
    <div class="sidebar-footer">
        <div class="connection-status">
            <div class="status-indicator online" id="connectionStatus"></div>
            <span class="status-text">เชื่อมต่อแล้ว</span>
        </div>
        <div class="last-update">
            อัปเดตล่าสุด: <span id="lastUpdateTime"><?php echo date('H:i'); ?></span>
        </div>
    </div>
</div>

<!-- Overlay สำหรับมือถือ -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* สไตล์สำหรับ Sidebar ผู้บริหาร */
.sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1565C0 0%, #0277BD 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
    box-shadow: 4px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo .material-icons {
    font-size: 32px;
    color: #FFD54F;
}

.logo-text {
    font-size: 1.1rem;
    font-weight: 600;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background 0.2s;
    display: none;
}

.sidebar-toggle:hover {
    background: rgba(255,255,255,0.1);
}

.user-info {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
}

.user-name {
    font-weight: 600;
    font-size: 1rem;
}

.user-role {
    font-size: 0.85rem;
    opacity: 0.8;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
}

.sidebar-nav {
    padding: 10px 0;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 2px 10px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s;
    position: relative;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.nav-item.active .nav-link {
    background: rgba(255,255,255,0.15);
    color: white;
    font-weight: 500;
}

.nav-link .material-icons {
    font-size: 20px;
    margin-right: 12px;
    width: 20px;
}

.nav-arrow {
    margin-left: auto !important;
    margin-right: 0 !important;
    font-size: 18px !important;
    transition: transform 0.2s;
}

.submenu-toggle.active .nav-arrow {
    transform: rotate(90deg);
}

.submenu {
    list-style: none;
    padding: 0;
    margin: 5px 0 0 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.has-submenu.active .submenu {
    max-height: 300px;
}

.submenu li {
    margin: 0;
}

.submenu a {
    display: block;
    padding: 8px 15px 8px 50px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    border-radius: 6px;
    margin: 2px 10px;
}

.submenu a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

.logout-link {
    color: #FFCDD2 !important;
}

.logout-link:hover {
    background: rgba(244,67,54,0.2) !important;
}

.sidebar-footer {
    padding: 15px 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 0.8rem;
}

.connection-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4CAF50;
    animation: pulse 2s infinite;
}

.status-indicator.offline {
    background: #F44336;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.last-update {
    opacity: 0.7;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: block;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

/* Scrollbar สำหรับ sidebar */
.sidebar-content::-webkit-scrollbar {
    width: 4px;
}

.sidebar-content::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.sidebar-content::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // การจัดการ Submenu
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parentItem = this.closest('.nav-item');
            const isActive = parentItem.classList.contains('active');
            
            // ปิด submenu อื่นๆ
            document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
                item.classList.remove('active');
            });
            
            // Toggle submenu ปัจจุบัน
            if (!isActive) {
                parentItem.classList.add('active');
            }
        });
    });
    
    // การจัดการ Sidebar Toggle สำหรับมือถือ
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // อัปเดตเวลาล่าสุด
    function updateLastTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        });
        const lastUpdateElement = document.getElementById('lastUpdateTime');
        if (lastUpdateElement) {
            lastUpdateElement.textContent = timeString;
        }
    }
    
    // อัปเดตทุก 30 วินาที
    setInterval(updateLastTime, 30000);
    
    // ตรวจสอบสถานะการเชื่อมต่อ
    function checkConnection() {
        const statusIndicator = document.getElementById('connectionStatus');
        const statusText = document.querySelector('.status-text');
        
        if (navigator.onLine) {
            statusIndicator.classList.remove('offline');
            statusText.textContent = 'เชื่อมต่อแล้ว';
        } else {
            statusIndicator.classList.add('offline');
            statusText.textContent = 'ออฟไลน์';
        }
    }
    
    window.addEventListener('online', checkConnection);
    window.addEventListener('offline', checkConnection);
    checkConnection();
});
</script>
<div class="header">
    <a href="#" onclick="goBack()" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>ตั้งค่า</h1>
    <div class="header-icon">
        <span class="material-icons">more_vert</span>
    </div>
</div>

<div class="container">
    <!-- ข้อมูลโปรไฟล์ -->
    <div class="profile-card">
        <div class="profile-avatar"><?php echo $teacher_info['avatar']; ?></div>
        <div class="profile-info">
            <div class="profile-name"><?php echo $teacher_info['name']; ?></div>
            <div class="profile-role"><?php echo $teacher_info['role']; ?> <?php echo $teacher_classes[0]['name']; ?></div>
        </div>
        <button class="profile-edit" onclick="showSettingModal('profile')">
    <span class="material-icons">edit</span> แก้ไข
</button>
    </div>

    <!-- รายการห้องเรียนที่รับผิดชอบ -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">groups</span> ชั้นเรียนที่รับผิดชอบ
        </div>
        <div class="class-list">
            <?php foreach ($teacher_classes as $class): ?>
            <div class="class-card">
                <div class="class-info">
                    <h3><?php echo $class['name']; ?></h3>
                    <p>นักเรียน <?php echo $class['total_students']; ?> คน</p>
                    <p>อัตราการเข้าแถว <?php echo $class['attendance_rate']; ?>%</p>
                </div>
                <div class="class-status">
                    <?php if ($class['is_primary']): ?>
                    <span class="primary-badge">หลัก</span>
                    <?php endif; ?>
                    <?php if ($class['at_risk_count'] > 0): ?>
                    <span class="at-risk-badge"><?php echo $class['at_risk_count']; ?> คนเสี่ยงตก</span>
                    <?php endif; ?>
                </div>
                <div class="class-actions">
                    <a href="check-attendance.php?class_id=<?php echo $class['id']; ?>" class="class-action-btn">
                        <span class="material-icons">how_to_reg</span> เช็คชื่อ
                    </a>
                    <a href="reports.php?class_id=<?php echo $class['id']; ?>" class="class-action-btn">
                        <span class="material-icons">assessment</span> รายงาน
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- การตั้งค่าบัญชี -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">account_circle</span> บัญชีและโปรไฟล์
        </div>
        <ul class="settings-list">
            <li class="settings-item" onclick="navigateTo('settings.php?type=profile')">
                <div class="settings-icon icon-general">
                    <span class="material-icons">person</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ข้อมูลส่วนตัว</div>
                    <div class="settings-description">แก้ไขชื่อ ข้อมูลติดต่อ และรายละเอียดอื่นๆ</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=password')">
                <div class="settings-icon icon-security">
                    <span class="material-icons">vpn_key</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">เปลี่ยนรหัสผ่าน</div>
                    <div class="settings-description">เปลี่ยนรหัสผ่านเข้าสู่ระบบของคุณ</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=class')">
                <div class="settings-icon icon-attendance">
                    <span class="material-icons">class</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ข้อมูลชั้นเรียน</div>
                    <div class="settings-description">จัดการข้อมูลและรายชื่อนักเรียนในชั้นเรียน</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
        </ul>
    </div>

    <!-- การตั้งค่าการเช็คชื่อ -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">how_to_reg</span> การตั้งค่าการเช็คชื่อ
        </div>
        <ul class="settings-list">
            <li class="settings-item" onclick="navigateTo('settings.php?type=pin')">
                <div class="settings-icon icon-attendance">
                    <span class="material-icons">pin</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">การตั้งค่ารหัส PIN</div>
                    <div class="settings-description">ตั้งค่าเวลาหมดอายุและความยาวของรหัส PIN</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=qr')">
                <div class="settings-icon icon-attendance">
                    <span class="material-icons">qr_code</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">การตั้งค่า QR Code</div>
                    <div class="settings-description">ตั้งค่าการสแกน QR Code และเวลาหมดอายุ</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=gps')">
                <div class="settings-icon icon-attendance">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">การตั้งค่า GPS</div>
                    <div class="settings-description">ตั้งค่าระยะห่างและพิกัดสำหรับการเช็คชื่อ</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=time')">
                <div class="settings-icon icon-attendance">
                    <span class="material-icons">schedule</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">เวลาเช็คชื่อและตารางเวลา</div>
                    <div class="settings-description">ตั้งค่าระยะเวลาสำหรับการเช็คชื่อ</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
        </ul>
    </div>

    <!-- การแจ้งเตือน -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">notifications</span> การแจ้งเตือน
        </div>
        <ul class="settings-list">
            <li class="settings-item">
                <div class="settings-icon icon-notification">
                    <span class="material-icons">notifications_active</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">แจ้งเตือนนักเรียนเข้าแถว</div>
                    <div class="settings-description">แจ้งเตือนเมื่อนักเรียนเช็คชื่อเข้าแถว</div>
                </div>
                <div class="settings-action">
                    <label class="toggle-switch">
                        <input type="checkbox" <?php echo $notification_settings['student_present'] ? 'checked' : ''; ?> onchange="toggleNotification('student_present')">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </li>
            <li class="settings-item">
                <div class="settings-icon icon-notification">
                    <span class="material-icons">notifications_off</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">แจ้งเตือนนักเรียนขาดแถว</div>
                    <div class="settings-description">แจ้งเตือนเมื่อนักเรียนไม่เช็คชื่อเข้าแถว</div>
                </div>
                <div class="settings-action">
                    <label class="toggle-switch">
                        <input type="checkbox" <?php echo $notification_settings['student_absent'] ? 'checked' : ''; ?> onchange="toggleNotification('student_absent')">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </li>
            <li class="settings-item">
                <div class="settings-icon icon-notification">
                    <span class="material-icons">campaign</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ประกาศจากโรงเรียน</div>
                    <div class="settings-description">แจ้งเตือนเมื่อมีประกาศจากโรงเรียน</div>
                </div>
                <div class="settings-action">
                    <label class="toggle-switch">
                        <input type="checkbox" <?php echo $notification_settings['school_announcement'] ? 'checked' : ''; ?> onchange="toggleNotification('school_announcement')">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </li>
            <li class="settings-item">
                <div class="settings-icon icon-notification">
                    <span class="material-icons">chat</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ข้อความจากผู้ปกครอง</div>
                    <div class="settings-description">แจ้งเตือนเมื่อได้รับข้อความจากผู้ปกครอง</div>
                </div>
                <div class="settings-action">
                    <label class="toggle-switch">
                        <input type="checkbox" <?php echo $notification_settings['parent_message'] ? 'checked' : ''; ?> onchange="toggleNotification('parent_message')">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </li>
            <li class="settings-item">
                <div class="settings-icon icon-notification">
                    <span class="material-icons">warning</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">แจ้งเตือนนักเรียนเสี่ยงตกกิจกรรม</div>
                    <div class="settings-description">แจ้งเตือนเมื่อนักเรียนมีความเสี่ยงตกกิจกรรม</div>
                </div>
                <div class="settings-action">
                    <label class="toggle-switch">
                        <input type="checkbox" <?php echo $notification_settings['at_risk_warning'] ? 'checked' : ''; ?> onchange="toggleNotification('at_risk_warning')">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </li>
        </ul>
    </div>

    <!-- การตั้งค่าทั่วไป -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">settings</span> การตั้งค่าทั่วไป
        </div>
        <ul class="settings-list">
            <li class="settings-item" onclick="navigateTo('settings.php?type=appearance')">
                <div class="settings-icon icon-appearance">
                    <span class="material-icons">palette</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ธีมและการแสดงผล</div>
                    <div class="settings-description">ปรับแต่งรูปแบบและสีของแอปพลิเคชัน</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=language')">
                <div class="settings-icon icon-general">
                    <span class="material-icons">language</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ภาษา</div>
                    <div class="settings-description">เปลี่ยนภาษาที่ใช้ในแอปพลิเคชัน</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=data')">
                <div class="settings-icon icon-data">
                    <span class="material-icons">storage</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">จัดการข้อมูล</div>
                    <div class="settings-description">ล้างแคช และจัดการพื้นที่จัดเก็บข้อมูล</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('settings.php?type=privacy')">
                <div class="settings-icon icon-security">
                    <span class="material-icons">security</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ความเป็นส่วนตัวและความปลอดภัย</div>
                    <div class="settings-description">จัดการการอนุญาตและข้อมูลส่วนตัว</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
        </ul>
    </div>

    <!-- ช่วยเหลือและข้อมูล -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">help</span> ช่วยเหลือและข้อมูล
        </div>
        <ul class="settings-list">
            <li class="settings-item" onclick="navigateTo('help.php')">
                <div class="settings-icon icon-support">
                    <span class="material-icons">help_outline</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">วิธีใช้งาน</div>
                    <div class="settings-description">คู่มือการใช้งานและคำถามที่พบบ่อย</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('support.php')">
                <div class="settings-icon icon-support">
                    <span class="material-icons">contact_support</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">ติดต่อสนับสนุน</div>
                    <div class="settings-description">แจ้งปัญหาหรือสอบถามข้อมูลเพิ่มเติม</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
            <li class="settings-item" onclick="navigateTo('about.php')">
                <div class="settings-icon icon-about">
                    <span class="material-icons">info</span>
                </div>
                <div class="settings-content">
                    <div class="settings-title">เกี่ยวกับแอปพลิเคชัน</div>
                    <div class="settings-description">ข้อมูลเวอร์ชันและลิขสิทธิ์</div>
                </div>
                <div class="settings-action">
                    <span class="material-icons">chevron_right</span>
                </div>
            </li>
        </ul>
    </div>

    <!-- ปุ่มออกจากระบบ -->
    <button class="logout-button" onclick="confirmLogout()">
        <span class="material-icons">logout</span> ออกจากระบบ
    </button>

    <!-- ข้อมูลเวอร์ชัน -->
    <div class="version-info">
        <p><?php echo $app_version['version']; ?></p>
        <p><?php echo $app_version['copyright']; ?></p>
    </div>
</div>

<!-- Modal ยืนยันการออกจากระบบ -->
<div class="modal" id="logout-modal">
    <div class="modal-content">
        <div class="modal-title">ยืนยันการออกจากระบบ</div>
        <p>คุณต้องการออกจากระบบใช่หรือไม่?</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('logout-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="logout()">ออกจากระบบ</button>
        </div>
    </div>
</div>

<!-- ปรับเป็นแสดงหน้าตั้งค่าเฉพาะทางเมื่อกดเท่านั้น -->
<?php if ($setting_type !== 'main' && isset($_GET['show_modal']) && $_GET['show_modal'] === 'true'): ?>
<script>
    // เมื่อหน้าโหลดเสร็จ ให้แสดง modal ตั้งค่าเฉพาะทาง เฉพาะเมื่อมีการระบุ parameter
    document.addEventListener('DOMContentLoaded', function() {
        showSettingModal('<?php echo $setting_type; ?>');
    });
</script>
<?php endif; ?>
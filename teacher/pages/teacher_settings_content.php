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
            <div class="profile-role"><?php echo $teacher_info['role']; ?> <?php echo $teacher_info['class']; ?></div>
        </div>
        <button class="profile-edit" onclick="editProfile()">
            <span class="material-icons">edit</span> แก้ไข
        </button>
    </div>

    <!-- การตั้งค่าบัญชี -->
    <div class="settings-section">
        <div class="section-header">
            <span class="material-icons">account_circle</span> บัญชีและโปรไฟล์
        </div>
        <ul class="settings-list">
            <li class="settings-item" onclick="navigateTo('edit-profile.php')">
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
            <li class="settings-item" onclick="navigateTo('change-password.php')">
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
            <li class="settings-item" onclick="navigateTo('class-info.php')">
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
            <li class="settings-item" onclick="navigateTo('pin-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('qr-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('gps-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('time-settings.php')">
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
                        <input type="checkbox" checked onchange="toggleNotification('attendance_present')">
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
                        <input type="checkbox" checked onchange="toggleNotification('attendance_absent')">
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
                        <input type="checkbox" checked onchange="toggleNotification('school_announcement')">
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
                        <input type="checkbox" checked onchange="toggleNotification('parent_message')">
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
            <li class="settings-item" onclick="navigateTo('appearance-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('language-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('data-settings.php')">
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
            <li class="settings-item" onclick="navigateTo('privacy-settings.php')">
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
        <p>Teacher-Prasat v1.0.0</p>
        <p>© 2025 วิทยาลัยการอาชีพปราสาท</p>
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
<!-- หน้าโปรไฟล์เจ้าหน้าที่ -->
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar-container">
            <?php if (!empty($admin_info['avatar'])): ?>
                <img src="<?php echo htmlspecialchars($admin_info['avatar']); ?>" alt="รูปโปรไฟล์" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder">
                    <?php echo htmlspecialchars($admin_info['initials']); ?>
                </div>
            <?php endif; ?>
            <button class="avatar-edit-btn" onclick="changeProfilePicture()">
                <span class="material-icons">edit</span>
            </button>
        </div>
        
        <div class="profile-header-info">
            <h1 class="profile-name"><?php echo htmlspecialchars($admin_info['name']); ?></h1>
            <p class="profile-role"><?php echo htmlspecialchars($admin_info['role']); ?></p>
            
            <div class="profile-quick-stats">
                <div class="quick-stat">
                    <span class="material-icons">groups</span>
                    <span>นักเรียนเสี่ยงตกกิจกรรม: <?php echo $at_risk_count; ?> คน</span>
                </div>
                <div class="quick-stat">
                    <span class="material-icons">login</span>
                    <span>เข้าสู่ระบบล่าสุด: <?php echo htmlspecialchars($admin_info['last_login']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-tabs">
        <div class="tabs-header">
            <div class="tab active" data-tab="personal-info">ข้อมูลส่วนตัว</div>
            <div class="tab" data-tab="work-info">ข้อมูลการทำงาน</div>
            <div class="tab" data-tab="security">ความปลอดภัย</div>
            <div class="tab" data-tab="activity-log">บันทึกกิจกรรม</div>
        </div>

        <!-- แท็บข้อมูลส่วนตัว -->
        <div id="personal-info-tab" class="tab-content active">
            <div class="card">
                <div class="card-title">
                    <span class="material-icons">person</span>
                    ข้อมูลส่วนตัว
                </div>
                
                <div class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_info['name']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin_info['email']); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($admin_info['phone']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID Line</label>
                            <input type="text" class="form-control" placeholder="ยังไม่ได้เชื่อมต่อ" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- แท็บข้อมูลการทำงาน -->
        <div id="work-info-tab" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <span class="material-icons">work</span>
                    ข้อมูลการทำงาน
                </div>
                
                <div class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">แผนก/ฝ่าย</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_info['department']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_info['role']); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">วันที่เริ่มงาน</label>
                            <input type="text" class="form-control" value="1 สิงหาคม 2565" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">สถานะการทำงาน</label>
                            <input type="text" class="form-control" value="ปกติ" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- แท็บความปลอดภัย -->
        <div id="security-tab" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <span class="material-icons">security</span>
                    การตั้งค่าความปลอดภัย
                </div>
                
                <div class="security-section">
                    <div class="security-item">
                        <div class="security-info">
                            <span class="material-icons">lock</span>
                            <div>
                                <h3>รหัสผ่าน</h3>
                                <p>เปลี่ยนแปลงรหัสผ่านเพื่อความปลอดภัย</p>
                            </div>
                        </div>
                        <button class="btn btn-secondary" onclick="changePassword()">
                            <span class="material-icons">edit</span>
                            เปลี่ยนรหัสผ่าน
                        </button>
                    </div>

                    <div class="security-item">
                        <div class="security-info">
                            <span class="material-icons">two_factor</span>
                            <div>
                                <h3>การยืนยันตัวตนสองขั้นตอน</h3>
                                <p>เพิ่มความปลอดภัยให้กับบัญชีของคุณ</p>
                            </div>
                        </div>
                        <button class="btn btn-secondary" onclick="configTwoFactor()">
                            <span class="material-icons">settings</span>
                            ตั้งค่า
                        </button>
                    </div>

                    <div class="security-item">
                        <div class="security-info">
                            <span class="material-icons">devices</span>
                            <div>
                                <h3>การเข้าสู่ระบบจากอุปกรณ์ต่างๆ</h3>
                                <p>ดูและจัดการอุปกรณ์ที่เข้าสู่ระบบ</p>
                            </div>
                        </div>
                        <button class="btn btn-secondary" onclick="manageDevices()">
                            <span class="material-icons">manage_accounts</span>
                            จัดการอุปกรณ์
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- แท็บบันทึกกิจกรรม -->
        <div id="activity-log-tab" class="tab-content">
            <div class="card">
                <div class="card-title">
                    <span class="material-icons">history</span>
                    บันทึกกิจกรรมล่าสุด
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่/เวลา</th>
                                <th>กิจกรรม</th>
                                <th>สถานที่/IP</th>
                                <th>อุปกรณ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>16/03/2568 08:15</td>
                                <td>เข้าสู่ระบบ</td>
                                <td>192.168.1.100</td>
                                <td>Chrome, Windows</td>
                            </tr>
                            <tr>
                                <td>15/03/2568 16:45</td>
                                <td>ส่งข้อความแจ้งเตือนผู้ปกครอง</td>
                                <td>192.168.1.100</td>
                                <td>Safari, iOS</td>
                            </tr>
                            <tr>
                                <td>15/03/2568 10:30</td>
                                <td>เปลี่ยนแปลงรหัสผ่าน</td>
                                <td>192.168.1.100</td>
                                <td>Chrome, Windows</td>
                            </tr>
                            <tr>
                                <td>14/03/2568 13:20</td>
                                <td>เช็คชื่อนักเรียน</td>
                                <td>192.168.1.100</td>
                                <td>Edge, Windows</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal" id="changePasswordModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeChangePasswordModal()">
            <span class="material-icons">close</span>
        </button>
        
        <h2 class="modal-title">เปลี่ยนรหัสผ่าน</h2>
        
        <form id="changePasswordForm" onsubmit="submitChangePassword(event)">
            <div class="form-group">
                <label class="form-label">รหัสผ่านปัจจุบัน</label>
                <div class="password-input-container">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="currentPassword" 
                        name="current_password" 
                        required 
                        placeholder="กรอกรหัสผ่านปัจจุบัน"
                    >
                    <button 
                        type="button" 
                        class="password-toggle" 
                        onclick="togglePasswordVisibility('currentPassword', this)"
                    >
                        <span class="material-icons">visibility_off</span>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">รหัสผ่านใหม่</label>
                <div class="password-input-container">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="newPassword" 
                        name="new_password" 
                        required 
                        placeholder="กรอกรหัสผ่านใหม่"
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                        title="รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร และประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข"
                    >
                    <button 
                        type="button" 
                        class="password-toggle" 
                        onclick="togglePasswordVisibility('newPassword', this)"
                    >
                        <span class="material-icons">visibility_off</span>
                    </button>
                </div>
                <div class="password-strength-meter">
                    <div class="strength-bar"></div>
                    <div class="strength-text"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <div class="password-input-container">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirmPassword" 
                        name="confirm_password" 
                        required 
                        placeholder="ยืนยันรหัสผ่านใหม่"
                    >
                    <button 
                        type="button" 
                        class="password-toggle" 
                        onclick="togglePasswordVisibility('confirmPassword', this)"
                    >
                        <span class="material-icons">visibility_off</span>
                    </button>
                </div>
            </div>
            
            <div class="modal-actions">
                <button 
                    type="button" 
                    class="btn btn-secondary" 
                    onclick="closeChangePasswordModal()"
                >
                    ยกเลิก
                </button>
                <button 
                    type="submit" 
                    class="btn btn-primary"
                >
                    <span class="material-icons">save</span>
                    บันทึกรหัสผ่านใหม่
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลแก้ไขข้อมูลส่วนตัว -->
<div class="modal" id="editProfileModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditProfileModal()">
            <span class="material-icons">close</span>
        </button>
        
        <h2 class="modal-title">แก้ไขข้อมูลส่วนตัว</h2>
        
        <form id="editProfileForm" onsubmit="submitProfileEdit(event)">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="editName" 
                        name="name" 
                        required 
                        placeholder="กรอกชื่อ-นามสกุล"
                        maxlength="100"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="editEmail" 
                        name="email" 
                        required 
                        placeholder="กรอกอีเมล"
                        maxlength="100"
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input 
                        type="tel" 
                        class="form-control" 
                        id="editPhone" 
                        name="phone" 
                        pattern="[0-9]{10}" 
                        placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก"
                        maxlength="10"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">แผนก/ฝ่าย</label>
                    <select 
                        class="form-control" 
                        id="editDepartment" 
                        name="department"
                    >
                        <option value="">เลือกแผนก/ฝ่าย</option>
                        <option value="กิจการนักเรียน">กิจการนักเรียน</option>
                        <option value="วิชาการ">วิชาการ</option>
                        <option value="เทคโนโลยีสารสนเทศ">เทคโนโลยีสารสนเทศ</option>
                        <option value="บริหาร">บริหาร</option>
                        <option value="งานทะเบียน">งานทะเบียน</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Line ID</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="editLineId" 
                    name="line_id" 
                    placeholder="กรอก Line ID (ถ้ามี)"
                    maxlength="50"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">เกี่ยวกับฉัน</label>
                <textarea 
                    class="form-control" 
                    id="editBio" 
                    name="bio" 
                    rows="4" 
                    placeholder="เขียนคำอธิบายเกี่ยวกับตัวคุณ (ถ้าต้องการ)"
                    maxlength="500"
                ></textarea>
            </div>
            
            <div class="modal-actions">
                <button 
                    type="button" 
                    class="btn btn-secondary" 
                    onclick="closeEditProfileModal()"
                >
                    ยกเลิก
                </button>
                <button 
                    type="submit" 
                    class="btn btn-primary"
                >
                    <span class="material-icons">save</span>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// เรียกใช้งานฟังก์ชันแก้ไขโปรไฟล์
function editProfile() {
    // ดึงข้อมูลปัจจุบัน
    const currentName = '<?php echo htmlspecialchars($admin_info['name']); ?>';
    const currentEmail = '<?php echo htmlspecialchars($admin_info['email']); ?>';
    const currentPhone = '<?php echo htmlspecialchars($admin_info['phone'] ?? ''); ?>';
    const currentDepartment = '<?php echo htmlspecialchars($admin_info['department'] ?? ''); ?>';
    
    // กำหนดค่าเริ่มต้นให้ฟอร์ม
    document.getElementById('editName').value = currentName;
    document.getElementById('editEmail').value = currentEmail;
    document.getElementById('editPhone').value = currentPhone;
    document.getElementById('editDepartment').value = currentDepartment || '';
    
    // แสดงโมดัล
    const modal = document.getElementById('editProfileModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// ปิดโมดัล
function closeEditProfileModal() {
    const modal = document.getElementById('editProfileModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ส่งข้อมูลแก้ไขโปรไฟล์
function submitProfileEdit(event) {
    event.preventDefault();
    
    // รวบรวมข้อมูลจากฟอร์ม
    const formData = new FormData(event.target);
    
    // แสดง loading
    showLoading('กำลังบันทึกข้อมูล...');
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // ซ่อน loading
        hideLoading();
        
        if (data.success) {
            // แสดงข้อความสำเร็จ
            showAlert('บันทึกข้อมูลสำเร็จ', 'success');
            
            // อัปเดต UI
            updateProfileUI(data.user_info);
            
            // ปิดโมดัล
            closeEditProfileModal();
        } else {
            // แสดงข้อความผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'danger');
        }
    })
    .catch(error => {
        // ซ่อน loading
        hideLoading();
        
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'danger');
    });
}

// อัปเดต UI หลังจากแก้ไขโปรไฟล์
function updateProfileUI(userInfo) {
    // อัปเดตชื่อ
    document.querySelector('.profile-name').textContent = userInfo.name;
    
    // อัปเดตบทบาท (หากมีการเปลี่ยน)
    if (userInfo.role) {
        document.querySelector('.profile-role').textContent = userInfo.role;
    }
    
    // อัปเดตข้อมูลอื่นๆ บนหน้าโปรไฟล์
    const personalInfoTab = document.getElementById('personal-info-tab');
    if (personalInfoTab) {
        personalInfoTab.querySelector('input[value]').value = userInfo.email;
        
        // หากมีการเพิ่มข้อมูลอื่นๆ ให้อัปเดตที่นี่
    }
}
</script>

<style>
/* สไตล์เพิ่มเติมสำหรับโมดัลแก้ไขโปรไฟล์ */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
}

.form-group textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
}
</style>


<script>
// เพิ่มฟังก์ชันสำหรับการจัดการรหัสผ่าน
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('.material-icons');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility_off';
    }
}

function checkPasswordStrength(password) {
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    // เกณฑ์การตรวจสอบความแข็งแรงของรหัสผ่าน
    const strongRegex = new RegExp('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{10,})');
    const mediumRegex = new RegExp('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})');
    
    if (strongRegex.test(password)) {
        strengthBar.style.width = '100%';
        strengthBar.style.backgroundColor = '#4caf50';
        strengthText.textContent = 'ความปลอดภัยสูง';
        strengthText.style.color = '#4caf50';
    } else if (mediumRegex.test(password)) {
        strengthBar.style.width = '66%';
        strengthBar.style.backgroundColor = '#ff9800';
        strengthText.textContent = 'ความปลอดภัยปานกลาง';
        strengthText.style.color = '#ff9800';
    } else {
        strengthBar.style.width = '33%';
        strengthBar.style.backgroundColor = '#f44336';
        strengthText.textContent = 'ความปลอดภัยต่ำ';
        strengthText.style.color = '#f44336';
    }
}

function checkPasswordStrength(password) {
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');
    
    // เกณฑ์การตรวจสอบความแข็งแรงของรหัสผ่าน
    const strongRegex = new RegExp('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])(?=.{10,})');
    const mediumRegex = new RegExp('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})');
    
    if (strongRegex.test(password)) {
        strengthBar.style.width = '100%';
        strengthBar.style.backgroundColor = '#4caf50';
        strengthText.textContent = 'ความปลอดภัยสูง';
        strengthText.style.color = '#4caf50';
    } else if (mediumRegex.test(password)) {
        strengthBar.style.width = '66%';
        strengthBar.style.backgroundColor = '#ff9800';
        strengthText.textContent = 'ความปลอดภัยปานกลาง';
        strengthText.style.color = '#ff9800';
    } else {
        strengthBar.style.width = '33%';
        strengthBar.style.backgroundColor = '#f44336';
        strengthText.textContent = 'ความปลอดภัยต่ำ';
        strengthText.style.color = '#f44336';
    }
}

// เพิ่ม event listener สำหรับการตรวจสอบความแข็งแรงของรหัสผ่าน
document.getElementById('newPassword').addEventListener('input', function() {
    checkPasswordStrength(this.value);
});

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    if (modal) {
        // รีเซ็ตฟอร์ม
        document.getElementById('changePasswordForm').reset();
        
        // ซ่อนโมดัล
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function submitChangePassword(event) {
    event.preventDefault();
    
    // เก็บค่าจากฟอร์ม
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // ตรวจสอบการยืนยันรหัสผ่าน
    if (newPassword !== confirmPassword) {
        showAlert('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน', 'danger');
        return;
    }
    
    // ตรวจสอบความแข็งแรงของรหัสผ่าน
    const mediumRegex = new RegExp('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})');
    if (!mediumRegex.test(newPassword)) {
        showAlert('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร และประกอบด้วยตัวพิมพ์เล็ก พิมพ์ใหญ่ และตัวเลข', 'danger');
        return;
    }
    
    // แสดง loading
    showLoading('กำลังเปลี่ยนรหัสผ่าน...');
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์ด้วย AJAX
    fetch('api/change_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        // ซ่อน loading
        hideLoading();
        
        if (data.success) {
            // แสดงข้อความสำเร็จ
            showAlert('เปลี่ยนรหัสผ่านสำเร็จ', 'success');
            
            // ปิดโมดัล
            closeChangePasswordModal();
        } else {
            // แสดงข้อความผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน', 'danger');
        }
    })
    .catch(error => {
        // ซ่อน loading
        hideLoading();
        
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'danger');
    });
}

// เพิ่ม CSS สำหรับการแสดงผลความแข็งแรงของรหัสผ่าน
const passwordStrengthStyle = document.createElement('style');
passwordStrengthStyle.textContent = `
.password-input-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
}

.password-strength-meter {
    margin-top: 5px;
}

.strength-bar {
    height: 5px;
    width: 0;
    transition: width 0.3s, background-color 0.3s;
}

.strength-text {
    font-size: 12px;
    text-align: right;
    margin-top: 5px;
}
`;
document.head.appendChild(passwordStrengthStyle);
</script>


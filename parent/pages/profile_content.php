<?php if(isset($success_message) && !empty($success_message)): ?>
<div class="notification-banner success">
    <span class="material-icons icon">check_circle</span>
    <div class="content">
        <div class="title">สำเร็จ</div>
        <div class="message"><?php echo $success_message; ?></div>
    </div>
</div>
<?php endif; ?>

<?php if(isset($error_message) && !empty($error_message)): ?>
<div class="notification-banner danger">
    <span class="material-icons icon">error</span>
    <div class="content">
        <div class="title">เกิดข้อผิดพลาด</div>
        <div class="message"><?php echo $error_message; ?></div>
    </div>
</div>
<?php endif; ?>

<div class="profile-section">
    <div class="profile-header">
        <div class="profile-image">
            <?php if(isset($user_data['profile_picture']) && !empty($user_data['profile_picture'])): ?>
                <img src="<?php echo $user_data['profile_picture']; ?>" alt="รูปโปรไฟล์">
            <?php else: ?>
                <span class="material-icons profile-icon">account_circle</span>
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <h1 class="profile-name">
                <?php echo $user_data['title'] . ' ' . $user_data['first_name'] . ' ' . $user_data['last_name']; ?>
            </h1>
            <div class="profile-role">ผู้ปกครอง</div>
            <?php if(isset($parent_data['relationship'])): ?>
                <div class="profile-relationship">
                    <span class="relationship-label">ความสัมพันธ์กับนักเรียน:</span>
                    <span class="relationship-value"><?php echo $parent_data['relationship']; ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="profile-tabs">
        <button class="tab-button active" onclick="switchTab('personal-info')">ข้อมูลส่วนตัว</button>
        <button class="tab-button" onclick="switchTab('children')">นักเรียนในความดูแล</button>
        <button class="tab-button" onclick="switchTab('settings')">ตั้งค่า</button>
    </div>
    
    <div class="tab-content active" id="personal-info-tab">
        <div class="form-card">
            <div class="card-header">
                <h2>แก้ไขข้อมูลส่วนตัว</h2>
            </div>
            <div class="card-body">
                <form method="post" action="" class="profile-form">
                    <div class="form-group">
                        <label for="title">คำนำหน้า</label>
                        <select name="title" id="title" required>
                            <option value="นาย" <?php echo ($user_data['title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                            <option value="นาง" <?php echo ($user_data['title'] == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                            <option value="นางสาว" <?php echo ($user_data['title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                            <option value="ดร." <?php echo ($user_data['title'] == 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                            <option value="ผศ." <?php echo ($user_data['title'] == 'ผศ.') ? 'selected' : ''; ?>>ผศ.</option>
                            <option value="รศ." <?php echo ($user_data['title'] == 'รศ.') ? 'selected' : ''; ?>>รศ.</option>
                            <option value="ศ." <?php echo ($user_data['title'] == 'ศ.') ? 'selected' : ''; ?>>ศ.</option>
                            <option value="อื่นๆ" <?php echo ($user_data['title'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">ชื่อจริง</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo $user_data['first_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">นามสกุล</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo $user_data['last_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="relationship">ความสัมพันธ์กับนักเรียน</label>
                        <select name="relationship" id="relationship" required>
                            <option value="พ่อ" <?php echo ($parent_data['relationship'] == 'พ่อ') ? 'selected' : ''; ?>>พ่อ</option>
                            <option value="แม่" <?php echo ($parent_data['relationship'] == 'แม่') ? 'selected' : ''; ?>>แม่</option>
                            <option value="ผู้ปกครอง" <?php echo ($parent_data['relationship'] == 'ผู้ปกครอง') ? 'selected' : ''; ?>>ผู้ปกครอง</option>
                            <option value="ญาติ" <?php echo ($parent_data['relationship'] == 'ญาติ') ? 'selected' : ''; ?>>ญาติ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">เบอร์โทรศัพท์</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo $user_data['phone_number']; ?>" pattern="[0-9]{9,10}" required>
                        <small>กรุณากรอกเบอร์โทรศัพท์ 10 หลัก</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">อีเมล (ไม่บังคับ)</label>
                        <input type="email" id="email" name="email" value="<?php echo $user_data['email']; ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-primary">บันทึกข้อมูล</button>
                        <button type="reset" class="btn-secondary">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="children-tab">
        <div class="form-card">
            <div class="card-header">
                <h2>นักเรียนในความดูแล</h2>
            </div>
            <div class="card-body">
                <?php if(!empty($students)): ?>
                    <div class="children-list">
                        <?php foreach($students as $student): ?>
                            <div class="child-item">
                                <div class="child-info">
                                    <div class="child-name"><?php echo $student['name']; ?></div>
                                    <div class="child-id">รหัสนักเรียน: <?php echo $student['student_code']; ?></div>
                                    <div class="child-class"><?php echo $student['class']; ?></div>
                                </div>
                                <div class="child-actions">
                                    <a href="students.php?id=<?php echo $student['id']; ?>" class="btn-view">
                                        <span class="material-icons">visibility</span> ดูข้อมูล
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="manage-children">
                        <a href="students.php" class="btn-primary">
                            <span class="material-icons">settings</span> จัดการนักเรียนในความดูแล
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">
                            <span class="material-icons">child_care</span>
                        </div>
                        <div class="no-data-message">ไม่พบข้อมูลนักเรียนในความดูแล</div>
                        <div class="no-data-action">
                            <a href="students.php" class="btn-primary">
                                <span class="material-icons">add_circle</span> เพิ่มนักเรียน
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="settings-tab">
        <div class="form-card">
            <div class="card-header">
                <h2>ตั้งค่าบัญชี</h2>
            </div>
            <div class="card-body">
                <div class="settings-section">
                    <h3>บัญชี LINE</h3>
                    <div class="line-account-info">
                        <div class="info-item">
                            <span class="info-label">LINE ID:</span>
                            <span class="info-value"><?php echo isset($user_data['line_id']) ? $user_data['line_id'] : 'ไม่ได้ระบุ'; ?></span>
                        </div>
                        <p class="line-info-message">
                            บัญชี LINE ใช้สำหรับการล็อกอินและรับการแจ้งเตือน ไม่สามารถเปลี่ยนได้ด้วยตนเอง
                            หากต้องการเปลี่ยนบัญชี LINE กรุณาติดต่อผู้ดูแลระบบ
                        </p>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3>การแจ้งเตือน</h3>
                    <div class="notification-settings">
                        <div class="form-check">
                            <input type="checkbox" id="notification_attendance" checked disabled>
                            <label for="notification_attendance">การเข้าแถวของนักเรียน</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="notification_absence" checked disabled>
                            <label for="notification_absence">การขาดแถวของนักเรียน</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="notification_risk" checked disabled>
                            <label for="notification_risk">ความเสี่ยงไม่ผ่านกิจกรรม</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="notification_announcement" checked disabled>
                            <label for="notification_announcement">ประกาศจากวิทยาลัย</label>
                        </div>
                        <p class="notification-info-message">
                            การตั้งค่าการแจ้งเตือนถูกกำหนดโดยระบบ ไม่สามารถปรับเปลี่ยนได้ด้วยตนเอง
                        </p>
                    </div>
                </div>
                
            
            </div>
        </div>
    </div>
</div>

<style>
/* สไตล์สำหรับหน้าโปรไฟล์ผู้ปกครอง */
.profile-section {
    margin-bottom: 20px;
}

.profile-header {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.profile-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: var(--primary-color-light);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-right: 20px;
    flex-shrink: 0;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-icon {
    font-size: 80px;
    color: var(--primary-color);
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.profile-role {
    font-size: 16px;
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 10px;
}

.profile-relationship {
    font-size: 14px;
    color: var(--text-light);
}

.relationship-label {
    font-weight: 500;
    margin-right: 5px;
}

/* แท็บนำทาง */
.profile-tabs {
    display: flex;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.tab-button {
    flex: 1;
    padding: 15px 0;
    text-align: center;
    background: none;
    border: none;
    font-weight: 600;
    font-size: 14px;
    color: var(--text-light);
    position: relative;
    cursor: pointer;
    transition: color var(--transition-speed);
}

.tab-button.active {
    color: var(--primary-color);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: var(--primary-color);
}

/* เนื้อหาของแท็บ */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* การ์ดฟอร์ม */
.form-card {
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    margin-bottom: 20px;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
}

.card-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.card-body {
    padding: 20px;
}

/* ฟอร์มข้อมูลส่วนตัว */
.profile-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color var(--transition-speed);
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--primary-color);
    outline: none;
}

.form-group small {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 5px;
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 10px;
}

/* ปุ่มกด */
.btn-primary,
.btn-secondary,
.btn-danger,
.btn-view {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    transition: background-color var(--transition-speed);
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-color-dark);
}

.btn-secondary {
    background-color: var(--bg-light);
    color: var(--text-dark);
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #d32f2f;
}

.btn-view {
    background-color: var(--secondary-color-light);
    color: var(--secondary-color);
}

.btn-view:hover {
    background-color: #d2e8fd;
}

.btn-primary .material-icons,
.btn-secondary .material-icons,
.btn-danger .material-icons,
.btn-view .material-icons {
    margin-right: 5px;
    font-size: 18px;
}

.btn-logout {
    width: 100%;
}

/* รายการนักเรียนในความดูแล */
.children-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.child-item {
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.child-info {
    flex: 1;
}

.child-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.child-id, .child-class {
    font-size: 14px;
    color: var(--text-light);
}

.manage-children {
    margin-top: 20px;
    text-align: center;
}

/* ตั้งค่า */
.settings-section {
    margin-bottom: 25px;
}

.settings-section h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.line-account-info,
.notification-settings {
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 15px;
}

.info-item {
    margin-bottom: 10px;
}

.info-label {
    font-weight: 500;
    margin-right: 10px;
}

.line-info-message,
.notification-info-message {
    margin-top: 15px;
    font-size: 14px;
    color: var(--text-muted);
    font-style: italic;
}

.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.form-check input {
    margin-right: 10px;
}

/* กรณีไม่มีข้อมูล */
.no-data {
    text-align: center;
    padding: 40px 20px;
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #e0e0e0;
}

.no-data-message {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 20px;
}

.no-data-action {
    margin-top: 15px;
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .profile-form {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-image {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .child-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .child-actions {
        width: 100%;
    }
    
    .btn-view {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .profile-tabs {
        flex-direction: column;
        border-radius: 8px;
    }
    
    .tab-button {
        padding: 12px 0;
    }
    
    .tab-button.active::after {
        width: 40px;
        left: 50%;
        transform: translateX(-50%);
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-primary,
    .btn-secondary,
    .btn-danger {
        width: 100%;
    }
}
</style>

<script>
// ฟังก์ชันสำหรับสลับแท็บ
function switchTab(tabName) {
    // ซ่อนทุกแท็บก่อน
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ลบคลาส active จากทุกปุ่มแท็บ
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // แสดงแท็บที่เลือกและตั้งค่าปุ่มที่เกี่ยวข้อง
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // ตั้งค่าปุ่มที่เกี่ยวข้องเป็น active
    const activeButtonIndex = tabName === 'personal-info' ? 0 : (tabName === 'children' ? 1 : 2);
    tabButtons[activeButtonIndex].classList.add('active');
}

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบ URL สำหรับพารามิเตอร์แท็บ
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    // เปิดแท็บตามพารามิเตอร์ (หรือค่าเริ่มต้น)
    if (tabParam === 'children') {
        switchTab('children');
    } else if (tabParam === 'settings') {
        switchTab('settings');
    } else {
        switchTab('personal-info');
    }
    
    // ตั้งค่าการยืนยันก่อนออกจากฟอร์มที่มีการเปลี่ยนแปลง
    const form = document.querySelector('.profile-form');
    let formChanged = false;
    
    const formInputs = form.querySelectorAll('input, select');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            formChanged = true;
        });
    });
    
    form.addEventListener('submit', function() {
        formChanged = false; // รีเซ็ตเมื่อกดบันทึก
    });
    
    form.addEventListener('reset', function() {
        formChanged = false; // รีเซ็ตเมื่อกดยกเลิก
        setTimeout(() => {
            alert('ยกเลิกการแก้ไขเรียบร้อย');
        }, 100);
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            const confirmationMessage = 'คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
});
</script>
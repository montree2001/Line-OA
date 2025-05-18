<div class="header">
    <a href="student_profile.php" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>แก้ไขข้อมูลส่วนตัว</h1>
    <div class="header-icon">
        <span class="material-icons">help_outline</span>
    </div>
</div>

<div class="container">
    <?php if (!empty($message)): ?>
    <div class="alert-message success">
        <div class="alert-icon"><span class="material-icons">check_circle</span></div>
        <div class="alert-content"><?php echo $message; ?></div>
        <button class="close-alert"><span class="material-icons">close</span></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="alert-message error">
        <div class="alert-icon"><span class="material-icons">error</span></div>
        <div class="alert-content"><?php echo $error; ?></div>
        <button class="close-alert"><span class="material-icons">close</span></button>
    </div>
    <?php endif; ?>

    <!-- การ์ดโปรไฟล์ -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-image-container">
                <?php if (!empty($student_info['profile_image'])): ?>
                <div class="profile-image">
                    <img src="<?php echo $student_info['profile_image']; ?>" alt="<?php echo $student_info['full_name']; ?>" id="profile-img">
                </div>
                <?php else: ?>
                <div class="profile-image">
                    <?php echo $student_info['avatar']; ?>
                </div>
                <?php endif; ?>
                <label for="upload-photo" class="change-photo">
                    <span class="material-icons">photo_camera</span>
                </label>
                <input type="file" id="upload-photo" accept="image/*" style="display: none;">
            </div>
            <div class="profile-info">
                <div class="profile-name"><?php echo $student_info['full_name']; ?></div>
                <div class="profile-details"><?php echo $student_info['class']; ?></div>
                <div class="profile-details">รหัสนักเรียน: <?php echo $student_info['student_code']; ?></div>
            </div>
        </div>
    </div>

    <!-- ฟอร์มแก้ไขข้อมูล -->
    <div class="form-card">
        <div class="card-title">
            <span class="material-icons">edit</span>
            แก้ไขข้อมูลส่วนตัว
        </div>
        
        <form id="edit-profile-form" method="post" action="edit_profile.php">
            <input type="hidden" name="student_id" value="<?php echo $student_info['id']; ?>">
            
            <div class="form-group">
                <label for="title">คำนำหน้า <span class="required">*</span></label>
                <select id="title" name="title" class="form-control" required>
                    <option value="นาย" <?php echo ($student_info['title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                    <option value="นางสาว" <?php echo ($student_info['title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                    <option value="อื่นๆ" <?php echo ($student_info['title'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="first_name">ชื่อ <span class="required">*</span></label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo $student_info['first_name']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">นามสกุล <span class="required">*</span></label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo $student_info['last_name']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="student_code">รหัสนักศึกษา <span class="required">*</span></label>
                <input type="text" id="student_code" name="student_code" class="form-control" value="<?php echo $student_info['student_code']; ?>" required maxlength="11" pattern="\d{11}" title="รหัสนักศึกษาต้องเป็นตัวเลข 11 หลักเท่านั้น">
                <div class="form-helper">รหัสประจำตัวนักศึกษาของคุณ (ตัวเลข 11 หลักเท่านั้น)</div>
            </div>

            <!-- ส่วนแก้ไขข้อมูลชั้นเรียน -->
            <div class="form-group">
                <label for="level">ระดับชั้น <span class="required">*</span></label>
                <select id="level" name="level" class="form-control" required>
                    <option value="ปวช.1" <?php echo ($student_info['level'] == 'ปวช.1') ? 'selected' : ''; ?>>ปวช.1</option>
                    <option value="ปวช.2" <?php echo ($student_info['level'] == 'ปวช.2') ? 'selected' : ''; ?>>ปวช.2</option>
                    <option value="ปวช.3" <?php echo ($student_info['level'] == 'ปวช.3') ? 'selected' : ''; ?>>ปวช.3</option>
                    <option value="ปวส.1" <?php echo ($student_info['level'] == 'ปวส.1') ? 'selected' : ''; ?>>ปวส.1</option>
                    <option value="ปวส.2" <?php echo ($student_info['level'] == 'ปวส.2') ? 'selected' : ''; ?>>ปวส.2</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="department_id">แผนกวิชา <span class="required">*</span></label>
                <select id="department_id" name="department_id" class="form-control" required>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>" <?php echo ($dept['department_id'] == $student_info['department_id']) ? 'selected' : ''; ?>>
                        <?php echo $dept['department_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="group_number">กลุ่มเรียน <span class="required">*</span></label>
                <select id="group_number" name="group_number" class="form-control" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($student_info['group_number'] == $i) ? 'selected' : ''; ?>>
                        กลุ่ม <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์ <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $student_info['phone']; ?>" required>
                <div class="form-helper">เบอร์โทรศัพท์ที่สามารถติดต่อได้</div>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo $student_info['email']; ?>">
                <div class="form-helper">อีเมลสำหรับรับข้อมูลข่าวสาร</div>
            </div>
            
            <div class="info-note" style="margin: 15px 0 20px 0;">
                <div class="note-icon"><span class="material-icons">info</span></div>
                <div class="note-content">
                    <p>การแก้ไขข้อมูลชั้นเรียนและแผนกวิชาควรตรงกับความเป็นจริง</p>
                    <p>หากมีข้อสงสัย โปรดติดต่อครูที่ปรึกษาเพื่อตรวจสอบข้อมูล</p>
                </div>
            </div>
            
            <div class="form-group form-actions">
                <button type="button" class="btn-cancel" onclick="window.location.href='student_profile.php'">
                    <span class="material-icons">close</span> ยกเลิก
                </button>
                <button type="submit" class="btn-submit">
                    <span class="material-icons">save</span> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal สำหรับแสดงผลการอัพโหลดรูปภาพ -->
<div id="upload-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>อัพโหลดรูปโปรไฟล์</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="image-preview-container">
                <img id="image-preview" src="#" alt="ตัวอย่างรูปภาพ">
            </div>
            <div class="image-controls">
                <div class="slider-container">
                    <span>ขนาด: </span>
                    <input type="range" id="zoom-slider" min="100" max="150" value="100">
                </div>
            </div>
            <div class="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">0%</div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="cancel-upload" class="btn-cancel">ยกเลิก</button>
            <button id="confirm-upload" class="btn-confirm">อัพโหลด</button>
        </div>
    </div>
</div>

<div id="loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text">กำลังอัพโหลด...</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ปิดข้อความแจ้งเตือนเมื่อคลิกปุ่มปิด
    const closeButtons = document.querySelectorAll('.close-alert');
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const alertMessage = this.closest('.alert-message');
            alertMessage.style.opacity = '0';
            setTimeout(function() {
                alertMessage.style.display = 'none';
            }, 300);
        });
    });
    
    // ซ่อนข้อความแจ้งเตือนหลังจาก 5 วินาที
    const alertMessages = document.querySelectorAll('.alert-message');
    alertMessages.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    // ตรวจสอบฟอร์มก่อนส่ง
    const form = document.getElementById('edit-profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const studentCodeInput = document.getElementById('student_code');
            const phoneInput = document.getElementById('phone');
            const emailInput = document.getElementById('email');
            
            let isValid = true;
            
            // ตรวจสอบชื่อ
            if (!firstNameInput.value.trim()) {
                isValid = false;
                showInputError(firstNameInput, 'กรุณากรอกชื่อ');
            } else {
                hideInputError(firstNameInput);
            }
            
            // ตรวจสอบนามสกุล
            if (!lastNameInput.value.trim()) {
                isValid = false;
                showInputError(lastNameInput, 'กรุณากรอกนามสกุล');
            } else {
                hideInputError(lastNameInput);
            }
            
            // ตรวจสอบรหัสนักเรียน
            if (!studentCodeInput.value.trim()) {
                isValid = false;
                showInputError(studentCodeInput, 'กรุณากรอกรหัสนักศึกษา');
            } else if (!/^\d{11}$/.test(studentCodeInput.value.trim())) {
                isValid = false;
                showInputError(studentCodeInput, 'รหัสนักศึกษาต้องเป็นตัวเลข 11 หลักเท่านั้น');
            } else {
                hideInputError(studentCodeInput);
            }
            
            // ตรวจสอบเบอร์โทรศัพท์
            if (!phoneInput.value) {
                isValid = false;
                showInputError(phoneInput, 'กรุณากรอกเบอร์โทรศัพท์');
            } else if (!/^[0-9\-]{9,15}$/.test(phoneInput.value)) {
                isValid = false;
                showInputError(phoneInput, 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
            } else {
                hideInputError(phoneInput);
            }
            
            // ตรวจสอบอีเมล (ถ้ามี)
            if (emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                isValid = false;
                showInputError(emailInput, 'รูปแบบอีเมลไม่ถูกต้อง');
            } else {
                hideInputError(emailInput);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    function showInputError(input, message) {
        const parent = input.parentElement;
        let errorDiv = parent.querySelector('.form-error');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            parent.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        input.classList.add('error');
    }
    
    function hideInputError(input) {
        const parent = input.parentElement;
        const errorDiv = parent.querySelector('.form-error');
        
        if (errorDiv) {
            parent.removeChild(errorDiv);
        }
        
        input.classList.remove('error');
    }
});
</script>
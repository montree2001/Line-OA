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
                <label for="phone">เบอร์โทรศัพท์ <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $student_info['phone']; ?>" required>
                <div class="form-helper">เบอร์โทรศัพท์ที่สามารถติดต่อได้</div>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo $student_info['email']; ?>">
                <div class="form-helper">อีเมลสำหรับรับข้อมูลข่าวสาร</div>
            </div>
            
            <div class="form-group">
                <label for="line_id">ไลน์ไอดี</label>
                <input type="text" id="line_id" name="line_id" class="form-control" value="<?php echo $student_info['line_id']; ?>">
                <div class="form-helper">ไลน์ไอดีสำหรับรับการแจ้งเตือน</div>
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

    <div class="info-note">
        <div class="note-icon"><span class="material-icons">info</span></div>
        <div class="note-content">
            <p>ข้อมูลชั้นเรียนและรหัสนักเรียนไม่สามารถแก้ไขได้ด้วยตนเอง</p>
            <p>หากต้องการแก้ไขข้อมูลดังกล่าว โปรดติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน</p>
        </div>
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
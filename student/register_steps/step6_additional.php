<div class="card">
    <h2 class="card-title">ข้อมูลเพิ่มเติมและการยินยอม</h2>
    
    <?php
    // ดึงข้อมูลที่ได้เลือกไว้จาก Session
    $class_info = $_SESSION['selected_class_info'] ?? null;
    $department_name = $_SESSION['selected_department_name'] ?? 'ไม่ระบุ';
    $advisor_name = $_SESSION['selected_teacher_name'] ?? ($_SESSION['advisor_name'] ?? 'ไม่ระบุ');
    
    // ข้อมูลระดับชั้นและกลุ่ม
    $level = $_SESSION['selected_level'] ?? 'ไม่ระบุ';
    $group_number = $_SESSION['selected_group'] ?? 'ไม่ระบุ';
    ?>
    
    <div class="profile-info-section">
        <h3>สรุปข้อมูลการลงทะเบียน</h3>
        <div class="info-item">
            <div class="info-label">รหัสนักศึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_code'] ?? ''); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">ชื่อ-นามสกุล:</div>
            <div class="info-value">
                <?php 
                    echo htmlspecialchars($_SESSION['student_title'] ?? ''); 
                    echo ' ';
                    echo htmlspecialchars($_SESSION['student_first_name'] ?? ''); 
                    echo ' ';
                    echo htmlspecialchars($_SESSION['student_last_name'] ?? ''); 
                ?>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">ระดับชั้น:</div>
            <div class="info-value"><?php echo htmlspecialchars($level); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">กลุ่มเรียน:</div>
            <div class="info-value"><?php echo htmlspecialchars($group_number); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">แผนกวิชา:</div>
            <div class="info-value"><?php echo htmlspecialchars($department_name); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">ครูที่ปรึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($advisor_name); ?></div>
        </div>
    </div>
    
    <form method="POST" action="register_process.php" enctype="multipart/form-data">
        <input type="hidden" name="step" value="6">
        
        <div class="input-container">
            <label for="address" class="input-label">ที่อยู่ปัจจุบัน (ถ้ามี)</label>
            <textarea id="address" name="address" class="input-field" rows="3" placeholder="กรอกที่อยู่ปัจจุบัน"></textarea>
        </div>
        
        <div class="input-container">
            <label for="emergency_contact" class="input-label">ผู้ติดต่อฉุกเฉิน (ถ้ามี)</label>
            <input type="text" id="emergency_contact" name="emergency_contact" class="input-field" placeholder="ชื่อผู้ติดต่อฉุกเฉิน">
        </div>
        
        <div class="input-container">
            <label for="emergency_phone" class="input-label">เบอร์โทรผู้ติดต่อฉุกเฉิน (ถ้ามี)</label>
            <input type="tel" id="emergency_phone" name="emergency_phone" class="input-field" placeholder="เบอร์โทรผู้ติดต่อฉุกเฉิน">
        </div>
        
        <div class="input-container">
            <label class="input-label">อัพโหลดรูปโปรไฟล์ (ถ้ามี)</label>
            <div class="upload-area" id="dropArea">
                <span class="upload-icon material-icons">cloud_upload</span>
                <p class="upload-text">คลิกหรือลากไฟล์มาวางที่นี่</p>
                <p class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 2MB</p>
                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png" style="display: none;">
            </div>
            <div id="image-preview" style="display: none;">
                <img id="preview-img" src="#" alt="ตัวอย่างรูปภาพ">
                <button type="button" id="reset-image" class="btn secondary mt-10">เลือกรูปใหม่</button>
            </div>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
            <label for="gdpr_consent" class="checkbox-label">
                ข้าพเจ้ายินยอมให้เก็บข้อมูลส่วนบุคคลตาม พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล พ.ศ.2562 เพื่อใช้ในระบบการเช็คชื่อเข้าแถวออนไลน์
            </label>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="terms_conditions" name="terms_conditions" required>
            <label for="terms_conditions" class="checkbox-label">
                ข้าพเจ้ายอมรับเงื่อนไขและข้อตกลงการใช้งานระบบทุกประการ
            </label>
        </div>
        
        <button type="submit" class="btn primary">
            ลงทะเบียน <span class="material-icons">how_to_reg</span>
        </button>
    </form>
</div>

<script>
// JavaScript สำหรับการอัพโหลดรูปภาพ
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('profile_image');
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const resetBtn = document.getElementById('reset-image');
    
    // คลิกที่พื้นที่อัพโหลดเพื่อเลือกไฟล์
    dropArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // เมื่อเลือกไฟล์
    fileInput.addEventListener('change', function() {
        showPreview(this);
    });
    
    // Drag & Drop
    dropArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropArea.classList.add('active');
    });
    
    dropArea.addEventListener('dragleave', function() {
        dropArea.classList.remove('active');
    });
    
    dropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        dropArea.classList.remove('active');
        fileInput.files = e.dataTransfer.files;
        showPreview(fileInput);
    });
    
    // รีเซ็ตการอัพโหลดรูปภาพ
    resetBtn.addEventListener('click', function() {
        fileInput.value = '';
        preview.style.display = 'none';
        dropArea.style.display = 'block';
    });
    
    // ฟังก์ชันแสดงตัวอย่างรูปภาพ
    function showPreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('กรุณาเลือกไฟล์ขนาดไม่เกิน 2MB');
                input.value = '';
                return;
            }
            
            // ตรวจสอบประเภทไฟล์
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                alert('กรุณาเลือกไฟล์รูปภาพประเภท JPG หรือ PNG เท่านั้น');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                dropArea.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }
});
</script>
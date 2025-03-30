<div class="card">
    <h2 class="card-title">กรอกข้อมูลส่วนตัว</h2>
    
    <p>ไม่พบข้อมูลสำหรับรหัสนักศึกษา <strong><?php echo htmlspecialchars($_SESSION['student_code'] ?? ''); ?></strong></p>
    <p>กรุณากรอกข้อมูลส่วนตัวของคุณเพื่อลงทะเบียน</p>
    
    <form method="POST" action="register_process.php">
        <input type="hidden" name="step" value="33">
        
        <div class="input-container">
            <label class="input-label">คำนำหน้า</label>
            <select name="title" class="input-field" required>
                <option value="" disabled selected>เลือกคำนำหน้า</option>
                <option value="นาย">นาย</option>
                <option value="นางสาว">นางสาว</option>
                <option value="อื่นๆ">อื่นๆ</option>
            </select>
        </div>
        
        <div class="input-container">
            <label for="first_name" class="input-label">ชื่อจริง</label>
            <input type="text" id="first_name" name="first_name" class="input-field" placeholder="กรอกชื่อจริง" required>
        </div>
        
        <div class="input-container">
            <label for="last_name" class="input-label">นามสกุล</label>
            <input type="text" id="last_name" name="last_name" class="input-field" placeholder="กรอกนามสกุล" required>
        </div>
        
        <div class="input-container">
            <label for="phone" class="input-label">เบอร์โทรศัพท์ (ถ้ามี)</label>
            <input type="tel" id="phone" name="phone" class="input-field" placeholder="กรอกเบอร์โทรศัพท์">
            <div class="help-text">ใช้สำหรับการติดต่อและแจ้งเตือน</div>
        </div>
        
        <div class="input-container">
            <label for="email" class="input-label">อีเมล (ถ้ามี)</label>
            <input type="email" id="email" name="email" class="input-field" placeholder="กรอกอีเมล">
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="confirm_info" name="confirm_info" required>
            <label for="confirm_info" class="checkbox-label">
                ข้าพเจ้าขอรับรองว่าข้อมูลดังกล่าวเป็นความจริงทุกประการ
            </label>
        </div>
        
        <button type="submit" class="btn primary">
            บันทึกข้อมูล <span class="material-icons">arrow_forward</span>
        </button>
    </form>
    
    <div class="mt-20 text-center">
        <a href="register.php?step=2" class="home-button">
            <span class="material-icons">arrow_back</span> กลับไปค้นหาใหม่
        </a>
    </div>
</div>
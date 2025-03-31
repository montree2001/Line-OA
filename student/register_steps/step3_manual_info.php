<div class="card">
    <div class="card-title">กรอกข้อมูลนักเรียน</div>
    
    <p class="mb-20">
        กรุณากรอกข้อมูลพื้นฐานของคุณเพื่อลงทะเบียนในระบบ
    </p>
    
    <form method="post" action="">
        <div class="input-container">
            <label class="input-label" for="student_code">รหัสนักศึกษา 11 หลัก</label>
            <input type="text" id="student_code" name="student_code" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" value="<?php echo isset($_SESSION['student_code']) ? htmlspecialchars($_SESSION['student_code']) : ''; ?>" maxlength="11" required>
            <div class="help-text">ตัวอย่างเช่น: 65201230001</div>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="title">คำนำหน้า</label>
            <select id="title" name="title" class="input-field" required>
                <option value="นาย" <?php echo (isset($_SESSION['student_title']) && $_SESSION['student_title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                <option value="นางสาว" <?php echo (isset($_SESSION['student_title']) && $_SESSION['student_title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                <option value="อื่นๆ" <?php echo (isset($_SESSION['student_title']) && $_SESSION['student_title'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
            </select>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="first_name">ชื่อ</label>
            <input type="text" id="first_name" name="first_name" class="input-field" placeholder="กรอกชื่อจริง" value="<?php echo isset($_SESSION['student_first_name']) ? htmlspecialchars($_SESSION['student_first_name']) : ''; ?>" required>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name" class="input-field" placeholder="กรอกนามสกุล" value="<?php echo isset($_SESSION['student_last_name']) ? htmlspecialchars($_SESSION['student_last_name']) : ''; ?>" required>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="phone">เบอร์โทรศัพท์</label>
            <input type="tel" id="phone" name="phone" class="input-field" placeholder="กรอกเบอร์โทรศัพท์" value="<?php echo isset($_SESSION['student_phone']) ? htmlspecialchars($_SESSION['student_phone']) : ''; ?>">
            <div class="help-text">ไม่จำเป็นต้องกรอก แต่แนะนำให้กรอกเพื่อใช้ในการติดต่อ</div>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="email">อีเมล</label>
            <input type="email" id="email" name="email" class="input-field" placeholder="กรอกอีเมล" value="<?php echo isset($_SESSION['student_email']) ? htmlspecialchars($_SESSION['student_email']) : ''; ?>">
            <div class="help-text">ไม่จำเป็นต้องกรอก</div>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="privacy_policy" name="privacy_policy" required>
            <label for="privacy_policy" class="checkbox-label">
                ฉันยอมรับ<a href="#" class="text-link" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>และยินยอมให้จัดเก็บข้อมูลส่วนบุคคลของฉันเพื่อใช้ในระบบ
            </label>
        </div>
        
        <button type="submit" name="submit_manual_info" class="btn primary">
            บันทึกข้อมูล
            <span class="material-icons">save</span>
        </button>
    </form>
</div>

<div class="contact-admin">
    หากคุณมีปัญหาในการลงทะเบียน กรุณาติดต่อครูที่ปรึกษา หรือเจ้าหน้าที่ทะเบียน
</div>

<script>
    function showPrivacyPolicy() {
        alert("นโยบายความเป็นส่วนตัว\n\nระบบน้องชูใจ AI ดูแลผู้เรียน มีการจัดเก็บข้อมูลส่วนบุคคลของนักเรียนเพื่อใช้ในการเช็คชื่อเข้าแถวและดูแลผู้เรียน ข้อมูลที่จัดเก็บจะถูกใช้เฉพาะในระบบนี้เท่านั้น และจะไม่เปิดเผยต่อบุคคลภายนอกโดยไม่ได้รับอนุญาต");
    }
</script>
<div class="card">
    <div class="card-title">ค้นหาข้อมูลนักเรียน</div>
    
    <p class="mb-20">
        กรุณาใส่รหัสนักศึกษา 11 หลักของคุณ เพื่อค้นหาข้อมูลในระบบ หากคุณเป็นนักเรียนใหม่ระบบจะให้คุณลงทะเบียนข้อมูลพื้นฐาน
    </p>
    
    <form method="post" action="">
        <div class="input-container">
            <label class="input-label" for="student_code">รหัสนักศึกษา 11 หลัก</label>
            <input type="text" id="student_code" name="student_code" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" value="<?php echo isset($_POST['student_code']) ? htmlspecialchars($_POST['student_code']) : ''; ?>" maxlength="11" required>
            <div class="help-text">ตัวอย่างเช่น: 65201230001</div>
        </div>
        
        <button type="submit" name="search_student" class="btn primary">
            ค้นหารหัสนักศึกษา
            <span class="material-icons">search</span>
        </button>
    </form>
    
    <div class="text-center mt-30">
        <p>หากไม่ทราบรหัสนักศึกษา หรือเป็นนักเรียนใหม่ สามารถกดปุ่มด้านล่างเพื่อกรอกข้อมูลด้วยตนเอง</p>
        <a href="register.php?step=33" class="btn secondary mt-10">กรอกข้อมูลด้วยตนเอง</a>
    </div>
</div>

<div class="contact-admin">
    หากคุณมีปัญหาในการลงทะเบียน กรุณาติดต่อครูที่ปรึกษา หรือเจ้าหน้าที่ทะเบียน
</div>
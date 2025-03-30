<div class="card">
    <h2 class="card-title">ค้นหาข้อมูลนักศึกษา</h2>
    
    <div class="intro-icon text-center">
        <span class="material-icons">search</span>
    </div>
    
    <p>กรุณากรอกรหัสนักศึกษา 11 หลักของคุณ เพื่อค้นหาข้อมูลในระบบ</p>
    
    <form method="POST" action="register_process.php">
        <input type="hidden" name="step" value="2">
        
        <div class="input-container">
            <label for="student_code" class="input-label">รหัสนักศึกษา</label>
            <input type="text" id="student_code" name="student_code" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" required maxlength="11" pattern="[0-9]{11}">
            <div class="help-text">เช่น 64309010001</div>
        </div>
        
        <button type="submit" class="btn primary">
            ค้นหาข้อมูล <span class="material-icons">search</span>
        </button>
    </form>
    
    <div class="contact-admin mt-20">
        หากไม่ทราบรหัสนักศึกษาหรือมีปัญหาในการค้นหา <br>
        กรุณาติดต่อครูที่ปรึกษาหรือ <a href="#" class="text-link">ผู้ดูแลระบบ</a>
    </div>
</div>
<div class="card">
    <h2 class="card-title">ค้นหาข้อมูลนักศึกษา</h2>
    
    <p class="mb-20">
        กรุณากรอกรหัสนักศึกษา 11 หลักของคุณ เพื่อค้นหาข้อมูลในระบบ หากไม่พบข้อมูล คุณสามารถลงทะเบียนได้ในขั้นตอนถัดไป
    </p>
    
    <form method="post" action="register.php?step=2" id="search-form">
        <input type="hidden" name="action" value="search_student">
        
        <div class="input-container">
            <label for="student_code" class="input-label">รหัสนักศึกษา</label>
            <input type="text" id="student_code" name="student_code" class="input-field" 
                placeholder="กรอกรหัสนักศึกษา 11 หลัก" maxlength="11" required>
            <div class="help-text">เช่น 64309010001</div>
        </div>
        
        <div class="text-center mt-30">
            <a href="register.php?step=1" class="btn secondary">
                <span class="material-icons">arrow_back</span> กลับ
            </a>
            <button type="submit" class="btn primary">
                ค้นหาข้อมูล <span class="material-icons">search</span>
            </button>
        </div>
    </form>
</div>

<div class="skip-section">
    <p>ไม่ทราบรหัสนักศึกษา? คุณสามารถสอบถามจากครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน</p>
</div>

<script>
    // ตรวจสอบความถูกต้องของรหัสนักศึกษา
    document.getElementById('search-form').addEventListener('submit', function(e) {
        const studentCode = document.getElementById('student_code').value.trim();
        
        // ตรวจสอบว่ากรอกรหัสนักศึกษาหรือไม่
        if (studentCode === '') {
            e.preventDefault();
            alert('กรุณากรอกรหัสนักศึกษา');
            return;
        }
        
        // ตรวจสอบรูปแบบรหัสนักศึกษา (ตัวเลข 11 หลัก)
        if (!/^\d{11}$/.test(studentCode)) {
            e.preventDefault();
            alert('รหัสนักศึกษาต้องเป็นตัวเลข 11 หลักเท่านั้น');
            return;
        }
    });
</script>
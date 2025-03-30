<div class="card">
    <h2 class="card-title">กรอกข้อมูลนักศึกษา</h2>
    
    <div class="alert alert-warning">
        <span class="material-icons">info</span>
        <span>ไม่พบข้อมูลนักศึกษารหัส <?php echo htmlspecialchars($_SESSION['student_code']); ?> ในระบบ กรุณากรอกข้อมูลเพิ่มเติม</span>
    </div>
    
    <form method="post" action="register.php?step=33" id="manual-info-form">
        <input type="hidden" name="action" value="manual_info">
        
        <div class="input-container">
            <label for="title" class="input-label">คำนำหน้า <span class="text-danger">*</span></label>
            <select id="title" name="title" class="input-field" required>
                <option value="">เลือกคำนำหน้า</option>
                <option value="นาย">นาย</option>
                <option value="นางสาว">นางสาว</option>
                <option value="อื่นๆ">อื่นๆ</option>
            </select>
        </div>
        
        <div class="input-container">
            <label for="first_name" class="input-label">ชื่อ <span class="text-danger">*</span></label>
            <input type="text" id="first_name" name="first_name" class="input-field" placeholder="กรอกชื่อจริง" required>
        </div>
        
        <div class="input-container">
            <label for="last_name" class="input-label">นามสกุล <span class="text-danger">*</span></label>
            <input type="text" id="last_name" name="last_name" class="input-field" placeholder="กรอกนามสกุล" required>
        </div>
        
        <div class="text-center mt-30">
            <a href="register.php?step=2" class="btn secondary">
                <span class="material-icons">arrow_back</span> กลับ
            </a>
            <button type="submit" class="btn primary">
                ถัดไป <span class="material-icons">arrow_forward</span>
            </button>
        </div>
    </form>
</div>

<div class="skip-section">
    <p>หากไม่แน่ใจว่าข้อมูลที่กรอกถูกต้องหรือไม่ กรุณาติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน</p>
</div>

<script>
    // ตรวจสอบความถูกต้องของฟอร์ม
    document.getElementById('manual-info-form').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        
        if (title === '' || firstName === '' || lastName === '') {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }
        
        // ตรวจสอบว่าชื่อและนามสกุลเป็นภาษาไทยหรือภาษาอังกฤษเท่านั้น
        const namePattern = /^[ก-๙a-zA-Z\s]+$/;
        if (!namePattern.test(firstName) || !namePattern.test(lastName)) {
            e.preventDefault();
            alert('ชื่อและนามสกุลต้องเป็นภาษาไทยหรือภาษาอังกฤษเท่านั้น');
            return;
        }
    });
</script>
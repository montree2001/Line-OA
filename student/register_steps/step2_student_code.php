<!-- ขั้นตอนค้นหารหัสนักศึกษา -->
<div class="card">
    <div class="card-title">กรอกรหัสนักศึกษา</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=2">
            <div class="input-container">
                <label class="input-label">รหัสนักศึกษา (11 หลัก)</label>
                <input type="text" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" maxlength="11" name="student_code" pattern="[0-9]{11}" inputmode="numeric" required>
                <div class="help-text">กรุณากรอกเฉพาะตัวเลข 11 หลัก</div>
            </div>

            <button type="submit" class="btn primary">
                <span class="material-icons">search</span> ค้นหาข้อมูล
            </button>

            <div class="contact-admin">
                หากมีปัญหาในการค้นหาข้อมูล กรุณา<a href="#">ติดต่อเจ้าหน้าที่</a>
            </div>
        </form>
    </div>
</div>
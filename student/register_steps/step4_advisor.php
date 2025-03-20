<!-- ขั้นตอนค้นหาครูที่ปรึกษา -->
<div class="card">
    <div class="card-title">ค้นหาครูที่ปรึกษา</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=4">
            <div class="input-container">
                <label class="input-label">ค้นหาจากชื่อครูที่ปรึกษา</label>
                <input type="text" class="input-field" name="teacher_name" placeholder="กรอกชื่อหรือนามสกุลครูที่ปรึกษา" required>
                <div class="help-text">เช่น สมชาย, ใจดี, อาจารย์วันดี</div>
            </div>

            <button type="submit" class="btn primary">
                <span class="material-icons">search</span> ค้นหาครูที่ปรึกษา
            </button>

            <div class="skip-section">
                <p>หากไม่ทราบชื่อครูที่ปรึกษา คุณสามารถ</p>
                <a href="register.php?step=5manual" class="text-link">ระบุข้อมูลชั้นเรียนเอง</a>
            </div>
        </form>
    </div>
</div>
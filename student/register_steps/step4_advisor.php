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
            <!-- สร้างแจ้งเตือนเมื่อไม่พบครูที่ปรึกษา -->
            <?php if (isset($_SESSION['alert_message'])): ?>
                <div class="alert alert-danger">
                    <span class="material-icons">error</span>
                    <span><?php echo $_SESSION['alert_message']; unset($_SESSION['alert_message']); ?></span>
                </div>
            <?php   endif; ?>
            <button type="submit" class="btn primary">
                <span class="material-icons">search</span> ค้นหาครูที่ปรึกษา
            </button>

            <div class="skip-section">
                <p>หากไม่ทราบชื่อครูที่ปรึกษา โปรดติดต่อเจ้าหน้าที่</p>
               
            </div>
        </form>
    </div>
</div>
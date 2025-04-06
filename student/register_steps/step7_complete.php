<div class="card success-card">
    <div class="success-icon">
        <span class="material-icons">check_circle</span>
    </div>
    
    <div class="success-message">
        <h2>ลงทะเบียนสำเร็จ!</h2>
        <p>
            คุณได้ลงทะเบียนเข้าสู่ระบบน้องชูใจ AI ดูแลผู้เรียนเรียบร้อยแล้ว<br>
            ขณะนี้คุณสามารถใช้งานระบบเช็คชื่อเข้าแถวออนไลน์ได้ทันที
        </p>
    </div>
    
    <div class="features-section">
        <div class="features-title">คุณสามารถใช้งานได้ดังนี้</div>
        
        <div class="feature-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="feature-title">เช็คชื่อด้วย GPS</div>
                <div class="feature-desc">เช็คชื่อผ่านระบบ GPS เมื่ออยู่ในรัศมีโรงเรียน</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">qr_code_scanner</span>
                </div>
                <div class="feature-title">สร้าง QR Code</div>
                <div class="feature-desc">สร้าง QR Code ให้ครูสแกนเพื่อเช็คชื่อ</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">assessment</span>
                </div>
                <div class="feature-title">ดูประวัติการเข้าแถว</div>
                <div class="feature-desc">ตรวจสอบสถิติและประวัติการเข้าแถวของตนเอง</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">edit</span>
                </div>
                <div class="feature-title">แก้ไขข้อมูลส่วนตัว</div>
                <div class="feature-desc">ปรับปรุงข้อมูลส่วนตัวให้เป็นปัจจุบัน</div>
            </div>
        </div>
    </div>
    
    <div class="mt-30">
        <a href="home.php" class="btn primary">
            เข้าสู่หน้าหลัก
            <span class="material-icons">home</span>
        </a>
    </div>
</div>

<div class="skip-section">
    <p>เข้าสู่ระบบอีกครั้งผ่าน LINE โดยเลือกบทบาทเป็นนักเรียน</p>
    <a href="../index.php" class="home-button">
        <span class="material-icons">logout</span> ออกจากระบบ
    </a>
</div>

<script>
    // ลบข้อมูลการลงทะเบียนออกจาก session storage
    sessionStorage.removeItem('registrationData');
    
    // ตั้งเวลาไปยังหน้าหลักโดยอัตโนมัติ
    setTimeout(function() {
        window.location.href = 'home.php';
    }, 10000); // 10 วินาที
</script>
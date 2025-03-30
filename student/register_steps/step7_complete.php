<div class="card success-card">
    <span class="material-icons success-icon">check_circle</span>
    
    <div class="success-message">
        <h2>ลงทะเบียนสำเร็จ!</h2>
        <p>ยินดีด้วย คุณได้ลงทะเบียนเข้าใช้งานระบบเช็คชื่อเข้าแถวออนไลน์เรียบร้อยแล้ว</p>
    </div>
    
    <p class="mb-20">ขณะนี้คุณสามารถใช้งานฟังก์ชันต่างๆ ของระบบได้ เช่น:</p>
    
    <div class="feature-grid mb-30">
        <div class="feature-item">
            <div class="feature-icon">
                <span class="material-icons">gps_fixed</span>
            </div>
            <div class="feature-title">เช็คชื่อด้วย GPS</div>
            <div class="feature-desc">เช็คชื่อเข้าแถวอย่างรวดเร็วเมื่ออยู่ในพื้นที่โรงเรียน</div>
        </div>
        
        <div class="feature-item">
            <div class="feature-icon">
                <span class="material-icons">qr_code_scanner</span>
            </div>
            <div class="feature-title">QR Code</div>
            <div class="feature-desc">สร้าง QR Code เพื่อให้ครูสแกนเช็คชื่อ</div>
        </div>
        
        <div class="feature-item">
            <div class="feature-icon">
                <span class="material-icons">assessment</span>
            </div>
            <div class="feature-title">ดูสถิติการเข้าแถว</div>
            <div class="feature-desc">ติดตามประวัติการเข้าแถวของคุณได้ทุกที่ทุกเวลา</div>
        </div>
        
        <div class="feature-item">
            <div class="feature-icon">
                <span class="material-icons">settings</span>
            </div>
            <div class="feature-title">จัดการโปรไฟล์</div>
            <div class="feature-desc">อัพเดทข้อมูลส่วนตัวและรูปโปรไฟล์ได้ตลอดเวลา</div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="home.php" class="btn primary">
            ไปยังหน้าหลัก <span class="material-icons">home</span>
        </a>
    </div>
</div>

<script>
    // ลบข้อมูลเซสชันที่เกี่ยวข้องกับการลงทะเบียน
    // (เป็นการดำเนินการแบบ client-side เสริมจากการล้างเซสชันใน PHP)
    
    // เปลี่ยนเส้นทาง URL ให้เป็นหน้า home เมื่อกดปุ่ม Back
    history.pushState(null, '', 'home.php');
    
    // หากผู้ใช้กด Back จะไม่กลับไปยังหน้าลงทะเบียนอีก
    window.addEventListener('popstate', function() {
        window.location.href = 'home.php';
    });
</script>
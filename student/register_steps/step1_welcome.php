<div class="card">
    <h2 class="card-title">ลงทะเบียนนักเรียน</h2>
    
    <div class="intro-icon text-center">
        <span class="material-icons">school</span>
    </div>
    
    <p class="text-center mb-20">ยินดีต้อนรับสู่ระบบลงทะเบียนเช็คชื่อเข้าแถวออนไลน์</p>
    
    <div class="profile-info-section">
        <h3>ข้อมูลที่ต้องเตรียม</h3>
        <ul style="padding-left: 20px; margin-bottom: 15px;">
            <li>รหัสนักศึกษา 11 หลัก</li>
            <li>ข้อมูลส่วนตัว ชื่อ-นามสกุล</li>
            <li>ชื่อครูที่ปรึกษาหรือข้อมูลห้องเรียน</li>
            <li>รูปถ่ายสำหรับใช้เป็นโปรไฟล์ (ถ้ามี)</li>
        </ul>
    </div>
    
    <p class="mb-20">ขั้นตอนการลงทะเบียนมี 5 ขั้นตอน ใช้เวลาประมาณ 2-3 นาที</p>
    
    <div class="features-section">
        <h3 class="features-title">ประโยชน์ที่จะได้รับ</h3>
        
        <div class="feature-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">schedule</span>
                </div>
                <div class="feature-title">ประหยัดเวลา</div>
                <div class="feature-desc">เช็คชื่อได้ทุกที่ ทุกเวลา</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">bar_chart</span>
                </div>
                <div class="feature-title">ติดตามสถิติ</div>
                <div class="feature-desc">ดูสถิติการเข้าแถวได้ทันที</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">notifications_active</span>
                </div>
                <div class="feature-title">รับการแจ้งเตือน</div>
                <div class="feature-desc">แจ้งเตือนผ่าน LINE ทันที</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">support_agent</span>
                </div>
                <div class="feature-title">ติดต่อง่าย</div>
                <div class="feature-desc">ติดต่อครูที่ปรึกษาได้สะดวก</div>
            </div>
        </div>
    </div>
    
    <form method="POST" action="register_process.php">
        <input type="hidden" name="step" value="1">
        <button type="submit" class="btn primary">
            เริ่มลงทะเบียน <span class="material-icons">arrow_forward</span>
        </button>
    </form>
</div>
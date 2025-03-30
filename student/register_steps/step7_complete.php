<div class="card success-card">
    <div class="success-icon">
        <span class="material-icons">check_circle</span>
    </div>
    
    <div class="success-message">
        <h2>ลงทะเบียนสำเร็จ!</h2>
        <p>คุณได้ลงทะเบียนเข้าใช้งานระบบ STD-Prasat เรียบร้อยแล้ว</p>
    </div>
    
    <div class="profile-info-section">
        <h3>ข้อมูลของคุณ</h3>
        <div class="info-item">
            <div class="info-label">รหัสนักศึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_code'] ?? ''); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">ชื่อ-นามสกุล:</div>
            <div class="info-value">
                <?php 
                    echo htmlspecialchars($_SESSION['student_title'] ?? ''); 
                    echo ' ';
                    echo htmlspecialchars($_SESSION['student_first_name'] ?? ''); 
                    echo ' ';
                    echo htmlspecialchars($_SESSION['student_last_name'] ?? ''); 
                ?>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">ระดับชั้น:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_level'] ?? 'ไม่ระบุ'); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">แผนกวิชา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_department_name'] ?? 'ไม่ระบุ'); ?></div>
        </div>
    </div>
    
    <div class="features-section">
        <h3 class="features-title">คุณสามารถใช้งานฟีเจอร์ต่างๆ ได้ดังนี้</h3>
        
        <div class="feature-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">how_to_reg</span>
                </div>
                <div class="feature-title">เช็คชื่อเข้าแถว</div>
                <div class="feature-desc">เช็คชื่อผ่าน GPS, QR Code หรือรหัส PIN</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">assessment</span>
                </div>
                <div class="feature-title">ดูสถิติการเข้าแถว</div>
                <div class="feature-desc">ตรวจสอบประวัติการเข้าแถวของคุณ</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">person</span>
                </div>
                <div class="feature-title">แก้ไขข้อมูลส่วนตัว</div>
                <div class="feature-desc">ปรับปรุงข้อมูลส่วนตัวให้เป็นปัจจุบัน</div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <span class="material-icons">notifications</span>
                </div>
                <div class="feature-title">รับการแจ้งเตือน</div>
                <div class="feature-desc">รับแจ้งเตือนจากระบบผ่านแอป LINE</div>
            </div>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn primary mt-30">
        เข้าสู่หน้าหลัก <span class="material-icons">home</span>
    </a>
</div>
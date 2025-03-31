<div class="card error-card">
    <div class="text-center">
        <span class="material-icons" style="font-size: 5rem; color: #f44336; margin-bottom: 20px;">error</span>
        
        <h2>เกิดข้อผิดพลาด</h2>
        <p class="mb-20">
            <?php echo $error_message ?? 'ไม่สามารถดำเนินการลงทะเบียนได้ในขณะนี้'; ?>
        </p>
        
        <div class="profile-info-section">
            <h3>สิ่งที่คุณควรทำ</h3>
            <ul style="list-style: none; padding-left: 10px;">
                <li style="margin-bottom: 10px;">
                    <span class="material-icons" style="vertical-align: middle; color: #06c755; margin-right: 5px;">refresh</span>
                    ลองโหลดหน้าเว็บใหม่อีกครั้ง
                </li>
                <li style="margin-bottom: 10px;">
                    <span class="material-icons" style="vertical-align: middle; color: #06c755; margin-right: 5px;">schedule</span>
                    ลองเข้าใช้งานในภายหลัง
                </li>
                <li style="margin-bottom: 10px;">
                    <span class="material-icons" style="vertical-align: middle; color: #06c755; margin-right: 5px;">person</span>
                    ติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียนเพื่อขอความช่วยเหลือ
                </li>
            </ul>
        </div>
        
        <div class="mt-30">
            <a href="register.php?step=1" class="btn secondary">
                <span class="material-icons">undo</span> เริ่มลงทะเบียนใหม่
            </a>
            
            <a href="../index.php" class="btn primary mt-10">
                <span class="material-icons">home</span> กลับหน้าหลัก
            </a>
        </div>
    </div>
</div>

<div class="contact-admin">
    หากยังพบปัญหา กรุณาส่งข้อความแจ้งปัญหาผ่านทาง LINE OA โดยระบุรายละเอียดปัญหาที่พบ
</div>
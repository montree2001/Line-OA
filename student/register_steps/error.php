<div class="card error-card">
    <span class="material-icons" style="font-size: 4rem; color: #f44336; margin-bottom: 20px;">error</span>
    
    <h2 class="mb-20">เกิดข้อผิดพลาด</h2>
    
    <p class="mb-20">
        <?php 
        if (!empty($error_message)) {
            echo htmlspecialchars($error_message);
        } else {
            echo "เกิดข้อผิดพลาดในระบบ โปรดลองใหม่ภายหลัง";
        }
        ?>
    </p>
    
    <div class="text-center">
        <a href="register.php?step=1" class="btn secondary">
            <span class="material-icons">refresh</span> ลองใหม่
        </a>
        
        <a href="index.php" class="btn primary" style="margin-left: 10px;">
            <span class="material-icons">home</span> กลับหน้าหลัก
        </a>
    </div>
</div>

<div class="contact-admin">
    หากคุณยังพบปัญหานี้อยู่ กรุณาติดต่อผู้ดูแลระบบที่ <a href="#" class="text-link">admin@prasat.ac.th</a> หรือแจ้งผ่านครูที่ปรึกษาของคุณ
</div>
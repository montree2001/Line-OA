<div class="card">
    <div class="card-title">ยืนยันข้อมูลนักเรียน</div>
    
    <p class="mb-20">
        พบข้อมูลนักเรียนในระบบ กรุณาตรวจสอบข้อมูลของคุณให้ถูกต้อง หากข้อมูลไม่ถูกต้องกรุณาติดต่อครูที่ปรึกษา
    </p>
    
    <div class="profile-info-section">
        <h3>ข้อมูลนักเรียน</h3>
        
        <div class="info-item">
            <div class="info-label">รหัสนักศึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_code']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">ชื่อ-นามสกุล:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_title'] . $_SESSION['student_first_name'] . ' ' . $_SESSION['student_last_name']); ?></div>
        </div>
        
        <?php if (isset($_SESSION['student_phone']) && !empty($_SESSION['student_phone'])): ?>
        <div class="info-item">
            <div class="info-label">เบอร์โทรศัพท์:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_phone']); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['student_email']) && !empty($_SESSION['student_email'])): ?>
        <div class="info-item">
            <div class="info-label">อีเมล:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_email']); ?></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="profile-info-section">
        <h3>ข้อมูลชั้นเรียน</h3>
        
        <div class="info-item">
            <div class="info-label">ระดับชั้น:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_level']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">แผนกวิชา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_department_name']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">กลุ่ม:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_group']); ?></div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['student_advisors']) && !empty($_SESSION['student_advisors'])): ?>
    <div class="profile-info-section">
        <h3>ครูที่ปรึกษา</h3>
        
        <?php foreach ($_SESSION['student_advisors'] as $advisor): ?>
        <div class="info-item">
            <div class="info-label"><?php echo $advisor['is_primary'] ? 'ที่ปรึกษาหลัก:' : 'ที่ปรึกษาร่วม:'; ?></div>
            <div class="info-value"><?php echo htmlspecialchars($advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="checkbox-container">
            <input type="checkbox" id="privacy_policy" name="privacy_policy" required>
            <label for="privacy_policy" class="checkbox-label">
                ฉันยอมรับ<a href="#" class="text-link" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>และยินยอมให้จัดเก็บข้อมูลส่วนบุคคลของฉันเพื่อใช้ในระบบ
            </label>
        </div>
        
        <button type="submit" name="confirm_student_info" class="btn primary">
            ยืนยันข้อมูลถูกต้อง
            <span class="material-icons">check_circle</span>
        </button>
    </form>
</div>

<div class="contact-admin">
    หากข้อมูลไม่ถูกต้องหรือพบปัญหา กรุณาติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน
</div>

<script>
    function showPrivacyPolicy() {
        alert("นโยบายความเป็นส่วนตัว\n\nระบบน้องชูใจ AI ดูแลผู้เรียน มีการจัดเก็บข้อมูลส่วนบุคคลของนักเรียนเพื่อใช้ในการเช็คชื่อเข้าแถวและดูแลผู้เรียน ข้อมูลที่จัดเก็บจะถูกใช้เฉพาะในระบบนี้เท่านั้น และจะไม่เปิดเผยต่อบุคคลภายนอกโดยไม่ได้รับอนุญาต");
    }
</script>
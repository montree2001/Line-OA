<div class="card">
    <div class="card-title">ข้อมูลเพิ่มเติมและยอมรับข้อตกลง</div>
    
    <div class="profile-info-section">
        <h3>สรุปข้อมูลนักเรียน</h3>
        
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
    
    <?php if (isset($_SESSION['selected_class']) && !empty($_SESSION['selected_class'])): ?>
    <div class="profile-info-section">
        <h3>ข้อมูลชั้นเรียน</h3>
        
        <div class="info-item">
            <div class="info-label">ระดับชั้น:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_class']['level']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">แผนกวิชา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_class']['department_name']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">กลุ่ม:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_class']['group_number']); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['class_advisors']) && !empty($_SESSION['class_advisors'])): ?>
    <div class="profile-info-section">
        <h3>ครูที่ปรึกษา</h3>
        
        <?php foreach ($_SESSION['class_advisors'] as $advisor): ?>
        <div class="info-item">
            <div class="info-label"><?php echo $advisor['is_primary'] ? 'ที่ปรึกษาหลัก:' : 'ที่ปรึกษาร่วม:'; ?></div>
            <div class="info-value"><?php echo htmlspecialchars($advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (isset($_SESSION['selected_teacher']) && !empty($_SESSION['selected_teacher'])): ?>
    <div class="profile-info-section">
        <h3>ครูที่ปรึกษา</h3>
        
        <div class="info-item">
            <div class="info-label">ที่ปรึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_teacher']['title'] . $_SESSION['selected_teacher']['first_name'] . ' ' . $_SESSION['selected_teacher']['last_name']); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="input-container">
            <label class="input-label" for="emergency_contact">เบอร์ติดต่อฉุกเฉิน (ถ้ามี)</label>
            <input type="tel" id="emergency_contact" name="emergency_contact" class="input-field" placeholder="กรอกเบอร์ติดต่อฉุกเฉิน">
            <div class="help-text">ไม่จำเป็นต้องกรอก แต่แนะนำให้กรอกเพื่อใช้ในกรณีฉุกเฉิน</div>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
            <label for="gdpr_consent" class="checkbox-label">
                ฉันยอมรับ<a href="#" class="text-link" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>และยินยอมให้จัดเก็บข้อมูลส่วนบุคคลของฉันเพื่อใช้ในระบบ
            </label>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="terms_agreement" name="terms_agreement" required>
            <label for="terms_agreement" class="checkbox-label">
                ฉันยอมรับ<a href="#" class="text-link" onclick="showTerms()">ข้อกำหนดการใช้งาน</a>ของระบบและจะปฏิบัติตามกฎระเบียบในการเช็คชื่อเข้าแถว
            </label>
        </div>
        
        <button type="submit" name="submit_additional_info" class="btn primary">
            ลงทะเบียนเสร็จสิ้น
            <span class="material-icons">done_all</span>
        </button>
    </form>
</div>

<div class="contact-admin">
    หากพบปัญหาในการลงทะเบียน กรุณาติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน
</div>

<script>
    function showPrivacyPolicy() {
        alert("นโยบายความเป็นส่วนตัว\n\nระบบน้องชูใจ AI ดูแลผู้เรียน มีการจัดเก็บข้อมูลส่วนบุคคลของนักเรียนเพื่อใช้ในการเช็คชื่อเข้าแถวและดูแลผู้เรียน ข้อมูลที่จัดเก็บจะถูกใช้เฉพาะในระบบนี้เท่านั้น และจะไม่เปิดเผยต่อบุคคลภายนอกโดยไม่ได้รับอนุญาต");
    }
    
    function showTerms() {
        alert("ข้อกำหนดการใช้งาน\n\n1. นักเรียนต้องเช็คชื่อด้วยตนเองเท่านั้น ห้ามให้ผู้อื่นเช็คชื่อแทน\n2. การเช็คชื่อต้องทำในเวลาและสถานที่ที่กำหนดเท่านั้น\n3. การใช้ GPS ต้องอยู่ในพื้นที่ของโรงเรียนจริง ไม่ใช้โปรแกรมปลอมตำแหน่ง\n4. การฝ่าฝืนกฎจะถูกดำเนินการตามระเบียบวินัยของโรงเรียน");
    }
</script>
<div class="card">
    <h2 class="card-title">กรอกข้อมูลชั้นเรียน</h2>
    
    <p class="mb-20">
        กรุณากรอกข้อมูลชั้นเรียนของคุณ หากไม่แน่ใจ สามารถสอบถามจากครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน
    </p>
    
    <form method="post" action="register.php?step=55" id="manual-class-form">
        <input type="hidden" name="action" value="manual_class">
        
        <div class="input-container">
            <label for="level" class="input-label">ระดับชั้น <span class="text-danger">*</span></label>
            <select id="level" name="level" class="input-field" required>
                <option value="">เลือกระดับชั้น</option>
                <option value="ปวช.1">ปวช.1</option>
                <option value="ปวช.2">ปวช.2</option>
                <option value="ปวช.3">ปวช.3</option>
                <option value="ปวส.1">ปวส.1</option>
                <option value="ปวส.2">ปวส.2</option>
            </select>
        </div>
        
        <div class="input-container">
            <label for="department_id" class="input-label">แผนกวิชา <span class="text-danger">*</span></label>
            <select id="department_id" name="department_id" class="input-field" required>
                <option value="">เลือกแผนกวิชา</option>
                <?php
                try {
                    $dept_sql = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
                    $dept_stmt = $conn->prepare($dept_sql);
                    $dept_stmt->execute();
                    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($departments as $dept) {
                        echo '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
                    }
                } catch (PDOException $e) {
                    echo '<option value="">ไม่สามารถดึงข้อมูลแผนกวิชาได้</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="input-container">
            <label for="group_number" class="input-label">กลุ่มเรียน <span class="text-danger">*</span></label>
            <select id="group_number" name="group_number" class="input-field" required>
                <option value="">เลือกกลุ่มเรียน</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <div class="help-text">กลุ่มเรียนคือตัวเลขที่ต่อท้ายระดับชั้น เช่น ปวช.1/1 คือกลุ่ม 1</div>
        </div>
        
        <div class="text-center mt-30">
            <a href="register.php?step=4" class="btn secondary">
                <span class="material-icons">arrow_back</span> กลับ
            </a>
            <button type="submit" class="btn primary">
                ถัดไป <span class="material-icons">arrow_forward</span>
            </button>
        </div>
    </form>
</div>

<div class="skip-section">
    <p>ข้อมูลชั้นเรียนจะช่วยให้ระบบสามารถกำหนดครูที่ปรึกษาที่ถูกต้องและจัดหมวดหมู่นักเรียนได้อย่างเหมาะสม</p>
</div>

<script>
    // ตรวจสอบความถูกต้องของฟอร์ม
    document.getElementById('manual-class-form').addEventListener('submit', function(e) {
        const level = document.getElementById('level').value;
        const departmentId = document.getElementById('department_id').value;
        const groupNumber = document.getElementById('group_number').value;
        
        if (level === '' || departmentId === '' || groupNumber === '') {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }
    });
</script>
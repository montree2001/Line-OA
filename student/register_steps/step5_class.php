<div class="card">
    <h2 class="card-title">เลือกห้องเรียน</h2>
    
    <?php 
    // ตรวจสอบว่ามีครูที่เลือกหรือไม่
    $selected_teacher = $_SESSION['selected_teacher'] ?? null;
    $teacher_name = $_SESSION['selected_teacher_name'] ?? 'ไม่ระบุ';
    
    // ตรวจสอบว่ามีรายการห้องเรียนที่ครูดูแลหรือไม่
    $teacher_classes = $_SESSION['teacher_classes'] ?? [];
    ?>
    
    <div class="profile-info-section">
        <h3>ข้อมูลที่เลือก</h3>
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
            <div class="info-label">ครูที่ปรึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($teacher_name); ?></div>
        </div>
    </div>
    
    <?php if (!empty($teacher_classes)): ?>
    <form method="POST" action="register_process.php">
        <input type="hidden" name="step" value="5">
        
        <div class="input-container">
            <label class="input-label">เลือกห้องเรียนของคุณ</label>
            <select name="class_id" class="input-field" required>
                <option value="" disabled selected>เลือกห้องเรียน</option>
                <?php foreach ($teacher_classes as $class): ?>
                <option value="<?php echo $class['class_id']; ?>">
                    <?php echo htmlspecialchars($class['level'] . ' กลุ่ม ' . $class['group_number'] . ' - ' . $class['department_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn primary">
            ยืนยันห้องเรียน <span class="material-icons">check</span>
        </button>
    </form>
    <?php else: ?>
    <div class="no-results">
        <p>ไม่พบข้อมูลห้องเรียนที่ครูท่านนี้เป็นที่ปรึกษา</p>
        <p>กรุณาเลือกครูท่านอื่น หรือกรอกข้อมูลห้องเรียนด้วยตนเอง</p>
    </div>
    
    <div class="mt-20 text-center">
        <a href="register.php?step=4" class="btn secondary">
            <span class="material-icons">arrow_back</span> กลับไปเลือกครูที่ปรึกษา
        </a>
    </div>
    <?php endif; ?>
    
    <div class="skip-section">
        <p>ต้องการกรอกข้อมูลห้องเรียนด้วยตนเอง?</p>
        <a href="register.php?step=55" class="btn secondary">
            กรอกข้อมูลห้องเรียนเอง <span class="material-icons">create</span>
        </a>
    </div>
</div>
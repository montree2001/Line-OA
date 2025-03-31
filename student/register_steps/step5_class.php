<div class="card">
    <div class="card-title">เลือกชั้นเรียน</div>
    
    <?php if (isset($_SESSION['selected_teacher']) && !empty($_SESSION['selected_teacher'])): ?>
    <div class="profile-info-section">
        <h3>ครูที่ปรึกษา</h3>
        <div class="info-item">
            <div class="info-label">ชื่อ-สกุล:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_teacher']['title'] . $_SESSION['selected_teacher']['first_name'] . ' ' . $_SESSION['selected_teacher']['last_name']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">แผนกวิชา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_teacher']['department_name'] ?? 'ไม่ระบุแผนกวิชา'); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">ตำแหน่ง:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['selected_teacher']['position'] ?? 'ไม่ระบุตำแหน่ง'); ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['teacher_classes']) && !empty($_SESSION['teacher_classes'])): ?>
    <p class="mb-20">
        คุณได้เลือกครูที่ปรึกษาเรียบร้อยแล้ว กรุณาเลือกชั้นเรียนที่คุณกำลังศึกษาอยู่จากรายการด้านล่าง
    </p>
    
    <form method="post" action="">
        <div class="teacher-list">
            <h3 class="mb-10">ชั้นเรียนที่ครูเป็นที่ปรึกษา</h3>
            
            <?php foreach ($_SESSION['teacher_classes'] as $class): ?>
            <div class="teacher-card">
                <div class="radio-container">
                    <input type="radio" id="class_<?php echo $class['class_id']; ?>" name="class_id" value="<?php echo $class['class_id']; ?>" required>
                    <label for="class_<?php echo $class['class_id']; ?>">
                        <div class="teacher-name">
                            <?php echo htmlspecialchars($class['level'] . ' ' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']); ?>
                            <?php if ($class['is_primary']): ?>
                                <span style="color: #06c755; margin-left: 5px;">(ที่ปรึกษาหลัก)</span>
                            <?php endif; ?>
                        </div>
                        <div class="teacher-department">
                            นักเรียนในชั้นเรียน: <?php echo htmlspecialchars($class['student_count']); ?> คน
                        </div>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" name="select_class" class="btn primary mt-20">
                เลือกชั้นเรียนนี้
                <span class="material-icons">check</span>
            </button>
        </div>
    </form>
    
    <?php else: ?>
    <div class="alert alert-error">
        <span class="material-icons">error</span>
        <span>ไม่พบข้อมูลชั้นเรียนที่ครูเป็นที่ปรึกษา</span>
    </div>
    
    <div class="text-center mt-20">
        <p>ไม่พบชั้นเรียนที่ครูเป็นที่ปรึกษาในปีการศึกษาปัจจุบัน กรุณาเลือกชั้นเรียนโดยตรงหรือเลือกครูที่ปรึกษาคนอื่น</p>
        <a href="register.php?step=55" class="btn secondary mt-10">ระบุชั้นเรียนโดยตรง</a>
        <a href="register.php?step=4" class="btn secondary mt-10">ค้นหาครูที่ปรึกษาอีกครั้ง</a>
    </div>
    <?php endif; ?>
</div>

<div class="contact-admin">
    หากพบปัญหาในการเลือกชั้นเรียน กรุณาติดต่อเจ้าหน้าที่ทะเบียน
</div>
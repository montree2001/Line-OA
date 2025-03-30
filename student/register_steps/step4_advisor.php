<div class="card">
    <h2 class="card-title">ค้นหาครูที่ปรึกษา</h2>
    
    <?php if (isset($_SESSION['student_first_name'])): ?>
    <div class="profile-info-section">
        <h3>ข้อมูลนักศึกษา</h3>
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
    </div>
    <?php endif; ?>
    
    <p>กรุณาค้นหาครูที่ปรึกษาของคุณ โดยกรอกชื่อครูที่ปรึกษา</p>
    
    <form method="POST" action="register_process.php" class="mb-20">
        <input type="hidden" name="step" value="4">
        <input type="hidden" name="search_teacher" value="1">
        
        <div class="input-container">
            <label for="teacher_name" class="input-label">ชื่อครูที่ปรึกษา</label>
            <input type="text" id="teacher_name" name="teacher_name" class="input-field" placeholder="กรอกชื่อหรือนามสกุลของครูที่ปรึกษา" required>
            <div class="help-text">เช่น อาจารย์วันดี, สมศักดิ์ เป็นต้น</div>
        </div>
        
        <button type="submit" class="btn primary">
            ค้นหาครูที่ปรึกษา <span class="material-icons">search</span>
        </button>
    </form>
    
    <?php if (isset($_SESSION['search_teacher_results']) && !empty($_SESSION['search_teacher_results'])): ?>
    <!-- แสดงผลการค้นหาครูที่ปรึกษา -->
    <div class="teacher-list">
        <h3>ผลการค้นหา</h3>
        
        <form method="POST" action="register_process.php">
            <input type="hidden" name="step" value="4">
            <input type="hidden" name="select_teacher" value="1">
            
            <?php foreach ($_SESSION['search_teacher_results'] as $teacher): ?>
            <div class="teacher-card">
                <div class="radio-container">
                    <input type="radio" id="teacher_<?php echo $teacher['teacher_id']; ?>" name="selected_teacher" value="<?php echo $teacher['teacher_id']; ?>" required>
                    <div>
                        <label for="teacher_<?php echo $teacher['teacher_id']; ?>" class="teacher-name">
                            <?php 
                                echo htmlspecialchars($teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']); 
                            ?>
                        </label>
                        <div class="teacher-department">
                            <?php echo htmlspecialchars($teacher['department_name'] ?? 'ไม่ระบุแผนก'); ?>
                            <?php if (!empty($teacher['classes'])): ?>
                                <div class="teacher-classes">
                                    <small>ดูแลห้อง: <?php echo htmlspecialchars($teacher['classes']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn primary mt-20">
                เลือกครูที่ปรึกษานี้ <span class="material-icons">check</span>
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="skip-section">
        <p>ไม่พบครูที่ปรึกษาของคุณ?</p>
        <a href="register.php?step=55" class="btn secondary">
            ข้ามไปกรอกข้อมูลห้องเรียนเอง <span class="material-icons">arrow_forward</span>
        </a>
    </div>
</div>
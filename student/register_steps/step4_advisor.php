<div class="card">
    <h2 class="card-title">ค้นหาครูที่ปรึกษา</h2>
    
    <p class="mb-20">
        ค้นหาครูที่ปรึกษาของคุณโดยพิมพ์ชื่อหรือนามสกุล ระบบจะแสดงรายชื่อครูที่ตรงกับคำค้นหา
    </p>
    
    <form method="post" action="register.php?step=4" id="search-teacher-form">
        <input type="hidden" name="action" value="search_teacher">
        
        <div class="input-container">
            <label for="search_term" class="input-label">ค้นหาครูที่ปรึกษา</label>
            <input type="text" id="search_term" name="search_term" class="input-field" 
                   placeholder="พิมพ์ชื่อหรือนามสกุลของครูที่ปรึกษา" 
                   value="<?php echo isset($_SESSION['search_teacher_term']) ? htmlspecialchars($_SESSION['search_teacher_term']) : ''; ?>" 
                   required>
        </div>
        
        <div class="text-center mt-20">
            <button type="submit" class="btn primary">
                <span class="material-icons">search</span> ค้นหา
            </button>
        </div>
    </form>
    
    <?php if (isset($_SESSION['search_teacher_results']) && !empty($_SESSION['search_teacher_results'])): ?>
        <div class="mt-30">
            <h3 class="mb-10">ผลการค้นหา</h3>
            
            <form method="post" action="register.php?step=4" id="select-teacher-form">
                <input type="hidden" name="action" value="select_teacher">
                
                <div class="teacher-list">
                    <?php foreach ($_SESSION['search_teacher_results'] as $index => $teacher): ?>
                        <div class="teacher-card">
                            <label class="radio-container">
                                <input type="radio" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>" required
                                       <?php echo $index === 0 ? 'checked' : ''; ?>>
                                <div class="teacher-info">
                                    <div class="teacher-name">
                                        <?php echo htmlspecialchars($teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                    </div>
                                    <div class="teacher-department">
                                        <?php echo htmlspecialchars('แผนก' . $teacher['department_name'] . ' (' . $teacher['position'] . ')'); ?>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-20">
                    <button type="submit" class="btn primary">
                        เลือกครูที่ปรึกษา <span class="material-icons">arrow_forward</span>
                    </button>
                </div>
            </form>
        </div>
    <?php elseif (isset($_SESSION['search_teacher_term'])): ?>
        <div class="no-results mt-20">
            <p>ไม่พบข้อมูลครูที่ตรงกับคำค้นหา "<?php echo htmlspecialchars($_SESSION['search_teacher_term']); ?>"</p>
            <p>กรุณาลองค้นหาด้วยชื่อหรือนามสกุลอื่น หรือติดต่อเจ้าหน้าที่</p>
        </div>
    <?php endif; ?>
</div>

<div class="skip-section">
    <p>
        ไม่พบครูที่ปรึกษาของคุณ? 
        <a href="register.php?step=55" class="text-link">ข้ามไปยังการกรอกข้อมูลชั้นเรียนเอง</a>
    </p>
</div>

<div class="text-center mt-20">
    <a href="register.php?step=<?php echo isset($_SESSION['student_id']) ? '3' : '33'; ?>" class="btn secondary">
        <span class="material-icons">arrow_back</span> กลับ
    </a>
</div>
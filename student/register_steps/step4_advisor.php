<?php
// ดึงข้อมูลแผนกวิชาทั้งหมด
try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
}
?>

<div class="card">
    <div class="card-title">ค้นหาครูที่ปรึกษา</div>
    
    <p class="mb-20">
        กรุณาค้นหาครูที่ปรึกษาของคุณโดยใช้ชื่อหรือนามสกุล เพื่อเลือกชั้นเรียนที่ครูเป็นที่ปรึกษา
    </p>
    
    <form method="post" action="">
        <div class="input-container">
            <label class="input-label" for="teacher_name">ชื่อหรือนามสกุลครูที่ปรึกษา</label>
            <input type="text" id="teacher_name" name="teacher_name" class="input-field" placeholder="กรอกชื่อหรือนามสกุลครูที่ปรึกษา" value="<?php echo isset($_POST['teacher_name']) ? htmlspecialchars($_POST['teacher_name']) : ''; ?>" required>
            <div class="help-text">เช่น มานิต, สมใจ</div>
        </div>
        
        <button type="submit" name="search_teacher" class="btn primary">
            ค้นหาครูที่ปรึกษา
            <span class="material-icons">search</span>
        </button>
    </form>
    
    <?php if (isset($_SESSION['search_teacher_results']) && !empty($_SESSION['search_teacher_results'])): ?>
    <div class="teacher-list mt-20">
        <h3 class="mb-10">ผลการค้นหาครูที่ปรึกษา</h3>
        
        <form method="post" action="">
            <?php foreach ($_SESSION['search_teacher_results'] as $teacher): ?>
            <div class="teacher-card">
                <div class="radio-container">
                    <input type="radio" id="teacher_<?php echo $teacher['teacher_id']; ?>" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>" required>
                    <label for="teacher_<?php echo $teacher['teacher_id']; ?>">
                        <div class="teacher-name"><?php echo htmlspecialchars($teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name']); ?></div>
                        <div class="teacher-department">
                            <?php echo !empty($teacher['department_name']) ? htmlspecialchars($teacher['department_name']) : 'ไม่ระบุแผนกวิชา'; ?>
                            <?php echo !empty($teacher['position']) ? ' | ' . htmlspecialchars($teacher['position']) : ''; ?>
                        </div>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" name="select_teacher" class="btn primary mt-20">
                เลือกครูที่ปรึกษานี้
                <span class="material-icons">check</span>
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- <div class="text-center mt-30">
        <p>หากไม่พบครูที่ปรึกษาของคุณ หรือไม่ทราบว่าใครเป็นครูที่ปรึกษา คุณสามารถระบุชั้นเรียนโดยตรงได้</p>
        <a href="register.php?step=55" class="btn secondary mt-10">ระบุชั้นเรียนโดยตรง</a>
    </div> -->
</div>

<div class="contact-admin">
    หากพบปัญหาในการค้นหาครูที่ปรึกษา กรุณาติดต่อเจ้าหน้าที่ทะเบียน
</div>
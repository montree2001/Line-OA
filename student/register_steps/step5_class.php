<?php
// ดึงข้อมูลครูที่ปรึกษาที่เลือก
$teacher_id = $_SESSION['teacher_id'] ?? 0;
$teacher_name = "";

try {
    if ($teacher_id > 0) {
        $teacher_sql = "SELECT t.title, t.first_name, t.last_name FROM teachers t WHERE t.teacher_id = :teacher_id";
        $teacher_stmt = $conn->prepare($teacher_sql);
        $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $teacher_stmt->execute();
        $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $teacher_name = $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name'];
        }
    }
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูลครูที่ปรึกษา: " . $e->getMessage();
}
?>

<div class="card">
    <h2 class="card-title">เลือกชั้นเรียน</h2>
    
    <div class="profile-info-section mb-20">
        <h3>ครูที่ปรึกษาที่เลือก</h3>
        <div class="info-item">
            <div class="info-label">ชื่อ-นามสกุล:</div>
            <div class="info-value"><?php echo htmlspecialchars($teacher_name); ?></div>
        </div>
    </div>
    
    <p class="mb-20">
        กรุณาเลือกชั้นเรียนของคุณจากรายการด้านล่าง ซึ่งเป็นชั้นเรียนที่ครูที่ปรึกษาของคุณดูแลอยู่
    </p>
    
    <?php if (isset($_SESSION['teacher_classes']) && !empty($_SESSION['teacher_classes'])): ?>
        <form method="post" action="register.php?step=5" id="select-class-form">
            <input type="hidden" name="action" value="select_class">
            
            <div class="teacher-list">
                <?php foreach ($_SESSION['teacher_classes'] as $index => $class): ?>
                    <div class="teacher-card">
                        <label class="radio-container">
                            <input type="radio" name="class_id" value="<?php echo $class['class_id']; ?>" required
                                   <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <div class="teacher-info">
                                <div class="teacher-name">
                                    <?php echo htmlspecialchars($class['level'] . ' แผนก' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']); ?>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
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
    <?php else: ?>
        <div class="no-results">
            <p>ไม่พบข้อมูลชั้นเรียนที่ครูที่ปรึกษาดูแล</p>
            <div class="text-center mt-20">
                <a href="register.php?step=55" class="btn primary">
                    กรอกข้อมูลชั้นเรียนเอง <span class="material-icons">arrow_forward</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
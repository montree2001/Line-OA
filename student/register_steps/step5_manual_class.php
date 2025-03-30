<?php
// ดึงข้อมูลแผนกวิชาและระดับชั้นเรียนจากฐานข้อมูล
$departments = [];
$levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];

try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // หากมีข้อผิดพลาดในการดึงข้อมูล ให้บันทึกข้อผิดพลาด
    error_log("Error fetching departments: " . $e->getMessage());
}
?>

<div class="card">
    <h2 class="card-title">กรอกข้อมูลห้องเรียน</h2>
    
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
    
    <p>กรุณากรอกข้อมูลห้องเรียนของคุณ</p>
    
    <form method="POST" action="register_process.php">
        <input type="hidden" name="step" value="55">
        
        <div class="input-container">
            <label class="input-label">ระดับชั้น</label>
            <select name="level" class="input-field" required>
                <option value="" disabled selected>เลือกระดับชั้น</option>
                <?php foreach ($levels as $level): ?>
                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="input-container">
            <label class="input-label">แผนกวิชา</label>
            <select name="department_id" class="input-field" required>
                <option value="" disabled selected>เลือกแผนกวิชา</option>
                <?php foreach ($departments as $department): ?>
                <option value="<?php echo $department['department_id']; ?>">
                    <?php echo htmlspecialchars($department['department_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="input-container">
            <label for="group_number" class="input-label">กลุ่มเรียน</label>
            <select name="group_number" class="input-field" required>
                <option value="" disabled selected>เลือกกลุ่มเรียน</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="input-container">
            <label for="advisor_name" class="input-label">ชื่อครูที่ปรึกษา (ถ้าทราบ)</label>
            <input type="text" id="advisor_name" name="advisor_name" class="input-field" placeholder="กรอกชื่อครูที่ปรึกษา (ถ้าทราบ)">
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" id="confirm_class" name="confirm_class" required>
            <label for="confirm_class" class="checkbox-label">
                ข้าพเจ้ายืนยันว่าข้อมูลห้องเรียนที่กรอกเป็นความจริง
            </label>
        </div>
        
        <button type="submit" class="btn primary">
            บันทึกข้อมูลห้องเรียน <span class="material-icons">arrow_forward</span>
        </button>
    </form>
    
    <div class="mt-20 text-center">
        <a href="register.php?step=4" class="home-button">
            <span class="material-icons">arrow_back</span> กลับไปค้นหาครูที่ปรึกษา
        </a>
    </div>
</div>
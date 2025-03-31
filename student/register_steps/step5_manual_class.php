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
    <div class="card-title">ระบุชั้นเรียน</div>
    
    <p class="mb-20">
        กรุณาเลือกข้อมูลชั้นเรียนที่คุณกำลังศึกษาอยู่
    </p>
    
    <form method="post" action="">
        <div class="input-container">
            <label class="input-label" for="level">ระดับชั้น</label>
            <select id="level" name="level" class="input-field" required>
                <option value="">กรุณาเลือกระดับชั้น</option>
                <option value="ปวช.1">ปวช.1</option>
                <option value="ปวช.2">ปวช.2</option>
                <option value="ปวช.3">ปวช.3</option>
                <option value="ปวส.1">ปวส.1</option>
                <option value="ปวส.2">ปวส.2</option>
            </select>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="department_id">แผนกวิชา</label>
            <select id="department_id" name="department_id" class="input-field" required>
                <option value="">กรุณาเลือกแผนกวิชา</option>
                <?php foreach ($departments as $department): ?>
                <option value="<?php echo $department['department_id']; ?>"><?php echo htmlspecialchars($department['department_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="input-container">
            <label class="input-label" for="group_number">กลุ่มเรียน</label>
            <select id="group_number" name="group_number" class="input-field" required>
                <option value="">กรุณาเลือกกลุ่มเรียน</option>
                <option value="1">กลุ่ม 1</option>
                <option value="2">กลุ่ม 2</option>
                <option value="3">กลุ่ม 3</option>
                <option value="4">กลุ่ม 4</option>
                <option value="5">กลุ่ม 5</option>
            </select>
        </div>
        
        <div class="alert alert-error">
            <span class="material-icons">info</span>
            <span>ข้อมูลชั้นเรียนจะต้องตรงกับชั้นเรียนจริงที่คุณศึกษาอยู่ หากระบุผิด อาจทำให้ไม่ได้รับการเช็คชื่อที่ถูกต้อง</span>
        </div>
        
        <button type="submit" name="submit_manual_class" class="btn primary mt-20">
            บันทึกข้อมูลชั้นเรียน
            <span class="material-icons">save</span>
        </button>
    </form>
</div>

<div class="contact-admin">
    หากไม่ทราบข้อมูลชั้นเรียนที่ถูกต้อง กรุณาสอบถามครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน
</div>
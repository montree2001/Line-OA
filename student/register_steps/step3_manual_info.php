<!-- ขั้นตอนกรอกข้อมูลนักศึกษาเอง -->
<div class="card">
    <div class="card-title">กรอกข้อมูลนักศึกษา</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=3manual">
            <div class="input-container">
                <label class="input-label">รหัสนักศึกษา</label>
                <input type="text" class="input-field" value="<?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?>" readonly>
            </div>

            <div class="input-container">
                <label class="input-label">คำนำหน้า</label>
                <select class="input-field" name="title" required>
                    <option value="" disabled selected>เลือกคำนำหน้า</option>
                    <option value="นาย">นาย</option>
                    <option value="นางสาว">นางสาว</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </div>

            <div class="input-container">
                <label class="input-label">ชื่อ</label>
                <input type="text" class="input-field" name="first_name" placeholder="กรอกชื่อ" required>
            </div>

            <div class="input-container">
                <label class="input-label">นามสกุล</label>
                <input type="text" class="input-field" name="last_name" placeholder="กรอกนามสกุล" required>
            </div>

            <div class="input-container">
                <label class="input-label">ระดับการศึกษา</label>
                <select class="input-field" name="level_system" id="level-system" required onchange="updateClassLevels()">
                    <option value="" disabled selected>เลือกระดับการศึกษา</option>
                    <option value="ปวช.">ปวช.</option>
                    <option value="ปวส.">ปวส.</option>
                </select>
            </div>

            <div class="input-container">
                <label class="input-label">ชั้นปี</label>
                <select class="input-field" name="class_level" id="class-level" required>
                    <option value="" disabled selected>เลือกชั้นปี</option>
                </select>
            </div>

            <button type="submit" class="btn primary">
                ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
            </button>
        </form>
    </div>
</div>

<script>
    function updateClassLevels() {
        const levelSystem = document.getElementById('level-system').value;
        const classLevelSelect = document.getElementById('class-level');

        // ล้างตัวเลือกเดิม
        classLevelSelect.innerHTML = '<option value="" disabled selected>เลือกชั้นปี</option>';

        if (levelSystem === 'ปวช.') {
            for (let i = 1; i <= 3; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                classLevelSelect.appendChild(option);
            }
        } else if (levelSystem === 'ปวส.') {
            for (let i = 1; i <= 2; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                classLevelSelect.appendChild(option);
            }
        }
    }
</script>
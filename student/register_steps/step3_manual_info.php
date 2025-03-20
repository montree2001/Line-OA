<!-- ขั้นตอนกรอกข้อมูลนักศึกษาเอง -->
<div class="card">
    <div class="card-title">กรอกข้อมูลนักศึกษา</div>
    
    <div class="card-message">
        <div class="alert alert-info">
            <span class="material-icons">info</span>
            <span>ไม่พบข้อมูลรหัสนักศึกษา <strong><?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?></strong> ในระบบ กรุณากรอกข้อมูลด้วยตนเอง</span>
        </div>
    </div>
    
    <div class="card-content">
        <form method="POST" action="register.php?step=3manual">
            <div class="section-divider">
                <span class="section-title">ข้อมูลส่วนตัว</span>
            </div>

            <div class="input-container">
                <label class="input-label">รหัสนักศึกษา</label>
                <input type="text" class="input-field" value="<?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?>" readonly>
                <div class="help-text">รหัสนักศึกษาที่คุณกรอกในขั้นตอนก่อนหน้า</div>
            </div>

            <div class="input-group">
                <div class="input-container title-select">
                    <label class="input-label">คำนำหน้า<span class="required-mark">*</span></label>
                    <select class="input-field" name="title" required>
                        <option value="" disabled selected>เลือกคำนำหน้า</option>
                        <option value="นาย">นาย</option>
                        <option value="นางสาว">นางสาว</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>

                <div class="input-container">
                    <label class="input-label">ชื่อ<span class="required-mark">*</span></label>
                    <input type="text" class="input-field" name="first_name" placeholder="กรอกชื่อจริง" required>
                </div>
            </div>

            <div class="input-container">
                <label class="input-label">นามสกุล<span class="required-mark">*</span></label>
                <input type="text" class="input-field" name="last_name" placeholder="กรอกนามสกุล" required>
            </div>

            <div class="section-divider">
                <span class="section-title">ข้อมูลการศึกษา</span>
            </div>

            <div class="input-group three-columns">
                <div class="input-container">
                    <label class="input-label">ระดับการศึกษา<span class="required-mark">*</span></label>
                    <select class="input-field" name="level_system" id="level-system" required onchange="updateClassLevels()">
                        <option value="" disabled selected>เลือกระดับการศึกษา</option>
                        <option value="ปวช.">ปวช.</option>
                        <option value="ปวส.">ปวส.</option>
                    </select>
                </div>

                <div class="input-container">
                    <label class="input-label">ชั้นปี<span class="required-mark">*</span></label>
                    <select class="input-field" name="class_level" id="class-level" required>
                        <option value="" disabled selected>เลือกชั้นปี</option>
                    </select>
                </div>
            </div>

            <div class="input-container">
                <label class="input-label">สาขาวิชา<span class="required-mark">*</span></label>
                <select class="input-field" name="department" required>
                    <option value="" disabled selected>เลือกสาขาวิชา</option>
                    <option value="ช่างยนต์">สาขาวิชาช่างยนต์</option>
                    <option value="ช่างกลโรงงาน">สาขาวิชาช่างกลโรงงาน</option>
                    <option value="ช่างไฟฟ้ากำลัง">สาขาวิชาช่างไฟฟ้ากำลัง</option>
                    <option value="ช่างอิเล็กทรอนิกส์">สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                    <option value="การบัญชี">สาขาวิชาการบัญชี</option>
                    <option value="เทคโนโลยีสารสนเทศ">สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                    <option value="การโรงแรม">สาขาวิชาการโรงแรม</option>
                    <option value="ช่างเชื่อมโลหะ">สาขาวิชาช่างเชื่อมโลหะ</option>
                </select>
            </div>

            <div class="input-container">
                <label class="input-label">กลุ่มเรียน<span class="required-mark">*</span></label>
                <select class="input-field" name="group_number" required>
                    <option value="" disabled selected>เลือกกลุ่มเรียน</option>
                    <option value="1">กลุ่ม 1</option>
                    <option value="2">กลุ่ม 2</option>
                    <option value="3">กลุ่ม 3</option>
                    <option value="4">กลุ่ม 4</option>
                    <option value="5">กลุ่ม 5</option>
                </select>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn secondary" onclick="window.location.href='register.php?step=2'">
                    <span class="material-icons">arrow_back</span> ย้อนกลับ
                </button>
                <button type="submit" class="btn primary">
                    ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .card-message {
        padding: 0 20px;
        margin-top: 15px;
    }
    
    .alert-info {
        background-color: #e3f2fd;
        color: #0d47a1;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .alert-info .material-icons {
        margin-right: 10px;
        color: #1976d2;
    }
    
    .section-divider {
        position: relative;
        text-align: left;
        margin: 25px 0 15px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .section-title {
        position: relative;
        top: 10px;
        background-color: white;
        padding-right: 10px;
        font-weight: 500;
        color: #1976d2;
        font-size: 0.9em;
    }
    
    .input-group {
        display: flex;
        gap: 15px;
        margin-bottom: 5px;
    }
    
    .three-columns {
        justify-content: space-between;
    }
    
    .title-select {
        flex: 0 0 35%;
    }
    
    .required-mark {
        color: #f44336;
        margin-left: 3px;
    }
    
    .action-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }
    
    @media (max-width: 600px) {
        .input-group {
            flex-direction: column;
            gap: 0;
        }
        
        .title-select {
            flex: 1;
        }
    }
</style>

<script>
    // สคริปต์อัพเดตชั้นปีตามระดับการศึกษา
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
    
    // เรียกฟังก์ชันเมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
        // ถ้ามีการเลือกระดับการศึกษาไว้แล้ว ให้อัพเดตชั้นปี
        const levelSystem = document.getElementById('level-system');
        if (levelSystem.value) {
            updateClassLevels();
        }
    });
</script>
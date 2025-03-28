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
        <form method="POST" action="register.php?step=33">
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
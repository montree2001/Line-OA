<!-- ขั้นตอนค้นหารหัสนักศึกษา -->
<div class="card">
    <div class="card-title">กรอกรหัสนักศึกษา</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=2" id="student-code-form">
            <div class="input-container">
                <label class="input-label">รหัสนักศึกษา (11 หลัก)</label>
                <input type="text" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" maxlength="11" name="student_code" pattern="[0-9]{11}" inputmode="numeric" required>
                <div class="help-text">กรุณากรอกเฉพาะตัวเลข 11 หลัก</div>
            </div>

            <button type="submit" class="btn primary">
                <span class="material-icons">search</span> ค้นหาข้อมูล
            </button>
        </form>

        <?php if (isset($_SESSION['search_attempted']) && $_SESSION['search_attempted']): ?>
        <div class="search-result not-found">
            <div class="alert alert-warning">
                <span class="material-icons">info</span>
                <span>ไม่พบข้อมูลรหัสนักศึกษา <?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?> ในระบบ</span>
            </div>
            <div class="manual-entry-section">
                <p>คุณสามารถกรอกข้อมูลด้วยตนเองได้</p>
                <form method="POST" action="register.php">
                    <input type="hidden" name="manual_entry" value="1">
                    <input type="hidden" name="student_code" value="<?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?>">
                    <button type="submit" class="btn secondary">
                        <span class="material-icons">edit</span> กรอกข้อมูลด้วยตนเอง
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="contact-admin">
            หากมีปัญหาในการค้นหาข้อมูล กรุณา<a href="#">ติดต่อเจ้าหน้าที่</a>
        </div>
    </div>
</div>

<style>
    .search-result {
        margin-top: 20px;
        padding: 15px;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    
    .search-result.not-found {
        border-left: 4px solid #ff9800;
    }
    
    .manual-entry-section {
        margin-top: 15px;
        text-align: center;
    }
    
    .alert-warning {
        background-color: #fff3e0;
        color: #e65100;
        padding: 10px;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    
    .alert-warning .material-icons {
        margin-right: 8px;
    }
</style>
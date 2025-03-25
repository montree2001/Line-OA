<!-- ขั้นตอนยืนยันข้อมูลนักศึกษา -->
<div class="card">
    <div class="card-title">ยืนยันข้อมูลนักศึกษา</div>
    <div class="card-content">
        <div class="profile-info-section">
            <h3>ข้อมูลนักศึกษา</h3>
            <div class="info-item">
                <div class="info-label">รหัสนักศึกษา:</div>
                <div class="info-value"><?php echo $_SESSION['student_code']; ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">ชื่อ-นามสกุล:</div>
                <div class="info-value"><?php echo $_SESSION['student_title'] . ' ' . $_SESSION['student_first_name'] . ' ' . $_SESSION['student_last_name']; ?></div>
            </div>

        </div>

        <form method="POST" action="register.php?step=4">
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn primary">
                ยืนยันข้อมูล <span class="material-icons">check</span>
            </button>
        </form>

    </div>
</div>
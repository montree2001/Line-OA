<!-- แสดงข้อผิดพลาด -->
<div class="card error-card">
    <div class="error-icon">
        <span class="material-icons">error</span>
    </div>
    <div class="error-message">
        <h2>เกิดข้อผิดพลาด</h2>
        <p><?php echo $error_message; ?></p>
    </div>
    <button class="btn secondary" onclick="window.location.href='../index.php'">
        กลับไปหน้าหลัก
    </button>
</div>
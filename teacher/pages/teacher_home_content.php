<!-- ส่วนหัว -->
<div class="header">
    <div class="app-name">Teacher-Prasat</div>
    <div class="header-icons">
        <span class="material-icons">notifications</span>
        <span class="material-icons">account_circle</span>
    </div>
</div>

<div class="container">
    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-card">
        <h2><?php echo $class_info['name']; ?></h2>
        <p>นักเรียน <?php echo $class_info['total_students']; ?> คน</p>
        <p>วันที่ <?php echo date('j M Y', strtotime('today')); ?></p>
    </div>

    <!-- สรุปการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card green">
            <h3><?php echo $class_info['present_today']; ?></h3>
            <p>มาเรียน</p>
        </div>
        <div class="stat-card red">
            <h3><?php echo $class_info['absent_today']; ?></h3>
            <p>ขาดเรียน</p>
        </div>
    </div>

    <!-- ปุ่มสร้าง PIN -->
    <button class="create-pin-button" onclick="generatePin()">
        <span class="material-icons">pin</span> สร้างรหัส PIN
    </button>

    <!-- แสดง PIN ที่ใช้งานอยู่ -->
    <div class="active-pin-card">
        <h3>รหัส PIN ที่ใช้งานได้</h3>
        <div class="active-pin" id="active-pin-code"><?php echo $active_pin['code']; ?></div>
        <div class="pin-expire">หมดอายุในอีก <span id="pin-expire-time"><?php echo $active_pin['expire_in_minutes']; ?></span> นาที</div>
    </div>

    <!-- ปุ่มสแกน QR -->
    <button class="scan-qr-button" onclick="scanQRCode()">
        <span class="material-icons">qr_code_scanner</span> สแกน QR นักเรียน
    </button>

    <!-- รายชื่อนักเรียน -->
    <div class="student-list">
        <div class="student-list-header">
            <span>รายชื่อนักเรียน</span>
            <span>สถานะ</span>
        </div>
        <?php foreach ($students_summary as $student): ?>
        <div class="student-item">
            <div class="student-name"><?php echo $student['number']; ?>. <?php echo $student['name']; ?></div>
            <div class="student-status <?php echo $student['status']; ?>">
                <?php echo $student['status'] === 'present' ? 'มา' : 'ขาด'; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="student-list-footer">
            <a href="check-attendance.php" class="view-all-btn">
                <span class="material-icons">people</span> ดูทั้งหมด
            </a>
        </div>
    </div>

    <!-- ปุ่มเช็คชื่อ -->
    <a href="check-attendance.php" class="check-attendance-button">
        <span class="material-icons">how_to_reg</span> เช็คชื่อนักเรียน
    </a>

    <!-- ปุ่มรายงาน -->
    <a href="reports.php" class="reports-button">
        <span class="material-icons">assessment</span> รายงานการเข้าแถว
    </a>
</div>

<!-- โมดัลสร้างรหัส PIN -->
<div class="modal" id="pinModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">สร้างรหัส PIN สำหรับการเช็คชื่อ</h2>
        <div class="pin-display" id="pinCode">5731</div>
        <div class="pin-info">
            รหัส PIN นี้สำหรับให้นักเรียนเช็คชื่อวันนี้<br>
            เท่านั้น และจะหมดอายุภายในเวลาที่กำหนด
        </div>
        <div class="timer">
            <span class="material-icons">timer</span>
            <span>หมดอายุใน 10:00 นาที</span>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal()">ปิด</button>
            <button class="btn btn-primary" onclick="generateNewPin()">สร้างรหัสใหม่</button>
        </div>
    </div>
</div>
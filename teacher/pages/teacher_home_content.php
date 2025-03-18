<!-- ส่วนหัว -->
<div class="header">
    <div class="app-name">Teacher-Prasat</div>
    <div class="header-icons">
        <span class="material-icons" id="notification-icon">notifications</span>
        <span class="material-icons" id="account-icon">account_circle</span>
    </div>
</div>

<div class="container">
    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-card">
        <div class="class-details">
            <h2><?php echo $class_info['name']; ?></h2>
            <p>นักเรียนทั้งหมด <?php echo $class_info['total_students']; ?> คน</p>
            <p>วันที่ <?php echo date('j M Y', strtotime('today')); ?></p>
        </div>
        <div class="date-info">
            <p><?php echo date('l', strtotime('today')); ?></p>
            <p><?php echo date('H:i'); ?> น.</p>
        </div>
    </div>

    <!-- สรุปการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card green">
            <div class="stat-value"><?php echo $class_info['present_today']; ?></div>
            <div class="stat-label">มาเรียน</div>
        </div>
        <div class="stat-card red">
            <div class="stat-value"><?php echo $class_info['absent_today']; ?></div>
            <div class="stat-label">ขาดเรียน</div>
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

    <!-- เมนูลัด -->
    <div class="quick-menu">
        <div class="section-title">เมนูลัด</div>
        <div class="quick-menu-grid">
            <a href="notification.php" class="quick-menu-item">
                <span class="material-icons">notifications_active</span>
                <span class="quick-menu-text">แจ้งเตือน</span>
            </a>
            <a href="calendar.php" class="quick-menu-item">
                <span class="material-icons">calendar_today</span>
                <span class="quick-menu-text">ปฏิทิน</span>
            </a>
            <a href="student-list.php" class="quick-menu-item">
                <span class="material-icons">assignment_ind</span>
                <span class="quick-menu-text">รายชื่อนักเรียน</span>
            </a>
            <a href="contact-parents.php" class="quick-menu-item">
                <span class="material-icons">contact_phone</span>
                <span class="quick-menu-text">ติดต่อผู้ปกครอง</span>
            </a>
        </div>
    </div>
</div>

<!-- โมดัลสร้างรหัส PIN -->
<div class="modal" id="pinModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">
            <span class="material-icons">close</span>
        </button>
        <div class="modal-title">
            <span class="material-icons">pin</span>
            สร้างรหัส PIN สำหรับการเช็คชื่อ
        </div>
        <div class="pin-display" id="pinCode"><?php echo $active_pin['code']; ?></div>
        <div class="pin-info">
            รหัส PIN นี้สำหรับให้นักเรียนเช็คชื่อวันนี้<br>
            เท่านั้น และจะหมดอายุภายในเวลาที่กำหนด
        </div>
        <div class="timer">
            <span class="material-icons">timer</span>
            <span>หมดอายุใน 10:00 นาที</span>
        </div>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal()">ปิด</button>
            <button class="modal-button confirm" onclick="generateNewPin()">สร้างรหัสใหม่</button>
        </div>
    </div>
</div>

<!-- โมดัลแจ้งเตือน -->
<div class="modal" id="notificationModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('notificationModal')">
            <span class="material-icons">close</span>
        </button>
        <div class="modal-title">
            <span class="material-icons">notifications</span>
            การแจ้งเตือน
        </div>
        <div class="notification-list">
            <div class="notification-item">
                <div class="notification-icon">
                    <span class="material-icons">person</span>
                </div>
                <div class="notification-content">
                    <div class="notification-title">นักเรียนขาดแถว 2 คน</div>
                    <div class="notification-time">เมื่อ 10 นาทีที่แล้ว</div>
                </div>
            </div>
            <div class="notification-item">
                <div class="notification-icon">
                    <span class="material-icons">event</span>
                </div>
                <div class="notification-content">
                    <div class="notification-title">วันนี้มีกิจกรรมหน้าเสาธง</div>
                    <div class="notification-time">เมื่อ 1 ชั่วโมงที่แล้ว</div>
                </div>
            </div>
            <div class="notification-item">
                <div class="notification-icon">
                    <span class="material-icons">message</span>
                </div>
                <div class="notification-content">
                    <div class="notification-title">ผู้ปกครองส่งข้อความถึงคุณ</div>
                    <div class="notification-time">เมื่อ 2 ชั่วโมงที่แล้ว</div>
                </div>
            </div>
        </div>
        <div class="modal-buttons">
            <button class="modal-button confirm" onclick="viewAllNotifications()">ดูทั้งหมด</button>
        </div>
    </div>
</div>

<!-- โมดัลการแจ้งเตือนผู้ปกครอง -->
<div class="modal" id="alertParentsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('alertParentsModal')">
            <span class="material-icons">close</span>
        </button>
        <div class="modal-title">
            <span class="material-icons">campaign</span>
            แจ้งเตือนผู้ปกครอง
        </div>
        <div class="alert-parents-form">
            <div class="form-group">
                <label class="input-label">เลือกผู้รับ</label>
                <select class="input-field">
                    <option value="all">ผู้ปกครองทั้งหมด</option>
                    <option value="absent">เฉพาะผู้ปกครองของนักเรียนที่ขาดแถว</option>
                    <option value="risk">เฉพาะผู้ปกครองของนักเรียนที่มีความเสี่ยง</option>
                </select>
            </div>
            <div class="form-group">
                <label class="input-label">ข้อความ</label>
                <textarea class="input-field" rows="4" placeholder="กรอกข้อความที่ต้องการแจ้งผู้ปกครอง..."></textarea>
            </div>
        </div>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('alertParentsModal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="sendParentNotification()">ส่งข้อความ</button>
        </div>
    </div>
</div>
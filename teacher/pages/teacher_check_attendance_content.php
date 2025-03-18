<div class="header">
    <a href="#" onclick="goBack()" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>เช็คชื่อนักเรียน</h1>
    <div class="header-icon" onclick="toggleOptions()">
        <span class="material-icons">more_vert</span>
    </div>
</div>

<div class="container">
    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-info">
        <div class="class-details">
            <h2><?php echo $class_info['name']; ?></h2>
            <p>นักเรียนทั้งหมด <?php echo $class_info['total_students']; ?> คน</p>
        </div>
        <div class="date-info">
            <p>วันที่ <?php echo date('j F Y'); ?></p>
            <p>เวลา <?php echo date('H:i'); ?> น.</p>
        </div>
    </div>

    <!-- สถิติการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="value"><?php echo $class_info['total_students']; ?></div>
            <div class="label">ทั้งหมด</div>
        </div>
        <div class="stat-card present">
            <div class="value" id="present-count"><?php echo $class_info['present_count']; ?></div>
            <div class="label">มาเรียน</div>
        </div>
        <div class="stat-card absent">
            <div class="value" id="absent-count"><?php echo $class_info['absent_count']; ?></div>
            <div class="label">ขาดเรียน</div>
        </div>
    </div>

    <!-- ปุ่มควบคุม -->
    <div class="control-buttons">
        <button class="control-button blue" onclick="showPinModal()">
            <span class="material-icons">pin</span> สร้าง PIN
        </button>
        <button class="control-button orange" onclick="scanQRCode()">
            <span class="material-icons">qr_code_scanner</span> สแกน QR
        </button>
        <button class="control-button green" onclick="showMarkAllModal()">
            <span class="material-icons">done_all</span> เช็คชื่อทั้งหมด
        </button>
    </div>

    <!-- ส่วนค้นหา -->
    <div class="search-bar">
        <span class="material-icons search-icon">search</span>
        <input type="text" class="search-input" id="search-input" placeholder="ค้นหาชื่อนักเรียน..." oninput="searchStudents()">
    </div>

    <!-- รายชื่อนักเรียน -->
    <div class="student-list" id="student-list">
        <div class="list-header">
            <div>เลขที่</div>
            <div>ชื่อ-นามสกุล</div>
            <div>การเข้าแถว</div>
        </div>
        
        <?php foreach ($students as $student): ?>
        <div class="student-item" data-name="<?php echo $student['name']; ?>">
            <div class="student-number"><?php echo $student['number']; ?></div>
            <div class="student-name"><?php echo $student['name']; ?></div>
            <div class="attendance-actions">
                <button class="action-button present <?php echo $student['status'] === 'present' ? 'active' : ''; ?>" 
                        onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)">
                    <span class="material-icons">check</span>
                </button>
                <button class="action-button absent <?php echo $student['status'] === 'absent' ? 'active' : ''; ?>" 
                        onclick="markAttendance(this, 'absent', <?php echo $student['id']; ?>)">
                    <span class="material-icons">close</span>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- ปุ่มลอย -->
    <button class="floating-button" onclick="saveAttendance()">
        <span class="material-icons">save</span>
    </button>
</div>

<!-- Modal สร้าง PIN -->
<div class="modal" id="pin-modal">
    <div class="modal-content">
        <div class="modal-title">สร้างรหัส PIN สำหรับการเช็คชื่อ</div>
        <div class="pin-code">5731</div>
        <p class="pin-info">รหัส PIN นี้จะหมดอายุใน 10 นาที</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('pin-modal')">ปิด</button>
            <button class="modal-button confirm" onclick="generateNewPin()">สร้างใหม่</button>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อทั้งหมด -->
<div class="modal" id="mark-all-modal">
    <div class="modal-content">
        <div class="modal-title">เช็คชื่อนักเรียนทั้งหมด</div>
        <p>คุณต้องการเช็คชื่อให้นักเรียนทั้งหมด "มาเรียน" ใช่หรือไม่?</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('mark-all-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="markAllPresent()">ยืนยัน</button>
        </div>
    </div>
</div>
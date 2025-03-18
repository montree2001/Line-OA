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
    <!-- ตัวเลือกเปลี่ยนห้องเรียน -->
    <div class="class-selector">
        <label for="class-select">เลือกห้องเรียน:</label>
        <select id="class-select" onchange="changeClass(this.value)">
            <?php foreach ($teacher_classes as $class): ?>
                <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $current_class_id) ? 'selected' : ''; ?>>
                    <?php echo $class['name']; ?> (<?php echo $class['present_count'] + $class['absent_count']; ?>/<?php echo $class['total_students']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-info">
        <div class="class-details">
            <h2><?php echo $current_class['name']; ?></h2>
            <p>นักเรียนทั้งหมด <?php echo $current_class['total_students']; ?> คน</p>
        </div>
        <div class="date-info">
            <p>วันที่ <?php echo date('j F Y'); ?></p>
            <p>เวลา <?php echo date('H:i'); ?> น.</p>
        </div>
    </div>

    <!-- สถิติการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="value"><?php echo $current_class['total_students']; ?></div>
            <div class="label">ทั้งหมด</div>
        </div>
        <div class="stat-card present">
            <div class="value" id="present-count"><?php echo $current_class['present_count']; ?></div>
            <div class="label">มาเรียน</div>
        </div>
        <div class="stat-card absent">
            <div class="value" id="absent-count"><?php echo $current_class['absent_count']; ?></div>
            <div class="label">ขาดเรียน</div>
        </div>
        <div class="stat-card not-checked">
            <div class="value" id="not-checked-count"><?php echo $current_class['not_checked']; ?></div>
            <div class="label">ยังไม่เช็ค</div>
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

    <!-- แท็บเมนู -->
    <div class="tabs">
        <button class="tab-button active" onclick="switchTab('unchecked')">
            <span class="material-icons">schedule</span> รอเช็คชื่อ <span class="count"><?php echo count($unchecked_students); ?></span>
        </button>
        <button class="tab-button" onclick="switchTab('checked')">
            <span class="material-icons">done_all</span> เช็คชื่อแล้ว <span class="count"><?php echo count($checked_students); ?></span>
        </button>
    </div>

    <!-- รายชื่อนักเรียนที่ยังไม่ได้เช็คชื่อ -->
    <div id="unchecked-tab" class="tab-content active">
        <?php if (empty($unchecked_students)): ?>
            <div class="empty-state">
                <span class="material-icons">check_circle</span>
                <p>เช็คชื่อครบทุกคนแล้ว!</p>
            </div>
        <?php else: ?>
            <div class="student-list">
                <div class="list-header">
                    <div>เลขที่</div>
                    <div>ชื่อ-นามสกุล</div>
                    <div>การเช็คชื่อ</div>
                </div>
                
                <?php foreach ($unchecked_students as $student): ?>
                <div class="student-item" data-name="<?php echo $student['name']; ?>">
                    <div class="student-number"><?php echo $student['number']; ?></div>
                    <div class="student-name"><?php echo $student['name']; ?></div>
                    <div class="attendance-actions">
                        <button class="action-button present" onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)">
                            <span class="material-icons">check</span>
                        </button>
                        <button class="action-button absent" onclick="markAttendance(this, 'absent', <?php echo $student['id']; ?>)">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- รายชื่อนักเรียนที่เช็คชื่อแล้ว -->
    <div id="checked-tab" class="tab-content">
        <?php if (empty($checked_students)): ?>
            <div class="empty-state">
                <span class="material-icons">schedule</span>
                <p>ยังไม่มีการเช็คชื่อในวันนี้</p>
            </div>
        <?php else: ?>
            <div class="student-list">
                <div class="list-header">
                    <div>เลขที่</div>
                    <div>ชื่อ-นามสกุล</div>
                    <div>สถานะ</div>
                    <div>เวลา</div>
                </div>
                
                <?php foreach ($checked_students as $student): ?>
                <div class="student-item" data-name="<?php echo $student['name']; ?>">
                    <div class="student-number"><?php echo $student['number']; ?></div>
                    <div class="student-name"><?php echo $student['name']; ?></div>
                    <div class="student-status <?php echo $student['status']; ?>">
                        <?php if ($student['status'] === 'present'): ?>
                            <span class="material-icons">check_circle</span> มา
                        <?php else: ?>
                            <span class="material-icons">cancel</span> ขาด
                        <?php endif; ?>
                    </div>
                    <div class="check-time"><?php echo $student['time_checked']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
        <div class="modal-subtitle"><?php echo $current_class['name']; ?></div>
        <div class="pin-code"><?php echo $pin_code; ?></div>
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
        <div class="modal-subtitle"><?php echo $current_class['name']; ?></div>
        <p>คุณต้องการเช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดเป็น "มาเรียน" ใช่หรือไม่?</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('mark-all-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="markAllPresent()">ยืนยัน</button>
        </div>
    </div>
</div>

<!-- Modal ยืนยันการบันทึก -->
<div class="modal" id="save-modal">
    <div class="modal-content">
        <div class="modal-title">บันทึกการเช็คชื่อ</div>
        <div class="modal-subtitle"><?php echo $current_class['name']; ?></div>
        <p>ยังมีนักเรียนที่ยังไม่ได้เช็คชื่ออีก <?php echo $current_class['not_checked']; ?> คน</p>
        <p>คุณต้องการบันทึกการเช็คชื่อเข้าแถวนี้ใช่หรือไม่?</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('save-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="confirmSaveAttendance()">บันทึก</button>
        </div>
    </div>
</div>
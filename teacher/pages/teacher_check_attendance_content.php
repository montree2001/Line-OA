<div class="header">
    <a href="home.php" class="header-icon">
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
                    <?php echo $class['name']; ?> (<?php echo $class['total_students']; ?> คน)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ตัวเลือกวันที่ -->
    <div class="date-selector">
        <label for="date-select">วันที่เช็คชื่อ:</label>
        <input type="date" id="date-select" value="<?php echo $check_date; ?>" max="<?php echo date('Y-m-d'); ?>" onchange="changeDate(this.value)">

        <?php if ($is_retroactive): ?>
            <div class="retroactive-warning">
                <span class="material-icons">warning</span>
                <span>คุณกำลังเช็คชื่อย้อนหลังสำหรับวันที่ <?php echo date('d/m/Y', strtotime($check_date)); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-info">
        <div class="class-details">
            <h2><?php echo $current_class['name']; ?></h2>
            <p>นักเรียนทั้งหมด <?php echo $current_class['total_students']; ?> คน</p>
        </div>
        <div class="date-info">
            <p>วันที่ <?php echo date('j F Y', strtotime($check_date)); ?></p>
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
        <button class="tab-btn active" onclick="showTab('unchecked')">
            <span class="material-icons">schedule</span> รอเช็คชื่อ <span class="count"><?php echo count($unchecked_students); ?></span>
        </button>
        <button class="tab-btn" onclick="showTab('checked')">
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
                    <div>รหัส/ชื่อ-นามสกุล</div>
                    <div>การเช็คชื่อ</div>
                </div>

                <?php foreach ($unchecked_students as $student): ?>
                    <div class="student-item" data-name="<?php echo $student['name']; ?>" data-id="<?php echo $student['id']; ?>">
                        <div class="student-number"><?php echo $student['number']; ?></div>
                        <div class="student-name">
                            <div><?php echo $student['code']; ?></div>
                            <div><?php echo $student['name']; ?></div>
                        </div>
                        <div class="attendance-actions">
                            <button class="action-button present" onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)">
                                <span class="material-icons">check</span>
                            </button>
                            <button class="action-button late" onclick="markAttendance(this, 'late', <?php echo $student['id']; ?>)">
                                <span class="material-icons">schedule</span>
                            </button>
                            <button class="action-button leave" onclick="markAttendance(this, 'leave', <?php echo $student['id']; ?>)">
                                <span class="material-icons">event_note</span>
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
                    <div>รหัส/ชื่อ-นามสกุล</div>
                    <div>สถานะ</div>
                    <div>เวลา/วิธี</div>
                </div>

                <?php foreach ($checked_students as $student): ?>
                    <div class="student-item" data-name="<?php echo $student['name']; ?>" data-id="<?php echo $student['id']; ?>" data-status="<?php echo $student['status']; ?>">
                        <div class="student-number"><?php echo $student['number']; ?></div>
                        <div class="student-name">
                            <div><?php echo $student['code']; ?></div>
                            <div><?php echo $student['name']; ?></div>
                            <?php if (!empty($student['remarks'])): ?>
                                <div class="student-remarks"><?php echo $student['remarks']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="student-status <?php echo $student['status']; ?>">
                            <?php if ($student['status'] === 'present'): ?>
                                <span class="material-icons">check_circle</span> มา
                            <?php else: ?>
                                <span class="material-icons">cancel</span> ขาด
                            <?php endif; ?>
                        </div>
                        <div class="check-info">
                            <div class="check-time"><?php echo $student['time_checked']; ?></div>
                            <div class="check-method"><?php echo getCheckMethodText($student['check_method']); ?></div>
                        </div>
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
        <div id="pin-display" class="pin-code">----</div>
        <p class="pin-info">รหัส PIN นี้จะหมดอายุใน <span id="pin-expire-time">10</span> นาที</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('pin-modal')">ปิด</button>
            <button class="modal-button confirm" onclick="generateNewPin()">สร้างใหม่</button>
        </div>
    </div>
</div>

<!-- Modal QR Code Scanner -->
<div class="modal" id="qr-modal">
    <div class="modal-content">
        <div class="modal-title">สแกน QR Code นักเรียน</div>
        <div class="modal-subtitle"><?php echo $current_class['name']; ?></div>
        <div class="qr-scanner-placeholder">
            <span class="material-icons">qr_code_scanner</span>
            <p>กรุณาอนุญาตการใช้งานกล้อง</p>
        </div>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('qr-modal')">ยกเลิก</button>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อทั้งหมด -->
<div class="modal" id="mark-all-modal">
    <div class="modal-content">
        <div class="modal-title">เช็คชื่อนักเรียนทั้งหมด</div>
        <div class="modal-subtitle"><?php echo $current_class['name']; ?></div>
        <p>คุณต้องการเช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดเป็น "มาเรียน" ใช่หรือไม่?</p>

        <?php if ($is_retroactive): ?>
            <div class="retroactive-note">
                <div class="note-title">ระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง:</div>
                <textarea id="retroactive-note" placeholder="ระบุหมายเหตุการเช็คชื่อย้อนหลัง เช่น เอกสารลาป่วย เอกสารขออนุญาต ฯลฯ"></textarea>
            </div>
        <?php endif; ?>

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
        <p>ยังมีนักเรียนที่ยังไม่ได้เช็คชื่ออีก <span id="remaining-students"><?php echo $current_class['not_checked']; ?></span> คน</p>
        <p>คุณต้องการบันทึกการเช็คชื่อเข้าแถวนี้ใช่หรือไม่?</p>

        <?php if ($is_retroactive): ?>
            <div class="retroactive-note">
                <div class="note-title">ระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง:</div>
                <textarea id="retroactive-save-note" placeholder="ระบุหมายเหตุการเช็คชื่อย้อนหลัง เช่น เอกสารลาป่วย เอกสารขออนุญาต ฯลฯ"></textarea>
            </div>
        <?php endif; ?>

        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('save-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="confirmSaveAttendance()">บันทึก</button>
        </div>
    </div>
</div>

<!-- Modal แสดงเช็คชื่อรายบุคคล -->
<div class="modal" id="mark-attendance-modal">
    <div class="modal-content">
        <div class="modal-title">เช็คชื่อนักเรียน</div>
        <div class="modal-subtitle"><span id="student-name-display"></span></div>

        <div class="attendance-status-selector">
            <div class="status-option">
                <input type="radio" id="status-present" name="attendance-status" value="present" checked>
                <label for="status-present">
                    <span class="material-icons present">check_circle</span> มาเรียน
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-late" name="attendance-status" value="late">
                <label for="status-late">
                    <span class="material-icons late">schedule</span> มาสาย
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-leave" name="attendance-status" value="leave">
                <label for="status-leave">
                    <span class="material-icons leave">event_note</span> ลา
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-absent" name="attendance-status" value="absent">
                <label for="status-absent">
                    <span class="material-icons absent">cancel</span> ขาดเรียน
                </label>
            </div>
        </div>

        <div class="remarks-input">
            <div class="note-title">หมายเหตุ:</div>
            <textarea id="attendance-remarks" placeholder="ระบุหมายเหตุเพิ่มเติม เช่น สาเหตุการมาสาย เหตุผลการลา"></textarea>
        </div>

        <?php if ($is_retroactive): ?>
            <div class="retroactive-note">
                <div class="note-title">ระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง:</div>
                <textarea id="individual-note" placeholder="ระบุหมายเหตุการเช็คชื่อย้อนหลัง เช่น เอกสารลาป่วย เอกสารขออนุญาต ฯลฯ"></textarea>
            </div>
        <?php endif; ?>

        <input type="hidden" id="student-id-input" value="">

        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('mark-attendance-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="confirmMarkAttendance()">บันทึก</button>
        </div>
    </div>
</div>

<script>
    // สร้างตัวแปรที่ส่งค่าไปยัง JavaScript
    const currentClassId = <?php echo $current_class_id; ?>;
    const checkDate = '<?php echo $check_date; ?>';
    const isRetroactive = <?php echo $is_retroactive ? 'true' : 'false'; ?>;
    const currentTeacherId = <?php echo $teacher_id; ?>;
</script>

<?php
// ฟังก์ชัน helper สำหรับแปลงรหัสวิธีการเช็คชื่อเป็นข้อความ
function getCheckMethodText($method)
{
    switch ($method) {
        case 'PIN':
            return 'PIN';
        case 'QR_Code':
            return 'QR';
        case 'GPS':
            return 'GPS';
        case 'Manual':
            return 'ครู';
        default:
            return '';
    }
}
?>
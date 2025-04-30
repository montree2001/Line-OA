<?php

/**
 * teacher/pages/teacher_check_attendance_content.php
 * 
 * เนื้อหาหน้าเช็คชื่อนักเรียน
 * รองรับการเช็คชื่อ: มา, ขาด, สาย, ลา และบันทึกหมายเหตุ
 */
?>
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
            <p>วันที่ <?php echo date('j F พ.ศ. ', strtotime($check_date)) . (date('Y', strtotime($check_date)) + 543); ?></p>
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
            <div class="value" id="present-count"><?php echo $present_count; ?></div>
            <div class="label">มาเรียน</div>
        </div>
        <div class="stat-card absent">
            <div class="value" id="absent-count"><?php echo $absent_count; ?></div>
            <div class="label">ขาดเรียน</div>
        </div>
        <div class="stat-card not-checked">
            <div class="value" id="not-checked-count"><?php echo $not_checked; ?></div>
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

    <!-- แท็บรายชื่อนักเรียนที่ยังไม่ได้เช็คชื่อ -->
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
                    <div class="student-item" data-name="<?php echo $student['name']; ?>" data-id="<?php echo $student['id']; ?>">
                        <div class="student-number"><?php echo $student['number']; ?></div>
                        <div class="student-name" onclick="showAttendanceModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                            <?php echo $student['name']; ?>
                        </div>
                        <div class="attendance-actions">
                            <button class="action-button present" title="มาเรียน" onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)">
                                <span class="material-icons">check</span>
                            </button>
                            <button class="action-button absent" title="ขาด" onclick="markAttendance(this, 'absent', <?php echo $student['id']; ?>)">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- แท็บรายชื่อนักเรียนที่เช็คชื่อแล้ว -->
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
                    <div>เวลา/วิธี</div>
                </div>

                <?php foreach ($checked_students as $student): ?>
                    <?php
                    // กำหนดสถานะและไอคอน
                    $status_icon = '';
                    $status_text = '';
                    $status_class = '';

                    switch ($student['status']) {
                        case 'present':
                            $status_icon = 'check_circle';
                            $status_text = 'มา';
                            $status_class = 'present';
                            break;
                        case 'late':
                            $status_icon = 'schedule';
                            $status_text = 'สาย';
                            $status_class = 'late';
                            break;
                        case 'leave':
                            $status_icon = 'event_note';
                            $status_text = 'ลา';
                            $status_class = 'leave';
                            break;
                        case 'absent':
                            $status_icon = 'cancel';
                            $status_text = 'ขาด';
                            $status_class = 'absent';
                            break;
                    }
                    ?>
                    <div class="student-item" data-name="<?php echo $student['name']; ?>" data-id="<?php echo $student['id']; ?>" data-status="<?php echo $student['status']; ?>">
                        <div class="student-number"><?php echo $student['number']; ?></div>
                        <div class="student-name" onclick="editAttendance(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                            <?php echo $student['name']; ?>
                            <?php if (!empty($student['remarks'])): ?>
                                <div class="student-remarks"><?php echo $student['remarks']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="student-status <?php echo $status_class; ?>">
                            <span class="material-icons"><?php echo $status_icon; ?></span> <?php echo $status_text; ?>
                        </div>
                        <div class="check-info">
                            <div class="check-time"><?php echo $student['time_checked']; ?></div>
                            <div class="check-method"><?php
                                                        echo $student['check_method'] === 'Manual' ? 'ครู' : $student['check_method'];
                                                        ?></div>
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

        <div class="mark-all-options">
            <div class="mark-all-option">
                <input type="radio" id="mark-all-present" name="mark-all-status" value="present" checked>
                <label for="mark-all-present" class="status-label present">
                    <span class="material-icons">check</span> เช็คทั้งหมดเป็น "มาเรียน"
                </label>
            </div>
            <div class="mark-all-option">
                <input type="radio" id="mark-all-late" name="mark-all-status" value="late">
                <label for="mark-all-late" class="status-label late">
                    <span class="material-icons">schedule</span> เช็คทั้งหมดเป็น "มาสาย"
                </label>
            </div>
            <div class="mark-all-option">
                <input type="radio" id="mark-all-leave" name="mark-all-status" value="leave">
                <label for="mark-all-leave" class="status-label leave">
                    <span class="material-icons">event_note</span> เช็คทั้งหมดเป็น "ลา"
                </label>
            </div>
            <div class="mark-all-option">
                <input type="radio" id="mark-all-absent" name="mark-all-status" value="absent">
                <label for="mark-all-absent" class="status-label absent">
                    <span class="material-icons">close</span> เช็คทั้งหมดเป็น "ขาดเรียน"
                </label>
            </div>
        </div>

        <?php if ($is_retroactive): ?>
            <div class="retroactive-note">
                <div class="note-title">ระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง:</div>
                <textarea id="retroactive-note" placeholder="ระบุหมายเหตุการเช็คชื่อย้อนหลัง เช่น เอกสารลาป่วย เอกสารขออนุญาต ฯลฯ"></textarea>
            </div>
        <?php endif; ?>

        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('mark-all-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="markAllWithStatus()">ยืนยัน</button>
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

<!-- Modal แสดงเช็คชื่อรายบุคคล (สำหรับเพิ่มการเช็คชื่อสาย/ลา) -->
<div class="modal" id="mark-attendance-modal">
    <div class="modal-content">
        <div class="modal-title">เช็คชื่อนักเรียน</div>
        <div class="modal-subtitle"><span id="student-name-display"></span></div>

        <div class="attendance-status-selector">
            <div class="status-option">
                <input type="radio" id="status-present" name="attendance-status" value="present" checked>
                <label for="status-present" class="status-label present">
                    <span class="material-icons">check</span> มาเรียน
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-late" name="attendance-status" value="late">
                <label for="status-late" class="status-label late">
                    <span class="material-icons">schedule</span> มาสาย
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-leave" name="attendance-status" value="leave">
                <label for="status-leave" class="status-label leave">
                    <span class="material-icons">event_note</span> ลา
                </label>
            </div>
            <div class="status-option">
                <input type="radio" id="status-absent" name="attendance-status" value="absent">
                <label for="status-absent" class="status-label absent">
                    <span class="material-icons">close</span> ขาดเรียน
                </label>
            </div>
        </div>

        <!-- ส่วนระบุเหตุผลสำหรับการเช็คชื่อ -->
        <div id="reason-container" class="reason-container">
            <div class="reason-title">กรุณาระบุเหตุผล:</div>
            <textarea id="status-reason" placeholder="ระบุเหตุผลการมาสาย/การลา เช่น ป่วย รถติด ฯลฯ"></textarea>
        </div>

        <?php if ($is_retroactive): ?>
            <div class="retroactive-note">
                <div class="note-title">ระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง:</div>
                <textarea id="individual-note" placeholder="ระบุหมายเหตุการเช็คชื่อย้อนหลัง เช่น เอกสารลาป่วย เอกสารขออนุญาต ฯลฯ"></textarea>
            </div>
        <?php endif; ?>

        <input type="hidden" id="student-id-input" value="">
        <input type="hidden" id="is-edit-mode" value="0">

        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('mark-attendance-modal')">ยกเลิก</button>
            <button class="modal-button confirm" onclick="confirmMarkAttendance()">บันทึก</button>
        </div>
    </div>
</div>




<!-- Modal เช็คชื่อย้อนหลัง -->
<div class="modal" id="retroactiveModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">ยืนยันการเช็คชื่อย้อนหลัง</h3>
                <button type="button" class="close-btn" onclick="closeModal('retroactiveModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-retroactive-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="modal-retroactive-text">
                        <strong>คุณกำลังทำการเช็คชื่อย้อนหลัง</strong>
                        สำหรับวันที่ <span id="retroactiveDisplayDate"><?php echo $display_date; ?></span>
                        <p>การเช็คชื่อย้อนหลังจำเป็นต้องระบุเหตุผลที่ชัดเจน และจะถูกบันทึกประวัติการเช็ค</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="retroactiveReason">
                        <strong>เหตุผลการเช็คชื่อย้อนหลัง</strong>
                        <span class="required-mark">*</span>
                    </label>
                    <textarea id="retroactiveReason" class="form-control" rows="3" 
                        placeholder="เช่น ใบรับรองแพทย์, หนังสือลาที่ได้รับล่าช้า, การแก้ไขข้อมูลผิดพลาด ฯลฯ" required></textarea>
                    <div class="form-hint">เหตุผลจะถูกบันทึกในประวัติระบบ</div>
                </div>
                
                <div class="form-group">
                    <label for="retroactiveStatus">
                        <strong>สถานะการเช็คชื่อ</strong>
                    </label>
                    <div class="status-options">
                        <label class="status-option">
                            <input type="radio" name="retroactiveStatus" value="present" checked>
                            <span class="status-label present">
                                <i class="fas fa-check-circle"></i> มาเรียน
                            </span>
                        </label>
                        
                        <label class="status-option">
                            <input type="radio" name="retroactiveStatus" value="late">
                            <span class="status-label late">
                                <i class="fas fa-clock"></i> มาสาย
                            </span>
                        </label>
                        
                        <label class="status-option">
                            <input type="radio" name="retroactiveStatus" value="leave">
                            <span class="status-label leave">
                                <i class="fas fa-clipboard"></i> ลา
                            </span>
                        </label>
                        
                        <label class="status-option">
                            <input type="radio" name="retroactiveStatus" value="absent">
                            <span class="status-label absent">
                                <i class="fas fa-times-circle"></i> ขาดเรียน
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group" id="retroactiveRemarksContainer">
                    <label for="retroactiveRemarks">
                        <strong>หมายเหตุเพิ่มเติม</strong>
                    </label>
                    <textarea id="retroactiveRemarks" class="form-control" rows="2" 
                        placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                </div>
                
                <input type="hidden" id="retroactiveStudentId" value="">
                <input type="hidden" id="retroactiveStudentName" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="closeModal('retroactiveModal')">ยกเลิก</button>
                <button type="button" class="btn primary" onclick="confirmRetroactiveAttendance()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- คำแนะนำการเช็คชื่อย้อนหลัง -->
<?php if ($is_retroactive): ?>
<div class="retroactive-reminder">
    <div class="reminder-icon"><i class="fas fa-exclamation-circle"></i></div>
    <div class="reminder-text">
        <strong>คุณกำลังเช็คชื่อย้อนหลังสำหรับวันที่ <?php echo $display_date; ?></strong>
        กรุณาระบุเหตุผลการเช็คชื่อย้อนหลังทุกครั้ง เช่น ใบรับรองแพทย์, หนังสือลา, การแก้ไขข้อมูลผิดพลาด ฯลฯ
    </div>
</div>
<?php endif; ?>

<!-- ประวัติการเช็คชื่อย้อนหลัง (ถ้ามี) -->
<?php if ($is_retroactive && !empty($retroactive_histories)): ?>
<div class="retroactive-history-section">
    <h3>
        <i class="fas fa-history"></i> 
        ประวัติการเช็คชื่อย้อนหลัง
        <span class="history-count"><?php echo count($retroactive_histories); ?> รายการ</span>
    </h3>
    
    <div class="retroactive-history-list">
        <?php foreach ($retroactive_histories as $history): ?>
            <div class="history-item">
                <div class="history-header">
                    <div class="history-user">
                        <i class="fas fa-user-edit"></i>
                        <?php 
                            echo $history['first_name'] . ' ' . $history['last_name'];
                            echo ' (' . ($history['role'] === 'teacher' ? 'ครู' : ($history['role'] === 'admin' ? 'แอดมิน' : $history['role'])) . ')';
                        ?>
                    </div>
                    <div class="history-time">
                        <?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?>
                    </div>
                </div>
                <div class="history-content">
                    <div class="history-status">
                        <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($history['retroactive_status']) {
                                case 'present':
                                    $status_class = 'present';
                                    $status_text = 'มาเรียน';
                                    break;
                                case 'late':
                                    $status_class = 'late';
                                    $status_text = 'มาสาย';
                                    break;
                                case 'leave':
                                    $status_class = 'leave';
                                    $status_text = 'ลา';
                                    break;
                                case 'absent':
                                    $status_class = 'absent';
                                    $status_text = 'ขาดเรียน';
                                    break;
                            }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                    <div class="history-reason">
                        <strong>เหตุผล:</strong> <?php echo htmlspecialchars($history['retroactive_reason']); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Include enhanced student search functionality -->
<script src="assets/js/student-search.js"></script>

<script>
    // สร้างตัวแปรที่ส่งค่าไปยัง JavaScript
    const currentClassId = <?php echo $current_class_id; ?>;
    const checkDate = '<?php echo $check_date; ?>';
    const isRetroactive = <?php echo $is_retroactive ? 'true' : 'false'; ?>;
    const currentTeacherId = <?php echo $teacher_id; ?>;

    // เพิ่มการแสดง/ซ่อนช่องหมายเหตุตามสถานะที่เลือก
    document.addEventListener('DOMContentLoaded', function() {
        const statusInputs = document.querySelectorAll('input[name="attendance-status"]');
        const reasonContainer = document.getElementById('reason-container');

        function toggleReasonField() {
            const selectedStatus = document.querySelector('input[name="attendance-status"]:checked').value;
            if (selectedStatus === 'late' || selectedStatus === 'leave') {
                reasonContainer.style.display = 'block';
            } else {
                reasonContainer.style.display = 'none';
            }
        }

        // เรียกครั้งแรกเพื่อตั้งค่าเริ่มต้น
        toggleReasonField();

        // เพิ่ม event listener สำหรับการเปลี่ยนสถานะ
        statusInputs.forEach(input => {
            input.addEventListener('change', toggleReasonField);
        });
    });

    // ฟังก์ชันแสดง Modal การเช็คชื่อแบบมีตัวเลือกเพิ่มเติม (สาย/ลา)
    function showAttendanceModal(studentId, studentName) {
        document.getElementById('student-name-display').textContent = studentName;
        document.getElementById('student-id-input').value = studentId;
        document.getElementById('is-edit-mode').value = '0';
        document.getElementById('status-present').checked = true;
        document.getElementById('status-reason').value = '';

        if (document.getElementById('individual-note')) {
            document.getElementById('individual-note').value = '';
        }

        // แสดง Modal
        const modal = document.getElementById('mark-attendance-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    // ฟังก์ชันแก้ไขการเช็คชื่อที่บันทึกไปแล้ว
    function editAttendance(studentId, studentName, status, remarks) {
        document.getElementById('student-name-display').textContent = studentName;
        document.getElementById('student-id-input').value = studentId;
        document.getElementById('is-edit-mode').value = '1';

        // เลือกสถานะปัจจุบัน
        document.getElementById('status-' + status).checked = true;

        // กรอกหมายเหตุที่มีอยู่เดิม
        document.getElementById('status-reason').value = remarks || '';

        if (document.getElementById('individual-note')) {
            document.getElementById('individual-note').value = '';
        }

        // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
        const reasonContainer = document.getElementById('reason-container');
        if (status === 'late' || status === 'leave') {
            reasonContainer.style.display = 'block';
        } else {
            reasonContainer.style.display = 'none';
        }

        // แสดง Modal
        const modal = document.getElementById('mark-attendance-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
</script>
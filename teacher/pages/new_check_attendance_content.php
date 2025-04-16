<?php

/**
 * new_check_attendance_content.php - เนื้อหาของหน้าเช็คชื่อนักเรียนรูปแบบใหม่
 * 
 * ออกแบบหน้า UI ใหม่ตามความต้องการ:
 * - ใช้งานง่าย ไม่ซับซ้อน
 * - แสดงสถานะ มา/ขาด/สาย/ลา ชัดเจน
 * - มีฟังก์ชันครบถ้วนตามความต้องการ
 * - แสดงปีเป็น พ.ศ.
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
    <div class="dropdown-menu" id="optionsMenu">
        <a href="javascript:void(0)" onclick="downloadReport()">
            <span class="material-icons">download</span> ดาวน์โหลดรายงาน
        </a>
        <a href="student_history.php?class_id=<?php echo $current_class_id; ?>">
            <span class="material-icons">history</span> ประวัติการเช็คชื่อ
        </a>
        <a href="class_parents.php?class_id=<?php echo $current_class_id; ?>">
            <span class="material-icons">family_restroom</span> ข้อมูลผู้ปกครอง
        </a>
        <a href="javascript:void(0)" onclick="showHelp()">
            <span class="material-icons">help_outline</span> วิธีใช้งาน
        </a>
    </div>
</div>
<div class="container">
    <div class="attendance-container">
        <!-- การ์ดข้อมูลคลาส -->
        <div class="class-info-card">
            <div class="class-selector">
                <label for="classSelect">ห้องเรียน:</label>
                <select id="classSelect" onchange="changeClass(this.value)">
                    <?php foreach ($teacher_classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $current_class_id) ? 'selected' : ''; ?>>
                            <?php echo $class['name']; ?> (<?php echo $class['total_students']; ?> คน)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="date-selector">
                <label for="dateSelect">วันที่เช็คชื่อ:</label>
                <input type="date" id="dateSelect" value="<?php echo $check_date; ?>" max="<?php echo date('Y-m-d'); ?>" onchange="changeDate(this.value)">

                <?php if ($is_retroactive): ?>
                    <div class="retroactive-badge">
                        <i class="fas fa-history"></i> เช็คชื่อย้อนหลัง
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- สรุปข้อมูลการเช็คชื่อ -->
        <div class="attendance-summary">
            <div class="summary-item total">
                <div class="summary-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $total_students; ?></div>
                    <div class="summary-label">นักเรียนทั้งหมด</div>
                </div>
            </div>

            <div class="summary-item present">
                <div class="summary-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $present_count; ?></div>
                    <div class="summary-label">มาเรียน</div>
                </div>
            </div>

            <div class="summary-item late">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $late_count; ?></div>
                    <div class="summary-label">มาสาย</div>
                </div>
            </div>

            <div class="summary-item leave">
                <div class="summary-icon">
                    <i class="fas fa-clipboard"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $leave_count; ?></div>
                    <div class="summary-label">ลา</div>
                </div>
            </div>

            <div class="summary-item absent">
                <div class="summary-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $absent_count; ?></div>
                    <div class="summary-label">ขาดเรียน</div>
                </div>
            </div>

            <div class="summary-item not-checked">
                <div class="summary-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="summary-content">
                    <div class="summary-value"><?php echo $not_checked; ?></div>
                    <div class="summary-label">ยังไม่เช็ค</div>
                </div>
            </div>
        </div>

        <!-- ปุ่มดำเนินการเช็คชื่อ -->
        <div class="action-buttons">
            <button type="button" class="btn primary" onclick="createPIN()">
                <i class="fas fa-key"></i> สร้างรหัส PIN
            </button>
            <button type="button" class="btn secondary" onclick="scanQR()">
                <i class="fas fa-qrcode"></i> สแกน QR Code
            </button>
            <button type="button" class="btn success" onclick="markAllAttendance()">
                <i class="fas fa-check-double"></i> เช็คชื่อทั้งหมด
            </button>
        </div>

        <!-- ช่องค้นหา -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาชื่อนักเรียน..." oninput="searchStudents()">
        </div>

        <!-- แท็บการเช็คชื่อ -->
        <div class="attendance-tabs">
            <button type="button" class="tab-button active" data-tab="waiting">
                <i class="fas fa-hourglass-half"></i> รอเช็คชื่อ <span class="count"><?php echo count($unchecked_students); ?></span>
            </button>
            <button type="button" class="tab-button" data-tab="checked">
                <i class="fas fa-check-double"></i> เช็คชื่อแล้ว <span class="count"><?php echo count($checked_students); ?></span>
            </button>
        </div>

        <!-- แท็บเนื้อหา -->
        <div class="tab-content">
            <!-- รายชื่อนักเรียนที่ยังไม่ได้เช็คชื่อ -->
            <!-- รายชื่อนักเรียนที่ยังไม่ได้เช็คชื่อ -->
<!-- รายชื่อนักเรียนที่ยังไม่ได้เช็คชื่อ -->
<div id="waitingTab" class="tab-pane active">
    <?php if (empty($unchecked_students)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
            <h3>เช็คชื่อครบทุกคนแล้ว!</h3>
            <p>ทุกคนได้รับการเช็คชื่อเรียบร้อยแล้ว</p>
        </div>
    <?php else: ?>
        <div class="student-list">
            <?php foreach ($unchecked_students as $student): ?>
                <div class="student-card" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="student-number"><?php echo $student['number']; ?></div>

                    <div class="student-info" onclick="showDetailAttendanceModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                        <?php if ($student['profile_picture']): ?>
                            <div class="student-avatar" style="background-image: url('<?php echo $student['profile_picture']; ?>')"></div>
                        <?php else: ?>
                            <div class="student-avatar"><?php echo mb_substr(str_replace(['นาย', 'นางสาว', 'นาง', 'เด็กชาย', 'เด็กหญิง'], '', $student['name']), 0, 1, 'UTF-8'); ?></div>
                        <?php endif; ?>

                        <div class="student-details">
                            <div class="student-name"><?php echo $student['name']; ?></div>
                            <div class="student-code">รหัส: <?php echo $student['code']; ?></div>
                        </div>
                    </div>

                    <!-- ปุ่มเช็คชื่อในรายการนักเรียน - ปรับปรุงใหม่ใช้ AJAX เพื่อบันทึกทันที -->
                    <div class="student-actions">
                        <button type="button" class="action-btn present action-button" title="มาเรียน" 
                                onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        
                        <button type="button" class="action-btn absent action-button" title="ขาดเรียน"
                                onclick="markAttendance(this, 'absent', <?php echo $student['id']; ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <button type="button" class="action-btn more" title="เช็คแบบละเอียด" 
                                onclick="showDetailAttendanceModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

            <!-- รายชื่อนักเรียนที่เช็คชื่อแล้ว -->
            <div id="checkedTab" class="tab-pane">
                <?php if (empty($checked_students)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-hourglass-start"></i></div>
                        <h3>ยังไม่มีการเช็คชื่อ</h3>
                        <p>ยังไม่มีนักเรียนที่ได้รับการเช็คชื่อในวันที่เลือก</p>
                    </div>
                <?php else: ?>
                    <div class="student-list">
                        <?php foreach ($checked_students as $student): ?>
                            <?php
                            // กำหนดคลาสและไอคอนตามสถานะ
                            $status_class = '';
                            $status_icon = '';
                            $status_text = '';

                            switch ($student['status']) {
                                case 'present':
                                    $status_class = 'present';
                                    $status_icon = 'fa-check-circle';
                                    $status_text = 'มาเรียน';
                                    break;
                                case 'late':
                                    $status_class = 'late';
                                    $status_icon = 'fa-clock';
                                    $status_text = 'มาสาย';
                                    break;
                                case 'leave':
                                    $status_class = 'leave';
                                    $status_icon = 'fa-clipboard';
                                    $status_text = 'ลา';
                                    break;
                                case 'absent':
                                    $status_class = 'absent';
                                    $status_icon = 'fa-times-circle';
                                    $status_text = 'ขาดเรียน';
                                    break;
                            }
                            ?>
                            <div class="student-card <?php echo $status_class; ?>-card" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo $student['status']; ?>" data-attendance-id="<?php echo $student['attendance_id']; ?>">
                                <div class="student-number"><?php echo $student['number']; ?></div>

                                <div class="student-info" onclick="if (typeof editAttendance === 'function') { editAttendance(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>'); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">
                                    <?php if ($student['profile_picture']): ?>
                                        <div class="student-avatar" style="background-image: url('<?php echo $student['profile_picture']; ?>')"></div>
                                    <?php else: ?>
                                        <div class="student-avatar"><?php echo mb_substr(str_replace(['นาย', 'นางสาว', 'นาง', 'เด็กชาย', 'เด็กหญิง'], '', $student['name']), 0, 1, 'UTF-8'); ?></div>
                                    <?php endif; ?>

                                    <div class="student-details">
                                        <div class="student-name"><?php echo $student['name']; ?></div>
                                        <?php if (!empty($student['remarks'])): ?>
                                            <div class="student-remarks"><?php echo $student['remarks']; ?></div>
                                        <?php else: ?>
                                            <div class="student-code">รหัส: <?php echo $student['code']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="student-status-info">
                                    <div class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </div>

                                    <div class="check-details">
                                        <div class="check-time"><?php echo $student['time_checked']; ?></div>
                                        <div class="check-method"><?php
                                                                    switch ($student['check_method']) {
                                                                        case 'Manual':
                                                                            echo 'ครู';
                                                                            break;
                                                                        case 'PIN':
                                                                            echo 'PIN';
                                                                            break;
                                                                        case 'QR_Code':
                                                                            echo 'QR';
                                                                            break;
                                                                        case 'GPS':
                                                                            echo 'GPS';
                                                                            break;
                                                                        default:
                                                                            echo $student['check_method'];
                                                                    }
                                                                    ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    </div>
</div>
<!-- Modal สร้าง PIN -->
<div class="modal" id="pinModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">สร้างรหัส PIN สำหรับเช็คชื่อ</h3>
                <button type="button" class="close-btn" onclick="if (typeof closeModal === 'function') { closeModal('pinModal'); } else { document.getElementById('pinModal').classList.remove('active'); }">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="pin-display">
                    <span class="pin-digit">-</span>
                    <span class="pin-digit">-</span>
                    <span class="pin-digit">-</span>
                    <span class="pin-digit">-</span>
                </div>
                <p class="pin-expire">รหัส PIN จะหมดอายุใน <span id="expireTime">10</span> นาที</p>
                <p class="class-detail"><?php echo $current_class['name']; ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="if (typeof closeModal === 'function') { closeModal('pinModal'); } else { document.getElementById('pinModal').classList.remove('active'); }">ปิด</button>
                <button type="button" class="btn primary" onclick="if (typeof generateNewPIN === 'function') { generateNewPIN(); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">สร้างใหม่</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สแกน QR Code -->
<div class="modal" id="qrModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">สแกน QR Code นักเรียน</h3>
                <button type="button" class="close-btn" onclick="if (typeof closeModal === 'function') { closeModal('qrModal'); } else { document.getElementById('qrModal').classList.remove('active'); }">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="qr-scanner-container" id="qrScannerContainer">
                    <!-- อุปกรณ์สแกนจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                    <div class="qr-placeholder">
                        <i class="fas fa-qrcode"></i>
                        <p>กำลังเรียกใช้กล้อง...</p>
                    </div>
                </div>
                <div class="qr-result-container" id="qrResultContainer" style="display: none;">
                    <div class="result-info" id="qrResultInfo"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="if (typeof closeModal === 'function') { closeModal('qrModal'); } else { document.getElementById('qrModal').classList.remove('active'); }">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อละเอียด -->
<div class="modal" id="attendanceDetailModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">เช็คชื่อนักเรียน</h3>
                <button type="button" class="close-btn" onclick="closeModal('attendanceDetailModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <h4 id="studentNameDetail" class="student-detail-name"></h4>

                <div class="status-options">
                    <label class="status-option">
                        <input type="radio" name="attendanceStatus" value="present" checked>
                        <span class="status-label present">
                            <i class="fas fa-check-circle"></i> มาเรียน
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="attendanceStatus" value="late">
                        <span class="status-label late">
                            <i class="fas fa-clock"></i> มาสาย
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="attendanceStatus" value="leave">
                        <span class="status-label leave">
                            <i class="fas fa-clipboard"></i> ลา
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="attendanceStatus" value="absent">
                        <span class="status-label absent">
                            <i class="fas fa-times-circle"></i> ขาดเรียน
                        </span>
                    </label>
                </div>

                <div class="remarks-container" id="remarksContainer">
                    <label for="attendanceRemarks">หมายเหตุ:</label>
                    <textarea id="attendanceRemarks" placeholder="ระบุหมายเหตุ เช่น สาเหตุการมาสาย, เหตุผลการลา ฯลฯ"></textarea>
                </div>

                <?php if ($is_retroactive): ?>
                    <div class="retroactive-note">
                        <label for="retroactiveNote">หมายเหตุการเช็คย้อนหลัง:</label>
                        <textarea id="retroactiveNote" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>

                <input type="hidden" id="studentIdDetail" value="">
                <input type="hidden" id="attendanceIdDetail" value="">
                <input type="hidden" id="isEditMode" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="closeModal('attendanceDetailModal')">ยกเลิก</button>
                <button type="button" class="btn primary" onclick="confirmDetailAttendance()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อทั้งหมด -->
<div class="modal" id="markAllModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">เช็คชื่อนักเรียนทั้งหมด</h3>
                <button type="button" class="close-btn" onclick="if (typeof closeModal === 'function') { closeModal('markAllModal'); } else { document.getElementById('markAllModal').classList.remove('active'); }">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="mark-all-desc">เลือกสถานะสำหรับนักเรียนที่ยังไม่ได้เช็คชื่อทั้งหมด <?php echo $not_checked; ?> คน</p>

                <div class="status-options mark-all-options">
                    <label class="status-option">
                        <input type="radio" name="markAllStatus" value="present" checked>
                        <span class="status-label present">
                            <i class="fas fa-check-circle"></i> เช็คเป็น "มาเรียน" ทั้งหมด
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="markAllStatus" value="late">
                        <span class="status-label late">
                            <i class="fas fa-clock"></i> เช็คเป็น "มาสาย" ทั้งหมด
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="markAllStatus" value="leave">
                        <span class="status-label leave">
                            <i class="fas fa-clipboard"></i> เช็คเป็น "ลา" ทั้งหมด
                        </span>
                    </label>

                    <label class="status-option">
                        <input type="radio" name="markAllStatus" value="absent">
                        <span class="status-label absent">
                            <i class="fas fa-times-circle"></i> เช็คเป็น "ขาดเรียน" ทั้งหมด
                        </span>
                    </label>
                </div>

                <?php if ($is_retroactive): ?>
                    <div class="retroactive-note">
                        <label for="markAllRetroactiveNote">หมายเหตุการเช็คย้อนหลัง:</label>
                        <textarea id="markAllRetroactiveNote" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="if (typeof closeModal === 'function') { closeModal('markAllModal'); } else { document.getElementById('markAllModal').classList.remove('active'); }">ยกเลิก</button>
                <button type="button" class="btn primary" onclick="if (typeof confirmMarkAll === 'function') { confirmMarkAll(); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">เช็คชื่อทั้งหมด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal บันทึกการเช็คชื่อ -->
<div class="modal" id="saveAttendanceModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">บันทึกการเช็คชื่อ</h3>
                <button type="button" class="close-btn" onclick="if (typeof closeModal === 'function') { closeModal('saveAttendanceModal'); } else { document.getElementById('saveAttendanceModal').classList.remove('active'); }">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="save-summary">
                    <div class="save-stat">
                        <span class="stat-circle total"><?php echo $total_students; ?></span>
                        <span class="stat-label">ทั้งหมด</span>
                    </div>
                    <div class="save-stat">
                        <span class="stat-circle checked" id="saveCheckedCount"><?php echo $checked_count; ?></span>
                        <span class="stat-label">เช็คแล้ว</span>
                    </div>
                    <div class="save-stat">
                        <span class="stat-circle remaining" id="saveRemainingCount"><?php echo $not_checked; ?></span>
                        <span class="stat-label">คงเหลือ</span>
                    </div>
                </div>

                <div class="confirmation-text">
                    <?php if ($not_checked > 0): ?>
                        <p class="warning-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            ยังมีนักเรียนที่ยังไม่ได้เช็คชื่อ <?php echo $not_checked; ?> คน
                        </p>
                        <p>นักเรียนที่ยังไม่ได้เช็คชื่อจะถูกบันทึกเป็น "ขาด" โดยอัตโนมัติ</p>
                    <?php else: ?>
                        <p class="success-text">
                            <i class="fas fa-check-circle"></i>
                            เช็คชื่อครบทุกคนแล้ว
                        </p>
                    <?php endif; ?>
                    <p>คุณต้องการบันทึกการเช็คชื่อนี้หรือไม่?</p>
                </div>

                <?php if ($is_retroactive): ?>
                    <div class="retroactive-note">
                        <label for="saveRetroactiveNote">หมายเหตุการเช็คย้อนหลัง:</label>
                        <textarea id="saveRetroactiveNote" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="if (typeof closeModal === 'function') { closeModal('saveAttendanceModal'); } else { document.getElementById('saveAttendanceModal').classList.remove('active'); }">ยกเลิก</button>
                <button type="button" class="btn primary" onclick="if (typeof confirmSaveAttendance === 'function') { confirmSaveAttendance(); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- คำแนะนำการใช้งาน Modal -->
<div class="modal" id="helpModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">วิธีใช้งานระบบเช็คชื่อ</h3>
                <button type="button" class="close-btn" onclick="if (typeof closeModal === 'function') { closeModal('helpModal'); } else { document.getElementById('helpModal').classList.remove('active'); }">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body help-content">
                <div class="help-section">
                    <h4><i class="fas fa-check"></i> การเช็คชื่อรายบุคคล</h4>
                    <p>คลิกปุ่ม <i class="fas fa-check"></i> เพื่อเช็คว่านักเรียนมาเรียน หรือคลิกปุ่ม <i class="fas fa-times"></i> เพื่อเช็คว่านักเรียนขาดเรียน</p>
                    <p>สำหรับการเช็คชื่อแบบมาสายหรือลา ให้คลิกที่ <i class="fas fa-ellipsis-h"></i> หรือที่ชื่อนักเรียน</p>
                </div>

                <div class="help-section">
                    <h4><i class="fas fa-users"></i> การเช็คชื่อทั้งห้อง</h4>
                    <p>คลิกปุ่ม "เช็คชื่อทั้งหมด" เพื่อกำหนดสถานะให้กับนักเรียนที่ยังไม่ได้เช็คชื่อทั้งหมดพร้อมกัน</p>
                </div>

                <div class="help-section">
                    <h4><i class="fas fa-key"></i> การใช้รหัส PIN</h4>
                    <p>คลิกปุ่ม "สร้างรหัส PIN" เพื่อสร้างรหัส 4 หลักให้นักเรียนใช้เช็คชื่อผ่านแอปนักเรียน</p>
                    <p>PIN จะมีอายุการใช้งาน 10 นาที</p>
                </div>

                <div class="help-section">
                    <h4><i class="fas fa-qrcode"></i> การสแกน QR Code</h4>
                    <p>คลิกปุ่ม "สแกน QR Code" เพื่อเปิดกล้องสำหรับสแกน QR Code ของนักเรียน</p>
                    <p>หลังจากสแกนสำเร็จ ระบบจะเช็คชื่อนักเรียนโดยอัตโนมัติ</p>
                </div>

                <div class="help-section">
                    <h4><i class="fas fa-edit"></i> การแก้ไขการเช็คชื่อ</h4>
                    <p>คลิกที่ชื่อนักเรียนในแท็บ "เช็คชื่อแล้ว" เพื่อแก้ไขสถานะการเช็คชื่อ</p>
                </div>

                <div class="help-section">
                    <h4><i class="fas fa-save"></i> การบันทึกข้อมูล</h4>
                    <p>คลิกปุ่มบันทึก (ไอคอนดิสก์) ที่ด้านล่างขวาของหน้าจอเพื่อบันทึกการเช็คชื่อทั้งหมด</p>
                    <p>นักเรียนที่ยังไม่ได้เช็คชื่อจะถูกบันทึกเป็น "ขาด" โดยอัตโนมัติ</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn primary" onclick="if (typeof closeModal === 'function') { closeModal('helpModal'); } else { document.getElementById('helpModal').classList.remove('active'); }">เข้าใจแล้ว</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ค่าตัวแปรสำหรับใช้ใน JavaScript
    const currentClassId = <?php echo $current_class_id; ?>;
    const checkDate = '<?php echo $check_date; ?>';
    const isRetroactive = <?php echo $is_retroactive ? 'true' : 'false'; ?>;
    const teacherId = <?php echo $teacher_id; ?>;
    const totalStudents = <?php echo $total_students; ?>;
    const notCheckedCount = <?php echo $not_checked; ?>;
</script>


<!-- เพิ่มในส่วนท้ายของไฟล์ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // นับจำนวนนักเรียนที่ยังไม่ได้เช็คชื่อและที่เช็คแล้ว
    updateStudentCounts();
    
    // จัดการแท็บ
    setupTabSystem();
});

/**
 * จัดการระบบแท็บ
 */
function setupTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ลบคลาส active จากทุกปุ่มและแท็บ
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // เพิ่มคลาส active ให้ปุ่มที่คลิกและแท็บที่เกี่ยวข้อง
            this.classList.add('active');
            
            const tabId = this.getAttribute('data-tab');
            const tabPane = document.getElementById(tabId + 'Tab');
            if (tabPane) {
                tabPane.classList.add('active');
            }
        });
    });
}

/**
 * แสดงรายละเอียดการเช็คชื่อ (Modal)
 * @param {number} studentId รหัสนักเรียน
 * @param {string} studentName ชื่อนักเรียน
 */
function showDetailAttendanceModal(studentId, studentName) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }
    
    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }
    
    // ระบุว่าเป็นการเพิ่มใหม่ ไม่ใช่การแก้ไข
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '0';
    }
    
    // รีเซ็ตค่า attendance_id
    const attendanceIdInput = document.getElementById('attendanceIdDetail');
    if (attendanceIdInput) {
        attendanceIdInput.value = '';
    }
    
    // รีเซ็ตค่าตัวเลือกเป็น "มาเรียน"
    const presentOption = document.querySelector('input[name="attendanceStatus"][value="present"]');
    if (presentOption) {
        presentOption.checked = true;
    }
    
    // รีเซ็ตค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = '';
    }
    
    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        remarksContainer.style.display = 'none';
    }
    
    // แสดง Modal
    document.getElementById('attendanceDetailModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
</script>




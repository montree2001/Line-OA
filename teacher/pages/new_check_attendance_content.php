<?php
/**
 * new_check_attendance_content.php - เนื้อหาของหน้าเช็คชื่อนักเรียนรูปแบบใหม่
 * 
 * ปรับปรุงให้ใช้ PHP ในการบันทึกข้อมูลโดยตรง ไม่ต้องใช้ API
 */

// แสดงข้อความแจ้งเตือนถ้ามี
if (isset($_SESSION['error'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . htmlspecialchars($_SESSION['error']) . "', 'error');
        });
    </script>";
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . htmlspecialchars($_SESSION['success']) . "', 'success');
        });
    </script>";
    unset($_SESSION['success']);
}
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
        <a href="api/download_report.php?class_id=<?php echo $current_class_id; ?>&date=<?php echo $check_date; ?>" target="_blank">
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
        <form method="post" action="">
            <input type="hidden" name="action" value="create_pin">
            <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
            <button type="submit" class="btn primary">
                <i class="fas fa-key"></i> สร้างรหัส PIN
            </button>
        </form>
        <button type="button" class="btn secondary" onclick="showModal('qrModal')">
            <i class="fas fa-qrcode"></i> สแกน QR Code
        </button>
        <button type="button" class="btn success" onclick="showMarkAllModal()">
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

                            <!-- ปุ่มเช็คชื่อในรายการนักเรียน -->
                            <div class="student-actions">
                                <!-- Form สำหรับเช็คชื่อ "มา" -->
                                <form method="post" action="" style="display: inline-block;">
                                    <input type="hidden" name="action" value="mark_attendance">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <input type="hidden" name="status" value="present">
                                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                                    <input type="hidden" name="date" value="<?php echo $check_date; ?>">
                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                                    <input type="hidden" name="is_retroactive" value="<?php echo $is_retroactive ? '1' : '0'; ?>">
                                    <?php if ($is_retroactive): ?>
                                        <input type="hidden" name="retroactive_note" value="เช็คชื่อย้อนหลังโดยครู">
                                    <?php endif; ?>
                                    <button type="submit" class="action-btn present" title="มาเรียน">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                
                                <!-- Form สำหรับเช็คชื่อ "ขาด" -->
                                <form method="post" action="" style="display: inline-block;">
                                    <input type="hidden" name="action" value="mark_attendance">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <input type="hidden" name="status" value="absent">
                                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                                    <input type="hidden" name="date" value="<?php echo $check_date; ?>">
                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                                    <input type="hidden" name="is_retroactive" value="<?php echo $is_retroactive ? '1' : '0'; ?>">
                                    <?php if ($is_retroactive): ?>
                                        <input type="hidden" name="retroactive_note" value="เช็คชื่อย้อนหลังโดยครู">
                                    <?php endif; ?>
                                    <button type="submit" class="action-btn absent" title="ขาดเรียน">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                
                                <!-- ปุ่มเช็คละเอียด (สาย/ลา) -->
                                <button type="button" class="action-btn more" title="เช็คแบบละเอียด" onclick="showDetailAttendanceModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')">
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

                            <div class="student-info" onclick="editAttendanceModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', <?php echo $student['attendance_id'] ?: 'null'; ?>)">
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

<!-- Modal สร้าง PIN -->
<div class="modal" id="pinModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">สร้างรหัส PIN สำหรับเช็คชื่อ</h3>
                <button type="button" class="close-btn" onclick="closeModal('pinModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="pin-display">
                    <?php if (isset($_SESSION['pin_data'])): ?>
                        <?php foreach (str_split($_SESSION['pin_data']['pin_code']) as $digit): ?>
                            <span class="pin-digit"><?php echo $digit; ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="pin-digit">-</span>
                        <span class="pin-digit">-</span>
                        <span class="pin-digit">-</span>
                        <span class="pin-digit">-</span>
                    <?php endif; ?>
                </div>
                <p class="pin-expire">รหัส PIN จะหมดอายุใน 
                    <span id="expireTime">
                        <?php echo isset($_SESSION['pin_data']) ? $_SESSION['pin_data']['expire_minutes'] : '10'; ?>
                    </span> นาที
                </p>
                <p class="class-detail"><?php echo $current_class['name']; ?></p>
                <?php if (isset($_SESSION['pin_data'])): ?>
                    <p class="pin-valid-until">
                        หมดอายุเวลา: <?php echo date('H:i น.', strtotime($_SESSION['pin_data']['valid_until'])); ?>
                    </p>
                    <?php unset($_SESSION['pin_data']); ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="closeModal('pinModal')">ปิด</button>
                <form method="post" action="" style="display: inline-block;">
                    <input type="hidden" name="action" value="create_pin">
                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                    <button type="submit" class="btn primary">สร้างใหม่</button>
                </form>
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
                <button type="button" class="close-btn" onclick="closeModal('qrModal')">
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
                
                <!-- เพิ่มฟอร์มสำหรับส่งข้อมูล QR Code ไปยัง PHP -->
                <form id="qrForm" method="post" action="">
                    <input type="hidden" name="action" value="scan_qr">
                    <input type="hidden" name="qr_data" id="qrDataInput" value="">
                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                    <input type="hidden" name="date" value="<?php echo $check_date; ?>">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                </form>
                
                <?php if (isset($_SESSION['qr_scan_result'])): ?>
                    <div class="qr-scan-result <?php echo $_SESSION['qr_scan_result']['status']; ?>">
                        <p><?php echo $_SESSION['qr_scan_result']['message']; ?></p>
                        <?php if ($_SESSION['qr_scan_result']['status'] === 'success'): ?>
                            <div class="student-info-display">
                                <strong>รหัสนักเรียน:</strong> <?php echo $_SESSION['qr_scan_result']['student_code']; ?><br>
                                <strong>ชื่อ-นามสกุล:</strong> <?php echo $_SESSION['qr_scan_result']['student_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php unset($_SESSION['qr_scan_result']); ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary" onclick="closeModal('qrModal')">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อละเอียด (เพิ่มใหม่/แก้ไข) -->
<div class="modal" id="attendanceDetailModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title-text">เช็คชื่อนักเรียน</h3>
                <button type="button" class="close-btn" onclick="closeModal('attendanceDetailModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="attendanceDetailForm" method="post" action="">
                    <input type="hidden" name="action" value="mark_attendance">
                    <input type="hidden" name="student_id" id="student_id_input" value="">
                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                    <input type="hidden" name="date" value="<?php echo $check_date; ?>">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                    <input type="hidden" name="attendance_id" id="attendance_id_input" value="">
                    <input type="hidden" name="is_retroactive" value="<?php echo $is_retroactive ? '1' : '0'; ?>">
                    
                    <h4 id="studentNameDetail" class="student-detail-name"></h4>

                    <div class="status-options">
                        <label class="status-option">
                            <input type="radio" name="status" value="present" checked>
                            <span class="status-label present">
                                <i class="fas fa-check-circle"></i> มาเรียน
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="late">
                            <span class="status-label late">
                                <i class="fas fa-clock"></i> มาสาย
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="leave">
                            <span class="status-label leave">
                                <i class="fas fa-clipboard"></i> ลา
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="absent">
                            <span class="status-label absent">
                                <i class="fas fa-times-circle"></i> ขาดเรียน
                            </span>
                        </label>
                    </div>

                    <div class="remarks-container" id="remarksContainer">
                        <label for="remarks">หมายเหตุ:</label>
                        <textarea name="remarks" id="remarks" placeholder="ระบุหมายเหตุ เช่น สาเหตุการมาสาย, เหตุผลการลา ฯลฯ"></textarea>
                    </div>

                    <?php if ($is_retroactive): ?>
                        <div class="retroactive-note">
                            <label for="retroactive_note">หมายเหตุการเช็คย้อนหลัง:</label>
                            <textarea name="retroactive_note" id="retroactive_note" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ">เช็คชื่อย้อนหลังโดยครู</textarea>
                        </div>
                    <?php endif; ?>
                
                    <div class="modal-footer">
                        <button type="button" class="btn secondary" onclick="closeModal('attendanceDetailModal')">ยกเลิก</button>
                        <button type="submit" class="btn primary">บันทึก</button>
                    </div>
                </form>
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
                <button type="button" class="close-btn" onclick="closeModal('markAllModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="markAllForm" method="post" action="">
                    <input type="hidden" name="action" value="mark_all">
                    <input type="hidden" name="class_id" value="<?php echo $current_class_id; ?>">
                    <input type="hidden" name="date" value="<?php echo $check_date; ?>">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                    <input type="hidden" name="is_retroactive" value="<?php echo $is_retroactive ? '1' : '0'; ?>">
                    <input type="hidden" name="student_ids" id="student_ids_input" value="">
                    
                    <p class="mark-all-desc">เลือกสถานะสำหรับนักเรียนที่ยังไม่ได้เช็คชื่อทั้งหมด <?php echo $not_checked; ?> คน</p>

                    <div class="status-options mark-all-options">
                        <label class="status-option">
                            <input type="radio" name="status" value="present" checked>
                            <span class="status-label present">
                                <i class="fas fa-check-circle"></i> เช็คเป็น "มาเรียน" ทั้งหมด
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="late">
                            <span class="status-label late">
                                <i class="fas fa-clock"></i> เช็คเป็น "มาสาย" ทั้งหมด
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="leave">
                            <span class="status-label leave">
                                <i class="fas fa-clipboard"></i> เช็คเป็น "ลา" ทั้งหมด
                            </span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="absent">
                            <span class="status-label absent">
                                <i class="fas fa-times-circle"></i> เช็คเป็น "ขาดเรียน" ทั้งหมด
                            </span>
                        </label>
                    </div>

                    <div class="remarks-container" id="markAllRemarksContainer">
                        <label for="markAllRemarks">หมายเหตุ (ถ้ามี):</label>
                        <textarea name="remarks" id="markAllRemarks" placeholder="ระบุหมายเหตุ (ใช้กับสถานะมาสาย และลา)"></textarea>
                    </div>

                    <?php if ($is_retroactive): ?>
                        <div class="retroactive-note">
                            <label for="markAllRetroactiveNote">หมายเหตุการเช็คย้อนหลัง:</label>
                            <textarea name="retroactive_note" id="markAllRetroactiveNote" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ">เช็คชื่อย้อนหลังโดยครู</textarea>
                        </div>
                    <?php endif; ?>
                
                    <div class="modal-footer">
                        <button type="button" class="btn secondary" onclick="closeModal('markAllModal')">ยกเลิก</button>
                        <button type="submit" class="btn primary">เช็คชื่อทั้งหมด</button>
                    </div>
                </form>
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
                <button type="button" class="close-btn" onclick="closeModal('helpModal')">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn primary" onclick="closeModal('helpModal')">เข้าใจแล้ว</button>
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

// แสดง PIN Modal ถ้ามีการระบุใน URL
<?php if (isset($_GET['show_pin']) && $_GET['show_pin'] == 1): ?>
document.addEventListener('DOMContentLoaded', function() {
    showModal('pinModal');
});
<?php endif; ?>

// แสดงผลการสแกน QR Code
<?php if (isset($_GET['show_qr_result']) && $_GET['show_qr_result'] == 1): ?>
document.addEventListener('DOMContentLoaded', function() {
    showModal('qrModal');
});
<?php endif; ?>

// ฟังก์ชั่นเปลี่ยนห้องเรียน
function changeClass(classId) {
    window.location.href = `new_check_attendance.php?class_id=${classId}&date=${checkDate}`;
}

// ฟังก์ชั่นเปลี่ยนวันที่
function changeDate(date) {
    window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${date}`;
}

// ฟังก์ชั่นค้นหานักเรียน
function searchStudents() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.toLowerCase();
    
    // ค้นหาในทั้งสองแท็บ
    searchInTab('waitingTab', searchTerm);
    searchInTab('checkedTab', searchTerm);
}

// ค้นหาในแท็บที่กำหนด
function searchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;
    
    const studentCards = tab.querySelectorAll('.student-card');
    
    studentCards.forEach(card => {
        const name = card.getAttribute('data-name')?.toLowerCase() || '';
        
        if (name.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ฟังก์ชั่นแสดง Modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// ฟังก์ชั่นปิด Modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ฟังก์ชั่นแสดง Modal สำหรับเช็คชื่อละเอียด
function showDetailAttendanceModal(studentId, studentName) {
    // กำหนดชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }
    
    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('student_id_input');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }
    
    // รีเซ็ตค่า attendance_id
    const attendanceIdInput = document.getElementById('attendance_id_input');
    if (attendanceIdInput) {
        attendanceIdInput.value = '';
    }
    
    // รีเซ็ตค่าตัวเลือกเป็น "มาเรียน"
    const presentOption = document.querySelector('input[name="status"][value="present"]');
    if (presentOption) {
        presentOption.checked = true;
    }
    
    // รีเซ็ตค่าหมายเหตุ
    const remarksInput = document.getElementById('remarks');
    if (remarksInput) {
        remarksInput.value = '';
    }
    
    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        remarksContainer.style.display = 'none';
    }
    
    // เปลี่ยนหัวข้อ Modal
    const modalTitle = document.getElementById('modal-title-text');
    if (modalTitle) {
        modalTitle.textContent = 'เช็คชื่อนักเรียน';
    }
    
    // แสดง Modal
    showModal('attendanceDetailModal');
}

// ฟังก์ชั่นแสดง Modal สำหรับแก้ไขการเช็คชื่อ
function editAttendanceModal(studentId, studentName, status, remarks, attendanceId) {
    // กำหนดชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }
    
    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('student_id_input');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }
    
    // กำหนดค่า attendance_id
    const attendanceIdInput = document.getElementById('attendance_id_input');
    if (attendanceIdInput && attendanceId) {
        attendanceIdInput.value = attendanceId;
    }
    
    // เลือกสถานะปัจจุบัน
    const statusOption = document.querySelector(`input[name="status"][value="${status}"]`);
    if (statusOption) {
        statusOption.checked = true;
    }
    
    // ใส่ค่าหมายเหตุ
    const remarksInput = document.getElementById('remarks');
    if (remarksInput) {
        remarksInput.value = remarks || '';
    }
    
    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        if (status === 'late' || status === 'leave') {
            remarksContainer.style.display = 'block';
        } else {
            remarksContainer.style.display = 'none';
        }
    }
    
    // เปลี่ยนหัวข้อ Modal
    const modalTitle = document.getElementById('modal-title-text');
    if (modalTitle) {
        modalTitle.textContent = 'แก้ไขการเช็คชื่อนักเรียน';
    }
    
    // แสดง Modal
    showModal('attendanceDetailModal');
}

// ฟังก์ชั่นแสดง Modal สำหรับเช็คชื่อทั้งหมด
function showMarkAllModal() {
    // รวบรวมรายการ ID นักเรียนที่ยังไม่ได้เช็คชื่อ
    const uncheckedStudents = document.querySelectorAll('#waitingTab .student-card');
    const studentIds = [];
    
    uncheckedStudents.forEach(card => {
        const studentId = card.getAttribute('data-id');
        if (studentId) {
            studentIds.push(studentId);
        }
    });
    
    // ถ้าไม่มีนักเรียนที่ต้องเช็คชื่อ
    if (studentIds.length === 0) {
        showNotification('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }
    
    // บันทึกรายการ ID ลงในฟอร์ม
    const studentIdsInput = document.getElementById('student_ids_input');
    if (studentIdsInput) {
        studentIdsInput.value = studentIds.join(',');
    }
    
    // แสดง Modal
    showModal('markAllModal');
}

// จัดการการแสดง/ซ่อนหมายเหตุตามสถานะที่เลือก
document.addEventListener('DOMContentLoaded', function() {
    // สำหรับ Modal รายบุคคล
    const statusRadios = document.querySelectorAll('input[name="status"]');
    const remarksContainer = document.getElementById('remarksContainer');
    
    if (statusRadios.length > 0 && remarksContainer) {
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'late' || this.value === 'leave') {
                    remarksContainer.style.display = 'block';
                } else {
                    remarksContainer.style.display = 'none';
                }
            });
        });
    }
    
    // สำหรับ Modal เช็คชื่อทั้งหมด
    const statusRadiosAll = document.querySelectorAll('#markAllForm input[name="status"]');
    const remarksContainerAll = document.getElementById('markAllRemarksContainer');
    
    if (statusRadiosAll.length > 0 && remarksContainerAll) {
        statusRadiosAll.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'late' || this.value === 'leave') {
                    remarksContainerAll.style.display = 'block';
                } else {
                    remarksContainerAll.style.display = 'none';
                }
            });
        });
        
        // ตรวจสอบค่าเริ่มต้น
        const checkedStatus = document.querySelector('#markAllForm input[name="status"]:checked');
        if (checkedStatus) {
            if (checkedStatus.value === 'late' || checkedStatus.value === 'leave') {
                remarksContainerAll.style.display = 'block';
            } else {
                remarksContainerAll.style.display = 'none';
            }
        }
    }
    
    // จัดการแท็บ
    setupTabSystem();
});

// จัดการระบบแท็บ
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

// แสดงข้อความแจ้งเตือน
function showNotification(message, type = 'info') {
    // สร้างแถบแจ้งเตือน
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // กำหนดไอคอนตามประเภท
    let icon = '';
    switch (type) {
        case 'success': icon = 'check-circle'; break;
        case 'warning': icon = 'exclamation-triangle'; break;
        case 'error': icon = 'exclamation-circle'; break;
        case 'info': default: icon = 'info-circle'; break;
    }
    
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    // เพิ่มไปยัง body
    document.body.appendChild(notification);
    
    // กำหนดการปิดเมื่อคลิก
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.remove();
        });
    }
    
    // กำหนดการปิดอัตโนมัติ
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.remove();
        }
    }, 5000);
}

// เปิด/ปิดเมนูเพิ่มเติม
function toggleOptions() {
    const optionsMenu = document.getElementById('optionsMenu');
    if (optionsMenu) {
        optionsMenu.classList.toggle('active');
    }
    
    // ปิดเมนูเมื่อคลิกที่อื่น
    document.addEventListener('click', function(e) {
        if (optionsMenu && !optionsMenu.contains(e.target) && !e.target.closest('.header-icon')) {
            optionsMenu.classList.remove('active');
        }
    });
}
</script>

<?php require_once 'templates/footer.php'; ?>
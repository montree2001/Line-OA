<div class="header">
    <div class="app-name">น้องชูใจ AI</div>
    <div class="header-icons">
        <span class="material-icons" id="notification-icon">notifications</span>
        <span class="material-icons" id="account-icon">account_circle</span>
    </div>
</div>

<div class="container">
    <?php if (empty($teacher_classes)): ?>
    <!-- แสดงข้อความเมื่อไม่มีห้องเรียนที่เป็นที่ปรึกษา -->
    <div class="alert-card warning">
        <div class="alert-icon">
            <span class="material-icons">info</span>
        </div>
        <div class="alert-content">
            <div class="alert-title">ไม่พบข้อมูลชั้นเรียนที่ปรึกษา</div>
            <div class="alert-message">คุณยังไม่ได้รับมอบหมายให้เป็นครูที่ปรึกษาห้องใด กรุณาติดต่อผู้ดูแลระบบ</div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- ตัวเลือกห้องเรียน -->
    <div class="class-selector">
        <label for="class-select">ห้องเรียนที่ปรึกษา:</label>
        <select id="class-select" onchange="changeClass(this.value)">
            <?php foreach ($teacher_classes as $class): ?>
                <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $current_class_id) ? 'selected' : ''; ?>>
                    <?php echo $class['name']; ?> (<?php echo $class['total_students']; ?> คน)
                    <?php echo $class['is_primary'] ? ' - ที่ปรึกษาหลัก' : ''; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($current_class): ?>
    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-card">
        <div class="class-details">
            <h2><?php echo $current_class['name']; ?></h2>
            <p>นักเรียน <?php echo $current_class['total_students']; ?> คน</p>
            <p>อัตราการเข้าแถวเฉลี่ย <?php echo $current_class['attendance_rate']; ?>%</p>
        </div>
        <div class="date-info">
            <p><?php echo date('l', strtotime('today')); ?></p>
            <p><?php echo date('j M Y'); ?></p>
            <p><?php echo date('H:i'); ?> น.</p>
        </div>
    </div>

    <!-- สรุปการเข้าแถววันนี้ -->
    <div class="section-title">
        <span class="material-icons">today</span>
        <h3>สรุปการเข้าแถวกิจกรรมหน้าเสาธงวันนี้</h3>
    </div>

    <!-- สรุปการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card total">
            <div class="value"><?php echo $current_class['total_students']; ?></div>
            <div class="label">ทั้งหมด</div>
        </div>
        <div class="stat-card present">
            <div class="value"><?php echo $current_class['present_today']; ?></div>
            <div class="label">มาเข้าแถว</div>
        </div>
        <div class="stat-card absent">
            <div class="value"><?php echo $current_class['absent_today']; ?></div>
            <div class="label">ขาดเข้าแถว</div>
        </div>
        <div class="stat-card not-checked">
            <div class="value"><?php echo $current_class['not_checked']; ?></div>
            <div class="label">ยังไม่เช็ค</div>
        </div>
    </div>

    <!-- การ์ดปุ่มทางลัด -->
    <div class="action-cards">
        <!-- ปุ่มสร้าง PIN -->
        <div class="action-card" onclick="generatePin()">
            <div class="action-icon">
                <span class="material-icons">pin</span>
            </div>
            <div class="action-text">
                <div class="action-title">สร้างรหัส PIN</div>
                <div class="action-subtitle">สำหรับนักเรียนเช็คชื่อเข้าแถว</div>
            </div>
        </div>

        <!-- ปุ่มสแกน QR -->
        <div class="action-card" onclick="scanQRCode()">
            <div class="action-icon orange">
                <span class="material-icons">qr_code_scanner</span>
            </div>
            <div class="action-text">
                <div class="action-title">สแกน QR Code</div>
                <div class="action-subtitle">สแกนเช็คชื่อเข้าแถวนักเรียน</div>
            </div>
        </div>
    </div>

    <!-- แสดง PIN ที่ใช้งานอยู่ (ถ้ามี) -->
    <?php if ($active_pin): ?>
    <div class="active-pin-card">
        <h3>รหัส PIN สำหรับเช็คชื่อเข้าแถว</h3>
        <div class="active-pin" id="active-pin-code"><?php echo $active_pin['code']; ?></div>
        <div class="pin-expire">หมดอายุในอีก <span id="pin-expire-time"><?php echo $active_pin['expire_in_minutes']; ?></span> นาที</div>
    </div>
    <?php endif; ?>

    <!-- การดำเนินการหลัก -->
    <div class="main-actions">
        <!-- ปุ่มเช็คชื่อ -->
        <a href="check-attendance.php?class_id=<?php echo $current_class_id; ?>" class="main-action-btn check">
            <span class="material-icons">how_to_reg</span>
            <span>เช็คชื่อนักเรียนเข้าแถว</span>
        </a>
        
        <!-- ปุ่มรายงาน -->
        <a href="reports.php?class_id=<?php echo $current_class_id; ?>" class="main-action-btn report">
            <span class="material-icons">assessment</span>
            <span>รายงานการเข้าแถวกิจกรรม</span>
        </a>
    </div>

    <!-- แท็บเนื้อหา -->
    <div class="content-tabs">
        <div class="tab-header">
            <button class="tab-btn active" onclick="showTab('attendance')">การเข้าแถว</button>
            <button class="tab-btn" onclick="showTab('at-risk')">เสี่ยงตกกิจกรรม <span class="badge"><?php echo $current_class['at_risk_count']; ?></span></button>
            <button class="tab-btn" onclick="showTab('announcements')">ประกาศ</button>
        </div>
        
        <!-- แท็บการเข้าแถว -->
        <div id="attendance-tab" class="tab-content active">
            <div class="tab-content-header">
                <h3>รายการเช็คชื่อเข้าแถวล่าสุด</h3>
                <a href="check-attendance.php?class_id=<?php echo $current_class_id; ?>" class="view-all">ดูทั้งหมด</a>
            </div>
            
            <div class="student-list">
                <?php if (empty($students_summary)): ?>
                    <div class="empty-state">
                        <span class="material-icons">schedule</span>
                        <p>ยังไม่มีการเช็คชื่อเข้าแถวในวันนี้</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($students_summary as $student): ?>
                    <div class="student-row">
                        <div class="student-info">
                            <span class="student-number"><?php echo $student['number']; ?></span>
                            <span class="student-name"><?php echo $student['name']; ?></span>
                        </div>
                        <div class="student-status <?php echo $student['status']; ?>">
                            <?php if ($student['status'] === 'present'): ?>
                                <span class="material-icons">check_circle</span> มา
                            <?php else: ?>
                                <span class="material-icons">cancel</span> ขาด
                            <?php endif; ?>
                        </div>
                        <div class="attendance-time"><?php echo $student['time']; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- แท็บนักเรียนเสี่ยงตก -->
        <div id="at-risk-tab" class="tab-content">
            <div class="tab-content-header">
                <h3>นักเรียนเสี่ยงตกกิจกรรมเข้าแถว</h3>
                <a href="at-risk.php?class_id=<?php echo $current_class_id; ?>" class="view-all">ดูทั้งหมด</a>
            </div>
            
            <?php if (empty($at_risk_students)): ?>
                <div class="empty-state">
                    <span class="material-icons">sentiment_very_satisfied</span>
                    <p>ไม่มีนักเรียนเสี่ยงตกกิจกรรมเข้าแถว</p>
                </div>
            <?php else: ?>
                <div class="at-risk-list">
                    <?php foreach ($at_risk_students as $student): ?>
                    <div class="at-risk-card">
                        <div class="at-risk-header">
                            <div class="student-avatar"><?php echo mb_substr($student['name'], 0, 1, 'UTF-8'); ?></div>
                            <div class="student-details">
                                <div class="student-name"><?php echo $student['name']; ?></div>
                                <div class="student-class">เลขที่ <?php echo $student['number']; ?></div>
                            </div>
                            <div class="risk-indicator">
                                <div class="risk-percentage"><?php echo $student['attendance_rate']; ?>%</div>
                                <div class="risk-label">การเข้าแถว</div>
                            </div>
                        </div>
                        <div class="at-risk-body">
                            <div class="risk-detail">
                                <span class="material-icons">event_busy</span>
                                <span>ขาดแถว <?php echo $student['absent_days']; ?> วัน</span>
                            </div>
                            <div class="risk-detail">
                                <span class="material-icons">calendar_today</span>
                                <span>ขาดล่าสุด: <?php echo $student['last_absent']; ?></span>
                            </div>
                        </div>
                        <div class="at-risk-actions">
                            <button class="action-btn" onclick="viewStudentDetail(<?php echo $student['id']; ?>)">
                                <span class="material-icons">visibility</span> ดูประวัติ
                            </button>
                            <button class="action-btn" onclick="notifyParent(<?php echo $student['id']; ?>)">
                                <span class="material-icons">notification_important</span> แจ้งผู้ปกครอง
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- แท็บประกาศ -->
        <div id="announcements-tab" class="tab-content">
            <div class="tab-content-header">
                <h3>ประกาศล่าสุด</h3>
                <a href="announcements.php" class="view-all">ดูทั้งหมด</a>
            </div>
            
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <span class="material-icons">campaign</span>
                    <p>ไม่มีประกาศล่าสุด</p>
                </div>
            <?php else: ?>
                <div class="announcements-list">
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h4><?php echo $announcement['title']; ?></h4>
                            <span class="announcement-date"><?php echo $announcement['date']; ?></span>
                        </div>
                        <div class="announcement-body">
                            <p><?php echo $announcement['content']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?> <!-- End of current_class check -->
    <?php endif; ?> <!-- End of teacher_classes check -->
</div>

<!-- Modal สร้าง PIN -->
<div class="modal" id="pin-modal">
    <div class="modal-content">
        <div class="modal-title">สร้างรหัส PIN สำหรับการเช็คชื่อเข้าแถว</div>
        <div class="modal-subtitle"><?php echo $current_class ? $current_class['name'] : ''; ?></div>
        <div class="pin-code"><?php echo $active_pin ? $active_pin['code'] : '????'; ?></div>
        <p class="pin-info">รหัส PIN นี้จะหมดอายุใน 10 นาที</p>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('pin-modal')">ปิด</button>
            <button class="modal-button confirm" onclick="generateNewPin()">สร้างใหม่</button>
        </div>
    </div>
</div>

<!-- Modal สแกน QR Code -->
<div class="modal" id="qr-modal">
    <div class="modal-content">
        <div class="modal-title">สแกน QR Code นักเรียน</div>
        <div class="modal-subtitle"><?php echo $current_class ? $current_class['name'] : ''; ?></div>
        <div class="qr-scanner-placeholder">
            <span class="material-icons">qr_code_scanner</span>
            <p>กรุณาอนุญาตการใช้งานกล้อง</p>
        </div>
        <div class="modal-buttons">
            <button class="modal-button cancel" onclick="closeModal('qr-modal')">ยกเลิก</button>
        </div>
    </div>
</div>
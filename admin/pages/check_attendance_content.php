<?php
// Verificar si hay mensajes de éxito o error para mostrar
$alert_success = $save_success ?? false;
$alert_error = $save_error ?? false;
?>

<!-- Alertas para mostrar mensajes de éxito o error -->
<?php if ($alert_success): ?>
<div class="alert alert-success" id="success-alert">
    <span class="material-icons">check_circle</span>
    <div class="alert-message">บันทึกการเช็คชื่อเรียบร้อยแล้ว</div>
    <button class="alert-close" onclick="this.parentElement.style.display='none'">
        <span class="material-icons">close</span>
    </button>
</div>
<?php endif; ?>

<?php if ($alert_error): ?>
<div class="alert alert-error" id="error-alert">
    <span class="material-icons">error</span>
    <div class="alert-message">เกิดข้อผิดพลาด: <?php echo htmlspecialchars($error_message ?? 'ไม่สามารถบันทึกข้อมูลได้'); ?></div>
    <button class="alert-close" onclick="this.parentElement.style.display='none'">
        <span class="material-icons">close</span>
    </button>
</div>
<?php endif; ?>

<!-- แท็บสำหรับการเช็คชื่อนักเรียน -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'qr-code' ? 'active' : ''; ?>" data-tab="qr-code">สแกน QR Code</div>
        <div class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'pin-code' ? 'active' : ''; ?>" data-tab="pin-code">รหัส PIN</div>
        <div class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'manual' ? 'active' : ''; ?>" data-tab="manual">เช็คชื่อด้วยตนเอง</div>
        <div class="tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'gps' ? 'active' : ''; ?>" data-tab="gps">ตรวจสอบ GPS</div>
    </div>
</div>

<!-- แท็บการสแกน QR Code -->
<div id="qr-code-tab" class="tab-content <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'qr-code' ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">qr_code_scanner</span>
            เช็คชื่อด้วย QR Code
        </div>
        
        <div class="camera-container">
            <!-- ใช้ HTML5 QR Scanner -->
            <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
            
            <!-- สำรองใช้กล้องแบบทั่วไปถ้า HTML5 QR Scanner ไม่ทำงาน -->
            <div class="camera-preview" id="camera-fallback" style="display: none;">
                <div id="camera-placeholder" class="camera-placeholder">
                    <span class="material-icons">videocam</span>
                    <p>กรุณากดปุ่ม "เปิดกล้อง" เพื่อสแกน QR Code</p>
                </div>
                <video id="qr-video" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"></video>
                <div class="scanner-border"></div>
            </div>
            
            <div class="camera-controls">
                <button class="btn btn-primary" onclick="startCamera()">
                    <span class="material-icons">videocam</span>
                    เปิดกล้อง
                </button>
                <button class="btn btn-secondary" onclick="toggleFlash()">
                    <span class="material-icons">flash_on</span>
                    เปิด/ปิดแฟลช
                </button>
            </div>
        </div>
        
        <div class="scan-results">
            <h3>ผลการสแกนล่าสุด</h3>
            <div id="scan-result-empty" class="scan-result-empty">ยังไม่มีการสแกน QR Code</div>
            
            <div id="scan-result-container"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">
            <span class="material-icons">history</span>
            ประวัติการสแกนล่าสุด
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>เวลา</th>
                        <th>นักเรียน</th>
                        <th>ชั้น/ห้อง</th>
                        <th>วิธีการเช็คชื่อ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody id="scan-history">
                    <?php 
                    // แสดงประวัติการเช็คชื่อล่าสุด
                    // ในกรณีเริ่มต้นให้ดึงข้อมูลการเช็คชื่อวันนี้
                    try {
                        $stmt = $conn->prepare("
                            SELECT a.attendance_id, a.check_time, a.attendance_status, a.check_method,
                                   s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                                   c.level, c.group_number, d.department_name
                            FROM attendance a
                            JOIN students s ON a.student_id = s.student_id
                            JOIN users u ON s.user_id = u.user_id
                            LEFT JOIN classes c ON s.current_class_id = c.class_id
                            LEFT JOIN departments d ON c.department_id = d.department_id
                            WHERE a.date = CURRENT_DATE()
                            ORDER BY a.check_time DESC
                            LIMIT 10
                        ");
                        $stmt->execute();
                        $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($recent_attendance as $record): 
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($record['attendance_status']) {
                                case 'present':
                                    $status_class = 'success';
                                    $status_text = 'มาเรียน';
                                    break;
                                case 'late':
                                    $status_class = 'warning';
                                    $status_text = 'มาสาย';
                                    break;
                                case 'absent':
                                    $status_class = 'danger';
                                    $status_text = 'ขาดเรียน';
                                    break;
                                case 'leave':
                                    $status_class = 'info';
                                    $status_text = 'ลา';
                                    break;
                            }
                            
                            $check_method = '';
                            switch ($record['check_method']) {
                                case 'QR_Code':
                                    $check_method = 'QR Code';
                                    break;
                                case 'PIN':
                                    $check_method = 'รหัส PIN';
                                    break;
                                case 'GPS':
                                    $check_method = 'GPS';
                                    break;
                                case 'Manual':
                                    $check_method = 'เช็คชื่อด้วยตนเอง';
                                    break;
                            }
                    ?>
                    <tr>
                        <td><?php echo date('H:i', strtotime($record['check_time'])); ?></td>
                        <td><?php echo $record['title'] . $record['first_name'] . ' ' . $record['last_name']; ?></td>
                        <td><?php echo $record['level'] . '/' . $record['group_number'] . ' ' . $record['department_name']; ?></td>
                        <td><?php echo $check_method; ?></td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    </tr>
                    <?php 
                        endforeach;
                        
                        if (count($recent_attendance) === 0): 
                    ?>
                    <tr>
                        <td colspan="5" class="text-center">ไม่พบข้อมูลการเช็คชื่อวันนี้</td>
                    </tr>
                    <?php 
                        endif;
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="5" class="text-center">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- แท็บรหัส PIN -->
<div id="pin-code-tab" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'pin-code' ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">pin</span>
            เช็คชื่อด้วยรหัส PIN
        </div>
        
        <!-- ตัวสร้างรหัส PIN -->
        <div class="pin-generator">
            <div class="pin-display">
                <div class="pin-code" id="currentPin"><?php echo $current_pin ?? '----'; ?></div>
                <div class="pin-timer">
                    <span class="material-icons">timer</span>
                    <span id="pinTimer"><?php echo sprintf('%02d:%02d', $pin_remaining_min ?? 0, $pin_remaining_sec ?? 0); ?></span> นาที
                </div>
                <?php if(!empty($pin_class_name)): ?>
                <div class="pin-class">
                    <span class="material-icons">class</span>
                    ใช้ได้กับห้อง: <?php echo htmlspecialchars($pin_class_name); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="pin-actions">
                <?php if ($user_role == 'admin'): ?>
                <div class="pin-filters">
                    <select id="pinClassFilter" class="form-control">
                        <option value="">ทุกห้องเรียน</option>
                        <?php
                        // แสดงรายการห้องเรียนสำหรับตัวกรอง
                        try {
                            $stmt = $conn->prepare("
                                SELECT c.class_id, c.level, c.group_number, d.department_name
                                FROM classes c 
                                JOIN departments d ON c.department_id = d.department_id
                                WHERE c.academic_year_id = ?
                                ORDER BY c.level, c.group_number
                            ");
                            $stmt->execute([$current_academic_year_id]);
                            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($classes as $class):
                        ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo $class['level'] . '/' . $class['group_number'] . ' ' . $class['department_name']; ?>
                        </option>
                        <?php
                            endforeach;
                        } catch (PDOException $e) {
                            // ไม่แสดงตัวเลือกเพิ่มเติมหากมีข้อผิดพลาด
                        }
                        ?>
                    </select>
                </div>
                <?php elseif ($user_role == 'teacher'): ?>
                <!-- แสดงเฉพาะห้องที่เป็นครูที่ปรึกษา -->
                <div class="pin-filters">
                    <select id="pinClassFilter" class="form-control">
                        <option value="">ทุกห้องเรียน</option>
                        <?php
                        // แสดงรายการห้องเรียนที่เป็นครูที่ปรึกษา
                        try {
                            $stmt = $conn->prepare("
                                SELECT c.class_id, c.level, c.group_number, d.department_name
                                FROM class_advisors ca
                                JOIN classes c ON ca.class_id = c.class_id
                                JOIN departments d ON c.department_id = d.department_id
                                WHERE ca.teacher_id = ? AND c.academic_year_id = ?
                                ORDER BY c.level, c.group_number
                            ");
                            $stmt->execute([$admin_info['teacher_id'], $current_academic_year_id]);
                            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($classes as $class):
                        ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo $class['level'] . '/' . $class['group_number'] . ' ' . $class['department_name']; ?>
                        </option>
                        <?php
                            endforeach;
                        } catch (PDOException $e) {
                            // ไม่แสดงตัวเลือกเพิ่มเติมหากมีข้อผิดพลาด
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <button class="btn btn-primary" onclick="generatePin()">
                    <span class="material-icons">refresh</span>
                    สร้างรหัสใหม่
                </button>
                <button class="btn btn-secondary" onclick="copyPin()">
                    <span class="material-icons">content_copy</span>
                    คัดลอกรหัส
                </button>
            </div>
        </div>
        
        <div class="pin-instruction">
            <h3>วิธีใช้งาน</h3>
            <ol>
                <li>แจ้งรหัส PIN ให้นักเรียนทราบ</li>
                <li>นักเรียนเปิดแอป STD-Prasat และเลือกเมนู "เช็คชื่อด้วยรหัส PIN"</li>
                <li>นักเรียนป้อนรหัส PIN ที่ได้รับและกดยืนยัน</li>
                <li>ระบบจะอัปเดตสถานะการเช็คชื่อโดยอัตโนมัติ</li>
            </ol>
            <p class="pin-note">หมายเหตุ: รหัส PIN จะหมดอายุภายใน <?php echo isset($pin_expiration) ? $pin_expiration : '10'; ?> นาที และสามารถใช้ได้กับนักเรียนทุกคนในระบบ<?php echo !empty($pin_class_name) && $pin_class_name != 'ทุกห้องเรียน' ? ' (เฉพาะห้อง '.$pin_class_name.')' : ''; ?></p>
        </div>
        
        <!-- รายการนักเรียนที่ใช้รหัส PIN นี้เช็คชื่อ -->
        <div class="pin-users-container">
            <h3>นักเรียนที่ใช้รหัส PIN นี้เช็คชื่อ</h3>
            
            <form method="post" action="check_attendance.php?tab=pin-code">
                <input type="hidden" name="attendance_date" value="<?php echo date('Y-m-d'); ?>">
                
                <div class="bulk-actions">
                    <button type="button" class="btn btn-secondary" onclick="checkAllStudents()">เลือกทั้งหมด</button>
                    <button type="button" class="btn btn-secondary" onclick="uncheckAllStudents()">ยกเลิกเลือกทั้งหมด</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="5%">เช็คชื่อ</th>
                                <th width="25%">ชื่อ-นามสกุล</th>
                                <th width="10%">รหัสนักเรียน</th>
                                <th width="15%">เวลามาเรียน</th>
                                <th width="20%">สถานะ</th>
                                <th width="20%">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($pin_checked_students) && !empty($pin_checked_students)): ?>
                                <?php foreach ($pin_checked_students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <input type="checkbox" name="attendance[<?php echo $student['student_id']; ?>]" value="1" checked>
                                    </td>
                                    <td><?php echo $student['title'] . $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                    <td><?php echo $student['student_code']; ?></td>
                                    <td><?php echo date('H:i', strtotime($student['check_time'])); ?></td>
                                    <td>
                                        <select class="form-control form-control-sm" name="attendance[<?php echo $student['student_id']; ?>][status]">
                                            <option value="present" <?php echo $student['attendance_status'] == 'present' ? 'selected' : ''; ?>>มาเรียน</option>
                                            <option value="late" <?php echo $student['attendance_status'] == 'late' ? 'selected' : ''; ?>>มาสาย</option>
                                            <option value="absent" <?php echo $student['attendance_status'] == 'absent' ? 'selected' : ''; ?>>ขาดเรียน</option>
                                            <option value="leave" <?php echo $student['attendance_status'] == 'leave' ? 'selected' : ''; ?>>ลา</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="attendance[<?php echo $student['student_id']; ?>][remarks]" placeholder="หมายเหตุ" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">ยังไม่มีนักเรียนใช้ PIN นี้เช็คชื่อ</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (isset($pin_checked_students) && !empty($pin_checked_students)): ?>
                <!-- สรุปการเช็คชื่อ -->
                <div class="attendance-summary">
                    <div class="row">
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">
                                    <?php 
                                    $present_count = 0;
                                    foreach ($pin_checked_students as $student) {
                                        if ($student['attendance_status'] == 'present') {
                                            $present_count++;
                                        }
                                    }
                                    echo $present_count;
                                    ?>
                                </div>
                                <div class="attendance-stat-label">มาเรียน</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">
                                    <?php 
                                    $late_count = 0;
                                    foreach ($pin_checked_students as $student) {
                                        if ($student['attendance_status'] == 'late') {
                                            $late_count++;
                                        }
                                    }
                                    echo $late_count;
                                    ?>
                                </div>
                                <div class="attendance-stat-label">มาสาย</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">
                                    <?php 
                                    $absent_count = 0;
                                    foreach ($pin_checked_students as $student) {
                                        if ($student['attendance_status'] == 'absent') {
                                            $absent_count++;
                                        }
                                    }
                                    echo $absent_count;
                                    ?>
                                </div>
                                <div class="attendance-stat-label">ขาดเรียน</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">
                                    <?php 
                                    $leave_count = 0;
                                    foreach ($pin_checked_students as $student) {
                                        if ($student['attendance_status'] == 'leave') {
                                            $leave_count++;
                                        }
                                    }
                                    echo $leave_count;
                                    ?>
                                </div>
                                <div class="attendance-stat-label">ลา</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary">ยกเลิก</button>
                    <button type="submit" name="save_attendance" value="1" class="btn btn-primary" onclick="return confirm('ยืนยันการบันทึกการเช็คชื่อ?')">
                        <span class="material-icons">save</span>
                        บันทึกการเช็คชื่อ
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- แท็บเช็คชื่อด้วยตนเอง -->
<div id="manual-tab" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'manual' ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">edit</span>
            เช็คชื่อด้วยตนเอง
        </div>
        
        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ระดับชั้น</div>
                <select class="form-control" id="classLevel">
                    <option value="">-- เลือกระดับชั้น --</option>
                    <?php foreach ($levels as $level): ?>
                    <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">ห้องเรียน</div>
                <select class="form-control" id="classRoom">
                    <option value="">-- เลือกห้องเรียน --</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">วันที่</div>
                <input type="date" class="form-control" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <button class="filter-button" onclick="loadClassStudents()">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>
        
        <div class="manual-attendance-form">
            <h3>รายชื่อนักเรียน</h3>
            <p>วันที่ <?php echo date('d/m/Y', strtotime('+543 years')); ?></p>
            
            <form method="post" action="check_attendance.php?tab=manual">
                <input type="hidden" name="attendance_date" value="<?php echo date('Y-m-d'); ?>">
                
                <div class="bulk-actions">
                    <button type="button" class="btn btn-secondary" onclick="checkAllStudents()">เลือกทั้งหมด</button>
                    <button type="button" class="btn btn-secondary" onclick="uncheckAllStudents()">ยกเลิกเลือกทั้งหมด</button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="5%">เช็คชื่อ</th>
                                <th width="30%">ชื่อ-นามสกุล</th>
                                <th width="10%">รหัสนักเรียน</th>
                                <th width="15%">สถานะ</th>
                                <th width="35%">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody id="student-list">
                            <tr>
                                <td colspan="6" class="text-center">กรุณาเลือกระดับชั้น ห้องเรียน และวันที่ แล้วกดค้นหา</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="attendance-summary">
                    <div class="row">
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">0</div>
                                <div class="attendance-stat-label">มาเรียน</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">0</div>
                                <div class="attendance-stat-label">มาสาย</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">0</div>
                                <div class="attendance-stat-label">ขาดเรียน</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="attendance-stat">
                                <div class="attendance-stat-value">0</div>
                                <div class="attendance-stat-label">ลา</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary">ยกเลิก</button>
                    <button type="submit" name="save_attendance" value="1" class="btn btn-primary">
                        <span class="material-icons">save</span>
                        บันทึกการเช็คชื่อ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- แท็บตรวจสอบ GPS -->
<div id="gps-tab" class="tab-content <?php echo isset($_GET['tab']) && $_GET['tab'] == 'gps' ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">location_on</span>
            ตรวจสอบตำแหน่ง GPS
        </div>
        
        <div class="gps-settings">
            <h3>ตั้งค่าพื้นที่การเช็คชื่อ</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ละติจูดของโรงเรียน</label>
                        <input type="text" class="form-control" id="schoolLatitude" value="<?php 
                            // ดึงพิกัดโรงเรียนจากการตั้งค่า
                            try {
                                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude'");
                                $stmt->execute();
                                echo $stmt->fetch(PDO::FETCH_COLUMN) ?: '13.7563';
                            } catch (PDOException $e) {
                                echo '13.7563';
                            }
                        ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ลองจิจูดของโรงเรียน</label>
                        <input type="text" class="form-control" id="schoolLongitude" value="<?php 
                            try {
                                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude'");
                                $stmt->execute();
                                echo $stmt->fetch(PDO::FETCH_COLUMN) ?: '100.5018';
                            } catch (PDOException $e) {
                                echo '100.5018';
                            }
                        ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">รัศมีพื้นที่ที่อนุญาต (เมตร)</label>
                <input type="number" class="form-control" id="allowedRadius" value="<?php 
                    try {
                        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius'");
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_COLUMN) ?: '100';
                    } catch (PDOException $e) {
                        echo '100';
                    }
                ?>" min="10" max="1000">
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" onclick="updateGpsSettings()">
                    <span class="material-icons">save</span>
                    บันทึกการตั้งค่า
                </button>
                <button class="btn btn-secondary" onclick="testGpsLocation()">
                    <span class="material-icons">my_location</span>
                    ทดสอบตำแหน่งปัจจุบัน
                </button>
            </div>
        </div>
        
        <div class="map-container">
            <div id="map" style="width: 100%; height: 400px; border-radius: 8px;"></div>
            <div class="map-info">
                <p><strong>พื้นที่ที่อนุญาต:</strong> รัศมี <span id="radius-display"><?php 
                    try {
                        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius'");
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_COLUMN) ?: '100';
                    } catch (PDOException $e) {
                        echo '100';
                    }
                ?></span> เมตรจากจุดศูนย์กลางโรงเรียน</p>
                
                <?php
                // นับจำนวนนักเรียนที่เช็คชื่อด้วย GPS วันนี้
                try {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS gps_count 
                        FROM attendance 
                        WHERE check_method = 'GPS' AND date = CURRENT_DATE()
                    ");
                    $stmt->execute();
                    $gps_count = $stmt->fetch(PDO::FETCH_ASSOC)['gps_count'] ?: 0;
                    
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS outside_count 
                        FROM attendance 
                        WHERE check_method = 'GPS' AND date = CURRENT_DATE()
                        AND (
                            location_lat IS NULL OR location_lng IS NULL OR
                            (
                                SQRT(
                                    POWER(69.1 * (location_lat - (SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude')), 2) +
                                    POWER(69.1 * ((SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude') - location_lng) * COS(location_lat / 57.3), 2)
                                ) * 1609.34 > (SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius')
                            )
                        )
                    ");
                    $stmt->execute();
                    $outside_count = $stmt->fetch(PDO::FETCH_ASSOC)['outside_count'] ?: 0;
                    
                    echo "<p><strong>จำนวนนักเรียนที่เช็คชื่อผ่าน GPS วันนี้:</strong> <span id=\"gps-count\">{$gps_count}</span> คน</p>";
                    echo "<p><strong>จำนวนนักเรียนที่อยู่นอกพื้นที่:</strong> <span id=\"outside-count\">{$outside_count}</span> คน</p>";
                } catch (PDOException $e) {
                    echo "<p><strong>จำนวนนักเรียนที่เช็คชื่อผ่าน GPS วันนี้:</strong> <span id=\"gps-count\">0</span> คน</p>";
                    echo "<p><strong>จำนวนนักเรียนที่อยู่นอกพื้นที่:</strong> <span id=\"outside-count\">0</span> คน</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="gps-attendance-list">
            <h3>นักเรียนที่เช็คชื่อผ่าน GPS วันนี้</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>นักเรียน</th>
                            <th>ชั้น/ห้อง</th>
                            <th>ระยะห่าง</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // แสดงรายการนักเรียนที่เช็คชื่อด้วย GPS วันนี้
                        try {
                            $stmt = $conn->prepare("
                                SELECT a.attendance_id, a.check_time, a.location_lat, a.location_lng, a.attendance_status,
                                       s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                                       c.level, c.group_number, d.department_name,
                                       (SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude') AS school_lat,
                                       (SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude') AS school_lng,
                                       (SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius') AS allowed_radius
                                FROM attendance a
                                JOIN students s ON a.student_id = s.student_id
                                JOIN users u ON s.user_id = u.user_id
                                LEFT JOIN classes c ON s.current_class_id = c.class_id
                                LEFT JOIN departments d ON c.department_id = d.department_id
                                WHERE a.check_method = 'GPS' AND a.date = CURRENT_DATE()
                                ORDER BY a.check_time DESC
                            ");
                            $stmt->execute();
                            $gps_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($gps_attendance as $record):
                                // คำนวณระยะห่าง
                                $distance = '?';
                                $within_radius = false;
                                
                                if ($record['location_lat'] && $record['location_lng'] && $record['school_lat'] && $record['school_lng']) {
                                    $distance = calculateDistance(
                                        $record['location_lat'], 
                                        $record['location_lng'], 
                                        $record['school_lat'], 
                                        $record['school_lng']
                                    );
                                    
                                    $within_radius = ($distance <= $record['allowed_radius']);
                                    $distance = number_format($distance, 0) . ' เมตร';
                                }
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($record['check_time'])); ?></td>
                            <td><?php echo $record['title'] . $record['first_name'] . ' ' . $record['last_name']; ?></td>
                            <td><?php echo $record['level'] . '/' . $record['group_number'] . ' ' . $record['department_name']; ?></td>
                            <td><?php echo $distance; ?></td>
                            <td>
                                <span class="status-badge <?php echo $within_radius ? 'success' : 'danger'; ?>">
                                    <?php echo $within_radius ? 'อยู่ในพื้นที่' : 'อยู่นอกพื้นที่'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="table-action-btn primary" title="ดูแผนที่" onclick="showMapModal('map-<?php echo $record['attendance_id']; ?>')">
                                    <span class="material-icons">map</span>
                                </button>
                                <?php if ($record['photo_url']): ?>
                                <button class="table-action-btn success" title="ดูรูปภาพ" onclick="showPhotoModal('photo-<?php echo $record['attendance_id']; ?>')">
                                    <span class="material-icons">photo</span>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                            
                            if (count($gps_attendance) === 0): 
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">ไม่พบข้อมูลการเช็คชื่อด้วย GPS วันนี้</td>
                        </tr>
                        <?php 
                            endif;
                        } catch (PDOException $e) {
                            echo '<tr><td colspan="6" class="text-center">เกิดข้อผิดพลาดในการดึงข้อมูล</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงแผนที่ -->
<div id="mapModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ตำแหน่ง GPS</h2>
            <span class="close" onclick="closeModal('mapModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalMapContainer"></div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงรูปภาพ -->
<div id="photoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>รูปภาพการเช็คชื่อ</h2>
            <span class="close" onclick="closeModal('photoModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-photo">
                <img src="" alt="รูปภาพการเช็คชื่อ">
            </div>
        </div>
    </div>
</div>

<!-- โมดัลดาวน์โหลดรายงาน -->
<div id="downloadReportModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ดาวน์โหลดรายงาน</h2>
            <span class="close" onclick="closeModal('downloadReportModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="report-options">
                <h3>เลือกประเภทรายงาน</h3>
                
                <form action="reports/generate_report.php" method="get" target="_blank">
                    <div class="form-group">
                        <label class="form-label">ประเภทรายงาน</label>
                        <select name="report_type" class="form-control">
                            <option value="daily">รายงานประจำวัน</option>
                            <option value="weekly">รายงานประจำสัปดาห์</option>
                            <option value="monthly">รายงานประจำเดือน</option>
                            <option value="class">รายงานตามห้องเรียน</option>
                            <option value="student">รายงานรายบุคคล</option>
                            <option value="summary">รายงานสรุป</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="date-range-group">
                        <label class="form-label">ช่วงวันที่</label>
                        <div class="date-range">
                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            <span>ถึง</span>
                            <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group" id="class-select-group">
                        <label class="form-label">ห้องเรียน</label>
                        <select name="class_id" class="form-control">
                            <option value="">ทุกห้องเรียน</option>
                            <?php
                            // แสดงรายการห้องเรียน
                            try {
                                $stmt = $conn->prepare("
                                    SELECT c.class_id, c.level, c.group_number, d.department_name
                                    FROM classes c 
                                    JOIN departments d ON c.department_id = d.department_id
                                    WHERE c.academic_year_id = ?
                                    ORDER BY c.level, c.group_number
                                ");
                                $stmt->execute([$current_academic_year_id]);
                                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($classes as $class):
                            ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo $class['level'] . '/' . $class['group_number'] . ' ' . $class['department_name']; ?>
                            </option>
                            <?php
                                endforeach;
                            } catch (PDOException $e) {
                                // ไม่แสดงตัวเลือกเพิ่มเติมหากมีข้อผิดพลาด
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="format-select-group">
                        <label class="form-label">รูปแบบไฟล์</label>
                        <select name="format" class="form-control">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('downloadReportModal')">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons">file_download</span>
                            ดาวน์โหลด
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// ฟังก์ชันคำนวณระยะห่างระหว่างพิกัด
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $dist = $dist * 60 * 1.1515 * 1.609344 * 1000; // ระยะห่างในเมตร
    return $dist;
}
?>

<!-- เพิ่ม script รองรับการใช้งานแผนที่ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีจำหรับแผนที่หรือไม่
    const mapElement = document.getElementById('map');
    if (mapElement && typeof L !== 'undefined') {
        const lat = parseFloat(document.getElementById('schoolLatitude').value) || 13.7563;
        const lng = parseFloat(document.getElementById('schoolLongitude').value) || 100.5018;
        const radius = parseInt(document.getElementById('allowedRadius').value) || 100;
        
        // สร้างแผนที่
        const map = L.map('map').setView([lat, lng], 16);
        
        // เพิ่มชั้นข้อมูลแผนที่ OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // เพิ่มมาร์กเกอร์โรงเรียน
        const schoolMarker = L.marker([lat, lng]).addTo(map);
        schoolMarker.bindPopup("<b>โรงเรียน</b>").openPopup();
        
        // เพิ่มวงกลมแสดงรัศมี
        const circle = L.circle([lat, lng], {
            color: 'green',
            fillColor: '#3de05c',
            fillOpacity: 0.2,
            radius: radius
        }).addTo(map);
    }
});
</script>
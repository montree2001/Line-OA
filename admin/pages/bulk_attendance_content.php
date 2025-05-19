<?php
// ตรวจสอบว่ามีข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาดเพื่อแสดง
$alert_success = $save_success ?? false;
$alert_error = $save_error ?? false;
?>

<!-- แสดงข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาด -->
<?php if ($alert_success): ?>
    <div class="alert alert-success" id="success-alert">
        <span class="material-icons">check_circle</span>
        <div class="alert-message"><?php echo $response_message ?? 'บันทึกการเช็คชื่อเรียบร้อยแล้ว'; ?></div>
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

<!-- คำอธิบายหน้าเว็บ -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">info</span>
        เกี่ยวกับหน้านี้
    </div>
    <div class="card-body">
        <p>หน้านี้ใช้สำหรับเช็คชื่อนักเรียนแบบกลุ่ม โดยสามารถเลือกวันที่ย้อนหลังได้</p>
        <p>ครูที่ปรึกษาสามารถเช็คชื่อได้เฉพาะห้องที่ตัวเองเป็นที่ปรึกษา ส่วนผู้ดูแลระบบสามารถเช็คชื่อได้ทุกห้อง</p>
    </div>
</div>

<!-- ส่วนของฟิลเตอร์การค้นหา -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ตัวกรองข้อมูล
    </div>
    <div class="filter-container">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDepartment" class="form-label">แผนกวิชา</label>
                    <select id="filterDepartment" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterLevel" class="form-label">ระดับชั้น</label>
                    <select id="filterLevel" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterClass" class="form-label">กลุ่มเรียน</label>
                    <!-- เพิ่มข้อมูลนี้ไว้ที่ dropdown กลุ่มเรียน -->
                    <select id="filterClass" class="form-control" data-selected="<?php echo $selected_class_id; ?>">
                        <option value="">-- ทั้งหมด --</option>
                        <!-- จะเติมตัวเลือกด้วย JavaScript -->
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDate" class="form-label">วันที่</label>
                    <input type="date" id="filterDate" class="form-control" value="<?php echo $selected_date; ?>">
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-9">
                <div class="form-group">
                    <label for="searchStudent" class="form-label">ค้นหาด้วยชื่อหรือรหัสนักเรียน</label>
                    <div class="search-wrapper">
                        <span class="material-icons search-icon">search</span>
                        <input type="text" id="searchStudent" class="form-control" placeholder="พิมพ์ชื่อหรือรหัสนักเรียนที่ต้องการค้นหา...">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <button id="btnSearch" class="filter-button">
                    <span class="material-icons">search</span>
                    ค้นหา
                </button>
            </div>
        </div>
    </div>
    <?php if ($selected_class_info): ?>
        <div class="selected-filter-info">
            <div class="alert alert-info">
                <span class="material-icons">info</span>
                <div class="alert-message">
                    ข้อมูลที่เลือกล่าสุด: ห้อง <?php echo $selected_class_info['level'] . '/' . $selected_class_info['group_number'] . ' แผนก' . $selected_class_info['department_name']; ?>
                    วันที่ <?php echo date('d/m/Y', strtotime($selected_date)); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>




</div>

<!-- แสดงรายชื่อนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
        <span id="classTitle" class="class-title-display"></span>
    </div>

    <div id="loadingIndicator" style="display: none;">
        <div class="text-center p-4">
            <div class="spinner"></div>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <div id="studentListContainer" style="display: none;">
        <!-- สร้างฟอร์มที่ครอบคลุมทุกแท็บ -->
        <form id="attendanceForm" method="post">
            <input type="hidden" name="attendance_date" id="attendance_date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="class_id" id="class_id" value="<?php echo htmlspecialchars($selected_class_id ?? ''); ?>">
            <input type="hidden" name="skip_admin_action" value="1"><!-- ไม่บันทึกลง admin_actions -->

            <!-- แท็บเมนู -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <div class="tab active" id="tab-all" onclick="switchTab('all')">
                        <span class="material-icons">people</span>
                        ทั้งหมด
                    </div>
                    <div class="tab" id="tab-checked" onclick="switchTab('checked')">
                        <span class="material-icons">check_circle</span>
                        เช็คชื่อแล้ว
                    </div>
                    <div class="tab" id="tab-unchecked" onclick="switchTab('unchecked')">
                        <span class="material-icons">pending</span>
                        ยังไม่เช็คชื่อ
                    </div>
                    <div class="tab" id="tab-present" onclick="switchTab('present')">
                        <span class="material-icons">verified</span>
                        มาเรียน
                    </div>
                    <div class="tab" id="tab-absent" onclick="switchTab('absent')">
                        <span class="material-icons">cancel</span>
                        ขาดเรียน
                    </div>
                    <div class="tab" id="tab-late" onclick="switchTab('late')">
                        <span class="material-icons">watch_later</span>
                        มาสาย
                    </div>
                    <div class="tab" id="tab-leave" onclick="switchTab('leave')">
                        <span class="material-icons">event_note</span>
                        ลา
                    </div>
                </div>

                <!-- เนื้อหาแท็บ "ทั้งหมด" -->
                <div class="tab-content active" id="content-all">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('all')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('all')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'all')">
                                <span class="material-icons">check_circle</span>
                                เลือกทั้งหมด: มาเรียน
                            </button>
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'all')">
                                <span class="material-icons">watch_later</span>
                                เลือกทั้งหมด: มาสาย
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'all')">
                                <span class="material-icons">cancel</span>
                                เลือกทั้งหมด: ขาดเรียน
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'all')">
                                <span class="material-icons">event_note</span>
                                เลือกทั้งหมด: ลา
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="3%">#</th>
                                    <th width="3%">เลือก</th>
                                    <th width="10%">รหัสนักเรียน</th>
                                    <th width="20%">ชื่อ-นามสกุล</th>
                                    <th width="8%">ระดับชั้น</th>
                                    <th width="12%">สถานะการเข้าแถว</th>
                                    <th width="8%">เวลาเช็คชื่อ</th>
                                    <th width="8%">วิธีเช็คชื่อ</th>
                                    <th width="28%">หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody id="studentList">
                                <tr>
                                    <td colspan="9" class="text-center">กรุณาเลือกแผนกวิชา ระดับชั้น และกลุ่มเรียน แล้วกดค้นหา</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- เนื้อหาแท็บ "เช็คชื่อแล้ว" -->
                <div class="tab-content" id="content-checked">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('checked')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('checked')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'checked')">
                                <span class="material-icons">check_circle</span>
                                เปลี่ยนเป็น: มาเรียน
                            </button>
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'checked')">
                                <span class="material-icons">watch_later</span>
                                เปลี่ยนเป็น: มาสาย
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'checked')">
                                <span class="material-icons">cancel</span>
                                เปลี่ยนเป็น: ขาดเรียน
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'checked')">
                                <span class="material-icons">event_note</span>
                                เปลี่ยนเป็น: ลา
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>

                <!-- เนื้อหาแท็บ "ยังไม่เช็คชื่อ" -->
                <div class="tab-content" id="content-unchecked">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('unchecked')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('unchecked')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'unchecked')">
                                <span class="material-icons">check_circle</span>
                                เช็คทั้งหมดเป็น: มาเรียน
                            </button>
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'unchecked')">
                                <span class="material-icons">watch_later</span>
                                เช็คทั้งหมดเป็น: มาสาย
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'unchecked')">
                                <span class="material-icons">cancel</span>
                                เช็คทั้งหมดเป็น: ขาดเรียน
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'unchecked')">
                                <span class="material-icons">event_note</span>
                                เช็คทั้งหมดเป็น: ลา
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>

                <!-- เนื้อหาแท็บสถานะอื่นๆ -->
                <div class="tab-content" id="content-present">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('present')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('present')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'present')">
                                <span class="material-icons">watch_later</span>
                                เปลี่ยนเป็น: มาสาย
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'present')">
                                <span class="material-icons">cancel</span>
                                เปลี่ยนเป็น: ขาดเรียน
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'present')">
                                <span class="material-icons">event_note</span>
                                เปลี่ยนเป็น: ลา
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>

                <div class="tab-content" id="content-absent">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('absent')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('absent')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'absent')">
                                <span class="material-icons">check_circle</span>
                                เปลี่ยนเป็น: มาเรียน
                            </button>
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'absent')">
                                <span class="material-icons">watch_later</span>
                                เปลี่ยนเป็น: มาสาย
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'absent')">
                                <span class="material-icons">event_note</span>
                                เปลี่ยนเป็น: ลา
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>

                <div class="tab-content" id="content-late">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('late')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('late')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'late')">
                                <span class="material-icons">check_circle</span>
                                เปลี่ยนเป็น: มาเรียน
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'late')">
                                <span class="material-icons">cancel</span>
                                เปลี่ยนเป็น: ขาดเรียน
                            </button>
                            <button type="button" class="btn btn-info" onclick="setAllStatus('leave', 'late')">
                                <span class="material-icons">event_note</span>
                                เปลี่ยนเป็น: ลา
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>

                <div class="tab-content" id="content-leave">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents('leave')">
                            <span class="material-icons">done_all</span>
                            เลือกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents('leave')">
                            <span class="material-icons">remove_done</span>
                            ยกเลิกเลือกทั้งหมด
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" onclick="setAllStatus('present', 'leave')">
                                <span class="material-icons">check_circle</span>
                                เปลี่ยนเป็น: มาเรียน
                            </button>
                            <button type="button" class="btn btn-warning" onclick="setAllStatus('late', 'leave')">
                                <span class="material-icons">watch_later</span>
                                เปลี่ยนเป็น: มาสาย
                            </button>
                            <button type="button" class="btn btn-danger" onclick="setAllStatus('absent', 'leave')">
                                <span class="material-icons">cancel</span>
                                เปลี่ยนเป็น: ขาดเรียน
                            </button>
                        </div>
                    </div>
                    <!-- ตารางจะถูกเติมด้วย JavaScript -->
                </div>
            </div>

            <!-- สรุปข้อมูลการเช็คชื่อ -->
            <div class="attendance-summary" id="attendanceSummary" style="display: none;">
                <div class="row">
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="presentCount">0</div>
                            <div class="attendance-stat-label">มาเรียน</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="lateCount">0</div>
                            <div class="attendance-stat-label">มาสาย</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="absentCount">0</div>
                            <div class="attendance-stat-label">ขาดเรียน</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="leaveCount">0</div>
                            <div class="attendance-stat-label">ลา</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions" id="formActions" style="display: none;">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <span class="material-icons">refresh</span>
                    ยกเลิก
                </button>
                <button type="submit" name="save_attendance" value="1" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการเช็คชื่อ
                </button>
            </div>
        </form>
    </div>

    <div id="noStudentsMessage" style="display: none;">
        <div class="text-center p-4">
            <span class="material-icons" style="font-size: 48px; color: #ccc;">person_off</span>
            <p>ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก</p>
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

<!-- ไอคอนความหมายของวิธีการเช็คชื่อ -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">help_outline</span>
        วิธีการเช็คชื่อ
    </div>
    <div class="card-body">
        <div class="attendance-methods">
            <div class="method-item">
                <span class="material-icons method-icon" style="color: #28a745;">gps_fixed</span>
                <span class="method-text">เช็คชื่อผ่าน GPS</span>
            </div>
            <div class="method-item">
                <span class="material-icons method-icon" style="color: #007bff;">qr_code_scanner</span>
                <span class="method-text">เช็คชื่อผ่าน QR Code</span>
            </div>
            <div class="method-item">
                <span class="material-icons method-icon" style="color: #fd7e14;">pin</span>
                <span class="method-text">เช็คชื่อผ่านรหัส PIN</span>
            </div>
            <div class="method-item">
                <span class="material-icons method-icon" style="color: #6c757d;">edit</span>
                <span class="method-text">เช็คชื่อด้วยตนเอง</span>
            </div>
        </div>
    </div>
</div>

<!-- สคริปต์เพิ่มเติมเฉพาะหน้านี้ -->
<script>
    // ฟังก์ชันเปิดโมดัลดาวน์โหลดรายงาน
    function downloadAttendanceReport() {
        document.getElementById('downloadReportModal').style.display = 'flex';
    }

    // ฟังก์ชันปิดโมดัล
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // ฟังก์ชันสลับแท็บ
    function switchTab(tabName) {
        // ซ่อนทุกแท็บเนื้อหา
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
        });

        // ซ่อนการเลือกจากทุกแท็บ
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.classList.remove('active');
        });

        // แสดงแท็บที่เลือก
        document.getElementById('tab-' + tabName).classList.add('active');
        document.getElementById('content-' + tabName).classList.add('active');

        // กรองข้อมูลนักเรียนตามแท็บที่เลือก
        filterStudentsByTab(tabName);
    }

    // ฟังก์ชันกรองข้อมูลนักเรียนตามแท็บ
    function filterStudentsByTab(tabName) {
        const allStudents = document.querySelectorAll('#content-all .student-row');

        // ล้างเนื้อหาในแท็บปัจจุบัน
        const currentTabContent = document.getElementById('content-' + tabName);
        if (tabName !== 'all') {
            currentTabContent.innerHTML = '';

            // คัดลอกตารางจากแท็บทั้งหมด
            const tableClone = document.querySelector('#content-all .data-table').cloneNode(true);
            const tbody = tableClone.querySelector('tbody');

            // ล้างเนื้อหาในตาราง
            tbody.innerHTML = '';

            // คัดกรองและเพิ่มแถวที่ตรงตามเงื่อนไข
            allStudents.forEach(row => {
                const status = row.getAttribute('data-status');
                const isChecked = row.getAttribute('data-checked') === 'true';

                let shouldInclude = false;

                switch (tabName) {
                    case 'checked':
                        shouldInclude = isChecked;
                        break;
                    case 'unchecked':
                        shouldInclude = !isChecked;
                        break;
                    case 'present':
                        shouldInclude = status === 'present';
                        break;
                    case 'absent':
                        shouldInclude = status === 'absent';
                        break;
                    case 'late':
                        shouldInclude = status === 'late';
                        break;
                    case 'leave':
                        shouldInclude = status === 'leave';
                        break;
                }

                if (shouldInclude) {
                    tbody.appendChild(row.cloneNode(true));
                }
            });

            // ถ้าไม่มีข้อมูลในแท็บนี้
            if (tbody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="9" class="text-center">ไม่พบข้อมูลนักเรียนในหมวดหมู่นี้</td>';
                tbody.appendChild(emptyRow);
            }

            currentTabContent.appendChild(tableClone);
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตั้งค่าค่าเริ่มต้นสำหรับตัวเลือกห้องเรียน (ถ้ามี)
        <?php if (!empty($selected_class_id)): ?>
            setTimeout(function() {
                // กำหนดค่า department และ level จาก selected_class_info
                <?php if ($selected_class_info): ?>
                    const dept = document.getElementById('filterDepartment');
                    const level = document.getElementById('filterLevel');

                    // ตั้งค่า department
                    for (let i = 0; i < dept.options.length; i++) {
                        if (dept.options[i].value == '<?php echo $selected_class_info['department_id']; ?>') {
                            dept.selectedIndex = i;
                            break;
                        }
                    }

                    // ตั้งค่า level
                    for (let i = 0; i < level.options.length; i++) {
                        if (level.options[i].value == '<?php echo $selected_class_info['level']; ?>') {
                            level.selectedIndex = i;
                            break;
                        }
                    }

                    // อัปเดตตัวเลือกห้องเรียน แล้วเลือกห้องเรียนที่เคยเลือกไว้
                    updateClasses(function() {
                        const classSelect = document.getElementById('filterClass');
                        for (let i = 0; i < classSelect.options.length; i++) {
                            if (classSelect.options[i].value == '<?php echo $selected_class_id; ?>') {
                                classSelect.selectedIndex = i;
                                break;
                            }
                        }

                        // โหลดข้อมูลนักเรียน
                        searchStudents();
                    });
                <?php endif; ?>
            }, 100);
        <?php endif; ?>
    });
</script>

<script>
// เพิ่มข้อมูลกลุ่มเรียนที่เลือกไว้ล่าสุด (ไม่กระทบโค้ดเดิม)
document.addEventListener('DOMContentLoaded', function() {
    // เชื่อมต่อ event เพิ่มเติมสำหรับการเปลี่ยนแปลงกลุ่มเรียน
    document.getElementById('filterClass').addEventListener('change', function() {
        // เก็บค่ากลุ่มเรียนที่เลือกใน localStorage
        localStorage.setItem('selectedClassId', this.value);
    });
    
    // เชื่อมต่อ event เดิม
    const originalDeptChange = document.getElementById('filterDepartment').onchange;
    const originalLevelChange = document.getElementById('filterLevel').onchange;
    
    // แทนที่ event สำหรับการเปลี่ยนแปลงแผนกและระดับชั้น
    document.getElementById('filterDepartment').onchange = function(e) {
        if (originalDeptChange) originalDeptChange.call(this, e);
        
        // เรียกใช้ updateClassesWithSelection หลังจากอัปเดตรายการกลุ่มเรียน
        setTimeout(updateClassesWithSelection, 100);
    };
    
    document.getElementById('filterLevel').onchange = function(e) {
        if (originalLevelChange) originalLevelChange.call(this, e);
        
        // เรียกใช้ updateClassesWithSelection หลังจากอัปเดตรายการกลุ่มเรียน
        setTimeout(updateClassesWithSelection, 100);
    };
    
    // แทนที่ event ปุ่มค้นหา
    const originalBtnSearch = document.getElementById('btnSearch').onclick;
    document.getElementById('btnSearch').onclick = function(e) {
        // บันทึกค่ากลุ่มเรียนที่เลือกใน localStorage ก่อนค้นหา
        localStorage.setItem('selectedClassId', document.getElementById('filterClass').value);
        
        // เรียกใช้ event เดิม
        if (originalBtnSearch) originalBtnSearch.call(this, e);
    };
    
    // โหลดค่ากลุ่มเรียนที่เลือก
    loadSelectedClass();
});

// ฟังก์ชันสำหรับโหลดค่ากลุ่มเรียนที่เลือกไว้ล่าสุด
function loadSelectedClass() {
    // ตรวจสอบว่ามีการกำหนดค่ากลุ่มเรียนที่เลือกไว้หรือไม่
    const classSelect = document.getElementById('filterClass');
    const selectedClass = classSelect.getAttribute('data-selected') || localStorage.getItem('selectedClassId');
    
    if (selectedClass) {
        // กำหนดค่า department และ level ตามกลุ่มเรียนที่เลือก
        fetchClassInfo(selectedClass);
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลกลุ่มเรียน
function fetchClassInfo(classId) {
    fetch('ajax/get_class_info.php?class_id=' + classId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.class_info) {
                const deptSelect = document.getElementById('filterDepartment');
                const levelSelect = document.getElementById('filterLevel');
                
                // ตั้งค่า department
                for (let i = 0; i < deptSelect.options.length; i++) {
                    if (deptSelect.options[i].value == data.class_info.department_id) {
                        deptSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // ตั้งค่า level
                for (let i = 0; i < levelSelect.options.length; i++) {
                    if (levelSelect.options[i].value == data.class_info.level) {
                        levelSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // อัปเดตรายการกลุ่มเรียน
                if (typeof updateClasses === 'function') {
                    setTimeout(function() {
                        updateClasses();
                        // ตั้งค่ากลุ่มเรียนหลังจากอัปเดตรายการ
                        setTimeout(function() {
                            updateClassesWithSelection();
                        }, 100);
                    }, 100);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching class info:', error);
        });
}

// ฟังก์ชันสำหรับอัปเดตการเลือกกลุ่มเรียน
function updateClassesWithSelection() {
    const classSelect = document.getElementById('filterClass');
    const selectedClass = classSelect.getAttribute('data-selected') || localStorage.getItem('selectedClassId');
    
    if (selectedClass) {
        // ตั้งค่ากลุ่มเรียนที่เคยเลือกไว้
        for (let i = 0; i < classSelect.options.length; i++) {
            if (classSelect.options[i].value == selectedClass) {
                classSelect.selectedIndex = i;
                break;
            }
        }
        
        // กำหนดค่าใน input hidden
        document.getElementById('class_id').value = selectedClass;
    }
}
</script>

<script>
// สคริปต์เพื่อโหลดข้อมูลกลุ่มเรียนที่เลือกล่าสุด
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($selected_class_info)): ?>
    // ทำงานเมื่อเอกสารโหลดเสร็จแล้ว
    setTimeout(function() {
        // ตั้งค่าแผนกวิชา
        var deptSelect = document.getElementById('filterDepartment');
        if (deptSelect) {
            for (var i = 0; i < deptSelect.options.length; i++) {
                if (deptSelect.options[i].value == '<?php echo $selected_class_info['department_id']; ?>') {
                    deptSelect.selectedIndex = i;
                    break;
                }
            }
            
            // ไม่ใช้ onchange เพื่อไม่ให้กระทบกับฟังก์ชั่นเดิม
            if (typeof updateClasses === 'function') {
                updateClasses();
            }
        }
        
        // ตั้งค่าระดับชั้น
        var levelSelect = document.getElementById('filterLevel');
        if (levelSelect) {
            for (var i = 0; i < levelSelect.options.length; i++) {
                if (levelSelect.options[i].value == '<?php echo $selected_class_info['level']; ?>') {
                    levelSelect.selectedIndex = i;
                    break;
                }
            }
            
            // ไม่ใช้ onchange เพื่อไม่ให้กระทบกับฟังก์ชั่นเดิม
            if (typeof updateClasses === 'function') {
                updateClasses();
            }
        }
        
        // ตั้งค่ากลุ่มเรียนและโหลดข้อมูลนักเรียน
        setTimeout(function() {
            var classSelect = document.getElementById('filterClass');
            if (classSelect) {
                for (var i = 0; i < classSelect.options.length; i++) {
                    if (classSelect.options[i].value == '<?php echo $selected_class_id; ?>') {
                        classSelect.selectedIndex = i;
                        break;
                    }
                }
                
                // โหลดข้อมูลนักเรียน
                if (typeof searchStudents === 'function') {
                    searchStudents();
                }
            }
        }, 300);
    }, 100);
    <?php endif; ?>
});
</script>
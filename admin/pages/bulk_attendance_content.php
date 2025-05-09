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
                        <?php
                        // ตรวจสอบว่า $departments เป็นอาร์เรย์ก่อนใช้งาน
                        if (isset($departments) && is_array($departments)):
                            foreach ($departments as $department):
                        ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo htmlspecialchars($department['department_name']); ?></option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterLevel" class="form-label">ระดับชั้น</label>
                    <select id="filterLevel" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php
                        // ตรวจสอบว่า $levels เป็นอาร์เรย์ก่อนใช้งาน
                        if (isset($levels) && is_array($levels)):
                            foreach ($levels as $level):
                        ?>
                                <option value="<?php echo htmlspecialchars($level); ?>"><?php echo htmlspecialchars($level); ?></option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterClass" class="form-label">กลุ่มเรียน</label>
                    <select id="filterClass" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <!-- จะเติมตัวเลือกด้วย JavaScript -->
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDate" class="form-label">วันที่</label>
                    <input type="date" id="filterDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 text-right">
                <button id="btnSearch" class="btn btn-primary">
                    <span class="material-icons">search</span>
                    ค้นหา
                </button>
            </div>
        </div>
    </div>
</div>

<!-- คำอธิบาย icon วิธีการเช็คชื่อ -->
<div class="card mt-3" id="checkMethodLegend" style="display: none;">
    <div class="card-title">
        <span class="material-icons">info</span>
        สัญลักษณ์วิธีการเช็คชื่อ
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="legend-list" style="list-style-type: none; padding-left: 0;">
                    <li style="margin-bottom: 8px;">
                        <span class="material-icons text-success" style="vertical-align: middle;">location_on</span>
                        <span style="margin-left: 8px;">เช็คชื่อด้วย GPS</span>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <span class="material-icons text-primary" style="vertical-align: middle;">qr_code_scanner</span>
                        <span style="margin-left: 8px;">เช็คชื่อด้วย QR Code</span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="legend-list" style="list-style-type: none; padding-left: 0;">
                    <li style="margin-bottom: 8px;">
                        <span class="material-icons text-warning" style="vertical-align: middle;">pin</span>
                        <span style="margin-left: 8px;">เช็คชื่อด้วยรหัส PIN</span>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <span class="material-icons text-info" style="vertical-align: middle;">edit</span>
                        <span style="margin-left: 8px;">เช็คชื่อด้วยผู้ดูแลระบบ</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- แสดงรายชื่อนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
        <span id="classTitle"></span>
    </div>

    <div id="loadingIndicator" style="display: none;">
        <div class="text-center p-4">
            <div class="spinner"></div>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    <div id="studentListContainer" style="display: none;">
        <form id="attendanceForm" method="post">
            <input type="hidden" name="attendance_date" id="attendance_date" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="class_id" id="class_id" value="">

            <div class="bulk-actions">
                <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents()">
                    <span class="material-icons">done_all</span>
                    เลือกทั้งหมด
                </button>
                <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents()">
                    <span class="material-icons">remove_done</span>
                    ยกเลิกเลือกทั้งหมด
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="setAllStatus('present')">
                        <span class="material-icons">check_circle</span>
                        เลือกทั้งหมด: เข้าแถว
                    </button>
                    <button type="button" class="btn btn-danger" onclick="setAllStatus('absent')">
                        <span class="material-icons">cancel</span>
                        เลือกทั้งหมด: ขาดแถว
                    </button>
                    <button type="button" class="btn btn-warning" onclick="setAllStatus('late')">
                        <span class="material-icons">watch_later</span>
                        เลือกทั้งหมด: สาย
                    </button>
                    <button type="button" class="btn btn-info" onclick="setAllStatus('leave')">
                        <span class="material-icons">event_note</span>
                        เลือกทั้งหมด: ลา
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="5%">เลือก</th>
                            <th width="10%">รหัสนักเรียน</th>
                            <th width="20%">ชื่อ-นามสกุล</th>
                            <th width="8%">ระดับชั้น</th>
                            <th width="7%">วิธีเช็คชื่อ</th>
                            <th width="15%">สถานะการเข้าแถว</th>
                            <th width="30%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody id="studentList">
                        <tr>
                            <td colspan="8" class="text-center">กรุณาเลือกแผนกวิชา ระดับชั้น และกลุ่มเรียน แล้วกดค้นหา</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- สรุปข้อมูลการเช็คชื่อ -->
            <div class="attendance-summary" id="attendanceSummary" style="display: none;">
                <div class="row">
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="presentCount">0</div>
                            <div class="attendance-stat-label">เข้าแถว</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="lateCount">0</div>
                            <div class="attendance-stat-label">สาย</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="absentCount">0</div>
                            <div class="attendance-stat-label">ขาดแถว</div>
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
                                if (isset($current_academic_year_id) && $current_academic_year_id !== null) {
                                    $stmt = $conn->prepare("
                                        SELECT c.class_id, c.level, c.group_number, d.department_name
                                        FROM classes c 
                                        JOIN departments d ON c.department_id = d.department_id
                                        WHERE c.academic_year_id = ?
                                        ORDER BY c.level, c.group_number
                                    ");
                                    $stmt->execute([$current_academic_year_id]);
                                    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (is_array($classes) && count($classes) > 0) {
                                        foreach ($classes as $class):
                            ?>
                                            <option value="<?php echo $class['class_id']; ?>">
                                                <?php echo htmlspecialchars($class['level'] . '/' . $class['group_number'] . ' ' . $class['department_name']); ?>
                                            </option>
                            <?php
                                        endforeach;
                                    }
                                }
                            } catch (PDOException $e) {
                                // ไม่แสดงตัวเลือกเพิ่มเติมหากมีข้อผิดพลาด
                                error_log("Error loading classes for report: " . $e->getMessage());
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

    // เพิ่ม console.log สำหรับตรวจสอบว่า JavaScript โหลดเรียบร้อย
    console.log('Bulk attendance content loaded');

    // เชื่อมโยงการแสดงผลคำอธิบายกับการแสดงรายชื่อนักเรียน
    document.addEventListener('DOMContentLoaded', function() {
        // เชื่อมโยงการแสดงผลคำอธิบายกับการแสดงรายชื่อนักเรียน
        const originalSearchStudents = window.searchStudents;

        if (typeof originalSearchStudents === 'function') {
            // Override ฟังก์ชัน searchStudents เพื่อแสดงคำอธิบาย icon
            window.searchStudents = function() {
                if (typeof originalSearchStudents === 'function') {
                    originalSearchStudents();
                }

                // รอให้มีการแสดงรายชื่อนักเรียนก่อน
                setTimeout(function() {
                    const studentListContainer = document.getElementById('studentListContainer');
                    const legendContainer = document.getElementById('checkMethodLegend');

                    if (studentListContainer && legendContainer) {
                        if (studentListContainer.style.display !== 'none') {
                            legendContainer.style.display = 'block';
                        } else {
                            legendContainer.style.display = 'none';
                        }
                    }
                }, 500);
            };
        }
    });
</script>
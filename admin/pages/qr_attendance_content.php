<?php
// QR Attendance Content Page
?>

<!-- แสดงข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาด -->
<div id="alertContainer"></div>

<!-- คำอธิบายหน้าเว็บ -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">info</span>
        เกี่ยวกับหน้านี้
    </div>
    <div class="card-body">
        <p>หน้านี้ใช้สำหรับเช็คชื่อนักเรียนผ่าน QR Code Scanner โดยให้นักเรียนแสดง QR Code ที่สร้างจากแอปพลิเคชัน</p>
        <p>ระบบจะแสกน QR Code อัตโนมัติและบันทึกการเข้าแถวทันที พร้อมแสดงข้อมูลนักเรียนที่เช็คชื่อ</p>
    </div>
</div>

<!-- ส่วนตัวเลือกและการตั้งค่า -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">settings</span>
        การตั้งค่าการเช็คชื่อ
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="scanDate" class="form-label">วันที่เช็คชื่อ</label>
                    <input type="date" id="scanDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="attendanceStatus" class="form-label">สถานะการเข้าแถว</label>
                    <select id="attendanceStatus" class="form-control">
                        <option value="present">มาเรียน</option>
                        <option value="late">มาสาย</option>
                        <option value="leave">ลา</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="scanner-controls">
                    <button id="startScanBtn" class="btn btn-primary">
                        <span class="material-icons">qr_code_scanner</span>
                        เริ่มแสกน QR Code
                    </button>
                    <button id="stopScanBtn" class="btn btn-secondary" style="display: none;">
                        <span class="material-icons">stop</span>
                        หยุดแสกน
                    </button>
                    <button id="switchCameraBtn" class="btn btn-info" style="display: none;">
                        <span class="material-icons">switch_camera</span>
                        เปลี่ยนกล้อง
                    </button>
                    <button id="fullscreenBtn" class="btn btn-success">
                        <span class="material-icons">fullscreen</span>
                        แสดงเต็มจอ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ส่วน QR Code Scanner -->
<div class="card scanner-card" id="scannerCard" style="display: none;">
    <div class="card-title">
        <span class="material-icons">qr_code_scanner</span>
        QR Code Scanner
        <div class="scanner-status">
            <span id="scannerStatus" class="status-ready">พร้อมแสกน</span>
        </div>
    </div>
    <div class="card-body">
        <div class="scanner-container">
            <div id="qr-reader" class="qr-reader"></div>
            <div class="scanner-overlay">
                <div class="scanner-frame"></div>
                <div class="scanner-instructions">
                    <p>วางมือถือให้ QR Code อยู่ในกรอบสี่เหลี่ยม</p>
                </div>
            </div>
        </div>
        
        <!-- ข้อมูลการแสกน -->
        <div class="scan-info">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-item">
                        <span class="info-label">จำนวนที่แสกนแล้ว:</span>
                        <span id="scanCount" class="info-value">0</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <span class="info-label">แสกนสำเร็จ:</span>
                        <span id="successCount" class="info-value success">0</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <span class="info-label">แสกนไม่สำเร็จ:</span>
                        <span id="errorCount" class="info-value error">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ข้อมูลนักเรียนที่เช็คชื่อล่าสุด -->
<div class="card" id="latestStudentCard" style="display: none;">
    <div class="card-title">
        <span class="material-icons">person_check</span>
        นักเรียนที่เช็คชื่อล่าสุด
    </div>
    <div class="card-body">
        <div id="latestStudentInfo" class="student-info-card">
            <!-- ข้อมูลจะถูกเติมด้วย JavaScript -->
        </div>
    </div>
</div>

<!-- รายการนักเรียนที่เช็คชื่อแล้ววันนี้ -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">checklist</span>
        รายการนักเรียนที่เช็คชื่อแล้ววันนี้
        <span id="attendanceCount" class="attendance-count">0 คน</span>
    </div>
    <div class="card-body">
        <div class="attendance-summary-stats">
            <div class="row">
                <div class="col-3">
                    <div class="stat-item present">
                        <div class="stat-number" id="presentTotal">0</div>
                        <div class="stat-label">มาเรียน</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-item late">
                        <div class="stat-number" id="lateTotal">0</div>
                        <div class="stat-label">มาสาย</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-item absent">
                        <div class="stat-number" id="absentTotal">0</div>
                        <div class="stat-label">ขาดเรียน</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="stat-item leave">
                        <div class="stat-number" id="leaveTotal">0</div>
                        <div class="stat-label">ลา</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="data-table" id="attendanceTable">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="12%">รหัสนักเรียน</th>
                        <th width="20%">ชื่อ-นามสกุล</th>
                        <th width="15%">ระดับชั้น</th>
                        <th width="12%">สถานะ</th>
                        <th width="10%">เวลา</th>
                        <th width="10%">วิธี</th>
                        <th width="16%">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="8" class="text-center">ยังไม่มีการเช็คชื่อ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงข้อมูลนักเรียนที่แสกน -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ข้อมูลนักเรียน</h2>
            <span class="close" onclick="closeStudentModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="studentModalContent">
                <!-- ข้อมูลจะถูกเติมด้วย JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeStudentModal()">ปิด</button>
            <button id="confirmAttendanceBtn" class="btn btn-primary" onclick="confirmAttendance()">ยืนยันการเช็คชื่อ</button>
        </div>
    </div>
</div>

<!-- Modal สำหรับแก้ไขการเช็คชื่อ -->
<div id="editAttendanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>แก้ไขการเช็คชื่อ</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editAttendanceForm">
                <input type="hidden" id="editAttendanceId" name="attendance_id">
                <input type="hidden" id="editStudentId" name="student_id">
                
                <div class="form-group">
                    <label for="editStatus" class="form-label">สถานะการเข้าแถว</label>
                    <select id="editStatus" name="status" class="form-control" required>
                        <option value="present">มาเรียน</option>
                        <option value="late">มาสาย</option>
                        <option value="absent">ขาดเรียน</option>
                        <option value="leave">ลา</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editRemarks" class="form-label">หมายเหตุ</label>
                    <textarea id="editRemarks" name="remarks" class="form-control" rows="3" placeholder="ระบุหมายเหตุ (ถ้ามี)"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditModal()">ยกเลิก</button>
            <button class="btn btn-primary" onclick="saveEditAttendance()">บันทึก</button>
        </div>
    </div>
</div>

<!-- ข้อมูลเพิ่มเติมสำหรับ JavaScript -->
<script>
    // ข้อมูลที่ต้องการส่งไปยัง JavaScript
    const QR_SCANNER_CONFIG = {
        userId: <?php echo json_encode($user_id); ?>,
        userRole: <?php echo json_encode($user_role); ?>,
        academicYearId: <?php echo json_encode($current_academic_year_id); ?>,
        scannerSettings: {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        }
    };
</script>
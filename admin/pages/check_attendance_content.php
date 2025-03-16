<!-- แท็บสำหรับการเช็คชื่อนักเรียน -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="qr-code">สแกน QR Code</div>
        <div class="tab" data-tab="pin-code">รหัส PIN</div>
        <div class="tab" data-tab="manual">เช็คชื่อด้วยตนเอง</div>
        <div class="tab" data-tab="gps">ตรวจสอบ GPS</div>
    </div>
</div>

<!-- แท็บการสแกน QR Code -->
<div id="qr-code-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">qr_code_scanner</span>
            เช็คชื่อด้วย QR Code
        </div>
        
        <div class="camera-container">
            <div class="camera-preview">
                <div class="camera-placeholder">
                    <span class="material-icons">videocam</span>
                    <p>กรุณากดปุ่ม "เปิดกล้อง" เพื่อสแกน QR Code</p>
                </div>
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
            <div class="scan-result-empty">ยังไม่มีการสแกน QR Code</div>
            
            <!-- ตัวอย่างผลการสแกน (ซ่อนไว้และจะแสดงเมื่อสแกนสำเร็จ) -->
            <div class="scan-result-item" style="display: none;">
                <div class="scan-result-header">
                    <div class="student-info">
                        <div class="student-avatar">ธ</div>
                        <div class="student-details">
                            <div class="student-name">นายธนกฤต สุขใจ</div>
                            <div class="student-class">ม.6/2 เลขที่ 12</div>
                        </div>
                    </div>
                    <div class="scan-result-time">16/03/2568 08:15</div>
                </div>
                <div class="scan-result-status success">
                    <span class="material-icons">check_circle</span>
                    <span>เช็คชื่อสำเร็จ</span>
                </div>
            </div>
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
                <tbody>
                    <tr>
                        <td>16/03/2568 08:10</td>
                        <td>นายพิชัย รักเรียน</td>
                        <td>ม.4/1</td>
                        <td>QR Code</td>
                        <td><span class="status-badge success">สำเร็จ</span></td>
                    </tr>
                    <tr>
                        <td>16/03/2568 08:08</td>
                        <td>นางสาววรรณา ชาติไทย</td>
                        <td>ม.5/2</td>
                        <td>QR Code</td>
                        <td><span class="status-badge success">สำเร็จ</span></td>
                    </tr>
                    <tr>
                        <td>16/03/2568 08:05</td>
                        <td>นางสาวสมหญิง มีสุข</td>
                        <td>ม.5/3</td>
                        <td>QR Code</td>
                        <td><span class="status-badge success">สำเร็จ</span></td>
                    </tr>
                    <tr>
                        <td>16/03/2568 08:01</td>
                        <td>นายมานะ พากเพียร</td>
                        <td>ม.4/3</td>
                        <td>QR Code</td>
                        <td><span class="status-badge success">สำเร็จ</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- แท็บรหัส PIN -->
<div id="pin-code-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">pin</span>
            เช็คชื่อด้วยรหัส PIN
        </div>
        
        <div class="pin-generator">
            <div class="pin-display">
                <div class="pin-code" id="currentPin">5731</div>
                <div class="pin-timer">
                    <span class="material-icons">timer</span>
                    <span id="pinTimer">09:58</span> นาที
                </div>
            </div>
            
            <div class="pin-actions">
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
            <p class="pin-note">หมายเหตุ: รหัส PIN จะหมดอายุภายใน 10 นาที และสามารถใช้ได้กับนักเรียนทุกคนในระบบ</p>
        </div>
        
        <div class="pin-users-container">
            <h3>นักเรียนที่ใช้รหัส PIN นี้เช็คชื่อ</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="5%">เช็คชื่อ</th>
                            <th width="25%">ชื่อ-นามสกุล</th>
                            <th width="10%">เลขที่</th>
                            <th width="15%">เวลามาเรียน</th>
                            <th width="20%">สถานะ</th>
                            <th width="20%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>
                                <input type="checkbox" name="attendance[]" value="1" checked>
                            </td>
                            <td>นายธนกฤต สุขใจ</td>
                            <td>12</td>
                            <td>08:11</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present" selected>มาเรียน</option>
                                    <option value="late">มาสาย</option>
                                    <option value="absent">ขาดเรียน</option>
                                    <option value="leave">ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" placeholder="หมายเหตุ">
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>
                                <input type="checkbox" name="attendance[]" value="2" checked>
                            </td>
                            <td>นางสาวนันทิชา ยิ้มแย้ม</td>
                            <td>13</td>
                            <td>08:00</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present" selected>มาเรียน</option>
                                    <option value="late">มาสาย</option>
                                    <option value="absent">ขาดเรียน</option>
                                    <option value="leave">ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" placeholder="หมายเหตุ">
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>
                                <input type="checkbox" name="attendance[]" value="3">
                            </td>
                            <td>นายภาณุพงศ์ วีระ</td>
                            <td>14</td>
                            <td>-</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present">มาเรียน</option>
                                    <option value="late">มาสาย</option>
                                    <option value="absent" selected>ขาดเรียน</option>
                                    <option value="leave">ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" placeholder="หมายเหตุ">
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>
                                <input type="checkbox" name="attendance[]" value="4">
                            </td>
                            <td>นางสาวจันทร์จิรา รักเรียน</td>
                            <td>15</td>
                            <td>-</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present">มาเรียน</option>
                                    <option value="late">มาสาย</option>
                                    <option value="absent">ขาดเรียน</option>
                                    <option value="leave" selected>ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" value="ลาป่วย มีใบรับรองแพทย์">
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>
                                <input type="checkbox" name="attendance[]" value="5" checked>
                            </td>
                            <td>นายสมชาย ใจดี</td>
                            <td>16</td>
                            <td>08:20</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present">มาเรียน</option>
                                    <option value="late" selected>มาสาย</option>
                                    <option value="absent">ขาดเรียน</option>
                                    <option value="leave">ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" value="รถติด">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="attendance-summary">
                <div class="row">
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">3</div>
                            <div class="attendance-stat-label">มาเรียน</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">1</div>
                            <div class="attendance-stat-label">มาสาย</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">1</div>
                            <div class="attendance-stat-label">ขาดเรียน</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">1</div>
                            <div class="attendance-stat-label">ลา</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveAttendance()">
                    <span class="material-icons">save</span>
                    บันทึกการเช็คชื่อ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- แท็บตรวจสอบ GPS -->
<div id="gps-tab" class="tab-content">
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
                        <input type="text" class="form-control" value="13.7563" id="schoolLatitude">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ลองจิจูดของโรงเรียน</label>
                        <input type="text" class="form-control" value="100.5018" id="schoolLongitude">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">รัศมีพื้นที่ที่อนุญาต (เมตร)</label>
                <input type="number" class="form-control" value="100" min="10" max="1000" id="allowedRadius">
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
            <div class="map-placeholder">
                <!-- ในทางปฏิบัติจริง จะใช้ Google Maps หรือ Leaflet.js แสดงแผนที่ -->
                <img src="/api/placeholder/800/400" alt="แผนที่โรงเรียน" style="width: 100%; height: auto; max-height: 400px; border-radius: 8px;">
                <div class="map-radius"></div>
                <div class="map-marker"></div>
            </div>
            <div class="map-info">
                <p><strong>พื้นที่ที่อนุญาต:</strong> รัศมี 100 เมตรจากจุดศูนย์กลางโรงเรียน</p>
                <p><strong>จำนวนนักเรียนที่เช็คชื่อผ่าน GPS วันนี้:</strong> 245 คน</p>
                <p><strong>จำนวนนักเรียนที่อยู่นอกพื้นที่:</strong> 3 คน</p>
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
                        <tr>
                            <td>16/03/2568 08:05</td>
                            <td>นายพงศกร มานะ</td>
                            <td>ม.5/1</td>
                            <td>15 เมตร</td>
                            <td><span class="status-badge success">อยู่ในพื้นที่</span></td>
                            <td>
                                <button class="table-action-btn primary" title="ดูแผนที่">
                                    <span class="material-icons">map</span>
                                </button>
                                <button class="table-action-btn success" title="ดูรูปภาพ">
                                    <span class="material-icons">photo</span>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>16/03/2568 08:02</td>
                            <td>นางสาวศศิธร ใจดี</td>
                            <td>ม.4/2</td>
                            <td>25 เมตร</td>
                            <td><span class="status-badge success">อยู่ในพื้นที่</span></td>
                            <td>
                                <button class="table-action-btn primary" title="ดูแผนที่">
                                    <span class="material-icons">map</span>
                                </button>
                                <button class="table-action-btn success" title="ดูรูปภาพ">
                                    <span class="material-icons">photo</span>
                                </button>
                            </td>
                        </tr>
                        <tr class="table-warning">
                            <td>16/03/2568 08:00</td>
                            <td>นายอานนท์ ภักดี</td>
                            <td>ม.5/1</td>
                            <td>150 เมตร</td>
                            <td><span class="status-badge danger">นอกพื้นที่</span></td>
                            <td>
                                <button class="table-action-btn primary" title="ดูแผนที่">
                                    <span class="material-icons">map</span>
                                </button>
                                <button class="table-action-btn success" title="ดูรูปภาพ">
                                    <span class="material-icons">photo</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงรูปภาพ -->
<div class="modal" id="photoModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('photoModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">รูปภาพการเช็คชื่อ - นายอานนท์ ภักดี</h2>
        
        <div class="photo-container">
            <img src="/api/placeholder/600/400" alt="รูปภาพการเช็คชื่อ" style="width: 100%; height: auto; border-radius: 8px;">
        </div>
        
        <div class="photo-info">
            <p><strong>เวลาถ่ายภาพ:</strong> 16/03/2568 08:00:15</p>
            <p><strong>พิกัด GPS:</strong> 13.7580, 100.5055</p>
            <p><strong>ระยะห่างจากโรงเรียน:</strong> 150 เมตร</p>
            <p><strong>อุปกรณ์:</strong> iPhone 14 Pro</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('photoModal')">ปิด</button>
            <button class="btn btn-primary">
                <span class="material-icons">check_circle</span>
                อนุมัติการเช็คชื่อ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลแสดงแผนที่ -->
<div class="modal" id="mapModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('mapModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แผนที่ตำแหน่งการเช็คชื่อ - นายอานนท์ ภักดี</h2>
        
        <div class="map-container">
            <img src="/api/placeholder/600/400" alt="แผนที่ตำแหน่งการเช็คชื่อ" style="width: 100%; height: auto; border-radius: 8px;">
        </div>
        
        <div class="map-detail-info">
            <p><strong>เวลาเช็คชื่อ:</strong> 16/03/2568 08:00:15</p>
            <p><strong>พิกัด GPS:</strong> 13.7580, 100.5055</p>
            <p><strong>ระยะห่างจากโรงเรียน:</strong> 150 เมตร</p>
            <p><strong>ความแม่นยำ GPS:</strong> ±5 เมตร</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('mapModal')">ปิด</button>
            <button class="btn btn-primary">
                <span class="material-icons">check_circle</span>
                อนุมัติการเช็คชื่อ
            </button>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันสำหรับแท็บ
function showTab(tabId) {
    // ซ่อนแท็บทั้งหมด
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ยกเลิกการเลือกแท็บทั้งหมด
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
    document.getElementById(tabId + '-tab').classList.add('active');
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
}

// เริ่มกล้องสำหรับสแกน QR Code
function startCamera() {
    // ในทางปฏิบัติจริง จะเป็นการเข้าถึงกล้องของอุปกรณ์
    alert('เริ่มกล้องสำหรับสแกน QR Code');
    
    // แสดงผลตัวอย่าง (ในการใช้งานจริงจะไม่ต้องมีส่วนนี้)
    setTimeout(() => {
        document.querySelector('.scan-result-item').style.display = 'block';
        document.querySelector('.scan-result-empty').style.display = 'none';
    }, 2000);
}

// เปิด/ปิดแฟลช
function toggleFlash() {
    // ในทางปฏิบัติจริง จะเป็นการควบคุมแฟลชของกล้อง
    alert('เปิด/ปิดแฟลช');
}

// สร้างรหัส PIN ใหม่
function generatePin() {
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    document.getElementById('currentPin').textContent = pin;
    
    // รีเซ็ตเวลา
    document.getElementById('pinTimer').textContent = '10:00';
    
    // ในทางปฏิบัติจริง จะมีการบันทึกลงฐานข้อมูล
    alert('สร้างรหัส PIN ใหม่: ' + pin);
}

// คัดลอกรหัส PIN
function copyPin() {
    const pin = document.getElementById('currentPin').textContent;
    
    // ในทางปฏิบัติจริง จะใช้ Clipboard API
    // navigator.clipboard.writeText(pin);
    
    alert('คัดลอกรหัส PIN: ' + pin);
}

// โหลดรายชื่อนักเรียนในชั้นเรียน
function loadClassStudents() {
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const date = document.getElementById('attendanceDate').value;
    
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    alert(`โหลดรายชื่อนักเรียนชั้น ${classLevel}/${classRoom} วันที่ ${date}`);
}

// เลือกนักเรียนทั้งหมด
function checkAllStudents() {
    document.querySelectorAll('input[name="attendance[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

// ยกเลิกการเลือกนักเรียนทั้งหมด
function uncheckAllStudents() {
    document.querySelectorAll('input[name="attendance[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// บันทึกการเช็คชื่อ
function saveAttendance() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    alert('บันทึกการเช็คชื่อเรียบร้อยแล้ว');
}

// อัปเดตการตั้งค่า GPS
function updateGpsSettings() {
    const latitude = document.getElementById('schoolLatitude').value;
    const longitude = document.getElementById('schoolLongitude').value;
    const radius = document.getElementById('allowedRadius').value;
    
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    alert(`อัปเดตการตั้งค่า GPS: ละติจูด ${latitude}, ลองจิจูด ${longitude}, รัศมี ${radius} เมตร`);
}

// ทดสอบตำแหน่ง GPS ปัจจุบัน
function testGpsLocation() {
    // ในทางปฏิบัติจริง จะใช้ Geolocation API
    // navigator.geolocation.getCurrentPosition(...);
    
    alert('กำลังตรวจสอบตำแหน่ง GPS ปัจจุบัน');
}

// เมื่อโหลดหน้าเสร็จ ให้เรียกฟังก์ชันเพื่อตั้งค่าแท็บและอื่นๆ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            showTab(tabId);
        });
    });
});
</script>

<style>
/* เพิ่มเติมสำหรับหน้าเช็คชื่อนักเรียน */
.camera-container {
    margin-bottom: 20px;
}

.camera-preview {
    width: 100%;
    height: 300px;
    background-color: #f5f5f5;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    margin-bottom: 15px;
}

.camera-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-light);
}

.camera-placeholder .material-icons {
    font-size: 48px;
    margin-bottom: 10px;
}

.scanner-border {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 200px;
    height: 200px;
    transform: translate(-50%, -50%);
    border: 2px solid var(--primary-color);
    border-radius: 10px;
}

.camera-controls {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.scan-results {
    margin-top: 20px;
}

.scan-results h3 {
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.scan-result-empty {
    color: var(--text-light);
    text-align: center;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.scan-result-item {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}

.scan-result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.scan-result-time {
    font-size: 14px;
    color: var(--text-light);
}

.scan-result-status {
    display: flex;
    align-items: center;
    font-weight: bold;
}

.scan-result-status.success {
    color: var(--success-color);
}

.scan-result-status .material-icons {
    margin-right: 5px;
}

.pin-generator {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 30px;
}

.pin-display {
    text-align: center;
    margin-bottom: 20px;
}

.pin-code {
    font-size: 48px;
    font-weight: bold;
    color: var(--primary-color);
    letter-spacing: 5px;
    margin-bottom: 10px;
}

.pin-timer {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--danger-color);
    font-size: 14px;
}

.pin-timer .material-icons {
    margin-right: 5px;
}

.pin-actions {
    display: flex;
    gap: 10px;
}

.pin-instruction {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.pin-instruction h3 {
    font-size: 16px;
    margin-bottom: 10px;
}

.pin-instruction ol {
    margin-left: 20px;
    margin-bottom: 15px;
}

.pin-instruction li {
    margin-bottom: 5px;
}

.pin-note {
    font-size: 14px;
    color: var(--text-light);
    font-style: italic;
}

.pin-users-container {
    margin-top: 20px;
}

.pin-users-container h3 {
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.manual-attendance-form {
    margin-top: 20px;
}

.manual-attendance-form h3 {
    font-size: 18px;
    margin-bottom: 5px;
}

.manual-attendance-form p {
    margin-bottom: 15px;
    color: var(--text-light);
}

.form-control-sm {
    padding: 5px 10px;
    font-size: 14px;
}

.attendance-summary {
    margin: 20px 0;
}

.bulk-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.gps-settings {
    margin-bottom: 20px;
}

.gps-settings h3 {
    font-size: 16px;
    margin-bottom: 15px;
}

.map-container {
    margin-bottom: 20px;
}

.map-placeholder {
    position: relative;
    margin-bottom: 15px;
}

.map-info {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
}

.map-info p {
    margin-bottom: 5px;
}

.gps-attendance-list {
    margin-top: 30px;
}

.gps-attendance-list h3 {
    font-size: 16px;
    margin-bottom: 15px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.photo-container {
    margin-bottom: 15px;
}

.photo-info {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.photo-info p {
    margin-bottom: 5px;
}

.map-detail-info {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.map-detail-info p {
    margin-bottom: 5px;
}

/* สำหรับการเลือกสีพื้นหลังตาราง */
.table-warning {
    background-color: #fff8e1;
}
</style>
                            <th>เวลา</th>
                            <th>นักเรียน</th>
                            <th>ชั้น/ห้อง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>16/03/2568 08:12</td>
                            <td>นายก้องเกียรติ มีเกียรติ</td>
                            <td>ม.3/2</td>
                            <td><span class="status-badge success">สำเร็จ</span></td>
                        </tr>
                        <tr>
                            <td>16/03/2568 08:11</td>
                            <td>นายธนกฤต สุขใจ</td>
                            <td>ม.6/2</td>
                            <td><span class="status-badge success">สำเร็จ</span></td>
                        </tr>
                        <tr>
                            <td>16/03/2568 08:10</td>
                            <td>นางสาวกันยา สุขศรี</td>
                            <td>ม.5/1</td>
                            <td><span class="status-badge success">สำเร็จ</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- แท็บเช็คชื่อด้วยตนเอง -->
<div id="manual-tab" class="tab-content">
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
                    <option value="ม.1">ม.1</option>
                    <option value="ม.2">ม.2</option>
                    <option value="ม.3">ม.3</option>
                    <option value="ม.4">ม.4</option>
                    <option value="ม.5">ม.5</option>
                    <option value="ม.6" selected>ม.6</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">ห้องเรียน</div>
                <select class="form-control" id="classRoom">
                    <option value="">-- เลือกห้องเรียน --</option>
                    <option value="1">1</option>
                    <option value="2" selected>2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">วันที่</div>
                <input type="date" class="form-control" id="attendanceDate" value="2025-03-16">
            </div>
            <button class="filter-button" onclick="loadClassStudents()">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>
        
        <div class="manual-attendance-form">
            <h3>รายชื่อนักเรียนชั้น ม.6/2</h3>
            <p>วันที่ 16 มีนาคม 2568</p>
            
            <div class="bulk-actions">
                <button class="btn btn-secondary" onclick="checkAllStudents()">เลือกทั้งหมด</button>
                <button class="btn btn-secondary" onclick="uncheckAllStudents()">ยกเลิกเลือกทั้งหมด</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
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
            <div class="camera-preview">
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
                    <!-- สำหรับข้อมูลที่จะถูกเพิ่มด้วย JavaScript -->
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
        
        <div class="pin-generator">
            <div class="pin-display">
                <div class="pin-code" id="currentPin"><?php echo $data['current_pin'] ?? '----'; ?></div>
                <div class="pin-timer">
                    <span class="material-icons">timer</span>
                    <span id="pinTimer"><?php echo sprintf('%02d:%02d', $data['pin_remaining_min'] ?? 0, $data['pin_remaining_sec'] ?? 0); ?></span> นาที
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
            
            <div class="bulk-actions">
                <button class="btn btn-secondary" onclick="checkAllStudents()">เลือกทั้งหมด</button>
                <button class="btn btn-secondary" onclick="uncheckAllStudents()">ยกเลิกเลือกทั้งหมด</button>
            </div>
            
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
                <input type="date" class="form-control" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <button class="filter-button" onclick="loadClassStudents()">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>
        
        <div class="manual-attendance-form">
            <h3>รายชื่อนักเรียนชั้น ม.6/2</h3>
            <p>วันที่ <?php echo date('d/m/Y', strtotime('+543 years')); ?></p>
            
            <div class="bulk-actions">
                <button class="btn btn-secondary" onclick="checkAllStudents()">เลือกทั้งหมด</button>
                <button class="btn btn-secondary" onclick="uncheckAllStudents()">ยกเลิกเลือกทั้งหมด</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="5%">เช็คชื่อ</th>
                            <th width="30%">ชื่อ-นามสกุล</th>
                            <th width="10%">เลขที่</th>
                            <th width="15%">สถานะ</th>
                            <th width="35%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody id="student-list">
                        <tr>
                            <td>1</td>
                            <td>
                                <input type="checkbox" name="manual_attendance[]" value="1" checked>
                            </td>
                            <td>นายธนกฤต สุขใจ</td>
                            <td>12</td>
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
                                <input type="checkbox" name="manual_attendance[]" value="2" checked>
                            </td>
                            <td>นางสาวนันทิชา ยิ้มแย้ม</td>
                            <td>13</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="present">มาเรียน</option>
                                    <option value="late" selected>มาสาย</option>
                                    <option value="absent">ขาดเรียน</option>
                                    <option value="leave">ลา</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" value="มาสาย 10 นาที">
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>
                                <input type="checkbox" name="manual_attendance[]" value="3">
                            </td>
                            <td>นายภาณุพงศ์ วีระ</td>
                            <td>14</td>
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
                                <input type="checkbox" name="manual_attendance[]" value="4">
                            </td>
                            <td>นางสาวจันทร์จิรา รักเรียน</td>
                            <td>15</td>
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
                                <input type="checkbox" name="manual_attendance[]" value="5" checked>
                            </td>
                            <td>นายสมชาย ใจดี</td>
                            <td>16</td>
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
                    </tbody>
                </table>
            </div>
            
            <div class="attendance-summary">
                <div class="row">
                    <div class="col-3">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">2</div>
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
                <button type="button" class="btn btn-primary" onclick="saveManualAttendance()">
                    <span class="material-icons">save</span>
                    บันทึกการเช็คชื่อ
                </button>
            </div>
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
            <div class="map-placeholder" id="map">
                <!-- ในทางปฏิบัติจริง จะใช้ Google Maps หรือ Leaflet.js แสดงแผนที่ -->
                <img src="/api/placeholder/800/400" alt="แผนที่โรงเรียน" style="width: 100%; height: auto; max-height: 400px; border-radius: 8px;">
                <div class="map-radius"></div>
                <div class="map-marker"></div>
            </div>
            <div class="map-info">
                <p><strong>พื้นที่ที่อนุญาต:</strong> รัศมี <span id="radius-display">100</span> เมตรจากจุดศูนย์กลางโรงเรียน</p>
                <p><strong>จำนวนนักเรียนที่เช็คชื่อผ่าน GPS วันนี้:</strong> <span id="gps-count">245</span> คน</p>
                <p><strong>จำนวนนักเรียนที่อยู่นอกพื้นที่:</strong> <span id="outside-count">3</span> คน</p>
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
                                <button class="table-action-btn primary" title="ดูแผนที่" onclick="showMapModal('map-1')">
                                    <span class="material-icons">map</span>
                                </button>
                                <button class="table-action-btn success" title="ดูรูปภาพ" onclick="showPhotoModal('photo-1')">
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
                                <button class="table-action-btn primary" title="ดูแผนที่" onclick="showMapModal('map-2')">
                                    <span class="material-icons">map</span>
                                </button>
                                <button class="table-action-btn success" title="ดูรูปภาพ" onclick="showPhotoModal('photo-2')">
                                    <span class="material-icons">photo</span>
                                </button>
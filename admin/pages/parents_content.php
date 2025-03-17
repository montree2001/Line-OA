<!-- แท็บสำหรับการจัดการข้อมูลผู้ปกครอง -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="parent-list">รายชื่อผู้ปกครอง</div>
        <div class="tab" data-tab="parent-add">เพิ่มผู้ปกครอง</div>
        <div class="tab" data-tab="parent-import">นำเข้าข้อมูล</div>
    </div>
</div>

<!-- เนื้อหาแท็บรายชื่อผู้ปกครอง -->
<div id="parent-list-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">family_restroom</span>
            ค้นหาผู้ปกครอง
        </div>
        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ชื่อ-นามสกุลผู้ปกครอง</div>
                <input type="text" class="form-control" placeholder="ป้อนชื่อผู้ปกครอง...">
            </div>
            <div class="filter-group">
                <div class="filter-label">หมายเลขโทรศัพท์</div>
                <input type="text" class="form-control" placeholder="ป้อนหมายเลขโทรศัพท์...">
            </div>
            <div class="filter-group">
                <div class="filter-label">ระดับชั้นนักเรียน</div>
                <select class="form-control">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option>ม.1</option>
                    <option>ม.2</option>
                    <option>ม.3</option>
                    <option>ม.4</option>
                    <option>ม.5</option>
                    <option>ม.6</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">สถานะการใช้งาน</div>
                <select class="form-control">
                    <option value="">-- ทั้งหมด --</option>
                    <option selected>เปิดใช้งาน</option>
                    <option>ระงับการใช้งาน</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="20%">ชื่อ-นามสกุล</th>
                        <th width="15%">หมายเลขโทรศัพท์</th>
                        <th width="15%">ความสัมพันธ์</th>
                        <th width="15%">จำนวนนักเรียนในปกครอง</th>
                        <th width="15%">LINE ผู้ปกครอง</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ว</div>
                                <div class="student-details">
                                    <div class="student-name">นางวันดี สุขใจ</div>
                                    <div class="student-class">ผู้ปกครอง 1 คน</div>
                                </div>
                            </div>
                        </td>
                        <td>081-234-5678</td>
                        <td>มารดา</td>
                        <td>1 คน</td>
                        <td>
                            <span class="status-badge success">เชื่อมต่อแล้ว</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูข้อมูล" onclick="viewParentDetails(1)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="แก้ไข" onclick="editParent(1)">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn danger" title="ลบ" onclick="deleteParent(1)">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="pagination-container">
            <div class="pagination">
                <a href="#" class="page-link">«</a>
                <a href="#" class="page-link active">1</a>
                <a href="#" class="page-link">2</a>
                <a href="#" class="page-link">3</a>
                <a href="#" class="page-link">»</a>
            </div>
            <div class="page-info">
                แสดง 1-1 จาก 1 รายการ
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บเพิ่มผู้ปกครอง -->
<div id="parent-add-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">person_add</span>
            เพิ่มข้อมูลผู้ปกครองใหม่
        </div>
        
        <form id="addParentForm" class="form-horizontal">
            <div class="form-section">
                <h3 class="section-title">ข้อมูลส่วนตัว</h3>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">คำนำหน้า</label>
                            <select class="form-control" required>
                                <option value="">-- เลือกคำนำหน้า --</option>
                                <option>นาย</option>
                                <option>นาง</option>
                                <option>นางสาว</option>
                                <option>ดร.</option>
                                <option>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้าอื่นๆ (ถ้ามี)</label>
                            <input type="text" class="form-control" placeholder="ระบุคำนำหน้าอื่นๆ">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">ชื่อ</label>
                            <input type="text" class="form-control" placeholder="ชื่อผู้ปกครอง" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">นามสกุล</label>
                            <input type="text" class="form-control" placeholder="นามสกุลผู้ปกครอง" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">หมายเลขบัตรประชาชน</label>
                            <input type="text" class="form-control" placeholder="เลขประจำตัวประชาชน 13 หลัก" required maxlength="13">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">ความสัมพันธ์กับนักเรียน</label>
                            <select class="form-control" required>
                                <option value="">-- เลือกความสัมพันธ์ --</option>
                                <option>บิดา</option>
                                <option>มารดา</option>
                                <option>ปู่</option>
                                <option>ย่า</option>
                                <option>ตา</option>
                                <option>ยาย</option>
                                <option>ลุง</option>
                                <option>ป้า</option>
                                <option>น้า</option>
                                <option>อา</option>
                                <option>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการติดต่อ</h3>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">หมายเลขโทรศัพท์</label>
                            <input type="tel" class="form-control" placeholder="หมายเลขโทรศัพท์มือถือ" required>
                            <div class="form-text">* ใช้สำหรับการเชื่อมต่อกับ LINE และการแจ้งเตือน</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" placeholder="อีเมล (ถ้ามี)">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">ที่อยู่ปัจจุบัน</label>
                    <textarea class="form-control" rows="3" placeholder="ที่อยู่ปัจจุบันที่สามารถติดต่อได้" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ตำบล/แขวง</label>
                            <input type="text" class="form-control" placeholder="ตำบล/แขวง">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">อำเภอ/เขต</label>
                            <input type="text" class="form-control" placeholder="อำเภอ/เขต">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">จังหวัด</label>
                            <select class="form-control">
                                <option value="">-- เลือกจังหวัด --</option>
                                <option>กรุงเทพมหานคร</option>
                                <option>กระบี่</option>
                                <option>กาญจนบุรี</option>
                                <!-- เพิ่มจังหวัดอื่นๆ ต่อไป -->
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control" placeholder="รหัสไปรษณีย์" maxlength="5">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการเชื่อมต่อ LINE</h3>
                
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="generateQRCode">
                                <label class="form-check-label" for="generateQRCode">สร้าง QR Code สำหรับการเชื่อมต่อ LINE</label>
                            </div>
                            <div class="form-text">* ระบบจะสร้าง QR Code สำหรับการเชื่อมต่อบัญชี LINE ของผู้ปกครองกับระบบ</div>
                        </div>
                    </div>
                </div>
                
                <div id="qrCodeSection" style="display: none; text-align: center; margin-top: 15px;">
                    <img src="/api/placeholder/150/150" alt="QR Code ตัวอย่าง" style="margin-bottom: 10px;">
                    <p>สแกน QR Code นี้เพื่อเพิ่มเพื่อน LINE Official Account: SADD-Prasat</p>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลนักเรียนในความปกครอง</h3>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="5%">เลือก</th>
                                <th width="25%">ชื่อ-นามสกุลนักเรียน</th>
                                <th width="20%">ชั้น/ห้อง</th>
                                <th width="20%">เลขประจำตัว</th>
                                <th width="30%">ความสัมพันธ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <button type="button" class="btn btn-secondary" onclick="searchStudents()">
                        <span class="material-icons">search</span>
                        ค้นหานักเรียน
                    </button>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">การตั้งค่าเพิ่มเติม</h3>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="enableNotifications" checked>
                        <label class="form-check-label" for="enableNotifications">เปิดใช้งานการแจ้งเตือนผ่าน LINE</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="enableAccountActive" checked>
                        <label class="form-check-label" for="enableAccountActive">เปิดใช้งานบัญชีผู้ปกครอง</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<!-- เนื้อหาแท็บนำเข้าข้อมูล -->
<div id="parent-import-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">upload_file</span>
            นำเข้าข้อมูลผู้ปกครอง
        </div>
        
        <div class="import-container">
            <div class="upload-section">
                <p class="upload-info">อัปโหลดไฟล์ CSV หรือ Excel ที่มีข้อมูลผู้ปกครอง</p>
                <div class="upload-area">
                    <input type="file" id="fileUpload" class="file-input" accept=".csv, .xlsx, .xls">
                    <label for="fileUpload" class="file-label">
                        <span class="material-icons">cloud_upload</span>
                        <span>เลือกไฟล์หรือลากไฟล์มาวางที่นี่</span>
                        <span class="file-format">(รองรับไฟล์ CSV, Excel)</span>
                    </label>
                </div>
            </div>
            
            <div class="format-example">
                <h3 class="format-title">รูปแบบไฟล์ที่รองรับ</h3>
                <p>ไฟล์ที่อัปโหลดควรมีคอลัมน์ดังต่อไปนี้:</p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>คำนำหน้า</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>เลขประจำตัวประชาชน</th>
                                <th>เบอร์โทรศัพท์</th>
                                <th>อีเมล</th>
                                <th>ที่อยู่</th>
                                <th>ความสัมพันธ์</th>
                                <th>รหัสนักเรียน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>นาง</td>
                                <td>วันดี</td>
                                <td>สุขใจ</td>
                                <td>1234567890123</td>
                                <td>0812345678</td>
                                <td>example@mail.com</td>
                                <td>123 หมู่ 4 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000</td>
                                <td>มารดา</td>
                                <td>12345</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="template-download">
                    <a href="#" class="btn btn-secondary btn-sm">
                        <span class="material-icons">file_download</span>
                        ดาวน์โหลดเทมเพลต Excel
                    </a>
                    <a href="#" class="btn btn-secondary btn-sm">
                        <span class="material-icons">file_download</span>
                        ดาวน์โหลดเทมเพลต CSV
                    </a>
                </div>
            </div>
            
            <div class="import-actions">
                <button type="button" class="btn btn-secondary" onclick="resetImport()">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="importParents()" disabled id="importButton">
                    <span class="material-icons">upload</span>
                    นำเข้าข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงรายละเอียดผู้ปกครอง -->
<div class="modal" id="parentDetailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('parentDetailModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ข้อมูลผู้ปกครอง - นางวันดี สุขใจ</h2>
        
        <div class="parent-profile">
            <div class="profile-header">
                <div class="profile-avatar">ว</div>
                <div class="profile-info">
                    <h3>นางวันดี สุขใจ</h3>
                    <p><strong>ความสัมพันธ์:</strong> มารดา</p>
                    <p><strong>เบอร์โทรศัพท์:</strong> 081-234-5678</p>
                    <p><strong>LINE สถานะ:</strong> <span class="status-badge success">เชื่อมต่อแล้ว</span></p>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>ข้อมูลส่วนตัว</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <tbody>
                            <tr>
                                <td width="200"><strong>เลขประจำตัวประชาชน:</strong></td>
                                <td>1-2345-67890-12-3</td>
                            </tr>
                            <tr>
                                <td><strong>อีเมล:</strong></td>
                                <td>wandee@example.com</td>
                            </tr>
                            <tr>
                                <td><strong>ที่อยู่:</strong></td>
                                <td>123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>นักเรียนในความปกครอง</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ชั้น/ห้อง</th>
                                <th>เลขประจำตัว</th>
                                <th>ความสัมพันธ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">ธ</div>
                                        <div class="student-details">
                                            <div class="student-name">นายธนกฤต สุขใจ</div>
                                            <div class="student-class">เลขที่ 12</div>
                                        </div>
                                    </div>
                                </td>
                                <td>ม.6/2</td>
                                <td>12345</td>
                                <td>มารดา</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>ประวัติการรับข้อความแจ้งเตือน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>เรื่อง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>16/03/2568 09:30</td>
                                <td>แจ้งเตือนความเสี่ยง</td>
                                <td>แจ้งเตือนนักเรียนเสี่ยงตกกิจกรรม</td>
                                <td><span class="status-badge success">อ่านแล้ว</span></td>
                            </tr>
                            <tr>
                                <td>10/03/2568 08:45</td>
                                <td>แจ้งเตือนทั่วไป</td>
                                <td>รายงานการเข้าแถวประจำสัปดาห์</td>
                                <td><span class="status-badge success">อ่านแล้ว</span></td>
                            </tr>
                            <tr>
                                <td>01/03/2568 10:15</td>
                                <td>แจ้งเตือนทั่วไป</td>
                                <td>นัดประชุมผู้ปกครอง</td>
                                <td><span class="status-badge warning">ยังไม่อ่าน</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('parentDetailModal')">ปิด</button>
            <button class="btn btn-primary" onclick="editParent(1)">
                <span class="material-icons">edit</span>
                แก้ไขข้อมูล
            </button>
            <button class="btn btn-success" onclick="sendDirectMessage(1)">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลค้นหานักเรียน -->
<div class="modal" id="searchStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('searchStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ค้นหานักเรียน</h2>
        
        <div class="search-container">
            <div class="filter-container">
                <div class="filter-group">
                    <div class="filter-label">ชื่อ-นามสกุลนักเรียน</div>
                    <input type="text" class="form-control" placeholder="ป้อนชื่อนักเรียน...">
                </div>
                <div class="filter-group">
                    <div class="filter-label">ระดับชั้น</div>
                    <select class="form-control">
                        <option value="">-- ทุกระดับชั้น --</option>
                        <option>ม.1</option>
                        <option>ม.2</option>
                        <option>ม.3</option>
                        <option>ม.4</option>
                        <option>ม.5</option>
                        <option>ม.6</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">ห้องเรียน</div>
                    <select class="form-control">
                        <option value="">-- ทุกห้อง --</option>
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </div>
                <button class="filter-button">
                    <span class="material-icons">search</span>
                    ค้นหา
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">เลือก</th>
                            <th width="25%">ชื่อ-นามสกุล</th>
                            <th width="10%">ชั้น/ห้อง</th>
                            <th width="10%">เลขที่</th>
                            <th width="15%">เลขประจำตัว</th>
                            <th width="15%">ผู้ปกครองปัจจุบัน</th>
                            <th width="20%">ความสัมพันธ์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="checkbox" class="student-select">
                            </td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">ธ</div>
                                    <div class="student-details">
                                        <div class="student-name">นายธนกฤต สุขใจ</div>
                                        <div class="student-class">เลขที่ 12</div>
                                    </div>
                                </div>
                            </td>
                            <td>ม.6/2</td>
                            <td>12</td>
                            <td>12345</td>
                            <td>นางวันดี สุขใจ</td>
                            <td>
                                <select class="form-control form-control-sm">
                                    <option value="">-- เลือก --</option>
                                    <option selected>มารดา</option>
                                    <option>บิดา</option>
                                    <option>ผู้ปกครอง</option>
                                    <option>อื่นๆ</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('searchStudentModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="addSelectedStudents()">
                <span class="material-icons">add</span>
                เพิ่มนักเรียนที่เลือก
            </button>
        </div>
    </div>
</div>

<!-- โมดัลส่งข้อความถึงผู้ปกครอง -->
<div class="modal" id="sendMessageModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('sendMessageModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ส่งข้อความถึงผู้ปกครอง - นางวันดี สุขใจ</h2>
        
        <div class="template-buttons">
            <button class="template-btn active" onclick="selectTemplate('regular')">ข้อความทั่วไป</button>
            <button class="template-btn" onclick="selectTemplate('meeting')">นัดประชุม</button>
            <button class="template-btn" onclick="selectTemplate('warning')">แจ้งเตือน</button>
            <button class="template-btn" onclick="selectTemplate('report')">รายงานผล</button>
        </div>
        
        <div class="message-form">
            <textarea class="message-textarea" id="messageText">เรียน คุณวันดี สุขใจ

ทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>
            
            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="preview-button" onclick="showMessagePreview()">
                        <span class="material-icons">visibility</span>
                        แสดงตัวอย่าง
                    </button>
                </div>
                <div class="preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p style="margin-top: 10px;">เรียน คุณวันดี สุขใจ<br><br>ทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>โรงเรียนประสาทวิทยาคม</p>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('sendMessageModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="sendDirectMessage()">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('confirmDeleteModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการลบข้อมูล</h2>
        
        <div class="confirmation-message">
            <p>คุณต้องการลบข้อมูลผู้ปกครอง "นางวันดี สุขใจ" ใช่หรือไม่?</p>
            <p class="warning-text">คำเตือน: การลบข้อมูลผู้ปกครองจะทำให้ไม่สามารถส่งข้อความถึงผู้ปกครองได้อีก และข้อมูลผู้ปกครองทั้งหมดจะถูกลบออกจากระบบ</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">ยกเลิก</button>
            <button class="btn btn-danger" onclick="confirmDelete()">
                <span class="material-icons">delete</span>
                ยืนยันการลบ
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

// แสดงข้อมูลผู้ปกครอง
function viewParentDetails(parentId) {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปดึงข้อมูลจาก backend
    console.log(`ดูข้อมูลผู้ปกครอง ID: ${parentId}`);
    showModal('parentDetailModal');
}

// แก้ไขข้อมูลผู้ปกครอง
function editParent(parentId) {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปดึงข้อมูลจาก backend
    console.log(`แก้ไขข้อมูลผู้ปกครอง ID: ${parentId}`);
    // เปลี่ยนไปยังแท็บเพิ่มข้อมูล (แต่จะใช้สำหรับการแก้ไขแทน)
    showTab('parent-add');
    // ตั้งค่าฟอร์มด้วยข้อมูลที่มีอยู่
    // (ในทางปฏิบัติจริง จะเติมข้อมูลในฟอร์มด้วยข้อมูลที่ดึงมาจาก backend)
}

// ลบข้อมูลผู้ปกครอง
function deleteParent(parentId) {
    // แสดงโมดัลยืนยันการลบ
    console.log(`เตรียมลบข้อมูลผู้ปกครอง ID: ${parentId}`);
    showModal('confirmDeleteModal');
}

// ยืนยันการลบ
function confirmDelete() {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปลบข้อมูลใน backend
    console.log('ยืนยันการลบข้อมูลผู้ปกครอง');
    closeModal('confirmDeleteModal');
    // แสดงข้อความแจ้งเตือนการลบสำเร็จ
    showAlert('ลบข้อมูลผู้ปกครองเรียบร้อยแล้ว', 'success');
}

// ค้นหานักเรียน (สำหรับเพิ่มในรายการผู้ปกครอง)
function searchStudents() {
    showModal('searchStudentModal');
}

// เพิ่มนักเรียนที่เลือกไว้ลงในตาราง
function addSelectedStudents() {
    // ในทางปฏิบัติจริง จะเพิ่มนักเรียนที่เลือกลงในตารางนักเรียนในความปกครอง
    console.log('เพิ่มนักเรียนที่เลือก');
    closeModal('searchStudentModal');
}

// แสดงตัวอย่างข้อความที่จะส่ง
function showMessagePreview() {
    const messageText = document.getElementById('messageText').value;
    // ในทางปฏิบัติจริง จะแสดงข้อความตัวอย่างในรูปแบบที่จะปรากฏบน LINE
    console.log('แสดงตัวอย่างข้อความ:', messageText);
}

// ส่งข้อความถึงผู้ปกครอง
function sendDirectMessage(parentId) {
    // แสดงโมดัลส่งข้อความ
    console.log(`เตรียมส่งข้อความถึงผู้ปกครอง ID: ${parentId}`);
    showModal('sendMessageModal');
}

// เลือกเทมเพลตข้อความ
function selectTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    event.target.classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    
    switch(templateType) {
        case 'regular':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'meeting':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอเรียนเชิญท่านเข้าร่วมประชุมผู้ปกครองนักเรียนชั้น ม.6/2 ในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nจึงเรียนมาเพื่อทราบและขอเชิญเข้าร่วมประชุมตามวันและเวลาดังกล่าว\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'warning':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอแจ้งให้ทราบว่า นายธนกฤต สุขใจ มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'report':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 26 วัน จากทั้งหมด 40 วัน (65%)\nจำนวนวันขาดแถว: 14 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
    }
}

// รีเซ็ตฟอร์ม
function resetForm() {
    document.getElementById('addParentForm').reset();
}

// แสดง QR Code สำหรับการเชื่อมต่อ LINE
document.getElementById('generateQRCode').addEventListener('change', function() {
    const qrCodeSection = document.getElementById('qrCodeSection');
    if (this.checked) {
        qrCodeSection.style.display = 'block';
    } else {
        qrCodeSection.style.display = 'none';
    }
});

// รีเซ็ตฟอร์มนำเข้าข้อมูล
function resetImport() {
    document.getElementById('fileUpload').value = '';
    document.getElementById('importButton').disabled = true;
}

// นำเข้าข้อมูลผู้ปกครอง
function importParents() {
    // ในทางปฏิบัติจริง จะส่งไฟล์ไปยัง backend เพื่อประมวลผล
    console.log('นำเข้าข้อมูลผู้ปกครอง');
    // แสดงข้อความแจ้งเตือนการนำเข้าสำเร็จ
    showAlert('นำเข้าข้อมูลผู้ปกครองเรียบร้อยแล้ว', 'success');
}

// เมื่อมีการเลือกไฟล์ เปิดใช้งานปุ่มนำเข้า
document.getElementById('fileUpload').addEventListener('change', function() {
    document.getElementById('importButton').disabled = !this.files.length;
});

// แสดงโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// ปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// แสดงการแจ้งเตือน
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            alertContainer.removeChild(alert);
        }, 300);
    });
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
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
/* เพิ่มเติมสำหรับหน้าจัดการผู้ปกครอง */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.page-info {
    font-size: 14px;
    color: var(--text-light);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--primary-color);
    display: flex;
    align-items: center;
}

.section-title::before {
    content: "";
    display: inline-block;
    width: 4px;
    height: 18px;
    background-color: var(--primary-color);
    margin-right: 10px;
}

.form-label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

.form-label.required::after {
    content: "*";
    color: var(--danger-color);
    margin-left: 5px;
}

.form-text {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 5px;
}

.form-check {
    display: flex;
    align-items: center;
}

.form-check-input {
    margin-right: 10px;
}

.profile-header {
    display: flex;
    margin-bottom: 20px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    color: var(--secondary-color);
    font-size: 36px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.profile-info h3 {
    font-size: 22px;
    margin: 0 0 10px 0;
}

.profile-info p {
    margin: 0 0 5px 0;
}

.profile-section {
    margin-bottom: 25px;
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
}

.profile-section h4 {
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.upload-section {
    margin-bottom: 20px;
}

.upload-info {
    margin-bottom: 10px;
    font-size: 14px;
}

.upload-area {
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    background-color: #f9f9f9;
    transition: all 0.3s;
}

.upload-area:hover {
    border-color: var(--primary-color);
    background-color: var(--primary-color-light);
}

.file-input {
    display: none;
}

.file-label {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.file-label .material-icons {
    font-size: 48px;
    color: var(--text-light);
    margin-bottom: 10px;
}

.file-format {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 5px;
}

.format-example {
    margin-top: 30px;
}

.format-title {
    font-size: 16px;
    margin-bottom: 10px;
}

.template-download {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.import-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.confirmation-message {
    text-align: center;
    margin: 20px 0;
}

.warning-text {
    color: var(--danger-color);
    font-weight: 500;
    margin-top: 10px;
}

.text-center {
    text-align: center;
}

/* รองรับการแสดงผลบนมือถือ */
@media (max-width: 768px) {
    .row {
        flex-direction: column;
    }
    
    .col-6 {
        width: 100%;
    }
    
    .profile-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .form-section {
        padding: 15px;
    }
}
</style>
<!-- ตัวกรองข้อมูลนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ค้นหาและกรองข้อมูลนักเรียน
    </div>
    
    <div class="filter-container">
        <div class="filter-group">
            <div class="filter-label">ชื่อ-นามสกุล</div>
            <input type="text" class="form-control" placeholder="ป้อนชื่อนักเรียน...">
        </div>
        <div class="filter-group">
            <div class="filter-label">รหัสนักเรียน</div>
            <input type="text" class="form-control" placeholder="ป้อนรหัสนักเรียน...">
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
        <div class="filter-group">
            <div class="filter-label">สถานะการเข้าแถว</div>
            <select class="form-control">
                <option value="">-- ทุกสถานะ --</option>
                <option>เสี่ยงตกกิจกรรม</option>
                <option>ต้องระวัง</option>
                <option>ปกติ</option>
            </select>
        </div>
        <button class="filter-button">
            <span class="material-icons">search</span>
            ค้นหา
        </button>
    </div>
</div>

<!-- สรุปข้อมูลนักเรียน -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนทั้งหมด</div>
            <div class="stat-icon blue">
                <span class="material-icons">groups</span>
            </div>
        </div>
        <div class="stat-value">1,250</div>
        <div class="stat-comparison up">
            <span class="material-icons">arrow_upward</span>
            เพิ่มขึ้น 2.5% จากปีที่แล้ว
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนชาย</div>
            <div class="stat-icon blue">
                <span class="material-icons">person</span>
            </div>
        </div>
        <div class="stat-value">600</div>
        <div class="stat-comparison">
            48% ของนักเรียนทั้งหมด
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนหญิง</div>
            <div class="stat-icon blue">
                <span class="material-icons">person</span>
            </div>
        </div>
        <div class="stat-value">650</div>
        <div class="stat-comparison">
            52% ของนักเรียนทั้งหมด
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนเสี่ยงตกกิจกรรม</div>
            <div class="stat-icon red">
                <span class="material-icons">warning</span>
            </div>
        </div>
        <div class="stat-value">12</div>
        <div class="stat-comparison down">
            <span class="material-icons">arrow_downward</span>
            ลดลง 5 คนจากสัปดาห์ที่แล้ว
        </div>
    </div>
</div>

<!-- รายการนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
    </div>
    
    <div class="bulk-actions">
        <button class="btn btn-secondary">
            <span class="material-icons">print</span>
            พิมพ์รายชื่อ
        </button>
        <button class="btn btn-secondary">
            <span class="material-icons">file_download</span>
            ดาวน์โหลด Excel
        </button>
        <button class="btn btn-primary" onclick="showAddStudentModal()">
            <span class="material-icons">person_add</span>
            เพิ่มนักเรียนใหม่
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">รหัส</th>
                    <th width="25%">ชื่อ-นามสกุล</th>
                    <th width="10%">ชั้น/ห้อง</th>
                    <th width="10%">เลขที่</th>
                    <th width="15%">การเข้าแถว</th>
                    <th width="15%">สถานะ</th>
                    <th width="20%">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['students'] as $student): ?>
                <tr>
                    <td><?php echo $student['student_id']; ?></td>
                    <td>
                        <div class="student-info">
                            <div class="student-avatar"><?php echo mb_substr($student['firstname'], 0, 1, 'UTF-8'); ?></div>
                            <div class="student-details">
                                <div class="student-name"><?php echo $student['title'] . $student['firstname'] . ' ' . $student['lastname']; ?></div>
                                <div class="student-class">เลขที่ <?php echo $student['class_number']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $student['class']; ?></td>
                    <td><?php echo $student['class_number']; ?></td>
                    <td><?php echo $student['attendance_rate']; ?>%</td>
                    <td>
                        <?php
                        $status_class = '';
                        if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
                            $status_class = 'danger';
                        } elseif ($student['attendance_status'] === 'ต้องระวัง') {
                            $status_class = 'warning';
                        } else {
                            $status_class = 'success';
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $student['attendance_status']; ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="table-action-btn primary" title="ดูข้อมูล" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" title="แก้ไข" onclick="editStudent(<?php echo $student['id']; ?>)">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="table-action-btn danger" title="ลบ" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="pagination">
        <a href="#" class="page-item active">1</a>
        <a href="#" class="page-item">2</a>
        <a href="#" class="page-item">3</a>
        <span class="page-separator">...</span>
        <a href="#" class="page-item">10</a>
    </div>
</div>

<!-- โมดัลเพิ่มนักเรียนใหม่ -->
<div class="modal" id="addStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('addStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">เพิ่มนักเรียนใหม่</h2>
        
        <form id="addStudentForm" method="post" action="students.php">
            <input type="hidden" name="action" value="add">
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลส่วนตัว</h3>
                
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้า</label>
                            <select class="form-control" name="title" required>
                                <option value="">-- เลือก --</option>
                                <option value="นาย">นาย</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="เด็กชาย">เด็กชาย</option>
                                <option value="เด็กหญิง">เด็กหญิง</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน</label>
                            <input type="text" class="form-control" name="student_id" required>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">เพศ</label>
                            <select class="form-control" name="gender" required>
                                <option value="">-- เลือก --</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">วันเกิด</label>
                            <input type="date" class="form-control" name="birth_date">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ที่อยู่</label>
                    <textarea class="form-control" name="address" rows="2"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการศึกษา</h3>
                
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">ระดับชั้น</label>
                            <select class="form-control" name="class_level" required>
                                <option value="">-- เลือก --</option>
                                <option value="ม.1">ม.1</option>
                                <option value="ม.2">ม.2</option>
                                <option value="ม.3">ม.3</option>
                                <option value="ม.4">ม.4</option>
                                <option value="ม.5">ม.5</option>
                                <option value="ม.6">ม.6</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">ห้อง</label>
                            <select class="form-control" name="class_room" required>
                                <option value="">-- เลือก --</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">เลขที่</label>
                            <input type="number" class="form-control" name="class_number" required min="1" max="50">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">ครูที่ปรึกษา</label>
                            <select class="form-control" name="advisor">
                                <option value="">-- เลือก --</option>
                                <option value="อ.ประสิทธิ์ ดีเลิศ">อ.ประสิทธิ์ ดีเลิศ</option>
                                <option value="อ.วันดี สดใส">อ.วันดี สดใส</option>
                                <option value="อ.ใจดี มากเมตตา">อ.ใจดี มากเมตตา</option>
                                <option value="อ.วิชัย สุขสวัสดิ์">อ.วิชัย สุขสวัสดิ์</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลผู้ปกครอง</h3>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ชื่อ-นามสกุลผู้ปกครอง</label>
                            <input type="text" class="form-control" name="parent_name">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">ความสัมพันธ์</label>
                            <select class="form-control" name="parent_relation">
                                <option value="">-- เลือก --</option>
                                <option value="บิดา">บิดา</option>
                                <option value="มารดา">มารดา</option>
                                <option value="ลุง">ลุง</option>
                                <option value="ป้า">ป้า</option>
                                <option value="อา">อา</option>
                                <option value="น้า">น้า</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" name="parent_phone">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">LINE ID (สำหรับรับการแจ้งเตือน)</label>
                    <input type="text" class="form-control" name="line_id">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลแก้ไขข้อมูลนักเรียน (โครงสร้างคล้ายกับโมดัลเพิ่ม) -->
<div class="modal" id="editStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('editStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แก้ไขข้อมูลนักเรียน</h2>
        
        <form id="editStudentForm" method="post" action="students.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">
            
            <!-- ฟอร์มแก้ไขข้อมูลนักเรียน (เหมือนกับฟอร์มเพิ่ม) -->
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลดูข้อมูลนักเรียน -->
<div class="modal" id="viewStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('viewStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ข้อมูลนักเรียน</h2>
        
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar">ธ</div>
                <div class="student-profile-info">
                    <h3>นายธนกฤต สุขใจ</h3>
                    <p>รหัสนักเรียน: 16478</p>
                    <p>ชั้น ม.6/2 เลขที่ 12</p>
                    <p>เพศ: ชาย | อายุ: 17 ปี (เกิด: 15 พ.ค. 2551)</p>
                </div>
            </div>
            
            <div class="info-sections">
                <div class="info-section">
                    <h4>ข้อมูลติดต่อ</h4>
                    <p><strong>ที่อยู่:</strong> 123 หมู่ 4 ต.ปราสาท อ.เมือง จ.สุรินทร์ 32000</p>
                    <p><strong>LINE ID:</strong> tanakit_s</p>
                </div>
                
                <div class="info-section">
                    <h4>ข้อมูลผู้ปกครอง</h4>
                    <p><strong>ชื่อผู้ปกครอง:</strong> นางวันดี สุขใจ (มารดา)</p>
                    <p><strong>เบอร์โทรศัพท์:</strong> 081-234-5678</p>
                </div>
                
                <div class="info-section">
                    <h4>ข้อมูลการศึกษา</h4>
                    <p><strong>ครูที่ปรึกษา:</strong> อ.ประสิทธิ์ ดีเลิศ</p>
                    <p><strong>สถานะการเข้าแถว:</strong> <span class="status-badge danger">เสี่ยงตกกิจกรรม (65.8%)</span></p>
                </div>
                
                <div class="info-section">
                    <h4>สถิติการเข้าแถว</h4>
                    <div class="attendance-stats">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">26</div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">14</div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">40</div>
                            <div class="attendance-stat-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewStudentModal')">ปิด</button>
                <button type="button" class="btn btn-primary" onclick="editStudent(1)">
                    <span class="material-icons">edit</span>
                    แก้ไขข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลลบนักเรียน -->
<div class="modal" id="deleteStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('deleteStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการลบข้อมูล</h2>
        
        <div class="confirmation-message">
            <p>คุณต้องการลบข้อมูลนักเรียน <strong id="delete_student_name">นายธนกฤต สุขใจ</strong> ใช่หรือไม่?</p>
            <p class="warning-text">คำเตือน: การลบข้อมูลจะไม่สามารถกู้คืนได้</p>
        </div>
        
        <form id="deleteStudentForm" method="post" action="students.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="student_id" id="delete_student_id">
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteStudentModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-danger">
                    <span class="material-icons">delete</span>
                    ยืนยันการลบ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลนำเข้าข้อมูลนักเรียน -->
<div class="modal" id="importModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('importModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">นำเข้าข้อมูลนักเรียน</h2>
        
        <form id="importForm" method="post" action="students.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            
            <div class="form-group">
                <label class="form-label">เลือกไฟล์ Excel (.xlsx, .xls)</label>
                <input type="file" class="form-control" name="import_file" accept=".xlsx,.xls" required>
            </div>
            
            <div class="import-instructions">
                <h4>คำแนะนำการนำเข้าข้อมูล</h4>
                <ol>
                    <li>ไฟล์ Excel ต้องมีรูปแบบตามที่กำหนด (คลิก <a href="#">ดาวน์โหลดตัวอย่าง</a>)</li>
                    <li>ต้องมีหัวตารางในแถวแรกเสมอ</li>
                    <li>ข้อมูลที่จำเป็นต้องมี: รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล, ระดับชั้น, ห้อง, เลขที่</li>
                    <li>ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                    <li>ระบบจะอัพเดทข้อมูลอัตโนมัติ หากพบรหัสนักเรียนซ้ำ</li>
                </ol>
            </div>
            
            <div class="import-options">
                <div class="checkbox-item">
                    <input type="checkbox" id="update_existing" name="update_existing" checked>
                    <label for="update_existing">อัพเดทข้อมูลที่มีอยู่แล้ว</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="skip_header" name="skip_header" checked>
                    <label for="skip_header">ข้ามแถวแรก (หัวตาราง)</label>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('importModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">upload_file</span>
                    นำเข้าข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ฟังก์ชันแสดงโมดัลเพิ่มนักเรียน
function showAddStudentModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addStudentForm').reset();
    
    // แสดงโมดัล
    showModal('addStudentModal');
}

// ฟังก์ชันแสดงโมดัลแก้ไขข้อมูลนักเรียน
function editStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลนักเรียนมาแสดงในฟอร์ม
    document.getElementById('edit_student_id').value = studentId;
    
    // แสดงโมดัล
    showModal('editStudentModal');
    
    // จำลองการดึงข้อมูล
    console.log('Edit student ID: ' + studentId);
}

// ฟังก์ชันแสดงโมดัลดูข้อมูลนักเรียน
function viewStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลนักเรียนมาแสดง
    
    // แสดงโมดัล
    showModal('viewStudentModal');
    
    // จำลองการดึงข้อมูล
    console.log('View student ID: ' + studentId);
}

// ฟังก์ชันแสดงโมดัลลบข้อมูลนักเรียน
function deleteStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลชื่อนักเรียนมาแสดง
    document.getElementById('delete_student_id').value = studentId;
    
    // แสดงโมดัล
    showModal('deleteStudentModal');
    
    // จำลองการดึงข้อมูล
    console.log('Delete student ID: ' + studentId);
}

// ฟังก์ชันแสดงโมดัลนำเข้าข้อมูล
function showImportModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('importForm').reset();
    
    // แสดงโมดัล
    showModal('importModal');
}

// ฟังก์ชันแสดงโมดัล
function showModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

// ฟังก์ชันซ่อนโมดัล
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}
</script>

<style>
/* เพิ่มเติมสำหรับหน้าจัดการข้อมูลนักเรียน */
.form-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-size: 16px;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.bulk-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.student-profile {
    padding: 15px 0;
}

.student-profile-header {
    display: flex;
    margin-bottom: 20px;
}

.student-profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    margin-right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--secondary-color);
    font-size: 32px;
    font-weight: bold;
}

.student-profile-info h3 {
    font-size: 20px;
    margin-bottom: 5px;
}

.student-profile-info p {
    margin: 0 0 5px 0;
    color: var(--text-light);
}

.info-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-section {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.info-section h4 {
    font-size: 16px;
    margin-bottom: 10px;
    color: var(--text-dark);
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.info-section p {
    margin: 0 0 5px 0;
}

.attendance-stats {
    display: flex;
    gap: 15px;
}

.attendance-stat {
    flex: 1;
    background-color: white;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.attendance-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary-color);
}

.attendance-stat-label {
    font-size: 12px;
    color: var(--text-light);
}

.confirmation-message {
    text-align: center;
    margin: 20px 0;
}

.warning-text {
    color: var(--danger-color);
    font-weight: bold;
    margin-top: 10px;
}

.import-instructions {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.import-instructions h4 {
    font-size: 16px;
    margin-bottom: 10px;
}

.import-instructions ol {
    margin-left: 20px;
}

.import-instructions li {
    margin-bottom: 5px;
}

.import-options {
    margin: 15px 0;
}

.checkbox-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.checkbox-item input {
    margin-right: 10px;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 5px;
}

.page-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    background-color: #f5f5f5;
    color: var(--text-dark);
    text-decoration: none;
}

.page-item.active {
    background-color: var(--primary-color);
    color: white;
}

.page-separator {
    display: flex;
    align-items: center;
    padding: 0 5px;
}

@media (max-width: 768px) {
    .info-sections {
        grid-template-columns: 1fr;
    }
    
    .student-profile-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .student-profile-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
}
</style>
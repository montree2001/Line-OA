<!-- ตัวกรองข้อมูลนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ค้นหาและกรองข้อมูลนักเรียน
    </div>
    
    <div class="filter-container">
        <form method="get" action="students.php" class="filter-form">
            <div class="filter-group">
                <div class="filter-label">ชื่อ-นามสกุล</div>
                <input type="text" class="form-control" name="name" placeholder="ป้อนชื่อนักเรียน..." value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
            </div>
            <div class="filter-group">
                <div class="filter-label">รหัสนักเรียน</div>
                <input type="text" class="form-control" name="student_code" placeholder="ป้อนรหัสนักเรียน..." value="<?php echo isset($_GET['student_code']) ? htmlspecialchars($_GET['student_code']) : ''; ?>">
            </div>
            <div class="filter-group">
                <div class="filter-label">ระดับชั้น</div>
                <select class="form-control" name="level">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option value="ปวช.1" <?php echo (isset($_GET['level']) && $_GET['level'] === 'ปวช.1') ? 'selected' : ''; ?>>ปวช.1</option>
                    <option value="ปวช.2" <?php echo (isset($_GET['level']) && $_GET['level'] === 'ปวช.2') ? 'selected' : ''; ?>>ปวช.2</option>
                    <option value="ปวช.3" <?php echo (isset($_GET['level']) && $_GET['level'] === 'ปวช.3') ? 'selected' : ''; ?>>ปวช.3</option>
                    <option value="ปวส.1" <?php echo (isset($_GET['level']) && $_GET['level'] === 'ปวส.1') ? 'selected' : ''; ?>>ปวส.1</option>
                    <option value="ปวส.2" <?php echo (isset($_GET['level']) && $_GET['level'] === 'ปวส.2') ? 'selected' : ''; ?>>ปวส.2</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">ห้องเรียน</div>
                <select class="form-control" name="room">
                    <option value="">-- ทุกห้อง --</option>
                    <option value="1" <?php echo (isset($_GET['room']) && $_GET['room'] === '1') ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo (isset($_GET['room']) && $_GET['room'] === '2') ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo (isset($_GET['room']) && $_GET['room'] === '3') ? 'selected' : ''; ?>>3</option>
                    <option value="4" <?php echo (isset($_GET['room']) && $_GET['room'] === '4') ? 'selected' : ''; ?>>4</option>
                    <option value="5" <?php echo (isset($_GET['room']) && $_GET['room'] === '5') ? 'selected' : ''; ?>>5</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">สถานะการเข้าแถว</div>
                <select class="form-control" name="status">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="เสี่ยงตกกิจกรรม" <?php echo (isset($_GET['status']) && $_GET['status'] === 'เสี่ยงตกกิจกรรม') ? 'selected' : ''; ?>>เสี่ยงตกกิจกรรม</option>
                    <option value="ต้องระวัง" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ต้องระวัง') ? 'selected' : ''; ?>>ต้องระวัง</option>
                    <option value="ปกติ" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ปกติ') ? 'selected' : ''; ?>>ปกติ</option>
                </select>
            </div>
            <button type="submit" class="filter-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </form>
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
        <div class="stat-value"><?php echo isset($data['statistics']['total']) ? number_format($data['statistics']['total']) : 0; ?></div>
        <div class="stat-comparison">
            จำนวนนักเรียนที่กำลังศึกษา
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนชาย</div>
            <div class="stat-icon blue">
                <span class="material-icons">person</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['male']) ? number_format($data['statistics']['male']) : 0; ?></div>
        <div class="stat-comparison">
            <?php
            $totalStudents = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
            $maleStudents = isset($data['statistics']['male']) ? $data['statistics']['male'] : 0;
            $malePercent = ($totalStudents > 0) ? round(($maleStudents / $totalStudents) * 100) : 0;
            echo $malePercent . '% ของนักเรียนทั้งหมด';
            ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนหญิง</div>
            <div class="stat-icon blue">
                <span class="material-icons">person</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['female']) ? number_format($data['statistics']['female']) : 0; ?></div>
        <div class="stat-comparison">
            <?php
            $totalStudents = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
            $femaleStudents = isset($data['statistics']['female']) ? $data['statistics']['female'] : 0;
            $femalePercent = ($totalStudents > 0) ? round(($femaleStudents / $totalStudents) * 100) : 0;
            echo $femalePercent . '% ของนักเรียนทั้งหมด';
            ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียนเสี่ยงตกกิจกรรม</div>
            <div class="stat-icon red">
                <span class="material-icons">warning</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['risk']) ? number_format($data['statistics']['risk']) : 0; ?></div>
        <div class="stat-comparison">
            <?php
            $totalStudents = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
            $riskStudents = isset($data['statistics']['risk']) ? $data['statistics']['risk'] : 0;
            $riskPercent = ($totalStudents > 0) ? round(($riskStudents / $totalStudents) * 100) : 0;
            echo $riskPercent . '% ของนักเรียนทั้งหมด';
            ?>
        </div>
    </div>
</div>

<!-- รายการนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
        <?php if (!empty($_GET)): ?>
        <span class="badge"><?php echo count($data['students']); ?> รายการ</span>
        <?php endif; ?>
    </div>
    
    <div class="bulk-actions">
        <button class="btn btn-secondary" onclick="printStudentList()">
            <span class="material-icons">print</span>
            พิมพ์รายชื่อ
        </button>
        <button class="btn btn-secondary" onclick="downloadExcel()">
            <span class="material-icons">file_download</span>
            ดาวน์โหลด Excel
        </button>
        <button class="btn btn-primary" onclick="showAddStudentModal()">
            <span class="material-icons">person_add</span>
            เพิ่มนักเรียนใหม่
        </button>
        <button class="btn btn-success" onclick="showImportModal()">
            <span class="material-icons">upload_file</span>
            นำเข้าข้อมูล
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">รหัส</th>
                    <th width="25%">ชื่อ-นามสกุล</th>
                    <th width="10%">ชั้น/ห้อง</th>
                    <th width="10%">ไลน์</th>
                    <th width="15%">การเข้าแถว</th>
                    <th width="15%">สถานะ</th>
                    <th width="20%">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['students'])): ?>
                <tr>
                    <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียน</td>
                </tr>
                <?php else: ?>
                <?php foreach ($data['students'] as $student): ?>
                <tr>
                    <td><?php echo $student['student_code']; ?></td>
                    <td>
                        <div class="student-info">
                            <div class="student-avatar">
                                <?php echo mb_substr($student['first_name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <div class="student-details">
                                <div class="student-name"><?php echo $student['title'] . $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                <div class="student-class"><?php echo $student['department_name'] ?? ''; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $student['class']; ?></td>
                    <td>
                        <?php if (isset($student['line_connected']) && $student['line_connected']): ?>
                        <span class="status-badge success">เชื่อมต่อแล้ว</span>
                        <?php else: ?>
                        <span class="status-badge warning">ยังไม่เชื่อมต่อ</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($student['attendance_rate'], 1); ?>%</td>
                    <td>
                        <?php
                        $status_class = '';
                        if (isset($student['attendance_status'])) {
                            if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
                                $status_class = 'danger';
                            } elseif ($student['attendance_status'] === 'ต้องระวัง') {
                                $status_class = 'warning';
                            } else {
                                $status_class = 'success';
                            }
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $student['attendance_status'] ?? 'ไม่มีข้อมูล'; ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="table-action-btn primary" title="ดูข้อมูล" onclick="viewStudent(<?php echo $student['student_id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" title="แก้ไข" onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="table-action-btn danger" title="ลบ" onclick="deleteStudent(<?php echo $student['student_id']; ?>)">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
                                <option value="ปวช.1">ปวช.1</option>
                                <option value="ปวช.2">ปวช.2</option>
                                <option value="ปวช.3">ปวช.3</option>
                                <option value="ปวส.1">ปวส.1</option>
                                <option value="ปวส.2">ปวส.2</option>
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
// เรียกใช้ฟังก์ชันเริ่มต้นเมื่อ DOM โหลดเสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // แสดงข้อความสำเร็จหรือข้อผิดพลาด (ถ้ามี)
    <?php if (isset($success_message)): ?>
    showAlert('<?php echo $success_message; ?>', 'success');
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    showAlert('<?php echo $error_message; ?>', 'danger');
    <?php endif; ?>
});

// ฟังก์ชันแสดงโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// ฟังก์ชันซ่อนโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// ฟังก์ชันแสดงการแจ้งเตือน
function showAlert(message, type = 'info') {
    // สร้าง Alert Container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง Alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม Alert ไปยัง Container
    alertContainer.appendChild(alert);
    
    // กำหนด Event Listener สำหรับปุ่มปิด
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
        }, 300);
    });
    
    // ให้ Alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
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

// ฟังก์ชันดูข้อมูลนักเรียน
function viewStudent(studentId) {
    // จำลองการแสดงโมดัล (ในทางปฏิบัติจริงจะดึงข้อมูลจากฐานข้อมูล)
    showModal('viewStudentModal');
}

// ฟังก์ชันแก้ไขข้อมูลนักเรียน
function editStudent(studentId) {
    document.getElementById('edit_student_id').value = studentId;
    // จำลองการแสดงโมดัล (ในทางปฏิบัติจริงจะดึงข้อมูลจากฐานข้อมูล)
    showModal('editStudentModal');
}

// ฟังก์ชันลบข้อมูลนักเรียน
function deleteStudent(studentId) {
    document.getElementById('delete_student_id').value = studentId;
    // จำลองการแสดงโมดัล (ในทางปฏิบัติจริงจะดึงข้อมูลจากฐานข้อมูล)
    showModal('deleteStudentModal');
}

// ฟังก์ชันพิมพ์รายชื่อนักเรียน
function printStudentList() {
    window.print();
}

// ฟังก์ชันดาวน์โหลดไฟล์ Excel
function downloadExcel() {
    alert('กำลังพัฒนาฟังก์ชันดาวน์โหลดไฟล์ Excel...');
}

// ฟังก์ชันแสดงโมดัลนำเข้าข้อมูล
function showImportModal() {
    showModal('importModal');
}
</script>
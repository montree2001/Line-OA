<!-- ตัวกรองข้อมูลนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ค้นหาและกรองข้อมูลนักเรียน
    </div>
    
    <div class="filter-container">
        <form method="get" action="students.php" class="filter-form">
            <div class="row">
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">ชื่อ-นามสกุล</div>
                        <input type="text" class="form-control" name="name" placeholder="ป้อนชื่อนักเรียน..." value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">รหัสนักเรียน</div>
                        <input type="text" class="form-control" name="student_code" placeholder="ป้อนรหัสนักเรียน..." value="<?php echo isset($_GET['student_code']) ? htmlspecialchars($_GET['student_code']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-2">
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
                </div>
                <div class="col-md-2">
                    <div class="filter-group">
                        <div class="filter-label">กลุ่ม</div>
                        <select class="form-control" name="group_number">
                            <option value="">-- ทุกกลุ่ม --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['group_number']) && $_GET['group_number'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="filter-group">
                        <div class="filter-label">แผนกวิชา</div>
                        <select class="form-control" name="department_id">
                            <option value="">-- ทุกแผนก --</option>
                            <?php foreach ($data['departments'] as $department): ?>
                            <option value="<?php echo $department['department_id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $department['department_id']) ? 'selected' : ''; ?>><?php echo $department['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">สถานะการเข้าแถว</div>
                        <select class="form-control" name="attendance_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="เสี่ยงตกกิจกรรม" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'เสี่ยงตกกิจกรรม') ? 'selected' : ''; ?>>เสี่ยงตกกิจกรรม</option>
                            <option value="ต้องระวัง" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'ต้องระวัง') ? 'selected' : ''; ?>>ต้องระวัง</option>
                            <option value="ปกติ" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'ปกติ') ? 'selected' : ''; ?>>ปกติ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">สถานะการศึกษา</div>
                        <select class="form-control" name="status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="กำลังศึกษา" <?php echo (isset($_GET['status']) && $_GET['status'] === 'กำลังศึกษา') ? 'selected' : ''; ?>>กำลังศึกษา</option>
                            <option value="พักการเรียน" <?php echo (isset($_GET['status']) && $_GET['status'] === 'พักการเรียน') ? 'selected' : ''; ?>>พักการเรียน</option>
                            <option value="พ้นสภาพ" <?php echo (isset($_GET['status']) && $_GET['status'] === 'พ้นสภาพ') ? 'selected' : ''; ?>>พ้นสภาพ</option>
                            <option value="สำเร็จการศึกษา" <?php echo (isset($_GET['status']) && $_GET['status'] === 'สำเร็จการศึกษา') ? 'selected' : ''; ?>>สำเร็จการศึกษา</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">การเชื่อมต่อ LINE</div>
                        <select class="form-control" name="line_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'connected') ? 'selected' : ''; ?>>เชื่อมต่อแล้ว</option>
                            <option value="not_connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'not_connected') ? 'selected' : ''; ?>>ยังไม่เชื่อมต่อ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <span class="material-icons">search</span>
                        ค้นหา
                    </button>
                </div>
            </div>
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
                    <th width="20%">ชื่อ-นามสกุล</th>
                    <th width="10%">ชั้น/ห้อง</th>
                    <th width="15%">แผนกวิชา</th>
                    <th width="10%">ไลน์</th>
                    <th width="10%">การเข้าแถว</th>
                    <th width="10%">สถานะ</th>
                    <th width="20%">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['students'])): ?>
                <tr>
                    <td colspan="8" class="text-center">ไม่พบข้อมูลนักเรียน</td>
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
                                <div class="student-class"><?php echo $student['status']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $student['class']; ?></td>
                    <td><?php echo $student['department_name'] ?? '-'; ?></td>
                    <td>
                        <?php if (isset($student['line_connected']) && $student['line_connected']): ?>
                        <span class="status-badge success">เชื่อมต่อแล้ว</span>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="generateLineQR(<?php echo $student['student_id']; ?>)">สร้าง QR</button>
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
                            <button class="btn btn-sm btn-info" title="ดูข้อมูล" onclick="viewStudent(<?php echo $student['student_id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="btn btn-sm btn-warning" title="แก้ไข" onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="btn btn-sm btn-danger" title="ลบ" onclick="deleteStudent(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>')">
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
                    <div class="col-md-3">
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน</label>
                            <input type="text" class="form-control" name="student_code" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" name="phone_number">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการศึกษา</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">ชั้นเรียน</label>
                            <select class="form-control" name="class_id">
                                <option value="">-- เลือกชั้นเรียน --</option>
                                <?php 
                                $classGroups = [];
                                foreach ($data['classes'] as $class) {
                                    $level = $class['level'];
                                    if (!isset($classGroups[$level])) {
                                        $classGroups[$level] = [];
                                    }
                                    $classGroups[$level][] = $class;
                                }
                                
                                foreach ($classGroups as $level => $classes):
                                ?>
                                <optgroup label="<?php echo $level; ?>">
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>"><?php echo $level . '/' . $class['group_number'] . ' ' . $class['department_name']; ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">สถานะการศึกษา</label>
                            <select class="form-control" name="status" required>
                                <option value="กำลังศึกษา">กำลังศึกษา</option>
                                <option value="พักการเรียน">พักการเรียน</option>
                                <option value="พ้นสภาพ">พ้นสภาพ</option>
                                <option value="สำเร็จการศึกษา">สำเร็จการศึกษา</option>
                            </select>
                        </div>
                    </div>
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

<!-- โมดัลแก้ไขข้อมูลนักเรียน -->
<div class="modal" id="editStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('editStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แก้ไขข้อมูลนักเรียน</h2>
        
        <form id="editStudentForm" method="post" action="students.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลส่วนตัว</h3>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้า</label>
                            <select class="form-control" name="title" id="edit_title" required>
                                <option value="">-- เลือก --</option>
                                <option value="นาย">นาย</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="เด็กชาย">เด็กชาย</option>
                                <option value="เด็กหญิง">เด็กหญิง</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน</label>
                            <input type="text" class="form-control" name="student_code" id="edit_student_code" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" name="phone_number" id="edit_phone_number">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการศึกษา</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">ชั้นเรียน</label>
                            <select class="form-control" name="class_id" id="edit_class_id">
                                <option value="">-- เลือกชั้นเรียน --</option>
                                <?php 
                                foreach ($classGroups as $level => $classes):
                                ?>
                                <optgroup label="<?php echo $level; ?>">
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>"><?php echo $level . '/' . $class['group_number'] . ' ' . $class['department_name']; ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">สถานะการศึกษา</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="กำลังศึกษา">กำลังศึกษา</option>
                                <option value="พักการเรียน">พักการเรียน</option>
                                <option value="พ้นสภาพ">พ้นสภาพ</option>
                                <option value="สำเร็จการศึกษา">สำเร็จการศึกษา</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
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
                <div class="student-profile-avatar" id="view_avatar"></div>
                <div class="student-profile-info">
                    <h3 id="view_full_name"></h3>
                    <p id="view_student_code"></p>
                    <p id="view_class"></p>
                </div>
            </div>
            
            <div class="info-sections">
                <div class="info-section">
                    <h4>ข้อมูลติดต่อ</h4>
                    <p id="view_phone"><strong>เบอร์โทรศัพท์:</strong> <span></span></p>
                    <p id="view_email"><strong>อีเมล:</strong> <span></span></p>
                    <p id="view_line"><strong>LINE:</strong> <span></span></p>
                </div>
                
                <div class="info-section">
                    <h4>ข้อมูลการศึกษา</h4>
                    <p id="view_advisor"><strong>ครูที่ปรึกษา:</strong> <span></span></p>
                    <p id="view_department"><strong>แผนกวิชา:</strong> <span></span></p>
                    <p id="view_status"><strong>สถานะการศึกษา:</strong> <span></span></p>
                </div>
                
                <div class="info-section">
                    <h4>การเข้าแถว</h4>
                    <p id="view_attendance_status"><strong>สถานะการเข้าแถว:</strong> <span></span></p>
                    <div class="attendance-stats">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="view_attendance_days">0</div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="view_absence_days">0</div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="view_attendance_rate">0%</div>
                            <div class="attendance-stat-label">อัตราการเข้าแถว</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewStudentModal')">ปิด</button>
                <button type="button" class="btn btn-warning" id="edit_btn" onclick="">
                    <span class="material-icons">edit</span>
                    แก้ไขข้อมูล
                </button>
                <button type="button" class="btn btn-primary" id="generate_qr_btn" onclick="">
                    <span class="material-icons">qr_code</span>
                    สร้าง QR LINE
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
            <p>คุณต้องการลบข้อมูลนักเรียน <strong id="delete_student_name">-</strong> ใช่หรือไม่?</p>
            <p class="warning-text text-danger">คำเตือน: การลบข้อมูลจะไม่สามารถกู้คืนได้</p>
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
                <label class="form-label">เลือกไฟล์ Excel (.xlsx, .xls) หรือ CSV</label>
                <input type="file" class="form-control" name="import_file" accept=".xlsx,.xls,.csv" required>
            </div>
            
            <div class="import-instructions">
                <h4>คำแนะนำการนำเข้าข้อมูล</h4>
                <ol>
                    <li>ไฟล์ต้องมีรูปแบบตามที่กำหนด (คลิก <a href="#" id="downloadTemplateBtn">ดาวน์โหลดตัวอย่าง</a>)</li>
                    <li>ต้องมีหัวตารางในแถวแรกเสมอ</li>
                    <li>ข้อมูลที่จำเป็นต้องมี: รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล</li>
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
                <div class="form-group mt-3">
                    <label for="import_class">นำเข้านักเรียนเข้าชั้นเรียน</label>
                    <select class="form-control" name="import_class_id" id="import_class">
                        <option value="">-- ไม่ระบุชั้นเรียน --</option>
                        <?php 
                        foreach ($classGroups as $level => $classes):
                        ?>
                        <optgroup label="<?php echo $level; ?>">
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $level . '/' . $class['group_number'] . ' ' . $class['department_name']; ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
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

<!-- โมดัลแสดง QR Code LINE -->
<div class="modal" id="lineQRModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('lineQRModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">QR Code สำหรับเชื่อมต่อ LINE</h2>
        
        <div class="text-center p-4">
            <p>ให้นักเรียนสแกน QR Code ด้านล่างเพื่อเชื่อมต่อกับ LINE</p>
            <div id="qrcode-container" class="my-4">
                <img id="qrcode-image" src="" alt="QR Code" class="img-fluid" style="max-width: 250px;">
            </div>
            <p class="text-info">หมายเหตุ: QR Code นี้จะหมดอายุภายใน 24 ชั่วโมง</p>
            <p>หรือคลิกลิงก์: <a href="#" id="line-connect-url" target="_blank">ลิงก์เชื่อมต่อ LINE</a></p>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('lineQRModal')">ปิด</button>
            <button type="button" class="btn btn-primary" onclick="checkLineStatus()">
                <span class="material-icons">refresh</span>
                ตรวจสอบสถานะ
            </button>
        </div>
    </div>
</div>

<!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
<?php if (isset($success_message)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('<?php echo $success_message; ?>', 'success');
    });
</script>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('<?php echo $error_message; ?>', 'error');
    });
</script>
<?php endif; ?>
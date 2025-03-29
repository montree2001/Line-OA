<!-- ส่วนหัวของหน้า -->
<div class="page-header">
    <h1>
        <i class="material-icons">people</i> จัดการข้อมูลนักเรียน
    </h1>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="showAddStudentModal()">
            <i class="material-icons">person_add</i> เพิ่มนักเรียนใหม่
        </button>
        <button class="btn btn-success" onclick="showImportModal()">
            <i class="material-icons">file_upload</i> นำเข้าข้อมูล
        </button>
        <button class="btn btn-secondary" onclick="downloadExcel()">
            <i class="material-icons">download</i> ดาวน์โหลด Excel
        </button>
        <button class="btn btn-outline-secondary" onclick="printStudentList()">
            <i class="material-icons">print</i> พิมพ์รายงาน
        </button>
    </div>
</div>

<!-- ตัวกรองข้อมูลนักเรียน -->
<div class="card filter-card">
    <div class="card-header">
        <h5><i class="material-icons">filter_list</i> ค้นหาและกรองข้อมูลนักเรียน</h5>
        <button class="btn btn-sm btn-link" id="toggle-filter">
            <i class="material-icons">expand_more</i>
        </button>
    </div>
    
    <div class="card-body" id="filter-container">
        <form method="get" action="students.php" class="filter-form">
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="name" placeholder="ป้อนชื่อนักเรียน..." value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <div class="form-group">
                        <label>รหัสนักเรียน</label>
                        <input type="text" class="form-control" name="student_code" placeholder="ป้อนรหัสนักเรียน..." value="<?php echo isset($_GET['student_code']) ? htmlspecialchars($_GET['student_code']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="form-group">
                        <label>ระดับชั้น</label>
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
                <div class="col-md-4 col-lg-2">
                    <div class="form-group">
                        <label>กลุ่ม</label>
                        <select class="form-control" name="group_number">
                            <option value="">-- ทุกกลุ่ม --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['group_number']) && $_GET['group_number'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="form-group">
                        <label>แผนกวิชา</label>
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
                <div class="col-md-4 col-lg-3">
                    <div class="form-group">
                        <label>สถานะการเข้าแถว</label>
                        <select class="form-control" name="attendance_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="เสี่ยงตกกิจกรรม" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'เสี่ยงตกกิจกรรม') ? 'selected' : ''; ?>>เสี่ยงตกกิจกรรม</option>
                            <option value="ต้องระวัง" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'ต้องระวัง') ? 'selected' : ''; ?>>ต้องระวัง</option>
                            <option value="ปกติ" <?php echo (isset($_GET['attendance_status']) && $_GET['attendance_status'] === 'ปกติ') ? 'selected' : ''; ?>>ปกติ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <div class="form-group">
                        <label>สถานะการศึกษา</label>
                        <select class="form-control" name="status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="กำลังศึกษา" <?php echo (isset($_GET['status']) && $_GET['status'] === 'กำลังศึกษา') ? 'selected' : ''; ?>>กำลังศึกษา</option>
                            <option value="พักการเรียน" <?php echo (isset($_GET['status']) && $_GET['status'] === 'พักการเรียน') ? 'selected' : ''; ?>>พักการเรียน</option>
                            <option value="พ้นสภาพ" <?php echo (isset($_GET['status']) && $_GET['status'] === 'พ้นสภาพ') ? 'selected' : ''; ?>>พ้นสภาพ</option>
                            <option value="สำเร็จการศึกษา" <?php echo (isset($_GET['status']) && $_GET['status'] === 'สำเร็จการศึกษา') ? 'selected' : ''; ?>>สำเร็จการศึกษา</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <div class="form-group">
                        <label>การเชื่อมต่อ LINE</label>
                        <select class="form-control" name="line_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'connected') ? 'selected' : ''; ?>>เชื่อมต่อแล้ว</option>
                            <option value="not_connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'not_connected') ? 'selected' : ''; ?>>ยังไม่เชื่อมต่อ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12 col-lg-3">
                    <div class="d-flex justify-content-end align-items-end h-100 mt-md-3 mt-lg-0">
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons">search</i> ค้นหา
                        </button>
                        <a href="students.php" class="btn btn-outline-secondary ms-2">
                            <i class="material-icons">refresh</i> รีเซ็ต
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- สรุปข้อมูลนักเรียน -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="material-icons">people</i>
        </div>
        <div class="stat-content">
            <div class="stat-title">นักเรียนทั้งหมด</div>
            <div class="stat-value"><?php echo isset($data['statistics']['total']) ? number_format($data['statistics']['total']) : 0; ?></div>
            <div class="stat-description">จำนวนนักเรียนที่กำลังศึกษา</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon indigo">
            <i class="material-icons">person</i>
        </div>
        <div class="stat-content">
            <div class="stat-title">นักเรียนชาย</div>
            <div class="stat-value"><?php echo isset($data['statistics']['male']) ? number_format($data['statistics']['male']) : 0; ?></div>
            <div class="stat-description">
                <?php
                $totalStudents = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
                $maleStudents = isset($data['statistics']['male']) ? $data['statistics']['male'] : 0;
                $malePercent = ($totalStudents > 0) ? round(($maleStudents / $totalStudents) * 100) : 0;
                echo $malePercent . '% ของนักเรียนทั้งหมด';
                ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="material-icons">person</i>
        </div>
        <div class="stat-content">
            <div class="stat-title">นักเรียนหญิง</div>
            <div class="stat-value"><?php echo isset($data['statistics']['female']) ? number_format($data['statistics']['female']) : 0; ?></div>
            <div class="stat-description">
                <?php
                $femaleStudents = isset($data['statistics']['female']) ? $data['statistics']['female'] : 0;
                $femalePercent = ($totalStudents > 0) ? round(($femaleStudents / $totalStudents) * 100) : 0;
                echo $femalePercent . '% ของนักเรียนทั้งหมด';
                ?>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="material-icons">warning</i>
        </div>
        <div class="stat-content">
            <div class="stat-title">เสี่ยงตกกิจกรรม</div>
            <div class="stat-value"><?php echo isset($data['statistics']['risk']) ? number_format($data['statistics']['risk']) : 0; ?></div>
            <div class="stat-description">
                <?php
                $riskStudents = isset($data['statistics']['risk']) ? $data['statistics']['risk'] : 0;
                $riskPercent = ($totalStudents > 0) ? round(($riskStudents / $totalStudents) * 100) : 0;
                echo $riskPercent . '% ของนักเรียนทั้งหมด';
                ?>
            </div>
        </div>
    </div>
</div>

<!-- รายการนักเรียน -->
<div class="card main-card">
    <div class="card-header">
        <h5>
            <i class="material-icons">format_list_bulleted</i> 
            รายชื่อนักเรียน 
            <?php if (!empty($data['students'])): ?>
                <span class="badge bg-primary"><?php echo count($data['students']); ?> รายการ</span>
            <?php endif; ?>
        </h5>
    </div>
    
    <div class="card-body">
        <?php if (empty($data['students'])): ?>
            <div class="empty-data">
                <i class="material-icons">sentiment_dissatisfied</i>
                <p>ไม่พบข้อมูลนักเรียน</p>
                <?php if (!empty($_GET)): ?>
                    <p>ลองปรับเงื่อนไขการค้นหาหรือ <a href="students.php">ดูข้อมูลทั้งหมด</a></p>
                <?php else: ?>
                    <p>คลิกที่ <a href="#" onclick="showAddStudentModal(); return false;">เพิ่มนักเรียนใหม่</a> เพื่อเริ่มเพิ่มข้อมูล</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="students-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="10%">รหัสนักศึกษา</th>
                            <th width="20%">ชื่อ-นามสกุล</th>
                            <th width="10%">ชั้น/ห้อง</th>
                            <th width="15%">แผนกวิชา</th>
                            <th width="10%">ไลน์</th>
                            <th width="10%">การเข้าแถว</th>
                            <th width="10%">สถานะ</th>
                            <th width="15%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                        <div class="student-status"><?php echo $student['status']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $student['class'] ?: '-'; ?></td>
                            <td><?php echo $student['department_name'] ?? '-'; ?></td>
                            <td>
                                <?php if (isset($student['line_connected']) && $student['line_connected']): ?>
                                <span class="badge bg-success"><i class="material-icons">check_circle</i> เชื่อมต่อแล้ว</span>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="generateLineQR(<?php echo $student['student_id']; ?>)">
                                    <i class="material-icons">qr_code</i> สร้าง QR
                                </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar <?php 
                                        if ($student['attendance_rate'] < 60) echo 'bg-danger';
                                        else if ($student['attendance_rate'] < 75) echo 'bg-warning';
                                        else echo 'bg-success';
                                    ?>" role="progressbar" style="width: <?php echo $student['attendance_rate']; ?>%;" 
                                    aria-valuenow="<?php echo $student['attendance_rate']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($student['attendance_rate'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                if (isset($student['attendance_status'])) {
                                    if ($student['attendance_status'] === 'เสี่ยงตกกิจกรรม') {
                                        $status_class = 'bg-danger';
                                    } elseif ($student['attendance_status'] === 'ต้องระวัง') {
                                        $status_class = 'bg-warning text-dark';
                                    } else {
                                        $status_class = 'bg-success';
                                    }
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $student['attendance_status'] ?? 'ไม่มีข้อมูล'; ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" title="ดูข้อมูล" onclick="viewStudent(<?php echo $student['student_id']; ?>)">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" title="แก้ไข" onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" title="ลบ" onclick="deleteStudent(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>')">
                                        <i class="material-icons">delete</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- โมดัลเพิ่มนักเรียนใหม่ -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="material-icons">person_add</i> เพิ่มนักเรียนใหม่
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="addStudentForm" method="post" action="students.php">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-body">
                    <div class="form-section">
                        <h6 class="section-title"><i class="material-icons">person</i> ข้อมูลส่วนตัว</h6>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
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
                                <div class="form-group mb-3">
                                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="firstname" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="lastname" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">รหัสนักเรียน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="student_code" required>
                                    <div class="form-text">รหัสนักเรียน 11 หลัก</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="tel" class="form-control" name="phone_number">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section mt-4">
                        <h6 class="section-title"><i class="material-icons">school</i> ข้อมูลการศึกษา</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
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
                                <div class="form-group mb-3">
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
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons">save</i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขข้อมูลนักเรียน -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="material-icons">edit</i> แก้ไขข้อมูลนักเรียน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editStudentForm" method="post" action="students.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id">
                
                <div class="modal-body">
                    <div class="form-section">
                        <h6 class="section-title"><i class="material-icons">person</i> ข้อมูลส่วนตัว</h6>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-3">
                                    <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
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
                                <div class="form-group mb-3">
                                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-3">
                                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">รหัสนักเรียน <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="student_code" id="edit_student_code" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="tel" class="form-control" name="phone_number" id="edit_phone_number">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" name="email" id="edit_email">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section mt-4">
                        <h6 class="section-title"><i class="material-icons">school</i> ข้อมูลการศึกษา</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
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
                                <div class="form-group mb-3">
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
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons">save</i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลดูข้อมูลนักเรียน -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="material-icons">person</i> ข้อมูลนักเรียน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="student-profile">
                    <div class="student-profile-header">
                        <div class="student-profile-avatar" id="view_avatar"></div>
                        <div class="student-profile-info">
                            <h4 id="view_full_name"></h4>
                            <p id="view_student_code" class="mb-1"></p>
                            <p id="view_class" class="text-muted mb-1"></p>
                            <div id="view_line_status" class="d-flex align-items-center mt-2"></div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6><i class="material-icons">contact_phone</i> ข้อมูลติดต่อ</h6>
                                <div class="info-item">
                                    <div class="info-label">เบอร์โทรศัพท์:</div>
                                    <div class="info-value" id="view_phone"></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">อีเมล:</div>
                                    <div class="info-value" id="view_email"></div>
                                </div>
                            </div>
                            
                            <div class="info-section mt-3">
                                <h6><i class="material-icons">school</i> ข้อมูลการศึกษา</h6>
                                <div class="info-item">
                                    <div class="info-label">ครูที่ปรึกษา:</div>
                                    <div class="info-value" id="view_advisor"></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">แผนกวิชา:</div>
                                    <div class="info-value" id="view_department"></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">สถานะการศึกษา:</div>
                                    <div class="info-value" id="view_status"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6><i class="material-icons">calendar_today</i> การเข้าแถว</h6>
                                <div class="attendance-progress mb-2">
                                    <div id="view_attendance_rate_text" class="attendance-rate-text"></div>
                                    <div class="progress">
                                        <div id="view_attendance_progress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div id="view_attendance_status" class="attendance-status mt-1"></div>
                                </div>
                                
                                <div class="attendance-stats">
                                    <div class="attendance-stat">
                                        <div id="view_attendance_days" class="attendance-stat-value">0</div>
                                        <div class="attendance-stat-label">วันที่เข้าแถว</div>
                                    </div>
                                    <div class="attendance-stat">
                                        <div id="view_absence_days" class="attendance-stat-value">0</div>
                                        <div class="attendance-stat-label">วันที่ขาดแถว</div>
                                    </div>
                                    <div class="attendance-stat">
                                        <div id="view_total_days" class="attendance-stat-value">0</div>
                                        <div class="attendance-stat-label">วันทั้งหมด</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-warning" id="edit_btn">
                    <i class="material-icons">edit</i> แก้ไขข้อมูล
                </button>
                <button type="button" class="btn btn-primary" id="generate_qr_btn">
                    <i class="material-icons">qr_code</i> สร้าง QR LINE
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลลบนักเรียน -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="material-icons">warning</i> ยืนยันการลบข้อมูล
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="material-icons text-danger delete-icon">delete_forever</i>
                </div>
                <p>คุณต้องการลบข้อมูลนักเรียน <strong id="delete_student_name">-</strong> ใช่หรือไม่?</p>
                <p class="text-danger">
                    <strong>คำเตือน:</strong> การลบข้อมูลจะไม่สามารถกู้คืนได้ และข้อมูลที่เกี่ยวข้องทั้งหมดจะถูกลบไปด้วย
                </p>
            </div>
            
            <form id="deleteStudentForm" method="post" action="students.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="student_id" id="delete_student_id">
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="material-icons">delete</i> ยืนยันการลบ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลนำเข้าข้อมูลนักเรียน -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="material-icons">upload_file</i> นำเข้าข้อมูลนักเรียน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="importForm" method="post" action="students.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                
                <div class="modal-body">
                    <div class="upload-area mb-4">
                        <div class="file-upload-container">
                            <i class="material-icons">file_upload</i>
                            <h5>อัปโหลดไฟล์</h5>
                            <p>ลากไฟล์มาวางที่นี่หรือคลิกเพื่อเลือกไฟล์</p>
                            <input type="file" class="form-control file-input" name="import_file" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    
                    <div class="selected-file d-none mb-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="material-icons me-2">description</i>
                            <div>
                                <strong>ไฟล์ที่เลือก:</strong> <span id="selected-file-name"></span>
                            </div>
                            <button type="button" class="btn-close ms-auto" id="clear-file"></button>
                        </div>
                    </div>
                    
                    <div class="import-instructions mb-4">
                        <h6><i class="material-icons">info</i> คำแนะนำการนำเข้าข้อมูล</h6>
                        <ol>
                            <li>ไฟล์ต้องมีรูปแบบตามที่กำหนด (<a href="#" id="downloadTemplateBtn">ดาวน์โหลดตัวอย่าง</a>)</li>
                            <li>รองรับไฟล์ Excel (.xlsx, .xls) และ CSV</li>
                            <li>ข้อมูลที่จำเป็นต้องมี: รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล</li>
                            <li>ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                        </ol>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">นำเข้านักเรียนเข้าชั้นเรียน</label>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">ตัวเลือกการนำเข้า</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing" checked>
                                    <label class="form-check-label" for="update_existing">
                                        อัพเดทข้อมูลที่มีอยู่แล้ว
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" checked>
                                    <label class="form-check-label" for="skip_header">
                                        ข้ามแถวแรก (หัวตาราง)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="importBtn">
                        <i class="material-icons">upload_file</i> นำเข้าข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลแสดง QR Code LINE -->
<div class="modal fade" id="lineQRModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="material-icons">qr_code</i> QR Code สำหรับเชื่อมต่อ LINE
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-center">
                <p>ให้นักเรียนสแกน QR Code ด้านล่างเพื่อเชื่อมต่อกับ LINE</p>
                <div id="qrcode-container" class="my-4">
                    <img id="qrcode-image" src="" alt="QR Code" class="img-fluid qr-image">
                </div>
                <p class="text-info">
                    <i class="material-icons align-text-bottom">schedule</i>
                    QR Code นี้จะหมดอายุภายใน 24 ชั่วโมง
                </p>
                <p>หรือคลิกลิงก์: <a href="#" id="line-connect-url" target="_blank">ลิงก์เชื่อมต่อ LINE</a></p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" onclick="checkLineStatus()">
                    <i class="material-icons">refresh</i> ตรวจสอบสถานะ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
<?php if (isset($success_message)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="material-icons">check_circle</i> <?php echo $success_message; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="material-icons">error</i> <?php echo $error_message; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>
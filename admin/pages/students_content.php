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

<!-- แก้ไขส่วนของตารางแสดงข้อมูลนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
        <span class="badge"><?php echo count($data['students']); ?> รายการ</span>
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

    <!-- ตารางข้อมูลนักเรียน -->
    <div class="table-responsive">
        <table id="studentDataTable" class="data-table display">
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
                <?php
                // ใช้ student_id เป็นรหัสที่ไม่ซ้ำกัน
                $displayed_student_ids = [];

                foreach ($data['students'] as $student):
                    // ข้ามถ้ามี student_id นี้แล้ว
                    if (in_array($student['student_id'], $displayed_student_ids)) {
                        continue;
                    }

                    // เพิ่ม student_id ที่กำลังแสดงเข้าไปในอาร์เรย์
                    $displayed_student_ids[] = $student['student_id'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php if (!empty($student['profile_picture']) && $student['line_connected']): ?>
                                        <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="<?php echo htmlspecialchars($student['first_name']); ?>" class="profile-image">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($student['first_name'] ?? '', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="student-details">
                                    <div class="student-name">
                                        <?php
                                        echo htmlspecialchars(($student['title'] ?? '') . ' ' .
                                            ($student['first_name'] ?? '') . ' ' .
                                            ($student['last_name'] ?? ''));
                                        ?>
                                    </div>
                                    <div class="student-class"><?php echo htmlspecialchars($student['status'] ?? 'กำลังศึกษา'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($student['class'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($student['department_name'] ?? '-'); ?></td>
                        <td>
                            <?php if (isset($student['line_connected']) && $student['line_connected']): ?>
                                <span class="status-badge success">เชื่อมต่อแล้ว</span>
                            <?php else: ?>
                                <button class="btn btn-line" onclick="generateLineQR('<?php echo $student['student_id']; ?>')">
                                    สร้าง QR
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="attendance-rate">
                                <?php
                                $attendanceRate = isset($student['attendance_rate']) ? $student['attendance_rate'] : 0;
                                $rateClass = '';

                                if ($attendanceRate >= 90) {
                                    $rateClass = 'success';
                                } elseif ($attendanceRate >= 75) {
                                    $rateClass = 'warning';
                                } else {
                                    $rateClass = 'danger';
                                }
                                ?>
                                <div class="progress">
                                    <div class="progress-bar <?php echo $rateClass; ?>" style="width: <?php echo $attendanceRate; ?>%"></div>
                                </div>
                                <span><?php echo number_format($attendanceRate, 1); ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo strtolower($student['status'] ?? 'กำลังศึกษา'); ?>">
                                <?php echo htmlspecialchars($student['status'] ?? 'กำลังศึกษา'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-icon btn-info" onclick="viewStudent('<?php echo $student['student_id']; ?>')" title="ดูข้อมูล">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="btn btn-icon btn-warning" onclick="editStudent('<?php echo $student['student_id']; ?>')" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="btn btn-icon btn-danger" onclick="deleteStudent('<?php echo $student['student_id']; ?>', '<?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?>')" title="ลบ">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($data['students'])): ?>
                    <tr>
                        <td colspan="8" class="text-center">ไม่พบข้อมูลนักเรียน</td>
                    </tr>
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
                            <label class="form-label">คำนำหน้า<span class="text-danger">*</span></label>
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
                            <label class="form-label">ชื่อ<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน<span class="text-danger">*</span></label>
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
                            <div class="class-search-container">
                                <input type="text" list="classList" class="form-control" id="class_search"
                                    placeholder="เลือกหรือพิมพ์เพื่อค้นหาชั้นเรียน..." autocomplete="off">
                                <input type="hidden" name="class_id" id="class_id">
                                <datalist id="classList">
                                    <!-- จะถูกเติมด้วย JavaScript -->
                                </datalist>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">สถานะการศึกษา<span class="text-danger">*</span></label>
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
                            <label class="form-label">คำนำหน้า<span class="text-danger">*</span></label>
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
                            <label class="form-label">ชื่อ<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน<span class="text-danger">*</span></label>
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
                            <div class="class-search-container">
                                <input type="text" list="editClassList" class="form-control" id="edit_class_search"
                                    placeholder="เลือกหรือพิมพ์เพื่อค้นหาชั้นเรียน..." autocomplete="off">
                                <input type="hidden" name="class_id" id="edit_class_id">
                                <datalist id="editClassList">
                                    <!-- จะถูกเติมด้วย JavaScript -->
                                </datalist>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">สถานะการศึกษา<span class="text-danger">*</span></label>
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
                <div class="student-profile-avatar" id="view_avatar">
                    <!-- รูปโปรไฟล์จะถูกเพิ่มโดย JavaScript -->
                </div>
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
    <div class="modal-content modal-lg">
        <button class="modal-close" onclick="closeModal('importModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">นำเข้าข้อมูลนักเรียน</h2>

        <!-- ตัวบ่งชี้ขั้นตอน -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-text">เลือกไฟล์</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-text">แม็ปข้อมูล</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-text">ตรวจสอบและยืนยัน</div>
            </div>
        </div>

        <form id="importForm" method="post" action="api/import_students.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">

            <!-- ขั้นตอนที่ 1: เลือกไฟล์ -->
            <div class="import-step" id="step1">
                <div class="form-section">
                    <h3 class="section-title">เลือกไฟล์นำเข้า</h3>

                    <div class="file-upload-container">
                        <div class="file-upload-area">
                            <input type="file" class="file-input" id="import_file" name="import_file" accept=".xlsx,.xls,.csv">
                            <div class="file-upload-content">
                                <span class="material-icons">cloud_upload</span>
                                <p>ลากไฟล์วางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                <p class="file-types">รองรับไฟล์ Excel (.xlsx, .xls) หรือ CSV</p>
                            </div>
                        </div>
                        <div class="file-info">
                            <p>ไฟล์ที่เลือก: <span id="fileLabel">ยังไม่ได้เลือกไฟล์</span></p>
                        </div>
                    </div>

                    <div class="import-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="skip_header" name="skip_header" checked>
                            <label for="skip_header">ข้ามแถวแรก (หัวตาราง)</label>
                        </div>
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="update_existing" name="update_existing" checked>
                            <label for="update_existing">อัพเดตข้อมูลที่มีอยู่แล้ว</label>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">เลือกชั้นเรียนปลายทาง</h3>
                    <p class="section-desc">หากต้องการนำเข้านักเรียนเข้าชั้นเรียนเดียวกันทั้งหมด ให้เลือกชั้นเรียนที่นี่</p>

                    <div class="form-group">
                        <select class="form-control" name="import_class_id" id="import_class_id">
                            <option value="">-- ไม่ระบุชั้นเรียน (ใช้ข้อมูลจากไฟล์) --</option>
                            <?php
                            // แสดงรายการชั้นเรียนจากฐานข้อมูล
                            if (isset($data['classGroups']) && is_array($data['classGroups'])):
                                foreach ($data['classGroups'] as $level => $classes):
                            ?>
                                    <optgroup label="<?php echo $level; ?>">
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['class_id']; ?>">
                                                <?php echo $level . '/' . $class['group_number'] . ' ' . $class['department_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">คำแนะนำการนำเข้าข้อมูล</h3>
                    <div class="import-instructions">
                        <ol>
                            <li>ไฟล์นำเข้าควรมีหัวตารางในแถวแรก (เลือก "ข้ามแถวแรก" ถ้ามี)</li>
                            <li>ข้อมูลที่จำเป็นต้องมี: รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล</li>
                            <li>คำนำหน้ารองรับเฉพาะ: นาย, นางสาว, เด็กชาย, เด็กหญิง, นาง</li>
                            <li>สถานะการศึกษารองรับ: กำลังศึกษา, พักการเรียน, พ้นสภาพ, สำเร็จการศึกษา</li>
                            <li>ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                            <li>สามารถ <a href="api/download_template.php?type=students" target="_blank">ดาวน์โหลดไฟล์ตัวอย่าง</a> เพื่อดูรูปแบบที่ถูกต้อง</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- ขั้นตอนที่ 2: แม็ปข้อมูล -->
            <div class="import-step" id="step2" style="display: none;">
                <div class="form-section">
                    <h3 class="section-title">ตัวอย่างข้อมูล</h3>
                    <p class="section-desc">ตัวอย่าง 5 รายการแรกจากไฟล์ที่อัปโหลด (พบข้อมูลทั้งหมด <span id="totalRecords">0</span> รายการ)</p>

                    <div id="dataPreview" class="data-preview"></div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">แม็ปฟิลด์ข้อมูล</h3>
                    <p class="section-desc">โปรดเลือกว่าคอลัมน์ใดในไฟล์ตรงกับข้อมูลชนิดใด</p>

                    <div class="field-mapping-container">
                        <div class="field-mapping-group">
                            <h4>ข้อมูลสำคัญ <span class="text-danger">*</span></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>รหัสนักเรียน <span class="text-danger">*</span></label>
                                        <select id="map_student_code" name="map_student_code" class="form-control" data-field="student_code" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>คำนำหน้า <span class="text-danger">*</span></label>
                                        <select id="map_title" name="map_title" class="form-control" data-field="title" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>ชื่อ <span class="text-danger">*</span></label>
                                        <select id="map_firstname" name="map_firstname" class="form-control" data-field="firstname" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>นามสกุล <span class="text-danger">*</span></label>
                                        <select id="map_lastname" name="map_lastname" class="form-control" data-field="lastname" required>
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field-mapping-group">
                            <h4>ข้อมูลติดต่อ</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>เบอร์โทรศัพท์</label>
                                        <select id="map_phone" name="map_phone" class="form-control" data-field="phone">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>อีเมล</label>
                                        <select id="map_email" name="map_email" class="form-control" data-field="email">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field-mapping-group">
                            <h4>ข้อมูลการศึกษา</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>ระดับชั้น</label>
                                        <select id="map_level" name="map_level" class="form-control" data-field="level">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>กลุ่ม</label>
                                        <select id="map_group" name="map_group" class="form-control" data-field="group">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="field-mapping">
                                        <label>แผนกวิชา</label>
                                        <select id="map_department" name="map_department" class="form-control" data-field="department">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="field-mapping">
                                        <label>สถานะการศึกษา</label>
                                        <select id="map_status" name="map_status" class="form-control" data-field="status">
                                            <option value="-1">-- เลือกคอลัมน์ --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field-mapping-note">
                    <p>หมายเหตุ: ระบบจะพยายามแม็ปฟิลด์อัตโนมัติตามชื่อหัวตาราง โปรดตรวจสอบความถูกต้อง</p>
                </div>
            </div>

            <!-- ขั้นตอนที่ 3: ตรวจสอบและยืนยัน -->
            <div class="import-step" id="step3" style="display: none;">
                <div class="form-section">
                    <h3 class="section-title">ตรวจสอบข้อมูลก่อนนำเข้า</h3>

                    <div id="importSummary" class="import-summary"></div>

                    <div class="destination-class">
                        <h4>ชั้นเรียนปลายทาง</h4>
                        <p id="selected_class_text" style="display: none;"></p>
                        <p class="info-text">
                            <span class="material-icons">info</span>
                            <?php if (isset($class_id) && !empty($class_id)): ?>
                                นักเรียนทั้งหมดจะถูกนำเข้าสู่ชั้นเรียนที่เลือกไว้
                            <?php else: ?>
                                ระบบจะใช้ข้อมูลชั้นเรียนจากไฟล์ หรือเว้นว่างถ้าไม่ระบุ
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="import-confirmation">
                        <p class="warning-text">
                            <span class="material-icons">warning</span>
                            การนำเข้าข้อมูลจะทำการเพิ่มหรืออัพเดตข้อมูลนักเรียนในระบบ โปรดตรวจสอบความถูกต้องก่อนดำเนินการ
                        </p>
                    </div>
                </div>
            </div>

            <!-- ปุ่มควบคุมขั้นตอน -->
            <div class="modal-actions">
                <button type="button" id="prevStepBtn" class="btn btn-secondary" style="display: none;" onclick="prevStep()">
                    <span class="material-icons">arrow_back</span>
                    ย้อนกลับ
                </button>
                <button type="button" id="nextStepBtn" class="btn btn-primary" disabled onclick="nextStep()">
                    ถัดไป
                    <span class="material-icons">arrow_forward</span>
                </button>
                <button type="submit" id="importSubmitBtn" class="btn btn-success" style="display: none;">
                    <span class="material-icons">cloud_upload</span>
                    นำเข้าข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>



<!-- ต้องเพิ่ม CSS ไฟล์นี้ไว้ใน header.php -->
<!-- <link href="assets/css/import.css" rel="stylesheet"> -->



<!-- โมดัลแสดง QR Code LINE -->
<div class="modal" id="lineQRModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('lineQRModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">เชื่อมต่อบัญชี LINE</h2>

        <div class="text-center p-4">
            <p>ให้นักเรียนสแกน QR Code ด้านล่างด้วยแอพ LINE เพื่อเชื่อมต่อกับระบบ</p>

            <div id="qrcode-container" class="qrcode-container my-4">
                <img id="qrcode-image" src="" alt="QR Code" class="img-fluid" style="max-width: 250px;">
                <div class="qr-info">
                    <p>QR Code นี้จะหมดอายุในวันที่ <span id="qr-expire-time">-</span></p>
                </div>
            </div>

            <div id="line-status-text">
                <span class="status-badge warning">รอการเชื่อมต่อ</span>
            </div>

            <div class="qr-link mt-3">
                <p>หรือคลิกลิงก์เพื่อเปิดในแอพ LINE:<br>
                    <a href="#" id="line-connect-url" target="_blank">ลิงก์เชื่อมต่อ LINE</a>
                </p>
            </div>

            <div class="qr-instructions mt-4">
                <h4>วิธีการเชื่อมต่อ:</h4>
                <ol class="text-start">
                    <li>เปิดแอพ LINE บนโทรศัพท์มือถือ</li>
                    <li>สแกน QR Code ด้านบน หรือคลิกที่ลิงก์</li>
                    <li>ยืนยันการเชื่อมต่อกับระบบ</li>
                    <li>เมื่อเชื่อมต่อสำเร็จ จะมีข้อความยืนยันในแอพ LINE</li>
                    <li>กดปุ่ม "ตรวจสอบสถานะ" เพื่อยืนยันการเชื่อมต่อ</li>
                </ol>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('lineQRModal')">ปิด</button>
            <button type="button" class="btn btn-primary" id="line-status-check" onclick="checkLineStatus()">
                <span class="material-icons">refresh</span>
                ตรวจสอบสถานะ
            </button>
        </div>
    </div>
</div>

<!-- ส่วนแสดงการแจ้งเตือน -->
<div id="alertContainer" class="alert-container"></div>
<!-- ต้องเพิ่ม JS libraries ไว้ใน footer.php -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="assets/js/import-students.js"></script>
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
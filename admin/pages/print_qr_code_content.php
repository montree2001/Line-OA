<!-- เนื้อหาสำหรับหน้าพิมพ์ QR Code -->
<div class="container-fluid">
    <!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <i class="material-icons">check_circle</i>
        <span><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <i class="material-icons">error</i>
        <span><?php echo $error; ?></span>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="material-icons">qr_code_2</i>
                        พิมพ์ QR Code สำหรับนักเรียน
                    </h5>
                </div>
                <div class="card-body">
                    <div class="card-description mb-4">
                        <p>เครื่องมือนี้ใช้สำหรับสร้างและพิมพ์ QR Code ให้กับนักเรียนที่ไม่มีโทรศัพท์มือถือ เพื่อใช้ในการเช็คชื่อเข้าแถว</p>
                        <ul>
                            <li>สามารถค้นหานักเรียนด้วยชื่อ รหัสนักเรียน หรือเลือกห้องเรียน</li>
                            <li>สามารถกำหนดวันหมดอายุของ QR Code ได้</li>
                            <li>สามารถพิมพ์ QR Code ได้ทั้งรายบุคคลและแบบกลุ่ม</li>
                        </ul>
                    </div>

                    <!-- ส่วนค้นหานักเรียน -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">ค้นหานักเรียน</h6>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="searchTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="search-tab" data-toggle="tab" href="#search-content" role="tab">
                                        <i class="material-icons">search</i> ค้นหาด้วยชื่อ/รหัส
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="class-tab" data-toggle="tab" href="#class-content" role="tab">
                                        <i class="material-icons">class</i> เลือกตามห้องเรียน
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="department-tab" data-toggle="tab" href="#department-content" role="tab">
                                        <i class="material-icons">business</i> เลือกตามแผนก/ระดับชั้น/กลุ่ม
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="searchTabsContent">
                                <!-- ค้นหาด้วยชื่อหรือรหัสนักเรียน -->
                                <div class="tab-pane fade show active" id="search-content" role="tabpanel">
                                    <form method="post" action="" class="search-form">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="search_term">ชื่อหรือรหัสนักเรียน</label>
                                                    <input type="text" class="form-control" id="search_term" name="search_term" 
                                                        placeholder="พิมพ์ชื่อหรือรหัสนักเรียน" value="<?php echo htmlspecialchars($search_term); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="qr_validity">อายุการใช้งาน QR Code (วัน)</label>
                                                    <input type="number" class="form-control" id="qr_validity" name="qr_validity" 
                                                        min="1" max="365" value="<?php echo $qr_validity; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date">วันที่เริ่มต้น</label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                                        value="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="expiry_date">วันที่หมดอายุ (หากกำหนด)</label>
                                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" 
                                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                                    <small class="text-muted">หากไม่ระบุ จะคำนวณจากอายุการใช้งาน</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <button type="submit" name="search" class="btn btn-primary">
                                                        <i class="material-icons">search</i> ค้นหา
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- เลือกตามห้องเรียน -->
                                <div class="tab-pane fade" id="class-content" role="tabpanel">
                                    <form method="post" action="" class="class-form">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="class_id">เลือกห้องเรียน</label>
                                                    <select class="form-control" id="class_id" name="class_id" required>
                                                        <option value="">-- เลือกห้องเรียน --</option>
                                                        <?php foreach ($classes as $class): ?>
                                                        <option value="<?php echo $class['class_id']; ?>" <?php echo ($selected_class == $class['class_id']) ? 'selected' : ''; ?>>
                                                            <?php echo $class['level'] . ' ' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']; ?> 
                                                            (<?php echo $class['student_count']; ?> คน)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="qr_validity_class">อายุการใช้งาน QR Code (วัน)</label>
                                                    <input type="number" class="form-control" id="qr_validity_class" name="qr_validity" 
                                                        min="1" max="365" value="<?php echo $qr_validity; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date_class">วันที่เริ่มต้น</label>
                                                    <input type="date" class="form-control" id="start_date_class" name="start_date" 
                                                        value="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="expiry_date_class">วันที่หมดอายุ (หากกำหนด)</label>
                                                    <input type="date" class="form-control" id="expiry_date_class" name="expiry_date" 
                                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <button type="submit" name="search" class="btn btn-primary">
                                                        <i class="material-icons">search</i> ค้นหา
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- เลือกตามแผนก/ระดับชั้น/กลุ่ม -->
                                <div class="tab-pane fade" id="department-content" role="tabpanel">
                                    <form method="post" action="" class="department-form">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="department_id">แผนกวิชา</label>
                                                    <select class="form-control" id="department_id" name="department_id">
                                                        <option value="">-- ทุกแผนก --</option>
                                                        <?php foreach ($departments as $dept): ?>
                                                        <option value="<?php echo $dept['department_id']; ?>" <?php echo ($selected_department == $dept['department_id']) ? 'selected' : ''; ?>>
                                                            <?php echo $dept['department_name']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="level">ระดับชั้น</label>
                                                    <select class="form-control" id="level" name="level">
                                                        <option value="">-- ทุกระดับชั้น --</option>
                                                        <option value="ปวช.1" <?php echo ($selected_level == 'ปวช.1') ? 'selected' : ''; ?>>ปวช.1</option>
                                                        <option value="ปวช.2" <?php echo ($selected_level == 'ปวช.2') ? 'selected' : ''; ?>>ปวช.2</option>
                                                        <option value="ปวช.3" <?php echo ($selected_level == 'ปวช.3') ? 'selected' : ''; ?>>ปวช.3</option>
                                                        <option value="ปวส.1" <?php echo ($selected_level == 'ปวส.1') ? 'selected' : ''; ?>>ปวส.1</option>
                                                        <option value="ปวส.2" <?php echo ($selected_level == 'ปวส.2') ? 'selected' : ''; ?>>ปวส.2</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="group_number">กลุ่ม</label>
                                                    <select class="form-control" id="group_number" name="group_number">
                                                        <option value="">-- ทุกกลุ่ม --</option>
                                                        <?php for($i=1; $i<=10; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo ($selected_group == $i) ? 'selected' : ''; ?>>
                                                            กลุ่ม <?php echo $i; ?>
                                                        </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="qr_validity_dept">อายุการใช้งาน QR Code (วัน)</label>
                                                    <input type="number" class="form-control" id="qr_validity_dept" name="qr_validity" 
                                                        min="1" max="365" value="<?php echo $qr_validity; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="start_date_dept">วันที่เริ่มต้น</label>
                                                    <input type="date" class="form-control" id="start_date_dept" name="start_date" 
                                                        value="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="expiry_date_dept">วันที่หมดอายุ (หากกำหนด)</label>
                                                    <input type="date" class="form-control" id="expiry_date_dept" name="expiry_date" 
                                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" name="search" class="btn btn-primary form-control">
                                                        <i class="material-icons">search</i> ค้นหา
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($search_performed && !empty($students)): ?>
                    <!-- ผลการค้นหา -->
                    <div class="search-results mt-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title">ผลการค้นหา (<?php echo count($students); ?> คน)</h6>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-success" id="selectAllBtn">
                                        <i class="material-icons">check_box</i> เลือกทั้งหมด
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">
                                        <i class="material-icons">check_box_outline_blank</i> ยกเลิกการเลือก
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="post" action="" id="generateQRForm">
                                    <input type="hidden" name="qr_validity" value="<?php echo $qr_validity; ?>">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="50px" class="text-center">เลือก</th>
                                                    <th width="100px">รหัสนักเรียน</th>
                                                    <th>ชื่อ-สกุล</th>
                                                    <th>ระดับชั้น</th>
                                                    <th>แผนก</th>
                                                    <th>กลุ่ม</th>
                                                    <th width="100px" class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <div class="form-check">
                                                            <input class="form-check-input student-checkbox" type="checkbox" 
                                                                name="selected_students[]" value="<?php echo $student['student_id']; ?>">
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['level'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($student['department_name'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($student['group_number'] ?? '-'); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-info single-qr-btn" 
                                                            data-student-id="<?php echo $student['student_id']; ?>"
                                                            data-student-code="<?php echo htmlspecialchars($student['student_code']); ?>"
                                                            data-student-name="<?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>"
                                                            data-class="<?php echo htmlspecialchars(($student['level'] ?? '') . ' ' . ($student['department_name'] ?? '') . ' กลุ่ม ' . ($student['group_number'] ?? '')); ?>">
                                                            <i class="material-icons">qr_code</i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="form-group mt-3">
                                        <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                                        <input type="hidden" name="expiry_date" value="<?php echo $expiry_date; ?>">
                                        <button type="submit" name="generate_qr" class="btn btn-primary" id="generateQRBtn" disabled>
                                            <i class="material-icons">print</i> สร้างและพิมพ์ QR Code
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($search_performed): ?>
                    <div class="alert alert-info mt-4">
                        <i class="material-icons">info</i>
                        <span>ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่ค้นหา</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดง QR Code รายบุคคล -->
<div class="modal fade" id="singleQRModal" tabindex="-1" role="dialog" aria-labelledby="singleQRModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="singleQRModalLabel">สร้าง QR Code สำหรับนักเรียน</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="mb-3" id="studentInfo"></div>
                    <div class="form-group">
                        <label for="singleQrValidity">อายุการใช้งาน QR Code (วัน)</label>
                        <input type="number" class="form-control" id="singleQrValidity" min="1" max="365" value="7">
                    </div>
                    <div class="form-group">
                        <label for="singleStartDate">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="singleStartDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="singleExpiryDate">วันที่หมดอายุ (หากกำหนด)</label>
                        <input type="date" class="form-control" id="singleExpiryDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        <small class="text-muted">หากไม่ระบุ จะคำนวณจากอายุการใช้งาน</small>
                    </div>
                    <div id="qrPreviewContainer" class="d-none">
                        <div class="qr-card">
                            <div class="qr-header">
                                <img src="assets/images/logo.png" alt="Logo" class="qr-logo">
                                <div class="qr-title">
                                    <h5>วิทยาลัยการอาชีพปราสาท</h5>
                                    <p>QR Code สำหรับเช็คชื่อเข้าแถว</p>
                                </div>
                            </div>
                            <div class="qr-body">
                                <div id="qrPreview"></div>
                                <div class="student-details">
                                    <p class="student-name" id="previewStudentName"></p>
                                    <p class="student-id" id="previewStudentId"></p>
                                    <p class="student-class" id="previewStudentClass"></p>
                                </div>
                            </div>
                            <div class="qr-footer">
                                <p>วันหมดอายุ: <span id="previewExpireDate"></span></p>
                                <p class="system-name">ระบบน้องชูใจ AI ดูแลผู้เรียน</p>
                            </div>
                        </div>
                    </div>
                    <div id="qrLoadingContainer">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">กำลังโหลด...</span>
                        </div>
                        <p class="mt-2">กำลังสร้าง QR Code...</p>
                    </div>
                    <div id="qrErrorContainer" class="d-none">
                        <div class="alert alert-danger">
                            <i class="material-icons">error</i>
                            <span id="qrErrorMessage">ไม่สามารถสร้าง QR Code ได้</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="generateSingleQR">สร้าง QR Code</button>
                <button type="button" class="btn btn-success d-none" id="printSingleQR">
                    <i class="material-icons">print</i> พิมพ์
                </button>
            </div>
        </div>
    </div>
</div>
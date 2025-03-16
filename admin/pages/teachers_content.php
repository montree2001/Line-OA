<?php
// แสดงข้อความความสำเร็จถ้ามี
if (isset($data['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="material-icons">check_circle</span> <?php echo $data['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- สถิติภาพรวม -->
<div class="row mb-4">
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon blue me-3">
                    <span class="material-icons">person</span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">ครูที่ปรึกษาทั้งหมด</h5>
                    <h2 class="mb-0 mt-2 text-primary"><?php echo $data['teachers_stats']['total']; ?> คน</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon green me-3">
                    <span class="material-icons">groups</span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">ดูแลชั้นเรียน</h5>
                    <h2 class="mb-0 mt-2 text-success"><?php echo $data['teachers_stats']['classrooms']; ?> ห้อง</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon amber me-3">
                    <span class="material-icons">school</span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">ดูแลนักเรียน</h5>
                    <h2 class="mb-0 mt-2 text-warning"><?php echo $data['teachers_stats']['students']; ?> คน</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ตารางครูที่ปรึกษา -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title">
                <span class="material-icons align-middle me-1">format_list_bulleted</span>
                รายชื่อครูที่ปรึกษาทั้งหมด
            </h5>
            
            <!-- ตัวกรองข้อมูล -->
            <div class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <span class="material-icons">search</span>
                    </span>
                    <input type="text" class="form-control" placeholder="ค้นหาครูที่ปรึกษา..." id="searchTeacher">
                </div>
                
                <select class="form-select" id="filterDepartment">
                    <option value="">ทุกกลุ่มสาระ</option>
                    <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                    <option value="คณิตศาสตร์">คณิตศาสตร์</option>
                    <option value="ภาษาไทย">ภาษาไทย</option>
                    <option value="ภาษาอังกฤษ">ภาษาอังกฤษ</option>
                    <option value="สังคมศึกษา">สังคมศึกษา</option>
                </select>
                
                <select class="form-select" id="filterStatus">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">ปฏิบัติงานอยู่</option>
                    <option value="inactive">ไม่ได้ปฏิบัติงาน</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="5%">รหัส</th>
                        <th scope="col" width="20%">ชื่อ-นามสกุล</th>
                        <th scope="col" width="15%">กลุ่มสาระ</th>
                        <th scope="col" width="10%">ตำแหน่ง</th>
                        <th scope="col" width="10%">ที่ปรึกษา</th>
                        <th scope="col" width="10%">นักเรียน</th>
                        <th scope="col" width="15%">ติดต่อ</th>
                        <th scope="col" width="5%">สถานะ</th>
                        <th scope="col" width="10%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['teachers'] as $teacher): ?>
                    <tr>
                        <td><?php echo $teacher['code']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="teacher-avatar me-2">
                                    <?php echo substr($teacher['name'], 9, 1); ?>
                                </div>
                                <div>
                                    <?php echo $teacher['name']; ?>
                                    <small class="text-muted d-block"><?php echo $teacher['gender']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $teacher['department']; ?></td>
                        <td><?php echo $teacher['position']; ?></td>
                        <td><?php echo $teacher['class']; ?></td>
                        <td>
                            <?php if ($teacher['students_count'] > 0): ?>
                                <span class="badge rounded-pill text-bg-primary"><?php echo $teacher['students_count']; ?> คน</span>
                            <?php else: ?>
                                <span class="badge rounded-pill text-bg-secondary">ไม่มี</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><small><i class="material-icons tiny-icon">phone</i> <?php echo $teacher['phone']; ?></small></div>
                            <div><small><i class="material-icons tiny-icon">email</i> <?php echo $teacher['email']; ?></small></div>
                        </td>
                        <td>
                            <?php if ($teacher['status'] == 'active'): ?>
                                <span class="badge rounded-pill text-bg-success">ปฏิบัติงาน</span>
                            <?php else: ?>
                                <span class="badge rounded-pill text-bg-danger">ไม่ปฏิบัติงาน</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" onclick="showEditTeacherModal(<?php echo $teacher['id']; ?>)" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="showDeleteConfirmation(<?php echo $teacher['id']; ?>)" title="ลบ">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- การแบ่งหน้า (Pagination) -->
        <nav aria-label="Page navigation" class="d-flex justify-content-end mt-3">
            <ul class="pagination">
                <li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- โมดัลเพิ่มครูที่ปรึกษา -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeacherModalLabel">เพิ่มครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="teachers.php" method="POST" id="addTeacherForm">
                    <input type="hidden" name="add_teacher" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacherCode" class="form-label">รหัสครู</label>
                            <input type="text" class="form-control" id="teacherCode" name="teacher_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="teacherPosition" class="form-label">ตำแหน่ง</label>
                            <select class="form-select" id="teacherPosition" name="teacher_position" required>
                                <option value="">-- เลือกตำแหน่ง --</option>
                                <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                                <option value="ครู">ครู</option>
                                <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                                <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                                <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                                <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacherName" class="form-label">ชื่อ-นามสกุล</label>
                            <div class="input-group">
                                <select class="form-select" style="max-width: 100px;" name="teacher_prefix">
                                    <option value="อาจารย์">อาจารย์</option>
                                    <option value="นาย">นาย</option>
                                    <option value="นาง">นาง</option>
                                    <option value="นางสาว">นางสาว</option>
                                    <option value="ดร.">ดร.</option>
                                </select>
                                <input type="text" class="form-control" id="teacherName" name="teacher_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="teacherGender" class="form-label">เพศ</label>
                            <select class="form-select" id="teacherGender" name="teacher_gender" required>
                                <option value="">-- เลือกเพศ --</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                                <option value="ไม่ระบุ">ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacherDepartment" class="form-label">กลุ่มสาระ</label>
                            <select class="form-select" id="teacherDepartment" name="teacher_department" required>
                                <option value="">-- เลือกกลุ่มสาระ --</option>
                                <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                                <option value="คณิตศาสตร์">คณิตศาสตร์</option>
                                <option value="ภาษาไทย">ภาษาไทย</option>
                                <option value="ภาษาอังกฤษ">ภาษาอังกฤษ</option>
                                <option value="สังคมศึกษา">สังคมศึกษา</option>
                                <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                                <option value="ศิลปะ">ศิลปะ</option>
                                <option value="การงานอาชีพ">การงานอาชีพ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="teacherClass" class="form-label">ที่ปรึกษาชั้น</label>
                            <select class="form-select" id="teacherClass" name="teacher_class">
                                <option value="">-- ไม่เป็นที่ปรึกษา --</option>
                                <option value="ม.1/1">ม.1/1</option>
                                <option value="ม.1/2">ม.1/2</option>
                                <option value="ม.1/3">ม.1/3</option>
                                <option value="ม.2/1">ม.2/1</option>
                                <option value="ม.2/2">ม.2/2</option>
                                <option value="ม.2/3">ม.2/3</option>
                                <option value="ม.3/1">ม.3/1</option>
                                <option value="ม.3/2">ม.3/2</option>
                                <option value="ม.3/3">ม.3/3</option>
                                <option value="ม.4/1">ม.4/1</option>
                                <option value="ม.4/2">ม.4/2</option>
                                <option value="ม.4/3">ม.4/3</option>
                                <option value="ม.5/1">ม.5/1</option>
                                <option value="ม.5/2">ม.5/2</option>
                                <option value="ม.5/3">ม.5/3</option>
                                <option value="ม.6/1">ม.6/1</option>
                                <option value="ม.6/2">ม.6/2</option>
                                <option value="ม.6/3">ม.6/3</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacherPhone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="teacherPhone" name="teacher_phone" placeholder="0xx-xxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <label for="teacherEmail" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="teacherEmail" name="teacher_email" placeholder="example@prasat.ac.th">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="teacherStatus" class="form-label">สถานะ</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="teacher_status" id="statusActive" value="active" checked>
                                <label class="form-check-label" for="statusActive">
                                    ปฏิบัติงานอยู่
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="teacher_status" id="statusInactive" value="inactive">
                                <label class="form-check-label" for="statusInactive">
                                    ไม่ได้ปฏิบัติงาน
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('addTeacherForm').submit();">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขครูที่ปรึกษา -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">แก้ไขข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="teachers.php" method="POST" id="editTeacherForm">
                    <input type="hidden" name="edit_teacher" value="1">
                    <input type="hidden" name="teacher_id" id="editTeacherId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherCode" class="form-label">รหัสครู</label>
                            <input type="text" class="form-control" id="editTeacherCode" name="teacher_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherPosition" class="form-label">ตำแหน่ง</label>
                            <select class="form-select" id="editTeacherPosition" name="teacher_position" required>
                                <option value="">-- เลือกตำแหน่ง --</option>
                                <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                                <option value="ครู">ครู</option>
                                <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                                <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                                <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                                <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherName" class="form-label">ชื่อ-นามสกุล</label>
                            <div class="input-group">
                                <select class="form-select" style="max-width: 100px;" name="teacher_prefix" id="editTeacherPrefix">
                                    <option value="อาจารย์">อาจารย์</option>
                                    <option value="นาย">นาย</option>
                                    <option value="นาง">นาง</option>
                                    <option value="นางสาว">นางสาว</option>
                                    <option value="ดร.">ดร.</option>
                                </select>
                                <input type="text" class="form-control" id="editTeacherName" name="teacher_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherGender" class="form-label">เพศ</label>
                            <select class="form-select" id="editTeacherGender" name="teacher_gender" required>
                                <option value="">-- เลือกเพศ --</option>
                                <option value="ชาย">ชาย</option>
                                <option value="หญิง">หญิง</option>
                                <option value="ไม่ระบุ">ไม่ระบุ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherDepartment" class="form-label">กลุ่มสาระ</label>
                            <select class="form-select" id="editTeacherDepartment" name="teacher_department" required>
                                <option value="">-- เลือกกลุ่มสาระ --</option>
                                <option value="วิทยาศาสตร์">วิทยาศาสตร์</option>
                                <option value="คณิตศาสตร์">คณิตศาสตร์</option>
                                <option value="ภาษาไทย">ภาษาไทย</option>
                                <option value="ภาษาอังกฤษ">ภาษาอังกฤษ</option>
                                <option value="สังคมศึกษา">สังคมศึกษา</option>
                                <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                                <option value="ศิลปะ">ศิลปะ</option>
                                <option value="การงานอาชีพ">การงานอาชีพ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherClass" class="form-label">ที่ปรึกษาชั้น</label>
                            <select class="form-select" id="editTeacherClass" name="teacher_class">
                                <option value="">-- ไม่เป็นที่ปรึกษา --</option>
                                <option value="ม.1/1">ม.1/1</option>
                                <option value="ม.1/2">ม.1/2</option>
                                <option value="ม.1/3">ม.1/3</option>
                                <option value="ม.2/1">ม.2/1</option>
                                <option value="ม.2/2">ม.2/2</option>
                                <option value="ม.2/3">ม.2/3</option>
                                <option value="ม.3/1">ม.3/1</option>
                                <option value="ม.3/2">ม.3/2</option>
                                <option value="ม.3/3">ม.3/3</option>
                                <option value="ม.4/1">ม.4/1</option>
                                <option value="ม.4/2">ม.4/2</option>
                                <option value="ม.4/3">ม.4/3</option>
                                <option value="ม.5/1">ม.5/1</option>
                                <option value="ม.5/2">ม.5/2</option>
                                <option value="ม.5/3">ม.5/3</option>
                                <option value="ม.6/1">ม.6/1</option>
                                <option value="ม.6/2">ม.6/2</option>
                                <option value="ม.6/3">ม.6/3</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherPhone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="editTeacherPhone" name="teacher_phone" placeholder="0xx-xxx-xxxx">
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherEmail" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="editTeacherEmail" name="teacher_email" placeholder="example@prasat.ac.th">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="editTeacherStatus" class="form-label">สถานะ</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="teacher_status" id="editStatusActive" value="active">
                                <label class="form-check-label" for="editStatusActive">
                                    ปฏิบัติงานอยู่
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="teacher_status" id="editStatusInactive" value="inactive">
                                <label class="form-check-label" for="editStatusInactive">
                                    ไม่ได้ปฏิบัติงาน
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editTeacherForm').submit();">บันทึกการแก้ไข</button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลนำเข้าข้อมูลครู -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">นำเข้าข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="teachers.php" method="POST" id="importTeacherForm" enctype="multipart/form-data">
                    <input type="hidden" name="import_teachers" value="1">
                    
                    <div class="mb-3">
                        <label for="importFile" class="form-label">เลือกไฟล์ข้อมูล</label>
                        <input type="file" class="form-control" id="importFile" name="import_file" accept=".csv, .xlsx, .xls" required>
                        <div class="form-text">รองรับไฟล์ Excel (.xlsx, .xls) และ CSV (.csv)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ตัวอย่างรูปแบบข้อมูล</label>
                        <div class="alert alert-info small">
                            <strong>คอลัมน์ที่จำเป็น:</strong> รหัสครู, ชื่อ-นามสกุล, เพศ, ตำแหน่ง, กลุ่มสาระ<br>
                            <strong>คอลัมน์เพิ่มเติม:</strong> ที่ปรึกษาชั้น, เบอร์โทรศัพท์, อีเมล, สถานะ<br>
                            <a href="#" class="alert-link">ดาวน์โหลดไฟล์ตัวอย่าง</a>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="overwriteExisting" name="overwrite_existing">
                        <label class="form-check-label" for="overwriteExisting">
                            อัปเดตข้อมูลที่มีอยู่แล้ว (กรณีรหัสครูซ้ำ)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('importTeacherForm').submit();">นำเข้าข้อมูล</button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบครู -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">ยืนยันการลบข้อมูล</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบข้อมูลครูที่ปรึกษาคนนี้ใช่หรือไม่?</p>
                <p class="text-danger"><strong>คำเตือน:</strong> การลบข้อมูลจะไม่สามารถกู้คืนได้</p>
                <form action="teachers.php" method="POST" id="deleteTeacherForm">
                    <input type="hidden" name="delete_teacher" value="1">
                    <input type="hidden" name="teacher_id" id="deleteTeacherId">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteTeacherForm').submit();">ยืนยันการลบ</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS เฉพาะหน้านี้ -->
<style>
/* สไตล์ภาพรวมของระบบที่ไม่ขัดกับ Bootstrap */
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon.blue {
    background-color: var(--secondary-color-light, #e3f2fd);
    color: var(--secondary-color, #1976d2);
}

.stat-icon.green {
    background-color: var(--success-color-light, #e8f5e9);
    color: var(--success-color, #4caf50);
}

.stat-icon.amber {
    background-color: var(--warning-color-light, #fff8e1);
    color: var(--warning-color, #ff9800);
}

.stat-icon .material-icons {
    font-size: 28px;
}

/* ปรับแต่ง avatar ครู */
.teacher-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: #e3f2fd;
    color: #1976d2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* ไอคอนขนาดเล็ก */
.tiny-icon {
    font-size: 14px !important;
    vertical-align: middle;
    margin-right: 3px;
}

/* การแจ้งเตือน */
.alert {
    display: flex;
    align-items: center;
}

.alert .material-icons {
    margin-right: 10px;
}

/* ปรับแต่ง modal ให้เข้ากับ UI ของระบบ */
.modal-header {
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.modal-footer {
    background-color: #f5f5f5;
    border-top: 1px solid #e0e0e0;
}

/* ปรับแต่ง form control ให้เข้ากับ UI ของระบบ */
.form-control:focus, .form-select:focus {
    border-color: var(--primary-color, #06c755);
    box-shadow: 0 0 0 0.25rem rgba(6, 199, 85, 0.25);
}

/* ปรับแต่ง pagination ให้เข้ากับ UI ของระบบ */
.pagination .page-link {
    color: var(--primary-color, #06c755);
}

.pagination .page-item.active .page-link {
    background-color: var(--primary-color, #06c755);
    border-color: var(--primary-color, #06c755);
    color: white;
}

/* ให้ปุ่มคงความเป็น Material Design */
.btn {
    text-transform: none;
    border-radius: 4px;
}

.btn-primary {
    background-color: var(--primary-color, #06c755);
    border-color: var(--primary-color, #06c755);
}

.btn-primary:hover {
    background-color: var(--primary-color-dark, #05a647);
    border-color: var(--primary-color-dark, #05a647);
}
</style>

<!-- JavaScript เฉพาะหน้านี้ -->
<script>
// แสดงโมดัลเพิ่มครูที่ปรึกษา
function showAddTeacherModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addTeacherForm').reset();
    
    // แสดงโมดัล
    var addModal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
    addModal.show();
}

// แสดงโมดัลแก้ไขครูที่ปรึกษา
function showEditTeacherModal(teacherId) {
    // ในการใช้งานจริง จะมีการส่ง AJAX request ไปดึงข้อมูลครูจาก backend
    // แต่ในตัวอย่างนี้ เราจะจำลองข้อมูล
    
    // สมมติว่านี่คือข้อมูลครูที่เราดึงมา
    const teacherData = getTeacherById(teacherId);
    
    // กรอกข้อมูลเดิมลงในฟอร์ม
    document.getElementById('editTeacherId').value = teacherData.id;
    document.getElementById('editTeacherCode').value = teacherData.code;
    
    // แยกคำนำหน้าและชื่อ
    const nameParts = teacherData.name.split(' ');
    const prefix = nameParts[0];
    const name = teacherData.name.substring(prefix.length + 1);
    
    document.getElementById('editTeacherPrefix').value = prefix;
    document.getElementById('editTeacherName').value = name;
    document.getElementById('editTeacherGender').value = teacherData.gender;
    document.getElementById('editTeacherPosition').value = teacherData.position;
    document.getElementById('editTeacherDepartment').value = teacherData.department;
    document.getElementById('editTeacherClass').value = teacherData.class;
    document.getElementById('editTeacherPhone').value = teacherData.phone;
    document.getElementById('editTeacherEmail').value = teacherData.email;
    
    // ตั้งค่าสถานะ
    if (teacherData.status === 'active') {
        document.getElementById('editStatusActive').checked = true;
    } else {
        document.getElementById('editStatusInactive').checked = true;
    }
    
    // แสดงโมดัล
    var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
    editModal.show();
}

// แสดงโมดัลนำเข้าข้อมูล
function showImportModal() {
    var importModal = new bootstrap.Modal(document.getElementById('importModal'));
    importModal.show();
}

// แสดงโมดัลยืนยันการลบ
function showDeleteConfirmation(teacherId) {
    document.getElementById('deleteTeacherId').value = teacherId;
    
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    deleteModal.show();
}

// ฟังก์ชันจำลองสำหรับดึงข้อมูลครูจาก ID
function getTeacherById(id) {
    // ข้อมูลตัวอย่าง (ในการใช้งานจริงจะดึงจากฐานข้อมูล)
    const teachers = [
        {
            id: 1,
            code: 'T001',
            name: 'อาจารย์ประสิทธิ์ ดีเลิศ',
            gender: 'ชาย',
            position: 'ครูชำนาญการพิเศษ',
            class: 'ม.6/2',
            department: 'วิทยาศาสตร์',
            phone: '081-234-5678',
            email: 'prasit.d@prasat.ac.th',
            status: 'active'
        },
        {
            id: 2,
            code: 'T002',
            name: 'อาจารย์วันดี สดใส',
            gender: 'หญิง',
            position: 'ครูชำนาญการ',
            class: 'ม.5/3',
            department: 'ภาษาไทย',
            phone: '089-876-5432',
            email: 'wandee.s@prasat.ac.th',
            status: 'active'
        },
        {
            id: 3,
            code: 'T003',
            name: 'อาจารย์อิศรา สุขใจ',
            gender: 'ชาย',
            position: 'ครู',
            class: 'ม.5/1',
            department: 'คณิตศาสตร์',
            phone: '062-345-6789',
            email: 'issara.s@prasat.ac.th',
            status: 'active'
        },
        {
            id: 4,
            code: 'T004',
            name: 'อาจารย์ใจดี มากเมตตา',
            gender: 'หญิง',
            position: 'ครูชำนาญการพิเศษ',
            class: 'ม.4/1',
            department: 'ภาษาอังกฤษ',
            phone: '091-234-5678',
            email: 'jaidee.m@prasat.ac.th',
            status: 'active'
        },
        {
            id: 5,
            code: 'T005',
            name: 'อาจารย์สมหมาย ใจร่าเริง',
            gender: 'ชาย',
            position: 'ครูชำนาญการ',
            class: 'ม.4/2',
            department: 'สังคมศึกษา',
            phone: '098-765-4321',
            email: 'sommai.j@prasat.ac.th',
            status: 'inactive'
        }
    ];
    
    return teachers.find(teacher => teacher.id === id) || {};
}

// การค้นหาและกรองข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchTeacher');
    const filterDepartment = document.getElementById('filterDepartment');
    const filterStatus = document.getElementById('filterStatus');
    
    function applyFilters() {
        const searchValue = searchInput.value.toLowerCase();
        const departmentValue = filterDepartment.value.toLowerCase();
        const statusValue = filterStatus.value;
        
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const teacherName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const department = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const statusElement = row.querySelector('td:nth-child(8) .badge');
            const statusText = statusElement.textContent.toLowerCase();
            const statusClass = statusElement.classList.contains('text-bg-success') ? 'active' : 'inactive';
            
            // ตรวจสอบว่าตรงกับเงื่อนไขการค้นหาหรือไม่
            const matchesSearch = searchValue === '' || teacherName.includes(searchValue);
            const matchesDepartment = departmentValue === '' || department.includes(departmentValue);
            const matchesStatus = statusValue === '' || statusValue === statusClass;
            
            // แสดงหรือซ่อนแถว
            if (matchesSearch && matchesDepartment && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // ผูกเหตุการณ์กับการค้นหาและกรอง
    searchInput.addEventListener('input', applyFilters);
    filterDepartment.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    
    // แสดงการแจ้งเตือนเมื่อมีข้อความความสำเร็จ
    const alertElement = document.querySelector('.alert');
    if (alertElement) {
        // ซ่อนการแจ้งเตือนหลังจาก 5 วินาที
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
            bsAlert.close();
        }, 5000);
    }
});
</script>
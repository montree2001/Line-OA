<?php
// แสดงข้อความความสำเร็จหรือข้อผิดพลาด (ถ้ามี)
if (isset($data['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="material-icons">check_circle</span> <?php echo $data['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($data['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span class="material-icons">error</span> <?php echo $data['error_message']; ?>
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
                    <input type="text" class="form-control" placeholder="ค้นหาครูที่ปรึกษา..." id="searchTeacher" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <select class="form-select" id="filterDepartment">
                    <option value="">ทุกแผนก</option>
                    <?php foreach ($data['departments'] as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($_GET['department']) && $_GET['department'] === $dept) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select class="form-select" id="filterStatus">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>ปฏิบัติงานอยู่</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : ''; ?>>ไม่ได้ปฏิบัติงาน</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="15%">ชื่อ-นามสกุล</th>
                        <th scope="col" width="10%">รหัส</th>
                        <th scope="col" width="10%">แผนก</th>
                        <th scope="col" width="10%">ตำแหน่ง</th>
                        <th scope="col" width="8%">นักเรียน</th>
                        <th scope="col" width="15%">ติดต่อ</th>
                        <th scope="col" width="5%">สถานะ</th>
                        <th scope="col" width="17%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data['teachers']) > 0): ?>
                        <?php foreach ($data['teachers'] as $teacher): ?>
                        <tr data-id="<?php echo $teacher['teacher_id']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="teacher-avatar me-2">
                                        <?php echo substr($teacher['first_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $teacher['national_id']; ?></td>
                            <td><?php echo $teacher['department']; ?></td>
                            <td><?php echo $teacher['position']; ?></td>
                            <td>
                                <?php if (!empty($teacher['students_count']) && $teacher['students_count'] > 0): ?>
                                    <span class="badge rounded-pill text-bg-primary"><?php echo $teacher['students_count']; ?> คน</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-bg-secondary">ไม่มี</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><small><i class="material-icons tiny-icon">phone</i> <?php echo !empty($teacher['phone_number']) ? $teacher['phone_number'] : '-'; ?></small></div>
                                <div><small><i class="material-icons tiny-icon">email</i> <?php echo !empty($teacher['email']) ? $teacher['email'] : '-'; ?></small></div>
                            </td>
                            <td>
                                <?php if ($teacher['is_active']): ?>
                                    <span class="badge rounded-pill text-bg-success">ปฏิบัติงาน</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-bg-danger">ไม่ปฏิบัติงาน</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" onclick="showEditTeacherModal(<?php echo $teacher['teacher_id']; ?>)" title="แก้ไข">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <!-- ปุ่มเปลี่ยนสถานะ -->
                                    <form method="post" class="d-inline" id="toggleForm<?php echo $teacher['teacher_id']; ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $teacher['is_active'] ? 'inactive' : 'active'; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-<?php echo $teacher['is_active'] ? 'warning' : 'success'; ?>" 
                                                onclick="confirmToggleStatus(<?php echo $teacher['teacher_id']; ?>, '<?php echo $teacher['is_active'] ? 'ระงับ' : 'เปิดใช้งาน'; ?>')" 
                                                title="<?php echo $teacher['is_active'] ? 'ระงับการใช้งาน' : 'เปิดใช้งาน'; ?>">
                                            <span class="material-icons"><?php echo $teacher['is_active'] ? 'block' : 'check_circle'; ?></span>
                                        </button>
                                    </form>
                                    <!-- ปุ่มลบ -->
                                    <button class="btn btn-sm btn-outline-danger" onclick="showDeleteConfirmation(<?php echo $teacher['teacher_id']; ?>, '<?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>')" title="ลบ">
                                        <span class="material-icons">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <span class="material-icons" style="font-size: 48px; display: block; margin-bottom: 10px;">search_off</span>
                                    ไม่พบข้อมูลครูที่ปรึกษา
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- การแบ่งหน้า (Pagination) - ในอนาคตอาจเพิ่มฟีเจอร์นี้ -->
        <?php if (count($data['teachers']) > 20): ?>
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
        <?php endif; ?>
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
                    
                    <!-- ปรับให้ช่องกรอกข้อมูลกว้างขึ้น โดยให้แต่ละฟิลด์อยู่คนละบรรทัด -->
                    <div class="mb-3">
                        <label for="teacherNationalId" class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="teacherNationalId" name="teacher_national_id" 
                               placeholder="1234567890123" required maxlength="13" pattern="\d{13}">
                        <div class="form-text">กรุณากรอกเลข 13 หลัก</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacherPosition" class="form-label">ตำแหน่ง <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacherPosition" name="teacher_position" required>
                            <option value="">-- เลือกตำแหน่ง --</option>
                            <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                            <option value="ครู">ครู</option>
                            <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                            <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                            <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                            <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                            <option value="ครูจ้างสอน">ครูจ้างสอน</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="teacherPrefix" class="form-label">คำนำหน้า</label>
                            <select class="form-select" id="teacherPrefix" name="teacher_prefix">
                                <option value="อาจารย์">อาจารย์</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="ศ.">ศ.</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label for="teacherFirstName" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teacherFirstName" name="teacher_first_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacherLastName" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="teacherLastName" name="teacher_last_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacherDepartment" class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacherDepartment" name="teacher_department" required>
                            <option value="">-- เลือกแผนก --</option>
                            <option value="ช่างยนต์">ช่างยนต์</option>
                            <option value="ช่างกลโรงงาน">ช่างกลโรงงาน</option>
                            <option value="ช่างไฟฟ้ากำลัง">ช่างไฟฟ้ากำลัง</option>
                            <option value="ช่างอิเล็กทรอนิกส์">ช่างอิเล็กทรอนิกส์</option>
                            <option value="การบัญชี">การบัญชี</option>
                            <option value="เทคโนโลยีสารสนเทศ">เทคโนโลยีสารสนเทศ</option>
                            <option value="การโรงแรม">การโรงแรม</option>
                            <option value="ช่างเชื่อมโลหะ">ช่างเชื่อมโลหะ</option>
                            <option value="บริหาร">บริหาร</option>
                            <option value="สามัญ">สามัญ</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    
                    <!-- ลบฟิลด์ "ที่ปรึกษาชั้น" ออกตามที่ต้องการ -->
                    
                    <div class="mb-3">
                        <label for="teacherPhone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-control" id="teacherPhone" name="teacher_phone" 
                               placeholder="0xx-xxx-xxxx" pattern="[0-9\-]{10,12}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacherEmail" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="teacherEmail" name="teacher_email" 
                               placeholder="example@prasat.ac.th">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
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
                    
                    <!-- ปรับให้ช่องกรอกข้อมูลกว้างขึ้น โดยให้แต่ละฟิลด์อยู่คนละบรรทัด -->
                    <div class="mb-3">
                        <label for="editTeacherNationalId" class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTeacherNationalId" name="teacher_national_id" 
                               placeholder="1234567890123" required maxlength="13" pattern="\d{13}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTeacherPosition" class="form-label">ตำแหน่ง <span class="text-danger">*</span></label>
                        <select class="form-select" id="editTeacherPosition" name="teacher_position" required>
                            <option value="">-- เลือกตำแหน่ง --</option>
                            <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                            <option value="ครู">ครู</option>
                            <option value="ครูชำนาญการ">ครูชำนาญการ</option>
                            <option value="ครูชำนาญการพิเศษ">ครูชำนาญการพิเศษ</option>
                            <option value="ครูเชี่ยวชาญ">ครูเชี่ยวชาญ</option>
                            <option value="ครูเชี่ยวชาญพิเศษ">ครูเชี่ยวชาญพิเศษ</option>
                            <option value="ครูจ้างสอน">ครูจ้างสอน</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="editTeacherPrefix" class="form-label">คำนำหน้า</label>
                            <select class="form-select" id="editTeacherPrefix" name="teacher_prefix">
                                <option value="อาจารย์">อาจารย์</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="ศ.">ศ.</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label for="editTeacherFirstName" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTeacherFirstName" name="teacher_first_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTeacherLastName" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTeacherLastName" name="teacher_last_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTeacherDepartment" class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select class="form-select" id="editTeacherDepartment" name="teacher_department" required>
                            <option value="">-- เลือกแผนก --</option>
                            <option value="ช่างยนต์">ช่างยนต์</option>
                            <option value="ช่างกลโรงงาน">ช่างกลโรงงาน</option>
                            <option value="ช่างไฟฟ้ากำลัง">ช่างไฟฟ้ากำลัง</option>
                            <option value="ช่างอิเล็กทรอนิกส์">ช่างอิเล็กทรอนิกส์</option>
                            <option value="การบัญชี">การบัญชี</option>
                            <option value="เทคโนโลยีสารสนเทศ">เทคโนโลยีสารสนเทศ</option>
                            <option value="การโรงแรม">การโรงแรม</option>
                            <option value="ช่างเชื่อมโลหะ">ช่างเชื่อมโลหะ</option>
                            <option value="บริหาร">บริหาร</option>
                            <option value="สามัญ">สามัญ</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    
                    <!-- ลบฟิลด์ "ที่ปรึกษาชั้น" ออกตามที่ต้องการ -->
                    
                    <div class="mb-3">
                        <label for="editTeacherPhone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-control" id="editTeacherPhone" name="teacher_phone" 
                               placeholder="0xx-xxx-xxxx" pattern="[0-9\-]{10,12}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTeacherEmail" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="editTeacherEmail" name="teacher_email" 
                               placeholder="example@prasat.ac.th">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
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
                            <strong>คอลัมน์ที่จำเป็น:</strong> เลขบัตรประชาชน, ชื่อ, นามสกุล<br>
                            <strong>คอลัมน์เพิ่มเติม:</strong> คำนำหน้า, แผนก, ตำแหน่ง, เบอร์โทรศัพท์, อีเมล, สถานะ<br>
                            <a href="download_sample.php?type=teacher" class="alert-link" target="_blank">ดาวน์โหลดไฟล์ตัวอย่าง</a>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="overwriteExisting" name="overwrite_existing">
                        <label class="form-check-label" for="overwriteExisting">
                            อัปเดตข้อมูลที่มีอยู่แล้ว (กรณีเลขบัตรประชาชนซ้ำ)
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
                <p>คุณต้องการลบข้อมูลครูที่ปรึกษา <strong id="deleteTeacherName"></strong> ใช่หรือไม่?</p>
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

<!-- โมดัลยืนยันการเปลี่ยนสถานะครู -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="toggleStatusModalLabel">ยืนยันการเปลี่ยนสถานะ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการ <strong id="toggleAction"></strong> ครูที่ปรึกษาคนนี้ใช่หรือไม่?</p>
                <p class="text-warning"><i class="material-icons align-middle">warning</i> การเปลี่ยนสถานะจะส่งผลต่อสิทธิ์การเช็คชื่อของครูที่ปรึกษา</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-warning" id="confirmToggleBtn">ยืนยัน</button>
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
// การค้นหาและกรองข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded");
    const searchInput = document.getElementById('searchTeacher');
    const filterDepartment = document.getElementById('filterDepartment');
    const filterStatus = document.getElementById('filterStatus');
    
    // ฟังก์ชันสำหรับการกรอง (นำไปใช้กับ URL parameter)
    function applyFilters() {
        const searchValue = searchInput.value.trim();
        const departmentValue = filterDepartment.value;
        const statusValue = filterStatus.value;
        
        // สร้าง URL ใหม่พร้อมพารามิเตอร์
        let url = 'teachers.php';
        let params = [];
        
        if (searchValue) {
            params.push('search=' + encodeURIComponent(searchValue));
        }
        if (departmentValue) {
            params.push('department=' + encodeURIComponent(departmentValue));
        }
        if (statusValue) {
            params.push('status=' + encodeURIComponent(statusValue));
        }
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        // นำทางไปยัง URL ใหม่
        window.location.href = url;
    }
    
    // ผูกกิจกรรมกับปุ่มค้นหา
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }
    
    // ผูกกิจกรรมกับตัวกรอง
    if (filterDepartment) {
        filterDepartment.addEventListener('change', applyFilters);
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilters);
    }
});

// แสดงโมดัลเพิ่มครูที่ปรึกษา
function showAddTeacherModal() {
    console.log("Show Add Modal called");
    // รีเซ็ตฟอร์ม
    document.getElementById('addTeacherForm').reset();
    
    // แสดงโมดัล
    var addModal = new bootstrap.Modal(document.getElementById('addTeacherModal'));
    addModal.show();
}


// ฟังก์ชันสำรองสำหรับดึงข้อมูลครูจาก DOM (กรณี API ไม่ทำงาน)
function fallbackEditTeacher(teacherId) {
    console.log("Fallback edit for teacher ID:", teacherId);
    
    // ค้นหาแถวของครูในตาราง
    const teacherRow = document.querySelector(`tr[data-id="${teacherId}"]`);
    if (!teacherRow) {
        console.error("Teacher row not found for ID:", teacherId);
        alert("ไม่พบข้อมูลครูที่ต้องการแก้ไข");
        return;
    }
    
    try {
        // ดึงข้อมูลจาก DOM
        const fullNameContainer = teacherRow.querySelector('td:nth-child(1) div:nth-child(2)');
        const fullName = fullNameContainer ? fullNameContainer.textContent.trim() : '';
        console.log("Full name:", fullName);
        
        const nameParts = fullName.split(' ');
        let prefix = 'อาจารย์';
        let firstName = '';
        let lastName = '';
        
        if (nameParts.length >= 2) {
            prefix = nameParts[0];
            firstName = nameParts[1] || '';
            lastName = nameParts.slice(2).join(' ') || '';
        }
        
        const nationalIdCell = teacherRow.querySelector('td:nth-child(2)');
        const nationalId = nationalIdCell ? nationalIdCell.textContent.trim() : '';
        
        const departmentCell = teacherRow.querySelector('td:nth-child(3)');
        const department = departmentCell ? departmentCell.textContent.trim() : '';
        
        const positionCell = teacherRow.querySelector('td:nth-child(4)');
        const position = positionCell ? positionCell.textContent.trim() : '';
        
        const contactInfo = teacherRow.querySelector('td:nth-child(6)');
        const phoneElement = contactInfo ? contactInfo.querySelector('div:nth-child(1)') : null;
        const phone = phoneElement ? phoneElement.textContent.replace('phone', '').trim() : '';
        
        const emailElement = contactInfo ? contactInfo.querySelector('div:nth-child(2)') : null;
        const email = emailElement ? emailElement.textContent.replace('email', '').trim() : '';
        
        const statusBadge = teacherRow.querySelector('td:nth-child(7) .badge');
        const isActive = statusBadge && statusBadge.classList.contains('text-bg-success');
        
        console.log("Extracted data:", {
            teacherId, prefix, firstName, lastName, nationalId, department, position, phone, email, isActive
        });
        
        // กรอกข้อมูลในฟอร์ม
        document.getElementById('editTeacherId').value = teacherId;
        document.getElementById('editTeacherNationalId').value = nationalId;
        document.getElementById('editTeacherPrefix').value = prefix;
        document.getElementById('editTeacherFirstName').value = firstName;
        document.getElementById('editTeacherLastName').value = lastName;
        document.getElementById('editTeacherPosition').value = position;
        document.getElementById('editTeacherDepartment').value = department;
        
        document.getElementById('editTeacherPhone').value = phone === '-' ? '' : phone;
        document.getElementById('editTeacherEmail').value = email === '-' ? '' : email;
        
        // ตั้งค่าสถานะ
        if (isActive) {
            document.getElementById('editStatusActive').checked = true;
        } else {
            document.getElementById('editStatusInactive').checked = true;
        }
        
        // แสดงโมดัล
        var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
        editModal.show();
        
    } catch (error) {
        console.error("Error in fallbackEditTeacher:", error);
        alert("ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่อีกครั้ง");
    }
}

// แสดงโมดัลนำเข้าข้อมูล
function showImportModal() {
    console.log("Show Import Modal called");
    // รีเซ็ตฟอร์ม
    document.getElementById('importTeacherForm').reset();
    
    // แสดงโมดัล
    var importModal = new bootstrap.Modal(document.getElementById('importModal'));
    importModal.show();
}

// แสดงยืนยันการลบ
function showDeleteConfirmation(teacherId, teacherName) {
    console.log("Show Delete Confirmation for:", teacherId, teacherName);
    document.getElementById('deleteTeacherId').value = teacherId;
    document.getElementById('deleteTeacherName').textContent = teacherName;
    
    // แสดงโมดัล
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    deleteModal.show();
}

// แสดงยืนยันการเปลี่ยนสถานะ
function confirmToggleStatus(teacherId, action) {
    console.log("Confirm toggle status for:", teacherId, action);
    document.getElementById('toggleAction').textContent = action;
    
    // ตั้งค่าปุ่มยืนยัน
    const confirmBtn = document.getElementById('confirmToggleBtn');
    confirmBtn.onclick = function() {
        document.getElementById('toggleForm' + teacherId).submit();
    };
    
    // แสดงโมดัล
    var toggleModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    toggleModal.show();
}
</script>

<script>
// ทับค่าฟังก์ชัน JavaScript เดิมที่ใช้งานปุ่มแก้ไข
function showEditTeacherModal(teacherId) {
    console.log("Edit teacher ID:", teacherId);
    
    // ค้นหาแถวของครูในตาราง
    const teacherRow = document.querySelector(`tr[data-id="${teacherId}"]`);
    if (!teacherRow) {
        console.error("Teacher row not found for ID:", teacherId);
        alert("ไม่พบข้อมูลครูที่ต้องการแก้ไข");
        return;
    }
    
    try {
        // ดึงข้อมูลจาก DOM
        const fullNameContainer = teacherRow.querySelector('td:nth-child(1) div:nth-child(2)');
        const fullName = fullNameContainer ? fullNameContainer.textContent.trim() : '';
        console.log("Full name:", fullName);
        
        const nameParts = fullName.split(' ');
        let prefix = 'อาจารย์';
        let firstName = '';
        let lastName = '';
        
        if (nameParts.length >= 2) {
            prefix = nameParts[0];
            firstName = nameParts[1] || '';
            lastName = nameParts.slice(2).join(' ') || '';
        }
        
        const nationalIdCell = teacherRow.querySelector('td:nth-child(2)');
        const nationalId = nationalIdCell ? nationalIdCell.textContent.trim() : '';
        
        const departmentCell = teacherRow.querySelector('td:nth-child(3)');
        const department = departmentCell ? departmentCell.textContent.trim() : '';
        
        const positionCell = teacherRow.querySelector('td:nth-child(4)');
        const position = positionCell ? positionCell.textContent.trim() : '';
        
        const contactInfo = teacherRow.querySelector('td:nth-child(6)');
        const phoneElement = contactInfo ? contactInfo.querySelector('div:nth-child(1)') : null;
        const phone = phoneElement ? phoneElement.textContent.replace('phone', '').trim() : '';
        
        const emailElement = contactInfo ? contactInfo.querySelector('div:nth-child(2)') : null;
        const email = emailElement ? emailElement.textContent.replace('email', '').trim() : '';
        
        const statusBadge = teacherRow.querySelector('td:nth-child(7) .badge');
        const isActive = statusBadge && statusBadge.classList.contains('text-bg-success');
        
        console.log("Extracted data:", {
            teacherId, prefix, firstName, lastName, nationalId, department, position, phone, email, isActive
        });
        
        // กรอกข้อมูลในฟอร์ม
        document.getElementById('editTeacherId').value = teacherId;
        document.getElementById('editTeacherNationalId').value = nationalId;
        document.getElementById('editTeacherPrefix').value = prefix;
        document.getElementById('editTeacherFirstName').value = firstName;
        document.getElementById('editTeacherLastName').value = lastName;
        document.getElementById('editTeacherPosition').value = position;
        document.getElementById('editTeacherDepartment').value = department;
        
        document.getElementById('editTeacherPhone').value = phone === '-' ? '' : phone;
        document.getElementById('editTeacherEmail').value = email === '-' ? '' : email;
        
        // ตั้งค่าสถานะ
        if (isActive) {
            document.getElementById('editStatusActive').checked = true;
        } else {
            document.getElementById('editStatusInactive').checked = true;
        }
        
        // แสดงโมดัล
        var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
        editModal.show();
        
    } catch (error) {
        console.error("Error in fallbackEditTeacher:", error);
        alert("ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่อีกครั้ง");
    }
}
</script>
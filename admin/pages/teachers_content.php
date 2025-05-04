<!-- แก้ไขไฟล์ admin/pages/teachers_content.php -->

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
    <div class="col-md-3 col-sm-6 mb-3">
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
    <div class="col-md-3 col-sm-6 mb-3">
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
    <div class="col-md-3 col-sm-6 mb-3">
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
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon purple me-3">
                    <span class="material-icons">chat</span>
                </div>
                <div class="flex-grow-1">
                    <h5 class="card-title mb-0">เชื่อมต่อไลน์แล้ว</h5>
                    <h2 class="mb-0 mt-2" style="color: #06c755;"><?php echo $data['teachers_stats']['line_connected']; ?> คน</h2>
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

                <select class="form-select" id="filterLineStatus">
                    <option value="">สถานะไลน์ทั้งหมด</option>
                    <option value="connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'connected') ? 'selected' : ''; ?>>เชื่อมต่อไลน์แล้ว</option>
                    <option value="not_connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'not_connected') ? 'selected' : ''; ?>>ยังไม่เชื่อมต่อไลน์</option>
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
                        <th scope="col" width="5%">เชื่อมต่อไลน์</th>
                        <th scope="col" width="12%">จัดการ</th>
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
                                    <?php
                                    // ตรวจสอบสถานะการเชื่อมต่อ Line
                                    if (!empty($teacher['line_id']) && substr($teacher['line_id'], 0, 1) === 'U'): ?>
                                        <span class="badge rounded-pill" style="background-color: #06c755; color: white;">
                                            <i class="material-icons tiny-icon">check_circle</i> เชื่อมต่อแล้ว
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill text-bg-secondary">
                                            <i class="material-icons tiny-icon">cancel</i> ยังไม่เชื่อมต่อ
                                        </span>
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
                            <td colspan="9" class="text-center py-4">
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

<!-- CSS เพิ่มเติม -->
<style>
    /* สไตล์สำหรับกล่องสถิติ Line */
    .stat-icon.purple {
        background-color: #f0ebff;
        color: #8a3ffc;
    }

    /* สี Line Official Account */
    .line-color {
        color: #06c755;
    }

    .line-badge {
        background-color: #06c755;
        color: white;
    }
</style>

<!-- JavaScript เพิ่มเติม -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // เพิ่มตัวกรองตามสถานะ Line
        const filterLineStatus = document.getElementById('filterLineStatus');
        if (filterLineStatus) {
            filterLineStatus.addEventListener('change', function() {
                applyFilters();
            });
        }

        // อัปเดตฟังก์ชัน applyFilters เพื่อรองรับการกรองตามสถานะ Line
        function applyFilters() {
            const searchValue = document.getElementById('searchTeacher').value.trim();
            const departmentValue = document.getElementById('filterDepartment').value;
            const statusValue = document.getElementById('filterStatus').value;
            const lineStatusValue = document.getElementById('filterLineStatus').value;

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
            if (lineStatusValue) {
                params.push('line_status=' + encodeURIComponent(lineStatusValue));
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            // นำทางไปยัง URL ใหม่
            window.location.href = url;
        }
    });
</script>


<!-- Modal -->


<!-- แบบฟอร์มและโมดัลสำหรับหน้าครูที่ปรึกษา -->

<!-- โมดัลเพิ่มครูที่ปรึกษา -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeacherModalLabel">เพิ่มครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTeacherForm" method="post" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="add_teacher" value="1">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="teacher_prefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                            <select class="form-select" id="teacher_prefix" name="teacher_prefix" required>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="ศ.">ศ.</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                            <div class="invalid-feedback">กรุณาเลือกคำนำหน้า</div>
                        </div>
                        <div class="col-md-4">
                            <label for="teacher_first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teacher_first_name" name="teacher_first_name" required>
                            <div class="invalid-feedback">กรุณากรอกชื่อ</div>
                        </div>
                        <div class="col-md-4">
                            <label for="teacher_last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teacher_last_name" name="teacher_last_name" required>
                            <div class="invalid-feedback">กรุณากรอกนามสกุล</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacher_national_id" class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teacher_national_id" name="teacher_national_id" maxlength="13" required>
                            <div class="invalid-feedback">กรุณากรอกเลขบัตรประชาชน 13 หลัก</div>
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_position" class="form-label">ตำแหน่ง</label>
                            <input type="text" class="form-control" id="teacher_position" name="teacher_position">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="teacher_department" class="form-label">แผนก/ฝ่าย <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacher_department" name="teacher_department" required>
                            <option value="">-- เลือกแผนก/ฝ่าย --</option>
                            <?php foreach ($data['departments'] as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกแผนก/ฝ่าย</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacher_phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="teacher_phone" name="teacher_phone" maxlength="10" pattern="[0-9]{9,10}">
                            <div class="invalid-feedback">กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง</div>
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="teacher_email" name="teacher_email">
                            <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สถานะ <span class="text-danger">*</span></label>
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

                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <span class="material-icons me-2">info</span>
                            <div>
                                <strong>หมายเหตุ:</strong> เมื่อเพิ่มข้อมูลแล้ว ครูจำเป็นต้องเชื่อมต่อบัญชี LINE เพื่อเข้าใช้งานระบบ
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขครูที่ปรึกษา -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">แก้ไขข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTeacherForm" method="post" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="edit_teacher" value="1">
                    <input type="hidden" name="teacher_id" id="editTeacherId">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="editTeacherPrefix" class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                            <select class="form-select" id="editTeacherPrefix" name="teacher_prefix" required>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="ศ.">ศ.</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                            <div class="invalid-feedback">กรุณาเลือกคำนำหน้า</div>
                        </div>
                        <div class="col-md-4">
                            <label for="editTeacherFirstName" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTeacherFirstName" name="teacher_first_name" required>
                            <div class="invalid-feedback">กรุณากรอกชื่อ</div>
                        </div>
                        <div class="col-md-4">
                            <label for="editTeacherLastName" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTeacherLastName" name="teacher_last_name" required>
                            <div class="invalid-feedback">กรุณากรอกนามสกุล</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherNationalId" class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTeacherNationalId" name="teacher_national_id" maxlength="13" required>
                            <div class="invalid-feedback">กรุณากรอกเลขบัตรประชาชน 13 หลัก</div>
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherPosition" class="form-label">ตำแหน่ง</label>
                            <input type="text" class="form-control" id="editTeacherPosition" name="teacher_position">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editTeacherDepartment" class="form-label">แผนก/ฝ่าย <span class="text-danger">*</span></label>
                        <select class="form-select" id="editTeacherDepartment" name="teacher_department" required>
                            <option value="">-- เลือกแผนก/ฝ่าย --</option>
                            <?php foreach ($data['departments'] as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกแผนก/ฝ่าย</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editTeacherPhone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="editTeacherPhone" name="teacher_phone" maxlength="10" pattern="[0-9]{9,10}">
                            <div class="invalid-feedback">กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง</div>
                        </div>
                        <div class="col-md-6">
                            <label for="editTeacherEmail" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="editTeacherEmail" name="teacher_email">
                            <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สถานะ <span class="text-danger">*</span></label>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลนำเข้าข้อมูล -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">นำเข้าข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importTeacherForm" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="import_teachers" value="1">
                    
                    <div class="mb-3">
                        <label for="importFile" class="form-label">ไฟล์ข้อมูล <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="importFile" name="import_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                        <div class="invalid-feedback">กรุณาเลือกไฟล์ข้อมูล</div>
                        <div class="form-text">รองรับไฟล์ .csv, .xlsx, .xls</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="overwriteExisting" name="overwrite_existing">
                        <label class="form-check-label" for="overwriteExisting">อัปเดตข้อมูลที่มีอยู่แล้ว</label>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <span class="material-icons me-2">info</span>
                            <div>
                                <strong>หมายเหตุ:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>ไฟล์ต้องมีคอลัมน์: รหัสบัตรประชาชน, คำนำหน้า, ชื่อ, นามสกุล, แผนก, ตำแหน่ง</li>
                                    <li>ข้อมูลต้องอยู่ในชีท (Sheet) แรกของไฟล์</li>
                                    <li><a href="templates/teacher_import_template.xlsx" target="_blank">ดาวน์โหลดเทมเพลต</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">นำเข้าข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div> 

<!-- โมดัลนำเข้าข้อมูลครู -->
<div class="modal fade" id="importTeacherModal" tabindex="-1" aria-labelledby="importTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importTeacherModalLabel">นำเข้าข้อมูลครูที่ปรึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- ตัวบ่งชี้ขั้นตอน -->
            <div class="step-indicator d-flex justify-content-center my-4">
                <div class="step active text-center mx-4">
                    <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">1</div>
                    <div class="step-text mt-2">เลือกไฟล์</div>
                </div>
                <div class="step text-center mx-4">
                    <div class="step-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">2</div>
                    <div class="step-text mt-2">แม็ปข้อมูล</div>
                </div>
                <div class="step text-center mx-4">
                    <div class="step-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 40px; height: 40px;">3</div>
                    <div class="step-text mt-2">ตรวจสอบและยืนยัน</div>
                </div>
            </div>

            <form id="importTeacherFullForm" method="post" action="api/import_teachers.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="current_step" id="current_step" value="1">

                <div class="modal-body">
                    <!-- ขั้นตอนที่ 1: เลือกไฟล์ -->
                    <div class="import-step" id="step1">
                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">เลือกไฟล์นำเข้า</h6>

                            <div class="file-upload-container">
                                <div class="file-upload-area border border-2 border-dashed rounded p-5 text-center bg-white cursor-pointer">
                                    <input type="file" class="file-input d-none" id="import_file" name="import_file" accept=".xlsx,.xls,.csv">
                                    <div class="file-upload-content">
                                        <span class="material-icons mb-3" style="font-size: 60px; color: #28a745;">cloud_upload</span>
                                        <p class="mb-2 fs-5">ลากไฟล์วางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                                        <p class="file-types text-muted small">รองรับไฟล์ Excel (.xlsx, .xls) หรือ CSV</p>
                                    </div>
                                </div>
                                <div class="file-info mt-3">
                                    <p class="mb-0">ไฟล์ที่เลือก: <span id="fileLabel" class="text-primary">ยังไม่ได้เลือกไฟล์</span></p>
                                </div>
                            </div>

                            <div class="import-options mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" id="skip_header" name="skip_header" class="form-check-input" checked>
                                            <label for="skip_header" class="form-check-label">ข้ามแถวแรก (หัวตาราง)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" id="update_existing" name="update_existing" class="form-check-input" checked>
                                            <label for="update_existing" class="form-check-label">อัพเดตข้อมูลที่มีอยู่แล้ว</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">เลือกแผนกวิชาปลายทาง</h6>
                            <p class="section-desc text-muted small">หากต้องการนำเข้าครูเข้าแผนกเดียวกันทั้งหมด ให้เลือกแผนกที่นี่</p>

                            <div class="form-group">
                                <select class="form-select" name="import_department_id" id="import_department_id">
                                    <option value="">-- ไม่ระบุแผนก (ใช้ข้อมูลจากไฟล์) --</option>
                                    <?php if (isset($data['departments']) && is_array($data['departments'])): ?>
                                        <?php foreach ($data['departments'] as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">คำแนะนำการนำเข้าข้อมูล</h6>
                            <div class="import-instructions bg-white p-3 rounded">
                                <ol class="ps-3 mb-0">
                                    <li class="mb-2">ไฟล์นำเข้าควรมีหัวตารางในแถวแรก (เลือก "ข้ามแถวแรก" ถ้ามี)</li>
                                    <li class="mb-2">ข้อมูลที่จำเป็นต้องมี: เลขบัตรประชาชน, คำนำหน้า, ชื่อ, นามสกุล</li>
                                    <li class="mb-2">คำนำหน้ารองรับ: นาย, นาง, นางสาว, ดร., ผศ., รศ., ศ., อื่นๆ</li>
                                    <li class="mb-2">ระบบจะข้ามรายการที่มีข้อมูลไม่ครบถ้วน</li>
                                    <li>สามารถ <a href="api/download_template.php?type=teachers" target="_blank" class="text-primary">ดาวน์โหลดไฟล์ตัวอย่าง</a> เพื่อดูรูปแบบที่ถูกต้อง</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- ขั้นตอนที่ 2: แม็ปข้อมูล -->
                    <div class="import-step" id="step2" style="display: none;">
                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">ตัวอย่างข้อมูล</h6>
                            <p class="section-desc text-muted small">ตัวอย่าง 5 รายการแรกจากไฟล์ที่อัปโหลด (พบข้อมูลทั้งหมด <span id="totalRecords" class="fw-bold">0</span> รายการ)</p>

                            <div id="dataPreview" class="data-preview table-responsive bg-white rounded border">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>คอลัมน์ 1</th>
                                            <th>คอลัมน์ 2</th>
                                            <th>คอลัมน์ 3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">กรุณาอัปโหลดไฟล์ในขั้นตอนแรก</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-section border rounded p-4 mb-4 bg-light">
                            <h6 class="section-title fw-bold mb-3">แม็ปฟิลด์ข้อมูล</h6>
                            <p class="section-desc text-muted small">โปรดเลือกว่าคอลัมน์ใดในไฟล์ตรงกับข้อมูลชนิดใด</p>

                            <div class="field-mapping-container">
                                <div class="field-mapping-group mb-4 bg-white p-3 rounded border">
                                    <h6 class="mb-3">ข้อมูลสำคัญ <span class="text-danger">*</span></h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                                <select id="map_national_id" name="map_national_id" class="form-select" data-field="national_id" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                                <select id="map_title" name="map_title" class="form-select" data-field="title" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                                <select id="map_firstname" name="map_firstname" class="form-select" data-field="firstname" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                                <select id="map_lastname" name="map_lastname" class="form-select" data-field="lastname" required>
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-mapping-group mb-4 bg-white p-3 rounded border">
                                    <h6 class="mb-3">ข้อมูลตำแหน่งและการติดต่อ</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">แผนก/ฝ่าย</label>
                                                <select id="map_department" name="map_department" class="form-select" data-field="department">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">ตำแหน่ง</label>
                                                <select id="map_position" name="map_position" class="form-select" data-field="position">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">เบอร์โทรศัพท์</label>
                                                <select id="map_phone" name="map_phone" class="form-select" data-field="phone">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="field-mapping">
                                                <label class="form-label">อีเมล</label>
                                                <select id="map_email" name="map_email" class="form-select" data-field="email">
                                                    <option value="-1">-- เลือกคอลัมน์ --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center">
                                <span class="material-icons me-3">info</span>
                                <div>
                                    <strong>หมายเหตุ:</strong> ระบบจะพยายามแม็ปฟิลด์อัตโนมัติตามชื่อหัวตาราง โปรดตรวจสอบความถูกต้อง
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ขั้นตอนที่ 3: ตรวจสอบและยืนยัน -->
                    <div class="import-step" id="step3" style="display: none;">
                        <div class="form-section border rounded p-4 bg-light">
                            <h6 class="section-title fw-bold mb-4">ตรวจสอบข้อมูลก่อนนำเข้า</h6>

                            <div id="importSummary" class="import-summary mb-4">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">จำนวนรายการทั้งหมด</h6>
                                                <h2 id="summary_total" class="mb-0 text-primary">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">คาดว่าจะนำเข้าใหม่</h6>
                                                <h2 id="summary_new" class="mb-0 text-success">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">คาดว่าจะอัพเดต</h6>
                                                <h2 id="summary_update" class="mb-0 text-info">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card h-100 text-center border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2">อาจมีปัญหา</h6>
                                                <h2 id="summary_issues" class="mb-0 text-warning">0</h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="destination-dept mb-4 bg-white p-4 rounded border">
                                <h6 class="mb-3">แผนกวิชาปลายทาง</h6>
                                <p id="selected_dept_text" style="display: none;" class="mb-3 fw-bold fs-5"></p>
                                <div class="d-flex align-items-center">
                                    <span class="material-icons text-info me-2">info</span>
                                    <p id="dept_info_text" class="mb-0">ระบบจะใช้ข้อมูลแผนกจากไฟล์ หรือเว้นว่างถ้าไม่ระบุ</p>
                                </div>
                            </div>

                            <div class="import-options mb-4 bg-white p-4 rounded border">
                                <h6 class="mb-3">ตัวเลือกการนำเข้า</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-icons me-2 text-primary">check_circle</span>
                                            <div>ข้ามแถวแรก (หัวตาราง): <span id="summary_skip_header" class="fw-bold">ใช่</span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="material-icons me-2 text-primary">check_circle</span>
                                            <div>อัพเดตข้อมูลที่มีอยู่แล้ว: <span id="summary_update_existing" class="fw-bold">ใช่</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning d-flex">
                                <span class="material-icons me-3">warning</span>
                                <div>
                                    <strong>คำเตือน:</strong> การนำเข้าข้อมูลจะทำการเพิ่มหรืออัพเดตข้อมูลครูในระบบ ข้อมูลที่ไม่ครบถ้วนจะถูกข้าม
                                    โปรดตรวจสอบความถูกต้องก่อนดำเนินการ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" id="prevStepBtn" class="btn btn-secondary d-flex align-items-center" style="display: none;" onclick="prevStep()">
                        <span class="material-icons me-1" style="font-size: 16px;">arrow_back</span>
                        ย้อนกลับ
                    </button>
                    <button type="button" id="nextStepBtn" class="btn btn-primary d-flex align-items-center" disabled onclick="nextStep()">
                        ถัดไป
                        <span class="material-icons ms-1" style="font-size: 16px;">arrow_forward</span>
                    </button>
                    <button type="submit" id="importSubmitBtn" class="btn btn-success d-flex align-items-center" style="display: none;">
                        <span class="material-icons me-1" style="font-size: 16px;">cloud_upload</span>
                        นำเข้าข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">กำลังประมวลผล...</div>
</div>


<!-- โมดัลยืนยันการลบ -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">ยืนยันการลบข้อมูล</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center text-warning mb-3">
                    <span class="material-icons me-2" style="font-size: 2rem;">warning</span>
                    <h5 class="mb-0">คุณแน่ใจหรือไม่?</h5>
                </div>
                <p>คุณกำลังจะลบข้อมูลครูที่ปรึกษา: <strong id="deleteTeacherName"></strong></p>
                <p class="text-danger">การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>

                <form id="deleteTeacherForm" method="post">
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

<!-- โมดัลยืนยันการเปลี่ยนสถานะ -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">ยืนยันการเปลี่ยนสถานะ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะ <span id="toggleAction" class="fw-bold"></span> ครูที่ปรึกษาคนนี้?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="confirmToggleBtn">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>
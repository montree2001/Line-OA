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
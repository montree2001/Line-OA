<!-- หน้าตั้งค่ารายงานการเข้าแถว -->
<div class="container-fluid">
    <!-- การ์ดโลโก้รายงาน -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <div class="d-flex align-items-center">
                <i class="material-icons me-2">image</i>
                <h5 class="mb-0">โลโก้รายงาน</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center mb-3 mb-md-0">
                        <?php if (isset($logoFile) && !empty($logoFile)): ?>
                            <img src="../uploads/logo/<?php echo $logoFile; ?>" alt="School Logo" class="img-fluid" style="max-height: 150px;">
                        <?php else: ?>
                            <div class="p-5 bg-light rounded-circle d-inline-block">
                                <i class="material-icons" style="font-size: 48px;">image</i>
                                <p>ไม่พบโลโก้</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="logo_file" class="form-label">อัพโหลดโลโก้ใหม่</label>
                            <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*" required>
                            <div class="form-text">ขนาดที่แนะนำ: 300x300 พิกเซล, สูงสุด 5MB (รองรับไฟล์ JPG, PNG, GIF)</div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="upload_logo">
                            <i class="material-icons align-middle me-1">cloud_upload</i> อัพโหลดโลโก้
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- การ์ดวันหยุด -->
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="material-icons me-2">event</i>
                            <h5 class="mb-0">จัดการวันหยุด</h5>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                            <i class="material-icons align-middle me-1">add</i> เพิ่มวันหยุด
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="holidaysTable">
                            <thead>
                                <tr>
                                    <th width="120">วันที่</th>
                                    <th>ชื่อวันหยุด</th>
                                    <th width="120">ประเภท</th>
                                    <th width="100">ประจำปี</th>
                                    <th width="130">ปีการศึกษา</th>
                                    <th width="80">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holidays as $holiday): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($holiday['holiday_date'])); ?></td>
                                    <td><?php echo $holiday['holiday_name']; ?></td>
                                    <td>
                                        <?php 
                                        switch ($holiday['holiday_type']) {
                                            case 'national':
                                                echo '<span class="badge bg-primary">วันหยุดราชการ</span>';
                                                break;
                                            case 'regional':
                                                echo '<span class="badge bg-info">วันหยุดท้องถิ่น</span>';
                                                break;
                                            case 'institutional':
                                                echo '<span class="badge bg-success">วันหยุดสถาบัน</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">อื่นๆ</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($holiday['is_repeating']): ?>
                                            <span class="badge bg-success">ใช่</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ไม่ใช่</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($holiday['year'] && $holiday['semester']): ?>
                                            <?php echo $holiday['semester'] . '/' . $holiday['year']; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline" onsubmit="return confirm('ต้องการลบวันหยุดนี้หรือไม่?');">
                                            <input type="hidden" name="holiday_id" value="<?php echo $holiday['holiday_id']; ?>">
                                            <button type="submit" name="delete_holiday" class="btn btn-danger btn-sm">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- การ์ดผู้ลงนาม -->
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="material-icons me-2">person</i>
                            <h5 class="mb-0">ผู้ลงนามในรายงาน</h5>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSignerModal">
                            <i class="material-icons align-middle me-1">add</i> เพิ่มผู้ลงนาม
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="signersTable">
                            <thead>
                                <tr>
                                    <th>ชื่อ-สกุล</th>
                                    <th>ตำแหน่ง</th>
                                    <th width="80">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($signers as $signer): ?>
                                <tr>
                                    <td><?php echo $signer['title'] . $signer['first_name'] . ' ' . $signer['last_name']; ?></td>
                                    <td><?php echo $signer['position']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm edit-signer" data-bs-toggle="modal" data-bs-target="#editSignerModal" 
                                            data-id="<?php echo $signer['signer_id']; ?>" 
                                            data-position="<?php echo $signer['position']; ?>" 
                                            data-title="<?php echo $signer['title']; ?>" 
                                            data-firstname="<?php echo $signer['first_name']; ?>" 
                                            data-lastname="<?php echo $signer['last_name']; ?>">
                                            <i class="material-icons">edit</i>
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('ต้องการลบผู้ลงนามนี้หรือไม่?');">
                                            <input type="hidden" name="signer_id" value="<?php echo $signer['signer_id']; ?>">
                                            <button type="submit" name="delete_signer" class="btn btn-danger btn-sm">
                                                <i class="material-icons">delete</i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal เพิ่มวันหยุด -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-labelledby="addHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addHolidayModalLabel">เพิ่มวันหยุด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="holiday_date" class="form-label">วันที่</label>
                        <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="holiday_name" class="form-label">ชื่อวันหยุด</label>
                        <input type="text" class="form-control" id="holiday_name" name="holiday_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="holiday_type" class="form-label">ประเภทวันหยุด</label>
                        <select class="form-select" id="holiday_type" name="holiday_type" required>
                            <option value="national">วันหยุดราชการ</option>
                            <option value="regional">วันหยุดท้องถิ่น</option>
                            <option value="institutional">วันหยุดสถาบัน</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_repeating" name="is_repeating">
                        <label class="form-check-label" for="is_repeating">เป็นวันหยุดประจำปี</label>
                    </div>
                    <div class="mb-3">
                        <label for="academic_year_id" class="form-label">ปีการศึกษา (ถ้ามี)</label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($academicYears as $academicYear): ?>
                            <option value="<?php echo $academicYear['academic_year_id']; ?>">
                                ภาคเรียนที่ <?php echo $academicYear['semester']; ?> ปีการศึกษา <?php echo $academicYear['year']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" name="add_holiday">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal เพิ่มผู้ลงนาม -->
<div class="modal fade" id="addSignerModal" tabindex="-1" aria-labelledby="addSignerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSignerModalLabel">เพิ่มผู้ลงนาม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="position" class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">คำนำหน้า</label>
                        <select class="form-select" id="title" name="title" required>
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                            <option value="ดร.">ดร.</option>
                            <option value="ผศ.">ผศ.</option>
                            <option value="รศ.">รศ.</option>
                            <option value="ศ.">ศ.</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">ชื่อจริง</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">นามสกุล</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" name="save_signer">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขผู้ลงนาม -->
<div class="modal fade" id="editSignerModal" tabindex="-1" aria-labelledby="editSignerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSignerModalLabel">แก้ไขผู้ลงนาม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" id="edit_signer_id" name="signer_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_position" class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" id="edit_position" name="position" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">คำนำหน้า</label>
                        <select class="form-select" id="edit_title" name="title" required>
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                            <option value="ดร.">ดร.</option>
                            <option value="ผศ.">ผศ.</option>
                            <option value="รศ.">รศ.</option>
                            <option value="ศ.">ศ.</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_first_name" class="form-label">ชื่อจริง</label>
                        <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_last_name" class="form-label">นามสกุล</label>
                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" name="save_signer">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // กำหนดค่าสำหรับฟอร์มแก้ไขผู้ลงนาม
        const editSignerButtons = document.querySelectorAll('.edit-signer');
        editSignerButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const position = this.getAttribute('data-position');
                const title = this.getAttribute('data-title');
                const firstname = this.getAttribute('data-firstname');
                const lastname = this.getAttribute('data-lastname');
                
                document.getElementById('edit_signer_id').value = id;
                document.getElementById('edit_position').value = position;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_first_name').value = firstname;
                document.getElementById('edit_last_name').value = lastname;
            });
        });
        
        // DataTables
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#holidaysTable').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
                }
            });
            
            $('#signersTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
                }
            });
        }
    });
</script>
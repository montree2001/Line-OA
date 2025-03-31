<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">รายการประกาศทั้งหมด</h5>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- เพิ่มปุ่มโดยตรงที่ด้านบนของเนื้อหา -->
                <div class="row mb-3">
                    <div class="col-12 text-right">
                        <button id="direct-create-btn" class="btn btn-primary">
                            <i class="fas fa-plus"></i> เพิ่มประกาศใหม่
                        </button>
                    </div>
                </div>

                <!-- ส่วนของตัวกรอง -->
                <div class="filter-section mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ประเภทประกาศ</label>
                                <select class="form-control" id="filter-type">
                                    <option value="">ทั้งหมด</option>
                                    <option value="general">ทั่วไป</option>
                                    <option value="urgent">สำคัญ</option>
                                    <option value="event">กิจกรรม</option>
                                    <option value="info">ข้อมูล</option>
                                    <option value="success">ความสำเร็จ</option>
                                    <option value="warning">คำเตือน</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>แผนกวิชา</label>
                                <select class="form-control" id="filter-department">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ระดับชั้น</label>
                                <select class="form-control" id="filter-level">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($levels as $level): ?>
                                    <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ค้นหา</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search-input" placeholder="ค้นหาประกาศ...">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" id="search-btn">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางประกาศ -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover announcement-table">
                        <thead>
                            <tr>
                                <th style="width: 5%">ลำดับ</th>
                                <th style="width: 15%">ประเภท</th>
                                <th style="width: 30%">หัวข้อ</th>
                                <th style="width: 15%">กลุ่มเป้าหมาย</th>
                                <th style="width: 15%">วันที่ประกาศ</th>
                                <th style="width: 10%">ผู้ประกาศ</th>
                                <th style="width: 10%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="7" class="text-center">ไม่พบข้อมูลประกาศ</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($announcements as $index => $announcement): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $announcement['type']; ?>">
                                        <?php echo getAnnouncementTypeName($announcement['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td>
                                    <?php 
                                    $target = 'ทั้งหมด';
                                    if (!empty($announcement['target_department']) || !empty($announcement['target_level'])) {
                                        $targetParts = [];
                                        if (!empty($announcement['target_department'])) {
                                            $targetParts[] = 'แผนก: ' . $announcement['target_department'];
                                        }
                                        if (!empty($announcement['target_level'])) {
                                            $targetParts[] = 'ระดับ: ' . $announcement['target_level'];
                                        }
                                        $target = implode(', ', $targetParts);
                                    }
                                    echo $target;
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?></td>
                                <td><?php echo $announcement['first_name'] . ' ' . $announcement['last_name']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info view-btn" data-id="<?php echo $announcement['announcement_id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary edit-btn" data-id="<?php echo $announcement['announcement_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $announcement['announcement_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- ส่วนแสดงหน้า (pagination) -->
                <div class="mt-4 d-flex justify-content-center">
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#">&laquo;</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">&raquo;</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สร้าง/แก้ไขประกาศ -->
<div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalLabel">สร้างประกาศใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="announcementForm">
                    <input type="hidden" id="announcement-id" name="announcement_id" value="">
                    
                    <div class="form-group">
                        <label for="announcement-title">หัวข้อประกาศ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="announcement-title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="announcement-type">ประเภทประกาศ <span class="text-danger">*</span></label>
                        <select class="form-control" id="announcement-type" name="type" required>
                            <option value="general">ทั่วไป</option>
                            <option value="urgent">สำคัญ</option>
                            <option value="event">กิจกรรม</option>
                            <option value="info">ข้อมูล</option>
                            <option value="success">ความสำเร็จ</option>
                            <option value="warning">คำเตือน</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="announcement-content">เนื้อหาประกาศ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="announcement-content" name="content" rows="6" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>กลุ่มเป้าหมาย</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="target-all" name="target_all" checked>
                                    <label class="form-check-label" for="target-all">ทั้งหมด</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="target-options" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target-department">แผนกวิชา</label>
                                    <select class="form-control" id="target-department" name="target_department">
                                        <option value="">ทั้งหมด</option>
                                        <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target-level">ระดับชั้น</label>
                                    <select class="form-control" id="target-level" name="target_level">
                                        <option value="">ทั้งหมด</option>
                                        <?php foreach ($levels as $level): ?>
                                        <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="announcement-status">สถานะ</label>
                        <select class="form-control" id="announcement-status" name="status">
                            <option value="active">เผยแพร่</option>
                            <option value="draft">ฉบับร่าง</option>
                            <option value="scheduled">กำหนดเวลา</option>
                        </select>
                    </div>
                    
                    <div class="form-group scheduled-options d-none">
                        <label for="scheduled-date">วันเวลาที่เผยแพร่</label>
                        <input type="datetime-local" class="form-control" id="scheduled-date" name="scheduled_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiration-date">วันหมดอายุ (ถ้ามี)</label>
                        <input type="date" class="form-control" id="expiration-date" name="expiration_date">
                        <small class="form-text text-muted">หากไม่ระบุ ประกาศจะแสดงตลอดไป</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send-notification" name="send_notification" checked>
                            <label class="form-check-label" for="send-notification">ส่งการแจ้งเตือนไปยังนักเรียนที่เกี่ยวข้อง</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="save-announcement">บันทึกประกาศ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal แสดงรายละเอียดประกาศ -->
<div class="modal fade" id="viewAnnouncementModal" tabindex="-1" role="dialog" aria-labelledby="viewAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAnnouncementModalLabel">รายละเอียดประกาศ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- รายละเอียดประกาศจะถูกแสดงที่นี่ -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่มโค้ดนี้ที่ด้านบนสุดของไฟล์เพื่อตรวจสอบการโหลดของ jQuery และ Bootstrap -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่า jQuery ถูกโหลดหรือไม่
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        alert('ไม่พบ jQuery กรุณาตรวจสอบการโหลดไฟล์ JavaScript');
    } else {
        console.log('jQuery version:', jQuery.fn.jquery);
        
        // ตรวจสอบว่า Bootstrap modal ถูกโหลดหรือไม่
        if (typeof jQuery.fn.modal === 'undefined') {
            console.error('Bootstrap modal is not loaded!');
            alert('ไม่พบ Bootstrap Modal กรุณาตรวจสอบการโหลด Bootstrap JS');
        } else {
            console.log('Bootstrap modal is loaded');
        }
    }
});
</script> 
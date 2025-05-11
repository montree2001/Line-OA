<?php
// ตรวจสอบการเรียกหน้านี้โดยตรง
if (!defined('BASE_PATH') && !isset($current_page)) {
    exit('Direct script access is not allowed!');
}

// เรียกใช้ฐานข้อมูล
require_once __DIR__ . '/../../db_connect.php';
$db = getDB();

// ฟังก์ชั่นสำหรับ escape HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ดึงข้อมูลประกาศทั้งหมด
$stmt = $db->prepare("
    SELECT a.*, d.department_name, CONCAT(u.first_name, ' ', u.last_name) as created_by_name 
    FROM announcements a 
    LEFT JOIN departments d ON a.target_department = d.department_id 
    LEFT JOIN users u ON a.created_by = u.user_id 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลแผนกวิชาทั้งหมด
$stmt = $db->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ส่วนของการแสดงรายการประกาศ -->
<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="announcementsTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="25%">หัวข้อ</th>
                        <th width="10%">ประเภท</th>
                        <th width="10%">กลุ่มเป้าหมาย</th>
                        <th width="10%">สถานะ</th>
                        <th width="15%">วันที่สร้าง</th>
                        <th width="15%">ผู้สร้าง</th>
                        <th width="10%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= h($announcement['title']) ?></td>
                        <td>
                            <?php
                            switch ($announcement['type']) {
                                case 'general':
                                    echo '<span class="badge badge-info">ทั่วไป</span>';
                                    break;
                                case 'important':
                                    echo '<span class="badge badge-danger">สำคัญ</span>';
                                    break;
                                case 'event':
                                    echo '<span class="badge badge-success">กิจกรรม</span>';
                                    break;
                                default:
                                    echo '<span class="badge badge-secondary">' . h($announcement['type']) . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($announcement['is_all_targets']): ?>
                                <span class="badge badge-primary">ทั้งหมด</span>
                            <?php else: ?>
                                <?php if (!empty($announcement['target_department'])): ?>
                                    <span class="badge badge-info"><?= h($announcement['department_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($announcement['target_level'])): ?>
                                    <span class="badge badge-warning"><?= h($announcement['target_level']) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            switch ($announcement['status']) {
                                case 'active':
                                    echo '<span class="badge badge-success">เผยแพร่</span>';
                                    break;
                                case 'draft':
                                    echo '<span class="badge badge-warning">ฉบับร่าง</span>';
                                    break;
                                case 'inactive':
                                    echo '<span class="badge badge-secondary">ไม่เผยแพร่</span>';
                                    break;
                                case 'scheduled':
                                    echo '<span class="badge badge-info">กำหนดเวลา</span>';
                                    break;
                                default:
                                    echo '<span class="badge badge-secondary">' . h($announcement['status']) . '</span>';
                            }
                            ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($announcement['created_at'])) ?></td>
                        <td><?= h($announcement['created_by_name']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info view-announcement" 
                                data-id="<?= $announcement['announcement_id'] ?>" 
                                data-title="<?= h($announcement['title']) ?>" 
                                data-content="<?= h($announcement['content']) ?>">
                                <i class="material-icons">visibility</i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-announcement" 
                                data-id="<?= $announcement['announcement_id'] ?>"
                                data-title="<?= h($announcement['title']) ?>"
                                data-content="<?= h($announcement['content']) ?>"
                                data-type="<?= h($announcement['type']) ?>"
                                data-status="<?= h($announcement['status']) ?>"
                                data-is-all-targets="<?= $announcement['is_all_targets'] ?>"
                                data-target-department="<?= $announcement['target_department'] ?>"
                                data-target-level="<?= $announcement['target_level'] ?>"
                                data-expiration-date="<?= $announcement['expiration_date'] ? date('Y-m-d\TH:i', strtotime($announcement['expiration_date'])) : '' ?>"
                                data-scheduled-date="<?= $announcement['scheduled_date'] ? date('Y-m-d\TH:i', strtotime($announcement['scheduled_date'])) : '' ?>">
                                <i class="material-icons">edit</i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-announcement" 
                                data-id="<?= $announcement['announcement_id'] ?>"
                                data-title="<?= h($announcement['title']) ?>">
                                <i class="material-icons">delete</i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($announcements)): ?>
                    <tr>
                        <td colspan="8" class="text-center">ไม่พบข้อมูลประกาศ</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal สำหรับดูประกาศ -->
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
                <h4 id="view-title"></h4>
                <div id="view-content" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับเพิ่ม/แก้ไขประกาศ -->
<div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalLabel">เพิ่มประกาศใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="announcementForm" method="post" action="" onsubmit="return saveAnnouncement(event);">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_announcement">
                    <input type="hidden" id="announcement_id" name="announcement_id" value="">
                    
                    <div class="form-group">
                        <label for="title">หัวข้อประกาศ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">เนื้อหาประกาศ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        <small class="form-text text-muted">หากตัวแก้ไขเนื้อหาไม่แสดง ให้กรอกเนื้อหาในช่องข้อความนี้โดยตรง</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">ประเภทประกาศ</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="general">ทั่วไป</option>
                                    <option value="important">สำคัญ</option>
                                    <option value="event">กิจกรรม</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">สถานะประกาศ</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active">เผยแพร่</option>
                                    <option value="draft">ฉบับร่าง</option>
                                    <option value="inactive">ไม่เผยแพร่</option>
                                    <option value="scheduled">กำหนดเวลา</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_all_targets" name="is_all_targets" value="1" checked>
                            <label class="custom-control-label" for="is_all_targets">ประกาศถึงทุกคน</label>
                        </div>
                    </div>
                    
                    <div id="targetOptions" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_department">แผนกวิชาเป้าหมาย</label>
                                    <select class="form-control" id="target_department" name="target_department">
                                        <option value="">-- เลือกแผนกวิชา --</option>
                                        <?php foreach ($departments as $department): ?>
                                        <option value="<?= $department['department_id'] ?>"><?= h($department['department_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_level">ระดับชั้นเป้าหมาย</label>
                                    <select class="form-control" id="target_level" name="target_level">
                                        <option value="">-- เลือกระดับชั้น --</option>
                                        <option value="ปวช.1">ปวช.1</option>
                                        <option value="ปวช.2">ปวช.2</option>
                                        <option value="ปวช.3">ปวช.3</option>
                                        <option value="ปวส.1">ปวส.1</option>
                                        <option value="ปวส.2">ปวส.2</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scheduled_date">กำหนดเวลาเผยแพร่</label>
                                <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date">
                                <small class="form-text text-muted">หากกำหนด ประกาศจะถูกเผยแพร่หลังจากเวลานี้</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expiration_date">วันหมดอายุประกาศ</label>
                                <input type="datetime-local" class="form-control" id="expiration_date" name="expiration_date">
                                <small class="form-text text-muted">หากกำหนด ประกาศจะหยุดเผยแพร่หลังจากเวลานี้</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal สำหรับลบประกาศ -->
<div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" role="dialog" aria-labelledby="deleteAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAnnouncementModalLabel">ยืนยันการลบประกาศ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบประกาศ "<span id="delete-title"></span>" ใช่หรือไม่?</p>
                <p class="text-danger">การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            </div>
            <div class="modal-footer">
                <form id="deleteAnnouncementForm" method="post" action="" onsubmit="return deleteAnnouncement(event);">
                    <input type="hidden" name="action" value="delete_announcement">
                    <input type="hidden" id="delete_announcement_id" name="announcement_id" value="">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ลบประกาศ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันเปิด Modal เพิ่มประกาศใหม่
function openAnnouncementModal() {
    // รีเซ็ตฟอร์ม
    $('#announcementForm')[0].reset();
    $('#announcement_id').val('');
    $('#announcementModalLabel').text('เพิ่มประกาศใหม่');
    $('#content').summernote('code', '');
    
    // รีเซ็ตตัวเลือกเป้าหมาย
    $('#is_all_targets').prop('checked', true);
    $('#targetOptions').addClass('d-none');
    
    // แสดง Modal
    $('#announcementModal').modal('show');
}

// กำหนดให้เป็นฟังก์ชัน global
window.openAnnouncementModal = openAnnouncementModal;

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadAnnouncementReport() {
    window.location.href = 'api/announcements_report.php';
}

// กำหนดให้เป็นฟังก์ชัน global
window.downloadAnnouncementReport = downloadAnnouncementReport;

// ฟังก์ชั่นสำหรับบันทึกประกาศด้วย AJAX
function saveAnnouncement(event) {
    event.preventDefault();
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if ($('#title').val().trim() === '') {
        alert('กรุณากรอกหัวข้อประกาศ');
        $('#title').focus();
        return false;
    }
    
    // ตรวจสอบเนื้อหา (รองรับทั้งกรณีที่ Summernote ทำงานและไม่ทำงาน)
    let content = '';
    try {
        // ตรวจสอบว่า Summernote พร้อมใช้งานหรือไม่
        if ($.fn.summernote && $('#content').summernote) {
            content = $('#content').summernote('code');
        } else {
            // ถ้า Summernote ไม่พร้อมใช้งาน ให้ใช้ค่าจาก textarea โดยตรง
            content = $('#content').val();
        }
    } catch (e) {
        // ถ้าเกิดข้อผิดพลาด ให้ใช้ค่าจาก textarea โดยตรง
        content = $('#content').val();
    }
    
    if (content.trim() === '' || content === '<p><br></p>') {
        alert('กรุณากรอกเนื้อหาประกาศ');
        $('#content').focus();
        return false;
    }
    
    // ถ้าเลือกสถานะเป็น "กำหนดเวลา" แต่ไม่ได้ระบุเวลา
    if ($('#status').val() === 'scheduled' && $('#scheduled_date').val() === '') {
        alert('กรุณากำหนดเวลาเผยแพร่ เมื่อเลือกสถานะเป็น "กำหนดเวลา"');
        $('#scheduled_date').focus();
        return false;
    }
    
    // แสดง loading indicator
    const saveBtn = $('#announcementForm button[type="submit"]');
    const originalText = saveBtn.text();
    saveBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...');
    saveBtn.prop('disabled', true);
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData($('#announcementForm')[0]);
    
    // ส่งข้อมูลด้วย AJAX
    $.ajax({
        url: 'api/process_announcement.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // แสดงข้อความสำเร็จ
                alert(response.message);
                
                // ปิด Modal
                $('#announcementModal').modal('hide');
                
                // รีโหลดหน้าเพื่อแสดงข้อมูลล่าสุด
                location.reload();
            } else {
                // แสดงข้อความผิดพลาด
                alert('เกิดข้อผิดพลาด: ' + response.message);
                
                // คืนค่าปุ่มกลับเป็นปกติ
                saveBtn.html(originalText);
                saveBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            // แสดงข้อความผิดพลาด
            alert('เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error);
            
            // คืนค่าปุ่มกลับเป็นปกติ
            saveBtn.html(originalText);
            saveBtn.prop('disabled', false);
        }
    });
    
    return false; // ป้องกันการส่งฟอร์มแบบปกติ
}

// ฟังก์ชั่นสำหรับลบประกาศด้วย AJAX
function deleteAnnouncement(event) {
    event.preventDefault();
    
    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบประกาศนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        return false;
    }
    
    // แสดง loading indicator
    const deleteBtn = $('#deleteAnnouncementForm button[type="submit"]');
    const originalText = deleteBtn.text();
    deleteBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังลบ...');
    deleteBtn.prop('disabled', true);
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData($('#deleteAnnouncementForm')[0]);
    
    // ส่งข้อมูลด้วย AJAX
    $.ajax({
        url: 'api/process_announcement.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // แสดงข้อความสำเร็จ
                alert(response.message);
                
                // ปิด Modal
                $('#deleteAnnouncementModal').modal('hide');
                
                // รีโหลดหน้าเพื่อแสดงข้อมูลล่าสุด
                location.reload();
            } else {
                // แสดงข้อความผิดพลาด
                alert('เกิดข้อผิดพลาด: ' + response.message);
                
                // คืนค่าปุ่มกลับเป็นปกติ
                deleteBtn.html(originalText);
                deleteBtn.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            // แสดงข้อความผิดพลาด
            alert('เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error);
            
            // คืนค่าปุ่มกลับเป็นปกติ
            deleteBtn.html(originalText);
            deleteBtn.prop('disabled', false);
        }
    });
    
    return false; // ป้องกันการส่งฟอร์มแบบปกติ
}
    // เริ่มต้น Summernote editor
    $('#content').summernote({
        placeholder: 'เนื้อหาประกาศ...',
        height: 250,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        lang: 'th-TH'
    });
    
    // จัดการการแสดง/ซ่อนตัวเลือกเป้าหมาย
    $('#is_all_targets').change(function() {
        if ($(this).is(':checked')) {
            $('#targetOptions').addClass('d-none');
        } else {
            $('#targetOptions').removeClass('d-none');
        }
    });
    
    // การแสดงรายละเอียดประกาศ
    $(document).on('click', '.view-announcement', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const content = $(this).data('content');
        
        $('#view-title').text(title);
        $('#view-content').html(content);
        $('#viewAnnouncementModal').modal('show');
    });
    
    // การแก้ไขประกาศ
    $(document).on('click', '.edit-announcement', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const content = $(this).data('content');
        const type = $(this).data('type');
        const status = $(this).data('status');
        const isAllTargets = $(this).data('is-all-targets');
        const targetDepartment = $(this).data('target-department');
        const targetLevel = $(this).data('target-level');
        const expirationDate = $(this).data('expiration-date');
        const scheduledDate = $(this).data('scheduled-date');
        
        // กำหนดค่าให้กับฟอร์ม
        $('#announcement_id').val(id);
        $('#title').val(title);
        $('#content').summernote('code', content);
        $('#type').val(type);
        $('#status').val(status);
        
        if (isAllTargets == 1) {
            $('#is_all_targets').prop('checked', true);
            $('#targetOptions').addClass('d-none');
        } else {
            $('#is_all_targets').prop('checked', false);
            $('#targetOptions').removeClass('d-none');
            $('#target_department').val(targetDepartment);
            $('#target_level').val(targetLevel);
        }
        
        $('#expiration_date').val(expirationDate);
        $('#scheduled_date').val(scheduledDate);
        
        // เปลี่ยนชื่อหัวข้อ Modal
        $('#announcementModalLabel').text('แก้ไขประกาศ');
        
        // แสดง Modal
        $('#announcementModal').modal('show');
    });
    
    // การลบประกาศ
    $(document).on('click', '.delete-announcement', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        
        $('#delete_announcement_id').val(id);
        $('#delete-title').text(title);
        $('#deleteAnnouncementModal').modal('show');
    });
    
    // DataTable initialization
    if ($.fn.DataTable) {
        $('#announcementsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            },
            "order": [[5, "desc"]], // เรียงตามวันที่สร้าง (ล่าสุดขึ้นก่อน)
            "pageLength": 10,
            "responsive": true
        });
    } else {
        console.error('DataTable is not loaded');
    }

</script>
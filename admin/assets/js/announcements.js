/**
 * JavaScript สำหรับการจัดการประกาศในระบบน้องชูใจ AI
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีการโหลด Summernote หรือไม่
    if (typeof $.fn.summernote === 'function') {
        initSummernote();
    } else {
        console.error('Summernote is not loaded');
    }

    // ผูกเหตุการณ์กับปุ่มต่างๆ
    bindEventHandlers();
    
    // ตั้งค่า DataTable
    initDataTable();
});

/**
 * ฟังก์ชันเริ่มต้น Summernote editor
 */
function initSummernote() {
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
        lang: 'th-TH', // ภาษาไทย
        callbacks: {
            onImageUpload: function(files) {
                // อัปโหลดรูปภาพ (ถ้าต้องการเพิ่มฟังก์ชันนี้ในอนาคต)
                uploadImage(files[0], this);
            }
        }
    });
}

/**
 * ฟังก์ชันผูกเหตุการณ์กับองค์ประกอบต่างๆ
 */
function bindEventHandlers() {
    // เปิด Modal เพิ่มประกาศใหม่
    window.openAnnouncementModal = function() {
        console.log('Opening announcement modal');
        // รีเซ็ตฟอร์ม
        if ($('#announcementForm').length) {
            $('#announcementForm')[0].reset();
            $('#announcement_id').val('');
            $('#announcementModalLabel').text('เพิ่มประกาศใหม่');
            
            // ล้างค่า Summernote
            if (typeof $.fn.summernote === 'function') {
                $('#content').summernote('code', '');
            }
            
            // รีเซ็ตตัวเลือกเป้าหมาย
            $('#is_all_targets').prop('checked', true);
            $('#targetOptions').addClass('d-none');
            
            // แสดง Modal
            $('#announcementModal').modal('show');
        } else {
            console.error('Announcement form not found');
        }
    };
    
    // สลับการแสดงผลตัวเลือกเป้าหมาย
    $('#is_all_targets').change(function() {
        if ($(this).is(':checked')) {
            $('#targetOptions').addClass('d-none');
        } else {
            $('#targetOptions').removeClass('d-none');
        }
    });
    
    // การตรวจสอบความถูกต้องของฟอร์มก่อนส่ง
    $('#announcementForm').submit(function(e) {
        // ตรวจสอบหัวข้อ
        if ($('#title').val().trim() === '') {
            e.preventDefault();
            alert('กรุณากรอกหัวข้อประกาศ');
            $('#title').focus();
            return false;
        }
        
        // ตรวจสอบเนื้อหา
        let content = '';
        if (typeof $.fn.summernote === 'function') {
            content = $('#content').summernote('code');
        } else {
            content = $('#content').val();
        }
        
        if (content.trim() === '' || content === '<p><br></p>') {
            e.preventDefault();
            alert('กรุณากรอกเนื้อหาประกาศ');
            $('#content').focus();
            return false;
        }
        
        // ตรวจสอบวันที่
        if ($('#status').val() === 'scheduled' && $('#scheduled_date').val() === '') {
            e.preventDefault();
            alert('กรุณากำหนดเวลาเผยแพร่ เมื่อเลือกสถานะเป็น "กำหนดเวลา"');
            $('#scheduled_date').focus();
            return false;
        }
        
        return true;
    });
    
    // ตรวจสอบสถานะ และบังคับให้กรอกวันที่กำหนดเวลาถ้าเลือก "กำหนดเวลา"
    $('#status').change(function() {
        if ($(this).val() === 'scheduled') {
            $('#scheduled_date').attr('required', true);
            // ถ้ายังไม่ได้กำหนดวันที่ ให้กำหนดเป็นวันปัจจุบัน + 1 ชั่วโมง
            if ($('#scheduled_date').val() === '') {
                const now = new Date();
                now.setHours(now.getHours() + 1);
                const dateStr = now.toISOString().slice(0, 16);
                $('#scheduled_date').val(dateStr);
            }
        } else {
            $('#scheduled_date').attr('required', false);
        }
    });
    
    // การดูประกาศ (ทำงานแยกจาก DOMContentLoaded เพราะอาจมีการเพิ่มปุ่มภายหลัง)
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
        
        if (typeof $.fn.summernote === 'function') {
            $('#content').summernote('code', content);
        } else {
            $('#content').val(content);
        }
        
        $('#type').val(type);
        $('#status').val(status);
        
        if (isAllTargets) {
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
    
    // การยืนยันการลบ
    $('#deleteAnnouncementForm').submit(function() {
        return confirm('คุณแน่ใจหรือไม่ที่จะลบประกาศนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้');
    });
    
    // ฟังก์ชันดาวน์โหลดรายงาน
    window.downloadAnnouncementReport = function() {
        window.location.href = 'api/announcements_report.php';
    };
}

/**
 * ฟังก์ชันตั้งค่า DataTable
 */
function initDataTable() {
    if ($.fn.DataTable) {
        $('#announcementsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            },
            "order": [[5, "desc"]], // เรียงตามวันที่สร้าง (ล่าสุดขึ้นก่อน)
            "pageLength": 10,
            "responsive": true
        });
    }
}

/**
 * ฟังก์ชันอัปโหลดรูปภาพสำหรับ Summernote
 * (ใช้สำหรับการพัฒนาในอนาคต)
 */
function uploadImage(file, editor) {
    const formData = new FormData();
    formData.append('file', file);
    
    $.ajax({
        url: 'api/upload_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                $(editor).summernote('insertImage', data.url);
            } else {
                alert('อัปโหลดรูปภาพล้มเหลว: ' + data.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
        }
    });
}
/**
 * ตรวจสอบความถูกต้องของฟอร์มประกาศ (ต่อ)
 */
function validateAnnouncementForm() {
    let isValid = true;
    const title = $('#announcement-title').val().trim();
    const content = $('#announcement-content').summernote('code').trim();
    
    // ตรวจสอบหัวข้อ
    if (!title) {
        showError('กรุณาระบุหัวข้อประกาศ');
        isValid = false;
    }
    
    // ตรวจสอบเนื้อหา
    if (!content || content === '<p><br></p>') {
        showError('กรุณาระบุเนื้อหาประกาศ');
        isValid = false;
    }
    
    // ตรวจสอบวันที่ตั้งเวลา (หากเลือกตั้งเวลา)
    if ($('#announcement-status').val() === 'scheduled') {
        const scheduledDate = $('#scheduled-date').val();
        if (!scheduledDate) {
            showError('กรุณาระบุวันเวลาที่ต้องการเผยแพร่');
            isValid = false;
        }
    }
    
    return isValid;
}

/**
 * บันทึกประกาศ
 */
function saveAnnouncement() {
    // แสดง loading
    showLoading();
    
    // รวบรวมข้อมูล
    const formData = new FormData($('#announcementForm')[0]);
    formData.append('content_html', $('#announcement-content').summernote('code'));
    
    // เพิ่มข้อมูลเป้าหมาย
    if ($('#target-all').is(':checked')) {
        formData.append('is_all_targets', '1');
    } else {
        formData.append('is_all_targets', '0');
    }
    
    // เตรียม URL และ method
    let url = 'api/save_announcement.php';
    let method = 'POST';
    
    // ส่งข้อมูล
    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoading();
            
            if (response.status === 'success') {
                // ปิด modal
                $('#announcementModal').modal('hide');
                
                // แสดงข้อความสำเร็จ
                showSuccess(response.message || 'บันทึกประกาศเรียบร้อยแล้ว');
                
                // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                // แสดงข้อความผิดพลาด
                showError(response.message || 'เกิดข้อผิดพลาดในการบันทึกประกาศ');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            console.error('Error:', error);
        }
    });
}

/**
 * โหลดข้อมูลประกาศสำหรับแก้ไข
 */
function loadAnnouncementForEdit(id) {
    showLoading();
    
    $.ajax({
        url: 'api/get_announcement.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.status === 'success' && response.data) {
                const announcement = response.data;
                
                // เติมข้อมูลในฟอร์ม
                $('#announcement-id').val(announcement.announcement_id);
                $('#announcement-title').val(announcement.title);
                $('#announcement-type').val(announcement.type);
                $('#announcement-content').summernote('code', announcement.content);
                $('#announcement-status').val(announcement.status);
                
                // ตั้งค่ากลุ่มเป้าหมาย
                if (announcement.is_all_targets === '1') {
                    $('#target-all').prop('checked', true);
                    $('#target-options').addClass('d-none');
                } else {
                    $('#target-all').prop('checked', false);
                    $('#target-options').removeClass('d-none');
                    $('#target-department').val(announcement.target_department || '');
                    $('#target-level').val(announcement.target_level || '');
                }
                
                // ตั้งค่าวันหมดอายุ
                if (announcement.expiration_date) {
                    $('#expiration-date').val(announcement.expiration_date.split(' ')[0]);
                }
                
                // ตั้งค่าวันเวลาที่เผยแพร่ (หากเป็นกำหนดเวลา)
                if (announcement.status === 'scheduled' && announcement.scheduled_date) {
                    $('.scheduled-options').removeClass('d-none');
                    // แปลงรูปแบบวันที่เวลาให้เข้ากับ input type datetime-local
                    const scheduledDate = announcement.scheduled_date.replace(' ', 'T');
                    $('#scheduled-date').val(scheduledDate);
                } else {
                    $('.scheduled-options').addClass('d-none');
                }
                
                // แสดง modal
                $('#announcementModalLabel').text('แก้ไขประกาศ');
                $('#announcementModal').modal('show');
            } else {
                showError(response.message || 'ไม่พบข้อมูลประกาศ');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            console.error('Error:', error);
        }
    });
}

/**
 * โหลดรายละเอียดประกาศ
 */
function loadAnnouncementDetails(id) {
    showLoading();
    
    $.ajax({
        url: 'api/get_announcement.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.status === 'success' && response.data) {
                const announcement = response.data;
                
                // เติมข้อมูลใน modal
                $('#view-title').text(announcement.title);
                
                // ตั้งค่า badge ประเภท
                const badgeClass = getBadgeClass(announcement.type);
                const typeName = getTypeName(announcement.type);
                $('#view-type-badge').attr('class', 'badge ' + badgeClass).text(typeName);
                
                // วันที่ประกาศ
                $('#view-date').text('ประกาศเมื่อ: ' + formatDate(announcement.created_at));
                
                // กลุ่มเป้าหมาย
                let targetText = 'ทั้งหมด';
                if (announcement.is_all_targets !== '1') {
                    const targetParts = [];
                    if (announcement.target_department) {
                        targetParts.push('แผนก: ' + announcement.target_department_name);
                    }
                    if (announcement.target_level) {
                        targetParts.push('ระดับ: ' + announcement.target_level);
                    }
                    if (targetParts.length > 0) {
                        targetText = targetParts.join(', ');
                    }
                }
                $('#view-target').text(targetText);
                
                // ผู้ประกาศ
                $('#view-author').text(announcement.author_name);
                
                // เนื้อหา
                $('#view-content').html(announcement.content);
                
                // เก็บ ID สำหรับการแก้ไข
                $('#edit-from-view').data('id', announcement.announcement_id);
                
                // แสดง modal
                $('#viewAnnouncementModal').modal('show');
            } else {
                showError(response.message || 'ไม่พบข้อมูลประกาศ');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            console.error('Error:', error);
        }
    });
}

/**
 * ลบประกาศ
 */
function deleteAnnouncement(id) {
    showLoading();
    
    $.ajax({
        url: 'api/delete_announcement.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            $('#deleteConfirmModal').modal('hide');
            
            if (response.status === 'success') {
                showSuccess(response.message || 'ลบประกาศเรียบร้อยแล้ว');
                
                // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showError(response.message || 'เกิดข้อผิดพลาดในการลบประกาศ');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            $('#deleteConfirmModal').modal('hide');
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            console.error('Error:', error);
        }
    });
}

/**
 * ค้นหาประกาศตามเงื่อนไข
 */
function searchAnnouncements() {
    const keyword = $('#search-input').val().trim();
    const type = $('#filter-type').val();
    const department = $('#filter-department').val();
    const level = $('#filter-level').val();
    
    showLoading();
    
    $.ajax({
        url: 'api/search_announcements.php',
        type: 'GET',
        data: {
            keyword: keyword,
            type: type,
            department: department,
            level: level
        },
        dataType: 'json',
        success: function(response) {
            hideLoading();
            
            if (response.status === 'success') {
                updateAnnouncementTable(response.data);
            } else {
                showError(response.message || 'เกิดข้อผิดพลาดในการค้นหาประกาศ');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            console.error('Error:', error);
        }
    });
}

/**
 * อัปเดตตารางประกาศ
 */
function updateAnnouncementTable(announcements) {
    const tbody = $('.announcement-table tbody');
    tbody.empty();
    
    if (announcements.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">ไม่พบข้อมูลประกาศ</td></tr>');
        return;
    }
    
    announcements.forEach(function(announcement, index) {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <span class="badge ${getBadgeClass(announcement.type)}">
                        ${getTypeName(announcement.type)}
                    </span>
                </td>
                <td>${escapeHtml(announcement.title)}</td>
                <td>${getTargetText(announcement)}</td>
                <td>${formatDate(announcement.created_at)}</td>
                <td>${announcement.author_name}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info view-btn" data-id="${announcement.announcement_id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary edit-btn" data-id="${announcement.announcement_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${announcement.announcement_id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

/**
 * แสดงข้อความแจ้งเตือนแบบ success
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * แสดงข้อความแจ้งเตือนแบบ error
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'ข้อผิดพลาด',
        text: message
    });
}

/**
 * แสดง loading
 */
function showLoading() {
    Swal.fire({
        title: 'กำลังดำเนินการ...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * ซ่อน loading
 */
function hideLoading() {
    Swal.close();
}

/**
 * เพิ่มความปลอดภัยด้วยการ escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * จัดรูปแบบวันที่
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * รับ class ของ badge ตามประเภท
 */
function getBadgeClass(type) {
    const classes = {
        general: 'badge-primary',
        urgent: 'badge-danger',
        event: 'badge-success',
        info: 'badge-info',
        success: 'badge-success',
        warning: 'badge-warning'
    };
    
    return classes[type] || 'badge-secondary';
}

/**
 * รับชื่อประเภทเป็นภาษาไทย
 */
function getTypeName(type) {
    const names = {
        general: 'ทั่วไป',
        urgent: 'สำคัญ',
        event: 'กิจกรรม',
        info: 'ข้อมูล',
        success: 'ความสำเร็จ',
        warning: 'คำเตือน'
    };
    
    return names[type] || 'ทั่วไป';
}

/**
 * รับข้อความกลุ่มเป้าหมาย
 */
function getTargetText(announcement) {
    if (announcement.is_all_targets === '1') {
        return 'ทั้งหมด';
    }
    
    const targetParts = [];
    if (announcement.target_department) {
        targetParts.push('แผนก: ' + announcement.target_department_name);
    }
    if (announcement.target_level) {
        targetParts.push('ระดับ: ' + announcement.target_level);
    }
    
    return targetParts.length > 0 ? targetParts.join(', ') : 'ทั้งหมด';
}

$(document).ready(function() {
    // Initialize Summernote editor
    $('#announcement-content').summernote({
        lang: 'th-TH',
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    // Create Announcement Button Click
    $('#create-announcement-btn').on('click', function() {
        // Reset form
        $('#announcementForm')[0].reset();
        $('#announcement-id').val('');
        $('#announcementModalLabel').text('สร้างประกาศใหม่');
        
        // Clear Summernote
        $('#announcement-content').summernote('code', '');
        
        // Show modal
        $('#announcementModal').modal('show');
    });

    // Target All checkbox toggle
    $('#target-all').on('change', function() {
        if($(this).is(':checked')) {
            $('#target-options').addClass('d-none');
        } else {
            $('#target-options').removeClass('d-none');
        }
    });

    // Status change handler
    $('#announcement-status').on('change', function() {
        if($(this).val() === 'scheduled') {
            $('.scheduled-options').removeClass('d-none');
        } else {
            $('.scheduled-options').addClass('d-none');
        }
    });

    // Save Announcement Button Click
    $('#save-announcement').on('click', function() {
        console.log('Save button clicked');
        
        // ตรวจสอบข้อมูลในฟอร์ม
        if (!validateAnnouncementForm()) {
            return;
        }
        
        // แสดง loading
        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // เก็บข้อมูลจากฟอร์ม
        var formData = new FormData(document.getElementById('announcementForm'));
        
        // เพิ่มเนื้อหาจาก Summernote (ถ้ามี)
        if ($.fn.summernote) {
            formData.set('content', $('#announcement-content').summernote('code'));
        }
        
        // Debug: แสดงข้อมูลที่จะส่ง
        console.log('Form data:');
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // ส่งข้อมูลไปยัง server
        $.ajax({
            url: 'api/save_announcement.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Server response:', response);
                
                try {
                    var result = JSON.parse(response);
                    
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'บันทึกประกาศเรียบร้อยแล้ว',
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: result.message || 'ไม่สามารถบันทึกประกาศได้',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    
                    // Reset button
                    $('#save-announcement').removeClass('btn-saving')
                        .html('<i class="fas fa-save mr-1"></i>บันทึกประกาศ');
                    
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล',
                        confirmButtonText: 'ตกลง'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonText: 'ตกลง'
                });
            }
        });
    });
}); 
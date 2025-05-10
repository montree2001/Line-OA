/**
 * activities.js - จัดการหน้ากิจกรรมกลาง
 */

document.addEventListener('DOMContentLoaded', function() {
    // ซ่อนแจ้งเตือนหลังจาก 3 วินาที
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    });
    
    // ตรวจสอบการเชื่อมโยง Select2 ก่อนใช้งาน
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        // เริ่มต้น Select2 สำหรับ dropdown แบบเลือกหลายรายการ
        $('#target_departments, #edit_target_departments').select2({
            placeholder: 'เลือกแผนกวิชา',
            allowClear: true
        });
        
        $('#target_levels, #edit_target_levels').select2({
            placeholder: 'เลือกระดับชั้น',
            allowClear: true
        });
    }
    
    // ตั้งค่าวันที่เริ่มต้นเป็นวันนี้สำหรับฟอร์มเพิ่มกิจกรรม
    const addDateInput = document.getElementById('activity_date');
    if (addDateInput) {
        addDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // ตรวจสอบสิทธิ์การใช้งานกลุ่มเป้าหมาย
    const isAdmin = document.body.classList.contains('role-admin');
    
    // เพิ่ม event listener สำหรับการตรวจสอบการส่งฟอร์มเพิ่มกิจกรรม
    const addForm = document.getElementById('addActivityForm');
    if (addForm) {
        addForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    // เพิ่ม event listener สำหรับการตรวจสอบการส่งฟอร์มแก้ไขกิจกรรม
    const editForm = document.getElementById('editActivityForm');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    // ตัวกรองกิจกรรมเริ่มต้น - ตรวจสอบ URL พารามิเตอร์
    initializeFilters();
});

/**
 * เปิดโมดัลเพิ่มกิจกรรม
 */
function openAddActivityModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addActivityForm').reset();
    
    // กำหนดวันที่เป็นวันนี้
    document.getElementById('activity_date').value = new Date().toISOString().split('T')[0];
    
    // รีเซ็ต Select2 ถ้ามี
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#target_departments').val(null).trigger('change');
        $('#target_levels').val(null).trigger('change');
    }
    
    // เปิดโมดัล
    document.getElementById('addActivityModal').style.display = 'flex';
}

/**
 * เปิดโมดัลแก้ไขกิจกรรม
 */
function openEditActivityModal(activityId) {
    // แสดงการโหลด
    const modal = document.getElementById('editActivityModal');
    const form = document.getElementById('editActivityForm');
    
    form.innerHTML = '<div class="text-center"><div class="spinner"></div><p>กำลังโหลดข้อมูล...</p></div>';
    modal.style.display = 'flex';
    
    // ดึงข้อมูลกิจกรรม
    fetch(`ajax/get_activity.php?activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // คืนค่าฟอร์มเดิม (สามารถปรับปรุงได้ตามความเหมาะสม)
                form.innerHTML = originalEditFormHTML;
                
                const activity = data.activity;
                
                // กำหนดค่าให้ฟอร์ม
                document.getElementById('edit_activity_id').value = activity.activity_id;
                document.getElementById('edit_activity_name').value = activity.activity_name;
                document.getElementById('edit_activity_date').value = activity.activity_date;
                document.getElementById('edit_activity_location').value = activity.activity_location || '';
                document.getElementById('edit_required_attendance').checked = (activity.required_attendance == 1);
                document.getElementById('edit_activity_description').value = activity.description || '';
                
                // กำหนดแผนกวิชาเป้าหมาย
                if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    $('#edit_target_departments').val(activity.target_departments).trigger('change');
                    $('#edit_target_levels').val(activity.target_levels).trigger('change');
                } else {
                    // กำหนดค่าแบบทั่วไปหากไม่มี Select2
                    const deptSelect = document.getElementById('edit_target_departments');
                    Array.from(deptSelect.options).forEach(option => {
                        option.selected = activity.target_departments.includes(parseInt(option.value));
                    });
                    
                    const levelSelect = document.getElementById('edit_target_levels');
                    Array.from(levelSelect.options).forEach(option => {
                        option.selected = activity.target_levels.includes(option.value);
                    });
                }
            } else {
                // แสดงข้อความผิดพลาด
                form.innerHTML = `
                    <div class="alert alert-error">
                        <span class="material-icons">error</span>
                        <div class="alert-message">${data.error || 'ไม่สามารถดึงข้อมูลกิจกรรมได้'}</div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ปิด</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // แสดงข้อความผิดพลาด
            form.innerHTML = `
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <div class="alert-message">เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรม</div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ปิด</button>
                </div>
            `;
        });
}

/**
 * ยืนยันการลบกิจกรรม
 */
function confirmDeleteActivity(activityId, activityName) {
    document.getElementById('delete_activity_id').value = activityId;
    document.getElementById('delete_activity_name').textContent = activityName;
    document.getElementById('deleteActivityModal').style.display = 'flex';
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

/**
 * กรองรายการกิจกรรม
 */
function filterActivities() {
    const month = document.getElementById('filterMonth').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();
    
    // บันทึกค่าลงใน URL เพื่อให้สามารถคงค่าการกรองได้เมื่อโหลดหน้าใหม่
    const url = new URL(window.location);
    if (month) url.searchParams.set('month', month);
    else url.searchParams.delete('month');
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    window.history.replaceState({}, '', url);
    
    // กรองรายการกิจกรรม
    const activities = document.querySelectorAll('.activity-item');
    let visibleCount = 0;
    
    activities.forEach(activity => {
        const activityMonth = activity.dataset.month;
        const activityStatus = activity.dataset.status;
        const activityName = activity.dataset.name;
        
        let isVisible = true;
        
        if (month && activityMonth !== month) {
            isVisible = false;
        }
        
        if (status && activityStatus !== status) {
            isVisible = false;
        }
        
        if (search && !activityName.includes(search)) {
            isVisible = false;
        }
        
        activity.style.display = isVisible ? 'flex' : 'none';
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // แสดงข้อความเมื่อไม่พบกิจกรรม
    document.getElementById('no-results-message').style.display = (visibleCount === 0) ? 'block' : 'none';
}

/**
 * เริ่มต้นตัวกรองจาก URL
 */
function initializeFilters() {
    const url = new URL(window.location);
    
    // ตั้งค่าตัวกรองตาม URL
    if (url.searchParams.has('month')) {
        document.getElementById('filterMonth').value = url.searchParams.get('month');
    }
    
    if (url.searchParams.has('status')) {
        document.getElementById('filterStatus').value = url.searchParams.get('status');
    }
    
    if (url.searchParams.has('search')) {
        document.getElementById('filterSearch').value = url.searchParams.get('search');
    }
    
    // ใช้ตัวกรองทันที
    if (url.searchParams.has('month') || url.searchParams.has('status') || url.searchParams.has('search')) {
        filterActivities();
    }
}

/**
 * ตรวจสอบความถูกต้องของฟอร์มกิจกรรม
 */
function validateActivityForm(form) {
    const name = form.querySelector('[name="activity_name"]').value.trim();
    const date = form.querySelector('[name="activity_date"]').value.trim();
    
    if (!name) {
        alert('กรุณาระบุชื่อกิจกรรม');
        return false;
    }
    
    if (!date) {
        alert('กรุณาระบุวันที่จัดกิจกรรม');
        return false;
    }
    
    return true;
}

// เก็บ HTML ของฟอร์มแก้ไขสำหรับใช้คืนค่า
const originalEditFormHTML = `
<input type="hidden" id="edit_activity_id" name="activity_id">

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="edit_activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
            <input type="text" id="edit_activity_name" name="activity_name" class="form-control" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="edit_activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
            <input type="date" id="edit_activity_date" name="activity_date" class="form-control" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="edit_activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
            <input type="text" id="edit_activity_location" name="activity_location" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">บังคับเข้าร่วม</label>
            <div class="form-check">
                <input type="checkbox" id="edit_required_attendance" name="required_attendance" class="form-check-input">
                <label for="edit_required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_target_departments" class="form-label">แผนกวิชาเป้าหมาย</label>
            <select id="edit_target_departments" name="target_departments[]" class="form-control" multiple>
                <!-- จะเติมตัวเลือกจาก PHP -->
            </select>
            <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_target_levels" class="form-label">ระดับชั้นเป้าหมาย</label>
            <select id="edit_target_levels" name="target_levels[]" class="form-control" multiple>
                <!-- จะเติมตัวเลือกจาก PHP -->
            </select>
            <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="edit_activity_description" class="form-label">รายละเอียดกิจกรรม</label>
    <textarea id="edit_activity_description" name="activity_description" class="form-control" rows="4"></textarea>
</div>

<div class="form-actions">
    <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ยกเลิก</button>
    <button type="submit" name="edit_activity" class="btn btn-primary">
        <span class="material-icons">save</span>
        บันทึกการแก้ไข
    </button>
</div>
`;
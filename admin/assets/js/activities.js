/**
 * activities.js - จัดการหน้ากิจกรรมกลาง
 */

document.addEventListener('DOMContentLoaded', function() {
    // ซ่อนแจ้งเตือนหลังจาก 3 วินาที
    const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
    alerts.forEach(alert => {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    });
    
    // ตั้งค่าวันที่เริ่มต้นเป็นวันนี้สำหรับฟอร์มเพิ่มกิจกรรม
    const addDateInput = document.getElementById('activity_date');
    if (addDateInput) {
        addDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // เชื่อมต่อ event สำหรับการตรวจสอบการส่งฟอร์ม
    const addForm = document.getElementById('addActivityForm');
    if (addForm) {
        addForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    const editForm = document.getElementById('editActivityForm');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    // ตัวกรองกิจกรรมเริ่มต้น
    initializeFilters();
    
    // ป้องกันการปิดโมดัลเมื่อคลิกภายนอก
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                // ไม่ให้ปิดโมดัลเมื่อคลิกพื้นหลัง
                event.stopPropagation();
            }
        });
    });
    
    // ป้องกันการกด ESC เพื่อปิดโมดัล
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            return false;
        }
    });
    
    // สร้างกราฟถ้ามีไลบรารี Chart.js
    if (typeof Chart !== 'undefined') {
        createDepartmentChart();
        createLevelChart();
    }
});

/**
 * เปิดโมดัลเพิ่มกิจกรรม
 */
function openAddActivityModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addActivityForm').reset();
    
    // กำหนดวันที่เป็นวันนี้
    document.getElementById('activity_date').value = new Date().toISOString().split('T')[0];
    
    // ล้างการติกเลือกในช่อง checkbox
    const checkboxes = document.querySelectorAll('#addActivityForm input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // เปิดโมดัล
    const modal = document.getElementById('addActivityModal');
    modal.style.display = 'flex';
    modal.classList.add('prevent-close');
}

/**
 * เปิดโมดัลแก้ไขกิจกรรม
 */
function openEditActivityModal(activityId) {
    // แสดงการโหลด
    const modal = document.getElementById('editActivityModal');
    const form = document.getElementById('editActivityForm');
    
    // แสดงโมดัล
    modal.style.display = 'flex';
    modal.classList.add('prevent-close');
    
    // ล้างการติกเลือกในช่อง checkbox
    const checkboxes = document.querySelectorAll('#editActivityForm input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // ดึงข้อมูลกิจกรรม
    fetch(`ajax/get_activity.php?activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activity = data.activity;
                
                // กำหนดค่าให้ฟอร์ม
                document.getElementById('edit_activity_id').value = activity.activity_id;
                document.getElementById('edit_activity_name').value = activity.activity_name;
                document.getElementById('edit_activity_date').value = activity.activity_date;
                document.getElementById('edit_activity_location').value = activity.activity_location || '';
                document.getElementById('edit_required_attendance').checked = (activity.required_attendance == 1);
                document.getElementById('edit_activity_description').value = activity.description || '';
                
                // กำหนดแผนกวิชาเป้าหมาย
                if (activity.target_departments && activity.target_departments.length > 0) {
                    activity.target_departments.forEach(deptId => {
                        const checkbox = document.getElementById(`edit_dept_${deptId}`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                
                // กำหนดระดับชั้นเป้าหมาย
                if (activity.target_levels && activity.target_levels.length > 0) {
                    activity.target_levels.forEach(level => {
                        const levelId = level.replace('.', '_');
                        const checkbox = document.getElementById(`edit_level_${levelId}`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                
                console.log('Activity data loaded:', activity);
            } else {
                // แสดงข้อความผิดพลาด
                alert(data.error || 'ไม่สามารถดึงข้อมูลกิจกรรมได้');
                closeModal('editActivityModal');
            }
        })
        .catch(error => {
            console.error('Error fetching activity:', error);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรม');
            closeModal('editActivityModal');
        });
}

/**
 * ยืนยันการลบกิจกรรม
 */
function confirmDeleteActivity(activityId, activityName) {
    document.getElementById('delete_activity_id').value = activityId;
    document.getElementById('delete_activity_name').textContent = activityName;
    
    const modal = document.getElementById('deleteActivityModal');
    modal.style.display = 'flex';
    modal.classList.add('prevent-close');
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('prevent-close');
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
        const activityId = activity.dataset.id || '';
        
        let isVisible = true;
        
        if (month && activityMonth !== month) {
            isVisible = false;
        }
        
        if (status && activityStatus !== status) {
            isVisible = false;
        }
        
        // ค้นหาทั้งในชื่อและรหัสกิจกรรม
        if (search && !activityName.includes(search) && !activityId.includes(search)) {
            isVisible = false;
        }
        
        activity.style.display = isVisible ? 'flex' : 'none';
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // แสดงข้อความเมื่อไม่พบกิจกรรม
    const noResultsMessage = document.getElementById('no-results-message');
    if (noResultsMessage) {
        noResultsMessage.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
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

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามแผนกวิชา (สำหรับตัวอย่าง)
 */
function createDepartmentChart() {
    if (typeof Chart === 'undefined') return;
    
    const ctx = document.getElementById('departmentChart');
    if (!ctx) return;
    
    // สร้างข้อมูลตัวอย่าง
    const data = {
        labels: ['ช่างยนต์', 'ช่างไฟฟ้า', 'อิเล็กทรอนิกส์', 'เทคโนโลยีสารสนเทศ', 'ช่างเชื่อมโลหะ'],
        datasets: [
            {
                label: 'นักเรียนทั้งหมด',
                data: [50, 45, 30, 40, 25],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'เข้าร่วมกิจกรรม',
                data: [40, 35, 25, 30, 20],
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }
        ]
    };
    
    // สร้างกราฟ
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามระดับชั้น (สำหรับตัวอย่าง)
 */
function createLevelChart() {
    if (typeof Chart === 'undefined') return;
    
    const ctx = document.getElementById('levelChart');
    if (!ctx) return;
    
    // สร้างข้อมูลตัวอย่าง
    const data = {
        labels: ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'],
        datasets: [
            {
                label: 'นักเรียนทั้งหมด',
                data: [60, 55, 50, 40, 35],
                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            },
            {
                label: 'เข้าร่วมกิจกรรม',
                data: [50, 45, 40, 30, 25],
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }
        ]
    };
    
    // สร้างกราฟ
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
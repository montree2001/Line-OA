/**
 * teachers.js - JavaScript สำหรับหน้าจัดการครูที่ปรึกษา
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแบบค้นหาและกรองข้อมูล
    setupSearch();
    
    // ตั้งค่าการตรวจสอบข้อมูลในฟอร์ม
    setupFormValidation();
    
    // ตั้งค่าการแจ้งเตือน
    setupAlerts();
});

/**
 * ตั้งค่าแบบค้นหาและกรองข้อมูล
 */
function setupSearch() {
    const searchInput = document.getElementById('searchTeacher');
    const filterDepartment = document.getElementById('filterDepartment');
    const filterStatus = document.getElementById('filterStatus');
    
    if (!searchInput || !filterDepartment || !filterStatus) return;
    
    // ฟังก์ชันสำหรับการกรองข้อมูล
    function applyFilters() {
        const searchValue = searchInput.value.toLowerCase();
        const departmentValue = filterDepartment.value.toLowerCase();
        const statusValue = filterStatus.value;
        
        const tableRows = document.querySelectorAll('table tbody tr');
        
        tableRows.forEach(row => {
            const teacherName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const department = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const statusBadge = row.querySelector('td:nth-child(8) .badge');
            const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';
            
            // กำหนดสถานะตามคลาสของ badge
            let statusClass = 'inactive';
            if (statusBadge && statusBadge.classList.contains('text-bg-success')) {
                statusClass = 'active';
            }
            
            // ตรวจสอบว่าตรงกับเงื่อนไขการค้นหาหรือไม่
            const matchesSearch = searchValue === '' || 
                                  teacherName.includes(searchValue);
            const matchesDepartment = departmentValue === '' || 
                                      department.includes(departmentValue);
            const matchesStatus = statusValue === '' || 
                                  statusValue === statusClass;
            
            // แสดงหรือซ่อนแถว
            if (matchesSearch && matchesDepartment && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // ผูกเหตุการณ์กับการค้นหาและกรอง
    searchInput.addEventListener('input', applyFilters);
    filterDepartment.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
}

/**
 * ตั้งค่าการตรวจสอบข้อมูลในฟอร์ม
 */
function setupFormValidation() {
    // ฟอร์มเพิ่มครูที่ปรึกษา
    const addTeacherForm = document.getElementById('addTeacherForm');
    
    if (addTeacherForm) {
        addTeacherForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    }
    
    // ฟอร์มแก้ไขครูที่ปรึกษา
    const editTeacherForm = document.getElementById('editTeacherForm');
    
    if (editTeacherForm) {
        editTeacherForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    }
    
    // ฟอร์มนำเข้าข้อมูล
    const importTeacherForm = document.getElementById('importTeacherForm');
    
    if (importTeacherForm) {
        importTeacherForm.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
        
        // ตรวจสอบประเภทไฟล์
        const importFile = document.getElementById('importFile');
        
        if (importFile) {
            importFile.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    
                    if (!['csv', 'xlsx', 'xls'].includes(fileExt)) {
                        this.setCustomValidity('รองรับเฉพาะไฟล์ .csv, .xlsx หรือ .xls เท่านั้น');
                        this.reportValidity();
                    } else {
                        this.setCustomValidity('');
                    }
                }
            });
        }
    }
}

/**
 * ตั้งค่าการแจ้งเตือน
 */
function setupAlerts() {
    // ซ่อนการแจ้งเตือนหลังจาก 5 วินาที
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            // ใช้ Bootstrap 5 API ถ้ามี
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                // Fallback ถ้าไม่มี Bootstrap 5
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    });
}

/**
 * แสดงโมดัลเพิ่มครูที่ปรึกษา
 */
function showAddTeacherModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('addTeacherForm');
    if (form) {
        form.reset();
        form.classList.remove('was-validated');
    }
    
    // แสดงโมดัล
    const modal = document.getElementById('addTeacherModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * แสดงโมดัลแก้ไขครูที่ปรึกษา
 * 
 * @param {number} teacherId - ID ของครูที่ต้องการแก้ไข
 */
function showEditTeacherModal(teacherId) {
    // ในการใช้งานจริง จะมีการส่ง AJAX request ไปดึงข้อมูลครูจาก backend
    fetchTeacherData(teacherId)
        .then(data => {
            fillEditTeacherForm(data);
            
            // แสดงโมดัล
            const modal = document.getElementById('editTeacherModal');
            if (modal && typeof bootstrap !== 'undefined') {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        })
        .catch(error => {
            console.error('ไม่สามารถดึงข้อมูลครูได้:', error);
            showAlert('ไม่สามารถโหลดข้อมูลครูได้ กรุณาลองใหม่อีกครั้ง', 'danger');
        });
}

/**
 * จำลองการดึงข้อมูลครูจาก backend
 * ในการใช้งานจริง จะใช้ fetch API หรือ AJAX
 * 
 * @param {number} teacherId - ID ของครูที่ต้องการดึงข้อมูล
 * @returns {Promise} - Promise ที่จะ resolve ด้วยข้อมูลครู
 */
function fetchTeacherData(teacherId) {
    // จำลองการส่ง AJAX request ไปยัง backend
    return new Promise((resolve) => {
        // สร้างข้อมูลจำลอง (ในการใช้งานจริงจะดึงจาก backend)
        const teachers = [
            {
                id: 1,
                code: 'T001',
                name: 'อาจารย์ประสิทธิ์ ดีเลิศ',
                prefix: 'อาจารย์',
                firstName: 'ประสิทธิ์',
                lastName: 'ดีเลิศ',
                gender: 'ชาย',
                position: 'ครูชำนาญการพิเศษ',
                class: 'ม.6/2',
                department: 'วิทยาศาสตร์',
                phone: '081-234-5678',
                email: 'prasit.d@prasat.ac.th',
                status: 'active'
            },
            {
                id: 2,
                code: 'T002',
                name: 'อาจารย์วันดี สดใส',
                prefix: 'อาจารย์',
                firstName: 'วันดี',
                lastName: 'สดใส',
                gender: 'หญิง',
                position: 'ครูชำนาญการ',
                class: 'ม.5/3',
                department: 'ภาษาไทย',
                phone: '089-876-5432',
                email: 'wandee.s@prasat.ac.th',
                status: 'active'
            },
            {
                id: 3,
                code: 'T003',
                name: 'อาจารย์อิศรา สุขใจ',
                prefix: 'อาจารย์',
                firstName: 'อิศรา',
                lastName: 'สุขใจ',
                gender: 'ชาย',
                position: 'ครู',
                class: 'ม.5/1',
                department: 'คณิตศาสตร์',
                phone: '062-345-6789',
                email: 'issara.s@prasat.ac.th',
                status: 'active'
            },
            {
                id: 4,
                code: 'T004',
                name: 'อาจารย์ใจดี มากเมตตา',
                prefix: 'อาจารย์',
                firstName: 'ใจดี',
                lastName: 'มากเมตตา',
                gender: 'หญิง',
                position: 'ครูชำนาญการพิเศษ',
                class: 'ม.4/1',
                department: 'ภาษาอังกฤษ',
                phone: '091-234-5678',
                email: 'jaidee.m@prasat.ac.th',
                status: 'active'
            },
            {
                id: 5,
                code: 'T005',
                name: 'อาจารย์สมหมาย ใจร่าเริง',
                prefix: 'อาจารย์',
                firstName: 'สมหมาย',
                lastName: 'ใจร่าเริง',
                gender: 'ชาย',
                position: 'ครูชำนาญการ',
                class: 'ม.4/2',
                department: 'สังคมศึกษา',
                phone: '098-765-4321',
                email: 'sommai.j@prasat.ac.th',
                status: 'inactive'
            }
        ];
        
        // หาข้อมูลครูตาม ID
        const teacher = teachers.find(t => t.id === teacherId);
        
        // จำลองความล่าช้าของ network
        setTimeout(() => {
            resolve(teacher || null);
        }, 300);
    });
}

/**
 * กรอกข้อมูลลงในฟอร์มแก้ไขครู
 * 
 * @param {Object} teacher - ข้อมูลครูที่ต้องการแก้ไข
 */
function fillEditTeacherForm(teacher) {
    if (!teacher) return;
    
    // กรอกข้อมูลลงในฟอร์ม
    document.getElementById('editTeacherId').value = teacher.id;
    document.getElementById('editTeacherCode').value = teacher.code;
    
    // ชื่อและนามสกุล
    document.getElementById('editTeacherPrefix').value = teacher.prefix;
    
    // แยกชื่อและนามสกุล (ในกรณีที่ไม่ได้รับข้อมูลแยกกันมา)
    const nameParts = teacher.name.split(' ');
    const prefix = nameParts[0];
    const name = teacher.name.substring(prefix.length + 1);
    
    document.getElementById('editTeacherName').value = name;
    document.getElementById('editTeacherGender').value = teacher.gender;
    document.getElementById('editTeacherPosition').value = teacher.position;
    document.getElementById('editTeacherDepartment').value = teacher.department;
    document.getElementById('editTeacherClass').value = teacher.class;
    document.getElementById('editTeacherPhone').value = teacher.phone;
    document.getElementById('editTeacherEmail').value = teacher.email;
    
    // ตั้งค่าสถานะ
    if (teacher.status === 'active') {
        document.getElementById('editStatusActive').checked = true;
    } else {
        document.getElementById('editStatusInactive').checked = true;
    }
}

/**
 * แสดงโมดัลนำเข้าข้อมูล
 */
function showImportModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('importTeacherForm');
    if (form) {
        form.reset();
        form.classList.remove('was-validated');
    }
    
    // แสดงโมดัล
    const modal = document.getElementById('importModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * แสดงยืนยันการลบ
 * 
 * @param {number} teacherId - ID ของครูที่ต้องการลบ
 */
function showDeleteConfirmation(teacherId) {
    document.getElementById('deleteTeacherId').value = teacherId;
    
    // แสดงโมดัล
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1050';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <span class="material-icons align-middle me-2">${getAlertIcon(type)}</span>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ซ่อนการแจ้งเตือนหลังจาก 5 วินาที
    setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        } else {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * ได้รับไอคอนสำหรับการแจ้งเตือนตามประเภท
 * 
 * @param {string} type - ประเภทของการแจ้งเตือน
 * @returns {string} - ชื่อไอคอน
 */
function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'danger': return 'error';
        case 'warning': return 'warning';
        default: return 'info';
    }
}
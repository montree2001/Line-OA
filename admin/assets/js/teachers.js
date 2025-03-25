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
    const filterLineStatus = document.getElementById('filterLineStatus');
    
    if (!searchInput) return;
    
    // ฟังก์ชันสำหรับการกรองข้อมูล
    function applyFilters() {
        const searchValue = searchInput.value.toLowerCase();
        const departmentValue = filterDepartment ? filterDepartment.value.toLowerCase() : '';
        const statusValue = filterStatus ? filterStatus.value : '';
        const lineStatusValue = filterLineStatus ? filterLineStatus.value : '';
        
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
    
    // ผูกเหตุการณ์กับการค้นหาและกรอง
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
    
    if (filterDepartment) {
        filterDepartment.addEventListener('change', applyFilters);
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilters);
    }
    
    if (filterLineStatus) {
        filterLineStatus.addEventListener('change', applyFilters);
    }
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
    console.log("Edit teacher ID:", teacherId);
    
    // ค้นหาแถวของครูในตาราง
    const teacherRow = document.querySelector(`tr[data-id="${teacherId}"]`);
    if (!teacherRow) {
        console.error("Teacher row not found for ID:", teacherId);
        alert("ไม่พบข้อมูลครูที่ต้องการแก้ไข");
        return;
    }
    
    try {
        // ดึงข้อมูลจาก DOM
        const fullNameContainer = teacherRow.querySelector('td:nth-child(1) div:nth-child(2)');
        const fullName = fullNameContainer ? fullNameContainer.textContent.trim() : '';
        console.log("Full name:", fullName);
        
        const nameParts = fullName.split(' ');
        let prefix = 'อาจารย์';
        let firstName = '';
        let lastName = '';
        
        if (nameParts.length >= 2) {
            prefix = nameParts[0];
            firstName = nameParts[1] || '';
            lastName = nameParts.slice(2).join(' ') || '';
        }
        
        const nationalIdCell = teacherRow.querySelector('td:nth-child(2)');
        const nationalId = nationalIdCell ? nationalIdCell.textContent.trim() : '';
        
        const departmentCell = teacherRow.querySelector('td:nth-child(3)');
        const department = departmentCell ? departmentCell.textContent.trim() : '';
        
        const positionCell = teacherRow.querySelector('td:nth-child(4)');
        const position = positionCell ? positionCell.textContent.trim() : '';
        
        const contactInfo = teacherRow.querySelector('td:nth-child(6)');
        const phoneElement = contactInfo ? contactInfo.querySelector('div:nth-child(1)') : null;
        const phone = phoneElement ? phoneElement.textContent.replace('phone', '').trim() : '';
        
        const emailElement = contactInfo ? contactInfo.querySelector('div:nth-child(2)') : null;
        const email = emailElement ? emailElement.textContent.replace('email', '').trim() : '';
        
        const statusBadge = teacherRow.querySelector('td:nth-child(7) .badge');
        const isActive = statusBadge && statusBadge.classList.contains('text-bg-success');
        
        // ตรวจสอบสถานะการเชื่อมต่อ Line
        const lineBadge = teacherRow.querySelector('td:nth-child(8) .badge');
        const isLineConnected = lineBadge && lineBadge.style.backgroundColor === 'rgb(6, 199, 85)';
        
        console.log("Extracted data:", {
            teacherId, prefix, firstName, lastName, nationalId, department, position, phone, email, isActive, isLineConnected
        });
        
        // กรอกข้อมูลในฟอร์ม
        document.getElementById('editTeacherId').value = teacherId;
        document.getElementById('editTeacherNationalId').value = nationalId;
        document.getElementById('editTeacherPrefix').value = prefix;
        document.getElementById('editTeacherFirstName').value = firstName;
        document.getElementById('editTeacherLastName').value = lastName;
        document.getElementById('editTeacherPosition').value = position;
        document.getElementById('editTeacherDepartment').value = department;
        
        document.getElementById('editTeacherPhone').value = phone === '-' ? '' : phone;
        document.getElementById('editTeacherEmail').value = email === '-' ? '' : email;
        
        // ตั้งค่าสถานะ
        if (isActive) {
            document.getElementById('editStatusActive').checked = true;
        } else {
            document.getElementById('editStatusInactive').checked = true;
        }
        
        // แสดงโมดัล
        var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
        editModal.show();
        
    } catch (error) {
        console.error("Error in showEditTeacherModal:", error);
        alert("ไม่สามารถดึงข้อมูลได้ กรุณาลองใหม่อีกครั้ง");
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
 * @param {string} teacherName - ชื่อครูที่ต้องการลบ
 */
function showDeleteConfirmation(teacherId, teacherName) {
    document.getElementById('deleteTeacherId').value = teacherId;
    document.getElementById('deleteTeacherName').textContent = teacherName;
    
    // แสดงโมดัล
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * แสดงยืนยันการเปลี่ยนสถานะ
 * 
 * @param {number} teacherId - ID ของครูที่ต้องการเปลี่ยนสถานะ
 * @param {string} action - การกระทำ (ระงับ/เปิดใช้งาน)
 */
function confirmToggleStatus(teacherId, action) {
    document.getElementById('toggleAction').textContent = action;
    
    // ตั้งค่าปุ่มยืนยัน
    const confirmBtn = document.getElementById('confirmToggleBtn');
    confirmBtn.onclick = function() {
        document.getElementById('toggleForm' + teacherId).submit();
    };
    
    // แสดงโมดัล
    const modal = document.getElementById('toggleStatusModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

/**
 * แสดงข้อความแจ้งเตือนแบบป๊อปอัพ
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
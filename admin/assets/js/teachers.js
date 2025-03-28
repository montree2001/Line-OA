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
    // ปิด modal อื่นๆ ที่อาจเปิดอยู่
    closeAllModals();
    
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

// เพิ่มฟังก์ชันใหม่สำหรับปิด modal ทั้งหมด
function closeAllModals() {
    // ปิด modal ทั้งหมดที่อาจเปิดอยู่
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        try {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        } catch (e) {
            console.error("Error closing modal:", e);
        }
    });
    
    // ลบ backdrop ที่อาจค้างอยู่
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        if (backdrop && backdrop.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
        }
    });
    
    // รีเซ็ต body styles ที่อาจถูกเพิ่มโดย Bootstrap
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}
/**
 * แสดงโมดัลแก้ไขครูที่ปรึกษา
 * 
 * @param {number} teacherId - ID ของครูที่ต้องการแก้ไข
 */
function showEditTeacherModal(teacherId) {
    console.log("Edit teacher ID:", teacherId);
    closeAllModals()
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
    closeAllModals()
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
    closeAllModals()
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
    closeAllModals();
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

/**
 * ตรวจสอบความถูกต้องของข้อมูลในฟอร์มและแจ้งเตือน
 */
// เพิ่มตรวจสอบความถูกต้องของฟอร์มเพิ่มครู
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบฟอร์มเพิ่มครู

    document.querySelectorAll('.modal .btn-close, .modal .btn-secondary').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            closeAllModals();
        });
    });


    const addTeacherForm = document.getElementById('addTeacherForm');
    if (addTeacherForm) {
        addTeacherForm.addEventListener('submit', function(event) {
            if (!validateTeacherForm(this)) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // ตรวจสอบข้อมูลซ้ำ (เลขบัตรประชาชน)
                checkDuplicateNationalId(this, event);
            }
        });
    }

    // ตรวจสอบฟอร์มแก้ไขครู
    const editTeacherForm = document.getElementById('editTeacherForm');
    if (editTeacherForm) {
        editTeacherForm.addEventListener('submit', function(event) {
            if (!validateTeacherForm(this)) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // ตรวจสอบข้อมูลซ้ำ (เลขบัตรประชาชน) ยกเว้นรายการตัวเอง
                const teacherId = document.getElementById('editTeacherId').value;
                checkDuplicateNationalId(this, event, teacherId);
            }
        });
    }

    // ตรวจสอบความถูกต้องของเลขบัตรประชาชน
    const nationalIdInputs = document.querySelectorAll('input[name="teacher_national_id"]');
    nationalIdInputs.forEach(input => {
        input.addEventListener('input', function() {
            validateNationalId(this);
        });
        input.addEventListener('blur', function() {
            validateNationalId(this);
        });
    });
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
});

/**
 * ตรวจสอบความถูกต้องของฟอร์มครู
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @returns {boolean} - ความถูกต้องของฟอร์ม
 */
function validateTeacherForm(form) {
    let isValid = true;
    form.classList.add('was-validated');

    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
            
            // เพิ่มข้อความแจ้งเตือน
            if (field.nextElementSibling && field.nextElementSibling.classList.contains('invalid-feedback')) {
                field.nextElementSibling.textContent = 'กรุณากรอกข้อมูลในช่องนี้';
            }
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });

    // ตรวจสอบเลขบัตรประชาชน
    const nationalIdField = form.querySelector('input[name="teacher_national_id"]');
    if (nationalIdField && nationalIdField.value.trim()) {
        if (!validateNationalId(nationalIdField)) {
            isValid = false;
        }
    }

    // ตรวจสอบอีเมล
    const emailField = form.querySelector('input[type="email"]');
    if (emailField && emailField.value.trim()) {
        if (!validateEmail(emailField.value)) {
            emailField.classList.add('is-invalid');
            isValid = false;
            
            // เพิ่มข้อความแจ้งเตือน
            if (emailField.nextElementSibling && emailField.nextElementSibling.classList.contains('invalid-feedback')) {
                emailField.nextElementSibling.textContent = 'รูปแบบอีเมลไม่ถูกต้อง';
            }
        } else {
            emailField.classList.remove('is-invalid');
            emailField.classList.add('is-valid');
        }
    }

    // ตรวจสอบเบอร์โทรศัพท์
    const phoneField = form.querySelector('input[name="teacher_phone"]');
    if (phoneField && phoneField.value.trim()) {
        if (!validatePhone(phoneField.value)) {
            phoneField.classList.add('is-invalid');
            isValid = false;
            
            // เพิ่มข้อความแจ้งเตือน
            if (phoneField.nextElementSibling && phoneField.nextElementSibling.classList.contains('invalid-feedback')) {
                phoneField.nextElementSibling.textContent = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
            }
        } else {
            phoneField.classList.remove('is-invalid');
            phoneField.classList.add('is-valid');
        }
    }

    // แสดงแจ้งเตือนถ้าไม่ผ่านการตรวจสอบ
    if (!isValid) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'warning');
    }

    return isValid;
}

/**
 * ตรวจสอบความถูกต้องของเลขบัตรประชาชน
 * 
 * @param {HTMLInputElement} input - input field ของเลขบัตรประชาชน
 * @returns {boolean} - ความถูกต้องของเลขบัตรประชาชน
 */
function validateNationalId(input) {
    const nationalId = input.value.trim();
    
    // ต้องเป็นตัวเลข 13 หลัก
    if (!/^\d{13}$/.test(nationalId)) {
        input.classList.add('is-invalid');
        
        // เพิ่มข้อความแจ้งเตือน
        if (input.nextElementSibling && input.nextElementSibling.classList.contains('invalid-feedback')) {
            input.nextElementSibling.textContent = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
        }
        
        return false;
    }
    
    // ตรวจสอบความถูกต้องตามอัลกอริทึมของเลขบัตรประชาชนไทย
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseFloat(nationalId.charAt(i)) * (13 - i);
    }
    
    let checkDigit = (11 - sum % 11) % 10;
    
    if (checkDigit !== parseInt(nationalId.charAt(12))) {
        input.classList.add('is-invalid');
        
        // เพิ่มข้อความแจ้งเตือน
        if (input.nextElementSibling && input.nextElementSibling.classList.contains('invalid-feedback')) {
            input.nextElementSibling.textContent = 'เลขบัตรประชาชนไม่ถูกต้อง';
        }
        
        return false;
    }
    
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    return true;
}

/**
 * ตรวจสอบความถูกต้องของอีเมล
 * 
 * @param {string} email - อีเมลที่ต้องการตรวจสอบ
 * @returns {boolean} - ความถูกต้องของอีเมล
 */
function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * ตรวจสอบความถูกต้องของเบอร์โทรศัพท์
 * 
 * @param {string} phone - เบอร์โทรศัพท์ที่ต้องการตรวจสอบ
 * @returns {boolean} - ความถูกต้องของเบอร์โทรศัพท์
 */
function validatePhone(phone) {
    const re = /^0[0-9]{8,9}$/;
    return re.test(String(phone).trim());
}

/**
 * ตรวจสอบข้อมูลซ้ำ (เลขบัตรประชาชน)
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @param {Event} event - event ที่เกิดขึ้น
 * @param {number} excludeId - รหัสครูที่ยกเว้นการตรวจสอบ (กรณีแก้ไข)
 */
function checkDuplicateNationalId(form, event, excludeId = null) {
    const nationalIdField = form.querySelector('input[name="teacher_national_id"]');
    if (!nationalIdField) return;
    
    const nationalId = nationalIdField.value.trim();
    if (!nationalId) return;
    
    // ตรวจสอบข้อมูลซ้ำผ่าน AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'check_duplicate_teacher.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.duplicate) {
                    // พบข้อมูลซ้ำ แสดงข้อความแจ้งเตือน
                    event.preventDefault();
                    nationalIdField.classList.add('is-invalid');
                    
                    // เพิ่มข้อความแจ้งเตือน
                    if (nationalIdField.nextElementSibling && nationalIdField.nextElementSibling.classList.contains('invalid-feedback')) {
                        nationalIdField.nextElementSibling.textContent = 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว';
                    }
                    
                    showAlert('เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว กรุณาตรวจสอบข้อมูล', 'danger');
                }
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
        }
    };
    
    // ส่งข้อมูลไปตรวจสอบ
    const data = 'national_id=' + encodeURIComponent(nationalId);
    if (excludeId) {
        data += '&exclude_id=' + encodeURIComponent(excludeId);
    }
    
    xhr.send(data);
}

/**
 * แสดงข้อความแจ้งเตือนแบบป๊อปอัพ
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 * @param {number} timeout - ระยะเวลาที่แสดง (มิลลิวินาที)
 */
function showAlert(message, type = 'info', timeout = 5000) {
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
    
    // ซ่อนการแจ้งเตือนหลังจากเวลาที่กำหนด
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
    }, timeout);
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
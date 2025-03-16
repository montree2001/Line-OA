/**
 * students.js - จาวาสคริปต์สำหรับหน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    initEventListeners();
    
    // ตั้งค่าอื่นๆ
    initStudentPage();
});

/**
 * ตั้งค่า event listeners
 */
function initEventListeners() {
    // Event listener สำหรับการค้นหา
    const searchForm = document.querySelector('.filter-container');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            searchStudents();
        });
    }
    
    // Event listener สำหรับปุ่มเพิ่มนักเรียน
    const addButton = document.querySelector('button[onclick="showAddStudentModal()"]');
    if (addButton) {
        addButton.addEventListener('click', showAddStudentModal);
    }
    
    // Event listener สำหรับฟอร์มเพิ่มนักเรียน
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addStudent();
        });
    }
    
    // Event listener สำหรับฟอร์มแก้ไขนักเรียน
    const editForm = document.getElementById('editStudentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateStudent();
        });
    }
    
    // Event listener สำหรับฟอร์มลบนักเรียน
    const deleteForm = document.getElementById('deleteStudentForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            deleteStudentConfirm();
        });
    }
    
    // Event listener สำหรับฟอร์มนำเข้าข้อมูล
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            importStudents();
        });
    }
}

/**
 * ตั้งค่าหน้านักเรียน
 */
function initStudentPage() {
    // ตั้งค่า datepicker สำหรับวันเกิด (ถ้ามี)
    const birthDateInputs = document.querySelectorAll('input[type="date"]');
    if (birthDateInputs.length > 0) {
        // ในทางปฏิบัติอาจใช้ไลบรารีเพิ่มเติม เช่น Flatpickr, jQuery UI Datepicker
        console.log('Date inputs initialized');
    }
    
    // ทำให้ตารางสามารถเรียงได้ (ถ้าต้องการ)
    initSortableTable();
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปยัง server เพื่อค้นหานักเรียน
    
    // จำลองการค้นหา
    showAlert('กำลังค้นหานักเรียน...', 'info');
    
    // จำลองการแสดงผลลัพธ์
    setTimeout(function() {
        showAlert('พบนักเรียนที่ตรงตามเงื่อนไข 3 คน', 'success');
    }, 1000);
}

/**
 * แสดงโมดัลเพิ่มนักเรียน
 */
function showAddStudentModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('addStudentForm');
    if (form) {
        form.reset();
    }
    
    // แสดงโมดัล
    showModal('addStudentModal');
}

/**
 * เพิ่มนักเรียนใหม่
 */
function addStudent() {
    // ในทางปฏิบัติจริง จะส่งข้อมูลไปยัง server ด้วย AJAX
    const form = document.getElementById('addStudentForm');
    const formData = new FormData(form);
    
    // จำลองการส่งข้อมูล
    showAlert('กำลังเพิ่มข้อมูลนักเรียน...', 'info');
    
    // จำลองการเพิ่มข้อมูลสำเร็จ
    setTimeout(function() {
        showAlert('เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว', 'success');
        closeModal('addStudentModal');
        
        // จำลองการรีโหลดหน้า (ในทางปฏิบัติอาจใช้ AJAX เพื่อโหลดข้อมูลใหม่)
        // window.location.reload();
    }, 1000);
}

/**
 * แสดงโมดัลแก้ไขข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function editStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลนักเรียนมาแสดงในฟอร์ม
    
    // จำลองการโหลดข้อมูลนักเรียน
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // เซ็ตค่า ID ที่จะแก้ไข
    document.getElementById('edit_student_id').value = studentId;
    
    // จำลองการโหลดข้อมูลสำเร็จ
    setTimeout(function() {
        // ในทางปฏิบัติจริง จะเติมข้อมูลในฟอร์ม
        
        // ซ่อนการแจ้งเตือน
        hideAlert();
        
        // แสดงโมดัล
        showModal('editStudentModal');
    }, 1000);
}

/**
 * อัปเดตข้อมูลนักเรียน
 */
function updateStudent() {
    // ในทางปฏิบัติจริง จะส่งข้อมูลไปยัง server ด้วย AJAX
    const form = document.getElementById('editStudentForm');
    const formData = new FormData(form);
    const studentId = document.getElementById('edit_student_id').value;
    
    // จำลองการส่งข้อมูล
    showAlert('กำลังอัปเดตข้อมูลนักเรียน...', 'info');
    
    // จำลองการอัปเดตข้อมูลสำเร็จ
    setTimeout(function() {
        showAlert('อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว', 'success');
        closeModal('editStudentModal');
        
        // จำลองการรีโหลดหน้า (ในทางปฏิบัติอาจใช้ AJAX เพื่อโหลดข้อมูลใหม่)
        // window.location.reload();
    }, 1000);
}

/**
 * แสดงโมดัลลบข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function viewStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลนักเรียนมาแสดง
    
    // จำลองการโหลดข้อมูลนักเรียน
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // จำลองการโหลดข้อมูลสำเร็จ
    setTimeout(function() {
        // ในทางปฏิบัติจริง จะเติมข้อมูลในหน้าดูรายละเอียด
        
        // ซ่อนการแจ้งเตือน
        hideAlert();
        
        // แสดงโมดัล
        showModal('viewStudentModal');
    }, 1000);
}

/**
 * แสดงโมดัลลบข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function deleteStudent(studentId) {
    // ในทางปฏิบัติจริง จะดึงข้อมูลชื่อนักเรียนมาแสดง
    
    // เซ็ตค่า ID ที่จะลบ
    document.getElementById('delete_student_id').value = studentId;
    
    // จำลองการโหลดข้อมูลนักเรียน
    // ในทางปฏิบัติจริง จะดึงชื่อนักเรียนมาแสดงในหน้ายืนยันการลบ
    const studentName = 'นายธนกฤต สุขใจ'; // จำลองชื่อนักเรียน
    document.getElementById('delete_student_name').textContent = studentName;
    
    // แสดงโมดัล
    showModal('deleteStudentModal');
}

/**
 * ลบข้อมูลนักเรียน
 */
function deleteStudentConfirm() {
    // ในทางปฏิบัติจริง จะส่งข้อมูลไปยัง server ด้วย AJAX
    const studentId = document.getElementById('delete_student_id').value;
    
    // จำลองการส่งข้อมูล
    showAlert('กำลังลบข้อมูลนักเรียน...', 'info');
    
    // จำลองการลบข้อมูลสำเร็จ
    setTimeout(function() {
        showAlert('ลบข้อมูลนักเรียนเรียบร้อยแล้ว', 'success');
        closeModal('deleteStudentModal');
        
        // จำลองการรีโหลดหน้า (ในทางปฏิบัติอาจใช้ AJAX เพื่อโหลดข้อมูลใหม่)
        // window.location.reload();
    }, 1000);
}

/**
 * นำเข้าข้อมูลนักเรียน
 */
function importStudents() {
    // ในทางปฏิบัติจริง จะส่งข้อมูลไปยัง server ด้วย AJAX
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    // ตรวจสอบว่ามีการเลือกไฟล์หรือไม่
    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('กรุณาเลือกไฟล์ Excel', 'warning');
        return;
    }
    
    // จำลองการส่งข้อมูล
    showAlert('กำลังนำเข้าข้อมูลนักเรียน...', 'info');
    
    // จำลองการนำเข้าข้อมูลสำเร็จ
    setTimeout(function() {
        showAlert('นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว จำนวน 50 รายการ', 'success');
        closeModal('importModal');
        
        // จำลองการรีโหลดหน้า (ในทางปฏิบัติอาจใช้ AJAX เพื่อโหลดข้อมูลใหม่)
        // window.location.reload();
    }, 2000);
}

/**
 * แสดงโมดัลนำเข้าข้อมูล
 */
function showImportModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('importForm');
    if (form) {
        form.reset();
    }
    
    // แสดงโมดัล
    showModal('importModal');
}

/**
 * ทำให้ตารางสามารถเรียงได้
 */
function initSortableTable() {
    const table = document.querySelector('.data-table');
    if (!table) return;
    
    const tableHeaders = table.querySelectorAll('th');
    tableHeaders.forEach(header => {
        // ข้ามคอลัมน์การจัดการ
        if (header.textContent.trim() === 'จัดการ') return;
        
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(table, Array.from(tableHeaders).indexOf(header));
        });
    });
}

/**
 * เรียงตาราง
 * 
 * @param {HTMLElement} table - ตารางที่ต้องการเรียง
 * @param {number} columnIndex - ลำดับคอลัมน์ที่ต้องการเรียง
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headers = table.querySelectorAll('th');
    const header = headers[columnIndex];
    
    // เปลี่ยนทิศทางการเรียง
    const isAscending = header.classList.contains('sort-asc');
    
    // รีเซ็ตการเรียงทั้งหมด
    headers.forEach(h => {
        h.classList.remove('sort-asc', 'sort-desc');
    });
    
    // กำหนดทิศทางการเรียงใหม่
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    // เรียงข้อมูล
    rows.sort((rowA, rowB) => {
        const cellA = rowA.querySelectorAll('td')[columnIndex].textContent.trim();
        const cellB = rowB.querySelectorAll('td')[columnIndex].textContent.trim();
        
        // หากเป็นตัวเลข ให้เปรียบเทียบเป็นตัวเลข
        if (!isNaN(cellA) && !isNaN(cellB)) {
            return isAscending ? parseFloat(cellB) - parseFloat(cellA) : parseFloat(cellA) - parseFloat(cellB);
        }
        
        // หากเป็นข้อความ ให้เปรียบเทียบเป็นข้อความ
        return isAscending ? cellB.localeCompare(cellA, 'th') : cellA.localeCompare(cellB, 'th');
    });
    
    // อัปเดตตาราง
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

/**
 * แสดงโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการแสดง
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * ซ่อนโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการซ่อน
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * แสดงการแจ้งเตือน
 * 
 * @param {string} message - ข้อความแจ้งเตือน
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // สร้าง Alert Container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง Alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม Alert ไปยัง Container
    alertContainer.appendChild(alert);
    
    // กำหนด Event Listener สำหรับปุ่มปิด
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
        }, 300);
    });
    
    // ให้ Alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * ซ่อนการแจ้งเตือนทั้งหมด
 */
function hideAlert() {
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        const alerts = alertContainer.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        });
    }
}



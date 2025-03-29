/**
 * students.js - จาวาสคริปต์สำหรับหน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat (ปรับปรุงใหม่)
 */

// ตัวแปรสำหรับเก็บข้อมูลปัจจุบัน
let currentStudent = null;
let dataTable = null;

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    initEventListeners();
    
    // ตั้งค่า DataTables
    initDataTable();
    
    // ตั้งค่า toast
    initializeToasts();
    
    // ตั้งค่าการตรวจสอบฟอร์มแบบ real-time
    setupFormValidation();
});

/**
 * ตั้งค่า event listeners
 */
function initEventListeners() {
    // สลับแสดง/ซ่อนตัวกรอง
    const toggleFilterBtn = document.getElementById('toggle-filter');
    const filterContainer = document.getElementById('filter-container');
    
    if (toggleFilterBtn && filterContainer) {
        toggleFilterBtn.addEventListener('click', function() {
            filterContainer.classList.toggle('collapsed');
            this.querySelector('i').textContent = filterContainer.classList.contains('collapsed') ? 'expand_more' : 'expand_less';
        });
    }
    
    // Event listener สำหรับฟอร์มเพิ่มนักเรียน
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(this)) {
                submitForm(this);
            }
        });
    }
    
    // Event listener สำหรับฟอร์มแก้ไขนักเรียน
    const editForm = document.getElementById('editStudentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateForm(this)) {
                submitForm(this);
            }
        });
    }
    
    // Event listener สำหรับฟอร์มลบนักเรียน
    const deleteForm = document.getElementById('deleteStudentForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this);
        });
    }
    
    // Event listener สำหรับฟอร์มนำเข้าข้อมูล
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateImportForm(this)) {
                submitForm(this);
            }
        });
        
        // จัดการการอัปโหลดไฟล์
        setupFileUpload();
    }
    
    // Event listener สำหรับปุ่มดาวน์โหลดเทมเพลต
    const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');
    if (downloadTemplateBtn) {
        downloadTemplateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            downloadTemplate();
        });
    }
    
    // Event listener สำหรับปุ่มแก้ไขในโมดัลดูข้อมูล
    const editBtn = document.getElementById('edit_btn');
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            // ปิดโมดัลดูข้อมูล
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentModal'));
            viewModal.hide();
            
            // เปิดโมดัลแก้ไข
            if (currentStudent) {
                setTimeout(() => {
                    editStudent(currentStudent.student_id);
                }, 500);
            }
        });
    }
    
    // Event listener สำหรับปุ่มสร้าง QR LINE ในโมดัลดูข้อมูล
    const generateQrBtn = document.getElementById('generate_qr_btn');
    if (generateQrBtn) {
        generateQrBtn.addEventListener('click', function() {
            // ปิดโมดัลดูข้อมูล
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewStudentModal'));
            viewModal.hide();
            
            // เปิดโมดัลสร้าง QR
            if (currentStudent) {
                setTimeout(() => {
                    generateLineQR(currentStudent.student_id);
                }, 500);
            }
        });
    }
}

/**
 * ตั้งค่าการอัปโหลดไฟล์
 */
function setupFileUpload() {
    const fileInput = document.querySelector('.file-input');
    const uploadArea = document.querySelector('.upload-area');
    const selectedFile = document.querySelector('.selected-file');
    const selectedFileName = document.getElementById('selected-file-name');
    const clearFileBtn = document.getElementById('clear-file');
    const importBtn = document.getElementById('importBtn');
    
    if (!fileInput || !uploadArea || !selectedFile || !selectedFileName || !clearFileBtn || !importBtn) return;
    
    // เมื่อคลิกที่พื้นที่อัปโหลด ให้เปิด file input
    uploadArea.addEventListener('click', function(e) {
        if (e.target.classList.contains('file-input')) return;
        fileInput.click();
    });
    
    // เมื่อลากไฟล์มาวาง
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });
    
    // เมื่อเลือกไฟล์
    fileInput.addEventListener('change', function() {
        handleFileSelect();
    });
    
    // ฟังก์ชันจัดการเมื่อเลือกไฟล์
    function handleFileSelect() {
        if (fileInput.files && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            selectedFileName.textContent = file.name;
            selectedFile.classList.remove('d-none');
            uploadArea.classList.add('d-none');
            importBtn.disabled = false;
        } else {
            selectedFile.classList.add('d-none');
            uploadArea.classList.remove('d-none');
            importBtn.disabled = true;
        }
    }
    
    // เมื่อคลิกปุ่มลบไฟล์
    clearFileBtn.addEventListener('click', function() {
        fileInput.value = '';
        selectedFile.classList.add('d-none');
        uploadArea.classList.remove('d-none');
        importBtn.disabled = true;
    });
}

/**
 * ตั้งค่า DataTables
 */
function initDataTable() {
    const table = document.getElementById('students-table');
    if (!table) return;
    
    // ตั้งค่า DataTables
    dataTable = new DataTable('#students-table', {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json'
        },
        columnDefs: [
            { orderable: false, targets: 7 } // ปิดการเรียงลำดับในคอลัมน์จัดการ
        ],
        order: [[0, 'asc']], // เรียงตามรหัสนักเรียน
        pageLength: 10,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'ทั้งหมด']
        ]
    });
}

/**
 * ตั้งค่า toasts
 */
function initializeToasts() {
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function (toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        toast.show();
        return toast;
    });
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
    const modal = new bootstrap.Modal(document.getElementById('addStudentModal'));
    modal.show();
}

/**
 * แสดงโมดัลแก้ไขข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function editStudent(studentId) {
    // แสดงการโหลด
    showLoading('กำลังโหลดข้อมูลนักเรียน...');
    
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`, {
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            fillEditForm(data.student);
            
            // แสดงโมดัล
            const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
            modal.show();
        } else {
            showAlert('danger', 'ข้อผิดพลาด', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading student data:', error);
        hideLoading();
        showAlert('danger', 'ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน');
    });
}

/**
 * เติมข้อมูลในฟอร์มแก้ไขนักเรียน
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function fillEditForm(student) {
    const form = document.getElementById('editStudentForm');
    if (!form) return;
    
    // เซ็ตค่า ID สำหรับแก้ไข
    form.querySelector('input[name="student_id"]').value = student.student_id;
    
    // เซ็ตค่าข้อมูลพื้นฐาน
    form.querySelector('input[name="student_code"]').value = student.student_code;
    
    // เซ็ตค่า dropdowns
    setSelectValue(form.querySelector('select[name="title"]'), student.title);
    setSelectValue(form.querySelector('select[name="status"]'), student.status);
    
    // เซ็ตค่าชื่อ-นามสกุล
    form.querySelector('input[name="firstname"]').value = student.first_name;
    form.querySelector('input[name="lastname"]').value = student.last_name;
    
    // เซ็ตค่าข้อมูลติดต่อ
    form.querySelector('input[name="phone_number"]').value = student.phone_number || '';
    form.querySelector('input[name="email"]').value = student.email || '';
    
    // เซ็ตค่าชั้นเรียน
    if (student.class_id) {
        setSelectValue(form.querySelector('select[name="class_id"]'), student.class_id);
    }
}

/**
 * แสดงโมดัลดูข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function viewStudent(studentId) {
    // แสดงการโหลด
    showLoading('กำลังโหลดข้อมูลนักเรียน...');
    
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`, {
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            currentStudent = data.student;
            updateStudentViewModal(data.student);
            
            // แสดงโมดัล
            const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
            modal.show();
        } else {
            showAlert('danger', 'ข้อผิดพลาด', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading student data:', error);
        hideLoading();
        showAlert('danger', 'ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน');
    });
}

/**
 * อัปเดตข้อมูลในโมดัลดูรายละเอียดนักเรียน
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function updateStudentViewModal(student) {
    const modal = document.getElementById('viewStudentModal');
    if (!modal) return;
    
    // อัปเดตอวาตาร์
    const avatar = modal.querySelector('#view_avatar');
    if (avatar) {
        avatar.textContent = student.first_name.charAt(0);
    }
    
    // อัปเดตข้อมูลพื้นฐาน
    const fullNameEl = modal.querySelector('#view_full_name');
    if (fullNameEl) {
        fullNameEl.textContent = `${student.title}${student.first_name} ${student.last_name}`;
    }
    
    const studentCodeEl = modal.querySelector('#view_student_code');
    if (studentCodeEl) {
        studentCodeEl.textContent = `รหัสนักศึกษา: ${student.student_code}`;
    }
    
    const classEl = modal.querySelector('#view_class');
    if (classEl) {
        classEl.textContent = student.class || 'ไม่ระบุชั้นเรียน';
    }
    
    // แสดงสถานะการเชื่อมต่อไลน์
    const lineStatusEl = modal.querySelector('#view_line_status');
    if (lineStatusEl) {
        if (student.line_connected) {
            lineStatusEl.innerHTML = '<span class="badge bg-success"><i class="material-icons">check_circle</i> เชื่อมต่อไลน์แล้ว</span>';
        } else {
            lineStatusEl.innerHTML = '<span class="badge bg-secondary"><i class="material-icons">cancel</i> ยังไม่ได้เชื่อมต่อไลน์</span>';
        }
    }
    
    // อัปเดตข้อมูลติดต่อ
    const phoneEl = modal.querySelector('#view_phone');
    if (phoneEl) {
        phoneEl.textContent = student.phone_number || 'ไม่ระบุ';
    }
    
    const emailEl = modal.querySelector('#view_email');
    if (emailEl) {
        emailEl.textContent = student.email || 'ไม่ระบุ';
    }
    
    // อัปเดตข้อมูลการศึกษา
    const advisorEl = modal.querySelector('#view_advisor');
    if (advisorEl) {
        advisorEl.textContent = student.advisor_name || 'ไม่ระบุ';
    }
    
    const departmentEl = modal.querySelector('#view_department');
    if (departmentEl) {
        departmentEl.textContent = student.department_name || 'ไม่ระบุ';
    }
    
    const statusEl = modal.querySelector('#view_status');
    if (statusEl) {
        statusEl.textContent = student.status || 'ไม่ระบุ';
    }
    
    // อัปเดตข้อมูลการเข้าแถว
    const attendanceDaysEl = modal.querySelector('#view_attendance_days');
    if (attendanceDaysEl) {
        attendanceDaysEl.textContent = student.attendance_days || 0;
    }
    
    const absenceDaysEl = modal.querySelector('#view_absence_days');
    if (absenceDaysEl) {
        absenceDaysEl.textContent = student.absence_days || 0;
    }
    
    const totalDaysEl = modal.querySelector('#view_total_days');
    if (totalDaysEl) {
        totalDaysEl.textContent = (parseInt(student.attendance_days || 0) + parseInt(student.absence_days || 0));
    }
    
    // อัปเดตแถบความคืบหน้าการเข้าแถว
    const attendanceRateTextEl = modal.querySelector('#view_attendance_rate_text');
    if (attendanceRateTextEl) {
        attendanceRateTextEl.textContent = `${student.attendance_rate.toFixed(1)}%`;
    }
    
    const attendanceProgressEl = modal.querySelector('#view_attendance_progress');
    if (attendanceProgressEl) {
        attendanceProgressEl.style.width = `${student.attendance_rate}%`;
        
        if (student.attendance_rate < 60) {
            attendanceProgressEl.className = 'progress-bar bg-danger';
        } else if (student.attendance_rate < 75) {
            attendanceProgressEl.className = 'progress-bar bg-warning';
        } else {
            attendanceProgressEl.className = 'progress-bar bg-success';
        }
    }
    
    const attendanceStatusEl = modal.querySelector('#view_attendance_status');
    if (attendanceStatusEl) {
        let statusClass = '';
        if (student.attendance_status === 'เสี่ยงตกกิจกรรม') {
            statusClass = 'bg-danger';
        } else if (student.attendance_status === 'ต้องระวัง') {
            statusClass = 'bg-warning text-dark';
        } else {
            statusClass = 'bg-success';
        }
        
        attendanceStatusEl.innerHTML = `<span class="badge ${statusClass}">${student.attendance_status || 'ไม่มีข้อมูล'}</span>`;
    }
    
    // ซ่อน/แสดงปุ่มสร้าง QR LINE
    const generateQrBtn = modal.querySelector('#generate_qr_btn');
    if (generateQrBtn) {
        generateQrBtn.style.display = student.line_connected ? 'none' : 'inline-flex';
    }
}

/**
 * แสดงโมดัลลบข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function deleteStudent(studentId, studentName) {
    // เซ็ตค่า ID ที่จะลบ
    document.getElementById('delete_student_id').value = studentId;
    
    // แสดงชื่อนักเรียนในหน้ายืนยันการลบ
    document.getElementById('delete_student_name').textContent = studentName;
    
    // แสดงโมดัล
    const modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
    modal.show();
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
    
    // รีเซ็ตหน้าตาการอัปโหลด
    const selectedFile = document.querySelector('.selected-file');
    const uploadArea = document.querySelector('.upload-area');
    const importBtn = document.getElementById('importBtn');
    
    if (selectedFile && uploadArea && importBtn) {
        selectedFile.classList.add('d-none');
        uploadArea.classList.remove('d-none');
        importBtn.disabled = true;
    }
    
    // แสดงโมดัล
    const modal = new bootstrap.Modal(document.getElementById('importModal'));
    modal.show();
}

/**
 * ตรวจสอบฟอร์มนำเข้าข้อมูล
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validateImportForm(form) {
    const fileInput = form.querySelector('input[type="file"]');
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showAlert('warning', 'แจ้งเตือน', 'กรุณาเลือกไฟล์ Excel หรือ CSV');
        return false;
    }
    
    const file = fileInput.files[0];
    const allowedExtensions = ['xlsx', 'xls', 'csv'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedExtensions.includes(fileExtension)) {
        showAlert('warning', 'แจ้งเตือน', 'รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) และ CSV เท่านั้น');
        return false;
    }
    
    return true;
}

/**
 * ดาวน์โหลดเทมเพลตสำหรับนำเข้าข้อมูลนักเรียน
 */
function downloadTemplate() {
    const timestamp = new Date().getTime();
    const url = `api/download_template.php?type=students&_nocache=${timestamp}`;
    window.open(url, '_blank');
}

/**
 * สร้าง QR Code สำหรับเชื่อมต่อ LINE
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function generateLineQR(studentId) {
    // แสดงการโหลด
    showLoading('กำลังสร้าง QR Code...');
    
    // เตรียมข้อมูลสำหรับส่งไปยัง API
    const formData = new FormData();
    formData.append('action', 'generate_qr_code');
    formData.append('student_id', studentId);
    
    // ส่งข้อมูลไปยัง API
    fetch('api/line_connect_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // เก็บข้อมูลนักเรียนที่กำลังเชื่อมต่อ
            window.currentConnectingStudent = {
                id: studentId
            };
            
            // แสดง QR Code ในโมดัล
            const qrImage = document.getElementById('qrcode-image');
            qrImage.src = data.qr_code_url;
            
            // เซ็ต URL สำหรับการเชื่อมต่อ
            const lineConnectUrl = document.getElementById('line-connect-url');
            lineConnectUrl.textContent = data.line_connect_url;
            lineConnectUrl.href = data.line_connect_url;
            
            // แสดงโมดัล
            const modal = new bootstrap.Modal(document.getElementById('lineQRModal'));
            modal.show();
        } else {
            showAlert('danger', 'ข้อผิดพลาด', data.message);
        }
    })
    .catch(error => {
        console.error('Error generating QR Code:', error);
        hideLoading();
        showAlert('danger', 'ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการสร้าง QR Code');
    });
}

/**
 * ตรวจสอบสถานะการเชื่อมต่อ LINE
 */
function checkLineStatus() {
    // ตรวจสอบว่ามีข้อมูลนักเรียนที่กำลังเชื่อมต่อหรือไม่
    if (!window.currentConnectingStudent) {
        showAlert('warning', 'แจ้งเตือน', 'ไม่พบข้อมูลนักเรียนที่กำลังเชื่อมต่อ');
        return;
    }
    
    // แสดงการโหลด
    showLoading('กำลังตรวจสอบสถานะการเชื่อมต่อ...');
    
    // เตรียมข้อมูลสำหรับส่งไปยัง API
    const formData = new FormData();
    formData.append('action', 'check_line_status');
    formData.append('student_id', window.currentConnectingStudent.id);
    
    // ส่งข้อมูลไปยัง API เพื่อตรวจสอบสถานะ
    fetch('api/line_connect_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            if (data.is_connected) {
                showAlert('success', 'สำเร็จ', 'เชื่อมต่อ LINE สำเร็จแล้ว');
                
                // ปิดโมดัล QR Code
                const qrModal = bootstrap.Modal.getInstance(document.getElementById('lineQRModal'));
                qrModal.hide();
                
                // รีโหลดหน้าหลังจาก 2 วินาที แบบไม่ใช้ cache
                setTimeout(() => {
                    const timestamp = new Date().getTime();
                    window.location.href = window.location.pathname + (window.location.search || '?') + '&_=' + timestamp;
                }, 2000);
            } else {
                showAlert('warning', 'แจ้งเตือน', 'ยังไม่ได้เชื่อมต่อ LINE กรุณาให้นักเรียนสแกน QR Code');
            }
        } else {
            showAlert('danger', 'ข้อผิดพลาด', data.message);
        }
    })
    .catch(error => {
        console.error('Error checking LINE status:', error);
        hideLoading();
        showAlert('danger', 'ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ LINE');
    });
}

/**
 * พิมพ์รายชื่อนักเรียน
 */
function printStudentList() {
    // สร้างสไตล์สำหรับการพิมพ์
    const style = `
        <style>
            @media print {
                @page { size: landscape; }
                body { font-family: 'Sarabun', sans-serif; font-size: 14px; }
                h1, h2 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .text-center { text-align: center; }
                .no-print { display: none; }
                .badge {
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: normal;
                }
                .bg-success { background-color: #d1e7dd; color: #0f5132; }
                .bg-warning { background-color: #fff3cd; color: #664d03; }
                .bg-danger { background-color: #f8d7da; color: #842029; }
            }
        </style>
    `;
    
    // ดึงข้อมูลจากตาราง
    const table = document.querySelector('#students-table');
    let rows = [];
    
    if (dataTable) {
        // กรณีใช้ DataTables ดึงข้อมูลจาก DataTables API
        rows = dataTable.rows({ search: 'applied' }).data();
    } else if (table) {
        // กรณีไม่ใช้ DataTables ดึงข้อมูลจาก DOM
        rows = Array.from(table.querySelectorAll('tbody tr'));
    }
    
    // สร้างเนื้อหาสำหรับการพิมพ์
    let content = `
        <div class="print-header">
            <h1>รายชื่อนักเรียน</h1>
            <h3>วิทยาลัยการอาชีพปราสาท</h3>
            <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสนักศึกษา</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>ชั้น/ห้อง</th>
                    <th>แผนกวิชา</th>
                    <th>การเข้าแถว</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // สร้างเนื้อหาตาราง
    if (dataTable) {
        // กรณีใช้ DataTables
        let index = 1;
        for (let i = 0; i < rows.length; i++) {
            const rowData = rows[i];
            const studentName = getTextFromHTML(rowData[1]); // คอลัมน์ชื่อ-นามสกุล
            const classRoom = rowData[2];
            const department = rowData[3];
            const attendance = getAttendancePercent(rowData[5]); // คอลัมน์การเข้าแถว
            const statusText = getStatusText(rowData[6]); // คอลัมน์สถานะ
            const statusClass = getStatusClass(rowData[6]); // คลาสของสถานะ
            
            content += `
                <tr>
                    <td class="text-center">${index}</td>
                    <td>${rowData[0]}</td>
                    <td>${studentName}</td>
                    <td>${classRoom}</td>
                    <td>${department}</td>
                    <td class="text-center">${attendance}%</td>
                    <td class="text-center"><span class="badge ${statusClass}">${statusText}</span></td>
                </tr>
            `;
            index++;
        }
    } else if (rows.length > 0) {
        // กรณีไม่ใช้ DataTables
        rows.forEach((row, index) => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 7) return; // ข้ามถ้าไม่มีข้อมูลครบ
            
            const studentCode = cells[0].textContent.trim();
            const studentName = cells[1].querySelector('.student-name')?.textContent.trim() || '';
            const classRoom = cells[2].textContent.trim();
            const department = cells[3].textContent.trim();
            const attendance = cells[5].textContent.trim();
            const status = cells[6].querySelector('.badge')?.textContent.trim() || '';
            const statusClass = cells[6].querySelector('.badge')?.classList.contains('bg-danger') ? 'bg-danger' :
                              cells[6].querySelector('.badge')?.classList.contains('bg-warning') ? 'bg-warning' : 'bg-success';
            
            content += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${studentCode}</td>
                    <td>${studentName}</td>
                    <td>${classRoom}</td>
                    <td>${department}</td>
                    <td class="text-center">${attendance}</td>
                    <td class="text-center"><span class="badge ${statusClass}">${status}</span></td>
                </tr>
            `;
        });
    }
    
    content += `
            </tbody>
        </table>
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()">พิมพ์</button>
            <button onclick="window.close()">ปิด</button>
        </div>
    `;
    
    // เปิดหน้าต่างใหม่สำหรับการพิมพ์
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>รายชื่อนักเรียน</title>
                <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
                ${style}
            </head>
            <body>
                ${content}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 500);
                    }
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

/**
 * ดึงข้อความจาก HTML
 * 
 * @param {string} html - HTML ที่ต้องการดึงข้อความ
 * @returns {string} - ข้อความที่ดึงได้
 */
function getTextFromHTML(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}

/**
 * ดึงเปอร์เซ็นต์การเข้าแถวจาก HTML
 * 
 * @param {string} html - HTML ที่ต้องการดึงเปอร์เซ็นต์
 * @returns {string} - เปอร์เซ็นต์ที่ดึงได้
 */
function getAttendancePercent(html) {
    // ดึงเปอร์เซ็นต์จาก progress bar
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '0';
}

/**
 * ดึงข้อความสถานะจาก HTML
 * 
 * @param {string} html - HTML ที่ต้องการดึงข้อความสถานะ
 * @returns {string} - ข้อความสถานะที่ดึงได้
 */
function getStatusText(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    const badge = div.querySelector('.badge');
    return badge ? badge.textContent.trim() : '';
}

/**
 * ดึงคลาสของสถานะจาก HTML
 * 
 * @param {string} html - HTML ที่ต้องการดึงคลาสของสถานะ
 * @returns {string} - คลาสของสถานะที่ดึงได้
 */
function getStatusClass(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    const badge = div.querySelector('.badge');
    if (!badge) return '';
    
    if (badge.classList.contains('bg-danger')) {
        return 'bg-danger';
    } else if (badge.classList.contains('bg-warning')) {
        return 'bg-warning';
    } else {
        return 'bg-success';
    }
}

/**
 * ดาวน์โหลดรายชื่อนักเรียนเป็นไฟล์ Excel
 */
function downloadExcel() {
    // แสดงการโหลด
    showLoading('กำลังเตรียมการดาวน์โหลด Excel...');
    
    // สร้าง query string จากตัวกรอง
    const params = new URLSearchParams(window.location.search);
    const timestamp = new Date().getTime();
    params.append('_nocache', timestamp);
    
    const downloadUrl = `api/export_students.php?${params.toString()}`;
    
    // สร้าง link สำหรับดาวน์โหลดและคลิกโดยอัตโนมัติ
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', 'รายชื่อนักเรียน.xlsx');
    document.body.appendChild(link);
    
    // สร้าง event listener เพื่อให้ทำงานหลังจากที่ link ถูกคลิก
    link.addEventListener('click', function() {
        setTimeout(() => {
            hideLoading();
            document.body.removeChild(link);
        }, 1000);
    });
    
    link.click();
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validateForm(form) {
    // ล้าง class is-invalid ทั้งหมด
    const invalidFields = form.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
    
    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
            
            // ถ้ายังไม่มีข้อความแสดงข้อผิดพลาด ให้สร้างใหม่
            let feedbackEl = field.parentNode.querySelector('.invalid-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.className = 'invalid-feedback';
                feedbackEl.textContent = 'กรุณากรอกข้อมูลในช่องนี้';
                field.parentNode.appendChild(feedbackEl);
            }
        }
    });
    
    // ตรวจสอบรหัสนักเรียน
    const studentCodeField = form.querySelector('[name="student_code"]');
    if (studentCodeField && studentCodeField.value.trim()) {
        const studentCode = studentCodeField.value.trim();
        
        // ตรวจสอบว่ารหัสนักเรียนต้องเป็นตัวเลขเท่านั้น
        if (!/^\d+$/.test(studentCode)) {
            studentCodeField.classList.add('is-invalid');
            isValid = false;
            
            let feedbackEl = studentCodeField.parentNode.querySelector('.invalid-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.className = 'invalid-feedback';
                field.parentNode.appendChild(feedbackEl);
            }
            feedbackEl.textContent = 'รหัสนักเรียนต้องเป็นตัวเลขเท่านั้น';
        }
    }
    
    // ตรวจสอบอีเมล (ถ้ามี)
    const emailField = form.querySelector('[name="email"]');
    if (emailField && emailField.value.trim()) {
        const email = emailField.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(email)) {
            emailField.classList.add('is-invalid');
            isValid = false;
            
            let feedbackEl = emailField.parentNode.querySelector('.invalid-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.className = 'invalid-feedback';
                emailField.parentNode.appendChild(feedbackEl);
            }
            feedbackEl.textContent = 'รูปแบบอีเมลไม่ถูกต้อง';
        }
    }
    
    // ตรวจสอบเบอร์โทรศัพท์ (ถ้ามี)
    const phoneField = form.querySelector('[name="phone_number"]');
    if (phoneField && phoneField.value.trim()) {
        const phone = phoneField.value.trim().replace(/[- ]/g, '');
        
        if (!/^\d{9,10}$/.test(phone)) {
            phoneField.classList.add('is-invalid');
            isValid = false;
            
            let feedbackEl = phoneField.parentNode.querySelector('.invalid-feedback');
            if (!feedbackEl) {
                feedbackEl = document.createElement('div');
                feedbackEl.className = 'invalid-feedback';
                phoneField.parentNode.appendChild(feedbackEl);
            }
            feedbackEl.textContent = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        }
    }
    
    if (!isValid) {
        showAlert('warning', 'แจ้งเตือน', 'กรุณากรอกข้อมูลให้ถูกต้องและครบถ้วน');
    }
    
    return isValid;
}

/**
 * ตั้งค่าการตรวจสอบฟอร์มแบบ real-time
 */
function setupFormValidation() {
    // ตั้งค่า event listener สำหรับทุกฟอร์ม
    document.querySelectorAll('form').forEach(form => {
        // รายการฟิลด์ที่ต้องการตรวจสอบ
        const fieldsToValidate = form.querySelectorAll('input, select, textarea');
        
        fieldsToValidate.forEach(field => {
            // เมื่อออกจากฟิลด์
            field.addEventListener('blur', function() {
                validateField(this);
            });
            
            // เมื่อมีการพิมพ์/เปลี่ยนแปลง
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                
                // ลบข้อความแสดงข้อผิดพลาดหากมี
                const feedbackElement = this.parentNode.querySelector('.invalid-feedback');
                if (feedbackElement) {
                    feedbackElement.remove();
                }
            });
        });
    });
}

/**
 * ตรวจสอบความถูกต้องของฟิลด์
 * 
 * @param {HTMLElement} field - ฟิลด์ที่ต้องการตรวจสอบ
 */
function validateField(field) {
    // ลบสถานะไม่ถูกต้อง
    field.classList.remove('is-invalid');
    
    // ลบข้อความแสดงข้อผิดพลาด
    const feedbackEl = field.parentNode.querySelector('.invalid-feedback');
    if (feedbackEl) {
        feedbackEl.remove();
    }
    
    let errorMessage = null;
    
    // ตรวจสอบว่าเป็นฟิลด์ที่จำเป็นหรือไม่
    if (field.hasAttribute('required') && !field.value.trim()) {
        errorMessage = 'กรุณากรอกข้อมูลในช่องนี้';
    } else if (field.value.trim()) {
        // ตรวจสอบตามประเภทของฟิลด์
        switch (field.name) {
            case 'student_code':
                if (!/^\d+$/.test(field.value.trim())) {
                    errorMessage = 'รหัสนักเรียนต้องเป็นตัวเลขเท่านั้น';
                }
                break;
                
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim())) {
                    errorMessage = 'รูปแบบอีเมลไม่ถูกต้อง';
                }
                break;
                
            case 'phone_number':
                const phone = field.value.trim().replace(/[- ]/g, '');
                if (!/^\d{9,10}$/.test(phone)) {
                    errorMessage = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
                }
                break;
        }
    }
    
    // แสดงข้อความผิดพลาด
    if (errorMessage) {
        field.classList.add('is-invalid');
        
        const newFeedbackEl = document.createElement('div');
        newFeedbackEl.className = 'invalid-feedback';
        newFeedbackEl.textContent = errorMessage;
        field.parentNode.appendChild(newFeedbackEl);
    }
}

/**
 * ส่งฟอร์ม
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการส่ง
 */
function submitForm(form) {
    // ปิดใช้งานปุ่มส่งฟอร์ม
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        
        // เพิ่มคลาสโหลดเพื่อแสดง spinner
        submitButton.classList.add('btn-loading');
        
        // บันทึกข้อความเดิมไว้
        submitButton.dataset.originalText = submitButton.innerHTML;
        
        // แสดง spinner
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังดำเนินการ...';
    }
    
    // เซ็ต flag เพื่อป้องกันการส่งซ้ำ
    form.classList.add('form-submitted');
    
    // ส่งฟอร์ม
    form.submit();
}

/**
 * เซ็ตค่า select
 * 
 * @param {HTMLSelectElement} selectElement - Element select
 * @param {string|number} value - ค่าที่ต้องการเลือก
 */
function setSelectValue(selectElement, value) {
    if (!selectElement) return;
    
    const options = selectElement.options;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value == value) {
            selectElement.selectedIndex = i;
            break;
        }
    }
}

/**
 * แสดงการโหลด
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 */
function showLoading(message = 'กำลังดำเนินการ...') {
    // ตรวจสอบว่ามี loading overlay อยู่แล้วหรือไม่
    let loadingOverlay = document.getElementById('loading-overlay');
    
    if (!loadingOverlay) {
        // สร้าง loading overlay ใหม่
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loading-overlay';
        loadingOverlay.classList.add('loading-overlay');
        
        const loadingContent = document.createElement('div');
        loadingContent.classList.add('loading-content');
        
        const spinner = document.createElement('div');
        spinner.classList.add('spinner-border', 'text-primary');
        spinner.setAttribute('role', 'status');
        
        const loadingMessage = document.createElement('div');
        loadingMessage.id = 'loading-message';
        loadingMessage.classList.add('loading-message');
        loadingMessage.textContent = message;
        
        loadingContent.appendChild(spinner);
        loadingContent.appendChild(loadingMessage);
        loadingOverlay.appendChild(loadingContent);
        
        document.body.appendChild(loadingOverlay);
    } else {
        // อัปเดตข้อความ
        const loadingMessage = document.getElementById('loading-message');
        if (loadingMessage) {
            loadingMessage.textContent = message;
        }
        
        // แสดง loading overlay
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * ซ่อนการโหลด
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

/**
 * แสดงการแจ้งเตือน
 * 
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 * @param {string} title - หัวข้อการแจ้งเตือน
 * @param {string} message - ข้อความที่ต้องการแสดง
 */
function showAlert(type, title, message) {
    // สร้าง container สำหรับ toasts
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.classList.add('toast-container', 'position-fixed', 'bottom-0', 'end-0', 'p-3');
        document.body.appendChild(toastContainer);
    }
    
    // กำหนดไอคอนตามประเภท
    let iconName;
    switch (type) {
        case 'success':
            iconName = 'check_circle';
            break;
        case 'info':
            iconName = 'info';
            break;
        case 'warning':
            iconName = 'warning';
            break;
        case 'danger':
            iconName = 'error';
            break;
        default:
            iconName = 'notifications';
    }
    
    // สร้าง toast
    const toastId = 'toast-' + Date.now();
    const toastEl = document.createElement('div');
    toastEl.id = toastId;
    toastEl.classList.add('toast', 'align-items-center', 'text-white', `bg-${type}`, 'border-0');
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="material-icons">${iconName}</i>
                <strong>${title}:</strong> ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // เพิ่ม toast ไปยัง container
    toastContainer.appendChild(toastEl);
    
    // แสดง toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    // ลบ toast หลังจากซ่อน
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}
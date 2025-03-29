/**
 * students.js - จาวาสคริปต์สำหรับหน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    initEventListeners();
    
    // ตั้งค่าการตรวจสอบฟอร์มแบบ real-time
    setupFormValidation();
});

/**
 * ตั้งค่า event listeners
 */
function initEventListeners() {
    // Event listener สำหรับการค้นหา
    const searchForm = document.querySelector('.filter-container form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // ไม่ต้องยกเลิกเหตุการณ์เพราะเราต้องการให้ฟอร์มทำการส่งข้อมูลปกติ
        });
    }
    
    // Event listener สำหรับฟอร์มเพิ่มนักเรียน
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // ตรวจสอบความถูกต้องของฟอร์ม
            if (validateStudentForm(this)) {
                this.submit(); // ส่งฟอร์มไปยัง server
            }
        });
    }
    
    // Event listener สำหรับฟอร์มแก้ไขนักเรียน
    const editForm = document.getElementById('editStudentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // ตรวจสอบความถูกต้องของฟอร์ม
            if (validateStudentForm(this)) {
                this.submit(); // ส่งฟอร์มไปยัง server
            }
        });
    }
    
    // Event listener สำหรับฟอร์มลบนักเรียน
    const deleteForm = document.getElementById('deleteStudentForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            // ไม่ต้องยกเลิกเหตุการณ์ เพราะเราต้องการให้ฟอร์มทำการส่งข้อมูลปกติ
        });
    }
    
    // Event listener สำหรับฟอร์มนำเข้าข้อมูล
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // ตรวจสอบว่ามีการเลือกไฟล์หรือไม่
            const fileInput = this.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('กรุณาเลือกไฟล์สำหรับนำเข้าข้อมูล', 'warning');
                return;
            }
            this.submit(); // ส่งฟอร์มไปยัง server
        });
    }
}


/**
 * แก้ไขฟังก์ชันในไฟล์ assets/js/students.js
 * โดยเพิ่มการรีโหลดตาราง DataTables หลังจากการเพิ่ม/แก้ไข/ลบข้อมูล
 */

// แก้ไขฟังก์ชันเพิ่มนักเรียน
function addStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('addStudentForm');
    
    // ล้างการแจ้งเตือนเดิม
    hideAlert();
    
    // ตรวจสอบความถูกต้องของข้อมูล
    const validation = validateStudentForm(form);
    
    if (!validation.isValid) {
        showAlert(validation.errorMessages.join('<br>'), 'warning', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    // ปิดการใช้งานปุ่มบันทึก
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData(form);
    
    // เพิ่ม action และ temp_line_id
    formData.append('action', 'add_student');
    const studentCode = formData.get('student_code');
    const tempLineId = `TEMP_${studentCode}_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    formData.append('temp_line_id', tempLineId);
    
    // แสดงการโหลด
    showAlert('กำลังเพิ่มข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store' // ป้องกันการใช้ cache
    })
    .then(response => response.json())
    .then(data => {
        hideAlert();
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('addStudentModal');
            
            // รีโหลดหน้าแบบไม่ใช้ cache
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding student:', error);
        hideAlert();
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการเพิ่มข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันอัปเดตข้อมูลนักเรียน
function updateStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('editStudentForm');
    const formData = new FormData(form);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!formData.get('student_code') || !formData.get('firstname') || !formData.get('lastname')) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // ปิดการใช้งานปุ่มบันทึก
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'update_student');
    
    // แสดงการโหลด
    showAlert('กำลังอัปเดตข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('editStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating student:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการอัปเดตข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันลบนักเรียน
function deleteStudentConfirm() {
    // ดึงรหัสนักเรียนที่จะลบ
    const studentId = document.getElementById('delete_student_id').value;
    
    // ปิดการใช้งานปุ่ม
    const submitBtn = document.querySelector('#deleteStudentForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData();
    formData.append('action', 'delete_student');
    formData.append('student_id', studentId);
    
    // แสดงการโหลด
    showAlert('กำลังลบข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('deleteStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting student:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันนำเข้าข้อมูลนักเรียน
function importStudents() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    // ตรวจสอบว่ามีการเลือกไฟล์หรือไม่
    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('กรุณาเลือกไฟล์ Excel', 'warning');
        return;
    }
    
    // ปิดการใช้งานปุ่ม
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'import_students');
    
    // แสดงการโหลด
    showAlert('กำลังนำเข้าข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('importModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error importing students:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการนำเข้าข้อมูลนักเรียน', 'danger');
    });
}









/**
 * แสดงโมดัลเพิ่มนักเรียนใหม่
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
 * แสดงโมดัลแก้ไขข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function editStudent(studentId) {
    // แสดงโมดัลรอดำเนินการ
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // ดึงข้อมูลนักเรียนจาก API
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // เติมข้อมูลในฟอร์มแก้ไข
                fillEditForm(data.student);
                
                // ซ่อนการแจ้งเตือน
                hideAlert();
                
                // แสดงโมดัล
                showModal('editStudentModal');
            } else {
                // แสดงข้อความผิดพลาด
                showAlert(data.message || 'ไม่สามารถโหลดข้อมูลนักเรียนได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน', 'error');
        });
}

/**
 * เติมข้อมูลในฟอร์มแก้ไข
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function fillEditForm(student) {
    const form = document.getElementById('editStudentForm');
    
    // กรอกข้อมูลพื้นฐาน
    form.querySelector('#edit_student_id').value = student.student_id;
    form.querySelector('#edit_title').value = student.title;
    form.querySelector('#edit_firstname').value = student.first_name;
    form.querySelector('#edit_lastname').value = student.last_name;
    form.querySelector('#edit_student_code').value = student.student_code;
    
    // กรอกข้อมูลติดต่อ (ถ้ามี)
    if (form.querySelector('#edit_phone_number')) {
        form.querySelector('#edit_phone_number').value = student.phone_number || '';
    }
    if (form.querySelector('#edit_email')) {
        form.querySelector('#edit_email').value = student.email || '';
    }
    
    // กรอกข้อมูลการศึกษา
    if (form.querySelector('#edit_class_id')) {
        form.querySelector('#edit_class_id').value = student.class_id || '';
    }
    if (form.querySelector('#edit_status')) {
        form.querySelector('#edit_status').value = student.status || 'กำลังศึกษา';
    }
}

/**
 * แสดงโมดัลดูข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function viewStudent(studentId) {
    // แสดงโมดัลรอดำเนินการ
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // ดึงข้อมูลนักเรียนจาก API
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // อัปเดตข้อมูลในโมดัล
                updateViewModal(data.student);
                
                // ซ่อนการแจ้งเตือน
                hideAlert();
                
                // แสดงโมดัล
                showModal('viewStudentModal');
            } else {
                // แสดงข้อความผิดพลาด
                showAlert(data.message || 'ไม่สามารถโหลดข้อมูลนักเรียนได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน', 'error');
        });
}

/**
 * อัปเดตข้อมูลในโมดัลดูรายละเอียดนักเรียน
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function updateViewModal(student) {
    // อัปเดตอวาตาร์
    const avatar = document.getElementById('view_avatar');
    if (avatar) {
        avatar.textContent = student.first_name.charAt(0);
    }
    
    // อัปเดตข้อมูลพื้นฐาน
    document.getElementById('view_full_name').textContent = student.title + student.first_name + ' ' + student.last_name;
    document.getElementById('view_student_code').textContent = 'รหัสนักศึกษา: ' + student.student_code;
    document.getElementById('view_class').textContent = student.class || 'ไม่ระบุชั้นเรียน';
    
    // อัปเดตข้อมูลติดต่อ
    document.querySelector('#view_phone span').textContent = student.phone_number || 'ไม่ระบุ';
    document.querySelector('#view_email span').textContent = student.email || 'ไม่ระบุ';
    document.querySelector('#view_line span').textContent = student.line_connected ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้เชื่อมต่อ';
    
    // อัปเดตข้อมูลการศึกษา
    document.querySelector('#view_advisor span').textContent = student.advisor_name || 'ไม่ระบุ';
    document.querySelector('#view_department span').textContent = student.department_name || 'ไม่ระบุ';
    document.querySelector('#view_status span').textContent = student.status || 'ไม่ระบุ';
    
    // อัปเดตข้อมูลการเข้าแถว
    const attendanceStatusEl = document.querySelector('#view_attendance_status span');
    let statusClass = '';
    
    if (student.attendance_status === 'เสี่ยงตกกิจกรรม') {
        statusClass = 'danger';
    } else if (student.attendance_status === 'ต้องระวัง') {
        statusClass = 'warning';
    } else {
        statusClass = 'success';
    }
    
    attendanceStatusEl.className = `status-badge ${statusClass}`;
    attendanceStatusEl.textContent = student.attendance_status || 'ไม่มีข้อมูล';
    
    // อัปเดตสถิติการเข้าแถว
    document.getElementById('view_attendance_days').textContent = student.attendance_days || 0;
    document.getElementById('view_absence_days').textContent = student.absence_days || 0;
    document.getElementById('view_attendance_rate').textContent = student.attendance_rate ? student.attendance_rate.toFixed(1) + '%' : '0%';
    
    // อัปเดตปุ่มดำเนินการ
    document.getElementById('edit_btn').setAttribute('onclick', `closeModal('viewStudentModal'); editStudent(${student.student_id});`);
    
    const generateQrBtn = document.getElementById('generate_qr_btn');
    if (student.line_connected) {
        generateQrBtn.style.display = 'none';
    } else {
        generateQrBtn.style.display = 'inline-flex';
        generateQrBtn.setAttribute('onclick', `closeModal('viewStudentModal'); generateLineQR(${student.student_id});`);
    }
}

/**
 * แสดงโมดัลลบข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function deleteStudent(studentId, studentName) {
    // เซ็ตค่า ID และชื่อนักเรียนที่จะลบ
    document.getElementById('delete_student_id').value = studentId;
    document.getElementById('delete_student_name').textContent = studentName;
    
    // แสดงโมดัล
    showModal('deleteStudentModal');
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
 * พิมพ์รายชื่อนักเรียน
 */
function printStudentList() {
    // สร้างสไตล์สำหรับการพิมพ์
    const style = `
        <style>
            @media print {
                body { font-family: 'Sarabun', sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h1, h2 { text-align: center; }
                .no-print { display: none; }
            }
        </style>
    `;
    
    // สร้างเนื้อหาสำหรับการพิมพ์
    let content = `
        <h1>รายชื่อนักเรียน</h1>
        <h2>วิทยาลัยการอาชีพปราสาท</h2>
        <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
        <table>
            <thead>
                <tr>
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
    
    // ดึงข้อมูลจากตาราง
    const table = document.querySelector('.data-table');
    if (!table) {
        showAlert('ไม่พบข้อมูลสำหรับพิมพ์', 'warning');
        return;
    }
    
    const rows = table.querySelectorAll('tbody tr');
    
    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        showAlert('ไม่มีข้อมูลสำหรับพิมพ์', 'warning');
        return;
    }
    
    rows.forEach(row => {
        // ตรวจสอบว่าเป็นแถวข้อมูลจริงหรือไม่ (ไม่ใช่แถว "ไม่พบข้อมูล")
        if (!row.querySelector('td[colspan]')) {
            const id = row.querySelector('td:nth-child(1)').textContent;
            const name = row.querySelector('.student-name').textContent;
            const classRoom = row.querySelector('td:nth-child(3)').textContent;
            const department = row.querySelector('td:nth-child(4)').textContent;
            const attendance = row.querySelector('td:nth-child(6)').textContent;
            const status = row.querySelector('.status-badge').textContent;
            
            content += `
                <tr>
                    <td>${id}</td>
                    <td>${name}</td>
                    <td>${classRoom}</td>
                    <td>${department}</td>
                    <td>${attendance}</td>
                    <td>${status}</td>
                </tr>
            `;
        }
    });
    
    content += `
            </tbody>
        </table>
        <div class="no-print">
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
                        // พิมพ์อัตโนมัติเมื่อโหลดเสร็จ
                        window.print();
                    }
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

/**
 * ดาวน์โหลด Excel
 */
function downloadExcel() {
    // สร้าง URL สำหรับดาวน์โหลด
    let url = 'api/export_students.php';
    
    // ถ้ามีการกรองข้อมูล ให้ส่งพารามิเตอร์ไปด้วย
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString()) {
        url += '?' + urlParams.toString();
    }
    
    // เปิดหน้าต่างดาวน์โหลดหรือเปิดลิงก์ในแท็บใหม่
    window.open(url, '_blank');
}

/**
 * สร้าง QR Code สำหรับเชื่อมต่อ LINE
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function generateLineQR(studentId) {
    // แสดงการโหลด
    showAlert('กำลังสร้าง QR Code...', 'info');
    
    // เรียกใช้ API สร้าง QR Code
    fetch('api/line_connect_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=generate_qr_code&student_id=${studentId}`
    })
    .then(response => response.json())
    .then(data => {
        // ซ่อนการแจ้งเตือน
        hideAlert();
        
        if (data.success) {
            // เก็บรหัสนักเรียนที่กำลังเชื่อมต่อ
            window.currentConnectingStudent = {
                id: studentId
            };
            
            // อัปเดต QR Code ในโมดัล
            document.getElementById('qrcode-image').src = data.qr_code_url;
            
            // อัปเดตลิงก์เชื่อมต่อ
            const lineConnectUrl = document.getElementById('line-connect-url');
            lineConnectUrl.textContent = data.line_connect_url;
            lineConnectUrl.href = data.line_connect_url;
            
            // แสดงโมดัล
            showModal('lineQRModal');
        } else {
            showAlert(data.message || 'ไม่สามารถสร้าง QR Code ได้', 'error');
        }
    })
    .catch(error => {
        console.error('Error generating QR code:', error);
        hideAlert();
        showAlert('เกิดข้อผิดพลาดในการสร้าง QR Code', 'error');
    });
}

/**
 * ตรวจสอบสถานะการเชื่อมต่อ LINE
 */
function checkLineStatus() {
    // ตรวจสอบว่ามีข้อมูลนักเรียนที่กำลังเชื่อมต่อหรือไม่
    if (!window.currentConnectingStudent) {
        showAlert('ไม่พบข้อมูลนักเรียนที่กำลังเชื่อมต่อ', 'warning');
        return;
    }
    
    // แสดงการโหลด
    showAlert('กำลังตรวจสอบสถานะการเชื่อมต่อ...', 'info');
    
    // เรียกใช้ API ตรวจสอบสถานะ
    fetch('api/line_connect_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_line_status&student_id=${window.currentConnectingStudent.id}`
    })
    .then(response => response.json())
    .then(data => {
        // ซ่อนการแจ้งเตือน
        hideAlert();
        
        if (data.success) {
            if (data.is_connected) {
                showAlert('เชื่อมต่อ LINE สำเร็จแล้ว', 'success');
                closeModal('lineQRModal');
                
                // รีโหลดหน้าเพื่อแสดงสถานะล่าสุด
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert('ยังไม่ได้เชื่อมต่อ LINE กรุณาให้นักเรียนสแกน QR Code', 'warning');
            }
        } else {
            showAlert(data.message || 'ไม่สามารถตรวจสอบสถานะได้', 'error');
        }
    })
    .catch(error => {
        console.error('Error checking LINE status:', error);
        hideAlert();
        showAlert('เกิดข้อผิดพลาดในการตรวจสอบสถานะ', 'error');
    });
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 * 
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @returns {boolean} true ถ้าข้อมูลถูกต้อง
 */
function validateStudentForm(form) {
    let isValid = true;
    
    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = ['student_code', 'title', 'firstname', 'lastname', 'status'];
    
    requiredFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input && !input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            
            // สร้างข้อความแสดงข้อผิดพลาด
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'กรุณากรอกข้อมูลในช่องนี้';
            
            // เพิ่มข้อความแสดงข้อผิดพลาดถ้ายังไม่มี
            if (!input.parentNode.querySelector('.invalid-feedback')) {
                input.parentNode.appendChild(feedback);
            }
        } else if (input) {
            input.classList.remove('is-invalid');
            
            // ลบข้อความแสดงข้อผิดพลาดถ้ามี
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.remove();
            }
        }
    });
    
    // ตรวจสอบรูปแบบรหัสนักเรียน (ตัวเลขเท่านั้น)
    const studentCodeInput = form.querySelector('[name="student_code"]');
    if (studentCodeInput && studentCodeInput.value.trim() && !/^\d+$/.test(studentCodeInput.value.trim())) {
        isValid = false;
        studentCodeInput.classList.add('is-invalid');
        
        // สร้างข้อความแสดงข้อผิดพลาด
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'รหัสนักเรียนต้องเป็นตัวเลขเท่านั้น';
        
        // เพิ่มข้อความแสดงข้อผิดพลาดถ้ายังไม่มี
        if (!studentCodeInput.parentNode.querySelector('.invalid-feedback')) {
            studentCodeInput.parentNode.appendChild(feedback);
        } else {
            // อัปเดตข้อความแสดงข้อผิดพลาด
            studentCodeInput.parentNode.querySelector('.invalid-feedback').textContent = 'รหัสนักเรียนต้องเป็นตัวเลขเท่านั้น';
        }
    }
    
    // ตรวจสอบรูปแบบอีเมล
    const emailInput = form.querySelector('[name="email"]');
    if (emailInput && emailInput.value.trim() && !isValidEmail(emailInput.value.trim())) {
        isValid = false;
        emailInput.classList.add('is-invalid');
        
        // สร้างข้อความแสดงข้อผิดพลาด
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'รูปแบบอีเมลไม่ถูกต้อง';
        
        // เพิ่มข้อความแสดงข้อผิดพลาดถ้ายังไม่มี
        if (!emailInput.parentNode.querySelector('.invalid-feedback')) {
            emailInput.parentNode.appendChild(feedback);
        }
    }
    
    // ตรวจสอบรูปแบบเบอร์โทรศัพท์
    const phoneInput = form.querySelector('[name="phone_number"]');
    if (phoneInput && phoneInput.value.trim() && !isValidPhone(phoneInput.value.trim())) {
        isValid = false;
        phoneInput.classList.add('is-invalid');
        
        // สร้างข้อความแสดงข้อผิดพลาด
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
        
        // เพิ่มข้อความแสดงข้อผิดพลาดถ้ายังไม่มี
        if (!phoneInput.parentNode.querySelector('.invalid-feedback')) {
            phoneInput.parentNode.appendChild(feedback);
        }
    }
    
    // ถ้าไม่ผ่านการตรวจสอบ ให้แสดงข้อความแจ้งเตือน
    if (!isValid) {
        showAlert('กรุณาตรวจสอบข้อมูลให้ถูกต้อง', 'warning');
    }
    
    return isValid;
}

/**
 * ตรวจสอบความถูกต้องของอีเมล
 * 
 * @param {string} email - อีเมลที่ต้องการตรวจสอบ
 * @returns {boolean} true ถ้าอีเมลถูกต้อง
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * ตรวจสอบความถูกต้องของเบอร์โทรศัพท์
 * 
 * @param {string} phone - เบอร์โทรศัพท์ที่ต้องการตรวจสอบ
 * @returns {boolean} true ถ้าเบอร์โทรศัพท์ถูกต้อง
 */
function isValidPhone(phone) {
    // เบอร์โทรศัพท์ไทย 9-10 หลัก (ไม่รวมเครื่องหมาย - หรือช่องว่าง)
    const re = /^[0-9]{9,10}$/;
    return re.test(phone.replace(/[ -]/g, ''));
}

/**
 * ตั้งค่าการตรวจสอบฟอร์มแบบ real-time
 */
function setupFormValidation() {
    // ฟอร์มเพิ่มนักเรียน
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        // ตั้งค่า event listener สำหรับการเปลี่ยนแปลงและบลัรร์
        const inputs = addForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            // เมื่อผู้ใช้ออกจากฟิลด์
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            // เมื่อผู้ใช้เปลี่ยนแปลงค่า
            input.addEventListener('change', function() {
                validateField(this);
            });
            
            // เมื่อผู้ใช้พิมพ์ ให้ลบสถานะไม่ถูกต้อง
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                
                // ลบข้อความแสดงข้อผิดพลาด
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
    }
    
    // ฟอร์มแก้ไขนักเรียน
    const editForm = document.getElementById('editStudentForm');
    if (editForm) {
        // ตั้งค่า event listener เหมือนกับฟอร์มเพิ่มนักเรียน
        const inputs = editForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('change', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
    }
}

/**
 * ตรวจสอบความถูกต้องของฟิลด์
 * 
 * @param {HTMLElement} field - ฟิลด์ที่ต้องการตรวจสอบ
 */
function validateField(field) {
    // ลบสถานะไม่ถูกต้องและข้อความแสดงข้อผิดพลาดเดิม
    field.classList.remove('is-invalid');
    
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
    
    // ตรวจสอบว่าเป็นฟิลด์ที่จำเป็นหรือไม่
    if (field.required && !field.value.trim()) {
        field.classList.add('is-invalid');
        
        // สร้างข้อความแสดงข้อผิดพลาด
        const error = document.createElement('div');
        error.className = 'invalid-feedback';
        error.textContent = 'กรุณากรอกข้อมูลในช่องนี้';
        field.parentNode.appendChild(error);
        
        return;
    }
    
    // ตรวจสอบตามประเภทของฟิลด์
    if (field.name === 'student_code' && field.value.trim()) {
        if (!/^\d+$/.test(field.value.trim())) {
            field.classList.add('is-invalid');
            
            const error = document.createElement('div');
            error.className = 'invalid-feedback';
            error.textContent = 'รหัสนักเรียนต้องเป็นตัวเลขเท่านั้น';
            field.parentNode.appendChild(error);
        }
    } else if (field.name === 'email' && field.value.trim()) {
        if (!isValidEmail(field.value.trim())) {
            field.classList.add('is-invalid');
            
            const error = document.createElement('div');
            error.className = 'invalid-feedback';
            error.textContent = 'รูปแบบอีเมลไม่ถูกต้อง';
            field.parentNode.appendChild(error);
        }
    } else if (field.name === 'phone_number' && field.value.trim()) {
        if (!isValidPhone(field.value.trim())) {
            field.classList.add('is-invalid');
            
            const error = document.createElement('div');
            error.className = 'invalid-feedback';
            error.textContent = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
            field.parentNode.appendChild(error);
        }
    }
}

/**
 * แสดงโมดัล
 * 
 * @param {string} modalId - ID ของโมดัล
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
 * @param {string} modalId - ID ของโมดัล
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
 * @param {string} type - ประเภทการแจ้งเตือน (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // สร้างคอนเทนเนอร์การแจ้งเตือนถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // กำหนดค่าเริ่มต้น
    let title = '';
    let icon = '';
    
    // กำหนดค่าตามประเภท
    switch (type) {
        case 'success':
            title = 'สำเร็จ';
            icon = 'check_circle';
            break;
        case 'info':
            title = 'ข้อมูล';
            icon = 'info';
            break;
        case 'warning':
            title = 'คำเตือน';
            icon = 'warning';
            break;
        case 'error':
            title = 'ข้อผิดพลาด';
            icon = 'error';
            break;
        default:
            title = 'ข้อความ';
            icon = 'notifications';
    }
    
    // สร้าง HTML ของการแจ้งเตือน
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-icon">
            <span class="material-icons">${icon}</span>
        </div>
        <div class="alert-content">
            <div class="alert-title">${title}</div>
            <div class="alert-message">${message}</div>
        </div>
        <button class="alert-close" onclick="this.parentNode.remove();">&times;</button>
    `;
    
    // เพิ่มการแจ้งเตือนไปยังคอนเทนเนอร์
    alertContainer.appendChild(alert);
    
    // ซ่อนการแจ้งเตือนอัตโนมัติหลังจาก 5 วินาที (ยกเว้นประเภท error)
    if (type !== 'error') {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

/**
 * ซ่อนการแจ้งเตือนทั้งหมด
 */
function hideAlert() {
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
}



/**
 * แก้ไขฟังก์ชันในไฟล์ assets/js/students.js
 * โดยเพิ่มการรีโหลดตาราง DataTables หลังจากการเพิ่ม/แก้ไข/ลบข้อมูล
 */

// แก้ไขฟังก์ชันเพิ่มนักเรียน
function addStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('addStudentForm');
    
    // ล้างการแจ้งเตือนเดิม
    hideAlert();
    
    // ตรวจสอบความถูกต้องของข้อมูล
    const validation = validateStudentForm(form);
    
    if (!validation.isValid) {
        showAlert(validation.errorMessages.join('<br>'), 'warning', 'กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    // ปิดการใช้งานปุ่มบันทึก
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData(form);
    
    // เพิ่ม action และ temp_line_id
    formData.append('action', 'add_student');
    const studentCode = formData.get('student_code');
    const tempLineId = `TEMP_${studentCode}_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;
    formData.append('temp_line_id', tempLineId);
    
    // แสดงการโหลด
    showAlert('กำลังเพิ่มข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store' // ป้องกันการใช้ cache
    })
    .then(response => response.json())
    .then(data => {
        hideAlert();
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('addStudentModal');
            
            // รีโหลดหน้าแบบไม่ใช้ cache
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding student:', error);
        hideAlert();
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการเพิ่มข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันอัปเดตข้อมูลนักเรียน
function updateStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('editStudentForm');
    const formData = new FormData(form);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!formData.get('student_code') || !formData.get('firstname') || !formData.get('lastname')) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // ปิดการใช้งานปุ่มบันทึก
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'update_student');
    
    // แสดงการโหลด
    showAlert('กำลังอัปเดตข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('editStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating student:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการอัปเดตข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันลบนักเรียน
function deleteStudentConfirm() {
    // ดึงรหัสนักเรียนที่จะลบ
    const studentId = document.getElementById('delete_student_id').value;
    
    // ปิดการใช้งานปุ่ม
    const submitBtn = document.querySelector('#deleteStudentForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData();
    formData.append('action', 'delete_student');
    formData.append('student_id', studentId);
    
    // แสดงการโหลด
    showAlert('กำลังลบข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('deleteStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting student:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน', 'danger');
    });
}

// แก้ไขฟังก์ชันนำเข้าข้อมูลนักเรียน
function importStudents() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    // ตรวจสอบว่ามีการเลือกไฟล์หรือไม่
    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('กรุณาเลือกไฟล์ Excel', 'warning');
        return;
    }
    
    // ปิดการใช้งานปุ่ม
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-loading');
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'import_students');
    
    // แสดงการโหลด
    showAlert('กำลังนำเข้าข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('importModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                const timestamp = new Date().getTime();
                window.location.href = window.location.pathname + '?_=' + timestamp;
            }, 2000);
        } else {
            // เปิดใช้งานปุ่มอีกครั้ง
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error importing students:', error);
        
        // เปิดใช้งานปุ่มอีกครั้ง
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-loading');
        }
        
        showAlert('เกิดข้อผิดพลาดในการนำเข้าข้อมูลนักเรียน', 'danger');
    });
}
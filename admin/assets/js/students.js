/**
 * students.js - จาวาสคริปต์สำหรับหน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    initEventListeners();
    
    // แสดงข้อมูลสถิติ
    loadStatistics();
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
            
            // สร้าง query string จากฟอร์มค้นหา
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let pair of formData.entries()) {
                if (pair[1] !== '') {
                    params.append(pair[0], pair[1]);
                }
            }
            
            // เปลี่ยน URL และโหลดหน้าใหม่
            window.location.href = 'students.php?' + params.toString();
        });
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
    
    // Event listener สำหรับปุ่มพิมพ์รายชื่อ
    const printButton = document.querySelector('button[title="พิมพ์รายชื่อ"]');
    if (printButton) {
        printButton.addEventListener('click', function() {
            printStudentList();
        });
    }
    
    // Event listener สำหรับปุ่มดาวน์โหลด Excel
    const downloadButton = document.querySelector('button[title="ดาวน์โหลด Excel"]');
    if (downloadButton) {
        downloadButton.addEventListener('click', function() {
            downloadExcel();
        });
    }
}

/**
 * โหลดข้อมูลสถิติ
 */
function loadStatistics() {
    // ใช้ AJAX เพื่อโหลดข้อมูลสถิติ
    fetch('api/students_api.php?action=get_statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // อัปเดตข้อมูลสถิติในหน้า
                updateStatisticsDisplay(data.statistics);
            }
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

/**
 * อัปเดตการแสดงผลสถิติ
 * 
 * @param {Object} stats - ข้อมูลสถิติ
 */
function updateStatisticsDisplay(stats) {
    // อัปเดตจำนวนนักเรียนทั้งหมด
    const totalElement = document.querySelector('.stat-card:nth-child(1) .stat-value');
    if (totalElement) {
        totalElement.textContent = stats.total.toLocaleString();
    }
    
    // อัปเดตจำนวนนักเรียนชาย
    const maleElement = document.querySelector('.stat-card:nth-child(2) .stat-value');
    if (maleElement) {
        maleElement.textContent = stats.male.toLocaleString();
    }
    
    // อัปเดตจำนวนนักเรียนหญิง
    const femaleElement = document.querySelector('.stat-card:nth-child(3) .stat-value');
    if (femaleElement) {
        femaleElement.textContent = stats.female.toLocaleString();
    }
    
    // อัปเดตจำนวนนักเรียนเสี่ยงตกกิจกรรม
    const riskElement = document.querySelector('.stat-card:nth-child(4) .stat-value');
    if (riskElement) {
        riskElement.textContent = stats.risk.toLocaleString();
    }
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
    
    // โหลดข้อมูลชั้นเรียนและครูที่ปรึกษา
    loadClassesForSelect('addStudentForm');
    
    // แสดงโมดัล
    showModal('addStudentModal');
}

/**
 * เพิ่มนักเรียนใหม่
 */
function addStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('addStudentForm');
    const formData = new FormData(form);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!formData.get('student_id') || !formData.get('firstname') || !formData.get('lastname')) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'add_student');
    
    // แสดงการโหลด
    showAlert('กำลังเพิ่มข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('addStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding student:', error);
        showAlert('เกิดข้อผิดพลาดในการเพิ่มข้อมูลนักเรียน', 'danger');
    });
}

/**
 * แสดงโมดัลแก้ไขข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function editStudent(studentId) {
    // โหลดข้อมูลนักเรียน
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // โหลดข้อมูลชั้นเรียนและครูที่ปรึกษา
    loadClassesForSelect('editStudentForm');
    
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // เซ็ตค่าในฟอร์ม
                fillEditForm(data.student);
                
                // ซ่อนการแจ้งเตือน
                hideAlert();
                
                // แสดงโมดัล
                showModal('editStudentModal');
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading student data:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน', 'danger');
        });
}

/**
 * เติมข้อมูลในฟอร์มแก้ไข
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function fillEditForm(student) {
    const form = document.getElementById('editStudentForm');
    
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
    if (form.querySelector('input[name="phone_number"]')) {
        form.querySelector('input[name="phone_number"]').value = student.phone_number || '';
    }
    if (form.querySelector('input[name="email"]')) {
        form.querySelector('input[name="email"]').value = student.email || '';
    }
    
    // เซ็ตค่าชั้นเรียน
    if (student.current_class_id && form.querySelector('select[name="class_id"]')) {
        setTimeout(() => {
            setSelectValue(form.querySelector('select[name="class_id"]'), student.current_class_id);
        }, 500); // รอให้โหลดข้อมูลชั้นเรียนเสร็จก่อน
    }
}

/**
 * อัปเดตข้อมูลนักเรียน
 */
function updateStudent() {
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById('editStudentForm');
    const formData = new FormData(form);
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (!formData.get('student_code') || !formData.get('firstname') || !formData.get('lastname')) {
        showAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'update_student');
    
    // แสดงการโหลด
    showAlert('กำลังอัปเดตข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('editStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating student:', error);
        showAlert('เกิดข้อผิดพลาดในการอัปเดตข้อมูลนักเรียน', 'danger');
    });
}

/**
 * โหลดข้อมูลชั้นเรียนสำหรับ dropdown
 * 
 * @param {string} formId - ID ของฟอร์ม
 */
function loadClassesForSelect(formId) {
    const classSelect = document.querySelector(`#${formId} select[name="class_id"]`);
    
    if (!classSelect) return;
    
    // เคลียร์ตัวเลือกเดิม
    classSelect.innerHTML = '<option value="">-- เลือกชั้นเรียน --</option>';
    
    // โหลดข้อมูลชั้นเรียน
    fetch('api/students_api.php?action=get_classes')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.classes) {
                // จัดกลุ่มชั้นเรียนตามระดับ
                const classGroups = {};
                
                data.classes.forEach(classData => {
                    const level = classData.level;
                    if (!classGroups[level]) {
                        classGroups[level] = [];
                    }
                    classGroups[level].push(classData);
                });
                
                // สร้าง optgroup สำหรับแต่ละระดับ
                for (const level in classGroups) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = level;
                    
                    classGroups[level].forEach(classData => {
                        const option = document.createElement('option');
                        option.value = classData.class_id;
                        option.textContent = `${classData.level}/${classData.group_number} ${classData.department_name}`;
                        optgroup.appendChild(option);
                    });
                    
                    classSelect.appendChild(optgroup);
                }
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });
}

/**
 * แสดงโมดัลดูข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function viewStudent(studentId) {
    // โหลดข้อมูลนักเรียน
    showAlert('กำลังโหลดข้อมูลนักเรียน...', 'info');
    
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // อัปเดตข้อมูลในโมดัล
                updateStudentViewModal(data.student);
                
                // ซ่อนการแจ้งเตือน
                hideAlert();
                
                // แสดงโมดัล
                showModal('viewStudentModal');
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading student data:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน', 'danger');
        });
}

/**
 * อัปเดตข้อมูลในโมดัลดูรายละเอียดนักเรียน
 * 
 * @param {Object} student - ข้อมูลนักเรียน
 */
function updateStudentViewModal(student) {
    const modal = document.getElementById('viewStudentModal');
    
    // อัปเดตอวาตาร์
    const avatar = modal.querySelector('.student-profile-avatar');
    if (avatar) {
        if (student.profile_picture) {
            // ถ้ามีรูปโปรไฟล์
            avatar.innerHTML = `<img src="${student.profile_picture}" alt="${student.first_name}">`;
        } else {
            // ถ้าไม่มีรูปโปรไฟล์ ใช้ตัวอักษรแรกของชื่อ
            avatar.textContent = student.first_name.charAt(0);
        }
    }
    
    // อัปเดตข้อมูลพื้นฐาน
    const nameEl = modal.querySelector('h3');
    if (nameEl) {
        nameEl.textContent = `${student.title} ${student.first_name} ${student.last_name}`;
    }
    
    const studentIdEl = modal.querySelector('p:nth-child(2)');
    if (studentIdEl) {
        studentIdEl.textContent = `รหัสนักเรียน: ${student.student_code}`;
    }
    
    const classEl = modal.querySelector('p:nth-child(3)');
    if (classEl) {
        classEl.textContent = `ชั้น ${student.class} ${student.department_name || ''}`;
    }
    
    // อัปเดตข้อมูลติดต่อ
    const addressEl = modal.querySelector('.info-section:nth-child(1) p:nth-child(2)');
    if (addressEl) {
        addressEl.innerHTML = `<strong>ที่อยู่:</strong> ${student.address || 'ไม่ระบุ'}`;
    }
    
    const lineEl = modal.querySelector('.info-section:nth-child(1) p:nth-child(3)');
    if (lineEl) {
        lineEl.innerHTML = `<strong>LINE ID:</strong> ${student.line_id || 'ยังไม่ได้เชื่อมต่อบัญชี LINE'}`;
        
        // เพิ่มสถานะการเชื่อมต่อ LINE
        if (student.line_connected) {
            lineEl.innerHTML += ' <span class="status-badge success">เชื่อมต่อแล้ว</span>';
        } else {
            lineEl.innerHTML += ' <span class="status-badge warning">ยังไม่ได้เชื่อมต่อ</span>';
        }
    }
    
    // อัปเดตข้อมูลผู้ปกครอง
    const parentSection = modal.querySelector('.info-section:nth-child(2)');
    if (parentSection) {
        let parentHTML = '<h4>ข้อมูลผู้ปกครอง</h4>';
        
        if (student.parents && student.parents.length > 0) {
            student.parents.forEach(parent => {
                parentHTML += `<p><strong>ชื่อผู้ปกครอง:</strong> ${parent.title} ${parent.first_name} ${parent.last_name} (${parent.relationship})</p>`;
                parentHTML += `<p><strong>เบอร์โทรศัพท์:</strong> ${parent.phone_number || 'ไม่ระบุ'}</p>`;
                
                if (parent.line_id) {
                    parentHTML += `<p><strong>LINE:</strong> <span class="status-badge success">เชื่อมต่อแล้ว</span></p>`;
                } else {
                    parentHTML += `<p><strong>LINE:</strong> <span class="status-badge warning">ยังไม่ได้เชื่อมต่อ</span></p>`;
                }
            });
        } else {
            parentHTML += '<p>ไม่มีข้อมูลผู้ปกครอง</p>';
        }
        
        parentSection.innerHTML = parentHTML;
    }
    
    // อัปเดตข้อมูลการศึกษา
    const educationSection = modal.querySelector('.info-section:nth-child(3)');
    if (educationSection) {
        let educationHTML = '<h4>ข้อมูลการศึกษา</h4>';
        
        if (student.advisor) {
            educationHTML += `<p><strong>ครูที่ปรึกษา:</strong> ${student.advisor.name}</p>`;
        } else {
            educationHTML += '<p><strong>ครูที่ปรึกษา:</strong> ไม่ระบุ</p>';
        }
        
        let statusClass = '';
        if (student.attendance_status === 'เสี่ยงตกกิจกรรม') {
            statusClass = 'danger';
        } else if (student.attendance_status === 'ต้องระวัง') {
            statusClass = 'warning';
        } else {
            statusClass = 'success';
        }
        
        educationHTML += `<p><strong>สถานะการเข้าแถว:</strong> <span class="status-badge ${statusClass}">${student.attendance_status} (${student.attendance_rate.toFixed(1)}%)</span></p>`;
        
        educationSection.innerHTML = educationHTML;
    }
    
    // อัปเดตสถิติการเข้าแถว
    const attendanceDaysEl = modal.querySelector('.attendance-stat:nth-child(1) .attendance-stat-value');
    if (attendanceDaysEl) {
        attendanceDaysEl.textContent = student.total_attendance_days || 0;
    }
    
    const absenceDaysEl = modal.querySelector('.attendance-stat:nth-child(2) .attendance-stat-value');
    if (absenceDaysEl) {
        absenceDaysEl.textContent = student.total_absence_days || 0;
    }
    
    const totalDaysEl = modal.querySelector('.attendance-stat:nth-child(3) .attendance-stat-value');
    if (totalDaysEl) {
        totalDaysEl.textContent = (student.total_attendance_days || 0) + (student.total_absence_days || 0);
    }
    
    // เซ็ต ID สำหรับปุ่มแก้ไข
    const editButton = modal.querySelector('button[onclick^="editStudent"]');
    if (editButton) {
        editButton.setAttribute('onclick', `editStudent(${student.student_id})`);
    }
}

/**
 * แสดงโมดัลลบข้อมูลนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function deleteStudent(studentId) {
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // เซ็ตค่า ID ที่จะลบ
                document.getElementById('delete_student_id').value = studentId;
                
                // แสดงชื่อนักเรียนในหน้ายืนยันการลบ
                const studentName = `${data.student.title} ${data.student.first_name} ${data.student.last_name}`;
                document.getElementById('delete_student_name').textContent = studentName;
                
                // แสดงโมดัล
                showModal('deleteStudentModal');
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading student data:', error);
            showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน', 'danger');
        });
}

/**
 * ลบข้อมูลนักเรียน
 */
function deleteStudentConfirm() {
    // ดึงรหัสนักเรียนที่จะลบ
    const studentId = document.getElementById('delete_student_id').value;
    
    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData();
    formData.append('action', 'delete_student');
    formData.append('student_id', studentId);
    
    // แสดงการโหลด
    showAlert('กำลังลบข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('deleteStudentModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting student:', error);
        showAlert('เกิดข้อผิดพลาดในการลบข้อมูลนักเรียน', 'danger');
    });
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
 * นำเข้าข้อมูลนักเรียน
 */
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
    
    // เพิ่ม action สำหรับการระบุการกระทำ
    formData.append('action', 'import_students');
    
    // แสดงการโหลด
    showAlert('กำลังนำเข้าข้อมูลนักเรียน...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/students_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('importModal');
            
            // รีโหลดหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error importing students:', error);
        showAlert('เกิดข้อผิดพลาดในการนำเข้าข้อมูลนักเรียน', 'danger');
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
                    <th>การเข้าแถว</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // ดึงข้อมูลจากตาราง
    const table = document.querySelector('.data-table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const id = row.querySelector('td:nth-child(1)').textContent;
        const name = row.querySelector('.student-name').textContent;
        const classRoom = row.querySelector('td:nth-child(3)').textContent;
        const attendance = row.querySelector('td:nth-child(5)').textContent;
        const status = row.querySelector('.status-badge').textContent;
        
        content += `
            <tr>
                <td>${id}</td>
                <td>${name}</td>
                <td>${classRoom}</td>
                <td>${attendance}</td>
                <td>${status}</td>
            </tr>
        `;
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
 * ดาวน์โหลดรายชื่อนักเรียนเป็นไฟล์ Excel
 */
function downloadExcel() {
    // ใช้ url สำหรับดาวน์โหลด
    const url = 'api/export_students.php';
    
    // สร้าง query string จากตัวกรอง
    const params = new URLSearchParams(window.location.search);
    const downloadUrl = `${url}?${params.toString()}`;
    
    // สร้าง link สำหรับดาวน์โหลดและคลิกโดยอัตโนมัติ
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', 'รายชื่อนักเรียน.xlsx');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
 * เซ็ตค่า select
 * 
 * @param {HTMLSelectElement} selectElement - Element select
 * @param {string} value - ค่าที่ต้องการเลือก
 */
function setSelectValue(selectElement, value) {
    if (!selectElement) return;
    
    for (let i = 0; i < selectElement.options.length; i++) {
        if (selectElement.options[i].value == value) {
            selectElement.selectedIndex = i;
            break;
        }
    }
}

/**
 * แสดงการแจ้งเตือน
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
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
    
    // ให้ Alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที (ยกเว้นประเภท danger)
    if (type !== 'danger') {
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
}

/**
 * ซ่อนการแจ้งเตือน
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
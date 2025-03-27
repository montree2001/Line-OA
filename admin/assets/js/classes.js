/**
 * classes.js - JavaScript สำหรับระบบจัดการชั้นเรียนและแผนกวิชา
 */

// ตัวแปรสำหรับเก็บข้อมูลชั่วคราว
let currentClassId = null;
let currentDepartmentId = null;
let currentAcademicYearId = null;
let activeClasses = [];
let advisorsChanges = [];
let deleteCallback = null;

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    initializeClassManagement();
});

/**
 * ฟังก์ชันเริ่มต้นระบบจัดการชั้นเรียน
 */
function initializeClassManagement() {
    // ติดตั้ง event listeners
    setupEventListeners();
    
    // ดึงข้อมูลชั้นเรียนเริ่มต้น
    fetchInitialData();
}

/**
 * ติดตั้ง event listeners
 */
function setupEventListeners() {
    // Event listener สำหรับฟอร์มแผนกวิชา
    const departmentForm = document.getElementById('departmentForm');
    if (departmentForm) {
        departmentForm.addEventListener('submit', handleDepartmentFormSubmit);
    }
    
    // Event listener สำหรับฟอร์มชั้นเรียน
    const classForm = document.getElementById('classForm');
    if (classForm) {
        classForm.addEventListener('submit', handleClassFormSubmit);
    }
    
    // Event listener สำหรับปุ่มปิดโมดัล
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // ปิดโมดัลเมื่อคลิกนอกกรอบ
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                this.style.display = 'none';
            }
        });
    });
    
    // เพิ่มตัวกรองชั้นเรียน
    const filterControls = document.querySelectorAll('.filter-container select');
    filterControls.forEach(control => {
        control.addEventListener('change', filterClasses);
    });
}

/**
 * ดึงข้อมูลเริ่มต้น
 */
function fetchInitialData() {
    // ดึงปีการศึกษาปัจจุบัน
    const activeYearElement = document.querySelector('#academicYearFilter option[selected]');
    if (activeYearElement) {
        currentAcademicYearId = activeYearElement.value;
    }
    
    // ดึงชั้นเรียนทั้งหมด
    const classRows = document.querySelectorAll('.class-row');
    classRows.forEach(row => {
        const classId = row.getAttribute('data-class-id');
        const academicYearId = row.getAttribute('data-academic-year');
        const level = row.getAttribute('data-level');
        const department = row.getAttribute('data-department');
        const groupNumberElement = row.querySelector('.class-name');
        const groupNumber = groupNumberElement ? groupNumberElement.textContent.split(' กลุ่ม ')[1] : '1';
        
        activeClasses.push({
            id: classId,
            academicYearId: academicYearId,
            level: level,
            department: department,
            groupNumber: groupNumber
        });
    });
}

/**
 * จัดการการ submit ฟอร์มแผนกวิชา
 */
function handleDepartmentFormSubmit(e) {
    e.preventDefault();
    
    const departmentId = document.getElementById('departmentId').value;
    const departmentName = document.getElementById('departmentName').value;
    const departmentCode = document.getElementById('departmentCode')?.value || '';
    
    // ตรวจสอบข้อมูล
    if (!departmentName) {
        showNotification('กรุณาระบุชื่อแผนกวิชา', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('department_name', departmentName);
    if (departmentCode) {
        formData.append('department_code', departmentCode);
    }
    
    if (departmentId) {
        // กรณีแก้ไข
        formData.append('action', 'edit_department');
        formData.append('department_id', departmentId);
        updateDepartment(formData);
    } else {
        // กรณีเพิ่มใหม่
        formData.append('action', 'add_department');
        createDepartment(formData);
    }
}

/**
 * สร้างแผนกวิชาใหม่
 */
function createDepartment(formData) {
    // ส่งข้อมูลผ่าน AJAX
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('departmentModal');
            reloadPage(); // โหลดหน้าใหม่เพื่อแสดงข้อมูลที่เพิ่ม
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
    });
}

/**
 * อัปเดตแผนกวิชา
 */
function updateDepartment(formData) {
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('departmentModal');
            reloadPage();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
    });
}

/**
 * ลบแผนกวิชา
 */
function deleteDepartment(departmentId) {
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบแผนกวิชารหัส <strong>${departmentId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบแผนกวิชาจะส่งผลต่อชั้นเรียนและนักเรียนทั้งหมดในแผนกนี้
    `;
    
    deleteCallback = () => {
        const formData = new FormData();
        formData.append('action', 'delete_department');
        formData.append('department_id', departmentId);
        
        fetch('api/class_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message, 'success');
                closeModal('confirmDeleteModal');
                reloadPage();
            } else {
                showNotification(data.message, 'error');
                closeModal('confirmDeleteModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
            closeModal('confirmDeleteModal');
        });
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

/**
 * แสดงโมดัลเพิ่มแผนกวิชา
 */
function showDepartmentModal() {
    document.getElementById('departmentModalTitle').textContent = 'เพิ่มแผนกวิชาใหม่';
    document.getElementById('departmentId').value = '';
    document.getElementById('departmentName').value = '';
    
    // ถ้ามีฟิลด์ department code
    const codeField = document.getElementById('departmentCode');
    if (codeField) {
        codeField.value = '';
    }
    
    showModal('departmentModal');
}

/**
 * แก้ไขแผนกวิชา
 */
function editDepartment(departmentId) {
    currentDepartmentId = departmentId;
    
    // ดึงข้อมูลแผนกวิชาจากเซิร์ฟเวอร์
    const formData = new FormData();
    formData.append('action', 'get_department_details');
    formData.append('department_id', departmentId);
    
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
            document.getElementById('departmentId').value = data.department.department_id;
            document.getElementById('departmentName').value = data.department.department_name;
            
            // ซ่อนฟิลด์ department code ในกรณีแก้ไข
            const codeField = document.getElementById('departmentCode');
            if (codeField) {
                codeField.value = data.department.department_code;
                const codeGroup = document.getElementById('departmentCodeGroup');
                if (codeGroup) {
                    codeGroup.style.display = 'none';
                }
            }
            
            showModal('departmentModal');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
    });
}

/**
 * จัดการการ submit ฟอร์มชั้นเรียน
 */
function handleClassFormSubmit(e) {
    e.preventDefault();
    
    const classId = document.getElementById('classId').value;
    const academicYearId = document.getElementById('academicYear').value;
    const level = document.getElementById('classLevel').value;
    const departmentId = document.getElementById('classDepartment').value;
    const groupNumber = document.getElementById('groupNumber').value;
    const classroom = document.getElementById('classroom')?.value || '';
    const advisorId = document.getElementById('classAdvisor')?.value || '';
    
    // ตรวจสอบข้อมูล
    if (!academicYearId || !level || !departmentId || !groupNumber) {
        showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('academic_year_id', academicYearId);
    formData.append('level', level);
    formData.append('department_id', departmentId);
    formData.append('group_number', groupNumber);
    formData.append('classroom', classroom);
    if (advisorId) formData.append('advisor_id', advisorId);
    
    if (classId) {
        // กรณีแก้ไข
        formData.append('action', 'edit_class');
        formData.append('class_id', classId);
        updateClass(formData);
    } else {
        // กรณีเพิ่มใหม่
        formData.append('action', 'add_class');
        createClass(formData);
    }
}

/**
 * สร้างชั้นเรียนใหม่
 */
function createClass(formData) {
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('addClassModal');
            reloadPage();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
    });
}

/**
 * อัปเดตชั้นเรียน
 */
function updateClass(formData) {
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('addClassModal');
            reloadPage();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
    });
}

/**
 * แสดงโมดัลเพิ่มชั้นเรียน
 */
function showAddClassModal() {
    document.getElementById('classModalTitle').textContent = 'เพิ่มชั้นเรียนใหม่';
    document.getElementById('classId').value = '';
    document.getElementById('classForm').reset();
    
    // ตั้งค่าปีการศึกษาปัจจุบันเป็นค่าเริ่มต้น
    const activeYear = document.querySelector('#academicYear option[selected]');
    if (activeYear) {
        document.getElementById('academicYear').value = activeYear.value;
    }
    
    showModal('addClassModal');
}

/**
 * แก้ไขชั้นเรียน
 */
function editClass(classId) {
    currentClassId = classId;
    
    // ดึงข้อมูลชั้นเรียนจากเซิร์ฟเวอร์
    const formData = new FormData();
    formData.append('action', 'get_class_details');
    formData.append('class_id', classId);
    
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('classModalTitle').textContent = 'แก้ไขชั้นเรียน';
            document.getElementById('classId').value = data.class.class_id;
            document.getElementById('academicYear').value = data.class.academic_year_id;
            document.getElementById('classLevel').value = data.class.level;
            document.getElementById('classDepartment').value = data.class.department_id;
            document.getElementById('groupNumber').value = data.class.group_number;
            
            if (document.getElementById('classroom')) {
                document.getElementById('classroom').value = data.class.classroom || '';
            }
            
            if (document.getElementById('classAdvisor') && data.primary_advisor) {
                document.getElementById('classAdvisor').value = data.primary_advisor.teacher_id;
            }
            
            showModal('addClassModal');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
    });
}

/**
 * ลบชั้นเรียน
 */
function deleteClass(classId) {
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียนรหัส <strong>${classId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนทั้งหมดในชั้นเรียนนี้
    `;
    
    deleteCallback = () => {
        const formData = new FormData();
        formData.append('action', 'delete_class');
        formData.append('class_id', classId);
        
        fetch('api/class_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message, 'success');
                closeModal('confirmDeleteModal');
                reloadPage();
            } else {
                showNotification(data.message, 'error');
                closeModal('confirmDeleteModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
            closeModal('confirmDeleteModal');
        });
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

/**
 * แสดงรายละเอียดชั้นเรียน
 */
function showClassDetails(classId) {
    currentClassId = classId;
    
    // ดึงข้อมูลชั้นเรียนจากเซิร์ฟเวอร์
    fetch(`api/class_manager.php`, {
        method: 'POST',
        body: new URLSearchParams({
            'action': 'get_class_details',
            'class_id': classId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // เติมข้อมูลพื้นฐาน
            document.getElementById('classDetailsTitle').textContent = `รายละเอียดชั้น ${data.class.level} กลุ่ม ${data.class.group_number} ${data.class.department_name}`;
            document.getElementById('detailAcademicYear').textContent = data.class.academic_year;
            document.getElementById('detailLevel').textContent = data.class.level;
            document.getElementById('detailDepartment').textContent = data.class.department_name;
            document.getElementById('detailGroup').textContent = data.class.group_number;
            document.getElementById('detailStudentCount').textContent = `${data.class.student_count} คน`;
            
            // เติมข้อมูลครูที่ปรึกษา
            populateAdvisorsList(data.advisors);
            
            // เติมข้อมูลนักเรียน
            populateStudentsList(data.students);
            
            // แสดงโมดัล
            showModal('classDetailsModal');
            
            // สร้างกราฟเมื่อโมดัลแสดงแล้ว
            setTimeout(() => {
                createAttendanceCharts(data.attendance_stats);
            }, 300);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
    });
}

/**
 * เติมข้อมูลครูที่ปรึกษา
 */
function populateAdvisorsList(advisors) {
    const advisorsList = document.getElementById('advisorsList');
    advisorsList.innerHTML = '';
    
    if (advisors.length === 0) {
        advisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position}</div>
            </div>
        `;
        advisorsList.appendChild(advisorEl);
    });
}

/**
 * เติมข้อมูลนักเรียน
 */
function populateStudentsList(students) {
    const studentTableBody = document.getElementById('studentTableBody');
    studentTableBody.innerHTML = '';
    
    if (students.length === 0) {
        studentTableBody.innerHTML = '<tr><td colspan="5" class="text-center">ไม่มีนักเรียนในชั้นเรียนนี้</td></tr>';
        return;
    }
    
    students.forEach(student => {
        const statusClass = student.percent > 90 ? 'success' : (student.percent > 75 ? 'warning' : 'danger');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.code}</td>
            <td>${student.name}</td>
            <td>${student.attendance}/${student.total} วัน</td>
            <td>${student.percent.toFixed(1)}%</td>
            <td><span class="status-badge ${statusClass}">${student.status}</span></td>
        `;
        studentTableBody.appendChild(row);
    });
}

/**
 * สร้างกราฟการเข้าแถว
 */
function createAttendanceCharts(attendance_stats) {
    // กราฟสรุปการเข้าแถว
    const overallChart = document.getElementById('classAttendanceChart');
    if (!overallChart) return;
    
    // คำนวณอัตราการเข้าแถวรวม
    const attendanceRate = attendance_stats.overall_rate || 85;
    
    overallChart.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="display: inline-flex; align-items: center; gap: 10px;">
                <div style="width: 20px; height: 20px; background-color: #4caf50;"></div>
                <span>เข้าแถว</span>
                <div style="width: 20px; height: 20px; background-color: #f44336;"></div>
                <span>ขาดแถว</span>
            </div>
            <div style="height: 200px; background: linear-gradient(to right, #4caf50 ${attendanceRate}%, #f44336 ${100-attendanceRate}%); margin-top: 10px; border-radius: 8px;">
                <div style="text-align: center; font-size: 24px; color: white; padding-top: 80px;">
                    ${attendanceRate.toFixed(1)}% <small style="font-size: 14px;">เข้าแถว</small>
                </div>
            </div>
        </div>
    `;
    
    // กราฟรายเดือน
    const monthlyChart = document.getElementById('monthlyAttendanceChart');
    if (!monthlyChart) return;
    
    let barsHtml = '';
    const monthlyData = attendance_stats.monthly || [
        { month: 'ม.ค.', present: 90, absent: 10 },
        { month: 'ก.พ.', present: 85, absent: 15 },
        { month: 'มี.ค.', present: 88, absent: 12 },
        { month: 'เม.ย.', present: 92, absent: 8 },
        { month: 'พ.ค.', present: 94, absent: 6 }
    ];
    
    monthlyData.forEach(item => {
        const presentRate = (item.present / (item.present + item.absent) * 100).toFixed(1);
        barsHtml += `
            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                <div style="height: ${presentRate * 1.5}px; width: 30px; background-color: #4caf50; margin-bottom: 5px;"></div>
                <div style="font-size: 12px;">${item.month}</div>
                <div style="font-size: 10px;">${presentRate}%</div>
            </div>
        `;
    });
    
    monthlyChart.innerHTML = `
        <div style="padding: 20px;">
            <div style="display: flex; align-items: flex-end; justify-content: space-around; height: 180px;">
                ${barsHtml}
            </div>
        </div>
    `;
}

/**
 * เปิดโมดัลจัดการครูที่ปรึกษา
 */
function manageAdvisors(classId) {
    currentClassId = classId;
    advisorsChanges = []; // รีเซ็ตการเปลี่ยนแปลง
    
    // ดึงข้อมูลครูที่ปรึกษาจากเซิร์ฟเวอร์
    fetch(`api/class_manager.php`, {
        method: 'POST',
        body: new URLSearchParams({
            'action': 'get_class_advisors',
            'class_id': classId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('advisorsClassTitle').textContent = data.class_name;
            
            // เติมข้อมูลครูที่ปรึกษาปัจจุบัน
            renderCurrentAdvisors(data.advisors);
            
            // แสดงโมดัล
            showModal('advisorsModal');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
    });
}

/**
 * แสดงรายการครูที่ปรึกษาปัจจุบัน
 */
function renderCurrentAdvisors(advisors) {
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    currentAdvisorsList.innerHTML = '';
    
    if (advisors.length === 0) {
        currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position}</div>
            </div>
            <div class="advisor-action">
                ${!advisor.is_primary ? `
                <button class="table-action-btn success" onclick="setAsPrimaryAdvisor(${advisor.id})">
                    <span class="material-icons">stars</span>
                </button>` : ''}
                <button class="table-action-btn danger" onclick="removeAdvisor(${advisor.id})">
                    <span class="material-icons">delete</span>
                </button>
            </div>
        `;
        currentAdvisorsList.appendChild(advisorEl);
    });
}

/**
 * เพิ่มครูที่ปรึกษา
 */
function addAdvisor() {
    const advisorId = document.getElementById('advisorSelect').value;
    const isPrimary = document.getElementById('isPrimaryAdvisor').checked;
    
    if (!advisorId) {
        showNotification('กรุณาเลือกครูที่ปรึกษา', 'warning');
        return;
    }
    
    // ตรวจสอบว่าเลือกซ้ำหรือไม่
    const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
    let isDuplicate = false;
    
    currentAdvisorItems.forEach(item => {
        const actionButton = item.querySelector('.advisor-action button:last-child');
        if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
            isDuplicate = true;
        }
    });
    
    if (isDuplicate) {
        showNotification('ครูที่ปรึกษาท่านนี้มีอยู่ในรายการแล้ว', 'warning');
        return;
    }
    
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'add',
        teacher_id: advisorId,
        is_primary: isPrimary
    });
    
    // ในสถานการณ์จริง ควรดึงข้อมูลครูจาก API
    // จำลองการดึงข้อมูล
    const advisorName = document.querySelector(`#advisorSelect option[value="${advisorId}"]`).textContent;
    
    // เพิ่มรายการใหม่ลงในรายการครูที่ปรึกษาปัจจุบัน
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    const noAdvisorMessage = currentAdvisorsList.querySelector('.text-muted');
    if (noAdvisorMessage) {
        currentAdvisorsList.innerHTML = '';
    }
    
    const advisorEl = document.createElement('div');
    advisorEl.className = 'advisor-item';
    advisorEl.innerHTML = `
        <div class="advisor-avatar">${advisorName.charAt(0)}</div>
        <div class="advisor-info">
            <div>${advisorName} ${isPrimary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
            <div class="advisor-position">เพิ่มใหม่</div>
        </div>
        <div class="advisor-action">
            ${!isPrimary ? `
            <button class="table-action-btn success" onclick="setAsPrimaryAdvisor(${advisorId})">
                <span class="material-icons">stars</span>
            </button>` : ''}
            <button class="table-action-btn danger" onclick="removeNewAdvisor(this, ${advisorId})">
                <span class="material-icons">delete</span>
            </button>
        </div>
    `;
    currentAdvisorsList.appendChild(advisorEl);
    
    // รีเซ็ตฟอร์ม
    document.getElementById('advisorSelect').value = '';
    document.getElementById('isPrimaryAdvisor').checked = false;
    
    showNotification('เพิ่มครูที่ปรึกษาใหม่ในรายการแล้ว', 'success');
}

/**
 * ลบครูที่ปรึกษาที่เพิ่งเพิ่มใหม่
 */
function removeNewAdvisor(buttonElement, advisorId) {
    // ลบรายการจาก DOM
    const advisorItem = buttonElement.closest('.advisor-item');
    advisorItem.remove();
    
    // ลบการเปลี่ยนแปลงจากรายการ
    advisorsChanges = advisorsChanges.filter(change => {
        return !(change.action === 'add' && change.teacher_id == advisorId);
    });
    
    // ตรวจสอบว่ายังมีครูที่ปรึกษาในรายการหรือไม่
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    if (currentAdvisorsList.children.length === 0) {
        currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
    }
    
    showNotification('ลบครูที่ปรึกษาออกจากรายการแล้ว', 'info');
}

/**
 * ตั้งเป็นครูที่ปรึกษาหลัก
 */
function setAsPrimaryAdvisor(advisorId) {
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'set_primary',
        teacher_id: advisorId
    });
    
    // ล้างครูที่ปรึกษาหลักเดิม
    const primaryBadges = document.querySelectorAll('#currentAdvisorsList .primary-badge');
    primaryBadges.forEach(badge => {
        badge.remove();
    });
    
    const setPrimaryButtons = document.querySelectorAll('#currentAdvisorsList .table-action-btn.success');
    setPrimaryButtons.forEach(button => {
        button.style.display = '';
    });
    
    // ตั้งครูคนนี้เป็นที่ปรึกษาหลัก
    const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
    currentAdvisorItems.forEach(item => {
        const actionButton = item.querySelector('.advisor-action button:last-child');
        if (actionButton && (actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`) || 
                              actionButton.getAttribute('onclick').includes(`removeNewAdvisor(this, ${advisorId})`))) {
            const nameElement = item.querySelector('.advisor-info div:first-child');
            nameElement.innerHTML = nameElement.textContent + ' <span class="primary-badge">หลัก</span>';
            
            // ซ่อนปุ่มตั้งเป็นครูที่ปรึกษาหลัก
            const setPrimaryButton = item.querySelector('.table-action-btn.success');
            if (setPrimaryButton) {
                setPrimaryButton.style.display = 'none';
            }
        }
    });
    
    showNotification('ตั้งเป็นครูที่ปรึกษาหลักแล้ว', 'success');
}

/**
 * ลบครูที่ปรึกษา
 */
function removeAdvisor(advisorId) {
    if (confirm(`ต้องการลบครูที่ปรึกษาออกจากชั้นเรียนนี้หรือไม่?`)) {
        // บันทึกการเปลี่ยนแปลง
        advisorsChanges.push({
            action: 'remove',
            teacher_id: advisorId
        });
        
        // ลบรายการจาก DOM
        const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
        currentAdvisorItems.forEach(item => {
            const actionButton = item.querySelector('.advisor-action button:last-child');
            if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
                item.remove();
            }
        });
        
        // ตรวจสอบว่ายังมีครูที่ปรึกษาในรายการหรือไม่
        const currentAdvisorsList = document.getElementById('currentAdvisorsList');
        if (currentAdvisorsList.children.length === 0) {
            currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        }
        
        showNotification('ลบครูที่ปรึกษาออกจากชั้นเรียนแล้ว', 'success');
    }
}

/**
 * บันทึกการเปลี่ยนแปลงครูที่ปรึกษา
 */
function saveAdvisorsChanges() {
    if (advisorsChanges.length === 0) {
        showNotification('ไม่มีการเปลี่ยนแปลง', 'info');
        closeModal('advisorsModal');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'manage_advisors');
    formData.append('class_id', currentClassId);
    formData.append('changes', JSON.stringify(advisorsChanges));
    
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('advisorsModal');
            reloadPage();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
    });
}

/**
 * แสดงโมดัลเลื่อนชั้นนักเรียน
 */
function showPromoteStudentsModal() {
    showModal('promoteStudentsModal');
}

/**
 * ยืนยันการเลื่อนชั้นนักเรียน
 */
function confirmPromoteStudents() {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการเลื่อนชั้นนักเรียน? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'promote_students');
    formData.append('from_academic_year_id', document.getElementById('fromAcademicYear').value);
    formData.append('to_academic_year_id', document.getElementById('toAcademicYear').value);
    formData.append('notes', document.getElementById('promotionNotes').value);
    
    showNotification('กำลังดำเนินการเลื่อนชั้นนักเรียน...', 'info');
    
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('promoteStudentsModal');
            reloadPage();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน', 'error');
    });
}

/**
 * กรองชั้นเรียน
 */
function filterClasses() {
    const academicYearFilter = document.getElementById('academicYearFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const departmentFilter = document.getElementById('departmentFilter').value;
    
    const classRows = document.querySelectorAll('.class-row');
    let visibleCount = 0;
    
    classRows.forEach(row => {
        const academicYear = row.getAttribute('data-academic-year');
        const level = row.getAttribute('data-level');
        const department = row.getAttribute('data-department');
        
        const academicYearMatch = !academicYearFilter || academicYear === academicYearFilter;
        const levelMatch = !levelFilter || level === levelFilter;
        const departmentMatch = !departmentFilter || department === departmentFilter;
        
        if (academicYearMatch && levelMatch && departmentMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // อัปเดตจำนวนรายการที่แสดง
    const countElement = document.getElementById('classCount');
    if (countElement) {
        countElement.textContent = visibleCount;
    }
}

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 */
function downloadClassReport() {
    window.location.href = `classes.php?action=download_report&class_id=${currentClassId}`;
}

/**
 * แสดงสถิติชั้นเรียน
 */
function showClassStatistics() {
    fetch('api/class_manager.php', {
        method: 'POST',
        body: new URLSearchParams({
            'action': 'get_class_statistics'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // แสดงสถิติในโมดัล
            document.getElementById('statisticsTitle').textContent = 'สถิติชั้นเรียนทั้งหมด';
            
            // สร้างกราฟ
            createStatisticsCharts(data.statistics);
            
            showModal('statisticsModal');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ', 'error');
    });
}

/**
 * แสดงโมดัล
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 */
function showNotification(message, type = 'info') {
    // สร้าง container ถ้ายังไม่มี
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // สร้างการแจ้งเตือน
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // เลือกไอคอนตามประเภท
    let icon = 'info';
    switch (type) {
        case 'success': icon = 'check_circle'; break;
        case 'warning': icon = 'warning'; break;
        case 'error': icon = 'error'; break;
    }
    
    notification.innerHTML = `
        <span class="material-icons notification-icon">${icon}</span>
        <div class="notification-message">${message}</div>
        <button class="notification-close"><span class="material-icons">close</span></button>
    `;
    
    // เพิ่มลงใน container
    notificationContainer.appendChild(notification);
    
    // ตั้งค่าปุ่มปิด
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        notification.classList.add('notification-closing');
        setTimeout(() => {
            if (notification.parentNode) {
                notificationContainer.removeChild(notification);
            }
        }, 300);
    });
    
    // ปิดอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('notification-closing');
            setTimeout(() => {
                if (notification.parentNode) {
                    notificationContainer.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * โหลดหน้าใหม่
 */
function reloadPage() {
    window.location.reload();
}
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
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อดึงข้อมูลจาก API
    // ตัวอย่างนี้จำลองข้อมูลที่มีอยู่แล้วในหน้า
    
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
 * ฮndle การ submit ฟอร์มแผนกวิชา
 */
function handleDepartmentFormSubmit(e) {
    e.preventDefault();
    
    const departmentId = document.getElementById('departmentId').value;
    const departmentName = document.getElementById('departmentName').value;
    
    // ตรวจสอบข้อมูล
    if (!departmentName) {
        showNotification('กรุณาระบุชื่อแผนกวิชา', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('department_id', departmentId);
    formData.append('department_name', departmentName);
    
    if (departmentId) {
        // กรณีแก้ไข
        updateDepartment(formData);
    } else {
        // กรณีเพิ่มใหม่
        createDepartment(formData);
    }
}

/**
 * สร้างแผนกวิชาใหม่
 */
function createDepartment(formData) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งข้อมูลไปยัง API
    
    // จำลองการส่งข้อมูล
    console.log('สร้างแผนกวิชาใหม่', Object.fromEntries(formData));
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    setTimeout(() => {
        showNotification('เพิ่มแผนกวิชาใหม่สำเร็จ', 'success');
        closeModal('departmentModal');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 500);
}

/**
 * อัปเดตแผนกวิชา
 */
function updateDepartment(formData) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งข้อมูลไปยัง API
    
    // จำลองการส่งข้อมูล
    console.log('อัปเดตแผนกวิชา', Object.fromEntries(formData));
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    setTimeout(() => {
        showNotification('อัปเดตแผนกวิชาสำเร็จ', 'success');
        closeModal('departmentModal');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 500);
}

/**
 * ลบแผนกวิชา
 */
function deleteDepartment(departmentId) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งคำขอลบไปยัง API
    
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบแผนกวิชารหัส <strong>${departmentId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบแผนกวิชาจะส่งผลต่อชั้นเรียนและนักเรียนทั้งหมดในแผนกนี้
    `;
    
    deleteCallback = () => {
        // จำลองการส่งคำขอลบ
        console.log('ลบแผนกวิชา', departmentId);
        
        // จำลองการตอบสนองจากเซิร์ฟเวอร์
        setTimeout(() => {
            showNotification('ลบแผนกวิชาสำเร็จ', 'success');
            closeModal('confirmDeleteModal');
            
            // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
            reloadPage();
        }, 500);
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

/**
 * ดูรายละเอียดแผนกวิชา
 */
function viewDepartmentDetails(departmentId) {
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API แล้วแสดงรายละเอียด
    console.log('ดูรายละเอียดแผนกวิชา', departmentId);
    
    // ควรเปิดหน้ารายละเอียดแผนกวิชาหรือโมดัลแสดงรายละเอียด
    // สำหรับตัวอย่างนี้จะแสดงแค่ข้อความแจ้งเตือน
    showNotification(`กำลังโหลดข้อมูลแผนกวิชารหัส ${departmentId}`, 'info');
}

/**
 * จัดการการ submit ฟอร์มชั้นเรียน
 */
function handleClassFormSubmit(e) {
    e.preventDefault();
    
    const classId = document.getElementById('classId').value;
    const academicYearId = document.getElementById('academicYear').value;
    const level = document.getElementById('classLevel').value;
    const department = document.getElementById('classDepartment').value;
    const groupNumber = document.getElementById('groupNumber').value;
    
    // ตรวจสอบข้อมูล
    if (!academicYearId || !level || !department || !groupNumber) {
        showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('class_id', classId);
    formData.append('academic_year_id', academicYearId);
    formData.append('level', level);
    formData.append('department', department);
    formData.append('group_number', groupNumber);
    
    if (classId) {
        // กรณีแก้ไข
        updateClass(formData);
    } else {
        // กรณีเพิ่มใหม่
        createClass(formData);
    }
}

/**
 * สร้างชั้นเรียนใหม่
 */
function createClass(formData) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งข้อมูลไปยัง API
    
    // จำลองการส่งข้อมูล
    console.log('สร้างชั้นเรียนใหม่', Object.fromEntries(formData));
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    setTimeout(() => {
        showNotification('เพิ่มชั้นเรียนใหม่สำเร็จ', 'success');
        closeModal('addClassModal');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 500);
}

/**
 * อัปเดตชั้นเรียน
 */
function updateClass(formData) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งข้อมูลไปยัง API
    
    // จำลองการส่งข้อมูล
    console.log('อัปเดตชั้นเรียน', Object.fromEntries(formData));
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    setTimeout(() => {
        showNotification('อัปเดตชั้นเรียนสำเร็จ', 'success');
        closeModal('addClassModal');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 500);
}

/**
 * ลบชั้นเรียน
 */
function deleteClass(classId) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อส่งคำขอลบไปยัง API
    
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียนรหัส <strong>${classId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนทั้งหมดในชั้นเรียนนี้
    `;
    
    deleteCallback = () => {
        // จำลองการส่งคำขอลบ
        console.log('ลบชั้นเรียน', classId);
        
        // จำลองการตอบสนองจากเซิร์ฟเวอร์
        setTimeout(() => {
            showNotification('ลบชั้นเรียนสำเร็จ', 'success');
            closeModal('confirmDeleteModal');
            
            // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
            reloadPage();
        }, 500);
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

/**
 * ดูรายละเอียดชั้นเรียน
 */
function showClassDetails(classId) {
    currentClassId = classId;
    
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API
    fetchClassDetails(classId)
        .then(classData => {
            // เติมข้อมูลพื้นฐาน
            document.getElementById('classDetailsTitle').textContent = `รายละเอียดชั้น ${classData.level} กลุ่ม ${classData.group_number} ${classData.department}`;
            document.getElementById('detailAcademicYear').textContent = classData.academic_year;
            document.getElementById('detailLevel').textContent = classData.level;
            document.getElementById('detailDepartment').textContent = classData.department;
            document.getElementById('detailGroup').textContent = classData.group_number;
            document.getElementById('detailStudentCount').textContent = `${classData.student_count} คน`;
            
            // เติมข้อมูลครูที่ปรึกษา
            populateAdvisorsList(classData.advisors);
            
            // เติมข้อมูลนักเรียน
            populateStudentsList(classData.students);
            
            // แสดงโมดัล
            showModal('classDetailsModal');
            
            // สร้างกราฟเมื่อโมดัลแสดงแล้ว
            setTimeout(() => {
                createAttendanceCharts(classData);
            }, 300);
        })
        .catch(error => {
            console.error('ไม่สามารถโหลดข้อมูลชั้นเรียนได้', error);
            showNotification('ไม่สามารถโหลดข้อมูลชั้นเรียนได้', 'error');
        });
}

/**
 * ดึงข้อมูลรายละเอียดชั้นเรียน
 */
function fetchClassDetails(classId) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อดึงข้อมูลจาก API
    
    // จำลองการดึงข้อมูล
    return new Promise((resolve) => {
        setTimeout(() => {
            // จำลองข้อมูลที่ได้รับจาก API
            resolve({
                academic_year: '2568 (ภาคเรียนที่ 1)',
                level: 'ปวช.2',
                department: 'เทคโนโลยีสารสนเทศ',
                group_number: '1',
                student_count: 32,
                advisors: [
                    { id: 1, name: 'นายมนตรี ศรีสุข', position: 'ครูจ้างสอน', is_primary: true },
                    { id: 2, name: 'นางสาวใจดี มีเมตตา', position: 'ครูประจำ', is_primary: false }
                ],
                students: [
                    { id: 1, code: '12345678910', name: 'นายทดสอบ ระบบดี', attendance: 38, total: 40, percent: 95, status: 'ปกติ' },
                    { id: 2, code: '12345678911', name: 'นายทดลอง การเขียน', attendance: 32, total: 40, percent: 80, status: 'ต้องระวัง' },
                    { id: 3, code: '12345678912', name: 'นางสาวทดสอบ การเขียน', attendance: 24, total: 40, percent: 60, status: 'เสี่ยง' }
                ],
                monthly_stats: [
                    { month: 'ม.ค.', present: 90, absent: 10 },
                    { month: 'ก.พ.', present: 85, absent: 15 },
                    { month: 'มี.ค.', present: 88, absent: 12 },
                    { month: 'เม.ย.', present: 92, absent: 8 },
                    { month: 'พ.ค.', present: 94, absent: 6 }
                ]
            });
        }, 300);
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
function createAttendanceCharts(classData) {
    // ในสถานการณ์จริง ควรใช้ Chart.js หรือไลบรารีการทำกราฟอื่นๆ
    
    // กราฟสรุปการเข้าแถว
    const overallChart = document.getElementById('classAttendanceChart');
    if (overallChart) {
        // คำนวณอัตราการเข้าแถวรวม
        const totalPresent = classData.students.reduce((sum, student) => sum + student.attendance, 0);
        const totalPossible = classData.students.reduce((sum, student) => sum + student.total, 0);
        const attendanceRate = (totalPresent / totalPossible * 100).toFixed(1);
        
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
                        ${attendanceRate}% <small style="font-size: 14px;">เข้าแถว</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    // กราฟรายเดือน
    const monthlyChart = document.getElementById('monthlyAttendanceChart');
    if (monthlyChart) {
        let barsHtml = '';
        const months = classData.monthly_stats.map(item => item.month);
        const presentRates = classData.monthly_stats.map(item => (item.present / (item.present + item.absent) * 100).toFixed(1));
        
        for (let i = 0; i < months.length; i++) {
            barsHtml += `
                <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                    <div style="height: ${presentRates[i] * 1.5}px; width: 30px; background-color: #4caf50; margin-bottom: 5px;"></div>
                    <div style="font-size: 12px;">${months[i]}</div>
                    <div style="font-size: 10px;">${presentRates[i]}%</div>
                </div>
            `;
        }
        
        monthlyChart.innerHTML = `
            <div style="padding: 20px;">
                <div style="display: flex; align-items: flex-end; justify-content: space-around; height: 180px;">
                    ${barsHtml}
                </div>
            </div>
        `;
    }
}

/**
 * เปิดโมดัลจัดการครูที่ปรึกษา
 */
function manageAdvisors(classId) {
    currentClassId = classId;
    advisorsChanges = []; // รีเซ็ตการเปลี่ยนแปลง
    
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API
    fetchClassAdvisors(classId)
        .then(data => {
            document.getElementById('advisorsClassTitle').textContent = data.className;
            
            // เติมข้อมูลครูที่ปรึกษาปัจจุบัน
            renderCurrentAdvisors(data.advisors);
            
            // แสดงโมดัล
            showModal('advisorsModal');
        })
        .catch(error => {
            console.error('ไม่สามารถโหลดข้อมูลครูที่ปรึกษาได้', error);
            showNotification('ไม่สามารถโหลดข้อมูลครูที่ปรึกษาได้', 'error');
        });
}

/**
 * ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
 */
function fetchClassAdvisors(classId) {
    // ในสถานการณ์จริง ควรใช้ AJAX เพื่อดึงข้อมูลจาก API
    
    // จำลองการดึงข้อมูล
    return new Promise((resolve) => {
        setTimeout(() => {
            // จำลองข้อมูลที่ได้รับจาก API
            resolve({
                className: 'ปวช.2 กลุ่ม 1 เทคโนโลยีสารสนเทศ',
                advisors: [
                    { id: 1, name: 'นายมนตรี ศรีสุข', position: 'ครูจ้างสอน', is_primary: true },
                    { id: 2, name: 'นางสาวใจดี มีเมตตา', position: 'ครูประจำ', is_primary: false }
                ]
            });
        }, 300);
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
        return !(change.action === 'add' && change.teacher_id === advisorId);
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
        button.style.display = 'none';
    });
    
    // ตั้งครูคนนี้เป็นครูที่ปรึกษาหลัก
    const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
    currentAdvisorItems.forEach(item => {
        const actionButton = item.querySelector('.advisor-action button:last-child');
        if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
            const nameElement = item.querySelector('.advisor-info div:first-child');
            nameElement.innerHTML = nameElement.textContent + ' <span class="primary-badge">หลัก</span>';
            
            // ซ่อนปุ่มตั้งเป็นครูที่ปรึกษาหลัก
            const setPrimaryButton = item.querySelector('.table-action-btn.success');
            if (setPrimaryButton) {
                setPrimaryButton.style.display = 'none';
            }
        } else {
            // แสดงปุ่มตั้งเป็นครูที่ปรึกษาหลัก
            const setPrimaryButton = item.querySelector('.table-action-btn.success');
            if (setPrimaryButton) {
                setPrimaryButton.style.display = '';
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
    
    // ในสถานการณ์จริง ควรส่งข้อมูลไปยัง API
    
    // จำลองการส่งข้อมูล
    console.log('บันทึกการเปลี่ยนแปลงครูที่ปรึกษา:', {
        class_id: currentClassId,
        changes: advisorsChanges
    });
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    setTimeout(() => {
        showNotification('บันทึกการเปลี่ยนแปลงครูที่ปรึกษาเรียบร้อยแล้ว', 'success');
        closeModal('advisorsModal');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 500);
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
    
    // ในสถานการณ์จริง ควรส่งคำขอไปยัง API
    
    // จำลองการส่งคำขอ
    console.log('กำลังดำเนินการเลื่อนชั้นนักเรียน');
    
    // จำลองการตอบสนองจากเซิร์ฟเวอร์
    showNotification('กำลังดำเนินการเลื่อนชั้นนักเรียน...', 'info');
    
    setTimeout(() => {
        closeModal('promoteStudentsModal');
        showNotification('เลื่อนชั้นนักเรียนสำเร็จแล้ว', 'success');
        
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
        reloadPage();
    }, 1500);
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
    // ในสถานการณ์จริง ควรเรียก API เพื่อสร้างและดาวน์โหลดรายงาน
    
    showNotification(`กำลังสร้างรายงานชั้นเรียนรหัส ${currentClassId}...`, 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showNotification('ดาวน์โหลดรายงานสำเร็จ', 'success');
    }, 1000);
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





/**
 * เพิ่ม JavaScript สำหรับจัดการเลือกแผนกวิชา
 */
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบเลขบัตรประชาชนในฟอร์มเพิ่มครู
    const nationalIdInput = document.getElementById('teacher_national_id');
    if (nationalIdInput) {
        nationalIdInput.addEventListener('blur', function() {
            if (this.value.length === 13) {
                checkNationalIdDuplicate(this.value);
            }
        });
    }
    
    // ตรวจสอบเลขบัตรประชาชนในฟอร์มแก้ไขครู
    const editNationalIdInput = document.getElementById('editTeacherNationalId');
    if (editNationalIdInput) {
        editNationalIdInput.addEventListener('blur', function() {
            if (this.value.length === 13) {
                const teacherId = document.getElementById('editTeacherId').value;
                checkNationalIdDuplicate(this.value, teacherId);
            }
        });
    }
    
    // โหลดข้อมูลแผนกวิชา
    loadDepartments();
});

/**
 * โหลดข้อมูลแผนกวิชามาใส่ใน dropdown
 */
function loadDepartments() {
    // ดึงข้อมูลแผนกวิชาผ่าน AJAX
    fetch('get_departments.php')
    .then(response => response.json())
    .then(data => {
        // ถ้าไม่มีข้อมูลก็ไม่ต้องทำอะไร
        if (!data || data.length === 0) return;
        
        // หา select element ของแผนกวิชา
        const departmentSelects = document.querySelectorAll('select[name="teacher_department"], select[id="teacher_department"], select[id="editTeacherDepartment"], select[id="filterDepartment"]');
        
        departmentSelects.forEach(select => {
            // เก็บค่าที่เลือกไว้ก่อน
            const selectedValue = select.value;
            
            // ล้างตัวเลือกเดิมยกเว้นตัวเลือกแรก (-- เลือกแผนก --)
            const firstOption = select.options[0];
            select.innerHTML = '';
            select.appendChild(firstOption);
            
            // เพิ่มตัวเลือกใหม่
            data.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept.department_id || dept.department_name;
                option.textContent = dept.department_name;
                select.appendChild(option);
            });
            
            // ถ้ามีค่าที่เลือกไว้ก่อนหน้านี้ ให้เลือกค่านั้น
            if (selectedValue) {
                select.value = selectedValue;
            }
        });
    })
    .catch(error => {
        console.error('Error loading departments:', error);
    });
}

/**
 * ตรวจสอบเลขบัตรประชาชนซ้ำในระบบผ่าน AJAX
 * 
 * @param {string} nationalId เลขบัตรประชาชน
 * @param {string|null} excludeId รหัสครูที่ยกเว้น (กรณีแก้ไข)
 */
function checkNationalIdDuplicate(nationalId, excludeId = null) {
    // สร้าง AJAX request
    fetch('check_duplicate_teacher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `national_id=${encodeURIComponent(nationalId)}${excludeId ? `&exclude_id=${encodeURIComponent(excludeId)}` : ''}`
    })
    .then(response => response.json())
    .then(data => {
        // ถ้าพบข้อมูลซ้ำ
        if (data.duplicate) {
            // กำหนด input field ที่ต้องการแจ้งเตือน
            const inputField = excludeId 
                ? document.getElementById('editTeacherNationalId') 
                : document.getElementById('teacher_national_id');
            
            if (inputField) {
                inputField.classList.add('is-invalid');
                
                // หา feedback element
                const feedbackElement = inputField.nextElementSibling;
                if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
                    feedbackElement.textContent = 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว';
                }
                
                // แสดงข้อความแจ้งเตือน
                showDuplicateAlert(nationalId, data.teacher_detail);
            }
        }
    })
    .catch(error => {
        console.error('Error checking duplicate:', error);
    });
}

/**
 * แสดงข้อความแจ้งเตือนกรณีพบข้อมูลซ้ำ
 * 
 * @param {string} nationalId เลขบัตรประชาชน
 * @param {object} teacherDetail ข้อมูลของครูที่มีเลขบัตรประชาชนซ้ำ (ถ้ามี)
 */
function showDuplicateAlert(nationalId, teacherDetail = null) {
    // สร้าง element สำหรับแจ้งเตือน
    const alertElement = document.createElement('div');
    alertElement.className = 'alert alert-danger alert-dismissible fade show mt-3';
    alertElement.role = 'alert';
    
    // กำหนดเนื้อหาของแจ้งเตือน
    let messageHtml = `
        <div class="d-flex align-items-center">
            <span class="material-icons me-2">error</span>
            <div>
                <strong>พบข้อมูลซ้ำ!</strong> เลขบัตรประชาชน ${nationalId} มีอยู่ในระบบแล้ว
    `;
    
    // ถ้ามีข้อมูลครู ให้แสดงรายละเอียดเพิ่มเติม
    if (teacherDetail) {
        messageHtml += `<br>เป็นของ ${teacherDetail.name} แผนก${teacherDetail.department || '-'}`;
    }
    
    messageHtml += `
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertElement.innerHTML = messageHtml;
    
    // หา container สำหรับใส่แจ้งเตือน
    const modalBody = document.querySelector('.modal-body');
    if (modalBody) {
        // ตรวจสอบว่ามีแจ้งเตือนอยู่แล้วหรือไม่
        const existingAlert = modalBody.querySelector('.alert-danger');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // เพิ่มแจ้งเตือนใหม่
        modalBody.insertBefore(alertElement, modalBody.firstChild);
    }
}

/**
 * สร้างไฟล์ get_departments.php สำหรับดึงข้อมูลแผนกวิชา
 * 
 * <?php
 * // get_departments.php - ดึงข้อมูลแผนกวิชาทั้งหมด
 * 
 * // เริ่ม session
 * session_start();
 * 
 * // โหลดไฟล์การเชื่อมต่อฐานข้อมูล
 * require_once '../db_connect.php';
 * require_once '../models/Teacher.php';
 * 
 * // สร้าง instance ของ Teacher
 * $teacherModel = new Teacher();
 * 
 * // ดึงข้อมูลแผนกวิชา
 * $departments = $teacherModel->getDepartmentsWithIds();
 * 
 * // ส่งข้อมูลกลับในรูปแบบ JSON
 * header('Content-Type: application/json');
 * echo json_encode($departments);
 * exit;
 * ?>
 */
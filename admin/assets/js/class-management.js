/**
 * class-management.js - JavaScript สำหรับจัดการชั้นเรียนและครูที่ปรึกษา
 * 
 * ไฟล์นี้เน้นฟังก์ชันสำหรับการจัดการชั้นเรียน ครูที่ปรึกษา และการเลื่อนชั้น
 */

// ตัวแปรสำหรับเก็บข้อมูลชั่วคราว
let currentClassId = null;
let advisorsChanges = [];
let classCharts = {};

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners สำหรับหน้าชั้นเรียน
    setupClassEventListeners();
    
    // เตรียมโมดัลเลื่อนชั้นนักเรียน (ถ้ามี)
    if (document.getElementById('promoteStudentsModal')) {
        setupPromotionChart();
    }
    
    console.log("Class management functions loaded successfully");
});

/**
 * ตั้งค่า Event Listeners สำหรับหน้าชั้นเรียน
 */
function setupClassEventListeners() {
    // ลงทะเบียนฟอร์มเพิ่ม/แก้ไขชั้นเรียน
    const classForm = document.getElementById('classForm');
    if (classForm) {
        classForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveClass();
        });
    }
    
    // ตัวกรองชั้นเรียน
    const filterControls = document.querySelectorAll('.filter-box select');
    filterControls.forEach(control => {
        control.addEventListener('change', filterClasses);
    });
    
    // ค้นหาครูที่ปรึกษา
    const teacherSearch = document.getElementById('teacherSearch');
    if (teacherSearch) {
        teacherSearch.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const options = document.querySelectorAll('#advisorSelect option');
            
            options.forEach(option => {
                if (option.value === '') return; // ข้ามตัวเลือกแรก
                
                const text = option.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    }
}

/**
 * แสดงโมดัลเพิ่มชั้นเรียน
 */
function showAddClassModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('classForm');
    if (form) form.reset();
    
    // ตั้งค่าหัวข้อและข้อมูลเริ่มต้น
    document.getElementById('classModalTitle').textContent = 'เพิ่มชั้นเรียนใหม่';
    document.getElementById('classId').value = '';
    
    // ตั้งค่าปีการศึกษาปัจจุบันเป็นค่าเริ่มต้น
    const activeYear = document.querySelector('#academicYear option[data-active="true"]');
    if (activeYear) {
        document.getElementById('academicYear').value = activeYear.value;
    }
    
    // แสดงโมดัล
    showModal('classModal');
}

/**
 * บันทึกข้อมูลชั้นเรียน
 */
function saveClass() {
    // ดึงข้อมูลจากฟอร์ม
    const classId = document.getElementById('classId').value;
    const academicYearId = document.getElementById('academicYear').value;
    const level = document.getElementById('classLevel').value;
    const departmentId = document.getElementById('classDepartment').value;
    const groupNumber = document.getElementById('groupNumber').value;
    const classroom = document.getElementById('classroom').value;
    
    // ตรวจสอบข้อมูล
    if (!academicYearId || !level || !departmentId || !groupNumber) {
        showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    
    if (classId) {
        // กรณีแก้ไข
        formData.append('form_action', 'edit_class');
        formData.append('class_id', classId);
    } else {
        // กรณีเพิ่มใหม่
        formData.append('form_action', 'add_class');
    }
    
    formData.append('academic_year_id', academicYearId);
    formData.append('level', level);
    formData.append('department_id', departmentId);
    formData.append('group_number', groupNumber);
    formData.append('classroom', classroom);
    
    // แสดงสถานะกำลังบันทึก
    const saveBtn = document.querySelector('#classModal .modal-footer .btn-primary');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังบันทึก...';
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('classes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการส่งข้อมูล');
        }
        return response.text();
    })
    .then(data => {
        closeModal('classModal');
        showNotification(classId ? 'แก้ไขชั้นเรียนสำเร็จ' : 'เพิ่มชั้นเรียนสำเร็จ', 'success');
        
        // รีโหลดหน้าเพื่อแสดงข้อมูลล่าสุด
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
        
        // คืนค่าปุ่มบันทึก
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnText;
    });
}

/**
 * แก้ไขชั้นเรียน
 */
function editClass(classId) {
    currentClassId = classId;
    
    // ส่ง AJAX request เพื่อดึงข้อมูลชั้นเรียน
    fetch(`api/class_manager.php?action=get_class&class_id=${classId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // ตั้งค่าหัวข้อและข้อมูลในฟอร์ม
            document.getElementById('classModalTitle').textContent = 'แก้ไขชั้นเรียน';
            document.getElementById('classId').value = classId;
            document.getElementById('academicYear').value = data.class.academic_year_id;
            document.getElementById('classLevel').value = data.class.level;
            document.getElementById('classDepartment').value = data.class.department_id;
            document.getElementById('groupNumber').value = data.class.group_number;
            document.getElementById('classroom').value = data.class.classroom || '';
            
            // แสดงโมดัล
            showModal('classModal');
        } else {
            showNotification(data.message || 'ไม่สามารถโหลดข้อมูลชั้นเรียนได้', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // กรณีไม่สามารถเรียก API ได้ ให้ลองใช้ข้อมูลจาก DOM แทน
        console.log("Falling back to DOM data for class editing");
        
        // หาข้อมูลชั้นเรียนจากตาราง
        const classRow = document.querySelector(`.class-row[data-class-id="${classId}"]`);
        if (classRow) {
            const academicYearId = classRow.getAttribute('data-academic-year');
            const level = classRow.getAttribute('data-level');
            const departmentId = classRow.getAttribute('data-department-id');
            const groupNumber = classRow.querySelector('.class-name').textContent.split(' กลุ่ม ')[1];
            
            document.getElementById('classModalTitle').textContent = 'แก้ไขชั้นเรียน';
            document.getElementById('classId').value = classId;
            
            // ตั้งค่าฟอร์ม
            if (academicYearId) document.getElementById('academicYear').value = academicYearId;
            if (level) document.getElementById('classLevel').value = level;
            if (departmentId) document.getElementById('classDepartment').value = departmentId;
            if (groupNumber) document.getElementById('groupNumber').value = groupNumber;
            
            // แสดงโมดัล
            showModal('classModal');
        } else {
            showNotification('ไม่พบข้อมูลชั้นเรียน', 'error');
        }
    });
}

/**
 * ลบชั้นเรียน
 */
function deleteClass(classId) {
    // ตรวจสอบว่ามีการส่งรหัสชั้นเรียนมาหรือไม่
    if (!classId) {
        showNotification('ไม่พบรหัสชั้นเรียน', 'error');
        return;
    }
    
    // หาข้อมูลชั้นเรียนจากตาราง
    let className = '';
    const classRows = document.querySelectorAll('.class-row');
    for (const row of classRows) {
        if (row.getAttribute('data-class-id') == classId) {
            className = row.querySelector('.class-name').textContent;
            break;
        }
    }
    
    // แสดงข้อความยืนยันการลบ
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียน <strong>${className}</strong> (รหัส ${classId}) ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนและข้อมูลการเข้าแถวทั้งหมดในชั้นเรียนนี้
    `;
    
    // กำหนดฟังก์ชันสำหรับปุ่มยืนยันการลบ
    deleteCallback = function() {
        // เตรียมข้อมูลสำหรับส่ง
        const formData = new FormData();
        formData.append('form_action', 'delete_class');
        formData.append('class_id', classId);
        
        // แสดงสถานะการลบ
        const deleteBtn = document.getElementById('confirmDeleteButton');
        const originalBtnText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังลบ...';
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์
        fetch('classes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
            return response.text();
        })
        .then(data => {
            closeModal('confirmDeleteModal');
            showNotification('ลบชั้นเรียนสำเร็จ', 'success');
            
            // รีโหลดหน้าเพื่อแสดงข้อมูลล่าสุด
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            
            // คืนค่าปุ่มลบ
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalBtnText;
        });
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    
    // แสดงโมดัลยืนยันการลบ
    showModal('confirmDeleteModal');
}

/**
 * แสดงรายละเอียดชั้นเรียน
 */
function showClassDetails(classId) {
    currentClassId = classId;
    
    // ส่ง AJAX request เพื่อดึงข้อมูลรายละเอียดชั้นเรียน
    fetch(`api/class_manager.php?action=get_class_details&class_id=${classId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // เติมข้อมูลพื้นฐาน
            document.getElementById('classDetailsTitle').textContent = `รายละเอียดชั้น ${data.class.level} กลุ่ม ${data.class.group_number} ${data.class.department_name}`;
            document.getElementById('detailAcademicYear').textContent = `${data.class.year} (ภาคเรียนที่ ${data.class.semester})`;
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
            showNotification(data.message || 'ไม่สามารถโหลดข้อมูลชั้นเรียนได้', 'error');
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
    
    if (!advisors || advisors.length === 0) {
        advisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="badge badge-primary">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position || 'ครูผู้สอน'}</div>
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
    
    if (!students || students.length === 0) {
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
function createAttendanceCharts(attendanceStats) {
    // ถ้ามีชาร์ตเก่าให้ทำลายก่อน
    if (classCharts.attendanceChart) {
        classCharts.attendanceChart.destroy();
    }
    if (classCharts.monthlyChart) {
        classCharts.monthlyChart.destroy();
    }
    
    // กราฟสรุปการเข้าแถว (Doughnut Chart)
    const ctx1 = document.getElementById('classAttendanceChart').getContext('2d');
    classCharts.attendanceChart = new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['เข้าแถว', 'ขาด'],
            datasets: [{
                data: [attendanceStats.present_days, attendanceStats.absent_days],
                backgroundColor: ['#2ecc71', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: `อัตราการเข้าแถว ${attendanceStats.overall_rate.toFixed(1)}%`,
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label;
                            const value = context.raw;
                            const total = attendanceStats.present_days + attendanceStats.absent_days;
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} วัน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // กราฟรายเดือน (Bar Chart)
    const monthlyData = attendanceStats.monthly || [];
    
    // เตรียมข้อมูลสำหรับกราฟ
    const months = monthlyData.map(item => item.month);
    const presentData = monthlyData.map(item => item.present);
    const absentData = monthlyData.map(item => item.absent);
    
    const ctx2 = document.getElementById('monthlyAttendanceChart').getContext('2d');
    classCharts.monthlyChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'เข้าแถว',
                    data: presentData,
                    backgroundColor: '#2ecc71',
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                },
                {
                    label: 'ขาด',
                    data: absentData,
                    backgroundColor: '#e74c3c',
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [2, 4]
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'สถิติการเข้าแถวรายเดือน',
                    font: {
                        size: 16
                    }
                }
            }
        }
    });
}

/**
 * แสดงโมดัลจัดการครูที่ปรึกษา
 */
function manageAdvisors(classId) {
    currentClassId = classId;
    advisorsChanges = []; // รีเซ็ตการเปลี่ยนแปลง
    
    // ส่ง AJAX request เพื่อดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
    fetch(`api/class_manager.php?action=get_class_advisors&class_id=${classId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('advisorsClassTitle').textContent = data.class_name;
            
            // เติมข้อมูลครูที่ปรึกษาปัจจุบัน
            renderCurrentAdvisors(data.advisors);
            
            // รีเซ็ตการแสดงการเปลี่ยนแปลง
            document.getElementById('changesLog').innerHTML = '<div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>';
            
            // แสดงโมดัล
            showModal('advisorsModal');
        } else {
            showNotification(data.message || 'ไม่สามารถโหลดข้อมูลครูที่ปรึกษาได้', 'error');
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
    
    if (!advisors || advisors.length === 0) {
        currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="badge badge-primary">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position || 'ครูผู้สอน'}</div>
            </div>
            <div class="advisor-action">
                ${!advisor.is_primary ? `
                <button class="table-action-btn primary" onclick="setAsPrimaryAdvisor(${advisor.id})">
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
    const isDuplicate = advisorsChanges.some(change => change.action === 'add' && change.teacher_id == advisorId);
    
    if (isDuplicate) {
        showNotification('ครูที่ปรึกษาท่านนี้มีอยู่ในรายการเพิ่มแล้ว', 'warning');
        return;
    }
    
    // ตรวจสอบว่ามีอยู่ในรายการปัจจุบันแล้วหรือไม่
    const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
    let isCurrentAdvisor = false;
    
    currentAdvisorItems.forEach(item => {
        const actionButton = item.querySelector('.advisor-action button:last-child');
        if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
            isCurrentAdvisor = true;
        }
    });
    
    if (isCurrentAdvisor) {
        showNotification('ครูที่ปรึกษาท่านนี้เป็นที่ปรึกษาของชั้นเรียนนี้อยู่แล้ว', 'warning');
        return;
    }
    
    // ถ้าตั้งเป็นที่ปรึกษาหลัก ให้ยกเลิกที่ปรึกษาหลักคนก่อนหน้า
    if (isPrimary) {
        // ลบการตั้งเป็นครูที่ปรึกษาหลักในการเปลี่ยนแปลงก่อนหน้า
        advisorsChanges = advisorsChanges.filter(change => change.action !== 'set_primary');
        
        // หากมีการเพิ่มครูที่ปรึกษาที่เป็นที่ปรึกษาหลัก ให้ยกเลิกการเป็นที่ปรึกษาหลัก
        advisorsChanges.forEach(change => {
            if (change.action === 'add') {
                change.is_primary = false;
            }
        });
    }
    
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'add',
        teacher_id: advisorId,
        is_primary: isPrimary
    });
    
    // อัพเดทการแสดงการเปลี่ยนแปลง
    updateChangesLog();
    
    // ดึงชื่อครูที่ปรึกษาจาก select
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
            <div>${advisorName} ${isPrimary ? '<span class="badge badge-primary">หลัก</span>' : ''}</div>
            <div class="advisor-position">เพิ่มใหม่</div>
        </div>
        <div class="advisor-action">
            ${!isPrimary ? `
            <button class="table-action-btn primary" onclick="setAsPrimaryAdvisor(${advisorId})">
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
 * อัพเดทการแสดงการเปลี่ยนแปลง
 */
function updateChangesLog() {
    const changesLog = document.getElementById('changesLog');
    
    if (!advisorsChanges || advisorsChanges.length === 0) {
        changesLog.innerHTML = '<div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>';
        return;
    }
    
    changesLog.innerHTML = '';
    advisorsChanges.forEach((change, index) => {
        const changeItem = document.createElement('div');
        changeItem.className = 'change-item';
        
        let iconClass = '';
        let changeText = '';
        
        // หาชื่อครูจาก select
        let teacherName = 'ครูรหัส ' + change.teacher_id;
        const teacherOption = document.querySelector(`#advisorSelect option[value="${change.teacher_id}"]`);
        if (teacherOption) {
            teacherName = teacherOption.textContent;
        } else {
            // ถ้าไม่พบในรายการ select ให้หาจากรายการที่ปรึกษาปัจจุบัน
            const advisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
            advisorItems.forEach(item => {
                const actionButton = item.querySelector('.advisor-action button:last-child');
                if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${change.teacher_id})`)) {
                    teacherName = item.querySelector('.advisor-info div:first-child').textContent.replace(' หลัก', '');
                }
            });
        }
        
        switch (change.action) {
            case 'add':
                iconClass = 'change-add';
                changeText = `<span class="material-icons">person_add</span> เพิ่ม ${teacherName} ${change.is_primary ? '(ที่ปรึกษาหลัก)' : ''}`;
                break;
            case 'remove':
                iconClass = 'change-remove';
                changeText = `<span class="material-icons">person_remove</span> ลบ ${teacherName}`;
                break;
            case 'set_primary':
                iconClass = 'change-primary';
                changeText = `<span class="material-icons">stars</span> ตั้ง ${teacherName} เป็นที่ปรึกษาหลัก`;
                break;
        }
        
        changeItem.innerHTML = `
            <div class="${iconClass}">
                ${changeText}
                <button class="btn-icon" onclick="removeChange(${index})">
                    <span class="material-icons">cancel</span>
                </button>
            </div>
        `;
        
        changesLog.appendChild(changeItem);
    });
}

/**
 * ลบการเปลี่ยนแปลง
 */
function removeChange(index) {
    if (index >= 0 && index < advisorsChanges.length) {
        advisorsChanges.splice(index, 1);
        updateChangesLog();
    }
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
    
    // อัพเดทการแสดงการเปลี่ยนแปลง
    updateChangesLog();
    
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
    // ลบการตั้งเป็นครูที่ปรึกษาหลักในการเปลี่ยนแปลงก่อนหน้า
    advisorsChanges = advisorsChanges.filter(change => change.action !== 'set_primary');
    
    // หากมีการเพิ่มครูที่ปรึกษาที่เป็นที่ปรึกษาหลัก ให้ยกเลิกการเป็นที่ปรึกษาหลัก
    advisorsChanges.forEach(change => {
        if (change.action === 'add') {
            change.is_primary = false;
        }
    });
    
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'set_primary',
        teacher_id: advisorId
    });
    
    // อัพเดทการแสดงการเปลี่ยนแปลง
    updateChangesLog();
    
    // ล้างครูที่ปรึกษาหลักเดิม
    const primaryBadges = document.querySelectorAll('#currentAdvisorsList .badge-primary');
    primaryBadges.forEach(badge => {
        badge.remove();
    });
    
    // แสดงปุ่มตั้งเป็นที่ปรึกษาหลักทั้งหมด
    const setPrimaryButtons = document.querySelectorAll('#currentAdvisorsList .table-action-btn.primary');
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
            nameElement.innerHTML = nameElement.textContent + ' <span class="badge badge-primary">หลัก</span>';
            
            // ซ่อนปุ่มตั้งเป็นครูที่ปรึกษาหลัก
            const setPrimaryButton = item.querySelector('.table-action-btn.primary');
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
        
        // อัพเดทการแสดงการเปลี่ยนแปลง
        updateChangesLog();
        
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
 * ยกเลิกการเปลี่ยนแปลงครูที่ปรึกษา
 */
function cancelAdvisorChanges() {
    if (advisorsChanges.length > 0) {
        if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการเปลี่ยนแปลงทั้งหมด?')) {
            advisorsChanges = [];
            closeModal('advisorsModal');
        }
    } else {
        closeModal('advisorsModal');
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
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('form_action', 'manage_advisors');
    formData.append('class_id', currentClassId);
    formData.append('changes', JSON.stringify(advisorsChanges));
    
    // แสดงสถานะกำลังบันทึก
    const saveBtn = document.querySelector('#advisorsModal .modal-footer .btn-primary');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังบันทึก...';
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('classes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการส่งข้อมูล');
        }
        return response.text();
    })
    .then(data => {
        closeModal('advisorsModal');
        showNotification('บันทึกการเปลี่ยนแปลงครูที่ปรึกษาสำเร็จ', 'success');
        
        // รีโหลดหน้าเพื่อแสดงข้อมูลล่าสุด
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
        
        // คืนค่าปุ่มบันทึก
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnText;
    });
}

/**
 * แสดงโมดัลเลื่อนชั้นนักเรียน
 */
function showPromoteStudentsModal() {
    // รีเซ็ตฟอร์ม
    if (document.getElementById('promotionNotes')) {
        document.getElementById('promotionNotes').value = '';
    }
    
    // แสดงโมดัล
    showModal('promoteStudentsModal');
    
    // สร้างกราฟ
    setTimeout(() => {
        setupPromotionChart();
    }, 300);
}

/**
 * สร้างกราฟแสดงจำนวนนักเรียนที่จะเลื่อนชั้น
 */
function setupPromotionChart() {
    const chartContainer = document.getElementById('promotionChart');
    if (!chartContainer) return;
    
    // ดึงข้อมูลจากตาราง
    const dataRows = document.querySelectorAll('#promotionCountsBody tr');
    if (dataRows.length === 0) {
        chartContainer.innerHTML = '<div class="text-muted">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>';
        return;
    }
    
    // สร้างข้อมูลสำหรับกราฟ
    const chartData = [];
    let totalStudents = 0;
    
    dataRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            const currentLevel = cells[0].textContent.trim();
            const studentCountText = cells[1].textContent.trim();
            const studentCount = parseInt(studentCountText.replace(/[^\d]/g, ''));
            const newLevel = cells[2].textContent.trim();
            
            if (!isNaN(studentCount)) {
                chartData.push({
                    level: currentLevel,
                    students: studentCount,
                    newLevel: newLevel
                });
                totalStudents += studentCount;
            }
        }
    });
    
    // สร้างกราฟแท่ง
    let chartHtml = '';
    
    chartData.forEach(item => {
        const barHeight = Math.max((item.students / totalStudents) * 200, 30); // ความสูงขั้นต่ำ 30px
        const percentage = ((item.students / totalStudents) * 100).toFixed(1);
        
        const barColor = item.newLevel === 'สำเร็จการศึกษา' 
            ? '#4caf50' // สีเขียวสำหรับนักเรียนที่จบการศึกษา
            : '#4a6cf7'; // สีฟ้าสำหรับนักเรียนที่เลื่อนชั้น
        
        chartHtml += `
            <div class="chart-bar-container">
                <div class="chart-bar" style="height: ${barHeight}px; background-color: ${barColor};">
                    <span class="chart-value">${item.students}</span>
                </div>
                <div class="chart-label">${item.level}</div>
                <div class="chart-percentage">${percentage}%</div>
            </div>
        `;
    });
    
    // เพิ่ม CSS เฉพาะสำหรับกราฟ
    chartHtml = `
        <style>
            .chart-wrapper {
                display: flex;
                height: 220px;
                align-items: flex-end;
                justify-content: space-around;
                margin-top: 20px;
            }
            
            .chart-bar-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 60px;
            }
            
            .chart-bar {
                width: 40px;
                min-height: 30px;
                border-radius: 4px 4px 0 0;
                position: relative;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                padding-top: 5px;
            }
            
            .chart-value {
                color: white;
                font-weight: bold;
                font-size: 12px;
            }
            
            .chart-label {
                margin-top: 5px;
                font-size: 12px;
                text-align: center;
            }
            
            .chart-percentage {
                font-size: 10px;
                color: #666;
            }
        </style>
        <div class="chart-wrapper">
            ${chartHtml}
        </div>
    `;
    
    chartContainer.innerHTML = chartHtml;
}

/**
 * ยืนยันการเลื่อนชั้นนักเรียน
 */
function confirmPromoteStudents() {
    // ตรวจสอบว่ามีปีการศึกษาปลายทางหรือไม่
    const toAcademicYear = document.getElementById('toAcademicYear').value;
    const fromAcademicYear = document.getElementById('fromAcademicYear').value;
    const promotionNotes = document.getElementById('promotionNotes').value;
    
    if (toAcademicYear === 'new') {
        showNotification('กรุณาเพิ่มปีการศึกษาใหม่ก่อนทำการเลื่อนชั้น', 'warning');
        return;
    }
    
    if (!toAcademicYear || !fromAcademicYear) {
        showNotification('กรุณาเลือกปีการศึกษาต้นทางและปลายทาง', 'warning');
        return;
    }
    
    if (!confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการเลื่อนชั้นนักเรียน? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        return;
    }
    
    // แสดงสถานะกำลังโหลด
    const promoteBtn = document.getElementById('promoteBtn');
    const originalBtnHtml = promoteBtn.innerHTML;
    promoteBtn.disabled = true;
    promoteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังดำเนินการ...';
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('form_action', 'promote_students');
    formData.append('from_academic_year_id', fromAcademicYear);
    formData.append('to_academic_year_id', toAcademicYear);
    formData.append('notes', promotionNotes);
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('classes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('เกิดข้อผิดพลาดในการส่งข้อมูล');
        }
        return response.text();
    })
    .then(data => {
        closeModal('promoteStudentsModal');
        showNotification('เลื่อนชั้นนักเรียนสำเร็จ', 'success');
        
        // รีโหลดหน้าหลังจากเลื่อนชั้นสำเร็จ
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
        
        // คืนค่าปุ่มเลื่อนชั้น
        promoteBtn.disabled = false;
        promoteBtn.innerHTML = originalBtnHtml;
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
    
    showNotification(`กรองข้อมูลสำเร็จ แสดง ${visibleCount} รายการ`, 'info');
}

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 */
function downloadClassReport() {
    if (!currentClassId) {
        showNotification('ไม่พบรหัสชั้นเรียน', 'error');
        return;
    }
    
    window.location.href = `classes.php?action=download_report&class_id=${currentClassId}`;
}
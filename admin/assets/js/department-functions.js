/**
 * department-functions.js - JavaScript สำหรับจัดการแผนกวิชา
 * 
 * ไฟล์นี้รวมฟังก์ชันเฉพาะสำหรับการจัดการแผนกวิชา
 * เพื่อให้แยกจากการจัดการชั้นเรียนอย่างชัดเจน
 */

// ตัวแปรสำหรับเก็บข้อมูลชั่วคราว
let currentDepartmentId = null;
let deleteCallback = null;
let departmentCharts = {};

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    setupDepartmentEventListeners();
    
    // สร้างกราฟสำหรับแผนกวิชา (ถ้ามี)
    if (document.getElementById('departmentStudentChart')) {
        createDepartmentCharts();
    }
    
    console.log("Department management functions loaded successfully");
});

/**
 * ตั้งค่า Event Listeners สำหรับหน้าแผนกวิชา
 */
function setupDepartmentEventListeners() {
    // ลงทะเบียนฟอร์มเพิ่ม/แก้ไขแผนกวิชา
    const departmentForm = document.getElementById('departmentForm');
    if (departmentForm) {
        departmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveDepartment();
        });
    }
    
    // ปุ่มปิดโมดัลทั้งหมด
    document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
    
    // ปิดโมดัลเมื่อคลิกภายนอก
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal(this.id);
            }
        });
    });
}

/**
 * แสดงโมดัลเพิ่มแผนกวิชา
 */
function showDepartmentModal() {
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('departmentForm');
    if (form) form.reset();
    
    // ตั้งค่าหัวข้อและข้อมูลเริ่มต้น
    document.getElementById('departmentModalTitle').textContent = 'เพิ่มแผนกวิชาใหม่';
    document.getElementById('departmentId').value = '';
    
    // แสดงฟิลด์รหัสแผนกวิชาสำหรับการเพิ่มใหม่
    const codeGroup = document.getElementById('departmentCode').parentElement;
    if (codeGroup) codeGroup.style.display = 'block';
    
    // ทำให้ฟิลด์รหัสแผนกสามารถแก้ไขได้
    document.getElementById('departmentCode').readOnly = false;
    
    // แสดงโมดัล
    showModal('departmentModal');
}

/**
 * แก้ไขแผนกวิชา
 */
function editDepartment(departmentId) {
    currentDepartmentId = departmentId;
    
    // หาข้อมูลแผนกวิชาจากตาราง
    let departmentName = '';
    let departmentCode = departmentId; // รหัสแผนกคือ departmentId ที่ส่งมา
    
    const departmentRows = document.querySelectorAll('#departmentTableBody tr');
    for (const row of departmentRows) {
        if (row.cells[0].textContent === departmentId) {
            departmentName = row.cells[1].textContent;
            break;
        }
    }
    
    // ตั้งค่าหัวข้อและข้อมูลในฟอร์ม
    document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
    document.getElementById('departmentId').value = departmentId;
    document.getElementById('departmentName').value = departmentName;
    document.getElementById('departmentCode').value = departmentCode;
    
    // ปิดการแก้ไขรหัสแผนกวิชาสำหรับแผนกที่มีอยู่แล้ว
    document.getElementById('departmentCode').readOnly = true;
    
    // แสดงโมดัล
    showModal('departmentModal');
}

/**
 * บันทึกข้อมูลแผนกวิชา (เพิ่มใหม่หรือแก้ไข)
 */
function saveDepartment() {
    // ดึงข้อมูลจากฟอร์ม
    const departmentId = document.getElementById('departmentId').value;
    const departmentName = document.getElementById('departmentName').value;
    const departmentCode = document.getElementById('departmentCode').value;
    
    // ตรวจสอบข้อมูล
    if (!departmentName) {
        showNotification('กรุณาระบุชื่อแผนกวิชา', 'warning');
        return;
    }
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    
    if (departmentId) {
        // กรณีแก้ไข
        formData.append('form_action', 'edit_department');
        formData.append('department_id', departmentId);
        formData.append('department_name', departmentName);
    } else {
        // กรณีเพิ่มใหม่
        formData.append('form_action', 'add_department');
        formData.append('department_name', departmentName);
        
        if (departmentCode) {
            formData.append('department_code', departmentCode);
        }
    }
    
    // แสดงสถานะกำลังบันทึก
    const saveBtn = document.querySelector('.modal-footer .btn-primary');
    const originalBtnText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังบันทึก...';
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('departments.php', {
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
        closeModal('departmentModal');
        showNotification(departmentId ? 'แก้ไขแผนกวิชาสำเร็จ' : 'เพิ่มแผนกวิชาสำเร็จ', 'success');
        
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
 * ลบแผนกวิชา
 */
function deleteDepartment(departmentId) {
    // ตรวจสอบว่ามีการส่งรหัสแผนกวิชามาหรือไม่
    if (!departmentId) {
        showNotification('ไม่พบรหัสแผนกวิชา', 'error');
        return;
    }
    
    // หาชื่อแผนกวิชาจากตาราง
    let departmentName = '';
    const departmentRows = document.querySelectorAll('#departmentTableBody tr');
    for (const row of departmentRows) {
        if (row.cells[0].textContent === departmentId) {
            departmentName = row.cells[1].textContent;
            break;
        }
    }
    
    // แสดงข้อความยืนยันการลบ
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบแผนกวิชา <strong>${departmentId}</strong> (${departmentName}) ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบแผนกวิชาจะส่งผลต่อชั้นเรียนและนักเรียนทั้งหมดในแผนกนี้
    `;
    
    // กำหนดฟังก์ชันสำหรับปุ่มยืนยันการลบ
    deleteCallback = function() {
        // เตรียมข้อมูลสำหรับส่ง
        const formData = new FormData();
        formData.append('form_action', 'delete_department');
        formData.append('department_id', departmentId);
        
        // แสดงสถานะการลบ
        const deleteBtn = document.getElementById('confirmDeleteButton');
        const originalBtnText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังลบ...';
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์
        fetch('departments.php', {
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
            showNotification('ลบแผนกวิชาสำเร็จ', 'success');
            
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
 * แสดงรายละเอียดแผนกวิชา
 */
function viewDepartmentDetails(departmentId) {
    currentDepartmentId = departmentId;
    
    // หาข้อมูลแผนกวิชาจากตาราง
    let departmentName = '';
    let studentCount = 0;
    let classCount = 0;
    let teacherCount = 0;
    
    const departmentRows = document.querySelectorAll('#departmentTableBody tr');
    for (const row of departmentRows) {
        if (row.cells[0].textContent === departmentId) {
            departmentName = row.cells[1].textContent;
            studentCount = row.cells[2].textContent;
            classCount = row.cells[3].textContent;
            teacherCount = row.cells[4].textContent;
            break;
        }
    }
    
    // ถ้าไม่พบข้อมูล
    if (!departmentName) {
        showNotification('ไม่พบข้อมูลแผนกวิชา', 'error');
        return;
    }
    
    // เติมข้อมูลพื้นฐาน
    document.getElementById('departmentDetailsTitle').textContent = `รายละเอียดแผนกวิชา ${departmentName}`;
    document.getElementById('detailDepartmentCode').textContent = departmentId;
    document.getElementById('detailDepartmentName').textContent = departmentName;
    document.getElementById('detailStudentCount').textContent = studentCount;
    document.getElementById('detailClassCount').textContent = classCount;
    document.getElementById('detailTeacherCount').textContent = teacherCount;
    document.getElementById('detailStatus').textContent = 'ใช้งาน';
    document.getElementById('detailStatus').className = 'text-success';
    
    // ดึงข้อมูลชั้นเรียนและครูในแผนกวิชาจากเซิร์ฟเวอร์ (ตรงนี้อาจต้องมีการเพิ่ม API เพื่อดึงข้อมูลเพิ่มเติม)
    
    // เติมข้อมูลชั้นเรียนในแผนกวิชา (ตัวอย่าง)
    const classesList = document.getElementById('departmentClassesList');
    classesList.innerHTML = '<div class="text-muted">กำลังโหลดข้อมูล...</div>';
    
    // เติมข้อมูลครูในแผนกวิชา (ตัวอย่าง)
    const teachersList = document.getElementById('departmentTeachersList');
    teachersList.innerHTML = '<tr><td colspan="4" class="text-center">กำลังโหลดข้อมูล...</td></tr>';
    
    // ส่ง AJAX request เพื่อดึงข้อมูลเพิ่มเติม (ตัวอย่าง)
    fetch(`api/class_manager.php?action=get_department_details&department_id=${departmentId}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // เติมข้อมูลชั้นเรียนในแผนกวิชา
            if (data.classes && data.classes.length > 0) {
                classesList.innerHTML = '';
                data.classes.forEach(classItem => {
                    const classEl = document.createElement('div');
                    classEl.className = 'class-item';
                    classEl.innerHTML = `
                        <div class="class-avatar">${classItem.level.charAt(0)}</div>
                        <div class="class-info">
                            <div class="class-name">${classItem.level} กลุ่ม ${classItem.group_number}</div>
                            <div class="class-details">นักเรียน ${classItem.student_count} คน</div>
                        </div>
                    `;
                    classesList.appendChild(classEl);
                });
            } else {
                classesList.innerHTML = '<div class="text-muted">ไม่มีข้อมูลชั้นเรียนในแผนกวิชานี้</div>';
            }
            
            // เติมข้อมูลครูในแผนกวิชา
            if (data.teachers && data.teachers.length > 0) {
                teachersList.innerHTML = '';
                data.teachers.forEach(teacher => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${teacher.teacher_id}</td>
                        <td>${teacher.title} ${teacher.first_name} ${teacher.last_name}</td>
                        <td>${teacher.position || '-'}</td>
                        <td>${teacher.advisor_classes || '-'}</td>
                    `;
                    teachersList.appendChild(row);
                });
            } else {
                teachersList.innerHTML = '<tr><td colspan="4" class="text-center">ไม่มีข้อมูลครูในแผนกวิชานี้</td></tr>';
            }
        } else {
            classesList.innerHTML = '<div class="text-muted">ไม่สามารถโหลดข้อมูลได้</div>';
            teachersList.innerHTML = '<tr><td colspan="4" class="text-center">ไม่สามารถโหลดข้อมูลได้</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        classesList.innerHTML = '<div class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        teachersList.innerHTML = '<tr><td colspan="4" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
    });
    
    // แสดงโมดัล
    showModal('departmentDetailsModal');
}

/**
 * สร้างกราฟสำหรับแผนกวิชา
 */
function createDepartmentCharts() {
    // กราฟจำนวนนักเรียนตามแผนกวิชา
    const studentCtx = document.getElementById('departmentStudentChart').getContext('2d');
    
    // ดึงข้อมูลแผนกวิชาจากตาราง
    const departments = [];
    const studentCounts = [];
    const departmentColors = generateColors(document.querySelectorAll('#departmentTableBody tr').length);
    
    document.querySelectorAll('#departmentTableBody tr').forEach((row, index) => {
        const departmentName = row.cells[1].textContent;
        const studentCount = parseInt(row.cells[2].textContent);
        
        departments.push(departmentName);
        studentCounts.push(studentCount);
    });
    
    departmentCharts.studentChart = new Chart(studentCtx, {
        type: 'pie',
        data: {
            labels: departments,
            datasets: [{
                data: studentCounts,
                backgroundColor: departmentColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'จำนวนนักเรียนตามแผนกวิชา',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((acc, cur) => acc + cur, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} คน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // กราฟจำนวนครูตามแผนกวิชา
    const teacherCtx = document.getElementById('departmentTeacherChart').getContext('2d');
    
    // ดึงข้อมูลจำนวนครูจากตาราง
    const teacherCounts = [];
    
    document.querySelectorAll('#departmentTableBody tr').forEach(row => {
        const teacherCount = parseInt(row.cells[4].textContent);
        teacherCounts.push(teacherCount);
    });
    
    departmentCharts.teacherChart = new Chart(teacherCtx, {
        type: 'bar',
        data: {
            labels: departments,
            datasets: [{
                label: 'จำนวนครู',
                data: teacherCounts,
                backgroundColor: departmentColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'จำนวนครูตามแผนกวิชา',
                    font: {
                        size: 16
                    }
                }
            }
        }
    });
}

/**
 * สร้างสีสำหรับกราฟ
 */
function generateColors(count) {
    const colors = [
        '#4a6cf7', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', 
        '#1abc9c', '#3498db', '#f1c40f', '#e67e22', '#34495e'
    ];
    
    if (count <= colors.length) {
        return colors.slice(0, count);
    }
    
    // ถ้าต้องการสีมากกว่าที่กำหนดไว้
    const result = [...colors];
    
    for (let i = colors.length; i < count; i++) {
        const h = (i * 137) % 360; // สุ่มสีที่แตกต่างกัน
        const s = 70 + (i % 30);
        const l = 40 + (i % 20);
        result.push(`hsl(${h}, ${s}%, ${l}%)`);
    }
    
    return result;
}

/**
 * แสดงการแจ้งเตือน
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
    notification.className = `notification ${type}`;
    
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
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
    
    // ปิดอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('notification-closing');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * แสดงโมดัล
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        
        // โฟกัสที่อินพุตแรกในโมดัล (ถ้ามี)
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    }
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}
/**
 * classes.js - JavaScript สำหรับจัดการหน้าชั้นเรียนและแผนกวิชา
 * ระบบ STP-Prasat (Student Tracking Platform)
 */

// ตัวแปรสำหรับเก็บข้อมูลชั่วคราว
let currentClassId = null;
let currentDepartmentId = null;
let advisorsChanges = [];
let deleteCallback = null;
let chartObjects = {};

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners
    setupEventListeners();
    
    // ตั้งค่าโมดัล Bootstrap
    setupModals();
    
    // แสดงชาร์ตถ้ามีโมดัลเลื่อนชั้น
    if (document.getElementById('promotionChart')) {
        setupPromotionChart();
    }
    
    // จัดการกับ URL parameters (ถ้ามี)
    handleUrlParameters();
    
    // แสดง notification ว่าระบบพร้อมใช้งาน
    showNotification('ระบบจัดการชั้นเรียนพร้อมใช้งาน', 'info');
});

/**
 * ตั้งค่า Event Listeners
 */
function setupEventListeners() {
    // Event listener สำหรับปุ่มปิดโมดัลทั้งหมด
    document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
    
    // Event listener เมื่อคลิกข้างนอกโมดัล
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Event listener สำหรับปุ่มในฟอร์ม
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
        });
    });
    
    // ตัวกรองในช่องค้นหาของครูที่ปรึกษา
    const teacherSearch = document.getElementById('teacherSearch');
    if (teacherSearch) {
        teacherSearch.addEventListener('input', function() {
            filterTeachers(this.value);
        });
    }
    
    // Event listener สำหรับการเลือกปีการศึกษาใหม่
    const toAcademicYear = document.getElementById('toAcademicYear');
    if (toAcademicYear) {
        toAcademicYear.addEventListener('change', function() {
            const newAcademicYearForm = document.getElementById('newAcademicYearForm');
            if (newAcademicYearForm) {
                newAcademicYearForm.style.display = this.value === 'new' ? 'block' : 'none';
            }
        });
    }
}

/**
 * ตั้งค่าโมดัล
 */
function setupModals() {
    // ใช้ Vanilla JS สำหรับการแสดงและซ่อนโมดัล
}

/**
 * จัดการกับ URL parameters
 */
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // กรณีมีข้อความแจ้งเตือน
    const message = urlParams.get('message');
    const messageType = urlParams.get('type') || 'info';
    
    if (message) {
        showNotification(decodeURIComponent(message), messageType);
    }
    
    // กรณีต้องการเปิดโมดัลทันที
    const modal = urlParams.get('modal');
    const id = urlParams.get('id');
    
    if (modal && id) {
        switch (modal) {
            case 'class':
                setTimeout(() => {
                    showClassDetails(id);
                }, 500);
                break;
            case 'advisors':
                setTimeout(() => {
                    manageAdvisors(id);
                }, 500);
                break;
        }
    }
}

/**
 * แสดงโมดัล
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // สร้าง backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
        
        // ถ้ามีฟิลด์อินพุท ตั้งโฟกัสที่อินพุทแรก
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
        
        // ลบ backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

/**
 * =================================================================
 * ฟังก์ชันเกี่ยวกับแผนกวิชา
 * =================================================================
 */

/**
 * แสดงโมดัลเพิ่มแผนกวิชา
 */
function showDepartmentModal() {
    document.getElementById('departmentModalTitle').textContent = 'เพิ่มแผนกวิชาใหม่';
    document.getElementById('departmentId').value = '';
    document.getElementById('departmentCode').value = '';
    document.getElementById('departmentName').value = '';
    
    // แสดงรหัสแผนกวิชาในกรณีเพิ่มใหม่
    document.getElementById('departmentCode').readOnly = false;
    
    showModal('departmentModal');
}

/**
 * แสดงรายละเอียดแผนกวิชา
 */
function viewDepartmentDetails(departmentId) {
    // ส่ง AJAX request เพื่อดึงข้อมูลแผนกวิชา
    fetch('api/class_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_department_details&department_id=${departmentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // แสดงรายละเอียดในโมดัลแสดงรายละเอียด
            showNotification(`ข้อมูลแผนกวิชา ${data.department.department_name}`, 'info');
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
 * แก้ไขแผนกวิชา
 */
function editDepartment(departmentId) {
    currentDepartmentId = departmentId;
    
    // เพิ่มกลไกสำรองโดยหาข้อมูลแผนกวิชาจากตารางก่อน
    let departmentName = '';
    let departmentCode = departmentId;
    
    try {
        const departmentRows = document.querySelectorAll('#departmentTableBody tr');
        for (const row of departmentRows) {
            const cells = row.cells;
            if (cells[0].textContent == departmentId) {
                departmentCode = cells[0].textContent;
                departmentName = cells[1].textContent;
                break;
            }
        }
    } catch (e) {
        console.error('Error finding department in table:', e);
    }
    
    // ส่ง AJAX request เพื่อดึงข้อมูลแผนกวิชา
    fetch('api/class_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_department_details&department_id=${departmentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.department) {
            document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
            document.getElementById('departmentId').value = data.department.department_id || departmentId;
            document.getElementById('departmentName').value = data.department.department_name || departmentName;
            
            // ในกรณีแก้ไข ไม่อนุญาตให้แก้ไขรหัสแผนกวิชา
            document.getElementById('departmentCode').value = data.department.department_code || departmentCode;
            document.getElementById('departmentCode').readOnly = true;
            
            showModal('departmentModal');
        } else {
            // ใช้ข้อมูลสำรองถ้า API ไม่ส่งข้อมูลที่ถูกต้องกลับมา
            document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
            document.getElementById('departmentId').value = departmentId;
            document.getElementById('departmentName').value = departmentName;
            document.getElementById('departmentCode').value = departmentCode;
            document.getElementById('departmentCode').readOnly = true;
            
            showModal('departmentModal');
            
            if (data.message) {
                showNotification(data.message, 'warning');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // ยังคงแสดงโมดัลพร้อมข้อมูลสำรอง
        document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
        document.getElementById('departmentId').value = departmentId;
        document.getElementById('departmentName').value = departmentName;
        document.getElementById('departmentCode').value = departmentCode;
        document.getElementById('departmentCode').readOnly = true;
        
        showModal('departmentModal');
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล แสดงข้อมูลเท่าที่มี', 'warning');
    });
}

/**
 * บันทึกข้อมูลแผนกวิชา (เพิ่มใหม่หรือแก้ไข)
 */
function saveDepartment() {
    const departmentId = document.getElementById('departmentId').value;
    const departmentName = document.getElementById('departmentName').value;
    const departmentCode = document.getElementById('departmentCode').value;
    
    if (!departmentName) {
        showNotification('กรุณาระบุชื่อแผนกวิชา', 'warning');
        return;
    }
    
    if (!departmentId && !departmentCode) {
        showNotification('กรุณาระบุรหัสแผนกวิชา', 'warning');
        return;
    }
    
    // กำหนด action ตามว่าเป็นการเพิ่มใหม่หรือแก้ไข
    const action = departmentId ? 'edit_department' : 'add_department';
    
    // เตรียมข้อมูล
    let formData = new FormData();
    formData.append('action', action);
    formData.append('department_name', departmentName);
    
    if (departmentId) {
        formData.append('department_id', departmentId);
    } else {
        formData.append('department_code', departmentCode);
    }
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('departmentModal');
            // รีโหลดหน้าหลังจากบันทึกสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
        const idCell = row.cells[0];
        const nameCell = row.cells[1];
        if (idCell && nameCell && idCell.textContent.trim() === departmentId) {
            departmentName = nameCell.textContent.trim();
            break;
        }
    }
    
    // แสดงข้อความยืนยันการลบ
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบแผนกวิชา <strong>${departmentName}</strong> (รหัส ${departmentId}) ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบแผนกวิชาจะส่งผลต่อชั้นเรียนและนักเรียนทั้งหมดในแผนกนี้
    `;
    
    deleteCallback = function() {
        // เตรียมข้อมูลสำหรับส่ง
        let formData = new FormData();
        formData.append('action', 'delete_department');
        formData.append('department_id', departmentId);
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์
        fetch('api/class_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message, 'success');
                closeModal('confirmDeleteModal');
                // รีโหลดหน้าหลังจากลบสำเร็จ
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message, 'error');
                closeModal('confirmDeleteModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            closeModal('confirmDeleteModal');
        });
    };
    
    // ตั้งค่าฟังก์ชันสำหรับปุ่มยืนยันการลบ
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    
    // แสดงโมดัลยืนยันการลบ
    showModal('confirmDeleteModal');
}

/**
 * =================================================================
 * ฟังก์ชันเกี่ยวกับชั้นเรียน
 * =================================================================
 */

/**
 * แสดงโมดัลเพิ่มชั้นเรียน
 */
function showAddClassModal() {
    document.getElementById('classModalTitle').textContent = 'เพิ่มชั้นเรียนใหม่';
    document.getElementById('classId').value = '';
    
    // รีเซ็ตฟอร์ม
    const classForm = document.getElementById('classForm');
    if (classForm) {
        classForm.reset();
    }
    
    // ตั้งค่าปีการศึกษาปัจจุบันเป็นค่าเริ่มต้น
    const academicYear = document.getElementById('academicYear');
    if (academicYear) {
        const activeOption = academicYear.querySelector('option[selected]');
        if (activeOption) {
            academicYear.value = activeOption.value;
        }
    }
    
    showModal('classModal');
}

/**
 * บันทึกข้อมูลชั้นเรียน
 */
function saveClass() {
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
    
    // เตรียมข้อมูล
    let formData = new FormData();
    formData.append('academic_year_id', academicYearId);
    formData.append('level', level);
    formData.append('department_id', departmentId);
    formData.append('group_number', groupNumber);
    formData.append('classroom', classroom);
    
    // กำหนด action ตามว่าเป็นการเพิ่มใหม่หรือแก้ไข
    const action = classId ? 'edit_class' : 'add_class';
    formData.append('action', action);
    
    if (classId) {
        formData.append('class_id', classId);
    }
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('classModal');
            
            // รีโหลดหน้าหลังจากบันทึกสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
 * แก้ไขชั้นเรียน
 */
function editClass(classId) {
    currentClassId = classId;
    
    // ส่ง AJAX request เพื่อดึงข้อมูลชั้นเรียน
    fetch('api/class_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_class_details&class_id=${classId}`
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
            document.getElementById('classroom').value = data.class.classroom || '';
            
            showModal('classModal');
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
    // ตรวจสอบว่ามีการส่งรหัสชั้นเรียนมาหรือไม่
    if (!classId) {
        showNotification('ไม่พบรหัสชั้นเรียน', 'error');
        return;
    }
    
    // หาข้อมูลชั้นเรียนจากตาราง
    let className = '';
    const classRows = document.querySelectorAll('.class-row');
    for (const row of classRows) {
        const cells = row.cells;
        if (cells[0].textContent.trim() == classId) {
            const classNameElement = row.querySelector('.class-name');
            if (classNameElement) {
                className = classNameElement.textContent.trim();
            }
            break;
        }
    }
    
    // แสดงข้อความยืนยันการลบ
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียน <strong>${className}</strong> (รหัส ${classId}) ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนและข้อมูลการเข้าแถวทั้งหมดในชั้นเรียนนี้
    `;
    
    deleteCallback = function() {
        // เตรียมข้อมูลสำหรับส่ง
        let formData = new FormData();
        formData.append('action', 'delete_class');
        formData.append('class_id', classId);
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์
        fetch('api/class_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message, 'success');
                closeModal('confirmDeleteModal');
                // รีโหลดหน้าหลังจากลบสำเร็จ
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message, 'error');
                closeModal('confirmDeleteModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            closeModal('confirmDeleteModal');
        });
    };
    
    // ตั้งค่าฟังก์ชันสำหรับปุ่มยืนยันการลบ
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
    fetch('api/class_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_class_details&class_id=${classId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // เติมข้อมูลพื้นฐาน
            document.getElementById('classDetailsTitle').textContent = `รายละเอียดชั้น ${data.class.level} กลุ่ม ${data.class.group_number} ${data.class.department_name}`;
            document.getElementById('detailAcademicYear').textContent = `${data.class.year} (ภาคเรียนที่ ${data.class.semester})`;
            document.getElementById('detailLevel').textContent = data.class.level;
            document.getElementById('detailDepartment').textContent = data.class.department_name;
            document.getElementById('detailGroup').textContent = data.class.group_number;
            document.getElementById('detailClassroom').textContent = data.class.classroom || '-';
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
    if (!advisorsList) return;
    
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
    if (!studentTableBody) return;
    
    studentTableBody.innerHTML = '';
    
    if (!students || students.length === 0) {
        studentTableBody.innerHTML = '<tr><td colspan="5" class="text-center">ไม่มีนักเรียนในชั้นเรียนนี้</td></tr>';
        return;
    }
    
    students.forEach(student => {
        // แก้ไข: ตรวจสอบให้แน่ใจว่า percent เป็นตัวเลขหรือแปลงเป็น 0
        const percent = typeof student.percent === 'number' ? student.percent : 0;
        const statusClass = percent > 90 ? 'success' : (percent > 75 ? 'warning' : 'danger');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.code || ''}</td>
            <td>${student.name || ''}</td>
            <td class="text-center">${student.attendance || 0}/${student.total || 0} วัน</td>
            <td class="text-center">${percent.toFixed(1)}%</td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${student.status || 'รอประเมิน'}
                </span>
            </td>
        `;
        studentTableBody.appendChild(row);
    });
}

/**
 * สร้างกราฟการเข้าแถว
 */
function createAttendanceCharts(attendanceStats) {
    // ถ้าไม่มีชาร์ต กลับออกไป
    if (!document.getElementById('classAttendanceChart') || !document.getElementById('monthlyAttendanceChart')) {
        return;
    }
    
    // ถ้าไม่มีข้อมูลหรือไม่ได้โหลด Chart.js
    if (!attendanceStats || typeof Chart === 'undefined') {
        console.error('Missing attendance stats or Chart.js is not loaded');
        return;
    }
    
    // ถ้ามีชาร์ตเก่าให้ทำลายก่อน
    if (chartObjects.attendanceChart) {
        chartObjects.attendanceChart.destroy();
    }
    if (chartObjects.monthlyChart) {
        chartObjects.monthlyChart.destroy();
    }
    
    // กราฟสรุปการเข้าแถว (Doughnut Chart)
    const ctx1 = document.getElementById('classAttendanceChart').getContext('2d');
    chartObjects.attendanceChart = new Chart(ctx1, {
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
    chartObjects.monthlyChart = new Chart(ctx2, {
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
 * =================================================================
 * ฟังก์ชันเกี่ยวกับครูที่ปรึกษา
 * =================================================================
 */

/**
 * แสดงโมดัลจัดการครูที่ปรึกษา
 */
function manageAdvisors(classId) {
    currentClassId = classId;
    advisorsChanges = []; // รีเซ็ตการเปลี่ยนแปลง
    
    // ส่ง AJAX request เพื่อดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
    fetch('api/class_manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_class_advisors&class_id=${classId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success' || data.success === true) {
            const classTitle = data.class_name || '';
            document.getElementById('advisorsClassTitle').textContent = classTitle;
            
            // เติมข้อมูลครูที่ปรึกษาปัจจุบัน
            renderCurrentAdvisors(data.advisors || []);
            
            // รีเซ็ตการแสดงการเปลี่ยนแปลง
            document.getElementById('changesLog').innerHTML = '<div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>';
            
            // รีเซ็ตฟอร์มเพิ่มครูที่ปรึกษา
            if (document.getElementById('advisorSelect')) {
                document.getElementById('advisorSelect').value = '';
            }
            if (document.getElementById('isPrimaryAdvisor')) {
                document.getElementById('isPrimaryAdvisor').checked = false;
            }
            
            // แสดงโมดัล
            showModal('advisorsModal');
        } else {
            showNotification(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + error.message, 'error');
    });
}

/**
 * เรียกใช้จัดการครูที่ปรึกษาจากหน้ารายละเอียดชั้นเรียน
 */
function manageAdvisorsFromDetails() {
    if (currentClassId) {
        // ซ่อนโมดัลรายละเอียดชั้นเรียน
        closeModal('classDetailsModal');
        
        // แสดงโมดัลจัดการครูที่ปรึกษา
        setTimeout(() => {
            manageAdvisors(currentClassId);
        }, 300);
    }
}

/**
 * เติมข้อมูลครูที่ปรึกษาปัจจุบัน
 */
function renderCurrentAdvisors(advisors) {
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    if (!currentAdvisorsList) {
        console.error('Element #currentAdvisorsList not found');
        return;
    }
    
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
 * แสดงข้อความแจ้งเตือน
 * @param {string} message ข้อความ
 * @param {string} type ประเภท (success, info, warning, error)
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
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.classList.add('notification-closing');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }
    
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
    const advisorSelect = document.getElementById('advisorSelect');
    const selectedOption = advisorSelect.options[advisorSelect.selectedIndex];
    const advisorName = selectedOption ? selectedOption.textContent : `ครูรหัส ${advisorId}`;
    
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
    if (!changesLog) return;
    
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
                <button class="btn btn-sm" onclick="removeChange(${index})">
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
    if (currentAdvisorsList && currentAdvisorsList.children.length === 0) {
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
    document.getElementById('confirmContent').innerHTML = `
        <div class="alert alert-warning">
            <span class="material-icons">warning</span>
            <div>คุณต้องการลบครูที่ปรึกษาออกจากชั้นเรียนนี้หรือไม่?</div>
        </div>
    `;
    
    document.getElementById('confirmButton').onclick = function() {
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
        if (currentAdvisorsList && currentAdvisorsList.children.length === 0) {
            currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        }
        
        closeModal('confirmModal');
        showNotification('ลบครูที่ปรึกษาออกจากชั้นเรียนแล้ว', 'success');
    };
    
    showModal('confirmModal');
}

/**
 * ยกเลิกการเปลี่ยนแปลงครูที่ปรึกษา
 */
function cancelAdvisorChanges() {
    if (advisorsChanges.length > 0) {
        document.getElementById('confirmContent').innerHTML = `
            <div class="alert alert-warning">
                <span class="material-icons">warning</span>
                <div>คุณแน่ใจหรือไม่ที่จะยกเลิกการเปลี่ยนแปลงทั้งหมด?</div>
            </div>
        `;
        
        document.getElementById('confirmButton').onclick = function() {
            advisorsChanges = [];
            closeModal('confirmModal');
            closeModal('advisorsModal');
        };
        
        showModal('confirmModal');
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
    let formData = new FormData();
    formData.append('action', 'manage_advisors');
    formData.append('class_id', currentClassId);
    formData.append('changes', JSON.stringify(advisorsChanges));
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('advisorsModal');
            // รีโหลดหน้าหลังจากบันทึกสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
 * =================================================================
 * ฟังก์ชันเกี่ยวกับการเลื่อนชั้น
 * =================================================================
 */

/**
 * ตั้งค่ากราฟแสดงจำนวนนักเรียนที่จะเลื่อนชั้น
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
            // แปลงข้อความจำนวนนักเรียนเป็นตัวเลข (ตัดคำว่า "คน" ออก ถ้ามี)
            const studentCount = parseInt(studentCountText.replace(/[^0-9]/g, ''));
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
                padding: 10px;
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
 * เพิ่มปีการศึกษาใหม่
 */
function addNewAcademicYear() {
    const year = document.getElementById('newYear').value;
    const semester = document.getElementById('newSemester').value;
    const startDate = document.getElementById('newStartDate').value;
    const endDate = document.getElementById('newEndDate').value;
    
    if (!year || !semester || !startDate || !endDate) {
        showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
    }
    
    // ปิดปุ่มและแสดงไอคอนโหลด
    const addButton = document.querySelector('#newAcademicYearForm button');
    const originalButtonHtml = addButton.innerHTML;
    addButton.disabled = true;
    addButton.innerHTML = '<span class="material-icons">sync</span> กำลังดำเนินการ...';
    
    // เตรียมข้อมูลสำหรับส่ง
    let formData = new FormData();
    formData.append('action', 'add_academic_year');
    formData.append('year', year);
    formData.append('semester', semester);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification(data.message, 'success');
            
            // เพิ่มตัวเลือกในรายการปีการศึกษาปลายทาง
            const toAcademicYear = document.getElementById('toAcademicYear');
            const newOption = document.createElement('option');
            newOption.value = data.academic_year_id;
            newOption.text = `${year} (ภาคเรียนที่ ${semester})`;
            newOption.selected = true;
            
            // ลบตัวเลือก "เพิ่มปีการศึกษาใหม่"
            for (let i = 0; i < toAcademicYear.options.length; i++) {
                if (toAcademicYear.options[i].value === 'new') {
                    toAcademicYear.remove(i);
                    break;
                }
            }
            
            toAcademicYear.appendChild(newOption);
            
            // ซ่อนฟอร์มเพิ่มปีการศึกษา
            document.getElementById('newAcademicYearForm').style.display = 'none';
            
        } else {
            showNotification(data.message, 'error');
            addButton.disabled = false;
            addButton.innerHTML = originalButtonHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเพิ่มปีการศึกษา', 'error');
        addButton.disabled = false;
        addButton.innerHTML = originalButtonHtml;
    });
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
    
    // แสดงข้อความยืนยันการเลื่อนชั้น
    document.getElementById('confirmContent').innerHTML = `
        <div class="alert alert-warning">
            <span class="material-icons">warning</span>
            <div class="alert-content">
                <p><strong>คุณกำลังจะเลื่อนชั้นนักเรียนทั้งหมด</strong></p>
                <p>ปีการศึกษาต้นทาง: <strong>${document.querySelector(`#fromAcademicYear option:checked`).textContent}</strong></p>
                <p>ปีการศึกษาปลายทาง: <strong>${document.querySelector(`#toAcademicYear option:checked`).textContent}</strong></p>
                <p class="text-danger">คำเตือน: การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
            </div>
        </div>
    `;
    
    document.getElementById('confirmButton').onclick = function() {
        executePromoteStudents(fromAcademicYear, toAcademicYear, promotionNotes);
    };
    
    showModal('confirmModal');
}

/**
 * ดำเนินการเลื่อนชั้นนักเรียน
 */
function executePromoteStudents(fromAcademicYear, toAcademicYear, promotionNotes) {
    // แสดงสถานะกำลังโหลด
    const promoteBtn = document.getElementById('promoteBtn');
    const originalBtnHtml = promoteBtn.innerHTML;
    promoteBtn.disabled = true;
    promoteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังดำเนินการ...';
    
    // เตรียมข้อมูลสำหรับส่ง
    let formData = new FormData();
    formData.append('action', 'promote_students');
    formData.append('from_academic_year_id', fromAcademicYear);
    formData.append('to_academic_year_id', toAcademicYear);
    formData.append('notes', promotionNotes);
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal('confirmModal');
            closeModal('promoteStudentsModal');
            
            showNotification(`
                <div>เลื่อนชั้นนักเรียนสำเร็จ</div>
                <div>- นักเรียนเลื่อนชั้น: ${data.promoted_count} คน</div>
                <div>- นักเรียนสำเร็จการศึกษา: ${data.graduated_count} คน</div>
            `, 'success');
            
            // รีโหลดหน้าหลังจากบันทึกสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showNotification(data.message, 'error');
            promoteBtn.disabled = false;
            promoteBtn.innerHTML = originalBtnHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน', 'error');
        promoteBtn.disabled = false;
        promoteBtn.innerHTML = originalBtnHtml;
    });
}

/**
 * =================================================================
 * ฟังก์ชันทั่วไป
 * =================================================================
 */

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
    
    // แสดงข้อความเมื่อไม่พบข้อมูล
    const tableBody = document.getElementById('classTableBody');
    if (tableBody) {
        if (visibleCount === 0) {
            // ตรวจสอบว่ามีข้อความไม่พบข้อมูลอยู่แล้วหรือไม่
            let noDataRow = tableBody.querySelector('.no-data-row');
            if (!noDataRow) {
                noDataRow = document.createElement('tr');
                noDataRow.className = 'no-data-row';
                noDataRow.innerHTML = '<td colspan="7" class="text-center">ไม่พบข้อมูลที่ตรงกับเงื่อนไขการค้นหา</td>';
                tableBody.appendChild(noDataRow);
            }
        } else {
            // ลบข้อความไม่พบข้อมูลถ้ามี
            const noDataRow = tableBody.querySelector('.no-data-row');
            if (noDataRow) {
                noDataRow.remove();
            }
        }
    }
    
    showNotification(`กรองข้อมูลสำเร็จ แสดง ${visibleCount} รายการ`, 'info');
}

/**
 * กรองรายชื่อครู
 */
function filterTeachers(searchText) {
    const options = document.querySelectorAll('#advisorSelect option');
    
    options.forEach(option => {
        if (option.value === '') return; // ข้ามตัวเลือกแรก
        
        const text = option.textContent.toLowerCase();
        if (text.includes(searchText.toLowerCase())) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 */
function downloadClassReport() {
    if (!currentClassId) {
        showNotification('ไม่พบรหัสชั้นเรียน', 'error');
        return;
    }
    
    window.location.href = `api/class_manager.php?action=download_report&class_id=${currentClassId}`;
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message ข้อความ
 * @param {string} type ประเภท (success, info, warning, error)
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
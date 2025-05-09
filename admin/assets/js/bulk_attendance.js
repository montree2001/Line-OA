/**
 * bulk_attendance.js - จัดการการเช็คชื่อนักเรียนแบบกลุ่ม
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing bulk attendance JS');
    
    // เชื่อมต่อปุ่มค้นหา
    const searchBtn = document.getElementById('btnSearch');
    if (searchBtn) {
        searchBtn.addEventListener('click', searchStudents);
        console.log('Search button initialized');
    } else {
        console.error('Search button not found');
    }
    
    // เชื่อมต่อ event สำหรับการเปลี่ยนแปลงแผนกและระดับชั้น
    const deptSelector = document.getElementById('filterDepartment');
    const levelSelector = document.getElementById('filterLevel');
    
    if (deptSelector) {
        deptSelector.addEventListener('change', function() {
            console.log('Department changed to:', this.value);
            updateClasses();
        });
    } else {
        console.error('Department selector not found');
    }
    
    if (levelSelector) {
        levelSelector.addEventListener('change', function() {
            console.log('Level changed to:', this.value);
            updateClasses();
        });
    } else {
        console.error('Level selector not found');
    }
    
    // เชื่อมต่อ event สำหรับฟอร์ม
    const attendanceForm = document.getElementById('attendanceForm');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function(event) {
            // ตรวจสอบว่ามีการเลือกนักเรียนหรือไม่
            const checkedStudents = document.querySelectorAll('input[name^="attendance"][type="checkbox"]:checked');
            if (checkedStudents.length === 0) {
                event.preventDefault();
                alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
                return false;
            }
            
            // อัปเดตวันที่ให้ตรงกับที่เลือก
            const dateInput = document.getElementById('filterDate');
            const attendanceDateInput = document.getElementById('attendance_date');
            
            if (dateInput && attendanceDateInput) {
                attendanceDateInput.value = dateInput.value;
            }
            
            // ถามยืนยันก่อนบันทึก
            if (!confirm('ยืนยันการบันทึกการเช็คชื่อ?')) {
                event.preventDefault();
                return false;
            }
        });
    } else {
        console.error('Attendance form not found');
    }
    
    // ปรับปรุงตัวเลือกห้องเรียนเริ่มต้น
    updateClasses();
    
    // ตรวจสอบการแสดงข้อความแจ้งเตือน
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 500);
        }, 3000);
    }
});

/**
 * อัปเดตรายการห้องเรียนตามแผนกและระดับชั้นที่เลือก
 */
function updateClasses() {
    console.log('Updating classes...');
    
    const departmentSelect = document.getElementById('filterDepartment');
    const levelSelect = document.getElementById('filterLevel');
    const classSelect = document.getElementById('filterClass');
    
    if (!departmentSelect || !levelSelect || !classSelect) {
        console.error('One or more form elements not found');
        return;
    }
    
    const departmentId = departmentSelect.value;
    const level = levelSelect.value;
    
    // ล้างตัวเลือกเดิมยกเว้นตัวแรก
    while (classSelect.options.length > 1) {
        classSelect.remove(1);
    }
    
    // ถ้าไม่ได้เลือกแผนกหรือระดับชั้น ไม่ต้องดึงข้อมูลเพิ่ม
    if (!departmentId && !level) {
        console.log('No department or level selected, skipping fetch');
        return;
    }
    
    // แสดง loading
    classSelect.disabled = true;
    
    // สร้าง URL สำหรับดึงข้อมูล
    let url = 'ajax/get_classes.php?';
    let params = [];
    
    if (departmentId) {
        params.push('department_id=' + departmentId);
    }
    
    if (level) {
        params.push('level=' + encodeURIComponent(level));
    }
    
    url += params.join('&');
    
    console.log('Fetching classes from URL:', url);
    
    // ดึงข้อมูลห้องเรียน
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text(); // ดึงข้อมูลเป็น text ก่อนเพื่อดูข้อผิดพลาด
        })
        .then(text => {
            try {
                console.log('Raw response:', text.substring(0, 200) + '...');
                const data = JSON.parse(text);
                console.log('JSON data:', data);
                
                if (data.success && data.classes && Array.isArray(data.classes)) {
                    // เพิ่มตัวเลือกห้องเรียน
                    data.classes.forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.class_id;
                        option.textContent = `${classItem.level}/${classItem.group_number} ${classItem.department_name}`;
                        classSelect.appendChild(option);
                    });
                    console.log(`Added ${data.classes.length} classes to dropdown`);
                } else {
                    console.warn('No classes found or data structure invalid:', data);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.log('Raw text received:', text);
            }
        })
        .catch(error => {
            console.error('Error fetching classes:', error);
        })
        .finally(() => {
            classSelect.disabled = false;
        });
}

/**
 * ค้นหานักเรียนตามเงื่อนไขที่เลือก
 */
function searchStudents() {
    console.log('Searching for students...');
    
    const departmentSelect = document.getElementById('filterDepartment');
    const levelSelect = document.getElementById('filterLevel');
    const classSelect = document.getElementById('filterClass');
    const dateInput = document.getElementById('filterDate');
    
    if (!departmentSelect || !levelSelect || !classSelect || !dateInput) {
        console.error('One or more form elements not found');
        return;
    }
    
    const departmentId = departmentSelect.value;
    const level = levelSelect.value;
    const classId = classSelect.value;
    const date = dateInput.value;
    
    // ตรวจสอบว่าเลือกเงื่อนไขการค้นหาอย่างน้อย 1 อย่าง
    if (!departmentId && !level && !classId) {
        alert('กรุณาเลือกแผนกวิชา ระดับชั้น หรือกลุ่มเรียนอย่างน้อย 1 อย่าง');
        return;
    }
    
    // ตรวจสอบว่าเลือกวันที่
    if (!date) {
        alert('กรุณาเลือกวันที่');
        return;
    }
    
    // ซ่อนข้อความและแสดง loading
    const studentListContainer = document.getElementById('studentListContainer');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const formActions = document.getElementById('formActions');
    const attendanceSummary = document.getElementById('attendanceSummary');
    
    if (studentListContainer) studentListContainer.style.display = 'none';
    if (noStudentsMessage) noStudentsMessage.style.display = 'none';
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (formActions) formActions.style.display = 'none';
    if (attendanceSummary) attendanceSummary.style.display = 'none';
    
    // สร้าง URL สำหรับดึงข้อมูล
    let url = 'ajax/get_students_for_attendance.php?';
    let params = [];
    
    if (departmentId) {
        params.push('department_id=' + departmentId);
    }
    
    if (level) {
        params.push('level=' + encodeURIComponent(level));
    }
    
    if (classId) {
        params.push('class_id=' + classId);
    }
    
    params.push('date=' + date);
    
    url += params.join('&');
    
    console.log('Fetching students from URL:', url);
    
    // ดึงข้อมูลนักเรียน
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.text(); // ดึงข้อมูลเป็น text ก่อนเพื่อดูข้อผิดพลาด
        })
        .then(text => {
            try {
                console.log('Raw response:', text.substring(0, 200) + '...');
                const data = JSON.parse(text);
                console.log('Students data received:', data);
                
                if (data.success) {
                    const classTitleElement = document.getElementById('classTitle');
                    if (classTitleElement) {
                        classTitleElement.textContent = data.class_info || '';
                    }
                    
                    // บันทึก class_id ถ้ามี
                    const classIdInput = document.getElementById('class_id');
                    if (classIdInput && data.class_id) {
                        classIdInput.value = data.class_id;
                    }
                    
                    if (data.students && Array.isArray(data.students) && data.students.length > 0) {
                        // แสดงรายชื่อนักเรียน
                        renderStudentList(data.students);
                        if (studentListContainer) studentListContainer.style.display = 'block';
                        if (formActions) formActions.style.display = 'flex';
                        if (attendanceSummary) attendanceSummary.style.display = 'block';
                        
                        // อัปเดตสรุปการเช็คชื่อ
                        updateAttendanceSummary();
                    } else {
                        // ไม่พบนักเรียน
                        if (noStudentsMessage) noStudentsMessage.style.display = 'block';
                    }
                } else {
                    // เกิดข้อผิดพลาด
                    console.error('API error:', data.error);
                    alert(data.error || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                    if (noStudentsMessage) noStudentsMessage.style.display = 'block';
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.log('Raw text received:', text);
                alert('เกิดข้อผิดพลาดในการประมวลผลข้อมูล: ' + e.message);
                if (noStudentsMessage) noStudentsMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + error.message);
            if (noStudentsMessage) noStudentsMessage.style.display = 'block';
        })
        .finally(() => {
            if (loadingIndicator) loadingIndicator.style.display = 'none';
        });
}



/**
 * อัปเดต highlight แถวตามการเลือก
 */
function updateRowHighlight(checkbox) {
    if (!checkbox) return;
    
    const row = checkbox.closest('tr');
    if (!row) return;
    
    if (checkbox.checked) {
        row.classList.add('selected-row');
    } else {
        row.classList.remove('selected-row');
    }
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * อัปเดตสรุปการเช็คชื่อ
 */
function updateAttendanceSummary() {
    console.log('Updating attendance summary');
    
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    let presentCount = 0;
    let lateCount = 0;
    let absentCount = 0;
    let leaveCount = 0;
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const studentIdMatch = checkbox.name.match(/\[(\d+)\]/);
            if (!studentIdMatch) return;
            
            const studentId = studentIdMatch[1];
            const statusSelect = document.querySelector(`select[name="attendance[${studentId}][status]"]`);
            
            if (statusSelect) {
                const status = statusSelect.value;
                
                switch (status) {
                    case 'present':
                        presentCount++;
                        break;
                    case 'late':
                        lateCount++;
                        break;
                    case 'absent':
                        absentCount++;
                        break;
                    case 'leave':
                        leaveCount++;
                        break;
                }
            }
        }
    });
    
    // อัปเดตค่าในหน้าเว็บ
    const presentCountElement = document.getElementById('presentCount');
    const lateCountElement = document.getElementById('lateCount');
    const absentCountElement = document.getElementById('absentCount');
    const leaveCountElement = document.getElementById('leaveCount');
    
    if (presentCountElement) presentCountElement.textContent = presentCount;
    if (lateCountElement) lateCountElement.textContent = lateCount;
    if (absentCountElement) absentCountElement.textContent = absentCount;
    if (leaveCountElement) leaveCountElement.textContent = leaveCount;
    
    console.log('Attendance summary updated:', {
        present: presentCount,
        late: lateCount,
        absent: absentCount,
        leave: leaveCount
    });
}

/**
 * เลือกนักเรียนทั้งหมด
 */
function checkAllStudents() {
    console.log('Checking all students');
    
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        const row = checkbox.closest('tr');
        if (row) row.classList.add('selected-row');
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * ยกเลิกการเลือกนักเรียนทั้งหมด
 */
function uncheckAllStudents() {
    console.log('Unchecking all students');
    
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const row = checkbox.closest('tr');
        if (row) row.classList.remove('selected-row');
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}
/**
 * function renderStudentList - แสดงรายชื่อนักเรียนพร้อม icon วิธีการเช็คชื่อ
 */
function renderStudentList(students) {
    console.log('Rendering student list:', students.length, 'students');
    
    const studentList = document.getElementById('studentList');
    if (!studentList) {
        console.error('Student list container not found');
        return;
    }
    
    // ล้างรายการเดิม
    studentList.innerHTML = '';
    
    // เพิ่มรายชื่อนักเรียน
    students.forEach((student, index) => {
        const row = document.createElement('tr');
        
        // สถานะการเช็คชื่อเดิม (ถ้ามี)
        const checkedAttr = student.attendance_status ? 'checked' : '';
        const status = student.attendance_status || 'absent';
        
        // กำหนด icon และสีตามวิธีการเช็คชื่อ
        let checkMethodIcon = '';
        let checkMethodColor = '';
        let checkMethodTitle = '';
        
        if (student.attendance_status) {
            // กำหนด icon ตามวิธีการเช็คชื่อ
            switch (student.check_method) {
                case 'GPS':
                    checkMethodIcon = 'location_on';
                    checkMethodColor = 'text-success';
                    checkMethodTitle = 'เช็คชื่อด้วย GPS';
                    break;
                case 'QR_Code':
                    checkMethodIcon = 'qr_code_scanner';
                    checkMethodColor = 'text-primary';
                    checkMethodTitle = 'เช็คชื่อด้วย QR Code';
                    break;
                case 'PIN':
                    checkMethodIcon = 'pin';
                    checkMethodColor = 'text-warning';
                    checkMethodTitle = 'เช็คชื่อด้วยรหัส PIN';
                    break;
                case 'Manual':
                    checkMethodIcon = 'edit';
                    checkMethodColor = 'text-info';
                    checkMethodTitle = 'เช็คชื่อด้วยผู้ดูแลระบบ';
                    break;
                default:
                    checkMethodIcon = 'check_circle';
                    checkMethodColor = 'text-muted';
                    checkMethodTitle = 'เช็คชื่อแล้ว';
            }
        } else {
            checkMethodIcon = 'radio_button_unchecked';
            checkMethodColor = 'text-muted';
            checkMethodTitle = 'ยังไม่ได้เช็คชื่อ';
        }
        
        // สร้าง HTML สำหรับ icon
        const methodIconHtml = `
            <span class="material-icons ${checkMethodColor} check-method-icon" 
                  title="${checkMethodTitle}" style="font-size: 20px; vertical-align: middle;">
                ${checkMethodIcon}
            </span>
        `;
        
        // กำหนดเวลาเช็คชื่อ (ถ้ามี)
        const checkTime = student.check_time ? `<small class="text-muted">(${student.check_time})</small>` : '';
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <input type="checkbox" name="attendance[${student.student_id}][check]" value="1" ${checkedAttr} onchange="updateRowHighlight(this)">
            </td>
            <td>${student.student_code || ''}</td>
            <td>${student.title || ''}${student.first_name || ''} ${student.last_name || ''}</td>
            <td>${student.level || ''}</td>
            <td class="text-center">${methodIconHtml}</td>
            <td>
                <select class="form-control form-control-sm" name="attendance[${student.student_id}][status]" onchange="updateAttendanceSummary()">
                    <option value="present" ${status === 'present' ? 'selected' : ''}>เข้าแถว</option>
                    <option value="absent" ${status === 'absent' ? 'selected' : ''}>ขาดแถว</option>
                    <option value="late" ${status === 'late' ? 'selected' : ''}>สาย</option>
                    <option value="leave" ${status === 'leave' ? 'selected' : ''}>ลา</option>
                </select>
                ${checkTime}
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="attendance[${student.student_id}][remarks]" 
                       placeholder="หมายเหตุ" value="${student.remarks || ''}">
            </td>
        `;
        
        // เพิ่ม highlight ถ้ามีการเช็คชื่อแล้ว
        if (student.attendance_status) {
            row.classList.add('selected-row');
        }
        
        studentList.appendChild(row);
    });
    
    console.log('Student list rendered successfully');
}

/**
 * แก้ไขฟังก์ชัน setAllStatus เพื่อใช้ชื่อตัวเลือกใหม่ในการแสดงข้อความยืนยัน
 */
function setAllStatus(status) {
    // แปลงสถานะเป็นข้อความภาษาไทย
    let statusText = '';
    switch (status) {
        case 'present':
            statusText = 'เข้าแถว';
            break;
        case 'absent':
            statusText = 'ขาดแถว';
            break;
        case 'late':
            statusText = 'สาย';
            break;
        case 'leave':
            statusText = 'ลา';
            break;
        default:
            statusText = status;
    }
    
    console.log('Setting all students to status:', statusText);
    
    // เลือกนักเรียนทั้งหมดก่อน
    checkAllStudents();
    
    // กำหนดสถานะให้ทั้งหมด
    const selects = document.querySelectorAll('select[name^="attendance"][name$="[status]"]');
    selects.forEach(select => {
        select.value = status;
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * รีเซ็ตฟอร์ม
 */
function resetForm() {
    console.log('Resetting form');
    
    const form = document.getElementById('attendanceForm');
    if (form) form.reset();
    
    uncheckAllStudents();
    
    // อัปเดต highlight ของแถว
    const rows = document.querySelectorAll('#studentList tr');
    rows.forEach(row => {
        row.classList.remove('selected-row');
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}
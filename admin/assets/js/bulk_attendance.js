/**
 * bulk_attendance.js - จัดการการเช็คชื่อนักเรียนแบบกลุ่ม
 * ปรับปรุงใหม่: รองรับแท็บ, ค้นหาโดยชื่อ, แสดงไอคอนวิธีการเช็ค
 */

document.addEventListener('DOMContentLoaded', function() {
    // เชื่อมต่อปุ่มค้นหา
    document.getElementById('btnSearch').addEventListener('click', searchStudents);
    
    // เชื่อมต่อ event สำหรับการเปลี่ยนแปลงแผนกและระดับชั้น
    document.getElementById('filterDepartment').addEventListener('change', updateClasses);
    document.getElementById('filterLevel').addEventListener('change', updateClasses);
    
    // เชื่อมต่อ event สำหรับค้นหาด้วยชื่อหรือรหัส
    document.getElementById('searchStudent').addEventListener('keyup', function(event) {
        // ถ้ากด Enter ให้ค้นหาทันที
        if (event.key === 'Enter') {
            searchStudents();
        }
    });
    
    // เชื่อมต่อ event สำหรับฟอร์ม
    document.getElementById('attendanceForm').addEventListener('submit', function(event) {
        // ตรวจสอบว่ามีการเลือกนักเรียนหรือไม่
        const checkedStudents = document.querySelectorAll('input[name^="attendance"][type="checkbox"]:checked');
        if (checkedStudents.length === 0) {
            event.preventDefault();
            alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
            return false;
        }
        
        // อัปเดตวันที่ให้ตรงกับที่เลือก
        document.getElementById('attendance_date').value = document.getElementById('filterDate').value;
        
        // ถามยืนยันก่อนบันทึก
        if (!confirm('ยืนยันการบันทึกการเช็คชื่อ?')) {
            event.preventDefault();
            return false;
        }
    });
    
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
    
    // เพิ่มแบดจ์จำนวนเริ่มต้นสำหรับแท็บต่างๆ
    document.getElementById('tab-all').innerHTML = '<span class="material-icons">people</span> ทั้งหมด';
    document.getElementById('tab-checked').innerHTML = '<span class="material-icons">check_circle</span> เช็คชื่อแล้ว <span class="badge bg-primary">0</span>';
    document.getElementById('tab-unchecked').innerHTML = '<span class="material-icons">pending</span> ยังไม่เช็คชื่อ <span class="badge bg-secondary">0</span>';
    document.getElementById('tab-present').innerHTML = '<span class="material-icons">verified</span> มาเรียน <span class="badge bg-success">0</span>';
    document.getElementById('tab-absent').innerHTML = '<span class="material-icons">cancel</span> ขาดเรียน <span class="badge bg-danger">0</span>';
    document.getElementById('tab-late').innerHTML = '<span class="material-icons">watch_later</span> มาสาย <span class="badge bg-warning">0</span>';
    document.getElementById('tab-leave').innerHTML = '<span class="material-icons">event_note</span> ลา <span class="badge bg-info">0</span>';
});

/**
 * อัปเดตรายการห้องเรียนตามแผนกและระดับชั้นที่เลือก
 * @param {Function} callback - ฟังก์ชันที่จะเรียกใช้หลังจากอัปเดตตัวเลือกห้องเรียนเสร็จ
 */
function updateClasses(callback) {
    const departmentId = document.getElementById('filterDepartment').value;
    const level = document.getElementById('filterLevel').value;
    const classSelect = document.getElementById('filterClass');
    
    // ล้างตัวเลือกเดิมยกเว้นตัวแรก
    while (classSelect.options.length > 1) {
        classSelect.remove(1);
    }
    
    // ถ้าไม่ได้เลือกแผนกหรือระดับชั้น ไม่ต้องดึงข้อมูลเพิ่ม
    if (!departmentId && !level) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }
    
    // แสดง loading
    classSelect.disabled = true;
    
    // สร้าง URL สำหรับดึงข้อมูล
    let url = 'ajax/get_classes.php?';
    if (departmentId) url += 'department_id=' + departmentId + '&';
    if (level) url += 'level=' + level;
    
    // ดึงข้อมูลห้องเรียน
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.classes) {
                // เพิ่มตัวเลือกห้องเรียน
                data.classes.forEach(classItem => {
                    const option = document.createElement('option');
                    option.value = classItem.class_id;
                    option.textContent = `${classItem.level}/${classItem.group_number} ${classItem.department_name}`;
                    classSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error fetching classes:', error);
        })
        .finally(() => {
            classSelect.disabled = false;
            
            // เรียกใช้ callback หลังจากอัปเดตตัวเลือกห้องเรียนเสร็จสิ้น
            if (typeof callback === 'function') {
                callback();
            }
        });
}

/**
 * ค้นหานักเรียนตามเงื่อนไขที่เลือก
 */
function searchStudents() {
    const departmentId = document.getElementById('filterDepartment').value;
    const level = document.getElementById('filterLevel').value;
    const classId = document.getElementById('filterClass').value;
    const date = document.getElementById('filterDate').value;
    const searchTerm = document.getElementById('searchStudent').value.trim();
    
    // ตรวจสอบว่าเลือกเงื่อนไขการค้นหาอย่างน้อย 1 อย่างหรือมีคำค้นหา
    if (!departmentId && !level && !classId && !searchTerm) {
        alert('กรุณาเลือกแผนกวิชา ระดับชั้น กลุ่มเรียน หรือระบุคำค้นหาอย่างน้อย 1 อย่าง');
        return;
    }
    
    // ซ่อนข้อความและแสดง loading
    document.getElementById('studentListContainer').style.display = 'none';
    document.getElementById('noStudentsMessage').style.display = 'none';
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('formActions').style.display = 'none';
    document.getElementById('attendanceSummary').style.display = 'none';
    
    // สร้าง URL สำหรับดึงข้อมูล
    let url = 'ajax/get_students_for_attendance.php?';
    if (departmentId) url += 'department_id=' + departmentId + '&';
    if (level) url += 'level=' + level + '&';
    if (classId) url += 'class_id=' + classId + '&';
    url += 'date=' + date;
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    
    // ดึงข้อมูลนักเรียน
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.students && data.students.length > 0) {
                    // แสดงชื่อห้องหรือกลุ่มที่เลือก
                    document.getElementById('classTitle').textContent = data.class_info || '';
                    
                    // บันทึก class_id ถ้ามี
                    if (data.class_id) {
                        document.getElementById('class_id').value = data.class_id;
                    }
                    
                    // แสดงรายชื่อนักเรียน
                    renderStudentList(data.students);
                    document.getElementById('studentListContainer').style.display = 'block';
                    document.getElementById('formActions').style.display = 'flex';
                    
                    // แสดงสรุปการเช็คชื่อทันที
                    document.getElementById('attendanceSummary').style.display = 'block';
                    updateAttendanceSummary();
                    
                    // กรองข้อมูลตามแท็บที่กำลังเลือกอยู่
                    const activeTab = document.querySelector('.tab.active').id.replace('tab-', '');
                    if (activeTab !== 'all') {
                        filterStudentsByTab(activeTab);
                    }
                } else {
                    // ไม่พบนักเรียน
                    document.getElementById('noStudentsMessage').style.display = 'block';
                }
            } else {
                // เกิดข้อผิดพลาด
                alert(data.error || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                document.getElementById('noStudentsMessage').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
            document.getElementById('noStudentsMessage').style.display = 'block';
        })
        .finally(() => {
            document.getElementById('loadingIndicator').style.display = 'none';
        });
}

/**
 * แสดงรายชื่อนักเรียน
 */
function renderStudentList(students) {
    const studentList = document.getElementById('studentList');
    
    // ล้างรายการเดิม
    studentList.innerHTML = '';
    
    // เพิ่มรายชื่อนักเรียน
    students.forEach((student, index) => {
        const row = document.createElement('tr');
        
        // กำหนดคุณสมบัติสำหรับการกรอง
        row.classList.add('student-row');
        row.setAttribute('data-student-id', student.student_id);
        row.setAttribute('data-student-code', student.student_code);
        row.setAttribute('data-student-name', (student.title || '') + student.first_name + ' ' + student.last_name);
        row.setAttribute('data-status', student.attendance_status || 'absent');
        row.setAttribute('data-checked', student.attendance_status ? 'true' : 'false');
        
        // สถานะการเช็คชื่อเดิม (ถ้ามี)
        const checkedAttr = student.attendance_status ? 'checked' : '';
        const status = student.attendance_status || 'absent';
        
        // ไอคอนสำหรับวิธีเช็คชื่อ
        let methodIcon = '';
        switch(student.check_method) {
            case 'GPS':
                methodIcon = '<span class="material-icons" style="color: #28a745;" title="เช็คชื่อผ่าน GPS">gps_fixed</span>';
                break;
            case 'QR_Code':
                methodIcon = '<span class="material-icons" style="color: #007bff;" title="เช็คชื่อผ่าน QR Code">qr_code_scanner</span>';
                break;
            case 'PIN':
                methodIcon = '<span class="material-icons" style="color: #fd7e14;" title="เช็คชื่อผ่านรหัส PIN">pin</span>';
                break;
            case 'Manual':
                methodIcon = '<span class="material-icons" style="color: #6c757d;" title="เช็คชื่อด้วยตนเอง">edit</span>';
                break;
            default:
                methodIcon = '';
        }
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <input type="checkbox" name="attendance[${student.student_id}][check]" value="1" ${checkedAttr} onchange="updateRowHighlight(this)">
            </td>
            <td>${student.student_code}</td>
            <td>${student.title || ''}${student.first_name} ${student.last_name}</td>
            <td>${student.level || ''}/${student.group_number || ''}</td>
            <td>
                <select class="form-control form-control-sm" name="attendance[${student.student_id}][status]" onchange="updateRowStatus(this)">
                    <option value="present" ${status === 'present' ? 'selected' : ''}>มาเรียน</option>
                    <option value="late" ${status === 'late' ? 'selected' : ''}>มาสาย</option>
                    <option value="absent" ${status === 'absent' ? 'selected' : ''}>ขาดเรียน</option>
                    <option value="leave" ${status === 'leave' ? 'selected' : ''}>ลา</option>
                </select>
            </td>
            <td>${student.check_time || '-'}</td>
            <td>${methodIcon}</td>
            <td>
                <input type="text" class="form-control form-control-sm" name="attendance[${student.student_id}][remarks]" 
                       placeholder="หมายเหตุ" value="${student.remarks || ''}">
            </td>
        `;
        
        // เพิ่ม highlight ถ้ามีการเช็คชื่อแล้ว
        if (student.attendance_status) {
            row.classList.add('selected-row');
        }
        
        // เพิ่มสีพื้นหลังตามสถานะ
        if (student.attendance_status) {
            switch(student.attendance_status) {
                case 'present':
                    row.classList.add('status-present');
                    break;
                case 'late':
                    row.classList.add('status-late');
                    break;
                case 'absent':
                    row.classList.add('status-absent');
                    break;
                case 'leave':
                    row.classList.add('status-leave');
                    break;
            }
        }
        
        studentList.appendChild(row);
    });
    
    // แสดงสรุปจำนวนต่างๆ ทันที
    document.getElementById('attendanceSummary').style.display = 'block';
    updateAttendanceSummary();
    
    // หลังจากแสดงข้อมูลแล้ว ให้กรองข้อมูลตามแท็บอีกครั้ง
    const activeTab = document.querySelector('.tab.active');
    if (activeTab) {
        const tabId = activeTab.id;
        const tabName = tabId.replace('tab-', '');
        if (tabName !== 'all') {
            filterStudentsByTab(tabName);
        }
    }
}

/**
 * อัปเดต highlight แถวตามการเลือก
 */
function updateRowHighlight(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
        row.classList.add('selected-row');
    } else {
        row.classList.remove('selected-row');
    }
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * อัปเดตสถานะการเช็คชื่อและสีพื้นหลัง
 */
function updateRowStatus(select) {
    const row = select.closest('tr');
    const status = select.value;
    
    // ลบคลาสสถานะเดิม
    row.classList.remove('status-present', 'status-late', 'status-absent', 'status-leave');
    
    // เพิ่มคลาสสถานะใหม่
    row.classList.add('status-' + status);
    
    // อัปเดตข้อมูลสถานะ
    row.setAttribute('data-status', status);
    
    // ถ้าเปลี่ยนสถานะ ต้องเช็คกล่องด้วย
    const checkbox = row.querySelector('input[type="checkbox"]');
    if (!checkbox.checked) {
        checkbox.checked = true;
        row.classList.add('selected-row');
    }
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * อัปเดตสรุปการเช็คชื่อ
 */
function updateAttendanceSummary() {
    // นับจำนวนสถานะทั้งหมด (ไม่ว่าจะเช็ค checkbox หรือไม่)
    const rows = document.querySelectorAll('#studentList tr.student-row');
    let totalPresent = 0;
    let totalLate = 0;
    let totalAbsent = 0;
    let totalLeave = 0;
    
    rows.forEach(row => {
        // ดูว่าสถานะปัจจุบันเป็นอะไร
        const select = row.querySelector('select');
        if (select) {
            switch (select.value) {
                case 'present':
                    totalPresent++;
                    break;
                case 'late':
                    totalLate++;
                    break;
                case 'absent':
                    totalAbsent++;
                    break;
                case 'leave':
                    totalLeave++;
                    break;
            }
        }
    });
    
    // อัปเดตค่าในหน้าเว็บ
    document.getElementById('presentCount').textContent = totalPresent;
    document.getElementById('lateCount').textContent = totalLate;
    document.getElementById('absentCount').textContent = totalAbsent;
    document.getElementById('leaveCount').textContent = totalLeave;
    
    // อัปเดตข้อมูลในแท็บ (ถ้ามีฟังก์ชันนี้)
    if (typeof updateTabCounts === 'function') {
        updateTabCounts(totalPresent, totalLate, totalAbsent, totalLeave);
    }
    
    // อัปเดตจำนวนในแท็บเพื่อแสดงสถิติ
    document.getElementById('tab-present').innerHTML = '<span class="material-icons">verified</span> มาเรียน <span class="badge bg-success">' + totalPresent + '</span>';
    document.getElementById('tab-late').innerHTML = '<span class="material-icons">watch_later</span> มาสาย <span class="badge bg-warning">' + totalLate + '</span>';
    document.getElementById('tab-absent').innerHTML = '<span class="material-icons">cancel</span> ขาดเรียน <span class="badge bg-danger">' + totalAbsent + '</span>';
    document.getElementById('tab-leave').innerHTML = '<span class="material-icons">event_note</span> ลา <span class="badge bg-info">' + totalLeave + '</span>';
    
    // คำนวณจำนวนที่เช็คชื่อแล้วและยังไม่ได้เช็ค
    const checked = totalPresent + totalLate + totalAbsent + totalLeave;
    const total = rows.length;
    const unchecked = total - checked;
    
    document.getElementById('tab-checked').innerHTML = '<span class="material-icons">check_circle</span> เช็คชื่อแล้ว <span class="badge bg-primary">' + checked + '</span>';
    document.getElementById('tab-unchecked').innerHTML = '<span class="material-icons">pending</span> ยังไม่เช็คชื่อ <span class="badge bg-secondary">' + unchecked + '</span>';
}

/**
 * อัปเดตจำนวนในแท็บ
 */
function updateTabCounts(presentCount, lateCount, absentCount, leaveCount) {
    const checkedCount = presentCount + lateCount + absentCount + leaveCount;
    const totalCount = document.querySelectorAll('.student-row').length;
    const uncheckedCount = totalCount - checkedCount;
    
    // อัปเดตจำนวนในแท็บ (ถ้าต้องการแสดงจำนวน)
    // สามารถเพิ่มองค์ประกอบใน HTML เพื่อแสดงจำนวน
}

/**
 * กรองนักเรียนตามคำค้นหา
 */
function filterStudentsBySearch(searchTerm) {
    const rows = document.querySelectorAll('.student-row');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const studentCode = row.getAttribute('data-student-code').toLowerCase();
        const studentName = row.getAttribute('data-student-name').toLowerCase();
        
        if (studentCode.includes(searchLower) || studentName.includes(searchLower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * เลือกนักเรียนทั้งหมด
 */
function checkAllStudents() {
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('tr').style.display !== 'none') { // เลือกเฉพาะแถวที่แสดงอยู่
            checkbox.checked = true;
            const row = checkbox.closest('tr');
            row.classList.add('selected-row');
        }
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * ยกเลิกการเลือกนักเรียนทั้งหมด
 */
function uncheckAllStudents() {
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const row = checkbox.closest('tr');
        row.classList.remove('selected-row');
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * กำหนดสถานะการเช็คชื่อทั้งหมด
 */
function setAllStatus(status) {
    // เลือกนักเรียนทั้งหมดก่อน
    checkAllStudents();
    
    // กำหนดสถานะให้ทั้งหมด
    const selects = document.querySelectorAll('select[name^="attendance"][name$="[status]"]');
    selects.forEach(select => {
        // เปลี่ยนเฉพาะแถวที่แสดงอยู่
        if (select.closest('tr').style.display !== 'none') {
            select.value = status;
            
            // อัปเดตสีพื้นหลังตามสถานะ
            const row = select.closest('tr');
            
            // ลบคลาสสถานะเดิม
            row.classList.remove('status-present', 'status-late', 'status-absent', 'status-leave');
            
            // เพิ่มคลาสสถานะใหม่
            row.classList.add('status-' + status);
            
            // อัปเดตข้อมูลสถานะ
            row.setAttribute('data-status', status);
        }
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * รีเซ็ตฟอร์ม
 */
function resetForm() {
    document.getElementById('attendanceForm').reset();
    uncheckAllStudents();
    
    // อัปเดต highlight ของแถว
    const rows = document.querySelectorAll('#studentList tr');
    rows.forEach(row => {
        row.classList.remove('selected-row');
        row.classList.remove('status-present', 'status-late', 'status-absent', 'status-leave');
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}
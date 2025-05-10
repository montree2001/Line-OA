/**
 * activity_attendance.js - จัดการการบันทึกการเข้าร่วมกิจกรรม
 */

document.addEventListener('DOMContentLoaded', function() {
    // เชื่อมต่อปุ่มค้นหา
    document.getElementById('btnSearch').addEventListener('click', searchStudents);
    
    // เชื่อมต่อ event สำหรับการเปลี่ยนแปลงแผนกและระดับชั้น
    document.getElementById('filterDepartment').addEventListener('change', updateClasses);
    document.getElementById('filterLevel').addEventListener('change', updateClasses);
    
    // เชื่อมต่อ event สำหรับฟอร์ม
    document.getElementById('attendanceForm').addEventListener('submit', function(event) {
        // ตรวจสอบว่ามีการเลือกนักเรียนหรือไม่
        const checkedStudents = document.querySelectorAll('input[name^="attendance"][type="checkbox"]:checked');
        if (checkedStudents.length === 0) {
            event.preventDefault();
            alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
            return false;
        }
        
        // ถามยืนยันก่อนบันทึก
        if (!confirm('ยืนยันการบันทึกการเข้าร่วมกิจกรรม?')) {
            event.preventDefault();
            return false;
        }
    });
    
    // ปรับปรุงตัวเลือกห้องเรียนเริ่มต้น
    updateClasses();
    
    // โหลดสรุปการเข้าร่วมกิจกรรม
    loadAttendanceSummary();
    
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
    const departmentId = document.getElementById('filterDepartment').value;
    const level = document.getElementById('filterLevel').value;
    const classSelect = document.getElementById('filterClass');
    
    // ล้างตัวเลือกเดิมยกเว้นตัวแรก
    while (classSelect.options.length > 1) {
        classSelect.remove(1);
    }
    
    // ถ้าไม่ได้เลือกแผนกหรือระดับชั้น ไม่ต้องดึงข้อมูลเพิ่ม
    if (!departmentId && !level) return;
    
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
        });
}

/**
 * ค้นหานักเรียนตามเงื่อนไขที่เลือก
 */
function searchStudents() {
    const departmentId = document.getElementById('filterDepartment').value;
    const level = document.getElementById('filterLevel').value;
    const classId = document.getElementById('filterClass').value;
    const search = document.getElementById('filterSearch').value;
    const activityId = document.querySelector('input[name="activity_id"]').value;
    
    // ตรวจสอบว่าเลือกเงื่อนไขการค้นหาอย่างน้อย 1 อย่าง
    if (!departmentId && !level && !classId && !search) {
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
    let url = 'ajax/get_students_for_activity.php?activity_id=' + activityId;
    if (departmentId) url += '&department_id=' + departmentId;
    if (level) url += '&level=' + level;
    if (classId) url += '&class_id=' + classId;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    // ดึงข้อมูลนักเรียน
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.students && data.students.length > 0) {
                    // แสดงชื่อห้องหรือกลุ่มที่เลือก
                    document.getElementById('classTitle').textContent = data.class_info || '';
                    
                    // แสดงรายชื่อนักเรียน
                    renderStudentList(data.students);
                    document.getElementById('studentListContainer').style.display = 'block';
                    document.getElementById('formActions').style.display = 'flex';
                    document.getElementById('attendanceSummary').style.display = 'block';
                    
                    // อัปเดตสรุปการเช็คชื่อ
                    updateAttendanceSummary();
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
        
        // สถานะการเช็คชื่อเดิม (ถ้ามี)
        const checkedAttr = student.attendance_status ? 'checked' : '';
        const status = student.attendance_status || 'absent';
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <input type="checkbox" name="attendance[${student.student_id}][check]" value="1" ${checkedAttr} onchange="updateRowHighlight(this)">
            </td>
            <td>${student.student_code}</td>
            <td>${student.title || ''}${student.first_name} ${student.last_name}</td>
            <td>${student.level || ''}/${student.group_number || ''} ${student.department_name || ''}</td>
            <td>
                <select class="form-control form-control-sm" name="attendance[${student.student_id}][status]" onchange="updateAttendanceSummary()">
                    <option value="present" ${status === 'present' ? 'selected' : ''}>เข้าร่วม</option>
                    <option value="absent" ${status === 'absent' ? 'selected' : ''}>ไม่เข้าร่วม</option>
                </select>
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
 * อัปเดตสรุปการเช็คชื่อ
 */
function updateAttendanceSummary() {
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    let presentCount = 0;
    let absentCount = 0;
    let totalCount = checkboxes.length;
    
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const studentId = checkbox.name.match(/\[(\d+)\]/)[1];
            const status = document.querySelector(`select[name="attendance[${studentId}][status]"]`).value;
            
            if (status === 'present') {
                presentCount++;
            } else {
                absentCount++;
            }
        }
    });
    
    // อัปเดตค่าในหน้าเว็บ
    document.getElementById('presentCount').textContent = presentCount;
    document.getElementById('absentCount').textContent = absentCount;
    document.getElementById('totalCount').textContent = totalCount;
}

/**
 * เลือกนักเรียนทั้งหมด
 */
function checkAllStudents() {
    const checkboxes = document.querySelectorAll('input[name^="attendance"][type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        const row = checkbox.closest('tr');
        row.classList.add('selected-row');
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
        select.value = status;
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
    });
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * โหลดสรุปการเข้าร่วมกิจกรรม
 */
function loadAttendanceSummary() {
    const activityId = document.querySelector('input[name="activity_id"]').value;
    
    // แสดง loading
    document.getElementById('loadingAttendanceSummary').style.display = 'block';
    document.getElementById('attendanceSummaryContent').style.display = 'none';
    document.getElementById('noAttendanceSummary').style.display = 'none';
    
    // ดึงข้อมูลสรุปการเข้าร่วมกิจกรรม
    fetch(`ajax/get_activity_summary.php?activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.has_attendance) {
                    // แสดงข้อมูลสรุป
                    document.getElementById('summary-total').textContent = data.total_students;
                    document.getElementById('summary-present').textContent = data.present_count;
                    document.getElementById('summary-absent').textContent = data.absent_count;
                    document.getElementById('summary-percent').textContent = data.attendance_percent + '%';
                    
                    // สร้างกราฟหรือแผนภูมิ
                    if (typeof Chart !== 'undefined') {
                        createDepartmentChart(data.department_summary);
                        createLevelChart(data.level_summary);
                    }
                    
                    document.getElementById('attendanceSummaryContent').style.display = 'block';
                } else {
                    // ยังไม่มีข้อมูลการเข้าร่วมกิจกรรม
                    document.getElementById('noAttendanceSummary').style.display = 'block';
                }
            } else {
                // เกิดข้อผิดพลาด
                console.error('Error loading attendance summary:', data.error);
                document.getElementById('noAttendanceSummary').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading attendance summary:', error);
            document.getElementById('noAttendanceSummary').style.display = 'block';
        })
        .finally(() => {
            document.getElementById('loadingAttendanceSummary').style.display = 'none';
        });
}

/**
 * สร้างกราฟสรุปตามแผนกวิชา
 */
function createDepartmentChart(data) {
    if (!data || data.length === 0) return;
    
    const ctx = document.getElementById('departmentChart').getContext('2d');
    
    // ล้างกราฟเดิม (ถ้ามี)
    if (window.departmentChart instanceof Chart) {
        window.departmentChart.destroy();
    }
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = data.map(item => item.department_name);
    const presentData = data.map(item => item.present_count);
    const absentData = data.map(item => item.absent_count);
    
    // สร้างกราฟใหม่
    window.departmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'เข้าร่วม',
                    data: presentData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'ไม่เข้าร่วม',
                    data: absentData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: false
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * สร้างกราฟสรุปตามระดับชั้น
 */
function createLevelChart(data) {
    if (!data || data.length === 0) return;
    
    const ctx = document.getElementById('levelChart').getContext('2d');
    
    // ล้างกราฟเดิม (ถ้ามี)
    if (window.levelChart instanceof Chart) {
        window.levelChart.destroy();
    }
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = data.map(item => item.level);
    const presentData = data.map(item => item.present_count);
    const absentData = data.map(item => item.absent_count);
    
    // สร้างกราฟใหม่
    window.levelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'เข้าร่วม',
                    data: presentData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'ไม่เข้าร่วม',
                    data: absentData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: false
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            }
        }
    });
}
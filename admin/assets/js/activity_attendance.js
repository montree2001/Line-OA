/**
 * activity_attendance.js - จัดการการบันทึกการเข้าร่วมกิจกรรม
 */

document.addEventListener('DOMContentLoaded', function() {
    // เชื่อมต่อปุ่มค้นหา
    const btnSearch = document.getElementById('btnSearch');
    if (btnSearch) {
        btnSearch.addEventListener('click', searchStudents);
    }
    
    // เชื่อมต่อ event สำหรับการเปลี่ยนแปลงแผนกและระดับชั้น
    const filterDepartment = document.getElementById('filterDepartment');
    const filterLevel = document.getElementById('filterLevel');
    
    if (filterDepartment) {
        filterDepartment.addEventListener('change', updateClasses);
    }
    
    if (filterLevel) {
        filterLevel.addEventListener('change', updateClasses);
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
            
            // ถามยืนยันก่อนบันทึก
            if (!confirm('ยืนยันการบันทึกการเข้าร่วมกิจกรรม?')) {
                event.preventDefault();
                return false;
            }
        });
    }
    
    // เชื่อมต่อปุ่มส่งออกข้อมูล
    const btnExport = document.getElementById('btnExport');
    if (btnExport) {
        btnExport.addEventListener('click', exportToExcel);
    }
    
    // ปรับปรุงตัวเลือกห้องเรียนเริ่มต้น
    updateClasses();
    
    // โหลดสรุปการเข้าร่วมกิจกรรม
    loadAttendanceSummary();
    
    // โหลดข้อมูลสถิติเบื้องต้นทันที
    loadQuickStats();
    
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
    
    // ปรับขนาดกราฟเมื่อหน้าจอเปลี่ยนขนาด
    window.addEventListener('resize', function() {
        adjustChartSize();
    });
});

/**
 * อัปเดตรายการห้องเรียนตามแผนกและระดับชั้นที่เลือก
 */
function updateClasses() {
    const departmentSelect = document.getElementById('filterDepartment');
    const levelSelect = document.getElementById('filterLevel');
    const classSelect = document.getElementById('filterClass');
    
    if (!departmentSelect || !levelSelect || !classSelect) return;
    
    const departmentId = departmentSelect.value;
    const level = levelSelect.value;
    
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
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
    // รับค่าจากฟอร์ม
    const departmentId = document.getElementById('filterDepartment')?.value || '';
    const level = document.getElementById('filterLevel')?.value || '';
    const classId = document.getElementById('filterClass')?.value || '';
    const search = document.getElementById('filterSearch')?.value || '';
    const activityId = document.querySelector('input[name="activity_id"]')?.value;
    
    if (!activityId) {
        console.error('ไม่พบรหัสกิจกรรม');
        return;
    }
    
    // ตรวจสอบว่าเลือกเงื่อนไขการค้นหาอย่างน้อย 1 อย่าง
    if (!departmentId && !level && !classId && !search) {
        alert('กรุณาเลือกแผนกวิชา ระดับชั้น กลุ่มเรียน หรือระบุคำค้นหาอย่างน้อย 1 อย่าง');
        return;
    }
    
    // ซ่อนข้อความและแสดง loading
    showLoading(true);
    
    // สร้าง URL สำหรับดึงข้อมูล
    let url = `ajax/get_students_for_activity.php?activity_id=${activityId}`;
    if (departmentId) url += `&department_id=${departmentId}`;
    if (level) url += `&level=${level}`;
    if (classId) url += `&class_id=${classId}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    // ดึงข้อมูลนักเรียน
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.students && data.students.length > 0) {
                    displayStudentList(data);
                } else {
                    showNoStudentsMessage();
                }
            } else {
                handleApiError(data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            handleFetchError();
        })
        .finally(() => {
            showLoading(false);
        });
}

/**
 * แสดงรายชื่อนักเรียนและข้อมูลที่เกี่ยวข้อง
 */
function displayStudentList(data) {
    // แสดงชื่อห้องหรือกลุ่มที่เลือก
    const classTitle = document.getElementById('classTitle');
    if (classTitle) {
        classTitle.textContent = data.class_info || '';
    }
    
    // แสดงรายชื่อนักเรียน
    renderStudentList(data.students);
    
    // แสดงส่วนที่เกี่ยวข้อง
    const studentListContainer = document.getElementById('studentListContainer');
    const formActions = document.getElementById('formActions');
    const attendanceSummary = document.getElementById('attendanceSummary');
    
    if (studentListContainer) studentListContainer.style.display = 'block';
    if (formActions) formActions.style.display = 'flex';
    if (attendanceSummary) attendanceSummary.style.display = 'block';
    
    // อัปเดตสรุปการเช็คชื่อ
    updateAttendanceSummary();
}

/**
 * แสดง/ซ่อนการโหลด
 */
function showLoading(isLoading) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const studentListContainer = document.getElementById('studentListContainer');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    const formActions = document.getElementById('formActions');
    const attendanceSummary = document.getElementById('attendanceSummary');
    
    if (loadingIndicator) loadingIndicator.style.display = isLoading ? 'block' : 'none';
    
    if (!isLoading) return;
    
    if (studentListContainer) studentListContainer.style.display = 'none';
    if (noStudentsMessage) noStudentsMessage.style.display = 'none';
    if (formActions) formActions.style.display = 'none';
    if (attendanceSummary) attendanceSummary.style.display = 'none';
}

/**
 * แสดงข้อความเมื่อไม่พบนักเรียน
 */
function showNoStudentsMessage() {
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    if (noStudentsMessage) noStudentsMessage.style.display = 'block';
}

/**
 * จัดการข้อผิดพลาดจาก API
 */
function handleApiError(errorMessage) {
    alert(errorMessage || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    if (noStudentsMessage) noStudentsMessage.style.display = 'block';
}

/**
 * จัดการข้อผิดพลาดจากการเชื่อมต่อ
 */
function handleFetchError() {
    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    if (noStudentsMessage) noStudentsMessage.style.display = 'block';
}

/**
 * แสดงรายชื่อนักเรียน
 */
function renderStudentList(students) {
    const studentList = document.getElementById('studentList');
    if (!studentList) return;
    
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
    if (!checkbox) return;
    
    const row = checkbox.closest('tr');
    if (row) {
        if (checkbox.checked) {
            row.classList.add('selected-row');
        } else {
            row.classList.remove('selected-row');
        }
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
            const studentId = checkbox.name.match(/\[(\d+)\]/);
            if (studentId && studentId[1]) {
                const statusSelect = document.querySelector(`select[name="attendance[${studentId[1]}][status]"]`);
                if (statusSelect) {
                    const status = statusSelect.value;
                    if (status === 'present') {
                        presentCount++;
                    } else {
                        absentCount++;
                    }
                }
            }
        }
    });
    
    // อัปเดตค่าในหน้าเว็บ
    const presentCountEl = document.getElementById('presentCount');
    const absentCountEl = document.getElementById('absentCount');
    const totalCountEl = document.getElementById('totalCount');
    
    if (presentCountEl) presentCountEl.textContent = presentCount;
    if (absentCountEl) absentCountEl.textContent = absentCount;
    if (totalCountEl) totalCountEl.textContent = totalCount;
}

/**
 * เลือกนักเรียนทั้งหมด
 */
function checkAllStudents() {
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

/**
 * โหลดสรุปการเข้าร่วมกิจกรรม
 */
function loadAttendanceSummary() {
    const activityId = document.querySelector('input[name="activity_id"]')?.value;
    if (!activityId) return;
    
    // แสดง loading
    const loadingAttendanceSummary = document.getElementById('loadingAttendanceSummary');
    const attendanceSummaryContent = document.getElementById('attendanceSummaryContent');
    const noAttendanceSummary = document.getElementById('noAttendanceSummary');
    
    if (loadingAttendanceSummary) loadingAttendanceSummary.style.display = 'block';
    if (attendanceSummaryContent) attendanceSummaryContent.style.display = 'none';
    if (noAttendanceSummary) noAttendanceSummary.style.display = 'none';
    
    // ดึงข้อมูลสรุปการเข้าร่วมกิจกรรม
    fetch(`ajax/get_activity_summary.php?activity_id=${activityId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.has_attendance) {
                    // แสดงข้อมูลสรุป
                    const summaryTotal = document.getElementById('summary-total');
                    const summaryPresent = document.getElementById('summary-present');
                    const summaryAbsent = document.getElementById('summary-absent');
                    const summaryPercent = document.getElementById('summary-percent');
                    
                    if (summaryTotal) summaryTotal.textContent = data.total_students;
                    if (summaryPresent) summaryPresent.textContent = data.present_count;
                    if (summaryAbsent) summaryAbsent.textContent = data.absent_count;
                    if (summaryPercent) summaryPercent.textContent = data.attendance_percent + '%';
                    
                    // อัปเดตข้อมูลสถิติเบื้องต้น
                    updateQuickStats(data.total_students, data.present_count, data.attendance_percent);
                    
                    // สร้างกราฟหรือแผนภูมิ
                    if (typeof Chart !== 'undefined') {
                        if (data.department_summary && data.department_summary.length > 0) {
                            createDepartmentChart(data.department_summary);
                        }
                        
                        if (data.level_summary && data.level_summary.length > 0) {
                            createLevelChart(data.level_summary);
                        }
                    } else {
                        console.error('Chart.js library is not loaded');
                    }
                    
                    if (attendanceSummaryContent) attendanceSummaryContent.style.display = 'block';
                } else {
                    // ยังไม่มีข้อมูลการเข้าร่วมกิจกรรม
                    if (noAttendanceSummary) noAttendanceSummary.style.display = 'block';
                }
            } else {
                // เกิดข้อผิดพลาด
                console.error('Error loading attendance summary:', data.error);
                if (noAttendanceSummary) noAttendanceSummary.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading attendance summary:', error);
            if (noAttendanceSummary) noAttendanceSummary.style.display = 'block';
        })
        .finally(() => {
            if (loadingAttendanceSummary) loadingAttendanceSummary.style.display = 'none';
        });
}

/**
 * โหลดข้อมูลสถิติเบื้องต้น
 */
function loadQuickStats() {
    const activityId = document.querySelector('input[name="activity_id"]')?.value;
    if (!activityId) return;
    
    fetch(`ajax/get_activity_summary.php?activity_id=${activityId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const totalStudents = data.total_students || 0;
                const presentCount = data.present_count || 0;
                const attendancePercent = data.attendance_percent || 0;
                
                updateQuickStats(totalStudents, presentCount, attendancePercent);
            }
        })
        .catch(error => {
            console.error('Error loading quick stats:', error);
        });
}

/**
 * อัปเดตข้อมูลสถิติเบื้องต้น
 */
function updateQuickStats(totalStudents, presentCount, attendancePercent) {
    const quickTotalStudents = document.getElementById('quick-total-students');
    const quickPresentCount = document.getElementById('quick-present-count');
    const quickPercent = document.getElementById('quick-percent');
    
    if (quickTotalStudents) quickTotalStudents.textContent = totalStudents;
    if (quickPresentCount) quickPresentCount.textContent = presentCount;
    if (quickPercent) quickPercent.textContent = attendancePercent + '%';
}

/**
 * สร้างกราฟสรุปตามแผนกวิชา
 */
function createDepartmentChart(data) {
    if (!data || data.length === 0) return;
    
    const canvas = document.getElementById('departmentChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // ล้างกราฟเดิม (ถ้ามี)
    if (window.departmentChart instanceof Chart) {
        window.departmentChart.destroy();
    }
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = data.map(item => 
        // ตัดชื่อแผนกให้สั้นลงสำหรับการแสดงผลบนมือถือ
        window.innerWidth < 768 ? 
            (item.department_name.length > 10 ? 
                item.department_name.substring(0, 10) + '...' : 
                item.department_name) : 
            item.department_name
    );
    
    const presentData = data.map(item => parseInt(item.present_count) || 0);
    const absentData = data.map(item => parseInt(item.absent_count) || 0);
    
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
                    stacked: false,
                    ticks: {
                        font: {
                            size: window.innerWidth < 768 ? 8 : 12
                        }
                    }
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: window.innerWidth < 768 ? 'bottom' : 'top'
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            // แสดงชื่อแผนกเต็มใน tooltip
                            const index = tooltipItems[0].dataIndex;
                            return data[index].department_name;
                        }
                    }
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
    
    const canvas = document.getElementById('levelChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // ล้างกราฟเดิม (ถ้ามี)
    if (window.levelChart instanceof Chart) {
        window.levelChart.destroy();
    }
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = data.map(item => item.level);
    const presentData = data.map(item => parseInt(item.present_count) || 0);
    const absentData = data.map(item => parseInt(item.absent_count) || 0);
    
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
                    stacked: false,
                    ticks: {
                        font: {
                            size: window.innerWidth < 768 ? 8 : 12
                        }
                    }
                },
                y: {
                    stacked: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: window.innerWidth < 768 ? 'bottom' : 'top'
                }
            }
        }
    });
}

/**
 * ปรับขนาดกราฟตามขนาดหน้าจอ
 */
function adjustChartSize() {
    if (window.departmentChart) {
        window.departmentChart.options.scales.x.ticks.font.size = window.innerWidth < 768 ? 8 : 12;
        window.departmentChart.options.plugins.legend.position = window.innerWidth < 768 ? 'bottom' : 'top';
        window.departmentChart.update();
    }
    
    if (window.levelChart) {
        window.levelChart.options.scales.x.ticks.font.size = window.innerWidth < 768 ? 8 : 12;
        window.levelChart.options.plugins.legend.position = window.innerWidth < 768 ? 'bottom' : 'top';
        window.levelChart.update();
    }
}

/**
 * ส่งออกข้อมูลเป็นไฟล์ Excel
 */
function exportToExcel() {
    const activityId = document.querySelector('input[name="activity_id"]')?.value;
    if (!activityId) {
        alert('ไม่พบรหัสกิจกรรม');
        return;
    }
    
    // ตรวจสอบว่ามีนักเรียนในรายการหรือไม่
    const studentList = document.getElementById('studentList');
    if (!studentList || studentList.children.length === 0 || studentList.querySelector('td[colspan]')) {
        alert('กรุณาค้นหานักเรียนก่อนส่งออกข้อมูล');
        return;
    }
    
    // แสดงการโหลด
    const exportButton = document.getElementById('btnExport');
    if (exportButton) {
        exportButton.disabled = true;
        const originalText = exportButton.innerHTML;
        exportButton.innerHTML = '<span class="material-icons">hourglass_top</span> กำลังส่งออก...';
        
        // คืนค่าปุ่มเป็นปกติหลังจาก 3 วินาที
        setTimeout(() => {
            if (exportButton) {
                exportButton.disabled = false;
                exportButton.innerHTML = originalText;
            }
        }, 3000);
    }
    
    // สร้าง URL สำหรับดาวน์โหลด
    const url = `ajax/export_attendance.php?activity_id=${activityId}`;
    
    // เปิดหน้าต่างใหม่สำหรับดาวน์โหลด
    window.open(url, '_blank');
}
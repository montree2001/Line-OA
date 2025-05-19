/**
 * ล้างข้อมูลการค้นหาทั้งหมด
 */
function clearFilters() {
    // ล้างค่าในช่องค้นหา
    $('#studentCodeFilter').val('');
    $('#studentNameFilter').val('');
    $('#levelFilter').val('');
    $('#groupFilter').val('');
    $('#departmentFilter').val('');
    $('#advisorFilter').val('');
    
    // คืนค่าตัวเลือกสถานะเริ่มต้น
    $('#lineStatusFilter').val('connected');
    $('#studentStatusFilter').val('กำลังศึกษา');
    
    // ล้างข้อมูลในตาราง
    const table = $('#studentPreviewTable').DataTable();
    table.clear().draw();
    
    // รีเซ็ตจำนวนที่เลือก
    document.getElementById('studentCount').textContent = '0';
    
    // แสดงข้อความ
    showAlert('ล้างการค้นหาเรียบร้อยแล้ว', 'info');
}/**
 * line-disconnect.js - สคริปต์สำหรับการยกเลิกการเชื่อมต่อ LINE
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("เริ่มต้นหน้ายกเลิกการเชื่อมต่อ LINE...");
    
    // โหลดข้อมูลแผนกวิชา
    loadDepartments();
    
    // โหลดข้อมูลครูที่ปรึกษา
    loadAdvisors();
    
    // DataTable สำหรับแสดงผลนักเรียน
    let previewTable = $('#studentPreviewTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
        },
        "responsive": true,
        "order": [
            [1, "asc"]
        ],
        "columnDefs": [
            { "orderable": false, "targets": 0 },
            { "className": "dt-center", "targets": [0, 5] }
        ],
        "dom": 'rtip' // ซ่อนตัวค้นหาและแสดงจำนวนหน้า
    });
    
    // ปุ่มค้นหานักเรียน
    $('#searchStudentsBtn').on('click', function() {
        searchStudents();
    });
    
    // ปุ่มล้างการค้นหา
    $('#clearFilterBtn').on('click', function() {
        clearFilters();
    });
    
    // รองรับการกด Enter ในช่องค้นหา
    $('#studentCodeFilter, #studentNameFilter').on('keypress', function(e) {
        if (e.which === 13) { // รหัส Enter คือ 13
            e.preventDefault();
            searchStudents();
        }
    });
    
    // Checkbox เลือกทั้งหมด
    $('#selectAllStudents').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.student-checkbox').prop('checked', isChecked);
        updateSelectedCount();
        validateForm();
    });
    
    // ตรวจสอบการป้อนรหัสผ่าน
    $('#adminPassword').on('input', function() {
        validateForm();
    });
    
    // ปุ่มยืนยันการยกเลิกการเชื่อมต่อ
    $('#confirmDisconnectBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        
        // แสดง confirmation dialog
        if (confirm('คุณแน่ใจที่จะยกเลิกการเชื่อมต่อ LINE ของนักเรียนที่เลือกหรือไม่?')) {
            disconnectLineAccounts();
        }
    });
    
    // ตรวจสอบการเปลี่ยนแปลงใน checkbox ของนักเรียนแต่ละคน (event delegation)
    $('#studentPreviewTable').on('change', '.student-checkbox', function() {
        updateSelectedCount();
        validateForm();
    });
});

/**
 * โหลดข้อมูลแผนกวิชาทั้งหมด
 */
function loadDepartments() {
    fetch('api/departments_api.php?action=get_departments')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.departments) {
                const select = document.getElementById('departmentFilter');
                
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.department_id;
                    option.textContent = dept.department_name;
                    select.appendChild(option);
                });
                
                console.log("โหลดข้อมูลแผนกวิชาสำเร็จ:", data.departments.length, "รายการ");
            } else {
                console.error("ไม่สามารถโหลดข้อมูลแผนกวิชาได้:", data.message || "ไม่ทราบสาเหตุ");
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูลแผนกวิชา:', error);
        });
}

/**
 * โหลดข้อมูลครูที่ปรึกษาทั้งหมด
 */
function loadAdvisors() {
    fetch('api/teachers_api.php?action=get_advisors')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.advisors) {
                const select = document.getElementById('advisorFilter');
                
                data.advisors.forEach(advisor => {
                    const option = document.createElement('option');
                    option.value = advisor.teacher_id;
                    option.textContent = `${advisor.title}${advisor.first_name} ${advisor.last_name}`;
                    select.appendChild(option);
                });
                
                console.log("โหลดข้อมูลครูที่ปรึกษาสำเร็จ:", data.advisors.length, "รายการ");
            } else {
                console.error("ไม่สามารถโหลดข้อมูลครูที่ปรึกษาได้:", data.message || "ไม่ทราบสาเหตุ");
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูลครูที่ปรึกษา:', error);
        });
}

/**
 * ค้นหานักเรียนตามเงื่อนไข
 */
function searchStudents() {
    // แสดงโหลดดิ้ง
    showLoading();
    
    // รวบรวมข้อมูลฟอร์ม
    const formData = new FormData(document.getElementById('disconnectFilterForm'));
    formData.append('action', 'search_students_for_disconnect');
    
    console.log("กำลังค้นหานักเรียน...");
    
    // ส่งข้อมูลไปยัง API
    fetch('api/line_disconnect_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("ได้รับการตอบกลับจาก API:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("ข้อมูลที่ได้รับ:", data);
        
        if (data.success) {
            // ล้างข้อมูลเดิม
            const table = $('#studentPreviewTable').DataTable();
            table.clear();
            
            if (data.students && data.students.length > 0) {
                console.log(`พบนักเรียน ${data.students.length} คน`);
                
                // เพิ่มข้อมูลใหม่
                data.students.forEach(student => {
                    let avatarHtml;
                    if (student.profile_picture && student.line_connected) {
                        avatarHtml = `<div class="student-avatar"><img src="${student.profile_picture}" alt="${student.first_name}" class="profile-image"></div>`;
                    } else {
                        avatarHtml = `<div class="student-avatar">${student.first_name ? student.first_name.charAt(0).toUpperCase() : '?'}</div>`;
                    }
                    
                    const studentInfo = `
                        <div class="student-info">
                            ${avatarHtml}
                            <div class="student-details">
                                <div class="student-name">${student.title || ''}${student.first_name || ''} ${student.last_name || ''}</div>
                                <div class="student-class">${student.student_code || ''}</div>
                            </div>
                        </div>
                    `;
                    
                    const statusBadgeClass = getStatusBadgeClass(student.status);
                    const statusBadge = `<span class="status-badge ${statusBadgeClass}">${student.status || ''}</span>`;
                    
                    table.row.add([
                        `<input type="checkbox" class="student-checkbox" value="${student.student_id}" data-user-id="${student.user_id}">`,
                        student.student_code || '',
                        studentInfo,
                        student.class || '-',
                        student.department_name || '-',
                        statusBadge
                    ]);
                });
                
                table.draw();
                console.log("วาดตารางเรียบร้อย");
                
                // อัพเดตจำนวนนักเรียนที่พบ
                document.getElementById('studentCount').textContent = data.students.length;
                
                // รีเซ็ต checkbox เลือกทั้งหมด
                $('#selectAllStudents').prop('checked', false);
                
                // แสดงข้อความสำเร็จ
                showAlert(`ค้นพบนักเรียน ${data.students.length} คน`, 'success');
            } else {
                // กรณีไม่พบข้อมูล
                console.log("ไม่พบข้อมูลนักเรียน");
                table.draw();
                document.getElementById('studentCount').textContent = '0';
                showAlert('ไม่พบนักเรียนที่ตรงตามเงื่อนไข', 'warning');
            }
        } else {
            console.error("การค้นหาไม่สำเร็จ:", data.message);
            showAlert(data.message || 'เกิดข้อผิดพลาดในการค้นหานักเรียน', 'error');
        }
        
        // ซ่อนโหลดดิ้ง
        hideLoading();
        
        // ตรวจสอบฟอร์ม
        validateForm();
    })
    .catch(error => {
        console.error('Error searching students:', error);
        showAlert('เกิดข้อผิดพลาดในการค้นหานักเรียน', 'error');
        hideLoading();
    });
}

/**
 * อัพเดตจำนวนนักเรียนที่เลือก
 */
function updateSelectedCount() {
    const selectedCount = $('.student-checkbox:checked').length;
    const totalCount = $('.student-checkbox').length;
    const countElement = document.getElementById('studentCount');
    
    if (selectedCount === 0) {
        countElement.textContent = totalCount;
    } else {
        countElement.textContent = `${selectedCount}/${totalCount}`;
    }
}

/**
 * ตรวจสอบและเปิดใช้งานปุ่มยืนยัน
 */
function validateForm() {
    const selectedCount = $('.student-checkbox:checked').length;
    const password = $('#adminPassword').val().trim();
    const confirmBtn = $('#confirmDisconnectBtn');
    
    if (selectedCount > 0 && password.length >= 4) {
        confirmBtn.prop('disabled', false);
    } else {
        confirmBtn.prop('disabled', true);
    }
}

/**
 * ดำเนินการยกเลิกการเชื่อมต่อ LINE
 */
function disconnectLineAccounts() {
    // แสดงโหลดดิ้ง
    showLoading();
    
    // รวบรวมข้อมูลนักเรียนที่เลือก
    const selectedStudents = [];
    $('.student-checkbox:checked').each(function() {
        selectedStudents.push({
            student_id: $(this).val(),
            user_id: $(this).data('user-id')
        });
    });
    
    const formData = new FormData();
    formData.append('action', 'disconnect_line_accounts');
    formData.append('students', JSON.stringify(selectedStudents));
    formData.append('admin_password', $('#adminPassword').val());
    
    // ส่งข้อมูลไปยัง API
    fetch('api/line_disconnect_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดงผลการยกเลิกการเชื่อมต่อ
            let resultHtml = `
                <div class="result-info mb-4">
                    <p>การยกเลิกการเชื่อมต่อ LINE เสร็จสิ้น</p>
                    <ul>
                        <li>จำนวนที่ทำรายการสำเร็จ: <strong>${data.success_count}</strong> คน</li>
                        <li>จำนวนที่มีปัญหา: <strong>${data.failed_count}</strong> คน</li>
                    </ul>
                </div>
            `;
            
            if (data.failed_students && data.failed_students.length > 0) {
                resultHtml += `
                    <div class="failed-list mb-3">
                        <h4>รายชื่อนักเรียนที่ไม่สามารถยกเลิกการเชื่อมต่อได้:</h4>
                        <ul>
                `;
                
                data.failed_students.forEach(student => {
                    resultHtml += `<li>${student.title}${student.first_name} ${student.last_name} (${student.student_code}) - ${student.error}</li>`;
                });
                
                resultHtml += `
                        </ul>
                    </div>
                `;
            }
            
            // แสดง Modal ผลลัพธ์
            document.getElementById('resultContent').innerHTML = resultHtml;
            showModal('resultModal');
            
            // รีเซ็ตฟอร์ม
            $('#adminPassword').val('');
            $('#confirmDisconnectBtn').prop('disabled', true);
            
            // ค้นหานักเรียนใหม่เพื่ออัพเดตข้อมูล
            if (data.success_count > 0) {
                setTimeout(() => {
                    searchStudents();
                }, 1000);
            }
        } else {
            // กรณีเกิดข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการยกเลิกการเชื่อมต่อ LINE', 'error');
        }
        
        // ซ่อนโหลดดิ้ง
        hideLoading();
    })
    .catch(error => {
        console.error('Error disconnecting LINE accounts:', error);
        showAlert('เกิดข้อผิดพลาดในการยกเลิกการเชื่อมต่อ LINE', 'error');
        hideLoading();
    });
}

/**
 * แสดงโหลดดิ้ง
 */
function showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'flex';
}

/**
 * ซ่อนโหลดดิ้ง
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'none';
}

/**
 * แสดง Modal
 * 
 * @param {string} modalId รหัส Modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }
}

/**
 * ปิด Modal
 * 
 * @param {string} modalId รหัส Modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message ข้อความ
 * @param {string} type ประเภท (success, error, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span class="alert-message">${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <span class="material-icons">close</span>
        </button>
    `;

    alertContainer.appendChild(alert);

    // ลบข้อความแจ้งเตือนอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

/**
 * ดึง class สำหรับแสดงสถานะของนักเรียน
 * 
 * @param {string} status สถานะนักเรียน
 * @returns {string} class สำหรับแสดงสถานะ
 */
function getStatusBadgeClass(status) {
    switch (status) {
        case 'กำลังศึกษา':
            return 'success';
        case 'พักการเรียน':
            return 'warning';
        case 'พ้นสภาพ':
            return 'danger';
        case 'สำเร็จการศึกษา':
            return 'info';
        default:
            return '';
    }
}
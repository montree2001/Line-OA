/**
 * fix_attendance.js - แก้ไขปัญหาระบบเช็คชื่อนักเรียน
 * 
 * ไฟล์นี้รวมการแก้ไขปัญหาในระบบเช็คชื่อนักเรียน โดยกำหนดฟังก์ชันที่จำเป็น
 * สำหรับการเช็คชื่อนักเรียนในรูปแบบต่างๆ
 */

// ตัวแปรสำหรับเก็บข้อมูลการเช็คชื่อ
let attendanceData = {
    class_id: currentClassId || 0,
    date: checkDate || new Date().toISOString().split('T')[0],
    teacher_id: teacherId || 0,
    students: [],
    is_retroactive: isRetroactive || false,
    check_method: 'Manual'
};

// ตัวแปรเก็บสถานะว่ามีการเปลี่ยนแปลงข้อมูลหรือไม่
let hasChanges = false;

/**
 * เช็คชื่อนักเรียนและบันทึกทันที (AJAX)
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเช็คชื่อ (present/absent/late/leave)
 * @param {number} studentId - รหัสนักเรียน
 */
function markAttendance(button, status, studentId) {
    try {
        // แสดงสถานะกำลังโหลด
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        // ดึงข้อมูลการ์ดนักเรียน
        const studentCard = button.closest('.student-card');

        if (!studentCard) {
            console.error('ไม่พบข้อมูล .student-card สำหรับปุ่มนี้:', button);
            showNotification('เกิดข้อผิดพลาด: กรุณารีเฟรชหน้าและลองใหม่อีกครั้ง', 'error');

            // คืนค่าปุ่มเดิม
            button.innerHTML = originalContent;
            button.disabled = false;
            return;
        }

        // ดึงข้อมูลนักเรียน
        const studentName = studentCard.getAttribute('data-name');

        // สร้างข้อมูลที่จะส่ง
        const data = {
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            is_retroactive: isRetroactive
        };

        console.log('กำลังส่งข้อมูลการเช็คชื่อ:', data);

        // ส่งข้อมูลไปบันทึก AJAX
        fetch('api/ajax_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server response was not OK: ' + response.status);
                }
                return response.json();
            })
            .then(responseData => {
                // คืนค่าปุ่มเดิม
                button.innerHTML = originalContent;
                button.disabled = false;

                if (responseData.success) {
                    // บันทึกสำเร็จ
                    console.log('เช็คชื่อสำเร็จ:', responseData);

                    // สร้างสถานะเช็คชื่อและเวลา
                    const timeChecked = responseData.student.time_checked || new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    const attendanceId = responseData.attendance_id;

                    // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
                    moveToCheckedTab(studentCard, studentId, status, timeChecked, attendanceId);

                    // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
                    updateStudentCounts();

                    // อัพเดทสถิติการเช็คชื่อ
                    updateAttendanceStats(status);

                    // แสดงข้อความแจ้งเตือน
                    showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                } else {
                    // บันทึกไม่สำเร็จ
                    console.error('เช็คชื่อไม่สำเร็จ:', responseData.message);
                    showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                }
            })
            .catch(error => {
                // คืนค่าปุ่มเดิม
                button.innerHTML = originalContent;
                button.disabled = false;

                console.error('เกิดข้อผิดพลาดในการส่งข้อมูล:', error);
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง', 'error');
    }
}

/**
 * ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
 * @param {HTMLElement} studentCard - การ์ดนักเรียน
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} timeChecked - เวลาที่เช็คชื่อ
 * @param {number} attendanceId - ID ของการเช็คชื่อ
 */
function moveToCheckedTab(studentCard, studentId, status, timeChecked, attendanceId) {
    try {
        // ลบการ์ดจากแท็บเดิม
        studentCard.remove();

        // ตรวจสอบว่ามีรายการในแท็บเดิมเหลืออยู่หรือไม่
        const waitingTab = document.getElementById('waitingTab');
        if (!waitingTab) {
            console.error('ไม่พบ element waitingTab');
            return;
        }

        const waitingStudents = waitingTab.querySelectorAll('.student-card');

        if (waitingStudents.length === 0) {
            // ถ้าไม่มีรายการเหลือ ให้แสดงข้อความว่าง
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                <h3>เช็คชื่อครบทุกคนแล้ว!</h3>
                <p>ทุกคนได้รับการเช็คชื่อเรียบร้อยแล้ว</p>
            `;
            waitingTab.innerHTML = '';
            waitingTab.appendChild(emptyState);
        }

        // สร้างการ์ดใหม่ในแท็บ "เช็คชื่อแล้ว"
        const checkedTab = document.getElementById('checkedTab');
        if (!checkedTab) {
            console.error('ไม่พบ element checkedTab');
            return;
        }

        let studentList = checkedTab.querySelector('.student-list');

        // ตรวจสอบว่ามีข้อความว่างหรือไม่
        const emptyState = checkedTab.querySelector('.empty-state');
        if (emptyState) {
            // ถ้ามีข้อความว่าง ให้ลบออกและสร้างรายการใหม่
            emptyState.remove();

            if (!studentList) {
                studentList = document.createElement('div');
                studentList.className = 'student-list';
                checkedTab.appendChild(studentList);
            }
        }

        // สร้าง studentList ถ้ายังไม่มี
        if (!studentList) {
            studentList = document.createElement('div');
            studentList.className = 'student-list';
            checkedTab.appendChild(studentList);
        }

        // หาข้อมูลจากแท็บที่เช็คแล้ว (กรณีแก้ไข)
        const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
        if (existingCard) {
            // อัพเดทการ์ดที่มีอยู่แล้ว
            updateExistingCard(existingCard, status, timeChecked, attendanceId);
            return;
        }

        // สร้างการ์ดใหม่จากข้อมูลใน studentCard
        const newCard = createCheckedCard(studentCard, studentId, status, timeChecked, attendanceId);

        // เพิ่มการ์ดใหม่ลงในรายการ
        studentList.appendChild(newCard);

        // อัพเดตข้อมูลการเช็คชื่อในตัวแปร
        updateAttendanceData(studentId, status);

        // กำหนดว่ามีการเปลี่ยนแปลง
        hasChanges = true;
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการย้ายการ์ดนักเรียน:', error);
        showNotification('เกิดข้อผิดพลาดในการย้ายการ์ด', 'error');
    }
}

/**
 * สร้างการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
 */
function createCheckedCard(originalCard, studentId, status, timeChecked, attendanceId) {
    // ดึงข้อมูลจาก originalCard
    const studentNumber = originalCard.querySelector('.student-number') ? .textContent || '';
    const studentName = originalCard.getAttribute('data-name') || 'ไม่ระบุชื่อ';
    const avatarElement = originalCard.querySelector('.student-avatar');
    const studentAvatar = avatarElement ? avatarElement.outerHTML : '<div class="student-avatar">?</div>';
    const studentCodeElement = originalCard.querySelector('.student-code');
    const studentCode = studentCodeElement ? studentCodeElement.textContent : 'รหัส: -';

    // กำหนดสถานะและไอคอน
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';

    switch (status) {
        case 'present':
            statusClass = 'present';
            statusIcon = 'fa-check-circle';
            statusText = 'มาเรียน';
            break;
        case 'late':
            statusClass = 'late';
            statusIcon = 'fa-clock';
            statusText = 'มาสาย';
            break;
        case 'leave':
            statusClass = 'leave';
            statusIcon = 'fa-clipboard';
            statusText = 'ลา';
            break;
        case 'absent':
            statusClass = 'absent';
            statusIcon = 'fa-times-circle';
            statusText = 'ขาดเรียน';
            break;
    }

    // สร้างการ์ดใหม่
    const newCard = document.createElement('div');
    newCard.className = `student-card ${statusClass}-card`;
    newCard.setAttribute('data-id', studentId);
    newCard.setAttribute('data-name', studentName);
    newCard.setAttribute('data-status', status);
    if (attendanceId) {
        newCard.setAttribute('data-attendance-id', attendanceId);
    }

    // กำหนด HTML ของการ์ด
    newCard.innerHTML = `
        <div class="student-number">${studentNumber}</div>
        
        <div class="student-info" onclick="editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', '')">
            ${studentAvatar}
            
            <div class="student-details">
                <div class="student-name">${studentName}</div>
                <div class="student-code">${studentCode}</div>
            </div>
        </div>
        
        <div class="student-status-info">
            <div class="status-badge ${statusClass}">
                <i class="fas ${statusIcon}"></i> ${statusText}
            </div>
            
            <div class="check-details">
                <div class="check-time">${timeChecked}</div>
                <div class="check-method">ครู</div>
            </div>
        </div>
    `;

    return newCard;
}

/**
 * อัพเดทการ์ดที่มีอยู่แล้ว
 */
function updateExistingCard(card, status, timeChecked, attendanceId) {
    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';

    switch (status) {
        case 'present':
            statusClass = 'present';
            statusIcon = 'fa-check-circle';
            statusText = 'มาเรียน';
            break;
        case 'late':
            statusClass = 'late';
            statusIcon = 'fa-clock';
            statusText = 'มาสาย';
            break;
        case 'leave':
            statusClass = 'leave';
            statusIcon = 'fa-clipboard';
            statusText = 'ลา';
            break;
        case 'absent':
            statusClass = 'absent';
            statusIcon = 'fa-times-circle';
            statusText = 'ขาดเรียน';
            break;
    }

    // อัพเดทคลาสการ์ด
    card.className = `student-card ${statusClass}-card`;
    card.setAttribute('data-status', status);
    if (attendanceId) {
        card.setAttribute('data-attendance-id', attendanceId);
    }

    // อัพเดทสถานะ
    const statusBadge = card.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge ${statusClass}`;
        statusBadge.innerHTML = `<i class="fas ${statusIcon}"></i> ${statusText}`;
    }

    // อัพเดทเวลา
    const checkTime = card.querySelector('.check-time');
    if (checkTime) {
        checkTime.textContent = timeChecked;
    }
}

/**
 * อัพเดทจำนวนนักเรียนในแต่ละแท็บ
 */
function updateStudentCounts() {
    const waitingCount = document.querySelectorAll('#waitingTab .student-card').length;
    const checkedCount = document.querySelectorAll('#checkedTab .student-card').length;

    // อัพเดทจำนวนในปุ่มแท็บ
    const waitingCountElement = document.querySelector('button[data-tab="waiting"] .count');
    const checkedCountElement = document.querySelector('button[data-tab="checked"] .count');

    if (waitingCountElement) {
        waitingCountElement.textContent = waitingCount;
    }

    if (checkedCountElement) {
        checkedCountElement.textContent = checkedCount;
    }
}

/**
 * อัพเดทสถิติการเช็คชื่อ
 */
function updateAttendanceStats(status) {
    // นับจำนวนนักเรียนตามสถานะ
    const presentCount = document.querySelectorAll('#checkedTab .student-card[data-status="present"]').length;
    const lateCount = document.querySelectorAll('#checkedTab .student-card[data-status="late"]').length;
    const leaveCount = document.querySelectorAll('#checkedTab .student-card[data-status="leave"]').length;
    const absentCount = document.querySelectorAll('#checkedTab .student-card[data-status="absent"]').length;
    const notCheckedCount = document.querySelectorAll('#waitingTab .student-card').length;

    // อัพเดทตัวเลขในสรุป
    const presentElement = document.querySelector('.summary-item.present .summary-value');
    const lateElement = document.querySelector('.summary-item.late .summary-value');
    const leaveElement = document.querySelector('.summary-item.leave .summary-value');
    const absentElement = document.querySelector('.summary-item.absent .summary-value');
    const notCheckedElement = document.querySelector('.summary-item.not-checked .summary-value');

    if (presentElement) presentElement.textContent = presentCount;
    if (lateElement) lateElement.textContent = lateCount;
    if (leaveElement) leaveElement.textContent = leaveCount;
    if (absentElement) absentElement.textContent = absentCount;
    if (notCheckedElement) notCheckedElement.textContent = notCheckedCount;
}

/**
 * ดึงข้อความสถานะ
 */
function getStatusText(status) {
    switch (status) {
        case 'present':
            return 'มาเรียน';
        case 'late':
            return 'มาสาย';
        case 'leave':
            return 'ลา';
        case 'absent':
            return 'ขาดเรียน';
        default:
            return 'ไม่ระบุ';
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 */
function showNotification(message, type = 'info') {
    try {
        // สร้างแถบแจ้งเตือน
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;

        // กำหนดไอคอนตามประเภท
        let icon = '';
        switch (type) {
            case 'success':
                icon = 'check-circle';
                break;
            case 'warning':
                icon = 'exclamation-triangle';
                break;
            case 'error':
                icon = 'exclamation-circle';
                break;
            case 'info':
            default:
                icon = 'info-circle';
                break;
        }

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;

        // เพิ่มไปยัง body
        document.body.appendChild(notification);

        // กำหนดการปิดเมื่อคลิก
        const closeButton = notification.querySelector('.notification-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                notification.remove();
            });
        }

        // กำหนดการปิดอัตโนมัติ
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.remove();
            }
        }, 5000);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงข้อความแจ้งเตือน:', error);
    }
}

/**
 * แสดง Modal รายละเอียดการเช็คชื่อ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function showDetailAttendanceModal(studentId, studentName) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }

    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }

    // ระบุว่าเป็นการเพิ่มใหม่ ไม่ใช่การแก้ไข
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '0';
    }

    // รีเซ็ตค่า attendance_id
    const attendanceIdInput = document.getElementById('attendanceIdDetail');
    if (attendanceIdInput) {
        attendanceIdInput.value = '';
    }

    // รีเซ็ตค่าตัวเลือกเป็น "มาเรียน"
    const presentOption = document.querySelector('input[name="attendanceStatus"][value="present"]');
    if (presentOption) {
        presentOption.checked = true;
    }

    // รีเซ็ตค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = '';
    }

    // รีเซ็ตค่าหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
    const retroactiveNoteInput = document.getElementById('retroactiveNote');
    if (retroactiveNoteInput) {
        retroactiveNoteInput.value = '';
    }

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        remarksContainer.style.display = 'none';
    }

    // แสดง Modal
    showModal('attendanceDetailModal');
}

/**
 * แก้ไขการเช็คชื่อ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 * @param {string} status - สถานะปัจจุบัน
 * @param {string} remarks - หมายเหตุ
 */
function editAttendance(studentId, studentName, status, remarks) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }

    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }

    // ระบุว่าเป็นการแก้ไข
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '1';
    }

    // ดึงและกำหนดค่า attendance_id
    const studentCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
    if (studentCard) {
        const attendanceId = studentCard.getAttribute('data-attendance-id');
        const attendanceIdInput = document.getElementById('attendanceIdDetail');
        if (attendanceIdInput && attendanceId) {
            attendanceIdInput.value = attendanceId;
        }
    }

    // เลือกสถานะปัจจุบัน
    const statusOption = document.querySelector(`input[name="attendanceStatus"][value="${status}"]`);
    if (statusOption) {
        statusOption.checked = true;
    }

    // ใส่ค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = remarks || '';
    }

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        if (status === 'late' || status === 'leave') {
            remarksContainer.style.display = 'block';
        } else {
            remarksContainer.style.display = 'none';
        }
    }

    // แสดง Modal
    showModal('attendanceDetailModal');
}

/**
 * แสดง Modal
 * @param {string} modalId - ID ของ Modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนพื้นหลัง
    }
}

/**
 * ปิด Modal
 * @param {string} modalId - ID ของ Modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // คืนค่าการเลื่อน
    }
}

/**
 * บันทึกข้อมูลการเช็คชื่อในตัวแปร
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ
 * @param {number|null} attendanceId - รหัสการเช็คชื่อ (กรณีแก้ไข)
 */
function updateAttendanceData(studentId, status, remarks = '', attendanceId = null) {
    // แปลงเป็นตัวเลข
    studentId = parseInt(studentId);

    // ตรวจสอบว่ามีข้อมูลนักเรียนในตัวแปรแล้วหรือไม่
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);

    if (studentIndex !== -1) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        attendanceData.students[studentIndex].remarks = remarks;

        // อัพเดท attendance_id ถ้ามี
        if (attendanceId) {
            attendanceData.students[studentIndex].attendance_id = parseInt(attendanceId);
        }
    } else {
        // เพิ่มข้อมูลใหม่
        const studentData = {
            student_id: studentId,
            status: status,
            remarks: remarks
        };

        // เพิ่ม attendance_id ถ้ามี
        if (attendanceId) {
            studentData.attendance_id = parseInt(attendanceId);
        }

        attendanceData.students.push(studentData);
    }
}

/**
 * ยืนยันการเช็คชื่อจาก Modal รายละเอียด
 */
function confirmDetailAttendance() {
    try {
        // ดึงข้อมูลจาก Modal
        const studentId = document.getElementById('studentIdDetail').value;
        const isEditMode = document.getElementById('isEditMode').value === '1';
        const attendanceId = document.getElementById('attendanceIdDetail') ? .value || null;

        // ดึงสถานะที่เลือก
        const statusElement = document.querySelector('input[name="attendanceStatus"]:checked');
        if (!statusElement) {
            showNotification('กรุณาเลือกสถานะการเช็คชื่อ', 'warning');
            return;
        }
        const status = statusElement.value;

        // ดึงหมายเหตุ
        let remarks = '';

        if (status === 'late' || status === 'leave') {
            const remarksInput = document.getElementById('attendanceRemarks');
            if (remarksInput) {
                remarks = remarksInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (remarks === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการมาสาย/ลา', 'warning');
                    return;
                }
            }
        }

        // ถ้าเป็นการเช็คชื่อย้อนหลัง ให้ดึงหมายเหตุเพิ่มเติม
        if (isRetroactive) {
            const retroactiveNoteInput = document.getElementById('retroactiveNote');
            if (retroactiveNoteInput) {
                const retroactiveNote = retroactiveNoteInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (retroactiveNote === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }

                // เพิ่มหมายเหตุย้อนหลัง
                remarks = remarks ? `${remarks} (${retroactiveNote})` : retroactiveNote;
            }
        }

        // สร้างข้อมูลที่จะส่ง
        const data = {
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            is_retroactive: isRetroactive,
            remarks: remarks
        };

        console.log('ส่งข้อมูลการเช็คชื่อละเอียด:', data);

        // แสดงสถานะกำลังโหลด
        const saveButton = document.querySelector('#attendanceDetailModal .btn.primary');
        if (saveButton) {
            const originalText = saveButton.textContent;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            saveButton.disabled = true;

            // ส่งข้อมูลไปบันทึก AJAX
            fetch('api/ajax_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server response was not OK: ' + response.status);
                    }
                    return response.json();
                })
                .then(responseData => {
                    // คืนค่าปุ่มเดิม
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;

                    if (responseData.success) {
                        console.log('บันทึกการเช็คชื่อละเอียดสำเร็จ:', responseData);

                        // ปิด Modal
                        closeModal('attendanceDetailModal');

                        if (isEditMode) {
                            // กรณีแก้ไข: อัพเดทการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
                            updateExistingCard(
                                document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`),
                                status,
                                responseData.student ? .time_checked || new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }),
                                responseData.attendance_id
                            );
                        } else {
                            // กรณีเพิ่มใหม่: ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
                            const studentCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);

                            if (studentCard) {
                                moveToCheckedTab(
                                    studentCard,
                                    studentId,
                                    status,
                                    responseData.student ? .time_checked || new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }),
                                    responseData.attendance_id
                                );
                            }
                        }

                        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
                        updateStudentCounts();

                        // อัพเดทสถิติการเช็คชื่อ
                        updateAttendanceStats();

                        // แสดงข้อความแจ้งเตือน
                        showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                    } else {
                        // แสดงข้อความเมื่อมีข้อผิดพลาด
                        console.error('บันทึกการเช็คชื่อละเอียดไม่สำเร็จ:', responseData.message);
                        showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                    }
                })
                .catch(error => {
                    // คืนค่าปุ่มเดิม
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;

                    console.error('Error:', error);
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                });
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการยืนยันการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ: ' + error.message, 'error');
    }
}

/**
 * เช็คชื่อทั้งหมด
 */
function confirmMarkAll() {
    try {
        // ดึงสถานะที่เลือก
        const statusElement = document.querySelector('input[name="markAllStatus"]:checked');
        if (!statusElement) {
            showNotification('กรุณาเลือกสถานะการเช็คชื่อ', 'warning');
            return;
        }
        const status = statusElement.value;

        // ดึงหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
        let remarks = '';

        if (isRetroactive) {
            const remarksInput = document.getElementById('markAllRetroactiveNote');
            if (remarksInput) {
                remarks = remarksInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (remarks === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
            }
        }

        // คำแนะนำเพิ่มเติมสำหรับสถานะมาสายและลา
        if ((status === 'late' || status === 'leave') && !remarks && !isRetroactive) {
            if (!confirm(`คุณกำลังจะเช็คชื่อนักเรียนทั้งหมดเป็น "${getStatusText(status)}" โดยไม่มีการระบุหมายเหตุ ต้องการดำเนินการต่อหรือไม่?`)) {
                return;
            }
        }

        // ดึงทุกการ์ดนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const studentCards = document.querySelectorAll('#waitingTab .student-card');

        if (studentCards.length === 0) {
            closeModal('markAllModal');
            showNotification('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
            return;
        }

        // เช็คชื่อทุกคน
        studentCards.forEach(card => {
            const studentId = card.getAttribute('data-id');

            // บันทึกข้อมูลการเช็คชื่อในตัวแปร
            updateAttendanceData(studentId, status, remarks);

            // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
            moveToCheckedTab(card, studentId, status, '', null);
        });

        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
        updateStudentCounts();

        // อัพเดทสถิติการเช็คชื่อ
        updateAttendanceStats();

        // ปิด Modal
        closeModal('markAllModal');

        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;

        // แสดงข้อความแจ้งเตือน
        showNotification(`เช็คชื่อนักเรียนทั้งหมด ${studentCards.length} คน เป็น "${getStatusText(status)}" เรียบร้อย`, 'success');
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด: ' + error.message, 'error');
    }
}

/**
 * ฟังก์ชันที่ขาดหายไป - สำหรับความเข้ากันได้กับ main.js เดิม
 */
function updateAttendanceCounters() {
    updateStudentCounts();
}

// เริ่มต้นเมื่อโหลดหน้าเพจ
document.addEventListener('DOMContentLoaded', function() {
    console.log('ระบบเช็คชื่อพร้อมใช้งาน');

    // จัดการระบบแท็บ
    setupTabSystem();

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    setupRemarkField();

    // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
    updateStudentCounts();
});

/**
 * จัดการระบบแท็บ
 */
function setupTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-button');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ลบคลาส active จากทุกปุ่มและแท็บ
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });

            // เพิ่มคลาส active ให้ปุ่มที่คลิกและแท็บที่เกี่ยวข้อง
            this.classList.add('active');

            const tabId = this.getAttribute('data-tab');
            const tabPane = document.getElementById(tabId + 'Tab');
            if (tabPane) {
                tabPane.classList.add('active');
            }
        });
    });
}

/**
 * แสดง/ซ่อนช่องหมายเหตุตามสถานะ
 */
function setupRemarkField() {
    const statusInputs = document.querySelectorAll('input[name="attendanceStatus"]');
    const remarksContainer = document.getElementById('remarksContainer');

    if (statusInputs.length > 0 && remarksContainer) {
        statusInputs.forEach(input => {
            input.addEventListener('change', function() {
                const status = this.value;

                // แสดงช่องหมายเหตุเฉพาะเมื่อเลือกสถานะมาสายหรือลา
                if (status === 'late' || status === 'leave') {
                    remarksContainer.style.display = 'block';
                } else {
                    remarksContainer.style.display = 'none';
                }
            });
        });
    }
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.toLowerCase();

    // ค้นหาในทั้งสองแท็บ
    searchInTab('waitingTab', searchTerm);
    searchInTab('checkedTab', searchTerm);
}

/**
 * ค้นหาในแท็บที่กำหนด
 */
function searchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;

    const studentCards = tab.querySelectorAll('.student-card');

    studentCards.forEach(card => {
        const name = card.getAttribute('data-name') ? .toLowerCase() || '';

        if (name.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
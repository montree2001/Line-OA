/**
<<<<<<< HEAD
 * check_ajax.js - JavaScript สำหรับระบบเช็คชื่อแบบ AJAX
 * 
 * ฟังก์ชันหลัก:
 * - markAttendance: เช็คชื่อนักเรียนและบันทึกทันที
 * - moveToCheckedTab: ย้ายการ์ดนักเรียนไปแท็บเช็คชื่อแล้ว
 * - updateStudentCounts: อัพเดทจำนวนนักเรียนแต่ละแท็บ
 * - updateAttendanceStats: อัพเดทสถิติการเช็คชื่อ
 */

// ตัวแปรสถานะการเปลี่ยนแปลง
let hasChanges = false;

=======
 * check_ajax.js - AJAX functions for attendance checking
 * Handles real-time attendance updates without page reloads
 */

>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
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

        console.log('ส่งข้อมูล:', data);

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
                console.log('ได้รับข้อมูลตอบกลับ:', responseData);

                // คืนค่าปุ่มเดิม
                button.innerHTML = originalContent;
                button.disabled = false;

                if (responseData.success) {
                    // บันทึกสำเร็จ

                    // สร้างสถานะเช็คชื่อและเวลา
                    const timeChecked = responseData.student.time_checked || new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    const attendanceId = responseData.attendance_id;

                    // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
                    moveToCheckedTab(studentCard, studentId, status, timeChecked, attendanceId);

                    // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
                    updateStudentCounts();

                    // อัพเดทสถิติการเช็คชื่อ
                    updateAttendanceStats(status);

                    // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
                    hasChanges = true;

                    // แสดงข้อความแจ้งเตือน
                    showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                } else {
                    // บันทึกไม่สำเร็จ
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
<<<<<<< HEAD
 * @param {string} timeChecked - เวลาที่เช็คชื่อ
 * @param {number} attendanceId - ID ของการเช็คชื่อ
=======
 * @param {string} timeChecked - เวลาที่เช็ค
 * @param {number} attendanceId - ID การเช็คชื่อ
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
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
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการย้ายการ์ดนักเรียน:', error);
        showNotification('เกิดข้อผิดพลาดในการย้ายการ์ดนักเรียน', 'error');
    }
}

/**
 * สร้างการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
<<<<<<< HEAD
 * @param {HTMLElement} originalCard - การ์ดนักเรียนต้นฉบับ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} timeChecked - เวลาที่เช็คชื่อ
 * @param {number} attendanceId - ID ของการเช็คชื่อ
=======
 * @param {HTMLElement} originalCard - การ์ดต้นฉบับ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} timeChecked - เวลาที่เช็ค
 * @param {number} attendanceId - ID การเช็คชื่อ
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
 * @returns {HTMLElement} - การ์ดใหม่
 */
function createCheckedCard(originalCard, studentId, status, timeChecked, attendanceId) {
    // ดึงข้อมูลจาก originalCard
    const studentName = originalCard.getAttribute('data-name') || '';
<<<<<<< HEAD
    const studentNumber = originalCard.querySelector('.student-number') ? .textContent || '';
    const studentAvatar = originalCard.querySelector('.student-avatar') ? .outerHTML || '';
    const studentCode = originalCard.querySelector('.student-code') ? .textContent || '';

    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '',
        statusIcon = '',
        statusText = '';
=======
    const studentNumber = originalCard.querySelector('.student-number')?.textContent || '';
    const avatarElement = originalCard.querySelector('.student-avatar');
    const studentAvatar = avatarElement ? avatarElement.outerHTML : '<div class="student-avatar">?</div>';
    const studentCodeElement = originalCard.querySelector('.student-code');
    const studentCode = studentCodeElement ? studentCodeElement.textContent : 'รหัส: -';

    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998

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
        
        <div class="student-info" onclick="if (typeof editAttendance === 'function') { editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', ''); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">
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
<<<<<<< HEAD
 * @param {HTMLElement} card - การ์ดนักเรียนที่ต้องการอัพเดท
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} timeChecked - เวลาที่เช็คชื่อ
 * @param {number} attendanceId - ID ของการเช็คชื่อ
 */
function updateExistingCard(card, status, timeChecked, attendanceId) {
    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '',
        statusIcon = '',
        statusText = '';
=======
 * @param {HTMLElement} card - การ์ดที่ต้องการอัพเดท
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} timeChecked - เวลาที่เช็ค
 * @param {number} attendanceId - ID การเช็คชื่อ
 */
function updateExistingCard(card, status, timeChecked, attendanceId) {
    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998

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
<<<<<<< HEAD
 * @param {string} status - สถานะที่เปลี่ยน (optional)
=======
 * @param {string} status - สถานะที่เปลี่ยน
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
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
 * @param {string} status - สถานะการเช็คชื่อ
 * @returns {string} - ข้อความสถานะ
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
 * @param {string} message - ข้อความ
<<<<<<< HEAD
 * @param {string} type - ประเภท (success, info, warning, error)
=======
 * @param {string} type - ประเภท (success, warning, error, info)
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
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
<<<<<<< HEAD
 * เปิด/ปิด Modal
 * @param {string} modalId - ID ของ Modal
=======
 * ฟังก์ชันที่ขาดหายไป - สำหรับความเข้ากันได้กับ main.js เดิม
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
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

// เริ่มทำงานเมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
<<<<<<< HEAD
    console.log('ระบบเช็คชื่อแบบ AJAX พร้อมใช้งาน');

=======
    console.log("ระบบเช็คชื่อแบบ AJAX พร้อมใช้งาน");
    
>>>>>>> bce6faef8d7b47a466477e6aa87b4247749cf998
    // จัดการกับการเปลี่ยนแท็บ
    setupTabSystem();

    // อัพเดทจำนวนนักเรียนเริ่มต้น
    updateStudentCounts();
});
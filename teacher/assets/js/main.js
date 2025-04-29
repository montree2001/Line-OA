/**
 * เช็คชื่อนักเรียน
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent/late/leave)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    try {
        // ดึงข้อมูลการ์ดนักเรียน
        const studentCard = button.closest('.student-card');

        if (!studentCard) {
            console.error('ไม่พบข้อมูล .student-card สำหรับปุ่มนี้:', button);
            showNotification('เกิดข้อผิดพลาด: กรุณารีเฟรชหน้าและลองใหม่อีกครั้ง', 'error');
            return;
        }

        // ค้นหาปุ่มสถานะทั้งหมด
        const presentButton = studentCard.querySelector('.action-button.present');
        const absentButton = studentCard.querySelector('.action-button.absent');

        if (!presentButton || !absentButton) {
            console.error('ไม่พบปุ่มสถานะในการ์ดนักเรียน');
            showNotification('เกิดข้อผิดพลาด: กรุณารีเฟรชหน้าและลองใหม่อีกครั้ง', 'error');
            return;
        }

        // ลบคลาส active จากปุ่มทั้งหมด
        presentButton.classList.remove('active');
        absentButton.classList.remove('active');

        // เพิ่มคลาส active ให้ปุ่มที่กด
        button.classList.add('active');

        // อัพเดทจำนวนการเข้าแถว
        if (typeof updateStudentCounts === 'function') {
            updateStudentCounts();
        }

        // บันทึกข้อมูลการเช็คชื่อ (หากใช้ JavaScript แทนการส่งฟอร์ม)
        // updateAttendanceData(studentId, status, ''); - ไม่ใช้แล้ว

        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        if (typeof hasChanges !== 'undefined') {
            hasChanges = true;
        }

        // ย้ายการ์ดไปยังแท็บที่เช็คชื่อแล้ว
        if (typeof moveStudentToCheckedTab === 'function') {
            moveStudentToCheckedTab(studentCard, studentId, status);
        }

        // บันทึกลงเซิร์ฟเวอร์ (แบบ AJAX)
        if (typeof saveAttendanceToServer === 'function') {
            saveAttendanceToServer(studentId, status);
        }

        // แสดงข้อความแจ้งเตือน
        if (typeof showNotification === 'function') {
            showNotification(`เช็คชื่อนักเรียนเป็น "${getStatusText(status)}" เรียบร้อย`, 'success');
        }

        // Log สำหรับดีบัก
        console.log(`นักเรียน ID: ${studentId} สถานะ: ${status}`);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
        if (typeof showNotification === 'function') {
            showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง', 'error');
        }
    }
}

/**
 * บันทึกการเช็คชื่อไปยังเซิร์ฟเวอร์ (ใช้ AJAX)
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 */
function saveAttendanceToServer(studentId, status) {
    try {
        // ข้อมูลที่จะส่ง
        const data = {
            action: 'mark_attendance',
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            teacher_id: teacherId,
            is_retroactive: isRetroactive
        };

        // ส่งข้อมูลไปบันทึกแบบ AJAX
        fetch('api/ajax_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('การบันทึกข้อมูลล้มเหลว');
                }
                return response.json();
            })
            .then(data => {
                console.log('บันทึกข้อมูลสำเร็จ:', data);

                // อัพเดทสถิติการเช็คชื่อ (ถ้ามีฟังก์ชัน)
                if (typeof updateAttendanceStats === 'function') {
                    updateAttendanceStats(status);
                }
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาดในการบันทึกข้อมูล:', error);
            });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการส่งข้อมูล:', error);
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
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

// เพิ่มฟังก์ชัน updateStudentCounts สำหรับความเข้ากันได้กับโค้ดที่เคยใช้ updateAttendanceCounters
function updateStudentCounts() {
    // ถ้ามีการนิยามฟังก์ชัน updateStudentCounts ในที่อื่นแล้ว จะเรียกใช้ฟังก์ชันนั้น
    if (window.updateStudentCounts && this !== window) {
        window.updateStudentCounts();
        return;
    }

    // หากไม่มี จะนับจำนวนนักเรียนในแต่ละแท็บและอัพเดท UI
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

// สร้างฟังก์ชัน alias เพื่อให้โค้ดเดิมทำงานได้
function updateAttendanceCounters() {
    updateStudentCounts();
}
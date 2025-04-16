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
        updateAttendanceCounters();
        
        // บันทึกข้อมูลการเช็คชื่อ (หากใช้ JavaScript แทนการส่งฟอร์ม)
        updateAttendanceData(studentId, status, '');
        
        // แสดงตัวบอกการเช็คชื่อที่ยังไม่ได้บันทึก
        showSaveIndicator();
        
        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;
        
        // ย้ายการ์ดไปยังแท็บที่เช็คชื่อแล้ว
        moveStudentToCheckedTab(studentCard, studentId, status);
        
        // บันทึกลงเซิร์ฟเวอร์ (แบบ AJAX)
        saveAttendanceToServer(studentId, status);
        
        // แสดงข้อความแจ้งเตือน
        showNotification(`เช็คชื่อนักเรียนเป็น "${getStatusText(status)}" เรียบร้อย`, 'success');
        
        // Log สำหรับดีบัก
        console.log(`นักเรียน ID: ${studentId} สถานะ: ${status}`);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง', 'error');
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
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('การบันทึกข้อมูลล้มเหลว');
            }
            return response.text();
        })
        .then(text => {
            console.log('บันทึกข้อมูลสำเร็จ');
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
            case 'success': icon = 'check-circle'; break;
            case 'warning': icon = 'exclamation-triangle'; break;
            case 'error': icon = 'exclamation-circle'; break;
            case 'info': default: icon = 'info-circle'; break;
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
 * แสดงตัวบอกการเช็คชื่อที่ยังไม่ได้บันทึก
 */
function showSaveIndicator() {
    try {
        // ตรวจสอบว่ามีตัวบอกอยู่แล้วหรือไม่
        let indicator = document.querySelector('.save-indicator');
        
        // ถ้ายังไม่มี ให้สร้างใหม่
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'save-indicator';
            indicator.innerHTML = `<i class="fas fa-exclamation-circle"></i> มีข้อมูลที่ยังไม่ได้บันทึก`;
            document.body.appendChild(indicator);
            
            // เพิ่มปุ่มบันทึกใน indicator
            const saveButton = document.createElement('button');
            saveButton.className = 'btn primary btn-sm';
            saveButton.innerHTML = 'บันทึกทั้งหมด';
            saveButton.style.marginLeft = '10px';
            saveButton.onclick = function() {
                saveAttendance();
            };
            indicator.appendChild(saveButton);
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงตัวบอกการบันทึก:', error);
    }
}

/**
 * ดึงข้อความสถานะ
 * @param {string} status - สถานะการเช็คชื่อ
 * @returns {string} - ข้อความสถานะ
 */
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'late': return 'มาสาย';
        case 'leave': return 'ลา';
        case 'absent': return 'ขาดเรียน';
        default: return 'ไม่ระบุ';
    }
}
/**
 * teacher-check-attendance.js - สคริปต์สำหรับหน้าเช็คชื่อนักเรียน
 * 
 * ฟังก์ชันหลัก:
 * - จัดการการเช็คชื่อนักเรียน (มา/ขาด/สาย/ลา)
 * - อัพเดทสถิติการเช็คชื่อ
 * - บันทึกข้อมูลการเช็คชื่อ
 * - สร้างรหัส PIN สำหรับการเช็คชื่อ
 */

// ตัวแปร Global สำหรับเก็บสถานะการเช็คชื่อ
let attendanceData = {
    class_id: currentClassId,
    date: checkDate,
    students: []
};

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้าเช็คชื่อ
    initCheckAttendance();
});

/**
 * เริ่มต้นการทำงานของหน้าเช็คชื่อ
 */
function initCheckAttendance() {
    // อัพเดทจำนวนการเข้าแถว
    updateAttendanceCounters();
    
    // ติดตั้งเหตุการณ์ค้นหา
    initSearchFunction();
    
    // ตั้งค่าแท็บเริ่มต้น
    showTab('unchecked');
    
    // จัดการเหตุการณ์คลิกที่รายการนักเรียน
    setupStudentItemEvents();
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์ห้องเรียน
    window.location.href = 'check-attendance.php?class_id=' + classId + '&date=' + checkDate;
}

/**
 * เปลี่ยนวันที่
 * @param {string} date - วันที่ต้องการเช็คชื่อ
 */
function changeDate(date) {
    // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์วันที่
    window.location.href = 'check-attendance.php?class_id=' + currentClassId + '&date=' + date;
}

/**
 * สร้างรหัส PIN สำหรับการเช็คชื่อ
 */
function showPinModal() {
    // แสดง Modal สร้างรหัส PIN
    const modal = document.getElementById('pin-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    }
}

/**
 * ปิด Modal ด้วย ID
 * @param {string} modalId - ID ของ Modal ที่ต้องการปิด
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generateNewPin() {
    // แสดงข้อความกำลังดำเนินการ
    showAlert('กำลังสร้างรหัส PIN...', 'info');
    
    // ส่งคำขอสร้าง PIN ใหม่ไปยัง API
    fetch('api/create_pin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: currentClassId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัพเดท PIN ที่แสดงใน Modal
            const pinDisplay = document.getElementById('pin-display');
            if (pinDisplay) {
                pinDisplay.textContent = data.pin_code;
            }
            
            // อัพเดทเวลาที่เหลือ
            const pinExpireTime = document.getElementById('pin-expire-time');
            if (pinExpireTime) {
                pinExpireTime.textContent = data.expire_minutes;
            }
            
            // แสดงข้อความแจ้งเตือนสำเร็จ
            showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
        } else {
            // แสดงข้อความเมื่อมีข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการสร้าง PIN', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

/**
 * สแกน QR Code
 */
function scanQRCode() {
    // แสดง Modal สแกน QR Code
    const modal = document.getElementById('qr-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // สำหรับตัวอย่าง (รออัพเดทในเวอร์ชันถัดไป)
        showAlert('ระบบกำลังเรียกใช้กล้อง กรุณารอสักครู่...', 'info');
    }
}

/**
 * แสดง Modal ยืนยันเช็คชื่อทั้งหมด
 */
function showMarkAllModal() {
    // ตรวจสอบว่ามีนักเรียนที่ยังไม่ได้เช็คชื่อหรือไม่
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    
    if (uncheckedCount === 0) {
        showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }
    
    // แสดง Modal ยืนยันเช็คชื่อทั้งหมด
    const modal = document.getElementById('mark-all-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดตามสถานะที่เลือก (แสดงการเลือกค้างไว้โดยไม่ย้ายรายการ)
 */
function markAllWithStatus() {
    // ดึงทุกรายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
    const uncheckedTab = document.getElementById('unchecked-tab');
    const studentItems = uncheckedTab.querySelectorAll('.student-item');
    
    // ถ้าไม่มีนักเรียนที่ยังไม่ได้เช็คชื่อ
    if (studentItems.length === 0) {
        closeModal('mark-all-modal');
        showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }
    
    // ดึงสถานะที่เลือก
    const selectedStatus = document.querySelector('input[name="mark-all-status"]:checked').value;
    
    // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
    let remarks = '';
    if (isRetroactive) {
        const remarksInput = document.getElementById('retroactive-note');
        if (remarksInput) {
            remarks = remarksInput.value.trim();
            
            // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
            if (remarks === '') {
                showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                return;
            }
        }
    }
    
    // ล้างการเลือกทั้งหมดก่อน
    studentItems.forEach(item => {
        const actionButtons = item.querySelectorAll('.action-button');
        actionButtons.forEach(btn => btn.classList.remove('active'));
    });
    
    // ตั้งค่าการเลือกสำหรับทุกรายการ
    studentItems.forEach(item => {
        const studentId = item.getAttribute('data-id');
        const button = item.querySelector(`.action-button.${selectedStatus}`);
        
        // ถ้ามีปุ่มที่ตรงกับสถานะ ให้ทำการ active
        if (button) {
            button.classList.add('active');
        }
        
        // เพิ่มหรืออัพเดทข้อมูลในตัวแปร attendanceData
        const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);
        
        if (studentIndex >= 0) {
            // อัพเดทข้อมูลเดิม
            attendanceData.students[studentIndex].status = selectedStatus;
            attendanceData.students[studentIndex].remarks = remarks;
        } else {
            // เพิ่มข้อมูลใหม่
            attendanceData.students.push({
                student_id: studentId,
                status: selectedStatus,
                remarks: remarks
            });
        }
        
        // เพิ่มข้อมูลสถานะให้กับรายการนักเรียน (เพื่อใช้อ้างอิงต่อไป)
        item.setAttribute('data-status', selectedStatus);
        
        // เพิ่มแถบสีเพื่อแสดงสถานะที่เลือก (ไม่มีในโค้ดเดิม)
        item.classList.remove('selected-present', 'selected-late', 'selected-leave', 'selected-absent');
        item.classList.add(`selected-${selectedStatus}`);
    });
    
    // แสดงข้อความสำเร็จ
    let statusText = '';
    switch (selectedStatus) {
        case 'present': statusText = 'มาเรียน'; break;
        case 'late': statusText = 'มาสาย'; break;
        case 'leave': statusText = 'ลา'; break;
        case 'absent': statusText = 'ขาด'; break;
    }
    
    showAlert(`เลือกสถานะนักเรียน ${studentItems.length} คน เป็น "${statusText}" แล้ว (ข้อมูลยังไม่ถูกบันทึก)`, 'info');
    
    // ปิด Modal
    closeModal('mark-all-modal');
}

/**
 * บันทึกการเช็คชื่อหลายคนพร้อมกัน
 * @param {Array} students - รายการนักเรียนที่จะเช็คชื่อ
 */
function saveMultipleAttendance(students) {
    // แสดงการโหลด
    showAlert('กำลังบันทึกการเช็คชื่อ...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/save_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: currentClassId,
            date: checkDate,
            teacher_id: currentTeacherId,
            students: students,
            is_retroactive: isRetroactive,
            check_method: 'Manual'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดงข้อความสำเร็จ
            showAlert(`เช็คชื่อนักเรียน ${students.length} คน สำเร็จ`, 'success');
            
            // รีโหลดหน้าเพื่ออัพเดทข้อมูล
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // แสดงข้อความเมื่อมีข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}


/**
 * เช็คชื่อนักเรียนและเปลี่ยนสถานะให้ถูกต้อง
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    console.log("markAttendance เริ่มทำงาน", { button, status, studentId });
    
    try {
        // ดึงรายการนักเรียน
        const studentItem = button.closest('.student-item');
        if (!studentItem) {
            console.error('ไม่พบรายการนักเรียน');
            return;
        }
        
        // ดึงข้อมูลชื่อนักเรียน
        const studentName = studentItem.querySelector('.student-name');
        if (!studentName) {
            console.error('ไม่พบชื่อนักเรียน');
            return;
        }
        
        // ดึงปุ่มทั้งหมด
        const presentButton = studentItem.querySelector('.action-button.present');
        const absentButton = studentItem.querySelector('.action-button.absent');
        
        if (!presentButton || !absentButton) {
            console.error('ไม่พบปุ่มเช็คชื่อ');
            return;
        }
        
        // รีเซ็ตสถานะของปุ่มทั้งหมด
        presentButton.classList.remove('active');
        presentButton.style.backgroundColor = '';
        presentButton.style.color = '';
        
        absentButton.classList.remove('active');
        absentButton.style.backgroundColor = '';
        absentButton.style.color = '';
        
        // ตั้งค่าปุ่มที่เลือก
        if (status === 'present') {
            presentButton.classList.add('active');
            presentButton.style.backgroundColor = '#4caf50';
            presentButton.style.color = 'white';
        } else if (status === 'absent') {
            absentButton.classList.add('active');
            absentButton.style.backgroundColor = '#f44336';
            absentButton.style.color = 'white';
        }
        
        // เก็บสถานะในข้อมูล
        studentItem.setAttribute('data-status', status);
        
        // กำหนดข้อความสถานะ
        const statusText = (status === 'present') ? 'มาเรียน' : 'ขาด';
        const statusColor = (status === 'present') ? '#4caf50' : '#f44336';
        
        // หาส่วนข้อความสถานะ
        const statusElement = studentItem.querySelector('.student-status');
        if (statusElement) {
            statusElement.innerHTML = `สถานะ: ${statusText}`;
            statusElement.style.color = statusColor;
        } else {
            // ถ้าไม่พบให้สร้างใหม่
            const newStatusElement = document.createElement('div');
            newStatusElement.className = 'student-status';
            newStatusElement.innerHTML = `สถานะ: ${statusText}`;
            newStatusElement.style.color = statusColor;
            newStatusElement.style.fontSize = '14px';
            newStatusElement.style.marginTop = '5px';
            studentName.appendChild(newStatusElement);
        }
        
        // เปลี่ยนสีพื้นหลังของรายการนักเรียน
        if (status === 'present') {
            studentItem.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
        } else {
            studentItem.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
        }
        
        // บันทึกข้อมูลในตัวแปร global
        saveAttendanceData(studentId, status, '');
        
        // แสดงข้อความแจ้งเตือนการเปลี่ยนสถานะ
        console.log(`เปลี่ยนสถานะ: นักเรียน ID ${studentId} เป็น ${statusText}`);
        
        // แสดงตัวบ่งชี้การบันทึก
        showSaveIndicator();
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
    }
}
/**
 * เปลี่ยนสถานะปุ่มและแสดงข้อความสถานะ
 */
function changeButtonStatus(studentItem, activeButton, status) {
    try {
        // ลบคลาส active จากทุกปุ่ม
        const allButtons = studentItem.querySelectorAll('.action-button');
        allButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.style.backgroundColor = '';
            btn.style.color = '';
        });
        
        // เพิ่มคลาส active ให้กับปุ่มที่เลือก
        activeButton.classList.add('active');
        
        // กำหนดสีตามสถานะ
        if (status === 'present') {
            activeButton.style.backgroundColor = '#4caf50';
            activeButton.style.color = 'white';
            studentItem.style.borderLeft = '3px solid #4caf50';
            studentItem.style.backgroundColor = '#e8f5e9';
        } else if (status === 'absent') {
            activeButton.style.backgroundColor = '#f44336';
            activeButton.style.color = 'white';
            studentItem.style.borderLeft = '3px solid #f44336';
            studentItem.style.backgroundColor = '#ffebee';
        } else if (status === 'late') {
            activeButton.style.backgroundColor = '#ff9800';
            activeButton.style.color = 'white';
            studentItem.style.borderLeft = '3px solid #ff9800';
            studentItem.style.backgroundColor = '#fff3e0';
        } else if (status === 'leave') {
            activeButton.style.backgroundColor = '#9c27b0';
            activeButton.style.color = 'white';
            studentItem.style.borderLeft = '3px solid #9c27b0';
            studentItem.style.backgroundColor = '#f3e5f5';
        }
        
        // เก็บสถานะในข้อมูล
        studentItem.setAttribute('data-status', status);
        
        // แสดงข้อความสถานะ
        const statusText = getStatusText(status);
        const studentName = studentItem.querySelector('.student-name');
        
        // ลบข้อความสถานะเดิม
        const oldStatus = studentItem.querySelector('.status-text');
        if (oldStatus) {
            oldStatus.remove();
        }
        
        // สร้างข้อความสถานะใหม่
        const statusElement = document.createElement('span');
        statusElement.className = 'status-text';
        statusElement.innerHTML = `<br>สถานะ: <b>${statusText}</b>`;
        statusElement.style.fontSize = '12px';
        statusElement.style.display = 'block';
        statusElement.style.marginTop = '4px';
        
        // กำหนดสีตามสถานะ
        if (status === 'present') {
            statusElement.style.color = '#4caf50';
        } else if (status === 'absent') {
            statusElement.style.color = '#f44336';
        } else if (status === 'late') {
            statusElement.style.color = '#ff9800';
        } else if (status === 'leave') {
            statusElement.style.color = '#9c27b0';
        }
        
        studentName.appendChild(statusElement);
        
        console.log('เปลี่ยนสถานะเรียบร้อย:', status);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเปลี่ยนสถานะปุ่ม:', error);
    }
}

/**
 * ดึงข้อความสถานะ
 */
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'absent': return 'ขาด';
        case 'late': return 'มาสาย';
        case 'leave': return 'ลา';
        default: return 'ไม่ระบุ';
    }
}

/**
 * บันทึกข้อมูลในตัวแปร attendanceData
 */
function saveAttendanceData(studentId, status, remarks) {
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);
    
    if (studentIndex >= 0) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        attendanceData.students[studentIndex].remarks = remarks;
    } else {
        // เพิ่มข้อมูลใหม่
        attendanceData.students.push({
            student_id: studentId,
            status: status,
            remarks: remarks
        });
    }
    
    console.log('บันทึกข้อมูล:', { studentId, status, remarks });
}

/**
 * แสดงตัวบอกว่ามีข้อมูลที่ยังไม่ได้บันทึก
 */
function showSaveIndicator() {
    let indicator = document.getElementById('save-indicator');
    
    if (!indicator) {
        // สร้างตัวบอกใหม่
        indicator = document.createElement('div');
        indicator.id = 'save-indicator';
        indicator.style.position = 'fixed';
        indicator.style.bottom = '20px';
        indicator.style.left = '20px';
        indicator.style.padding = '8px 12px';
        indicator.style.borderRadius = '5px';
        indicator.style.backgroundColor = '#ff9800';
        indicator.style.color = 'white';
        indicator.style.fontWeight = 'bold';
        indicator.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        indicator.style.zIndex = '9999';
        indicator.textContent = 'มีข้อมูลที่ยังไม่ได้บันทึก! กรุณากดปุ่ม "บันทึก"';
        
        document.body.appendChild(indicator);
    }
}


/**
 * แสดงการเช็คชื่อในหน้าเว็บ โดยไม่ย้ายรายการหรือบันทึกลงฐานข้อมูล
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 * @param {number} studentId - ID ของนักเรียน
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function markAttendanceUI(button, status, studentId, remarks) {
    // ดึงรายการนักเรียน
    const studentItem = button.closest('.student-item');
    
    // ดึงปุ่มทั้งหมด
    const buttons = studentItem.querySelectorAll('.action-button');
    
    // ลบคลาส active จากทุกปุ่ม และรีเซ็ตสไตล์
    buttons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.transform = '';
        btn.style.boxShadow = '';
        btn.style.backgroundColor = '';
    });
    
    // เพิ่มสไตล์โดยตรงให้กับปุ่มที่ถูกคลิก
    button.classList.add('active');
    button.style.transform = 'scale(1.1)';
    button.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    
    // กำหนดสีพื้นหลังตามสถานะ
    switch (status) {
        case 'present':
            button.style.backgroundColor = '#4caf50';
            button.style.color = 'white';
            break;
        case 'late':
            button.style.backgroundColor = '#ff9800';
            button.style.color = 'white';
            break;
        case 'leave':
            button.style.backgroundColor = '#9c27b0';
            button.style.color = 'white';
            break;
        case 'absent':
            button.style.backgroundColor = '#f44336';
            button.style.color = 'white';
            break;
    }
    
    // เพิ่มข้อมูลสถานะให้กับรายการนักเรียน
    studentItem.setAttribute('data-status', status);
    
    // รีเซ็ตสไตล์ทั้งหมดแล้วเพิ่มสไตล์ใหม่ตามสถานะ
    studentItem.style.backgroundColor = '';
    studentItem.style.borderLeft = '';
    
    // กำหนดสไตล์ตามสถานะที่เลือก
    switch (status) {
        case 'present':
            studentItem.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
            studentItem.style.borderLeft = '3px solid #4caf50';
            break;
        case 'late':
            studentItem.style.backgroundColor = 'rgba(255, 152, 0, 0.1)';
            studentItem.style.borderLeft = '3px solid #ff9800';
            break;
        case 'leave':
            studentItem.style.backgroundColor = 'rgba(156, 39, 176, 0.1)';
            studentItem.style.borderLeft = '3px solid #9c27b0';
            break;
        case 'absent':
            studentItem.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
            studentItem.style.borderLeft = '3px solid #f44336';
            break;
    }
    
    // บันทึกข้อมูลในตัวแปร attendanceData
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);
    
    if (studentIndex >= 0) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        attendanceData.students[studentIndex].remarks = remarks;
    } else {
        // เพิ่มข้อมูลใหม่
        attendanceData.students.push({
            student_id: studentId,
            status: status,
            remarks: remarks
        });
    }
    
    // กำหนดข้อความสถานะตามที่เลือก
    let statusText = '';
    let statusColor = '';
    switch (status) {
        case 'present': 
            statusText = 'มาเรียน'; 
            statusColor = '#4caf50';
            break;
        case 'late': 
            statusText = 'มาสาย'; 
            statusColor = '#ff9800';
            break;
        case 'leave': 
            statusText = 'ลา'; 
            statusColor = '#9c27b0';
            break;
        case 'absent': 
            statusText = 'ขาด'; 
            statusColor = '#f44336';
            break;
    }
    
    // ลบข้อความสถานะเดิม (ถ้ามี)
    const existingStatusElem = studentItem.querySelector('.status-indicator');
    if (existingStatusElem) {
        existingStatusElem.remove();
    }
    
    // เพิ่มข้อความสถานะใหม่ด้วยสไตล์ inline
    const nameElement = studentItem.querySelector('.student-name');
    const statusIndicator = document.createElement('div');
    statusIndicator.className = 'status-indicator';
    statusIndicator.textContent = `สถานะ: ${statusText}`;
    statusIndicator.style.fontSize = '12px';
    statusIndicator.style.marginTop = '4px';
    statusIndicator.style.color = statusColor;
    statusIndicator.style.fontWeight = 'normal';
    
    // เพิ่มเข้าไปในชื่อนักเรียน
    nameElement.appendChild(statusIndicator);
    
    // แสดงตัวบอกว่ามีข้อมูลที่ยังไม่ได้บันทึก
    updateSaveIndicator();
    
    // บันทึกลงในคอนโซลเพื่อดีบัก
    console.log(`เปลี่ยนสถานะ: นักเรียน ID ${studentId} เป็น ${status}`);
}

/**
 * เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดตามสถานะที่เลือก
 */
function markAllWithStatus() {
    console.log("markAllWithStatus เริ่มทำงาน");
    
    try {
        // ดึงทุกรายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const uncheckedTab = document.getElementById('unchecked-tab');
        const studentItems = uncheckedTab.querySelectorAll('.student-item');
        
        // ถ้าไม่มีนักเรียนที่ยังไม่ได้เช็คชื่อ
        if (studentItems.length === 0) {
            closeModal('mark-all-modal');
            showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
            return;
        }
        
        // ดึงสถานะที่เลือก
        const selectedStatus = document.querySelector('input[name="mark-all-status"]:checked').value;
        console.log("สถานะที่เลือก:", selectedStatus);
        
        // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
        let remarks = '';
        if (isRetroactive) {
            const remarksInput = document.getElementById('retroactive-note');
            if (remarksInput) {
                remarks = remarksInput.value.trim();
                
                if (remarks === '') {
                    showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
            }
        }
        
        // ทำการเช็คชื่อทุกคน
        studentItems.forEach(item => {
            const studentId = item.getAttribute('data-id');
            console.log("กำลังเช็คนักเรียน ID:", studentId);
            
            // หาปุ่มที่ต้องการใช้
            const buttonToUse = (selectedStatus === 'present' || selectedStatus === 'late' || selectedStatus === 'leave') 
                ? item.querySelector('.action-button.present') 
                : item.querySelector('.action-button.absent');
            
            if (buttonToUse) {
                // เรียกใช้ฟังก์ชัน markAttendance โดยตรง
                markAttendance(buttonToUse, selectedStatus, studentId);
            } else {
                console.warn("ไม่พบปุ่มสำหรับนักเรียน ID:", studentId);
            }
        });
        
        // แสดงข้อความสำเร็จ
        const statusText = selectedStatus === 'present' ? 'มาเรียน' : 
                           selectedStatus === 'absent' ? 'ขาด' : 
                           selectedStatus === 'late' ? 'มาสาย' : 'ลา';
                           
        showAlert(`เลือกสถานะนักเรียน ${studentItems.length} คน เป็น "${statusText}" แล้ว (ข้อมูลยังไม่ถูกบันทึก)`, 'info');
        
        // ปิด Modal
        closeModal('mark-all-modal');
        
        console.log(`เลือกสถานะทั้งหมด: ${studentItems.length} คน เป็น ${selectedStatus}`);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด:', error);
    }
}


/**
 * แสดงตัวบอกว่ามีข้อมูลที่ยังไม่ได้บันทึก
 */
function updateSaveIndicator() {
    // ตรวจสอบว่ามีการเลือกข้อมูลใดๆ หรือไม่
    const hasSelections = document.querySelectorAll('#unchecked-tab .student-item[data-status]').length > 0;
    
    // ถ้ามีแล้ว แสดงตัวบอก
    if (hasSelections) {
        // ตรวจสอบว่ามีตัวบอกอยู่แล้วหรือไม่
        let indicator = document.querySelector('.save-indicator');
        
        if (!indicator) {
            // สร้างตัวบอกใหม่
            indicator = document.createElement('div');
            indicator.className = 'save-indicator';
            indicator.textContent = 'มีข้อมูลที่ยังไม่ได้บันทึก! กรุณากดปุ่ม "บันทึก"';
            indicator.style.position = 'fixed';
            indicator.style.bottom = '20px';
            indicator.style.left = '20px';
            indicator.style.padding = '8px 12px';
            indicator.style.borderRadius = '5px';
            indicator.style.backgroundColor = 'rgba(255, 152, 0, 0.9)';
            indicator.style.color = 'white';
            indicator.style.fontSize = '14px';
            indicator.style.fontWeight = '500';
            indicator.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
            indicator.style.zIndex = '900';
            
            // เพิ่ม animation
            const keyframes = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }`;
            
            const styleElement = document.createElement('style');
            styleElement.innerHTML = keyframes;
            document.head.appendChild(styleElement);
            
            indicator.style.animation = 'pulse 2s infinite';
            
            document.body.appendChild(indicator);
        }
    } else {
        // ถ้าไม่มีการเลือก ให้ลบตัวบอกออก
        const indicator = document.querySelector('.save-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
}

/**
 * ยืนยันการเช็คชื่อจาก Modal
 */
function confirmMarkAttendance() {
    console.log("confirmMarkAttendance เริ่มทำงาน");
    
    // ดึงข้อมูลจาก Modal
    const studentId = document.getElementById('student-id-input').value;
    const status = document.querySelector('input[name="attendance-status"]:checked').value;
    const isEditMode = document.getElementById('is-edit-mode').value === '1';
    
    console.log("ข้อมูลที่จะบันทึก:", { studentId, status, isEditMode });
    
    // ดึงข้อมูลหมายเหตุ
    let remarks = '';
    
    // ถ้าเป็นสถานะสายหรือลา ให้ดึงหมายเหตุจากช่องเหตุผล
    if (status === 'late' || status === 'leave') {
        const reasonInput = document.getElementById('status-reason');
        if (reasonInput) {
            remarks = reasonInput.value.trim();
            
            // ตรวจสอบว่ามีการระบุเหตุผลหรือไม่
            if (remarks === '') {
                showAlert('กรุณาระบุเหตุผลสำหรับการมาสาย/ลา', 'warning');
                return;
            }
        }
    }
    
    // ถ้าเป็นการเช็คชื่อย้อนหลัง ให้ดึงหมายเหตุเพิ่มเติม
    if (isRetroactive) {
        const retroactiveInput = document.getElementById('individual-note');
        if (retroactiveInput) {
            const retroactiveNote = retroactiveInput.value.trim();
            
            // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
            if (retroactiveNote === '') {
                showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                return;
            }
            
            // ถ้ามีหมายเหตุสำหรับสาย/ลาแล้ว ให้เพิ่มหมายเหตุย้อนหลังต่อท้าย
            if (remarks) {
                remarks += ` (${retroactiveNote})`;
            } else {
                remarks = retroactiveNote;
            }
        }
    }
    
    // บันทึกข้อมูลในตัวแปร global
    saveAttendanceData(studentId, status, remarks);
    
    // ปิด Modal
    closeModal('mark-attendance-modal');
    
    // แสดงตัวบ่งชี้การบันทึก
    showSaveIndicator();
    
    // ถ้าเป็นโหมดแก้ไข ให้อัพเดทสถานะในหน้าเว็บ
    if (isEditMode) {
        console.log("กำลังอัพเดท UI ในโหมดแก้ไข");
        
        // ค้นหารายการนักเรียนที่ต้องการแก้ไข
        const studentItem = document.querySelector(`#checked-tab .student-item[data-id="${studentId}"]`);
        if (!studentItem) {
            console.error("ไม่พบ element ของนักเรียน ID:", studentId);
            showAlert('ไม่พบข้อมูลนักเรียนที่ต้องการแก้ไข', 'error');
            return;
        }
        
        console.log("พบ element ของนักเรียน:", studentItem);
        
        // อัพเดทสถานะในหน้าเว็บ
        studentItem.setAttribute('data-status', status);
        
        // กำหนดค่าไอคอนและข้อความตามสถานะ
        let statusIcon, statusText, statusClass;
        
        switch (status) {
            case 'present':
                statusIcon = 'check_circle';
                statusText = 'มา';
                statusClass = 'present';
                break;
            case 'late':
                statusIcon = 'schedule';
                statusText = 'สาย';
                statusClass = 'late';
                break;
            case 'leave':
                statusIcon = 'event_note';
                statusText = 'ลา';
                statusClass = 'leave';
                break;
            case 'absent':
            default:
                statusIcon = 'cancel';
                statusText = 'ขาด';
                statusClass = 'absent';
                break;
        }
        
        console.log("ค่าสถานะที่จะอัพเดท:", { statusIcon, statusText, statusClass });
        
        // อัพเดทแสดงผลสถานะ
        const statusDisplay = studentItem.querySelector('.student-status');
        if (statusDisplay) {
            console.log("กำลังอัพเดทแสดงผลสถานะ");
            // ล้างคลาสเดิมและเพิ่มคลาสใหม่
            statusDisplay.className = '';
            statusDisplay.className = `student-status ${statusClass}`;
            // อัพเดทเนื้อหา
            statusDisplay.innerHTML = `<span class="material-icons">${statusIcon}</span> ${statusText}`;
            console.log("อัพเดทสถานะเสร็จสิ้น");
        } else {
            console.error("ไม่พบ element .student-status");
        }
        
        // อัพเดทหมายเหตุ (ถ้ามี)
        let remarksElem = studentItem.querySelector('.student-remarks');
        if (remarks) {
            console.log("กำลังอัพเดทหมายเหตุ:", remarks);
            if (remarksElem) {
                // อัพเดทหมายเหตุที่มีอยู่
                remarksElem.textContent = remarks;
            } else {
                // สร้างหมายเหตุใหม่
                remarksElem = document.createElement('div');
                remarksElem.className = 'student-remarks';
                remarksElem.textContent = remarks;
                
                const nameElem = studentItem.querySelector('.student-name');
                if (nameElem) {
                    nameElem.appendChild(remarksElem);
                }
            }
        } else if (remarksElem) {
            // ลบหมายเหตุออกถ้าไม่มีค่า
            remarksElem.remove();
        }
        
        // อัพเดทข้อมูลเวลาเช็คชื่อให้เป็นเวลาปัจจุบัน
        const timeDisplay = studentItem.querySelector('.check-time');
        if (timeDisplay) {
            console.log("กำลังอัพเดทเวลา");
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            timeDisplay.textContent = `${hours}:${minutes}`;
        }
        
        // อัพเดทข้อมูลวิธีการเช็คชื่อ
        const methodDisplay = studentItem.querySelector('.check-method');
        if (methodDisplay) {
            console.log("กำลังอัพเดทวิธีการเช็คชื่อ");
            methodDisplay.textContent = 'ครู';
        }
        
        // อัพเดทสถิติการเช็คชื่อ
        updateAttendanceCounters();
        
        console.log("อัพเดท UI เสร็จสมบูรณ์");
        
        // แสดงข้อความสำเร็จ
        showAlert('แก้ไขข้อมูลการเช็คชื่อแล้ว (ยังไม่ได้บันทึก)', 'info');
    } else {
        // กรณีเป็นการเช็คชื่อใหม่ จะใช้โค้ดเดิมตามที่มีอยู่แล้ว...
    }
}

/**
 * ฟังก์ชันภายในสำหรับเช็คชื่อนักเรียน
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 * @param {number} studentId - ID ของนักเรียน
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function markAttendanceInternal(button, status, studentId, remarks) {
    // ดึงรายการนักเรียน
    const studentItem = button.closest('.student-item');
    
    // ดึงปุ่มทั้งหมด
    const buttons = studentItem.querySelectorAll('.action-button');
    
    // ลบคลาส active จากทุกปุ่ม
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // เพิ่มคลาส active ให้กับปุ่มที่ถูกคลิก
    button.classList.add('active');
    
    // เพิ่มข้อมูลสถานะให้กับรายการนักเรียน
    studentItem.setAttribute('data-status', status);
    
    // บันทึกข้อมูลในตัวแปร attendanceData
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);
    
    if (studentIndex >= 0) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        attendanceData.students[studentIndex].remarks = remarks;
    } else {
        // เพิ่มข้อมูลใหม่
        attendanceData.students.push({
            student_id: studentId,
            status: status,
            remarks: remarks
        });
    }
    
    // สร้างสถานะการแสดงผล
    let statusIcon, statusText, statusClass;
    
    switch (status) {
        case 'present':
            statusIcon = 'check_circle';
            statusText = 'มา';
            statusClass = 'present';
            break;
        case 'late':
            statusIcon = 'schedule';
            statusText = 'สาย';
            statusClass = 'late';
            break;
        case 'leave':
            statusIcon = 'event_note';
            statusText = 'ลา';
            statusClass = 'leave';
            break;
        case 'absent':
        default:
            statusIcon = 'cancel';
            statusText = 'ขาด';
            statusClass = 'absent';
            break;
    }
    
    // เลื่อนรายการไปยังแท็บที่เช็คชื่อแล้ว
    moveToCheckedTab(studentItem, status, statusIcon, statusText, statusClass, remarks);
    
    // อัพเดทจำนวนการเข้าแถว
    updateAttendanceCounters();
    
    // อัพเดทจำนวนในแท็บ
    updateTabCounts();
}

/**
 * ย้ายรายการนักเรียนไปยังแท็บที่เช็คชื่อแล้ว
 * @param {HTMLElement} studentItem - รายการนักเรียน
 * @param {string} status - สถานะการเข้าแถว
 * @param {string} statusIcon - ไอคอนสถานะ
 * @param {string} statusText - ข้อความสถานะ
 * @param {string} statusClass - คลาสสถานะ
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function moveToCheckedTab(studentItem, status, statusIcon, statusText, statusClass, remarks) {
    // คลอนรายการ
    const newItem = studentItem.cloneNode(true);
    
    // สร้างรายการใหม่สำหรับแท็บที่เช็คชื่อแล้ว
    const number = newItem.querySelector('.student-number').textContent;
    const nameElement = newItem.querySelector('.student-name');
    const studentId = newItem.getAttribute('data-id');
    
    // สร้าง HTML สำหรับรายการในแท็บที่เช็คชื่อแล้ว
    const currentTime = new Date();
    const timeString = `${currentTime.getHours().toString().padStart(2, '0')}:${currentTime.getMinutes().toString().padStart(2, '0')}`;
    
    // สร้างรายการใหม่
    const checkedItem = document.createElement('div');
    checkedItem.className = 'student-item';
    checkedItem.setAttribute('data-name', nameElement.textContent);
    checkedItem.setAttribute('data-id', studentId);
    checkedItem.setAttribute('data-status', status);
    
    // สร้าง HTML สำหรับชื่อนักเรียน พร้อมหมายเหตุ (ถ้ามี)
    let nameHTML = nameElement.textContent;
    if (remarks) {
        nameHTML += `<div class="student-remarks">${remarks}</div>`;
    }
    
    checkedItem.innerHTML = `
        <div class="student-number">${number}</div>
        <div class="student-name" onclick="editAttendance(${studentId}, '${nameElement.textContent.replace(/'/g, "\\'")}', '${status}', '${remarks ? remarks.replace(/'/g, "\\'") : ''}')">${nameHTML}</div>
        <div class="student-status ${statusClass}">
            <span class="material-icons">${statusIcon}</span> ${statusText}
        </div>
        <div class="check-info">
            <div class="check-time">${timeString}</div>
            <div class="check-method">ครู</div>
        </div>
    `;
    
    // เพิ่มรายการใหม่ไปยังแท็บที่เช็คชื่อแล้ว
    const checkedTab = document.getElementById('checked-tab');
    const studentList = checkedTab.querySelector('.student-list');
    
    if (studentList) {
        // เช็คว่ามีข้อความว่างหรือไม่
        const emptyState = checkedTab.querySelector('.empty-state');
        if (emptyState) {
            // ถ้ามี ให้ลบออกและสร้างรายการใหม่
            emptyState.remove();
            
            // สร้างตารางใหม่
            const newList = document.createElement('div');
            newList.className = 'student-list';
            newList.innerHTML = `
                <div class="list-header">
                    <div>เลขที่</div>
                    <div>ชื่อ-นามสกุล</div>
                    <div>สถานะ</div>
                    <div>เวลา/วิธี</div>
                </div>
            `;
            
            // เพิ่มรายการใหม่
            newList.appendChild(checkedItem);
            
            // เพิ่มตารางใหม่ไปยังแท็บ
            checkedTab.appendChild(newList);
        } else {
            // ถ้าไม่มี ให้เพิ่มรายการใหม่ไปยังตารางที่มีอยู่
            studentList.appendChild(checkedItem);
        }
    }
    
    // ลบรายการเดิมออกจากแท็บที่ยังไม่ได้เช็ค
    studentItem.remove();
    
    // ตรวจสอบว่ารายการในแท็บที่ยังไม่ได้เช็คว่างหรือไม่
    const uncheckedTab = document.getElementById('unchecked-tab');
    const uncheckedItems = uncheckedTab.querySelectorAll('.student-item');
    
    if (uncheckedItems.length === 0) {
        // ถ้าว่าง ให้แสดงข้อความว่าง
        const uncheckedList = uncheckedTab.querySelector('.student-list');
        if (uncheckedList) {
            uncheckedList.remove();
            
            // สร้างข้อความว่าง
            const emptyUnchecked = document.createElement('div');
            emptyUnchecked.className = 'empty-state';
            emptyUnchecked.innerHTML = `
                <span class="material-icons">check_circle</span>
                <p>เช็คชื่อครบทุกคนแล้ว!</p>
            `;
            
            uncheckedTab.appendChild(emptyUnchecked);
        }
    }
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('search-input');
    const searchTerm = searchInput.value.toLowerCase();
    
    // ค้นหาในแท็บที่ยังไม่ได้เช็ค
    searchInTab('unchecked-tab', searchTerm);
    
    // ค้นหาในแท็บที่เช็คแล้ว
    searchInTab('checked-tab', searchTerm);
}

/**
 * ค้นหานักเรียนในแท็บที่กำหนด
 * @param {string} tabId - ID ของแท็บ
 * @param {string} searchTerm - คำค้นหา
 */
function searchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    const studentItems = tab.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        const name = item.getAttribute('data-name') || item.querySelector('.student-name').textContent.toLowerCase();
        
        if (name.toLowerCase().includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * ติดตั้งเหตุการณ์ค้นหา
 */
function initSearchFunction() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', searchStudents);
    }
}

/**
 * สลับแท็บที่แสดง
 * @param {string} tabName - ชื่อแท็บ
 */
function showTab(tabName) {
    // ซ่อนทุกแท็บ
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่เลือก
    const selectedTab = document.getElementById(`${tabName}-tab`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // ปรับปุ่มแท็บ
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // เพิ่มคลาส active ให้กับปุ่มที่เลือก
    const selectedButton = document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

/**
 * อัพเดทจำนวนในแท็บ
 */
function updateTabCounts() {
    // นับจำนวนนักเรียนในแต่ละแท็บ
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    const checkedCount = document.querySelectorAll('#checked-tab .student-item').length;
    
    // อัพเดทจำนวนในปุ่มแท็บ
    const uncheckedButton = document.querySelector('.tab-btn[onclick="showTab(\'unchecked\')"] .count');
    const checkedButton = document.querySelector('.tab-btn[onclick="showTab(\'checked\')"] .count');
    
    if (uncheckedButton) {
        uncheckedButton.textContent = uncheckedCount;
    }
    
    if (checkedButton) {
        checkedButton.textContent = checkedCount;
    }
    
    // อัพเดท Remaining Students ใน Save Modal
    const remainingStudents = document.getElementById('remaining-students');
    if (remainingStudents) {
        remainingStudents.textContent = uncheckedCount;
    }
}

/**
 * อัพเดทจำนวนการเข้าแถว
 */
function updateAttendanceCounters() {
    // คำนวณสถิติการเช็คชื่อ
    const presentCount = document.querySelectorAll('#checked-tab .student-item[data-status="present"]').length;
    const lateCount = document.querySelectorAll('#checked-tab .student-item[data-status="late"]').length;
    const leaveCount = document.querySelectorAll('#checked-tab .student-item[data-status="leave"]').length;
    const absentCount = document.querySelectorAll('#checked-tab .student-item[data-status="absent"]').length;
    const notCheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    
    // นับรวมจำนวนการมาเรียน (มา+สาย+ลา เป็น 'มาเรียน')
    const totalPresent = presentCount + lateCount + leaveCount;
    
    // อัพเดทสถิติ
    document.getElementById('present-count').textContent = totalPresent;
    document.getElementById('absent-count').textContent = absentCount;
    document.getElementById('not-checked-count').textContent = notCheckedCount;
}

/**
 * ฟังก์ชันเมื่อคลิกปุ่มบันทึก - แสดง Modal ยืนยันการบันทึก
 */
function saveAttendance() {
    console.log("saveAttendance เริ่มทำงาน");
    
    // เวอร์ชั่นใหม่: ไม่ต้องตรวจสอบข้อมูลก่อน เพราะเราต้องการให้ Modal แสดงเสมอ
    // เพื่อยืนยันการบันทึกทั้งกรณีมีการเปลี่ยนแปลงและไม่มีการเปลี่ยนแปลง
    
    // ตรวจสอบจำนวนนักเรียนที่ยังไม่ได้เช็คชื่อ
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    console.log("จำนวนนักเรียนที่ยังไม่ได้เช็คชื่อ:", uncheckedCount);
    
    // อัพเดทจำนวนนักเรียนที่ยังไม่ได้เช็คชื่อในโมดัล
    const remainingStudents = document.getElementById('remaining-students');
    if (remainingStudents) {
        remainingStudents.textContent = uncheckedCount;
    }
    
    // แสดง Modal ยืนยัน
    const modal = document.getElementById('save-modal');
    console.log("Modal element:", modal);
    
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        console.log("เปิด Modal แล้ว");
    } else {
        console.error("ไม่พบ Modal element ที่มี ID 'save-modal'");
        // หากไม่พบ Modal ให้เรียก confirmSaveAttendance() เลย
        confirmSaveAttendance();
    }
}

/**
 * ยืนยันการบันทึกการเช็คชื่อ - รวบรวมข้อมูลและส่งไปยัง API
 */
function confirmSaveAttendance() {
    // รวบรวมข้อมูลการเช็คชื่อทั้งหมด
    let allStudents = [];
    
    // ดึงรายการนักเรียนที่เช็คแล้ว (อยู่ในแท็บ "เช็คชื่อแล้ว")
    const checkedStudents = document.querySelectorAll('#checked-tab .student-item');
    checkedStudents.forEach(item => {
        const studentId = item.getAttribute('data-id');
        
        // ตรวจสอบว่ามีข้อมูลใน attendanceData หรือไม่
        const studentDataIndex = attendanceData.students.findIndex(s => s.student_id === studentId);
        
        if (studentDataIndex >= 0) {
            // ถ้ามี ใช้ข้อมูลจาก attendanceData
            allStudents.push({
                student_id: studentId,
                status: attendanceData.students[studentDataIndex].status,
                remarks: attendanceData.students[studentDataIndex].remarks || ''
            });
        } else {
            // ถ้าไม่มี ใช้ข้อมูลจาก DOM
            const remarksElem = item.querySelector('.student-remarks');
            const remarks = remarksElem ? remarksElem.textContent : '';
            
            allStudents.push({
                student_id: studentId,
                status: item.getAttribute('data-status'),
                remarks: remarks
            });
        }
    });
    
    // ดึงรายการนักเรียนที่ยังไม่ได้เช็ค (อยู่ในแท็บ "รอเช็คชื่อ")
    const uncheckedStudents = document.querySelectorAll('#unchecked-tab .student-item');
    uncheckedStudents.forEach(item => {
        const studentId = item.getAttribute('data-id');
        
        // ตรวจสอบว่ามีข้อมูลใน attendanceData หรือไม่
        const studentDataIndex = attendanceData.students.findIndex(s => s.student_id === studentId);
        
        if (studentDataIndex >= 0) {
            // ถ้ามี ใช้ข้อมูลจาก attendanceData
            allStudents.push({
                student_id: studentId,
                status: attendanceData.students[studentDataIndex].status,
                remarks: attendanceData.students[studentDataIndex].remarks || ''
            });
        } else {
            // ถ้าไม่มี ใช้ค่าเริ่มต้นเป็น absent
            allStudents.push({
                student_id: studentId,
                status: 'absent',
                remarks: ''
            });
        }
    });
    
    // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
    if (isRetroactive) {
        const remarksInput = document.getElementById('retroactive-save-note');
        if (remarksInput) {
            const remarks = remarksInput.value.trim();
            
            // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
            if (remarks === '') {
                showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                return;
            }
            
            // เพิ่มหมายเหตุให้กับรายการที่ยังไม่มีหมายเหตุ
            allStudents.forEach(student => {
                if (!student.remarks) {
                    student.remarks = remarks;
                }
            });
        }
    }
    
    // ปิด Modal
    closeModal('save-modal');
    
    // แสดงการโหลด
    const loadingButton = document.querySelector('.floating-button');
    if (loadingButton) {
        // เปลี่ยนไอคอนเป็นหมุน
        const icon = loadingButton.querySelector('.material-icons');
        const originalIcon = icon.textContent;
        icon.textContent = 'hourglass_top';
        
        // ปิดการใช้งานปุ่ม
        loadingButton.disabled = true;
        loadingButton.style.backgroundColor = '#9e9e9e';
        
        // ส่งข้อมูลไปยัง API
        fetch('api/save_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                class_id: currentClassId,
                date: checkDate,
                teacher_id: currentTeacherId,
                students: allStudents,
                is_retroactive: isRetroactive,
                check_method: 'Manual'
            })
        })
        .then(response => response.json())
        .then(data => {
            // คืนค่าเดิม
            icon.textContent = originalIcon;
            loadingButton.disabled = false;
            loadingButton.style.backgroundColor = '';
            
            if (data.success) {
                // แสดงข้อความแจ้งเตือน
                showAlert('บันทึกการเช็คชื่อเรียบร้อย', 'success');
                
                // นำทางไปยังหน้าหลัก (ปรับเป็นหน้าเดิมแทนเพื่อให้ครูตรวจสอบผลการบันทึก)
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // แสดงข้อความเมื่อมีข้อผิดพลาด
                showAlert(data.message || 'เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ', 'error');
            }
        })
        .catch(error => {
            // คืนค่าเดิม
            icon.textContent = originalIcon;
            loadingButton.disabled = false;
            loadingButton.style.backgroundColor = '';
            
            // แสดงข้อความเมื่อมีข้อผิดพลาด
            console.error('Error:', error);
            showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        });
    }
}



/**
 * จัดการเหตุการณ์คลิกที่รายการนักเรียน
 */
function setupStudentItemEvents() {
    // เพิ่มเหตุการณ์คลิกที่รายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
    const uncheckedItems = document.querySelectorAll('#unchecked-tab .student-item');
    uncheckedItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // ถ้าคลิกที่ปุ่มเช็คชื่อ ไม่ต้องทำอะไร
            if (e.target.closest('.action-button') || e.target.closest('.student-name')) {
                return;
            }
            
            // แสดง Modal เช็คชื่อรายบุคคล
            const studentId = this.getAttribute('data-id');
            const studentName = this.querySelector('.student-name').textContent.trim();
            
            showAttendanceModal(studentId, studentName);
        });
    });
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // ถ้ามีฟังก์ชันจาก main.js ใช้อันนั้น
    if (typeof window.displayAlert === 'function') {
        window.displayAlert(message, type);
        return;
    }
    
    // สร้าง Alert Container ถ้ายังไม่มี
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง Alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <div class="alert-content">
            <span class="alert-icon material-icons">${getAlertIcon(type)}</span>
            <span class="alert-message">${message}</span>
        </div>
        <span class="alert-close material-icons">close</span>
    `;
    
    // เพิ่มการจัดการเหตุการณ์ปิดการแจ้งเตือน
    const closeButton = alertDiv.querySelector('.alert-close');
    closeButton.addEventListener('click', () => {
        alertDiv.remove();
    });
    
    // เพิ่มการแจ้งเตือนในคอนเทนเนอร์
    alertContainer.appendChild(alertDiv);
    
    // กำหนดให้ปิดอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode === alertContainer) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * รับไอคอนสำหรับประเภทการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน
 * @returns {string} - ชื่อไอคอน Material Icons
 */
function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'warning': return 'warning';
        case 'error': return 'error';
        case 'info':
        default: return 'info';
    }
}

/**
 * ย้อนกลับ
 */
function goBack() {
    window.history.back();
}

/**
 * แสดงตัวเลือกเพิ่มเติม
 */
function toggleOptions() {
    // ในระบบจริงจะมีการแสดงเมนูเพิ่มเติม
    showAlert('เมนูเพิ่มเติม', 'info');
}
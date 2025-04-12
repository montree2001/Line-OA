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
 * เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดตามสถานะที่เลือก
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
    
    // เตรียมข้อมูลสำหรับการบันทึก
    const studentsToMark = [];
    
    // เก็บข้อมูลนักเรียนที่จะเช็คชื่อทั้งหมด
    studentItems.forEach(item => {
        studentsToMark.push({
            student_id: item.getAttribute('data-id'),
            status: selectedStatus,
            remarks: remarks
        });
    });
    
    // บันทึกข้อมูลการเช็คชื่อทั้งหมด
    saveMultipleAttendance(studentsToMark);
    
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
 * เช็คชื่อนักเรียน (ฟังก์ชันที่เรียกจากปุ่มในหน้าเว็บ)
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    // ดึงรายการนักเรียน
    const studentItem = button.closest('.student-item');
    const studentName = studentItem.querySelector('.student-name').textContent.trim();
    
    // กรณีเช็คชื่อย้อนหลัง แสดง Modal ให้กรอกหมายเหตุ
    if (isRetroactive) {
        // เตรียมข้อมูลสำหรับ Modal
        document.getElementById('student-name-display').textContent = studentName;
        document.getElementById('student-id-input').value = studentId;
        
        // เลือกสถานะการเช็คชื่อตามที่คลิก
        document.getElementById('status-' + status).checked = true;
        
        // แสดง Modal
        const modal = document.getElementById('mark-attendance-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    } else {
        // ถ้าไม่ใช่การเช็คชื่อย้อนหลัง ให้ดำเนินการเช็คชื่อทันที
        markAttendanceInternal(button, status, studentId);
    }
}

/**
 * ยืนยันการเช็คชื่อจาก Modal
 */
function confirmMarkAttendance() {
    // ดึงข้อมูลจาก Modal
    const studentId = document.getElementById('student-id-input').value;
    const status = document.querySelector('input[name="attendance-status"]:checked').value;
    let remarks = '';
    
    if (isRetroactive) {
        remarks = document.getElementById('individual-note').value.trim();
        
        // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
        if (remarks === '') {
            showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
            return;
        }
    }
    
    // ปิด Modal
    closeModal('mark-attendance-modal');
    
    // บันทึกการเช็คชื่อ
    const student = {
        student_id: studentId,
        status: status,
        remarks: remarks
    };
    
    saveMultipleAttendance([student]);
}

/**
 * ฟังก์ชันภายในสำหรับเช็คชื่อนักเรียน
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendanceInternal(button, status, studentId) {
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
    } else {
        // เพิ่มข้อมูลใหม่
        attendanceData.students.push({
            student_id: studentId,
            status: status
        });
    }
    
    // เลื่อนรายการไปยังแท็บที่เช็คชื่อแล้ว
    moveToCheckedTab(studentItem, status);
    
    // อัพเดทจำนวนการเข้าแถว
    updateAttendanceCounters();
    
    // อัพเดทจำนวนในแท็บ
    updateTabCounts();
}

/**
 * ย้ายรายการนักเรียนไปยังแท็บที่เช็คชื่อแล้ว
 * @param {HTMLElement} studentItem - รายการนักเรียน
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 */
function moveToCheckedTab(studentItem, status) {
    // คลอนรายการ
    const newItem = studentItem.cloneNode(true);
    
    // สร้างรายการใหม่สำหรับแท็บที่เช็คชื่อแล้ว
    const number = newItem.querySelector('.student-number').textContent;
    const nameElement = newItem.querySelector('.student-name');
    const studentId = newItem.getAttribute('data-id');
    
    // สร้าง HTML สำหรับรายการในแท็บที่เช็คชื่อแล้ว
    const currentTime = new Date();
    const timeString = `${currentTime.getHours().toString().padStart(2, '0')}:${currentTime.getMinutes().toString().padStart(2, '0')}`;
    
    // กำหนดไอคอนและข้อความตามสถานะ
    let statusIcon, statusText;
    
    switch (status) {
        case 'present':
            statusIcon = 'check_circle';
            statusText = 'มา';
            break;
        case 'late':
            statusIcon = 'schedule';
            statusText = 'สาย';
            break;
        case 'leave':
            statusIcon = 'event_note';
            statusText = 'ลา';
            break;
        case 'absent':
        default:
            statusIcon = 'cancel';
            statusText = 'ขาด';
            break;
    }
    
    // สร้างรายการใหม่
    const checkedItem = document.createElement('div');
    checkedItem.className = 'student-item';
    checkedItem.setAttribute('data-name', nameElement.textContent);
    checkedItem.setAttribute('data-id', studentId);
    checkedItem.setAttribute('data-status', status);
    
    checkedItem.innerHTML = `
        <div class="student-number">${number}</div>
        <div class="student-name">${nameElement.innerHTML}</div>
        <div class="student-status ${status}">
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
                    <div>รหัส/ชื่อ-นามสกุล</div>
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
    
    // ในหน้าเว็บนี้ มา+สาย+ลา จะนับในกลุ่มมาเรียน ส่วนขาดก็จะนับในกลุ่มขาดเรียน
    const totalPresent = presentCount + lateCount + leaveCount;
    
    // อัพเดทสถิติ
    document.getElementById('present-count').textContent = totalPresent;
    document.getElementById('absent-count').textContent = absentCount;
    document.getElementById('not-checked-count').textContent = notCheckedCount;
}

/**
 * ฟังก์ชันเมื่อคลิกปุ่มบันทึก
 */
function saveAttendance() {
    // ตรวจสอบว่าถ้ามีนักเรียนที่ยังไม่ได้เช็คชื่อ
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    
    // ถ้ามี ให้แสดง Modal ยืนยัน
    const modal = document.getElementById('save-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ยืนยันการบันทึกการเช็คชื่อ
 */
function confirmSaveAttendance() {
    // รวบรวมข้อมูลการเช็คชื่อทั้งหมด
    let allStudents = [];
    
    // ดึงรายการนักเรียนที่เช็คแล้ว
    const checkedStudents = document.querySelectorAll('#checked-tab .student-item');
    checkedStudents.forEach(item => {
        allStudents.push({
            student_id: item.getAttribute('data-id'),
            status: item.getAttribute('data-status')
        });
    });
    
    // ดึงรายการนักเรียนที่ยังไม่ได้เช็ค (จะเป็นสถานะขาด)
    const uncheckedStudents = document.querySelectorAll('#unchecked-tab .student-item');
    uncheckedStudents.forEach(item => {
        allStudents.push({
            student_id: item.getAttribute('data-id'),
            status: 'absent' // ถ้าไม่ได้เช็ค ให้เป็นขาด
        });
    });
    
    // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
    let remarks = '';
    if (isRetroactive) {
        const remarksInput = document.getElementById('retroactive-save-note');
        if (remarksInput) {
            remarks = remarksInput.value.trim();
            
            // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
            if (remarks === '') {
                showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                return;
            }
            
            // เพิ่มหมายเหตุให้กับทุกรายการ
            allStudents.forEach(student => {
                student.remarks = remarks;
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
                
                // นำทางไปยังหน้ารายงาน
                setTimeout(() => {
                    window.location.href = 'home.php?class_id=' + currentClassId;
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
            if (e.target.closest('.action-button')) {
                return;
            }
            
            // แสดง Modal เช็คชื่อรายบุคคล
            const studentId = this.getAttribute('data-id');
            const studentName = this.querySelector('.student-name').textContent.trim();
            
            document.getElementById('student-name-display').textContent = studentName;
            document.getElementById('student-id-input').value = studentId;
            document.getElementById('status-present').checked = true; // เลือกสถานะ "มาเรียน" เป็นค่าเริ่มต้น
            
            const modal = document.getElementById('mark-attendance-modal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
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
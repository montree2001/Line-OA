/**
 * teacher-check-attendance.js - สคริปต์เฉพาะสำหรับหน้าเช็คชื่อนักเรียน
 */

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
    setActiveTab('unchecked');
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ในระบบจริงจะใช้ AJAX เพื่อเรียกข้อมูลของห้องเรียนใหม่
    // สำหรับตัวอย่าง เราจะนำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์
    window.location.href = 'check-attendance.php?class_id=' + classId;
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
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    const pinDisplay = document.querySelector('.pin-code');
    
    if (pinDisplay) {
        pinDisplay.textContent = pin;
    }
    
    // แสดงข้อความแจ้งเตือน
    showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
}

/**
 * สแกน QR Code
 */
function scanQRCode() {
    // ในระบบจริงจะมีการเรียกใช้ API สำหรับสแกน QR Code
    // ตัวอย่างนี้จะแสดงการแจ้งเตือนเท่านั้น
    showAlert('กำลังเปิดกล้องเพื่อสแกน QR Code...', 'info');
}

/**
 * แสดง Modal ยืนยันเช็คชื่อทั้งหมด
 */
function showMarkAllModal() {
    // แสดง Modal ยืนยันเช็คชื่อทั้งหมด
    const modal = document.getElementById('mark-all-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดเป็น "มาเรียน"
 */
function markAllPresent() {
    // ดึงทุกรายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
    const uncheckedTab = document.getElementById('unchecked-tab');
    const studentItems = uncheckedTab.querySelectorAll('.student-item');
    
    // ถ้าไม่มีนักเรียนที่ยังไม่ได้เช็คชื่อ
    if (studentItems.length === 0) {
        closeModal('mark-all-modal');
        showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }
    
    // เปลี่ยนสถานะให้เป็น "มาเรียน" ทั้งหมด
    studentItems.forEach(item => {
        // จำลองการคลิกปุ่ม "มาเรียน"
        const presentButton = item.querySelector('.action-button.present');
        markAttendanceInternal(presentButton, 'present', item.getAttribute('data-id'));
    });
    
    // อัพเดทจำนวนนักเรียน
    updateAttendanceCounters();
    
    // รีเฟรชแท็บ
    refreshTabs();
    
    // ปิด Modal
    closeModal('mark-all-modal');
    
    // แสดงข้อความแจ้งเตือน
    showAlert(`เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมด ${studentItems.length} คน สำเร็จ`, 'success');
}

/**
 * เช็คชื่อนักเรียน (ฟังก์ชันที่เรียกจากปุ่มในหน้าเว็บ)
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    // ดึงรายการนักเรียน
    const studentItem = button.closest('.student-item');
    
    // ตรวจสอบว่าเป็นรายการในแท็บที่ยังไม่ได้เช็คชื่อหรือไม่
    if (studentItem.parentElement.closest('#unchecked-tab')) {
        markAttendanceInternal(button, status, studentId);
        
        // เลื่อนรายการไปยังแท็บที่เช็คชื่อแล้ว
        moveToCheckedTab(studentItem, status);
        
        // อัพเดทจำนวนการเข้าแถว
        updateAttendanceCounters();
        
        // อัพเดทจำนวนในแท็บ
        updateTabCounts();
    } else {
        // ถ้าอยู่ในแท็บเช็คชื่อแล้ว จะไม่สามารถเปลี่ยนสถานะได้
        showAlert('รายการนี้เช็คชื่อแล้ว ไม่สามารถเปลี่ยนแปลงได้', 'info');
    }
}

/**
 * ฟังก์ชันภายในสำหรับเช็คชื่อนักเรียน
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendanceInternal(button, status, studentId) {
    // ดึงรายการนักเรียน
    const studentItem = button.closest('.student-item');
    
    // ดึงปุ่มมาเรียนและขาดเรียน
    const presentButton = studentItem.querySelector('.action-button.present');
    const absentButton = studentItem.querySelector('.action-button.absent');
    
    // ลบคลาส active จากทั้งสองปุ่ม
    presentButton.classList.remove('active');
    absentButton.classList.remove('active');
    
    // เพิ่มคลาส active ให้กับปุ่มที่ถูกคลิก
    button.classList.add('active');
    
    // เพิ่มข้อมูลสถานะให้กับรายการนักเรียน
    studentItem.setAttribute('data-status', status);
    
    // ในระบบจริงจะมีการบันทึกข้อมูลไปยัง server
    console.log(`เช็คชื่อนักเรียน ID: ${studentId} สถานะ: ${status}`);
}

/**
 * ย้ายรายการนักเรียนไปยังแท็บที่เช็คชื่อแล้ว
 * @param {HTMLElement} studentItem - รายการนักเรียน
 * @param {string} status - สถานะการเข้าแถว (present/absent)
 */
function moveToCheckedTab(studentItem, status) {
    // คลอนรายการ
    const newItem = studentItem.cloneNode(true);
    
    // สร้างรายการใหม่สำหรับแท็บที่เช็คชื่อแล้ว
    const number = newItem.querySelector('.student-number').textContent;
    const name = newItem.querySelector('.student-name').textContent;
    const studentId = newItem.getAttribute('data-id');
    
    // สร้าง HTML สำหรับรายการในแท็บที่เช็คชื่อแล้ว
    const currentTime = new Date();
    const timeString = `${currentTime.getHours().toString().padStart(2, '0')}:${currentTime.getMinutes().toString().padStart(2, '0')}`;
    
    // สร้างรายการใหม่
    const checkedItem = document.createElement('div');
    checkedItem.className = 'student-item';
    checkedItem.setAttribute('data-name', name);
    checkedItem.setAttribute('data-id', studentId);
    checkedItem.setAttribute('data-status', status);
    
    checkedItem.innerHTML = `
        <div class="student-number">${number}</div>
        <div class="student-name">${name}</div>
        <div class="student-status ${status}">
            ${status === 'present' 
              ? '<span class="material-icons">check_circle</span> มา' 
              : '<span class="material-icons">cancel</span> ขาด'}
        </div>
        <div class="check-time">${timeString}</div>
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
                    <div>เวลา</div>
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
function switchTab(tabName) {
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
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // เพิ่มคลาส active ให้กับปุ่มที่เลือก
    const selectedButton = document.querySelector(`.tab-button[onclick="switchTab('${tabName}')"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

/**
 * กำหนดแท็บเริ่มต้น
 * @param {string} tabName - ชื่อแท็บ
 */
function setActiveTab(tabName) {
    switchTab(tabName);
}

/**
 * อัพเดทจำนวนในแท็บ
 */
function updateTabCounts() {
    // นับจำนวนนักเรียนในแต่ละแท็บ
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    const checkedCount = document.querySelectorAll('#checked-tab .student-item').length;
    
    // อัพเดทจำนวนในปุ่มแท็บ
    const uncheckedButton = document.querySelector('.tab-button[onclick="switchTab(\'unchecked\')"] .count');
    const checkedButton = document.querySelector('.tab-button[onclick="switchTab(\'checked\')"] .count');
    
    if (uncheckedButton) {
        uncheckedButton.textContent = uncheckedCount;
    }
    
    if (checkedButton) {
        checkedButton.textContent = checkedCount;
    }
    
    // อัพเดทสถิติ
    document.getElementById('not-checked-count').textContent = uncheckedCount;
    
    // คำนวณนักเรียนมาเรียนและขาดเรียน
    const presentCount = document.querySelectorAll('#checked-tab .student-item[data-status="present"]').length;
    const absentCount = document.querySelectorAll('#checked-tab .student-item[data-status="absent"]').length;
    
    document.getElementById('present-count').textContent = presentCount;
    document.getElementById('absent-count').textContent = absentCount;
}

/**
 * รีเฟรชข้อมูลในแท็บ
 */
function refreshTabs() {
    // รีเฟรชแท็บที่ยังไม่ได้เช็ค
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
    
    // รีเฟรชแท็บที่เช็คแล้ว
    const checkedTab = document.getElementById('checked-tab');
    const checkedItems = checkedTab.querySelectorAll('.student-item');
    
    if (checkedItems.length === 0) {
        // ถ้าว่าง ให้แสดงข้อความว่าง
        const checkedList = checkedTab.querySelector('.student-list');
        if (checkedList) {
            checkedList.remove();
            
            // สร้างข้อความว่าง
            const emptyChecked = document.createElement('div');
            emptyChecked.className = 'empty-state';
            emptyChecked.innerHTML = `
                <span class="material-icons">schedule</span>
                <p>ยังไม่มีการเช็คชื่อในวันนี้</p>
            `;
            
            checkedTab.appendChild(emptyChecked);
        }
    }
    
    // อัพเดทจำนวนในแท็บ
    updateTabCounts();
}

/**
 * อัพเดทจำนวนการเข้าแถว
 */
function updateAttendanceCounters() {
    // อัพเดทจำนวนในแท็บ
    updateTabCounts();
}

/**
 * ฟังก์ชันเมื่อคลิกปุ่มบันทึก
 */
function saveAttendance() {
    // ตรวจสอบว่ายังมีนักเรียนที่ยังไม่ได้เช็คชื่อหรือไม่
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    
    if (uncheckedCount > 0) {
        // ถ้ายังมี ให้แสดง Modal ยืนยัน
        const modal = document.getElementById('save-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    } else {
        // ถ้าไม่มี ให้บันทึกทันที
        confirmSaveAttendance();
    }
}

/**
 * ยืนยันการบันทึกการเช็คชื่อ
 */
function confirmSaveAttendance() {
    // ในระบบจริงจะมีการส่งข้อมูลไปยัง server
    // ตัวอย่างนี้จะแสดงการแจ้งเตือนเท่านั้น
    
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
        
        // จำลองเวลาในการบันทึก
        setTimeout(() => {
            // คืนค่าเดิม
            icon.textContent = originalIcon;
            loadingButton.disabled = false;
            loadingButton.style.backgroundColor = '';
            
            // แสดงข้อความแจ้งเตือน
            showAlert('บันทึกการเช็คชื่อเรียบร้อย', 'success');
            
            // นำทางไปยังหน้ารายงาน (ตัวอย่าง)
            // window.location.href = 'reports.php?class_id=' + getCurrentClassId();
        }, 2000);
    }
}

/**
 * ดึง ID ของห้องเรียนปัจจุบัน
 * @returns {string} ID ของห้องเรียน
 */
function getCurrentClassId() {
    const classSelect = document.getElementById('class-select');
    return classSelect ? classSelect.value : '1';
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

/**
 * แสดงข้อความแจ้งเตือน (อิงจาก main.js)
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // เรียกใช้ฟังก์ชันจาก main.js (ถ้ามี)
    if (typeof window.showAlert === 'function') {
        window.showAlert(message, type);
        return;
    }
    
    // ถ้าไม่มีฟังก์ชันใน main.js ให้สร้าง alert ง่ายๆ
    alert(`${type.toUpperCase()}: ${message}`);
}
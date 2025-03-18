/**
 * teacher-home.js - สคริปต์เฉพาะสำหรับหน้าหลักของระบบ Teacher-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้าหลัก
    initHomePage();
});

/**
 * เริ่มต้นการทำงานของหน้าหลัก
 */
function initHomePage() {
    // ตั้งค่า PIN Timer
    setupPinTimer();
    
    // ตั้งค่าแท็บเริ่มต้น
    showTab('attendance');
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ในระบบจริงจะใช้ AJAX เพื่อเรียกข้อมูลของห้องเรียนใหม่
    // สำหรับตัวอย่าง เราจะนำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์
    window.location.href = 'home.php?class_id=' + classId;
}

/**
 * สร้างรหัส PIN สำหรับการเช็คชื่อ
 */
function generatePin() {
    // แสดง Modal สร้างรหัส PIN
    const modal = document.getElementById('pin-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
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
    
    // อัพเดทรหัส PIN ในหน้าหลัก (ถ้ามี)
    const activePin = document.getElementById('active-pin-code');
    if (activePin) {
        activePin.textContent = pin;
    }
    
    // รีเซ็ต Timer
    setupPinTimer();
    
    // แสดงข้อความแจ้งเตือน
    showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
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
 * สแกน QR Code
 */
function scanQRCode() {
    // แสดง Modal สแกน QR Code
    const modal = document.getElementById('qr-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // ในระบบจริงจะต้องขออนุญาตใช้งานกล้องและเริ่มสแกน QR Code
        // startQRScanner();
    }
}

/**
 * ตั้งค่า Timer สำหรับ PIN
 */
let pinTimer = null;
let remainingTime = 600; // 10 นาทีในวินาที

function setupPinTimer() {
    // เคลียร์ Timer เดิม (ถ้ามี)
    if (pinTimer) {
        clearInterval(pinTimer);
        pinTimer = null;
    }
    
    // ตั้งค่าเวลาเริ่มต้น (10 นาที)
    const pinExpireTime = document.getElementById('pin-expire-time');
    if (pinExpireTime) {
        remainingTime = parseInt(pinExpireTime.textContent) * 60;
    } else {
        remainingTime = 600;
    }
    
    // อัพเดทการแสดงผล
    updatePinTimeDisplay();
    
    // เริ่ม Timer ใหม่
    startPinTimer();
}

/**
 * เริ่ม Timer สำหรับรหัส PIN
 */
function startPinTimer() {
    // เคลียร์ Timer เดิม (ถ้ามี)
    if (pinTimer) {
        clearInterval(pinTimer);
    }
    
    // เริ่ม Timer ใหม่
    pinTimer = setInterval(function() {
        remainingTime--;
        
        if (remainingTime <= 0) {
            clearInterval(pinTimer);
            pinTimer = null;
            
            // สร้าง PIN ใหม่โดยอัตโนมัติเมื่อหมดเวลา
            generateNewPin();
        }
        
        updatePinTimeDisplay();
    }, 1000);
}

/**
 * อัพเดทการแสดงผล Timer
 */
function updatePinTimeDisplay() {
    const pinExpireTime = document.getElementById('pin-expire-time');
    
    if (pinExpireTime) {
        const minutes = Math.floor(remainingTime / 60);
        pinExpireTime.textContent = minutes;
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
 * ดูรายละเอียดนักเรียน
 * @param {number} studentId - ID ของนักเรียน
 */
function viewStudentDetail(studentId) {
    window.location.href = `student-detail.php?id=${studentId}&class_id=${getCurrentClassId()}`;
}

/**
 * แจ้งเตือนผู้ปกครอง
 * @param {number} studentId - ID ของนักเรียน
 */
function notifyParent(studentId) {
    // ในระบบจริงจะมีการนำทางไปยังหน้าส่งการแจ้งเตือนหรือแสดง Modal
    showAlert('กำลังส่งการแจ้งเตือนไปยังผู้ปกครอง...', 'info');
    
    // จำลองการส่งข้อความ
    setTimeout(() => {
        showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
    }, 2000);
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

/**
 * อัพเดทข้อมูลการเช็คชื่อในเรียลไทม์
 * สามารถใช้เทคโนโลยีเช่น WebSocket หรือ AJAX Polling
 */
function startRealtimeUpdates() {
    // ในระบบจริงจะใช้ WebSocket หรือ Server-Sent Events
    // แต่ตัวอย่างนี้จะใช้ setInterval แทน
    
    setInterval(() => {
        // เรียกข้อมูลการเช็คชื่อล่าสุดจาก server
        fetchLatestAttendanceData();
    }, 30000); // ทุก 30 วินาที
}

/**
 * ดึงข้อมูลการเช็คชื่อล่าสุดจาก server
 */
function fetchLatestAttendanceData() {
    // ในระบบจริงจะใช้ AJAX เพื่อดึงข้อมูลจาก server
    // ตัวอย่าง:
    /*
    fetch(`api/attendance.php?class_id=${getCurrentClassId()}`)
        .then(response => response.json())
        .then(data => {
            updateAttendanceStats(data.stats);
            updateStudentList(data.students);
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
        });
    */
    
    // ในตัวอย่างนี้ไม่ได้ทำอะไร
    console.log('Fetching latest attendance data...');
}

/**
 * อัพเดทสถิติการเช็คชื่อ
 * @param {Object} stats - ข้อมูลสถิติการเช็คชื่อ
 */
function updateAttendanceStats(stats) {
    // อัพเดทจำนวนนักเรียนที่มาเรียน
    const presentCount = document.querySelector('.stat-card.present .value');
    if (presentCount) {
        presentCount.textContent = stats.present_count;
    }
    
    // อัพเดทจำนวนนักเรียนที่ขาดเรียน
    const absentCount = document.querySelector('.stat-card.absent .value');
    if (absentCount) {
        absentCount.textContent = stats.absent_count;
    }
    
    // อัพเดทจำนวนนักเรียนที่ยังไม่ได้เช็ค
    const notCheckedCount = document.querySelector('.stat-card.not-checked .value');
    if (notCheckedCount) {
        notCheckedCount.textContent = stats.not_checked;
    }
}

/**
 * อัพเดทรายการเช็คชื่อนักเรียน
 * @param {Array} students - ข้อมูลการเช็คชื่อนักเรียน
 */
function updateStudentList(students) {
    const studentList = document.querySelector('#attendance-tab .student-list');
    if (!studentList) return;
    
    // ลบข้อความว่าง (ถ้ามี)
    const emptyState = studentList.querySelector('.empty-state');
    if (emptyState && students.length > 0) {
        emptyState.remove();
    }
    
    // อัพเดทหรือสร้างรายการใหม่
    if (students.length > 0) {
        // ล้างรายการเดิม
        studentList.innerHTML = '';
        
        // สร้างรายการใหม่
        students.forEach(student => {
            const studentRow = document.createElement('div');
            studentRow.className = 'student-row';
            
            studentRow.innerHTML = `
                <div class="student-info">
                    <span class="student-number">${student.number}</span>
                    <span class="student-name">${student.name}</span>
                </div>
                <div class="student-status ${student.status}">
                    ${student.status === 'present' 
                      ? '<span class="material-icons">check_circle</span> มา' 
                      : '<span class="material-icons">cancel</span> ขาด'}
                </div>
                <div class="attendance-time">${student.time}</div>
            `;
            
            studentList.appendChild(studentRow);
        });
    } else if (!emptyState) {
        // สร้างข้อความว่างถ้าไม่มีข้อมูล
        const newEmptyState = document.createElement('div');
        newEmptyState.className = 'empty-state';
        newEmptyState.innerHTML = `
            <span class="material-icons">schedule</span>
            <p>ยังไม่มีการเช็คชื่อในวันนี้</p>
        `;
        
        studentList.appendChild(newEmptyState);
    }
}
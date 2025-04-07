/**
 * teacher-home.js - สคริปต์เฉพาะสำหรับหน้าหลักของระบบน้องชูใจ AI
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้าหลัก
    initHomePage();
    
    // เริ่มการอัพเดทข้อมูลแบบเรียลไทม์
    startRealtimeUpdates();
});

/**
 * เริ่มต้นการทำงานของหน้าหลัก
 */
function initHomePage() {
    // ตั้งค่า PIN Timer
    setupPinTimer();
    
    // ตั้งค่าแท็บเริ่มต้น
    showTab('attendance');
    
    // ตั้งค่าการแสดงผู้ใช้เมื่อคลิกที่ไอคอนบัญชี
    const accountIcon = document.getElementById('account-icon');
    if (accountIcon) {
        accountIcon.addEventListener('click', function() {
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown) {
                userDropdown.classList.toggle('show');
            }
        });
    }
    
    // ปิดเมนูผู้ใช้เมื่อคลิกที่อื่น
    document.addEventListener('click', function(e) {
        if (!e.target.matches('#account-icon') && !e.target.closest('#userDropdown')) {
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown && userDropdown.classList.contains('show')) {
                userDropdown.classList.remove('show');
            }
        }
    });
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
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
    // เตรียมข้อมูลสำหรับส่งไป API
    const classId = getCurrentClassId();
    
    // ตรวจสอบว่า classId มีค่าและถูกต้อง
    if (!classId || classId === '0') {
        console.error('ไม่พบรหัสห้องเรียน');
        displayAlert('กรุณาเลือกห้องเรียน', 'error');
        return;
    }
    
    console.log('กำลังสร้าง PIN สำหรับห้องเรียน ID:', classId);
    
    // แสดงข้อความกำลังดำเนินการ
    displayAlert('กำลังสร้างรหัส PIN...', 'info');
    
    // ส่งคำขอสร้าง PIN ใหม่ไปยัง API
    fetch('api/create_pin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: classId
        })
    })
    .then(response => {
        console.log('การตอบกลับจาก API:', response);
        if (!response.ok) {
            throw new Error('การเชื่อมต่อกับเซิร์ฟเวอร์มีปัญหา (HTTP ' + response.status + ')');
        }
        return response.json();
    })
    .then(data => {
        console.log('ข้อมูลที่ได้รับจาก API:', data);
        if (data.success) {
            // อัพเดท PIN ที่แสดงใน Modal
            const pinDisplay = document.querySelector('.pin-code');
            if (pinDisplay) {
                pinDisplay.textContent = data.pin_code;
            }
            
            // อัพเดทรหัส PIN ในหน้าหลัก (ถ้ามี)
            const activePin = document.getElementById('active-pin-code');
            if (activePin) {
                activePin.textContent = data.pin_code;
            }
            
            // อัพเดทเวลาที่เหลือ
            const pinExpireTime = document.getElementById('pin-expire-time');
            if (pinExpireTime) {
                pinExpireTime.textContent = data.expire_minutes;
            }
            
            // รีเซ็ต Timer
            setupPinTimer();
            
            // แสดงข้อความแจ้งเตือนสำเร็จ
            displayAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
            
            // แสดงการ์ด PIN ถ้าซ่อนอยู่
            const activePinCard = document.querySelector('.active-pin-card');
            if (activePinCard) {
                activePinCard.style.display = 'block';
            } else {
                // ถ้ายังไม่มีการ์ด PIN ให้สร้างใหม่
                createActivePinCard(data.pin_code, data.expire_minutes);
            }
        } else {
            // แสดงข้อความเมื่อมีข้อผิดพลาด
            console.error('ข้อผิดพลาดการสร้าง PIN:', data.message);
            displayAlert(data.message || 'เกิดข้อผิดพลาดในการสร้าง PIN', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
    });
}

/**
 * สร้างการ์ด PIN ถ้ายังไม่มี
 * @param {string} pinCode - รหัส PIN
 * @param {number} expireMinutes - เวลาที่เหลือ (นาที)
 */
function createActivePinCard(pinCode, expireMinutes) {
    const container = document.querySelector('.container');
    if (!container) return;
    
    // ค้นหาตำแหน่งที่จะใส่การ์ด PIN (หลังการ์ดปุ่มทางลัด)
    const actionCards = document.querySelector('.action-cards');
    if (!actionCards) return;
    
    // สร้างการ์ด PIN
    const pinCard = document.createElement('div');
    pinCard.className = 'active-pin-card';
    pinCard.innerHTML = `
        <h3>รหัส PIN สำหรับเช็คชื่อเข้าแถว</h3>
        <div class="active-pin" id="active-pin-code">${pinCode}</div>
        <div class="pin-expire">หมดอายุในอีก <span id="pin-expire-time">${expireMinutes}</span> นาที</div>
    `;
    
    // แทรกการ์ด PIN หลังการ์ดปุ่มทางลัด
    actionCards.insertAdjacentElement('afterend', pinCard);
    
    // เริ่ม Timer ใหม่
    setupPinTimer();
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
        
        // ในระบบจริงจะใช้ JavaScript QR Scanner API
        // startQRScanner();
        
        // สำหรับตัวอย่าง (รออัพเดทในเวอร์ชันถัดไป)
        displayAlert('ระบบกำลังเรียกใช้กล้อง กรุณารอสักครู่...', 'info');
    }
}

/**
 * ตั้งค่า Timer สำหรับ PIN
 */
// กำหนดตัวแปรแบบ global scope
if (typeof window.pinTimer === 'undefined') {
    window.pinTimer = null;
    window.remainingTime = 600; // 10 นาทีในวินาที
}

function setupPinTimer() {
    // เคลียร์ Timer เดิม (ถ้ามี)
    if (window.pinTimer) {
        clearInterval(window.pinTimer);
        window.pinTimer = null;
    }
    
    // ตั้งค่าเวลาเริ่มต้นจากข้อมูลที่มี
    const pinExpireTime = document.getElementById('pin-expire-time');
    if (pinExpireTime) {
        window.remainingTime = parseInt(pinExpireTime.textContent || "10") * 60;
    } else {
        window.remainingTime = 600;
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
    if (window.pinTimer) {
        clearInterval(window.pinTimer);
    }
    
    // เริ่ม Timer ใหม่
    window.pinTimer = setInterval(function() {
        window.remainingTime--;
        
        if (window.remainingTime <= 0) {
            clearInterval(window.pinTimer);
            window.pinTimer = null;
            
            // ซ่อนการแสดง PIN ที่หมดอายุ
            const activePinCard = document.querySelector('.active-pin-card');
            if (activePinCard) {
                activePinCard.style.display = 'none';
            }
            
            // แจ้งเตือนผู้ใช้
            displayAlert('รหัส PIN หมดอายุแล้ว กรุณาสร้างใหม่', 'warning');
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
        const minutes = Math.floor(window.remainingTime / 60);
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
    // ส่งคำขอแจ้งเตือนไปยัง API
    fetch('api/notify_parent.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: studentId,
            notification_type: 'risk_alert',
            message: 'แจ้งเตือนเรื่องการเข้าแถวกิจกรรมหน้าเสาธง'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
        } else {
            displayAlert(data.message || 'เกิดข้อผิดพลาดในการส่งการแจ้งเตือน', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

/**
 * ดึง ID ของห้องเรียนปัจจุบัน
 * @returns {string} ID ของห้องเรียน
 */
function getCurrentClassId() {
    const classSelect = document.getElementById('class-select');
    return classSelect ? classSelect.value : '0';
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function displayAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        console.log(`${type.toUpperCase()}: ${message}`);
        return;
    }
    
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
        if (alertDiv.parentNode === alertContainer) {
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
 * อัพเดทข้อมูลการเช็คชื่อในเรียลไทม์
 * สามารถใช้เทคโนโลยีเช่น WebSocket หรือ AJAX Polling
 */
function startRealtimeUpdates() {
    // ในระบบจริงอาจใช้ WebSocket หรือ Server-Sent Events
    // แต่ตัวอย่างนี้จะใช้ setInterval แทน
    
    setInterval(() => {
        // เรียกข้อมูลการเช็คชื่อล่าสุดจาก server
        fetchLatestAttendanceData();
    }, 60000); // ทุก 1 นาที
}

/**
 * ดึงข้อมูลการเช็คชื่อล่าสุดจาก server
 */
function fetchLatestAttendanceData() {
    const classId = getCurrentClassId();
    if (!classId || classId === '0') return;
    
    fetch(`api/get_attendance.php?class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAttendanceStats(data.stats);
                updateStudentList(data.students);
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
        });
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
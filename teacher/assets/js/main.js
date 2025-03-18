/**
 * main.js - ไฟล์ JavaScript หลักสำหรับระบบ Teacher-Prasat
 */

// Global Variables
let pinTimer = null;
let remainingTime = 600; // 10 minutes in seconds

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initHeaderEvents();
    initModals();
    initUserDropdown();
});

/**
 * จัดการเหตุการณ์ส่วนหัว
 */
function initHeaderEvents() {
    // ปุ่มย้อนกลับ
    const backButton = document.querySelector('.header .header-icon:first-child');
    if (backButton) {
        backButton.addEventListener('click', function() {
            goBack();
        });
    }
    
    // ปุ่มเมนูเพิ่มเติม
    const moreButton = document.querySelector('.header .header-icon:last-child');
    if (moreButton) {
        moreButton.addEventListener('click', function() {
            toggleOptions();
        });
    }
}

/**
 * จัดการ Modal ต่างๆ
 */
function initModals() {
    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.classList.contains('active')) {
                    closeModal(modal.id);
                }
            });
        }
    });
}

/**
 * จัดการ Dropdown ของผู้ใช้
 */
function initUserDropdown() {
    const userIcon = document.querySelector('.header-icons .material-icons:last-child');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userIcon && userDropdown) {
        // Toggle user dropdown
        userIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (userDropdown.classList.contains('active')) {
                userDropdown.classList.remove('active');
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

/**
 * แสดง Modal
 * @param {string} modalId - ID ของ Modal ที่ต้องการแสดง
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    }
}

/**
 * ซ่อน Modal
 * @param {string} modalId - ID ของ Modal ที่ต้องการซ่อน
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // ถ้าเป็น Pin Modal ให้หยุด Timer
        if (modalId === 'pin-modal' && pinTimer) {
            clearInterval(pinTimer);
            pinTimer = null;
        }
    }
}

/**
 * แสดง Modal สร้างรหัส PIN สำหรับการเช็คชื่อ
 */
function showPinModal() {
    // Generate PIN
    generateNewPin();
    
    // Show modal
    showModal('pin-modal');
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generateNewPin() {
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    const pinDisplay = document.querySelector('.pin-code, .pin-display');
    
    if (pinDisplay) {
        pinDisplay.textContent = pin;
    }
    
    // รีเซ็ต Timer
    resetPinTimer();
    
    // อัพเดต UI
    const activePin = document.getElementById('active-pin-code');
    if (activePin) {
        activePin.textContent = pin;
    }
    
    // แสดงข้อความแจ้งเตือน
    showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
    
    return pin;
}

/**
 * รีเซ็ต Timer สำหรับรหัส PIN
 */
function resetPinTimer() {
    // หยุด Timer เดิม (ถ้ามี)
    if (pinTimer) {
        clearInterval(pinTimer);
    }
    
    // ตั้งค่าเวลาเริ่มต้น (10 นาที)
    remainingTime = 600;
    updateTimerDisplay();
    
    // เริ่ม Timer ใหม่
    pinTimer = setInterval(function() {
        remainingTime--;
        
        if (remainingTime <= 0) {
            clearInterval(pinTimer);
            pinTimer = null;
            
            // สร้าง PIN ใหม่โดยอัตโนมัติเมื่อหมดเวลา
            generateNewPin();
        }
        
        updateTimerDisplay();
    }, 1000);
}

/**
 * อัพเดตการแสดงผล Timer
 */
function updateTimerDisplay() {
    const timerElement = document.querySelector('.timer span:last-child');
    const pinExpireTime = document.getElementById('pin-expire-time');
    
    if (timerElement) {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        timerElement.textContent = `หมดอายุใน ${minutes}:${seconds < 10 ? '0' : ''}${seconds} นาที`;
    }
    
    if (pinExpireTime) {
        const minutes = Math.floor(remainingTime / 60);
        pinExpireTime.textContent = minutes;
    }
}

/**
 * แสดง Modal ยืนยันเช็คชื่อทั้งหมด
 */
function showMarkAllModal() {
    showModal('mark-all-modal');
}

/**
 * เช็คชื่อนักเรียนทั้งหมดเป็น "มาเรียน"
 */
function markAllPresent() {
    // Fetch all student items
    const studentItems = document.querySelectorAll('.student-item');
    let count = 0;
    
    studentItems.forEach(item => {
        const presentButton = item.querySelector('.action-button.present');
        const absentButton = item.querySelector('.action-button.absent');
        
        if (presentButton && absentButton) {
            // Mark as present
            presentButton.classList.add('active');
            absentButton.classList.remove('active');
            count++;
        }
    });
    
    // Update counters
    const presentCount = document.getElementById('present-count');
    const absentCount = document.getElementById('absent-count');
    const totalCount = studentItems.length;
    
    if (presentCount) presentCount.textContent = totalCount;
    if (absentCount) absentCount.textContent = '0';
    
    // Close modal and show confirmation
    closeModal('mark-all-modal');
    showAlert(`เช็คชื่อนักเรียนทั้งหมด ${count} คน สำเร็จ`, 'success');
}

/**
 * เช็คชื่อนักเรียน
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    const studentItem = button.closest('.student-item');
    const presentButton = studentItem.querySelector('.action-button.present');
    const absentButton = studentItem.querySelector('.action-button.absent');
    
    // Remove active class from both buttons
    presentButton.classList.remove('active');
    absentButton.classList.remove('active');
    
    // Add active class to clicked button
    button.classList.add('active');
    
    // Update counters
    updateAttendanceCounters();
    
    // Log for debugging
    console.log(`นักเรียน ID: ${studentId} สถานะ: ${status}`);
}

/**
 * อัพเดตจำนวนการเข้าแถว
 */
function updateAttendanceCounters() {
    const totalStudents = document.querySelectorAll('.student-item').length;
    const presentStudents = document.querySelectorAll('.action-button.present.active').length;
    const absentStudents = totalStudents - presentStudents;
    
    const presentCount = document.getElementById('present-count');
    const absentCount = document.getElementById('absent-count');
    
    if (presentCount) presentCount.textContent = presentStudents;
    if (absentCount) absentCount.textContent = absentStudents;
}

/**
 * บันทึกการเช็คชื่อ
 */
function saveAttendance() {
    // Simulate saving data
    setTimeout(() => {
        showAlert('บันทึกการเช็คชื่อเรียบร้อย', 'success');
    }, 1000);
}

/**
 * สแกน QR Code
 */
function scanQRCode() {
    // Simulate scanning 
    showAlert('กำลังเปิดกล้องเพื่อสแกน QR Code...', 'info');
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('search-input');
    const searchTerm = searchInput.value.toLowerCase();
    const studentItems = document.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        const studentName = item.getAttribute('data-name') || item.querySelector('.student-name').textContent;
        
        if (studentName.toLowerCase().includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * สลับแท็บ
 * @param {string} tabName - ชื่อแท็บ (table/graph/calendar)
 */
function switchTab(tabName) {
    const tableView = document.getElementById('table-view');
    const graphView = document.getElementById('graph-view');
    const calendarView = document.getElementById('calendar-view');
    const tabButtons = document.querySelectorAll('.tab-button');
    
    // Hide all views
    if (tableView) tableView.style.display = 'none';
    if (graphView) graphView.style.display = 'none';
    if (calendarView) calendarView.style.display = 'none';
    
    // Remove active class from all tabs
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected view and activate tab
    if (tabName === 'table' && tableView) {
        tableView.style.display = 'block';
        tabButtons[0].classList.add('active');
    } else if (tabName === 'graph' && graphView) {
        graphView.style.display = 'block';
        tabButtons[1].classList.add('active');
    } else if (tabName === 'calendar' && calendarView) {
        calendarView.style.display = 'block';
        tabButtons[2].classList.add('active');
    }
}

/**
 * ดูรายละเอียดนักเรียน
 * @param {number} studentId - ID ของนักเรียน
 */
function viewStudentDetails(studentId) {
    // ไปยังหน้ารายละเอียดนักเรียน
    window.location.href = `student-detail.php?id=${studentId}`;
}

/**
 * ส่งข้อความถึงผู้ปกครอง
 * @param {number} studentId - ID ของนักเรียน
 */
function contactParent(studentId) {
    // ไปยังหน้าส่งข้อความถึงผู้ปกครอง
    window.location.href = `contact-parent.php?student_id=${studentId}`;
}

/**
 * เปลี่ยนเดือนที่แสดงในรายงาน
 */
function changeMonth() {
    const monthSelect = document.getElementById('month-select');
    const selectedMonth = monthSelect.value;
    
    // ทำการโหลดข้อมูลเดือนใหม่
    showAlert(`โหลดข้อมูลเดือน ${selectedMonth} เรียบร้อย`, 'info');
}

/**
 * ดาวน์โหลดรายงาน
 * @param {string} reportType - ประเภทรายงาน
 */
function downloadReport(reportType) {
    showAlert(`กำลังดาวน์โหลดรายงานประเภท ${reportType}...`, 'info');
}

/**
 * พิมพ์รายงาน
 * @param {string} reportType - ประเภทรายงาน
 */
function printReport(reportType) {
    showAlert(`กำลังเตรียมพิมพ์รายงานประเภท ${reportType}...`, 'info');
}

/**
 * ย้อนกลับ
 */
function goBack() {
    window.history.back();
}

/**
 * นำทางไปยังหน้าอื่น
 * @param {string} url - URL ที่ต้องการนำทางไป
 */
function navigateTo(url) {
    window.location.href = url;
}

/**
 * แก้ไขโปรไฟล์
 */
function editProfile() {
    navigateTo('edit-profile.php');
}

/**
 * เปิดปิดการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน
 */
function toggleNotification(type) {
    showAlert(`เปลี่ยนการตั้งค่าแจ้งเตือน ${type} เรียบร้อย`, 'success');
}

/**
 * ยืนยันการออกจากระบบ
 */
function confirmLogout() {
    showModal('logout-modal');
}

/**
 * ออกจากระบบ
 */
function logout() {
    // ในการใช้งานจริงควรจะทำการ Clear Session
    window.location.href = 'logout.php';
}

/**
 * แสดงตัวเลือกเพิ่มเติม
 */
function toggleOptions() {
    // TODO: แสดงตัวเลือกเพิ่มเติม (เช่น dropdown หรือ modal)
    showAlert('ตัวเลือกเพิ่มเติม', 'info');
}

/**
 * แสดงการแจ้งเตือนผู้ปกครอง
 */
function showNotifyParentsModal() {
    showModal('notify-parents-modal');
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
function sendNotification() {
    const notifyType = document.querySelector('input[name="notification-type"]:checked').value;
    const messageText = document.getElementById('message-text').value;
    
    // ในการใช้งานจริงควรจะทำการส่งข้อมูลไปยัง server
    console.log('ประเภทการแจ้งเตือน:', notifyType);
    console.log('ข้อความ:', messageText);
    
    closeModal('notify-parents-modal');
    showAlert(`ส่งการแจ้งเตือนไปยังผู้ปกครอง ${notifyType === 'all' ? 'ทั้งหมด' : 'ที่มีปัญหา'} เรียบร้อย`, 'success');
}

/**
 * ฟังก์ชันแสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    
    // ไอคอนตามประเภท
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    else if (type === 'warning') icon = 'warning';
    else if (type === 'error') icon = 'error';
    
    alert.innerHTML = `
        <div class="alert-icon">
            <span class="material-icons">${icon}</span>
        </div>
        <div class="alert-content">
            <div class="alert-title">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
            <div class="alert-message">${message}</div>
        </div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            alertContainer.removeChild(alert);
        }, 300);
    });
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}
// ฟังก์ชั่นสำหรับ User Dropdown
function initUserDropdown() {
    const userMenu = document.querySelector('.user-menu');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenu && userDropdown) {
        // เปิด/ปิด dropdown เมื่อคลิกที่ avatar
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // ปิด dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
        
        // ป้องกันการปิดเมื่อคลิกที่ dropdown
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}

// เรียกใช้ฟังก์ชั่นเมื่อโหลดหน้าเพจ
document.addEventListener('DOMContentLoaded', function() {
    initUserDropdown();
});
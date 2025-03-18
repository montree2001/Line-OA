/**
 * teacher-home.js - JavaScript สำหรับหน้าหลักของครู
 */

// เมื่อโหลด DOM เสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าเริ่มต้น
    setupPinTimer();
    addDataLabelsToTable();
    initNotificationButtons();
    
    // ตั้งค่าการทำงานของ sidebar
    initSidebar();
    initTeacherDropdown();
});

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
    remainingTime = 600;
    
    // อัพเดทการแสดงผล
    updateTimerDisplay();
}

/**
 * อัพเดทการแสดงผล Timer
 */
function updateTimerDisplay() {
    const timerElement = document.querySelector('.timer span:last-child');
    
    if (timerElement) {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        timerElement.textContent = `หมดอายุใน ${minutes}:${seconds < 10 ? '0' : ''}${seconds} นาที`;
    }
}

/**
 * แสดง Modal สร้างรหัส PIN
 */
function showPinModal() {
    // แสดง Modal
    const modal = document.getElementById('pinModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
        
        // สร้าง PIN ใหม่
        generateNewPin();
        
        // เริ่ม Timer
        startPinTimer();
    }
}

/**
 * ซ่อน Modal
 */
function closeModal() {
    const modal = document.getElementById('pinModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // หยุด Timer
        if (pinTimer) {
            clearInterval(pinTimer);
            pinTimer = null;
        }
    }
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generateNewPin() {
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    const pinDisplay = document.getElementById('pinCode');
    
    if (pinDisplay) {
        pinDisplay.textContent = pin;
    }
    
    // บันทึก PIN ลงใน Session หรือ Database (ในการใช้งานจริง)
    savePinToServer(pin);
    
    // เริ่ม Timer ใหม่
    setupPinTimer();
    startPinTimer();
    
    // แสดงการแจ้งเตือน
    showAlert('สร้างรหัส PIN ใหม่เรียบร้อยแล้ว', 'success');
    
    return pin;
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
        
        updateTimerDisplay();
    }, 1000);
}

/**
 * บันทึกรหัส PIN ไปยังเซิร์ฟเวอร์ (จำลอง)
 */
function savePinToServer(pin) {
    // ในการใช้งานจริง นี่จะเป็นการส่งคำขอ AJAX ไปยังเซิร์ฟเวอร์
    console.log('บันทึกรหัส PIN:', pin);
    
    // ตัวอย่างการใช้ AJAX (สำหรับการใช้งานจริง)
    /*
    fetch('api/save_pin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            pin: pin,
            expires: new Date(Date.now() + remainingTime * 1000)
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('บันทึกรหัส PIN สำเร็จ:', data);
    })
    .catch(error => {
        console.error('เกิดข้อผิดพลาดในการบันทึกรหัส PIN:', error);
        showAlert('เกิดข้อผิดพลาดในการบันทึกรหัส PIN', 'error');
    });
    */
}

/**
 * เพิ่ม data-label ให้กับเซลล์ในตารางเพื่อการแสดงผลบนมือถือ
 */
function addDataLabelsToTable() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        const headerCells = table.querySelectorAll('thead th');
        const headerTexts = Array.from(headerCells).map(cell => cell.textContent.trim());
        
        const bodyCells = table.querySelectorAll('tbody td');
        bodyCells.forEach((cell, index) => {
            const headerIndex = index % headerTexts.length;
            cell.setAttribute('data-label', headerTexts[headerIndex]);
        });
    });
}

/**
 * เพิ่ม event listeners ให้กับปุ่มส่งการแจ้งเตือน
 */
function initNotificationButtons() {
    // ปุ่มในตาราง
    const notificationButtons = document.querySelectorAll('.table-action-btn.success');
    notificationButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-id') || this.parentElement.getAttribute('data-id') || 'unknown';
            sendNotification(studentId);
        });
    });
    
    // ปุ่มส่งการแจ้งเตือนทั้งหมด
    const bulkNotificationButton = document.querySelector('.card-footer .btn-primary');
    if (bulkNotificationButton) {
        bulkNotificationButton.addEventListener('click', function(e) {
            e.preventDefault();
            sendBulkNotifications();
        });
    }
}

/**
 * ส่งการแจ้งเตือนไปยังนักเรียนคนเดียว
 */
function sendNotification(studentId) {
    // ในการใช้งานจริง นี่จะเป็นการส่งคำขอ AJAX ไปยังเซิร์ฟเวอร์
    console.log('ส่งการแจ้งเตือนไปยังนักเรียน ID:', studentId);
    
    // แสดงการแจ้งเตือน
    showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
    
    // ตัวอย่างการใช้ AJAX (สำหรับการใช้งานจริง)
    /*
    fetch('api/send_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: studentId,
            message: 'แจ้งเตือนการขาดเรียน'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('ส่งการแจ้งเตือนสำเร็จ:', data);
        showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
    })
    .catch(error => {
        console.error('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน:', error);
        showAlert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน', 'error');
    });
    */
}

/**
 * ส่งการแจ้งเตือนไปยังนักเรียนทั้งหมดที่เสี่ยงตกกิจกรรม
 */
function sendBulkNotifications() {
    // แสดงการยืนยัน
    const confirmed = confirm('คุณต้องการส่งการแจ้งเตือนไปยังผู้ปกครองของนักเรียนทั้งหมดที่เสี่ยงตกกิจกรรมใช่หรือไม่?');
    
    if (confirmed) {
        // แสดงการแจ้งเตือน
        showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมดเรียบร้อยแล้ว', 'success');
        
        // ตัวอย่างการใช้ AJAX (สำหรับการใช้งานจริง)
        /*
        fetch('api/send_bulk_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                threshold: 75, // เกณฑ์การเสี่ยงตกกิจกรรม
                message: 'แจ้งเตือนการขาดเรียนสะสม'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('ส่งการแจ้งเตือนสำเร็จ:', data);
            showAlert(`ส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมด ${data.count} คนเรียบร้อยแล้ว`, 'success');
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน:', error);
            showAlert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน', 'error');
        });
        */
    }
}

/**
 * ดาวน์โหลดรายงานการเข้าแถว
 */
function downloadReport(date) {
    // ในการใช้งานจริง นี่จะเป็นการส่งคำขอไปยังเซิร์ฟเวอร์
    console.log('ดาวน์โหลดรายงานวันที่:', date);
    
    // แสดงการแจ้งเตือน
    showAlert('กำลังดาวน์โหลดรายงานการเข้าแถววันที่ ' + date, 'info');
    
    // ตัวอย่างการใช้ส่งคำขอ (สำหรับการใช้งานจริง)
    /*
    window.location.href = `reports/download.php?date=${encodeURIComponent(date)}&type=attendance`;
    */
}

/**
 * แสดงการแจ้งเตือน
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
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
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

/**
 * ตั้งค่าการทำงานของ Sidebar
 */
function initSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarClose = document.getElementById('sidebarClose');
    const overlay = document.getElementById('overlay');
    
    if (menuToggle && sidebar) {
        // เปิด sidebar
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
            if (overlay) overlay.classList.add('active');
        });
        
        // ปิด sidebar (ปุ่มปิด)
        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            });
        }
        
        // ปิด sidebar (คลิกที่ overlay)
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
        
        // ปรับตามขนาดหน้าจอ
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        });
    }
}

/**
 * ตั้งค่าการทำงานของ Teacher Dropdown
 */
function initTeacherDropdown() {
    const teacherMenuToggle = document.getElementById('teacherMenuToggle');
    const teacherDropdown = document.getElementById('teacherDropdown');
    
    if (teacherMenuToggle && teacherDropdown) {
        // เปิด/ปิด dropdown
        teacherMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            teacherDropdown.classList.toggle('active');
        });
        
        // ปิด dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', function() {
            teacherDropdown.classList.remove('active');
        });
        
        // ป้องกันการปิดเมื่อคลิกที่ dropdown
        teacherDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
}
/**
 * main.js - ไฟล์ JavaScript หลักสำหรับระบบ STUDENT-Prasat
 */

// Global Variables
let pinTimer = null;
let remainingTime = 600; // 10 minutes in seconds

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initSidebar();
    initAdminDropdown();
    initModals();
    initNotifications();
});

/**
 * จัดการ Sidebar และการแสดงผลบนอุปกรณ์มือถือ
 */
function initSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (menuToggle && sidebar && overlay) {
        // Toggle sidebar on mobile
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    }
}

/**
 * จัดการ Dropdown ของผู้ใช้
 */
function initAdminDropdown() {
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
 * จัดการการแจ้งเตือน
 */
function initNotifications() {
    const notificationIcon = document.querySelector('.header-icons .material-icons:first-child');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('จะมีการแสดงการแจ้งเตือนในอนาคต');
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
 * แสดง Modal
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
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // ถ้าเป็น Pin Modal ให้หยุด Timer
        if (modalId === 'pinModal' && pinTimer) {
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
    
    // Set timer
    resetPinTimer();
    
    // Show modal
    showModal('pinModal');
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
    
    // รีเซ็ต Timer
    resetPinTimer();
    
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
    
    if (timerElement) {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        timerElement.textContent = `หมดอายุใน ${minutes}:${seconds < 10 ? '0' : ''}${seconds} นาที`;
    }
}

/**
 * ฟังก์ชันแสดงข้อความแจ้งเตือน
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
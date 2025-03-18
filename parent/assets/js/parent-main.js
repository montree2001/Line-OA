/**
 * parent-main.js - ไฟล์ JavaScript หลักสำหรับระบบผู้ปกครอง SADD-Prasat
 */

// ตัวแปรสำหรับเก็บข้อมูลผู้ใช้ (จะใช้งานจริงควรมี API)
let userData = null;

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงาน
    initNotifications();
    initUserData();
    
    // อัพเดทเวลาทุกวินาที
    setInterval(updateClock, 1000);
    updateClock(); // เรียกครั้งแรกทันที
});

/**
 * เริ่มต้นการทำงานของระบบการแจ้งเตือน
 */
function initNotifications() {
    // ในงานจริงควรมีการเชื่อมต่อกับ WebSocket หรือ LINE API เพื่อรับการแจ้งเตือน
    console.log("กำลังเริ่มต้นระบบการแจ้งเตือน");
    
    const notificationIcon = document.querySelector('.header-icons .material-icons:first-child');
    
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            // แสดงการแจ้งเตือนทั้งหมด (ในงานจริงควรแสดงรายการแจ้งเตือน)
            alert('จะมีการแสดงรายการแจ้งเตือนในอนาคต');
        });
    }
    
    // จำลองการได้รับการแจ้งเตือนใหม่
    if (Math.random() > 0.5) {
        setTimeout(function() {
            showNotification('นายเอกชัย รักเรียน ส่งการบ้านวิชาคณิตศาสตร์แล้ว');
        }, 5000);
    }
}

/**
 * เริ่มต้นการทำงานของระบบข้อมูลผู้ใช้
 */
function initUserData() {
    // ในงานจริงควรมีการเชื่อมต่อกับ API เพื่อดึงข้อมูลผู้ใช้
    console.log("กำลังเริ่มต้นระบบข้อมูลผู้ใช้");
    
    const userIcon = document.querySelector('.header-icons .material-icons:last-child');
    
    if (userIcon) {
        userIcon.addEventListener('click', function() {
            // ไปที่หน้าโปรไฟล์
            window.location.href = 'profile.php';
        });
    }
}

/**
 * อัพเดทนาฬิกาแบบเรียลไทม์
 */
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds}`;
    
    // อัพเดทนาฬิกาถ้ามีในหน้านั้น
    const clockElement = document.getElementById('current-time');
    if (clockElement) {
        clockElement.textContent = timeString;
    }
}

/**
 * แสดงการแจ้งเตือนแบบป๊อปอัพ
 * @param {string} message - ข้อความแจ้งเตือน
 */
function showNotification(message) {
    // สร้างการแจ้งเตือน
    const notification = document.createElement('div');
    notification.className = 'popup-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <span class="material-icons">notifications</span>
            <span>${message}</span>
        </div>
        <button class="close-notification" onclick="closeNotification(this.parentNode)">&times;</button>
    `;
    
    // เพิ่มการแจ้งเตือนลงในหน้า
    document.body.appendChild(notification);
    
    // ปิดการแจ้งเตือนอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (document.body.contains(notification)) {
            closeNotification(notification);
        }
    }, 5000);
}

/**
 * ปิดการแจ้งเตือน
 * @param {HTMLElement} notification - องค์ประกอบ HTML ของการแจ้งเตือน
 */
function closeNotification(notification) {
    notification.style.animation = 'slideOut 0.3s forwards';
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.remove();
        }
    }, 300);
}

/**
 * สลับแท็บ
 * @param {string} tabName - ชื่อแท็บที่ต้องการเปิด
 */
function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab-button');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // ตั้งค่าแท็บที่เลือก
    if (tabName === 'overview') {
        tabs[0].classList.add('active');
    } else if (tabName === 'attendance') {
        tabs[1].classList.add('active');
    } else if (tabName === 'news') {
        tabs[2].classList.add('active');
    }
    
    // ในงานจริงควรมีการเชื่อมต่อกับ API เพื่อดึงข้อมูลตามแท็บที่เลือก
    console.log(`ดึงข้อมูลสำหรับแท็บ ${tabName}`);
}

/**
 * โทรหาครูประจำชั้น
 */
function callTeacher() {
    // ในงานจริงควรมีการเชื่อมต่อกับระบบโทรศัพท์หรือ LINE API
    alert('กำลังโทรหาครูประจำชั้น');
}

/**
 * ส่งข้อความหาครูประจำชั้น
 */
function messageTeacher() {
    // ในงานจริงควรมีการเชื่อมต่อกับ LINE API หรือไปยังหน้าส่งข้อความ
    window.location.href = 'messages.php?teacher=1';
}
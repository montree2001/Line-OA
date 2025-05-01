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

    // ลบโค้ดส่วนจำลองการได้รับการแจ้งเตือนใหม่
    // (ยกเลิกการแสดงแบบ popup-notification)
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

// ลบฟังก์ชัน showNotification และ closeNotification


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
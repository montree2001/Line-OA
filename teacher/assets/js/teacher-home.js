/**
 * teacher-home.js - สคริปต์เฉพาะสำหรับหน้า Home ของระบบ Teacher-Prasat
 */

// Global Variables สำหรับหน้า Home
let pinTimer = null;
let remainingTime = 600; // 10 minutes in seconds
let isDarkMode = false;

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    initHomePage();
    startPinTimer();
    checkDarkModeTime();
});

/**
 * เริ่มต้นการทำงานของหน้า Home
 */
function initHomePage() {
    // ตั้งค่าทั่วไปของหน้า Home
    updateDateTime();
    
    // ตรวจสอบเวลาเพื่ออัพเดทสถานะทุก 1 นาที
    setInterval(updateDateTime, 60000);
    
    // เพิ่มอิเวนต์คลิกที่รายการนักเรียน
    addStudentItemEvents();
    
    // ตรวจสอบการเชื่อมต่อ
    checkConnection();
}

/**
 * อัพเดทวันที่และเวลาปัจจุบัน
 */
function updateDateTime() {
    const now = new Date();
    
    // สร้างรูปแบบวันที่ภาษาไทย
    const thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    const day = now.getDate();
    const month = thaiMonths[now.getMonth()];
    const year = now.getFullYear() + 543; // แปลงเป็นปี พ.ศ.
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    
    // อัพเดทข้อมูลวันที่ในหน้า home (ถ้ามี)
    const dateElements = document.querySelectorAll('.date-info p:first-child');
    if (dateElements.length > 0) {
        dateElements.forEach(el => {
            el.textContent = `วันที่ ${day} ${month} ${year}`;
        });
    }
    
    // อัพเดทข้อมูลเวลาในหน้า home (ถ้ามี)
    const timeElements = document.querySelectorAll('.date-info p:last-child');
    if (timeElements.length > 0) {
        timeElements.forEach(el => {
            el.textContent = `เวลา ${hours}:${minutes} น.`;
        });
    }
    
    // ตรวจสอบเวลาเพื่อเปลี่ยนโหมดมืด/สว่าง
    checkDarkModeTime();
}

/**
 * เริ่ม Timer สำหรับรหัส PIN
 */
function startPinTimer() {
    // หยุด Timer เดิม (ถ้ามี)
    if (pinTimer) {
        clearInterval(pinTimer);
    }
    
    // อัพเดทเวลาที่เหลือในหน้าเว็บ
    updatePinTimeDisplay();
    
    // เริ่ม Timer ใหม่
    pinTimer = setInterval(function() {
        remainingTime--;
        
        if (remainingTime <= 0) {
            // เมื่อหมดเวลา จะสร้าง PIN ใหม่
            generateNewPin();
            remainingTime = 600; // รีเซ็ตเวลาเป็น 10 นาที
        }
        
        updatePinTimeDisplay();
    }, 1000);
}

/**
 * อัพเดทการแสดงเวลาของ PIN
 */
function updatePinTimeDisplay() {
    const pinExpireElement = document.getElementById('pin-expire-time');
    
    if (pinExpireElement) {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        // แสดงเฉพาะนาที ถ้าไม่มีวินาที
        if (seconds === 0) {
            pinExpireElement.textContent = minutes;
        } else {
            // แสดงเป็น "X นาที Y วินาที" เมื่อเหลือเวลาน้อย (น้อยกว่า 5 นาที)
            if (minutes < 5) {
                pinExpireElement.textContent = `${minutes} นาที ${seconds} วินาที`;
                // เพิ่มคลาส warning เมื่อเวลาเหลือน้อย
                if (minutes < 2) {
                    pinExpireElement.classList.add('warning');
                }
            } else {
                pinExpireElement.textContent = minutes;
            }
        }
    }
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generatePin() {
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    
    // อัพเดตค่าใน UI
    const activePin = document.getElementById('active-pin-code');
    if (activePin) {
        activePin.textContent = pin;
    }
    
    // รีเซ็ต Timer
    remainingTime = 600; // 10 นาที
    startPinTimer();
    
    // แสดง Modal (ถ้ามี)
    showPinModal();
    
    // แสดงข้อความแจ้งเตือน
    showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์ (ในระบบจริง)
    // savePinToServer(pin);
    
    return pin;
}

/**
 * แสดง Modal รหัส PIN
 */
function showPinModal() {
    const pinModal = document.getElementById('pinModal');
    const pinCode = document.getElementById('pinCode');
    
    if (pinModal && pinCode) {
        // อัพเดทรหัส PIN ในโมดัล
        const activePin = document.getElementById('active-pin-code');
        if (activePin) {
            pinCode.textContent = activePin.textContent;
        }
        
        // แสดงโมดัล
        pinModal.style.display = 'flex';
    }
}

/**
 * ปิด Modal
 */
function closeModal() {
    const pinModal = document.getElementById('pinModal');
    
    if (pinModal) {
        pinModal.style.display = 'none';
    }
}

/**
 * สร้าง PIN ใหม่ และปิด Modal
 */
function generateNewPin() {
    generatePin();
    closeModal();
}

/**
 * เพิ่มอิเวนต์คลิกที่รายการนักเรียน
 */
function addStudentItemEvents() {
    const studentItems = document.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        item.addEventListener('click', function() {
            const studentName = this.querySelector('.student-name').textContent;
            const studentStatus = this.querySelector('.student-status').textContent;
            
            // แสดงสถานะการเข้าแถวของนักเรียน
            showAlert(`${studentName} - สถานะ: ${studentStatus}`, 'info');
        });
    });
}

/**
 * สแกน QR Code
 */
function scanQRCode() {
    // ในระบบจริงจะมีการเรียกใช้ API สำหรับสแกน QR Code
    showAlert('กำลังเปิดกล้องเพื่อสแกน QR Code...', 'info');
    
    // สมมติว่ามีการเรียกใช้ API สำหรับเปิดกล้อง
    // openCamera();
}

/**
 * ตรวจสอบการเชื่อมต่อ
 */
function checkConnection() {
    if (navigator.onLine) {
        console.log('ระบบออนไลน์ - เชื่อมต่อกับเซิร์ฟเวอร์แล้ว');
    } else {
        showAlert('ไม่มีการเชื่อมต่ออินเทอร์เน็ต - ระบบจะทำงานในโหมดออฟไลน์', 'warning');
    }
    
    // เพิ่มการฟังเหตุการณ์เมื่อมีการเชื่อมต่อ/ไม่มีการเชื่อมต่อ
    window.addEventListener('online', function() {
        showAlert('เชื่อมต่ออินเทอร์เน็ตแล้ว - ระบบกำลังซิงค์ข้อมูล', 'success');
    });
    
    window.addEventListener('offline', function() {
        showAlert('ขาดการเชื่อมต่ออินเทอร์เน็ต - ระบบจะทำงานในโหมดออฟไลน์', 'warning');
    });
}

/**
 * ตรวจสอบเวลาเพื่อเปลี่ยนโหมดมืด/สว่าง
 */
function checkDarkModeTime() {
    const now = new Date();
    const hour = now.getHours();
    
    // เปลี่ยนเป็นโหมดมืดหลัง 18:00 และก่อน 06:00
    if ((hour >= 18 || hour < 6) && !isDarkMode) {
        enableDarkMode();
    } else if (hour >= 6 && hour < 18 && isDarkMode) {
        disableDarkMode();
    }
}

/**
 * เปิดใช้งานโหมดมืด
 */
function enableDarkMode() {
    document.body.classList.add('dark-mode');
    isDarkMode = true;
}

/**
 * ปิดใช้งานโหมดมืด
 */
function disableDarkMode() {
    document.body.classList.remove('dark-mode');
    isDarkMode = false;
}

/**
 * เปิดโมดัลการแจ้งเตือน
 */
function openNotificationModal() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * ดูการแจ้งเตือนทั้งหมด
 */
function viewAllNotifications() {
    // ในระบบจริงจะนำทางไปยังหน้าการแจ้งเตือนทั้งหมด
    window.location.href = 'notifications.php';
}

/**
 * เปิดโมดัลแจ้งเตือนผู้ปกครอง
 */
function openAlertParentsModal() {
    const modal = document.getElementById('alertParentsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
function sendParentNotification() {
    // ในระบบจริงจะส่งข้อมูลไปยัง server
    showAlert('ส่งข้อความแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
    closeModal('alertParentsModal');
}

// เพิ่มอีเวนต์ให้กับอไอคอนการแจ้งเตือนและบัญชี
document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.getElementById('notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', openNotificationModal);
    }
    
    // เพิ่มตัวอย่างการแจ้งเตือนและเมาส์โฮเวอร์ให้กับรายการแจ้งเตือน
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            // ในระบบจริงจะนำทางไปยังรายละเอียดการแจ้งเตือน
            const title = this.querySelector('.notification-title').textContent;
            showAlert(`เปิดการแจ้งเตือน: ${title}`, 'info');
        });
    });
});

/**
 * ฟังก์ชันแสดงข้อความแจ้งเตือน (อิงตาม main.js)
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
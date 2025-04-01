/**
 * student-checkin.js - JavaScript สำหรับหน้าเช็คชื่อเข้าแถวสำหรับนักเรียน
 */

// อัพเดทเวลาปัจจุบัน
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;

    // อัพเดทสถานะการเช็คชื่อ (ถ้าต้องการ)
    const statusElement = document.querySelector('.status-indicator');
    if (statusElement) {
        // ดึงเวลาเปิด-ปิดจาก data attribute (ถ้ามี)
        const startTime = statusElement.dataset.startTime || '07:30';
        const endTime = statusElement.dataset.endTime || '08:30';

        // แปลงเป็นนาที
        const currentMinutes = (now.getHours() * 60) + now.getMinutes();
        const startMinutes = (parseInt(startTime.split(':')[0]) * 60) + parseInt(startTime.split(':')[1]);
        const endMinutes = (parseInt(endTime.split(':')[0]) * 60) + parseInt(endTime.split(':')[1]);

        // ตรวจสอบว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
        if (currentMinutes >= startMinutes && currentMinutes <= endMinutes) {
            statusElement.className = 'status-indicator status-open';
            statusElement.textContent = 'เปิดให้เช็คชื่อ';
        } else {
            statusElement.className = 'status-indicator status-closed';
            statusElement.textContent = 'ปิดการเช็คชื่อแล้ว';
        }
    }
}

// อัพเดทเวลาทุกวินาที
setInterval(updateTime, 1000);
updateTime(); // เรียกใช้งานครั้งแรก

// ฟังก์ชันแสดงวิธีการเช็คชื่อ
function showMethod(method) {
    // ซ่อนวิธีการเช็คชื่อทั้งหมด
    document.getElementById('gps-method').style.display = 'none';
    document.getElementById('qr-method').style.display = 'none';
    document.getElementById('pin-method').style.display = 'none';
    document.getElementById('photo-method').style.display = 'none';

    // แสดงวิธีการที่เลือก
    if (method === 'gps') {
        document.getElementById('gps-method').style.display = 'block';
    } else if (method === 'qr') {
        document.getElementById('qr-method').style.display = 'block';
    } else if (method === 'pin') {
        document.getElementById('pin-method').style.display = 'block';
        setupPinInputs(); // ตั้งค่า input สำหรับรหัส PIN
    } else if (method === 'photo') {
        document.getElementById('photo-method').style.display = 'block';
    }

    // เลื่อนไปยังวิธีการที่เลือก
    document.getElementById(method + '-method').scrollIntoView({ behavior: 'smooth' });
}

// ฟังก์ชันตั้งค่า input สำหรับรหัส PIN
function setupPinInputs() {
    const pinDigits = document.querySelectorAll('.pin-digit');

    // เพิ่ม event listener สำหรับ input แต่ละตัว
    pinDigits.forEach((input, index) => {
        // ล้าง event listeners เดิม (ถ้ามี)
        const clone = input.cloneNode(true);
        input.parentNode.replaceChild(clone, input);

        // เมื่อพิมพ์ตัวเลข ให้เลื่อนไปยัง input ถัดไป
        clone.addEventListener('input', function() {
            // รับเฉพาะตัวเลข
            this.value = this.value.replace(/[^0-9]/g, '');

            if (this.value.length === 1) {
                if (index < pinDigits.length - 1) {
                    pinDigits[index + 1].focus();
                }
            }
        });

        // เมื่อกด Backspace ให้เลื่อนไปยัง input ก่อนหน้า
        clone.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value.length === 0) {
                if (index > 0) {
                    pinDigits[index - 1].focus();
                }
            }
        });
    });

    // ตั้งค่า focus ที่ input แรก
    pinDigits[0].focus();
}

// ฟังก์ชันแสดงตัวอย่างรูปภาพ
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
            document.querySelector('.upload-area').style.display = 'none';
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// ฟังก์ชันรีเซ็ตรูปภาพ
function resetImage() {
    document.getElementById('file-upload').value = '';
    document.getElementById('image-preview').style.display = 'none';
    document.querySelector('.upload-area').style.display = 'block';
}

// ฟังก์ชันย้อนกลับ
function goBack() {
    window.location.href = 'home.php';
}

// ฟังก์ชันแสดงข้อความแจ้งเตือน
function showAlert(message, type = 'success') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type}`;

    const icon = document.createElement('span');
    icon.className = 'material-icons';
    icon.textContent = type === 'success' ? 'check_circle' : 'error';

    const alertMessage = document.createElement('span');
    alertMessage.className = 'alert-message';
    alertMessage.textContent = message;

    alertContainer.appendChild(icon);
    alertContainer.appendChild(alertMessage);

    // เพิ่มปุ่มปิด
    const closeButton = document.createElement('button');
    closeButton.className = 'close-alert';
    closeButton.innerHTML = '<span class="material-icons">close</span>';
    closeButton.onclick = function() {
        alertContainer.remove();
    };

    alertContainer.appendChild(closeButton);

    // เพิ่มไปยังหน้าเว็บ
    document.querySelector('.container').prepend(alertContainer);

    // ซ่อนหลังจาก 5 วินาที
    setTimeout(() => {
        alertContainer.classList.add('fade-out');
        setTimeout(() => alertContainer.remove(), 500);
    }, 5000);
}

// ฟังก์ชันสำหรับการทำงานของ QR Code Timer
function startQRCodeTimer(expiryMinutes) {
    const timerElement = document.getElementById('qr-timer');
    if (!timerElement) return;

    let timeLeft = expiryMinutes * 60; // แปลงเป็นวินาที

    function updateTimer() {
        if (timeLeft <= 0) {
            timerElement.textContent = '0:00';
            clearInterval(timerInterval);
            return;
        }

        const minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        timerElement.textContent = `${minutes}:${seconds}`;
        timeLeft--;
    }

    updateTimer(); // เริ่มนับถอยหลังทันที
    const timerInterval = setInterval(updateTimer, 1000);
}

// เรียกใช้ฟังก์ชันเมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า PIN Inputs
    if (document.querySelector('.pin-digit')) {
        setupPinInputs();
    }

    // ตั้งค่า QR Code Timer (ถ้ามี)
    if (document.getElementById('qr-timer')) {
        startQRCodeTimer(5); // 5 นาที
    }

    // จัดการเหตุการณ์สำหรับปุ่มย้อนกลับ
    const backButton = document.querySelector('.header a[onclick="goBack()"]');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            goBack();
        });
    }
});
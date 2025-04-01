/**
 * student-checkin.js - JavaScript สำหรับหน้าเช็คชื่อนักเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // ป้องกันการแคช
    preventCache();

    // อัพเดทเวลา
    updateTime();
    setInterval(updateTime, 1000);

    // ตั้งค่าเหตุการณ์คลิกสำหรับวิธีเช็คชื่อ
    setupMethodCards();

    // กำหนดให้ปุ่มย้อนกลับนำทางไปยังหน้าหลัก
    setupBackButton();
});

// ป้องกันการแคช
function preventCache() {
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
}

// อัพเดทเวลาปัจจุบัน
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeDisplay = document.getElementById('current-time');

    if (timeDisplay) {
        timeDisplay.textContent = `${hours}:${minutes}:${seconds}`;
    }

    // อัพเดทสถานะการเช็คชื่อ (ถ้าต้องการ)
    const statusElement = document.querySelector('.status-indicator');
    if (statusElement) {
        // ดึงเวลาเปิด-ปิดจาก data attribute (ถ้ามี)
        const startTime = statusElement.dataset.startTime || '07:30';
        const endTime = statusElement.dataset.endTime || '08:30';

        // แปลงเป็นนาที
        const currentMinutes = (now.getHours() * 60) + now.getMinutes();
        const startMinutes = (parseInt(startTime.split(':')[0]) * 60) + parseInt(startTime.split(':')[1] || 0);
        const endMinutes = (parseInt(endTime.split(':')[0]) * 60) + parseInt(endTime.split(':')[1] || 0);

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

// ตั้งค่าเหตุการณ์คลิกสำหรับวิธีเช็คชื่อ
function setupMethodCards() {
    const methodCards = document.querySelectorAll('.method-card');
    if (methodCards) {
        methodCards.forEach(card => {
            card.addEventListener('click', function() {
                const methodName = this.querySelector('.method-name').textContent.toLowerCase();
                showMethod(methodName);
            });
        });
    }
}

// ตั้งค่าปุ่มย้อนกลับ
function setupBackButton() {
    const backButton = document.querySelector('.header a');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'home.php';
        });
    }
}

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
        checkGPSLocation(); // เรียกใช้ฟังก์ชันตรวจสอบตำแหน่ง GPS
    } else if (method === 'qr' || method === 'qr code') {
        document.getElementById('qr-method').style.display = 'block';
    } else if (method === 'pin' || method === 'รหัส pin') {
        document.getElementById('pin-method').style.display = 'block';
        setupPinInputs(); // ตั้งค่า input สำหรับรหัส PIN
    } else if (method === 'photo' || method === 'ถ่ายรูป') {
        document.getElementById('photo-method').style.display = 'block';
    }

    // เลื่อนไปยังวิธีการที่เลือก
    const methodElement = document.getElementById(method.replace(' ', '-') + '-method');
    if (methodElement) {
        methodElement.scrollIntoView({ behavior: 'smooth' });
    }
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
            const previewImg = document.getElementById('preview-img');
            const imagePreview = document.getElementById('image-preview');
            const uploadArea = document.querySelector('.upload-area');

            if (previewImg && imagePreview && uploadArea) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadArea.style.display = 'none';
            }
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// ฟังก์ชันรีเซ็ตรูปภาพ
function resetImage() {
    const fileUpload = document.getElementById('file-upload');
    const imagePreview = document.getElementById('image-preview');
    const uploadArea = document.querySelector('.upload-area');

    if (fileUpload && imagePreview && uploadArea) {
        fileUpload.value = '';
        imagePreview.style.display = 'none';
        uploadArea.style.display = 'block';
    }
}

// ฟังก์ชันสำหรับตรวจสอบตำแหน่ง GPS
function checkGPSLocation() {
    const statusText = document.getElementById('gps-status-text');
    const statusSubtext = document.getElementById('gps-status-subtext');
    const statusValue = document.getElementById('gps-status-value');
    const distanceValue = document.getElementById('gps-distance-value');
    const accuracyValue = document.getElementById('gps-accuracy-value');
    const submitButton = document.getElementById('gps-submit-button');

    // ดึงตำแหน่งโรงเรียนจาก data attribute ของหน้าเว็บ (ถ้ามี)
    const schoolLatElement = document.querySelector('[data-school-lat]');
    const schoolLngElement = document.querySelector('[data-school-lng]');
    const radiusElement = document.querySelector('[data-radius]');

    // ให้ค่าเริ่มต้นเป็น 0,0 หากไม่มีข้อมูล
    let schoolLat = 0;
    let schoolLng = 0;
    let radius = 100;

    // ถ้ามีข้อมูลบนหน้าเว็บ ใช้ข้อมูลจากหน้าเว็บ
    if (schoolLatElement && schoolLngElement && radiusElement) {
        schoolLat = parseFloat(schoolLatElement.dataset.schoolLat);
        schoolLng = parseFloat(schoolLngElement.dataset.schoolLng);
        radius = parseInt(radiusElement.dataset.radius);
    }

    // ตรวจสอบว่าเบราว์เซอร์รองรับ Geolocation API หรือไม่
    if (!navigator.geolocation) {
        if (statusText) statusText.textContent = "เบราว์เซอร์ของคุณไม่รองรับการตรวจสอบตำแหน่ง";
        if (statusSubtext) statusSubtext.textContent = "กรุณาใช้เบราว์เซอร์อื่น หรือเลือกวิธีการเช็คชื่ออื่น";
        if (statusValue) statusValue.textContent = "ไม่รองรับ";
        if (submitButton) submitButton.disabled = true;
        return;
    }

    // แสดงสถานะกำลังค้นหา
    if (statusText) statusText.textContent = "กำลังตรวจสอบตำแหน่ง";
    if (statusSubtext) statusSubtext.textContent = "โปรดรอสักครู่...";
    if (statusValue) statusValue.textContent = "กำลังค้นหา...";

    // ตรวจสอบตำแหน่ง
    navigator.geolocation.getCurrentPosition(
        // เมื่อได้รับตำแหน่งสำเร็จ
        function(position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            const accuracy = position.coords.accuracy;

            // คำนวณระยะห่างจากโรงเรียน
            const distance = calculateDistance(
                latitude, longitude,
                schoolLat, schoolLng
            );

            // บันทึกพิกัดลงใน hidden input
            const latInput = document.getElementById('gps-latitude');
            const lngInput = document.getElementById('gps-longitude');
            const accInput = document.getElementById('gps-accuracy');

            if (latInput) latInput.value = latitude;
            if (lngInput) lngInput.value = longitude;
            if (accInput) accInput.value = accuracy;

            // อัปเดตสถานะ
            if (statusValue) statusValue.textContent = "พร้อมใช้งาน";
            if (distanceValue) distanceValue.textContent = Math.round(distance) + " เมตร";
            if (accuracyValue) accuracyValue.textContent = "± " + Math.round(accuracy) + " เมตร";

            // ตรวจสอบว่าอยู่ในรัศมีที่กำหนดหรือไม่
            if (distance <= radius) {
                if (statusText) statusText.textContent = "ตำแหน่งถูกต้อง";
                if (statusSubtext) statusSubtext.textContent = "คุณอยู่ในรัศมีที่กำหนด";
                if (submitButton) submitButton.disabled = false;
            } else {
                if (statusText) statusText.textContent = "ตำแหน่งไม่ถูกต้อง";
                if (statusSubtext) statusSubtext.textContent = "คุณอยู่นอกรัศมีที่กำหนด (" + radius + " เมตร)";
                if (submitButton) submitButton.disabled = true;
            }
        },
        // เมื่อเกิดข้อผิดพลาด
        function(error) {
            if (statusValue) statusValue.textContent = "ไม่พร้อมใช้งาน";
            if (submitButton) submitButton.disabled = true;

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    if (statusText) statusText.textContent = "ไม่ได้รับอนุญาตให้ใช้ตำแหน่ง";
                    if (statusSubtext) statusSubtext.textContent = "กรุณาอนุญาตให้เข้าถึงตำแหน่งที่ตั้ง";
                    break;
                case error.POSITION_UNAVAILABLE:
                    if (statusText) statusText.textContent = "ไม่สามารถระบุตำแหน่งได้";
                    if (statusSubtext) statusSubtext.textContent = "โปรดตรวจสอบการเชื่อมต่อหรือลองใหม่อีกครั้ง";
                    break;
                case error.TIMEOUT:
                    if (statusText) statusText.textContent = "หมดเวลาในการค้นหาตำแหน่ง";
                    if (statusSubtext) statusSubtext.textContent = "โปรดลองใหม่อีกครั้ง";
                    break;
                case error.UNKNOWN_ERROR:
                    if (statusText) statusText.textContent = "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
                    if (statusSubtext) statusSubtext.textContent = "โปรดลองใหม่อีกครั้ง";
                    break;
            }
        },
        // ตั้งค่าตัวเลือก
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// ฟังก์ชันคำนวณระยะห่างระหว่างพิกัด GPS (หน่วยเป็นเมตร)
function calculateDistance(lat1, lon1, lat2, lon2) {
    if ((lat1 == lat2) && (lon1 == lon2)) {
        return 0;
    }

    const earthRadius = 6371000; // รัศมีของโลก (เมตร)

    const lat1Rad = toRadians(lat1);
    const lon1Rad = toRadians(lon1);
    const lat2Rad = toRadians(lat2);
    const lon2Rad = toRadians(lon2);

    const dLat = lat2Rad - lat1Rad;
    const dLon = lon2Rad - lon1Rad;

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1Rad) * Math.cos(lat2Rad) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = earthRadius * c;

    return distance;
}

function toRadians(degrees) {
    return degrees * (Math.PI / 180);
}

function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
    
    // อัพเดทสถานะการเช็คชื่อ
    const statusElement = document.querySelector('.status-indicator');
    if (now.getHours() < 8 || (now.getHours() === 8 && now.getMinutes() <= 30)) {
        statusElement.className = 'status-indicator status-open';
        statusElement.textContent = 'เปิดให้เช็คชื่อ';
    } else {
        statusElement.className = 'status-indicator status-closed';
        statusElement.textContent = 'ปิดการเช็คชื่อแล้ว';
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
    document.querySelector('.upload-card').style.display = 'none'; // ซ่อนการ์ดอัพโหลดรูปภาพ

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
        document.querySelector('.upload-card').style.display = 'block'; // แสดงการ์ดอัพโหลดรูปภาพ
    }

    // เลื่อนไปยังวิธีการที่เลือก
    document.getElementById(method + '-method').scrollIntoView({ behavior: 'smooth' });
}


// ฟังก์ชันตั้งค่า input สำหรับรหัส PIN
function setupPinInputs() {
    const pinDigits = document.querySelectorAll('.pin-digit');
    
    // เพิ่ม event listener สำหรับ input แต่ละตัว
    pinDigits.forEach((input, index) => {
        // เมื่อพิมพ์ตัวเลข ให้เลื่อนไปยัง input ถัดไป
        input.addEventListener('input', function() {
            if (this.value.length === 1) {
                if (index < pinDigits.length - 1) {
                    pinDigits[index + 1].focus();
                }
            }
        });
        
        // เมื่อกด Backspace ให้เลื่อนไปยัง input ก่อนหน้า
        input.addEventListener('keydown', function(e) {
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

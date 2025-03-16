document.addEventListener('DOMContentLoaded', function() {
    // Select all tabs and tab contents
    const tabs = document.querySelectorAll('.tabs-container .tab');
    const tabContents = document.querySelectorAll('.tab-content');

    // Add click event listener to each tab
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and tab contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            // Add active class to clicked tab and corresponding tab content
            const tabId = this.getAttribute('data-tab');
            this.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
});

// Additional functions for tab-specific interactions
function startCamera() {
    const videoElement = document.getElementById('qr-video');
    const placeholderElement = document.getElementById('camera-placeholder');

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                videoElement.srcObject = stream;
                videoElement.style.display = 'block';
                placeholderElement.style.display = 'none';
                videoElement.play();
            })
            .catch(function(error) {
                console.error("Error accessing camera:", error);
                alert('ไม่สามารถเปิดกล้องได้ กรุณาตรวจสอบสิทธิ์การใช้งานกล้อง');
            });
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุนการใช้งานกล้อง');
    }
}

function toggleFlash() {
    alert('ฟังก์ชันเปิด/ปิดแฟลชยังไม่พร้อมใช้งาน');
}

function checkAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="attendance[]"], input[name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

function uncheckAllStudents() {
    const checkboxes = document.querySelectorAll('input[name="attendance[]"], input[name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

function saveAttendance() {
    alert('บันทึกการเช็คชื่อสำเร็จ');
}

function saveManualAttendance() {
    alert('บันทึกการเช็คชื่อด้วยตนเองสำเร็จ');
}

function generatePin() {
    const pin = Math.floor(1000 + Math.random() * 9000);
    const pinElement = document.getElementById('currentPin');
    const timerElement = document.getElementById('pinTimer');
    
    if (pinElement) pinElement.textContent = pin;
    if (timerElement) timerElement.textContent = '10:00';
    
    alert(`สร้างรหัส PIN ใหม่: ${pin}`);
}

function copyPin() {
    const pin = document.getElementById('currentPin').textContent;
    navigator.clipboard.writeText(pin)
        .then(() => alert('คัดลอกรหัส PIN เรียบร้อย'))
        .catch(err => alert('ไม่สามารถคัดลอกรหัสได้'));
}

function loadClassStudents() {
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const date = document.getElementById('attendanceDate').value;
    
    alert(`โหลดรายชื่อนักเรียน: ${classLevel} / ${classRoom} วันที่ ${date}`);
}

function updateGpsSettings() {
    const latitude = document.getElementById('schoolLatitude').value;
    const longitude = document.getElementById('schoolLongitude').value;
    const radius = document.getElementById('allowedRadius').value;
    
    const radiusDisplay = document.getElementById('radius-display');
    if (radiusDisplay) radiusDisplay.textContent = radius;
    
    alert(`อัปเดตการตั้งค่า GPS: lat ${latitude}, long ${longitude}, radius ${radius} เมตร`);
}

function testGpsLocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                alert(`ตำแหน่งปัจจุบัน: 
Latitude: ${position.coords.latitude}
Longitude: ${position.coords.longitude}
ความแม่นยำ: ±${position.coords.accuracy} เมตร`);
            },
            function(error) {
                alert('ไม่สามารถระบุตำแหน่งได้: ' + error.message);
            }
        );
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุน GPS');
    }
}

function showPhotoModal(photoId) {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) photoModal.style.display = 'flex';
}

function showMapModal(mapId) {
    const mapModal = document.getElementById('mapModal');
    if (mapModal) mapModal.style.display = 'flex';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}
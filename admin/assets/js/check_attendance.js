/**
 * check_attendance.js - ระบบเช็คชื่อเข้าแถวสำหรับผู้ดูแลระบบ
 * สำหรับระบบน้องชูใจ AI ดูแลผู้เรียน
 * 
 * ฟังก์ชันสำหรับจัดการการเช็คชื่อนักเรียน มีฟีเจอร์:
 * - สแกน QR Code
 * - สร้างและจัดการรหัส PIN
 * - ตรวจสอบตำแหน่ง GPS
 * - เช็คชื่อด้วยตนเองแบบกลุ่ม
 */

document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นฟังก์ชันทั้งหมดเมื่อโหลดเอกสารเสร็จ
    initTabs();
    initPinTimer();
    initQrScanner();
    initPinCodeTab();
    initManualTab();
    initGpsTab();
    showAlerts();
});

// ==================== ฟังก์ชันทั่วไป ====================

// เริ่มต้นระบบแท็บ
function initTabs() {
    const tabs = document.querySelectorAll('.tabs-container .tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // ลบคลาส active จากแท็บทั้งหมด
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            // เพิ่มคลาส active ให้แท็บที่เลือก
            const tabId = this.getAttribute('data-tab');
            this.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
            
            // อัปเดต URL ด้วยพารามิเตอร์ tab
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
            
            // ถ้าเป็นแท็บ QR ให้เริ่มกล้องอัตโนมัติ
            if (tabId === 'qr-code') {
                initQrScanner();
            }
        });
    });
}

// แสดงแจ้งเตือน
function showAlerts() {
    const successAlert = document.getElementById('success-alert');
    const errorAlert = document.getElementById('error-alert');
    
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.add('fade-out');
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 500);
        }, 3000);
    }
}

// แสดงข้อความแบบ Toast
function showToast(message, type = 'success') {
    let toast = document.getElementById('toast-message');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-message';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ==================== ฟังก์ชันสำหรับ QR Code ====================

// เริ่มต้นตัวสแกน QR
function initQrScanner() {
    const qrReaderElement = document.getElementById('qr-reader');
    if (!qrReaderElement) return;
    
    // เคลียร์เนื้อหาเดิม
    qrReaderElement.innerHTML = '';
    
    if (typeof Html5QrcodeScanner !== 'undefined') {
        try {
            const html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { 
                    fps: 10, 
                    qrbox: 250,
                    rememberLastUsedCamera: true 
                }
            );
            
            html5QrcodeScanner.render((decodedText) => {
                processQrCode(decodedText);
            }, (errorMessage) => {
                // จัดการข้อผิดพลาดถ้าจำเป็น
                console.log(errorMessage);
            });
        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการเริ่มต้นตัวสแกน QR:", error);
            startFallbackCamera();
        }
    } else {
        // ใช้กล้องสำรองหากไม่มี Html5QrcodeScanner
        startFallbackCamera();
    }
}

// เริ่มกล้องสำรองในกรณีที่ Html5QrcodeScanner ไม่ทำงาน
function startFallbackCamera() {
    const fallbackElement = document.getElementById('camera-fallback');
    if (fallbackElement) {
        fallbackElement.style.display = 'block';
    }
}

// เริ่มใช้งานกล้อง
function startCamera() {
    const videoElement = document.getElementById('qr-video');
    const placeholderElement = document.getElementById('camera-placeholder');
    
    if (!videoElement || !placeholderElement) return;

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                videoElement.srcObject = stream;
                videoElement.style.display = 'block';
                placeholderElement.style.display = 'none';
                videoElement.play();
                
                // เริ่มสแกน QR code
                if (typeof jsQR !== 'undefined') {
                    startQrScanner(videoElement);
                }
            })
            .catch(function(error) {
                console.error("เกิดข้อผิดพลาดในการเข้าถึงกล้อง:", error);
                alert('ไม่สามารถเปิดกล้องได้ กรุณาตรวจสอบสิทธิ์การใช้งานกล้อง');
            });
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุนการใช้งานกล้อง');
    }
}

// สแกน QR code จากวิดีโอ
function startQrScanner(videoElement) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    let scanning = true;
    
    function scanQR() {
        if (videoElement.readyState === videoElement.HAVE_ENOUGH_DATA && scanning) {
            canvas.height = videoElement.videoHeight;
            canvas.width = videoElement.videoWidth;
            context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);
            
            if (code) {
                console.log("พบ QR Code:", code.data);
                processQrCode(code.data);
                // หยุดสแกนชั่วคราวหลังจากพบโค้ด
                scanning = false;
                setTimeout(() => { scanning = true; }, 3000);
            }
        }
        
        requestAnimationFrame(scanQR);
    }
    
    scanQR();
}

// ประมวลผลข้อมูล QR Code
function processQrCode(qrData) {
    console.log("ข้อมูล QR ที่ได้รับ:", qrData);
    
    try {
        // ทดลองแปลงเป็น JSON
        const qrInfo = JSON.parse(qrData);
        
        if (qrInfo.type === 'student-check-in' && qrInfo.student_id) {
            // แสดงข้อมูลนักเรียนและยืนยันการเช็คชื่อ
            confirmStudentAttendance(qrInfo);
        } else if (qrInfo.student_id) {
            // กรณีมี student_id แต่ไม่มี type
            confirmStudentAttendance({
                student_id: qrInfo.student_id,
                type: 'student-check-in'
            });
        } else {
            showScanError("รูปแบบ QR Code ไม่ถูกต้อง");
        }
    } catch (e) {
        // หากไม่ใช่ JSON ที่ถูกต้อง ตรวจสอบรูปแบบอื่น
        
        // รูปแบบ STP:STUDENT_ID:TIMESTAMP
        if (qrData.startsWith('STP:')) {
            const parts = qrData.split(':');
            if (parts.length >= 2) {
                const studentId = parts[1];
                confirmStudentAttendance({
                    student_id: studentId,
                    type: 'student-check-in'
                });
                return;
            }
        }
        
        // รูปแบบรหัสนักเรียนอย่างเดียว (ตัวเลข)
        const studentIdMatch = qrData.match(/^(\d+)$/);
        if (studentIdMatch) {
            confirmStudentAttendance({
                student_id: studentIdMatch[1],
                type: 'student-check-in'
            });
            return;
        }
        
        // กรณีหาข้อมูลนักเรียนไม่พบในทุกรูปแบบ
        showScanError("ไม่สามารถอ่าน QR Code นี้ได้ หรือรูปแบบไม่ถูกต้อง");
    }
}

// แสดงข้อผิดพลาดจากการสแกน
function showScanError(message) {
    const scanResultContainer = document.getElementById('scan-result-container');
    if (!scanResultContainer) return;
    
    scanResultContainer.innerHTML = `
        <div class="scan-result error">
            <div class="scan-icon error">
                <span class="material-icons">error</span>
            </div>
            <div class="scan-info">
                <div class="scan-message">${message}</div>
                <div class="scan-submessage">โปรดลองสแกนอีกครั้ง</div>
            </div>
        </div>
    `;
    
    const scanResultEmpty = document.getElementById('scan-result-empty');
    if (scanResultEmpty) {
        scanResultEmpty.style.display = 'none';
    }
}

// ยืนยันการเช็คชื่อนักเรียนหลังจากสแกน QR
function confirmStudentAttendance(studentInfo) {
    console.log("ยืนยันการเช็คชื่อสำหรับนักเรียนรหัส:", studentInfo.student_id);
    
    // ดึงข้อมูลนักเรียนจาก API
    fetch(`ajax/get_student.php?student_id=${studentInfo.student_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แสดงข้อมูลนักเรียน
                displayStudentInfo(data.student);
                
                // บันทึกการเช็คชื่อ
                recordAttendance(studentInfo.student_id, 'QR_Code');
            } else {
                showScanError(data.error || "ไม่พบข้อมูลนักเรียน");
            }
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน:", error);
            showScanError("เกิดข้อผิดพลาดในการดึงข้อมูล");
        });
}

// แสดงข้อมูลนักเรียนหลังจากสแกนสำเร็จ
function displayStudentInfo(student) {
    const scanResultContainer = document.getElementById('scan-result-container');
    if (!scanResultContainer) return;
    
    // จัดรูปแบบเวลาปัจจุบัน
    const now = new Date();
    const timeString = now.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
    
    scanResultContainer.innerHTML = `
        <div class="scan-result success">
            <div class="scan-icon success">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="scan-info">
                <div class="scan-title">บันทึกการเช็คชื่อสำเร็จ</div>
                <div class="student-info">
                    <div class="student-name">${student.title}${student.first_name} ${student.last_name}</div>
                    <div class="student-id">รหัส ${student.student_code}</div>
                    <div class="student-class">${student.class}</div>
                </div>
                <div class="scan-time">เวลา: ${timeString} น.</div>
            </div>
        </div>
    `;
    
    const scanResultEmpty = document.getElementById('scan-result-empty');
    if (scanResultEmpty) {
        scanResultEmpty.style.display = 'none';
    }
    
    // อัปเดตประวัติการสแกน
    updateScanHistory(student, timeString);
}

// อัปเดตประวัติการสแกน
function updateScanHistory(student, timeString) {
    const scanHistory = document.getElementById('scan-history');
    if (!scanHistory) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${timeString}</td>
        <td>${student.title}${student.first_name} ${student.last_name}</td>
        <td>${student.class}</td>
        <td>QR Code</td>
        <td><span class="status-badge success">มาเรียน</span></td>
    `;
    
    // เพิ่มแถวใหม่ไว้ด้านบนสุด
    scanHistory.prepend(newRow);
    
    // ลบแถวเก่าถ้ามีมากกว่า 10 แถว
    const rows = scanHistory.querySelectorAll('tr');
    if (rows.length > 10) {
        for (let i = 10; i < rows.length; i++) {
            rows[i].remove();
        }
    }
}

// เปิด/ปิดแฟลช
function toggleFlash() {
    const videoElement = document.getElementById('qr-video');
    if (!videoElement || !videoElement.srcObject) {
        alert('กรุณาเปิดกล้องก่อนใช้แฟลช');
        return;
    }
    
    const track = videoElement.srcObject.getVideoTracks()[0];
    if (!track) return;
    
    try {
        const capabilities = track.getCapabilities();
        if (capabilities.torch) {
            const flashState = track.getConstraints().advanced?.find(c => c.torch)?.torch;
            track.applyConstraints({
                advanced: [{ torch: !flashState }]
            })
            .then(() => {
                const flashButton = document.querySelector('.btn-secondary .material-icons');
                if (flashButton) {
                    flashButton.textContent = !flashState ? 'flash_off' : 'flash_on';
                }
            })
            .catch(err => {
                console.error("เกิดข้อผิดพลาดในการควบคุมแฟลช:", err);
                alert('ไม่สามารถควบคุมแฟลชได้');
            });
        } else {
            alert('กล้องนี้ไม่สนับสนุนการใช้งานแฟลช');
        }
    } catch (error) {
        console.error("เกิดข้อผิดพลาดในการตรวจสอบความสามารถแฟลช:", error);
        alert('ไม่สามารถตรวจสอบความสามารถแฟลชได้');
    }
}

// ==================== ฟังก์ชันสำหรับรหัส PIN ====================

// เริ่มต้นตัวนับเวลาสำหรับ PIN
function initPinTimer() {
    const pinTimerElement = document.getElementById('pinTimer');
    if (!pinTimerElement) return;
    
    const timerText = pinTimerElement.textContent || '00:00';
    const timerParts = timerText.split(':');
    let minutes = parseInt(timerParts[0], 10);
    let seconds = parseInt(timerParts[1], 10);
    
    if (isNaN(minutes) || isNaN(seconds)) return;
    
    const timerInterval = setInterval(function() {
        if (seconds <= 0) {
            if (minutes <= 0) {
                clearInterval(timerInterval);
                pinTimerElement.textContent = '00:00';
                document.getElementById('currentPin').textContent = '----';
                
                // แสดงข้อความหมดอายุ
                const pinDisplay = document.querySelector('.pin-display');
                if (pinDisplay) {
                    const expiredMessage = document.createElement('div');
                    expiredMessage.className = 'pin-expired';
                    expiredMessage.textContent = 'รหัส PIN หมดอายุแล้ว กรุณาสร้างรหัสใหม่';
                    
                    // ลบข้อความเดิมถ้ามี
                    const oldMessage = pinDisplay.querySelector('.pin-expired');
                    if (oldMessage) {
                        oldMessage.remove();
                    }
                    
                    pinDisplay.appendChild(expiredMessage);
                }
                
                return;
            }
            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }
        
        pinTimerElement.textContent = 
            (minutes < 10 ? '0' + minutes : minutes) + ':' + 
            (seconds < 10 ? '0' + seconds : seconds);
    }, 1000);
}

// สร้างรหัส PIN ใหม่
function generatePin() {
    const formData = new FormData();
    formData.append('ajax_generate_pin', '1');
    
    // เพิ่ม class_id ถ้ามี
    const classFilter = document.getElementById('pinClassFilter');
    if (classFilter && classFilter.value) {
        formData.append('class_id', classFilter.value);
    }
    
    // แสดงการโหลด
    const pinElement = document.getElementById('currentPin');
    if (pinElement) {
        pinElement.textContent = '...';
    }
    
    fetch('check_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัปเดต UI
            const pinElement = document.getElementById('currentPin');
            const timerElement = document.getElementById('pinTimer');
            
            if (pinElement) pinElement.textContent = data.pin;
            
            if (timerElement) {
                const minutes = Math.floor(data.expiration_minutes);
                timerElement.textContent = `${minutes < 10 ? '0' + minutes : minutes}:00`;
                
                // รีสตาร์ทตัวนับเวลา
                initPinTimer();
            }
            
            // ลบข้อความหมดอายุถ้ามี
            const expiredMessage = document.querySelector('.pin-expired');
            if (expiredMessage) {
                expiredMessage.remove();
            }
            
            showToast('สร้างรหัส PIN ใหม่เรียบร้อย');
            
            // รีโหลดหน้าเพื่อแสดงรายชื่อนักเรียนที่เช็คชื่อด้วย PIN นี้
            setTimeout(() => {
                window.location.href = 'check_attendance.php?tab=pin-code';
            }, 500);
        } else {
            pinElement.textContent = '----';
            alert(data.error || 'เกิดข้อผิดพลาดในการสร้างรหัส PIN');
        }
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการสร้าง PIN:", error);
        pinElement.textContent = '----';
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

// คัดลอกรหัส PIN
function copyPin() {
    const pin = document.getElementById('currentPin').textContent;
    if (pin === '----' || pin === '...') {
        alert('ไม่มีรหัส PIN ที่ใช้งานได้');
        return;
    }
    
    navigator.clipboard.writeText(pin)
        .then(() => showToast('คัดลอกรหัส PIN เรียบร้อย'))
        .catch(err => {
            console.error("เกิดข้อผิดพลาดในการคัดลอก PIN:", err);
            alert('ไม่สามารถคัดลอกรหัสได้');
        });
}

// เริ่มต้นแท็บรหัส PIN
function initPinCodeTab() {
    // จัดการกับการเลือกนักเรียนด้วย checkbox
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="attendance"]');
    if (checkboxes.length === 0) return;
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
            
            updateAttendanceSummary();
        });
    });
    
    // จัดการกับการเปลี่ยนสถานะการเช็คชื่อ
    const statusSelects = document.querySelectorAll('select[name^="attendance"][name$="[status]"]');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateAttendanceSummary();
        });
    });
    
    // อัปเดตสรุปข้อมูลเมื่อเริ่มต้น
    updateAttendanceSummary();
}

// อัปเดตสรุปข้อมูลการเช็คชื่อ
function updateAttendanceSummary() {
    const form = document.querySelector('form');
    if (!form) return;
    
    const checkboxes = form.querySelectorAll('input[type="checkbox"][name^="attendance"], input[type="checkbox"][name="manual_attendance[]"]');
    const statusSelects = form.querySelectorAll('select[name^="attendance"][name$="[status]"]');
    
    let presentCount = 0;
    let lateCount = 0;
    let absentCount = 0;
    let leaveCount = 0;
    
    // นับจำนวนนักเรียนตามสถานะ
    statusSelects.forEach(select => {
        // หาช่อง checkbox ที่อยู่ในแถวเดียวกัน
        const row = select.closest('tr');
        if (!row) return;
        
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            switch (select.value) {
                case 'present':
                    presentCount++;
                    break;
                case 'late':
                    lateCount++;
                    break;
                case 'absent':
                    absentCount++;
                    break;
                case 'leave':
                    leaveCount++;
                    break;
            }
        }
    });
    
    // อัปเดตค่าในหน้าเว็บ
    const summaryContainer = form.closest('.tab-content').querySelector('.attendance-summary');
    if (summaryContainer) {
        const statValues = summaryContainer.querySelectorAll('.attendance-stat-value');
        if (statValues.length >= 4) {
            statValues[0].textContent = presentCount;
            statValues[1].textContent = lateCount;
            statValues[2].textContent = absentCount;
            statValues[3].textContent = leaveCount;
        }
    }
}

// ==================== ฟังก์ชันสำหรับการเช็คชื่อด้วยตนเอง ====================

// เริ่มต้นแท็บเช็คชื่อด้วยตนเอง
function initManualTab() {
    const classLevelSelect = document.getElementById('classLevel');
    const classRoomSelect = document.getElementById('classRoom');
    
    if (!classLevelSelect || !classRoomSelect) return;
    
    // เมื่อเลือกระดับชั้น
    classLevelSelect.addEventListener('change', function() {
        fetchClassRooms(this.value);
    });
    
    // เลือกระดับชั้นอัตโนมัติถ้ามีพารามิเตอร์ในการเข้าชม
    const urlParams = new URLSearchParams(window.location.search);
    const levelParam = urlParams.get('level');
    const roomParam = urlParams.get('room');
    
    if (levelParam) {
        classLevelSelect.value = levelParam;
        fetchClassRooms(levelParam, roomParam);
    }
}

// ดึงห้องเรียนตามระดับชั้น
function fetchClassRooms(level, selectedRoom = null) {
    if (!level) return;
    
    const classRoomSelect = document.getElementById('classRoom');
    if (!classRoomSelect) return;
    
    // ล้างตัวเลือกห้องเรียนทั้งหมดยกเว้นตัวแรก
    while (classRoomSelect.options.length > 1) {
        classRoomSelect.remove(1);
    }
    
    // ดึงข้อมูลห้องเรียน
    fetch(`ajax/get_class_rooms.php?level=${encodeURIComponent(level)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.rooms) {
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.group_number;
                    option.textContent = `${room.group_number} - ${room.department_name}`;
                    classRoomSelect.appendChild(option);
                    
                    // เลือกห้องเรียนอัตโนมัติถ้าตรงกับพารามิเตอร์
                    if (selectedRoom && selectedRoom == room.group_number) {
                        option.selected = true;
                        setTimeout(() => loadClassStudents(), 100);
                    }
                });
            }
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน:", error);
        });
}

// โหลดรายชื่อนักเรียนตามห้องเรียนที่เลือก
function loadClassStudents() {
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const attendanceDate = document.getElementById('attendanceDate').value;
    
    if (!classLevel || !classRoom || !attendanceDate) {
        alert('กรุณาเลือกระดับชั้น ห้องเรียน และวันที่');
        return;
    }
    
    // แสดงการโหลด
    const studentList = document.getElementById('student-list');
    if (studentList) {
        studentList.innerHTML = '<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>';
    }
    
    // เตรียม URL สำหรับดึงข้อมูล
    const ajaxUrl = `ajax/get_class_students.php?level=${encodeURIComponent(classLevel)}&room=${encodeURIComponent(classRoom)}&date=${encodeURIComponent(attendanceDate)}`;
    
    console.log("กำลังดึงข้อมูลจาก:", ajaxUrl);
    
    // ดึงข้อมูลนักเรียน
    fetch(ajaxUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("ข้อมูลที่ได้รับ:", data);
            
            if (data.success && studentList) {
                if (data.students && data.students.length > 0) {
                    renderStudentList(data.students, studentList);
                    
                    // อัปเดตหัวข้อ
                    const title = document.querySelector('.manual-attendance-form h3');
                    if (title) {
                        title.textContent = `รายชื่อนักเรียนชั้น ${classLevel}/${classRoom}`;
                    }
                    
                    // อัปเดตวันที่
                    const dateElement = document.querySelector('.manual-attendance-form p');
                    if (dateElement) {
                        const displayDate = new Date(attendanceDate);
                        // แปลงปีเป็นพุทธศักราช
                        const thaiYear = displayDate.getFullYear() + 543;
                        const displayDateStr = `${displayDate.getDate()}/${displayDate.getMonth() + 1}/${thaiYear}`;
                        dateElement.textContent = `วันที่ ${displayDateStr}`;
                    }
                    
                    // เริ่มการทำงานของ event listeners สำหรับการเช็คชื่อ
                    initPinCodeTab();
                } else {
                    studentList.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียนในห้องเรียนนี้</td></tr>';
                }
            } else {
                studentList.innerHTML = `<tr><td colspan="6" class="text-center">เกิดข้อผิดพลาด: ${data.error || 'ไม่สามารถดึงข้อมูลได้'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน:", error);
            if (studentList) {
                studentList.innerHTML = `<tr><td colspan="6" class="text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}</td></tr>`;
            }
        });
}

// แสดงรายชื่อนักเรียน
function renderStudentList(students, container) {
    if (!students || students.length === 0) {
        container.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียน</td></tr>';
        return;
    }
    
    let html = '';
    students.forEach((student, index) => {
        // ตรวจสอบข้อมูลที่จำเป็น
        const studentId = student.student_id || '';
        const studentName = student.title ? 
            `${student.title}${student.first_name} ${student.last_name}` : 
            `${student.first_name} ${student.last_name}`;
        const studentCode = student.student_code || '-';
        const attendanceStatus = student.attendance_status || 'absent';
        const remarks = student.remarks || '';
        
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <input type="checkbox" name="manual_attendance[]" value="${studentId}" ${attendanceStatus !== null ? 'checked' : ''}>
                </td>
                <td>${studentName}</td>
                <td>${studentCode}</td>
                <td>
                    <select class="form-control form-control-sm" name="attendance[${studentId}][status]">
                        <option value="present" ${attendanceStatus === 'present' ? 'selected' : ''}>มาเรียน</option>
                        <option value="late" ${attendanceStatus === 'late' ? 'selected' : ''}>มาสาย</option>
                        <option value="absent" ${attendanceStatus === 'absent' ? 'selected' : ''}>ขาดเรียน</option>
                        <option value="leave" ${attendanceStatus === 'leave' ? 'selected' : ''}>ลา</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="attendance[${studentId}][remarks]" 
                           placeholder="หมายเหตุ" value="${remarks}">
                </td>
            </tr>
        `;
    });
    
    container.innerHTML = html;
    
    // อัปเดตสรุปข้อมูล
    updateAttendanceSummary();
}

// เลือกนักเรียนทั้งหมด
function checkAllStudents() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="attendance"], input[type="checkbox"][name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        const row = checkbox.closest('tr');
        if (row) row.classList.add('selected-row');
    });
    
    updateAttendanceSummary();
}

// ยกเลิกการเลือกนักเรียนทั้งหมด
function uncheckAllStudents() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="attendance"], input[type="checkbox"][name="manual_attendance[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const row = checkbox.closest('tr');
        if (row) row.classList.remove('selected-row');
    });
    
    updateAttendanceSummary();
}

// บันทึกการเช็คชื่อผ่าน AJAX
function recordAttendance(studentId, method, status = 'present', remarks = '') {
    const formData = new FormData();
    formData.append('action', 'record_attendance');
    formData.append('student_id', studentId);
    formData.append('method', method);
    formData.append('status', status);
    formData.append('remarks', remarks);
    
    return fetch('ajax/attendance_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("บันทึกการเช็คชื่อแล้ว:", data);
        return data;
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ:", error);
        throw error;
    });
}

// บันทึกการเช็คชื่อแบบกลุ่ม
function recordBulkAttendance(form) {
    if (!form) {
        alert('ไม่พบฟอร์มสำหรับบันทึกข้อมูล');
        return false;
    }
    
    // ตรวจสอบว่ามีนักเรียนที่เลือกหรือไม่
    const selectedStudents = form.querySelectorAll('input[type="checkbox"]:checked');
    if (selectedStudents.length === 0) {
        alert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
        return false;
    }
    
    // ยืนยันการบันทึก
    return confirm('ยืนยันการบันทึกการเช็คชื่อ?');
}

// ==================== ฟังก์ชันสำหรับแท็บ GPS ====================

// เริ่มต้นแท็บ GPS
function initGpsTab() {
    const mapElement = document.getElementById('map');
    if (!mapElement || typeof L === 'undefined') return;
    
    // ดึงค่าจาก input
    const latInput = document.getElementById('schoolLatitude');
    const lngInput = document.getElementById('schoolLongitude');
    const radiusInput = document.getElementById('allowedRadius');
    
    if (!latInput || !lngInput || !radiusInput) return;
    
    // ตรวจสอบค่า GPS
    const lat = parseFloat(latInput.value) || 13.7563;
    const lng = parseFloat(lngInput.value) || 100.5018;
    const radius = parseInt(radiusInput.value) || 100;
    
    // สร้างแผนที่
    let map;
    
    // ตรวจสอบว่ามีแผนที่อยู่แล้วหรือไม่
    if (mapElement._leaflet_id) {
        map = mapElement._leaflet;
        map.setView([lat, lng], 16);
    } else {
        map = L.map('map').setView([lat, lng], 16);
    }
    
    // เพิ่มชั้นข้อมูลแผนที่ OpenStreetMap ถ้ายังไม่มี
    if (!document.querySelector('.leaflet-tile-pane')) {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
    }
    
    // สร้างมาร์กเกอร์โรงเรียน
    const schoolMarker = L.marker([lat, lng]).addTo(map);
    schoolMarker.bindPopup("<b>โรงเรียน</b>").openPopup();
    
    // สร้างวงกลมแสดงขอบเขต
    const circle = L.circle([lat, lng], {
        color: 'green',
        fillColor: '#3de05c',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(map);
    
    // อัปเดตแผนที่เมื่อมีการเปลี่ยนแปลงพิกัด
    latInput.addEventListener('change', updateMapCenter);
    lngInput.addEventListener('change', updateMapCenter);
    radiusInput.addEventListener('change', updateMapRadius);
    
    function updateMapCenter() {
        const newLat = parseFloat(latInput.value) || lat;
        const newLng = parseFloat(lngInput.value) || lng;
        map.setView([newLat, newLng], 16);
        schoolMarker.setLatLng([newLat, newLng]);
        circle.setLatLng([newLat, newLng]);
    }
    
    function updateMapRadius() {
        const newRadius = parseInt(radiusInput.value) || radius;
        circle.setRadius(newRadius);
        document.getElementById('radius-display').textContent = newRadius;
    }
    
    // บันทึกอ้างอิงแผนที่
    mapElement._leaflet = map;
}

// อัปเดตการตั้งค่า GPS
function updateGpsSettings() {
    const latitude = document.getElementById('schoolLatitude').value;
    const longitude = document.getElementById('schoolLongitude').value;
    const radius = document.getElementById('allowedRadius').value;
    
    if (!latitude || !longitude || !radius) {
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
    }
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (isNaN(parseFloat(latitude)) || isNaN(parseFloat(longitude)) || isNaN(parseInt(radius))) {
        alert('กรุณากรอกข้อมูลที่ถูกต้อง');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_gps_settings');
    formData.append('latitude', latitude);
    formData.append('longitude', longitude);
    formData.append('radius', radius);
    
    // แสดงการโหลด
    const saveButton = document.querySelector('.gps-settings .btn-primary');
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="material-icons">hourglass_empty</span> กำลังบันทึก...';
    }
    
    fetch('ajax/settings_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัปเดต UI
            const radiusDisplay = document.getElementById('radius-display');
            if (radiusDisplay) radiusDisplay.textContent = radius;
            
            showToast('บันทึกการตั้งค่า GPS เรียบร้อย');
            
            // อัปเดตแผนที่ถ้ามีการแสดงอยู่
            const mapElement = document.getElementById('map');
            if (mapElement && mapElement._leaflet) {
                const map = mapElement._leaflet;
                const newLat = parseFloat(latitude);
                const newLng = parseFloat(longitude);
                const newRadius = parseInt(radius);
                
                map.setView([newLat, newLng], 16);
                
                // อัปเดตมาร์กเกอร์และวงกลม
                map.eachLayer(layer => {
                    if (layer instanceof L.Marker) {
                        layer.setLatLng([newLat, newLng]);
                    }
                    if (layer instanceof L.Circle) {
                        layer.setLatLng([newLat, newLng]);
                        layer.setRadius(newRadius);
                    }
                });
            }
        } else {
            alert(data.error || 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
        }
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการอัปเดตการตั้งค่า GPS:", error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    })
    .finally(() => {
        // คืนค่าปุ่มบันทึก
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = '<span class="material-icons">save</span> บันทึกการตั้งค่า';
        }
    });
}

// ทดสอบตำแหน่ง GPS ปัจจุบัน
function testGpsLocation() {
    if ("geolocation" in navigator) {
        // แสดงการโหลด
        const testButton = document.querySelector('.gps-settings .btn-secondary');
        if (testButton) {
            testButton.disabled = true;
            testButton.innerHTML = '<span class="material-icons">hourglass_empty</span> กำลังตรวจสอบ...';
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // คำนวณระยะห่างจากโรงเรียน
                const schoolLat = parseFloat(document.getElementById('schoolLatitude').value);
                const schoolLng = parseFloat(document.getElementById('schoolLongitude').value);
                const distance = calculateDistance(lat, lng, schoolLat, schoolLng);
                const radius = parseInt(document.getElementById('allowedRadius').value);
                
                // แสดงผลลัพธ์
                const inRangeText = distance <= radius ? 'อยู่ในพื้นที่ที่กำหนด ✓' : 'อยู่นอกพื้นที่ที่กำหนด ✗';
                
                alert(`ตำแหน่งปัจจุบัน:
ละติจูด: ${lat.toFixed(6)}
ลองจิจูด: ${lng.toFixed(6)}
ความแม่นยำ: ±${accuracy.toFixed(0)} เมตร
ระยะห่างจากโรงเรียน: ${distance.toFixed(0)} เมตร
สถานะ: ${inRangeText}`);
                
                // อัปเดตแผนที่ถ้ามีการแสดงอยู่
                updateMapWithCurrentLocation(lat, lng);
                
                // คืนค่าปุ่มทดสอบ
                if (testButton) {
                    testButton.disabled = false;
                    testButton.innerHTML = '<span class="material-icons">my_location</span> ทดสอบตำแหน่งปัจจุบัน';
                }
            },
            function(error) {
                // คืนค่าปุ่มทดสอบ
                if (testButton) {
                    testButton.disabled = false;
                    testButton.innerHTML = '<span class="material-icons">my_location</span> ทดสอบตำแหน่งปัจจุบัน';
                }
                
                alert(`ไม่สามารถระบุตำแหน่งได้: ${getLocationErrorMessage(error.code)}`);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุน GPS');
    }
}

// อัปเดตแผนที่ด้วยตำแหน่งปัจจุบัน
function updateMapWithCurrentLocation(lat, lng) {
    const mapElement = document.getElementById('map');
    if (!mapElement || !mapElement._leaflet) return;
    
    const map = mapElement._leaflet;
    const schoolLat = parseFloat(document.getElementById('schoolLatitude').value);
    const schoolLng = parseFloat(document.getElementById('schoolLongitude').value);
    
    // ลบมาร์กเกอร์ตำแหน่งปัจจุบันเดิมถ้ามี
    map.eachLayer(layer => {
        if (layer._current_location) {
            map.removeLayer(layer);
        }
        if (layer._current_line) {
            map.removeLayer(layer);
        }
    });
    
    // เพิ่มมาร์กเกอร์ตำแหน่งปัจจุบัน
    const currentMarker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: 'current-location-marker',
            html: '<span class="material-icons" style="color:#1976D2;font-size:24px;">person_pin_circle</span>',
            iconSize: [24, 24],
            iconAnchor: [12, 24]
        })
    }).addTo(map);
    currentMarker._current_location = true;
    currentMarker.bindPopup("<b>ตำแหน่งปัจจุบัน</b>").openPopup();
    
    // วาดเส้นเชื่อมระหว่างโรงเรียนและตำแหน่งปัจจุบัน
    const line = L.polyline([
        [schoolLat, schoolLng],
        [lat, lng]
    ], {
        color: '#1976D2',
        dashArray: '5, 10'
    }).addTo(map);
    line._current_line = true;
    
    // คำนวณระยะห่าง
    const distance = calculateDistance(lat, lng, schoolLat, schoolLng);
    
    // เพิ่มป้ายระยะห่าง
    const midLat = (schoolLat + lat) / 2;
    const midLng = (schoolLng + lng) / 2;
    
    const distanceLabel = L.marker([midLat, midLng], {
        icon: L.divIcon({
            className: 'distance-label',
            html: `<div style="background:white;padding:3px 8px;border-radius:4px;font-size:12px;border:1px solid #1976D2;">${Math.round(distance)} เมตร</div>`,
            iconSize: [80, 20],
            iconAnchor: [40, 10]
        })
    }).addTo(map);
    distanceLabel._current_line = true;
    
    // ปรับมุมมองให้เห็นทั้งสองจุด
    const bounds = L.latLngBounds([
        [schoolLat, schoolLng],
        [lat, lng]
    ]);
    map.fitBounds(bounds, { padding: [50, 50] });
}

// แปลรหัสข้อผิดพลาดของ geolocation
function getLocationErrorMessage(code) {
    switch(code) {
        case 1: return "ผู้ใช้ไม่อนุญาตให้เข้าถึงตำแหน่ง";
        case 2: return "ไม่สามารถรับตำแหน่งได้";
        case 3: return "หมดเวลาในการรับตำแหน่ง";
        default: return "เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ";
    }
}

// คำนวณระยะห่างระหว่างสองพิกัด (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // รัศมีของโลกในหน่วยเมตร
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// ==================== ฟังก์ชันสำหรับโมดัล ====================

// แสดงโมดัลแผนที่
function showMapModal(mapId) {
    const modal = document.getElementById('mapModal');
    if (!modal) return;
    
    // ตรวจสอบรหัสแผนที่
    const attendanceId = mapId.replace('map-', '');
    
    // แสดงการโหลด
    const modalBody = modal.querySelector('.modal-body');
    if (modalBody) {
        modalBody.innerHTML = '<div class="loading-spinner">กำลังโหลดข้อมูลแผนที่...</div>';
    }
    
    // แสดงโมดัล
    modal.style.display = 'flex';
    
    // ดึงข้อมูลตำแหน่ง
    fetch(`ajax/get_location.php?map_id=${attendanceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // เตรียมพื้นที่สำหรับแผนที่
                if (modalBody) {
                    modalBody.innerHTML = '<div id="modalMapContainer" style="width: 100%; height: 400px;"></div>';
                }
                
                // สร้างแผนที่
                initModalMap(
                    data.latitude, 
                    data.longitude, 
                    data.school_lat, 
                    data.school_lng, 
                    data.radius, 
                    data.student_name,
                    data.distance
                );
            } else {
                modalBody.innerHTML = `<div class="error-message">${data.error || 'ไม่สามารถโหลดข้อมูลแผนที่ได้'}</div>`;
            }
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูลแผนที่:", error);
            if (modalBody) {
                modalBody.innerHTML = '<div class="error-message">เกิดข้อผิดพลาดในการโหลดข้อมูลแผนที่</div>';
            }
        });
}

// สร้างแผนที่ในโมดัล
function initModalMap(studentLat, studentLng, schoolLat, schoolLng, radius, studentName, distance) {
    const mapContainer = document.getElementById('modalMapContainer');
    if (!mapContainer || typeof L === 'undefined') return;
    
    // สร้างแผนที่
    const map = L.map('modalMapContainer');
    
    // เพิ่มชั้นข้อมูลแผนที่ OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // เพิ่มมาร์กเกอร์โรงเรียน
    const schoolMarker = L.marker([schoolLat, schoolLng], {
        icon: L.divIcon({
            className: 'school-marker',
            html: '<span class="material-icons" style="color:#4CAF50;font-size:24px;">school</span>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        })
    }).addTo(map);
    schoolMarker.bindPopup("<b>โรงเรียน</b>");
    
    // เพิ่มวงกลมแสดงขอบเขต
    const circle = L.circle([schoolLat, schoolLng], {
        color: 'green',
        fillColor: '#4CAF50',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(map);
    
    // เพิ่มมาร์กเกอร์นักเรียน
    const studentMarker = L.marker([studentLat, studentLng], {
        icon: L.divIcon({
            className: 'student-marker',
            html: '<span class="material-icons" style="color:#F44336;font-size:24px;">person_pin</span>',
            iconSize: [24, 24],
            iconAnchor: [12, 24]
        })
    }).addTo(map);
    studentMarker.bindPopup(`<b>${studentName || 'นักเรียน'}</b>`).openPopup();
    
    // วาดเส้นเชื่อมระหว่างโรงเรียนและนักเรียน
    const line = L.polyline([
        [schoolLat, schoolLng],
        [studentLat, studentLng]
    ], {
        color: '#F44336',
        dashArray: '5, 10'
    }).addTo(map);
    
    // แสดงระยะห่าง
    const actualDistance = distance || calculateDistance(studentLat, studentLng, schoolLat, schoolLng);
    const isWithinRadius = actualDistance <= radius;
    const statusColor = isWithinRadius ? '#4CAF50' : '#F44336';
    
    // เพิ่มป้ายระยะห่าง
    const midLat = (schoolLat + studentLat) / 2;
    const midLng = (schoolLng + studentLng) / 2;
    
    L.marker([midLat, midLng], {
        icon: L.divIcon({
            className: 'distance-label',
            html: `<div style="background:white;padding:3px 8px;border-radius:4px;font-size:12px;border:1px solid ${statusColor};">${Math.round(actualDistance)} เมตร</div>`,
            iconSize: [80, 20],
            iconAnchor: [40, 10]
        })
    }).addTo(map);
    
    // เพิ่มป้ายสถานะ
    L.marker([studentLat, studentLng], {
        icon: L.divIcon({
            className: 'status-label',
            html: `<div style="background:${statusColor};color:white;padding:3px 8px;border-radius:4px;font-size:12px;margin-top:24px;">
                ${isWithinRadius ? 'อยู่ในพื้นที่ ✓' : 'อยู่นอกพื้นที่ ✗'}
            </div>`,
            iconSize: [80, 20],
            iconAnchor: [40, 0]
        })
    }).addTo(map);
    
    // ปรับมุมมองให้เห็นทั้งสองจุด
    const bounds = L.latLngBounds([
        [schoolLat, schoolLng],
        [studentLat, studentLng]
    ]);
    map.fitBounds(bounds, { padding: [50, 50] });
}

// แสดงโมดัลรูปภาพ
function showPhotoModal(photoId) {
    const modal = document.getElementById('photoModal');
    if (!modal) return;
    
    // ตรวจสอบรหัสรูปภาพ
    const attendanceId = photoId.replace('photo-', '');
    
    // ตรวจสอบภาพ
    const photoElement = modal.querySelector('.modal-photo img');
    if (photoElement) {
        photoElement.src = `uploads/attendance/${attendanceId}.jpg`;
        photoElement.onerror = function() {
            this.src = 'assets/img/placeholder.png';
            modal.querySelector('.modal-photo').innerHTML += '<div class="error-message">ไม่พบรูปภาพ</div>';
        };
    }
    
    // แสดงโมดัล
    modal.style.display = 'flex';
}

// ปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        
        // ล้างแผนที่ถ้าเป็นโมดัลแผนที่
        if (modalId === 'mapModal') {
            const mapContainer = document.getElementById('modalMapContainer');
            if (mapContainer) {
                mapContainer.innerHTML = '';
            }
        }
    }
}

// ==================== ฟังก์ชันสำหรับรายงาน ====================

// แสดงโมดัลดาวน์โหลดรายงาน
function downloadAttendanceReport() {
    const modal = document.getElementById('downloadReportModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        // กรณีไม่มีโมดัล ให้ redirect ไปที่หน้ารายงานโดยตรง
        window.location.href = 'reports/attendance_report.php';
    }
}

// เช็คชื่อทันที (สำหรับปุ่ม "เช็คชื่อ" ในตาราง)
function checkInNow(studentId, method = 'Manual') {
    if (!confirm('ยืนยันการเช็คชื่อนักเรียนรหัส ' + studentId + ' ?')) {
        return;
    }
    
    recordAttendance(studentId, method)
        .then(data => {
            if (data.success) {
                showToast('เช็คชื่อนักเรียนเรียบร้อยแล้ว');
                
                // รีเฟรชหน้าเว็บเพื่อแสดงข้อมูลล่าสุด
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(data.error || 'เกิดข้อผิดพลาดในการเช็คชื่อ');
            }
        })
        .catch(error => {
            alert('เกิดข้อผิดพลาดในการเช็คชื่อ');
        });
}
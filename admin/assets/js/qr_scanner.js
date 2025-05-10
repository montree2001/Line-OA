/**
 * qr_scanner.js - จัดการการสแกน QR Code สำหรับเช็คชื่อนักเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // อัปเดตเวลาปัจจุบัน
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // เริ่มต้นสแกนเนอร์ QR Code
    initQRScanner();
    
    // เชื่อมต่อปุ่มบันทึกการเช็คชื่อ
    document.getElementById('save-attendance').addEventListener('click', saveAttendance);
});

/**
 * อัปเดตเวลาปัจจุบัน
 */
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('current-time').textContent = timeString;
}

/**
 * เริ่มต้นสแกนเนอร์ QR Code
 */
function initQRScanner() {
    // หยุดการสแกนหากไม่มีกล้อง
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('เบราว์เซอร์ของคุณไม่รองรับการเข้าถึงกล้อง หรือคุณไม่ได้อนุญาตให้เข้าถึงกล้อง');
        return;
    }

    try {
        // กำหนดตัวแปรที่ใช้ร่วมกัน
        const video = document.getElementById('qr-video');
        let scanner = null;
        let cameras = [];
        let currentCameraIndex = 0;
        let isScanning = true;
        
        // สร้าง Scanner จาก Instascan
        scanner = new Instascan.Scanner({
            video: video,
            mirror: false,
            captureImage: true,
            scanPeriod: 5 // สแกนทุก 5 วินาที
        });
        
        // ตรวจจับเมื่อสแกน QR Code ได้
        scanner.addListener('scan', function(content) {
            processQRCode(content);
        });
        
        // เชื่อมต่อปุ่มสลับกล้อง
        document.getElementById('camera-switch').addEventListener('click', function() {
            if (cameras.length > 1) {
                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                scanner.start(cameras[currentCameraIndex]);
            }
        });
        
        // เชื่อมต่อปุ่มหยุดชั่วคราว/เริ่มสแกน
        const pauseButton = document.getElementById('camera-pause');
        pauseButton.addEventListener('click', function() {
            if (isScanning) {
                scanner.stop();
                pauseButton.innerHTML = '<span class="material-icons">play_arrow</span> เริ่มสแกน';
            } else {
                scanner.start(cameras[currentCameraIndex]);
                pauseButton.innerHTML = '<span class="material-icons">pause</span> หยุดชั่วคราว';
            }
            isScanning = !isScanning;
        });
        
        // รับรายการกล้องทั้งหมด
        Instascan.Camera.getCameras().then(function(availableCameras) {
            cameras = availableCameras;
            if (cameras.length > 0) {
                // ถ้ามีกล้องหลังให้ใช้กล้องหลังก่อน
                let selectedCamera = cameras[0]; // กล้องแรกโดยค่าเริ่มต้น
                
                // พยายามหากล้องหลัง (environment)
                for (let i = 0; i < cameras.length; i++) {
                    if (cameras[i].name.toLowerCase().includes('back') || 
                        cameras[i].name.toLowerCase().includes('environment')) {
                        selectedCamera = cameras[i];
                        currentCameraIndex = i;
                        break;
                    }
                }
                
                scanner.start(selectedCamera);
            } else {
                alert('ไม่พบกล้องที่สามารถใช้งานได้');
            }
        }).catch(function(error) {
            console.error('Error getting cameras:', error);
            alert('เกิดข้อผิดพลาดในการเข้าถึงกล้อง: ' + error);
        });
    } catch (error) {
        console.error('Error initializing QR scanner:', error);
        alert('เกิดข้อผิดพลาดในการเริ่มต้นสแกนเนอร์ QR Code: ' + error);
    }
}

/**
 * ประมวลผล QR Code ที่สแกนได้
 */
function processQRCode(content) {
    try {
        // แปลงข้อมูล QR Code เป็น JSON
        const qrData = JSON.parse(content);
        
        // ตรวจสอบว่าเป็น QR Code สำหรับเช็คชื่อหรือไม่
        if (qrData.type === 'student_link' && qrData.student_id && qrData.student_code) {
            // เล่นเสียงเมื่อสแกนสำเร็จ
            playBeepSound();
            
            // ดึงข้อมูลนักเรียนจากเซิร์ฟเวอร์
            fetchStudentData(qrData.student_id);
        } else {
            console.log('QR Code ไม่ถูกต้อง:', qrData);
            showError('QR Code ไม่ถูกต้องหรือไม่ใช่ QR Code สำหรับเช็คชื่อ');
        }
    } catch (error) {
        console.error('Error processing QR code:', error);
        showError('ไม่สามารถอ่าน QR Code ได้');
    }
}

/**
 * แสดงข้อผิดพลาด
 */
function showError(message) {
    // สร้าง toast notification
    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.innerHTML = `
        <span class="material-icons">error</span>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // แสดงและลบ toast หลังจาก 3 วินาที
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }, 10);
}

/**
 * เล่นเสียงเมื่อสแกนสำเร็จ
 */
function playBeepSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
    
    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.5);
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.5);
}

/**
 * ดึงข้อมูลนักเรียนจากเซิร์ฟเวอร์
 */
function fetchStudentData(studentId) {
    fetch(`ajax/get_student.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStudentData(data.student);
            } else {
                showError(data.error || 'ไม่พบข้อมูลนักเรียน');
            }
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            showError('เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน');
        });
}

/**
 * แสดงข้อมูลนักเรียน
 */
function displayStudentData(student) {
    // ซ่อนข้อความว่างเปล่าและแสดงข้อมูลนักเรียน
    document.getElementById('scan-result-empty').style.display = 'none';
    document.getElementById('scan-result').style.display = 'block';
    
    // กำหนดข้อมูลนักเรียน
    document.getElementById('student-name').textContent = `${student.title}${student.first_name} ${student.last_name}`;
    document.getElementById('student-id').textContent = `รหัสนักเรียน: ${student.student_code}`;
    document.getElementById('student-class').textContent = `ห้องเรียน: ${student.class}`;
    
    // กำหนดรูปภาพนักเรียน
    if (student.profile_picture) {
        document.getElementById('student-image').src = `../uploads/profiles/${student.profile_picture}`;
    } else {
        document.getElementById('student-image').src = 'assets/images/default-profile.png';
    }
    
    // กำหนดสถานะการเข้าแถวเริ่มต้น
    if (student.attendance && student.attendance.status) {
        document.getElementById('attendance-status').value = student.attendance.status;
        document.getElementById('attendance-remark').value = student.attendance.remarks || '';
        
        // แสดงข้อความว่าเช็คชื่อแล้ว
        document.getElementById('save-status').style.display = 'block';
        document.getElementById('save-status').innerHTML = `
            <div class="alert alert-info">
                <span class="material-icons">info</span>
                <span class="status-message">นักเรียนคนนี้เช็คชื่อไปแล้วเมื่อเวลา ${student.attendance.check_time} น. (${getStatusText(student.attendance.status)})</span>
            </div>
        `;
    } else {
        // รีเซ็ตฟอร์ม
        document.getElementById('attendance-status').value = 'present';
        document.getElementById('attendance-remark').value = '';
        document.getElementById('save-status').style.display = 'none';
    }
    
    // เก็บ ID นักเรียนไว้ใช้ตอนบันทึก
    document.getElementById('save-attendance').dataset.studentId = student.student_id;
}

/**
 * แปลงสถานะเป็นข้อความภาษาไทย
 */
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'late': return 'มาสาย';
        case 'absent': return 'ขาดเรียน';
        case 'leave': return 'ลา';
        default: return status;
    }
}

/**
 * บันทึกการเช็คชื่อ
 */
function saveAttendance() {
    const studentId = document.getElementById('save-attendance').dataset.studentId;
    const status = document.getElementById('attendance-status').value;
    const remarks = document.getElementById('attendance-remark').value;
    
    if (!studentId) {
        showError('ไม่พบข้อมูลนักเรียน');
        return;
    }
    
    // แสดงการโหลด
    document.getElementById('save-attendance').disabled = true;
    document.getElementById('save-attendance').innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...';
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('action', 'record_attendance');
    formData.append('student_id', studentId);
    formData.append('method', 'QR_Code');
    formData.append('status', status);
    formData.append('remarks', remarks);
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('ajax/attendance_actions.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แสดงข้อความสำเร็จ
                document.getElementById('save-status').style.display = 'block';
                document.getElementById('save-status').innerHTML = `
                    <div class="alert alert-success">
                        <span class="material-icons">check_circle</span>
                        <span class="status-message">บันทึกการเช็คชื่อเรียบร้อยแล้ว (${getStatusText(status)})</span>
                    </div>
                `;
                
                // เพิ่มข้อมูลในประวัติการสแกน
                addToScanHistory(data.student);
            } else {
                showError(data.error || 'ไม่สามารถบันทึกการเช็คชื่อได้');
                document.getElementById('save-status').style.display = 'block';
                document.getElementById('save-status').innerHTML = `
                    <div class="alert alert-danger">
                        <span class="material-icons">error</span>
                        <span class="status-message">${data.error || 'ไม่สามารถบันทึกการเช็คชื่อได้'}</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error saving attendance:', error);
            showError('เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ');
            document.getElementById('save-status').style.display = 'block';
            document.getElementById('save-status').innerHTML = `
                <div class="alert alert-danger">
                    <span class="material-icons">error</span>
                    <span class="status-message">เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ</span>
                </div>
            `;
        })
        .finally(() => {
            // รีเซ็ตปุ่ม
            document.getElementById('save-attendance').disabled = false;
            document.getElementById('save-attendance').innerHTML = '<span class="material-icons">save</span> บันทึกการเช็คชื่อ';
        });
}

/**
 * เพิ่มข้อมูลในประวัติการสแกน
 */
function addToScanHistory(student) {
    const history = document.getElementById('scan-history');
    const now = new Date();
    const timeString = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    
    // สร้างแถวใหม่
    const newRow = document.createElement('tr');
    
    // กำหนดคลาสตามสถานะ
    let statusClass = '';
    switch (student.attendance_status) {
        case 'present': statusClass = 'text-success'; break;
        case 'late': statusClass = 'text-warning'; break;
        case 'absent': statusClass = 'text-danger'; break;
        case 'leave': statusClass = 'text-info'; break;
    }
    
    // กำหนดเนื้อหาแถว
    newRow.innerHTML = `
        <td>${timeString}</td>
        <td>${student.student_code}</td>
        <td>${student.title}${student.first_name} ${student.last_name}</td>
        <td class="${statusClass}">${getStatusText(student.attendance_status)}</td>
    `;
    
    // เพิ่มแถวใหม่ไว้ข้างบนสุด
    if (history.firstChild) {
        history.insertBefore(newRow, history.firstChild);
    } else {
        history.appendChild(newRow);
    }
    
    // จำกัดจำนวนแถวไว้ที่ 10 แถว
    while (history.children.length > 10) {
        history.removeChild(history.lastChild);
    }
}
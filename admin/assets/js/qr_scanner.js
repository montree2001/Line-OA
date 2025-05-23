/**
 * qr_scanner.js - JavaScript สำหรับจัดการ QR Code Scanner (แก้ไขแล้ว)
 * ระบบน้องชูใจ AI ดูแลผู้เรียน
 */

class QRAttendanceScanner {
    constructor() {
        this.html5QrCode = null;
        this.isScanning = false;
        this.currentCameraId = null;
        this.cameras = [];
        this.scanCount = 0;
        this.successCount = 0;
        this.errorCount = 0;
        this.lastScanTime = 0;
        this.scanCooldown = 2000; // 2 seconds cooldown between scans
        this.attendanceData = [];
        
        this.init();
    }
    
    async init() {
        // โหลด html5-qrcode library
        await this.loadQRCodeLibrary();
        
        // เชื่อมต่อ event listeners
        this.bindEvents();
        
        // โหลดข้อมูลการเช็คชื่อวันนี้
        this.loadTodayAttendance();
        
        // ตรวจสอบกล้องที่มีอยู่
        this.checkAvailableCameras();
    }
    
    async loadQRCodeLibrary() {
        return new Promise((resolve, reject) => {
            if (typeof Html5Qrcode !== 'undefined') {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load QR Code library'));
            document.head.appendChild(script);
        });
    }
    
    bindEvents() {
        document.getElementById('startScanBtn').addEventListener('click', () => this.startScanning());
        document.getElementById('stopScanBtn').addEventListener('click', () => this.stopScanning());
        document.getElementById('switchCameraBtn').addEventListener('click', () => this.switchCamera());
        document.getElementById('fullscreenBtn').addEventListener('click', () => this.openFullscreen());
        
        // เปลี่ยนวันที่
        document.getElementById('scanDate').addEventListener('change', () => {
            this.loadTodayAttendance();
        });
    }
    
    async checkAvailableCameras() {
        try {
            this.cameras = await Html5Qrcode.getCameras();
            
            if (this.cameras && this.cameras.length > 0) {
                this.currentCameraId = this.cameras[0].id;
                
                // แสดงปุ่มเปลี่ยนกล้องถ้ามีกล้องมากกว่า 1 ตัว
                if (this.cameras.length > 1) {
                    document.getElementById('switchCameraBtn').style.display = 'inline-flex';
                }
            } else {
                this.showAlert('ไม่พบกล้องที่สามารถใช้งานได้', 'error');
            }
        } catch (error) {
            console.error('Error checking cameras:', error);
            this.showAlert('ไม่สามารถเข้าถึงกล้องได้', 'error');
        }
    }
    
    async startScanning() {
        if (this.isScanning) return;
        
        try {
            if (!this.currentCameraId) {
                this.showAlert('ไม่พบกล้องที่สามารถใช้งานได้', 'error');
                return;
            }
            
            this.html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: QR_SCANNER_CONFIG?.scannerSettings?.fps || 10,
                qrbox: QR_SCANNER_CONFIG?.scannerSettings?.qrbox || { width: 250, height: 250 },
                aspectRatio: QR_SCANNER_CONFIG?.scannerSettings?.aspectRatio || 1.0
            };
            
            await this.html5QrCode.start(
                this.currentCameraId,
                config,
                (decodedText, decodedResult) => this.onScanSuccess(decodedText, decodedResult),
                (errorMessage) => this.onScanError(errorMessage)
            );
            
            this.isScanning = true;
            this.updateScannerUI();
            this.updateScannerStatus('กำลังแสกน...', 'scanning');
            
        } catch (error) {
            console.error('Error starting scanner:', error);
            this.showAlert('ไม่สามารถเริ่มการแสกนได้: ' + error.message, 'error');
        }
    }
    
    async stopScanning() {
        if (!this.isScanning || !this.html5QrCode) return;
        
        try {
            await this.html5QrCode.stop();
            this.html5QrCode.clear();
            this.html5QrCode = null;
            this.isScanning = false;
            this.updateScannerUI();
            this.updateScannerStatus('พร้อมแสกน', 'ready');
        } catch (error) {
            console.error('Error stopping scanner:', error);
        }
    }
    
    async switchCamera() {
        if (!this.isScanning || this.cameras.length <= 1) return;
        
        const currentIndex = this.cameras.findIndex(camera => camera.id === this.currentCameraId);
        const nextIndex = (currentIndex + 1) % this.cameras.length;
        this.currentCameraId = this.cameras[nextIndex].id;
        
        // รีสตาร์ทการแสกนด้วยกล้องใหม่
        await this.stopScanning();
        setTimeout(() => this.startScanning(), 500);
    }
    
    openFullscreen() {
        // เปิดหน้าใหม่ในโหมดเต็มจอ
        const fullscreenUrl = 'qr_fullscreen.php';
        const fullscreenWindow = window.open(
            fullscreenUrl, 
            'QRFullscreen', 
            'fullscreen=yes,scrollbars=no,resizable=no,status=no,toolbar=no,menubar=no,location=no'
        );
        
        // ถ้าไม่สามารถเปิด fullscreen ได้ ให้เปิดเป็นหน้าต่างใหม่ขนาดใหญ่
        if (!fullscreenWindow || fullscreenWindow.closed) {
            window.open(
                fullscreenUrl,
                'QRFullscreen',
                'width=' + screen.width + ',height=' + screen.height + ',top=0,left=0,scrollbars=no,resizable=yes'
            );
        }
    }
    
    onScanSuccess(decodedText, decodedResult) {
        const currentTime = Date.now();
        
        // ตรวจสอบ cooldown
        if (currentTime - this.lastScanTime < this.scanCooldown) {
            return;
        }
        
        this.lastScanTime = currentTime;
        this.scanCount++;
        this.updateScanInfo();
        
        try {
            console.log('Raw QR Code data:', decodedText); // เพิ่ม log เพื่อ debug
            
            // แปลง QR Code data
            const qrData = JSON.parse(decodedText);
            console.log('Parsed QR Data:', qrData); // เพิ่ม log เพื่อ debug
            
            // ปรับปรุงการตรวจสอบให้รองรับได้หลายรูปแบบ
            const validTypes = ['student_attendance', 'student_link']; // รองรับทั้งสองแบบ
            
            if (validTypes.includes(qrData.type) && qrData.student_id) {
                // ตรวจสอบเวลาหมดอายุ (ถ้ามี)
                if (qrData.expire_time) {
                    const expireTime = new Date(qrData.expire_time);
                    const currentTime = new Date();
                    
                    if (currentTime > expireTime) {
                        throw new Error('QR Code หมดอายุแล้ว');
                    }
                }
                
                this.processStudentQR(qrData);
            } else {
                throw new Error('QR Code ไม่ถูกต้อง - ไม่ใช่ QR Code สำหรับเช็คชื่อ');
            }
            
        } catch (error) {
            console.error('QR Parse Error:', error);
            console.error('QR Data that failed:', decodedText); // เพิ่ม log เพื่อ debug
            this.errorCount++;
            this.updateScanInfo();
            
            // แสดงข้อความ error ที่ชัดเจนขึ้น
            let errorMessage = 'QR Code ไม่ถูกต้อง';
            if (error.message.includes('JSON')) {
                errorMessage = 'รูปแบบ QR Code ไม่ถูกต้อง - ไม่ใช่ JSON';
            } else if (error.message.includes('หมดอายุ')) {
                errorMessage = 'QR Code หมดอายุแล้ว กรุณาสร้าง QR Code ใหม่';
            } else if (error.message.includes('ไม่ใช่ QR Code สำหรับเช็คชื่อ')) {
                errorMessage = 'QR Code นี้ไม่ใช่สำหรับเช็คชื่อ';
            }
            
            this.showAlert(errorMessage, 'error');
        }
    }
    
    onScanError(errorMessage) {
        // ไม่แสดง error ของการแสกนที่ไม่สำเร็จ (เป็นเรื่องปกติ)
    }
    
    async processStudentQR(qrData) {
        try {
            this.updateScannerStatus('กำลังประมวลผล...', 'processing');
            
            // ตรวจสอบว่าเช็คชื่อแล้วหรือยัง
            const alreadyChecked = this.checkedStudents.find(s => {
                const qrDataObj = typeof qrData === 'string' ? JSON.parse(qrData) : qrData;
                return s.student_id === qrDataObj.student_id;
            });
            
            if (alreadyChecked) {
                this.showAlert(`${alreadyChecked.name} เช็คชื่อแล้ว`, 'warning');
                this.updateScannerStatus('เช็คชื่อแล้ว', 'warning');
                setTimeout(() => {
                    if (this.isScanning) {
                        this.updateScannerStatus('กำลังแสกน...', 'scanning');
                    }
                }, 3000);
                return;
            }
            
            // ส่งข้อมูล QR Code ไปประมวลผล
            const response = await fetch('ajax/process_qr_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'scan_qr',
                    qr_data: typeof qrData === 'string' ? qrData : JSON.stringify(qrData)
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // บันทึกการเช็คชื่อทันที
                await this.recordAttendance(result.student);
                
            } else {
                throw new Error(result.error || 'ไม่สามารถประมวลผล QR Code ได้');
            }
            
        } catch (error) {
            console.error('Process QR Error:', error);
            this.showAlert('เกิดข้อผิดพลาด: ' + error.message, 'error');
            this.updateScannerStatus('เกิดข้อผิดพลาด', 'error');
        }
        
        // รีเซ็ตสถานะหลังจาก 3 วินาที
        setTimeout(() => {
            if (this.isScanning) {
                this.updateScannerStatus('กำลังแสกน...', 'scanning');
            }
        }, 3000);
    }
    
    showStudentModal(student, qrData) {
        const modal = document.getElementById('studentModal');
        const content = document.getElementById('studentModalContent');
        
        // สร้างข้อมูลนักเรียนในโมดัล
        content.innerHTML = `
            <div class="student-info-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="student-avatar">
                        ${student.title ? student.title.charAt(0) : student.first_name.charAt(0)}
                    </div>
                    <div class="ms-3">
                        <div class="student-name">${student.title || ''}${student.first_name} ${student.last_name}</div>
                        <div class="student-code">รหัสนักเรียน: ${student.student_code}</div>
                    </div>
                </div>
                
                <div class="student-details">
                    <div class="detail-item">
                        <div class="detail-label">ห้องเรียน</div>
                        <div class="detail-value">${student.class || 'ไม่ระบุ'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">สถานะการเช็คชื่อปัจจุบัน</div>
                        <div class="detail-value">${this.getAttendanceStatusText(student.attendance?.status)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">เวลาที่เช็คชื่อล่าสุด</div>
                        <div class="detail-value">${student.attendance?.check_time || 'ยังไม่เช็คชื่อ'}</div>
                    </div>
                    ${qrData.expire_time ? `
                    <div class="detail-item">
                        <div class="detail-label">QR Code หมดอายุ</div>
                        <div class="detail-value">${new Date(qrData.expire_time).toLocaleString('th-TH')}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        // เก็บข้อมูลไว้ใช้ตอนยืนยัน
        modal.studentData = student;
        modal.qrData = qrData;
        
        // แสดงโมดัล
        modal.style.display = 'flex';
    }
    
    async confirmAttendance() {
        const modal = document.getElementById('studentModal');
        const student = modal.studentData;
        const qrData = modal.qrData;
        const scanDate = document.getElementById('scanDate').value;
        const attendanceStatus = document.getElementById('attendanceStatus').value;
        
        try {
            const formData = new FormData();
            formData.append('action', 'record_attendance');
            formData.append('student_id', student.student_id);
            formData.append('method', 'QR_Code');
            formData.append('status', attendanceStatus);
            formData.append('date', scanDate);
            
            // เพิ่มข้อมูล QR Code token เพื่อตรวจสอบ
            if (qrData.token) {
                formData.append('qr_token', qrData.token);
            }
            
            const response = await fetch('ajax/attendance_actions.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert(`เช็คชื่อสำเร็จ: ${student.title || ''}${student.first_name} ${student.last_name}`, 'success');
                this.showLatestStudent(result.student);
                this.loadTodayAttendance(); // รีโหลดข้อมูล
                this.closeStudentModal();
            } else {
                throw new Error(result.error || 'เกิดข้อผิดพลาดในการบันทึก');
            }
            
        } catch (error) {
            console.error('Confirm Attendance Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message, 'error');
        }
    }
    
    showLatestStudent(student) {
        const card = document.getElementById('latestStudentCard');
        const content = document.getElementById('latestStudentInfo');
        
        content.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="student-avatar me-3">
                    ${student.title ? student.title.charAt(0) : student.first_name.charAt(0)}
                </div>
                <div class="flex-grow-1">
                    <div class="student-name">${student.title || ''}${student.first_name} ${student.last_name}</div>
                    <div class="student-details">
                        <div class="d-flex justify-content-between">
                            <span>รหัส: ${student.student_code}</span>
                            <span>ห้อง: ${student.level}/${student.group_number}</span>
                            <span class="status-badge ${student.attendance_status}">${this.getAttendanceStatusText(student.attendance_status)}</span>
                            <span>เวลา: ${student.check_time}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        card.style.display = 'block';
    }
    
    async loadTodayAttendance() {
        try {
            const scanDate = document.getElementById('scanDate').value;
            const response = await fetch(`ajax/get_today_attendance.php?date=${scanDate}`);
            const result = await response.json();
            
            if (result.success) {
                this.attendanceData = result.attendance;
                this.updateAttendanceTable();
                this.updateAttendanceStats();
            } else {
                console.error('Load attendance error:', result.error);
            }
            
        } catch (error) {
            console.error('Load Today Attendance Error:', error);
        }
    }
    
    updateAttendanceTable() {
        const tbody = document.getElementById('attendanceTableBody');
        
        if (this.attendanceData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">ยังไม่มีการเช็คชื่อวันนี้</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.attendanceData.map((attendance, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${attendance.student_code}</td>
                <td>${attendance.title || ''}${attendance.first_name} ${attendance.last_name}</td>
                <td>${attendance.level}/${attendance.group_number}</td>
                <td><span class="status-badge ${attendance.attendance_status}">${this.getAttendanceStatusText(attendance.attendance_status)}</span></td>
                <td>${attendance.check_time}</td>
                <td><span class="method-badge ${attendance.check_method.toLowerCase()}">${this.getMethodText(attendance.check_method)}</span></td>
                <td>
                    <span>${attendance.remarks || ''}</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="qrScanner.editAttendance(${attendance.attendance_id})">
                        <span class="material-icons" style="font-size: 16px;">edit</span>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    updateAttendanceStats() {
        const stats = {
            present: 0,
            late: 0,
            absent: 0,
            leave: 0
        };
        
        this.attendanceData.forEach(attendance => {
            if (stats.hasOwnProperty(attendance.attendance_status)) {
                stats[attendance.attendance_status]++;
            }
        });
        
        document.getElementById('presentTotal').textContent = stats.present;
        document.getElementById('lateTotal').textContent = stats.late;
        document.getElementById('absentTotal').textContent = stats.absent;
        document.getElementById('leaveTotal').textContent = stats.leave;
        document.getElementById('attendanceCount').textContent = `${this.attendanceData.length} คน`;
    }
    
    async editAttendance(attendanceId) {
        try {
            // หาข้อมูลการเช็คชื่อ
            const attendance = this.attendanceData.find(a => a.attendance_id == attendanceId);
            if (!attendance) return;
            
            // เติมข้อมูลในฟอร์มแก้ไข
            document.getElementById('editAttendanceId').value = attendanceId;
            document.getElementById('editStudentId').value = attendance.student_id;
            document.getElementById('editStatus').value = attendance.attendance_status;
            document.getElementById('editRemarks').value = attendance.remarks || '';
            
            // แสดงโมดัลแก้ไข
            document.getElementById('editAttendanceModal').style.display = 'flex';
            
        } catch (error) {
            console.error('Edit Attendance Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการแก้ไข', 'error');
        }
    }
    
    async saveEditAttendance() {
        try {
            const formData = new FormData(document.getElementById('editAttendanceForm'));
            formData.append('action', 'update_attendance');
            
            const response = await fetch('ajax/attendance_actions.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert('แก้ไขการเช็คชื่อสำเร็จ', 'success');
                this.loadTodayAttendance(); // รีโหลดข้อมูล
                this.closeEditModal();
            } else {
                throw new Error(result.error || 'เกิดข้อผิดพลาดในการแก้ไข');
            }
            
        } catch (error) {
            console.error('Save Edit Attendance Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message, 'error');
        }
    }
    
    updateScannerUI() {
        const scannerCard = document.getElementById('scannerCard');
        const startBtn = document.getElementById('startScanBtn');
        const stopBtn = document.getElementById('stopScanBtn');
        const switchBtn = document.getElementById('switchCameraBtn');
        
        if (this.isScanning) {
            scannerCard.style.display = 'block';
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-flex';
            switchBtn.style.display = this.cameras.length > 1 ? 'inline-flex' : 'none';
        } else {
            scannerCard.style.display = 'none';
            startBtn.style.display = 'inline-flex';
            stopBtn.style.display = 'none';
            switchBtn.style.display = 'none';
        }
    }
    
    updateScannerStatus(message, type) {
        const statusElement = document.getElementById('scannerStatus');
        statusElement.textContent = message;
        statusElement.className = `status-${type}`;
    }
    
    updateScanInfo() {
        document.getElementById('scanCount').textContent = this.scanCount;
        document.getElementById('successCount').textContent = this.successCount;
        document.getElementById('errorCount').textContent = this.errorCount;
    }
    
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div class="alert alert-${type}" id="${alertId}">
                <span class="material-icons">${this.getAlertIcon(type)}</span>
                <div class="alert-message">${message}</div>
                <button class="alert-close" onclick="document.getElementById('${alertId}').remove()">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // ลบ alert อัตโนมัติหลังจาก 5 วินาที
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }
    
    getAlertIcon(type) {
        const icons = {
            'success': 'check_circle',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };
        return icons[type] || 'info';
    }
    
    getAttendanceStatusText(status) {
        const statusTexts = {
            'present': 'มาเรียน',
            'late': 'มาสาย',
            'absent': 'ขาดเรียน',
            'leave': 'ลา'
        };
        return statusTexts[status] || 'ไม่ระบุ';
    }
    
    getMethodText(method) {
        const methodTexts = {
            'GPS': 'GPS',
            'QR_Code': 'QR Code',
            'PIN': 'PIN',
            'Manual': 'Manual'
        };
        return methodTexts[method] || method;
    }
    
    playSuccessSound() {
        // เล่นเสียงแจ้งเตือนเมื่อแสกนสำเร็จ (ถ้าเบราว์เซอร์รองรับ)
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhEkqKt6mXmKOJmH+bYhcRO3K1rpeVpZqGg5mMiX2RiYiGgJWYhpqIgpCJjI2RioOPhYiIhpOEo4ychJiGhZeNiYyMjYqPk4WOhZGOhJqRh4yRjYyPgpOOjZOEi5uWipWHj5iKiZqSioiOhpOIjJqMgJqGi5iDhJWKjJuKhZuMio2NhJGPhoyKjpmQkJuRjJqGi5yVhJOHk5aMjY2Pi4yNhoyGjpKKhpqOjJaFk5CGk4yJi5OGj5OKgoyNhYiOhJCKhpyJjYyPj42Oy4mOhYuJgJ6Oi5WLh5qIlYyDlYWNlIGWlYyZhJaEh5yKhpiQh5CPg5GMhZ+Lh4iGhpWLi5OPhpOGjJiKjp2IjYyMg4KIhJaPgpOLhZuOj46Mio6OhZ+FhZKNjYmShJeOhJOLgZaIjJKIgJONhpCGiZyPjYyGhJ2HjI2Gi4ySjpOSipCKhpOOhJuMjIyKjJOJjYyFkZeCjZyMkZKGipGNhJORgZOGhJOGk5aCpgA==');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // เงียบๆ ถ้าไม่สามารถเล่นเสียงได้
            });
        } catch (error) {
            // ไม่ทำอะไรถ้าไม่สามารถเล่นเสียงได้
        }
    }
    
    closeStudentModal() {
        document.getElementById('studentModal').style.display = 'none';
    }
    
    closeEditModal() {
        document.getElementById('editAttendanceModal').style.display = 'none';
    }
}

// Global functions
function closeStudentModal() {
    if (window.qrScanner) {
        window.qrScanner.closeStudentModal();
    }
}

function confirmAttendance() {
    if (window.qrScanner) {
        window.qrScanner.confirmAttendance();
    }
}

function closeEditModal() {
    if (window.qrScanner) {
        window.qrScanner.closeEditModal();
    }
}

function saveEditAttendance() {
    if (window.qrScanner) {
        window.qrScanner.saveEditAttendance();
    }
}

// เริ่มต้นระบบเมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    window.qrScanner = new QRAttendanceScanner();
});
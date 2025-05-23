/**
 * qr_fullscreen.js - JavaScript สำหรับจัดการ QR Code Scanner แบบเต็มจอ
 */

class QRFullscreenScanner {
    constructor() {
        this.html5QrCode = null;
        this.isScanning = false;
        this.currentCameraId = null;
        this.cameras = [];
        this.scanCount = 0;
        this.checkedStudents = [];
        this.lastScanTime = 0;
        this.scanCooldown = 3000; // 3 seconds cooldown
        
        this.init();
    }
    
    async init() {
        // อัปเดตเวลาปัจจุบัน
        this.updateDateTime();
        setInterval(() => this.updateDateTime(), 1000);
        
        // เชื่อมต่อ event listeners
        this.bindEvents();
        
        // ตรวจสอบกล้องที่มีอยู่
        await this.checkAvailableCameras();
        
        // เริ่มการแสกนอัตโนมัติ
        setTimeout(() => this.startScanning(), 1000);
    }
    
    bindEvents() {
        document.getElementById('startScanBtn').addEventListener('click', () => this.startScanning());
        document.getElementById('stopScanBtn').addEventListener('click', () => this.stopScanning());
        
        // ปิดหน้าต่างเมื่อกด ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    }
    
    async checkAvailableCameras() {
        try {
            this.cameras = await Html5Qrcode.getCameras();
            
            if (this.cameras && this.cameras.length > 0) {
                // หากมีกล้องหลายตัว ให้เลือกกล้องหลัง (rear camera) ก่อน
                const rearCamera = this.cameras.find(camera => 
                    camera.label.toLowerCase().includes('back') || 
                    camera.label.toLowerCase().includes('rear') ||
                    camera.label.toLowerCase().includes('environment')
                );
                
                this.currentCameraId = rearCamera ? rearCamera.id : this.cameras[0].id;
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
            
            this.html5QrCode = new Html5Qrcode("qr-reader-fullscreen");
            
            const config = {
                fps: QR_FULLSCREEN_CONFIG.scannerSettings.fps,
                qrbox: QR_FULLSCREEN_CONFIG.scannerSettings.qrbox,
                aspectRatio: QR_FULLSCREEN_CONFIG.scannerSettings.aspectRatio
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
            this.updateScannerStatus('หยุดการแสกน', 'ready');
        } catch (error) {
            console.error('Error stopping scanner:', error);
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
            // แปลง QR Code data
            const qrData = JSON.parse(decodedText);
            
            if (qrData.type === 'student_attendance' && qrData.student_id) {
                this.processStudentQR(qrData);
            } else {
                throw new Error('QR Code ไม่ถูกต้อง');
            }
            
        } catch (error) {
            console.error('QR Parse Error:', error);
            this.showAlert('QR Code ไม่ถูกต้องหรือไม่ใช่ QR Code สำหรับเช็คชื่อ', 'error');
        }
    }
    
    onScanError(errorMessage) {
        // ไม่แสดง error ของการแสกนที่ไม่สำเร็จ
    }
    
    async processStudentQR(qrData) {
        try {
            this.updateScannerStatus('กำลังประมวลผล...', 'processing');
            
            // ตรวจสอบว่าเช็คชื่อแล้วหรือยัง
            const alreadyChecked = this.checkedStudents.find(s => s.student_id === qrData.student_id);
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
            
            // ดึงข้อมูลนักเรียน
            const response = await fetch(`ajax/get_student.php?student_id=${qrData.student_id}`);
            const result = await response.json();
            
            if (result.success) {
                // บันทึกการเช็คชื่อ
                await this.recordAttendance(result.student);
                
            } else {
                throw new Error(result.error || 'ไม่พบข้อมูลนักเรียน');
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
    
    async recordAttendance(student) {
        try {
            const formData = new FormData();
            formData.append('action', 'record_attendance');
            formData.append('student_id', student.student_id);
            formData.append('method', 'QR_Code');
            formData.append('status', 'present');
            formData.append('date', new Date().toISOString().split('T')[0]);
            
            const response = await fetch('ajax/attendance_actions.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                const studentName = `${student.title || ''}${student.first_name} ${student.last_name}`;
                
                // เพิ่มในรายการที่เช็คแล้ว
                this.checkedStudents.unshift({
                    student_id: student.student_id,
                    name: studentName,
                    student_code: student.student_code,
                    class: student.class,
                    time: new Date().toLocaleTimeString('th-TH', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    })
                });
                
                // อัปเดตการแสดงผล
                this.updateStudentDisplay();
                this.showSuccessModal(studentName, student);
                this.updateScannerStatus('เช็คชื่อสำเร็จ', 'success');
                
                // เล่นเสียงแจ้งเตือน
                this.playSuccessSound();
                
            } else {
                throw new Error(result.error || 'เกิดข้อผิดพลาดในการบันทึก');
            }
            
        } catch (error) {
            console.error('Record Attendance Error:', error);
            this.showAlert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message, 'error');
        }
    }
    
    updateStudentDisplay() {
        const studentInfoList = document.getElementById('studentInfoList');
        const rows = studentInfoList.querySelectorAll('.info-item-row');
        
        // อัปเดตรายการนักเรียนในแต่ละแถว
        rows.forEach((row, index) => {
            const checkbox = row.querySelector('.checkbox-container .material-icons');
            const nameField = row.querySelector('.student-name-field');
            
            if (index < this.checkedStudents.length) {
                const student = this.checkedStudents[index];
                
                // อัปเดต checkbox
                checkbox.textContent = 'check_box';
                checkbox.parentElement.classList.add('checked');
                
                // อัปเดตชื่อ
                nameField.innerHTML = `
                    <div class="student-name-active">
                        ${student.name} (${student.student_code})
                        <br><small>${student.class} - ${student.time}</small>
                    </div>
                `;
            } else {
                // รีเซ็ตแถวว่าง
                checkbox.textContent = 'check_box_outline_blank';
                checkbox.parentElement.classList.remove('checked');
                nameField.innerHTML = '<span class="placeholder-text"></span>';
            }
        });
    }
    
    showSuccessModal(studentName, student) {
        const modal = document.getElementById('successModal');
        const message = document.getElementById('successMessage');
        const info = document.getElementById('successStudentInfo');
        
        message.textContent = `เช็คชื่อสำเร็จ!`;
        info.innerHTML = `
            <div style="font-size: 20px; margin-bottom: 10px;">${studentName}</div>
            <div>รหัส: ${student.student_code}</div>
            <div>ห้อง: ${student.class}</div>
            <div>เวลา: ${new Date().toLocaleTimeString('th-TH')}</div>
        `;
        
        modal.style.display = 'flex';
        
        // ปิดโมดัลอัตโนมัติหลังจาก 3 วินาที
        setTimeout(() => {
            modal.style.display = 'none';
        }, 3000);
    }
    
    updateDateTime() {
        const now = new Date();
        
        const dateOptions = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        
        const timeOptions = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        
        document.getElementById('currentDate').textContent = 
            now.toLocaleDateString('th-TH', dateOptions);
        document.getElementById('currentTime').textContent = 
            now.toLocaleTimeString('th-TH', timeOptions);
    }
    
    updateScannerUI() {
        const startBtn = document.getElementById('startScanBtn');
        const stopBtn = document.getElementById('stopScanBtn');
        
        if (this.isScanning) {
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-flex';
        } else {
            startBtn.style.display = 'inline-flex';
            stopBtn.style.display = 'none';
        }
    }
    
    updateScannerStatus(message, type) {
        const statusElement = document.getElementById('scannerStatusFullscreen');
        statusElement.textContent = message;
        statusElement.className = `status-value ${type}`;
    }
    
    updateScanInfo() {
        document.getElementById('scanCountFullscreen').textContent = this.scanCount;
    }
    
    showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div class="alert alert-${type}" id="${alertId}">
                <span class="material-icons">${this.getAlertIcon(type)}</span>
                <span>${message}</span>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // ลบ alert อัตโนมัติหลังจาก 5 วินาที
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                alertElement.style.opacity = '0';
                setTimeout(() => alertElement.remove(), 300);
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
    
    playSuccessSound() {
        try {
            // เสียงแจ้งเตือนเมื่อเช็คชื่อสำเร็จ
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhEkqKt6mXmKOJmH+bYhcRO3K1rpeVpZqGg5mMiX2RiYiGgJWYhpqIgpCJjI2RioOPhYiIhpOEo4ychJiGhZeNiYyMjYqPk4WOhZGOhJqRh4yRjYyPgpOOjZOEi5uWipWHj5iKiZqSioiOhpOIjJqMgJqGi5iDhJWKjJuKhZuMio2NhJGPhoyKjpmQkJuRjJqGi5yVhJOHk5aMjY2Pi4yNhoyGjpKKhpqOjJaFk5CGk4yJi5OGj5OKgoyNhYiOhJCKhpyJjYyPj42Oy4mOhYuJgJ6Oi5WLh5qIlYyDlYWNlIGWlYyZhJaEh5yKhpiQh5CPg5GMhZ+Lh4iGhpWLi5OPhpOGjJiKjp2IjYyMg4KIhJaPgpOLhZuOj46Mio6OhZ+FhZKNjYmShJeOhJOLgZaIjJKIgJONhpCGiZyPjYyGhJ2HjI2Gi4ySjpOSipCKhpOOhJuMjIyKjJOJjYyFkZeCjZyMkZKGipGNhJORgZOGhJOGk5aCpgA==');
            audio.volume = 0.5;
            audio.play().catch(() => {
                // เงียบๆ ถ้าไม่สามารถเล่นเสียงได้
            });
        } catch (error) {
            // ไม่ทำอะไรถ้าไม่สามารถเล่นเสียงได้
        }
    }
}

// เริ่มต้นระบบเมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามี Html5Qrcode library หรือไม่
    if (typeof Html5Qrcode === 'undefined') {
        console.error('Html5Qrcode library not loaded');
        return;
    }
    
    window.qrFullscreenScanner = new QRFullscreenScanner();
});

// ป้องกันการปิดหน้าต่างโดยไม่ตั้งใจ
window.addEventListener('beforeunload', function(e) {
    if (window.qrFullscreenScanner && window.qrFullscreenScanner.isScanning) {
        const confirmationMessage = 'คุณแน่ใจหรือไม่ว่าต้องการปิดหน้าต่าง? การแสกนจะหยุดทำงาน';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});
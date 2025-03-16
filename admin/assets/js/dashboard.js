/**
 * dashboard.js - JavaScript สำหรับหน้าแดชบอร์ด
 */

// ตัวแปรสำหรับการนับเวลาถอยหลังของรหัส PIN
let pinTimer = null;
let remainingTime = 600; // 10 นาที (600 วินาที)

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นกราฟ (ถ้ามี)
    initCharts();
    
    // ตั้งค่า event listeners
    setupEventListeners();
});

/**
 * ตั้งค่า event listeners สำหรับหน้าแดชบอร์ด
 */
function setupEventListeners() {
    // ปุ่มดาวน์โหลดรายงาน
    const downloadBtn = document.querySelector('.action-button');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', downloadReport);
    }
    
    // ปุ่มสร้างรหัส PIN
    const pinBtn = document.querySelector('.quick-action-btn.pin');
    if (pinBtn) {
        pinBtn.addEventListener('click', showPinModal);
    }
    
    // ปุ่มทางลัดอื่นๆ
    const qrBtn = document.querySelector('.quick-action-btn.qr');
    if (qrBtn) {
        qrBtn.addEventListener('click', () => {
            window.location.href = 'check_attendance.php?tab=qr-code';
        });
    }
    
    const checkBtn = document.querySelector('.quick-action-btn.check');
    if (checkBtn) {
        checkBtn.addEventListener('click', () => {
            window.location.href = 'check_attendance.php?tab=manual';
        });
    }
    
    const alertBtn = document.querySelector('.quick-action-btn.alert');
    if (alertBtn) {
        alertBtn.addEventListener('click', () => {
            window.location.href = 'send_notification.php';
        });
    }
}

/**
 * แสดงโมดัลสร้างรหัส PIN
 */
function showPinModal() {
    const pinModal = document.getElementById('pinModal');
    if (pinModal) {
        // สร้างรหัส PIN ใหม่
        generateNewPin();
        
        // แสดงโมดัล
        pinModal.style.display = 'flex';
    }
}

/**
 * ปิดโมดัล
 * @param {string} modalId - ID ของโมดัลที่ต้องการปิด
 */
function closeModal(modalId = 'pinModal') {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        
        // ถ้าเป็นโมดัล PIN ให้หยุดการนับเวลาถอยหลัง
        if (modalId === 'pinModal' && pinTimer) {
            clearInterval(pinTimer);
            pinTimer = null;
        }
    }
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generateNewPin() {
    // สร้างรหัส PIN 4 หลักแบบสุ่ม
    const pin = Math.floor(1000 + Math.random() * 9000);
    const pinElement = document.getElementById('pinCode');
    
    if (pinElement) {
        pinElement.textContent = pin;
    }
    
    // เริ่มการนับเวลาถอยหลัง
    resetPinTimer();
    
    // ในทางปฏิบัติจริง ควรมีการส่ง AJAX request ไปยัง backend เพื่อบันทึกรหัส PIN ใหม่
    
    return pin;
}

/**
 * รีเซ็ตตัวนับเวลาถอยหลังสำหรับรหัส PIN
 */
function resetPinTimer() {
    // หยุดการนับเวลาถอยหลังเดิม (ถ้ามี)
    if (pinTimer) {
        clearInterval(pinTimer);
    }
    
    // ตั้งค่าเวลาเริ่มต้น (10 นาที)
    remainingTime = 600;
    updateTimerDisplay();
    
    // เริ่มการนับเวลาถอยหลังใหม่
    pinTimer = setInterval(function() {
        remainingTime--;
        
        if (remainingTime <= 0) {
            clearInterval(pinTimer);
            pinTimer = null;
            
            // สร้าง PIN ใหม่โดยอัตโนมัติเมื่อหมดเวลา
            generateNewPin();
        }
        
        updateTimerDisplay();
    }, 1000);
}

/**
 * อัปเดตการแสดงผลเวลาถอยหลัง
 */
function updateTimerDisplay() {
    const timerElement = document.querySelector('.timer span:last-child');
    
    if (timerElement) {
        const minutes = Math.floor(remainingTime / 60);
        const seconds = remainingTime % 60;
        
        timerElement.textContent = `หมดอายุใน ${minutes}:${seconds < 10 ? '0' : ''}${seconds} นาที`;
    }
}

/**
 * ดาวน์โหลดรายงาน
 */
function downloadReport() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    // เพื่อสร้างรายงาน และดาวน์โหลดไฟล์
    alert('กำลังดาวน์โหลดรายงานสรุปการเข้าแถว');
}

/**
 * เริ่มต้นกราฟต่างๆ ในหน้าแดชบอร์ด
 * ในทางปฏิบัติจริง ควรใช้ไลบรารี JavaScript สำหรับสร้างกราฟ เช่น Chart.js
 */
function initCharts() {
    // สร้างกราฟแท่งอย่างง่าย (ถ้ามี)
    createBarChart();
    
    // อื่นๆ
}

/**
 * สร้างกราฟแท่งอย่างง่าย
 * ในทางปฏิบัติจริง ควรใช้ไลบรารี JavaScript สำหรับสร้างกราฟ เช่น Chart.js
 */
function createBarChart() {
    const barChart = document.querySelector('.bar-chart');
    
    if (!barChart) return;
    
    // ข้อมูลตัวอย่าง
    const data = [
        { day: 'จันทร์', value: 95.2 },
        { day: 'อังคาร', value: 96.1 },
        { day: 'พุธ', value: 93.8 },
        { day: 'พฤหัสบดี', value: 92.7 },
        { day: 'ศุกร์', value: 91.5 },
        { day: 'จันทร์', value: 98.1 },
        { day: 'วันนี้', value: 95.0 }
    ];
    
    // ลบข้อมูลเดิม (ถ้ามี)
    barChart.innerHTML = '';
    
    // สร้างแท่งกราฟจากข้อมูล
    data.forEach(item => {
        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        bar.style.height = `${item.value}%`;
        
        const valueLabel = document.createElement('div');
        valueLabel.className = 'chart-bar-value';
        valueLabel.textContent = `${item.value}%`;
        
        const dayLabel = document.createElement('div');
        dayLabel.className = 'chart-bar-label';
        dayLabel.textContent = item.day;
        
        bar.appendChild(valueLabel);
        bar.appendChild(dayLabel);
        barChart.appendChild(bar);
    });
}
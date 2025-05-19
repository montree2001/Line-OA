/**
 * line-disconnect-student.js - สคริปต์สำหรับการยกเลิกการเชื่อมต่อ LINE ของนักเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // รับอ้างอิงปุ่มและโมดอล
    const disconnectBtn = document.getElementById('disconnectLineBtn');
    const disconnectModal = document.getElementById('disconnect-modal');
    const resultModal = document.getElementById('result-modal');
    const confirmDisconnectBtn = document.getElementById('confirm-disconnect');
    const cancelDisconnectBtn = document.getElementById('cancel-disconnect');
    const okResultBtn = document.getElementById('ok-result');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    
    // ตรวจสอบว่ามีปุ่มยกเลิกการเชื่อมต่อหรือไม่
    if (disconnectBtn) {
        // เพิ่ม Event Listener สำหรับปุ่มยกเลิกการเชื่อมต่อ
        disconnectBtn.addEventListener('click', function() {
            showModal(disconnectModal);
        });
    }
    
    // Event Listener สำหรับปุ่มยกเลิกใน modal
    if (cancelDisconnectBtn) {
        cancelDisconnectBtn.addEventListener('click', function() {
            hideModal(disconnectModal);
        });
    }
    
    // Event Listener สำหรับปุ่มยืนยันการยกเลิกการเชื่อมต่อ
    if (confirmDisconnectBtn) {
        confirmDisconnectBtn.addEventListener('click', function() {
            disconnectLine();
        });
    }
    
    // Event Listener สำหรับปุ่มตกลงใน modal ผลลัพธ์
    if (okResultBtn) {
        okResultBtn.addEventListener('click', function() {
            hideModal(resultModal);
            // รีโหลดหน้าเพื่อแสดงผลการเปลี่ยนแปลง
            window.location.reload();
        });
    }
    
    // Event Listener สำหรับปุ่มปิด modal ทั้งหมด
    closeModalBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            hideModal(modal);
        });
    });
    
    // ปิด modal เมื่อคลิกที่พื้นที่นอก modal content
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            hideModal(event.target);
        }
    });
});

/**
 * แสดง modal
 * 
 * @param {HTMLElement} modal modal ที่ต้องการแสดง
 */
function showModal(modal) {
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเมื่อ modal แสดง
    }
}

/**
 * ซ่อน modal
 * 
 * @param {HTMLElement} modal modal ที่ต้องการซ่อน
 */
function hideModal(modal) {
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // คืนค่าการเลื่อนหน้า
    }
}

/**
 * ส่งคำขอยกเลิกการเชื่อมต่อ LINE ไปยัง API
 */
function disconnectLine() {
    // แสดง Loading Overlay
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    // ซ่อน modal ยืนยัน
    const disconnectModal = document.getElementById('disconnect-modal');
    hideModal(disconnectModal);

    // สร้าง FormData สำหรับส่งข้อมูล
    const formData = new FormData();
    formData.append('action', 'disconnect');
    
    // สร้าง fetch request ไปยัง API ด้วย FormData แทน JSON
    fetch('api/line_disconnect_student_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // ตรวจสอบการตอบกลับของ API
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        
        return response.json();
    })
    .then(data => {
        // ซ่อน Loading Overlay
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        // รับอ้างอิงถึง modal ผลลัพธ์และองค์ประกอบ
        const resultModal = document.getElementById('result-modal');
        const resultIcon = document.getElementById('result-icon');
        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');
        
        // ปรับข้อมูลใน modal ตามผลลัพธ์
        if (data.success) {
            // กรณีสำเร็จ
            resultIcon.innerHTML = '<span class="material-icons">check_circle</span>';
            resultIcon.className = 'success';
            resultTitle.textContent = 'ดำเนินการเรียบร้อย';
            resultMessage.textContent = 'ยกเลิกการเชื่อมต่อ LINE สำเร็จแล้ว';
        } else {
            // กรณีเกิดข้อผิดพลาด
            resultIcon.innerHTML = '<span class="material-icons">error</span>';
            resultIcon.className = 'error';
            resultTitle.textContent = 'เกิดข้อผิดพลาด';
            resultMessage.textContent = data.message || 'ไม่สามารถยกเลิกการเชื่อมต่อ LINE ได้';
        }
        
        // แสดง modal ผลลัพธ์
        showModal(resultModal);
    })
    .catch(error => {
        // ซ่อน Loading Overlay
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        // แสดงข้อผิดพลาด
        console.error('Error:', error);
        
        // แสดง modal ผลลัพธ์กรณีเกิดข้อผิดพลาด
        const resultModal = document.getElementById('result-modal');
        const resultIcon = document.getElementById('result-icon');
        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');
        
        resultIcon.innerHTML = '<span class="material-icons">error</span>';
        resultIcon.className = 'error';
        resultTitle.textContent = 'เกิดข้อผิดพลาด';
        resultMessage.textContent = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ โปรดลองอีกครั้งในภายหลัง: ' + error.message;
        
        showModal(resultModal);
    });
}
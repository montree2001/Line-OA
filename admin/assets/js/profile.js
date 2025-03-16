/**
 * profile.js - JavaScript สำหรับหน้าโปรไฟล์เจ้าหน้าที่
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่าปุ่มและฟังก์ชันต่างๆ
    setupEventListeners();
});

/**
 * ตั้งค่าแท็บในหน้าโปรไฟล์
 */
function initTabs() {
    const tabs = document.querySelectorAll('.profile-tabs .tab');
    const tabContents = document.querySelectorAll('.profile-tabs .tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // ซ่อนแท็บทั้งหมด
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            
            // แสดงแท็บที่เลือก
            const tabId = this.getAttribute('data-tab');
            this.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
}

/**
 * ตั้งค่า event listeners สำหรับปุ่มต่างๆ
 */
function setupEventListeners() {
    // ฟังก์ชันเปลี่ยนรูปโปรไฟล์
    const changeProfilePicBtn = document.querySelector('.avatar-edit-btn');
    if (changeProfilePicBtn) {
        changeProfilePicBtn.addEventListener('click', changeProfilePicture);
    }
}

/**
 * เปลี่ยนรูปโปรไฟล์
 */
function changeProfilePicture() {
    // สร้าง input file แบบไม่แสดงผล
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    
    // เมื่อเลือกไฟล์
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        
        // ตรวจสอบขนาดและประเภทไฟล์
        if (file) {
            if (file.size > 5 * 1024 * 1024) { // 5MB
                showAlert('ขนาดไฟล์ใหญ่เกินไป กรุณาเลือกไฟล์ขนาดไม่เกิน 5MB', 'danger');
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showAlert('กรุณาเลือกไฟล์รูปภาพที่ถูกต้อง (JPEG, PNG, GIF)', 'danger');
                return;
            }
            
            // อัปโหลดรูปโปรไฟล์
            uploadProfilePicture(file);
        }
    });
    
    // เรียกใช้ input file
    fileInput.click();
}

/**
 * อัปโหลดรูปโปรไฟล์
 * @param {File} file - ไฟล์รูปภาพที่ต้องการอัปโหลด
 */
function uploadProfilePicture(file) {
    // สร้าง FormData เพื่อส่งไฟล์
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    // แสดง loading
    showLoading('กำลังอัปโหลดรูปโปรไฟล์...');
    
    // ส่งคำขอ AJAX
    fetch('api/upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // อัปเดตรูปโปรไฟล์
            const avatarContainer = document.querySelector('.profile-avatar-container');
            const currentAvatar = avatarContainer.querySelector('.profile-avatar, .profile-avatar-placeholder');
            
            if (currentAvatar) {
                const newAvatar = document.createElement('img');
                newAvatar.src = data.file_path;
                newAvatar.alt = 'รูปโปรไฟล์';
                newAvatar.className = 'profile-avatar';
                
                currentAvatar.replaceWith(newAvatar);
            }
            
            showAlert('อัปโหลดรูปโปรไฟล์สำเร็จ', 'success');
        } else {
            showAlert(data.message || 'เกิดข้อผิดพลาดในการอัปโหลด', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'danger');
    });
}

/**
 * เปลี่ยนรหัสผ่าน
 */
function changePassword() {
    // สร้าง modal หรือส่งไปยังหน้าเปลี่ยนรหัสผ่าน
    const passwordModal = document.getElementById('changePasswordModal');
    if (passwordModal) {
        showModal('changePasswordModal');
    } else {
        // หากไม่มี modal ให้เปลี่ยนหน้า
        window.location.href = 'change_password.php';
    }
}

/**
 * กำหนดค่าการยืนยันตัวตนสองขั้นตอน
 */
function configTwoFactor() {
    // สร้าง modal หรือส่งไปยังหน้าตั้งค่า
    const twoFactorModal = document.getElementById('twoFactorModal');
    if (twoFactorModal) {
        showModal('twoFactorModal');
    } else {
        // หากไม่มี modal ให้เปลี่ยนหน้า
        window.location.href = 'two_factor_setup.php';
    }
}

/**
 * จัดการอุปกรณ์เข้าสู่ระบบ
 */
function manageDevices() {
    // สร้าง modal หรือส่งไปยังหน้าจัดการอุปกรณ์
    const devicesModal = document.getElementById('devicesModal');
    if (devicesModal) {
        showModal('devicesModal');
    } else {
        // หากไม่มี modal ให้เปลี่ยนหน้า
        window.location.href = 'manage_devices.php';
    }
}

/**
 * แสดง modal
 * @param {string} modalId - ID ของ modal ที่ต้องการแสดง
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            alertContainer.removeChild(alert);
        }, 300);
    });
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * แสดง loading
 * @param {string} message - ข้อความที่ต้องการแสดง
 */
function showLoading(message = 'กำลังโหลด...') {
    // สร้าง loading container ถ้ายังไม่มี
    let loadingContainer = document.querySelector('.loading-container');
    
    if (!loadingContainer) {
        loadingContainer = document.createElement('div');
        loadingContainer.className = 'loading-container';
        document.body.appendChild(loadingContainer);
    }
    
    // สร้าง loading element
    loadingContainer.innerHTML = `
        <div class="loading-spinner"></div>
        <div class="loading-message">${message}</div>
    `;
    
    // แสดง loading
    loadingContainer.classList.add('active');
}

/**
 * ซ่อน loading
 */
function hideLoading() {
    const loadingContainer = document.querySelector('.loading-container');
    if (loadingContainer) {
        loadingContainer.classList.remove('active');
        setTimeout(() => {
            document.body.removeChild(loadingContainer);
        }, 300);
    }
}




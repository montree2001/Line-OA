/**
 * parent-profile.js - ไฟล์ JavaScript สำหรับหน้าโปรไฟล์ผู้ปกครอง SADD-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงาน
    initProfilePage();
});

/**
 * เริ่มต้นการทำงานในหน้าโปรไฟล์
 */
function initProfilePage() {
    // ตั้งค่าการทำงานของแท็บ
    setupTabs();
    
    // ตั้งค่าการตรวจสอบฟอร์ม
    setupFormValidation();
    
    // ตั้งค่าการยืนยันก่อนออกจากฟอร์มที่มีการเปลี่ยนแปลง
    setupFormChangeDetection();
    
    // ตั้งค่าการเปลี่ยนข้อมูลส่วนตัว
    setupProfileUpdate();
}

/**
 * ตั้งค่าการทำงานของแท็บ
 */
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // ตรวจสอบพารามิเตอร์ใน URL
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    // กำหนดแท็บเริ่มต้น
    let activeTabIndex = 0;
    
    if (tabParam === 'children') {
        activeTabIndex = 1;
    } else if (tabParam === 'settings') {
        activeTabIndex = 2;
    }
    
    // ซ่อนทุกแท็บเนื้อหาและลบคลาส active
    tabContents.forEach(content => {
        content.classList.remove('active');
    });
    
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // แสดงแท็บที่เลือก
    tabContents[activeTabIndex].classList.add('active');
    tabButtons[activeTabIndex].classList.add('active');
    
    // เพิ่มเหตุการณ์คลิกให้กับทุกปุ่มแท็บ
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            // ซ่อนทุกแท็บและลบคลาส active
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // แสดงแท็บที่คลิก
            tabContents[index].classList.add('active');
            this.classList.add('active');
            
            // อัปเดต URL (ไม่มีการโหลดหน้าใหม่)
            const tabNames = ['personal-info', 'children', 'settings'];
            const newUrl = window.location.pathname + (index > 0 ? `?tab=${tabNames[index]}` : '');
            window.history.replaceState({}, '', newUrl);
        });
    });
}

/**
 * ตั้งค่าการตรวจสอบฟอร์ม
 */
function setupFormValidation() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // ตรวจสอบชื่อ-นามสกุล
        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        
        if (firstName && firstName.value.trim() === '') {
            highlightError(firstName, 'กรุณากรอกชื่อจริง');
            hasErrors = true;
        } else if (firstName) {
            removeError(firstName);
        }
        
        if (lastName && lastName.value.trim() === '') {
            highlightError(lastName, 'กรุณากรอกนามสกุล');
            hasErrors = true;
        } else if (lastName) {
            removeError(lastName);
        }
        
        // ตรวจสอบเบอร์โทรศัพท์
        const phoneNumber = document.getElementById('phone_number');
        const phoneRegex = /^0[0-9]{8,9}$/; // เบอร์โทรที่ขึ้นต้นด้วย 0 ตามด้วยตัวเลข 8-9 หลัก
        
        if (phoneNumber && !phoneRegex.test(phoneNumber.value)) {
            highlightError(phoneNumber, 'กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง (เช่น 0812345678)');
            hasErrors = true;
        } else if (phoneNumber) {
            removeError(phoneNumber);
        }
        
        // ตรวจสอบอีเมล (ถ้ามี)
        const email = document.getElementById('email');
        const emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        
        if (email && email.value.trim() !== '' && !emailRegex.test(email.value)) {
            highlightError(email, 'กรุณากรอกอีเมลที่ถูกต้อง');
            hasErrors = true;
        } else if (email) {
            removeError(email);
        }
        
        // ถ้ามีข้อผิดพลาด ไม่ให้ส่งฟอร์ม
        if (hasErrors) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
}

/**
 * ไฮไลต์ช่องกรอกข้อมูลที่มีข้อผิดพลาด
 * @param {HTMLElement} element - อีลิเมนต์ที่มีข้อผิดพลาด
 * @param {string} message - ข้อความแสดงข้อผิดพลาด
 */
function highlightError(element, message) {
    // ตรวจสอบว่ามีข้อความแสดงข้อผิดพลาดอยู่แล้วหรือไม่
    let errorMessage = element.parentNode.querySelector('.error-message');
    
    // หากไม่มี ให้สร้างใหม่
    if (!errorMessage) {
        errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        element.parentNode.appendChild(errorMessage);
    }
    
    // กำหนดค่าและแสดงข้อความแสดงข้อผิดพลาด
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
    
    // เพิ่มสไตล์ให้กับอีลิเมนต์ที่มีข้อผิดพลาด
    element.classList.add('error');
    
    // เพิ่มสไตล์ CSS
    if (!document.getElementById('error-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'error-styles';
        styleElement.textContent = `
            .error-message {
                color: #f44336;
                font-size: 12px;
                margin-top: 5px;
                display: none;
            }
            
            .error {
                border-color: #f44336 !important;
            }
            
            .form-group.has-error {
                margin-bottom: 25px;
            }
        `;
        document.head.appendChild(styleElement);
    }
    
    // เพิ่มคลาสให้ .form-group
    element.closest('.form-group').classList.add('has-error');
}

/**
 * ลบสถานะข้อผิดพลาด
 * @param {HTMLElement} element - อีลิเมนต์ที่ต้องการลบสถานะข้อผิดพลาด
 */
function removeError(element) {
    // ลบคลาส error
    element.classList.remove('error');
    
    // ซ่อนข้อความแสดงข้อผิดพลาด
    const errorMessage = element.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.style.display = 'none';
    }
    
    // ลบคลาส has-error
    element.closest('.form-group').classList.remove('has-error');
}

/**
 * ตั้งค่าการตรวจจับการเปลี่ยนแปลงฟอร์ม
 */
function setupFormChangeDetection() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    
    // เก็บค่าเริ่มต้นของฟอร์ม
    const initialFormData = new FormData(form);
    const initialFormValues = {};
    
    for (const [key, value] of initialFormData.entries()) {
        initialFormValues[key] = value;
    }
    
    // ตัวแปรสำหรับเก็บสถานะการเปลี่ยนแปลง
    let formChanged = false;
    
    // เพิ่มเหตุการณ์การเปลี่ยนแปลงให้กับทุกอินพุตในฟอร์ม
    const formInputs = form.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            checkFormChanges();
        });
        
        input.addEventListener('input', function() {
            checkFormChanges();
        });
    });
    
    // ตรวจสอบการเปลี่ยนแปลงของฟอร์ม
    function checkFormChanges() {
        const currentFormData = new FormData(form);
        formChanged = false;
        
        for (const [key, value] of currentFormData.entries()) {
            if (initialFormValues[key] !== value) {
                formChanged = true;
                break;
            }
        }
        
        // ปรับสถานะปุ่มยกเลิก
        const resetButton = form.querySelector('button[type="reset"]');
        if (resetButton) {
            resetButton.disabled = !formChanged;
        }
    }
    
    // เพิ่มการยืนยันก่อนออกจากหน้าหากฟอร์มมีการเปลี่ยนแปลง
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            const confirmationMessage = 'คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
    
    // รีเซ็ตสถานะการเปลี่ยนแปลงเมื่อกดบันทึก
    form.addEventListener('submit', function() {
        formChanged = false;
    });
    
    // รีเซ็ตสถานะการเปลี่ยนแปลงเมื่อกดยกเลิก
    const resetButton = form.querySelector('button[type="reset"]');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            formChanged = false;
            setTimeout(() => {
                resetButton.disabled = true;
            }, 100);
        });
    }
}

/**
 * ตั้งค่าการอัปเดตข้อมูลส่วนตัว
 */
function setupProfileUpdate() {
    const form = document.querySelector('.profile-form');
    if (!form) return;
    
    // เพิ่มการทำงานเมื่อกดบันทึก
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // แสดงการโหลด
        showLoading();
        
        // ส่งฟอร์มด้วย AJAX
        const formData = new FormData(form);
        
        // ใช้ Fetch API
        fetch(form.action || window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // ซ่อนการโหลด
            hideLoading();
            
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
            
            // ถ้าโอเค ให้รีโหลดหน้า
            window.location.reload();
        })
        .catch(error => {
            // ซ่อนการโหลด
            hideLoading();
            
            // แสดงข้อผิดพลาด
            showNotification('เกิดข้อผิดพลาด', error.message, 'danger');
        });
    });
    
    // รีเซ็ตฟอร์ม
    const resetButton = form.querySelector('button[type="reset"]');
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // แสดงข้อความยืนยันการยกเลิก
            setTimeout(() => {
                showNotification('ยกเลิกการแก้ไข', 'ข้อมูลถูกรีเซ็ตเรียบร้อยแล้ว', 'warning');
            }, 100);
        });
    }
}

/**
 * แสดงการแจ้งเตือน
 * @param {string} title - หัวข้อการแจ้งเตือน
 * @param {string} message - ข้อความการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน (success, warning, danger)
 */
function showNotification(title, message, type = 'success') {
    // ตรวจสอบว่ามีแบนเนอร์การแจ้งเตือนอยู่แล้วหรือไม่
    let notification = document.querySelector('.notification-banner');
    if (!notification) {
        // สร้างแบนเนอร์การแจ้งเตือนใหม่
        notification = document.createElement('div');
        notification.className = `notification-banner ${type}`;
        
        // สร้างไอคอน
        const icon = document.createElement('span');
        icon.className = 'material-icons icon';
        
        switch (type) {
            case 'success':
                icon.textContent = 'check_circle';
                break;
            case 'warning':
                icon.textContent = 'warning';
                break;
            case 'danger':
                icon.textContent = 'error';
                break;
            default:
                icon.textContent = 'info';
        }
        
        // สร้างเนื้อหา
        const content = document.createElement('div');
        content.className = 'content';
        
        const titleElement = document.createElement('div');
        titleElement.className = 'title';
        titleElement.textContent = title;
        
        const messageElement = document.createElement('div');
        messageElement.className = 'message';
        messageElement.textContent = message;
        
        // รวมองค์ประกอบเข้าด้วยกัน
        content.appendChild(titleElement);
        content.appendChild(messageElement);
        
        notification.appendChild(icon);
        notification.appendChild(content);
        
        // เพิ่มลงในหน้า
        document.querySelector('.container').prepend(notification);
        
        // ซ่อนการแจ้งเตือนหลังจาก 5 วินาที
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.height = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
}

/**
 * แสดงการโหลด
 */
function showLoading() {
    // ตรวจสอบว่ามีการแสดงการโหลดอยู่แล้วหรือไม่
    if (document.getElementById('loading-overlay')) return;
    
    // สร้างโอเวอร์เลย์
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    overlay.style.zIndex = '9999';
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    
    // สร้างตัวแสดงการโหลด
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = '<span class="material-icons rotating">sync</span>';
    
    // เพิ่มสไตล์ CSS
    const style = document.createElement('style');
    style.textContent = `
        .rotating {
            animation: rotating 2s linear infinite;
            font-size: 48px;
            color: white;
        }
        
        @keyframes rotating {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    // รวมองค์ประกอบเข้าด้วยกัน
    overlay.appendChild(spinner);
    document.body.appendChild(overlay);
}

/**
 * ซ่อนการโหลด
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}
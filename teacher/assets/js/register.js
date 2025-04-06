/**
 * register.js - สคริปต์จาวาสคริปต์สำหรับหน้าลงทะเบียนครู
 */

// เริ่มการทำงานเมื่อโหลดหน้าเว็บเสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    initInputValidation();
    initProfileImageUpload();
    setupConfetti();
});

/**
 * ตั้งค่าการตรวจสอบข้อมูลในฟอร์ม
 */
function initInputValidation() {
    // ตรวจสอบเลขบัตรประชาชน
    const idCardInput = document.getElementById('id-card-input');
    if (idCardInput) {
        idCardInput.addEventListener('input', function() {
            // รับค่าปัจจุบันและลบอักขระที่ไม่ใช่ตัวเลข
            let value = this.value.replace(/\D/g, '');
            
            // จำกัดความยาวไม่เกิน 13 หลัก
            if (value.length > 13) {
                value = value.slice(0, 13);
            }
            
            // อัปเดตค่าในช่องข้อมูล
            this.value = value;
        });
    }
    
    // ตรวจสอบเบอร์โทรศัพท์
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            // รับค่าปัจจุบันและลบอักขระที่ไม่ใช่ตัวเลข
            let value = this.value.replace(/\D/g, '');
            
            // จำกัดความยาวไม่เกิน 10 หลัก
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            
            // อัปเดตค่าในช่องข้อมูล
            this.value = value;
        });
    }
    
    // ตรวจสอบการกดปุ่มบันทึกข้อมูล
    const saveButton = document.getElementById('save-button');
    if (saveButton) {
        saveButton.addEventListener('click', function(event) {
            // ตรวจสอบว่าฟอร์มถูกต้องหรือไม่ก่อนส่งข้อมูล
            const form = this.closest('form');
            if (form && !validateForm(form)) {
                event.preventDefault();
            }
        });
    }
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 * @param {HTMLFormElement} form - ฟอร์มที่ต้องการตรวจสอบ
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validateForm(form) {
    let isValid = true;
    
    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('invalid');
            
            // แสดง tooltip หรือข้อความแจ้งเตือน
            showFieldError(field, 'กรุณากรอกข้อมูลในช่องนี้');
        } else {
            field.classList.remove('invalid');
            hideFieldError(field);
        }
    });
    
    // ตรวจสอบเบอร์โทรศัพท์
    const phoneInput = form.querySelector('#phone');
    if (phoneInput && phoneInput.value.trim() && !validatePhoneNumber(phoneInput.value)) {
        isValid = false;
        phoneInput.classList.add('invalid');
        showFieldError(phoneInput, 'เบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกเบอร์โทรศัพท์ 10 หลัก');
    }
    
    // ตรวจสอบอีเมล
    const emailInput = form.querySelector('input[type="email"]');
    if (emailInput && emailInput.value.trim() && !validateEmail(emailInput.value)) {
        isValid = false;
        emailInput.classList.add('invalid');
        showFieldError(emailInput, 'รูปแบบอีเมลไม่ถูกต้อง');
    }
    
    // ตรวจสอบการยินยอมนโยบายความเป็นส่วนตัว
    const privacyConsent = form.querySelector('#privacy-consent');
    if (privacyConsent && !privacyConsent.checked) {
        isValid = false;
        showFieldError(privacyConsent, 'กรุณายินยอมนโยบายความเป็นส่วนตัวเพื่อดำเนินการต่อ');
    }
    
    return isValid;
}

/**
 * แสดงข้อความแจ้งเตือนข้อผิดพลาดของฟิลด์
 * @param {HTMLElement} field - ฟิลด์ที่มีข้อผิดพลาด
 * @param {string} message - ข้อความแจ้งเตือน
 */
function showFieldError(field, message) {
    // ตรวจสอบว่ามีข้อความแจ้งเตือนอยู่แล้วหรือไม่
    let errorElement = field.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('field-error')) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }
    
    errorElement.textContent = message;
    errorElement.style.color = 'red';
    errorElement.style.fontSize = '12px';
    errorElement.style.marginTop = '5px';
}

/**
 * ซ่อนข้อความแจ้งเตือนข้อผิดพลาดของฟิลด์
 * @param {HTMLElement} field - ฟิลด์ที่ต้องการซ่อนข้อความแจ้งเตือน
 */
function hideFieldError(field) {
    const errorElement = field.nextElementSibling;
    if (errorElement && errorElement.classList.contains('field-error')) {
        errorElement.remove();
    }
}

/**
 * ตรวจสอบความถูกต้องของเบอร์โทรศัพท์
 * @param {string} phone - เบอร์โทรศัพท์ที่ต้องการตรวจสอบ
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validatePhoneNumber(phone) {
    return /^0\d{9}$/.test(phone);
}

/**
 * ตรวจสอบความถูกต้องของอีเมล
 * @param {string} email - อีเมลที่ต้องการตรวจสอบ
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * ตั้งค่าการอัพโหลดรูปโปรไฟล์
 */
function initProfileImageUpload() {
    const profilePictureInput = document.getElementById('profile-picture-input');
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (file) {
                // ตรวจสอบประเภทไฟล์
                const fileType = file.type;
                if (!fileType.startsWith('image/')) {
                    alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                    this.value = ''; // ล้างค่าไฟล์ที่เลือก
                    return;
                }
                
                // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('ขนาดไฟล์ต้องไม่เกิน 5MB');
                    this.value = ''; // ล้างค่าไฟล์ที่เลือก
                    return;
                }
                
                // แสดงตัวอย่างรูปภาพ
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profileAvatar = document.querySelector('.profile-avatar');
                    
                    // ซ่อนตัวอักษรแรก
                    const initial = profileAvatar.querySelector('.avatar-initial');
                    if (initial) {
                        initial.style.display = 'none';
                    }
                    
                    // ตรวจสอบว่ามีรูปภาพอยู่แล้วหรือไม่
                    let img = profileAvatar.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        profileAvatar.insertBefore(img, profileAvatar.firstChild);
                    }
                    
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
}

/**
 * ตั้งค่า Confetti Effect สำหรับหน้าลงทะเบียนเสร็จสิ้น
 */
function setupConfetti() {
    const confettiContainer = document.getElementById('confetti-container');
    if (confettiContainer) {
        createConfetti();
    }
}

/**
 * สร้าง Confetti Effect
 */
function createConfetti() {
    const confettiContainer = document.getElementById('confetti-container');
    const colors = ['', 'blue', 'green', 'pink'];
    const totalConfetti = 50;
    
    for (let i = 0; i < totalConfetti; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti ' + colors[Math.floor(Math.random() * colors.length)];
        
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.animationDelay = Math.random() * 5 + 's';
        confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
        
        // กำหนดรูปร่างของ confetti
        if (Math.random() > 0.5) {
            confetti.style.borderRadius = '50%';
        } else if (Math.random() > 0.5) {
            confetti.style.width = '6px';
            confetti.style.height = '16px';
        }
        
        confettiContainer.appendChild(confetti);
    }
}

/**
 * แสดงนโยบายความเป็นส่วนตัว
 */
function showPrivacyPolicy() {
    alert('นโยบายความเป็นส่วนตัวของวิทยาลัยการอาชีพปราสาท\n\nวิทยาลัยการอาชีพปราสาทจะเก็บรวบรวมข้อมูลส่วนบุคคลของครูที่ปรึกษาและนักเรียนเพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์เท่านั้น โดยจะไม่เปิดเผยข้อมูลต่อบุคคลที่สาม ยกเว้นในกรณีที่จำเป็นต้องปฏิบัติตามกฎหมาย');
}

/**
 * ระบบนำทาง
 */
function goBack() {
    window.history.back();
}

/**
 * นำทางไปยังหน้าที่กำหนด
 * @param {string} url - URL ที่ต้องการนำทางไป
 */
function navigateTo(url) {
    window.location.href = url;
}
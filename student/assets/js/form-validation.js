/**
 * form-validation.js - JavaScript สำหรับการตรวจสอบฟอร์ม
 */

document.addEventListener('DOMContentLoaded', function() {
    // ดึงอ้างอิงไปยังฟอร์มทั้งหมด
    const forms = document.querySelectorAll('form');
    
    // เพิ่ม Event Listener สำหรับแต่ละฟอร์ม
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
        
        // เพิ่ม Event Listener สำหรับ Input ทุกตัว
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            input.addEventListener('input', function() {
                const errorDiv = this.parentElement.querySelector('.form-error');
                if (errorDiv) {
                    errorDiv.remove();
                    this.classList.remove('error');
                }
            });
        });
    });
    
    /**
     * ฟังก์ชันตรวจสอบความถูกต้องของฟอร์ม
     * @param {Event} e - เหตุการณ์การ submit
     */
    function validateForm(e) {
        let isValid = true;
        
        // ตรวจสอบ input ทุกตัว
        const inputs = this.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.hasAttribute('required') && !validateInput(input)) {
                isValid = false;
            }
        });
        
        // ถ้าฟอร์มไม่ถูกต้อง ให้ยกเลิกการส่ง
        if (!isValid) {
            e.preventDefault();
            
            // เลื่อนไปยัง input แรกที่มีข้อผิดพลาด
            const firstError = this.querySelector('.error');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
    }
    
    /**
     * ฟังก์ชันตรวจสอบความถูกต้องของแต่ละ input
     * @param {HTMLElement} input - element input ที่ต้องการตรวจสอบ
     * @returns {boolean} ผลการตรวจสอบ (true = ถูกต้อง, false = ไม่ถูกต้อง)
     */
    function validateInput(input) {
        // เคลียร์ข้อผิดพลาดเดิม
        clearError(input);
        
        // ถ้า input ไม่ได้ required และไม่มีค่า ให้ผ่าน
        if (!input.hasAttribute('required') && !input.value.trim()) {
            return true;
        }
        
        // ตรวจสอบว่า input มีค่าหรือไม่
        if (input.hasAttribute('required') && !input.value.trim()) {
            showError(input, 'กรุณากรอกข้อมูลในช่องนี้');
            return false;
        }
        
        // ตรวจสอบตามประเภทของ input
        const type = input.type.toLowerCase();
        
        // ตรวจสอบ email
        if (type === 'email' && input.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value.trim())) {
                showError(input, 'รูปแบบอีเมลไม่ถูกต้อง');
                return false;
            }
        }
        
        // ตรวจสอบเบอร์โทรศัพท์
        if (type === 'tel' && input.value.trim()) {
            const phoneRegex = /^[0-9\-]{9,15}$/;
            if (!phoneRegex.test(input.value.trim())) {
                showError(input, 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง');
                return false;
            }
        }
        
        // ตรวจสอบความยาวขั้นต่ำ
        if (input.hasAttribute('minlength') && input.value.trim()) {
            const minLength = parseInt(input.getAttribute('minlength'));
            if (input.value.trim().length < minLength) {
                showError(input, `กรุณากรอกอย่างน้อย ${minLength} ตัวอักษร`);
                return false;
            }
        }
        
        // ตรวจสอบความยาวสูงสุด
        if (input.hasAttribute('maxlength') && input.value.trim()) {
            const maxLength = parseInt(input.getAttribute('maxlength'));
            if (input.value.trim().length > maxLength) {
                showError(input, `กรุณากรอกไม่เกิน ${maxLength} ตัวอักษร`);
                return false;
            }
        }
        
        // ตรวจสอบรูปแบบด้วย pattern (ถ้ามี)
        if (input.hasAttribute('pattern') && input.value.trim()) {
            const pattern = new RegExp(input.getAttribute('pattern'));
            if (!pattern.test(input.value.trim())) {
                showError(input, 'รูปแบบข้อมูลไม่ถูกต้อง');
                return false;
            }
        }
        
        // ตรวจสอบผ่าน
        return true;
    }
    
    /**
     * ฟังก์ชันแสดงข้อความผิดพลาด
     * @param {HTMLElement} input - element input ที่มีข้อผิดพลาด
     * @param {string} message - ข้อความผิดพลาด
     */
    function showError(input, message) {
        // เพิ่ม class error ให้กับ input
        input.classList.add('error');
        
        // สร้าง element สำหรับแสดงข้อผิดพลาด
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        errorDiv.textContent = message;
        
        // เพิ่ม element เข้าไปหลัง input
        const parent = input.parentElement;
        
        // ตรวจสอบว่ามี error message อยู่แล้วหรือไม่
        const existingError = parent.querySelector('.form-error');
        if (existingError) {
            existingError.textContent = message;
        } else {
            parent.appendChild(errorDiv);
        }
    }
    
    /**
     * ฟังก์ชันลบข้อความผิดพลาด
     * @param {HTMLElement} input - element input ที่ต้องการลบข้อผิดพลาด
     */
    function clearError(input) {
        // ลบ class error
        input.classList.remove('error');
        
        // ลบข้อความผิดพลาด
        const parent = input.parentElement;
        const errorDiv = parent.querySelector('.form-error');
        if (errorDiv) {
            parent.removeChild(errorDiv);
        }
    }
});
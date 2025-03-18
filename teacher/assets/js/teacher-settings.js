/**
 * teacher-settings.js - สคริปต์เฉพาะสำหรับหน้าตั้งค่าระบบ Teacher-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้าตั้งค่า
    initSettings();
});

/**
 * เริ่มต้นการทำงานของหน้าตั้งค่า
 */
function initSettings() {
    // เพิ่ม event listeners สำหรับ modals
    initModals();
    
    // ตรวจสอบการแจ้งเตือน (alerts)
    checkAlerts();
}

/**
 * เพิ่ม event listeners สำหรับ modals
 */
function initModals() {
    // ปิด modal เมื่อคลิกนอกพื้นที่
    const settingsModals = document.querySelectorAll('.settings-modal');
    
    settingsModals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeSettingModal(modal.id.replace('-modal', ''));
            }
        });
    });
    
    // ปิดด้วยปุ่ม ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.settings-modal.active');
            if (activeModal) {
                closeSettingModal(activeModal.id.replace('-modal', ''));
            }
        }
    });
}

/**
 * ตรวจสอบการแจ้งเตือน (alerts) จาก session
 */
function checkAlerts() {
    // ในระบบจริงจะดึงข้อมูลจาก session storage หรือ cookies
    // สำหรับตัวอย่างนี้ เราจะใช้ localStorage แทน
    
    const alert = localStorage.getItem('alert');
    
    if (alert) {
        try {
            const alertData = JSON.parse(alert);
            
            // แสดงการแจ้งเตือน
            showAlert(alertData.message, alertData.type);
            
            // ลบข้อมูลการแจ้งเตือน
            localStorage.removeItem('alert');
        } catch (error) {
            console.error('เกิดข้อผิดพลาดในการอ่านข้อมูลการแจ้งเตือน:', error);
        }
    }
}

/**
 * นำทางไปยังหน้าอื่น
 * @param {string} url - URL ที่ต้องการนำทางไป
 */
function navigateTo(url) {
    // ถ้าเป็น URL ที่มี type=xxx ให้แสดง modal แทนการเปลี่ยนหน้า
    if (url.includes('type=')) {
        const type = url.split('type=')[1].split('&')[0]; // ดึงค่า type จาก URL
        showSettingModal(type);
    } else {
        window.location.href = url;
    }
}

/**
 * แสดง Modal การตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 */
function showSettingModal(type) {
    const modalId = `${type}-modal`;
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    } else {
        // สร้าง modal ใหม่ถ้ายังไม่มี
        createSettingModal(type);
    }
}

/**
 * สร้าง Modal การตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 */
function createSettingModal(type) {
    // ข้อมูลสำหรับสร้าง modal ตามประเภท
    const modalData = getModalData(type);
    
    if (!modalData) {
        console.error(`ไม่พบข้อมูลสำหรับการตั้งค่าประเภท "${type}"`);
        return;
    }
    
    // สร้าง modal element
    const modal = document.createElement('div');
    modal.id = `${type}-modal`;
    modal.className = 'settings-modal';
    
    // กำหนด HTML ของ modal
    modal.innerHTML = `
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <div class="settings-modal-title">${modalData.title}</div>
                <button class="settings-modal-close" onclick="closeSettingModal('${type}')">&times;</button>
            </div>
            <form class="settings-form" id="${type}-form" onsubmit="saveSettingForm('${type}', event)">
                ${modalData.content}
                <div class="settings-buttons">
                    <button type="button" class="settings-button cancel" onclick="closeSettingModal('${type}')">ยกเลิก</button>
                    <button type="submit" class="settings-button save">บันทึก</button>
                </div>
            </form>
        </div>
    `;
    
    // เพิ่ม modal ไปยังหน้าเว็บ
    document.body.appendChild(modal);
    
    // แสดง modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // เพิ่ม event listeners สำหรับ form elements ถ้าจำเป็น
    initFormElements(type);
}

/**
 * ปิด Modal การตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 */
function closeSettingModal(type) {
    const modalId = `${type}-modal`;
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * บันทึกข้อมูลการตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 * @param {Event} event - เหตุการณ์การส่งฟอร์ม
 */
function saveSettingForm(type, event) {
    event.preventDefault();
    
    // ดึงข้อมูลจากฟอร์ม
    const form = document.getElementById(`${type}-form`);
    const formData = new FormData(form);
    
    // แปลงข้อมูลเป็น object
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    // ตรวจสอบข้อมูล (ตามประเภทการตั้งค่า)
    if (!validateSettingData(type, data)) {
        return;
    }
    
    // ในระบบจริงจะมีการส่งข้อมูลไปยัง server
    // แต่ในตัวอย่างนี้จะเป็นเพียงการจำลองการบันทึกข้อมูล
    simulateSaveSettings(type, data);
}

/**
 * จำลองการบันทึกข้อมูลการตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 * @param {object} data - ข้อมูลที่จะบันทึก
 */
function simulateSaveSettings(type, data) {
    // แสดงข้อความกำลังบันทึก
    showAlert('กำลังบันทึกข้อมูล...', 'info');
    
    // จำลองการส่งข้อมูลไปยัง server
    setTimeout(() => {
        // บันทึกการแจ้งเตือนใน localStorage
        localStorage.setItem('alert', JSON.stringify({
            type: 'success',
            message: 'บันทึกการตั้งค่าเรียบร้อยแล้ว'
        }));
        
        // รีโหลดหน้าเว็บ
        window.location.href = 'settings.php';
    }, 1000);
}

/**
 * ตรวจสอบข้อมูลการตั้งค่า
 * @param {string} type - ประเภทการตั้งค่า
 * @param {object} data - ข้อมูลที่จะตรวจสอบ
 * @returns {boolean} ผลการตรวจสอบ
 */
function validateSettingData(type, data) {
    // ตรวจสอบข้อมูลตามประเภทการตั้งค่า
    switch (type) {
        case 'profile':
            // ตรวจสอบข้อมูลโปรไฟล์
            if (!data.firstname || !data.lastname) {
                showAlert('กรุณากรอกชื่อและนามสกุล', 'error');
                return false;
            }
            
            if (!data.phone || !/^\d{10}$/.test(data.phone)) {
                showAlert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (10 หลัก)', 'error');
                return false;
            }
            
            if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
                showAlert('กรุณากรอกอีเมลให้ถูกต้อง', 'error');
                return false;
            }
            
            return true;
            
        case 'password':
            // ตรวจสอบรหัสผ่าน
            if (!data.old_password) {
                showAlert('กรุณากรอกรหัสผ่านเดิม', 'error');
                return false;
            }
            
            if (!data.new_password || data.new_password.length < 8) {
                showAlert('รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร', 'error');
                return false;
            }
            
            if (data.new_password !== data.confirm_password) {
                showAlert('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน', 'error');
                return false;
            }
            
            return true;
            
        case 'pin':
            // ตรวจสอบการตั้งค่า PIN
            if (!data.pin_expiration || isNaN(data.pin_expiration) || data.pin_expiration < 1) {
                showAlert('กรุณากรอกเวลาหมดอายุของรหัส PIN เป็นตัวเลขที่มากกว่า 0', 'error');
                return false;
            }
            
            return true;
            
        case 'gps':
            // ตรวจสอบการตั้งค่า GPS
            if (!data.gps_distance || isNaN(data.gps_distance) || data.gps_distance < 1) {
                showAlert('กรุณากรอกระยะห่างเป็นตัวเลขที่มากกว่า 0', 'error');
                return false;
            }
            
            if (!data.gps_latitude || !data.gps_longitude) {
                showAlert('กรุณากรอกพิกัด (ละติจูดและลองจิจูด)', 'error');
                return false;
            }
            
            return true;
            
        case 'time':
            // ตรวจสอบการตั้งค่าเวลา
            if (!data.time_start || !data.time_end) {
                showAlert('กรุณากรอกเวลาเริ่มต้นและสิ้นสุดการเช็คชื่อ', 'error');
                return false;
            }
            
            return true;
            
        default:
            // สำหรับการตั้งค่าอื่นๆ ไม่มีการตรวจสอบเพิ่มเติม
            return true;
    }
}

/**
 * ดึงข้อมูลสำหรับสร้าง modal ตามประเภท
 * @param {string} type - ประเภทการตั้งค่า
 * @returns {object|null} ข้อมูลสำหรับสร้าง modal หรือ null ถ้าไม่พบ
 */
function getModalData(type) {
    // ข้อมูลสำหรับสร้าง modal ตามประเภท
    const modalData = {
        // การตั้งค่าโปรไฟล์
        profile: {
            title: 'แก้ไขข้อมูลส่วนตัว',
            content: `
                <div class="profile-image-container">
                    <div class="profile-image-preview">
                        <span class="profile-image-initial">ค</span>
                        <div class="profile-image-upload">
                            <span class="material-icons">photo_camera</span>
                            <input type="file" name="profile_image" accept="image/*" onchange="previewProfileImage(this)">
                        </div>
                    </div>
                    <div class="profile-image-text">คลิกที่รูปเพื่ออัพโหลดรูปโปรไฟล์</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="title">คำนำหน้า</label>
                    <input type="text" class="form-control" id="title" name="title" value="อาจารย์">
                </div>
                <div class="form-group">
                    <label class="form-label" for="firstname">ชื่อ</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" value="ใจดี" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="lastname">นามสกุล</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" value="มากเมตตา" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="0891234567" required pattern="[0-9]{10}">
                    <div class="form-text">กรุณากรอกเบอร์โทรศัพท์ 10 หลัก</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">อีเมล</label>
                    <input type="email" class="form-control" id="email" name="email" value="teacher@prasat.ac.th" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="line_id">LINE ID</label>
                    <input type="text" class="form-control" id="line_id" name="line_id" value="teacher_prasat">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department">กลุ่มสาระการเรียนรู้</label>
                    <select class="form-select" id="department" name="department">
                        <option value="คณิตศาสตร์" selected>คณิตศาสตร์</option>
                        <option value="วิทยาศาสตร์และเทคโนโลยี">วิทยาศาสตร์และเทคโนโลยี</option>
                        <option value="ภาษาไทย">ภาษาไทย</option>
                        <option value="ภาษาต่างประเทศ">ภาษาต่างประเทศ</option>
                        <option value="สังคมศึกษา">สังคมศึกษา</option>
                        <option value="สุขศึกษาและพลศึกษา">สุขศึกษาและพลศึกษา</option>
                        <option value="ศิลปะ">ศิลปะ</option>
                        <option value="การงานอาชีพ">การงานอาชีพ</option>
                    </select>
                </div>
            `
        },
        
        // การเปลี่ยนรหัสผ่าน
        password: {
            title: 'เปลี่ยนรหัสผ่าน',
            content: `
                <div class="form-group">
                    <label class="form-label" for="old_password">รหัสผ่านเดิม</label>
                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">รหัสผ่านใหม่</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                    <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            `
        },
        
        // การตั้งค่ารหัส PIN
        pin: {
            title: 'การตั้งค่ารหัส PIN',
            content: `
                <div class="form-group">
                    <label class="form-label" for="pin_expiration">เวลาหมดอายุของรหัส PIN (นาที)</label>
                    <input type="number" class="form-control" id="pin_expiration" name="pin_expiration" value="10" min="1" max="60" required>
                    <div class="form-text">กำหนดเวลาหมดอายุของรหัส PIN สำหรับการเช็คชื่อ</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pin_length">ความยาวของรหัส PIN</label>
                    <select class="form-select" id="pin_length" name="pin_length">
                        <option value="4" selected>4 หลัก</option>
                        <option value="6">6 หลัก</option>
                    </select>
                    <div class="form-text">กำหนดความยาวของรหัส PIN สำหรับการเช็คชื่อ</div>
                </div>
                <div class="form-group">
                    <label class="form-label">การรีเซ็ตรหัส PIN อัตโนมัติ</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="auto_reset_yes" name="auto_reset" value="1" checked>
                            <label for="auto_reset_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="auto_reset_no" name="auto_reset" value="0">
                            <label for="auto_reset_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">สร้างรหัส PIN ใหม่โดยอัตโนมัติเมื่อหมดเวลา</div>
                </div>
            `
        },
        
        // การตั้งค่า QR Code
        qr: {
            title: 'การตั้งค่า QR Code',
            content: `
                <div class="form-group">
                    <label class="form-label" for="qr_expiration">เวลาหมดอายุของ QR Code (นาที)</label>
                    <input type="number" class="form-control" id="qr_expiration" name="qr_expiration" value="15" min="1" max="60" required>
                    <div class="form-text">กำหนดเวลาหมดอายุของ QR Code สำหรับการเช็คชื่อ</div>
                </div>
                <div class="form-group">
                    <label class="form-label">ขนาดของ QR Code</label>
                    <select class="form-select" id="qr_size" name="qr_size">
                        <option value="small">เล็ก</option>
                        <option value="medium" selected>กลาง</option>
                        <option value="large">ใหญ่</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">การรีเซ็ต QR Code อัตโนมัติ</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="qr_auto_reset_yes" name="qr_auto_reset" value="1" checked>
                            <label for="qr_auto_reset_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="qr_auto_reset_no" name="qr_auto_reset" value="0">
                            <label for="qr_auto_reset_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">สร้าง QR Code ใหม่โดยอัตโนมัติเมื่อหมดเวลา</div>
                </div>
            `
        },
        
        // การตั้งค่า GPS
        gps: {
            title: 'การตั้งค่า GPS',
            content: `
                <div class="form-group">
                    <label class="form-label" for="gps_distance">ระยะห่างสูงสุด (เมตร)</label>
                    <input type="number" class="form-control" id="gps_distance" name="gps_distance" value="100" min="1" required>
                    <div class="form-text">ระยะห่างสูงสุดที่อนุญาตให้นักเรียนเช็คชื่อได้</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gps_latitude">ละติจูด</label>
                    <input type="text" class="form-control" id="gps_latitude" name="gps_latitude" value="14.967500" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gps_longitude">ลองจิจูด</label>
                    <input type="text" class="form-control" id="gps_longitude" name="gps_longitude" value="102.076683" required>
                </div>
                <div class="form-group">
                    <label class="form-label">การบังคับใช้ตำแหน่ง GPS</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="gps_enforce_yes" name="gps_enforce" value="1" checked>
                            <label for="gps_enforce_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="gps_enforce_no" name="gps_enforce" value="0">
                            <label for="gps_enforce_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">บังคับให้นักเรียนต้องอยู่ในระยะที่กำหนดเท่านั้น</div>
                </div>
            `
        },
        
        // การตั้งค่าเวลา
        time: {
            title: 'เวลาเช็คชื่อและตารางเวลา',
            content: `
                <div class="form-group">
                    <label class="form-label" for="time_start">เวลาเริ่มต้นการเช็คชื่อ</label>
                    <input type="time" class="form-control" id="time_start" name="time_start" value="07:30" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="time_end">เวลาสิ้นสุดการเช็คชื่อ</label>
                    <input type="time" class="form-control" id="time_end" name="time_end" value="08:30" required>
                </div>
                <div class="form-group">
                    <label class="form-label">เช็คขาดเรียนโดยอัตโนมัติ</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="auto_absent_yes" name="auto_absent" value="1" checked>
                            <label for="auto_absent_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="auto_absent_no" name="auto_absent" value="0">
                            <label for="auto_absent_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">เช็คนักเรียนเป็นขาดเรียนโดยอัตโนมัติเมื่อหมดเวลา</div>
                </div>
                <div class="form-group">
                    <label class="form-label">แจ้งเตือนผู้ปกครองโดยอัตโนมัติเมื่อนักเรียนขาดเรียน</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="absent_notification_yes" name="absent_notification" value="1" checked>
                            <label for="absent_notification_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="absent_notification_no" name="absent_notification" value="0">
                            <label for="absent_notification_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                </div>
            `
        },
        
        // การตั้งค่าธีมและการแสดงผล
        appearance: {
            title: 'ธีมและการแสดงผล',
            content: `
                <div class="form-group">
                    <label class="form-label">ธีม</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="theme_light" name="theme" value="light" checked>
                            <label for="theme_light">สว่าง</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="theme_dark" name="theme" value="dark">
                            <label for="theme_dark">มืด</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="theme_auto" name="theme" value="auto">
                            <label for="theme_auto">อัตโนมัติ</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">ขนาดตัวอักษร</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="font_size_small" name="font_size" value="small">
                            <label for="font_size_small">เล็ก</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="font_size_medium" name="font_size" value="medium" checked>
                            <label for="font_size_medium">กลาง</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="font_size_large" name="font_size" value="large">
                            <label for="font_size_large">ใหญ่</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">แสดงผลหน้าแดชบอร์ด</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="dashboard_card" name="dashboard_view" value="card" checked>
                            <label for="dashboard_card">การ์ด</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="dashboard_list" name="dashboard_view" value="list">
                            <label for="dashboard_list">รายการ</label>
                        </div>
                    </div>
                </div>
            `
        },
        
        // การตั้งค่าภาษา
        language: {
            title: 'ภาษา',
            content: `
                <div class="form-group">
                    <label class="form-label">ภาษาที่ใช้ในแอปพลิเคชัน</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="lang_th" name="language" value="th" checked>
                            <label for="lang_th">ไทย</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="lang_en" name="language" value="en">
                            <label for="lang_en">English</label>
                        </div>
                    </div>
                </div>
            `
        },
        
        // การจัดการข้อมูล
        data: {
            title: 'จัดการข้อมูล',
            content: `
                <div class="form-group">
                    <label class="form-label">ล้างแคช</label>
                    <button type="button" class="form-control" onclick="clearCache()">ล้างแคช</button>
                    <div class="form-text">ล้างข้อมูลแคชเพื่อเพิ่มประสิทธิภาพของแอปพลิเคชัน</div>
                </div>
                <div class="form-group">
                    <label class="form-label">ข้อมูลที่จัดเก็บในอุปกรณ์</label>
                    <div class="form-text">พื้นที่ที่ใช้ไป: 5.2 MB</div>
                </div>
                <div class="form-group">
                    <label class="form-label">ล้างข้อมูลทั้งหมด</label>
                    <button type="button" class="form-control" onclick="confirmClearAllData()">ล้างข้อมูลทั้งหมด</button>
                    <div class="form-text">ลบข้อมูลทั้งหมดที่จัดเก็บในอุปกรณ์นี้ (ไม่สามารถกู้คืนได้)</div>
                </div>
            `
        },
        
        // การตั้งค่าความเป็นส่วนตัวและความปลอดภัย
        privacy: {
            title: 'ความเป็นส่วนตัวและความปลอดภัย',
            content: `
                <div class="form-group">
                    <label class="form-label">บันทึกการเข้าสู่ระบบ</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="login_log_yes" name="login_log" value="1" checked>
                            <label for="login_log_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="login_log_no" name="login_log" value="0">
                            <label for="login_log_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">บันทึกประวัติการเข้าสู่ระบบของคุณ</div>
                </div>
                <div class="form-group">
                    <label class="form-label">การแชร์ข้อมูลการใช้งาน</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="share_usage_yes" name="share_usage" value="1" checked>
                            <label for="share_usage_yes">เปิดใช้งาน</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="share_usage_no" name="share_usage" value="0">
                            <label for="share_usage_no">ปิดใช้งาน</label>
                        </div>
                    </div>
                    <div class="form-text">แชร์ข้อมูลการใช้งานเพื่อปรับปรุงแอปพลิเคชัน</div>
                </div>
                <div class="form-group">
                    <label class="form-label">อุปกรณ์ที่เข้าสู่ระบบ</label>
                    <button type="button" class="form-control" onclick="viewLoggedInDevices()">ดูอุปกรณ์ที่เข้าสู่ระบบ</button>
                </div>
            `
        },
        
        // การตั้งค่าข้อมูลนักเรียน
        class: {
            title: 'ข้อมูลชั้นเรียน',
            content: `
                <div class="form-group">
                    <label class="form-label">ห้องเรียนที่รับผิดชอบ</label>
                    <select class="form-select" id="primary_class" name="primary_class">
                        <option value="1" selected>ม.6/1</option>
                        <option value="2">ม.6/2</option>
                        <option value="3">ม.6/3</option>
                    </select>
                    <div class="form-text">เลือกห้องเรียนหลักที่คุณรับผิดชอบ</div>
                </div>
                <div class="form-group">
                    <label class="form-label">การแสดงรายชื่อนักเรียน</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="student_view_number" name="student_view" value="number" checked>
                            <label for="student_view_number">เรียงตามเลขที่</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="student_view_name" name="student_view" value="name">
                            <label for="student_view_name">เรียงตามชื่อ</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">การแสดงผลสถิติการเข้าแถว</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="stats_view_percentage" name="stats_view" value="percentage" checked>
                            <label for="stats_view_percentage">แสดงเป็นเปอร์เซ็นต์</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="stats_view_days" name="stats_view" value="days">
                            <label for="stats_view_days">แสดงเป็นจำนวนวัน</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">เกณฑ์ความเสี่ยงตกกิจกรรม</label>
                    <input type="number" class="form-control" id="at_risk_threshold" name="at_risk_threshold" value="75" min="1" max="100" required>
                    <div class="form-text">กำหนดเกณฑ์อัตราการเข้าแถวขั้นต่ำ (%) ที่ไม่เสี่ยงตกกิจกรรม</div>
                </div>
            `
        }
    };
    
    // ส่งคืนข้อมูลตามประเภท
    return modalData[type] || null;
}

/**
 * เพิ่ม event listeners สำหรับ form elements
 * @param {string} type - ประเภทการตั้งค่า
 */
function initFormElements(type) {
    // ตามประเภทการตั้งค่า
    switch (type) {
        case 'profile':
            // บังคับให้กรอกเบอร์โทรศัพท์เป็นตัวเลขเท่านั้น
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                });
            }
            break;
            
        case 'password':
            // ตรวจสอบว่ารหัสผ่านใหม่และยืนยันรหัสผ่านตรงกัน
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (this.value !== newPassword.value) {
                        this.setCustomValidity('รหัสผ่านไม่ตรงกัน');
                    } else {
                        this.setCustomValidity('');
                    }
                });
                
                newPassword.addEventListener('input', function() {
                    if (confirmPassword.value && this.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('รหัสผ่านไม่ตรงกัน');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                });
            }
            break;
            
        case 'gps':
            // ตรวจสอบว่าละติจูดและลองจิจูดอยู่ในช่วงที่ถูกต้อง
            const latitudeInput = document.getElementById('gps_latitude');
            const longitudeInput = document.getElementById('gps_longitude');
            
            if (latitudeInput) {
                latitudeInput.addEventListener('input', function() {
                    const lat = parseFloat(this.value);
                    if (isNaN(lat) || lat < -90 || lat > 90) {
                        this.setCustomValidity('ละติจูดต้องอยู่ระหว่าง -90 ถึง 90');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            if (longitudeInput) {
                longitudeInput.addEventListener('input', function() {
                    const lng = parseFloat(this.value);
                    if (isNaN(lng) || lng < -180 || lng > 180) {
                        this.setCustomValidity('ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            break;
            
        case 'time':
            // ตรวจสอบว่าเวลาเริ่มต้นมาก่อนเวลาสิ้นสุด
            const timeStartInput = document.getElementById('time_start');
            const timeEndInput = document.getElementById('time_end');
            
            if (timeStartInput && timeEndInput) {
                timeEndInput.addEventListener('input', function() {
                    if (this.value <= timeStartInput.value) {
                        this.setCustomValidity('เวลาสิ้นสุดต้องมาหลังเวลาเริ่มต้น');
                    } else {
                        this.setCustomValidity('');
                    }
                });
                
                timeStartInput.addEventListener('input', function() {
                    if (timeEndInput.value && this.value >= timeEndInput.value) {
                        timeEndInput.setCustomValidity('เวลาสิ้นสุดต้องมาหลังเวลาเริ่มต้น');
                    } else {
                        timeEndInput.setCustomValidity('');
                    }
                });
            }
            break;
    }
}

/**
 * แสดงตัวอย่างรูปโปรไฟล์
 * @param {HTMLInputElement} input - input element สำหรับอัพโหลดรูป
 */
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.querySelector('.profile-image-preview');
            const initial = preview.querySelector('.profile-image-initial');
            
            // ซ่อนตัวอักษรเริ่มต้น
            if (initial) {
                initial.style.display = 'none';
            }
            
            // ตรวจสอบว่ามีรูปอยู่แล้วหรือไม่
            let img = preview.querySelector('img');
            
            if (!img) {
                // สร้าง image element ใหม่
                img = document.createElement('img');
                preview.insertBefore(img, preview.firstChild);
            }
            
            // แสดงรูปตัวอย่าง
            img.src = e.target.result;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * เปิด/ปิดการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน
 */
function toggleNotification(type) {
    // ในระบบจริงจะมีการบันทึกการตั้งค่าไปยัง server
    // แต่ในตัวอย่างนี้จะเป็นเพียงการแสดงข้อความแจ้งเตือน
    
    // ดึงสถานะปัจจุบัน
    const checkbox = document.querySelector(`input[onchange="toggleNotification('${type}')"]`);
    const isEnabled = checkbox.checked;
    
    // แสดงข้อความแจ้งเตือน
    showAlert(`${isEnabled ? 'เปิด' : 'ปิด'}การแจ้งเตือน ${getNotificationName(type)} แล้ว`, 'success');
    
    // บันทึกการตั้งค่าใน localStorage
    localStorage.setItem(`notification_${type}`, isEnabled ? '1' : '0');
}

/**
 * ดึงชื่อการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน
 * @returns {string} ชื่อการแจ้งเตือน
 */
function getNotificationName(type) {
    const names = {
        student_present: 'นักเรียนเข้าแถว',
        student_absent: 'นักเรียนขาดแถว',
        school_announcement: 'ประกาศจากโรงเรียน',
        parent_message: 'ข้อความจากผู้ปกครอง',
        at_risk_warning: 'นักเรียนเสี่ยงตกกิจกรรม',
        attendance_summary: 'สรุปการเข้าแถวประจำวัน'
    };
    
    return names[type] || type;
}

/**
 * ล้างแคช
 */
function clearCache() {
    // แสดงข้อความกำลังล้างแคช
    showAlert('กำลังล้างแคช...', 'info');
    
    // จำลองการล้างแคช
    setTimeout(() => {
        showAlert('ล้างแคชเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * ยืนยันการล้างข้อมูลทั้งหมด
 */
function confirmClearAllData() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะล้างข้อมูลทั้งหมด? การกระทำนี้ไม่สามารถยกเลิกได้')) {
        clearAllData();
    }
}

/**
 * ล้างข้อมูลทั้งหมด
 */
function clearAllData() {
    // แสดงข้อความกำลังล้างข้อมูล
    showAlert('กำลังล้างข้อมูลทั้งหมด...', 'info');
    
    // จำลองการล้างข้อมูล
    setTimeout(() => {
        showAlert('ล้างข้อมูลทั้งหมดเรียบร้อยแล้ว', 'success');
    }, 2000);
}

/**
 * ดูอุปกรณ์ที่เข้าสู่ระบบ
 */
function viewLoggedInDevices() {
    // ในระบบจริงจะดึงข้อมูลจาก server
    // แต่ในตัวอย่างนี้จะเป็นเพียงการแสดง alert
    alert('อุปกรณ์ที่เข้าสู่ระบบ:\n1. iPhone 13 Pro - เข้าสู่ระบบเมื่อ 16/03/2025\n2. MacBook Pro - เข้าสู่ระบบเมื่อ 15/03/2025');
}

/**
 * ยืนยันการออกจากระบบ
 */
function confirmLogout() {
    const modal = document.getElementById('logout-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ปิด Modal
 * @param {string} modalId - ID ของ Modal ที่ต้องการปิด
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * ออกจากระบบ
 */
function logout() {
    // แสดงข้อความกำลังออกจากระบบ
    showAlert('กำลังออกจากระบบ...', 'info');
    
    // จำลองการออกจากระบบ
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 1000);
}

/**
 * ย้อนกลับ
 */
function goBack() {
    window.history.back();
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
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
    alert.className = `alert ${type}`;
    
    // ไอคอนตามประเภท
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    else if (type === 'warning') icon = 'warning';
    else if (type === 'error') icon = 'error';
    
    alert.innerHTML = `
        <div class="alert-icon">
            <span class="material-icons">${icon}</span>
        </div>
        <div class="alert-content">
            <div class="alert-message">${message}</div>
        </div>
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
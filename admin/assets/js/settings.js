/**
 * settings.js - JavaScript สำหรับการจัดการหน้าตั้งค่าระบบ STUDENT-Prasat
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initializeTabs();
    
    // ตั้งค่าปุ่มและฟอร์ม
    setupSettingsControls();
});

/**
 * เริ่มต้นการทำงานของแท็บ
 */
function initializeTabs() {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            showTab(tabId);
        });
    });
}

/**
 * แสดงแท็บที่เลือก
 * @param {string} tabId - ID ของแท็บที่ต้องการแสดง
 */
function showTab(tabId) {
    // ซ่อนแท็บทั้งหมด
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ยกเลิกการเลือกแท็บทั้งหมด
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
    document.getElementById(tabId + '-tab').classList.add('active');
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
}

/**
 * ตั้งค่าการควบคุมต่างๆ ในหน้าตั้งค่า
 */
function setupSettingsControls() {
    // เพิ่มการตรวจสอบการเปลี่ยนแปลงการตั้งค่า
    const settingsInputs = document.querySelectorAll('input, select, textarea');
    settingsInputs.forEach(input => {
        input.addEventListener('change', markSettingsModified);
    });

    // ปุ่มบันทึกการตั้งค่า
    const saveButton = document.querySelector('.btn-primary');
    if (saveButton) {
        saveButton.addEventListener('click', saveSettings);
    }
}

/**
 * ทำเครื่องหมายว่ามีการแก้ไขการตั้งค่า
 */
function markSettingsModified() {
    const saveButton = document.querySelector('.btn-primary');
    if (saveButton) {
        saveButton.classList.add('btn-warning');
        saveButton.innerHTML = `
            <span class="material-icons">warning</span>
            บันทึกการเปลี่ยนแปลง
        `;
    }
}

/**
 * บันทึกการตั้งค่า
 */
function saveSettings() {
    // รวบรวมข้อมูลการตั้งค่า
    const settingsData = collectSettingsData();
    
    // แสดง loading
    showLoadingIndicator();
    
    // ส่งข้อมูลไปบันทึกที่เซิร์ฟเวอร์
    fetch('/api/save-settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settingsData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('บันทึกการตั้งค่าเรียบร้อยแล้ว');
            resetModifiedState();
        } else {
            showErrorMessage(result.message || 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

/**
 * รวบรวมข้อมูลการตั้งค่าจากหน้าเว็บ
 * @returns {Object} ข้อมูลการตั้งค่าทั้งหมด
 */
function collectSettingsData() {
    const settings = {
        system: {},
        notification: {},
        attendance: {},
        gps: {},
        line: {}
    };

    // รวบรวมการตั้งค่าระบบ
    settings.system = {
        schoolName: document.querySelector('input[value="โรงเรียนประสาทวิทยาคม"]').value,
        schoolCode: document.querySelector('input[value="10001"]').value,
        schoolAddress: document.querySelector('input[value="123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000"]').value,
        schoolPhone: document.querySelector('input[value="044-511234"]').value,
        currentAcademicYear: document.querySelector('select[value="2568 (2025)"]').value,
        currentSemester: document.querySelector('select[value="ภาคเรียนที่ 2/2568"]').value,
        language: document.querySelector('select[value="ไทย"]').value,
        theme: document.querySelector('select[value="เขียว (ค่าเริ่มต้น)"]').value
    };

    // รวบรวมการตั้งค่าการแจ้งเตือน
    settings.notification = {
        enableNotifications: document.getElementById('enable-notifications').checked,
        enableCriticalNotifications: document.getElementById('critical-notifications').checked,
        missedAttendanceThreshold: document.querySelector('select[value="5 ครั้ง"]').value,
        notificationFrequency: document.querySelector('select[value="สัปดาห์ละครั้ง"]').value,
        autoNotifications: document.getElementById('auto-notifications').checked,
        notificationChannels: {
            line: document.getElementById('line-notification').checked,
            sms: document.getElementById('sms-notification').checked,
            email: document.getElementById('email-notification').checked
        }
    };

    // รวบรวมการตั้งค่าการเช็คชื่อ
    settings.attendance = {
        minimumAttendanceRate: document.querySelector('select[value="80%"]').value,
        attendancePeriod: document.querySelector('select[value="ภาคเรียน"]').value,
        countWeekends: document.getElementById('count-weekend').checked,
        enableQrCode: document.getElementById('enable-qr').checked,
        enablePinCode: document.getElementById('enable-pin').checked,
        pinDuration: document.querySelector('select[value="10 นาที"]').value,
        pinUsageLimit: document.querySelector('select[value="ใช้ได้ 3 ครั้ง"]').value,
        checkInStartTime: document.querySelector('input[type="time"]').value,
        allowLateCheck: document.getElementById('late-check').checked,
        lateCheckDuration: document.querySelector('select[value="30 นาที"]').value,
        lateCheckPenalty: document.querySelector('select[value="ลดคะแนนความประพฤติ"]').value
    };

    // รวบรวมการตั้งค่า GPS
    settings.gps = {
        schoolLatitude: document.querySelector('input[value="14.0065"]').value,
        schoolLongitude: document.querySelector('input[value="100.5018"]').value,
        allowedRadius: document.querySelector('select[value="100 เมตร"]').value,
        gpsAccuracy: document.querySelector('select[value="±10 เมตร"]').value,
        requireGpsVerification: document.getElementById('gps-required').checked,
        requirePhotoOnCheckIn: document.getElementById('gps-photo-required').checked,
        allowHomeCheck: document.getElementById('allow-home-check').checked,
        allowParentVerification: document.getElementById('allow-parent-verification').checked
    };

    // รวบรวมการตั้งค่า LINE
    settings.line = {
        officialAccountName: document.querySelector('input[value="SADD-Prasat"]').value,
        officialAccountId: document.querySelector('input[value="@sadd-prasat"]').value,
        welcomeMessage: document.querySelector('textarea[placeholder*="ยินดีต้อนรับ"]').value,
        channelAccessToken: document.querySelector('input[placeholder="ใส่ Channel Access Token"]').value,
        channelSecret: document.querySelector('input[placeholder="ใส่ Channel Secret"]').value,
        enableLineLogin: document.getElementById('enable-line-login').checked,
        enableWebhook: document.getElementById('enable-line-webhook').checked,
        studentOAName: document.querySelector('input[value="STD-Prasat"]').value,
        teacherOAName: document.querySelector('input[value="Teacher-Prasat"]').value
    };

    return settings;
}

/**
 * แสดงข้อความสำเร็จ
 * @param {string} message - ข้อความที่ต้องการแสดง
 */
function showSuccessMessage(message) {
    // ในทางปฏิบัติจริงควรใช้ระบบแจ้งเตือนที่ออกแบบไว้
    alert(message);
}

/**
 * แสดงข้อความข้อผิดพลาด
 * @param {string} message - ข้อความข้อผิดพลาด
 */
function showErrorMessage(message) {
    // ในทางปฏิบัติจริงควรใช้ระบบแจ้งเตือนที่ออกแบบไว้
    alert(message);
}

/**
 * แสดงตัวบ่งชี้การโหลด
 */
function showLoadingIndicator() {
    const saveButton = document.querySelector('.btn-primary');
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML = `
            <span class="material-icons spinning">autorenew</span>
            กำลังบันทึก...
        `;
    }
}

/**
 * ซ่อนตัวบ่งชี้การโหลด
 */
function hideLoadingIndicator() {
    const saveButton = document.querySelector('.btn-primary');
    if (saveButton) {
        saveButton.disabled = false;
        saveButton.innerHTML = `
            <span class="material-icons">save</span>
            บันทึกการตั้งค่า
        `;
    }
}

/**
 * รีเซ็ตสถานะการแก้ไข
 */
function resetModifiedState() {
    const saveButton = document.querySelector('.btn-primary');
    if (saveButton) {
        saveButton.classList.remove('btn-warning');
        saveButton.innerHTML = `
            <span class="material-icons">save</span>
            บันทึกการตั้งค่า
        `;
    }
}

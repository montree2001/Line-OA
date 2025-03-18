/**
 * teacher-reports.js - สคริปต์เฉพาะสำหรับหน้ารายงานของระบบ Teacher-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้ารายงาน
    initReportsPage();
});

/**
 * เริ่มต้นการทำงานของหน้ารายงาน
 */
function initReportsPage() {
    // เริ่มต้นแท็บเมนู
    initTabMenu();
}

/**
 * เริ่มต้นแท็บเมนู
 */
function initTabMenu() {
    // ตั้งค่าเริ่มต้นให้แสดงแท็บ table
    switchTab('table');
}

/**
 * สลับแท็บที่แสดง
 * @param {string} tabName - ชื่อแท็บ (table/graph/calendar)
 */
function switchTab(tabName) {
    // ซ่อนทุกแท็บ
    const tableView = document.getElementById('table-view');
    const graphView = document.getElementById('graph-view');
    const calendarView = document.getElementById('calendar-view');
    
    if (tableView) tableView.style.display = 'none';
    if (graphView) graphView.style.display = 'none';
    if (calendarView) calendarView.style.display = 'none';
    
    // แสดงแท็บที่เลือก
    if (tabName === 'table' && tableView) {
        tableView.style.display = 'block';
    } else if (tabName === 'graph' && graphView) {
        graphView.style.display = 'block';
    } else if (tabName === 'calendar' && calendarView) {
        calendarView.style.display = 'block';
    }
    
    // อัพเดทปุ่มแท็บ
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // เพิ่มคลาส active ให้กับปุ่มที่เลือก
    const selectedButton = document.querySelector(`.tab-button:nth-child(${
        tabName === 'table' ? 1 : tabName === 'graph' ? 2 : 3
    })`);
    
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ดึงเดือนปัจจุบัน
    const monthSelect = document.getElementById('month-select');
    const currentMonth = monthSelect ? monthSelect.value : '';
    
    // สร้าง URL สำหรับการนำทาง
    let url = 'reports.php?class_id=' + classId;
    
    // เพิ่มพารามิเตอร์เดือนถ้ามี
    if (currentMonth) {
        url += '&month=' + currentMonth;
    }
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * เปลี่ยนเดือนที่แสดง
 */
function changeMonth() {
    const monthSelect = document.getElementById('month-select');
    if (!monthSelect) return;
    
    // ดึง class_id จาก URL หรือใช้ค่าเริ่มต้น
    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่พร้อมพารามิเตอร์
    const url = 'reports.php?class_id=' + classId + '&month=' + monthSelect.value;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ไปยังเดือนก่อนหน้า
 */
function prevMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear();
    
    // คำนวณเดือนก่อนหน้า
    month--;
    if (month < 1) {
        month = 12;
        year--;
    }
    
    // ดึง class_id
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่
    const url = 'reports.php?class_id=' + classId + '&month=' + month + '&year=' + year;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ไปยังเดือนถัดไป
 */
function nextMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear();
    
    // คำนวณเดือนถัดไป
    month++;
    if (month > 12) {
        month = 1;
        year++;
    }
    
    // ดึง class_id
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่
    const url = 'reports.php?class_id=' + classId + '&month=' + month + '&year=' + year;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('student-search');
    if (!searchInput) return;
    
    const searchText = searchInput.value.toLowerCase();
    const studentTable = document.querySelector('.student-table');
    if (!studentTable) return;
    
    const rows = studentTable.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        if (name.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * ดูรายละเอียดนักเรียน
 * @param {number} studentId - ID ของนักเรียน
 */
function viewStudentDetail(studentId) {
    // ในระบบจริงจะทำการดึงข้อมูลจากเซิร์ฟเวอร์
    // ในตัวอย่างนี้จะแสดง Modal เท่านั้น
    const modal = document.getElementById('student-detail-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // ในระบบจริงจะเติมข้อมูลนักเรียนตาม ID
    console.log('ดูรายละเอียดนักเรียน ID:', studentId);
}

/**
 * ติดต่อผู้ปกครอง
 * @param {number} studentId - ID ของนักเรียน
 */
function contactParent(studentId) {
    // แสดงหน้าต่างติดต่อผู้ปกครอง
    const modal = document.getElementById('contact-parent-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // ในระบบจริงจะเติมข้อมูลผู้ปกครองของนักเรียนตาม ID
    console.log('ติดต่อผู้ปกครองของนักเรียน ID:', studentId);
}

/**
 * ติดต่อผู้ปกครองของนักเรียนที่กำลังดูรายละเอียด
 */
function contactStudentParent() {
    // ปิด Modal รายละเอียดนักเรียน
    closeModal('student-detail-modal');
    
    // เปิด Modal ติดต่อผู้ปกครอง
    const modal = document.getElementById('contact-parent-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ส่งข้อความถึงผู้ปกครอง
 */
function sendParentMessage() {
    const messageTextarea = document.getElementById('parent-message');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value.trim();
    if (!message) {
        alert('กรุณาระบุข้อความที่ต้องการส่งถึงผู้ปกครอง');
        return;
    }
    
    // ในระบบจริงจะส่งข้อความไปยัง LINE ของผู้ปกครอง
    console.log('ส่งข้อความถึงผู้ปกครอง:', message);
    
    // ปิด Modal
    closeModal('contact-parent-modal');
    
    // แสดงการแจ้งเตือน
    showAlert('ส่งข้อความถึงผู้ปกครองเรียบร้อยแล้ว', 'success');
}

/**
 * พิมพ์รายงานนักเรียนรายบุคคล
 */
function printStudentReport() {
    // ในระบบจริงจะเปิดหน้าต่างพิมพ์
    window.print();
}

/**
 * แสดง Modal แจ้งเตือนผู้ปกครอง
 */
function notifyParents() {
    const modal = document.getElementById('notify-parents-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
function sendNotification() {
    // รับประเภทการแจ้งเตือนที่เลือก
    const notificationType = document.querySelector('input[name="notification-type"]:checked').value;
    
    // รับข้อความแจ้งเตือน
    const messageTextarea = document.getElementById('message-text');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value.trim();
    if (!message) {
        alert('กรุณาระบุข้อความแจ้งเตือน');
        return;
    }
    
    // ในระบบจริงจะส่งข้อความไปยัง LINE ของผู้ปกครอง
    console.log('ส่งการแจ้งเตือนไปยังผู้ปกครอง:', notificationType, message);
    
    // ปิด Modal
    closeModal('notify-parents-modal');
    
    // แสดงการแจ้งเตือน
    if (notificationType === 'all') {
        showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมดเรียบร้อยแล้ว', 'success');
    } else {
        showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองนักเรียนที่มีปัญหาเรียบร้อยแล้ว', 'success');
    }
}

/**
 * ดาวน์โหลดรายงานการเข้าแถว
 */
function downloadReport() {
    // ในระบบจริงจะสร้างไฟล์รายงาน
    showAlert('กำลังดาวน์โหลดรายงานการเข้าแถว...', 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดรายงานเรียบร้อยแล้ว', 'success');
    }, 2000);
}

/**
 * ดาวน์โหลดกราฟรายวัน
 */
function downloadDailyChart() {
    showAlert('กำลังดาวน์โหลดกราฟอัตราการเข้าแถวรายวัน...', 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดกราฟเรียบร้อยแล้ว', 'success');
    }, 1500);
}

/**
 * พิมพ์กราฟรายวัน
 */
function printDailyChart() {
    showAlert('กำลังเตรียมพิมพ์กราฟอัตราการเข้าแถวรายวัน...', 'info');
    
    // จำลองการพิมพ์
    setTimeout(() => {
        window.print();
    }, 1000);
}

/**
 * ดาวน์โหลดกราฟนักเรียนรายคน
 */
function downloadStudentChart() {
    showAlert('กำลังดาวน์โหลดกราฟอัตราการเข้าแถวรายคน...', 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดกราฟเรียบร้อยแล้ว', 'success');
    }, 1500);
}

/**
 * พิมพ์กราฟนักเรียนรายคน
 */
function printStudentChart() {
    showAlert('กำลังเตรียมพิมพ์กราฟอัตราการเข้าแถวรายคน...', 'info');
    
    // จำลองการพิมพ์
    setTimeout(() => {
        window.print();
    }, 1000);
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
 * ย้อนกลับหน้าที่แล้ว
 */
function goBack() {
    window.history.back();
}

/**
 * แสดงตัวเลือกเพิ่มเติม
 */
function toggleOptions() {
    // ในระบบจริงจะแสดงเมนูเพิ่มเติม
    showAlert('เมนูเพิ่มเติม', 'info');
}

/**
 * แสดงข้อความแจ้งเตือน (อิงจาก main.js)
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // เรียกใช้ฟังก์ชันจาก main.js (ถ้ามี)
    if (typeof window.showAlert === 'function') {
        window.showAlert(message, type);
        return;
    }
    
    // ถ้าไม่มีฟังก์ชันใน main.js ให้สร้าง alert container
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert element
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    
    // กำหนดไอคอนตามประเภท
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    else if (type === 'warning') icon = 'warning';
    else if (type === 'error') icon = 'error';
    
    // สร้าง HTML สำหรับ alert
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
    
    // กำหนดการปิด alert เมื่อคลิกปุ่มปิด
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alertContainer.removeChild(alert);
    });
    
    // ปิด alert โดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alertContainer.removeChild(alert);
        }
    }, 5000);
}
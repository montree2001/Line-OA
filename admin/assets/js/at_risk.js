/**
 * at_risk.js - JavaScript เฉพาะสำหรับหน้านักเรียนที่เสี่ยงตกกิจกรรม
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // เริ่มต้นแผนภูมิ (ถ้ามี)
    initCharts();
    
    // ตั้งค่าตาราง
    initTables();
});

/**
 * ตั้งค่าแท็บต่างๆ ในหน้า
 */
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            showTab(tabId);
        });
    });
}

/**
 * แสดงแท็บที่ต้องการและซ่อนแท็บอื่นๆ
 * 
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
 * แสดงรายละเอียดนักเรียน
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function showStudentDetail(studentId) {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    console.log(`Loading student details for student ID: ${studentId}`);
    
    // จำลองการโหลดข้อมูล (ในการใช้งานจริงจะใช้ AJAX)
    showModal('studentDetailModal');
}

/**
 * แสดงโมดัลส่งข้อความ
 * 
 * @param {number} studentId - รหัสนักเรียน
 */
function showSendMessageModal(studentId) {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    console.log(`Loading message form for student ID: ${studentId}`);
    
    // จำลองการโหลดข้อมูล (ในการใช้งานจริงจะใช้ AJAX)
    showModal('sendMessageModal');
}

/**
 * แสดงโมดัลส่งข้อความกลุ่ม
 */
function showBulkNotificationModal() {
    showModal('bulkNotificationModal');
}

/**
 * ส่งข้อความแจ้งเตือนรายบุคคล
 */
function sendIndividualMessage() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    const messageText = document.getElementById('modalMessageText').value;
    console.log(`Sending message: ${messageText}`);
    
    // จำลองการส่งข้อความ (ในการใช้งานจริงจะใช้ AJAX)
    closeModal('sendMessageModal');
    showAlert('ส่งข้อความแจ้งเตือนเรียบร้อยแล้ว', 'success');
}

/**
 * ส่งข้อความแจ้งเตือนกลุ่ม
 */
function sendBulkNotification() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    console.log('Sending bulk notifications');
    
    // จำลองการส่งข้อความ (ในการใช้งานจริงจะใช้ AJAX)
    closeModal('bulkNotificationModal');
    showAlert('ส่งข้อความแจ้งเตือนกลุ่มเรียบร้อยแล้ว', 'success');
}

/**
 * เลือกเทมเพลตในโมดัล
 * 
 * @param {string} templateType - ประเภทของเทมเพลต
 */
function selectModalTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    event.target.classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('modalMessageText');
    
    switch(templateType) {
        case 'regular':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ปัจจุบันเข้าร่วม 26 จาก 40 วัน (65%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'warning':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'critical':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\n[ข้อความด่วน] ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'summary':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 10 วัน จากทั้งหมด 22 วัน (45.45%)\nจำนวนวันขาดแถว: 12 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
    }
}

/**
 * ดาวน์โหลดรายงานนักเรียนเสี่ยงตกกิจกรรม
 */
function downloadAtRiskReport() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    console.log('Downloading at-risk student report');
    
    // จำลองการดาวน์โหลด (ในการใช้งานจริงจะส่ง request ไปยัง endpoint ที่สร้างไฟล์)
    showAlert('กำลังดาวน์โหลดรายงานนักเรียนเสี่ยงตกกิจกรรม', 'info');
}

/**
 * สร้างแผนภูมิต่างๆ ในหน้านักเรียนเสี่ยงตกกิจกรรม
 */
function initCharts() {
    // ฟังก์ชันนี้จะเรียกใช้ฟังก์ชันจาก charts.js
    // ในการใช้งานจริง จะมีการโหลดไลบรารีกราฟมาใช้งาน เช่น Chart.js
    
    if (typeof initAttendanceCharts === 'function') {
        initAttendanceCharts();
    } else {
        console.log('Chart initialization skipped (charts.js not loaded)');
    }
}

/**
 * ตั้งค่าตาราง
 */
function initTables() {
    // ตั้งค่าการคลิกแถวในตาราง
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // ไม่ทำอะไรถ้าคลิกที่ปุ่มหรือเช็คบ็อกซ์
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT') {
                return;
            }
            
            // ดึง ID ของนักเรียนจากแอตทริบิวต์ data-id
            const studentId = this.getAttribute('data-id');
            if (studentId) {
                showStudentDetail(studentId);
            }
        });
    });
}

/**
 * กรองข้อมูลนักเรียนตามเงื่อนไข
 */
function filterStudents() {
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    const advisor = document.getElementById('advisor').value;
    const attendanceRate = document.getElementById('attendanceRate').value;
    
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    console.log(`Filtering students: Class ${classLevel}/${classRoom}, Advisor: ${advisor}, Rate: ${attendanceRate}`);
    
    // จำลองการโหลดข้อมูล (ในการใช้งานจริงจะใช้ AJAX)
    showAlert('กำลังโหลดข้อมูลตามเงื่อนไขที่กำหนด', 'info');
    
    // สร้าง URL พร้อมกับพารามิเตอร์ที่จำเป็น
    let url = 'at_risk.php?';
    if (classLevel) url += `&class_level=${encodeURIComponent(classLevel)}`;
    if (classRoom) url += `&class_room=${encodeURIComponent(classRoom)}`;
    if (advisor) url += `&advisor=${encodeURIComponent(advisor)}`;
    if (attendanceRate) url += `&attendance_rate=${encodeURIComponent(attendanceRate)}`;
    
    // โหลดหน้าใหม่พร้อมกับพารามิเตอร์การกรอง
    // window.location.href = url;
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
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
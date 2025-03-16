/**
 * notification.js - JavaScript สำหรับหน้าส่งข้อความแจ้งเตือน
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่าเทมเพลตข้อความ
    initTemplates();
    
    // ตั้งค่า event listeners
    setupEventListeners();
    
    // ตรวจสอบ URL parameters เพื่อเปิดแท็บที่ระบุ (ถ้ามี)
    checkUrlParameters();
});

/**
 * ตั้งค่าระบบแท็บ
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
    
    // อัปเดต URL (เพื่อให้สามารถใช้ browser back/forward ได้)
    updateUrlParameter('tab', tabId);
}

/**
 * ตั้งค่าเทมเพลตข้อความ
 */
function initTemplates() {
    // ตั้งค่าเทมเพลตรายบุคคล
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const templateType = this.getAttribute('data-template') || btn.textContent.trim().toLowerCase();
            
            // เทมเพลตรายบุคคล
            if (document.getElementById('messageText')) {
                selectTemplate(templateType);
            }
            // เทมเพลตกลุ่ม
            else if (document.getElementById('groupMessageText')) {
                selectGroupTemplate(templateType);
            }
        });
    });
}

/**
 * เลือกเทมเพลตสำหรับการส่งข้อความรายบุคคล
 * 
 * @param {string} templateType - ประเภทของเทมเพลต
 */
function selectTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    const selectedButton = document.querySelector(`.template-btn[onclick*="${templateType}"]`) || 
                           document.querySelector(`.template-btn[data-template="${templateType}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    if (!messageText) return;
    
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
    
    // อัปเดตตัวอย่างข้อความ
    updatePreview(messageText.value);
}

/**
 * เลือกเทมเพลตสำหรับการส่งข้อความแบบกลุ่ม
 * 
 * @param {string} templateType - ประเภทของเทมเพลต
 */
function selectGroupTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    const selectedButton = document.querySelector(`.template-btn[onclick*="${templateType}"]`) || 
                         document.querySelector(`.template-btn[data-template="${templateType}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('groupMessageText');
    if (!messageText) return;
    
    const classInfo = "ม.5/1";
    const advisorName = "ครูอิศรา สุขใจ";
    const advisorPhone = "081-234-5678";
    
    switch(templateType) {
        case 'regular':
        case 'reminder':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น ${classInfo}\n\nทางโรงเรียนขอแจ้งว่าในวันศุกร์ที่ 21 มีนาคม 2568 นักเรียนชั้น ${classInfo} จะต้องมาโรงเรียนก่อนเวลา 07:30 น. เพื่อเตรียมความพร้อมในการเข้าแถวพิเศษสำหรับกิจกรรมวันภาษาไทย\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม`;
            break;
        case 'risk-warning':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น ${classInfo}\n\nทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nโดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น ${classInfo} ${advisorName} โทร. ${advisorPhone} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม`;
            break;
        case 'meeting':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น ${classInfo}\n\nทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้\n\n1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา\n2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว\n3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น ${classInfo} ${advisorName} โทร. ${advisorPhone} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม`;
            break;
    }
    
    // อัปเดตตัวอย่างข้อความ
    updateGroupPreview(messageText.value);
}

/**
 * อัปเดตการแสดงตัวอย่างข้อความสำหรับแท็บรายบุคคล
 * 
 * @param {string} message - ข้อความที่ต้องการแสดงตัวอย่าง
 */
function updatePreview(message) {
    const previewContainer = document.querySelector('#individual-tab .preview-content p');
    if (previewContainer) {
        previewContainer.innerHTML = message.replace(/\n/g, '<br>');
    }
}

/**
 * อัปเดตการแสดงตัวอย่างข้อความสำหรับแท็บกลุ่ม
 * 
 * @param {string} message - ข้อความที่ต้องการแสดงตัวอย่าง
 */
function updateGroupPreview(message) {
    const previewContainer = document.querySelector('#group-tab .preview-content p');
    if (previewContainer) {
        previewContainer.innerHTML = message.replace(/\n/g, '<br>');
    }
}

/**
 * แสดงตัวอย่างข้อความในโมดัล
 */
function showPreview() {
    const messageText = document.getElementById('messageText').value;
    const previewText = document.getElementById('previewText');
    
    if (previewText) {
        previewText.innerHTML = messageText.replace(/\n/g, '<br>');
    }
    
    showModal('previewModal');
}

/**
 * แสดงตัวอย่างข้อความกลุ่มในโมดัล
 */
function showGroupPreview() {
    const messageText = document.getElementById('groupMessageText').value;
    const previewText = document.getElementById('previewText');
    
    if (previewText) {
        previewText.innerHTML = messageText.replace(/\n/g, '<br>');
    }
    
    showModal('previewModal');
}

/**
 * แสดงโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการแสดง
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    }
}

/**
 * ซ่อนโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการซ่อน
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * แสดงประวัติการส่งข้อความ
 */
function showHistory() {
    showModal('historyModal');
}

/**
 * เลือกผู้รับข้อความทั้งหมด
 */
function selectAllRecipients() {
    document.querySelectorAll('.recipients-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
    
    // อัปเดตจำนวนผู้รับในปุ่มส่ง
    updateRecipientCount();
}

/**
 * ยกเลิกการเลือกผู้รับข้อความทั้งหมด
 */
function clearAllRecipients() {
    document.querySelectorAll('.recipients-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // อัปเดตจำนวนผู้รับในปุ่มส่ง
    updateRecipientCount();
}

/**
 * อัปเดตจำนวนผู้รับในปุ่มส่งข้อความ
 */
function updateRecipientCount() {
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    const sendButton = document.querySelector('#group-tab .form-actions .btn-primary');
    
    if (sendButton) {
        const buttonText = sendButton.innerHTML.replace(/\(\d+.*\)/g, '');
        sendButton.innerHTML = `${buttonText} (${selectedCount} ราย)`;
    }
}

/**
 * ส่งข้อความรายบุคคล
 */
function sendMessage() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    const messageText = document.getElementById('messageText').value;
    
    // จำลองการส่งข้อความ
    showAlert('กำลังส่งข้อความ...', 'info');
    
    // จำลองการส่งสำเร็จหลังจาก 1 วินาที
    setTimeout(() => {
        showAlert('ส่งข้อความเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * ส่งข้อความกลุ่ม
 */
function sendGroupMessage() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    const messageText = document.getElementById('groupMessageText').value;
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    
    if (selectedCount === 0) {
        showAlert('กรุณาเลือกผู้รับข้อความอย่างน้อย 1 คน', 'warning');
        return;
    }
    
    // จำลองการส่งข้อความ
    showAlert(`กำลังส่งข้อความไปยังผู้ปกครอง ${selectedCount} คน...`, 'info');
    
    // จำลองการส่งสำเร็จหลังจาก 1.5 วินาที
    setTimeout(() => {
        showAlert(`ส่งข้อความเรียบร้อยแล้ว ${selectedCount} คน`, 'success');
    }, 1500);
}

/**
 * รีเซ็ตฟอร์มส่งข้อความรายบุคคล
 */
function resetForm() {
    // เลือกเทมเพลตแรก
    const firstTemplate = document.querySelector('#individual-tab .template-btn');
    if (firstTemplate) {
        firstTemplate.click();
    }
}

/**
 * รีเซ็ตฟอร์มส่งข้อความกลุ่ม
 */
function resetGroupForm() {
    // เลือกเทมเพลตแรก
    const firstTemplate = document.querySelector('#group-tab .template-btn');
    if (firstTemplate) {
        firstTemplate.click();
    }
}

/**
 * แสดงข้อความแจ้งเตือนแบบชั่วคราว
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // ตรวจสอบว่ามี container สำหรับ alert หรือไม่
    let alertContainer = document.querySelector('.alert-container');
    
    // ถ้ายังไม่มี ให้สร้างขึ้นมาใหม่
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.backgroundColor = type === 'success' ? '#e8f5e9' : 
                                  type === 'warning' ? '#fff8e1' : 
                                  type === 'danger' ? '#ffebee' : '#e3f2fd';
    alert.style.color = type === 'success' ? '#4caf50' : 
                        type === 'warning' ? '#ff9800' : 
                        type === 'danger' ? '#f44336' : '#1976d2';
    alert.style.padding = '15px';
    alert.style.marginBottom = '10px';
    alert.style.borderRadius = '5px';
    alert.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
    alert.style.position = 'relative';
    alert.style.transition = 'all 0.3s';
    
    // เพิ่มเนื้อหาข้อความและปุ่มปิด
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close" style="position: absolute; top: 5px; right: 5px; background: none; border: none; cursor: pointer; font-size: 18px; color: inherit;">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ตั้งค่าปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            alert.style.opacity = '0';
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 300);
        });
    }
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
}

/**
 * แสดงแผนที่แบบจำลองในโมดัล
 */
function showMapModal(studentId) {
    // ในทางปฏิบัติจริง จะมีการโหลดข้อมูลตำแหน่งของนักเรียนมาแสดง
    showModal('mapModal');
}

/**
 * สร้างเทมเพลตข้อความใหม่
 */
function createNewTemplate() {
    // แสดงโมดัลสร้างเทมเพลตใหม่
    showModal('templateModal');
}

/**
 * ตั้งค่า event listeners ต่างๆ
 */
function setupEventListeners() {
    // ติดตามการเปลี่ยนแปลงของ checkboxes ในรายการผู้รับข้อความ
    const recipientCheckboxes = document.querySelectorAll('.recipients-container input[type="checkbox"]');
    recipientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateRecipientCount);
    });
    
    // ติดตามการเปลี่ยนแปลงของ textarea เพื่ออัปเดตตัวอย่าง
    const individualTextarea = document.getElementById('messageText');
    if (individualTextarea) {
        individualTextarea.addEventListener('input', function() {
            updatePreview(this.value);
        });
    }
    
    const groupTextarea = document.getElementById('groupMessageText');
    if (groupTextarea) {
        groupTextarea.addEventListener('input', function() {
            updateGroupPreview(this.value);
        });
    }
    
    // ปุ่มประวัติการส่ง
    const historyButtons = document.querySelectorAll('.table-action-btn[title="ดูประวัติการส่ง"]');
    historyButtons.forEach(button => {
        button.addEventListener('click', showHistory);
    });
    
    // ปุ่มส่งข้อความ
    const sendButtons = document.querySelectorAll('.table-action-btn[title="ส่งข้อความ"]');
    sendButtons.forEach(button => {
        button.addEventListener('click', function() {
            // เลือกนักเรียนที่ต้องการส่งข้อความ
            const row = this.closest('tr');
            const radio = row.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
            
            // เลื่อนไปยังส่วนข้อความ
            const messageForm = document.querySelector('.message-form');
            if (messageForm) {
                messageForm.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

/**
 * ตรวจสอบและประมวลผล URL parameters
 */
function checkUrlParameters() {
    const params = new URLSearchParams(window.location.search);
    
    // ตรวจสอบว่ามีการระบุแท็บหรือไม่
    if (params.has('tab')) {
        const tabId = params.get('tab');
        if (document.getElementById(tabId + '-tab')) {
            showTab(tabId);
        }
    }
    
    // ตรวจสอบว่ามีการระบุนักเรียนหรือไม่
    if (params.has('student_id')) {
        const studentId = params.get('student_id');
        selectStudentById(studentId);
    }
}

/**
 * เลือกนักเรียนตาม ID
 * 
 * @param {string} studentId - ID ของนักเรียนที่ต้องการเลือก
 */
function selectStudentById(studentId) {
    // ในทางปฏิบัติจริง จะมีการค้นหานักเรียนตาม ID และเลือก
    // ตัวอย่างแบบจำลอง
    const studentRadios = document.querySelectorAll('input[name="student_select"]');
    if (studentRadios.length > 0) {
        studentRadios[0].checked = true;
    }
}

/**
 * อัปเดต URL parameter
 * 
 * @param {string} key - ชื่อของ parameter
 * @param {string} value - ค่าของ parameter
 */
function updateUrlParameter(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.history.replaceState({}, '', url);
}
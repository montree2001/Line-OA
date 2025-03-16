/**
 * parents.js - JavaScript สำหรับหน้าจัดการข้อมูลผู้ปกครอง
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่า event listeners
    setupEventListeners();
    
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
 * ตั้งค่า event listeners ต่างๆ
 */
function setupEventListeners() {
    // ตั้งค่าปุ่มเพิ่มผู้ปกครอง
    const addParentBtn = document.querySelector('.action-button[onclick="showAddParentModal()"]');
    if (addParentBtn) {
        addParentBtn.addEventListener('click', showAddParentModal);
    }
    
    // ตั้งค่าปุ่มนำเข้า CSV
    const importBtn = document.querySelector('.action-button[onclick="showImportModal()"]');
    if (importBtn) {
        importBtn.addEventListener('click', showImportModal);
    }
    
    // ตั้งค่าปุ่มปิดโมดัล
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            closeModal(modalId);
        });
    });
    
    // ตั้งค่าปุ่มเลือกเทมเพลตข้อความ
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateType = this.getAttribute('data-template');
            if (templateType) {
                selectMessageTemplate(templateType);
            }
        });
    });
    
    // ตั้งค่าการอัปโหลดไฟล์
    setupFileUpload();
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
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' || 
                e.target.closest('button') || e.target.closest('input')) {
                return;
            }
            
            // ดึงข้อมูลจากแถว
            // ตัวอย่างเช่น ดึง ID จากข้อมูลที่ซ่อนอยู่
            const parentInfo = this.querySelector('.parent-info');
            if (parentInfo) {
                const parentName = parentInfo.querySelector('.parent-name').textContent;
                showParentDetails(parentName);
            }
        });
    });
}

/**
 * ตั้งค่าการอัปโหลดไฟล์
 */
function setupFileUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileUpload = document.getElementById('fileUpload');
    const importButton = document.querySelector('#importModal .btn-primary');
    
    if (uploadArea) {
        uploadArea.innerHTML = `
            <span class="material-icons">cloud_upload</span>
            <p>คลิกหรือลากไฟล์ CSV มาที่นี่</p>
        `;
    }
    
    if (fileUpload) {
        fileUpload.value = '';
    }
    
    if (importButton) {
        importButton.disabled = true;
    }
}

/**
 * ฟอร์แมตขนาดไฟล์
 * 
 * @param {number} bytes - ขนาดไฟล์เป็นไบต์
 * @return {string} ขนาดไฟล์ที่จัดรูปแบบแล้ว
 */
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
}

/**
 * ค้นหาข้อมูลผู้ปกครอง
 */
function searchParents() {
    const name = document.querySelector('input[placeholder="ป้อนชื่อผู้ปกครอง..."]').value;
    const studentName = document.querySelector('input[placeholder="ป้อนชื่อนักเรียน..."]').value;
    const classLevel = document.querySelector('select[class="form-control"]').value;
    const classRoom = document.querySelectorAll('select[class="form-control"]')[1].value;
    
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    console.log(`ค้นหาผู้ปกครอง: ${name}, นักเรียน: ${studentName}, ระดับชั้น: ${classLevel}, ห้อง: ${classRoom}`);
    
    // จำลองการแสดงผลการค้นหา (ในการใช้งานจริงจะเปลี่ยนแปลงข้อมูลในตาราง)
    showAlert('กำลังค้นหาข้อมูลผู้ปกครอง', 'info');
    
    // ในทางปฏิบัติจริง จะมีการอัปเดตข้อมูลในตาราง
    setTimeout(() => {
        showAlert('พบข้อมูลผู้ปกครอง 3 รายการ', 'success');
    }, 1000);
}

/**
 * บันทึกข้อมูลผู้ปกครอง
 * 
 * @param {string} formType - ประเภทของฟอร์ม ('add' หรือ 'edit')
 */
function saveParentData(formType) {
    const modalId = formType === 'add' ? 'addParentModal' : 'editParentModal';
    const form = document.querySelector(`#${modalId} .modal-content`);
    
    // ในทางปฏิบัติจริง จะมีการเก็บข้อมูลจากฟอร์มและส่งไปยัง backend
    console.log(`บันทึกข้อมูลผู้ปกครอง (${formType})`);
    
    // จำลองการบันทึกข้อมูล
    showAlert('กำลังบันทึกข้อมูล...', 'info');
    
    // ปิดโมดัล
    closeModal(modalId);
    
    // แสดงข้อความสำเร็จ
    setTimeout(() => {
        showAlert('บันทึกข้อมูลเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * ส่งข้อความถึงผู้ปกครอง
 */
function sendMessage() {
    const messageText = document.getElementById('messageText').value;
    const channels = [];
    
    if (document.getElementById('send_line').checked) channels.push('LINE');
    if (document.getElementById('send_sms').checked) channels.push('SMS');
    if (document.getElementById('send_email').checked) channels.push('Email');
    
    // ในทางปฏิบัติจริง จะมีการส่งข้อมูลไปยัง backend
    console.log(`ส่งข้อความ: ${messageText}`);
    console.log(`ช่องทาง: ${channels.join(', ')}`);
    
    // จำลองการส่งข้อความ
    showAlert('กำลังส่งข้อความ...', 'info');
    
    // ปิดโมดัล
    closeModal('sendMessageModal');
    
    // แสดงข้อความสำเร็จ
    setTimeout(() => {
        showAlert('ส่งข้อความเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * บันทึกการตั้งค่า
 */
function saveSettings() {
    // ในทางปฏิบัติจริง จะมีการเก็บข้อมูลจากฟอร์มและส่งไปยัง backend
    console.log('บันทึกการตั้งค่า');
    
    // จำลองการบันทึกการตั้งค่า
    showAlert('กำลังบันทึกการตั้งค่า...', 'info');
    
    // แสดงข้อความสำเร็จ
    setTimeout(() => {
        showAlert('บันทึกการตั้งค่าเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message - ข้อความที่ต้องการแสดง
 * @param {string} type - ประเภทของการแจ้งเตือน (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // ตรวจสอบว่ามีฟังก์ชัน showAlert ใน main.js หรือไม่
    if (typeof window.showAlert === 'function') {
        window.showAlert(message, type);
        return;
    }
    
    // ถ้าไม่มีฟังก์ชัน showAlert ใน main.js ให้สร้าง alert ของตัวเอง
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.backgroundColor = type === 'success' ? '#e8f5e9' : 
                               type === 'info' ? '#e3f2fd' : 
                               type === 'warning' ? '#fff8e1' : '#ffebee';
    alert.style.color = type === 'success' ? '#4caf50' : 
                      type === 'info' ? '#1976d2' : 
                      type === 'warning' ? '#ff9800' : '#f44336';
    alert.style.padding = '15px';
    alert.style.marginBottom = '10px';
    alert.style.borderRadius = '5px';
    alert.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
    alert.style.display = 'flex';
    alert.style.justifyContent = 'space-between';
    alert.style.alignItems = 'center';
    alert.style.transition = 'all 0.3s';
    
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close" style="background: none; border: none; cursor: pointer; font-size: 18px;">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.style.opacity = '0';
        setTimeout(() => {
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
        }, 300);
    });
    
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
}d('fileUpload');
    
    if (uploadArea && fileUpload) {
        uploadArea.addEventListener('click', function() {
            fileUpload.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('upload-area-drag');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            this.classList.remove('upload-area-drag');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('upload-area-drag');
            
            if (e.dataTransfer.files.length) {
                fileUpload.files = e.dataTransfer.files;
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
        
        fileUpload.addEventListener('change', function() {
            if (this.files.length) {
                handleFileUpload(this.files[0]);
            }
        });
    }


/**
 * แสดงรายละเอียดผู้ปกครอง
 * 
 * @param {string} parentName - ชื่อผู้ปกครอง
 */
function showParentDetails(parentName) {
    // ในทางปฏิบัติจริง จะมีการดึงข้อมูลจากฐานข้อมูล
    console.log(`แสดงรายละเอียดของผู้ปกครอง: ${parentName}`);
    
    // ตัวอย่างการแสดงข้อมูลในโมดัล
    showEditParentModal(0); // ใช้ ID จริงในงานจริง
}

/**
 * แสดงโมดัลเพิ่มผู้ปกครอง
 */
function showAddParentModal() {
    showModal('addParentModal');
}

/**
 * แสดงโมดัลแก้ไขข้อมูลผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง
 */
function showEditParentModal(parentId) {
    // ในทางปฏิบัติจริง จะมีการดึงข้อมูลผู้ปกครองจากฐานข้อมูล
    console.log(`ดึงข้อมูลผู้ปกครอง ID: ${parentId}`);
    
    // แสดงโมดัล
    showModal('editParentModal');
}

/**
 * แสดงโมดัลส่งข้อความถึงผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง
 */
function showSendMessageModal(parentId) {
    // ในทางปฏิบัติจริง จะมีการดึงข้อมูลผู้ปกครองจากฐานข้อมูล
    console.log(`ดึงข้อมูลผู้ปกครอง ID: ${parentId} สำหรับส่งข้อความ`);
    
    // แสดงโมดัล
    showModal('sendMessageModal');
}

/**
 * แสดงโมดัลนำเข้า CSV
 */
function showImportModal() {
    // รีเซ็ตฟอร์มอัปโหลด
    resetFileUpload();
    
    // แสดงโมดัล
    showModal('importModal');
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
 * ปิดโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการปิด
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // คืนค่าการเลื่อนหน้าเว็บ
    }
}

/**
 * เลือกเทมเพลตข้อความ
 * 
 * @param {string} templateType - ประเภทของเทมเพลต
 */
function selectMessageTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    document.querySelector(`.template-btn[data-template="${templateType}"]`).classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    if (!messageText) return;
    
    // ตัวอย่างข้อความสำหรับแต่ละเทมเพลต
    const templates = {
        'regular': 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ปัจจุบันเข้าร่วม 26 จาก 40 วัน (65%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม',
        'warning': 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม',
        'critical': 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\n[ข้อความด่วน] ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม',
        'summary': 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 10 วัน จากทั้งหมด 22 วัน (45.45%)\nจำนวนวันขาดแถว: 12 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม'
    };
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    if (templates[templateType]) {
        messageText.value = templates[templateType];
    }
}

/**
 * จัดการกับไฟล์ที่อัปโหลด
 * 
 * @param {File} file - ไฟล์ที่อัปโหลด
 */
function handleFileUpload(file) {
    if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
        alert('กรุณาอัปโหลดไฟล์ CSV เท่านั้น');
        return;
    }
    
    const uploadArea = document.getElementById('uploadArea');
    const importButton = document.querySelector('#importModal .btn-primary');
    
    if (uploadArea) {
        uploadArea.innerHTML = `
            <span class="material-icons">description</span>
            <p>${file.name} (${formatFileSize(file.size)})</p>
            <button type="button" class="btn btn-sm btn-secondary" onclick="resetFileUpload(event)">เปลี่ยนไฟล์</button>
        `;
    }
    
    if (importButton) {
        importButton.disabled = false;
    }
}




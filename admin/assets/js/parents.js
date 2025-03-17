/**
 * parents.js - JavaScript สำหรับหน้าจัดการผู้ปกครอง
 * ระบบ STUDENT-Prasat
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่าการอัปโหลดไฟล์
    initFileUpload();
    
    // ตั้งค่า event listeners สำหรับแบบฟอร์ม
    setupEventListeners();
});

/**
 * ตั้งค่าระบบแท็บในหน้า
 */
function initTabs() {
    // ซ่อนแท็บคอนเทนต์ทั้งหมดยกเว้นแท็บแรก
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach((tabContent, index) => {
        if (index !== 0) {
            tabContent.style.display = 'none';
        }
    });
    
    // เพิ่ม event listener สำหรับปุ่มแท็บ
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            showTab(tabId);
        });
    });
}

/**
 * แสดงแท็บที่เลือกและซ่อนแท็บอื่นๆ
 * 
 * @param {string} tabId - ID ของแท็บที่ต้องการแสดง
 */
function showTab(tabId) {
    // ซ่อนแท็บทั้งหมด
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tabContent => {
        tabContent.style.display = 'none';
    });
    
    // ยกเลิกการเลือกแท็บทั้งหมด
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
    document.getElementById(tabId + '-tab').style.display = 'block';
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
}

/**
 * ตั้งค่าการอัปโหลดไฟล์
 */
function initFileUpload() {
    const fileInput = document.getElementById('fileUpload');
    const uploadArea = document.querySelector('.upload-area');
    const importButton = document.getElementById('importButton');
    
    if (fileInput && uploadArea) {
        // Drag & Drop events
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                // ทริกเกอร์ event change เพื่อให้ handler ทำงาน
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });
        
        // File input change event
        fileInput.addEventListener('change', function() {
            if (importButton) {
                importButton.disabled = !this.files.length;
            }
            
            if (this.files.length) {
                const fileName = this.files[0].name;
                // แสดงชื่อไฟล์ที่อัปโหลด (ถ้าต้องการ)
                // uploadArea.querySelector('span').textContent = fileName;
            }
        });
    }
}

/**
 * ตั้งค่า event listeners สำหรับฟอร์มและปุ่มต่างๆ
 */
function setupEventListeners() {
    // ปุ่มค้นหานักเรียน
    const searchButton = document.querySelector('button[onclick="searchStudents()"]');
    if (searchButton) {
        searchButton.addEventListener('click', searchStudents);
    }
    
    // สลับการแสดง QR Code เมื่อติ๊กเลือก
    const generateQRCode = document.getElementById('generateQRCode');
    const qrCodeSection = document.getElementById('qrCodeSection');
    if (generateQRCode && qrCodeSection) {
        generateQRCode.addEventListener('change', function() {
            qrCodeSection.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // ปุ่มรีเซ็ตฟอร์ม
    const resetButton = document.querySelector('button[onclick="resetForm()"]');
    if (resetButton) {
        resetButton.addEventListener('click', resetForm);
    }
    
    // ปุ่มรีเซ็ตการนำเข้า
    const resetImportButton = document.querySelector('button[onclick="resetImport()"]');
    if (resetImportButton) {
        resetImportButton.addEventListener('click', resetImport);
    }
    
    // ปุ่มในโมดัลยืนยันการลบ
    const confirmDeleteButton = document.querySelector('button[onclick="confirmDelete()"]');
    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', confirmDelete);
    }
    
    // ปุ่มในโมดัลส่งข้อความ
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateType = this.getAttribute('data-template');
            selectTemplate(templateType);
        });
    });
}

/**
 * ฟังก์ชันสำหรับดูข้อมูลผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง
 */
function viewParentDetails(parentId) {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปดึงข้อมูลจาก backend
    console.log(`ดูข้อมูลผู้ปกครอง ID: ${parentId}`);
    showModal('parentDetailModal');
}

/**
 * ฟังก์ชันสำหรับแก้ไขข้อมูลผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง
 */
function editParent(parentId) {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปดึงข้อมูลจาก backend
    console.log(`แก้ไขข้อมูลผู้ปกครอง ID: ${parentId}`);
    // เปลี่ยนไปยังแท็บเพิ่มข้อมูล (แต่จะใช้สำหรับการแก้ไขแทน)
    showTab('parent-add');
    // ตั้งค่าฟอร์มด้วยข้อมูลที่มีอยู่
    // (ในทางปฏิบัติจริง จะเติมข้อมูลในฟอร์มด้วยข้อมูลที่ดึงมาจาก backend)
}

/**
 * ฟังก์ชันสำหรับลบข้อมูลผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง
 */
function deleteParent(parentId) {
    // แสดงโมดัลยืนยันการลบ
    console.log(`เตรียมลบข้อมูลผู้ปกครอง ID: ${parentId}`);
    showModal('confirmDeleteModal');
}

/**
 * ฟังก์ชันยืนยันการลบข้อมูลผู้ปกครอง
 */
function confirmDelete() {
    // ในทางปฏิบัติจริง จะส่ง AJAX request ไปลบข้อมูลใน backend
    console.log('ยืนยันการลบข้อมูลผู้ปกครอง');
    closeModal('confirmDeleteModal');
    // แสดงข้อความแจ้งเตือนการลบสำเร็จ
    showAlert('ลบข้อมูลผู้ปกครองเรียบร้อยแล้ว', 'success');
}

/**
 * ฟังก์ชันค้นหานักเรียนเพื่อเพิ่มในรายการผู้ปกครอง
 */
function searchStudents() {
    showModal('searchStudentModal');
}

/**
 * ฟังก์ชันเพิ่มนักเรียนที่เลือกลงในตาราง
 */
function addSelectedStudents() {
    // ในทางปฏิบัติจริง จะเพิ่มนักเรียนที่เลือกลงในตารางนักเรียนในความปกครอง
    console.log('เพิ่มนักเรียนที่เลือก');
    closeModal('searchStudentModal');
    
    // จำลองการเพิ่มนักเรียนลงในตาราง
    const studentTable = document.querySelector('#parent-add-tab .data-table tbody');
    if (studentTable) {
        // ล้างข้อความ "กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ"
        studentTable.innerHTML = '';
        
        // เพิ่มแถวนักเรียนตัวอย่าง
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="checkbox" checked></td>
            <td>นายธนกฤต สุขใจ</td>
            <td>ม.6/2</td>
            <td>12345</td>
            <td>
                <select class="form-control form-control-sm">
                    <option value="">-- เลือก --</option>
                    <option selected>มารดา</option>
                    <option>บิดา</option>
                    <option>ผู้ปกครอง</option>
                    <option>อื่นๆ</option>
                </select>
            </td>
        `;
        studentTable.appendChild(newRow);
    }
}

/**
 * ฟังก์ชันเลือกเทมเพลตข้อความ
 * 
 * @param {string} templateType - ประเภทของเทมเพลต
 */
function selectTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    const clickedButton = document.querySelector(`.template-btn[data-template="${templateType}"]`);
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    if (!messageText) return;
    
    switch(templateType) {
        case 'regular':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'meeting':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอเรียนเชิญท่านเข้าร่วมประชุมผู้ปกครองนักเรียนชั้น ม.6/2 ในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nจึงเรียนมาเพื่อทราบและขอเชิญเข้าร่วมประชุมตามวันและเวลาดังกล่าว\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'warning':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nทางโรงเรียนขอแจ้งให้ทราบว่า นายธนกฤต สุขใจ มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'report':
            messageText.value = 'เรียน คุณวันดี สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 26 วัน จากทั้งหมด 40 วัน (65%)\nจำนวนวันขาดแถว: 14 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
    }
}

/**
 * แสดงตัวอย่างข้อความที่จะส่ง
 */
function showMessagePreview() {
    const messageText = document.getElementById('messageText').value;
    // ในทางปฏิบัติจริง จะแสดงข้อความตัวอย่างในรูปแบบที่จะปรากฏบน LINE
    console.log('แสดงตัวอย่างข้อความ:', messageText);
    
    // สร้างโมดัลแสดงตัวอย่าง (ถ้ายังไม่มี)
    let previewModal = document.getElementById('messagePreviewModal');
    if (!previewModal) {
        previewModal = document.createElement('div');
        previewModal.id = 'messagePreviewModal';
        previewModal.className = 'modal';
        previewModal.innerHTML = `
            <div class="modal-content">
                <button class="modal-close" onclick="closeModal('messagePreviewModal')">
                    <span class="material-icons">close</span>
                </button>
                <h2 class="modal-title">ตัวอย่างข้อความที่จะส่ง</h2>
                <div class="preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p id="previewMessageText" style="margin-top: 15px; white-space: pre-line;"></p>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="closeModal('messagePreviewModal')">ปิด</button>
                </div>
            </div>
        `;
        document.body.appendChild(previewModal);
    }
    
    // อัปเดตข้อความในโมดัล
    const previewMessageText = document.getElementById('previewMessageText');
    if (previewMessageText) {
        previewMessageText.textContent = messageText;
    }
    
    // แสดงโมดัล
    showModal('messagePreviewModal');
}

/**
 * ฟังก์ชันส่งข้อความถึงผู้ปกครอง
 * 
 * @param {number} parentId - ID ของผู้ปกครอง (optional)
 */
function sendDirectMessage(parentId) {
    if (parentId) {
        // แสดงโมดัลส่งข้อความ
        console.log(`เตรียมส่งข้อความถึงผู้ปกครอง ID: ${parentId}`);
        showModal('sendMessageModal');
    } else {
        // ส่งข้อความ (จากโมดัลที่เปิดอยู่แล้ว)
        console.log('ส่งข้อความถึงผู้ปกครอง');
        
        // ในทางปฏิบัติจริง จะส่ง AJAX request ไปยัง LINE Messaging API
        const messageText = document.getElementById('messageText').value;
        console.log('ข้อความที่ส่ง:', messageText);
        
        // ปิดโมดัล
        closeModal('sendMessageModal');
        
        // แสดงข้อความแจ้งเตือนการส่งสำเร็จ
        showAlert('ส่งข้อความถึงผู้ปกครองเรียบร้อยแล้ว', 'success');
    }
}

/**
 * ฟังก์ชันรีเซ็ตฟอร์ม
 */
function resetForm() {
    const form = document.getElementById('addParentForm');
    if (form) {
        form.reset();
        
        // ซ่อน QR Code ถ้าแสดงอยู่
        const qrCodeSection = document.getElementById('qrCodeSection');
        if (qrCodeSection) {
            qrCodeSection.style.display = 'none';
        }
        
        // ล้างตารางนักเรียน
        const studentTable = document.querySelector('#parent-add-tab .data-table tbody');
        if (studentTable) {
            studentTable.innerHTML = '<tr><td colspan="5" class="text-center">กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ</td></tr>';
        }
    }
}

/**
 * ฟังก์ชันรีเซ็ตการนำเข้าข้อมูล
 */
function resetImport() {
    const fileInput = document.getElementById('fileUpload');
    if (fileInput) {
        fileInput.value = '';
    }
    
    const importButton = document.getElementById('importButton');
    if (importButton) {
        importButton.disabled = true;
    }
}

/**
 * ฟังก์ชันนำเข้าข้อมูลผู้ปกครอง
 */
function importParents() {
    // ในทางปฏิบัติจริง จะส่งไฟล์ไปยัง backend เพื่อประมวลผล
    console.log('นำเข้าข้อมูลผู้ปกครอง');
    
    // ในตัวอย่างนี้ จะจำลองการนำเข้าสำเร็จ
    setTimeout(() => {
        // แสดงข้อความแจ้งเตือนการนำเข้าสำเร็จ
        showAlert('นำเข้าข้อมูลผู้ปกครองเรียบร้อยแล้ว', 'success');
        
        // รีเซ็ตฟอร์ม
        resetImport();
    }, 1500);
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
        // เพิ่ม event listener เพื่อปิดโมดัลเมื่อคลิกภายนอก
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modalId);
            }
        });
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
    }
}

/**
 * แสดงการแจ้งเตือน
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
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
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
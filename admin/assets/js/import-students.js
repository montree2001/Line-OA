/**
 * import-students.js - ไฟล์ JavaScript สำหรับจัดการการนำเข้าข้อมูลนักเรียน
 * เพิ่มไฟล์นี้ใน assets/js/import-students.js
 */

// ตัวแปรเก็บข้อมูลการนำเข้า
let importedData = [];
let importDataTable;
let currentStep = 1;
let totalSteps = 3;
let mappedData = [];

// เมื่อโหลดเอกสารเสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม event listener ให้กับฟอร์มนำเข้า
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', handleImportSubmit);
    }
    
    // เพิ่ม event listener ให้กับปุ่มเลือกไฟล์
    const fileInput = document.getElementById('import_file');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }
    
    // เพิ่ม event listener ให้กับปุ่มขั้นตอน
    const nextBtn = document.getElementById('nextStepBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', nextStep);
    }
    
    const prevBtn = document.getElementById('prevStepBtn');
    if (prevBtn) {
        prevBtn.addEventListener('click', prevStep);
    }
});

/**
 * แสดงโมดัลนำเข้าข้อมูล
 */
function showImportModal() {
    // รีเซ็ตข้อมูลการนำเข้า
    resetImportData();
    
    // แสดงโมดัล
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'block';
        showStep(1);
    }
}

/**
 * รีเซ็ตข้อมูลการนำเข้า
 */
function resetImportData() {
    importedData = [];
    mappedData = [];
    currentStep = 1;
    
    // รีเซ็ตฟอร์มและตัวแสดงตัวอย่างข้อมูล
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.reset();
    }
    
    const previewContainer = document.getElementById('dataPreview');
    if (previewContainer) {
        previewContainer.innerHTML = '';
    }
    
    const summaryContainer = document.getElementById('importSummary');
    if (summaryContainer) {
        summaryContainer.innerHTML = '';
    }
}

/**
 * จัดการเมื่อเลือกไฟล์
 * @param {Event} event - เหตุการณ์การเลือกไฟล์
 */
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }
    
    // ตรวจสอบนามสกุลไฟล์
    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(fileExt)) {
        showAlert('รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) หรือ CSV เท่านั้น', 'error');
        event.target.value = '';
        return;
    }
    
    // เปิดใช้งานปุ่มถัดไป
    const nextBtn = document.getElementById('nextStepBtn');
    if (nextBtn) {
        nextBtn.disabled = false;
    }
    
    // แสดงชื่อไฟล์ที่เลือก
    const fileLabel = document.getElementById('fileLabel');
    if (fileLabel) {
        fileLabel.textContent = file.name;
    }
}

/**
 * จัดการการส่งฟอร์มนำเข้า
 * @param {Event} event - เหตุการณ์การส่งฟอร์ม
 */
function handleImportSubmit(event) {
    event.preventDefault();
    
    // ตรวจสอบว่าอยู่ในขั้นตอนสุดท้ายหรือไม่
    if (currentStep !== totalSteps) {
        nextStep();
        return;
    }
    
    // สร้าง FormData
    const formData = new FormData(event.target);
    formData.append('action', 'import');
    
    // เพิ่มข้อมูลที่แม็ปแล้ว
    if (mappedData.length > 0) {
        formData.append('mapped_data', JSON.stringify(mappedData));
    }
    
    // แสดง loading
    showLoading();
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/import_students.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('importModal');
            
            // รีเฟรชหน้าหลังจากนำเข้าสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error importing data:', error);
        showAlert('เกิดข้อผิดพลาดในการนำเข้าข้อมูล', 'error');
    });
}

/**
 * แสดงขั้นตอนการนำเข้า
 * @param {number} step - หมายเลขขั้นตอน
 */
function showStep(step) {
    // ซ่อนทุกขั้นตอน
    document.querySelectorAll('.import-step').forEach(el => {
        el.style.display = 'none';
    });
    
    // แสดงขั้นตอนที่เลือก
    const stepElement = document.getElementById(`step${step}`);
    if (stepElement) {
        stepElement.style.display = 'block';
    }
    
    // อัพเดตตัวบ่งชี้ขั้นตอน
    updateStepIndicator(step);
    
    // อัพเดตปุ่มควบคุม
    updateStepButtons(step);
    
    // บันทึกขั้นตอนปัจจุบัน
    currentStep = step;
    
    // ดำเนินการเฉพาะสำหรับแต่ละขั้นตอน
    if (step === 2 && importedData.length === 0) {
        processImportFile();
    } else if (step === 3 && mappedData.length === 0) {
        prepareImportData();
    }
}

/**
 * ไปขั้นตอนถัดไป
 */
function nextStep() {
    if (currentStep < totalSteps) {
        // ตรวจสอบก่อนไปขั้นต่อไป
        if (currentStep === 1) {
            const fileInput = document.getElementById('import_file');
            if (!fileInput || !fileInput.files.length) {
                showAlert('กรุณาเลือกไฟล์ที่ต้องการนำเข้า', 'error');
                return;
            }
        } else if (currentStep === 2) {
            if (!validateFieldMapping()) {
                return;
            }
        }
        
        showStep(currentStep + 1);
    }
}

/**
 * ย้อนกลับไปขั้นตอนก่อนหน้า
 */
function prevStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

/**
 * อัพเดตตัวบ่งชี้ขั้นตอน
 * @param {number} step - ขั้นตอนปัจจุบัน
 */
function updateStepIndicator(step) {
    document.querySelectorAll('.step-indicator .step').forEach((el, index) => {
        const stepNum = index + 1;
        
        // ลบทุกคลาส
        el.classList.remove('active', 'completed');
        
        // กำหนดคลาสตามสถานะ
        if (stepNum < step) {
            el.classList.add('completed');
        } else if (stepNum === step) {
            el.classList.add('active');
        }
    });
}

/**
 * อัพเดตปุ่มควบคุมขั้นตอน
 * @param {number} step - ขั้นตอนปัจจุบัน
 */
function updateStepButtons(step) {
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const importBtn = document.getElementById('importSubmitBtn');
    
    if (prevBtn) {
        prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
    }
    
    if (nextBtn) {
        nextBtn.style.display = step < totalSteps ? 'inline-block' : 'none';
        
        // ปิดการใช้งานปุ่มถัดไปในขั้นตอนแรกถ้ายังไม่ได้เลือกไฟล์
        if (step === 1) {
            const fileInput = document.getElementById('import_file');
            nextBtn.disabled = !fileInput || !fileInput.files.length;
        } else {
            nextBtn.disabled = false;
        }
    }
    
    if (importBtn) {
        importBtn.style.display = step === totalSteps ? 'inline-block' : 'none';
    }
}

/**
 * ประมวลผลไฟล์ที่นำเข้า
 */
function processImportFile() {
    const fileInput = document.getElementById('import_file');
    if (!fileInput || !fileInput.files.length) {
        return;
    }
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    // แสดงการโหลด
    showLoading();
    
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            
            // อ่านข้อมูลจาก sheet แรก
            const firstSheet = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheet];
            
            // แปลงเป็น array ของ array
            importedData = XLSX.utils.sheet_to_json(worksheet, {header: 1, defval: ''});
            
            // ข้ามส่วนหัว
            const skipHeader = document.getElementById('skip_header').checked;
            if (skipHeader && importedData.length > 0) {
                // เก็บส่วนหัวไว้สำหรับแสดงในขั้นตอนการแม็ป
                const headers = [...importedData[0]];
                
                // อัพเดตตัวเลือกในการแม็ปฟิลด์
                updateMappingOptions(headers);
                
                // ลบส่วนหัวออกจากข้อมูล
                importedData = importedData.slice(1);
            } else {
                // ใช้ชื่อคอลัมน์ตามลำดับ
                const defaultHeaders = Array.from({length: importedData[0].length}, (_, i) => `คอลัมน์ ${i + 1}`);
                updateMappingOptions(defaultHeaders);
            }
            
            // แสดงตัวอย่างข้อมูล
            showDataPreview();
            
            hideLoading();
        } catch (error) {
            hideLoading();
            console.error('Error processing file:', error);
            showAlert('เกิดข้อผิดพลาดในการประมวลผลไฟล์: ' + error.message, 'error');
        }
    };
    
    reader.onerror = function() {
        hideLoading();
        showAlert('เกิดข้อผิดพลาดในการอ่านไฟล์', 'error');
    };
    
    reader.readAsArrayBuffer(file);
}

/**
 * อัพเดตตัวเลือกการแม็ปฟิลด์
 * @param {Array} headers - รายการหัวข้อ
 */
function updateMappingOptions(headers) {
    const mappingSelects = document.querySelectorAll('.field-mapping select');
    
    mappingSelects.forEach(select => {
        // เก็บค่าที่เลือกไว้
        const selectedValue = select.value;
        
        // ล้างตัวเลือกเดิม
        select.innerHTML = '<option value="-1">-- ไม่เลือก --</option>';
        
        // เพิ่มตัวเลือกใหม่
        headers.forEach((header, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = header;
            select.appendChild(option);
        });
        
        // คืนค่าที่เลือกไว้
        if (selectedValue !== '' && parseInt(selectedValue) >= 0) {
            select.value = selectedValue;
        } else {
            // พยายามแม็ปอัตโนมัติตามชื่อฟิลด์
            const fieldName = select.getAttribute('data-field');
            const matchIndex = findMatchingHeaderIndex(headers, fieldName);
            
            if (matchIndex >= 0) {
                select.value = matchIndex;
            }
        }
    });
}

/**
 * ค้นหาหัวข้อที่ตรงกับชื่อฟิลด์
 * @param {Array} headers - รายการหัวข้อ
 * @param {string} fieldName - ชื่อฟิลด์
 * @returns {number} - ลำดับของหัวข้อที่ตรงกัน หรือ -1 ถ้าไม่พบ
 */
function findMatchingHeaderIndex(headers, fieldName) {
    // คำสำคัญสำหรับแต่ละฟิลด์
    const fieldKeywords = {
        student_code: ['รหัสนักศึกษา', 'รหัสนักเรียน', 'student_code', 'student code', 'id'],
        title: ['คำนำหน้า', 'title', 'prefix'],
        firstname: ['ชื่อ', 'first_name', 'firstname', 'first name', 'name'],
        lastname: ['นามสกุล', 'last_name', 'lastname', 'last name', 'surname'],
        phone: ['เบอร์โทร', 'โทรศัพท์', 'เบอร์โทรศัพท์', 'phone', 'phone_number', 'tel'],
        email: ['อีเมล', 'email', 'e-mail'],
        level: ['ระดับชั้น', 'ชั้น', 'level', 'grade'],
        group: ['กลุ่ม', 'ห้อง', 'group', 'group_number', 'class'],
        department: ['แผนก', 'สาขา', 'แผนกวิชา', 'department'],
        status: ['สถานะ', 'status']
    };
    
    const keywords = fieldKeywords[fieldName] || [];
    
    // ค้นหาหัวข้อที่ตรงกับคำสำคัญ
    for (let i = 0; i < headers.length; i++) {
        const header = headers[i].toString().toLowerCase();
        
        for (const keyword of keywords) {
            if (header.includes(keyword.toLowerCase())) {
                return i;
            }
        }
    }
    
    return -1;
}

/**
 * แสดงตัวอย่างข้อมูล
 */
function showDataPreview() {
    const previewContainer = document.getElementById('dataPreview');
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if (!importedData.length) {
        previewContainer.innerHTML = '<p class="text-center">ไม่พบข้อมูลในไฟล์</p>';
        return;
    }
    
    // จำกัดจำนวนแถวที่แสดง
    const previewRows = importedData.slice(0, 5);
    
    // สร้างตาราง
    const table = document.createElement('table');
    table.className = 'data-preview-table';
    
    // สร้างส่วนหัวตาราง
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    for (let i = 0; i < Math.max(...previewRows.map(row => row.length)); i++) {
        const th = document.createElement('th');
        th.textContent = `คอลัมน์ ${i + 1}`;
        headerRow.appendChild(th);
    }
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // สร้างเนื้อหาตาราง
    const tbody = document.createElement('tbody');
    
    previewRows.forEach(row => {
        const tr = document.createElement('tr');
        
        for (let i = 0; i < Math.max(...previewRows.map(row => row.length)); i++) {
            const td = document.createElement('td');
            td.textContent = row[i] || '';
            tr.appendChild(td);
        }
        
        tbody.appendChild(tr);
    });
    
    table.appendChild(tbody);
    previewContainer.appendChild(table);
    
    // แสดงจำนวนรายการทั้งหมด
    const totalRecords = document.getElementById('totalRecords');
    if (totalRecords) {
        totalRecords.textContent = importedData.length;
    }
}

/**
 * ตรวจสอบการแม็ปฟิลด์
 * @returns {boolean} - ผลการตรวจสอบ
 */
function validateFieldMapping() {
    // ตรวจสอบฟิลด์ที่จำเป็น
    const requiredFields = ['student_code', 'title', 'firstname', 'lastname'];
    let isValid = true;
    
    // รวบรวมการแม็ปฟิลด์
    const mappings = {};
    
    // ตรวจสอบแต่ละฟิลด์
    requiredFields.forEach(field => {
        const select = document.getElementById(`map_${field}`);
        if (!select) return;
        
        mappings[field] = parseInt(select.value);
        
        if (mappings[field] < 0) {
            select.classList.add('is-invalid');
            isValid = false;
        } else {
            select.classList.remove('is-invalid');
        }
    });
    
    // ตรวจสอบฟิลด์เพิ่มเติม
    const optionalFields = ['phone', 'email', 'level', 'group', 'department', 'status'];
    
    optionalFields.forEach(field => {
        const select = document.getElementById(`map_${field}`);
        if (!select) return;
        
        mappings[field] = parseInt(select.value);
        select.classList.remove('is-invalid');
    });
    
    if (!isValid) {
        showAlert('กรุณาเลือกแม็ปฟิลด์ที่จำเป็นทั้งหมด (รหัสนักเรียน, คำนำหน้า, ชื่อ, นามสกุล)', 'error');
        return false;
    }
    
    // ตรวจสอบการแม็ปซ้ำ
    const usedIndices = Object.values(mappings).filter(index => index >= 0);
    const uniqueIndices = new Set(usedIndices);
    
    if (usedIndices.length !== uniqueIndices.size) {
        showAlert('พบการแม็ปคอลัมน์ซ้ำ กรุณาตรวจสอบอีกครั้ง', 'error');
        return false;
    }
    
    return true;
}

/**
 * เตรียมข้อมูลสำหรับนำเข้า
 */
function prepareImportData() {
    // รวบรวมการแม็ปฟิลด์
    const mappings = {
        student_code: parseInt(document.getElementById('map_student_code').value),
        title: parseInt(document.getElementById('map_title').value),
        firstname: parseInt(document.getElementById('map_firstname').value),
        lastname: parseInt(document.getElementById('map_lastname').value),
        phone: parseInt(document.getElementById('map_phone').value),
        email: parseInt(document.getElementById('map_email').value),
        level: parseInt(document.getElementById('map_level').value),
        group: parseInt(document.getElementById('map_group').value),
        department: parseInt(document.getElementById('map_department').value),
        status: parseInt(document.getElementById('map_status').value)
    };
    
    // เตรียมข้อมูลแม็ป
    mappedData = importedData.map(row => {
        const mappedRow = {};
        
        // แม็ปแต่ละฟิลด์
        for (const [field, index] of Object.entries(mappings)) {
            if (index >= 0 && index < row.length) {
                mappedRow[field] = row[index] || '';
            } else {
                mappedRow[field] = '';
            }
        }
        
        return mappedRow;
    });
    
    // กรองเฉพาะแถวที่มีข้อมูลสำคัญครบถ้วน
    mappedData = mappedData.filter(row => 
        row.student_code && row.title && row.firstname && row.lastname
    );
    
    // สร้างสรุปข้อมูล
    showImportSummary();
}

/**
 * แสดงสรุปข้อมูลที่จะนำเข้า
 */
function showImportSummary() {
    const summaryContainer = document.getElementById('importSummary');
    if (!summaryContainer) return;
    
    // ล้างข้อมูลเดิม
    summaryContainer.innerHTML = '';
    
    // สร้างสรุปจำนวน
    const summaryDiv = document.createElement('div');
    summaryDiv.className = 'import-summary-stats';
    summaryDiv.innerHTML = `
        <div class="summary-stat">
            <div class="summary-value">${mappedData.length}</div>
            <div class="summary-label">รายการที่พร้อมนำเข้า</div>
        </div>
        <div class="summary-stat">
            <div class="summary-value">${importedData.length - mappedData.length}</div>
            <div class="summary-label">รายการที่ไม่สมบูรณ์ (จะข้าม)</div>
        </div>
    `;
    
    summaryContainer.appendChild(summaryDiv);
    
    // สร้างตารางตัวอย่าง
    const table = document.createElement('table');
    table.className = 'data-preview-table';
    
    // สร้างส่วนหัวตาราง
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    const headers = ['รหัสนักเรียน', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ชั้น/กลุ่ม', 'แผนกวิชา', 'สถานะ'];
    
    headers.forEach(header => {
        const th = document.createElement('th');
        th.textContent = header;
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // สร้างเนื้อหาตาราง
    const tbody = document.createElement('tbody');
    const previewRows = mappedData.slice(0, 5);
    
    previewRows.forEach(row => {
        const tr = document.createElement('tr');
        
        // สร้างเซลล์ข้อมูล
        const cell1 = document.createElement('td');
        cell1.textContent = row.student_code;
        tr.appendChild(cell1);
        
        const cell2 = document.createElement('td');
        cell2.textContent = row.title;
        tr.appendChild(cell2);
        
        const cell3 = document.createElement('td');
        cell3.textContent = row.firstname;
        tr.appendChild(cell3);
        
        const cell4 = document.createElement('td');
        cell4.textContent = row.lastname;
        tr.appendChild(cell4);
        
        const cell5 = document.createElement('td');
        cell5.textContent = row.level && row.group ? `${row.level}/${row.group}` : '-';
        tr.appendChild(cell5);
        
        const cell6 = document.createElement('td');
        cell6.textContent = row.department || '-';
        tr.appendChild(cell6);
        
        const cell7 = document.createElement('td');
        cell7.textContent = row.status || 'กำลังศึกษา';
        tr.appendChild(cell7);
        
        tbody.appendChild(tr);
    });
    
    // แสดงข้อความถ้ามีรายการมากกว่าที่แสดง
    if (mappedData.length > previewRows.length) {
        const trMore = document.createElement('tr');
        const tdMore = document.createElement('td');
        tdMore.colSpan = headers.length;
        tdMore.className = 'text-center';
        tdMore.textContent = `... และอีก ${mappedData.length - previewRows.length} รายการ`;
        trMore.appendChild(tdMore);
        tbody.appendChild(trMore);
    }
    
    table.appendChild(tbody);
    summaryContainer.appendChild(table);
    
    // กำหนดชั้นเรียนปลายทางถ้ามีการเลือก
    const classId = document.getElementById('import_class_id');
    const classText = document.getElementById('selected_class_text');
    
    if (classId && classText) {
        if (classId.value) {
            const selectedOption = classId.options[classId.selectedIndex];
            classText.textContent = selectedOption.text;
            classText.style.display = 'block';
        } else {
            classText.style.display = 'none';
        }
    }
}

/**
 * แสดงการโหลด
 */
function showLoading() {
    const loadingEl = document.getElementById('loadingOverlay');
    if (loadingEl) {
        loadingEl.style.display = 'flex';
    }
}

/**
 * ซ่อนการโหลด
 */
function hideLoading() {
    const loadingEl = document.getElementById('loadingOverlay');
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, error, warning, info)
 */
function showAlert(message, type = 'info') {
    // ตรวจสอบว่ามีคอนเทนเนอร์หรือไม่
    let container = document.getElementById('alertContainer');
    
    if (!container) {
        // สร้างคอนเทนเนอร์ใหม่
        container = document.createElement('div');
        container.id = 'alertContainer';
        container.className = 'alert-container';
        document.body.appendChild(container);
    }
    
    // สร้างการแจ้งเตือน
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">
            <span class="alert-message">${message}</span>
        </div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม event listener สำหรับปุ่มปิด
    alert.querySelector('.alert-close').addEventListener('click', function() {
        container.removeChild(alert);
    });
    
    // เพิ่มการแจ้งเตือนลงในคอนเทนเนอร์
    container.appendChild(alert);
    
    // ลบการแจ้งเตือนหลังจาก 5 วินาที
    setTimeout(() => {
        if (container.contains(alert)) {
            container.removeChild(alert);
        }
    }, 5000);
}

/**
 * ปิดโมดัล
 * @param {string} modalId - ID ของโมดัล
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}
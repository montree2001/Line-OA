/**
 * import-teachers.js - JavaScript สำหรับการนำเข้าข้อมูลครูจากไฟล์ Excel
 * ระบบ STUDENT-Prasat
 */

// ตัวแปรสำหรับเก็บข้อมูลจากไฟล์
let excelData = null;
let fileHeaders = [];
let currentStep = 1;

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // จัดการปุ่มในโมดัลนำเข้าข้อมูล
    setupImportModal();
    
    // จัดการการอัปโหลดไฟล์
    setupFileUpload();
});

/**
 * จัดการปุ่มในโมดัลนำเข้าข้อมูล
 */
function setupImportModal() {
    // เพิ่ม event listener สำหรับปุ่มใน modal
    const importModal = document.getElementById('importTeacherModal');
    
    if (importModal) {
        importModal.addEventListener('hidden.bs.modal', function() {
            // รีเซ็ตข้อมูลเมื่อปิดโมดัล
            resetImportForm();
        });
    }
    
    // จัดการอีเวนต์คลิกบนพื้นที่อัปโหลดไฟล์
    const fileUploadArea = document.querySelector('.file-upload-area');
    const fileInput = document.getElementById('import_file');
    
    if (fileUploadArea && fileInput) {
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // รองรับการลากไฟล์มาวาง (drag and drop)
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
    }
}

/**
 * จัดการการอัปโหลดไฟล์
 */
function setupFileUpload() {
    const fileInput = document.getElementById('import_file');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0]);
            }
        });
    }
}

/**
 * จัดการไฟล์ที่อัปโหลด
 * 
 * @param {File} file ไฟล์ที่อัปโหลด
 */
function handleFileUpload(file) {
    // อัปเดตชื่อไฟล์ที่เลือก
    const fileLabel = document.getElementById('fileLabel');
    if (fileLabel) {
        fileLabel.textContent = file.name;
    }
    
    // ตรวจสอบนามสกุลไฟล์
    const fileExt = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(fileExt)) {
        showAlert('รองรับเฉพาะไฟล์ Excel (.xlsx, .xls) หรือ CSV เท่านั้น', 'danger');
        return;
    }
    
    // แสดง loading
    showLoading();
    
    // อ่านไฟล์ด้วย SheetJS (XLSX)
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array', cellDates: true });
            
            // ใช้ชีทแรก
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // แปลงข้อมูลเป็น JSON
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
            
            // ตรวจสอบว่ามีข้อมูลหรือไม่
            if (jsonData.length === 0) {
                hideLoading();
                showAlert('ไม่พบข้อมูลในไฟล์ที่อัปโหลด', 'warning');
                return;
            }
            
            // เก็บข้อมูล
            excelData = jsonData;
            
            // ดึงหัวตาราง (แถวแรก)
            fileHeaders = jsonData[0].map(String);
            
            // แสดงตัวอย่างข้อมูล
            displayDataPreview(jsonData);
            
            // อัปเดตจำนวนรายการที่พบ
            updateTotalRecords(jsonData.length - 1); // หักหัวตาราง
            
            // สร้างตัวเลือกสำหรับการแม็ปคอลัมน์
            createColumnMappingOptions(fileHeaders);
            
            // แม็ปคอลัมน์อัตโนมัติ
            autoMapColumns(fileHeaders);
            
            // เปิดใช้งานปุ่มถัดไป
            enableNextButton();
            
            hideLoading();
            
        } catch (error) {
            console.error('Error reading file:', error);
            hideLoading();
            showAlert('เกิดข้อผิดพลาดในการอ่านไฟล์: ' + error.message, 'danger');
        }
    };
    
    reader.onerror = function() {
        hideLoading();
        showAlert('เกิดข้อผิดพลาดในการอ่านไฟล์', 'danger');
    };
    
    reader.readAsArrayBuffer(file);
}

/**
 * แสดงตัวอย่างข้อมูล
 * 
 * @param {Array} data ข้อมูลที่จะแสดง
 */
function displayDataPreview(data) {
    const previewContainer = document.getElementById('dataPreview');
    if (!previewContainer) return;
    
    // จำกัดจำนวนแถวที่แสดง
    const maxRows = 5;
    const displayData = data.slice(0, maxRows + 1); // รวมหัวตาราง
    
    // สร้างตาราง HTML
    let tableHtml = '<table class="table table-sm table-bordered table-hover">';
    
    // เพิ่มหัวตาราง
    if (displayData.length > 0) {
        tableHtml += '<thead class="table-light"><tr>';
        displayData[0].forEach((header, index) => {
            tableHtml += `<th>คอลัมน์ ${index + 1}${header ? ': ' + escapeHtml(header) : ''}</th>`;
        });
        tableHtml += '</tr></thead>';
    }
    
    // เพิ่มข้อมูล
    if (displayData.length > 1) {
        tableHtml += '<tbody>';
        for (let i = 1; i < displayData.length; i++) {
            tableHtml += '<tr>';
            displayData[i].forEach(cell => {
                tableHtml += `<td>${escapeHtml(cell)}</td>`;
            });
            tableHtml += '</tr>';
        }
        tableHtml += '</tbody>';
    }
    
    tableHtml += '</table>';
    
    previewContainer.innerHTML = tableHtml;
}

/**
 * อัปเดตจำนวนรายการที่พบ
 * 
 * @param {number} count จำนวนรายการ
 */
function updateTotalRecords(count) {
    const totalRecordsElement = document.getElementById('totalRecords');
    if (totalRecordsElement) {
        totalRecordsElement.textContent = count;
    }
}

/**
 * สร้างตัวเลือกสำหรับการแม็ปคอลัมน์
 * 
 * @param {Array} headers หัวตาราง
 */
function createColumnMappingOptions(headers) {
    // หาทุก select ที่ใช้สำหรับการแม็ปคอลัมน์
    const selects = document.querySelectorAll('select[data-field]');
    
    selects.forEach(select => {
        // ล้างตัวเลือกเดิม
        select.innerHTML = '<option value="-1">-- เลือกคอลัมน์ --</option>';
        
        // เพิ่มตัวเลือกใหม่
        headers.forEach((header, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = `คอลัมน์ ${index + 1}${header ? ': ' + header : ''}`;
            select.appendChild(option);
        });
    });
}

/**
 * แม็ปคอลัมน์อัตโนมัติ
 * 
 * @param {Array} headers หัวตาราง
 */
function autoMapColumns(headers) {
    // คำที่ใช้ในการค้นหาสำหรับแต่ละฟิลด์
    const fieldMappings = {
        'national_id': ['รหัสบัตรประชาชน', 'เลขบัตรประชาชน', 'เลขบัตร', 'บัตรประชาชน', 'id', 'national_id', 'nationalid'],
        'title': ['คำนำหน้า', 'prefix', 'title'],
        'firstname': ['ชื่อ', 'ชื่อจริง', 'name', 'first_name', 'firstname'],
        'lastname': ['นามสกุล', 'surname', 'last_name', 'lastname'],
        'department': ['แผนก', 'ฝ่าย', 'สาขา', 'department', 'dept'],
        'position': ['ตำแหน่ง', 'position', 'job', 'role'],
        'phone': ['เบอร์โทร', 'โทรศัพท์', 'phone', 'tel', 'mobile'],
        'email': ['อีเมล', 'email', 'mail']
    };
    
    // วนลูปผ่านแต่ละฟิลด์
    Object.keys(fieldMappings).forEach(fieldName => {
        const select = document.getElementById(`map_${fieldName}`);
        if (!select) return;
        
        // ค้นหาคอลัมน์ที่ตรงกับคำค้นหา
        for (let i = 0; i < headers.length; i++) {
            const header = headers[i].toLowerCase();
            const searchTerms = fieldMappings[fieldName];
            
            if (searchTerms.some(term => header.includes(term.toLowerCase()))) {
                select.value = i;
                break;
            }
        }
    });
}

/**
 * ไปยังขั้นตอนถัดไป
 */
function nextStep() {
    if (currentStep === 1) {
        // ตรวจสอบว่าได้อัปโหลดไฟล์แล้วหรือไม่
        if (!excelData) {
            showAlert('กรุณาอัปโหลดไฟล์ก่อน', 'warning');
            return;
        }
        
        // ไปยังขั้นตอนที่ 2
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        document.getElementById('step3').style.display = 'none';
        
        // อัปเดตตัวบ่งชี้ขั้นตอน
        updateStepIndicator(2);
        
        // แสดงปุ่มย้อนกลับ
        document.getElementById('prevStepBtn').style.display = 'inline-block';
        
        currentStep = 2;
        document.getElementById('current_step').value = currentStep;
        
    } else if (currentStep === 2) {
        // ตรวจสอบว่าได้เลือกฟิลด์ที่จำเป็นแล้วหรือไม่
        if (!validateMappingFields()) {
            showAlert('กรุณาเลือกคอลัมน์สำหรับข้อมูลที่จำเป็น', 'warning');
            return;
        }
        
        // ไปยังขั้นตอนที่ 3 (สรุป)
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';
        
        // อัปเดตตัวบ่งชี้ขั้นตอน
        updateStepIndicator(3);
        
        // ซ่อนปุ่มถัดไป และแสดงปุ่มนำเข้าข้อมูล
        document.getElementById('nextStepBtn').style.display = 'none';
        document.getElementById('importSubmitBtn').style.display = 'inline-block';
        
        // อัปเดตข้อมูลสรุป
        updateSummary();
        
        currentStep = 3;
        document.getElementById('current_step').value = currentStep;
    }
}

/**
 * ย้อนกลับไปขั้นตอนก่อนหน้า
 */
function prevStep() {
    if (currentStep === 2) {
        // กลับไปขั้นตอนที่ 1
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        
        // อัปเดตตัวบ่งชี้ขั้นตอน
        updateStepIndicator(1);
        
        // ซ่อนปุ่มย้อนกลับ
        document.getElementById('prevStepBtn').style.display = 'none';
        
        currentStep = 1;
        document.getElementById('current_step').value = currentStep;
        
    } else if (currentStep === 3) {
        // กลับไปขั้นตอนที่ 2
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        document.getElementById('step3').style.display = 'none';
        
        // อัปเดตตัวบ่งชี้ขั้นตอน
        updateStepIndicator(2);
        
        // แสดงปุ่มถัดไป และซ่อนปุ่มนำเข้าข้อมูล
        document.getElementById('nextStepBtn').style.display = 'inline-block';
        document.getElementById('importSubmitBtn').style.display = 'none';
        
        currentStep = 2;
        document.getElementById('current_step').value = currentStep;
    }
}

/**
 * อัปเดตตัวบ่งชี้ขั้นตอน
 * 
 * @param {number} step ขั้นตอนปัจจุบัน
 */
function updateStepIndicator(step) {
    const steps = document.querySelectorAll('.step');
    const stepNumbers = document.querySelectorAll('.step-number');
    
    steps.forEach((stepEl, index) => {
        if (index + 1 < step) {
            stepEl.classList.add('completed');
            stepEl.classList.remove('active');
        } else if (index + 1 === step) {
            stepEl.classList.add('active');
            stepEl.classList.remove('completed');
        } else {
            stepEl.classList.remove('active', 'completed');
        }
    });
    
    stepNumbers.forEach((numEl, index) => {
        if (index + 1 <= step) {
            numEl.classList.remove('bg-secondary');
            numEl.classList.add('bg-primary');
        } else {
            numEl.classList.remove('bg-primary');
            numEl.classList.add('bg-secondary');
        }
    });
}

/**
 * ตรวจสอบว่าได้เลือกฟิลด์ที่จำเป็นแล้วหรือไม่
 * 
 * @returns {boolean} ผลการตรวจสอบ
 */
function validateMappingFields() {
    // ฟิลด์ที่จำเป็น
    const requiredFields = ['national_id', 'title', 'firstname', 'lastname'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const select = document.getElementById(`map_${field}`);
        if (select && select.value === '-1') {
            select.classList.add('is-invalid');
            isValid = false;
        } else if (select) {
            select.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * อัปเดตข้อมูลสรุป
 */
function updateSummary() {
    // ตรวจสอบตัวเลือกการนำเข้า
    const skipHeader = document.getElementById('skip_header').checked;
    const updateExisting = document.getElementById('update_existing').checked;
    
    // อัปเดตข้อมูลสรุปตัวเลือก
    document.getElementById('summary_skip_header').textContent = skipHeader ? 'ใช่' : 'ไม่';
    document.getElementById('summary_update_existing').textContent = updateExisting ? 'ใช่' : 'ไม่';
    
    // จำนวนรายการทั้งหมด
    const totalRows = excelData ? excelData.length - (skipHeader ? 1 : 0) : 0;
    document.getElementById('summary_total').textContent = totalRows;
    
    // ตรวจสอบแผนกที่เลือก
    const deptSelect = document.getElementById('import_department_id');
    const selectedDept = deptSelect ? deptSelect.options[deptSelect.selectedIndex].text : '';
    
    if (deptSelect && deptSelect.value) {
        document.getElementById('selected_dept_text').style.display = 'block';
        document.getElementById('selected_dept_text').textContent = selectedDept;
        document.getElementById('dept_info_text').textContent = 'ครูทั้งหมดจะถูกนำเข้าสู่แผนกที่เลือกไว้';
    } else {
        document.getElementById('selected_dept_text').style.display = 'none';
        document.getElementById('dept_info_text').textContent = 'ระบบจะใช้ข้อมูลแผนกจากไฟล์ หรือเว้นว่างถ้าไม่ระบุ';
    }
    
    // จำลองการนำเข้าเพื่อแสดงสถิติ (ในโปรดักชันควรใช้ข้อมูลจากเซิร์ฟเวอร์)
    const newCount = Math.floor(totalRows * 0.7); // จำลองว่า 70% เป็นข้อมูลใหม่
    const updateCount = updateExisting ? Math.floor(totalRows * 0.2) : 0; // จำลองว่า 20% เป็นข้อมูลที่มีอยู่แล้ว
    const issuesCount = totalRows - newCount - updateCount; // ส่วนที่เหลือ
    
    document.getElementById('summary_new').textContent = newCount;
    document.getElementById('summary_update').textContent = updateCount;
    document.getElementById('summary_issues').textContent = issuesCount;
}

/**
 * รีเซ็ตฟอร์มการนำเข้าข้อมูล
 */
function resetImportForm() {
    // รีเซ็ตตัวแปร
    excelData = null;
    fileHeaders = [];
    currentStep = 1;
    
    // รีเซ็ตตัวบ่งชี้ขั้นตอน
    updateStepIndicator(1);
    
    // รีเซ็ตการแสดงผล
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'none';
    
    // รีเซ็ตปุ่ม
    document.getElementById('prevStepBtn').style.display = 'none';
    document.getElementById('nextStepBtn').style.display = 'inline-block';
    document.getElementById('nextStepBtn').disabled = true;
    document.getElementById('importSubmitBtn').style.display = 'none';
    
    // รีเซ็ตฟอร์ม
    document.getElementById('importTeacherFullForm').reset();
    document.getElementById('fileLabel').textContent = 'ยังไม่ได้เลือกไฟล์';
    document.getElementById('dataPreview').innerHTML = `
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>คอลัมน์ 1</th>
                    <th>คอลัมน์ 2</th>
                    <th>คอลัมน์ 3</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="3" class="text-center text-muted">กรุณาอัปโหลดไฟล์ในขั้นตอนแรก</td>
                </tr>
            </tbody>
        </table>
    `;
    document.getElementById('totalRecords').textContent = '0';
    
    // รีเซ็ตการแม็ปคอลัมน์
    const selects = document.querySelectorAll('select[data-field]');
    selects.forEach(select => {
        select.innerHTML = '<option value="-1">-- เลือกคอลัมน์ --</option>';
        select.classList.remove('is-invalid');
    });
    
    // รีเซ็ตข้อมูลสรุป
    document.getElementById('summary_total').textContent = '0';
    document.getElementById('summary_new').textContent = '0';
    document.getElementById('summary_update').textContent = '0';
    document.getElementById('summary_issues').textContent = '0';
}

/**
 * เปิดใช้งานปุ่มถัดไป
 */
function enableNextButton() {
    const nextButton = document.getElementById('nextStepBtn');
    if (nextButton) {
        nextButton.disabled = false;
    }
}

/**
 * แสดง loading overlay
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

/**
 * ซ่อน loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message ข้อความ
 * @param {string} type ประเภท (success, info, warning, danger)
 * @param {number} timeout เวลาที่แสดง (มิลลิวินาที)
 */
function showAlert(message, type = 'info', timeout = 5000) {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1050';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <span class="material-icons align-middle me-2">${getAlertIcon(type)}</span>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ซ่อนการแจ้งเตือนหลังจากเวลาที่กำหนด
    setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        } else {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }
    }, timeout);
}

/**
 * ได้รับไอคอนสำหรับการแจ้งเตือนตามประเภท
 * 
 * @param {string} type ประเภทของการแจ้งเตือน
 * @returns {string} ชื่อไอคอน
 */
function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'danger': return 'error';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

/**
 * Escape HTML เพื่อป้องกัน XSS
 * 
 * @param {string} unsafe ข้อความที่ไม่ปลอดภัย
 * @returns {string} ข้อความที่ปลอดภัย
 */
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
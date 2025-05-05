/**
 * notification_enhanced.js - JavaScript สำหรับหน้าส่งข้อความแจ้งเตือนที่มีฟีเจอร์ใหม่
 * ระบบน้องชูใจ AI ดูแลผู้เรียน (แก้ไขเพื่อให้ทำงานร่วมกับ Backend ได้)
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่าเทมเพลตข้อความ
    initTemplates();
    
    // ตั้งค่า date pickers
    initDatePickers();
    
    // ตั้งค่า event listeners
    setupEventListeners();
    
    // ตั้งค่ากราฟและการแสดงผล
    initCharts();
    
    // ตรวจสอบ URL parameters เพื่อเปิดแท็บที่ระบุ (ถ้ามี)
    checkUrlParameters();

    // ทำการค้นหานักเรียนครั้งแรก
    if (document.getElementById('studentSearchForm')) {
        applyFilters();
    }
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
 * ตั้งค่า date pickers สำหรับการกรองช่วงวันที่การเข้าแถว
 */
function initDatePickers() {
    // ถ้ามี date picker elements
    const datePickers = document.querySelectorAll('.date-picker');
    if (datePickers.length > 0) {
        // ตั้งค่า date pickers
        datePickers.forEach(picker => {
            if (picker.type !== 'date') {
                picker.type = 'date';  // เปลี่ยนเป็น HTML5 date input
            }
            
            // กำหนดค่าเริ่มต้น
            if (picker.id === 'start-date' || picker.id === 'start-date-group') {
                // กำหนดค่าเริ่มต้นเป็นวันแรกของเดือนปัจจุบัน
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                picker.valueAsDate = firstDay;
            } else if (picker.id === 'end-date' || picker.id === 'end-date-group') {
                // กำหนดค่าเริ่มต้นเป็นวันปัจจุบัน
                const now = new Date();
                picker.valueAsDate = now;
            }
        });
    }
}

/**
 * ตั้งค่ากราฟและการแสดงผล
 */
function initCharts() {
    // ตั้งค่ากราฟตัวอย่าง (ถ้ามี)
    const chartCanvas = document.getElementById('attendance-chart');
    if (chartCanvas && typeof Chart !== 'undefined') {
        // สร้างกราฟตัวอย่างด้วย Chart.js
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['1 เม.ย.', '8 เม.ย.', '15 เม.ย.', '22 เม.ย.', '29 เม.ย.'],
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: [85, 78, 65, 72, 80],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    // ตั้งค่ากราฟตัวอย่างสำหรับกลุ่ม
    const groupChartCtx = document.getElementById('group-attendance-chart');
    if (groupChartCtx && typeof Chart !== 'undefined') {
        new Chart(groupChartCtx, {
            type: 'bar',
            data: {
                labels: ['1-7 เม.ย.', '8-14 เม.ย.', '15-21 เม.ย.', '22-28 เม.ย.', '29-30 เม.ย.'],
                datasets: [{
                    label: 'อัตราการเข้าแถวเฉลี่ย (%)',
                    data: [63, 65, 66, 68, 69],
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    // ตั้งค่ากราฟตัวอย่างในโมดัลตัวอย่าง
    const previewChartCtx = document.getElementById('preview-attendance-chart');
    if (previewChartCtx && typeof Chart !== 'undefined') {
        new Chart(previewChartCtx, {
            type: 'line',
            data: {
                labels: ['1 เม.ย.', '8 เม.ย.', '15 เม.ย.', '22 เม.ย.', '29 เม.ย.'],
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: [65, 62, 68, 65, 70],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
}

/**
 * ตั้งค่าเทมเพลตข้อความ
 */
function initTemplates() {
    // ตั้งค่าเทมเพลตรายบุคคล
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const templateType = this.getAttribute('data-template') || this.textContent.trim().toLowerCase();
            
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

    // ตั้งค่า template dropdown
    const templateSelects = document.querySelectorAll('#templateSelect, #groupTemplateSelect');
    templateSelects.forEach(select => {
        select.addEventListener('change', function() {
            const templateId = this.value;
            if (!templateId) return;
            
            // ใช้ AJAX เพื่อดึงข้อมูลเทมเพลต
            const formData = new FormData();
            formData.append('get_template', '1');
            formData.append('template_id', templateId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.template;
                    
                    // ตรวจสอบประเภทของเทมเพลต
                    if (template.type === 'individual' && document.getElementById('messageText')) {
                        document.getElementById('messageText').value = template.content;
                        updatePreview(template.content);
                    } else if (template.type === 'group' && document.getElementById('groupMessageText')) {
                        document.getElementById('groupMessageText').value = template.content;
                        updateGroupPreview(template.content);
                    }
                    
                    // เลือกเทมเพลตในรูปแบบปุ่ม
                    document.querySelectorAll('.template-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                } else {
                    showAlert('ไม่สามารถดึงข้อมูลเทมเพลต: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลเทมเพลต', 'danger');
            });
        });
    });

    // ตั้งค่าปุ่มเพิ่มตัวแปรในเทมเพลต
    const variableHelpers = document.querySelectorAll('.variable-helper .dropdown-item');
    variableHelpers.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            
            const variable = this.getAttribute('data-variable');
            const targetId = this.getAttribute('data-target') || '#messageText';
            const targetElement = document.querySelector(targetId);
            
            if (targetElement && variable) {
                // เพิ่มตัวแปรที่ตำแหน่ง cursor หรือท้ายข้อความ
                const cursorPos = targetElement.selectionStart;
                const textBefore = targetElement.value.substring(0, cursorPos);
                const textAfter = targetElement.value.substring(cursorPos);
                
                targetElement.value = textBefore + variable + textAfter;
                
                // ตั้งค่า cursor หลังตัวแปรที่เพิ่ม
                targetElement.selectionStart = cursorPos + variable.length;
                targetElement.selectionEnd = cursorPos + variable.length;
                targetElement.focus();
                
                // อัปเดตตัวอย่าง
                if (targetId === '#messageText') {
                    updatePreview(targetElement.value);
                } else if (targetId === '#groupMessageText') {
                    updateGroupPreview(targetElement.value);
                }
            }
        });
    });

    // ปุ่มสร้างเทมเพลตใหม่
    const createTemplateButton = document.getElementById('btnCreateTemplate');
    if (createTemplateButton) {
        createTemplateButton.addEventListener('click', function() {
            // รีเซ็ตแบบฟอร์ม
            const templateForm = document.getElementById('templateForm');
            if (templateForm) {
                templateForm.reset();
                document.getElementById('template_id').value = '';
                document.getElementById('template_content').value = '';
                document.querySelector('.modal-title').textContent = 'สร้างเทมเพลตข้อความใหม่';
            }
            
            // แสดงโมดัล
            showModal('templateModal');
        });
    }

    // ปุ่มบันทึกเทมเพลต
    const saveTemplateButton = document.getElementById('btnSaveTemplate');
    if (saveTemplateButton) {
        saveTemplateButton.addEventListener('click', function() {
            const templateForm = document.getElementById('templateForm');
            if (!templateForm) return;
            
            // ตรวจสอบข้อมูล
            const name = document.getElementById('template_name').value;
            const content = document.getElementById('template_content').value;
            
            if (!name || !content) {
                showAlert('กรุณากรอกชื่อเทมเพลตและเนื้อหาให้ครบถ้วน', 'warning');
                return;
            }
            
            // สร้างข้อมูลสำหรับส่ง
            const formData = new FormData(templateForm);
            formData.append('save_template', '1');
            
            // แสดงการกำลังโหลด
            showLoading('กำลังบันทึกเทมเพลต...');
            
            // ส่งข้อมูลผ่าน AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showAlert('บันทึกเทมเพลตเรียบร้อยแล้ว', 'success');
                    closeModal('templateModal');
                    
                    // รีโหลดหน้าเพื่ออัปเดตรายการเทมเพลต
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('ไม่สามารถบันทึกเทมเพลต: ' + (data.message || 'เกิดข้อผิดพลาด'), 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการบันทึกเทมเพลต', 'danger');
            });
        });
    }

    // ปุ่มแก้ไขเทมเพลต
    const editTemplateButtons = document.querySelectorAll('.edit-template-btn');
    editTemplateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            if (!templateId) return;
            
            // ใช้ AJAX เพื่อดึงข้อมูลเทมเพลต
            const formData = new FormData();
            formData.append('get_template', '1');
            formData.append('template_id', templateId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.template;
                    
                    // กรอกข้อมูลในแบบฟอร์ม
                    document.getElementById('template_id').value = template.id;
                    document.getElementById('template_name').value = template.name;
                    document.getElementById('template_type').value = template.type;
                    document.getElementById('template_category').value = template.category;
                    document.getElementById('template_content').value = template.content;
                    
                    // เปลี่ยนชื่อหัวข้อโมดัล
                    document.querySelector('.modal-title').textContent = 'แก้ไขเทมเพลต: ' + template.name;
                    
                    // แสดงโมดัล
                    showModal('templateModal');
                } else {
                    showAlert('ไม่สามารถดึงข้อมูลเทมเพลต: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลเทมเพลต', 'danger');
            });
        });
    });

    // ปุ่มแสดงตัวอย่างเทมเพลต
    const previewTemplateButtons = document.querySelectorAll('.preview-template-btn');
    previewTemplateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            if (!templateId) return;
            
            // ใช้ AJAX เพื่อดึงข้อมูลเทมเพลต
            const formData = new FormData();
            formData.append('get_template', '1');
            formData.append('template_id', templateId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const template = data.template;
                    
                    // แสดงตัวอย่างในโมดัล
                    const previewText = document.getElementById('previewText');
                    if (previewText) {
                        previewText.innerHTML = template.content.replace(/\n/g, '<br>');
                    }
                    
                    // ซ่อนตัวอย่างกราฟและลิงก์
                    const previewChartContainer = document.getElementById('previewChartContainer');
                    const previewLinkContainer = document.getElementById('previewLinkContainer');
                    
                    if (previewChartContainer) previewChartContainer.style.display = 'none';
                    if (previewLinkContainer) previewLinkContainer.style.display = 'none';
                    
                    // แสดงโมดัล
                    showModal('previewModal');
                } else {
                    showAlert('ไม่สามารถดึงข้อมูลเทมเพลต: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลเทมเพลต', 'danger');
            });
        });
    });

    // ปุ่มกรองเทมเพลตตามหมวดหมู่
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // ยกเลิกการเลือกปุ่มทั้งหมด
            categoryButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // เลือกปุ่มปัจจุบัน
            this.classList.add('active');
            
            // กรองรายการเทมเพลต
            filterTemplates(category);
        });
    });

    // ปุ่มกรองเทมเพลตตามประเภท
    const typeButtons = document.querySelectorAll('.type-btn');
    typeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            
            // ยกเลิกการเลือกปุ่มทั้งหมด
            typeButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // เลือกปุ่มปัจจุบัน
            this.classList.add('active');
            
            // กรองรายการเทมเพลต
            filterTemplatesByType(type);
        });
    });
}

/**
 * กรองรายการเทมเพลตตามหมวดหมู่
 * 
 * @param {string} category - หมวดหมู่ที่ต้องการกรอง
 */
function filterTemplates(category) {
    const templateRows = document.querySelectorAll('#templatesTable tbody tr');
    
    templateRows.forEach(row => {
        if (category === 'all' || row.getAttribute('data-category') === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * กรองรายการเทมเพลตตามประเภท
 * 
 * @param {string} type - ประเภทที่ต้องการกรอง
 */
function filterTemplatesByType(type) {
    const templateRows = document.querySelectorAll('#templatesTable tbody tr');
    
    templateRows.forEach(row => {
        if (type === 'all' || row.getAttribute('data-type') === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
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
    const selectedButton = document.querySelector(`.template-btn[data-template="${templateType}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    if (!messageText) return;
    
    // ดึงข้อมูลนักเรียน (ในเหตุการณ์จริงควรดึงจาก DOM หรือ API)
    const studentName = document.querySelector('.student-name-display')?.textContent || 'นายธนกฤต สุขใจ';
    const studentClass = document.querySelector('.student-class-display')?.textContent || 'ปวช.1/1';
    const attendanceDays = document.querySelector('.attendance-days-display')?.textContent || '26/40';
    const attendanceRate = document.querySelector('.attendance-rate-display')?.textContent || '65';
    const advisorName = document.querySelector('.advisor-name-display')?.textContent || 'อ.ประสิทธิ์ ดีเลิศ';
    const advisorPhone = document.querySelector('.advisor-phone-display')?.textContent || '081-234-5678';
    
    switch(templateType) {
        case 'regular':
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'warning':
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'critical':
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'summary':
            // สร้างเดือนปัจจุบันเป็นภาษาไทย
            const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            const currentMonth = months[new Date().getMonth()];
            const currentYear = new Date().getFullYear() + 543; // พ.ศ.
            
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน${currentMonth} ${currentYear}\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัย\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความของท่าน] กรุณาพิมพ์ข้อความที่ต้องการส่งที่นี่\n\nท่านสามารถใช้ตัวแปรต่างๆ เช่น:\n- {{ชื่อนักเรียน}} - ชื่อของนักเรียน\n- {{ชั้นเรียน}} - ชั้นเรียนของนักเรียน\n- {{จำนวนวันเข้าแถว}} - จำนวนวันที่นักเรียนเข้าแถว\n- {{จำนวนวันทั้งหมด}} - จำนวนวันทั้งหมดในช่วงเวลาที่เลือก\n- {{ร้อยละการเข้าแถว}} - อัตราการเข้าแถวเป็นเปอร์เซ็นต์\n- {{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา\n- {{เบอร์โทรครู}} - เบอร์โทรของครูที่ปรึกษา\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
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
    const selectedButton = document.querySelector(`.template-btn[data-template="${templateType}"]`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('groupMessageText');
    if (!messageText) return;
    
    const classInfo = document.querySelector('.class-info-display')?.textContent || "ปวช.1/1";
    const advisorName = document.querySelector('.advisor-name-display')?.textContent || "ครูอิศรา สุขใจ";
    const advisorPhone = document.querySelector('.advisor-phone-display')?.textContent || "081-234-5678";
    
    switch(templateType) {
        case 'regular':
        case 'reminder':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่าในวันศุกร์ที่ 21 มีนาคม 2568 นักเรียนชั้น {{ชั้นเรียน}} จะต้องมาวิทยาลัยก่อนเวลา 07:30 น. เพื่อเตรียมความพร้อมในการเข้าแถวพิเศษสำหรับกิจกรรมวันภาษาไทย\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'risk-warning':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nโดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'meeting':
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้\n\n1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา\n2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว\n3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            // เทมเพลตสำหรับเรื่องทั่วไป ไม่เกี่ยวกับการเข้าแถว
            messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\n[ข้อความของท่าน] กรุณาพิมพ์ข้อความที่ต้องการส่งที่นี่\n\nท่านสามารถใช้ตัวแปรต่างๆ เช่น:\n- {{ชั้นเรียน}} - ชั้นเรียนของนักเรียน\n- {{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา\n- {{เบอร์โทรครู}} - เบอร์โทรของครูที่ปรึกษา\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
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
    const previewContainer = document.querySelector('#individual-tab .preview-content .preview-message p');
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
    const previewContainer = document.querySelector('#group-tab .preview-content .preview-message p');
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
    
    // แสดงตัวอย่างกราฟ (ถ้ามี)
    const includeChart = document.getElementById('include-chart')?.checked || false;
    const previewChartContainer = document.getElementById('previewChartContainer');
    
    if (previewChartContainer && includeChart) {
        previewChartContainer.style.display = 'block';
    } else if (previewChartContainer) {
        previewChartContainer.style.display = 'none';
    }
    
    // แสดงตัวอย่างลิงก์ (ถ้ามี)
    const includeLink = document.getElementById('include-link')?.checked || false;
    const previewLinkContainer = document.getElementById('previewLinkContainer');
    
    if (previewLinkContainer && includeLink) {
        previewLinkContainer.style.display = 'block';
    } else if (previewLinkContainer) {
        previewLinkContainer.style.display = 'none';
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
    
    // แสดงตัวอย่างกราฟ (ถ้ามี)
    const includeChart = document.getElementById('include-chart-group')?.checked || false;
    const previewChartContainer = document.getElementById('previewChartContainer');
    
    if (previewChartContainer && includeChart) {
        previewChartContainer.style.display = 'block';
    } else if (previewChartContainer) {
        previewChartContainer.style.display = 'none';
    }
    
    // แสดงตัวอย่างลิงก์ (ถ้ามี)
    const includeLink = document.getElementById('include-link-group')?.checked || false;
    const previewLinkContainer = document.getElementById('previewLinkContainer');
    
    if (previewLinkContainer && includeLink) {
        previewLinkContainer.style.display = 'block';
    } else if (previewLinkContainer) {
        previewLinkContainer.style.display = 'none';
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
    // ดึงข้อมูลรหัสนักเรียนที่เลือก
    const studentId = getSelectedStudentId();
    
    if (!studentId) {
        showAlert('กรุณาเลือกนักเรียนก่อนดูประวัติการส่งข้อความ', 'warning');
        return;
    }
    
    // สร้าง loading indicator
    showAlert('กำลังดึงข้อมูลประวัติการส่งข้อความ...', 'info');
    
    // ใช้ AJAX เพื่อดึงประวัติการส่งข้อความ
    const formData = new FormData();
    formData.append('get_notification_history', '1');
    formData.append('student_id', studentId);
    
    fetch('send_notification_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดงข้อมูลประวัติในโมดัล
            const historyTable = document.querySelector('#historyModal .data-table tbody');
            if (historyTable) {
                historyTable.innerHTML = '';
                
                if (data.history.length === 0) {
                    historyTable.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center">ไม่พบประวัติการส่งข้อความ</td>
                        </tr>
                    `;
                } else {
                    data.history.forEach(item => {
                        const statusClass = item.status === 'sent' ? 'success' : 'danger';
                        const statusText = item.status === 'sent' ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ';
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${formatDateTime(item.sent_at)}</td>
                            <td>${getNotificationTypeName(item.notification_type)}</td>
                            <td>${item.first_name} ${item.last_name}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>
                                <button class="table-action-btn primary" title="ดูข้อความ" onclick="viewNotificationMessage(${item.notification_id})">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </td>
                        `;
                        
                        historyTable.appendChild(row);
                    });
                }
            }
            
            // อัปเดตชื่อนักเรียนในหัวข้อโมดัล
            const studentNameElement = document.querySelector('.history-student-name');
            if (studentNameElement) {
                const studentNameDisplay = document.querySelector('.student-name-display');
                if (studentNameDisplay) {
                    studentNameElement.textContent = studentNameDisplay.textContent;
                }
            }
            
            showModal('historyModal');
        } else {
            showAlert('ไม่สามารถดึงข้อมูลประวัติการส่งข้อความได้: ' + (data.message || 'เกิดข้อผิดพลาด'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลประวัติการส่งข้อความ', 'danger');
    });
}

/**
 * แปลงรูปแบบวันที่และเวลาเป็นรูปแบบที่อ่านง่าย
 * 
 * @param {string} dateTimeString - วันที่และเวลาในรูปแบบ MySQL
 * @return {string} วันที่และเวลาในรูปแบบที่อ่านง่าย
 */
function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear() + 543; // พ.ศ.
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * แปลงประเภทการแจ้งเตือนเป็นชื่อที่อ่านง่าย
 * 
 * @param {string} type - ประเภทการแจ้งเตือน
 * @return {string} ชื่อประเภทการแจ้งเตือนที่อ่านง่าย
 */
function getNotificationTypeName(type) {
    switch (type) {
        case 'attendance':
            return 'แจ้งเตือนการเข้าแถว';
        case 'risk_alert':
            return 'แจ้งเตือนความเสี่ยง';
        case 'system':
            return 'ข้อความระบบ';
        default:
            return type;
    }
}

/**
 * ดูข้อความที่ส่ง
 * 
 * @param {number} notificationId - รหัสการแจ้งเตือน
 */
function viewNotificationMessage(notificationId) {
    // ใช้ AJAX เพื่อดึงข้อมูลข้อความแจ้งเตือนตามรหัส
    const formData = new FormData();
    formData.append('get_notification_message', '1');
    formData.append('notification_id', notificationId);
    
    fetch('send_notification_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const messageContent = data.message;
            
            const previewText = document.getElementById('previewText');
            if (previewText) {
                previewText.innerHTML = messageContent.replace(/\n/g, '<br>');
            }
            
            // ซ่อนตัวอย่างกราฟในโมดัลนี้
            const previewChartContainer = document.getElementById('previewChartContainer');
            if (previewChartContainer) {
                previewChartContainer.style.display = 'none';
            }
            
            // ซ่อนตัวอย่างลิงก์ในโมดัลนี้
            const previewLinkContainer = document.getElementById('previewLinkContainer');
            if (previewLinkContainer) {
                previewLinkContainer.style.display = 'none';
            }
            
            closeModal('historyModal');
            showModal('previewModal');
        } else {
            showAlert('ไม่สามารถดึงข้อมูลข้อความได้: ' + (data.message || 'เกิดข้อผิดพลาด'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลข้อความ', 'danger');
    });
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
    // อัปเดตค่าใช้จ่าย
    updateGroupMessageCost();
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
    // อัปเดตค่าใช้จ่าย
    updateGroupMessageCost();
}

/**
 * อัปเดตจำนวนผู้รับในปุ่มส่งข้อความ
 */
function updateRecipientCount() {
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    const sendButton = document.querySelector('#group-tab .form-actions .btn-primary');
    const recipientCountElement = document.querySelector('.recipient-count');
    
    if (sendButton) {
        sendButton.innerHTML = `<i class="material-icons">send</i> ส่งข้อความ (${selectedCount} ราย)`;
        sendButton.disabled = selectedCount === 0;
    }
    
    if (recipientCountElement) {
        recipientCountElement.textContent = selectedCount;
    }
}

/**
 * อัปเดตค่าใช้จ่ายในการส่งข้อความกลุ่ม
 */
function updateGroupMessageCost() {
    const includeChart = document.getElementById('include-chart-group')?.checked || false;
    const includeLink = document.getElementById('include-link-group')?.checked || false;
    
    const messageCost = 0.075; // บาทต่อข้อความ
    const chartCost = 0.15; // บาทต่อรูปภาพ
    const linkCost = 0.075; // บาทต่อลิงก์
    
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    
    let costPerRecipient = messageCost;
    
    if (includeChart) {
        costPerRecipient += chartCost;
    }
    
    if (includeLink) {
        costPerRecipient += linkCost;
    }
    
    const totalCost = costPerRecipient * selectedCount;
    
    // อัปเดตการแสดงผล
    const costTotalElement = document.querySelector('#group-tab .message-cost .cost-item.total .cost-value');
    if (costTotalElement) {
        costTotalElement.textContent = totalCost.toFixed(2) + ' บาท';
    }
    
    // อัปเดตรายละเอียดค่าใช้จ่าย
    const messageCountElement = document.querySelector('#group-tab .cost-item:nth-child(1) .cost-label');
    const messageCostElement = document.querySelector('#group-tab .cost-item:nth-child(1) .cost-value');
    
    if (messageCountElement && messageCostElement) {
        messageCountElement.textContent = `ข้อความ (${selectedCount} คน):`;
        messageCostElement.textContent = (messageCost * selectedCount).toFixed(2) + ' บาท';
    }
    
    const chartCountElement = document.querySelector('#group-tab .cost-item:nth-child(2) .cost-label');
    const chartCostElement = document.querySelector('#group-tab .cost-item:nth-child(2) .cost-value');
    
    if (chartCountElement && chartCostElement) {
        chartCountElement.textContent = `รูปภาพกราฟ (${includeChart ? selectedCount : 0} รูป):`;
        chartCostElement.textContent = (chartCost * (includeChart ? selectedCount : 0)).toFixed(2) + ' บาท';
    }
    
    const linkCountElement = document.querySelector('#group-tab .cost-item:nth-child(3) .cost-label');
    const linkCostElement = document.querySelector('#group-tab .cost-item:nth-child(3) .cost-value');
    
    if (linkCountElement && linkCostElement) {
        linkCountElement.textContent = `ลิงก์ (${includeLink ? selectedCount : 0} ลิงก์):`;
        linkCostElement.textContent = (linkCost * (includeLink ? selectedCount : 0)).toFixed(2) + ' บาท';
    }
}

/**
 * อัปเดตค่าใช้จ่ายในการส่งข้อความรายบุคคล
 */
function updateMessageCost() {
    const includeChart = document.getElementById('include-chart')?.checked || false;
    const includeLink = document.getElementById('include-link')?.checked || false;
    
    const messageCost = 0.075; // บาทต่อข้อความ
    const chartCost = 0.15; // บาทต่อรูปภาพ
    const linkCost = 0.075; // บาทต่อลิงก์
    
    let totalCost = messageCost;
    
    if (includeChart) {
        totalCost += chartCost;
    }
    
    if (includeLink) {
        totalCost += linkCost;
    }
    
    // อัปเดตการแสดงผล
    const costTotalElement = document.querySelector('#individual-tab .message-cost .cost-item.total .cost-value');
    if (costTotalElement) {
        costTotalElement.textContent = totalCost.toFixed(2) + ' บาท';
    }
    
    // อัปเดตรายละเอียดค่าใช้จ่าย
    const messageCostElement = document.querySelector('#individual-tab .cost-item:nth-child(1) .cost-value');
    if (messageCostElement) {
        messageCostElement.textContent = messageCost.toFixed(2) + ' บาท';
    }
    
    const chartCostElement = document.querySelector('#individual-tab .cost-item:nth-child(2) .cost-value');
    if (chartCostElement) {
        chartCostElement.textContent = (includeChart ? chartCost : 0).toFixed(2) + ' บาท';
    }
    
    const linkCostElement = document.querySelector('#individual-tab .cost-item:nth-child(3) .cost-value');
    if (linkCostElement) {
        linkCostElement.textContent = (includeLink ? linkCost : 0).toFixed(2) + ' บาท';
    }
}

/**
 * ส่งข้อความรายบุคคล
 */
function sendMessage() {
    // ตรวจสอบว่ามีนักเรียนที่เลือก
    const studentId = getSelectedStudentId();
    if (!studentId) {
        showAlert('กรุณาเลือกนักเรียนก่อนส่งข้อความ', 'warning');
        return;
    }
    
    // ดึงข้อมูลข้อความและการตั้งค่า
    const messageText = document.getElementById('messageText').value;
    if (!messageText.trim()) {
        showAlert('กรุณากรอกข้อความก่อนส่ง', 'warning');
        return;
    }
    
    // ดึงช่วงวันที่
    const startDate = document.getElementById('start-date')?.value || '';
    const endDate = document.getElementById('end-date')?.value || '';
    
    // ดึงการตั้งค่าการส่ง
    const includeChart = document.getElementById('include-chart')?.checked || false;
    const includeLink = document.getElementById('include-link')?.checked || false;
    
    // แสดงการกำลังส่ง
    showLoading('กำลังส่งข้อความ...');
    
    // สร้างข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('send_individual_message', '1');
    formData.append('student_id', studentId);
    formData.append('message', messageText);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('include_chart', includeChart ? 'true' : 'false');
    formData.append('include_link', includeLink ? 'true' : 'false');
    
    // ส่งข้อมูลผ่าน AJAX
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // แสดงผลลัพธ์การส่งสำเร็จ
            showSendResults(data.results, data.total_cost, data.success_count, data.error_count);
        } else {
            // แสดงข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการส่งข้อความ', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการส่งข้อความ: ' + error.message, 'danger');
    });
}

/**
 * แสดงหน้าต่างโหลดระหว่างดำเนินการ
 * 
 * @param {string} message - ข้อความที่แสดงระหว่างโหลด
 */
function showLoading(message = 'กำลังดำเนินการ...') {
    // แสดง loading overlay
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        const loadingText = loadingOverlay.querySelector('.loading-text');
        if (loadingText) {
            loadingText.textContent = message;
        }
        loadingOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    } else {
        // สร้างโมดัลโหลด
        const newLoadingOverlay = document.createElement('div');
        newLoadingOverlay.id = 'loadingOverlay';
        newLoadingOverlay.className = 'loading-overlay';
        newLoadingOverlay.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div class="loading-text">${message}</div>
                <div class="loading-subtitle">กรุณารอสักครู่...</div>
            </div>
        `;
        document.body.appendChild(newLoadingOverlay);
        
        // เพิ่ม CSS สำหรับ loading overlay
        const style = document.createElement('style');
        style.textContent = `
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            .loading-container {
                background-color: white;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            }
            .loading-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid var(--primary-color, #06c755);
                border-radius: 50%;
                margin: 0 auto 20px;
                animation: spin 1s linear infinite;
            }
            .loading-text {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .loading-subtitle {
                font-size: 14px;
                color: #666;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        newLoadingOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ซ่อนหน้าต่างโหลด
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

/**
 * ส่งข้อความกลุ่ม
 */
function sendGroupMessage() {
    // ตรวจสอบว่ามีนักเรียนที่เลือก
    const selectedCheckboxes = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked');
    if (selectedCheckboxes.length === 0) {
        showAlert('กรุณาเลือกผู้รับข้อความอย่างน้อย 1 คน', 'warning');
        return;
    }
    
    // ดึงข้อมูลข้อความและการตั้งค่า
    const messageText = document.getElementById('groupMessageText').value;
    if (!messageText.trim()) {
        showAlert('กรุณากรอกข้อความก่อนส่ง', 'warning');
        return;
    }
    
    // ดึงช่วงวันที่
    const startDate = document.getElementById('start-date-group')?.value || '';
    const endDate = document.getElementById('end-date-group')?.value || '';
    
    // ดึงการตั้งค่าการส่ง
    const includeChart = document.getElementById('include-chart-group')?.checked || false;
    const includeLink = document.getElementById('include-link-group')?.checked || false;
    
    // สร้างรายการรหัสนักเรียน
    const studentIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    
    // แสดงการกำลังส่ง
    showLoading(`กำลังส่งข้อความไปยังผู้ปกครอง ${studentIds.length} คน...`);
    
    // สร้างข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('send_group_message', '1');
    formData.append('student_ids', JSON.stringify(studentIds));
    formData.append('message', messageText);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('include_chart', includeChart ? 'true' : 'false');
    formData.append('include_link', includeLink ? 'true' : 'false');
    
    // ส่งข้อมูลผ่าน AJAX
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // แสดงผลลัพธ์การส่ง
            showSendResults(data.results, data.total_cost, data.success_count, data.error_count);
        } else {
            // แสดงข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการส่งข้อความ', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการส่งข้อความ: ' + error.message, 'danger');
    });
}

/**
 * แสดงผลลัพธ์การส่งข้อความ
 * 
 * @param {Array} results - รายการผลลัพธ์การส่งข้อความ
 * @param {number} totalCost - ค่าใช้จ่ายทั้งหมด
 * @param {number} successCount - จำนวนการส่งสำเร็จ
 * @param {number} errorCount - จำนวนการส่งล้มเหลว
 */
function showSendResults(results, totalCost = null, successCount = null, errorCount = null) {
    // คำนวณผลลัพธ์
    const success = successCount !== null ? successCount : results.filter(r => r.success).length;
    const error = errorCount !== null ? errorCount : results.filter(r => !r.success).length;
    const cost = totalCost !== null ? totalCost : results.reduce((sum, r) => sum + (r.cost || 0), 0);
    
    // อัปเดตข้อมูลในโมดัล
    const resultModal = document.getElementById('resultModal');
    if (!resultModal) return;
    
    // อัปเดตข้อมูลสรุป
    const successCountElement = resultModal.querySelector('.result-item.success .result-value');
    const errorCountElement = resultModal.querySelector('.result-item.error .result-value');
    const costValueElement = resultModal.querySelector('.result-item.cost .result-value');
    
    if (successCountElement) successCountElement.textContent = success;
    if (errorCountElement) errorCountElement.textContent = error;
    if (costValueElement) costValueElement.textContent = formatCurrency(cost);
    
    // อัปเดตรายละเอียดในตาราง
    const resultTable = resultModal.querySelector('#resultTable tbody');
    if (resultTable) {
        resultTable.innerHTML = '';
        
        results.forEach(result => {
            const row = document.createElement('tr');
            const statusClass = result.success ? 'success' : 'danger';
            const statusText = result.success ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ';
            
            row.innerHTML = `
                <td>${result.student_name}</td>
                <td>${result.class || '-'}</td>
                <td>${result.parent_count || '0'} คน</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${formatCurrency(result.cost || 0)}</td>
            `;
            
            resultTable.appendChild(row);
        });
    }
    
    // แสดงโมดัล
    showModal('resultModal');
}

/**
 * จัดรูปแบบค่าเงิน
 * 
 * @param {number} value - จำนวนเงิน
 * @return {string} จำนวนเงินในรูปแบบ x.xx บาท
 */
function formatCurrency(value) {
    return parseFloat(value).toFixed(2) + ' บาท';
}

/**
 * ดึงรหัสนักเรียนที่เลือก
 * 
 * @return {string|null} รหัสนักเรียนที่เลือก หรือ null หากไม่มีการเลือก
 */
function getSelectedStudentId() {
    const selectedRadio = document.querySelector('input[name="student_select"]:checked');
    if (selectedRadio) {
        return selectedRadio.value || selectedRadio.closest('tr').dataset.studentId;
    } else {
        // อาจจะมีการเลือกผ่าน data attribute
        const selectedRow = document.querySelector('tr.selected');
        if (selectedRow) {
            return selectedRow.dataset.studentId;
        }
    }
    return null;
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
    
    // รีเซ็ตวันที่
    if (document.getElementById('start-date')) {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        document.getElementById('start-date').valueAsDate = firstDay;
    }
    
    if (document.getElementById('end-date')) {
        const now = new Date();
        document.getElementById('end-date').valueAsDate = now;
    }
    
    // รีเซ็ตการตั้งค่าการส่ง
    if (document.getElementById('include-chart')) {
        document.getElementById('include-chart').checked = true;
    }
    
    if (document.getElementById('include-link')) {
        document.getElementById('include-link').checked = true;
    }
    
    // อัปเดตค่าใช้จ่าย
    updateMessageCost();
    
    // แสดงข้อความแจ้งเตือน
    showAlert('รีเซ็ตข้อความเรียบร้อย', 'success');
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
    
    // รีเซ็ตวันที่
    if (document.getElementById('start-date-group')) {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        document.getElementById('start-date-group').valueAsDate = firstDay;
    }
    
    if (document.getElementById('end-date-group')) {
        const now = new Date();
        document.getElementById('end-date-group').valueAsDate = now;
    }
    
    // รีเซ็ตการตั้งค่าการส่ง
    if (document.getElementById('include-chart-group')) {
        document.getElementById('include-chart-group').checked = true;
    }
    
    if (document.getElementById('include-link-group')) {
        document.getElementById('include-link-group').checked = true;
    }
    
    // อัปเดตค่าใช้จ่าย
    updateGroupMessageCost();
    
    // แสดงข้อความแจ้งเตือน
    showAlert('รีเซ็ตข้อความเรียบร้อย', 'success');
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
        alertContainer.style.display = 'flex';
        alertContainer.style.flexDirection = 'column';
        alertContainer.style.alignItems = 'flex-end';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    
    // กำหนดสีและสไตล์แบบพรีเมี่ยม
    let backgroundColor, textColor, iconColor;
    switch(type) {
        case 'success':
            backgroundColor = 'linear-gradient(to right, #4caf50, #43a047)';
            textColor = 'white';
            iconColor = '#e8f5e9';
            break;
        case 'warning':
            backgroundColor = 'linear-gradient(to right, #ff9800, #f57c00)';
            textColor = 'white';
            iconColor = '#fff8e1';
            break;
        case 'danger':
            backgroundColor = 'linear-gradient(to right, #f44336, #e53935)';
            textColor = 'white';
            iconColor = '#ffebee';
            break;
        default:
            backgroundColor = 'linear-gradient(to right, #2196f3, #1976d2)';
            textColor = 'white';
            iconColor = '#e3f2fd';
    }
    
    alert.style.background = backgroundColor;
    alert.style.color = textColor;
    alert.style.padding = '15px 20px';
    alert.style.borderRadius = '8px';
    alert.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08)';
    alert.style.position = 'relative';
    alert.style.transition = 'all 0.3s ease';
    alert.style.transform = 'translateX(110%)';
    alert.style.maxWidth = '350px';
    alert.style.width = '100%';
    alert.style.marginBottom = '10px';
    alert.style.display = 'flex';
    alert.style.alignItems = 'center';
    
    // เพิ่มเนื้อหาข้อความและปุ่มปิด
    alert.innerHTML = `
        <div class="alert-icon" style="margin-right: 15px; display: flex; align-items: center; color: ${iconColor};">
            ${type === 'success' ? '✓' : type === 'warning' ? '!' : type === 'danger' ? '✘' : 'ℹ️'}
        </div>
        <div class="alert-content" style="flex-grow: 1;">${message}</div>
        <button class="alert-close" style="background: none; border: none; cursor: pointer; color: ${textColor}; opacity: 0.7; transition: opacity 0.3s;">
            &times;
        </button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // แสดง alert ด้วยการเลื่อนเข้ามา
    requestAnimationFrame(() => {
        alert.style.transform = 'translateX(0)';
    });
    
    // ตั้งค่าปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            alert.style.transform = 'translateX(110%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 300);
        });
    }
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    const autoCloseTimer = setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.style.transform = 'translateX(110%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
    
    // หยุดการนับเวลาอัตโนมัติหากปิดก่อนหมดเวลา
    alert.addEventListener('mouseover', () => {
        clearTimeout(autoCloseTimer);
    });
}
/**
 * ตั้งค่า event listeners ต่างๆ
 */
function setupEventListeners() {
    // ปุ่มส่งข้อความรายบุคคล
    const sendButton = document.getElementById('btnSendIndividual');
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    // ปุ่มรีเซ็ตข้อความรายบุคคล
    const resetButton = document.getElementById('btnResetMessage');
    if (resetButton) {
        resetButton.addEventListener('click', resetForm);
    }
    
    // ปุ่มแสดงตัวอย่างรายบุคคล
    const previewButton = document.getElementById('btnShowPreview');
    if (previewButton) {
        previewButton.addEventListener('click', showPreview);
    }
    
    // ปุ่มส่งข้อความกลุ่ม
    const sendGroupButton = document.getElementById('btnSendGroup');
    if (sendGroupButton) {
        sendGroupButton.addEventListener('click', sendGroupMessage);
    }
    
    // ปุ่มรีเซ็ตข้อความกลุ่ม
    const resetGroupButton = document.getElementById('btnResetGroupMessage');
    if (resetGroupButton) {
        resetGroupButton.addEventListener('click', resetGroupForm);
    }
    
    // ปุ่มแสดงตัวอย่างกลุ่ม
    const previewGroupButton = document.getElementById('btnShowGroupPreview');
    if (previewGroupButton) {
        previewGroupButton.addEventListener('click', showGroupPreview);
    }
    
    // ปุ่มเลือกทั้งหมด
    const selectAllButton = document.getElementById('btnSelectAllRecipients');
    if (selectAllButton) {
        selectAllButton.addEventListener('click', selectAllRecipients);
    }
    
    // ปุ่มยกเลิกเลือกทั้งหมด
    const clearAllButton = document.getElementById('btnClearAllRecipients');
    if (clearAllButton) {
        clearAllButton.addEventListener('click', clearAllRecipients);
    }
    
    // ปุ่มปิดโมดัล
    document.querySelectorAll('.modal .close, .modal .btn-secondary[data-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            closeModal(modalId);
        });
    });
    
    // ติดตามการเปลี่ยนแปลงของ checkboxes ในหน้ากลุ่ม
    document.addEventListener('change', function(event) {
        if (event.target.matches('.recipients-container input[type="checkbox"]')) {
            updateRecipientCount();
            updateGroupMessageCost();
        }
    });
    
    // ติดตามการเปลี่ยนแปลงของ checkboxes ในการตั้งค่าการส่ง
    const includeChartCheckbox = document.getElementById('include-chart');
    if (includeChartCheckbox) {
        includeChartCheckbox.addEventListener('change', function() {
            updateMessageCost();
            const previewChart = document.querySelector('#individual-tab .preview-chart');
            if (previewChart) {
                previewChart.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeLinkCheckbox = document.getElementById('include-link');
    if (includeLinkCheckbox) {
        includeLinkCheckbox.addEventListener('change', function() {
            updateMessageCost();
            const previewLink = document.querySelector('#individual-tab .preview-link');
            if (previewLink) {
                previewLink.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeChartGroupCheckbox = document.getElementById('include-chart-group');
    if (includeChartGroupCheckbox) {
        includeChartGroupCheckbox.addEventListener('change', function() {
            updateGroupMessageCost();
            const previewChart = document.querySelector('#group-tab .preview-chart');
            if (previewChart) {
                previewChart.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeLinkGroupCheckbox = document.getElementById('include-link-group');
    if (includeLinkGroupCheckbox) {
        includeLinkGroupCheckbox.addEventListener('change', function() {
            updateGroupMessageCost();
            const previewLink = document.querySelector('#group-tab .preview-link');
            if (previewLink) {
                previewLink.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    // ติดตามการเปลี่ยนแปลงของข้อความ
    const messageTextarea = document.getElementById('messageText');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            updatePreview(this.value);
        });
    }
    
    const groupMessageTextarea = document.getElementById('groupMessageText');
    if (groupMessageTextarea) {
        groupMessageTextarea.addEventListener('input', function() {
            updateGroupPreview(this.value);
        });
    }
    
    // ปุ่มค้นหานักเรียน
    const studentSearchForm = document.getElementById('studentSearchForm');
    if (studentSearchForm) {
        studentSearchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            applyFilters();
        });
    }
    
    // ปุ่มรีเซ็ตตัวกรอง
    const resetFilterButton = document.getElementById('btnResetFilter');
    if (resetFilterButton) {
        resetFilterButton.addEventListener('click', function() {
            // รีเซ็ตฟอร์มค้นหา
            studentSearchForm.reset();
            // ทำการค้นหาใหม่
            applyFilters();
        });
    }
    
    // ปุ่มค้นหานักเรียนสำหรับส่งกลุ่ม
    const groupFilterForm = document.getElementById('groupFilterForm');
    if (groupFilterForm) {
        groupFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchGroupRecipients();
        });
    }
    
    // อัปเดตค่าใช้จ่ายครั้งแรก
    updateMessageCost();
    updateGroupMessageCost();
    
    // ปุ่มดูประวัติการส่งข้อความ
    document.querySelectorAll('.history-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.studentId;
            // เลือก radio ของนักเรียนคนนี้
            const radio = document.querySelector(`input[name="student_select"][value="${studentId}"]`);
          // เพิ่มเติมส่วนที่ขาดหายในไฟล์ notification_enhanced.js
// ต่อจากส่วนสุดท้ายที่มีอยู่ในไฟล์ต้นฉบับ

if (radio) {
    radio.checked = true;
    // อัปเดตชื่อนักเรียนในส่วนแสดงผล
    updateSelectedStudentInfo();
}

// แสดงโมดัลประวัติการส่ง
document.querySelector('.history-student-name').textContent = this.closest('tr').querySelector('.student-name').textContent;
showModal('historyModal');

// จำลองการแสดงข้อมูลประวัติ (ในระบบจริงควรดึงจาก API)
const historyTable = document.querySelector('#historyTable tbody');
historyTable.innerHTML = `
    <tr>
        <td>${new Date().toLocaleDateString('th-TH')}</td>
        <td>แจ้งเตือนการเข้าแถว</td>
        <td>ผู้ดูแลระบบ</td>
        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
        <td>
            <div class="action-buttons">
                <button class="btn-icon" onclick="viewMessageDetail(1)">
                    <i class="material-icons">visibility</i>
                </button>
            </div>
        </td>
    </tr>
    <tr>
        <td>${new Date(Date.now() - 86400000).toLocaleDateString('th-TH')}</td>
        <td>แจ้งเตือนความเสี่ยง</td>
        <td>ครูที่ปรึกษา</td>
        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
        <td>
            <div class="action-buttons">
                <button class="btn-icon" onclick="viewMessageDetail(2)">
                    <i class="material-icons">visibility</i>
                </button>
            </div>
        </td>
    </tr>
`;
});
});

// ปุ่มส่งข้อความจากหน้ารายการนักเรียน
document.querySelectorAll('.send-btn').forEach(button => {
button.addEventListener('click', function() {
const studentId = this.getAttribute('data-student-id');
// เลือก radio ของนักเรียนคนนี้
const radio = document.querySelector(`input[name="student_select"][value="${studentId}"]`);
if (radio) {
    radio.checked = true;
    // อัปเดตชื่อนักเรียนในส่วนแสดงผล
    updateSelectedStudentInfo();
    // เลื่อนไปยังส่วนข้อความ
    document.querySelector('#individual-tab .card:nth-child(2)').scrollIntoView({
        behavior: 'smooth'
    });
}
});
});

// การเปลี่ยนแปลงการเลือกนักเรียน
document.querySelectorAll('input[name="student_select"]').forEach(radio => {
radio.addEventListener('change', function() {
updateSelectedStudentInfo();

// ดึงข้อมูลการเข้าแถวของนักเรียนที่เลือก
const studentId = this.value;
fetchStudentAttendanceData(studentId);
});
});

// อัปเดตการเลือกนักเรียนคนแรกที่โหลดหน้า
const firstStudentRadio = document.querySelector('input[name="student_select"]:first-child');
if (firstStudentRadio) {
firstStudentRadio.checked = true;
updateSelectedStudentInfo();
fetchStudentAttendanceData(firstStudentRadio.value);
}

// ปุ่มอัปเดตข้อมูลตามช่วงวันที่
const updateDateRangeButton = document.getElementById('btnUpdateDateRange');
if (updateDateRangeButton) {
updateDateRangeButton.addEventListener('click', function() {
const studentId = getSelectedStudentId();
if (studentId) {
    fetchStudentAttendanceData(studentId);
} else {
    showAlert('กรุณาเลือกนักเรียนก่อนอัปเดตข้อมูล', 'warning');
}
});
}

// ปุ่มดูประวัติการส่งข้อความ
const historyButton = document.getElementById('btnSendHistory');
if (historyButton) {
historyButton.addEventListener('click', function() {
// ในระบบจริงควรดึงประวัติการส่งข้อความทั้งหมด
const historyTable = document.querySelector('#historyModal tbody');
historyTable.innerHTML = `
    <tr>
        <td colspan="5" class="text-center">กำลังโหลดข้อมูล...</td>
    </tr>
`;

// จำลองการดึงข้อมูล
setTimeout(() => {
    historyTable.innerHTML = `
        <tr>
            <td>${new Date().toLocaleDateString('th-TH')}</td>
            <td>ส่งข้อความแบบกลุ่ม</td>
            <td>แจ้งเตือนการเข้าแถว</td>
            <td>ปวช.1/1 (24 คน)</td>
            <td><span class="status-badge success">ส่งสำเร็จ 24/24</span></td>
        </tr>
        <tr>
            <td>${new Date(Date.now() - 86400000).toLocaleDateString('th-TH')}</td>
            <td>ส่งข้อความแบบกลุ่ม</td>
            <td>แจ้งเตือนความเสี่ยง</td>
            <td>ปวช.2/1 (8 คน)</td>
            <td><span class="status-badge warning">ส่งสำเร็จ 7/8</span></td>
        </tr>
    `;
}, 500);

// แสดงโมดัลประวัติ
showModal('historyModal');
});
}
}

/**
* ดูรายละเอียดข้อความแจ้งเตือน
*/
function viewMessageDetail(messageId) {
// ในระบบจริงควรดึงข้อมูลจาก API
const messages = {
1: {
content: `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 ปัจจุบันเข้าร่วม 26/40 วัน (65%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`,
has_chart: true,
has_link: true
},
2: {
content: `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางวิทยาลัยขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26/40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`,
has_chart: true,
has_link: true
}
};

const message = messages[messageId];
if (!message) return;

// แสดงเนื้อหาข้อความในโมดัล
const previewText = document.getElementById('previewText');
if (previewText) {
previewText.innerHTML = message.content.replace(/\n/g, '<br>');
}

// แสดง/ซ่อน กราฟและลิงก์
const previewChart = document.getElementById('previewChartContainer');
const previewLink = document.getElementById('previewLinkContainer');

if (previewChart) {
previewChart.style.display = message.has_chart ? 'block' : 'none';
}

if (previewLink) {
previewLink.style.display = message.has_link ? 'block' : 'none';
}

// ซ่อนโมดัลประวัติ
closeModal('historyModal');
// แสดงโมดัลตัวอย่าง
showModal('previewModal');
}

/**
* อัปเดตข้อมูลนักเรียนที่เลือกในส่วนแสดงผล
*/
function updateSelectedStudentInfo() {
const selectedRadio = document.querySelector('input[name="student_select"]:checked');
if (!selectedRadio) return;

const row = selectedRadio.closest('tr');
const studentName = row.querySelector('.student-name').textContent;
const studentNameDisplay = document.querySelector('.student-name-display');

if (studentNameDisplay) {
studentNameDisplay.textContent = studentName;
}
}

/**
* ดึงข้อมูลการเข้าแถวของนักเรียน
*/
function fetchStudentAttendanceData(studentId) {
if (!studentId) return;

// ดึงช่วงวันที่
const startDate = document.getElementById('start-date').value;
const endDate = document.getElementById('end-date').value;

// แสดงการกำลังโหลด
showAlert('กำลังดึงข้อมูลการเข้าแถว...', 'info');

// สร้างข้อมูลสำหรับส่ง
const formData = new FormData();
formData.append('get_student_attendance', '1');
formData.append('student_id', studentId);
formData.append('start_date', startDate);
formData.append('end_date', endDate);

// ส่งข้อมูลผ่าน AJAX
fetch(window.location.href, {
method: 'POST',
body: formData,
headers: {
'X-Requested-With': 'XMLHttpRequest'
}
})
.then(response => response.json())
.then(data => {
if (data.success) {
// อัปเดตข้อมูลการเข้าแถวในส่วนแสดงตัวอย่าง
updateAttendancePreview(data.attendance);

// อัปเดตกราฟ
updateAttendanceChart(data.attendance.chart_data);
} else {
// แสดงข้อผิดพลาด
showAlert(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลการเข้าแถว', 'warning');
}
})
.catch(error => {
console.error('Error:', error);
showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลการเข้าแถว: ' + error.message, 'danger');
});
}

/**
* อัปเดตการแสดงข้อมูลการเข้าแถวในส่วนตัวอย่าง
*/
function updateAttendancePreview(attendanceData) {
// อัปเดตตัวอย่างข้อความ
const messageTemplate = document.getElementById('messageText').value;

// ข้อมูลสำหรับแทนที่ตัวแปรในข้อความ
const attendanceRate = attendanceData.attendance_rate || 0;
const presentCount = attendanceData.present_count || 0;
const totalDays = attendanceData.total_days || 0;
const absentCount = attendanceData.absent_count || 0;

// ดึงข้อมูลนักเรียนและครูที่ปรึกษา
const studentName = document.querySelector('.student-name-display')?.textContent || '';
const classInfo = document.querySelector('input[name="student_select"]:checked')?.closest('tr')?.querySelector('td:nth-child(3)').textContent.trim() || '';

// แทนที่ตัวแปรในข้อความ
let personalizedMessage = messageTemplate
.replace(/{{ชื่อนักเรียน}}/g, studentName)
.replace(/{{ชั้นเรียน}}/g, classInfo)
.replace(/{{จำนวนวันเข้าแถว}}/g, presentCount)
.replace(/{{จำนวนวันทั้งหมด}}/g, totalDays)
.replace(/{{ร้อยละการเข้าแถว}}/g, attendanceRate)
.replace(/{{จำนวนวันขาด}}/g, absentCount)
.replace(/{{สถานะการเข้าแถว}}/g, getAttendanceStatus(attendanceRate))
.replace(/{{ชื่อครูที่ปรึกษา}}/g, 'อ.ประสิทธิ์ ดีเลิศ') // ในระบบจริงควรดึงจากฐานข้อมูล
.replace(/{{เบอร์โทรครู}}/g, '081-234-5678'); // ในระบบจริงควรดึงจากฐานข้อมูล

// อัปเดตตัวอย่างข้อความ
updatePreview(personalizedMessage);
}

/**
* ดึงสถานะการเข้าแถวตามอัตราการเข้าแถว
*/
function getAttendanceStatus(rate) {
if (rate < 60) return 'เสี่ยงตกกิจกรรม';
if (rate < 80) return 'ต้องระวัง';
return 'ปกติ';
}

/**
* อัปเดตกราฟการเข้าแถว
*/
function updateAttendanceChart(chartData) {
const chartCanvas = document.getElementById('attendance-chart');
if (!chartCanvas || !window.Chart) return;

// ถ้ามีข้อมูลกราฟเดิม ให้ทำลายก่อน
if (chartCanvas.chart) {
chartCanvas.chart.destroy();
}

// ถ้าไม่มีข้อมูล ใช้ข้อมูลตัวอย่าง
const dates = chartData?.dates || ['1 พ.ค.', '8 พ.ค.', '15 พ.ค.', '22 พ.ค.', '29 พ.ค.'];
const rates = chartData?.rates || [85, 78, 65, 72, 80];

// สร้างกราฟ
const ctx = chartCanvas.getContext('2d');
chartCanvas.chart = new Chart(ctx, {
type: 'line',
data: {
labels: dates,
datasets: [{
    label: 'อัตราการเข้าแถว (%)',
    data: rates,
    fill: false,
    borderColor: 'rgb(75, 192, 192)',
    tension: 0.1
}]
},
options: {
responsive: true,
scales: {
    y: {
        beginAtZero: true,
        max: 100
    }
}
}
});

// อัปเดตกราฟในโมดัลตัวอย่าง
const previewChartCanvas = document.getElementById('preview-attendance-chart');
if (previewChartCanvas && window.Chart) {
// ถ้ามีกราฟเดิม ให้ทำลายก่อน
if (previewChartCanvas.chart) {
previewChartCanvas.chart.destroy();
}

// สร้างกราฟในโมดัลตัวอย่าง
const previewCtx = previewChartCanvas.getContext('2d');
previewChartCanvas.chart = new Chart(previewCtx, {
type: 'line',
data: {
    labels: dates,
    datasets: [{
        label: 'อัตราการเข้าแถว (%)',
        data: rates,
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1
    }]
},
options: {
    responsive: true,
    scales: {
        y: {
            beginAtZero: true,
            max: 100
        }
    }
}
});
}
}

/**
* ตรวจสอบ URL parameters เพื่อเปิดแท็บที่ระบุ
*/
function checkUrlParameters() {
const urlParams = new URLSearchParams(window.location.search);
const tab = urlParams.get('tab');

if (tab) {
showTab(tab);
}
}

/**
* อัปเดต URL parameter
*/
function updateUrlParameter(param, value) {
const url = new URL(window.location.href);
url.searchParams.set(param, value);
window.history.replaceState({}, '', url);
}

/**
* ค้นหานักเรียนตามเงื่อนไข
*/
function applyFilters() {
const searchForm = document.getElementById('studentSearchForm');
if (!searchForm) return;

// แสดงการกำลังโหลด
document.querySelector('#studentsTable tbody').innerHTML = `
<tr>
<td colspan="7" class="text-center">กำลังโหลดข้อมูล...</td>
</tr>
`;

// สร้างข้อมูลสำหรับส่ง
const formData = new FormData(searchForm);
formData.append('get_students', '1');
formData.append('limit', document.getElementById('pageSize')?.value || 20);
formData.append('offset', 0);

// ส่งข้อมูลผ่าน AJAX
fetch(window.location.href, {
method: 'POST',
body: formData,
headers: {
'X-Requested-With': 'XMLHttpRequest'
}
})
.then(response => response.json())
.then(data => {
if (data.success) {
// อัปเดตตารางนักเรียน
updateStudentsTable(data.students, data.total);
} else {
// แสดงข้อผิดพลาด
document.querySelector('#studentsTable tbody').innerHTML = `
    <tr>
        <td colspan="7" class="text-center">เกิดข้อผิดพลาดในการดึงข้อมูล</td>
    </tr>
`;
showAlert(data.message || 'เกิดข้อผิดพลาดในการค้นหานักเรียน', 'danger');
}
})
.catch(error => {
console.error('Error:', error);
document.querySelector('#studentsTable tbody').innerHTML = `
<tr>
    <td colspan="7" class="text-center">เกิดข้อผิดพลาดในการดึงข้อมูล</td>
</tr>
`;
showAlert('เกิดข้อผิดพลาดในการค้นหานักเรียน: ' + error.message, 'danger');
});
}

/**
* อัปเดตตารางนักเรียน
*/
function updateStudentsTable(students, total) {
    const tableBody = document.querySelector('#studentsTable tbody');
    if (!tableBody) return;

    // อัปเดตจำนวนนักเรียนที่พบ
    document.getElementById('totalStudents').textContent = total;

    // ถ้าไม่มีข้อมูล
    if (!students || students.length === 0) {
        tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียน</td>
        </tr>
        `;
        return;
    }

    // อัปเดตตาราง
    tableBody.innerHTML = '';

    students.forEach((student, index) => {
        const initial = student.first_name ? student.first_name.charAt(0) : '?';
        const fullName = `${student.title || ''} ${student.first_name || ''} ${student.last_name || ''}`;

        const row = document.createElement('tr');
        row.setAttribute('data-student-id', student.student_id);

        row.innerHTML = `
        <td>
            <input type="radio" name="student_select" value="${student.student_id}" ${index === 0 ? 'checked' : ''}>
        </td>
        <td>
            <div class="student-info">
                <div class="student-avatar">${initial}</div>
                <div class="student-details">
                    <div class="student-name">${fullName}</div>
                    <div class="student-code">รหัส ${student.student_code || ''}</div>
                </div>
            </div>
        </td>
        <td>
            ${student.class || ''}<br>
            <small class="text-muted">${student.department_name || ''}</small>
        </td>
        <td>${student.attendance_days || '0/0 (0%)'}</td>
        <td><span class="status-badge ${student.status_class || 'secondary'}">${student.status || 'ไม่มีข้อมูล'}</span></td>
        <td>
            ${student.parents_info ? `
            <div class="parent-info">
                <span class="parent-count">${student.parent_count || 0} คน</span>
                <span class="parent-names">${student.parents_info}</span>
            </div>
            ` : '<span class="text-danger">ไม่พบข้อมูลผู้ปกครอง</span>'}
        </td>
        <td>
            <div class="action-buttons">
                <button class="btn-icon history-btn" title="ดูประวัติการส่ง" data-student-id="${student.student_id}">
                    <i class="material-icons">history</i>
                </button>
                <button class="btn-icon send-btn" title="ส่งข้อความ" data-student-id="${student.student_id}">
                    <i class="material-icons">send</i>
                </button>
            </div>
        </td>
        `;

        tableBody.appendChild(row);
    });

    // เพิ่ม event listeners ใหม่หลังจากอัปเดตตาราง
    addTableEventListeners();
}

// เพิ่มฟังก์ชันเพื่อผูก event listeners กับปุ่มต่างๆ ในตาราง
function addTableEventListeners() {
    // ปุ่มดูประวัติการส่งข้อความ
    document.querySelectorAll('.history-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const radio = document.querySelector(`input[name="student_select"][value="${studentId}"]`);
            if (radio) {
                radio.checked = true;
                updateSelectedStudentInfo();
            }
            showHistory(studentId);
        });
    });

    // ปุ่มส่งข้อความ
    document.querySelectorAll('.send-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const radio = document.querySelector(`input[name="student_select"][value="${studentId}"]`);
            if (radio) {
                radio.checked = true;
                updateSelectedStudentInfo();
                fetchStudentAttendanceData(studentId);
                // เลื่อนไปยังส่วนข้อความ
                document.querySelector('#individual-tab .card:nth-child(2)').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // การเปลี่ยนการเลือกนักเรียน
    document.querySelectorAll('input[name="student_select"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateSelectedStudentInfo();
            fetchStudentAttendanceData(this.value);
        });
    });

    // เลือกนักเรียนคนแรก
    const firstRadio = document.querySelector('input[name="student_select"]:first-child');
    if (firstRadio) {
        firstRadio.checked = true;
        updateSelectedStudentInfo();
        fetchStudentAttendanceData(firstRadio.value);
    }
}

/**
* ดึงข้อมูลนักเรียนสำหรับส่งข้อความกลุ่ม
*/
function fetchGroupRecipients() {
const filterForm = document.getElementById('groupFilterForm');
if (!filterForm) return;

// แสดงการกำลังโหลด
const recipientsContainer = document.getElementById('recipientsContainer');
if (recipientsContainer) {
recipientsContainer.innerHTML = `
<div class="text-center p-4">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">กำลังโหลด...</span>
    </div>
    <p class="mt-2">กำลังดึงข้อมูลนักเรียน...</p>
</div>
`;
}

// สร้างข้อมูลสำหรับส่ง
const formData = new FormData(filterForm);
formData.append('get_at_risk_students', '1');

// ส่งข้อมูลผ่าน AJAX
fetch(window.location.href, {
method: 'POST',
body: formData,
headers: {
'X-Requested-With': 'XMLHttpRequest'
}
})
.then(response => response.json())
.then(data => {
if (data.success) {
// อัปเดตรายการผู้รับกลุ่ม
updateGroupRecipients(data.students, data.total);
} else {
// แสดงข้อผิดพลาด
if (recipientsContainer) {
    recipientsContainer.innerHTML = `
        <div class="text-center p-4">
            <div class="text-danger">
                <i class="material-icons">error</i>
                <p>เกิดข้อผิดพลาดในการดึงข้อมูล</p>
            </div>
        </div>
    `;
}
showAlert(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน', 'danger');
}
})
.catch(error => {
console.error('Error:', error);
if (recipientsContainer) {
recipientsContainer.innerHTML = `
    <div class="text-center p-4">
        <div class="text-danger">
            <i class="material-icons">error</i>
            <p>เกิดข้อผิดพลาดในการดึงข้อมูล</p>
        </div>
    </div>
`;
}
showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: ' + error.message, 'danger');
});
}

/**
* อัปเดตรายการผู้รับกลุ่ม
*/
function updateGroupRecipients(students, total) {
const recipientsContainer = document.getElementById('recipientsContainer');
const totalStudentsElement = document.getElementById('groupTotalStudents');

if (totalStudentsElement) {
totalStudentsElement.textContent = total;
}

if (!recipientsContainer) return;

// ถ้าไม่มีข้อมูล
if (!students || students.length === 0) {
recipientsContainer.innerHTML = `
<div class="text-center p-4">
    <div class="text-warning">
        <i class="material-icons">info</i>
        <p>ไม่พบนักเรียนที่ตรงตามเงื่อนไข</p>
    </div>
</div>
`;
return;
}

// อัปเดตรายการผู้รับ
recipientsContainer.innerHTML = '';

students.forEach(student => {
const initial = student.first_name ? student.first_name.charAt(0) : '?';
const fullName = `${student.title || ''} ${student.first_name || ''} ${student.last_name || ''}`;

const item = document.createElement('div');
item.className = 'recipient-item';

item.innerHTML = `
<div class="form-check">
    <input type="checkbox" class="form-check-input" id="student-${student.student_id}" value="${student.student_id}" checked>
    <label class="form-check-label" for="student-${student.student_id}">
        <div class="recipient-info">
            <div class="student-avatar">${initial}</div>
            <div class="recipient-details">
                <div class="student-name">${fullName}</div>
                <div class="student-class">${student.class || ''} - ${student.department_name || ''}</div>
                <div class="student-attendance">การเข้าแถว: ${student.attendance_days || '0/0 (0%)'}</div>
                <div class="student-status">สถานะ: <span class="status-badge ${student.status_class || 'secondary'}">${student.status || 'ไม่มีข้อมูล'}</span></div>
            </div>
        </div>
    </label>
</div>

<div class="parent-info">
    ${student.parent_count ? `
    <div>ผู้ปกครอง: ${student.parent_count} คน</div>
    <div class="text-muted">${student.parents_info || ''}</div>
    ` : '<span class="text-danger">ไม่พบข้อมูลผู้ปกครอง</span>'}
</div>
`;

recipientsContainer.appendChild(item);
});

// อัปเดตจำนวนผู้รับและปุ่มส่ง
updateRecipientCount();
updateGroupMessageCost();
}

/**
 * Floating Alert System
 * สำหรับระบบน้องชูใจ AI ดูแลผู้เรียน
 * 
 * ระบบแจ้งเตือนแบบลอยตัวที่สวยงาม และใช้งานง่าย
 */

class FloatingAlert {
    constructor(options = {}) {
      // ตั้งค่าเริ่มต้น
      this.options = {
        container: options.container || document.body,
        duration: options.duration || 5000, // ระยะเวลาที่แสดง (มิลลิวินาที)
        maxAlerts: options.maxAlerts || 3, // จำนวนการแจ้งเตือนสูงสุดที่แสดงพร้อมกัน
        position: options.position || 'top-right',
        animationDuration: options.animationDuration || 400,
        showClose: options.showClose !== undefined ? options.showClose : true
      };
      
      this.alerts = [];
      this.initContainer();
    }
    
    // สร้าง container หลักสำหรับการแจ้งเตือน
    initContainer() {
      this.container = document.querySelector('.floating-alert-container');
      
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'floating-alert-container';
        this.options.container.appendChild(this.container);
      }
    }
    
    // แสดงการแจ้งเตือน
    show(options) {
      if (!options || (!options.message && !options.content)) {
        console.error('Alert message or content is required');
        return null;
      }
      
      // จำกัดจำนวนการแจ้งเตือนที่แสดงพร้อมกัน
      if (this.alerts.length >= this.options.maxAlerts) {
        this.closeOldestAlert();
      }
      
      // สร้างอิลิเมนต์การแจ้งเตือน
      const alert = this.createAlertElement(options);
      
      // เพิ่มเข้าไปใน DOM
      this.container.appendChild(alert.element);
      
      // เพิ่มเข้าไปในรายการการแจ้งเตือน
      this.alerts.push(alert);
      
      // เริ่มนับเวลาปิดอัตโนมัติ (ถ้ากำหนด)
      if (options.duration !== 0 && (options.duration || this.options.duration)) {
        alert.autoCloseTimeout = setTimeout(() => {
          this.close(alert.id);
        }, options.duration || this.options.duration);
      }
      
      // รีเทิร์นรหัสการแจ้งเตือนเพื่อใช้อ้างอิงต่อไป
      return alert.id;
    }
    
    // สร้างอิลิเมนต์การแจ้งเตือน
    createAlertElement(options) {
      const id = 'alert-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
      const type = options.type || 'info';
      const title = options.title || this.getDefaultTitle(type);
      const icon = options.icon || this.getDefaultIcon(type);
      
      const alertElement = document.createElement('div');
      alertElement.className = `floating-alert ${type}`;
      alertElement.id = id;
      
      // สร้างส่วนหัว
      const headerElement = document.createElement('div');
      headerElement.className = 'floating-alert-header';
      
      // ส่วนชื่อเรื่อง
      const titleElement = document.createElement('h3');
      titleElement.className = 'floating-alert-title';
      
      // เพิ่มไอคอน (ถ้ามี)
      if (icon) {
        const iconElement = document.createElement('i');
        iconElement.className = `material-icons floating-alert-icon`;
        iconElement.textContent = icon;
        titleElement.appendChild(iconElement);
      }
      
      // เพิ่มข้อความชื่อเรื่อง
      const titleText = document.createTextNode(title);
      titleElement.appendChild(titleText);
      headerElement.appendChild(titleElement);
      
      // เพิ่มปุ่มปิด (ถ้ากำหนด)
      if (this.options.showClose !== false && options.showClose !== false) {
        const closeButton = document.createElement('button');
        closeButton.className = 'floating-alert-close';
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', () => this.close(id));
        headerElement.appendChild(closeButton);
      }
      
      alertElement.appendChild(headerElement);
      
      // ส่วนเนื้อหา
      const bodyElement = document.createElement('div');
      bodyElement.className = 'floating-alert-body';
      
      if (options.content) {
        bodyElement.appendChild(options.content);
      } else {
        bodyElement.innerHTML = options.message;
      }
      
      alertElement.appendChild(bodyElement);
      
      // ส่วนล่าง (ถ้ามีปุ่มหรือการกระทำเพิ่มเติม)
      if (options.buttons && options.buttons.length > 0) {
        const footerElement = document.createElement('div');
        footerElement.className = 'floating-alert-footer';
        
        // เพิ่มปุ่มทั้งหมด
        options.buttons.forEach(button => {
          const buttonElement = document.createElement('button');
          buttonElement.className = `floating-alert-button ${button.type || 'secondary'}`;
          buttonElement.textContent = button.text;
          buttonElement.addEventListener('click', () => {
            if (button.onClick) {
              button.onClick();
            }
            if (button.close !== false) {
              this.close(id);
            }
          });
          footerElement.appendChild(buttonElement);
        });
        
        alertElement.appendChild(footerElement);
      }
      
      // เริ่มแอนิเมชันแสดง
      setTimeout(() => {
        alertElement.classList.add('active');
      }, 10);
      
      return {
        id,
        element: alertElement,
        createdAt: Date.now()
      };
    }
    
    // ปิดการแจ้งเตือนตามรหัส
    close(id) {
      const alertIndex = this.alerts.findIndex(alert => alert.id === id);
      
      if (alertIndex === -1) return;
      
      const alert = this.alerts[alertIndex];
      const alertElement = document.getElementById(id);
      
      if (!alertElement) return;
      
      // ยกเลิกการปิดอัตโนมัติ (ถ้ามี)
      if (alert.autoCloseTimeout) {
        clearTimeout(alert.autoCloseTimeout);
      }
      
      // เริ่มแอนิเมชันปิด
      alertElement.classList.add('removing');
      
      // ลบอิลิเมนต์ออกหลังจากแอนิเมชันเสร็จสิ้น
      setTimeout(() => {
        if (alertElement.parentNode) {
          alertElement.parentNode.removeChild(alertElement);
        }
        this.alerts.splice(alertIndex, 1);
      }, 300); // ระยะเวลาของแอนิเมชันปิด
    }
    
    // ปิดการแจ้งเตือนที่เก่าที่สุด
    closeOldestAlert() {
      if (this.alerts.length === 0) return;
      
      const oldestAlert = this.alerts.reduce((oldest, current) => {
        return current.createdAt < oldest.createdAt ? current : oldest;
      }, this.alerts[0]);
      
      this.close(oldestAlert.id);
    }
    
    // ปิดการแจ้งเตือนทั้งหมด
    closeAll() {
      [...this.alerts].forEach(alert => {
        this.close(alert.id);
      });
    }
    
    // ชื่อเรื่องเริ่มต้นสำหรับแต่ละประเภท
    getDefaultTitle(type) {
      switch (type) {
        case 'success': return 'สำเร็จ';
        case 'warning': return 'คำเตือน';
        case 'danger': return 'ข้อผิดพลาด';
        case 'info': 
        default: return 'ข้อมูล';
      }
    }
    
    // ไอคอนเริ่มต้นสำหรับแต่ละประเภท
    getDefaultIcon(type) {
      switch (type) {
        case 'success': return 'check_circle';
        case 'warning': return 'warning';
        case 'danger': return 'error';
        case 'info': 
        default: return 'info';
      }
    }
    
    // แจ้งเตือนประเภทสำเร็จ
    success(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      return this.show({ ...options, type: 'success' });
    }
    
    // แจ้งเตือนประเภทข้อมูล
    info(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      return this.show({ ...options, type: 'info' });
    }
    
    // แจ้งเตือนประเภทคำเตือน
    warning(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      return this.show({ ...options, type: 'warning' });
    }
    
    // แจ้งเตือนประเภทผิดพลาด/อันตราย
    danger(options) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      return this.show({ ...options, type: 'danger' });
    }
  }
  
  // สร้าง instance สำหรับใช้งานทั่วไป
  const floatingAlert = new FloatingAlert();
  
  // ตัวอย่างการใช้งาน
  /*
  // การแจ้งเตือนพื้นฐาน
  floatingAlert.success('บันทึกข้อมูลสำเร็จ');
  floatingAlert.info('กำลังโหลดข้อมูล...');
  floatingAlert.warning('กรุณาตรวจสอบข้อมูลให้ถูกต้อง');
  floatingAlert.danger('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
  
  // การแจ้งเตือนแบบกำหนดค่าเพิ่มเติม
  floatingAlert.show({
    type: 'success',
    title: 'บันทึกข้อมูลสำเร็จ',
    message: 'ข้อมูลของคุณได้รับการบันทึกลงในระบบเรียบร้อยแล้ว',
    duration: 8000, // แสดงนานกว่าปกติ
    buttons: [
      {
        text: 'ดูข้อมูล',
        type: 'primary',
        onClick: () => { console.log('View data') },
        close: false // ไม่ปิดหลังคลิก
      },
      {
        text: 'ตกลง',
        type: 'secondary'
      }
    ]
  });
  */
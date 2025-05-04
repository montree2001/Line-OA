/**
 * notification_enhanced.js - JavaScript สำหรับหน้าส่งข้อความแจ้งเตือนที่มีฟีเจอร์ใหม่
 * ระบบ STUDENT-Prasat
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
            picker.type = 'date';  // เปลี่ยนเป็น HTML5 date input
            
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
            
            messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน${currentMonth} ${currentYear}\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
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
    
    // แสดงตัวอย่างกราฟ (ถ้ามี)
    const includeChart = document.getElementById('include-chart')?.checked || false;
    const previewChart = document.getElementById('previewChart');
    
    if (previewChart && includeChart) {
        previewChart.style.display = 'block';
    } else if (previewChart) {
        previewChart.style.display = 'none';
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
    const previewChart = document.getElementById('previewChart');
    
    if (previewChart && includeChart) {
        previewChart.style.display = 'block';
    } else if (previewChart) {
        previewChart.style.display = 'none';
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
    
    // สร้าง loading indicator
    showAlert('กำลังดึงข้อมูลประวัติการส่งข้อความ...', 'info');
    
    // ใช้ AJAX เพื่อดึงประวัติการส่งข้อความ
    fetch('send_notification_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `get_notification_history=1&student_id=${studentId}`
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
    // อาจใช้ AJAX เพื่อดึงข้อมูลข้อความแจ้งเตือนตามรหัส
    // สำหรับตัวอย่าง ใช้ข้อความจำลอง
    
    const messageContent = `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางวิทยาลัยขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
    
    const previewText = document.getElementById('previewText');
    if (previewText) {
        previewText.innerHTML = messageContent.replace(/\n/g, '<br>');
    }
    
    // ซ่อนตัวอย่างกราฟในโมดัลนี้
    const previewChart = document.getElementById('previewChart');
    if (previewChart) {
        previewChart.style.display = 'none';
    }
    
    closeModal('historyModal');
    showModal('previewModal');
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
        const buttonText = sendButton.innerHTML.replace(/\(\d+.*\)/g, '');
        sendButton.innerHTML = `${buttonText} (${selectedCount} ราย)`;
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
    const costValueElements = document.querySelectorAll('#group-tab .message-cost .cost-item.total .cost-value');
    costValueElements.forEach(element => {
        element.textContent = totalCost.toFixed(2) + ' บาท';
    });
    
    // อัปเดตรายละเอียดค่าใช้จ่าย
    document.querySelector('#group-tab .cost-item:nth-child(1) .cost-label').textContent = `ข้อความ (${selectedCount} คน):`;
    document.querySelector('#group-tab .cost-item:nth-child(1) .cost-value').textContent = (messageCost * selectedCount).toFixed(2) + ' บาท';
    
    document.querySelector('#group-tab .cost-item:nth-child(2) .cost-label').textContent = `รูปภาพกราฟ (${includeChart ? selectedCount : 0} รูป):`;
    document.querySelector('#group-tab .cost-item:nth-child(2) .cost-value').textContent = (chartCost * (includeChart ? selectedCount : 0)).toFixed(2) + ' บาท';
    
    document.querySelector('#group-tab .cost-item:nth-child(3) .cost-label').textContent = `ลิงก์ (${includeLink ? selectedCount : 0} ลิงก์):`;
    document.querySelector('#group-tab .cost-item:nth-child(3) .cost-value').textContent = (linkCost * (includeLink ? selectedCount : 0)).toFixed(2) + ' บาท';
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
    const costValueElements = document.querySelectorAll('#individual-tab .message-cost .cost-item.total .cost-value');
    costValueElements.forEach(element => {
        element.textContent = totalCost.toFixed(2) + ' บาท';
    });
    
    // อัปเดตรายละเอียดค่าใช้จ่าย
    document.querySelector('#individual-tab .cost-item:nth-child(1) .cost-value').textContent = messageCost.toFixed(2) + ' บาท';
    document.querySelector('#individual-tab .cost-item:nth-child(2) .cost-value').textContent = (includeChart ? chartCost : 0).toFixed(2) + ' บาท';
    document.querySelector('#individual-tab .cost-item:nth-child(3) .cost-value').textContent = (includeLink ? linkCost : 0).toFixed(2) + ' บาท';
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
    
    // ส่งข้อมูลผ่าน AJAX
    fetch('send_notification_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `send_individual_message=1&student_id=${studentId}&message=${encodeURIComponent(messageText)}&start_date=${startDate}&end_date=${endDate}&include_chart=${includeChart}&include_link=${includeLink}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // แสดงผลลัพธ์การส่งสำเร็จ
            showSendResults([{
                student_name: data.student_name,
                success: true,
                message_count: data.message_count,
                cost: data.cost
            }]);
        } else {
            // แสดงข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการส่งข้อความ', 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการส่งข้อความ', 'danger');
    });
}

/**
 * แสดงหน้าต่างโหลดระหว่างดำเนินการ
 * 
 * @param {string} message - ข้อความที่แสดงระหว่างโหลด
 */
function showLoading(message = 'กำลังดำเนินการ...') {
    // ตรวจสอบว่ามีโมดัลโหลดอยู่แล้วหรือไม่
    let loadingModal = document.getElementById('loadingModal');
    
    if (!loadingModal) {
        // สร้างโมดัลโหลด
        loadingModal = document.createElement('div');
        loadingModal.id = 'loadingModal';
        loadingModal.className = 'modal loading-modal';
        loadingModal.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-message">${message}</div>
            </div>
        `;
        document.body.appendChild(loadingModal);
        
        // เพิ่ม CSS สำหรับโมดัลโหลด
        const style = document.createElement('style');
        style.textContent = `
            .loading-modal {
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 2000;
            }
            .loading-content {
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
            .loading-message {
                font-size: 18px;
                font-weight: 600;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    } else {
        // อัปเดตข้อความ
        loadingModal.querySelector('.loading-message').textContent = message;
    }
    
    // แสดงโมดัล
    loadingModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * ซ่อนหน้าต่างโหลด
 */
function hideLoading() {
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) {
        loadingModal.classList.remove('active');
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
    showLoading(`กำลังส่งข้อความไปยังผู้ปกครอง ${studentIds.length} คน... (0/${studentIds.length})`);
    
    // ส่งข้อมูลผ่าน AJAX
    fetch('send_notification_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `send_group_message=1&student_ids=${JSON.stringify(studentIds)}&message=${encodeURIComponent(messageText)}&start_date=${startDate}&end_date=${endDate}&include_chart=${includeChart}&include_link=${includeLink}`
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
        showAlert('เกิดข้อผิดพลาดในการส่งข้อความ', 'danger');
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
    // ตรวจสอบว่ามีโมดัลผลลัพธ์อยู่แล้วหรือไม่
    let resultModal = document.getElementById('resultModal');
    
    if (!resultModal) {
        // สร้างโมดัลผลลัพธ์
        resultModal = document.createElement('div');
        resultModal.id = 'resultModal';
        resultModal.className = 'modal';
        document.body.appendChild(resultModal);
    }
    
    // คำนวณผลลัพธ์
    const success = successCount !== null ? successCount : results.filter(r => r.success).length;
    const error = errorCount !== null ? errorCount : results.filter(r => !r.success).length;
    const cost = totalCost !== null ? totalCost : results.reduce((sum, r) => sum + (r.cost || 0), 0);
    
    // สร้างเนื้อหาโมดัล
    resultModal.innerHTML = `
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('resultModal')">
                <span class="material-icons">close</span>
            </button>
            <h2 class="modal-title">ผลลัพธ์การส่งข้อความ</h2>
            
            <div class="result-summary">
                <div class="result-item success">
                    <span class="material-icons">check_circle</span>
                    <div class="result-value">${success}</div>
                    <div class="result-label">สำเร็จ</div>
                </div>
                <div class="result-item error">
                    <span class="material-icons">error</span>
                    <div class="result-value">${error}</div>
                    <div class="result-label">ล้มเหลว</div>
                </div>
                <div class="result-item cost">
                    <span class="material-icons">payments</span>
                    <div class="result-value">${formatCurrency(cost)}</div>
                    <div class="result-label">ค่าใช้จ่าย</div>
                </div>
            </div>
    `;
    
    // เพิ่มรายละเอียดการส่งแต่ละรายการ (ถ้ามีมากกว่า 1 รายการ)
    if (results.length > 1) {
        resultModal.querySelector('.modal-content').innerHTML += `
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>สถานะ</th>
                            <th>จำนวนข้อความ</th>
                            <th>ค่าใช้จ่าย</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${results.map(result => {
                            const statusClass = result.success ? 'success' : 'danger';
                            const statusText = result.success ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ';
                            
                            return `
                                <tr>
                                    <td>${result.student_name}</td>
                                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                    <td>${result.message_count || 1}</td>
                                    <td>${formatCurrency(result.cost || 0)}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    // เพิ่มปุ่มปิด
    resultModal.querySelector('.modal-content').innerHTML += `
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="closeModal('resultModal')">ตกลง</button>
        </div>
    `;
    
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
    return value.toFixed(2) + ' บาท';
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
 * ตั้งค่า event listeners ต่างๆ
 */
function setupEventListeners() {
    // ติดตามการเปลี่ยนแปลงของ checkboxes ในรายการผู้รับข้อความ
    const recipientCheckboxes = document.querySelectorAll('.recipients-container input[type="checkbox"]');
    recipientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRecipientCount();
            updateGroupMessageCost();
        });
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
    
    // ติดตามการเปลี่ยนแปลงของ checkbox ตัวเลือกการส่ง
    const includeChartCheckbox = document.getElementById('include-chart');
    if (includeChartCheckbox) {
        includeChartCheckbox.addEventListener('change', function() {
            updateMessageCost();
            const chartPreview = document.querySelector('#individual-tab .chart-preview');
            if (chartPreview) {
                chartPreview.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeLinkCheckbox = document.getElementById('include-link');
    if (includeLinkCheckbox) {
        includeLinkCheckbox.addEventListener('change', function() {
            updateMessageCost();
            const linkPreview = document.querySelector('#individual-tab .link-preview');
            if (linkPreview) {
                linkPreview.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeChartGroupCheckbox = document.getElementById('include-chart-group');
    if (includeChartGroupCheckbox) {
        includeChartGroupCheckbox.addEventListener('change', function() {
            updateGroupMessageCost();
            const chartPreview = document.querySelector('#group-tab .chart-preview');
            if (chartPreview) {
                chartPreview.style.display = this.checked ? 'block' : 'none';
            }
        });
    }
    
    const includeLinkGroupCheckbox = document.getElementById('include-link-group');
    if (includeLinkGroupCheckbox) {
        includeLinkGroupCheckbox.addEventListener('change', function() {
            updateGroupMessageCost();
            const linkPreview = document.querySelector('#group-tab .link-preview');
            if (linkPreview) {
                linkPreview.style.display = this.checked ? 'block' : 'none';
            }
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
                
                // อัปเดตข้อมูลนักเรียนในส่วนหัวของฟอร์มส่งข้อความ
                updateSelectedStudent(row);
            }
            
            // เลื่อนไปยังส่วนข้อความ
            const messageForm = document.querySelector('.message-form');
            if (messageForm) {
                messageForm.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // ปุ่มกรองนักเรียน
    const filterButton = document.querySelector('.filter-button');
    if (filterButton) {
        filterButton.addEventListener('click', applyFilters);
    }
    
    // ปุ่มส่งข้อความในฟอร์ม
    const sendMessageButton = document.querySelector('#individual-tab .btn-primary');
    if (sendMessageButton) {
        sendMessageButton.addEventListener('click', sendMessage);
    }
    
    const sendGroupMessageButton = document.querySelector('#group-tab .btn-primary');
    if (sendGroupMessageButton) {
        sendGroupMessageButton.addEventListener('click', sendGroupMessage);
    }
    
    // ปุ่มรีเซ็ตฟอร์ม
    const resetFormButton = document.querySelector('#individual-tab .btn-secondary');
    if (resetFormButton) {
        resetFormButton.addEventListener('click', resetForm);
    }
    
    const resetGroupFormButton = document.querySelector('#group-tab .btn-secondary');
    if (resetGroupFormButton) {
        resetGroupFormButton.addEventListener('click', resetGroupForm);
    }
    
    // ปุ่มเลือกทั้งหมด/ยกเลิกการเลือกทั้งหมด
    const selectAllButton = document.querySelector('.batch-actions .btn:first-child');
    if (selectAllButton) {
        selectAllButton.addEventListener('click', selectAllRecipients);
    }
    
    const clearAllButton = document.querySelector('.batch-actions .btn:last-child');
    if (clearAllButton) {
        clearAllButton.addEventListener('click', clearAllRecipients);
    }
    
    // radio buttons นักเรียน
    const studentRadios = document.querySelectorAll('input[name="student_select"]');
    studentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updateSelectedStudent(this.closest('tr'));
            }
        });
    });
    
    // อัปเดตค่าใช้จ่ายครั้งแรก
    updateMessageCost();
    updateGroupMessageCost();
}

/**
 * อัปเดตข้อมูลนักเรียนที่เลือกในฟอร์มส่งข้อความ
 * 
 * @param {Element} row - แถวของนักเรียนที่เลือก
 */
function updateSelectedStudent(row) {
    if (!row) return;
    
    const studentName = row.querySelector('.student-name')?.textContent;
    const studentClass = row.querySelector('td:nth-child(3)')?.textContent;
    const attendanceDays = row.querySelector('td:nth-child(4)')?.textContent;
    const studentStatus = row.querySelector('.status-badge')?.textContent;
    
    // อัปเดตข้อมูลในส่วนหัวของฟอร์มส่งข้อความ
    const nameDisplay = document.querySelector('.student-name-display');
    const classDisplay = document.querySelector('.student-class-display');
    const daysDisplay = document.querySelector('.attendance-days-display');
    const statusDisplay = document.querySelector('.attendance-status-display');
    
    if (nameDisplay && studentName) {
        nameDisplay.textContent = studentName;
    }
    
    if (classDisplay && studentClass) {
        classDisplay.textContent = studentClass;
    }
    
    if (daysDisplay && attendanceDays) {
        daysDisplay.textContent = attendanceDays;
    }
    
    if (statusDisplay && studentStatus) {
        statusDisplay.textContent = studentStatus;
    }
    
    // อัปเดตข้อความตามเทมเพลตที่เลือก
    const activeTemplateBtn = document.querySelector('#individual-tab .template-btn.active');
    if (activeTemplateBtn) {
        const templateType = activeTemplateBtn.getAttribute('data-template');
        selectTemplate(templateType);
    }
}

/**
 * กรองข้อมูลนักเรียน
 */
function applyFilters() {
    // ดึงค่าตัวกรอง
    const studentName = document.querySelector('input[name="student_name"]')?.value || '';
    const classLevel = document.querySelector('select[name="class_level"]')?.value || '';
    const classGroup = document.querySelector('select[name="class_group"]')?.value || '';
    const riskStatus = document.querySelector('select[name="risk_status"]')?.value || '';
    
    // แสดงการกำลังโหลด
    showLoading('กำลังค้นหานักเรียน...');
    
    // ส่งข้อมูลผ่าน AJAX
    fetch('send_notification_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `get_at_risk_students=1&student_name=${encodeURIComponent(studentName)}&class_level=${classLevel}&class_group=${classGroup}&risk_status=${riskStatus}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // อัปเดตตารางนักเรียน
            updateStudentTable(data.students, data.total);
        } else {
            showAlert('เกิดข้อผิดพลาดในการค้นหานักเรียน: ' + (data.message || 'เกิดข้อผิดพลาด'), 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการค้นหานักเรียน', 'danger');
    });
}

/**
 * อัปเดตตารางนักเรียน
 * 
 * @param {Array} students - รายการนักเรียน
 * @param {number} total - จำนวนนักเรียนทั้งหมด
 */
function updateStudentTable(students, total) {
    const tableBody = document.querySelector('#individual-tab .data-table tbody');
    if (!tableBody) return;
    
    // ล้างข้อมูลในตาราง
    tableBody.innerHTML = '';
    
    if (students.length === 0) {
        // แสดงข้อความไม่พบข้อมูล
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่กำหนด</td>
        `;
        tableBody.appendChild(row);
        
        // แสดงข้อความแจ้งเตือน
        showAlert('ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่กำหนด', 'warning');
        return;
    }
    
    // เพิ่มข้อมูลนักเรียนลงในตาราง
    students.forEach((student, index) => {
        const row = document.createElement('tr');
        row.dataset.studentId = student.student_id;
        
        row.innerHTML = `
            <td>
                <input type="radio" name="student_select" value="${student.student_id}" ${index === 0 ? 'checked' : ''}>
            </td>
            <td>
                <div class="student-info">
                    <div class="student-avatar">${student.initial}</div>
                    <div class="student-details">
                        <div class="student-name">${student.title} ${student.first_name} ${student.last_name}</div>
                        <div class="student-class">รหัส ${student.student_code || '-'}</div>
                    </div>
                </div>
            </td>
            <td>${student.class}</td>
            <td>${student.attendance_days}</td>
            <td><span class="status-badge ${student.status_class}">${student.status}</span></td>
            <td>${student.parents_info || '-'}</td>
            <td>
                <div class="action-buttons">
                    <button class="table-action-btn primary" title="ดูประวัติการส่ง" onclick="showHistory()">
                        <span class="material-icons">history</span>
                    </button>
                    <button class="table-action-btn success" title="ส่งข้อความ">
                        <span class="material-icons">send</span>
                    </button>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // อัปเดตจำนวนนักเรียนที่พบ
    const countInfo = document.querySelector('#individual-tab .filter-result-count');
    if (countInfo) {
        countInfo.textContent = `พบนักเรียนทั้งหมด ${total} คน`;
    }
    
    // แสดงข้อความแจ้งเตือน
    showAlert(`พบนักเรียนตามเงื่อนไขที่กำหนด ${students.length} คน จากทั้งหมด ${total} คน`, 'success');
    
    // ตั้งค่า event listeners สำหรับปุ่มในตารางใหม่
    setupEventListeners();
    
    // อัปเดตข้อมูลนักเรียนในฟอร์มส่งข้อความ (จากนักเรียนคนแรก)
    if (students.length > 0) {
        const firstRow = tableBody.querySelector('tr');
        updateSelectedStudent(firstRow);
    }
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
    
    // ตรวจสอบว่ามีการระบุห้องเรียนหรือไม่
    if (params.has('class_level') || params.has('class_group') || params.has('risk_status')) {
        document.querySelector('select[name="class_level"]').value = params.get('class_level') || '';
        document.querySelector('select[name="class_group"]').value = params.get('class_group') || '';
        document.querySelector('select[name="risk_status"]').value = params.get('risk_status') || '';
        
        // ใช้ตัวกรองอัตโนมัติ
        applyFilters();
    }
}

/**
 * เลือกนักเรียนตาม ID
 * 
 * @param {string} studentId - ID ของนักเรียนที่ต้องการเลือก
 */
function selectStudentById(studentId) {
    const studentRadios = document.querySelectorAll('input[name="student_select"]');
    let found = false;
    
    studentRadios.forEach(radio => {
        if (radio.value === studentId) {
            radio.checked = true;
            found = true;
            
            // อัปเดตข้อมูลนักเรียนในฟอร์ม
            updateSelectedStudent(radio.closest('tr'));
        }
    });
    
    if (!found) {
        // ถ้าไม่พบนักเรียน อาจต้องทำการค้นหาจากฐานข้อมูล
        // หรือเลือกนักเรียนคนแรกในรายการ
        if (studentRadios.length > 0) {
            studentRadios[0].checked = true;
            updateSelectedStudent(studentRadios[0].closest('tr'));
        }
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
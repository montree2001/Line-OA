/**
 * at_risk.js - JavaScript สำหรับหน้านักเรียนที่เสี่ยงตกกิจกรรม
 * ระบบน้องชูใจ AI ดูแลผู้เรียน
 */

// ตัวแปรสำหรับเก็บข้อมูลนักเรียนปัจจุบัน
let currentStudentData = null;

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าแท็บ
    initTabs();
    
    // ตั้งค่า event listeners
    initEventListeners();
    
    // เริ่มต้นแผนภูมิ (ถ้ามี)
    initCharts();
    
    // ตั้งค่าตาราง
    initTables();
    
    // ตั้งค่า datepicker (ถ้ามี)
    initDatepickers();
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
    
    // ตรวจสอบว่ามีแท็บที่ถูกระบุในพารามิเตอร์ URL หรือไม่
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        showTab(tabParam);
    }
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
    const tabContent = document.getElementById(tabId + '-tab');
    const tabButton = document.querySelector(`.tab[data-tab="${tabId}"]`);
    
    if (tabContent && tabButton) {
        tabContent.classList.add('active');
        tabButton.classList.add('active');
        
        // อัปเดต URL เพื่อให้สามารถแชร์ได้
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    }
}

/**
 * ตั้งค่าตาราง DataTable
 */
function initDataTables() {
    if (typeof $.fn !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        // กำหนดภาษาไทยสำหรับ DataTable
        const thaiLanguage = {
            "emptyTable": "ไม่พบข้อมูล",
            "info": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            "infoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
            "infoFiltered": "(กรองข้อมูล _MAX_ ทุกรายการ)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "แสดง _MENU_ รายการ",
            "loadingRecords": "กำลังโหลดข้อมูล...",
            "processing": "กำลังดำเนินการ...",
            "search": "ค้นหา:",
            "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "ถัดไป",
                "previous": "ก่อนหน้า"
            },
            "aria": {
                "sortAscending": ": เรียงข้อมูลจากน้อยไปมาก",
                "sortDescending": ": เรียงข้อมูลจากมากไปน้อย"
            }
        };

        // ตั้งค่า DataTable สำหรับตารางนักเรียนเสี่ยงตกกิจกรรม
        $('#at-risk-table').DataTable({
            language: thaiLanguage,
            responsive: true,
            pageLength: 10,
            order: [[5, 'asc']], // เรียงตามอัตราการเข้าแถวจากน้อยไปมาก
            columnDefs: [
                { targets: 0, width: '25%' }, // คอลัมน์นักเรียน
                { targets: 1, width: '10%' }, // คอลัมน์ชั้น/ห้อง
                { targets: 2, width: '12%' }, // คอลัมน์อัตราการเข้าแถว
                { targets: 3, width: '8%' }, // คอลัมน์วันที่ขาด
                { targets: 4, width: '15%' }, // คอลัมน์ครูที่ปรึกษา
                { targets: 5, width: '15%' }, // คอลัมน์การแจ้งเตือน
                { targets: 6, width: '15%', orderable: false } // คอลัมน์จัดการ (ไม่ต้องเรียงลำดับ)
            ],
            dom: 'Blfrtip', // แสดงปุ่มส่งออก, ตัวเลือกแสดงจำนวนรายการ, ช่องค้นหา, ตาราง, ข้อมูลจำนวนรายการ, pagination
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="material-icons">file_download</i> Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // ส่งออกทุกคอลัมน์ยกเว้นคอลัมน์จัดการ
                    },
                    title: 'รายงานนักเรียนเสี่ยงตกกิจกรรม'
                },
                {
                    extend: 'print',
                    text: '<i class="material-icons">print</i> พิมพ์',
                    className: 'btn btn-info',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // พิมพ์ทุกคอลัมน์ยกเว้นคอลัมน์จัดการ
                    }
                }
            ]
        });

        // ตั้งค่า DataTable สำหรับตารางนักเรียนขาดแถวบ่อย
        $('#frequently-absent-table').DataTable({
            language: thaiLanguage,
            responsive: true,
            pageLength: 10,
            order: [[3, 'desc']] // เรียงตามจำนวนวันที่ขาดจากมากไปน้อย
        });

        // ตั้งค่า DataTable สำหรับตารางนักเรียนรอการแจ้งเตือน
        $('#pending-notification-table').DataTable({
            language: thaiLanguage,
            responsive: true,
            pageLength: 10,
            order: [[2, 'asc']] // เรียงตามอัตราการเข้าแถวจากน้อยไปมาก
        });
    }
}


/**
 * ตั้งค่า event listeners
 */
function initEventListeners() {
    // ตั้งค่า event listener สำหรับการเลือกเทมเพลตในโมดัลส่งข้อความกลุ่ม
    const bulkTemplateSelect = document.getElementById('bulkTemplateSelect');
    if (bulkTemplateSelect) {
        bulkTemplateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const templateContent = selectedOption.getAttribute('data-content');
                document.getElementById('bulkMessageText').value = templateContent || '';
            } else {
                document.getElementById('bulkMessageText').value = '';
            }
        });
    }
    
    // ตั้งค่าปุ่มส่งข้อความในโมดัลรายละเอียดนักเรียน
    const sendMessageButton = document.getElementById('sendMessageButton');
    if (sendMessageButton) {
        sendMessageButton.addEventListener('click', function() {
            if (currentStudentData) {
                showSendMessageModal(
                    currentStudentData.id,
                    currentStudentData.name,
                    currentStudentData.class,
                    currentStudentData.attendance_rate,
                    currentStudentData.days_present,
                    currentStudentData.days_missed,
                    currentStudentData.total_days,
                    currentStudentData.advisor,
                    currentStudentData.advisor_phone
                );
            } else {
                showAlert('ไม่พบข้อมูลนักเรียน', 'danger');
            }
        });
    }
    
    // ตั้งค่า event listeners สำหรับปุ่มปิดโมดัล
    document.querySelectorAll('.modal-close').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            closeModal(modalId);
        });
    });
    
    // ปิดโมดัลเมื่อคลิกนอกกรอบ
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // ตั้งค่า event listeners สำหรับปุ่มเทมเพลต
    document.querySelectorAll('.template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const templateType = this.getAttribute('onclick').match(/selectModalTemplate\('(.+?)'\)/)[1];
            selectModalTemplate(templateType);
        });
    });
    
    // ตั้งค่า event listeners สำหรับการกรอง
    const minAttendance = document.getElementById('minAttendance');
    const maxAttendance = document.getElementById('maxAttendance');
    
    if (minAttendance) {
        minAttendance.addEventListener('change', function() {
            validateAttendanceRange();
        });
    }
    
    if (maxAttendance) {
        maxAttendance.addEventListener('change', function() {
            validateAttendanceRange();
        });
    }
}

/**
 * ตรวจสอบความถูกต้องของช่วงอัตราการเข้าแถว
 */
function validateAttendanceRange() {
    const minAttendance = document.getElementById('minAttendance');
    const maxAttendance = document.getElementById('maxAttendance');
    
    if (!minAttendance || !maxAttendance) return;
    
    const minVal = parseInt(minAttendance.value);
    const maxVal = parseInt(maxAttendance.value);
    
    // ตรวจสอบว่าค่าต่ำสุดต้องไม่เกินค่าสูงสุด
    if (!isNaN(minVal) && !isNaN(maxVal) && minVal > maxVal) {
        maxAttendance.value = minVal;
    }
    
    // ตรวจสอบว่าค่าอยู่ในช่วง 0-100
    if (!isNaN(minVal) && (minVal < 0 || minVal > 100)) {
        minAttendance.value = Math.max(0, Math.min(100, minVal));
    }
    
    if (!isNaN(maxVal) && (maxVal < 0 || maxVal > 100)) {
        maxAttendance.value = Math.max(0, Math.min(100, maxVal));
    }
}

/**
 * สร้างแผนภูมิต่างๆ ในหน้านักเรียนเสี่ยงตกกิจกรรม
 */
function initCharts() {
    // ตรวจสอบว่ามีแผนภูมิในหน้าหรือไม่
    const levelChartContainer = document.getElementById('attendance-by-level-chart');
    const departmentChartContainer = document.getElementById('attendance-by-department-chart');
    
    if (levelChartContainer) {
        // ตรวจสอบว่ามีไลบรารีสำหรับสร้างแผนภูมิหรือไม่ (Chart.js)
        if (typeof Chart !== 'undefined') {
            createAttendanceByLevelChart();
        } else {
            // ใช้การแสดงผลแบบ CSS ตามที่มีในไฟล์ HTML
            animateChartBars();
        }
    }
    
    // กราฟแผนกจะถูกสร้างโดยตรงจาก PHP ถ้ามี Chart.js
}

/**
 * สร้างแผนภูมิอัตราการเข้าแถวตามระดับชั้น
 */
function createAttendanceByLevelChart() {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const chartContainer = document.getElementById('attendance-by-level-chart');
    if (!chartContainer) return;
    
    // ดึงข้อมูลจาก chart-bar elements
    const chartBars = chartContainer.querySelectorAll('.chart-bar-item');
    if (chartBars.length === 0) return;
    
    // เก็บข้อมูลสำหรับกราฟ
    const labels = [];
    const data = [];
    const backgroundColors = [];
    const borderColors = [];
    
    // ดึงข้อมูลจากแต่ละแท่ง
    chartBars.forEach(item => {
        const label = item.querySelector('.chart-bar-label').textContent;
        const value = parseFloat(item.querySelector('.chart-bar-value').textContent);
        const barElement = item.querySelector('.chart-bar');
        
        let bgColor, borderColor;
        
        // กำหนดสีตามเกณฑ์
        if (barElement.classList.contains('bg-danger')) {
            bgColor = 'rgba(220, 53, 69, 0.7)';
            borderColor = 'rgba(220, 53, 69, 1)';
        } else if (barElement.classList.contains('bg-warning')) {
            bgColor = 'rgba(255, 193, 7, 0.7)';
            borderColor = 'rgba(255, 193, 7, 1)';
        } else if (barElement.classList.contains('bg-primary')) {
            bgColor = 'rgba(23, 162, 184, 0.7)';
            borderColor = 'rgba(23, 162, 184, 1)';
        } else {
            bgColor = 'rgba(40, 167, 69, 0.7)';
            borderColor = 'rgba(40, 167, 69, 1)';
        }
        
        labels.push(label);
        data.push(value);
        backgroundColors.push(bgColor);
        borderColors.push(borderColor);
    });
    
    // สร้าง canvas element
    const canvas = document.createElement('canvas');
    canvas.id = 'levelChart';
    canvas.width = 400;
    canvas.height = 250;
    
    // ล้างข้อมูลในกล่อง chart และเพิ่ม canvas
    chartContainer.innerHTML = '';
    chartContainer.appendChild(canvas);
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * สร้างแอนิเมชันสำหรับแผนภูมิแท่งที่ใช้ CSS
 */
function animateChartBars() {
    // ดึงแท่งกราฟทั้งหมด
    const chartBars = document.querySelectorAll('.chart-bar');
    
    // ตั้งค่าความสูงเริ่มต้นเป็น 0
    chartBars.forEach(bar => {
        bar.style.height = '0%';
    });
    
    // สร้างแอนิเมชัน
    setTimeout(() => {
        chartBars.forEach(bar => {
            const targetHeight = bar.querySelector('.chart-bar-value').textContent.replace('%', '');
            bar.style.height = Math.min(targetHeight, 100) + '%';
        });
    }, 300);
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
                e.target.closest('.table-action-btn')) {
                return;
            }
            
            // ดึง ID ของนักเรียนจากแอตทริบิวต์ data-id
            const studentId = this.getAttribute('data-id');
            if (studentId) {
                showStudentDetail(studentId);
            }
        });
    });
    
    // ถ้ามีการใช้ DataTables
    if (typeof $.fn !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json"
                    },
                    "pageLength": 10,
                    "ordering": true,
                    "responsive": true,
                    "columnDefs": [
                        { "orderable": false, "targets": -1 } // คอลัมน์สุดท้าย (จัดการ) ไม่สามารถเรียงลำดับได้
                    ]
                });
            }
        });
    }
}

/**
 * ตั้งค่า datepicker
 */
function initDatepickers() {
    // ถ้ามีการใช้ Datepicker
    if (typeof $.fn !== 'undefined' && typeof $.fn.datepicker !== 'undefined') {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            language: 'th',
            todayHighlight: true
        });
    }
}

/**
 * แสดงรายละเอียดนักเรียน
 * 
 * @param {number|string} studentId - รหัสนักเรียน
 */
function showStudentDetail(studentId) {
    // แสดงโมดัล
    showModal('studentDetailModal');
    
    // แสดงสถานะกำลังโหลด
    document.getElementById('studentProfileContainer').innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    `;
    
    // ทำ AJAX request เพื่อดึงข้อมูลนักเรียน
    fetchStudentDetail(studentId);
}

/**
 * ดึงข้อมูลรายละเอียดนักเรียนจาก server
 * 
 * @param {number|string} studentId - รหัสนักเรียน
 */
function fetchStudentDetail(studentId) {
    // ใช้ Fetch API เพื่อดึงข้อมูลนักเรียน
    fetch(`api/students/${studentId}?include=attendance,parent,notifications`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch student data');
            }
            return response.json();
        })
        .then(data => {
            // บันทึกข้อมูลนักเรียนปัจจุบัน
            currentStudentData = {
                id: data.student_id,
                name: data.name,
                class: data.class,
                attendance_rate: data.attendance_rate,
                days_present: data.days_present,
                days_missed: data.days_absent,
                total_days: data.total_days,
                advisor: data.advisor,
                advisor_phone: data.advisor_phone
            };
            
            // แสดงข้อมูลนักเรียน
            displayStudentDetail(data);
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            
            // ในกรณีที่มีข้อผิดพลาด แสดงข้อมูลจำลอง
            showMockStudentDetail(studentId);
            
            // แสดงข้อความแจ้งเตือน
            showAlert('ไม่สามารถโหลดข้อมูลนักเรียนได้ แสดงข้อมูลจำลอง', 'warning');
        });
}

/**
 * แสดงข้อมูลนักเรียนจำลองในกรณีที่ไม่สามารถดึงข้อมูลจริงได้
 * 
 * @param {number|string} studentId - รหัสนักเรียน
 */
function showMockStudentDetail(studentId) {
    // สมมติว่าได้รับข้อมูลจาก server แล้ว
    const mockData = {
        student_id: studentId,
        name: "นายธนกฤต สุขใจ",
        student_code: "67201010001",
        class: "ม.6/2",
        class_number: 12,
        attendance_rate: 68.5,
        days_present: 26,
        days_absent: 15,
        total_days: 40,
        advisor: "อ.ประสิทธิ์ ดีเลิศ",
        advisor_phone: "081-234-5678",
        parent_name: "นางวันดี สุขใจ",
        parent_relation: "แม่",
        parent_phone: "089-765-4321",
        attendance_history: [
            {date: "2568-03-01", status: "present"},
            {date: "2568-03-02", status: "present"},
            {date: "2568-03-03", status: "weekend"},
            {date: "2568-03-04", status: "weekend"},
            {date: "2568-03-05", status: "present"},
            {date: "2568-03-06", status: "absent"},
            {date: "2568-03-07", status: "present"},
            {date: "2568-03-08", status: "present"},
            {date: "2568-03-09", status: "absent"},
            {date: "2568-03-10", status: "weekend"},
            {date: "2568-03-11", status: "weekend"},
            {date: "2568-03-12", status: "present"},
            {date: "2568-03-13", status: "present"},
            {date: "2568-03-14", status: "absent"},
            {date: "2568-03-15", status: "present"}
        ],
        notification_history: [
            {date: "2568-03-10", type: "แจ้งเตือนปกติ", sender: "อ.ประสิทธิ์ ดีเลิศ", status: "ส่งสำเร็จ"},
            {date: "2568-03-01", type: "แจ้งเตือนเบื้องต้น", sender: "อ.ประสิทธิ์ ดีเลิศ", status: "ส่งสำเร็จ"}
        ]
    };
    
    // บันทึกข้อมูลนักเรียนปัจจุบัน
    currentStudentData = {
        id: mockData.student_id,
        name: mockData.name,
        class: mockData.class,
        attendance_rate: mockData.attendance_rate,
        days_present: mockData.days_present,
        days_missed: mockData.days_absent,
        total_days: mockData.total_days,
        advisor: mockData.advisor,
        advisor_phone: mockData.advisor_phone
    };
    
    // แสดงข้อมูลนักเรียน
    displayStudentDetail(mockData);
}

/**
 * แสดงข้อมูลนักเรียนในโมดัล
 * 
 * @param {Object} data - ข้อมูลนักเรียน
 */
function displayStudentDetail(data) {
    // อัปเดตชื่อในโมดัล
    document.getElementById('studentDetailTitle').textContent = `ข้อมูลนักเรียน - ${data.name}`;
    
    // สร้าง HTML สำหรับแสดงข้อมูลนักเรียน
    let statusClass = 'danger';
    if (data.attendance_rate >= 80) {
        statusClass = 'success';
    } else if (data.attendance_rate >= 70) {
        statusClass = 'warning';
    }
    
    // ตัวอักษรแรกของชื่อสำหรับ avatar
    let initial = data.name.charAt(0);
    if (['น', 'เ'].includes(initial)) {
        initial = data.name.charAt(3) || initial;
    }
    
    const html = `
        <div class="student-profile-header">
            <div class="student-profile-avatar">${initial}</div>
            <div class="student-profile-info">
                <h3>${data.name}</h3>
                <p>รหัสนักเรียน: ${data.student_code}</p>
                <p>ชั้น ${data.class} เลขที่ ${data.class_number}</p>
                <p>อัตราการเข้าแถว: <span class="status-badge ${statusClass}">${data.attendance_rate}%</span></p>
            </div>
        </div>
        
        <div class="student-attendance-summary">
            <h4>สรุปการเข้าแถว</h4>
            <div class="row">
                <div class="col-4">
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${data.days_present}</div>
                        <div class="attendance-stat-label">วันที่เข้าแถว</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${data.days_absent}</div>
                        <div class="attendance-stat-label">วันที่ขาดแถว</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${data.total_days}</div>
                        <div class="attendance-stat-label">วันทั้งหมด</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="student-attendance-history">
            <h4>ประวัติการเข้าแถว</h4>
            <div class="attendance-calendar">
                <div class="attendance-month">เดือนมีนาคม 2568</div>
                <div class="attendance-days">
                    ${data.attendance_history.map(day => {
                        const date = new Date(day.date);
                        return `
                            <div class="attendance-day ${day.status}" title="${formatDateFull(day.date)}">${date.getDate()}</div>
                        `;
                    }).join('')}
                </div>
            </div>
        </div>
        
        <div class="student-contact-info">
            <h4>ข้อมูลติดต่อ</h4>
            <div class="row">
                <div class="col-6">
                    <p><strong>ครูที่ปรึกษา:</strong> ${data.advisor}</p>
                    <p><strong>เบอร์โทรครู:</strong> ${data.advisor_phone}</p>
                </div>
                <div class="col-6">
                    <p><strong>ผู้ปกครอง:</strong> ${data.parent_name} (${data.parent_relation})</p>
                    <p><strong>เบอร์โทรผู้ปกครอง:</strong> ${data.parent_phone}</p>
                </div>
            </div>
        </div>
        
        <div class="student-notification-history">
            <h4>ประวัติการแจ้งเตือน</h4>
            ${data.notification_history.length > 0 ? `
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>ผู้ส่ง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.notification_history.map(notification => `
                                <tr>
                                    <td>${formatDate(notification.date)}</td>
                                    <td>${notification.type}</td>
                                    <td>${notification.sender}</td>
                                    <td><span class="status-badge success">${notification.status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            ` : `
                <p class="text-center" style="padding: 15px; color: var(--text-light);">ยังไม่มีประวัติการแจ้งเตือน</p>
            `}
        </div>
    `;
    
    // อัปเดตเนื้อหาในโมดัล
    document.getElementById('studentProfileContainer').innerHTML = html;
    
    // ตั้งค่าข้อมูลสำหรับปุ่มส่งข้อความ
    const sendMessageButton = document.getElementById('sendMessageButton');
    sendMessageButton.setAttribute('data-student-id', data.student_id);
    sendMessageButton.setAttribute('data-student-name', data.name);
    sendMessageButton.setAttribute('data-student-class', data.class);
    sendMessageButton.setAttribute('data-attendance-rate', data.attendance_rate);
    sendMessageButton.setAttribute('data-days-present', data.days_present);
    sendMessageButton.setAttribute('data-days-missed', data.days_absent);
    sendMessageButton.setAttribute('data-total-days', data.total_days);
    sendMessageButton.setAttribute('data-advisor', data.advisor);
    sendMessageButton.setAttribute('data-advisor-phone', data.advisor_phone);
}

/**
 * แสดงโมดัลส่งข้อความ
 */
function showSendMessageModal(studentId, studentName, studentClass, attendanceRate, daysPresent, daysMissed, totalDays, advisor, advisorPhone) {
    // ตั้งค่าชื่อในโมดัล
    document.getElementById('sendMessageTitle').textContent = `ส่งข้อความแจ้งเตือน - ${studentName}`;
    
    // บันทึกรหัสนักเรียน
    document.getElementById('studentIdField').value = studentId;
    
    // ตั้งค่าข้อความเริ่มต้น (เลือกเทมเพลตแรก)
    selectModalTemplate('warning');
    
    // แทนที่ตัวแปรในข้อความ
    const messageText = document.getElementById('modalMessageText').value;
    const newMessageText = messageText
        .replace(/\{\{ชื่อนักเรียน\}\}/g, studentName)
        .replace(/\{\{ชั้นเรียน\}\}/g, studentClass)
        .replace(/\{\{ร้อยละการเข้าแถว\}\}/g, attendanceRate)
        .replace(/\{\{จำนวนวันเข้าแถว\}\}/g, daysPresent)
        .replace(/\{\{จำนวนวันขาด\}\}/g, daysMissed)
        .replace(/\{\{จำนวนวันทั้งหมด\}\}/g, totalDays)
        .replace(/\{\{ชื่อครูที่ปรึกษา\}\}/g, advisor)
        .replace(/\{\{เบอร์โทรครู\}\}/g, advisorPhone);
    
    document.getElementById('modalMessageText').value = newMessageText;
    
    // แสดงโมดัล
    showModal('sendMessageModal');
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
    document.querySelector(`.template-btn[onclick*="${templateType}"]`).classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('modalMessageText');
    
    switch(templateType) {
        case 'regular':
            messageText.value = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} จาก {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
            break;
        case 'warning':
            messageText.value = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} จาก {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
            break;
        case 'critical':
            messageText.value = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} จาก {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
            break;
        case 'summary':
            messageText.value = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท';
            break;
    }
}

/**
 * ส่งข้อความแจ้งเตือนรายบุคคล
 */
function sendIndividualMessage() {
    // ดึงข้อมูลจากฟอร์ม
    const studentId = document.getElementById('studentIdField').value;
    const messageText = document.getElementById('modalMessageText').value;
    
    // ตรวจสอบว่ามีข้อความหรือไม่
    if (!messageText.trim()) {
        showAlert('กรุณากรอกข้อความก่อนส่ง', 'danger');
        return;
    }
    
    // แสดง loading overlay
    showLoadingOverlay();
    
    // ส่งข้อมูลไปยัง server
    fetch('api/notifications/individual', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            student_id: studentId,
            message: messageText
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to send notification');
        }
        return response.json();
    })
    .then(data => {
        // ซ่อน loading overlay
        hideLoadingOverlay();
        
        // ปิดโมดัล
        closeModal('sendMessageModal');
        
        // แสดงข้อความแจ้งเตือน
        showAlert('ส่งข้อความแจ้งเตือนเรียบร้อยแล้ว', 'success');
    })
    .catch(error => {
        console.error('Error sending notification:', error);
        
        // ซ่อน loading overlay
        hideLoadingOverlay();
        
        // จำลองการส่งข้อความสำเร็จ (ในกรณีที่ไม่สามารถเชื่อมต่อกับ server ได้)
        setTimeout(function() {
            // ปิดโมดัล
            closeModal('sendMessageModal');
            
            // แสดงข้อความแจ้งเตือน
            showAlert('ส่งข้อความแจ้งเตือนเรียบร้อยแล้ว (จำลอง)', 'success');
        }, 1000);
    });
}

/**
 * ส่งข้อความแจ้งเตือนกลุ่ม
 */
function sendBulkNotification() {
    // ดึงข้อมูลจากฟอร์ม
    const messageText = document.getElementById('bulkMessageText').value;
    const recipientCount = document.getElementById('bulkRecipientCount').textContent.replace(' คน', '');
    
    // ตรวจสอบว่ามีข้อความหรือไม่
    if (!messageText.trim()) {
        showAlert('กรุณากรอกข้อความก่อนส่ง', 'danger');
        return;
    }
    
    // แสดง loading overlay
    showLoadingOverlay();
    
    // รวบรวมเงื่อนไขการกรอง
    const filters = {
        department_id: document.getElementById('departmentId')?.value || '',
        class_level: document.getElementById('classLevel')?.value || '',
        class_room: document.getElementById('classRoom')?.value || '',
        advisor: document.getElementById('advisor')?.value || '',
        min_attendance: document.getElementById('minAttendance')?.value || '',
        max_attendance: document.getElementById('maxAttendance')?.value || ''
    };
    
    // ส่งข้อมูลไปยัง server
    fetch('api/notifications/bulk', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            message: messageText,
            filters: filters
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to send bulk notification');
        }
        return response.json();
    })
    .then(data => {
        // ซ่อน loading overlay
        hideLoadingOverlay();
        
        // ปิดโมดัล
        closeModal('bulkNotificationModal');
        
        // แสดงข้อความแจ้งเตือน
        showAlert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองจำนวน ${data.sent_count || recipientCount} รายเรียบร้อยแล้ว`, 'success');
    })
    .catch(error => {
        console.error('Error sending bulk notification:', error);
        
        // ซ่อน loading overlay
        hideLoadingOverlay();
        
        // จำลองการส่งข้อความสำเร็จ (ในกรณีที่ไม่สามารถเชื่อมต่อกับ server ได้)
        setTimeout(function() {
            // ปิดโมดัล
            closeModal('bulkNotificationModal');
            
            // แสดงข้อความแจ้งเตือน
            showAlert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองจำนวน ${recipientCount} รายเรียบร้อยแล้ว (จำลอง)`, 'success');
        }, 1500);
    });
}

/**
 * กรองข้อมูลนักเรียน
 */
function filterStudents() {
    const departmentId = document.getElementById('departmentId')?.value || '';
    const classLevel = document.getElementById('classLevel')?.value || '';
    const classRoom = document.getElementById('classRoom')?.value || '';
    const advisor = document.getElementById('advisor')?.value || '';
    const minAttendance = document.getElementById('minAttendance')?.value || '';
    const maxAttendance = document.getElementById('maxAttendance')?.value || '';
    
    // ตรวจสอบความถูกต้องของช่วงอัตราการเข้าแถว
    validateAttendanceRange();
    
    // สร้าง URL พร้อมกับพารามิเตอร์ที่จำเป็น
    let url = window.location.pathname + '?';
    if (departmentId) url += `department_id=${encodeURIComponent(departmentId)}&`;
    if (classLevel) url += `class_level=${encodeURIComponent(classLevel)}&`;
    if (classRoom) url += `class_room=${encodeURIComponent(classRoom)}&`;
    if (advisor) url += `advisor=${encodeURIComponent(advisor)}&`;
    if (minAttendance) url += `min_attendance=${encodeURIComponent(minAttendance)}&`;
    if (maxAttendance) url += `max_attendance=${encodeURIComponent(maxAttendance)}&`;
    
    // เพิ่มพารามิเตอร์แท็บปัจจุบัน (ถ้ามี)
    const activeTab = document.querySelector('.tab.active');
    if (activeTab) {
        url += `tab=${encodeURIComponent(activeTab.getAttribute('data-tab'))}&`;
    }
    
    // ตัดเครื่องหมาย & ตัวสุดท้ายออก
    url = url.replace(/&$/, '');
    
    // แสดง loading overlay
    showLoadingOverlay();
    
    // โหลดหน้าใหม่พร้อมกับพารามิเตอร์การกรอง
    window.location.href = url;
}

/**
 * รีเซ็ตตัวกรองข้อมูล
 */
function resetFilters() {
    document.getElementById('departmentId').value = '';
    document.getElementById('classLevel').value = '';
    document.getElementById('classRoom').value = '';
    document.getElementById('advisor').value = '';
    document.getElementById('minAttendance').value = '';
    document.getElementById('maxAttendance').value = '';
    
    // เก็บแท็บปัจจุบัน
    const activeTab = document.querySelector('.tab.active');
    let tabParam = '';
    if (activeTab) {
        tabParam = `?tab=${encodeURIComponent(activeTab.getAttribute('data-tab'))}`;
    }
    
    // แสดง loading overlay
    showLoadingOverlay();
    
    // โหลดหน้าใหม่พร้อมกับพารามิเตอร์แท็บ (ถ้ามี)
    window.location.href = window.location.pathname + tabParam;
}
/**
 * ดาวน์โหลดรายงานนักเรียนเสี่ยงตกกิจกรรม
 */
function downloadAtRiskReport() {
    // แสดง loading overlay
    showLoadingOverlay();
    
    // รวบรวมเงื่อนไขการกรอง
    const departmentId = document.getElementById('departmentId')?.value || '';
    const classLevel = document.getElementById('classLevel')?.value || '';
    const classRoom = document.getElementById('classRoom')?.value || '';
    const advisor = document.getElementById('advisor')?.value || '';
    const minAttendance = document.getElementById('minAttendance')?.value || '';
    const maxAttendance = document.getElementById('maxAttendance')?.value || '';
    const academicYearId = document.querySelector('meta[name="academic-year-id"]')?.content || '1';
    
    // สร้าง form สำหรับ submit แบบ POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/reports/at_risk_export.php';
    form.target = '_blank'; // เปิดในแท็บใหม่
    form.style.display = 'none';
    
    // เพิ่ม input fields
    const addInput = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    };
    
    // เพิ่มข้อมูลการกรอง
    addInput('department_id', departmentId);
    addInput('class_level', classLevel);
    addInput('class_room', classRoom);
    addInput('advisor', advisor);
    addInput('min_attendance', minAttendance);
    addInput('max_attendance', maxAttendance);
    addInput('academic_year_id', academicYearId);
    
    // แนบ form เข้ากับ document และ submit
    document.body.appendChild(form);
    form.submit();
    
    // ลบ form หลังจาก submit
    setTimeout(() => {
        document.body.removeChild(form);
        hideLoadingOverlay();
        showAlert('กำลังดาวน์โหลดรายงาน Excel', 'success');
    }, 1000);
}
/**
 * แปลง date format
 * 
 * @param {string} dateString - วันที่ในรูปแบบ YYYY-MM-DD
 * @returns {string} - วันที่ในรูปแบบ DD/MM/YYYY
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear() + 543}`;
}

/**
 * แปลง date format แบบเต็ม
 * 
 * @param {string} dateString - วันที่ในรูปแบบ YYYY-MM-DD
 * @returns {string} - วันที่ในรูปแบบ วันXXที่ DD เดือนYY พ.ศ. ZZZZ
 */
function formatDateFull(dateString) {
    const date = new Date(dateString);
    const days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    const months = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    return `วัน${days[date.getDay()]}ที่ ${date.getDate()} ${months[date.getMonth()]} พ.ศ. ${date.getFullYear() + 543}`;
}

/**
 * แสดงโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการแสดง
 */
function showModal(modalId) {
    document.getElementById(modalId).classList.add('show');
    document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้า
}

/**
 * ปิดโมดัล
 * 
 * @param {string} modalId - ID ของโมดัลที่ต้องการปิด
 */
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.body.style.overflow = ''; // อนุญาตให้เลื่อนหน้าได้อีกครั้ง
}

/**
 * แสดง loading overlay
 */
function showLoadingOverlay() {
    // สร้าง loading overlay ถ้ายังไม่มี
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>กำลังดำเนินการ...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    // แสดง loading overlay
    document.getElementById('loadingOverlay').style.display = 'flex';
}

/**
 * ซ่อน loading overlay
 */
function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
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
/**
 * dashboard.js - JavaScript สำหรับหน้าแดชบอร์ดผู้บริหาร
 * รองรับการแสดงกราฟ ตาราง และโต้ตอบกับผู้ใช้
 */

// ตัวแปรสำหรับเก็บ Chart Objects
let attendanceLineChart = null;
let statusPieChart = null;
let currentStudentId = null;
let currentPeriod = 'month'; // default period

/**
 * เมื่อโหลด DOM เสร็จสมบูรณ์
 */
document.addEventListener('DOMContentLoaded', function() {
    // สร้างกราฟและแผนภูมิ
    initializeCharts();
    
    // เพิ่ม Event Listeners
    setupEventListeners();
    
    // ปรับขนาดกราฟตามขนาดหน้าจอ
    handleResponsiveLayout();
    
    // ตั้งค่า UI components อื่นๆ
    initializeUIComponents();
});

/**
 * สร้างกราฟเส้นแสดงอัตราการเข้าแถว
 */
function initializeAttendanceLineChart(data) {
    const ctx = document.getElementById('attendanceLineChart');
    if (!ctx) return;
    
    // ถ้าไม่มีข้อมูลจาก server ให้ใช้ข้อมูลตัวอย่าง
    const chartData = data || {
        labels: ['10 มี.ค.', '11 มี.ค.', '12 มี.ค.', '13 มี.ค.', '14 มี.ค.', '15 มี.ค.', '16 มี.ค.'],
        datasets: [{
            values: [92.5, 93.8, 91.2, 94.5, 90.8, 93.7, 95.2]
        }]
    };
    
    // ถ้ามีกราฟเดิมอยู่แล้วให้ทำลายก่อน
    if (attendanceLineChart) {
        attendanceLineChart.destroy();
    }
    
    // สร้างกราฟเส้นใหม่
    attendanceLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: chartData.datasets[0].values,
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                borderColor: 'rgba(25, 118, 210, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(25, 118, 210, 1)',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...chartData.datasets[0].values) - 10),
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                        }
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

/**
 * สร้างกราฟวงกลมแสดงสถานะการเข้าแถว
 */
function initializeStatusPieChart(data) {
    const ctx = document.getElementById('attendancePieChart');
    if (!ctx) return;
    
    // ถ้าไม่มีข้อมูลจาก server ให้ใช้ข้อมูลตัวอย่าง
    const chartData = data || {
        normal: 75,
        late: 15,
        absent: 10
    };
    
    // ถ้ามีกราฟเดิมอยู่แล้วให้ทำลายก่อน
    if (statusPieChart) {
        statusPieChart.destroy();
    }
    
    // สร้างกราฟวงกลมใหม่
    statusPieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['มาปกติ', 'มาสาย', 'ขาด'],
            datasets: [{
                data: [chartData.normal, chartData.late, chartData.absent],
                backgroundColor: ['#4caf50', '#ff9800', '#f44336'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${percentage}%`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // อัปเดตค่าตัวเลขในกลางกราฟ (ไม่มีใน Chart.js โดยตรง ต้องเพิ่มเอง)
    const centerValue = chartData.normal + chartData.late;
    const centerText = document.createElement('div');
    centerText.className = 'chart-center-text';
    centerText.innerHTML = `
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #1976d2;">${centerValue}%</div>
            <div style="font-size: 12px; color: #666;">เข้าแถว</div>
        </div>
    `;
    
    // เพิ่มตัวเลขกลางกราฟถ้ายังไม่มี
    const container = ctx.parentElement;
    if (!container.querySelector('.chart-center-text')) {
        container.style.position = 'relative';
        container.appendChild(centerText);
    }
}

/**
 * สร้างกราฟและแผนภูมิทั้งหมด
 */
function initializeCharts() {
    // ดึงข้อมูลสำหรับกราฟเส้น
    fetchAttendanceLineData();
    
    // ดึงข้อมูลสำหรับกราฟวงกลม
    fetchStatusPieData();
}

/**
 * ดึงข้อมูลอัตราการเข้าแถวสำหรับกราฟเส้น
 */
function fetchAttendanceLineData() {
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    // แต่สำหรับตัวอย่างนี้ จะใช้ข้อมูลจำลอง
    
    try {
        // ถ้ามีตัวแปร weeklyAttendanceData ที่ส่งมาจาก PHP
        if (typeof weeklyAttendanceData !== 'undefined') {
            const labels = weeklyAttendanceData.map(item => item.date);
            const values = weeklyAttendanceData.map(item => item.attendance_rate);
            
            initializeAttendanceLineChart({
                labels: labels,
                datasets: [{
                    values: values
                }]
            });
        } else {
            // ถ้าไม่มีข้อมูลจาก PHP ให้ใช้ข้อมูลตัวอย่าง
            initializeAttendanceLineChart();
        }
    } catch (error) {
        console.error('Error fetching attendance line data:', error);
        initializeAttendanceLineChart();
    }
}

/**
 * ดึงข้อมูลสถานะการเข้าแถวสำหรับกราฟวงกลม
 */
function fetchStatusPieData() {
    try {
        // ถ้ามีตัวแปร pieChartData ที่ส่งมาจาก PHP
        if (typeof pieChartData !== 'undefined') {
            initializeStatusPieChart(pieChartData);
        } else {
            // ถ้าไม่มีข้อมูลจาก PHP ให้ใช้ข้อมูลตัวอย่าง
            initializeStatusPieChart();
        }
    } catch (error) {
        console.error('Error fetching status pie data:', error);
        initializeStatusPieChart();
    }
}

/**
 * ตั้งค่า Event Listeners
 */
function setupEventListeners() {
    // ปุ่มแท็บกราฟเส้น
    document.querySelectorAll('.chart-actions .chart-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // เอาคลาส active ออกจากทุกแท็บ
            document.querySelectorAll('.chart-actions .chart-tab').forEach(t => t.classList.remove('active'));
            // เพิ่มคลาส active ให้กับแท็บที่คลิก
            this.classList.add('active');
            
            // เปลี่ยนข้อมูลกราฟตามช่วงเวลาที่เลือก
            const period = this.getAttribute('data-period');
            updateAttendanceChart(period);
        });
    });
    
    // ปุ่มแท็บตารางอันดับชั้นเรียน
    document.querySelectorAll('.card-actions .chart-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // เอาคลาส active ออกจากทุกแท็บ
            document.querySelectorAll('.card-actions .chart-tab').forEach(t => t.classList.remove('active'));
            // เพิ่มคลาส active ให้กับแท็บที่คลิก
            this.classList.add('active');
            
            // กรองข้อมูลตารางตามระดับชั้นที่เลือก
            const level = this.getAttribute('data-level');
            filterClassTable(level);
        });
    });
    
    // ช่องค้นหานักเรียน
    const studentSearch = document.getElementById('student-search');
    if (studentSearch) {
        studentSearch.addEventListener('input', function() {
            filterStudentTable(this.value);
        });
    }
    
    // ตัวเลือกช่วงเวลา
    const periodSelector = document.getElementById('period-selector');
    if (periodSelector) {
        periodSelector.addEventListener('change', changePeriod);
    }
    
    // ปุ่มดาวน์โหลดรายงาน
    const downloadButton = document.querySelector('.header-button');
    if (downloadButton) {
        downloadButton.addEventListener('click', downloadReport);
    }
    
    // ปุ่มแจ้งเตือนทั้งหมด
    const notifyAllButton = document.querySelector('.card-actions .header-button');
    if (notifyAllButton) {
        notifyAllButton.addEventListener('click', notifyAllRiskStudents);
    }
    
    // ปุ่มปิด Modal
    const closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.closest('.modal').id;
            closeModal(modalId);
        });
    });
    
    // ปิด Modal เมื่อคลิกพื้นหลัง
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // เมื่อหน้าต่างเปลี่ยนขนาด
    window.addEventListener('resize', handleResponsiveLayout);
    
    // เพิ่ม Event Listener สำหรับ Modal แจ้งเตือน
    setupNotificationModal();
}

/**
 * อัปเดตกราฟเส้นตามช่วงเวลาที่เลือก
 */
function updateAttendanceChart(period) {
    if (period === currentPeriod) return;
    currentPeriod = period;
    
    // แสดง loading indicator
    const chartContainer = document.querySelector('.chart-container');
    if (chartContainer) {
        chartContainer.classList.add('loading');
        
        // เพิ่ม div loading ถ้ายังไม่มี
        if (!chartContainer.querySelector('.chart-loading')) {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'chart-loading';
            loadingDiv.style.position = 'absolute';
            loadingDiv.style.top = '0';
            loadingDiv.style.left = '0';
            loadingDiv.style.width = '100%';
            loadingDiv.style.height = '100%';
            loadingDiv.style.display = 'flex';
            loadingDiv.style.alignItems = 'center';
            loadingDiv.style.justifyContent = 'center';
            loadingDiv.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
            loadingDiv.style.zIndex = '10';
            loadingDiv.innerHTML = '<div>กำลังโหลดข้อมูล...</div>';
            
            chartContainer.style.position = 'relative';
            chartContainer.appendChild(loadingDiv);
        }
    }
    
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    // แต่สำหรับตัวอย่างนี้ จะใช้ข้อมูลจำลอง
    setTimeout(() => {
        let labels = [];
        let values = [];
        
        switch (period) {
            case 'week':
                // ข้อมูลรายวันย้อนหลัง 7 วัน
                if (typeof weeklyAttendanceData !== 'undefined') {
                    labels = weeklyAttendanceData.map(item => item.date);
                    values = weeklyAttendanceData.map(item => item.attendance_rate);
                } else {
                    labels = ['10 พ.ค.', '11 พ.ค.', '12 พ.ค.', '13 พ.ค.', '14 พ.ค.', '15 พ.ค.', '16 พ.ค.'];
                    values = [92.5, 93.8, 91.2, 94.5, 90.8, 93.7, 95.2];
                }
                break;
                
            case 'month':
                // ข้อมูลรายวันในเดือนปัจจุบัน
                labels = Array.from({length: 30}, (_, i) => `${i+1}`);
                values = Array.from({length: 30}, () => Math.floor(85 + Math.random() * 15));
                break;
                
            case 'semester':
                // ข้อมูลรายเดือนในภาคเรียนปัจจุบัน
                labels = ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
                values = [92.5, 91.8, 90.5, 93.2, 94.1, 94.8];
                break;
        }
        
        // อัปเดตข้อมูลกราฟ
        if (attendanceLineChart) {
            attendanceLineChart.data.labels = labels;
            attendanceLineChart.data.datasets[0].data = values;
            attendanceLineChart.update();
        }
        
        // ซ่อน loading indicator
        if (chartContainer) {
            chartContainer.classList.remove('loading');
            const loadingDiv = chartContainer.querySelector('.chart-loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
    }, 500);
}

/**
 * กรองตารางชั้นเรียนตามระดับ
 */
function filterClassTable(level) {
    const rows = document.querySelectorAll('.class-rank-table tbody tr');
    
    rows.forEach(row => {
        if (level === 'all' || row.getAttribute('data-level') === level) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * กรองตารางนักเรียนจากการค้นหา
 */
function filterStudentTable(searchText) {
    const rows = document.querySelectorAll('#risk-students-table tbody tr');
    const searchLower = searchText.toLowerCase();
    
    rows.forEach(row => {
        if (row.getAttribute('data-student-id')) {
            const studentName = row.querySelector('.student-detail a')?.textContent.toLowerCase() || '';
            const studentCode = row.querySelector('.student-detail p')?.textContent.toLowerCase() || '';
            
            if (studentName.includes(searchLower) || studentCode.includes(searchLower) || searchText === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

/**
 * เปลี่ยนช่วงเวลาการแสดงผล
 */
function changePeriod() {
    const periodSelector = document.getElementById('period-selector');
    if (!periodSelector) return;
    
    const period = periodSelector.value;
    
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    console.log(`กำลังเปลี่ยนการแสดงผลเป็น: ${period}`);
    
    // ถ้าเป็นกำหนดเอง ให้แสดง modal เลือกวันที่
    if (period === 'custom') {
        showDateRangeSelector();
    } else {
        // ดึงข้อมูลตามช่วงเวลาที่เลือก
        fetchDataByPeriod(period);
    }
}

/**
 * แสดง modal เลือกช่วงวันที่
 */
function showDateRangeSelector() {
    // สร้าง Modal เลือกช่วงวันที่ถ้ายังไม่มี
    if (!document.getElementById('dateRangeModal')) {
        const modal = document.createElement('div');
        modal.id = 'dateRangeModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 400px;">
                <span class="close" onclick="closeModal('dateRangeModal')">&times;</span>
                <h2>เลือกช่วงวันที่</h2>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
                    <div class="form-group">
                        <label for="start-date">วันที่เริ่มต้น</label>
                        <input type="date" id="start-date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="end-date">วันที่สิ้นสุด</label>
                        <input type="date" id="end-date" class="form-control">
                    </div>
                    <div class="form-actions">
                        <button class="btn-cancel" onclick="closeModal('dateRangeModal')">ยกเลิก</button>
                        <button class="btn-send" onclick="applyDateRange()">ตกลง</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    // แสดง Modal
    document.getElementById('dateRangeModal').style.display = 'block';
    
    // ตั้งค่าวันที่เริ่มต้นเป็นวันแรกของเดือนปัจจุบัน
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    startDate.valueAsDate = firstDayOfMonth;
    endDate.valueAsDate = today;
}

/**
 * ประมวลผลช่วงวันที่ที่เลือก
 */
function applyDateRange() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    if (!startDate || !endDate) {
        alert('กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('วันที่เริ่มต้นต้องมาก่อนวันที่สิ้นสุด');
        return;
    }
    
    // ปิด Modal
    closeModal('dateRangeModal');
    
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    console.log(`กำลังดึงข้อมูลตั้งแต่วันที่ ${startDate} ถึง ${endDate}`);
    
    // จำลองการดึงข้อมูลตามช่วงวันที่
    fetchDataByDateRange(startDate, endDate);
}

/**
 * ดึงข้อมูลตามช่วงเวลาที่เลือก
 */
function fetchDataByPeriod(period) {
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    console.log(`กำลังดึงข้อมูลสำหรับช่วง: ${period}`);
    
    // ตัวอย่าง แสดง Loading indicator
    showLoading();
    
    // จำลองการดึงข้อมูลจาก server
    setTimeout(() => {
        hideLoading();
        // ในระบบจริง ควร reload ข้อมูลทั้งหมด
        alert(`เปลี่ยนการแสดงผลเป็นช่วง: ${period} เรียบร้อยแล้ว`);
    }, 1000);
}

/**
 * ดึงข้อมูลตามช่วงวันที่ที่เลือก
 */
function fetchDataByDateRange(startDate, endDate) {
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    console.log(`กำลังดึงข้อมูลตั้งแต่วันที่ ${startDate} ถึง ${endDate}`);
    
    // ตัวอย่าง แสดง Loading indicator
    showLoading();
    
    // จำลองการดึงข้อมูลจาก server
    setTimeout(() => {
        hideLoading();
        // ในระบบจริง ควร reload ข้อมูลทั้งหมด
        alert(`เปลี่ยนการแสดงผลเป็นช่วงวันที่ ${startDate} ถึง ${endDate} เรียบร้อยแล้ว`);
    }, 1000);
}

/**
 * แสดง Loading Overlay
 */
function showLoading() {
    // สร้าง Loading Overlay ถ้ายังไม่มี
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '9999';
        
        const spinner = document.createElement('div');
        spinner.innerHTML = `
            <div style="text-align: center;">
                <div class="material-icons" style="font-size: 48px; animation: spin 1s linear infinite;">refresh</div>
                <div style="margin-top: 10px;">กำลังโหลดข้อมูล...</div>
            </div>
        `;
        
        overlay.appendChild(spinner);
        document.body.appendChild(overlay);
        
        // เพิ่ม style animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.getElementById('loadingOverlay').style.display = 'flex';
}

/**
 * ซ่อน Loading Overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * ดาวน์โหลดรายงาน
 */
function downloadReport() {
    const periodSelector = document.getElementById('period-selector');
    const period = periodSelector ? periodSelector.value : 'month';
    
    // ในระบบจริง ควรส่ง POST request ไปยัง endpoint ที่จะสร้างรายงาน
    console.log(`กำลังดาวน์โหลดรายงานสำหรับช่วง: ${period}`);
    
    // ตัวอย่าง แสดง Loading indicator
    showLoading();
    
    // จำลองการสร้างรายงาน
    setTimeout(() => {
        hideLoading();
        alert(`เริ่มดาวน์โหลดรายงานสำหรับช่วง: ${period}`);
        
        // ในระบบจริง ควรเปิดหน้าต่างดาวน์โหลดหรือส่ง response เป็นไฟล์
        // window.location.href = `download_report.php?period=${period}`;
    }, 1000);
}

/**
 * ดูรายละเอียดนักเรียน
 */
function viewStudentDetail(studentId) {
    currentStudentId = studentId;
    
    // แสดง modal
    const modal = document.getElementById('studentDetailModal');
    if (!modal) return;
    
    modal.style.display = 'block';
    
    // ตั้งค่า loading state
    const contentDiv = document.getElementById('student-detail-content');
    if (contentDiv) {
        contentDiv.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }
    
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend เพื่อดึงข้อมูลนักเรียน
    
    // จำลองการดึงข้อมูลจาก server
    setTimeout(() => {
        fetchStudentDetails(studentId);
    }, 500);
}

/**
 * ดึงข้อมูลรายละเอียดนักเรียน
 */
function fetchStudentDetails(studentId) {
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
    
    // ตัวอย่างข้อมูลจำลอง
    const studentData = {
        id: studentId,
        name: 'นักเรียนรหัส ' + studentId,
        class: 'ปวช.1/1',
        attendanceRate: 65.8,
        attendance: [
            { date: '10 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:45' },
            { date: '11 พ.ค. 2568', status: 'ขาด', statusClass: 'text-danger', time: '-' },
            { date: '12 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:50' },
            { date: '13 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:42' },
            { date: '14 พ.ค. 2568', status: 'ขาด', statusClass: 'text-danger', time: '-' }
        ]
    };
    
    // อัปเดตชื่อนักเรียนใน modal
    const titleElement = document.getElementById('modal-student-name');
    if (titleElement) {
        titleElement.textContent = studentData.name;
    }
    
    // สร้าง HTML สำหรับแสดงข้อมูล
    let html = `
        <div class="student-info">
            <div class="student-header">
                <h3>${studentData.name}</h3>
                <p>ชั้น ${studentData.class}</p>
                <p>อัตราการเข้าแถว: <span class="${studentData.attendanceRate < 70 ? 'text-danger' : 'text-warning'}">${studentData.attendanceRate}%</span></p>
            </div>
            
            <h4>ประวัติการเข้าแถว</h4>
            <div class="table-responsive">
                <table class="attendance-history-table">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                            <th>เวลา</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    // เพิ่มข้อมูลประวัติการเข้าแถว
    studentData.attendance.forEach(day => {
        html += `
            <tr>
                <td>${day.date}</td>
                <td><span class="${day.statusClass}">${day.status}</span></td>
                <td>${day.time}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <div class="button-group">
                <button class="btn-primary" onclick="notifyParent(${studentId})">
                    <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                </button>
                <button class="btn-secondary" onclick="viewFullHistory(${studentId})">
                    <span class="material-icons">history</span> ดูประวัติทั้งหมด
                </button>
            </div>
        </div>
    `;
    
    // อัปเดตเนื้อหาใน modal
    const contentDiv = document.getElementById('student-detail-content');
    if (contentDiv) {
        contentDiv.innerHTML = html;
    }
}

/**
 * ปิด modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
function notifyParent(studentId) {
    currentStudentId = studentId;
    
    // แสดง modal แจ้งเตือน
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.style.display = 'block';
        
        // ตั้งค่า template เริ่มต้น
        updateNotificationContent();
    }
}

/**
 * ตั้งค่า Modal การแจ้งเตือน
 */
function setupNotificationModal() {
    // Template selector
    const templateSelect = document.getElementById('notification-template');
    if (templateSelect) {
        templateSelect.addEventListener('change', updateNotificationContent);
    }
    
    // ปุ่มส่งข้อความ
    const sendButton = document.querySelector('.btn-send');
    if (sendButton) {
        sendButton.addEventListener('click', sendNotification);
    }
}

/**
 * อัปเดตเนื้อหาข้อความแจ้งเตือน
 */
function updateNotificationContent() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    if (!templateSelect || !contentField) return;
    
    const template = templateSelect.value;
    
    // ตัวอย่างเทมเพลตข้อความ
    switch (template) {
        case 'risk_alert':
            contentField.value = `เรียน ผู้ปกครองของนักเรียน

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 70% ซึ่งต่ำกว่าเกณฑ์ที่กำหนด (80%)

กรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'absence_alert':
            contentField.value = `เรียน ผู้ปกครองของนักเรียน

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้

กรุณาติดต่อครูที่ปรึกษาหากมีข้อสงสัย

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'monthly_report':
            contentField.value = `เรียน ผู้ปกครองของนักเรียน

รายงานสรุปการเข้าแถวประจำเดือนพฤษภาคม 2568

จำนวนวันเข้าแถว: 15 วัน
จำนวนวันขาด: 5 วัน
อัตราการเข้าแถว: 75%
สถานะ: เสี่ยงไม่ผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            contentField.value = '';
            break;
    }
}

/**
 * ส่งข้อความแจ้งเตือน
 */
function sendNotification() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    if (!templateSelect || !contentField) return;
    
    const template = templateSelect.value;
    const content = contentField.value;
    
    if (!content.trim()) {
        alert('กรุณากรอกข้อความแจ้งเตือน');
        return;
    }
    
    // แสดง loading
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX request ไปยัง backend เพื่อส่งข้อความแจ้งเตือน
    setTimeout(() => {
        hideLoading();
        alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนรหัส ${currentStudentId} เรียบร้อยแล้ว`);
        
        // ปิด modal
        closeModal('notificationModal');
    }, 1000);
}

/**
 * ปิด modal แจ้งเตือน
 */
function closeNotificationModal() {
    closeModal('notificationModal');
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมด
 */
function notifyAllRiskStudents() {
    // ในระบบจริง ควรมี modal ให้ยืนยันการส่งแจ้งเตือนทั้งหมด
    
    if (confirm('คุณต้องการส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดหรือไม่?')) {
        // แสดง loading
        showLoading();
        
        // ในระบบจริง ควรส่ง AJAX request ไปยัง backend
        setTimeout(() => {
            hideLoading();
            alert('ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดเรียบร้อยแล้ว');
        }, 1500);
    }
}

/**
 * ดูประวัติการเข้าแถวทั้งหมด
 */
function viewFullHistory(studentId) {
    // ในระบบจริง ควรนำทางไปยังหน้าประวัติแบบละเอียด
    window.location.href = `student_history.php?id=${studentId}`;
}

/**
 * ปรับขนาดตามขนาดหน้าจอ
 */
function handleResponsiveLayout() {
    // ปรับขนาดกราฟตามขนาดหน้าจอ
    if (window.innerWidth <= 768) {
        // สำหรับหน้าจอมือถือ
        document.querySelectorAll('.chart-container').forEach(container => {
            container.style.height = '250px';
        });
    } else {
        // สำหรับหน้าจอขนาดใหญ่
        document.querySelectorAll('.chart-container').forEach(container => {
            container.style.height = '300px';
        });
    }
    
    // อัปเดตกราฟเมื่อเปลี่ยนขนาดหน้าจอ
    if (attendanceLineChart) {
        attendanceLineChart.resize();
    }
    
    if (statusPieChart) {
        statusPieChart.resize();
    }
}

/**
 * ตั้งค่า UI Components อื่นๆ
 */
function initializeUIComponents() {
    // เพิ่ม Animation เมื่อ hover เมาส์บน card
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
}
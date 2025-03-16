/**
 * charts.js - JavaScript สำหรับแผนภูมิและกราฟในระบบ STUDENT-Prasat
 * 
 * ไฟล์นี้มีฟังก์ชันสำหรับสร้างและอัปเดตกราฟและแผนภูมิต่างๆ ในระบบ
 * โดยใช้ Chart.js (ต้องมีการโหลด Chart.js ก่อนหน้านี้)
 */

// เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart !== 'undefined') {
        // สร้างกราฟที่จำเป็นตามหน้าที่กำลังแสดง
        initPageCharts();
    } else {
        console.log('Chart.js not loaded');
    }
});

/**
 * สร้างกราฟตามหน้าที่กำลังแสดง
 */
function initPageCharts() {
    // ตรวจสอบว่าอยู่ที่หน้าไหนและเรียกใช้ฟังก์ชันที่เหมาะสม
    const currentPage = document.body.getAttribute('data-page');
    
    switch (currentPage) {
        case 'dashboard':
            initDashboardCharts();
            break;
        case 'at_risk':
            initAttendanceCharts();
            break;
        case 'reports':
            initReportCharts();
            break;
        case 'check_attendance':
            initCheckAttendanceCharts();
            break;
        default:
            // ไม่ต้องสร้างกราฟถ้าไม่มีหน้าที่ต้องการใช้
            break;
    }
}

/**
 * สร้างกราฟสำหรับหน้าแดชบอร์ด
 */
function initDashboardCharts() {
    // กราฟเส้นแสดงอัตราการเข้าแถวรายวัน
    createDailyAttendanceChart();
    
    // กราฟวงกลมแสดงสัดส่วนนักเรียนที่มาเรียน/ขาดเรียน
    createAttendancePieChart();
    
    // กราฟแท่งแสดงอัตราการเข้าแถวแยกตามระดับชั้น
    createClassAttendanceChart();
}

/**
 * สร้างกราฟสำหรับหน้านักเรียนเสี่ยงตกกิจกรรม
 */
function initAttendanceCharts() {
    // กราฟแท่งแสดงอัตราการเข้าแถวแยกตามระดับชั้น
    createClassAttendanceChart();
    
    // กราฟวงกลมแสดงสัดส่วนความเสี่ยง
    createRiskLevelPieChart();
}

/**
 * สร้างกราฟสำหรับหน้ารายงาน
 */
function initReportCharts() {
    // กราฟเส้นแสดงแนวโน้มการเข้าแถวตลอดปีการศึกษา
    createYearlyTrendChart();
    
    // กราฟแท่งเปรียบเทียบอัตราการเข้าแถวระหว่างระดับชั้น
    createClassComparisonChart();
    
    // กราฟวงกลมแสดงสาเหตุการขาดเรียน
    createAbsenceReasonChart();
}

/**
 * สร้างกราฟสำหรับหน้าเช็คชื่อ
 */
function initCheckAttendanceCharts() {
    // กราฟวงกลมแสดงสัดส่วนการเช็คชื่อวันนี้
    createTodayAttendanceChart();
}

/**
 * สร้างกราฟเส้นแสดงอัตราการเข้าแถวรายวัน (7 วันล่าสุด)
 */
function createDailyAttendanceChart() {
    const ctx = document.getElementById('dailyAttendanceChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'จันทร์', 'วันนี้'],
        datasets: [{
            label: 'อัตราการเข้าแถว (%)',
            data: [95.2, 96.1, 93.8, 92.7, 91.5, 98.1, 95.0],
            borderColor: '#06c755',
            backgroundColor: 'rgba(6, 199, 85, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#06c755',
            pointRadius: 4
        }]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 85,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟวงกลมแสดงสัดส่วนนักเรียนที่มาเรียน/ขาดเรียน
 */
function createAttendancePieChart() {
    const ctx = document.getElementById('attendancePieChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['มาเรียน', 'ขาดเรียน', 'มาสาย', 'ลา'],
        datasets: [{
            data: [1187, 43, 12, 8],
            backgroundColor: [
                '#4caf50', // มาเรียน
                '#f44336', // ขาดเรียน
                '#ff9800', // มาสาย
                '#2196f3'  // ลา
            ],
            borderWidth: 0
        }]
    };
    
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} คน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟแท่งแสดงอัตราการเข้าแถวแยกตามระดับชั้น
 */
function createClassAttendanceChart() {
    const ctx = document.getElementById('classAttendanceChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'],
        datasets: [{
            label: 'อัตราการเข้าแถว (%)',
            data: [92, 90, 88, 78, 72, 65],
            backgroundColor: [
                '#64b5f6', // ม.1
                '#64b5f6', // ม.2
                '#66bb6a', // ม.3
                '#ffa726', // ม.4
                '#ef5350', // ม.5
                '#ef5350'  // ม.6
            ],
            borderWidth: 0,
            borderRadius: 4
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 60,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟวงกลมแสดงสัดส่วนความเสี่ยง
 */
function createRiskLevelPieChart() {
    const ctx = document.getElementById('riskLevelChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['ปกติ', 'ต้องระวัง', 'เสี่ยงปานกลาง', 'เสี่ยงสูง'],
        datasets: [{
            data: [950, 210, 65, 25],
            backgroundColor: [
                '#4caf50', // ปกติ
                '#ff9800', // ต้องระวัง
                '#ff7043', // เสี่ยงปานกลาง
                '#f44336'  // เสี่ยงสูง
            ],
            borderWidth: 0
        }]
    };
    
    const config = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} คน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟเส้นแสดงแนวโน้มการเข้าแถวตลอดปีการศึกษา
 */
function createYearlyTrendChart() {
    const ctx = document.getElementById('yearlyTrendChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.'],
        datasets: [{
            label: 'ม.ต้น',
            data: [95, 94, 92, 91, 93, 94, 90, 89, 92, 91, 90],
            borderColor: '#1976d2',
            backgroundColor: 'rgba(25, 118, 210, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }, {
            label: 'ม.ปลาย',
            data: [92, 90, 88, 85, 86, 88, 84, 82, 83, 80, 78],
            borderColor: '#ff7043',
            backgroundColor: 'rgba(255, 112, 67, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 75,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟแท่งเปรียบเทียบอัตราการเข้าแถวระหว่างระดับชั้น
 */
function createClassComparisonChart() {
    const ctx = document.getElementById('classComparisonChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'],
        datasets: [{
            label: 'อัตราการเข้าแถวเฉลี่ย (%)',
            data: [92, 90, 88, 78, 72, 65],
            backgroundColor: [
                '#64b5f6',
                '#64b5f6',
                '#64b5f6',
                '#ffa726',
                '#ffa726',
                '#ef5350'
            ],
            borderWidth: 0,
            borderRadius: 4
        }, {
            label: 'เป้าหมาย (%)',
            data: [80, 80, 80, 80, 80, 80],
            type: 'line',
            borderColor: '#f44336',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            pointStyle: false
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 60,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟวงกลมแสดงสาเหตุการขาดเรียน
 */
function createAbsenceReasonChart() {
    const ctx = document.getElementById('absenceReasonChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['ป่วย', 'ธุระส่วนตัว', 'มาสาย', 'ไม่ทราบสาเหตุ'],
        datasets: [{
            data: [42, 28, 15, 15],
            backgroundColor: [
                '#2196f3',
                '#ff9800',
                '#9c27b0',
                '#f44336'
            ],
            borderWidth: 0
        }]
    };
    
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${percentage}%`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

/**
 * สร้างกราฟวงกลมแสดงสัดส่วนการเช็คชื่อวันนี้
 */
function createTodayAttendanceChart() {
    const ctx = document.getElementById('todayAttendanceChart');
    if (!ctx) return;
    
    // ข้อมูลตัวอย่าง (ในทางปฏิบัติจริง ควรดึงจาก API)
    const data = {
        labels: ['เช็คชื่อแล้ว', 'ยังไม่ได้เช็คชื่อ'],
        datasets: [{
            data: [1187, 63],
            backgroundColor: [
                '#4caf50',
                '#f44336'
            ],
            borderWidth: 0
        }]
    };
    
    const config = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} คน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}
/**
 * charts.js - JavaScript สำหรับแสดงกราฟต่างๆ ในระบบน้องชูใจ AI ดูแลผู้เรียน
 */

/**
 * เริ่มต้นแผนภูมิและกราฟต่างๆ ในหน้านักเรียนเสี่ยงตกกิจกรรม
 */
function initAttendanceCharts() {
    // ตรวจสอบว่ามีกราฟในหน้าหรือไม่
    const chartContainer = document.getElementById('attendance-chart');
    if (!chartContainer) return;

    // สร้างแผนภูมิอัตราการเข้าแถวแยกตามระดับชั้น
    createAttendanceRateByLevelChart();
}

/**
 * สร้างแผนภูมิอัตราการเข้าแถวแยกตามระดับชั้น
 */
function createAttendanceRateByLevelChart() {
    // ดึงข้อมูลจาก chart-bar elements
    const chartBars = document.querySelectorAll('.chart-bar-item');
    
    // ถ้าไม่มีข้อมูล หรือไม่มี Chart.js ให้ใช้กราฟ CSS แทน
    if (chartBars.length === 0 || typeof Chart === 'undefined') {
        animateChartBars();
        return;
    }

    // เก็บข้อมูลสำหรับกราฟ
    const labels = [];
    const data = [];
    const backgroundColors = [];
    const borderColors = [];

    // ดึงข้อมูลจากแต่ละแท่ง
    chartBars.forEach(item => {
        const label = item.querySelector('.chart-bar-label').textContent;
        const value = parseFloat(item.querySelector('.chart-bar-value').textContent);
        let bgColor, borderColor;

        // กำหนดสีตามเกณฑ์
        if (value < 60) {
            bgColor = 'rgba(220, 53, 69, 0.2)';
            borderColor = 'rgba(220, 53, 69, 1)';
        } else if (value < 70) {
            bgColor = 'rgba(255, 193, 7, 0.2)';
            borderColor = 'rgba(255, 193, 7, 1)';
        } else if (value < 80) {
            bgColor = 'rgba(23, 162, 184, 0.2)';
            borderColor = 'rgba(23, 162, 184, 1)';
        } else {
            bgColor = 'rgba(40, 167, 69, 0.2)';
            borderColor = 'rgba(40, 167, 69, 1)';
        }

        labels.push(label);
        data.push(value);
        backgroundColors.push(bgColor);
        borderColors.push(borderColor);
    });

    // สร้าง canvas element
    const canvas = document.createElement('canvas');
    canvas.id = 'attendanceRateChart';
    canvas.style.width = '100%';
    canvas.style.height = '100%';

    // ล้างข้อมูลในกล่อง chart และเพิ่ม canvas
    const chartContainer = document.getElementById('attendance-chart');
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
                            return context.parsed.y.toFixed(1) + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * สร้างแอนิเมชันสำหรับแผนภูมิแท่งที่ใช้ CSS (กรณีไม่สามารถใช้ Chart.js ได้)
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
            // ดึงค่าเปอร์เซ็นต์จาก .chart-bar-value
            const targetHeight = parseFloat(bar.querySelector('.chart-bar-value').textContent);
            
            // กำหนดความสูงจริง (เทียบจากเปอร์เซ็นต์เต็ม 100 = 220px)
            bar.style.height = Math.min(targetHeight, 100) + '%';
        });
    }, 300);
}

/**
 * สร้างแผนภูมิแนวโน้มการเข้าแถวของนักเรียนรายบุคคล
 * 
 * @param {string} canvasId - ID ของ canvas element
 * @param {Array} attendanceData - ข้อมูลการเข้าแถว
 */
function createStudentAttendanceTrendChart(canvasId, attendanceData) {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = [];
    const presentData = [];
    const absentData = [];
    const rateData = [];
    
    // คำนวณข้อมูลสะสม
    let totalDays = 0;
    let presentDays = 0;
    
    attendanceData.forEach(record => {
        totalDays++;
        if (record.status === 'present') {
            presentDays++;
        }
        
        labels.push(`วันที่ ${totalDays}`);
        presentData.push(presentDays);
        absentData.push(totalDays - presentDays);
        rateData.push((presentDays / totalDays) * 100);
    });
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'อัตราการเข้าแถว (%)',
                    data: rateData,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    tension: 0.2,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'จำนวนวันที่เข้าแถว',
                    data: presentData,
                    borderColor: 'rgba(23, 162, 184, 1)',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderWidth: 2,
                    tension: 0.2,
                    pointBackgroundColor: 'rgba(23, 162, 184, 1)',
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    title: {
                        display: true,
                        text: 'อัตราการเข้าแถว (%)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'จำนวนวัน'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label.includes('%')) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            } else {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        }
    });
}

/**
 * สร้างแผนภูมิวงกลมแสดงอัตราส่วนการเข้าแถวและขาดแถว
 * 
 * @param {string} canvasId - ID ของ canvas element
 * @param {number} presentDays - จำนวนวันที่เข้าแถว
 * @param {number} absentDays - จำนวนวันที่ขาดแถว
 */
function createAttendancePieChart(canvasId, presentDays, absentDays) {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'pie',
        data: {
            labels: ['เข้าแถว', 'ขาดแถว'],
            datasets: [{
                data: [presentDays, absentDays],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
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
                            const total = presentDays + absentDays;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} วัน (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * สร้างแผนภูมิเปรียบเทียบอัตราการเข้าแถวระหว่างนักเรียนและค่าเฉลี่ยของห้องเรียน
 * 
 * @param {string} canvasId - ID ของ canvas element
 * @param {number} studentRate - อัตราการเข้าแถวของนักเรียน
 * @param {number} classRate - อัตราการเข้าแถวเฉลี่ยของห้องเรียน
 * @param {number} gradeRate - อัตราการเข้าแถวเฉลี่ยของระดับชั้น
 * @param {number} schoolRate - อัตราการเข้าแถวเฉลี่ยของโรงเรียน
 */
function createAttendanceComparisonChart(canvasId, studentRate, classRate, gradeRate, schoolRate) {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // กำหนดสีตามเกณฑ์
    const getBarColor = (rate) => {
        if (rate < 60) return 'rgba(220, 53, 69, 0.7)';
        if (rate < 70) return 'rgba(255, 193, 7, 0.7)';
        if (rate < 80) return 'rgba(23, 162, 184, 0.7)';
        return 'rgba(40, 167, 69, 0.7)';
    };
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['นักเรียน', 'ห้องเรียน', 'ระดับชั้น', 'โรงเรียน'],
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: [studentRate, classRate, gradeRate, schoolRate],
                backgroundColor: [
                    getBarColor(studentRate),
                    getBarColor(classRate),
                    getBarColor(gradeRate),
                    getBarColor(schoolRate)
                ],
                borderColor: [
                    getBarColor(studentRate).replace('0.7', '1'),
                    getBarColor(classRate).replace('0.7', '1'),
                    getBarColor(gradeRate).replace('0.7', '1'),
                    getBarColor(schoolRate).replace('0.7', '1')
                ],
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
 * สร้างแผนภูมิแนวโน้มความเสี่ยงของนักเรียนในระบบ
 * 
 * @param {string} canvasId - ID ของ canvas element
 * @param {Array} data - ข้อมูลแนวโน้มความเสี่ยง เช่น [{month: 'ม.ค.', low: 50, medium: 30, high: 15, critical: 5}, ...]
 */
function createRiskTrendChart(canvasId, data) {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // เก็บข้อมูลสำหรับกราฟ
    const labels = data.map(item => item.month);
    const lowData = data.map(item => item.low);
    const mediumData = data.map(item => item.medium);
    const highData = data.map(item => item.high);
    const criticalData = data.map(item => item.critical);
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'ความเสี่ยงต่ำ',
                    data: lowData,
                    borderColor: 'rgba(23, 162, 184, 1)',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'ความเสี่ยงปานกลาง',
                    data: mediumData,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'ความเสี่ยงสูง',
                    data: highData,
                    borderColor: 'rgba(255, 87, 34, 1)',
                    backgroundColor: 'rgba(255, 87, 34, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'ความเสี่ยงวิกฤต',
                    data: criticalData,
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนนักเรียน'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

/**
 * สร้างแผนภูมิเปรียบเทียบอัตราการเข้าแถวแยกตามห้องเรียน
 * 
 * @param {string} canvasId - ID ของ canvas element
 * @param {Object} data - ข้อมูลอัตราการเข้าแถว เช่น {'1/1': 85.2, '1/2': 78.5, ...}
 * @param {string} level - ระดับชั้นที่ต้องการแสดง
 */
function createAttendanceRateByClassChart(canvasId, data, level) {
    // ตรวจสอบว่ามี Chart.js หรือไม่
    if (typeof Chart === 'undefined') return;
    
    // ตรวจสอบว่ามี canvas element หรือไม่
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // กรองข้อมูลตามระดับชั้น
    const filteredData = {};
    for (const [key, value] of Object.entries(data)) {
        if (key.startsWith(level)) {
            filteredData[key] = value;
        }
    }
    
    // เก็บข้อมูลสำหรับกราฟ
    const labels = Object.keys(filteredData);
    const values = Object.values(filteredData);
    const backgroundColors = values.map(value => {
        if (value < 60) return 'rgba(220, 53, 69, 0.7)';
        if (value < 70) return 'rgba(255, 193, 7, 0.7)';
        if (value < 80) return 'rgba(23, 162, 184, 0.7)';
        return 'rgba(40, 167, 69, 0.7)';
    });
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));
    
    // สร้างกราฟด้วย Chart.js
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: values,
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

// สำหรับการติดตั้ง event listener เมื่อโหลด DOM เสร็จแล้ว
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นแผนภูมิ
    initAttendanceCharts();
});
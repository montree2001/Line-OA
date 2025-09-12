/**
 * executive_reports.js - JavaScript สำหรับแดชบอร์ดผู้บริหาร
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 * 
 * รองรับการแสดงผลบนมือถือและการทำงานแบบ Real-time
 */

// ตัวแปรสำหรับเก็บ Chart instances
let executiveTrendChart;
let executiveStatusChart;
let departmentComparisonChart;
let classRankingChart;

// ตัวแปรสำหรับการควบคุม
let currentPeriod = 'month';
let currentDepartment = 'all';
let refreshInterval;
let isAutoRefresh = true;

// เมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    initializeExecutiveDashboard();
    setupEventListeners();
    setupAutoRefresh();
    
    // ตรวจสอบว่าเป็นมือถือหรือไม่
    if (isMobileDevice()) {
        optimizeForMobile();
    }
});

/**
 * เริ่มต้นแดชบอร์ดผู้บริหาร
 */
function initializeExecutiveDashboard() {
    showLoading();
    
    try {
        // สร้างแผนภูมิทั้งหมด
        initializeExecutiveCharts();
        
        // เปิดใช้งานแท็บแรก
        activateTab('departments');
        
        // ตั้งค่าตารางข้อมูล
        setupDataTables();
        
        hideLoading();
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเริ่มต้นแดชบอร์ด:', error);
        showError('ไม่สามารถโหลดแดชบอร์ดได้ กรุณาลองใหม่อีกครั้ง');
        hideLoading();
    }
}

/**
 * สร้างแผนภูมิทั้งหมด
 */
function initializeExecutiveCharts() {
    // แผนภูมิแนวโน้มการเข้าแถว
    createTrendChart();
    
    // แผนภูมิสถานะการเข้าแถว
    createStatusChart();
    
    // แผนภูมิเปรียบเทียบแผนกวิชา
    createDepartmentComparisonChart();
    
    // แผนภูมิอันดับห้องเรียน
    createClassRankingChart();
}

/**
 * สร้างแผนภูมิแนวโน้มการเข้าแถว
 */
function createTrendChart() {
    const ctx = document.getElementById('executiveTrendChart');
    if (!ctx) return;
    
    const labels = executiveData.weeklyTrends.map(item => item.date);
    const data = executiveData.weeklyTrends.map(item => item.attendance_rate);
    const presentCounts = executiveData.weeklyTrends.map(item => item.present_count);
    const totalCounts = executiveData.weeklyTrends.map(item => item.total_checked);
    
    executiveTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: data,
                borderColor: '#1565C0',
                backgroundColor: 'rgba(21, 101, 192, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1565C0',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#1565C0',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const rate = context.parsed.y;
                            const present = presentCounts[index] || 0;
                            const total = totalCounts[index] || 0;
                            
                            return [
                                `อัตราการเข้าแถว: ${rate}%`,
                                `นักเรียนเข้าแถว: ${present} คน`,
                                `จากทั้งหมด: ${total} คน`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...data) - 10),
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

/**
 * สร้างแผนภูมิสถานะการเข้าแถว
 */
function createStatusChart() {
    const ctx = document.getElementById('executiveStatusChart');
    if (!ctx) return;
    
    const labels = executiveData.attendanceStatus.map(item => item.status);
    const data = executiveData.attendanceStatus.map(item => item.percent);
    const colors = executiveData.attendanceStatus.map(item => item.color);
    
    executiveStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverBorderWidth: 3,
                hoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const status = executiveData.attendanceStatus[index];
                            return [
                                `${context.label}: ${context.parsed}%`,
                                `จำนวน: ${status.count?.toLocaleString() || 0} ครั้ง`
                            ];
                        }
                    }
                }
            },
            cutout: '65%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000
            }
        }
    });
}

/**
 * สร้างแผนภูมิเปรียบเทียบแผนกวิชา
 */
function createDepartmentComparisonChart() {
    const ctx = document.getElementById('departmentComparisonChart');
    if (!ctx) return;
    
    const deptNames = executiveData.departmentPerformance.map(dept => dept.department_name);
    const attendanceRates = executiveData.departmentPerformance.map(dept => dept.avg_attendance_rate);
    const studentCounts = executiveData.departmentPerformance.map(dept => dept.total_students);
    const riskCounts = executiveData.departmentPerformance.map(dept => dept.at_risk_count);
    
    // กำหนดสีตามประสิทธิภาพ
    const backgroundColors = executiveData.departmentPerformance.map(dept => {
        switch(dept.performance_status) {
            case 'excellent': return 'rgba(46, 125, 50, 0.8)';
            case 'good': return 'rgba(0, 131, 143, 0.8)';
            case 'warning': return 'rgba(245, 127, 23, 0.8)';
            case 'critical': return 'rgba(198, 40, 40, 0.8)';
            default: return 'rgba(108, 117, 125, 0.8)';
        }
    });
    
    departmentComparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: deptNames,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: attendanceRates,
                backgroundColor: backgroundColors,
                borderWidth: 0,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const dept = executiveData.departmentPerformance[index];
                            return [
                                `อัตราการเข้าแถว: ${context.parsed.y}%`,
                                `จำนวนนักเรียน: ${studentCounts[index]} คน`,
                                `นักเรียนเสี่ยง: ${riskCounts[index]} คน`,
                                `สถานะ: ${dept.status_text}`
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
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
 * สร้างแผนภูมิอันดับห้องเรียน
 */
function createClassRankingChart() {
    const ctx = document.getElementById('classRankingChart');
    if (!ctx) return;
    
    // เรียงลำดับตามประสิทธิภาพ
    const sortedClasses = [...executiveData.classPerformance].sort((a, b) => b.avg_attendance_rate - a.avg_attendance_rate);
    
    const classNames = sortedClasses.slice(0, 15).map(cls => cls.class_name + ' ' + cls.department_name);
    const attendanceRates = sortedClasses.slice(0, 15).map(cls => cls.avg_attendance_rate);
    
    // กำหนดสีตามประสิทธิภาพ
    const backgroundColors = sortedClasses.slice(0, 15).map(cls => {
        switch(cls.performance_level) {
            case 'excellent': return 'rgba(46, 125, 50, 0.8)';
            case 'good': return 'rgba(0, 131, 143, 0.8)';
            case 'average': return 'rgba(245, 127, 23, 0.8)';
            case 'poor': return 'rgba(198, 40, 40, 0.8)';
            default: return 'rgba(108, 117, 125, 0.8)';
        }
    });
    
    classRankingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: classNames,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: attendanceRates,
                backgroundColor: backgroundColors,
                borderWidth: 0,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const cls = sortedClasses[index];
                            return [
                                `อัตราการเข้าแถว: ${context.parsed.x}%`,
                                `จำนวนนักเรียน: ${cls.total_students} คน`,
                                `นักเรียนเสี่ยง: ${cls.risk_count} คน`,
                                `ประสิทธิภาพ: ${cls.level_text}`,
                                `ครูที่ปรึกษา: ${cls.advisor_name || 'ไม่ระบุ'}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            },
            animation: {
                duration: 1200,
                easing: 'easeOutQuart'
            }
        }
    });
}

/**
 * ตั้งค่า Event Listeners
 */
function setupEventListeners() {
    // ตัวเลือกช่วงเวลา
    const periodSelector = document.getElementById('period-selector');
    if (periodSelector) {
        periodSelector.addEventListener('change', handlePeriodChange);
    }
    
    // ตัวเลือกแผนกวิชา
    const departmentSelector = document.getElementById('department-selector');
    if (departmentSelector) {
        departmentSelector.addEventListener('change', handleDepartmentChange);
    }
    
    // ปุ่มรีเฟรช
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshDashboard);
    }
    
    // ปุ่มส่งออกรายงาน
    const exportBtn = document.querySelector('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportReport);
    }
    
    // แท็บต่างๆ
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            activateTab(tabId);
        });
    });
    
    // ปุ่มกรองในแผนภูมิ
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            updateTrendChart(period);
        });
    });
    
    // ปุ่มกรองระดับชั้น
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const level = this.getAttribute('data-level');
            filterClassRanking(level);
        });
    });
    
    // ค้นหานักเรียน
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', handleStudentSearch);
    }
    
    // การปิดหน้าต่าง - หยุด auto refresh
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
}

/**
 * จัดการการเปลี่ยนช่วงเวลา
 */
function handlePeriodChange() {
    const periodSelector = document.getElementById('period-selector');
    currentPeriod = periodSelector.value;
    
    if (currentPeriod === 'custom') {
        showCustomDatePicker();
    } else {
        refreshDashboard();
    }
}

/**
 * จัดการการเปลี่ยนแผนกวิชา
 */
function handleDepartmentChange() {
    const departmentSelector = document.getElementById('department-selector');
    currentDepartment = departmentSelector.value;
    refreshDashboard();
}

/**
 * รีเฟรชแดชบอร์ด
 */
function refreshDashboard() {
    showLoading();
    
    // สร้าง URL สำหรับการดึงข้อมูลใหม่
    const params = new URLSearchParams({
        period: currentPeriod,
        department_id: currentDepartment === 'all' ? '' : currentDepartment,
        ajax: '1'
    });
    
    // ในการใช้งานจริง จะเรียก API เพื่อดึงข้อมูลใหม่
    // fetch(`executive_reports.php?${params}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         updateDashboardData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('เกิดข้อผิดพลาดในการรีเฟรชข้อมูล:', error);
    //         showError('ไม่สามารถรีเฟรชข้อมูลได้');
    //         hideLoading();
    //     });
    
    // จำลองการรีเฟรช
    setTimeout(() => {
        hideLoading();
        showSuccess('รีเฟรชข้อมูลเรียบร้อยแล้ว');
    }, 1000);
}

/**
 * ส่งออกรายงาน
 */
function exportReport() {
    showLoading();
    
    // สร้าง URL สำหรับการส่งออก
    const params = new URLSearchParams({
        period: currentPeriod,
        department_id: currentDepartment === 'all' ? '' : currentDepartment,
        export: 'pdf'
    });
    
    // ในการใช้งานจริง จะสร้างไฟล์ PDF
    // window.open(`executive_reports.php?${params}`, '_blank');
    
    setTimeout(() => {
        hideLoading();
        showSuccess('กำลังสร้างรายงาน PDF...');
    }, 800);
}

/**
 * ส่งออกรายงานประสิทธิภาพห้องเรียนเป็น Excel
 */
function exportClassPerformanceExcel() {
    showLoading();
    
    try {
        // สร้าง URL สำหรับการส่งออก Excel
        const params = new URLSearchParams({
            period: currentPeriod,
            department_id: currentDepartment === 'all' ? '' : currentDepartment
        });
        
        // สร้าง URL สำหรับดาวน์โหลด
        const exportUrl = `export_class_performance_excel.php?${params}`;
        
        // สร้าง link element สำหรับดาวน์โหลด
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = '';
        link.style.display = 'none';
        
        // เพิ่มเข้าไปใน DOM และคลิก
        document.body.appendChild(link);
        link.click();
        
        // ลบ link ออกจาก DOM
        document.body.removeChild(link);
        
        hideLoading();
        showSuccess('กำลังดาวน์โหลดไฟล์ Excel...');
        
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการส่งออก Excel:', error);
        hideLoading();
        showError('ไม่สามารถส่งออกไฟล์ Excel ได้ กรุณาลองใหม่อีกครั้ง');
    }
}

/**
 * เปิดใช้งานแท็บ
 */
function activateTab(tabId) {
    // ซ่อนแท็บทั้งหมด
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // ปิดการใช้งานปุ่มแท็บทั้งหมด
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // แสดงแท็บที่เลือก
    const selectedTab = document.getElementById(`tab-${tabId}`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // เปิดใช้งานปุ่มที่เลือก
    const selectedBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }
    
    // ปรับขนาดแผนภูมิหลังจากแสดงแท็บ
    setTimeout(() => {
        resizeChartsInTab(tabId);
    }, 100);
}

/**
 * ปรับขนาดแผนภูมิในแท็บ
 */
function resizeChartsInTab(tabId) {
    switch(tabId) {
        case 'departments':
            if (departmentComparisonChart) {
                departmentComparisonChart.resize();
                departmentComparisonChart.update();
            }
            break;
        case 'classes':
            if (classRankingChart) {
                classRankingChart.resize();
                classRankingChart.update();
            }
            break;
    }
}

/**
 * อัปเดตแผนภูมิแนวโน้ม
 */
function updateTrendChart(period) {
    // เปลี่ยนสถานะปุ่ม
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-period="${period}"]`).classList.add('active');
    
    // ในการใช้งานจริง จะดึงข้อมูลใหม่ตาม period
    showSuccess(`เปลี่ยนมุมมองเป็น${period === 'week' ? 'รายสัปดาห์' : 'รายเดือน'}แล้ว`);
}

/**
 * กรองอันดับห้องเรียน
 */
function filterClassRanking(level) {
    // เปลี่ยนสถานะปุ่ม
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-level="${level}"]`).classList.add('active');
    
    // กรองข้อมูลและอัปเดตแผนภูมิ
    let filteredClasses = executiveData.classPerformance;
    
    if (level !== 'all') {
        filteredClasses = executiveData.classPerformance.filter(cls => {
            const isHigh = cls.class_name.includes('ปวส.');
            return (level === 'high' && isHigh) || (level === 'middle' && !isHigh);
        });
    }
    
    // เรียงลำดับและอัปเดตแผนภูมิ
    const sortedClasses = filteredClasses.sort((a, b) => b.avg_attendance_rate - a.avg_attendance_rate);
    
    if (classRankingChart) {
        classRankingChart.data.labels = sortedClasses.slice(0, 15).map(cls => cls.class_name + ' ' + cls.department_name);
        classRankingChart.data.datasets[0].data = sortedClasses.slice(0, 15).map(cls => cls.avg_attendance_rate);
        classRankingChart.update();
    }
    
    const levelText = level === 'all' ? 'ทั้งหมด' : (level === 'high' ? 'ปวส.' : 'ปวช.');
    showSuccess(`กรองข้อมูลระดับ ${levelText} แล้ว`);
}

/**
 * ค้นหานักเรียน
 */
function handleStudentSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    const tableRows = document.querySelectorAll('.responsive-table tbody tr');
    
    tableRows.forEach(row => {
        const studentName = row.querySelector('.student-info strong')?.textContent.toLowerCase() || '';
        const studentCode = row.querySelector('.student-info small')?.textContent.toLowerCase() || '';
        const className = row.cells[1]?.textContent.toLowerCase() || '';
        
        const isVisible = studentName.includes(searchTerm) || 
                         studentCode.includes(searchTerm) || 
                         className.includes(searchTerm);
        
        row.style.display = isVisible ? '' : 'none';
    });
}

/**
 * ตั้งค่าตารางข้อมูล
 */
function setupDataTables() {
    // ตั้งค่าตารางข้อมูลด้วย DataTables หากต้องการ
    // const table = document.querySelector('.responsive-table');
    // if (table && typeof $ !== 'undefined' && $.fn.DataTable) {
    //     $(table).DataTable({
    //         responsive: true,
    //         language: {
    //             url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
    //         },
    //         pageLength: 10,
    //         order: [[2, 'asc']] // เรียงตามอัตราการเข้าแถว
    //     });
    // }
}

/**
 * ตั้งค่า Auto Refresh
 */
function setupAutoRefresh() {
    if (isAutoRefresh && executiveConfig.refreshInterval > 0) {
        refreshInterval = setInterval(() => {
            refreshDashboard();
        }, executiveConfig.refreshInterval);
    }
}

/**
 * ตรวจสอบว่าเป็นอุปกรณ์มือถือหรือไม่
 */
function isMobileDevice() {
    return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * ปรับแต่งสำหรับมือถือ
 */
function optimizeForMobile() {
    // ลดขนาดแผนภูมิ
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        if (!container.classList.contains('main-trend')) {
            container.style.height = '250px';
        }
    });
    
    // ปรับแต่งตาราง
    const tables = document.querySelectorAll('.responsive-table');
    tables.forEach(table => {
        // เพิ่มการเลื่อนแนวนอน
        table.style.fontSize = '0.8rem';
        const wrapper = document.createElement('div');
        wrapper.style.overflowX = 'auto';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
    
    // ปรับแต่งการแสดงผลสถิติ
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.minHeight = 'auto';
    });
}

/**
 * แสดงการโหลด
 */
function showLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.style.display = 'flex';
    }
}

/**
 * ซ่อนการโหลด
 */
function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.style.display = 'none';
    }
}

/**
 * แสดงข้อความสำเร็จ
 */
function showSuccess(message) {
    // ใช้ SweetAlert2 หากมี หรือใช้ alert ธรรมดา
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: message,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        // สร้าง toast notification เอง
        showToast(message, 'success');
    }
}

/**
 * แสดงข้อความผิดพลาด
 */
function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        showToast(message, 'error');
    }
}

/**
 * สร้าง Toast Notification
 */
function showToast(message, type = 'info') {
    // สร้าง element สำหรับ toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        max-width: 350px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // แสดง toast
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // ซ่อน toast
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

/**
 * แสดงตัวเลือกวันที่แบบกำหนดเอง
 */
function showCustomDatePicker() {
    // สร้าง modal สำหรับเลือกวันที่
    const modal = document.createElement('div');
    modal.className = 'custom-date-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        padding: 25px;
        border-radius: 12px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    `;
    
    modalContent.innerHTML = `
        <h3 style="margin: 0 0 20px 0; color: #1565C0;">เลือกช่วงวันที่</h3>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500;">วันเริ่มต้น:</label>
            <input type="date" id="custom-start-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500;">วันสิ้นสุด:</label>
            <input type="date" id="custom-end-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="cancel-custom-date" style="padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer;">ยกเลิก</button>
            <button type="button" id="apply-custom-date" style="padding: 8px 16px; border: none; background: #1565C0; color: white; border-radius: 6px; cursor: pointer;">ตกลง</button>
        </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    // ตั้งค่าวันที่เริ่มต้น
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('custom-start-date').valueAsDate = firstDay;
    document.getElementById('custom-end-date').valueAsDate = today;
    
    // Event listeners
    document.getElementById('cancel-custom-date').addEventListener('click', () => {
        document.getElementById('period-selector').value = 'month';
        document.body.removeChild(modal);
    });
    
    document.getElementById('apply-custom-date').addEventListener('click', () => {
        const startDate = document.getElementById('custom-start-date').value;
        const endDate = document.getElementById('custom-end-date').value;
        
        if (!startDate || !endDate) {
            showError('กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showError('วันที่เริ่มต้นต้องมาก่อนวันที่สิ้นสุด');
            return;
        }
        
        // ตั้งค่า URL parameters
        const url = new URL(window.location);
        url.searchParams.set('period', 'custom');
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        
        // รีเฟรชหน้า (ในการใช้งานจริง)
        // window.location.href = url.toString();
        
        document.body.removeChild(modal);
        showSuccess(`เปลี่ยนช่วงวันที่เป็น ${formatThaiDate(startDate)} ถึง ${formatThaiDate(endDate)}`);
    });
    
    // ปิด modal เมื่อคลิกพื้นหลัง
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.getElementById('period-selector').value = 'month';
            document.body.removeChild(modal);
        }
    });
}

/**
 * แปลงวันที่เป็นรูปแบบไทย
 */
function formatThaiDate(dateString) {
    const date = new Date(dateString);
    const thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
}

/**
 * ฟังก์ชันสำหรับการ resize หน้าต่าง
 */
window.addEventListener('resize', function() {
    // ปรับขนาดแผนภูมิทั้งหมด
    const charts = [executiveTrendChart, executiveStatusChart, departmentComparisonChart, classRankingChart];
    charts.forEach(chart => {
        if (chart) {
            chart.resize();
        }
    });
    
    // ตรวจสอบการเปลี่ยนแปลงขนาดหน้าจอ
    if (isMobileDevice() && !document.body.classList.contains('mobile-optimized')) {
        optimizeForMobile();
        document.body.classList.add('mobile-optimized');
    }
});

// ฟังก์ชันที่เรียกจากภายนอก
window.refreshDashboard = refreshDashboard;
window.exportReport = exportReport;
window.exportClassPerformanceExcel = exportClassPerformanceExcel;
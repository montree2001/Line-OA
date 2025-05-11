/**
 * reports.js - Interactive functionality for reports dashboard
 * 
 * Part of the system "น้องสัตบรรณ - ดูแลผู้เรียน"
 * วิทยาลัยการอาชีพปราสาท
 */

// Charts references
let attendanceLineChart;
let attendancePieChart;
let currentStudentId;
let currentPeriod = 'week'; // Default period
let riskDataTable;
let classRankDataTable;

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // สร้างกราฟและแผนภูมิ
    initializeCharts();
    
    // ตั้งค่า Event Listeners
    setupEventListeners();
    
    // สร้างปฏิทินรายวัน (สำหรับมุมมองรายวัน)
    createCalendarView();
    
    // เริ่มต้น DataTables
    initializeDataTables();
});

// ฟังก์ชันสร้างกราฟเส้นแสดงอัตราการเข้าแถว
function initializeLineChart() {
    const ctx = document.getElementById('attendanceLineChart').getContext('2d');
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = weeklyTrendsData.map(item => item.date);
    const data = weeklyTrendsData.map(item => item.attendance_rate);
    
    // กำหนดสีสำหรับวันหยุดสุดสัปดาห์
    const pointBackgroundColors = weeklyTrendsData.map(item => 
        item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
    );
    
    const pointBorderColors = weeklyTrendsData.map(item => 
        item.is_weekend ? 'rgba(200, 200, 200, 0.8)' : 'rgb(40, 167, 69)'
    );
    
    attendanceLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: data,
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderColor: 'rgb(40, 167, 69)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: pointBackgroundColors,
                pointBorderColor: pointBorderColors,
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
                    min: Math.max(0, Math.min(...data.filter(val => val > 0)) - 10),
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
                            const dataIndex = context.dataIndex;
                            const isWeekend = weeklyTrendsData[dataIndex]?.is_weekend;
                            
                            if (isWeekend) {
                                return ['วันหยุดสุดสัปดาห์', `อัตราการเข้าแถว: ${context.parsed.y}%`];
                            }
                            return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                        }
                    }
                }
            }
        }
    });
}

// ฟังก์ชันสร้างกราฟวงกลมแสดงสถานะการเข้าแถว
function initializePieChart() {
    const ctx = document.getElementById('attendancePieChart').getContext('2d');
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = attendanceStatusData.map(item => item.status);
    const data = attendanceStatusData.map(item => item.percent);
    const colors = attendanceStatusData.map(item => item.color);
    
    attendancePieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0
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
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed}%`;
                        }
                    }
                }
            },
            cutout: '65%',
            animation: {
                animateRotate: true,
                animateScale: true
            }
        }
    });
}

// ฟังก์ชันสร้างกราฟและแผนภูมิทั้งหมด
function initializeCharts() {
    initializeLineChart();
    initializePieChart();
}

// ฟังก์ชันเริ่มต้นการใช้งาน DataTables
function initializeDataTables() {
    // ตารางนักเรียนที่เสี่ยง
    riskDataTable = $('#risk-students-table').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        columnDefs: [
            { targets: -1, orderable: false }, // คอลัมน์การจัดการไม่สามารถเรียงลำดับได้
        ],
        order: [[3, 'asc']], // เรียงลำดับตามอัตราการเข้าแถวจากน้อยไปมาก
        drawCallback: function() {
            // เพิ่ม event listeners ให้กับปุ่มหลังจากที่ DataTable วาดตารางใหม่
            $('.action-button.view').on('click', function() {
                const studentId = $(this).data('student-id');
                viewStudentDetail(studentId);
            });
            
            $('.action-button.message').on('click', function() {
                const studentId = $(this).data('student-id');
                notifyParent(studentId);
            });
            
            $('.student-link').on('click', function(e) {
                e.preventDefault();
                const studentId = $(this).data('student-id');
                viewStudentDetail(studentId);
            });
        }
    });

    // ตารางอันดับชั้นเรียน
    classRankDataTable = $('#class-rank-table').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        columnDefs: [
            { targets: -1, orderable: false }, // คอลัมน์กราฟไม่สามารถเรียงลำดับได้
        ],
        order: [[4, 'desc']] // เรียงลำดับตามอัตราการเข้าแถวจากมากไปน้อย
    });
    
    // ปรับแต่ง DataTables ให้ตอบสนองกับอุปกรณ์มือถือ
    adjustDataTablesResponsiveness();
}

// ปรับแต่ง DataTables ให้ตอบสนองกับอุปกรณ์มือถือ
function adjustDataTablesResponsiveness() {
    // ปรับความกว้างของตารางให้เหมาะสมกับอุปกรณ์
    $(window).resize(function() {
        if (riskDataTable) riskDataTable.columns.adjust().responsive.recalc();
        if (classRankDataTable) classRankDataTable.columns.adjust().responsive.recalc();
    });
}

// ฟังก์ชันตั้งค่า Event Listeners
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
    
    // ช่องค้นหานักเรียน - ไม่จำเป็นต้องมี Event Listener อีกเนื่องจากใช้ DataTable ที่มีการค้นหาในตัวแล้ว
    
    // ตัวเลือกช่วงเวลา
    const periodSelector = document.getElementById('period-selector');
    if (periodSelector) {
        periodSelector.addEventListener('change', changePeriod);
    }
    
    // ตัวเลือกแผนก
    const departmentSelector = document.getElementById('department-selector');
    if (departmentSelector) {
        departmentSelector.addEventListener('change', changeDepartment);
    }
    
    // ปุ่มดาวน์โหลดรายงาน
    const downloadButton = document.getElementById('downloadReportBtn');
    if (downloadButton) {
        downloadButton.addEventListener('click', downloadReport);
    }
    
    // ปุ่มพิมพ์รายงาน
    const printButton = document.getElementById('printReportBtn');
    if (printButton) {
        printButton.addEventListener('click', printReport);
    }
    
    // ปุ่มแจ้งเตือนทั้งหมด
    const notifyAllButton = document.getElementById('notifyAllBtn');
    if (notifyAllButton) {
        notifyAllButton.addEventListener('click', confirmNotifyAllRiskStudents);
    }
    
    // Event listeners สำหรับปุ่มในตารางจะถูกเพิ่มใน drawCallback ของ DataTable
    
    // ปุ่มปิด Modal รายละเอียดนักเรียน
    const closeStudentModal = document.getElementById('closeStudentModal');
    if (closeStudentModal) {
        closeStudentModal.addEventListener('click', function() {
            document.getElementById('studentDetailModal').style.display = 'none';
        });
    }
    
    // ปุ่มปิด Modal การแจ้งเตือน
    const closeNotificationModal = document.getElementById('closeNotificationModal');
    if (closeNotificationModal) {
        closeNotificationModal.addEventListener('click', function() {
            document.getElementById('notificationModal').style.display = 'none';
        });
    }
    
    // ปุ่มยกเลิกการแจ้งเตือน
    const cancelNotification = document.getElementById('cancelNotification');
    if (cancelNotification) {
        cancelNotification.addEventListener('click', function() {
            document.getElementById('notificationModal').style.display = 'none';
        });
    }
    
    // ปุ่มส่งข้อความแจ้งเตือน
    const sendNotificationBtn = document.getElementById('sendNotification');
    if (sendNotificationBtn) {
        sendNotificationBtn.addEventListener('click', sendNotification);
    }
    
    // เลือกเทมเพลตข้อความ
    const notificationTemplate = document.getElementById('notification-template');
    if (notificationTemplate) {
        notificationTemplate.addEventListener('change', updateNotificationContent);
    }
    
    // ปุ่มปิด Modal เลือกช่วงวันที่
    const closeDateRangeModal = document.getElementById('closeDateRangeModal');
    if (closeDateRangeModal) {
        closeDateRangeModal.addEventListener('click', function() {
            document.getElementById('dateRangeModal').style.display = 'none';
        });
    }
    
    // ปุ่มยกเลิกการเลือกช่วงวันที่
    const cancelDateRange = document.getElementById('cancelDateRange');
    if (cancelDateRange) {
        cancelDateRange.addEventListener('click', function() {
            document.getElementById('dateRangeModal').style.display = 'none';
        });
    }
    
    // ปุ่มตกลงเลือกช่วงวันที่
    const applyDateRangeBtn = document.getElementById('applyDateRange');
    if (applyDateRangeBtn) {
        applyDateRangeBtn.addEventListener('click', applyDateRange);
    }
    
    // ปิด Modal เมื่อคลิกพื้นหลัง
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };
}

// ฟังก์ชันอัปเดตกราฟเส้นตามช่วงเวลาที่เลือก
function updateAttendanceChart(period) {
    if (period === currentPeriod) return;
    currentPeriod = period;
    
    showLoading();
    
    // ในระบบจริง ควรใช้ AJAX เพื่อดึงข้อมูลจากเซิร์ฟเวอร์
    // fetch(`api/reports.php?action=chart_data&period=${period}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // อัปเดตกราฟด้วยข้อมูลที่ได้รับ
    //         updateChartWithData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching chart data:', error);
    //         hideLoading();
    //     });
    
    // จำลองการดึงข้อมูลโดยใช้ setTimeout
    setTimeout(() => {
        let labels = [];
        let data = [];
        let colors = [];
        
        switch (period) {
            case 'week':
                // ใช้ข้อมูลที่มีอยู่แล้ว
                labels = weeklyTrendsData.map(item => item.date);
                data = weeklyTrendsData.map(item => item.attendance_rate);
                colors = weeklyTrendsData.map(item => 
                    item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
                );
                break;
                
            case 'month':
                // สร้างข้อมูลจำลองสำหรับเดือน
                const daysInMonth = 30;
                labels = Array.from({length: daysInMonth}, (_, i) => `${i+1}`);
                data = Array.from({length: daysInMonth}, () => Math.floor(85 + Math.random() * 10));
                colors = Array(daysInMonth).fill('rgb(40, 167, 69)');
                break;
                
            case 'semester':
                // สร้างข้อมูลจำลองสำหรับภาคเรียน
                labels = ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
                data = [94.5, 93.8, 92.5, 93.2, 94.1, 94.8];
                colors = Array(6).fill('rgb(40, 167, 69)');
                break;
        }
        
        // อัปเดตข้อมูลกราฟ
        if (attendanceLineChart) {
            attendanceLineChart.data.labels = labels;
            attendanceLineChart.data.datasets[0].data = data;
            attendanceLineChart.data.datasets[0].pointBackgroundColor = colors;
            attendanceLineChart.data.datasets[0].pointBorderColor = colors;
            
            // อัปเดตสเกล Y เพื่อให้แสดงผลได้สวยงาม
            const minValue = Math.min(...data.filter(val => val > 0));
            attendanceLineChart.options.scales.y.min = Math.max(0, minValue - 10);
            
            attendanceLineChart.update();
        }
        
        hideLoading();
    }, 800);
}

// ฟังก์ชันกรองตารางชั้นเรียนตามระดับ
function filterClassTable(level) {
    if (classRankDataTable) {
        if (level === 'all') {
            classRankDataTable.column(0).search('').draw();
        } else {
            classRankDataTable.column(0).search(`data-level="${level}"`).draw();
        }
    }
}

// ฟังก์ชันเปลี่ยนช่วงเวลาการแสดงผล
function changePeriod() {
    const periodSelector = document.getElementById('period-selector');
    const period = periodSelector.value;
    
    // แสดงหรือซ่อนปฏิทินรายวัน
    const dailyAttendanceCard = document.getElementById('dailyAttendanceCard');
    if (dailyAttendanceCard) {
        dailyAttendanceCard.style.display = period === 'day' ? 'block' : 'none';
    }
    
    // ถ้าเป็นกำหนดเอง ให้แสดง modal เลือกวันที่
    if (period === 'custom') {
        showDateRangeSelector();
        return;
    }
    
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX ไปยังเซิร์ฟเวอร์เพื่อดึงข้อมูลตามช่วงเวลา
    // fetch(`api/reports.php?action=overview&period=${period}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // อัปเดตหน้าเว็บด้วยข้อมูลที่ได้รับ
    //         updateReportWithData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching report data:', error);
    //         hideLoading();
    //     });
    
    setTimeout(() => {
        hideLoading();
        
        // แสดงข้อความว่าเปลี่ยนช่วงเวลาแล้ว
        let periodText = '';
        switch (period) {
            case 'day': periodText = 'วันนี้'; break;
            case 'week': periodText = 'สัปดาห์นี้'; break;
            case 'month': periodText = 'เดือนนี้'; break;
            case 'semester': periodText = `ภาคเรียนที่ ${academicYearData.semester}/${academicYearData.thai_year}`; break;
        }
        
        // แสดงข้อความแจ้งเตือน (ในระบบจริงอาจไม่จำเป็นต้องมี)
        alert(`เปลี่ยนการแสดงผลเป็นช่วง: ${periodText} เรียบร้อยแล้ว`);
    }, 800);
}

// ฟังก์ชันเปลี่ยนแผนกที่แสดง
function changeDepartment() {
    const departmentSelector = document.getElementById('department-selector');
    const departmentId = departmentSelector.value;
    
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX ไปยังเซิร์ฟเวอร์เพื่อดึงข้อมูลตามแผนก
    // fetch(`api/reports.php?action=overview&department_id=${departmentId}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // อัปเดตหน้าเว็บด้วยข้อมูลที่ได้รับ
    //         updateReportWithData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching department data:', error);
    //         hideLoading();
    //     });
    
    setTimeout(() => {
        hideLoading();
        
        const departmentText = departmentSelector.options[departmentSelector.selectedIndex].text;
        alert(`เปลี่ยนการแสดงผลเป็นแผนก: ${departmentText} เรียบร้อยแล้ว`);
    }, 800);
}

// แสดง modal เลือกช่วงวันที่
function showDateRangeSelector() {
    // ตั้งค่าวันที่เริ่มต้นเป็นวันแรกของเดือนปัจจุบัน
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    startDate.valueAsDate = firstDayOfMonth;
    endDate.valueAsDate = today;
    
    // แสดง Modal
    document.getElementById('dateRangeModal').style.display = 'block';
}

// ประมวลผลช่วงวันที่ที่เลือก
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
    document.getElementById('dateRangeModal').style.display = 'none';
    
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX ไปยังเซิร์ฟเวอร์เพื่อดึงข้อมูลตามช่วงวันที่
    // fetch(`api/reports.php?action=custom_period&start_date=${startDate}&end_date=${endDate}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // อัปเดตหน้าเว็บด้วยข้อมูลที่ได้รับ
    //         updateReportWithData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching custom period data:', error);
    //         hideLoading();
    //     });
    
    setTimeout(() => {
        hideLoading();
        
        // แสดงช่วงวันที่ที่เลือก
        const formatDate = (dateStr) => {
            const d = new Date(dateStr);
            return d.toLocaleDateString('th-TH', {year: 'numeric', month: 'long', day: 'numeric'});
        };
        
        alert(`เปลี่ยนการแสดงผลเป็นช่วงวันที่ ${formatDate(startDate)} ถึง ${formatDate(endDate)} เรียบร้อยแล้ว`);
        
        // เปลี่ยนตัวเลือกใน dropdown เป็น 'custom'
        const periodSelector = document.getElementById('period-selector');
        periodSelector.value = 'custom';
    }, 800);
}

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadReport() {
    const periodSelector = document.getElementById('period-selector');
    const departmentSelector = document.getElementById('department-selector');
    
    const period = periodSelector.value;
    const departmentId = departmentSelector.value;
    
    showLoading();
    
    // ในระบบจริง ควรเปิดหน้าต่าง popup หรือส่งคำขอไปยังเซิร์ฟเวอร์
    // window.open(`api/download_report.php?period=${period}&department_id=${departmentId}`, '_blank');
    
    setTimeout(() => {
        hideLoading();
        
        let periodText = '';
        switch (period) {
            case 'day': periodText = 'วันนี้'; break;
            case 'week': periodText = 'สัปดาห์นี้'; break;
            case 'month': periodText = 'เดือนนี้'; break;
            case 'semester': periodText = `ภาคเรียนที่ ${academicYearData.semester}/${academicYearData.thai_year}`; break;
            case 'custom': periodText = 'ช่วงวันที่ที่กำหนด'; break;
        }
        
        const departmentText = departmentSelector.options[departmentSelector.selectedIndex].text;
        
        alert(`เริ่มดาวน์โหลดรายงานสำหรับช่วง: ${periodText} แผนก: ${departmentText}`);
    }, 1000);
}

// ฟังก์ชันพิมพ์รายงาน
function printReport() {
    window.print();
}

// ฟังก์ชันดูรายละเอียดนักเรียน
function viewStudentDetail(studentId) {
    currentStudentId = studentId;
    
    // แสดง modal
    document.getElementById('studentDetailModal').style.display = 'block';
    
    // ตั้งค่า loading state
    document.getElementById('student-detail-content').innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    
    // ในระบบจริง ควรใช้ AJAX เพื่อดึงข้อมูลนักเรียนจากเซิร์ฟเวอร์
    // fetch(`api/reports.php?action=student_details&student_id=${studentId}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         // แสดงข้อมูลที่ได้รับใน modal
    //         displayStudentDetails(data);
    //     })
    //     .catch(error => {
    //         console.error('Error fetching student details:', error);
    //         document.getElementById('student-detail-content').innerHTML = '<div class="error">เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน</div>';
    //     });
    
    // จำลองการดึงข้อมูลโดยใช้ setTimeout
    setTimeout(() => {
        // สมมติว่าได้รับข้อมูลจาก server
        let studentData;
        
        // ค้นหาข้อมูลนักเรียนจาก DOM (ในระบบจริงควรดึงจาก AJAX)
        const studentRow = document.querySelector(`#risk-students-table tr[data-student-id="${studentId}"]`);
        if (studentRow) {
            const nameElement = studentRow.querySelector('.student-detail a');
            const codeElement = studentRow.querySelector('.student-detail p');
            const classElement = studentRow.querySelector('td:nth-child(2)');
            const rateElement = studentRow.querySelector('.attendance-rate');
            const advisorElement = studentRow.querySelector('td:nth-child(3)');
            
            studentData = {
                id: studentId,
                name: nameElement.textContent.trim(),
                code: codeElement.textContent.replace('รหัส: ', '').trim(),
                class: classElement.textContent.trim(),
                advisorName: advisorElement.textContent.trim(),
                attendanceRate: parseFloat(rateElement.textContent),
                attendance: [
                    { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' },
                    { date: '7 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:50', remarks: '-' },
                    { date: '8 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' },
                    { date: '9 พ.ค. 2568', status: 'มาสาย', statusClass: 'warning', time: '08:32', remarks: 'รถติด' },
                    { date: '10 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:40', remarks: '-' },
                    { date: '11 พ.ค. 2568', status: 'ลา', statusClass: 'info', time: '-', remarks: 'ป่วย' },
                    { date: '12 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' }
                ],
                monthlyTrend: {
                    labels: ['มี.ค.', 'เม.ย.', 'พ.ค.'],
                    rates: [60, 65, rateElement ? parseFloat(rateElement.textContent) : 65.8]
                },
                notifications: [
                    { date: '28 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'อ.' + (advisorElement?.textContent.trim() || 'ที่ปรึกษา'), status: 'ส่งสำเร็จ' },
                    { date: '15 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'ฝ่ายกิจการนักเรียน', status: 'ส่งสำเร็จ' }
                ]
            };
        } else {
            // ข้อมูลตัวอย่างหากไม่พบในตาราง
            studentData = {
                id: studentId,
                name: 'นักเรียนรหัส ' + studentId,
                code: '67319010001',
                class: 'ปวช.1/1',
                advisorName: 'อาจารย์ที่ปรึกษา',
                attendanceRate: 65.8,
                attendance: [
                    { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' },
                    { date: '7 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' },
                    { date: '8 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:50', remarks: '-' },
                    { date: '9 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:42', remarks: '-' },
                    { date: '10 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' }
                ],
                monthlyTrend: {
                    labels: ['มี.ค.', 'เม.ย.', 'พ.ค.'],
                    rates: [60, 65, 65.8]
                },
                notifications: [
                    { date: '28 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'อาจารย์ที่ปรึกษา', status: 'ส่งสำเร็จ' },
                    { date: '15 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'ฝ่ายกิจการนักเรียน', status: 'ส่งสำเร็จ' }
                ]
            };
        }
        
        // แสดงข้อมูลนักเรียนใน modal
        displayStudentDetails(studentData);
    }, 800);
}

// ฟังก์ชันแสดงข้อมูลนักเรียนใน modal
function displayStudentDetails(studentData) {
    // อัปเดตชื่อนักเรียนใน modal
    document.getElementById('modal-student-name').textContent = 'ข้อมูลการเข้าแถว - ' + studentData.name;
    
    // กำหนดคลาสสำหรับอัตราการเข้าแถว
    let rateClass = 'text-success';
    let statusText = 'ปกติ';
    if (studentData.attendanceRate < 80 && studentData.attendanceRate >= 70) {
        rateClass = 'text-warning';
        statusText = 'เสี่ยงตกกิจกรรม';
    } else if (studentData.attendanceRate < 70) {
        rateClass = 'text-danger';
        statusText = 'ตกกิจกรรม';
    }
    
    // คำนวณจำนวนวันเข้าแถวและขาดแถว
    const presentDays = studentData.attendance.filter(day => day.status === 'มา' || day.status === 'มาสาย').length;
    const absentDays = studentData.attendance.filter(day => day.status === 'ขาด').length;
    const leaveDays = studentData.attendance.filter(day => day.status === 'ลา').length;
    const totalDays = studentData.attendance.length;
    
    // สร้าง HTML สำหรับแสดงข้อมูล
    let html = `
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar">${studentData.name.charAt(0)}</div>
                <div class="student-profile-info">
                    <h3>${studentData.name}</h3>
                    <p>รหัสนักเรียน: ${studentData.code}</p>
                    <p>ชั้น ${studentData.class}</p>
                    <p>ครูที่ปรึกษา: ${studentData.advisorName}</p>
                    <p>สถานะการเข้าแถว: <span class="${rateClass}">${statusText} (${studentData.attendanceRate}%)</span></p>
                </div>
            </div>
            
            <div class="student-attendance-summary">
                <h4>สรุปการเข้าแถวประจำเดือน${academicYearData.current_month} ${academicYearData.current_year}</h4>
                <div class="row">
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">${presentDays}</div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">${absentDays}</div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">${totalDays}</div>
                            <div class="attendance-stat-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-history">
                <h4>ประวัติการเข้าแถวรายวัน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                                <th>หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    // เพิ่มข้อมูลประวัติการเข้าแถว
    studentData.attendance.forEach(day => {
        html += `
            <tr>
                <td>${day.date}</td>
                <td><span class="status-badge ${day.statusClass}">${day.status}</span></td>
                <td>${day.time}</td>
                <td>${day.remarks}</td>
            </tr>`;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="attendance-chart">
                <h4>แนวโน้มการเข้าแถวรายเดือน</h4>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="studentMonthlyChart"></canvas>
                </div>
            </div>
            
            <div class="notification-history">
                <h4>ประวัติการแจ้งเตือนผู้ปกครอง</h4>
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
                        <tbody>`;
    
    // เพิ่มข้อมูลประวัติการแจ้งเตือน
    studentData.notifications.forEach(notification => {
        html += `
            <tr>
                <td>${notification.date}</td>
                <td>${notification.type}</td>
                <td>${notification.sender}</td>
                <td><span class="status-badge success">${notification.status}</span></td>
            </tr>`;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-actions" style="margin-top: 20px;">
                <button class="btn-cancel" onclick="document.getElementById('studentDetailModal').style.display='none'">ปิด</button>
                <button class="btn-send" onclick="notifyParent(${studentId})">
                    <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                </button>
                <button class="btn-primary" onclick="window.location.href='student_details.php?id=${studentId}'">
                    <span class="material-icons">visibility</span> ดูข้อมูลเพิ่มเติม
                </button>
            </div>
        </div>
    `;
    
    // อัปเดตเนื้อหาใน modal
    document.getElementById('student-detail-content').innerHTML = html;
    
    // สร้างกราฟแนวโน้มรายเดือน
    createStudentMonthlyChart(studentData.monthlyTrend);
}

// ฟังก์ชันสร้างกราฟแนวโน้มการเข้าแถวรายเดือนของนักเรียน
function createStudentMonthlyChart(trendData) {
    const ctx = document.getElementById('studentMonthlyChart');
    if (!ctx) return;
    
    const chartColor = trendData.rates[trendData.rates.length - 1] >= 70 ? 
        (trendData.rates[trendData.rates.length - 1] >= 80 ? '#28a745' : '#ffc107') : '#dc3545';
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: trendData.rates,
                backgroundColor: `${chartColor}20`,
                borderColor: chartColor,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: chartColor,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...trendData.rates) - 10),
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
                            return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                        }
                    }
                }
            }
        }
    });
}

// ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครอง
function notifyParent(studentId) {
    currentStudentId = studentId;
    
    // แสดง modal แจ้งเตือน
    document.getElementById('notificationModal').style.display = 'block';
    
    // ตั้งค่า template เริ่มต้น
    updateNotificationContent();
}

// ฟังก์ชันอัปเดตเนื้อหาข้อความแจ้งเตือน
function updateNotificationContent() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    
    const template = templateSelect.value;
    
    // ข้อมูลนักเรียน (ในระบบจริงควรดึงจาก AJAX)
    let studentName = "นักเรียน";
    let className = "";
    let advisorName = "";
    
    const studentRow = document.querySelector(`#risk-students-table tr[data-student-id="${currentStudentId}"]`);
    if (studentRow) {
        studentName = studentRow.querySelector('.student-detail a').textContent.trim();
        className = studentRow.querySelector('td:nth-child(2)').textContent.trim();
        advisorName = studentRow.querySelector('td:nth-child(3)').textContent.trim();
    }
    
    // ตัวอย่างเทมเพลตข้อความ
    switch (template) {
        case 'risk_alert':
            contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nทางวิทยาลัยขอแจ้งว่า ${studentName} นักเรียนชั้น ${className} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 70% ซึ่งต่ำกว่าเกณฑ์ที่กำหนด (80%)\n\nกรุณาติดต่อครูที่ปรึกษา ${advisorName} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'absence_alert':
            contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nทางวิทยาลัยขอแจ้งว่า ${studentName} นักเรียนชั้น ${className} ไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้\n\nกรุณาติดต่อครูที่ปรึกษา ${advisorName} หากมีข้อสงสัย\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'monthly_report':
            contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nรายงานสรุปการเข้าแถวประจำเดือน${academicYearData.current_month} ${academicYearData.current_year}\n\nจำนวนวันเข้าแถว: 15 วัน\nจำนวนวันขาด: 5 วัน\nอัตราการเข้าแถว: 75%\nสถานะ: เสี่ยงไม่ผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา ${advisorName} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            contentField.value = '';
            break;
    }
}

// ฟังก์ชันส่งข้อความแจ้งเตือน
function sendNotification() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    
    const template = templateSelect.value;
    const content = contentField.value;
    
    if (!content.trim()) {
        alert('กรุณากรอกข้อความแจ้งเตือน');
        return;
    }
    
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX ไปยังเซิร์ฟเวอร์
    // fetch('api/reports.php?action=send_notification', {
    //     method: 'POST',
    //     body: JSON.stringify({
    //         student_id: currentStudentId,
    //         template: template,
    //         message: content
    //     }),
    //     headers: {
    //         'Content-Type': 'application/json'
    //     }
    // })
    // .then(response => response.json())
    // .then(data => {
    //     hideLoading();
    //     // ปิด modal
    //     document.getElementById('notificationModal').style.display = 'none';
    //     // แสดงข้อความสำเร็จ
    //     alert(`ส่งข้อความแจ้งเตือนสำเร็จ!`);
    // })
    // .catch(error => {
    //     console.error('Error sending notification:', error);
    //     hideLoading();
    //     alert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
    // });
    
    setTimeout(() => {
        hideLoading();
        
        // ปิด modal
        document.getElementById('notificationModal').style.display = 'none';
        
        // แสดงข้อความสำเร็จ
        alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองนักเรียนรหัส ${currentStudentId} เรียบร้อยแล้ว`);
    }, 800);
}

// ฟังก์ชันยืนยันการส่งแจ้งเตือนไปยังนักเรียนทั้งหมดที่เสี่ยง
function confirmNotifyAllRiskStudents() {
    if (confirm('คุณต้องการส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดหรือไม่?')) {
        notifyAllRiskStudents();
    }
}

// ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมด
function notifyAllRiskStudents() {
    showLoading();
    
    // ในระบบจริง ควรส่ง AJAX ไปยังเซิร์ฟเวอร์
    // fetch('api/reports.php?action=notify_all_risk_students', {
    //     method: 'POST'
    // })
    // .then(response => response.json())
    // .then(data => {
    //     hideLoading();
    //     // แสดงข้อความสำเร็จ
    //     alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดแล้ว (${data.sent_count} คน)`);
    // })
    // .catch(error => {
    //     console.error('Error sending notifications:', error);
    //     hideLoading();
    //     alert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
    // });
    
    setTimeout(() => {
        hideLoading();
        
        // แสดงข้อความสำเร็จ
        alert('ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดเรียบร้อยแล้ว');
    }, 1200);
}

// ฟังก์ชันสร้างปฏิทินรายวัน
function createCalendarView() {
    const calendarView = document.getElementById('calendarView');
    if (!calendarView) return;
    
    // ล้างปฏิทินเดิม
    calendarView.innerHTML = '';
    
    // สร้างวันที่จำลอง
    const currentDate = new Date();
    const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
    
    for (let i = 1; i <= daysInMonth; i++) {
        const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);
        const isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6;
        const isToday = i === currentDate.getDate();
        
        // สร้างข้อมูลจำลอง
        let attendanceRate = '-';
        if (!isWeekend) {
            if (i <= currentDate.getDate()) {
                attendanceRate = Math.floor(90 + Math.random() * 8) + '%';
            }
        }
        
        // สร้าง DOM element
        const dayElement = document.createElement('div');
        dayElement.className = `calendar-day${isWeekend ? ' weekend' : ''}${isToday ? ' today' : ''}`;
        
        dayElement.innerHTML = `
            <div class="calendar-date">${i}</div>
            <div class="calendar-stats">${attendanceRate}</div>
        `;
        
        calendarView.appendChild(dayElement);
    }
}

// ฟังก์ชันแสดง loading
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// ฟังก์ชันซ่อน loading
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}
/**
 * reports.js - จัดการ Interactive ของหน้าแดชบอร์ดรายงาน
 * ระบบน้องสัตบรรณ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตัวแปรกราฟ
let attendanceLineChart;
let attendancePieChart;
let studentMonthlyChart;
let currentStudentId;
let currentPeriod = 'week'; // ค่าเริ่มต้น
let currentDepartmentId = 'all'; // ค่าเริ่มต้น

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // สร้างกราฟและแผนภูมิ
    initializeCharts();
    
    // ตั้งค่า Event Listeners
    setupEventListeners();
    
    // สร้างปฏิทินรายวัน (สำหรับมุมมองรายวัน)
    createCalendarView();
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

// ฟังก์ชันสร้างกราฟวงกลมแสดงสาเหตุการขาดแถว
function initializePieChart() {
    const ctx = document.getElementById('attendancePieChart').getContext('2d');
    
    // เตรียมข้อมูลสำหรับกราฟ
    const labels = absenceReasonsData.map(item => item.reason);
    const data = absenceReasonsData.map(item => item.percent);
    const colors = absenceReasonsData.map(item => item.color);
    
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
    
    // ปุ่มดูรายละเอียดนักเรียน
    document.querySelectorAll('.action-button.view, .student-link').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-student-id');
            viewStudentDetail(studentId);
        });
    });
    
    // ปุ่มส่งข้อความแจ้งเตือน
    document.querySelectorAll('.action-button.message').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            notifyParent(studentId);
        });
    });
    
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
    
    // ดึงข้อมูลจาก API
    fetch(`api/reports.php?action=weekly_trends&period=${period}&department_id=${currentDepartmentId}`)
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                let data = response.data;
                
                // อัปเดตกราฟ
                if (attendanceLineChart) {
                    const labels = data.map(item => item.date);
                    const values = data.map(item => item.attendance_rate);
                    const colors = data.map(item => 
                        item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
                    );
                    
                    attendanceLineChart.data.labels = labels;
                    attendanceLineChart.data.datasets[0].data = values;
                    attendanceLineChart.data.datasets[0].pointBackgroundColor = colors;
                    attendanceLineChart.data.datasets[0].pointBorderColor = colors;
                    
                    // อัปเดตสเกล Y เพื่อให้แสดงผลได้สวยงาม
                    const minValue = Math.min(...values.filter(val => val > 0));
                    attendanceLineChart.options.scales.y.min = Math.max(0, minValue - 10);
                    
                    attendanceLineChart.update();
                }
            } else {
                console.error("เกิดข้อผิดพลาด:", response.error);
            }
            
            hideLoading();
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
            hideLoading();
        });
}

// ฟังก์ชันกรองตารางชั้นเรียนตามระดับ
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

// ฟังก์ชันกรองตารางนักเรียนจากการค้นหา
function filterStudentTable(searchText) {
    const rows = document.querySelectorAll('#risk-students-table tbody tr');
    const searchLower = searchText.toLowerCase();
    
    rows.forEach(row => {
        // ข้ามแถวที่เป็นข้อความ "ไม่พบข้อมูล"
        if (!row.getAttribute('data-student-id')) return;
        
        const studentName = row.querySelector('.student-detail a')?.textContent.toLowerCase() || '';
        const studentCode = row.querySelector('.student-detail p')?.textContent.toLowerCase() || '';
        
        if (studentName.includes(searchLower) || studentCode.includes(searchLower) || searchText === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
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
    
    // ดึงข้อมูลตามช่วงเวลาที่เลือก
    Promise.all([
        fetch(`api/reports.php?action=overview&period=${period}&department_id=${currentDepartmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=department_stats&period=${period}`).then(res => res.json()),
        fetch(`api/reports.php?action=class_ranking&period=${period}&department_id=${currentDepartmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=absence_reasons&period=${period}&department_id=${currentDepartmentId}`).then(res => res.json())
    ])
    .then(([overviewData, departmentData, classData, absenceData]) => {
        // อัปเดตข้อมูลบนหน้าเว็บตามข้อมูลที่ได้รับ
        if (overviewData.success) {
            updateOverviewStats(overviewData.data);
        }
        
        if (departmentData.success) {
            updateDepartmentStats(departmentData.data);
        }
        
        if (classData.success) {
            updateClassRanking(classData.data);
        }
        
        if (absenceData.success) {
            updateAbsenceReasons(absenceData.data);
        }
        
        // อัปเดตกราฟแนวโน้ม
        updateAttendanceChart(period);
        
        hideLoading();
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาลองใหม่อีกครั้ง");
    });
}

// ฟังก์ชันเปลี่ยนแผนกที่แสดง
function changeDepartment() {
    const departmentSelector = document.getElementById('department-selector');
    const departmentId = departmentSelector.value;
    currentDepartmentId = departmentId;
    
    showLoading();
    
    // ดึงช่วงเวลาปัจจุบัน
    const periodSelector = document.getElementById('period-selector');
    const period = periodSelector.value;
    
    // ดึงข้อมูลตามแผนกที่เลือก
    Promise.all([
        fetch(`api/reports.php?action=overview&period=${period}&department_id=${departmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=class_ranking&period=${period}&department_id=${departmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=risk_students&department_id=${departmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=weekly_trends&period=${period}&department_id=${departmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=absence_reasons&department_id=${departmentId}`).then(res => res.json())
    ])
    .then(([overviewData, classData, studentsData, trendsData, absenceData]) => {
        // อัปเดตข้อมูลบนหน้าเว็บตามข้อมูลที่ได้รับ
        if (overviewData.success) {
            updateOverviewStats(overviewData.data);
        }
        
        if (classData.success) {
            updateClassRanking(classData.data);
        }
        
        if (studentsData.success) {
            updateRiskStudents(studentsData.data);
        }
        
        if (trendsData.success) {
            updateAttendanceTrends(trendsData.data);
        }
        
        if (absenceData.success) {
            updateAbsenceReasons(absenceData.data);
        }
        
        hideLoading();
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาลองใหม่อีกครั้ง");
    });
}

// ฟังก์ชันอัปเดตข้อมูลภาพรวม
function updateOverviewStats(data) {
    // อัปเดตค่าสถิติภาพรวม
    document.querySelector('.stats-grid .stat-card.blue .stat-value').textContent = 
        new Intl.NumberFormat('th-TH').format(data.total_students);
    
    document.querySelector('.stats-grid .stat-card.green .stat-value').textContent = 
        data.avg_attendance_rate + '%';
    
    document.querySelector('.stats-grid .stat-card.red .stat-value').textContent = 
        data.failed_students;
    
    document.querySelector('.stats-grid .stat-card.yellow .stat-value').textContent = 
        data.risk_students;
    
    // อัปเดตการเปลี่ยนแปลง
    const changeElem = document.querySelector('.stats-grid .stat-card.green .stat-change');
    if (data.rate_change >= 0) {
        changeElem.classList.remove('negative');
        changeElem.classList.add('positive');
        changeElem.innerHTML = `<span class="material-icons">arrow_upward</span> เพิ่มขึ้น ${Math.abs(data.rate_change)}%`;
    } else {
        changeElem.classList.remove('positive');
        changeElem.classList.add('negative');
        changeElem.innerHTML = `<span class="material-icons">arrow_downward</span> ลดลง ${Math.abs(data.rate_change)}%`;
    }
}

// ฟังก์ชันอัปเดตข้อมูลแผนกวิชา
function updateDepartmentStats(data) {
    // ดึงพื้นที่แสดงแผนกวิชา
    const departmentContainer = document.getElementById('departmentStats');
    
    // ล้างข้อมูลเดิม
    departmentContainer.innerHTML = '';
    
    // ถ้าไม่มีข้อมูล
    if (data.length === 0) {
        departmentContainer.innerHTML = '<div class="empty-data-message">ไม่พบข้อมูลแผนกวิชา</div>';
        return;
    }
    
    // สร้างการ์ดใหม่สำหรับแต่ละแผนก
    data.forEach(dept => {
        const card = document.createElement('div');
        card.className = 'department-card';
        
        card.innerHTML = `
            <div class="department-name">
                <span>${dept.department_name}</span>
                <span class="attendance-rate ${dept.rate_class}">${dept.attendance_rate}%</span>
            </div>
            <div class="department-stats-row">
                <div class="department-stat">
                    <div class="department-stat-label">นักเรียน</div>
                    <div class="department-stat-value">${dept.student_count}</div>
                </div>
                <div class="department-stat">
                    <div class="department-stat-label">เข้าแถว</div>
                    <div class="department-stat-value">${dept.total_attendance}</div>
                </div>
                <div class="department-stat">
                    <div class="department-stat-label">เสี่ยง</div>
                    <div class="department-stat-value">${dept.risk_count}</div>
                </div>
            </div>
            <div class="department-progress">
                <div class="progress-bar">
                    <div class="progress-fill ${dept.rate_class}" style="width: ${dept.attendance_rate}%;"></div>
                </div>
            </div>
        `;
        
        departmentContainer.appendChild(card);
    });
}

// ฟังก์ชันอัปเดตข้อมูลนักเรียนที่มีความเสี่ยง
function updateRiskStudents(data) {
    // ดึงตารางนักเรียนเสี่ยง
    const tableBody = document.querySelector('#risk-students-table tbody');
    
    // ล้างข้อมูลเดิม
    tableBody.innerHTML = '';
    
    // ถ้าไม่มีข้อมูล
    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียนที่มีความเสี่ยง</td></tr>';
        return;
    }
    
    // สร้างแถวใหม่สำหรับแต่ละนักเรียน
    data.forEach(student => {
        const row = document.createElement('tr');
        row.setAttribute('data-student-id', student.student_id);
        
        row.innerHTML = `
            <td>
                <div class="student-name">
                    <div class="student-avatar">${student.initial}</div>
                    <div class="student-detail">
                        <a href="#" class="student-link" data-student-id="${student.student_id}">
                            ${student.title} ${student.first_name} ${student.last_name}
                        </a>
                        <p>รหัส: ${student.student_code}</p>
                    </div>
                </div>
            </td>
            <td>${student.class_name}</td>
            <td>${student.advisor_name || 'ไม่ระบุ'}</td>
            <td><span class="attendance-rate ${student.status}">${student.attendance_rate}%</span></td>
            <td><span class="status-badge ${student.status}">${student.status_text}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-button view" data-student-id="${student.student_id}">
                        <span class="material-icons">visibility</span>
                    </button>
                    <button class="action-button message" data-student-id="${student.student_id}">
                        <span class="material-icons">message</span>
                    </button>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
        
        // เพิ่ม event listener สำหรับปุ่มใหม่
        row.querySelector('.action-button.view').addEventListener('click', function() {
            viewStudentDetail(student.student_id);
        });
        
        row.querySelector('.action-button.message').addEventListener('click', function() {
            notifyParent(student.student_id);
        });
        
        row.querySelector('.student-link').addEventListener('click', function(e) {
            e.preventDefault();
            viewStudentDetail(student.student_id);
        });
    });
}

// ฟังก์ชันอัปเดตข้อมูลอันดับห้องเรียน
function updateClassRanking(data) {
    // ดึงตารางอันดับห้องเรียน
    const tableBody = document.querySelector('.class-rank-table tbody');
    
    // ล้างข้อมูลเดิม
    tableBody.innerHTML = '';
    
    // ถ้าไม่มีข้อมูล
    if (data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลชั้นเรียน</td></tr>';
        return;
    }
    
    // สร้างแถวใหม่สำหรับแต่ละห้องเรียน
    data.forEach(classItem => {
        const row = document.createElement('tr');
        row.setAttribute('data-class-id', classItem.class_id);
        row.setAttribute('data-level', classItem.level_group);
        
        row.innerHTML = `
            <td>${classItem.class_name}</td>
            <td>${classItem.advisor_name || 'ไม่ระบุ'}</td>
            <td>${classItem.student_count}</td>
            <td>${classItem.present_count}</td>
            <td><span class="attendance-rate ${classItem.rate_class}">${classItem.attendance_rate}%</span></td>
            <td>
                <div class="progress-bar">
                    <div class="progress-fill ${classItem.bar_class}" style="width: ${classItem.attendance_rate}%;"></div>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

// ฟังก์ชันอัปเดตข้อมูลแนวโน้มการเข้าแถว
function updateAttendanceTrends(data) {
    // อัปเดตกราฟแนวโน้ม
    if (attendanceLineChart) {
        const labels = data.map(item => item.date);
        const values = data.map(item => item.attendance_rate);
        const colors = data.map(item => 
            item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
        );
        
        attendanceLineChart.data.labels = labels;
        attendanceLineChart.data.datasets[0].data = values;
        attendanceLineChart.data.datasets[0].pointBackgroundColor = colors;
        attendanceLineChart.data.datasets[0].pointBorderColor = colors;
        
        // อัปเดตสเกล Y เพื่อให้แสดงผลได้สวยงาม
        const minValue = Math.min(...values.filter(val => val > 0));
        attendanceLineChart.options.scales.y.min = Math.max(0, minValue - 10);
        
        attendanceLineChart.update();
    }
}

// ฟังก์ชันอัปเดตข้อมูลสาเหตุการขาดแถว
function updateAbsenceReasons(data) {
    // อัปเดตกราฟวงกลม
    if (attendancePieChart) {
        const labels = data.map(item => item.reason);
        const values = data.map(item => item.percent);
        const colors = data.map(item => item.color);
        
        attendancePieChart.data.labels = labels;
        attendancePieChart.data.datasets[0].data = values;
        attendancePieChart.data.datasets[0].backgroundColor = colors;
        
        attendancePieChart.update();
    }
    
    // อัปเดตคำอธิบายกราฟ
    const pieLegend = document.querySelector('.pie-legend');
    pieLegend.innerHTML = '';
    
    data.forEach(reason => {
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <div class="legend-color" style="background-color: ${reason.color}"></div>
            <span>${reason.reason} (${reason.percent}%)</span>
        `;
        
        pieLegend.appendChild(legendItem);
    });
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
    
    // ดึงข้อมูลตามช่วงวันที่ที่กำหนด
    Promise.all([
        fetch(`api/reports.php?action=overview&period=custom&start_date=${startDate}&end_date=${endDate}&department_id=${currentDepartmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=department_stats&period=custom&start_date=${startDate}&end_date=${endDate}`).then(res => res.json()),
        fetch(`api/reports.php?action=class_ranking&period=custom&start_date=${startDate}&end_date=${endDate}&department_id=${currentDepartmentId}`).then(res => res.json()),
        fetch(`api/reports.php?action=absence_reasons&period=custom&start_date=${startDate}&end_date=${endDate}&department_id=${currentDepartmentId}`).then(res => res.json())
    ])
    .then(([overviewData, departmentData, classData, absenceData]) => {
        // อัปเดตข้อมูลบนหน้าเว็บตามข้อมูลที่ได้รับ
        if (overviewData.success) {
            updateOverviewStats(overviewData.data);
        }
        
        if (departmentData.success) {
            updateDepartmentStats(departmentData.data);
        }
        
        if (classData.success) {
            updateClassRanking(classData.data);
        }
        
        if (absenceData.success) {
            updateAbsenceReasons(absenceData.data);
        }
        
        // อัปเดตช่วงเวลาใน dropdown
        const periodSelector = document.getElementById('period-selector');
        periodSelector.value = 'custom';
        
        hideLoading();
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการดึงข้อมูล:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาลองใหม่อีกครั้ง");
    });
}

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadReport() {
    const periodSelector = document.getElementById('period-selector');
    const departmentSelector = document.getElementById('department-selector');
    
    const period = periodSelector.value;
    const departmentId = departmentSelector.value;
    
    showLoading();
    
    // สร้างข้อมูลสำหรับการส่งออก
    const exportData = {
        format: 'excel',
        period: period,
        department_id: departmentId
    };
    
    // ถ้าเป็นช่วงวันที่ที่กำหนดเอง ให้เพิ่มข้อมูลวันที่
    if (period === 'custom') {
        exportData.start_date = document.getElementById('start-date').value;
        exportData.end_date = document.getElementById('end-date').value;
    }
    
    // ส่งคำขอไปยัง API
    fetch('api/reports.php?action=export_report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(exportData)
    })
    .then(response => response.json())
    .then(response => {
        hideLoading();
        
        if (response.success) {
            // เปิดหน้าต่างดาวน์โหลด
            window.location.href = response.data.download_url;
        } else {
            alert("เกิดข้อผิดพลาดในการสร้างรายงาน: " + response.error);
        }
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการดาวน์โหลดรายงาน:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการดาวน์โหลดรายงาน กรุณาลองใหม่อีกครั้ง");
    });
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
    
    // ดึงข้อมูลนักเรียนผ่าน API
    fetch(`api/reports.php?action=student_details&student_id=${studentId}`)
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                const data = response.data;
                const student = data.student;
                
                // กำหนดคลาสสำหรับอัตราการเข้าแถว
                let rateClass = 'text-success';
                let statusText = 'ปกติ';
                if (student.attendance_rate < 80 && student.attendance_rate >= 70) {
                    rateClass = 'text-warning';
                    statusText = 'เสี่ยงตกกิจกรรม';
                } else if (student.attendance_rate < 70) {
                    rateClass = 'text-danger';
                    statusText = 'ตกกิจกรรม';
                }
                
                // คำนวณจำนวนวันเข้าแถวและขาดแถว
                const presentDays = student.total_attendance_days;
                const absentDays = student.total_absence_days;
                const totalDays = presentDays + absentDays;
                
                // อัปเดตชื่อนักเรียนใน modal
                document.getElementById('modal-student-name').textContent = 'ข้อมูลการเข้าแถว - ' + student.title + ' ' + student.first_name + ' ' + student.last_name;
                
                // สร้าง HTML สำหรับแสดงข้อมูล
                let html = `
                    <div class="student-profile">
                        <div class="student-profile-header">
                            <div class="student-profile-avatar">${student.first_name.charAt(0)}</div>
                            <div class="student-profile-info">
                                <h3>${student.title} ${student.first_name} ${student.last_name}</h3>
                                <p>รหัสนักเรียน: ${student.student_code}</p>
                                <p>ชั้น ${student.class_name}</p>
                                <p>แผนกวิชา: ${student.department_name}</p>
                                <p>ครูที่ปรึกษา: ${student.advisor_name || 'ไม่ระบุ'}</p>
                                <p>สถานะการเข้าแถว: <span class="${rateClass}">${statusText} (${student.attendance_rate}%)</span></p>
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
                                            <th>วิธีเช็คชื่อ</th>
                                            <th>หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                
                // เพิ่มข้อมูลประวัติการเข้าแถว
                if (data.attendanceHistory.length > 0) {
                    data.attendanceHistory.forEach(day => {
                        html += `
                            <tr>
                                <td>${day.date}</td>
                                <td><span class="status-badge ${day.statusClass}">${day.status}</span></td>
                                <td>${day.time}</td>
                                <td>${day.checkMethod || '-'}</td>
                                <td>${day.remark}</td>
                            </tr>`;
                    });
                } else {
                    html += `<tr><td colspan="5" class="text-center">ไม่พบประวัติการเข้าแถว</td></tr>`;
                }
                
                html += `
                                </tbody>
                            </table>
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
                if (data.notificationHistory.length > 0) {
                    data.notificationHistory.forEach(notification => {
                        html += `
                            <tr>
                                <td>${notification.date}</td>
                                <td>${notification.type}</td>
                                <td>${notification.sender}</td>
                                <td><span class="status-badge ${notification.statusClass}">${notification.status}</span></td>
                            </tr>`;
                    });
                } else {
                    html += `<tr><td colspan="4" class="text-center">ไม่พบประวัติการแจ้งเตือน</td></tr>`;
                }
                
                html += `
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn-cancel" onclick="document.getElementById('studentDetailModal').style.display='none'">ปิด</button>
                            <button class="btn-send" onclick="notifyParent(${studentId})">
                                <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                            </button>
                        </div>
                    </div>
                `;
                
                // อัปเดตเนื้อหาใน modal
                document.getElementById('student-detail-content').innerHTML = html;
                
                // สร้างกราฟแนวโน้มรายเดือน
                createStudentMonthlyChart(data.monthlyTrend);
            } else {
                document.getElementById('student-detail-content').innerHTML = 
                    `<div class="alert alert-danger">เกิดข้อผิดพลาด: ${response.error}</div>`;
            }
        })
        .catch(error => {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน:", error);
            document.getElementById('student-detail-content').innerHTML = 
                '<div class="alert alert-danger">เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาลองใหม่อีกครั้ง</div>';
        });
}

// ฟังก์ชันสร้างกราฟแนวโน้มการเข้าแถวรายเดือนของนักเรียน
function createStudentMonthlyChart(trendData) {
    const ctx = document.getElementById('studentMonthlyChart');
    if (!ctx) return;
    
    const chartColor = trendData.rates[trendData.rates.length - 1] >= 70 ? 
        (trendData.rates[trendData.rates.length - 1] >= 80 ? '#28a745' : '#ffc107') : '#dc3545';
    
    if (studentMonthlyChart) {
        studentMonthlyChart.destroy();
    }
    
    studentMonthlyChart = new Chart(ctx, {
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
    
    const templateId = templateSelect.value;
    const content = contentField.value;
    
    if (!content.trim()) {
        alert('กรุณากรอกข้อความแจ้งเตือน');
        return;
    }
    
    showLoading();
    
    // ส่งคำขอไปยัง API
    fetch('api/reports.php?action=send_notification', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            student_ids: [currentStudentId],
            template_id: templateId === 'custom' ? null : templateId,
            message: content,
            type: 'attendance_alert'
        })
    })
    .then(response => response.json())
    .then(response => {
        hideLoading();
        
        // ปิด modal
        document.getElementById('notificationModal').style.display = 'none';
        
        if (response.success) {
            alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว`);
        } else {
            alert(`เกิดข้อผิดพลาดในการส่งข้อความ: ${response.error}`);
        }
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการส่งข้อความ:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการส่งข้อความ กรุณาลองใหม่อีกครั้ง");
    });
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
    
    // กำหนดพารามิเตอร์
    const departmentId = document.getElementById('department-selector').value;
    
    // ส่งคำขอไปยัง API
    fetch('api/reports.php?action=send_notification', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            department_id: departmentId === 'all' ? null : departmentId,
            template_id: 'risk_alert',
            only_risk: true,
            type: 'attendance_alert'
        })
    })
    .then(response => response.json())
    .then(response => {
        hideLoading();
        
        if (response.success) {
            alert(response.message);
        } else {
            alert(`เกิดข้อผิดพลาดในการส่งข้อความ: ${response.error}`);
        }
    })
    .catch(error => {
        console.error("เกิดข้อผิดพลาดในการส่งข้อความ:", error);
        hideLoading();
        alert("เกิดข้อผิดพลาดในการส่งข้อความ กรุณาลองใหม่อีกครั้ง");
    });
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
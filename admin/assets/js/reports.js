/**
 * reports.js - Enhanced JavaScript functionality for reports page
 */

// Main reporting manager object
const ReportManager = {
    // Charts references
    charts: {
        yearlyTrend: null,
        absenceReasons: null,
        classComparison: null,
        studentMonthly: null
    },
    
    // Current filters state
    filters: {
        reportType: 'monthly',
        department: '',
        period: 'current',
        classLevel: '',
        classRoom: '',
        startDate: '',
        endDate: '',
        studentSearch: ''
    },
    
    // Initialize the reporting dashboard
    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.setupResponsiveLayout();
    },
    
    // Set up event listeners for interactive elements
    setupEventListeners() {
        // Filter controls
        document.getElementById('reportType')?.addEventListener('change', this.handleReportTypeChange.bind(this));
        document.getElementById('reportPeriod')?.addEventListener('change', this.handlePeriodChange.bind(this));
        document.getElementById('departmentFilter')?.addEventListener('change', this.handleDepartmentChange.bind(this));
        document.getElementById('classLevel')?.addEventListener('change', this.handleClassLevelChange.bind(this));
        
        // Detail buttons for students
        document.querySelectorAll('.table-action-btn.primary').forEach(btn => {
            btn.addEventListener('click', e => {
                const studentId = e.currentTarget.getAttribute('data-student-id') || 
                                 e.currentTarget.closest('tr')?.getAttribute('data-student-id');
                if (studentId) {
                    this.viewStudentDetails(parseInt(studentId));
                }
            });
        });
        
        // Notification buttons
        document.querySelectorAll('.table-action-btn.success').forEach(btn => {
            btn.addEventListener('click', e => {
                const studentId = e.currentTarget.getAttribute('data-student-id') || 
                                 e.currentTarget.closest('tr')?.getAttribute('data-student-id');
                if (studentId) {
                    this.sendNotificationToParent(parseInt(studentId));
                }
            });
        });
        
        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', e => {
                const modalId = e.currentTarget.closest('.modal').id;
                this.closeModal(modalId);
            });
        });
        
        // Generate report button
        document.querySelector('.filter-button')?.addEventListener('click', this.generateReport.bind(this));
        
        // Chart action buttons
        document.querySelector('[onclick="refreshYearlyChart()"]')?.addEventListener('click', this.refreshYearlyChart.bind(this));
        document.querySelector('[onclick="downloadYearlyChart()"]')?.addEventListener('click', this.downloadYearlyChart.bind(this));
        
        // Handle window resize events
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // Close modal when clicking outside
        window.addEventListener('click', e => {
            document.querySelectorAll('.modal').forEach(modal => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    },
    
    // Initialize all charts
    initializeCharts() {
        this.createYearlyTrendChart();
        this.createAbsenceReasonsPieChart();
        this.createClassComparisonChart();
    },
    
    // Set up responsive layout
    setupResponsiveLayout() {
        this.handleResize();
        
        // Add mobile-specific classes and functionality
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.chart-card').forEach(card => {
                card.classList.add('mobile-view');
            });
        }
    },
    
    // Handle window resize events
    handleResize() {
        // Adjust chart sizes based on window size
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            if (window.innerWidth <= 480) {
                container.style.height = '250px';
            } else {
                container.style.height = '300px';
            }
        });
        
        // Update charts to reflect new container sizes
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                chart.resize();
            }
        });
    },
    
    // Handle report type change
    handleReportTypeChange(e) {
        const reportType = e.target.value;
        this.filters.reportType = reportType;
        
        // Show/hide student filter
        const studentFilter = document.querySelectorAll('.student-filter');
        if (reportType === 'student') {
            studentFilter.forEach(filter => filter.style.display = 'block');
        } else {
            studentFilter.forEach(filter => filter.style.display = 'none');
        }
        
        // Modify other filters based on report type
        if (reportType === 'daily') {
            // Maybe change period options for daily reports
            const periodEl = document.getElementById('reportPeriod');
            if (periodEl) {
                // Preserve current selection if possible
                const currentPeriod = periodEl.value;
                
                // Update options
                periodEl.innerHTML = `
                    <option value="today">วันนี้</option>
                    <option value="yesterday">เมื่อวาน</option>
                    <option value="custom">กำหนดเอง</option>
                `;
                
                // Try to restore previous selection
                if (['today', 'yesterday', 'custom'].includes(currentPeriod)) {
                    periodEl.value = currentPeriod;
                }
            }
        } else if (reportType === 'monthly' || reportType === 'weekly') {
            // Restore default period options
            const periodEl = document.getElementById('reportPeriod');
            if (periodEl) {
                periodEl.innerHTML = `
                    <option value="current">เดือนปัจจุบัน</option>
                    <option value="prev">เดือนที่แล้ว</option>
                    <option value="last3">3 เดือนย้อนหลัง</option>
                    <option value="semester">ภาคเรียนปัจจุบัน</option>
                    <option value="custom">กำหนดเอง</option>
                `;
                periodEl.value = 'current';
            }
        }
        
        // Update any visible/applicable filters
        this.handlePeriodChange({ target: document.getElementById('reportPeriod') });
    },
    
    // Handle report period change
    handlePeriodChange(e) {
        if (!e.target) return;
        
        const period = e.target.value;
        this.filters.period = period;
        
        // Show/hide date range fields
        const dateRange = document.querySelectorAll('.date-range');
        if (period === 'custom') {
            dateRange.forEach(filter => filter.style.display = 'block');
        } else {
            dateRange.forEach(filter => filter.style.display = 'none');
        }
    },
    
    // Handle department filter change
    handleDepartmentChange(e) {
        const departmentId = e.target.value;
        this.filters.department = departmentId;
        
        // In a real implementation, this would update available class levels
        // based on the selected department
        const classLevelEl = document.getElementById('classLevel');
        if (classLevelEl) {
            // Here you would normally make an AJAX request to get updated class levels
            // For demo, we'll just log this
            console.log(`Updating class levels for department ${departmentId}`);
        }
    },
    
    // Handle class level change
    handleClassLevelChange(e) {
        const classLevel = e.target.value;
        this.filters.classLevel = classLevel;
        
        // Update classroom options based on selected class level
        const classRoomEl = document.getElementById('classRoom');
        if (classRoomEl) {
            // For a real implementation, you would fetch this from the backend
            classRoomEl.innerHTML = '<option value="">ทุกห้อง</option>';
            
            if (classLevel) {
                // Add sample rooms based on level
                for (let i = 1; i <= 5; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    classRoomEl.appendChild(option);
                }
            }
        }
    },
    
    // Generate a new report with current filters
    generateReport() {
        // Collect all filter values
        this.filters.reportType = document.getElementById('reportType').value;
        this.filters.department = document.getElementById('departmentFilter').value;
        this.filters.period = document.getElementById('reportPeriod').value;
        this.filters.classLevel = document.getElementById('classLevel').value;
        this.filters.classRoom = document.getElementById('classRoom').value;
        
        if (this.filters.period === 'custom') {
            this.filters.startDate = document.getElementById('startDate').value;
            this.filters.endDate = document.getElementById('endDate').value;
            
            // Validate date range
            if (!this.filters.startDate || !this.filters.endDate) {
                alert('กรุณาระบุวันที่เริ่มต้นและวันที่สิ้นสุด');
                return;
            }
            
            // Check if start date is before end date
            if (new Date(this.filters.startDate) > new Date(this.filters.endDate)) {
                alert('วันที่เริ่มต้นต้องอยู่ก่อนวันที่สิ้นสุด');
                return;
            }
        }
        
        if (this.filters.reportType === 'student') {
            this.filters.studentSearch = document.getElementById('studentSearch').value;
            if (!this.filters.studentSearch) {
                alert('กรุณาระบุรหัสหรือชื่อนักเรียน');
                return;
            }
        }
        
        // Display loading state
        this.showLoadingOverlay();
        
        // In a real implementation, you would make an AJAX request here
        console.log('Generating report with filters:', this.filters);
        
        // Simulate API call
        setTimeout(() => {
            // Hide loading overlay
            this.hideLoadingOverlay();
            
            // For demo, just reload the page (in real implementation, you'd update the UI with new data)
            window.location.reload();
        }, 1500);
    },
    
    // Show loading overlay
    showLoadingOverlay() {
        let overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <span class="material-icons spin">refresh</span>
                    <span>กำลังโหลดข้อมูล...</span>
                </div>
            `;
            
            // Add styles for the overlay
            const style = document.createElement('style');
            style.textContent = `
                #loadingOverlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(255, 255, 255, 0.8);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 2000;
                    backdrop-filter: blur(3px);
                }
                .loading-spinner {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 10px;
                    background-color: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .spin {
                    animation: spin 1.5s linear infinite;
                    font-size: 36px;
                    color: #1976d2;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        } else {
            overlay.style.display = 'flex';
        }
    },
    
    // Hide loading overlay
    hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },
    
    // Create yearly trend chart
    createYearlyTrendChart() {
        const ctx = document.getElementById('yearlyAttendanceChart');
        if (!ctx) return;
        
        // Check if we have existing chart
        if (this.charts.yearlyTrend) {
            this.charts.yearlyTrend.destroy();
        }
        
        // Try to get data from window object (set in PHP)
        const labels = window.yearlyTrendsData ? window.yearlyTrendsData.map(item => item.month) : 
            ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        
        const data = window.yearlyTrendsData ? window.yearlyTrendsData.map(item => item.rate) : 
            [95, 94, 92, 91, 93, 94, 90, 89, 92, 91, 93, 94];
        
        // Create chart
        this.charts.yearlyTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: data,
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    borderColor: 'rgba(25, 118, 210, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(25, 118, 210, 1)',
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: Math.max(0, Math.min(...data) - 10),
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
                        caretSize: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });
    },
    
    // Create pie chart for absence reasons
    createAbsenceReasonsPieChart() {
        const ctx = document.getElementById('absenceReasonsChart');
        if (!ctx) return;
        
        // Check if we have existing chart
        if (this.charts.absenceReasons) {
            this.charts.absenceReasons.destroy();
        }
        
        // Try to get data from window object (set in PHP)
        const labels = window.absenceReasonsData ? window.absenceReasonsData.map(item => item.reason) : 
            ['ป่วย', 'ธุระส่วนตัว', 'มาสาย', 'ไม่ทราบสาเหตุ'];
        
        const data = window.absenceReasonsData ? window.absenceReasonsData.map(item => item.percent) : 
            [42, 28, 15, 15];
        
        // Chart colors
        const backgroundColors = [
            'rgba(33, 150, 243, 0.8)',  // Blue
            'rgba(255, 152, 0, 0.8)',   // Orange
            'rgba(156, 39, 176, 0.8)',  // Purple
            'rgba(244, 67, 54, 0.8)'    // Red
        ];
        
        // Create chart
        this.charts.absenceReasons = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
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
                                const value = context.parsed;
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
    },
    
    // Create bar chart for class comparison
    createClassComparisonChart() {
        const ctx = document.getElementById('classComparisonBarChart');
        if (!ctx) return;
        
        // Check if we have existing chart
        if (this.charts.classComparison) {
            this.charts.classComparison.destroy();
        }
        
        // Try to get data from window object (set in PHP)
        const classRates = window.classRatesData || [];
        
        // Prepare data
        const classNames = classRates.length ? classRates.map(item => item.class_name) : 
            ['ม.4/1', 'ม.4/2', 'ม.4/3', 'ม.5/1', 'ม.5/2', 'ม.5/3', 'ม.6/1', 'ม.6/2', 'ม.6/3'];
        
        const attendanceRates = classRates.length ? classRates.map(item => item.attendance_rate) : 
            [92, 90, 88, 94, 89, 82, 93, 85, 90];
        
        // Generate colors based on values
        const barColors = attendanceRates.map(rate => {
            if (rate >= 90) return 'rgba(76, 175, 80, 0.8)';  // Green
            if (rate >= 80) return 'rgba(255, 152, 0, 0.8)';  // Orange
            return 'rgba(244, 67, 54, 0.8)';  // Red
        });
        
        // Create chart
        this.charts.classComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: classNames,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: attendanceRates,
                    backgroundColor: barColors,
                    borderWidth: 0,
                    borderRadius: 4,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: Math.max(0, Math.min(...attendanceRates) - 10),
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
                        callbacks: {
                            label: function(context) {
                                const dataIndex = context.dataIndex;
                                
                                // Check if we have class data from PHP
                                if (classRates.length && classRates[dataIndex]) {
                                    const classInfo = classRates[dataIndex];
                                    return [
                                        `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`,
                                        `จำนวนนักเรียน: ${classInfo.student_count} คน`,
                                        `แผนกวิชา: ${classInfo.department_name}`
                                    ];
                                }
                                
                                return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                    delay: function(context) {
                        return context.dataIndex * 100;
                    }
                }
            }
        });
    },
    
    // Create student monthly chart
    createStudentMonthlyChart(studentId, data) {
        const ctx = document.getElementById('studentMonthlyChart');
        if (!ctx) return;
        
        // Check if we have existing chart
        if (this.charts.studentMonthly) {
            this.charts.studentMonthly.destroy();
        }
        
        // Sample data if not provided
        const chartData = data || {
            labels: ['ม.ค.', 'ก.พ.', 'มี.ค.'],
            rates: [68, 70, 68.5]
        };
        
        // Determine color based on attendance rate
        const color = chartData.rates[chartData.rates.length - 1] >= 80 ? 
            'rgba(255, 152, 0, 1)' : 'rgba(244, 67, 54, 1)';
        
        const bgColor = chartData.rates[chartData.rates.length - 1] >= 80 ? 
            'rgba(255, 152, 0, 0.1)' : 'rgba(244, 67, 54, 0.1)';
        
        // Create chart
        this.charts.studentMonthly = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: chartData.rates,
                    backgroundColor: bgColor,
                    borderColor: color,
                    borderWidth: 2,
                    pointBackgroundColor: color,
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: Math.max(0, Math.min(...chartData.rates) - 10),
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
                        caretSize: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
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
    },
    
    // View student details
    viewStudentDetails(studentId) {
        console.log(`Viewing details for student ID: ${studentId}`);
        
        // In real implementation, you would fetch student data from backend
        // For demo, we're using static data
        
        // Prepare sample student data
        const studentData = {
            id: studentId,
            name: 'นายธนกฤต สุขใจ',
            code: '16478',
            className: 'ม.6/2 เลขที่ 12',
            attendanceRate: 68.5,
            presentDays: 15,
            absentDays: 7,
            totalDays: 22,
            attendanceHistory: [
                { date: '16/03/2568', status: 'มา', statusClass: 'success', time: '08:02', remark: '-' },
                { date: '15/03/2568', status: 'มา', statusClass: 'success', time: '08:05', remark: '-' },
                { date: '14/03/2568', status: 'ขาด', statusClass: 'danger', time: '-', remark: 'ไม่มาโรงเรียน' },
                { date: '13/03/2568', status: 'มาสาย', statusClass: 'warning', time: '08:32', remark: 'รถติด' },
                { date: '12/03/2568', status: 'มา', statusClass: 'success', time: '08:10', remark: '-' }
            ],
            notificationHistory: [
                { date: '16/03/2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'จารุวรรณ บุญมี', status: 'ส่งสำเร็จ', statusClass: 'success' },
                { date: '01/03/2568', type: 'แจ้งเตือนปกติ', sender: 'อ.ประสิทธิ์ ดีเลิศ', status: 'ส่งสำเร็จ', statusClass: 'success' },
                { date: '15/02/2568', type: 'แจ้งเตือนปกติ', sender: 'อ.ประสิทธิ์ ดีเลิศ', status: 'ส่งสำเร็จ', statusClass: 'success' }
            ],
            monthlyTrend: {
                labels: ['ม.ค.', 'ก.พ.', 'มี.ค.'],
                rates: [68, 70, 68.5]
            }
        };
        
        // Fill modal with student data
        document.getElementById('studentDetailName').textContent = studentData.name;
        document.getElementById('studentDetailInitial').textContent = studentData.name.charAt(0);
        document.getElementById('studentDetailFullName').textContent = studentData.name;
        document.getElementById('studentDetailCode').textContent = studentData.code;
        document.getElementById('studentDetailClass').textContent = studentData.className;
        
        const rateElement = document.getElementById('studentDetailRate');
        rateElement.textContent = `${studentData.attendanceRate.toFixed(1)}%`;
        rateElement.className = 'status-badge ' + (studentData.attendanceRate < 70 ? 'danger' : 'warning');
        
        document.getElementById('studentDetailPresent').textContent = studentData.presentDays;
        document.getElementById('studentDetailAbsent').textContent = studentData.absentDays;
        document.getElementById('studentDetailTotal').textContent = studentData.totalDays;
        
        // Fill attendance history table
        const historyTable = document.getElementById('studentAttendanceHistory');
        historyTable.innerHTML = '';
        
        studentData.attendanceHistory.forEach(record => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${record.date}</td>
                <td><span class="status-badge ${record.statusClass}">${record.status}</span></td>
                <td>${record.time}</td>
                <td>${record.remark}</td>
            `;
            historyTable.appendChild(row);
        });
        
        // Fill notification history table
        const notificationTable = document.getElementById('studentNotificationHistory');
        notificationTable.innerHTML = '';
        
        studentData.notificationHistory.forEach(record => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${record.date}</td>
                <td>${record.type}</td>
                <td>${record.sender}</td>
                <td><span class="status-badge ${record.statusClass}">${record.status}</span></td>
            `;
            notificationTable.appendChild(row);
        });
        
        // Create monthly trend chart
        this.createStudentMonthlyChart(studentId, studentData.monthlyTrend);
        
        // Show modal
        this.showModal('studentDetailModal');
    },
    
    // Send notification to parent
    sendNotificationToParent(studentId) {
        console.log(`Sending notification for student ID: ${studentId}`);
        
        // In real implementation, you would show a notification form or make an API call
        // For demo, we'll just show an alert
        alert(`กำลังส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนรหัส ${studentId}`);
    },
    
    // Open send message modal
    openSendMessageModal() {
        // In real implementation, you would show a form to send a message
        // For demo, we'll just redirect
        window.location.href = 'send_notification.php?student_id=16478';
    },
    
    // Print student report
    printStudentReport() {
        window.print();
    },
    
    // Show modal
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            
            // Apply body style to prevent scrolling
            document.body.style.overflow = 'hidden';
        }
    },
    
    // Close modal
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            
            // Restore body scrolling
            document.body.style.overflow = '';
        }
    },
    
    // Refresh yearly chart
    refreshYearlyChart() {
        // Show loading indicator on the chart
        const chartContainer = document.getElementById('yearlyTrendChart');
        if (chartContainer) {
            chartContainer.classList.add('loading');
        }
        
        // In real implementation, you would fetch new data from the backend
        // For demo, we'll just recreate the chart with the same data after a delay
        setTimeout(() => {
            this.createYearlyTrendChart();
            
            // Remove loading indicator
            if (chartContainer) {
                chartContainer.classList.remove('loading');
            }
        }, 1000);
    },
    
    // Download yearly chart
    downloadYearlyChart() {
        // In real implementation, you would generate an image from the chart
        // For demo, we'll just show an alert
        alert('กำลังดาวน์โหลดกราฟแนวโน้มการเข้าแถวตลอดปีการศึกษา');
    }
};

// Document ready
document.addEventListener('DOMContentLoaded', () => {
    ReportManager.init();
    
    // Add additional event listeners that might not be covered in init
    
    // Send message modal button
    document.querySelector('[onclick="openSendMessageModal()"]')?.addEventListener('click', () => {
        ReportManager.openSendMessageModal();
    });
    
    // Print report button
    document.querySelector('[onclick="printStudentReport()"]')?.addEventListener('click', () => {
        ReportManager.printStudentReport();
    });
});
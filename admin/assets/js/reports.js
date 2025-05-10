/**
 * reports.js - แก้ไขการทำงานของแผงค้นหาและกรองข้อมูล
 */

// ตัวจัดการรายงาน
const ReportManager = {
    // การอ้างอิงกราฟ
    charts: {
        yearlyTrend: null,
        absenceReasons: null,
        classComparison: null,
        studentMonthly: null
    },
    
    // สถานะตัวกรองปัจจุบัน
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
    
    // เริ่มต้นตัวจัดการรายงาน
    init() {
        console.log('เริ่มต้น ReportManager...');
        this.setupEventListeners();
        this.initializeFilters();
        this.initializeCharts();
        this.setupResponsiveLayout();
    },
    
    // กำหนด Event Listeners
    setupEventListeners() {
        console.log('กำหนด Event Listeners...');
        
        // ตัวกรอง
        const reportTypeElem = document.getElementById('reportType');
        if (reportTypeElem) {
            reportTypeElem.addEventListener('change', this.handleReportTypeChange.bind(this));
        } else {
            console.warn('ไม่พบอิลิเมนต์ reportType');
        }
        
        const reportPeriodElem = document.getElementById('reportPeriod');
        if (reportPeriodElem) {
            reportPeriodElem.addEventListener('change', this.handlePeriodChange.bind(this));
        } else {
            console.warn('ไม่พบอิลิเมนต์ reportPeriod');
        }
        
        const departmentFilterElem = document.getElementById('departmentFilter');
        if (departmentFilterElem) {
            departmentFilterElem.addEventListener('change', this.handleDepartmentChange.bind(this));
        }
        
        const classLevelElem = document.getElementById('classLevel');
        if (classLevelElem) {
            classLevelElem.addEventListener('change', this.handleClassLevelChange.bind(this));
        }
        
        // ปุ่มสร้างรายงาน
        const filterButtonElem = document.querySelector('.filter-button');
        if (filterButtonElem) {
            filterButtonElem.addEventListener('click', this.generateReport.bind(this));
        } else {
            console.warn('ไม่พบปุ่มกรองข้อมูล');
        }
        
        // ปุ่มดูรายละเอียดนักเรียน
        document.querySelectorAll('.table-action-btn.primary').forEach(btn => {
            btn.addEventListener('click', e => {
                const studentId = e.currentTarget.getAttribute('data-student-id') || 
                                 e.currentTarget.closest('tr')?.getAttribute('data-student-id');
                if (studentId) {
                    this.viewStudentDetails(parseInt(studentId));
                }
            });
        });
        
        // ตรวจจับการปรับขนาดหน้าจอ
        window.addEventListener('resize', this.handleResize.bind(this));
    },
    
    // ตั้งค่าเริ่มต้นตัวกรอง
    initializeFilters() {
        console.log('ตั้งค่าเริ่มต้นตัวกรอง...');
        
        // ตั้งค่า period จากการเลือกเริ่มต้น
        const periodElem = document.getElementById('reportPeriod');
        if (periodElem) {
            this.filters.period = periodElem.value;
            this.handlePeriodChange({ target: periodElem });
        }
        
        // ตั้งค่า report type จากการเลือกเริ่มต้น
        const reportTypeElem = document.getElementById('reportType');
        if (reportTypeElem) {
            this.filters.reportType = reportTypeElem.value;
            this.handleReportTypeChange({ target: reportTypeElem });
        }
        
        // แก้ไขปัญหาการไม่แสดงผลแผงกรองข้อมูล
        this.fixFilterDisplayIssues();
    },
    
    // แก้ไขปัญหาการไม่แสดงผลแผงกรองข้อมูล
    fixFilterDisplayIssues() {
        // ตรวจสอบว่าแผงกรองแสดงผลอยู่หรือไม่
        const filterContainer = document.querySelector('.filter-container');
        if (filterContainer) {
            // ตรวจสอบค่า display
            const displayStyle = window.getComputedStyle(filterContainer).display;
            if (displayStyle === 'none') {
                console.log('แก้ไขปัญหาแผงกรองไม่แสดงผล');
                filterContainer.style.display = 'flex';
            }
            
            // ตรวจสอบความโปร่งใส
            filterContainer.style.opacity = '1';
            filterContainer.style.visibility = 'visible';
        } else {
            console.warn('ไม่พบ filter-container');
        }
        
        // ตรวจสอบตัวกรองย่อยทุกตัว
        document.querySelectorAll('.filter-group').forEach(group => {
            group.style.display = 'block';
        });
        
        // ตรวจสอบการแสดงผลของช่วงวันที่
        this.updateDateRangeDisplay();
    },
    
    // เริ่มต้นกราฟทั้งหมด
    initializeCharts() {
        console.log('เริ่มต้นกราฟ...');
        
        // ตรวจสอบว่า Chart.js ถูกโหลดแล้วหรือไม่
        if (typeof Chart !== 'undefined') {
            this.createYearlyTrendChart();
            this.createAbsenceReasonsPieChart();
            this.createClassComparisonChart();
        } else {
            console.warn('Chart.js ไม่ได้ถูกโหลด รอการโหลด...');
            // รอให้ Chart.js โหลดเสร็จ
            window.addEventListener('load', () => {
                setTimeout(() => {
                    if (typeof Chart !== 'undefined') {
                        this.createYearlyTrendChart();
                        this.createAbsenceReasonsPieChart();
                        this.createClassComparisonChart();
                    } else {
                        console.error('ไม่พบ Chart.js หลังจากรอ');
                    }
                }, 1000);
            });
        }
    },
    
    // ตั้งค่าสำหรับหน้าจอแบบ responsive
    setupResponsiveLayout() {
        this.handleResize();
    },
    
    // จัดการกับการปรับขนาดหน้าจอ
    handleResize() {
        // ปรับขนาดกราฟตามขนาดหน้าจอ
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            if (window.innerWidth <= 576) {
                container.style.height = '250px';
            } else {
                container.style.height = '300px';
            }
        });
        
        // ปรับการแสดงผลบนมือถือ
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.monthly-summary-item').forEach(item => {
                item.style.minWidth = '100%';
            });
            
            document.querySelectorAll('.filter-group').forEach(group => {
                group.style.minWidth = '100%';
                group.style.maxWidth = '100%';
            });
        }
        
        // อัพเดทกราฟตามขนาดใหม่
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                try {
                    chart.resize();
                } catch (e) {
                    // บางครั้ง Chart.js อาจจะไม่มีเมธอด resize
                    console.warn('ไม่สามารถปรับขนาดกราฟได้', e);
                }
            }
        });
    },
    
    // จัดการกับการเปลี่ยนประเภทรายงาน
    handleReportTypeChange(e) {
        if (!e || !e.target) return;
        
        const reportType = e.target.value;
        console.log(`เปลี่ยนประเภทรายงานเป็น: ${reportType}`);
        this.filters.reportType = reportType;
        
        // แสดง/ซ่อนตัวกรองนักเรียน
        const studentFilters = document.querySelectorAll('.student-filter');
        studentFilters.forEach(filter => {
            filter.style.display = reportType === 'student' ? 'block' : 'none';
        });
        
        // ปรับตัวกรองอื่นๆ ตามประเภทรายงาน
        const classFilters = document.querySelectorAll('.class-filter');
        classFilters.forEach(filter => {
            filter.style.display = reportType === 'class' ? 'block' : 'flex';
        });
        
        // ปรับตัวเลือกช่วงเวลาตามประเภทรายงาน
        const periodElem = document.getElementById('reportPeriod');
        if (periodElem) {
            if (reportType === 'daily') {
                periodElem.innerHTML = `
                    <option value="today">วันนี้</option>
                    <option value="yesterday">เมื่อวาน</option>
                    <option value="custom">กำหนดเอง</option>
                `;
                periodElem.value = 'today';
            } else if (reportType === 'weekly') {
                periodElem.innerHTML = `
                    <option value="current">สัปดาห์นี้</option>
                    <option value="prev">สัปดาห์ที่แล้ว</option>
                    <option value="custom">กำหนดเอง</option>
                `;
                periodElem.value = 'current';
            } else {
                periodElem.innerHTML = `
                    <option value="current">เดือนปัจจุบัน</option>
                    <option value="prev">เดือนที่แล้ว</option>
                    <option value="last3">3 เดือนย้อนหลัง</option>
                    <option value="semester">ภาคเรียนปัจจุบัน</option>
                    <option value="custom">กำหนดเอง</option>
                `;
                periodElem.value = 'current';
            }
            
            this.filters.period = periodElem.value;
            this.handlePeriodChange({ target: periodElem });
        }
    },
    
    // จัดการกับการเปลี่ยนช่วงเวลา
    handlePeriodChange(e) {
        if (!e || !e.target) return;
        
        const period = e.target.value;
        console.log(`เปลี่ยนช่วงเวลาเป็น: ${period}`);
        this.filters.period = period;
        
        this.updateDateRangeDisplay();
    },
    
    // อัพเดทการแสดงผลช่วงวันที่
    updateDateRangeDisplay() {
        const dateRanges = document.querySelectorAll('.date-range');
        const shouldShowDateRange = this.filters.period === 'custom';
        
        dateRanges.forEach(range => {
            // แทนที่จะใช้ display: none ซึ่งอาจมีปัญหา
            if (shouldShowDateRange) {
                range.classList.remove('truly-hidden');
                range.style.visibility = 'visible';
                range.style.height = 'auto';
                range.style.opacity = '1';
            } else {
                range.classList.add('truly-hidden');
                range.style.visibility = 'hidden';
                range.style.height = '0';
                range.style.opacity = '0';
            }
        });
    },
    
    // จัดการกับการเปลี่ยนแผนก
    handleDepartmentChange(e) {
        if (!e || !e.target) return;
        
        const departmentId = e.target.value;
        console.log(`เปลี่ยนแผนกเป็น: ${departmentId}`);
        this.filters.department = departmentId;
        
        // อัพเดทตัวเลือกระดับชั้น
        this.updateClassLevelOptions(departmentId);
    },
    
    // อัพเดทตัวเลือกระดับชั้นตามแผนก
    updateClassLevelOptions(departmentId) {
        const classLevelElem = document.getElementById('classLevel');
        if (!classLevelElem) return;
        
        // ถ้ามีการเลือกแผนก ให้ดึงข้อมูลระดับชั้นที่เกี่ยวข้อง
        if (departmentId) {
            // ในกรณีจริง ควรส่ง AJAX request ไปดึงข้อมูล
            console.log(`กำลังดึงระดับชั้นสำหรับแผนก ${departmentId}`);
            
            // ตัวอย่างข้อมูล (ในระบบจริงควรดึงจาก API)
            const levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
            
            // อัพเดทตัวเลือก
            classLevelElem.innerHTML = '<option value="">ทุกระดับชั้น</option>';
            levels.forEach(level => {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = level;
                classLevelElem.appendChild(option);
            });
        } else {
            // ถ้าไม่เลือกแผนก แสดงทุกระดับชั้น
            classLevelElem.innerHTML = '<option value="">ทุกระดับชั้น</option>';
            const levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
            levels.forEach(level => {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = level;
                classLevelElem.appendChild(option);
            });
        }
        
        // รีเซ็ตค่าระดับชั้นและกลุ่ม
        classLevelElem.value = '';
        this.filters.classLevel = '';
        this.handleClassLevelChange({ target: classLevelElem });
    },
    
    // จัดการกับการเปลี่ยนระดับชั้น
    handleClassLevelChange(e) {
        if (!e || !e.target) return;
        
        const classLevel = e.target.value;
        console.log(`เปลี่ยนระดับชั้นเป็น: ${classLevel}`);
        this.filters.classLevel = classLevel;
        
        // อัพเดทตัวเลือกห้องเรียน
        this.updateClassRoomOptions(classLevel);
    },
    
    // อัพเดทตัวเลือกห้องเรียนตามระดับชั้น
    updateClassRoomOptions(classLevel) {
        const classRoomElem = document.getElementById('classRoom');
        if (!classRoomElem) return;
        
        // ถ้ามีการเลือกระดับชั้น ให้ดึงข้อมูลห้องที่เกี่ยวข้อง
        if (classLevel) {
            // ในกรณีจริง ควรส่ง AJAX request ไปดึงข้อมูล
            console.log(`กำลังดึงห้องเรียนสำหรับระดับชั้น ${classLevel}`);
            
            // อัพเดทตัวเลือก
            classRoomElem.innerHTML = '<option value="">ทุกห้อง</option>';
            
            // ตัวอย่างข้อมูล (ในระบบจริงควรดึงจาก API)
            for (let i = 1; i <= 5; i++) {
                const option = document.createElement('option');
                option.value = i.toString();
                option.textContent = i.toString();
                classRoomElem.appendChild(option);
            }
        } else {
            // ถ้าไม่เลือกระดับชั้น ล้างตัวเลือกห้อง
            classRoomElem.innerHTML = '<option value="">ทุกห้อง</option>';
        }
        
        // รีเซ็ตค่าห้อง
        classRoomElem.value = '';
        this.filters.classRoom = '';
    },
    
    // สร้างรายงานด้วยตัวกรองปัจจุบัน
    generateReport() {
        console.log('กำลังสร้างรายงาน...');
        
        // เก็บค่าตัวกรองทั้งหมด
        this.filters.reportType = document.getElementById('reportType').value;
        this.filters.department = document.getElementById('departmentFilter').value;
        this.filters.period = document.getElementById('reportPeriod').value;
        this.filters.classLevel = document.getElementById('classLevel').value;
        this.filters.classRoom = document.getElementById('classRoom').value;
        
        if (this.filters.period === 'custom') {
            this.filters.startDate = document.getElementById('startDate').value;
            this.filters.endDate = document.getElementById('endDate').value;
            
            // ตรวจสอบวันที่
            if (!this.filters.startDate || !this.filters.endDate) {
                alert('กรุณาระบุวันที่เริ่มต้นและวันที่สิ้นสุด');
                return;
            }
            
            // ตรวจสอบว่าวันที่เริ่มต้นอยู่ก่อนวันที่สิ้นสุด
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
        
        // แสดงข้อความกำลังโหลด
        this.showLoadingOverlay();
        
        // ในกรณีจริง ควรส่ง AJAX request ไปดึงข้อมูล
        console.log('กำลังสร้างรายงานด้วยตัวกรอง:', this.filters);
        
        // จำลองการเรียก API
        setTimeout(() => {
            // ซ่อนข้อความกำลังโหลด
            this.hideLoadingOverlay();
            
            // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่ (ในระบบจริงควรอัพเดท DOM แทน)
            alert('สร้างรายงานเรียบร้อยแล้ว');
            window.location.reload();
        }, 1500);
    },
    
    // แสดงข้อมูลนักเรียนโดยละเอียด
    viewStudentDetails(studentId) {
        console.log(`กำลังดูข้อมูลของนักเรียนรหัส: ${studentId}`);
        
        // ในกรณีจริง ควรส่ง AJAX request ไปดึงข้อมูล
        // สำหรับตัวอย่าง ใช้ข้อมูลสมมติ
        
        // เตรียมข้อมูลนักเรียนตัวอย่าง
        const studentData = {
            id: studentId,
            name: 'นายธนกฤต สุขใจ',
            code: '16478',
            className: 'ปวช.1/1 เลขที่ 12',
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
        
        // แสดงข้อมูลในโมดัล (ในกรณีจริงควรเรียกฟังก์ชันเฉพาะ)
        alert(`แสดงข้อมูลนักเรียน: ${studentData.name} (รหัส ${studentData.code})\nอัตราการเข้าแถว: ${studentData.attendanceRate}%`);
    },
    
    // แสดงโอเวอร์เลย์กำลังโหลด
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
            
            // เพิ่มสไตล์สำหรับโอเวอร์เลย์
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
    
    // ซ่อนโอเวอร์เลย์กำลังโหลด
    hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },
    
    // สร้างกราฟแนวโน้มรายปี
    createYearlyTrendChart() {
        const ctx = document.getElementById('yearlyAttendanceChart');
        if (!ctx) {
            console.warn('ไม่พบอิลิเมนต์ yearlyAttendanceChart');
            return;
        }
        
        // ถ้ามีกราฟเดิมให้ทำลายก่อน
        if (this.charts.yearlyTrend) {
            this.charts.yearlyTrend.destroy();
        }
        
        // พยายามดึงข้อมูลจาก window object (ถ้าตั้งไว้ใน PHP)
        let data = [];
        let labels = [];
        
        if (window.yearlyTrendsData) {
            labels = window.yearlyTrendsData.map(item => item.month);
            data = window.yearlyTrendsData.map(item => item.rate);
        } else {
            // ใช้ข้อมูลตัวอย่างถ้าไม่มีข้อมูลจริง
            labels = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
            data = [95, 94, 92, 91, 93, 94, 90, 89, 92, 100];
        }
        
        // สร้างกราฟ
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
                }
            }
        });
    },
    
    // สร้างกราฟวงกลมแสดงสาเหตุการขาด
    createAbsenceReasonsPieChart() {
        const ctx = document.getElementById('absenceReasonsChart');
        if (!ctx) {
            console.warn('ไม่พบอิลิเมนต์ absenceReasonsChart');
            return;
        }
        
        // ถ้ามีกราฟเดิมให้ทำลายก่อน
        if (this.charts.absenceReasons) {
            this.charts.absenceReasons.destroy();
        }
        
        // พยายามดึงข้อมูลจาก window object (ถ้าตั้งไว้ใน PHP)
        let data = [];
        let labels = [];
        let colors = [];
        
        if (window.absenceReasonsData) {
            labels = window.absenceReasonsData.map(item => item.reason);
            data = window.absenceReasonsData.map(item => item.percent);
            colors = window.absenceReasonsData.map(item => item.color || getRandomColor());
        } else {
            // ใช้ข้อมูลตัวอย่างถ้าไม่มีข้อมูลจริง
            labels = ['ป่วย', 'ธุระส่วนตัว', 'มาสาย', 'ไม่ทราบสาเหตุ'];
            data = [42, 28, 15, 15];
            colors = [
                'rgba(33, 150, 243, 0.8)',
                'rgba(255, 152, 0, 0.8)',
                'rgba(156, 39, 176, 0.8)',
                'rgba(244, 67, 54, 0.8)'
            ];
        }
        
        // สร้างกราฟ
        this.charts.absenceReasons = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
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
                                return `${context.label}: ${value}%`;
                            }
                        }
                    }
                }
            }
        });
    },
    
    // สร้างกราฟเปรียบเทียบอัตราการเข้าแถวตามชั้นเรียน
    createClassComparisonChart() {
        const ctx = document.getElementById('classComparisonBarChart');
        if (!ctx) {
            console.warn('ไม่พบอิลิเมนต์ classComparisonBarChart');
            return;
        }
        
        // ถ้ามีกราฟเดิมให้ทำลายก่อน
        if (this.charts.classComparison) {
            this.charts.classComparison.destroy();
        }
        
        // พยายามดึงข้อมูลจาก window object (ถ้าตั้งไว้ใน PHP)
        let data = [];
        let labels = [];
        let colors = [];
        
        if (window.classRatesData && window.classRatesData.length) {
            labels = window.classRatesData.map(item => item.class_name);
            data = window.classRatesData.map(item => parseFloat(item.attendance_rate));
            
            // กำหนดสีตามค่า
            colors = data.map(rate => {
                if (rate >= 90) return 'rgba(76, 175, 80, 0.8)';
                if (rate >= 80) return 'rgba(255, 152, 0, 0.8)';
                return 'rgba(244, 67, 54, 0.8)';
            });
        } else {
            // ใช้ข้อมูลตัวอย่างถ้าไม่มีข้อมูลจริง
            labels = ['ปวช.1/1', 'ปวช.1/2', 'ปวช.2/1', 'ปวช.2/2', 'ปวช.3/1', 'ปวส.1/1', 'ปวส.2/1'];
            data = [92, 90, 88, 94, 89, 82, 93];
            
            // กำหนดสีตามค่า
            colors = data.map(rate => {
                if (rate >= 90) return 'rgba(76, 175, 80, 0.8)';
                if (rate >= 80) return 'rgba(255, 152, 0, 0.8)';
                return 'rgba(244, 67, 54, 0.8)';
            });
        }
        
        // สร้างกราฟ
        this.charts.classComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: data,
                    backgroundColor: colors,
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
                        callbacks: {
                            label: function(context) {
                                return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                }
            }
        });
    }
};

// ฟังก์ชันสร้างสีสุ่ม
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = 'rgba(';
    for (let i = 0; i < 3; i++) {
        color += Math.floor(Math.random() * 256) + ',';
    }
    color += '0.8)';
    return color;
}

// เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing ReportManager...');
    
    // เริ่มต้นตัวจัดการรายงาน
    ReportManager.init();
    
    // เพิ่ม listeners ที่อาจไม่ได้ถูกจัดการใน init
    document.querySelector('[onclick="refreshYearlyChart()"]')?.addEventListener('click', () => {
        ReportManager.createYearlyTrendChart();
    });
    
    document.querySelector('[onclick="downloadYearlyChart()"]')?.addEventListener('click', () => {
        alert('กำลังดาวน์โหลดกราฟแนวโน้มการเข้าแถวตลอดปีการศึกษา');
    });
    
    document.querySelector('[onclick="downloadReport()"]')?.addEventListener('click', () => {
        alert('กำลังดาวน์โหลดรายงานสรุปการเข้าแถว');
    });
    
    // แก้ไขปัญหาที่อาจเกิดขึ้นจากการโหลดสคริปต์
    setTimeout(() => ReportManager.fixFilterDisplayIssues(), 500);
});
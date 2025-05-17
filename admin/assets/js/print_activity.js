/**
 * print_activity.js - จัดการหน้าพิมพ์รายงานผลกิจกรรมเข้าแถว
 * 
 * ระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

document.addEventListener('DOMContentLoaded', function() {
    // อ้างอิงองค์ประกอบใน DOM
    const departmentSelect = document.getElementById('department-select');
    const classSelect = document.getElementById('class-select');
    const weekSelect = document.getElementById('week-select');
    const generateReportBtn = document.getElementById('generate-report-btn');
    const printReportBtn = document.getElementById('print-report-btn');
    const exportPdfBtn = document.getElementById('export-pdf-btn');
    const exportExcelBtn = document.getElementById('export-excel-btn');
    const reportContainer = document.getElementById('report-container');
    const reportContent = document.getElementById('report-content');
    const chartsContainer = document.getElementById('charts-container');
    const reportPlaceholder = document.getElementById('report-placeholder');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    // รายชื่อวันในสัปดาห์ภาษาไทย
    const thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    const thaiDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    // รายชื่อเดือนภาษาไทย
    const thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    // ติดตั้ง Event Listeners
    departmentSelect.addEventListener('change', fetchClasses);
    classSelect.addEventListener('change', updateGenerateButtonState);
    weekSelect.addEventListener('change', updateGenerateButtonState);
    generateReportBtn.addEventListener('click', generateReport);
    printReportBtn.addEventListener('click', printReport);
    exportPdfBtn.addEventListener('click', exportToPdf);
    exportExcelBtn.addEventListener('click', exportToExcel);
    
    // ตัวแปรสำหรับเก็บข้อมูลรายงานล่าสุด
    let lastReportData = null;
    
    // ดึงข้อมูลห้องเรียนเมื่อเลือกแผนกวิชา
    function fetchClasses() {
        const departmentId = departmentSelect.value;
        
        // รีเซ็ตตัวเลือกชั้นเรียน
        classSelect.innerHTML = '<option value="">-- เลือกชั้นเรียน --</option>';
        classSelect.disabled = true;
        
        if (!departmentId) return;
        
        // แสดง loading
        showLoading();
        
        // ดึงข้อมูลชั้นเรียนจาก API
        fetch(`api/classes.php?action=get_by_department&department_id=${departmentId}&academic_year_id=${academicYear.academic_year_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // เพิ่มตัวเลือกชั้นเรียน
                    data.classes.forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.class_id;
                        option.textContent = `${classItem.level}/${classItem.group_number}`;
                        option.dataset.level = classItem.level;
                        option.dataset.group = classItem.group_number;
                        classSelect.appendChild(option);
                    });
                    
                    // เปิดใช้งานตัวเลือกชั้นเรียน
                    classSelect.disabled = false;
                    
                    // ถ้ามีการกำหนด exportClassId (สำหรับการส่งออกอัตโนมัติ)
                    if (typeof autoExportToExcel === 'function' && exportClassId) {
                        // ค้นหาและเลือกชั้นเรียนสำหรับการส่งออก
                        for (let i = 0; i < classSelect.options.length; i++) {
                            if (classSelect.options[i].value === exportClassId) {
                                classSelect.selectedIndex = i;
                                updateGenerateButtonState();
                                setTimeout(autoExportToExcel, 500);
                                break;
                            }
                        }
                    }
                } else {
                    alert('ไม่สามารถดึงข้อมูลชั้นเรียนได้: ' + (data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                }
                hideLoading();
            })
            .catch(error => {
                console.error('Error fetching classes:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน');
                hideLoading();
                
                // กรณีทดสอบระบบโดยไม่มี API
                simulateClassData(departmentId);
            });
    }
    
    // จำลองข้อมูลชั้นเรียนสำหรับการทดสอบ
    function simulateClassData(departmentId) {
        // รีเซ็ตตัวเลือกชั้นเรียน
        classSelect.innerHTML = '<option value="">-- เลือกชั้นเรียน --</option>';
        
        // สร้างข้อมูลชั้นเรียนจำลอง
        const levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
        const groupCounts = {
            'ปวช.1': 2, 'ปวช.2': 2, 'ปวช.3': 2, 'ปวส.1': 1, 'ปวส.2': 1
        };
        
        // จำลองข้อมูลชั้นเรียนตามแผนกวิชา
        levels.forEach((level, index) => {
            for (let group = 1; group <= groupCounts[level]; group++) {
                const option = document.createElement('option');
                option.value = `${departmentId}_${level}_${group}`; // class_id จำลอง
                option.textContent = `${level}/${group}`;
                option.dataset.level = level;
                option.dataset.group = group;
                classSelect.appendChild(option);
            }
        });
        
        // เปิดใช้งานตัวเลือกชั้นเรียน
        classSelect.disabled = false;
    }
    
    // อัปเดตสถานะปุ่ม "แสดงรายงาน"
    function updateGenerateButtonState() {
        generateReportBtn.disabled = !classSelect.value || !weekSelect.value;
    }
    
    // สร้างรายงาน
    async function generateReport() {
        // ตรวจสอบว่าเลือกข้อมูลครบถ้วน
        if (!classSelect.value || !weekSelect.value) {
            alert('กรุณาเลือกชั้นเรียนและสัปดาห์');
            return;
        }
        
        // แสดง loading
        showLoading();
        
        // ดึงข้อมูลที่เลือก
        const classId = classSelect.value;
        const selectedOption = classSelect.options[classSelect.selectedIndex];
        const classLevel = selectedOption.dataset.level;
        const groupNumber = selectedOption.dataset.group;
        const departmentName = departmentSelect.options[departmentSelect.selectedIndex].textContent;
        
        const weekNumber = weekSelect.value;
        const startDate = weekSelect.options[weekSelect.selectedIndex].dataset.start;
        const endDate = weekSelect.options[weekSelect.selectedIndex].dataset.end;
        
        // ข้อมูลสำหรับส่งไปยัง API
        const data = {
            class_id: classId,
            week_number: weekNumber,
            start_date: startDate,
            end_date: endDate,
            academic_year_id: academicYear.academic_year_id
        };
        
        try {
            // ดึงข้อมูลรายงานจาก API
            const response = await fetch('api/attendance.php?action=get_report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // เก็บข้อมูลรายงานล่าสุด
                lastReportData = {
                    report_data: result.report_data,
                    class_level: classLevel,
                    group_number: groupNumber,
                    department_name: departmentName,
                    week_number: weekNumber,
                    start_date: startDate,
                    end_date: endDate
                };
                
                // สร้างรายงาน
                createReportContent(
                    result.report_data, 
                    classLevel, 
                    groupNumber, 
                    departmentName, 
                    weekNumber, 
                    startDate, 
                    endDate
                );
                
                // เปิดใช้งานปุ่มพิมพ์และส่งออก
                printReportBtn.disabled = false;
                exportPdfBtn.disabled = false;
                exportExcelBtn.disabled = false;
            } else {
                alert('ไม่สามารถดึงข้อมูลรายงานได้: ' + (result.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                // จำลองข้อมูลสำหรับทดสอบ
                simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
            }
        } catch (error) {
            console.error('Error fetching report data:', error);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน');
            
            // กรณีทดสอบระบบโดยไม่มี API
            simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
        } finally {
            hideLoading();
        }
        
        return lastReportData; // ส่งคืนข้อมูลรายงานล่าสุดเพื่อใช้ในการส่งออก
    }
    
    // จำลองข้อมูลรายงานสำหรับการทดสอบ
    function simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate) {
        // สร้างข้อมูลนักเรียนจำลอง
        const students = [];
        const studentCount = 15 + Math.floor(Math.random() * 10); // 15-24 คน
        
        // สร้างข้อมูลวันในสัปดาห์
        const weekDays = getWeekDays(startDate, endDate);
        
        // สร้างข้อมูลนักเรียน
        for (let i = 1; i <= studentCount; i++) {
            const student = {
                student_id: `STUD_${i}`,
                student_code: `67${departmentSelect.value}${i.toString().padStart(5, '0')}`,
                title: Math.random() > 0.3 ? 'นาย' : 'นางสาว',
                first_name: `นักเรียน${i}`,
                last_name: `ทดสอบ${i}`,
                attendances: {}
            };
            
            // สร้างข้อมูลการเข้าแถวในแต่ละวัน
            weekDays.forEach(day => {
                // ข้ามวันเสาร์-อาทิตย์
                const dayOfWeek = new Date(day).getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) return;
                
                // ตรวจสอบวันหยุด
                if (holidays[day]) return;
                
                // สุ่มสถานะการเข้าแถว
                const rand = Math.random();
                let status;
                if (rand > 0.9) {
                    status = 'absent'; // 10% ขาด
                } else if (rand > 0.85) {
                    status = 'late'; // 5% สาย
                } else if (rand > 0.8) {
                    status = 'leave'; // 5% ลา
                } else {
                    status = 'present'; // 80% มา
                }
                
                student.attendances[day] = {
                    status: status,
                    remarks: status === 'leave' ? 'ลาป่วย' : (status === 'absent' ? 'ไม่มาเรียน' : '')
                };
            });
            
            students.push(student);
        }
        
        // สร้างข้อมูลครูที่ปรึกษา
        const advisors = [
            {
                teacher_id: 'T001',
                title: 'นาย',
                first_name: 'มนตรี',
                last_name: 'ศรีสุข',
                is_primary: 1
            }
        ];
        
        // สร้างรายงาน
        const reportData = {
            students: students,
            advisors: advisors,
            week_days: weekDays
        };
        
        // เก็บข้อมูลรายงานล่าสุด
        lastReportData = {
            report_data: reportData,
            class_level: classLevel,
            group_number: groupNumber,
            department_name: departmentName,
            week_number: weekNumber,
            start_date: startDate,
            end_date: endDate
        };
        
        // สร้างเนื้อหารายงาน
        createReportContent(reportData, classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
        
        // เปิดใช้งานปุ่มพิมพ์และส่งออก
        printReportBtn.disabled = false;
        exportPdfBtn.disabled = false;
        exportExcelBtn.disabled = false;
    }
    
    // สร้างเนื้อหารายงาน
    function createReportContent(reportData, classLevel, groupNumber, departmentName, weekNumber, startDate, endDate) {
        // สร้าง DOM สำหรับรายงาน
        const reportDOM = document.createElement('div');
        reportDOM.className = 'print-wrapper';
        
        // ดึงข้อมูลวันในสัปดาห์
        const weekDays = reportData.week_days || getWeekDays(startDate, endDate);
        
        // เลือกเฉพาะวันจันทร์-ศุกร์
        const workDays = weekDays.filter(day => {
            const date = new Date(day);
            const dayOfWeek = date.getDay();
            return dayOfWeek >= 1 && dayOfWeek <= 5; // จันทร์-ศุกร์
        });
        
        // ข้อมูลเดือนและปีสำหรับหัวรายงาน
        const reportDate = new Date(startDate);
        const month = thaiMonths[reportDate.getMonth()];
        const year = reportDate.getFullYear();
        const thaiYear = year + 543;
        
        // สร้างส่วนหัวรายงาน
        reportDOM.innerHTML = `
            <div class="report-header">
                <div class="report-logo">
                    <img src="${reportSettings.logo_path || 'assets/images/school_logo.png'}" alt="โลโก้วิทยาลัย">
                </div>
                <div class="report-title">
                    <h1>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</h1>
                    <h2>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</h2>
                    <h3>ภาคเรียนที่ ${academicYear.semester} ปีการศึกษา ${academicYear.year + 543} สัปดาห์ที่ ${weekNumber} เดือน ${month} พ.ศ. ${thaiYear}</h3>
                    <h3>ระหว่างวันที่ ${formatThaiDate(startDate)} ถึง วันที่ ${formatThaiDate(endDate)}</h3>
                    <h3>ระดับชั้น ${classLevel} กลุ่ม ${groupNumber} แผนกวิชา${departmentName}</h3>
                </div>
            </div>
            
            <div class="attendance-table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="no-col">ลำดับที่</th>
                            <th rowspan="2" class="code-col">รหัสนักศึกษา</th>
                            <th rowspan="2" class="name-col">ชื่อ-สกุล</th>
                            <th colspan="${workDays.length}" class="week-header">สัปดาห์ที่ ${weekNumber}</th>
                            <th rowspan="2" class="total-col">รวม</th>
                            <th rowspan="2" class="remark-col">หมายเหตุ</th>
                        </tr>
                        <tr class="day-header">
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        `;
        
        // เพิ่มหัวข้อวันในตาราง
        const dayHeaderRow = reportDOM.querySelector('.day-header');
        
        workDays.forEach((day, index) => {
            const dayDate = new Date(day);
            const dayOfWeek = dayDate.getDay();
            const dayCell = document.createElement('th');
            dayCell.className = 'day-col';
            dayCell.innerHTML = `${index + 1}<br>${thaiDaysShort[dayOfWeek]}<br>${dayDate.getDate()}`;
            dayHeaderRow.appendChild(dayCell);
        });
        
        // เพิ่มข้อมูลนักเรียนลงในตาราง
        const tableBody = reportDOM.querySelector('tbody');
        
        // ตัวแปรสำหรับสรุปข้อมูล
        let totalPresent = 0;
        let totalAbsent = 0;
        let totalLate = 0;
        let totalLeave = 0;
        
        reportData.students.forEach((student, index) => {
            const row = document.createElement('tr');
            
            // สร้างเซลล์ข้อมูลพื้นฐาน
            row.innerHTML = `
                <td class="no-col">${index + 1}</td>
                <td class="code-col">${student.student_code}</td>
                <td class="name-col">${student.title}${student.first_name} ${student.last_name}</td>
            `;
            
            // ตัวแปรสำหรับนับการเข้าแถวของนักเรียนคนนี้
            let studentPresent = 0;
            let studentAbsent = 0;
            let studentLate = 0;
            let studentLeave = 0;
            
            // วนลูปผ่านวันในสัปดาห์ (จันทร์-ศุกร์)
            workDays.forEach(day => {
                // สร้างเซลล์สำหรับวันนี้
                const dayCell = document.createElement('td');
                dayCell.className = 'day-col';
                
                // ตรวจสอบวันหยุด
                if (holidays[day]) {
                    dayCell.textContent = 'หยุด';
                    dayCell.classList.add('holiday');
                    dayCell.title = holidays[day]; // แสดง tooltip เหตุผลวันหยุด
                } else {
                    // ดึงข้อมูลการเข้าแถวสำหรับวันนี้
                    const attendance = student.attendances[day];
                    
                    if (attendance) {
                        const status = attendance.status;
                        dayCell.textContent = getStatusSymbol(status);
                        dayCell.classList.add(status);
                        
                        // นับสถานะการเข้าแถว
                        if (status === 'present') {
                            studentPresent++;
                            totalPresent++;
                        } else if (status === 'absent') {
                            studentAbsent++;
                            totalAbsent++;
                        } else if (status === 'late') {
                            studentLate++;
                            totalLate++;
                            // นับว่ามาเรียนด้วย แต่สาย
                            studentPresent++;
                            totalPresent++;
                        } else if (status === 'leave') {
                            studentLeave++;
                            totalLeave++;
                        }
                    } else {
                        dayCell.textContent = '-';
                    }
                }
                
                row.appendChild(dayCell);
            });
            
            // เพิ่มเซลล์รวมและหมายเหตุ
            const totalCell = document.createElement('td');
            totalCell.className = 'total-col';
            totalCell.textContent = studentPresent;
            row.appendChild(totalCell);
            
            const remarkCell = document.createElement('td');
            remarkCell.className = 'remark-col';
            
            // สร้างหมายเหตุอัตโนมัติ
            let remarks = [];
            if (studentAbsent > 0) remarks.push(`ขาด ${studentAbsent} วัน`);
            if (studentLate > 0) remarks.push(`สาย ${studentLate} วัน`);
            if (studentLeave > 0) remarks.push(`ลา ${studentLeave} วัน`);
            
            remarkCell.textContent = remarks.join(', ');
            row.appendChild(remarkCell);
            
            // เพิ่มแถวลงในตาราง
            tableBody.appendChild(row);
        });
        
        // คำนวณอัตราการเข้าแถว
        const totalStudents = reportData.students.length;
        const totalAttendanceDays = workDays.length * totalStudents;
        const attendanceRate = totalAttendanceDays > 0 ? 
            ((totalPresent) / (totalAttendanceDays - totalLeave) * 100).toFixed(2) : '0.00';
        
        // เพิ่มส่วนสรุปและลายเซ็น
        const summarySection = document.createElement('div');
        summarySection.innerHTML = `
            <div class="report-summary">
                <p>สรุป จำนวนคน ${totalStudents} มา ${totalPresent} ขาด ${totalAbsent} สาย ${totalLate} ลา ${totalLeave}</p>
                <p>สรุปจำนวนนักเรียนเข้าแถวร้อยละ ${attendanceRate}</p>
            </div>
            
            <div class="report-footer">
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line">ลงชื่อ........................................</div>
                        <div class="signature-name">(${reportData.advisors?.[0]?.title || ''} ${reportData.advisors?.[0]?.first_name || 'มนตรี'} ${reportData.advisors?.[0]?.last_name || 'ศรีสุข'})</div>
                        <div class="signature-title">ครูที่ปรึกษา</div>
                    </div>
                    
                    <div class="signature-box">
                        <div class="signature-line">ลงชื่อ........................................</div>
                        <div class="signature-name">(${reportSettings.activity_head_name || 'นายมนตรี ศรีสุข'})</div>
                        <div class="signature-title">${reportSettings.activity_head_title || 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา'}</div>
                    </div>
                    
                    <div class="signature-box">
                        <div class="signature-line">ลงชื่อ........................................</div>
                        <div class="signature-name">(${reportSettings.director_deputy_name || 'นายพงษ์ศักดิ์ สนโศรก'})</div>
                        <div class="signature-title">รองผู้อำนวยการ</div>
                        <div class="signature-subtitle">ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
                    </div>
                </div>
            </div>
        `;
        
        // เพิ่มส่วนสรุปลงในรายงาน
        reportDOM.appendChild(summarySection);
        
        // แสดงรายงาน
        reportContent.innerHTML = '';
        reportContent.appendChild(reportDOM);
        reportContainer.style.display = 'block';
        reportPlaceholder.style.display = 'none';
        
        // สร้างกราฟแสดงอัตราการเข้าแถว
        createAttendanceChart(workDays, reportData.students);
        chartsContainer.style.display = 'block';
    }
    
    // สร้างกราฟแสดงอัตราการเข้าแถว
    function createAttendanceChart(days, students) {
        const canvas = document.getElementById('attendance-chart');
        if (!canvas || !Chart) return;
        
        // คำนวณอัตราการเข้าแถวในแต่ละวัน
        const dailyRates = [];
        const labels = [];
        
        days.forEach(day => {
            // ข้ามวันเสาร์-อาทิตย์
            const dayOfWeek = new Date(day).getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) return;
            
            // ข้ามวันหยุด
            if (holidays[day]) return;
            
            // นับจำนวนนักเรียนที่มาเรียนในวันนี้
            let presentCount = 0;
            let totalCount = 0;
            
            students.forEach(student => {
                const attendance = student.attendances[day];
                if (attendance) {
                    totalCount++;
                    if (attendance.status === 'present' || attendance.status === 'late') {
                        presentCount++;
                    }
                }
            });
            
            // คำนวณอัตราการเข้าแถว
            if (totalCount > 0) {
                dailyRates.push((presentCount / totalCount) * 100);
                
                const date = new Date(day);
                const dayOfWeek = date.getDay();
                labels.push(`${date.getDate()} ${thaiMonths[date.getMonth()].substring(0, 3)} (${thaiDaysShort[dayOfWeek]})`);
            }
        });
        
        // ลบกราฟเดิมถ้ามี
        if (window.attendanceChart) {
            window.attendanceChart.destroy();
        }
        
        // สร้างกราฟใหม่
        window.attendanceChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: dailyRates,
                    fill: true,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7
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
                    title: {
                        display: true,
                        text: 'อัตราการเข้าแถวประจำวัน',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // สร้างฟังก์ชันแปลงสถานะเป็นสัญลักษณ์
    function getStatusSymbol(status) {
        const symbols = {
            'present': '✓',
            'absent': 'x',
            'late': 'ส',
            'leave': 'ล'
        };
        return symbols[status] || '-';
    }
    
    // สร้างรายการวันในสัปดาห์
    function getWeekDays(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = [];
        
        // สร้างรายการวันในช่วงวันที่กำหนด
        const currentDate = new Date(start);
        while (currentDate <= end) {
            days.push(currentDate.toISOString().split('T')[0]);
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        return days;
    }
    
    // แปลงวันที่เป็นรูปแบบไทย (วันที่ เดือน พ.ศ.)
    function formatThaiDate(dateStr) {
        const date = new Date(dateStr);
        const day = date.getDate();
        const month = thaiMonths[date.getMonth()];
        const thaiYear = date.getFullYear() + 543;
        
        return `${day} ${month} ${thaiYear}`;
    }
    
    // พิมพ์รายงาน
    function printReport() {
        // ซ่อนปุ่มและส่วนควบคุมก่อนพิมพ์
        document.body.classList.add('printing');
        
        // เรียกใช้ window.print() หลังจากเตรียม DOM เสร็จแล้ว
        setTimeout(() => {
            window.print();
            // หลังจากพิมพ์เสร็จ นำคลาสออก
            setTimeout(() => {
                document.body.classList.remove('printing');
            }, 1000);
        }, 300);
    }
    
    // ส่งออกเป็น PDF
    function exportToPdf() {
        showLoading();
        
        // ตรวจสอบว่ามีข้อมูลรายงานหรือไม่
        if (!lastReportData) {
            alert('กรุณาสร้างรายงานก่อนส่งออก');
            hideLoading();
            return;
        }
        
        // วิธีที่ 1: ใช้ server-side PDF generation (MPDF)
        const classId = classSelect.value;
        const weekNumber = weekSelect.value;
        window.location.href = `print_activity_report.php?export=pdf&class_id=${classId}&week=${weekNumber}`;
        
        // ซ่อน loading หลังจากเปิดหน้าใหม่
        setTimeout(() => {
            hideLoading();
        }, 1000);
    }
    
    // ส่งออกเป็น Excel
    function exportToExcel() {
        showLoading();
        
        // ตรวจสอบว่ามีข้อมูลรายงานหรือไม่
        if (!lastReportData) {
            alert('กรุณาสร้างรายงานก่อนส่งออก');
            hideLoading();
            return;
        }
        
        try {
            // ดึงข้อมูลจากตาราง
            const table = document.querySelector('.attendance-table');
            const rows = table.querySelectorAll('tbody tr');
            
            // สร้างข้อมูลหัวตาราง
            const headerRow = table.querySelector('thead tr:nth-child(2)');
            const headers = ['ลำดับที่', 'รหัสนักศึกษา', 'ชื่อ-สกุล'];
            
            // เพิ่มวันในสัปดาห์
            const dayHeaders = headerRow.querySelectorAll('th');
            dayHeaders.forEach(th => {
                headers.push(th.innerText.replace(/\n/g, ' '));
            });
            
            // เพิ่มคอลัมน์รวมและหมายเหตุ
            headers.push('รวม', 'หมายเหตุ');
            
            // สร้างข้อมูลสำหรับ Excel
            const data = [];
            data.push(headers);
            
            // เพิ่มข้อมูลจากแต่ละแถว
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(cell => {
                    rowData.push(cell.textContent.trim());
                });
                data.push(rowData);
            });
            
            // เพิ่มข้อมูลสรุป
            const summaryText = document.querySelector('.report-summary p:nth-child(1)').textContent;
            const rateText = document.querySelector('.report-summary p:nth-child(2)').textContent;
            
            data.push([]);
            data.push([summaryText]);
            data.push([rateText]);
            
            // เพิ่มข้อมูลลายเซ็น
            data.push([]);
            data.push([]);
            data.push(['ลงชื่อ.........................................', '', '', '', 'ลงชื่อ.........................................', '', '', '', 'ลงชื่อ........................................']);
            
            const signatures = document.querySelectorAll('.signature-name');
            data.push([
                '(' + signatures[0].textContent.replace(/[()]/g, '') + ')',
                '', '', '',
                '(' + signatures[1].textContent.replace(/[()]/g, '') + ')',
                '', '', '',
                '(' + signatures[2].textContent.replace(/[()]/g, '') + ')'
            ]);
            
            const titles = document.querySelectorAll('.signature-title');
            data.push([
                titles[0].textContent,
                '', '', '',
                titles[1].textContent,
                '', '', '',
                titles[2].textContent
            ]);
            
            // สร้าง workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            // ตั้งค่าความกว้างของคอลัมน์
            const colWidths = [
                { wch: 10 }, // ลำดับที่
                { wch: 15 }, // รหัสนักศึกษา
                { wch: 30 }  // ชื่อ-สกุล
            ];
            
            // เพิ่มความกว้างสำหรับวันในสัปดาห์
            for (let i = 0; i < dayHeaders.length; i++) {
                colWidths.push({ wch: 10 });
            }
            
            // เพิ่มความกว้างสำหรับคอลัมน์รวมและหมายเหตุ
            colWidths.push({ wch: 10 }, { wch: 20 });
            
            ws['!cols'] = colWidths;
            
            // เพิ่ม worksheet ลงใน workbook
            XLSX.utils.book_append_sheet(wb, ws, 'รายงานเช็คชื่อเข้าแถว');
            
            // ดึงข้อมูลชั้นเรียนและสัปดาห์
            const className = `${lastReportData.class_level}${lastReportData.group_number}`;
            const weekNum = lastReportData.week_number;
            
            // บันทึกไฟล์ Excel
            XLSX.writeFile(wb, `รายงานเช็คชื่อเข้าแถว_${className}_สัปดาห์${weekNum}.xlsx`);
            
            hideLoading();
        } catch (error) {
            console.error('Error exporting to Excel:', error);
            alert('เกิดข้อผิดพลาดในการสร้างไฟล์ Excel: ' + error.message);
            hideLoading();
        }
    }
    
    // สร้างรายงานเฉพาะกราฟ
    function printChartOnly() {
        if (!lastReportData) {
            alert('กรุณาสร้างรายงานก่อนพิมพ์กราฟ');
            return;
        }
        
        // สร้างหน้าเอกสารใหม่สำหรับพิมพ์กราฟ
        const printWin = window.open('', '_blank');
        
        // ดึงข้อมูลจากรายงานล่าสุด
        const { class_level, group_number, department_name, week_number } = lastReportData;
        
        // สร้าง HTML สำหรับหน้าพิมพ์กราฟ
        printWin.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>กราฟการเข้าแถว</title>
                <style>
                    body {
                        font-family: 'TH Sarabun New', sans-serif;
                        text-align: center;
                        padding: 20px;
                    }
                    .report-header h1 {
                        font-size: 20pt;
                        margin-bottom: 10px;
                    }
                    .report-header h2 {
                        font-size: 18pt;
                        margin-bottom: 10px;
                    }
                    .report-header h3 {
                        font-size: 16pt;
                        margin-bottom: 5px;
                    }
                    .chart-container {
                        margin: 20px auto;
                        max-width: 800px;
                    }
                    img {
                        max-width: 100%;
                        height: auto;
                    }
                </style>
            </head>
            <body>
                <div class="report-header">
                    <h1>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</h1>
                    <h2>กราฟแสดงอัตราการเข้าแถวรายวัน</h2>
                    <h3>ภาคเรียนที่ ${academicYear.semester} ปีการศึกษา ${academicYear.year + 543} สัปดาห์ที่ ${week_number}</h3>
                    <h3>ระดับชั้น ${class_level} กลุ่ม ${group_number} แผนกวิชา${department_name}</h3>
                </div>
                <div class="chart-container">
                    <img id="chart-image" src="" alt="กราฟอัตราการเข้าแถว">
                </div>
            </body>
            </html>
        `);
        
        // ดึงภาพกราฟ
        const canvas = document.getElementById('attendance-chart');
        const dataUrl = canvas.toDataURL('image/png');
        
        // นำภาพกราฟไปใส่ในหน้าพิมพ์
        const img = printWin.document.getElementById('chart-image');
        img.src = dataUrl;
        
        // พิมพ์เมื่อภาพโหลดเสร็จ
        img.onload = function() {
            setTimeout(() => {
                printWin.print();
                // ปิดหน้าต่างหลังจากพิมพ์
                setTimeout(() => {
                    printWin.close();
                }, 500);
            }, 500);
        };
    }
    
    // แสดง loading
    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }
    
    // ซ่อน loading
    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }
});
/**
 * print_activity.js - จัดการหน้าพิมพ์รายงานผลกิจกรรมเข้าแถว (ปรับปรุง)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
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
    const reportPlaceholder = document.getElementById('report-placeholder');
    const reportTemplate = document.getElementById('report-template');
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
        hideLoading();
        
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
    function generateReport() {
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
        
        // ดึงข้อมูลรายงานจาก API
        fetch('api/attendance.php?action=get_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // สร้างรายงาน
                    createReportContent(data.report_data, classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
                } else {
                    alert('ไม่สามารถดึงข้อมูลรายงานได้: ' + (data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                }
                hideLoading();
            })
            .catch(error => {
                console.error('Error fetching report data:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน');
                hideLoading();
                
                // กรณีทดสอบระบบโดยไม่มี API
                simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
            });
    }
    
    // จำลองข้อมูลรายงานสำหรับการทดสอบ
    function simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate) {
        hideLoading();
        
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
        
        // สร้างเนื้อหารายงาน
        createReportContent(reportData, classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
    }
    
    // สร้างเนื้อหารายงาน
    function createReportContent(reportData, classLevel, groupNumber, departmentName, weekNumber, startDate, endDate) {
        // ดึงข้อมูลเทมเพลตรายงาน
        const template = reportTemplate.innerHTML;
        
        // ดึงข้อมูลวันในสัปดาห์
        const weekDays = reportData.week_days || getWeekDays(startDate, endDate);
        
        // ข้อมูลเดือนและปีสำหรับหัวรายงาน
        const reportDate = new Date(startDate);
        const month = thaiMonths[reportDate.getMonth()];
        const year = reportDate.getFullYear();
        const thaiYear = year + 543;
        
        // แทนที่ค่าในเทมเพลต
        let report = template
            .replace('{semester}', academicYear.semester)
            .replace('{year}', academicYear.year + 543)
            .replace('{week}', weekNumber)
            .replace('{month}', month)
            .replace('{thai_year}', thaiYear)
            .replace('{class_level}', classLevel)
            .replace('{group_number}', groupNumber)
            .replace('{department_name}', departmentName)
            .replace('{advisor_name}', reportData.advisors?.[0]?.first_name + ' ' + reportData.advisors?.[0]?.last_name || 'มนตรี ศรีสุข')
            .replace('{activity_head_name}', reportSettings.activity_head_name || 'มนตรี ศรีสุข')
            .replace('{director_deputy_name}', reportSettings.director_deputy_name || 'พงษ์ศักดิ์ สนโศรก');
        
        // สร้าง DOM จากรายงาน
        const reportDOM = document.createElement('div');
        reportDOM.innerHTML = report;
        
        // หาตารางและหัวข้อวัน
        const table = reportDOM.querySelector('.attendance-table');
        const dayHeaderRow = reportDOM.querySelector('.day-header');
        const weekHeaderCell = reportDOM.querySelector('.week-header');
        
        // ล้างข้อมูลวันเดิม
        while (dayHeaderRow.children.length > 0) {
            dayHeaderRow.removeChild(dayHeaderRow.lastChild);
        }
        
        // เพิ่มเฉพาะวันจันทร์-ศุกร์ (วันที่ 1-5 ของสัปดาห์)
        const workDays = weekDays.filter(day => {
            const date = new Date(day);
            const dayOfWeek = date.getDay();
            return dayOfWeek >= 1 && dayOfWeek <= 5; // จันทร์-ศุกร์
        });
        
        // อัปเดต colspan ของหัวตาราง
        weekHeaderCell.setAttribute('colspan', workDays.length + 2); // +2 สำหรับคอลัมน์รวมและหมายเหตุ
        
        // เพิ่มหัวข้อวันในตาราง
        workDays.forEach((day, index) => {
            const dayDate = new Date(day);
            const dayOfWeek = dayDate.getDay();
            const dayCell = document.createElement('th');
            dayCell.className = 'day-col';
            dayCell.innerHTML = `${index + 1}<br>${thaiDaysShort[dayOfWeek]}`;
            dayHeaderRow.appendChild(dayCell);
        });
        
        // เพิ่มคอลัมน์รวม
        const totalHeaderCell = document.createElement('th');
        totalHeaderCell.className = 'total-col';
        totalHeaderCell.textContent = 'รวม';
        dayHeaderRow.appendChild(totalHeaderCell);
        
        // สร้างแถวข้อมูลนักเรียน
        const tableBody = reportDOM.querySelector('tbody');
        tableBody.innerHTML = '';
        
        // ตัวแปรสำหรับสรุปข้อมูล
        let totalPresent = 0;
        let totalAbsent = 0;
        let totalLate = 0;
        let totalLeave = 0;
        
        // เพิ่มข้อมูลนักเรียนลงในตาราง
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
            totalCell.textContent = studentPresent; // จำนวนวันที่มา (รวมสาย)
            row.appendChild(totalCell);
            
            const remarkCell = document.createElement('td');
            remarkCell.className = 'remark-col';
            
            // สร้างหมายเหตุอัตโนมัติ
            let remarks = [];
            if (studentAbsent > 0) remarks.push(`ขาด ${studentAbsent} วัน`);
            if (studentLate > 0) remarks.push(`สาย ${studentLate} วัน`);
            if (studentLeave > 0) remarks.push(`ลา ${studentLeave} วัน`);
            
            remarkCell.textContent = remarks.join(', ') || (student.remarks || '');
            row.appendChild(remarkCell);
            
            // เพิ่มแถวลงในตาราง
            tableBody.appendChild(row);
        });
        
        // อัปเดตข้อมูลสรุป
        const totalStudents = reportData.students.length;
        const totalAttendanceDays = workDays.length * totalStudents;
        const attendanceRate = totalAttendanceDays > 0 ? 
            ((totalPresent) / (totalAttendanceDays - (totalLeave)) * 100).toFixed(2) : '0.00';
        
        const summaryText = reportDOM.querySelector('.report-summary p:nth-child(2)');
        summaryText.textContent = `จำนวนคน ${totalStudents} มา ${totalPresent} ขาด ${totalAbsent} สาย ${totalLate} ลา ${totalLeave}`;
        
        const rateText = reportDOM.querySelector('.report-summary p:nth-child(3)');
        rateText.textContent = `สรุปจำนวนนักเรียนเข้าแถวร้อยละ ${attendanceRate}`;
        
        // เพิ่มกราฟแสดงอัตราการเข้าแถว
        const graphContainer = document.createElement('div');
        graphContainer.className = 'attendance-graph-container';
        graphContainer.innerHTML = `
            <h3>กราฟแสดงอัตราการเข้าแถวรายวัน</h3>
            <div class="attendance-graph">
                <canvas id="attendanceChart" width="800" height="300"></canvas>
            </div>
        `;
        
        // แทรกกราฟก่อนส่วนสรุป
        const reportFooter = reportDOM.querySelector('.report-footer');
        reportFooter.parentNode.insertBefore(graphContainer, reportFooter);
        
        // แสดงรายงาน
        reportContent.innerHTML = '';
        reportContent.appendChild(reportDOM);
        reportContainer.style.display = 'block';
        reportPlaceholder.style.display = 'none';
        
        // เปิดใช้งานปุ่มพิมพ์และส่งออก
        printReportBtn.disabled = false;
        exportPdfBtn.disabled = false;
        exportExcelBtn.disabled = false;
        
        // สร้างกราฟหลังจากที่ DOM ถูกเพิ่มเข้าไปในเอกสาร
        setTimeout(() => {
            createAttendanceChart(workDays, reportData.students);
        }, 100);
    }
    
    // สร้างกราฟแสดงอัตราการเข้าแถว
    function createAttendanceChart(days, students) {
        const canvas = document.getElementById('attendanceChart');
        if (!canvas) return;
        
        // คำนวณอัตราการเข้าแถวในแต่ละวัน
        const dailyRates = days.map(day => {
            // ข้ามวันเสาร์-อาทิตย์
            const dayOfWeek = new Date(day).getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) return null;
            
            // ข้ามวันหยุด
            if (holidays[day]) return null;
            
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
            return totalCount > 0 ? (presentCount / totalCount) * 100 : 0;
        }).filter(rate => rate !== null);
        
        // สร้างข้อมูลวันที่
        const labels = days.filter(day => {
            const dayOfWeek = new Date(day).getDay();
            return dayOfWeek >= 1 && dayOfWeek <= 5 && !holidays[day];
        }).map(day => {
            const date = new Date(day);
            const dayOfWeek = date.getDay();
            return `${date.getDate()}/${date.getMonth() + 1} (${thaiDaysShort[dayOfWeek]})`;
        });
        
        // สร้างกราฟโดยใช้ Chart.js
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: dailyRates,
                    fill: true,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
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
        
        // เตรียม DOM สำหรับส่งออก
        const reportElement = document.getElementById('report-content');
        document.body.classList.add('exporting-pdf');
        
        // ตั้งค่าสำหรับ html2pdf
        const opt = {
            margin: [10, 10, 10, 10],
            filename: `รายงานเช็คชื่อเข้าแถว_${classSelect.options[classSelect.selectedIndex].textContent}_สัปดาห์${weekSelect.value}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2,
                letterRendering: true,
                useCORS: true
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'portrait' 
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };
        
        // ใช้ html2pdf.js
        html2pdf()
            .from(reportElement)
            .set(opt)
            .toPdf()
            .get('pdf')
            .then((pdf) => {
                // กำหนดฟอนต์สำหรับภาษาไทย (ถ้ามี)
                if (typeof pdf.addFont === 'function') {
                    try {
                        pdf.addFont('THSarabun.ttf', 'THSarabun', 'normal');
                        pdf.addFont('THSarabun-Bold.ttf', 'THSarabun', 'bold');
                        pdf.setFont('THSarabun');
                    } catch (e) {
                        console.warn('Could not set Thai font:', e);
                    }
                }
                return pdf;
            })
            .save()
            .then(() => {
                document.body.classList.remove('exporting-pdf');
                hideLoading();
            });
    }
    
    // ส่งออกเป็น Excel
    function exportToExcel() {
        showLoading();
        
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
                if (th.classList.contains('day-col')) {
                    headers.push(th.innerText.replace('\n', ' '));
                } else if (th.classList.contains('total-col')) {
                    headers.push('รวม');
                } else if (th.classList.contains('remark-col')) {
                    headers.push('หมายเหตุ');
                }
            });
            
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
            const summaryText = document.querySelector('.report-summary p:nth-child(2)').textContent;
            const rateText = document.querySelector('.report-summary p:nth-child(3)').textContent;
            
            data.push(['']);
            data.push(['สรุป']);
            data.push([summaryText]);
            data.push([rateText]);
            
            // เพิ่มข้อมูลลายเซ็น
            data.push(['']);
            data.push(['']);
            data.push(['']);
            data.push(['ลงชื่อ.........................................', '', '', '', '', 'ลงชื่อ.........................................', '', '', '', '', 'ลงชื่อ........................................']);
            data.push(['(', document.querySelector('.signature-name').textContent.replace(/[()]/g, ''), ')', '', '', '(', document.querySelectorAll('.signature-name')[1].textContent.replace(/[()]/g, ''), ')', '', '', '(', document.querySelectorAll('.signature-name')[2].textContent.replace(/[()]/g, ''), ')']);
            data.push([document.querySelector('.signature-title').textContent, '', '', '', '', document.querySelectorAll('.signature-title')[1].textContent, '', '', '', '', document.querySelectorAll('.signature-title')[2].textContent]);
            
            // สร้าง workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            
            // ตั้งค่าความกว้างของคอลัมน์
            ws['!cols'] = [
                { width: 10 }, // ลำดับที่
                { width: 15 }, // รหัสนักศึกษา
                { width: 30 }, // ชื่อ-สกุล
                { width: 10 }, // วันที่ 1
                { width: 10 }, // วันที่ 2
                { width: 10 }, // วันที่ 3
                { width: 10 }, // วันที่ 4
                { width: 10 }, // วันที่ 5
                { width: 10 }, // รวม
                { width: 20 }  // หมายเหตุ
            ];
            
            // เพิ่ม worksheet ลงใน workbook
            XLSX.utils.book_append_sheet(wb, ws, 'รายงานเช็คชื่อเข้าแถว');
            
            // ดึงข้อมูลชั้นเรียนและสัปดาห์
            const className = classSelect.options[classSelect.selectedIndex].textContent;
            const weekNum = weekSelect.value;
            
            // บันทึกไฟล์ Excel
            XLSX.writeFile(wb, `รายงานเช็คชื่อเข้าแถว_${className}_สัปดาห์${weekNum}.xlsx`);
            
            hideLoading();
        } catch (error) {
            console.error('Error exporting to Excel:', error);
            alert('เกิดข้อผิดพลาดในการสร้างไฟล์ Excel: ' + error.message);
            hideLoading();
        }
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
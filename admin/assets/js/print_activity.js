/**
 * print_activity.js - จัดการหน้าพิมพ์รายงานผลกิจกรรมเข้าแถว
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
        fetch(`api/get_classes.php?department_id=${departmentId}&academic_year_id=${academicYear.academic_year_id}`)
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
                    alert('ไม่สามารถดึงข้อมูลชั้นเรียนได้');
                }
                hideLoading();
            })
            .catch(error => {
                console.error('Error fetching classes:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน');
                hideLoading();
            });
            
        // สำหรับการทดสอบ (จำลองข้อมูล)
        // สามารถลบส่วนนี้ในระบบจริง
        simulateClassData(departmentId);
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
        fetch('api/get_attendance_report.php', {
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
                    alert('ไม่สามารถดึงข้อมูลรายงานได้');
                }
                hideLoading();
            })
            .catch(error => {
                console.error('Error fetching report data:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน');
                hideLoading();
                
                // สำหรับการทดสอบ (จำลองข้อมูล)
                simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
            });
            
        // สำหรับการทดสอบ (จำลองข้อมูล)
        // สามารถลบส่วนนี้ในระบบจริง
        simulateReportData(classLevel, groupNumber, departmentName, weekNumber, startDate, endDate);
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
        
        // สร้างข้อมูลวันในตาราง
        const dayHeaders = reportDOM.querySelector('.day-header');
        const weekHeader = reportDOM.querySelector('.week-header');
        weekHeader.setAttribute('colspan', weekDays.length + 2); // +2 สำหรับคอลัมน์รวมและหมายเหตุ
        
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
            weekDays.forEach(day => {
                // ข้ามวันเสาร์-อาทิตย์
                const dayDate = new Date(day);
                const dayOfWeek = dayDate.getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) return;
                
                // สร้างเซลล์สำหรับวันนี้
                const dayCell = document.createElement('td');
                dayCell.className = 'day-col';
                
                // ตรวจสอบวันหยุด
                if (holidays[day]) {
                    dayCell.textContent = 'หยุด';
                    dayCell.classList.add('holiday');
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
            totalCell.textContent = studentPresent + studentLate;
            row.appendChild(totalCell);
            
            const remarkCell = document.createElement('td');
            remarkCell.className = 'remark-col';
            remarkCell.textContent = student.remarks || '';
            row.appendChild(remarkCell);
            
            // เพิ่มแถวลงในตาราง
            tableBody.appendChild(row);
        });
        
        // อัปเดตข้อมูลสรุป
        const totalStudents = reportData.students.length;
        const attendanceRate = ((totalPresent + totalLate) / (totalPresent + totalAbsent + totalLate + totalLeave) * 100).toFixed(2);
        
        const summaryText = reportDOM.querySelector('.report-summary p:nth-child(2)');
        summaryText.textContent = `จำนวนคน ${totalStudents} มา ${totalPresent} ขาด ${totalAbsent} สาย ${totalLate} ลา ${totalLeave}`;
        
        const rateText = reportDOM.querySelector('.report-summary p:nth-child(3)');
        rateText.textContent = `สรุปจำนวนนักเรียนเข้าแถวร้อยละ ${attendanceRate}`;
        
        // แสดงรายงาน
        reportContent.innerHTML = '';
        reportContent.appendChild(reportDOM);
        reportContainer.style.display = 'block';
        reportPlaceholder.style.display = 'none';
        
        // เปิดใช้งานปุ่มพิมพ์และส่งออก
        printReportBtn.disabled = false;
        exportPdfBtn.disabled = false;
        exportExcelBtn.disabled = false;
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
        window.print();
    }
    
    // ส่งออกเป็น PDF
    function exportToPdf() {
        showLoading();
        
        const reportElement = document.getElementById('report-content');
        const opt = {
            margin: 10,
            filename: 'รายงานเช็คชื่อเข้าแถว.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        // ใช้ html2pdf.js
        html2pdf().from(reportElement).set(opt).save().then(() => {
            hideLoading();
        });
    }
    
    // ส่งออกเป็น Excel
    function exportToExcel() {
        showLoading();
        
        // ดึงข้อมูลจากตาราง
        const table = document.querySelector('.attendance-table');
        const rows = table.querySelectorAll('tbody tr');
        const headers = [
            'ลำดับที่', 'รหัสนักศึกษา', 'ชื่อ-สกุล',
            'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์',
            'รวม', 'หมายเหตุ'
        ];
        
        // สร้างข้อมูลสำหรับ Excel
        const data = [];
        data.push(headers);
        
        // เพิ่มข้อมูลจากแต่ละแถว
        rows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach(cell => {
                rowData.push(cell.textContent);
            });
            data.push(rowData);
        });
        
        // สร้าง workbook
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(data);
        
        // ตั้งค่าความกว้างของคอลัมน์
        ws['!cols'] = [
            { width: 10 }, // ลำดับที่
            { width: 15 }, // รหัสนักศึกษา
            { width: 30 }, // ชื่อ-สกุล
            { width: 10 }, // จันทร์
            { width: 10 }, // อังคาร
            { width: 10 }, // พุธ
            { width: 10 }, // พฤหัสบดี
            { width: 10 }, // ศุกร์
            { width: 10 }, // รวม
            { width: 20 }  // หมายเหตุ
        ];
        
        // เพิ่ม worksheet ลงใน workbook
        XLSX.utils.book_append_sheet(wb, ws, 'รายงานเช็คชื่อเข้าแถว');
        
        // บันทึกไฟล์ Excel
        XLSX.writeFile(wb, 'รายงานเช็คชื่อเข้าแถว.xlsx');
        
        hideLoading();
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
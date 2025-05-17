/**
 * attendance_report.js - JavaScript สำหรับหน้าค้นหาและพิมพ์รายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

document.addEventListener('DOMContentLoaded', function() {
    // อ้างอิง DOM elements
    const departmentSelect = document.getElementById('department_id');
    const levelSelect = document.getElementById('level');
    const groupSelect = document.getElementById('group');
    const advisorSelect = document.getElementById('advisor_id');
    const weekSelect = document.getElementById('week');
    const resetBtn = document.getElementById('resetBtn');
    const searchForm = document.getElementById('reportSearchForm');
    const resultsCard = document.getElementById('resultsCard');
    const resultsBody = document.getElementById('resultsBody');
    const previewCard = document.getElementById('previewCard');
    const reportPreview = document.getElementById('reportPreview');
    const closePreviewBtn = document.getElementById('closePreviewBtn');
    const closeLinkBtn = document.getElementById('closeLinkBtn');
    const pdfBtn = document.getElementById('pdfBtn');
    const excelBtn = document.getElementById('excelBtn');
    const chartBtn = document.getElementById('chartBtn');
    const downloadAllBtn = document.getElementById('downloadAllBtn');
    
    // เมื่อเลือกแผนกวิชาหรือระดับชั้น ให้ดึงข้อมูลกลุ่ม
    function updateGroups() {
        if (departmentSelect.value && levelSelect.value) {
            // Reset และ disable กลุ่ม
            groupSelect.innerHTML = '<option value="">-- กำลังโหลดข้อมูล --</option>';
            groupSelect.disabled = true;
            advisorSelect.innerHTML = '<option value="">-- กรุณาเลือกชั้นเรียนก่อน --</option>';
            advisorSelect.disabled = true;
            
            // ดึงข้อมูลกลุ่ม
            fetch(`attendance_report.php?ajax=get_groups&department_id=${departmentSelect.value}&level=${levelSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`)
                .then(response => response.json())
                .then(data => {
                    groupSelect.innerHTML = '<option value="">-- เลือกกลุ่ม --</option>';
                    
                    if (data.length > 0) {
                        data.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group;
                            option.textContent = `กลุ่ม ${group}`;
                            groupSelect.appendChild(option);
                        });
                        groupSelect.disabled = false;
                    } else {
                        groupSelect.innerHTML = '<option value="">-- ไม่พบข้อมูลกลุ่ม --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    groupSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาดในการโหลดข้อมูล --</option>';
                });
        } else {
            // Reset และ disable กลุ่ม
            groupSelect.innerHTML = '<option value="">-- เลือกกลุ่ม --</option>';
            groupSelect.disabled = true;
            advisorSelect.innerHTML = '<option value="">-- กรุณาเลือกชั้นเรียนก่อน --</option>';
            advisorSelect.disabled = true;
        }
    }
    
    // เมื่อเลือกกลุ่ม ให้ดึงข้อมูลครูที่ปรึกษา
    function updateAdvisors() {
        if (departmentSelect.value && levelSelect.value && groupSelect.value) {
            // Reset และ disable ครูที่ปรึกษา
            advisorSelect.innerHTML = '<option value="">-- กำลังโหลดข้อมูล --</option>';
            advisorSelect.disabled = true;
            
            // ดึงข้อมูลครูที่ปรึกษา
            fetch(`attendance_report.php?ajax=get_advisors&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`)
                .then(response => response.json())
                .then(data => {
                    advisorSelect.innerHTML = '<option value="">-- ทั้งหมด --</option>';
                    
                    if (data.length > 0) {
                        data.forEach(advisor => {
                            const option = document.createElement('option');
                            option.value = advisor.teacher_id;
                            option.textContent = `${advisor.title}${advisor.first_name} ${advisor.last_name}`;
                            if (advisor.is_primary) {
                                option.textContent += ' (ครูที่ปรึกษาหลัก)';
                                option.selected = true;
                            }
                            advisorSelect.appendChild(option);
                        });
                        advisorSelect.disabled = false;
                    } else {
                        advisorSelect.innerHTML = '<option value="">-- ไม่พบข้อมูลครูที่ปรึกษา --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    advisorSelect.innerHTML = '<option value="">-- เกิดข้อผิดพลาดในการโหลดข้อมูล --</option>';
                });
        } else {
            // Reset และ disable ครูที่ปรึกษา
            advisorSelect.innerHTML = '<option value="">-- กรุณาเลือกชั้นเรียนก่อน --</option>';
            advisorSelect.disabled = true;
        }
    }
    
    // เมื่อค้นหาข้อมูล
    function searchReport(event) {
        event.preventDefault();
        
        if (!departmentSelect.value || !levelSelect.value || !groupSelect.value || !weekSelect.value) {
            alert('กรุณาเลือกข้อมูลให้ครบถ้วน');
            return;
        }
        
        // แสดง loading
        resultsCard.style.display = 'block';
        resultsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div> กำลังโหลดข้อมูล...</td></tr>';
        
        // สร้าง URL สำหรับ PDF, Excel และ Chart
        const pdfUrl = `attendance_report.php?action=generate_pdf&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
        const excelUrl = `attendance_report.php?action=generate_excel&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
        const chartUrl = `attendance_report.php?action=generate_chart&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
        
        // ตั้งค่า URL สำหรับปุ่ม
        pdfBtn.href = pdfUrl;
        excelBtn.href = excelUrl;
        chartBtn.href = chartUrl;
        
        // ดึงข้อมูลที่เลือก
        const department = departmentSelect.options[departmentSelect.selectedIndex].text;
        const level = levelSelect.value;
        const group = groupSelect.value;
        const week = weekSelect.value;
        const weekText = weekSelect.options[weekSelect.selectedIndex].text;
        
        let advisorName = "ไม่ระบุ";
        if (advisorSelect.selectedIndex > 0) {
            advisorName = advisorSelect.options[advisorSelect.selectedIndex].text;
        }
        
        // หลังจากโหลดเสร็จ
        setTimeout(() => {
            // แสดงผลลัพธ์
            resultsBody.innerHTML = `
                <tr>
                    <td>${level}</td>
                    <td>${department}</td>
                    <td>กลุ่ม ${group}</td>
                    <td>${advisorName}</td>
                    <td>25</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-info btn-sm" onclick="showReportPreview('${level}', '${department}', '${group}', '${weekText}', '${advisorName}')">
                                <i class="material-icons align-middle">visibility</i> ดูรายงาน
                            </button>
                            <a href="${pdfUrl}" target="_blank" class="btn btn-danger btn-sm">
                                <i class="material-icons align-middle">picture_as_pdf</i> PDF
                            </a>
                            <a href="${excelUrl}" target="_blank" class="btn btn-success btn-sm">
                                <i class="material-icons align-middle">description</i> Excel
                            </a>
                            <a href="${chartUrl}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="material-icons align-middle">bar_chart</i> กราฟ
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }, 500);
    }
    
    // เมื่อกดปุ่มรีเซ็ต
    function resetForm() {
        departmentSelect.value = '';
        levelSelect.value = '';
        groupSelect.innerHTML = '<option value="">-- เลือกกลุ่ม --</option>';
        groupSelect.disabled = true;
        advisorSelect.innerHTML = '<option value="">-- กรุณาเลือกชั้นเรียนก่อน --</option>';
        advisorSelect.disabled = true;
        weekSelect.value = '';
        
        // ซ่อนผลลัพธ์และตัวอย่างรายงาน
        resultsCard.style.display = 'none';
        previewCard.style.display = 'none';
    }
    
    // แสดงตัวอย่างรายงาน
    window.showReportPreview = function(level, department, group, weekText, advisorName) {
        previewCard.style.display = 'block';
        
        // สร้างตัวอย่างรายงาน
        reportPreview.innerHTML = `
            <div class="report-sample border p-4">
                <div class="d-flex align-items-start mb-4">
                    <div class="report-logo border rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        โลโก้<br>วิทยาลัย
                    </div>
                    <div>
                        <div class="text-center">
                            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
                            <strong>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</strong><br>
                            ภาคเรียนที่ ${document.querySelector('#academicSemester').value || '1'} ปีการศึกษา ${document.querySelector('#academicYear').value || '2568'} ${weekText}<br>
                            ระหว่างวันที่............เดือน..............พ.ศ. ถึง วันที่.............เดือน..............พ.ศ.<br>
                            ระดับชั้น ${level} กลุ่ม ${group} แผนกวิชา${department}
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered small">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th rowspan="2" style="vertical-align: middle;">ลำดับที่</th>
                                <th rowspan="2" style="vertical-align: middle;">รหัสนักศึกษา</th>
                                <th rowspan="2" style="vertical-align: middle;">ชื่อ-สกุล</th>
                                <th colspan="5">${weekText}</th>
                                <th rowspan="2" style="vertical-align: middle;">รวม</th>
                            </tr>
                            <tr class="text-center">
                                <th>1<br>จ.</th>
                                <th>2<br>อ.</th>
                                <th>3<br>พ.</th>
                                <th>4<br>พฤ.</th>
                                <th>5<br>ศ.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>65101001</td>
                                <td>นายอานนท์ มีสุข</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">ขาด</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">4</td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td>65101002</td>
                                <td>นางสาวกานดา ดอกไม้</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">สาย</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">4</td>
                            </tr>
                            <tr>
                                <td class="text-center">3</td>
                                <td>65101003</td>
                                <td>นายภูมิ ฉลาด</td>
                                <td class="text-center">ลา</td>
                                <td class="text-center">ลา</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">✓</td>
                                <td class="text-center">3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-2">
                    <p>สรุป จำนวนคน...........5...........ชาย.............3.............หญิง............2..............<br>
                    สรุปจำนวนนักเรียนเข้าแถวร้อยละ...........80...........</p>
                </div>
                
                <div class="row mt-5">
                    <div class="col-4 text-center">
                        <p class="mb-0 mt-5 pt-3 border-top">ลงชื่อ...........................................</p>
                        <p>(${advisorName})<br>ครูที่ปรึกษา</p>
                    </div>
                    <div class="col-4 text-center">
                        <p class="mb-0 mt-5 pt-3 border-top">ลงชื่อ...........................................</p>
                        <p>(นายนนทศรี ศรีสุข)<br>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</p>
                    </div>
                    <div class="col-4 text-center">
                        <p class="mb-0 mt-5 pt-3 border-top">ลงชื่อ...........................................</p>
                        <p>(นายพงษ์ศักดิ์ สมใจรัก)<br>รองผู้อำนวยการ<br>ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</p>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <div>หน้าที่ 1</div>
                    <div>พิมพ์เมื่อวันที่ ${new Date().toLocaleDateString('th-TH')}</div>
                </div>
            </div>
        `;
        
        // เลื่อนไปยังตัวอย่างรายงาน
        previewCard.scrollIntoView({ behavior: 'smooth' });
    };
    
    // เมื่อกดปุ่มดาวน์โหลดทั้งหมด
    function downloadAll() {
        if (!departmentSelect.value || !levelSelect.value || !groupSelect.value || !weekSelect.value) {
            alert('กรุณาเลือกข้อมูลให้ครบถ้วน');
            return;
        }
        
        const confirmed = confirm('ต้องการดาวน์โหลดรายงานทั้งหมดหรือไม่?');
        if (confirmed) {
            // เปิดหน้าต่างใหม่สำหรับ PDF
            window.open(pdfBtn.href, '_blank');
            
            // หน่วงเวลาเล็กน้อย แล้วเปิดหน้าต่างใหม่สำหรับ Excel
            setTimeout(() => {
                window.open(excelBtn.href, '_blank');
            }, 1000);
            
            // หน่วงเวลาเล็กน้อย แล้วเปิดหน้าต่างใหม่สำหรับ Chart
            setTimeout(() => {
                window.open(chartBtn.href, '_blank');
            }, 2000);
        }
    }
    
    // เมื่อเลือกแผนกวิชาหรือระดับชั้น
    departmentSelect.addEventListener('change', updateGroups);
    levelSelect.addEventListener('change', updateGroups);
    
    // เมื่อเลือกกลุ่ม
    groupSelect.addEventListener('change', updateAdvisors);
    
    // เมื่อค้นหาข้อมูล
    searchForm.addEventListener('submit', searchReport);
    
    // เมื่อกดปุ่มรีเซ็ต
    resetBtn.addEventListener('click', resetForm);
    
    // เมื่อกดปุ่มปิดตัวอย่างรายงาน
    closePreviewBtn.addEventListener('click', function() {
        previewCard.style.display = 'none';
    });
    
    closeLinkBtn.addEventListener('click', function(e) {
        e.preventDefault();
        previewCard.style.display = 'none';
    });
    
    // เมื่อกดปุ่มดาวน์โหลดทั้งหมด
    downloadAllBtn.addEventListener('click', downloadAll);
    
    // เพิ่ม hidden input สำหรับข้อมูลปีการศึกษา (ใช้ในการแสดงตัวอย่างรายงาน)
    const academicYear = document.querySelector('input[name="academic_year_id"]').getAttribute('data-year') || '2568';
    const academicSemester = document.querySelector('input[name="academic_year_id"]').getAttribute('data-semester') || '1';
    
    const academicYearInput = document.createElement('input');
    academicYearInput.type = 'hidden';
    academicYearInput.id = 'academicYear';
    academicYearInput.value = academicYear;
    
    const academicSemesterInput = document.createElement('input');
    academicSemesterInput.type = 'hidden';
    academicSemesterInput.id = 'academicSemester';
    academicSemesterInput.value = academicSemester;
    
    searchForm.appendChild(academicYearInput);
    searchForm.appendChild(academicSemesterInput);
});
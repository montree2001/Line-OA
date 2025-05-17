<!-- หน้าค้นหาและพิมพ์รายงานการเข้าแถว -->
<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="material-icons align-middle me-1">filter_alt</i> ค้นหาและกรองข้อมูล</h5>
            </div>
        </div>
        <div class="card-body">
            <form id="reportSearchForm" method="get" action="attendance_report.php">
                <input type="hidden" name="academic_year_id" value="<?php echo $academicYear['academic_year_id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="department_id" class="form-label">แผนกวิชา</label>
                            <select class="form-select form-select-sm" id="department_id" name="department_id" required>
                                <option value="">-- เลือกแผนกวิชา --</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="level" class="form-label">ระดับชั้น</label>
                            <select class="form-select form-select-sm" id="level" name="level" required>
                                <option value="">-- เลือกระดับชั้น --</option>
                                <?php foreach ($levels as $lv): ?>
                                <option value="<?php echo $lv; ?>"><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="group" class="form-label">กลุ่ม</label>
                            <select class="form-select form-select-sm" id="group" name="group" required disabled>
                                <option value="">-- เลือกกลุ่ม --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="advisor_id" class="form-label">ครูที่ปรึกษา</label>
                            <select class="form-select form-select-sm" id="advisor_id" name="advisor_id" disabled>
                                <option value="">-- กรุณาเลือกชั้นเรียนก่อน --</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="week" class="form-label">สัปดาห์</label>
                            <select class="form-select form-select-sm" id="week" name="week" required>
                                <option value="">-- เลือกสัปดาห์ --</option>
                                <?php for ($i = 1; $i <= $totalWeeks; $i++): ?>
                                <?php 
                                    $startDate = $allWeeks[$i-1]['start_date']->format('d/m/Y');
                                    $endDate = $allWeeks[$i-1]['end_date']->format('d/m/Y');
                                ?>
                                <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?> (<?php echo $startDate; ?> - <?php echo $endDate; ?>)</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">ข้อมูลภาคเรียน</label>
                            <div class="alert alert-info py-2">
                                ภาคเรียนที่ <?php echo $academicYear['semester']; ?> ปีการศึกษา <?php echo $academicYear['year']; ?><br>
                                ระหว่างวันที่ <?php echo date('d/m/Y', strtotime($academicYear['start_date'])); ?> ถึง <?php echo date('d/m/Y', strtotime($academicYear['end_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="resetBtn">
                        <i class="material-icons align-middle me-1">restart_alt</i> รีเซ็ต
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="material-icons align-middle me-1">search</i> ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4" id="resultsCard" style="display: none;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="material-icons align-middle me-1">format_list_bulleted</i> ผลลัพธ์การค้นหา</h5>
                <div class="header-actions">
                    <button class="btn btn-success btn-sm" id="downloadAllBtn">
                        <i class="material-icons align-middle me-1">file_download</i> ดาวน์โหลดทั้งหมด
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="resultsTable">
                    <thead class="table-light">
                        <tr>
                            <th>ชั้นเรียน</th>
                            <th>แผนกวิชา</th>
                            <th>กลุ่ม</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>จำนวนนักเรียน</th>
                            <th width="280">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <tr id="placeholderRow">
                            <td colspan="6" class="text-center py-4">กรุณาค้นหาข้อมูลที่ต้องการ</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- ตัวอย่างรายงาน -->
    <div class="card mb-4" id="previewCard" style="display: none;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="material-icons align-middle me-1">visibility</i> ตัวอย่างรายงาน</h5>
                <button type="button" class="btn-close" id="closePreviewBtn" aria-label="Close"></button>
            </div>
        </div>
        <div class="card-body">
            <div class="report-preview" id="reportPreview">
                <!-- จะแสดงตัวอย่างรายงานที่นี่ -->
            </div>
            
            <div class="text-end mt-3">
                <a href="#" class="btn btn-outline-secondary btn-sm me-2" id="closeLinkBtn">
                    <i class="material-icons align-middle me-1">close</i> ปิด
                </a>
                <a href="#" class="btn btn-danger btn-sm me-2" id="pdfBtn" target="_blank">
                    <i class="material-icons align-middle me-1">picture_as_pdf</i> พิมพ์ PDF
                </a>
                <a href="#" class="btn btn-success btn-sm me-2" id="excelBtn" target="_blank">
                    <i class="material-icons align-middle me-1">description</i> ดาวน์โหลด Excel
                </a>
                <a href="#" class="btn btn-primary btn-sm" id="chartBtn" target="_blank">
                    <i class="material-icons align-middle me-1">bar_chart</i> พิมพ์กราฟ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
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
            
            // ในสถานการณ์จริง ควรส่ง AJAX request ไปยังเซิร์ฟเวอร์เพื่อดึงข้อมูล
            // แต่ในตัวอย่างนี้จะใช้ข้อมูลตัวอย่าง
            
            // ดึงข้อมูลที่เลือก
            const department = departmentSelect.options[departmentSelect.selectedIndex].text;
            const level = levelSelect.value;
            const group = groupSelect.value;
            const week = weekSelect.value;
            const weekText = weekSelect.options[weekSelect.selectedIndex].text;
            const advisorName = advisorSelect.options[advisorSelect.selectedIndex].text;
            
            // สร้าง URL สำหรับ PDF, Excel และ Chart
            const pdfUrl = `attendance_report.php?action=generate_pdf&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
            const excelUrl = `attendance_report.php?action=generate_excel&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
            const chartUrl = `attendance_report.php?action=generate_chart&department_id=${departmentSelect.value}&level=${levelSelect.value}&group=${groupSelect.value}&week=${weekSelect.value}&academic_year_id=${document.querySelector('input[name="academic_year_id"]').value}`;
            
            // ตั้งค่า URL สำหรับปุ่ม
            pdfBtn.href = pdfUrl;
            excelBtn.href = excelUrl;
            chartBtn.href = chartUrl;
            
            // จำลองผลลัพธ์
            setTimeout(() => {
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
        
        // ประกาศฟังก์ชัน showReportPreview สำหรับเรียกใช้จาก inline onclick
        window.showReportPreview = function(level, department, group, weekText, advisorName) {
            previewCard.style.display = 'block';
            
            // สร้างตัวอย่างรายงาน (ในสถานการณ์จริงควรดึงข้อมูลจริงด้วย AJAX)
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
                                ภาคเรียนที่ <?php echo $academicYear['semester']; ?> ปีการศึกษา <?php echo $academicYear['year']; ?> ${weekText}<br>
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
                        <div>พิมพ์เมื่อวันที่ <?php echo date('d/m/Y'); ?></div>
                    </div>
                </div>
            `;
            
            // เลื่อนไปยังตัวอย่างรายงาน
            previewCard.scrollIntoView({ behavior: 'smooth' });
        };
        
        // เมื่อกดปุ่มดาวน์โหลดทั้งหมด
        downloadAllBtn.addEventListener('click', function() {
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
        });
    });
</script>
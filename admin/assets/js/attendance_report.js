// ปรับปรุง JavaScript ในส่วนการแสดงผลการค้นหา
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้น Select2
    $('.select2').select2({
        width: '100%',
        placeholder: "เลือกหรือค้นหา...",
        allowClear: true
    });
    
    // เมื่อเลือกแผนกวิชา
    $('#department').on('change', function() {
        const departmentId = $(this).val();
        const classSelect = $('#class');
        
        // ล้างและปิดใช้งาน dropdown ห้องเรียน
        classSelect.empty().append('<option value="">-- เลือกห้องเรียน --</option>').prop('disabled', true).trigger('change');
        
        if (departmentId) {
            // เปิดใช้งาน dropdown ห้องเรียน
            classSelect.prop('disabled', false);
            
            // แสดง loading
            $('#class-loading').show();
            
            // โหลดข้อมูลห้องเรียนจาก AJAX
            $.ajax({
                url: 'ajax/get_classes_by_department.php',
                type: 'GET',
                data: { department_id: departmentId },
                dataType: 'json',
                success: function(response) {
                    $('#class-loading').hide();
                    
                    if (response.status === 'success' && response.classes) {
                        // ล้างข้อมูลเดิมทั้งหมด
                        classSelect.empty().append('<option value="">-- เลือกห้องเรียน --</option>');
                        
                        // เพิ่มตัวเลือกห้องเรียน
                        response.classes.forEach(function(classItem) {
                            classSelect.append(
                                `<option value="${classItem.class_id}">${classItem.level}/${classItem.group_number} ${classItem.department_name}</option>`
                            );
                        });
                        
                        // อัปเดต Select2
                        classSelect.trigger('change');
                    } else {
                        alert('ไม่สามารถโหลดข้อมูลห้องเรียนได้: ' + (response.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                    }
                },
                error: function() {
                    $('#class-loading').hide();
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                }
            });
        }
    });
    
    // เมื่อเลือกสัปดาห์เริ่มต้น
    $('#start_week').on('change', function() {
        const startWeek = parseInt($(this).val());
        const endWeekSelect = $('#end_week');
        
        // ล้างและตั้งค่าตัวเลือกสัปดาห์สิ้นสุด
        endWeekSelect.empty().append('<option value="">-- เลือกสัปดาห์ --</option>');
        
        if (startWeek) {
            // เพิ่มตัวเลือกสัปดาห์สิ้นสุด (จากสัปดาห์เริ่มต้นถึง 18)
            for (let i = startWeek; i <= 18; i++) {
                endWeekSelect.append(`<option value="${i}" ${i === startWeek ? 'selected' : ''}>สัปดาห์ที่ ${i}</option>`);
            }
            
            // อัปเดต Select2
            endWeekSelect.trigger('change');
        }
    });
    
    // เมื่อคลิกปุ่มค้นหา
    $('#search_btn').on('click', function() {
        const departmentId = $('#department').val();
        const classId = $('#class').val();
        const startWeek = $('#start_week').val();
        const endWeek = $('#end_week').val();
        const searchTerm = $('#search_student').val();
        
        // ตรวจสอบว่าเลือกครบถ้วนหรือไม่
        if (!departmentId || !classId || !startWeek || !endWeek) {
            alert('กรุณาเลือกข้อมูลให้ครบถ้วน');
            return;
        }
        
        // คำนวณวันที่เริ่มต้นและสิ้นสุดจากสัปดาห์
        const academicStartDate = new Date($('#semester_start_date').val() || '<?php echo $academic_year["start_date"]; ?>'); // วันเริ่มต้นภาคเรียน
        
        // วันเริ่มต้นของสัปดาห์ที่เลือก
        const startDate = new Date(academicStartDate);
        startDate.setDate(startDate.getDate() + (parseInt(startWeek) - 1) * 7);
        
        // ปรับให้เป็นวันจันทร์
        const dayOfWeek = startDate.getDay(); // 0 = อาทิตย์, 1 = จันทร์, ...
        if (dayOfWeek === 0) { // ถ้าเป็นวันอาทิตย์ ให้เลื่อนไป 1 วัน (เป็นวันจันทร์)
            startDate.setDate(startDate.getDate() + 1);
        } else if (dayOfWeek > 1) { // ถ้าไม่ใช่วันจันทร์ ให้ถอยกลับไปเป็นวันจันทร์ล่าสุด
            startDate.setDate(startDate.getDate() - (dayOfWeek - 1));
        }
        
        // วันสิ้นสุดของสัปดาห์ที่เลือก (ศุกร์ของสัปดาห์สุดท้าย)
        const endDate = new Date(academicStartDate);
        endDate.setDate(endDate.getDate() + (parseInt(endWeek) - 1) * 7); // ไปถึงเริ่มต้นของสัปดาห์สุดท้าย
        
        // ปรับให้เป็นวันจันทร์
        const endDayOfWeek = endDate.getDay();
        if (endDayOfWeek === 0) { // ถ้าเป็นวันอาทิตย์
            endDate.setDate(endDate.getDate() + 1);
        } else if (endDayOfWeek > 1) { // ถ้าไม่ใช่วันจันทร์
            endDate.setDate(endDate.getDate() - (endDayOfWeek - 1));
        }
        
        // เพิ่มอีก 4 วันเพื่อไปถึงวันศุกร์
        endDate.setDate(endDate.getDate() + 4);
        
        // จัดรูปแบบวันที่เป็น yyyy-mm-dd
        const formattedStartDate = formatDate(startDate);
        const formattedEndDate = formatDate(endDate);
        
        // กำหนดค่าให้กับฟอร์มพิมพ์
        $('#form_class_id, #chart_class_id, #excel_class_id').val(classId);
        $('#form_start_date, #chart_start_date, #excel_start_date').val(formattedStartDate);
        $('#form_end_date, #chart_end_date, #excel_end_date').val(formattedEndDate);
        $('#form_week_number, #chart_week_number, #excel_week_number').val(startWeek);
        $('#form_end_week').val(endWeek);
        $('#form_search, #excel_search').val(searchTerm);
        
        // อัปเดตข้อมูลสรุป
        const selectedClass = $('#class option:selected').text();
        $('#summary_class').text(selectedClass);
        $('#summary_date').text(formatThaiDate(formattedStartDate) + ' - ' + formatThaiDate(formattedEndDate));
        $('#summary_week').text(startWeek + ' - ' + endWeek);
        
        // โหลดข้อมูลการเข้าแถว
        loadAttendancePreview(classId, formattedStartDate, formattedEndDate, startWeek, endWeek, searchTerm);
    });
    
    // เมื่อคลิกปุ่มพิมพ์รายงาน PDF
    $('#print_pdf_btn').on('click', function() {
        $('#print_form').submit();
    });
    
    // เมื่อคลิกปุ่มพิมพ์กราฟสรุป PDF
    $('#print_chart_btn').on('click', function() {
        $('#chart_form').submit();
    });
    
    // เมื่อคลิกปุ่มส่งออก Excel
    $('#export_excel_btn').on('click', function() {
        $('#excel_form').submit();
    });
    
    // ฟังก์ชันแสดงข้อมูลการเข้าแถว
    function loadAttendancePreview(classId, startDate, endDate, startWeek, endWeek, searchTerm) {
        // แสดง loading และ report container ก่อนทำการโหลดข้อมูล
        $('#preview_loading').show();
        $('#preview_content').hide();
        $('#report_container').show();
        
        // ซ่อนข้อความแจ้งเตือน
        $('.info-panel').hide();
        
        // โหลดข้อมูลการเข้าแถวจาก AJAX
        $.ajax({
            url: 'ajax/get_attendance_preview.php',
            type: 'GET',
            data: {
                class_id: classId,
                start_date: startDate,
                end_date: endDate,
                search: searchTerm
            },
            dataType: 'json',
            success: function(response) {
                // ซ่อน loading
                $('#preview_loading').hide();
                
                console.log('Response data:', response);
                
                if (response.status === 'success') {
                    // แสดงข้อมูลรายงาน
                    let weekTable = '';
                    
                    // สร้างข้อมูลสำหรับแต่ละสัปดาห์
                    let currentWeek = parseInt(startWeek);
                    let weekStartDate = new Date(startDate);
                    
                    while (currentWeek <= parseInt(endWeek)) {
                        // คำนวณวันที่เริ่มต้นและสิ้นสุดของสัปดาห์นี้
                        let weekEndDate = new Date(weekStartDate);
                        weekEndDate.setDate(weekEndDate.getDate() + 4); // จันทร์ถึงศุกร์ = 5 วัน
                        
                        // กรองวันที่เฉพาะของสัปดาห์นี้
                        const weekDays = response.week_days.filter(day => {
                            const dayDate = new Date(day.date);
                            return dayDate >= weekStartDate && dayDate <= weekEndDate;
                        });
                        
                        // ข้าม loop ถ้าไม่มีวันในสัปดาห์นี้
                        if (weekDays.length === 0) {
                            currentWeek++;
                            weekStartDate.setDate(weekStartDate.getDate() + 7);
                            continue;
                        }
                        
                        // สร้างตารางสำหรับสัปดาห์นี้
                        weekTable += `
                            <div class="week-table">
                                <div class="week-header">
                                    <h5 class="mb-0">สัปดาห์ที่ ${currentWeek} (${formatThaiDate(formatDate(weekStartDate))} - ${formatThaiDate(formatDate(weekEndDate))})</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="text-center" style="vertical-align: middle;">ลำดับ</th>
                                                <th rowspan="2" class="text-center" style="vertical-align: middle;">รหัสนักศึกษา</th>
                                                <th rowspan="2" class="text-center" style="vertical-align: middle;">ชื่อ-สกุล</th>
                                                <th colspan="${weekDays.length}" class="text-center">วันที่</th>
                                                <th rowspan="2" class="text-center" style="vertical-align: middle;">รวม</th>
                                            </tr>
                                            <tr>
                        `;
                        
                        // หัวตารางวันที่
                        weekDays.forEach(day => {
                            weekTable += `
                                <th class="text-center">
                                    ${day.day_name}<br>
                                    ${day.day_num}
                                </th>
                            `;
                        });
                        
                        weekTable += `
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;
                        
                        // ข้อมูลนักเรียน
                        response.students.forEach((student, index) => {
                            let presentCount = 0;
                            
                            weekTable += `
                                <tr>
                                    <td class="text-center">${index + 1}</td>
                                    <td class="text-center">${student.student_code}</td>
                                    <td>${student.display_title}${student.first_name} ${student.last_name}</td>
                            `;
                            
                            // สถานะการเข้าแถวของแต่ละวัน
                            weekDays.forEach(day => {
                                let cellClass = '';
                                let cellText = '-';
                                
                                if (day.is_holiday) {
                                    cellClass = 'status-holiday';
                                    cellText = 'หยุด';
                                } else if (response.attendance_data[student.student_id] && 
                                          response.attendance_data[student.student_id][day.date]) {
                                    const status = response.attendance_data[student.student_id][day.date];
                                    
                                    if (status === 'present') {
                                        cellClass = 'status-present';
                                        cellText = 'มา';
                                        presentCount++;
                                    } else if (status === 'absent') {
                                        cellClass = 'status-absent';
                                        cellText = 'ขาด';
                                    } else if (status === 'late') {
                                        cellClass = 'status-late';
                                        cellText = 'สาย';
                                        presentCount++; // นับสายเป็นมาเรียน
                                    } else if (status === 'leave') {
                                        cellClass = 'status-leave';
                                        cellText = 'ลา';
                                    }
                                }
                                
                                weekTable += `<td class="text-center ${cellClass}">${cellText}</td>`;
                            });
                            
                            weekTable += `
                                    <td class="text-center fw-bold">${presentCount}</td>
                                </tr>
                            `;
                        });
                        
                        weekTable += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                        
                        // เตรียมสำหรับสัปดาห์ถัดไป
                        currentWeek++;
                        weekStartDate.setDate(weekStartDate.getDate() + 7);
                    }
                    
                    // ตรวจสอบว่ามีข้อมูลหรือไม่
                    if (weekTable === '') {
                        $('#preview_content').html('<div class="alert alert-warning">ไม่พบข้อมูลการเข้าแถวในช่วงเวลาที่เลือก</div>').show();
                    } else {
                        $('#preview_content').html(weekTable).show();
                    }
                    
                } else {
                    // แสดงข้อความข้อผิดพลาด
                    $('#preview_content').html(`<div class="alert alert-danger">${response.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'}</div>`).show();
                }
            },
            error: function(xhr, status, error) {
                // ซ่อน loading
                $('#preview_loading').hide();
                
                // แสดงข้อความข้อผิดพลาด
                $('#preview_content').html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์</div>').show();
                console.error('AJAX Error:', error);
            }
        });
    }
    
    // ฟังก์ชันจัดรูปแบบวันที่เป็น yyyy-mm-dd
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // ฟังก์ชันจัดรูปแบบวันที่เป็น d/m/yyyy (ภาษาไทย)
    function formatThaiDate(dateStr) {
        const date = new Date(dateStr);
        return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear() + 543}`;
    }
});
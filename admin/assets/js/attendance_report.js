/**
 * attendance_report.js - JavaScript สำหรับหน้ารายงานการเข้าแถว (ปรับปรุงใหม่)
 */

// ตัวแปรเพื่อป้องกันการโหลดซ้ำ
if (typeof AttendanceReport === 'undefined') {
    
    var AttendanceReport = {
        initialized: false,
        
        init: function() {
            if (this.initialized) {
                return;
            }
            
            this.initialized = true;
            this.initializeComponents();
            this.bindEvents();
        },
        
        initializeComponents: function() {
            // เริ่มต้น Select2
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    width: '100%',
                    placeholder: "เลือกหรือค้นหา...",
                    allowClear: true
                });
            }
        },
        
        bindEvents: function() {
            var self = this;
            
            // เมื่อเปลี่ยนประเภทการค้นหา
            $('#search_type').off('change.attendance').on('change.attendance', function() {
                const searchType = $(this).val();
                
                if (searchType === 'student') {
                    $('#class_search_section').hide();
                    $('#search_input').show().focus();
                } else {
                    $('#class_search_section').show();
                    $('#search_input').hide();
                }
            });
            
            // เมื่อเลือกแผนกวิชา
            $('#department').off('change.attendance').on('change.attendance', function() {
                self.loadClassesByDepartment($(this).val());
            });
            
            // เมื่อเลือกสัปดาห์เริ่มต้น
            $('#start_week').off('change.attendance').on('change.attendance', function() {
                self.updateEndWeekOptions($(this).val());
            });
            
            // เมื่อคลิกปุ่มค้นหา
            $('#search_btn').off('click.attendance').on('click.attendance', function(e) {
                e.preventDefault();
                self.performSearch();
                return false;
            });
            
            // เมื่อคลิกปุ่มพิมพ์ต่างๆ
            $('#print_pdf_btn').off('click.attendance').on('click.attendance', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                self.handlePrintPDF(this);
                return false;
            });
            
            $('#print_chart_btn').off('click.attendance').on('click.attendance', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handlePrintChart(this);
                return false;
            });
            
            $('#export_excel_btn').off('click.attendance').on('click.attendance', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleExportExcel(this);
                return false;
            });
        },
        
        loadClassesByDepartment: function(departmentId) {
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
        },
        
        updateEndWeekOptions: function(startWeek) {
            const endWeekSelect = $('#end_week');
            
            // ล้างและตั้งค่าตัวเลือกสัปดาห์สิ้นสุด
            endWeekSelect.empty().append('<option value="">-- เลือกสัปดาห์ --</option>');
            
            if (startWeek) {
                const totalWeeks = typeof window.totalWeeks !== 'undefined' ? window.totalWeeks : 18;
                
                // เพิ่มตัวเลือกสัปดาห์สิ้นสุด
                for (let i = parseInt(startWeek); i <= totalWeeks; i++) {
                    endWeekSelect.append(`<option value="${i}" ${i === parseInt(startWeek) ? 'selected' : ''}>สัปดาห์ที่ ${i}</option>`);
                }
                
                // อัปเดต Select2
                endWeekSelect.trigger('change');
            }
        },
        
        performSearch: function() {
            const searchType = $('#search_type').val();
            const startWeek = $('#start_week').val();
            const endWeek = $('#end_week').val();
            
            // ตรวจสอบสัปดาห์
            if (!startWeek || !endWeek) {
                alert('กรุณาเลือกช่วงสัปดาห์');
                return;
            }
            
            let searchData = {
                search_type: searchType,
                start_week: startWeek,
                end_week: endWeek
            };
            
            if (searchType === 'class') {
                const departmentId = $('#department').val();
                const classId = $('#class').val();
                
                if (!departmentId || !classId) {
                    alert('กรุณาเลือกแผนกวิชาและห้องเรียน');
                    return;
                }
                
                searchData.department_id = departmentId;
                searchData.class_id = classId;
            } else {
                const searchInput = $('#search_input').val().trim();
                
                if (!searchInput) {
                    alert('กรุณาพิมพ์ข้อมูลที่ต้องการค้นหา');
                    return;
                }
                
                searchData.search_input = searchInput;
            }
            
            this.executeSearch(searchData);
        },
        
        executeSearch: function(searchData) {
            // คำนวณวันที่เริ่มต้นและสิ้นสุดจากสัปดาห์
            const academicYear = typeof window.academicYear !== 'undefined' ? window.academicYear : {};
            const academicStartDate = new Date(academicYear.start_date);
            
            // วันเริ่มต้นของสัปดาห์ที่เลือก
            const startDate = new Date(academicStartDate);
            startDate.setDate(startDate.getDate() + (parseInt(searchData.start_week) - 1) * 7);
            
            // ปรับให้เป็นวันจันทร์
            const dayOfWeek = startDate.getDay();
            if (dayOfWeek === 0) {
                startDate.setDate(startDate.getDate() + 1);
            } else if (dayOfWeek > 1) {
                startDate.setDate(startDate.getDate() - (dayOfWeek - 1));
            }
            
            // วันสิ้นสุดของสัปดาห์ที่เลือก
            const endDate = new Date(academicStartDate);
            endDate.setDate(endDate.getDate() + (parseInt(searchData.end_week) - 1) * 7);
            
            const endDayOfWeek = endDate.getDay();
            if (endDayOfWeek === 0) {
                endDate.setDate(endDate.getDate() + 1);
            } else if (endDayOfWeek > 1) {
                endDate.setDate(endDate.getDate() - (endDayOfWeek - 1));
            }
            
            endDate.setDate(endDate.getDate() + 4); // ไปถึงวันศุกร์
            
            // จัดรูปแบบวันที่
            const formattedStartDate = this.formatDate(startDate);
            const formattedEndDate = this.formatDate(endDate);
            
            // กำหนดค่าให้กับฟอร์มพิมพ์
            this.setFormValues(searchData, formattedStartDate, formattedEndDate);
            
            // โหลดตัวอย่างข้อมูล
            this.loadAttendancePreview(searchData, formattedStartDate, formattedEndDate);
        },
        
        setFormValues: function(searchData, startDate, endDate) {
            const forms = ['#print_form', '#chart_form', '#excel_form'];
            
            forms.forEach(formId => {
                if (searchData.search_type === 'class') {
                    $(formId + ' input[name="class_id"]').val(searchData.class_id);
                    $(formId + ' input[name="search"]').val('');
                } else {
                    $(formId + ' input[name="class_id"]').val('');
                    $(formId + ' input[name="search"]').val(searchData.search_input);
                }
                
                $(formId + ' input[name="start_date"]').val(startDate);
                $(formId + ' input[name="end_date"]').val(endDate);
                $(formId + ' input[name="week_number"]').val(searchData.start_week);
                $(formId + ' input[name="end_week"]').val(searchData.end_week);
                $(formId + ' input[name="search_type"]').val(searchData.search_type);
            });
        },
        
        loadAttendancePreview: function(searchData, startDate, endDate) {
            // แสดง loading และ report container
            $('#preview_loading').show();
            $('#preview_content').hide();
            $('#report_container').show();
            $('#info_panel').hide();
            
            // สร้าง AJAX request
            let ajaxData = {
                search_type: searchData.search_type,
                start_date: startDate,
                end_date: endDate,
                start_week: searchData.start_week,
                end_week: searchData.end_week
            };
            
            if (searchData.search_type === 'class') {
                ajaxData.class_id = searchData.class_id;
            } else {
                ajaxData.search_input = searchData.search_input;
            }
            
            var self = this;
            
            $.ajax({
                url: 'ajax/get_attendance_preview.php',
                type: 'GET',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    $('#preview_loading').hide();
                    
                    if (response.status === 'success') {
                        self.displaySearchResults(response, searchData);
                        $('#action_buttons').show();
                    } else {
                        alert('ไม่สามารถโหลดข้อมูลได้: ' + (response.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                        $('#report_container').hide();
                        $('#info_panel').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#preview_loading').hide();
                    console.error('AJAX error:', { xhr, status, error });
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                    $('#report_container').hide();
                    $('#info_panel').show();
                }
            });
        },
        
        displaySearchResults: function(response, searchData) {
            // แสดงข้อมูลสรุป
            let summaryHtml = '<div class="row">';
            
            if (searchData.search_type === 'class') {
                const selectedClass = $('#class option:selected').text();
                summaryHtml += `
                    <div class="col-md-4">
                        <div class="info-item">
                            <strong><i class="material-icons align-middle me-1">school</i> ห้องเรียน:</strong>
                            <span>${selectedClass}</span>
                        </div>
                    </div>
                `;
            } else {
                summaryHtml += `
                    <div class="col-md-4">
                        <div class="info-item">
                            <strong><i class="material-icons align-middle me-1">person</i> ค้นหา:</strong>
                            <span>${searchData.search_input}</span>
                        </div>
                    </div>
                `;
            }
            
            summaryHtml += `
                <div class="col-md-4">
                    <div class="info-item">
                        <strong><i class="material-icons align-middle me-1">date_range</i> ช่วงวันที่:</strong>
                        <span>${this.formatThaiDate(response.start_date)} - ${this.formatThaiDate(response.end_date)}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item">
                        <strong><i class="material-icons align-middle me-1">event_note</i> สัปดาห์ที่:</strong>
                        <span>${searchData.start_week} - ${searchData.end_week}</span>
                    </div>
                </div>
            </div>`;
            
            $('#report_summary').html(summaryHtml);
            
            // สร้างตารางแสดงข้อมูล
            let contentHtml = '';
            
            if (response.students && response.students.length > 0) {
                contentHtml = this.createWeeklyTables(response, searchData);
            } else {
                contentHtml = '<div class="alert alert-warning">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่ระบุ</div>';
            }
            
            $('#preview_content').html(contentHtml).show();
        },
        
        createWeeklyTables: function(response, searchData) {
            const academicYear = typeof window.academicYear !== 'undefined' ? window.academicYear : {};
            let tablesHtml = '';
            
            // สร้างตารางสำหรับแต่ละสัปดาห์
            for (let week = parseInt(searchData.start_week); week <= parseInt(searchData.end_week); week++) {
                // คำนวณวันที่ของสัปดาห์นี้
                const weekStartDate = new Date(academicYear.start_date);
                weekStartDate.setDate(weekStartDate.getDate() + (week - 1) * 7);
                
                // ปรับให้เป็นวันจันทร์
                const dayOfWeek = weekStartDate.getDay();
                if (dayOfWeek === 0) {
                    weekStartDate.setDate(weekStartDate.getDate() + 1);
                } else if (dayOfWeek > 1) {
                    weekStartDate.setDate(weekStartDate.getDate() - (dayOfWeek - 1));
                }
                
                // สร้างอาเรย์วันจันทร์-ศุกร์
                const weekDays = [];
                const currentDay = new Date(weekStartDate);
                
                for (let i = 0; i < 5; i++) {
                    const dateStr = this.formatDate(currentDay);
                    const dayName = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'][i];
                    
                    weekDays.push({
                        date: dateStr,
                        day_name: dayName,
                        day_num: currentDay.getDate(),
                        is_holiday: response.holidays && response.holidays[dateStr]
                    });
                    
                    currentDay.setDate(currentDay.getDate() + 1);
                }
                
                // สร้างตาราง
                tablesHtml += this.createWeekTable(week, weekDays, response, searchData);
            }
            
            return tablesHtml;
        },
        
        createWeekTable: function(week, weekDays, response, searchData) {
            let tableHtml = `
                <div class="week-table mb-4">
                    <div class="week-header bg-primary text-white p-3">
                        <h5 class="mb-0">สัปดาห์ที่ ${week}</h5>
                        <small>${this.formatThaiDate(weekDays[0].date)} - ${this.formatThaiDate(weekDays[4].date)}</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 60px;" class="text-center">ลำดับ</th>
                                    <th style="width: 120px;" class="text-center">รหัสนักศึกษา</th>
                                    <th class="text-left">ชื่อ-สกุล</th>
            `;
            
            if (searchData.search_type === 'student') {
                tableHtml += `<th style="width: 100px;" class="text-center">ห้องเรียน</th>`;
            }
            
            // เพิ่มหัวตารางวันที่
            weekDays.forEach(day => {
                tableHtml += `
                    <th style="width: 80px;" class="text-center ${day.is_holiday ? 'table-secondary' : ''}">
                        ${day.day_name}<br>${day.day_num}
                        ${day.is_holiday ? '<br><small>หยุด</small>' : ''}
                    </th>
                `;
            });
            
            tableHtml += `
                                    <th style="width: 80px;" class="text-center">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            // เพิ่มแถวข้อมูลนักเรียน
            response.students.forEach((student, index) => {
                let totalPresent = 0;
                
                tableHtml += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center">${student.student_code}</td>
                        <td>${student.title}${student.first_name} ${student.last_name}</td>
                `;
                
                if (searchData.search_type === 'student') {
                    tableHtml += `
                        <td class="text-center">
                            ${student.level && student.group_number ? student.level + '/' + student.group_number : '-'}
                        </td>
                    `;
                }
                
                // เพิ่มเซลล์สำหรับแต่ละวัน
                weekDays.forEach(day => {
                    if (day.is_holiday) {
                        tableHtml += `<td class="text-center table-secondary">หยุด</td>`;
                    } else if (response.attendance_data[student.student_id] && response.attendance_data[student.student_id][day.date]) {
                        const status = response.attendance_data[student.student_id][day.date];
                        let statusText = '', statusClass = '';
                        
                        switch (status) {
                            case 'present':
                                statusText = 'มา';
                                statusClass = 'table-success';
                                totalPresent++;
                                break;
                            case 'absent':
                                statusText = 'ขาด';
                                statusClass = 'table-danger';
                                break;
                            case 'late':
                                statusText = 'สาย';
                                statusClass = 'table-warning';
                                totalPresent++;
                                break;
                            case 'leave':
                                statusText = 'ลา';
                                statusClass = 'table-info';
                                break;
                            default:
                                statusText = '-';
                        }
                        
                        tableHtml += `<td class="text-center ${statusClass}">${statusText}</td>`;
                    } else {
                        tableHtml += `<td class="text-center">-</td>`;
                    }
                });
                
                tableHtml += `
                        <td class="text-center fw-bold">${totalPresent}</td>
                    </tr>
                `;
            });
            
            tableHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            return tableHtml;
        },
        
        handlePrintPDF: function(button) {
            if ($(button).hasClass('processing')) {
                return;
            }
            
            $(button).addClass('processing');
            
            try {
                $('#print_form').get(0).submit();
            } catch (e) {
                console.error('Error submitting form:', e);
            }
            
            // รีเซ็ตสถานะหลังจาก 3 วินาที
            setTimeout(() => {
                $(button).removeClass('processing');
            }, 3000);
        },
        
        handlePrintChart: function(button) {
            if ($(button).hasClass('processing')) {
                return;
            }
            
            $(button).addClass('processing');
            
            try {
                $('#chart_form').get(0).submit();
            } catch (e) {
                console.error('Error submitting form:', e);
            }
            
            // รีเซ็ตสถานะหลังจาก 3 วินาที
            setTimeout(() => {
                $(button).removeClass('processing');
            }, 3000);
        },
        
        handleExportExcel: function(button) {
            if ($(button).hasClass('processing')) {
                return;
            }
            
            $(button).addClass('processing');
            
            try {
                $('#excel_form').get(0).submit();
            } catch (e) {
                console.error('Error submitting form:', e);
            }
            
            // รีเซ็ตสถานะหลังจาก 3 วินาที
            setTimeout(() => {
                $(button).removeClass('processing');
            }, 3000);
        },
        
        // ฟังก์ชันช่วยเหลือ
        formatDate: function(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        
        formatThaiDate: function(dateStr) {
            const date = new Date(dateStr);
            return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear() + 543}`;
        }
    };
    
    // เริ่มต้นระบบเมื่อ DOM โหลดเสร็จ
    $(document).ready(function() {
        AttendanceReport.init();
    });
}
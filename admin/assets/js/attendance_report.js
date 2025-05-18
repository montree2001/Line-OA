/**
 * แก้ไขส่วนของ JavaScript เพื่อแก้ปัญหาการแสดงผลซ้ำของห้องเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้น Select2 สำหรับการค้นหา
    $('#department').select2({
        placeholder: "เลือกหรือพิมพ์ค้นหาแผนกวิชา",
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "ไม่พบผลลัพธ์";
            },
            searching: function() {
                return "กำลังค้นหา...";
            }
        }
    });
    
    $('#start_week, #end_week').select2({
        placeholder: "เลือกหรือพิมพ์ค้นหาสัปดาห์",
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "ไม่พบผลลัพธ์";
            }
        }
    });
    
    // เมื่อเลือกแผนกวิชา
    $('#department').on('change', function() {
        const departmentId = $(this).val();
        const classSelect = $('#class');
        
        // ล้างและปิดใช้งาน dropdown ห้องเรียน
        classSelect.empty().append('<option value="">-- เลือกห้องเรียน --</option>').prop('disabled', true);
        
        // ปิดการใช้งาน Select2 ถ้ามีการใช้งานอยู่
        if (classSelect.hasClass("select2-hidden-accessible")) {
            classSelect.select2('destroy');
        }
        
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
                        
                        // สร้างตัวแปรเพื่อตรวจสอบข้อมูลซ้ำ
                        const addedClasses = new Set();
                        
                        // เพิ่มตัวเลือกห้องเรียน
                        response.classes.forEach(function(classItem) {
                            const classKey = `${classItem.level}/${classItem.group_number} ${classItem.department_name}`;
                            
                            // ตรวจสอบว่าเคยเพิ่มแล้วหรือไม่
                            if (!addedClasses.has(classKey)) {
                                classSelect.append(
                                    `<option value="${classItem.class_id}">${classItem.level}/${classItem.group_number} ${classItem.department_name}</option>`
                                );
                                addedClasses.add(classKey);
                            }
                        });
                        
                        // เริ่มต้น Select2 สำหรับห้องเรียน
                        classSelect.select2({
                            placeholder: "เลือกหรือพิมพ์ค้นหาห้องเรียน",
                            allowClear: true,
                            width: '100%',
                            language: {
                                noResults: function() {
                                    return "ไม่พบผลลัพธ์";
                                }
                            }
                        });
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
        
        // ปิดการใช้งาน Select2 ถ้ามีการใช้งานอยู่
        if (endWeekSelect.hasClass("select2-hidden-accessible")) {
            endWeekSelect.select2('destroy');
        }
        
        if (startWeek) {
            // เพิ่มตัวเลือกสัปดาห์สิ้นสุด (จากสัปดาห์เริ่มต้นถึง 18)
            for (let i = startWeek; i <= 18; i++) {
                endWeekSelect.append(`<option value="${i}" ${i === startWeek ? 'selected' : ''}>สัปดาห์ที่ ${i}</option>`);
            }
            
            // เริ่มต้น Select2 อีกครั้ง
            endWeekSelect.select2({
                placeholder: "เลือกหรือพิมพ์ค้นหาสัปดาห์",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "ไม่พบผลลัพธ์";
                    }
                }
            });
        } else {
            // เริ่มต้น Select2 อีกครั้งกรณีไม่มีการเลือกสัปดาห์เริ่มต้น
            endWeekSelect.select2({
                placeholder: "เลือกหรือพิมพ์ค้นหาสัปดาห์",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "ไม่พบผลลัพธ์";
                    }
                }
            });
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
        const academicStartDate = new Date(academicYear.start_date); // วันเริ่มต้นภาคเรียน
        
        // วันเริ่มต้นของสัปดาห์ที่เลือก
        const startDate = new Date(academicStartDate);
        startDate.setDate(startDate.getDate() + (parseInt(startWeek) - 1) * 7);
        
        // วันสิ้นสุดของสัปดาห์ที่เลือก
        const endDate = new Date(academicStartDate);
        endDate.setDate(endDate.getDate() + (parseInt(endWeek) * 7) - 1);
        
        // จัดรูปแบบวันที่เป็น yyyy-mm-dd
        const formattedStartDate = startDate.toISOString().split('T')[0];
        const formattedEndDate = endDate.toISOString().split('T')[0];
        
        // กำหนดค่าให้กับฟอร์มพิมพ์
        $('#form_class_id, #chart_class_id, #excel_class_id').val(classId);
        $('#form_start_date, #chart_start_date, #excel_start_date').val(formattedStartDate);
        $('#form_end_date, #chart_end_date, #excel_end_date').val(formattedEndDate);
        $('#form_week_number, #chart_week_number, #excel_week_number').val(startWeek);
        $('#form_end_week').val(endWeek);
        $('#form_search, #excel_search').val(searchTerm);
        
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
});
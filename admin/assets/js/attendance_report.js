/**
 * attendance_report.js - JavaScript สำหรับหน้ารายงานการเข้าแถว
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า DateRangePicker
    initDateRangePicker();
    
    // อัพเดทวันที่เมื่อเปลี่ยนสัปดาห์
    $('#week_number').on('change', calculateWeekDates);
    
    // เมื่อเลือกแผนกวิชา
    $('#department_id').on('change', function() {
        const departmentId = $(this).val();
        if (departmentId) {
            loadClassesByDepartment(departmentId);
        } else {
            resetClassSelect();
        }
    });
    
    // Preview Logo
    $('#preview_logo').on('click', function() {
        const fileInput = document.getElementById('school_logo');
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#logoPreviewImage').attr('src', e.target.result);
            };
            reader.readAsDataURL(fileInput.files[0]);
            $('#logoPreviewModal').modal('show');
        } else {
            // ตรวจสอบว่ามีโลโก้ที่บันทึกไว้หรือไม่
            $.ajax({
                url: 'ajax/get_school_logo.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.logo_url) {
                        $('#logoPreviewImage').attr('src', response.logo_url);
                    } else {
                        $('#logoPreviewImage').attr('src', '../uploads/logos/school_logo_default.png');
                    }
                    $('#logoPreviewModal').modal('show');
                },
                error: function() {
                    $('#logoPreviewImage').attr('src', '../uploads/logos/school_logo_default.png');
                    $('#logoPreviewModal').modal('show');
                }
            });
        }
    });
    
    // เมื่อกดปุ่มพิมพ์รายงาน PDF
    $('#btnPdfReport').on('click', function() {
        if (validateReportForm()) {
            showLoadingOverlay();
            generatePdfReport();
        }
    });
    
    // เมื่อกดปุ่มส่งออก Excel
    $('#btnExcelReport').on('click', function() {
        if (validateReportForm()) {
            showLoadingOverlay();
            generateExcelReport();
        }
    });
    
    // เริ่มต้นคำนวณวันที่
    calculateWeekDates();
});

/**
 * ตั้งค่า DateRangePicker
 */
function initDateRangePicker() {
    $('#date_range').daterangepicker({
        opens: 'left',
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' - ',
            applyLabel: 'ตกลง',
            cancelLabel: 'ยกเลิก',
            fromLabel: 'จาก',
            toLabel: 'ถึง',
            customRangeLabel: 'กำหนดเอง',
            weekLabel: 'W',
            daysOfWeek: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
            monthNames: [
                'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
            ],
            firstDay: 1
        }
    }, function(start, end, label) {
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
    });
}

/**
 * คำนวณวันที่เริ่มต้นและสิ้นสุดของสัปดาห์ที่เลือก
 */
function calculateWeekDates() {
    const weekNumber = parseInt($('#week_number').val());
    const semesterStartDate = new Date($('#semester_start_date').val());
    
    // คำนวณวันแรกของสัปดาห์ที่เลือก (เพิ่ม (weekNumber - 1) * 7 วันจากวันเริ่มต้นภาคเรียน)
    const startDate = new Date(semesterStartDate);
    startDate.setDate(startDate.getDate() + (weekNumber - 1) * 7);
    
    // ปรับให้เป็นวันจันทร์
    const dayOfWeek = startDate.getDay(); // 0 = อาทิตย์, 1 = จันทร์, ...
    if (dayOfWeek === 0) { // ถ้าเป็นวันอาทิตย์ ให้เลื่อนไป 1 วัน (เป็นวันจันทร์)
        startDate.setDate(startDate.getDate() + 1);
    } else if (dayOfWeek > 1) { // ถ้าไม่ใช่วันจันทร์ ให้ถอยกลับไปเป็นวันจันทร์ล่าสุด
        startDate.setDate(startDate.getDate() - (dayOfWeek - 1));
    }
    
    // คำนวณวันสุดท้ายของสัปดาห์ (วันศุกร์)
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + 4); // เพิ่มอีก 4 วัน (จันทร์ + 4 = ศุกร์)
    
    // ฟอร์แมตวันที่
    const startDateStr = formatDate(startDate);
    const endDateStr = formatDate(endDate);
    
    // กำหนดค่าให้กับฟิลด์
    $('#start_date').val(startDateStr);
    $('#end_date').val(endDateStr);
    $('#date_range').val(startDateStr + ' - ' + endDateStr);
}

/**
 * ฟอร์แมตวันที่เป็น YYYY-MM-DD
 * 
 * @param {Date} date - วันที่ที่ต้องการฟอร์แมต
 * @return {string} - วันที่ในรูปแบบ YYYY-MM-DD
 */
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * โหลดข้อมูลห้องเรียนตามแผนกวิชา
 * 
 * @param {string} departmentId - รหัสแผนกวิชา
 */
function loadClassesByDepartment(departmentId) {
    $.ajax({
        url: 'ajax/get_classes_by_department.php',
        method: 'GET',
        data: {department_id: departmentId},
        dataType: 'json',
        beforeSend: function() {
            $('#class_id').html('<option value="">กำลังโหลดข้อมูล...</option>').prop('disabled', true).selectpicker('refresh');
        },
        success: function(response) {
            if (response.status === 'success') {
                let options = '<option value="">เลือกห้องเรียน</option>';
                
                response.classes.forEach(function(classItem) {
                    const classLabel = `${classItem.level}/${classItem.group_number} ${classItem.department_name}`;
                    options += `<option value="${classItem.class_id}">${classLabel}</option>`;
                });
                
                $('#class_id').html(options).prop('disabled', false).selectpicker('refresh');
            } else {
                alert('เกิดข้อผิดพลาด: ' + response.error);
                resetClassSelect();
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            resetClassSelect();
        }
    });
}

/**
 * รีเซ็ตตัวเลือกห้องเรียน
 */
function resetClassSelect() {
    $('#class_id').html('<option value="">กรุณาเลือกแผนกวิชาก่อน</option>').prop('disabled', true).selectpicker('refresh');
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 * 
 * @return {boolean} - ผลการตรวจสอบ
 */
function validateReportForm() {
    const departmentId = $('#department_id').val();
    const classId = $('#class_id').val();
    
    if (!departmentId) {
        alert('กรุณาเลือกแผนกวิชา');
        $('#department_id').focus();
        return false;
    }
    
    if (!classId) {
        alert('กรุณาเลือกห้องเรียน');
        $('#class_id').focus();
        return false;
    }
    
    return true;
}

/**
 * สร้างรายงาน PDF
 */
function generatePdfReport() {
    const reportType = $('input[name="report_type"]:checked').val();
    const formData = new FormData($('#reportForm')[0]);
    
    let url = reportType === 'attendance' ? 'print_attendance_report.php' : 'print_attendance_chart.php';
    
    // สร้างฟอร์มและส่งข้อมูล
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank';
    
    // แปลง FormData เป็น hidden inputs
    for (const [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // ซ่อน Loading หลังจากพิมพ์ 2 วินาที
    setTimeout(hideLoadingOverlay, 2000);
}

/**
 * สร้างรายงาน Excel
 */
function generateExcelReport() {
    const formData = new FormData($('#reportForm')[0]);
    
    // สร้างฟอร์มและส่งข้อมูล
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_attendance_excel.php';
    form.target = '_blank';
    
    // แปลง FormData เป็น hidden inputs
    for (const [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // ซ่อน Loading หลังจากส่งออก 2 วินาที
    setTimeout(hideLoadingOverlay, 2000);
}

/**
 * แสดง overlay การโหลด
 */
function showLoadingOverlay() {
    $('#loadingOverlay').fadeIn(200);
}

/**
 * ซ่อน overlay การโหลด
 */
function hideLoadingOverlay() {
    $('#loadingOverlay').fadeOut(200);
}
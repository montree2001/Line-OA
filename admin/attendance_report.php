<?php
/**
 * attendance_report.php - หน้าค้นหาและพิมพ์รายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ดึงข้อมูลรายละเอียดผู้ใช้
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'attendance_report';
$page_title = 'ค้นหาและพิมพ์รายงานการเข้าแถว';
$page_header = 'ระบบค้นหาและพิมพ์รายงานการเข้าแถว';

// ซ่อนค้นหา
$hide_search = true;

// ปีการศึกษาปัจจุบัน
$conn = getDB();
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// แผนกวิชา
$query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
$stmt = $conn->query($query);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนดเส้นทางไปยังไฟล์ CSS และ JS
$extra_css = [
    'assets/css/attendance_report.css',
    'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css',
    'https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
];

$extra_js = [
    'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    'assets/js/attendance_report.js'
];

// กำหนดตัวแปรสำหรับค้นหาเพื่อป้องกัน undefined variable
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
?>

<!-- เนื้อหาหลัก -->
<div class="main-content" id="mainContent">
    <div class="header">
        <h1 class="page-title"><?php echo $page_header; ?></h1>
        <div class="header-actions">
            <?php if(!isset($hide_search) || !$hide_search): ?>
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหา...">
                <button class="search-button">
                    <span class="material-icons">search</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <!-- เนื้อหาเฉพาะหน้า -->
    <div class="content">
        <div class="search-panel">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="department">แผนกวิชา</label>
                        <select id="department" class="form-control">
                            <option value="">-- เลือกแผนกวิชา --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="class">ห้องเรียน</label>
                        <div class="select-container">
                            <select id="class" class="form-control" disabled>
                                <option value="">-- เลือกห้องเรียน --</option>
                            </select>
                            <div id="class-loading" class="select-loading" style="display: none;">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">กำลังโหลด...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_week">เริ่มต้นสัปดาห์ที่</label>
                        <select id="start_week" class="form-control">
                            <option value="">-- เลือกสัปดาห์ --</option>
                            <?php for ($i = 1; $i <= 18; $i++): ?>
                            <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_week">สิ้นสุดสัปดาห์ที่</label>
                        <select id="end_week" class="form-control">
                            <option value="">-- เลือกสัปดาห์ --</option>
                            <?php for ($i = 1; $i <= 18; $i++): ?>
                            <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="search_student">ค้นหานักเรียน</label>
                        <input type="text" id="search_student" class="form-control" placeholder="รหัสนักเรียน, ชื่อ หรือนามสกุล">
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button id="search_btn" class="btn btn-primary">
                        <span class="material-icons">search</span> ค้นหา
                    </button>
                </div>
            </div>
        </div>

        <div class="info-panel mt-4">
            <div class="alert alert-info">
                กรุณาเลือกห้องเรียนและช่วงสัปดาห์เพื่อแสดงรายงานการเข้าแถว
            </div>
        </div>

        <div id="report_container" class="report-container mt-4" style="display: none;">
            <div class="report-header">
                <h3>ข้อมูลรายงานการเข้าแถว</h3>
                <div id="report_info" class="report-summary">
                    <!-- ข้อมูลรายงานจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                </div>
            </div>
            
            <div class="preview-table-container mt-4">
                <div id="preview_loading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p>กำลังโหลดข้อมูล...</p>
                </div>
                <div id="preview_content" style="display: none;">
                    <!-- ตารางแสดงข้อมูลจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                </div>
            </div>
        </div>

        <div class="action-buttons mt-4" style="display: none;">
            <button id="print_pdf_btn" class="btn btn-danger">
                <span class="material-icons">picture_as_pdf</span> พิมพ์รายงาน PDF
            </button>
            <button id="print_chart_btn" class="btn btn-primary">
                <span class="material-icons">bar_chart</span> พิมพ์กราฟสรุป PDF
            </button>
            <button id="export_excel_btn" class="btn btn-success">
                <span class="material-icons">grid_on</span> ส่งออก Excel
            </button>
        </div>
    </div>
</div>

<!-- ฟอร์มซ่อนสำหรับการส่งข้อมูลไปหน้าพิมพ์ -->
<form id="print_form" method="POST" action="print_attendance_report.php" target="_blank" style="display: none;">
    <input type="hidden" name="class_id" id="form_class_id">
    <input type="hidden" name="start_date" id="form_start_date">
    <input type="hidden" name="end_date" id="form_end_date">
    <input type="hidden" name="week_number" id="form_week_number">
    <input type="hidden" name="end_week" id="form_end_week">
    <input type="hidden" name="report_type" id="form_report_type" value="attendance">
    <input type="hidden" name="search" id="form_search">
</form>

<!-- ฟอร์มซ่อนสำหรับการส่งข้อมูลไปหน้าพิมพ์กราฟ -->
<form id="chart_form" method="POST" action="print_attendance_chart.php" target="_blank" style="display: none;">
    <input type="hidden" name="class_id" id="chart_class_id">
    <input type="hidden" name="start_date" id="chart_start_date">
    <input type="hidden" name="end_date" id="chart_end_date">
    <input type="hidden" name="week_number" id="chart_week_number">
    <input type="hidden" name="report_type" id="chart_report_type" value="chart">
</form>

<!-- ฟอร์มซ่อนสำหรับการส่งออก Excel -->
<form id="excel_form" method="POST" action="export_attendance_excel.php" target="_blank" style="display: none;">
    <input type="hidden" name="class_id" id="excel_class_id">
    <input type="hidden" name="start_date" id="excel_start_date">
    <input type="hidden" name="end_date" id="excel_end_date">
    <input type="hidden" name="week_number" id="excel_week_number">
    <input type="hidden" name="search" id="excel_search">
</form>

<!-- เพิ่ม JavaScript เฉพาะหน้า -->
<script>
    // กำหนดข้อมูลปีการศึกษาสำหรับใช้ใน JavaScript
    const academicYear = {
        academic_year_id: <?php echo $academic_year['academic_year_id']; ?>,
        year: <?php echo $academic_year['year']; ?>,
        semester: <?php echo $academic_year['semester']; ?>,
        start_date: "<?php echo $academic_year['start_date']; ?>",
        end_date: "<?php echo $academic_year['end_date']; ?>"
    };
    
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
        
        // ฟังก์ชันโหลดข้อมูลการเข้าแถว
        function loadAttendancePreview(classId, startDate, endDate, startWeek, endWeek, searchTerm) {
            // แสดง loading
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
                    
                    if (response.status === 'success') {
                        // แสดงข้อมูลรายงาน
                        const selectedClass = $('#class option:selected').text();
                        
                        $('#report_info').html(`
                            <p><strong>ห้องเรียน:</strong> ${selectedClass}</p>
                            <p><strong>ช่วงวันที่:</strong> ${formatThaiDate(startDate)} ถึง ${formatThaiDate(endDate)}</p>
                            <p><strong>สัปดาห์ที่:</strong> ${startWeek} - ${endWeek}</p>
                        `);
                        
                        // สร้างตารางแสดงข้อมูล
                        let weekTable = '';
                        
                        // สร้างตารางสำหรับแต่ละสัปดาห์
                        for (let week = parseInt(startWeek); week <= parseInt(endWeek); week++) {
                            // คำนวณวันที่เริ่มต้นและสิ้นสุดของสัปดาห์
                            const weekStartDate = new Date(academicYear.start_date);
                            weekStartDate.setDate(weekStartDate.getDate() + (week - 1) * 7);
                            
                            const weekEndDate = new Date(weekStartDate);
                            weekEndDate.setDate(weekEndDate.getDate() + 6);
                            
                            // กรองวันที่เฉพาะของสัปดาห์นี้
                            const weekDays = response.week_days.filter(day => {
                                const dayDate = new Date(day.date);
                                return dayDate >= weekStartDate && dayDate <= weekEndDate;
                            });
                            
                            if (weekDays.length === 0) continue;
                            
                            weekTable += `
                                <div class="week-table mb-4">
                                    <h5>สัปดาห์ที่ ${week} (${formatThaiDate(weekStartDate.toISOString().split('T')[0])} - ${formatThaiDate(weekEndDate.toISOString().split('T')[0])})</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th rowspan="2" style="vertical-align: middle;">ลำดับที่</th>
                                                    <th rowspan="2" style="vertical-align: middle;">รหัสนักศึกษา</th>
                                                    <th rowspan="2" style="vertical-align: middle;">ชื่อ-สกุล</th>
                                                    <th colspan="${weekDays.length}" class="text-center">วันที่</th>
                                                    <th rowspan="2" style="vertical-align: middle;">รวม</th>
                                                </tr>
                                                <tr>
                            `;
                            
                            // สร้างหัวตารางสำหรับวันที่
                            weekDays.forEach(day => {
                                weekTable += `
                                    <th class="text-center">
                                        ${day.day_num}<br>
                                        ${day.day_name}
                                    </th>
                                `;
                            });
                            
                            weekTable += `
                                                </tr>
                                            </thead>
                                            <tbody>
                            `;
                            
                            // สร้างแถวสำหรับนักเรียนแต่ละคน
                            let rowNum = 1;
                            response.students.forEach(student => {
                                // คำนวณจำนวนวันที่มาเรียน
                                let totalPresent = 0;
                                
                                weekTable += `
                                    <tr>
                                        <td class="text-center">${rowNum}</td>
                                        <td class="text-center">${student.student_code}</td>
                                        <td>${student.title}${student.first_name} ${student.last_name}</td>
                                `;
                                
                                // สร้างเซลล์สำหรับแต่ละวัน
                                weekDays.forEach(day => {
                                    if (day.is_holiday) {
                                        weekTable += `<td class="text-center bg-light">หยุด</td>`;
                                    } else if (response.attendance_data[student.student_id] && response.attendance_data[student.student_id][day.date]) {
                                        const status = response.attendance_data[student.student_id][day.date];
                                        let statusText = '';
                                        let statusClass = '';
                                        
                                        if (status === 'present') {
                                            statusText = 'มา';
                                            statusClass = 'bg-success text-white';
                                            totalPresent++;
                                        } else if (status === 'absent') {
                                            statusText = 'ขาด';
                                            statusClass = 'bg-danger text-white';
                                        } else if (status === 'late') {
                                            statusText = 'สาย';
                                            statusClass = 'bg-warning';
                                            totalPresent++; // นับสายเป็นมาเรียน
                                        } else if (status === 'leave') {
                                            statusText = 'ลา';
                                            statusClass = 'bg-info text-white';
                                        }
                                        
                                        weekTable += `<td class="text-center ${statusClass}">${statusText}</td>`;
                                    } else {
                                        weekTable += `<td class="text-center">-</td>`;
                                    }
                                });
                                
                                // เพิ่มคอลัมน์รวม
                                weekTable += `
                                        <td class="text-center">${totalPresent}</td>
                                    </tr>
                                `;
                                
                                rowNum++;
                            });
                            
                            weekTable += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('#preview_content').html(weekTable).show();
                        
                        // แสดงปุ่มสำหรับพิมพ์รายงาน
                        $('.action-buttons').show();
                    } else {
                        alert('ไม่สามารถโหลดข้อมูลการเข้าแถวได้: ' + (response.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                        $('#report_container').hide();
                        $('.info-panel').show();
                    }
                },
                error: function() {
                    $('#preview_loading').hide();
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                    $('#report_container').hide();
                    $('.info-panel').show();
                }
            });
        }
        
        // ฟังก์ชันจัดรูปแบบวันที่เป็น d/m/yyyy (ภาษาไทย)
        function formatThaiDate(dateStr) {
            const date = new Date(dateStr);
            return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear() + 543}`;
        }
    });
</script>

<style>
    /* เพิ่มสไตล์ CSS เฉพาะหน้านี้ */
    .search-panel {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .report-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .report-header {
        margin-bottom: 20px;
    }
    
    .report-header h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
    }
    
    .report-summary p {
        margin-bottom: 10px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th, .table td {
        border: 1px solid #dee2e6;
        padding: 8px;
    }
    
    .table thead th {
        vertical-align: bottom;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .text-center {
        text-align: center;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    
    .bg-light {
        background-color: #f8f9fa;
    }
    
    .bg-success {
        background-color: #28a745;
    }
    
    .bg-danger {
        background-color: #dc3545;
    }
    
    .bg-warning {
        background-color: #ffc107;
    }
    
    .bg-info {
        background-color: #17a2b8;
    }
    
    .text-white {
        color: white;
    }
    
    .week-table {
        margin-bottom: 30px;
    }
    
    .week-table h5 {
        margin-bottom: 15px;
        color: #333;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        font-weight: 500;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
    }
    
    .btn .material-icons {
        margin-right: 8px;
        font-size: 20px;
    }
    
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
    }
    
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #c82333;
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .d-flex {
        display: flex;
    }
    
    .align-items-end {
        align-items: flex-end;
    }
    
    .mt-3 {
        margin-top: 15px;
    }
    
    .mt-4 {
        margin-top: 20px;
    }
    
    /* ปรับแต่ง Select2 */
    .select2-container {
        display: block;
        width: 100% !important;
    }
    
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        padding-left: 12px;
        color: #495057;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    .select2-dropdown {
        border-color: #ced4da;
    }
    
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da;
        padding: 6px;
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
    }
    
    /* แก้ไขปัญหาการแสดงผลซ้ำในกรอบค้นหา */
    .select2-container--default .select2-search--dropdown {
        position: relative;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 20px;
    }
    
    .select2-container--default .select2-results > .select2-results__options {
        max-height: 200px;
        overflow-y: auto;
    }
    
    .select2-search__field::placeholder {
        color: #aaa;
    }
    
    /* แก้ไขปัญหา dropdown แสดงซ้อนทับ */
    .select2-container--open .select2-dropdown {
        z-index: 9999;
    }
    
    /* ซ่อน dropdown arrow เมื่อใช้ select2 */
    select.select2-hidden-accessible + .select2-container--default .select2-selection--single .select2-selection__arrow {
        display: none;
    }
    
    /* แก้ปัญหาการซ้อนทับของรายการซ้ำ */
    .select2-results__option {
        padding: 8px 12px;
        font-size: 16px;
    }
    
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f2f2f2;
    }
    
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0d6efd;
        color: white;
    }
    
    /* กล่องบรรจุตัวเลือก */
    .select-container {
        position: relative;
    }
    
    .select-loading {
        position: absolute;
        right: 30px;
        top: 10px;
    }
    
    /* สไตล์สำหรับ spinner */
    .spinner-border {
        display: inline-block;
        width: 2rem;
        height: 2rem;
        vertical-align: text-bottom;
        border: 0.25em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spinner-border .75s linear infinite;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.2em;
    }
    
    @keyframes spinner-border {
        to { transform: rotate(360deg); }
    }
    
    .visually-hidden {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
</style>

<?php
// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
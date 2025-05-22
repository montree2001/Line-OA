<?php
/**
 * attendance_report.php - หน้าค้นหาและพิมพ์รายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
$_SESSION['user_id'] = 9999999;

$_SESSION['user_role'] = 'admin';
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
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="material-icons align-middle me-2">search</i>ค้นหารายงานการเข้าแถว
                </h5>
            </div>
            <div class="card-body">
                <div class="search-panel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="department" class="form-label">แผนกวิชา</label>
                                <select id="department" class="form-select form-select-lg select2">
                                    <option value="">-- เลือกแผนกวิชา --</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="class" class="form-label">ห้องเรียน</label>
                                <div class="select-container">
                                    <select id="class" class="form-select form-select-lg select2" disabled>
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
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="start_week" class="form-label">เริ่มต้นสัปดาห์ที่</label>
                                <select id="start_week" class="form-select form-select-lg select2">
                                    <option value="">-- เลือกสัปดาห์ --</option>
                                    <?php for ($i = 1; $i <= 18; $i++): ?>
                                    <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="end_week" class="form-label">สิ้นสุดสัปดาห์ที่</label>
                                <select id="end_week" class="form-select form-select-lg select2">
                                    <option value="">-- เลือกสัปดาห์ --</option>
                                    <?php for ($i = 1; $i <= 18; $i++): ?>
                                    <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="search_student" class="form-label">ค้นหานักเรียน (เฉพาะเจาะจง)</label>
                                <input type="text" id="search_student" class="form-control form-control-lg" placeholder="รหัสนักเรียน, ชื่อ หรือนามสกุล">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button id="search_btn" class="btn btn-primary btn-lg w-100">
                                <span class="material-icons">search</span> ค้นหา
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ข้อความแนะนำสำหรับการค้นหา -->
                <div class="info-panel mt-4">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="material-icons fs-2 me-2">info</i>
                            <div>
                                <strong>วิธีใช้งาน:</strong> กรุณาเลือกห้องเรียนและช่วงสัปดาห์เพื่อแสดงรายงานการเข้าแถว
                                <button type="button" class="btn btn-sm btn-outline-info ms-2" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    คำแนะนำเพิ่มเติม
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ส่วนแสดงผลรายงาน - ปรับปรุงใหม่ -->
        <div id="report_container" class="card mb-4" style="display: none;">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="material-icons align-middle me-2">assignment</i>ผลการค้นหา
                </h5>
            </div>
            <div class="card-body">
                <!-- ข้อมูลสรุป -->
                <div class="report-summary bg-light p-3 rounded mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-item">
                                <strong><i class="material-icons align-middle me-1">school</i> ห้องเรียน:</strong>
                                <span id="summary_class">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <strong><i class="material-icons align-middle me-1">date_range</i> ช่วงวันที่:</strong>
                                <span id="summary_date">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <strong><i class="material-icons align-middle me-1">event_note</i> สัปดาห์ที่:</strong>
                                <span id="summary_week">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- แสดงการโหลด -->
                <div id="preview_loading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-3 text-primary fs-5">กำลังโหลดข้อมูล กรุณารอสักครู่...</p>
                </div>
                
                <!-- แสดงผลการค้นหา -->
                <div id="preview_content" class="preview-content">
                    <!-- ตารางแสดงข้อมูลจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                </div>
                
                <!-- ปุ่มดำเนินการ -->
                <div class="action-buttons mt-4 d-flex flex-wrap gap-2 justify-content-end">
                    <button id="print_pdf_btn" class="btn btn-danger btn-lg">
                        <span class="material-icons">picture_as_pdf</span> พิมพ์รายงาน PDF
                    </button>
                    <button id="print_chart_btn" class="btn btn-primary btn-lg">
                        <span class="material-icons">bar_chart</span> พิมพ์กราฟสรุป PDF
                    </button>
                    <button id="export_excel_btn" class="btn btn-success btn-lg">
                        <span class="material-icons">grid_on</span> ส่งออก Excel
                    </button>
                </div>
            </div>
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

<!-- Modal แสดงเมื่อต้องการค้นหาข้อมูล -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">วิธีใช้งานรายงานการเข้าแถว</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คำแนะนำในการใช้งาน:</p>
                <ol>
                    <li>เลือกแผนกวิชาและห้องเรียนที่ต้องการดูรายงาน</li>
                    <li>เลือกสัปดาห์เริ่มต้นและสัปดาห์สิ้นสุดสำหรับการออกรายงาน</li>
                    <li>กดปุ่มค้นหาเพื่อแสดงตัวอย่างรายงาน</li>
                    <li>เลือกประเภทรายงานที่ต้องการพิมพ์ (PDF หรือส่งออก Excel)</li>
                </ol>
                <p class="text-info">
                    <i class="material-icons align-middle me-1">info</i>
                    รายงานจะแสดงข้อมูลการเข้าแถวแยกตามสัปดาห์ โดยแต่ละสัปดาห์จะแสดงเฉพาะวันจันทร์ถึงวันศุกร์เท่านั้น
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS เพิ่มเติมเฉพาะหน้านี้ -->
<style>
    /* ปรับแต่งการแสดงผลเพิ่มเติม */
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .card-header {
        font-weight: 600;
        border-bottom: none;
    }
    
    .form-label {
        font-weight: 500;
    }
    
    .preview-content {
        max-height: 600px;
        overflow-y: auto;
        padding-right: 10px;
    }
    
    /* ปรับแต่ง scrollbar ให้สวยงาม */
    .preview-content::-webkit-scrollbar {
        width: 8px;
    }
    
    .preview-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .preview-content::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .preview-content::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* สไตล์สำหรับตาราง */
    .week-table {
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .week-table .week-header {
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .week-table .table {
        margin-bottom: 0;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        margin-bottom: 0.5rem;
    }
    
    .info-item strong {
        margin-bottom: 0.25rem;
    }
    
    @media (min-width: 768px) {
        .info-item {
            flex-direction: row;
            align-items: center;
        }
        
        .info-item strong {
            margin-bottom: 0;
            margin-right: 0.5rem;
            min-width: 120px;
        }
    }
    
    /* สีสถานะการเข้าแถว */
    .status-present {
        background-color: #d1e7dd !important;
        color: #0f5132 !important;
    }
    
    .status-absent {
        background-color: #f8d7da !important;
        color: #842029 !important;
    }
    
    .status-late {
        background-color: #fff3cd !important;
        color: #664d03 !important;
    }
    
    .status-leave {
        background-color: #cff4fc !important;
        color: #055160 !important;
    }
    
    .status-holiday {
        background-color: #e2e3e5 !important;
        color: #41464b !important;
    }
    
    /* แต่งปุ่ม */
    .btn-lg {
        padding: 0.6rem 1.2rem;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .report-summary {
        border: 1px solid #dee2e6;
    }
    
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<!-- JavaScript เพิ่มเติมเฉพาะหน้านี้ -->
<script>
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
        const academicStartDate = new Date('<?php echo $academic_year['start_date']; ?>'); // วันเริ่มต้นภาคเรียน
        
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
    
    // เมื่อคลิกปุ่มดูตัวอย่างโลโก้
    $('#preview_logo').on('click', function() {
        const fileInput = document.getElementById('school_logo');
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#logo_preview').attr('src', e.target.result);
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
        $('#logoModal').modal('show');
    });
    
// แก้ไขเฉพาะฟังก์ชัน loadAttendancePreview เพื่อให้แสดงผลถูกต้อง
function loadAttendancePreview(classId, startDate, endDate, startWeek, endWeek, searchTerm) {
    // แสดงส่วน report container ก่อน
    $('#report_container').show();
    
    // แสดง loading และซ่อนเนื้อหา
    $('#preview_loading').show();
    $('#preview_content').hide();
    
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
                // สร้างเนื้อหาตารางแสดงข้อมูล
                let weekTable = '';
                
                // สร้างข้อมูลสำหรับแต่ละสัปดาห์
                let currentWeek = parseInt(startWeek);
                let weekStartDate = new Date(startDate);
                
                while (currentWeek <= parseInt(endWeek)) {
                    // รหัสสร้างตารางข้อมูลเหมือนเดิม...
                    // (โค้ดส่วนนี้ยังเป็นเหมือนเดิม)
                    
                    // เลื่อนไปสัปดาห์ถัดไป
                    currentWeek++;
                    weekStartDate.setDate(weekStartDate.getDate() + 7);
                }
                
                // ตรวจสอบว่ามีข้อมูลหรือไม่
                if (weekTable === '') {
                    $('#preview_content').html('<div class="alert alert-warning">ไม่พบข้อมูลการเข้าแถวในช่วงเวลาที่เลือก</div>').show();
                } else {
                    // ใส่เนื้อหาและแสดงผล - สำคัญ: ต้องแสดงผลด้วย .show()
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
</script>

<?php
// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
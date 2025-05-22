<?php

/**
 * attendance_report.php - หน้าค้นหาและพิมพ์รายงานการเข้าแถว (ปรับปรุงใหม่)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();


// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';


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

// คำนวณจำนวนสัปดาห์ทั้งหมด
$start_date = new DateTime($academic_year['start_date']);
$end_date = new DateTime($academic_year['end_date']);
$total_days = $start_date->diff($end_date)->days;
$total_weeks = ceil($total_days / 7);

// แผนกวิชา
$query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
$stmt = $conn->query($query);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนดเส้นทางไปยังไฟล์ CSS และ JS
$extra_css = [
    'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css',
    'https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css'
];

// ใช้ไฟล์ JavaScript ที่ปรับปรุงแล้วเท่านั้น
$extra_js = [
    'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js'

];

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . ' - STUDENT-Prasat' : 'STUDENT-Prasat'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="assets/css/import.css" rel="stylesheet">

    <!-- ทดสอบโหลด jQuery และ Bootstrap โดยตรงจาก CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* attendance_report.css */
        .report-container {
            padding: 20px 0;
        }

        /* Card styling */
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: none;
        }

        .card-header {
            padding: 15px 20px;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }

        .card-header .card-title {
            margin-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .card-body {
            padding: 25px;
        }

        /* Alert styling */
        .alert-info {
            background-color: #e3f2fd;
            border-color: #bbdefb;
            color: #0d47a1;
        }

        .alert-info .material-icons {
            color: #1976d2;
        }

        /* Form controls */
        .form-label {
            margin-bottom: 8px;
            color: #333;
        }

        .form-select,
        .form-control {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            height: auto;
            font-size: 16px;
        }

        .form-select:focus,
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            border-color: #28a745;
        }

        .input-group-text {
            border-radius: 0 8px 8px 0;
            background-color: #f8f9fa;
        }

        /* Report type cards */
        .report-type-cards .btn-outline-primary {
            border-width: 2px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .report-type-cards .btn-check:checked+.btn-outline-primary {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }

        .report-type-cards .btn-check:checked+.btn-outline-primary i {
            color: #fff;
        }

        .report-type-card {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-type-card i {
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .report-type-card:hover i {
            color: #28a745;
        }

        /* Buttons */
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-success {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-success:hover,
        .btn-success:focus {
            background-color: #138496;
            border-color: #117a8b;
        }

        .btn-lg {
            padding: 10px 20px;
            font-size: 16px;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner-container {
            text-align: center;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Bootstrap Select customization */
        .bootstrap-select .dropdown-toggle {
            padding: 10px 15px;
            border-radius: 8px;
        }

        .bootstrap-select .dropdown-toggle:focus {
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
            border-color: #28a745;
        }

        .bootstrap-select .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 15px;
            }

            .report-type-card {
                min-height: 180px;
            }
        }

        /* DateRangePicker customization */
        .daterangepicker {
            font-family: 'Prompt', sans-serif;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .daterangepicker .calendar-table {
            border-radius: 8px;
        }

        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background-color: #28a745;
        }

        .daterangepicker .drp-buttons .btn {
            font-size: 14px;
            border-radius: 6px;
        }

        .daterangepicker .drp-buttons .applyBtn {
            background-color: #28a745;
            border-color: #28a745;
        }

        /* Signers section styling */
        .border-bottom {
            border-bottom: 2px solid #e9ecef !important;
        }
    </style>

    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <!-- ปุ่มเปิด/ปิดเมนูสำหรับมือถือ -->
    <button class="menu-toggle" id="menuToggle" aria-label="เปิด/ปิดเมนู">
        <span class="material-icons">menu</span>
    </button>

    <!-- Overlay สำหรับมือถือ -->
    <div class="overlay" id="overlay"></div>

    <!-- เนื้อหาหลัก -->
    <div class="container-fluid px-0">
  
        <div class="header">
            <h1 class="page-title"><?php echo $page_header; ?></h1>
            <div class="header-actions">
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="material-icons">help</i> คำแนะนำ
                </button>
            </div>
        </div>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show">' . $_SESSION['success_message'] .
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['success_message']);
        }

        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show">' . $_SESSION['error_message'] .
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <!-- เนื้อหาเฉพาะหน้า -->
        <div class="content">
            <!-- ฟอร์มค้นหา -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="material-icons align-middle me-2">search</i>ค้นหารายงานการเข้าแถว
                    </h5>
                </div>
                <div class="card-body">
                    <form id="searchForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="search_type" class="form-label fw-bold">ประเภทการค้นหา</label>
                                <select id="search_type" class="form-select form-select-lg">
                                    <option value="class">ค้นหาตามห้องเรียน</option>
                                    <option value="student">ค้นหาตามนักเรียน</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search_input" class="form-label fw-bold">ค้นหา</label>
                                <input type="text" id="search_input" class="form-control form-control-lg"
                                    placeholder="ชื่อ, นามสกุล หรือรหัสนักเรียน" style="display: none;">
                            </div>
                        </div>

                        <!-- ส่วนค้นหาตามห้องเรียน -->
                        <div id="class_search_section">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="department" class="form-label fw-bold">แผนกวิชา</label>
                                    <select id="department" class="form-select form-select-lg select2">
                                        <option value="">-- เลือกแผนกวิชา --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="class" class="form-label fw-bold">ห้องเรียน</label>
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

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_week" class="form-label fw-bold">เริ่มต้นสัปดาห์ที่</label>
                                <select id="start_week" class="form-select form-select-lg select2">
                                    <option value="">-- เลือกสัปดาห์ --</option>
                                    <?php for ($i = 1; $i <= $total_weeks; $i++): ?>
                                        <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="end_week" class="form-label fw-bold">สิ้นสุดสัปดาห์ที่</label>
                                <select id="end_week" class="form-select form-select-lg select2">
                                    <option value="">-- เลือกสัปดาห์ --</option>
                                    <?php for ($i = 1; $i <= $total_weeks; $i++): ?>
                                        <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12 d-flex align-items-end">
                                <button id="search_btn" type="button" class="btn btn-primary btn-lg w-100">
                                    <span class="material-icons">search</span> ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- ข้อความแนะนำ -->
                    <div class="info-panel mt-4" id="info_panel">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="material-icons fs-2 me-2">info</i>
                                <div>
                                    <strong>วิธีใช้งาน:</strong> เลือกประเภทการค้นหา จากนั้นเลือกข้อมูลที่ต้องการและกดค้นหา
                                    <ul class="mb-0 mt-2">
                                        <li>ค้นหาตามห้องเรียน: เลือกแผนกและห้องเรียน</li>
                                        <li>ค้นหาตามนักเรียน: พิมพ์ชื่อ นามสกุล หรือรหัสนักเรียน</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ส่วนแสดงผลรายงาน -->
            <div id="report_container" class="card mb-4" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="material-icons align-middle me-2">assignment</i>ผลการค้นหา
                    </h5>
                </div>
                <div class="card-body">
                    <!-- ข้อมูลสรุป -->
                    <div class="report-summary bg-light p-3 rounded mb-4" id="report_summary">
                        <!-- ข้อมูลสรุปจะถูกเพิ่มด้วย JavaScript -->
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
                    <div class="action-buttons mt-4 d-flex flex-wrap gap-2 justify-content-end" style="display: none!important;" id="action_buttons">
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
        <input type="hidden" name="search_type" id="form_search_type">
    </form>

    <!-- ฟอร์มซ่อนสำหรับการส่งข้อมูลไปหน้าพิมพ์กราฟ -->
    <form id="chart_form" method="POST" action="print_attendance_chart.php" target="_blank" style="display: none;">
        <input type="hidden" name="class_id" id="chart_class_id">
        <input type="hidden" name="start_date" id="chart_start_date">
        <input type="hidden" name="end_date" id="chart_end_date">
        <input type="hidden" name="week_number" id="chart_week_number">
        <input type="hidden" name="end_week" id="chart_end_week">
        <input type="hidden" name="report_type" id="chart_report_type" value="chart">
        <input type="hidden" name="search" id="chart_search">
        <input type="hidden" name="search_type" id="chart_search_type">
    </form>

    <!-- ฟอร์มซ่อนสำหรับการส่งออก Excel -->
    <form id="excel_form" method="POST" action="export_attendance_excel.php" target="_blank" style="display: none;">
        <input type="hidden" name="class_id" id="excel_class_id">
        <input type="hidden" name="start_date" id="excel_start_date">
        <input type="hidden" name="end_date" id="excel_end_date">
        <input type="hidden" name="week_number" id="excel_week_number">
        <input type="hidden" name="end_week" id="excel_end_week">
        <input type="hidden" name="search" id="excel_search">
        <input type="hidden" name="search_type" id="excel_search_type">
    </form>

    <!-- Modal คำแนะนำ -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">วิธีใช้งานรายงานการเข้าแถว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>คำแนะนำในการใช้งาน:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">ค้นหาตามห้องเรียน:</h6>
                            <ol>
                                <li>เลือกแผนกวิชาและห้องเรียนที่ต้องการดูรายงาน</li>
                                <li>เลือกสัปดาห์เริ่มต้นและสัปดาห์สิ้นสุด</li>
                                <li>กดปุ่มค้นหา</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">ค้นหาตามนักเรียน:</h6>
                            <ol>
                                <li>เลือกประเภทการค้นหาเป็น "ค้นหาตามนักเรียน"</li>
                                <li>พิมพ์ชื่อ นามสกุล หรือรหัสนักเรียน</li>
                                <li>เลือกช่วงสัปดาห์ที่ต้องการ</li>
                                <li>กดปุ่มค้นหา</li>
                            </ol>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <p class="mb-2"><strong>หมายเหตุ:</strong></p>
                        <ul class="mb-0">
                            <li>รายงานจะแสดงข้อมูลการเข้าแถวเฉพาะวันจันทร์ถึงวันศุกร์</li>
                            <li>แต่ละสัปดาห์ในรายงาน PDF จะแสดงในหน้าแยกกัน</li>
                            <li>วันหยุดราชการจะแสดงเป็นช่องสีเทา</li>
                            <li>สามารถพิมพ์รายงานเป็น PDF และส่งออกเป็น Excel ได้</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ตัวแปร JavaScript สำหรับใช้งาน -->
    <script>
        const academicYear = <?php echo json_encode($academic_year); ?>;
        const totalWeeks = <?php echo $total_weeks; ?>;
    </script>

    <!-- JavaScript เพิ่มเติมเฉพาะหน้านี้ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // เริ่มต้น Select2
            $('.select2').select2({
                width: '100%',
                placeholder: "เลือกหรือค้นหา...",
                allowClear: true
            });

            // เมื่อเปลี่ยนประเภทการค้นหา
            $('#search_type').on('change', function() {
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
                        data: {
                            department_id: departmentId
                        },
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
                    // เพิ่มตัวเลือกสัปดาห์สิ้นสุด (จากสัปดาห์เริ่มต้นถึง totalWeeks)
                    for (let i = startWeek; i <= totalWeeks; i++) {
                        endWeekSelect.append(`<option value="${i}" ${i === startWeek ? 'selected' : ''}>สัปดาห์ที่ ${i}</option>`);
                    }

                    // อัปเดต Select2
                    endWeekSelect.trigger('change');
                }
            });

            // เมื่อคลิกปุ่มค้นหา
            $('#search_btn').on('click', function() {
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

                // เรียกฟังก์ชันค้นหา
                performSearch(searchData);
            });

            // ฟังก์ชันทำการค้นหา
            function performSearch(searchData) {
                // คำนวณวันที่เริ่มต้นและสิ้นสุดจากสัปดาห์
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
                const formattedStartDate = formatDate(startDate);
                const formattedEndDate = formatDate(endDate);

                // กำหนดค่าให้กับฟอร์มพิมพ์
                const forms = ['#print_form', '#chart_form', '#excel_form'];
                forms.forEach(formId => {
                    if (searchData.search_type === 'class') {
                        $(formId + ' input[name="class_id"]').val(searchData.class_id);
                        $(formId + ' input[name="search"]').val('');
                    } else {
                        $(formId + ' input[name="class_id"]').val('');
                        $(formId + ' input[name="search"]').val(searchData.search_input);
                    }

                    $(formId + ' input[name="start_date"]').val(formattedStartDate);
                    $(formId + ' input[name="end_date"]').val(formattedEndDate);
                    $(formId + ' input[name="week_number"]').val(searchData.start_week);
                    $(formId + ' input[name="end_week"]').val(searchData.end_week);
                    $(formId + ' input[name="search_type"]').val(searchData.search_type);
                });

                // โหลดตัวอย่างข้อมูล
                loadAttendancePreview(searchData, formattedStartDate, formattedEndDate);
            }

            // ฟังก์ชันโหลดตัวอย่างข้อมูล
            function loadAttendancePreview(searchData, startDate, endDate) {
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

                $.ajax({
                    url: 'ajax/get_attendance_preview.php',
                    type: 'GET',
                    data: ajaxData,
                    dataType: 'json',
                    success: function(response) {
                        $('#preview_loading').hide();

                        if (response.status === 'success') {
                            displaySearchResults(response, searchData);
                            $('#action_buttons').show();
                        } else {
                            alert('ไม่สามารถโหลดข้อมูลได้: ' + (response.error || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'));
                            $('#report_container').hide();
                            $('#info_panel').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#preview_loading').hide();
                        console.error('AJAX error:', {
                            xhr,
                            status,
                            error
                        });
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                        $('#report_container').hide();
                        $('#info_panel').show();
                    }
                });
            }

            // ฟังก์ชันแสดงผลลัพธ์การค้นหา
            function displaySearchResults(response, searchData) {
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
                    <span>${formatThaiDate(response.start_date)} - ${formatThaiDate(response.end_date)}</span>
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
                    contentHtml = createWeeklyTables(response, searchData);
                } else {
                    contentHtml = '<div class="alert alert-warning">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่ระบุ</div>';
                }

                $('#preview_content').html(contentHtml).show();
            }

            // ฟังก์ชันสร้างตารางรายสัปดาห์
            function createWeeklyTables(response, searchData) {
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
                        const dateStr = formatDate(currentDay);
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
                    tablesHtml += `
                <div class="week-table mb-4">
                    <div class="week-header bg-primary text-white p-3">
                        <h5 class="mb-0">สัปดาห์ที่ ${week}</h5>
                        <small>${formatThaiDate(weekDays[0].date)} - ${formatThaiDate(weekDays[4].date)}</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 60px;" class="text-center">ลำดับ</th>
                                    <th style="width: 120px;" class="text-center">รหัสนักศึกษา</th>
                                    <th class="text-left">ชื่อ-สกุล</th>
            `;

                    // เพิ่มหัวตารางวันที่
                    weekDays.forEach(day => {
                        tablesHtml += `
                    <th style="width: 80px;" class="text-center ${day.is_holiday ? 'table-secondary' : ''}">
                        ${day.day_name}<br>${day.day_num}
                        ${day.is_holiday ? '<br><small>หยุด</small>' : ''}
                    </th>
                `;
                    });

                    tablesHtml += `
                                    <th style="width: 80px;" class="text-center">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

                    // เพิ่มแถวข้อมูลนักเรียน
                    response.students.forEach((student, index) => {
                        let totalPresent = 0;

                        tablesHtml += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center">${student.student_code}</td>
                        <td>${student.title}${student.first_name} ${student.last_name}</td>
                `;

                        // เพิ่มเซลล์สำหรับแต่ละวัน
                        weekDays.forEach(day => {
                            if (day.is_holiday) {
                                tablesHtml += `<td class="text-center table-secondary">หยุด</td>`;
                            } else if (response.attendance_data[student.student_id] && response.attendance_data[student.student_id][day.date]) {
                                const status = response.attendance_data[student.student_id][day.date];
                                let statusText = '',
                                    statusClass = '';

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

                                tablesHtml += `<td class="text-center ${statusClass}">${statusText}</td>`;
                            } else {
                                tablesHtml += `<td class="text-center">-</td>`;
                            }
                        });

                        tablesHtml += `
                        <td class="text-center fw-bold">${totalPresent}</td>
                    </tr>
                `;
                    });

                    tablesHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
                }

                return tablesHtml;
            }

            // เมื่อคลิกปุ่มต่าง ๆ
            $('#print_pdf_btn').on('click', function() {
                $('#print_form').submit();
            });

            $('#print_chart_btn').on('click', function() {
                $('#chart_form').submit();
            });

            $('#export_excel_btn').on('click', function() {
                $('#excel_form').submit();
            });

            // ฟังก์ชันช่วยเหลือ
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

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
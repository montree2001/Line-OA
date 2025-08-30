<?php
/**
 * attendance_report.php - หน้ารายงานการเข้าแถวแบบสาธารณะ (ไม่ต้อง Login)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$page_title = 'รายงานการเข้าแถว - วิทยาลัยการอาชีพปราสาท';
$page_header = 'ระบบรายงานการเข้าแถว';

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="admin/assets/css/attendance_report.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            max-width: 1200px;
            padding: 0;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        
        .header-section h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 600;
        }
        
        .header-section p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .form-select, .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem;
            transition: border-color 0.3s;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .week-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .week-header {
            background: linear-gradient(135deg, #4CAF50, #45a049) !important;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background: #343a40 !important;
            color: white !important;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }
        
        .table td {
            vertical-align: middle;
            text-align: center;
            color: #212529;
            font-weight: 500;
        }
        
        .table-success {
            background-color: #d4edda !important;
            color: #155724 !important;
            font-weight: 600 !important;
        }
        
        .table-danger {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            font-weight: 600 !important;
        }
        
        .table-warning {
            background-color: #fff3cd !important;
            color: #856404 !important;
            font-weight: 600 !important;
        }
        
        .table-info {
            background-color: #d1ecf1 !important;
            color: #0c5460 !important;
            font-weight: 600 !important;
        }
        
        .table-secondary {
            background-color: #e9ecef !important;
            color: #6c757d !important;
            font-weight: 500 !important;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 2rem;
        }
        
        .action-buttons .btn {
            margin: 0.25rem;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s;
            color: #343a40 !important;
            border: 1px solid #dee2e6;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            color: #212529 !important;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            border: none;
            color: white !important;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #51cf66, #40c057);
            border: none;
            color: white !important;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #74c0fc, #339af0);
            border: none;
            color: white !important;
        }
        
        .report-summary {
            color: #212529 !important;
        }
        
        .report-summary .info-item {
            color: #495057 !important;
            margin-bottom: 0.5rem;
        }
        
        .report-summary .info-item strong {
            color: #212529 !important;
        }
        
        .footer-section {
            background: #f8f9fa;
            padding: 1.5rem;
            text-align: center;
            color: #6c757d;
            border-radius: 0 0 20px 20px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .header-section h1 {
                font-size: 2rem;
            }
            
            .content-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1><i class="material-icons" style="font-size: 2.5rem; vertical-align: middle; margin-right: 0.5rem;">school</i><?php echo $page_header; ?></h1>
                <p>วิทยาลัยการอาชีพปราสาท</p>
            </div>

            <!-- Content Section -->
            <div class="content-section">
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

                <!-- ฟอร์มค้นหา -->
                <div class="card">
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
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="material-icons align-middle me-2">assignment</i>ผลการค้นหา
                        </h5>
                        <!-- ปุ่มดำเนินการ -->
                        <div class="action-buttons" id="action_buttons" style="display: none;">
                            <button id="print_pdf_btn" class="btn btn-light btn-sm me-1">
                                <span class="material-icons">picture_as_pdf</span> PDF พิมพ์รายงาน
                            </button>
                            <button id="print_chart_btn" class="btn btn-light btn-sm me-1">
                                <span class="material-icons">bar_chart</span> กราฟ
                            </button>
                            <button id="evaluation_btn" class="btn btn-warning btn-sm me-1">
                                <span class="material-icons">assessment</span> ประเมินผลกิจกรรม
                            </button>
                            <button id="export_excel_btn" class="btn btn-light btn-sm">
                                <span class="material-icons">grid_on</span> Excel
                            </button>
                        </div>
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
                    </div>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="footer-section">
                <p>&copy; 2024 วิทยาลัยการอาชีพปราสาท | ระบบน้องชูใจ AI - ดูแลผู้เรียน</p>
            </div>
        </div>
    </div>

    <!-- ฟอร์มซ่อนสำหรับการส่งข้อมูลไปหน้าพิมพ์ -->
    <form id="print_form" method="POST" action="admin/print_attendance_report.php" target="_blank" style="display: none;">
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
    <form id="chart_form" method="POST" action="admin/print_attendance_chart.php" target="_blank" style="display: none;">
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
    <form id="excel_form" method="POST" action="admin/export_attendance_excel.php" target="_blank" style="display: none;">
        <input type="hidden" name="class_id" id="excel_class_id">
        <input type="hidden" name="start_date" id="excel_start_date">
        <input type="hidden" name="end_date" id="excel_end_date">
        <input type="hidden" name="week_number" id="excel_week_number">
        <input type="hidden" name="end_week" id="excel_end_week">
        <input type="hidden" name="search" id="excel_search">
        <input type="hidden" name="search_type" id="excel_search_type">
    </form>

    <!-- ฟอร์มซ่อนสำหรับการประเมินผลกิจกรรม -->
    <form id="evaluation_form" method="POST" action="print_evaluation_report.php" target="_blank" style="display: none;">
        <input type="hidden" name="class_id" id="eval_class_id">
        <input type="hidden" name="start_date" id="eval_start_date">
        <input type="hidden" name="end_date" id="eval_end_date">
        <input type="hidden" name="week_number" id="eval_week_number">
        <input type="hidden" name="end_week" id="eval_end_week">
        <input type="hidden" name="search" id="eval_search">
        <input type="hidden" name="search_type" id="eval_search_type">
    </form>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

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
                    url: './admin/ajax/get_classes_by_department.php',
                    type: 'GET',
                    data: { department_id: departmentId },
                    dataType: 'json',
                    timeout: 10000,
                    cache: false,
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
            const forms = ['#print_form', '#chart_form', '#excel_form', '#evaluation_form'];
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
                url: './admin/ajax/get_attendance_preview.php',
                type: 'GET',
                data: ajaxData,
                dataType: 'json',
                timeout: 15000,
                cache: false,
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
                    console.error('AJAX error:', { xhr, status, error });
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
        
        $('#evaluation_btn').on('click', function() {
            $('#evaluation_form').submit();
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
</body>
</html>
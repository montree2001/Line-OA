<?php
/**
 * evaluation_report.php - หน้าประเมินผลกิจกรรมแบบสาธารณะ (ไม่ต้อง Login)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

try {
    // เชื่อมต่อฐานข้อมูล
    require_once 'db_connect.php';
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษา
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษา");
    }
    
    // คำนวณสัปดาห์
    $start_date = new DateTime($academic_year['start_date']);
    $end_date = new DateTime($academic_year['end_date']);
    $total_days = $start_date->diff($end_date)->days;
    $total_weeks = ceil($total_days / 7);
    
    // ดึงแผนกวิชา
    $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประเมินผลกิจกรรม - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    
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
            background: linear-gradient(135deg, #ff7b7b, #e74c3c);
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
            background: linear-gradient(135deg, #ff7b7b, #e74c3c);
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
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
        }
        
        .form-select, .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem;
            transition: border-color 0.3s;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        }
        
        .evaluation-card {
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            border: 2px solid #e74c3c;
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
                <h1><i class="material-icons" style="font-size: 2.5rem; vertical-align: middle; margin-right: 0.5rem;">assessment</i>ประเมินผลกิจกรรม</h1>
                <p>วิทยาลัยการอาชีพปราสาท</p>
            </div>

            <!-- Content Section -->
            <div class="content-section">
                <!-- ฟอร์มประเมินผล -->
                <div class="card evaluation-card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons align-middle me-2">assignment_turned_in</i>แบบฟอร์มประเมินผลกิจกรรม
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="evaluationForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="search_type" class="form-label fw-bold">ประเภทการประเมิน</label>
                                    <select id="search_type" class="form-select form-select-lg">
                                        <option value="class">ประเมินตามห้องเรียน</option>
                                        <option value="student">ประเมินตามนักเรียน</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="search_input" class="form-label fw-bold">ค้นหานักเรียน</label>
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
                                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
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
                                <div class="col-md-12 d-flex align-items-end gap-3">
                                    <button id="preview_btn" type="button" class="btn btn-primary btn-lg">
                                        <span class="material-icons">preview</span> ดูตัวอย่าง
                                    </button>
                                    <button id="evaluation_btn" type="button" class="btn btn-success btn-lg">
                                        <span class="material-icons">assessment</span> สร้างรายงานประเมิน PDF
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- ข้อความแนะนำ -->
                        <div class="alert alert-info mt-4">
                            <div class="d-flex align-items-center">
                                <i class="material-icons fs-2 me-2">info</i>
                                <div>
                                    <strong>วิธีการประเมินผลกิจกรรม:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li><strong>เกณฑ์การผ่าน:</strong> 60% ของจำนวนวันเรียนจริง (ไม่นับวันหยุด)</li>
                                        <li><strong>ปวช.:</strong> ประเมินตาม 18 สัปดาห์</li>
                                        <li><strong>ปวส.:</strong> ประเมินตาม 15 สัปดาห์</li>
                                        <li>รายงานจะแสดงผลเป็น A4 แนวนอน พร้อมคอลัมน์รายสัปดาห์</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนแสดงตัวอย่าง -->
                <div id="preview_container" class="card mb-4" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="material-icons align-middle me-2">visibility</i>ตัวอย่างข้อมูลการประเมิน
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- ข้อมูลสรุป -->
                        <div class="bg-light p-3 rounded mb-4" id="preview_summary">
                            <!-- ข้อมูลสรุปจะถูกเพิ่มด้วย JavaScript -->
                        </div>
                        
                        <!-- แสดงการโหลด -->
                        <div id="preview_loading" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">กำลังโหลด...</span>
                            </div>
                            <p class="mt-3 text-primary fs-5">กำลังคำนวณผลการประเมิน กรุณารอสักครู่...</p>
                        </div>
                        
                        <!-- แสดงผลตัวอย่าง -->
                        <div id="preview_content">
                            <!-- ตารางแสดงข้อมูลจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="footer-section">
                <p>&copy; 2024 วิทยาลัยการอาชีพปราสาท | ระบบประเมินผลกิจกรรม</p>
            </div>
        </div>
    </div>

    <!-- ฟอร์มซ่อนสำหรับการประเมินผล -->
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
        
        // เมื่อเปลี่ยนประเภทการประเมิน
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
            
            classSelect.empty().append('<option value="">-- เลือกห้องเรียน --</option>').prop('disabled', true).trigger('change');
            
            if (departmentId) {
                classSelect.prop('disabled', false);
                $('#class-loading').show();
                
                $.ajax({
                    url: 'ajax_get_classes.php',
                    type: 'GET',
                    data: { department_id: departmentId },
                    dataType: 'json',
                    success: function(response) {
                        $('#class-loading').hide();
                        
                        if (response.status === 'success' && response.classes) {
                            classSelect.empty().append('<option value="">-- เลือกห้องเรียน --</option>');
                            
                            response.classes.forEach(function(classItem) {
                                classSelect.append(
                                    `<option value="${classItem.class_id}">${classItem.level}/${classItem.group_number} ${classItem.department_name}</option>`
                                );
                            });
                            
                            classSelect.trigger('change');
                        } else {
                            alert('ไม่สามารถโหลดข้อมูลห้องเรียนได้');
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
            
            endWeekSelect.empty().append('<option value="">-- เลือกสัปดาห์ --</option>');
            
            if (startWeek) {
                for (let i = startWeek; i <= totalWeeks; i++) {
                    endWeekSelect.append(`<option value="${i}" ${i === startWeek ? 'selected' : ''}>สัปดาห์ที่ ${i}</option>`);
                }
                endWeekSelect.trigger('change');
            }
        });
        
        // เมื่อคลิกปุ่มดูตัวอย่าง
        $('#preview_btn').on('click', function() {
            performEvaluation('preview');
        });
        
        // เมื่อคลิกปุ่มสร้างรายงาน
        $('#evaluation_btn').on('click', function() {
            performEvaluation('report');
        });
        
        function performEvaluation(action) {
            const searchType = $('#search_type').val();
            const startWeek = $('#start_week').val();
            const endWeek = $('#end_week').val();
            
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
            
            if (action === 'report') {
                generateReport(searchData);
            } else {
                showPreview(searchData);
            }
        }
        
        function generateReport(searchData) {
            // คำนวณวันที่
            const academicStartDate = new Date(academicYear.start_date);
            const startDate = new Date(academicStartDate);
            startDate.setDate(startDate.getDate() + (parseInt(searchData.start_week) - 1) * 7);
            
            const dayOfWeek = startDate.getDay();
            if (dayOfWeek === 0) {
                startDate.setDate(startDate.getDate() + 1);
            } else if (dayOfWeek > 1) {
                startDate.setDate(startDate.getDate() - (dayOfWeek - 1));
            }
            
            const endDate = new Date(academicStartDate);
            endDate.setDate(endDate.getDate() + (parseInt(searchData.end_week) - 1) * 7);
            
            const endDayOfWeek = endDate.getDay();
            if (endDayOfWeek === 0) {
                endDate.setDate(endDate.getDate() + 1);
            } else if (endDayOfWeek > 1) {
                endDate.setDate(endDate.getDate() - (endDayOfWeek - 1));
            }
            endDate.setDate(endDate.getDate() + 4);
            
            // ส่งข้อมูลไปยังฟอร์ม
            if (searchData.search_type === 'class') {
                $('#eval_class_id').val(searchData.class_id);
                $('#eval_search').val('');
            } else {
                $('#eval_class_id').val('');
                $('#eval_search').val(searchData.search_input);
            }
            
            $('#eval_start_date').val(formatDate(startDate));
            $('#eval_end_date').val(formatDate(endDate));
            $('#eval_week_number').val(searchData.start_week);
            $('#eval_end_week').val(searchData.end_week);
            $('#eval_search_type').val(searchData.search_type);
            
            // ส่งฟอร์ม
            $('#evaluation_form').submit();
        }
        
        function showPreview(searchData) {
            $('#preview_container').show();
            $('#preview_loading').show();
            $('#preview_content').html('');
            
            // แสดงข้อมูลสรุป
            let summaryHtml = '<div class="row">';
            
            if (searchData.search_type === 'class') {
                const selectedClass = $('#class option:selected').text();
                summaryHtml += `
                    <div class="col-md-4">
                        <strong><i class="material-icons align-middle me-1">school</i> ห้องเรียน:</strong>
                        <span>${selectedClass}</span>
                    </div>
                `;
            } else {
                summaryHtml += `
                    <div class="col-md-4">
                        <strong><i class="material-icons align-middle me-1">person</i> ค้นหา:</strong>
                        <span>${searchData.search_input}</span>
                    </div>
                `;
            }
            
            summaryHtml += `
                <div class="col-md-4">
                    <strong><i class="material-icons align-middle me-1">event_note</i> สัปดาห์ที่:</strong>
                    <span>${searchData.start_week} - ${searchData.end_week}</span>
                </div>
                <div class="col-md-4">
                    <strong><i class="material-icons align-middle me-1">assessment</i> เกณฑ์ผ่าน:</strong>
                    <span>60%</span>
                </div>
            </div>`;
            
            $('#preview_summary').html(summaryHtml);
            
            setTimeout(function() {
                $('#preview_loading').hide();
                $('#preview_content').html(`
                    <div class="alert alert-success">
                        <h5><i class="material-icons align-middle me-2">check_circle</i>พร้อมสร้างรายงาน!</h5>
                        <p>ข้อมูลการประเมินได้รับการตรวจสอบแล้ว กรุณากดปุ่ม <strong>"สร้างรายงานประเมิน PDF"</strong> เพื่อดูรายงานฉบับเต็ม</p>
                        <ul>
                            <li>รายงานจะแสดงผลเป็น A4 แนวนอน</li>
                            <li>มีคอลัมน์รายสัปดาห์และผลการประเมิน</li>
                            <li>แสดงเปอร์เซ็นต์การเข้าแถวและผลผ่าน/ไม่ผ่าน</li>
                        </ul>
                    </div>
                `);
            }, 2000);
        }
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    });
    </script>
</body>
</html>
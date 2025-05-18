<?php
/**
 * attendance_report_content.php - เนื้อหาหน้ารายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */
?>

<div class="report-container">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="material-icons align-middle me-2">print</i>พิมพ์รายงานการเข้าแถว
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="material-icons fs-3">info</i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="alert-heading">ข้อมูลรายงาน</h5>
                        <p>รายงานนี้แสดงข้อมูลการเข้าแถวของนักเรียนตามช่วงเวลาที่เลือก โดยแยกตามสัปดาห์ (1 สัปดาห์ต่อ 1 หน้า A4)</p>
                        <p>ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> มีทั้งหมด <?php echo $total_weeks; ?> สัปดาห์</p>
                        <p class="mb-0">เริ่มตั้งแต่วันที่ <?php echo date('d/m/Y', strtotime($academic_year['start_date'])); ?> ถึงวันที่ <?php echo date('d/m/Y', strtotime($academic_year['end_date'])); ?></p>
                    </div>
                </div>
            </div>

            <form id="reportForm" method="post" action="print_attendance_report.php" target="_blank">
                <input type="hidden" id="semester_start_date" value="<?php echo $academic_year['start_date']; ?>">

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="department_id" class="form-label fw-bold">แผนกวิชา</label>
                        <select id="department_id" name="department_id" class="form-select selectpicker" data-live-search="true" required>
                            <option value="">เลือกแผนกวิชา</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="class_id" class="form-label fw-bold">ห้องเรียน</label>
                        <select id="class_id" name="class_id" class="form-select selectpicker" data-live-search="true" required disabled>
                            <option value="">กรุณาเลือกแผนกวิชาก่อน</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="week_number" class="form-label fw-bold">สัปดาห์ที่</label>
                        <select id="week_number" name="week_number" class="form-select" required>
                            <?php for ($i = 1; $i <= $total_weeks; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $current_week) ? 'selected' : ''; ?>>สัปดาห์ที่ <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="date_range" class="form-label fw-bold">ช่วงวันที่</label>
                        <div class="input-group">
                            <input type="text" id="date_range" name="date_range" class="form-control" readonly>
                            <input type="hidden" id="start_date" name="start_date">
                            <input type="hidden" id="end_date" name="end_date">
                            <span class="input-group-text">
                                <i class="material-icons">date_range</i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold d-block mb-3">ประเภทรายงาน</label>
                        <div class="report-type-cards">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="report_type" id="attendance_report" value="attendance" checked>
                                    <label class="btn btn-outline-primary w-100 h-100 py-4 report-type-card" for="attendance_report">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="material-icons mb-3" style="font-size: 48px;">fact_check</i>
                                            <h5 class="mb-2">รายงานการเช็คชื่อ</h5>
                                            <p class="text-muted small mb-0">แสดงตารางข้อมูลการเข้าแถวรายวัน</p>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="report_type" id="chart_report" value="chart">
                                    <label class="btn btn-outline-primary w-100 h-100 py-4 report-type-card" for="chart_report">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="material-icons mb-3" style="font-size: 48px;">bar_chart</i>
                                            <h5 class="mb-2">กราฟสถิติการเข้าแถว</h5>
                                            <p class="text-muted small mb-0">แสดงกราฟและสถิติการเข้าแถว</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" id="btnPdfReport" class="btn btn-primary btn-lg">
                        <i class="material-icons align-middle me-1">picture_as_pdf</i> พิมพ์รายงาน PDF
                    </button>
                    <button type="button" id="btnExcelReport" class="btn btn-success btn-lg">
                        <i class="material-icons align-middle me-1">table_view</i> ส่งออก Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="material-icons align-middle me-2">settings</i>ตั้งค่าการพิมพ์รายงาน
            </h5>
        </div>
        <div class="card-body">
            <form id="reportSettingsForm" method="post" action="save_report_settings.php" enctype="multipart/form-data">
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <h5 class="border-bottom pb-2 mb-3">ข้อมูลสถานศึกษา</h5>
                        <div class="mb-3">
                            <label for="school_logo" class="form-label fw-bold">โลโก้สถานศึกษา</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="school_logo" name="school_logo" accept="image/*">
                                <button class="btn btn-outline-secondary" type="button" id="preview_logo">แสดงตัวอย่าง</button>
                            </div>
                            <div class="form-text">ขนาดแนะนำ 200x200 พิกเซล รูปแบบไฟล์ JPG, PNG</div>
                        </div>
                        <div class="mb-3">
                            <label for="school_name" class="form-label fw-bold">ชื่อสถานศึกษา</label>
                            <input type="text" class="form-control" id="school_name" name="school_name" value="วิทยาลัยการอาชีพปราสาท">
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <h5 class="border-bottom pb-2 mb-3">ข้อมูลผู้ลงนาม</h5>
                        <?php
                        // ดึงข้อมูลผู้ลงนามจากฐานข้อมูล
                        $query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY signer_id LIMIT 3";
                        $stmt = $conn->query($query);
                        $signers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // กำหนดค่าเริ่มต้นสำหรับผู้ลงนาม
                        $defaultSigners = [
                            ['position' => 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา', 'title' => 'นาย', 'first_name' => 'มนตรี', 'last_name' => 'ศรีสุข'],
                            ['position' => 'รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา', 'title' => 'นาย', 'first_name' => 'พงษ์ศักดิ์', 'last_name' => 'สนโศรก'],
                            ['position' => 'ผู้อำนวยการวิทยาลัยการอาชีพปราสาท', 'title' => 'นาย', 'first_name' => 'ชูศักดิ์', 'last_name' => 'ขุ่ยล่ะ']
                        ];
                        ?>

                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <?php
                            $position = isset($signers[$i]) ? $signers[$i]['position'] : $defaultSigners[$i]['position'];
                            $title = isset($signers[$i]) ? $signers[$i]['title'] : $defaultSigners[$i]['title'];
                            $firstName = isset($signers[$i]) ? $signers[$i]['first_name'] : $defaultSigners[$i]['first_name'];
                            $lastName = isset($signers[$i]) ? $signers[$i]['last_name'] : $defaultSigners[$i]['last_name'];
                            ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">ผู้ลงนามที่ <?php echo $i + 1; ?></label>
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="signer_position[]" placeholder="ตำแหน่ง" value="<?php echo $position; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select" name="signer_title[]">
                                            <option value="นาย" <?php echo ($title == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                            <option value="นาง" <?php echo ($title == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                            <option value="นางสาว" <?php echo ($title == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                            <option value="ดร." <?php echo ($title == 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="signer_first_name[]" placeholder="ชื่อ" value="<?php echo $firstName; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="signer_last_name[]" placeholder="นามสกุล" value="<?php echo $lastName; ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons align-middle me-1">save</i> บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แสดงตัวอย่าง Logo -->
<div class="modal fade" id="logoPreviewModal" tabindex="-1" aria-labelledby="logoPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoPreviewModalLabel">ตัวอย่างโลโก้สถานศึกษา</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="logoPreviewImage" src="../uploads/logos/school_logo_default.png" alt="Logo Preview" class="img-fluid" style="max-height: 200px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Overlay การโหลด -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-container">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
        <div class="mt-2">กำลังสร้างรายงาน...</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตั้งค่า DateRangePicker
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
        });
        
        // คำนวณวันที่เริ่มต้นและสิ้นสุดตามสัปดาห์ที่เลือก
        calculateWeekDates();
        
        // อัพเดทวันที่เมื่อเปลี่ยนสัปดาห์
        $('#week_number').on('change', calculateWeekDates);
        
        // เมื่อเลือกแผนกวิชา
        $('#department_id').on('change', function() {
            const departmentId = $(this).val();
            if (departmentId) {
                loadClassesByDepartment(departmentId);
            } else {
                $('#class_id').html('<option value="">กรุณาเลือกแผนกวิชาก่อน</option>').prop('disabled', true).selectpicker('refresh');
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
                $('#logoPreviewImage').attr('src', '../uploads/logos/school_logo_default.png');
                $('#logoPreviewModal').modal('show');
            }
        });
        
        // เมื่อกดปุ่มพิมพ์รายงาน PDF
        $('#btnPdfReport').on('click', function() {
            if (validateReportForm()) {
                showLoadingOverlay();
                
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
        });
        
        // เมื่อกดปุ่มส่งออก Excel
        $('#btnExcelReport').on('click', function() {
            if (validateReportForm()) {
                showLoadingOverlay();
                
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
        });
    });
    
    // คำนวณวันที่เริ่มต้นและสิ้นสุดของสัปดาห์ที่เลือก
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
    
    // ฟอร์แมตวันที่เป็น YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // โหลดข้อมูลห้องเรียนตามแผนกวิชา
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
                    $('#class_id').html('<option value="">กรุณาเลือกแผนกวิชาก่อน</option>').prop('disabled', true).selectpicker('refresh');
                }
            },
            error: function() {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                $('#class_id').html('<option value="">กรุณาเลือกแผนกวิชาก่อน</option>').prop('disabled', true).selectpicker('refresh');
            }
        });
    }
    
    // ตรวจสอบความถูกต้องของฟอร์ม
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
    
    // แสดง overlay การโหลด
    function showLoadingOverlay() {
        $('#loadingOverlay').fadeIn(200);
    }
    
    // ซ่อน overlay การโหลด
    function hideLoadingOverlay() {
        $('#loadingOverlay').fadeOut(200);
    }
</script>
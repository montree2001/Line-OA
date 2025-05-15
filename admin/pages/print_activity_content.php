<?php
// ตรวจสอบการเข้าถึงโดยตรง
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
}

// ตรวจสอบการกำหนดค่าตัวแปรต่างๆ
if (!isset($academic_year) || !isset($departments) || !isset($all_weeks)) {
    echo "<div class='alert alert-danger'>ไม่สามารถโหลดข้อมูลสำหรับรายงานได้</div>";
    return;
}

// แปลงเดือนเป็นภาษาไทย
function getThaiMonth($month) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $thai_months[(int)$month] ?? '';
}

// ระดับหลักสูตรทั้งหมด
$education_levels = ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
?>

<!-- ส่วนการเลือกพารามิเตอร์รายงาน -->
<div class="report-params card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">filter_alt</span>
            เลือกพารามิเตอร์รายงาน
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="academic-year">ภาคเรียน</label>
                    <div class="input-readonly">
                        <?php 
                            $thai_year = $academic_year['year'] + 543;
                            echo "ภาคเรียนที่ {$academic_year['semester']}/{$thai_year}"; 
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="week-select">สัปดาห์</label>
                    <select id="week-select" class="form-control">
                        <?php foreach ($all_weeks as $week): ?>
                        <option value="<?php echo $week['week_number']; ?>" 
                                data-start="<?php echo $week['start_date']; ?>"
                                data-end="<?php echo $week['end_date']; ?>">
                            สัปดาห์ที่ <?php echo $week['week_number']; ?> 
                            (<?php echo $week['start_date_display']; ?> - <?php echo $week['end_date_display']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="department-select">แผนกวิชา</label>
                    <select id="department-select" class="form-control">
                        <option value="">-- เลือกแผนกวิชา --</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>">
                            <?php echo $dept['department_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="class-select">ชั้นเรียน</label>
                    <select id="class-select" class="form-control" disabled>
                        <option value="">-- เลือกชั้นเรียน --</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button id="generate-report-btn" class="btn btn-primary" disabled>
            <span class="material-icons">search</span> แสดงรายงาน
        </button>
        <button id="print-report-btn" class="btn btn-success" disabled>
            <span class="material-icons">print</span> พิมพ์รายงาน
        </button>
        <button id="export-pdf-btn" class="btn btn-danger" disabled>
            <span class="material-icons">picture_as_pdf</span> ส่งออก PDF
        </button>
        <button id="export-excel-btn" class="btn btn-success" disabled>
            <span class="material-icons">table_view</span> ส่งออก Excel
        </button>
    </div>
</div>

<!-- ส่วนแสดงรายงาน -->
<div id="report-container" class="report-container" style="display: none;">
    <div id="report-content" class="report-content">
        <!-- รายงานจะถูกแสดงที่นี่ -->
    </div>
    
    <!-- ส่วนกราฟสรุป -->
    <div id="charts-container" class="charts-container" style="display: none;">
        <div class="chart-card">
            <div class="chart-header">
                <h3>กราฟสรุปการเข้าแถว</h3>
            </div>
            <div class="chart-body">
                <canvas id="attendance-chart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ส่วนแสดงรายงานตัวอย่าง -->
<div id="report-placeholder" class="report-placeholder">
    <div class="report-example">
        <img src="assets/images/report_example.png" alt="ตัวอย่างรายงาน">
        <p>เลือกพารามิเตอร์รายงานและคลิก "แสดงรายงาน" เพื่อสร้างรายงานเช็คชื่อเข้าแถว</p>
    </div>
</div>


<!-- เทมเพลตรายงาน -->
<!-- เทมเพลตรายงาน -->
<template id="report-template">
    <div class="print-wrapper">
        <div class="report-header">
            <div class="report-logo">
                <img src="assets/images/school_logo.png" alt="โลโก้วิทยาลัย">
            </div>
            <div class="report-title">
                <h1>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</h1>
                <h2>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</h2>
                <h3>ภาคเรียนที่ {semester} ปีการศึกษา {year} สัปดาห์ที่ {week} เดือน {month} พ.ศ. {thai_year}</h3>
                <h3>ระดับชั้น {class_level} กลุ่ม {group_number} แผนกวิชา{department_name}</h3>
            </div>
        </div>
        
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="no-col">ลำดับที่</th>
                        <th rowspan="2" class="code-col">รหัสนักศึกษา</th>
                        <th rowspan="2" class="name-col">ชื่อ-สกุล</th>
                        <th colspan="5" class="week-header">สัปดาห์ที่ {week}</th>
                        <th rowspan="2" class="total-col">รวม</th>
                    </tr>
                    <tr class="day-header">
                        <!-- ช่องวันจะถูกเพิ่มแบบไดนามิกโดย JavaScript -->
                    </tr>
                </thead>
                <tbody>
                    <!-- ข้อมูลนักเรียนจะถูกแทรกที่นี่ -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8">
                            <div class="report-summary">
                                <p>สรุป</p>
                                <p>จำนวนคน............มา..............ขาด...............สาย...............ลา...............</p>
                                <p>สรุปจำนวนนักเรียนเข้าแถวร้อยละ................</p>
                            </div>
                            
                            <div class="report-footer">
                                <div class="signature-section">
                                    <div class="signature-box">
                                        <div class="signature-line">ลงชื่อ........................................</div>
                                        <div class="signature-name">({advisor_name})</div>
                                        <div class="signature-title">ครูที่ปรึกษา</div>
                                    </div>
                                    
                                    <div class="signature-box">
                                        <div class="signature-line">ลงชื่อ........................................</div>
                                        <div class="signature-name">({activity_head_name})</div>
                                        <div class="signature-title">หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
                                    </div>
                                    
                                    <div class="signature-box">
                                        <div class="signature-line">ลงชื่อ........................................</div>
                                        <div class="signature-name">({director_deputy_name})</div>
                                        <div class="signature-title">รองผู้อำนวยการ</div>
                                        <div class="signature-subtitle">ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay" style="display: none;">
    <div class="spinner-container">
        <div class="spinner"></div>
        <div class="loading-text">กำลังสร้างรายงาน...</div>
    </div>
</div>

<!-- ส่วน JavaScript สำหรับการโหลดข้อมูลและสร้างรายงาน -->
<script>
// ข้อมูลปีการศึกษาปัจจุบัน
const academicYear = <?php echo json_encode($academic_year); ?>;

// ข้อมูลวันหยุด
const holidays = <?php echo json_encode($holidays); ?>;

// ข้อมูลการตั้งค่ารายงาน
const reportSettings = <?php echo json_encode($report_settings); ?>;

// นำเข้าฟังก์ชันการสร้างรายงานจากไฟล์ print_activity.js
document.addEventListener('DOMContentLoaded', function() {
    // อีเวนต์จะถูกจัดการในไฟล์ print_activity.js
});
</script>
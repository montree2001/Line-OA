<?php
/**
 * print_attendance_chart.php - สร้างไฟล์ PDF กราฟการเข้าแถว
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

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['class_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

// นำเข้าไฟล์ MPDF
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

// ดึงข้อมูลที่ส่งมา
$class_id = $_POST['class_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$week_number = $_POST['week_number'] ?? '1';
$report_type = $_POST['report_type'] ?? 'chart';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องเรียน
$query = "SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name 
          FROM classes c 
          JOIN departments d ON c.department_id = d.department_id 
          WHERE c.class_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลแผนกวิชา
$department = [
    'department_id' => $class['department_id'],
    'department_name' => $class['department_name']
];

// ดึงข้อมูลนักเรียนในห้อง
$query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
          CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
          FROM students s 
          JOIN users u ON s.user_id = u.user_id 
          WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
          ORDER BY s.student_code";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($students);

// สร้างข้อมูลวันที่สำหรับรายงาน (เฉพาะวันจันทร์-ศุกร์)
$current_date = new DateTime($start_date);
$end_report_date = new DateTime($end_date);

// ดึงข้อมูลวันหยุด
$query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->execute([$start_date, $end_date]);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดรูปแบบวันหยุดเป็น [date => name]
$holiday_map = [];
foreach ($holidays as $holiday) {
    $holiday_map[$holiday['holiday_date']] = $holiday['holiday_name'];
}

// สร้างอาเรย์วันที่
$week_days = [];
$thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
$thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

while ($current_date <= $end_report_date) {
    $day_of_week = (int)$current_date->format('w'); // 0 = อาทิตย์, 6 = เสาร์
    
    // เฉพาะวันจันทร์ถึงศุกร์
    if ($day_of_week >= 1 && $day_of_week <= 5) {
        $date_str = $current_date->format('Y-m-d');
        $is_holiday = isset($holiday_map[$date_str]);
        
        $week_days[] = [
            'date' => $date_str,
            'day_name' => $thaiDayAbbrs[$day_of_week],
            'day_full' => $thaiDays[$day_of_week],
            'day_num' => $current_date->format('j'),
            'is_holiday' => $is_holiday,
            'holiday_name' => $is_holiday ? $holiday_map[$date_str] : null
        ];
    }
    
    $current_date->modify('+1 day');
}

// ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียนในช่วงวันที่
$student_ids = array_column($students, 'student_id');
if (!empty($student_ids)) {
    $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
    
    $query = "SELECT student_id, date, attendance_status FROM attendance 
              WHERE student_id IN ({$placeholders}) 
              AND academic_year_id = ? 
              AND date BETWEEN ? AND ?";
    
    $query_params = array_merge($student_ids, [$academic_year['academic_year_id'], $start_date, $end_date]);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($query_params);
    $attendance_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูลเป็น [student_id][date] => status
    $attendance_data = [];
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
} else {
    $attendance_data = [];
}

// ดึงข้อมูลครูที่ปรึกษา
$query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, t.phone 
          FROM teachers t 
          JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
          WHERE ca.class_id = ? AND ca.is_primary = 1
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$class_id]);
$primary_advisor = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลผู้ลงนามจากตาราง report_signers
$query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY signer_id LIMIT 3";
$stmt = $conn->query($query);
$signers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับจำนวนนักเรียนชาย/หญิง
$male_count = 0;
$female_count = 0;
foreach ($students as $student) {
    if ($student['title'] == 'นาย') {
        $male_count++;
    } else {
        $female_count++;
    }
}

// กำหนดค่า config สำหรับ mPDF
$mpdf_config = [
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font_size' => 16,
    'default_font' => 'thsarabun',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => __DIR__ . '/../tmp'
];

// สร้าง mPDF
$mpdf = new \Mpdf\Mpdf($mpdf_config);
$mpdf->useAdobeCJK = true;
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

// อัปโหลดแบบอักษร Sarabun หรือแบบอักษรไทยอื่น ๆ
$mpdf->SetFont('thsarabun');

// คำนวณสถิติการเข้าแถวรายวัน
$dailyStats = [];
$totalPresent = 0;
$totalAbsent = 0;
$totalLate = 0;
$totalLeave = 0;

foreach ($week_days as $day) {
    $dayStats = [
        'date' => $day['date'],
        'day_name' => $day['day_name'],
        'is_holiday' => $day['is_holiday'],
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'leave' => 0,
        'attendance_rate' => 0
    ];
    
    if (!$day['is_holiday']) {
        $totalStudentsForDay = count($students);
        $presentCount = 0;
        
        foreach ($students as $student) {
            if (isset($attendance_data[$student['student_id']][$day['date']])) {
                $status = $attendance_data[$student['student_id']][$day['date']];
                if ($status == 'present') {
                    $dayStats['present']++;
                    $totalPresent++;
                    $presentCount++;
                } elseif ($status == 'absent') {
                    $dayStats['absent']++;
                    $totalAbsent++;
                } elseif ($status == 'late') {
                    $dayStats['late']++;
                    $totalLate++;
                    $presentCount++; // นับสายเป็นมาเรียน
                } elseif ($status == 'leave') {
                    $dayStats['leave']++;
                    $totalLeave++;
                }
            } else {
                $dayStats['absent']++;
                $totalAbsent++;
            }
        }
        
        // คำนวณอัตราการเข้าแถว
        if ($totalStudentsForDay > 0) {
            $dayStats['attendance_rate'] = ($presentCount / $totalStudentsForDay) * 100;
        }
    }
    
    $dailyStats[] = $dayStats;
}

// คำนวณอัตราการเข้าแถวรวม
$totalAttendanceRate = 0;
$totalDays = count(array_filter($week_days, function($day) {
    return !$day['is_holiday'];
}));

if ($totalDays > 0 && $total_count > 0) {
    $totalPossibleAttendance = $totalDays * $total_count;
    $totalAttendances = $totalPresent + $totalLate;
    $totalAttendanceRate = ($totalAttendances / $totalPossibleAttendance) * 100;
}

// สร้างเนื้อหา PDF ตามแบบฟอร์ม
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>กราฟการเข้าแถว</title>
    <style>
        body {
            font-family: 'thsarabun';
            font-size: 16pt;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-logo {
            float: left;
            width: 80px;
            height: 80px;
            text-align: center;
            margin-right: 20px;
            padding-top: 10px;
        }
        .clear {
            clear: both;
        }
        .chart-container {
            width: 100%;
            height: 400px;
            border: 1px solid #ddd;
            margin: 20px 0;
            padding: 10px;
        }
        .chart-placeholder {
            width: 100%;
            height: 100%;
            background-color: #f8f9fa;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            text-align: center;
            font-size: 16pt;
            color: #666;
            flex-direction: column;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            float: left;
            width: 33%;
            text-align: center;
        }
        .signature-line {
            width: 80%;
            height: 1px;
            background-color: #000;
            margin: 50px auto 5px;
        }
        .page-footer {
            margin-top: 30px;
            font-size: 14pt;
        }
        .left {
            float: left;
        }
        .right {
            float: right;
        }
        .summary-section {
            margin: 20px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .summary-table th {
            background-color: #f2f2f2;
        }
        .status-container {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
        }
        .status-box {
            text-align: center;
            width: 22%;
        }
        .status-value {
            font-size: 24pt;
            font-weight: bold;
        }
        .status-label {
            font-size: 14pt;
            color: #666;
        }
        .colored-box {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 5px;
            vertical-align: middle;
        }
        .good {
            background-color: #4caf50;
        }
        .warning {
            background-color: #ff9800;
        }
        .danger {
            background-color: #f44336;
        }
        .info {
            background-color: #2196f3;
        }
        .chart-bar {
            display: inline-block;
            margin: 0 10px;
            text-align: center;
        }
        .bar {
            width: 40px;
            display: inline-block;
            background-color: #4caf50;
            margin-bottom: 5px;
        }
        .bar-label {
            font-size: 12pt;
            text-align: center;
        }
        .pie-chart {
            width: 100%;
            text-align: center;
            margin: 20px 0;
        }
        .pie-legend {
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }
        .legend-item {
            display: inline-block;
            margin: 0 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <img src="../uploads/logos/school_logo_1747545769.png" alt="Logo" style="width: 100%; height: auto;">
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงอัตราการเข้าแถวรายวัน</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?> เดือน <?php echo date('F', strtotime($week_days[0]['date'])); ?> พ.ศ. <?php echo date('Y', strtotime($week_days[0]['date'])) + 543; ?><br>
            ระหว่างวันที่ <?php echo date('j', strtotime($start_date)); ?> เดือน <?php echo date('F', strtotime($start_date)); ?> พ.ศ. <?php echo date('Y', strtotime($start_date)) + 543; ?> ถึง วันที่ <?php echo date('j', strtotime($end_date)); ?> เดือน <?php echo date('F', strtotime($end_date)); ?> พ.ศ. <?php echo date('Y', strtotime($end_date)) + 543; ?><br>
            ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <div class="status-container">
        <div class="status-box">
            <div class="status-value"><?php echo $total_count; ?></div>
            <div class="status-label">จำนวนนักเรียน</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo number_format($totalAttendanceRate, 1); ?>%</div>
            <div class="status-label">อัตราการเข้าแถวเฉลี่ย</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo $totalDays; ?></div>
            <div class="status-label">จำนวนวันเรียน</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo $totalPresent + $totalLate; ?>/<?php echo $totalDays * $total_count; ?></div>
            <div class="status-label">จำนวนครั้งเข้าแถว</div>
        </div>
    </div>
    
    <!-- กราฟแสดงอัตราการเข้าแถวรายวัน -->
    <div class="chart-container">
        <div class="chart-placeholder">
            <div style="width: 100%; text-align: center; margin-bottom: 10px;">
                <strong>อัตราการเข้าแถวรายวัน (ร้อยละ)</strong>
            </div>
            
            <div style="display: flex; justify-content: space-around; align-items: flex-end; width: 90%; height: 300px;">
                <?php foreach ($dailyStats as $index => $day): ?>
                    <?php if (!$day['is_holiday']): ?>
                        <?php
                        $height = isset($day['attendance_rate']) ? round($day['attendance_rate']) : 0;
                        $barHeight = ($height / 100) * 250; // Scale to max height of 250px
                        
                        // Set color based on attendance rate
                        if ($height >= 90) {
                            $color = '#4caf50'; // Green
                        } elseif ($height >= 80) {
                            $color = '#ff9800'; // Orange
                        } else {
                            $color = '#f44336'; // Red
                        }
                        ?>
                        <div class="chart-bar">
                            <div class="bar" style="height: <?php echo $barHeight; ?>px; background-color: <?php echo $color; ?>;"></div>
                            <div class="bar-label">
                                <?php echo $day['day_name']; ?><br>
                                <?php echo $day['day_num']; ?><br>
                                <?php echo number_format($day['attendance_rate'], 1); ?>%
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="chart-bar">
                            <div class="bar" style="height: 0px;"></div>
                            <div class="bar-label">
                                <?php echo $day['day_name']; ?><br>
                                <?php echo $day['day_num']; ?><br>
                                <span style="color: #f44336;">หยุด</span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- ตารางแสดงข้อมูลรายวัน -->
    <div class="summary-section">
        <h3>ข้อมูลการเข้าแถวรายวัน</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>วัน</th>
                    <th>มา</th>
                    <th>ขาด</th>
                    <th>สาย</th>
                    <th>ลา</th>
                    <th>อัตราการเข้าแถว (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyStats as $dayStat): ?>
                <tr>
                    <td>
                        <?php echo $dayStat['day_name']; ?> 
                        <?php echo date('d/m/Y', strtotime($dayStat['date'])); ?>
                        <?php if ($dayStat['is_holiday']): ?>
                            <span style="color: red;">(หยุด)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['present']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['absent']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['late']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['leave']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : number_format($dayStat['attendance_rate'], 1) . '%'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>รวม</th>
                    <th><?php echo $totalPresent; ?></th>
                    <th><?php echo $totalAbsent; ?></th>
                    <th><?php echo $totalLate; ?></th>
                    <th><?php echo $totalLeave; ?></th>
                    <th><?php echo number_format($totalAttendanceRate, 1); ?>%</th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- แผนภูมิแสดงสัดส่วนสถานะการเข้าแถว -->
    <div class="summary-section">
        <h3>สัดส่วนสถานะการเข้าแถว</h3>
        
        <!-- คำนวณสัดส่วน -->
        <?php
        $total = $totalPresent + $totalAbsent + $totalLate + $totalLeave;
        $presentPercent = ($total > 0) ? ($totalPresent / $total) * 100 : 0;
        $absentPercent = ($total > 0) ? ($totalAbsent / $total) * 100 : 0;
        $latePercent = ($total > 0) ? ($totalLate / $total) * 100 : 0;
        $leavePercent = ($total > 0) ? ($totalLeave / $total) * 100 : 0;
        ?>
        
        <div class="pie-legend">
            <div class="legend-item">
                <span class="colored-box good"></span> มาปกติ: <?php echo number_format($presentPercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box danger"></span> ขาด: <?php echo number_format($absentPercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box warning"></span> มาสาย: <?php echo number_format($latePercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box info"></span> ลา: <?php echo number_format($leavePercent, 1); ?>%
            </div>
        </div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <?php if ($primary_advisor): ?>
            <div>(<?php echo $primary_advisor['title'] . $primary_advisor['first_name'] . ' ' . $primary_advisor['last_name']; ?>)</div>
            <?php else: ?>
            <div>(.......................................)</div>
            <?php endif; ?>
            <div>ครูที่ปรึกษา</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <?php if (isset($signers[0])): ?>
            <div>(<?php echo $signers[0]['title'] . $signers[0]['first_name'] . ' ' . $signers[0]['last_name']; ?>)</div>
            <div><?php echo $signers[0]['position']; ?></div>
            <?php else: ?>
            <div>(นายมนตรี ศรีสุข)</div>
            <div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
            <?php endif; ?>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <?php if (isset($signers[1])): ?>
            <div>(<?php echo $signers[1]['title'] . $signers[1]['first_name'] . ' ' . $signers[1]['last_name']; ?>)</div>
            <div><?php echo $signers[1]['position']; ?></div>
            <?php else: ?>
            <div>(นายพงษ์ศักดิ์ สนโศรก)</div>
            <div>รองผู้อำนวยการ</div>
            <div>ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="clear"></div>
    
    <div class="page-footer">
        <div class="left">หน้าที่ 1</div>
        <div class="right">พิมพ์เมื่อวันที่ <?php echo date('j/n/Y'); ?></div>
        <div class="clear"></div>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();

// เพิ่มเนื้อหาลงใน mPDF
$mpdf->WriteHTML($content);

// กำหนดชื่อไฟล์
$filename = "กราฟการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}.pdf";

// Output PDF
$mpdf->Output($filename, 'I');
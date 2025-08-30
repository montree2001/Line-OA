<?php
/**
 * print_attendance_chart_pdf.php - สร้างไฟล์ PDF กราฟการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบการล็อกอิน
/* if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
} */

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ((!isset($_REQUEST['class_id']) && !isset($_REQUEST['search'])) || !isset($_REQUEST['start_date']) || !isset($_REQUEST['end_date'])) {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

// นำเข้าไฟล์ MPDF
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

// ดึงข้อมูลที่ส่งมา
$class_id = $_REQUEST['class_id'] ?? '';
$start_date = $_REQUEST['start_date'] ?? '';
$end_date = $_REQUEST['end_date'] ?? '';
$week_number = $_REQUEST['week_number'] ?? '1';
$end_week = $_REQUEST['end_week'] ?? $week_number;
$report_type = $_REQUEST['report_type'] ?? 'chart';
$search = $_REQUEST['search'] ?? '';
$search_type = $_REQUEST['search_type'] ?? 'class';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// ใช้ไฟล์วิเคราะห์ข้อมูลเดียวกัน
include 'includes/attendance_analysis.php';

// ตรวจสอบว่ามีข้อมูลหรือไม่
if (!$chart_data || empty($students)) {
    die('ไม่พบข้อมูลสำหรับสร้างรายงาน');
}

// ตั้งค่าข้อมูลพื้นฐานจาก analysis
$total_count = count($students);
$totalPresent = $chart_data['totalPresent'];
$totalLate = $chart_data['totalLate'];
$totalAbsent = $chart_data['totalAbsent'];
$totalLeave = $chart_data['totalLeave'] ?? 0;
$totalAttendanceRate = $chart_data['attendanceRate'];

// ข้อมูลห้องเรียน
if ($search_type === 'class' && $class_info) {
    $class = $class_info;
    $department = ['department_name' => $class_info['department_name']];
} else {
    // สำหรับการค้นหาตามนักเรียน
    $class = ['level' => '', 'group_number' => ''];
    $department = ['department_name' => ''];
}

// คำนวณข้อมูลสำหรับ Pie Chart
$totalAttendanceCount = $totalPresent + $totalLate;
$totalAbsenceCount = $totalAbsent + $totalLeave;
$pieTotal = $totalAttendanceCount + $totalAbsenceCount;

// คำนวณมุมสำหรับ Pie Chart (360 องศา)
$attendanceAngle = $pieTotal > 0 ? ($totalAttendanceCount / $pieTotal) * 360 : 0;
$absenceAngle = $pieTotal > 0 ? ($totalAbsenceCount / $pieTotal) * 360 : 0;

// คำนวณพิกัดสำหรับ SVG path
function getPieSlicePath($centerX, $centerY, $radius, $startAngle, $endAngle) {
    $startAngleRad = deg2rad($startAngle);
    $endAngleRad = deg2rad($endAngle);
    
    $x1 = $centerX + $radius * cos($startAngleRad);
    $y1 = $centerY + $radius * sin($startAngleRad);
    $x2 = $centerX + $radius * cos($endAngleRad);
    $y2 = $centerY + $radius * sin($endAngleRad);
    
    $largeArcFlag = ($endAngle - $startAngle) > 180 ? 1 : 0;
    
    return "M $centerX,$centerY L $x1,$y1 A $radius,$radius 0 $largeArcFlag,1 $x2,$y2 Z";
}

// นับเพศ
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
    'default_font_size' => 14,
    'default_font' => 'thsarabunnew',
    'margin_left' => 20,
    'margin_right' => 20,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => '../tmp',
    'fontDir' => [
        '../fonts/',
        '../fonts/thsarabunnew/'
    ],
    'fontdata' => [
        'thsarabunnew' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew-Bold.ttf',
            'I' => 'THSarabunNew-Italic.ttf',
            'BI' => 'THSarabunNew-BoldItalic.ttf',
        ]
    ]
];

// สร้าง mPDF
$mpdf = new \Mpdf\Mpdf($mpdf_config);
$mpdf->SetFont('thsarabunnew');

// สร้างเนื้อหา PDF
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>กราฟการเข้าแถว</title>
    <style>
        body {
            font-family: "thsarabunnew", sans-serif;
            font-size: 14pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .school-logo {
            float: left;
            width: 60px;
            height: 60px;
            text-align: center;
            margin-right: 15px;
            padding-top: 5px;
        }
        .clear {
            clear: both;
        }
        .chart-container {
            width: 100%;
            min-height: 200px;
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            page-break-inside: avoid;
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
        .page-footer {
            margin-top: 30px;
            font-size: 14pt;
        }
        .summary-section {
            margin: 20px 0;
        }
        .summary-section h3 {
            font-size: 14pt;
            margin-bottom: 10px;
            color: #1976d2;
        }
        .status-container {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 10px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            border-radius: 8px;
        }
        .status-box {
            text-align: center;
            width: 22%;
            padding: 5px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-value {
            font-size: 18pt;
            font-weight: bold;
            color: #2196f3;
        }
        .status-label {
            font-size: 12pt;
            color: #666;
            margin-top: 2px;
        }
        .pie-chart {
            width: 400px;
            height: 400px;
            margin: 20px auto;
            position: relative;
        }
        .pie-svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .pie-slice {
            stroke: #fff;
            stroke-width: 2;
        }
        .pie-slice.present {
            fill: #4caf50;
        }
        .pie-slice.absent {
            fill: #f44336;
        }
        .pie-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #333;
        }
        .pie-legends {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 30px;
        }
        .pie-legend {
            display: flex;
            align-items: center;
            font-size: 12pt;
        }
        .legend-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .chart-stats {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        .stat-number {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12pt;
            color: #666;
        }
        .stat-present .stat-number { color: #4caf50; }
        .stat-absent .stat-number { color: #f44336; }
        .stat-late .stat-number { color: #ff9800; }
        .stat-total .stat-number { color: #2196f3; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <?php if (file_exists('../uploads/logos/school_logo.png')): ?>
                <img src="../uploads/logos/school_logo.png" alt="Logo" style="width: 100%; height: auto;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10pt;">โลโก้</div>
            <?php endif; ?>
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงสัดส่วนการเข้าแถว</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?><br>
            <?php echo "ระหว่างวันที่ " . date('j/n/Y', strtotime($start_date)) . " - " . date('j/n/Y', strtotime($end_date)); ?>
            <br>
            <?php if ($search_type === 'student' && !empty($search)): ?>
                ค้นหา: <?php echo htmlspecialchars($search); ?>
            <?php else: ?>
                ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <!-- สถิติการเข้าแถว -->
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
            <div class="status-value"><?php echo $chart_data['totalDays']; ?></div>
            <div class="status-label">จำนวนวันเรียน</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo $totalPresent + $totalLate; ?>/<?php echo $chart_data['totalDays'] * $total_count; ?></div>
            <div class="status-label">จำนวนครั้งเข้าแถว</div>
        </div>
    </div>
    
    <!-- กราฟวงกลมแสดงสัดส่วนการเข้าแถว -->
    <div class="chart-container">
        <h3 style="text-align: center; margin-top: 0; color: #1976d2; font-size: 16pt; background: linear-gradient(135deg, #f0f8ff 0%, #e1f5fe 100%); padding: 8px; border-radius: 5px;">📊 สัดส่วนการเข้าแถวและขาดแถว</h3>
        
        <div class="pie-chart">
            <?php if ($pieTotal > 0): ?>
                <svg class="pie-svg" viewBox="0 0 200 200">
                    <?php
                    $centerX = 100;
                    $centerY = 100;
                    $radius = 80;
                    
                    // วาดส่วนของการเข้าแถว (เขียว)
                    if ($attendanceAngle > 0) {
                        $attendancePath = getPieSlicePath($centerX, $centerY, $radius, 0, $attendanceAngle);
                        echo '<path class="pie-slice present" d="' . $attendancePath . '"></path>';
                    }
                    
                    // วาดส่วนของการขาดแถว (แดง)
                    if ($absenceAngle > 0) {
                        $absencePath = getPieSlicePath($centerX, $centerY, $radius, $attendanceAngle, 360);
                        echo '<path class="pie-slice absent" d="' . $absencePath . '"></path>';
                    }
                    ?>
                </svg>
                
                <div class="pie-center">
                    <div><?php echo number_format($totalAttendanceRate, 1); ?>%</div>
                    <div style="font-size: 12pt; font-weight: normal;">อัตราการเข้าแถว</div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #666;">
                    ไม่มีข้อมูลการเข้าแถว
                </div>
            <?php endif; ?>
        </div>
        
        <!-- คำอธิบายและสถิติ -->
        <div class="pie-legends">
            <div class="pie-legend">
                <div class="legend-dot" style="background-color: #4caf50;"></div>
                <span>เข้าแถว: <?php echo $pieTotal > 0 ? number_format(($totalAttendanceCount / $pieTotal) * 100, 1) : '0'; ?>% (<?php echo $totalAttendanceCount; ?> ครั้ง)</span>
            </div>
            <div class="pie-legend">
                <div class="legend-dot" style="background-color: #f44336;"></div>
                <span>ขาดแถว: <?php echo $pieTotal > 0 ? number_format(($totalAbsenceCount / $pieTotal) * 100, 1) : '0'; ?>% (<?php echo $totalAbsenceCount; ?> ครั้ง)</span>
            </div>
        </div>
        
        <!-- สถิติรายละเอียด -->
        <div class="chart-stats">
            <div class="stat-item stat-present">
                <div class="stat-number"><?php echo $totalPresent; ?></div>
                <div class="stat-label">มาแถว</div>
            </div>
            <div class="stat-item stat-late">
                <div class="stat-number"><?php echo $totalLate; ?></div>
                <div class="stat-label">สาย</div>
            </div>
            <div class="stat-item stat-absent">
                <div class="stat-number"><?php echo $totalAbsent; ?></div>
                <div class="stat-label">ขาด</div>
            </div>
            <div class="stat-item stat-total">
                <div class="stat-number"><?php echo $pieTotal; ?></div>
                <div class="stat-label">รวมทั้งหมด</div>
            </div>
        </div>
    </div>
    
    <!-- สรุปผล -->
    <div class="summary-section">
        <h3>สรุปผลการวิเคราะห์</h3>
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 48%;">
                <p><strong>จำนวนนักเรียนทั้งหมด:</strong> <?php echo $total_count; ?> คน (ชาย <?php echo $male_count; ?> คน, หญิง <?php echo $female_count; ?> คน)</p>
                <p><strong>ช่วงเวลาที่วิเคราะห์:</strong> <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                <p><strong>จำนวนวันเรียน:</strong> <?php echo $chart_data['totalDays']; ?> วัน</p>
            </div>
            <div style="width: 48%;">
                <p><strong>อัตราการเข้าแถวเฉลี่ย:</strong> <?php echo number_format($totalAttendanceRate, 1); ?>%</p>
                <p><strong>จำนวนครั้งที่เข้าแถว:</strong> <?php echo $totalAttendanceCount; ?> ครั้ง</p>
                <p><strong>จำนวนครั้งที่ขาดแถว:</strong> <?php echo $totalAbsenceCount; ?> ครั้ง</p>
            </div>
        </div>
    </div>
    
    <!-- ส่วนเซ็นชื่อ -->
    <div class="signature-section">
        <div class="signature-box">
            <div style="margin-bottom: 50px;"></div>
            <div>ลงชื่อ...........................................</div>
            <div>(.......................................)</div>
            <div>ครูที่ปรึกษา</div>
        </div>

        <div class="signature-box">
            <div style="margin-bottom: 50px;"></div>
            <div>ลงชื่อ...........................................</div>
            <div>(นายมนตรี ศรีสุข)</div>
            <div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
        </div>

        <div class="signature-box">
            <div style="margin-bottom: 50px;"></div>
            <div>ลงชื่อ...........................................</div>
            <div>(นายพงษ์ศักดิ์ สนโศรก)</div>
            <div>รองผู้อำนวยการ</div>
            <div>ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
        </div>
    </div>
    
    <div class="clear"></div>
    
    <div class="page-footer">
        <p>พิมพ์เมื่อวันที่ <?php echo date('j/n/Y'); ?></p>
    </div>
</body>
</html>

<?php
$html = ob_get_clean();

// ส่งออก PDF
$mpdf->WriteHTML($html);
$filename = 'attendance_chart_' . date('Y-m-d_H-i-s') . '.pdf';
$mpdf->Output($filename, 'I'); // 'I' = แสดงใน browser, 'D' = download
?>
<?php
/**
 * print_attendance_chart.php - สร้างไฟล์ PDF กราฟการเข้าแถว (แก้ไขแล้ว)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
date_default_timezone_set('Asia/Bangkok');
/* แสดง Error */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/* 
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
} */

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] != 'POST' || (!isset($_POST['class_id']) && !isset($_POST['search'])) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

// นำเข้าไฟล์ MPDF
require_once '../vendor/autoload.php';
require_once '../db_connect.php';

// ดึงข้อมูลที่ส่งมา
$class_id = $_POST['class_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$week_number = $_POST['week_number'] ?? '1';
$end_week = $_POST['end_week'] ?? $week_number;
$report_type = $_POST['report_type'] ?? 'chart';
$search = $_POST['search'] ?? '';
$search_type = $_POST['search_type'] ?? 'class';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
$stmt = $conn->query($query);
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// เตรียม query สำหรับดึงนักเรียนและข้อมูลห้องเรียน
if ($search_type === 'student' && !empty($search)) {
    // ค้นหาตามชื่อนักเรียน
    $query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                      CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title,
                      c.class_id, c.level, c.group_number, d.department_name
                      FROM students s 
                      JOIN users u ON s.user_id = u.user_id 
                      LEFT JOIN classes c ON s.current_class_id = c.class_id
                      LEFT JOIN departments d ON c.department_id = d.department_id
                      WHERE s.status = 'กำลังศึกษา'
                      AND (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
                      ORDER BY s.student_code";
    
    $search_param = "%$search%";
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$search_param, $search_param, $search_param]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ใช้ข้อมูลของนักเรียนคนแรกสำหรับข้อมูลห้องเรียน
    if (!empty($students)) {
        $first_student = $students[0];
        $class_id = $first_student['class_id'];
        $class = [
            'class_id' => $first_student['class_id'],
            'level' => $first_student['level'],
            'group_number' => $first_student['group_number'],
            'department_id' => 1, // กำหนดค่าเริ่มต้น
            'department_name' => $first_student['department_name']
        ];
        $department = [
            'department_name' => $first_student['department_name']
        ];
    } else {
        die('ไม่พบข้อมูลนักเรียนที่ค้นหา');
    }
} else {
    // ค้นหาตามห้องเรียน
    if (empty($class_id)) {
        die('กรุณาระบุห้องเรียน');
    }
    
    // ดึงข้อมูลห้องเรียน
    $query = "SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        die('ไม่พบข้อมูลห้องเรียน');
    }
    
    $department = ['department_name' => $class['department_name']];
    
    // ดึงข้อมูลนักเรียนในห้อง
    $query_students = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name,
                      CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as display_title  
                      FROM students s 
                      JOIN users u ON s.user_id = u.user_id 
                      WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา' 
                      ORDER BY s.student_code";
    $stmt = $conn->prepare($query_students);
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

// ดึงข้อมูลครูที่ปรึกษา - แก้ไข query ให้ถูกต้อง
$query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, u.phone_number as phone 
          FROM teachers t 
          JOIN users u ON t.user_id = u.user_id
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
    if ($student['display_title'] == 'นาย') {
        $male_count++;
    } else {
        $female_count++;
    }
}

// กำหนดค่า config สำหรับ mPDF พร้อมฟอนต์ THSarabunNew
$mpdf_config = [
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font_size' => 16,
    'default_font' => 'thsarabunnew',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10,
    'tempDir' => __DIR__ . '/../tmp',
    'fontDir' => [
        __DIR__ . '/../fonts/',
        __DIR__ . '/../fonts/thsarabunnew/'
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
        'day_num' => $day['day_num'],
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
            font-family: 'thsarabunnew';
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
            background-color: #f8f9fa;
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
        .bar-chart {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            margin-top: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ccc;
        }
        .bar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: <?php echo count($dailyStats) > 0 ? 100 / count($dailyStats) : 20; ?>%;
            max-width: 80px;
        }
        .bar {
            width: 40px;
            background-color: #4caf50;
            margin-bottom: 10px;
            border-radius: 5px 5px 0 0;
        }
        .bar-label {
            text-align: center;
            font-size: 12pt;
        }
        .bar-value {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <?php if (file_exists('../uploads/logos/school_logo.png')): ?>
                <img src="../uploads/logos/school_logo.png" alt="Logo" style="width: 100%; height: auto;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">โลโก้</div>
            <?php endif; ?>
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงอัตราการเข้าแถวรายวัน</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?><br>
            <?php 
            if (!empty($week_days)) {
                $first_day = $week_days[0];
                $last_day = $week_days[count($week_days)-1];
                echo "ระหว่างวันที่ " . $first_day['day_num'] . " - " . $last_day['day_num'] . " ";
                echo "พ.ศ. " . (date('Y', strtotime($start_date)) + 543);
            }
            ?>
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
        <h3 style="text-align: center; margin-top: 0;">กราฟแสดงอัตราการเข้าแถวรายวัน</h3>
        <div class="bar-chart">
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
                    <div class="bar-container">
                        <div class="bar-value"><?php echo number_format($day['attendance_rate'], 1); ?>%</div>
                        <div class="bar" style="height: <?php echo $barHeight; ?>px; background-color: <?php echo $color; ?>;"></div>
                        <div class="bar-label">
                            <?php echo $day['day_name']; ?> <?php echo $day['day_num']; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bar-container">
                        <div class="bar-value">หยุด</div>
                        <div class="bar" style="height: 0; background-color: #ccc;"></div>
                        <div class="bar-label">
                            <?php echo $day['day_name']; ?> <?php echo $day['day_num']; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
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
    
    <!-- ส่วนเซ็นชื่อ -->
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
if ($search_type === 'student' && !empty($search)) {
    $filename = "กราฟการเข้าแถว_ค้นหา_{$search}_สัปดาห์ที่{$week_number}.pdf";
} else {
    $filename = "กราฟการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}.pdf";
}

// Output PDF
$mpdf->Output($filename, 'I');
?>
<?php
/**
 * print_attendance_chart.php - สร้างไฟล์ PDF กราฟการเข้าแถว (1 สัปดาห์ต่อหน้า)
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
date_default_timezone_set('Asia/Bangkok');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

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
            'department_id' => 1,
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

// ดึงข้อมูลวันหยุด
$query = "SELECT holiday_date, holiday_name FROM holidays";
$stmt = $conn->query($query);
$holidays = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $holidays[$row['holiday_date']] = $row['holiday_name'];
}

// ดึงข้อมูลการเข้าแถวสำหรับทุกนักเรียน
$student_ids = array_column($students, 'student_id');
$attendance_data = [];

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
    foreach ($attendance_results as $result) {
        $attendance_data[$result['student_id']][$result['date']] = $result['attendance_status'];
    }
}

// ดึงข้อมูลครูที่ปรึกษา
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

// คำนวณสัปดาห์ที่กำลังดำเนินการ
$currentWeek = $week_number;
$endWeek = $end_week;

// สร้างและจัดกลุ่มตามสัปดาห์
$weeks = [];
$semesterStart = new DateTime($academic_year['start_date']);
$thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
$thaiDayAbbrs = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
$thaiMonths = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];

for ($w = $currentWeek; $w <= $endWeek; $w++) {
    // คำนวณวันเริ่มต้นของสัปดาห์
    $weekStart = clone $semesterStart;
    $weekStart->modify('+' . (($w - 1) * 7) . ' days');

    // ปรับให้เป็นวันจันทร์
    $dayOfWeek = $weekStart->format('N'); // 1 = จันทร์, 7 = อาทิตย์
    if ($dayOfWeek > 1) {
        $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
    }

    // สร้างข้อมูลวันจันทร์ถึงศุกร์ของสัปดาห์นี้ (เฉพาะ 5 วัน)
    $days = [];
    $currentDay = clone $weekStart;

    // สร้างเฉพาะ 5 วัน (จันทร์ถึงศุกร์)
    for ($i = 0; $i < 5; $i++) {
        $dateStr = $currentDay->format('Y-m-d');
        $dayNum = (int)$currentDay->format('N'); // 1 = จันทร์, 7 = อาทิตย์

        // ตรวจสอบว่าเป็นวันหยุดหรือไม่
        $isHoliday = isset($holidays[$dateStr]);
        $holidayName = $isHoliday ? $holidays[$dateStr] : null;

        $days[] = [
            'date' => $dateStr,
            'day_name' => $thaiDayAbbrs[$dayNum % 7],
            'day_full' => $thaiDays[$dayNum % 7],
            'day_num' => $currentDay->format('j'),
            'month' => $thaiMonths[$currentDay->format('m')],
            'year' => (int)$currentDay->format('Y') + 543, // พ.ศ.
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName
        ];

        $currentDay->modify('+1 day');
    }

    $weeks[] = [
        'week_number' => $w,
        'start_date' => $weekStart->format('Y-m-d'),
        'end_date' => $weekStart->modify('+4 days')->format('Y-m-d'),
        'days' => $days
    ];
}

// กำหนดค่า config สำหรับ mPDF
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

// สร้าง PDF สำหรับแต่ละสัปดาห์ (แต่ละสัปดาห์หน้าใหม่)
foreach ($weeks as $weekIndex => $week) {
    // สำหรับแต่ละสัปดาห์จะสร้างหน้า PDF ใหม่
    if ($weekIndex > 0) {
        $mpdf->AddPage();
    }

    // คำนวณสถิติการเข้าแถวสำหรับสัปดาห์นี้
    $weeklyStats = [];
    $weekTotalPresent = 0;
    $weekTotalAbsent = 0;
    $weekTotalLate = 0;
    $weekTotalLeave = 0;

    foreach ($week['days'] as $day) {
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
            $presentCount = 0;
            
            foreach ($students as $student) {
                if (isset($attendance_data[$student['student_id']][$day['date']])) {
                    $status = $attendance_data[$student['student_id']][$day['date']];
                    if ($status == 'present') {
                        $dayStats['present']++;
                        $weekTotalPresent++;
                        $presentCount++;
                    } elseif ($status == 'absent') {
                        $dayStats['absent']++;
                        $weekTotalAbsent++;
                    } elseif ($status == 'late') {
                        $dayStats['late']++;
                        $weekTotalLate++;
                        $presentCount++; // นับสายเป็นมาเรียน
                    } elseif ($status == 'leave') {
                        $dayStats['leave']++;
                        $weekTotalLeave++;
                    }
                } else {
                    $dayStats['absent']++;
                    $weekTotalAbsent++;
                }
            }
            
            // คำนวณอัตราการเข้าแถว
            if ($total_count > 0) {
                $dayStats['attendance_rate'] = ($presentCount / $total_count) * 100;
            }
        }
        
        $weeklyStats[] = $dayStats;
    }

    // คำนวณอัตราการเข้าแถวรวมของสัปดาห์
    $weekTotalDays = count(array_filter($week['days'], function($day) {
        return !$day['is_holiday'];
    }));
    
    $weekAttendanceRate = 0;
    if ($weekTotalDays > 0 && $total_count > 0) {
        $weekTotalPossibleAttendance = $weekTotalDays * $total_count;
        $weekTotalAttendances = $weekTotalPresent + $weekTotalLate;
        $weekAttendanceRate = ($weekTotalAttendances / $weekTotalPossibleAttendance) * 100;
    }

    // กำหนดตัวแปรที่จะส่งไปยังเทมเพลต
    $current_week_number = $week['week_number'];
    $week_start_date = $week['start_date'];
    $week_end_date = $week['end_date'];
    $week_days = $week['days'];
    $dailyStats = $weeklyStats;
    $totalPresent = $weekTotalPresent;
    $totalAbsent = $weekTotalAbsent;
    $totalLate = $weekTotalLate;
    $totalLeave = $weekTotalLeave;
    $totalDays = $weekTotalDays;
    $totalAttendanceRate = $weekAttendanceRate;

    // สร้างเนื้อหา PDF สำหรับสัปดาห์นี้
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>กราฟการเข้าแถว สัปดาห์ที่ <?php echo $current_week_number; ?></title>
    <style>
        body {
            font-family: 'thsarabunnew';
            font-size: 16pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            page-break-inside: avoid;
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
        
        /* กราฟสำหรับ 1 สัปดาห์ */
        .chart-container {
            width: 100%;
            margin: 30px 0;
            padding: 25px;
            border: 3px solid #2c5282;
            border-radius: 15px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            page-break-inside: avoid;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 25px;
            border-bottom: 3px solid #2c5282;
            padding-bottom: 15px;
        }
        
        .week-info {
            text-align: center;
            font-size: 16pt;
            color: #4a5568;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .bar-chart {
            width: 100%;
            height: 400px;
            margin: 30px auto;
            position: relative;
            background-color: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-evenly;
            height: 100%;
            padding: 0 20px;
        }
        
        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            width: 18%; /* 5 วัน = 100%/5 = 20% แต่ลบ margin */
            margin: 0 1%;
        }
        
        .bar-value {
            font-size: 14pt;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 8px;
            text-align: center;
            min-height: 20px;
            background-color: rgba(255,255,255,0.9);
            padding: 4px 8px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .bar {
            width: 100%;
            max-width: 80px;
            border-radius: 8px 8px 0 0;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .bar.excellent {
            background: linear-gradient(to top, #22c55e 0%, #16a34a 50%, #15803d 100%);
        }
        
        .bar.good {
            background: linear-gradient(to top, #f59e0b 0%, #d97706 50%, #b45309 100%);
        }
        
        .bar.poor {
            background: linear-gradient(to top, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
        }
        
        .bar.holiday {
            background: linear-gradient(to top, #94a3b8 0%, #64748b 50%, #475569 100%);
        }
        
        .bar-label {
            margin-top: 12px;
            font-size: 13pt;
            text-align: center;
            color: #374151;
            font-weight: 600;
            line-height: 1.3;
            padding: 8px;
            background-color: rgba(255,255,255,0.8);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .holiday-label {
            color: #ef4444;
            font-weight: bold;
        }
        
        /* สถิติสัปดาห์ */
        .week-stats {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .stat-box {
            text-align: center;
            flex: 1;
            padding: 15px;
        }
        
        .stat-value {
            font-size: 24pt;
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .stat-label {
            font-size: 12pt;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Legend */
        .chart-legend {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin: 8px 20px;
            font-size: 12pt;
            font-weight: 500;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .legend-excellent { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .legend-good { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .legend-poor { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .legend-holiday { background: linear-gradient(135deg, #94a3b8, #64748b); }
        
        /* ตาราง */
        .summary-section {
            margin: 30px 0;
            page-break-inside: avoid;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .summary-table th {
            background: linear-gradient(135deg, #2c5282, #3182ce);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #2c5282;
            font-size: 14pt;
        }
        
        .summary-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 8px;
            text-align: center;
            background-color: #ffffff;
            font-size: 13pt;
        }
        
        .summary-table tfoot th {
            background: linear-gradient(135deg, #1a365d, #2c5282);
            color: white;
            font-weight: bold;
        }
        
        .summary-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        /* ส่วนลายเซ็น */
        .signature-section {
            margin-top: 50px;
            width: 100%;
            page-break-inside: avoid;
        }
        
        .signature-box {
            float: left;
            width: 33%;
            text-align: center;
            padding: 15px;
        }
        
        .signature-line {
            width: 80%;
            height: 1px;
            background-color: #000;
            margin: 50px auto 8px;
        }
        
        .page-footer {
            margin-top: 30px;
            font-size: 14pt;
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
        }
        
        .left {
            float: left;
        }
        
        .right {
            float: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <?php if (file_exists('../uploads/logos/school_logo.png')): ?>
                <img src="../uploads/logos/school_logo.png" alt="Logo" style="width: 100%; height: auto;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 12pt;">โลโก้</div>
            <?php endif; ?>
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงอัตราการเข้าแถวรายวัน</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?><br>
            <?php if ($search_type === 'student' && !empty($search)): ?>
                ค้นหา: <?php echo htmlspecialchars($search); ?>
            <?php else: ?>
                ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <!-- สถิติสัปดาห์ -->
    <div class="week-stats">
        <div class="stat-box">
            <span class="stat-value"><?php echo $total_count; ?></span>
            <div class="stat-label">จำนวนนักเรียน</div>
        </div>
        <div class="stat-box">
            <span class="stat-value"><?php echo number_format($totalAttendanceRate, 1); ?>%</span>
            <div class="stat-label">อัตราการเข้าแถวเฉลี่ย</div>
        </div>
        <div class="stat-box">
            <span class="stat-value"><?php echo $totalDays; ?></span>
            <div class="stat-label">จำนวนวันเรียน</div>
        </div>
        <div class="stat-box">
            <span class="stat-value"><?php echo $totalPresent + $totalLate; ?>/<?php echo $totalDays * $total_count; ?></span>
            <div class="stat-label">จำนวนครั้งเข้าแถว</div>
        </div>
    </div>
    
    <!-- กราฟแสดงอัตราการเข้าแถวรายวัน -->
    <div class="chart-container">
        <div class="chart-title">กราฟแสดงอัตราการเข้าแถว สัปดาห์ที่ <?php echo $current_week_number; ?></div>
        
        <div class="week-info">
            <?php 
            if (!empty($week_days)) {
                $first_day = $week_days[0];
                $last_day = $week_days[count($week_days)-1];
                echo "วันที่ " . $first_day['day_num'] . " - " . $last_day['day_num'] . " ";
                echo $first_day['month'] . " พ.ศ. " . $first_day['year'];
            }
            ?>
        </div>
        
        <!-- Legend -->
        <div class="chart-legend">
            <div class="legend-item">
                <div class="legend-color legend-excellent"></div>
                <span>ดีเยี่ยม (90% ขึ้นไป)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-good"></div>
                <span>ดี (80-89%)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-poor"></div>
                <span>ต้องปรับปรุง (ต่ำกว่า 80%)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-holiday"></div>
                <span>วันหยุด</span>
            </div>
        </div>
        
        <div class="bar-chart">
            <div class="chart-bars">
                <?php foreach ($dailyStats as $index => $day): ?>
                    <?php
                    $height = isset($day['attendance_rate']) ? round($day['attendance_rate']) : 0;
                    $barHeight = $day['is_holiday'] ? 40 : (($height / 100) * 300); // Scale to max height of 300px
                    
                    // Set class based on attendance rate
                    if ($day['is_holiday']) {
                        $barClass = 'holiday';
                        $displayValue = 'หยุด';
                    } elseif ($height >= 90) {
                        $barClass = 'excellent';
                        $displayValue = number_format($day['attendance_rate'], 1) . '%';
                    } elseif ($height >= 80) {
                        $barClass = 'good';
                        $displayValue = number_format($day['attendance_rate'], 1) . '%';
                    } else {
                        $barClass = 'poor';
                        $displayValue = number_format($day['attendance_rate'], 1) . '%';
                    }
                    ?>
                    <div class="bar-item">
                        <div class="bar-value"><?php echo $displayValue; ?></div>
                        <div class="bar <?php echo $barClass; ?>" style="height: <?php echo $barHeight; ?>px;"></div>
                        <div class="bar-label <?php echo $day['is_holiday'] ? 'holiday-label' : ''; ?>">
                            <?php echo $day['day_name']; ?><br>
                            <?php echo $day['day_num']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- ตารางแสดงข้อมูลรายวัน -->
    <div class="summary-section">
        <h3 style="color: #2c5282; border-bottom: 2px solid #2c5282; padding-bottom: 10px; text-align: center;">
            ข้อมูลการเข้าแถวรายวัน สัปดาห์ที่ <?php echo $current_week_number; ?>
        </h3>
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
                            <br><span style="color: red; font-size: 11pt;">(หยุด)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['present']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['absent']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['late']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['leave']; ?></td>
                    <td style="font-weight: bold;">
                        <?php echo $dayStat['is_holiday'] ? '-' : number_format($dayStat['attendance_rate'], 1) . '%'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>รวมสัปดาห์ที่ <?php echo $current_week_number; ?></th>
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
        <div class="left">สัปดาห์ที่ <?php echo $current_week_number; ?> หน้าที่ <?php echo $weekIndex + 1; ?></div>
        <div class="right">พิมพ์เมื่อวันที่ <?php echo date('j/n/Y'); ?></div>
        <div class="clear"></div>
    </div>
</body>
</html>
    <?php
    $content = ob_get_clean();

    // เพิ่มเนื้อหาลงใน mPDF
    $mpdf->WriteHTML($content);
}

// กำหนดชื่อไฟล์
if ($search_type === 'student' && !empty($search)) {
    $filename = "กราฟการเข้าแถว_ค้นหา_{$search}_สัปดาห์ที่{$week_number}-{$end_week}.pdf";
} else {
    $filename = "กราฟการเข้าแถว_{$class['level']}_{$class['group_number']}_{$department['department_name']}_สัปดาห์ที่{$week_number}-{$end_week}.pdf";
}

// Output PDF
$mpdf->Output($filename, 'I');
?>
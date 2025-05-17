<?php
/**
 * print_activity_report.php - หน้าพิมพ์รายงานผลกิจกรรมเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => isset($_SESSION['user_name']) ? mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8') : 'A',
];

// ดึงข้อมูลปีการศึกษาปัจจุบัน
function getActiveAcademicYear() {
    $db = getDB();
    $query = "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $db->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลแผนกวิชาทั้งหมด
function getDepartments() {
    $db = getDB();
    $query = "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลชั้นเรียนตามแผนกวิชาและปีการศึกษา
function getClassesByDepartment($department_id, $academic_year_id) {
    $db = getDB();
    $query = "SELECT * FROM classes 
              WHERE department_id = ? AND academic_year_id = ? AND is_active = 1 
              ORDER BY level, group_number";
    $stmt = $db->prepare($query);
    $stmt->execute([$department_id, $academic_year_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลนักเรียนในชั้นเรียน
function getStudentsByClass($class_id) {
    $db = getDB();
    $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
              ORDER BY s.student_code";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลครูที่ปรึกษาในชั้นเรียน
function getAdvisorsByClass($class_id) {
    $db = getDB();
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, ca.is_primary
              FROM class_advisors ca
              JOIN teachers t ON ca.teacher_id = t.teacher_id
              WHERE ca.class_id = ?
              ORDER BY ca.is_primary DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลการเข้าแถวตามชั้นเรียนและช่วงวันที่
function getAttendanceByClassAndDateRange($class_id, $start_date, $end_date, $academic_year_id) {
    $db = getDB();
    
    // ดึงข้อมูลนักเรียนในชั้นเรียน
    $students = getStudentsByClass($class_id);
    
    // ดึงข้อมูลการเข้าแถวของนักเรียนแต่ละคน
    foreach ($students as &$student) {
        $query = "SELECT date, attendance_status, remarks
                  FROM attendance
                  WHERE student_id = ? AND date BETWEEN ? AND ? AND academic_year_id = ?
                  ORDER BY date";
        $stmt = $db->prepare($query);
        $stmt->execute([$student['student_id'], $start_date, $end_date, $academic_year_id]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดข้อมูลการเข้าแถวตามวัน
        $student['attendances'] = [];
        foreach ($attendances as $attendance) {
            $student['attendances'][$attendance['date']] = [
                'status' => $attendance['attendance_status'],
                'remarks' => $attendance['remarks']
            ];
        }
    }
    
    return $students;
}

// ดึงข้อมูลสัปดาห์ทั้งหมดในภาคเรียน
function getAllWeeks($academic_year) {
    $start_date = new DateTime($academic_year['start_date']);
    $end_date = new DateTime($academic_year['end_date']);
    
    // ตรวจสอบว่าเป็นระดับ ปวช. หรือ ปวส.
    $level_info = isset($academic_year['description']) ? $academic_year['description'] : '';
    $is_high_level = strpos($level_info, 'ปวส.') !== false;
    $max_weeks = $is_high_level ? 15 : 18; // ปวส. 15 สัปดาห์, ปวช. 18 สัปดาห์
    
    $interval = $start_date->diff($end_date);
    $total_days = $interval->days;
    $total_weeks = min(ceil($total_days / 7), $max_weeks); // จำกัดจำนวนสัปดาห์
    
    $weeks = [];
    for ($i = 1; $i <= $total_weeks; $i++) {
        $week_start = clone $start_date;
        $week_start->modify('+' . (($i - 1) * 7) . ' days');
        
        $week_end = clone $week_start;
        $week_end->modify('+6 days');
        
        // ปรับให้อยู่ในช่วงของภาคเรียน
        if ($week_end > $end_date) {
            $week_end = clone $end_date;
        }
        
        // แปลงวันที่เป็นรูปแบบ d/m/Y ภาษาไทย
        $thai_start = thaiDate($week_start->format('Y-m-d'));
        $thai_end = thaiDate($week_end->format('Y-m-d'));
        
        $weeks[] = [
            'week_number' => $i,
            'start_date' => $week_start->format('Y-m-d'),
            'end_date' => $week_end->format('Y-m-d'),
            'start_date_display' => $thai_start,
            'end_date_display' => $thai_end
        ];
    }
    
    return $weeks;
}

// ฟังก์ชันแปลงวันที่เป็นรูปแบบไทย
function thaiDate($date) {
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    list($year, $month, $day) = explode('-', $date);
    $thai_year = (int)$year + 543;
    $thai_month = $thai_months[$month];
    
    return $day . ' ' . $thai_month . ' ' . $thai_year;
}

// ดึงข้อมูลวันหยุดนักขัตฤกษ์และวันหยุดเพิ่มเติม
function getHolidays($academic_year_id) {
    $db = getDB();
    $holidays = [];
    
    // ดึงวันหยุดจากฐานข้อมูล (ถ้ามี)
    try {
        $query = "SELECT holiday_date, holiday_name FROM holidays WHERE academic_year_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$academic_year_id]);
        $db_holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($db_holidays as $holiday) {
            $holidays[$holiday['holiday_date']] = $holiday['holiday_name'];
        }
    } catch (PDOException $e) {
        // ถ้าไม่มีตาราง holidays ให้ใช้ข้อมูลจำลอง
    }
    
    // ถ้าไม่มีข้อมูลวันหยุดในฐานข้อมูล ใช้วันหยุดนักขัตฤกษ์พื้นฐาน
    if (empty($holidays)) {
        $holidays = [
            '2025-01-01' => 'วันขึ้นปีใหม่',
            '2025-02-10' => 'วันมาฆบูชา',
            '2025-04-06' => 'วันจักรี',
            '2025-04-13' => 'วันสงกรานต์',
            '2025-04-14' => 'วันสงกรานต์',
            '2025-04-15' => 'วันสงกรานต์',
            '2025-05-01' => 'วันแรงงานแห่งชาติ',
            '2025-05-05' => 'วันฉัตรมงคล',
            '2025-06-03' => 'วันเฉลิมพระชนมพรรษาสมเด็จพระราชินี',
            '2025-07-28' => 'วันเฉลิมพระชนมพรรษา ร.10',
            '2025-08-12' => 'วันแม่แห่งชาติ',
            '2025-10-13' => 'วันคล้ายวันสวรรคต ร.9',
            '2025-12-05' => 'วันพ่อแห่งชาติ',
            '2025-12-10' => 'วันรัฐธรรมนูญ',
            '2025-12-31' => 'วันสิ้นปี'
        ];
    }
    
    // ดึงวันหยุดเพิ่มเติมจากการตั้งค่า
    $exemption_dates = getSetting('exemption_dates');
    if ($exemption_dates) {
        $dates = explode(',', $exemption_dates);
        foreach ($dates as $date) {
            $date = trim($date);
            if (!empty($date)) {
                // แปลงรูปแบบ dd/mm/yyyy เป็น yyyy-mm-dd ถ้าจำเป็น
                if (strpos($date, '/') !== false) {
                    list($d, $m, $y) = explode('/', $date);
                    $date = "$y-$m-$d";
                }
                
                if (!isset($holidays[$date])) {
                    $holidays[$date] = 'วันหยุดพิเศษ';
                }
            }
        }
    }
    
    return $holidays;
}

// ดึงค่าการตั้งค่าจากฐานข้อมูล
function getSetting($key, $default = '') {
    $db = getDB();
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : $default;
}

// ดึงข้อมูลการตั้งค่าระบบสำหรับรายงาน
function getReportSettings() {
    $settings = [
        'activity_head_name' => getSetting('activity_head_name', 'นายมนตรี ศรีสุข'),
        'activity_head_title' => getSetting('activity_head_title', 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา'),
        'director_deputy_name' => getSetting('director_deputy_name', 'นายพงษ์ศักดิ์ สนโศรก'),
        'director_deputy_title' => getSetting('director_deputy_title', 'รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา'),
        'director_name' => getSetting('director_name', 'นายชัยพงษ์ พงษ์พิทักษ์'),
        'director_title' => getSetting('director_title', 'ผู้อำนวยการวิทยาลัยการอาชีพปราสาท'),
        'logo_path' => getSetting('school_logo', 'assets/images/school_logo.png')
    ];
    
    return $settings;
}

// คำนวณสัปดาห์ปัจจุบันจากวันที่เริ่มต้นภาคเรียน
function calculateWeeks($start_date, $current_date) {
    $start = new DateTime($start_date);
    $current = new DateTime($current_date);
    $interval = $start->diff($current);
    
    // คำนวณจำนวนวันและหารด้วย 7 เพื่อให้ได้จำนวนสัปดาห์
    $days = $interval->days;
    return floor($days / 7) + 1;
}

// สร้าง PDF โดยใช้ MPDF (เตรียมไว้สำหรับการสร้าง PDF ฝั่ง server)
function generatePDF($class_id, $week_number, $start_date, $end_date, $academic_year_id) {
    require_once '../vendor/autoload.php'; // ต้องติดตั้ง MPDF ผ่าน Composer ก่อน
    
    // ดึงข้อมูลที่จำเป็น
    $academic_year = getActiveAcademicYear();
    $holidays = getHolidays($academic_year_id);
    $report_settings = getReportSettings();
    
    // ดึงข้อมูลชั้นเรียน
    $db = getDB();
    $query = "SELECT c.*, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลครูที่ปรึกษา
    $advisors = getAdvisorsByClass($class_id);
    
    // ดึงข้อมูลการเข้าแถว
    $students_with_attendance = getAttendanceByClassAndDateRange($class_id, $start_date, $end_date, $academic_year_id);
    
    // วันในสัปดาห์ (จันทร์-ศุกร์)
    $weekDays = getWeekDays($start_date, $end_date);
    
    // สร้าง HTML สำหรับ MPDF
    $html = createReportHTML($class_info, $advisors, $students_with_attendance, $weekDays, $week_number, $academic_year, $holidays, $report_settings);
    
    // สร้าง PDF
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_header' => 0,
        'margin_footer' => 0,
        'tempDir' => '../tmp'
    ]);
    
    // เพิ่มฟอนต์ภาษาไทย
    $mpdf->useAdobeCJK = true;
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    
    // สร้าง PDF
    $mpdf->WriteHTML($html);
    
    // ส่งไฟล์ PDF ไปยังเบราว์เซอร์
    $fileName = "รายงานเช็คชื่อเข้าแถว_{$class_info['level']}{$class_info['group_number']}_{$week_number}.pdf";
    $mpdf->Output($fileName, 'D');
}

// สร้าง HTML สำหรับรายงาน
// สร้าง HTML สำหรับรายงาน
function createReportHTML($class_info, $advisors, $students, $days, $week_number, $academic_year, $holidays, $report_settings) {
    // ช่วยเหลือการเลือกวันทำงาน (จันทร์-ศุกร์)
    $work_days = array_filter($days, function($day) {
        $dayOfWeek = date('w', strtotime($day));
        return $dayOfWeek >= 1 && $dayOfWeek <= 5; // จันทร์-ศุกร์
    });
    
    // การแปลงเดือนเป็นภาษาไทย
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    // ข้อมูลเดือนและปีในรายงาน
    $reportDate = new DateTime(reset($work_days));
    $month = $thai_months[$reportDate->format('n')];
    $thai_year = $reportDate->format('Y') + 543;
    
    // เริ่มสร้าง HTML
    $html = '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>รายงานเช็คชื่อเข้าแถว</title>
        <style>
            /* CSS สำหรับการพิมพ์ */
            body {
                font-family: "TH Sarabun New", sans-serif;
                font-size: 16pt;
                line-height: 1.2;
            }
            .report-header {
                text-align: center;
                margin-bottom: 20px;
                page-break-before: always;
                page-break-after: avoid;
            }
            .report-logo {
                margin-bottom: 10px;
            }
            .report-logo img {
                height: 80px;
                width: auto;
            }
            .report-title h1 {
                font-size: 20pt;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .report-title h2 {
                font-size: 18pt;
                margin-bottom: 10px;
            }
            .report-title h3 {
                font-size: 16pt;
                margin-bottom: 5px;
            }
            .attendance-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                page-break-inside: auto;
            }
            .attendance-table thead {
                display: table-header-group;
            }
            .attendance-table th,
            .attendance-table td {
                border: 1px solid #000;
                padding: 5px;
                text-align: center;
            }
            .attendance-table th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            .attendance-table tr {
                page-break-inside: avoid;
            }
            .attendance-table .name-col {
                text-align: left;
            }
            .attendance-table td.present {
                color: green;
                font-weight: bold;
            }
            .attendance-table td.absent {
                color: red;
                font-weight: bold;
            }
            .attendance-table td.late {
                color: orange;
                font-weight: bold;
            }
            .attendance-table td.leave {
                color: blue;
                font-weight: bold;
            }
            .attendance-table td.holiday {
                background-color: #f0f0f0;
                font-style: italic;
            }
            .report-summary {
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            .report-summary p {
                margin-bottom: 5px;
            }
            .signature-section {
                display: table;
                width: 100%;
                margin-top: 40px;
                page-break-inside: avoid;
            }
            .signature-box {
                display: table-cell;
                width: 33%;
                text-align: center;
                padding: 0 10px;
            }
            .signature-line {
                margin-bottom: 40px;
            }
            .signature-name,
            .signature-title,
            .signature-subtitle {
                margin-bottom: 5px;
            }
            @page {
                size: A4 portrait;
                margin: 1cm;
            }
        </style>
    </head>
    <body>';
    
    // เพิ่มส่วนหัวรายงาน
    $html .= '
        <div class="report-header">
            <div class="report-logo">
                <img src="' . $report_settings['logo_path'] . '" alt="โลโก้วิทยาลัย">
            </div>
            <div class="report-title">
                <h1>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</h1>
                <h2>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</h2>
                <h3>ภาคเรียนที่ ' . $academic_year['semester'] . ' ปีการศึกษา ' . ($academic_year['year'] + 543) . ' สัปดาห์ที่ ' . $week_number . ' เดือน ' . $month . ' พ.ศ. ' . $thai_year . '</h3>
                <h3>ระดับชั้น ' . $class_info['level'] . ' กลุ่ม ' . $class_info['group_number'] . ' แผนกวิชา' . $class_info['department_name'] . '</h3>
            </div>
        </div>';
        
    // สร้างตาราง
    $html .= '
        <table class="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2" class="no-col">ลำดับที่</th>
                    <th rowspan="2" class="code-col">รหัสนักศึกษา</th>
                    <th rowspan="2" class="name-col">ชื่อ-สกุล</th>
                    <th colspan="' . count($work_days) . '" class="week-header">สัปดาห์ที่ ' . $week_number . '</th>
                    <th rowspan="2" class="total-col">รวม</th>
                    <th rowspan="2" class="remark-col">หมายเหตุ</th>
                </tr>
                <tr class="day-header">';
    
    // วันที่ในสัปดาห์ (เฉพาะจันทร์-ศุกร์)
    $thaiDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    foreach (array_values($work_days) as $index => $day) {
        $dayDate = date('d', strtotime($day));
        $dayOfWeek = date('w', strtotime($day));
        $html .= '<th class="day-col">' . ($index + 1) . '<br>' . $thaiDaysShort[$dayOfWeek] . '<br>' . $dayDate . '</th>';
    }
    
    $html .= '</tr>
            </thead>
            <tbody>';
    
    // ตัวแปรสำหรับสรุปข้อมูล
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLate = 0;
    $totalLeave = 0;
    
    // แถวข้อมูลนักเรียน
    foreach ($students as $index => $student) {
        $html .= '<tr>
            <td class="no-col">' . ($index + 1) . '</td>
            <td class="code-col">' . $student['student_code'] . '</td>
            <td class="name-col">' . $student['title'] . $student['first_name'] . ' ' . $student['last_name'] . '</td>';
        
        // ตัวแปรสำหรับนับการเข้าแถวของนักเรียนคนนี้
        $studentPresent = 0;
        $studentAbsent = 0;
        $studentLate = 0;
        $studentLeave = 0;
        
        // วนลูปผ่านวันในสัปดาห์ (จันทร์-ศุกร์)
        foreach ($work_days as $day) {
            $html .= '<td class="day-col">';
            
            // ตรวจสอบวันหยุด
            if (isset($holidays[$day])) {
                $html .= '<span class="holiday" title="' . $holidays[$day] . '">หยุด</span>';
            } else {
                // ดึงข้อมูลการเข้าแถวสำหรับวันนี้
                if (isset($student['attendances'][$day])) {
                    $attendance = $student['attendances'][$day];
                    $status = $attendance['status'];
                    
                    // แสดงสัญลักษณ์การเข้าแถว
                    switch ($status) {
                        case 'present':
                            $html .= '<span class="present">✓</span>';
                            $studentPresent++;
                            $totalPresent++;
                            break;
                        case 'absent':
                            $html .= '<span class="absent">x</span>';
                            $studentAbsent++;
                            $totalAbsent++;
                            break;
                        case 'late':
                            $html .= '<span class="late">ส</span>';
                            $studentLate++;
                            $totalLate++;
                            // นับว่ามาด้วย แต่สาย
                            $studentPresent++;
                            $totalPresent++;
                            break;
                        case 'leave':
                            $html .= '<span class="leave">ล</span>';
                            $studentLeave++;
                            $totalLeave++;
                            break;
                        default:
                            $html .= '-';
                    }
                } else {
                    $html .= '-';
                }
            }
            
            $html .= '</td>';
        }
        
        // เซลล์รวมและหมายเหตุ
        $html .= '<td class="total-col">' . $studentPresent . '</td>';
        
        // สร้างหมายเหตุอัตโนมัติ
        $remarks = [];
        if ($studentAbsent > 0) $remarks[] = 'ขาด ' . $studentAbsent . ' วัน';
        if ($studentLate > 0) $remarks[] = 'สาย ' . $studentLate . ' วัน';
        if ($studentLeave > 0) $remarks[] = 'ลา ' . $studentLeave . ' วัน';
        
        $html .= '<td class="remark-col">' . implode(', ', $remarks) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="' . (count($work_days) + 5) . '">
                        <div class="report-summary">
                            <p>สรุป</p>
                            <p>จำนวนคน ' . count($students) . ' มา ' . $totalPresent . ' ขาด ' . $totalAbsent . ' สาย ' . $totalLate . ' ลา ' . $totalLeave . '</p>';
    
    // คำนวณอัตราการเข้าแถว
    $totalAttendanceDays = count($work_days) * count($students);
    $effectiveAttendanceDays = $totalAttendanceDays - $totalLeave; // หักวันลาออก
    $attendanceRate = $effectiveAttendanceDays > 0 ? 
        (($totalPresent) / $effectiveAttendanceDays * 100) : 0;
    
    $html .= '<p>สรุปจำนวนนักเรียนเข้าแถวร้อยละ ' . number_format($attendanceRate, 2) . '</p>
                        </div>
                        
                        <div class="signature-section">
                            <div class="signature-box">
                                <div class="signature-line">ลงชื่อ........................................</div>
                                <div class="signature-name">(' . ($advisors[0]['title'] ?? '') . ' ' . ($advisors[0]['first_name'] ?? '') . ' ' . ($advisors[0]['last_name'] ?? '') . ')</div>
                                <div class="signature-title">ครูที่ปรึกษา</div>
                            </div>
                            
                            <div class="signature-box">
                                <div class="signature-line">ลงชื่อ........................................</div>
                                <div class="signature-name">(' . $report_settings['activity_head_name'] . ')</div>
                                <div class="signature-title">' . $report_settings['activity_head_title'] . '</div>
                            </div>
                            
                            <div class="signature-box">
                                <div class="signature-line">ลงชื่อ........................................</div>
                                <div class="signature-name">(' . $report_settings['director_deputy_name'] . ')</div>
                                <div class="signature-title">' . $report_settings['director_deputy_title'] . '</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </body>
    </html>';
    
    return $html;
}

// ฟังก์ชันช่วยหาวันในช่วงที่กำหนด
function getWeekDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // รวมวันสุดท้าย
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $days = [];
    foreach ($period as $day) {
        $days[] = $day->format('Y-m-d');
    }
    
    return $days;
}

// ดึงข้อมูลสำหรับหน้ารายงาน
$academic_year = getActiveAcademicYear();
$departments = getDepartments();
$current_week = calculateWeeks($academic_year['start_date'], date('Y-m-d'));
$all_weeks = getAllWeeks($academic_year);
$holidays = getHolidays($academic_year['academic_year_id']);
$report_settings = getReportSettings();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'print_activity_report';
$page_title = 'พิมพ์ผลกิจกรรมเข้าแถว';
$page_header = 'พิมพ์รายงานผลกิจกรรมเข้าแถว';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/print_activity.css',
];

$extra_js = [
    'assets/js/print_activity.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// ตรวจสอบการร้องขอ PDF หรือ Excel
if (isset($_GET['export']) && isset($_GET['class_id']) && isset($_GET['week'])) {
    $export_type = $_GET['export'];
    $class_id = $_GET['class_id'];
    $week_number = $_GET['week'];
    $week_data = null;
    
    // หาข้อมูลสัปดาห์
    foreach ($all_weeks as $week) {
        if ($week['week_number'] == $week_number) {
            $week_data = $week;
            break;
        }
    }
    
    if ($week_data) {
        $start_date = $week_data['start_date'];
        $end_date = $week_data['end_date'];
        
        if ($export_type === 'pdf') {
            // ส่งออกเป็น PDF ด้วย MPDF
            generatePDF($class_id, $week_number, $start_date, $end_date, $academic_year['academic_year_id']);
            exit;
        } else if ($export_type === 'excel') {
            // ส่งออกเป็น Excel (ใช้ JavaScript ดีกว่า)
            // ในที่นี้จะเปลี่ยนเป็นการเรียกหน้า HTML และให้ JavaScript จัดการการส่งออก
            // สามารถเพิ่มพารามิเตอร์เพื่อให้ JavaScript รู้ว่าต้องส่งออกเป็น Excel
            $extra_js[] = 'assets/js/export_excel.js';
        }
    }
}

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/print_activity_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
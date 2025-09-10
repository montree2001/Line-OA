<?php
/**
 * print_evaluation_report.php - รายงานการประเมินผลกิจกรรมแบบสาธารณะ
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตั้งค่าเวลา
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบ POST data
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('กรุณาระบุข้อมูลให้ครบถ้วน');
}

if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    die('ข้อมูลไม่ครบถ้วน');
}

try {
    // นำเข้า mPDF และเชื่อมต่อฐานข้อมูล
    require_once 'vendor/autoload.php';
    require_once 'db_connect.php';

    $conn = getDB();

    // รับข้อมูลจาก POST
    $class_id = $_POST['class_id'] ?? '';
    $search_input = $_POST['search'] ?? '';
    $search_type = $_POST['search_type'] ?? 'class';
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_week = $_POST['week_number'] ?? 1;
    $end_week = $_POST['end_week'] ?? 1;

    // ดึงข้อมูลปีการศึกษา
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษา");
    }

    // คำนวณจำนวนสัปดาห์ทั้งหมด
    $academic_start = new DateTime($academic_year['start_date']);
    $academic_end = new DateTime($academic_year['end_date']);
    $total_days = $academic_start->diff($academic_end)->days;
    $total_weeks = ceil($total_days / 7);

    // ดึงข้อมูลนักเรียน
    if ($search_type === 'class' && !empty($class_id)) {
        // ค้นหาตามห้องเรียน
        $query = "
            SELECT s.student_id, s.student_code, 
                   CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as title,
                   u.first_name, u.last_name, c.level,
                   c.level as class_level, c.group_number, d.department_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ข้อมูลห้องเรียน
        $class_info = $students[0] ?? null;
        $report_title = $class_info ? "{$class_info['class_level']}/{$class_info['group_number']} {$class_info['department_name']}" : "ไม่พบข้อมูลห้องเรียน";
    } else {
        // ค้นหาตามนักเรียน
        $query = "
            SELECT s.student_id, s.student_code, 
                   CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as title,
                   u.first_name, u.last_name, c.level,
                   c.level as class_level, c.group_number, d.department_name
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE (s.student_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?) 
                  AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ";
        $search_term = "%{$search_input}%";
        $stmt = $conn->prepare($query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report_title = "ค้นหา: {$search_input}";
    }

    if (empty($students)) {
        throw new Exception('ไม่พบข้อมูลนักเรียน');
    }

    // ดึงข้อมูลวันหยุด (ถ้ามีตาราง holidays)
    $holidays = [];
    try {
        $query = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $holidays_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($holidays_data as $holiday) {
            $holidays[$holiday['holiday_date']] = $holiday['holiday_name'];
        }
    } catch (Exception $e) {
        // ถ้าไม่มีตาราง holidays ก็ไม่เป็นไร ใช้อาเรย์ว่าง
        $holidays = [];
    }

    // ดึงข้อมูลครูที่ปรึกษา
    $primary_advisor = null;
    if ($search_type === 'class' && !empty($class_id)) {
        $query = "SELECT t.teacher_id, u.title, u.first_name, u.last_name
                  FROM teachers t 
                  JOIN users u ON t.user_id = u.user_id
                  JOIN class_advisors ca ON t.teacher_id = ca.teacher_id 
                  WHERE ca.class_id = ? AND ca.is_primary = 1
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $primary_advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ดึงข้อมูลผู้ลงนามจากตาราง report_signers
    $signers = [];
    try {
        $query = "SELECT * FROM report_signers WHERE is_active = 1 ORDER BY signer_id LIMIT 3";
        $stmt = $conn->query($query);
        $signers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ถ้าไม่มีตาราง report_signers ใช้ค่าเริ่มต้น
    }
    
    // ดึงข้อมูลการเข้าแถว
    $student_ids = array_column($students, 'student_id');
    $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';

    $query = "
        SELECT student_id, date, attendance_status
        FROM attendance
        WHERE student_id IN ($placeholders) 
              AND date BETWEEN ? AND ?
        ORDER BY date
    ";

    $params = array_merge($student_ids, [$start_date, $end_date]);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดรูปแบบข้อมูลการเข้าแถว
    $attendance_data = [];
    foreach ($attendance_records as $record) {
        $attendance_data[$record['student_id']][$record['date']] = $record['attendance_status'];
    }

    // เตรียมข้อมูลสำหรับรายงาน HTML
    $date_range_text = formatThaiDate($start_date) . ' ถึง ' . formatThaiDate($end_date) . ' (สัปดาห์ที่ ' . $start_week . '-' . $end_week . ')';

    // กำหนดค่า config สำหรับ mPDF
    $mpdf_config = [
        'mode' => 'utf-8',
        'format' => 'A4-L', // A4 Landscape
        'orientation' => 'L',
        'default_font_size' => 16,
        'default_font' => 'thsarabunnew',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 10,
        'margin_footer' => 10,
        'tempDir' => __DIR__ . '/tmp',
        'fontDir' => [
            __DIR__ . '/fonts/',
            __DIR__ . '/fonts/thsarabunnew/'
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

    // คำนวณข้อมูลสำหรับแต่ละสัปดาห์
    $weekly_data = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);

    for ($week = $start_week; $week <= $end_week; $week++) {
        // คำนวณวันที่ของสัปดาห์
        $week_start = new DateTime($academic_year['start_date']);
        $week_start->add(new DateInterval('P' . (($week - 1) * 7) . 'D'));
        
        // ปรับให้เป็นวันจันทร์
        $day_of_week = $week_start->format('w');
        if ($day_of_week == 0) {
            $week_start->add(new DateInterval('P1D'));
        } elseif ($day_of_week > 1) {
            $week_start->sub(new DateInterval('P' . ($day_of_week - 1) . 'D'));
        }
        
        // คำนวณวันจันทร์-ศุกร์
        $week_days = [];
        $temp_date = clone $week_start;
        
        for ($i = 0; $i < 5; $i++) {
            $date_str = $temp_date->format('Y-m-d');
            if ($temp_date >= $current_date && $temp_date <= $end_date_obj) {
                $week_days[] = $date_str;
            }
            $temp_date->add(new DateInterval('P1D'));
        }
        
        // นับวันเรียนจริง (ไม่รวมวันหยุด)
        $study_days = 0;
        foreach ($week_days as $day) {
            if (!isset($holidays[$day])) {
                $study_days++;
            }
        }
        
        $weekly_data[$week] = [
            'days' => $week_days,
            'study_days' => $study_days,
            'week_start' => clone $week_start
        ];
    }

    // กำหนดเกณฑ์การผ่านกิจกรรม
    $pass_criteria = 0.6; // 60%

    // สร้าง HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>รายงานการประเมินผลกิจกรรม</title>
        <style>
        body {
            font-family: \"thsarabunnew\", sans-serif;
            font-size: 16pt;
            line-height: 1.3;
        }
        
        .header {
            text-align: center;
            margin-bottom: 5px;
        }
        
        .school-logo {
            float: left;
            width: 80px;
            height: 80px;
            margin-right: 20px;
            text-align: center;
        }
        
        .school-logo img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .clear {
            clear: both;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 14pt;
        }
        
        th {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        
        .name-column {
            text-align: left;
            padding-left: 10px;
        }
        
        
        .fail {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .notes {
            margin-top: 20px;
            font-size: 14pt;
        }
        
        .page-footer {
            margin-top: 30px;
            font-size: 14pt;
        }
        
        .signature-and-notes-wrapper {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-top: 30px;
            min-height: 300px;
        }
        
        .signature-and-notes-wrapper:before {
            content: "";
            display: block;
            page-break-before: auto;
            orphans: 4;
            widows: 4;
        }
        
        .signature-section {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
            display: table;
            table-layout: fixed;
        }
        
        .signature-box {
            display: table-cell;
            width: 25%;
            text-align: center;
            vertical-align: top;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .signature-line {
            width: 80%;
            height: 1px;
            background-color: #000;
            margin: 50px auto 5px;
        }
        
        .summary-section {
            margin-top: 20px;
            font-size: 14pt;
        }
        
        .clear {
            clear: both;
        }
    </style>
    </head>
    <body>
        <div class="header">
            <div class="school-logo">';
    
    // เพิ่มโลโก้
    if (file_exists('uploads/logos/school_logo.png')) {
        $html .= '<img src="uploads/logos/school_logo.png" alt="Logo">';
    } else {
        $html .= '<div style="width: 100%; height: 100%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10pt;">โลโก้</div>';
    }
    
    $html .= '</div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>รายงานการประเมินผลกิจกรรม</strong><br>
            ' . $report_title . '<br>
            ' . $date_range_text . '
        </p>
            <div class="clear"></div>
        </div>

    <table>
        <thead>
            <tr>
                <th width="30">ลำดับ</th>
                <th width="70">รหัสนักศึกษา</th>
                <th width="100">ชื่อ-สกุล</th>';

    // หัวตารางสัปดาห์
    foreach ($weekly_data as $week => $data) {
        $week_start_formatted = $data['week_start']->format('d/m');
        $week_end = clone $data['week_start'];
        $week_end->add(new DateInterval('P4D'));
        $week_end_formatted = $week_end->format('d/m');
        
        $html .= '<th width="40">สัปดาห์ ' . $week . '<br>(' . $week_start_formatted . '-' . $week_end_formatted . ')<br>' . $data['study_days'] . ' วัน</th>';
    }

    $html .= '<th width="40">รวม<br>เข้าแถว</th>
        <th width="40">รวม<br>วันเรียน</th>
        <th width="30">%</th>
        <th width="40">ผลการ<br>ประเมิน</th>
            </tr>
        </thead>
        <tbody>';

    // แถวข้อมูลนักเรียน
    foreach ($students as $index => $student) {
        $total_present = 0;
        $total_study_days = 0;
        
        $html .= '<tr>
            <td>' . ($index + 1) . '</td>
            <td>' . $student['student_code'] . '</td>
            <td class="name-column">' . htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']) . '</td>';
        
        // แต่ละสัปดาห์
        foreach ($weekly_data as $week => $data) {
            $week_present = 0;
            $week_study_days = $data['study_days'];
            
            foreach ($data['days'] as $day) {
                if (!isset($holidays[$day])) { // ไม่นับวันหยุด
                    if (isset($attendance_data[$student['student_id']][$day])) {
                        $status = $attendance_data[$student['student_id']][$day];
                        if ($status === 'present' || $status === 'late') {
                            $week_present++;
                        }
                    }
                }
            }
            
            $total_present += $week_present;
            $total_study_days += $week_study_days;
            
            $week_percent = $week_study_days > 0 ? round(($week_present / $week_study_days) * 100, 1) : 0;
            $week_class = $week_percent >= ($pass_criteria * 100) ? 'pass' : 'fail';
            
            $html .= '<td class="' . $week_class . '">' . $week_present . '/' . $week_study_days . '<br>(' . $week_percent . '%)</td>';
        }
        
        // สรุปรวม
        $overall_percent = $total_study_days > 0 ? round(($total_present / $total_study_days) * 100, 1) : 0;
        $overall_status = $overall_percent >= ($pass_criteria * 100) ? 'ผ่าน' : 'ไม่ผ่าน';
        $overall_class = $overall_percent >= ($pass_criteria * 100) ? 'pass' : 'fail';
        
        $html .= '
            <td><strong>' . $total_present . '</strong></td>
            <td><strong>' . $total_study_days . '</strong></td>
            <td><strong>' . $overall_percent . '%</strong></td>
            <td class="' . $overall_class . '">' . $overall_status . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    // คำนวณสรุปผลการประเมิน
    $total_count = count($students);
    $pass_count = 0;
    $fail_count = 0;
    
    // นับจำนวนนักเรียนชาย/หญิง
    $male_count = 0;
    $female_count = 0;
    
    foreach ($students as $student) {
        $total_present = 0;
        $total_study_days = 0;
        
        // คำนวณผลการประเมินของนักเรียนแต่ละคน
        foreach ($weekly_data as $week => $data) {
            foreach ($data['days'] as $day) {
                if (!isset($holidays[$day])) {
                    if (isset($attendance_data[$student['student_id']][$day])) {
                        $status = $attendance_data[$student['student_id']][$day];
                        if ($status === 'present' || $status === 'late') {
                            $total_present++;
                        }
                    }
                }
            }
            $total_study_days += $data['study_days'];
        }
        
        $overall_percent = $total_study_days > 0 ? ($total_present / $total_study_days) * 100 : 0;
        if ($overall_percent >= ($pass_criteria * 100)) {
            $pass_count++;
        } else {
            $fail_count++;
        }
        
        // นับเพศ
        if ($student['title'] == 'นาย') {
            $male_count++;
        } else {
            $female_count++;
        }
    }
    
    $pass_percentage = $total_count > 0 ? round(($pass_count / $total_count) * 100, 2) : 0;
    $fail_percentage = $total_count > 0 ? round(($fail_count / $total_count) * 100, 2) : 0;
    
    // เพิ่มส่วนสรุป
    $html .= '
    <div class="summary-section">
        <strong>สรุป</strong> จำนวนคน <u>&nbsp;&nbsp;' . $total_count . '&nbsp;&nbsp;</u> คน 
        ชาย <u>&nbsp;&nbsp;' . $male_count . '&nbsp;&nbsp;</u> คน
        หญิง <u>&nbsp;&nbsp;' . $female_count . '&nbsp;&nbsp;</u> คน<br>
        <strong>ผลการประเมิน:</strong> 
        ผ่าน <u>&nbsp;&nbsp;' . $pass_count . '&nbsp;&nbsp;</u> คน (ร้อยละ <u>&nbsp;&nbsp;' . $pass_percentage . '&nbsp;&nbsp;</u>) 
        ไม่ผ่าน <u>&nbsp;&nbsp;' . $fail_count . '&nbsp;&nbsp;</u> คน (ร้อยละ <u>&nbsp;&nbsp;' . $fail_percentage . '&nbsp;&nbsp;</u>)
    </div>';

 

    $html .= '
        </ul>
    </div>
    
    <!-- ส่วนเซ็นชื่อและหมายเหตุ -->
    <div class="signature-and-notes-wrapper">
    <div class="signature-section">
        <div class="signature-box">
            <div>ลงชื่อ...........................................</div>';
            
    if ($primary_advisor) {
        $html .= '<div>(' . $primary_advisor['title'] . $primary_advisor['first_name'] . ' ' . $primary_advisor['last_name'] . ')</div>';
    } else {
        $html .= '<div>(.......................................)</div>';
    }
    
    $html .= '<div>ครูที่ปรึกษา</div>
        </div>

        <div class="signature-box">
            <div>ลงชื่อ...........................................</div>';
            
    if (isset($signers[0])) {
        $html .= '<div>(' . $signers[0]['title'] . $signers[0]['first_name'] . ' ' . $signers[0]['last_name'] . ')</div>';
        $html .= '<div>' . $signers[0]['position'] . '</div>';
    } else {
        $html .= '<div>(นายมนตรี ศรีสุข)</div>';
        $html .= '<div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>';
    }
    
    $html .= '</div>

        <div class="signature-box">
            <div>ลงชื่อ...........................................</div>';
            
    if (isset($signers[1])) {
        $html .= '<div>(' . $signers[1]['title'] . $signers[1]['first_name'] . ' ' . $signers[1]['last_name'] . ')</div>';
        $html .= '<div>รองผู้อำนวยการ</div>';
        $html .= '<div>ฝ่ายพัฒนากิจการนักเรียน นักศึกษา</div>';
    } else {
        $html .= '<div>(นายพงษ์ศักดิ์ สนโศรก)</div>';
        $html .= '<div>รองผู้อำนวยการ</div>';
        $html .= '<div>ฝ่ายพัฒนากิจการนักเรียน นักศึกษา</div>';
    }
    
    $html .= '</div>

        <div class="signature-box">
            <div>ลงชื่อ...........................................</div>';
            
    if (isset($signers[2])) {
        $html .= '<div>(' . $signers[2]['title'] . $signers[2]['first_name'] . ' ' . $signers[2]['last_name'] . ')</div>';
        $html .= '<div>ผู้อำนวยการ</div>';
     
    } else {
        $html .= '<div>(นายวันเฉลิม ธิสาโรการ)</div>';
        $html .= '<div>ผู้อำนวยการ</div>';
   
    }
    
    $html .= '</div>
    </div>
    
    <div class="clear"></div>';

    // เพิ่มคำอธิบาย  
    $html .= '<div class="notes">';
    $html .= '<p><strong>หมายเหตุ:</strong></p>';
    $html .= '<ul>';
    $html .= '<li>เกณฑ์การผ่านกิจกรรม: ' . ($pass_criteria * 100) . '% ของจำนวนวันเรียนจริง (ไม่นับวันหยุดราชการ)</li>';
    $html .= '<li>วันเรียนจริงต่อสัปดาห์: วันจันทร์ - ศุกร์ หักลบวันหยุดราชการ</li>';

    // ระบุจำนวนสัปดาห์ตามประเภท
    if (!empty($students)) {
        $level = isset($students[0]['level']) ? $students[0]['level'] : '';
        if (strpos($level, 'ปวช') !== false) {
            $html .= '<li>หลักสูตรประกาศนียบัตรวิชาชีพ (ปวช.): 18 สัปดาห์</li>';
        } elseif (strpos($level, 'ปวส') !== false) {
            $html .= '<li>หลักสูตรประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.): 15 สัปดาห์</li>';
        }
    }

    $html .= '</ul>';
    $html .= '</div>';
    $html .= '<div class="page-footer">';
    $html .= '<p>พิมพ์เมื่อวันที่ ' . date('j/n/Y') . '</p>';
    $html .= '</div>';
    $html .= '</div>'; // Close signature-and-notes-wrapper
    $html .= '</body>';
    $html .= '</html>';

    // เพิ่มหมายเลขหน้า
    $mpdf->SetFooter('หน้าที่ {PAGENO} / {nbpg}');
    
    // ส่งออก PDF
    $mpdf->WriteHTML($html);
    
    $filename = 'รายงานประเมินผลกิจกรรม_' . date('Y-m-d_H-i-s') . '.pdf';
    $mpdf->Output($filename, 'I'); // I = Inline (แสดงในเบราว์เซอร์)

} catch (Exception $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}

// ฟังก์ชันช่วยเหลือ
function formatThaiDate($date_str) {
    $date = new DateTime($date_str);
    return $date->format('d/m/') . ($date->format('Y') + 543);
}
?>
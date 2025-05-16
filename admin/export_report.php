<?php
/**
 * export_report.php - สร้างและส่งออกรายงานผลกิจกรรมเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// เชื่อมต่อฐานข้อมูลและฟังก์ชันช่วยเหลือ
require_once '../db_connect.php';
require_once 'includes/helper_functions.php';

// ตรวจสอบพารามิเตอร์
$export_type = $_GET['format'] ?? '';
$class_id = $_GET['class_id'] ?? '';
$week_number = $_GET['week'] ?? '';
$academic_year_id = $_GET['academic_year_id'] ?? '';

if (empty($export_type) || empty($class_id) || empty($week_number)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ต้องระบุรูปแบบการส่งออก รหัสชั้นเรียน และสัปดาห์']);
    exit;
}

// ดึงข้อมูลปีการศึกษาปัจจุบันถ้าไม่ได้ระบุ
if (empty($academic_year_id)) {
    $db = getDB();
    $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $academic_year_id = $result['academic_year_id'] ?? '';
    
    if (empty($academic_year_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน']);
        exit;
    }
}

// ดึงข้อมูลปีการศึกษา
function getAcademicYear($academic_year_id) {
    $db = getDB();
    $query = "SELECT * FROM academic_years WHERE academic_year_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$academic_year_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลชั้นเรียน
function getClassInfo($class_id) {
    $db = getDB();
    $query = "SELECT c.*, d.department_name 
              FROM classes c 
              JOIN departments d ON c.department_id = d.department_id 
              WHERE c.class_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$class_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลครูที่ปรึกษา
function getAdvisors($class_id) {
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

// ดึงข้อมูลนักเรียน
function getStudents($class_id) {
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

// ดึงข้อมูลการเข้าแถวของนักเรียน
function getAttendance($student_id, $start_date, $end_date, $academic_year_id) {
    $db = getDB();
    $query = "SELECT date, attendance_status, remarks
              FROM attendance
              WHERE student_id = ? AND date BETWEEN ? AND ? AND academic_year_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id, $start_date, $end_date, $academic_year_id]);
    
    $attendances = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $attendances[$row['date']] = [
            'status' => $row['attendance_status'],
            'remarks' => $row['remarks']
        ];
    }
    
    return $attendances;
}

// ดึงข้อมูลวันหยุด
function getHolidays($academic_year_id) {
    $db = getDB();
    $holidays = [];
    
    // ดึงวันหยุดจากฐานข้อมูล (ถ้ามี)
    try {
        $query = "SELECT holiday_date, holiday_name FROM holidays WHERE academic_year_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$academic_year_id]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $holidays[$row['holiday_date']] = $row['holiday_name'];
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

// ดึงข้อมูลสัปดาห์
function getWeekDates($academic_year, $week_number) {
    $start_date = new DateTime($academic_year['start_date']);
    $start_date->modify('+' . (($week_number - 1) * 7) . ' days');
    
    $end_date = clone $start_date;
    $end_date->modify('+6 days');
    
    // ตรวจสอบว่าไม่เกินวันสิ้นสุดภาคเรียน
    $semester_end = new DateTime($academic_year['end_date']);
    if ($end_date > $semester_end) {
        $end_date = clone $semester_end;
    }
    
    return [
        'start_date' => $start_date->format('Y-m-d'),
        'end_date' => $end_date->format('Y-m-d')
    ];
}

// ดึงวันในช่วงเวลา
function getDaysInRange($start_date, $end_date) {
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

// ดึงค่าการตั้งค่าจากฐานข้อมูล
function getSetting($key, $default = '') {
    $db = getDB();
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['setting_value'] : $default;
}

// ดึงข้อมูลการตั้งค่ารายงาน
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

// รวบรวมข้อมูลรายงาน
$academic_year = getAcademicYear($academic_year_id);
$class_info = getClassInfo($class_id);
$advisors = getAdvisors($class_id);
$students = getStudents($class_id);
$report_settings = getReportSettings();
$week_dates = getWeekDates($academic_year, $week_number);
$holidays = getHolidays($academic_year_id);
$days = getDaysInRange($week_dates['start_date'], $week_dates['end_date']);

// เตรียมข้อมูลนักเรียนพร้อมการเข้าแถว
$students_with_attendance = [];
foreach ($students as $student) {
    $attendances = getAttendance($student['student_id'], $week_dates['start_date'], $week_dates['end_date'], $academic_year_id);
    $student['attendances'] = $attendances;
    $students_with_attendance[] = $student;
}

// ตรวจสอบประเภทการส่งออก
if ($export_type === 'pdf') {
    // ส่งออกเป็น PDF ด้วย MPDF
    require_once '../vendor/autoload.php'; // ต้องติดตั้ง MPDF ผ่าน Composer ก่อน
    
    // เตรียมข้อมูลสำหรับ PDF
    $html = createReportHTML($class_info, $advisors, $students_with_attendance, $days, $week_number, $academic_year, $holidays, $report_settings);
    
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
    
} elseif ($export_type === 'excel') {
    // ส่งออกเป็น Excel ด้วย PhpSpreadsheet
    require_once '../vendor/autoload.php'; // ต้องติดตั้ง PhpSpreadsheet ผ่าน Composer ก่อน
    
    // ใช้ PhpSpreadsheet
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    // สร้าง Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // เพิ่มข้อมูลหัวรายงาน
    $sheet->setCellValue('A1', 'งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท');
    $sheet->setCellValue('A2', 'แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง');
    $sheet->setCellValue('A3', "ภาคเรียนที่ {$academic_year['semester']} ปีการศึกษา " . ($academic_year['year'] + 543) . " สัปดาห์ที่ {$week_number}");
    $sheet->setCellValue('A4', "ระดับชั้น {$class_info['level']} กลุ่ม {$class_info['group_number']} แผนกวิชา{$class_info['department_name']}");
    
    // เพิ่มหัวตาราง
    $sheet->setCellValue('A6', 'ลำดับที่');
    $sheet->setCellValue('B6', 'รหัสนักศึกษา');
    $sheet->setCellValue('C6', 'ชื่อ-สกุล');
    
    // เพิ่มวันในสัปดาห์ (เฉพาะจันทร์-ศุกร์)
    $work_days = array_filter($days, function($day) {
        $dayOfWeek = date('w', strtotime($day));
        return $dayOfWeek >= 1 && $dayOfWeek <= 5; // จันทร์-ศุกร์
    });
    
    $thaiDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $column = 'D';
    foreach ($work_days as $index => $day) {
        $dayDate = date('d', strtotime($day));
        $dayOfWeek = date('w', strtotime($day));
        $sheet->setCellValue($column . '6', ($index + 1) . "\n" . $thaiDaysShort[$dayOfWeek]);
        $column++;
    }
    
    // เพิ่มคอลัมน์รวมและหมายเหตุ
    $sheet->setCellValue($column . '6', 'รวม');
    $column++;
    $sheet->setCellValue($column . '6', 'หมายเหตุ');
    
    // เพิ่มข้อมูลนักเรียน
    $row = 7;
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLate = 0;
    $totalLeave = 0;
    
    foreach ($students_with_attendance as $index => $student) {
        $sheet->setCellValue('A' . $row, $index + 1);
        $sheet->setCellValue('B' . $row, $student['student_code']);
        $sheet->setCellValue('C' . $row, $student['title'] . $student['first_name'] . ' ' . $student['last_name']);
        
        // ตัวแปรสำหรับนับการเข้าแถวของนักเรียนคนนี้
        $studentPresent = 0;
        $studentAbsent = 0;
        $studentLate = 0;
        $studentLeave = 0;
        
        // เพิ่มข้อมูลการเข้าแถวในแต่ละวัน
        $column = 'D';
        foreach ($work_days as $day) {
            // ตรวจสอบวันหยุด
            if (isset($holidays[$day])) {
                $sheet->setCellValue($column . $row, 'หยุด');
            } else {
                // ดึงข้อมูลการเข้าแถวสำหรับวันนี้
                if (isset($student['attendances'][$day])) {
                    $attendance = $student['attendances'][$day];
                    $status = $attendance['status'];
                    
                    // แสดงสัญลักษณ์การเข้าแถว
                    $symbols = [
                        'present' => '✓',
                        'absent' => 'x',
                        'late' => 'ส',
                        'leave' => 'ล'
                    ];
                    $sheet->setCellValue($column . $row, $symbols[$status] ?? '-');
                    
                    // นับสถานะการเข้าแถว
                    if ($status === 'present') {
                        $studentPresent++;
                        $totalPresent++;
                    } else if ($status === 'absent') {
                        $studentAbsent++;
                        $totalAbsent++;
                    } else if ($status === 'late') {
                        $studentLate++;
                        $totalLate++;
                        // นับว่ามาด้วย แต่สาย
                        $studentPresent++;
                        $totalPresent++;
                    } else if ($status === 'leave') {
                        $studentLeave++;
                        $totalLeave++;
                    }
                } else {
                    $sheet->setCellValue($column . $row, '-');
                }
            }
            $column++;
        }
        
        // เพิ่มคอลัมน์รวม
        $sheet->setCellValue($column . $row, $studentPresent);
        $column++;
        
        // เพิ่มหมายเหตุ
        $remarks = [];
        if ($studentAbsent > 0) $remarks[] = "ขาด {$studentAbsent} วัน";
        if ($studentLate > 0) $remarks[] = "สาย {$studentLate} วัน";
        if ($studentLeave > 0) $remarks[] = "ลา {$studentLeave} วัน";
        
        $sheet->setCellValue($column . $row, implode(', ', $remarks));
        
        $row++;
    }
    
    // เพิ่มข้อมูลสรุป
    $row += 2; // เว้นบรรทัด
    $sheet->setCellValue('A' . $row, 'สรุป');
    $row++;
    
    $totalStudents = count($students_with_attendance);
    $sheet->setCellValue('A' . $row, "จำนวนคน {$totalStudents} มา {$totalPresent} ขาด {$totalAbsent} สาย {$totalLate} ลา {$totalLeave}");
    $row++;
    
    // คำนวณอัตราการเข้าแถว
    $totalDays = count($work_days) * $totalStudents;
    $attendanceRate = $totalDays > 0 ? 
        (($totalPresent) / ($totalDays - $totalLeave) * 100) : 0;
    
    $sheet->setCellValue('A' . $row, "สรุปจำนวนนักเรียนเข้าแถวร้อยละ " . number_format($attendanceRate, 2));
    $row += 2; // เว้นบรรทัด
    
    // เพิ่มส่วนลายเซ็น
    $sheet->setCellValue('A' . $row, 'ลงชื่อ........................................');
    $sheet->setCellValue('D' . $row, 'ลงชื่อ........................................');
    $sheet->setCellValue('G' . $row, 'ลงชื่อ........................................');
    $row++;
    
    // ชื่อผู้ลงนาม
    $advisorName = $advisors[0]['title'] ?? '' . ' ' . $advisors[0]['first_name'] ?? '' . ' ' . $advisors[0]['last_name'] ?? '';
    $sheet->setCellValue('A' . $row, '(' . ($advisorName ?: 'ครูที่ปรึกษา') . ')');
    $sheet->setCellValue('D' . $row, '(' . $report_settings['activity_head_name'] . ')');
    $sheet->setCellValue('G' . $row, '(' . $report_settings['director_deputy_name'] . ')');
    $row++;
    
    // ตำแหน่ง
    $sheet->setCellValue('A' . $row, 'ครูที่ปรึกษา');
    $sheet->setCellValue('D' . $row, $report_settings['activity_head_title']);
    $sheet->setCellValue('G' . $row, $report_settings['director_deputy_title']);
    
    // ปรับความกว้างคอลัมน์ให้เหมาะสม
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    
    // ตั้งค่าความกว้างคอลัมน์สำหรับวันในสัปดาห์
    $column = 'D';
    for ($i = 0; $i < count($work_days); $i++) {
        $sheet->getColumnDimension($column)->setWidth(10);
        $column++;
    }
    
    // ตั้งค่าความกว้างคอลัมน์รวมและหมายเหตุ
    $sheet->getColumnDimension($column)->setWidth(10);
    $column++;
    $sheet->getColumnDimension($column)->setWidth(20);
    
    // ส่งไฟล์ Excel ไปยังเบราว์เซอร์
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="รายงานเช็คชื่อเข้าแถว_' . $class_info['level'] . $class_info['group_number'] . '_สัปดาห์' . $week_number . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} else {
    // รูปแบบไม่รองรับ
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'รูปแบบการส่งออกไม่รองรับ']);
}

// ฟังก์ชันสร้าง HTML สำหรับรายงาน PDF
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
    $reportDate = new DateTime(reset($days));
    $month = $thai_months[$reportDate->format('n')];
    $thai_year = $reportDate->format('Y') + 543;
    
    // สร้าง HTML สำหรับรายงาน
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
            .report-summary {
                margin-bottom: 20px;
            }
            .report-summary p {
                margin-bottom: 5px;
            }
            .signature-section {
                display: table;
                width: 100%;
                margin-top: 40px;
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
        </style>
    </head>
    <body>
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
        </div>
        
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
                $html .= '<span title="' . $holidays[$day] . '">หยุด</span>';
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
    $attendanceRate = $totalAttendanceDays > 0 ? 
        (($totalPresent) / ($totalAttendanceDays - $totalLeave) * 100) : 0;
    
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
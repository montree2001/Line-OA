<?php
/**
 * download_report.php - API สำหรับดาวน์โหลดรายงานการเข้าแถว
 * สำหรับระบบน้องสัตบรรณ ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้']);
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตรวจสอบการติดตั้ง PHPExcel (หรือใช้ไลบรารีอื่น)
// ในอนาคตควรใช้ PhpSpreadsheet แทน PHPExcel
if (!class_exists('PHPExcel')) {
    include '../../vendor/autoload.php';
}

// ฟังก์ชันดึงข้อมูลรายงานทั่วไป
function getGeneralReport($filters = []) {
    $conn = getDB();
    $data = [];
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $academicYearId = $filters['academic_year_id'] ?? null;
    
    if (!$academicYearId) {
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
    }
    
    // สร้าง query พื้นฐาน
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name,
                COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE NULL END) as present_count,
                COUNT(a.attendance_id) as total_count,
                ROUND(
                    (COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE NULL END) / COUNT(a.attendance_id)) * 100, 
                    1
                ) as attendance_rate
             FROM students s
             JOIN users u ON s.user_id = u.user_id
             LEFT JOIN classes c ON s.current_class_id = c.class_id
             LEFT JOIN departments d ON c.department_id = d.department_id
             LEFT JOIN attendance a ON s.student_id = a.student_id AND a.academic_year_id = :academic_year_id
             WHERE s.status = 'กำลังศึกษา'";
    
    // กรองตามช่วงเวลา
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $query .= " AND a.date BETWEEN :start_date AND :end_date";
    }
    
    // กรองตามแผนกวิชา
    if (!empty($filters['department_id'])) {
        $query .= " AND c.department_id = :department_id";
    }
    
    // กรองตามระดับชั้น
    if (!empty($filters['level'])) {
        $query .= " AND c.level = :level";
    }
    
    // กรองตามห้องเรียน
    if (!empty($filters['class_id'])) {
        $query .= " AND s.current_class_id = :class_id";
    }
    
    // จัดกลุ่มและเรียงลำดับ
    $query .= " GROUP BY s.student_id
               ORDER BY c.level, c.group_number, s.student_code";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':academic_year_id', $academicYearId, PDO::PARAM_INT);
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $stmt->bindParam(':start_date', $filters['start_date']);
        $stmt->bindParam(':end_date', $filters['end_date']);
    }
    
    if (!empty($filters['department_id'])) {
        $stmt->bindParam(':department_id', $filters['department_id'], PDO::PARAM_INT);
    }
    
    if (!empty($filters['level'])) {
        $stmt->bindParam(':level', $filters['level'], PDO::PARAM_STR);
    }
    
    if (!empty($filters['class_id'])) {
        $stmt->bindParam(':class_id', $filters['class_id'], PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $students;
}

// ฟังก์ชันดึงข้อมูลรายงานนักเรียนรายบุคคล
function getStudentReport($studentId, $filters = []) {
    $conn = getDB();
    $data = [];
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $academicYearId = $filters['academic_year_id'] ?? null;
    
    if (!$academicYearId) {
        $query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($query);
        $academicYearId = $stmt->fetchColumn();
    }
    
    // 1. ดึงข้อมูลพื้นฐานของนักเรียน
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name,
                t.first_name as advisor_first_name,
                t.last_name as advisor_last_name,
                t.phone_number as advisor_phone
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
              LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
              WHERE s.student_id = :student_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return null;
    }
    
    // 2. ดึงข้อมูลการเข้าแถวทั้งหมด
    $query = "SELECT 
                date,
                attendance_status,
                check_time,
                check_method,
                remarks
              FROM attendance
              WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
    
    // กรองตามช่วงเวลา
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $query .= " AND date BETWEEN :start_date AND :end_date";
    }
    
    $query .= " ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year_id', $academicYearId, PDO::PARAM_INT);
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $stmt->bindParam(':start_date', $filters['start_date']);
        $stmt->bindParam(':end_date', $filters['end_date']);
    }
    
    $stmt->execute();
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. คำนวณสถิติ
    $totalDays = count($attendance);
    $presentDays = 0;
    $absentDays = 0;
    $lateDays = 0;
    $leaveDays = 0;
    
    foreach ($attendance as $record) {
        switch ($record['attendance_status']) {
            case 'present':
                $presentDays++;
                break;
            case 'absent':
                $absentDays++;
                break;
            case 'late':
                $lateDays++;
                break;
            case 'leave':
                $leaveDays++;
                break;
        }
    }
    
    $attendanceRate = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100, 1) : 0;
    
    // 4. รวมข้อมูลทั้งหมด
    $data = [
        'profile' => [
            'student_id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'name' => $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'],
            'class' => $student['level'] . '/' . $student['group_number'],
            'department' => $student['department_name'],
            'advisor' => ($student['advisor_first_name']) ? 'อ.' . $student['advisor_first_name'] . ' ' . $student['advisor_last_name'] : '-',
            'advisor_phone' => $student['advisor_phone'] ?? '-'
        ],
        'statistics' => [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'attendance_rate' => $attendanceRate
        ],
        'attendance' => $attendance
    ];
    
    return $data;
}

// ฟังก์ชันสร้างไฟล์ Excel รายงานทั่วไป
function generateGeneralExcel($data, $filters = []) {
    // สร้าง instance ของ PHPExcel
    $excel = new PHPExcel();
    
    // ตั้งค่าเอกสาร
    $excel->getProperties()
        ->setCreator("ระบบน้องสัตบรรณ")
        ->setLastModifiedBy("ระบบน้องสัตบรรณ")
        ->setTitle("รายงานการเข้าแถว")
        ->setSubject("รายงานการเข้าแถวนักเรียน")
        ->setDescription("รายงานการเข้าแถวนักเรียน");
    
    // รับ active sheet
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle("รายงานการเข้าแถว");
    
    // ตั้งค่า header
    $sheet->setCellValue('A1', 'รายงานการเข้าแถวนักเรียน');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    
    // กำหนดชื่อรายงานตามตัวกรอง
    $reportTitle = 'ข้อมูลเดือน' . date('F Y');
    
    if (isset($filters['period'])) {
        switch ($filters['period']) {
            case 'current_month':
                $reportTitle = 'ข้อมูลเดือน' . date('F Y');
                break;
            case 'prev_month':
                $reportTitle = 'ข้อมูลเดือน' . date('F Y', strtotime('-1 month'));
                break;
            case 'last3':
                $reportTitle = 'ข้อมูล 3 เดือนย้อนหลัง';
                break;
            case 'semester':
                $reportTitle = 'ข้อมูลภาคเรียนปัจจุบัน';
                break;
            case 'custom':
                if (isset($filters['start_date']) && isset($filters['end_date'])) {
                    $reportTitle = 'ข้อมูลระหว่างวันที่ ' . date('d/m/Y', strtotime($filters['start_date'])) . ' ถึง ' . date('d/m/Y', strtotime($filters['end_date']));
                }
                break;
        }
    }
    
    $sheet->setCellValue('A2', $reportTitle);
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getFont()->setSize(14);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    
    // ตั้งค่า header columns
    $sheet->setCellValue('A4', 'ลำดับ');
    $sheet->setCellValue('B4', 'รหัสนักเรียน');
    $sheet->setCellValue('C4', 'ชื่อ-นามสกุล');
    $sheet->setCellValue('D4', 'ชั้น/ห้อง');
    $sheet->setCellValue('E4', 'แผนกวิชา');
    $sheet->setCellValue('F4', 'วันที่เข้าแถว');
    $sheet->setCellValue('G4', 'อัตราการเข้าแถว (%)');
    $sheet->setCellValue('H4', 'สถานะ');
    
    $sheet->getStyle('A4:H4')->getFont()->setBold(true);
    $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A4:H4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
    
    // ใส่ข้อมูล
    $row = 5;
    $counter = 1;
    
    foreach ($data as $student) {
        $attendanceRate = $student['attendance_rate'];
        
        // กำหนดสถานะตามอัตราการเข้าแถว
        $status = 'ปกติ';
        if ($attendanceRate < 50) {
            $status = 'ตกกิจกรรม';
        } elseif ($attendanceRate < 70) {
            $status = 'เสี่ยงตกกิจกรรม';
        } elseif ($attendanceRate < 80) {
            $status = 'ต้องระวัง';
        }
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, $student['student_code']);
        $sheet->setCellValue('C' . $row, $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']);
        $sheet->setCellValue('D' . $row, $student['level'] . '/' . $student['group_number']);
        $sheet->setCellValue('E' . $row, $student['department_name']);
        $sheet->setCellValue('F' . $row, $student['present_count']);
        $sheet->setCellValue('G' . $row, $attendanceRate);
        $sheet->setCellValue('H' . $row, $status);
        
        // จัดรูปแบบสีสถานะ
        if ($status == 'ตกกิจกรรม') {
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FF0000');
        } elseif ($status == 'เสี่ยงตกกิจกรรม') {
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FF9900');
        } elseif ($status == 'ต้องระวัง') {
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FFCC00');
        }
        
        $row++;
        $counter++;
    }
    
    // ปรับขนาดคอลัมน์อัตโนมัติ
    foreach (range('A', 'H') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // สร้างไฟล์ Excel
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $filename = 'รายงานการเข้าแถว_' . date('Y-m-d_His') . '.xlsx';
    
    // ตั้งค่า header สำหรับการดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // ส่งไฟล์ไปยัง browser
    $writer->save('php://output');
    exit;
}

// ฟังก์ชันสร้างไฟล์ Excel รายงานนักเรียนรายบุคคล
function generateStudentExcel($data) {
    // สร้าง instance ของ PHPExcel
    $excel = new PHPExcel();
    
    // ตั้งค่าเอกสาร
    $excel->getProperties()
        ->setCreator("ระบบน้องสัตบรรณ")
        ->setLastModifiedBy("ระบบน้องสัตบรรณ")
        ->setTitle("รายงานการเข้าแถวนักเรียนรายบุคคล")
        ->setSubject("รายงานการเข้าแถวนักเรียนรายบุคคล")
        ->setDescription("รายงานการเข้าแถวนักเรียนรายบุคคล");
    
    // รับ active sheet
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle("รายงานนักเรียนรายบุคคล");
    
    // ตั้งค่า header
    $sheet->setCellValue('A1', 'รายงานการเข้าแถวนักเรียนรายบุคคล');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    
    // ข้อมูลนักเรียน
    $sheet->setCellValue('A3', 'ข้อมูลนักเรียน:');
    $sheet->getStyle('A3')->getFont()->setBold(true);
    
    $sheet->setCellValue('A4', 'รหัสนักเรียน:');
    $sheet->setCellValue('B4', $data['profile']['student_code']);
    $sheet->setCellValue('A5', 'ชื่อ-นามสกุล:');
    $sheet->setCellValue('B5', $data['profile']['name']);
    $sheet->setCellValue('A6', 'ชั้น/ห้อง:');
    $sheet->setCellValue('B6', $data['profile']['class']);
    $sheet->setCellValue('A7', 'แผนกวิชา:');
    $sheet->setCellValue('B7', $data['profile']['department']);
    $sheet->setCellValue('A8', 'ครูที่ปรึกษา:');
    $sheet->setCellValue('B8', $data['profile']['advisor']);
    
    // สรุปการเข้าแถว
    $sheet->setCellValue('D4', 'วันที่เข้าแถวทั้งหมด:');
    $sheet->setCellValue('E4', $data['statistics']['present_days'] . ' วัน');
    $sheet->setCellValue('D5', 'วันที่ขาดแถว:');
    $sheet->setCellValue('E5', $data['statistics']['absent_days'] . ' วัน');
    $sheet->setCellValue('D6', 'วันที่มาสาย:');
    $sheet->setCellValue('E6', $data['statistics']['late_days'] . ' วัน');
    $sheet->setCellValue('D7', 'วันที่ลา:');
    $sheet->setCellValue('E7', $data['statistics']['leave_days'] . ' วัน');
    $sheet->setCellValue('D8', 'อัตราการเข้าแถว:');
    $sheet->setCellValue('E8', $data['statistics']['attendance_rate'] . '%');
    
    // กำหนดสีตามอัตราการเข้าแถว
    if ($data['statistics']['attendance_rate'] < 70) {
        $sheet->getStyle('E8')->getFont()->getColor()->setRGB('FF0000');
    } elseif ($data['statistics']['attendance_rate'] < 80) {
        $sheet->getStyle('E8')->getFont()->getColor()->setRGB('FF9900');
    } else {
        $sheet->getStyle('E8')->getFont()->getColor()->setRGB('00AA00');
    }
    
    // ตั้งค่า header ของประวัติการเข้าแถว
    $sheet->setCellValue('A10', 'ประวัติการเข้าแถวรายวัน:');
    $sheet->getStyle('A10')->getFont()->setBold(true);
    
    $sheet->setCellValue('A11', 'ลำดับ');
    $sheet->setCellValue('B11', 'วันที่');
    $sheet->setCellValue('C11', 'สถานะ');
    $sheet->setCellValue('D11', 'เวลา');
    $sheet->setCellValue('E11', 'วิธีการเช็ค');
    $sheet->setCellValue('F11', 'หมายเหตุ');
    
    $sheet->getStyle('A11:F11')->getFont()->setBold(true);
    $sheet->getStyle('A11:F11')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A11:F11')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
    
    // ใส่ข้อมูลประวัติการเข้าแถว
    $row = 12;
    $counter = 1;
    
    foreach ($data['attendance'] as $record) {
        $status = $record['attendance_status'];
        
        // แปลงสถานะให้เป็นภาษาไทย
        switch ($status) {
            case 'present':
                $statusText = 'มา';
                break;
            case 'absent':
                $statusText = 'ขาด';
                break;
            case 'late':
                $statusText = 'มาสาย';
                break;
            case 'leave':
                $statusText = 'ลา';
                break;
            default:
                $statusText = $status;
                break;
        }
        
        // แปลงวิธีการเช็คให้เป็นภาษาไทย
        $checkMethod = $record['check_method'];
        switch ($checkMethod) {
            case 'GPS':
                $methodText = 'พิกัด GPS';
                break;
            case 'QR_Code':
                $methodText = 'สแกน QR Code';
                break;
            case 'PIN':
                $methodText = 'รหัส PIN';
                break;
            case 'Manual':
                $methodText = 'เช็คด้วยครู';
                break;
            default:
                $methodText = $checkMethod;
                break;
        }
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($record['date'])));
        $sheet->setCellValue('C' . $row, $statusText);
        $sheet->setCellValue('D' . $row, $record['check_time']);
        $sheet->setCellValue('E' . $row, $methodText);
        $sheet->setCellValue('F' . $row, $record['remarks'] ?: '-');
        
        // จัดรูปแบบสีสถานะ
        if ($statusText == 'ขาด') {
            $sheet->getStyle('C' . $row)->getFont()->getColor()->setRGB('FF0000');
        } elseif ($statusText == 'มาสาย') {
            $sheet->getStyle('C' . $row)->getFont()->getColor()->setRGB('FF9900');
        } elseif ($statusText == 'ลา') {
            $sheet->getStyle('C' . $row)->getFont()->getColor()->setRGB('0000FF');
        } else {
            $sheet->getStyle('C' . $row)->getFont()->getColor()->setRGB('00AA00');
        }
        
        $row++;
        $counter++;
    }
    
    // ปรับขนาดคอลัมน์อัตโนมัติ
    foreach (range('A', 'F') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // สร้างไฟล์ Excel
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $filename = 'รายงานนักเรียน_' . $data['profile']['student_code'] . '_' . date('Y-m-d_His') . '.xlsx';
    
    // ตั้งค่า header สำหรับการดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // ส่งไฟล์ไปยัง browser
    $writer->save('php://output');
    exit;
}

// รับพารามิเตอร์
$reportType = $_GET['type'] ?? 'all';
$studentId = $_GET['student_id'] ?? null;

// รับตัวกรองจาก URL
$filters = [];
$possibleFilters = ['academic_year_id', 'period', 'start_date', 'end_date', 'department_id', 'level', 'class_id'];

foreach ($possibleFilters as $filter) {
    if (isset($_GET[$filter]) && !empty($_GET[$filter])) {
        $filters[$filter] = $_GET[$filter];
    }
}

// ดำเนินการตามประเภทรายงาน
if ($reportType === 'student' && $studentId) {
    // ดึงข้อมูลนักเรียนรายบุคคล
    $data = getStudentReport($studentId, $filters);
    
    if ($data) {
        generateStudentExcel($data);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
} else {
    // ดึงข้อมูลรายงานทั่วไป
    $data = getGeneralReport($filters);
    generateGeneralExcel($data, $filters);
}
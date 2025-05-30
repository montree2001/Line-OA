<?php
/**
 * at_risk_export.php - API สำหรับดาวน์โหลดรายงานนักเรียนเสี่ยงตกกิจกรรมในรูปแบบ Excel
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */


// เริ่ม session
session_start();
/* แสดงผล Error */
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ตรวจสอบว่า session ถูกตั้งค่าแล้วหรือไม่

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

// กำหนดให้ไม่จำกัดเวลาการทำงาน
set_time_limit(0);

// ล้าง output buffer ทั้งหมด
ob_end_clean();
// เริ่ม buffer ใหม่
ob_start();

// นำเข้าไฟล์ที่จำเป็น
require_once '../../../db_connect.php';

// ตรวจสอบว่ามี Composer และ PhpSpreadsheet หรือไม่
if (!file_exists('../../../vendor/autoload.php')) {
    // ถ้าไม่มี Composer จะใช้ไลบรารี PHPExcel เดิมแทน (ถ้ามี)
    if (file_exists('../../includes/PHPExcel/PHPExcel.php')) {
        require_once '../../includes/PHPExcel/PHPExcel.php';
        require_once '../../includes/PHPExcel/PHPExcel/IOFactory.php';
        $useOldLibrary = true;
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบไลบรารีสำหรับสร้าง Excel']);
        exit;
    }
} else {
    require_once '../../../vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    $useOldLibrary = false;
}

// รับพารามิเตอร์
$department_id = isset($_POST['department_id']) ? $_POST['department_id'] : '';
$class_level = isset($_POST['class_level']) ? $_POST['class_level'] : '';
$class_room = isset($_POST['class_room']) ? $_POST['class_room'] : '';
$advisor = isset($_POST['advisor']) ? $_POST['advisor'] : '';
$min_attendance = isset($_POST['min_attendance']) ? $_POST['min_attendance'] : '';
$max_attendance = isset($_POST['max_attendance']) ? $_POST['max_attendance'] : '';
$academic_year_id = isset($_POST['academic_year_id']) ? $_POST['academic_year_id'] : '1'; // ค่าเริ่มต้น = 1

try {
    $db = getDB();
    
    // สร้างเงื่อนไขการค้นหา
    $where_conditions = ["sar.academic_year_id = :academic_year_id"];
    $params = [':academic_year_id' => $academic_year_id];
    
    // เพิ่มเงื่อนไขการกรองข้อมูล
    if (!empty($department_id)) {
        $where_conditions[] = "d.department_id = :department_id";
        $params[':department_id'] = $department_id;
    }
    
    if (!empty($class_level)) {
        $where_conditions[] = "c.level = :class_level";
        $params[':class_level'] = $class_level;
    }
    
    if (!empty($class_room)) {
        $where_conditions[] = "c.group_number = :class_room";
        $params[':class_room'] = $class_room;
    }
    
    if (!empty($advisor)) {
        $where_conditions[] = "t.teacher_id = :advisor";
        $params[':advisor'] = $advisor;
    }
    
    if (!empty($min_attendance) && !empty($max_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN :min_attendance AND :max_attendance";
        $params[':min_attendance'] = $min_attendance;
        $params[':max_attendance'] = $max_attendance;
    } elseif (!empty($min_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 >= :min_attendance";
        $params[':min_attendance'] = $min_attendance;
    } elseif (!empty($max_attendance)) {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 <= :max_attendance";
        $params[':max_attendance'] = $max_attendance;
    }
    
    // เพิ่มเงื่อนไขนักเรียนที่เสี่ยงตกกิจกรรม
    $where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80";
    $where_conditions[] = "s.status = 'กำลังศึกษา'";
    
    // รวมเงื่อนไข SQL
    $where_clause = implode(" AND ", $where_conditions);
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, 
           c.level, c.group_number, d.department_name,
           sar.total_attendance_days, sar.total_absence_days,
           CONCAT(tu.first_name, ' ', tu.last_name) as advisor_name,
           tu.phone_number as advisor_phone,
           (SELECT COUNT(*) FROM notifications n 
            WHERE n.related_student_id = s.student_id AND n.type = 'risk_alert') as notification_count,
           (SELECT MAX(n.created_at) FROM notifications n 
            WHERE n.related_student_id = s.student_id AND n.type = 'risk_alert') as last_notification
           FROM students s
           JOIN users u ON s.user_id = u.user_id
           JOIN student_academic_records sar ON s.student_id = sar.student_id
           JOIN classes c ON s.current_class_id = c.class_id
           JOIN departments d ON c.department_id = d.department_id
           LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
           LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
           LEFT JOIN users tu ON t.user_id = tu.user_id
           WHERE $where_clause
           ORDER BY (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $sql_year = "SELECT year, semester FROM academic_years WHERE academic_year_id = :academic_year_id";
    $stmt_year = $db->prepare($sql_year);
    $stmt_year->execute([':academic_year_id' => $academic_year_id]);
    $academic_year = $stmt_year->fetch(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลวิทยาลัย
    $sql_settings = "SELECT setting_value FROM system_settings WHERE setting_key = 'school_name'";
    $stmt_settings = $db->prepare($sql_settings);
    $stmt_settings->execute();
    $school_name = $stmt_settings->fetchColumn() ?: 'วิทยาลัยการอาชีพปราสาท';
    
    // =========================================
    // สร้างไฟล์ Excel
    // =========================================
    
    // ตรวจสอบว่าใช้ PhpSpreadsheet หรือ PHPExcel
    if (!$useOldLibrary) {
        // ใช้ PhpSpreadsheet (ใหม่)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // ตั้งค่าหัวเรื่อง
        $sheet->setCellValue('A1', $school_name);
        $sheet->setCellValue('A2', 'รายงานนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว');
        $sheet->setCellValue('A3', "ปีการศึกษา {$academic_year['year']} ภาคเรียนที่ {$academic_year['semester']}");
        $sheet->setCellValue('A4', "วันที่พิมพ์: " . date('d/m/Y H:i'));
        
        // จัดรูปแบบหัวเรื่อง
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        
        // กำหนดหัวตาราง
        $sheet->setCellValue('A6', 'ลำดับ');
        $sheet->setCellValue('B6', 'รหัสนักเรียน');
        $sheet->setCellValue('C6', 'ชื่อ-นามสกุล');
        $sheet->setCellValue('D6', 'ชั้น/กลุ่ม');
        $sheet->setCellValue('E6', 'แผนกวิชา');
        $sheet->setCellValue('F6', 'อัตราการเข้าแถว (%)');
        $sheet->setCellValue('G6', 'จำนวนวันที่มา');
        $sheet->setCellValue('H6', 'จำนวนวันที่ขาด');
        $sheet->setCellValue('I6', 'ครูที่ปรึกษา');
        $sheet->setCellValue('J6', 'เบอร์โทรครู');
        $sheet->setCellValue('K6', 'สถานะการแจ้งเตือน');
        
        // จัดรูปแบบหัวตาราง
        $sheet->getStyle('A6:K6')->getFont()->setBold(true);
        $sheet->getStyle('A6:K6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
        $sheet->getStyle('A6:K6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:K6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // เพิ่มข้อมูลนักเรียน
        $row = 7;
        foreach ($students as $i => $student) {
            $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
            $attendance_rate = $total_days > 0 ? 
                ($student['total_attendance_days'] / $total_days) * 100 : 0;
            
            $notification_status = 'ยังไม่แจ้ง';
            if ($student['notification_count'] > 0) {
                $notification_status = 'แจ้งแล้ว ' . $student['notification_count'] . ' ครั้ง';
                if ($student['last_notification']) {
                    $notification_status .= ' (ล่าสุด: ' . date('d/m/Y', strtotime($student['last_notification'])) . ')';
                }
            }
            
            // ชื่อเต็ม
            $full_name = $student['first_name'] . ' ' . $student['last_name'];
            
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $student['student_code']);
            $sheet->setCellValue('C' . $row, $full_name);
            $sheet->setCellValue('D' . $row, $student['level'] . '/' . $student['group_number']);
            $sheet->setCellValue('E' . $row, $student['department_name']);
            $sheet->setCellValue('F' . $row, round($attendance_rate, 1));
            $sheet->setCellValue('G' . $row, $student['total_attendance_days']);
            $sheet->setCellValue('H' . $row, $student['total_absence_days']);
            $sheet->setCellValue('I' . $row, $student['advisor_name'] ?: 'ไม่ระบุ');
            $sheet->setCellValue('J' . $row, $student['advisor_phone'] ?: 'ไม่ระบุ');
            $sheet->setCellValue('K' . $row, $notification_status);
            
            $row++;
        }
        
        // จัดรูปแบบข้อมูล
        $lastRow = $row - 1;
        $sheet->getStyle("A7:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A7:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B7:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D7:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F7:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // เพิ่มสรุปข้อมูล
        $row = $lastRow + 2;
        $sheet->setCellValue('A' . $row, 'สรุปรายงาน:');
        $sheet->setCellValue('A' . ($row+1), 'จำนวนนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมด:');
        $sheet->setCellValue('C' . ($row+1), count($students) . ' คน');
        
        // ปรับขนาดคอลัมน์อัตโนมัติ
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // ตั้งชื่อชีท
        $sheet->setTitle('นักเรียนเสี่ยงตกกิจกรรม');
        
        // ล้าง output buffer ทั้งหมดอีกครั้ง
        ob_end_clean();
        
        // สร้างไฟล์ชั่วคราว
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($temp_file);
        
        // อ่านไฟล์และส่งกลับ
        $file_content = file_get_contents($temp_file);
        
        // ลบไฟล์ชั่วคราว
        @unlink($temp_file);
        
    } else {
        // ใช้ PHPExcel (เก่า)
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setTitle("รายงานนักเรียนเสี่ยงตกกิจกรรม");
        $objPHPExcel->getProperties()->setSubject("รายงานนักเรียนเสี่ยงตกกิจกรรม");
        $objPHPExcel->getProperties()->setDescription("รายงานนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว");
        
        $sheet = $objPHPExcel->getActiveSheet();
        
        // ตั้งค่าหัวเรื่อง
        $sheet->setCellValue('A1', $school_name);
        $sheet->setCellValue('A2', 'รายงานนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว');
        $sheet->setCellValue('A3', "ปีการศึกษา {$academic_year['year']} ภาคเรียนที่ {$academic_year['semester']}");
        $sheet->setCellValue('A4', "วันที่พิมพ์: " . date('d/m/Y H:i'));
        
        // จัดรูปแบบหัวเรื่อง
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        
        $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        
        // กำหนดหัวตาราง
        $sheet->setCellValue('A6', 'ลำดับ');
        $sheet->setCellValue('B6', 'รหัสนักเรียน');
        $sheet->setCellValue('C6', 'ชื่อ-นามสกุล');
        $sheet->setCellValue('D6', 'ชั้น/กลุ่ม');
        $sheet->setCellValue('E6', 'แผนกวิชา');
        $sheet->setCellValue('F6', 'อัตราการเข้าแถว (%)');
        $sheet->setCellValue('G6', 'จำนวนวันที่มา');
        $sheet->setCellValue('H6', 'จำนวนวันที่ขาด');
        $sheet->setCellValue('I6', 'ครูที่ปรึกษา');
        $sheet->setCellValue('J6', 'เบอร์โทรครู');
        $sheet->setCellValue('K6', 'สถานะการแจ้งเตือน');
        
        // จัดรูปแบบหัวตาราง
        $sheet->getStyle('A6:K6')->getFont()->setBold(true);
        $sheet->getStyle('A6:K6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
        $sheet->getStyle('A6:K6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:K6')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        
        // เพิ่มข้อมูลนักเรียน
        $row = 7;
        foreach ($students as $i => $student) {
            $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
            $attendance_rate = $total_days > 0 ? 
                ($student['total_attendance_days'] / $total_days) * 100 : 0;
            
            $notification_status = 'ยังไม่แจ้ง';
            if ($student['notification_count'] > 0) {
                $notification_status = 'แจ้งแล้ว ' . $student['notification_count'] . ' ครั้ง';
                if ($student['last_notification']) {
                    $notification_status .= ' (ล่าสุด: ' . date('d/m/Y', strtotime($student['last_notification'])) . ')';
                }
            }
            
            // ชื่อเต็ม
            $full_name = $student['first_name'] . ' ' . $student['last_name'];
            
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $student['student_code']);
            $sheet->setCellValue('C' . $row, $full_name);
            $sheet->setCellValue('D' . $row, $student['level'] . '/' . $student['group_number']);
            $sheet->setCellValue('E' . $row, $student['department_name']);
            $sheet->setCellValue('F' . $row, round($attendance_rate, 1));
            $sheet->setCellValue('G' . $row, $student['total_attendance_days']);
            $sheet->setCellValue('H' . $row, $student['total_absence_days']);
            $sheet->setCellValue('I' . $row, $student['advisor_name'] ?: 'ไม่ระบุ');
            $sheet->setCellValue('J' . $row, $student['advisor_phone'] ?: 'ไม่ระบุ');
            $sheet->setCellValue('K' . $row, $notification_status);
            
            $row++;
        }
        
        // จัดรูปแบบข้อมูล
        $lastRow = $row - 1;
        $sheet->getStyle("A7:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $sheet->getStyle("A7:A{$lastRow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B7:B{$lastRow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D7:D{$lastRow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F7:H{$lastRow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        
        // เพิ่มสรุปข้อมูล
        $row = $lastRow + 2;
        $sheet->setCellValue('A' . $row, 'สรุปรายงาน:');
        $sheet->setCellValue('A' . ($row+1), 'จำนวนนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมด:');
        $sheet->setCellValue('C' . ($row+1), count($students) . ' คน');
        
        // ปรับขนาดคอลัมน์อัตโนมัติ
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // ตั้งชื่อชีท
        $sheet->setTitle('นักเรียนเสี่ยงตกกิจกรรม');
        
        // ล้าง output buffer ทั้งหมดอีกครั้ง
        ob_end_clean();
        
        // สร้างไฟล์ชั่วคราว
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($temp_file);
        
        // อ่านไฟล์และส่งกลับ
        $file_content = file_get_contents($temp_file);
        
        // ลบไฟล์ชั่วคราว
        @unlink($temp_file);
    }
    
    // กำหนด headers สำหรับการดาวน์โหลด
    $filename = 'รายงานนักเรียนเสี่ยงตกกิจกรรม-' . date('Y-m-d') . '.xlsx';
    
    // ล้าง output buffer ทั้งหมด
    if (ob_get_length()) ob_end_clean();
    
    // ส่ง headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . strlen($file_content));
    
    // ส่งไฟล์
    echo $file_content;
    exit;
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด ให้ส่ง error กลับไป
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
    exit;
}
<?php
/**
 * api/download_report.php - API สำหรับดาวน์โหลดรายงานการเช็คชื่อ
 * 
 * รับข้อมูล GET:
 * - class_id (int) - รหัสห้องเรียน
 * - date (YYYY-MM-DD) - วันที่ต้องการดูรายงาน (ถ้าไม่ระบุจะใช้วันปัจจุบัน)
 * - period (day|week|month) - ช่วงเวลาของรายงาน (ถ้าไม่ระบุจะใช้ day)
 * 
 * ผลลัพธ์:
 * - ไฟล์ Excel สำหรับดาวน์โหลด
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสิทธิ์ในการดาวน์โหลดรายงาน'
    ]);
    exit;
}

// ตรวจสอบว่ามีการระบุ class_id หรือไม่
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัสห้องเรียน'
    ]);
    exit;
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ต้องติดตั้ง PhpSpreadsheet ก่อนใช้งาน
// composer require phpoffice/phpspreadsheet
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // เก็บค่าพารามิเตอร์
    $class_id = intval($_GET['class_id']);
    $check_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $period = isset($_GET['period']) ? $_GET['period'] : 'day';
    
    // ตรวจสอบสิทธิ์ในการดูข้อมูลห้องเรียนนี้ (กรณีเป็นครู)
    if ($_SESSION['role'] === 'teacher') {
        $user_id = $_SESSION['user_id'];
        $check_permission_query = "SELECT ca.class_id 
                                  FROM class_advisors ca 
                                  JOIN teachers t ON ca.teacher_id = t.teacher_id 
                                  WHERE t.user_id = :user_id AND ca.class_id = :class_id";
        
        $stmt = $db->prepare($check_permission_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('คุณไม่มีสิทธิ์ในการดูข้อมูลห้องเรียนนี้');
        }
    }
    
    // ดึงข้อมูลห้องเรียน
    $class_query = "SELECT c.level, d.department_name, c.group_number, ay.year, ay.semester
                   FROM classes c
                   JOIN departments d ON c.department_id = d.department_id
                   JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                   WHERE c.class_id = :class_id";
    
    $stmt = $db->prepare($class_query);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('ไม่พบข้อมูลห้องเรียน');
    }
    
    $class_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_name = $class_data['level'] . '/' . $class_data['department_name'] . '/' . $class_data['group_number'];
    $academic_year = $class_data['year'];
    $semester = $class_data['semester'];
    
    // กำหนดช่วงวันที่ตามพารามิเตอร์ period
    $start_date = $check_date;
    $end_date = $check_date;
    
    switch ($period) {
        case 'week':
            // หาวันจันทร์และวันอาทิตย์ของสัปดาห์ที่วันที่ระบุอยู่
            $dayOfWeek = date('N', strtotime($check_date));
            $start_date = date('Y-m-d', strtotime("-" . ($dayOfWeek - 1) . " days", strtotime($check_date)));
            $end_date = date('Y-m-d', strtotime("+" . (7 - $dayOfWeek) . " days", strtotime($check_date)));
            break;
        case 'month':
            // หาวันแรกและวันสุดท้ายของเดือนที่วันที่ระบุอยู่
            $start_date = date('Y-m-01', strtotime($check_date));
            $end_date = date('Y-m-t', strtotime($check_date));
            break;
    }
    
    // ดึงรายชื่อนักเรียนในห้องเรียน
    $students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name
                      FROM students s
                      JOIN users u ON s.user_id = u.user_id
                      WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                      ORDER BY s.student_code";
    
    $stmt = $db->prepare($students_query);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) === 0) {
        throw new Exception('ไม่พบข้อมูลนักเรียนในห้องเรียนนี้');
    }
    
    // ดึงข้อมูลวันที่มีการเช็คชื่อในช่วงที่ระบุ
    $dates_query = "SELECT DISTINCT date
                   FROM attendance
                   WHERE date BETWEEN :start_date AND :end_date
                   AND student_id IN (SELECT student_id FROM students WHERE current_class_id = :class_id)
                   ORDER BY date";
    
    $stmt = $db->prepare($dates_query);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
    $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $attendance_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ถ้าไม่มีข้อมูลการเช็คชื่อ ให้ใช้เฉพาะวันที่ระบุ
    if (count($attendance_dates) === 0) {
        $attendance_dates = [$check_date];
    }
    
    // ดึงข้อมูลการเช็คชื่อของนักเรียนในช่วงที่ระบุ
    $attendance_query = "SELECT a.student_id, a.date, a.attendance_status, a.remarks
                        FROM attendance a
                        WHERE a.date BETWEEN :start_date AND :end_date
                        AND a.student_id IN (SELECT student_id FROM students WHERE current_class_id = :class_id)
                        ORDER BY a.date, a.student_id";
    
    $stmt = $db->prepare($attendance_query);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
    $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูลการเช็คชื่อให้เป็น Array 2 มิติโดยมี [student_id][date] => status
    $attendance_map = [];
    foreach ($attendance_data as $data) {
        $attendance_map[$data['student_id']][$data['date']] = [
            'status' => $data['attendance_status'],
            'remarks' => $data['remarks']
        ];
    }
    
    // สร้างไฟล์ Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // กำหนดข้อมูลทั่วไป
    $sheet->setCellValue('A1', 'รายงานการเช็คชื่อนักเรียน');
    $sheet->setCellValue('A2', 'ห้องเรียน: ' . $class_name);
    $sheet->setCellValue('A3', 'ปีการศึกษา: ' . $academic_year . ' ภาคเรียนที่ ' . $semester);
    
    // กำหนดวันที่
    if ($period === 'day') {
        // กรณีรายวัน
        $thai_month_names = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        $date_parts = explode('-', $check_date);
        $day = intval($date_parts[2]);
        $month = intval($date_parts[1]);
        $year = intval($date_parts[0]) + 543; // แปลงเป็น พ.ศ.
        
        $thai_date = $day . ' ' . $thai_month_names[$month] . ' ' . $year;
        $sheet->setCellValue('A4', 'วันที่: ' . $thai_date);
    } else {
        // กรณีรายสัปดาห์หรือรายเดือน
        $start_parts = explode('-', $start_date);
        $end_parts = explode('-', $end_date);
        
        $start_day = intval($start_parts[2]);
        $start_month = intval($start_parts[1]);
        $start_year = intval($start_parts[0]) + 543;
        
        $end_day = intval($end_parts[2]);
        $end_month = intval($end_parts[1]);
        $end_year = intval($end_parts[0]) + 543;
        
        $thai_month_names = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
            7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];
        
        $thai_start_date = $start_day . ' ' . $thai_month_names[$start_month] . ' ' . $start_year;
        $thai_end_date = $end_day . ' ' . $thai_month_names[$end_month] . ' ' . $end_year;
        
        $sheet->setCellValue('A4', 'ช่วงวันที่: ' . $thai_start_date . ' - ' . $thai_end_date);
    }
    
    // จัดรูปแบบหัวข้อ
    $sheet->getStyle('A1:A4')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setSize(16);
    
    // กำหนดหัวตาราง
    $sheet->setCellValue('A6', 'ลำดับ');
    $sheet->setCellValue('B6', 'รหัสนักเรียน');
    $sheet->setCellValue('C6', 'ชื่อ-นามสกุล');
    
    // กำหนดหัวตารางวันที่
    $col = 'D';
    foreach ($attendance_dates as $index => $date) {
        $date_parts = explode('-', $date);
        $day = intval($date_parts[2]);
        $month = intval($date_parts[1]);
        
        // แสดงวันที่แบบ วัน/เดือน
        $sheet->setCellValue($col . '6', $day . '/' . $month);
        $col++;
    }
    
    // กำหนดคอลัมน์สรุป
    $sheet->setCellValue($col . '6', 'มา');
    $col++;
    $sheet->setCellValue($col . '6', 'สาย');
    $col++;
    $sheet->setCellValue($col . '6', 'ลา');
    $col++;
    $sheet->setCellValue($col . '6', 'ขาด');
    $last_col = $col;
    
    // ตั้งค่าการจัดวางและกรอบ
    $header_range = 'A6:' . $last_col . '6';
    $sheet->getStyle($header_range)->getFont()->setBold(true);
    $sheet->getStyle($header_range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($header_range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle($header_range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle($header_range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
    
    // กำหนดข้อมูลนักเรียน
    $row = 7;
    foreach ($students as $index => $student) {
        $sheet->setCellValue('A' . $row, $index + 1);
        $sheet->setCellValue('B' . $row, $student['student_code']);
        $sheet->setCellValue('C' . $row, $student['title'] . $student['first_name'] . ' ' . $student['last_name']);
        
        // กำหนดสถานะการเช็คชื่อ
        $col = 'D';
        $present_count = 0;
        $late_count = 0;
        $leave_count = 0;
        $absent_count = 0;
        
        foreach ($attendance_dates as $date) {
            $status = isset($attendance_map[$student['student_id']][$date]) ? $attendance_map[$student['student_id']][$date]['status'] : '';
            
            // กำหนดค่าในเซลล์
            switch ($status) {
                case 'present':
                    $sheet->setCellValue($col . $row, '✓');
                    $present_count++;
                    break;
                case 'late':
                    $sheet->setCellValue($col . $row, 'ส');
                    $late_count++;
                    break;
                case 'leave':
                    $sheet->setCellValue($col . $row, 'ล');
                    $leave_count++;
                    break;
                case 'absent':
                    $sheet->setCellValue($col . $row, 'ข');
                    $absent_count++;
                    break;
                default:
                    $sheet->setCellValue($col . $row, '');
            }
            
            // เพิ่ม Comment ถ้ามีหมายเหตุ
            if (isset($attendance_map[$student['student_id']][$date]) && !empty($attendance_map[$student['student_id']][$date]['remarks'])) {
                $remarks = $attendance_map[$student['student_id']][$date]['remarks'];
                $comment = $sheet->getComment($col . $row);
                
                if (!$comment) {
                    $comment = $sheet->getCell($col . $row)->getComment();
                }
                
                $comment->getText()->createTextRun('หมายเหตุ: ' . $remarks);
            }
            
            // จัดตำแหน่งข้อความให้อยู่ตรงกลาง
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $col++;
        }
        
        // กำหนดจำนวนสถานะ
        $sheet->setCellValue($col . $row, $present_count);
        $col++;
        $sheet->setCellValue($col . $row, $late_count);
        $col++;
        $sheet->setCellValue($col . $row, $leave_count);
        $col++;
        $sheet->setCellValue($col . $row, $absent_count);
        
        $row++;
    }
    
    // ตั้งค่าขนาดคอลัมน์
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(30);
    
    // ตั้งค่าขนาดของคอลัมน์วันที่และสรุป
    $col = 'D';
    foreach ($attendance_dates as $date) {
        $sheet->getColumnDimension($col)->setWidth(6);
        $col++;
    }
    
    // ตั้งค่าขนาดคอลัมน์สรุป
    $sheet->getColumnDimension($col)->setWidth(6);
    $col++;
    $sheet->getColumnDimension($col)->setWidth(6);
    $col++;
    $sheet->getColumnDimension($col)->setWidth(6);
    $col++;
    $sheet->getColumnDimension($col)->setWidth(6);
    
    // ตั้งค่ากรอบตาราง
    $data_range = 'A7:' . $last_col . ($row - 1);
    $sheet->getStyle($data_range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // จัดตำแหน่งข้อความในคอลัมน์ลำดับและรหัสนักเรียนให้อยู่ตรงกลาง
    $sheet->getStyle('A7:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // จัดตำแหน่งข้อความในคอลัมน์สรุปให้อยู่ตรงกลาง
    $summary_col = chr(ord('D') + count($attendance_dates));
    $sheet->getStyle($summary_col . '7:' . $last_col . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // กำหนดชื่อไฟล์
    if ($period === 'day') {
        $filename = 'รายงานการเช็คชื่อ_' . $class_name . '_' . $check_date . '.xlsx';
    } else {
        $filename = 'รายงานการเช็คชื่อ_' . $class_name . '_' . $start_date . '_ถึง_' . $end_date . '.xlsx';
    }
    
    // แทนที่เครื่องหมายที่ไม่อนุญาตในชื่อไฟล์
    $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $filename);
    
    // กำหนด header สำหรับการดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // สร้างไฟล์ Excel และส่งออก
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // แสดงข้อผิดพลาด
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
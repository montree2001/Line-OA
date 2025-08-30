<?php
/**
 * get_student_activity_result.php - AJAX endpoint สำหรับดึงผลการตัดกิจกรรมของนักเรียน
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../db_connect.php";

$student_id = $_POST["student_id"] ?? "";

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสนักเรียน']);
    exit;
}

try {
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
    $query = "SELECT 
                s.student_id,
                s.student_code,
                CASE WHEN u.title IS NULL THEN s.title ELSE u.title END as title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              WHERE s.student_id = ? AND s.status = 'กำลังศึกษา'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนักเรียน']);
        exit;
    }
    
    // ดึงข้อมูลปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester, start_date, end_date 
              FROM academic_years 
              WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลปีการศึกษา']);
        exit;
    }
    
    // กำหนดช่วงวันที่สำหรับการค้นหา
    $start_date = $academic_year['start_date'];
    $end_date = $academic_year['end_date'];
    
    // ถ้าวันที่สิ้นสุดยังไม่ถึง ใช้วันที่ปัจจุบัน
    $current_date = date('Y-m-d');
    if ($current_date < $end_date) {
        $end_date = $current_date;
    }
    
    // ดึงข้อมูลการเข้าแถว
    $query = "SELECT date, attendance_status
              FROM attendance
              WHERE student_id = ? AND date BETWEEN ? AND ?
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบข้อมูลการเข้าแถว
    $attendance_data = [];
    foreach ($attendance_records as $record) {
        $attendance_data[$record['date']] = $record['attendance_status'];
    }
    
    // ดึงข้อมูลวันหยุด
    $holidays = [];
    try {
        $query = "SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $holiday_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($holiday_records as $holiday) {
            $holidays[$holiday['holiday_date']] = true;
        }
    } catch (Exception $e) {
        // ถ้าไม่มีตาราง holidays
        $holidays = [];
    }
    
    // สร้างรายการวันที่ในช่วงเวลาทั้งหมด
    $period = new DatePeriod(
        new DateTime($start_date),
        new DateInterval('P1D'),
        (new DateTime($end_date))->modify('+1 day')
    );
    
    // คำนวณสถิติและสร้างปฏิทิน
    $total_present = 0;
    $total_late = 0;
    $total_absent = 0;
    $total_study_days = 0;
    $calendar = [];
    $weekly_data = [];
    
    $current_week = [];
    $week_index = 0;
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $day_of_week = (int)$date->format('w'); // 0=Sunday, 6=Saturday
        
        $day_data = [
            'is_holiday' => isset($holidays[$date_str]),
            'status' => null
        ];
        
        // เฉพาะวันจันทร์ - ศุกร์ที่ไม่ใช่วันหยุด
        if ($day_of_week >= 1 && $day_of_week <= 5 && !isset($holidays[$date_str])) {
            $total_study_days++;
            
            if (isset($attendance_data[$date_str])) {
                $status = $attendance_data[$date_str];
                $day_data['status'] = $status;
                
                switch ($status) {
                    case 'present':
                        $total_present++;
                        break;
                    case 'late':
                        $total_late++;
                        break;
                    default: // absent
                        $total_absent++;
                        break;
                }
            } else {
                $day_data['status'] = 'absent';
                $total_absent++;
            }
            
            // เก็บข้อมูลรายสัปดาห์
            if ($day_of_week == 1 || empty($current_week)) { // วันจันทร์หรือสัปดาห์แรก
                if (!empty($current_week)) {
                    $weekly_data[] = $current_week;
                }
                $current_week = [
                    'week' => $week_index + 1,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'study_days' => 0
                ];
                $week_index++;
            }
            
            $current_week['study_days']++;
            switch ($day_data['status']) {
                case 'present':
                    $current_week['present']++;
                    break;
                case 'late':
                    $current_week['late']++;
                    break;
                default:
                    $current_week['absent']++;
                    break;
            }
        }
        
        $calendar[$date_str] = $day_data;
    }
    
    // เพิ่มสัปดาห์สุดท้าย
    if (!empty($current_week)) {
        $weekly_data[] = $current_week;
    }
    
    // สร้างผลลัพธ์
    $result = [
        'success' => true,
        'student' => $student,
        'stats' => [
            'total_present' => $total_present,
            'total_late' => $total_late,
            'total_absent' => $total_absent,
            'total_study_days' => $total_study_days,
            'attendance_rate' => $total_study_days > 0 ? 
                round((($total_present + $total_late) / $total_study_days) * 100, 1) : 0
        ],
        'calendar' => $calendar,
        'weekly' => $weekly_data,
        'academic_year' => $academic_year
    ];
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get Student Activity Result Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
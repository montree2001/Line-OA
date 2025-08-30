<?php
/**
 * attendance_analysis.php - ไฟล์วิเคราะห์ข้อมูลการเข้าแถว
 */

// ตัวแปรสำหรับเก็บข้อมูล
$chart_data = null;
$class_info = null;
$students = [];

try {
    // ดึงข้อมูลนักเรียน
    if ($search_type === 'class' && !empty($class_id)) {
        // ดึงข้อมูลจากห้องเรียน
        $query = "SELECT 
                    s.student_id,
                    s.student_code,
                    u.title,
                    u.first_name,
                    u.last_name,
                    s.status,
                    c.level,
                    c.group_number,
                    d.department_name
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  JOIN classes c ON s.current_class_id = c.class_id
                  JOIN departments d ON c.department_id = d.department_id
                  WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
                  ORDER BY u.first_name, u.last_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลห้องเรียน
        $query = "SELECT c.*, d.department_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.department_id 
                  WHERE c.class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } elseif ($search_type === 'student' && !empty($search)) {
        // ดึงข้อมูลจากการค้นหานักเรียน
        $query = "SELECT 
                    s.student_id,
                    s.student_code,
                    u.title,
                    u.first_name,
                    u.last_name,
                    s.status,
                    c.level,
                    c.group_number,
                    d.department_name
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  JOIN classes c ON s.current_class_id = c.class_id
                  JOIN departments d ON c.department_id = d.department_id
                  WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_code LIKE ?)
                    AND s.status = 'กำลังศึกษา'
                  ORDER BY u.first_name, u.last_name";
        
        $search_term = '%' . $search . '%';
        $stmt = $conn->prepare($query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (!empty($students)) {
        // สร้างอาเรย์ student_ids
        $student_ids = array_column($students, 'student_id');
        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
        
        // ดึงข้อมูลการเข้าแถว
        $query = "SELECT 
                    student_id,
                    date,
                    attendance_status
                  FROM attendance 
                  WHERE student_id IN ($placeholders)
                    AND date BETWEEN ? AND ?
                  ORDER BY date, student_id";
        
        $params = array_merge($student_ids, [$start_date, $end_date]);
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // จัดกลุ่มข้อมูลการเข้าแถว
        $attendance_data = [];
        foreach ($attendance_records as $record) {
            $attendance_data[$record['student_id']][$record['date']] = $record['attendance_status'];
        }
        
        // สร้างรายการวันที่ในช่วงที่เลือก
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );
        
        $date_list = [];
        foreach ($period as $date) {
            // เฉพาะวันจันทร์ - ศุกร์
            if ($date->format('N') <= 5) {
                $date_list[] = $date->format('Y-m-d');
            }
        }
        
        // ดึงข้อมูลวันหยุด
        $holidays = [];
        try {
            $query = "SELECT holiday_date FROM holidays 
                      WHERE holiday_date BETWEEN ? AND ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$start_date, $end_date]);
            $holiday_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($holiday_records as $holiday) {
                $holidays[$holiday['holiday_date']] = true;
            }
        } catch (Exception $e) {
            // ไม่มีตาราง holidays
        }
        
        // คำนวณสถิติ
        $totalPresent = 0;
        $totalLate = 0; 
        $totalAbsent = 0;
        $totalLeave = 0;
        $dailyStats = [];
        
        foreach ($date_list as $date) {
            if (!isset($holidays[$date])) {
                $dayStats = [
                    'date' => $date,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'leave' => 0,
                    'attendance_rate' => 0,
                    'is_holiday' => false
                ];
                
                $total_students_for_day = count($students);
                $present_count = 0;
                
                foreach ($students as $student) {
                    $status = $attendance_data[$student['student_id']][$date] ?? 'absent';
                    
                    switch ($status) {
                        case 'present':
                            $dayStats['present']++;
                            $totalPresent++;
                            $present_count++;
                            break;
                        case 'late':
                            $dayStats['late']++;
                            $totalLate++;
                            $present_count++; // นับสายเป็นมาแถวด้วย
                            break;
                        case 'leave':
                            $dayStats['leave']++;
                            $totalLeave++;
                            break;
                        default: // absent
                            $dayStats['absent']++;
                            $totalAbsent++;
                            break;
                    }
                }
                
                if ($total_students_for_day > 0) {
                    $dayStats['attendance_rate'] = ($present_count / $total_students_for_day) * 100;
                }
                
                $dailyStats[] = $dayStats;
            }
        }
        
        // คำนวณอัตราการเข้าแถวรวม
        $totalDays = count($dailyStats);
        $totalPossibleAttendance = $totalDays * count($students);
        $totalAttendances = $totalPresent + $totalLate;
        $attendanceRate = $totalPossibleAttendance > 0 ? ($totalAttendances / $totalPossibleAttendance) * 100 : 0;
        
        // สร้างข้อมูลสำหรับ Chart
        $chart_data = [
            'totalPresent' => $totalPresent,
            'totalLate' => $totalLate,
            'totalAbsent' => $totalAbsent,
            'totalLeave' => $totalLeave,
            'totalDays' => $totalDays,
            'attendanceRate' => $attendanceRate,
            'dailyStats' => $dailyStats,
            'studentCount' => count($students)
        ];
    }
    
} catch (Exception $e) {
    error_log("Attendance Analysis Error: " . $e->getMessage());
    $chart_data = null;
}
?>
<?php
/**
 * attendance_adjustment.php - ปรับข้อมูลเข้าแถวสำหรับนักเรียนที่เข้าแถวไม่ถึง 60%
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'attendance_adjustment';
$page_title = 'ปรับข้อมูลเข้าแถว';
$page_header = 'ปรับข้อมูลการเข้าแถวสำหรับนักเรียนที่เข้าแถวไม่ถึง 60%';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$message_type = '';

// ไม่ใช้ filters อีกต่อไป - DataTables จะจัดการ
$filters = [];

// จัดการการ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adjust_attendance') {
        $student_id = $_POST['student_id'] ?? 0;
        $days_to_add = $_POST['days_to_add'] ?? 0;
        
        if ($student_id > 0 && $days_to_add > 0) {
            $result = adjustStudentAttendance($student_id, $days_to_add);
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        }
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลแผนก
function getDepartments() {
    try {
        $conn = getDB();
        $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting departments: " . $e->getMessage());
        // คืนข้อมูลจำลองเมื่อเกิดข้อผิดพลาด
        return [
            ['department_id' => 1, 'department_name' => 'คอมพิวเตอร์'],
            ['department_id' => 2, 'department_name' => 'อิเล็กทรอนิกส์'],
            ['department_id' => 3, 'department_name' => 'ช่างยนต์'],
            ['department_id' => 4, 'department_name' => 'สามัญ'],
            ['department_id' => 5, 'department_name' => 'บัญชี'],
            ['department_id' => 6, 'department_name' => 'การตลาด']
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลชั้นปี
function getLevels() {
    try {
        $conn = getDB();
        $stmt = $conn->query("SELECT DISTINCT level FROM classes ORDER BY level");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error getting levels: " . $e->getMessage());
        return ['ม.4', 'ม.5', 'ม.6', 'ปวช.1', 'ปวช.2', 'ปวส.1'];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลอาจารย์ที่ปรึกษา
function getAdvisors() {
    try {
        $conn = getDB();
        $stmt = $conn->query("
            SELECT DISTINCT t.teacher_id, u.first_name, u.last_name
            FROM class_advisors ca
            LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE u.first_name IS NOT NULL
            ORDER BY u.first_name, u.last_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting advisors: " . $e->getMessage());
        // คืนข้อมูลจำลองเมื่อเกิดข้อผิดพลาด
        return [
            ['teacher_id' => 1, 'first_name' => 'อาจารย์สมชาย', 'last_name' => 'ใจดี'],
            ['teacher_id' => 2, 'first_name' => 'อาจารย์สมหญิง', 'last_name' => 'รักเรียน'],
            ['teacher_id' => 3, 'first_name' => 'อาจารย์สมศักดิ์', 'last_name' => 'ใฝ่หาความรู้']
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลนักเรียนที่เข้าแถวต่ำกว่า 60% พร้อมการกรอง
function getStudentsUnder60Percent($filters = []) {
    try {
        $conn = getDB();
        // ตรวจสอบว่ามีตาราง academic_years หรือไม่
        $tables_stmt = $conn->query("SHOW TABLES LIKE 'academic_years'");
        $has_academic_years = $tables_stmt->fetch() !== false;
        
        $academic_year_id = 1; // ค่าเริ่มต้น
        
        if ($has_academic_years) {
            // ดึงปีการศึกษาปัจจุบัน
            $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
            $stmt = $conn->query($academic_year_query);
            $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$academic_year) {
                // หากไม่พบ active ให้ใช้อันล่าสุด
                $stmt = $conn->query("SELECT academic_year_id FROM academic_years ORDER BY academic_year_id DESC LIMIT 1");
                $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($academic_year) {
                    $academic_year_id = $academic_year['academic_year_id'];
                }
            } else {
                $academic_year_id = $academic_year['academic_year_id'];
            }
        }
        
        // คำนวณจำนวนวันทั้งหมดในปีการศึกษา
        $total_days = 48;
        
        // ตรวจสอบว่ามีตาราง student_academic_records หรือไม่
        $tables_stmt = $conn->query("SHOW TABLES LIKE 'student_academic_records'");
        $has_sar = $tables_stmt->fetch() !== false;
        
        if (!$has_sar) {
            // สร้างข้อมูลจำลองถ้าไม่มีตาราง
            return createSampleStudentsData($filters);
        }
        
        // สร้าง WHERE conditions สำหรับการกรอง
        $where_conditions = [];
        $params = [$total_days, $academic_year_id, $total_days, $total_days];
        
        // เพิ่มเงื่อนไขการกรองตามแผนก
        if (!empty($filters['department_id'])) {
            $where_conditions[] = "d.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        // เพิ่มเงื่อนไขการกรองตามชั้นปี
        if (!empty($filters['level'])) {
            $where_conditions[] = "c.level = ?";
            $params[] = $filters['level'];
        }
        
        // เพิ่มเงื่อนไขการกรองตามอาจารย์ที่ปรึกษา
        if (!empty($filters['advisor_id'])) {
            $where_conditions[] = "EXISTS (SELECT 1 FROM class_advisors ca2 WHERE ca2.class_id = s.current_class_id AND ca2.teacher_id = ?)";
            $params[] = $filters['advisor_id'];
        }
        
        // เพิ่มเงื่อนไขการค้นหาตามชื่อ
        if (!empty($filters['search'])) {
            $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_code LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_clause = "";
        if (!empty($where_conditions)) {
            $where_clause = " AND " . implode(" AND ", $where_conditions);
        }
        
        // ดึงข้อมูลนักเรียนที่มีการเข้าแถวต่ำกว่า 50%
        $query = "
            SELECT 
                s.student_id,
                s.student_code,
                COALESCE(s.title, '') AS title,
                COALESCE(s.status, 'ปกติ') AS status,
                u.first_name,
                u.last_name,
                COALESCE(c.level, 'ม.6') AS level,
                COALESCE(c.group_number, '1') AS group_number,
                COALESCE(d.department_name, 'สามัญ') AS department_name,
                CONCAT(COALESCE(c.level, 'ม.6'), '/', COALESCE(c.group_number, '1')) AS class,
                COALESCE(sar.total_attendance_days, 0) AS attended_days,
                COALESCE(sar.total_absence_days, 0) AS absence_days,
                ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) AS attendance_percentage
            FROM 
                students s
            LEFT JOIN 
                users u ON s.user_id = u.user_id
            LEFT JOIN 
                classes c ON s.current_class_id = c.class_id
            LEFT JOIN 
                departments d ON c.department_id = d.department_id
            LEFT JOIN 
                student_academic_records sar ON s.student_id = sar.student_id 
                    AND sar.academic_year_id = ?
            WHERE 
                ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) < 60
                AND s.status = 'กำลังศึกษา'
                $where_clause
            ORDER BY 
                ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) ASC
            LIMIT 200
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // คำนวณจำนวนวันที่ต้องเพิ่มเพื่อให้ได้ 60%
        foreach ($students as &$student) {
            $student['total_days'] = $total_days;
            $target_days = ceil($total_days * 0.6); // 60% ของ total_days = 29 วัน
            $current_attended = (int)$student['attended_days'];
            $days_needed = $target_days - $current_attended;
            $student['days_needed'] = max(0, $days_needed);
            
            // คำนวณเปอร์เซ็นต์ใหม่หลังจากเพิ่มวันที่ต้องการ
            $projected_attended = $current_attended + $student['days_needed'];
            $student['projected_percentage'] = round(($projected_attended / $total_days) * 100, 2);
            
            // ตรวจสอบข้อมูลชื่อ
            if (empty($student['first_name'])) {
                $student['first_name'] = 'นักเรียน';
                $student['last_name'] = 'รหัส' . $student['student_code'];
            }
        }
        
        return $students;
        
    } catch (Exception $e) {
        error_log("Error in getStudentsUnder60Percent: " . $e->getMessage());
        // คืนข้อมูลจำลองเมื่อเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล
        return createSampleStudentsData($filters);
    }
}

// ฟังก์ชันสร้างข้อมูลจำลองเมื่อไม่พบข้อมูลจริง
function createSampleStudentsData($filters = []) {
    try {
        $sample_data = [];
        $total_days = 48;
        
        // สร้างข้อมูลจำลอง 30 คนให้ครอบคลุมทุกแผนกและชั้นปีในทุกคอมบิเนชัน
        $departments = ['คอมพิวเตอร์', 'อิเล็กทรอนิกส์', 'ช่างยนต์', 'สามัญ', 'บัญชี', 'การตลาด'];
        $levels = ['ม.4', 'ม.5', 'ม.6', 'ปวช.1', 'ปวช.2', 'ปวส.1'];
        
        $student_id = 1000;
        
        // สร้างนักเรียนให้ครอบคลุมทุกแผนกในทุกชั้นปี
        foreach ($departments as $dept_index => $dept_name) {
            foreach ($levels as $level_index => $level) {
                $student_id++;
                
                // สร้างข้อมูลการเข้าแถวจำลองที่ต่ำกว่า 60%
                $attended_days = rand(10, 28); // สุ่มวันที่เข้าแถวระหว่าง 10-28 วัน
                $attendance_percentage = round(($attended_days / $total_days) * 100, 2);
                $target_days = ceil($total_days * 0.6); // 29 วัน
                
                $days_needed = max(0, $target_days - $attended_days);
                $projected_attended = $attended_days + $days_needed;
                $projected_percentage = round(($projected_attended / $total_days) * 100, 2);
                
                $sample_data[] = [
                    'student_id' => $student_id,
                    'student_code' => 'STD' . str_pad($student_id - 1000, 4, '0', STR_PAD_LEFT),
                    'title' => ($student_id % 2 == 0) ? 'นาย' : 'นางสาว',
                    'status' => 'กำลังศึกษา',
                    'first_name' => 'นักเรียน',
                    'last_name' => 'ตัวอย่าง' . ($student_id - 1000),
                    'level' => $level,
                    'group_number' => (($student_id % 4) + 1), // กลุ่ม 1-4
                    'department_name' => $dept_name,
                    'department_id' => $dept_index + 1,
                    'class' => $level . '/' . (($student_id % 4) + 1),
                    'attended_days' => $attended_days,
                    'absence_days' => $total_days - $attended_days,
                    'attendance_percentage' => $attendance_percentage,
                    'total_days' => $total_days,
                    'days_needed' => $days_needed,
                    'projected_percentage' => $projected_percentage
                ];
                
                // จำกัดจำนวนนักเรียนไม่เกิน 30 คน
                if (count($sample_data) >= 30) {
                    break 2;
                }
            }
        }
        
        // กรองข้อมูลตามเงื่อนไข
        if (!empty($filters)) {
            $filtered_data = [];
            foreach ($sample_data as $student) {
                $include = true;
                
                // กรองตามแผนก
                if (!empty($filters['department_id'])) {
                    if ($student['department_id'] != $filters['department_id']) {
                        $include = false;
                    }
                }
                
                // กรองตามชั้นปี
                if (!empty($filters['level'])) {
                    if ($student['level'] !== $filters['level']) {
                        $include = false;
                    }
                }
                
                // กรองตามการค้นหาชื่อหรือรหัส
                if (!empty($filters['search'])) {
                    $search = strtolower($filters['search']);
                    $full_name = strtolower($student['first_name'] . ' ' . $student['last_name']);
                    $code = strtolower($student['student_code']);
                    
                    if (strpos($full_name, $search) === false && strpos($code, $search) === false) {
                        $include = false;
                    }
                }
                
                if ($include) {
                    $filtered_data[] = $student;
                }
            }
            return $filtered_data;
        }
        
        return $sample_data;
        
    } catch (Exception $e) {
        error_log("Error in createSampleStudentsData: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับปรับข้อมูลการเข้าแถว
// ฟังก์ชันสำหรับดึงวันหยุด
 function getHolidays($academic_year_id) {
    $conn = getDB();
    
    try {
        $query = "SELECT holiday_date FROM holidays WHERE academic_year_id = ? OR academic_year_id IS NULL";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academic_year_id]);
        
        $holidays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $holidays[] = $row['holiday_date'];
        }
        
        return $holidays;
        
    } catch (Exception $e) {
        error_log("Error getting holidays: " . $e->getMessage());
        return [];
    }
}

// ฟังก์ชันสำหรับหาวันทำการย้อนหลังที่ไม่ใช่วันหยุด
function getWorkingDaysFromPast($start_date, $days_needed, $holidays) {
    $working_days = [];
    $current_date = new DateTime($start_date);
    
    // เริ่มจากการหาวันทำการใน 3 เดือนที่ผ่านมา
    $max_lookback = new DateTime($start_date);
    $max_lookback->modify('-3 months');
    
    // หาวันทำการย้อนหลัง
    $attempts = 0;
    $max_attempts = $days_needed * 5; // เพิ่มความยืดหยุ่น
    
    while (count($working_days) < $days_needed && $attempts < $max_attempts) {
        $current_date->modify('-1 day');
        $date_str = $current_date->format('Y-m-d');
        $day_of_week = (int)$current_date->format('w'); // 0=อาทิตย์, 6=เสาร์
        $attempts++;
        
        // หยุดหากย้อนกลับเกินไป
        if ($current_date < $max_lookback) {
            break;
        }
        
        // ข้ามเสาร์-อาทิตย์ และวันหยุด
        if ($day_of_week == 0 || $day_of_week == 6 || in_array($date_str, $holidays)) {
            continue;
        }
        
        $working_days[] = $date_str;
    }
    
    // ถ้าหาได้ไม่เพียงพอ ให้สร้างรายการวันเพิ่มเติม
    if (count($working_days) < $days_needed) {
        $additional_needed = $days_needed - count($working_days);
        
        // ใช้ช่วงเวลาที่เหมาะสมสำหรับการเพิ่มข้อมูลย้อนหลัง
        $fallback_date = new DateTime($start_date);
        $fallback_date->modify('-2 weeks');
        
        $fallback_attempts = 0;
        $max_fallback_attempts = $additional_needed * 10;
        
        while (count($working_days) < $days_needed && $fallback_attempts < $max_fallback_attempts) {
            $fallback_date->modify('-1 day');
            $date_str = $fallback_date->format('Y-m-d');
            $day_of_week = (int)$fallback_date->format('w');
            $fallback_attempts++;
            
            // ข้ามเสาร์-อาทิตย์ และวันหยุด และวันที่มีอยู่แล้ว
            if ($day_of_week == 0 || $day_of_week == 6 || 
                in_array($date_str, $holidays) || 
                in_array($date_str, $working_days)) {
                continue;
            }
            
            // หยุดถ้าย้อนหลังเกิน 6 เดือน
            $six_months_ago = new DateTime($start_date);
            $six_months_ago->modify('-6 months');
            if ($fallback_date < $six_months_ago) {
                break;
            }
            
            $working_days[] = $date_str;
        }
    }
    
    // เรียงลำดับวันที่จากเก่าไปใหม่
    sort($working_days);
    return $working_days;
}

function adjustStudentAttendance($student_id, $days_to_add) {
    $conn = getDB();
    
    try {
        $conn->beginTransaction();
        
        // ดึงปีการศึกษาปัจจุบัน
        $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($academic_year_query);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$academic_year) {
            throw new Exception('ไม่พบปีการศึกษาที่ใช้งาน');
        }
        
        $academic_year_id = $academic_year['academic_year_id'];
        
        // ดึงข้อมูลนักเรียน
        $student_query = "SELECT current_class_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception('ไม่พบข้อมูลนักเรียน');
        }
        
        $class_id = $student['current_class_id'];
        
        // ดึงรายการวันหยุด
        $holidays = getHolidays($academic_year_id);
        
        // หาวันที่มีการเรียนย้อนหลังที่จะเพิ่มการเข้าแถว
        $today = date('Y-m-d');
        $working_days = getWorkingDaysFromPast($today, $days_to_add, $holidays);
        
        if (empty($working_days)) {
            throw new Exception('ไม่สามารถหาวันทำการย้อนหลังที่เหมาะสม');
        }
        
        // Debug: บันทึกข้อมูลการค้นหาวันทำการ
        error_log("Days to add: $days_to_add, Working days found: " . count($working_days));
        
        // ตรวจสอบว่ามีการเข้าแถวอยู่แล้วหรือไม่
        $check_existing = "SELECT date FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date IN (" . 
                         str_repeat('?,', count($working_days) - 1) . "?)";
        $params = array_merge([$student_id, $academic_year_id], $working_days);
        $stmt = $conn->prepare($check_existing);
        $stmt->execute($params);
        
        $existing_dates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_dates[] = $row['date'];
        }
        
        // กรองวันที่ยังไม่มีการเข้าแถว
        $new_attendance_days = array_diff($working_days, $existing_dates);
        $actual_days_added = 0;
        
        // เพิ่มประวัติการเข้าแถว
        foreach ($new_attendance_days as $date) {
            if ($actual_days_added >= $days_to_add) break;
            
            try {
                $insert_attendance = "
                    INSERT INTO attendance 
                    (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks) 
                    VALUES (?, ?, ?, 'present', 'Manual', ?, '08:00:00', 'ปรับข้อมูลย้อนหลัง')
                ";
                
                $stmt = $conn->prepare($insert_attendance);
                $stmt->execute([$student_id, $academic_year_id, $date, $_SESSION['user_id']]);
                $actual_days_added++;
                
                // บันทึกการเปลี่ยนแปลงเพื่อดีบัก
                error_log("Added attendance for date: $date, total added: $actual_days_added");
                
            } catch (Exception $e) {
                error_log("Error adding attendance for date $date: " . $e->getMessage());
                // ถ้าเกิดข้อผิดพลาดให้ข้ามไป
                continue;
            }
        }
        
        // ถ้าเพิ่มไม่ครบ ให้พยายามสร้างวันเพิ่มเติม
        if ($actual_days_added < $days_to_add) {
            error_log("Need to add more days: added $actual_days_added out of $days_to_add");
            
            // สร้างวันทำการเพิ่มเติม
            $additional_needed = $days_to_add - $actual_days_added;
            // ใช้วันที่ในช่วงปีการศึกษา (ใช้ปี 2024 สำหรับปีการศึกษา 2024-2025)
            $fallback_date = new DateTime('2024-08-01'); // เริ่มจากต้นปีการศึกษา
            
            // เริ่มจากรอบที่ 1: หาวันจากช่วงสิงหาคม-กันยายน 2024
            $periods = [
                new DateTime('2024-09-01'),
                new DateTime('2024-08-15'),
                new DateTime('2024-07-15'),
            ];
            
            foreach ($periods as $period_start) {
                if ($actual_days_added >= $days_to_add) break;
                
                $fallback_date = clone $period_start;
                $attempts_in_period = 0;
                $max_attempts_per_period = 100; // ให้ความยืดหยุ่นมากขึ้น
                
                while ($actual_days_added < $days_to_add && $attempts_in_period < $max_attempts_per_period) {
                    $fallback_date->modify('-1 day');
                    $date_str = $fallback_date->format('Y-m-d');
                    $day_of_week = (int)$fallback_date->format('w');
                    $attempts_in_period++;
                    
                    // ข้ามเสาร์-อาทิตย์
                    if ($day_of_week == 0 || $day_of_week == 6) {
                        continue;
                    }
                    
                    // ตรวจสอบว่าไม่มีการเข้าแถวในวันนี้อยู่แล้ว
                    $check_date_query = "SELECT COUNT(*) FROM attendance WHERE student_id = ? AND academic_year_id = ? AND date = ?";
                    $check_stmt = $conn->prepare($check_date_query);
                    $check_stmt->execute([$student_id, $academic_year_id, $date_str]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        continue; // มีข้อมูลอยู่แล้ว
                    }
                    
                    // หยุดถ้าย้อนหลังไปมากเกินไป (ก่อนปี 2024)
                    if ($fallback_date->format('Y') < '2024') {
                        break;
                    }
                    
                    try {
                        $insert_attendance = "
                            INSERT INTO attendance 
                            (student_id, academic_year_id, date, attendance_status, check_method, checker_user_id, check_time, remarks) 
                            VALUES (?, ?, ?, 'present', 'Manual', ?, '08:00:00', 'ปรับข้อมูลย้อนหลัง - ระบบสำรอง')
                        ";
                        
                        $stmt = $conn->prepare($insert_attendance);
                        $stmt->execute([$student_id, $academic_year_id, $date_str, $_SESSION['user_id']]);
                        $actual_days_added++;
                        
                        error_log("Added fallback attendance for date: $date_str, total added: $actual_days_added");
                        
                    } catch (Exception $e) {
                        error_log("Error adding fallback attendance for date $date_str: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }
        
        // อัพเดต student_academic_records
        $update_query = "
            UPDATE student_academic_records 
            SET 
                total_attendance_days = COALESCE(total_attendance_days, 0) + ?,
                total_absence_days = GREATEST(0, COALESCE(total_absence_days, 0) - ?),
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ? AND academic_year_id = ?
        ";
        
        $stmt = $conn->prepare($update_query);
        $stmt->execute([$actual_days_added, $actual_days_added, $student_id, $academic_year_id]);
        
        // ตรวจสอบว่ามีการอัพเดตหรือไม่
        if ($stmt->rowCount() == 0) {
            // ถ้าไม่มี record ให้สร้างใหม่
            $insert_query = "
                INSERT INTO student_academic_records 
                (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days) 
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $absence_days = max(0, 48 - $actual_days_added);
            
            $stmt = $conn->prepare($insert_query);
            $stmt->execute([$student_id, $academic_year_id, $class_id, $actual_days_added, $absence_days]);
        }
        
        $conn->commit();
        
        // คำนวณเปอร์เซ็นต์ใหม่
        $stmt = $conn->prepare("SELECT total_attendance_days FROM student_academic_records WHERE student_id = ? AND academic_year_id = ?");
        $stmt->execute([$student_id, $academic_year_id]);
        $updated_record = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_percentage = $updated_record ? round(($updated_record['total_attendance_days'] / 48) * 100, 2) : 0;
        
        $status_message = "";
        if ($actual_days_added == $days_to_add) {
            $status_message = "✅ เพิ่มครบตามที่ต้องการ";
        } elseif ($actual_days_added < $days_to_add) {
            $deficit = $days_to_add - $actual_days_added;
            $status_message = "⚠️ เพิ่มได้เพียง {$actual_days_added} วัน (ขาดอีก {$deficit} วัน)";
        }
        
        return [
            'success' => true, 
            'days_added' => $actual_days_added,
            'new_percentage' => $new_percentage,
            'requested_days' => $days_to_add,
            'message' => "ปรับข้อมูลเสร็จสิ้น! ต้องการ {$days_to_add} วัน • เพิ่มได้ {$actual_days_added} วัน • เปอร์เซ็นต์ใหม่ {$new_percentage}% {$status_message}"
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error adjusting attendance: " . $e->getMessage());
        
        return [
            'success' => false,
            'days_added' => 0,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

// ดึงข้อมูลนักเรียนที่ต้องปรับ
$students_under_60 = getStudentsUnder60Percent($filters);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - STP-Prasat</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .page-header .material-icons {
            margin-right: 12px;
            font-size: 32px;
        }
        
        .page-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        /* DataTables Custom Styles */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin: 15px 0;
            padding: 10px 20px;
        }
        
        .dataTables_wrapper .dataTables_filter {
            text-align: right;
        }
        
        .dataTables_wrapper .dataTables_filter input[type="search"] {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }
        
        .dataTables_wrapper .dataTables_filter input[type="search"]:focus {
            outline: none;
            border-color: #2196f3;
        }
        
        /* Pagination Styles */
        .dataTables_wrapper .dataTables_paginate {
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .dataTables_wrapper .dataTables_paginate .pagination {
            justify-content: center;
            margin: 0;
        }
        
        .dataTables_wrapper .dataTables_paginate .page-link {
            color: #495057;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .dataTables_wrapper .dataTables_paginate .page-link:hover {
            color: #0056b3;
            background-color: #e9ecef;
            border-color: #adb5bd;
            transform: translateY(-1px);
        }
        
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,123,255,0.25);
        }
        
        .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
            cursor: not-allowed;
        }
        
        /* Info and Length Styles */
        .dataTables_wrapper .dataTables_info {
            color: #6c757d;
            font-size: 14px;
            padding-top: 10px;
        }
        
        .dataTables_wrapper .dataTables_length select {
            padding: 6px 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #fff;
            color: #495057;
        }
        
        .dt-buttons {
            margin-bottom: 15px;
        }
        
        .dt-buttons .btn {
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-number.danger { color: #f44336; }
        .stat-number.warning { color: #ff9800; }
        .stat-number.success { color: #4caf50; }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        
        .students-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .students-table-container .dataTables_wrapper {
            padding: 0;
        }
        
        .students-table-container .dataTables_wrapper .dataTables_paginate {
            background: white;
            border-top: 1px solid #f0f0f0;
            margin-top: 0;
            padding: 20px;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table-header h3 {
            margin: 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e9ecef;
            font-size: 14px;
        }
        
        .students-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        .students-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .student-info {
            display: flex;
            flex-direction: column;
        }
        
        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .student-code {
            color: #666;
            font-size: 13px;
        }
        
        .attendance-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            min-width: 60px;
        }
        
        .attendance-badge.critical {
            background: #ffebee;
            color: #c62828;
        }
        
        .attendance-badge.warning {
            background: #fff8e1;
            color: #f57c00;
        }
        
        .attendance-badge.success {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .adjust-btn {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .adjust-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .adjust-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .no-students {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-students .material-icons {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
            color: #4caf50;
        }
        
        .no-students h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert .material-icons {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'templates/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <span class="material-icons">auto_fix_high</span>
                <?php echo $page_title; ?>
            </h1>
            <p><?php echo $page_header; ?></p>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span class="material-icons">
                    <?php echo $message_type === 'success' ? 'check_circle' : 'error'; ?>
                </span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        
        <?php if (count($students_under_60) > 0): ?>
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number danger"><?php echo count($students_under_60); ?></div>
                    <div class="stat-label">นักเรียนที่ต้องปรับข้อมูล</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number warning">
                        <?php echo count(array_filter($students_under_60, function($s) { return $s['attendance_percentage'] >= 40; })); ?>
                    </div>
                    <div class="stat-label">เข้าแถว 40-59%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number danger">
                        <?php echo count(array_filter($students_under_60, function($s) { return $s['attendance_percentage'] < 40; })); ?>
                    </div>
                    <div class="stat-label">เข้าแถวต่ำกว่า 40%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number success">60%</div>
                    <div class="stat-label">เป้าหมายการเข้าแถว</div>
                </div>
            </div>
            
            <!-- Students Table -->
            <div class="students-table-container">
                <div class="table-header">
                    <h3>รายชื่อนักเรียนที่ต้องปรับข้อมูลการเข้าแถว</h3>
                </div>
                <table class="students-table table table-striped table-hover" id="studentsTable">
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>ชั้นเรียน</th>
                            <th>แผนกวิชา</th>
                            <th>การเข้าแถว</th>
                            <th>เปอร์เซ็นต์</th>
                            <th>ต้องเพิ่ม (วัน)</th>
                            <th>หลังปรับ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_under_60 as $student): ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-name">
                                            <?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>
                                        </div>
                                        <div class="student-code"><?php echo htmlspecialchars($student['student_code']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['class'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo $student['attended_days']; ?>/<?php echo $student['total_days']; ?> วัน</td>
                                <td>
                                    <span class="attendance-badge <?php echo $student['attendance_percentage'] < 40 ? 'critical' : 'warning'; ?>">
                                        <?php echo $student['attendance_percentage']; ?>%
                                    </span>
                                </td>
                                <td class="days-needed">
                                    <div class="days-needed-info">
                                        <strong class="days-number"><?php echo $student['days_needed']; ?></strong>
                                        <span class="days-label">วัน</span>
                                        <div class="target-info">เพื่อถึง 60%</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="attendance-badge success">
                                        <?php echo $student['projected_percentage'] ?? '60.00'; ?>%
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="adjust_attendance">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <input type="hidden" name="days_to_add" value="<?php echo $student['days_needed']; ?>">
                                        <button type="submit" class="adjust-btn" 
                                                onclick="return confirm('จะปรับข้อมูลการเข้าแถวให้ <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>\n\nปัจจุบัน: <?php echo $student['attended_days']; ?> วัน (<?php echo $student['attendance_percentage']; ?>%)\nจะเพิ่ม: <?php echo $student['days_needed']; ?> วัน\nหลังปรับ: <?php echo ($student['attended_days'] + $student['days_needed']); ?> วัน (<?php echo $student['projected_percentage'] ?? '60.00'; ?>%)\n\nยืนยันการดำเนินการ?')">
                                            ⚙️ ปรับเป็น 60%
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <!-- No Students Found -->
            <div class="students-table-container">
                <div class="no-students">
                    <div class="material-icons">sentiment_very_satisfied</div>
                    <h3>ไม่พบนักเรียนที่ต้องปรับข้อมูล</h3>
                    <p>นักเรียนทุกคนมีการเข้าแถวมากกว่าหรือเท่ากับ 60% แล้ว</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
        
        // Admin dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const adminMenuToggle = document.getElementById('adminMenuToggle');
            const adminDropdown = document.getElementById('adminDropdown');
            
            if (adminMenuToggle && adminDropdown) {
                adminMenuToggle.addEventListener('click', function() {
                    adminDropdown.style.display = adminDropdown.style.display === 'block' ? 'none' : 'block';
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!adminMenuToggle.contains(event.target) && !adminDropdown.contains(event.target)) {
                        adminDropdown.style.display = 'none';
                    }
                });
            }
        });
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#studentsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/th.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="material-icons" style="font-size: 16px;">download</i> Excel',
                        titleAttr: 'Export เป็น Excel',
                        className: 'btn btn-success btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="material-icons" style="font-size: 16px;">picture_as_pdf</i> PDF',
                        titleAttr: 'Export เป็น PDF',
                        className: 'btn btn-danger btn-sm'
                    },
                    {
                        extend: 'print',
                        text: '<i class="material-icons" style="font-size: 16px;">print</i> พิมพ์',
                        titleAttr: 'พิมพ์',
                        className: 'btn btn-info btn-sm'
                    }
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[4, 'asc']], // เรียงตามเปอร์เซ็นต์จากน้อยไปมาก
                columnDefs: [
                    { targets: [7], orderable: false, searchable: false }, // คอลัมน์การจัดการ (คอลัมน์สุดท้าย)
                    { targets: [4], type: 'num-fmt' }, // เปอร์เซ็นต์
                    { targets: [5], type: 'num-fmt' }, // จำนวนวัน
                    { targets: [6], type: 'num-fmt' } // เปอร์เซ็นต์หลังปรับ
                ],
                searching: true,
                info: true,
                paging: true,
                autoWidth: false,
                stateSave: true
            });
        });
    </script>
</body>
</html>
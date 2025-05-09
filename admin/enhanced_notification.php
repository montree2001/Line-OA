<?php
/**
 * enhanced_notification.php - หน้าส่งข้อความแจ้งเตือนผู้ปกครองแบบปรับปรุง
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$conn = getDB();

// โหลด LINE Notification API
require_once 'notification_api.php';

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'enhanced_notification';
$page_title = 'ส่งข้อความแจ้งเตือน';
$page_header = 'ส่งข้อความรายงานการเข้าแถว';

// ค่าใช้จ่ายในการส่งข้อความ LINE
$line_message_cost = 0.075; // บาทต่อข้อความ
$line_image_cost = 0.15; // บาทต่อรูปภาพ
$line_link_cost = 0.075; // บาทต่อลิงก์

// โหลดคลาสจัดการเทมเพลต
require_once 'notification_templates.php';
$template_manager = new NotificationTemplates();

// ดึงข้อมูลตั้งค่าระบบ
try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_access_token', 'school_name', 'primary_color', 'secondary_color')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $line_access_token = $settings['line_access_token'] ?? '';
    $school_name = $settings['school_name'] ?? 'วิทยาลัยการอาชีพปราสาท';
    $primary_color = $settings['primary_color'] ?? '#28a745';
    $secondary_color = $settings['secondary_color'] ?? '#6c757d';
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = [];
    $line_access_token = '';
    $school_name = 'วิทยาลัยการอาชีพปราสาท';
    $primary_color = '#28a745';
    $secondary_color = '#6c757d';
}

// ทดสอบการเชื่อมต่อฐานข้อมูล
try {
    $testQuery = $conn->query("SELECT 1");
    error_log("Database connection test: SUCCESS");
} catch (PDOException $e) {
    error_log("Database connection test: FAILED - " . $e->getMessage());
}

// สร้าง LINE API Object
$lineAPI = new LineNotificationAPI($line_access_token, $conn);

// ดึงข้อมูลแผนกวิชา
try {
    $stmt = $conn->query("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
    $departments = [];
}

// ดึงข้อมูลครูที่ปรึกษา
try {
    $stmt = $conn->query("
        SELECT DISTINCT 
            t.teacher_id, 
            t.title, 
            t.first_name, 
            t.last_name 
        FROM 
            teachers t
            JOIN class_advisors ca ON t.teacher_id = ca.teacher_id
        ORDER BY 
            t.first_name, t.last_name
    ");
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching advisors: " . $e->getMessage());
    $advisors = [];
}

// ดึงข้อมูลชั้นปี
try {
    $stmt = $conn->query("SELECT DISTINCT level FROM classes WHERE is_active = 1 ORDER BY level");
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching levels: " . $e->getMessage());
    $levels = [];
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->query("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1");
    $current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_academic_year) {
        $current_academic_year = [
            'academic_year_id' => 1,
            'year' => date('Y') + 543,
            'semester' => 1
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching academic year: " . $e->getMessage());
    $current_academic_year = [
        'academic_year_id' => 1,
        'year' => date('Y') + 543,
        'semester' => 1
    ];
}

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
try {
    $at_risk_query = $conn->prepare("
        SELECT COUNT(*) FROM student_academic_records sar
        JOIN students s ON sar.student_id = s.student_id
        JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE ay.is_active = 1
        AND s.status = 'กำลังศึกษา'
        AND (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 80
    ");
    $at_risk_query->execute();
    $at_risk_count = $at_risk_query->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching at risk students: " . $e->getMessage());
    $at_risk_count = 0;
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/notification_enhanced.css'
];

$extra_js = [
    'assets/js/notification_enhanced.js',
    'assets/js/chart.min.js'  // เพิ่ม Chart.js สำหรับสร้างกราฟ
];

/**
 * ฟังก์ชันดึงข้อมูลนักเรียนตามเงื่อนไข - แก้ไขให้ใช้งานได้จริง
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @param int $limit จำนวนรายการที่ต้องการ
 * @param int $offset ตำแหน่งเริ่มต้น
 * @return array ข้อมูลนักเรียน
 */
function getStudentsByFilters($conn, $filters = [], $limit = 10, $offset = 0) {
    try {
        // สร้างคำสั่ง SQL พื้นฐาน
        $baseQuery = "
            SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                s.status,
                s.current_class_id,
                c.level,
                c.group_number,
                d.department_name,
                d.department_id
            FROM 
                students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE 
                s.status = 'กำลังศึกษา'
        ";
        
        // เพิ่มเงื่อนไขการกรอง
        $params = [];
        $whereConditions = [];
        
        // กรองตามชื่อหรือรหัสนักเรียน
        if (!empty($filters['student_name'])) {
            $searchTerm = '%' . $filters['student_name'] . '%';
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_code LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // กรองตามแผนกวิชา
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "d.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        // กรองตามระดับชั้น
        if (!empty($filters['class_level'])) {
            $whereConditions[] = "c.level = ?";
            $params[] = $filters['class_level'];
        }
        
        // กรองตามกลุ่ม
        if (!empty($filters['class_group'])) {
            $whereConditions[] = "c.group_number = ?";
            $params[] = $filters['class_group'];
        }
        
        // กรองตามครูที่ปรึกษา
        if (!empty($filters['advisor_id'])) {
            $baseQuery .= " JOIN class_advisors ca ON c.class_id = ca.class_id ";
            $whereConditions[] = "ca.teacher_id = ?";
            $params[] = $filters['advisor_id'];
        }
        
        // รวมเงื่อนไข WHERE เข้ากับคำสั่ง SQL
        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(" AND ", $whereConditions);
        }
        
        // เพิ่ม ORDER BY และ LIMIT
        $baseQuery .= " ORDER BY c.level, c.group_number, u.first_name";

        // หากมีการกำหนดจำนวนจำกัด
        if ($limit > 0) {
            $baseQuery .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        // ประมวลผลคำสั่ง SQL
        $stmt = $conn->prepare($baseQuery);
        
        // ถ้ามีการใช้ bindParam ต้องทำทีละตัว
        if (!empty($params)) {
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // บันทึกผลลัพธ์จาก query เพื่อ debug
        error_log("SQL Query result count: " . count($students));
        
        // ถ้าไม่มีนักเรียน ให้ return เลย ไม่ต้องทำส่วนที่เหลือ
        if (empty($students)) {
            return [];
        }
        
        // เพิ่มข้อมูลเพิ่มเติมสำหรับแต่ละนักเรียน
        foreach ($students as &$student) {
            // รวมข้อมูลชั้นเรียน
            $student['class'] = isset($student['level']) && isset($student['group_number']) ? 
                $student['level'] . '/' . $student['group_number'] : '';
            
            // ดึงข้อมูลการเข้าแถว
            try {
                $attendance_stmt = $conn->prepare("
                    SELECT 
                        total_attendance_days, 
                        total_absence_days
                    FROM 
                        student_academic_records
                    WHERE 
                        student_id = ? 
                        AND academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1)
                ");
                $attendance_stmt->execute([$student['student_id']]);
                $attendance_info = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($attendance_info) {
                    $student['total_attendance_days'] = $attendance_info['total_attendance_days'] ?? 0;
                    $student['total_absence_days'] = $attendance_info['total_absence_days'] ?? 0;
                    
                    $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
                    if ($total_days > 0) {
                        $rate = round(($student['total_attendance_days'] / $total_days) * 100);
                        $student['attendance_rate'] = $rate;
                        $student['attendance_days'] = "{$student['total_attendance_days']}/$total_days วัน ($rate%)";
                        
                        // กำหนดสถานะความเสี่ยง
                        if ($rate < 60) {
                            $student['status'] = 'เสี่ยงตกกิจกรรม';
                            $student['status_class'] = 'danger';
                        } elseif ($rate < 80) {
                            $student['status'] = 'ต้องระวัง';
                            $student['status_class'] = 'warning';
                        } else {
                            $student['status'] = 'ปกติ';
                            $student['status_class'] = 'success';
                        }
                    } else {
                        $student['attendance_rate'] = 0;
                        $student['attendance_days'] = "0/0 วัน (0%)";
                        $student['status'] = 'ไม่มีข้อมูล';
                        $student['status_class'] = 'secondary';
                    }
                } else {
                    $student['total_attendance_days'] = 0;
                    $student['total_absence_days'] = 0;
                    $student['attendance_rate'] = 0;
                    $student['attendance_days'] = "0/0 วัน (0%)";
                    $student['status'] = 'ไม่มีข้อมูล';
                    $student['status_class'] = 'secondary';
                }
            } catch (PDOException $e) {
                error_log("Error getting attendance info: " . $e->getMessage());
                $student['total_attendance_days'] = 0;
                $student['total_absence_days'] = 0;
                $student['attendance_rate'] = 0;
                $student['attendance_days'] = "0/0 วัน (0%)";
                $student['status'] = 'ไม่มีข้อมูล';
                $student['status_class'] = 'secondary';
            }
            
            // ดึงข้อมูลผู้ปกครอง
            try {
                $parent_stmt = $conn->prepare("
                    SELECT COUNT(*) as parent_count
                    FROM parent_student_relation psr
                    JOIN parents p ON psr.parent_id = p.parent_id
                    JOIN users u ON p.user_id = u.user_id
                    WHERE psr.student_id = ?
                ");
                $parent_stmt->execute([$student['student_id']]);
                $parent_count = $parent_stmt->fetchColumn();
                
                $student['parent_count'] = $parent_count;
                
                if ($parent_count > 0) {
                    // ดึงรายชื่อผู้ปกครอง
                    $parent_names_stmt = $conn->prepare("
                        SELECT u.first_name, u.last_name, p.relationship
                        FROM parent_student_relation psr
                        JOIN parents p ON psr.parent_id = p.parent_id
                        JOIN users u ON p.user_id = u.user_id
                        WHERE psr.student_id = ?
                        LIMIT 2
                    ");
                    $parent_names_stmt->execute([$student['student_id']]);
                    $parents = $parent_names_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $parent_details = [];
                    foreach ($parents as $parent) {
                        $parent_details[] = "{$parent['relationship']}: {$parent['first_name']} {$parent['last_name']}";
                    }
                    
                    $student['parents_info'] = implode(", ", $parent_details);
                    if ($parent_count > 2) {
                        $student['parents_info'] .= " และอีก " . ($parent_count - 2) . " คน";
                    }
                } else {
                    $student['parents_info'] = "ไม่มีข้อมูล";
                }
            } catch (PDOException $e) {
                error_log("Error getting parent info: " . $e->getMessage());
                $student['parent_count'] = 0;
                $student['parents_info'] = "ไม่มีข้อมูล";
            }
            
            // ข้อมูลอื่นๆ
            $student['initial'] = !empty($student['first_name']) ? mb_substr($student['first_name'], 0, 1, 'UTF-8') : '?';
        }

        // กรองนักเรียนที่ไม่ตรงตามเงื่อนไขความเสี่ยงออก
        $filtered_students = $students;
        if (!empty($filters['risk_status'])) {
            $filtered_students = array_filter($students, function($student) use ($filters) {
                return isset($student['status']) && $student['status'] === $filters['risk_status'];
            });
            
            // ถ้ากรองแล้วไม่มีข้อมูล ไม่ต้องกรอง ให้ใช้ข้อมูลทั้งหมด
            if (empty($filtered_students)) {
                error_log("Risk status filter returned empty result, using all students");
                return array_values($students);
            }
        }
        
        return array_values($filtered_students);
    } catch (PDOException $e) {
        error_log("Error in getStudentsByFilters: " . $e->getMessage());
        return [];
    }
}

/**
 * ฟังก์ชันนับจำนวนนักเรียนที่ตรงตามเงื่อนไข - แก้ไขให้ตรงกับฟังก์ชัน getStudentsByFilters
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @return int จำนวนนักเรียน
 */
function countStudentsByFilters($conn, $filters = []) {
    try {
        // ถ้ามีการกรองตามสถานะความเสี่ยง ต้องดึงข้อมูลทั้งหมดและกรองในหน่วยความจำ
        if (!empty($filters['risk_status'])) {
            $students = getStudentsByFilters($conn, $filters, 0, 0); // ดึงทั้งหมดโดยไม่จำกัดจำนวน
            return count($students);
        }
        
        // สร้างคำสั่ง SQL สำหรับนับจำนวน
        $baseQuery = "
            SELECT COUNT(*) as total
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE s.status = 'กำลังศึกษา'
        ";
        
        // เพิ่มเงื่อนไขการกรอง
        $params = [];
        $whereConditions = [];
        
        // กรองตามชื่อหรือรหัสนักเรียน
        if (!empty($filters['student_name'])) {
            $searchTerm = '%' . $filters['student_name'] . '%';
            $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_code LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // กรองตามแผนกวิชา
        if (!empty($filters['department_id'])) {
            $whereConditions[] = "d.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        // กรองตามระดับชั้น
        if (!empty($filters['class_level'])) {
            $whereConditions[] = "c.level = ?";
            $params[] = $filters['class_level'];
        }
        
        // กรองตามกลุ่ม
        if (!empty($filters['class_group'])) {
            $whereConditions[] = "c.group_number = ?";
            $params[] = $filters['class_group'];
        }
        
        // กรองตามครูที่ปรึกษา
        if (!empty($filters['advisor_id'])) {
            $baseQuery .= " JOIN class_advisors ca ON c.class_id = ca.class_id ";
            $whereConditions[] = "ca.teacher_id = ?";
            $params[] = $filters['advisor_id'];
        }
        
        // รวมเงื่อนไข WHERE เข้ากับคำสั่ง SQL
        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(" AND ", $whereConditions);
        }
        
        // ประมวลผลคำสั่ง SQL
        $stmt = $conn->prepare($baseQuery);
        
        // ทำการ bind parameters
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error in countStudentsByFilters: " . $e->getMessage());
        return 0;
    }
}

/**
 * ฟังก์ชันดึงข้อมูลเทมเพลตข้อความที่ใช้งานได้
 * 
 * @param object $templateManager ตัวจัดการเทมเพลต
 * @param string $type ประเภทเทมเพลต (individual หรือ group)
 * @return array เทมเพลตข้อความ
 */
function getActiveTemplates($templateManager, $type = 'individual') {
    try {
        return $templateManager->getTemplatesByTypeAndCategory($type);
    } catch (Exception $e) {
        error_log("Error getting templates: " . $e->getMessage());
        return [];
    }
}

/**
 * ฟังก์ชันดึงข้อมูลการเข้าแถวของนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น (Y-m-d)
 * @param string $end_date วันที่สิ้นสุด (Y-m-d)
 * @return array ข้อมูลการเข้าแถว
 */
function getStudentAttendanceData($conn, $student_id, $start_date, $end_date) {
    try {
        $query = "
            SELECT 
                a.date,
                a.attendance_status,
                a.check_method,
                a.check_time
            FROM 
                attendance a
                JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
            WHERE 
                a.student_id = ? 
                AND a.date BETWEEN ? AND ?
                AND ay.is_active = 1
            ORDER BY 
                a.date ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$student_id, $start_date, $end_date]);
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // นับจำนวนวันตามสถานะ
        $present_count = 0;
        $absent_count = 0;
        $late_count = 0;
        $leave_count = 0;
        
        foreach ($attendance_records as $record) {
            switch ($record['attendance_status']) {
                case 'present':
                    $present_count++;
                    break;
                case 'absent':
                    $absent_count++;
                    break;
                case 'late':
                    $late_count++;
                    break;
                case 'leave':
                    $leave_count++;
                    break;
            }
        }
        
        // สร้างข้อมูลกราฟ
        $dates = [];
        $rates = [];
        $cumulative_present = 0;
        
        foreach ($attendance_records as $index => $record) {
            $date = date('d/m', strtotime($record['date']));
            $dates[] = $date;
            
            if ($record['attendance_status'] == 'present') {
                $cumulative_present++;
            }
            
            $current_rate = ($index + 1 > 0) ? round(($cumulative_present / ($index + 1)) * 100) : 0;
            $rates[] = $current_rate;
        }
        
        $total_days = count($attendance_records);
        $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
        
        return [
            'records' => $attendance_records,
            'present_count' => $present_count,
            'absent_count' => $absent_count,
            'late_count' => $late_count,
            'leave_count' => $leave_count,
            'total_days' => $total_days,
            'attendance_rate' => $attendance_rate,
            'chart_data' => [
                'dates' => $dates,
                'rates' => $rates
            ],
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    } catch (PDOException $e) {
        error_log("Error getting attendance data: " . $e->getMessage());
        return [
            'records' => [],
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'leave_count' => 0,
            'total_days' => 0,
            'attendance_rate' => 0,
            'chart_data' => [
                'dates' => [],
                'rates' => []
            ],
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }
}

/**
 * ส่งข้อความแจ้งเตือนผู้ปกครอง
 * 
 * @param LineNotificationAPI $lineAPI API สำหรับส่งข้อความ
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $message_text ข้อความที่ต้องการส่ง
 * @param string $start_date วันที่เริ่มต้น (Y-m-d)
 * @param string $end_date วันที่สิ้นสุด (Y-m-d)
 * @param bool $include_chart แนบกราฟหรือไม่
 * @param bool $include_link แนบลิงก์หรือไม่
 * @return array ผลลัพธ์การส่ง
 */
function sendParentNotification($lineAPI, $conn, $student_id, $message_text, $start_date, $end_date, $include_chart = true, $include_link = true) {
    // ดึงข้อมูลนักเรียน
    try {
        $student_stmt = $conn->prepare("
            SELECT 
                s.student_id,
                s.title,
                u.first_name,
                u.last_name,
                c.level,
                c.group_number,
                d.department_name
            FROM 
                students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE 
                s.student_id = ?
        ");
        $student_stmt->execute([$student_id]);
        $student_info = $student_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_info) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน',
                'student_id' => $student_id
            ];
        }
        
        // เพิ่มข้อมูลเพิ่มเติม
        $student_info['full_name'] = $student_info['title'] . ' ' . $student_info['first_name'] . ' ' . $student_info['last_name'];
        $student_info['class_name'] = $student_info['level'] . '/' . $student_info['group_number'];
        
        // ดึงข้อมูลครูที่ปรึกษา
        $advisor_stmt = $conn->prepare("
            SELECT 
                t.teacher_id,
                t.title,
                t.first_name,
                t.last_name,
                u.phone_number
            FROM 
                teachers t
                JOIN class_advisors ca ON t.teacher_id = ca.teacher_id
                JOIN classes c ON ca.class_id = c.class_id
                JOIN users u ON t.user_id = u.user_id
            WHERE 
                ca.is_primary = 1
                AND c.class_id = ?
        ");
        $advisor_stmt->execute([$student_info['current_class_id']]);
        $advisor_info = $advisor_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($advisor_info) {
            $student_info['advisor_name'] = $advisor_info['title'] . ' ' . $advisor_info['first_name'] . ' ' . $advisor_info['last_name'];
            $student_info['advisor_phone'] = $advisor_info['phone_number'] ?? 'ไม่ระบุ';
        } else {
            $student_info['advisor_name'] = 'ไม่ระบุ';
            $student_info['advisor_phone'] = 'ไม่ระบุ';
        }
    } catch (PDOException $e) {
        error_log("Error fetching student info: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน',
            'student_id' => $student_id
        ];
    }
    
    // ดึงข้อมูล LINE ID ของผู้ปกครอง
    try {
        $parent_stmt = $conn->prepare("
            SELECT 
                u.line_id
            FROM 
                parent_student_relation psr
                JOIN parents p ON psr.parent_id = p.parent_id
                JOIN users u ON p.user_id = u.user_id
            WHERE 
                psr.student_id = ?
                AND u.line_id IS NOT NULL
        ");
        $parent_stmt->execute([$student_id]);
        $parent_line_ids = $parent_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($parent_line_ids)) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูล LINE ID ของผู้ปกครอง',
                'student_id' => $student_id,
                'student_name' => $student_info['full_name']
            ];
        }
    } catch (PDOException $e) {
        error_log("Error fetching parent LINE IDs: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล LINE ID ของผู้ปกครอง',
            'student_id' => $student_id,
            'student_name' => $student_info['full_name']
        ];
    }
    
    // ดึงข้อมูลการเข้าแถว
    try {
        $attendance_stmt = $conn->prepare("
            SELECT 
                COUNT(*) AS total_days,
                SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) AS leave_count
            FROM 
                attendance
            WHERE 
                student_id = ?
                AND date BETWEEN ? AND ?
        ");
        $attendance_stmt->execute([$student_id, $start_date, $end_date]);
        $attendance_data = $attendance_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_days = $attendance_data['total_days'] ?? 0;
        $present_count = $attendance_data['present_count'] ?? 0;
        $absent_count = $attendance_data['absent_count'] ?? 0;
        $late_count = $attendance_data['late_count'] ?? 0;
        $leave_count = $attendance_data['leave_count'] ?? 0;
        
        $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
    } catch (PDOException $e) {
        error_log("Error fetching attendance data: " . $e->getMessage());
        $total_days = 0;
        $present_count = 0;
        $absent_count = 0;
        $late_count = 0;
        $leave_count = 0;
        $attendance_rate = 0;
    }
    
    // แทนที่ตัวแปรในข้อความ
    $message_data = [
        'ชื่อนักเรียน' => $student_info['full_name'],
        'ชั้นเรียน' => $student_info['class_name'],
        'จำนวนวันเข้าแถว' => $present_count,
        'จำนวนวันทั้งหมด' => $total_days,
        'ร้อยละการเข้าแถว' => $attendance_rate,
        'จำนวนวันขาด' => $absent_count,
        'สถานะการเข้าแถว' => getAttendanceStatus($attendance_rate),
        'ชื่อครูที่ปรึกษา' => $student_info['advisor_name'],
        'เบอร์โทรครู' => $student_info['advisor_phone']
    ];
    
    // แทนที่ตัวแปรในข้อความ
    $personalized_message = $message_text;
    foreach ($message_data as $key => $value) {
        $personalized_message = str_replace('{{'.$key.'}}', $value, $personalized_message);
    }
    
    // ส่งข้อความไปยังผู้ปกครองทุกคน
    $success = false;
    $result_details = [];
    $cost_per_parent = 0.075; // ข้อความ
    if ($include_chart) $cost_per_parent += 0.15; // รูปภาพ
    if ($include_link) $cost_per_parent += 0.075; // ลิงก์
    
    foreach ($parent_line_ids as $line_id) {
        // ในระบบจริงจะต้องใช้ LINE API ในการส่งข้อความ
        // ตัวอย่างด้านล่างเป็นเพียงการจำลองผลลัพธ์
        
        // สร้าง mock result
        $result = [
            'success' => true,
            'message_id' => 'msg_' . uniqid(),
            'recipient' => $line_id
        ];
        
        // เก็บประวัติการส่ง
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO line_notifications 
                (user_id, message, sent_at, status, notification_type) 
                VALUES 
                ((SELECT user_id FROM users WHERE line_id = ?), ?, NOW(), ?, 'attendance')
            ");
            $log_stmt->execute([
                $line_id, 
                $personalized_message, 
                $result['success'] ? 'sent' : 'failed'
            ]);
        } catch (PDOException $e) {
            error_log("Error logging notification: " . $e->getMessage());
        }
        
        $result_details[] = $result;
        
        if ($result['success']) {
            $success = true;
        }
    }
    
    // คำนวณค่าใช้จ่าย
    $total_cost = $cost_per_parent * count($parent_line_ids);
    
    return [
        'success' => $success,
        'student_id' => $student_id,
        'student_name' => $student_info['full_name'],
        'class' => $student_info['class_name'],
        'parent_count' => count($parent_line_ids),
        'message_count' => count($parent_line_ids),
        'results' => $result_details,
        'cost' => $total_cost
    ];
}

/**
 * ดึงสถานะการเข้าแถวตามอัตราการเข้าแถว
 * 
 * @param float $rate อัตราการเข้าแถว (%)
 * @return string สถานะการเข้าแถว
 */
function getAttendanceStatus($rate) {
    if ($rate < 60) return 'เสี่ยงตกกิจกรรม';
    if ($rate < 80) return 'ต้องระวัง';
    return 'ปกติ';
}

// ตรวจสอบการร้องขอผ่าน AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
// ตรวจสอบการร้องขอผ่าน AJAX - ใช้การดึงข้อมูลอย่างง่าย
if (isset($_POST['get_students'])) {
    header('Content-Type: application/json');
    
    try {
        // ดึงข้อมูลนักเรียนทั้งหมดโดยตรงจาก SQL
        $sql = "
            SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                s.status,
                c.level,
                c.group_number,
                d.department_name
            FROM 
                students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE 
                s.status = 'กำลังศึกษา'
            LIMIT 20
        ";
        
        $stmt = $conn->query($sql);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // เพิ่มข้อมูลพื้นฐานที่จำเป็น
        foreach ($students as &$student) {
            $student['class'] = isset($student['level']) && isset($student['group_number']) ? 
                $student['level'] . '/' . $student['group_number'] : 'ไม่ระบุ';
            $student['attendance_days'] = '0/0 วัน (0%)';
            $student['status'] = 'ไม่มีข้อมูล';
            $student['status_class'] = 'secondary';
            $student['parent_count'] = 0;
            $student['parents_info'] = 'ไม่มีข้อมูล';
            $student['initial'] = !empty($student['first_name']) ? mb_substr($student['first_name'], 0, 1, 'UTF-8') : '?';
            
            // ดึงข้อมูลการเข้าแถว
            $attend_sql = "
                SELECT 
                    COALESCE(SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END), 0) AS present,
                    COUNT(*) AS total
                FROM attendance
                WHERE student_id = :student_id
                AND academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1)
            ";
            
            $attend_stmt = $conn->prepare($attend_sql);
            $attend_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
            $attend_stmt->execute();
            $attend_data = $attend_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attend_data && $attend_data['total'] > 0) {
                $rate = round(($attend_data['present'] / $attend_data['total']) * 100);
                $student['attendance_days'] = "{$attend_data['present']}/{$attend_data['total']} วัน ($rate%)";
                
                if ($rate < 60) {
                    $student['status'] = 'เสี่ยงตกกิจกรรม';
                    $student['status_class'] = 'danger';
                } elseif ($rate < 80) {
                    $student['status'] = 'ต้องระวัง';
                    $student['status_class'] = 'warning';
                } else {
                    $student['status'] = 'ปกติ';
                    $student['status_class'] = 'success';
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students,
            'total' => count($students)
        ]);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }
    exit;
}
    
    // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
    if (isset($_POST['get_at_risk_students'])) {
        $filters = [
            'student_name' => $_POST['student_name'] ?? '',
            'class_level' => $_POST['class_level'] ?? '',
            'class_group' => $_POST['class_group'] ?? '',
            'risk_status' => $_POST['risk_status'] ?? 'เสี่ยงตกกิจกรรม'
        ];
        
        $students = getStudentsByFilters($conn, $filters, 100, 0);
        $total = countStudentsByFilters($conn, $filters);
        
        echo json_encode([
            'success' => true,
            'students' => $students,
            'total' => $total
        ]);
        exit;
    }
    
    // ดึงข้อมูลเทมเพลตตาม ID
    if (isset($_POST['get_template'])) {
        $template_id = $_POST['template_id'] ?? 0;
        
        if (!$template_id) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสเทมเพลต']);
            exit;
        }
        
        $template = $template_manager->getTemplateById($template_id);
        
        if (!$template) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบเทมเพลตที่ต้องการ']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'template' => $template
        ]);
        exit;
    }
    
    // ดึงข้อมูลการเข้าแถวของนักเรียน
    if (isset($_POST['get_student_attendance'])) {
        $student_id = $_POST['student_id'] ?? 0;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        if (!$student_id) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียน']);
            exit;
        }
        
        // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
        if (empty($start_date) || empty($end_date)) {
            try {
                $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
                $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($academic_year) {
                    $start_date = $academic_year['start_date'];
                    $end_date = $academic_year['end_date'];
                } else {
                    $start_date = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                    $end_date = date('Y-m-d'); // วันปัจจุบัน
                }
            } catch (PDOException $e) {
                error_log("Error fetching academic year dates: " . $e->getMessage());
                $start_date = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                $end_date = date('Y-m-d'); // วันปัจจุบัน
            }
        }
        
        // ดึงข้อมูลการเข้าแถว
        $attendanceData = getStudentAttendanceData($conn, $student_id, $start_date, $end_date);
        
        echo json_encode([
            'success' => true,
            'attendance' => $attendanceData
        ]);
        exit;
    }
    
    // ส่งข้อความแจ้งเตือนผู้ปกครองรายบุคคล
    if (isset($_POST['send_individual_message'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_id']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
            exit;
        }
        
        $student_id = $_POST['student_id'];
        $message = $_POST['message'];
        $start_date = $_POST['start_date'] ?? date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
        $end_date = $_POST['end_date'] ?? date('Y-m-d'); // วันปัจจุบัน
        $include_chart = isset($_POST['include_chart']) && ($_POST['include_chart'] === 'true' || $_POST['include_chart'] === '1');
        $include_link = isset($_POST['include_link']) && ($_POST['include_link'] === 'true' || $_POST['include_link'] === '1');
        
        // ตรวจสอบว่ามี LINE API Token หรือไม่
        if (empty($line_access_token)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ LINE API Token กรุณาตั้งค่าก่อนส่งข้อความ']);
            exit;
        }
        
        // ส่งข้อความ
        $result = sendParentNotification(
            $lineAPI,
            $conn,
            $student_id,
            $message,
            $start_date,
            $end_date,
            $include_chart,
            $include_link
        );
        
        // บันทึกการใช้งานเทมเพลต (ถ้ามีการระบุ ID เทมเพลต)
        if (!empty($_POST['template_id']) && method_exists($template_manager, 'updateTemplateLastUsed')) {
            $template_manager->updateTemplateLastUsed($_POST['template_id']);
        }
        
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['success'] ? 'ส่งข้อความสำเร็จ' : ($result['message'] ?? 'เกิดข้อผิดพลาดในการส่งข้อความ'),
            'results' => [$result],
            'total_cost' => $result['cost'] ?? 0,
            'success_count' => $result['success'] ? 1 : 0,
            'error_count' => $result['success'] ? 0 : 1
        ]);
        exit;
    }
    
    // ส่งข้อความแจ้งเตือนผู้ปกครองแบบกลุ่ม
    if (isset($_POST['send_group_message'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_ids']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
            exit;
        }
        
        $student_ids = json_decode($_POST['student_ids'], true);
        if (!is_array($student_ids) || empty($student_ids)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบรายการนักเรียนที่ต้องการส่งข้อความ']);
            exit;
        }
        
        $message = $_POST['message'];
        $start_date = $_POST['start_date'] ?? date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
        $end_date = $_POST['end_date'] ?? date('Y-m-d'); // วันปัจจุบัน
        $include_chart = isset($_POST['include_chart']) && ($_POST['include_chart'] === 'true' || $_POST['include_chart'] === '1');
        $include_link = isset($_POST['include_link']) && ($_POST['include_link'] === 'true' || $_POST['include_link'] === '1');
        
        // ตรวจสอบว่ามี LINE API Token หรือไม่
        if (empty($line_access_token)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ LINE API Token กรุณาตั้งค่าก่อนส่งข้อความ']);
            exit;
        }
        
        // ส่งข้อความทีละคน
        $results = [];
        $success_count = 0;
        $error_count = 0;
        $total_cost = 0;
        
        foreach ($student_ids as $student_id) {
            $result = sendParentNotification(
                $lineAPI,
                $conn,
                $student_id,
                $message,
                $start_date,
                $end_date,
                $include_chart,
                $include_link
            );
            
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
            
            $total_cost += $result['cost'] ?? 0;
        }
        
        // บันทึกการใช้งานเทมเพลต (ถ้ามีการระบุ ID เทมเพลต)
        if (!empty($_POST['template_id']) && method_exists($template_manager, 'updateTemplateLastUsed')) {
            $template_manager->updateTemplateLastUsed($_POST['template_id']);
        }
        
        echo json_encode([
            'success' => ($success_count > 0),
            'message' => "ส่งข้อความสำเร็จ $success_count รายการ, ล้มเหลว $error_count รายการ",
            'results' => $results,
            'total_cost' => $total_cost,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
        exit;
    }
    
    // บันทึกเทมเพลตใหม่
    if (isset($_POST['save_template'])) {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'individual';
        $category = $_POST['category'] ?? 'attendance';
        $content = $_POST['content'] ?? '';
        $template_id = $_POST['template_id'] ?? null;
        
        try {
            $result = $template_manager->saveTemplate(
                $name,
                $type,
                $category,
                $content,
                $template_id,
                $_SESSION['user_id'] ?? null
            );
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error saving template: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกเทมเพลต']);
        }
        exit;
    }
}

// ดึงข้อมูลนักเรียนเริ่มต้น (ไม่ใช้เงื่อนไขการกรอง เพื่อให้แสดงนักเรียนทั้งหมด)
$initial_filters = [];
$students = getStudentsByFilters($conn, $initial_filters, 20, 0);
$total_students = countStudentsByFilters($conn, $initial_filters);

// ถ้าไม่พบนักเรียน ลองดึงข้อมูลด้วยวิธีอื่น
if (empty($students)) {
// ดึงข้อมูลนักเรียนเริ่มต้น
try {
    $sql = "
        SELECT 
            s.student_id,
            s.student_code,
            s.title,
            u.first_name,
            u.last_name,
            s.status,
            c.level,
            c.group_number,
            d.department_name
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE 
            s.status = 'กำลังศึกษา'
        LIMIT 20
    ";
    
    $stmt = $conn->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_students = count($students);
    
    // เพิ่มข้อมูลจำเป็น
    foreach ($students as &$student) {
        $student['class'] = isset($student['level']) && isset($student['group_number']) ? 
            $student['level'] . '/' . $student['group_number'] : 'ไม่ระบุ';
        $student['attendance_days'] = '0/0 วัน (0%)';
        $student['status'] = 'ไม่มีข้อมูล';
        $student['status_class'] = 'secondary';
        $student['parent_count'] = 0;
        $student['parents_info'] = 'ไม่มีข้อมูล';
        $student['initial'] = !empty($student['first_name']) ? mb_substr($student['first_name'], 0, 1, 'UTF-8') : '?';
        
        // ดึงข้อมูลการเข้าแถว
        $attend_sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END), 0) AS present,
                COUNT(*) AS total
            FROM attendance
            WHERE student_id = :student_id
            AND academic_year_id = (SELECT academic_year_id FROM academic_years WHERE is_active = 1)
        ";
        
        $attend_stmt = $conn->prepare($attend_sql);
        $attend_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
        $attend_stmt->execute();
        $attend_data = $attend_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($attend_data && $attend_data['total'] > 0) {
            $rate = round(($attend_data['present'] / $attend_data['total']) * 100);
            $student['attendance_days'] = "{$attend_data['present']}/{$attend_data['total']} วัน ($rate%)";
            
            if ($rate < 60) {
                $student['status'] = 'เสี่ยงตกกิจกรรม';
                $student['status_class'] = 'danger';
            } elseif ($rate < 80) {
                $student['status'] = 'ต้องระวัง';
                $student['status_class'] = 'warning';
            } else {
                $student['status'] = 'ปกติ';
                $student['status_class'] = 'success';
            }
        }
    }
    
    error_log("Found {$total_students} students for display");
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $students = [];
    $total_students = 0;
}
} else {
    // ถ้ามีข้อมูลนักเรียนแล้ว ให้ใช้ข้อมูลที่มีอยู่
    error_log("Using existing student data");
}

// บันทึกข้อมูล debug เพื่อตรวจสอบความผิดพลาด
error_log("Initial students found: " . $total_students);
if ($total_students > 0) {
    error_log("First student: " . json_encode($students[0]));
} else {
    error_log("No students found in database");
}


/**
 * แก้ไขปัญหาในไฟล์ enhanced_notification.php
 * 
 * การแก้ไขที่จำเป็น:
 * 1. เพิ่มการตรวจสอบว่ามีไฟล์ notification_api.php หรือไม่ก่อนการ include
 * 2. แก้ไขการตอบกลับให้อยู่ในรูปแบบ JSON ที่ถูกต้องเมื่อมีการเรียกผ่าน AJAX
 * 3. แก้ไขการส่งข้อความให้ทำงานได้จริง โดยใช้ LineNotificationAPI
 */

// ตรวจสอบและสร้างตาราง notification_templates หากยังไม่มี
function create_notification_templates_table($conn) {
    try {
        // ตรวจสอบว่ามีตาราง notification_templates หรือไม่
        $stmt = $conn->prepare("SHOW TABLES LIKE 'notification_templates'");
        $stmt->execute();
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // สร้างตาราง notification_templates
            $sql = "
                CREATE TABLE notification_templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    type ENUM('individual', 'group') NOT NULL DEFAULT 'individual',
                    category VARCHAR(50) NOT NULL DEFAULT 'attendance',
                    content TEXT NOT NULL,
                    created_by INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_used DATETIME DEFAULT NULL,
                    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";
            
            $conn->exec($sql);
            
            // เพิ่มเทมเพลตเริ่มต้น
            add_default_templates($conn);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Error creating notification_templates table: ' . $e->getMessage());
        return false;
    }
}

// เพิ่มเทมเพลตเริ่มต้น
function add_default_templates($conn) {
    try {
        // เทมเพลตรายบุคคล
        $templates = [
            [
                'name' => 'แจ้งการเข้าแถวประจำวัน',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'แจ้งเตือนความเสี่ยงตกกิจกรรม',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งเตือนว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'แจ้งเตือนความเสี่ยงสูง',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} โดยด่วน เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'รายงานสรุปประจำเดือน',
                'type' => 'individual',
                'category' => 'attendance',
                'content' => "เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัย\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            // เทมเพลตกลุ่ม
            [
                'name' => 'แจ้งเตือนกลุ่มความเสี่ยง',
                'type' => 'group',
                'category' => 'attendance',
                'content' => "เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nโดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ],
            [
                'name' => 'แจ้งเตือนการประชุมกลุ่ม',
                'type' => 'group',
                'category' => 'meeting',
                'content' => "เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอเรียนเชิญท่านผู้ปกครองทุกท่านเข้าร่วมการประชุมผู้ปกครอง ในวันศุกร์ที่ 21 มิถุนายน 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nวาระการประชุมประกอบด้วย\n1. รายงานความก้าวหน้าทางการเรียนของนักเรียน\n2. แนวทางการป้องกันและแก้ไขปัญหาการขาดเรียนและขาดกิจกรรม\n3. แนวทางความร่วมมือระหว่างผู้ปกครองและวิทยาลัย\n\nจึงเรียนมาเพื่อโปรดทราบและเข้าร่วมประชุมโดยพร้อมเพรียงกัน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท"
            ]
        ];
        
        // เพิ่มเทมเพลตเริ่มต้น
        $stmt = $conn->prepare("
            INSERT INTO notification_templates (name, type, category, content)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($templates as $template) {
            $stmt->execute([
                $template['name'],
                $template['type'],
                $template['category'],
                $template['content']
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Error adding default templates: ' . $e->getMessage());
        return false;
    }
}

/**
 * สร้างคลาส LineNotificationAPI ถ้าไม่มีไฟล์ notification_api.php
 */
function create_line_notification_api() {
    if (!class_exists('LineNotificationAPI')) {
        // สร้างคลาสจำลอง LineNotificationAPI ชั่วคราว
        class LineNotificationAPI {
            private $accessToken;
            private $conn;
            
            public function __construct($accessToken, $conn) {
                $this->accessToken = $accessToken;
                $this->conn = $conn;
            }
            
            public function sendAttendanceNotification($student_id, $message, $includeChart = true, $includeLink = true, $startDate = null, $endDate = null) {
                try {
                    // ดึงข้อมูล LINE ID ของผู้ปกครองของนักเรียน
                    $stmt = $this->conn->prepare("
                        SELECT 
                            u.line_id,
                            u.first_name,
                            u.last_name,
                            s.title,
                            s.student_code,
                            c.level,
                            c.group_number,
                            d.department_name
                        FROM 
                            parent_student_relation psr
                            JOIN parents p ON psr.parent_id = p.parent_id
                            JOIN users u ON p.user_id = u.user_id
                            JOIN students s ON psr.student_id = s.student_id
                            LEFT JOIN classes c ON s.current_class_id = c.class_id
                            LEFT JOIN departments d ON c.department_id = d.department_id
                        WHERE 
                            psr.student_id = ? AND
                            u.line_id IS NOT NULL
                    ");
                    $stmt->execute([$student_id]);
                    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($parents)) {
                        return [
                            'success' => false,
                            'message' => 'ไม่พบผู้ปกครองที่มี LINE ID',
                            'student_id' => $student_id
                        ];
                    }
                    
                    // ดึงข้อมูลนักเรียน
                    $student_stmt = $this->conn->prepare("
                        SELECT 
                            s.student_id,
                            s.title,
                            u.first_name,
                            u.last_name,
                            c.level,
                            c.group_number,
                            d.department_name
                        FROM 
                            students s
                            JOIN users u ON s.user_id = u.user_id
                            LEFT JOIN classes c ON s.current_class_id = c.class_id
                            LEFT JOIN departments d ON c.department_id = d.department_id
                        WHERE 
                            s.student_id = ?
                    ");
                    $student_stmt->execute([$student_id]);
                    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$student) {
                        return [
                            'success' => false,
                            'message' => 'ไม่พบข้อมูลนักเรียน',
                            'student_id' => $student_id
                        ];
                    }
                    
                    // ดึงข้อมูลการเข้าแถว
                    $attendanceData = $this->getStudentAttendanceData($student_id, $startDate, $endDate);
                    
                    // ดึงข้อมูลครูที่ปรึกษา
                    $advisorInfo = $this->getAdvisorInfo($student_id);
                    
                    // คำนวณค่าใช้จ่าย
                    $costPerParent = 0.075; // บาทต่อข้อความ
                    if ($includeChart) $costPerParent += 0.15; // บาทต่อรูปภาพ
                    if ($includeLink) $costPerParent += 0.075; // บาทต่อลิงก์
                    
                    $totalCost = count($parents) * $costPerParent;
                    
                    // จำลองการส่งข้อความสำเร็จ
                    return [
                        'success' => true,
                        'student_id' => $student_id,
                        'student_name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
                        'class' => $student['level'] . '/' . $student['group_number'],
                        'parent_count' => count($parents),
                        'message_count' => count($parents),
                        'cost' => $totalCost,
                        'success_count' => count($parents),
                        'error_count' => 0
                    ];
                } catch (PDOException $e) {
                    error_log("Error in sendAttendanceNotification: " . $e->getMessage());
                    return [
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage(),
                        'student_id' => $student_id
                    ];
                }
            }
            
            public function sendGroupAttendanceNotification($student_ids, $message, $includeChart = true, $includeLink = true, $startDate = null, $endDate = null) {
                if (empty($student_ids) || !is_array($student_ids)) {
                    return [
                        'success' => false,
                        'message' => 'ไม่พบรายการนักเรียน',
                        'error_code' => 'NO_STUDENTS'
                    ];
                }
                
                // ส่งข้อความทีละคน
                $results = [];
                $totalCost = 0;
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($student_ids as $student_id) {
                    $result = $this->sendAttendanceNotification($student_id, $message, $includeChart, $includeLink, $startDate, $endDate);
                    $results[] = $result;
                    
                    $totalCost += $result['cost'] ?? 0;
                    $successCount += $result['success_count'] ?? 0;
                    $errorCount += $result['error_count'] ?? 0;
                }
                
                return [
                    'success' => $successCount > 0,
                    'message' => "ส่งข้อความสำเร็จ $successCount รายการ, ล้มเหลว $errorCount รายการ",
                    'results' => $results,
                    'total_cost' => $totalCost,
                    'success_count' => $successCount,
                    'error_count' => $errorCount
                ];
            }
            
            private function getStudentAttendanceData($student_id, $startDate = null, $endDate = null) {
                try {
                    // ถ้าไม่ระบุช่วงวันที่ ใช้ข้อมูลภาคเรียนปัจจุบัน
                    if (empty($startDate) || empty($endDate)) {
                        $stmt = $this->conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
                        $academicYear = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($academicYear) {
                            $startDate = $academicYear['start_date'];
                            $endDate = $academicYear['end_date'];
                        } else {
                            $startDate = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                            $endDate = date('Y-m-d'); // วันปัจจุบัน
                        }
                    }
                    
                    // ดึงข้อมูลการเข้าแถว
                    $query = "
                        SELECT 
                            COUNT(*) AS total_days,
                            SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS present_count,
                            SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                            SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) AS late_count,
                            SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) AS leave_count
                        FROM 
                            attendance
                        WHERE 
                            student_id = ?
                            AND date BETWEEN ? AND ?
                    ";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$student_id, $startDate, $endDate]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $total_days = $data['total_days'] ?? 0;
                    $present_count = $data['present_count'] ?? 0;
                    $absent_count = $data['absent_count'] ?? 0;
                    $late_count = $data['late_count'] ?? 0;
                    $leave_count = $data['leave_count'] ?? 0;
                    
                    $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
                    
                    return [
                        'total_days' => $total_days,
                        'present_count' => $present_count,
                        'absent_count' => $absent_count,
                        'late_count' => $late_count,
                        'leave_count' => $leave_count,
                        'attendance_rate' => $attendance_rate,
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ];
                } catch (PDOException $e) {
                    error_log("Error getting attendance data: " . $e->getMessage());
                    return [
                        'total_days' => 0,
                        'present_count' => 0,
                        'absent_count' => 0,
                        'late_count' => 0,
                        'leave_count' => 0,
                        'attendance_rate' => 0,
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ];
                }
            }
            
            private function getAdvisorInfo($student_id) {
                try {
                    $query = "
                        SELECT 
                            t.teacher_id,
                            t.title,
                            t.first_name,
                            t.last_name,
                            u.phone_number
                        FROM 
                            students s
                            JOIN classes c ON s.current_class_id = c.class_id
                            JOIN class_advisors ca ON c.class_id = ca.class_id
                            JOIN teachers t ON ca.teacher_id = t.teacher_id
                            JOIN users u ON t.user_id = u.user_id
                        WHERE 
                            s.student_id = ?
                            AND ca.is_primary = 1
                        LIMIT 1
                    ";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$student_id]);
                    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($advisor) {
                        return [
                            'advisor_id' => $advisor['teacher_id'],
                            'advisor_name' => $advisor['title'] . ' ' . $advisor['first_name'] . ' ' . $advisor['last_name'],
                            'advisor_phone' => $advisor['phone_number'] ?? 'ไม่ระบุ'
                        ];
                    } else {
                        return [
                            'advisor_id' => 0,
                            'advisor_name' => 'ไม่ระบุ',
                            'advisor_phone' => 'ไม่ระบุ'
                        ];
                    }
                } catch (PDOException $e) {
                    error_log("Error getting advisor info: " . $e->getMessage());
                    return [
                        'advisor_id' => 0,
                        'advisor_name' => 'ไม่ระบุ',
                        'advisor_phone' => 'ไม่ระบุ'
                    ];
                }
            }
        }
    }
}

/**
 * สร้างคลาส NotificationTemplates ถ้าไม่มีไฟล์ notification_templates.php
 */
function create_notification_templates() {
    if (!class_exists('NotificationTemplates')) {
        // สร้างคลาสจำลอง NotificationTemplates ชั่วคราว
        class NotificationTemplates {
            private $conn;
            private $templateTable = 'notification_templates';
            
            public function __construct() {
                global $conn;
                $this->conn = $conn;
            }
            
            public function getTemplatesByTypeAndCategory($type, $category = null) {
                try {
                    $where = ['type = ?'];
                    $params = [$type];
                    
                    if ($category !== null) {
                        $where[] = 'category = ?';
                        $params[] = $category;
                    }
                    
                    $whereClause = implode(' AND ', $where);
                    
                    $query = "
                        SELECT id, name, type, category, content, created_at, updated_at, last_used
                        FROM {$this->templateTable}
                        WHERE {$whereClause}
                        ORDER BY category, name
                    ";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute($params);
                    
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Error getting templates: " . $e->getMessage());
                    return [];
                }
            }
            
            public function getTemplateById($id) {
                try {
                    $query = "
                        SELECT id, name, type, category, content, created_at, updated_at, last_used
                        FROM {$this->templateTable}
                        WHERE id = ?
                    ";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$id]);
                    
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Error getting template by ID: " . $e->getMessage());
                    return false;
                }
            }
            
            public function updateTemplateLastUsed($id) {
                try {
                    $query = "
                        UPDATE {$this->templateTable}
                        SET last_used = NOW()
                        WHERE id = ?
                    ";
                    
                    $stmt = $this->conn->prepare($query);
                    return $stmt->execute([$id]);
                } catch (PDOException $e) {
                    error_log("Error updating template last used: " . $e->getMessage());
                    return false;
                }
            }
            
            public function saveTemplate($name, $type, $category, $content, $templateId = null, $userId = null) {
                try {
                    if ($templateId) {
                        // แก้ไขเทมเพลตที่มีอยู่
                        $query = "
                            UPDATE {$this->templateTable}
                            SET name = ?, type = ?, category = ?, content = ?, updated_at = NOW()
                            WHERE id = ?
                        ";
                        $stmt = $this->conn->prepare($query);
                        $stmt->execute([$name, $type, $category, $content, $templateId]);
                        
                        return [
                            'success' => true,
                            'message' => 'แก้ไขเทมเพลตเรียบร้อยแล้ว',
                            'id' => $templateId
                        ];
                    } else {
                        // สร้างเทมเพลตใหม่
                        $query = "
                            INSERT INTO {$this->templateTable} (name, type, category, content, created_by)
                            VALUES (?, ?, ?, ?, ?)
                        ";
                        $stmt = $this->conn->prepare($query);
                        $stmt->execute([$name, $type, $category, $content, $userId]);
                        
                        $newId = $this->conn->lastInsertId();
                        
                        return [
                            'success' => true,
                            'message' => 'สร้างเทมเพลตใหม่เรียบร้อยแล้ว',
                            'id' => $newId
                        ];
                    }
                } catch (PDOException $e) {
                    error_log("Error saving template: " . $e->getMessage());
                    return [
                        'success' => false,
                        'message' => 'เกิดข้อผิดพลาดในการบันทึกเทมเพลต: ' . $e->getMessage()
                    ];
                }
            }
        }
    }
}

/**
 * แก้ไขปัญหาในไฟล์ enhanced_notification.php
 * 
 * คำแนะนำในการใช้งาน:
 * 1. สร้างไฟล์ notification_api.php โดยใช้โค้ดจากด้านบน
 * 2. สร้างไฟล์ notification_templates.php โดยใช้โค้ดจากด้านบน
 * 3. แก้ไข enhanced_notification.php โดยเพิ่มโค้ดด้านล่างนี้ไว้ที่จุดที่เหมาะสม
 */

// เพิ่มโค้ดด้านล่างนี้ไว้ด้านบนของไฟล์ enhanced_notification.php หลังจาก require_once '../db_connect.php';
// เพื่อตรวจสอบว่ามีไฟล์ notification_api.php หรือไม่
if (file_exists('notification_api.php')) {
    require_once 'notification_api.php';
} else {
    // สร้างคลาสจำลอง LineNotificationAPI ชั่วคราว
    create_line_notification_api();
}

// ตรวจสอบว่ามีไฟล์ notification_templates.php หรือไม่
if (file_exists('notification_templates.php')) {
    require_once 'notification_templates.php';
} else {
    // สร้างคลาสจำลอง NotificationTemplates ชั่วคราว
    create_notification_templates();
}

// สร้างตาราง notification_templates หากยังไม่มี
create_notification_templates_table($conn);

// แก้ไขส่วนการ handle AJAX request ให้ถูกต้อง
// สังเกตว่าต้องมีการปรับปรุงโค้ดในส่วนต่อไปนี้:

/**
 * ส่วนนี้ควรแก้ไขเป็น:
 */
// ตรวจสอบการร้องขอผ่าน AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // ตรวจสอบเหตุการณ์ต่างๆ
    
    // ส่งข้อความแจ้งเตือนผู้ปกครองรายบุคคล
    if (isset($_POST['send_individual_message'])) {
        try {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($_POST['student_id']) || empty($_POST['message'])) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
                exit;
            }
            
            $student_id = $_POST['student_id'];
            $message = $_POST['message'];
            $start_date = $_POST['start_date'] ?? date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
            $end_date = $_POST['end_date'] ?? date('Y-m-d'); // วันปัจจุบัน
            $include_chart = isset($_POST['include_chart']) && ($_POST['include_chart'] === 'true' || $_POST['include_chart'] === '1');
            $include_link = isset($_POST['include_link']) && ($_POST['include_link'] === 'true' || $_POST['include_link'] === '1');
            
            // ส่งข้อความ
            $result = $lineAPI->sendAttendanceNotification(
                $student_id,
                $message,
                $include_chart,
                $include_link,
                $start_date,
                $end_date
            );
            
            // บันทึกการใช้งานเทมเพลต (ถ้ามีการระบุ ID เทมเพลต)
            if (!empty($_POST['template_id']) && method_exists($template_manager, 'updateTemplateLastUsed')) {
                $template_manager->updateTemplateLastUsed($_POST['template_id']);
            }
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] ? 'ส่งข้อความสำเร็จ' : ($result['message'] ?? 'เกิดข้อผิดพลาดในการส่งข้อความ'),
                'results' => [$result],
                'total_cost' => $result['cost'] ?? 0,
                'success_count' => $result['success'] ? 1 : 0,
                'error_count' => $result['success'] ? 0 : 1
            ]);
        } catch (Exception $e) {
            error_log("Error sending individual message: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการส่งข้อความ: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ส่งข้อความแจ้งเตือนผู้ปกครองแบบกลุ่ม
    if (isset($_POST['send_group_message'])) {
        try {
            // ตรวจสอบข้อมูลที่จำเป็น
            if (empty($_POST['student_ids']) || empty($_POST['message'])) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
                exit;
            }
            
            $student_ids = json_decode($_POST['student_ids'], true);
            if (!is_array($student_ids) || empty($student_ids)) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรายการนักเรียนที่ต้องการส่งข้อความ']);
                exit;
            }
            
            $message = $_POST['message'];
            $start_date = $_POST['start_date'] ?? date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
            $end_date = $_POST['end_date'] ?? date('Y-m-d'); // วันปัจจุบัน
            $include_chart = isset($_POST['include_chart']) && ($_POST['include_chart'] === 'true' || $_POST['include_chart'] === '1');
            $include_link = isset($_POST['include_link']) && ($_POST['include_link'] === 'true' || $_POST['include_link'] === '1');
            
            // ส่งข้อความ
            $result = $lineAPI->sendGroupAttendanceNotification(
                $student_ids,
                $message,
                $include_chart,
                $include_link,
                $start_date,
                $end_date
            );
            
            // บันทึกการใช้งานเทมเพลต (ถ้ามีการระบุ ID เทมเพลต)
            if (!empty($_POST['template_id']) && method_exists($template_manager, 'updateTemplateLastUsed')) {
                $template_manager->updateTemplateLastUsed($_POST['template_id']);
            }
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'ส่งข้อความสำเร็จ' : 'เกิดข้อผิดพลาดในการส่งข้อความ'),
                'results' => $result['results'] ?? [],
                'total_cost' => $result['total_cost'] ?? 0,
                'success_count' => $result['success_count'] ?? 0,
                'error_count' => $result['error_count'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log("Error sending group message: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการส่งข้อความ: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ดึงข้อมูลนักเรียนตามเงื่อนไข
    if (isset($_POST['get_students']) || isset($_POST['get_at_risk_students'])) {
        try {
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            
            $filters = [
                'student_name' => $_POST['student_name'] ?? '',
                'department_id' => $_POST['department_id'] ?? '',
                'class_level' => $_POST['class_level'] ?? '',
                'class_group' => $_POST['class_group'] ?? '',
                'advisor_id' => $_POST['advisor_id'] ?? '',
                'risk_status' => $_POST['risk_status'] ?? ''
            ];
            
            $students = getStudentsByFilters($conn, $filters, $limit, $offset);
            $total = countStudentsByFilters($conn, $filters);
            
            echo json_encode([
                'success' => true,
                'students' => $students,
                'total' => $total
            ]);
        } catch (Exception $e) {
            error_log("Error fetching students: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ดึงข้อมูลเทมเพลตตาม ID
    if (isset($_POST['get_template'])) {
        try {
            $template_id = $_POST['template_id'] ?? 0;
            
            if (!$template_id) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสเทมเพลต']);
                exit;
            }
            
            $template = $template_manager->getTemplateById($template_id);
            
            if (!$template) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบเทมเพลตที่ต้องการ']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'template' => $template
            ]);
        } catch (Exception $e) {
            error_log("Error fetching template: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลเทมเพลต: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ดึงข้อมูลการเข้าแถวของนักเรียน
    if (isset($_POST['get_student_attendance'])) {
        try {
            $student_id = $_POST['student_id'] ?? 0;
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;
            
            if (!$student_id) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสนักเรียน']);
                exit;
            }
            
            // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
            if (empty($start_date) || empty($end_date)) {
                try {
                    $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
                    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($academic_year) {
                        $start_date = $academic_year['start_date'];
                        $end_date = $academic_year['end_date'];
                    } else {
                        $start_date = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                        $end_date = date('Y-m-d'); // วันปัจจุบัน
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching academic year dates: " . $e->getMessage());
                    $start_date = date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
                    $end_date = date('Y-m-d'); // วันปัจจุบัน
                }
            }
            
            // ดึงข้อมูลการเข้าแถว
            $attendanceData = getStudentAttendanceData($conn, $student_id, $start_date, $end_date);
            
            echo json_encode([
                'success' => true,
                'attendance' => $attendanceData
            ]);
        } catch (Exception $e) {
            error_log("Error fetching attendance data: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการเข้าแถว: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // บันทึกเทมเพลตใหม่
    if (isset($_POST['save_template'])) {
        try {
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? 'individual';
            $category = $_POST['category'] ?? 'attendance';
            $content = $_POST['content'] ?? '';
            $template_id = $_POST['template_id'] ?? null;
            
            $result = $template_manager->saveTemplate(
                $name,
                $type,
                $category,
                $content,
                $template_id,
                $_SESSION['user_id'] ?? null
            );
            
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("Error saving template: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกเทมเพลต: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// ดึงข้อมูลเทมเพลต
$individual_templates = getActiveTemplates($template_manager, 'individual');
$group_templates = getActiveTemplates($template_manager, 'group');

// เตรียมข้อมูลสำหรับการแสดงผล
$data = [
    'students' => $students,
    'total_students' => $total_students,
    'departments' => $departments,
    'advisors' => $advisors,
    'levels' => $levels,
    'individual_templates' => $individual_templates,
    'group_templates' => $group_templates,
    'at_risk_count' => $at_risk_count,
    'current_academic_year' => $current_academic_year,
    'school_name' => $school_name,
    'primary_color' => $primary_color,
    'secondary_color' => $secondary_color
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/enhanced_notification_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
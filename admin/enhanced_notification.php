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
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('line_access_token', 'school_name', 'primary_color', 'secondary_color')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$line_access_token = $settings['line_access_token'] ?? '';
$school_name = $settings['school_name'] ?? 'วิทยาลัยการอาชีพปราสาท';
$primary_color = $settings['primary_color'] ?? '#28a745';
$secondary_color = $settings['secondary_color'] ?? '#6c757d';

// ดึงข้อมูลแผนกวิชา
$stmt = $conn->query("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลครูที่ปรึกษา
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

// ดึงข้อมูลชั้นปี
$stmt = $conn->query("SELECT DISTINCT level FROM classes WHERE is_active = 1 ORDER BY level");
$levels = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$stmt = $conn->query("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1");
$current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
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

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/notification_enhanced.css'
];

$extra_js = [
    'assets/js/notification_enhanced.js',
    'assets/js/chart.min.js'  // เพิ่ม Chart.js สำหรับสร้างกราฟ
];

/**
 * ฟังก์ชันดึงข้อมูลนักเรียนตามเงื่อนไข
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @param int $limit จำนวนรายการที่ต้องการ
 * @param int $offset ตำแหน่งเริ่มต้น
 * @return array ข้อมูลนักเรียน
 */
function getStudentsByFilters($conn, $filters = [], $limit = 10, $offset = 0) {
    // Validate and sanitize input
    $limit = max(1, intval($limit));
    $offset = max(0, intval($offset));
    
    $params = [];
    $where_clauses = [];

    // Add default conditions
    $where_clauses[] = "s.status = 'กำลังศึกษา'";
    $where_clauses[] = "ay.is_active = 1";

    // Add filters with strict checking
    if (!empty($filters['department_id'])) {
        $where_clauses[] = "d.department_id = :department_id";
        $params[':department_id'] = $filters['department_id'];
    }

    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = :level";
        $params[':level'] = $filters['class_level'];
    }

    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = :group";
        $params[':group'] = $filters['class_group'];
    }

    if (!empty($filters['advisor_id'])) {
        $where_clauses[] = "ca.teacher_id = :advisor_id";
        $params[':advisor_id'] = $filters['advisor_id'];
    }

    // Filter by risk status
    if (!empty($filters['risk_status'])) {
        switch ($filters['risk_status']) {
            case 'เสี่ยงตกกิจกรรม':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 60";
                break;
            case 'ต้องระวัง':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 60 AND 80";
                break;
            case 'ปกติ':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 80";
                break;
        }
    }

    // Filter by attendance rate
    if (!empty($filters['attendance_rate'])) {
        switch ($filters['attendance_rate']) {
            case 'น้อยกว่า 70%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 70";
                break;
            case '70% - 80%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 70 AND 80";
                break;
            case '80% - 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 80 AND 90";
                break;
            case 'มากกว่า 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 90";
                break;
        }
    }

    // Filter by student name
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE :name OR u.last_name LIKE :name OR s.student_code LIKE :name)";
        $params[':name'] = '%' . $filters['student_name'] . '%';
    }

    // Combine WHERE clauses
    $where_sql = implode(" AND ", $where_clauses);

    // Main query with prepared statement
    $query = "
        SELECT 
            s.student_id,
            s.student_code,
            s.title,
            u.first_name,
            u.last_name,
            c.level,
            c.group_number,
            d.department_name,
            (
                SELECT GROUP_CONCAT(CONCAT(pu.first_name, ' ', pu.last_name, ' (', p.relationship, ')'))
                FROM parent_student_relation psr
                JOIN parents p ON psr.parent_id = p.parent_id
                JOIN users pu ON p.user_id = pu.user_id
                WHERE psr.student_id = s.student_id
            ) as parents_info,
            (
                SELECT COUNT(DISTINCT psr.parent_id)
                FROM parent_student_relation psr
                WHERE psr.student_id = s.student_id
            ) as parent_count,
            sar.total_attendance_days,
            sar.total_absence_days,
            CASE 
                WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                THEN ROUND((sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100, 2)
                ELSE 0 
            END as attendance_rate
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN departments d ON c.department_id = d.department_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id 
                AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
            LEFT JOIN class_advisors ca ON c.class_id = ca.class_id
        WHERE 
            $where_sql
        GROUP BY
            s.student_id
        ORDER BY 
            attendance_rate ASC
        LIMIT :offset, :limit
    ";

    // Add limit and offset parameters
    $params[':offset'] = $offset;
    $params[':limit'] = $limit;

    try {
        // Prepare and execute the statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters with proper types
        foreach ($params as $key => &$value) {
            if (strpos($key, ':limit') !== false || strpos($key, ':offset') !== false) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process each student to add more information
        foreach ($students as &$student) {
            $rate = $student['attendance_rate'];
            
            // Determine risk status
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
            
            // Format attendance details
            $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
            $student['attendance_days'] = "{$student['total_attendance_days']}/$total_days วัน (" . round($rate) . '%)';
            $student['class'] = $student['level'] . '/' . $student['group_number'];
            $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
            
            // Get class number (if applicable)
            $stmt = $conn->prepare("
                SELECT COUNT(*) + 1 as class_number
                FROM students s2
                JOIN classes c2 ON s2.current_class_id = c2.class_id
                WHERE c2.class_id = (SELECT current_class_id FROM students WHERE student_id = ?)
                AND s2.student_id < ?
            ");
            $stmt->execute([$student['student_id'], $student['student_id']]);
            $student['class_number'] = $stmt->fetchColumn();
        }

        return $students;
    } catch (PDOException $e) {
        // Log error for debugging
        error_log("Error in getStudentsByFilters: " . $e->getMessage());
        return [];
    }
}

/**
 * ฟังก์ชันนับจำนวนนักเรียนที่ตรงตามเงื่อนไข
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @return int จำนวนนักเรียน
 */
function countStudentsByFilters($conn, $filters = []) {
    $params = [];
    $where_clauses = [];

    // Add default conditions
    $where_clauses[] = "s.status = 'กำลังศึกษา'";
    $where_clauses[] = "ay.is_active = 1";

    // Add filters with strict checking
    if (!empty($filters['department_id'])) {
        $where_clauses[] = "d.department_id = :department_id";
        $params[':department_id'] = $filters['department_id'];
    }

    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = :level";
        $params[':level'] = $filters['class_level'];
    }

    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = :group";
        $params[':group'] = $filters['class_group'];
    }

    if (!empty($filters['advisor_id'])) {
        $where_clauses[] = "ca.teacher_id = :advisor_id";
        $params[':advisor_id'] = $filters['advisor_id'];
    }

    // Filter by risk status
    if (!empty($filters['risk_status'])) {
        switch ($filters['risk_status']) {
            case 'เสี่ยงตกกิจกรรม':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 60";
                break;
            case 'ต้องระวัง':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 60 AND 80";
                break;
            case 'ปกติ':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 80";
                break;
        }
    }

    // Filter by attendance rate
    if (!empty($filters['attendance_rate'])) {
        switch ($filters['attendance_rate']) {
            case 'น้อยกว่า 70%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) < 70";
                break;
            case '70% - 80%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 70 AND 80";
                break;
            case '80% - 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) BETWEEN 80 AND 90";
                break;
            case 'มากกว่า 90%':
                $where_clauses[] = "(CASE WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 
                                    ELSE 0 END) > 90";
                break;
        }
    }

    // Filter by student name
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE :name OR u.last_name LIKE :name OR s.student_code LIKE :name)";
        $params[':name'] = '%' . $filters['student_name'] . '%';
    }

    // Combine WHERE clauses
    $where_sql = implode(" AND ", $where_clauses);

    // Count query with prepared statement
    $query = "
        SELECT 
            COUNT(DISTINCT s.student_id) as total
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN departments d ON c.department_id = d.department_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id 
                AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
            LEFT JOIN class_advisors ca ON c.class_id = ca.class_id
        WHERE 
            $where_sql
    ";

    try {
        // Prepare and execute the statement
        $stmt = $conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => &$value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['total'];
    } catch (PDOException $e) {
        // Log error for debugging
        error_log("Error in countStudentsByFilters: " . $e->getMessage());
        return 0;
    }
}

/**
 * ฟังก์ชันดึงข้อมูลเทมเพลตข้อความที่ใช้งานได้
 * 
 * @param NotificationTemplates $templateManager ตัวจัดการเทมเพลต
 * @param string $type ประเภทเทมเพลต (individual หรือ group)
 * @return array เทมเพลตข้อความ
 */
function getActiveTemplates($templateManager, $type = 'individual') {
    return $templateManager->getTemplatesByTypeAndCategory($type);
}

// ตรวจสอบการร้องขอผ่าน AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // ดึงข้อมูลนักเรียนตามเงื่อนไข
    if (isset($_POST['get_students'])) {
        $filters = [
            'department_id' => $_POST['department_id'] ?? '',
            'class_level' => $_POST['class_level'] ?? '',
            'class_group' => $_POST['class_group'] ?? '',
            'advisor_id' => $_POST['advisor_id'] ?? '',
            'risk_status' => $_POST['risk_status'] ?? '',
            'attendance_rate' => $_POST['attendance_rate'] ?? '',
            'student_name' => $_POST['student_name'] ?? ''
        ];
        
        $limit = $_POST['limit'] ?? 20;
        $offset = $_POST['offset'] ?? 0;
        
        $students = getStudentsByFilters($conn, $filters, $limit, $offset);
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
            $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
            $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
            $start_date = $academic_year['start_date'];
            $end_date = $academic_year['end_date'];
        }
        
        // ดึงข้อมูลการเข้าแถว
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
        
        $total_days = $present_count + $absent_count + $late_count + $leave_count;
        $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
        
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
        
        // ถ้าไม่มีข้อมูล ใช้ข้อมูลตัวอย่าง
        if (empty($dates)) {
            $current_date = new DateTime();
            for ($i = 0; $i < 5; $i++) {
                $date = clone $current_date;
                $date->modify("-$i day");
                $dates[] = $date->format('d/m');
                $rates[] = rand(60, 90);
            }
            // เรียงลำดับวันที่
            $dates = array_reverse($dates);
        }
        
        echo json_encode([
            'success' => true,
            'attendance' => [
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
            ]
        ]);
        exit;
    }
    
    // ส่งข้อความแจ้งเตือน
    if (isset($_POST['send_notification'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($_POST['student_ids']) || empty($_POST['message'])) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
            exit;
        }
        
        $student_ids = json_decode($_POST['student_ids'], true);
        $message = $_POST['message'];
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $include_chart = ($_POST['include_chart'] === 'true');
        $include_link = ($_POST['include_link'] === 'true');
        $is_group = ($_POST['is_group'] === 'true');
        
        // ตรวจสอบว่ามี LINE API Token หรือไม่
        if (empty($line_access_token)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ LINE API Token กรุณาตั้งค่าก่อนส่งข้อความ']);
            exit;
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        $total_cost = 0;
        
        // ให้เรียกใช้ API จาก notification_api.php
        require_once 'notification_api.php';
        
        foreach ($student_ids as $student_id) {
            // ดึงข้อมูลนักเรียน
            $stmt = $conn->prepare("
                SELECT 
                    s.student_id, s.title, u.first_name, u.last_name,
                    c.level, c.group_number,
                    (
                        SELECT GROUP_CONCAT(pu.line_id)
                        FROM parent_student_relation psr
                        JOIN parents p ON psr.parent_id = p.parent_id
                        JOIN users pu ON p.user_id = pu.user_id
                        WHERE psr.student_id = s.student_id
                    ) as parent_line_ids
                FROM 
                    students s
                    JOIN users u ON s.user_id = u.user_id
                    JOIN classes c ON s.current_class_id = c.class_id
                WHERE 
                    s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                $results[] = [
                    'student_id' => $student_id,
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน',
                    'student_name' => "รหัส $student_id"
                ];
                $error_count++;
                continue;
            }
            
            // ตรวจสอบว่ามี LINE ID ของผู้ปกครองหรือไม่
            if (empty($student['parent_line_ids'])) {
                $results[] = [
                    'student_id' => $student_id,
                    'success' => false,
                    'message' => 'ไม่พบ LINE ID ของผู้ปกครอง',
                    'student_name' => $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']
                ];
                $error_count++;
                continue;
            }
            
            // ดึงข้อมูลครูที่ปรึกษา
            $stmt = $conn->prepare("
                SELECT 
                    t.title, t.first_name, t.last_name, u.phone_number
                FROM 
                    classes c
                    JOIN class_advisors ca ON c.class_id = ca.class_id
                    JOIN teachers t ON ca.teacher_id = t.teacher_id
                    JOIN users u ON t.user_id = u.user_id
                WHERE 
                    c.class_id = ? AND ca.is_primary = 1
            ");
            $stmt->execute([$student['current_class_id']]);
            $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $advisor_name = $advisor ? $advisor['title'] . ' ' . $advisor['first_name'] . ' ' . $advisor['last_name'] : 'ไม่พบข้อมูล';
            $advisor_phone = $advisor ? $advisor['phone_number'] : 'ไม่พบข้อมูล';
            
            // ดึงข้อมูลการเข้าแถว
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(CASE WHEN attendance_status = 'present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN attendance_status = 'absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN attendance_status = 'late' THEN 1 END) as late_count,
                    COUNT(CASE WHEN attendance_status = 'leave' THEN 1 END) as leave_count
                FROM 
                    attendance a
                    JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
                WHERE 
                    a.student_id = ? 
                    AND a.date BETWEEN ? AND ?
                    AND ay.is_active = 1
            ");
            $stmt->execute([$student_id, $start_date, $end_date]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_days = $attendance['present_count'] + $attendance['absent_count'] + $attendance['late_count'] + $attendance['leave_count'];
            $attendance_rate = ($total_days > 0) ? round(($attendance['present_count'] / $total_days) * 100, 2) : 0;
            
            // กำหนดสถานะการเข้าแถว
            $attendance_status = 'ปกติ';
            if ($attendance_rate < 60) {
                $attendance_status = 'เสี่ยงตกกิจกรรม';
            } elseif ($attendance_rate < 80) {
                $attendance_status = 'ต้องระวัง';
            }
            
            // แทนที่ตัวแปรในข้อความ
            $student_name = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
            $class_info = $student['level'] . '/' . $student['group_number'];
            
            $message_data = [
                'student_name' => $student_name,
                'class' => $class_info,
                'attendance_days' => $attendance['present_count'],
                'total_days' => $total_days,
                'attendance_rate' => round($attendance_rate),
                'absence_days' => $attendance['absent_count'],
                'attendance_status' => $attendance_status,
                'advisor_name' => $advisor_name,
                'advisor_phone' => $advisor_phone,
                'month' => date('m'),
                'year' => (int)date('Y') + 543 // พ.ศ.
            ];
            
            $personalized_message = replaceTemplateVariables($message, $message_data);
            
            // แปลง LINE ID จาก string เป็น array
            $parent_line_ids = explode(',', $student['parent_line_ids']);
            
            // สร้าง chart URL (ถ้าต้องการ)
            $chart_url = '';
            if ($include_chart) {
                $chart_url = generateAttendanceChart($conn, $student_id, $start_date, $end_date);
            }
            
            // สร้าง detail URL (ถ้าต้องการ)
            $detail_url = '';
            if ($include_link) {
                $detail_url = generateDetailUrl($student_id, $start_date, $end_date);
            }
            
            // ส่งข้อความแจ้งเตือนไปยังผู้ปกครองทุกคน
            $student_success = false;
            
            foreach ($parent_line_ids as $line_id) {
                if (empty($line_id)) continue;
                
                // สร้างข้อความที่จะส่ง
                $messages = [];
                
                // เพิ่มข้อความหลัก
                $messages[] = [
                    'type' => 'text',
                    'text' => $personalized_message
                ];
                
                // เพิ่มรูปภาพ (ถ้ามี)
                if (!empty($chart_url)) {
                    $messages[] = [
                        'type' => 'image',
                        'originalContentUrl' => $chart_url,
                        'previewImageUrl' => $chart_url
                    ];
                }
                
                // เพิ่มลิงก์ (ถ้ามี)
                if (!empty($detail_url)) {
                    $messages[] = [
                        'type' => 'template',
                        'altText' => 'ดูรายละเอียดเพิ่มเติม',
                        'template' => [
                            'type' => 'buttons',
                            'text' => 'ต้องการดูข้อมูลโดยละเอียดหรือไม่?',
                            'actions' => [
                                [
                                    'type' => 'uri',
                                    'label' => 'ดูรายละเอียด',
                                    'uri' => $detail_url
                                ]
                            ]
                        ]
                    ];
                }
                
                // ส่งข้อความและตรวจสอบผลลัพธ์
                $result = sendLineMessage($line_id, $messages, $line_access_token);
                
                if ($result['success']) {
                    $student_success = true;
                }
                
                // บันทึกประวัติการส่ง
                $stmt = $conn->prepare("
                    INSERT INTO line_notifications (
                        user_id, message, sent_at, status, notification_type, error_message
                    ) VALUES (
                        (SELECT user_id FROM users WHERE line_id = ?),
                        ?,
                        NOW(),
                        ?,
                        'attendance',
                        ?
                    )
                ");
                $stmt->execute([
                    $line_id,
                    $personalized_message,
                    $result['success'] ? 'sent' : 'failed',
                    $result['success'] ? '' : json_encode($result)
                ]);
            }
            
            // คำนวณค่าใช้จ่าย
            $message_cost = $line_message_cost;
            $chart_cost = $include_chart ? $line_image_cost : 0;
            $link_cost = $include_link ? $line_link_cost : 0;
            $cost_per_parent = $message_cost + $chart_cost + $link_cost;
            $total_parent_cost = $cost_per_parent * count($parent_line_ids);
            
            // เพิ่มค่าใช้จ่ายรวม
            $total_cost += $total_parent_cost;
            
            // เพิ่มผลลัพธ์
            $results[] = [
                'student_id' => $student_id,
                'student_name' => $student_name,
                'class' => $class_info,
                'success' => $student_success,
                'cost' => $total_parent_cost,
                'message_count' => count($messages),
                'parent_count' => count($parent_line_ids)
            ];
            
            if ($student_success) {
                $success_count++;
            } else {
                $error_count++;
            }
            
            // อัปเดตการใช้งานเทมเพลต (ถ้ามีการระบุ ID เทมเพลต)
            if (!empty($_POST['template_id'])) {
                $template_manager->updateTemplateLastUsed($_POST['template_id']);
            }
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
        
        $result = $template_manager->saveTemplate(
            $name,
            $type,
            $category,
            $content,
            $template_id,
            $_SESSION['user_id'] ?? null
        );
        
        echo json_encode($result);
        exit;
    }
}

// ดึงข้อมูลนักเรียนเริ่มต้น (เสี่ยงตกกิจกรรม)
$initial_filters = [
    'risk_status' => 'เสี่ยงตกกิจกรรม'
];
$students = getStudentsByFilters($conn, $initial_filters, 20, 0);
$total_students = countStudentsByFilters($conn, $initial_filters);

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
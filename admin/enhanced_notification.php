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
        $baseQuery .= " ORDER BY c.level, c.group_number, u.first_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // ประมวลผลคำสั่ง SQL
        $stmt = $conn->prepare($baseQuery);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // เพิ่มข้อมูลเพิ่มเติมสำหรับแต่ละนักเรียน
        foreach ($students as &$student) {
            // รวมข้อมูลชั้นเรียน
            $student['class'] = $student['level'] . '/' . $student['group_number'];
            
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

                        // กรองตามสถานะความเสี่ยง ถ้ามีการกำหนด
                        if (!empty($filters['risk_status']) && $student['status'] !== $filters['risk_status']) {
                            // เราจะกรองนักเรียนในหน่วยความจำ เนื่องจากเราต้องคำนวณสถานะก่อน
                            $student['filtered_out'] = true;
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
            $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        }

        // กรองนักเรียนที่ไม่ตรงตามเงื่อนไขความเสี่ยงออก
        $students = array_filter($students, function($student) {
            return empty($student['filtered_out']);
        });
        
        return array_values($students); // ปรับเป็น array เริ่มต้นจาก 0
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
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // สำหรับการกรองตามสถานะความเสี่ยง ต้องดึงข้อมูลทั้งหมดและกรองในหน่วยความจำ
        if (!empty($filters['risk_status'])) {
            $studentCount = 0;
            $students = getStudentsByFilters($conn, $filters, 100000, 0); // ดึงทั้งหมด
            return count($students);
        }
        
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
 * ฟังก์ชันแทนที่ตัวแปรในข้อความเทมเพลต
 * 
 * @param string $message ข้อความเทมเพลต
 * @param array $data ข้อมูลสำหรับแทนที่
 * @return string ข้อความที่แทนที่ตัวแปรแล้ว
 */
function replaceTemplateVariables($message, $data) {
    $patterns = [
        '/{{ชื่อนักเรียน}}/' => $data['student_name'] ?? '',
        '/{{ชั้นเรียน}}/' => $data['class'] ?? '',
        '/{{จำนวนวันเข้าแถว}}/' => $data['attendance_days'] ?? '0',
        '/{{จำนวนวันทั้งหมด}}/' => $data['total_days'] ?? '0',
        '/{{ร้อยละการเข้าแถว}}/' => $data['attendance_rate'] ?? '0',
        '/{{จำนวนวันขาด}}/' => $data['absence_days'] ?? '0',
        '/{{สถานะการเข้าแถว}}/' => $data['attendance_status'] ?? '',
        '/{{ชื่อครูที่ปรึกษา}}/' => $data['advisor_name'] ?? '',
        '/{{เบอร์โทรครู}}/' => $data['advisor_phone'] ?? ''
    ];
    
    return preg_replace(array_keys($patterns), array_values($patterns), $message);
}

/**
 * ฟังก์ชันสร้าง URL รูปภาพกราฟการเข้าแถว
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL รูปภาพกราฟ
 */
function generateAttendanceChart($conn, $student_id, $start_date, $end_date) {
    // ในระบบจริง ควรสร้างกราฟและบันทึกเป็นไฟล์ แล้วคืนค่า URL
    // ตัวอย่างการใช้งานจริง
    return "chart-api.php?student_id={$student_id}&start={$start_date}&end={$end_date}";
}

/**
 * ฟังก์ชันสร้าง URL หน้ารายละเอียดการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL หน้ารายละเอียด
 */
function generateDetailUrl($student_id, $start_date, $end_date) {
    // ในระบบจริง ควรสร้าง URL ที่ถูกต้อง
    return "attendance-detail.php?student_id={$student_id}&start={$start_date}&end={$end_date}";
}

/**
 * ฟังก์ชันส่งข้อความผ่าน LINE
 * 
 * @param string $line_id LINE ID ของผู้รับ
 * @param array $messages ข้อความที่ต้องการส่ง
 * @param string $access_token LINE Access Token
 * @return array ผลลัพธ์การส่ง
 */
function sendLineMessage($line_id, $messages, $access_token) {
    // ในระบบจริง ควรใช้ LINE Messaging API
    if (empty($line_id) || empty($messages) || empty($access_token)) {
        return ['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'];
    }
    
    // สร้างข้อมูลสำหรับส่งไปยัง LINE API
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];
    
    $data = [
        'to' => $line_id,
        'messages' => $messages
    ];
    
    // ส่งข้อมูลไปยัง LINE API
    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return ['success' => true, 'message_id' => uniqid()];
    } else {
        return ['success' => false, 'message' => 'ไม่สามารถส่งข้อความได้: ' . $response];
    }
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
        } catch (PDOException $e) {
            error_log("Error fetching attendance: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการเข้าแถว']);
        }
        exit;
    }
    
    // ส่งข้อความแจ้งเตือน - แก้ไขให้สามารถทำงานร่วมกับ JavaScript ได้ถูกต้อง
    if (isset($_POST['send_notification']) || isset($_POST['send_individual_message']) || isset($_POST['send_group_message'])) {
        // ตรวจสอบข้อมูลที่จำเป็น
        $student_ids = [];
        
        if (isset($_POST['send_individual_message'])) {
            // กรณีส่งข้อความรายบุคคล
            if (empty($_POST['student_id']) || empty($_POST['message'])) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
                exit;
            }
            $student_ids = [$_POST['student_id']];
            $message = $_POST['message'];
            $is_group = false;
        } else if (isset($_POST['send_group_message'])) {
            // กรณีส่งข้อความกลุ่ม
            if (empty($_POST['student_ids']) || empty($_POST['message'])) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
                exit;
            }
            $student_ids = json_decode($_POST['student_ids'], true);
            $message = $_POST['message'];
            $is_group = true;
        } else {
            // กรณีเรียกจากฟอร์มเดิม
            if (empty($_POST['student_ids']) || empty($_POST['message'])) {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุผู้รับและข้อความ']);
                exit;
            }
            $student_ids = json_decode($_POST['student_ids'], true);
            $message = $_POST['message'];
            $is_group = (isset($_POST['is_group']) && $_POST['is_group'] === 'true');
        }
        
        $start_date = $_POST['start_date'] ?? date('Y-m-01'); // วันแรกของเดือนปัจจุบัน
        $end_date = $_POST['end_date'] ?? date('Y-m-d'); // วันปัจจุบัน
        $include_chart = isset($_POST['include_chart']) && ($_POST['include_chart'] === 'true' || $_POST['include_chart'] === '1');
        $include_link = isset($_POST['include_link']) && ($_POST['include_link'] === 'true' || $_POST['include_link'] === '1');
        
        // ตรวจสอบว่ามี LINE API Token หรือไม่
        if (empty($line_access_token)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบ LINE API Token กรุณาตั้งค่าก่อนส่งข้อความ']);
            exit;
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        $total_cost = 0;
        
        foreach ($student_ids as $student_id) {
            // ดึงข้อมูลนักเรียน
            try {
                $stmt = $conn->prepare("
                    SELECT 
                        s.student_id, s.title, u.first_name, u.last_name,
                        c.level, c.group_number, s.current_class_id,
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
                    try {
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
                    } catch (PDOException $e) {
                        error_log("Error logging notification: " . $e->getMessage());
                    }
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
            } catch (PDOException $e) {
                error_log("Error processing student ID $student_id: " . $e->getMessage());
                $results[] = [
                    'student_id' => $student_id,
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการประมวลผลข้อมูล',
                    'student_name' => "รหัส $student_id"
                ];
                $error_count++;
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

// ดึงข้อมูลนักเรียนเริ่มต้น (เสี่ยงตกกิจกรรม)
$initial_filters = [
    'risk_status' => 'เสี่ยงตกกิจกรรม'
];
$students = getStudentsByFilters($conn, $initial_filters, 20, 0);
$total_students = countStudentsByFilters($conn, $initial_filters);

// ถ้าไม่มีข้อมูลนักเรียน ให้กำหนดค่าเริ่มต้นเป็น array ว่าง
if (empty($students)) {
    $students = [];
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
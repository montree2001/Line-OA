<?php
/**
 * at_risk.php - หน้าแสดงนักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

// ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// นำเข้าไฟล์ที่จำเป็น
require_once '../db_connect.php';
$db = getDB();

// รับค่าพารามิเตอร์การกรองข้อมูล (ถ้ามี)
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$class_level = isset($_GET['class_level']) ? $_GET['class_level'] : '';
$class_year = isset($_GET['class_year']) ? $_GET['class_year'] : '';
$class_room = isset($_GET['class_room']) ? $_GET['class_room'] : '';
$advisor = isset($_GET['advisor']) ? $_GET['advisor'] : '';
$min_attendance = isset($_GET['min_attendance']) ? (int)$_GET['min_attendance'] : '';
$max_attendance = isset($_GET['max_attendance']) ? (int)$_GET['max_attendance'] : '';
$attendance_rate = isset($_GET['attendance_rate']) ? $_GET['attendance_rate'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // จำนวนรายการต่อหน้า

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'at_risk';
$page_title = 'นักเรียนเสี่ยงตกกิจกรรม';
$page_header = 'นักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว';

// ดึงข้อมูลผู้ดูแลระบบจาก session
$admin_id = $_SESSION['user_id'];
// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ค้นหาข้อมูลผู้ดูแลระบบจากฐานข้อมูล
try {
    $stmt = $db->prepare("SELECT a.admin_id, a.first_name, a.last_name, a.role 
                         FROM admin_users a
                         WHERE a.admin_id = :admin_id");
    $stmt->execute(['admin_id' => $admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_data) {
        $admin_info = [
            'name' => $admin_data['first_name'] . ' ' . $admin_data['last_name'],
            'role' => $admin_data['role'],
            'initials' => mb_substr($admin_data['first_name'], 0, 1, 'UTF-8')
        ];
    } else {
        // ตั้งค่าข้อมูลผู้ดูแลระบบเริ่มต้น
        $admin_info = [
            'name' => 'ผู้ดูแลระบบ',
            'role' => 'เจ้าหน้าที่กิจการนักเรียน',
            'initials' => 'ผ'
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล
    $admin_info = [
        'name' => 'ผู้ดูแลระบบ',
        'role' => 'เจ้าหน้าที่กิจการนักเรียน',
        'initials' => 'ผ'
    ];
}

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ส่งรายงานกลุ่ม',
        'icon' => 'send',
        'onclick' => 'showBulkNotificationModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAtRiskReport()'
    ]
];

// ดึงข้อมูลการตั้งค่าความเสี่ยง
try {
    $risk_settings = [
        'low' => 80, // ค่าเริ่มต้น
        'medium' => 70,
        'high' => 60,
        'critical' => 50
    ];
    
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings 
                         WHERE setting_key IN ('risk_threshold_low', 'risk_threshold_medium', 
                         'risk_threshold_high', 'risk_threshold_critical')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($settings as $setting) {
        switch ($setting['setting_key']) {
            case 'risk_threshold_low':
                $risk_settings['low'] = (int)$setting['setting_value'];
                break;
            case 'risk_threshold_medium':
                $risk_settings['medium'] = (int)$setting['setting_value'];
                break;
            case 'risk_threshold_high':
                $risk_settings['high'] = (int)$setting['setting_value'];
                break;
            case 'risk_threshold_critical':
                $risk_settings['critical'] = (int)$setting['setting_value'];
                break;
        }
    }
} catch (PDOException $e) {
    // ใช้ค่าเริ่มต้นกรณีเกิดข้อผิดพลาด
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $db->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ถ้าไม่พบปีการศึกษาที่ active ให้ใช้ปีล่าสุด
        $stmt = $db->prepare("SELECT academic_year_id, year, semester FROM academic_years ORDER BY year DESC, semester DESC LIMIT 1");
        $stmt->execute();
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาด
    $academic_year = [
        'academic_year_id' => 1,
        'year' => date('Y') + 543, // พ.ศ. ปัจจุบัน
        'semester' => 1
    ];
}

// สร้างเงื่อนไข SQL สำหรับการกรองข้อมูล
$where_conditions = ["sar.academic_year_id = :academic_year_id"];
$params = [':academic_year_id' => $academic_year['academic_year_id']];

// กรองตามแผนกวิชา
if (!empty($department_id)) {
    $where_conditions[] = "d.department_id = :department_id";
    $params[':department_id'] = $department_id;
}

// กรองตามระดับชั้น
if (!empty($class_level)) {
    $where_conditions[] = "c.level = :class_level";
    $params[':class_level'] = $class_level;
}

// กรองตามชั้นปี (จากปีการศึกษา)
if (!empty($class_year)) {
    $where_conditions[] = "SUBSTRING(s.student_code, 1, 2) = :class_year";
    $params[':class_year'] = $class_year;
}

// กรองตามห้องเรียน
if (!empty($class_room)) {
    $where_conditions[] = "c.group_number = :class_room";
    $params[':class_room'] = $class_room;
}

// กรองตามครูที่ปรึกษา
if (!empty($advisor)) {
    $where_conditions[] = "t.teacher_id = :advisor";
    $params[':advisor'] = $advisor;
}

// กรองตามอัตราการเข้าแถวที่กำหนดเอง
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
// กรองตามอัตราการเข้าแถวแบบเดิม (ถ้ามี)
elseif (!empty($attendance_rate)) {
    // แยกช่วงอัตราการเข้าแถว (เช่น "ต่ำกว่า 60%", "60% - 70%", ฯลฯ)
    if ($attendance_rate == "ต่ำกว่า 60%") {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 60";
    } elseif ($attendance_rate == "60% - 70%") {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 60 AND 70";
    } elseif ($attendance_rate == "70% - 80%") {
        $where_conditions[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
    }
}

// เพิ่มเงื่อนไขนักเรียนที่เสี่ยงตกกิจกรรม (อัตราการเข้าแถวต่ำกว่า 80%)
$where_conditions[] = "(sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80";
$where_conditions[] = "s.status = 'กำลังศึกษา'";

// รวมเงื่อนไข SQL
$where_clause = implode(" AND ", $where_conditions);

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม จากฐานข้อมูล
try {
    // คำนวณจำนวนทั้งหมดเพื่อทำ pagination
    $count_sql = "SELECT COUNT(*) as count
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 JOIN student_academic_records sar ON s.student_id = sar.student_id
                 JOIN classes c ON s.current_class_id = c.class_id
                 JOIN departments d ON c.department_id = d.department_id
                 LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
                 LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
                 LEFT JOIN users tu ON t.user_id = tu.user_id
                 WHERE $where_clause";
    
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $total_pages = ceil($total_records / $per_page);
    
    // ปรับค่าหน้าปัจจุบันให้อยู่ในช่วงที่ถูกต้อง
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // ดึงข้อมูลนักเรียนตามหน้าที่เลือก
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
           ORDER BY (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) ASC
           LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    
    // เพิ่ม parameter สำหรับ LIMIT
    $params[':offset'] = $offset;
    $params[':per_page'] = $per_page;
    
    $stmt->execute($params);
    $at_risk_students_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงข้อมูลให้อยู่ในรูปแบบที่เหมาะสม
    $at_risk_students = [];
    foreach ($at_risk_students_data as $student) {
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $attendance_rate = $total_days > 0 ? 
            ($student['total_attendance_days'] / $total_days) * 100 : 0;
        
        // กำหนดสถานะการแจ้งเตือน
        $notification_status = 'ยังไม่แจ้ง';
        if ($student['notification_count'] > 0) {
            $notification_status = 'แจ้งแล้ว ' . $student['notification_count'] . ' ครั้ง';
        }
        
        // สร้างชื่อเต็ม
        $title = ($student['first_name'][0] == 'น' && strpos($student['first_name'], 'นาย') === 0) ? '' : 
                (strpos($student['first_name'], 'นางสาว') === 0 ? '' : ($student['first_name'][0] == 'เ' ? 'นางสาว' : 'นาย'));
        $full_name = $title . $student['first_name'] . ' ' . $student['last_name'];
        
        // สร้างข้อมูลชั้นเรียน
        $class = $student['level'] . '/' . $student['group_number'];
        
        // ตัวอักษรแรกของชื่อสำหรับ avatar
        $initial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        if (in_array($initial, ['น', 'เ'])) {
            $initial = mb_substr($student['first_name'], 3, 1, 'UTF-8');
        }
        
        $at_risk_students[] = [
            'id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'name' => $full_name,
            'initial' => $initial,
            'class' => $class,
            'department' => $student['department_name'],
            'attendance_rate' => round($attendance_rate, 1),
            'days_present' => $student['total_attendance_days'],
            'days_missed' => $student['total_absence_days'],
            'total_days' => $total_days,
            'advisor' => $student['advisor_name'] ?: 'ไม่ระบุ',
            'advisor_phone' => $student['advisor_phone'] ?: 'ไม่ระบุ',
            'notification_status' => $notification_status,
            'last_notification' => $student['last_notification'] ? date('d/m/Y', strtotime($student['last_notification'])) : '-'
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $at_risk_students = [];
    $total_pages = 1;
    $page = 1;
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php: " . $e->getMessage());
}

// ดึงข้อมูลนักเรียนที่ขาดแถวบ่อย
try {
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, 
           c.level, c.group_number, d.department_name,
           sar.total_attendance_days, sar.total_absence_days,
           CONCAT(tu.first_name, ' ', tu.last_name) as advisor_name,
           tu.phone_number as advisor_phone,
           (SELECT MAX(a.date) FROM attendance a 
            WHERE a.student_id = s.student_id AND a.attendance_status = 'absent') as last_absence_date
           FROM students s
           JOIN users u ON s.user_id = u.user_id
           JOIN student_academic_records sar ON s.student_id = sar.student_id
           JOIN classes c ON s.current_class_id = c.class_id
           JOIN departments d ON c.department_id = d.department_id
           LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
           LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
           LEFT JOIN users tu ON t.user_id = tu.user_id
           WHERE sar.academic_year_id = :academic_year_id
           AND sar.total_absence_days >= 5
           AND s.status = 'กำลังศึกษา'
           ORDER BY sar.total_absence_days DESC
           LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $frequently_absent_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงข้อมูลให้อยู่ในรูปแบบที่เหมาะสม
    $frequently_absent = [];
    foreach ($frequently_absent_data as $student) {
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $attendance_rate = $total_days > 0 ? 
            ($student['total_attendance_days'] / $total_days) * 100 : 0;
        
        // สร้างชื่อเต็ม
        $title = ($student['first_name'][0] == 'น' && strpos($student['first_name'], 'นาย') === 0) ? '' : 
                (strpos($student['first_name'], 'นางสาว') === 0 ? '' : ($student['first_name'][0] == 'เ' ? 'นางสาว' : 'นาย'));
        $full_name = $title . $student['first_name'] . ' ' . $student['last_name'];
        
        // สร้างข้อมูลชั้นเรียน
        $class = $student['level'] . '/' . $student['group_number'];
        
        // ตัวอักษรแรกของชื่อสำหรับ avatar
        $initial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        if (in_array($initial, ['น', 'เ'])) {
            $initial = mb_substr($student['first_name'], 3, 1, 'UTF-8');
        }
        
        $frequently_absent[] = [
            'id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'name' => $full_name,
            'initial' => $initial,
            'class' => $class,
            'department' => $student['department_name'],
            'attendance_rate' => round($attendance_rate, 1),
            'days_present' => $student['total_attendance_days'],
            'days_missed' => $student['total_absence_days'],
            'total_days' => $total_days,
            'advisor' => $student['advisor_name'] ?: 'ไม่ระบุ',
            'advisor_phone' => $student['advisor_phone'] ?: 'ไม่ระบุ',
            'last_absence_date' => $student['last_absence_date'] ? date('d/m/Y', strtotime($student['last_absence_date'])) : '-'
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $frequently_absent = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (frequently_absent): " . $e->getMessage());
}

// ดึงข้อมูลนักเรียนที่รอการแจ้งเตือน
try {
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, 
           c.level, c.group_number, d.department_name,
           sar.total_attendance_days, sar.total_absence_days,
           CONCAT(tu.first_name, ' ', tu.last_name) as advisor_name,
           tu.phone_number as advisor_phone
           FROM students s
           JOIN users u ON s.user_id = u.user_id
           JOIN student_academic_records sar ON s.student_id = sar.student_id
           JOIN classes c ON s.current_class_id = c.class_id
           JOIN departments d ON c.department_id = d.department_id
           LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
           LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
           LEFT JOIN users tu ON t.user_id = tu.user_id
           LEFT JOIN (
               SELECT related_student_id, COUNT(*) as notification_count
               FROM notifications
               WHERE type = 'risk_alert'
               GROUP BY related_student_id
           ) n ON s.student_id = n.related_student_id
           WHERE sar.academic_year_id = :academic_year_id
           AND (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) * 100 < 80
           AND s.status = 'กำลังศึกษา'
           AND (n.notification_count IS NULL OR n.notification_count = 0)
           ORDER BY (sar.total_attendance_days / GREATEST(1, (sar.total_attendance_days + sar.total_absence_days))) ASC
           LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $pending_notification_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงข้อมูลให้อยู่ในรูปแบบที่เหมาะสม
    $pending_notification = [];
    foreach ($pending_notification_data as $student) {
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $attendance_rate = $total_days > 0 ? 
            ($student['total_attendance_days'] / $total_days) * 100 : 0;
        
        // กำหนดระดับความเร่งด่วน
        $urgency = 'ปานกลาง';
        if ($attendance_rate < $risk_settings['high']) {
            $urgency = 'สูง';
        } elseif ($attendance_rate < $risk_settings['medium']) {
            $urgency = 'ปานกลาง';
        } elseif ($attendance_rate < $risk_settings['low']) {
            $urgency = 'ต่ำ';
        }
        
        // สร้างชื่อเต็ม
        $title = ($student['first_name'][0] == 'น' && strpos($student['first_name'], 'นาย') === 0) ? '' : 
                (strpos($student['first_name'], 'นางสาว') === 0 ? '' : ($student['first_name'][0] == 'เ' ? 'นางสาว' : 'นาย'));
        $full_name = $title . $student['first_name'] . ' ' . $student['last_name'];
        
        // สร้างข้อมูลชั้นเรียน
        $class = $student['level'] . '/' . $student['group_number'];
        
        // ตัวอักษรแรกของชื่อสำหรับ avatar
        $initial = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        if (in_array($initial, ['น', 'เ'])) {
            $initial = mb_substr($student['first_name'], 3, 1, 'UTF-8');
        }
        
        $pending_notification[] = [
            'id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'name' => $full_name,
            'initial' => $initial,
            'class' => $class,
            'department' => $student['department_name'],
            'attendance_rate' => round($attendance_rate, 1),
            'days_present' => $student['total_attendance_days'],
            'days_missed' => $student['total_absence_days'],
            'total_days' => $total_days,
            'advisor' => $student['advisor_name'] ?: 'ไม่ระบุ',
            'advisor_phone' => $student['advisor_phone'] ?: 'ไม่ระบุ',
            'urgency' => $urgency
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $pending_notification = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (pending_notification): " . $e->getMessage());
}

// ดึงข้อมูลระดับชั้นและเครื่องกรอง
try {
    // ดึงข้อมูลแผนกวิชา
    $stmt = $db->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลระดับชั้น
    $stmt = $db->prepare("SELECT DISTINCT level FROM classes WHERE academic_year_id = :academic_year_id ORDER BY level");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $class_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึงข้อมูลชั้นปี (จากรหัสนักเรียน 2 หลักแรก - ปี พ.ศ.)
    $stmt = $db->prepare("SELECT DISTINCT SUBSTRING(s.student_code, 1, 2) as class_year
                           FROM students s
                           JOIN student_academic_records sar ON s.student_id = sar.student_id
                           WHERE sar.academic_year_id = :academic_year_id
                           AND s.status = 'กำลังศึกษา'
                           ORDER BY class_year DESC");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $class_years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึงข้อมูลกลุ่มเรียน
    $stmt = $db->prepare("SELECT DISTINCT group_number FROM classes WHERE academic_year_id = :academic_year_id ORDER BY group_number");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $class_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ดึงข้อมูลครูที่ปรึกษา
    $stmt = $db->prepare("SELECT t.teacher_id, u.title, u.first_name, u.last_name 
                         FROM teachers t 
                         JOIN users u ON t.user_id = u.user_id 
                         JOIN class_advisors ca ON t.teacher_id = ca.teacher_id
                         JOIN classes c ON ca.class_id = c.class_id
                         WHERE c.academic_year_id = :academic_year_id
                         GROUP BY t.teacher_id
                         ORDER BY u.first_name, u.last_name");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $departments = [];
    $class_levels = [];
    $class_years = [];
    $class_rooms = [];
    $advisors = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (filters): " . $e->getMessage());
}

// ดึงข้อมูลกราฟอัตราการเข้าแถวตามระดับชั้น
try {
    $stmt = $db->prepare("SELECT c.level, 
                         SUM(sar.total_attendance_days) as total_present, 
                         SUM(sar.total_absence_days) as total_absent
                         FROM student_academic_records sar
                         JOIN students s ON sar.student_id = s.student_id
                         JOIN classes c ON s.current_class_id = c.class_id
                         WHERE sar.academic_year_id = :academic_year_id
                         AND s.status = 'กำลังศึกษา'
                         GROUP BY c.level
                         ORDER BY c.level");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $attendance_by_level_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $attendance_by_level = [];
    foreach ($attendance_by_level_data as $level_data) {
        $total_days = $level_data['total_present'] + $level_data['total_absent'];
        $rate = $total_days > 0 ? 
            ($level_data['total_present'] / $total_days) * 100 : 0;
        
        $attendance_by_level[$level_data['level']] = round($rate, 1);
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $attendance_by_level = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (chart): " . $e->getMessage());
}

// ดึงข้อมูลกราฟอัตราการเข้าแถวตามแผนก
try {
    $stmt = $db->prepare("SELECT d.department_name, 
                         SUM(sar.total_attendance_days) as total_present, 
                         SUM(sar.total_absence_days) as total_absent
                         FROM student_academic_records sar
                         JOIN students s ON sar.student_id = s.student_id
                         JOIN classes c ON s.current_class_id = c.class_id
                         JOIN departments d ON c.department_id = d.department_id
                         WHERE sar.academic_year_id = :academic_year_id
                         AND s.status = 'กำลังศึกษา'
                         GROUP BY d.department_name
                         ORDER BY d.department_name");
    $stmt->execute([':academic_year_id' => $academic_year['academic_year_id']]);
    $attendance_by_department_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $attendance_by_department = [];
    foreach ($attendance_by_department_data as $dept_data) {
        $total_days = $dept_data['total_present'] + $dept_data['total_absent'];
        $rate = $total_days > 0 ? 
            ($dept_data['total_present'] / $total_days) * 100 : 0;
        
        $attendance_by_department[$dept_data['department_name']] = round($rate, 1);
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $attendance_by_department = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (department chart): " . $e->getMessage());
}

// ดึงข้อมูลเทมเพลตข้อความ
try {
    $stmt = $db->prepare("SELECT id, name, type, category, content FROM message_templates WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    $message_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $message_templates = [];
    // บันทึกข้อผิดพลาดลงใน log
    error_log("Database error in at_risk.php (templates): " . $e->getMessage());
}

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = count($at_risk_students);
$frequently_absent_count = count($frequently_absent);
$pending_notification_count = count($pending_notification);

// ไฟล์ CSS และ JS เพิ่มเติม
/*<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
*/
$extra_css = [
    'assets/css/at_risk.css',
    'assets/css/charts.css',
    'https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css',
    'https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css',
    'https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css'
];

/*
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
*/

$extra_js = [
    'assets/js/at_risk.js',
    'assets/js/charts.js',
    'https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js',
    'https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js',
    'https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js'
];

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'at_risk_students' => $at_risk_students,
    'frequently_absent' => $frequently_absent,
    'pending_notification' => $pending_notification,
    'at_risk_count' => $at_risk_count,
    'frequently_absent_count' => $frequently_absent_count,
    'pending_notification_count' => $pending_notification_count,
    'departments' => $departments,
    'class_levels' => $class_levels,
    'class_years' => $class_years,
    'class_rooms' => $class_rooms,
    'advisors' => $advisors,
    'attendance_by_level' => $attendance_by_level,
    'attendance_by_department' => $attendance_by_department,
    'message_templates' => $message_templates,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_records,
        'per_page' => $per_page
    ],
    'filters' => [
        'department_id' => $department_id,
        'class_level' => $class_level,
        'class_year' => $class_year,
        'class_room' => $class_room,
        'advisor' => $advisor,
        'min_attendance' => $min_attendance,
        'max_attendance' => $max_attendance,
        'attendance_rate' => $attendance_rate
    ],
    'academic_year' => $academic_year,
    'risk_settings' => $risk_settings
];





// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/at_risk_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';


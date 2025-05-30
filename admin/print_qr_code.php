<?php
/**
 * print_qr_code.php - หน้าพิมพ์ QR Code สำหรับนักเรียน
 * ระบบน้องชูใจ AI ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}
/* แสดงผล  Error*/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'print_qr_code';
$page_title = 'พิมพ์ QR Code นักเรียน';
$page_header = 'พิมพ์ QR Code สำหรับนักเรียน';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT a.admin_id, a.title, a.first_name, a.last_name, a.role FROM admin_users a WHERE a.admin_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $admin_info = [
                'name' => $user['title'] . $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role'] == 'super_admin' ? 'ผู้ดูแลระบบสูงสุด' : 'ผู้ดูแลระบบ',
                'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
            ];
        } else {
            $admin_info = [
                'name' => 'ผู้ดูแลระบบ',
                'role' => 'ผู้ดูแลระบบ',
                'initials' => 'A'
            ];
        }
        $can_view_all = true;
    } else {
        // ผู้ใช้เป็นครู - ดึงข้อมูลครูเพิ่มเติม
        $stmt = $conn->prepare("
            SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, 
                   t.position, d.department_name
            FROM users u
            JOIN teachers t ON u.user_id = t.user_id
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $admin_info = [
                'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
                'role' => $teacher['position'] . ' ' . $teacher['department_name'],
                'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
                'teacher_id' => $teacher['teacher_id']
            ];
        } else {
            $admin_info = [
                'name' => 'ครูผู้สอน',
                'role' => 'ครูผู้สอน',
                'initials' => 'T'
            ];
        }
        $can_view_all = false;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $admin_info = [
        'name' => 'ไม่พบข้อมูล',
        'role' => 'ไม่พบข้อมูล',
        'initials' => 'x'
    ];
}

// ดึงข้อมูลแผนกวิชาทั้งหมดที่ active
try {
    $stmt = $conn->prepare("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $departments = [];
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// ตัวแปรสำหรับเก็บผลลัพธ์การค้นหา
$students = [];
$selected_class = null;
$search_term = '';
$search_performed = false;
$selected_department = null;
$selected_level = null;
$selected_group = null;
$qr_validity = 7; // จำนวนวันที่ QR Code ใช้งานได้ (ค่าเริ่มต้น 7 วัน)
$message = '';
$error = '';


// แทนที่ส่วนการค้นหาใน print_qr_code.php (ประมาณบรรทัดที่ 144-180)

// ประมวลผลการค้นหา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $search_performed = true;
        
        // ตรวจสอบการค้นหา
        if (isset($_POST['search_term']) && !empty($_POST['search_term'])) {
            $search_term = trim($_POST['search_term']);
            
            // ค้นหาตามชื่อหรือรหัสนักเรียน
            $sql = "
                SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                       c.level, c.group_number, d.department_name, s.current_class_id
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE (s.student_code LIKE ? 
                       OR u.first_name LIKE ? 
                       OR u.last_name LIKE ?)
                      AND s.status = 'กำลังศึกษา'
            ";
            
            $params = ["%{$search_term}%", "%{$search_term}%", "%{$search_term}%"];
            
            // เพิ่มเงื่อนไขการค้นหาตามสิทธิ์ของผู้ใช้
            if (!$can_view_all && isset($admin_info['teacher_id'])) {
                $sql .= " AND c.class_id IN (SELECT ca.class_id FROM class_advisors ca WHERE ca.teacher_id = ?)";
                $params[] = $admin_info['teacher_id'];
            }
            
            $sql .= " ORDER BY c.level, d.department_name, c.group_number, u.first_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } elseif (isset($_POST['department_id']) && isset($_POST['level']) && isset($_POST['group_number'])) {
            // ค้นหาตามแผนก/ระดับชั้น/กลุ่ม
            $selected_department = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $selected_level = !empty($_POST['level']) ? $_POST['level'] : null;
            $selected_group = !empty($_POST['group_number']) ? $_POST['group_number'] : null;
            
            $sql = "
                SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                       c.level, c.group_number, d.department_name, s.current_class_id
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE s.status = 'กำลังศึกษา'
            ";
            
            $params = [];
            
            if ($selected_department) {
                $sql .= " AND c.department_id = ?";
                $params[] = $selected_department;
            }
            
            if ($selected_level) {
                $sql .= " AND c.level = ?";
                $params[] = $selected_level;
            }
            
            if ($selected_group) {
                $sql .= " AND c.group_number = ?";
                $params[] = $selected_group;
            }
            
            // เพิ่มเงื่อนไขการค้นหาตามสิทธิ์ของผู้ใช้
            if (!$can_view_all && isset($admin_info['teacher_id'])) {
                $sql .= " AND c.class_id IN (SELECT ca.class_id FROM class_advisors ca WHERE ca.teacher_id = ?)";
                $params[] = $admin_info['teacher_id'];
            }
            
            $sql .= " ORDER BY c.level, d.department_name, c.group_number, u.first_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } elseif (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
            // ค้นหาตามห้องเรียน
            $selected_class = $_POST['class_id'];
            
            $sql = "
                SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                       c.level, c.group_number, d.department_name, s.current_class_id
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ";
            
            $params = [$selected_class];
            
            // เพิ่มเงื่อนไขการค้นหาตามสิทธิ์ของผู้ใช้ (แม้ว่าจะเลือกห้องเรียนแล้วก็ตาม)
            if (!$can_view_all && isset($admin_info['teacher_id'])) {
                $sql .= " AND c.class_id IN (SELECT ca.class_id FROM class_advisors ca WHERE ca.teacher_id = ?)";
                $params[] = $admin_info['teacher_id'];
            }
            
            $sql .= " ORDER BY u.first_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // บันทึกค่า QR validity จากฟอร์ม
        if (isset($_POST['qr_validity']) && !empty($_POST['qr_validity'])) {
            $qr_validity = intval($_POST['qr_validity']);
            if ($qr_validity < 1) $qr_validity = 1;
            if ($qr_validity > 365) $qr_validity = 365;
        }
        
    } elseif (isset($_POST['generate_qr'])) {
        // สร้าง QR Code สำหรับนักเรียนที่เลือก
        if (isset($_POST['selected_students']) && !empty($_POST['selected_students'])) {
            $selected_students = $_POST['selected_students'];
            $qr_validity = intval($_POST['qr_validity'] ?? 7);
            
            if ($qr_validity < 1) $qr_validity = 1;
            if ($qr_validity > 365) $qr_validity = 365;
            
            $generated_count = 0;
            $failed_count = 0;
            
            foreach ($selected_students as $student_id) {
                try {
                    // ปิดการใช้งาน QR Code เก่าที่ยังใช้งานได้
                    $stmt = $conn->prepare("
                        UPDATE qr_codes 
                        SET is_active = 0 
                        WHERE student_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$student_id]);
                    
                    // ดึงข้อมูลนักเรียน
                    $stmt = $conn->prepare("
                        SELECT s.student_code, s.title, u.first_name, u.last_name 
                        FROM students s
                        JOIN users u ON s.user_id = u.user_id
                        WHERE s.student_id = ?
                    ");
                    $stmt->execute([$student_id]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$student) {
                        $failed_count++;
                        continue;
                    }
                    
                    // กำหนดเวลาหมดอายุ
                    $valid_from = new DateTime();
                    $valid_until = clone $valid_from;
                    $valid_until->add(new DateInterval('P' . $qr_validity . 'D')); // เพิ่มตามจำนวนวันที่กำหนด
                    
                    // สร้างข้อมูลสำหรับ QR Code
                    $token = hash('sha256', time() . $student_id . $student['student_code'] . rand(1000, 9999));
                    $qr_data = [
                        'type' => 'student_attendance',
                        'student_id' => (int)$student_id,
                        'student_code' => $student['student_code'],
                        'token' => $token,
                        'generated_at' => $valid_from->format('Y-m-d H:i:s'),
                        'expires_at' => $valid_until->format('Y-m-d H:i:s')
                    ];
                    
                    // บันทึกข้อมูล QR Code ลงฐานข้อมูล
                    $stmt = $conn->prepare("
                        INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, is_active, created_at)
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([
                        $student_id,
                        json_encode($qr_data),
                        $valid_from->format('Y-m-d H:i:s'),
                        $valid_until->format('Y-m-d H:i:s')
                    ]);
                    
                    $generated_count++;
                } catch (Exception $e) {
                    error_log("Error generating QR code: " . $e->getMessage());
                    $failed_count++;
                }
            }
            
            if ($generated_count > 0) {
                $message = "สร้าง QR Code สำเร็จ $generated_count รายการ";
                if ($failed_count > 0) {
                    $message .= " (ล้มเหลว $failed_count รายการ)";
                }
                
                // ดึงข้อมูลนักเรียนที่สร้าง QR Code สำเร็จ
                $placeholders = implode(',', array_fill(0, count($selected_students), '?'));
                $sql = "
                    SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                           c.level, c.group_number, d.department_name, s.current_class_id,
                           qc.qr_code_data, qc.valid_until
                    FROM students s
                    JOIN users u ON s.user_id = u.user_id
                    LEFT JOIN classes c ON s.current_class_id = c.class_id
                    LEFT JOIN departments d ON c.department_id = d.department_id
                    JOIN qr_codes qc ON s.student_id = qc.student_id
                    WHERE s.student_id IN ($placeholders)
                    AND qc.is_active = 1
                    ORDER BY c.level, d.department_name, c.group_number, u.first_name
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($selected_students);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // กำหนดว่าเป็นหน้าพิมพ์ QR Code
                $print_qr_codes = true;
            } else {
                $error = "ไม่สามารถสร้าง QR Code ได้";
            }
        } else {
            $error = "กรุณาเลือกนักเรียนที่ต้องการสร้าง QR Code";
        }
    }
}

// ดึงข้อมูลห้องเรียน
try {
    $class_sql = "
        SELECT c.class_id, c.level, c.group_number, d.department_name, 
               (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') as student_count
        FROM classes c
        JOIN departments d ON c.department_id = d.department_id
        WHERE c.academic_year_id = ?
    ";
    
    // เพิ่มเงื่อนไขการดึงข้อมูลตามสิทธิ์ของผู้ใช้
    if (!$can_view_all && isset($admin_info['teacher_id'])) {
        $class_sql .= " AND c.class_id IN (SELECT ca.class_id FROM class_advisors ca WHERE ca.teacher_id = ?)";
    }
    
    $class_sql .= " ORDER BY c.level, d.department_name, c.group_number";
    
    $stmt = $conn->prepare($class_sql);
    
    if (!$can_view_all && isset($admin_info['teacher_id'])) {
        $stmt->execute([$current_academic_year_id, $admin_info['teacher_id']]);
    } else {
        $stmt->execute([$current_academic_year_id]);
    }
    
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $classes = [];
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/print_qr_code.css'
];

$extra_js = [
    'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js',
    'assets/js/print_qr_code.js'
];

// ตรวจสอบว่าเป็นหน้าพิมพ์ QR Code หรือไม่
$print_mode = isset($_GET['print']) || (isset($print_qr_codes) && $print_qr_codes);

// ถ้าเป็นหน้าพิมพ์ให้ใช้ layout พิเศษ
if ($print_mode) {
    include 'pages/print_qr_code_layout.php';
    exit;
}

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/print_qr_code_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
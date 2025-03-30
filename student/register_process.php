<?php
/**
 * register_process.php - ไฟล์ประมวลผลฟอร์มการลงทะเบียนนักเรียน
 * ระบบเช็คชื่อเข้าแถวออนไลน์ STP-Prasat
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นการส่งฟอร์มจริงหรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ดึงการเชื่อมต่อฐานข้อมูล
$conn = getDB();

// ตรวจสอบปีการศึกษาที่ใช้งานอยู่
try {
    $academic_year_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->prepare($academic_year_sql);
    $stmt->execute();
    $academic_year_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_year_row) {
        $_SESSION['current_academic_year_id'] = $academic_year_row['academic_year_id'];
    } else {
        $_SESSION['error_message'] = "ไม่พบข้อมูลปีการศึกษาที่ใช้งานอยู่ กรุณาติดต่อผู้ดูแลระบบ";
        header('Location: register.php?step=error');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
    header('Location: register.php?step=error');
    exit;
}

// ประมวลผลตามขั้นตอนที่กำลังดำเนินการ
$current_step = isset($_POST['step']) ? intval($_POST['step']) : 1;

switch ($current_step) {
    case 1:
        // ขั้นตอนแรก: เริ่มต้นการลงทะเบียน
        $_SESSION['registration_started'] = true;
        header('Location: register.php?step=2');
        exit;
        break;

    case 2:
        // ขั้นตอนที่ 2: ค้นหารหัสนักเรียน
        processStudentCodeSearch($conn);
        break;

    case 3:
        // ขั้นตอนที่ 3: ยืนยันข้อมูล
        processConfirmInfo($conn);
        break;

    case 33:
        // ขั้นตอนที่ 3.3: กรอกข้อมูลด้วยตนเอง
        processManualInfo($conn);
        break;

    case 4:
        // ขั้นตอนที่ 4: เลือกครูที่ปรึกษา
        if (isset($_POST['search_teacher'])) {
            // ค้นหาครูที่ปรึกษา
            searchTeacher($conn);
        } elseif (isset($_POST['select_teacher'])) {
            // เลือกครูที่ปรึกษา
            selectTeacher($conn);
        } else {
            header('Location: register.php?step=4');
            exit;
        }
        break;

    case 5:
        // ขั้นตอนที่ 5: เลือกห้องเรียน
        processClassSelection($conn);
        break;

    case 55:
        // ขั้นตอนที่ 5.5: กรอกข้อมูลห้องเรียนด้วยตนเอง
        processManualClassInfo($conn);
        break;

    case 6:
        // ขั้นตอนที่ 6: ข้อมูลเพิ่มเติมและยินยอมเก็บข้อมูล
        processAdditionalInfo($conn);
        break;

    default:
        // ขั้นตอนไม่ถูกต้อง
        header('Location: register.php');
        exit;
}

/**
 * ประมวลผลการค้นหารหัสนักเรียน
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processStudentCodeSearch($conn) {
    // รับรหัสนักเรียนจากฟอร์ม
    $student_code = isset($_POST['student_code']) ? trim($_POST['student_code']) : '';

    // ตรวจสอบว่ามีรหัสนักเรียนหรือไม่
    if (empty($student_code)) {
        $_SESSION['error_message'] = 'กรุณากรอกรหัสนักเรียน';
        header('Location: register.php?step=2');
        exit;
    }

    try {
        // ตรวจสอบว่ามีนักเรียนคนนี้ในระบบแล้วหรือไม่
        $stmt = $conn->prepare("SELECT s.*, u.first_name, u.last_name, u.title 
                              FROM students s 
                              JOIN users u ON s.user_id = u.user_id 
                              WHERE s.student_code = ?");
        $stmt->execute([$student_code]);
        $existing_student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_student) {
            // พบข้อมูลในระบบ
            $_SESSION['student_code'] = $existing_student['student_code'];
            $_SESSION['student_title'] = $existing_student['title'];
            $_SESSION['student_first_name'] = $existing_student['first_name'];
            $_SESSION['student_last_name'] = $existing_student['last_name'];
            $_SESSION['existing_student_id'] = $existing_student['student_id'];
            $_SESSION['existing_user_id'] = $existing_student['user_id'];
            
            // ตรวจสอบว่า user_id นั้นมี line_id เดียวกับที่กำลังลงทะเบียนหรือไม่
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND line_id = ?");
            $stmt->execute([$existing_student['user_id'], $_SESSION['line_id']]);
            $same_line_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($same_line_user) {
                // เป็นคนเดียวกัน ให้เข้าสู่ระบบได้เลย
                $_SESSION['success_message'] = 'พบข้อมูลของคุณในระบบแล้ว กำลังเข้าสู่ระบบ...';
                header('Location: dashboard.php');
                exit;
            } else {
                // มีรหัสนักเรียนนี้แล้ว แต่ผูกกับ LINE ID อื่น
                $_SESSION['error_message'] = 'รหัสนักเรียนนี้ถูกใช้งานในระบบแล้ว กรุณาติดต่อผู้ดูแลระบบ';
                header('Location: register.php?step=2');
                exit;
            }
        } else {
            // ไม่พบข้อมูลในระบบ ต้องกรอกข้อมูลเอง
            $_SESSION['student_code'] = $student_code;
            header('Location: register.php?step=33');
            exit;
        }
    } catch (PDOException $e) {
        // แก้ไขปัญหา SQL Duplicate entry สำหรับ line_id
        if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'line_id') !== false) {
            // กรณีมี line_id ซ้ำ ให้ตรวจสอบว่ามีข้อมูลผู้ใช้นี้ในระบบแล้วหรือไม่
            $duplicate_user = checkAndFixDuplicateLineId($_SESSION['line_id'], $conn);
            if ($duplicate_user) {
                // มีผู้ใช้งานนี้ในระบบแล้ว ให้ตรวจสอบว่าเป็นนักเรียนหรือไม่
                $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
                $stmt->execute([$duplicate_user['user_id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    // เป็นนักเรียนที่ลงทะเบียนแล้ว ให้เข้าสู่หน้า dashboard
                    $_SESSION['user_id'] = $duplicate_user['user_id'];
                    $_SESSION['role'] = $duplicate_user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['success_message'] = 'พบข้อมูลของคุณในระบบแล้ว กำลังเข้าสู่ระบบ...';
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // เป็นผู้ใช้งานอื่นที่ไม่ใช่นักเรียน
                    $_SESSION['error_message'] = 'บัญชี LINE นี้ถูกใช้งานในระบบแล้วในฐานะ ' . $duplicate_user['role'] . ' กรุณาใช้บัญชี LINE อื่น';
                    header('Location: register.php?step=2');
                    exit;
                }
            } else {
                // ข้อผิดพลาดอื่นๆ เกี่ยวกับ line_id ซ้ำ
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดเกี่ยวกับบัญชี LINE กรุณาลองใหม่หรือติดต่อผู้ดูแลระบบ';
                header('Location: register.php?step=2');
                exit;
            }
        } else {
            // ข้อผิดพลาดอื่นๆ
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการค้นหาข้อมูล: ' . $e->getMessage();
            header('Location: register.php?step=2');
            exit;
        }
    }
}

/**
 * ประมวลผลการยืนยันข้อมูล
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processConfirmInfo($conn) {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        // ผู้ใช้ยืนยันข้อมูล ไปขั้นตอนการเลือกครูที่ปรึกษา
        header('Location: register.php?step=4');
        exit;
    } else {
        // ผู้ใช้ไม่ยืนยันข้อมูล ให้กรอกข้อมูลเอง
        header('Location: register.php?step=33');
        exit;
    }
}

/**
 * ประมวลผลการกรอกข้อมูลด้วยตนเอง
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processManualInfo($conn) {
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // ตรวจสอบว่ามีข้อมูลครบถ้วนหรือไม่
    if (empty($title) || empty($first_name) || empty($last_name)) {
        $_SESSION['error_message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header('Location: register.php?step=33');
        exit;
    }
    
    // บันทึกข้อมูลลงใน session สำหรับใช้ในขั้นตอนถัดไป
    $_SESSION['student_title'] = $title;
    $_SESSION['student_first_name'] = $first_name;
    $_SESSION['student_last_name'] = $last_name;
    $_SESSION['student_phone'] = $phone;
    $_SESSION['student_email'] = $email;
    
    // ไปขั้นตอนการเลือกครูที่ปรึกษา
    header('Location: register.php?step=4');
    exit;
}

/**
 * ค้นหาครูที่ปรึกษาจากชื่อ
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function searchTeacher($conn) {
    $teacher_name = isset($_POST['teacher_name']) ? trim($_POST['teacher_name']) : '';
    
    if (empty($teacher_name)) {
        $_SESSION['error_message'] = 'กรุณากรอกชื่อครูที่ต้องการค้นหา';
        header('Location: register.php?step=4');
        exit;
    }
    
    try {
        // ค้นหาข้อมูลครูที่ปรึกษา
        $search_term = "%$teacher_name%";
        
        // SQL สำหรับค้นหาครูและข้อมูลห้องเรียนที่เป็นครูที่ปรึกษา
        $sql = "SELECT 
                    t.teacher_id, 
                    t.title, 
                    t.first_name, 
                    t.last_name, 
                    d.department_name,
                    GROUP_CONCAT(DISTINCT CONCAT(c.level, ' กลุ่ม ', c.group_number) SEPARATOR ', ') as classes
                FROM 
                    teachers t
                LEFT JOIN 
                    departments d ON t.department_id = d.department_id
                LEFT JOIN 
                    class_advisors ca ON t.teacher_id = ca.teacher_id
                LEFT JOIN 
                    classes c ON ca.class_id = c.class_id
                WHERE 
                    (t.first_name LIKE ? OR t.last_name LIKE ?)
                    AND c.academic_year_id = ?
                GROUP BY 
                    t.teacher_id, t.title, t.first_name, t.last_name, d.department_name
                ORDER BY 
                    t.first_name, t.last_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$search_term, $search_term, $_SESSION['current_academic_year_id']]);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($teachers) > 0) {
            // เก็บผลลัพธ์การค้นหาใน session
            $_SESSION['search_teacher_results'] = $teachers;
            header('Location: register.php?step=4');
            exit;
        } else {
            // ไม่พบข้อมูลครูที่ปรึกษา
            $_SESSION['error_message'] = 'ไม่พบข้อมูลครูที่ปรึกษาตามคำค้นหา กรุณาลองค้นหาใหม่หรือเลือกกรอกข้อมูลห้องเรียนเอง';
            header('Location: register.php?step=4');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการค้นหาครูที่ปรึกษา: ' . $e->getMessage();
        header('Location: register.php?step=4');
        exit;
    }
}

/**
 * เลือกครูที่ปรึกษา
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function selectTeacher($conn) {
    $selected_teacher_id = isset($_POST['selected_teacher']) ? intval($_POST['selected_teacher']) : 0;
    
    if ($selected_teacher_id <= 0) {
        $_SESSION['error_message'] = 'กรุณาเลือกครูที่ปรึกษา';
        header('Location: register.php?step=4');
        exit;
    }
    
    try {
        // ดึงข้อมูลครูที่เลือก
        $stmt = $conn->prepare("SELECT t.teacher_id, t.title, t.first_name, t.last_name, d.department_name 
                              FROM teachers t 
                              LEFT JOIN departments d ON t.department_id = d.department_id 
                              WHERE t.teacher_id = ?");
        $stmt->execute([$selected_teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            $_SESSION['error_message'] = 'ไม่พบข้อมูลครูที่ปรึกษาที่เลือก';
            header('Location: register.php?step=4');
            exit;
        }
        
        // ดึงข้อมูลห้องเรียนที่ครูดูแล
        $stmt = $conn->prepare("SELECT c.class_id, c.level, c.group_number, d.department_name 
                              FROM classes c 
                              JOIN class_advisors ca ON c.class_id = ca.class_id 
                              JOIN departments d ON c.department_id = d.department_id 
                              WHERE ca.teacher_id = ? AND c.academic_year_id = ? AND c.is_active = 1
                              ORDER BY c.level, c.group_number");
        $stmt->execute([$selected_teacher_id, $_SESSION['current_academic_year_id']]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // บันทึกข้อมูลลงใน session
        $_SESSION['selected_teacher_id'] = $selected_teacher_id;
        $_SESSION['selected_teacher_name'] = $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name'];
        $_SESSION['teacher_classes'] = $classes;
        
        // ไปขั้นตอนการเลือกห้องเรียน
        header('Location: register.php?step=5');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการเลือกครูที่ปรึกษา: ' . $e->getMessage();
        header('Location: register.php?step=4');
        exit;
    }
}

/**
 * ประมวลผลการเลือกห้องเรียน
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processClassSelection($conn) {
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    
    if ($class_id <= 0) {
        $_SESSION['error_message'] = 'กรุณาเลือกห้องเรียน';
        header('Location: register.php?step=5');
        exit;
    }
    
    try {
        // ดึงข้อมูลห้องเรียนที่เลือก
        $stmt = $conn->prepare("SELECT c.class_id, c.level, c.group_number, d.department_name, d.department_id 
                              FROM classes c 
                              JOIN departments d ON c.department_id = d.department_id 
                              WHERE c.class_id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            $_SESSION['error_message'] = 'ไม่พบข้อมูลห้องเรียนที่เลือก';
            header('Location: register.php?step=5');
            exit;
        }
        
        // บันทึกข้อมูลลงใน session
        $_SESSION['selected_class_id'] = $class_id;
        $_SESSION['selected_level'] = $class['level'];
        $_SESSION['selected_group'] = $class['group_number'];
        $_SESSION['selected_department_id'] = $class['department_id'];
        $_SESSION['selected_department_name'] = $class['department_name'];
        
        // ไปขั้นตอนการกรอกข้อมูลเพิ่มเติม
        header('Location: register.php?step=6');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการเลือกห้องเรียน: ' . $e->getMessage();
        header('Location: register.php?step=5');
        exit;
    }
}

/**
 * ประมวลผลการกรอกข้อมูลห้องเรียนด้วยตนเอง
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processManualClassInfo($conn) {
    $level = isset($_POST['level']) ? $_POST['level'] : '';
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
    $group_number = isset($_POST['group_number']) ? intval($_POST['group_number']) : 0;
    $advisor_name = isset($_POST['advisor_name']) ? trim($_POST['advisor_name']) : '';
    
    // ตรวจสอบว่ามีข้อมูลครบถ้วนหรือไม่
    if (empty($level) || $department_id <= 0 || $group_number <= 0) {
        $_SESSION['error_message'] = 'กรุณากรอกข้อมูลห้องเรียนให้ครบถ้วน';
        header('Location: register.php?step=55');
        exit;
    }
    
    try {
        // ดึงข้อมูลแผนกวิชา
        $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $stmt->execute([$department_id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            $_SESSION['error_message'] = 'ไม่พบข้อมูลแผนกวิชาที่เลือก';
            header('Location: register.php?step=55');
            exit;
        }
        
        // ค้นหาห้องเรียนที่ตรงกับข้อมูลที่กรอก
        $stmt = $conn->prepare("SELECT class_id FROM classes 
                              WHERE level = ? AND department_id = ? AND group_number = ? AND academic_year_id = ?");
        $stmt->execute([$level, $department_id, $group_number, $_SESSION['current_academic_year_id']]);
        $existing_class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $class_id = 0;
        
        if ($existing_class) {
            // กรณีพบห้องเรียนที่ตรงกับข้อมูลที่กรอก
            $class_id = $existing_class['class_id'];
        } else {
            // กรณีไม่พบห้องเรียน ให้สร้างห้องเรียนใหม่
            $stmt = $conn->prepare("INSERT INTO classes 
                                  (academic_year_id, level, department_id, group_number, is_active, created_at) 
                                  VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$_SESSION['current_academic_year_id'], $level, $department_id, $group_number]);
            $class_id = $conn->lastInsertId();
        }
        
        // บันทึกข้อมูลลงใน session
        $_SESSION['selected_class_id'] = $class_id;
        $_SESSION['selected_level'] = $level;
        $_SESSION['selected_group'] = $group_number;
        $_SESSION['selected_department_id'] = $department_id;
        $_SESSION['selected_department_name'] = $department['department_name'];
        
        if (!empty($advisor_name)) {
            $_SESSION['advisor_name'] = $advisor_name;
        }
        
        // ไปขั้นตอนการกรอกข้อมูลเพิ่มเติม
        header('Location: register.php?step=6');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลห้องเรียน: ' . $e->getMessage();
        header('Location: register.php?step=55');
        exit;
    }
}

/**
 * ประมวลผลการบันทึกข้อมูลเพิ่มเติมและการยินยอมเก็บข้อมูล
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 */
function processAdditionalInfo($conn) {
    // ตรวจสอบว่ามีข้อมูลพื้นฐานที่จำเป็นหรือไม่
    if (!isset($_SESSION['student_code']) || !isset($_SESSION['student_first_name']) ||
        !isset($_SESSION['student_title'])) {
        $_SESSION['error_message'] = 'ข้อมูลไม่ครบถ้วน กรุณากรอกข้อมูลให้ครบถ้วน';
        header('Location: register.php?step=2');
        exit;
    }
    
    // ตรวจสอบการยินยอมเก็บข้อมูลส่วนบุคคล
    if (!isset($_POST['gdpr_consent']) || !isset($_POST['terms_conditions'])) {
        $_SESSION['error_message'] = 'กรุณายอมรับเงื่อนไขการใช้งานและการเก็บข้อมูลส่วนบุคคล';
        header('Location: register.php?step=6');
        exit;
    }
    
    try {
        // เริ่ม Transaction
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามี LINE ID ซ้ำหรือไม่
        $duplicate_user = checkAndFixDuplicateLineId($_SESSION['line_id'], $conn);
        
        if ($duplicate_user) {
            // กรณีมี LINE ID ซ้ำ และเป็นนักเรียนอยู่แล้ว
            $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
            $stmt->execute([$duplicate_user['user_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                // เป็นนักเรียนที่ลงทะเบียนแล้ว
                $_SESSION['user_id'] = $duplicate_user['user_id'];
                $_SESSION['success_message'] = 'พบข้อมูลของคุณในระบบแล้ว กำลังเข้าสู่ระบบ...';
                
                // นำไปที่หน้า dashboard
                $conn->commit();
                header('Location: dashboard.php');
                exit;
            }
        }
        
        // รับข้อมูลเพิ่มเติม
        $address = isset($_POST['address']) ? trim($_POST['address']) : null;
        $emergency_contact = isset($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : null;
        $emergency_phone = isset($_POST['emergency_phone']) ? trim($_POST['emergency_phone']) : null;
        
        // รับชั้นเรียนและแผนกวิชาจาก session
        $class_id = $_SESSION['selected_class_id'] ?? null;
        $level = $_SESSION['selected_level'] ?? null;
        $department_id = $_SESSION['selected_department_id'] ?? null;
        
        // ตรวจสอบว่าพบชั้นเรียนหรือไม่
        if (!$class_id) {
            // ถ้าไม่มี class_id ให้ตรวจสอบว่ามีข้อมูลระดับและแผนกหรือไม่
            if ($level && $department_id) {
                // ค้นหาหรือสร้างชั้นเรียนใหม่
                $group_number = $_SESSION['selected_group'] ?? 1;
                
                // ค้นหาว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่
                $stmt = $conn->prepare("SELECT class_id FROM classes WHERE level = ? AND department_id = ? AND group_number = ? AND academic_year_id = ?");
                $stmt->execute([$level, $department_id, $group_number, $_SESSION['current_academic_year_id']]);
                $existing_class = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_class) {
                    $class_id = $existing_class['class_id'];
                } else {
                    // สร้างชั้นเรียนใหม่
                    $stmt = $conn->prepare("INSERT INTO classes (academic_year_id, level, department_id, group_number, is_active, created_at) 
                                          VALUES (?, ?, ?, ?, 1, NOW())");
                    $stmt->execute([$_SESSION['current_academic_year_id'], $level, $department_id, $group_number]);
                    $class_id = $conn->lastInsertId();
                }
            }
        }
        
        // อัพเดทข้อมูลผู้ใช้
        $user_data = [
            'title' => $_SESSION['student_title'],
            'first_name' => $_SESSION['student_first_name'],
            'last_name' => $_SESSION['student_last_name'],
            'phone_number' => isset($_SESSION['student_phone']) ? $_SESSION['student_phone'] : (isset($_POST['phone']) ? trim($_POST['phone']) : null),
            'email' => isset($_SESSION['student_email']) ? $_SESSION['student_email'] : (isset($_POST['email']) ? trim($_POST['email']) : null),
            'gdpr_consent' => 1,
            'gdpr_consent_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        // ถ้ามีข้อมูลผู้ใช้อยู่แล้วให้อัพเดท
        if (isset($_SESSION['existing_user_id'])) {
            $user_id = $_SESSION['existing_user_id'];
            $stmt = $conn->prepare("UPDATE users SET 
                                  title = ?, first_name = ?, last_name = ?, 
                                  phone_number = ?, email = ?, 
                                  gdpr_consent = ?, gdpr_consent_date = ?,
                                  updated_at = ?, line_id = ?
                                  WHERE user_id = ?");
            $stmt->execute([
                $user_data['title'], 
                $user_data['first_name'], 
                $user_data['last_name'],
                $user_data['phone_number'],
                $user_data['email'],
                $user_data['gdpr_consent'],
                $user_data['gdpr_consent_date'],
                $user_data['updated_at'],
                $_SESSION['line_id'],
                $user_id
            ]);
        } else {
            // สร้างข้อมูลผู้ใช้ใหม่
            $stmt = $conn->prepare("INSERT INTO users 
                                  (line_id, role, title, first_name, last_name, 
                                   phone_number, email, gdpr_consent, gdpr_consent_date, created_at) 
                                  VALUES (?, 'student', ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['line_id'],
                $user_data['title'],
                $user_data['first_name'],
                $user_data['last_name'],
                $user_data['phone_number'],
                $user_data['email'],
                $user_data['gdpr_consent'],
                $user_data['gdpr_consent_date']
            ]);
            $user_id = $conn->lastInsertId();
        }
        
        // อัพโหลดรูปโปรไฟล์ (ถ้ามี)
        $profile_picture = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/profiles/';
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_file)) {
                $profile_picture = $new_filename;
                
                // อัพเดทรูปโปรไฟล์ในตาราง users
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$profile_picture, $user_id]);
            }
        }
        
        // ตรวจสอบว่ามีข้อมูลนักเรียนอยู่แล้วหรือไม่
        if (isset($_SESSION['existing_student_id'])) {
            // อัพเดทข้อมูลนักเรียน
            $stmt = $conn->prepare("UPDATE students SET 
                                  current_class_id = ?,
                                  updated_at = NOW() 
                                  WHERE student_id = ?");
            $stmt->execute([$class_id, $_SESSION['existing_student_id']]);
            $student_id = $_SESSION['existing_student_id'];
        } else {
            // สร้างข้อมูลนักเรียนใหม่
            $stmt = $conn->prepare("INSERT INTO students 
                                  (user_id, student_code, title, current_class_id, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'กำลังศึกษา', NOW())");
            $stmt->execute([$user_id, $_SESSION['student_code'], $_SESSION['student_title'], $class_id]);
            $student_id = $conn->lastInsertId();
        }
        
        // บันทึกข้อมูลการศึกษา
        if ($class_id) {
            // ตรวจสอบว่ามีข้อมูลอยู่แล้วหรือไม่
            $stmt = $conn->prepare("SELECT record_id FROM student_academic_records 
                                  WHERE student_id = ? AND academic_year_id = ?");
            $stmt->execute([$student_id, $_SESSION['current_academic_year_id']]);
            $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_record) {
                // อัพเดทข้อมูลที่มีอยู่
                $stmt = $conn->prepare("UPDATE student_academic_records SET 
                                      class_id = ?, updated_at = NOW() 
                                      WHERE record_id = ?");
                $stmt->execute([$class_id, $existing_record['record_id']]);
            } else {
                // สร้างข้อมูลใหม่
                $stmt = $conn->prepare("INSERT INTO student_academic_records 
                                      (student_id, academic_year_id, class_id, total_attendance_days, 
                                       total_absence_days, created_at) 
                                      VALUES (?, ?, ?, 0, 0, NOW())");
                $stmt->execute([$student_id, $_SESSION['current_academic_year_id'], $class_id]);
            }
        }
        
        // เชื่อมโยงกับครูที่ปรึกษา (ถ้ามี)
        if (isset($_SESSION['selected_teacher_id']) && !empty($_SESSION['selected_teacher_id'])) {
            $teacher_id = $_SESSION['selected_teacher_id'];
            
            // ตรวจสอบว่าครูเป็นครูที่ปรึกษาของห้องเรียนนี้หรือไม่
            $stmt = $conn->prepare("SELECT * FROM class_advisors WHERE teacher_id = ? AND class_id = ?");
            $stmt->execute([$teacher_id, $class_id]);
            $advisor_exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$advisor_exists) {
                // ถ้ายังไม่ได้เป็นครูที่ปรึกษาของห้องนี้ ให้เพิ่มเข้าไป
                $stmt = $conn->prepare("INSERT INTO class_advisors (class_id, teacher_id, is_primary) 
                                      VALUES (?, ?, 1)");
                $stmt->execute([$class_id, $teacher_id]);
            }
        }
        
        // ยืนยัน Transaction
        $conn->commit();
        
        // บันทึกข้อมูลลงใน session เพื่อใช้ในหน้าถัดไป
        $_SESSION['user_id'] = $user_id;
        $_SESSION['student_id'] = $student_id;
        $_SESSION['registration_complete'] = true;
        
        // เคลียร์ข้อมูลการลงทะเบียนเพื่อป้องกันการลงทะเบียนซ้ำ
        unset($_SESSION['student_code']);
        unset($_SESSION['student_title']);
        unset($_SESSION['student_first_name']);
        unset($_SESSION['student_last_name']);
        unset($_SESSION['selected_class_id']);
        
        // ไปที่หน้าลงทะเบียนสำเร็จ
        header('Location: register.php?step=7');
        exit;
        
    } catch (PDOException $e) {
        // กรณีเกิดข้อผิดพลาด
        $conn->rollBack();
        $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการลงทะเบียน: ' . $e->getMessage();
        header('Location: register.php?step=6');
        exit;
    }
}

/**
 * ตรวจสอบและแก้ไขปัญหา LINE ID ซ้ำ
 * @param string $line_id LINE ID ที่ต้องการตรวจสอบ
 * @param PDO $conn การเชื่อมต่อฐานข้อมูล
 * @return bool|array ถ้าไม่มีปัญหาจะคืนค่า false ถ้ามีปัญหาจะคืนค่าข้อมูลผู้ใช้ที่มี LINE ID นี้
 */
function checkAndFixDuplicateLineId($line_id, $conn) {
    try {
        // ตรวจสอบว่ามี LINE ID นี้ในระบบแล้วหรือไม่
        $stmt = $conn->prepare("SELECT * FROM users WHERE line_id = ?");
        $stmt->execute([$line_id]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            return $existing_user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error checking duplicate LINE ID: " . $e->getMessage());
        return false;
    }
}
?>
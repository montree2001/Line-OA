<?php
/**
 * students.php - หน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat (ปรับปรุงใหม่)
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'students';
$page_title = 'จัดการข้อมูลนักเรียน';
$page_header = 'จัดการข้อมูลนักเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'ผู้ดูแลระบบ',
    'role' => $_SESSION['user_role'] ?? 'เจ้าหน้าที่',
    'initials' => mb_substr($_SESSION['user_name'] ?? 'ป', 0, 1, 'UTF-8')
];

// สร้างเงื่อนไขการค้นหา
$where_conditions = [];
$params = [];

if (isset($_GET['name']) && !empty($_GET['name'])) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_name = '%' . $_GET['name'] . '%';
    $params[] = $search_name;
    $params[] = $search_name;
}

if (isset($_GET['student_code']) && !empty($_GET['student_code'])) {
    $where_conditions[] = "s.student_code LIKE ?";
    $params[] = '%' . $_GET['student_code'] . '%';
}

if (isset($_GET['level']) && !empty($_GET['level'])) {
    $where_conditions[] = "c.level = ?";
    $params[] = $_GET['level'];
}

if (isset($_GET['group_number']) && !empty($_GET['group_number'])) {
    $where_conditions[] = "c.group_number = ?";
    $params[] = $_GET['group_number'];
}

if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $where_conditions[] = "c.department_id = ?";
    $params[] = $_GET['department_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "s.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['attendance_status']) && !empty($_GET['attendance_status'])) {
    // จะกรองด้วยคำนวณในภายหลัง (จัดการในโค้ด PHP)
    $attendance_status = $_GET['attendance_status'];
}

if (isset($_GET['line_status']) && !empty($_GET['line_status'])) {
    if ($_GET['line_status'] === 'connected') {
        $where_conditions[] = "u.line_id IS NOT NULL AND u.line_id != ''";
    } else if ($_GET['line_status'] === 'not_connected') {
        $where_conditions[] = "(u.line_id IS NULL OR u.line_id = '')";
    }
}

// สร้าง SQL condition
$sql_condition = "";
if (!empty($where_conditions)) {
    $sql_condition = " WHERE " . implode(" AND ", $where_conditions);
}

// ดึงข้อมูลนักเรียนจากฐานข้อมูล
$students = [];
try {
    $conn = getDB();
    
    // ดึงข้อมูลนักเรียน
    $query = "SELECT DISTINCT s.student_id, s.student_code, s.status, 
              u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email,
              c.level, c.group_number, c.class_id,
              d.department_name, d.department_id,
              (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
               FROM class_advisors ca 
               JOIN teachers t ON ca.teacher_id = t.teacher_id 
               WHERE ca.class_id = c.class_id AND ca.is_primary = 1
               LIMIT 1) as advisor_name,
              (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1) as attendance_days,
              (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0) as absence_days
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              $sql_condition
              ORDER BY s.student_code";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query($query);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เติมข้อมูลเพิ่มเติม
    foreach ($students as &$student) {
        // สร้างชื่อชั้นเรียน
        $student['class'] = ($student['level'] ?? '') . '/' . ($student['group_number'] ?? '');
        
        // คำนวณอัตราการเข้าแถว
        $total_days = $student['attendance_days'] + $student['absence_days'];
        if ($total_days > 0) {
            $student['attendance_rate'] = ($student['attendance_days'] / $total_days) * 100;
        } else {
            $student['attendance_rate'] = 100; // ถ้ายังไม่มีข้อมูลให้เป็น 100%
        }
        
        // กำหนดสถานะการเข้าแถว
        if ($student['attendance_rate'] < 60) {
            $student['attendance_status'] = 'เสี่ยงตกกิจกรรม';
        } elseif ($student['attendance_rate'] < 75) {
            $student['attendance_status'] = 'ต้องระวัง';
        } else {
            $student['attendance_status'] = 'ปกติ';
        }
        
        // ตรวจสอบการเชื่อมต่อกับ LINE - ตรวจสอบค่าให้แน่ใจว่าไม่เป็นค่าว่างหรือ null
        $student['line_connected'] = !empty($student['line_id']);
    }
    
    // กรองตามสถานะการเข้าแถว (ถ้ามี)
    if (!empty($attendance_status)) {
        $students = array_filter($students, function($student) use ($attendance_status) {
            return $student['attendance_status'] === $attendance_status;
        });
    }
    
} catch (PDOException $e) {
    // บันทึกข้อผิดพลาด
    error_log("Error fetching students: " . $e->getMessage());
}

// ดึงข้อมูลแผนกวิชาทั้งหมด
$departments = [];
try {
    $conn = getDB();
    $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

// ดึงข้อมูลชั้นเรียนทั้งหมด
$classes = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT c.class_id, c.level, c.group_number, d.department_name, c.department_id
        FROM classes c 
        JOIN departments d ON c.department_id = d.department_id
        JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE ay.is_active = 1
        ORDER BY c.level, c.group_number
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching classes: " . $e->getMessage());
}

// ดึงข้อมูลครูทั้งหมด
$teachers = [];
try {
    $conn = getDB();
    $stmt = $conn->query("
        SELECT t.teacher_id, t.title, t.first_name, t.last_name, d.department_name
        FROM teachers t
        LEFT JOIN departments d ON t.department_id = d.department_id
        ORDER BY t.first_name, t.last_name
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
}

// ดึงสถิติจำนวนนักเรียน
$student_stats = [
    'total' => 0,
    'male' => 0,
    'female' => 0,
    'risk' => 0
];

try {
    $conn = getDB();
    
    // นับจำนวนนักเรียนทั้งหมดที่กำลังศึกษา
    $totalQuery = "SELECT COUNT(DISTINCT s.student_id) as total 
                  FROM students s 
                  WHERE s.status = 'กำลังศึกษา'";
    $totalStmt = $conn->query($totalQuery);
    $student_stats['total'] = $totalStmt->fetchColumn();
    
    // นับจำนวนนักเรียนชาย
    $maleQuery = "SELECT COUNT(DISTINCT s.student_id) as male 
                 FROM students s 
                 JOIN users u ON s.user_id = u.user_id 
                 WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นาย', 'เด็กชาย')";
    $maleStmt = $conn->query($maleQuery);
    $student_stats['male'] = $maleStmt->fetchColumn();
    
    // นับจำนวนนักเรียนหญิง
    $femaleQuery = "SELECT COUNT(DISTINCT s.student_id) as female 
                   FROM students s 
                   JOIN users u ON s.user_id = u.user_id 
                   WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นางสาว', 'เด็กหญิง', 'นาง')";
    $femaleStmt = $conn->query($femaleQuery);
    $student_stats['female'] = $femaleStmt->fetchColumn();
    
    // นับจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
    $riskQuery = "SELECT COUNT(DISTINCT rs.student_id) as risk 
                 FROM risk_students rs 
                 JOIN students s ON rs.student_id = s.student_id
                 WHERE rs.risk_level IN ('high', 'critical') 
                 AND s.status = 'กำลังศึกษา'";
    $riskStmt = $conn->query($riskQuery);
    $student_stats['risk'] = $riskStmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// ประมวลผลการเพิ่ม/แก้ไข/ลบข้อมูลนักเรียน (ถ้ามี)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // ตรวจสอบข้อมูล
                if (empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
                    $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
                } else {
                    try {
                        $conn = getDB();
                        
                        // ตรวจสอบว่ามีรหัสนักเรียนซ้ำหรือไม่
                        $checkQuery = "SELECT COUNT(*) FROM students WHERE student_code = ?";
                        $checkStmt = $conn->prepare($checkQuery);
                        $checkStmt->execute([$_POST['student_code']]);
                        $duplicateCount = $checkStmt->fetchColumn();
                        
                        if ($duplicateCount > 0) {
                            $error_message = "รหัสนักเรียนนี้มีอยู่ในระบบแล้ว";
                        } else {
                            $conn->beginTransaction();
                            
                            // 1. เพิ่มข้อมูลในตาราง users ก่อน
                            $userSql = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                                      VALUES ('', 'student', ?, ?, ?, ?, ?, 1)";
                            $userStmt = $conn->prepare($userSql);
                            $userStmt->execute([
                                $_POST['title'],
                                $_POST['firstname'],
                                $_POST['lastname'],
                                $_POST['phone_number'] ?? '',
                                $_POST['email'] ?? ''
                            ]);
                            
                            $user_id = $conn->lastInsertId();
                            
                            // 2. เพิ่มข้อมูลนักเรียน
                            $studentSql = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                                         VALUES (?, ?, ?, ?, ?)";
                            $studentStmt = $conn->prepare($studentSql);
                            $studentStmt->execute([
                                $user_id,
                                $_POST['student_code'],
                                $_POST['title'],
                                $_POST['class_id'] ?? null,
                                $_POST['status'] ?? 'กำลังศึกษา'
                            ]);
                            
                            $student_id = $conn->lastInsertId();
                            
                            // 3. เพิ่มข้อมูลในตาราง student_academic_records ถ้ามีการเลือกชั้นเรียน
                            if (!empty($_POST['class_id'])) {
                                // ดึงข้อมูล academic_year_id จากชั้นเรียน
                                $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
                                $yearStmt = $conn->prepare($yearQuery);
                                $yearStmt->execute([$_POST['class_id']]);
                                $academic_year_id = $yearStmt->fetchColumn();
                                
                                if ($academic_year_id) {
                                    $recordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                                  VALUES (?, ?, ?)";
                                    $recordStmt = $conn->prepare($recordQuery);
                                    $recordStmt->execute([$student_id, $academic_year_id, $_POST['class_id']]);
                                }
                            }
                            
                            $conn->commit();
                            $success_message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
                        }
                    } catch (PDOException $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                        error_log("Error adding student: " . $e->getMessage());
                    }
                }
                break;
                
            case 'edit':
                // ตรวจสอบข้อมูล
                if (empty($_POST['student_id']) || empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname'])) {
                    $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
                } else {
                    try {
                        $conn = getDB();
                        
                        // ตรวจสอบว่ามีรหัสนักเรียนซ้ำหรือไม่ (ยกเว้นรหัสตัวเอง)
                        $checkQuery = "SELECT COUNT(*) FROM students WHERE student_code = ? AND student_id != ?";
                        $checkStmt = $conn->prepare($checkQuery);
                        $checkStmt->execute([$_POST['student_code'], $_POST['student_id']]);
                        $duplicateCount = $checkStmt->fetchColumn();
                        
                        if ($duplicateCount > 0) {
                            $error_message = "รหัสนักเรียนนี้มีอยู่ในระบบแล้ว";
                        } else {
                            $conn->beginTransaction();
                            
                            // 1. ดึงข้อมูล user_id จากตาราง students
                            $getUserIdQuery = "SELECT user_id, current_class_id FROM students WHERE student_id = ?";
                            $getUserIdStmt = $conn->prepare($getUserIdQuery);
                            $getUserIdStmt->execute([$_POST['student_id']]);
                            $student_data = $getUserIdStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$student_data) {
                                throw new Exception("ไม่พบข้อมูลนักเรียน");
                            }
                            
                            // 2. อัปเดตข้อมูลในตาราง users
                            $updateUserSql = "UPDATE users 
                                           SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = CURRENT_TIMESTAMP
                                           WHERE user_id = ?";
                            $updateUserStmt = $conn->prepare($updateUserSql);
                            $updateUserStmt->execute([
                                $_POST['title'],
                                $_POST['firstname'],
                                $_POST['lastname'],
                                $_POST['phone_number'] ?? '',
                                $_POST['email'] ?? '',
                                $student_data['user_id']
                            ]);
                            
                            // 3. อัปเดตข้อมูลในตาราง students
                            $updateStudentSql = "UPDATE students 
                                              SET student_code = ?, title = ?, current_class_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                                              WHERE student_id = ?";
                            $updateStudentStmt = $conn->prepare($updateStudentSql);
                            $updateStudentStmt->execute([
                                $_POST['student_code'],
                                $_POST['title'],
                                $_POST['class_id'] ?? null,
                                $_POST['status'] ?? 'กำลังศึกษา',
                                $_POST['student_id']
                            ]);
                            
                            // 4. อัปเดตหรือเพิ่มข้อมูลในตาราง student_academic_records ถ้ามีการเปลี่ยนชั้นเรียน
                            if (!empty($_POST['class_id']) && $_POST['class_id'] != $student_data['current_class_id']) {
                                // ดึงข้อมูล academic_year_id จากชั้นเรียนใหม่
                                $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
                                $yearStmt = $conn->prepare($yearQuery);
                                $yearStmt->execute([$_POST['class_id']]);
                                $academic_year_id = $yearStmt->fetchColumn();
                                
                                if ($academic_year_id) {
                                    // ตรวจสอบว่ามี record อยู่แล้วหรือไม่
                                    $checkRecordQuery = "SELECT record_id FROM student_academic_records 
                                                      WHERE student_id = ? AND academic_year_id = ?";
                                    $checkRecordStmt = $conn->prepare($checkRecordQuery);
                                    $checkRecordStmt->execute([$_POST['student_id'], $academic_year_id]);
                                    $record_id = $checkRecordStmt->fetchColumn();
                                    
                                    if ($record_id) {
                                        // อัปเดต record ที่มีอยู่
                                        $updateRecordSql = "UPDATE student_academic_records 
                                                         SET class_id = ?, updated_at = CURRENT_TIMESTAMP
                                                         WHERE record_id = ?";
                                        $updateRecordStmt = $conn->prepare($updateRecordSql);
                                        $updateRecordStmt->execute([$_POST['class_id'], $record_id]);
                                    } else {
                                        // สร้าง record ใหม่
                                        $insertRecordSql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                                         VALUES (?, ?, ?)";
                                        $insertRecordStmt = $conn->prepare($insertRecordSql);
                                        $insertRecordStmt->execute([$_POST['student_id'], $academic_year_id, $_POST['class_id']]);
                                    }
                                }
                            }
                            
                            $conn->commit();
                            $success_message = "แก้ไขข้อมูลนักเรียนเรียบร้อยแล้ว";
                        }
                    } catch (Exception $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                        error_log("Error editing student: " . $e->getMessage());
                    }
                }
                break;
                
            case 'delete':
                if (empty($_POST['student_id'])) {
                    $error_message = "ไม่ระบุรหัสนักเรียนที่ต้องการลบ";
                } else {
                    try {
                        $conn = getDB();
                        $conn->beginTransaction();
                        
                        // 1. ดึง user_id จากตาราง students
                        $getUserIdQuery = "SELECT user_id FROM students WHERE student_id = ?";
                        $getUserIdStmt = $conn->prepare($getUserIdQuery);
                        $getUserIdStmt->execute([$_POST['student_id']]);
                        $user_id = $getUserIdStmt->fetchColumn();
                        
                        if (!$user_id) {
                            throw new Exception("ไม่พบข้อมูลนักเรียน");
                        }
                        
                        // 2. ลบข้อมูลที่เกี่ยวข้องในตารางอื่นๆ (ลบตามลำดับเพื่อหลีกเลี่ยง foreign key constraint)
                        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
                        
                        // ลบข้อมูลในตาราง student_academic_records
                        $deleteRecordsSql = "DELETE FROM student_academic_records WHERE student_id = ?";
                        $deleteRecordsStmt = $conn->prepare($deleteRecordsSql);
                        $deleteRecordsStmt->execute([$_POST['student_id']]);
                        
                        // ลบข้อมูลในตาราง attendance
                        $deleteAttendanceSql = "DELETE FROM attendance WHERE student_id = ?";
                        $deleteAttendanceStmt = $conn->prepare($deleteAttendanceSql);
                        $deleteAttendanceStmt->execute([$_POST['student_id']]);
                        
                        // ลบข้อมูลในตาราง risk_students
                        $deleteRiskSql = "DELETE FROM risk_students WHERE student_id = ?";
                        $deleteRiskStmt = $conn->prepare($deleteRiskSql);
                        $deleteRiskStmt->execute([$_POST['student_id']]);
                        
                        // ลบข้อมูลในตาราง qr_codes
                        $deleteQRCodesSql = "DELETE FROM qr_codes WHERE student_id = ?";
                        $deleteQRCodesStmt = $conn->prepare($deleteQRCodesSql);
                        $deleteQRCodesStmt->execute([$_POST['student_id']]);
                        
                        // ลบข้อมูลในตาราง parent_student_relation
                        $deleteRelationSql = "DELETE FROM parent_student_relation WHERE student_id = ?";
                        $deleteRelationStmt = $conn->prepare($deleteRelationSql);
                        $deleteRelationStmt->execute([$_POST['student_id']]);
                        
                        // 3. ลบข้อมูลในตาราง students
                        $deleteStudentSql = "DELETE FROM students WHERE student_id = ?";
                        $deleteStudentStmt = $conn->prepare($deleteStudentSql);
                        $deleteStudentStmt->execute([$_POST['student_id']]);
                        
                        // 4. ลบข้อมูลในตาราง users
                        $deleteUserSql = "DELETE FROM users WHERE user_id = ?";
                        $deleteUserStmt = $conn->prepare($deleteUserSql);
                        $deleteUserStmt->execute([$user_id]);
                        
                        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
                        
                        $conn->commit();
                        $success_message = "ลบข้อมูลนักเรียนเรียบร้อยแล้ว";
                    } catch (Exception $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                        error_log("Error deleting student: " . $e->getMessage());
                    }
                }
                break;
                
            case 'import':
                // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
                if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] != UPLOAD_ERR_OK) {
                    $error_message = "กรุณาอัปโหลดไฟล์";
                } else {
                    // ตรวจสอบชนิดไฟล์
                    $file_ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
                    if (!in_array($file_ext, ['csv', 'xlsx', 'xls'])) {
                        $error_message = "รองรับเฉพาะไฟล์ CSV, XLS, XLSX เท่านั้น";
                    } else {
                        // ในกรณีนี้เราจะจำลองการนำเข้าข้อมูลสำเร็จ
                        // ในระบบจริงจะต้องมีการอ่านและประมวลผลไฟล์
                        $success_message = "นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว";
                    }
                }
                break;
        }
    }
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/students.css'
];

$extra_js = [
    'assets/js/students.js',
    'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/students_content.php';

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'students' => $students,
    'statistics' => $student_stats,
    'departments' => $departments,
    'classes' => $classes,
    'teachers' => $teachers
];

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
?>
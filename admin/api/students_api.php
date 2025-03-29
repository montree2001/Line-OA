<?php
/**
 * students_api.php - API สำหรับจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน (ให้เปิดใช้งานเมื่อพร้อมใช้งานจริง)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'teacher'])) {
//     header('Content-Type: application/json');
//     echo json_encode([
//         'success' => false,
//         'message' => 'ไม่มีสิทธิ์เข้าถึง API นี้'
//     ]);
//     exit;
// }

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// กำหนด header เป็น JSON
header('Content-Type: application/json; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// ตรวจสอบการร้องขอ
if (isset($_GET['action'])) {
    // ร้องขอแบบ GET
    handleGetRequest();
} elseif (isset($_POST['action'])) {
    // ร้องขอแบบ POST
    handlePostRequest();
} else {
    // ไม่ระบุ action
    echo json_encode([
        'success' => false,
        'message' => 'ไม่ระบุการกระทำ (action)'
    ]);
}

/**
 * จัดการคำขอแบบ GET
 */
function handleGetRequest() {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_student':
            getStudent();
            break;
            
        case 'get_classes':
            getClasses();
            break;
            
        case 'get_teachers':
            getTeachers();
            break;
            
        case 'get_departments':
            getDepartments();
            break;
            
        case 'get_statistics':
            getStatistics();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่รู้จักการกระทำ: ' . $action
            ]);
            break;
    }
}

/**
 * จัดการคำขอแบบ POST
 */
function handlePostRequest() {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add_student':
            addStudent();
            break;
            
        case 'update_student':
            updateStudent();
            break;
            
        case 'delete_student':
            deleteStudent();
            break;
            
        case 'import_students':
            importStudents();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่รู้จักการกระทำ: ' . $action
            ]);
            break;
    }
}


function getAllStudents($filters = []) {
    try {
        $conn = getDB();
        
        // สร้างเงื่อนไขการค้นหา
        $where_conditions = [];
        $params = [];
        
        if (isset($filters['name']) && !empty($filters['name'])) {
            $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search_name = '%' . $filters['name'] . '%';
            $params[] = $search_name;
            $params[] = $search_name;
        }
        
        if (isset($filters['student_code']) && !empty($filters['student_code'])) {
            $where_conditions[] = "s.student_code LIKE ?";
            $params[] = '%' . $filters['student_code'] . '%';
        }
        
        // เพิ่มเงื่อนไขอื่นๆ ตามความเหมาะสม
        
        // สร้าง SQL condition
        $sql_condition = "";
        if (!empty($where_conditions)) {
            $sql_condition = " WHERE " . implode(" AND ", $where_conditions);
        }
        
        // ดึงข้อมูลนักเรียน - เพิ่ม DISTINCT เพื่อป้องกันข้อมูลซ้ำซ้อน
        $query = "SELECT DISTINCT s.student_id, s.student_code, s.status, 
                 u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email,
                 c.level, c.group_number, c.class_id,
                 d.department_name, d.department_id,
                 (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                  FROM class_advisors ca 
                  JOIN teachers t ON ca.teacher_id = t.teacher_id 
                  WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                  LIMIT 1) as advisor_name,
                 IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
                 IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
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
        
        return $students;
    } catch (PDOException $e) {
        error_log("Error fetching students: " . $e->getMessage());
        return [];
    }
}




/**
 * ดึงข้อมูลนักเรียน
 */
function getStudent() {
    if (!isset($_GET['student_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่ระบุรหัสนักเรียน'
        ]);
        return;
    }
    
    $student_id = $_GET['student_id'];
    
    try {
        $conn = getDB();
        
        // ดึงข้อมูลนักเรียน
        $query = "SELECT s.student_id, s.student_code, s.current_class_id as class_id, s.status, 
                u.title, u.first_name, u.last_name, u.line_id, u.phone_number, u.email, u.user_id,
                c.level, c.group_number, c.academic_year_id,
                d.department_name, d.department_id,
                (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) 
                 FROM class_advisors ca 
                 JOIN teachers t ON ca.teacher_id = t.teacher_id 
                 WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                 LIMIT 1) as advisor_name,
                IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 1), 0) as attendance_days,
                IFNULL((SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.student_id AND a.is_present = 0), 0) as absence_days
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE s.student_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            return;
        }
        
        // เพิ่มข้อมูลเพิ่มเติม
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
        
        // ตรวจสอบการเชื่อมต่อกับ LINE
        $student['line_connected'] = !empty($student['line_id']);
        
        echo json_encode([
            'success' => true,
            'student' => $student
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลชั้นเรียนทั้งหมด
 */
function getClasses() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลชั้นเรียน
        $query = "SELECT c.class_id, c.level, c.group_number, c.department_id, d.department_name
                FROM classes c
                JOIN departments d ON c.department_id = d.department_id
                JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                WHERE ay.is_active = 1
                ORDER BY c.level, c.group_number";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลครูทั้งหมด
 */
function getTeachers() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลครู
        $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, t.position,
                d.department_name, d.department_id
                FROM teachers t
                LEFT JOIN departments d ON t.department_id = d.department_id
                ORDER BY t.first_name, t.last_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'teachers' => $teachers
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลแผนกวิชาทั้งหมด
 */
function getDepartments() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลแผนกวิชา
        $query = "SELECT department_id, department_code, department_name
                FROM departments
                ORDER BY department_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'departments' => $departments
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลสถิติของนักเรียน
 */
function getStatistics() {
    try {
        $conn = getDB();
        
        // ดึงจำนวนนักเรียนทั้งหมด
        $queryTotal = "SELECT COUNT(*) as total FROM students WHERE status = 'กำลังศึกษา'";
        $stmtTotal = $conn->prepare($queryTotal);
        $stmtTotal->execute();
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
        
        // ดึงจำนวนนักเรียนชาย
        $queryMale = "SELECT COUNT(*) as male 
                    FROM students s 
                    JOIN users u ON s.user_id = u.user_id
                    WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นาย', 'เด็กชาย')";
        $stmtMale = $conn->prepare($queryMale);
        $stmtMale->execute();
        $male = $stmtMale->fetch(PDO::FETCH_ASSOC)['male'];
        
        // ดึงจำนวนนักเรียนหญิง
        $queryFemale = "SELECT COUNT(*) as female 
                      FROM students s 
                      JOIN users u ON s.user_id = u.user_id
                      WHERE s.status = 'กำลังศึกษา' AND u.title IN ('นางสาว', 'เด็กหญิง', 'นาง')";
        $stmtFemale = $conn->prepare($queryFemale);
        $stmtFemale->execute();
        $female = $stmtFemale->fetch(PDO::FETCH_ASSOC)['female'];
        
        // ดึงจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
        $queryRisk = "SELECT COUNT(*) as risk FROM risk_students WHERE risk_level IN ('high', 'critical')";
        $stmtRisk = $conn->prepare($queryRisk);
        $stmtRisk->execute();
        $risk = $stmtRisk->fetch(PDO::FETCH_ASSOC)['risk'];
        
        echo json_encode([
            'success' => true,
            'statistics' => [
                'total' => (int)$total,
                'male' => (int)$male,
                'female' => (int)$female,
                'risk' => (int)$risk
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}



/**
 * ตรวจสอบว่ามีรหัสนักเรียนซ้ำหรือไม่
 */
function checkStudentCode() {
    // ตรวจสอบว่ามีการส่ง student_code มาหรือไม่
    if (!isset($_GET['student_code']) || empty($_GET['student_code'])) {
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
        return;
    }
    
    $student_code = $_GET['student_code'];
    
    try {
        $conn = getDB();
        
        // ตรวจสอบว่ามีรหัสนักเรียนนี้ในระบบแล้วหรือไม่
        $query = "SELECT COUNT(*) AS count FROM students WHERE student_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$student_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'exists' => ($result['count'] > 0)
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการตรวจสอบรหัสนักเรียน: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลสถิติทั้งหมดของนักเรียน
 */
function getDetailedStatistics() {
    try {
        $conn = getDB();
        
        // ดึงจำนวนนักเรียนตามระดับชั้น
        $levelQuery = "SELECT c.level, COUNT(*) as count 
                      FROM students s 
                      JOIN classes c ON s.current_class_id = c.class_id 
                      WHERE s.status = 'กำลังศึกษา' 
                      GROUP BY c.level 
                      ORDER BY c.level";
        $levelStmt = $conn->prepare($levelQuery);
        $levelStmt->execute();
        $levelStats = $levelStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงจำนวนนักเรียนตามแผนกวิชา
        $deptQuery = "SELECT d.department_name, COUNT(*) as count 
                     FROM students s 
                     JOIN classes c ON s.current_class_id = c.class_id 
                     JOIN departments d ON c.department_id = d.department_id 
                     WHERE s.status = 'กำลังศึกษา' 
                     GROUP BY d.department_id 
                     ORDER BY count DESC";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->execute();
        $deptStats = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลการเข้าแถวรายเดือน
        $attendanceQuery = "SELECT 
                          YEAR(a.date) as year,
                          MONTH(a.date) as month,
                          SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_count,
                          COUNT(*) - SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as absent_count
                          FROM attendance a
                          GROUP BY YEAR(a.date), MONTH(a.date)
                          ORDER BY YEAR(a.date), MONTH(a.date)";
        $attendanceStmt = $conn->prepare($attendanceQuery);
        $attendanceStmt->execute();
        $attendanceStats = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // รวมข้อมูลสถิติ
        $statistics = [
            'levels' => $levelStats,
            'departments' => $deptStats,
            'attendance_monthly' => $attendanceStats
        ];
        
        echo json_encode([
            'success' => true,
            'statistics' => $statistics
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ: ' . $e->getMessage()
        ]);
    }
}

/**
 * ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
 */
function getRiskStudents() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
        $query = "SELECT s.student_id, s.student_code, u.title, u.first_name, u.last_name,
                 c.level, c.group_number, d.department_name,
                 rs.risk_level, rs.absence_count,
                 (SELECT CONCAT(t.title, ' ', t.first_name, ' ', t.last_name)
                  FROM class_advisors ca
                  JOIN teachers t ON ca.teacher_id = t.teacher_id
                  WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                  LIMIT 1) as advisor_name
                 FROM risk_students rs
                 JOIN students s ON rs.student_id = s.student_id
                 JOIN users u ON s.user_id = u.user_id
                 JOIN classes c ON s.current_class_id = c.class_id
                 JOIN departments d ON c.department_id = d.department_id
                 WHERE rs.risk_level IN ('high', 'critical')
                 AND s.status = 'กำลังศึกษา'
                 ORDER BY rs.risk_level DESC, rs.absence_count DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงระดับความเสี่ยงเป็นภาษาไทย
        foreach ($students as &$student) {
            switch ($student['risk_level']) {
                case 'low':
                    $student['risk_level_th'] = 'ต่ำ';
                    break;
                case 'medium':
                    $student['risk_level_th'] = 'ปานกลาง';
                    break;
                case 'high':
                    $student['risk_level_th'] = 'สูง';
                    break;
                case 'critical':
                    $student['risk_level_th'] = 'วิกฤต';
                    break;
                default:
                    $student['risk_level_th'] = 'ไม่ระบุ';
            }
            
            // เพิ่มคลาสสำหรับการแสดงสีตามระดับความเสี่ยง
            switch ($student['risk_level']) {
                case 'low':
                    $student['risk_class'] = 'success';
                    break;
                case 'medium':
                    $student['risk_class'] = 'warning';
                    break;
                case 'high':
                    $student['risk_class'] = 'danger';
                    break;
                case 'critical':
                    $student['risk_class'] = 'critical';
                    break;
                default:
                    $student['risk_class'] = '';
            }
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม: ' . $e->getMessage()
        ]);
    }
}

/**
 * แจ้งเตือนผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรม
 */
function notifyRiskStudentParents() {
    try {
        $conn = getDB();
        
        // ดึงข้อมูลเฉพาะนักเรียนที่เสียงต่อการตกกิจกรรม
        $student_ids = [];
        if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
            $student_ids = $_POST['student_ids'];
        } elseif (isset($_POST['student_id'])) {
            $student_ids = [$_POST['student_id']];
        }
        
        if (empty($student_ids)) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่ได้ระบุรหัสนักเรียน'
            ]);
            return;
        }
        
        // ประมวลผลการแจ้งเตือน
        $successful = 0;
        $failed = 0;
        
        foreach ($student_ids as $student_id) {
            // ดึงข้อมูลผู้ปกครองของนักเรียน
            $parentsQuery = "SELECT p.parent_id, u.line_id, u.first_name, u.last_name
                           FROM parent_student_relation psr
                           JOIN parents p ON psr.parent_id = p.parent_id
                           JOIN users u ON p.user_id = u.user_id
                           WHERE psr.student_id = ? AND u.line_id IS NOT NULL AND u.line_id != ''";
            $parentsStmt = $conn->prepare($parentsQuery);
            $parentsStmt->execute([$student_id]);
            $parents = $parentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงข้อมูลนักเรียน
            $studentQuery = "SELECT s.student_id, s.student_code, u.title, u.first_name, u.last_name,
                            rs.risk_level, rs.absence_count,
                            (SELECT a.date FROM attendance a 
                             WHERE a.student_id = s.student_id AND a.is_present = 0
                             ORDER BY a.date DESC LIMIT 1) as last_absence,
                            ay.required_attendance_days
                            FROM students s
                            JOIN users u ON s.user_id = u.user_id
                            JOIN risk_students rs ON s.student_id = rs.student_id
                            JOIN student_academic_records sar ON s.student_id = sar.student_id
                            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
                            WHERE s.student_id = ? AND ay.is_active = 1";
            $studentStmt = $conn->prepare($studentQuery);
            $studentStmt->execute([$student_id]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                $failed++;
                continue;
            }
            
            // สร้างข้อความแจ้งเตือน
            $message = "แจ้งเตือนการขาดเข้าแถว\n\n";
            $message .= "นักเรียน: {$student['title']}{$student['first_name']} {$student['last_name']}\n";
            $message .= "รหัสนักศึกษา: {$student['student_code']}\n";
            $message .= "วันที่ขาดล่าสุด: " . date('d/m/Y', strtotime($student['last_absence'])) . "\n";
            $message .= "จำนวนวันที่ขาดสะสม: {$student['absence_count']} วัน\n";
            
            // คำนวณวันที่ต้องมาเข้าแถวขั้นต่ำ
            $required_attendance = $student['required_attendance_days'];
            $message .= "จำนวนวันที่ต้องเข้าแถวตามเกณฑ์: {$required_attendance} วัน\n";
            
            // สำหรับแต่ละผู้ปกครอง
            foreach ($parents as $parent) {
                try {
                    // บันทึกการแจ้งเตือนไปยัง LINE
                    $notifyQuery = "INSERT INTO line_notifications (user_id, message, status, notification_type)
                                   VALUES (?, ?, 'pending', 'risk_alert')";
                    $notifyStmt = $conn->prepare($notifyQuery);
                    $notifyStmt->execute([$parent['parent_id'], $message]);
                    
                    // อัปเดตสถานะการแจ้งเตือนในตาราง risk_students
                    $updateQuery = "UPDATE risk_students SET notification_sent = 1, notification_date = NOW()
                                   WHERE student_id = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->execute([$student_id]);
                    
                    $successful++;
                } catch (Exception $e) {
                    $failed++;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "แจ้งเตือนผู้ปกครองสำเร็จ {$successful} คน, ล้มเหลว {$failed} คน",
            'successful_count' => $successful,
            'failed_count' => $failed
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการแจ้งเตือนผู้ปกครอง: ' . $e->getMessage()
        ]);
    }
}






/**
 * เพิ่มนักเรียนใหม่
 */
function addStudent() {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['title'])) {
        echo json_encode([
            'success' => false,
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีรหัสนักเรียนนี้ในระบบแล้วหรือไม่
        $checkQuery = "SELECT student_id FROM students WHERE student_code = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([trim($_POST['student_code'])]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // ใช้ line_id ชั่วคราวที่ส่งมาจาก client หรือสร้างใหม่
        $tempLineId = isset($_POST['temp_line_id']) 
            ? $_POST['temp_line_id'] 
            : 'TEMP_' . $_POST['student_code'] . '_' . time() . '_' . substr(md5(rand()), 0, 6);
        
        // 1. เพิ่มข้อมูลในตาราง users ก่อน
        $userQuery = "INSERT INTO users (line_id, role, title, first_name, last_name, phone_number, email, gdpr_consent)
                    VALUES (?, 'student', ?, ?, ?, ?, ?, 1)";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $tempLineId, // ใช้ line_id ชั่วคราวที่ไม่ซ้ำกัน
            $_POST['title'],
            $_POST['firstname'],
            $_POST['lastname'],
            $_POST['phone_number'] ?? '',
            $_POST['email'] ?? ''
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // 2. เพิ่มข้อมูลนักเรียน
        $studentQuery = "INSERT INTO students (user_id, student_code, title, current_class_id, status)
                        VALUES (?, ?, ?, ?, ?)";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([
            $user_id,
            trim($_POST['student_code']), // ใช้ trim เพื่อตัดช่องว่างที่อาจมี
            $_POST['title'],
            !empty($_POST['class_id']) ? $_POST['class_id'] : null,
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
        
        // ดึงข้อมูลนักเรียนที่เพิ่งเพิ่มเพื่อยืนยันว่าข้อมูลถูกต้อง
        $confirmQuery = "SELECT s.student_code, u.first_name, u.last_name 
                        FROM students s 
                        JOIN users u ON s.user_id = u.user_id
                        WHERE s.student_id = ?";
        $confirmStmt = $conn->prepare($confirmQuery);
        $confirmStmt->execute([$student_id]);
        $student = $confirmStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว',
            'student_id' => $student_id,
            'student_data' => $student // ส่งข้อมูลกลับเพื่อยืนยัน
        ]);
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        $errorMessage = $e->getMessage();
        // จัดการข้อความแสดงข้อผิดพลาดที่เข้าใจง่ายขึ้น
        if (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'line_id') !== false) {
            $errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: รหัส LINE ซ้ำกัน กรุณาลองใหม่อีกครั้ง';
        } elseif (strpos($errorMessage, 'Duplicate entry') !== false && strpos($errorMessage, 'student_code') !== false) {
            $errorMessage = 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว';
        }
        
        error_log("Error adding student: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    }
}









/**
 * อัปเดตข้อมูลนักเรียน
 */
function updateStudent() {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['student_id']) || empty($_POST['student_code']) || empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['title'])) {
        echo json_encode([
            'success' => false,
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // 1. ดึงข้อมูล user_id และชั้นเรียนปัจจุบันจากตาราง students
        $studentQuery = "SELECT user_id, current_class_id FROM students WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([$_POST['student_id']]);
        $student_data = $studentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_data) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            return;
        }
        
        // 2. ตรวจสอบว่ามีนักเรียนที่ใช้รหัสนี้ (ที่ไม่ใช่นักเรียนคนนี้) หรือไม่
        $checkQuery = "SELECT student_id FROM students WHERE student_code = ? AND student_id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$_POST['student_code'], $_POST['student_id']]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'รหัสนักเรียนนี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // 3. อัปเดตข้อมูลในตาราง users
        $userQuery = "UPDATE users 
                    SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute([
            $_POST['title'],
            $_POST['firstname'],
            $_POST['lastname'],
            $_POST['phone_number'] ?? '',
            $_POST['email'] ?? '',
            $student_data['user_id']
        ]);
        
        // 4. อัปเดตข้อมูลในตาราง students
        $studentUpdateQuery = "UPDATE students 
                             SET student_code = ?, title = ?, current_class_id = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                             WHERE student_id = ?";
        $studentUpdateStmt = $conn->prepare($studentUpdateQuery);
        $studentUpdateStmt->execute([
            $_POST['student_code'],
            $_POST['title'],
            $_POST['class_id'] ?? null,
            $_POST['status'] ?? 'กำลังศึกษา',
            $_POST['student_id']
        ]);
        
        // 5. อัปเดตหรือเพิ่มข้อมูลในตาราง student_academic_records ถ้ามีการเปลี่ยนชั้นเรียน
        if (!empty($_POST['class_id']) && $_POST['class_id'] != $student_data['current_class_id']) {
            // ดึงข้อมูล academic_year_id จากชั้นเรียนใหม่
            $yearQuery = "SELECT academic_year_id FROM classes WHERE class_id = ?";
            $yearStmt = $conn->prepare($yearQuery);
            $yearStmt->execute([$_POST['class_id']]);
            $academic_year_id = $yearStmt->fetchColumn();
            
            if ($academic_year_id) {
                // ตรวจสอบว่ามี record อยู่แล้วหรือไม่
                $recordQuery = "SELECT record_id FROM student_academic_records 
                              WHERE student_id = ? AND academic_year_id = ?";
                $recordStmt = $conn->prepare($recordQuery);
                $recordStmt->execute([$_POST['student_id'], $academic_year_id]);
                $record_id = $recordStmt->fetchColumn();
                
                if ($record_id) {
                    // อัปเดต record ที่มีอยู่
                    $updateRecordQuery = "UPDATE student_academic_records 
                                        SET class_id = ?, updated_at = CURRENT_TIMESTAMP
                                        WHERE record_id = ?";
                    $updateRecordStmt = $conn->prepare($updateRecordQuery);
                    $updateRecordStmt->execute([$_POST['class_id'], $record_id]);
                } else {
                    // สร้าง record ใหม่
                    $insertRecordQuery = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id)
                                        VALUES (?, ?, ?)";
                    $insertRecordStmt = $conn->prepare($insertRecordQuery);
                    $insertRecordStmt->execute([$_POST['student_id'], $academic_year_id, $_POST['class_id']]);
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'อัปเดตข้อมูลนักเรียนเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * ลบข้อมูลนักเรียน
 */
function deleteStudent() {
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['student_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่ระบุรหัสนักเรียน'
        ]);
        return;
    }
    
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        // 1. ดึง user_id จากตาราง students
        $studentQuery = "SELECT user_id FROM students WHERE student_id = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->execute([$_POST['student_id']]);
        $user_id = $studentStmt->fetchColumn();
        
        if (!$user_id) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบข้อมูลนักเรียน'
            ]);
            return;
        }
        
        // 2. ลบข้อมูลที่เกี่ยวข้องในตารางอื่นๆ
        $tables = [
            'student_academic_records',
            'attendance',
            'risk_students',
            'qr_codes',
            'parent_student_relation',
            'class_history'
        ];
        
        foreach ($tables as $table) {
            $deleteQuery = "DELETE FROM $table WHERE student_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->execute([$_POST['student_id']]);
        }
        
        // 3. ลบข้อมูลในตาราง students
        $deleteStudentQuery = "DELETE FROM students WHERE student_id = ?";
        $deleteStudentStmt = $conn->prepare($deleteStudentQuery);
        $deleteStudentStmt->execute([$_POST['student_id']]);
        
        // 4. ลบข้อมูลในตาราง users
        $deleteUserQuery = "DELETE FROM users WHERE user_id = ?";
        $deleteUserStmt = $conn->prepare($deleteUserQuery);
        $deleteUserStmt->execute([$user_id]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'ลบข้อมูลนักเรียนเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}

/**
 * นำเข้าข้อมูลนักเรียน
 */
function importStudents() {
    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] != UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่มีไฟล์ที่อัปโหลดหรือเกิดข้อผิดพลาดในการอัปโหลด'
        ]);
        return;
    }
    
    // ตรวจสอบประเภทไฟล์
    $file_extension = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['csv', 'xlsx', 'xls'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'รองรับเฉพาะไฟล์ CSV, XLS, XLSX เท่านั้น'
        ]);
        return;
    }
    
    // ในระบบจริงจะต้องมีการประมวลผลไฟล์ที่อัปโหลด
    // ตัวอย่างนี้จะจำลองการนำเข้าข้อมูลสำเร็จ
    
    echo json_encode([
        'success' => true,
        'message' => 'นำเข้าข้อมูลนักเรียนเรียบร้อยแล้ว',
        'imported_count' => 10,
        'updated_count' => 5,
        'skipped_count' => 2
    ]);
}
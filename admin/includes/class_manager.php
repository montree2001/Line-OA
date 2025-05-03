<?php
/**
 * class_manager.php - API สำหรับจัดการข้อมูลชั้นเรียนและแผนกวิชา
 * ระบบ STP-Prasat (Student Tracking Platform)
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

// ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ส่งข้อความแจ้งเตือน)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'คุณไม่มีสิทธิ์เข้าใช้งานส่วนนี้'
    ]);
    exit;
}

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล
require_once '../includes/db_connect.php';

// ตรวจสอบการเรียก API
if (isset($_POST['action']) || isset($_GET['action'])) {
    $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
    
    switch ($action) {
        // === แผนกวิชา ===
        case 'get_department_details':
            handleGetDepartmentDetails();
            break;
            
        case 'add_department':
            handleAddDepartment();
            break;
            
        case 'edit_department':
            handleEditDepartment();
            break;
            
        case 'delete_department':
            handleDeleteDepartment();
            break;
            
        // === ชั้นเรียน ===
        case 'get_class_details':
            handleGetClassDetails();
            break;
            
        case 'add_class':
            handleAddClass();
            break;
            
        case 'edit_class':
            handleEditClass();
            break;
            
        case 'delete_class':
            handleDeleteClass();
            break;
            
        // === ครูที่ปรึกษา ===
      case 'get_class_advisors':
    // ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
    $class_id = isset($_POST['class_id']) ? $_POST['class_id'] : (isset($_GET['class_id']) ? $_GET['class_id'] : '');
    
    if (empty($class_id)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่ระบุรหัสชั้นเรียน'
        ]);
        break;
    }
    
    $result = getClassAdvisors($class_id);
    
    // ตรวจสอบว่าผลลัพธ์เป็น array หรือไม่
    if (!is_array($result)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ผลลัพธ์ไม่ถูกต้อง'
        ]);
        break;
    }
    
    echo json_encode($result);
    break;
            
        case 'manage_advisors':
            handleManageAdvisors();
            break;
            
        // === เลื่อนชั้น ===
        case 'promote_students':
            handlePromoteStudents();
            break;
            
        // === ปีการศึกษา ===
        case 'add_academic_year':
            handleAddAcademicYear();
            break;
            
        // === รายงาน ===
        case 'download_report':
            handleDownloadReport();
            break;
            
        default:
            // ส่งข้อความแจ้งเตือนกรณีไม่พบ action
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบการกระทำที่ระบุ'
            ]);
            break;
    }
} else {
    // ส่งข้อความแจ้งเตือนกรณีไม่มีการส่ง action
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่มีการระบุการกระทำ'
    ]);
}

// =================================================================
// ฟังก์ชันสำหรับจัดการแผนกวิชา
// =================================================================

/**
 * ดึงข้อมูลรายละเอียดแผนกวิชา
 */
function handleGetDepartmentDetails() {
    global $conn;
    
    // ตรวจสอบว่ามีการส่ง department_id มาหรือไม่
    if (!isset($_POST['department_id']) && !isset($_GET['department_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่ระบุรหัสแผนกวิชา'
        ]);
        return;
    }
    
    // รับค่า department_id
    $departmentId = isset($_POST['department_id']) ? $_POST['department_id'] : $_GET['department_id'];
    
    try {
        // ดึงข้อมูลแผนกวิชา
        $stmt = $conn->prepare("
            SELECT department_id, department_code, department_name, is_active
            FROM departments
            WHERE department_id = ?
        ");
        $stmt->execute([$departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลแผนกวิชา'
            ]);
            return;
        }
        
        // ตรวจสอบว่าข้อมูลมีครบถ้วนหรือไม่ ถ้าไม่ใส่ค่าเริ่มต้น
        if (!isset($department['department_id'])) $department['department_id'] = $departmentId;
        if (!isset($department['department_code'])) $department['department_code'] = $departmentId;
        if (!isset($department['department_name'])) $department['department_name'] = '';
        
        // ดึงจำนวนชั้นเรียนในแผนกวิชา
        $stmt = $conn->prepare("
            SELECT COUNT(*) as class_count
            FROM classes
            WHERE department_id = ? AND is_active = 1
        ");
        $stmt->execute([$departmentId]);
        $classCount = $stmt->fetch(PDO::FETCH_ASSOC)['class_count'];
        
        // ดึงจำนวนนักเรียนในแผนกวิชา
        $stmt = $conn->prepare("
            SELECT COUNT(*) as student_count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.department_id = ? AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$departmentId]);
        $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];
        
        // ดึงจำนวนครูในแผนกวิชา
        $stmt = $conn->prepare("
            SELECT COUNT(*) as teacher_count
            FROM teachers
            WHERE department_id = ?
        ");
        $stmt->execute([$departmentId]);
        $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['teacher_count'];
        
        // เพิ่มข้อมูลสถิติลงในผลลัพธ์
        $department['class_count'] = $classCount ?? 0;
        $department['student_count'] = $studentCount ?? 0;
        $department['teacher_count'] = $teacherCount ?? 0;
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'department' => $department
        ]);
    } catch (PDOException $e) {
        error_log("Error in handleGetDepartmentDetails: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * เพิ่มแผนกวิชาใหม่
 */
function handleAddDepartment() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['department_name']) || empty($_POST['department_name'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุชื่อแผนกวิชา'
        ]);
        return;
    }
    
    $departmentName = trim($_POST['department_name']);
    $departmentCode = isset($_POST['department_code']) ? strtoupper(trim($_POST['department_code'])) : '';
    
    // ถ้าไม่มีรหัสแผนกวิชา ให้สร้างจากชื่อแผนกวิชา
    if (empty($departmentCode)) {
        // ใช้ตัวอักษรตัวแรกของทุกคำในชื่อแผนกวิชา (เฉพาะภาษาอังกฤษ)
        preg_match_all('/\b([a-zA-Z])/u', $departmentName, $matches);
        if (!empty($matches[1])) {
            $departmentCode = strtoupper(implode('', array_slice($matches[1], 0, 4)));
        } else {
            // หากไม่มีตัวอักษรภาษาอังกฤษ ใช้ตัวย่อภาษาไทย (รับมาจากฟอร์ม)
            $departmentCode = 'DEPT' . rand(10, 99);
        }
    }
    
    try {
        // ตรวจสอบว่ามีรหัสแผนกวิชาซ้ำหรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM departments WHERE department_code = ?");
        $stmt->execute([$departmentCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'รหัสแผนกวิชานี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีชื่อแผนกวิชาซ้ำหรือไม่
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM departments WHERE department_name = ?");
        $stmt->execute([$departmentName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ชื่อแผนกวิชานี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // เพิ่มข้อมูลแผนกวิชา
        $stmt = $conn->prepare("
            INSERT INTO departments (department_code, department_name)
            VALUES (?, ?)
        ");
        $stmt->execute([$departmentCode, $departmentName]);
        
        $departmentId = $conn->lastInsertId();
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'add_department', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'department_id' => $departmentId,
                'department_code' => $departmentCode,
                'department_name' => $departmentName
            ])
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มแผนกวิชาใหม่เรียบร้อยแล้ว',
            'department_id' => $departmentId
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * แก้ไขข้อมูลแผนกวิชา
 */
function handleEditDepartment() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['department_id']) || empty($_POST['department_id']) ||
        !isset($_POST['department_name']) || empty($_POST['department_name'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    $departmentId = $_POST['department_id'];
    $departmentName = trim($_POST['department_name']);
    
    try {
        // ตรวจสอบว่ามีแผนกวิชาที่ต้องการแก้ไขหรือไม่
        $stmt = $conn->prepare("SELECT department_code FROM departments WHERE department_id = ?");
        $stmt->execute([$departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลแผนกวิชาที่ต้องการแก้ไข'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีชื่อแผนกวิชาซ้ำหรือไม่ (ยกเว้นแผนกวิชาที่กำลังแก้ไข)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM departments 
            WHERE department_name = ? AND department_id != ?
        ");
        $stmt->execute([$departmentName, $departmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ชื่อแผนกวิชานี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // แก้ไขข้อมูลแผนกวิชา
        $stmt = $conn->prepare("
            UPDATE departments
            SET department_name = ?, updated_at = CURRENT_TIMESTAMP
            WHERE department_id = ?
        ");
        $stmt->execute([$departmentName, $departmentId]);
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'edit_department', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'department_id' => $departmentId,
                'department_code' => $department['department_code'],
                'department_name' => $departmentName
            ])
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลแผนกวิชาเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * ลบแผนกวิชา
 */
function handleDeleteDepartment() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุรหัสแผนกวิชา'
        ]);
        return;
    }
    
    $departmentId = $_POST['department_id'];
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีแผนกวิชาที่ต้องการลบหรือไม่
        $stmt = $conn->prepare("
            SELECT department_id, department_code, department_name
            FROM departments 
            WHERE department_id = ?
        ");
        $stmt->execute([$departmentId]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลแผนกวิชาที่ต้องการลบ'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีชั้นเรียนในแผนกวิชานี้หรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM classes 
            WHERE department_id = ? AND is_active = 1
        ");
        $stmt->execute([$departmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถลบแผนกวิชาได้ เนื่องจากมีชั้นเรียนที่ใช้งานอยู่'
            ]);
            return;
        }
        
        // ลบข้อมูลแผนกวิชา (ในระบบจริงอาจใช้การ soft delete แทน)
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->execute([$departmentId]);
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'remove_department', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'department_id' => $department['department_id'],
                'department_code' => $department['department_code'],
                'department_name' => $department['department_name']
            ])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'ลบแผนกวิชาเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
        ]);
    }
}

// =================================================================
// ฟังก์ชันสำหรับจัดการชั้นเรียน
// =================================================================

/**
 * ดึงข้อมูลรายละเอียดชั้นเรียน
 */
function handleGetClassDetails() {
    global $conn;
    
    // ตรวจสอบว่ามีการส่ง class_id มาหรือไม่
    if (!isset($_POST['class_id']) && !isset($_GET['class_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่ระบุรหัสชั้นเรียน'
        ]);
        return;
    }
    
    // รับค่า class_id
    $classId = isset($_POST['class_id']) ? $_POST['class_id'] : $_GET['class_id'];
    
    try {
        // ดึงข้อมูลชั้นเรียนพร้อมรายละเอียด
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.academic_year_id, c.level, c.department_id, 
                c.group_number, c.classroom, c.is_active,
                d.department_name, d.department_code,
                ay.year, ay.semester, ay.required_attendance_days
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียน'
            ]);
            return;
        }
        
        // ดึงจำนวนนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT COUNT(*) as student_count
            FROM students
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'
        ");
        $stmt->execute([$classId]);
        $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id as id,
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary,
                t.position
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                s.student_id,
                s.student_code as code,
                CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as name,
                sar.total_attendance_days as attendance,
                (sar.total_attendance_days + sar.total_absence_days) as total,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) = 0 THEN 0
                    ELSE (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100
                END as percent,
                CASE
                    WHEN sar.passed_activity IS NULL THEN 'รอประเมิน'
                    WHEN sar.passed_activity = 1 THEN 'ผ่าน'
                    ELSE 'ไม่ผ่าน'
                END as status
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ");
        $stmt->execute([$class['academic_year_id'], $classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลสถิติการเข้าแถว
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) as absent_days
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            WHERE s.current_class_id = ? AND a.academic_year_id = ?
        ");
        $stmt->execute([$classId, $class['academic_year_id']]);
        $attendanceStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendanceStats['present_days']) {
            $attendanceStats['present_days'] = 0;
        }
        if (!$attendanceStats['absent_days']) {
            $attendanceStats['absent_days'] = 0;
        }
        
        // คำนวณอัตราการเข้าแถวโดยรวม
        $totalDays = $attendanceStats['present_days'] + $attendanceStats['absent_days'];
        if ($totalDays > 0) {
            $attendanceStats['overall_rate'] = ($attendanceStats['present_days'] / $totalDays) * 100;
        } else {
            $attendanceStats['overall_rate'] = 0;
        }
        
        // ดึงข้อมูลสถิติรายเดือน
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(a.date, '%Y-%m') as month_year,
                DATE_FORMAT(a.date, '%m/%Y') as month,
                SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) as absent
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            WHERE s.current_class_id = ? AND a.academic_year_id = ?
            GROUP BY month_year
            ORDER BY month_year
        ");
        $stmt->execute([$classId, $class['academic_year_id']]);
        $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $attendanceStats['monthly'] = $monthlyStats;
        
        // เพิ่มข้อมูลสถิติลงในผลลัพธ์
        $class['student_count'] = $studentCount;
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'class' => $class,
            'advisors' => $advisors,
            'students' => $students,
            'attendance_stats' => $attendanceStats
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * เพิ่มชั้นเรียนใหม่
 */
function handleAddClass() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['academic_year_id']) || empty($_POST['academic_year_id']) ||
        !isset($_POST['level']) || empty($_POST['level']) ||
        !isset($_POST['department_id']) || empty($_POST['department_id']) ||
        !isset($_POST['group_number']) || empty($_POST['group_number'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    $academicYearId = $_POST['academic_year_id'];
    $level = $_POST['level'];
    $departmentId = $_POST['department_id'];
    $groupNumber = $_POST['group_number'];
    $classroom = isset($_POST['classroom']) ? $_POST['classroom'] : null;
    
    try {
        // ตรวจสอบว่ามีปีการศึกษาที่ระบุหรือไม่
        $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE academic_year_id = ?");
        $stmt->execute([$academicYearId]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบปีการศึกษาที่ระบุ'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีแผนกวิชาที่ระบุหรือไม่
        $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
        $stmt->execute([$departmentId]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบแผนกวิชาที่ระบุ'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM classes 
            WHERE academic_year_id = ? AND level = ? AND department_id = ? AND group_number = ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ชั้นเรียนนี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // เพิ่มข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            INSERT INTO classes (academic_year_id, level, department_id, group_number, classroom)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classroom]);
        
        $classId = $conn->lastInsertId();
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'add_class', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'level' => $level,
                'department_id' => $departmentId,
                'group_number' => $groupNumber
            ])
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มชั้นเรียนใหม่เรียบร้อยแล้ว',
            'class_id' => $classId
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * แก้ไขข้อมูลชั้นเรียน
 */
function handleEditClass() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['class_id']) || empty($_POST['class_id']) ||
        !isset($_POST['academic_year_id']) || empty($_POST['academic_year_id']) ||
        !isset($_POST['level']) || empty($_POST['level']) ||
        !isset($_POST['department_id']) || empty($_POST['department_id']) ||
        !isset($_POST['group_number']) || empty($_POST['group_number'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    $classId = $_POST['class_id'];
    $academicYearId = $_POST['academic_year_id'];
    $level = $_POST['level'];
    $departmentId = $_POST['department_id'];
    $groupNumber = $_POST['group_number'];
    $classroom = isset($_POST['classroom']) ? $_POST['classroom'] : null;
    
    try {
        // ตรวจสอบว่ามีชั้นเรียนที่ต้องการแก้ไขหรือไม่
        $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียนที่ต้องการแก้ไข'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่ (ยกเว้นชั้นเรียนที่กำลังแก้ไข)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM classes 
            WHERE academic_year_id = ? AND level = ? AND department_id = ? AND group_number = ? AND class_id != ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ชั้นเรียนนี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // แก้ไขข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            UPDATE classes
            SET academic_year_id = ?, level = ?, department_id = ?, group_number = ?, classroom = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE class_id = ?
        ");
        $stmt->execute([$academicYearId, $level, $departmentId, $groupNumber, $classroom, $classId]);
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'edit_class', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'level' => $level,
                'department_id' => $departmentId,
                'group_number' => $groupNumber
            ])
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'แก้ไขข้อมูลชั้นเรียนเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * ลบชั้นเรียน
 */
function handleDeleteClass() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['class_id']) || empty($_POST['class_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุรหัสชั้นเรียน'
        ]);
        return;
    }
    
    $classId = $_POST['class_id'];
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีชั้นเรียนที่ต้องการลบหรือไม่
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.level, c.group_number, 
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียนที่ต้องการลบ'
            ]);
            return;
        }
        
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนนี้หรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM students 
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'
        ");
        $stmt->execute([$classId]);
        $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($studentCount > 0) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถลบชั้นเรียนได้ เนื่องจากมีนักเรียนอยู่ในชั้นเรียนนี้'
            ]);
            return;
        }
        
        // ลบข้อมูลครูที่ปรึกษาในชั้นเรียนนี้
        $stmt = $conn->prepare("DELETE FROM class_advisors WHERE class_id = ?");
        $stmt->execute([$classId]);
        
        // ลบข้อมูลชั้นเรียน (ในระบบจริงอาจใช้การ soft delete แทน)
        $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'remove_class', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'class_id' => $class['class_id'],
                'class_name' => "{$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']}",
                'academic_year' => "{$class['year']} (ภาคเรียนที่ {$class['semester']})"
            ])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'ลบชั้นเรียนเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
        ]);
    }
}

// =================================================================
// ฟังก์ชันสำหรับจัดการครูที่ปรึกษา
// =================================================================

/**
 * ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
 */
function handleGetClassAdvisors() {
    global $conn;
    
    // ตรวจสอบว่ามีการส่ง class_id มาหรือไม่
    if (!isset($_POST['class_id']) && !isset($_GET['class_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่ระบุรหัสชั้นเรียน'
        ]);
        return;
    }
    
    // รับค่า class_id
    $classId = isset($_POST['class_id']) ? $_POST['class_id'] : $_GET['class_id'];
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.level, c.group_number, 
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียน'
            ]);
            return;
        }
        
        // สร้างชื่อชั้นเรียน
        $className = "{$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']}";
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id as id,
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary,
                t.position
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'class_id' => $class['class_id'],
            'class_name' => $className,
            'advisors' => $advisors
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }
}

/**
 * จัดการครูที่ปรึกษา
 */
function handleManageAdvisors() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['class_id']) || empty($_POST['class_id']) ||
        !isset($_POST['changes']) || empty($_POST['changes'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ข้อมูลไม่ครบถ้วน'
        ]);
        return;
    }
    
    $classId = $_POST['class_id'];
    $changes = json_decode($_POST['changes'], true);
    
    if (!is_array($changes) || empty($changes)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่มีข้อมูลการเปลี่ยนแปลง'
        ]);
        return;
    }
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีชั้นเรียนที่ระบุหรือไม่
        $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        if (!$stmt->fetch()) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบชั้นเรียนที่ระบุ'
            ]);
            return;
        }
        
        // ดำเนินการตามการเปลี่ยนแปลง
        foreach ($changes as $change) {
            if (!isset($change['action']) || !isset($change['teacher_id'])) {
                continue;
            }
            
            $action = $change['action'];
            $teacherId = $change['teacher_id'];
            
            switch ($action) {
                case 'add':
                    // ตรวจสอบว่ามีครูที่ระบุหรือไม่
                    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ?");
                    $stmt->execute([$teacherId]);
                    if (!$stmt->fetch()) {
                        continue;
                    }
                    
                    // ตรวจสอบว่าครูเป็นที่ปรึกษาของชั้นเรียนนี้อยู่แล้วหรือไม่
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                        continue;
                    }
                    
                    // ถ้าตั้งเป็นที่ปรึกษาหลัก ให้ยกเลิกที่ปรึกษาหลักคนเดิม
                    if (isset($change['is_primary']) && $change['is_primary']) {
                        $stmt = $conn->prepare("
                            UPDATE class_advisors 
                            SET is_primary = 0 
                            WHERE class_id = ? AND is_primary = 1
                        ");
                        $stmt->execute([$classId]);
                    }
                    
                    // เพิ่มครูที่ปรึกษา
                    $stmt = $conn->prepare("
                        INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $classId,
                        $teacherId,
                        isset($change['is_primary']) && $change['is_primary'] ? 1 : 0
                    ]);
                    break;
                    
                case 'remove':
                    // ลบครูที่ปรึกษา
                    $stmt = $conn->prepare("
                        DELETE FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    break;
                    
                case 'set_primary':
                    // ตรวจสอบว่าครูเป็นที่ปรึกษาของชั้นเรียนนี้หรือไม่
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
                        continue;
                    }
                    
                    // ยกเลิกที่ปรึกษาหลักคนเดิม
                    $stmt = $conn->prepare("
                        UPDATE class_advisors 
                        SET is_primary = 0 
                        WHERE class_id = ? AND is_primary = 1
                    ");
                    $stmt->execute([$classId]);
                    
                    // ตั้งเป็นที่ปรึกษาหลัก
                    $stmt = $conn->prepare("
                        UPDATE class_advisors 
                        SET is_primary = 1 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    break;
            }
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'manage_advisors', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'class_id' => $classId,
                'changes' => $changes
            ])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'จัดการครูที่ปรึกษาเรียบร้อยแล้ว'
        ]);
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการจัดการข้อมูล: ' . $e->getMessage()
        ]);
    }
}

// =================================================================
// ฟังก์ชันสำหรับการเลื่อนชั้น
// =================================================================

/**
 * เลื่อนชั้นนักเรียน
 */
function handlePromoteStudents() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['from_academic_year_id']) || empty($_POST['from_academic_year_id']) ||
        !isset($_POST['to_academic_year_id']) || empty($_POST['to_academic_year_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'
        ]);
        return;
    }
    
    $fromAcademicYearId = $_POST['from_academic_year_id'];
    $toAcademicYearId = $_POST['to_academic_year_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $adminId = $_SESSION['user_id'];
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบว่ามีปีการศึกษาที่ระบุหรือไม่
        $stmt = $conn->prepare("
            SELECT academic_year_id, year, semester 
            FROM academic_years 
            WHERE academic_year_id IN (?, ?)
        ");
        $stmt->execute([$fromAcademicYearId, $toAcademicYearId]);
        $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($academicYears) != 2) {
            $conn->rollBack();
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบปีการศึกษาที่ระบุ'
            ]);
            return;
        }
        
        // สร้างรายการ batch
        $stmt = $conn->prepare("
            INSERT INTO student_promotion_batch (
                from_academic_year_id, to_academic_year_id, status, notes, created_by
            ) VALUES (?, ?, 'in_progress', ?, ?)
        ");
        $stmt->execute([$fromAcademicYearId, $toAcademicYearId, $notes, $adminId]);
        $batchId = $conn->lastInsertId();
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'promote_students', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'batch_id' => $batchId,
                'from_academic_year_id' => $fromAcademicYearId,
                'to_academic_year_id' => $toAcademicYearId
            ])
        ]);
        
        // ดึงข้อมูลนักเรียนและชั้นเรียนในปีการศึกษาต้นทาง
        $stmt = $conn->prepare("
            SELECT 
                s.student_id, s.current_class_id, c.level, c.department_id, c.group_number
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$fromAcademicYearId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $promotedCount = 0;
        $graduatedCount = 0;
        
        // ดำเนินการเลื่อนชั้นนักเรียน
        foreach ($students as $student) {
            $studentId = $student['student_id'];
            $currentClassId = $student['current_class_id'];
            $currentLevel = $student['level'];
            $departmentId = $student['department_id'];
            $groupNumber = $student['group_number'];
            
            // กำหนดระดับชั้นใหม่
            $newLevel = '';
            $promotionType = '';
            
            if ($currentLevel == 'ปวช.1') {
                $newLevel = 'ปวช.2';
                $promotionType = 'promotion';
            } else if ($currentLevel == 'ปวช.2') {
                $newLevel = 'ปวช.3';
                $promotionType = 'promotion';
            } else if ($currentLevel == 'ปวส.1') {
                $newLevel = 'ปวส.2';
                $promotionType = 'promotion';
            } else if ($currentLevel == 'ปวช.3' || $currentLevel == 'ปวส.2') {
                $newLevel = $currentLevel; // คงระดับชั้นเดิม
                $promotionType = 'graduation';
                
                // อัพเดตสถานะนักเรียนเป็นสำเร็จการศึกษา
                $stmt = $conn->prepare("
                    UPDATE students 
                    SET status = 'สำเร็จการศึกษา', 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE student_id = ?
                ");
                $stmt->execute([$studentId]);
                
                $graduatedCount++;
                continue; // ข้ามไปนักเรียนคนถัดไป
            } else {
                // กรณีไม่ทราบระดับชั้น
                continue;
            }
            
            // ค้นหาชั้นเรียนใหม่ในปีการศึกษาปลายทาง
            $stmt = $conn->prepare("
                SELECT class_id 
                FROM classes 
                WHERE academic_year_id = ? AND level = ? AND department_id = ? AND group_number = ?
            ");
            $stmt->execute([$toAcademicYearId, $newLevel, $departmentId, $groupNumber]);
            $newClass = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$newClass) {
                // สร้างชั้นเรียนใหม่ถ้ายังไม่มี
                $stmt = $conn->prepare("
                    INSERT INTO classes (academic_year_id, level, department_id, group_number)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$toAcademicYearId, $newLevel, $departmentId, $groupNumber]);
                $newClassId = $conn->lastInsertId();
            } else {
                $newClassId = $newClass['class_id'];
            }
            
            // บันทึกประวัติการเลื่อนชั้น
            $stmt = $conn->prepare("
                INSERT INTO class_history (
                    student_id, previous_class_id, new_class_id, 
                    previous_level, new_level, 
                    academic_year_id, promotion_type, promotion_notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $studentId, $currentClassId, $newClassId,
                $currentLevel, $newLevel,
                $toAcademicYearId, $promotionType, $notes, $adminId
            ]);
            
            // อัพเดตชั้นเรียนของนักเรียน
            $stmt = $conn->prepare("
                UPDATE students 
                SET current_class_id = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE student_id = ?
            ");
            $stmt->execute([$newClassId, $studentId]);
            
            // สร้างประวัติการศึกษาสำหรับปีการศึกษาใหม่
            $stmt = $conn->prepare("
                INSERT INTO student_academic_records (
                    student_id, academic_year_id, class_id
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([$studentId, $toAcademicYearId, $newClassId]);
            
            $promotedCount++;
        }
        
        // อัพเดตสถานะ batch
        $stmt = $conn->prepare("
            UPDATE student_promotion_batch
            SET status = 'completed', 
                students_count = ?, 
                graduates_count = ?
            WHERE batch_id = ?
        ");
        $stmt->execute([$promotedCount, $graduatedCount, $batchId]);
        
        // Commit transaction
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'เลื่อนชั้นนักเรียนเรียบร้อยแล้ว',
            'batch_id' => $batchId,
            'promoted_count' => $promotedCount,
            'graduated_count' => $graduatedCount
        ]);
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()
        ]);
    }
}

// =================================================================
// ฟังก์ชันสำหรับจัดการปีการศึกษา
// =================================================================

/**
 * เพิ่มปีการศึกษาใหม่
 */
function handleAddAcademicYear() {
    global $conn;
    
    // ตรวจสอบข้อมูลที่ส่งมา
    if (!isset($_POST['year']) || empty($_POST['year']) ||
        !isset($_POST['semester']) || empty($_POST['semester']) ||
        !isset($_POST['start_date']) || empty($_POST['start_date']) ||
        !isset($_POST['end_date']) || empty($_POST['end_date'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'
        ]);
        return;
    }
    
    $year = (int)$_POST['year'];
    $semester = (int)$_POST['semester'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $requiredDays = isset($_POST['required_days']) ? (int)$_POST['required_days'] : 80;
    
    try {
        // ตรวจสอบว่ามีปีการศึกษานี้อยู่แล้วหรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM academic_years 
            WHERE year = ? AND semester = ?
        ");
        $stmt->execute([$year, $semester]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ปีการศึกษานี้มีอยู่ในระบบแล้ว'
            ]);
            return;
        }
        
        // เพิ่มปีการศึกษาใหม่
        $stmt = $conn->prepare("
            INSERT INTO academic_years (
                year, semester, start_date, end_date, required_attendance_days, is_active
            ) VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$year, $semester, $startDate, $endDate, $requiredDays]);
        
        $academicYearId = $conn->lastInsertId();
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'create_academic_year', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'academic_year_id' => $academicYearId,
                'year' => $year,
                'semester' => $semester
            ])
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มปีการศึกษาใหม่เรียบร้อยแล้ว',
            'academic_year_id' => $academicYearId
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $e->getMessage()
        ]);
    }
}

// =================================================================
// ฟังก์ชันสำหรับการดาวน์โหลดรายงาน
// =================================================================

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 */
function handleDownloadReport() {
    global $conn;
    
    // ตรวจสอบว่ามีการส่ง class_id มาหรือไม่
    if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่ระบุรหัสชั้นเรียน'
        ]);
        return;
    }
    
    $classId = $_GET['class_id'];
    $reportType = isset($_GET['type']) ? $_GET['type'] : 'full';
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.level, c.group_number, c.classroom,
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียน'
            ]);
            return;
        }
        
        // สร้างชื่อไฟล์
        $className = "{$class['level']}_group{$class['group_number']}_{$class['department_name']}";
        $fileName = "class_report_{$className}_{$class['year']}_{$class['semester']}.csv";
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                s.student_id, s.student_code,
                u.title, u.first_name, u.last_name,
                sar.total_attendance_days as attendance,
                sar.total_absence_days as absence,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) = 0 THEN 0
                    ELSE (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100
                END as percent,
                CASE
                    WHEN sar.passed_activity IS NULL THEN 'รอประเมิน'
                    WHEN sar.passed_activity = 1 THEN 'ผ่าน'
                    ELSE 'ไม่ผ่าน'
                END as activity_status
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.class_id = ?
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ");
        $stmt->execute([$classId, $classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // สร้างไฟล์ CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        // เปิด output stream
        $output = fopen('php://output', 'w');
        
        // เขียน BOM (Byte Order Mark) สำหรับ UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // เขียนหัวข้อรายงาน
        fputcsv($output, [
            "รายงานข้อมูลชั้นเรียน: {$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']} " .
            "ปีการศึกษา {$class['year']} ภาคเรียนที่ {$class['semester']}"
        ]);
        
        fputcsv($output, [""]);
        
        // เขียนข้อมูลครูที่ปรึกษา
        fputcsv($output, ["ครูที่ปรึกษา:"]);
        foreach ($advisors as $advisor) {
            fputcsv($output, [
                $advisor['name'] . ($advisor['is_primary'] ? ' (ที่ปรึกษาหลัก)' : '')
            ]);
        }
        
        fputcsv($output, [""]);
        
        // เขียนหัวตาราง
        fputcsv($output, [
            "รหัสนักศึกษา", "คำนำหน้า", "ชื่อ", "นามสกุล", 
            "วันที่เข้าแถว", "วันที่ขาด", "รวมวัน", "ร้อยละ", "สถานะกิจกรรม"
        ]);
        
        // เขียนข้อมูลนักเรียน
        foreach ($students as $student) {
            $totalDays = $student['attendance'] + $student['absence'];
            fputcsv($output, [
                $student['student_code'],
                $student['title'],
                $student['first_name'],
                $student['last_name'],
                $student['attendance'] ?? 0,
                $student['absence'] ?? 0,
                $totalDays,
                round($student['percent'], 2),
                $student['activity_status']
            ]);
        }
        
        // ปิด output stream
        fclose($output);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ]);
    }
}
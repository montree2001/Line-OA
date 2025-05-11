<?php
/**
 * activities.php - หน้าจัดการกิจกรรมกลาง
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'activities';
$page_title = 'จัดการกิจกรรมกลาง';
$page_header = 'ระบบจัดการกิจกรรมกลาง';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';



// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// -------- จัดการกับการบันทึกกิจกรรม (POST) --------
$save_success = false;
$save_error = false;
$error_message = '';
$response_message = '';

// จัดการการบันทึกกิจกรรมใหม่
if (isset($_POST['save_activity'])) {
    try {
        $activity_name = $_POST['activity_name'] ?? '';
        $activity_date = $_POST['activity_date'] ?? '';
        $activity_location = $_POST['activity_location'] ?? '';
        $activity_description = $_POST['activity_description'] ?? '';
        $required_attendance = isset($_POST['required_attendance']) ? 1 : 0;
        $target_departments = $_POST['target_departments'] ?? [];
        $target_levels = $_POST['target_levels'] ?? [];
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($activity_name) || empty($activity_date)) {
            throw new Exception("กรุณาระบุชื่อกิจกรรมและวันที่จัดกิจกรรม");
        }
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // เพิ่มข้อมูลกิจกรรม
        $stmt = $conn->prepare("
            INSERT INTO activities (
                activity_name, activity_date, activity_location, description, 
                academic_year_id, required_attendance, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $activity_name,
            $activity_date,
            $activity_location,
            $activity_description,
            $current_academic_year_id,
            $required_attendance,
            $user_id
        ]);
        
        $activity_id = $conn->lastInsertId();
        
        // บันทึกกลุ่มเป้าหมาย (แผนกวิชา)
        if (!empty($target_departments)) {
            $placeholders = implode(',', array_fill(0, count($target_departments), '(?, ?)'));
            $values = [];
            
            foreach ($target_departments as $dept_id) {
                $values[] = $activity_id;
                $values[] = $dept_id;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO activity_target_departments (activity_id, department_id) 
                VALUES $placeholders
            ");
            $stmt->execute($values);
        }
        
        // บันทึกกลุ่มเป้าหมาย (ระดับชั้น)
        if (!empty($target_levels)) {
            $placeholders = implode(',', array_fill(0, count($target_levels), '(?, ?)'));
            $values = [];
            
            foreach ($target_levels as $level) {
                $values[] = $activity_id;
                $values[] = $level;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO activity_target_levels (activity_id, level) 
                VALUES $placeholders
            ");
            $stmt->execute($values);
        }
        
        /* // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'create_activity';
        $action_details = json_encode([
            'activity_id' => $activity_id,
            'activity_name' => $activity_name,
            'activity_date' => $activity_date
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $action_type, $action_details]);
         */
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "บันทึกกิจกรรม '$activity_name' เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// จัดการการแก้ไขกิจกรรม
if (isset($_POST['edit_activity'])) {
    try {
        $activity_id = $_POST['activity_id'] ?? 0;
        $activity_name = $_POST['activity_name'] ?? '';
        $activity_date = $_POST['activity_date'] ?? '';
        $activity_location = $_POST['activity_location'] ?? '';
        $activity_description = $_POST['activity_description'] ?? '';
        $required_attendance = isset($_POST['required_attendance']) ? 1 : 0;
        $target_departments = $_POST['target_departments'] ?? [];
        $target_levels = $_POST['target_levels'] ?? [];
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($activity_id) || empty($activity_name) || empty($activity_date)) {
            throw new Exception("กรุณาระบุชื่อกิจกรรมและวันที่จัดกิจกรรม");
        }
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // อัปเดตข้อมูลกิจกรรม
        $stmt = $conn->prepare("
            UPDATE activities SET
                activity_name = ?,
                activity_date = ?,
                activity_location = ?,
                description = ?,
                required_attendance = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE activity_id = ?
        ");
        $stmt->execute([
            $activity_name,
            $activity_date,
            $activity_location,
            $activity_description,
            $required_attendance,
            $user_id,
            $activity_id
        ]);
        
        // ลบข้อมูลกลุ่มเป้าหมายเดิม
        $stmt = $conn->prepare("DELETE FROM activity_target_departments WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        $stmt = $conn->prepare("DELETE FROM activity_target_levels WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        // บันทึกกลุ่มเป้าหมาย (แผนกวิชา)
        if (!empty($target_departments)) {
            $placeholders = implode(',', array_fill(0, count($target_departments), '(?, ?)'));
            $values = [];
            
            foreach ($target_departments as $dept_id) {
                $values[] = $activity_id;
                $values[] = $dept_id;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO activity_target_departments (activity_id, department_id) 
                VALUES $placeholders
            ");
            $stmt->execute($values);
        }
        
        // บันทึกกลุ่มเป้าหมาย (ระดับชั้น)
        if (!empty($target_levels)) {
            $placeholders = implode(',', array_fill(0, count($target_levels), '(?, ?)'));
            $values = [];
            
            foreach ($target_levels as $level) {
                $values[] = $activity_id;
                $values[] = $level;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO activity_target_levels (activity_id, level) 
                VALUES $placeholders
            ");
            $stmt->execute($values);
        }
        
    /*     // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'edit_activity';
        $action_details = json_encode([
            'activity_id' => $activity_id,
            'activity_name' => $activity_name,
            'activity_date' => $activity_date
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $action_type, $action_details]); */
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "อัปเดตกิจกรรม '$activity_name' เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// จัดการการลบกิจกรรม
if (isset($_POST['delete_activity'])) {
    try {
        $activity_id = $_POST['activity_id'] ?? 0;
        
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($activity_id)) {
            throw new Exception("ไม่พบรหัสกิจกรรมที่ต้องการลบ");
        }
        
        // ดึงข้อมูลกิจกรรมก่อนลบ
        $stmt = $conn->prepare("SELECT activity_name FROM activities WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$activity) {
            throw new Exception("ไม่พบกิจกรรมที่ต้องการลบ");
        }
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ลบข้อมูลการเข้าร่วมกิจกรรม
        $stmt = $conn->prepare("DELETE FROM activity_attendance WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        // ลบข้อมูลกลุ่มเป้าหมาย
        $stmt = $conn->prepare("DELETE FROM activity_target_departments WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        $stmt = $conn->prepare("DELETE FROM activity_target_levels WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
        // ลบข้อมูลกิจกรรม
        $stmt = $conn->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        
 /*        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'delete_activity';
        // แก้ไขบรรทัดนี้ - เพิ่มการตรวจสอบว่า $activity เป็น array หรือไม่
        $activity_name = $activity && isset($activity['activity_name']) ? $activity['activity_name'] : '';
        $action_details = json_encode([
            'activity_id' => $activity_id,
            'activity_name' => $activity_name
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        "); 
        $stmt->execute([$user_id, $action_type, $action_details]);*/
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "ลบกิจกรรม เรียบร้อยแล้ว";
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $save_error = true;
        $error_message = $e->getMessage();
    } catch (Exception $e) {
        $save_error = true;
        $error_message = $e->getMessage();
    }
}

// ดึงรายชื่อแผนกวิชา และระดับชั้น สำหรับฟิลเตอร์
try {
    $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงระดับชั้นที่มีในระบบ
    $stmt = $conn->prepare("
        SELECT DISTINCT level 
        FROM classes 
        WHERE academic_year_id = ? 
        ORDER BY CASE 
            WHEN level = 'ปวช.1' THEN 1
            WHEN level = 'ปวช.2' THEN 2
            WHEN level = 'ปวช.3' THEN 3
            WHEN level = 'ปวส.1' THEN 4
            WHEN level = 'ปวส.2' THEN 5
            ELSE 6
        END
    ");
    $stmt->execute([$current_academic_year_id]);
    $levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $departments = [];
    $levels = [];
}

// ดึงรายการกิจกรรม
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT
            a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
            a.description, a.required_attendance, a.created_at,
            u.first_name, u.last_name,
            (SELECT COUNT(*) FROM activity_attendance aa WHERE aa.activity_id = a.activity_id) AS attendance_count
        FROM activities a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.academic_year_id = ?
        GROUP BY a.activity_id
        ORDER BY a.activity_date DESC
    ");
    $stmt->execute([$current_academic_year_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงกลุ่มเป้าหมายของแต่ละกิจกรรม
    foreach ($activities as &$activity) {
        // ดึงแผนกวิชาเป้าหมาย
        $stmt = $conn->prepare("
            SELECT d.department_name
            FROM activity_target_departments atd
            JOIN departments d ON atd.department_id = d.department_id
            WHERE atd.activity_id = ?
        ");
        $stmt->execute([$activity['activity_id']]);
        $target_depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $activity['target_departments'] = $target_depts;
        
        // ดึงระดับชั้นเป้าหมาย
        $stmt = $conn->prepare("
            SELECT level
            FROM activity_target_levels
            WHERE activity_id = ?
        ");
        $stmt->execute([$activity['activity_id']]);
        $target_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $activity['target_levels'] = $target_levels;
        
        // คำนวณเป้าหมายนักเรียนทั้งหมด
        $where_clauses = [];
        $params = [];
        
        if (!empty($target_depts)) {
            $dept_placeholders = implode(',', array_fill(0, count($target_depts), '?'));
            $where_clauses[] = "d.department_name IN ($dept_placeholders)";
            $params = array_merge($params, $target_depts);
        }
        
        if (!empty($target_levels)) {
            $level_placeholders = implode(',', array_fill(0, count($target_levels), '?'));
            $where_clauses[] = "c.level IN ($level_placeholders)";
            $params = array_merge($params, $target_levels);
        }
        
        if (!empty($where_clauses)) {
            $where_sql = implode(' AND ', $where_clauses);
            
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT s.student_id) as total_students
                FROM students s
                JOIN classes c ON s.current_class_id = c.class_id
                JOIN departments d ON c.department_id = d.department_id
                WHERE s.status = 'กำลังศึกษา' AND $where_sql
            ");
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $activity['target_students'] = $result['total_students'] ?? 0;
        } else {
            // ถ้าไม่มีเงื่อนไข นับนักเรียนทั้งหมด
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_students
                FROM students 
                WHERE status = 'กำลังศึกษา'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $activity['target_students'] = $result['total_students'] ?? 0;
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $activities = [];
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/activities.css'
];

$extra_js = [
    'assets/js/activities.js'
];

// สร้างข้อมูลสำหรับส่งไปยังหน้าแสดงผล
$data = [
    'departments' => $departments,
    'levels' => $levels,
    'academic_year_id' => $current_academic_year_id,
    'academic_year_display' => $academic_year_display,
    'activities' => $activities,
    'save_success' => $save_success,
    'save_error' => $save_error,
    'error_message' => $error_message,
    'response_message' => $response_message,
    'user_role' => $user_role
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/activities_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
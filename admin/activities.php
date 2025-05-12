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
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];
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
        
        // Commit transaction
        $conn->commit();
        
        $save_success = true;
        $response_message = "ลบกิจกรรม '{$activity['activity_name']}' เรียบร้อยแล้ว";
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
    a.academic_year_id, a.created_by,
    au.title, au.first_name, au.last_name,
    (SELECT COUNT(*) FROM activity_attendance aa WHERE aa.activity_id = a.activity_id) AS attendance_count
FROM activities a
LEFT JOIN admin_users au ON a.created_by = au.admin_id
WHERE a.academic_year_id = ?
GROUP BY a.activity_id
ORDER BY a.activity_date DESC
    ");
    $stmt->execute([$current_academic_year_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
   /*  // ดึงกลุ่มเป้าหมายของแต่ละกิจกรรม
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
    } */
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

// เริ่มแสดงเนื้อหาหลัก
?>
<div class="main-content">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="breadcrumb">
            <span class="material-icons">home</span>
            <a href="index.php">หน้าหลัก</a>
            <span class="separator">/</span>
            <span class="current"><?php echo $page_title; ?></span>
        </div>
    </div>

    <?php if ($save_success): ?>
    <div class="alert alert-success" id="success-alert">
        <span class="material-icons">check_circle</span>
        <div class="alert-message"><?php echo $response_message ?? 'บันทึกข้อมูลเรียบร้อยแล้ว'; ?></div>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">
            <span class="material-icons">close</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if ($save_error): ?>
    <div class="alert alert-error" id="error-alert">
        <span class="material-icons">error</span>
        <div class="alert-message">เกิดข้อผิดพลาด: <?php echo htmlspecialchars($error_message ?? 'ไม่สามารถบันทึกข้อมูลได้'); ?></div>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">
            <span class="material-icons">close</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- คำอธิบายหน้าเว็บ -->
    <div class="card">
        <div class="card-title">
            <span class="material-icons">info</span>
            เกี่ยวกับหน้านี้
        </div>
        <div class="card-body">
            <p>หน้านี้ใช้สำหรับจัดการกิจกรรมกลางของวิทยาลัย เช่น กิจกรรมหน้าเสาธง กิจกรรมวันสำคัญ หรือกิจกรรมพิเศษอื่นๆ</p>
            <p>คุณสามารถสร้าง แก้ไข หรือลบกิจกรรม รวมถึงเลือกกลุ่มเป้าหมายของกิจกรรมได้</p>
            <p>หลังจากสร้างกิจกรรมแล้ว คุณสามารถเช็คชื่อนักเรียนที่เข้าร่วมกิจกรรมได้ที่หน้า "บันทึกการเข้าร่วมกิจกรรม"</p>
        </div>
    </div>

    <!-- ปุ่มเพิ่มกิจกรรมใหม่ -->
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="openAddActivityModal()">
            <span class="material-icons">add</span>
            เพิ่มกิจกรรมใหม่
        </button>
    </div>

    <!-- รายการกิจกรรม -->
    <div class="card">
        <div class="card-title">
            <span class="material-icons">event</span>
            รายการกิจกรรมกลาง ปีการศึกษา <?php echo $academic_year_display; ?>
        </div>
        <div class="card-body">
            <div class="filter-container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterMonth" class="form-label">เดือน</label>
                            <select id="filterMonth" class="form-control" onchange="filterActivities()">
                                <option value="">-- ทุกเดือน --</option>
                                <option value="01">มกราคม</option>
                                <option value="02">กุมภาพันธ์</option>
                                <option value="03">มีนาคม</option>
                                <option value="04">เมษายน</option>
                                <option value="05">พฤษภาคม</option>
                                <option value="06">มิถุนายน</option>
                                <option value="07">กรกฎาคม</option>
                                <option value="08">สิงหาคม</option>
                                <option value="09">กันยายน</option>
                                <option value="10">ตุลาคม</option>
                                <option value="11">พฤศจิกายน</option>
                                <option value="12">ธันวาคม</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterStatus" class="form-label">สถานะ</label>
                            <select id="filterStatus" class="form-control" onchange="filterActivities()">
                                <option value="">-- ทุกสถานะ --</option>
                                <option value="upcoming">กิจกรรมที่ยังไม่จัด</option>
                                <option value="passed">กิจกรรมที่จัดไปแล้ว</option>
                                <option value="today">กิจกรรมวันนี้</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterSearch" class="form-label">ค้นหา</label>
                            <input type="text" id="filterSearch" class="form-control" placeholder="ค้นหาชื่อกิจกรรม" oninput="filterActivities()">
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($activities)): ?>
            <div class="empty-state">
                <span class="material-icons">event_busy</span>
                <p>ไม่พบข้อมูลกิจกรรม กรุณาเพิ่มกิจกรรมใหม่</p>
            </div>
            <?php else: ?>
            <div class="activity-list">
                <?php 
                // ตรวจสอบว่าไม่มีรายการกิจกรรมซ้ำกัน
                $displayed_activity_ids = []; 
                
                foreach ($activities as $activity): 
                    // ข้ามรายการที่ซ้ำกัน
                    if (in_array($activity['activity_id'], $displayed_activity_ids)) {
                        continue;
                    }
                    
                    // เพิ่ม ID ที่กำลังแสดงในรายการที่แสดงแล้ว
                    $displayed_activity_ids[] = $activity['activity_id'];
                    
                    $activity_date = new DateTime($activity['activity_date']);
                    $today = new DateTime(date('Y-m-d'));
                    $is_passed = $activity_date < $today;
                    $is_today = $activity_date->format('Y-m-d') === $today->format('Y-m-d');
                    
                    $attendance_percent = 0;
                   /*  if ($activity['target_students'] > 0) {
                        $attendance_percent = ($activity['attendance_count'] / $activity['target_students']) * 100;
                    } */
                ?>
                <div class="activity-item" 
                     data-month="<?php echo date('m', strtotime($activity['activity_date'])); ?>"
                     data-status="<?php echo $is_passed ? 'passed' : ($is_today ? 'today' : 'upcoming'); ?>"
                     data-name="<?php echo strtolower($activity['activity_name']); ?>"
                     data-id="<?php echo $activity['activity_id']; ?>">
                    <div class="activity-date">
                        <div class="date-day"><?php echo date('d', strtotime($activity['activity_date'])); ?></div>
                        <div class="date-month"><?php echo date('M', strtotime($activity['activity_date'])); ?></div>
                        <div class="date-year"><?php echo date('Y', strtotime($activity['activity_date'])); ?></div>
                        <?php if ($is_today): ?>
                        <div class="date-badge today">วันนี้</div>
                        <?php elseif ($is_passed): ?>
                        <div class="date-badge passed">ผ่านไปแล้ว</div>
                        <?php else: ?>
                        <div class="date-badge upcoming">กำลังจะมาถึง</div>
                        <?php endif; ?>
                    </div>
                    <div class="activity-details">
                        <h3 class="activity-name">
                            <?php echo htmlspecialchars($activity['activity_name']); ?>
                            <small class="activity-id">(รหัส: <?php echo $activity['activity_id']; ?>)</small>
                        </h3>
                        <div class="activity-info">
                            <div class="info-item">
                                <span class="material-icons">place</span>
                                <span><?php echo htmlspecialchars($activity['activity_location'] ?: 'ไม่ระบุสถานที่'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="material-icons">group</span>
                                <span>
                                    กลุ่มเป้าหมาย: 
                                    <?php 
                                    if (empty($activity['target_departments']) && empty($activity['target_levels'])) {
                                        echo 'ทุกแผนก/ทุกระดับชั้น';
                                    } else {
                                        $targets = [];
                                        if (!empty($activity['target_departments'])) {
                                            $targets[] = implode(', ', $activity['target_departments']);
                                        }
                                        if (!empty($activity['target_levels'])) {
                                            $targets[] = implode(', ', $activity['target_levels']);
                                        }
                                        echo implode(' / ', $targets);
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="material-icons">how_to_reg</span>
                                <span>
                                    เช็คชื่อแล้ว: <?php echo $activity['attendance_count']; ?> คน 
                                    (<?php echo number_format($attendance_percent, 1); ?>%)
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="material-icons">person</span>
                                <span>สร้างโดย: <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($activity['description'])): ?>
                        <div class="activity-description">
                            <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="activity-actions">
                        <a href="activity_attendance.php?id=<?php echo $activity['activity_id']; ?>" class="btn btn-primary" title="บันทึกการเข้าร่วม">
                            <span class="material-icons">how_to_reg</span>
                        </a>
                        <button class="btn btn-info" onclick="openEditActivityModal(<?php echo $activity['activity_id']; ?>)" title="แก้ไข">
                            <span class="material-icons">edit</span>
                        </button>
                        <button class="btn btn-danger" onclick="confirmDeleteActivity(<?php echo $activity['activity_id']; ?>, '<?php echo addslashes($activity['activity_name']); ?>')" title="ลบ">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div id="no-results-message" class="empty-state" style="display: none;">
                    <span class="material-icons">search_off</span>
                    <p>ไม่พบกิจกรรมที่ตรงกับเงื่อนไขการค้นหา</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- โมดัลเพิ่มกิจกรรม -->
    <div id="addActivityModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>เพิ่มกิจกรรมใหม่</h2>
                <span class="close" onclick="closeModal('addActivityModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addActivityForm" method="post">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                                <input type="text" id="activity_name" name="activity_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
                                <input type="date" id="activity_date" name="activity_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
                                <input type="text" id="activity_location" name="activity_location" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">บังคับเข้าร่วม</label>
                                <div class="form-check">
                                    <input type="checkbox" id="required_attendance" name="required_attendance" class="form-check-input">
                                    <label for="required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="target_departments" class="form-label">แผนกวิชาเป้าหมาย</label>
                                <div class="checkbox-container">
                                    <?php foreach ($departments as $department): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="dept_<?php echo $department['department_id']; ?>" name="target_departments[]" value="<?php echo $department['department_id']; ?>" class="form-check-input">
                                        <label for="dept_<?php echo $department['department_id']; ?>" class="form-check-label"><?php echo $department['department_name']; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="target_levels" class="form-label">ระดับชั้นเป้าหมาย</label>
                                <div class="checkbox-container">
                                    <?php foreach ($levels as $level): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="level_<?php echo str_replace('.', '_', $level); ?>" name="target_levels[]" value="<?php echo $level; ?>" class="form-check-input">
                                        <label for="level_<?php echo str_replace('.', '_', $level); ?>" class="form-check-label"><?php echo $level; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="activity_description" class="form-label">รายละเอียดกิจกรรม</label>
                        <textarea id="activity_description" name="activity_description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addActivityModal')">ยกเลิก</button>
                        <button type="submit" name="save_activity" class="btn btn-primary">
                            <span class="material-icons">save</span>
                            บันทึกกิจกรรม
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- โมดัลแก้ไขกิจกรรม -->
    <div id="editActivityModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2>แก้ไขกิจกรรม</h2>
                <span class="close" onclick="closeModal('editActivityModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editActivityForm" method="post">
                    <input type="hidden" id="edit_activity_id" name="activity_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="edit_activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                                <input type="text" id="edit_activity_name" name="activity_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
                                <input type="date" id="edit_activity_date" name="activity_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="edit_activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
                                <input type="text" id="edit_activity_location" name="activity_location" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">บังคับเข้าร่วม</label>
                                <div class="form-check">
                                    <input type="checkbox" id="edit_required_attendance" name="required_attendance" class="form-check-input">
                                    <label for="edit_required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">แผนกวิชาเป้าหมาย</label>
                                <div class="checkbox-container" id="edit_departments_container">
                                    <?php foreach ($departments as $department): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="edit_dept_<?php echo $department['department_id']; ?>" name="target_departments[]" value="<?php echo $department['department_id']; ?>" class="form-check-input">
                                        <label for="edit_dept_<?php echo $department['department_id']; ?>" class="form-check-label"><?php echo $department['department_name']; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">ระดับชั้นเป้าหมาย</label>
                                <div class="checkbox-container" id="edit_levels_container">
                                    <?php foreach ($levels as $level): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="edit_level_<?php echo str_replace('.', '_', $level); ?>" name="target_levels[]" value="<?php echo $level; ?>" class="form-check-input">
                                        <label for="edit_level_<?php echo str_replace('.', '_', $level); ?>" class="form-check-label"><?php echo $level; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_activity_description" class="form-label">รายละเอียดกิจกรรม</label>
                        <textarea id="edit_activity_description" name="activity_description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ยกเลิก</button>
                        <button type="submit" name="edit_activity" class="btn btn-primary">
                            <span class="material-icons">save</span>
                            บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- โมดัลลบกิจกรรม -->
    <div id="deleteActivityModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>ยืนยันการลบกิจกรรม</h2>
                <span class="close" onclick="closeModal('deleteActivityModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="deleteActivityForm" method="post">
                    <input type="hidden" id="delete_activity_id" name="activity_id">
                    
                    <div class="alert alert-warning">
                        <span class="material-icons">warning</span>
                        <span>คุณกำลังจะลบกิจกรรม: <strong id="delete_activity_name"></strong></span>
                    </div>
                    
                    <p>การลบกิจกรรมจะลบข้อมูลการเข้าร่วมกิจกรรมของนักเรียนทั้งหมดด้วย และไม่สามารถเรียกคืนได้</p>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteActivityModal')">ยกเลิก</button>
                        <button type="submit" name="delete_activity" class="btn btn-danger">
                            <span class="material-icons">delete</span>
                            ยืนยันการลบ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- สรุปภาพรวมกิจกรรม -->
    <div class="card">
        <div class="card-title">
            <span class="material-icons">assessment</span>
            สรุปภาพรวมกิจกรรม ปีการศึกษา <?php echo $academic_year_display; ?>
        </div>
        <div class="card-body">
            <!-- สรุปจำนวนกิจกรรม -->
            <div class="activity-summary">
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="summary-icon">
                                <span class="material-icons">event</span>
                            </div>
                            <div class="summary-content">
                                <div class="summary-value"><?php echo count($activities); ?></div>
                                <div class="summary-label">กิจกรรมทั้งหมด</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="summary-icon upcoming">
                                <span class="material-icons">event_upcoming</span>
                            </div>
                            <div class="summary-content">
                                <?php
                                $today = date('Y-m-d');
                                $upcoming_count = 0;
                                foreach ($activities as $activity) {
                                    if ($activity['activity_date'] > $today) {
                                        $upcoming_count++;
                                    }
                                }
                                ?>
                                <div class="summary-value"><?php echo $upcoming_count; ?></div>
                                <div class="summary-label">กิจกรรมที่กำลังจะมาถึง</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="summary-icon today">
                                <span class="material-icons">today</span>
                            </div>
                            <div class="summary-content">
                                <?php
                                $today_count = 0;
                                foreach ($activities as $activity) {
                                    if ($activity['activity_date'] === $today) {
                                        $today_count++;
                                    }
                                }
                                ?>
                                <div class="summary-value"><?php echo $today_count; ?></div>
                                <div class="summary-label">กิจกรรมวันนี้</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="summary-icon passed">
                                <span class="material-icons">event_available</span>
                            </div>
                            <div class="summary-content">
                                <?php
                                $passed_count = 0;
                                foreach ($activities as $activity) {
                                    if ($activity['activity_date'] < $today) {
                                        $passed_count++;
                                    }
                                }
                                ?>
                                <div class="summary-value"><?php echo $passed_count; ?></div>
                                <div class="summary-label">กิจกรรมที่ผ่านไปแล้ว</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- กราฟแสดงการมีส่วนร่วมกิจกรรม -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h3 class="chart-title">การเข้าร่วมกิจกรรมตามแผนกวิชา</h3>
                        <canvas id="departmentChart" height="250"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h3 class="chart-title">การเข้าร่วมกิจกรรมตามระดับชั้น</h3>
                        <canvas id="levelChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่ม CSS เฉพาะสำหรับเพิ่มโครงสร้างใหม่ -->
<style>
/* กล่องตัวเลือกแบบ checkbox */
.checkbox-container {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
}

.checkbox-container .form-check {
    margin-bottom: 8px;
}

.checkbox-container .form-check:last-child {
    margin-bottom: 0;
}

/* ปรับปรุงรูปแบบโมดัล */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.modal-content {
    position: relative;
    background-color: #fff;
    margin: auto;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

/* ป้องกันการปิดโมดัลเมื่อคลิกภายนอก */
.modal.prevent-close {
    pointer-events: none;
}

.modal.prevent-close .modal-content {
    pointer-events: auto;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .checkbox-container {
        max-height: 150px;
    }
}
</style>

<!-- อัปเดตไฟล์ JavaScript สำหรับการเปิดปิดโมดัลและจัดการกิจกรรม -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ซ่อนแจ้งเตือนหลังจาก 3 วินาที
    const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
    alerts.forEach(alert => {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    });
    
    // ตั้งค่าวันที่เริ่มต้นเป็นวันนี้สำหรับฟอร์มเพิ่มกิจกรรม
    const addDateInput = document.getElementById('activity_date');
    if (addDateInput) {
        addDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // ตรวจสอบการแสดงข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาด
    setTimeout(function() {
        const successAlert = document.getElementById('success-alert');
        const errorAlert = document.getElementById('error-alert');
        
        if (successAlert) {
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.style.display = 'none', 500);
        }
        
        if (errorAlert) {
            errorAlert.style.opacity = '0';
            setTimeout(() => errorAlert.style.display = 'none', 500);
        }
    }, 3000);
    
    // ตัวกรองกิจกรรมเริ่มต้น
    initializeFilters();
    
    // ตั้งค่าโมดัลแบบป้องกันการปิดเมื่อคลิกภายนอก
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                // ป้องกันการปิดเมื่อคลิกภายนอก
                event.stopPropagation();
            }
        });
    });
    
    // ป้องกันการกดปุ่ม ESC เพื่อปิดโมดัล
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            return false;
        }
    });
    
    // สร้างกราฟถ้ามีไลบรารี Chart.js
    if (typeof Chart !== 'undefined') {
        createDepartmentChart();
        createLevelChart();
    }
});

/**
 * เปิดโมดัลเพิ่มกิจกรรม
 */
function openAddActivityModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addActivityForm').reset();
    
    // กำหนดวันที่เป็นวันนี้
    document.getElementById('activity_date').value = new Date().toISOString().split('T')[0];
    
    // ล้างการติกเลือกในช่อง checkbox
    const checkboxes = document.querySelectorAll('#addActivityForm input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // เปิดโมดัล
    const modal = document.getElementById('addActivityModal');
    modal.style.display = 'flex';
    modal.classList.add('prevent-close');
}

/**
 * เปิดโมดัลแก้ไขกิจกรรม
 */
function openEditActivityModal(activityId) {
    // แสดงการโหลด
    const modal = document.getElementById('editActivityModal');
    const form = document.getElementById('editActivityForm');
    
    // แสดงโมดัล
    modal.style.display = 'flex';
    modal.classList.add('prevent-close');
    
    // รีเซ็ตฟอร์ม
    form.reset();
    
    // ล้างการติกเลือกในช่อง checkbox
    const checkboxes = document.querySelectorAll('#editActivityForm input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // แสดงการโหลด
    form.innerHTML = '<div class="text-center"><div class="spinner"></div><p>กำลังโหลดข้อมูล...</p></div>';
    
    // ดึงข้อมูลกิจกรรม
    fetch(`ajax/get_activity.php?activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // คืนค่าฟอร์มเดิม
                restoreEditForm();
                
                const activity = data.activity;
                
                // กำหนดค่าให้ฟอร์ม
                document.getElementById('edit_activity_id').value = activity.activity_id;
                document.getElementById('edit_activity_name').value = activity.activity_name;
                document.getElementById('edit_activity_date').value = activity.activity_date;
                document.getElementById('edit_activity_location').value = activity.activity_location || '';
                document.getElementById('edit_required_attendance').checked = (activity.required_attendance == 1);
                document.getElementById('edit_activity_description').value = activity.description || '';
                
                // กำหนดแผนกวิชาและระดับชั้นเป้าหมาย
                if (activity.target_departments && activity.target_departments.length > 0) {
                    activity.target_departments.forEach(deptId => {
                        const checkbox = document.getElementById(`edit_dept_${deptId}`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                
                if (activity.target_levels && activity.target_levels.length > 0) {
                    activity.target_levels.forEach(level => {
                        const checkbox = document.getElementById(`edit_level_${level.replace('.', '_')}`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
            } else {
                // แสดงข้อความผิดพลาด
                form.innerHTML = `
                    <div class="alert alert-error">
                        <span class="material-icons">error</span>
                        <div class="alert-message">${data.error || 'ไม่สามารถดึงข้อมูลกิจกรรมได้'}</div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ปิด</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            form.innerHTML = `
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <div class="alert-message">เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรม</div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ปิด</button>
                </div>
            `;
        });
}

/**
 * คืนค่าฟอร์มแก้ไขกิจกรรม
 */
function restoreEditForm() {
    const form = document.getElementById('editActivityForm');
    if (!form) return;
    
    // ตรวจสอบว่ามีรายการเดิมหรือไม่
    const existingInput = form.querySelector('#edit_activity_id');
    if (existingInput) return; // มีฟอร์มอยู่แล้ว ไม่ต้องสร้างใหม่
    
    // สร้างฟอร์มใหม่
    form.innerHTML = `
        <input type="hidden" id="edit_activity_id" name="activity_id">
        
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="edit_activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                    <input type="text" id="edit_activity_name" name="activity_name" class="form-control" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="edit_activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
                    <input type="date" id="edit_activity_date" name="activity_date" class="form-control" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="edit_activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
                    <input type="text" id="edit_activity_location" name="activity_location" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">บังคับเข้าร่วม</label>
                    <div class="form-check">
                        <input type="checkbox" id="edit_required_attendance" name="required_attendance" class="form-check-input">
                        <label for="edit_required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">แผนกวิชาเป้าหมาย</label>
                    <div class="checkbox-container" id="edit_departments_container">
                        ${generateDepartmentsCheckboxes()}
                    </div>
                    <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">ระดับชั้นเป้าหมาย</label>
                    <div class="checkbox-container" id="edit_levels_container">
                        ${generateLevelsCheckboxes()}
                    </div>
                    <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="edit_activity_description" class="form-label">รายละเอียดกิจกรรม</label>
            <textarea id="edit_activity_description" name="activity_description" class="form-control" rows="4"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ยกเลิก</button>
            <button type="submit" name="edit_activity" class="btn btn-primary">
                <span class="material-icons">save</span>
                บันทึกการแก้ไข
            </button>
        </div>
    `;
}

/**
 * สร้าง HTML สำหรับ checkboxes แผนกวิชา
 */
function generateDepartmentsCheckboxes() {
    const departments = <?php echo json_encode($departments); ?>;
    let html = '';
    
    departments.forEach(department => {
        html += `
            <div class="form-check">
                <input type="checkbox" id="edit_dept_${department.department_id}" name="target_departments[]" value="${department.department_id}" class="form-check-input">
                <label for="edit_dept_${department.department_id}" class="form-check-label">${department.department_name}</label>
            </div>
        `;
    });
    
    return html;
}

/**
 * สร้าง HTML สำหรับ checkboxes ระดับชั้น
 */
function generateLevelsCheckboxes() {
    const levels = <?php echo json_encode($levels); ?>;
    let html = '';
    
    levels.forEach(level => {
        const levelId = level.replace('.', '_');
        html += `
            <div class="form-check">
                <input type="checkbox" id="edit_level_${levelId}" name="target_levels[]" value="${level}" class="form-check-input">
                <label for="edit_level_${levelId}" class="form-check-label">${level}</label>
            </div>
        `;
    });
    
    return html;
}

/**
 * ยืนยันการลบกิจกรรม
 */
function confirmDeleteActivity(activityId, activityName) {
    document.getElementById('delete_activity_id').value = activityId;
    document.getElementById('delete_activity_name').textContent = activityName;
    document.getElementById('deleteActivityModal').style.display = 'flex';
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('prevent-close');
}

/**
 * กรองรายการกิจกรรม
 */
function filterActivities() {
    const month = document.getElementById('filterMonth').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();
    
    // บันทึกค่าลงใน URL เพื่อให้สามารถคงค่าการกรองได้เมื่อโหลดหน้าใหม่
    const url = new URL(window.location);
    if (month) url.searchParams.set('month', month);
    else url.searchParams.delete('month');
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    window.history.replaceState({}, '', url);
    
    // กรองรายการกิจกรรม
    const activities = document.querySelectorAll('.activity-item');
    let visibleCount = 0;
    
    activities.forEach(activity => {
        const activityMonth = activity.dataset.month;
        const activityStatus = activity.dataset.status;
        const activityName = activity.dataset.name;
        const activityId = activity.dataset.id || '';
        
        let isVisible = true;
        
        if (month && activityMonth !== month) {
            isVisible = false;
        }
        
        if (status && activityStatus !== status) {
            isVisible = false;
        }
        
        // ค้นหาทั้งในชื่อและรหัสกิจกรรม
        if (search && !activityName.includes(search) && !activityId.includes(search)) {
            isVisible = false;
        }
        
        activity.style.display = isVisible ? 'flex' : 'none';
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // แสดงข้อความเมื่อไม่พบกิจกรรม
    const noResultsMessage = document.getElementById('no-results-message');
    if (noResultsMessage) {
        noResultsMessage.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

/**
 * เริ่มต้นตัวกรองจาก URL
 */
function initializeFilters() {
    const url = new URL(window.location);
    
    // ตั้งค่าตัวกรองตาม URL
    if (url.searchParams.has('month')) {
        document.getElementById('filterMonth').value = url.searchParams.get('month');
    }
    
    if (url.searchParams.has('status')) {
        document.getElementById('filterStatus').value = url.searchParams.get('status');
    }
    
    if (url.searchParams.has('search')) {
        document.getElementById('filterSearch').value = url.searchParams.get('search');
    }
    
    // ใช้ตัวกรองทันที
    if (url.searchParams.has('month') || url.searchParams.has('status') || url.searchParams.has('search')) {
        filterActivities();
    }
}

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามแผนกวิชา (สำหรับตัวอย่าง)
 */
function createDepartmentChart() {
    if (typeof Chart === 'undefined') return;
    
    const ctx = document.getElementById('departmentChart');
    if (!ctx) return;
    
    // สร้างข้อมูลตัวอย่าง
    const data = {
        labels: ['ช่างยนต์', 'ช่างไฟฟ้า', 'อิเล็กทรอนิกส์', 'เทคโนโลยีสารสนเทศ', 'ช่างเชื่อมโลหะ'],
        datasets: [
            {
                label: 'นักเรียนทั้งหมด',
                data: [50, 45, 30, 40, 25],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'เข้าร่วมกิจกรรม',
                data: [40, 35, 25, 30, 20],
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }
        ]
    };
    
    // สร้างกราฟ
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามระดับชั้น (สำหรับตัวอย่าง)
 */
function createLevelChart() {
    if (typeof Chart === 'undefined') return;
    
    const ctx = document.getElementById('levelChart');
    if (!ctx) return;
    
    // สร้างข้อมูลตัวอย่าง
    const data = {
        labels: ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'],
        datasets: [
            {
                label: 'นักเรียนทั้งหมด',
                data: [60, 55, 50, 40, 35],
                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            },
            {
                label: 'เข้าร่วมกิจกรรม',
                data: [50, 45, 40, 30, 25],
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }
        ]
    };
    
    // สร้างกราฟ
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php
// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
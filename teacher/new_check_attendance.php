<?php
/**
 * new_check_attendance.php - หน้าเช็คชื่อนักเรียนรูปแบบใหม่สำหรับครูที่ปรึกษา
 * 
 * คุณสมบัติ:
 * - UI แบบใหม่ที่ใช้งานง่าย ไม่ซับซ้อน
 * - เช็คชื่อได้ 4 สถานะ: มา, ขาด, สาย, ลา
 * - เช็คชื่อผ่าน PIN, QR Code, และกรอกโดยครู
 * - สามารถเช็คชื่อย้อนหลังได้ (เก็บประวัติการเช็คย้อนหลัง)
 * - แก้ไขการเช็คชื่อที่บันทึกแล้วได้
 * - ดูข้อมูลสรุปการเช็คชื่อ
 * - แสดงปีเป็น พ.ศ.
 */

// เริ่มต้น session และตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'check_attendance';
$page_title = 'ระบบเช็คชื่อ - เช็คชื่อนักเรียน';
$page_header = 'เช็คชื่อนักเรียน';

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';
require_once '../lib/functions.php';

// เชื่อมต่อฐานข้อมูล
try {
    $db = getDB();
} catch (Exception $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ดึงข้อมูลครูที่ปรึกษาจากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$teacher_query = "SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, d.department_name 
                 FROM teachers t 
                 JOIN users u ON t.user_id = u.user_id 
                 LEFT JOIN departments d ON t.department_id = d.department_id 
                 WHERE t.user_id = :user_id";

try {
    $stmt = $db->prepare($teacher_query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $teacher_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacher_data) {
        throw new Exception("ไม่พบข้อมูลครู");
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลครู: " . $e->getMessage());
}

// สร้างข้อมูลครูที่ปรึกษา
$teacher_id = $teacher_data['teacher_id'];
$teacher_name = $teacher_data['title'] . ' ' . $teacher_data['first_name'] . ' ' . $teacher_data['last_name'];
$teacher_info = [
    'name' => $teacher_name,
    'avatar' => mb_substr($teacher_data['first_name'], 0, 1, 'UTF-8'),
    'role' => 'ครูที่ปรึกษา' . ($teacher_data['department_name'] ? ' ' . $teacher_data['department_name'] : ''),
    'profile_picture' => $teacher_data['profile_picture']
];

// ดึงห้องเรียนที่ครูเป็นที่ปรึกษา
$classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                 c.level, d.department_name, c.group_number, ay.year, ay.semester, ca.is_primary,
                 (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                 FROM class_advisors ca 
                 JOIN classes c ON ca.class_id = c.class_id 
                 JOIN departments d ON c.department_id = d.department_id 
                 JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                 WHERE ca.teacher_id = :teacher_id AND c.is_active = 1 AND ay.is_active = 1
                 ORDER BY ca.is_primary DESC, c.level, c.group_number";

try {
    $stmt = $db->prepare($classes_query);
    $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
    $stmt->execute();
    $classes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
}

// อ่านข้อมูลห้องเรียนและเตรียมข้อมูลสำหรับแสดงผล
$teacher_classes = [];
foreach ($classes_result as $class) {
    $teacher_classes[] = [
        'id' => $class['class_id'],
        'name' => $class['class_name'],
        'level' => $class['level'],
        'department' => $class['department_name'],
        'group' => $class['group_number'],
        'year' => $class['year'],
        'semester' => $class['semester'],
        'is_primary' => $class['is_primary'],
        'total_students' => $class['total_students']
    ];
}

// ดึงห้องเรียนที่กำลังดูข้อมูล (จาก URL หรือค่าเริ่มต้น)
$current_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// ถ้าไม่มีห้องเรียนที่กำลังดู ให้ใช้ห้องแรกในรายการ
if ($current_class_id == 0 && !empty($teacher_classes)) {
    $current_class_id = $teacher_classes[0]['id'];
}

// ตรวจสอบสิทธิ์ในการเข้าถึงห้องเรียนนี้
if ($_SESSION['role'] === 'teacher') {
    $has_permission = false;
    foreach ($teacher_classes as $class) {
        if ($class['id'] == $current_class_id) {
            $has_permission = true;
            $current_class = $class;
            break;
        }
    }
    
    if (!$has_permission) {
        // ถ้าไม่มีสิทธิ์ ให้เปลี่ยนไปใช้ห้องแรกที่มีสิทธิ์
        if (!empty($teacher_classes)) {
            $current_class_id = $teacher_classes[0]['id'];
            $current_class = $teacher_classes[0];
        } else {
            // ถ้าไม่มีห้องที่รับผิดชอบเลย ให้แสดงข้อความแจ้งเตือน
            echo "<script>alert('คุณไม่มีสิทธิ์ในการเข้าถึงข้อมูลห้องเรียนนี้');</script>";
            echo "<script>window.location.href = 'home.php';</script>";
            exit;
        }
    }
} else {
    // กรณีเป็น admin สามารถเข้าถึงได้ทุกห้อง
    try {
        // ดึงข้อมูลห้องเรียนปัจจุบัน
        $class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                       c.level, d.department_name, c.group_number, ay.year, ay.semester,
                       (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                       FROM classes c 
                       JOIN departments d ON c.department_id = d.department_id 
                       JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                       WHERE c.class_id = :class_id AND c.is_active = 1 AND ay.is_active = 1";
        
        $stmt = $db->prepare($class_query);
        $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
        $stmt->execute();
        $class_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class_result) {
            $current_class = [
                'id' => $class_result['class_id'],
                'name' => $class_result['class_name'],
                'level' => $class_result['level'],
                'department' => $class_result['department_name'],
                'group' => $class_result['group_number'],
                'year' => $class_result['year'],
                'semester' => $class_result['semester'],
                'total_students' => $class_result['total_students']
            ];
        } else {
            // ถ้าไม่พบห้องเรียน ให้ใช้ห้องแรกที่มีในระบบ
            $all_class_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                              c.level, d.department_name, c.group_number, ay.year, ay.semester,
                              (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                              FROM classes c 
                              JOIN departments d ON c.department_id = d.department_id 
                              JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                              WHERE c.is_active = 1 AND ay.is_active = 1
                              LIMIT 1";
            
            $stmt = $db->query($all_class_query);
            $class_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($class_result) {
                $current_class_id = $class_result['class_id'];
                $current_class = [
                    'id' => $class_result['class_id'],
                    'name' => $class_result['class_name'],
                    'level' => $class_result['level'],
                    'department' => $class_result['department_name'],
                    'group' => $class_result['group_number'],
                    'year' => $class_result['year'],
                    'semester' => $class_result['semester'],
                    'total_students' => $class_result['total_students']
                ];
            } else {
                echo "<script>alert('ไม่พบข้อมูลห้องเรียนในระบบ');</script>";
                echo "<script>window.location.href = 'home.php';</script>";
                exit;
            }
        }
        
        // กรณีเป็น admin ให้ดึงทุกห้องเรียนมาแสดง
        $all_classes_query = "SELECT c.class_id, CONCAT(c.level, '/', d.department_code, '/', c.group_number) AS class_name, 
                            c.level, d.department_name, c.group_number, ay.year, ay.semester,
                            (SELECT COUNT(*) FROM students WHERE current_class_id = c.class_id AND status = 'กำลังศึกษา') as total_students
                            FROM classes c 
                            JOIN departments d ON c.department_id = d.department_id 
                            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                            WHERE c.is_active = 1 AND ay.is_active = 1
                            ORDER BY c.level, d.department_name, c.group_number";
        
        $stmt = $db->query($all_classes_query);
        $all_classes_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($teacher_classes)) {
            // เคลียร์ข้อมูลเดิม และเพิ่มข้อมูลใหม่
            $teacher_classes = [];
        }
        
        foreach ($all_classes_result as $class) {
            $teacher_classes[] = [
                'id' => $class['class_id'],
                'name' => $class['class_name'],
                'level' => $class['level'],
                'department' => $class['department_name'],
                'group' => $class['group_number'],
                'year' => $class['year'],
                'semester' => $class['semester'],
                'total_students' => $class['total_students']
            ];
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน: " . $e->getMessage());
    }
}

// ดึงวันที่ที่ต้องการเช็คชื่อ (จาก URL หรือวันปัจจุบัน)
$check_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// ตรวจสอบความถูกต้องของรูปแบบวันที่
$date_pattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($date_pattern, $check_date)) {
    $check_date = date('Y-m-d');
}

// ถ้าเป็นวันในอนาคต ให้เปลี่ยนเป็นวันปัจจุบัน (ยกเว้นกรณีเป็น admin)
if ($_SESSION['role'] !== 'admin' && $check_date > date('Y-m-d')) {
    $check_date = date('Y-m-d');
}

// ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
$is_retroactive = ($check_date < date('Y-m-d'));

// เพิ่มฟังก์ชันแปลงวันที่เป็นรูปแบบไทย
function formatThaiDate($date) {
    $thai_months = array(
        1 => 'มกราคม', 
        2 => 'กุมภาพันธ์', 
        3 => 'มีนาคม', 
        4 => 'เมษายน', 
        5 => 'พฤษภาคม', 
        6 => 'มิถุนายน', 
        7 => 'กรกฎาคม', 
        8 => 'สิงหาคม', 
        9 => 'กันยายน', 
        10 => 'ตุลาคม', 
        11 => 'พฤศจิกายน', 
        12 => 'ธันวาคม'
    );
    
    $date_array = explode('-', $date);
    $day = intval($date_array[2]);
    $month = intval($date_array[1]);
    $year = intval($date_array[0]) + 543; // แปลงเป็น พ.ศ.
    
    return "$day {$thai_months[$month]} $year";
}

// แปลงวันที่เป็นรูปแบบไทยสำหรับแสดงผลในหน้าเว็บ
$thai_date = formatThaiDate($check_date);

// หาสถิติการเข้าแถววันนี้
$attendance_stats_query = "SELECT 
                          COUNT(DISTINCT s.student_id) as total_students,
                          SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.attendance_status = 'late' THEN 1 ELSE 0 END) as late_count,
                          SUM(CASE WHEN a.attendance_status = 'leave' THEN 1 ELSE 0 END) as leave_count,
                          SUM(CASE WHEN a.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          COUNT(a.attendance_id) as checked_count
                          FROM students s
                          LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :check_date
                          WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'";

try {
    $stmt = $db->prepare($attendance_stats_query);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_students = $stats_data['total_students'];
    $present_count = $stats_data['present_count'] ?: 0;
    $late_count = $stats_data['late_count'] ?: 0;
    $leave_count = $stats_data['leave_count'] ?: 0;
    $absent_count = $stats_data['absent_count'] ?: 0;
    $checked_count = $stats_data['checked_count'] ?: 0;
    $not_checked = $total_students - $checked_count;
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลสถิติการเช็คชื่อ: " . $e->getMessage());
}

// ปรับปรุงข้อมูลของ $current_class เพื่อแสดงผล
$current_class['present_count'] = $present_count;
$current_class['late_count'] = $late_count;
$current_class['leave_count'] = $leave_count;
$current_class['absent_count'] = $absent_count;
$current_class['not_checked'] = $not_checked;

// ดึงรายชื่อนักเรียนทั้งหมดพร้อมสถานะการเช็คชื่อในวันที่เลือก
$students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                  a.attendance_id, a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time, a.check_method, a.remarks
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :check_date
                 WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                 ORDER BY s.student_code";

// ดึงประวัติการเช็คชื่อย้อนหลังถ้ามีการเช็คย้อนหลัง
$retroactive_history_query = "";
if ($is_retroactive) {
    // ตรวจสอบว่ามีตารางประวัติการเช็คย้อนหลังหรือไม่
    $table_exists_query = "SHOW TABLES LIKE 'attendance_retroactive_history'";
    $stmt = $db->query($table_exists_query);
    if ($stmt->rowCount() > 0) {
        $retroactive_history_query = "SELECT arh.*, 
                                     u.first_name, u.last_name, u.role,
                                     DATE_FORMAT(arh.created_at, '%d/%m/%Y %H:%i') as formatted_date
                                     FROM attendance_retroactive_history arh
                                     JOIN users u ON arh.created_by = u.user_id
                                     WHERE arh.retroactive_date = :check_date
                                     AND arh.student_id IN (
                                        SELECT student_id FROM students WHERE current_class_id = :class_id
                                     )
                                     ORDER BY arh.created_at DESC";
    }
}

try {
    $stmt = $db->prepare($students_query);
    $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
    $stmt->execute();
    $students_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แยกนักเรียนตามสถานะการเช็คชื่อ
    $unchecked_students = [];
    $checked_students = [];
    
    foreach ($students_result as $student) {
        // สร้างข้อมูลนักเรียน
        $student_data = [
            'id' => $student['student_id'],
            'number' => $student['number'],
            'code' => $student['student_code'],
            'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
            'profile_picture' => $student['profile_picture'],
            'status' => $student['attendance_status'] ?? 'not_checked',
            'time_checked' => $student['check_time'] ?? '',
            'check_method' => $student['check_method'] ?? '',
            'remarks' => $student['remarks'] ?? '',
            'attendance_id' => $student['attendance_id'] ?? null
        ];
        
        // แยกตามสถานะ
        if ($student_data['status'] === 'not_checked') {
            $unchecked_students[] = $student_data;
        } else {
            $checked_students[] = $student_data;
        }
    }
    
    // ดึงประวัติการเช็คชื่อย้อนหลัง
    $retroactive_history = [];
    if ($is_retroactive && !empty($retroactive_history_query)) {
        $stmt = $db->prepare($retroactive_history_query);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->bindParam(':class_id', $current_class_id, PDO::PARAM_INT);
        $stmt->execute();
        $retroactive_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: " . $e->getMessage());
}

$extra_css = [
    'assets/css/new_check_attendance.css',
    'assets/css/modal.css',
    'assets/css/retroactive-check.css',
    'https://fonts.googleapis.com/icon?family=Material+Icons',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];

$extra_js = [
    'assets/js/check_ajax.js',
    'assets/js/enhance-search.js',
    'assets/js/attendance-fix.js',
    'assets/js/retroactive-check.js'
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/new_check_attendance_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once $content_path;
require_once 'templates/footer.php';
?>

<!-- เพิ่มส่วนแสดงประวัติการเช็คชื่อย้อนหลัง -->
<?php if ($is_retroactive && !empty($retroactive_history)): ?>
<div class="modal" id="retroactiveHistoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">ประวัติการเช็คชื่อย้อนหลัง - <?php echo $thai_date; ?></h3>
                <button type="button" class="close-btn" onclick="closeModal('retroactiveHistoryModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="retroactive-history-list">
                    <?php foreach ($retroactive_history as $history): ?>
                    <div class="history-item">
                        <div class="history-header">
                            <div class="history-user">
                                <i class="fas fa-user"></i> <?php echo $history['first_name'] . ' ' . $history['last_name']; ?>
                            </div>
                            <div class="history-time"><?php echo $history['formatted_date']; ?></div>
                        </div>
                        <div class="history-content">
                            <div class="history-status">
                                <?php
                                $status_class = '';
                                $status_icon = '';
                                $status_text = '';
                                
                                switch ($history['retroactive_status']) {
                                    case 'present':
                                        $status_class = 'present';
                                        $status_icon = 'fa-check-circle';
                                        $status_text = 'มาเรียน';
                                        break;
                                    case 'late':
                                        $status_class = 'late';
                                        $status_icon = 'fa-clock';
                                        $status_text = 'มาสาย';
                                        break;
                                    case 'leave':
                                        $status_class = 'leave';
                                        $status_icon = 'fa-clipboard';
                                        $status_text = 'ลา';
                                        break;
                                    case 'absent':
                                        $status_class = 'absent';
                                        $status_icon = 'fa-times-circle';
                                        $status_text = 'ขาดเรียน';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                </span>
                            </div>
                            <div class="history-reason">
                                <strong>เหตุผล:</strong> <?php echo htmlspecialchars($history['retroactive_reason']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn primary" onclick="closeModal('retroactiveHistoryModal')">ปิด</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- เพิ่มสคริปต์สำหรับการเริ่มต้นระบบ -->
<script>
// เตรียมข้อมูลสำหรับใช้ใน JavaScript
if (typeof currentClassId === 'undefined') {
    window.currentClassId = <?php echo $current_class_id; ?>;
}
const checkDate = '<?php echo $check_date; ?>';
const isRetroactive = <?php echo $is_retroactive ? 'true' : 'false'; ?>;
const teacherId = <?php echo $teacher_id; ?>;
const totalStudents = <?php echo $total_students; ?>;
const notCheckedCount = <?php echo $not_checked; ?>;
const thaiDate = '<?php echo $thai_date; ?>';

document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีการโหลดไฟล์แก้ไขหรือไม่
    if (typeof updateStudentCounts === 'function') {
        console.log('โหลดไฟล์แก้ไขสำเร็จ');
        
        // จัดการระบบแท็บ
        setupTabSystem();
        
        // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
        setupRemarkField();
        
        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
        updateStudentCounts();
        
        // ตรวจสอบการเช็คชื่อย้อนหลัง
        if (isRetroactive) {
            setupRetroactiveMode();
        }
    } else {
        console.error('ไม่สามารถโหลดไฟล์แก้ไขได้');
    }
    
    // แสดงข้อความแจ้งเตือนเมื่อเป็นการเช็คชื่อย้อนหลัง
    if (isRetroactive) {
        showRetroactiveNotification();
    }
    
    <?php if ($is_retroactive && !empty($retroactive_history)): ?>
    // เพิ่มปุ่มดูประวัติการเช็คชื่อย้อนหลัง
    addRetroactiveHistoryButton();
    <?php endif; ?>
});

// นิยามฟังก์ชัน showModal และ closeModal ให้พร้อมใช้งานทันที
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนพื้นหลัง
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // คืนค่าการเลื่อน
    }
}


/**
 * เปลี่ยนวันที่เช็คชื่อ
 * @param {string} date - วันที่ต้องการเช็คชื่อ
 */
function changeDate(date) {
    if (hasChanges) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${date}`;
        }
    } else {
        window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${date}`;
    }
}

function setupRetroactiveMode() {
    // เพิ่มแบนเนอร์แจ้งเตือนการเช็คชื่อย้อนหลัง
    const container = document.querySelector('.attendance-container');
    if (container) {
        const warningBox = document.createElement('div');
        warningBox.className = 'retroactive-warning-box';
        warningBox.innerHTML = `
            <div class="icon"><i class="fas fa-history"></i></div>
            <div class="content">
                <strong>คุณกำลังเช็คชื่อย้อนหลัง</strong>
                <p>การเช็คชื่อย้อนหลังจะถูกบันทึกประวัติไว้ในระบบ กรุณาระบุเหตุผลการเช็คย้อนหลังทุกครั้ง</p>
            </div>
        `;
        
        // เพิ่มแบนเนอร์ถ้ายังไม่มี
        if (!container.querySelector('.retroactive-warning-box')) {
            container.insertBefore(warningBox, container.firstChild);
        }
    }
    
    // ตั้งค่าตัวแปรสำหรับโหมดเช็คชื่อย้อนหลัง
    window.isRetroactiveMode = true;
}

// ฟังก์ชันเพิ่มปุ่มดูประวัติการเช็คชื่อย้อนหลัง
function addRetroactiveHistoryButton() {
    const actionButtons = document.querySelector('.action-buttons');
    if (actionButtons) {
        const historyButton = document.createElement('button');
        historyButton.type = 'button';
        historyButton.className = 'btn secondary';
        historyButton.innerHTML = '<i class="fas fa-history"></i> ประวัติการเช็คย้อนหลัง';
        historyButton.onclick = function() {
            showModal('retroactiveHistoryModal');
        };
        actionButtons.appendChild(historyButton);
    }
}

// ฟังก์ชันแสดงข้อความแจ้งเตือนการเช็คชื่อย้อนหลัง
function showRetroactiveNotification() {
    const notification = document.createElement('div');
    notification.className = 'notification warning';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-history"></i>
            <span>คุณกำลังเช็คชื่อย้อนหลังสำหรับวันที่ ${thaiDate}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    document.body.appendChild(notification);
    
    // กำหนดการปิดเมื่อคลิก
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.remove();
        });
    }
    
    // กำหนดการปิดอัตโนมัติ
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.remove();
        }
    }, 5000);
}

// แก้ไขฟังก์ชันสำหรับการให้แก้ไขสถานะเช็คชื่อย้อนหลัง
function editAttendance(studentId, studentName, status, remarks) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }

    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }

    // ระบุว่าเป็นการแก้ไข ไม่ใช่การเพิ่มใหม่
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '1';
    }

    // ดึงและกำหนดค่า attendance_id
    const studentCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
    if (studentCard) {
        const attendanceId = studentCard.getAttribute('data-attendance-id');
        const attendanceIdInput = document.getElementById('attendanceIdDetail');
        if (attendanceIdInput && attendanceId) {
            attendanceIdInput.value = attendanceId;
        }
    }

    // เลือกสถานะปัจจุบัน
    const statusOption = document.querySelector(`input[name="attendanceStatus"][value="${status}"]`);
    if (statusOption) {
        statusOption.checked = true;
    }

    // ใส่ค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = remarks || '';
    }
    
    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    if (isRetroactive) {
        // เพิ่มหรือแสดงช่องหมายเหตุการเช็คย้อนหลัง
        let retroactiveNoteContainer = document.getElementById('retroactiveNoteContainer');
        if (!retroactiveNoteContainer) {
            const modalBody = document.querySelector('#attendanceDetailModal .modal-body');
            if (modalBody) {
                retroactiveNoteContainer = document.createElement('div');
                retroactiveNoteContainer.id = 'retroactiveNoteContainer';
                retroactiveNoteContainer.className = 'retroactive-note';
                retroactiveNoteContainer.innerHTML = `
                    <label for="retroactiveNote">หมายเหตุการเช็คย้อนหลัง: <span class="required-mark">*</span></label>
                    <textarea id="retroactiveNote" placeholder="ระบุเหตุผลการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    <div class="retroactive-warning">
                        <i class="fas fa-exclamation-triangle"></i> การเช็คชื่อย้อนหลังจำเป็นต้องมีหมายเหตุ
                    </div>
                `;
                
                // แทรกหลังจากช่องหมายเหตุปกติ
                const remarksContainer = document.getElementById('remarksContainer');
                if (remarksContainer) {
                    modalBody.insertBefore(retroactiveNoteContainer, remarksContainer.nextSibling);
                } else {
                    modalBody.appendChild(retroactiveNoteContainer);
                }
            }
        } else {
            retroactiveNoteContainer.style.display = 'block';
        }
    }

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        if (status === 'late' || status === 'leave') {
            remarksContainer.style.display = 'block';
        } else {
            remarksContainer.style.display = 'none';
        }
    }

    // แสดง Modal
    showModal('attendanceDetailModal');
}

// ฟังก์ชันยืนยันการเช็คชื่อรายละเอียด
function confirmDetailAttendance() {
    const studentId = document.getElementById('studentIdDetail').value;
    const status = document.querySelector('input[name="attendanceStatus"]:checked').value;
    const remarks = document.getElementById('attendanceRemarks').value;

    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    if (isRetroactive) {
        const retroactiveNoteInput = document.getElementById('retroactiveNote');
        if (!retroactiveNoteInput || retroactiveNoteInput.value.trim() === '') {
            alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
            retroactiveNoteInput.focus();
            return;
        }
        
        // ใช้ API เช็คชื่อย้อนหลัง
        const retroactiveReason = retroactiveNoteInput.value.trim();
        
        // ปิด Modal
        closeModal('attendanceDetailModal');
        
        // แสดงข้อความกำลังดำเนินการ
        showNotification('กำลังบันทึกการเช็คชื่อย้อนหลัง...', 'info');
        
        // ส่งข้อมูลไปยัง API สำหรับเช็คชื่อย้อนหลัง
        fetch('api/retroactive_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId,
                status: status,
                class_id: currentClassId,
                date: checkDate,
                retroactive_reason: retroactiveReason,
                remarks: remarks
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แสดงข้อความสำเร็จ
                showNotification(`บันทึกการเช็คชื่อย้อนหลัง "${getStatusText(status)}" สำเร็จ`, 'success');
                
                // รีเฟรชหน้าเว็บ
                setTimeout(() => { location.reload(); }, 1500);
            } else {
                showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        });
        
        return;
    }

    // ส่งข้อมูลไปยัง API ปกติ (กรณีไม่ใช่การเช็คชื่อย้อนหลัง)
    const data = {
        student_id: studentId,
        status: status,
        class_id: currentClassId,
        date: checkDate,
        is_retroactive: false,
        remarks: remarks
    };

    // ปิด Modal
    closeModal('attendanceDetailModal');

    // ส่งข้อมูลไปยัง API
    fetch('api/ajax_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(responseData => {
        if (responseData.success) {
            // ดึงการ์ดนักเรียน
            const studentCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);
            if (studentCard) {
                // ย้ายการ์ดไปยังแท็บเช็คชื่อแล้ว
                moveToCheckedTab(studentCard, studentId, status, responseData.student.time_checked, responseData.attendance_id);

                // อัพเดทจำนวนนักเรียน
                updateStudentCounts();

                // อัพเดทสถิติ
                updateAttendanceStats(status);
            } else {
                // กรณีแก้ไขข้อมูลในแท็บเช็คชื่อแล้ว
                const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
                if (existingCard) {
                    updateExistingCard(existingCard, status, responseData.student.time_checked, responseData.attendance_id);
                    updateAttendanceStats(status);
                }
            }

            // แสดงการแจ้งเตือน
            showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
        } else {
            showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
        }
    })
    .catch(error => {
        showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        console.error('Error:', error);
    });
}

// ฟังก์ชันสำหรับการแสดงข้อความแจ้งเตือน
function showNotification(message, type = 'info') {
    try {
        // สร้างแถบแจ้งเตือน
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;

        // กำหนดไอคอนตามประเภท
        let icon = '';
        switch (type) {
            case 'success':
                icon = 'check-circle';
                break;
            case 'warning':
                icon = 'exclamation-triangle';
                break;
            case 'error':
                icon = 'exclamation-circle';
                break;
            case 'info':
            default:
                icon = 'info-circle';
                break;
        }

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;

        // เพิ่มไปยัง body
        document.body.appendChild(notification);

        // กำหนดการปิดเมื่อคลิก
        const closeButton = notification.querySelector('.notification-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                notification.remove();
            });
        }

        // กำหนดการปิดอัตโนมัติ
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.remove();
            }
        }, 5000);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงข้อความแจ้งเตือน:', error);
    }
}

// ดึงข้อความสถานะ
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'late': return 'มาสาย';
        case 'leave': return 'ลา';
        case 'absent': return 'ขาดเรียน';
        default: return 'ไม่ระบุ';
    }
}

// แก้ไขฟังก์ชันเพื่อรองรับการเช็คชื่อย้อนหลัง
function markAttendance(button, status, studentId) {
    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    if (isRetroactive) {
        // เปิด Modal เช็คชื่อย้อนหลังแทน
        showRetroactiveModal(button, status, studentId);
        return;
    }
    
    try {
        // แสดงสถานะกำลังโหลด
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        // ดึงข้อมูลการ์ดนักเรียน
        const studentCard = button.closest('.student-card');

        if (!studentCard) {
            console.error('ไม่พบข้อมูล .student-card สำหรับปุ่มนี้:', button);
            showNotification('เกิดข้อผิดพลาด: กรุณารีเฟรชหน้าและลองใหม่อีกครั้ง', 'error');

            // คืนค่าปุ่มเดิม
            button.innerHTML = originalContent;
            button.disabled = false;
            return;
        }

        // ดึงข้อมูลนักเรียน
        const studentName = studentCard.getAttribute('data-name');

        // สร้างข้อมูลที่จะส่ง
        const data = {
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            is_retroactive: false
        };

        console.log('ส่งข้อมูล:', data);

        // ส่งข้อมูลไปบันทึก AJAX
        fetch('api/ajax_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server response was not OK: ' + response.status);
                }
                return response.json();
            })
            .then(responseData => {
                console.log('ได้รับข้อมูลตอบกลับ:', responseData);

                // คืนค่าปุ่มเดิม
                button.innerHTML = originalContent;
                button.disabled = false;

                if (responseData.success) {
                    // บันทึกสำเร็จ

                    // สร้างสถานะเช็คชื่อและเวลา
                    const timeChecked = responseData.student.time_checked || new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    const attendanceId = responseData.attendance_id;

                    // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
                    moveToCheckedTab(studentCard, studentId, status, timeChecked, attendanceId);

                    // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
                    updateStudentCounts();

                    // อัพเดทสถิติการเช็คชื่อ
                    updateAttendanceStats(status);

                    // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
                    hasChanges = true;

                    // แสดงข้อความแจ้งเตือน
                    showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                } else {
                    // บันทึกไม่สำเร็จ
                    showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                }
            })
            .catch(error => {
                // คืนค่าปุ่มเดิม
                button.innerHTML = originalContent;
                button.disabled = false;

                console.error('เกิดข้อผิดพลาดในการส่งข้อมูล:', error);
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง', 'error');
    }
}

// ฟังก์ชันแสดง Modal สำหรับการเช็คชื่อย้อนหลัง
function showRetroactiveModal(button, status, studentId) {
    // ตรวจสอบว่ามี Modal ที่สร้างไว้แล้วหรือไม่
    let retroModal = document.getElementById('retroactiveModal');
    
    if (!retroModal) {
        // สร้าง Modal ใหม่
        retroModal = document.createElement('div');
        retroModal.id = 'retroactiveModal';
        retroModal.className = 'modal';
        
        retroModal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="retroactiveModalTitle">เช็คชื่อย้อนหลัง</h3>
                        <button type="button" class="close-btn" onclick="closeModal('retroactiveModal')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="retroactive-modal-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="retroactive-modal-text">
                                <strong>คุณกำลังทำการเช็คชื่อย้อนหลังสำหรับวันที่ ${thaiDate}</strong>
                                <span>การเช็คชื่อย้อนหลังจำเป็นต้องระบุเหตุผลที่ชัดเจน และจะถูกบันทึกประวัติการเช็ค</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="retroactiveReason"><strong>เหตุผลการเช็คชื่อย้อนหลัง</strong> <span class="required-mark">*</span></label>
                            <textarea id="retroactiveReason" placeholder="เช่น ใบรับรองแพทย์, หนังสือลาที่ได้รับล่าช้า, การแก้ไขข้อมูลผิดพลาด ฯลฯ" required></textarea>
                            <div class="form-hint">เหตุผลจะถูกบันทึกในประวัติระบบ</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="retroactiveStatus"><strong>สถานะการเช็คชื่อ</strong></label>
                            <div class="status-options">
                                <label class="status-option">
                                    <input type="radio" name="retroactiveStatus" value="present" checked>
                                    <span class="status-label present">
                                        <i class="fas fa-check-circle"></i> มาเรียน
                                    </span>
                                </label>
                                
                                <label class="status-option">
                                    <input type="radio" name="retroactiveStatus" value="late">
                                    <span class="status-label late">
                                        <i class="fas fa-clock"></i> มาสาย
                                    </span>
                                </label>
                                
                                <label class="status-option">
                                    <input type="radio" name="retroactiveStatus" value="leave">
                                    <span class="status-label leave">
                                        <i class="fas fa-clipboard"></i> ลา
                                    </span>
                                </label>
                                
                                <label class="status-option">
                                    <input type="radio" name="retroactiveStatus" value="absent">
                                    <span class="status-label absent">
                                        <i class="fas fa-times-circle"></i> ขาดเรียน
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group" id="retroactiveRemarksContainer">
                            <label for="retroactiveRemarks"><strong>หมายเหตุเพิ่มเติม</strong></label>
                            <textarea id="retroactiveRemarks" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                        </div>
                        
                        <input type="hidden" id="retroactiveStudentId" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn secondary" onclick="closeModal('retroactiveModal')">ยกเลิก</button>
                        <button type="button" class="btn primary" onclick="confirmRetroactiveAttendance()">บันทึก</button>
                    </div>
                </div>
            </div>
        `;
        
        // เพิ่ม Modal เข้าไปในหน้า
        document.body.appendChild(retroModal);
    }
    
    // ดึงชื่อนักเรียน
    const studentCard = button.closest('.student-card');
    let studentName = 'นักเรียน';
    
    if (studentCard) {
        studentName = studentCard.getAttribute('data-name') || 'นักเรียน';
    }
    
    // กำหนดค่าใน Modal
    document.getElementById('retroactiveStudentId').value = studentId;
    document.getElementById('retroactiveModalTitle').textContent = 'เช็คชื่อย้อนหลัง: ' + studentName;
    
    // เลือกสถานะตามที่เลือกจากปุ่ม
    const statusRadio = document.querySelector(`input[name="retroactiveStatus"][value="${status}"]`);
    if (statusRadio) {
        statusRadio.checked = true;
    }
    
    // ล้างค่าเดิม
    document.getElementById('retroactiveReason').value = '';
    document.getElementById('retroactiveRemarks').value = '';
    
    // แสดง Modal
    showModal('retroactiveModal');
}

// ฟังก์ชันยืนยันการเช็คชื่อย้อนหลัง
function confirmRetroactiveAttendance() {
    // ดึงข้อมูลจาก Modal
    const studentId = document.getElementById('retroactiveStudentId').value;
    const reason = document.getElementById('retroactiveReason').value.trim();
    const remarks = document.getElementById('retroactiveRemarks').value.trim();
    const status = document.querySelector('input[name="retroactiveStatus"]:checked').value;
    
    // ตรวจสอบว่ามีเหตุผลหรือไม่
    if (!reason) {
        alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
        document.getElementById('retroactiveReason').focus();
        return;
    }
    
    // ปิด Modal
    closeModal('retroactiveModal');
    
    // แสดงข้อความกำลังดำเนินการ
    showNotification('กำลังบันทึกการเช็คชื่อย้อนหลัง...', 'info');
    
    // ส่งข้อมูลไปยัง API
    fetch('api/retroactive_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            retroactive_reason: reason,
            remarks: remarks
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดงข้อความสำเร็จ
            showNotification(`บันทึกการเช็คชื่อย้อนหลัง "${getStatusText(status)}" สำเร็จ`, 'success');
            
            // รีเฟรชหน้าเว็บ
            setTimeout(() => { location.reload(); }, 1500);
        } else {
            showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}
</script>
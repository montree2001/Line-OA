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
$is_retroactive = ($check_date != date('Y-m-d'));

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


// ดึงรายชื่อนักเรียนทั้งหมดพร้อมสถานะการเช็คชื่อ
$students_query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, u.profile_picture,
                  (SELECT COUNT(*) + 1 FROM students WHERE current_class_id = s.current_class_id AND student_code < s.student_code) as number,
                  a.attendance_id, a.attendance_status, TIME_FORMAT(a.check_time, '%H:%i') as check_time, a.check_method, a.remarks
                 FROM students s
                 JOIN users u ON s.user_id = u.user_id
                 LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = :check_date
                 WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                 ORDER BY s.student_code";

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
        // ตรวจสอบว่ามีการเช็คชื่อแล้วหรือไม่โดยดูจาก attendance_id
        $has_attendance = isset($student['attendance_id']) && $student['attendance_id'] !== null;
        // แยกตามว่าเช็คชื่อแล้วหรือไม่

        // สร้างข้อมูลนักเรียน
        $student_data = [
            'id' => $student['student_id'],
            'number' => $student['number'],
            'code' => $student['student_code'],
            'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
            'profile_picture' => $student['profile_picture'],
            'status' => $has_attendance ? ($student['attendance_status'] ?? 'not_checked') : 'not_checked',
            'time_checked' => $has_attendance ? ($student['check_time'] ?? '') : '',
            'check_method' => $has_attendance ? ($student['check_method'] ?? '') : '',
            'remarks' => $has_attendance ? ($student['remarks'] ?? '') : '',
            'attendance_id' => $student['attendance_id'] ?? null
        ];
        
        // แยกตามว่าเช็คชื่อแล้วหรือไม่
        if (!$has_attendance) {
            $unchecked_students[] = $student_data;
        } else {
            $checked_students[] = $student_data;
        }
    }
} catch (Exception $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน: " . $e->getMessage());
}

$extra_css = [
    'assets/css/new_check_attendance.css',
    'assets/css/modal.css',
    'assets/css/retroactive-check.css', // เพิ่มบรรทัดนี้
    'https://fonts.googleapis.com/icon?family=Material+Icons',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];

$extra_js = [
    'assets/js/check_ajax.js',
    'assets/js/enhance-search.js',
    'assets/js/retroactive-check.js' // เพิ่มบรรทัดนี้
];

// กำหนดเส้นทางเนื้อหา
$content_path = 'pages/new_check_attendance_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once $content_path;
require_once 'templates/footer.php';
?>



<!-- เพิ่มสคริปต์สำหรับการเริ่มต้นระบบ -->
<script>
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
    } else {
        console.error('ไม่สามารถโหลดไฟล์แก้ไขได้');
    }
});
</script>

<script>
// นิยามฟังก์ชัน changeDate ให้พร้อมใช้งานทันที
function changeDate(date) {
    // ใช้ URLSearchParams เพื่อรักษาพารามิเตอร์ URL อื่นๆ
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('date', date);
    
    // รักษาค่า class_id จาก URL ปัจจุบัน (ถ้ามี)
    const classIdParam = urlParams.get('class_id');
    const classId = classIdParam || (typeof currentClassId !== 'undefined' ? currentClassId : '');
    
    if (classId) {
        urlParams.set('class_id', classId);
    }
    
    // นำทางไปยัง URL ใหม่
    window.location.href = `new_check_attendance.php?${urlParams.toString()}`;
}
</script>

<script>
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
 * เพิ่มฟังก์ชัน editAttendance ลงในโค้ดแก้ไข
 * ให้ใส่โค้ดนี้เพิ่มเติม ก่อนบรรทัด document.addEventListener('DOMContentLoaded',...) 
 */

// ฟังก์ชันแก้ไขการเช็คชื่อ
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

    // รีเซ็ตค่าหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
    const retroactiveNoteInput = document.getElementById('retroactiveNote');
    if (retroactiveNoteInput) {
        retroactiveNoteInput.value = '';
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

// ฟังก์ชันสำหรับอัพเดทข้อมูลของการ์ดนักเรียนที่มีการแก้ไข
function updateExistingCard(card, status, timeChecked, attendanceId) {
    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';

    switch (status) {
        case 'present':
            statusClass = 'present';
            statusIcon = 'fa-check-circle';
            statusText = 'มาเรียน';
            break;
        case 'late':
            statusClass = 'late';
            statusIcon = 'fa-clock';
            statusText = 'มาสาย';
            break;
        case 'leave':
            statusClass = 'leave';
            statusIcon = 'fa-clipboard';
            statusText = 'ลา';
            break;
        case 'absent':
            statusClass = 'absent';
            statusIcon = 'fa-times-circle';
            statusText = 'ขาดเรียน';
            break;
    }

    // อัพเดทคลาสการ์ด
    card.className = `student-card ${statusClass}-card`;
    card.setAttribute('data-status', status);
    if (attendanceId) {
        card.setAttribute('data-attendance-id', attendanceId);
    }

    // อัพเดทสถานะ
    const statusBadge = card.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge ${statusClass}`;
        statusBadge.innerHTML = `<i class="fas ${statusIcon}"></i> ${statusText}`;
    }

    // อัพเดทเวลา
    const checkTime = card.querySelector('.check-time');
    if (checkTime && timeChecked) {
        checkTime.textContent = timeChecked;
    }
}

// ฟังก์ชันย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
function moveToCheckedTab(studentCard, studentId, status, timeChecked, attendanceId) {
    // ลบการ์ดจากแท็บเดิม
    studentCard.remove();

    // ตรวจสอบว่ามีรายการในแท็บ waiting เหลืออยู่หรือไม่
    const waitingTab = document.getElementById('waitingTab');
    if (!waitingTab) {
        console.error('ไม่พบ element waitingTab');
        return;
    }

    const waitingStudents = waitingTab.querySelectorAll('.student-card');
    if (waitingStudents.length === 0) {
        // ถ้าไม่มีรายการเหลือ ให้แสดงข้อความว่าง
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
            <h3>เช็คชื่อครบทุกคนแล้ว!</h3>
            <p>ทุกคนได้รับการเช็คชื่อเรียบร้อยแล้ว</p>
        `;
        waitingTab.innerHTML = '';
        waitingTab.appendChild(emptyState);
    }

    // สร้างการ์ดใหม่ในแท็บ "เช็คชื่อแล้ว"
    const checkedTab = document.getElementById('checkedTab');
    if (!checkedTab) {
        console.error('ไม่พบ element checkedTab');
        return;
    }

    // ทำความสะอาดแท็บเป้าหมายหากมีข้อความว่าง
    const emptyState = checkedTab.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    // ตรวจสอบหรือสร้าง student-list
    let studentList = checkedTab.querySelector('.student-list');
    if (!studentList) {
        studentList = document.createElement('div');
        studentList.className = 'student-list';
        checkedTab.appendChild(studentList);
    }

    // หาข้อมูลจากแท็บที่เช็คแล้ว (กรณีแก้ไข)
    const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
    if (existingCard) {
        // อัพเดทการ์ดที่มีอยู่แล้ว
        updateExistingCard(existingCard, status, timeChecked, attendanceId);
        return;
    }

    // สร้างการ์ดใหม่จากข้อมูลใน studentCard
    const newCard = createCheckedCard(studentCard, studentId, status, timeChecked, attendanceId);

    // เพิ่มการ์ดใหม่ลงในรายการ
    studentList.appendChild(newCard);
}

// ฟังก์ชันสร้างการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
function createCheckedCard(originalCard, studentId, status, timeChecked, attendanceId) {
    // ดึงข้อมูลจาก originalCard
    const studentName = originalCard.getAttribute('data-name') || '';
    const studentNumber = originalCard.querySelector('.student-number')?.textContent || '';
    const avatarElement = originalCard.querySelector('.student-avatar');
    const studentAvatar = avatarElement ? avatarElement.outerHTML : '<div class="student-avatar">?</div>';
    const studentCodeElement = originalCard.querySelector('.student-code');
    const studentCode = studentCodeElement ? studentCodeElement.textContent : 'รหัส: -';

    // กำหนดคลาสและไอคอนตามสถานะ
    let statusClass = '';
    let statusIcon = '';
    let statusText = '';

    switch (status) {
        case 'present':
            statusClass = 'present';
            statusIcon = 'fa-check-circle';
            statusText = 'มาเรียน';
            break;
        case 'late':
            statusClass = 'late';
            statusIcon = 'fa-clock';
            statusText = 'มาสาย';
            break;
        case 'leave':
            statusClass = 'leave';
            statusIcon = 'fa-clipboard';
            statusText = 'ลา';
            break;
        case 'absent':
            statusClass = 'absent';
            statusIcon = 'fa-times-circle';
            statusText = 'ขาดเรียน';
            break;
    }

    // สร้างการ์ดใหม่
    const newCard = document.createElement('div');
    newCard.className = `student-card ${statusClass}-card`;
    newCard.setAttribute('data-id', studentId);
    newCard.setAttribute('data-name', studentName);
    newCard.setAttribute('data-status', status);
    if (attendanceId) {
        newCard.setAttribute('data-attendance-id', attendanceId);
    }

    // กำหนด HTML ของการ์ด
    newCard.innerHTML = `
        <div class="student-number">${studentNumber}</div>
        
        <div class="student-info" onclick="editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', '')">
            ${studentAvatar}
            
            <div class="student-details">
                <div class="student-name">${studentName}</div>
                <div class="student-code">${studentCode}</div>
            </div>
        </div>
        
        <div class="student-status-info">
            <div class="status-badge ${statusClass}">
                <i class="fas ${statusIcon}"></i> ${statusText}
            </div>
            
            <div class="check-details">
                <div class="check-time">${timeChecked || ''}</div>
                <div class="check-method">ครู</div>
            </div>
        </div>
    `;

    return newCard;
}

// ฟังก์ชันอัพเดตจำนวนนักเรียนในการเช็คชื่อ
function updateAttendanceStats(status) {
    // อัพเดทสถิติตามสถานะ
    const presentCountElement = document.querySelector('.summary-item.present .summary-value');
    const lateCountElement = document.querySelector('.summary-item.late .summary-value');
    const leaveCountElement = document.querySelector('.summary-item.leave .summary-value');
    const absentCountElement = document.querySelector('.summary-item.absent .summary-value');
    const notCheckedCountElement = document.querySelector('.summary-item.not-checked .summary-value');
    
    if (presentCountElement && lateCountElement && leaveCountElement && absentCountElement && notCheckedCountElement) {
        // นับจำนวนนักเรียนตามสถานะ
        const presentCount = document.querySelectorAll('#checkedTab .student-card[data-status="present"]').length;
        const lateCount = document.querySelectorAll('#checkedTab .student-card[data-status="late"]').length;
        const leaveCount = document.querySelectorAll('#checkedTab .student-card[data-status="leave"]').length;
        const absentCount = document.querySelectorAll('#checkedTab .student-card[data-status="absent"]').length;
        const notCheckedCount = document.querySelectorAll('#waitingTab .student-card').length;

        // อัพเดทการแสดงผล
        presentCountElement.textContent = presentCount;
        lateCountElement.textContent = lateCount;
        leaveCountElement.textContent = leaveCount;
        absentCountElement.textContent = absentCount;
        notCheckedCountElement.textContent = notCheckedCount;
    }
}
// เพิ่มสไตล์ CSS สำหรับ Modal
document.head.insertAdjacentHTML('beforeend', `
<style>
/* สไตล์สำหรับ Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-dialog {
    position: relative;
    width: 90%;
    max-width: 500px;
    margin: 30px auto;
}

.modal-content {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.modal-title {
    font-size: 18px;
    font-weight: 500;
    margin: 0;
    color: #1976d2;
}

.close-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #757575;
}

.close-btn:hover {
    color: #f44336;
}

.modal-body {
    padding: 16px;
}

.modal-footer {
    padding: 16px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>
`);

// แก้ไขฟังก์ชัน showDetailAttendanceModal ให้ทำงานได้อย่างถูกต้อง
function showDetailAttendanceModal(studentId, studentName) {
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
    
    // ระบุว่าเป็นการเพิ่มใหม่ ไม่ใช่การแก้ไข
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '0';
    }
    
    // รีเซ็ตค่า attendance_id
    const attendanceIdInput = document.getElementById('attendanceIdDetail');
    if (attendanceIdInput) {
        attendanceIdInput.value = '';
    }
    
    // รีเซ็ตค่าตัวเลือกเป็น "มาเรียน"
    const presentOption = document.querySelector('input[name="attendanceStatus"][value="present"]');
    if (presentOption) {
        presentOption.checked = true;
    }
    
    // รีเซ็ตค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = '';
    }
    
    // รีเซ็ตค่าหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
    const retroactiveNoteInput = document.getElementById('retroactiveNote');
    if (retroactiveNoteInput) {
        retroactiveNoteInput.value = '';
    }
    
    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        remarksContainer.style.display = 'none';
    }
    
    // แสดง Modal
    showModal('attendanceDetailModal');
}

// นิยามฟังก์ชันที่ควรมีทั้งหมดเพื่อให้ระบบทำงานได้อย่างถูกต้อง
function confirmDetailAttendance() {
    const studentId = document.getElementById('studentIdDetail').value;
    const status = document.querySelector('input[name="attendanceStatus"]:checked').value;
    const remarks = document.getElementById('attendanceRemarks').value;
    const retroactiveNote = document.getElementById('retroactiveNote')?.value || '';

    // รวมหมายเหตุ
    let finalRemarks = remarks;
    if (retroactiveNote) {
        finalRemarks = finalRemarks ? `${finalRemarks} (${retroactiveNote})` : retroactiveNote;
    }

    // ส่งข้อมูลไปยัง API
    const data = {
        student_id: studentId,
        status: status,
        class_id: currentClassId,
        date: checkDate,
        is_retroactive: isRetroactive,
        remarks: finalRemarks
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

// ฟังก์ชันเพิ่มเติมที่จำเป็น
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'late': return 'มาสาย';
        case 'leave': return 'ลา';
        case 'absent': return 'ขาดเรียน';
        default: return 'ไม่ระบุ';
    }
}

// แก้ไขการทำงานของปุ่ม Scan QR
function scanQR() {
    showModal('qrModal');
    showNotification('กำลังเรียกใช้งานกล้อง...', 'info');
}

// แก้ไขการทำงานของปุ่ม Create PIN
function createPIN() {
    showModal('pinModal');
    
    // ส่งคำขอสร้าง PIN ใหม่ไปยัง API
    fetch('api/create_pin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                class_id: currentClassId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แสดง PIN ใน Modal
                const pinDigits = document.querySelectorAll('.pin-digit');
                const pin = data.pin_code.split('');
                
                pinDigits.forEach((digit, index) => {
                    if (index < pin.length) {
                        digit.textContent = pin[index];
                    }
                });
                
                // อัพเดทเวลาหมดอายุ
                const expireTimeElement = document.getElementById('expireTime');
                if (expireTimeElement) {
                    expireTimeElement.textContent = data.expire_minutes;
                }
                
                showNotification('สร้างรหัส PIN สำเร็จ', 'success');
            } else {
                showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        });
}

// เมื่อโหลดเอกสารเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    console.log('โหลดไฟล์แก้ไข Modal สำเร็จ');
    
    // แก้ไขปุ่มปิดทั้งหมดใน Modal
    document.querySelectorAll('.close-btn').forEach(button => {
        const modalId = button.closest('.modal').id;
        button.onclick = function() {
            closeModal(modalId);
        };
    });
    
    // จัดการระบบแท็บ
    setupTabSystem();
    
    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const statusInputs = document.querySelectorAll('input[name="attendanceStatus"]');
    const remarksContainer = document.getElementById('remarksContainer');
    
    if (statusInputs.length > 0 && remarksContainer) {
        statusInputs.forEach(input => {
            input.addEventListener('change', function() {
                const status = this.value;
                
                // แสดงช่องหมายเหตุเฉพาะเมื่อเลือกสถานะมาสายหรือลา
                if (status === 'late' || status === 'leave') {
                    remarksContainer.style.display = 'block';
                } else {
                    remarksContainer.style.display = 'none';
                }
            });
        });
    }
    
    // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
    updateStudentCounts();
});
</script>


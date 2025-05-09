<?php
/**
 * teachers.php - หน้าจัดการข้อมูลครูที่ปรึกษา
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

// ตรวจสอบการล็อกอิน (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// โหลดไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
$_SESSION['user_role']='admin';
// โหลดคลาสสำหรับจัดการข้อมูลครู
require_once '../models/Teacher.php';
$teacherModel = new Teacher();

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'teachers';
$page_title = 'จัดการครูที่ปรึกษา';
$page_header = 'จัดการข้อมูลครูที่ปรึกษา';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มครูที่ปรึกษา',
        'icon' => 'person_add',
        'onclick' => 'showAddTeacherModal()'
    ],
    [
        'text' => 'นำเข้าข้อมูล',
        'icon' => 'upload_file',
        'onclick' => 'showImportModal()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// เพิ่ม Bootstrap CSS แต่ยังคงใช้ CSS หลักของระบบ
$extra_css = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'assets/css/teachers.css', // CSS เฉพาะสำหรับหน้านี้
    'assets/css/import.css' // เพิ่ม CSS สำหรับนำเข้าข้อมูล
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'assets/js/teachers.js', // JS เฉพาะสำหรับหน้านี้
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', // เพิ่ม SheetJS
    'assets/js/import-teachers.js' // เพิ่ม JS สำหรับนำเข้าข้อมูล
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/teachers_content.php';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // เพิ่มข้อมูลครูใหม่
    if (isset($_POST['add_teacher'])) {
        try {
            $teacher_data = [
                'title' => $_POST['teacher_prefix'] ?? 'อาจารย์',
                'first_name' => $_POST['teacher_first_name'] ?? '',
                'last_name' => $_POST['teacher_last_name'] ?? '',
                'national_id' => $_POST['teacher_national_id'] ?? '',
                'department' => $_POST['teacher_department'] ?? '',
                'position' => $_POST['teacher_position'] ?? '',
                'phone_number' => $_POST['teacher_phone'] ?? '',
                'email' => $_POST['teacher_email'] ?? '',
                'status' => $_POST['teacher_status'] ?? 'active',
                'class_id' => $_POST['teacher_class'] ?? null
            ];
            
            $teacherModel->addTeacher($teacher_data);
            $success_message = "เพิ่มข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "ไม่สามารถเพิ่มข้อมูลครู: " . $e->getMessage();
        }
    } 
    // แก้ไขข้อมูลครู
    elseif (isset($_POST['edit_teacher'])) {
        try {
            $teacher_id = $_POST['teacher_id'] ?? 0;
            
            $teacher_data = [
                'title' => $_POST['teacher_prefix'] ?? 'อาจารย์',
                'first_name' => $_POST['teacher_first_name'] ?? '',
                'last_name' => $_POST['teacher_last_name'] ?? '',
                'national_id' => $_POST['teacher_national_id'] ?? '',
                'department' => $_POST['teacher_department'] ?? '',
                'position' => $_POST['teacher_position'] ?? '',
                'phone_number' => $_POST['teacher_phone'] ?? '',
                'email' => $_POST['teacher_email'] ?? '',
                'status' => $_POST['teacher_status'] ?? 'active',
                'class_id' => $_POST['teacher_class'] ?? null
            ];
            
            $teacherModel->updateTeacher($teacher_id, $teacher_data);
            $success_message = "แก้ไขข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "ไม่สามารถแก้ไขข้อมูลครู: " . $e->getMessage();
        }
    } 
    // ลบข้อมูลครู
    elseif (isset($_POST['delete_teacher'])) {
        try {
            $teacher_id = $_POST['teacher_id'] ?? 0;
            
            $teacherModel->deleteTeacher($teacher_id);
            $success_message = "ลบข้อมูลครูที่ปรึกษาเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "ไม่สามารถลบข้อมูลครู: " . $e->getMessage();
        }
    } 
    // เปลี่ยนสถานะครู (ระงับ/เปิดใช้งาน)
    elseif (isset($_POST['toggle_status'])) {
        try {
            $teacher_id = $_POST['teacher_id'] ?? 0;
            $active = $_POST['status'] === 'active';
            
            $teacherModel->toggleTeacherStatus($teacher_id, $active);
            $status_text = $active ? "เปิดใช้งาน" : "ระงับการใช้งาน";
            $success_message = "{$status_text}ครูที่ปรึกษาเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "ไม่สามารถเปลี่ยนสถานะครู: " . $e->getMessage();
        }
    } 
    // นำเข้าข้อมูลครูจากไฟล์ Excel
    elseif (isset($_POST['import_teachers'])) {
        try {
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] != UPLOAD_ERR_OK) {
                throw new Exception("ไม่พบไฟล์ที่อัปโหลด");
            }
            
            require_once '../import/ImportTeachers.php';
            $importer = new ImportTeachers();
            
            $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] == 'on';
            $result = $importer->import($_FILES['import_file'], $overwrite);
            
            if ($result['success']) {
                $success_message = "นำเข้าข้อมูลเรียบร้อยแล้ว: เพิ่มใหม่ {$result['new']} คน, อัปเดต {$result['updated']} คน";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . implode(", ", $result['errors']);
            }
            
        } catch (Exception $e) {
            $error_message = "ไม่สามารถนำเข้าข้อมูล: " . $e->getMessage();
        }
    }
    
    // Redirect เพื่อป้องกันการ resubmit form
    $params = [];
    if (isset($success_message)) {
        $params[] = "success=" . urlencode($success_message);
    }
    if (isset($error_message)) {
        $params[] = "error=" . urlencode($error_message);
    }
    
    $redirect_url = "teachers.php";
    if (!empty($params)) {
        $redirect_url .= "?" . implode("&", $params);
    }
    
    header("Location: " . $redirect_url);
    exit;
}

// ดึงข้อความแจ้งเตือนจาก URL (ถ้ามี)
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// ดึงข้อมูลครูจากฐานข้อมูล
$filters = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['department']) && $_GET['department'] !== '') {
    $filters['department'] = $_GET['department'];
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['line_status']) && $_GET['line_status'] !== '') {
    $filters['line_status'] = $_GET['line_status'];
}

// ดึงข้อมูลครูทั้งหมด
$teachers = $teacherModel->getAllTeachers(100, 0, $filters);

// ดึงข้อมูลสถิติเกี่ยวกับครู
$teachers_stats = $teacherModel->getTeacherStats();

// ดึงรายชื่อแผนกและห้องเรียนสำหรับฟอร์ม
$departments = $teacherModel->getAllDepartments();
$classes = $teacherModel->getAllClasses();

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'teachers' => $teachers,
    'teachers_stats' => $teachers_stats,
    'departments' => $departments,
    'classes' => $classes,
    'success_message' => $success_message ?? null,
    'error_message' => $error_message ?? null
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';



// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
<script>
    /**
 * แสดงโมดัลนำเข้าข้อมูล
 */
function showImportModal() {
    // รีเซ็ตฟอร์ม
    closeAllModals();
    
    const form = document.getElementById('importTeacherFullForm');
    if (form) {
        form.reset();
    }
    
    // แสดงโมดัล
    const modal = document.getElementById('importTeacherModal');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        console.error("ไม่พบโมดัลสำหรับนำเข้าข้อมูลครู");
    }
}
</script>
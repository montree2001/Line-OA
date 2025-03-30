<?php
/**
 * register.php - หน้าลงทะเบียนสำหรับนักเรียน
 * ระบบลงทะเบียนสำหรับนักเรียนที่เข้าใช้งานครั้งแรกผ่าน LINE
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

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;
$line_id = $_SESSION['line_id'] ?? null;
$profile_picture = $_SESSION['profile_picture'] ?? null;

// กำหนดค่าเริ่มต้นสำหรับขั้นตอนการลงทะเบียน
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error_message = '';
$success_message = '';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ตรวจสอบปีการศึกษาที่ใช้งานอยู่
try {
    $academic_year_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->prepare($academic_year_sql);
    $stmt->execute();
    $academic_year_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_year_row) {
        $current_academic_year_id = $academic_year_row['academic_year_id'];
    } else {
        $error_message = "ไม่พบข้อมูลปีการศึกษาที่ใช้งานอยู่ กรุณาติดต่อผู้ดูแลระบบ";
        $step = 'error';
    }
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
    $step = 'error';
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // นำเข้าไฟล์สำหรับประมวลผลฟอร์ม
    require_once 'register_process.php';
}

// กำหนดหัวข้อเพจ
$page_title = "STD-Prasat - ลงทะเบียนนักเรียน";

// เริ่มแสดงเพจ
include 'templates/header.php';
?>

<div class="container">
    <!-- Step Indicator -->
    <div class="steps">
        <div class="step <?php echo ($step == 1 || $step == 'error') ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
            <div class="step-number">1</div>
            <div class="step-title">เริ่มต้น</div>
        </div>
        
        <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
        
        <div class="step <?php echo $step == 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
            <div class="step-number">2</div>
            <div class="step-title">ค้นหาข้อมูล</div>
        </div>
        
        <div class="step-line <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
        
        <div class="step <?php echo ($step == 3 || $step == 33) ? 'active' : ($step > 3 ? 'completed' : ''); ?>">
            <div class="step-number">3</div>
            <div class="step-title">ยืนยันข้อมูล</div>
        </div>
        
        <div class="step-line <?php echo $step > 3 ? 'completed' : ''; ?>"></div>
        
        <div class="step <?php echo $step == 4 ? 'active' : ($step > 4 ? 'completed' : ''); ?>">
            <div class="step-number">4</div>
            <div class="step-title">ครูที่ปรึกษา</div>
        </div>
        
        <div class="step-line <?php echo $step > 4 ? 'completed' : ''; ?>"></div>
        
        <div class="step <?php echo $step == 5 || $step == 55 ? 'active' : ($step > 5 ? 'completed' : ''); ?>">
            <div class="step-number">5</div>
            <div class="step-title">สรุปข้อมูล</div>
        </div>
    </div>

    <!-- แสดงข้อความข้อผิดพลาดและสำเร็จ -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <span class="material-icons">error</span>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <span class="material-icons">check_circle</span>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <!-- แสดงเนื้อหาตามขั้นตอน -->
    <?php
    switch ($step) {
        case 1:
            include 'register_steps/step1_welcome.php';
            break;
        case 2:
            include 'register_steps/step2_student_code.php';
            break;
        case 3:
            // ตรวจสอบว่ามีข้อมูลนักศึกษาใน session หรือไม่ก่อนแสดงหน้ายืนยันข้อมูล
            if (isset($_SESSION['student_code']) && isset($_SESSION['student_first_name']) && isset($_SESSION['student_last_name'])) {
                include 'register_steps/step3_confirm_info.php';
            } else {
                // ถ้าไม่มีข้อมูลให้กลับไปหน้าค้นหารหัสนักศึกษา
                header('Location: register.php?step=2');
                exit;
            }
            break;
        case 33:
            // ตรวจสอบว่ามีรหัสนักศึกษาหรือไม่
            if (!isset($_SESSION['student_code'])) {
                header('Location: register.php?step=2');
                exit;
            }
            include 'register_steps/step3_manual_info.php';
            break;
        case 4:
            // ต้องมีข้อมูลพื้นฐานที่ได้จากขั้นตอนก่อนหน้า
            if (!isset($_SESSION['student_code']) || !isset($_SESSION['student_first_name'])) {
                header('Location: register.php?step=2');
                exit;
            }
            include 'register_steps/step4_advisor.php';
            break;
        case 5:
            // ตรวจสอบว่ามีผลลัพธ์การค้นหาครูที่ปรึกษาใน session หรือไม่
            if (isset($_SESSION['search_teacher_results']) && !empty($_SESSION['search_teacher_results'])) {
                include 'register_steps/step5_class.php';
            } else {
                // ถ้าไม่มีผลลัพธ์ให้ไปหน้ากรอกข้อมูลห้องเรียนเอง
                header('Location: register.php?step=55');
                exit;
            }
            break;
        case 55:
            // ต้องมีข้อมูลพื้นฐานที่ได้จากขั้นตอนก่อนหน้า
            if (!isset($_SESSION['student_code']) || !isset($_SESSION['student_first_name'])) {
                header('Location: register.php?step=2');
                exit;
            }
            include 'register_steps/step5_manual_class.php';
            break;
        case 6:
            // ต้องมีข้อมูลพื้นฐานที่ได้จากขั้นตอนก่อนหน้า
            if (!isset($_SESSION['student_code']) || !isset($_SESSION['student_first_name'])) {
                header('Location: register.php?step=2');
                exit;
            }
            include 'register_steps/step6_additional.php';
            break;
        case 7:
            include 'register_steps/step7_complete.php';
            break;
        case 'error':
            include 'register_steps/error.php';
            break;
        default:
            include 'register_steps/step1_welcome.php';
    }
    ?>
</div>

<?php include 'templates/footer.php'; ?>
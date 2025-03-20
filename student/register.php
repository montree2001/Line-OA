<?php
/**
 * register.php - หน้าลงทะเบียนสำหรับนักเรียน
 * ระบบลงทะเบียนสำหรับนักเรียนที่เข้าใช้งานครั้งแรกผ่าน LINE
 */
session_start();
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
    require_once 'register_process.php';
}

// กำหนดหัวข้อเพจ
$page_title = "STD-Prasat - ลงทะเบียนนักเรียน";

// เริ่มแสดงเพจ
include 'includes/header.php';
?>

<div class="container">
    <!-- Step Indicator -->
    <?php include 'includes/step_indicator.php'; ?>

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
            include 'register_steps/step3_confirm_info.php';
            break;
        case '3manual':
            include 'register_steps/step3_manual_info.php';
            break;
        case 4:
            include 'register_steps/step4_advisor.php';
            break;
        case 5:
            include 'register_steps/step5_class.php';
            break;
        case '5manual':
            include 'register_steps/step5_manual_class.php';
            break;
        case 6:
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

<?php include 'includes/footer.php'; ?>
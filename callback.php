<?php
/**
 * callback.php - หน้ารับข้อมูลหลังจาก LINE Login
 */
session_start();
require_once 'config/db_config.php';
require_once 'lib/line_api.php';
require_once 'config_manager.php'; // เพิ่มไฟล์ ConfigManager

// แสดงข้อผิดพลาดเพื่อช่วยในการดีบัก
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ดึงการตั้งค่า LINE จากฐานข้อมูล
$configManager = ConfigManager::getInstance();
$lineSettings = $configManager->getLineSettings();

// ตรวจสอบว่ามีโค้ดหรือไม่
if (!isset($_GET['code'])) {
    echo "ไม่ได้รับโค้ดจาก LINE";
    exit;
}

// รับโค้ดจาก URL
$code = $_GET['code'];

// ตรวจสอบ state
$state = isset($_GET['state']) ? $_GET['state'] : '';
if (!in_array($state, ['student', 'teacher', 'parent', 'admin'])) {
    echo "ไม่สามารถระบุบทบาทได้";
    exit;
}

// กำหนดค่า LINE Login ตามบทบาท
if ($configManager->getBoolSetting('single_line_oa', true)) {
    // กรณีใช้ LINE OA เดียว
    $client_id = $lineSettings['client_id'];
    $client_secret = $lineSettings['client_secret'];
    $redirect_uri = $lineSettings['redirect_uri'];
} else {
    // กรณีใช้หลาย LINE OA
    $client_id = $lineSettings[$state]['client_id'];
    $client_secret = $lineSettings[$state]['client_secret'];
    $redirect_uri = $lineSettings[$state]['redirect_uri'];
}

// สร้างอ็อบเจ็กต์ LINE API
$line_api = new LineAPI($client_id, $client_secret, $redirect_uri);

// เรียกใช้ API เพื่อรับ Token
$token = $line_api->getToken($code);
if (!$token) {
    echo "ไม่สามารถรับโทเค็นได้";
    exit;
}

// รับข้อมูลผู้ใช้จาก LINE
$user_profile = $line_api->getUserProfile($token);
if (!$user_profile) {
    echo "ไม่สามารถรับข้อมูลผู้ใช้ได้";
    exit;
}

// บันทึกข้อมูลเข้าสู่ระบบ
$line_id = $user_profile['userId'];
$display_name = $user_profile['displayName'];
$picture_url = isset($user_profile['pictureUrl']) ? $user_profile['pictureUrl'] : '';


// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีผู้ใช้อยู่แล้วหรือไม่
$stmt = $conn->prepare("SELECT user_id, role, first_name, last_name FROM users WHERE line_id = ?");
$stmt->bind_param("s", $line_id);
$stmt->execute();
$result = $stmt->get_result();

// สร้างตัวแปรเพื่อเก็บ user_id
$user_id = null;

if ($result->num_rows > 0) {
    // ผู้ใช้มีอยู่แล้ว - อัปเดตข้อมูล
    $user_data = $result->fetch_assoc();
    $user_id = $user_data['user_id'];
    
    // อัปเดตข้อมูลผู้ใช้เมื่อมีการล็อกอินใหม่
    $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ?, last_login = NOW() WHERE user_id = ?");
    $update_stmt->bind_param("si", $picture_url, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // ถ้าผู้ใช้มีบทบาทอยู่แล้ว ใช้บทบาทนั้น
    $role = $user_data['role'];
    
    // บันทึกข้อมูลเพิ่มเติมใน session เพื่อใช้ในหน้าลงทะเบียน
    $_SESSION['profile_picture'] = $picture_url;
    if (!empty($user_data['first_name'])) {
        $_SESSION['first_name'] = $user_data['first_name'];
    }
    if (!empty($user_data['last_name'])) {
        $_SESSION['last_name'] = $user_data['last_name'];
    }
} else {
    // ผู้ใช้ใหม่ - เพิ่มข้อมูล
    // กำหนดค่าเริ่มต้นสำหรับฟิลด์ที่ต้องมีค่า
    $empty_title = ''; // ค่าว่างสำหรับ title ที่เป็น NOT NULL

    // ใช้ try-catch เพื่อจับข้อผิดพลาดที่อาจเกิดขึ้น
    try {
        // เตรียม statement พร้อมกำหนดค่าสำหรับทุกฟิลด์ที่จำเป็น
        $insert_stmt = $conn->prepare("INSERT INTO users (line_id, role, title, profile_picture, created_at, last_login, gdpr_consent) VALUES (?, ?, ?, ?, NOW(), NOW(), 0)");
        
        // ตรวจสอบว่า statement ถูกเตรียมสำเร็จหรือไม่
        if ($insert_stmt === false) {
            throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
        }
        
        $insert_stmt->bind_param("ssss", $line_id, $state, $empty_title, $picture_url);
        
        // ทดลองใช้การ query โดยตรงถ้า bind_param ไม่สำเร็จ
        if (!$insert_stmt->execute()) {
            // ถ้า execute ไม่สำเร็จ ทดลองใช้การ query ตรงๆ
            $sql = "INSERT INTO users (line_id, role, title, profile_picture, created_at, last_login, gdpr_consent) VALUES ('$line_id', '$state', '', '$picture_url', NOW(), NOW(), 0)";
            if (!$conn->query($sql)) {
                throw new Exception("ไม่สามารถเพิ่มข้อมูลผู้ใช้ได้: " . $conn->error);
            }
        }
        
        $user_id = $conn->insert_id;
        $insert_stmt->close();
        
        // บันทึกบทบาท
        $role = $state;
        
        // บันทึกข้อมูลเพิ่มเติมใน session
        $_SESSION['profile_picture'] = $picture_url;
        
        
    } catch (Exception $e) {
        // บันทึกข้อผิดพลาดและแสดงผล
        error_log("ข้อผิดพลาดในการเพิ่มข้อมูลผู้ใช้: " . $e->getMessage());
        die("เกิดข้อผิดพลาดในการลงทะเบียน: " . $e->getMessage());
    }
}

// บันทึกข้อมูลใน session
$_SESSION['user_id'] = $user_id;
$_SESSION['line_id'] = $line_id;
$_SESSION['role'] = $role;
$_SESSION['logged_in'] = true;

// ปิดการเชื่อมต่อฐานข้อมูล
$stmt->close();
$conn->close();

// เปลี่ยนเส้นทางไปยังหน้าที่เหมาะสมตามบทบาท
switch ($role) {
    case 'student':
        // ตรวจสอบว่าเป็นการลงทะเบียนครั้งแรกหรือไม่
        if (!checkStudentRegistered($user_id)) {
            header('Location: student/register.php?step=1');
        } else {
            header('Location: student/dashboard.php');
        }
        break;
    case 'teacher':
        // ตรวจสอบว่าเป็นการลงทะเบียนครั้งแรกหรือไม่
        if (!checkTeacherRegistered($user_id)) {
            header('Location: teacher/register.php?step=1');
        } else {
            header('Location: teacher/home.php');
        }
        break;
    case 'parent':
        // ตรวจสอบว่าเป็นการลงทะเบียนครั้งแรกหรือไม่
        if (!checkParentRegistered($user_id)) {
            header('Location: parent/register.php');
        } else {
            header('Location: parent/dashboard.php');
        }
        break;
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    default:
        header('Location: index.php');
        break;
}
exit;

// ฟังก์ชันตรวจสอบว่านักเรียนได้ลงทะเบียนข้อมูลเพิ่มเติมแล้วหรือไม่
function checkStudentRegistered($user_id) {
    global $conn;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_registered = ($result->num_rows > 0);
    
    $stmt->close();
    $conn->close();
    
    return $is_registered;
}

// ฟังก์ชันตรวจสอบว่าครูได้ลงทะเบียนข้อมูลเพิ่มเติมแล้วหรือไม่
function checkTeacherRegistered($user_id) {
    global $conn;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_registered = ($result->num_rows > 0);
    
    $stmt->close();
    $conn->close();
    
    return $is_registered;
}

// ฟังก์ชันตรวจสอบว่าผู้ปกครองได้ลงทะเบียนข้อมูลเพิ่มเติมแล้วหรือไม่
function checkParentRegistered($user_id) {
    global $conn;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_registered = ($result->num_rows > 0);
    
    $stmt->close();
    $conn->close();
    
    return $is_registered;
}
?>
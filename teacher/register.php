<?php
session_start();
require_once '../config/db_config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทครูหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'];
$line_id = $_SESSION['line_id'];
$profile_picture = $_SESSION['profile_picture'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีข้อมูลครูแล้วหรือไม่
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
if ($stmt === false) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // มีข้อมูลครูแล้ว นำไปที่หน้า dashboard
    header('Location: dashboard.php');
    exit;
}

// กำหนดค่าเริ่มต้นสำหรับขั้นตอนการลงทะเบียน
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error_message = '';
$success_message = '';
$national_id = '';
$title = '';
$first_name = '';
$last_name = '';
$department = '';
$phone_number = '';
$email = '';
$position = '';

// จัดการข้อมูลที่ส่งมา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2: // ขั้นตอนยืนยันตัวตนด้วยบัตรประชาชน
            $national_id = $_POST['national_id'] ?? '';
            
            if (empty($national_id) || strlen($national_id) !== 13) {
                $error_message = "กรุณากรอกเลขบัตรประชาชน 13 หลักให้ถูกต้อง";
            } else {
                // ตรวจสอบว่ามีข้อมูลครูในฐานข้อมูลหรือไม่
                // ทดลองใช้การคิวรีโดยตรง
                $query = "SELECT * FROM teacher_pending WHERE national_id = '$national_id'";
                $result = $conn->query($query);
                
                if ($result === false) {
                    $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $conn->error;
                } elseif ($result->num_rows > 0) {
                    // พบข้อมูลครูในตาราง teacher_pending
                    $teacher_data = $result->fetch_assoc();
                    $title = $teacher_data['title'];
                    $first_name = $teacher_data['first_name'];
                    $last_name = $teacher_data['last_name'];
                    $department = $teacher_data['department'];
                    $position = $teacher_data['position'] ?? '';
                    
                    // บันทึกข้อมูลทั้งหมดไว้ใน session
                    $_SESSION['national_id'] = $national_id;
                    $_SESSION['teacher_title'] = $title;
                    $_SESSION['teacher_first_name'] = $first_name;
                    $_SESSION['teacher_last_name'] = $last_name;
                    $_SESSION['teacher_department'] = $department;
                    $_SESSION['teacher_position'] = $position;
                    
                    // ไปยังขั้นตอนถัดไป
                    header('Location: register.php?step=3');
                    exit;
                } else {
                    // ถ้าไม่พบข้อมูลให้แสดงข้อความเตือน
                    $error_message = "ไม่พบข้อมูลครูที่ปรึกษาในระบบ กรุณาตรวจสอบเลขบัตรประชาชนอีกครั้ง หรือติดต่อเจ้าหน้าที่";
                }
            }
            break;
            
        case 3: // ขั้นตอนกรอกข้อมูลส่วนตัว
            $title = $_POST['title'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $department = $_POST['department'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';
            $email = $_POST['email'] ?? '';
            $position = $_POST['position'] ?? '';
            $gdpr_consent = isset($_POST['gdpr_consent']) ? 1 : 0;
            
            // ดึง national_id จาก session
            $national_id = $_SESSION['national_id'] ?? '';
            
            if (empty($first_name) || empty($last_name) || empty($department) || empty($phone_number)) {
                $error_message = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
            } elseif (!$gdpr_consent) {
                $error_message = "กรุณายินยอมให้เก็บข้อมูลส่วนบุคคลเพื่อดำเนินการต่อ";
            } elseif (empty($national_id)) {
                $error_message = "ไม่พบข้อมูลเลขบัตรประชาชน กรุณาลงทะเบียนใหม่";
                header('Location: register.php?step=2');
                exit;
            } else {
                // ทำการบันทึกข้อมูลแบบไม่ใช้ transaction เพื่อทดสอบ
                
                // 1. อัปเดตข้อมูลในตาราง users
                $update_sql = "UPDATE users SET title = '$title', first_name = '$first_name', last_name = '$last_name', phone_number = '$phone_number', email = '$email', gdpr_consent = $gdpr_consent, gdpr_consent_date = NOW() WHERE user_id = $user_id";
                
                if (!$conn->query($update_sql)) {
                    $error_message = "ไม่สามารถอัปเดตข้อมูลผู้ใช้ได้: " . $conn->error;
                } else {
                    // 2. เพิ่มข้อมูลครูในตาราง teachers
                    $insert_sql = "INSERT INTO teachers (user_id, title, national_id, department, position, first_name, last_name, created_at) VALUES ($user_id, '$title', '$national_id', '$department', '$position', '$first_name', '$last_name', NOW())";
                    
                    if (!$conn->query($insert_sql)) {
                        $error_message = "ไม่สามารถเพิ่มข้อมูลครูได้: " . $conn->error;
                    } else {
                        // 3. ลบข้อมูลจากตาราง teacher_pending (ถ้ามี)
                        $delete_sql = "DELETE FROM teacher_pending WHERE national_id = '$national_id'";
                        $conn->query($delete_sql); // ไม่ว่าจะสำเร็จหรือไม่ ไม่ส่งผลต่อการลงทะเบียน
                        
                        // บันทึกสำเร็จ ไปยังขั้นตอนถัดไป
                        header('Location: register.php?step=4');
                        exit;
                    }
                }
            }
            break;
    }
}

// ถ้าอยู่ในขั้นตอนที่ 3 และมี national_id ใน session
if ($step === 3 && isset($_SESSION['national_id'])) {
    $national_id = $_SESSION['national_id'];
    
    // ดึงข้อมูลจาก session ที่เราเก็บไว้ในขั้นตอนที่ 2
    if (isset($_SESSION['teacher_title'])) {
        $title = $_SESSION['teacher_title'];
    }
    
    if (isset($_SESSION['teacher_first_name'])) {
        $first_name = $_SESSION['teacher_first_name'];
    }
    
    if (isset($_SESSION['teacher_last_name'])) {
        $last_name = $_SESSION['teacher_last_name'];
    }
    
    if (isset($_SESSION['teacher_department'])) {
        $department = $_SESSION['teacher_department'];
    }
    
    if (isset($_SESSION['teacher_position'])) {
        $position = $_SESSION['teacher_position'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - <?php 
        if ($step === 1) echo "เข้าสู่ระบบ";
        elseif ($step === 2) echo "ยืนยันตัวตน";
        elseif ($step === 3) echo "ข้อมูลส่วนตัว";
        else echo "ลงทะเบียนเสร็จสิ้น";
    ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <style>
        /* CSS styles remain the same */
        /* ตั้งค่าพื้นฐาน */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* ส่วนหัว */
        .header {
            background-color: #1976d2;
            color: white;
            padding: 15px;
            text-align: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        
        .header-icon {
            font-size: 24px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-spacer {
            width: 40px;
        }
        
        .container {
            max-width: 480px;
            margin: 70px auto 20px;
            padding: 15px;
            min-height: calc(100vh - 90px);
            display: flex;
            flex-direction: column;
        }
        
        /* Step indicator */
        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 70px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background-color: #1976d2;
        }
        
        .step.completed .step-number {
            background-color: #4caf50;
        }
        
        .step-line {
            flex: 1;
            height: 3px;
            background-color: #ccc;
            position: relative;
            top: -15px;
            z-index: 1;
        }
        
        .step.active .step-line, .step.completed .step-line {
            background-color: #1976d2;
        }
        
        .step-title {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .step.active .step-title {
            color: #1976d2;
            font-weight: 500;
        }
        
        /* Card style */
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #1976d2;
        }
        
        .card-content {
            margin-bottom: 15px;
        }
        
        /* Success styles */
        .success-icon {
            width: 120px;
            height: 120px;
            background-color: #4caf50;
            border-radius: 50%;
            margin: 10px auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }
        
        .success-icon .material-icons {
            font-size: 70px;
        }
        
        .success-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .success-message {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .success-message h2 {
            font-size: 26px;
            margin-bottom: 15px;
            color: #4caf50;
        }
        
        .success-message p {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        /* Input fields */
        .input-container {
            margin-bottom: 25px;
        }
        
        .input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .input-field {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-field:focus {
            border-color: #1976d2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }
        
        .input-field:disabled,
        .input-field[readonly] {
            background-color: #f5f5f5;
            color: #666;
        }
        
        /* Buttons */
        .btn {
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            background-color: #1565c0;
        }
        
        .btn.back {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .btn.back:hover {
            background-color: #e0e0e0;
        }
        
        .btn.next {
            background-color: #1976d2;
        }
        
        .btn.success {
            background-color: #4caf50;
            margin-top: auto;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
            padding: 16px 30px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
        }
        
        .btn.success:hover {
            background-color: #388e3c;
        }
        
        .btn .material-icons {
            margin-right: 8px;
        }
        
        .btn.next .material-icons,
        .btn.success .material-icons {
            margin-right: 0;
            margin-left: 8px;
        }
        
        /* Nav Buttons */
        .page-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .nav-button {
            padding: 12px 20px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }
        
        .nav-button:hover {
            background-color: #1565c0;
        }
        
        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .nav-button.back {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .nav-button.back:hover {
            background-color: #e0e0e0;
        }
        
        .nav-button.next {
            margin-left: auto;
        }
        
        .nav-button .material-icons {
            margin-right: 5px;
        }
        
        .nav-button.next .material-icons {
            margin-right: 0;
            margin-left: 5px;
        }
        
        /* Profile Avatar */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e3f2fd;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-initial {
            font-size: 54px;
            color: #1976d2;
            font-weight: 500;
        }
        
        .avatar-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #1976d2;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .avatar-edit input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        /* Help text */
        .help-text {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .contact-admin {
            text-align: center;
            font-size: 14px;
            color: #666;
            padding: 10px 0;
        }
        
        .contact-admin a, .help-text a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
        }
        
        .contact-admin a:hover, .help-text a:hover {
            text-decoration: underline;
        }
        
        /* Result message */
        .result-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .result-message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .result-message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .result-message .material-icons {
            margin-right: 8px;
            flex-shrink: 0;
        }
        
        /* Checkbox container */
        .checkbox-container {
            display: flex;
            margin: 20px 0;
            align-items: flex-start;
        }
        
        .checkbox-container input {
            margin-right: 10px;
            margin-top: 3px;
            width: 18px;
            height: 18px;
        }
        
        /* Features section */
        .features-section {
            margin-top: 20px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .features-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            width: 100%;
        }
        
        .feature-item {
            background-color: #f8f8f8;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .feature-icon .material-icons {
            color: #1976d2;
            font-size: 25px;
        }
        
        .feature-title {
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .feature-desc {
            font-size: 12px;
            color: #666;
        }
        
        /* Confetti animation */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f2d74e;
            opacity: 0;
            top: 0;
            animation: confetti 5s ease-in-out infinite;
        }
        
        .confetti.blue {
            background-color: #60c5ff;
        }
        
        .confetti.green {
            background-color: #82dd55;
        }
        
        .confetti.pink {
            background-color: #ec6cd3;
        }
        
        @keyframes confetti {
            0% {
                opacity: 1;
                transform: translateY(0) rotateZ(0deg);
            }
            100% {
                opacity: 0;
                transform: translateY(100vh) rotateZ(360deg);
            }
        }
        
        /* Debug info */
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- ส่วนหัว -->
    <div class="header">
        <?php if ($step < 4): ?>
        <button class="header-icon" onclick="history.back()">
            <span class="material-icons">arrow_back</span>
        </button>
        <?php else: ?>
        <div class="header-spacer"></div>
        <?php endif; ?>
        
        <h1>
            <?php 
                if ($step === 1) echo "เข้าสู่ระบบ";
                elseif ($step === 2) echo "ยืนยันตัวตน";
                elseif ($step === 3) echo "ข้อมูลส่วนตัว";
                else echo "ลงทะเบียนเสร็จสิ้น";
            ?>
        </h1>
        
        <div class="header-spacer"></div>
    </div>
    
    <div class="container">
        <!-- Step Indicator -->
        <div class="steps">
            <div class="step <?php echo ($step >= 1) ? 'completed' : ''; ?> <?php echo ($step === 1) ? 'active' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-title">เข้าสู่ระบบ</div>
            </div>
            <div class="step-line <?php echo ($step > 1) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step > 2) ? 'completed' : ''; ?> <?php echo ($step === 2) ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-title">ยืนยันตัวตน</div>
            </div>
            <div class="step-line <?php echo ($step > 2) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step > 3) ? 'completed' : ''; ?> <?php echo ($step === 3) ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-title">ข้อมูลส่วนตัว</div>
            </div>
            <div class="step-line <?php echo ($step > 3) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step === 4) ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- แสดงข้อความข้อผิดพลาด -->
        <?php if (!empty($error_message)): ?>
            <div class="result-message error">
                <span class="material-icons">error</span>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- แสดงข้อความสำเร็จ -->
        <?php if (!empty($success_message)): ?>
            <div class="result-message success">
                <span class="material-icons">check_circle</span>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- แสดงเนื้อหาตามขั้นตอน -->
        <?php if ($step === 1): ?>
            <!-- ขั้นตอนเข้าสู่ระบบ (กำหนดว่าได้เข้าสู่ระบบด้วย LINE แล้ว) -->
            <div class="card">
                <div class="card-title">เข้าสู่ระบบด้วย LINE</div>
                <div class="card-content">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <span class="material-icons" style="font-size: 64px; color: #06c755; margin-bottom: 10px;">check_circle</span>
                        <h3 style="margin-bottom: 10px;">เข้าสู่ระบบสำเร็จแล้ว</h3>
                        <p>ระบบตรวจพบว่านี่เป็นการเข้าใช้งานครั้งแรกของคุณ</p>
                        <p>กรุณาดำเนินการลงทะเบียนให้เสร็จสมบูรณ์</p>
                    </div>
                    
                    <p class="help-text" style="text-align: center;">ในขั้นตอนถัดไป คุณจะต้องยืนยันตัวตนด้วยเลขบัตรประชาชน และกรอกข้อมูลส่วนตัวเพิ่มเติม</p>
                    
                    <div style="margin-top: 30px;">
                        <button class="btn next" onclick="window.location.href='register.php?step=2'">
                            ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <!-- ขั้นตอนยืนยันตัวตนด้วยบัตรประชาชน -->
            <div class="card">
                <div class="card-title">ยืนยันตัวตนด้วยเลขบัตรประชาชน</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=2">
                        <div class="input-container">
                            <label class="input-label">เลขประจำตัวประชาชน 13 หลัก</label>
                            <input type="text" class="input-field" placeholder="X-XXXX-XXXXX-XX-X" maxlength="13" name="national_id" id="id-card-input" inputmode="numeric" value="<?php echo htmlspecialchars($national_id); ?>" required>
                            <div class="help-text">กรุณากรอกเลขบัตรประชาชน 13 หลัก (ไม่ต้องใส่ขีด)</div>
                        </div>
                        
                        <button type="submit" class="btn">
                            <span class="material-icons">search</span> ค้นหาข้อมูล
                        </button>
                        
                        <div class="contact-admin">
                            หากไม่พบข้อมูลของคุณ กรุณา<a href="#">ติดต่อเจ้าหน้าที่</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Navigation buttons -->
            <div class="page-navigation">
                <button class="nav-button back" onclick="window.location.href='register.php?step=1'">
                    <span class="material-icons">arrow_back</span> ย้อนกลับ
                </button>
            </div>
        <?php elseif ($step === 3): ?>
            <!-- ขั้นตอนกรอกข้อมูลส่วนตัว -->
            <div class="card">
                <div class="card-title">ตรวจสอบและแก้ไขข้อมูลส่วนตัว</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=3" enctype="multipart/form-data">
                        <!-- Profile Avatar -->
                        <div class="profile-avatar">
                            <?php if (!empty($profile_picture)): ?>
                                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="รูปโปรไฟล์">
                            <?php else: ?>
                                <?php
                                    $initial = '';
                                    if (!empty($first_name)) {
                                        $initial = mb_substr($first_name, 0, 1, 'UTF-8');
                                    } elseif (!empty($_SESSION['first_name'])) {
                                        $initial = mb_substr($_SESSION['first_name'], 0, 1, 'UTF-8');
                                    } else {
                                        $initial = 'ค';
                                    }
                                ?>
                                <span class="avatar-initial"><?php echo $initial; ?></span>
                            <?php endif; ?>
                            <div class="avatar-edit">
                                <span class="material-icons">photo_camera</span>
                                <input type="file" name="profile_picture" accept="image/*" id="profile-picture-input">
                            </div>
                        </div>
                        
                        <!-- Form Fields -->
                        <div class="form-group">
                            <label class="input-label">คำนำหน้า <span style="color: red;">*</span></label>
                            <select class="input-field" name="title" required>
                                <option value="">-- เลือกคำนำหน้า --</option>
                                <option value="นาย" <?php echo ($title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo ($title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo ($title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                <option value="ดร." <?php echo ($title === 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                                <option value="ผศ." <?php echo ($title === 'ผศ.') ? 'selected' : ''; ?>>ผศ.</option>
                                <option value="รศ." <?php echo ($title === 'รศ.') ? 'selected' : ''; ?>>รศ.</option>
                                <option value="ศ." <?php echo ($title === 'ศ.') ? 'selected' : ''; ?>>ศ.</option>
                                <option value="อื่นๆ" <?php echo ($title === 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">ชื่อ <span style="color: red;">*</span></label>
                            <input type="text" class="input-field" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">นามสกุล <span style="color: red;">*</span></label>
                            <input type="text" class="input-field" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
                            <input type="tel" class="input-field" name="phone_number" id="phone" inputmode="tel" pattern="[0-9]{10}" maxlength="10" placeholder="0801234567" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                            <div class="help-text">กรอกเบอร์โทรศัพท์ 10 หลัก (ไม่ต้องใส่ขีด)</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">อีเมล</label>
                            <input type="email" class="input-field" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="example@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">แผนก/สาขาวิชา <span style="color: red;">*</span></label>
                            <select class="input-field" name="department" required>
                                <option value="">-- เลือกแผนก/สาขาวิชา --</option>
                                <option value="ช่างยนต์" <?php echo ($department === 'ช่างยนต์') ? 'selected' : ''; ?>>สาขาวิชาช่างยนต์</option>
                                <option value="ช่างกลโรงงาน" <?php echo ($department === 'ช่างกลโรงงาน') ? 'selected' : ''; ?>>สาขาวิชาช่างกลโรงงาน</option>
                                <option value="ช่างไฟฟ้ากำลัง" <?php echo ($department === 'ช่างไฟฟ้ากำลัง') ? 'selected' : ''; ?>>สาขาวิชาช่างไฟฟ้ากำลัง</option>
                                <option value="ช่างอิเล็กทรอนิกส์" <?php echo ($department === 'ช่างอิเล็กทรอนิกส์') ? 'selected' : ''; ?>>สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                                <option value="การบัญชี" <?php echo ($department === 'การบัญชี') ? 'selected' : ''; ?>>สาขาวิชาการบัญชี</option>
                                <option value="เทคโนโลยีสารสนเทศ" <?php echo ($department === 'เทคโนโลยีสารสนเทศ') ? 'selected' : ''; ?>>สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                                <option value="การโรงแรม" <?php echo ($department === 'การโรงแรม') ? 'selected' : ''; ?>>สาขาวิชาการโรงแรม</option>
                                <option value="ช่างเชื่อมโลหะ" <?php echo ($department === 'ช่างเชื่อมโลหะ') ? 'selected' : ''; ?>>สาขาวิชาช่างเชื่อมโลหะ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="input-label">ตำแหน่ง</label>
                            <input type="text" class="input-field" name="position" value="<?php echo htmlspecialchars($position); ?>" placeholder="ครูที่ปรึกษา">
                        </div>
                        
                        <!-- Privacy Consent -->
                        <div class="checkbox-container">
                            <input type="checkbox" name="gdpr_consent" id="privacy-consent" required>
                            <label for="privacy-consent">
                                ข้าพเจ้ายินยอมให้เก็บข้อมูลส่วนบุคคลตาม <a href="#" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a> ของวิทยาลัยการอาชีพปราสาท เพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์
                            </label>
                        </div>
                        
                        <!-- Debug Info -->
                        <div class="debug-info">
                            <p>National ID: <?php echo htmlspecialchars($national_id); ?></p>
                            <p>ข้อมูลจาก session:</p>
                            <ul>
                                <li>user_id: <?php echo $_SESSION['user_id'] ?? 'ไม่มี'; ?></li>
                                <li>role: <?php echo $_SESSION['role'] ?? 'ไม่มี'; ?></li>
                                <li>logged_in: <?php echo isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'ไม่มี'; ?></li>
                            </ul>
                        </div>
                        
                        <!-- Navigation buttons -->
                        <div class="page-navigation">
                            <button type="button" class="nav-button back" onclick="window.location.href='register.php?step=2'">
                                <span class="material-icons">arrow_back</span> ย้อนกลับ
                            </button>
                            <button type="submit" class="nav-button" id="save-button">
                                บันทึกข้อมูล <span class="material-icons">save</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 4): ?>
            <!-- ขั้นตอนลงทะเบียนเสร็จสิ้น -->
            <div class="card success-card">
                <!-- Success Icon -->
                <div class="success-icon">
                    <span class="material-icons">check</span>
                </div>
                
                <!-- Success Message -->
                <div class="success-message">
                    <h2>ลงทะเบียนเสร็จสิ้น</h2>
                    <p>คุณได้ลงทะเบียนเข้าใช้งานระบบ Teacher-Prasat เรียบร้อยแล้ว</p>
                    <p>ตอนนี้คุณสามารถเริ่มใช้งานระบบเช็คชื่อเข้าแถวออนไลน์ได้ทันที</p>
                </div>
                
                <!-- Features Section -->
                <div class="features-section">
                    <div class="features-title">คุณสามารถทำอะไรได้บ้าง?</div>
                    <div class="feature-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">pin</span>
                            </div>
                            <div class="feature-title">สร้างรหัส PIN</div>
                            <div class="feature-desc">สร้างรหัส PIN 4 หลักให้นักเรียนเช็คชื่อ</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">qr_code_scanner</span>
                            </div>
                            <div class="feature-title">สแกน QR</div>
                            <div class="feature-desc">สแกน QR Code ของนักเรียนเพื่อเช็คชื่อ</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">assessment</span>
                            </div>
                            <div class="feature-title">ดูรายงาน</div>
                            <div class="feature-desc">ตรวจสอบสถิติการเข้าแถวของนักเรียน</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">notifications</span>
                            </div>
                            <div class="feature-title">แจ้งเตือน</div>
                            <div class="feature-desc">ส่งการแจ้งเตือนถึงผู้ปกครอง</div>
                        </div>
                    </div>
                </div>
                
                <!-- Start Button -->
                <button class="btn success" onclick="window.location.href='dashboard.php'">
                    เริ่มใช้งานระบบ
                    <span class="material-icons">arrow_forward</span>
                </button>
            </div>
            
            <!-- Confetti Elements -->
            <div id="confetti-container"></div>
        <?php endif; ?>
    </div>
    
    <script>
        // สำหรับหน้ายืนยันตัวตน (Step 2)
        if (document.getElementById('id-card-input')) {
            document.getElementById('id-card-input').addEventListener('input', function() {
                // รับค่าปัจจุบันและลบอักขระที่ไม่ใช่ตัวเลข
                let value = this.value.replace(/\D/g, '');
                
                // จำกัดความยาวไม่เกิน 13 หลัก
                if (value.length > 13) {
                    value = value.slice(0, 13);
                }
                
                // อัปเดตค่าในช่องข้อมูล
                this.value = value;
            });
        }
        
        // สำหรับหน้าข้อมูลส่วนตัว (Step 3)
        if (document.getElementById('profile-picture-input')) {
            document.getElementById('profile-picture-input').addEventListener('change', function(event) {
                const file = event.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const profileAvatar = document.querySelector('.profile-avatar');
                        
                        // ลบตัวอักษรแรกออก
                        const initial = profileAvatar.querySelector('.avatar-initial');
                        if (initial) {
                            initial.style.display = 'none';
                        }
                        
                        // เช็คว่ามีรูปอยู่แล้วหรือไม่
                        let img = profileAvatar.querySelector('img');
                        
                        if (!img) {
                            img = document.createElement('img');
                            profileAvatar.insertBefore(img, profileAvatar.firstChild);
                        }
                        
                        img.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // สำหรับหน้าเสร็จสิ้น (Step 4)
        if (document.getElementById('confetti-container')) {
            // สร้างเอฟเฟกต์ confetti เพื่อความสวยงาม
            function createConfetti() {
                const confettiContainer = document.getElementById('confetti-container');
                const colors = ['', 'blue', 'green', 'pink'];
                const totalConfetti = 50;
                
                for (let i = 0; i < totalConfetti; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti ' + colors[Math.floor(Math.random() * colors.length)];
                    
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.animationDelay = Math.random() * 5 + 's';
                    confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                    
                    // กำหนดรูปร่างของ confetti
                    if (Math.random() > 0.5) {
                        confetti.style.borderRadius = '50%';
                    } else if (Math.random() > 0.5) {
                        confetti.style.width = '6px';
                        confetti.style.height = '16px';
                    }
                    
                    confettiContainer.appendChild(confetti);
                }
            }
            
            // เรียกใช้ฟังก์ชันสร้าง confetti เมื่อหน้าเว็บโหลดเสร็จ
            window.addEventListener('load', function() {
                createConfetti();
            });
        }
        
        // แสดงนโยบายความเป็นส่วนตัว
        function showPrivacyPolicy() {
            alert('นโยบายความเป็นส่วนตัวของวิทยาลัยการอาชีพปราสาท\n\nวิทยาลัยการอาชีพปราสาทจะเก็บรวบรวมข้อมูลส่วนบุคคลของครูที่ปรึกษาและนักเรียนเพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์เท่านั้น โดยจะไม่เปิดเผยข้อมูลต่อบุคคลที่สาม ยกเว้นในกรณีที่จำเป็นต้องปฏิบัติตามกฎหมาย');
        }
        
        // ตรวจสอบความถูกต้องของเบอร์โทรศัพท์แบบ Real-time
        if (document.getElementById('phone')) {
            document.getElementById('phone').addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 10);
            });
        }
    </script>
</body>
</html>
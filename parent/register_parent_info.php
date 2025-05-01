<?php
/**
 * parent/register_parent_info.php
 * หน้ากรอกข้อมูลผู้ปกครองในกระบวนการลงทะเบียน (ขั้นตอนที่ 2)
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอินและขั้นตอนการลงทะเบียน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบขั้นตอนการลงทะเบียน
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
    // ย้อนกลับไปยังขั้นตอนที่ 1
    header('Location: register_select_students.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, phone_number, email, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// จัดการการส่งฟอร์ม
$errors = [];

if (isset($_POST['submit'])) {
    // ตรวจสอบข้อมูลที่ส่งมา
    $title = $_POST['title'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // ตรวจสอบความถูกต้อง
    if (empty($title)) {
        $errors[] = "กรุณาเลือกคำนำหน้า";
    }
    
    if (empty($relationship)) {
        $errors[] = "กรุณาเลือกความสัมพันธ์กับนักเรียน";
    }
    
    if (empty($first_name)) {
        $errors[] = "กรุณากรอกชื่อจริง";
    }
    
    if (empty($last_name)) {
        $errors[] = "กรุณากรอกนามสกุล";
    }
    
    if (empty($phone_number)) {
        $errors[] = "กรุณากรอกเบอร์โทรศัพท์";
    } elseif (!preg_match('/^[0-9]{9,10}$/', $phone_number)) {
        $errors[] = "เบอร์โทรศัพท์ไม่ถูกต้อง";
    }
    
    // ถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        // อัพเดทข้อมูลผู้ใช้
        $stmt = $conn->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $title, $first_name, $last_name, $phone_number, $email, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // เก็บข้อมูลสำหรับใช้ในขั้นตอนต่อไป
        $_SESSION['parent_info'] = [
            'title' => $title,
            'relationship' => $relationship,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number,
            'email' => $email
        ];
        
        // ไปยังขั้นตอนถัดไป
        $_SESSION['registration_step'] = 3;
        header('Location: register_confirm.php');
        exit;
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ตั้งค่าหัวข้อหน้า
$page_title = 'SADD-Prasat - ข้อมูลผู้ปกครอง';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* ตั้งค่าพื้นฐาน */
        :root {
            --primary-color: #8e24aa; /* สีม่วงสำหรับ SADD-Prasat (ผู้ปกครอง) */
            --primary-color-dark: #5c007a;
            --primary-color-light: #f3e5f5;
            --text-dark: #333;
            --text-light: #666;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --card-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* ส่วนหัว */
        .header {
            background: linear-gradient(135deg, #8e24aa 0%, #6a1b9a 100%);
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }
        
        .header-icon {
            font-size: 24px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .container {
            max-width: 600px;
            margin: 70px auto 30px;
            padding: 15px;
        }
        
        /* ตัวแสดงขั้นตอน */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 15%;
            right: 15%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #666;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .step.active .step-number {
            background-color: #8e24aa;
            color: white;
        }
        
        .step.completed .step-number {
            background-color: #4caf50;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .step.active .step-label {
            color: #8e24aa;
            font-weight: 500;
        }
        
        .step.completed .step-label {
            color: #4caf50;
        }
        
        /* ส่วนข้อมูล */
        .form-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #8e24aa;
        }
        
        .form-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* แสดงข้อผิดพลาด */
        .errors-container {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #d32f2f;
        }
        
        .errors-list {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }
        
        .errors-list li {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .errors-list li:last-child {
            margin-bottom: 0;
        }
        
        /* รูปแบบฟอร์ม */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-column {
            flex: 1;
        }
        
        /* ปุ่ม */
        .action-button {
            background-color: #8e24aa;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(142, 36, 170, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(142, 36, 170, 0.4);
        }
        
        .back-button {
            background-color: #f5f5f5;
            color: #666;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        
        .back-button:hover {
            background-color: #e0e0e0;
        }
        
        /* ข้อความช่วยเหลือ */
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* ดอกจัน */
        .required::after {
            content: ' *';
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="register_select_students.php" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>ข้อมูลผู้ปกครอง</h1>
        <div class="header-icon">
            <span class="material-icons">help_outline</span>
        </div>
    </div>

    <div class="container">
        <!-- ตัวแสดงขั้นตอน -->
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-label">เลือกนักเรียน</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-label">ข้อมูลผู้ปกครอง</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">ยืนยันข้อมูล</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-label">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- ฟอร์มข้อมูลผู้ปกครอง -->
        <div class="form-card">
            <div class="form-title">กรอกข้อมูลผู้ปกครอง</div>
            <div class="form-description">
                กรุณากรอกข้อมูลของท่านให้ครบถ้วน เพื่อประโยชน์ในการติดต่อและรับการแจ้งเตือนจากทางโรงเรียน
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="errors-container">
                    <ul class="errors-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label required">คำนำหน้า</label>
                            <select name="title" class="form-select">
                                <option value="">-- เลือกคำนำหน้า --</option>
                                <option value="นาย" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นาย') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นาง') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นางสาว') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                <option value="ดร." <?php echo (isset($_POST['title']) && $_POST['title'] == 'ดร.') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                                <option value="ผศ." <?php echo (isset($_POST['title']) && $_POST['title'] == 'ผศ.') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'ผศ.') ? 'selected' : ''; ?>>ผศ.</option>
                                <option value="รศ." <?php echo (isset($_POST['title']) && $_POST['title'] == 'รศ.') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'รศ.') ? 'selected' : ''; ?>>รศ.</option>
                                <option value="ศ." <?php echo (isset($_POST['title']) && $_POST['title'] == 'ศ.') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'ศ.') ? 'selected' : ''; ?>>ศ.</option>
                                <option value="อื่นๆ" <?php echo (isset($_POST['title']) && $_POST['title'] == 'อื่นๆ') || (!isset($_POST['title']) && isset($user_data['title']) && $user_data['title'] == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label required">ความสัมพันธ์กับนักเรียน</label>
                            <select name="relationship" class="form-select">
                                <option value="">-- เลือกความสัมพันธ์ --</option>
                                <option value="พ่อ" <?php echo isset($_POST['relationship']) && $_POST['relationship'] == 'พ่อ' ? 'selected' : ''; ?>>พ่อ</option>
                                <option value="แม่" <?php echo isset($_POST['relationship']) && $_POST['relationship'] == 'แม่' ? 'selected' : ''; ?>>แม่</option>
                                <option value="ผู้ปกครอง" <?php echo isset($_POST['relationship']) && $_POST['relationship'] == 'ผู้ปกครอง' ? 'selected' : ''; ?>>ผู้ปกครอง</option>
                                <option value="ญาติ" <?php echo isset($_POST['relationship']) && $_POST['relationship'] == 'ญาติ' ? 'selected' : ''; ?>>ญาติ</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label required">ชื่อจริง</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : (isset($user_data['first_name']) ? htmlspecialchars($user_data['first_name']) : ''); ?>">
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label required">นามสกุล</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : (isset($user_data['last_name']) ? htmlspecialchars($user_data['last_name']) : ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone_number" class="form-control" placeholder="เช่น 0812345678" value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : (isset($user_data['phone_number']) ? htmlspecialchars($user_data['phone_number']) : ''); ?>">
                    <div class="help-text">เบอร์โทรศัพท์ที่สามารถติดต่อได้ในกรณีฉุกเฉิน</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" class="form-control" placeholder="example@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''); ?>">
                    <div class="help-text">อีเมลสำหรับรับการแจ้งเตือนจากทางโรงเรียน (ไม่บังคับ)</div>
                </div>
                
                <button type="submit" name="submit" class="action-button">
                    ดำเนินการต่อ
                </button>
                
                <a href="register_select_students.php" class="back-button" style="display: block; text-align: center; text-decoration: none;">
                    ย้อนกลับ
                </a>
            </form>
        </div>
    </div>
</body>
</html>
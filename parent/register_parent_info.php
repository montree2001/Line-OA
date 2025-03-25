<?php
/**
 * parent/register_parent_info.php
 * หน้ากรอกข้อมูลผู้ปกครองในการลงทะเบียน (ขั้นตอนที่ 3)
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอินและขั้นตอนการลงทะเบียน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าขั้นตอนถูกต้องหรือไม่ (ควรผ่านขั้นตอนที่ 1 มาก่อน)
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
    // ถ้ายังไม่ได้ผ่านขั้นตอนที่ 1 ให้กลับไปขั้นตอนแรก
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

// ดึงข้อมูลผู้ใช้จาก users table
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, phone_number, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// สร้างตัวแปรเก็บค่าฟอร์ม
$title = '';
$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$phone_number = $user_data['phone_number'] ?? '';
$email = $user_data['email'] ?? '';
$relationship = '';
$error = '';

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // รับค่าจากฟอร์ม
    $title = $_POST['title'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($first_name) || empty($last_name) || empty($phone_number) || empty($relationship)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // บันทึกข้อมูลลงใน session
        $_SESSION['parent_info'] = [
            'title' => $title,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number,
            'email' => $email,
            'relationship' => $relationship
        ];
        
        // อัปเดตข้อมูลผู้ใช้
        $stmt = $conn->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $title, $first_name, $last_name, $phone_number, $email, $user_id);
        $stmt->execute();
        
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
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
        
        /* คำแนะนำ */
        .instruction-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .instruction-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #8e24aa;
        }
        
        .instruction-text {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        /* ฟอร์มกรอกข้อมูล */
        .form-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .form-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
        }
        
        .form-column {
            flex: 1;
        }
        
        .form-note {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* ปุ่มดำเนินการต่อ */
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
        
        /* ข้อมูลเพิ่มเติม */
        .info-text {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        
        .info-text a {
            color: #8e24aa;
            text-decoration: none;
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
        
        <!-- คำแนะนำ -->
        <div class="instruction-card">
            <div class="instruction-title">กรอกข้อมูลผู้ปกครอง</div>
            <div class="instruction-text">
                กรุณากรอกข้อมูลส่วนตัวของท่านเพื่อใช้ในการติดต่อสื่อสารและแจ้งข้อมูลการเข้าแถวของนักเรียน
            </div>
        </div>
        
        <!-- ฟอร์มกรอกข้อมูล -->
        <div class="form-card">
            <h2 class="form-title">ข้อมูลส่วนตัว</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form id="parentInfoForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้า</label>
                            <select class="form-select" name="title" required>
                                <option value="" disabled <?php echo empty($title) ? 'selected' : ''; ?>>เลือกคำนำหน้า</option>
                                <option value="นาย" <?php echo ($title == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo ($title == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo ($title == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                <option value="ดร." <?php echo ($title == 'ดร.') ? 'selected' : ''; ?>>ดร.</option>
                                <option value="อื่นๆ" <?php echo ($title == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">ความสัมพันธ์กับนักเรียน</label>
                            <select class="form-select" name="relationship" required>
                                <option value="" disabled <?php echo empty($relationship) ? 'selected' : ''; ?>>เลือกความสัมพันธ์</option>
                                <option value="พ่อ" <?php echo ($relationship == 'พ่อ') ? 'selected' : ''; ?>>พ่อ</option>
                                <option value="แม่" <?php echo ($relationship == 'แม่') ? 'selected' : ''; ?>>แม่</option>
                                <option value="ปู่" <?php echo ($relationship == 'ปู่') ? 'selected' : ''; ?>>ปู่</option>
                                <option value="ย่า" <?php echo ($relationship == 'ย่า') ? 'selected' : ''; ?>>ย่า</option>
                                <option value="ตา" <?php echo ($relationship == 'ตา') ? 'selected' : ''; ?>>ตา</option>
                                <option value="ยาย" <?php echo ($relationship == 'ยาย') ? 'selected' : ''; ?>>ยาย</option>
                                <option value="ลุง" <?php echo ($relationship == 'ลุง') ? 'selected' : ''; ?>>ลุง</option>
                                <option value="ป้า" <?php echo ($relationship == 'ป้า') ? 'selected' : ''; ?>>ป้า</option>
                                <option value="น้า" <?php echo ($relationship == 'น้า') ? 'selected' : ''; ?>>น้า</option>
                                <option value="อา" <?php echo ($relationship == 'อา') ? 'selected' : ''; ?>>อา</option>
                                <option value="พี่" <?php echo ($relationship == 'พี่') ? 'selected' : ''; ?>>พี่</option>
                                <option value="ผู้ปกครอง" <?php echo ($relationship == 'ผู้ปกครอง') ? 'selected' : ''; ?>>ผู้ปกครอง</option>
                                <option value="อื่นๆ" <?php echo ($relationship == 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" class="form-input" name="first_name" placeholder="กรอกชื่อจริง" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" class="form-input" name="last_name" placeholder="กรอกนามสกุล" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-input" name="phone_number" placeholder="กรอกเบอร์โทรศัพท์" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    <div class="form-note">* กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (ไม่ต้องใส่ขีด)</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">อีเมล (ถ้ามี)</label>
                    <input type="email" class="form-input" name="email" placeholder="กรอกอีเมล" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="form-note">* ไม่จำเป็นต้องกรอก หากต้องการรับการแจ้งเตือนทางอีเมลด้วย</div>
                </div>
                
                <!-- ปุ่มดำเนินการต่อ -->
                <button type="submit" name="submit" class="action-button">
                    ดำเนินการต่อ
                </button>
            </form>
        </div>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>ข้อมูลของท่านจะถูกเก็บเป็นความลับตาม <a href="#">นโยบายความเป็นส่วนตัว</a> ของโรงเรียน</p>
        </div>
    </div>

    <script>
        // ตรวจสอบฟอร์มก่อนส่ง
        document.querySelector('.action-button').addEventListener('click', function(e) {
            const form = document.getElementById('parentInfoForm');
            if (!form.checkValidity()) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                form.reportValidity();
            }
        });
    </script>
</body>
</html>
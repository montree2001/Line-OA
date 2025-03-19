<?php
session_start();
require_once '../config/db_config.php';

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

// ตรวจสอบว่ามีข้อมูลบัญชีครูแล้วหรือไม่
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีข้อมูลครูแล้วหรือไม่
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // มีข้อมูลครูแล้ว นำไปที่หน้า dashboard
    header('Location: dashboard.php');
    exit;
}

// ถ้ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $title = $_POST['title'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $phone_number = $_POST['phone_number'];
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($first_name) || empty($last_name) || empty($department)) {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // อัปเดตข้อมูลผู้ใช้
        $update_user = $conn->prepare("UPDATE users SET title = ?, first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?");
        $update_user->bind_param("ssssi", $title, $first_name, $last_name, $phone_number, $user_id);
        
        if ($update_user->execute()) {
            // เพิ่มข้อมูลครู
            $insert_teacher = $conn->prepare("INSERT INTO teachers (user_id, department) VALUES (?, ?)");
            $insert_teacher->bind_param("is", $user_id, $department);
            
            if ($insert_teacher->execute()) {
                // นำไปที่หน้า dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = "ไม่สามารถบันทึกข้อมูลครูได้: " . $conn->error;
            }
            
            $insert_teacher->close();
        } else {
            $error_message = "ไม่สามารถบันทึกข้อมูลผู้ใช้ได้: " . $conn->error;
        }
        
        $update_user->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนครูที่ปรึกษา - STP-Prasat</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <style>
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
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .logo {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1976d2 0%, #0d47a1 100%);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
        }
        
        .form-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1976d2;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #0d47a1;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .checkbox-group {
            margin-top: 20px;
            margin-bottom: 30px;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SP</div>
            <h1>ลงทะเบียนครูที่ปรึกษา</h1>
            <p>กรุณากรอกข้อมูลของคุณเพื่อเริ่มใช้งานระบบ</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">คำนำหน้า <span style="color: red;">*</span></label>
                        <select id="title" name="title" required>
                            <option value="">-- เลือกคำนำหน้า --</option>
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                            <option value="อาจารย์">อาจารย์</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">ชื่อ <span style="color: red;">*</span></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">นามสกุล <span style="color: red;">*</span></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="department">สาขาวิชา <span style="color: red;">*</span></label>
                    <select id="department" name="department" required>
                        <option value="">-- เลือกสาขาวิชา --</option>
                        <option value="ช่างยนต์">สาขาวิชาช่างยนต์</option>
                        <option value="ช่างกลโรงงาน">สาขาวิชาช่างกลโรงงาน</option>
                        <option value="ช่างไฟฟ้ากำลัง">สาขาวิชาช่างไฟฟ้ากำลัง</option>
                        <option value="ช่างอิเล็กทรอนิกส์">สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                        <option value="การบัญชี">สาขาวิชาการบัญชี</option>
                        <option value="เทคโนโลยีสารสนเทศ">สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                        <option value="การโรงแรม">สาขาวิชาการโรงแรม</option>
                        <option value="ช่างเชื่อมโลหะ">สาขาวิชาช่างเชื่อมโลหะ</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone_number">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" pattern="[0-9]{10}" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" maxlength="10" required>
                </div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="accept_terms" required>
                        ข้าพเจ้ายินยอมให้วิทยาลัยการอาชีพปราสาทเก็บข้อมูลส่วนบุคคลเพื่อใช้ในระบบเช็คชื่อเข้าแถว
                    </label>
                </div>
                
                <button type="submit" class="btn">บันทึกข้อมูล</button>
            </form>
        </div>
        
        <p class="info-text">หากพบปัญหาในการลงทะเบียน กรุณาติดต่อเจ้าหน้าที่</p>
    </div>
    
    <script>
        // ตรวจสอบเบอร์โทรศัพท์เมื่อส่งฟอร์ม
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneNumber = document.getElementById('phone_number').value;
            
            // ตรวจสอบเบอร์โทรศัพท์ 10 หลัก
            if (!/^\d{10}$/.test(phoneNumber)) {
                alert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (ตัวเลข 10 หลัก)');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
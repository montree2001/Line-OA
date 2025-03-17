<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินอยู่แล้วหรือไม่
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    // ถ้าล็อกอินแล้ว ให้ redirect ไปยังหน้าแดชบอร์ด
    header('Location: admin/index.php');
    exit;
}

// ตั้งค่าตัวแปรเริ่มต้น
$error_message = '';

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ในระบบจริง ควรใช้การเชื่อมต่อฐานข้อมูลและการเข้ารหัสรหัสผ่าน
    // นี่เป็นเพียงตัวอย่างการตรวจสอบแบบง่ายๆ
    $valid_users = [
        'admin' => [
            'password' => 'prasat2025', // ควรเก็บในรูปแบบแฮช
            'name' => 'จารุวรรณ บุญมี',
            'role' => 'เจ้าหน้าที่กิจการนักเรียน'
        ],
        'teacher' => [
            'password' => 'teacher2025',
            'name' => 'อิศรา สุขใจ',
            'role' => 'ครูที่ปรึกษา'
        ]
    ];

    // ตรวจสอบชื่อผู้ใช้และรหัสผ่าน
    if (isset($valid_users[$username]) && $valid_users[$username]['password'] === $password) {
        // สร้าง session สำหรับผู้ใช้
        $_SESSION['user_id'] = md5($username); // ใช้ md5 เพียงเพื่อตัวอย่าง ไม่แนะนำในการใช้งานจริง
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = ($username === 'admin') ? 'admin' : 'teacher';
        $_SESSION['user_name'] = $valid_users[$username]['name'];
        $_SESSION['user_role'] = $valid_users[$username]['role'];

        // Redirect ไปยังหน้าแดชบอร์ด
        header('Location: admin/index.php');
        exit;
    } else {
        // ถ้าชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง
        $error_message = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - STUDENT-Prasat</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #06c755;
            --secondary-color: #1976d2;
            --text-color: #333;
            --bg-color: #f5f5f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            text-align: center;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .login-logo img {
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }

        .login-logo h1 {
            font-size: 24px;
            color: var(--primary-color);
        }

        .login-form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(6, 199, 85, 0.2);
        }

        .login-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #05a647;
        }

        .error-message {
            color: #d32f2f;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .line-login {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .line-login-btn {
            background-color: #06c755;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            font-weight: 500;
            cursor: pointer;
        }

        .line-login-btn img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="logo.png" alt="โลโก้ STUDENT-Prasat">
            <h1>STUDENT-Prasat</h1>
        </div>

        <form method="post" class="login-form" action="">
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="กรอกชื่อผู้ใช้"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="กรอกรหัสผ่าน"
                    required
                >
            </div>

            <button type="submit" class="login-btn">เข้าสู่ระบบ</button>

            <div class="line-login">
                <button type="button" class="line-login-btn" onclick="loginWithLine()">
                    <img src="line.png" alt="LINE Login">
                    เข้าสู่ระบบด้วย LINE
                </button>
            </div>
        </form>

        <div class="footer">
            <p>©2025 STUDENT-Prasat ระบบบริหารการเข้าแถว</p>
        </div>
    </div>

    <script>
        function loginWithLine() {
            // ในอนาคต จะเชื่อมต่อกับ LINE Login API
            alert('กำลังเชื่อมต่อกับ LINE Login');
        }
    </script>
</body>
</html>
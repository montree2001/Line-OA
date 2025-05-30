<?php
session_start();

// โหลดการเชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

// ตรวจสอบว่ามีการล็อกอินอยู่แล้วหรือไม่
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_md5 = md5($password); // แปลงรหัสผ่านเป็น MD5

    // ได้ connection จาก db_connect.php
    $conn = getDB();

    // ตรวจสอบข้อมูลจากตาราง users
    $stmt = $conn->prepare("SELECT * FROM users WHERE line_id = :username AND role IN ('admin', 'teacher')");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    // เปรียบเทียบ MD5 hash
    if ($user && $user['password_hash'] === $password_md5) {
        // ล็อกอินสำเร็จ
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['line_id'];
        $_SESSION['user_type'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'] === 'admin' ? 'เจ้าหน้าที่' : 'ครูที่ปรึกษา';

        header('Location: admin/index.php');
        exit;
    } else {
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
                    required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="กรอกรหัสผ่าน"
                    required>
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
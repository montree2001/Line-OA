<?php
// เริ่มเซสชัน
session_start();

// ลบข้อมูลผู้ใช้ออกจากเซสชัน
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['user_role']);
unset($_SESSION['user_name']);
unset($_SESSION['is_logged_in']);

// ลบข้อมูลทั้งหมดในเซสชัน
$_SESSION = array();

// ทำลายคุกกี้เซสชัน
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลายเซสชัน
session_destroy();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ - น้องสัตบรรณ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #28a745;
            --primary-dark: #218838;
            --secondary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('assets/images/bg-pattern.png');
            background-size: cover;
            background-position: center;
        }
        
        .logout-container {
            max-width: 500px;
            width: 100%;
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .logout-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .logout-container h2 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .logout-container p {
            color: var(--secondary-color);
            margin-bottom: 30px;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .timer {
            font-size: 1.2rem;
            font-weight: 500;
            margin-top: 20px;
            color: var(--primary-color);
        }
        
        @media (max-width: 576px) {
            .logout-container {
                margin: 15px;
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>ออกจากระบบสำเร็จ</h2>
        <p>คุณได้ออกจากระบบน้องสัตบรรณเรียบร้อยแล้ว</p>
        <a href="../login.php" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบอีกครั้ง
        </a>
        <div class="timer">
            <span id="countdown">5</span> วินาที
        </div>
    </div>
    
    <script>
        // นับถอยหลังและเปลี่ยนเส้นทางไปยังหน้า login
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = '../login.php';
            }
        }, 1000);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>น้องสัตบรรณ - ระบบดูแลผู้เรียน</title>
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
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            overflow: hidden;
        }
        
        .login-container {
            display: flex;
            flex-direction: row;
            max-width: 900px;
            width: 100%;
            height: 550px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-banner {
            flex: 1;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            animation: pulse 4s infinite;
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
            animation-delay: 2s;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1); opacity: 0.3; }
        }
        
        .login-banner h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
        }
        
        .login-banner p {
            font-size: 1.2rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .login-banner img {
            max-width: 200px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }
        
        .login-form-container {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .login-header p {
            color: var(--secondary-color);
        }
        
        .login-form .form-floating {
            margin-bottom: 20px;
        }
        
        .login-form .form-control {
            border-radius: 8px;
            height: 50px;
            font-size: 16px;
            border: 1px solid #ddd;
        }
        
        .login-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        .login-form .btn-login {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .login-form .btn-login:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            cursor: pointer;
        }
        
        .password-toggle {
            border-left: none;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-width: 100%;
                margin: 15px;
            }
            
            .login-banner {
                padding: 20px;
                min-height: 200px;
            }
            
            .login-banner h1 {
                font-size: 2rem;
            }
            
            .login-banner img {
                max-width: 120px;
                margin-bottom: 15px;
            }
            
            .login-form-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-banner">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <img src="assets/images/logo.png" alt="โลโก้น้องสัตบรรณ" onerror="this.src='logo.png'">
            <h1>น้องสัตบรรณ</h1>
            <p>ระบบดูแลผู้เรียน วิทยาลัยการอาชีพปราสาท</p>
        </div>
        
        <div class="login-form-container">
            <div class="login-header">
                <h2>เข้าสู่ระบบผู้ดูแล</h2>
                <p>กรุณาลงชื่อเข้าใช้เพื่อจัดการระบบ</p>
            </div>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="login_process.php">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" required autofocus>
                    <label for="username"><i class="fas fa-user me-2"></i> ชื่อผู้ใช้</label>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                    <label for="password"><i class="fas fa-lock me-2"></i> รหัสผ่าน</label>
                </div>
                
                <div class="d-flex align-items-center justify-content-between mt-3 mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">จดจำการเข้าสู่ระบบ</label>
                    </div>
                    <a href="#" class="text-decoration-none" style="color: var(--primary-color);">ลืมรหัสผ่าน?</a>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> วิทยาลัยการอาชีพปราสาท - ระบบน้องสัตบรรณ v1.0</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.password-toggle');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const passwordInput = document.getElementById('password');
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
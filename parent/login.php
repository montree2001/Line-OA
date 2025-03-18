<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADD-Prasat - เข้าสู่ระบบผู้ปกครอง</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ตั้งค่าพื้นฐาน */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #8e24aa 0%, #6a1b9a 100%);
            color: #333;
            font-size: 16px;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            width: 90%;
            max-width: 360px;
            background-color: white;
            border-radius: 15px;
            padding: 30px 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .logo {
            margin-bottom: 20px;
        }
        
        .logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        
        .app-title {
            font-size: 24px;
            font-weight: 600;
            color: #8e24aa;
            margin-bottom: 10px;
        }
        
        .app-description {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .login-button {
            background-color: #06C755;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(6, 199, 85, 0.3);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(6, 199, 85, 0.4);
        }
        
        .login-button img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        
        .login-info {
            font-size: 12px;
            color: #999;
            margin-top: 30px;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
        }
        
        .footer a {
            color: #8e24aa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <!-- เปลี่ยนเป็นโลโก้ของโรงเรียน -->
            <img src="assets/images/school-logo.png" alt="โลโก้โรงเรียนปราสาทวิทยาคม" onerror="this.src='https://via.placeholder.com/120x120?text=School+Logo'">
        </div>
        
        <h1 class="app-title">SADD-Prasat</h1>
        <p class="app-description">ระบบติดตามการเข้าแถวสำหรับผู้ปกครอง</p>
        
        <a href="student-selection.html" class="login-button">
            <img src="assets/images/line-icon.png" alt="LINE" onerror="this.src='https://via.placeholder.com/24x24?text=LINE'">
            เข้าสู่ระบบด้วย LINE
        </a>
        
        <p class="login-info">การเข้าสู่ระบบนี้จะใช้บัญชี LINE ของท่านเพื่อยืนยันตัวตน<br>และเชื่อมต่อกับระบบแจ้งเตือนอัตโนมัติ</p>
        
        <div class="footer">
            <p>&copy; 2025 โรงเรียนปราสาทวิทยาคม</p>
            <p><a href="#">นโยบายความเป็นส่วนตัว</a> | <a href="#">ติดต่อเรา</a></p>
        </div>
    </div>
</body>
</html>
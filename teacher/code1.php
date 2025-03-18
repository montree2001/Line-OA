<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - เข้าสู่ระบบครั้งแรก</title>
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
            justify-content: center;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 480px;
            margin: 70px auto 20px;
            padding: 15px;
            min-height: calc(100vh - 90px);
        }
        
        /* Logo and Welcome section */
        .welcome-image {
            width: 100%;
            display: flex;
            justify-content: center;
            margin: 20px 0 30px;
        }
        
        .welcome-image img {
            width: 180px;
            height: 180px;
            object-fit: contain;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-text h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1976d2;
        }
        
        .welcome-text p {
            color: #666;
            font-size: 16px;
        }
        
        /* LINE Login Button */
        .line-login-button {
            background-color: #06C755;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            margin-bottom: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .line-login-button:hover {
            background-color: #05a649;
        }
        
        .line-icon {
            margin-right: 10px;
            width: 24px;
            height: 24px;
        }
        
        /* Instructions Card */
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1976d2;
        }
        
        .card-content {
            margin-bottom: 10px;
        }
        
        .card-content p {
            margin-bottom: 8px;
            color: #333;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teacher-Prasat</h1>
    </div>

    <div class="container">
        <!-- Logo and Welcome Section -->
        <div class="welcome-image">
            <img src="/api/placeholder/400/400" alt="Teacher-Prasat Logo">
        </div>
        
        <div class="welcome-text">
            <h2>ยินดีต้อนรับครูที่ปรึกษา</h2>
            <p>ระบบเช็คชื่อเข้าแถวออนไลน์ โรงเรียนปราสาทวิทยาคม</p>
        </div>
        
        <!-- LINE Login Button -->
        <button class="line-login-button" onclick="loginWithLine()">
            <img src="/api/placeholder/24/24" alt="LINE" class="line-icon">
            เข้าสู่ระบบด้วย LINE
        </button>
        
        <!-- Instructions Card -->
        <div class="card">
            <div class="card-title">วิธีการใช้งาน</div>
            <div class="card-content">
                <p>1. กดปุ่ม "เข้าสู่ระบบด้วย LINE" ด้านบน</p>
                <p>2. อนุญาตให้เชื่อมต่อกับบัญชี LINE ของคุณ</p>
                <p>3. ระบบจะพาคุณไปยังหน้าถัดไปโดยอัตโนมัติ</p>
            </div>
        </div>
        
        <div class="footer">
            © 2025 โรงเรียนปราสาทวิทยาคม - Teacher-Prasat v1.0.0
        </div>
    </div>

    <script>
        function loginWithLine() {
            // ในเวอร์ชันจริงจะต้องมีการเรียกใช้ LINE Login API
            // สำหรับตัวอย่างนี้จะใช้การ redirect ไปยังหน้าต่อไป
            window.location.href = "id-verification.html";
        }
    </script>
</body>
</html>
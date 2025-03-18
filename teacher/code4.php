<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - ลงทะเบียนเสร็จสิ้น</title>
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
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
            margin: 0;
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
        
        /* Success Card */
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 30px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
        }
        
        /* Success Icon */
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
        
        /* Success Message */
        .success-message {
            margin-bottom: 40px;
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
        
        /* Start Button */
        .start-button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 16px 30px;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            max-width: 300px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
            transition: background-color 0.3s, transform 0.2s;
            margin-top: auto;
        }
        
        .start-button:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .start-button:active {
            transform: translateY(0);
            box-shadow: 0 3px 5px rgba(0,0,0,0.1);
        }
        
        .start-button .material-icons {
            margin-left: 10px;
            font-size: 20px;
        }
        
        /* Confetti Animation */
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
        
        /* Features Section */
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-spacer"></div>
        <h1>ลงทะเบียนเสร็จสิ้น</h1>
        <div class="header-spacer"></div>
    </div>

    <div class="container">
        <!-- Step Indicator -->
        <div class="steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-title">เข้าสู่ระบบ</div>
            </div>
            <div class="step-line"></div>
            <div class="step completed">
                <div class="step-number">2</div>
                <div class="step-title">ยืนยันตัวตน</div>
            </div>
            <div class="step-line"></div>
            <div class="step completed">
                <div class="step-number">3</div>
                <div class="step-title">ข้อมูลส่วนตัว</div>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-number">4</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- Success Card -->
        <div class="card">
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
            <button class="start-button" onclick="startUsingApp()">
                เริ่มใช้งานระบบ
                <span class="material-icons">arrow_forward</span>
            </button>
        </div>
    </div>

    <!-- Confetti Elements (สร้างพลุกระดาษ) -->
    <div id="confetti-container"></div>

    <script>
        // เริ่มใช้งานแอปพลิเคชัน
        function startUsingApp() {
            // ในเวอร์ชันจริงจะเป็นการไปยังหน้าหลักของแอปพลิเคชัน
            window.location.href = "dashboard.html";
        }
        
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
    </script>
</body>
</html>
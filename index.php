<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STP-Prasat - เข้าสู่ระบบด้วย LINE</title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 480px;
            width: 90%;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* โลโก้และหัวข้อ */
        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            border-radius: 24px;
            background: linear-gradient(135deg, #06c755 0%, #04a73b 100%);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(6, 199, 85, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .logo-text {
            color: white;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .logo::after {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .logo::before {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -15px;
            width: 50px;
            height: 50px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 40px;
        }
        
        /* การ์ดเลือกบทบาท */
        .role-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .role-card {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            border: 2px solid transparent;
        }
        
        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .role-card.selected {
            border-color: #06c755;
        }
        
        .role-card.selected::after {
            content: '✓';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 24px;
            height: 24px;
            background-color: #06c755;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        .role-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 24px;
        }
        
        .role-icon.student {
            background-color: #06c755; /* สีเขียว LINE สำหรับนักเรียน */
        }
        
        .role-icon.teacher {
            background-color: #1976d2; /* สีน้ำเงินสำหรับครู */
        }
        
        .role-icon.parent {
            background-color: #8e24aa; /* สีม่วงสำหรับผู้ปกครอง */
        }
        
        .role-info {
            flex: 1;
        }
        
        .role-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .role-description {
            font-size: 14px;
            color: #666;
        }
        
        /* ปุ่มเข้าสู่ระบบด้วย LINE */
        .login-button {
            background-color: #06c755;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(6, 199, 85, 0.2);
            transition: background-color 0.2s;
            margin-bottom: 20px;
        }
        
        .login-button:hover {
            background-color: #05b64d;
        }
        
        .login-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .line-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ข้อความช่วยเหลือ */
        .help-text {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .help-link {
            color: #06c755;
            text-decoration: none;
            font-weight: 500;
        }
        
        .help-link:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 40px;
        }
        
        /* ไอคอนจาก Material Icons */
        .material-icons {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
        }
        
        /* LINE SVG Icon */
        .line-svg-icon {
            fill: white;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- โลโก้และหัวข้อ -->
        <div class="logo-container">
            <div class="logo">
                <div class="logo-text">SP</div>
            </div>
            <h1 class="title">น้องชูใจ</h1>
            <p class="subtitle">Ai ดูแลผู้เรียน วิทยาลัยการอาชีพปราสาท</p>
        </div>

        <!-- เลือกบทบาท -->
        <h2 style="margin-bottom: 15px; font-size: 18px;">เลือกบทบาทของคุณ</h2>
        <div class="role-cards">
            <div class="role-card selected" onclick="selectRole(this, 'student')">
                <div class="role-icon student">
                    <span class="material-icons">school</span>
                </div>
                <div class="role-info">
                    <div class="role-name">นักเรียน</div>
                    <div class="role-description">สำหรับนักเรียนที่ต้องการเช็คชื่อและดูข้อมูลการเข้าแถว</div>
                </div>
            </div>
            
            <div class="role-card" onclick="selectRole(this, 'teacher')">
                <div class="role-icon teacher">
                    <span class="material-icons">person</span>
                </div>
                <div class="role-info">
                    <div class="role-name">ครูที่ปรึกษา</div>
                    <div class="role-description">สำหรับครูที่ต้องการเช็คชื่อและดูข้อมูลนักเรียน</div>
                </div>
            </div>
            
            <div class="role-card" onclick="selectRole(this, 'parent')">
                <div class="role-icon parent">
                    <span class="material-icons">people</span>
                </div>
                <div class="role-info">
                    <div class="role-name">ผู้ปกครอง</div>
                    <div class="role-description">สำหรับผู้ปกครองที่ต้องการดูข้อมูลการเข้าแถวของบุตร</div>
                </div>
            </div>
        </div>

        <!-- ปุ่มเข้าสู่ระบบด้วย LINE -->
        <button class="login-button" id="lineLoginBtn">
            <span class="line-icon">
                <svg class="line-svg-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M24 10.304c0-5.369-5.383-9.738-12-9.738-6.616 0-12 4.369-12 9.738 0 4.814 4.269 8.846 10.036 9.608.391.084.922.258 1.057.592.121.303.079.778.039 1.085l-.171 1.027c-.053.303-.242 1.186 1.039.647 1.281-.54 6.911-4.069 9.428-6.967 1.739-1.907 2.572-3.843 2.572-5.992zm-18.988-2.595c.129 0 .234.105.234.234v4.153h2.287c.129 0 .233.104.233.233v.842c0 .129-.104.234-.233.234h-3.363c-.063 0-.119-.025-.161-.065-.043-.043-.068-.1-.068-.169v-5.229c0-.129.104-.233.233-.233h.838zm14.992 0c.129 0 .233.105.233.234v.842c0 .129-.104.234-.233.234h-2.287v.883h2.287c.129 0 .233.105.233.234v.842c0 .129-.104.233-.233.233h-2.287v.884h2.287c.129 0 .233.105.233.233v.842c0 .129-.104.234-.233.234h-3.363c-.063 0-.12-.025-.162-.065-.043-.043-.067-.1-.067-.169v-5.229c0-.129.104-.233.233-.233h3.359zm-10.42 2.763h1.904c.129 0 .233.105.233.234v.842c0 .129-.104.234-.233.234h-1.904c-.056 0-.142-.079-.142-.236v-5.229c0-.129.104-.233.233-.233h.842c.129 0 .233.104.233.233v4.153zm2.666-2.763h.839c.129 0 .233.105.233.234v5.229c0 .129-.104.234-.233.234h-.839c-.129 0-.233-.105-.233-.234v-5.229c0-.129.104-.234.233-.234z"/>
                </svg>
            </span>
            เข้าสู่ระบบด้วย LINE
        </button>

        <!-- ข้อความช่วยเหลือ -->
        <div class="help-text">
            ไม่มีบัญชี LINE? <a href="https://line.me/th/download" target="_blank" class="help-link">วิธีสมัครบัญชี LINE</a>
        </div>
        
        <div class="help-text">
            มีปัญหาในการเข้าสู่ระบบ? <a href="#" class="help-link">ติดต่อผู้ดูแลระบบ</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; 2025 STP-Prasat. All rights reserved.
        </div>
    </div>

    <!-- LIFF SDK และ JavaScript ของเรา -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script src="js/line_liff.js"></script>
    
    <script>
        // เลือกบทบาท
        function selectRole(element, role) {
            // ลบคลาส selected จากทุกการ์ด
            const cards = document.querySelectorAll('.role-card');
            cards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // เพิ่มคลาส selected ให้การ์ดที่เลือก
            element.classList.add('selected');
            
            // อัปเดตข้อความปุ่ม
            const button = document.querySelector('.login-button');
            let buttonText = 'เข้าสู่ระบบด้วย LINE';
            
            switch(role) {
                case 'student':
                    buttonText = 'เข้าสู่ระบบด้วย LINE (นักเรียน)';
                    break;
                case 'teacher':
                    buttonText = 'เข้าสู่ระบบด้วย LINE (ครูที่ปรึกษา)';
                    break;
                case 'parent':
                    buttonText = 'เข้าสู่ระบบด้วย LINE (ผู้ปกครอง)';
                    break;
            }
            
            button.innerHTML = `
                <span class="line-icon">
                    <svg class="line-svg-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path d="M24 10.304c0-5.369-5.383-9.738-12-9.738-6.616 0-12 4.369-12 9.738 0 4.814 4.269 8.846 10.036 9.608.391.084.922.258 1.057.592.121.303.079.778.039 1.085l-.171 1.027c-.053.303-.242 1.186 1.039.647 1.281-.54 6.911-4.069 9.428-6.967 1.739-1.907 2.572-3.843 2.572-5.992zm-18.988-2.595c.129 0 .234.105.234.234v4.153h2.287c.129 0 .233.104.233.233v.842c0 .129-.104.234-.233.234h-3.363c-.063 0-.119-.025-.161-.065-.043-.043-.068-.1-.068-.169v-5.229c0-.129.104-.233.233-.233h.838zm14.992 0c.129 0 .233.105.233.234v.842c0 .129-.104.234-.233.234h-2.287v.883h2.287c.129 0 .233.105.233.234v.842c0 .129-.104.233-.233.233h-2.287v.884h2.287c.129 0 .233.105.233.233v.842c0 .129-.104.234-.233.234h-3.363c-.063 0-.12-.025-.162-.065-.043-.043-.067-.1-.067-.169v-5.229c0-.129.104-.233.233-.233h3.359zm-10.42 2.763h1.904c.129 0 .233.105.233.234v.842c0 .129-.104.234-.233.234h-1.904c-.056 0-.142-.079-.142-.236v-5.229c0-.129.104-.233.233-.233h.842c.129 0 .233.104.233.233v4.153zm2.666-2.763h.839c.129 0 .233.105.233.234v5.229c0 .129-.104.234-.233.234h-.839c-.129 0-.233-.105-.233-.234v-5.229c0-.129.104-.234.233-.234z"/>
                    </svg>
                </span>
                ${buttonText}
            `;
        }
    </script>
</body>
</html>
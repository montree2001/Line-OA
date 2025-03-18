<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADD-Prasat - ลงทะเบียนสำเร็จ</title>
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
            background-color: #f8f9fa;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        
        .container {
            max-width: 600px;
            margin: 70px auto 30px;
            padding: 15px;
            flex: 1;
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
        
        .step.completed .step-number {
            background-color: #4caf50;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .step.completed .step-label {
            color: #4caf50;
        }
        
        /* ส่วนแสดงผลการลงทะเบียนสำเร็จ */
        .success-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #e8f5e9;
            color: #4caf50;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .success-icon .material-icons {
            font-size: 60px;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .success-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        
        /* ข้อมูลการเชื่อมต่อ LINE */
        .connect-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .connect-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .line-connect {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .line-icon {
            width: 50px;
            height: 50px;
            background-color: #06C755;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .line-icon img {
            width: 30px;
            height: 30px;
        }
        
        .line-info {
            flex: 1;
        }
        
        .line-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .line-status {
            font-size: 14px;
            color: #4caf50;
            display: flex;
            align-items: center;
        }
        
        .line-status .material-icons {
            font-size: 16px;
            margin-right: 5px;
        }
        
        .connect-note {
            font-size: 14px;
            color: #666;
        }
        
        /* ข้อมูลระบบ */
        .info-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .info-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .feature-icon {
            background-color: #f3e5f5;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .feature-icon .material-icons {
            color: #8e24aa;
            font-size: 20px;
        }
        
        .feature-text {
            flex: 1;
        }
        
        .feature-heading {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .feature-description {
            font-size: 14px;
            color: #666;
        }
        
        /* ปุ่มดำเนินการ */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-button {
            flex: 1;
            background-color: #8e24aa;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(142, 36, 170, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(142, 36, 170, 0.4);
        }
        
        .action-button .material-icons {
            margin-right: 8px;
        }
        
        .action-button.secondary {
            background-color: white;
            color: #8e24aa;
            border: 1px solid #8e24aa;
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
        <h1>ลงทะเบียนสำเร็จ</h1>
    </div>

    <div class="container">
        <!-- ตัวแสดงขั้นตอน -->
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-label">เลือกนักเรียน</div>
            </div>
            <div class="step completed">
                <div class="step-number">2</div>
                <div class="step-label">ข้อมูลผู้ปกครอง</div>
            </div>
            <div class="step completed">
                <div class="step-number">3</div>
                <div class="step-label">ยืนยันข้อมูล</div>
            </div>
            <div class="step completed">
                <div class="step-number">4</div>
                <div class="step-label">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- ส่วนแสดงผลการลงทะเบียนสำเร็จ -->
        <div class="success-card">
            <div class="success-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <h1 class="success-title">ลงทะเบียนสำเร็จ</h1>
            <p class="success-message">
                ท่านได้ลงทะเบียนเข้าใช้งานระบบ SADD-Prasat เรียบร้อยแล้ว<br>
                ขณะนี้ท่านสามารถติดตามการเข้าแถวของนักเรียนได้แล้ว
            </p>
        </div>
        
        <!-- ข้อมูลการเชื่อมต่อ LINE -->
        <div class="connect-card">
            <div class="connect-title">การเชื่อมต่อกับ LINE</div>
            
            <div class="line-connect">
                <div class="line-icon">
                    <img src="assets/images/line-icon-white.png" alt="LINE" onerror="this.src='https://via.placeholder.com/30x30?text=LINE'">
                </div>
                <div class="line-info">
                    <div class="line-name">SADD-Prasat</div>
                    <div class="line-status">
                        <span class="material-icons">check_circle</span> เชื่อมต่อสำเร็จ
                    </div>
                </div>
            </div>
            
            <p class="connect-note">
                ท่านจะได้รับการแจ้งเตือนผ่าน LINE เมื่อนักเรียนเช็คชื่อเข้าแถว หรือไม่ได้เข้าแถวในแต่ละวัน รวมถึงข่าวสารสำคัญจากทางโรงเรียน
            </p>
        </div>
        
        <!-- ข้อมูลระบบ -->
        <div class="info-card">
            <div class="info-title">คุณสมบัติของระบบ</div>
            
            <ul class="feature-list">
                <li class="feature-item">
                    <div class="feature-icon">
                        <span class="material-icons">notifications</span>
                    </div>
                    <div class="feature-text">
                        <div class="feature-heading">แจ้งเตือนการเข้าแถว</div>
                        <div class="feature-description">
                            รับการแจ้งเตือนเมื่อนักเรียนเช็คชื่อหรือขาดการเข้าแถวในแต่ละวัน
                        </div>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <span class="material-icons">bar_chart</span>
                    </div>
                    <div class="feature-text">
                        <div class="feature-heading">ดูสถิติการเข้าแถว</div>
                        <div class="feature-description">
                            ตรวจสอบสถิติการเข้าแถวของนักเรียนได้ทุกที่ทุกเวลา
                        </div>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <span class="material-icons">chat</span>
                    </div>
                    <div class="feature-text">
                        <div class="feature-heading">ติดต่อครูที่ปรึกษา</div>
                        <div class="feature-description">
                            สามารถติดต่อสื่อสารกับครูที่ปรึกษาได้โดยตรงผ่านระบบ
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        
        <!-- ปุ่มดำเนินการ -->
        <div class="action-buttons">
            <a href="dashboard.html" class="action-button">
                <span class="material-icons">dashboard</span> ไปยังหน้าหลัก
            </a>
        </div>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>&copy; 2025 โรงเรียนปราสาทวิทยาคม</p>
            <p>หากมีข้อสงสัยหรือต้องการความช่วยเหลือ <a href="#">ติดต่อเรา</a></p>
        </div>
    </div>
</body>
</html>
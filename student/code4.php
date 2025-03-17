
<!-- หน้าสำเร็จการลงทะเบียน -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - ลงทะเบียนสำเร็จ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background-color: #06c755;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            margin: auto;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .success-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background-color: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4caf50;
            font-size: 50px;
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4caf50;
        }
        
        .success-message {
            color: #666;
            margin-bottom: 30px;
        }
        
        .student-info {
            background-color: #f8f8f8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            width: 120px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
        }
        
        .btn {
            background-color: #06c755;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background-color: #05a647;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>STD-Prasat</h1>
    </div>
    
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="success-title">ลงทะเบียนสำเร็จ</div>
            <div class="success-message">
                ยินดีต้อนรับเข้าสู่ระบบเช็คชื่อเข้าแถวออนไลน์ของวิทยาลัยการอาชีพปราสาท คุณสามารถเริ่มใช้งานระบบได้ทันที
            </div>
            
            <div class="student-info">
                <div class="info-item">
                    <div class="info-label">ชื่อ-นามสกุล:</div>
                    <div class="info-value">นายเอกชัย รักเรียน</div>
                </div>
                <div class="info-item">
                    <div class="info-label">รหัสนักศึกษา:</div>
                    <div class="info-value">12345678901</div>
                </div>
                <div class="info-item">
                    <div class="info-label">ระดับชั้น:</div>
                    <div class="info-value">ปวช.3/1</div>
                </div>
                <div class="info-item">
                    <div class="info-label">สาขาวิชา:</div>
                    <div class="info-value">เทคโนโลยีสารสนเทศ</div>
                </div>
                <div class="info-item">
                    <div class="info-label">ครูที่ปรึกษา:</div>
                    <div class="info-value">อ.ใจดี มากเมตตา</div>
                </div>
            </div>
            
            <a href="student_home.php" class="btn">เข้าสู่ระบบ</a>
        </div>
    </div>
    
    <div class="footer">
        &copy; 2025 วิทยาลัยการอาชีพปราสาท | ระบบเช็คชื่อเข้าแถวออนไลน์
    </div>
</body>
</html>
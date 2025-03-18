<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - ยืนยันตัวตน</title>
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
        
        .header-icon {
            font-size: 24px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-spacer {
            width: 40px;
        }
        
        .container {
            max-width: 480px;
            margin: 70px auto 20px;
            padding: 15px;
            min-height: calc(100vh - 90px);
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
        
        /* ID Verification Card */
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
            margin-bottom: 20px;
            color: #1976d2;
        }
        
        .card-content {
            margin-bottom: 15px;
        }
        
        /* Input field */
        .input-container {
            margin-bottom: 25px;
        }
        
        .input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .input-field {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-field:focus {
            border-color: #1976d2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }
        
        .id-format {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
        
        /* Search button */
        .search-button {
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }
        
        .search-button:hover {
            background-color: #1565c0;
        }
        
        .search-button .material-icons {
            margin-right: 8px;
        }
        
        /* Contact admin link */
        .contact-admin {
            text-align: center;
            font-size: 14px;
            color: #666;
            padding: 10px 0;
        }
        
        .contact-admin a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
        }
        
        .contact-admin a:hover {
            text-decoration: underline;
        }
        
        /* Result message */
        .result-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .result-message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            display: flex;
            align-items: center;
        }
        
        .result-message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            display: flex;
            align-items: center;
        }
        
        .result-message .material-icons {
            margin-right: 8px;
        }
        
        /* Navigation buttons */
        .page-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .nav-button {
            padding: 12px 20px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }
        
        .nav-button:hover {
            background-color: #1565c0;
        }
        
        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .nav-button.back {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .nav-button.back:hover {
            background-color: #e0e0e0;
        }
        
        .nav-button.next {
            margin-left: auto;
        }
        
        .nav-button .material-icons {
            margin-right: 5px;
        }
        
        .nav-button.next .material-icons {
            margin-right: 0;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="header-icon" onclick="goBack()">
            <span class="material-icons">arrow_back</span>
        </button>
        <h1>ยืนยันตัวตน</h1>
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
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-title">ยืนยันตัวตน</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-title">ข้อมูลส่วนตัว</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- ID Verification Card -->
        <div class="card">
            <div class="card-title">ยืนยันตัวตนด้วยเลขบัตรประชาชน</div>
            <div class="card-content">
                <div class="input-container">
                    <label class="input-label">เลขประจำตัวประชาชน 13 หลัก</label>
                    <input type="text" class="input-field" placeholder="X-XXXX-XXXXX-XX-X" maxlength="13" id="id-card-input" inputmode="numeric">
                    <div class="id-format">กรุณากรอกเลขบัตรประชาชน 13 หลัก (ไม่ต้องใส่ขีด)</div>
                </div>
                
                <button class="search-button" onclick="searchTeacher()">
                    <span class="material-icons">search</span> ค้นหาข้อมูล
                </button>
                
                <div class="contact-admin">
                    หากไม่พบข้อมูลของคุณ กรุณา<a href="#">ติดต่อเจ้าหน้าที่</a>
                </div>
                
                <!-- Result Message - ซ่อนไว้ก่อน แสดงเมื่อมีผลลัพธ์จากการค้นหา -->
                <div class="result-message success" id="success-message">
                    <span class="material-icons">check_circle</span>
                    <span>พบข้อมูลครูที่ปรึกษา: อาจารย์ใจดี มากเมตตา</span>
                </div>
                
                <div class="result-message error" id="error-message">
                    <span class="material-icons">error</span>
                    <span>ไม่พบข้อมูลครูที่ปรึกษาในระบบ กรุณาตรวจสอบเลขบัตรประชาชนอีกครั้ง</span>
                </div>
            </div>
        </div>
        
        <!-- Navigation buttons -->
        <div class="page-navigation">
            <button class="nav-button back" onclick="goBack()">
                <span class="material-icons">arrow_back</span> ย้อนกลับ
            </button>
            <button class="nav-button next" id="next-button" onclick="goToNextPage()" disabled>
                ถัดไป <span class="material-icons">arrow_forward</span>
            </button>
        </div>
    </div>

    <script>
        function goBack() {
            // ในเวอร์ชันจริงจะเป็นการกลับไปยังหน้าก่อนหน้า
            window.location.href = "login.html";
        }
        
        function goToNextPage() {
            // ในเวอร์ชันจริงจะเป็นการไปยังหน้าถัดไป
            window.location.href = "profile-setup.html";
        }
        
        function searchTeacher() {
            const idCardInput = document.getElementById('id-card-input');
            const nextButton = document.getElementById('next-button');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            // ซ่อนข้อความผลลัพธ์ทั้งหมดก่อน
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // ตรวจสอบความถูกต้องของเลขบัตรประชาชน
            if (idCardInput.value.length === 13) {
                // สมมติว่าค้นหาเจอ (ในระบบจริงจะต้องมีการตรวจสอบกับฐานข้อมูล)
                successMessage.style.display = 'flex';
                nextButton.disabled = false;
            } else {
                // ไม่พบข้อมูลหรือข้อมูลไม่ถูกต้อง
                errorMessage.style.display = 'flex';
                nextButton.disabled = true;
            }
        }
        
        // เพิ่มการตรวจสอบขณะกรอกข้อมูล
        document.getElementById('id-card-input').addEventListener('input', function() {
            // รับค่าปัจจุบันและลบอักขระที่ไม่ใช่ตัวเลข
            let value = this.value.replace(/\D/g, '');
            
            // จำกัดความยาวไม่เกิน 13 หลัก
            if (value.length > 13) {
                value = value.slice(0, 13);
            }
            
            // อัปเดตค่าในช่องข้อมูล
            this.value = value;
            
            // ซ่อนข้อความผลลัพธ์เมื่อมีการเปลี่ยนแปลงข้อมูล
            document.getElementById('success-message').style.display = 'none';
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('next-button').disabled = true;
        });
    </script>
</body>
</html>
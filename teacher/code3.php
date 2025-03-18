<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - ข้อมูลส่วนตัว</title>
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
        
        /* Personal Info Card */
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
            margin-bottom: 25px;
            color: #1976d2;
        }
        
        .card-content {
            margin-bottom: 15px;
        }
        
        /* Profile Avatar */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e3f2fd;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-initial {
            font-size: 54px;
            color: #1976d2;
            font-weight: 500;
        }
        
        .avatar-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #1976d2;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .avatar-edit input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .input-field {
            width: 100%;
            padding: 12px 15px;
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
        
        .input-field:disabled,
        .input-field[readonly] {
            background-color: #f5f5f5;
            color: #666;
        }
        
        /* Consent checkbox */
        .checkbox-container {
            display: flex;
            margin: 25px 0;
            align-items: flex-start;
        }
        
        .checkbox-container input {
            margin-right: 10px;
            margin-top: 3px;
            width: 18px;
            height: 18px;
        }
        
        .privacy-policy {
            font-size: 14px;
            flex: 1;
        }
        
        .privacy-policy a {
            color: #1976d2;
            text-decoration: none;
        }
        
        .privacy-policy a:hover {
            text-decoration: underline;
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
        
        .nav-button.save {
            background-color: #4caf50;
        }
        
        .nav-button.save:hover {
            background-color: #388e3c;
        }
        
        .nav-button.next {
            margin-left: auto;
        }
        
        .nav-button .material-icons {
            margin-right: 8px;
        }
        
        .nav-button.next .material-icons,
        .nav-button.save .material-icons {
            margin-right: 0;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="header-icon" onclick="goBack()">
            <span class="material-icons">arrow_back</span>
        </button>
        <h1>ข้อมูลส่วนตัว</h1>
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
            <div class="step active">
                <div class="step-number">3</div>
                <div class="step-title">ข้อมูลส่วนตัว</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- Personal Information Form -->
        <div class="card">
            <div class="card-title">ตรวจสอบและแก้ไขข้อมูลส่วนตัว</div>
            <div class="card-content">
                <!-- Profile Avatar -->
                <div class="profile-avatar">
                    <span class="avatar-initial">ค</span>
                    <div class="avatar-edit">
                        <span class="material-icons">photo_camera</span>
                        <input type="file" accept="image/*" onchange="changeProfilePicture(event)">
                    </div>
                </div>
                
                <!-- Form Fields -->
                <div class="form-group">
                    <label class="input-label">คำนำหน้า</label>
                    <input type="text" class="input-field" value="อาจารย์" id="title">
                </div>
                
                <div class="form-group">
                    <label class="input-label">ชื่อ</label>
                    <input type="text" class="input-field" value="ใจดี" id="firstname">
                </div>
                
                <div class="form-group">
                    <label class="input-label">นามสกุล</label>
                    <input type="text" class="input-field" value="มากเมตตา" id="lastname">
                </div>
                
                <div class="form-group">
                    <label class="input-label">เบอร์โทรศัพท์</label>
                    <input type="tel" class="input-field" value="0891234567" id="phone" inputmode="tel">
                </div>
                
                <div class="form-group">
                    <label class="input-label">อีเมล</label>
                    <input type="email" class="input-field" value="teacher@prasat.ac.th" id="email">
                </div>
                
                <div class="form-group">
                    <label class="input-label">ชั้นที่ปรึกษา</label>
                    <input type="text" class="input-field" value="ม.6/1" readonly id="class">
                </div>
                
                <!-- Privacy Consent -->
                <div class="checkbox-container">
                    <input type="checkbox" id="privacy-consent">
                    <label for="privacy-consent" class="privacy-policy">
                        ข้าพเจ้ายินยอมให้เก็บข้อมูลส่วนบุคคลตาม <a href="#" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a> ของโรงเรียนปราสาทวิทยาคม เพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Navigation buttons -->
        <div class="page-navigation">
            <button class="nav-button back" onclick="goBack()">
                <span class="material-icons">arrow_back</span> ย้อนกลับ
            </button>
            <button class="nav-button save" id="save-button" onclick="saveProfile()" disabled>
                บันทึกข้อมูล <span class="material-icons">save</span>
            </button>
        </div>
    </div>

    <script>
        function goBack() {
            // ในเวอร์ชันจริงจะเป็นการกลับไปยังหน้าก่อนหน้า
            window.location.href = "id-verification.html";
        }
        
        function saveProfile() {
            // ตรวจสอบความถูกต้องของข้อมูล
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!firstname || !lastname || !phone || !email) {
                alert('กรุณากรอกข้อมูลให้ครบทุกช่อง');
                return;
            }
            
            // ตรวจสอบรูปแบบเบอร์โทรศัพท์
            if (!/^[0-9]{10}$/.test(phone)) {
                alert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (ตัวเลข 10 หลัก)');
                return;
            }
            
            // ตรวจสอบรูปแบบอีเมล
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('กรุณากรอกอีเมลให้ถูกต้อง');
                return;
            }
            
            // ในเวอร์ชันจริง จะมีการบันทึกข้อมูลลงฐานข้อมูล
            // แต่ในตัวอย่างนี้จะเป็นเพียงการแสดงข้อความและนำไปยังหน้าถัดไป
            alert('บันทึกข้อมูลเรียบร้อย');
            window.location.href = "registration-complete.html";
        }
        
        function changeProfilePicture(event) {
            const file = event.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const profileAvatar = document.querySelector('.profile-avatar');
                    
                    // ลบตัวอักษรแรกออก
                    const initial = profileAvatar.querySelector('.avatar-initial');
                    if (initial) {
                        initial.style.display = 'none';
                    }
                    
                    // เช็คว่ามีรูปอยู่แล้วหรือไม่
                    let img = profileAvatar.querySelector('img');
                    
                    if (!img) {
                        img = document.createElement('img');
                        profileAvatar.insertBefore(img, profileAvatar.firstChild);
                    }
                    
                    img.src = e.target.result;
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        function showPrivacyPolicy() {
            // ในเวอร์ชันจริง จะเป็นการแสดงนโยบายความเป็นส่วนตัว
            alert('นโยบายความเป็นส่วนตัวของโรงเรียนปราสาทวิทยาคม\n\nโรงเรียนปราสาทวิทยาคมจะเก็บรวบรวมข้อมูลส่วนบุคคลของครูที่ปรึกษาและนักเรียนเพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์เท่านั้น โดยจะไม่เปิดเผยข้อมูลต่อบุคคลที่สาม ยกเว้นในกรณีที่จำเป็นต้องปฏิบัติตามกฎหมาย');
        }
        
        // ตรวจสอบสถานะของปุ่มบันทึกเมื่อมีการติ๊กยินยอม
        document.getElementById('privacy-consent').addEventListener('change', function() {
            document.getElementById('save-button').disabled = !this.checked;
        });
        
        // ตรวจสอบความถูกต้องของเบอร์โทรศัพท์แบบ Real-time
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });
    </script>
</body>
</html>
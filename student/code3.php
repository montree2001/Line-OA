<!-- หน้าลงทะเบียนข้อมูลนักศึกษา -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - ลงทะเบียนข้อมูลนักศึกษา</title>
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
        }
        
        .header {
            background-color: #06c755;
            color: white;
            padding: 15px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-size: 20px;
            flex: 1;
            text-align: center;
        }
        
        .header-back {
            width: 24px;
            height: 24px;
            color: white;
            cursor: pointer;
        }
        
        .container {
            max-width: 500px;
            margin: 20px auto 80px;
            padding: 20px;
        }
        
        .register-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .subtitle {
            font-size: 16px;
            font-weight: 500;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #06c755;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .input-field {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #06c755;
        }
        
        select.input-field {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        .checkbox-label {
            font-size: 14px;
            color: #666;
        }
        
        .checkbox-label a {
            color: #06c755;
            text-decoration: none;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #06c755;
            background-color: #f9f9f9;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 10px;
        }
        
        .upload-text {
            color: #666;
            margin-bottom: 5px;
        }
        
        .upload-subtext {
            font-size: 12px;
            color: #999;
        }
        
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f0f0f0;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .profile-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .upload-button {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 36px;
            height: 36px;
            background-color: #06c755;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
            margin-top: 30px;
        }
        
        .btn:hover {
            background-color: #05a647;
        }
        
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .note {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
        
        .dependent-field {
            display: none;
        }
        
        .dependent-field.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <span class="material-icons header-back" onclick="history.back()">arrow_back</span>
        <h1>ลงทะเบียนข้อมูลนักศึกษา</h1>
        <div style="width: 24px;"></div>
    </div>
    
    <div class="container">
        <div class="register-card">
            <div class="title">กรอกข้อมูลนักศึกษา</div>
            
            <form id="registerForm" onsubmit="return validateForm()">
                <div class="subtitle">ข้อมูลส่วนตัว</div>
                
                <div class="form-group">
                    <label for="studentId">รหัสนักศึกษา (11 หลัก)</label>
                    <input type="text" id="studentId" name="studentId" class="input-field" maxlength="11" placeholder="กรอกรหัสนักศึกษา" required pattern="[0-9]{11}">
                    <div class="note">กรอกเฉพาะตัวเลข 11 หลักเท่านั้น</div>
                </div>
                
                <div class="form-group">
                    <label for="prefix">คำนำหน้า</label>
                    <select id="prefix" name="prefix" class="input-field" required>
                        <option value="" disabled selected>เลือกคำนำหน้า</option>
                        <option value="นาย">นาย</option>
                        <option value="นางสาว">นางสาว</option>
                        <option value="นาง">นาง</option>
                
                <div class="form-group">
                    <label for="firstName">ชื่อ</label>
                    <input type="text" id="firstName" name="firstName" class="input-field" placeholder="กรอกชื่อ" required>
                </div>
                
                <div class="form-group">
                    <label for="lastName">นามสกุล</label>
                    <input type="text" id="lastName" name="lastName" class="input-field" placeholder="กรอกนามสกุล" required>
                </div>
                
                <div class="subtitle">ข้อมูลการศึกษา</div>
                
                <div class="form-group">
                    <label for="eduLevel">ระดับการศึกษา</label>
                    <select id="eduLevel" name="eduLevel" class="input-field" required onchange="showDependentFields()">
                        <option value="" disabled selected>เลือกระดับการศึกษา</option>
                        <option value="ปวช.">ปวช.</option>
                        <option value="ปวส.">ปวส.</option>
                    </select>
                </div>
                
                <div class="form-group dependent-field" id="gradeGroup">
                    <label for="grade">ชั้นปี</label>
                    <select id="grade" name="grade" class="input-field" required>
                        <option value="" disabled selected>เลือกชั้นปี</option>
                        <!-- จะเปลี่ยนตามระดับการศึกษาที่เลือก -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="group">กลุ่มเรียน</label>
                    <select id="group" name="group" class="input-field" required>
                        <option value="" disabled selected>เลือกกลุ่มเรียน</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="department">สาขาวิชา</label>
                    <select id="department" name="department" class="input-field" required>
                        <option value="" disabled selected>เลือกสาขาวิชา</option>
                        <option value="สาขาวิชาช่างยนต์">สาขาวิชาช่างยนต์</option>
                        <option value="สาขาวิชาช่างกลโรงงาน">สาขาวิชาช่างกลโรงงาน</option>
                        <option value="สาขาวิชาช่างไฟฟ้ากำลัง">สาขาวิชาช่างไฟฟ้ากำลัง</option>
                        <option value="สาขาวิชาช่างอิเล็กทรอนิกส์">สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                        <option value="สาขาวิชาการบัญชี">สาขาวิชาการบัญชี</option>
                        <option value="สาขาวิชาเทคโนโลยีสารสนเทศ">สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                        <option value="สาขาวิชาการโรงแรม">สาขาวิชาการโรงแรม</option>
                        <option value="สาขาวิชาช่างเชื่อมโลหะ">สาขาวิชาช่างเชื่อมโลหะ</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="advisor">ครูที่ปรึกษา</label>
                    <select id="advisor" name="advisor" class="input-field" required>
                        <option value="" disabled selected>เลือกครูที่ปรึกษา</option>
                        <option value="อ.ประสิทธิ์ ดีเลิศ">อ.ประสิทธิ์ ดีเลิศ</option>
                        <option value="อ.วันดี สดใส">อ.วันดี สดใส</option>
                        <option value="อ.มานะ รักงาน">อ.มานะ รักงาน</option>
                        <option value="อ.ใจดี มากเมตตา">อ.ใจดี มากเมตตา</option>
                        <option value="อ.สมศักดิ์ ภูมิปัญญา">อ.สมศักดิ์ ภูมิปัญญา</option>
                    </select>
                </div>
                
                <div class="subtitle">ข้อมูลติดต่อ</div>
                
                <div class="form-group">
                    <label for="phone">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                    <input type="tel" id="phone" name="phone" class="input-field" placeholder="กรอกเบอร์โทรศัพท์">
                </div>
                
                <div class="form-group">
                    <label for="profile">รูปโปรไฟล์ (ไม่บังคับ)</label>
                    <div class="profile-preview">
                        <img id="profileImage" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiB2aWV3Qm94PSIwIDAgMTIwIDEyMCI+PHJlY3Qgd2lkdGg9IjEyMCIgaGVpZ2h0PSIxMjAiIGZpbGw9IiNmMGYwZjAiLz48dGV4dCB4PSI2MCIgeT0iNjAiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMzYiIGZvbnQtZmFtaWx5PSJzYW5zLXNlcmlmIiBmaWxsPSIjY2NjY2NjIj5PPC90ZXh0Pjwvc3ZnPg==" alt="รูปโปรไฟล์">
                        <label for="profileUpload" class="upload-button">
                            <span class="material-icons" style="font-size: 18px;">photo_camera</span>
                        </label>
                        <input type="file" id="profileUpload" style="display: none;" accept="image/*" onchange="previewImage(this)">
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="consent" name="consent" required>
                    <label for="consent" class="checkbox-label">
                        ยินยอมให้วิทยาลัยการอาชีพปราสาทเก็บข้อมูลส่วนบุคคลของข้าพเจ้า เพื่อใช้ในระบบเช็คชื่อเข้าแถว <a href="#" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>
                    </label>
                </div>
                
                <button type="submit" class="btn">ลงทะเบียน</button>
            </form>
        </div>
    </div>
    
    <script>
        // แสดงฟิลด์ตามเงื่อนไขที่เลือก
        function showDependentFields() {
            const eduLevel = document.getElementById('eduLevel').value;
            const gradeGroup = document.getElementById('gradeGroup');
            const gradeSelect = document.getElementById('grade');
            
            // ล้างตัวเลือกเดิม
            gradeSelect.innerHTML = '<option value="" disabled selected>เลือกชั้นปี</option>';
            
            if (eduLevel === 'ปวช.') {
                for (let i = 1; i <= 3; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    gradeSelect.appendChild(option);
                }
                gradeGroup.classList.add('active');
            } else if (eduLevel === 'ปวส.') {
                for (let i = 1; i <= 2; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    gradeSelect.appendChild(option);
                }
                gradeGroup.classList.add('active');
            } else {
                gradeGroup.classList.remove('active');
            }
        }
        
        // แสดงตัวอย่างรูปโปรไฟล์
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // แสดงนโยบายความเป็นส่วนตัว
        function showPrivacyPolicy() {
            alert('นโยบายความเป็นส่วนตัวของวิทยาลัยการอาชีพปราสาท\n\nวิทยาลัยการอาชีพปราสาทจะเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคลของท่านเพื่อวัตถุประสงค์ในการให้บริการระบบเช็คชื่อเข้าแถวออนไลน์ การบริหารจัดการด้านการศึกษา และการดำเนินการที่เกี่ยวข้องเท่านั้น เราจะเก็บรักษาข้อมูลของท่านไว้เป็นความลับและจะไม่เปิดเผยข้อมูลดังกล่าวแก่บุคคลภายนอก เว้นแต่จะได้รับความยินยอมจากท่านหรือเป็นไปตามที่กฎหมายกำหนด');
        }
        
        // ตรวจสอบฟอร์มก่อนส่ง
        function validateForm() {
            // ตรวจสอบรหัสนักศึกษา
            const studentId = document.getElementById('studentId').value;
            if (!/^\d{11}$/.test(studentId)) {
                alert('กรุณากรอกรหัสนักศึกษาให้ถูกต้อง (ตัวเลข 11 หลัก)');
                return false;
            }
            
            // ตรวจสอบการยินยอม
            if (!document.getElementById('consent').checked) {
                alert('กรุณายินยอมให้เก็บข้อมูลส่วนบุคคลก่อนดำเนินการต่อ');
                return false;
            }
            
            // ถ้าผ่านการตรวจสอบทั้งหมด ให้ส่งแบบฟอร์ม
            alert('ลงทะเบียนสำเร็จ! กำลังเข้าสู่ระบบ...');
            window.location.href = 'student_home.php';
            return false; // ป้องกันการส่งฟอร์มจริงในตัวอย่าง
        }
    </script>
</body>
</html>

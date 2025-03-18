<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADD-Prasat - ข้อมูลผู้ปกครอง</title>
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
        
        .header-icon {
            font-size: 24px;
        }
        
        .container {
            max-width: 600px;
            margin: 70px auto 30px;
            padding: 15px;
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
        
        .step.active .step-number {
            background-color: #8e24aa;
            color: white;
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
        
        .step.active .step-label {
            color: #8e24aa;
            font-weight: 500;
        }
        
        .step.completed .step-label {
            color: #4caf50;
        }
        
        /* คำแนะนำ */
        .instruction-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .instruction-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #8e24aa;
        }
        
        .instruction-text {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        /* ฟอร์มกรอกข้อมูล */
        .form-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .form-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #8e24aa;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
        }
        
        .form-column {
            flex: 1;
        }
        
        .form-note {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* ปุ่มดำเนินการต่อ */
        .action-button {
            background-color: #8e24aa;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(142, 36, 170, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(142, 36, 170, 0.4);
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
        <a href="student-selection.html" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>ข้อมูลผู้ปกครอง</h1>
        <div class="header-icon">
            <span class="material-icons">help_outline</span>
        </div>
    </div>

    <div class="container">
        <!-- ตัวแสดงขั้นตอน -->
        <div class="progress-steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-label">เลือกนักเรียน</div>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-label">ข้อมูลผู้ปกครอง</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">ยืนยันข้อมูล</div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-label">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- คำแนะนำ -->
        <div class="instruction-card">
            <div class="instruction-title">กรอกข้อมูลผู้ปกครอง</div>
            <div class="instruction-text">
                กรุณากรอกข้อมูลส่วนตัวของท่านเพื่อใช้ในการติดต่อสื่อสารและแจ้งข้อมูลการเข้าแถวของนักเรียน
            </div>
        </div>
        
        <!-- ฟอร์มกรอกข้อมูล -->
        <div class="form-card">
            <h2 class="form-title">ข้อมูลส่วนตัว</h2>
            
            <form id="parentInfoForm">
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้า</label>
                            <select class="form-select" required>
                                <option value="" disabled selected>เลือกคำนำหน้า</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">ความสัมพันธ์กับนักเรียน</label>
                            <select class="form-select" required>
                                <option value="" disabled selected>เลือกความสัมพันธ์</option>
                                <option value="บิดา">บิดา</option>
                                <option value="มารดา">มารดา</option>
                                <option value="ปู่">ปู่</option>
                                <option value="ย่า">ย่า</option>
                                <option value="ตา">ตา</option>
                                <option value="ยาย">ยาย</option>
                                <option value="ลุง">ลุง</option>
                                <option value="ป้า">ป้า</option>
                                <option value="น้า">น้า</option>
                                <option value="อา">อา</option>
                                <option value="พี่">พี่</option>
                                <option value="ผู้ปกครอง">ผู้ปกครอง</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" class="form-input" placeholder="กรอกชื่อจริง" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" class="form-input" placeholder="กรอกนามสกุล" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="tel" class="form-input" placeholder="กรอกเบอร์โทรศัพท์" pattern="[0-9]{10}" required>
                    <div class="form-note">* กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (ไม่ต้องใส่ขีด)</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">อีเมล (ถ้ามี)</label>
                    <input type="email" class="form-input" placeholder="กรอกอีเมล">
                    <div class="form-note">* ไม่จำเป็นต้องกรอก หากต้องการรับการแจ้งเตือนทางอีเมลด้วย</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ที่อยู่ (ถ้ามี)</label>
                    <textarea class="form-input" rows="3" placeholder="กรอกที่อยู่"></textarea>
                </div>
            </form>
        </div>
        
        <!-- ปุ่มดำเนินการต่อ -->
        <a href="confirm.html">
            <button class="action-button">
                ดำเนินการต่อ
            </button>
        </a>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>ข้อมูลของท่านจะถูกเก็บเป็นความลับตาม <a href="#">นโยบายความเป็นส่วนตัว</a> ของโรงเรียน</p>
        </div>
    </div>

    <script>
        // ตรวจสอบฟอร์มก่อนส่ง
        document.querySelector('.action-button').addEventListener('click', function(e) {
            const form = document.getElementById('parentInfoForm');
            if (!form.checkValidity()) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                form.reportValidity();
            }
        });
    </script>
</body>
</html>
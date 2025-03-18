<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADD-Prasat - ยืนยันข้อมูล</title>
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
            padding: a;
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
        
        /* การ์ดข้อมูลสรุป */
        .summary-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .summary-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .edit-link {
            font-size: 14px;
            color: #8e24aa;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .edit-link .material-icons {
            font-size: 16px;
            margin-right: 5px;
        }
        
        .summary-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .summary-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .section-heading {
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .section-heading .material-icons {
            font-size: 20px;
            margin-right: 8px;
            color: #8e24aa;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-weight: 500;
        }
        
        .student-list {
            list-style: none;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            background-color: #e0e0e0;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            font-weight: bold;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .student-class {
            font-size: 12px;
            color: #666;
        }
        
        /* ข้อตกลงการใช้งาน */
        .agreement-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .agreement-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .agreement-content {
            height: 150px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #f1f1f1;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
            font-size: 14px;
            color: #666;
        }
        
        .agreement-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .agreement-checkbox input {
            margin-right: 10px;
            margin-top: 5px;
            transform: scale(1.2);
        }
        
        .agreement-label {
            font-size: 14px;
            color: #333;
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
        
        .action-button:disabled {
            background-color: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
        <a href="parent-info.html" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>ยืนยันข้อมูล</h1>
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
            <div class="step completed">
                <div class="step-number">2</div>
                <div class="step-label">ข้อมูลผู้ปกครอง</div>
            </div>
            <div class="step active">
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
            <div class="instruction-title">ตรวจสอบและยืนยันข้อมูล</div>
            <div class="instruction-text">
                กรุณาตรวจสอบความถูกต้องของข้อมูลที่ท่านได้กรอก หากต้องการแก้ไขข้อมูลส่วนใด สามารถคลิกที่ "แก้ไข" เพื่อกลับไปแก้ไขได้
            </div>
        </div>
        
        <!-- ข้อมูลสรุป -->
        <div class="summary-card">
            <div class="summary-title">
                สรุปข้อมูล
            </div>
            
            <!-- ข้อมูลผู้ปกครอง -->
            <div class="summary-section">
                <div class="section-heading">
                    <span class="material-icons">person</span> ข้อมูลผู้ปกครอง
                    <a href="parent-info.html" class="edit-link" style="margin-left: auto;">
                        <span class="material-icons">edit</span> แก้ไข
                    </a>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ชื่อ-นามสกุล</div>
                        <div class="info-value">นางสมใจ รักเรียน</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ความสัมพันธ์</div>
                        <div class="info-value">มารดา</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">เบอร์โทรศัพท์</div>
                        <div class="info-value">081-234-5678</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">อีเมล</div>
                        <div class="info-value">somjai@example.com</div>
                    </div>
                </div>
            </div>
            
            <!-- ข้อมูลนักเรียน -->
            <div class="summary-section">
                <div class="section-heading">
                    <span class="material-icons">school</span> นักเรียนที่ดูแล
                    <a href="student-selection.html" class="edit-link" style="margin-left: auto;">
                        <span class="material-icons">edit</span> แก้ไข
                    </a>
                </div>
                <ul class="student-list">
                    <li class="student-item">
                        <div class="student-avatar">อ</div>
                        <div class="student-info">
                            <div class="student-name">นายเอกชัย รักเรียน</div>
                            <div class="student-class">ม.6/1 เลขที่ 15 (รหัส: 16536)</div>
                        </div>
                    </li>
                    <li class="student-item">
                        <div class="student-avatar">ส</div>
                        <div class="student-info">
                            <div class="student-name">นางสาวสมหญิง รักเรียน</div>
                            <div class="student-class">ม.4/2 เลขที่ 8 (รหัส: 14528)</div>
                        </div>
                    </li>
                    <li class="student-item">
                        <div class="student-avatar">ธ</div>
                        <div class="student-info">
                            <div class="student-name">เด็กชายธนกฤต รักเรียน</div>
                            <div class="student-class">ป.6/3 เลขที่ 10 (รหัส: 09610)</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- ข้อตกลงการใช้งาน -->
        <div class="agreement-card">
            <div class="agreement-title">ข้อตกลงการใช้งานและนโยบายความเป็นส่วนตัว</div>
            
            <div class="agreement-content">
                <p><b>ข้อตกลงการใช้งานและนโยบายความเป็นส่วนตัว</b></p>
                <p>ข้อตกลงการใช้งานระบบ SADD-Prasat ระบบติดตามการเข้าแถวสำหรับผู้ปกครอง</p>
                <br>
                <p>1. <b>วัตถุประสงค์การใช้งาน</b></p>
                <p>ระบบ SADD-Prasat มีวัตถุประสงค์เพื่อติดตามการเข้าแถวของนักเรียนและแจ้งข้อมูลต่าง ๆ ไปยังผู้ปกครอง ช่วยสร้างความมั่นใจให้กับผู้ปกครองว่านักเรียนเข้าโรงเรียนตรงเวลา</p>
                <br>
                <p>2. <b>การเก็บรวบรวมและใช้ข้อมูลส่วนบุคคล</b></p>
                <p>ข้อมูลที่ระบบจัดเก็บจะประกอบด้วย ชื่อ-นามสกุล เบอร์โทรศัพท์ ความสัมพันธ์กับนักเรียน และข้อมูลการเข้าแถวของนักเรียน โดยทางโรงเรียนจะใช้ข้อมูลดังกล่าวเพื่อการติดต่อสื่อสารกับผู้ปกครองเท่านั้น</p>
                <br>
                <p>3. <b>การแจ้งเตือนผ่าน LINE</b></p>
                <p>ระบบนี้จะมีการแจ้งเตือนผ่าน LINE Official Account ซึ่งผู้ปกครองตกลงที่จะรับข้อความแจ้งเตือนเกี่ยวกับการเข้าแถว การขาดเรียน และข่าวสารสำคัญของทางโรงเรียน</p>
                <br>
                <p>4. <b>การรักษาความปลอดภัยของข้อมูล</b></p>
                <p>ทางโรงเรียนจะดำเนินการตามมาตรการรักษาความปลอดภัยที่เหมาะสมเพื่อป้องกันการเข้าถึง เปิดเผย เปลี่ยนแปลง หรือทำลายข้อมูลโดยไม่ได้รับอนุญาต</p>
                <br>
                <p>5. <b>การแก้ไขข้อมูล</b></p>
                <p>ผู้ปกครองสามารถแก้ไขข้อมูลส่วนตัวได้ผ่านทางระบบหรือแจ้งต่อทางโรงเรียนโดยตรง</p>
            </div>
            
            <div class="agreement-checkbox">
                <input type="checkbox" id="privacy-agreement">
                <label for="privacy-agreement" class="agreement-label">
                    ข้าพเจ้ายอมรับข้อตกลงในการใช้งานและนโยบายความเป็นส่วนตัว
                </label>
            </div>
            
            <div class="agreement-checkbox">
                <input type="checkbox" id="info-agreement">
                <label for="info-agreement" class="agreement-label">
                    ข้าพเจ้ายืนยันว่าข้อมูลที่กรอกเป็นความจริงและเป็นข้อมูลที่เป็นปัจจุบัน
                </label>
            </div>
        </div>
        
        <!-- ปุ่มดำเนินการต่อ -->
        <a href="complete.html">
            <button class="action-button" id="confirm-button" disabled>
                ยืนยันข้อมูล
            </button>
        </a>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>หากมีข้อสงสัยหรือต้องการความช่วยเหลือ <a href="#">ติดต่อเรา</a></p>
        </div>
    </div>

    <script>
        // ตรวจสอบการยอมรับข้อตกลง
        const privacyCheckbox = document.getElementById('privacy-agreement');
        const infoCheckbox = document.getElementById('info-agreement');
        const confirmButton = document.getElementById('confirm-button');
        
        function checkAgreements() {
            confirmButton.disabled = !(privacyCheckbox.checked && infoCheckbox.checked);
        }
        
        privacyCheckbox.addEventListener('change', checkAgreements);
        infoCheckbox.addEventListener('change', checkAgreements);
    </script>
</body>
</html>
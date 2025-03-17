
<!-- หน้าตรวจสอบรหัสนักศึกษา -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - ตรวจสอบรหัสนักศึกษา</title>
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
            margin: 20px auto;
            padding: 20px;
        }
        
        .check-card {
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
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
        
        .btn {
            background-color: #06c755;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #05a647;
        }
        
        .result-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .result-box.success {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #388e3c;
        }
        
        .result-box.error {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            color: #d32f2f;
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .result-title .material-icons {
            margin-right: 8px;
        }
        
        .result-message {
            margin-bottom: 10px;
        }
        
        .result-action {
            margin-top: 15px;
        }
        
        .link-btn {
            background-color: transparent;
            color: #06c755;
            border: 1px solid #06c755;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .link-btn:hover {
            background-color: #06c755;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <span class="material-icons header-back" onclick="history.back()">arrow_back</span>
        <h1>STD-Prasat</h1>
        <div style="width: 24px;"></div>
    </div>
    
    <div class="container">
        <div class="check-card">
            <div class="title">ตรวจสอบรหัสนักศึกษา</div>
            
            <form id="checkForm">
                <div class="form-group">
                    <label for="studentId">รหัสนักศึกษา (11 หลัก)</label>
                    <input type="text" id="studentId" name="studentId" class="input-field" maxlength="11" placeholder="กรุณากรอกรหัสนักศึกษา" required>
                </div>
                
                <button type="submit" class="btn">ตรวจสอบ</button>
            </form>
            
            <!-- ตัวอย่างผลลัพธ์เมื่อพบข้อมูลนักศึกษา -->
            <div class="result-box success" id="successResult">
                <div class="result-title">
                    <span class="material-icons">check_circle</span>
                    พบข้อมูลนักศึกษา
                </div>
                <div class="result-message">
                    <p><strong>ชื่อ-นามสกุล:</strong> <span id="studentName">นายเอกชัย รักเรียน</span></p>
                    <p><strong>ระดับชั้น:</strong> <span id="studentClass">ปวช.3/1</span></p>
                    <p><strong>สาขาวิชา:</strong> <span id="studentDept">สาขาวิชาเทคโนโลยีสารสนเทศ</span></p>
                    <p><strong>ครูที่ปรึกษา:</strong> <span id="studentAdvisor">นางสาวสุดา สุดสวย</span></p>
                </div>
                <div class="result-action">
                    <a href="student_home.php" class="btn">เข้าสู่ระบบ</a>
                </div>
            </div>
            
            <!-- ตัวอย่างผลลัพธ์เมื่อไม่พบข้อมูลนักศึกษา -->
            <div class="result-box error" id="errorResult">
                <div class="result-title">
                    <span class="material-icons">error</span>
                    ไม่พบข้อมูลนักศึกษา
                </div>
                <div class="result-message">
                    ไม่พบข้อมูลนักศึกษาในระบบ กรุณาลงทะเบียนข้อมูลเพิ่มเติม
                </div>
                <div class="result-action">
                    <a href="student_register.php" class="btn">ลงทะเบียนข้อมูลนักศึกษา</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ตัวอย่างการทำงานของฟอร์ม (ในการใช้งานจริงควรใช้ AJAX หรือการส่งฟอร์มไปยังเซิร์ฟเวอร์)
        document.getElementById('checkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const studentId = document.getElementById('studentId').value;
            
            // สมมติว่าตรวจสอบกับฐานข้อมูล (ในตัวอย่างนี้ใช้การตรวจสอบง่ายๆ)
            if (studentId === '12345678901') {
                document.getElementById('successResult').style.display = 'block';
                document.getElementById('errorResult').style.display = 'none';
            } else {
                document.getElementById('successResult').style.display = 'none';
                document.getElementById('errorResult').style.display = 'block';
            }
        });
    </script>
</body>
</html>

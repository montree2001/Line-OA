<?php
// login_2.php
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบรหัสนักศึกษา - STUDENT-Prasat</title>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #06c755;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 400px;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: #06c755;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ddd;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #06c755;
        }
        button {
            display: block;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            background-color: #06c755;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #05a649;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #e8f5e9;
            color: #1e881a;
            border: 1px solid #c8e6c9;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <h1>STUDENT-Prasat</h1>
    </div>
    <div class="container">
        <h1>ตรวจสอบรหัสนักศึกษา</h1>
        <form id="checkForm" action="" method="POST">
            <div class="form-group">
                <label for="studentId">รหัสนักศึกษา:</label>
                <input type="text" id="studentId" name="studentId" maxlength="11" placeholder="กรอกรหัสนักศึกษา 11 หลัก" required>
            </div>
            <button type="submit">ตรวจสอบ</button>
        </form>
        <?php
        // ฟังก์ชันตรวจสอบรหัสนักศึกษา
        function checkStudentId($studentId) {
            // ตัวอย่างการตรวจสอบรหัสนักศึกษา (สมมติว่ามีในฐานข้อมูล)
            $studentIds = ['12345678901', '23456789012', '34567890123'];

            if (in_array($studentId, $studentIds)) {
                return true;
            } else {
                return false;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = $_POST['studentId'];
            $message = '';

            if (checkStudentId($studentId)) {
                $message = '<div class="message success">รหัสนักศึกษานี้มีอยู่ในระบบแล้ว</div>';
            } else {
                $message = '<div class="message error">ไม่พบรหัสนักศึกษานี้ในระบบ กรุณากรอกข้อมูลเพิ่มเติม</div>';
            }

            echo $message;
        }
        ?>
    </div>
</body>
</html>

<!-- หน้า Login ด้วย Line -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - เข้าสู่ระบบ</title>
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
        
        .login-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background-color: #06c755;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
        }
        
        .welcome-text {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .sub-text {
            color: #666;
            margin-bottom: 30px;
        }
        
        .line-btn {
            background-color: #06c755;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        
        .line-btn:hover {
            background-color: #05a647;
        }
        
        .line-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            background-image: url('data:image/svg+xml;charset=utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2048%2048%22%20width%3D%2248px%22%20height%3D%2248px%22%3E%3Cpath%20fill%3D%22%23fff%22%20d%3D%22M12.5%2C42h23c3.59%2C0%2C6.5-2.91%2C6.5-6.5v-23C42%2C8.91%2C39.09%2C6%2C35.5%2C6h-23C8.91%2C6%2C6%2C8.91%2C6%2C12.5v23C6%2C39.09%2C8.91%2C42%2C12.5%2C42z%22%2F%3E%3Cpath%20fill%3D%22%2300b900%22%20d%3D%22M37.113%2C22.417c0-5.865-5.88-10.637-13.107-10.637s-13.108%2C4.772-13.108%2C10.637c0%2C5.258%2C4.663%2C9.662%2C10.962%2C10.495c0.427%2C0.092%2C1.008%2C0.282%2C1.155%2C0.646c0.132%2C0.331%2C0.086%2C0.85%2C0.042%2C1.185c0%2C0-0.153%2C0.925-0.187%2C1.122c-0.057%2C0.331-0.263%2C1.296%2C1.135%2C0.707c1.399-0.589%2C7.548-4.445%2C10.298-7.611h-0.001C36.203%2C26.879%2C37.113%2C24.764%2C37.113%2C22.417z%20M18.875%2C25.357h-2.604c-0.379%2C0-0.687-0.308-0.687-0.688V20.62c0-0.379%2C0.308-0.687%2C0.687-0.687c0.379%2C0%2C0.687%2C0.308%2C0.687%2C0.687v3.362h1.917c0.379%2C0%2C0.687%2C0.308%2C0.687%2C0.688S19.254%2C25.357%2C18.875%2C25.357z%20M21.568%2C24.67c0%2C0.379-0.308%2C0.688-0.687%2C0.688s-0.687-0.308-0.687-0.688V20.62c0-0.379%2C0.308-0.687%2C0.687-0.687s0.687%2C0.308%2C0.687%2C0.687V24.67z%20M27.21%2C24.67c0%2C0.298-0.193%2C0.567-0.476%2C0.66c-0.073%2C0.024-0.147%2C0.036-0.224%2C0.035c-0.211%2C0-0.414-0.098-0.547-0.265l-2.665-3.604l0.012%2C3.173c0%2C0.379-0.308%2C0.688-0.688%2C0.688c-0.379%2C0-0.687-0.308-0.687-0.688V20.62c0-0.298%2C0.193-0.567%2C0.476-0.66c0.283-0.093%2C0.593%2C0%2C0.771%2C0.23l2.677%2C3.604l-0.012-3.173c0-0.379%2C0.308-0.687%2C0.687-0.687c0.379%2C0%2C0.687%2C0.308%2C0.687%2C0.687V24.67z%20M32.517%2C22.619c0.379%2C0%2C0.687%2C0.308%2C0.687%2C0.688c0%2C0.379-0.308%2C0.687-0.687%2C0.687h-1.917v1.263c0%2C0.379-0.308%2C0.688-0.687%2C0.688s-0.687-0.308-0.687-0.688V20.62c0-0.379%2C0.308-0.687%2C0.687-0.687h2.604c0.379%2C0%2C0.687%2C0.308%2C0.687%2C0.687s-0.308%2C0.688-0.687%2C0.688h-1.917v1.312H32.517z%22%2F%3E%3C%2Fsvg%3E');
            background-size: contain;
        }
        
        .notes {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 20px;
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
        <div class="login-card">
            <div class="logo">
                <span class="material-icons">school</span>
            </div>
            <div class="welcome-text">ยินดีต้อนรับสู่ระบบเช็คชื่อเข้าแถวออนไลน์</div>
            <div class="sub-text">วิทยาลัยการอาชีพปราสาท</div>
            
            <a href="student_check.php" class="line-btn">
                <div class="line-icon"></div>
                เข้าสู่ระบบด้วย LINE
            </a>
            
            <div class="notes">
                หมายเหตุ: กรุณาเข้าสู่ระบบด้วยบัญชี LINE ของนักเรียนเท่านั้น
            </div>
        </div>
    </div>
    
    <div class="footer">
        &copy; 2025 วิทยาลัยการอาชีพปราสาท | ระบบเช็คชื่อเข้าแถวออนไลน์
    </div>
</body>
</html>

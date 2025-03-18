<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SADD-Prasat - เลือกนักเรียน</title>
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
        
        /* ส่วนคำแนะนำ */
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
        
        /* ส่วนค้นหา */
        .search-box {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .search-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .search-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .search-option {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .search-option.active {
            border-color: #8e24aa;
            background-color: #f3e5f5;
            color: #8e24aa;
            font-weight: 500;
        }
        
        .search-input {
            display: flex;
            margin-bottom: 15px;
        }
        
        .search-input input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
            padding: 12px 15px;
            font-size: 16px;
            outline: none;
        }
        
        .search-input input:focus {
            border-color: #8e24aa;
        }
        
        .search-button {
            background-color: #8e24aa;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 0 20px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .search-info {
            font-size: 12px;
            color: #999;
        }
        
        /* ผลการค้นหา */
        .search-results {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .results-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-count {
            color: #8e24aa;
            font-weight: 500;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-checkbox {
            margin-right: 15px;
            transform: scale(1.2);
            accent-color: #8e24aa;
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
        <a href="login.html" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>เลือกนักเรียน</h1>
        <div class="header-icon">
            <span class="material-icons">help_outline</span>
        </div>
    </div>

    <div class="container">
        <!-- คำแนะนำ -->
        <div class="instruction-card">
            <div class="instruction-title">ยินดีต้อนรับสู่ SADD-Prasat</div>
            <div class="instruction-text">
                กรุณาเลือกนักเรียนที่ท่านต้องการติดตาม โดยท่านสามารถค้นหาได้จากรหัสนักเรียนหรือชื่อ-นามสกุล และสามารถเลือกนักเรียนได้มากกว่า 1 คน
            </div>
        </div>
        
        <!-- ค้นหานักเรียน -->
        <div class="search-box">
            <div class="search-title">ค้นหานักเรียน</div>
            
            <div class="search-options">
                <div class="search-option active" onclick="switchSearchType('id')">
                    รหัสนักเรียน
                </div>
                <div class="search-option" onclick="switchSearchType('name')">
                    ชื่อ-นามสกุล
                </div>
            </div>
            
            <div class="search-input">
                <input type="text" id="search-field" placeholder="กรอกรหัสนักเรียน" autocomplete="off">
                <button class="search-button">
                    <span class="material-icons">search</span>
                </button>
            </div>
            
            <div class="search-info">
                * สามารถค้นหาได้โดยใช้รหัสนักเรียน หรือ ชื่อ-นามสกุล
            </div>
        </div>
        
        <!-- ผลการค้นหา -->
        <div class="search-results">
            <div class="results-title">
                ผลการค้นหา <span class="results-count">3 คน</span>
            </div>
            
            <div class="student-item">
                <input type="checkbox" class="student-checkbox" id="student1" checked>
                <div class="student-avatar">อ</div>
                <div class="student-info">
                    <div class="student-name">นายเอกชัย รักเรียน</div>
                    <div class="student-class">ม.6/1 เลขที่ 15 (รหัส: 16536)</div>
                </div>
            </div>
            
            <div class="student-item">
                <input type="checkbox" class="student-checkbox" id="student2" checked>
                <div class="student-avatar">ส</div>
                <div class="student-info">
                    <div class="student-name">นางสาวสมหญิง รักเรียน</div>
                    <div class="student-class">ม.4/2 เลขที่ 8 (รหัส: 14528)</div>
                </div>
            </div>
            
            <div class="student-item">
                <input type="checkbox" class="student-checkbox" id="student3" checked>
                <div class="student-avatar">ธ</div>
                <div class="student-info">
                    <div class="student-name">เด็กชายธนกฤต รักเรียน</div>
                    <div class="student-class">ป.6/3 เลขที่ 10 (รหัส: 09610)</div>
                </div>
            </div>
        </div>
        
        <!-- ปุ่มดำเนินการต่อ -->
        <a href="parent-info.html">
            <button class="action-button">
                ดำเนินการต่อ
            </button>
        </a>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>ไม่พบนักเรียนที่ต้องการ? <a href="#">ติดต่อทางโรงเรียน</a></p>
        </div>
    </div>

    <script>
        // สลับประเภทการค้นหา
        function switchSearchType(type) {
            const options = document.querySelectorAll('.search-option');
            const searchField = document.getElementById('search-field');
            
            options.forEach(option => option.classList.remove('active'));
            
            if (type === 'id') {
                options[0].classList.add('active');
                searchField.placeholder = 'กรอกรหัสนักเรียน';
            } else {
                options[1].classList.add('active');
                searchField.placeholder = 'กรอกชื่อ-นามสกุลนักเรียน';
            }
        }
    </script>
</body>
</html>
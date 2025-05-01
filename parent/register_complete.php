<?php
/**
 * parent/register_complete.php
 * หน้ายืนยันการลงทะเบียนเสร็จสิ้น (ขั้นตอนที่ 4)
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอินและขั้นตอนการลงทะเบียน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบขั้นตอนการลงทะเบียน
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 4) {
    // ย้อนกลับไปยังขั้นตอนที่ 1
    header('Location: register_select_students.php');
    exit;
}

// ล้างข้อมูลลงทะเบียนที่ไม่จำเป็น
unset($_SESSION['registration_step']);
unset($_SESSION['selected_students']);
unset($_SESSION['parent_info']);

// ตั้งค่าหัวข้อหน้า
$page_title = 'SADD-Prasat - ลงทะเบียนสำเร็จ';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* ตั้งค่าพื้นฐาน */
        :root {
            --primary-color: #8e24aa; /* สีม่วงสำหรับ SADD-Prasat (ผู้ปกครอง) */
            --primary-color-dark: #5c007a;
            --primary-color-light: #f3e5f5;
            --success-color: #4caf50;
            --success-color-light: #e8f5e9;
            --text-dark: #333;
            --text-light: #666;
            --bg-light: #f8f9fa;
            --border-color: #e0e0e0;
            --card-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
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
        
        .step.completed .step-number {
            background-color: #4caf50;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .step.completed .step-label {
            color: #4caf50;
        }
        
        /* ส่วนสำเร็จ */
        .success-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: var(--success-color-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .success-icon span {
            font-size: 48px;
            color: var(--success-color);
        }
        
        .success-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--success-color);
        }
        
        .success-description {
            color: var(--text-light);
            margin-bottom: 30px;
            padding: 0 10px;
        }
        
        /* ปุ่ม */
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
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(142, 36, 170, 0.4);
        }
        
        .success-footer {
            color: var(--text-light);
            font-size: 14px;
            margin-top: 20px;
        }
        
        /* คุณสมบัติระบบ */
        .features-container {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .features-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            text-align: center;
        }
        
        .features-list {
            list-style: none;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-icon {
            color: var(--primary-color);
            margin-right: 10px;
            margin-top: 2px;
        }
        
        .feature-text {
            flex: 1;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left"></div>
        <h1>ลงทะเบียนสำเร็จ</h1>
        <div class="header-right"></div>
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
            <div class="step completed">
                <div class="step-number">3</div>
                <div class="step-label">ยืนยันข้อมูล</div>
            </div>
            <div class="step completed">
                <div class="step-number">4</div>
                <div class="step-label">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- การ์ดแสดงความสำเร็จ -->
        <div class="success-card">
            <div class="success-icon">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="success-title">ลงทะเบียนสำเร็จ</div>
            <div class="success-description">
                ขอบคุณที่ลงทะเบียนกับระบบ SADD-Prasat ท่านสามารถเริ่มใช้งานระบบเพื่อติดตามข้อมูลการเข้าแถวของนักเรียนในความดูแลได้ทันที
            </div>
            
            <a href="home.php" class="action-button">
                เข้าสู่หน้าหลัก
            </a>
            
            <div class="success-footer">
                หากมีข้อสงสัยเพิ่มเติม กรุณาติดต่อครูที่ปรึกษา
            </div>
        </div>
        
        <!-- คุณสมบัติระบบ -->
        <div class="features-container">
            <div class="features-title">คุณสมบัติของระบบ SADD-Prasat</div>
            <ul class="features-list">
                <li class="feature-item">
                    <span class="material-icons feature-icon">notifications</span>
                    <div class="feature-text">รับการแจ้งเตือนการเข้าแถวของนักเรียนในความดูแลผ่าน LINE</div>
                </li>
                <li class="feature-item">
                    <span class="material-icons feature-icon">trending_up</span>
                    <div class="feature-text">ติดตามสถิติการเข้าแถวรายวัน รายสัปดาห์ และรายเดือน</div>
                </li>
                <li class="feature-item">
                    <span class="material-icons feature-icon">warning</span>
                    <div class="feature-text">ได้รับการแจ้งเตือนเมื่อนักเรียนมีความเสี่ยงที่จะไม่ผ่านกิจกรรม</div>
                </li>
                <li class="feature-item">
                    <span class="material-icons feature-icon">chat</span>
                    <div class="feature-text">ติดต่อสื่อสารกับครูที่ปรึกษาได้โดยตรง</div>
                </li>
                <li class="feature-item">
                    <span class="material-icons feature-icon">event_note</span>
                    <div class="feature-text">รับข่าวสารและประกาศจากทางโรงเรียน</div>
                </li>
            </ul>
        </div>
    </div>
</body>
</html>
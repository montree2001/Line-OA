<?php
/**
 * parent/register_confirm.php
 * หน้ายืนยันข้อมูลในกระบวนการลงทะเบียนผู้ปกครอง (ขั้นตอนที่ 3)
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
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 3) {
    // ย้อนกลับไปยังขั้นตอนที่ 2
    header('Location: register_parent_info.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ปกครอง
$parent_info = $_SESSION['parent_info'] ?? [];

// ดึงข้อมูลนักเรียนที่เลือก
$selected_students = $_SESSION['selected_students'] ?? [];
$students_data = [];

if (!empty($selected_students)) {
    $students_ids = implode(',', array_map('intval', $selected_students));
    
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, c.level, d.department_name, c.group_number 
            FROM students s 
            INNER JOIN users u ON s.user_id = u.user_id 
            LEFT JOIN classes c ON s.current_class_id = c.class_id 
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE s.student_id IN ($students_ids)";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['class_name'] = $row['level'] . ' ' . $row['department_name'] . ' กลุ่ม ' . $row['group_number'];
            $row['avatar'] = substr($row['first_name'], 0, 1); // ใช้อักษรตัวแรกของชื่อเป็น avatar
            $students_data[] = $row;
        }
    }
}

// ส่งฟอร์ม
if (isset($_POST['submit'])) {
    // ตรวจสอบการยอมรับเงื่อนไข
    if (!isset($_POST['accept_terms']) || $_POST['accept_terms'] != '1') {
        $error_message = "กรุณายอมรับเงื่อนไขการใช้งานและนโยบายความเป็นส่วนตัว";
    } else {
        // บันทึกข้อมูลผู้ปกครอง
        $user_id = $_SESSION['user_id'];
        
        // เริ่ม Transaction
        $conn->begin_transaction();
        try {
            // สร้างบันทึกผู้ปกครอง
            $stmt = $conn->prepare("INSERT INTO parents (user_id, title, relationship, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $parent_info['title'], $parent_info['relationship']);
            $stmt->execute();
            $parent_id = $conn->insert_id;
            $stmt->close();
            
            // ถ้ามีนักเรียนที่เลือก ให้บันทึกความสัมพันธ์
            if (!empty($selected_students)) {
                $stmt = $conn->prepare("INSERT INTO parent_student_relation (parent_id, student_id, created_at) VALUES (?, ?, NOW())");
                
                foreach ($selected_students as $student_id) {
                    $stmt->bind_param("ii", $parent_id, $student_id);
                    $stmt->execute();
                }
                
                $stmt->close();
            }
            
            // บันทึกยอมรับ GDPR
            $stmt = $conn->prepare("UPDATE users SET gdpr_consent = 1, gdpr_consent_date = NOW() WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit Transaction
            $conn->commit();
            
            // ไปยังขั้นตอนสุดท้าย
            $_SESSION['registration_step'] = 4;
            header('Location: register_complete.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback Transaction
            $conn->rollback();
            $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ตั้งค่าหัวข้อหน้า
$page_title = 'SADD-Prasat - ยืนยันข้อมูล';
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
        
        .header-icon {
            font-size: 24px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
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
        
        /* แสดงข้อมูล */
        .confirm-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .confirm-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #8e24aa;
        }
        
        .confirm-description {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* ส่วนข้อมูลผู้ปกครอง */
        .parent-data {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .data-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .data-row:last-child {
            margin-bottom: 0;
        }
        
        .data-label {
            font-weight: 500;
            width: 150px;
            color: #555;
        }
        
        .data-value {
            flex: 1;
        }
        
        /* รายการนักเรียน */
        .students-list {
            margin-top: 20px;
        }
        
        .students-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #555;
        }
        
        .no-students {
            text-align: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            color: #999;
            font-style: italic;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .student-item:last-child {
            margin-bottom: 0;
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
        
        /* แสดงข้อผิดพลาด */
        .error-message {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #d32f2f;
        }
        
        /* ยอมรับเงื่อนไข */
        .terms-container {
            margin-top: 20px;
        }
        
        .terms-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .terms-check input {
            margin-top: 3px;
            margin-right: 10px;
            transform: scale(1.2);
            accent-color: #8e24aa;
        }
        
        .terms-text {
            font-size: 14px;
            color: #666;
        }
        
        .terms-link {
            color: #8e24aa;
            text-decoration: none;
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
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(142, 36, 170, 0.4);
        }
        
        .back-button {
            background-color: #f5f5f5;
            color: #666;
            border: none;
            border-radius: 10px;
            padding: 15px 0;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        
        .back-button:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="register_parent_info.php" class="header-icon">
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
        
        <!-- สรุปข้อมูล -->
        <div class="confirm-card">
            <div class="confirm-title">ตรวจสอบข้อมูล</div>
            <div class="confirm-description">
                กรุณาตรวจสอบความถูกต้องของข้อมูล และยืนยันการเข้าร่วมระบบ
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- ข้อมูลผู้ปกครอง -->
            <h3 style="margin-bottom: 10px;">ข้อมูลผู้ปกครอง</h3>
            <div class="parent-data">
                <div class="data-row">
                    <div class="data-label">ชื่อ-นามสกุล:</div>
                    <div class="data-value"><?php echo htmlspecialchars($parent_info['title'] ?? '') . ' ' . htmlspecialchars($parent_info['first_name'] ?? '') . ' ' . htmlspecialchars($parent_info['last_name'] ?? ''); ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">ความสัมพันธ์:</div>
                    <div class="data-value"><?php echo htmlspecialchars($parent_info['relationship'] ?? ''); ?></div>
                </div>
                <div class="data-row">
                    <div class="data-label">เบอร์โทรศัพท์:</div>
                    <div class="data-value"><?php echo htmlspecialchars($parent_info['phone_number'] ?? ''); ?></div>
                </div>
                <?php if (!empty($parent_info['email'])): ?>
                <div class="data-row">
                    <div class="data-label">อีเมล:</div>
                    <div class="data-value"><?php echo htmlspecialchars($parent_info['email']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- รายการนักเรียน -->
            <div class="students-list">
                <div class="students-title">นักเรียนในความดูแล</div>
                
                <?php if (empty($students_data)): ?>
                    <div class="no-students">
                        ยังไม่ได้เลือกนักเรียน
                    </div>
                <?php else: ?>
                    <?php foreach ($students_data as $student): ?>
                        <div class="student-item">
                            <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                <div class="student-class"><?php echo htmlspecialchars($student['class_name']); ?> (รหัส: <?php echo htmlspecialchars($student['student_code']); ?>)</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <!-- ยอมรับเงื่อนไข -->
                <div class="terms-container">
                    <div class="terms-check">
                        <input type="checkbox" name="accept_terms" id="accept_terms" value="1" <?php echo isset($_POST['accept_terms']) && $_POST['accept_terms'] == '1' ? 'checked' : ''; ?>>
                        <label for="accept_terms" class="terms-text">
                            ข้าพเจ้ายอมรับ <a href="#" class="terms-link">เงื่อนไขการใช้งาน</a> และ <a href="#" class="terms-link">นโยบายความเป็นส่วนตัว</a> ของระบบ SADD-Prasat และยินยอมให้เก็บข้อมูลส่วนบุคคลเพื่อวัตถุประสงค์ในการแจ้งเตือนและติดตามข้อมูลการเข้าแถวของนักเรียนในความดูแล
                        </label>
                    </div>
                </div>
                
                <!-- ปุ่มดำเนินการ -->
                <button type="submit" name="submit" class="action-button">
                    ยืนยันข้อมูล
                </button>
                
                <a href="register_parent_info.php" class="back-button" style="display: block; text-align: center; text-decoration: none;">
                    ย้อนกลับ
                </a>
            </form>
        </div>
    </div>
</body>
</html>
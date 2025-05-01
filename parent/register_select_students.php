<?php
/**
 * parent/register_select_students.php
 * หน้าเลือกนักเรียนในการลงทะเบียนผู้ปกครอง (ขั้นตอนที่ 2)
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอินและขั้นตอนการลงทะเบียน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// ตั้งค่าขั้นตอนการลงทะเบียน
$_SESSION['registration_step'] = 1;

// สร้างหรือเรียกใช้ตัวแปร session สำหรับเก็บรายชื่อนักเรียนที่เลือก
if (!isset($_SESSION['selected_students'])) {
    $_SESSION['selected_students'] = [];
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

// ค้นหานักเรียน
$search_result = [];
$search_query = '';
$search_type = 'id'; // ค่าเริ่มต้นค้นหาด้วยรหัสนักเรียน

// ตรวจสอบการเพิ่ม/ลบนักเรียนผ่าน AJAX
if (isset($_POST['action']) && $_POST['action'] == 'toggle_student') {
    $student_id = (int)$_POST['student_id'];
    
    if (in_array($student_id, $_SESSION['selected_students'])) {
        // ถ้ามีอยู่แล้ว ให้ลบออก
        $_SESSION['selected_students'] = array_values(array_filter($_SESSION['selected_students'], function($id) use ($student_id) {
            return $id != $student_id;
        }));
    } else {
        // ถ้ายังไม่มี ให้เพิ่มเข้าไป
        $_SESSION['selected_students'][] = $student_id;
    }
    
    echo json_encode(['success' => true, 'selected_students' => $_SESSION['selected_students']]);
    exit;
}

// ตรวจสอบการค้นหา
if (isset($_POST['search']) && !empty($_POST['search_query'])) {
    $search_query = $_POST['search_query'];
    $search_type = $_POST['search_type'];
    
    // สร้างคำสั่ง SQL ตามประเภทการค้นหา
    if ($search_type == 'id') {
        // ค้นหาด้วยรหัสนักเรียน
        $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, c.level, d.department_name, c.group_number 
                FROM students s 
                INNER JOIN users u ON s.user_id = u.user_id 
                LEFT JOIN classes c ON s.current_class_id = c.class_id 
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE s.student_code LIKE ?";
        $param = "%$search_query%";
    } else {
        // ค้นหาด้วยชื่อ-นามสกุล
        $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, c.level, d.department_name, c.group_number 
                FROM students s 
                INNER JOIN users u ON s.user_id = u.user_id 
                LEFT JOIN classes c ON s.current_class_id = c.class_id 
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE u.first_name LIKE ? OR u.last_name LIKE ?";
        $param1 = "%$search_query%";
        $param2 = "%$search_query%";
    }
    
    // ดำเนินการค้นหา
    $stmt = $conn->prepare($sql);
    
    if ($search_type == 'id') {
        $stmt->bind_param("s", $param);
    } else {
        $stmt->bind_param("ss", $param1, $param2);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // เก็บผลลัพธ์
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['class_name'] = $row['level'] . ' ' . $row['department_name'] . ' กลุ่ม ' . $row['group_number'];
            $row['avatar'] = substr($row['first_name'], 0, 1); // ใช้อักษรตัวแรกของชื่อเป็น avatar
            $search_result[] = $row;
        }
    }
    
    $stmt->close();
}

// ดึงข้อมูลนักเรียนที่เลือกแล้ว
$selected_students_data = [];
if (!empty($_SESSION['selected_students'])) {
    $students_ids = implode(',', array_map('intval', $_SESSION['selected_students']));
    
    $sql = "SELECT s.student_id, s.student_code, u.first_name, u.last_name, c.level, d.department_name, c.group_number 
            FROM students s 
            INNER JOIN users u ON s.user_id = u.user_id 
            LEFT JOIN classes c ON s.current_class_id = c.class_id 
            LEFT JOIN departments d ON c.department_id = d.department_id
            WHERE s.student_id IN ($students_ids)";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['class_name'] = $row['level'] . ' ' . $row['department_name'] . ' กลุ่ม ' . $row['group_number'];
            $row['avatar'] = substr($row['first_name'], 0, 1); // ใช้อักษรตัวแรกของชื่อเป็น avatar
            $selected_students_data[] = $row;
        }
    }
}

// ตรวจสอบการเลือกนักเรียน
if (isset($_POST['submit'])) {
    // ไปยังขั้นตอนถัดไป
    $_SESSION['registration_step'] = 2;
    header('Location: register_parent_info.php');
    exit;
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ตั้งค่าหัวข้อหน้า
$page_title = 'SADD-Prasat - เลือกนักเรียน';
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
        
        /* ไม่พบข้อมูล */
        .no-results {
            text-align: center;
            padding: 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="../index.php" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>เลือกนักเรียน</h1>
        <div class="header-icon">
            <span class="material-icons">help_outline</span>
        </div>
    </div>

    <div class="container">
        <!-- ตัวแสดงขั้นตอน -->
        <div class="progress-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-label">เลือกนักเรียน</div>
            </div>
            <div class="step">
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
            <div class="instruction-title">ยินดีต้อนรับสู่ SADD-Prasat</div>
            <div class="instruction-text">
                กรุณาเลือกนักเรียนที่ท่านต้องการติดตาม โดยท่านสามารถค้นหาได้จากรหัสนักเรียนหรือชื่อ-นามสกุล และสามารถเลือกนักเรียนได้มากกว่า 1 คน
            </div>
        </div>
        
        <!-- ค้นหานักเรียน -->
        <div class="search-box">
            <div class="search-title">ค้นหานักเรียน</div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="search-options">
                    <div class="search-option <?php echo ($search_type == 'id' ? 'active' : ''); ?>" onclick="switchSearchType('id')">
                        รหัสนักเรียน
                    </div>
                    <div class="search-option <?php echo ($search_type == 'name' ? 'active' : ''); ?>" onclick="switchSearchType('name')">
                        ชื่อ-นามสกุล
                    </div>
                </div>
                
                <input type="hidden" name="search_type" id="search_type" value="<?php echo $search_type; ?>">
                
                <div class="search-input">
                    <input type="text" name="search_query" id="search-field" placeholder="<?php echo ($search_type == 'id' ? 'กรอกรหัสนักเรียน' : 'กรอกชื่อ-นามสกุลนักเรียน'); ?>" value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                    <button type="submit" name="search" class="search-button">
                        <span class="material-icons">search</span>
                    </button>
                </div>
                
                <div class="search-info">
                    * สามารถค้นหาได้โดยใช้รหัสนักเรียน หรือ ชื่อ-นามสกุล
                </div>
            </form>
        </div>
        
        <!-- ผลการค้นหา -->
        <div class="search-results">
            <div class="results-title">
                ผลการค้นหา 
                <?php if (!empty($search_result)): ?>
                    <span class="results-count"><?php echo count($search_result); ?> คน</span>
                <?php endif; ?>
            </div>
            
            <!-- แสดงนักเรียนที่เลือกแล้ว -->
            <?php if (!empty($selected_students_data)): ?>
                <div class="selected-students-section">
                    <h3 style="margin-bottom: 15px;">นักเรียนที่เลือกแล้ว (<?php echo count($selected_students_data); ?> คน)</h3>
                    
                    <div class="selected-students-list">
                        <?php foreach ($selected_students_data as $student): ?>
                            <div class="student-item selected">
                                <input type="checkbox" class="student-checkbox" checked 
                                       onclick="toggleStudent(<?php echo $student['student_id']; ?>)" 
                                       data-id="<?php echo $student['student_id']; ?>">
                                <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                                <div class="student-info">
                                    <div class="student-name"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                    <div class="student-class"><?php echo $student['class_name']; ?> (รหัส: <?php echo $student['student_code']; ?>)</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="divider" style="height: 1px; background-color: #e0e0e0; margin: 20px 0;"></div>
            <?php endif; ?>
            
            <!-- แสดงผลการค้นหา -->
            <?php if (!empty($search_result)): ?>
                <div>
                    <?php foreach ($search_result as $student): ?>
                        <?php
                        // ตรวจสอบว่านักเรียนคนนี้ถูกเลือกไว้แล้วหรือไม่
                        $isSelected = in_array($student['student_id'], $_SESSION['selected_students']);
                        ?>
                        <div class="student-item <?php echo $isSelected ? 'selected' : ''; ?>">
                            <input type="checkbox" class="student-checkbox" <?php echo $isSelected ? 'checked' : ''; ?> 
                                   onclick="toggleStudent(<?php echo $student['student_id']; ?>)" 
                                   data-id="<?php echo $student['student_id']; ?>">
                            <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                            <div class="student-info">
                                <div class="student-name"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                <div class="student-class"><?php echo $student['class_name']; ?> (รหัส: <?php echo $student['student_code']; ?>)</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (isset($_POST['search'])): ?>
                <div class="no-results">
                    <p>ไม่พบข้อมูลนักเรียนที่ค้นหา</p>
                </div>
            <?php endif; ?>
            
            <!-- ปุ่มดำเนินการต่อ - แสดงเฉพาะเมื่อมีนักเรียนที่เลือกแล้ว -->
            <?php if (!empty($selected_students_data)): ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <button type="submit" name="submit" class="action-button" style="margin-top: 20px;">
                        ดำเนินการต่อ (<?php echo count($selected_students_data); ?> คน)
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- เปิดให้ข้ามขั้นตอนนี้ได้ -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="submit" class="action-button" style="background-color: #757575;">
                ข้ามขั้นตอนนี้
            </button>
        </form>
        
        <!-- ข้อมูลเพิ่มเติม -->
        <div class="info-text">
            <p>ไม่พบนักเรียนที่ต้องการ? <a href="#">ติดต่อทางโรงเรียน</a></p>
        </div>
    </div>

    <style>
        /* เพิ่มสไตล์สำหรับนักเรียนที่เลือกแล้ว */
        .selected-students-section {
            background-color: #f3e5f5;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ce93d8;
        }
        
        .selected-students-list {
            margin-bottom: 0;
        }
        
        .student-item.selected {
            background-color: #f3e5f5;
            border-radius: 8px;
        }
        
        /* เพิ่มเอฟเฟกต์เมื่อกดเลือกนักเรียน */
        .student-item {
            transition: background-color 0.3s, transform 0.2s;
            padding: 10px;
            border-radius: 8px;
        }
        
        .student-item:hover {
            background-color: #f9f9f9;
        }
    </style>
    
    <script>
        // สลับประเภทการค้นหา
        function switchSearchType(type) {
            const options = document.querySelectorAll('.search-option');
            const searchField = document.getElementById('search-field');
            const searchType = document.getElementById('search_type');
            
            options.forEach(option => option.classList.remove('active'));
            
            if (type === 'id') {
                options[0].classList.add('active');
                searchField.placeholder = 'กรอกรหัสนักเรียน';
                searchType.value = 'id';
            } else {
                options[1].classList.add('active');
                searchField.placeholder = 'กรอกชื่อ-นามสกุลนักเรียน';
                searchType.value = 'name';
            }
        }
        
        // ฟังก์ชันเพิ่ม/ลบนักเรียนออกจากรายการที่เลือก
        function toggleStudent(studentId) {
            // ส่งคำขอ AJAX ไปยังเซิร์ฟเวอร์
            fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_student&student_id=' + studentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // อัพเดทสถานะการเลือก
                    const checkboxes = document.querySelectorAll(`.student-checkbox[data-id="${studentId}"]`);
                    const studentItems = document.querySelectorAll(`.student-item:has(.student-checkbox[data-id="${studentId}"])`);
                    
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = data.selected_students.includes(studentId);
                    });
                    
                    if (data.selected_students.includes(studentId)) {
                        // ถ้าเลือก ให้เพิ่มคลาส selected
                        studentItems.forEach(item => item.classList.add('selected'));
                    } else {
                        // ถ้ายกเลิกการเลือก ให้ลบคลาส selected
                        studentItems.forEach(item => item.classList.remove('selected'));
                    }
                    
                    // รีโหลดหน้าเพื่ออัพเดทข้อมูล
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
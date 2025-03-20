<?php
session_start();
require_once '../config/db_config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบบทบาท
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ฟังก์ชันตรวจสอบรหัสนักศึกษา
function validateStudentCode($student_code) {
    return preg_match('/^\d{11}$/', $student_code) === 1;
}

// ฟังก์ชันตรวจสอบความซ้ำของรหัสนักศึกษา
function checkStudentCodeUniqueness($conn, $student_code) {
    $students_stmt = $conn->prepare("SELECT * FROM students WHERE student_code = ?");
    $students_stmt->bind_param("s", $student_code);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();

    $pending_stmt = $conn->prepare("SELECT * FROM student_pending WHERE student_code = ?");
    $pending_stmt->bind_param("s", $student_code);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();

    return [
        'exists_in_students' => $students_result->num_rows > 0,
        'exists_in_pending' => $pending_result->num_rows > 0
    ];
}

// ฟังก์ชันตรวจสอบข้อมูลการลงทะเบียน
function validateRegistrationData($data) {
    $errors = [];

    $valid_titles = ['นาย', 'นางสาว', 'นาง'];
    if (!in_array($data['title'], $valid_titles)) {
        $errors[] = "กรุณาเลือกคำนำหน้าให้ถูกต้อง";
    }

    if (empty(trim($data['first_name']))) {
        $errors[] = "กรุณากรอกชื่อ";
    }

    if (empty(trim($data['last_name']))) {
        $errors[] = "กรุณากรอกนามสกุล";
    }

    $valid_levels = ['ปวช.', 'ปวส.'];
    if (!in_array($data['level_system'], $valid_levels)) {
        $errors[] = "กรุณาเลือกระดับการศึกษา";
    }

    $valid_class_levels = [
        'ปวช.' => ['ปวช.1', 'ปวช.2', 'ปวช.3'],
        'ปวส.' => ['ปวส.1', 'ปวส.2']
    ];
    if (!in_array($data['level'], $valid_class_levels[$data['level_system']] ?? [])) {
        $errors[] = "กรุณาเลือกชั้นปีให้ถูกต้อง";
    }

    $valid_departments = [
        'ช่างยนต์', 'ช่างกลโรงงาน', 'ช่างไฟฟ้ากำลัง', 
        'ช่างอิเล็กทรอนิกส์', 'การบัญชี', 'เทคโนโลยีสารสนเทศ', 
        'การโรงแรม', 'ช่างเชื่อมโลหะ'
    ];
    if (!in_array($data['department'], $valid_departments)) {
        $errors[] = "กรุณาเลือกสาขาวิชา";
    }

    $valid_groups = ['1', '2', '3', '4', '5'];
    if (!in_array($data['group_number'], $valid_groups)) {
        $errors[] = "กรุณาเลือกกลุ่มเรียน";
    }

    if (empty($data['teacher_id'])) {
        $errors[] = "กรุณาเลือกครูที่ปรึกษา";
    }

    if (!$data['gdpr_consent']) {
        $errors[] = "กรุณายินยอมให้เก็บข้อมูลส่วนบุคคล";
    }

    if (!empty($data['phone_number']) && !preg_match('/^0\d{9}$/', $data['phone_number'])) {
        $errors[] = "เบอร์โทรศัพท์ไม่ถูกต้อง";
    }

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "อีเมลไม่ถูกต้อง";
    }

    return [
        'valid' => empty($errors),
        'message' => implode(', ', $errors)
    ];
}

// ฟังก์ชันอัพโหลดรูปภาพโปรไฟล์
function uploadProfilePicture($file, $student_code) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ'
        ];
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return [
            'success' => false, 
            'message' => 'รองรับเฉพาะไฟล์รูปภาพ JPEG, PNG หรือ GIF เท่านั้น'
        ];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return [
            'success' => false, 
            'message' => 'ขนาดไฟล์ต้องไม่เกิน 5MB'
        ];
    }

    $upload_dir = '../uploads/profile_pictures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $student_code . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true, 
            'filename' => $new_filename,
            'path' => 'uploads/profile_pictures/' . $new_filename
        ];
    } else {
        return [
            'success' => false, 
            'message' => 'ไม่สามารถอัพโหลดรูปภาพได้'
        ];
    }
}

// กำหนดขั้นตอนการลงทะเบียน
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error_message = '';
$success_message = '';

// ดึงข้อมูลครูที่ปรึกษา
$teachers_query = "SELECT teacher_id, title, first_name, last_name, department FROM teachers";
$teachers_result = $conn->query($teachers_query);
$teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];

// กำหนดตัวแปรสำหรับ HTML
$valid_departments = [
    'ช่างยนต์', 'ช่างกลโรงงาน', 'ช่างไฟฟ้ากำลัง', 
    'ช่างอิเล็กทรอนิกส์', 'การบัญชี', 'เทคโนโลยีสารสนเทศ', 
    'การโรงแรม', 'ช่างเชื่อมโลหะ'
];

// จัดการแต่ละขั้นตอน
switch ($step) {
    case 1: // ขั้นตอนตรวจสอบรหัสนักศึกษา
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_code = trim($_POST['student_code'] ?? '');
            
            if (!validateStudentCode($student_code)) {
                $error_message = "รหัสนักศึกษาไม่ถูกต้อง กรุณากรอก 11 หลัก";
                break;
            }
            
            $check_result = checkStudentCodeUniqueness($conn, $student_code);
            
            if ($check_result['exists_in_students']) {
                $error_message = "รหัสนักศึกษานี้มีอยู่ในระบบแล้ว";
                break;
            }
            
            if ($check_result['exists_in_pending']) {
                $error_message = "รหัสนักศึกษานี้อยู่ระหว่างการตรวจสอบ";
                break;
            }
            
            $_SESSION['new_student_code'] = $student_code;
            header('Location: register.php?step=2');
            exit;
        }
        break;
    
    case 2: // ขั้นตอนกรอกข้อมูลส่วนตัว
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'level_system' => $_POST['level_system'] ?? '',
                'level' => $_POST['level'] ?? '',
                'department' => $_POST['department'] ?? '',
                'group_number' => $_POST['group_number'] ?? '',
                'teacher_id' => $_POST['teacher_id'] ?? '',
                'phone_number' => $_POST['phone_number'] ?? '',
                'email' => $_POST['email'] ?? '',
                'gdpr_consent' => isset($_POST['gdpr_consent']) ? 1 : 0
            ];
            
            $validation_result = validateRegistrationData($data);
            
            if ($validation_result['valid']) {
                $conn->begin_transaction();
                
                try {
                    $insert_stmt = $conn->prepare("
                        INSERT INTO student_pending 
                        (student_code, title, first_name, last_name, 
                        level_system, level, department, group_number, 
                        teacher_id, phone_number, email, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'รอการตรวจสอบ')
                    ");
                    
                    $student_code = $_SESSION['new_student_code'] ?? '';
                    $insert_stmt->bind_param(
                        "ssssssssisss", 
                        $student_code, $data['title'], $data['first_name'], $data['last_name'],
                        $data['level_system'], $data['level'], $data['department'], 
                        $data['group_number'], $data['teacher_id'], 
                        $data['phone_number'], $data['email']
                    );
                    
                    if (!$insert_stmt->execute()) {
                        throw new Exception("ไม่สามารถบันทึกข้อมูลได้: " . $insert_stmt->error);
                    }
                    
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = uploadProfilePicture($_FILES['profile_picture'], $student_code);
                        if (!$upload_result['success']) {
                            throw new Exception($upload_result['message']);
                        }
                        
                        $update_pic_stmt = $conn->prepare("UPDATE student_pending SET profile_picture = ? WHERE student_code = ?");
                        $update_pic_stmt->bind_param("ss", $upload_result['path'], $student_code);
                        if (!$update_pic_stmt->execute()) {
                            throw new Exception("ไม่สามารถอัปเดตรูปภาพได้: " . $update_pic_stmt->error);
                        }
                    }
                    
                    $conn->commit();
                    header('Location: register.php?step=3');
                    exit;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = $e->getMessage();
                }
            } else {
                $error_message = $validation_result['message'];
            }
        }
        break;
    
    case 3: // ขั้นตอนสุดท้าย
        $student_code = $_SESSION['new_student_code'] ?? '';
        if (empty($student_code)) {
            $error_message = "ไม่พบข้อมูลการลงทะเบียน กรุณาเริ่มต้นใหม่";
            header('Location: register.php?step=1&error=' . urlencode($error_message));
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM student_pending WHERE student_code = ?");
        $stmt->bind_param("s", $student_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student_data = $result->fetch_assoc();
        } else {
            $error_message = "ไม่พบข้อมูลที่รอการตรวจสอบ";
            header('Location: register.php?step=1&error=' . urlencode($error_message));
            exit;
        }
        break;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - ลงทะเบียนนักเรียน</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    
    <style>
        /* กำหนดตัวแปร CSS */
        :root {
            --primary-color: #06c755;
            --primary-color-dark: #05a647;
            --text-dark: #333;
            --text-light: #666;
            --bg-light: #f5f5f5;
            --border-color: #ddd;
            --white: #ffffff;
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
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }

        .registration-card {
            background-color: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registration-title {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .registration-subtitle {
            color: var(--text-light);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(6, 199, 85, 0.2);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-color-dark);
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .error-message .material-icons {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ขั้นตอนที่ 1: ตรวจสอบรหัสนักศึกษา -->
        <?php if ($step === 1): ?>
            <div class="registration-card">
                <div class="registration-header">
                    <h2 class="registration-title">ตรวจสอบรหัสนักศึกษา</h2>
                    <p class="registration-subtitle">กรอกรหัสนักศึกษา 11 หลัก</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <span class="material-icons">error_outline</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php?step=1">
                    <div class="form-group">
                        <label for="student-code" class="form-label">รหัสนักศึกษา</label>
                        <input 
                            type="text" 
                            id="student-code" 
                            name="student_code" 
                            class="form-input" 
                            placeholder="กรอกรหัสนักศึกษา 11 หลัก" 
                            required 
                            pattern="\d{11}" 
                            maxlength="11" 
                            inputmode="numeric"
                        >
                    </div>
                    <button type="submit" class="btn">ตรวจสอบ</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ขั้นตอนที่ 2: กรอกข้อมูลส่วนตัว -->
        <?php if ($step === 2): ?>
            <div class="registration-card">
                <div class="registration-header">
                    <h2 class="registration-title">กรอกข้อมูลส่วนตัว</h2>
                    <p class="registration-subtitle">ระบุข้อมูลนักเรียน</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <span class="material-icons">error_outline</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php?step=2" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title" class="form-label">คำนำหน้า</label>
                        <select id="title" name="title" class="form-input" required>
                            <option value="">เลือกคำนำหน้า</option>
                            <option value="นาย">นาย</option>
                            <option value="นางสาว">นางสาว</option>
                            <option value="นาง">นาง</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="first_name" class="form-label">ชื่อ</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name" class="form-label">นามสกุล</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="level_system" class="form-label">ระดับการศึกษา</label>
                        <select id="level_system" name="level_system" class="form-input" required>
                            <option value="">เลือกระดับ</option>
                            <option value="ปวช.">ปวช.</option>
                            <option value="ปวส.">ปวส.</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="level" class="form-label">ชั้นปี</label>
                        <select id="level" name="level" class="form-input" required>
                            <option value="">เลือกชั้นปี</option>
                            <option value="ปวช.1">ปวช.1</option>
                            <option value="ปวช.2">ปวช.2</option>
                            <option value="ปวช.3">ปวช.3</option>
                            <option value="ปวส.1">ปวส.1</option>
                            <option value="ปวส.2">ปวส.2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">สาขาวิชา</label>
                        <select id="department" name="department" class="form-input" required>
                            <option value="">เลือกสาขา</option>
                            <?php foreach ($valid_departments as $dept): ?>
                                <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="group_number" class="form-label">กลุ่มเรียน</label>
                        <select id="group_number" name="group_number" class="form-input" required>
                            <option value="">เลือกกลุ่ม</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="teacher_id" class="form-label">ครูที่ปรึกษา</label>
                        <select id="teacher_id" name="teacher_id" class="form-input" required>
                            <option value="">เลือกครู</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['teacher_id']; ?>">
                                    <?php echo $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">เบอร์โทรศัพท์ (ถ้ามี)</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-input" pattern="0\d{9}" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">อีเมล (ถ้ามี)</label>
                        <input type="email" id="email" name="email" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">รูปโปรไฟล์ (ถ้ามี)</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="form-input">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="gdpr_consent" value="1" required>
                            ยินยอมให้เก็บข้อมูลส่วนบุคคลตามนโยบาย GDPR
                        </label>
                    </div>

                    <button type="submit" class="btn">บันทึกข้อมูล</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ขั้นตอนที่ 3: สรุปการลงทะเบียน -->
        <?php if ($step === 3): ?>
            <div class="registration-card">
                <div class="registration-header">
                    <h2 class="registration-title">ลงทะเบียนสำเร็จ</h2>
                    <p class="registration-subtitle">รอการอนุมัติจากเจ้าหน้าที่</p>
                </div>
                <div>
                    <p>รหัสนักศึกษา: <?php echo htmlspecialchars($student_data['student_code']); ?></p>
                    <p>ชื่อ: <?php echo htmlspecialchars($student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name']); ?></p>
                    <p>สถานะ: <?php echo htmlspecialchars($student_data['status']); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
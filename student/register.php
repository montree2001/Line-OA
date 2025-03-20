<?php
/**
 * student/register.php - หน้าลงทะเบียนสำหรับนักเรียน
 */
session_start();
require_once '../config/db_config.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'];
$line_id = $_SESSION['line_id'];
$profile_picture = $_SESSION['profile_picture'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตรวจสอบว่ามีข้อมูลนักเรียนแล้วหรือไม่
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // มีข้อมูลนักเรียนแล้ว นำไปที่หน้า dashboard
    header('Location: dashboard.php');
    exit;
}

// กำหนดค่าเริ่มต้นสำหรับขั้นตอนการลงทะเบียน
$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error_message = '';
$success_message = '';

// ตรวจสอบปีการศึกษาที่ใช้งานอยู่
$academic_year_sql = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
$academic_year_result = $conn->query($academic_year_sql);
if ($academic_year_result->num_rows > 0) {
    $academic_year_row = $academic_year_result->fetch_assoc();
    $current_academic_year_id = $academic_year_row['academic_year_id'];
} else {
    $error_message = "ไม่พบข้อมูลปีการศึกษาที่ใช้งานอยู่ กรุณาติดต่อผู้ดูแลระบบ";
    $step = 'error';
}

// จัดการข้อมูลที่ส่งมา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2: // ขั้นตอนค้นหารหัสนักศึกษา
            $student_code = $_POST['student_code'] ?? '';
            
            if (empty($student_code) || strlen($student_code) !== 11) {
                $error_message = "กรุณากรอกรหัสนักศึกษา 11 หลักให้ถูกต้อง";
            } else {
                // ตรวจสอบว่ามีข้อมูลนักศึกษาในฐานข้อมูลหรือไม่ (ในตาราง student_pending)
                $query = "SELECT * FROM student_pending WHERE student_code = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $student_code);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // พบข้อมูลนักศึกษาในตาราง student_pending
                    $student_data = $result->fetch_assoc();
                    
                    // บันทึกข้อมูลทั้งหมดไว้ใน session
                    $_SESSION['student_code'] = $student_code;
                    $_SESSION['student_title'] = $student_data['title'];
                    $_SESSION['student_first_name'] = $student_data['first_name'];
                    $_SESSION['student_last_name'] = $student_data['last_name'];
                    $_SESSION['student_level_system'] = $student_data['level_system'];
                    $_SESSION['student_class_level'] = $student_data['class_level'];
                    $_SESSION['student_department'] = $student_data['department'];
                    $_SESSION['student_group_number'] = $student_data['group_number'];
                    
                    // ไปยังขั้นตอนถัดไป
                    header('Location: register.php?step=3');
                    exit;
                } else {
                    // ไม่พบข้อมูลนักศึกษา ให้ไปยังขั้นตอนกรอกข้อมูลเอง
                    $_SESSION['student_code'] = $student_code;
                    header('Location: register.php?step=3manual');
                    exit;
                }
            }
            break;
            
        case "3manual": // ขั้นตอนกรอกข้อมูลนักศึกษาเอง
            $title = $_POST['title'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $level_system = $_POST['level_system'] ?? '';
            $class_level = $_POST['class_level'] ?? '';
            
            if (empty($title) || empty($first_name) || empty($last_name) || empty($level_system) || empty($class_level)) {
                $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
            } else {
                // บันทึกข้อมูลใน session
                $_SESSION['student_title'] = $title;
                $_SESSION['student_first_name'] = $first_name;
                $_SESSION['student_last_name'] = $last_name;
                $_SESSION['student_level_system'] = $level_system;
                $_SESSION['student_class_level'] = $class_level;
                
                // ไปยังขั้นตอนค้นหาครูที่ปรึกษา
                header('Location: register.php?step=4');
                exit;
            }
            break;
            
        case 4: // ขั้นตอนค้นหาครูที่ปรึกษา
            $teacher_name = $_POST['teacher_name'] ?? '';
            
            if (empty($teacher_name)) {
                $error_message = "กรุณากรอกชื่อครูที่ปรึกษา";
            } else {
                // ค้นหาครูที่ปรึกษาจากชื่อ
                $query = "SELECT t.teacher_id, u.first_name, u.last_name, t.department 
                         FROM teachers t 
                         JOIN users u ON t.user_id = u.user_id 
                         WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE ?";
                $stmt = $conn->prepare($query);
                $search_term = "%" . $teacher_name . "%";
                $stmt->bind_param("s", $search_term);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // พบครูที่ปรึกษา แสดงชั้นเรียนที่ครูดูแล
                    $_SESSION['search_teacher_name'] = $teacher_name;
                    $_SESSION['search_teacher_results'] = array();
                    
                    while ($row = $result->fetch_assoc()) {
                        $_SESSION['search_teacher_results'][] = $row;
                    }
                    
                    // ไปยังขั้นตอนเลือกครูที่ปรึกษาและชั้นเรียน
                    header('Location: register.php?step=5');
                    exit;
                } else {
                    // ไม่พบครูที่ปรึกษา ให้ไปยังขั้นตอนกรอกข้อมูลห้องเรียนเอง
                    header('Location: register.php?step=5manual');
                    exit;
                }
            }
            break;
            
        case 5: // ขั้นตอนเลือกครูที่ปรึกษาและชั้นเรียน
            $teacher_id = $_POST['teacher_id'] ?? '';
            $class_id = $_POST['class_id'] ?? '';
            
            if (empty($teacher_id) || empty($class_id)) {
                $error_message = "กรุณาเลือกครูที่ปรึกษาและชั้นเรียน";
            } else {
                // บันทึกข้อมูลใน session
                $_SESSION['selected_teacher_id'] = $teacher_id;
                $_SESSION['selected_class_id'] = $class_id;
                
                // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติม
                header('Location: register.php?step=6');
                exit;
            }
            break;
            
        case "5manual": // ขั้นตอนกรอกข้อมูลห้องเรียนเอง
            $department = $_POST['department'] ?? '';
            $group_number = $_POST['group_number'] ?? '';
            
            if (empty($department) || empty($group_number)) {
                $error_message = "กรุณากรอกข้อมูลสาขาวิชาและกลุ่มเรียนให้ครบถ้วน";
            } else {
                // บันทึกข้อมูลใน session
                $_SESSION['student_department'] = $department;
                $_SESSION['student_group_number'] = $group_number;
                
                // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติม
                header('Location: register.php?step=6');
                exit;
            }
            break;
            
        case 6: // ขั้นตอนกรอกข้อมูลเพิ่มเติม
            $phone_number = $_POST['phone_number'] ?? '';
            $email = $_POST['email'] ?? '';
            $gdpr_consent = isset($_POST['gdpr_consent']) ? 1 : 0;
            
            // ตรวจสอบการยินยอมเก็บข้อมูลส่วนบุคคล
            if (!$gdpr_consent) {
                $error_message = "กรุณายินยอมให้เก็บข้อมูลส่วนบุคคลเพื่อดำเนินการต่อ";
            } else {
                // เริ่มต้น transaction
                $conn->begin_transaction();
                
                try {
                    // 1. อัปเดตข้อมูลในตาราง users
                    $update_user_sql = "UPDATE users SET 
                                       title = ?, 
                                       first_name = ?, 
                                       last_name = ?, 
                                       phone_number = ?, 
                                       email = ?, 
                                       gdpr_consent = ?, 
                                       gdpr_consent_date = NOW() 
                                       WHERE user_id = ?";
                    
                    $user_stmt = $conn->prepare($update_user_sql);
                    $user_stmt->bind_param(
                        "sssssii", 
                        $_SESSION['student_title'], 
                        $_SESSION['student_first_name'], 
                        $_SESSION['student_last_name'], 
                        $phone_number, 
                        $email, 
                        $gdpr_consent, 
                        $user_id
                    );
                    $user_stmt->execute();
                    
                    // 2. ตรวจสอบว่ามี class_id ที่เลือกไว้หรือไม่
                    $class_id = null;
                    if (isset($_SESSION['selected_class_id'])) {
                        // ใช้ class_id ที่เลือกไว้
                        $class_id = $_SESSION['selected_class_id'];
                    } else {
                        // สร้างชั้นเรียนใหม่
                        $level = $_SESSION['student_level_system'] . $_SESSION['student_class_level'];
                        $new_class_sql = "INSERT INTO classes (academic_year_id, level, department, group_number, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())";
                        $class_stmt = $conn->prepare($new_class_sql);
                        $class_stmt->bind_param(
                            "issi", 
                            $current_academic_year_id, 
                            $level, 
                            $_SESSION['student_department'], 
                            $_SESSION['student_group_number']
                        );
                        $class_stmt->execute();
                        $class_id = $conn->insert_id;
                    }
                    
                    // 3. เพิ่มข้อมูลในตาราง students
                    $insert_student_sql = "INSERT INTO students (
                                          user_id, 
                                          student_code, 
                                          title, 
                                          level_system, 
                                          current_class_id, 
                                          status, 
                                          created_at
                                        ) VALUES (?, ?, ?, ?, ?, 'กำลังศึกษา', NOW())";
                    
                    $student_stmt = $conn->prepare($insert_student_sql);
                    $student_stmt->bind_param(
                        "isssi", 
                        $user_id, 
                        $_SESSION['student_code'], 
                        $_SESSION['student_title'], 
                        $_SESSION['student_level_system'], 
                        $class_id
                    );
                    $student_stmt->execute();
                    $student_id = $conn->insert_id;
                    
                    // 4. สร้างบันทึกประวัติวิชาการ
                    $record_sql = "INSERT INTO student_academic_records (
                                  student_id, 
                                  academic_year_id, 
                                  class_id, 
                                  total_attendance_days, 
                                  total_absence_days, 
                                  created_at
                                ) VALUES (?, ?, ?, 0, 0, NOW())";
                    
                    $record_stmt = $conn->prepare($record_sql);
                    $record_stmt->bind_param(
                        "iii", 
                        $student_id, 
                        $current_academic_year_id, 
                        $class_id
                    );
                    $record_stmt->execute();
                    
                    // 5. ถ้ามีการอัปโหลดรูปภาพ ให้อัปเดตรูปโปรไฟล์
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../uploads/profiles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $new_filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                            $profile_url = 'uploads/profiles/' . $new_filename;
                            $profile_update_sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
                            $profile_stmt = $conn->prepare($profile_update_sql);
                            $profile_stmt->bind_param("si", $profile_url, $user_id);
                            $profile_stmt->execute();
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // ล้าง session ที่ไม่จำเป็น
                    unset($_SESSION['student_code']);
                    unset($_SESSION['student_title']);
                    unset($_SESSION['student_first_name']);
                    unset($_SESSION['student_last_name']);
                    unset($_SESSION['student_level_system']);
                    unset($_SESSION['student_class_level']);
                    unset($_SESSION['student_department']);
                    unset($_SESSION['student_group_number']);
                    unset($_SESSION['search_teacher_name']);
                    unset($_SESSION['search_teacher_results']);
                    unset($_SESSION['selected_teacher_id']);
                    unset($_SESSION['selected_class_id']);
                    
                    // ไปยังขั้นตอนเสร็จสิ้น
                    header('Location: register.php?step=7');
                    exit;
                    
                } catch (Exception $e) {
                    // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
                    $conn->rollback();
                    $error_message = "เกิดข้อผิดพลาดในการลงทะเบียน: " . $e->getMessage();
                }
            }
            break;
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// รวมหน้าแบบฟอร์มสำหรับแต่ละขั้นตอน
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>STD-Prasat - ลงทะเบียนนักเรียน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/register.css" rel="stylesheet">
</head>
<body>
    <!-- ส่วนหัว -->
    <div class="header">
        <?php if ($step < 7): ?>
        <button class="header-icon" onclick="history.back()">
            <span class="material-icons">arrow_back</span>
        </button>
        <?php else: ?>
        <div class="header-spacer"></div>
        <?php endif; ?>
        
        <h1>
            <?php 
                if ($step === 1) echo "เข้าสู่ระบบ";
                elseif ($step === 2) echo "ค้นหารหัสนักศึกษา";
                elseif ($step === 3 || $step === '3manual') echo "ข้อมูลนักศึกษา";
                elseif ($step === 4 || $step === 5 || $step === '5manual') echo "ข้อมูลชั้นเรียน";
                elseif ($step === 6) echo "ข้อมูลเพิ่มเติม";
                else echo "ลงทะเบียนเสร็จสิ้น";
            ?>
        </h1>
        
        <div class="header-spacer"></div>
    </div>
    
    <div class="container">
        <!-- Step Indicator -->
        <div class="steps">
            <div class="step <?php echo ($step >= 1) ? 'completed' : ''; ?> <?php echo ($step === 1) ? 'active' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-title">เข้าสู่ระบบ</div>
            </div>
            <div class="step-line <?php echo ($step > 1) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step > 2) ? 'completed' : ''; ?> <?php echo ($step === 2) ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-title">รหัสนักศึกษา</div>
            </div>
            <div class="step-line <?php echo ($step > 3) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step > 4) ? 'completed' : ''; ?> <?php echo (in_array($step, [3, '3manual', 4])) ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-title">ชั้นเรียน</div>
            </div>
            <div class="step-line <?php echo ($step > 6) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step > 6) ? 'completed' : ''; ?> <?php echo (in_array($step, [5, '5manual', 6])) ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-title">ข้อมูลเพิ่มเติม</div>
            </div>
            <div class="step-line <?php echo ($step === 7) ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo ($step === 7) ? 'active' : ''; ?>">
                <div class="step-number">5</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>
        
        <!-- แสดงข้อความข้อผิดพลาด -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- แสดงข้อความสำเร็จ -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- แสดงเนื้อหาตามขั้นตอน -->
        <?php if ($step === 1): ?>
            <!-- ขั้นตอนเข้าสู่ระบบ (กำหนดว่าได้เข้าสู่ระบบด้วย LINE แล้ว) -->
            <div class="card">
                <div class="card-title">เข้าสู่ระบบด้วย LINE</div>
                <div class="card-content">
                    <div class="text-center mb-20">
                        <span class="material-icons success-icon">check_circle</span>
                        <h3 class="mb-10">เข้าสู่ระบบสำเร็จแล้ว</h3>
                        <p>ระบบตรวจพบว่านี่เป็นการเข้าใช้งานครั้งแรกของคุณ</p>
                        <p>กรุณาดำเนินการลงทะเบียนให้เสร็จสมบูรณ์</p>
                    </div>
                    
                    <p class="help-text text-center">ในขั้นตอนถัดไป คุณจะต้องกรอกข้อมูลรหัสนักศึกษาเพื่อตรวจสอบข้อมูล</p>
                    
                    <div class="mt-30">
                        <button class="btn primary" onclick="window.location.href='register.php?step=2'">
                            ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php elseif ($step === 2): ?>
            <!-- ขั้นตอนค้นหารหัสนักศึกษา -->
            <div class="card">
                <div class="card-title">กรอกรหัสนักศึกษา</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=2">
                        <div class="input-container">
                            <label class="input-label">รหัสนักศึกษา (11 หลัก)</label>
                            <input type="text" class="input-field" placeholder="กรอกรหัสนักศึกษา 11 หลัก" maxlength="11" name="student_code" pattern="[0-9]{11}" inputmode="numeric" required>
                            <div class="help-text">กรุณากรอกเฉพาะตัวเลข 11 หลัก</div>
                        </div>
                        
                        <button type="submit" class="btn primary">
                            <span class="material-icons">search</span> ค้นหาข้อมูล
                        </button>
                        
                        <div class="contact-admin">
                            หากมีปัญหาในการค้นหาข้อมูล กรุณา<a href="#">ติดต่อเจ้าหน้าที่</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === "3manual"): ?>
            <!-- ขั้นตอนกรอกข้อมูลนักศึกษาเอง -->
            <div class="card">
                <div class="card-title">กรอกข้อมูลนักศึกษา</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=3manual">
                        <div class="input-container">
                            <label class="input-label">รหัสนักศึกษา</label>
                            <input type="text" class="input-field" value="<?php echo isset($_SESSION['student_code']) ? $_SESSION['student_code'] : ''; ?>" readonly>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">คำนำหน้า</label>
                            <select class="input-field" name="title" required>
                                <option value="" disabled selected>เลือกคำนำหน้า</option>
                                <option value="นาย">นาย</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">ชื่อ</label>
                            <input type="text" class="input-field" name="first_name" placeholder="กรอกชื่อ" required>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">นามสกุล</label>
                            <input type="text" class="input-field" name="last_name" placeholder="กรอกนามสกุล" required>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">ระดับการศึกษา</label>
                            <select class="input-field" name="level_system" id="level-system" required onchange="updateClassLevels()">
                                <option value="" disabled selected>เลือกระดับการศึกษา</option>
                                <option value="ปวช.">ปวช.</option>
                                <option value="ปวส.">ปวส.</option>
                            </select>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">ชั้นปี</label>
                            <select class="input-field" name="class_level" id="class-level" required>
                                <option value="" disabled selected>เลือกชั้นปี</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn primary">
                            ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                        </button>
                    </form>
                </div>
            </div>
            
            <script>
                function updateClassLevels() {
                    const levelSystem = document.getElementById('level-system').value;
                    const classLevelSelect = document.getElementById('class-level');
                    
                    // ล้างตัวเลือกเดิม
                    classLevelSelect.innerHTML = '<option value="" disabled selected>เลือกชั้นปี</option>';
                    
                    if (levelSystem === 'ปวช.') {
                        for (let i = 1; i <= 3; i++) {
                            const option = document.createElement('option');
                            option.value = i;
                            option.textContent = i;
                            classLevelSelect.appendChild(option);
                        }
                    } else if (levelSystem === 'ปวส.') {
                        for (let i = 1; i <= 2; i++) {
                            const option = document.createElement('option');
                            option.value = i;
                            option.textContent = i;
                            classLevelSelect.appendChild(option);
                        }
                    }
                }
            </script>
        <?php elseif ($step === 7): ?>
            <!-- ขั้นตอนลงทะเบียนเสร็จสิ้น -->
            <div class="card success-card">
                <!-- Success Icon -->
                <div class="success-icon">
                    <span class="material-icons">check</span>
                </div>
                
                <!-- Success Message -->
                <div class="success-message">
                    <h2>ลงทะเบียนเสร็จสิ้น</h2>
                    <p>คุณได้ลงทะเบียนเข้าใช้งานระบบ STD-Prasat เรียบร้อยแล้ว</p>
                    <p>ตอนนี้คุณสามารถเริ่มใช้งานระบบเช็คชื่อเข้าแถวออนไลน์ได้ทันที</p>
                </div>
                
                <!-- Features Section -->
                <div class="features-section">
                    <div class="features-title">คุณสามารถทำอะไรได้บ้าง?</div>
                    <div class="feature-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">how_to_reg</span>
                            </div>
                            <div class="feature-title">เช็คชื่อ</div>
                            <div class="feature-desc">เช็คชื่อด้วย GPS, PIN หรือ QR Code</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">history</span>
                            </div>
                            <div class="feature-title">ประวัติ</div>
                            <div class="feature-desc">ดูประวัติการเช็คชื่อย้อนหลัง</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">assessment</span>
                            </div>
                            <div class="feature-title">สถิติ</div>
                            <div class="feature-desc">ดูสถิติการเข้าแถวของตนเอง</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <span class="material-icons">notifications</span>
                            </div>
                            <div class="feature-title">การแจ้งเตือน</div>
                            <div class="feature-desc">รับการแจ้งเตือนผ่าน LINE</div>
                        </div>
                    </div>
                </div>
                
                <!-- Start Button -->
                <button class="btn primary" onclick="window.location.href='dashboard.php'">
                    เริ่มใช้งานระบบ
                    <span class="material-icons">arrow_forward</span>
                </button>
            </div>
        <?php elseif ($step === 'error'): ?>
            <!-- แสดงข้อผิดพลาด -->
            <div class="card error-card">
                <div class="error-icon">
                    <span class="material-icons">error</span>
                </div>
                <div class="error-message">
                    <h2>เกิดข้อผิดพลาด</h2>
                    <p><?php echo $error_message; ?></p>
                </div>
                <button class="btn secondary" onclick="window.location.href='../index.php'">
                    กลับไปหน้าหลัก
                </button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php elseif ($step === 3): ?>
            <!-- ขั้นตอนยืนยันข้อมูลนักศึกษา -->
            <div class="card">
                <div class="card-title">ยืนยันข้อมูลนักศึกษา</div>
                <div class="card-content">
                    <div class="profile-info-section">
                        <h3>ข้อมูลนักศึกษา</h3>
                        <div class="info-item">
                            <div class="info-label">รหัสนักศึกษา:</div>
                            <div class="info-value"><?php echo $_SESSION['student_code']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ชื่อ-นามสกุล:</div>
                            <div class="info-value"><?php echo $_SESSION['student_title'] . ' ' . $_SESSION['student_first_name'] . ' ' . $_SESSION['student_last_name']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ระดับการศึกษา:</div>
                            <div class="info-value"><?php echo $_SESSION['student_level_system'] . $_SESSION['student_class_level']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">สาขาวิชา:</div>
                            <div class="info-value"><?php echo $_SESSION['student_department']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">กลุ่มเรียน:</div>
                            <div class="info-value"><?php echo $_SESSION['student_group_number']; ?></div>
                        </div>
                    </div>
                    
                    <form method="POST" action="register.php?step=4">
                        <input type="hidden" name="confirm" value="1">
                        <button type="submit" class="btn primary">
                            ยืนยันข้อมูล <span class="material-icons">check</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 4): ?>
            <!-- ขั้นตอนค้นหาครูที่ปรึกษา -->
            <div class="card">
                <div class="card-title">ค้นหาครูที่ปรึกษา</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=4">
                        <div class="input-container">
                            <label class="input-label">ค้นหาจากชื่อครูที่ปรึกษา</label>
                            <input type="text" class="input-field" name="teacher_name" placeholder="กรอกชื่อหรือนามสกุลครูที่ปรึกษา" required>
                            <div class="help-text">เช่น สมชาย, ใจดี, อาจารย์วันดี</div>
                        </div>
                        
                        <button type="submit" class="btn primary">
                            <span class="material-icons">search</span> ค้นหาครูที่ปรึกษา
                        </button>
                        
                        <div class="skip-section">
                            <p>หากไม่ทราบชื่อครูที่ปรึกษา คุณสามารถ</p>
                            <a href="register.php?step=5manual" class="text-link">ระบุข้อมูลชั้นเรียนเอง</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 5): ?>
            <!-- ขั้นตอนเลือกครูที่ปรึกษาและชั้นเรียน -->
            <div class="card">
                <div class="card-title">เลือกครูที่ปรึกษาและชั้นเรียน</div>
                <div class="card-content">
                    <p>ผลการค้นหา: <?php echo count($_SESSION['search_teacher_results']); ?> รายการ</p>
                    
                    <?php if (empty($_SESSION['search_teacher_results'])): ?>
                        <div class="no-results">
                            <p>ไม่พบข้อมูลครูที่ปรึกษา</p>
                            <a href="register.php?step=5manual" class="btn secondary">ระบุข้อมูลชั้นเรียนเอง</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="register.php?step=5">
                            <div class="teacher-list">
                                <?php foreach ($_SESSION['search_teacher_results'] as $key => $teacher): ?>
                                <div class="teacher-card">
                                    <div class="radio-container">
                                        <input type="radio" name="teacher_id" id="teacher_<?php echo $teacher['teacher_id']; ?>" value="<?php echo $teacher['teacher_id']; ?>" required>
                                        <label for="teacher_<?php echo $teacher['teacher_id']; ?>" class="radio-label">
                                            <div class="teacher-name"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></div>
                                            <div class="teacher-department"><?php echo $teacher['department']; ?></div>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="input-container">
                                <label class="input-label">เลือกชั้นเรียน</label>
                                <select class="input-field" name="class_id" required>
                                    <option value="" disabled selected>เลือกชั้นเรียน</option>
                                    <?php 
                                    // ดึงข้อมูลชั้นเรียนของครูที่ปรึกษา
                                    $first_teacher = $_SESSION['search_teacher_results'][0]['teacher_id'];
                                    $classes_sql = "SELECT c.class_id, c.level, c.department, c.group_number 
                                                  FROM classes c 
                                                  JOIN class_advisors ca ON c.class_id = ca.class_id
                                                  WHERE ca.teacher_id = $first_teacher
                                                  AND c.academic_year_id = $current_academic_year_id";
                                    $classes_result = $conn->query($classes_sql);
                                    
                                    if ($classes_result->num_rows > 0) {
                                        while ($class = $classes_result->fetch_assoc()) {
                                            echo "<option value='" . $class['class_id'] . "'>" 
                                                . $class['level'] . " สาขา" . $class['department'] 
                                                . " กลุ่ม " . $class['group_number'] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn primary">
                                ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                            </button>
                        </form>
                        
                        <div class="skip-section">
                            <p>หากไม่พบชั้นเรียนที่ต้องการ คุณสามารถ</p>
                            <a href="register.php?step=5manual" class="text-link">ระบุข้อมูลชั้นเรียนเอง</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($step === "5manual"): ?>
            <!-- ขั้นตอนกรอกข้อมูลห้องเรียนเอง -->
            <div class="card">
                <div class="card-title">ระบุข้อมูลชั้นเรียน</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=5manual">
                        <div class="input-container">
                            <label class="input-label">สาขาวิชา</label>
                            <select class="input-field" name="department" required>
                                <option value="" disabled selected>เลือกสาขาวิชา</option>
                                <option value="ช่างยนต์">สาขาวิชาช่างยนต์</option>
                                <option value="ช่างกลโรงงาน">สาขาวิชาช่างกลโรงงาน</option>
                                <option value="ช่างไฟฟ้ากำลัง">สาขาวิชาช่างไฟฟ้ากำลัง</option>
                                <option value="ช่างอิเล็กทรอนิกส์">สาขาวิชาช่างอิเล็กทรอนิกส์</option>
                                <option value="การบัญชี">สาขาวิชาการบัญชี</option>
                                <option value="เทคโนโลยีสารสนเทศ">สาขาวิชาเทคโนโลยีสารสนเทศ</option>
                                <option value="การโรงแรม">สาขาวิชาการโรงแรม</option>
                                <option value="ช่างเชื่อมโลหะ">สาขาวิชาช่างเชื่อมโลหะ</option>
                            </select>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">กลุ่มเรียน</label>
                            <select class="input-field" name="group_number" required>
                                <option value="" disabled selected>เลือกกลุ่มเรียน</option>
                                <option value="1">กลุ่ม 1</option>
                                <option value="2">กลุ่ม 2</option>
                                <option value="3">กลุ่ม 3</option>
                                <option value="4">กลุ่ม 4</option>
                                <option value="5">กลุ่ม 5</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn primary">
                            ดำเนินการต่อ <span class="material-icons">arrow_forward</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($step === 6): ?>
            <!-- ขั้นตอนกรอกข้อมูลเพิ่มเติม -->
            <div class="card">
                <div class="card-title">กรอกข้อมูลเพิ่มเติม</div>
                <div class="card-content">
                    <form method="POST" action="register.php?step=6" enctype="multipart/form-data">
                        <div class="input-container">
                            <label class="input-label">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                            <input type="tel" class="input-field" name="phone_number" placeholder="กรอกเบอร์โทรศัพท์" pattern="[0-9]{10}" maxlength="10">
                            <div class="help-text">กรอกเฉพาะตัวเลข 10 หลัก</div>
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">อีเมล (ไม่บังคับ)</label>
                            <input type="email" class="input-field" name="email" placeholder="example@email.com">
                        </div>
                        
                        <div class="input-container">
                            <label class="input-label">รูปโปรไฟล์ (ไม่บังคับ)</label>
                            <div class="upload-area" onclick="document.getElementById('profile_picture').click()">
                                <input type="file" id="profile_picture" name="profile_picture" style="display: none;" accept="image/*">
                                <div class="upload-icon">
                                    <span class="material-icons">cloud_upload</span>
                                </div>
                                <div class="upload-text">คลิกเพื่ออัพโหลดรูปโปรไฟล์</div>
                                <div class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</div>
                            </div>
                            <div id="image-preview" style="display: none;">
                                <img id="preview-img" src="#" alt="รูปโปรไฟล์" style="max-width: 100%; border-radius: 5px;">
                                <button type="button" class="btn secondary" onclick="resetImage()">
                                    <span class="material-icons">refresh</span> เลือกรูปใหม่
                                </button>
                            </div>
                        </div>
                        
                        <div class="checkbox-container">
                            <input type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
                            <label for="gdpr_consent" class="checkbox-label">
                                ข้าพเจ้ายินยอมให้วิทยาลัยการอาชีพปราสาทเก็บข้อมูลส่วนบุคคลของข้าพเจ้า เพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์
                                <a href="#" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn primary">
                            ลงทะเบียน <span class="material-icons">check</span>
                        </button>
                    </form>
                </div>
            </div>
            
            <script>
                // แสดงตัวอย่างรูปภาพ
                document.getElementById('profile_picture').addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        var reader = new FileReader();
                        
                        reader.onload = function(e) {
                            document.getElementById('preview-img').src = e.target.result;
                            document.getElementById('image-preview').style.display = 'block';
                            document.querySelector('.upload-area').style.display = 'none';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
                
                // รีเซ็ตรูปภาพ
                function resetImage() {
                    document.getElementById('profile_picture').value = '';
                    document.getElementById('image-preview').style.display = 'none';
                    document.querySelector('.upload-area').style.display = 'block';
                }
                
                // แสดงนโยบายความเป็นส่วนตัว
                function showPrivacyPolicy() {
                    alert('นโยบายความเป็นส่วนตัวของวิทยาลัยการอาชีพปราสาท\n\nวิทยาลัยการอาชีพปราสาทจะเก็บรวบรวมข้อมูลส่วนบุคคลของนักเรียนเพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์เท่านั้น โดยจะไม่เปิดเผยข้อมูลต่อบุคคลที่สาม ยกเว้นในกรณีที่จำเป็นต้องปฏิบัติตามกฎหมาย');
                }
            </script>
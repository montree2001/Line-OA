<?php
/**
 * SADD-Prasat - ระบบผู้ปกครอง
 * หน้าแสดงรายชื่อครูที่ปรึกษาของนักเรียนในความดูแล
 */

// เริ่มต้น Session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้าล็อกอิน
    header('Location: ../index.php');
    exit;
}

// กำหนดค่าเริ่มต้น
$page_title = 'SADD-Prasat - ครูที่ปรึกษา';
$current_page = 'dashboard';
$extra_css = [
    'assets/css/parent-dashboard.css'
];
$extra_js = [
    'assets/js/parent-dashboard.js'
];

// เชื่อมต่อกับฐานข้อมูล
require_once '../config/db_config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ดึงข้อมูลผู้ปกครอง
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // ถ้ายังไม่ได้ลงทะเบียนเป็นผู้ปกครอง ให้ไปที่หน้าลงทะเบียน
    header('Location: register.php');
    exit;
} else {
    $parent_data = $result->fetch_assoc();
    $parent_id = $parent_data['parent_id'];
}
$stmt->close();

// สร้างฟังก์ชันดึงข้อมูลครูที่ปรึกษาทั้งหมด
function getAllTeachers($conn, $parent_id) {
    $teachers = [];
    
    // SQL เพื่อดึงข้อมูลครูที่ปรึกษาทั้งหมดของนักเรียนในความดูแล
    $sql = "SELECT DISTINCT t.teacher_id, t.title, t.first_name, t.last_name, 
                  u.phone_number, d.department_name, c.level, c.group_number,
                  s.student_id, su.first_name as student_first_name, su.last_name as student_last_name,
                  s.title as student_title
           FROM parent_student_relation psr
           JOIN students s ON psr.student_id = s.student_id
           JOIN users su ON s.user_id = su.user_id
           JOIN classes c ON s.current_class_id = c.class_id
           JOIN class_advisors ca ON c.class_id = ca.class_id
           JOIN teachers t ON ca.teacher_id = t.teacher_id
           JOIN users u ON t.user_id = u.user_id
           JOIN departments d ON t.department_id = d.department_id
           WHERE psr.parent_id = ? AND s.status = 'กำลังศึกษา'
           ORDER BY s.student_id, ca.is_primary DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $teacher_full_name = $row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name'];
            $position = 'ครูที่ปรึกษา ' . $row['level'] . '/' . $row['group_number'] . ' แผนก' . $row['department_name'];
            $student_full_name = $row['student_title'] . ' ' . $row['student_first_name'] . ' ' . $row['student_last_name'];
            
            // ตรวจสอบว่าครูคนนี้มีอยู่ในรายการหรือไม่
            $found = false;
            foreach ($teachers as &$teacher) {
                if ($teacher['id'] === $row['teacher_id']) {
                    // ถ้ามีแล้ว ให้เพิ่มเฉพาะชื่อนักเรียนถ้ายังไม่มี
                    if (!in_array($student_full_name, $teacher['students'])) {
                        $teacher['students'][] = $student_full_name;
                    }
                    $found = true;
                    break;
                }
            }
            
            // ถ้ายังไม่มี ให้เพิ่มใหม่
            if (!$found) {
                $teachers[] = [
                    'id' => $row['teacher_id'],
                    'name' => $teacher_full_name,
                    'position' => $position,
                    'phone' => $row['phone_number'],
                    'line_id' => '@teacher_prasat', // ข้อมูลสมมติ
                    'students' => [$student_full_name]
                ];
            }
        }
    }
    $stmt->close();
    
    return $teachers;
}

// ดึงข้อมูลที่จำเป็น
$teachers = getAllTeachers($conn, $parent_id);

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/parent-style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <?php if(isset($extra_css)): ?>
        <?php foreach($extra_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="header">
        <div class="app-name">SADD-Prasat</div>
        <div class="header-icons">
            <span class="material-icons">notifications</span>
            <span class="material-icons">account_circle</span>
        </div>
    </div>

    <div class="container">
        <!-- ปุ่มย้อนกลับ -->
        <div class="back-button-container">
            <a href="home.php" class="back-button">
                <span class="material-icons">arrow_back</span>
                <span>กลับไปหน้าหลัก</span>
            </a>
        </div>

        <!-- หัวข้อหน้า -->
        <div class="page-title">
            <h1>ครูที่ปรึกษา</h1>
            <p>รายชื่อครูที่ปรึกษาของบุตรหลานในความดูแลของคุณ</p>
        </div>

        <!-- รายการครูที่ปรึกษา -->
        <div class="teachers-list">
            <?php if(isset($teachers) && !empty($teachers)): ?>
                <?php foreach($teachers as $teacher): ?>
                    <div class="teacher-card">
                        <div class="teacher-info">
                            <div class="teacher-avatar">
                                <?php if(isset($teacher['avatar']) && !empty($teacher['avatar'])): ?>
                                    <img src="<?php echo $teacher['avatar']; ?>" alt="<?php echo $teacher['name']; ?>">
                                <?php else: ?>
                                    <span class="material-icons">person</span>
                                <?php endif; ?>
                            </div>
                            <div class="teacher-details">
                                <div class="teacher-name"><?php echo $teacher['name']; ?></div>
                                <div class="teacher-position"><?php echo $teacher['position']; ?></div>
                                
                                <div class="teacher-contact-info">
                                    <div class="contact-item">
                                        <span class="material-icons">phone</span>
                                        <span><?php echo $teacher['phone']; ?></span>
                                    </div>
                                    <div class="contact-item">
                                        <span class="material-icons">chat</span>
                                        <span>LINE ID: <?php echo $teacher['line_id']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="teacher-students">
                                    <div class="students-label">นักเรียนในความดูแล:</div>
                                    <div class="students-list">
                                        <?php echo implode(', ', $teacher['students']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-buttons">
                            <a href="tel:<?php echo $teacher['phone']; ?>" class="contact-button call">
                                <span class="material-icons">call</span> โทร
                            </a>
                            <a href="messages.php?teacher=<?php echo $teacher['id']; ?>" class="contact-button message">
                                <span class="material-icons">chat</span> ข้อความ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">
                        <span class="material-icons">person_off</span>
                    </div>
                    <div class="no-data-message">ไม่พบข้อมูลครูที่ปรึกษาในขณะนี้</div>
                    <div class="no-data-action">
                        <a href="students.php" class="action-button">
                            <span class="material-icons">people</span>
                            จัดการนักเรียนในความดูแล
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- แถบนำทางด้านล่าง -->
    <div class="bottom-nav">
        <a href="home.php" class="nav-item">
            <span class="material-icons nav-icon">home</span>
            <span>หน้าหลัก</span>
        </a>
        <a href="students.php" class="nav-item">
            <span class="material-icons nav-icon">child_care</span>
            <span>นักเรียน</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="material-icons nav-icon">person</span>
            <span>โปรไฟล์</span>
        </a>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/parent-main.js"></script>
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
    /* สไตล์สำหรับหน้าครูที่ปรึกษา */
    .back-button-container {
        margin-bottom: 20px;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .back-button:hover {
        color: var(--primary-color-dark);
    }

    .back-button .material-icons {
        margin-right: 5px;
    }

    .page-title {
        margin-bottom: 20px;
    }

    .page-title h1 {
        font-size: 24px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 5px;
    }

    .page-title p {
        color: var(--text-light);
        font-size: 16px;
    }

    .teachers-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 80px; /* ให้มีพื้นที่สำหรับ bottom-nav */
    }

    .teacher-card {
        background-color: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--card-shadow);
    }

    .teacher-info {
        display: flex;
        margin-bottom: 20px;
    }

    .teacher-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: var(--secondary-color-light);
        margin-right: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--secondary-color);
        font-size: 36px;
        overflow: hidden;
    }

    .teacher-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .teacher-details {
        flex: 1;
    }

    .teacher-name {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 5px;
    }

    .teacher-position {
        color: var(--text-light);
        margin-bottom: 10px;
    }

    .teacher-contact-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 10px;
    }

    .contact-item {
        display: flex;
        align-items: center;
        color: var(--text-light);
    }

    .contact-item .material-icons {
        font-size: 18px;
        margin-right: 5px;
        color: var(--primary-color);
    }

    .teacher-students {
        margin-top: 5px;
    }

    .students-label {
        font-weight: 500;
        color: var(--text-dark);
        margin-bottom: 3px;
    }

    .students-list {
        color: var(--text-light);
    }

    .contact-buttons {
        display: flex;
        gap: 10px;
    }

    .contact-button {
        flex: 1;
        padding: 12px 0;
        border: none;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background-color var(--transition-speed);
    }

    .contact-button.call {
        background-color: var(--success-color-light);
        color: var(--success-color);
    }

    .contact-button.call:hover {
        background-color: #d7f0d8;
    }

    .contact-button.message {
        background-color: var(--secondary-color-light);
        color: var(--secondary-color);
    }

    .contact-button.message:hover {
        background-color: #d2e8fd;
    }

    .contact-button .material-icons {
        margin-right: 8px;
        font-size: 18px;
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        background-color: white;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
    }

    .no-data-icon {
        margin-bottom: 15px;
    }

    .no-data-icon .material-icons {
        font-size: 48px;
        color: #e0e0e0;
    }

    .no-data-message {
        font-size: 16px;
        color: var(--text-light);
        margin-bottom: 20px;
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        background-color: var(--primary-color);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
    }

    .action-button .material-icons {
        margin-right: 5px;
    }

    @media (max-width: 768px) {
        .teacher-info {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .teacher-avatar {
            margin-right: 0;
            margin-bottom: 15px;
        }
        
        .teacher-contact-info {
            align-items: center;
        }
        
        .teacher-students {
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .contact-buttons {
            flex-direction: column;
        }
    }
    </style>
</body>
</html>
<?php
/**
 * profile.php - หน้าข้อมูลส่วนตัวของนักเรียน
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ลดการแสดงข้อผิดพลาดในหน้าเว็บ แต่เก็บไว้ในล็อก
error_reporting(0);
ini_set('display_errors', 0);

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'profile';
$page_title = 'STD-Prasat - ข้อมูลส่วนตัว';

// สถานะสำหรับการอัปเดตข้อมูล
$update_status = '';
$update_message = '';

// ตรวจสอบการส่งฟอร์มอัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $conn = getDB();
        
        // ข้อมูลที่อนุญาตให้อัปเดต (เฉพาะข้อมูลส่วนตัวที่จำเป็น)
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $line_id = $_POST['line_id'] ?? '';
        
        // อัปเดตข้อมูลในตาราง users
        $stmt = $conn->prepare("
            UPDATE users 
            SET phone_number = ?, email = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$phone, $email, $user_id]);
        
        // อัปเดตข้อมูลในตาราง students
        $stmt = $conn->prepare("
            UPDATE students 
            SET line_id = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$line_id, $user_id]);
        
        // อัปเดตรูปโปรไฟล์ถ้ามีการอัปโหลด
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_picture']['type'];
            $file_size = $_FILES['profile_picture']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = 'student_' . $user_id . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $profile_url = 'uploads/profiles/' . $file_name;
                    
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET profile_picture = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$profile_url, $user_id]);
                }
            }
        }
        
        $update_status = 'success';
        $update_message = 'อัปเดตข้อมูลสำเร็จ';
        
    } catch (PDOException $e) {
        $update_status = 'error';
        $update_message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
        error_log("Update error in profile.php: " . $e->getMessage());
    }
}

try {
    // เชื่อมต่อฐานข้อมูล
    $conn = getDB();
    
    // ดึงข้อมูลพื้นฐานของนักเรียน (เฉพาะที่จำเป็น)
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.line_id,
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_data) {
        throw new Exception("ไม่พบข้อมูลนักเรียน");
    }
    
    // แปลงข้อมูลเป็นรูปแบบที่ใช้ในหน้าเว็บ
    $first_char = mb_substr($student_data['first_name'], 0, 1, 'UTF-8');
    
    // ข้อมูลนักเรียนที่จะแสดงในหน้าเว็บ
    $student_info = [
        'name' => $student_data['title'] . $student_data['first_name'] . ' ' . $student_data['last_name'],
        'class' => $student_data['level'] . ' ' . $student_data['department_name'] . ' กลุ่ม ' . $student_data['group_number'],
        'student_code' => $student_data['student_code'],
        'phone' => $student_data['phone_number'],
        'email' => $student_data['email'],
        'line_id' => $student_data['line_id'],
        'avatar' => $first_char,
        'profile_picture' => $student_data['profile_picture']
    ];
    
    // CSS และ JS เพิ่มเติม
    $extra_css = [
        'assets/css/student-home.css'
    ];
    
    // กำหนดไฟล์เนื้อหา
    $content_path = 'pages/simple_profile_content.php';
    
    // ถ้าไม่มีไฟล์ simple_profile_content.php ให้สร้างเนื้อหาแบบ inline
    if (!file_exists($content_path)) {
        ob_start();
?>
<div class="container">
    <?php if (!empty($update_status)): ?>
        <div class="alert alert-<?php echo $update_status; ?>">
            <?php echo $update_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-card">
        <h1>ข้อมูลส่วนตัว</h1>
        
        <div class="profile-image-container">
            <?php if (!empty($student_info['profile_picture'])): ?>
                <div class="profile-image">
                    <img src="../<?php echo $student_info['profile_picture']; ?>" alt="รูปโปรไฟล์">
                </div>
            <?php else: ?>
                <div class="profile-image text-avatar">
                    <span><?php echo $student_info['avatar']; ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <h2><?php echo $student_info['name']; ?></h2>
            <p><?php echo $student_info['class']; ?></p>
            <p>รหัสนักเรียน: <?php echo $student_info['student_code']; ?></p>
        </div>
    </div>
    
    <form method="post" action="profile.php" enctype="multipart/form-data" class="profile-form">
        <div class="form-section">
            <h3>แก้ไขข้อมูลส่วนตัว</h3>
            
            <div class="form-group">
                <label for="profile_picture">รูปโปรไฟล์</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <small>ขนาดไฟล์ไม่เกิน 5MB (jpg, png, gif)</small>
            </div>
            
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input type="tel" id="phone" name="phone" value="<?php echo $student_info['phone']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="<?php echo $student_info['email']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="line_id">LINE ID</label>
                <input type="text" id="line_id" name="line_id" value="<?php echo $student_info['line_id']; ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <input type="hidden" name="update_profile" value="1">
            <button type="submit" class="btn-primary">บันทึกข้อมูล</button>
        </div>
    </form>
</div>

<style>
.container {
    padding: 15px;
    max-width: 600px;
    margin: 0 auto;
}

.alert {
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
}

.profile-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}

.profile-card h1 {
    font-size: 24px;
    margin: 0 0 20px;
    color: #333;
}

.profile-image-container {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
}

.profile-image {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-image.text-avatar {
    background-color: #06c755;
    color: white;
    font-size: 48px;
    font-weight: bold;
}

.profile-info h2 {
    font-size: 18px;
    margin: 0 0 5px;
}

.profile-info p {
    margin: 0 0 5px;
    color: #666;
}

.profile-form {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
}

.form-section {
    margin-bottom: 20px;
}

.form-section h3 {
    font-size: 18px;
    margin: 0 0 15px;
    color: #333;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    text-align: center;
}

.btn-primary {
    background-color: #06c755;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

.btn-primary:hover {
    background-color: #05a649;
}

/* พื้นที่ว่างสำหรับรองรับ bottom nav */
.container::after {
    content: "";
    display: block;
    height: 70px;
}
</style>
<?php
        $content = ob_get_clean();
        echo $content;
    } else {
        // รวม template หลัก
        include 'templates/main_template.php';
    }
    
} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาด
    $error_message = "ไม่สามารถดึงข้อมูลหรือบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
    
    echo '<div style="max-width: 500px; margin: 50px auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">';
    echo '<div style="color: #e53935; font-size: 48px; margin-bottom: 20px;">&#9888;</div>';
    echo '<h1 style="margin: 0 0 15px; color: #333;">เกิดข้อผิดพลาด</h1>';
    echo '<p style="color: #666; margin-bottom: 25px;">' . $error_message . '</p>';
    echo '<div>';
    echo '<a href="home.php" style="display: inline-block; background-color: #06c755; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">กลับหน้าหลัก</a>';
    echo '<a href="javascript:history.back()" style="display: inline-block; border: 1px solid #06c755; color: #06c755; padding: 10px 20px; text-decoration: none; border-radius: 5px;">ย้อนกลับ</a>';
    echo '</div>';
    echo '</div>';
    
    error_log("Error in profile.php: " . $e->getMessage());
}
?>
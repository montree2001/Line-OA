<?php
/**
 * ไฟล์ทดสอบการแสดงรูปโปรไฟล์
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลผู้ใช้
try {
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "ไม่พบข้อมูลผู้ใช้";
        exit;
    }
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบการแสดงรูปโปรไฟล์</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { padding-top: 50px; }
        .profile-container { max-width: 600px; margin: 0 auto; }
        .profile-image-normal { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db; }
        .profile-image-large { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db; margin-bottom: 15px; }
        .profile-section { background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .text-avatar { width: 50px; height: 50px; border-radius: 50%; background-color: #e74c3c; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; }
        .debug-section { margin-top: 20px; background-color: #f8f9fa; border-radius: 10px; padding: 20px; }
        .debug-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 10px; }
        
        .image-size-demo { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 20px; }
        .size-item { text-align: center; }
        .size-label { font-size: 0.9rem; color: #666; margin-top: 5px; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="profile-container">
            <h1 class="text-center mb-4">ทดสอบการแสดงรูปโปรไฟล์</h1>
            
            <div class="profile-section text-center">
                <h3 class="mb-3">รูปโปรไฟล์จากฐานข้อมูล</h3>
                <?php if (!empty($user['profile_picture']) && filter_var($user['profile_picture'], FILTER_VALIDATE_URL)): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" class="profile-image-large">
                <?php else: ?>
                    <div class="text-avatar mx-auto mb-3" style="width: 150px; height: 150px; font-size: 3rem;"><?php echo substr($user['first_name'], 0, 1); ?></div>
                <?php endif; ?>
                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
            </div>
            
            <div class="profile-section">
                <h4 class="mb-3">ขนาดรูปโปรไฟล์ต่างๆ</h4>
                
                <div class="image-size-demo">
                    <?php if (!empty($user['profile_picture']) && filter_var($user['profile_picture'], FILTER_VALIDATE_URL)): ?>
                        <div class="size-item">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                            <div class="size-label">35x35px</div>
                        </div>
                        <div class="size-item">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                            <div class="size-label">45x45px</div>
                        </div>
                        <div class="size-item">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                            <div class="size-label">50x50px</div>
                        </div>
                        <div class="size-item">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                            <div class="size-label">70x70px</div>
                        </div>
                        <div class="size-item">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="โปรไฟล์" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db;">
                            <div class="size-label">100x100px</div>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <div class="text-muted">ไม่มีรูปโปรไฟล์ในฐานข้อมูล</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="debug-section">
                <div class="debug-title">ข้อมูลสำหรับการดีบัก</div>
                <div class="debug-content">
                    <p><strong>User ID:</strong> <?php echo $user['user_id']; ?></p>
                    <p><strong>Profile URL:</strong> <?php echo $user['profile_picture'] ?: 'ไม่มี'; ?></p>
                    <p><strong>LINE User ID:</strong> <?php echo $_SESSION['line_user_id'] ?? 'ไม่มี'; ?></p>
                    <p><strong>LINE Profile Picture:</strong> <?php echo $_SESSION['line_profile_picture'] ?? 'ไม่มี'; ?></p>
                </div>
                
                <?php if (isset($_SESSION['line_profile_picture']) && !empty($_SESSION['line_profile_picture'])): ?>
                <div class="text-center mt-4">
                    <h5>รูปโปรไฟล์จาก LINE</h5>
                    <img src="<?php echo htmlspecialchars($_SESSION['line_profile_picture']); ?>" alt="LINE Profile" class="profile-image-large">
                    
                    <form action="../update_profile_picture.php" method="post" class="mt-3">
                        <input type="hidden" name="profile_picture" value="<?php echo htmlspecialchars($_SESSION['line_profile_picture']); ?>">
                        <button type="submit" class="btn btn-primary">อัปเดตรูปโปรไฟล์</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4 mb-5">
                <a href="home.php" class="btn btn-secondary">กลับหน้าหลัก</a>
            </div>
        </div>
    </div>
</body>
</html> 
<?php
/**
 * announcements.php - หน้าแสดงรายการประกาศทั้งหมด
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน (ถ้าต้องการเปิดใช้)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบบทบาท (ถ้าต้องการเปิดใช้)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? 0;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลแผนก/ระดับชั้นของนักเรียน
    $stmt = $conn->prepare("
        SELECT s.student_id, c.department_id, c.level
        FROM students s
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // เตรียมค่า dept_id และ level
    $dept_id = isset($student['department_id']) ? $student['department_id'] : 0;
    $level = isset($student['level']) ? $student['level'] : '';
    
    // ดึงรายการประกาศทั้งหมดที่เกี่ยวข้องกับนักเรียน
    $stmt = $conn->prepare("
        SELECT a.announcement_id, a.title, a.content, a.type, a.created_at, 
               u.first_name, u.last_name, u.title as user_title
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.status = 'active' 
        AND (a.is_all_targets = 1 OR (a.target_department = ? OR a.target_level = ?))
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$dept_id, $level]);
    $db_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // จัดรูปแบบประกาศสำหรับแสดงผล
    $announcements = [];
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    foreach ($db_announcements as $item) {
        $created_date = new DateTime($item['created_at']);
        $badge_type = '';
        $badge_text = '';
        
        // กำหนดประเภทแบดจ์ตามประเภทประกาศ
        switch ($item['type']) {
            case 'urgent':
                $badge_type = 'urgent';
                $badge_text = 'ด่วน';
                break;
            case 'event':
                $badge_type = 'event';
                $badge_text = 'กิจกรรม';
                break;
            case 'academic':
                $badge_type = 'info';
                $badge_text = 'วิชาการ';
                break;
            default:
                $badge_type = 'info';
                $badge_text = 'ข่าวสาร';
        }
        
        // ตัดข้อความให้สั้นลงสำหรับแสดงในหน้ารายการ
        $short_content = mb_substr(strip_tags($item['content']), 0, 150, 'UTF-8');
        if (mb_strlen($item['content'], 'UTF-8') > 150) {
            $short_content .= '...';
        }
        
        // ข้อมูลผู้สร้างประกาศ
        $creator_name = '';
        if (!empty($item['user_title']) && !empty($item['first_name']) && !empty($item['last_name'])) {
            $creator_name = $item['user_title'] . $item['first_name'] . ' ' . $item['last_name'];
        } else {
            $creator_name = 'เจ้าหน้าที่วิทยาลัย';
        }
        
        $announcements[] = [
            'id' => $item['announcement_id'],
            'title' => $item['title'],
            'content' => $short_content,
            'date' => $created_date->format('d') . ' ' . $thai_months[$created_date->format('m')] . ' ' . 
                     ($created_date->format('Y') + 543),
            'badge' => $badge_type,
            'badge_text' => $badge_text,
            'creator' => $creator_name
        ];
    }
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    // ควรบันทึกข้อผิดพลาดและแสดงหน้าว่าง
    
    // ถ้าเป็นระบบจริง ควรบันทึกข้อผิดพลาดลงในไฟล์ log
    // error_log('Database error in announcements.php: ' . $e->getMessage());
    
    $error_message = "ไม่สามารถดึงข้อมูลประกาศได้ในขณะนี้ กรุณาลองใหม่อีกครั้งในภายหลัง";
    $announcements = []; // กำหนดให้เป็นอาเรย์ว่าง
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศทั้งหมด - STD-Prasat</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        /* CSS เฉพาะหน้า */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header {
            background-color: #06c755;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header button {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
        }
        
        .header-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .container {
            max-width: 800px;
            margin: 70px auto 80px;
            padding: 15px;
        }
        
        .announcement-card {
            background-color: white;
            border-radius: 15px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .announcement-link {
            display: block;
            padding: 16px;
            text-decoration: none;
            color: inherit;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .badge-info {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .badge-event {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .badge-urgent {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .announcement-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .meta div {
            display: flex;
            align-items: center;
        }
        
        .meta .material-icons {
            font-size: 14px;
            margin-right: 4px;
        }
        
        .announcement-content {
            font-size: 14px;
            line-height: 1.5;
            color: #666;
            margin-bottom: 10px;
        }
        
        .read-more {
            color: #06c755;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .read-more .material-icons {
            font-size: 16px;
            margin-left: 4px;
        }
        
        .empty-message {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-size: 12px;
        }
        
        .nav-item .material-icons {
            font-size: 24px;
            margin-bottom: 2px;
        }
        
        .nav-item.active {
            color: #06c755;
        }
    </style>
</head>
<body>
    <!-- ส่วนหัว -->
    <div class="header">
        <button onclick="history.back()">
            <span class="material-icons">arrow_back</span>
        </button>
        <div class="header-title">ประกาศทั้งหมด</div>
        <div style="width: 24px;"></div>
    </div>
    
    <!-- เนื้อหา -->
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($announcements)): ?>
            <div class="empty-message">
                <div class="empty-icon">
                    <span class="material-icons">campaign</span>
                </div>
                <div>ยังไม่มีประกาศในขณะนี้</div>
                <button class="btn btn-secondary" onclick="location.href='home.php'" style="background-color:#f2f2f2; color:#333; border:none; padding:10px 15px; border-radius:5px; margin-top:15px; cursor:pointer;">
                    <span class="material-icons" style="vertical-align:middle; margin-right:5px;">arrow_back</span> กลับหน้าหลัก
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <a href="view_announcement.php?id=<?php echo $announcement['id']; ?>" class="announcement-link">
                        <div class="badge badge-<?php echo $announcement['badge']; ?>">
                            <?php echo $announcement['badge_text']; ?>
                        </div>
                        
                        <h2 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                        
                        <div class="meta">
                            <div>
                                <span class="material-icons">event</span>
                                <?php echo $announcement['date']; ?>
                            </div>
                            <div>
                                <span class="material-icons">person</span>
                                <?php echo htmlspecialchars($announcement['creator']); ?>
                            </div>
                        </div>
                        
                        <div class="announcement-content">
                            <?php echo htmlspecialchars($announcement['content']); ?>
                        </div>
                        
                        <div class="read-more">
                            อ่านเพิ่มเติม <span class="material-icons">arrow_forward</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- แถบนำทางด้านล่าง -->
    <div class="bottom-nav">
        <a href="home.php" class="nav-item">
            <span class="material-icons">home</span>
            <span>หน้าหลัก</span>
        </a>
        <a href="check-in.php" class="nav-item">
            <span class="material-icons">how_to_reg</span>
            <span>เช็คชื่อ</span>
        </a>
        <a href="history.php" class="nav-item">
            <span class="material-icons">history</span>
            <span>ประวัติ</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="material-icons">person</span>
            <span>โปรไฟล์</span>
        </a>
    </div>
</body>
</html>
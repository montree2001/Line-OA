<?php
/**
 * view_announcement.php - หน้าแสดงรายละเอียดประกาศฉบับเต็ม
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน (ถ้าต้องการเปิดใช้)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// รับค่า ID ประกาศและตรวจสอบความถูกต้อง
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($announcement_id <= 0) {
    // กรณี ID ไม่ถูกต้อง ให้กลับไปหน้าหลัก
    header('Location: home.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

try {
    // ดึงข้อมูลประกาศตาม ID
    $stmt = $conn->prepare("
        SELECT a.announcement_id, a.title, a.content, a.type, a.created_at,
               u.first_name, u.last_name, u.title as user_title
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.announcement_id = ? AND a.status = 'active'
    ");
    
    $stmt->execute([$announcement_id]);
    $announcement_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement_data) {
        // กรณีไม่พบประกาศที่ระบุหรือประกาศไม่ active
        header('Location: announcements.php');
        exit;
    }
    
    // จัดรูปแบบวันที่
    $created_date = new DateTime($announcement_data['created_at']);
    $thai_months = [
        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
    ];
    
    $thai_date = $created_date->format('d') . ' ' . $thai_months[$created_date->format('m')] . ' ' . 
                ($created_date->format('Y') + 543);
    
    // กำหนดแบดจ์ตามประเภทประกาศ
    $badge_type = '';
    $badge_text = '';
    
    switch ($announcement_data['type']) {
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
    
    // ข้อมูลผู้สร้างประกาศ
    $creator_name = '';
    if (!empty($announcement_data['user_title']) && !empty($announcement_data['first_name']) && !empty($announcement_data['last_name'])) {
        $creator_name = $announcement_data['user_title'] . $announcement_data['first_name'] . ' ' . $announcement_data['last_name'];
    } else {
        $creator_name = 'เจ้าหน้าที่วิทยาลัย';
    }
    
    // เตรียมข้อมูลสำหรับแสดงผล
    $announcement = [
        'id' => $announcement_data['announcement_id'],
        'title' => $announcement_data['title'],
        'content' => $announcement_data['content'],
        'date' => $thai_date,
        'badge' => $badge_type,
        'badge_text' => $badge_text,
        'creator' => $creator_name
    ];
    
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    // ควรบันทึกข้อผิดพลาดและแสดงข้อความทั่วไป
    
    // ถ้าเป็นระบบจริง ควรบันทึกข้อผิดพลาดลงในไฟล์ log
    // error_log('Database error in view_announcement.php: ' . $e->getMessage());
    
    // ข้อมูลประกาศสำหรับกรณีมีข้อผิดพลาด
    $announcement = [
        'id' => 0,
        'title' => 'ไม่สามารถแสดงข้อมูลประกาศได้',
        'content' => 'ขออภัย ระบบไม่สามารถแสดงข้อมูลประกาศที่คุณต้องการได้ในขณะนี้ กรุณาลองใหม่อีกครั้งในภายหลัง',
        'date' => date('d') . ' ' . $thai_months[date('m')] . ' ' . (date('Y') + 543),
        'badge' => 'info',
        'badge_text' => 'แจ้งเตือน',
        'creator' => 'ระบบ'
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($announcement['title']); ?> - STD-Prasat</title>
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
        
        .card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
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
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .meta div {
            display: flex;
            align-items: center;
        }
        
        .meta .material-icons {
            font-size: 16px;
            margin-right: 5px;
        }
        
        .announcement-content {
            font-size: 16px;
            line-height: 1.6;
            white-space: pre-line;
            margin-bottom: 20px;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .btn-secondary {
            background-color: #f2f2f2;
            color: #333;
        }
        
        .btn-primary {
            background-color: #06c755;
            color: white;
        }
        
        .btn .material-icons {
            margin-right: 5px;
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
        <div class="header-title">รายละเอียดประกาศ</div>
        <div style="width: 24px;"></div>
    </div>
    
    <!-- เนื้อหา -->
    <div class="container">
        <div class="card">
            <div class="badge badge-<?php echo $announcement['badge']; ?>">
                <?php echo $announcement['badge_text']; ?>
            </div>
            
            <h1 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h1>
            
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
                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
            </div>
            
            <div class="buttons">
                <button class="btn btn-secondary" onclick="history.back()">
                    <span class="material-icons">arrow_back</span> กลับ
                </button>
                <a href="announcements.php" class="btn btn-primary">
                    <span class="material-icons">list</span> ประกาศทั้งหมด
                </a>
            </div>
        </div>
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
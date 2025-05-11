<?php
/**
 * activities_db_check.php - ตรวจสอบและแก้ไขปัญหาแสดงกิจกรรมซ้ำกัน
 * 
 * ไฟล์นี้ใช้สำหรับตรวจสอบข้อมูลกิจกรรมในฐานข้อมูลและแก้ไขปัญหาการแสดงผลซ้ำกัน
 * วิธีใช้งาน: วางไฟล์นี้ในโฟลเดอร์ admin และเรียกใช้งานผ่าน URL
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('ต้องเข้าสู่ระบบด้วยสิทธิ์ผู้ดูแลระบบ');
}

// กำหนดการแสดงผลข้อมูล
header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>ตรวจสอบข้อมูลกิจกรรม</title>";
echo "<style>
    body { font-family: 'Sarabun', sans-serif; margin: 20px; }
    h1, h2 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .action-btn { 
        display: inline-block; 
        padding: 5px 10px; 
        background-color: #4CAF50; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px;
        margin: 5px;
    }
</style></head><body>";

echo "<h1>ตรวจสอบและแก้ไขปัญหาแสดงกิจกรรมซ้ำกัน</h1>";

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        echo "<p class='error'>ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน</p>";
        exit;
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
    
    echo "<p>ปีการศึกษาปัจจุบัน: " . $academic_year_display . "</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    exit;
}

// ตรวจสอบการกระทำการแก้ไข
$action_taken = false;
$message = "";

// ถ้ามีการส่งคำร้องในการแก้ไข
if (isset($_POST['fix_activities']) && $_POST['fix_activities'] == 1) {
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // 1. แก้ไขชื่อกิจกรรมที่มีเครื่องหมาย / ต่อท้าย
        $stmt = $conn->prepare("
            UPDATE activities 
            SET activity_name = TRIM(TRAILING '/' FROM activity_name),
                updated_by = ?,
                updated_at = NOW()
            WHERE activity_name LIKE '%/'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $fixed_names = $stmt->rowCount();
        
        // 2. ลบกิจกรรมที่ซ้ำกัน (มีชื่อเดียวกันและวันเดียวกัน ให้เหลือแค่ activity_id ที่น้อยที่สุด)
        // หา activity_id ที่ต้องการเก็บ (activity_id ที่น้อยที่สุดของแต่ละกลุ่ม)
        $stmt = $conn->prepare("
            SELECT MIN(activity_id) as keep_id, activity_name, activity_date
            FROM activities
            WHERE academic_year_id = ?
            GROUP BY activity_name, activity_date
            HAVING COUNT(*) > 1
        ");
        $stmt->execute([$current_academic_year_id]);
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deleted_count = 0;
        foreach ($duplicates as $dup) {
            // ลบกิจกรรมซ้ำที่ไม่ใช่ activity_id ที่น้อยที่สุด
            $stmt_del = $conn->prepare("
                DELETE FROM activities 
                WHERE activity_name = ? 
                AND activity_date = ? 
                AND activity_id <> ?
            ");
            $stmt_del->execute([$dup['activity_name'], $dup['activity_date'], $dup['keep_id']]);
            $deleted_count += $stmt_del->rowCount();
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $action_type = 'fix_activities';
        $action_details = json_encode([
            'fixed_names' => $fixed_names,
            'deleted_duplicates' => $deleted_count
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $action_type, $action_details]);
        
        // Commit transaction
        $conn->commit();
        
        $action_taken = true;
        $message = "ดำเนินการแก้ไขเรียบร้อยแล้ว:<br>";
        $message .= "- แก้ไขชื่อกิจกรรมที่มีเครื่องหมาย / ต่อท้าย: $fixed_names รายการ<br>";
        $message .= "- ลบกิจกรรมที่ซ้ำกัน: $deleted_count รายการ";
        
    } catch (PDOException $e) {
        // Rollback ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        echo "<p class='error'>เกิดข้อผิดพลาดในการแก้ไข: " . $e->getMessage() . "</p>";
    }
}

// แสดงข้อความผลลัพธ์
if ($action_taken) {
    echo "<p class='success'>$message</p>";
}

// ดึงรายการกิจกรรมในปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("
        SELECT 
            a.activity_id, a.activity_name, a.activity_date, a.activity_location, 
            a.description, a.required_attendance, a.created_at,
            u.first_name, u.last_name
        FROM activities a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.academic_year_id = ?
        ORDER BY a.activity_date DESC, a.activity_name
    ");
    $stmt->execute([$current_academic_year_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบหาชื่อกิจกรรมซ้ำในวันเดียวกัน
    $duplicates = [];
    $activity_names = [];
    $trailing_slashes = [];
    
    foreach ($activities as $activity) {
        $key = $activity['activity_name'] . '_' . $activity['activity_date'];
        if (isset($activity_names[$key])) {
            $duplicates[] = [
                'name' => $activity['activity_name'],
                'date' => $activity['activity_date'],
                'ids' => [$activity_names[$key], $activity['activity_id']]
            ];
        } else {
            $activity_names[$key] = $activity['activity_id'];
        }
        
        // ตรวจสอบชื่อที่มีเครื่องหมาย / ต่อท้าย
        if (substr($activity['activity_name'], -1) === '/') {
            $trailing_slashes[] = $activity['activity_id'];
        }
    }
    
    // แสดงรายงานสรุป
    echo "<h2>รายงานสรุปการตรวจสอบ</h2>";
    
    echo "<p>จำนวนกิจกรรมทั้งหมดในปีการศึกษา $academic_year_display: " . count($activities) . " รายการ</p>";
    
    // แสดงกิจกรรมที่ซ้ำกัน
    if (!empty($duplicates)) {
        echo "<h3 class='warning'>พบกิจกรรมที่มีชื่อซ้ำกันในวันเดียวกัน:</h3>";
        echo "<table>";
        echo "<tr><th>ลำดับ</th><th>ชื่อกิจกรรม</th><th>วันที่</th><th>Activity IDs</th></tr>";
        
        foreach ($duplicates as $index => $duplicate) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>" . htmlspecialchars($duplicate['name']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($duplicate['date'])) . "</td>";
            echo "<td>" . implode(', ', $duplicate['ids']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='success'>ไม่พบกิจกรรมที่มีชื่อซ้ำกันในวันเดียวกัน</p>";
    }
    
    // แสดงกิจกรรมที่มีเครื่องหมาย / ต่อท้าย
    if (!empty($trailing_slashes)) {
        echo "<h3 class='warning'>พบกิจกรรมที่มีเครื่องหมาย / ต่อท้าย:</h3>";
        echo "<table>";
        echo "<tr><th>ลำดับ</th><th>Activity ID</th><th>ชื่อกิจกรรม</th><th>วันที่</th></tr>";
        
        $index = 1;
        foreach ($activities as $activity) {
            if (in_array($activity['activity_id'], $trailing_slashes)) {
                echo "<tr>";
                echo "<td>" . $index++ . "</td>";
                echo "<td>" . $activity['activity_id'] . "</td>";
                echo "<td>" . htmlspecialchars($activity['activity_name']) . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($activity['activity_date'])) . "</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
    } else {
        echo "<p class='success'>ไม่พบกิจกรรมที่มีเครื่องหมาย / ต่อท้าย</p>";
    }
    
    // แสดงรายการกิจกรรมทั้งหมด
    echo "<h2>รายการกิจกรรมทั้งหมดในปีการศึกษา $academic_year_display</h2>";
    
    echo "<table>";
    echo "<tr><th>Activity ID</th><th>ชื่อกิจกรรม</th><th>วันที่</th><th>สถานที่</th><th>ผู้สร้าง</th><th>วันที่สร้าง</th></tr>";
    
    foreach ($activities as $activity) {
        echo "<tr>";
        echo "<td>" . $activity['activity_id'] . "</td>";
        echo "<td>" . htmlspecialchars($activity['activity_name']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($activity['activity_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($activity['activity_location'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($activity['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // แสดงฟอร์มสำหรับการแก้ไข
    if (!empty($duplicates) || !empty($trailing_slashes)) {
        echo "<h2>การแก้ไขปัญหา</h2>";
        echo "<p>การดำเนินการแก้ไขจะ:</p>";
        echo "<ol>";
        if (!empty($trailing_slashes)) {
            echo "<li>ลบเครื่องหมาย / ต่อท้ายออกจากชื่อกิจกรรม</li>";
        }
        if (!empty($duplicates)) {
            echo "<li>ลบกิจกรรมที่ซ้ำกัน (เก็บไว้เฉพาะ Activity ID ที่น้อยที่สุดของแต่ละกลุ่ม)</li>";
        }
        echo "</ol>";
        
        echo "<form method='post' onsubmit='return confirm(\"ยืนยันการแก้ไขข้อมูลกิจกรรม?\")'>";
        echo "<input type='hidden' name='fix_activities' value='1'>";
        echo "<button type='submit' class='action-btn'>ดำเนินการแก้ไข</button>";
        echo "</form>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "</p>";
}

// ลิงก์กลับไปหน้ากิจกรรม
echo "<p><a href='activities.php' class='action-btn'>กลับไปหน้ากิจกรรม</a></p>";

echo "</body></html>";
?>
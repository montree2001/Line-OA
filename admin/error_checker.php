<?php
/**
 * error_checker.php - ตรวจสอบการเรียก AJAX โดยตรง
 * วางไฟล์นี้ไว้ในโฟลเดอร์ admin/ แล้วเรียกใช้ผ่านเบราว์เซอร์
 */

// แสดงข้อมูลข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบไฟล์ในโฟลเดอร์ ajax
function checkAjaxFiles() {
    $ajaxPath = __DIR__ . '/ajax';
    $requiredFiles = [
        'get_classes.php',
        'get_students_for_attendance.php'
    ];
    
    echo "<h2>ตรวจสอบไฟล์ในโฟลเดอร์ ajax</h2>";
    if (!is_dir($ajaxPath)) {
        echo "<p style='color:red'>ไม่พบโฟลเดอร์ ajax</p>";
        return;
    }
    
    foreach ($requiredFiles as $file) {
        $fullPath = $ajaxPath . '/' . $file;
        if (file_exists($fullPath)) {
            echo "<p>- ✓ พบไฟล์ $file (ขนาด: " . filesize($fullPath) . " bytes, แก้ไขล่าสุด: " . date("Y-m-d H:i:s", filemtime($fullPath)) . ")</p>";
        } else {
            echo "<p style='color:red'>- ✗ ไม่พบไฟล์ $file</p>";
        }
    }
}

// ทดสอบเรียก get_classes.php
function testGetClasses() {
    echo "<h2>ทดสอบเรียก get_classes.php โดยตรง</h2>";
    
    $departmentId = 6; // ID แผนกวิชา (ปรับให้ตรงกับข้อมูลจริง)
    $level = 'ปวช.1'; // ระดับชั้น (ปรับให้ตรงกับข้อมูลจริง)
    
    $url = "ajax/get_classes.php?department_id=$departmentId&level=" . urlencode($level);
    
    echo "<p>URL ที่ทดสอบ: $url</p>";
    
    try {
        // ใช้การ require เพื่อเรียกไฟล์โดยตรง (จำลองการเรียก AJAX)
        ob_start();
        $_GET['department_id'] = $departmentId;
        $_GET['level'] = $level;
        include __DIR__ . '/ajax/get_classes.php';
        $result = ob_get_clean();
        
        echo "<p>ผลลัพธ์:</p>";
        echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:300px'>" . htmlspecialchars($result) . "</pre>";
        
        $data = json_decode($result, true);
        if ($data !== null) {
            echo "<p>สถานะ: " . ($data['success'] ? "✓ สำเร็จ" : "✗ ไม่สำเร็จ") . "</p>";
            if ($data['success']) {
                echo "<p>จำนวนห้องเรียนที่พบ: " . count($data['classes']) . "</p>";
            } else {
                echo "<p style='color:red'>ข้อผิดพลาด: " . $data['error'] . "</p>";
            }
        } else {
            echo "<p style='color:red'>ข้อผิดพลาด: ไม่สามารถแปลงข้อมูลเป็น JSON ได้</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    }
}

// ทดสอบเรียก get_students_for_attendance.php
function testGetStudents() {
    echo "<h2>ทดสอบเรียก get_students_for_attendance.php โดยตรง</h2>";
    
    $departmentId = 6; // ID แผนกวิชา (ปรับให้ตรงกับข้อมูลจริง)
    $level = 'ปวช.1'; // ระดับชั้น (ปรับให้ตรงกับข้อมูลจริง)
    $date = date('Y-m-d'); // วันที่ปัจจุบัน
    
    $url = "ajax/get_students_for_attendance.php?department_id=$departmentId&level=" . urlencode($level) . "&date=$date";
    
    echo "<p>URL ที่ทดสอบ: $url</p>";
    
    try {
        // ใช้การ require เพื่อเรียกไฟล์โดยตรง (จำลองการเรียก AJAX)
        ob_start();
        $_GET['department_id'] = $departmentId;
        $_GET['level'] = $level;
        $_GET['date'] = $date;
        include __DIR__ . '/ajax/get_students_for_attendance.php';
        $result = ob_get_clean();
        
        echo "<p>ผลลัพธ์:</p>";
        echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:300px'>" . htmlspecialchars($result) . "</pre>";
        
        $data = json_decode($result, true);
        if ($data !== null) {
            echo "<p>สถานะ: " . ($data['success'] ? "✓ สำเร็จ" : "✗ ไม่สำเร็จ") . "</p>";
            if ($data['success']) {
                echo "<p>จำนวนนักเรียนที่พบ: " . (isset($data['students']) ? count($data['students']) : 0) . "</p>";
                
                if (isset($data['students']) && count($data['students']) > 0) {
                    echo "<p>ตัวอย่างข้อมูลนักเรียน:</p>";
                    echo "<pre style='background:#eef;padding:10px'>" . print_r($data['students'][0], true) . "</pre>";
                }
            } else {
                echo "<p style='color:red'>ข้อผิดพลาด: " . $data['error'] . "</p>";
            }
        } else {
            echo "<p style='color:red'>ข้อผิดพลาด: ไม่สามารถแปลงข้อมูลเป็น JSON ได้</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    }
}

// แสดงเนื้อหาหน้าเว็บ
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบข้อผิดพลาดการเรียก AJAX</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>ตรวจสอบข้อผิดพลาดการเรียก AJAX</h1>
        
        <div class="card">
            <h2>ข้อมูลเซิร์ฟเวอร์และ PHP</h2>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p>Current Script: <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
        </div>
        
        <div class="card">
            <h2>ข้อมูลการเชื่อมต่อฐานข้อมูล</h2>
            <?php
            try {
                // ตรวจสอบค่าคงที่จาก db_config.php
                echo "<p>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '<span class="error">ไม่ได้กำหนดค่า</span>') . "</p>";
                echo "<p>DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '<span class="error">ไม่ได้กำหนดค่า</span>') . "</p>";
                echo "<p>DB_USER: " . (defined('DB_USER') ? DB_USER : '<span class="error">ไม่ได้กำหนดค่า</span>') . "</p>";
                echo "<p>DB_PASS: " . (defined('DB_PASS') ? '***ถูกปิดบัง***' : '<span class="error">ไม่ได้กำหนดค่า</span>') . "</p>";
                echo "<p>DB_CHARSET: " . (defined('DB_CHARSET') ? DB_CHARSET : '<span class="error">ไม่ได้กำหนดค่า</span>') . "</p>";
                
                // ทดสอบการเชื่อมต่อฐานข้อมูล
                if (function_exists('getDB')) {
                    $conn = getDB();
                    echo "<p class='success'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
                    
                    // ตรวจสอบข้อมูลปีการศึกษา
                    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
                    $stmt->execute();
                    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($academic_year) {
                        echo "<p>ปีการศึกษาปัจจุบัน: " . $academic_year['year'] . "/" . $academic_year['semester'] . " (ID: " . $academic_year['academic_year_id'] . ")</p>";
                    } else {
                        echo "<p class='error'>ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน</p>";
                    }
                } else {
                    echo "<p class='error'>ไม่พบฟังก์ชัน getDB()</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="card">
            <?php checkAjaxFiles(); ?>
        </div>
        
        <div class="card">
            <?php testGetClasses(); ?>
        </div>
        
        <div class="card">
            <?php testGetStudents(); ?>
        </div>
        
        <div class="card">
            <h2>ตรวจสอบข้อมูล JavaScript</h2>
            <p>ไฟล์ assets/js/bulk_attendance.js:</p>
            <?php
            $jsFile = __DIR__ . '/assets/js/bulk_attendance.js';
            if (file_exists($jsFile)) {
                echo "<p>✓ พบไฟล์ (ขนาด: " . filesize($jsFile) . " bytes, แก้ไขล่าสุด: " . date("Y-m-d H:i:s", filemtime($jsFile)) . ")</p>";
            } else {
                echo "<p class='error'>✗ ไม่พบไฟล์</p>";
            }
            ?>
            
            <p>รายละเอียดหน้าเว็บ:</p>
            <ul>
                <li>เปิด Developer Console (F12) ในเบราว์เซอร์</li>
                <li>ตรวจสอบข้อความในแท็บ Console</li>
                <li>ตรวจสอบการเรียก Network Request เมื่อกดปุ่มค้นหา</li>
            </ul>
        </div>
    </div>
</body>
</html>
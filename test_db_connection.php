<?php
// ทดสอบการเชื่อมต่อฐานข้อมูล
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";

try {
    // ทดสอบการเชื่อมต่อแบบพื้นฐานก่อน
    echo "1️⃣ ทดสอบการเชื่อมต่อพื้นฐาน...<br>";
    $pdo = new PDO('mysql:host=localhost;dbname=prasat_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ เชื่อมต่อฐานข้อมูล prasat_db สำเร็จ!<br><br>";
    
    // แสดงตารางที่มี
    echo "2️⃣ ตารางที่มีในฐานข้อมูล:<br>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "📋 " . $table . "<br>";
    }
    echo "<br>";
    
    // ทดสอบการโหลด db_connect.php
    echo "3️⃣ ทดสอบไฟล์ db_connect.php...<br>";
    require_once 'db_connect.php';
    $conn = getDB();
    echo "✅ โหลด db_connect.php สำเร็จ!<br><br>";
    
    // ทดสอบ query ง่ายๆ
    echo "4️⃣ ทดสอบ query...<br>";
    if (in_array('students', $tables)) {
        $query = "SELECT COUNT(*) as student_count FROM students";
        $stmt = $conn->query($query);
        $result = $stmt->fetch();
        echo "📊 จำนวนนักเรียนทั้งหมด: " . $result['student_count'] . " คน<br>";
    }
    
    if (in_array('academic_years', $tables)) {
        $query = "SELECT * FROM academic_years LIMIT 1";
        $stmt = $conn->query($query);
        $academic_year = $stmt->fetch();
        if ($academic_year) {
            echo "📅 มีข้อมูลปีการศึกษา<br>";
        }
    }
    
    if (in_array('attendance', $tables)) {
        $query = "SELECT COUNT(*) as attendance_count FROM attendance";
        $stmt = $conn->query($query);
        $result = $stmt->fetch();
        echo "📈 จำนวนการเช็คชื่อทั้งหมด: " . $result['attendance_count'] . " รายการ<br>";
    }
    
    echo "<br>✅ ระบบฐานข้อมูลพร้อมใช้งาน!<br>";
    echo "<a href='admin/attendance_adjustment.php'>🔗 ไปที่หน้าปรับข้อมูลเข้าแถว</a>";
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "📝 Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
<?php
/**
 * database.php - ไฟล์ตั้งค่าการเชื่อมต่อฐานข้อมูล
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// ข้อมูลการเชื่อมต่อฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_prasat');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * ฟังก์ชันสำหรับเชื่อมต่อฐานข้อมูล
 * 
 * @return PDO PDO object สำหรับเชื่อมต่อฐานข้อมูล
 */
function getConnection() {
    try {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดและแสดงข้อความที่เหมาะสม
        error_log("Database connection error: " . $e->getMessage());
        throw new PDOException("ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
    }
}

/**
 * ฟังก์ชันสำหรับ sanitize ข้อมูลก่อนแสดงผล
 * 
 * @param string $data ข้อมูลที่ต้องการ sanitize
 * @return string ข้อมูลที่ผ่านการ sanitize แล้ว
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
<?php
// db_connect.php - หลักการเชื่อมต่อฐานข้อมูลเดียว

// โหลด config หลัก
require_once __DIR__ . '/config/db_config.php';

class Database {
    private static $instance = null;
    private $conn;

    // Constructor เป็น private เพื่อป้องกันการสร้าง instance โดยตรง
   // ในไฟล์ db_connect.php ให้ปรับปรุงเมธอด __construct() ในคลาส Database

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // ตั้งค่า timezone
            $this->conn->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
        }
    }

    // ใช้ Singleton pattern เพื่อให้มี connection เดียว
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // คืนค่า connection
    public function getConnection() {
        return $this->conn;
    }

    // ป้องกันการ clone object
    private function __clone() {}
}

// ฟังก์ชันช่วยเหลือสำหรับการใช้งานทั่วไป
function getDB() {
    $db = Database::getInstance();
    return $db->getConnection();
}

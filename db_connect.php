<?php
// db_connect.php

// กำหนดค่าการเชื่อมต่อฐานข้อมูลโดยตรวจสอบว่ามีการกำหนดค่าไว้แล้วหรือไม่
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'stp_prasat');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $conn;

    // Constructor เป็น private เพื่อป้องกันการสร้าง instance โดยตรง
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // ในระบบจริง ควร log error นี้ไปยังไฟล์ log แทนการแสดงผล
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
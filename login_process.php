<?php
// login_process.php - ตรวจสอบการเข้าสู่ระบบด้วย MD5
session_start();
require_once 'db_connect.php';

// ตรวจสอบว่ามีการส่งข้อมูลเข้ามาหรือไม่
if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    header('Location: login.php');
    exit;
}

try {
    $conn = getDB();
    
    $username = trim($_POST['username']);
    $password = md5($_POST['password']); // ใช้ MD5 เข้ารหัสรหัสผ่าน
    
    // ค้นหาผู้ใช้ในฐานข้อมูล
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่าพบผู้ใช้หรือไม่และรหัสผ่านถูกต้องหรือไม่
    if ($user && $password === $user['password']) {
        // บันทึกข้อมูลการเข้าสู่ระบบ
        $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
        $updateStmt->execute([$user['admin_id']]);
        
        // เก็บข้อมูลผู้ใช้ในเซสชัน
        $_SESSION['user_id'] = $user['admin_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['is_logged_in'] = true;
        
        // ลบข้อความผิดพลาดถ้ามี
        if (isset($_SESSION['error'])) {
            unset($_SESSION['error']);
        }
        
        // เปลี่ยนเส้นทางไปยังหน้าแดชบอร์ด
        header('Location: admin/index.php');
        exit;
    } else {
        $_SESSION['error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}
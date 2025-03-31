<?php
session_start();
require_once 'config/line_config.php';
require_once 'db_connect.php';

// รับ authorization code จาก LINE
$code = $_GET['code'] ?? null;

if ($code) {
    // แลกเปลี่ยน code เพื่อรับ access token
    $url = 'https://api.line.me/oauth2/v2.1/token';
    
    $data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => LINE_CALLBACK_URL,
        'client_id' => LINE_CHANNEL_ID,
        'client_secret' => LINE_CHANNEL_SECRET
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        // จัดการข้อผิดพลาด
        die('เกิดข้อผิดพลาดในการเชื่อมต่อกับ LINE, กรุณาลองใหม่อีกครั้ง');
    }
    
    $token_data = json_decode($result, true);
    $access_token = $token_data['access_token'] ?? null;
    
    if ($access_token) {
        // ใช้ access token เพื่อดึงข้อมูลผู้ใช้จาก LINE
        $profile_url = 'https://api.line.me/v2/profile';
        
        $profile_options = [
            'http' => [
                'header' => "Authorization: Bearer $access_token\r\n",
                'method' => 'GET'
            ]
        ];
        
        $profile_context = stream_context_create($profile_options);
        $profile_result = file_get_contents($profile_url, false, $profile_context);
        
        if ($profile_result === FALSE) {
            die('เกิดข้อผิดพลาดในการดึงข้อมูลโปรไฟล์ LINE, กรุณาลองใหม่อีกครั้ง');
        }
        
        $user_data = json_decode($profile_result, true);
        $line_user_id = $user_data['userId'] ?? null;
        $display_name = $user_data['displayName'] ?? null;
        $picture_url = $user_data['pictureUrl'] ?? null;
        
        // ตรวจสอบว่ามี LINE User ID ในระบบหรือไม่
        try {
            $conn = getDB();
            
            $stmt = $conn->prepare("SELECT u.user_id, u.role, u.first_name, u.last_name 
                                    FROM users u 
                                    WHERE u.line_user_id = ?");
            $stmt->execute([$line_user_id]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // ถ้ามีผู้ใช้ในระบบ ให้ล็อกอิน
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['line_user_id'] = $line_user_id;
                $_SESSION['line_profile_picture'] = $picture_url;
                
                // อัปเดตรูปโปรไฟล์ในฐานข้อมูล (ถ้ามี)
                if ($picture_url) {
                    $update_stmt = $conn->prepare("
                        UPDATE users 
                        SET profile_picture = ?, 
                            updated_at = NOW() 
                        WHERE user_id = ? AND (profile_picture IS NULL OR profile_picture = '' OR profile_picture != ?)
                    ");
                    $update_stmt->execute([$picture_url, $user['user_id'], $picture_url]);
                }
                
                // Redirect ตามบทบาท
                switch ($user['role']) {
                    case 'student':
                        header('Location: student/home.php');
                        break;
                    case 'teacher':
                        header('Location: teacher/home.php');
                        break;
                    case 'admin':
                        header('Location: admin/index.php');
                        break;
                    case 'parent':
                        header('Location: parent/home.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit;
            } else {
                // ถ้ายังไม่มีผู้ใช้ในระบบ ให้เก็บข้อมูลไว้ใน session
                $_SESSION['line_user_id'] = $line_user_id;
                $_SESSION['line_display_name'] = $display_name;
                $_SESSION['line_profile_picture'] = $picture_url;
                
                // ส่งไปยังหน้าลงทะเบียน
                header('Location: register.php');
                exit;
            }
            
        } catch (PDOException $e) {
            die('เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล: ' . $e->getMessage());
        }
    }
}

// กรณีมีข้อผิดพลาด ให้กลับไปที่หน้าล็อกอิน
header('Location: index.php?error=line_auth_failed');
exit;
?> 
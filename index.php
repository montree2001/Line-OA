<?php
require 'config.php'; // เรียกใช้ค่าที่เก็บไว้ใน config.php

$state = bin2hex(random_bytes(16)); // ป้องกัน CSRF

// สร้าง URL ไปยังหน้า Login ของ LINE
$line_login_url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id=" . LINE_CLIENT_ID . "&redirect_uri=" . urlencode(LINE_REDIRECT_URI) . "&state=$state&scope=profile%20openid";

echo "<a href='$line_login_url'><button>เข้าสู่ระบบด้วย LINE</button></a>";
?>

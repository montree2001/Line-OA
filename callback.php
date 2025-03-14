<?php
require 'config.php'; // ดึงค่าจาก config.php
session_start();

// ตรวจสอบว่ามีโค้ดที่ได้รับจาก LINE หรือไม่
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // ขอ Access Token จาก LINE
    $token_url = "https://api.line.me/oauth2/v2.1/token";
    $data = [
        "grant_type" => "authorization_code",
        "code" => $code,
        "redirect_uri" => LINE_REDIRECT_URI,
        "client_id" => LINE_CLIENT_ID,
        "client_secret" => LINE_CLIENT_SECRET
    ];

    $headers = ["Content-Type: application/x-www-form-urlencoded"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response["access_token"])) {
        $access_token = $response["access_token"];

        // เก็บ Access Token ใน SESSION
        $_SESSION['access_token'] = $access_token;

        // ดึงข้อมูลโปรไฟล์จาก LINE
        $profile_url = "https://api.line.me/v2/profile";
        $headers = ["Authorization: Bearer " . $access_token];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $profile_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $profile = json_decode(curl_exec($ch), true);
        curl_close($ch);

        // แสดงข้อมูลผู้ใช้
        echo "สวัสดี, " . $profile['displayName'] . "<br>";
        echo "<img src='" . $profile['pictureUrl'] . "' width='100'><br>";

        // แสดงฟอร์มให้กรอกข้อมูล
        echo '<form action="save_user.php" method="POST">
                <input type="hidden" name="line_id" value="' . $profile['userId'] . '">
                <label for="title">คำนำหน้า:</label><br>
                <input type="text" name="title" required><br>
                <label for="first_name">ชื่อ:</label><br>
                <input type="text" name="first_name" required><br>
                <label for="last_name">สกุล:</label><br>
                <input type="text" name="last_name" required><br>
                <label for="phone_number">เบอร์โทรศัพท์:</label><br>
                <input type="text" name="phone_number" required><br>
                <input type="submit" value="บันทึกข้อมูล">
              </form>';

    } else {
        echo "❌ ไม่สามารถรับ Token ได้";
    }
} else {
    echo "❌ Login ผิดพลาด";
}
?>

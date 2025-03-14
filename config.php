<?php
define('LINE_CLIENT_ID', '2007063117'); // ใส่ Channel ID
define('LINE_CLIENT_SECRET', 'e3039e56eb9d9329487cb0e3eb097cb2'); // ใส่ Channel Secret
define('LINE_REDIRECT_URI', 'https://activity.prasat.ac.th/line/callback.php'); // เปลี่ยนเป็น URL ของคุณ

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "line_server", "Msezcnnt5maSAzBT", "line_server"); // แก้ไขให้ตรงกับข้อมูลของคุณ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

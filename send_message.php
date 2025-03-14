<?php
require 'config.php'; // ดึงค่าจาก config.php

// ดึง LINE ID ของผู้ปกครองที่ลงทะเบียนไว้
$sql = "SELECT line_id FROM parents";
$result = $conn->query($sql);

// ใส่ Access Token ของ LINE Messaging API
$accessToken = "YOUR_CHANNEL_ACCESS_TOKEN";
$headers = ["Content-Type: application/json", "Authorization: Bearer " . $accessToken];

while ($row = $result->fetch_assoc()) {
    $userId = $row['line_id'];
    $message = "📢 แจ้งเตือนจากโรงเรียน: กรุณาตรวจสอบสถานะเช็คชื่อของลูกคุณ!";

    $data = [
        "to" => $userId,
        "messages" => [["type" => "text", "text" => $message]]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

$conn->close();
echo "✅ ส่งข้อความแจ้งเตือนสำเร็จ!";
?>

<?php
require 'config.php'; // ดึงค่าจาก config.php
session_start();

// ตรวจสอบว่า access_token ใน session มีอยู่หรือไม่
if (isset($_SESSION['access_token'])) {
    $access_token = $_SESSION['access_token'];

    // ดึงข้อมูลโปรไฟล์จาก LINE
    $profile_url = "https://api.line.me/v2/profile";
    $headers = ["Authorization: Bearer " . $access_token];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $profile_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $profile = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($profile['pictureUrl'])) {
        $profile_picture_url = $profile['pictureUrl']; // รูปโปรไฟล์ของผู้ใช้
    } else {
        $profile_picture_url = ''; // หากไม่มีรูป
    }
} else {
    echo "❌ ไม่พบข้อมูลการเชื่อมต่อ";
    exit;
}

// รับข้อมูลจากฟอร์ม
$line_id = $_POST['line_id'];
$title = $conn->real_escape_string($_POST['title']);
$first_name = $conn->real_escape_string($_POST['first_name']);
$last_name = $conn->real_escape_string($_POST['last_name']);
$phone_number = $conn->real_escape_string($_POST['phone_number']);

// บันทึกข้อมูลลงฐานข้อมูล
$sql = "INSERT INTO users (line_id, title, first_name, last_name, phone_number, profile_picture_url)
        VALUES ('$line_id', '$title', '$first_name', '$last_name', '$phone_number', '$profile_picture_url')";

if ($conn->query($sql) === TRUE) {
    echo "✅ ข้อมูลถูกบันทึกเรียบร้อย!";
} else {
    echo "❌ เกิดข้อผิดพลาด: " . $conn->error;
}

$conn->close();
?>

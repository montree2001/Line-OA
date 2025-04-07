<?php
require_once '../../config/db_config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['error' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// ตรวจสอบโครงสร้างตาราง pins
$structure_query = "DESCRIBE pins";
$structure_result = $conn->query($structure_query);

if ($structure_result === false) {
    echo json_encode(['error' => 'ไม่พบตาราง pins: ' . $conn->error]);
    exit;
}

$columns = [];
while($row = $structure_result->fetch_assoc()) {
    $columns[] = $row;
}

// ตรวจสอบจำนวนแถวในตาราง pins
$count_query = "SELECT COUNT(*) as total FROM pins";
$count_result = $conn->query($count_query);
$count_data = $count_result ? $count_result->fetch_assoc() : ['total' => 'Error'];

// ทดสอบการเพิ่มข้อมูลอย่างง่าย
$test_query = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id) 
              VALUES ('1234', 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 1, 1)";
$test_result = $conn->query($test_query);

echo json_encode([
    'table_structure' => $columns,
    'row_count' => $count_data,
    'test_insert' => $test_result ? 'สำเร็จ' : 'ล้มเหลว: ' . $conn->error,
    'last_insert_id' => $test_result ? $conn->insert_id : 0
]);

$conn->close();
?> 
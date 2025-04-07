<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    'user_id' => $_SESSION['user_id'] ?? 'ไม่มี',
    'role' => $_SESSION['role'] ?? 'ไม่มี',
    'logged_in' => isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'yes' : 'no') : 'ไม่มี'
]);
?> 
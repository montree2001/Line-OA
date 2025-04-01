<?php
/**
 * check-in-with-db.php - หน้าเช็คชื่อเข้าแถวที่เชื่อมต่อกับฐานข้อมูลจริง
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทนักเรียนหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

// รับข้อมูลจาก session
$user_id = $_SESSION['user_id'] ?? null;

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลนักเรียน
try {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.student_code, s.title, s.current_class_id, 
               u.first_name, u.last_name, u.profile_picture, u.phone_number, u.email,
               c.level, c.group_number, d.department_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN classes c ON s.current_class_id = c.class_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        // ไม่พบข้อมูลนักเรียน - อาจยังไม่ได้ลงทะเบียน
        header('Location: register.php');
        exit;
    }
    
    // ตรวจสอบปีการศึกษาปัจจุบัน
    $stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_academic_year_id = $academic_year['academic_year_id'] ?? null;
    
    // ตรวจสอบการเช็คชื่อวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? AND date = ?
    ");
    $stmt->execute([$student['student_id'], $today]);
    $today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่าได้เช็คชื่อแล้วหรือยัง
    $already_checked_in = !empty($today_attendance);
    
    // ดึงค่าพิกัด GPS จากการตั้งค่า
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_latitude'
    ");
    $stmt->execute();
    $school_lat = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'school_longitude'
    ");
    $stmt->execute();
    $school_lng = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '0';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'gps_radius'
    ");
    $stmt->execute();
    $gps_radius = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '100';
    
    // ดึงเวลาเช็คชื่อ
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time'
    ");
    $stmt->execute();
    $start_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '07:30';
    
    $stmt = $conn->prepare("
        SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time'
    ");
    $stmt->execute();
    $end_time = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? '08:30';
    
    // ตรวจสอบสถานะ QR Code
    $stmt = $conn->prepare("
        SELECT * FROM qr_codes 
        WHERE student_id = ? AND is_active = 1 AND valid_until > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student['student_id']]);
    $current_qr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $has_active_qr = !empty($current_qr);
    $qr_expire_time = $has_active_qr ? date('H:i', strtotime($current_qr['valid_until'])) : '';
    $qr_data = $has_active_qr ? $current_qr['qr_code_data'] : '';
    
    // ตรวจสอบเวลาปัจจุบันว่าอยู่ในช่วงเช็คชื่อหรือไม่
    $current_time = date('H:i');
    $check_in_available = ($current_time >= $start_time && $current_time <= $end_time);
    
    // สร้างข้อมูลสำหรับแสดงผลในหน้าเว็บ
    $student_info = [
        'id' => $student['student_id'],
        'code' => $student['student_code'],
        'name' => $student['title'] . $student['first_name'] . ' ' . $student['last_name'],
        'class' => $student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number'],
        'profile_picture' => $student['profile_picture']
    ];
    
    $gps_info = [
        'lat' => $school_lat,
        'lng' => $school_lng,
        'radius' => $gps_radius,
    ];
    
    $attendance_info = [
        'already_checked_in' => $already_checked_in,
        'check_in_method' => $already_checked_in ? $today_attendance['check_method'] : '',
        'check_in_time' => $already_checked_in ? date('H:i', strtotime($today_attendance['check_time'])) : '',
        'check_in_available' => $check_in_available,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ];
    
    $qr_info = [
        'has_active_qr' => $has_active_qr,
        'qr_data' => $qr_data,
        'expire_time' => $qr_expire_time,
    ];
    
    // สร้างตัวอักษรแรกของชื่อสำหรับใช้แสดงในกรณีไม่มีรูปโปรไฟล์
    $first_char = mb_substr($student['first_name'], 0, 1, 'UTF-8');
    
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เช็คชื่อเข้าแถว - STD-Prasat</title>
    
    <!-- Material Icons & Google Fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    
    <style>
        /* ตั้งค่าพื้นฐาน */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
        }

        /* ส่วนหัว */
        .header {
            background-color: #06c755;
            color: white;
            padding: 15px 20px;
            text-align: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 20px;
            font-weight: 600;
        }

        .back-button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-spacer {
            width: 24px; /* สำหรับความสมดุลกับปุ่มย้อนกลับ */
        }

        .container {
            max-width: 600px;
            margin: 70px auto 80px;
            padding: 15px;
        }

        /* ข้อความแสดงข้อผิดพลาด */
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #c62828;
        }

        /* การ์ดแสดงผลการเช็คชื่อ */
        .check-in-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 20px;
        }

        .success-card {
            border-left: 5px solid #4caf50;
        }

        .warning-card {
            border-left: 5px solid #ff9800;
        }

        .error-card {
            border-left: 5px solid #f44336;
        }

        .check-in-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .check-in-icon .material-icons {
            font-size: 48px;
        }

        .success-icon {
            color: #4caf50;
        }

        .warning-icon {
            color: #ff9800;
        }

        .error-icon {
            color: #f44336;
        }

        .check-in-message {
            flex: 1;
        }

        .check-in-message h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .check-in-message p {
            color: #666;
            margin-bottom: 15px;
        }

        .check-in-details {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 10px 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item .material-icons {
            font-size: 18px;
            color: #666;
        }

        /* โปรไฟล์สรุป */
        .profile-summary {
            background-color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .profile-info {
            display: flex;
            align-items: center;
        }

        .profile-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #06c755;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .profile-image-pic {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: transparent;
            border: 2px solid #06c755;
        }

        .profile-details {
            flex: 1;
        }

        .profile-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .profile-class {
            font-size: 14px;
            color: #666;
        }

        .check-time {
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .time-label {
            font-weight: 500;
            color: #666;
        }

        .time-value {
            font-weight: bold;
            color: #06c755;
        }

        /* Tab Navigation */
        .tab-container {
            background-color: white;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-header {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
        }

        .tab-item {
            flex: 1;
            text-align: center;
            padding: 12px 5px;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .tab-item:hover {
            background-color: #f9f9f9;
        }

        .tab-item.active {
            color: #06c755;
            border-bottom: 3px solid #06c755;
            background-color: #f9f9f9;
        }

        .tab-item .material-icons {
            font-size: 20px;
        }

        .tab-content {
            padding: 20px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .tab-description {
            margin-bottom: 20px;
            text-align: center;
        }

        .tab-description p {
            margin-bottom: 5px;
        }

        .tab-description .small {
            font-size: 12px;
            color: #666;
        }

        /* GPS Tab */
        .map-container {
            height: 250px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        .location-status {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .status-icon {
            margin-right: 10px;
        }

        .status-icon .material-icons {
            color: #666;
        }

        .status-icon .material-icons.success {
            color: #4caf50;
        }

        .status-icon .material-icons.error {
            color: #f44336;
        }

        .status-text {
            font-size: 14px;
        }

        .status-text.success {
            color: #4caf50;
        }

        .status-text.error {
            color: #f44336;
        }

        /* QR Code Tab */
        .qr-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .qr-wrapper {
            width: 200px;
            height: 200px;
            border: 2px dashed #e0e0e0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            position: relative;
        }

        .qr-wrapper.active {
            border: 2px solid #06c755;
        }

        .qr-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #999;
            text-align: center;
            padding: 20px;
        }

        .qr-placeholder .material-icons {
            font-size: 48px;
            margin-bottom: 10px;
        }

        #qr-display {
            padding: 10px;
            background-color: white;
        }

        .qr-expire {
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: #f44336;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .qr-expire .material-icons {
            font-size: 16px;
        }

        /* PIN Tab */
        .pin-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .pin-input-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .pin-input {
            width: 50px;
            height: 60px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            transition: border-color 0.3s;
        }

        .pin-input:focus {
            border-color: #06c755;
            outline: none;
        }

        .pin-status {
            height: 20px;
            font-size: 14px;
            color: #f44336;
        }

        /* ปุ่มกด */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            border: none;
            width: 100%;
            gap: 10px;
            transition: transform 0.2s, background-color 0.3s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn.primary {
            background-color: #06c755;
            color: white;
        }

        .btn.secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #e0e0e0;
        }

        .btn:disabled {
            background-color: #cccccc;
            color: #666;
            cursor: not-allowed;
        }

        .btn .material-icons {
            font-size: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* Scan QR Tab */
        .scanner-container {
            position: relative;
            width: 100%;
            height: 250px;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            background-color: #000;
        }

        .scanner {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .scanner-frame {
            width: 200px;
            height: 200px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            position: relative;
        }

        .scanner-frame::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 30px;
            height: 30px;
            border-top: 4px solid #06c755;
            border-left: 4px solid #06c755;
            border-top-left-radius: 8px;
        }

        .scanner-frame::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 30px;
            height: 30px;
            border-top: 4px solid #06c755;
            border-right: 4px solid #06c755;
            border-top-right-radius: 8px;
        }

        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #06c755;
            animation: scan-animation 2s linear infinite;
        }

        @keyframes scan-animation {
            0% {
                opacity: 0.5;
                transform: translateY(-100px);
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0.5;
                transform: translateY(100px);
            }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 20% auto;
            width: 90%;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: slide-up 0.3s ease-out;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 18px;
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .modal-icon.success {
            color: #4caf50;
        }

        .modal-icon.error {
            color: #f44336;
        }

        .modal-message {
            margin-bottom: 10px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            text-align: right;
        }

        @keyframes slide-up {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Loading Indicator */
        .loading-indicator {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
        }

        .loading-indicator.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid #06c755;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* แถบนำทางด้านล่าง */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
            color: #666;
            text-decoration: none;
        }

        .nav-item.active {
            color: #06c755;
        }

        .nav-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-button" onclick="history.back()">
            <span class="material-icons">arrow_back</span>
        </button>
        <div class="header-title">เช็คชื่อเข้าแถว</div>
        <div class="header-spacer"></div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($attendance_info['already_checked_in']): ?>
        <!-- กรณีเช็คชื่อไปแล้ว -->
        <div class="check-in-card success-card">
            <div class="check-in-icon">
                <span class="material-icons success-icon">check_circle</span>
            </div>
            <div class="check-in-message">
                <h2>เช็คชื่อเข้าแถวสำเร็จ</h2>
                <p>คุณได้เช็คชื่อเข้าแถวของวันนี้เรียบร้อยแล้ว</p>
                <div class="check-in-details">
                    <div class="detail-item">
                        <span class="material-icons">access_time</span>
                        <span>เวลาเช็คชื่อ: <?php echo $attendance_info['check_in_time']; ?> น.</span>
                    </div>
                    <div class="detail-item">
                        <span class="material-icons">how_to_reg</span>
                        <span>วิธีการเช็คชื่อ: 
                            <?php 
                                switch($attendance_info['check_in_method']) {
                                    case 'GPS':
                                        echo 'เช็คชื่อผ่าน GPS';
                                        break;
                                    case 'QR_Code':
                                        echo 'เช็คชื่อด้วย QR Code';
                                        break;
                                    case 'PIN':
                                        echo 'เช็คชื่อด้วยรหัส PIN';
                                        break;
                                    case 'Manual':
                                        echo 'ครูเช็คชื่อให้';
                                        break;
                                    default:
                                        echo $attendance_info['check_in_method'];
                                }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="home.php" class="btn primary">
                <span class="material-icons">home</span> กลับหน้าหลัก
            </a>
            <a href="history.php" class="btn secondary">
                <span class="material-icons">history</span> ดูประวัติการเช็คชื่อ
            </a>
        </div>
        
        <?php elseif (!$attendance_info['check_in_available']): ?>
        <!-- กรณีไม่อยู่ในช่วงเวลาเช็คชื่อ -->
        <div class="check-in-card warning-card">
            <div class="check-in-icon">
                <span class="material-icons warning-icon">access_time</span>
            </div>
            <div class="check-in-message">
                <h2>ไม่อยู่ในช่วงเวลาเช็คชื่อ</h2>
                <p>ขณะนี้ไม่อยู่ในช่วงเวลาเช็คชื่อเข้าแถว</p>
                <div class="check-in-details">
                    <div class="detail-item">
                        <span class="material-icons">schedule</span>
                        <span>ช่วงเวลาเช็คชื่อ: <?php echo $attendance_info['start_time']; ?> - <?php echo $attendance_info['end_time']; ?> น.</span>
                    </div>
                    <div class="detail-item">
                        <span class="material-icons">today</span>
                        <span>วันที่: <?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="home.php" class="btn primary">
                <span class="material-icons">home</span> กลับหน้าหลัก
            </a>
        </div>
        
        <?php else: ?>
        <!-- กรณีพร้อมเช็คชื่อ -->
        <div class="profile-summary">
            <div class="profile-info">
                <?php if (!empty($student_info['profile_picture'])): ?>
                    <div class="profile-image profile-image-pic" style="background-image: url('<?php echo $student_info['profile_picture']; ?>');"></div>
                <?php else: ?>
                    <div class="profile-image"><?php echo $first_char; ?></div>
                <?php endif; ?>
                <div class="profile-details">
                    <div class="profile-name"><?php echo $student_info['name']; ?></div>
                    <div class="profile-class"><?php echo $student_info['class']; ?></div>
                </div>
            </div>
            <div class="check-time">
                <div class="time-label">เวลาเช็คชื่อ:</div>
                <div class="time-value"><?php echo $attendance_info['start_time']; ?> - <?php echo $attendance_info['end_time']; ?> น.</div>
            </div>
        </div>
        
        <div class="tab-container">
            <div class="tab-header">
                <div class="tab-item active" data-tab="gps">
                    <span class="material-icons">gps_fixed</span>
                    <span>GPS</span>
                </div>
                <div class="tab-item" data-tab="qr">
                    <span class="material-icons">qr_code</span>
                    <span>QR Code</span>
                </div>
                <div class="tab-item" data-tab="pin">
                    <span class="material-icons">pin</span>
                    <span>PIN</span>
                </div>
                <div class="tab-item" data-tab="scan">
                    <span class="material-icons">qr_code_scanner</span>
                    <span>สแกน QR</span>
                </div>
            </div>
            
            <div class="tab-content">
                <!-- GPS Tab -->
                <div class="tab-pane active" id="gps-tab">
                    <div class="tab-description">
                        <p>เช็คชื่อด้วยตำแหน่ง GPS ของคุณ</p>
                        <p class="small">คุณต้องอยู่ในรัศมีที่กำหนดจากจุดศูนย์กลางของวิทยาลัย</p>
                    </div>
                    
                    <div id="map" class="map-container"></div>
                    
                    <div class="location-status">
                        <div class="status-icon">
                            <span class="material-icons" id="location-icon">location_searching</span>
                        </div>
                        <div class="status-text" id="location-status">กำลังค้นหาตำแหน่งของคุณ...</div>
                    </div>
                    
                    <button id="check-in-gps" class="btn primary check-in-btn" disabled>
                        <span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS
                    </button>
                    
                    <input type="hidden" id="user-lat" value="">
                    <input type="hidden" id="user-lng" value="">
                    <input type="hidden" id="school-lat" value="<?php echo $gps_info['lat']; ?>">
                    <input type="hidden" id="school-lng" value="<?php echo $gps_info['lng']; ?>">
                    <input type="hidden" id="gps-radius" value="<?php echo $gps_info['radius']; ?>">
                    <input type="hidden" id="student-id" value="<?php echo $student_info['id']; ?>">
                </div>
                
                <!-- QR Code Tab -->
                <div class="tab-pane" id="qr-tab">
                    <div class="tab-description">
                        <p>สร้าง QR Code ให้ครูสแกนเพื่อเช็คชื่อ</p>
                        <p class="small">QR Code จะหมดอายุภายใน 5 นาที หลังจากสร้าง</p>
                    </div>
                    
                    <div class="qr-container">
                        <?php if ($qr_info['has_active_qr']): ?>
                        <div class="qr-wrapper active">
                            <div id="qr-display"></div>
                            <div class="qr-expire">
                                <span class="material-icons">access_time</span>
                                <span>หมดอายุเวลา <?php echo $qr_info['expire_time']; ?> น.</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="qr-wrapper">
                            <div class="qr-placeholder">
                                <span class="material-icons">qr_code</span>
                                <span>กดปุ่มด้านล่างเพื่อสร้าง QR Code</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($qr_info['has_active_qr']): ?>
                    <button id="check-qr-status" class="btn secondary">
                        <span class="material-icons">refresh</span> ตรวจสอบสถานะ
                    </button>
                    <?php else: ?>
                    <button id="generate-qr" class="btn primary">
                        <span class="material-icons">qr_code</span> สร้าง QR Code
                    </button>
                    <?php endif; ?>
                    
                    <input type="hidden" id="existing-qr-data" value='<?php echo htmlspecialchars($qr_info["qr_data"]); ?>'>
                </div>
                
                <!-- PIN Tab -->
                <div class="tab-pane" id="pin-tab">
                    <div class="tab-description">
                        <p>กรอกรหัส PIN ที่ได้รับจากครู</p>
                        <p class="small">รหัส PIN ประกอบด้วยตัวเลข 4 หลัก</p>
                    </div>
                    
                    <div class="pin-container">
                        <div class="pin-input-group">
                            <input type="text" class="pin-input" maxlength="1" data-index="0" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="1" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="2" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="3" pattern="[0-9]" inputmode="numeric">
                        </div>
                        <div class="pin-status" id="pin-status"></div>
                    </div>
                    
                    <button id="submit-pin" class="btn primary" disabled>
                        <span class="material-icons">check_circle</span> ยืนยันรหัส PIN
                    </button>
                </div>
                
                <!-- Scan QR Tab -->
                <div class="tab-pane" id="scan-tab">
                    <div class="tab-description">
                        <p>สแกน QR Code จากครู</p>
                        <p class="small">อนุญาตการเข้าถึงกล้องเพื่อสแกน QR Code</p>
                    </div>
                    
                    <div class="scanner-container">
                        <video id="qr-scanner" class="scanner"></video>
                        <div class="scanner-overlay">
                            <div class="scanner-frame"></div>
                        </div>
                    </div>
                    
                    <button id="start-scan" class="btn primary">
                        <span class="material-icons">qr_code_scanner</span> เริ่มสแกน QR Code
                    </button>
                    
                    <button id="stop-scan" class="btn secondary" style="display: none;">
                        <span class="material-icons">stop</span> หยุดสแกน
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal สำหรับแสดงผลการเช็คชื่อ -->
    <div class="modal" id="result-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">ผลการเช็คชื่อ</h2>
                <button class="close-modal" id="close-modal">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- ข้อความจะถูกเพิ่มด้วย JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn primary" id="modal-ok">ตกลง</button>
            </div>
        </div>
    </div>

    <!-- แถบนำทางด้านล่าง -->
    <div class="bottom-nav">
        <a href="home.php" class="nav-item">
            <span class="material-icons nav-icon">home</span>
            <span>หน้าหลัก</span>
        </a>
        <a href="check-in.php" class="nav-item active">
            <span class="material-icons nav-icon">how_to_reg</span>
            <span>เช็คชื่อ</span>
        </a>
        <a href="history.php" class="nav-item">
            <span class="material-icons nav-icon">history</span>
            <span>ประวัติ</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="material-icons nav-icon">person</span>
            <span>โปรไฟล์</span>
        </a>
    </div>

    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loading-indicator">
        <div class="spinner"></div>
        <div>กำลังดำเนินการ...</div>
    </div>

    <!-- เพิ่มลิงก์ CSS สำหรับ Leaflet Map -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    
    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab Navigation
            const tabItems = document.querySelectorAll('.tab-item');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            if (tabItems.length > 0) {
                tabItems.forEach(item => {
                    item.addEventListener('click', function() {
                        const tabId = this.getAttribute('data-tab');
                        
                        // ยกเลิกการ active ของ tabs อื่นๆ
                        tabItems.forEach(tab => tab.classList.remove('active'));
                        tabPanes.forEach(pane => pane.classList.remove('active'));
                        
                        // Active tab ที่ถูกคลิก
                        this.classList.add('active');
                        document.getElementById(`${tabId}-tab`).classList.add('active');
                        
                        // หยุดการสแกนถ้ากำลังสแกนอยู่และเปลี่ยนแท็บ
                        if (tabId !== 'scan' && window.scanner) {
                            stopQRScanner();
                        }
                    });
                });
            }
            
            // ============== ส่วนการเช็คชื่อด้วย GPS ==============
            const mapContainer = document.getElementById('map');
            let map, userMarker, schoolMarker, accuracyCircle, boundaryCircle;
            let userLocation = null;
            
            if (mapContainer) {
                initMap();
                startGPSTracking();
            }
            
            function initMap() {
                const schoolLat = parseFloat(document.getElementById('school-lat').value);
                const schoolLng = parseFloat(document.getElementById('school-lng').value);
                const gpsRadius = parseInt(document.getElementById('gps-radius').value);
                
                // สร้างแผนที่
                map = L.map('map').setView([schoolLat, schoolLng], 16);
                
                // เพิ่ม OpenStreetMap layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // สร้าง marker จุดที่ตั้งโรงเรียน
                schoolMarker = L.marker([schoolLat, schoolLng], {
                    icon: L.divIcon({
                        className: 'school-marker',
                        html: '<span class="material-icons" style="color: #06c755; font-size: 32px; background: white; border-radius: 50%; padding: 3px;">school</span>',
                        iconSize: [32, 32],
                        iconAnchor: [16, 32]
                    })
                }).addTo(map);
                schoolMarker.bindPopup("วิทยาลัยการอาชีพปราสาท");
                
                // สร้างวงกลมแสดงรัศมีที่อนุญาตให้เช็คชื่อได้
                boundaryCircle = L.circle([schoolLat, schoolLng], {
                    radius: gpsRadius,
                    color: '#06c755',
                    fillColor: '#06c755',
                    fillOpacity: 0.1
                }).addTo(map);
            }
            
            function startGPSTracking() {
                if (navigator.geolocation) {
                    const locationIcon = document.getElementById('location-icon');
                    const locationStatus = document.getElementById('location-status');
                    
                    locationIcon.textContent = 'location_searching';
                    locationStatus.textContent = 'กำลังค้นหาตำแหน่งของคุณ...';
                    locationIcon.className = 'material-icons';
                    locationStatus.className = 'status-text';
                    
                    navigator.geolocation.watchPosition(
                        function(position) {
                            userLocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                                accuracy: position.coords.accuracy
                            };
                            
                            document.getElementById('user-lat').value = userLocation.lat;
                            document.getElementById('user-lng').value = userLocation.lng;
                            
                            updateUserMarker(userLocation);
                            checkLocationValidity(userLocation);
                        },
                        function(error) {
                            console.error('Error getting location:', error);
                            
                            locationIcon.textContent = 'error';
                            locationStatus.textContent = 'ไม่สามารถระบุตำแหน่งของคุณได้ กรุณาเปิดการใช้งาน GPS';
                            locationIcon.className = 'material-icons error';
                            locationStatus.className = 'status-text error';
                            
                            document.getElementById('check-in-gps').disabled = true;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    showStatusMessage('error', 'เบราว์เซอร์ของคุณไม่รองรับการใช้งาน GPS');
                    document.getElementById('check-in-gps').disabled = true;
                }
            }
            
            function updateUserMarker(location) {
                const schoolLat = parseFloat(document.getElementById('school-lat').value);
                const schoolLng = parseFloat(document.getElementById('school-lng').value);
                
                // ลบ marker เดิมถ้ามี
                if (userMarker) map.removeLayer(userMarker);
                if (accuracyCircle) map.removeLayer(accuracyCircle);
                
                // สร้าง marker ใหม่
                userMarker = L.marker([location.lat, location.lng], {
                    icon: L.divIcon({
                        className: 'user-marker',
                        html: '<span class="material-icons" style="color: #2196F3; font-size: 24px; background: white; border-radius: 50%; padding: 3px;">person_pin</span>',
                        iconSize: [24, 24],
                        iconAnchor: [12, 24]
                    })
                }).addTo(map);
                userMarker.bindPopup("ตำแหน่งของคุณ");
                
                // สร้างวงกลมแสดงความแม่นยำ
                accuracyCircle = L.circle([location.lat, location.lng], {
                    radius: location.accuracy,
                    color: '#2196F3',
                    fillColor: '#2196F3',
                    fillOpacity: 0.2
                }).addTo(map);
                
                // ปรับมุมมองแผนที่เพื่อให้เห็นทั้ง marker ของผู้ใช้และโรงเรียน
                const bounds = L.latLngBounds(
                    L.latLng(location.lat, location.lng),
                    L.latLng(schoolLat, schoolLng)
                );
                map.fitBounds(bounds.pad(0.3));
            }
            
            function checkLocationValidity(location) {
                const schoolLat = parseFloat(document.getElementById('school-lat').value);
                const schoolLng = parseFloat(document.getElementById('school-lng').value);
                const gpsRadius = parseInt(document.getElementById('gps-radius').value);
                
                // คำนวณระยะห่างระหว่างผู้ใช้กับโรงเรียน
                const distance = getDistance(
                    location.lat, location.lng,
                    schoolLat, schoolLng
                );
                
                const locationIcon = document.getElementById('location-icon');
                const locationStatus = document.getElementById('location-status');
                
                if (distance <= gpsRadius) {
                    // อยู่ในรัศมีที่กำหนด
                    locationIcon.textContent = 'check_circle';
                    locationStatus.textContent = `คุณอยู่ในรัศมีที่กำหนด (${Math.round(distance)} เมตร จากวิทยาลัย)`;
                    locationIcon.className = 'material-icons success';
                    locationStatus.className = 'status-text success';
                    
                    document.getElementById('check-in-gps').disabled = false;
                } else {
                    // อยู่นอกรัศมีที่กำหนด
                    locationIcon.textContent = 'location_off';
                    locationStatus.textContent = `คุณอยู่นอกรัศมีที่กำหนด (${Math.round(distance)} เมตร จากวิทยาลัย)`;
                    locationIcon.className = 'material-icons error';
                    locationStatus.className = 'status-text error';
                    
                    document.getElementById('check-in-gps').disabled = true;
                }
            }
            
            // ปุ่มเช็คชื่อด้วย GPS
            const checkInGpsBtn = document.getElementById('check-in-gps');
            if (checkInGpsBtn) {
                checkInGpsBtn.addEventListener('click', function() {
                    const lat = document.getElementById('user-lat').value;
                    const lng = document.getElementById('user-lng').value;
                    const studentId = document.getElementById('student-id').value;
                    
                    if (!lat || !lng) {
                        showResultModal('error', 'ไม่สามารถระบุตำแหน่งได้', 'กรุณาลองใหม่อีกครั้ง');
                        return;
                    }
                    
                    // แสดง loading
                    document.getElementById('loading-indicator').classList.add('active');
                    this.disabled = true;
                    
                    // ส่งข้อมูลไปยัง server
                    fetch('api/check_in.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId,
                            method: 'GPS',
                            lat: parseFloat(lat),
                            lng: parseFloat(lng)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว', true);
                        } else {
                            showResultModal('error', 'เช็คชื่อไม่สำเร็จ', data.message || 'เกิดข้อผิดพลาดในการเช็คชื่อ กรุณาลองใหม่อีกครั้ง');
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResultModal('error', 'เช็คชื่อไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                        this.disabled = false;
                    })
                    .finally(() => {
                        document.getElementById('loading-indicator').classList.remove('active');
                    });
                });
            }
            
            // ============== ส่วนการเช็คชื่อด้วย QR Code ==============
            // แสดง QR Code ที่มีอยู่แล้ว (ถ้ามี)
            const existingQrData = document.getElementById('existing-qr-data');
            if (existingQrData && existingQrData.value) {
                try {
                    const qrData = JSON.parse(existingQrData.value);
                    generateQRCode(qrData);
                } catch (e) {
                    console.error('Error parsing existing QR data:', e);
                }
            }
            
            // ปุ่มสร้าง QR Code
            const generateQrBtn = document.getElementById('generate-qr');
            if (generateQrBtn) {
                generateQrBtn.addEventListener('click', function() {
                    const studentId = document.getElementById('student-id').value;
                    
                    // แสดง loading
                    document.getElementById('loading-indicator').classList.add('active');
                    this.disabled = true;
                    
                    // ส่งข้อมูลไปยัง server
                    fetch('api/generate_qr.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // แสดง QR Code
                            generateQRCode(data.qr_data);
                            
                            // แสดงเวลาหมดอายุ
                            const expireTime = new Date(data.expire_time);
                            const hours = expireTime.getHours().toString().padStart(2, '0');
                            const minutes = expireTime.getMinutes().toString().padStart(2, '0');
                            
                            const qrWrapper = document.querySelector('.qr-wrapper');
                            qrWrapper.classList.add('active');
                            qrWrapper.innerHTML = `
                                <div id="qr-display"></div>
                                <div class="qr-expire">
                                    <span class="material-icons">access_time</span>
                                    <span>หมดอายุเวลา ${hours}:${minutes} น.</span>
                                </div>
                            `;
                            
                            // แสดง QR Code อีกครั้งที่ element ใหม่
                            document.getElementById('qr-display').innerHTML = '';
                            generateQRCode(data.qr_data, 'qr-display');
                            
                            // เปลี่ยนปุ่มเป็นปุ่มตรวจสอบสถานะ
                            this.style.display = 'none';
                            
                            // ถ้ายังไม่มีปุ่มตรวจสอบสถานะให้สร้างใหม่
                            if (!document.getElementById('check-qr-status')) {
                                const checkStatusBtn = document.createElement('button');
                                checkStatusBtn.id = 'check-qr-status';
                                checkStatusBtn.className = 'btn secondary';
                                checkStatusBtn.innerHTML = '<span class="material-icons">refresh</span> ตรวจสอบสถานะ';
                                this.parentNode.appendChild(checkStatusBtn);
                                
                                // เพิ่ม event listener
                                checkStatusBtn.addEventListener('click', checkQRStatus);
                            } else {
                                document.getElementById('check-qr-status').style.display = 'block';
                            }
                            
                        } else {
                            showResultModal('error', 'สร้าง QR Code ไม่สำเร็จ', data.message || 'เกิดข้อผิดพลาดในการสร้าง QR Code กรุณาลองใหม่อีกครั้ง');
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResultModal('error', 'สร้าง QR Code ไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                        this.disabled = false;
                    })
                    .finally(() => {
                        document.getElementById('loading-indicator').classList.remove('active');
                    });
                });
            }
            
            // ปุ่มตรวจสอบสถานะ QR Code
            const checkQrStatusBtn = document.getElementById('check-qr-status');
            if (checkQrStatusBtn) {
                checkQrStatusBtn.addEventListener('click', checkQRStatus);
            }
            
            function checkQRStatus() {
                const studentId = document.getElementById('student-id').value;
                
                // แสดง loading
                document.getElementById('loading-indicator').classList.add('active');
                this.disabled = true;
                
                // ส่งข้อมูลไปยัง server
                fetch('api/check_qr_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: studentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.is_checked_in) {
                            // QR Code ถูกใช้เช็คชื่อแล้ว
                            showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว', true);
                        } else {
                            // ยังไม่มีการเช็คชื่อ
                            showResultModal('info', 'ยังไม่มีการเช็คชื่อ', 'QR Code ของคุณยังไม่ถูกใช้เช็คชื่อ โปรดให้ครูสแกน');
                        }
                    } else {
                        if (data.expired) {
                            // QR Code หมดอายุ
                            showResultModal('warning', 'QR Code หมดอายุ', 'QR Code ของคุณหมดอายุแล้ว กรุณาสร้างใหม่', false, true);
                        } else {
                            showResultModal('error', 'ตรวจสอบไม่สำเร็จ', data.message || 'เกิดข้อผิดพลาดในการตรวจสอบ กรุณาลองใหม่อีกครั้ง');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResultModal('error', 'ตรวจสอบไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                })
                .finally(() => {
                    document.getElementById('loading-indicator').classList.remove('active');
                    this.disabled = false;
                });
            }
            
            // ============== ส่วนการเช็คชื่อด้วย PIN ==============
            const pinInputs = document.querySelectorAll('.pin-input');
            const submitPinBtn = document.getElementById('submit-pin');
            
            if (pinInputs.length > 0) {
                // ตั้งค่า input ให้รับได้เฉพาะตัวเลข และเลื่อนไปยัง input ถัดไปเมื่อกรอกเสร็จ
                pinInputs.forEach(input => {
                    input.addEventListener('keyup', function(e) {
                        // รับได้เฉพาะตัวเลข
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        const index = parseInt(this.getAttribute('data-index'));
                        
                        // ถ้ากดปุ่ม Backspace และช่องนี้ว่าง ให้ย้อนกลับไปยัง input ก่อนหน้า
                        if (e.key === 'Backspace' && this.value === '' && index > 0) {
                            pinInputs[index - 1].focus();
                            return;
                        }
                        
                        // ถ้ากรอกแล้ว ให้ไปยัง input ถัดไป
                        if (this.value.length === 1 && index < pinInputs.length - 1) {
                            pinInputs[index + 1].focus();
                        }
                        
                        // ตรวจสอบว่ากรอกครบทุกช่องหรือยัง
                        checkPINCompletion();
                    });
                    
                    // เมื่อคลิกที่ input ให้เลือกข้อความทั้งหมด
                    input.addEventListener('click', function() {
                        this.select();
                    });
                });
            }
            
            if (submitPinBtn) {
                submitPinBtn.addEventListener('click', function() {
                    // รวม PIN จากทุก input
                    let pin = '';
                    pinInputs.forEach(input => {
                        pin += input.value;
                    });
                    
                    const studentId = document.getElementById('student-id').value;
                    
                    // แสดง loading
                    document.getElementById('loading-indicator').classList.add('active');
                    this.disabled = true;
                    
                    // ส่งข้อมูลไปยัง server
                    fetch('api/check_in_pin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId,
                            pin: pin
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResultModal('success', 'เช็คชื่อสำเร็จ', 'คุณได้เช็คชื่อเข้าแถวเรียบร้อยแล้ว', true);
                        } else {
                            showResultModal('error', 'เช็คชื่อไม่สำเร็จ', data.message || 'รหัส PIN ไม่ถูกต้องหรือหมดอายุ');
                            
                            // ล้าง PIN
                            pinInputs.forEach(input => {
                                input.value = '';
                            });
                            pinInputs[0].focus();
                            
                            // แสดงข้อความผิดพลาด
                            document.getElementById('pin-status').textContent = 'รหัส PIN ไม่ถูกต้องหรือหมดอายุ';
                            
                            this.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showResultModal('error', 'เช็คชื่อไม่สำเร็จ', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
                        
                        // ล้าง PIN
                        pinInputs.forEach(input => {
                            input.value = '';
                        });
                        pinInputs[0].focus();
                        
                        this.disabled = true;
                    })
                    .finally(() => {
                        document.getElementById('loading-indicator').classList.remove('active');
                    });
                });
            }
            
            function checkPINCompletion() {
                let isComplete = true;
                const pinStatus = document.getElementById('pin-status');
                
                pinInputs.forEach(input => {
                    if (input.value === '') {
                        isComplete = false;
                    }
                });
                
                if (isComplete) {
                    submitPinBtn.disabled = false;
                    pinStatus.textContent = '';
                } else {
                    submitPinBtn.disabled = true;
                }
            }
            
            // ============== ส่วนสแกน QR Code ==============
            const startScanBtn = document.getElementById('start-scan');
            const stopScanBtn = document.getElementById('stop-scan');
            
            if (startScanBtn) {
                startScanBtn.addEventListener('click', function() {
                    startQRScanner();
                    this.style.display = 'none';
                    document.getElementById('stop-scan').style.display = 'block';
                });
            }
            
            if (stopScanBtn) {
                stopScanBtn.addEventListener('click', function() {
                    stopQRScanner();
                    this.style.display = 'none';
                    document.getElementById('start-scan').style.display = 'block';
                });
            }
            
            function startQRScanner() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showResultModal('error', 'ไม่รองรับกล้อง', 'เบราว์เซอร์ของคุณไม่รองรับการใช้งานกล้อง');
                    return;
                }
                
                const video = document.getElementById('qr-scanner');
                
                // ขออนุญาตใช้กล้อง
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(stream => {
                        // แสดงภาพจากกล้อง
                        video.srcObject = stream;
                        video.setAttribute('playsinline', true);
                        video.play();
                        
                        // เก็บ stream ไว้ใช้ยกเลิกภายหลัง
                        window.scanner = stream;
                    })
                    .catch(error => {
                        console.error('Error accessing camera:', error);
                        showResultModal('error', 'ไม่สามารถเข้าถึงกล้องได้', 'กรุณาอนุญาตการใช้งานกล้องและลองใหม่อีกครั้ง');
                        stopScanBtn.style.display = 'none';
                        startScanBtn.style.display = 'block';
                    });
            }
            
            function stopQRScanner() {
                if (window.scanner) {
                    // หยุดทุก track
                    window.scanner.getTracks().forEach(track => track.stop());
                    window.scanner = null;
                    
                    // ล้างวิดีโอ
                    const video = document.getElementById('qr-scanner');
                    if (video) {
                        video.srcObject = null;
                    }
                }
            }
            
            // ============== ฟังก์ชันสนับสนุน ==============
            function getDistance(lat1, lon1, lat2, lon2) {
                const R = 6371e3; // รัศมีของโลกในหน่วยเมตร
                const φ1 = lat1 * Math.PI / 180;
                const φ2 = lat2 * Math.PI / 180;
                const Δφ = (lat2 - lat1) * Math.PI / 180;
                const Δλ = (lon2 - lon1) * Math.PI / 180;
                
                const a = sin(Δφ / 2) * sin(Δφ / 2) +
                        cos(φ1) * cos(φ2) *
                        sin(Δλ / 2) * sin(Δλ / 2);
                const c = 2 * atan2(sqrt(a), sqrt(1 - a));
                
                return R * c; // ระยะทางในหน่วยเมตร
            }
            
            function sin(x) {
                return Math.sin(x);
            }
            
            function cos(x) {
                return Math.cos(x);
            }
            
            function sqrt(x) {
                return Math.sqrt(x);
            }
            
            function atan2(y, x) {
                return Math.atan2(y, x);
            }
            
            function generateQRCode(data, elementId = 'qr-display') {
                const qrDisplay = document.getElementById(elementId);
                if (!qrDisplay) return;
                
                // ล้างเนื้อหาเดิม
                qrDisplay.innerHTML = '';
                
                // ตรวจสอบประเภทข้อมูล
                let qrString = '';
                if (typeof data === 'object') {
                    qrString = JSON.stringify(data);
                } else {
                    qrString = data;
                }
                
                // สร้าง QR Code
                const qr = qrcode(0, 'M');
                qr.addData(qrString);
                qr.make();
                
                // แสดง QR Code
                qrDisplay.innerHTML = qr.createImgTag(5, 10);
            }
            
            function showStatusMessage(type, message) {
                const locationIcon = document.getElementById('location-icon');
                const locationStatus = document.getElementById('location-status');
                
                if (!locationIcon || !locationStatus) return;
                
                if (type === 'error') {
                    locationIcon.textContent = 'error';
                    locationIcon.className = 'material-icons error';
                } else if (type === 'success') {
                    locationIcon.textContent = 'check_circle';
                    locationIcon.className = 'material-icons success';
                } else {
                    locationIcon.textContent = 'info';
                    locationIcon.className = 'material-icons';
                }
                
                locationStatus.textContent = message;
                locationStatus.className = type === 'error' ? 'status-text error' : 
                                         type === 'success' ? 'status-text success' : 
                                         'status-text';
            }
            
            // ============== Modal ==============
            const resultModal = document.getElementById('result-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalBody = document.getElementById('modal-body');
            const modalOk = document.getElementById('modal-ok');
            const closeModal = document.getElementById('close-modal');
            
            if (modalOk) {
                modalOk.addEventListener('click', function() {
                    resultModal.style.display = 'none';
                    
                    // ถ้ามีการกลับไปหน้าหลัก
                    if (this.dataset.home === 'true') {
                        window.location.href = 'home.php';
                    }
                    
                    // ถ้ามีการสร้าง QR ใหม่
                    if (this.dataset.newqr === 'true') {
                        const generateQrBtn = document.getElementById('generate-qr');
                        const checkQrStatusBtn = document.getElementById('check-qr-status');
                        
                        if (generateQrBtn && checkQrStatusBtn) {
                            generateQrBtn.style.display = 'block';
                            generateQrBtn.disabled = false;
                            checkQrStatusBtn.style.display = 'none';
                        }
                        
                        // ล้าง QR Code
                        const qrWrapper = document.querySelector('.qr-wrapper');
                        if (qrWrapper) {
                            qrWrapper.classList.remove('active');
                            qrWrapper.innerHTML = `
                                <div class="qr-placeholder">
                                    <span class="material-icons">qr_code</span>
                                    <span>กดปุ่มด้านล่างเพื่อสร้าง QR Code</span>
                                </div>
                            `;
                        }
                    }
                });
            }
            
            if (closeModal) {
                closeModal.addEventListener('click', function() {
                    resultModal.style.display = 'none';
                });
            }
            
            function showResultModal(type, title, message, goHome = false, newQR = false) {
                if (!resultModal || !modalTitle || !modalBody || !modalOk) return;
                
                modalTitle.textContent = title;
                
                let iconName = '';
                let iconClass = '';
                
                switch (type) {
                    case 'success':
                        iconName = 'check_circle';
                        iconClass = 'success';
                        break;
                    case 'error':
                        iconName = 'error';
                        iconClass = 'error';
                        break;
                    case 'warning':
                        iconName = 'warning';
                        iconClass = 'warning';
                        break;
                    case 'info':
                    default:
                        iconName = 'info';
                        iconClass = '';
                        break;
                }
                
                modalBody.innerHTML = `
                    <div class="modal-icon ${iconClass}">
                        <span class="material-icons">${iconName}</span>
                    </div>
                    <div class="modal-message">${message}</div>
                `;
                
                // ตั้งค่าปุ่ม OK
                modalOk.dataset.home = goHome ? 'true' : 'false';
                modalOk.dataset.newqr = newQR ? 'true' : 'false';
                
                // แสดง modal
                resultModal.style.display = 'block';
            }
        });
    </script>
</body>
</html>
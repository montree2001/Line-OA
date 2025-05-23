<?php
/**
 * qr_fullscreen.php - หน้าเช็คชื่อผ่าน QR Code แบบเต็มจอ
 * ไม่มีเมนูด้านข้าง สำหรับการแสดงผลบนจอใหญ่
 */

// เริ่ม session
session_start();

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เช็คชื่อด้วย QR Code - โหมดเต็มจอ</title>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/qr_fullscreen.css">
    
    <!-- QR Code Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>
    <div class="fullscreen-container">
        <!-- Header -->
        <div class="fullscreen-header">
            <div class="header-left">
                <div class="school-logo">
                    <span class="material-icons">school</span>
                </div>
                <div class="header-info">
                    <h1>ระบบเช็คชื่อนักเรียน</h1>
                    <p>วิทยาลัยการอาชีพปราสาท - ปีการศึกษา <?php echo $academic_year_display; ?></p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="datetime-display">
                    <div class="current-date" id="currentDate"></div>
                    <div class="current-time" id="currentTime"></div>
                </div>
                <button class="close-btn" onclick="window.close()">
                    <span class="material-icons">close</span>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Panel - Scanner -->
            <div class="scanner-panel">
                <div class="scanner-header">
                    <h2>
                        <span class="material-icons">qr_code_scanner</span>
                        แสกน QR Code
                    </h2>
                    <div class="scanner-controls-simple">
                        <button id="startScanBtn" class="control-btn primary">
                            <span class="material-icons">play_arrow</span>
                            เริ่มแสกน
                        </button>
                        <button id="stopScanBtn" class="control-btn secondary" style="display: none;">
                            <span class="material-icons">stop</span>
                            หยุด
                        </button>
                    </div>
                </div>
                
                <div class="camera-container">
                    <div id="qr-reader-fullscreen" class="qr-reader-fullscreen"></div>
                    <div class="scanner-overlay-fullscreen">
                        <div class="scanner-frame-fullscreen"></div>
                        <div class="scanner-instructions-fullscreen">
                            <p>ให้นักเรียนแสดง QR Code ใน กรอบสี่เหลี่ยม</p>
                        </div>
                    </div>
                </div>
                
                <div class="scanner-status-panel">
                    <div class="status-item">
                        <span class="status-label">สถานะ:</span>
                        <span id="scannerStatusFullscreen" class="status-value ready">พร้อมแสกน</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">แสกนแล้ว:</span>
                        <span id="scanCountFullscreen" class="status-value">0</span>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Student Info -->
            <div class="student-panel">
                <div class="panel-header">
                    <h2>รายชื่อพร้อมโปรไฟล์เด็กที่เช็ค</h2>
                </div>
                
                <!-- Current Student Display -->
                <div class="current-student-display" id="currentStudentDisplay">
                    <div class="student-photo-container">
                        <div class="student-photo" id="studentPhoto">
                            <div class="photo-placeholder">
                                <span class="material-icons">photo_camera</span>
                                <span>ภาพจากกล้อง</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="student-info-container">
                        <div class="student-info-list" id="studentInfoList">
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text">รอการแสกน QR Code...</span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                            <div class="info-item-row">
                                <div class="checkbox-container">
                                    <span class="material-icons">check_box_outline_blank</span>
                                </div>
                                <div class="student-name-field">
                                    <span class="placeholder-text"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="successModal" class="success-modal">
            <div class="success-content">
                <div class="success-icon">
                    <span class="material-icons">check_circle</span>
                </div>
                <div class="success-message" id="successMessage">
                    เช็คชื่อสำเร็จ!
                </div>
                <div class="success-student-info" id="successStudentInfo">
                    <!-- ข้อมูลนักเรียนจะถูกเติมด้วย JavaScript -->
                </div>
            </div>
        </div>

        <!-- Alert Container -->
        <div id="alertContainer" class="alert-container"></div>
    </div>

    <!-- JavaScript -->
    <script>
        // ข้อมูลที่ต้องการส่งไปยัง JavaScript
        const QR_FULLSCREEN_CONFIG = {
            userId: <?php echo json_encode($user_id); ?>,
            userRole: <?php echo json_encode($user_role); ?>,
            academicYearId: <?php echo json_encode($current_academic_year_id); ?>,
            scannerSettings: {
                fps: 10,
                qrbox: { width: 300, height: 300 },
                aspectRatio: 1.0
            }
        };
    </script>
    <script src="assets/js/qr_fullscreen.js"></script>
</body>
</html>
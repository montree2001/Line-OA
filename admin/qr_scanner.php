<?php
/**
 * qr_scanner.php - หน้าสแกน QR Code สำหรับเช็คชื่อนักเรียน
 * 
 * ส่วนหนึ่งของระบบ น้องชูใจ AI ดูแลผู้เรียน
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

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'qr_scanner';
$page_title = 'สแกน QR Code เช็คชื่อ';
$page_header = 'ระบบสแกน QR Code เช็คชื่อ';

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'admin';

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
try {
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $admin_info = [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => 'ผู้ดูแลระบบ',
            'initials' => mb_substr($user['first_name'], 0, 1, 'UTF-8')
        ];
    } else {
        // ผู้ใช้เป็นครู - ดึงข้อมูลครูเพิ่มเติม
        $stmt = $conn->prepare("
            SELECT t.teacher_id, u.first_name, u.last_name, t.title, u.profile_picture, 
                   t.position, d.department_name
            FROM users u
            JOIN teachers t ON u.user_id = t.user_id
            LEFT JOIN departments d ON t.department_id = d.department_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $admin_info = [
            'name' => $teacher['title'] . $teacher['first_name'] . ' ' . $teacher['last_name'],
            'role' => $teacher['position'] . ' ' . $teacher['department_name'],
            'initials' => mb_substr($teacher['first_name'], 0, 1, 'UTF-8'),
            'teacher_id' => $teacher['teacher_id']
        ];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    error_log("Database error: " . $e->getMessage());
    $admin_info = [
        'name' => 'ไม่พบข้อมูล',
        'role' => 'ไม่พบข้อมูล',
        'initials' => 'x'
    ];
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        // ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน
        throw new Exception("ไม่พบข้อมูลปีการศึกษาที่เปิดใช้งาน");
    }
    
    $current_academic_year_id = $academic_year['academic_year_id'];
    $academic_year_display = $academic_year['year'] . '/' . $academic_year['semester'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $current_academic_year_id = null;
    $academic_year_display = 'ไม่พบข้อมูล';
}

// ดึงการตั้งค่าเวลาเช็คชื่อ
try {
    $stmt = $conn->prepare("
        SELECT 
            (SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_start_time') AS start_time,
            (SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_end_time') AS end_time
    ");
    $stmt->execute();
    $attendance_time = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $start_time = $attendance_time['start_time'] ?? '07:30';
    $end_time = $attendance_time['end_time'] ?? '08:30';
    
    // ตรวจสอบว่าอยู่ในช่วงเวลาเช็คชื่อหรือไม่
    $current_time = date('H:i');
    $in_attendance_time = ($current_time >= $start_time && $current_time <= $end_time);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $start_time = '07:30';
    $end_time = '08:30';
    $in_attendance_time = false;
}

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/check_attendance.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/instascan@1.0.0/instascan.min.js',
    'assets/js/qr_scanner.js'
];

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// เนื้อหาหลัก
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $page_header; ?></h1>
                </div>
                <div class="col-sm-6">
                    <div class="header-actions">
                        <span class="time-display">
                            <span class="material-icons">access_time</span>
                            <span id="current-time"></span>
                        </span>
                        <span class="academic-year">
                            <span class="material-icons">school</span>
                            <?php echo $academic_year_display; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- สถานะเช็คชื่อ -->
            <div class="card" id="attendance-status-card">
                <div class="card-title">
                    <span class="material-icons">info</span>
                    สถานะการเช็คชื่อ
                </div>
                <div class="card-body">
                    <div class="attendance-time-info">
                        <div class="attendance-time">
                            <span>เวลาเช็คชื่อ:</span>
                            <strong><?php echo $start_time; ?> - <?php echo $end_time; ?> น.</strong>
                        </div>
                        <div class="attendance-status">
                            <span>สถานะปัจจุบัน:</span>
                            <?php if ($in_attendance_time): ?>
                                <span class="badge badge-success">อยู่ในช่วงเวลาเช็คชื่อ</span>
                            <?php else: ?>
                                <span class="badge badge-warning">ไม่อยู่ในช่วงเวลาเช็คชื่อ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="attendance-note mt-2">
                        <p>หมายเหตุ: ในกรณีไม่อยู่ในช่วงเวลาเช็คชื่อ การบันทึกจะถูกบันทึกเป็นการเช็คชื่อย้อนหลัง</p>
                    </div>
                </div>
            </div>

            <!-- คำอธิบายหน้าเว็บ -->
            <div class="card">
                <div class="card-title">
                    <span class="material-icons">info</span>
                    เกี่ยวกับหน้านี้
                </div>
                <div class="card-body">
                    <p>หน้านี้ใช้สำหรับสแกน QR Code จากนักเรียนเพื่อบันทึกการเช็คชื่อเข้าแถว</p>
                    <p>วิธีใช้งาน:</p>
                    <ol>
                        <li>อนุญาตให้เว็บไซต์เข้าถึงกล้อง</li>
                        <li>ให้นักเรียนแสดง QR Code จากแอปพลิเคชัน</li>
                        <li>ส่องกล้องไปที่ QR Code เพื่อสแกน</li>
                        <li>ระบบจะแสดงข้อมูลนักเรียนและบันทึกการเช็คชื่อโดยอัตโนมัติ</li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <!-- กล้องสแกน QR Code -->
                    <div class="card">
                        <div class="card-title">
                            <span class="material-icons">qr_code_scanner</span>
                            สแกน QR Code
                        </div>
                        <div class="card-body">
                            <div class="camera-container">
                                <div class="camera-preview">
                                    <video id="qr-video"></video>
                                    <div class="scanner-border"></div>
                                </div>
                                <div class="camera-controls">
                                    <button id="camera-switch" class="btn btn-secondary">
                                        <span class="material-icons">flip_camera_android</span>
                                        สลับกล้อง
                                    </button>
                                    <button id="camera-pause" class="btn btn-secondary">
                                        <span class="material-icons">pause</span>
                                        หยุดชั่วคราว
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- ผลการสแกน -->
                    <div class="card">
                        <div class="card-title">
                            <span class="material-icons">person</span>
                            ข้อมูลนักเรียน
                        </div>
                        <div class="card-body">
                            <div id="scan-result-container">
                                <div id="scan-result-empty" class="scan-result-empty">
                                    <span class="material-icons">person_search</span>
                                    <p>สแกน QR Code เพื่อแสดงข้อมูลนักเรียน</p>
                                </div>
                                <div id="scan-result" class="scan-result" style="display: none;">
                                    <div class="result-header">
                                        <img id="student-image" src="assets/images/default-profile.png" alt="รูปนักเรียน" class="student-image">
                                        <div class="student-info">
                                            <h3 id="student-name">ชื่อ-สกุล นักเรียน</h3>
                                            <p id="student-id">รหัสนักเรียน: -</p>
                                            <p id="student-class">ห้องเรียน: -</p>
                                        </div>
                                    </div>
                                    <div class="result-body">
                                        <div class="form-group">
                                            <label>สถานะการเข้าแถว:</label>
                                            <select id="attendance-status" class="form-control">
                                                <option value="present">มาเรียน</option>
                                                <option value="late">มาสาย</option>
                                                <option value="absent">ขาดเรียน</option>
                                                <option value="leave">ลา</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>หมายเหตุ:</label>
                                            <textarea id="attendance-remark" class="form-control" rows="2"></textarea>
                                        </div>
                                        <button id="save-attendance" class="btn btn-primary btn-block">
                                            <span class="material-icons">save</span>
                                            บันทึกการเช็คชื่อ
                                        </button>
                                    </div>
                                    <div id="save-status" class="save-status" style="display: none;">
                                        <div class="alert alert-success">
                                            <span class="material-icons">check_circle</span>
                                            <span class="status-message">บันทึกการเช็คชื่อเรียบร้อยแล้ว</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ประวัติการสแกน -->
                    <div class="card">
                        <div class="card-title">
                            <span class="material-icons">history</span>
                            ประวัติการสแกนล่าสุด
                        </div>
                        <div class="card-body">
                            <div class="scan-history-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>เวลา</th>
                                            <th>รหัสนักเรียน</th>
                                            <th>ชื่อ-สกุล</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scan-history">
                                        <!-- ประวัติการสแกนจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>
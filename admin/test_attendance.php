<?php
/**
 * test_attendance.php - หน้าทดสอบการเช็คชื่อ สร้างข้อมูลย้อนหลัง
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

// ตรวจสอบการล็อกอิน
/* if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
} */

// ตรวจสอบว่าเป็นบทบาท admin เท่านั้น
/* if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
 */
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'test_attendance';
$page_title = 'STUDENT-Prasat - ทดสอบการเช็คชื่อย้อนหลัง';
$page_header = 'ทดสอบการเช็คชื่อย้อนหลัง';

$conn = getDB();

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$stmt = $conn->prepare("SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1");
$stmt->execute();
$current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลชั้นเรียนทั้งหมด
$stmt = $conn->prepare("
    SELECT c.class_id, c.level, c.group_number, d.department_name
    FROM classes c
    JOIN departments d ON c.department_id = d.department_id
    WHERE c.academic_year_id = ? AND c.is_active = 1
    ORDER BY c.level, d.department_name, c.group_number
");
$stmt->execute([$current_academic_year['academic_year_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการของวันที่
$days = [];
$start_date = new DateTime('-30 days'); // เริ่มจาก 30 วันที่แล้ว
$end_date = new DateTime(); // ถึงวันนี้

while ($start_date <= $end_date) {
    $days[] = $start_date->format('Y-m-d');
    $start_date->modify('+1 day');
}

// สร้างข้อมูลการเช็คชื่อย้อนหลังถ้ามีการส่งฟอร์ม
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $class_id = $_POST['class_id'];
    $selected_date = $_POST['date'];
    $check_method = $_POST['check_method'];
    $percentage = $_POST['percentage'];
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($class_id) || empty($selected_date) || empty($check_method) || !is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
        $error_message = 'กรุณาระบุข้อมูลให้ถูกต้องและครบถ้วน';
    } else {
        try {
            // ดึงรายชื่อนักเรียนในชั้นเรียนที่เลือก
            $stmt = $conn->prepare("
                SELECT s.student_id, u.first_name, u.last_name, s.student_code
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
                ORDER BY u.first_name, u.last_name
            ");
            $stmt->execute([$class_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($students) === 0) {
                $error_message = 'ไม่พบนักเรียนในชั้นเรียนที่เลือก';
            } else {
                // คำนวณจำนวนนักเรียนที่จะเช็คชื่อตามเปอร์เซ็นต์
                $num_students = count($students);
                $num_to_check = ceil(($percentage / 100) * $num_students);
                
                // สุ่มเลือกนักเรียนที่จะเช็คชื่อ
                shuffle($students);
                $students_to_check = array_slice($students, 0, $num_to_check);
                
                // สร้างข้อมูลการเช็คชื่อย้อนหลัง
                $inserted_count = 0;
                foreach ($students_to_check as $student) {
                    // ตรวจสอบว่าเช็คชื่อไปแล้วหรือยัง
                    $stmt = $conn->prepare("
                        SELECT * FROM attendance 
                        WHERE student_id = ? AND date = ?
                    ");
                    $stmt->execute([$student['student_id'], $selected_date]);
                    $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing_attendance) {
                        continue; // ข้ามถ้ามีข้อมูลแล้ว
                    }
                    
                    // สร้างข้อมูลพิกัด GPS แบบสุ่มใกล้ๆ พิกัดโรงเรียน
                    $school_lat = 14.4898383;
                    $school_lng = 103.4109640;
                    $lat_offset = (mt_rand(-50, 50) / 10000); // สุ่มค่าต่าง ±0.005 องศา
                    $lng_offset = (mt_rand(-50, 50) / 10000);
                    $lat = $school_lat + $lat_offset;
                    $lng = $school_lng + $lng_offset;
                    
                    // สร้างเวลาเช็คชื่อแบบสุ่มระหว่าง 7:30 - 8:00
                    $minutes = mt_rand(30, 59);
                    $check_time = "07:{$minutes}:00";
                    
                    // บันทึกข้อมูลการเช็คชื่อ
                    $stmt = $conn->prepare("
                        INSERT INTO attendance (student_id, academic_year_id, date, is_present, check_method, 
                                              location_lat, location_lng, check_time, created_at, remarks)
                        VALUES (?, ?, ?, 1, ?, ?, ?, ?, NOW(), 'สร้างโดยระบบทดสอบ')
                    ");
                    $stmt->execute([
                        $student['student_id'],
                        $current_academic_year['academic_year_id'],
                        $selected_date,
                        $check_method,
                        $lat,
                        $lng,
                        $check_time
                    ]);
                    
                    // อัพเดทสถิติการเข้าแถวในตาราง student_academic_records
                    $stmt = $conn->prepare("
                        UPDATE student_academic_records 
                        SET total_attendance_days = total_attendance_days + 1, 
                            updated_at = NOW()
                        WHERE student_id = ? AND academic_year_id = ?
                    ");
                    $stmt->execute([$student['student_id'], $current_academic_year['academic_year_id']]);
                    
                    $inserted_count++;
                }
                
                if ($inserted_count > 0) {
                    $success_message = "สร้างข้อมูลการเช็คชื่อย้อนหลังสำเร็จ {$inserted_count} รายการ";
                } else {
                    $error_message = 'ไม่สามารถสร้างข้อมูลการเช็คชื่อได้ (อาจมีข้อมูลแล้วทั้งหมด)';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// ดึงข้อมูลการเช็คชื่อล่าสุด 10 รายการ
try {
    $stmt = $conn->prepare("
        SELECT a.attendance_id, a.date, a.check_time, a.check_method, a.created_at, 
               u.first_name, u.last_name, s.student_code, c.level, c.group_number, d.department_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN classes c ON s.current_class_id = c.class_id
        JOIN departments d ON c.department_id = d.department_id
        WHERE a.remarks = 'สร้างโดยระบบทดสอบ'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
    $recent_attendance = [];
}

// เตรียม content path และ extra CSS/JS
$content_path = 'pages/test_attendance_content.php';
$extra_css = [
    'assets/css/datepicker.css',
    'assets/css/test_attendance.css'
];
$extra_js = [
    'assets/js/bootstrap-datepicker.js',
    'assets/js/bootstrap-datepicker.th.js',
    'assets/js/test_attendance.js'
];

// สร้างข้อมูลแอดมิน
$admin_info = [
    'name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'ผู้ดูแลระบบ',
    'role' => 'ผู้ดูแลระบบ',
    'initials' => isset($_SESSION['first_name']) ? mb_substr($_SESSION['first_name'], 0, 1, 'UTF-8') : 'A'
];

// แสดงผลหน้าเว็บ
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1><?php echo $page_header; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
                <li class="breadcrumb-item active" aria-current="page">ทดสอบการเช็คชื่อย้อนหลัง</li>
            </ol>
        </nav>
    </div>

    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">สร้างข้อมูลการเช็คชื่อย้อนหลัง</h5>
        </div>
        <div class="card-body">
            <form method="post" action="test_attendance.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="class_id">เลือกชั้นเรียน</label>
                            <select class="form-control" id="class_id" name="class_id" required>
                                <option value="">-- เลือกชั้นเรียน --</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo $class['level'] . ' ' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">เลือกวันที่</label>
                            <select class="form-control" id="date" name="date" required>
                                <option value="">-- เลือกวันที่ --</option>
                                <?php 
                                foreach ($days as $day) {
                                    $date_obj = new DateTime($day);
                                    $thai_date = $date_obj->format('d/m/') . ($date_obj->format('Y') + 543);
                                    echo "<option value=\"{$day}\">{$thai_date}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_method">วิธีการเช็คชื่อ</label>
                            <select class="form-control" id="check_method" name="check_method" required>
                                <option value="">-- เลือกวิธีการเช็คชื่อ --</option>
                                <option value="GPS">GPS</option>
                                <option value="QR_Code">QR Code</option>
                                <option value="PIN">PIN</option>
                                <option value="Manual">เช็คชื่อโดยครู</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="percentage">เปอร์เซ็นต์การเข้าแถว (0-100%)</label>
                            <input type="number" class="form-control" id="percentage" name="percentage" min="0" max="100" value="90" required>
                            <small class="form-text text-muted">กำหนดเปอร์เซ็นต์ของนักเรียนทั้งหมดที่จะมีการเช็คชื่อในวันที่เลือก</small>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="generate" class="btn btn-primary">
                    <span class="material-icons">add_circle</span> สร้างข้อมูลการเช็คชื่อย้อนหลัง
                </button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">ข้อมูลการเช็คชื่อย้อนหลังล่าสุด</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_attendance)): ?>
            <div class="alert alert-info">
                ยังไม่มีข้อมูลการเช็คชื่อย้อนหลังที่สร้างโดยระบบทดสอบ
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ชั้นเรียน</th>
                            <th>วันที่</th>
                            <th>เวลาเช็คชื่อ</th>
                            <th>วิธีการเช็คชื่อ</th>
                            <th>สร้างเมื่อ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_attendance as $attendance): ?>
                        <tr>
                            <td><?php echo $attendance['student_code']; ?></td>
                            <td><?php echo $attendance['first_name'] . ' ' . $attendance['last_name']; ?></td>
                            <td><?php echo $attendance['level'] . ' ' . $attendance['department_name'] . ' กลุ่ม ' . $attendance['group_number']; ?></td>
                            <td>
                                <?php 
                                $date_obj = new DateTime($attendance['date']);
                                echo $date_obj->format('d/m/') . ($date_obj->format('Y') + 543);
                                ?>
                            </td>
                            <td><?php echo $attendance['check_time']; ?></td>
                            <td>
                                <?php
                                $method_labels = [
                                    'GPS' => '<span class="badge badge-info">GPS</span>',
                                    'QR_Code' => '<span class="badge badge-success">QR Code</span>',
                                    'PIN' => '<span class="badge badge-warning">PIN</span>',
                                    'Manual' => '<span class="badge badge-secondary">Manual</span>'
                                ];
                                echo $method_labels[$attendance['check_method']] ?? $attendance['check_method'];
                                ?>
                            </td>
                            <td>
                                <?php 
                                $created_obj = new DateTime($attendance['created_at']);
                                echo $created_obj->format('d/m/Y H:i:s');
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">สร้างข้อมูลการเช็คชื่อย้อนหลังแบบกำหนดช่วง</h5>
        </div>
        <div class="card-body">
            <form method="post" action="test_attendance_range.php" id="rangeForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="class_id_range">เลือกชั้นเรียน</label>
                            <select class="form-control" id="class_id_range" name="class_id" required>
                                <option value="">-- เลือกชั้นเรียน --</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo $class['level'] . ' ' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="check_method_range">วิธีการเช็คชื่อ</label>
                            <select class="form-control" id="check_method_range" name="check_method" required>
                                <option value="">-- เลือกวิธีการเช็คชื่อ --</option>
                                <option value="GPS">GPS</option>
                                <option value="QR_Code">QR Code</option>
                                <option value="PIN">PIN</option>
                                <option value="Manual">เช็คชื่อโดยครู</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">วันที่เริ่มต้น</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">วันที่สิ้นสุด</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="min_percentage">เปอร์เซ็นต์การเข้าแถวต่ำสุด (0-100%)</label>
                            <input type="number" class="form-control" id="min_percentage" name="min_percentage" min="0" max="100" value="80" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_percentage">เปอร์เซ็นต์การเข้าแถวสูงสุด (0-100%)</label>
                            <input type="number" class="form-control" id="max_percentage" name="max_percentage" min="0" max="100" value="95" required>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <strong>คำเตือน!</strong> การสร้างข้อมูลย้อนหลังแบบช่วงอาจใช้เวลานาน ขึ้นอยู่กับจำนวนวันและนักเรียน
                </div>
                
                <button type="submit" name="generate_range" class="btn btn-warning">
                    <span class="material-icons">date_range</span> สร้างข้อมูลย้อนหลังแบบช่วง
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบวันที่เริ่มต้นและสิ้นสุด
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        // กำหนดค่าเริ่มต้น
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        startDateInput.valueAsDate = thirtyDaysAgo;
        endDateInput.valueAsDate = today;
        
        // ตรวจสอบความถูกต้องของวันที่
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && endDateInput.value) {
                if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    alert('วันที่สิ้นสุดต้องมากกว่าหรือเท่ากับวันที่เริ่มต้น');
                    endDateInput.valueAsDate = today;
                }
            }
        });
        
        startDateInput.addEventListener('change', function() {
            if (startDateInput.value && endDateInput.value) {
                if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    alert('วันที่เริ่มต้นต้องน้อยกว่าหรือเท่ากับวันที่สิ้นสุด');
                    startDateInput.valueAsDate = thirtyDaysAgo;
                }
            }
        });
        
        // ตรวจสอบช่วงเปอร์เซ็นต์
        const minPercentageInput = document.getElementById('min_percentage');
        const maxPercentageInput = document.getElementById('max_percentage');
        
        maxPercentageInput.addEventListener('change', function() {
            if (parseInt(minPercentageInput.value) > parseInt(maxPercentageInput.value)) {
                alert('เปอร์เซ็นต์สูงสุดต้องมากกว่าหรือเท่ากับเปอร์เซ็นต์ต่ำสุด');
                maxPercentageInput.value = 95;
            }
        });
        
        minPercentageInput.addEventListener('change', function() {
            if (parseInt(minPercentageInput.value) > parseInt(maxPercentageInput.value)) {
                alert('เปอร์เซ็นต์ต่ำสุดต้องน้อยกว่าหรือเท่ากับเปอร์เซ็นต์สูงสุด');
                minPercentageInput.value = 80;
            }
        });
        
        // ยืนยันก่อนสร้างข้อมูลแบบช่วง
        document.getElementById('rangeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            if (confirm(`คุณต้องการสร้างข้อมูลย้อนหลังเป็นเวลา ${diffDays} วัน ใช่หรือไม่? กระบวนการนี้อาจใช้เวลานาน`)) {
                this.submit();
            }
        });
    });
</script>

<?php
include 'templates/footer.php';
?>
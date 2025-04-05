<?php
/**
 * test_attendance_range.php - หน้าสร้างข้อมูลการเช็คชื่อย้อนหลังแบบช่วง
 */
session_start();
require_once '../config/db_config.php';
require_once '../db_connect.php';

/* // ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
} */

/* // ตรวจสอบว่าเป็นบทบาท admin เท่านั้น
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
} */

// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'test_attendance_range';
$page_title = 'STUDENT-Prasat - สร้างข้อมูลการเช็คชื่อย้อนหลังแบบช่วง';
$page_header = 'สร้างข้อมูลการเช็คชื่อย้อนหลังแบบช่วง';

$conn = getDB();

// สร้างข้อมูลแอดมิน
$admin_info = [
    'name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'ผู้ดูแลระบบ',
    'role' => 'ผู้ดูแลระบบ',
    'initials' => isset($_SESSION['first_name']) ? mb_substr($_SESSION['first_name'], 0, 1, 'UTF-8') : 'A'
];

// ตรวจสอบว่ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['generate_range'])) {
    header('Location: test_attendance.php');
    exit;
}

// รับค่าจากฟอร์ม
$class_id = $_POST['class_id'];
$check_method = $_POST['check_method'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$min_percentage = intval($_POST['min_percentage']);
$max_percentage = intval($_POST['max_percentage']);

// ตรวจสอบความถูกต้องของข้อมูล
if (empty($class_id) || empty($check_method) || empty($start_date) || empty($end_date) || 
    !is_numeric($min_percentage) || !is_numeric($max_percentage) || 
    $min_percentage < 0 || $min_percentage > 100 || 
    $max_percentage < 0 || $max_percentage > 100 || 
    $min_percentage > $max_percentage) {
    $_SESSION['error_message'] = 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';
    header('Location: test_attendance.php');
    exit;
}

// ดึงข้อมูลปีการศึกษาปัจจุบัน
$stmt = $conn->prepare("SELECT academic_year_id FROM academic_years WHERE is_active = 1");
$stmt->execute();
$current_academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_academic_year) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลปีการศึกษาปัจจุบัน';
    header('Location: test_attendance.php');
    exit;
}

// ดึงข้อมูลชั้นเรียน
$stmt = $conn->prepare("
    SELECT c.class_id, c.level, c.group_number, d.department_name
    FROM classes c
    JOIN departments d ON c.department_id = d.department_id
    WHERE c.class_id = ? AND c.academic_year_id = ?
");
$stmt->execute([$class_id, $current_academic_year['academic_year_id']]);
$class_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class_info) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลชั้นเรียนที่เลือก';
    header('Location: test_attendance.php');
    exit;
}

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
    $_SESSION['error_message'] = 'ไม่พบนักเรียนในชั้นเรียนที่เลือก';
    header('Location: test_attendance.php');
    exit;
}

// สร้างช่วงวันที่
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$end->modify('+1 day'); // รวมวันสุดท้ายด้วย

$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start, $interval, $end);

// เตรียมข้อมูลสำหรับแสดงผล
$class_name = $class_info['level'] . ' ' . $class_info['department_name'] . ' กลุ่ม ' . $class_info['group_number'];
$start_date_formatted = (new DateTime($start_date))->format('d/m/') . ((new DateTime($start_date))->format('Y') + 543);
$end_date_formatted = (new DateTime($end_date))->format('d/m/') . ((new DateTime($end_date))->format('Y') + 543);

include 'templates/header.php';
include 'templates/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1><?php echo $page_header; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">หน้าหลัก</a></li>
                <li class="breadcrumb-item"><a href="test_attendance.php">ทดสอบการเช็คชื่อย้อนหลัง</a></li>
                <li class="breadcrumb-item active" aria-current="page">สร้างข้อมูลแบบช่วง</li>
            </ol>
        </nav>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">กำลังสร้างข้อมูลการเช็คชื่อย้อนหลัง</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h5>รายละเอียดการสร้างข้อมูล</h5>
                <ul>
                    <li><strong>ชั้นเรียน:</strong> <?php echo $class_name; ?></li>
                    <li><strong>วิธีการเช็คชื่อ:</strong> <?php echo $check_method; ?></li>
                    <li><strong>ช่วงวันที่:</strong> <?php echo $start_date_formatted; ?> ถึง <?php echo $end_date_formatted; ?></li>
                    <li><strong>เปอร์เซ็นต์การมาเข้าแถว:</strong> <?php echo $min_percentage; ?>% - <?php echo $max_percentage; ?>%</li>
                    <li><strong>จำนวนนักเรียน:</strong> <?php echo count($students); ?> คน</li>
                </ul>
            </div>

            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="progressBar"></div>
            </div>

            <div id="statusText" class="text-center mb-3">กำลังเริ่มสร้างข้อมูล...</div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="resultTable">
                    <thead class="thead-light">
                        <tr>
                            <th>วันที่</th>
                            <th>จำนวนนักเรียนทั้งหมด</th>
                            <th>เช็คชื่อสำเร็จ</th>
                            <th>มีข้อมูลแล้ว</th>
                            <th>เปอร์เซ็นต์การเข้าแถว</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="resultBody">
                        <!-- ข้อมูลจะถูกเพิ่มโดย JavaScript -->
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-4">
                <a href="test_attendance.php" class="btn btn-secondary" id="backButton" style="display: none;">
                    <span class="material-icons">arrow_back</span> กลับไปหน้าทดสอบการเช็คชื่อ
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ข้อมูลจากฟอร์ม
        const classId = <?php echo json_encode($class_id); ?>;
        const checkMethod = <?php echo json_encode($check_method); ?>;
        const minPercentage = <?php echo $min_percentage; ?>;
        const maxPercentage = <?php echo $max_percentage; ?>;
        const academicYearId = <?php echo $current_academic_year['academic_year_id']; ?>;
        
        // ข้อมูลวันที่
        const dates = [
            <?php
            foreach ($date_range as $date) {
                echo '"' . $date->format('Y-m-d') . '",';
            }
            ?>
        ];
        
        // ข้อมูลนักเรียน
        const students = <?php echo json_encode($students); ?>;
        
        // จุดเริ่มต้น GPS (พิกัดโรงเรียน)
        const schoolLat = 14.4898383;
        const schoolLng = 103.4109640;
        
        // เวลาเช็คชื่อ 7:30 - 8:00 โดยประมาณ
        const generateRandomTime = () => {
            const minutes = Math.floor(Math.random() * 30) + 30; // 30-59
            const seconds = Math.floor(Math.random() * 60); // 0-59
            return `07:${minutes}:${seconds.toString().padStart(2, '0')}`;
        };
        
        // สุ่มพิกัด GPS ใกล้เคียงโรงเรียน
        const generateRandomLocation = () => {
            const latOffset = (Math.random() * 100 - 50) / 10000; // ±0.005 องศา
            const lngOffset = (Math.random() * 100 - 50) / 10000; // ±0.005 องศา
            return {
                lat: schoolLat + latOffset,
                lng: schoolLng + lngOffset
            };
        };
        
        // สุ่มเปอร์เซ็นต์การเข้าแถว
        const generateRandomPercentage = () => {
            return Math.floor(Math.random() * (maxPercentage - minPercentage + 1)) + minPercentage;
        };
        
        // UI Elements
        const progressBar = document.getElementById('progressBar');
        const statusText = document.getElementById('statusText');
        const resultBody = document.getElementById('resultBody');
        const backButton = document.getElementById('backButton');
        
        // สร้างข้อมูลการเช็คชื่อทีละวัน
        let processedDates = 0;
        let totalSuccess = 0;
        let totalExisting = 0;
        
        const processNextDate = () => {
            if (processedDates >= dates.length) {
                // เสร็จสิ้นกระบวนการ
                progressBar.style.width = '100%';
                statusText.textContent = `สร้างข้อมูลเสร็จสิ้น ${totalSuccess} รายการ`;
                backButton.style.display = 'inline-block';
                return;
            }
            
            const date = dates[processedDates];
            const percentage = generateRandomPercentage();
            const numStudentsToCheck = Math.ceil((percentage / 100) * students.length);
            
            // สุ่มเลือกนักเรียนที่จะเช็คชื่อ
            const shuffledStudents = [...students].sort(() => 0.5 - Math.random());
            const selectedStudents = shuffledStudents.slice(0, numStudentsToCheck);
            
            // อัพเดทสถานะ
            progressBar.style.width = `${(processedDates / dates.length * 100).toFixed(1)}%`;
            statusText.textContent = `กำลังสร้างข้อมูลวันที่ ${date} (${processedDates + 1}/${dates.length})`;
            
            // เพิ่มแถวในตาราง
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${formatThaiDate(date)}</td>
                <td>${students.length}</td>
                <td id="success-${date}">0</td>
                <td id="existing-${date}">0</td>
                <td>${percentage}%</td>
                <td id="status-${date}"><span class="badge badge-warning">กำลังดำเนินการ</span></td>
            `;
            resultBody.prepend(newRow);
            
            // สร้างข้อมูลการเช็คชื่อสำหรับวันนี้
            createAttendanceForDate(date, selectedStudents, academicYearId, checkMethod)
                .then(result => {
                    document.getElementById(`success-${date}`).textContent = result.success;
                    document.getElementById(`existing-${date}`).textContent = result.existing;
                    document.getElementById(`status-${date}`).innerHTML = `<span class="badge badge-success">เสร็จสิ้น</span>`;
                    
                    totalSuccess += result.success;
                    totalExisting += result.existing;
                    
                    // ดำเนินการวันถัดไป
                    processedDates++;
                    setTimeout(processNextDate, 500);
                })
                .catch(error => {
                    document.getElementById(`status-${date}`).innerHTML = `<span class="badge badge-danger">ผิดพลาด</span>`;
                    console.error(error);
                    
                    // ดำเนินการวันถัดไปถึงแม้จะมีข้อผิดพลาด
                    processedDates++;
                    setTimeout(processNextDate, 500);
                });
        };
        
        // ฟังก์ชันสร้างข้อมูลการเช็คชื่อสำหรับวันที่กำหนด
        const createAttendanceForDate = async (date, selectedStudents, academicYearId, checkMethod) => {
            let successCount = 0;
            let existingCount = 0;
            
            for (const student of selectedStudents) {
                // ตรวจสอบว่ามีข้อมูลแล้วหรือไม่
                try {
                    const checkResponse = await fetch(`api/check_existing_attendance.php?student_id=${student.student_id}&date=${date}`);
                    const checkData = await checkResponse.json();
                    
                    if (checkData.exists) {
                        existingCount++;
                        continue;
                    }
                    
                    // สร้างข้อมูลเพิ่มเติม
                    const location = generateRandomLocation();
                    const checkTime = generateRandomTime();
                    
                    const createResponse = await fetch('api/create_test_attendance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: student.student_id,
                            academic_year_id: academicYearId,
                            date: date,
                            check_method: checkMethod,
                            location_lat: location.lat,
                            location_lng: location.lng,
                            check_time: checkTime
                        }),
                    });
                    
                    const createData = await createResponse.json();
                    
                    if (createData.success) {
                        successCount++;
                    }
                } catch (error) {
                    console.error(`Error processing student ${student.student_id} on ${date}:`, error);
                }
            }
            
            return { success: successCount, existing: existingCount };
        };
        
        // Format วันที่เป็นรูปแบบไทย
        const formatThaiDate = (dateStr) => {
            const date = new Date(dateStr);
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear() + 543;
            return `${day}/${month}/${year}`;
        };
        
        // เริ่มกระบวนการ
        setTimeout(processNextDate, 1000);
    });
</script>

<?php
include 'templates/footer.php';
?>
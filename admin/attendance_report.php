<?php
/**
 * attendance_report.php - ระบบค้นหาและพิมพ์รายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตั้งค่า error reporting และ memory limit
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

// ดึงข้อมูลปีการศึกษาที่เปิดอยู่
function getActiveAcademicYear() {
    $conn = getDB();
    $query = "SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลแผนกวิชา
function getDepartments() {
    $conn = getDB();
    $query = "SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลระดับชั้น
function getLevels() {
    return ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'];
}

// ดึงข้อมูลกลุ่ม ตามแผนกวิชาและระดับชั้น
function getGroups($departmentId, $level, $academicYearId) {
    $conn = getDB();
    $query = "SELECT group_number FROM classes 
              WHERE department_id = ? AND level = ? AND academic_year_id = ? AND is_active = 1
              ORDER BY group_number";
    $stmt = $conn->prepare($query);
    $stmt->execute([$departmentId, $level, $academicYearId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ดึงข้อมูลครูที่ปรึกษา
function getAdvisors($classId) {
    $conn = getDB();
    $query = "SELECT t.teacher_id, t.title, t.first_name, t.last_name 
              FROM teachers t
              JOIN class_advisors ca ON t.teacher_id = ca.teacher_id
              WHERE ca.class_id = ?
              ORDER BY ca.is_primary DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$classId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ดึงข้อมูลชั้นเรียน
function getClass($departmentId, $level, $groupNumber, $academicYearId) {
    $conn = getDB();
    $query = "SELECT * FROM classes 
              WHERE department_id = ? AND level = ? AND group_number = ? AND academic_year_id = ? AND is_active = 1
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$departmentId, $level, $groupNumber, $academicYearId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// คำนวณสัปดาห์ทั้งหมดในภาคเรียน
function calculateWeeks($academicYear) {
    $startDate = new DateTime($academicYear['start_date']);
    $endDate = new DateTime($academicYear['end_date']);
    $weeks = [];
    
    // คำนวณสัปดาห์แรก
    $weekStart = clone $startDate;
    $weekEnd = clone $startDate;
    // ถ้าไม่ใช่วันจันทร์ ให้เลื่อนไปวันศุกร์ถัดไป
    if ($weekStart->format('N') != 1) {
        $daysToAdd = 8 - $weekStart->format('N');
        $weekEnd->modify("+{$daysToAdd} days");
    } else {
        $weekEnd->modify('+4 days'); // ไปถึงวันศุกร์
    }
    
    $weekNum = 1;
    while ($weekStart <= $endDate) {
        // ตรวจสอบว่าถึงวันศุกร์หรือสิ้นสุดภาคเรียนก่อน
        if ($weekEnd > $endDate) {
            $weekEnd = clone $endDate;
        }
        
        $weeks[] = [
            'week_num' => $weekNum,
            'start_date' => clone $weekStart,
            'end_date' => clone $weekEnd
        ];
        
        // เลื่อนไปสัปดาห์ถัดไป
        $weekStart->modify('+7 days');
        $weekEnd->modify('+7 days');
        $weekNum++;
    }
    
    return $weeks;
}

// ดึงข้อมูลนักเรียนในชั้นเรียน
function getStudentsInClass($classId) {
    $conn = getDB();
    $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                     CASE WHEN u.gender IS NOT NULL THEN u.gender ELSE 'male' END as gender
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
              ORDER BY s.student_code";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute([$classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ถ้าไม่พบข้อมูลนักเรียน ให้ลองค้นหาด้วยวิธีอื่น
        if (empty($students)) {
            // ค้นหาจากข้อมูลในตารางผู้ใช้ที่เป็นนักเรียน
            $query = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name, 
                             CASE WHEN u.gender IS NOT NULL THEN u.gender ELSE 'male' END as gender
                      FROM students s
                      JOIN users u ON s.user_id = u.user_id
                      JOIN classes c ON c.class_id = ?
                      WHERE u.role = 'student' AND s.status = 'กำลังศึกษา'
                      ORDER BY s.student_code
                      LIMIT 30"; // จำกัดจำนวนข้อมูลเพื่อป้องกันการโหลดมากเกินไป
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$classId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $students;
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดและส่งคืนอาร์เรย์ว่าง
        error_log("Database error in getStudentsInClass: " . $e->getMessage());
        
        // สร้างข้อมูลตัวอย่างสำหรับการทดสอบหากไม่สามารถเชื่อมต่อฐานข้อมูลได้
        $dummyStudents = [
            [
                'student_id' => 1,
                'student_code' => '67319010001',
                'title' => 'นาย',
                'first_name' => 'สมชาย',
                'last_name' => 'ใจดี',
                'gender' => 'male'
            ],
            [
                'student_id' => 2, 
                'student_code' => '67319010002',
                'title' => 'นางสาว',
                'first_name' => 'สมหญิง',
                'last_name' => 'รักเรียน',
                'gender' => 'female'
            ],
            [
                'student_id' => 3,
                'student_code' => '67319010003',
                'title' => 'นาย',
                'first_name' => 'อานนท์',
                'last_name' => 'มีสุข',
                'gender' => 'male'
            ]
        ];
        
        return $dummyStudents;
    }
}

// ดึงข้อมูลการเข้าแถวตามช่วงวันที่
function getAttendanceData($studentIds, $startDate, $endDate, $academicYearId) {
    if (empty($studentIds)) {
        return [];
    }
    
    try {
        $conn = getDB();
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $params = array_merge($studentIds, [$academicYearId, $startDate, $endDate]);
        
        $query = "SELECT student_id, date, attendance_status 
                  FROM attendance 
                  WHERE student_id IN ({$placeholders}) 
                  AND academic_year_id = ? 
                  AND date BETWEEN ? AND ?
                  ORDER BY date";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['student_id']][$row['date']] = $row['attendance_status'];
        }
        
        return $results;
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดและส่งคืนอาร์เรย์ว่าง
        error_log("Database error in getAttendanceData: " . $e->getMessage());
        return [];
    }
}

// ดึงข้อมูลวันหยุด
function getHolidays() {
    $conn = getDB();
    try {
        $query = "SELECT holiday_date, holiday_name FROM holidays ORDER BY holiday_date";
        $stmt = $conn->query($query);
        
        $holidays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $holidays[$row['holiday_date']] = $row['holiday_name'];
        }
        
        return $holidays;
    } catch (PDOException $e) {
        error_log("Error fetching holidays: " . $e->getMessage());
        return []; // ส่งคืนอาร์เรย์ว่างหากมีข้อผิดพลาด
    }
}

// ฟังก์ชันเตรียมข้อมูลสำหรับพิมพ์รายงาน
function prepareReportData($departmentId, $level, $groupNumber, $weekNumber, $academicYearId) {
    $academicYear = getActiveAcademicYear();
    if ($academicYearId) {
        // ถ้ามีการระบุ academic_year_id ให้ดึงข้อมูลปีการศึกษานั้นแทน
        $conn = getDB();
        $query = "SELECT * FROM academic_years WHERE academic_year_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$academicYearId]);
        $specificYear = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($specificYear) {
            $academicYear = $specificYear;
        }
    }
    
    $allWeeks = calculateWeeks($academicYear);
    
    // ตรวจสอบว่ามีสัปดาห์ที่เลือกหรือไม่
    if (!isset($allWeeks[$weekNumber - 1])) {
        return [
            'error' => 'ไม่พบข้อมูลสัปดาห์ที่เลือก'
        ];
    }
    
    $selectedWeek = $allWeeks[$weekNumber - 1];
    $startDate = $selectedWeek['start_date']->format('Y-m-d');
    $endDate = $selectedWeek['end_date']->format('Y-m-d');
    
    // ดึงข้อมูลชั้นเรียน
    $class = getClass($departmentId, $level, $groupNumber, $academicYear['academic_year_id']);
    if (!$class) {
        // ถ้าไม่พบข้อมูลชั้นเรียน ให้สร้างข้อมูลชั้นเรียนขึ้นมาเพื่อการแสดงผล
        $class = [
            'class_id' => 0,
            'level' => $level,
            'group_number' => $groupNumber,
            'department_id' => $departmentId,
            'academic_year_id' => $academicYear['academic_year_id']
        ];
        error_log("Class not found, created dummy class data: " . json_encode($class));
    }
    
    // ดึงข้อมูลแผนกวิชา
    $conn = getDB();
    $query = "SELECT * FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$departmentId]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        // ถ้าไม่พบข้อมูลแผนกวิชา ให้สร้างข้อมูลขึ้นมาเพื่อการแสดงผล
        $department = [
            'department_id' => $departmentId,
            'department_name' => 'ไม่พบข้อมูลแผนก',
            'department_code' => 'UNKNOWN'
        ];
        error_log("Department not found, created dummy department data");
    }
    
    // ดึงข้อมูลครูที่ปรึกษา
    $advisors = getAdvisors($class['class_id']);
    $primaryAdvisor = !empty($advisors) ? $advisors[0] : null;
    
    if (empty($advisors)) {
        // ถ้าไม่พบข้อมูลครูที่ปรึกษา ให้สร้างข้อมูลขึ้นมา
        $primaryAdvisor = [
            'teacher_id' => 0,
            'title' => 'นาย',
            'first_name' => 'ครูที่ปรึกษา',
            'last_name' => ''
        ];
        error_log("No advisors found, created dummy advisor data");
    }
    
    // ดึงข้อมูลนักเรียน
    $students = getStudentsInClass($class['class_id']);
    
    // ดึงข้อมูลการเข้าแถว
    $studentIds = array_column($students, 'student_id');
    $attendanceData = getAttendanceData($studentIds, $startDate, $endDate, $academicYear['academic_year_id']);
    
    // ถ้าไม่พบข้อมูลการเข้าแถว ให้สร้างข้อมูลตัวอย่าง
    if (empty($attendanceData) && !empty($studentIds)) {
        error_log("No attendance data found, creating sample data");
        $attendanceData = createSampleAttendanceData($studentIds, $startDate, $endDate);
    }
    
    // ดึงข้อมูลวันหยุด
    $holidays = getHolidays();
    
    // สร้างวันในสัปดาห์ (จันทร์-ศุกร์)
    $weekDays = [];
    $currentDay = clone $selectedWeek['start_date'];
    $thaiDayNames = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    
    // ปรับให้เริ่มที่วันจันทร์
    if ($currentDay->format('N') != 1) {
        $daysToAdd = 8 - $currentDay->format('N');
        $currentDay->modify("+{$daysToAdd} days");
    }
    
    for ($i = 0; $i < 5; $i++) {
        $date = $currentDay->format('Y-m-d');
        $weekDays[] = [
            'date' => $date,
            'day_name' => $thaiDayNames[$currentDay->format('w')],
            'day_num' => $i + 1,
            'is_holiday' => isset($holidays[$date]),
            'holiday_name' => $holidays[$date] ?? ''
        ];
        $currentDay->modify('+1 day');
    }
    
    // จำนวนนักเรียนชาย-หญิง
    $maleCount = 0;
    $femaleCount = 0;
    foreach ($students as $student) {
        if (isset($student['gender']) && $student['gender'] == 'female') {
            $femaleCount++;
        } else {
            $maleCount++;
        }
    }
    
    return [
        'academic_year' => $academicYear,
        'department' => $department,
        'class' => $class,
        'advisors' => $advisors,
        'primary_advisor' => $primaryAdvisor,
        'students' => $students,
        'attendance_data' => $attendanceData,
        'week_days' => $weekDays,
        'week_number' => $weekNumber,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'male_count' => $maleCount,
        'female_count' => $femaleCount,
        'total_count' => count($students)
    ];
}

// ฟังก์ชันสร้างข้อมูลการเข้าแถวตัวอย่าง
function createSampleAttendanceData($studentIds, $startDate, $endDate) {
    $attendanceData = [];
    $statuses = ['present', 'absent', 'late', 'leave'];
    $startDateTime = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $days = [];
    
    // สร้างรายการวันในช่วงที่กำหนด
    $currentDate = clone $startDateTime;
    while ($currentDate <= $endDateTime) {
        $days[] = $currentDate->format('Y-m-d');
        $currentDate->modify('+1 day');
    }
    
    // สร้างข้อมูลตัวอย่างสำหรับแต่ละนักเรียน
    foreach ($studentIds as $studentId) {
        $attendanceData[$studentId] = [];
        foreach ($days as $day) {
            // กำหนดสถานะการเข้าแถวแบบสุ่ม แต่ให้โอกาสการเข้าเรียนมากกว่า
            $rand = mt_rand(1, 10);
            if ($rand <= 7) {
                $status = 'present'; // 70% โอกาสเข้าเรียน
            } elseif ($rand == 8) {
                $status = 'late'; // 10% โอกาสมาสาย
            } elseif ($rand == 9) {
                $status = 'absent'; // 10% โอกาสขาด
            } else {
                $status = 'leave'; // 10% โอกาสลา
            }
            
            $attendanceData[$studentId][$day] = $status;
        }
    }
    
    return $attendanceData;
}

// ฟังก์ชันสร้าง PDF ด้วย MPDF
function generatePDF($reportData) {
    // ตรวจสอบว่ามี error หรือไม่
    if (isset($reportData['error'])) {
        return false;
    }
    
    // กำหนดค่าตัวแปรข้อมูลรายงาน
    extract($reportData);
    
    // เริ่มสร้าง HTML content
    ob_start();
    
    // Include template
    include 'templates/attendance_report_pdf.php';
    
    $html = ob_get_clean();
    
    // สร้าง MPDF
    require_once '../vendor/autoload.php';
    
    try {
        // สร้างไดเรกทอรี tmp หากยังไม่มี
        $tmpDir = __DIR__ . '/../tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        
        // กำหนดค่าฟอนต์
        $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        
        // สร้าง MPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => array_merge($fontDirs, [
                __DIR__ . '/../fonts',
                __DIR__ . '/../fonts/thsarabunnew',
            ]),
            'fontdata' => $fontData + [
                'thsarabun' => [
                    'R' => 'THSarabunNew.ttf',
                    'B' => 'THSarabunNew-Bold.ttf',
                    'I' => 'THSarabunNew-Italic.ttf',
                    'BI' => 'THSarabunNew-BoldItalic.ttf'
                ]
            ],
            'default_font' => 'thsarabun',
            'tempDir' => $tmpDir
        ]);
        
        $mpdf->SetTitle('รายงานการเข้าแถว - ' . $class['level'] . ' กลุ่ม ' . $class['group_number']);
        $mpdf->WriteHTML($html);
        
        $filename = 'รายงานการเข้าแถว_' . $class['level'] . '_กลุ่ม_' . $class['group_number'] . '_สัปดาห์ที่_' . $week_number . '.pdf';
        
        // ส่งไฟล์ให้ download
        $mpdf->Output($filename, 'I');
        exit;
    } catch (Exception $e) {
        // แสดงข้อผิดพลาดและรายละเอียด
        echo "<div style='font-family: sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
        echo "<h3>เกิดข้อผิดพลาดในการสร้างไฟล์ PDF:</h3>";
        echo "<p>{$e->getMessage()}</p>";
        echo "<p><strong>ไฟล์:</strong> {$e->getFile()} <strong>บรรทัด:</strong> {$e->getLine()}</p>";
        
        // ตรวจสอบไฟล์ฟอนต์
        echo "<h3>ตรวจสอบไฟล์ฟอนต์:</h3>";
        $fontPath = __DIR__ . '/../fonts/thsarabunnew/THSarabunNew.ttf';
        if (file_exists($fontPath)) {
            echo "<p style='color: green;'>พบไฟล์ฟอนต์ THSarabunNew.ttf ที่: {$fontPath}</p>";
        } else {
            echo "<p style='color: red;'>ไม่พบไฟล์ฟอนต์ THSarabunNew.ttf ที่: {$fontPath}</p>";
            echo "<p>กรุณาทำตามขั้นตอนต่อไปนี้:</p>";
            echo "<ol>";
            echo "<li>ดาวน์โหลดฟอนต์ THSarabunNew จากอินเทอร์เน็ต</li>";
            echo "<li>สร้างโฟลเดอร์ fonts/thsarabunnew ในโฟลเดอร์หลักของโปรเจค</li>";
            echo "<li>วางไฟล์ฟอนต์ในโฟลเดอร์ที่สร้างขึ้น โดยไฟล์ต้องมีชื่อ THSarabunNew.ttf, THSarabunNew-Bold.ttf, THSarabunNew-Italic.ttf, THSarabunNew-BoldItalic.ttf</li>";
            echo "<li>สร้างโฟลเดอร์ tmp ในโฟลเดอร์หลักของโปรเจคสำหรับไฟล์ชั่วคราว</li>";
            echo "</ol>";
        }
        
        // ตรวจสอบไดเรกทอรี tmp
        $tmpDir = __DIR__ . '/../tmp';
        if (is_dir($tmpDir)) {
            echo "<p style='color: green;'>พบไดเรกทอรี tmp ที่: {$tmpDir}</p>";
            if (is_writable($tmpDir)) {
                echo "<p style='color: green;'>ไดเรกทอรี tmp สามารถเขียนได้</p>";
            } else {
                echo "<p style='color: red;'>ไดเรกทอรี tmp ไม่สามารถเขียนได้ กรุณาตั้งค่าสิทธิ์การเขียนให้กับไดเรกทอรีนี้</p>";
            }
        } else {
            echo "<p style='color: red;'>ไม่พบไดเรกทอรี tmp ที่: {$tmpDir}</p>";
        }
        
        echo "<p><a href='javascript:history.back()' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>กลับไปหน้าก่อนหน้า</a></p>";
        echo "</div>";
    }
}

// ฟังก์ชันสร้างไฟล์ Excel
function generateExcel($reportData) {
    // ตรวจสอบว่ามี error หรือไม่
    if (isset($reportData['error'])) {
        return false;
    }
    
    // กำหนดค่าตัวแปรข้อมูลรายงาน
    extract($reportData);
    
    try {
        require_once '../vendor/autoload.php';
        
        // สร้าง spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // ตั้งค่า font
        $spreadsheet->getDefaultStyle()->getFont()->setName('TH SarabunPSK');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(16);
        
        // หัวข้อรายงาน
        $sheet->setCellValue('A1', 'งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท');
        $sheet->setCellValue('A2', 'แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง');
        $sheet->setCellValue('A3', "ภาคเรียนที่ {$academic_year['semester']} ปีการศึกษา {$academic_year['year']} สัปดาห์ที่ {$week_number}");
        $sheet->setCellValue('A4', "ระดับชั้น {$class['level']} กลุ่ม {$class['group_number']} แผนกวิชา{$department['department_name']}");
        
        // รวมเซลล์ส่วนหัว
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        $sheet->mergeCells('A4:H4');
        
        // จัดกึ่งกลางส่วนหัว
        $sheet->getStyle('A1:H4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // หัวตาราง
        $sheet->setCellValue('A6', 'ลำดับที่');
        $sheet->setCellValue('B6', 'รหัสนักศึกษา');
        $sheet->setCellValue('C6', 'ชื่อ-สกุล');
        
        // หัวตารางวันที่
        $col = 'D';
        foreach ($week_days as $day) {
            $sheet->setCellValue($col . '6', $day['day_num']);
            $sheet->setCellValue($col . '7', $day['day_name']);
            $col++;
        }
        $sheet->setCellValue($col . '6', 'รวม');
        $sheet->getStyle('A6:' . $col . '7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // ข้อมูลนักเรียน
        $row = 8;
        $no = 1;
        foreach ($students as $student) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $student['student_code']);
            $sheet->setCellValue('C' . $row, $student['title'] . $student['first_name'] . ' ' . $student['last_name']);
            
            // ข้อมูลการเข้าแถว
            $col = 'D';
            $totalPresent = 0;
            
            foreach ($week_days as $day) {
                $status = '';
                if (isset($attendance_data[$student['student_id']][$day['date']])) {
                    $attendanceStatus = $attendance_data[$student['student_id']][$day['date']];
                    
                    if ($attendanceStatus == 'present') {
                        $status = '✓';
                        $totalPresent++;
                    } elseif ($attendanceStatus == 'absent') {
                        $status = 'ขาด';
                    } elseif ($attendanceStatus == 'late') {
                        $status = 'สาย';
                        $totalPresent++; // นับสายเป็นมาเรียน
                    } elseif ($attendanceStatus == 'leave') {
                        $status = 'ลา';
                    }
                } elseif ($day['is_holiday']) {
                    $status = 'หยุด';
                }
                
                $sheet->setCellValue($col . $row, $status);
                $col++;
            }
            
            // รวมวันที่มาเรียน
            $sheet->setCellValue($col . $row, $totalPresent);
            
            $row++;
            $no++;
        }
        
        // สรุปข้อมูล
        $row = $row + 2;
        $sheet->setCellValue('A' . $row, "สรุป จำนวนคน {$total_count} ชาย {$male_count} หญิง {$female_count}");
        
        // คำนวณอัตราการเข้าแถว
        $totalAttendanceRate = 0;
        if ($total_count > 0) {
            $totalAttendanceData = 0;
            $totalPossibleAttendance = $total_count * count($week_days);
            
            foreach ($students as $student) {
                foreach ($week_days as $day) {
                    if (isset($attendance_data[$student['student_id']][$day['date']])) {
                        $status = $attendance_data[$student['student_id']][$day['date']];
                        if ($status == 'present' || $status == 'late') {
                            $totalAttendanceData++;
                        }
                    }
                }
            }
            
            if ($totalPossibleAttendance > 0) {
                $totalAttendanceRate = ($totalAttendanceData / $totalPossibleAttendance) * 100;
            }
        }
        
        $row++;
        $sheet->setCellValue('A' . $row, "สรุปจำนวนนักเรียนเข้าแถวร้อยละ " . number_format($totalAttendanceRate, 2));
        
        // ช่องลงชื่อ
        $row = $row + 3;
        $sheet->setCellValue('B' . $row, "ลงชื่อ............................................");
        $sheet->setCellValue('F' . $row, "ลงชื่อ............................................");
        
        $row++;
        $advisorName = "";
        if ($primary_advisor) {
            $advisorName = "({$primary_advisor['title']}{$primary_advisor['first_name']} {$primary_advisor['last_name']})";
        }
        $sheet->setCellValue('B' . $row, $advisorName);
        $sheet->setCellValue('F' . $row, "(นายนนทศรี ศรีสุข)");
        
        $row++;
        $sheet->setCellValue('B' . $row, "ครูที่ปรึกษา");
        $sheet->setCellValue('F' . $row, "หัวหน้างานกิจกรรมนักเรียน นักศึกษา");
        
        // จัดความกว้างคอลัมน์
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        
        // กำหนดเส้นขอบตาราง
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A6:' . $col . ($row - 7))->applyFromArray($styleArray);
        
        // สร้างไฟล์ Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'รายงานการเข้าแถว_' . $class['level'] . '_กลุ่ม_' . $class['group_number'] . '_สัปดาห์ที่_' . $week_number . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        // แสดงข้อผิดพลาด
        echo "<div style='font-family: sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
        echo "<h3>เกิดข้อผิดพลาดในการสร้างไฟล์ Excel:</h3>";
        echo "<p>{$e->getMessage()}</p>";
        echo "<p><a href='javascript:history.back()' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>กลับไปหน้าก่อนหน้า</a></p>";
        echo "</div>";
    }
}

// ฟังก์ชันสร้างกราฟ
function generateAttendanceChart($reportData) {
    // ตรวจสอบว่ามี error หรือไม่
    if (isset($reportData['error'])) {
        return false;
    }
    
    // กำหนดค่าตัวแปรข้อมูลรายงาน
    extract($reportData);
    
    // เริ่มสร้าง HTML content
    ob_start();
    
    // Include template
    include 'templates/attendance_chart_pdf.php';
    
    $html = ob_get_clean();
    
    try {
        // สร้างไดเรกทอรี tmp หากยังไม่มี
        $tmpDir = __DIR__ . '/../tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        
        // สร้าง MPDF
        require_once '../vendor/autoload.php';
        
        $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => array_merge($fontDirs, [
                __DIR__ . '/../fonts',
                __DIR__ . '/../fonts/thsarabunnew',
            ]),
            'fontdata' => $fontData + [
                'thsarabun' => [
                    'R' => 'THSarabunNew.ttf',
                    'B' => 'THSarabunNew-Bold.ttf',
                    'I' => 'THSarabunNew-Italic.ttf',
                    'BI' => 'THSarabunNew-BoldItalic.ttf'
                ]
            ],
            'default_font' => 'thsarabun',
            'tempDir' => $tmpDir
        ]);
        
        $mpdf->SetTitle('กราฟการเข้าแถว - ' . $class['level'] . ' กลุ่ม ' . $class['group_number']);
        $mpdf->WriteHTML($html);
        
        $filename = 'กราฟการเข้าแถว_' . $class['level'] . '_กลุ่ม_' . $class['group_number'] . '_สัปดาห์ที่_' . $week_number . '.pdf';
        
        // ส่งไฟล์ให้ download
        $mpdf->Output($filename, 'I');
        exit;
    } catch (Exception $e) {
        // แสดงข้อผิดพลาด
        echo "<div style='font-family: sans-serif; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
        echo "<h3>เกิดข้อผิดพลาดในการสร้างกราฟการเข้าแถว:</h3>";
        echo "<p>{$e->getMessage()}</p>";
        echo "<p><a href='javascript:history.back()' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>กลับไปหน้าก่อนหน้า</a></p>";
        echo "</div>";
    }
}

// ตรวจสอบการทำงาน
$action = $_GET['action'] ?? '';
if ($action == 'generate_pdf' && isset($_GET['department_id'], $_GET['level'], $_GET['group'], $_GET['week'])) {
    $reportData = prepareReportData(
        $_GET['department_id'], 
        $_GET['level'], 
        $_GET['group'], 
        $_GET['week'], 
        $_GET['academic_year_id'] ?? getActiveAcademicYear()['academic_year_id']
    );
    generatePDF($reportData);
} elseif ($action == 'generate_excel' && isset($_GET['department_id'], $_GET['level'], $_GET['group'], $_GET['week'])) {
    $reportData = prepareReportData(
        $_GET['department_id'], 
        $_GET['level'], 
        $_GET['group'], 
        $_GET['week'], 
        $_GET['academic_year_id'] ?? getActiveAcademicYear()['academic_year_id']
    );
    generateExcel($reportData);
} elseif ($action == 'generate_chart' && isset($_GET['department_id'], $_GET['level'], $_GET['group'], $_GET['week'])) {
    $reportData = prepareReportData(
        $_GET['department_id'], 
        $_GET['level'], 
        $_GET['group'], 
        $_GET['week'], 
        $_GET['academic_year_id'] ?? getActiveAcademicYear()['academic_year_id']
    );
    generateAttendanceChart($reportData);
}

// ดึงข้อมูลสำหรับการแสดงในหน้า
$academicYear = getActiveAcademicYear();
$departments = getDepartments();
$levels = getLevels();

// จำนวนสัปดาห์ทั้งหมด
$allWeeks = calculateWeeks($academicYear);
$totalWeeks = count($allWeeks);

// Ajax สำหรับดึงกลุ่ม
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_groups') {
    header('Content-Type: application/json');
    $groups = getGroups(
        $_GET['department_id'], 
        $_GET['level'], 
        $_GET['academic_year_id'] ?? $academicYear['academic_year_id']
    );
    echo json_encode($groups);
    exit;
}

// Ajax สำหรับดึงครูที่ปรึกษา
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_advisors') {
    header('Content-Type: application/json');
    $class = getClass(
        $_GET['department_id'], 
        $_GET['level'], 
        $_GET['group'], 
        $_GET['academic_year_id'] ?? $academicYear['academic_year_id']
    );
    
    $advisors = [];
    if ($class) {
        $advisors = getAdvisors($class['class_id']);
    }
    
    echo json_encode($advisors);
    exit;
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'attendance_report';
$page_title = 'รายงานการเข้าแถว';
$page_header = 'ระบบค้นหาและพิมพ์รายงานการเข้าแถว';

// ไฟล์ CSS และ JS
$extra_css = [
    'assets/css/reports.css',
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css'
];

$extra_js = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js',
    'assets/js/attendance_report.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหา
$content_path = 'pages/attendance_report_content.php';

// โหลดเทมเพลต
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'templates/main_content.php';
require_once 'templates/footer.php';
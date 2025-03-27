<?php
/**
 * classes.php - หน้าจัดการข้อมูลชั้นเรียน
 * 
 * ส่วนหนึ่งของระบบ STP-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'classes';
$page_title = 'จัดการชั้นเรียน';
$page_header = 'ข้อมูลและการจัดการชั้นเรียน';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มชั้นเรียนใหม่',
        'icon' => 'add',
        'onclick' => 'showAddClassModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadClassReport()'
    ],
    [
        'text' => 'สถิติชั้นเรียน',
        'icon' => 'leaderboard',
        'onclick' => 'showClassStatistics()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/classes.css'
];

$extra_js = [
    'assets/js/classes.js',
    'assets/js/charts.js'
];

// นำเข้าไฟล์เชื่อมต่อฐานข้อมูล 
require_once '../db_connect.php';

// ดึงข้อมูลชั้นเรียนจากฐานข้อมูล
function getClassesFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                c.class_id,
                c.academic_year_id,
                c.level,
                c.group_number,
                c.department_id,
                d.department_name,
                (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            WHERE c.is_active = 1
            ORDER BY c.level, c.group_number";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $classesResult = $stmt->fetchAll();
        
        $classes = [];
        foreach ($classesResult as $row) {
            // ดึงข้อมูลครูที่ปรึกษา
            $advisorQuery = "SELECT 
                    t.teacher_id,
                    CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) AS name,
                    ca.is_primary
                FROM class_advisors ca
                JOIN teachers t ON ca.teacher_id = t.teacher_id
                WHERE ca.class_id = :class_id
                ORDER BY ca.is_primary DESC";
                
            $advisorStmt = $db->prepare($advisorQuery);
            $advisorStmt->bindParam(':class_id', $row['class_id'], PDO::PARAM_INT);
            $advisorStmt->execute();
            $advisorResult = $advisorStmt->fetchAll();
            
            $advisors = [];
            foreach ($advisorResult as $advisor) {
                $advisors[] = [
                    'id' => $advisor['teacher_id'],
                    'name' => $advisor['name'],
                    'is_primary' => (bool)$advisor['is_primary']
                ];
            }
            
            // สร้างข้อมูลการเข้าแถว (กรณีนี้จะใช้ข้อมูลตัวอย่าง เพราะการคำนวณจริงต้องใช้ข้อมูลจากตาราง attendance)
            $attendanceRate = rand(75, 100);
            
            $classes[] = [
                'class_id' => $row['class_id'],
                'academic_year_id' => $row['academic_year_id'],
                'level' => $row['level'],
                'department' => $row['department_name'],
                'group_number' => $row['group_number'],
                'student_count' => $row['student_count'],
                'attendance_rate' => $attendanceRate,
                'status' => $attendanceRate > 90 ? 'good' : ($attendanceRate > 75 ? 'warning' : 'danger'),
                'advisors' => $advisors
            ];
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log("Error fetching classes: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลแผนกวิชาจากฐานข้อมูล
function getDepartmentsFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                department_id,
                department_code,
                department_name 
            FROM departments
            WHERE is_active = 1
            ORDER BY department_name";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $departmentsResult = $stmt->fetchAll();
        
        $departments = [];
        foreach ($departmentsResult as $row) {
            // ดึงจำนวนนักเรียน
            $studentCountQuery = "SELECT COUNT(*) AS count 
                FROM students s 
                JOIN classes c ON s.current_class_id = c.class_id 
                WHERE c.department_id = :department_id 
                AND s.status = 'กำลังศึกษา'";
                
            $studentStmt = $db->prepare($studentCountQuery);
            $studentStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $studentStmt->execute();
            $studentData = $studentStmt->fetch();
            
            // ดึงจำนวนชั้นเรียน
            $classCountQuery = "SELECT COUNT(DISTINCT class_id) AS count 
                FROM classes 
                WHERE department_id = :department_id 
                AND is_active = 1";
                
            $classStmt = $db->prepare($classCountQuery);
            $classStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $classStmt->execute();
            $classData = $classStmt->fetch();
            
            // ดึงจำนวนครู
            $teacherCountQuery = "SELECT COUNT(*) AS count 
                FROM teachers 
                WHERE department_id = :department_id";
                
            $teacherStmt = $db->prepare($teacherCountQuery);
            $teacherStmt->bindParam(':department_id', $row['department_id'], PDO::PARAM_INT);
            $teacherStmt->execute();
            $teacherData = $teacherStmt->fetch();
            
            $departments[$row['department_code']] = [
                'name' => $row['department_name'],
                'student_count' => $studentData['count'],
                'class_count' => $classData['count'],
                'teacher_count' => $teacherData['count']
            ];
        }
        
        return $departments;
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลปีการศึกษาจากฐานข้อมูล
function getAcademicYearsFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                academic_year_id,
                year,
                semester,
                is_active,
                start_date,
                end_date,
                required_attendance_days
            FROM academic_years
            ORDER BY year DESC, semester DESC";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $academic_years = $stmt->fetchAll();
        
        // ดึงข้อมูลปีการศึกษาปัจจุบัน
        $activeYearQuery = "SELECT academic_year_id, year, semester
            FROM academic_years
            WHERE is_active = 1
            LIMIT 1";
            
        $activeYearStmt = $db->prepare($activeYearQuery);
        $activeYearStmt->execute();
        
        if ($activeYearStmt->rowCount() > 0) {
            $activeYear = $activeYearStmt->fetch();
            
            // ตรวจสอบว่ามีปีการศึกษาถัดไปหรือไม่
            $nextYearQuery = "SELECT academic_year_id, year, semester
                FROM academic_years
                WHERE (year > :year) OR (year = :year AND semester > :semester)
                ORDER BY year ASC, semester ASC
                LIMIT 1";
                
            $nextYearStmt = $db->prepare($nextYearQuery);
            $nextYearStmt->bindParam(':year', $activeYear['year'], PDO::PARAM_INT);
            $nextYearStmt->bindParam(':semester', $activeYear['semester'], PDO::PARAM_INT);
            $nextYearStmt->execute();
            
            $has_new_academic_year = ($nextYearStmt->rowCount() > 0);
            $current_academic_year = $activeYear['year'] . ' ภาคเรียนที่ ' . $activeYear['semester'];
            
            if ($has_new_academic_year) {
                $nextYear = $nextYearStmt->fetch();
                $next_academic_year = $nextYear['year'] . ' ภาคเรียนที่ ' . $nextYear['semester'];
            } else {
                $next_academic_year = '';
            }
        } else {
            $has_new_academic_year = false;
            $current_academic_year = '';
            $next_academic_year = '';
        }
        
        return [
            'academic_years' => $academic_years,
            'has_new_academic_year' => $has_new_academic_year,
            'current_academic_year' => $current_academic_year, 
            'next_academic_year' => $next_academic_year,
            'active_year_id' => $activeYear['academic_year_id'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Error fetching academic years: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
function getAtRiskStudentCount() {
    try {
        $db = getDB();
        $query = "SELECT COUNT(*) AS count
            FROM risk_students rs
            JOIN students s ON rs.student_id = s.student_id
            JOIN academic_years ay ON rs.academic_year_id = ay.academic_year_id
            WHERE rs.risk_level IN ('high', 'critical')
            AND s.status = 'กำลังศึกษา'
            AND ay.is_active = 1";
            
        $stmt = $db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['count'];
    } catch (PDOException $e) {
        error_log("Error fetching at-risk count: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลการเลื่อนชั้น
function getPromotionCounts($academic_year_id) {
    try {
        $db = getDB();
        $query = "SELECT 
                c.level AS current_level,
                COUNT(s.student_id) AS student_count,
                CASE 
                    WHEN c.level = 'ปวช.1' THEN 'ปวช.2'
                    WHEN c.level = 'ปวช.2' THEN 'ปวช.3'
                    WHEN c.level = 'ปวช.3' THEN 'สำเร็จการศึกษา'
                    WHEN c.level = 'ปวส.1' THEN 'ปวส.2'
                    WHEN c.level = 'ปวส.2' THEN 'สำเร็จการศึกษา'
                    ELSE c.level
                END AS new_level
            FROM 
                students s
                JOIN classes c ON s.current_class_id = c.class_id
            WHERE 
                s.status = 'กำลังศึกษา'
                AND c.academic_year_id = :academic_year_id
            GROUP BY 
                current_level, new_level
            ORDER BY 
                c.level";
                
        $stmt = $db->prepare($query);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->execute();
        $promotion_counts = $stmt->fetchAll();
        return $promotion_counts;
    } catch (PDOException $e) {
        error_log("Error fetching promotion counts: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลครูทั้งหมด
function getTeachersFromDB() {
    try {
        $db = getDB();
        $query = "SELECT 
                t.teacher_id,
                t.title,
                t.first_name,
                t.last_name,
                d.department_name
            FROM 
                teachers t
                LEFT JOIN departments d ON t.department_id = d.department_id
            ORDER BY 
                t.first_name, t.last_name";
                
        $stmt = $db->prepare($query);
        $stmt->execute();
        $teachers = $stmt->fetchAll();
        return $teachers;
    } catch (PDOException $e) {
        error_log("Error fetching teachers: " . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลจากฐานข้อมูล
$classes = getClassesFromDB();
$departments = getDepartmentsFromDB();
$academicYearData = getAcademicYearsFromDB();
$at_risk_count_db = getAtRiskStudentCount();
$teachers = getTeachersFromDB();

// ถ้าดึงข้อมูลไม่สำเร็จ ให้ใช้ข้อมูลตัวอย่าง
if ($classes === false) {
    $classes = [
        [
            'class_id' => 1,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '1',
            'attendance_rate' => 94.3,
            'status' => 'good',
            'student_count' => 35,
            'advisors' => [
                ['name' => 'นาย มนตรี ศรีสุข', 'is_primary' => true]
            ]
        ],
        [
            'class_id' => 2,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => '2',
            'attendance_rate' => 87.5,
            'status' => 'warning',
            'student_count' => 32,
            'advisors' => [
                ['name' => 'นาง ราตรี นอนดึก', 'is_primary' => true]
            ]
        ]
    ];
}

if ($departments === false) {
    $departments = [
        'IT' => ['name' => 'เทคโนโลยีสารสนเทศ', 'student_count' => 110, 'class_count' => 4, 'teacher_count' => 6],
        'AUTO' => ['name' => 'ช่างยนต์', 'student_count' => 120, 'class_count' => 4, 'teacher_count' => 8],
        'GEN' => ['name' => 'สามัญ', 'student_count' => 0, 'class_count' => 0, 'teacher_count' => 12]
    ];
}

if ($academicYearData === false) {
    $academic_years = [
        ['academic_year_id' => 1, 'year' => '2568', 'semester' => '1', 'is_active' => 1]
    ];
    $has_new_academic_year = true;
    $current_academic_year = '2567 ภาคเรียนที่ 2';
    $next_academic_year = '2568 ภาคเรียนที่ 1';
    $active_year_id = 1;
} else {
    $academic_years = $academicYearData['academic_years'];
    $has_new_academic_year = $academicYearData['has_new_academic_year'];
    $current_academic_year = $academicYearData['current_academic_year'];
    $next_academic_year = $academicYearData['next_academic_year'];
    $active_year_id = $academicYearData['active_year_id'];
}

if ($at_risk_count_db !== false) {
    $at_risk_count = $at_risk_count_db;
}

if ($teachers === false) {
    $teachers = [
        ['teacher_id' => 1, 'title' => 'นาย', 'first_name' => 'ใจดี', 'last_name' => 'มากเมตตา'],
        ['teacher_id' => 2, 'title' => 'นาง', 'first_name' => 'ราตรี', 'last_name' => 'นอนดึก'],
        ['teacher_id' => 3, 'title' => 'นาย', 'first_name' => 'มานะ', 'last_name' => 'พยายาม'],
        ['teacher_id' => 4, 'title' => 'นางสาว', 'first_name' => 'วันดี', 'last_name' => 'สดใส'],
        ['teacher_id' => 5, 'title' => 'นาง', 'first_name' => 'สมศรี', 'last_name' => 'ใจดี']
    ];
}

// ข้อมูลการเลื่อนชั้นจะดึงเมื่อมีปีการศึกษาถัดไป
if ($has_new_academic_year && $active_year_id !== null) {
    $promotion_counts = getPromotionCounts($active_year_id);
    
    if ($promotion_counts === false) {
        $promotion_counts = [
            ['current_level' => 'ปวช.1', 'student_count' => 120, 'new_level' => 'ปวช.2'],
            ['current_level' => 'ปวช.2', 'student_count' => 105, 'new_level' => 'ปวช.3'],
            ['current_level' => 'ปวช.3', 'student_count' => 95, 'new_level' => 'สำเร็จการศึกษา'],
            ['current_level' => 'ปวส.1', 'student_count' => 80, 'new_level' => 'ปวส.2'],
            ['current_level' => 'ปวส.2', 'student_count' => 75, 'new_level' => 'สำเร็จการศึกษา']
        ];
    }
} else {
    $promotion_counts = [];
}

// ส่งข้อมูลไปยังเทมเพลต
$data = [
    'classes' => $classes,
    'departments' => $departments,
    'academic_years' => $academic_years,
    'has_new_academic_year' => $has_new_academic_year,
    'current_academic_year' => $current_academic_year,
    'next_academic_year' => $next_academic_year,
    'promotion_counts' => $promotion_counts,
    'teachers' => $teachers
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/classes_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>